<?php

class Pipe_Cli_Route {
    private $app;
    private $unitList;

    public function __construct($args = array()) {
        list($this->app) = $args;
        $this->unitList = $this->app->unitList ?? [];
    }

    public function pipe($request, $response) {
        $break = false;

        $routes = $this->app->routes;
        $pipesPrepend = $this->app->pipes['prepend'];
        $pipesAppend = $this->app->pipes['append'];

        $routes = $this->flattenRoutesWithMethod($routes);

        foreach ($routes as $no => $route) {
            $no++;
            $line = " - {$route['method']} {$route['path']}";

            $parts = [];

            if (!empty($route['run'])) {
                $run = array_map(fn($i) => $this->unitList[$i] ?? "[unit:$i]", array_merge($pipesPrepend, $route['run'], $pipesAppend));
                $parts[] = "run: " . implode(' > ', $run);
            }

            if (!empty($route['ignore'])) {
                $ignore = array_map(fn($i) => $this->unitList[$i] ?? "[unit:--global]", $route['ignore']);
                $parts[] = "ignore: " . implode(' > ', $ignore);
            }

            if (!empty($parts)) {
                $line .= ' → ' . implode(' | ', $parts);
            }

            $response->std($line . "\n");
        }

        return array($request, $response, $break);
    }

    private function flattenRoutesWithMethod(array $tree): array {
        $routes = [];

        foreach ($tree as $method => $branches) {
            $paths = $this->flattenRoutes($branches);

            foreach ($paths as $route) {
                $routes[] = ['method' => '"' . $method . '"'] + $route;
            }
        }

        return $routes;
    }

    private function flattenRoutes(array $tree, string $prefix = ''): array {
        $routes = [];

        foreach ($tree as $segment => $children) {
            if ($segment === '*' || $segment === '_i') {
                continue;
            }

            $currentPath = $prefix === '' ? $segment : $prefix . '/' . $segment;

            if (is_array($children)) {
                $childKeys = array_keys($children);
                $onlyMeta = empty(array_diff($childKeys, ['*']));

                if ($onlyMeta) {
                    $route = ['path' => '"' . $currentPath . '"'];

                    if (isset($children['*']['_p'])) {
                        $route['run'] = $children['*']['_p'];
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
