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
	"context"
	"crypto/rand"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"log"
	"net"
	"net/http"
	"os"
	"os/signal"
	"runtime"
	"strings"
	"sync"
	"sync/atomic"
	"syscall"
	"time"

	"uc-hub/websocket"
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
	conn     *websocket.Conn
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
						srv.sendToClient(client.id, websocket.OpClose, nil)
						return true
					}

					srv.sendToClient(client.id, websocket.OpPing, nil)
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

		conn, err := websocket.Upgrade(w, r)
		if err != nil {
			return
		}

		cid := fmt.Sprintf("%016x%08x", time.Now().UnixNano(), s.clientId.Add(1))
		conn.OnPing(func(payload []byte) {
			s.sendToClient(cid, websocket.OpPong, payload)
		})
		conn.OnPong(func(payload []byte) {
			if v, ok := s.clients[shard(cid, s.clientShardCount)].Load(cid); ok {
				v.(*Client).lastPong.Store(uint32(time.Since(s.startTime) / time.Second))
			}
		})
		conn.OnClose(func(payload []byte) {
			s.sendToClient(cid, websocket.OpClose, nil)
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

		msgType := websocket.Optext
		switch r.Header.Get("X-Uc-Hub-Type") {
		case "close":
			msgType = websocket.OpClose
		default:
			if r.Header.Get("Content-Type") == "application/octet-stream" {
				msgType = websocket.Opbinary
			}
		}

		for _, trimmedCid := range strings.Split(cidsRaw, ",") {
			trimmedCid = strings.TrimSpace(trimmedCid)
			if _, ok := s.clients[shard(trimmedCid, s.clientShardCount)].Load(trimmedCid); ok {
				s.sendToClient(trimmedCid, msgType, body)
			} else {
				s.sendToEndpoint(trimmedCid, websocket.OpClose, nil)
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

func (s *Server) wsloop(conn *websocket.Conn, cid string) {
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

	s.sendToClient(cid, websocket.OpClose, nil)
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

				s.sendToEndpoint(queue.cid, websocket.OpClose, nil)
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
			s.sendToClient(queue.cid, websocket.Optext, []byte("Request failed"))
			s.epFailed.Add(1)
			continue
		}

		req.ContentLength = int64(len(queue.payload))

		typ := "message"
		switch queue.typ {
		case opOpen:
			typ = "open"
		case websocket.OpClose:
			typ = "close"
		}

		h := req.Header
		h["X-Uc-Hub-Client"] = []string{queue.cid}
		h["X-Uc-Hub-Type"] = []string{typ}
		h["X-Uc-Hub-Server"] = []string{s.cfg.ServerAdvertise}
		h["X-Uc-Hub-Tls-Server"] = []string{s.cfg.TLSServerAdvertise}
		h["X-Uc-Hub-Token"] = []string{s.token}

		if queue.typ == websocket.Opbinary {
			h["Content-Type"] = []string{"application/octet-stream"}
		} else {
			h["Content-Type"] = []string{"text/plain"}
		}

		resp, err := s.hc.Do(req)
		if err != nil {
			s.sendToClient(queue.cid, websocket.Optext, []byte("Request failed"))
			s.epFailed.Add(1)
			continue
		}
		if resp.StatusCode < 200 || resp.StatusCode >= 300 {
			s.sendToClient(queue.cid, websocket.Optext, []byte("Request failed"))
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
