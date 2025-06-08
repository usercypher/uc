<?php

class Pipe_CsrfValidate {
    public $session;

    public function args($args) {
        list(
            $this->session
        ) = $args;
    }

    public function pipe($request, $response) {
        $break = false;

        if (!isset($request->post['csrf_token'])) {
            $response->code = 403;
            $response->content = 'Invalid CSRF token';
            $break = true;
        }

        $csrfToken = $this->session->get('csrf_token');
        if (!$csrfToken) {
            $response->code = 403;
            $response->content = 'Invalid CSRF token';
            $break = true;
        }

        if ($request->post['csrf_token'] !== $csrfToken) {
            $response->code = 403;
            $response->content = 'Invalid CSRF token';
            $break = true;
        }

        return array($request, $response, $break);
    }
}