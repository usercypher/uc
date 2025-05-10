<?php

class Pipe_Book_Store {
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
        $data = $request->post;

        $route = $this->bookModel->validateAndCreate($data) ? 'home' : 'create';

        $this->session->set('flash', $this->bookModel->getFlash());

        $logger = $this->loggerService->get();
        foreach ($this->bookModel->getFlash() as $flash) {
            $logger->info($flash['type'] . ': ' . $flash['message']);
        }

        return array($request, $response->redirect($this->app->url('route', $route)));
    }
}