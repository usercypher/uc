<?php

class Cli_Pipe_Unit_List {
    var $app;

    function args($args) {
        list($this->app) = $args;
    }

    function call($input, $output) {
        $success = true;
        $message = '';

        $message = implode("\n", $this->app->unitList) . "\n";

        $output->content = $message;

        return array($input, $output, $success);
    }
}
