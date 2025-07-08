<?php

class Pipe_Cli_Help {
    private $app;

    public function args($args) {
        list(
            $this->app,
        ) = $args;
    } 

    public function process($input, $output) {
        $success = true;

        $message = '';

        $routes = $this->flattenRoutesWithMethod($this->app->routes);

        sort($routes);

        $target = $input->getFrom($input->options, 'autocomplete');

        $seen = array();
        if (!$target) {
            $route = $input->getFrom($input->params, 'on-unknown-route', array(''));
            $message .= 'No route \'' . $route[0] . '\' found, list:'. EOL;
            foreach ($routes as $route) {
                $pathParts = explode('/', $route['path']);
                if (isset($seen[$pathParts[0]]) || substr($pathParts[0], 0, 1) === ':') continue;
                $seen[$pathParts[0]] = true;
                if ($route['method'] === '') $message .= ' Route \'' . str_replace('/', ' ', $pathParts[0]) . '\'' . EOL;
            }
       } else {
            $matched = false;
            foreach ($routes as $route) {
                $pathParts = explode('/', $route['path']);
                if ($pathParts[0] === $target && isset($pathParts[1])) {
                    if (isset($seen[$pathParts[1]]) || substr($pathParts[1], 0, 1) === ':') continue;
                    $seen[$pathParts[1]] = true;
                    $message .= ' ' . $pathParts[1] . EOL;
                    $matched = true;
                }
            }
            if (!$matched) {
                $message .= 'No sub-route found for \'' . $target . '\'' . EOL;
            }
        }

        $output->content = $message;
        $output->code = 1;

        return array($input, $output, $success);
    }

    private function flattenRoutesWithMethod($tree) {
        $routes = array();

        foreach ($tree as $method => $branches) {
            $paths = $this->flattenRoutes($branches);

            foreach ($paths as $route) {
                $routes[] = array('method' => $method) + $route;
            }
        }

        return $routes;
    }

    private function flattenRoutes($tree, $prefix = '') {
        $routes = array();

        foreach ($tree as $segment => $children) {
            if ($segment === $this->app->ROUTE_HANDLER || $segment === $this->app->ROUTE_HANDLER_IGNORE) {
                continue;
            }

            $currentPath = $prefix === '' ? $segment : $prefix . '/' . $segment;

            if (is_array($children)) {
                $childKeys = array_keys($children);
                $onlyMeta = empty(array_diff($childKeys, array($this->app->ROUTE_HANDLER)));

                if ($onlyMeta) {
                    $route = array('path' => $currentPath);

                    if (isset($children[$this->app->ROUTE_HANDLER][$this->app->ROUTE_HANDLER_PIPE])) {
                        $route['pipe'] = $children[$this->app->ROUTE_HANDLER][$this->app->ROUTE_HANDLER_PIPE];
                    }

                    if (isset($children[$this->app->ROUTE_HANDLER][$this->app->ROUTE_HANDLER_IGNORE])) {
                        $route['ignore'] = $children[$this->app->ROUTE_HANDLER][$this->app->ROUTE_HANDLER_IGNORE];
                    }

                    $routes[] = $route;
                } else {
                    $routes = array_merge($routes, $this->flattenRoutes($children, $currentPath));
                }
            }
        }

        return $routes;
    }
}
