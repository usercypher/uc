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

        $data = $input->frame;

        $route = trim(isset($input->query['redirect']) ? $input->query['redirect'] : '', '/');

        $route = $this->bookRepo->validateAndInsert($data) ? 'home' : $route;

        $this->session->set('flash', $this->bookRepo->getMessages());

        $output->header['location'] = $this->app->urlRoute($route);

        return array($input, $output, $success);
    }
}