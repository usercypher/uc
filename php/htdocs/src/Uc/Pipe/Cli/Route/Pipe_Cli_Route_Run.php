<?php

class Pipe_Cli_Route_Run {
    var $app;

    function args($args) {
        list(
            $this->app
        ) = $args;
    }

    function process($input, $output) {
        $success = true;
        $message = '';

        $tempInput = new Input;
        $tempInput->source = 'http';

        $tempInput->route = $input->getFrom($input->query, 'route');

        if (empty($tempInput->route)) {
            $message .= 'Error: Missing required parameters.' . "\n";
            $message .= 'Usage: --route=/route/path' . "\n";
            $output->content = $message;
            $output->code = 1;
            $success = false;
            return array($input, $output, $success);
        }

        // Handle headers
        if (isset($input->query['header'])) {
            $headers = explode("\n", $input->query['header']);
            foreach ($headers as $header) {
                list($k, $v) = explode(':', $header, 2);
                $tempInput->headers[strtolower(trim($k))] = trim($v);
            }
        }

        // Handle content and method
        $tempInput->content = $input->getFrom($input->query, 'content', '');
        $tempInput->method = $input->getFrom($input->query, 'method', 'GET');

        // Handle query string if provided
        if (isset($input->query['query'])) {
            parse_str($input->query['query'], $tempInput->query);
        }

        // Dispatch the request
        list($_, $output) = $this->app->process($tempInput, $output);

        return array($input, $output, $success);
    }
}
