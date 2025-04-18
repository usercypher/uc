<?php

class BookStore {
    private $app, $session;
    private $bookModel;

    public function __construct($args) {
        list(
            $this->app, 
            $this->session, 
            $this->bookModel
        ) = $args;
    } 

    public function process($request, $response) {
        $data = $request->post;

        $route = $this->bookModel->validateAndCreate($data) ? 'home' : 'create';

        $this->session->set('flash', $this->bookModel->getFlash());

        $response->redirect($this->app->url('route', $route));

        return array($request, $response);
    }
}