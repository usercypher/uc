<?php

class Pipe_Cli_Route_Resolve {
    private $app;

    public function args($args) {
        list(
            $this->app
        ) = $args;
    }

    public function process($input, $output) {
        $success = true;

        $unitList = isset($this->app->unitList) ? $this->app->unitList : array();

        $message = '';

        $method = $input->getFrom($input->options, 'method', 'GET');
        $route = $input->getFrom($input->options, 'route');

        if ($route === null) {
            $message .= 'Error: Missing required parameters.' . EOL;
            $message .= 'Usage: --route=/route/path' . EOL;
            $output->content = $message;
            $output->code = 1;
            $success = false;
            return array($input, $output, $success);
        }

        $result = $this->app->resolveRoute($method, $route);

        if (isset($result['error'])) {
            $message .= 'Route Error [http ' . $result['http'] . ']: ' . $result['error'] . EOL;
            $output->content = $message;
            $output->code = 1;
            return array($input, $output, $success);
        }

        $message .= 'RESOLVED ROUTE' . EOL;
        $message .= '  Method : ' . $method . EOL;
        $message .= '  Route  : ' . $route . EOL;

        $message .= '  Pipe   :' . EOL;
        foreach ($result['pipe'] as $i => $index) {
            $message .= '    #' . str_pad($i, 2, ' ', STR_PAD_LEFT) . '  ' . $unitList[$index] . EOL;
        }

        // Show dynamic params if any
        if (!empty($result['params'])) {
            $message .= '  Params :' . EOL;
            foreach ($result['params'] as $key => $value) {
                $message .= '    ' . str_pad($key, 12) . ' = ' . (is_array($value) ? 'array(' . implode(', ', $value) . ')' : $value) . EOL;
            }
        }

        $output->content = $message;

        return array($input, $output, $success);
    }
}