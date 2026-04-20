<?php

class Shared_Pipe_SessionTokenValidate {
    var $app, $session;

    function args($args) {
        list($this->app, $this->session) = $args;
    }

    function process($input, $output) {
        $success = true;

        if (!isset($input->frame['session_token'])) {
            $output->header['location'] = $this->app->urlRoute($route = trim(isset($input->query['redirect']) ? $input->query['redirect'] : '', '/'));
            $this->session->set('flash', array(array('type' => 'message:error', 'data' => array('content' => 'invalid session token'))));
            $success = false;
        }

        $csrfToken = $this->session->get('session_token');

        if (!$csrfToken) {
            $output->header['location'] = $this->app->urlRoute($route = trim(isset($input->query['redirect']) ? $input->query['redirect'] : '', '/'));
            $this->session->set('flash', array(array('type' => 'message:error', 'data' => array('content' => 'invalid session token'))));
            $success = false;
        }

        if ($input->frame['session_token'] != $csrfToken) {
            $output->header['location'] = $this->app->urlRoute($route = trim(isset($input->query['redirect']) ? $input->query['redirect'] : '', '/'));
            $this->session->set('flash', array(array('type' => 'message:error', 'data' => array('content' => 'invalid session token'))));
            $success = false;
        }

        return array($input, $output, $success);
    }
}
