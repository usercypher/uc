<?php

class Shared_Pipe_Header {
    var $app;
    var $header = array(
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
    );

    function args($args) {
        $this->app = $args[0];
        $this->header = isset($args[1]) ? $args[1] : $this->header;
    } 

    function call($input, $output) {
        $success = true;

        foreach ($this->header as $key => $value) {
            $output->header[$key] = $value;
        }

        return array($input, $output, $success);
    }
}
