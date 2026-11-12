<?php

class Shared_Pipe_Event {
    var $args = array();

    function args($args) {
        $this->args = $args;
    } 

    function call($input, $output) {
        $success = true;

        foreach ($this->args as $arg) {
            list($input, $output, $success) = $arg->call($input, $output);
        }

        return array($input, $output, $success);
    }
}
