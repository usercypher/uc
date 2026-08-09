/*
Version: 0.0.0

Copyright 2026 Lloyd Miles M. Bersabe

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/

package main

import (
	// uc-hub
	"context"
	"crypto/rand"
	"encoding/json"
	"fmt"
	"log"
	"os"
	"os/signal"
	"runtime"
	"sync"
	"sync/atomic"
	"syscall"

	// websocket
	"bufio"
	"crypto/sha1"
	"encoding/base64"
	"encoding/binary"

	// uc-hub / websocket
	"errors"
	"io"
	"net"
	"net/http"
	"strings"
	"time"
)

type Config struct {
	Server              string `json:"server"`
	ServerAdvertise     string `json:"server_advertise"`
	TLSServer           string `json:"tls_server"`
	TLSServerAdvertise  string `json:"tls_server_advertise"`
	TLSCert             string `json:"tls_cert"`
	TLSKey              string `json:"tls_key"`
	ClientLimit         uint32 `json:"client_limit"`
	ClientTimeout       int    `json:"client_timeout"`
	ClientWorkerCount   int    `json:"client_worker_count"`
	ClientWorkerQueue   int    `json:"client_worker_queue"`
	Endpoint            string `json:"endpoint"`
	EndpointTimeout     int    `json:"endpoint_timeout"`
	EndpointWorkerCount int    `json:"endpoint_worker_count"`
	EndpointWorkerQueue int    `json:"endpoint_worker_queue"`
	ReadHeaderTimeout   int    `json:"read_header_timeout"`
	ReadTimeout         int    `json:"read_timeout"`
	WriteTimeout        int    `json:"write_timeout"`
	IdleTimeout         int    `json:"idle_timeout"`
	MaxHeaderBytes      int    `json:"max_header_bytes"`
	MaxBodyBytes        int64  `json:"max_body_bytes"`
}

type Client struct {
	id       string
	conn     *Websocket_Conn
	lastPong atomic.Uint32
}

type Queue struct {
	cid     string
	typ     int
	payload []byte
}

type Server struct {
	cfg              *Config
	clients          []sync.Map
	clientCount      atomic.Uint32
	clientId         atomic.Uint32
	clientShardCount int
	hc               *http.Client
	clientQueues     []chan Queue
	endpointQueues   []chan Queue
	wsDropped        atomic.Int32
	epDropped        atomic.Int32
	epFailed         atomic.Int32
	startTime        time.Time
	token            string
	shuttingDown     atomic.Bool
}

type byteReader struct {
	data []byte
	pos  int
}

const (
	opOpen = -1
)

func (br *byteReader) Read(p []byte) (int, error) {
	if br.pos >= len(br.data) {
		return 0, io.EOF
	}
	n := copy(p, br.data[br.pos:])
	br.pos += n
	return n, nil
}

func (br *byteReader) Close() error {
	return nil
}

func main() {
	runtime.GOMAXPROCS(1)

	if len(os.Args) < 2 {
		fmt.Fprintf(os.Stderr, "Usage: %s <config-file>\n\n", os.Args[0])
		fmt.Fprintf(os.Stderr, `Example config file (config.json):
{
  "server": "0.0.0.0:2080",
  "server_advertise": "192.168.254.1:2080",
  "tls_server": "0.0.0.0:2443",
  "tls_server_advertise": "192.168.254.1:2443",
  "tls_cert": "${ROOT}/server.crt",
  "tls_key": "${ROOT}/server.key",
  "client_limit": 10000,
  "client_timeout": 0,
  "client_worker_count": 64,
  "client_worker_queue": 4096,
  "endpoint": "http://127.0.0.1:8080/http-endpoint",
  "endpoint_timeout": 90,
  "endpoint_worker_count": 64,
  "endpoint_worker_queue": 4096,
  "read_header_timeout": 5,
  "read_timeout": 30,
  "write_timeout": 30,
  "idle_timeout": 60,
  "max_header_bytes": 1048576,
  "max_body_bytes": 1048576
}

Server:

GET /
  Establishes websocket connection.

POST /
Headers:
  X-Uc-Hub-Client: id | id,id,...
  X-Uc-Hub-Type: open | message | close
  X-Uc-Hub-Token
Body:
  Message payload

GET /stats
  Returns server statistics.

Endpoint:

POST /http-endpoint
Headers:
  X-Uc-Hub-Client
  X-Uc-Hub-Type: open | message | close
  X-Uc-Hub-Server
  X-Uc-Hub-Tls-Server
  X-Uc-Hub-Token
Body:
  Message payload

`)
		os.Exit(1)
	}

	cfg, err := parseConfig(os.Args[1])
	if err != nil {
		log.Fatalf("Failed to parse config: %v\n", err)
	}

	if cfg.Server == "" && cfg.TLSServer == "" {
		log.Fatal("error: no server configured")
	}

	clientShardCount := int(cfg.ClientLimit / 2500)
	if clientShardCount < 1 {
		clientShardCount = 1
	}

	token := make([]byte, 32)
	if _, err := io.ReadFull(rand.Reader, token); err != nil {
		log.Fatalf("Failed to generate secure secret: %v\n", err)
	}

	srv := &Server{
		cfg:              cfg,
		clientShardCount: clientShardCount,
		clients:          make([]sync.Map, clientShardCount),
		clientQueues:     make([]chan Queue, cfg.ClientWorkerCount),
		endpointQueues:   make([]chan Queue, cfg.EndpointWorkerCount),
		startTime:        time.Now(),
		token:            fmt.Sprintf("%x", token),
		hc: &http.Client{
			Timeout: time.Duration(cfg.EndpointTimeout) * time.Second,
			Transport: &http.Transport{
				DialContext: (&net.Dialer{
					Timeout:   10 * time.Second,
					KeepAlive: 90 * time.Second,
				}).DialContext,
				MaxConnsPerHost:     cfg.EndpointWorkerCount,
				MaxIdleConns:        cfg.EndpointWorkerCount,
				MaxIdleConnsPerHost: cfg.EndpointWorkerCount,
				IdleConnTimeout:     90 * time.Second,
				TLSHandshakeTimeout: 10 * time.Second,
				DisableKeepAlives:   false,
			},
		},
	}

	go func() {
		ticker := time.NewTicker(30 * time.Second)
		defer ticker.Stop()

		for range ticker.C {
			if srv.shuttingDown.Load() {
				return
			}

			currentElapsed := uint32(time.Since(srv.startTime) / time.Second)

			for i := 0; i < srv.clientShardCount; i++ {
				srv.clients[i].Range(func(key, value interface{}) bool {
					client := value.(*Client)

					if currentElapsed-client.lastPong.Load() > 90 {
						srv.sendToClient(client.id, Websocket_OpClose, nil)
						return true
					}

					srv.sendToClient(client.id, Websocket_OpPing, nil)
					return true
				})
			}
		}
	}()

	for i := 0; i < cfg.ClientWorkerCount; i++ {
		srv.clientQueues[i] = make(chan Queue, cfg.ClientWorkerQueue)
		go srv.clientWorker(i)
	}

	for i := 0; i < cfg.EndpointWorkerCount; i++ {
		srv.endpointQueues[i] = make(chan Queue, cfg.EndpointWorkerQueue)
		go srv.endpointWorker(i)
	}

	if cfg.Server == "" && cfg.TLSServer == "" {
		log.Fatal("error: no server configured")
	}

	server := func(addr string) *http.Server {
		return &http.Server{
			Addr:              addr,
			Handler:           http.HandlerFunc(srv.httpHandler),
			ReadHeaderTimeout: time.Duration(cfg.ReadHeaderTimeout) * time.Second,
			ReadTimeout:       time.Duration(cfg.ReadTimeout) * time.Second,
			WriteTimeout:      time.Duration(cfg.WriteTimeout) * time.Second,
			IdleTimeout:       time.Duration(cfg.IdleTimeout) * time.Second,
			MaxHeaderBytes:    cfg.MaxHeaderBytes,
		}
	}

	httpServer := server(cfg.Server)
	httpsServer := server(cfg.TLSServer)

	if cfg.Server != "" {
		go func() {
			log.Printf("HTTP listening on %s", cfg.Server)
			if err := httpServer.ListenAndServe(); err != nil &&
				err != http.ErrServerClosed {
				log.Printf("HTTP server: %v", err)
			}
		}()
	}

	if cfg.TLSServer != "" {
		go func() {
			log.Printf("HTTPS listening on %s", cfg.TLSServer)
			if err := httpsServer.ListenAndServeTLS(cfg.TLSCert, cfg.TLSKey); err != nil &&
				err != http.ErrServerClosed {
				log.Printf("HTTPS server: %v", err)
			}
		}()
	}

	sigCh := make(chan os.Signal, 1)
	signal.Notify(sigCh, os.Interrupt, syscall.SIGTERM)

	<-sigCh

	srv.shuttingDown.Store(true)

	for i := 0; i < srv.clientShardCount; i++ {
		srv.clients[i].Range(func(_, value any) bool {
			client := value.(*Client)
			if client.conn != nil {
				client.conn.Close()
			}
			return true
		})
	}

	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	if cfg.Server != "" {
		httpServer.Shutdown(ctx)
	}

	if cfg.TLSServer != "" {
		httpsServer.Shutdown(ctx)
	}

	for _, q := range srv.clientQueues {
		close(q)
	}

	for _, q := range srv.endpointQueues {
		close(q)
	}
}

func (s *Server) httpHandler(w http.ResponseWriter, r *http.Request) {
	if r.URL.Path == "/" && r.Method == http.MethodGet {
		if s.clientCount.Load() >= s.cfg.ClientLimit {
			http.Error(w, "Service unavailable", http.StatusServiceUnavailable)
			return
		}

		conn, err := Websocket_Upgrade(w, r)
		if err != nil {
			return
		}

		cid := fmt.Sprintf("%016x%08x", time.Now().UnixNano(), s.clientId.Add(1))
		conn.OnPing(func(payload []byte) {
			s.sendToClient(cid, Websocket_OpPong, payload)
		})
		conn.OnPong(func(payload []byte) {
			if v, ok := s.clients[shard(cid, s.clientShardCount)].Load(cid); ok {
				v.(*Client).lastPong.Store(uint32(time.Since(s.startTime) / time.Second))
			}
		})
		conn.OnClose(func(payload []byte) {
			s.sendToClient(cid, Websocket_OpClose, nil)
		})

		client := &Client{
			id:   cid,
			conn: conn,
		}
		client.lastPong.Store(uint32(time.Since(s.startTime) / time.Second))

		s.clients[shard(cid, s.clientShardCount)].Store(cid, client)
		s.clientCount.Add(1)

		s.sendToEndpoint(cid, opOpen, nil)

		go s.wsloop(conn, cid)
	} else if r.URL.Path == "/" && r.Method == http.MethodPost {
		if r.Header.Get("X-Uc-Hub-Token") != s.token {
			http.Error(w, "Forbidden", http.StatusForbidden)
			return
		}

		cidsRaw := r.Header.Get("X-Uc-Hub-Client")
		if cidsRaw == "" {
			http.Error(w, "Missing client header", http.StatusBadRequest)
			return
		}

		if s.cfg.MaxBodyBytes > 0 {
			r.Body = http.MaxBytesReader(w, r.Body, int64(s.cfg.MaxBodyBytes))
		}

		body, err := io.ReadAll(r.Body)
		if err != nil {
			var mbe *http.MaxBytesError
			if errors.As(err, &mbe) {
				http.Error(w, "Request entity too large", http.StatusRequestEntityTooLarge)
			} else {
				http.Error(w, "Bad request", http.StatusBadRequest)
			}
			return
		}

		w.WriteHeader(http.StatusOK)

		if f, ok := w.(http.Flusher); ok {
			f.Flush()
		}

		msgType := Websocket_Optext
		switch r.Header.Get("X-Uc-Hub-Type") {
		case "close":
			msgType = Websocket_OpClose
		default:
			if r.Header.Get("Content-Type") == "application/octet-stream" {
				msgType = Websocket_Opbinary
			}
		}

		for _, trimmedCid := range strings.Split(cidsRaw, ",") {
			trimmedCid = strings.TrimSpace(trimmedCid)
			if _, ok := s.clients[shard(trimmedCid, s.clientShardCount)].Load(trimmedCid); ok {
				s.sendToClient(trimmedCid, msgType, body)
			} else {
				s.sendToEndpoint(trimmedCid, Websocket_OpClose, nil)
			}
		}
	} else if r.URL.Path == "/stats" && r.Method == http.MethodGet {
		fmt.Fprintf(w, "clients: %d\n", s.clientCount.Load())
		fmt.Fprintf(w, "ws_dropped: %d\n", s.wsDropped.Load())
		fmt.Fprintf(w, "endpoint_dropped: %d\n", s.epDropped.Load())
		fmt.Fprintf(w, "endpoint_failed: %d\n", s.epFailed.Load())
		fmt.Fprintf(w, "uptime: %s\n", time.Since(s.startTime).Truncate(time.Second).String())
	} else {
		http.NotFound(w, r)
	}
}

func (s *Server) wsloop(conn *Websocket_Conn, cid string) {
	timeout := time.Duration(s.cfg.ClientTimeout) * time.Second

	for {
		if s.cfg.ClientTimeout > 0 {
			conn.SetReadDeadline(time.Now().Add(timeout))
		}

		typ, msg, err := conn.ReadMessage()
		if err != nil {
			break
		}

		s.sendToEndpoint(cid, typ, msg)
	}

	s.sendToClient(cid, Websocket_OpClose, nil)
}

func (s *Server) sendToClient(cid string, typ int, payload []byte) {
	if s.shuttingDown.Load() {
		return
	}

	select {
	case s.clientQueues[shard(cid, s.cfg.ClientWorkerCount)] <- Queue{cid: cid, typ: typ, payload: payload}:
	default:
		s.wsDropped.Add(1)
	}
}

func (s *Server) clientWorker(i int) {
	q := s.clientQueues[i]
	timeout := 50 * time.Millisecond

	for queue := range q {
		if c, exists := s.clients[shard(queue.cid, s.clientShardCount)].Load(queue.cid); exists {
			c.(*Client).conn.SetWriteDeadline(time.Now().Add(timeout))

			if c.(*Client).conn.WriteMessage(queue.typ, queue.payload) != nil {
				c, exists := s.clients[shard(queue.cid, s.clientShardCount)].LoadAndDelete(queue.cid)
				if !exists {
					continue
				}

				s.clientCount.Add(^uint32(0))
				client := c.(*Client)

				if client.conn != nil {
					client.conn.Close()
				}

				s.sendToEndpoint(queue.cid, Websocket_OpClose, nil)
			}
		}
	}
}

func (s *Server) sendToEndpoint(cid string, typ int, payload []byte) {
	if s.shuttingDown.Load() {
		return
	}

	select {
	case s.endpointQueues[shard(cid, s.cfg.EndpointWorkerCount)] <- Queue{cid: cid, typ: typ, payload: payload}:
	default:
		s.epDropped.Add(1)
	}
}

func (s *Server) endpointWorker(i int) {
	q := s.endpointQueues[i]

	for queue := range q {
		req, err := http.NewRequest("POST", s.cfg.Endpoint, &byteReader{data: queue.payload})
		if err != nil {
			s.sendToClient(queue.cid, Websocket_Optext, []byte("Request failed"))
			s.epFailed.Add(1)
			continue
		}

		req.ContentLength = int64(len(queue.payload))

		typ := "message"
		switch queue.typ {
		case opOpen:
			typ = "open"
		case Websocket_OpClose:
			typ = "close"
		}

		h := req.Header
		h["X-Uc-Hub-Client"] = []string{queue.cid}
		h["X-Uc-Hub-Type"] = []string{typ}
		h["X-Uc-Hub-Server"] = []string{s.cfg.ServerAdvertise}
		h["X-Uc-Hub-Tls-Server"] = []string{s.cfg.TLSServerAdvertise}
		h["X-Uc-Hub-Token"] = []string{s.token}

		if queue.typ == Websocket_Opbinary {
			h["Content-Type"] = []string{"application/octet-stream"}
		} else {
			h["Content-Type"] = []string{"text/plain"}
		}

		resp, err := s.hc.Do(req)
		if err != nil {
			s.sendToClient(queue.cid, Websocket_Optext, []byte("Request failed"))
			s.epFailed.Add(1)
			continue
		}
		if resp.StatusCode < 200 || resp.StatusCode >= 300 {
			s.sendToClient(queue.cid, Websocket_Optext, []byte("Request failed"))
			s.epFailed.Add(1)
		}
		io.Copy(io.Discard, resp.Body)
		resp.Body.Close()
	}
}

func shard(id string, n int) int {
	const offsetBasis uint32 = 2166136261
	const prime uint32 = 16777619
	h := offsetBasis
	for i := 0; i < len(id); i++ {
		h ^= uint32(id[i])
		h *= prime
	}
	return int(h % uint32(n))
}

func parseConfig(path string) (*Config, error) {
	root, err := os.Getwd()
	if err != nil {
		return nil, err
	}

	b, err := os.ReadFile(path)
	if err != nil {
		return nil, err
	}

	cfg := &Config{
		ClientLimit:         10000,
		ClientTimeout:       0,
		ClientWorkerCount:   64,
		ClientWorkerQueue:   4096,
		EndpointTimeout:     90,
		EndpointWorkerCount: 64,
		EndpointWorkerQueue: 4096,
		ReadHeaderTimeout:   5,
		ReadTimeout:         30,
		WriteTimeout:        30,
		IdleTimeout:         60,
		MaxHeaderBytes:      1 << 20,
		MaxBodyBytes:        1 << 20,
	}

	if err := json.Unmarshal(b, cfg); err != nil {
		return nil, err
	}

	cfg.TLSCert = strings.ReplaceAll(cfg.TLSCert, "${ROOT}", root)
	cfg.TLSKey = strings.ReplaceAll(cfg.TLSKey, "${ROOT}", root)

	return cfg, nil
}

/* **********
 * websocket
 */

const (
	Websocket_opContinuation = 0x0

	Websocket_Optext   = 0x1
	Websocket_Opbinary = 0x2
	Websocket_OpClose  = 0x8
	Websocket_OpPing   = 0x9
	Websocket_OpPong   = 0xA
)

const (
	Websocket_maxFrameSize = 32768
)

var (
	Websocket_guid      = []byte("258EAFA5-E914-47DA-95CA-C5AB0DC85B11")
	Websocket_respPart1 = []byte("HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: ")
	Websocket_respPart2 = []byte("\r\n\r\n")

	Websocket_MaxMessageSize = 4 * 1024 * 1024
)

type Websocket_Conn struct {
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

type Websocket_HandlerFunc func(*Websocket_Conn)

func Websocket_NewConn(conn net.Conn, br *bufio.Reader) *Websocket_Conn {
	return &Websocket_Conn{
		conn:   conn,
		br:     br,
		msgBuf: make([]byte, 0, Websocket_MaxMessageSize),
	}
}

func (w *Websocket_Conn) SetReadDeadline(t time.Time) error  { return w.conn.SetReadDeadline(t) }
func (w *Websocket_Conn) SetWriteDeadline(t time.Time) error { return w.conn.SetWriteDeadline(t) }
func (w *Websocket_Conn) SetDeadline(t time.Time) error      { return w.conn.SetDeadline(t) }

func (w *Websocket_Conn) OnPing(h func([]byte))  { w.onPingHandler = h }
func (w *Websocket_Conn) OnPong(h func([]byte))  { w.onPongHandler = h }
func (w *Websocket_Conn) OnClose(h func([]byte)) { w.onCloseHandler = h }

func Websocket_Upgrade(w http.ResponseWriter, r *http.Request) (*Websocket_Conn, error) {
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

	ws := Websocket_NewConn(netConn, brw.Reader)

	keyLen := copy(ws.keyBuf[:], challengeKey)
	keyLen += copy(ws.keyBuf[keyLen:], Websocket_guid)
	hash := sha1.Sum(ws.keyBuf[:keyLen])

	var accept [28]byte
	base64.StdEncoding.Encode(accept[:], hash[:])

	var bufs net.Buffers
	bufs = append(bufs,
		Websocket_respPart1,
		accept[:],
		Websocket_respPart2,
	)

	if _, err := bufs.WriteTo(ws.conn); err != nil {
		netConn.Close()
		return nil, err
	}

	return ws, nil
}

func (w *Websocket_Conn) ReadMessage() (int, []byte, error) {
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
		case Websocket_opContinuation, Websocket_Optext, Websocket_Opbinary, Websocket_OpClose, Websocket_OpPing, Websocket_OpPong:
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
		isControl := frameOpcode == Websocket_OpClose || frameOpcode == Websocket_OpPing || frameOpcode == Websocket_OpPong

		if isControl {
			if payloadLen > 125 {
				return 0, nil, errors.New("protocol error: control frame payload exceeded 125 bytes")
			}
			if !fin {
				return 0, nil, errors.New("protocol error: control frames cannot be fragmented")
			}
		} else {
			if uint64(len(w.msgBuf))+payloadLen64 > uint64(Websocket_MaxMessageSize) {
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
			case Websocket_OpClose:
				if w.onCloseHandler != nil {
					w.onCloseHandler(controlPayload)
				}
				return 0, nil, io.EOF
			case Websocket_OpPing:
				if w.onPingHandler != nil {
					w.onPingHandler(controlPayload)
				}
			case Websocket_OpPong:
				if w.onPongHandler != nil {
					w.onPongHandler(controlPayload)
				}
			}
			continue
		}

		if w.inFragmentation {
			if frameOpcode != Websocket_opContinuation {
				return 0, nil, errors.New("protocol error: expected continuation frame")
			}
		} else {
			if frameOpcode == Websocket_opContinuation {
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

func (w *Websocket_Conn) WriteFrame(fin bool, opcode int, payload []byte) error {
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

func (w *Websocket_Conn) WriteMessage(opcode int, data []byte) error {
	if len(data) <= Websocket_maxFrameSize {
		return w.WriteFrame(true, opcode, data)
	}

	if err := w.WriteFrame(false, opcode, data[:Websocket_maxFrameSize]); err != nil {
		return err
	}
	data = data[Websocket_maxFrameSize:]

	for len(data) > Websocket_maxFrameSize {
		if err := w.WriteFrame(false, Websocket_opContinuation, data[:Websocket_maxFrameSize]); err != nil {
			return err
		}
		data = data[Websocket_maxFrameSize:]
	}

	return w.WriteFrame(true, Websocket_opContinuation, data)
}

func (w *Websocket_Conn) Close() error {
	return w.conn.Close()
}
