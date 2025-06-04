<?php

class AppError {
    private $app;

    public function __construct($args = array()) {
        list($this->app) = $args;
    }

    public function setup($exception = false) {
        if ($exception) {
            set_error_handler(array($this, 'errorThrow'));
            set_exception_handler(array($this, 'exception'));
        } else {
            set_error_handler(array($this->app, 'error'));
        }

        register_shutdown_function(array($this->app, 'shutdown'));
    }

    private function exception($e) {
        $this->app->error(method_exists($e, 'getSeverity') ? $e->getSeverity() : 1, ($e->getCode() === 0 ? 1 : $e->getCode()). '|' . $e->getMessage(), $e->getFile(), $e->getLine(), false, true, $e->getTrace());
    }

    private function errorThrow($errno, $errstr, $errfile, $errline) {
        throw new ErrorException($errstr, 500, $errno, $errfile, $errline);
    }
}
