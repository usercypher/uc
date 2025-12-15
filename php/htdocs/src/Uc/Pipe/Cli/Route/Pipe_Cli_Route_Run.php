<?php

class Pipe_Cli_Route_Run {
    private $app;

    public function args($args) {
        list(
            $this->app
        ) = $args;
    }

    public function process($input, $output) {
        $success = true;

        $message = '';

        $input->route = $input->getFrom($input->options, 'route');

        if ($input->route === null) {
            $message .= 'Error: Missing required parameters.' . EOL;
            $message .= 'Usage: --route=/route/path' . EOL;
            $output->content = $message;
            $output->code = 1;
            $success = false;
            return array($input, $output, $success);
        }

        foreach ((isset($input->options['header']) ? explode("\n", $input->options['header']) : array()) as $header) {
            list($k, $v) = explode(':', $header, 2);
            $input->headers[strtolower(trim($k))] = trim($v);
        }
        
        $input->content = $input->getFrom($input->options, 'content', '');
        $input->method = $input->getFrom($input->options, 'method', 'GET');
        if (isset($input->options['query'])) parse_str($input->options['query'], $input->query);

        $route = $this->app->resolveRoute($input->method, $input->route);

        if (isset($route['error'])) return trigger_error((SAPI === 'cli' ? 1 : $route['http']) . '|' . $route['error'], E_USER_WARNING);

        $input->params = $route['params'];
        foreach ($route['pipe'] as $p) {
            $p = $this->app->loadClass($this->app->unitList[$p]);
            list($input, $output, $success) = $p->process($input, $output);
            if (!$success) break;
        }

        return array($input, $output, $success);
    }
}