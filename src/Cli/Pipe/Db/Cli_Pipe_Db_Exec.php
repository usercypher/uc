<?php

class Cli_Pipe_Db_Exec {
    var $app;
    var $db;

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

        $output->io('If input is piped, the script will read it and exit automatically. Paste Db script and type EXIT on its own line to finish:' . "\n");
        $db = $input->io(0, 'EXIT');

        $output->io('Executing...' . "\n");

        $this->db->execute($db);

        $message .= 'Done.' . "\n";

        $output->content = $message;

        return array($input, $output, $success);
    }
}
