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

if (strpos(strtolower(PHP_OS), 'win') !== false) {
    define('DS', '\\');
    define('EOL', "\r\n");
} else {
    define('DS', '/');
    define('EOL', "\n");
}

function d($var, $detailed = false) {
    if (SAPI !== 'cli' && !headers_sent()) header('Content-Type: text/plain');
    $detailed ? var_dump($var) : print_r($var);
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
        } else {
            $in->positional[] = $arg;
        }
    }

    return $in;
}

class Input {
    var $source = '', $data = array(), $server = array(), $headers = array(), $content = '', $method = '', $uri = '', $route = '/', $query = array(), $cookies = array(), $files = array(), $parsed = array(), $params = array(), $argc = 0, $argv = array(), $positional = array(), $options = array(), $flags = array();

    function getFrom(&$arr, $key, $default = null) {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    function std($mark = '', $eol = "\n") {
        if ($mark === '' && ($line = fgets(STDIN))) return $line ? rtrim($line) : '';

        $lines = array();
        while (($line = fgets(STDIN)) !== false && ($line = rtrim($line)) !== $mark) $lines[] = $line;

        return implode($eol, $lines);
    }
}

class Output {
    var $headers = array(), $content = '', $code = 200, $type = 'text/html';

    function http($content) {
        if (!headers_sent()) {
            header('HTTP/1.1 ' . $this->code);
            if (!isset($this->headers['content-type'])) header('content-type: ' . $this->type);
            foreach ($this->headers as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $v) header($key . ': ' . $v, false);
                } else {
                    header($key . ': ' . $value);
                }
            }
        }

        if (!isset($this->headers['location'])) {
            ob_clean();
            echo $content;
            ob_flush();
            flush();
        }
    }

    function std($content, $err = false) {
        fwrite($err ? STDERR : STDOUT, $content);
    }

    function redirect($url, $code = 302) {
        $this->headers['location'] = $url;
        $this->code = $code;
    }
}

class App {
    var $ENV = array(), $UNIT_LIST_INDEX = 0, $UNIT_PATH = 1, $UNIT_FILE = 2, $UNIT_LOAD = 3, $UNIT_ARGS = 4, $UNIT_CACHE = 5, $CACHE_CLASS = 0, $CACHE_PATH = 1, $ROUTE_HANDLER = '!', $ROUTE_HANDLER_PIPE = 0, $ROUTE_HANDLER_IGNORE = 1;
    var $routes = array(), $pipes = array('prepend' => array(), 'append' => array());
    var $unit = array(), $unitList = array(), $unitListIndex = 0, $pathList = array(), $pathListIndex = 0, $cache = array();

    // Application Setup

    function init() {
        $this->ENV['DEBUG'] = false;

        $this->ENV['DIR_ROOT'] = $this->dir(dirname(__FILE__)) . '/';
        $this->ENV['DIR_WEB'] = '';
        $this->ENV['DIR_LOG'] = '';
        $this->ENV['DIR_LOG_TIMESTAMP'] = '';

        $this->ENV['ROUTE_FILE'] = 'index.php';
        $this->ENV['ROUTE_REWRITE'] = false;
        $this->ENV['URL_ROOT'] = '/';
        $this->ENV['URL_WEB'] = '/';

        $this->ENV['ERROR_TEMPLATES'] = array();
        $this->ENV['ERROR_NON_FATAL'] = E_NOTICE | E_USER_NOTICE;
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

        set_error_handler(array($this, 'error'));
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
        echo('File created: ' . $file . "\n");
    }

    function load($file) {
        list($this->routes, $this->pipes, $this->unit, $this->unitList, $this->unitListIndex, $this->pathList, $this->pathListIndex) = unserialize($this->read($this->ENV['DIR_ROOT'] . $file . '.dat'));
    }

    // Error Management

    function error($errno, $errstr, $errfile, $errline, $errcontext = array()) {
        if (!($errno & error_reporting())) return true;

        ob_clean();

        if ($this->ENV['DEBUG']) {
            echo($errstr);
            return true;
        }

        $type = $this->httpNegotiate($this->getEnv('ACCEPT', ''), array_keys($this->ENV['ERROR_TEMPLATES']));
        if ($type === null) $type = 'text/plain';
        $code = 500;
        $content = '';

        $parts = explode('|', $errstr, 2);
        if (is_numeric($parts[0])) {
            $code = (int) $parts[0];
            $errstr = $parts[1];
        }

        $code = SAPI === 'cli' && $code > 255 ? 1 : $code;

        if ($this->ENV['LOG_ERRORS']) $this->log('[php error ' . $errno . '] [' . SAPI . ' ' . $code . '] ' . $errstr . ' in '. $errfile . ':' . $errline, $this->ENV['ERROR_LOG_FILE']);

        if ($errno & $this->ENV['ERROR_NON_FATAL']) return true;

        if ($this->ENV['SHOW_ERRORS'] || SAPI === 'cli') {
            $content = '[php error ' . $errno . '] [' . SAPI . ' ' . $code . '] ' . $errstr . ' in '. $errfile . ':' . $errline . "\n\n" . 'Stack trace: ' . "\n";

            foreach (array_merge(debug_backtrace(), isset($errcontext['ERROR_TRACE']) ? $errcontext['ERROR_TRACE'] : array()) as $i => $frame) $content .= '#' . $i . ' ' . (isset($frame['file']) ? $frame['file'] : '[internal function]') . '(' . ((isset($frame['line']) ? $frame['line'] : 'no line')) . '): ' . (isset($frame['class']) ? $frame['class'] . (isset($frame['type']) ? $frame['type'] : '') : '') . (isset($frame['function']) ? $frame['function'] : '[unknown function]') . '(...' . (isset($frame['args']) ? count($frame['args']) : 0) . ')' . "\n";
        }

        if (file_exists($this->ENV['DIR_ROOT'] . $this->ENV['ERROR_TEMPLATES'][$type])) {
            $content = $this->template($this->ENV['DIR_ROOT'] . $this->ENV['ERROR_TEMPLATES'][$type], array('app' => $this, 'code' => $code, 'error' => $content));
        }

        if (SAPI === 'cli') {
            fwrite(STDERR, $content);
        } else {
            if (!headers_sent()) {
                header('HTTP/1.1 ' . $code);
                header('content-type: ' . $type);
            }
            echo($content);
        }

        if (isset($errcontext['ERROR_RETURN'])) return true;

        exit($code > 255 ? 1 : $code);
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
        $routeSegments = explode('/', trim($route, '/'));
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
        $this->setRoute($method, $route, $option);
    }

    function setPipes($pipes) {
        foreach ($pipes as $key => $p) {
            foreach ($p as $unit) $this->pipes[$key][] = $this->unit[$unit][$this->UNIT_LIST_INDEX];
        }
    }

    function resolveRoute($method, $route) {
        if (!isset($this->routes[$method])) return array('http' => 405, 'error' => 'Method not allowed: ' . $method . ' ' . $route);

        $current = $this->routes[$method];
        $params = array();
        $routeSegments = explode('/', $route);
        $emptySegmentsCount = 0;
        $foundSegment = false;
        $last = count($routeSegments) - 1;

        foreach ($routeSegments as $index => $routeSegment) {
            if ($routeSegment === '' && !(!$foundSegment && $last === $index)) {
                if (++$emptySegmentsCount > 20) return array('http' => 400, 'error' => 'Empty route segments exceeded limit (20): ' . $route);
                continue;
            }

            $foundSegment = true;

            if (strlen($routeSegment) > 255) return array('http' => 400, 'error' => 'Route segment too long (max 255 chars): ' . $routeSegment);

            if (isset($current[$routeSegment])) {
                $current = $current[$routeSegment];
                continue;
            }

            $matched = false;

            foreach ($current as $key => $value) {
                if (substr($key, 0, 1) === ':') {
                    list($none, $paramName, $paramModifier, $paramRegex) = explode(':', $key, 4);
                    if ($paramModifier === '*') {
                        $params[$paramName] = array_slice($routeSegments, $index);
                        $current = $value;
                        if (isset($current[$this->ROUTE_HANDLER])) break 2;
                        $matched = true;
                        break;
                    }
                    $matches = array($routeSegment);
                    if ($paramRegex === '' || preg_match('/' . $paramRegex . '/', $routeSegment, $matches)) {
                        foreach ($matches as $k => $v) $matches[$k] = urldecode($v);
                        $params[$paramName] = (count($matches) === 1) ? $matches[0] : $matches;
                        $current = $value;
                        $matched = true;
                        break;
                    }
                }
            }

            if (!$matched) return array('http' => 404, 'error' => 'Route not found: ' . $method . ' ' . $route);
        }

        while (!isset($current[$this->ROUTE_HANDLER])) {
            $matched = false;

            foreach ($current as $key => $value) {
                if (substr($key, 0, 1) === ':') {
                    list($none, $paramName, $paramModifier) = explode(':', $key, 4);
                    if ($paramModifier === '*' || $paramModifier === '?' || (($pos = strpos($paramModifier, '=')) !== false) && ($params[$paramName] = substr($paramModifier, $pos + 1))) {
                        $current = $value;
                        $matched = true;
                        break;
                    }
                }
            }

            if (!$matched) return array('http' => 404, 'error' => 'Route not found: ' . $method . ' ' . $route);
        }

        if (!isset($current[$this->ROUTE_HANDLER])) return array('http' => 404, 'error' => 'Route not found: ' . $method . ' ' . $route);

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
            foreach ($input->positional as $positional) $input->route .= '/' . urlencode($positional);
            if (isset($input->flags['request'])) {
                foreach ((isset($input->options['header']) ? explode("\n", $input->options['header']) : array()) as $header) {
                    list($k, $v) = explode(':', $header, 2);
                    $input->headers[strtolower(trim($k))] = trim($v);
                }
                $input->content = isset($input->options['content']) ? $input->options['content'] : '';
                $input->method = isset($input->options['method']) ? $input->options['method'] : 'GET';
                if (isset($input->options['query'])) parse_str($input->options['query'], $input->query);
            }
        } elseif ($this->ENV['ROUTE_REWRITE']) {
            $pos = strpos($input->uri, '?');
            $input->route = ($pos !== false) ? substr($input->uri, 0, $pos) : $input->uri;
        } elseif (isset($input->query['route']) && $input->query['route'] !== '') {
            $input->route = ($input->query['route'][0] === '/' ? '' : '/') . $input->query['route'];
        }

        $route = $this->resolveRoute($input->method, $input->route);

        if (isset($route['error'])) return trigger_error((SAPI === 'cli' ? 1 : $route['http']) . '|' . $route['error'], E_USER_WARNING);

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
                    $this->scanUnits($path . $file . '/', $option);
                    $option['namespace'] = $namespace;
                    --$option['depth'];
                } else if (substr($file, -4) === '.php') {
                    $unitFile = substr($file, 0, -4);
                    $unit = ($option['dir_as_namespace']) ? ($option['namespace'] . $unitFile) : $unitFile;

                    if (isset($this->unit[$unit])) return trigger_error('500|Duplicate unit detected: ' . $unit . ' from ' . $path . $file . ' and ' . $this->pathList[$this->unit[$unit][$this->UNIT_PATH]] . $this->unit[$unit][$this->UNIT_FILE] . '.php', E_USER_WARNING);

                    $pathListIndex = array_search($path, $this->pathList);
                    if ($pathListIndex === false) {
                        $pathListIndex = $this->pathListIndex;
                        $this->pathList[$this->pathListIndex] = $path;
                        ++$this->pathListIndex;
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
        unset($this->{$property});
    }

    function dir($s) {
        return str_replace(array('/', '\\'), '/', $s);
    }

    function dirRoot($s = '') {
        return $this->ENV['DIR_ROOT'] . $s;
    }

    function dirWeb($s = '') {
        return $this->ENV['DIR_ROOT'] . $this->ENV['DIR_WEB'] . $s;
    }

    function urlRoute($s, $params = array()) {
        $base = $this->ENV['URL_ROOT'] . ($this->ENV['ROUTE_REWRITE'] ? '' : $this->ENV['ROUTE_FILE'] . '?route=/');
        if (!$this->ENV['ROUTE_REWRITE'] && strpos($base, '?') !== false) $s = str_replace('?', '&', $s);
        return $base . ($params ? strtr($s, $params) : $s);
    }

    function urlWeb($s, $params = array()) {
        return $this->ENV['URL_WEB'] . ($params ? strtr($s, $params) : $s);
    }

    function strSlug($s) {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($s)), '-');
    }

    function template($file, $data = array()) {
        ob_start();
        require($file);
        return ob_get_clean();
    }

    function htmlEncode($s) {
        return isset($s) ? htmlspecialchars($s, ENT_QUOTES) : '';
    }

    function httpNegotiate($accept, $offers) {
        $prefs = array();
        foreach (explode(',', $accept) as $type) {
            $parts = explode(';', trim($type));
            $aType = trim(array_shift($parts));
    
            $q = 1.0;
            foreach ($parts as $p) {
                $p = explode('=', trim($p));
                if (isset($p[1]) && strtolower(trim($p[0])) === 'q') $q = (float)trim($p[1]);
            }
            if ($q > 0) $prefs[$aType] = $q;
        }
        arsort($prefs);
        foreach (array_keys($prefs) as $p) {
            foreach ($offers as $o) {
                if ($p === $o || $p === '*/*' || (substr($p, -2) === '/*' && strpos($o, substr($p, 0, -1)) === 0)) return $o;
            }
        }
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

        $this->write($logFile, ('[' . date('Y-m-d H:i:s', $time) . '.' . sprintf('%06d', $micro * 1000000) . '] ' . $msg . "\n"), true);

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