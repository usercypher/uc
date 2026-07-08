<?php

class Cli_Pipe_Db_Exec {
    var $app;
    var $db;

    function args($args) {
        list($this->app, $this->db) = $args;
    }

    function process($input, $output) {
        $success = true;
        $message = '';
        
        $name = isset($input->query['name']) ? $input->query['name'] : 'DEFAULT';

        $db = $this->app->getEnv('DB', array());

        $this->db->connect(array(
            'dsn' => isset($db[$name]['DSN']) ? $db[$name]['DSN'] : null,
            'user' => isset($db[$name]['USER']) ? $db[$name]['USER'] : null,
            'pass' => isset($db[$name]['PASS']) ? $db[$name]['PASS'] : null,
            'query' => isset($db[$name]['QUERY']) ? $db[$name]['QUERY'] : null,
        ));

        $output->io('If input is piped, the script will read it and exit automatically. Paste Db script and type EXIT on its own line to finish:' . "\n");
        $db = $input->io(0, 'EXIT');

        $output->io('Executing...' . "\n");

        $this->db->execute($db);

        $message .= 'Done.' . "\n";

        $output->content = $message;

        return array($input, $output, $success);
    }
}
