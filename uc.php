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

while (ob_get_level() > 0) ob_end_clean();
ob_start();

define('SAPI', php_sapi_name());
define('CR', "\r");

if (strpos(strtolower(PHP_OS), 'win') !== false) {
    define('DS', '\\');
    define('EOL', "\r\n");
} else {
    define('DS', '/');
    define('EOL', "\n");
}

function d($var, $detailed = false, $exit = false) {
    if (SAPI !== 'cli' && !headers_sent()) header('Content-Type: text/plain');
    $detailed ? var_dump($var) : print_r($var);
    if ($exit) exit;
}

function input_http($in) {
    $in->source = 'http';

    $in->server = $_SERVER;

    $contentHeaders = array('CONTENT_TYPE' => true, 'CONTENT_LENGTH' => true, 'CONTENT_MD5' => true);
    foreach ($_SERVER as $key => $value) {
        if (strpos($key, 'HTTP_') === 0) {
            $in->headers[str_replace('_', '-', strtolower(substr($key, 5)))] = $value;
        } elseif (isset($contentHeaders[$key])) {
            $in->headers[str_replace('_', '-', strtolower($key))] = $value;
        }
    }

    $in->method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
    $in->uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $in->query = $_GET;
    $in->cookies = $_COOKIE;
    $in->files = $_FILES;
    $in->parsed = $_POST;
    $in->content = file_get_contents('php://input');

    return $in;
}

function input_cli($in) {
    global $argc, $argv;

    $in->source = 'cli';

    $in->argc = isset($argc) ? $argc : 0;
    $in->argv = isset($argv) ? $argv : array();

    for ($i = 1; $in->argc > $i; $i++) {
        $arg = $in->argv[$i];
        if (substr($arg, 0, 2) === '--') {
            $eq = strpos($arg, '=');
            if ($eq !== false) {
                $in->options[substr($arg, 2, $eq - 2)] = substr($arg, $eq + 1);
            } else {
                $in->flags[substr($arg, 2)] = true;
            }
        } elseif (substr($arg, 0, 1) !== '-') {
            $in->positional[] = $arg;
        }
    }

    return $in;
}

function http_preferred_mime_types($accept) {
    $types = explode(',', $accept);
    $preferences = array();

    foreach ($types as $type) {
        $parts = array_map('trim', explode(';', $type));
        $mime = array_shift($parts);
        $q = 1.0;

        foreach ($parts as $param) {
            $paramParts = explode('=', trim($param), 2);
            if (count($paramParts) == 2 && strtolower($paramParts[0]) === 'q') {
                $q = (float) $paramParts[1];
            }
        }

        if ($q !== 0) $preferences[$mime] = $q;
    }

    arsort($preferences);
    return array_keys($preferences);
}

class Input {
    var $source = '', $data = array(), $server = array(), $headers = array(), $content = '', $method = '', $uri = '', $path = '', $query = array(), $cookies = array(), $files = array(), $parsed = array(), $params = array(), $argc = 0, $argv = array(), $positional = array(), $options = array(), $flags = array();

    function getFrom(&$arr, $key, $default = null) {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    function std($mark = '') {
        if ($mark === '' && ($line = fgets(STDIN))) return $line ? rtrim($line) : '';

        $lines = array();
        while (($line = fgets(STDIN)) !== false && ($line = rtrim($line)) !== $mark) $lines[] = $line;

        return implode(EOL, $lines);
    }
}

class Output {
    var $headers = array(), $content = '', $code = 200, $type = 'text/html';

    function http() {
        if (!headers_sent()) {
            header('HTTP/1.1 ' . $this->code);
            if (!isset($this->headers['content-type'])) header('content-type: ' . $this->type);
            foreach ($this->headers as $key => $value) header($key . ': ' . $value);
        }

        echo(isset($this->headers['location']) ? '' : $this->content);
    }

    function std($msg, $err = false) {
        fwrite($err ? STDERR : STDOUT, $msg);
    }

    function html($file, $data) {
        $this->type = 'text/html';
        ob_start();
        require($file);
        $this->content = ob_get_clean();
    }

    function htmlEncode($s) {
        return htmlspecialchars($s, ENT_QUOTES);
    }

    function redirect($url, $code = 302) {
        $this->headers['location'] = $url;
        $this->code = $code;
    }
}

class App {
    var $ENV = array(), $UNIT_LIST_INDEX = 0, $UNIT_PATH = 1, $UNIT_FILE = 2, $UNIT_LOAD = 3, $UNIT_ARGS = 4, $UNIT_CACHE = 5, $CACHE_CLASS = 0, $CACHE_PATH = 1, $ROUTE_HANDLER = '!', $ROUTE_HANDLER_PIPE = 0, $ROUTE_HANDLER_IGNORE = 1;
    var $routes = array(), $pipes = array('prepend' => array(), 'append' => array());
    var $unit = array(), $unitList = array(), $unitListIndex = 0, $pathList = array(), $pathListIndex = 0, $cache = array(), $pathListCache = array();

    // Application Setup

    function init() {
        $this->ENV['DEBUG'] = false;

        $this->ENV['DIR_ROOT'] = dirname(__FILE__) . '/';
        $this->ENV['DIR_LOG'] = '';
        $this->ENV['DIR_LOG_TIMESTAMP'] = '';
        $this->ENV['DIR_RES'] = '';
        $this->ENV['DIR_WEB'] = '';
        $this->ENV['DIR_SRC'] = '';
        $this->ENV['DIR_ERROR'] = '';

        $this->ENV['ROUTE_FILE'] = 'index.php';
        $this->ENV['ROUTE_REWRITE'] = false;
        $this->ENV['URL_DIR_WEB'] = '';
        $this->ENV['URL_BASE'] = '/';

        $this->ENV['ERROR_LOG_FILE'] = 'error';
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
        $file = $this->ENV['DIR_ROOT'] . $file . '.dat';
        $this->write($file, serialize(array($this->routes, $this->pipes, $this->unit, $this->unitList, $this->unitListIndex, $this->pathList, $this->pathListIndex)));
        echo('File created: ' . $file . EOL);
    }

    function load($file) {
        list($this->routes, $this->pipes, $this->unit, $this->unitList, $this->unitListIndex, $this->pathList, $this->pathListIndex) = unserialize($this->read($this->ENV['DIR_ROOT'] . $file . '.dat'));
    }

    // Error Management

    function error($errno, $errstr, $errfile, $errline, $return = false, $exception = false, $trace = array()) {
        if (ob_get_level() > 0) ob_clean();

        if ($this->ENV['DEBUG']) {
            echo($errstr);
            return;
        }

        if (!(error_reporting() & $errno)) return;

        $code = 500;
        $type = 'text/plain';
        $content = '';

        $parts = explode('|', $errstr, 2);
        if (is_numeric($parts[0])) {
            $code = (int) $parts[0];
            $errstr = $parts[1];
        }

        if ($this->ENV['LOG_ERRORS']) $this->log('[php error ' . $errno . '] [' . (SAPI === 'cli' ? 'cli' : 'http') . $code . '] ' . $errstr . ' in ' . $errfile . ':' . $errline, $this->ENV['ERROR_LOG_FILE']);

        if ($this->ENV['SHOW_ERRORS'] || SAPI === 'cli') {
            $content = '[php error ' . $errno . '] [' . (SAPI === 'cli' ? 'cli' : 'http') . ' ' . $code . '] ' . $errstr . ' in '. $errfile . ':' . $errline . EOL . EOL . 'Stack trace: ' . EOL;

            foreach (array_merge(debug_backtrace(), $trace) as $i => $frame) $content .= '#' . $i . ' ' . (isset($frame['file']) ? $frame['file'] : '[internal function]') . '(' . ((isset($frame['line']) ? $frame['line'] : 'no line')) . '): ' . (isset($frame['class']) ? $frame['class'] . (isset($frame['type']) ? $frame['type'] : '') : '') . (isset($frame['function']) ? $frame['function'] : '[unknown function]') . '(...' . (isset($frame['args']) ? count($frame['args']) : 0) . ')' . EOL;
        }

        $typeExist = false;
        $file = '';
        foreach (http_preferred_mime_types($this->getEnv('ACCEPT', '')) as $t) {
            $file = $this->ENV['DIR_ROOT'] . $this->ENV['DIR_ERROR'] . str_replace('/', '_', $t) . '.php';
            if ($typeExist = file_exists($file)) {
                $type = $t;
                break;
            }
        }

        if ($typeExist) {
            $data = array('app' => $this, 'code' => $code, 'error' => $content);
            ob_start();
            include($file);
            $content = ob_get_clean();
        } else {
            if (SAPI !== 'cli') $code = 406;
            $content = $this->ENV['SHOW_ERRORS'] ? $content : '';
        }

        if ($return) return array('code' => $code, 'type' => $type, 'content' => $content);

        if (SAPI === 'cli') {
            fwrite(STDERR, $content);
        } else {
            if (!headers_sent()) {
                header('HTTP/1.1 ' . $code);
                header('content-type: ' . $type);
            }
            echo($content);
        }

        if (!$exception) exit($code > 255 ? 1 : $code);
    }

    // Route Management

    function setRoute($method, $route, $option) {
        $handler = array($this->ROUTE_HANDLER_PIPE => array(), $this->ROUTE_HANDLER_IGNORE => array());

        $map = array('pipe' => $this->ROUTE_HANDLER_PIPE, 'ignore' => $this->ROUTE_HANDLER_IGNORE);
        foreach ($map as $key => $value) {
            if (isset($option[$key])) {
                foreach ($option[$key] as $tmpUnit) $handler[$value][] = ($tmpUnit === '--global' && $key === 'ignore') ? -1 : $this->unit[$tmpUnit][$this->UNIT_LIST_INDEX];
            }
        }

        $node = &$this->routes[$method];
        $routeSegments = explode('/', $route);
        foreach ($routeSegments as $segment) {
            if (!isset($node[$segment])) $node[$segment] = array();
            $node = &$node[$segment];
        }

        if (isset($node[$this->ROUTE_HANDLER])) return trigger_error('500|Duplicate route detected: ' . $route, E_USER_WARNING);

        $node[$this->ROUTE_HANDLER] = $handler;
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
                if (substr($key, 0, 1) === ':') {
                    list($prefix, $paramName, $paramModifier, $paramRegex) = explode(':', $key, 4);
                    if ($paramModifier === '*') {
                        $params[$paramName] = array_slice($pathSegments, $index + $decrement);
                        $current = $value;
                        if (isset($current[$this->ROUTE_HANDLER])) break 2;
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

        while (!isset($current[$this->ROUTE_HANDLER])) {
            $matched = false;

            foreach ($current as $key => $value) {
                if (substr($key, 0, 1) === ':') {
                    list($prefix, $paramName, $paramModifier) = explode(':', $key, 4);
                    if ($paramModifier === '*' || $paramModifier === '?' || (($pos = strpos($paramModifier, '=')) !== false) && ($params[$paramName] = substr($paramModifier, $pos + 1))) {
                        $current = $value;
                        $matched = true;
                        break;
                    }
                }
            }

            if (!$matched) return array('http' => 404, 'error' => 'Route not found: ' . $method . ' ' . $path);
        }

        if (!isset($current[$this->ROUTE_HANDLER])) return array('http' => 404, 'error' => 'Route not found: ' . $method . ' ' . $path);

        $finalPipes = array();

        $ignore = array_flip($current[$this->ROUTE_HANDLER][$this->ROUTE_HANDLER_IGNORE]);

        $pipeGroup = isset($ignore[-1]) ? array(&$current[$this->ROUTE_HANDLER][$this->ROUTE_HANDLER_PIPE]) : array(&$this->pipes['prepend'], &$current[$this->ROUTE_HANDLER][$this->ROUTE_HANDLER_PIPE], &$this->pipes['append']);

        foreach ($pipeGroup as $pipes) {
            foreach ($pipes as $pipe) {
                if (!isset($ignore[$pipe])) $finalPipes[] = $pipe;
            }
        }

        return array('pipe' => $finalPipes, 'params' => $params);
    }

    // Request Handling

    function dispatch($input, $output) {
        if (SAPI === 'cli') {
            foreach ($input->positional as $positional) $input->path .= '/' . urlencode($positional);
            $input->method = (isset($input->options['method']) && $input->options['method'] !== true) ? $input->options['method'] : '';
        } elseif ($this->ENV['ROUTE_REWRITE']) {
            $pos = strpos($input->uri, '?');
            $input->path = ($pos !== false) ? substr($input->uri, 0, $pos) : $input->uri;
        } elseif (isset($input->query['route'])) {
            $input->path = $input->query['route'];
        }

        $route = $this->resolveRoute($input->method, $input->path);

        if (isset($route['error'])) trigger_error((SAPI === 'cli' ? 1 : $route['http']) . '|' . $route['error'], E_USER_WARNING);

        $input->params = $route['params'];
        foreach ($route['pipe'] as $p) {
            $p = $this->loadClass($this->unitList[$p]);
            list($input, $output, $success) = $p->process($input, $output);
            if (!$success) break;
        }

        return $output;
    }

    // Class Management

    function scanUnits($path, $option) {
        if (!isset($option['depth'])) $option['depth'] = 0;
        if (!isset($option['max'])) $option['max'] = -1;
        if (!isset($option['ignore'])) $option['ignore'] = array();
        if (!isset($option['namespace'])) $option['namespace'] = '';
        if (!isset($option['dir_as_namespace'])) $option['dir_as_namespace'] = false;

        if ($dp = opendir($this->ENV['DIR_ROOT'] . $path)) {
            while (($file = readdir($dp)) !== false) {
                if ($file === '.' || $file === '..') continue;

                foreach ($option['ignore'] as $pattern) {
                    if (fnmatch(strtolower($pattern), strtolower($file))) continue 2;
                }

                if (($option['max'] === -1 || $option['max'] > $option['depth']) && is_dir($this->ENV['DIR_ROOT'] . $path . $file)) {
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
        $stack = array($unit);
        $seen = array();
        $md = array();

        while ($stack) {
            $unit = array_pop($stack);
            $previousUnit = end($stack);
            $seen[$previousUnit] = true;

            if (isset($seen[$unit])) return trigger_error('500|Circular load detected: ' . implode(' -> ', $stack) . ' -> ' . $unit, E_USER_WARNING);

            if (isset($this->cache[$unit][$this->CACHE_PATH])) {
                if (!$stack) return;

                unset($seen[$previousUnit]);
                continue;
            }

            $load = $this->unit[$unit][$this->UNIT_LOAD];
            if ($load) {
                if (!isset($md[$unit])) $md[$unit] = array(0, count($load));

                if ($md[$unit][1] > $md[$unit][0]) {
                    $stack[] = $unit;
                    $stack[] = $this->unitList[$load[$md[$unit][0]]];
                    ++$md[$unit][0];
                    continue;
                }
                unset($md[$unit]);
            }

            unset($seen[$previousUnit]);

            require($this->ENV['DIR_ROOT'] . $this->pathList[$this->unit[$unit][$this->UNIT_PATH]] . $this->unit[$unit][$this->UNIT_FILE] . '.php');
            $this->cache[$unit][$this->CACHE_PATH] = true;
        }
    }

    function loadClass($unit, $new = false) {
        $stack = array($unit);
        $seen = array();
        $md = array();
        $resolvedArgs = array();
        $class = null;

        while ($stack) {
            $unit = array_pop($stack);
            $previousUnit = end($stack);
            $seen[$previousUnit] = true;

            if (isset($seen[$unit])) return trigger_error('Circular dependency detected: ' . implode(' -> ', $stack) . ' -> ' . $unit, E_USER_WARNING);

            $cache = !$new && $this->unit[$unit][$this->UNIT_CACHE];
            if ($cache && isset($this->cache[$unit][$this->CACHE_CLASS])) {
                if (!$stack) return $this->cache[$unit][$this->CACHE_CLASS];

                unset($seen[$previousUnit]);
                $resolvedArgs[$previousUnit][] = $this->cache[$unit][$this->CACHE_CLASS];
                continue;
            }

            $args = $this->unit[$unit][$this->UNIT_ARGS];
            if ($args) {
                if (!isset($md[$unit])) $md[$unit] = array(0, count($args));

                if ($md[$unit][1] > $md[$unit][0]) {
                    $stack[] = $unit;
                    $stack[] = $this->unitList[$args[$md[$unit][0]]];
                    ++$md[$unit][0];
                    continue;
                }
                unset($md[$unit]);
            }

            unset($seen[$previousUnit]);

            $this->loadUnit($unit);

            $class = new $unit;
            if (isset($resolvedArgs[$unit])) {
                $class->args($resolvedArgs[$unit]);
                unset($resolvedArgs[$unit]);
            }

            if ($cache) $this->cache[$unit][$this->CACHE_CLASS] = $class;

            $resolvedArgs[$previousUnit][] = $class;
        }

        return $class;
    }

    function reloadClass($unit) {
        if ($this->unit[$unit][$this->UNIT_CACHE]) $this->cache[$unit][$this->CACHE_CLASS] = $this->loadClass($unit, true);
    }

    // Utility Functions

    function clear($property) {
        unset($this-> { $property });
    }

    function dirRoot($s) {
        return $this->ENV['DIR_ROOT'] . $s;
    }

    function dirRes($s) {
        return $this->ENV['DIR_ROOT'] . $this->ENV['DIR_RES'] . $s;
    }

    function dirWeb($s) {
        return $this->ENV['DIR_ROOT'] . $this->ENV['DIR_WEB'] . $s;
    }

    function dirSrc($s) {
        return $this->ENV['DIR_ROOT'] . $this->ENV['DIR_SRC'] . $s;
    }

    function urlRoute($s, $params = array()) {
        return $this->ENV['URL_BASE'] . ($this->ENV['ROUTE_REWRITE'] ? '' : $this->ENV['ROUTE_FILE'] . '?route=/') . ($params ? strtr($s, $params) : $s);
    }

    function urlWeb($s, $params = array()) {
        return $this->ENV['URL_BASE'] . $this->ENV['URL_DIR_WEB'] . ($params ? strtr($s, $params) : $s);
    }

    function strSlug($s) {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($s)), '-');
    }

    function write($file, $string, $append = false) {
        if ($fp = fopen($file, (($append) ? 'ab' : 'wb'))) {
            fwrite($fp, (string) $string);
            fclose($fp);
        }
    }

    function read($file) {
        if ($fp = fopen($file, 'rb')) {
            $chunks = array();
            while (!feof($fp)) $chunks[] = fread($fp, 8192);
            fclose($fp);
            return implode('', $chunks);
        }
        return false;
    }

    function log($msg, $file) {
        $mt = explode(' ', microtime());
        $micro = (float) $mt[0];
        $time = (int) $mt[1];

        $logDir = $this->ENV['DIR_ROOT'] . $this->ENV['DIR_LOG'];
        $logFile = $logDir . $file . '.log';

        $this->write($logFile, ('[' . date('Y-m-d H:i:s', $time) . '.' . sprintf('%06d', $micro * 1000000) . '] ' . $msg . EOL), true);

        if (filesize($logFile) >= ($this->ENV['LOG_SIZE_LIMIT_MB'] * 1048576)) {
            $newLogFile = $logDir . $file . '_' . date('Y-m-d_H-i-s') . '.log';
            rename($logFile, $newLogFile);
        }

        $timestampFile = $this->ENV['DIR_ROOT'] . $this->ENV['DIR_LOG_TIMESTAMP'] . $file . '_last-log-cleanup-timestamp.txt';
        $lastCleanup = file_exists($timestampFile) ? (int) $this->read($timestampFile) : 0;

        if (($time - $lastCleanup) >= $this->ENV['LOG_CLEANUP_INTERVAL_DAYS'] * 86400) {
            $logFiles = glob($logDir . $file . '_*.log');
            $logFilesMTime = array();

            foreach ($logFiles as $lf) {
                $lfmtime = filemtime($lf);
                if (($time - $lfmtime) > ($this->ENV['LOG_RETENTION_DAYS'] * 86400)) {
                    unlink($lf);
                    continue;
                }
                $logFilesMTime[$lf] = $lfmtime;
            }

            asort($logFilesMTime);
            $logFiles = array_keys($logFilesMTime);

            if (count($logFiles) > $this->ENV['MAX_LOG_FILES']) {
                $maxIndex = count($logFiles) - $this->ENV['MAX_LOG_FILES'];
                for ($i = 0; $maxIndex > $i; $i++) unlink($logFiles[$i]);
            }

            $this->write($timestampFile, $time);
        }
    }
}