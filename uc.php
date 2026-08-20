<?php /*
Version: 9.0.0

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

define('APP_UNIT_LIST', 0);
define('APP_UNIT_PATH', 1);
define('APP_UNIT_FILE', 2);
define('APP_UNIT_LOAD', 3);
define('APP_UNIT_ARGS', 4);
define('APP_UNIT_INST_CACHE', 5);
define('APP_ROUTE_HANDLER', '!');

while (ob_get_length() !== false) {
    ob_end_clean();
}

function d($var, $limit = 8192) {
    if (php_sapi_name() !== 'cli' && !headers_sent()) {
        header('content-type: text/plain');
    }
    ob_start();
    var_dump($var);
    $content = ob_get_contents();
    ob_end_clean();
    echo($limit > -1 && strlen($content) > $limit ? substr($content, 0, $limit) . "\n... [truncated]" : $content);
}

function dd($var = null, $limit = 8192) {
    d($var, $limit);
    die;
}

class Input {
    var $data = array();

    var $header = array();
    var $content = '';
    var $version = '1.1';

    var $method = '';
    var $uri = '/';
    var $route = '/';
    var $cookie = array();
    var $query = array();
    var $frame = array();
    var $param = array();

    function init() {}
    function term() {}
    function call($content = '', $code = 0) {}
}

class InputHttp extends Input {
    var $contentRead = false;

    function init() {
        $contentHeader = array('CONTENT_TYPE' => true, 'CONTENT_LENGTH' => true);
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $this->header[str_replace('_', '-', strtolower(substr($key, 5)))] = $value;
            } elseif (isset($contentHeader[$key])) {
                $this->header[str_replace('_', '-', strtolower($key))] = $value;
            }
        }

        $this->version = isset($_SERVER['SERVER_PROTOCOL']) ? substr($_SERVER['SERVER_PROTOCOL'], 5) : '1.1';
        $this->method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
        $this->uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $this->route = ($pos = strpos($this->uri, '?')) !== false ? substr($this->uri, 0, $pos) : $this->uri;
        $this->cookie = $_COOKIE;
        $this->query = $_GET;
        $this->frame = $_FILES + $_POST;
    }

    function call($content = '', $code = 0) {
        if (!$this->contentRead && ($handle = fopen('php://input', 'rb'))) {
            $lines = '';
            while (($chunk = fread($handle, 8192)) !== false && $chunk !== '') {
                $lines .= $chunk;
            }
            fclose($handle);
            $this->content = $lines;
        }

        $this->contentRead = true;

        return $this->content;
    }
}

class InputCli extends Input {
    function init() {
        global $argc, $argv;

        $route = '';
        $query = array();

        for ($i = 1; $argc > $i; $i++) {
            $arg = $argv[$i];
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

        $this->uri = 'cli://' . $argv[0] . $route . ($queryStr ? '?' . $queryStr : '');
        $this->route = $route;

        parse_str($queryStr, $this->query);
    }

    function call($content = '', $code = 0) {
        if ($content === '') {
            return ($line = fgets(STDIN)) !== false ? rtrim($line) : '';
        }

        $lines = '';
        while (($line = fgets(STDIN)) !== false && rtrim($line) !== $content) {
            $lines .= $line;
        }

        return $lines;
    }
}

class Output {
    var $header = array();
    var $content = '';
    var $version = '1.1';

    var $code = 200;
    var $text = '';

    function init() {}
    function term() {}
    function call($content = '', $code = 0) {}
}

class OutputHttp extends Output {
    function call($content = '', $code = 0) {
        if (!headers_sent()) {
            if (isset($this->header['location']) && (300 > $code || $code > 399)) {
                $code = 302;
            }
            header('HTTP/' . $this->version . ' ' . $code . ' ' . $this->text);
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

        echo($content);
        flush();
    }
}

class OutputCli extends Output {
    var $code = 0;

    function call($content = '', $code = 0) {
        fwrite($code === 0 ? STDOUT : STDERR, $content);
        flush();
    }
}

class App {
    var $version = '9.0.0';
    var $routes = array();
    var $unit = array();
    var $unitList = array();
    var $unitListIndex = 0;
    var $path = array();
    var $pathList = array();
    var $pathListIndex = 0;
    var $env = array(
        'SAPI' => '',

        'DIR_ROOT' => '',
        'URL_ROUTE' => '/',

        'ERROR_TEMPLATES' => array(),
        'ERROR_NON_FATAL' => 0,
        'ERROR_LOG_FILE' => 'error.log',
        'ERROR_MAX_LENGTH' => 4096,
        'ERROR_DISPLAY' => true,
        'ERROR_LOGGING' => false,

        'LOG_HANDLER' => null,
        'LOG_DIR' => '',
        'LOG_DIR_TIMESTAMP' => '',
        'LOG_SIZE_LIMIT_MB' => 5,
        'LOG_CLEANUP_INTERVAL_DAYS' => 1,
        'LOG_RETENTION_DAYS' => 7,
        'LOG_MAX_FILES' => 10,

        'READ_HANDLER' => null,
        'WRITE_HANDLER' => null,
    );
    var $unitInstCache = array();
    var $unitLoadCache = array('App' => true, 'Input' => true, 'InputHttp' => true, 'InputCli' => true, 'Output' => true, 'OutputHttp' => true, 'OutputCli' => true);

    // Application Setup

    function init() {
        $this->env['SAPI'] = php_sapi_name();
        $this->env['DIR_ROOT'] = $this->dirToUnix(dirname(__FILE__)) . '/';
        $this->env['ERROR_NON_FATAL'] = E_NOTICE | E_USER_NOTICE;
        foreach (array('App', 'Input', 'InputHttp', 'InputCli', 'Output', 'OutputHttp', 'OutputCli') as $unit) {
            if (!isset($this->unit[$unit])) {
                $this->addUnit($unit);
            }
        }
        $this->setUnit('App', array('cache' => true));
        $this->unitInstCache['App'] = $this;
    }

    function term() {
        $this->unitInstCache = array();
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

    function handleError($errno, $errstr, $errfile, $errline) {
        $e = $this->error($errno, $errstr, $errfile, $errline, array('TRACE' => $this->env['ERROR_DISPLAY'] ? debug_backtrace() : array()) + $this->getEnv('HANDLE_ERROR_DEFAULT_CONTEXT', array()));

        if (!$e) {
            return true;
        }

        while (ob_get_length() !== false) {
            ob_end_clean();
        }

        if ($this->env['SAPI'] !== 'cli' && !headers_sent()) {
            header('HTTP/1.1 ' . $e['code']);
            foreach ($e['header'] as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        header($key . ': ' . $v, false);
                    }
                } else {
                    header($key . ': ' . $value);
                }
            }
        }

        if ($this->env['SAPI'] === 'cli') {
            fwrite(STDERR, $e['content']);
        } else {
            echo($e['content']);
        }

        die($e['code'] > 255 ? 1 : $e['code']);
    }

    function error($errno, $errstr, $errfile, $errline, $errcontext) {
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

        $error = '[php ' . $errno . '] [' . $this->env['SAPI'] . ' ' . $code . '] ' . $errstr . ' in ' . $errfile . ':' . $errline;

        if ($this->env['ERROR_LOGGING']) {
            $this->log($error, $this->env['ERROR_LOG_FILE']);
        }

        if ($errno & $this->env['ERROR_NON_FATAL']) {
            return array();
        }

        if ($this->env['ERROR_DISPLAY']) {
            $error .= "\n\n";

            foreach ((isset($errcontext['TRACE']) ? $errcontext['TRACE'] : array()) as $i => $frame) {
                $error .= '#' . $i . ' ' . (isset($frame['file']) ? $frame['file'] : '[internal function]') . '(' . (isset($frame['line']) ? $frame['line'] : 'no line') . '): ' . (isset($frame['class']) ? $frame['class'] . (isset($frame['type']) ? $frame['type'] : '') : '') . (isset($frame['function']) ? $frame['function'] : '[unknown function]') . '(...' . (isset($frame['args']) ? count($frame['args']) : 0) . ')' . "\n";
            }
        } else {
            $error = '';
        }

        $content = '';
        $type = $this->httpNegotiate(isset($errcontext['ACCEPT']) ? $errcontext['ACCEPT'] : '', array_keys($this->env['ERROR_TEMPLATES']));
        if ($type && file_exists($this->env['DIR_ROOT'] . $this->env['ERROR_TEMPLATES'][$type])) {
            $content = $this->template($this->env['DIR_ROOT'] . $this->env['ERROR_TEMPLATES'][$type], array('app' => $this, 'code' => $code, 'error' => $error));
        } else {
            $type = 'text/plain';
            $content = $code . '. An unexpected error occurred.' . "\n\n" . $error;
        }

        $header = isset($errcontext['HEADER']) ? $errcontext['HEADER'] : array();
        $header['content-type'] = $type;
        unset($header['location']);

        return array('header' => $header, 'content' => $content, 'code' => $code);
    }

    // Route Management

    function setRoute($method, $route, $units) {
        $handler = array();
        foreach ($units as $unit) {
            $handler[] = $this->unit[$unit][APP_UNIT_LIST];
        }

        $node = &$this->routes;
        $routeSegments = explode('/', trim($route, '/') . '/' . APP_ROUTE_HANDLER);
        foreach ($routeSegments as $segment) {
            if (!isset($node[$segment])) {
                $node[$segment] = array();
            }
            $node = &$node[$segment];
        }

        if (isset($node[$method])) {
            user_error('Duplicate route detected: ' . $route, E_USER_WARNING);
            return;
        }

        $node[$method] = $handler;
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
        $current = $this->routes;
        $param = array();
        $routeSegments = explode('/', $route, 128);
        $foundSegment = false;
        $last = count($routeSegments) - 1;

        foreach ($routeSegments as $index => $routeSegment) {
            if ($routeSegment === '' && ($foundSegment || $last !== $index)) {
                continue;
            }

            $foundSegment = true;

            if (isset($current[$routeSegment])) {
                $current = $current[$routeSegment];
                continue;
            }

            $matched = false;

            foreach ($current as $key => $value) {
                if ($key && $key[0] === ':') {
                    $current = $value;
                    if (substr($key, -1) === '*') {
                        $param[substr($key, 1, -1)] = implode('/', array_slice($routeSegments, $index));
                        if (isset($current[APP_ROUTE_HANDLER])) {
                            break 2;
                        }
                    } else {
                        $param[substr($key, 1)] = rawurldecode($routeSegment);
                    }
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                return array('error' => 404);
            }
        }

        while (!isset($current[APP_ROUTE_HANDLER])) {
            $matched = false;

            foreach ($current as $key => $value) {
                if ($key && $key[0] === ':') {
                    $current = $value;
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                return array('error' => 404);
            }
        }

        $current = $current[APP_ROUTE_HANDLER];

        if (!isset($current[$method])) {
            return array('error' => 405, 'header' => array('allow' => implode(', ', array_keys($current))));
        }

        $handler = array();
        foreach ($current[$method] as $unit) {
            $handler[] = $this->unitList[$unit];
        }

        return array('handler' => $handler, 'param' => $param);
    }

    // Unit Management

    function syncUnits() {
        $units = array();
        $loads = array();

        foreach (get_declared_classes() as $unit) {
            $units[strtolower($unit)] = true;
        }

        foreach (get_required_files() as $load) {
            $loads[$this->dirToUnix($load)] = true;
        }

        foreach ($this->unit as $unit => $data) {
            if (!isset($this->unitLoadCache[$unit]) && (isset($units[strtolower($unit)]) || isset($loads[$this->env['DIR_ROOT'] . $this->pathList[$data[APP_UNIT_PATH]] . $data[APP_UNIT_FILE] . '.php']))) {
                $this->unitLoadCache[$unit] = true;
            }
        }
    }

    function scanUnits($path, $option) {
        if (!isset($option['depth'])) {
            $option['depth'] = 1;
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

        $relative = implode('/', array_slice(explode('/', $path), -$option['depth']));

        if ($handle = opendir($this->env['DIR_ROOT'] . $path)) {
            while (($item = readdir($handle)) !== false) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                foreach ($option['ignore'] as $pattern) {
                    if ($this->strMatch($pattern, $relative . $item)) {
                        continue 2;
                    }
                }

                $isDir = is_dir($this->env['DIR_ROOT'] . $path . $item);

                if ($isDir && ($option['max'] === -1 || $option['max'] >= $option['depth'])) {
                    $subOption = $option;
                    $subOption['depth']++;
                    $subOption['namespace'] .= $item . '\\';
                    $this->scanUnits($path . $item . '/', $subOption);
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
        if (isset($this->unit[$unit])) {
            if (($newFile = $path . $file) !== ($oldFile = $this->pathList[$this->unit[$unit][APP_UNIT_PATH]] . $this->unit[$unit][APP_UNIT_FILE])) {
                user_error('Duplicate unit detected: ' . $unit . ' from ' . $newFile . '.php and ' . $oldFile . '.php', E_USER_WARNING);
            }
            return;
        }

        $unitListIndex = $this->unitListIndex++;
        $this->unit[$unit] = array($unitListIndex, $pathListIndex, $file, array(), array(), false);
        $this->unitList[$unitListIndex] = $unit;
    }

    function setUnit($unit, $option = array()) {
        $test = $this->unit[$unit];

        $map = array('args' => APP_UNIT_ARGS, 'load' => APP_UNIT_LOAD);
        foreach ($map as $key => $value) {
            if (isset($option[$key])) {
                foreach ($option[$key] as $tmpUnit) {
                    $this->unit[$unit][$value][] = $this->unit[$tmpUnit][APP_UNIT_LIST];
                }
            }
        }

        $this->unit[$unit][APP_UNIT_INST_CACHE] = isset($option['cache']) ? $option['cache'] : $this->unit[$unit][APP_UNIT_INST_CACHE];
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
                user_error('Circular load detected: ' . implode(' -> ', array_slice($stack, 0, $top + 2)), E_USER_WARNING);
                return;
            }

            if (isset($this->unitLoadCache[$unit])) {
                if (0 > $top) {
                    return;
                }

                unset($seen[$previousUnit]);
                continue;
            }

            $load = $this->unit[$unit][APP_UNIT_LOAD];
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

            require $this->env['DIR_ROOT'] . $this->pathList[$this->unit[$unit][APP_UNIT_PATH]] . $this->unit[$unit][APP_UNIT_FILE] . '.php';
            $this->unitLoadCache[$unit] = true;
        }
    }

    function &makeUnit($unit, $new = false) {
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
                user_error('Circular args detected: ' . implode(' -> ', array_slice($stack, 0, $top + 2)), E_USER_WARNING);
                return $class;
            }

            $cache = !$new && $this->unit[$unit][APP_UNIT_INST_CACHE];
            if ($cache && isset($this->unitInstCache[$unit])) {
                if (0 > $top) {
                    return $this->unitInstCache[$unit];
                }

                unset($seen[$previousUnit]);
                $resolvedArgs[$previousUnit][] = &$this->unitInstCache[$unit];
                continue;
            }

            $args = $this->unit[$unit][APP_UNIT_ARGS];
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
        $this->unitInstCache[$unit] = null;
    }

    // Utility

    function dirToUnix($s) {
        return str_replace('\\', '/', $s);
    }

    function dir($k, $s = '') {
        return $this->env['DIR_' . $k] . $s;
    }

    function url($k, $s = '', $param = array()) {
        if (strpos($this->env['URL_' . $k], '?') !== false && ($q = strpos($s, '?')) !== false) {
            $s[$q] = '&';
        }
        return $this->env['URL_' . $k] . ($param ? strtr($s, $param) : $s);
    }

    function log($msg, $file) {
        $mt = explode(' ', microtime());
        $micro = (float) $mt[0];
        $time = (int) $mt[1];

        $msg = date(sprintf('[Y-m-d H:i:s.%06d O]', $micro * 1000000), $time) . ' ' . $msg . "\n";

        if ($this->env['LOG_HANDLER']) {
            return $this->env['LOG_HANDLER']->call($msg, $file);
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

    function pipe($input, $output, $pipe) {
        foreach ($pipe as $p) {
            $p = $this->makeUnit($p);
            list($input, $output, $success) = $p->call($input, $output);
            if (!$success) {
                break;
            }
        }

        return array($input, $output);
    }

    function cast($data, $schema, $meta = array()) {
        $valid = array();
        $error = array();
        foreach ($schema as $field => $rules) {
            $valid[$field] = isset($data[$field]) ? $data[$field] : null;
            foreach ($rules as $rule) {
                list($valid[$field], $err) = $rule->call($valid[$field]);
                if ($err) {
                    $error[] = array('type' => 'system:message:error', 'data' => array('content' => $err, 'field' => $field) + $meta);
                    break;
                }
            }
        }
        return array($valid, $error);
    }

    function read($file) {
        if ($this->env['READ_HANDLER']) {
            return $this->env['READ_HANDLER']->call($file);
        }

        if ($handle = fopen($file, 'rb')) {
            $content = '';
            while (($chunk = fread($handle, 8192)) !== false && $chunk !== '') {
                $content .= $chunk;
            }
            fclose($handle);
            return $content;
        }
    }

    function write($file, $content, $append = false) {
        if ($this->env['WRITE_HANDLER']) {
            return $this->env['WRITE_HANDLER']->call($file, $content, $append);
        }

        if ($handle = fopen($file, $append ? 'ab' : 'wb')) {
            $result = fwrite($handle, (string) $content);
            fclose($handle);
            return $result;
        }
    }

    function template($file, $data = array()) {
        ob_start();
        require $file;
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    function data($file, $data = array()) {
        if (!is_array($output = require $file)) {
            user_error('Data file must return an array', E_USER_WARNING);
        }
        return $output;
    }

    function strSlug($s) {
        $s = strtolower($s);
        $slug = '';
        for ($i = 0, $ilen = strlen($s); $ilen > $i; $i++) {
            $char = $s[$i];
            if (($char >= 'a' && 'z' >= $char) || ($char >= '0' && '9' >= $char)) {
                $slug .= $char;
            } elseif (($char === ' ' || $char === '-') && substr($slug, -1) !== '-') {
                $slug .= '-';
            }
        }
        return trim($slug, '-');
    }

    function strMatch($pattern, $s) {
        switch ($pattern === '' ? $pattern : $pattern[0]) {
            case '^' : return strpos($s, substr($pattern, 1)) === 0;
            case '$' :
                $suffix = substr($pattern, 1);
                return strrpos($s, $suffix) === strlen($s) - strlen($suffix);
            case '*' : return strpos($s, substr($pattern, 1)) !== false;
            case '=' : return $s === substr($pattern, 1);
            default  : return $s === $pattern;
        }
    }

    function htmlEncode($s, $f = ENT_QUOTES) {
        return isset($s) ? htmlspecialchars($s, $f) : '';
    }

    function httpNegotiate($accept, $offers) {
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
                if ($p === $o || $p === '*' || $p === '*/*' || (substr($p, -2) === '/*' && strpos($o, substr($p, 0, -1)) === 0)) {
                    return $o;
                }
            }
        }
    }

    function versionCompare($required, $available) {
        $r = explode('.', $required, 3);
        $a = explode('.', $available, 3);

        if ((int) $a[0] > (int) $r[0]) {
            return 1;
        }

        if ((int) $r[0] > (int) $a[0] || (isset($r[1]) ? ((int) $r[1]) : 0) > (isset($a[1]) ? ((int) $a[1]) : 0)) {
            return -1;
        }

        return 0;
    }
}