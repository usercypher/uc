<?php

class Pipe_ErrorHandler {
    var $app;
    var $input, $output;

    function args($args) {
        list($this->app) = $args;
    }

    function process($input, $output) {
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

        if ($errno & $this->app->getEnv('ERROR_NON_FATAL')) {
            $result = $this->app->error($errno, $errstr, $errfile, $errline, $this->app->getEnv('ERROR_DISPLAY') ? debug_backtrace() : array(), isset($this->input->header['accept']) ? $this->input->header['accept'] : '');

            return true;
        }

        throw new ErrorException($errstr, 500, $errno, $errfile, $errline);
    }

    function exception($e) {
        while (ob_get_level()) {
            ob_end_clean();
        }

        $result = $this->app->error(method_exists($e, 'getSeverity') ? $e->getSeverity() : 1, $e->getMessage(), $e->getFile(), $e->getLine(), $this->app->getEnv('ERROR_DISPLAY') ? $e->getTrace() : array(), isset($this->input->header['accept']) ? $this->input->header['accept'] : '');
        $this->output->header['content-type'] = $result['header']['content-type'];
        $this->output->content = $result['content'];
        $this->output->code = $result['code'];
        $this->output->version = $this->input->version;

        $app = $this->app;
        $output = $this->output;

        $output->io($output->content, (int) ($app->getEnv('SAPI') === 'cli' && $output->code > 0));
    }

    function shutdown() {
        if (($error = error_get_last()) !== null) {
            $this->error($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
}
