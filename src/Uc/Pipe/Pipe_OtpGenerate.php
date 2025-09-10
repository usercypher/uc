<?php

class Pipe_OtpGenerate {
    public $app, $session;

    public function args($args) {
        list(
            $this->app,
            $this->session
        ) = $args;
    }

    public function process($input, $output) {
        $success = true;

        $this->session->set('otp_token', random_int(100000, 999999));

        return array($input, $output, $success);
    }
}