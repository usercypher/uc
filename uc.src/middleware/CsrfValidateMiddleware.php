<?php

class CsrfValidateMiddleware {
    public $session;

    public function __construct($args) {
        list(
            $this->session
        ) = $args;
    }

    public function process($request, $response, $next) {
        if (!isset($request->post['csrf_token'])) {
            trigger_error('403|Invalid CSRF token');
        }

        $csrfToken = $this->session->get('csrf_token');
        if (!$csrfToken) {
            trigger_error('403|Invalid CSRF token');
        }

        if ($request->post['csrf_token'] !== $csrfToken) {
            trigger_error('403|Invalid CSRF token');
        }

        return $next->process($request, $response, $next);
    }
}