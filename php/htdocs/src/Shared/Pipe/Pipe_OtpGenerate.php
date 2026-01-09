<?php

class Pipe_OtpGenerate {
    var $app, $session;

    function args($args) {
        list($this->app, $this->session) = $args;
    }

    function process($input, $output) {
        $success = true;

        $this->session->set('otp_token', random_int(100000, 999999));

        return array($input, $output, $success);
    }
}
