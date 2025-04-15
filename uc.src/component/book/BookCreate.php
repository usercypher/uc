<?php

class BookCreate {
    private $app, $session;

    public function __construct($args) {
        list(
            $this->app, 
            $this->session, 
        ) = $args;
    } 

    public function process($request, $response) {
        $response->content = $response->view($this->app->path('view', 'create.php'), array(
            'app' => $this->app,
            'flash' => $this->session->unset('flash'),
            'csrf_token' => $this->session->get('csrf_token'),
        ));
        return array($request, $response);
    }
}