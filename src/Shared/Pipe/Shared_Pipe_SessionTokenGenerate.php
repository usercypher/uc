<?php

class Shared_Pipe_SessionTokenGenerate {
    var $app, $session;

    function args($args) {
        list($this->app, $this->session) = $args;
    }

    function process($input, $output) {
        $success = true;

        if (!$this->session->get('session_token')) {
            $this->session->set('session_token', bin2hex(random_bytes(32)));
        }

        return array($input, $output, $success);
    }
}
