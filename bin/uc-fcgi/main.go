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
	"encoding/json"
	"fmt"
	"io"
	"net"
	"net/http"
	"net/textproto"
	"os"
	"os/exec"
	"path/filepath"
	"runtime"
	"strconv"
	"strings"
	"sync/atomic"
	"time"

	"uc-fcgi/fcgiclient"
)

type Config struct {
	Server                string            `json:"server"`
	DocumentRoot          string            `json:"document_root"`
	FcgiEnabled           bool              `json:"fcgi_enabled"`
	Fcgibin               string            `json:"fcgi_bin"`
	FcgiRouterFile        string            `json:"fcgi_router_file"`
	FcgiWorkerCount       int               `json:"fcgi_worker_count"`
	FcgiWorkerConcurrency int               `json:"fcgi_worker_concurrency"`
	FcgiEnv               map[string]string `json:"fcgi_env"`
}

type FcgiWorker struct {
	port      atomic.Int32
	cmd       *exec.Cmd
	semaphore chan struct{}
}

type Server struct {
	cfg             *Config
	fcgiWorkerIndex atomic.Int32
	fcgiWorkers     []*FcgiWorker
	fcgiPortCounter atomic.Int32
}

func main() {
	runtime.GOMAXPROCS(1)

	if len(os.Args) < 2 {
		fmt.Fprintf(os.Stderr, "Usage: %s <config-file>\n\n", os.Args[0])
		fmt.Fprintf(os.Stderr, `Example config file (config.json):
{
  "server": "0.0.0.0:8080",
  "document_root": "/var/www/html",
  "fcgi_enabled": true,
  "fcgi_bin": "php-cgi -b 0.0.0.0:{PORT}",
  "fcgi_router_file": "/var/www/html/index.php",
  "fcgi_worker_count": 4,
  "fcgi_worker_concurrency": 1,
  "fcgi_env": {
    "PHP_FCGI_MAX_REQUESTS": "0"
  }
}
`)
		os.Exit(1)
	}

	cfg, err := parseConfig(os.Args[1])
	if err != nil {
		fmt.Fprintf(os.Stderr, "Failed to parse config: %v\n", err)
		os.Exit(1)
	}

	cfg.DocumentRoot, _ = filepath.Abs(cfg.DocumentRoot)
	cfg.FcgiRouterFile, _ = filepath.Abs(cfg.FcgiRouterFile)
	cfg.DocumentRoot = filepath.Clean(cfg.DocumentRoot)
	cfg.FcgiRouterFile = filepath.Clean(cfg.FcgiRouterFile)

	srv := &Server{
		cfg:         cfg,
		fcgiWorkers: make([]*FcgiWorker, cfg.FcgiWorkerCount),
	}
	srv.fcgiPortCounter.Store(49152)

	if cfg.FcgiEnabled {
		for i := 0; i < cfg.FcgiWorkerCount; i++ {
			var fcgiWorker FcgiWorker
			port := srv.fcgiPortCounter.Add(1)
			fcgiWorker.port.Store(port)
			fcgiWorker.semaphore = make(chan struct{}, cfg.FcgiWorkerConcurrency)
			srv.fcgiWorkers[i] = &fcgiWorker
			go srv.superviseFcgiWorker(i)
		}
	}

	fmt.Printf("[%s] HTTP server listening on %s\n", time.Now().Format(time.RFC3339), cfg.Server)
	if err := http.ListenAndServe(cfg.Server, http.HandlerFunc(srv.httpHandler)); err != nil {
		fmt.Fprintf(os.Stderr, "HTTP Server error: %v\n", err)
		os.Exit(1)
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
		if _, err := os.Stat(s.cfg.FcgiRouterFile); err != nil {
			http.NotFound(w, r)
			return
		}

		idx := int(s.fcgiWorkerIndex.Add(1)) % s.cfg.FcgiWorkerCount
		fcgiWorker := s.fcgiWorkers[idx]

		fcgiWorker.semaphore <- struct{}{}
		defer func() { <-fcgiWorker.semaphore }()

		client, err := fcgiclient.DialTimeout("tcp", fmt.Sprintf("127.0.0.1:%d", fcgiWorker.port.Load()), 2*time.Second)
		if err != nil {
			http.Error(w, "Bad Gateway", http.StatusBadGateway)
			return
		}
		defer client.Close()

		env := map[string]string{
			"GATEWAY_INTERFACE": "CGI/1.1",

			"SERVER_SOFTWARE": "uc-fcgi",
			"SERVER_PROTOCOL": r.Proto,

			"REQUEST_SCHEME": "http",
			"REQUEST_METHOD": r.Method,
			"REQUEST_URI":    r.URL.RequestURI(),
			"QUERY_STRING":   r.URL.RawQuery,

			"DOCUMENT_ROOT":   s.cfg.DocumentRoot,
			"SCRIPT_FILENAME": s.cfg.FcgiRouterFile,
			"SCRIPT_NAME":     "/" + filepath.Base(s.cfg.FcgiRouterFile),
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

		for key, values := range r.Header {
			if len(values) > 0 {
				cgiBuf := "HTTP_" + strings.ToUpper(strings.ReplaceAll(key, "-", "_"))
				env[cgiBuf] = values[0]
			}
		}

		ioread, err := client.Do(env, r.Body)
		if err != nil {
			http.Error(w, "Bad Gateway", http.StatusBadGateway)
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

	env := os.Environ()
	for key, value := range s.cfg.FcgiEnv {
		env = append(env, fmt.Sprintf("%s=%s", key, value))
	}

	for {
		cmdStr := strings.ReplaceAll(s.cfg.Fcgibin, "{PORT}", fmt.Sprintf("%d", fcgiWorker.port.Load()))
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

		if err != nil {
			fmt.Printf("[%s] Fcgi worker %d uptime=%s port=%d err=%v\n", time.Now().Format(time.RFC3339), idx, time.Since(started).Round(time.Second), fcgiWorker.port.Load(), err)
		}

		time.Sleep(100 * time.Millisecond)
	}
}

func parseConfig(path string) (*Config, error) {
	b, err := os.ReadFile(path)
	if err != nil {
		return nil, err
	}

	cfg := &Config{
		FcgiEnabled:           false,
		FcgiWorkerConcurrency: 1,
		FcgiEnv:               make(map[string]string),
	}

	if err := json.Unmarshal(b, cfg); err != nil {
		return nil, err
	}

	return cfg, nil
}
