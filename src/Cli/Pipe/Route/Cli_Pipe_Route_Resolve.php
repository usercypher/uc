<?php

class Cli_Pipe_Route_Resolve {
    var $app;

    function args($args) {
        list($this->app) = $args;
    }

    function process($input, $output) {
        $success = true;

        $message = '';

        if (empty($input->query['route'])) {
            $message .= 'Error: Missing required parameters.' . "\n";
            $message .= 'Usage: --route=/route/path' . "\n";
            $output->content = $message;
            $output->code = 1;
            $success = false;
            return array($input, $output, $success);
        }

        $route = $input->query['route'];
        $method = isset($input->query['method']) ? $input->query['method'] : 'GET';

        $result = $this->app->resolveRoute($method, $route);

        if (isset($result['error'])) {
            $message .= 'Route not found: ' . $route . "\n";
            $output->content = $message;
            $output->code = 1;
            return array($input, $output, $success);
        }

        $message .= 'RESOLVED ROUTE' . "\n";
        $message .= '  Method  : ' . $method . "\n";
        $message .= '  Route   : ' . $route . "\n";

        $message .= '  Handler :' . "\n";
        foreach ($result['handler'] as $i => $p) {
            $message .= '    #' . str_pad($i, 2, ' ', STR_PAD_LEFT) . '  ' . $p . "\n";
        }

        // Show dynamic param if any
        $message .= '  Params  :' . "\n";
        if (!empty($result['param'])) {
            foreach ($result['param'] as $key => $value) {
                $message .= '    ' . str_pad($key, 12) . ' = ' . $value . "\n";
            }
        }

        $output->content = $message;

        return array($input, $output, $success);
    }
}
