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

        $this->db->set($name, array(
            'dsn' => $this->app->env['db'][$name]['dsn'],
            'user' => $this->app->env['db'][$name]['user'],
            'pass' => $this->app->env['db'][$name]['pass']
        ));

        $db = $this->db->get($name);

        $output->call('If input is piped, the script will read it and exit automatically. Paste Db script and type EXIT on its own line to finish:' . "\n");
        $query = $input->call('EXIT');

        $output->call('Executing...' . "\n");

        $message .= 'Done. ' . $db->execute($query) . "\n";

        $output->content = $message;

        return array($input, $output, $success);
    }
}
