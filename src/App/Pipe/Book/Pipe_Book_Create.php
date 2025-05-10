<?php

class Pipe_Book_Create {
    private $app, $session, $loggerService;

    public function __construct($args = array()) {
        list(
            $this->app, 
            $this->session, 
            $this->loggerService,
        ) = $args;
    } 

    public function pipe($request, $response) {
        $logger = $this->loggerService->get();
        $logger->info('create');

        return array($request, $response->html($this->app->path('res', 'html/create.php'), array(
            'app' => $this->app,
            'flash' => $this->session->unset('flash'),
            'csrf_token' => $this->session->get('csrf_token'),
        )));
    }
}