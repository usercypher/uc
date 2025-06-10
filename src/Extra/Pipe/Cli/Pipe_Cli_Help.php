<?php

class Pipe_Cli_Help {
    private $app;

    public function args($args) {
        list(
            $this->app,
        ) = $args;
    } 

    public function pipe($request, $response) {
        $break = false;

        $output = '';

        $routes = $this->flattenRoutesWithMethod($this->app->routes);

        sort($routes);

        $target = isset($request->cli['option']['autocomplete']) ? $request->cli['option']['autocomplete'] : null;

        $seen = array();
        if (!$target || $target === true) {
            $output .= 'No route \'' . trim(urldecode(str_replace('/', ' ', $request->path))) . '\' found, list:'. EOL;
            foreach ($routes as $route) {
                $pathParts = explode('/', $route['path']);
                if (isset($seen[$pathParts[0]]) || substr($pathParts[0], 0, 1) === '{') continue;
                $seen[$pathParts[0]] = true;
                if ($route['method'] === '') $output .= ' Route \'' . str_replace('/', ' ', $pathParts[0]) . '\'' . EOL;
            }
       } else {
            $matched = false;
            foreach ($routes as $route) {
                $pathParts = explode('/', $route['path']);
                if ($pathParts[0] === $target && isset($pathParts[1])) {
                    if (isset($seen[$pathParts[1]]) || substr($pathParts[1], 0, 1) === '{') continue;
                    $seen[$pathParts[1]] = true;
                    $output .= ' ' . $pathParts[1] . EOL;
                    $matched = true;
                }
            }
            if (!$matched) {
                $output .= 'No sub-route found for \'' . $target . '\'' . EOL;
            }
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
