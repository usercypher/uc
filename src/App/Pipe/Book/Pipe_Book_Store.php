<?php

class Pipe_Book_Store {
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

        $data = $input->parsed;

        $route = $this->bookRepo->validateAndInsert($data) ? 'home' : 'create';

        $this->session->set('flash', $this->bookRepo->getMessages());

        $output->redirect($this->app->urlRoute($route));

        return array($input, $output, $success);
    }
}