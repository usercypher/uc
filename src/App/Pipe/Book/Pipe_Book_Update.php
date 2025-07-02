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

    public function pipe($input, $output) {
        $break = false;

        $data = $input->parsed;

        $this->bookRepo->validateAndUpdate($data);

        $this->session->set('flash', $this->bookRepo->getMessages());

        $output->redirect($this->app->url('route', 'edit/' . $data['book']['id']));

        return array($input, $output, $break);
    }
}