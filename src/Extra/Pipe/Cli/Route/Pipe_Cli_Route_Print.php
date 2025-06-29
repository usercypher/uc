<?php

class Pipe_Cli_Route_Print {
    private $app;

    public function args($args) {
        list(
            $this->app
        ) = $args;
    }

    public function pipe($input, $output) {
        $break = false;

        $unitList = isset($this->app->unitList) ? $this->app->unitList : array();
        $routes = $this->app->routes;
        $pipesPrepend = $this->app->pipes['prepend'];
        $pipesAppend = $this->app->pipes['append'];

        $routes = $this->flattenRoutesWithMethod($routes);

        sort($routes);

        $message = "ROUTES" . EOL;

        foreach ($routes as $no => $route) {
            $no++;
            $line = '  ' . str_pad('\'' . $route['method'] . '\'', 6) . ' \'' . $route['path'] . '\'';

            $parts = array();

            if (!empty($pipesPrepend)) {
                $prepend = array();
                foreach (array_merge($pipesPrepend) as $i) {
                    $prepend[] = $unitList[$i];
                }

                $parts[] = 'prepend: ' . implode(' > ', $prepend);
            }

            if (!empty($pipesAppend)) {
                $prepend = array();
                foreach (array_merge($pipesAppend) as $i) {
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
                $ignore = [];
                foreach ($route['ignore'] as $i) {
                    $ignore[] = isset($unitList[$i]) ? $unitList[$i] : '--global';
                }

                $parts[] = 'ignore: ' . implode(' > ', $ignore);
            }

            if (!empty($parts)) {
                $line .= ' → ' . implode(' | ', $parts);
            }

            $message .= $line . EOL;
        }

        $output->content = $message;

        return array($input, $output, $break);
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
            if ($segment === '*' || $segment === $this->app->ROUTE_IGNORE) {
                continue;
            }

            $currentPath = $prefix === '' ? $segment : $prefix . '/' . $segment;

            if (is_array($children)) {
                $childKeys = array_keys($children);
                $onlyMeta = empty(array_diff($childKeys, array('*')));

                if ($onlyMeta) {
                    $route = array('path' => $currentPath);

                    if (isset($children['*'][$this->app->ROUTE_PIPE])) {
                    $route['pipe'] = $children['*'][$this->app->ROUTE_PIPE];
                    }

                    if (isset($children['*'][$this->app->ROUTE_IGNORE])) {
                        $route['ignore'] = $children['*'][$this->app->ROUTE_IGNORE];
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
