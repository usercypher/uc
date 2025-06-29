<?php

class Pipe_Cli_Route_Resolve {
    private $app;

    public function args($args) {
        list(
            $this->app
        ) = $args;
    }

    public function pipe($input, $output) {
        $break = false;

        $unitList = isset($this->app->unitList) ? $this->app->unitList : array();

        $message = '';

        $type = $input->getFrom($input->options, 'type');
        $path = $input->getFrom($input->options, 'path');

        if (!$type || !$path) {
            $message .= 'Error: Missing required parameters.' . EOL;
            $message .= 'Usage: --type=GET|POST --path=/route/path' . EOL;
            $output->content = $message;
            $output->code = 1;
            $break = true;
            return array($input, $output, $break);
        }

        $result = $this->app->resolveRoute($type, $path);

        if (isset($result['error'])) {
            $message .= 'Route Error [http ' . $result['http'] . ']: ' . $result['error'] . EOL;
            $output->content = $message;
            $output->code = 1;
            return array($input, $output, $break);
        }

        $message .= 'RESOLVED ROUTE' . EOL;
        $message .= '  Method : ' . $type . EOL;
        $message .= '  Path   : ' . $path . EOL;

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

        return array($input, $output, $break);
    }
}
