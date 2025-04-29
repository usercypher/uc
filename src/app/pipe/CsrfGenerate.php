<?php

class CsrfGenerate {
    public $session;

    public function __construct($args) {
        list(
            $this->session
        ) = $args;
    }

    public function pipe($request, $response) {
        $this->session->set('csrf_token', bin2hex(random_bytes(32)));

        return array($request, $response);
    }
}