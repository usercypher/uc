<?php

class CsrfGenerateMiddleware {
    public $session;

    public function __construct($dependency) {
        $this->session = $dependency['Session'];
    }

    public function process($request, $response, $next) {
        $this->session->set('csrf_token', bin2hex(random_bytes(32)));

        $response = $next->process($request, $response, $next);

        return $response;
    }
}