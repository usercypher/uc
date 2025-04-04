<?php

class BookController {
    private $app, $session;
    private $bookModel;

    public function __construct($args) {
        list(
            $this->app, 
            $this->session, 
            $this->bookModel
        ) = $args;
    } 

    public function index($request, $response) {
        return $response->view($this->app->path('view', 'home.php'), array(
            'app' => $this->app,
            'flash' => $this->session->unset('flash'),
            'csrf_token' => $this->session->get('csrf_token'),
            'books' => $this->bookModel->all()
        ));
    }

    public function create($request, $response) {
        return $response->view($this->app->path('view', 'create.php'), array(
            'app' => $this->app,
            'flash' => $this->session->unset('flash'),
            'csrf_token' => $this->session->get('csrf_token'),
        ));
    }

    public function store($request, $response) {
        $data = $request->post;

        $route = $this->bookModel->validateAndCreate($data) ? 'home' : 'create';

        $this->session->set('flash', $this->bookModel->getFlash());

        return $response->redirect($this->app->url('route', $route));
    }

    public function edit($request, $response) {
        $data = $request->params;

        return $response->view($this->app->path('view', 'edit.php'), array(
            'app' => $this->app,
            'flash' => $this->session->unset('flash'),
            'csrf_token' => $this->session->get('csrf_token'),
            'book' => $this->bookModel->first('id = ?', array($data['id']))
        ));
    }

    public function update($request, $response) {
        $data = $request->post;

        $this->bookModel->validateAndUpdate($data);

        $this->session->set('flash', $this->bookModel->getFlash());

        return $response->redirect($this->app->url('route', 'edit/' . $data['book']['id']));
    }

    public function delete($request, $response) {
        $data = $request->post;

        $this->bookModel->validateAndDelete($data);

        $this->session->set('flash', $this->bookModel->getFlash());

        return $response->redirect($this->app->url('route', 'home'));
    }
}