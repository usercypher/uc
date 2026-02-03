<?php

class App_Pipe_Init {
    private $app, $session;

    public function args($args) {
        list(
            $this->app, 
            $this->session,
        ) = $args;
    } 

    public function process($input, $output) {
        $success = true;

        $this->session->name('SESSION_ID');
        $this->session->start();

        return array($input, $output, $success);
    }
}