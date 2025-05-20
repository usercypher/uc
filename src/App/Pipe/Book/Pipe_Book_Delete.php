<?php

class Pipe_Book_Delete {
    private $app, $session;
    private $bookModel;

    public function __construct($args = array()) {
        list(
            $this->app, 
            $this->session, 
            $this->bookModel
        ) = $args;
    } 

    public function pipe($request, $response) {
        $data = $request->post;

        $this->bookModel->validateAndDelete($data);

        $this->session->set('flash', $this->bookModel->getFlash());

        $response->redirect($this->app->url('route', 'home'));

        return array($request, $response);
    }
}