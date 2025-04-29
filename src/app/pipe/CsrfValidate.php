<?php

class CsrfValidate {
    public $session;

    public function __construct($args) {
        list(
            $this->session
        ) = $args;
    }

    public function pipe($request, $response) {
        if (!isset($request->post['csrf_token'])) {
            trigger_error('403|Invalid CSRF token');
            exit();
        }

        $csrfToken = $this->session->get('csrf_token');
        if (!$csrfToken) {
            trigger_error('403|Invalid CSRF token');
            exit();
        }

        if ($request->post['csrf_token'] !== $csrfToken) {
            trigger_error('403|Invalid CSRF token');
            exit();
        }

        return array($request, $response);
    }
}