<?php

class Link_CsrfValidate {
    public $session;

    public function __construct($args = array()) {
        list(
            $this->session
        ) = $args;
    }

    public function link($request, $response) {
        if (!isset($request->post['csrf_token'])) {
            $response->code = 403;
            $response->content = 'Invalid CSRF token';
            return false;
        }

        $csrfToken = $this->session->get('csrf_token');
        if (!$csrfToken) {
            $response->code = 403;
            $response->content = 'Invalid CSRF token';
            return false;
        }

        if ($request->post['csrf_token'] !== $csrfToken) {
            $response->code = 403;
            $response->content = 'Invalid CSRF token';
            return false;
        }

        return true;
    }
}