package websocket

import (
	"bufio"
	"crypto/sha1"
	"encoding/base64"
	"encoding/binary"
	"errors"
	"io"
	"net"
	"net/http"
	"strings"
	"time"
)

const (
	opContinuation = 0x0

	Optext   = 0x1
	Opbinary = 0x2
	OpClose  = 0x8
	OpPing   = 0x9
	OpPong   = 0xA
)

const (
	maxFrameSize = 32768
)

var (
	guid      = []byte("258EAFA5-E914-47DA-95CA-C5AB0DC85B11")
	respPart1 = []byte("HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: ")
	respPart2 = []byte("\r\n\r\n")

	MaxMessageSize = 4 * 1024 * 1024
)

type Conn struct {
	conn net.Conn
	br   *bufio.Reader

	onPingHandler  func(payload []byte)
	onPongHandler  func(payload []byte)
	onCloseHandler func(payload []byte)

	readHdrBuf  [14]byte
	writeHdrBuf [10]byte
	keyBuf      [128]byte
	ctrlBuf     [125]byte

	msgBuf          []byte
	fragmentedOp    int
	inFragmentation bool
}

type HandlerFunc func(*Conn)

func NewConn(conn net.Conn, br *bufio.Reader) *Conn {
	return &Conn{
		conn:   conn,
		br:     br,
		msgBuf: make([]byte, 0, MaxMessageSize),
	}
}

func (w *Conn) SetReadDeadline(t time.Time) error  { return w.conn.SetReadDeadline(t) }
func (w *Conn) SetWriteDeadline(t time.Time) error { return w.conn.SetWriteDeadline(t) }
func (w *Conn) SetDeadline(t time.Time) error      { return w.conn.SetDeadline(t) }

func (w *Conn) OnPing(h func([]byte))  { w.onPingHandler = h }
func (w *Conn) OnPong(h func([]byte))  { w.onPongHandler = h }
func (w *Conn) OnClose(h func([]byte)) { w.onCloseHandler = h }

func Upgrade(w http.ResponseWriter, r *http.Request) (*Conn, error) {
	if r.Method != http.MethodGet {
		http.Error(w, "WebSocket handshake must use GET", http.StatusMethodNotAllowed)
		return nil, errors.New("websocket: handshake must use GET")
	}

	if !strings.EqualFold(r.Header.Get("Upgrade"), "websocket") {
		http.Error(w, "Missing or invalid Upgrade header", http.StatusBadRequest)
		return nil, errors.New("websocket: missing or invalid Upgrade header")
	}

	if !strings.Contains(strings.ToLower(r.Header.Get("Connection")), "upgrade") {
		http.Error(w, "Missing or invalid Connection header", http.StatusBadRequest)
		return nil, errors.New("websocket: missing or invalid Connection header")
	}

	if r.Header.Get("Sec-WebSocket-Version") != "13" {
		w.Header().Set("Sec-WebSocket-Version", "13")
		http.Error(w, "Unsupported WebSocket version", http.StatusUpgradeRequired)
		return nil, errors.New("websocket: unsupported WebSocket version")
	}

	challengeKey := r.Header.Get("Sec-WebSocket-Key")
	if challengeKey == "" {
		http.Error(w, "Missing Sec-WebSocket-Key", http.StatusBadRequest)
		return nil, errors.New("websocket: missing challenge key")
	}

	hijacker, ok := w.(http.Hijacker)
	if !ok {
		http.Error(w, "Webserver doesn't support hijacking", http.StatusInternalServerError)
		return nil, errors.New("websocket: hijacker not supported")
	}

	netConn, brw, err := hijacker.Hijack()
	if err != nil {
		return nil, err
	}

	ws := NewConn(netConn, brw.Reader)

	keyLen := copy(ws.keyBuf[:], challengeKey)
	keyLen += copy(ws.keyBuf[keyLen:], guid)
	hash := sha1.Sum(ws.keyBuf[:keyLen])

	var accept [28]byte
	base64.StdEncoding.Encode(accept[:], hash[:])

	var bufs net.Buffers
	bufs = append(bufs,
		respPart1,
		accept[:],
		respPart2,
	)

	if _, err := bufs.WriteTo(ws.conn); err != nil {
		netConn.Close()
		return nil, err
	}

	return ws, nil
}

func (w *Conn) ReadMessage() (int, []byte, error) {
	w.msgBuf = w.msgBuf[:0]

	for {
		if _, err := io.ReadFull(w.br, w.readHdrBuf[:2]); err != nil {
			return 0, nil, err
		}

		fin := (w.readHdrBuf[0] & 0x80) != 0
		if (w.readHdrBuf[0] & 0x70) != 0 {
			return 0, nil, errors.New("protocol error: reserved bits set")
		}

		frameOpcode := int(w.readHdrBuf[0] & 0x0F)
		switch frameOpcode {
		case opContinuation, Optext, Opbinary, OpClose, OpPing, OpPong:
		default:
			return 0, nil, errors.New("protocol error: invalid opcode")
		}
		masked := (w.readHdrBuf[1] & 0x80) != 0
		payloadLen64 := uint64(w.readHdrBuf[1] & 0x7F)

		if !masked {
			return 0, nil, errors.New("protocol error: unmasked client frame received")
		}

		if payloadLen64 == 126 {
			if _, err := io.ReadFull(w.br, w.readHdrBuf[2:4]); err != nil {
				return 0, nil, err
			}
			payloadLen64 = uint64(binary.BigEndian.Uint16(w.readHdrBuf[2:4]))
		} else if payloadLen64 == 127 {
			if _, err := io.ReadFull(w.br, w.readHdrBuf[2:10]); err != nil {
				return 0, nil, err
			}
			payloadLen64 = binary.BigEndian.Uint64(w.readHdrBuf[2:10])
		}

		payloadLen := int(payloadLen64)
		isControl := frameOpcode == OpClose || frameOpcode == OpPing || frameOpcode == OpPong

		if isControl {
			if payloadLen > 125 {
				return 0, nil, errors.New("protocol error: control frame payload exceeded 125 bytes")
			}
			if !fin {
				return 0, nil, errors.New("protocol error: control frames cannot be fragmented")
			}
		} else {
			if uint64(len(w.msgBuf))+payloadLen64 > uint64(MaxMessageSize) {
				return 0, nil, errors.New("protocol error: message size exceeds max limit")
			}
		}

		var maskKey [4]byte
		if _, err := io.ReadFull(w.br, maskKey[:]); err != nil {
			return 0, nil, err
		}

		if isControl {
			controlPayload := w.ctrlBuf[:payloadLen]
			if _, err := io.ReadFull(w.br, controlPayload); err != nil {
				return 0, nil, err
			}

			for i := 0; i < len(controlPayload); i++ {
				controlPayload[i] ^= maskKey[i%4]
			}

			switch frameOpcode {
			case OpClose:
				if w.onCloseHandler != nil {
					w.onCloseHandler(controlPayload)
				}
				return 0, nil, io.EOF
			case OpPing:
				if w.onPingHandler != nil {
					w.onPingHandler(controlPayload)
				}
			case OpPong:
				if w.onPongHandler != nil {
					w.onPongHandler(controlPayload)
				}
			}
			continue
		}

		if w.inFragmentation {
			if frameOpcode != opContinuation {
				return 0, nil, errors.New("protocol error: expected continuation frame")
			}
		} else {
			if frameOpcode == opContinuation {
				return 0, nil, errors.New("protocol error: unexpected continuation frame")
			}
			w.fragmentedOp = frameOpcode
			if !fin {
				w.inFragmentation = true
			}
		}

		startIdx := len(w.msgBuf)
		neededCap := startIdx + payloadLen

		if neededCap > cap(w.msgBuf) {
			newCap := cap(w.msgBuf) * 2
			if newCap < neededCap {
				newCap = neededCap
			}
			newBuf := make([]byte, startIdx, newCap)
			copy(newBuf, w.msgBuf[:startIdx])
			w.msgBuf = newBuf
		}
		w.msgBuf = w.msgBuf[:neededCap]

		if _, err := io.ReadFull(w.br, w.msgBuf[startIdx:neededCap]); err != nil {
			return 0, nil, err
		}

		payload := w.msgBuf[startIdx:neededCap]

		blen := (len(payload) / 4) * 4
		for i := 0; i < blen; i += 4 {
			payload[i] ^= maskKey[0]
			payload[i+1] ^= maskKey[1]
			payload[i+2] ^= maskKey[2]
			payload[i+3] ^= maskKey[3]
		}
		for i := blen; i < len(payload); i++ {
			payload[i] ^= maskKey[i%4]
		}

		if fin {
			w.inFragmentation = false
			return w.fragmentedOp, w.msgBuf, nil
		}
	}
}

func (w *Conn) WriteFrame(fin bool, opcode int, payload []byte) error {
	var firstByte byte = byte(opcode)
	if fin {
		firstByte |= 0x80
	}
	w.writeHdrBuf[0] = firstByte

	length := len(payload)
	var headerLen int

	switch {
	case length < 126:
		w.writeHdrBuf[1] = byte(length)
		headerLen = 2

	case length <= 65535:
		w.writeHdrBuf[1] = 126
		binary.BigEndian.PutUint16(w.writeHdrBuf[2:4], uint16(length))
		headerLen = 4

	default:
		w.writeHdrBuf[1] = 127
		binary.BigEndian.PutUint64(w.writeHdrBuf[2:10], uint64(length))
		headerLen = 10
	}

	var bufs net.Buffers
	bufs = append(bufs,
		w.writeHdrBuf[:headerLen],
		payload,
	)

	_, err := bufs.WriteTo(w.conn)
	return err
}

func (w *Conn) WriteMessage(opcode int, data []byte) error {
	if len(data) <= maxFrameSize {
		return w.WriteFrame(true, opcode, data)
	}

	if err := w.WriteFrame(false, opcode, data[:maxFrameSize]); err != nil {
		return err
	}
	data = data[maxFrameSize:]

	for len(data) > maxFrameSize {
		if err := w.WriteFrame(false, opContinuation, data[:maxFrameSize]); err != nil {
			return err
		}
		data = data[maxFrameSize:]
	}

	return w.WriteFrame(true, opContinuation, data)
}

func (w *Conn) Close() error {
	return w.conn.Close()
}
