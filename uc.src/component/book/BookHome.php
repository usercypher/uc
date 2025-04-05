<?php

class BookHome {
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
        $response = $response->view($this->app->path('view', 'home.php'), array(
            'app' => $this->app,
            'flash' => $this->session->unset('flash'),
            'csrf_token' => $this->session->get('csrf_token'),
            'books' => $this->bookModel->all()
        ));

        return array($request, $response);
    }
}