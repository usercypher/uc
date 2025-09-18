<?php

class Pipe_OtpExist {
    public $app, $session;

    public function args($args) {
        list(
            $this->app,
            $this->session
        ) = $args;
    }

    public function process($input, $output) {
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