<?php

class BookUpdate {
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

        $this->bookModel->validateAndUpdate($data);

        $this->session->set('flash', $this->bookModel->getFlash());

        $response->redirect($this->app->url('route', 'edit/' . $data['book']['id']));

        return array($request, $response);
    }
}