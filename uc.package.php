<?php

define('DS', '/');

function app($mode) {
    $app = new App(array(new Request, new Response));

    require('uc.settings.php');
    $settings = settings();

    $app->setInis($settings['ini'][$mode]);
    $app->setEnvs($settings['env'][$mode]);

    $app->init();

    set_error_handler(array($app, 'error'));
    register_shutdown_function(array($app, 'shutdown'));

    return $app;
}

class Request {
    var $uri, $method, $get, $post, $files, $cookies, $server, $params, $data;

    function __construct() {
        $this->init();
    }

    function init() {
        $this->uri = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '';
        $this->method = isset($_SERVER["REQUEST_METHOD"]) ? $_SERVER["REQUEST_METHOD"] : '';
        $this->get = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
        $this->server = $_SERVER;
        $this->params = array();
        $this->data = array();
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
        header('HTTP/1.1 ' . $this->code);

        foreach ($this->headers as $key => $value) {
            header($key . ': ' . $value);
        }

        if (!isset($this->headers['Content-Type'])) {
            header('Content-Type: ' . $this->type);
        }

        exit(isset($this->headers['Location']) ? '' : $this->content);
    }

    function view($view, $data) {
        ob_start();
        require($view);
        $viewData = ob_get_contents();
        ob_end_clean();

        return $viewData;
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

    var $CLASS_ARGS = 0;
    var $CLASS_PATH = 1;
    var $CLASS_CACHE = 2;
    var $CLASS_CLASS_LIST_INDEX = 3;

    var $CACHE_CLASS = 0;
    var $CACHE_PATH = 1;

    var $routes = array();
    var $components = array('prepend' => array(), 'append' => array());
    var $class = array();
    var $classList = array();
    var $classListIndex = 0;
    var $pathList = array();
    var $pathListIndex = 0;

    var $cache = array();
    var $pathListCache = array();

    var $invokeApp = false;

    // Application Setup

    function __construct($args) {
        list($request, $response) = $args;

        $this->class = array(
            'App' => array(array(1, 2), null, true, 0),
            'Request' => array(array(), null, true, 1),
            'Response' => array(array(), null, true, 2),
        );

        $this->classList = array('App', 'Request', 'Response');
        $this->classListIndex = 3;

        $this->cache = array(
            'App' => array($this, true),
            'Request' => array($request, true),
            'Response' => array($response, true),
        );
    }

    function init() {
        $this->ENV['DIR'] = __DIR__ . DS;

        $this->ENV['DIR_LOG'] = isset($this->ENV['DIR_LOG']) ? $this->ENV['DIR_LOG'] : '';
        $this->ENV['DIR_LOG_TIMESTAMP'] = isset($this->ENV['DIR_LOG_TIMESTAMP']) ? $this->ENV['DIR_LOG_TIMESTAMP'] : '';
        $this->ENV['DIR_VIEW'] = isset($this->ENV['DIR_VIEW']) ? $this->ENV['DIR_VIEW'] : '';
        $this->ENV['DIR_WEB'] = isset($this->ENV['DIR_WEB']) ? $this->ENV['DIR_WEB'] : '';
        $this->ENV['DIR_SRC'] = isset($this->ENV['DIR_SRC']) ? $this->ENV['DIR_SRC'] : '';

        $this->ENV['ROUTE_FILE'] = isset($this->ENV['ROUTE_FILE']) ? $this->ENV['ROUTE_FILE'] : 'index.php';
        $this->ENV['ROUTE_REWRITE'] = isset($this->ENV['ROUTE_REWRITE']) ? (bool) $this->ENV['ROUTE_REWRITE'] : false;
        $this->ENV['URL_EXTRA'] = $this->ENV['ROUTE_REWRITE'] ? '' : ($this->ENV['ROUTE_FILE'] . '?route=/');

        $this->ENV['URL_DIR_WEB'] = isset($this->ENV['URL_DIR_WEB']) ? $this->ENV['URL_DIR_WEB'] : '';

        $request = $this->cache['Request'][$this->CACHE_CLASS];
        $this->ENV['HTTP_PROTOCOL'] = isset($this->ENV['HTTP_PROTOCOL']) ? $this->ENV['HTTP_PROTOCOL'] : ((isset($request->server['HTTPS']) && $request->server['HTTPS'] === 'on') ? 'https' : 'http');
        $this->ENV['HTTP_HOST'] = isset($this->ENV['HTTP_HOST']) ? $this->ENV['HTTP_HOST'] : (isset($request->server['HTTP_HOST']) ? $request->server['HTTP_HOST'] : '127.0.0.1');
        $this->ENV['BASE_URL'] = $this->ENV['HTTP_PROTOCOL'] . '://' . $this->ENV['HTTP_HOST'] . '/';

        $this->ENV['ERROR_VIEW_FILE'] = isset($this->ENV['ERROR_VIEW_FILE']) ? $this->ENV['ERROR_VIEW_FILE'] : 'uc.error.php';
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
            $this->log('Failed to set ini setting: ' . $key, 'app.error');
        }
    }

    function setInis($keys) {
        foreach ($keys as $key => $value) {
            if (ini_set($key, $value) === false) {
                $this->log('Failed to set ini setting: ' . $key, 'app.error');
            }
        }
    }

    // Config Management

    function saveConfig($file) {
        $configFile = $this->ENV['DIR'] . $file . '.json';
        file_put_contents($configFile, json_encode(array(
            'routes' => $this->routes,
            'components' => $this->components,
            'class' => $this->class,
            'class_list' => $this->classList,
            'class_list_index' => $this->classListIndex,
            'path_list' => $this->pathList,
            'path_list_index' => $this->pathListIndex
        )));

        echo('File created: ' . $configFile);
    }

    function loadConfig($file) {
        $configFile = $this->ENV['DIR'] . $file . '.json';
        if (file_exists($configFile)) {
            $data = json_decode(file_get_contents($configFile), true);
            $this->routes = $data['routes'];
            $this->components = $data['components'];
            $this->class = $data['class'];
            $this->classList = $data['class_list'];
            $this->classListIndex = $data['class_list_index'];
            $this->pathList = $data['path_list'];
            $this->pathListIndex = $data['path_list_index'];
        } else {
            trigger_error('404|File not found: ' . $configFile, E_USER_WARNING);
            exit();
        }
    }

    // Error Management

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

        $parts = explode('|', $errstr, 2);
        $httpCode = 500;

        if (isset($parts[0]) && is_numeric($parts[0])) {
            $httpCode = (int) $parts[0];
            $errstr = $parts[1];
        }

        header('HTTP/1.1 ' . $httpCode);
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->log($errstr . ' in ' . $errfile . ' on line ' . $errline, 'app.error');
            header('Content-Type: application/json');
            exit($this->ENV['SHOW_ERRORS'] ? '{"error":true,"message":"Error: ' . $errstr . ' in ' . $errfile . ' on line ' . $errline . '"}' : '{"error":true,"message":"An unexpected error occurred. Please try again later."}');
        } else {
            if ($this->ENV['SHOW_ERRORS']) {
                $traceOutput = '';
                if ($enableStackTrace) {
                    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                    $traceOutput = 'Stack trace: ' . PHP_EOL;
                    foreach ($trace as $i => $frame) {
                        if (1 > $i) { continue; }
                        $traceOutput .= '#' . ($i - 1) . ' ';
                        $traceOutput .= isset($frame['file']) ? $frame['file'] : '[internal function]';
                        $traceOutput .= ' (' . (isset($frame['line']) ? $frame['line'] : 'no line') . '): ';
                        $traceOutput .= isset($frame['class']) ? $frame['class'] . (isset($frame['type']) ? $frame['type'] : '') : '';
                        $traceOutput .= (isset($frame['function']) ? $frame['function'] . '()' : '[unknown function]') . PHP_EOL;
                    }
                }
                header('Content-Type: text/plain');
                exit('Error: ' . $errstr . ' in '. $errfile . ' on line ' . $errline . PHP_EOL . PHP_EOL . $traceOutput);
            } else {
                $this->log($errstr . ' in ' . $errfile . ' on line ' . $errline, 'app.error');
                $data = array('app' => $this, 'http_code' => $httpCode);
                $file = $this->ENV['DIR'] . $this->ENV['ERROR_VIEW_FILE'];
                exit(file_exists($file) ? include($file) : 'An unexpected error occurred. Please try again later.');
            }
        }
    }

    // Route Management

    function setRoute($method, $route, $option) {
        $component = array();
        if (isset($option['component'])) {
            foreach ($option['component'] as $class) {
                if (!isset($this->class[$class])) {
                    $this->class[$class] = array(null, null, false, $this->classListIndex);
                    $this->classList[$this->classListIndex] = $class;
                    ++$this->classListIndex;
                }
                $component[] = $this->class[$class][$this->CLASS_CLASS_LIST_INDEX];
            }
        }

        $ignore = array();
        if (isset($option['ignore'])) {
            foreach ($option['ignore'] as $class) {
                if (!isset($this->class[$class])) {
                    $this->class[$class] = array(null, null, false, $this->classListIndex);
                    $this->classList[$this->classListIndex] = $class;
                    ++$this->classListIndex;
                }
                $ignore[] = $this->class[$class][$this->CLASS_CLASS_LIST_INDEX];
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

        $node['_h'] = array('_c' => $component, '_i' => $ignore);
    }

    function setRoutes($option, $params) {
        foreach ($params as $p) {
            $p[2]['component'] = array_merge((isset($option['component_prepend']) ? $option['component_prepend'] : array()), (isset($p[2]['component']) ? $p[2]['component'] : array()), (isset($option['component_append']) ? $option['component_append'] : array()));
            $p[2]['ignore'] = array_merge((isset($option['ignore']) ? $option['ignore'] : array()), (isset($p[2]['ignore']) ? $p[2]['ignore'] : array()));
            $this->setRoute($p[0], (isset($option['prefix']) ? $option['prefix'] : '') . $p[1], array_merge($option, $p[2]));
        }
    }

    function setComponents($components) {
        foreach ($components as $key => $c) {
            if (!in_array($key, array('prepend', 'append'))) { trigger_error('500|is not prepend or append: ' . $key, E_USER_WARNING); exit(); }
            foreach ($c as $class) {
                if (!isset($this->class[$class])) {
                    $this->class[$class] = array(null, null, false, $this->classListIndex);
                    $this->classList[$this->classListIndex] = $class;
                    ++$this->classListIndex;
                }
                $this->components[$key][] = $this->class[$class][$this->CLASS_CLASS_LIST_INDEX];
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
                --$decrement;
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
                        $params[rtrim($paramName, '*')] = array_slice($pathSegments, $index);
                        $current = $value;
                        break 2;
                    }
                    if ($paramModifier === '?' && preg_match('/' . $paramRegex . '/', $pathSegment, $matches)) {
                        $params[rtrim($paramName, '?')] = (count($matches) === 1) ? $matches[0] : $matches;
                        $current = $value;
                        $matched = true;
                        break;
                    }
                    if (preg_match('/' . $paramRegex . '/', $pathSegment, $matches)) {
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

        $finalComponents = array();
        if ($current['_h']['_i'] !== array(true)) {
            $ignore = array_flip($current['_h']['_i']);

            foreach ($this->components['prepend'] as $component) {
                if (!isset($ignore[$component])) {
                    $finalComponents[] = $component;
                }
            }

            foreach ($current['_h']['_c'] as $component) {
                if (!isset($ignore[$component])) {
                    $finalComponents[] = $component;
                }
            }

            foreach ($this->components['append'] as $component) {
                if (!isset($ignore[$component])) {
                    $finalComponents[] = $component;
                }
            }
        }

        return array('handler' => array('component' => $finalComponents), 'params' => $params);
    }

    // Request Handling

    function dispatch() {
        $response = $this->cache['Response'][$this->CACHE_CLASS];
        if ($this->invokeApp) {
            return $response;
        }

        $this->invokeApp = true;
        $request = $this->cache['Request'][$this->CACHE_CLASS];

        if ($this->ENV['ROUTE_REWRITE']) {
            $parseUrl = parse_url($request->uri);
            $path = ($parseUrl === false) ? $request->uri : $parseUrl['path'];
        } else {
            $path = isset($request->get['route']) ? $request->get['route'] : '';
        }

        $route = $this->resolveRoute($request->method, $path);

        if ($route === array()) {
            trigger_error('404|Route not found: ' . $request->method . ' ' . $path, E_USER_WARNING);
            exit();
        }

        $request->params = $route['params'];
        foreach ($route['handler']['component'] as $c) {
            $c = $this->getClass($this->classList[$c]);
            $rr = $c->process($request, $response);
            $request = $rr[0];
            $response = $rr[1];
        }

        return $response;
    }

    // Class Management

    function autoSetClass($path, $option) {
        $option = array(
            'depth' => isset($option['depth']) ? $option['depth'] : 0,
            'max' => isset($option['max']) ? $option['max'] : -1,
            'ignore' => isset($option['ignore']) ? $option['ignore'] : array(),
            'namespace' => isset($option['namespace']) ? $option['namespace'] : '',
            'dir_as_namespace' => isset($option['dir_as_namespace']) ? $option['dir_as_namespace'] : false,
            'args' => isset($option['args']) ? $option['args'] : array(),
        );

        if ($dirHandle = opendir($this->ENV['DIR'] . $path)) {
            while (($file = readdir($dirHandle)) !== false) {
                if ($file === '.' || $file === '..' || in_array($file, $option['ignore'])) {
                    continue;
                }
                if (($option['max'] === -1 || $option['max'] > $option['depth']) && is_dir($this->ENV['DIR'] . $path . $file)) {
                    ++$option['depth'];
                    if ($option['dir_as_namespace']) {
                        $namespace = $option['namespace'];
                        $option['namespace'] .= ($file . '\\');
                        $this->autoSetClass($path . $file . DS, $option);
                        $option['namespace'] = $namespace;
                    } else {
                        $this->autoSetClass($path . $file . DS, $option);
                    }
                    --$option['depth'];
                } else if (substr($file, -4) === '.php') {
                    $this->setClass(substr($file, 0, -4), array('path' => $path, 'namespace' => $option['namespace'], 'args' => $option['args']));
                }
            }
            closedir($dirHandle);
        }
    }

    function setClass($class, $option) {
        $class = (isset($option['namespace']) ? $option['namespace'] : '') . $class;
        if (!isset($this->class[$class])) {
            $this->class[$class] = array(null, null, false, $this->classListIndex);
            $this->classList[$this->classListIndex] = $class;
            ++$this->classListIndex;
        }
        if (isset($option['args'])) {
            foreach ($option['args'] as $arg) {
                if (!isset($this->class[$arg])) {
                    $this->class[$arg] = array(null, null, false, $this->classListIndex);
                    $this->classList[$this->classListIndex] = $arg;
                    ++$this->classListIndex;
                }
                $this->class[$class][$this->CLASS_ARGS][] = $this->class[$arg][$this->CLASS_CLASS_LIST_INDEX];
            }
        }
        if (isset($option['path'])) {
            $pathListIndex = isset($this->pathListCache[$option['path']]) ? $this->pathListCache[$option['path']] : array_search($option['path'], $this->pathList);
            if ($pathListIndex === false) {
                $pathListIndex = $this->pathListIndex;
                $this->pathList[$this->pathListIndex] = $option['path'];
                ++$this->pathListIndex;
            }
            $this->pathListCache[$option['path']] = $pathListIndex;
            $this->class[$class][$this->CLASS_PATH] = $pathListIndex;
        }
        $this->class[$class][$this->CLASS_CACHE] = (isset($option['cache']) ? $option['cache'] : $this->class[$class][$this->CLASS_CACHE]);
    }

    function setClasses($option, $classes) {
        foreach ($classes as $class) {
            $class[1]['args'] = array_merge((isset($option['args_prepend']) ? $option['args_prepend'] : array()), (isset($class[1]['args']) ? $class[1]['args'] : array()), (isset($option['args_append']) ? $option['args_append'] : array()));
            $this->setClass($class[0], array_merge($option, $class[1]));
        }
    }

    function newClass($class) {
        $mode = $this->class[$class][$this->CLASS_CACHE];
        $this->class[$class][$this->CLASS_CACHE] = false;
        $classInstance = $this->getClass($class);
        $this->class[$class][$this->CLASS_CACHE] = $mode;
        return $classInstance;
    }

    function resetClass($class) {
        $this->cache[$class][$this->CACHE_CLASS] = null;
    }

    function getClass($class) {
        $INDEX = 0;
        $COUNT = 1;

        $stack = array($class);
        $md = array();
        $resolved = array();
        $resolveClass = null;

        while (!empty($stack)) {
            $class = array_pop($stack);
            $classParent = end($stack);
            $stackSet[$classParent] = true;

            if (isset($stackSet[$class])) {
                trigger_error('500|Circular dependency detected: ' . implode(' -> ', $stack) . ' -> ' . $class, E_USER_WARNING);
                exit();
            }

            $cache = $this->class[$class][$this->CLASS_CACHE];
            if ($cache && isset($this->cache[$class][$this->CACHE_CLASS])) {
                unset($stackSet[$classParent]);
                if (empty($stack)) {
                    return $this->cache[$class][$this->CACHE_CLASS];
                }
                $resolved[$classParent][] = $this->cache[$class][$this->CACHE_CLASS];
                continue;
            }

            if (isset($this->class[$class][$this->CLASS_ARGS])) {
                if (!isset($md[$class])) {
                    $md[$class] = array(0, count($this->class[$class][$this->CLASS_ARGS]));
                }

                if ($md[$class][$COUNT] > $md[$class][$INDEX]) {
                    $stack[] = $class;
                    $stack[] = $this->classList[$this->class[$class][$this->CLASS_ARGS][$md[$class][$INDEX]]];
                    ++$md[$class][$INDEX];
                    continue;
                }

                unset($md[$class]);
            }

            unset($stackSet[$classParent]);

            if (!isset($this->cache[$class][$this->CACHE_PATH])) {
                require($this->ENV['DIR'] . (isset($this->class[$class][$this->CLASS_PATH]) && isset($this->pathList[$this->class[$class][$this->CLASS_PATH]]) ? $this->pathList[$this->class[$class][$this->CLASS_PATH]] : '') . (substr($class, ($pos = strrpos($class, '\\')) !== false ? $pos + 1 : 0)) . '.php');
                $this->cache[$class][$this->CACHE_PATH] = true;
            }

            $resolvedClass = new $class(isset($resolved[$class]) ? $resolved[$class] : array());
            unset($resolved[$class]);
            if ($cache) {
                $this->cache[$class][$this->CACHE_CLASS] = $resolvedClass;
            }

            $resolved[$classParent][] = $resolvedClass;
        }

        return $resolvedClass;
    }

    function loadClass($classes) {
        if (!isset($this->cache[$class][$this->CACHE_PATH])) {
            require($this->ENV['DIR'] . (isset($this->class[$class][$this->CLASS_PATH]) && isset($this->pathList[$this->class[$class][$this->CLASS_PATH]]) ? $this->pathList[$this->class[$class][$this->CLASS_PATH]] : '') . (substr($class, ($pos = strrpos($class, '\\')) !== false ? $pos + 1 : 0)) . '.php');
            $this->cache[$class][$this->CACHE_PATH] = true;
        }
    }

    function loadClasses($classes) {
        foreach ($classes as $class) {
            if (!isset($this->cache[$class][$this->CACHE_PATH])) {
                require($this->ENV['DIR'] . (isset($this->class[$class][$this->CLASS_PATH]) && isset($this->pathList[$this->class[$class][$this->CLASS_PATH]]) ? $this->pathList[$this->class[$class][$this->CLASS_PATH]] : '') . (substr($class, ($pos = strrpos($class, '\\')) !== false ? $pos + 1 : 0)) . '.php');
                $this->cache[$class][$this->CACHE_PATH] = true;
            }
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
            case 'view':
                return $this->ENV['DIR'] . $this->ENV['DIR_VIEW'] . $path;
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

    function urlEncode($url) {
        return urlencode(preg_replace('/\s+/', '-', strtolower($url)));
    }

    function log($message, $file) {
        $logFile = $this->ENV['DIR'] . $this->ENV['DIR_LOG'] . $file . '.log';
        $maxLogSize = $this->ENV['LOG_SIZE_LIMIT_MB'] * 1048576;
        $message = '[' . date('Y-m-d H:i:s') . '.' . sprintf('%06d', (int) ((microtime(true) - floor(microtime(true))) * 1000000)) . '] ' . $message . PHP_EOL;

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