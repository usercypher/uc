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
	// uc-web
	"bytes"
	"compress/gzip"
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"log"
	"mime"
	"net/http"
	"os"
	"os/exec"
	"os/signal"
	"path"
	"path/filepath"
	"runtime"
	"slices"
	"strconv"
	"strings"
	"sync"
	"sync/atomic"
	"syscall"

	// uc-web / fcgi
	"io"
	"net"
	"time"
)

type Config struct {
	Server            string              `json:"server"`
	TLSServer         string              `json:"tls_server"`
	TLSCert           string              `json:"tls_cert"`
	TLSKey            string              `json:"tls_key"`
	DocumentRoot      string              `json:"document_root"`
	Fcgi              map[string]Fcgi     `json:"fcgi"`
	Mime              map[string]string   `json:"mime"`
	ReadHeaderTimeout int                 `json:"read_header_timeout"`
	ReadTimeout       int                 `json:"read_timeout"`
	WriteTimeout      int                 `json:"write_timeout"`
	IdleTimeout       int                 `json:"idle_timeout"`
	MaxHeaderBytes    int                 `json:"max_header_bytes"`
	MaxBodyBytes      int64               `json:"max_body_bytes"`
	Encoding          int                 `json:"encoding"`
	EncodingDir       string              `json:"encoding_dir"`
	EncodingInterval  int64               `json:"encoding_interval"`
	EncodingRetention int64               `json:"encoding_retention"`
	EncodingMinBytes  int64               `json:"encoding_min_bytes"`
	EncodingMaxBytes  int64               `json:"encoding_max_bytes"`
	EncodingMime      []string            `json:"encoding_mime"`
	Rules             []map[string]string `json:"rules"`
	compiledRules     []Rule
}

type Fcgi struct {
	Network           string            `json:"network"`
	Address           string            `json:"address"`
	Bin               string            `json:"bin"`
	Script            string            `json:"script"`
	WorkerCount       int               `json:"worker_count"`
	WorkerConcurrency int               `json:"worker_concurrency"`
	Env               map[string]string `json:"env"`
}

type CompiledRuleString struct {
	Value   string
	HasVars bool
}

type Rule struct {
	MatchAll []CompiledRuleString
	MatchAny []CompiledRuleString

	Headers map[string]string

	Serve         CompiledRuleString
	Redirect      CompiledRuleString
	RedirectHTTPS CompiledRuleString
	RedirectCode  int

	Encoding []string

	Fcgi string

	Break bool
}

type FcgiWorker struct {
	port      atomic.Uint32
	pid       atomic.Uint32
	semaphore chan struct{}
}

type Server struct {
	cfg              *Config
	rootDir          string
	fcgiWorkerIndex  map[string]atomic.Uint32
	fcgiWorkerWG     sync.WaitGroup
	fcgiWorkers      map[string][]*FcgiWorker
	fcgiPortCounter  atomic.Uint32
	shuttingDown     atomic.Bool
	encodingLocks    *encodingLocks
	bufferPool       sync.Pool
	gzipPool         sync.Pool
	fcgiEnvPool      sync.Pool
	encodingReplacer *strings.Replacer
}

type encodingLock struct {
	mu   sync.Mutex
	refs int
}

type encodingLocks struct {
	mu    sync.Mutex
	items map[string]*encodingLock
}

func (s *encodingLocks) acquire(key string) func() {
	s.mu.Lock()

	l := s.items[key]
	if l == nil {
		l = &encodingLock{}
		s.items[key] = l
	}
	l.refs++

	s.mu.Unlock()

	l.mu.Lock()

	return func() {
		l.mu.Unlock()

		s.mu.Lock()
		l.refs--

		if l.refs == 0 {
			delete(s.items, key)
		}
		s.mu.Unlock()
	}
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
  "fcgi": {
    "php": {
      "network": "tcp",
      "address": "0.0.0.0:${PORT}",
      "bin": "php-cgi -b 0.0.0.0:${PORT}",
      "script": "${ROOT}/html/index.php",
      "worker_count": 4,
      "worker_concurrency": 1,
      "env": {
        "PHP_FCGI_MAX_REQUESTS": "0",
        "LISTEN": "0.0.0.0:${PORT}",
        "ROOT": "${ROOT}"
      },
    }
  },
  "mime": {
    ".md": "text/markdown; charset=utf-8"
  },
  "read_header_timeout": 5,
  "read_timeout": 30,
  "write_timeout": 30,
  "idle_timeout": 60,
  "max_header_bytes": 1048576,
  "max_body_bytes": 16777216,
  "encoding": 6,
  "encoding_dir": "${ROOT}/encoding/",
  "encoding_interval": 3600,
  "encoding_retention": 86400,
  "encoding_min_bytes": 256,
  "encoding_max_bytes": 134217728,
  "encoding_mime": [
    "text/plain"
  ],
  "rules": [
    {
      "X-Uc-Web-Match-Any": "(var) ${REQ_PATH} ${REQ_DIR} ${REQ_BASENAME} ${REQ_NAME} ${REQ_EXT}, (pattern) ^prefix $postfix *contains =exact exactdefault, pattern3, pattern4",
      "X-Uc-Web-Match-All": "(var) ..., (pattern) ..., pattern3, pattern4",
      "X-Uc-Web-Serve": "(var) ...",
      "X-Uc-Web-Redirect-Https": "(var) ...",
      "X-Uc-Web-Redirect": "(var) ...",
      "X-Uc-Web-Redirect-Code": "",
      "X-Uc-Web-Encoding": "gzip, br, deflate",
      "X-Uc-Web-Fcgi": "php",
      "X-Uc-Web-Break": "stop in this rule, value here wont matter, it's presence base"
    }
  ]
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

	compileRules(cfg)

	if cfg.Server == "" && cfg.TLSServer == "" {
		log.Fatal("error: no server configured")
	}

	if cfg.Mime != nil {
		for ext, typ := range cfg.Mime {
			if ext != "" && ext[0] != '.' {
				ext = "." + ext
			}
			_ = mime.AddExtensionType(ext, typ)
		}
	}

	srv := &Server{
		cfg:     cfg,
		rootDir: currentDir,
		encodingLocks: &encodingLocks{
			items: make(map[string]*encodingLock),
		},
		bufferPool: sync.Pool{
			New: func() any {
				b := make([]byte, 64*1024)
				return &b
			},
		},
		gzipPool: sync.Pool{
			New: func() any {
				gz, err := gzip.NewWriterLevel(io.Discard, cfg.Encoding)
				if err != nil {
					gz, _ = gzip.NewWriterLevel(io.Discard, gzip.DefaultCompression)
				}
				return gz
			},
		},
		fcgiEnvPool: sync.Pool{
			New: func() any {
				return make(map[string]string, 64)
			},
		},
		encodingReplacer: strings.NewReplacer(string(filepath.Separator), "_", ":", ""),
	}
	srv.fcgiPortCounter.Store(49152)

	if cfg.Fcgi != nil {
		srv.fcgiWorkerIndex = make(map[string]atomic.Uint32, len(cfg.Fcgi))
		srv.fcgiWorkers = make(map[string][]*FcgiWorker, len(cfg.Fcgi))
		for fKey, fVal := range cfg.Fcgi {
			srv.fcgiWorkers[fKey] = make([]*FcgiWorker, fVal.WorkerCount)
			srv.fcgiWorkerWG.Add(fVal.WorkerCount)
			for i := 0; i < fVal.WorkerCount; i++ {
				var worker FcgiWorker
				port := 49152 + (srv.fcgiPortCounter.Add(1) % 16384)
				worker.port.Store(port)
				worker.semaphore = make(chan struct{}, fVal.WorkerConcurrency)

				srv.fcgiWorkers[fKey][i] = &worker

				go func(key string, i int) {
					defer srv.fcgiWorkerWG.Done()
					srv.superviseFcgiWorker(key, i)
				}(fKey, i)
			}
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

	if cfg.Encoding > 0 {
		srv.encodingCleanup()

		go func() {
			ticker := time.NewTicker(time.Duration(cfg.EncodingInterval) * time.Second)
			defer ticker.Stop()

			for range ticker.C {
				srv.encodingCleanup()
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

	if cfg.Fcgi != nil {
		for fKey, _ := range cfg.Fcgi {
			for _, w := range srv.fcgiWorkers[fKey] {
				pid := w.pid.Load()
				if pid > 0 {
					if proc, err := os.FindProcess(int(pid)); err == nil {
						_ = proc.Signal(syscall.SIGTERM)
					}
				}
			}
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
		if cfg.Fcgi != nil {
			for fKey, _ := range cfg.Fcgi {
				for _, w := range srv.fcgiWorkers[fKey] {
					pid := w.pid.Load()
					if pid > 0 {
						if proc, err := os.FindProcess(int(pid)); err == nil {
							_ = proc.Kill()
						}
					}
				}
			}
		}
		<-done
	}
}

func (s *Server) encodingCleanup() {
	cutoff := time.Now().Add(-time.Duration(s.cfg.EncodingRetention) * time.Second)

	entries, err := os.ReadDir(s.cfg.EncodingDir)
	if err != nil {
		if !os.IsNotExist(err) {
			log.Printf("encoding cleanup: %v", err)
		}
		return
	}

	var removed int
	var freed int64

	for _, e := range entries {
		if e.IsDir() {
			continue
		}

		info, err := e.Info()
		if err != nil {
			continue
		}

		name := e.Name()
		if (!strings.HasSuffix(name, ".tmp") && !strings.HasSuffix(name, ".gzip")) || !info.ModTime().Before(cutoff) {
			continue
		}

		if os.Remove(filepath.Join(s.cfg.EncodingDir, name)) == nil {
			removed++
			freed += info.Size()
		}
	}

	if removed > 0 {
		log.Printf("encoding cleanup: removed %d, freed %.2f MB", removed, float64(freed)/1024/1024)
	}
}

func (s *Server) httpHandler(w http.ResponseWriter, r *http.Request) {
	reqPath := r.URL.Path

	var encoding []string
	var fcgi string
	if len(s.cfg.compiledRules) > 0 {
		var reqVars RequestVars
		var code int

		processedReqPath := strings.ToLower(path.Clean(reqPath))

		for _, rule := range s.cfg.compiledRules {
			matched := true

			for _, p := range rule.MatchAll {
				matchPattern := p.Value
				if p.HasVars {
					reqVars.Init(reqPath)
					matchPattern = reqVars.Replace(p.Value)
				}
				if !matchString(matchPattern, processedReqPath) {
					matched = false
					break
				}
			}
			if !matched {
				continue
			}

			if len(rule.MatchAny) > 0 {
				matched = false
				for _, p := range rule.MatchAny {
					matchPattern := p.Value
					if p.HasVars {
						reqVars.Init(reqPath)
						matchPattern = reqVars.Replace(p.Value)
					}
					if matchString(matchPattern, processedReqPath) {
						matched = true
						break
					}
				}
				if !matched {
					continue
				}
			}

			for k, v := range rule.Headers {
				w.Header().Set(k, v)
			}

			code = rule.RedirectCode
			if code == 0 {
				code = http.StatusMovedPermanently
			}

			if rule.RedirectHTTPS.Value != "" && r.TLS == nil {
				if code == http.StatusMovedPermanently {
					code = http.StatusPermanentRedirect
				}
				target := rule.RedirectHTTPS.Value
				if rule.RedirectHTTPS.HasVars {
					reqVars.Init(reqPath)
					target = reqVars.Replace(target)
				}
				http.Redirect(w, r, target, code)
				return
			}

			if rule.Redirect.Value != "" {
				target := rule.Redirect.Value
				if rule.Redirect.HasVars {
					reqVars.Init(reqPath)
					target = reqVars.Replace(target)
				}
				http.Redirect(w, r, target, code)
				return
			}

			if rule.Serve.Value != "" {
				target := rule.Serve.Value
				if rule.Serve.HasVars {
					reqVars.Init(reqPath)
					target = reqVars.Replace(target)
				}
				reqPath = target
				processedReqPath = strings.ToLower(path.Clean(reqPath))
				reqVars.IsInit = false
			}

			if rule.Fcgi != "" {
				fcgi = rule.Fcgi
			}

			encoding = rule.Encoding

			if rule.Break {
				break
			}
		}
	}

	_, fcgiok := s.cfg.Fcgi[fcgi]

	cleanedReq := filepath.Clean(reqPath)
	staticFile := filepath.Join(s.cfg.DocumentRoot, cleanedReq)

	var f *os.File
	var staticFileInfo os.FileInfo
	var err error

	f, err = os.Open(staticFile)
	if err == nil {
		staticFileInfo, err = f.Stat()
		if err == nil && !staticFileInfo.Mode().IsRegular() {
			f.Close()
			err = os.ErrNotExist
		}
	}

	if err != nil && !fcgiok {
		cleanedReq = filepath.Join(cleanedReq, "index.html")
		staticFile = filepath.Join(s.cfg.DocumentRoot, cleanedReq)
		f, err = os.Open(staticFile)
		if err == nil {
			staticFileInfo, err = f.Stat()
			if err != nil || !staticFileInfo.Mode().IsRegular() {
				if f != nil {
					f.Close()
				}
				s.httpErrorFile(w, r, "Not Found", http.StatusNotFound)
				return
			}
		} else {
			s.httpErrorFile(w, r, "Not Found", http.StatusNotFound)
			return
		}
	}

	if err == nil {
		defer f.Close()

		var contentType string
		var encodingFile string
		var enc string
		if encoding != nil {
			var tried uint64

			contentType = w.Header().Get("Content-Type")
			if contentType == "" {
				contentType = mime.TypeByExtension(filepath.Ext(staticFile))
				if contentType == "" {
					contentType = "application/octet-stream"
				} else {
					if mediaType, _, err := mime.ParseMediaType(contentType); err == nil {
						contentType = mediaType
					}
				}
			}

			w.Header().Set("Vary", "Accept-Encoding")

			for {
				bestIdx := -1
				bestQ := -1.0

				for _, part := range strings.Split(r.Header.Get("Accept-Encoding"), ",") {
					part = strings.TrimSpace(part)
					if part == "" {
						continue
					}

					reqEnc := part
					q := 1.0

					if semi := strings.IndexByte(part, ';'); semi != -1 {
						reqEnc = strings.TrimSpace(part[:semi])

						for _, param := range strings.Split(part[semi+1:], ";") {
							param = strings.TrimSpace(param)
							if strings.HasPrefix(param, "q=") {
								if v, err := strconv.ParseFloat(param[2:], 64); err == nil {
									q = v
								}
								break
							}
						}
					}

					if q <= 0 {
						continue
					}

					for i, a := range encoding {
						if tried&(1<<i) != 0 {
							continue
						}

						if reqEnc == a || reqEnc == "*" {
							if q > bestQ || (q == bestQ && (bestIdx == -1 || i < bestIdx)) {
								bestQ = q
								bestIdx = i
							}
						}
					}
				}

				if bestIdx < 0 {
					break
				}

				tried |= 1 << bestIdx
				enc = encoding[bestIdx]

				encodingFile = filepath.Join(s.cfg.EncodingDir, s.encodingReplacer.Replace(cleanedReq)+"_"+strconv.FormatInt(staticFileInfo.Size(), 10)+"_"+strconv.FormatInt(staticFileInfo.ModTime().UnixNano(), 10)+"."+enc)

				if ef, err := os.Open(encodingFile); err == nil {
					defer ef.Close()

					if efInfo, err := ef.Stat(); err == nil && efInfo.Mode().IsRegular() {
						w.Header().Set("Content-Encoding", enc)
						w.Header().Set("Content-Type", contentType)
						http.ServeContent(w, r, efInfo.Name(), efInfo.ModTime(), ef)
						return
					}
				}

				if enc == "gzip" {
					break
				}
			}
		}

		if enc != "gzip" || !slices.Contains(s.cfg.EncodingMime, contentType) || staticFileInfo.Size() < s.cfg.EncodingMinBytes || staticFileInfo.Size() > s.cfg.EncodingMaxBytes {
			http.ServeContent(w, r, staticFileInfo.Name(), staticFileInfo.ModTime(), f)
			return
		}

		release := s.encodingLocks.acquire(encodingFile)

		gzFile, err := s.openFileGzCopy(encodingFile, f)

		release()

		if err == nil {
			defer gzFile.Close()
			if gzFileInfo, err := gzFile.Stat(); err == nil {
				w.Header().Set("Content-Encoding", enc)
				w.Header().Set("Content-Type", contentType)
				http.ServeContent(w, r, gzFileInfo.Name(), gzFileInfo.ModTime(), gzFile)
				return
			}
		}

		http.ServeContent(w, r, staticFileInfo.Name(), staticFileInfo.ModTime(), f)
		return
	}

	if !fcgiok {
		s.httpErrorFile(w, r, "Not Found", http.StatusNotFound)
		return
	}

	if r.Body != nil {
		defer r.Body.Close()
	}

	var fcgiWorker *FcgiWorker

	for _, worker := range s.fcgiWorkers[fcgi] {
		select {
		case worker.semaphore <- struct{}{}:
			fcgiWorker = worker
		default:
			continue
		}

		if fcgiWorker != nil {
			break
		}
	}

	if fcgiWorker == nil {
		workerIdx := s.fcgiWorkerIndex[fcgi]
		fcgiWorker = s.fcgiWorkers[fcgi][int(workerIdx.Add(1))%s.cfg.Fcgi[fcgi].WorkerCount]
		fcgiWorker.semaphore <- struct{}{}
	}

	defer func() { <-fcgiWorker.semaphore }()

	fcgiAddr := strings.ReplaceAll(s.cfg.Fcgi[fcgi].Address, "${PORT}", fmt.Sprintf("%d", fcgiWorker.port.Load()))
	fcgiAddr = strings.ReplaceAll(fcgiAddr, "${ROOT}", s.rootDir)

	client, err := Fcgi_DialTimeout(s.cfg.Fcgi[fcgi].Network, fcgiAddr, 2*time.Second)
	if err != nil {
		s.httpErrorFile(w, r, "Bad Gateway", http.StatusBadGateway)
		return
	}
	defer client.Close()

	env := s.fcgiEnvPool.Get().(map[string]string)
	defer func() {
		for k := range env {
			delete(env, k)
		}
		s.fcgiEnvPool.Put(env)
	}()

	env["GATEWAY_INTERFACE"] = "CGI/1.1"
	env["SERVER_SOFTWARE"] = "uc-web/0.0.0"
	env["SERVER_PROTOCOL"] = r.Proto
	env["REQUEST_SCHEME"] = "http"
	env["REQUEST_METHOD"] = r.Method
	env["QUERY_STRING"] = r.URL.RawQuery
	env["DOCUMENT_ROOT"] = filepath.ToSlash(s.cfg.DocumentRoot)
	env["SCRIPT_FILENAME"] = filepath.ToSlash(s.cfg.Fcgi[fcgi].Script)
	env["SCRIPT_NAME"] = "/" + filepath.Base(s.cfg.Fcgi[fcgi].Script)
	env["PATH_INFO"] = filepath.ToSlash(reqPath)
	env["SERVER_NAME"] = "localhost"
	env["SERVER_PORT"] = "80"
	env["REMOTE_ADDR"] = ""
	env["REMOTE_HOST"] = ""
	env["HTTPS"] = "off"
	env["PATH_TRANSLATED"] = filepath.ToSlash(staticFile)

	if r.URL.RawQuery != "" {
		env["REQUEST_URI"] = reqPath + "?" + r.URL.RawQuery
	} else {
		env["REQUEST_URI"] = reqPath
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
		r.Body = http.MaxBytesReader(w, r.Body, s.cfg.MaxBodyBytes)
	}

	ioread, err := client.Do(env, r.Body)
	if err != nil {
		var mbe *http.MaxBytesError
		if errors.As(err, &mbe) {
			s.httpErrorFile(w, r, "Request entity too large", http.StatusRequestEntityTooLarge)
		} else {
			s.httpErrorFile(w, r, "Bad Gateway", http.StatusBadGateway)
		}
		return
	}

	statusCode := http.StatusOK

	for {
		line, err := ioread.ReadLine()
		if err != nil {
			s.httpErrorFile(w, r, "Bad Gateway", http.StatusBadGateway)
			return
		}

		line = strings.TrimSuffix(line, "\n")
		line = strings.TrimSuffix(line, "\r")

		if line == "" {
			break
		}

		i := strings.IndexByte(line, ':')
		if i <= 0 {
			s.httpErrorFile(w, r, "Bad Gateway", http.StatusBadGateway)
			return
		}

		key := line[:i]
		value := strings.TrimSpace(line[i+1:])

		if strings.EqualFold(key, "Status") {
			parts := strings.Fields(value)
			if len(parts) == 0 {
				s.httpErrorFile(w, r, "Bad Gateway", http.StatusBadGateway)
				return
			}

			code, err := strconv.Atoi(parts[0])
			if err != nil || code < 100 || code >= 600 {
				s.httpErrorFile(w, r, "Bad Gateway", http.StatusBadGateway)
				return
			}

			statusCode = code
			continue
		}

		w.Header().Add(key, value)
	}

	w.WriteHeader(statusCode)

	bufPtr := s.bufferPool.Get().(*[]byte)
	defer s.bufferPool.Put(bufPtr)

	_, _ = io.CopyBuffer(w, ioread, *bufPtr)
}

func (s *Server) openFileGzCopy(encodingFile string, f *os.File) (*os.File, error) {
	if fDouble, err := os.Open(encodingFile); err == nil {
		return fDouble, nil
	}

	if err := os.MkdirAll(s.cfg.EncodingDir, 0755); err != nil {
		return nil, err
	}

	tempFile := encodingFile + ".tmp"

	dstFile, err := os.OpenFile(tempFile, os.O_CREATE|os.O_EXCL|os.O_WRONLY, 0644)
	if err != nil {
		return nil, err
	}

	gz := s.gzipPool.Get().(*gzip.Writer)
	gz.Reset(dstFile)

	cBufPtr := s.bufferPool.Get().(*[]byte)

	cleanup := func() {
		s.bufferPool.Put(cBufPtr)
		_ = dstFile.Close()
		_ = os.Remove(tempFile)
	}

	if _, err = f.Seek(0, io.SeekStart); err != nil {
		_ = gz.Close()
		cleanup()
		return nil, err
	}

	if _, err = io.CopyBuffer(gz, f, *cBufPtr); err != nil {
		_ = gz.Close()
		cleanup()
		return nil, err
	}

	if err = gz.Close(); err != nil {
		cleanup()
		return nil, err
	}

	s.gzipPool.Put(gz)

	if err = dstFile.Sync(); err != nil {
		_ = dstFile.Close()
		_ = os.Remove(tempFile)
		return nil, err
	}

	if err = dstFile.Close(); err != nil {
		_ = os.Remove(tempFile)
		return nil, err
	}

	if err = os.Rename(tempFile, encodingFile); err != nil {
		_ = os.Remove(tempFile)
		return nil, err
	}

	return os.Open(encodingFile)
}

func (s *Server) httpErrorFile(w http.ResponseWriter, r *http.Request, fallback string, code int) {
	file := filepath.Join(s.cfg.DocumentRoot, strconv.Itoa(code)+".html")

	data, err := os.ReadFile(file)
	if err != nil {
		http.Error(w, fallback, code)
		return
	}

	w.Header().Set("Content-Type", "text/html; charset=utf-8")
	w.WriteHeader(code)
	_, _ = w.Write(data)
}

func compileRules(cfg *Config) {
	cfg.compiledRules = make([]Rule, 0, len(cfg.Rules))

	makeRuleStr := func(val string) CompiledRuleString {
		return CompiledRuleString{
			Value:   val,
			HasVars: strings.Contains(val, "${"),
		}
	}

	for _, m := range cfg.Rules {
		var r Rule
		r.Headers = make(map[string]string)

		for key, value := range m {
			lowerKey := strings.ToLower(key)

			switch {
			case lowerKey == "x-uc-web-match-all":
				for _, pattern := range strings.Split(value, ",") {
					trimmedPattern := strings.TrimSpace(pattern)
					r.MatchAll = append(r.MatchAll, CompiledRuleString{
						Value:   strings.ToLower(trimmedPattern),
						HasVars: strings.Contains(trimmedPattern, "${"),
					})
				}

			case lowerKey == "x-uc-web-match-any":
				for _, pattern := range strings.Split(value, ",") {
					trimmedPattern := strings.TrimSpace(pattern)
					r.MatchAny = append(r.MatchAny, CompiledRuleString{
						Value:   strings.ToLower(trimmedPattern),
						HasVars: strings.Contains(trimmedPattern, "${"),
					})
				}

			case lowerKey == "x-uc-web-serve":
				r.Serve = makeRuleStr(value)

			case lowerKey == "x-uc-web-redirect":
				r.Redirect = makeRuleStr(value)

			case lowerKey == "x-uc-web-redirect-https":
				r.RedirectHTTPS = makeRuleStr(value)

			case lowerKey == "x-uc-web-redirect-code":
				if code, err := strconv.Atoi(value); err == nil {
					r.RedirectCode = code
				}

			case lowerKey == "x-uc-web-encoding":
				for _, part := range strings.Split(value, ",") {
					r.Encoding = append(r.Encoding, strings.TrimSpace(part))
				}

			case lowerKey == "x-uc-web-fcgi":
				r.Fcgi = value

			case lowerKey == "x-uc-web-break":
				r.Break = true
			}
			r.Headers[http.CanonicalHeaderKey(key)] = value
		}
		cfg.compiledRules = append(cfg.compiledRules, r)
	}
}

type RequestVars struct {
	Path   string
	Dir    string
	Base   string
	Name   string
	Ext    string
	IsInit bool
}

func (rv *RequestVars) Init(reqPath string) {
	if !rv.IsInit {
		rv.Path = reqPath
		rv.Base = path.Base(reqPath)
		rv.Ext = path.Ext(rv.Base)
		rv.Name = strings.TrimSuffix(rv.Base, rv.Ext)
		rv.Dir = strings.TrimSuffix(path.Dir(reqPath), "/")
		if rv.Dir == "." {
			rv.Dir = ""
		}
		rv.IsInit = true
	}
}

func (rv *RequestVars) Replace(s string) string {
	s = strings.ReplaceAll(s, "${REQ_PATH}", rv.Path)
	s = strings.ReplaceAll(s, "${REQ_DIR}", rv.Dir)
	s = strings.ReplaceAll(s, "${REQ_BASENAME}", rv.Base)
	s = strings.ReplaceAll(s, "${REQ_NAME}", rv.Name)
	s = strings.ReplaceAll(s, "${REQ_EXT}", rv.Ext)
	return s
}

func matchString(pattern string, s string) bool {
	if pattern == "" {
		return s == pattern
	}
	switch pattern[0] {
	case '^':
		return strings.HasPrefix(s, pattern[1:])
	case '$':
		return strings.HasSuffix(s, pattern[1:])
	case '*':
		return strings.Contains(s, pattern[1:])
	case '=':
		return s == pattern[1:]
	default:
		return s == pattern
	}
}

func (s *Server) superviseFcgiWorker(fcgi string, idx int) {
	var err error
	fcgiWorker := s.fcgiWorkers[fcgi][idx]

	for {
		env := os.Environ()
		for key, value := range s.cfg.Fcgi[fcgi].Env {
			val := strings.ReplaceAll(value, "${PORT}", fmt.Sprintf("%d", fcgiWorker.port.Load()))
			val = strings.ReplaceAll(val, "${ROOT}", s.rootDir)
			env = append(env, fmt.Sprintf("%s=%s", key, val))
		}

		cmdStr := strings.ReplaceAll(s.cfg.Fcgi[fcgi].Bin, "${PORT}", fmt.Sprintf("%d", fcgiWorker.port.Load()))
		cmdStr = strings.ReplaceAll(cmdStr, "${ROOT}", s.rootDir)
		parts := strings.Fields(cmdStr)

		cmd := exec.Command(parts[0], parts[1:]...)
		cmd.Stderr = os.Stderr
		cmd.Env = env

		started := time.Now()

		if s.shuttingDown.Load() {
			return
		}

		if err = cmd.Start(); err == nil {
			fcgiWorker.pid.Store(uint32(cmd.Process.Pid))
			err = cmd.Wait()
			fcgiWorker.pid.Store(0)

			if err != nil {
				fcgiWorker.port.Store(49152 + (s.fcgiPortCounter.Add(1) % 16384))
			}
		}

		if err != nil && !s.shuttingDown.Load() {
			var exitErr *exec.ExitError
			delay := true
			if errors.As(err, &exitErr) && exitErr.ExitCode() == 0 {
				delay = false
			}
			if delay {
				log.Printf("Fcgi %s worker %d uptime=%s port=%d err=%v", fcgi, idx, time.Since(started).Round(time.Second), fcgiWorker.port.Load(), err)
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
		ReadHeaderTimeout: 5,
		ReadTimeout:       30,
		WriteTimeout:      30,
		IdleTimeout:       60,
		MaxHeaderBytes:    1 << 20,
		MaxBodyBytes:      16 << 20,
		EncodingDir:       "${ROOT}/compress/",
		EncodingInterval:  3600,
		EncodingRetention: 86400,
		EncodingMinBytes:  256,
		EncodingMaxBytes:  128 << 20,
	}

	if err := json.Unmarshal(b, cfg); err != nil {
		return nil, err
	}

	cfg.DocumentRoot = strings.ReplaceAll(cfg.DocumentRoot, "${ROOT}", root)

	cfg.DocumentRoot, _ = filepath.Abs(cfg.DocumentRoot)
	cfg.DocumentRoot = filepath.Clean(cfg.DocumentRoot)

	for fKey, fVal := range cfg.Fcgi {
		if fVal.WorkerCount < 0 {
			fVal.WorkerCount = 5
		}

		if fVal.WorkerConcurrency < 0 {
			fVal.WorkerConcurrency = 1
		}

		fVal.Script = strings.ReplaceAll(fVal.Script, "${ROOT}", root)
		fVal.Script, _ = filepath.Abs(fVal.Script)
		fVal.Script = filepath.Clean(fVal.Script)

		cfg.Fcgi[fKey] = fVal
	}

	cfg.TLSCert = strings.ReplaceAll(cfg.TLSCert, "${ROOT}", root)
	cfg.TLSKey = strings.ReplaceAll(cfg.TLSKey, "${ROOT}", root)

	cfg.EncodingDir = strings.ReplaceAll(cfg.EncodingDir, "${ROOT}", root)

	return cfg, nil
}

/* **********
 * fcgi
 */

const (
	Fcgi_FCGI_PARAMS      uint8 = 4
	Fcgi_FCGI_STDIN       uint8 = 5
	Fcgi_FCGI_STDOUT      uint8 = 6
	Fcgi_FCGI_END_REQUEST uint8 = 3

	Fcgi_headerSize = 8
	Fcgi_maxContent = 65535
	Fcgi_maxBuffer  = Fcgi_headerSize + Fcgi_maxContent + 255
)

type Fcgi_Client struct {
	conn      net.Conn
	readBuf   []byte
	hdr       [Fcgi_headerSize]byte
	buf       [Fcgi_maxBuffer]byte
	KeepAlive uint8
}

func Fcgi_Dial(network, address string) (*Fcgi_Client, error) {
	conn, err := net.Dial(network, address)
	if err != nil {
		return nil, err
	}
	return &Fcgi_Client{conn: conn}, nil
}

func Fcgi_DialTimeout(network, address string, timeout time.Duration) (*Fcgi_Client, error) {
	conn, err := net.DialTimeout(network, address, timeout)
	if err != nil {
		return nil, err
	}
	return &Fcgi_Client{conn: conn}, nil
}

func (c *Fcgi_Client) Close() error {
	return c.conn.Close()
}

func (c *Fcgi_Client) Do(p map[string]string, req io.Reader) (*Fcgi_Client, error) {
	beginRecord := [16]byte{1, 1, 0, 1, 0, 8, 0, 0, 0, 1, c.KeepAlive, 0, 0, 0, 0, 0}
	if _, err := c.conn.Write(beginRecord[:]); err != nil {
		return nil, err
	}

	pos := Fcgi_headerSize
	for k, v := range p {
		kl, vl := len(k), len(v)
		if pos+8+kl+vl > Fcgi_headerSize+Fcgi_maxContent {
			contentLength := pos - Fcgi_headerSize
			paddingLength := int(-contentLength & 7)
			c.buf[0], c.buf[1], c.buf[2], c.buf[3] = 1, Fcgi_FCGI_PARAMS, 0, 1
			c.buf[4] = byte(contentLength >> 8)
			c.buf[5] = byte(contentLength)
			c.buf[6] = byte(paddingLength)
			c.buf[7] = 0

			if _, err := c.conn.Write(c.buf[:pos+paddingLength]); err != nil {
				return nil, err
			}
			pos = Fcgi_headerSize
		}

		if kl > 127 {
			c.buf[pos] = byte(kl>>24) | 0x80
			c.buf[pos+1] = byte(kl >> 16)
			c.buf[pos+2] = byte(kl >> 8)
			c.buf[pos+3] = byte(kl)
			pos += 4
		} else {
			c.buf[pos] = byte(kl)
			pos++
		}

		if vl > 127 {
			c.buf[pos] = byte(vl>>24) | 0x80
			c.buf[pos+1] = byte(vl >> 16)
			c.buf[pos+2] = byte(vl >> 8)
			c.buf[pos+3] = byte(vl)
			pos += 4
		} else {
			c.buf[pos] = byte(vl)
			pos++
		}
		pos += copy(c.buf[pos:], k)
		pos += copy(c.buf[pos:], v)
	}

	if pos > Fcgi_headerSize {
		contentLength := pos - Fcgi_headerSize
		paddingLength := int(-contentLength & 7)
		c.buf[0], c.buf[1], c.buf[2], c.buf[3] = 1, Fcgi_FCGI_PARAMS, 0, 1
		c.buf[4] = byte(contentLength >> 8)
		c.buf[5] = byte(contentLength)
		c.buf[6] = byte(paddingLength)
		c.buf[7] = 0
		if _, err := c.conn.Write(c.buf[:pos+paddingLength]); err != nil {
			return nil, err
		}
	}
	c.buf[0], c.buf[1], c.buf[2], c.buf[3], c.buf[4], c.buf[5], c.buf[6], c.buf[7] = 1, Fcgi_FCGI_PARAMS, 0, 1, 0, 0, 0, 0
	if _, err := c.conn.Write(c.buf[:Fcgi_headerSize]); err != nil {
		return nil, err
	}

	if req != nil {
		for {
			n, err := req.Read(c.buf[Fcgi_headerSize : Fcgi_headerSize+Fcgi_maxContent])
			if n > 0 {
				paddingLength := int(-n & 7)
				c.buf[0], c.buf[1], c.buf[2], c.buf[3] = 1, Fcgi_FCGI_STDIN, 0, 1
				c.buf[4] = byte(n >> 8)
				c.buf[5] = byte(n)
				c.buf[6] = byte(paddingLength)
				c.buf[7] = 0
				if _, err := c.conn.Write(c.buf[:Fcgi_headerSize+n+paddingLength]); err != nil {
					return nil, err
				}
			}
			if err == io.EOF {
				break
			}
			if err != nil {
				return nil, err
			}
		}
	}
	c.buf[0], c.buf[1], c.buf[2], c.buf[3], c.buf[4], c.buf[5], c.buf[6], c.buf[7] = 1, Fcgi_FCGI_STDIN, 0, 1, 0, 0, 0, 0
	if _, err := c.conn.Write(c.buf[:Fcgi_headerSize]); err != nil {
		return nil, err
	}

	return c, nil
}

func (c *Fcgi_Client) Read(p []byte) (int, error) {
	for len(c.readBuf) == 0 {
		if _, err := io.ReadFull(c.conn, c.hdr[:]); err != nil {
			return 0, err
		}
		recType := c.hdr[1]
		contentLength := (int(c.hdr[4]) << 8) | int(c.hdr[5])
		paddingLength := int(c.hdr[6])

		if recType == Fcgi_FCGI_END_REQUEST {
			total := contentLength + paddingLength
			if total > 0 {
				if _, err := io.ReadFull(c.conn, c.buf[:total]); err != nil {
					return 0, err
				}
			}
			return 0, io.EOF
		}

		total := contentLength + paddingLength
		if total > 0 {
			if _, err := io.ReadFull(c.conn, c.buf[:total]); err != nil {
				return 0, err
			}
		}

		if recType == Fcgi_FCGI_STDOUT {
			c.readBuf = c.buf[:contentLength]
			break
		}
	}

	n := copy(p, c.readBuf)
	c.readBuf = c.readBuf[n:]
	return n, nil
}

func (fc *Fcgi_Client) ReadLine() (string, error) {
	var line []byte

	for {
		if len(fc.readBuf) > 0 {
			if i := bytes.IndexByte(fc.readBuf, '\n'); i >= 0 {
				line = append(line, fc.readBuf[:i+1]...)
				fc.readBuf = fc.readBuf[i+1:]
				return string(line), nil
			}

			line = append(line, fc.readBuf...)
			fc.readBuf = nil
		}

		var err error

		for len(fc.readBuf) == 0 {
			if _, err = io.ReadFull(fc.conn, fc.hdr[:]); err != nil {
				return "", err
			}

			recType := fc.hdr[1]
			contentLength := (int(fc.hdr[4]) << 8) | int(fc.hdr[5])
			paddingLength := int(fc.hdr[6])

			total := contentLength + paddingLength

			if total > 0 {
				if _, err = io.ReadFull(fc.conn, fc.buf[:total]); err != nil {
					return "", err
				}
			}

			if recType == Fcgi_FCGI_END_REQUEST {
				return "", io.EOF
			}

			if recType == Fcgi_FCGI_STDOUT {
				fc.readBuf = fc.buf[:contentLength]
			}
		}
	}
}
