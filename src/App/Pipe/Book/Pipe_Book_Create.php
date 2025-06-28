<?php

class Pipe_Book_Create {
    private $app, $session;

    public function args($args) {
        list(
            $this->app, 
            $this->session, 
        ) = $args;
    } 

    public function pipe($input, $output) {
        $break = false;

        $output->html($this->app->path('res', 'html/create.php'), array(
            'app' => $this->app,
            'flash' => $this->session->unset('flash'),
            'csrf_token' => $this->session->get('csrf_token'),
        ));

        return array($input, $output, $break);
    }
}