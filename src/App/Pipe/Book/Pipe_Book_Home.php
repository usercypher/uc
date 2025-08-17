<?php

class Pipe_Book_Home {
    private $app, $session;
    private $bookRepo;

    public function args($args) {
        list(
            $this->app, 
            $this->session,
            $this->bookRepo
        ) = $args;
    } 

    public function process($input, $output) {
        $success = true;

        
        $output->html($this->app->dirRoot('res/app/view/home.html.php'), array(
            'app' => $this->app,
            'output' => $output,

            'flash' => $this->session->unset('flash'),
            'csrf_token' => $this->session->get('csrf_token'),
            'books' => $this->bookRepo->list(),
        ));

        return array($input, $output, $success);
    }
}