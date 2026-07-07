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
	"encoding/json"
	"fmt"
	"io"
	"net"
	"net/http"
	"os"
	"runtime"
	"strings"
	"sync"
	"sync/atomic"
	"time"

	"uc-hub/websocket"
)

type Config struct {
	WSServer                  string `json:"ws_server"`
	WSServerAdvertise         string `json:"ws_server_advertise"`
	WsServerClientLimit       uint32 `json:"ws_server_client_limit"`
	WsServerClientTimeout     int    `json:"ws_server_client_timeout"`
	WsServerClientWorkerCount int    `json:"ws_server_client_worker_count"`
	WsServerClientWorkerQueue int    `json:"ws_server_client_worker_queue"`
	HTTPServer                string `json:"http_server"`
	HTTPServerAdvertise       string `json:"http_server_advertise"`
	HTTPEndpoint              string `json:"http_endpoint"`
	HTTPEndpointTimeout       int    `json:"http_endpoint_timeout"`
	HTTPEndpointWorkerCount   int    `json:"http_endpoint_worker_count"`
	HTTPEndpointWorkerQueue   int    `json:"http_endpoint_worker_queue"`
	MaxMessageSize            int64  `json:"max_message_size"`
}

type Client struct {
	id       string
	conn     *websocket.Conn
	lastPong atomic.Uint32
	cancel   context.CancelFunc
	ctx      context.Context
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
	upgrader         websocket.Upgrader
}

type byteReader struct {
	data []byte
	pos  int
}

const (
	OpenMessage = -1
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
  "ws_server": "0.0.0.0:2080",
  "ws_server_advertise": "192.168.254.1:2080",
  "ws_server_client_limit": 10000,
  "ws_server_client_timeout": 0,
  "ws_server_client_worker_count": 64,
  "ws_server_client_worker_queue": 4096,
  "http_server": "0.0.0.0:4080",
  "http_server_advertise": "192.168.254.1:4080",
  "http_endpoint": "http://127.0.0.1:6080/http-endpoint",
  "http_endpoint_timeout": 90,
  "http_endpoint_worker_count": 64,
  "http_endpoint_worker_queue": 4096,
  "max_message_size": 1048576
}

HTTP Endpoint:

POST /http-endpoint
Headers:
  X-Uc-Hub-Client
  X-Uc-Hub-Type: open | message | close
  X-Uc-Hub-Ws-Server
  X-Uc-Hub-Http-Server
Body:
  Message payload (message events only)

HTTP Server:

GET /
  Returns "UC Hub is running."

POST /
Headers:
  X-Uc-Hub-Client: id | id,id,...
  X-Uc-Hub-Type: open | message | close
Body:
  Message payload

GET /stats
  Returns server statistics.
`)
		os.Exit(1)
	}

	cfg, err := parseConfig(os.Args[1])
	if err != nil {
		fmt.Fprintf(os.Stderr, "Failed to parse config: %v\n", err)
		os.Exit(1)
	}

	clientShardCount := int(cfg.WsServerClientLimit / 2500)
	if clientShardCount < 1 {
		clientShardCount = 1
	}

	srv := &Server{
		cfg:              cfg,
		clientShardCount: clientShardCount,
		clients:          make([]sync.Map, clientShardCount),
		clientQueues:     make([]chan Queue, cfg.WsServerClientWorkerCount),
		endpointQueues:   make([]chan Queue, cfg.HTTPEndpointWorkerCount),
		startTime:        time.Now(),
		upgrader: websocket.Upgrader{
			CheckOrigin: func(r *http.Request) bool {
				return true
			},
		},
		hc: &http.Client{
			Timeout: time.Duration(cfg.HTTPEndpointTimeout) * time.Second,
			Transport: &http.Transport{
				DialContext: (&net.Dialer{
					Timeout:   10 * time.Second,
					KeepAlive: 90 * time.Second,
				}).DialContext,
				MaxConnsPerHost:     cfg.HTTPEndpointWorkerCount,
				MaxIdleConns:        cfg.HTTPEndpointWorkerCount,
				MaxIdleConnsPerHost: cfg.HTTPEndpointWorkerCount,
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
			currentElapsed := uint32(time.Since(srv.startTime) / time.Second)

			for i := 0; i < srv.clientShardCount; i++ {
				srv.clients[i].Range(func(key, value interface{}) bool {
					client := value.(*Client)

					if currentElapsed-client.lastPong.Load() > 90 {
						srv.sendToClient(client.id, websocket.CloseMessage, nil)
						return true
					}

					srv.sendToClient(client.id, websocket.PingMessage, nil)
					return true
				})
			}
		}
	}()

	for i := 0; i < cfg.WsServerClientWorkerCount; i++ {
		srv.clientQueues[i] = make(chan Queue, cfg.WsServerClientWorkerQueue)
		go srv.clientWorker(i)
	}

	for i := 0; i < cfg.HTTPEndpointWorkerCount; i++ {
		srv.endpointQueues[i] = make(chan Queue, cfg.HTTPEndpointWorkerQueue)
		go srv.endpointWorker(i)
	}

	go func() {
		fmt.Printf("[%s] HTTP server listening on %s\n", time.Now().Format(time.RFC3339), cfg.HTTPServer)
		if err := http.ListenAndServe(cfg.HTTPServer, http.HandlerFunc(srv.httpHandler)); err != nil {
			fmt.Fprintf(os.Stderr, "HTTP server error: %v\n", err)
			os.Exit(1)
		}
	}()

	fmt.Printf("[%s] WS server listening on %s\n", time.Now().Format(time.RFC3339), cfg.WSServer)
	if err := http.ListenAndServe(cfg.WSServer, http.HandlerFunc(srv.wsHandler)); err != nil {
		fmt.Fprintf(os.Stderr, "WS server error: %v\n", err)
		os.Exit(1)
	}
}

func (s *Server) httpHandler(w http.ResponseWriter, r *http.Request) {
	if r.URL.Path == "/" {
		if r.Method == http.MethodGet {
			fmt.Fprintln(w, "UC Hub is running.")
			return
		}

		if r.Method != http.MethodPost {
			http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
			return
		}

		cidsRaw := r.Header.Get("X-Uc-Hub-Client")
		if cidsRaw == "" {
			http.Error(w, "Missing client header", http.StatusBadRequest)
			return
		}

		var reader io.ReadCloser = r.Body
		if s.cfg.MaxMessageSize > 0 {
			reader = io.NopCloser(io.LimitReader(r.Body, s.cfg.MaxMessageSize))
		}

		body, err := io.ReadAll(reader)
		r.Body.Close()
		if err != nil {
			http.Error(w, "Bad request", http.StatusBadRequest)
			return
		}

		w.WriteHeader(http.StatusOK)

		if f, ok := w.(http.Flusher); ok {
			f.Flush()
		}

		msgType := websocket.TextMessage
		switch r.Header.Get("X-Uc-Hub-Type") {
		case "close":
			msgType = websocket.CloseMessage
		default:
			if r.Header.Get("Content-Type") == "application/octet-stream" {
				msgType = websocket.BinaryMessage
			}
		}

		for _, trimmedCid := range strings.Split(cidsRaw, ",") {
			trimmedCid = strings.TrimSpace(trimmedCid)
			if _, ok := s.clients[shard(trimmedCid, s.clientShardCount)].Load(trimmedCid); ok {
				s.sendToClient(trimmedCid, msgType, body)
			} else {
				s.sendToEndpoint(trimmedCid, websocket.CloseMessage, nil)
			}
		}
	} else if r.URL.Path == "/stats" {
		fmt.Fprintf(w, "clients: %d\n", s.clientCount.Load())
		fmt.Fprintf(w, "ws_dropped: %d\n", s.wsDropped.Load())
		fmt.Fprintf(w, "endpoint_dropped: %d\n", s.epDropped.Load())
		fmt.Fprintf(w, "endpoint_failed: %d\n", s.epFailed.Load())
		fmt.Fprintf(w, "uptime: %s\n", time.Since(s.startTime).Truncate(time.Second).String())
	} else {
		http.NotFound(w, r)
	}
}

func (s *Server) wsHandler(w http.ResponseWriter, r *http.Request) {
	if s.clientCount.Load() >= s.cfg.WsServerClientLimit {
		http.Error(w, "Too many connections", http.StatusServiceUnavailable)
		return
	}

	conn, err := s.upgrader.Upgrade(w, r, nil)
	if err != nil {
		return
	}

	cid := fmt.Sprintf("%016x%08x", time.Now().UnixNano(), s.clientId.Add(1))
	conn.SetPingHandler(func(appData string) error {
		s.sendToClient(cid, websocket.PongMessage, nil)
		return nil
	})
	conn.SetPongHandler(func(appData string) error {
		if v, ok := s.clients[shard(cid, s.clientShardCount)].Load(cid); ok {
			v.(*Client).lastPong.Store(uint32(time.Since(s.startTime) / time.Second))
		}
		return nil
	})

	if s.cfg.MaxMessageSize > 0 {
		conn.SetReadLimit(s.cfg.MaxMessageSize)
	}

	client := &Client{
		id:   cid,
		conn: conn,
	}
	client.lastPong.Store(uint32(time.Since(s.startTime) / time.Second))

	s.clients[shard(cid, s.clientShardCount)].Store(cid, client)
	s.clientCount.Add(1)

	s.sendToEndpoint(cid, OpenMessage, nil)

	go s.clientRead(cid, conn)
}

func (s *Server) clientRead(cid string, conn *websocket.Conn) {
	timeout := time.Duration(s.cfg.WsServerClientTimeout) * time.Second

	for {
		if s.cfg.WsServerClientTimeout > 0 {
			conn.SetReadDeadline(time.Now().Add(timeout))
		}

		typ, msg, err := conn.ReadMessage()
		if err != nil {
			break
		}

		s.sendToEndpoint(cid, typ, msg)
	}

	s.sendToClient(cid, websocket.CloseMessage, nil)
}

func (s *Server) sendToClient(cid string, typ int, payload []byte) {
	select {
	case s.clientQueues[shard(cid, s.cfg.WsServerClientWorkerCount)] <- Queue{cid: cid, typ: typ, payload: payload}:
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

				s.sendToEndpoint(queue.cid, websocket.CloseMessage, nil)
			}
		}
	}
}

func (s *Server) sendToEndpoint(cid string, typ int, payload []byte) {
	select {
	case s.endpointQueues[shard(cid, s.cfg.HTTPEndpointWorkerCount)] <- Queue{cid: cid, typ: typ, payload: payload}:
	default:
		s.epDropped.Add(1)
	}
}

func (s *Server) endpointWorker(i int) {
	q := s.endpointQueues[i]

	for queue := range q {
		req, err := http.NewRequest("POST", s.cfg.HTTPEndpoint, &byteReader{data: queue.payload})
		if err != nil {
			s.sendToClient(queue.cid, websocket.TextMessage, []byte("Request failed"))
			s.epFailed.Add(1)
			continue
		}

		req.ContentLength = int64(len(queue.payload))

		typ := "message"
		switch queue.typ {
		case OpenMessage:
			typ = "open"
		case websocket.CloseMessage:
			typ = "close"
		}

		h := req.Header
		h["X-Uc-Hub-Client"] = []string{queue.cid}
		h["X-Uc-Hub-Type"] = []string{typ}
		h["X-Uc-Hub-Ws-Server"] = []string{s.cfg.WSServerAdvertise}
		h["X-Uc-Hub-Http-Server"] = []string{s.cfg.HTTPServerAdvertise}

		if queue.typ == websocket.BinaryMessage {
			h["Content-Type"] = []string{"application/octet-stream"}
		} else {
			h["Content-Type"] = []string{"text/plain"}
		}

		resp, err := s.hc.Do(req)
		if err != nil {
			s.sendToClient(queue.cid, websocket.TextMessage, []byte("Request failed"))
			s.epFailed.Add(1)
			continue
		}
		if resp.StatusCode < 200 || resp.StatusCode >= 300 {
			s.sendToClient(queue.cid, websocket.TextMessage, []byte("Request failed"))
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
	b, err := os.ReadFile(path)
	if err != nil {
		return nil, err
	}

	cfg := &Config{
		WsServerClientLimit:       10000,
		WsServerClientTimeout:     0,
		WsServerClientWorkerCount: 64,
		WsServerClientWorkerQueue: 4096,
		HTTPEndpointTimeout:       90,
		HTTPEndpointWorkerCount:   64,
		HTTPEndpointWorkerQueue:   4096,
	}

	if err := json.Unmarshal(b, cfg); err != nil {
		return nil, err
	}

	return cfg, nil
}
