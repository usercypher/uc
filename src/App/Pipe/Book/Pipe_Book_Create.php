<?php

class Pipe_Book_Create {
    private $app, $session, $html;

    public function args($args) {
        list(
            $this->app, 
            $this->session, 
            $this->html
        ) = $args;
    } 

    public function process($input, $output) {
        $success = true;

        $output->html($this->app->dirRes('html/create.php'), array(
            'app' => $this->app,
            'flash' => $this->session->unset('flash'),
            'csrf_token' => $this->session->get('csrf_token'),
        ));

        return array($input, $output, $success);
    }
}