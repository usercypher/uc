<?php

class Cli_Pipe_Db_Exec {
    var $app;
    var $db;

    function args($args) {
        list($this->app, $this->db) = $args;
    }

    function call($input, $output) {
        $success = true;
        $message = '';

        $name = isset($input->query['name']) ? $input->query['name'] : 'default';

        $this->db->connect(array(
            'dsn' => $this->app->env['db'][$name]['dsn'],
            'user' => $this->app->env['db'][$name]['user'],
            'pass' => $this->app->env['db'][$name]['pass']
        ));

        $output->call('If input is piped, the script will read it and exit automatically. Paste Db script and type EXIT on its own line to finish:' . "\n");
        $db = $input->call('EXIT');

        $output->call('Executing...' . "\n");

        $message .= 'Done. ' . $this->db->execute($db) . "\n";

        $output->content = $message;

        return array($input, $output, $success);
    }
}
