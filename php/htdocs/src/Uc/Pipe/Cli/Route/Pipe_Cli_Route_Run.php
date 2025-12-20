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

        $route = $input->getFrom($input->options, 'route');

        if ($route === null) {
            $message .= 'Error: Missing required parameters.' . "\n";
            $message .= 'Usage: --route=/route/path' . "\n";
            $output->content = $message;
            $output->code = 1;
            $success = false;
            return array($input, $output, $success);
        }

        // Handle headers
        if (isset($input->options['header'])) {
            $headers = explode("\n", $input->options['header']);
            foreach ($headers as $header) {
                list($k, $v) = explode(':', $header, 2);
                $input->headers[strtolower(trim($k))] = trim($v);
            }
        }

        // Handle content and method
        $input->content = $input->getFrom($input->options, 'content', '');
        $input->method = $input->getFrom($input->options, 'method', 'GET');

        // Handle query string if provided
        if (isset($input->options['query'])) {
            parse_str($input->options['query'], $input->query);
        }

        // Save the current positional arguments
        $savePostional = $input->positional;

        // Update positional arguments
        $input->positional = array_filter(explode('/', $route));

        // Dispatch the request
        list($input, $output) = $this->app->process($input, $output);

        // Restore the original positional arguments
        $input->positional = $savePostional;

        return array($input, $output, $success);
    }
}
