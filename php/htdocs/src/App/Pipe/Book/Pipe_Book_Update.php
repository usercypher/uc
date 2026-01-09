<?php

class Pipe_Book_Update {
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

        $this->bookRepo->validateAndUpdate($data);

        $this->session->set('flash', $this->bookRepo->getMessages());

        $output->header['location'] = $this->app->urlRoute($route);

        return array($input, $output, $success);
    }
}