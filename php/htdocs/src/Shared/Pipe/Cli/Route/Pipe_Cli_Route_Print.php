<?php

class Pipe_Cli_Route_Print {
    var $app;

    function args($args) {
        list(
            $this->app
        ) = $args;
    }

    function process($input, $output) {
        $success = true;

        $unitList = isset($this->app->unitList) ? $this->app->unitList : array();
        $routes = $this->app->routes;
        $pipesPrepend = $this->app->pipes['prepend'];
        $pipesAppend = $this->app->pipes['append'];

        $routes = $this->flattenRoutesWithMethod($routes);

        $message = "ROUTES" . "\n";

        foreach ($routes as $no => $route) {
            $no++;
            $line = '  ' . str_pad('\'' . $route['method'] . '\'', 6) . ' \'' . $route['path'] . '\'';

            $parts = array();

            if (!empty($pipesPrepend)) {
                $prepend = array();
                foreach ($pipesPrepend as $i) {
                    $prepend[] = $unitList[$i];
                }

                $parts[] = 'prepend: ' . implode(' > ', $prepend);
            }

            if (!empty($pipesAppend)) {
                $append = array();
                foreach ($pipesAppend as $i) {
                    $append[] = $unitList[$i];
                }

                $parts[] = 'append: ' . implode(' > ', $append);
            }

            if (!empty($route['pipe'])) {
                $pipe = array();
                foreach ($route['pipe'] as $i) {
                    $pipe[] = $unitList[$i];
                }

                $parts[] = 'pipe: ' . implode(' > ', $pipe);
            }

            if (!empty($route['ignore'])) {
                $ignore = array();
                foreach ($route['ignore'] as $i) {
                    $ignore[] = isset($unitList[$i]) ? $unitList[$i] : '--global';
                }

                $parts[] = 'ignore: ' . implode(' > ', $ignore);
            }

            if (!empty($parts)) {
                $line .= ' → ' . implode(' | ', $parts);
            }

            $message .= $line . "\n";
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
                $route['method'] = $method; // assignment instead of '+'
                $routes[] = $route;
            }
        }

        return $routes;
    }

    function flattenRoutes($tree, $prefix = '') {
        $routes = array();

        foreach ($tree as $segment => $children) {
            if ($segment === $this->app->ROUTE_HANDLER || $segment === $this->app->ROUTE_HANDLER_IGNORE) {
                continue;
            }

            $currentPath = ($prefix === '') ? $segment : $prefix . '/' . $segment;

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
                    $childRoutes = $this->flattenRoutes($children, $currentPath);
                    $routes = array_merge($routes, $childRoutes);
                }
            }
        }

        return $routes;
    }
}
