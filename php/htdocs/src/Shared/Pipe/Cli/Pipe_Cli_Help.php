<?php

class Pipe_Cli_Help {
    var $app;

    function args($args) {
        list($this->app) = $args;
    }

    function process($input, $output) {
        $success = true;
        $message = '';

        $routes = $this->flattenRoutesWithMethod($this->app->routes);

        $target = isset($input->query['autocomplete']) ? $input->query['autocomplete'] : null;
        $seen = array();

        if (!$target) {
            $route = isset($input->param['on-unknown-route']) ? $input->param['on-unknown-route'] : array('');
            $message .= 'No route \'' . $route[0] . '\' found, list:' . "\n";

            for ($i = 0; $i < count($routes); $i++) {
                $routeItem = $routes[$i];
                $pathParts = explode('/', $routeItem['path']);

                if (isset($seen[$pathParts[0]]) || substr($pathParts[0], 0, 1) === ':') {
                    continue;
                }
                $seen[$pathParts[0]] = true;

                if ($routeItem['method'] === '') {
                    $message .= ' Route \'' . str_replace('/', ' ', $pathParts[0]) . '\'' . "\n";
                }
            }
        } else {
            $matched = false;
            for ($i = 0; $i < count($routes); $i++) {
                $routeItem = $routes[$i];
                $pathParts = explode('/', $routeItem['path']);

                if ($pathParts[0] === $target && isset($pathParts[1])) {
                    if (isset($seen[$pathParts[1]]) || substr($pathParts[1], 0, 1) === ':') {
                        continue;
                    }
                    $seen[$pathParts[1]] = true;
                    $message .= ' ' . $pathParts[1] . "\n";
                    $matched = true;
                }
            }

            if (!$matched) {
                $message .= 'No sub-route found for \'' . $target . '\'' . "\n";
            }
        }

        $output->content = $message;

        return array($input, $output, $success);
    }

    function flattenRoutesWithMethod($tree) {
        $routes = array();

        foreach ($tree as $method => $branches) {
            $paths = $this->flattenRoutes($branches);
            sort($paths);

            foreach ($paths as $route) {
                $route['method'] = $method;
                $routes[] = $route;
            }
        }

        return $routes;
    }

    function flattenRoutes($tree, $prefix = '') {
        $routes = array();

        foreach ($tree as $segment => $children) {
            if ($segment === $this->app->ROUTE_HANDLER) {
                continue;
            }

            $currentPath = $prefix === '' ? $segment : $prefix . '/' . $segment;

            if (is_array($children)) {
                $childKeys = array_keys($children);
                $onlyMeta = empty(array_diff($childKeys, array($this->app->ROUTE_HANDLER)));

                if ($onlyMeta) {
                    $route = array('path' => $currentPath);

                    if (isset($children[$this->app->ROUTE_HANDLER])) {
                        $route['handler'] = $children[$this->app->ROUTE_HANDLER];
                    }

                    $routes[] = $route;
                } else {
                    $childRoutes = $this->flattenRoutes($children, $currentPath);
                    $routes = array_merge($routes, $childRoutes);
                }
            }
        }

        return $routes;
    }
}
