<?php

class Cli_Pipe_Help {
    protected $app;

    function args($args) {
        list($this->app) = $args;
    }

    function process($input, $output) {
        $success = true;
        $target = $input->query['autocomplete'] ?? null;
        $unknownRoute = $input->param['on-unknown-route'] ?? '';
        
        $message = '';

        if ($target) {
            $message = $this->getAutocompleteSuggestions($target);
        } else {
            // Error/Help mode: list top-level available commands
            $cleanRoute = rawurldecode(explode('/', $unknownRoute)[0]);
            $message = "No route '$cleanRoute' found. Available routes:\n";
            $message .= $this->listAvailableSegments(isset($this->app->routes['cli']) ? $this->app->routes['cli'] : array());
        }

        $output->content = $message;
        return array($input, $output, $success);
    }

    private function getAutocompleteSuggestions($target) {
        $segments = explode('/', trim($target, '/'));
        $current = isset($this->app->routes['cli']) ? $this->app->routes['cli'] : array();

        // Navigate to the target depth
        foreach ($segments as $segment) {
            if (isset($current[$segment])) {
                $current = $current[$segment];
            } else {
                return "No sub-route found for '$target'\n";
            }
        }

        return $this->listAvailableSegments($current);
    }

    private function listAvailableSegments($tree) {
        if (!is_array($tree)) return "";

        $suggestions = [];
        foreach ($tree as $key => $value) {
            // Skip the handler meta-key and dynamic parameters (starting with :)
            if ($key === $this->app->ROUTE_HANDLER || (isset($key[0]) && $key[0] === ':')) {
                continue;
            }
            $suggestions[] = "  " . $key;
        }

        return implode("\n", $suggestions) . "\n";
    }
}