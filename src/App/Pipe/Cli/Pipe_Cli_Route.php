<?php

class Pipe_Cli_Route {
    private $app;
    private $unitList;

    public function args($args) {
        list(
            $this->app
        ) = $args;
        $this->unitList = isset($this->app->unitList) ? $this->app->unitList : array();
    }

    public function pipe($request, $response) {
        $break = false;

        $option = isset($request->params['option']) ? $request->params['option'] : null;

        switch ($option) {
            case 'print':
                list($request, $response) = $this->print($request, $response);
                break;
            case 'resolve':
                list($request, $response) = $this->resolve($request, $response);
                break;
            default:
                $response->std('Error: Usage - php [file] route [option:eg. print, resolve]' . EOL, true);
        }

        return array($request, $response, $break);
    }

    private function resolve($request, $response) {
        if (!isset($request->cli['option']['type']) || !isset($request->cli['option']['path'])) {
            $response->std('Error: Required - --type=[value: GET, POST, eg.] --path=[value]' . EOL, true);
            return array($request, $response);
        }
        $result = $this->app->resolveRoute($request->cli['option']['type'], $request->cli['option']['path']);

        if (isset($result['error'])) {
            $response->std('Route Error [http ' . $result['http'] . ']: ' . $result['error'] . EOL, true);
            return array($request, $response);
        }

        $output['pipe'] = array();
        foreach ($result['pipe'] as $index) {
            $output['pipe'][] = $this->unitList[$index];
        }
        $output['dynamic_url_parameter'] = $result['params'];

        $response->std(print_r($output, true));

        return array($request, $response);
    }

    private function print($request, $response) {
        $routes = $this->app->routes;
        $pipesPrepend = $this->app->pipes['prepend'];
        $pipesAppend = $this->app->pipes['append'];

        $routes = $this->flattenRoutesWithMethod($routes);

        foreach ($routes as $no => $route) {
            $no++;
            $line = " - {$route['method']} {$route['path']}";

            $parts = array();

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

        return array($request, $response);
    }

    private function flattenRoutesWithMethod(array $tree): array {
        $routes = array();

        foreach ($tree as $method => $branches) {
            $paths = $this->flattenRoutes($branches);

            foreach ($paths as $route) {
                $routes[] = ['method' => '"' . $method . '"'] + $route;
            }
        }

        return $routes;
    }

    private function flattenRoutes(array $tree, string $prefix = ''): array {
        $routes = array();

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
