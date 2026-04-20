<?php

class Cli_Pipe_Route_Print {
    /** @var App */
    protected $app;

    function args($args) {
        list($this->app) = $args;
    }

    function process($input, $output) {
        $success = true;
        $unitList = $this->app->unitList ?? array();
        
        // Flatten the tree starting from the root
        $flattened = $this->walk($this->app->routes);
        
        $message = '';
        foreach ($flattened as $route) {
            $path   = $route['path'];
            $method = $route['method'];
            $pipe   = $route['pipe'];

            // Map unit indexes to names
            $handlerNames = array_map(function($unitIndex) use ($unitList) {
                return $unitList[$unitIndex] ?? "Unknown($unitIndex)";
            }, $pipe);

            $message .= sprintf(
                "  %-8s '%s' => %s\n",
                "'$method'",
                $path,
                implode(' > ', $handlerNames)
            );
        }

        $output->content = $message;
        return array($input, $output, $success);
    }

    /**
     * Recursively traverses the route tree to find handlers.
     */
    private function walk($tree, $prefix = '') {
        $routes = array();

        foreach ($tree as $segment => $node) {
            // If we hit the handler key, we've found the methods (GET, POST, etc.)
            if ($segment === $this->app->ROUTE_HANDLER) {
                foreach ($node as $method => $pipe) {
                    $routes[] = array(
                        'path'   => $prefix ?: '/',
                        'method' => $method,
                        'pipe'   => $pipe
                    );
                }
                continue;
            }

            // Otherwise, keep digging deeper into the tree
            if (is_array($node)) {
                $currentPath = $prefix === '' ? $segment : $prefix . '/' . $segment;
                $routes = array_merge($routes, $this->walk($node, $currentPath));
            }
        }

        return $routes;
    }
}