<?php
/**
 * Copyright 2025 Lloyd Miles M. Bersabe
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

init();

function init() {
    $os = strtoupper(PHP_OS);
    if (strpos($os, 'WIN') !== false) {
        define('DS', '\\');
        define('EOL', "\r\n");
    } elseif (strpos($os, 'DAR') !== false) {
        define('DS', '/');
        define('EOL', "\r");
    } else {
        define('DS', '/');
        define('EOL', "\n");
    }
}

function d($var) {
    header('Content-Type: text/plain');
    echo var_export($var, true);
}

class Request {
    var $server, $data, $uri, $method, $baseUrl, $params, $get, $post, $files, $cookies, $argv, $argc, $cli;

    function __construct() {
        $this->init();
    }

    function init() {
        $this->server = $_SERVER;
        $this->data = array();
        $this->uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $this->method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
        $this->baseUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http') . '://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '127.0.0.1') . '/';
        $this->params = array();
        $this->get = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
        $this->argv = isset($GLOBALS['argv']) ? $GLOBALS['argv'] : array();
        $this->argc = isset($GLOBALS['argc']) ? $GLOBALS['argc'] : array();
        $this->cli = array('positional' => array(), 'options' => array());
        if ($this->method === '') {
            for ($i = 0; $this->argc > $i; $i++) {
                $arg = $this->argv[$i];
                if (strpos($arg, '--') === 0) {
                    $eq = strpos($arg, '=');
                    if ($eq !== false) {
                        $this->cli['options'][substr($arg, 2, $eq - 2)] = trim(substr($arg, $eq + 1), '"\'');
                    } else {
                        $this->cli['options'][substr($arg, 2)] = true;
                    }
                } elseif (strpos($arg, '-') !== 0) {
                    $this->cli['positional'][] = $arg;
                }
            }
        }
    }

    function setData($key, $value) {
        $this->data[$key] = $value;
    }

    function getData($key) {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }
}

class Response {
    var $headers, $code, $type, $content;

    function __construct() {
        $this->init();
    }

    function init() {
        $this->headers = array();
        $this->code = 200;
        $this->type = 'text/html';
        $this->content = '';
    }

    function send() {
        if (!headers_sent()) {
            header('HTTP/1.1 ' . $this->code);

            foreach ($this->headers as $key => $value) {
                header($key . ': ' . $value);
            }

            if (!isset($this->headers['Content-Type'])) {
                header('Content-Type: ' . $this->type);
            }
        }

        exit(isset($this->headers['Location']) ? '' : $this->content);
    }

    function html($html, $data) {
        ob_start();
        require($html);
        $htmlData = ob_get_clean();

        return $htmlData;
    }

    function json($data) {
        $this->type = 'application/json';
        $jsonData = json_encode($data);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $jsonData = '{"error": "Unable to encode data"}';
        }

        return $jsonData;
    }

    function redirect($url) {
        $this->headers['Location'] = $url;
    }
}

class App {
    var $ENV = array();

    var $UNIT_LIST_INDEX = 0;
    var $UNIT_PATH = 1;
    var $UNIT_FILE = 2;
    var $UNIT_LOAD = 3;
    var $UNIT_CLASS = 4;
    var $UNIT_CLASS_ARGS = 5;
    var $UNIT_CLASS_CACHE = 6;

    var $CACHE_CLASS = 0;
    var $CACHE_PATH = 1;

    var $routes = array();
    var $pipes = array('prepend' => array(), 'append' => array());
    var $unit = array();
    var $unitList = array();
    var $unitListIndex = 0;
    var $pathList = array();
    var $pathListIndex = 0;

    var $cache = array();
    var $pathListCache = array();

    var $isDispatch = false;

    // Application Setup

    function __construct($args) {
        list($request, $response) = $args;

        $this->unit = array(
            'App' => array(0, null, null, array(), 'App', array(1, 2), true),
            'Request' => array(1, null, null, array(), 'Request', array(), true),
            'Response' => array(2, null, null, array(), 'Response', array(), true),
        );

        $this->unitList = array('App', 'Request', 'Response');
        $this->unitListIndex = 3;

        $this->cache = array(
            'App' => array($this, true),
            'Request' => array($request, true),
            'Response' => array($response, true),
        );

        $this->ENV['BASE_URL'] = $request->baseUrl;
    }

    function init() {
        $this->ENV['DIR'] = dirname(__FILE__) . DS;

        $this->ENV['DIR_LOG'] = isset($this->ENV['DIR_LOG']) ? $this->ENV['DIR_LOG'] : '';
        $this->ENV['DIR_LOG_TIMESTAMP'] = isset($this->ENV['DIR_LOG_TIMESTAMP']) ? $this->ENV['DIR_LOG_TIMESTAMP'] : '';
        $this->ENV['DIR_RES'] = isset($this->ENV['DIR_RES']) ? $this->ENV['DIR_RES'] : '';
        $this->ENV['DIR_WEB'] = isset($this->ENV['DIR_WEB']) ? $this->ENV['DIR_WEB'] : '';
        $this->ENV['DIR_SRC'] = isset($this->ENV['DIR_SRC']) ? $this->ENV['DIR_SRC'] : '';

        $this->ENV['ROUTE_FILE'] = isset($this->ENV['ROUTE_FILE']) ? $this->ENV['ROUTE_FILE'] : 'index.php';
        $this->ENV['ROUTE_REWRITE'] = isset($this->ENV['ROUTE_REWRITE']) ? (bool) $this->ENV['ROUTE_REWRITE'] : false;
        $this->ENV['URL_EXTRA'] = $this->ENV['ROUTE_REWRITE'] ? '' : ($this->ENV['ROUTE_FILE'] . '?route=/');
        
        $this->ENV['URL_DIR_WEB'] = isset($this->ENV['URL_DIR_WEB']) ? $this->ENV['URL_DIR_WEB'] : '';

        $this->ENV['ERROR_HTML_FILE'] = isset($this->ENV['ERROR_HTML_FILE']) ? $this->ENV['ERROR_HTML_FILE'] : '';
        $this->ENV['SHOW_ERRORS'] = (bool) $this->ENV['SHOW_ERRORS'];

        $this->ENV['LOG_SIZE_LIMIT_MB'] = isset($this->ENV['LOG_SIZE_LIMIT_MB']) && (int) $this->ENV['LOG_SIZE_LIMIT_MB'] > 0 ? (int) $this->ENV['LOG_SIZE_LIMIT_MB'] : 5;
        $this->ENV['LOG_CLEANUP_INTERVAL_DAYS'] = isset($this->ENV['LOG_CLEANUP_INTERVAL_DAYS']) && (int) $this->ENV['LOG_CLEANUP_INTERVAL_DAYS'] > 0 ? (int) $this->ENV['LOG_CLEANUP_INTERVAL_DAYS'] : 1;
        $this->ENV['LOG_RETENTION_DAYS'] = isset($this->ENV['LOG_RETENTION_DAYS']) && (int) $this->ENV['LOG_RETENTION_DAYS'] > 0 ? (int) $this->ENV['LOG_RETENTION_DAYS'] : 7;
        $this->ENV['MAX_LOG_FILES'] = isset($this->ENV['MAX_LOG_FILES']) && (int) $this->ENV['MAX_LOG_FILES'] > 0 ? (int) $this->ENV['MAX_LOG_FILES'] : 10;
    }

    function setEnv($key, $value) {
        $this->ENV[$key] = $value;
    }

    function setEnvs($keys) {
        foreach ($keys as $key => $value) {
            $this->ENV[$key] = $value;
        }
    }

    function getEnv($key) {
        return isset($this->ENV[$key]) ? $this->ENV[$key] : null;
    }

    function setIni($key, $value) {
        if (ini_set($key, $value) === false) {
            $this->log('Failed to set ini setting: ' . $key, 'app/error');
        }
    }

    function setInis($keys) {
        foreach ($keys as $key => $value) {
            if (ini_set($key, $value) === false) {
                $this->log('Failed to set ini setting: ' . $key, 'app/error');
            }
        }
    }

    // Config Management

    function saveConfig($file) {
        $configFile = $this->ENV['DIR'] . $file . '.dat';
        file_put_contents($configFile, serialize(array(
            'routes' => unserialize(serialize($this->routes)),
            'pipes' => unserialize(serialize($this->pipes)),
            'unit' => unserialize(serialize($this->unit)),
            'unit_list' => unserialize(serialize($this->unitList)),
            'unit_list_index' => unserialize(serialize($this->unitListIndex)),
            'path_list' => unserialize(serialize($this->pathList)),
            'path_list_index' => unserialize(serialize($this->pathListIndex))
        )));

        echo('File created: ' . $configFile . EOL);
    }

    function loadConfig($file) {
        $configFile = $this->ENV['DIR'] . $file . '.dat';
        $data = unserialize(file_get_contents($configFile));
        $this->routes = $data['routes'];
        $this->pipes = $data['pipes'];
        $this->unit = $data['unit'];
        $this->unitList = $data['unit_list'];
        $this->unitListIndex = $data['unit_list_index'];
        $this->pathList = $data['path_list'];
        $this->pathListIndex = $data['path_list_index'];
    }

    // Error Management

    function triggerError($message, $no = 500) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $this->error(1, ($no . '|' . $message), $trace[0]['file'], $trace[0]['line']);
    }

    function error($errno, $errstr, $errfile, $errline) {
        $this->handleError($errno, $errstr, $errfile, $errline, true);
    }

    function shutdown() {
        $error = error_get_last();
        if ($error !== null) {
            if (in_array($error['type'], array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE))) {
                $this->handleError($error['type'], $error['message'], $error['file'], $error['line'], false);
            }
        }
    }

    function handleError($errno, $errstr, $errfile, $errline, $enableStackTrace) {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        $httpCode = 500;
        $type = 'text/html';
        $content = '';

        $parts = explode('|', $errstr, 2);
        if (is_numeric($parts[0])) {
            $httpCode = (int) $parts[0];
            $errstr = $parts[1];
        }

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $type = 'application/json';
            $content = $this->ENV['SHOW_ERRORS'] ? '{"error":true,"message":"[php error ' . $errno . '] [http ' . $httpCode . '] ' . $errstr . ' in ' . $errfile . ':' . $errline . '"}' : '{"error":true,"message":"An unexpected error occurred. Please try again later."}';
        } else {
            if ($this->ENV['SHOW_ERRORS'] || empty($_SERVER['REQUEST_METHOD'])) {
                $traceOutput = '';
                if ($enableStackTrace) {
                    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                    $traceOutput = 'Stack trace: ' . EOL;
                    foreach ($trace as $i => $frame) {
                        if (1 > $i) { continue; }
                        $traceOutput .= '#' . ($i - 1) . ' ';
                        $traceOutput .= isset($frame['file']) ? $frame['file'] : '[internal function]';
                        $traceOutput .= ' (' . (isset($frame['line']) ? $frame['line'] : 'no line') . '): ';
                        $traceOutput .= isset($frame['class']) ? $frame['class'] . (isset($frame['type']) ? $frame['type'] : '') : '';
                        $traceOutput .= (isset($frame['function']) ? $frame['function'] . '()' : '[unknown function]') . EOL;
                    }
                }
                $type = 'text/plain';
                $content = '[php error ' . $errno . '] [http ' . $httpCode . '] ' . $errstr . ' in '. $errfile . ':' . $errline . EOL . EOL . $traceOutput;
            } else {
                $file = $this->ENV['DIR'] . $this->ENV['ERROR_HTML_FILE'];
                if (file_exists($file)) {
                    $data = array('app' => $this, 'http_code' => $httpCode);
                    ob_start();
                    include($file);
                    $content = ob_get_clean();
                } else {
                    $content = 'An unexpected error occurred. Please try again later.' . EOL; 
                }
            }
        }

        $this->log('[php error ' . $errno . '] [http ' . $httpCode . '] ' . $errstr . ' in ' . $errfile . ':' . $errline, 'app/error');

        if (!headers_sent()) {
            header('HTTP/1.1 ' . $httpCode);
            header('Content-Type: ' . $type);
        }

        exit($content);
    }

    // Route Management

    function setRoute($method, $route, $option) {
        $handler = array('_p' => array(), '_i' => array());

        $map = array('pipe' => '_p', 'ignore' => '_i');
        foreach ($map as $key => $value) {
            if (isset($option[$key])) {
                foreach ($option[$key] as $unit) {
                    if ($unit == 'global' && $key === 'ignore') {
                        $handler[$value][] = -1;
                        continue;
                    }
                    $handler[$value][] = $this->unit[$unit][$this->UNIT_LIST_INDEX];
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

        $node['_h'] = $handler;
    }

    function addRoute($group, $method, $route, $option = array()) {
        $option['pipe'] = array_merge((isset($group['pipe_prepend']) ? $group['pipe_prepend'] : array()), (isset($option['pipe']) ? $option['pipe'] : array()), (isset($group['pipe_append']) ? $group['pipe_append'] : array()));
        $option['ignore'] = array_merge((isset($group['ignore']) ? $group['ignore'] : array()), (isset($option['ignore']) ? $option['ignore'] : array()));
        $this->setRoute($method, (isset($group['prefix']) ? $group['prefix'] : '') . $route, $option);
    }

    function setPipes($pipes) {
        foreach ($pipes as $key => $p) {
            foreach ($p as $unit) {
                $this->pipes[$key][] = $this->unit[$unit][$this->UNIT_LIST_INDEX];
            }
        }
    }

    function resolveRoute($method, $path) {
        if (!isset($this->routes[$method])) {
            return array();
        }

        $current = $this->routes[$method];
        $params = array();
        $pathSegments = explode('/', trim($path, '/'));
        $decrement = 0;

        foreach ($pathSegments as $index => $pathSegment) {
            if ($pathSegment === '' && $index != 0) {
                ++$decrement;
                if ($decrement > 20) {
                    return array();
                }
                continue;
            }

            $index -= $decrement;

            if (strlen($pathSegment) > 255) {
                return array();
            }

            if (isset($current[$pathSegment])) {
                $current = $current[$pathSegment];
                continue;
            }

            $matched = false;

            foreach ($current as $key => $value) {
                if (strpos($key, '{') !== false && strpos($key, '}') !== false) {
                    $param = trim($key, '{}');
                    $paramParts = explode(':', $param, 2);
                    $paramName = $paramParts[0];
                    $paramRegex = (isset($paramParts[1])) ? $paramParts[1] : '.+';
                    $paramModifier = substr($paramName, -1);
                    if ($paramModifier === '*') {
                        if (!isset($value['_h'])) {
                            return array();
                        }
                        $params[substr($paramName, 0, -1)] = array_slice($pathSegments, $index);
                        $current = $value;
                        break 2;
                    }
                    if ($paramModifier === '?' && preg_match('/' . $paramRegex . '/', $pathSegment, $matches)) {
                        foreach ($matches as $k => $v) $matches[$k] = urldecode($v);
                        $params[substr($paramName, 0, -1)] = (count($matches) === 1) ? $matches[0] : $matches;
                        $current = $value;
                        $matched = true;
                        break;
                    }
                    if (preg_match('/' . $paramRegex . '/', $pathSegment, $matches)) {
                        foreach ($matches as $k => $v) $matches[$k] = urldecode($v);
                        $params[$paramName] = (count($matches) === 1) ? $matches[0] : $matches;
                        $current = $value;
                        $matched = true;
                        break;
                    }
                }
            }

            if (!$matched) {
                return array();
            }
        }

        while (!isset($current['_h'])) {
            $matched = false;

            foreach ($current as $key => $value) {
                if (strpos($key, '{') !== false && strpos($key, '}') !== false) {
                    $param = trim($key, '{}');
                    $paramParts = explode(':', $param, 2);
                    $paramModifier = substr($paramParts[0], -1);
                    $current = $value;
                    if ($paramModifier === '*') {
                        if (!isset($value['_h'])) {
                            return array();
                        }
                        break 2;
                    }
                    if ($paramModifier === '?') {
                        $matched = true;
                        break;
                    }
                }
            }

            if (!$matched) {
                return array();
            }
        }

        if (!isset($current['_h'])) {
            return array();
        }

        $finalPipes = array();

        $ignore = array_flip($current['_h']['_i']);

        list($pipes, $length) = isset($ignore[-1]) ? array(array(&$current['_h']['_p']), 1) : array(array(&$this->pipes['prepend'], &$current['_h']['_p'], &$this->pipes['append']), 3);

        for ($i = 0; $length > $i; $i++) {
            foreach ($pipes[$i] as $pipe) {
                if (!isset($ignore[$pipe])) {
                    $finalPipes[] = $pipe;
                }
            }
        }

        return array('pipe' => $finalPipes, 'params' => $params);
    }

    // Request Handling

    function dispatch() {
        $response = $this->cache['Response'][$this->CACHE_CLASS];
        if ($this->isDispatch) {
            return $response;
        }

        $this->isDispatch = true;
        $request = $this->cache['Request'][$this->CACHE_CLASS];

        $path = '';
        if ($request->method === '') {
            $count = count($request->cli['positional']);
            for ($i = 1; $count > $i; $i++) {
                $path .= urlencode($request->cli['positional'][$i]) . '/';
            }
            $request->method = (isset($request->cli['options']['method']) && $request->cli['options']['method'] !== true) ? $request->cli['options']['method'] : '';
        } elseif ($this->ENV['ROUTE_REWRITE']) {
            $parseUrl = parse_url($request->uri);
            $path = ($parseUrl === false) ? $request->uri : $parseUrl['path'];
        } else {
            $path = isset($request->get['route']) ? $request->get['route'] : '';
        }

        $route = $this->resolveRoute($request->method, $path);

        if ($route === array()) {
            $this->triggerError('Route not found: ' . $request->method . ' ' . $path, 404);
            exit();
        }

        $request->params = $route['params'];
        foreach ($route['pipe'] as $p) {
            $p = $this->getClass($this->unitList[$p]);
            list($request, $response) = $p->pipe($request, $response);
        }

        return $response;
    }

    // Class Management

    function autoSetUnit($path, $option) {
        $option = array(
            'depth' => isset($option['depth']) ? $option['depth'] : 0,
            'max' => isset($option['max']) ? $option['max'] : -1,
            'ignore' => isset($option['ignore']) ? $option['ignore'] : array(),
            'namespace' => isset($option['namespace']) ? $option['namespace'] : '',
            'dir_as_namespace' => isset($option['dir_as_namespace']) ? $option['dir_as_namespace'] : false,
        );

        if ($dirHandle = opendir($this->ENV['DIR'] . $path)) {
            while (($file = readdir($dirHandle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                foreach ($option['ignore'] as $pattern) {
                    if (preg_match('/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/i', $file)) {
                        continue 2;
                    }
                }

                if (($option['max'] === -1 || $option['max'] > $option['depth']) && is_dir($this->ENV['DIR'] . $path . $file)) {
                    ++$option['depth'];
                    $namespace = $option['namespace'];
                    $option['namespace'] .= ($file . '\\');
                    $this->autoSetUnit($path . $file . DS, $option);
                    $option['namespace'] = $namespace;
                    --$option['depth'];
                } else if (substr($file, -4) === '.php') {
                    $unitFile = substr($file, 0, -4);
                    $unit = str_replace('\\', '.', ($option['namespace'] . $unitFile));
                    $unitClass = ($option['dir_as_namespace']) ? ($option['namespace'] . $unitFile) : $unitFile;                    
                    $pathListIndex = isset($this->pathListCache[$path]) ? $this->pathListCache[$path] : array_search($path, $this->pathList);
                    if ($pathListIndex === false) {
                        $pathListIndex = $this->pathListIndex;
                        $this->pathList[$this->pathListIndex] = $path;
                        ++$this->pathListIndex;
                        $this->pathListCache[$path] = $pathListIndex;
                    }

                    $this->unit[$unit] = array($this->unitListIndex, $pathListIndex, $unitFile, array(), $unitClass, array(), false);
                    $this->unitList[$this->unitListIndex] = $unit;
                    ++$this->unitListIndex;
                }
            }
            closedir($dirHandle);
        }
    }

    function setUnit($unit, $option) {
        $test = $this->unit[$unit];

        if (isset($option['args'])) {
            foreach ($option['args'] as $arg) {
                $this->unit[$unit][$this->UNIT_CLASS_ARGS][] = $this->unit[$arg][$this->UNIT_LIST_INDEX];
            }
        }

        if (isset($option['load'])) {
            foreach ($option['load'] as $load) {
                $this->unit[$unit][$this->UNIT_LOAD][] = $this->unit[$load][$this->UNIT_LIST_INDEX];
            }
        }

        $this->unit[$unit][$this->UNIT_CLASS_CACHE] = (isset($option['cache']) ? $option['cache'] : $this->unit[$unit][$this->UNIT_CLASS_CACHE]);
    }

    function addUnit($group, $unit, $option = array()) {
        $option['args'] = array_merge((isset($group['args_prepend']) ? $group['args_prepend'] : array()), (isset($option['args']) ? $option['args'] : array()), (isset($group['args_append']) ? $group['args_append'] : array()));
        $option['load'] = array_merge((isset($group['load_prepend']) ? $group['load_prepend'] : array()), (isset($option['load']) ? $option['load'] : array()), (isset($group['load_append']) ? $group['load_append'] : array()));
        $option['cache'] = isset($option['cache']) ? $option['cache'] : (isset($group['cache']) ? $group['cache'] : false);
        $this->setUnit($unit, $option);
    }

    function newClass($unit) {
        $mode = $this->unit[$unit][$this->UNIT_CLASS_CACHE];
        $this->unit[$unit][$this->UNIT_CLASS_CACHE] = false;
        $class = $this->getClass($unit);
        $this->unit[$unit][$this->UNIT_CLASS_CACHE] = $mode;
        return $class;
    }

    function resetClass($unit) {
        $this->cache[$unit][$this->CACHE_CLASS] = null;
    }

    function getClass($unit) {
        $INDEX = 0;
        $COUNT = 1;

        $stack = array($unit);
        $md = array();
        $resolved = array();
        $class = null;

        while (!empty($stack)) {
            $unit = array_pop($stack);
            $unitParent = end($stack);
            $stackSet[$unitParent] = true;

            if (isset($stackSet[$unit])) {
                $this->triggerError('Circular dependency found: ' . implode(' -> ', $stack) . ' -> ' . $unit, 500);
                exit();
            }

            $cache = $this->unit[$unit][$this->UNIT_CLASS_CACHE];
            if ($cache && isset($this->cache[$unit][$this->CACHE_CLASS])) {
                if (empty($stack)) {
                    return $this->cache[$unit][$this->CACHE_CLASS];
                }
                unset($stackSet[$unitParent]);
                $resolved[$unitParent][] = $this->cache[$unit][$this->CACHE_CLASS];
                continue;
            }

            if (!isset($md[$unit])) {
                $md[$unit] = array(0, count($this->unit[$unit][$this->UNIT_CLASS_ARGS]));
            }

            if ($md[$unit][$COUNT] > $md[$unit][$INDEX]) {
                $stack[] = $unit;
                $stack[] = $this->unitList[$this->unit[$unit][$this->UNIT_CLASS_ARGS][$md[$unit][$INDEX]]];
                ++$md[$unit][$INDEX];
                continue;
            }

            unset($md[$unit]);

            unset($stackSet[$unitParent]);

            $this->loadUnit($unit);

            $class = $this->unit[$unit][$this->UNIT_CLASS];
            $class = new $class(isset($resolved[$unit]) ? $resolved[$unit] : array());
            unset($resolved[$unit]);
            if ($cache) {
                $this->cache[$unit][$this->CACHE_CLASS] = $class;
            }

            $resolved[$unitParent][] = $class;
        }

        return $class;
    }

    function loadUnit($unit) {
        $INDEX = 0;
        $COUNT = 1;

        $stack = array($unit);
        $md = array();

        while (!empty($stack)) {
            $unit = array_pop($stack);
            $unitParent = end($stack);
            $stackSet[$unitParent] = true;

            if (isset($stackSet[$unit])) {
                $this->triggerError('Circular load found: ' . implode(' -> ', $stack) . ' -> ' . $unit, 500);
                exit();
            }

            if (isset($this->cache[$unit][$this->CACHE_PATH])) {
                if (empty($stack)) {
                    return;
                }
                unset($stackSet[$unitParent]);
                continue;
            }

            if (!isset($md[$unit])) {
                $md[$unit] = array(0, count($this->unit[$unit][$this->UNIT_LOAD]));
            }

            if ($md[$unit][$COUNT] > $md[$unit][$INDEX]) {
                $stack[] = $unit;
                $stack[] = $this->unitList[$this->unit[$unit][$this->UNIT_LOAD][$md[$unit][$INDEX]]];
                ++$md[$unit][$INDEX];
                continue;
            }

            unset($md[$unit]);

            unset($stackSet[$unitParent]);

            require($this->ENV['DIR'] . $this->pathList[$this->unit[$unit][$this->UNIT_PATH]] . $this->unit[$unit][$this->UNIT_FILE] . '.php');
            $this->cache[$unit][$this->CACHE_PATH] = true;
        }
    }

    // Utility Functions

    function unsetProperty($name) {
        unset($this-> {$name});
    }

    function path($option, $path = '') {
        switch ($option) {
            case 'root':
                return $this->ENV['DIR'] . $path;
            case 'res':
                return $this->ENV['DIR'] . $this->ENV['DIR_RES'] . $path;
            case 'web':
                return $this->ENV['DIR'] . $this->ENV['DIR_WEB'] . $path;
            case 'src':
                return $this->ENV['DIR'] . $this->ENV['DIR_SRC'] . $path;
            default:
                return $path;
        }
    }

    function url($option, $url = '') {
        switch ($option) {
            case 'route':
                return $this->ENV['BASE_URL'] . $this->ENV['URL_EXTRA'] . $url;
            case 'web':
                return $this->ENV['BASE_URL'] . $this->ENV['URL_DIR_WEB'] . $url;
            default:
                return $url;
        }
    }

    function urlEncodeSeo($url) {
        return urlencode(preg_replace('/\s+/', '-', strtolower($url)));
    }

    function log($message, $file) {
        $logFile = $this->ENV['DIR'] . $this->ENV['DIR_LOG'] . $file . '.log';
        $maxLogSize = $this->ENV['LOG_SIZE_LIMIT_MB'] * 1048576;
        $message = '[' . date('Y-m-d H:i:s') . '.' . sprintf('%06d', (int) ((microtime(true) - floor(microtime(true))) * 1000000)) . '] ' . $message . EOL;

        file_put_contents($logFile, $message, FILE_APPEND);

        if (filesize($logFile) >= $maxLogSize) {
            $newLogFile = $this->ENV['DIR'] . $this->ENV['DIR_LOG'] . $file . '_' . date('Y-m-d_H-i-s') . '.log';
            rename($logFile, $newLogFile);
        }

        $timestampFile = $this->ENV['DIR'] . $this->ENV['DIR_LOG_TIMESTAMP'] . $file . '_last-log-cleanup-timestamp.txt';
        $now = time();
        $lastCleanup = file_exists($timestampFile) ? (int) file_get_contents($timestampFile) : 0;

        if (($now - $lastCleanup) >= $this->ENV['LOG_CLEANUP_INTERVAL_DAYS'] * 86400) {
            $logFiles = glob($this->ENV['DIR'] . $this->ENV['DIR_LOG'] . $file . '_*.log');
            $logFilesWithTime = array();
            foreach ($logFiles as $file) {
                $logFilesWithTime[$file] = filemtime($file);
            }

            asort($logFilesWithTime);
            $logFiles = array_keys($logFilesWithTime);

            if (count($logFiles) > $this->ENV['MAX_LOG_FILES']) {
                $filesToDelete = array_slice($logFiles, 0, count($logFiles) - $this->ENV['MAX_LOG_FILES']);
                foreach ($filesToDelete as $file) {
                    unlink($file);
                    unset($logFilesWithTime[$file]);
                }
                $logFiles = array_keys($logFilesWithTime);
            }

            foreach ($logFiles as $file) {
                if (($now - $logFilesWithTime[$file]) > ($this->ENV['LOG_RETENTION_DAYS'] * 86400)) {
                    unlink($file);
                }
            }

            file_put_contents($timestampFile, $now);
        }
    }
}