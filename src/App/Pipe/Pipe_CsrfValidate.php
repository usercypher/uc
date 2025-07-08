<?php

class Pipe_CsrfValidate {
    public $session;

    public function args($args) {
        list(
            $this->session
        ) = $args;
    }

    public function process($input, $output) {
        $success = true;

        if (!isset($input->parsed['csrf_token'])) {
            $output->code = 403;
            $output->content = 'Invalid CSRF token';
            $success = false;
        }

        $csrfToken = $this->session->get('csrf_token');
        if (!$csrfToken) {
            $output->code = 403;
            $output->content = 'Invalid CSRF token';
            $success = false;
        }

        if ($input->parsed['csrf_token'] !== $csrfToken) {
            $output->code = 403;
            $output->content = 'Invalid CSRF token';
            $success = false;
        }

        return array($input, $output, $success);
    }
}