<?php

class Shared_Pipe_DbEnd {
    var $app;
    var $database;

    function args($args) {
        list(
            $this->app,
            $this->database
        ) = $args;
    }

    function call($input, $output) {
        $success = true;
        // code
        $name = isset($input->data['db_end:name']) ? $input->data['db_end:name'] : 'default';

        $this->database->connect(array(
            'dsn' => $this->app->env['db'][$name]['dsn'],
            'user' => $this->app->env['db'][$name]['user'],
            'pass' => $this->app->env['db'][$name]['pass']
        ), $name);

        if ($input->data['db_end:commit']) {
            $this->database->commit($name);
        } elseif ($input->data['db_end:rollback']) {
            $this->database->rollback($name);
        }

        return array($input, $output, $success);
    }
}