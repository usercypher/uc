<?php

class Pipe_CsrfValidate {
    var $app, $session;

    function args($args) {
        list(
            $this->app,
            $this->session
        ) = $args;
    }

    function process($input, $output) {
        $success = true;

        if (!isset($input->frame['csrf_token'])) {
            $output->header['location'] = $this->app->urlRoute(trim($input->getFrom($input->query, 'redirect', ''), '/'));
            $this->session->set('flash', array(
                array('type' => 'error', 'message' => 'invalid csrf token')
            ));
            $success = false;
        }

        $csrfToken = $this->session->unset('csrf_token');

        if (!$csrfToken) {
            $output->header['location'] = $this->app->urlRoute(trim($input->getFrom($input->query, 'redirect', ''), '/'));
            $this->session->set('flash', array(
                array('type' => 'error', 'message' => 'invalid csrf token')
            ));
            $success = false;
        }

        if ($input->frame['csrf_token'] != $csrfToken) {
            $output->header['location'] = $this->app->urlRoute(trim($input->getFrom($input->query, 'redirect', ''), '/'));
            $this->session->set('flash', array(
                array('type' => 'error', 'message' => 'invalid csrf token')
            ));
            $success = false;
        }

        return array($input, $output, $success);
    }
}