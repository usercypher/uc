<?php

class Shared_Pipe_OtpValidate {
    var $app, $session;

    function args($args) {
        list($this->app, $this->session) = $args;
    }

    function process($input, $output) {
        $success = true;

        if (!isset($input->frame['otp_token'])) {
            $output->header['location'] = $this->app->urlRoute($route = trim(isset($input->query['redirect']) ? $input->query['redirect'] : '', '/'));
            $this->session->set('flash', array(array('type' => 'error', 'message' => 'invalid otp token')));
            $success = false;
        }

        $otpToken = $this->session->get('otp_token');

        if (!$otpToken) {
            $output->header['location'] = $this->app->urlRoute($route = trim(isset($input->query['redirect']) ? $input->query['redirect'] : '', '/'));
            $this->session->set('flash', array(array('type' => 'error', 'message' => 'invalid otp token')));
            $success = false;
        }

        if ($input->frame['otp_token'] != $otpToken) {
            $output->header['location'] = $this->app->urlRoute($route = trim(isset($input->query['redirect']) ? $input->query['redirect'] : '', '/'));
            $this->session->set('flash', array(array('type' => 'error', 'message' => 'invalid otp token')));
            $success = false;
        }

        return array($input, $output, $success);
    }
}
