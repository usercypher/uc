<?php

class CsrfGenerateMiddleware {
    public $session;

    public function __construct($args) {
        list(
            $this->session
        ) = $args;
    }

    public function process($request, $response, $next) {
        $this->session->set('csrf_token', bin2hex(random_bytes(32)));

        $response = $next->process($request, $response, $next);

        return $response;
    }
}