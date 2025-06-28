<?php

class Pipe_CsrfValidate {
    public $session;

    public function args($args) {
        list(
            $this->session
        ) = $args;
    }

    public function pipe($input, $output) {
        $break = false;

        if (!isset($input->parsed['csrf_token'])) {
            $output->code = 403;
            $output->content = 'Invalid CSRF token';
            $break = true;
        }

        $csrfToken = $this->session->get('csrf_token');
        if (!$csrfToken) {
            $output->code = 403;
            $output->content = 'Invalid CSRF token';
            $break = true;
        }

        if ($input->parsed['csrf_token'] !== $csrfToken) {
            $output->code = 403;
            $output->content = 'Invalid CSRF token';
            $break = true;
        }

        return array($input, $output, $break);
    }
}