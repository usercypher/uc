<?php

class Pipe_Book_Home {
    private $app, $session, $loggerService;
    private $bookModel;

    public function __construct($args = array()) {
        list(
            $this->app, 
            $this->session, 
            $this->loggerService,
            $this->bookModel
        ) = $args;
    } 

    public function pipe($request, $response) {
        $logger = $this->loggerService->get();
        $logger->info('home');

        return array($request, $response->html($this->app->path('res', 'html/home.php'), array(
            'app' => $this->app,
            'flash' => $this->session->unset('flash'),
            'csrf_token' => $this->session->get('csrf_token'),
            'books' => $this->bookModel->all(),
        )));
    }
}