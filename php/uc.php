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

// Version 0.6.5

while (ob_get_level()) {
    ob_end_clean();
}

function d($var, $detailed = false) {
    if (php_sapi_name() !== 'cli' && !headers_sent()) {
        header('Content-Type: text/plain');
    }
    $detailed ? var_dump($var) : print_r($var);
}

function input_http($in) {
    $in->source = 'http';

    $contentHeader = array('CONTENT_TYPE' => true, 'CONTENT_LENGTH' => true);
    foreach ($_SERVER as $key => $value) {
        if (substr($key, 0, 5) === 'HTTP_') {
            $in->header[str_replace('_', '-', strtolower(substr($key, 5)))] = $value;
        } elseif (isset($contentHeader[$key])) {
            $in->header[str_replace('_', '-', strtolower($key))] = $value;
        }
    }

    $in->version = isset($_SERVER['SERVER_PROTOCOL']) ? substr($_SERVER['SERVER_PROTOCOL'], 5) : '1.1';
    $in->method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
    $in->uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $in->cookie = $_COOKIE;
    $in->query = $_GET;
    $in->frame = array_merge($_POST, $_FILES);

    return $in;
}

function input_cli($in) {
    $in->source = 'cli';

    global $argc, $argv;

    $in->argc = isset($argc) ? $argc : 0;
    $in->argv = isset($argv) ? $argv : array();

    for ($i = 1; $in->argc > $i; $i++) {
        $arg = $in->argv[$i];
        if (substr($arg, 0, 2) === '--') {
            $eq = strpos($arg, '=');
            if ($eq !== false) {
                $in->query[] = urlencode(substr($arg, 2, $eq - 2)) . '=' . urlencode(substr($arg, $eq + 1));
            } else {
                $in->query[] = urlencode(substr($arg, 2));
            }
        } else {
            $in->uri .= '/' . rawurlencode($arg);
        }
    }

    parse_str(implode('&', $in->query), $in->query);

    return $in;
}

class Input {
    var $source = '';
    var $data = array();

    var $header = array();
    var $content = '';
    var $version = '1.1';
    var $method = '';
    var $uri = '';

    var $argc = 0;
    var $argv = array();

    var $route = '';
    var $cookie = array();
    var $query = array();
    var $frame = array();
    var $param = array();

    function getFrom(&$arr, $key, $default = null) {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    function std($mark = '', $eol = "\n") {
        if ($mark === '') {
            return ($line = fgets(STDIN)) !== false ? rtrim($line) : '';
        }

        $lines = array();
        while (($line = fgets(STDIN)) !== false && ($line = rtrim($line)) !== $mark) {
            $lines[] = $line;
        }

        return implode($eol, $lines);
    }
}

class Output {
    var $header = array();
    var $content = '';
    var $code = 200;
    var $version = '1.1';

    function http($content) {
        if (!headers_sent()) {
            header('HTTP/' . $this->version . ' ' . $this->code);
            if (!isset($this->header['content-type'])) {
                $this->header['content-type'] = 'text/html';
            }
            foreach ($this->header as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        header($key . ': ' . $v, false);
                    }
                } else {
                    header($key . ': ' . $value);
                }
            }
        }

        if (!isset($this->header['location'])) {
            echo $content;
            flush();
        }
    }

    function std($content, $err = false) {
        fwrite($err ? STDERR : STDOUT, $content);
    }

    function redirect($url, $code = 302) {
        $this->header['location'] = $url;
        $this->code = $code;
    }
}

class App {
    var $UNIT_LIST = 0;
    var $UNIT_PATH = 1;
    var $UNIT_FILE = 2;
    var $UNIT_LOAD = 3;
    var $UNIT_ARGS = 4;
    var $UNIT_INST_CACHE = 5;
    var $ROUTE_HANDLER = '!';
    var $ROUTE_HANDLER_PIPE = 0;
    var $ROUTE_HANDLER_IGNORE = 1;

    var $routes = array();
    var $pipes = array('prepend' => array(), 'append' => array());
    var $unit = array();
    var $unitList = array();
    var $unitListIndex = 0;
    var $pathList = array();
    var $pathListIndex = 0;
    var $unitInstCache = array();
    var $unitPathCache = array();
    var $pathListCache = array();

    var $env = array(
        'SAPI' => '',

        'DIR_ROOT' => '',
        'DIR_WEB' => '',
        'DIR_LOG' => '',
        'DIR_LOG_TIMESTAMP' => '',

        'ROUTE_FILE' => 'index.php',
        'ROUTE_REWRITE' => false,
        'URL_ROOT' => '/',
        'URL_WEB' => '/',

        'ERROR_TEMPLATES' => array(),
        'ERROR_NON_FATAL' => 0,
        'ERROR_LOG_FILE' => 'error.log',
        'ERROR_MAX_LENGTH' => 4096,
        'SHOW_ERRORS' => true,
        'LOG_ERRORS' => false,

        'LOG_SIZE_LIMIT_MB' => 5,
        'LOG_CLEANUP_INTERVAL_DAYS' => 1,
        'LOG_RETENTION_DAYS' => 7,
        'MAX_LOG_FILES' => 10,
    );

    // Application Setup

    function init() {
        $this->env['SAPI'] = php_sapi_name();
        $this->env['DIR_ROOT'] = $this->dir(dirname(__FILE__)) . '/';
        $this->env['ERROR_NON_FATAL'] = E_NOTICE | E_USER_NOTICE;

        if (!isset($this->unit['App'])) {
            $this->addUnit('App');
            $this->setUnit('App', array('cache' => true));
        }
        $this->unitInstCache['App'] = $this;
        $this->unitPathCache['App'] = true;

        set_error_handler(array($this, 'handleErrorDefault'));
    }

    function setEnv($key, $value) {
        $this->env[$key] = $value;
    }

    function getEnv($key, $default = null) {
        return isset($this->env[$key]) ? $this->env[$key] : $default;
    }

    function setIni($key, $value) {
        if (ini_set($key, $value) === false) {
            $this->log('Failed to set ini setting: ' . $key, $this->env['ERROR_LOG_FILE']);
        }
    }

    function getIni($key) {
        return ini_get($key);
    }

    // Config Management

    function save($file) {
        $file = $this->env['DIR_ROOT'] . $file;
        $this->write($file, serialize(array($this->routes, $this->pipes, $this->unit, $this->unitList, $this->unitListIndex, $this->pathList, $this->pathListIndex)));
        echo 'File created: ' . $file . "\n";
    }

    function load($file) {
        list($this->routes, $this->pipes, $this->unit, $this->unitList, $this->unitListIndex, $this->pathList, $this->pathListIndex) = unserialize($this->read($this->env['DIR_ROOT'] . $file));
    }

    // Error Management

    function handleErrorDefault($errno, $errstr, $errfile, $errline) {
        $e = $this->error($errno, $errstr, $errfile, $errline, array('ERROR_ACCEPT' => $this->getEnv('ERROR_ACCEPT', '')));

        if (!$e) {
            return true;
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        if ($this->env['SAPI'] === 'cli') {
            fwrite(STDERR, $e['content']);
        } else {
            if (!headers_sent()) {
                header('HTTP/1.1 ' . $e['code']);
                header('content-type: ' . $e['type']);
            }
            echo $e['content'];
        }

        exit($e['code'] > 255 ? 1 : $e['code']);
    }

    function error($errno, $errstr, $errfile, $errline, $errcontext = array()) {
        if (!($errno & error_reporting())) {
            return array();
        }

        $code = 500;
        $parts = explode('|', $errstr, 2);
        if (is_numeric($parts[0])) {
            $code = (int) $parts[0];
            $errstr = $parts[1];
        }

        if ($this->env['SAPI'] === 'cli' && $code > 255) {
            $code = 1;
        }

        if ($this->env['ERROR_MAX_LENGTH'] > -1 && strlen($errstr) > $this->env['ERROR_MAX_LENGTH']) {
            $errstr = substr($errstr, 0, $this->env['ERROR_MAX_LENGTH']) . '...';
        }

        $error = '[php error ' . $errno . '] [' . $this->env['SAPI'] . ' ' . $code . '] ' . $errstr . ' in ' . $errfile . ':' . $errline;

        if ($this->env['LOG_ERRORS']) {
            $this->log($error, $this->env['ERROR_LOG_FILE']);
        }

        if ($errno & $this->env['ERROR_NON_FATAL']) {
            return array();
        }

        if ($this->env['SHOW_ERRORS']) {
            $error .= "\n\n" . 'Stack trace: ' . "\n";

            foreach (array_merge(debug_backtrace(), isset($errcontext['ERROR_TRACE']) ? $errcontext['ERROR_TRACE'] : array()) as $i => $frame) {
                $error .= '#' . $i . ' ' . (isset($frame['file']) ? $frame['file'] : '[internal function]') . '(' . (isset($frame['line']) ? $frame['line'] : 'no line') . '): ' . (isset($frame['class']) ? $frame['class'] . (isset($frame['type']) ? $frame['type'] : '') : '') . (isset($frame['function']) ? $frame['function'] : '[unknown function]') . '(...' . (isset($frame['args']) ? count($frame['args']) : 0) . ')' . "\n";
            }
        } else {
            $error = '';
        }

        $content = '';
        $type = $this->negotiateMime(isset($errcontext['ERROR_ACCEPT']) ? $errcontext['ERROR_ACCEPT'] : '', array_keys($this->env['ERROR_TEMPLATES']));
        if ($type && file_exists($this->env['DIR_ROOT'] . $this->env['ERROR_TEMPLATES'][$type])) {
            $content = $this->template($this->env['DIR_ROOT'] . $this->env['ERROR_TEMPLATES'][$type], array('app' => $this, 'code' => $code, 'error' => $error));
        } else {
            $type = 'text/plain';
            $content = $code . '. An unexpected error occurred.' . "\n\n" . $error;
        }

        return array('content' => $content, 'code' => $code, 'type' => $type);
    }

    // Route Management

    function setRoute($method, $route, $option) {
        $handler = array($this->ROUTE_HANDLER_PIPE => array(), $this->ROUTE_HANDLER_IGNORE => array());

        $map = array('pipe' => $this->ROUTE_HANDLER_PIPE, 'ignore' => $this->ROUTE_HANDLER_IGNORE);
        foreach ($map as $key => $value) {
            if (isset($option[$key])) {
                foreach ($option[$key] as $tmpUnit) {
                    $handler[$value][] = $tmpUnit === '--global' && $key === 'ignore' ? -1 : $this->unit[$tmpUnit][$this->UNIT_LIST];
                }
            }
        }

        $node = &$this->routes[$method];
        $routeSegments = explode('/', trim($route, '/'));
        foreach ($routeSegments as $segment) {
            if (!isset($node[$segment])) {
                $node[$segment] = array();
            }
            $node = &$node[$segment];
        }

        if (isset($node[$this->ROUTE_HANDLER])) {
            trigger_error('500|Duplicate route detected: ' . $route, E_USER_WARNING);
            return;
        }

        $node[$this->ROUTE_HANDLER] = $handler;
    }

    function groupRoute($group, $method, $route, $option = array()) {
        $option['pipe'] = array_merge(isset($group['pipe_prepend']) ? $group['pipe_prepend'] : array(), isset($option['pipe']) ? $option['pipe'] : array(), isset($group['pipe_append']) ? $group['pipe_append'] : array());
        $option['ignore'] = array_merge(isset($group['ignore']) ? $group['ignore'] : array(), isset($option['ignore']) ? $option['ignore'] : array());
        $this->setRoute($method, $route, $option);
    }

    function setPipes($pipes) {
        foreach ($pipes as $key => $p) {
            foreach ($p as $unit) {
                $this->pipes[$key][] = $this->unit[$unit][$this->UNIT_LIST];
            }
        }
    }

    function resolveRoute($method, $route) {
        if (strlen($route) > 32640) {
            return array('pipe' => array_merge($this->pipes['prepend'], $this->pipes['append']), 'param' => array(), 'error' => '414|URI too long (max 32640 bytes): ' . $route);
        }

        if (!isset($this->routes[$method])) {
            return array('pipe' => array_merge($this->pipes['prepend'], $this->pipes['append']), 'param' => array(), 'error' => '405|Method not allowed: ' . $method . ' ' . $route);
        }

        $current = $this->routes[$method];
        $param = array();
        $routeSegments = explode('/', $route, 129);
        $foundSegment = false;
        $last = count($routeSegments) - 1;

        if ($last === 128) {
            unset($routeSegments[$last--]);
        }

        foreach ($routeSegments as $index => $routeSegment) {
            if ($routeSegment === '' && !(!$foundSegment && $last === $index)) {
                continue;
            }

            $foundSegment = true;

            if (strlen($routeSegment) > 255) {
                return array('pipe' => array_merge($this->pipes['prepend'], $this->pipes['append']), 'param' => array(), 'error' => '400|Route segment too long (max 255 chars): ' . $routeSegment);
            }

            if (isset($current[$routeSegment])) {
                $current = $current[$routeSegment];
                continue;
            }

            $matched = false;

            foreach ($current as $key => $value) {
                if ($key && $key[0] === ':') {
                    list($none, $paramName, $paramModifier, $paramRegex) = explode(':', $key, 4);
                    if ($paramModifier === '*') {
                        foreach (array_slice($routeSegments, $index) as $v) {
                            $param[$paramName][] = rawurldecode($v);
                        }
                        $current = $value;
                        if (isset($current[$this->ROUTE_HANDLER])) {
                            break 2;
                        }
                        $matched = true;
                        break;
                    }
                    $matches = array($routeSegment);
                    if ($paramRegex === '' || preg_match('/' . $paramRegex . '/', $routeSegment, $matches)) {
                        foreach ($matches as $k => $v) {
                            $matches[$k] = rawurldecode($v);
                        }
                        $param[$paramName] = count($matches) === 1 ? $matches[0] : $matches;
                        $current = $value;
                        $matched = true;
                        break;
                    }
                }
            }

            if (!$matched) {
                return array('pipe' => array_merge($this->pipes['prepend'], $this->pipes['append']), 'param' => array(), 'error' => '404|Route not found: ' . $method . ' ' . $route);
            }
        }

        while (!isset($current[$this->ROUTE_HANDLER])) {
            $matched = false;

            foreach ($current as $key => $value) {
                if ($key && $key[0] === ':') {
                    list($none, $paramName, $paramModifier) = explode(':', $key, 4);
                    if ($paramModifier === '*' || $paramModifier === '?' || (($pos = strpos($paramModifier, '=')) !== false && ($param[$paramName] = substr($paramModifier, $pos + 1)))) {
                        $current = $value;
                        $matched = true;
                        break;
                    }
                }
            }

            if (!$matched) {
                return array('pipe' => array_merge($this->pipes['prepend'], $this->pipes['append']), 'param' => array(), 'error' => '404|Route not found: ' . $method . ' ' . $route);
            }
        }

        if (!isset($current[$this->ROUTE_HANDLER])) {
            return array('pipe' => array_merge($this->pipes['prepend'], $this->pipes['append']), 'param' => array(), 'error' => '404|Route not found: ' . $method . ' ' . $route);
        }

        $finalPipes = array();

        $ignore = array_flip($current[$this->ROUTE_HANDLER][$this->ROUTE_HANDLER_IGNORE]);

        $pipeGroup = isset($ignore[-1]) ? array(&$current[$this->ROUTE_HANDLER][$this->ROUTE_HANDLER_PIPE]) : array(&$this->pipes['prepend'], &$current[$this->ROUTE_HANDLER][$this->ROUTE_HANDLER_PIPE], &$this->pipes['append']);

        foreach ($pipeGroup as $pipes) {
            foreach ($pipes as $pipe) {
                if (!isset($ignore[$pipe])) {
                    $finalPipes[] = $pipe;
                }
            }
        }

        return array('pipe' => $finalPipes, 'param' => $param);
    }

    // Request Handling

    function process($input, $output) {
        if ($input->source !== 'cli' && !$this->env['ROUTE_REWRITE']) {
            foreach (isset($input->query['route']) && $input->query['route'] ? explode('/', $input->query['route'][0] === '/' ? substr($input->query['route'], 1) : $input->query['route']) : array() as $routePart) {
                $input->route .= '/' . rawurlencode($routePart);
            }
        } else {
            $input->route = ($pos = strpos($input->uri, '?')) !== false ? substr($input->uri, 0, $pos) : $input->uri;
        }

        $route = $this->resolveRoute($input->method, $input->route);

        $input->param = $route['param'];

        foreach ($route['pipe'] as $p) {
            $p = $this->makeUnit($this->unitList[$p]);
            list($input, $output, $success) = $p->process($input, $output);
            if (!$success) {
                break;
            }
        }

        if (isset($route['error'])) {
            trigger_error($route['error'], E_USER_WARNING);
            return array($input, $output, false);
        }

        return array($input, $output, true);
    }

    // Unit Management

    function autoAddUnit($path, $option) {
        if (!isset($option['depth'])) {
            $option['depth'] = 0;
        }

        if (!isset($option['max'])) {
            $option['max'] = -1;
        }

        if (!isset($option['ignore'])) {
            $option['ignore'] = array();
        }

        if (!isset($option['namespace'])) {
            $option['namespace'] = '';
        }

        if (!isset($option['dir_as_namespace'])) {
            $option['dir_as_namespace'] = false;
        }

        if ($handle = opendir($this->env['DIR_ROOT'] . $path)) {
            while (($item = readdir($handle)) !== false) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                foreach ($option['ignore'] as $pattern) {
                    if (($pattern[0] === '?' && strpos($item, substr($pattern, 1))) || ($pattern[0] !== '?' && $pattern === $item)) {
                        continue 2;
                    }
                }

                $isDir = is_dir($this->env['DIR_ROOT'] . $path . $item);

                if ($isDir && ($option['max'] === -1 || $option['max'] > $option['depth'])) {
                    $subOption = $option;
                    $subOption['depth']++;
                    $subOption['namespace'] .= $item . '\\';
                    $this->autoAddUnit($path . $item . '/', $subOption);
                } elseif (!$isDir && substr($item, -4) === '.php') {
                    $this->addUnit(($option['dir_as_namespace'] ? $option['namespace'] : '') . substr($item, 0, -4), $path);
                }
            }
            closedir($handle);
        }
    }

    function addUnit($unit, $path = '') {
        $pathListIndex = isset($this->pathListCache[$path]) ? $this->pathListCache[$path] : array_search($path, $this->pathList);
        if ($pathListIndex === false) {
            $pathListIndex = $this->pathListIndex;
            $this->pathList[$this->pathListIndex] = $path;
            $this->pathListCache[$path] = $this->pathListIndex;
            ++$this->pathListIndex;
        }

        $pos = strrpos($unit, '\\');
        $file = $pos === false ? $unit : substr($unit, $pos + 1);

        if (isset($this->unit[$unit]) && ($newFile = $path . $file) !== ($oldFile = $this->pathList[$this->unit[$unit][$this->UNIT_PATH]] . $this->unit[$unit][$this->UNIT_FILE])) {
            trigger_error('500|Duplicate unit detected: ' . $unit . ' from ' . $newFile . '.php and ' . $oldFile . '.php', E_USER_WARNING);
            return;
        }

        $this->unit[$unit] = array($this->unitListIndex, $pathListIndex, $file, array(), array(), false);
        $this->unitList[$this->unitListIndex] = $unit;
        ++$this->unitListIndex;
    }

    function setUnit($unit, $option) {
        $test = $this->unit[$unit];

        $map = array('args' => $this->UNIT_ARGS, 'load' => $this->UNIT_LOAD);
        foreach ($map as $key => $value) {
            if (isset($option[$key])) {
                foreach ($option[$key] as $tmpUnit) {
                    $this->unit[$unit][$value][] = $this->unit[$tmpUnit][$this->UNIT_LIST];
                }
            }
        }

        $this->unit[$unit][$this->UNIT_INST_CACHE] = isset($option['cache']) ? $option['cache'] : $this->unit[$unit][$this->UNIT_INST_CACHE];
    }

    function groupUnit($group, $unit, $option = array()) {
        $option['args'] = array_merge(isset($group['args_prepend']) ? $group['args_prepend'] : array(), isset($option['args']) ? $option['args'] : array(), isset($group['args_append']) ? $group['args_append'] : array());
        $option['load'] = array_merge(isset($group['load_prepend']) ? $group['load_prepend'] : array(), isset($option['load']) ? $option['load'] : array(), isset($group['load_append']) ? $group['load_append'] : array());
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

            if (isset($seen[$unit])) {
                trigger_error('500|Circular load detected: ' . implode(' -> ', $stack) . ' -> ' . $unit, E_USER_WARNING);
                return;
            }

            if (isset($this->unitPathCache[$unit])) {
                if (!$stack) {
                    return;
                }

                unset($seen[$previousUnit]);
                continue;
            }

            $load = $this->unit[$unit][$this->UNIT_LOAD];
            if ($load) {
                if (!isset($md[$unit])) {
                    $md[$unit] = array(0, count($load));
                }

                if ($md[$unit][1] > $md[$unit][0]) {
                    $stack[] = $unit;
                    $stack[] = $this->unitList[$load[$md[$unit][0]]];
                    ++$md[$unit][0];
                    continue;
                }
                unset($md[$unit]);
            }

            unset($seen[$previousUnit]);

            require $this->env['DIR_ROOT'] . $this->pathList[$this->unit[$unit][$this->UNIT_PATH]] . $this->unit[$unit][$this->UNIT_FILE] . '.php';
            $this->unitPathCache[$unit] = true;
        }
    }

    function makeUnit($unit, $new = false) {
        $stack = array($unit);
        $seen = array();
        $md = array();
        $resolvedArgs = array();
        $class = null;

        while ($stack) {
            $unit = array_pop($stack);
            $previousUnit = end($stack);
            $seen[$previousUnit] = true;

            if (isset($seen[$unit])) {
                trigger_error('Circular args detected: ' . implode(' -> ', $stack) . ' -> ' . $unit, E_USER_WARNING);
                return;
            }

            $cache = !$new && $this->unit[$unit][$this->UNIT_INST_CACHE];
            if ($cache && isset($this->unitInstCache[$unit])) {
                if (!$stack) {
                    return $this->unitInstCache[$unit];
                }

                unset($seen[$previousUnit]);
                $resolvedArgs[$previousUnit][] = $this->unitInstCache[$unit];
                continue;
            }

            $args = $this->unit[$unit][$this->UNIT_ARGS];
            if ($args) {
                if (!isset($md[$unit])) {
                    $md[$unit] = array(0, count($args));
                }

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

            $class = new $unit();
            if (isset($resolvedArgs[$unit])) {
                $class->args($resolvedArgs[$unit]);
                unset($resolvedArgs[$unit]);
            }

            if ($cache) {
                $this->unitInstCache[$unit] = $class;
            }

            $resolvedArgs[$previousUnit][] = $class;
        }

        return $class;
    }

    function resetUnit($unit) {
        if ($this->unit[$unit][$this->UNIT_INST_CACHE]) {
            unset($this->unitInstCache[$unit]);
        }
    }

    // Utility Functions

    function clear($property) {
        unset($this->{$property});
    }

    function dir($s) {
        return str_replace('\\', '/', $s);
    }

    function dirRoot($s = '') {
        return $this->env['DIR_ROOT'] . $s;
    }

    function dirWeb($s = '') {
        return $this->env['DIR_ROOT'] . $this->env['DIR_WEB'] . $s;
    }

    function urlRoute($s, $param = array()) {
        $base = $this->env['URL_ROOT'] . ($this->env['ROUTE_REWRITE'] ? '' : $this->env['ROUTE_FILE'] . '?route=/');
        if (!$this->env['ROUTE_REWRITE'] && strpos($base, '?') !== false) {
            $s = str_replace('?', '&', $s);
        }
        return $base . ($param ? strtr($s, $param) : $s);
    }

    function urlRoot($s = '') {
        return $this->env['URL_ROOT'] . $s;
    }

    function urlWeb($s, $param = array()) {
        return $this->env['URL_WEB'] . ($param ? strtr($s, $param) : $s);
    }

    function strSlug($s) {
        return preg_replace('/[^a-z0-9]+/', '-', strtolower($s));
    }

    function template($file, $data = array()) {
        ob_start();
        require $file;
        return ob_get_clean();
    }

    function htmlEncode($s) {
        return isset($s) ? htmlspecialchars($s, ENT_QUOTES) : '';
    }

    function negotiateMime($accept, $offers) {
        $prefs = array();
        foreach (explode(',', $accept) as $type) {
            $parts = explode(';', trim($type));
            $aType = trim(array_shift($parts));

            $q = 1.0;
            foreach ($parts as $p) {
                $p = explode('=', trim($p));
                if (isset($p[1]) && strtolower(trim($p[0])) === 'q') {
                    $q = (float) trim($p[1]);
                }
            }
            if ($q > 0) {
                $prefs[$aType] = $q;
            }
        }
        arsort($prefs);
        foreach (array_keys($prefs) as $p) {
            foreach ($offers as $o) {
                if ($p === $o || $p === '*/*' || (substr($p, -2) === '/*' && strpos($o, substr($p, 0, -1)) === 0)) {
                    return $o;
                }
            }
        }
    }

    function write($file, $string, $append = false) {
        if ($fp = fopen($file, $append ? 'ab' : 'wb')) {
            fwrite($fp, (string) $string);
            fclose($fp);
        }
    }

    function read($file) {
        if ($fp = fopen($file, 'rb')) {
            $chunks = array();
            while (!feof($fp)) {
                $chunks[] = fread($fp, 8192);
            }
            fclose($fp);
            return implode('', $chunks);
        }
        return false;
    }

    function log($msg, $file) {
        $mt = explode(' ', microtime());
        $micro = (float) $mt[0];
        $time = (int) $mt[1];

        $ext = '';
        $pos = strrpos($file, '.');
        if ($pos !== false) {
            $ext = substr($file, $pos);
            $file = substr($file, 0, $pos);
        }

        $logDir = $this->env['DIR_ROOT'] . $this->env['DIR_LOG'];
        $logFile = $logDir . $file . $ext;

        $this->write($logFile, '[' . date('Y-m-d H:i:s', $time) . '.' . sprintf('%06d', $micro * 1000000) . '] ' . $msg . "\n", true);

        if (filesize($logFile) >= $this->env['LOG_SIZE_LIMIT_MB'] * 1048576) {
            $newLogFile = $logDir . '/' . $file . '_' . date('Y-m-d_H-i-s') . $ext;
            rename($logFile, $newLogFile);
        }

        $timestampFile = $this->env['DIR_ROOT'] . $this->env['DIR_LOG_TIMESTAMP'] . $file . '_last-log-cleanup-timestamp.txt';
        $lastCleanup = file_exists($timestampFile) ? (int) $this->read($timestampFile) : 0;

        if ($time - $lastCleanup >= $this->env['LOG_CLEANUP_INTERVAL_DAYS'] * 86400) {
            $prefix = $file . '_';
            $prefixLen = strlen($prefix);
            $logFilesMTime = array();
            if ($handle = opendir($logDir)) {
                while (($item = readdir($handle)) !== false) {
                    if ($item === '.' || $item === '..' || substr($item, 0, $prefixLen) !== $prefix) {
                        continue;
                    }
                    $lf = $logDir . $item;
                    $lfmtime = filemtime($lf);
                    if ($time - $lfmtime > $this->env['LOG_RETENTION_DAYS'] * 86400) {
                        unlink($lf);
                        continue;
                    }
                    $logFilesMTime[$lf] = $lfmtime;
                }
                closedir($handle);
            }

            asort($logFilesMTime);
            $logFiles = array_keys($logFilesMTime);

            if (count($logFiles) > $this->env['MAX_LOG_FILES']) {
                $maxIndex = count($logFiles) - $this->env['MAX_LOG_FILES'];
                for ($i = 0; $maxIndex > $i; $i++) {
                    unlink($logFiles[$i]);
                }
            }

            $this->write($timestampFile, $time);
        }
    }
}
