<?php

class Shared_Pipe_ErrorHandler {
    var $app;
    var $input, $output;

    function args($args) {
        list($this->app) = $args;
    }

    function call($input, $output) {
        $this->input = $input;
        $this->output = $output;

        set_error_handler(array($this, 'error'));
        set_exception_handler(array($this, 'exception'));
        register_shutdown_function(array($this, 'shutdown'));

        return array($input, $output, true);
    }

    function error($errno, $errstr, $errfile, $errline) {
        if (!($errno & error_reporting())) {
            return true;
        }

        if ($errno & $this->app->env['error_non_fatal']) {
            $result = $this->app->error($errno, $errstr, $errfile, $errline, array(
                'trace' => $this->app->env['error_display'] ? debug_backtrace() : array(),
                'accept' => isset($this->input->header['accept']) ? $this->input->header['accept'] : '',
                'header' => array()
            ));

            return true;
        }

        throw new ErrorException($errstr, 500, $errno, $errfile, $errline);
    }

    function exception($e) {
        while (ob_get_length() !== false) {
            ob_end_clean();
        }

        $result = $this->app->error(method_exists($e, 'getSeverity') ? $e->getSeverity() : 1, $e->getMessage(), $e->getFile(), $e->getLine(), array(
            'trace' => $this->app->env['error_display'] ? $e->getTrace() : array(),
            'accept' => isset($this->input->header['accept']) ? $this->input->header['accept'] : '',
            'header' => $this->output->header
        ));

        $this->output->header = $result['header'];
        $this->output->content = $result['content'];
        $this->output->code = $result['code'];
        $this->output->version = $this->input->version;

        $app = $this->app;
        $input = $this->input;
        $output = $this->output;

        $output->call($output->content, (int) $output->code);

        $app->term();
        $input->term();
        $output->term();
    }

    function shutdown() {
        if (($error = error_get_last()) !== null) {
            $this->error($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }   
    
}
