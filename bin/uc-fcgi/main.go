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
	"bufio"
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"log"
	"net"
	"net/http"
	"net/textproto"
	"os"
	"os/exec"
	"os/signal"
	"path/filepath"
	"runtime"
	"strconv"
	"strings"
	"sync"
	"sync/atomic"
	"syscall"
	"time"

	"uc-fcgi/fcgiclient"
)

type Config struct {
	Server                string            `json:"server"`
	TLSServer             string            `json:"tls_server"`
	TLSCert               string            `json:"tls_cert"`
	TLSKey                string            `json:"tls_key"`
	DocumentRoot          string            `json:"document_root"`
	FcgiEnabled           bool              `json:"fcgi_enabled"`
	FcgiNetwork           string            `json:"fcgi_network"`
	FcgiAddress           string            `json:"fcgi_address"`
	Fcgibin               string            `json:"fcgi_bin"`
	FcgiScript            string            `json:"fcgi_script"`
	FcgiWorkerCount       int               `json:"fcgi_worker_count"`
	FcgiWorkerConcurrency int               `json:"fcgi_worker_concurrency"`
	FcgiEnv               map[string]string `json:"fcgi_env"`
	ReadHeaderTimeout     int               `json:"read_header_timeout"`
	ReadTimeout           int               `json:"read_timeout"`
	WriteTimeout          int               `json:"write_timeout"`
	IdleTimeout           int               `json:"idle_timeout"`
	MaxHeaderBytes        int               `json:"max_header_bytes"`
	MaxBodyBytes          int64             `json:"max_body_bytes"`
}

type FcgiWorker struct {
	port      atomic.Int32
	cmd       *exec.Cmd
	semaphore chan struct{}
}

type Server struct {
	cfg             *Config
	rootDir         string
	fcgiWorkerIndex atomic.Int32
	fcgiWorkerWG    sync.WaitGroup
	fcgiWorkers     []*FcgiWorker
	fcgiPortCounter atomic.Int32
	shuttingDown    atomic.Bool
}

func main() {
	runtime.GOMAXPROCS(1)

	if len(os.Args) < 2 {
		fmt.Fprintf(os.Stderr, "Usage: %s <config-file>\n\n", os.Args[0])
		fmt.Fprintf(os.Stderr, `Example config file (config.json):
{
  "server": "0.0.0.0:8080",
  "tls_server": "0.0.0.0:8443",
  "tls_cert": "${ROOT}/server.crt",
  "tls_key": "${ROOT}/server.key",
  "document_root": "${ROOT}/html",
  "fcgi_enabled": true,
  "fcgi_network": "tcp",
  "fcgi_address": "0.0.0.0:${PORT}",
  "fcgi_bin": "php-cgi -b 0.0.0.0:${PORT}",
  "fcgi_script": "${ROOT}/html/index.php",
  "fcgi_worker_count": 4,
  "fcgi_worker_concurrency": 1,
  "fcgi_env": {
    "PHP_FCGI_MAX_REQUESTS": "0",
    "LISTEN": "0.0.0.0:${PORT}",
    "ROOT": "${ROOT}"
  },
  "read_header_timeout": 5,
  "read_timeout": 30,
  "write_timeout": 30,
  "idle_timeout": 60,
  "max_header_bytes": 1048576,
  "max_body_bytes": 16777216
}
`)
		os.Exit(1)
	}

	currentDir, err := os.Getwd()
	if err != nil {
		log.Fatalf("Failed to get current working directory: %v", err)
	}

	cfg, err := parseConfig(os.Args[1])
	if err != nil {
		log.Fatalf("Failed to parse config: %v", err)
	}

	if cfg.Server == "" && cfg.TLSServer == "" {
		log.Fatal("error: no server configured")
	}

	srv := &Server{
		cfg:         cfg,
		rootDir:     currentDir,
		fcgiWorkers: make([]*FcgiWorker, cfg.FcgiWorkerCount),
	}
	srv.fcgiPortCounter.Store(49152)

	if cfg.FcgiEnabled {
		srv.fcgiWorkerWG.Add(cfg.FcgiWorkerCount)
		for i := 0; i < cfg.FcgiWorkerCount; i++ {
			var worker FcgiWorker

			port := srv.fcgiPortCounter.Add(1)
			worker.port.Store(port)
			worker.semaphore = make(chan struct{}, cfg.FcgiWorkerConcurrency)

			srv.fcgiWorkers[i] = &worker

			go func(i int) {
				defer srv.fcgiWorkerWG.Done()
				srv.superviseFcgiWorker(i)
			}(i)
		}
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

	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	if cfg.Server != "" {
		httpServer.Shutdown(ctx)
	}

	if cfg.TLSServer != "" {
		httpsServer.Shutdown(ctx)
	}

	for _, w := range srv.fcgiWorkers {
		if w != nil && w.cmd != nil && w.cmd.Process != nil {
			_ = w.cmd.Process.Signal(syscall.SIGTERM)
		}
	}

	done := make(chan struct{})
	go func() {
		srv.fcgiWorkerWG.Wait()
		close(done)
	}()

	select {
	case <-done:
	case <-time.After(5 * time.Second):
		for _, w := range srv.fcgiWorkers {
			if w != nil && w.cmd != nil && w.cmd.Process != nil {
				_ = w.cmd.Process.Kill()
			}
		}
		<-done
	}
}

func (s *Server) httpHandler(w http.ResponseWriter, r *http.Request) {
	reqPath := r.URL.Path
	if reqPath == "/" {
		reqPath = ""
	}

	filePath := filepath.Join(s.cfg.DocumentRoot, filepath.Clean(reqPath))
	if filePathInfo, err := os.Stat(filePath); err == nil && filePathInfo.Mode().IsRegular() {
		http.ServeFile(w, r, filePath)
		return
	}

	if s.cfg.FcgiEnabled {
		if _, err := os.Stat(s.cfg.FcgiScript); err != nil {
			http.NotFound(w, r)
			return
		}

		idx := int(s.fcgiWorkerIndex.Add(1)) % s.cfg.FcgiWorkerCount
		fcgiWorker := s.fcgiWorkers[idx]

		fcgiWorker.semaphore <- struct{}{}
		defer func() { <-fcgiWorker.semaphore }()

		fcgiAddr := strings.ReplaceAll(s.cfg.FcgiAddress, "${PORT}", fmt.Sprintf("%d", fcgiWorker.port.Load()))
		fcgiAddr = strings.ReplaceAll(fcgiAddr, "${ROOT}", s.rootDir)

		client, err := fcgiclient.DialTimeout(s.cfg.FcgiNetwork, fcgiAddr, 2*time.Second)
		if err != nil {
			http.Error(w, "Bad Gateway", http.StatusBadGateway)
			return
		}
		defer client.Close()

		env := map[string]string{
			"GATEWAY_INTERFACE": "CGI/1.1",

			"SERVER_SOFTWARE": "uc-fcgi/0.0.0",
			"SERVER_PROTOCOL": r.Proto,

			"REQUEST_SCHEME": "http",
			"REQUEST_METHOD": r.Method,
			"REQUEST_URI":    r.URL.RequestURI(),
			"QUERY_STRING":   r.URL.RawQuery,

			"DOCUMENT_ROOT":   s.cfg.DocumentRoot,
			"SCRIPT_FILENAME": s.cfg.FcgiScript,
			"SCRIPT_NAME":     "/" + filepath.Base(s.cfg.FcgiScript),
			"PATH_INFO":       reqPath,

			"SERVER_NAME": "localhost",
			"SERVER_PORT": "80",

			"REMOTE_ADDR": "",
			"REMOTE_HOST": "",

			"HTTPS": "off",

			"PATH_TRANSLATED": filePath,
		}

		if host, port, err := net.SplitHostPort(r.Host); err == nil {
			env["SERVER_NAME"] = host
			env["SERVER_PORT"] = port
		} else {
			env["SERVER_NAME"] = r.Host
		}

		if host, _, err := net.SplitHostPort(r.RemoteAddr); err == nil {
			env["REMOTE_ADDR"] = host
			env["REMOTE_HOST"] = host
		}

		if ct := r.Header.Get("Content-Type"); ct != "" {
			env["CONTENT_TYPE"] = ct
		}

		if cl := r.Header.Get("Content-Length"); cl != "" {
			env["CONTENT_LENGTH"] = cl
		}

		if r.TLS != nil {
			env["HTTPS"] = "on"
			env["REQUEST_SCHEME"] = "https"
		}

		for key, values := range r.Header {
			if len(values) > 0 {
				cgiBuf := "HTTP_" + strings.ToUpper(strings.ReplaceAll(key, "-", "_"))
				env[cgiBuf] = values[0]
			}
		}

		if s.cfg.MaxBodyBytes > 0 {
			r.Body = http.MaxBytesReader(w, r.Body, int64(s.cfg.MaxBodyBytes))
		}

		ioread, err := client.Do(env, r.Body)
		if err != nil {
			var mbe *http.MaxBytesError
			if errors.As(err, &mbe) {
				http.Error(w, "Request entity too large", http.StatusRequestEntityTooLarge)
			} else {
				http.Error(w, "Bad Gateway", http.StatusBadGateway)
			}
			return
		}

		buf := bufio.NewReader(ioread)
		reader := textproto.NewReader(buf)
		headers, err := reader.ReadMIMEHeader()

		if err != nil {
			http.Error(w, "Bad Gateway", http.StatusBadGateway)
			return
		}

		statusCode := http.StatusOK
		if statusStr, ok := headers["Status"]; ok {
			parts := strings.Fields(statusStr[0])
			if len(parts) > 0 {
				if code, err := strconv.Atoi(parts[0]); err == nil && code > 0 && code < 600 {
					statusCode = code
				}
			}
			delete(headers, "Status")
		}

		for key, values := range headers {
			for _, v := range values {
				w.Header().Add(key, v)
			}
		}

		w.WriteHeader(statusCode)

		io.Copy(w, buf)

		return
	} else {
		indexPath := filepath.Join(filePath, "index.html")
		if indexInfo, err := os.Stat(indexPath); err == nil && indexInfo.Mode().IsRegular() {
			http.ServeFile(w, r, indexPath)
			return
		}
	}

	http.NotFound(w, r)
}

func (s *Server) superviseFcgiWorker(idx int) {
	var err error
	fcgiWorker := s.fcgiWorkers[idx]

	for {
		if s.shuttingDown.Load() {
			return
		}

		env := os.Environ()
		for key, value := range s.cfg.FcgiEnv {
			val := strings.ReplaceAll(value, "${PORT}", fmt.Sprintf("%d", fcgiWorker.port.Load()))
			val = strings.ReplaceAll(val, "${ROOT}", s.rootDir)
			env = append(env, fmt.Sprintf("%s=%s", key, val))
		}

		cmdStr := strings.ReplaceAll(s.cfg.Fcgibin, "${PORT}", fmt.Sprintf("%d", fcgiWorker.port.Load()))
		cmdStr = strings.ReplaceAll(cmdStr, "${ROOT}", s.rootDir)
		parts := strings.Fields(cmdStr)

		fcgiWorker.cmd = exec.Command(parts[0], parts[1:]...)
		fcgiWorker.cmd.Stderr = os.Stderr
		fcgiWorker.cmd.Env = env

		started := time.Now()

		if err = fcgiWorker.cmd.Start(); err == nil {
			err = fcgiWorker.cmd.Wait()

			if err != nil {
				port := s.fcgiPortCounter.Add(1)
				if port > 65535 {
					if s.fcgiPortCounter.CompareAndSwap(port, 49152) {
						port = 49152
					} else {
						port = s.fcgiPortCounter.Load()
					}
				}
				fcgiWorker.port.Store(port)
			}
		}

		if err != nil && !s.shuttingDown.Load() {
			var exitErr *exec.ExitError
			delay := true
			if errors.As(err, &exitErr) && exitErr.ExitCode() == 0 {
				delay = false
			}
			if delay {
				log.Printf("Fcgi worker %d uptime=%s port=%d err=%v", idx, time.Since(started).Round(time.Second), fcgiWorker.port.Load(), err)
				time.Sleep(100 * time.Millisecond)
			}
		}
	}
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
		FcgiEnabled:           false,
		FcgiWorkerConcurrency: 1,
		FcgiEnv:               make(map[string]string),
		ReadHeaderTimeout:     5,
		ReadTimeout:           30,
		WriteTimeout:          30,
		IdleTimeout:           60,
		MaxHeaderBytes:        1 << 20,
		MaxBodyBytes:          16 << 20,
	}

	if err := json.Unmarshal(b, cfg); err != nil {
		return nil, err
	}

	cfg.DocumentRoot = strings.ReplaceAll(cfg.DocumentRoot, "${ROOT}", root)
	cfg.FcgiScript = strings.ReplaceAll(cfg.FcgiScript, "${ROOT}", root)

	cfg.DocumentRoot, _ = filepath.Abs(cfg.DocumentRoot)
	cfg.FcgiScript, _ = filepath.Abs(cfg.FcgiScript)
	cfg.DocumentRoot = filepath.Clean(cfg.DocumentRoot)
	cfg.FcgiScript = filepath.Clean(cfg.FcgiScript)

	cfg.TLSCert = strings.ReplaceAll(cfg.TLSCert, "${ROOT}", root)
	cfg.TLSKey = strings.ReplaceAll(cfg.TLSKey, "${ROOT}", root)

	return cfg, nil
}
