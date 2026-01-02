<?php

class Pipe_OtpExist {
    var $app, $session;

    function args($args) {
        list(
            $this->app,
            $this->session
        ) = $args;
    }

    function process($input, $output) {
        $success = true;

        $otpToken = $this->session->get('otp_token');

        if (!$otpToken) {
            $output->redirect($this->app->urlRoute('home'));
            $this->session->set('flash', array(
                array('type' => 'error', 'message' => 'Otp not found. please resend code again.'),
            ));
            $success = false;
        }

        return array($input, $output, $success);
    }
}