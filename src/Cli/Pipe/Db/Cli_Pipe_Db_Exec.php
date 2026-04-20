<?php

class Cli_Pipe_Db_Exec {
    var $app;

    function args($args) {
        list($this->app, $this->db) = $args;
        $this->db->connect(array(
            'dsn' => $this->app->getEnv('DB_DSN'),
            'user' => $this->app->getEnv('DB_USER'),
            'pass' => $this->app->getEnv('DB_PASS'),
        ));
    }

    function process($input, $output) {
        $success = true;
        $message = '';

        $db = $input->io(0, "NULL");

        if (empty($db) && empty($input->param['db'])) {
            $message .= 'Error: Missing required parameters.' . "\n";
            $message .= 'Usage: php [file] db exec [db]' . "\n";
            $output->content = $message;
            $output->code = 1;
            $success = false;
            return array($input, $output, $success);
        }

        if ($this->db->execute(empty($db) ? $input->param['db'] : $db)) {
            $message .= 'Db executed successfully.' . "\n";
        }

        $output->content = $message;

        return array($input, $output, $success);
    }
}
