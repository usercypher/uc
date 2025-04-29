<?php

class BookDelete {
    private $app, $session;
    private $bookModel;

    public function __construct($args) {
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