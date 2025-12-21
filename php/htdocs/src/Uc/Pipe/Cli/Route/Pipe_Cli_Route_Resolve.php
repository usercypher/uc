<?php

class Pipe_Cli_Route_Resolve {
    var $app;

    function args($args) {
        list(
            $this->app
        ) = $args;
    }

    function process($input, $output) {
        $success = true;

        $unitList = isset($this->app->unitList) ? $this->app->unitList : array();

        $message = '';

        $method = $input->getFrom($input->query, 'method', 'GET');
        $route = $input->getFrom($input->query, 'route');

        if (empty($route)) {
            $message .= 'Error: Missing required parameters.' . "\n";
            $message .= 'Usage: --route=/route/path' . "\n";
            $output->content = $message;
            $output->code = 1;
            $success = false;
            return array($input, $output, $success);
        }

        $result = $this->app->resolveRoute($method, $route);

        if (isset($result['error'])) {
            $message .= 'Route Error: ' . $result['error'] . "\n";
            $output->content = $message;
            $output->code = 1;
            return array($input, $output, $success);
        }

        $message .= 'RESOLVED ROUTE' . "\n";
        $message .= '  Method : ' . $method . "\n";
        $message .= '  Route  : ' . $route . "\n";

        $message .= '  Pipe   :' . "\n";
        foreach ($result['pipe'] as $i => $index) {
            $message .= '    #' . str_pad($i, 2, ' ', STR_PAD_LEFT) . '  ' . $unitList[$index] . "\n";
        }

        // Show dynamic params if any
        if (!empty($result['params'])) {
            $message .= '  Params :' . "\n";
            foreach ($result['params'] as $key => $value) {
                $message .= '    ' . str_pad($key, 12) . ' = ' . (is_array($value) ? 'array(' . implode(', ', $value) . ')' : $value) . "\n";
            }
        }

        $output->content = $message;

        return array($input, $output, $success);
    }
}
