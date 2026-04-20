<?php

class Shared_Pipe_DbEnd {
    var $app;
    var $database;

    function args($args) {
        list(
            $this->app,
            $this->database
        ) = $args;

        $this->database->connect(array(
            'dsn' => $this->app->getEnv('DB_DSN'),
            'user' => $this->app->getEnv('DB_USER'),
            'pass' => $this->app->getEnv('DB_PASS'),
        ));
    }

    function process($input, $output) {
        $success = true;
        // code

        if ($input->data['db_end:commit']) {
            $this->database->commit();
        } elseif ($input->data['db_end:rollback']) {
            $this->database->rollback();
        }

        return array($input, $output, $success);
    }
}