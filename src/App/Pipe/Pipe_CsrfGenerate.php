<?php

class Pipe_CsrfGenerate {
    public $session;

    public function args($args) {
        list(
            $this->session
        ) = $args;
    }

    public function process($input, $output) {
        $success = true;

        $this->session->set('csrf_token', bin2hex(random_bytes(32)));

        return array($input, $output, $success);
    }
}