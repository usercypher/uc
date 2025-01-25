<?php

class App {
    private static $ENV;

    private static $CLASS_ARGS = 0;
    private static $CLASS_PATH = 1;
    private static $CLASS_CLASS_LIST_INDEX = 2;
    private static $CLASS_CACHE = 3;

    private static $CACHE_CLASS = 0;
    private static $CACHE_FILE = 1;

    private $routes = array();
    private $middlewares = array();

    private $class = array();
    private $classList = array();
    private $classListIndex = 0;
    private $pathList = array();
    private $pathListIndex = 0;
    private $pathListCache = array();
    private $cache = array();

    private $controller = '';
    private $action = '';
    private $params = array();
    private $finalMiddlewares = array();
    private $finalMiddlewaresIndex = 0;
    private $isRunning = false;

    // Application Setup

    public function __construct($request, $response) {
        self::$ENV['ROUTE_REWRITE'] = self::$ENV['ROUTE_REWRITE'];
        self::$ENV['ROUTE_MAIN_FILE'] = (isset(self::$ENV['ROUTE_MAIN_FILE']) ? self::$ENV['ROUTE_MAIN_FILE'] : 'index.php');
        self::$ENV['ROUTE_PARAM'] = (isset(self::$ENV['ROUTE_PARAM']) ? self::$ENV['ROUTE_PARAM'] : 'route');
        self::$ENV['DIR'] = self::$ENV['DIR'];
        self::$ENV['DIR_RELATIVE'] = self::$ENV['DIR_RELATIVE'];
        
        self::$ENV['HTTP_PROTOCOL'] = isset(self::$ENV['HTTP_PROTOCOL']) ? self::$ENV['HTTP_PROTOCOL'] : 'http';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            self::$ENV['HTTP_PROTOCOL'] = 'https';
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            self::$ENV['HTTP_PROTOCOL'] = 'https';
        }
        self::$ENV['HTTP_HOST'] = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset(self::$ENV['HTTP_HOST']) ? self::$ENV['HTTP_HOST'] : '127.0.0.1');

        self::$ENV['BASE_URL'] = self::$ENV['HTTP_PROTOCOL'] . '://' . self::$ENV['HTTP_HOST'];

        self::$ENV['SHOW_ERRORS'] = self::$ENV['SHOW_ERRORS'];
        self::$ENV['CONFIG_FILE'] = (isset(self::$ENV['CONFIG_FILE']) && self::$ENV['CONFIG_FILE'] !== '') ? self::$ENV['CONFIG_FILE'] : 'core/runtime/var/app.config';
        self::$ENV['ERROR_LOG_FILE'] = (isset(self::$ENV['ERROR_LOG_FILE']) && self::$ENV['ERROR_LOG_FILE'] !== '') ? self::$ENV['ERROR_LOG_FILE'] : 'core/runtime/log/app.error';
        self::$ENV['ERROR_LOG_SIZE_LIMIT_MB'] = (isset(self::$ENV['ERROR_LOG_SIZE_LIMIT_MB']) && (int)self::$ENV['ERROR_LOG_SIZE_LIMIT_MB'] >= 5) ? (int)self::$ENV['ERROR_LOG_SIZE_LIMIT_MB'] : 5;

        set_error_handler(array('App', 'errorHandler'));
        set_exception_handler(array('App', 'exceptionHandler'));

        $this->class = array(
            'App' => array(null, null, 0, true),
            'Request' => array(null, null, 1, true),
            'Response' => array(null, null, 2, true),
        );

        $this->cache = array(
            'App' => array($this, true),
            'Request' => array($request, true),
            'Response' => array($response, true),
        );

        $this->classList = array('App', 'Request', 'Response');
        $this->classListIndex = 3;
    }

    public static function setEnv($variable, $value) {
        self::$ENV[$variable] = $value;
    }

    public static function setEnvs($variables) {
        foreach ($variables as $var => $value) {
            self::$ENV[$var] = $value;
        }
    }

    public static function getEnv($variable) {
        return isset(self::$ENV[$variable]) ? self::$ENV[$variable] : null ;
    }

    public static function setIni($variables) {
        foreach ($variables as $var => $value) {
            if (ini_set($var, $value) === false) {
                self::logError('Failed to set ini setting: ' . $var);
            }
        }
    }

    // Route Management

    public function setRoute($method, $route, $action, $option) {
        if (!isset($this->class[$option['controller']])) {
            $this->class[$option['controller']] = array(null, null, $this->classListIndex, false);
            $this->classList[$this->classListIndex] = $option['controller'];
            ++$this->classListIndex;
        }

        $middleware = array();
        if (isset($option['middleware'])) {
            foreach ($option['middleware'] as $class) {
                if (!isset($this->class[$class])) {
                    $this->class[$class] = array(null, null, $this->classListIndex, false);
                    $this->classList[$this->classListIndex] = $class;
                    ++$this->classListIndex;
                }
                $middleware[] = $this->class[$class][self::$CLASS_CLASS_LIST_INDEX];
            }
        }

        $ignore = array();
        if (isset($option['ignore'])) {
            foreach ($option['ignore'] as $class) {
                if (!isset($this->class[$class])) {
                    $this->class[$class] = array(null, null, $this->classListIndex, false);
                    $this->classList[$this->classListIndex] = $class;
                    ++$this->classListIndex;
                }
                $ignore[] = $this->class[$class][self::$CLASS_CLASS_LIST_INDEX];
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

        $node['_h'] = array('_a' => $action, '_c' => $this->class[$option['controller']][self::$CLASS_CLASS_LIST_INDEX], '_m' => $middleware, '_i' => $ignore);
    }

    public function setRoutes($option, $params) {
        foreach ($params as $p) {
            $this->setRoute($p[0], (isset($option['prefix']) ? $option['prefix'] : '') . $p[1], $p[2], isset($p[3]) ? array_merge($option, $p[3]) : $option);
        }
    }

    public function setMiddlewares($middlewares) {
        foreach ($middlewares as $class) {
            if (!isset($this->class[$class])) {
                $this->class[$class] = array(null, null, $this->classListIndex, false);
                $this->classList[$this->classListIndex] = $class;
                ++$this->classListIndex;
            }
            $this->middlewares[] = $this->class[$class][self::$CLASS_CLASS_LIST_INDEX];
        }
    }

    private function resolveRoute($method, $path) {
        $current = $this->routes[$method];
        $params = array();
        $pathSegments = explode('/', trim($path, '/'));

        foreach ($pathSegments as $index => $pathSegment) {
            if (isset($current[$pathSegment])) {
                $current = $current[$pathSegment];
                continue;
            }

            $matched = false;

            foreach ($current as $key => $value) {
                if (strpos($key, '{') !== false && strpos($key, '}') !== false) {
                    $param = trim($key, '{}');
                    $paramParts = explode(':', $param);
                    $paramName = $paramParts[0];
                    $paramRegex = (isset($paramParts[1])) ? $paramParts[1] : '';
                    $paramModifier = substr($paramName, -1);
                    if ($paramModifier === '*') {
                        if (!isset($value['_h'])) {
                            return null;
                        }
                        $params[rtrim($paramName, '*')] = array_slice($pathSegments, $index);
                        $current = $value;
                        break 2;
                    }
                    if ($paramModifier === '?' && preg_match('/' . $paramRegex . '/', $pathSegment)) {
                        $params[rtrim($paramName, '?')] = $pathSegment;
                        $current = $value;
                        $matched = true;
                        break;
                    }
                    if (preg_match('/' . $paramRegex . '/', $pathSegment)) {
                        $params[$paramName] = $pathSegment;
                        $current = $value;
                        $matched = true;
                        break;
                    }
                }
            }

            if (!$matched) {
                return null;
            }
        }

        while (!isset($current['_h'])) {
            $matched = false;

            foreach ($current as $key => $value) {
                if (strpos($key, '{') !== false && strpos($key, '}') !== false) {
                    $param = trim($key, '{}');
                    $paramParts = explode(':', $param);
                    $paramModifier = substr($paramParts[0], -1);
                    $current = $value;
                    if ($paramModifier === '*') {
                        if (!isset($value['_h'])) {
                            return null;
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
                return null;
            }
        }

        return (isset($current['_h'])) ? array('handler' => $current['_h'], 'params' => $params) : null;
    }

    // Request Handling

    public function run() {
        if ($this->isRunning) {
            return;
        }

        $this->isRunning = true;
        $request = $this->cache['Request'][self::$CACHE_CLASS];
        $response = $this->cache['Response'][self::$CACHE_CLASS];
        $parseUrl = parse_url($request->uri);
        $path = self::$ENV['ROUTE_REWRITE'] ? $parseUrl['path'] : (isset($request->get[self::$ENV['ROUTE_PARAM']]) ? $request->get[self::$ENV['ROUTE_PARAM']] : '/');

        $route = $this->resolveRoute($request->method, $path);

        $this->routes = null;

        if (!isset($route)) {
            throw new Exception('Route not found: ' . $path, 404);
        }

        if ($route['handler']['_i'] !== array(true)) {
            $seen = array();
            $ignore = array_flip($route['handler']['_i']);

            foreach ($this->middlewares as $middleware) {
                if (!isset($ignore[$middleware]) && !isset($seen[$middleware])) {
                    $this->finalMiddlewares[] = $middleware;
                    $seen[$middleware] = true;
                }
            }

            foreach ($route['handler']['_m'] as $middleware) {
                if (!isset($ignore[$middleware]) && !isset($seen[$middleware])) {
                    $this->finalMiddlewares[] = $middleware;
                    $seen[$middleware] = true;
                }
            }
        }

        $this->controller = $route['handler']['_c'];
        $this->action = $route['handler']['_a'];
        $this->params = $route['params'];

        $response = $this->process($request, $response, $this);
        $response->send();
    }

    public function process($request, $response, $app) {
        if (isset($this->finalMiddlewares[$this->finalMiddlewaresIndex])) {
            ++$this->finalMiddlewaresIndex;
            $middleware = $this->resolveClass($this->classList[$this->finalMiddlewares[$this->finalMiddlewaresIndex - 1]], array(), $this->class[$this->classList[$this->finalMiddlewares[$this->finalMiddlewaresIndex - 1]]][self::$CLASS_CACHE]);
            return $middleware->process($request, $response, $app);
        }
        $this->class[$this->classList[$this->controller]][self::$CLASS_ARGS][] = $this->class['Request'][self::$CLASS_CLASS_LIST_INDEX];
        $this->class[$this->classList[$this->controller]][self::$CLASS_ARGS][] = $this->class['Response'][self::$CLASS_CLASS_LIST_INDEX];
        $controller = $this->resolveClass($this->classList[$this->controller], array(), $this->class[$this->classList[$this->controller]][self::$CLASS_CACHE]);
        return $controller-> {
            $this->action
        }($this->params);
    }

    // Dependency Management

    public function setFile($class, $path) {
        if (!isset($this->class[$class])) {
            $this->class[$class] = array(null, null, $this->classListIndex, false);
            $this->classList[$this->classListIndex] = $class;
            ++$this->classListIndex;
        }

        $pathListIndex = isset($this->pathListCache[$path]) ? $this->pathListCache[$path] : array_search($path, $this->pathList);

        if ($pathListIndex === false) {
            $pathListIndex = $this->pathListIndex;
            $this->pathList[$this->pathListIndex] = $path;
            ++$this->pathListIndex;
        }

        $this->pathListCache[$path] = $pathListIndex;
        $this->class[$class][self::$CLASS_PATH] = $pathListIndex;
    }

    public function setFiles($path, $classes) {
        foreach ($classes as $class) {
            $this->setFile($class, $path);
        }
    }

    public function autoSetFiles($path, $option) {
        $option = array(
            'depth' => isset($option['depth']) ? $option['depth'] : 0,
            'max' => isset($option['max']) ? $option['max'] : 0,
            'ignore' => isset($option['ignore']) ? $option['ignore'] : array(),
            'include' => isset($option['include']) ? $option['include'] : false,
            'namespace' => isset($option['namespace']) ? $option['namespace'] : false,
            'namespaceStack' => isset($option['namespaceStack']) ? $option['namespaceStack'] : '',
        );

        if ($dirHandle = opendir(self::$ENV['DIR'] . $path)) {
            while (($file = readdir($dirHandle)) !== false) {
                if ($file === '.' || $file === '..' || in_array($file, $option['ignore'])) {
                    continue;
                }
                if (($option['max'] === -1 || $option['max'] > $option['depth']) && is_dir(self::$ENV['DIR'] . $path . $file)) {
                    $previousNamespace = $option['namespaceStack'];
                    $option['namespaceStack'] = $option['namespaceStack'] . $file . '\\';
                    ++$option['depth'];
                    $this->autoSetFiles($path . $file . '/', $option);
                    --$option['depth'];
                    $option['namespaceStack'] = $previousNamespace;
                } else if (substr($file, -4) === '.php') {
                    $class = ($option['namespace'] ? $option['namespaceStack'] : '')  . substr($file, 0, -4);
                    $this->setFile($class, $path);
                    if ($option['include']) {
                        $this->loadClass($class);
                    }
                }
            }
            closedir($dirHandle);
        }
    }

    public function setClass($class, $option) {
        if (!isset($this->class[$class])) {
            $this->class[$class] = array(null, null, $this->classListIndex, false);
            $this->classList[$this->classListIndex] = $class;
            ++$this->classListIndex;
        }
        $this->class[$class][self::$CLASS_CACHE] = (isset($option['cache']) ? $option['cache'] : $this->class[$class][self::$CLASS_CACHE]);
        if (isset($option['args'])) {
            foreach ($option['args'] as $arg) {
                if (!isset($this->class[$arg])) {
                    $this->class[$arg] = array(null, null, $this->classListIndex, false);
                    $this->classList[$this->classListIndex] = $arg;
                    ++$this->classListIndex;
                }
                $this->class[$class][self::$CLASS_ARGS][] = $this->class[$arg][self::$CLASS_CLASS_LIST_INDEX];
            }
        }
    }

    public function setClasses($option, $classes) {
        foreach ($classes as $class) {
            $this->setClass($class, (isset($class[1]) && is_array($class[1])) ? $class[1] : $option);
        }
    }

    private function resolveClass($class, $resolvedStack, $cache) {
        if ($cache && isset($this->cache[$class][self::$CACHE_CLASS])) {
            return $this->cache[$class][self::$CACHE_CLASS];
        }

        if (isset($resolvedStack[$class])) {
            throw new Exception('Circular dependency detected: ' . implode(' -> ', array_keys($resolvedStack)) . ' -> ' . $class, 500);
        }

        $resolvedStack[$class] = true;
        $resolved = array();
        if (isset($this->class[$class][self::$CLASS_ARGS])) {
            foreach ($this->class[$class][self::$CLASS_ARGS] as $argsIndex) {
                $resolved[$this->classList[$argsIndex]] = $this->resolveClass($this->classList[$argsIndex], $resolvedStack, $this->class[$this->classList[$argsIndex]][self::$CLASS_CACHE]);
            }
        }
        unset($resolvedStack[$class]);

        $this->loadClass($class);
        $resolvedClass = new $class($resolved);
        if ($cache) {
            $this->cache[$class][self::$CACHE_CLASS] = $resolvedClass;
        }

        return $resolvedClass;
    }

    private function loadClass($class) {
        if (!isset($this->cache[$class][self::$CACHE_FILE])) {
            require(self::$ENV['DIR'] . (isset($this->class[$class][self::$CLASS_PATH]) && isset($this->pathList[$this->class[$class][self::$CLASS_PATH]]) ? $this->pathList[$this->class[$class][self::$CLASS_PATH]] : '') . (substr($class, ($pos = strrpos($class, '\\')) !== false ? $pos + 1 : 0)) . '.php');
            $this->cache[$class][self::$CACHE_FILE] = true;
        }
    }

    // Error Handling

    public static function errorHandler($errno, $errstr, $errfile, $errline) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    public static function exceptionHandler($e) {
        header('HTTP/1.1 ' . $e->getCode());

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            exit(json_encode(array('error' => true, 'message' => $e->getMessage(), 'code' => $e->getCode(), 'file' => $e->getFile(), 'line' => $e->getLine())));
        } else {
            if (self::$ENV['SHOW_ERRORS']) {
                exit(nl2br($e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine() . PHP_EOL . PHP_EOL . $e->getTraceAsString()));
            } else {
                self::logError($e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
                $file = self::$ENV['DIR'] . 'core/view/' . $e->getCode() . '.php';
                exit(include(file_exists($file) ? $file : self::$ENV['DIR'] . 'core/view/default.php'));
            }
        }
    }

    private static function logError($logMessage) {
        $logFile = self::$ENV['DIR'] . self::$ENV['ERROR_LOG_FILE'] . '.log';
        $maxLogSize = self::$ENV['ERROR_LOG_SIZE_LIMIT_MB'] * 1048576;
        $logMessage = '[' . date('Y-m-d H:i:s') . '] ' . $logMessage . PHP_EOL;

        if (file_exists($logFile) && filesize($logFile) >= $maxLogSize) {
            $newLogFile = self::$ENV['DIR'] . self::$ENV['ERROR_LOG_FILE'] . '_' . date('Y-m-d_H-i-s') . '.log';
            rename($logFile, $newLogFile);
        }

        file_put_contents($logFile, $logMessage, FILE_APPEND);

        $cleanupIntervalDays = 1;
        $logRetentionDays = 7;
        $maxLogFiles = 10;
    
        $timestampFile = self::$ENV['DIR'] . 'core/runtime/var/last_log_cleanup_timestamp.txt';
        $now = time();
        $lastCleanup = file_exists($timestampFile) ? (int)file_get_contents($timestampFile) : 0;

        if (($now - $lastCleanup) >= $cleanupIntervalDays * 86400) {
            $logFiles = glob(self::$ENV['DIR'] . self::$ENV['ERROR_LOG_FILE'] . '_*.log');
            $modTimes = array();
            foreach ($logFiles as $file) {
                $modTimes[] = filemtime($file);
            }

            array_multisort($modTimes, SORT_ASC, $logFiles);

            if (count($logFiles) > $maxLogFiles) {
                $filesToDelete = array_slice($logFiles, 0, count($logFiles) - $maxLogFiles);
                foreach ($filesToDelete as $file) {
                    unlink($file);
                }
                $logFiles = glob(self::$ENV['DIR'] . self::$ENV['ERROR_LOG_FILE'] . '_*.log');
            }

            foreach ($logFiles as $file) {
                if (($now - filemtime($file)) > ($logRetentionDays * 86400)) {
                    unlink($file);
                }
            }

            file_put_contents($timestampFile, $now);
        }
    }

    // Config Management

    public function saveConfig() {
        if (!is_dir(dirname($this->configFile()))) {
            mkdir(dirname($this->configFile()), 0777, true);
        }

        file_put_contents($this->configFile(), json_encode(array(
            'routes' => $this->routes,
            'middlewares' => $this->middlewares,
            'class' => $this->class,
            'class_list' => $this->classList,
            'class_list_index' => $this->classListIndex,
            'path_list' => $this->pathList,
            'path_list_index' => $this->pathListIndex
        )));

        exit('File created: ' . $this->configFile());
    }

    public function loadConfig() {
        if (file_exists($this->configFile())) {
            $data = json_decode(file_get_contents($this->configFile()), true);
            $this->routes = $data['routes'];
            $this->middlewares = $data['middlewares'];
            $this->class = $data['class'];
            $this->classList = $data['class_list'];
            $this->classListIndex = $data['class_list_index'];
            $this->pathList = $data['path_list'];
            $this->pathListIndex = $data['path_list_index'];
        } else {
            throw new Exception('File not found: ' . $this->configFile(), 404);
        }
    }

    public function configFile() {
        return self::$ENV['DIR'] . self::$ENV['CONFIG_FILE'] . '.json';
    }

    // Utility Functions

    public function newClass($class) {
        if (isset($this->class[$class])) {
            return $this->resolveClass($class, array(), false);
        }
    }

    public function cacheClass($class) {
        if (isset($this->class[$class])) {
            return $this->resolveClass($class, array(), true);
        }
    }

    public function resetClass($class) {
        if (isset($this->class[$class])) {
            unset($this->cache[$class][self::$CACHE_CLASS]);
        }
    }

    public function loadClasses($classes) {
        foreach ($classes as $class) {
            $this->loadClass($class);
        }
    }

    public static function buildPath($path) {
        return self::$ENV['DIR'] . $path;
    }

    public static function buildLink($option, $link) {
        switch ($option) {
            case 'route':
                return self::$ENV['BASE_URL'] . (self::$ENV['ROUTE_REWRITE'] ? $link : ('/' . self::$ENV['ROUTE_MAIN_FILE'] . '?' . self::$ENV['ROUTE_PARAM'] . '=' . $link));
            case 'relative':
                return self::$ENV['DIR_RELATIVE'] . $link;
            case 'absolute':
                return self::$ENV['BASE_URL'] . $link;
            default:
                return $link;
        }
    }

    public static function validateArray($message, $keys, $requiredKeys) {
        $missingKeys = array();

        foreach ($requiredKeys as $key) {
            if (!isset($keys[$key]) || $keys[$key] === null) {
                $missingKeys[] = $key;
            }
        }

        if (!empty($missingKeys)) {
            throw new Exception($message . implode(', ', $missingKeys), 500);
        }
    }
}