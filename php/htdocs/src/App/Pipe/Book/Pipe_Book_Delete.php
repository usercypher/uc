<?php

class Pipe_Book_Delete {
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

        $route = trim($input->getFrom($input->query, 'redirect', ''), '/');

        $this->bookRepo->validateAndDelete($data);

        $this->session->set('flash', $this->bookRepo->getMessages());

        $output->header['location'] = $this->app->urlRoute($route);

        return array($input, $output, $success);
    }
}