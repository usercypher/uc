<?php

class App_Pipe_Init {
    private $app, $session;

    public function args($args) {
        list(
            $this->app, 
            $this->session
        ) = $args;
    } 

    public function call($input, $output) {
        $success = true;

        $this->session->init(array(
            'name' => $this->app->env['app_id'] . ':session'
        ));

        return array($input, $output, $success);
    }
}