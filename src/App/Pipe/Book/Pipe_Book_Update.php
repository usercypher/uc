<?php

class Pipe_Book_Update {
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

        $this->bookModel->validateAndUpdate($data);

        $this->session->set('flash', $this->bookModel->getFlash());

        $output->redirect($this->app->url('route', 'edit/' . $data['book']['id']));

        return array($input, $output, $break);
    }
}