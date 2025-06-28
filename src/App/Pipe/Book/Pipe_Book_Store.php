<?php

class Pipe_Book_Store {
    private $app, $session;
    private $bookModel;

    public function args($args) {
        list(
            $this->app, 
            $this->session, 
            $this->bookModel
        ) = $args;
    } 

    public function pipe($input, $output) {
        $break = false;

        $data = $input->data;

        $route = $this->bookModel->validateAndCreate($data) ? 'home' : 'create';

        $this->session->set('flash', $this->bookModel->getFlash());

        $output->redirect($this->app->url('route', $route));

        return array($input, $output, $break);
    }
}