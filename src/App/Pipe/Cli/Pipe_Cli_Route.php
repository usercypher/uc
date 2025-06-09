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
                $output = 'Error: Missing or unknown option \'' . $option . '\'.'. EOL;
                $output .= 'Usage: php [file] route [option]' . EOL;
                $output .= 'Options:' . EOL;
                $output .= '  print    Show all defined routes' . EOL;
                $output .= '  resolve  Simulate resolving a request using --type and --path' . EOL;
                $response->std($output, true);
        }

        return array($request, $response, $break);
    }

    private function resolve($request, $response) {
        $output = '';
        if (!isset($request->cli['option']['type']) || !isset($request->cli['option']['path'])) {
            $output .= 'Error: Missing required parameters.' . EOL;
            $output .= 'Usage: --type=GET|POST --path=/route/path' . EOL;
            $response->std($output, true);
            return array($request, $response);
        }
        $result = $this->app->resolveRoute($request->cli['option']['type'], $request->cli['option']['path']);

        if (isset($result['error'])) {
            $output .= 'Route Error [http ' . $result['http'] . ']: ' . $result['error'] . EOL;
            return array($request, $response);
        }
        $output .= 'RESOLVED ROUTE' . EOL;
        $output .= '  Method : ' . $request->cli['option']['type'] . EOL;
        $output .= '  Path   : ' . $request->cli['option']['path'] . EOL;

        $output .= '  Pipe   :' . EOL;
        foreach ($result['pipe'] as $i => $index) {
            $output .= '    #' . str_pad($i, 2, ' ', STR_PAD_LEFT) . '  ' . $this->unitList[$index] . EOL;
        }

        // Show dynamic params if any
        if (!empty($result['params'])) {
            $output .= '  Params :' . EOL;
            foreach ($result['params'] as $key => $value) {
                $output .= '    ' . str_pad($key, 12) . ' = ' . (is_array($value) ? print_r($value, true) : $value) . EOL;
            }
        }

        $response->std($output);

        return array($request, $response);
    }

    private function print($request, $response) {
        $routes = $this->app->routes;
        $pipesPrepend = $this->app->pipes['prepend'];
        $pipesAppend = $this->app->pipes['append'];

        $routes = $this->flattenRoutesWithMethod($routes);

        $response->std("ROUTES" . EOL);

        foreach ($routes as $no => $route) {
            $no++;
            $line = '  ' . str_pad('\'' . $route['method'] . '\'', 6) . ' \'' . $route['path'] . '\'';

            $parts = array();

            if (!empty($pipesPrepend)) {
                $prepend = array();
                foreach (array_merge($pipesPrepend) as $i) {
                    $prepend[] = $this->unitList[$i];
                }

                $parts[] = 'prepend: ' . implode(' > ', $prepend);
            }

            if (!empty($pipesAppend)) {
                $prepend = array();
                foreach (array_merge($pipesAppend) as $i) {
                    $append[] = $this->unitList[$i];
                }

                $parts[] = 'append: ' . implode(' > ', $append);
            }

            if (!empty($route['pipe'])) {
                $pipe = array();
                foreach ($route['pipe'] as $i) {
                    $pipe[] = $this->unitList[$i];
                }

                $parts[] = 'pipe: ' . implode(' > ', $pipe);
            }            

            if (!empty($route['ignore'])) {
                $ignore = [];
                foreach ($route['ignore'] as $i) {
                    $ignore[] = isset($this->unitList[$i]) ? $this->unitList[$i] : '--global';
                }

                $parts[] = 'ignore: ' . implode(' > ', $ignore);
            }

            if (!empty($parts)) {
                $line .= ' → ' . implode(' | ', $parts);
            }

            $response->std($line . EOL);
        }

        return array($request, $response);
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
