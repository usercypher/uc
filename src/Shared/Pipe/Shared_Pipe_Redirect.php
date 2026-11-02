<?php

class Shared_Pipe_Redirect {
    var $app;
    var $key = 'route';
    var $redirect = '';

    function args($args) {
        $this->app = $args[0];
        $this->key = isset($args[1]) ? $args[1] : $this->key;
        $this->redirect = isset($args[2]) ? $args[2] : $this->redirect;
    } 

    function call($input, $output) {
        $success = true;

        $output->header['location'] = $this->app->url($this->key, $this->redirect);

        return array($input, $output, $success);
    }
}
