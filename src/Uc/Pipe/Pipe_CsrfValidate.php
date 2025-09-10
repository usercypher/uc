<?php

class Pipe_CsrfValidate {
    public $app, $session;

    public function args($args) {
        list(
            $this->app,
            $this->session
        ) = $args;
    }

    public function process($input, $output) {
        $success = true;

        if (!isset($input->parsed['csrf_token'])) {
            $output->redirect($this->app->urlRoute($input->getFrom($input->query, 'redirect', 'home')));
            $this->session->set('flash', array(
                array('type' => 'error', 'message' => 'invalid csrf token')
            ));
            $success = false;
        }

        $csrfToken = $this->session->get('csrf_token');

        if (!$csrfToken) {
            $output->redirect($this->app->urlRoute($input->getFrom($input->query, 'redirect', 'home')));
            $this->session->set('flash', array(
                array('type' => 'error', 'message' => 'invalid csrf token')
            ));
            $success = false;
        }

        if ($input->parsed['csrf_token'] != $csrfToken) {
            $output->redirect($this->app->urlRoute($input->getFrom($input->query, 'redirect', 'home')));
            $this->session->set('flash', array(
                array('type' => 'error', 'message' => 'invalid csrf token')
            ));
            $success = false;
        }

        return array($input, $output, $success);
    }
}