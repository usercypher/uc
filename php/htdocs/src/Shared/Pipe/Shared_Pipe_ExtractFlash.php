<?php

class Shared_Pipe_ExtractFlash {
    var $app, $session;

    function args($args) {
        list($this->app, $this->session) = $args;
    }

    function process($input, $output) {
        $success = true;

        $output->content = json_encode($this->session->get('flash'), JSON_PRETTY_PRINT) . "\n";
        return array($input, $output, $success);
    }
}
