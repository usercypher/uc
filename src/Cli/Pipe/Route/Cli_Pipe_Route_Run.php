<?php

class Cli_Pipe_Route_Run {
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

        $tempInput = new Input();

        $tempInput->route = $input->query['route'];

        // Handle headers
        if (isset($input->query['header'])) {
            $header = explode("\n", $input->query['header']);
            foreach ($header as $h) {
                list($k, $v) = explode(':', $h, 2);
                $tempInput->header[strtolower(trim($k))] = trim($v);
            }
        }

        // Handle content and method
        $tempInput->content = isset($input->query['content']) ? $input->query['content'] : '';
        $tempInput->method = isset($input->query['method']) ? $input->query['method'] : 'GET';

        // Handle query string if provided
        if (isset($input->query['query'])) {
            parse_str($input->query['query'], $tempInput->query);
        }

        $result = $this->app->resolveRoute($tempInput->method, $tempInput->route);

        if (isset($result['error'])) {
            $description = '';
            if ($result['error'] === 405) {
                $description = 'Method not allowed: ' . $tempInput->method . ' ' . $tempInput->route . '. allow: ' . $result['header']['allow'];
                $this->app->setEnv('HANDLE_ERROR_DEFAULT_CONTEXT', array(
                    'ACCEPT' => isset($tempInput->header['accept']) ? $tempInput->header['accept'] : '',
                    'HEADER' => $result['header']
                ));
                $output->header += $result['header'];
            } else {
                $description = 'Route not found: ' . $tempInput->method . ' ' . $tempInput->route;
            }
            trigger_error($result['error'] . '|' . $description, E_USER_WARNING);
        } else {
            $tempInput->param = $result['param'];
            // Dispatch the request
            list($_, $output) = $this->app->pipe($tempInput, $output, $result['handler']);
        }

        return array($input, $output, $success);
    }
}
