<?php
/*
Copyright 2025 Lloyd Miles M. Bersabe

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

if (strpos(strtolower(PHP_OS), 'win') !== false) {
    define('DS', '\\');
    define('EOL', "\r\n");
} else {
    define('DS', '/');
    define('EOL', "\n");
}

define('ROOT', dirname(__FILE__) . DS);
define('SAPI', php_sapi_name());

function d($var, $detailed = false) {
    if (SAPI !== 'cli' && !headers_sent()) header('Content-Type: text/plain');
    $detailed ? var_dump($var) : print_r($var);
}

class Request {
    var $data = array(), $path = '', $params = array(), $cli = array('positional' => array(), 'option' => array());
    var $globals, $server, $uri, $method, $get, $post, $files, $cookies, $argv, $argc;

    function init($globals, $server, $get, $post, $files, $cookie) {
        $this->globals = $globals;
        $this->server = $server;
        $this->uri = isset($server['REQUEST_URI']) ? $server['REQUEST_URI'] : '';
        $this->method = isset($server['REQUEST_METHOD']) ? $server['REQUEST_METHOD'] : '';
        $this->get = $get;
        $this->post = $post;
        $this->files = $files;
        $this->cookies = $cookie;
        $this->argv = isset($globals['argv']) ? $globals['argv'] : array();
        $this->argc = isset($globals['argc']) ? $globals['argc'] : 0;
        for ($i = 1; $this->argc > $i; $i++) {
            $arg = $this->argv[$i];
            if (substr($arg, 0, 2) === '--') {
                $eq = strpos($arg, '=');
                if ($eq !== false) {
                    $this->cli['option'][substr($arg, 2, $eq - 2)] = trim(substr($arg, $eq + 1), '"\'');
                } else {
                    $this->cli['option'][substr($arg, 2)] = true;
                }
            } elseif (substr($arg, 0, 1) !== '-') {
                $this->cli['positional'][] = $arg;
            }
        }
    }

    function setData($key, $value) {
        $this->data[$key] = $value;
    }

    function getData($key, $default = null) {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    function std($mark = '') {
        if (SAPI !== 'cli') return '';
        if ($mark === '' && ($line = fgets(STDIN))) return $line ? rtrim($line) : '';

        $lines = array();
        while (($line = fgets(STDIN)) !== false && ($line = rtrim($line)) !== $mark) $lines[] = $line;

        return implode(EOL, $lines);
    }
}

class Response {
    var $headers, $code, $type, $content, $stderr;

    function init($headers, $code, $type, $content, $stderr) {
        $this->headers = $headers;
        $this->code = $code;
        $this->type = $type;
        $this->content = $content;
        $this->stderr = $stderr;
    }

    function send() {
        if (SAPI === 'cli') {
            $this->std($this->content, $this->stderr);
        } else {
            echo($this->http());
        }
    }

    function http() {
        if (!headers_sent()) {
            header('HTTP/1.1 ' . $this->code);
            foreach ($this->headers as $key => $value) header($key . ': ' . $value);
            if (!isset($this->headers['Content-Type'])) header('Content-Type: ' . $this->type);
        }

        return isset($this->headers['Location']) ? '' : $this->content;
    }

    function std($msg, $err = false) {
        if (SAPI === 'cli') fwrite($err ? STDERR : STDOUT, $msg);
    }

    function html($file, $data) {
        $this->type = 'text/html';
        ob_start();
        require($file);
        $this->content = ob_get_clean();
    }

    function redirect($url, $code = 302) {
        $this->code = $code;
        $this->headers['Location'] = $url;
    }
}

class App {
    var $ENV = array(), $UNIT_LIST_INDEX = 0, $UNIT_PATH = 1, $UNIT_FILE = 2, $UNIT_LOAD = 3, $UNIT_ARGS = 4, $UNIT_CACHE = 5, $CACHE_CLASS = 0, $CACHE_PATH = 1;
    var $routes = array(), $pipes = array('prepend' => array(), 'append' => array());
    var $unit = array(), $unitList = array(), $unitListIndex = 0, $pathList = array(), $pathListIndex = 0, $cache = array(), $pathListCache = array();

    // Application Setup

    function init() {
        $this->ENV['DEBUG'] = false;

        $this->ENV['DIR_LOG'] = '';
        $this->ENV['DIR_LOG_TIMESTAMP'] = '';
        $this->ENV['DIR_RES'] = '';
        $this->ENV['DIR_WEB'] = '';
        $this->ENV['DIR_SRC'] = '';

        $this->ENV['ROUTE_FILE'] = 'index.php';
        $this->ENV['ROUTE_REWRITE'] = false;
        $this->ENV['URL_DIR_WEB'] = '';
        $this->ENV['URL_BASE'] = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http') . '://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '127.0.0.1') . '/';

        $this->ENV['ERROR_HTML_FILE'] = 'error.php';
        $this->ENV['ERROR_LOG_FILE'] = 'error';
        $this->ENV['ERROR_TRACE_IGNORE_ARGS'] = false;
        $this->ENV['SHOW_ERRORS'] = false;
        $this->ENV['LOG_ERRORS'] = true;

        $this->ENV['LOG_SIZE_LIMIT_MB'] = 5;
        $this->ENV['LOG_CLEANUP_INTERVAL_DAYS'] = 1;
        $this->ENV['LOG_RETENTION_DAYS'] = 7;
        $this->ENV['MAX_LOG_FILES'] = 10;

        $this->unit['App'] = array(0, null, null, array(), array(), true);
        $this->unitList[0] = 'App';
        $this->unitListIndex = 1;
        $this->cache['App'] = array($this, true);
    }

    function setEnv($key, $value) {
        $this->ENV[$key] = $value;
    }

    function setEnvs($env) {
        foreach ($env as $key => $value) $this->ENV[$key] = $value;
    }

    function getEnv($key, $default = null) {
        return isset($this->ENV[$key]) ? $this->ENV[$key] : $default;
    }

    function setIni($key, $value) {
        if (ini_set($key, $value) === false) $this->log('Failed to set ini setting: ' . $key, $this->ENV['ERROR_LOG_FILE']);
    }

    function setInis($ini) {
        foreach ($ini as $key => $value) {
            if (ini_set($key, $value) === false) $this->log('Failed to set ini setting: ' . $key, $this->ENV['ERROR_LOG_FILE']);
        }
    }

    // Config Management

    function save($file) {
        $file = ROOT . $file . '.dat';
        $this->write($file, serialize(array($this->routes, $this->pipes, $this->unit, $this->unitList, $this->unitListIndex, $this->pathList, $this->pathListIndex)));
        echo('File created: ' . $file . EOL);
    }

    function load($file) {
        list($this->routes, $this->pipes, $this->unit, $this->unitList, $this->unitListIndex, $this->pathList, $this->pathListIndex) = unserialize($this->read(ROOT . $file . '.dat'));
    }

    // Error Management

    function shutdown() {
        if (function_exists('error_get_last') && ($error = error_get_last()) !== null) $this->error($error['type'], $error['message'], $error['file'], $error['line']);
    }

    function error($errno, $errstr, $errfile, $errline, $return = false, $exception = false, $trace = array()) {
        if ($this->ENV['DEBUG']) {
            echo($errstr);
            return;
        }

        if (!(error_reporting() & $errno)) return;

        $http = 500;
        $type = 'text/html';
        $content = '';

        $parts = explode('|', $errstr, 2);
        if (is_numeric($parts[0])) {
            $http = (int) $parts[0];
            $errstr = $parts[1];
        }

        if ($this->ENV['LOG_ERRORS']) $this->log('[php error ' . $errno . '] [http ' . $http . '] ' . $errstr . ' in ' . $errfile . ':' . $errline, $this->ENV['ERROR_LOG_FILE']);

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $type = 'application/json';
            $content = $this->ENV['SHOW_ERRORS'] ? '{"error":"[php error ' . $errno . '] [http ' . $http . '] ' . $errstr . ' in ' . $errfile . ':' . $errline . '"}' : '{"error":"An unexpected error occurred. Please try again later."}';
        } else {
            if ($this->ENV['SHOW_ERRORS'] || SAPI === 'cli') {
                $traceOutput = 'Stack trace: ' . EOL;
                foreach (array_merge(debug_backtrace(), $trace) as $i => $frame) $traceOutput .= '#' . $i . ' ' . (isset($frame['file']) ? $frame['file'] : '[internal function]') . '(' . ((isset($frame['line']) ? $frame['line'] : 'no line')) . '): ' . (isset($frame['class']) ? $frame['class'] . (isset($frame['type']) ? $frame['type'] : '') : '') . (isset($frame['function']) ? $frame['function'] : '[unknown function]') . (!$this->ENV['ERROR_TRACE_IGNORE_ARGS'] && isset($frame['args']) ? substr(print_r($frame['args'], true), 5) : '()') . EOL;
                $type = 'text/plain';
                $content = '[php error ' . $errno . '] [http ' . $http . '] ' . $errstr . ' in '. $errfile . ':' . $errline . EOL . EOL . $traceOutput;
            } else {
                $file = ROOT . $this->ENV['ERROR_HTML_FILE'];
                if (file_exists($file)) {
                    $data = array('app' => $this, 'http' => $http);
                    ob_start();
                    include($file);
                    $content = ob_get_clean();
                } else {
                    $content = 'An unexpected error occurred. Please try again later.' . EOL;
                }
            }
        }

        if (ob_get_level() > 0) ob_end_clean();

        if ($return) return array('code' => $http, 'type' => $type, 'content' => $content);

        if (SAPI === 'cli') {
            fwrite(STDERR, $content);
        } else {
            if (!headers_sent()) {
                header('HTTP/1.1 ' . $http);
                header('Content-Type: ' . $type);
            }
            echo($content);
        }

        if (!$exception) exit(1);
    }

    // Route Management

    function setRoute($method, $route, $option) {
        $end = array('_p' => array(), '_i' => array());

        $map = array('pipe' => '_p', 'ignore' => '_i');
        foreach ($map as $key => $value) {
            if (isset($option[$key])) {
                foreach ($option[$key] as $tmpUnit) $end[$value][] = ($tmpUnit === '--global' && $key === 'ignore') ? -1 : $this->unit[$tmpUnit][$this->UNIT_LIST_INDEX];
            }
        }

        $node = &$this->routes[$method];
        $routeSegments = explode('/', $route);
        foreach ($routeSegments as $segment) {
            if (!isset($node[$segment])) $node[$segment] = array();
            $node = &$node[$segment];
        }

        if (isset($node['*'])) trigger_error('500|Duplicate route detected: ' . $route, E_USER_WARNING);

        $node['*'] = $end;
    }

    function groupRoute($group, $method, $route, $option = array()) {
        $option['pipe'] = array_merge((isset($group['pipe_prepend']) ? $group['pipe_prepend'] : array()), (isset($option['pipe']) ? $option['pipe'] : array()), (isset($group['pipe_append']) ? $group['pipe_append'] : array()));
        $option['ignore'] = array_merge((isset($group['ignore']) ? $group['ignore'] : array()), (isset($option['ignore']) ? $option['ignore'] : array()));
        $this->setRoute($method, (isset($group['prefix']) ? $group['prefix'] : '') . $route, $option);
    }

    function setPipes($pipes) {
        foreach ($pipes as $key => $p) {
            foreach ($p as $unit) $this->pipes[$key][] = $this->unit[$unit][$this->UNIT_LIST_INDEX];
        }
    }

    function resolveRoute($method, $path) {
        if (!isset($this->routes[$method])) return array('http' => 405, 'error' => 'Method not allowed: ' . $method . ' ' . $path);

        $current = $this->routes[$method];
        $params = array();
        $pathSegments = explode('/', $path);
        $decrement = 0;
        $foundSegment = false;
        $last = count($pathSegments) - 1;

        foreach ($pathSegments as $index => $pathSegment) {
            if ($pathSegment === '' && !(!$foundSegment && $last === $index)) {
                if (++$decrement > 20) return array('http' => 400, 'error' => 'Empty path segments exceeded limit (20): ' . $path);
                continue;
            }

            $foundSegment = true;

            $index -= $decrement;

            if (strlen($pathSegment) > 255) return array('http' => 400, 'error' => 'Path segment too long (max 255 chars): ' . $pathSegment);

            if (isset($current[$pathSegment])) {
                $current = $current[$pathSegment];
                continue;
            }

            $matched = false;

            foreach ($current as $key => $value) {
                if (substr($key, 0, 1) === '{' && substr($key, -1) === '}') {
                    list($paramName, $paramModifier, $paramRegex) = explode(':', substr($key, 1, -1), 3);
                    if ($paramModifier === '*') {
                        $params[$paramName] = array_slice($pathSegments, $index);
                        $current = $value;
                        if (isset($current['*'])) break 2;
                        $matched = true;
                        break;
                    }
                    $matches = array($pathSegment);
                    if ($paramRegex === '' || preg_match('/' . $paramRegex . '/', $pathSegment, $matches)) {
                        foreach ($matches as $k => $v) $matches[$k] = urldecode($v);
                        $params[$paramName] = (count($matches) === 1) ? $matches[0] : $matches;
                        $current = $value;
                        $matched = true;
                        break;
                    }
                }
            }

            if (!$matched) return array('http' => 404, 'error' => 'Route not found: ' . $method . ' ' . $path);
        }

        while (!isset($current['*'])) {
            $matched = false;

            foreach ($current as $key => $value) {
                if (substr($key, 0, 1) && substr($key, -1) === '}') {
                    list($paramName, $paramModifier) = explode(':', substr($key, 1, -1), 3);
                    if ($paramModifier === '*' || $paramModifier === '?' || (($pos = strpos($paramModifier, '=')) !== false) && ($params[$paramName] = substr($paramModifier, $pos + 1))) {
                        $current = $value;
                        $matched = true;
                        break;
                    }
                }
            }

            if (!$matched) return array('http' => 404, 'error' => 'Route not found: ' . $method . ' ' . $path);
        }

        if (!isset($current['*'])) return array('http' => 404, 'error' => 'Route not found: ' . $method . ' ' . $path);

        $finalPipes = array();

        $ignore = array_flip($current['*']['_i']);

        $pipeGroup = isset($ignore[-1]) ? array(&$current['*']['_p']) : array(&$this->pipes['prepend'], &$current['*']['_p'], &$this->pipes['append']);

        foreach ($pipeGroup as $pipes) {
            foreach ($pipes as $pipe) {
                if (!isset($ignore[$pipe])) $finalPipes[] = $pipe;
            }
        }

        return array('pipe' => $finalPipes, 'params' => $params);
    }

    // Request Handling

    function dispatch($request, $response) {
        if (SAPI === 'cli') {
            foreach ($request->cli['positional'] as $positional) $request->path .= '/' . urlencode($positional);
            $request->method = (isset($request->cli['option']['method']) && $request->cli['option']['method'] !== true) ? $request->cli['option']['method'] : '';
        } elseif ($this->ENV['ROUTE_REWRITE']) {
            $pos = strpos($request->uri, '?');
            $request->path = ($pos !== false) ? substr($request->uri, 0, $pos) : $request->uri;
        } elseif (isset($request->get['route'])) {
            $request->path = $request->get['route'];
        }

        $route = $this->resolveRoute($request->method, $request->path);

        if (isset($route['error'])) {
            $e = $this->error(E_USER_WARNING, $route['http'] . '|' . $route['error'], __FILE__, __LINE__, true);
            $response->init($response->headers, $e['code'], $e['type'], $e['content'], true);

            return $response;
        }

        $request->params = $route['params'];
        foreach ($route['pipe'] as $p) {
            $p = $this->getClass($this->unitList[$p]);
            list($request, $response, $break) = $p->pipe($request, $response);
            if ($break) break;
        }

        return $response;
    }

    // Class Management

    function scanUnits($path, $option) {
        if (!isset($option['depth'])) $option['depth'] = 0;
        if (!isset($option['max'])) $option['max'] = -1;
        if (!isset($option['ignore'])) $option['ignore'] = array();
        if (!isset($option['namespace'])) $option['namespace'] = '';
        if (!isset($option['dir_as_namespace'])) $option['dir_as_namespace'] = false;

        if ($dp = opendir(ROOT . $path)) {
            while (($file = readdir($dp)) !== false) {
                if ($file === '.' || $file === '..') continue;

                foreach ($option['ignore'] as $pattern) {
                    if (preg_match('/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/i', $file)) continue 2;
                }

                if (($option['max'] === -1 || $option['max'] > $option['depth']) && is_dir(ROOT . $path . $file)) {
                    ++$option['depth'];
                    $namespace = $option['namespace'];
                    $option['namespace'] .= $file . '\\';
                    $this->scanUnits($path . $file . DS, $option);
                    $option['namespace'] = $namespace;
                    --$option['depth'];
                } else if (substr($file, -4) === '.php') {
                    $unitFile = substr($file, 0, -4);
                    $unit = ($option['dir_as_namespace']) ? ($option['namespace'] . $unitFile) : $unitFile;

                    if (isset($this->unit[$unit])) return trigger_error('500|Duplicate unit detected: ' . $unit . ' from ' . $path . $file . ' and ' . $this->pathList[$this->unit[$unit][$this->UNIT_PATH]] . $this->unit[$unit][$this->UNIT_FILE] . '.php', E_USER_WARNING);

                    $pathListIndex = isset($this->pathListCache[$path]) ? $this->pathListCache[$path] : array_search($path, $this->pathList);
                    if ($pathListIndex === false) {
                        $pathListIndex = $this->pathListIndex;
                        $this->pathList[$this->pathListIndex] = $path;
                        ++$this->pathListIndex;
                        $this->pathListCache[$path] = $pathListIndex;
                    }

                    $this->unit[$unit] = array($this->unitListIndex, $pathListIndex, $unitFile, array(), array(), false);
                    $this->unitList[$this->unitListIndex] = $unit;
                    ++$this->unitListIndex;
                }
            }
            closedir($dp);
        }
    }

    function setUnit($unit, $option) {
        $test = $this->unit[$unit];

        $map = array('args' => $this->UNIT_ARGS, 'load' => $this->UNIT_LOAD);
        foreach ($map as $key => $value) {
            if (isset($option[$key])) {
                foreach ($option[$key] as $tmpUnit) $this->unit[$unit][$value][] = $this->unit[$tmpUnit][$this->UNIT_LIST_INDEX];
            }
        }

        $this->unit[$unit][$this->UNIT_CACHE] = (isset($option['cache']) ? $option['cache'] : $this->unit[$unit][$this->UNIT_CACHE]);
    }

    function groupUnit($group, $unit, $option = array()) {
        $option['args'] = array_merge((isset($group['args_prepend']) ? $group['args_prepend'] : array()), (isset($option['args']) ? $option['args'] : array()), (isset($group['args_append']) ? $group['args_append'] : array()));
        $option['load'] = array_merge((isset($group['load_prepend']) ? $group['load_prepend'] : array()), (isset($option['load']) ? $option['load'] : array()), (isset($group['load_append']) ? $group['load_append'] : array()));
        $option['cache'] = isset($option['cache']) ? $option['cache'] : (isset($group['cache']) ? $group['cache'] : false);
        $this->setUnit($unit, $option);
    }

    function loadUnit($unit) {
        $INDEX = 0;
        $COUNT = 1;

        $stack = array($unit);
        $stackSet = array();
        $md = array();

        while (!empty($stack)) {
            $unit = array_pop($stack);
            $unitParent = end($stack);
            $stackSet[$unitParent] = true;

            if (isset($stackSet[$unit])) return trigger_error('500|Circular load detected: ' . implode(' -> ', $stack) . ' -> ' . $unit, E_USER_WARNING);

            if (isset($this->cache[$unit][$this->CACHE_PATH])) {
                if (empty($stack)) return;

                unset($stackSet[$unitParent]);
                continue;
            }

            if ($this->unit[$unit][$this->UNIT_LOAD] !== array()) {
                if (!isset($md[$unit])) $md[$unit] = array(0, count($this->unit[$unit][$this->UNIT_LOAD]));

                if ($md[$unit][$COUNT] > $md[$unit][$INDEX]) {
                    $stack[] = $unit;
                    $stack[] = $this->unitList[$this->unit[$unit][$this->UNIT_LOAD][$md[$unit][$INDEX]]];
                    ++$md[$unit][$INDEX];
                    continue;
                }
                unset($md[$unit]);
            }

            unset($stackSet[$unitParent]);

            require(ROOT . $this->pathList[$this->unit[$unit][$this->UNIT_PATH]] . $this->unit[$unit][$this->UNIT_FILE] . '.php');
            $this->cache[$unit][$this->CACHE_PATH] = true;
        }
    }

    function newClass($unit) {
        $mode = $this->unit[$unit][$this->UNIT_CACHE];
        $this->unit[$unit][$this->UNIT_CACHE] = false;
        $class = $this->getClass($unit);
        $this->unit[$unit][$this->UNIT_CACHE] = $mode;
        return $class;
    }

    function resetClass($unit) {
        $this->cache[$unit][$this->CACHE_CLASS] = null;
    }

    function getClass($unit) {
        $INDEX = 0;
        $COUNT = 1;

        $stack = array($unit);
        $stackSet = array();
        $md = array();
        $resolved = array();
        $class = null;

        while (!empty($stack)) {
            $unit = array_pop($stack);
            $unitParent = end($stack);
            $stackSet[$unitParent] = true;

            if (isset($stackSet[$unit])) return trigger_error('Circular dependency detected: ' . implode(' -> ', $stack) . ' -> ' . $unit, E_USER_WARNING);

            $cache = $this->unit[$unit][$this->UNIT_CACHE];
            if ($cache && isset($this->cache[$unit][$this->CACHE_CLASS])) {
                if (empty($stack)) return $this->cache[$unit][$this->CACHE_CLASS];

                unset($stackSet[$unitParent]);
                $resolved[$unitParent][] = $this->cache[$unit][$this->CACHE_CLASS];
                continue;
            }

            if ($this->unit[$unit][$this->UNIT_ARGS] !== array()) {
                if (!isset($md[$unit])) $md[$unit] = array(0, count($this->unit[$unit][$this->UNIT_ARGS]));

                if ($md[$unit][$COUNT] > $md[$unit][$INDEX]) {
                    $stack[] = $unit;
                    $stack[] = $this->unitList[$this->unit[$unit][$this->UNIT_ARGS][$md[$unit][$INDEX]]];
                    ++$md[$unit][$INDEX];
                    continue;
                }
                unset($md[$unit]);
            }

            unset($stackSet[$unitParent]);

            $this->loadUnit($unit);

            $class = new $unit;
            if (isset($resolved[$unit])) $class->args($resolved[$unit]);
            unset($resolved[$unit]);

            if ($cache) $this->cache[$unit][$this->CACHE_CLASS] = $class;

            $resolved[$unitParent][] = $class;
        }

        return $class;
    }

    // Utility Functions

    function clear($property) {
        unset($this-> {$property});
    }

    function path($option, $path = '') {
        switch ($option) {
            case 'root':
                return ROOT . $path;
            case 'res':
                return ROOT . $this->ENV['DIR_RES'] . $path;
            case 'web':
                return ROOT . $this->ENV['DIR_WEB'] . $path;
            case 'src':
                return ROOT . $this->ENV['DIR_SRC'] . $path;
            default:
                return $path;
        }
    }

    function url($option, $url = '') {
        switch ($option) {
            case 'route':
                return $this->ENV['URL_BASE'] . ($this->ENV['ROUTE_REWRITE'] ? '' : $this->ENV['ROUTE_FILE'] . '?route=/') . $url;
            case 'web':
                return $this->ENV['URL_BASE'] . $this->ENV['URL_DIR_WEB'] . $url;
            default:
                return $url;
        }
    }

    function urlSlug($s) {
        return trim(preg_replace('/[^a-z0-9-]/', '', strtolower(preg_replace('/[\s-]+/', '-', $s))), '-');
    }

    function write($file, $string, $append = false) {
        if ($fp = fopen($file, (($append) ? 'a' : 'w'))) {
            fwrite($fp, (string) $string);
            fclose($fp);
        }
    }

    function read($file) {
        if ($fp = fopen($file, 'r')) {
            $fs = fstat($fp);
            $content = fread($fp, $fs['size']);
            fclose($fp);
            return $content;
        }
    }

    function log($msg, $file) {
        $mt = explode(' ', microtime());
        $micro = (float) $mt[0];
        $time = (int) $mt[1];

        $logFile = ROOT . $this->ENV['DIR_LOG'] . $file . '.log';

        $this->write($logFile, ('[' . date('Y-m-d H:i:s', $time) . '.' . sprintf('%06d', $micro * 1000000) . '] ' . $msg . EOL), true);

        if (filesize($logFile) >= ($this->ENV['LOG_SIZE_LIMIT_MB'] * 1048576)) {
            $newLogFile = ROOT . $this->ENV['DIR_LOG'] . $file . '_' . date('Y-m-d_H-i-s') . '.log';
            rename($logFile, $newLogFile);
        }

        $timestampFile = ROOT . $this->ENV['DIR_LOG_TIMESTAMP'] . $file . '_last-log-cleanup-timestamp.txt';
        $lastCleanup = file_exists($timestampFile) ? (int) $this->read($timestampFile) : 0;

        if (($time - $lastCleanup) >= $this->ENV['LOG_CLEANUP_INTERVAL_DAYS'] * 86400) {
            $logFiles = glob(ROOT . $this->ENV['DIR_LOG'] . $file . '_*.log');
            $logFilesMTime = array();

            foreach ($logFiles as $file) $logFilesMTime[$file] = filemtime($file);

            asort($logFilesMTime);
            $logFiles = array_keys($logFilesMTime);

            if (count($logFiles) > $this->ENV['MAX_LOG_FILES']) {
                $filesToDelete = array_slice($logFiles, 0, count($logFiles) - $this->ENV['MAX_LOG_FILES']);
                foreach ($filesToDelete as $file) {
                    unlink($file);
                    unset($logFilesMTime[$file]);
                }
                $logFiles = array_keys($logFilesMTime);
            }

            foreach ($logFiles as $file) {
                if (($time - $logFilesMTime[$file]) > ($this->ENV['LOG_RETENTION_DAYS'] * 86400)) unlink($file);
            }

            $this->write($timestampFile, $time);
        }
    }
}