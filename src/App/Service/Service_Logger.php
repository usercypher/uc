<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Service_Logger {
    private $logger, $app;

    public function __construct($args = array()) {
        list(
            $this->app,
        ) = $args;

        $this->logger = new Logger('app_logger');
        $this->logger->pushHandler(new StreamHandler($this->app->dirRoot('var/log/monolog.log'), Logger::DEBUG));
    }

    public function get() {
        return $this->logger;
    }
}
