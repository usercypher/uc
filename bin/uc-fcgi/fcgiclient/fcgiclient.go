package fcgiclient

import (
	"bufio"
	"bytes"
	"encoding/binary"
	"errors"
	"io"
	"net"
	"sync"
	"time"
)

const (
	FCGI_BEGIN_REQUEST uint8 = iota + 1
	FCGI_ABORT_REQUEST
	FCGI_END_REQUEST
	FCGI_PARAMS
	FCGI_STDIN
	FCGI_STDOUT
	FCGI_STDERR
	FCGI_DATA
	FCGI_GET_VALUES
	FCGI_GET_VALUES_RESULT
	FCGI_UNKNOWN_TYPE
	FCGI_MAXTYPE = FCGI_UNKNOWN_TYPE
)

const (
	FCGI_RESPONDER uint8 = iota + 1
)

const (
	maxWrite = 65500
	maxPad   = 255
)

type header struct {
	Version       uint8
	Type          uint8
	Id            uint16
	ContentLength uint16
	PaddingLength uint8
	Reserved      uint8
}

var pad [maxPad]byte

func (h *header) init(recType uint8, reqId uint16, contentLength int) {
	h.Version = 1
	h.Type = recType
	h.Id = reqId
	h.ContentLength = uint16(contentLength)
	h.PaddingLength = uint8(-contentLength & 7)
}

type record struct {
	h    header
	rbuf []byte
}

func (rec *record) read(r io.Reader) (buf []byte, err error) {
	var headerBuf [8]byte
	if _, err = io.ReadFull(r, headerBuf[:]); err != nil {
		return
	}

	rec.h.Version = headerBuf[0]
	rec.h.Type = headerBuf[1]
	rec.h.Id = (uint16(headerBuf[2]) << 8) | uint16(headerBuf[3])
	rec.h.ContentLength = (uint16(headerBuf[4]) << 8) | uint16(headerBuf[5])
	rec.h.PaddingLength = headerBuf[6]
	rec.h.Reserved = headerBuf[7]

	if rec.h.Version != 1 {
		err = errors.New("fcgi: invalid header version")
		return
	}
	if rec.h.Type == FCGI_END_REQUEST {
		err = io.EOF
		return
	}

	n := int(rec.h.ContentLength) + int(rec.h.PaddingLength)
	if len(rec.rbuf) < n {
		rec.rbuf = make([]byte, n)
	}
	if n, err = io.ReadFull(r, rec.rbuf[:n]); err != nil {
		return
	}
	buf = rec.rbuf[:int(rec.h.ContentLength)]

	return
}

type FCGIClient struct {
	mutex     sync.Mutex
	rwc       io.ReadWriteCloser
	h         header
	buf       bytes.Buffer
	keepAlive bool
	reqId     uint16
}

func Dial(network, address string) (fcgi *FCGIClient, err error) {
	var conn net.Conn
	conn, err = net.Dial(network, address)
	if err != nil {
		return
	}
	fcgi = &FCGIClient{
		rwc:       conn,
		keepAlive: false,
		reqId:     1,
	}
	return
}

func DialTimeout(network, address string, timeout time.Duration) (fcgi *FCGIClient, err error) {
	var conn net.Conn
	conn, err = net.DialTimeout(network, address, timeout)
	if err != nil {
		return
	}
	fcgi = &FCGIClient{
		rwc:       conn,
		keepAlive: false,
		reqId:     1,
	}
	return
}

func (this *FCGIClient) Close() {
	this.rwc.Close()
}

func (this *FCGIClient) writeRecord(recType uint8, content []byte) (err error) {
	this.mutex.Lock()
	defer this.mutex.Unlock()
	this.buf.Reset()
	this.h.init(recType, this.reqId, len(content))

	var headerBuf [8]byte
	headerBuf[0] = this.h.Version
	headerBuf[1] = this.h.Type
	headerBuf[2] = byte(this.h.Id >> 8)
	headerBuf[3] = byte(this.h.Id)
	headerBuf[4] = byte(this.h.ContentLength >> 8)
	headerBuf[5] = byte(this.h.ContentLength)
	headerBuf[6] = this.h.PaddingLength
	headerBuf[7] = this.h.Reserved

	if _, err := this.buf.Write(headerBuf[:]); err != nil {
		return err
	}
	if _, err := this.buf.Write(content); err != nil {
		return err
	}
	if _, err := this.buf.Write(pad[:this.h.PaddingLength]); err != nil {
		return err
	}
	_, err = this.rwc.Write(this.buf.Bytes())
	return err
}

func (this *FCGIClient) writeBeginRequest(role uint16, flags uint8) error {
	b := [8]byte{byte(role >> 8), byte(role), flags}
	return this.writeRecord(FCGI_BEGIN_REQUEST, b[:])
}

var writerPool = sync.Pool{
	New: func() interface{} {
		s := &streamWriter{}
		return &bufWriter{
			stream: s,
			Writer: bufio.NewWriterSize(s, maxWrite),
		}
	},
}

func (this *FCGIClient) writePairs(recType uint8, pairs map[string]string) error {
	w := writerPool.Get().(*bufWriter)
	w.stream.c = this
	w.stream.recType = recType
	w.Writer.Reset(w.stream)

	var b [8]byte
	nn := 0
	for k, v := range pairs {
		m := 8 + len(k) + len(v)
		if m > maxWrite {
			vl := maxWrite - 8 - len(k)
			v = v[:vl]
		}
		n := encodeSize(b[:], uint32(len(k)))
		n += encodeSize(b[n:], uint32(len(v)))
		m = n + len(k) + len(v)
		if (nn + m) > maxWrite {
			w.Flush()
			nn = 0
		}
		nn += m
		if _, err := w.Write(b[:n]); err != nil {
			writerPool.Put(w)
			return err
		}
		if _, err := w.WriteString(k); err != nil {
			writerPool.Put(w)
			return err
		}
		if _, err := w.WriteString(v); err != nil {
			writerPool.Put(w)
			return err
		}
	}
	
	err := w.CloseStream()
	writerPool.Put(w)
	return err
}

func encodeSize(b []byte, size uint32) int {
	if size > 127 {
		size |= 1 << 31
		binary.BigEndian.PutUint32(b, size)
		return 4
	}
	b[0] = byte(size)
	return 1
}

type bufWriter struct {
	stream *streamWriter
	*bufio.Writer
}

func (w *bufWriter) CloseStream() error {
	if err := w.Writer.Flush(); err != nil {
		return err
	}
	return w.stream.Close()
}

type streamWriter struct {
	c       *FCGIClient
	recType uint8
}

func (w *streamWriter) Write(p []byte) (int, error) {
	nn := 0
	for len(p) > 0 {
		n := len(p)
		if n > maxWrite {
			n = maxWrite
		}
		if err := w.c.writeRecord(w.recType, p[:n]); err != nil {
			return nn, err
		}
		nn += n
		p = p[n:]
	}
	return nn, nil
}

func (w *streamWriter) Close() error {
	return w.c.writeRecord(w.recType, nil)
}

type streamReader struct {
	c   *FCGIClient
	buf []byte
	rec record
}

func (w *streamReader) Read(p []byte) (n int, err error) {
	if len(p) > 0 {
		if len(w.buf) == 0 {
			w.rec.h = header{}
			w.buf, err = w.rec.read(w.c.rwc)
			if err != nil {
				return
			}
		}

		n = len(p)
		if n > len(w.buf) {
			n = len(w.buf)
		}
		copy(p, w.buf[:n])
		w.buf = w.buf[n:]
	}
	return
}

func (this *FCGIClient) Do(p map[string]string, req io.Reader) (r io.Reader, err error) {
	err = this.writeBeginRequest(uint16(FCGI_RESPONDER), 0)
	if err != nil {
		return
	}

	err = this.writePairs(FCGI_PARAMS, p)
	if err != nil {
		return
	}

	body := writerPool.Get().(*bufWriter)
	body.stream.c = this
	body.stream.recType = FCGI_STDIN
	body.Writer.Reset(body.stream)

	if req != nil {
		_, err = io.Copy(body, req)
		if err != nil {
			writerPool.Put(body)
			return nil, err
		}
	}
	
	err = body.CloseStream()
	writerPool.Put(body)
	if err != nil {
		return nil, err
	}

	r = &streamReader{c: this}
	return
}