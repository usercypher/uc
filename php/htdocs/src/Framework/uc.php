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

// Version 0.0.1

while (ob_get_level()) {
    ob_end_clean();
}

function d($var, $detailed = false) {
    if (php_sapi_name() !== 'cli' && !headers_sent()) {
        header('content-type: text/plain');
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

    $in->route = ($pos = strpos($in->uri, '?')) !== false ? substr($in->uri, 0, $pos) : $in->uri;

    return $in;
}

function input_cli($in) {
    $in->source = 'cli';

    global $argc, $argv;

    $in->argc = isset($argc) ? $argc : 0;
    $in->argv = isset($argv) ? $argv : array();

    $route = '';
    $query = array();

    for ($i = 1; $in->argc > $i; $i++) {
        $arg = $in->argv[$i];
        if (substr($arg, 0, 2) === '--') {
            $eq = strpos($arg, '=');
            if ($eq !== false) {
                $query[] = urlencode(substr($arg, 2, $eq - 2)) . '=' . urlencode(substr($arg, $eq + 1));
            } else {
                $query[] = urlencode(substr($arg, 2));
            }
        } elseif (substr($arg, 0, 1) !== '-') {
            $route .= '/' . rawurlencode($arg);
        }
    }

    $queryStr = implode('&', $query);

    $in->uri = $route . '?' . $queryStr;
    $in->route = $route;

    parse_str($queryStr, $in->query);

    return $in;
}

class Input {
    var $source = '';
    var $data = array();

    var $header = array();
    var $content = '';
    var $version = '1.1';
    var $method = '';
    var $uri = '/';

    var $argc = 0;
    var $argv = array();

    var $route = '/';
    var $cookie = array();
    var $query = array();
    var $frame = array();
    var $param = array();

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
            if (isset($this->header['location']) && (300 > $this->code || $this->code > 399)) {
                $this->code = 302;
            }
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
}

class App {
    var $UNIT_LIST = 0;
    var $UNIT_PATH = 1;
    var $UNIT_FILE = 2;
    var $UNIT_LOAD = 3;
    var $UNIT_ARGS = 4;
    var $UNIT_INST_CACHE = 5;
    var $ROUTE_HANDLER = '!';

    var $routes = array();
    var $unit = array();
    var $unitList = array();
    var $unitListIndex = 0;
    var $path = array();
    var $pathList = array();
    var $pathListIndex = 0;
    var $unitInstCache = array();
    var $unitPathCache = array();

    var $env = array(
        'SAPI' => '',

        'DIR_ROOT' => '',
        'DIR_WEB' => '',

        'URL_ROOT' => '/',
        'URL_WEB' => '/',
        'URL_ROUTE' => '/',

        'ERROR_TEMPLATES' => array(),
        'ERROR_NON_FATAL' => 0,
        'ERROR_LOG_FILE' => 'error.log',
        'ERROR_MAX_LENGTH' => 4096,
        'ERROR_DISPLAY' => true,
        'ERROR_LOGGING' => false,

        'LOG_HANDLER' => array(),
        'LOG_DIR' => '',
        'LOG_DIR_TIMESTAMP' => '',
        'LOG_SIZE_LIMIT_MB' => 5,
        'LOG_CLEANUP_INTERVAL_DAYS' => 1,
        'LOG_RETENTION_DAYS' => 7,
        'LOG_MAX_FILES' => 10,
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

    // State Management

    function save($file) {
        $this->write($this->env['DIR_ROOT'] . $file, serialize(array($this->routes, $this->unit, $this->unitList, $this->unitListIndex, $this->path, $this->pathList, $this->pathListIndex)));
    }

    function load($file) {
        list($this->routes, $this->unit, $this->unitList, $this->unitListIndex, $this->path, $this->pathList, $this->pathListIndex) = unserialize($this->read($this->env['DIR_ROOT'] . $file));
    }

    // Error Management

    function handleErrorDefault($errno, $errstr, $errfile, $errline) {
        $e = $this->error($errno, $errstr, $errfile, $errline, array('ERROR_ACCEPT' => $this->getEnv('HANDLE_ERROR_DEFAULT_ACCEPT', '')));

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

        if ($this->env['ERROR_LOGGING']) {
            $this->log($error, $this->env['ERROR_LOG_FILE']);
        }

        if ($errno & $this->env['ERROR_NON_FATAL']) {
            return array();
        }

        if ($this->env['ERROR_DISPLAY']) {
            $error .= "\n\n" . 'Stack trace: ' . "\n";

            foreach (array_merge(debug_backtrace(), isset($errcontext['ERROR_TRACE']) ? $errcontext['ERROR_TRACE'] : array()) as $i => $frame) {
                $error .= '#' . $i . ' ' . (isset($frame['file']) ? $frame['file'] : '[internal function]') . '(' . (isset($frame['line']) ? $frame['line'] : 'no line') . '): ' . (isset($frame['class']) ? $frame['class'] . (isset($frame['type']) ? $frame['type'] : '') : '') . (isset($frame['function']) ? $frame['function'] : '[unknown function]') . '(...' . (isset($frame['args']) ? count($frame['args']) : 0) . ')' . "\n";
            }
        } else {
            $error = '';
        }

        $content = '';
        $type = $this->mimeNegotiate(isset($errcontext['ERROR_ACCEPT']) ? $errcontext['ERROR_ACCEPT'] : '', array_keys($this->env['ERROR_TEMPLATES']));
        if ($type && file_exists($this->env['DIR_ROOT'] . $this->env['ERROR_TEMPLATES'][$type])) {
            $content = $this->template($this->env['DIR_ROOT'] . $this->env['ERROR_TEMPLATES'][$type], array('app' => $this, 'code' => $code, 'error' => $error));
        } else {
            $type = 'text/plain';
            $content = $code . '. An unexpected error occurred.' . "\n\n" . $error;
        }

        return array('content' => $content, 'code' => $code, 'type' => $type);
    }

    // Route Management

    function setRoute($method, $route, $units) {
        $handler = array();
        foreach ($units as $unit) {
            $handler[] = $this->unit[$unit][$this->UNIT_LIST];
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
            trigger_error('Duplicate route detected: ' . $route, E_USER_WARNING);
            return;
        }

        $node[$this->ROUTE_HANDLER] = $handler;
    }

    function groupRoute($group, $method, $route, $units, $ignore = array()) {
        $ignore = array_flip($ignore);
        $units = isset($ignore['--all']) ? $units : array_merge(isset($group['prepend']) && !isset($ignore['--prepend']) ? $group['prepend'] : array(), isset($units) ? $units : array(), isset($group['append']) && !isset($ignore['--append']) ? $group['append'] : array());

        $filteredUnits = array();
        foreach ($units as $unit) {
            if (!isset($ignore[$unit])) {
                $filteredUnits[] = $unit;
            }
        }

        $this->setRoute($method, $route, $filteredUnits);
    }

    function resolveRoute($method, $route) {
        if (strlen($route) > 32640) {
            return array('handler' => array(), 'param' => array(), 'error' => '414|URI too long (max 32640 bytes): ' . $route);
        }

        if (!isset($this->routes[$method])) {
            return array('handler' => array(), 'param' => array(), 'error' => '405|Method not allowed: ' . $method . ' ' . $route);
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
                return array('handler' => array(), 'param' => array(), 'error' => '400|Route segment too long (max 255 chars): ' . $routeSegment);
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
                return array('handler' => array(), 'param' => array(), 'error' => '404|Route not found: ' . $method . ' ' . $route);
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
                return array('handler' => array(), 'param' => array(), 'error' => '404|Route not found: ' . $method . ' ' . $route);
            }
        }

        if (!isset($current[$this->ROUTE_HANDLER])) {
            return array('handler' => array(), 'param' => array(), 'error' => '404|Route not found: ' . $method . ' ' . $route);
        }

        $handler = array();
        foreach ($current[$this->ROUTE_HANDLER] as $unit) {
            $handler[] = $this->unitList[$unit];
        }

        return array('handler' => $handler, 'param' => $param);
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
        $pathListIndex = null;
        if (isset($this->path[$path])) {
            $pathListIndex = $this->path[$path];
        } else {
            $pathListIndex = $this->pathListIndex++;
            $this->path[$path] = $pathListIndex;
            $this->pathList[$pathListIndex] = $path;
        }

        $pos = strrpos($unit, '\\');
        $file = $pos === false ? $unit : substr($unit, $pos + 1);
        if (isset($this->unit[$unit]) && ($newFile = $path . $file) !== ($oldFile = $this->pathList[$this->unit[$unit][$this->UNIT_PATH]] . $this->unit[$unit][$this->UNIT_FILE])) {
            trigger_error('Duplicate unit detected: ' . $unit . ' from ' . $newFile . '.php and ' . $oldFile . '.php', E_USER_WARNING);
            return;
        }

        $unitListIndex = $this->unitListIndex++;
        $this->unit[$unit] = array($unitListIndex, $pathListIndex, $file, array(), array(), false);
        $this->unitList[$unitListIndex] = $unit;
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
        $top = 0;
        $seen = array();
        $md = array();

        while ($top > -1) {
            $unit = $stack[$top--];
            $previousUnit = $top > -1 ? $stack[$top] : '';
            $seen[$previousUnit] = true;

            if (isset($seen[$unit])) {
                trigger_error('Circular load detected: ' . implode(' -> ', array_slice($stack, 0, $top + 2)), E_USER_WARNING);
                return;
            }

            if (isset($this->unitPathCache[$unit])) {
                if (0 > $top) {
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
                    $top = $top + 2;
                    $stack[$top] = $this->unitList[$load[$md[$unit][0]]];
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
        $top = 0;
        $seen = array();
        $md = array();
        $resolvedArgs = array();
        $class = null;

        while ($top > -1) {
            $unit = $stack[$top--];
            $previousUnit = $top > -1 ? $stack[$top] : '';
            $seen[$previousUnit] = true;

            if (isset($seen[$unit])) {
                trigger_error('Circular args detected: ' . implode(' -> ', array_slice($stack, 0, $top + 2)), E_USER_WARNING);
                return;
            }

            $cache = !$new && $this->unit[$unit][$this->UNIT_INST_CACHE];
            if ($cache && isset($this->unitInstCache[$unit])) {
                if (0 > $top) {
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
                    $top = $top + 2;
                    $stack[$top] = $this->unitList[$args[$md[$unit][0]]];
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
        unset($this->unitInstCache[$unit]);
    }

    // Utility

    function pipe($input, $output, $pipe) {
        foreach ($pipe as $p) {
            $p = $this->makeUnit($p);
            list($input, $output, $success) = $p->process($input, $output);
            if (!$success) {
                break;
            }
        }

        return array($input, $output);
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

    function urlRoot($s = '') {
        return $this->env['URL_ROOT'] . $s;
    }

    function urlWeb($s = '', $param = array()) {
        return $this->env['URL_WEB'] . ($param ? strtr($s, $param) : $s);
    }

    function urlRoute($s = '', $param = array()) {
        if (strpos($this->env['URL_ROUTE'], '?') !== false) {
            $s = str_replace('?', '&', $s);
        }
        return $this->env['URL_ROUTE'] . ($param ? strtr($s, $param) : $s);
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

    function mimeNegotiate($accept, $offers) {
        $prefs = array();
        foreach (explode(',', $accept) as $type) {
            $parts = explode(';', trim($type));
            $mime = strtolower(trim(array_shift($parts)));

            $q = 1.0;
            foreach ($parts as $p) {
                $p = explode('=', trim($p));
                if (isset($p[1]) && strtolower(trim($p[0])) === 'q') {
                    $q = (float) trim($p[1]);
                }
            }
            if ($q > 0) {
                $q += substr($mime, -2) === '/*' ? 0 : 0.01;
                $prefs[$mime] = isset($prefs[$mime]) && $prefs[$mime] > $q ? $prefs[$mime] : $q;
            }
        }
        arsort($prefs);
        foreach (array_keys($prefs) as $p) {
            foreach ($offers as $o) {
                $o = strtolower($o);
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

        $msg = date(sprintf('[Y-m-d H:i:s.%06d O]', $micro * 1000000), $time) . ' ' . $msg . "\n";

        if ($this->env['LOG_HANDLER']) {
            list($obj, $method) = $this->env['LOG_HANDLER'];
            $obj->$method($msg, $file);
            return;
        }

        $ext = '';
        $pos = strrpos($file, '.');
        if ($pos !== false && $pos > 0) {
            $ext = substr($file, $pos);
            $file = substr($file, 0, $pos);
        }

        $logDir = $this->env['DIR_ROOT'] . $this->env['LOG_DIR'];
        $logFile = $logDir . $file . $ext;

        $this->write($logFile, $msg, true);

        if (filesize($logFile) >= $this->env['LOG_SIZE_LIMIT_MB'] * 1048576) {
            $newLogFile = $logDir . '/' . $file . '_' . date('Y-m-d_H-i-s') . $ext;
            rename($logFile, $newLogFile);
        }

        $timestampFile = $this->env['DIR_ROOT'] . $this->env['LOG_DIR_TIMESTAMP'] . $file . '_last-log-cleanup-timestamp.txt';
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

            if (count($logFiles) > $this->env['LOG_MAX_FILES']) {
                $maxIndex = count($logFiles) - $this->env['LOG_MAX_FILES'];
                for ($i = 0; $maxIndex > $i; $i++) {
                    unlink($logFiles[$i]);
                }
            }

            $this->write($timestampFile, $time);
        }
    }
}
