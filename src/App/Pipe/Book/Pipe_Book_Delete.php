<?php

class Pipe_Book_Delete {
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

        $data = $input->parsed;

        $this->bookModel->validateAndDelete($data);

        $this->session->set('flash', $this->bookModel->getFlash());

        $output->redirect($this->app->url('route', 'home'));

        return array($input, $output, $break);
    }
}