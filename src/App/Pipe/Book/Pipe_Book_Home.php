<?php

class Pipe_Book_Home {
    private $app, $session, $html;
    private $bookRepo;

    public function args($args) {
        list(
            $this->app, 
            $this->session, 
            $this->html, 
            $this->bookRepo
        ) = $args;
    } 

    public function process($input, $output) {
        $success = true;

        $output->html($this->app->dirRes('html/home.php'), array(
            'app' => $this->app,
            'flash' => $this->session->unset('flash'),
            'csrf_token' => $this->session->get('csrf_token'),
            'html' => $this->html,
            'books' => $this->bookRepo->all(),
        ));

        return array($input, $output, $success);
    }
}