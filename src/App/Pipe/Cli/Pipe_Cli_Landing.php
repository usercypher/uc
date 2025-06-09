<?php

class Pipe_Cli_Landing {
    private $app;

    public function args($args) {
        list(
            $this->app,
        ) = $args;
    } 

    public function pipe($request, $response) {
        $break = false;

        $output = 'No route \'' . trim(urldecode(str_replace('/', ' ', $request->path))) . '\' found, list:'. EOL;

        $routes = $this->flattenRoutesWithMethod($this->app->routes);
        foreach ($routes as $route) {
            if ($route['method'] === '') $output .= ' Route \'' . str_replace('/', ' ', $route['path']) . '\'' . EOL;
        }

        $response->std($output, true);

        return array($request, $response, $break);
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
            if ($segment === '*' || $segment === '_i') {
                continue;
            }

            $currentPath = $prefix === '' ? $segment : $prefix . '/' . $segment;

            if (is_array($children)) {
                $childKeys = array_keys($children);
                $onlyMeta = empty(array_diff($childKeys, array('*')));

                if ($onlyMeta) {
                    $route = array('path' => $currentPath);

                    if (isset($children['*']['_p'])) {
                    $route['pipe'] = $children['*']['_p'];
                    }

                    if (isset($children['*']['_i'])) {
                        $route['ignore'] = $children['*']['_i'];
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
