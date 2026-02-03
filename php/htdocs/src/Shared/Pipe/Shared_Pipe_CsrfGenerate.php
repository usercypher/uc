<?php

class Shared_Pipe_CsrfGenerate {
    var $app, $session;

    function args($args) {
        list($this->app, $this->session) = $args;
    }

    function process($input, $output) {
        $success = true;

        $this->session->set('csrf_token', bin2hex(random_bytes(32)));

        return array($input, $output, $success);
    }
}
