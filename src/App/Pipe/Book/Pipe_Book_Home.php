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

    public function pipe($input, $output) {
        $break = false;

        $output->html($this->app->dirRes('html/home.php'), array(
            'app' => $this->app,
            'flash' => $this->session->unset('flash'),
            'csrf_token' => $this->session->get('csrf_token'),
            'books' => $this->bookRepo->all(),
        ));

        return array($input, $output, $break);
    }
}