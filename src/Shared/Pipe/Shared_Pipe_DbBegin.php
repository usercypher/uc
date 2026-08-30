<?php

class Shared_Pipe_DbBegin {
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
        $name = isset($input->data['db_begin:name']) ? $input->data['db_begin:name'] : 'default';

        $this->database->connect(array(
            'dsn' => $this->app->env['db'][$name]['dsn'],
            'user' => $this->app->env['db'][$name]['user'],
            'pass' => $this->app->env['db'][$name]['pass']
        ), $name);
        $this->database->begin($name);

        return array($input, $output, $success);
    }
}