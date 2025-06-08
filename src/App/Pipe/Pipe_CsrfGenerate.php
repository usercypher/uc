<?php

class Pipe_CsrfGenerate {
    public $session;

    public function args($args) {
        list(
            $this->session
        ) = $args;
    }

    public function pipe($request, $response) {
        $break = false;

        $this->session->set('csrf_token', bin2hex(random_bytes(32)));

        return array($request, $response, $break);
    }
}