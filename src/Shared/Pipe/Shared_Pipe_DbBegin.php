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

    function process($input, $output) {
        $success = true;
        // code
        $db = $app->getEnv('DB', array());
        $name = isset($input->data['db_begin:name']) ? $input->data['db_begin:name'] : 'DEFAULT';

        $this->database->connect(array(
            'dsn' => isset($db[$name]['DSN']) ? $db[$name]['DSN'] : null,
            'user' => isset($db[$name]['USER']) ? $db[$name]['USER'] : null,
            'pass' => isset($db[$name]['PASS']) ? $db[$name]['PASS'] : null,
            'query' => isset($db[$name]['QUERY']) ? $db[$name]['QUERY'] : null,
        ), $name);
        $this->database->begin($name);

        return array($input, $output, $success);
    }
}