<?php

class Link_Book_Store {
    private $app, $session;
    private $bookModel;

    public function __construct($args = array()) {
        list(
            $this->app, 
            $this->session, 
            $this->bookModel
        ) = $args;
    } 

    public function link($request, $response) {
        $data = $request->post;

        $route = $this->bookModel->validateAndCreate($data) ? 'home' : 'create';

        $this->session->set('flash', $this->bookModel->getFlash());

        $response->redirect($this->app->url('route', $route));

        return true;
    }
}