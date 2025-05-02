<?php

class BookCreate {
    private $app, $session;

    public function __construct($args = array()) {
        list(
            $this->app, 
            $this->session, 
        ) = $args;
    } 

    public function pipe($request, $response) {
        $response->content = $response->html($this->app->path('res', 'html/create.php'), array(
            'app' => $this->app,
            'flash' => $this->session->unset('flash'),
            'csrf_token' => $this->session->get('csrf_token'),
        ));
        return array($request, $response);
    }
}