<?php

class Pipe_OtpValidate {
    public $app, $session;

    public function args($args) {
        list(
            $this->app,
            $this->session
        ) = $args;
    }

    public function process($input, $output) {
        $success = true;

        if (!isset($input->parsed['otp_token'])) {
            $output->redirect($this->app->urlRoute(trim($input->getFrom($input->query, 'redirect', ''), '/')));
            $this->session->set('flash', array(
                array('type' => 'error', 'message' => 'invalid otp token')
            ));
            $success = false;
        }

        $otpToken = $this->session->get('otp_token');

        if (!$otpToken) {
            $output->redirect($this->app->urlRoute(trim($input->getFrom($input->query, 'redirect', ''), '/')));
            $this->session->set('flash', array(
                array('type' => 'error', 'message' => 'invalid otp token')
            ));
            $success = false;
        }

        if ($input->parsed['otp_token'] != $otpToken) {
            $output->redirect($this->app->urlRoute(trim($input->getFrom($input->query, 'redirect', ''), '/')));
            $this->session->set('flash', array(
                array('type' => 'error', 'message' => 'invalid otp token')
            ));
            $success = false;
        }

        return array($input, $output, $success);
    }
}