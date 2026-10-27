<?php

class Shared_Pipe_SessionInit {
    var $app, $session;

    function args($args) {
        list($this->app, $this->session) = $args;
    }

    function call($input, $output) {
        $success = true;

        $name = (isset($this->app->env['app_id']) ? $this->app->env['app_id'] : 'default') . ':session';

        $this->session->config = array(
            'name' => $name,
            'id' => isset($input->query[$name]) ? $input->query[$name] : session_id()
        );

        return array($input, $output, $success);
    }
}
