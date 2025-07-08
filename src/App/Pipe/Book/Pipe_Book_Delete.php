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

        $data = $input->parsed;

        $this->bookRepo->validateAndDelete($data);

        $this->session->set('flash', $this->bookRepo->getMessages());

        $output->redirect($this->app->urlRoute('home'));

        return array($input, $output, $success);
    }
}