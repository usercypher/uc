<?php

class Pipe_CsrfValidate {
    public $session;

    public function __construct($args = array()) {
        list(
            $this->session
        ) = $args;
    }

    public function pipe($request, $response) {
        if (!isset($request->post['csrf_token'])) {
            $response->code = 403;
            $response->content = 'Invalid CSRF token';
            $response->send();
            return array($request, $response);
        }

        $csrfToken = $this->session->get('csrf_token');
        if (!$csrfToken) {
            $response->code = 403;
            $response->content = 'Invalid CSRF token';
            $response->send();
            return array($request, $response);
        }

        if ($request->post['csrf_token'] !== $csrfToken) {
            $response->code = 403;
            $response->content = 'Invalid CSRF token';
            $response->send();
            return array($request, $response);
        }

        return array($request, $response);
    }
}