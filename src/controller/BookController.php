<?php

class BookController extends Controller {
    private $session;
    private $bookModel;

    public function __construct($d) {
        parent::__construct($d);
        $this->session = $d['Session'];
        $this->bookModel = $d['BookModel'];
    }

    public function index() {
        return $this->view('home.php', array(
            'app' => $this->app,
            'flash' => $this->session->unset('flash'),
            'csrf_token' => $this->session->get('csrf_token'),
            'books' => $this->bookModel->all()
        ));
    }

    public function create() {
        return $this->view('create.php', array(
            'app' => $this->app,
            'flash' => $this->session->unset('flash'),
            'csrf_token' => $this->session->get('csrf_token'),
        ));
    }

    public function store() {
        $data = $this->request->post;

        $route = $this->bookModel->validateAndCreate($data) ? 'home' : 'create';

        $this->session->set('flash', $this->bookModel->getFlash());

        return $this->redirect($route);
    }

    public function edit($param) {
        $data = $param;

        return $this->view('edit.php', array(
            'app' => $this->app,
            'flash' => $this->session->unset('flash'),
            'csrf_token' => $this->session->get('csrf_token'),
            'book' => $this->bookModel->first('id = ?', array($data['id']))
        ));
    }

    public function update() {
        $data = $this->request->post;

        $this->bookModel->validateAndUpdate($data);

        $this->session->set('flash', $this->bookModel->getFlash());

        return $this->redirect('edit/' . $data['book']['id']);
    }

    public function delete() {
        $data = $this->request->post;

        $this->bookModel->validateAndDelete($data);

        $this->session->set('flash', $this->bookModel->getFlash());

        return $this->redirect('home');
    }
}