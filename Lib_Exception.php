<?php

class Lib_Exception {
    private $app;

    public function args($args) {
        list($this->app) = $args;
    }

    public function init() {
        set_error_handler(array($this, 'errorThrow'));
        set_exception_handler(array($this, 'exception'));
        register_shutdown_function(array($this, 'shutdown'));
    }

    public function errorThrow($errno, $errstr, $errfile, $errline) {
        throw new ErrorException($errstr, 500, $errno, $errfile, $errline);
    }

    public function exception($e) {
        $this->app->error(method_exists($e, 'getSeverity') ? $e->getSeverity() : 1, ($e->getCode() === 0 ? 1 : $e->getCode()). '|' . $e->getMessage(), $e->getFile(), $e->getLine(), false, true, $e->getTrace());
    }

    public function shutdown() {
        if (($error = error_get_last()) !== null) $this->errorThrow($error['type'], $error['message'], $error['file'], $error['line']);
    }
}
