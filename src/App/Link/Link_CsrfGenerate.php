<?php

class Link_CsrfGenerate {
    public $session;

    public function __construct($args = array()) {
        list(
            $this->session
        ) = $args;
    }

    public function link($request, $response) {
        $this->session->set('csrf_token', bin2hex(random_bytes(32)));

        return true;
    }
}