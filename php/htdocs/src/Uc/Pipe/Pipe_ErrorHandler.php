<?php

class Pipe_ErrorHandler {
    private $app;
    private $input, $output;

    public function args($args) {
        list(
            $this->app, 
        ) = $args;
    } 

    public function process($input, $output) {
        $this->input = $input;
        $this->output = $output;

        set_error_handler(array($this, 'error'));
        set_exception_handler(array($this, 'exception'));
        register_shutdown_function(array($this, 'shutdown'));

        return array($input, $output, true);
    }

    public function error($errno, $errstr, $errfile, $errline) {
        if (!($errno & error_reporting())) return true;

        if ($errno & $this->app->getEnv('ERROR_NON_FATAL')) {
            $result = $this->app->error($errno, $errstr, $errfile, $errline, array(
                'ERROR_ACCEPT' => $this->input->getFrom($this->input->headers, 'accept', ''),
            ));

            return true;
        }

        throw new ErrorException($errstr, 500, $errno, $errfile, $errline);
    }

    public function exception($e) {
        $result = $this->app->error(method_exists($e, 'getSeverity') ? $e->getSeverity() : 1, $e->getMessage(), $e->getFile(), $e->getLine(), array(
            'ERROR_ACCEPT' => $this->input->getFrom($this->input->headers, 'accept', ''),
            'ERROR_TRACE' => $e->getTrace(),
        ));

        $this->output->content = $result['content'];
        $this->output->code = $result['code'];
        $this->output->type = $result['type'];

        $input = $this->input;
        $output = $this->output;

        switch ($input->source) {
            case 'cli':
                $output->std($output->content, $output->code > 0);
                exit($output->code);
            case 'http':
                return $output->http($output->content);
            default:
                echo('Unknown input source:' . $input->source);
        }
    }

    public function shutdown() {
        if (($error = error_get_last()) !== null) $this->error($error['type'], $error['message'], $error['file'], $error['line']);
    }
}