<?php

class BookController extends Controller {
    private $bookModel;

    public function __construct($dependencies) {
        parent::__construct($dependencies['App'], $dependencies['Request'], $dependencies['Response']);
        $this->bookModel = $dependencies['BookModel'];
    }

    public function index() {
        return $this->view('home.php', array(
            'books' => $this->bookModel->all()
        ));
    }

    public function create() {
        return $this->view('create.php', array());
    }

    public function store() {
        $data = $this->request->post;

        $route = $this->bookModel->validateAndCreate($data) ? 'home' : 'create';

        $_SESSION['flash'] = $this->bookModel->getFlash();

        return $this->redirect($route);
    }

    public function edit($param) {
        $data = $param;

        return $this->view('edit.php', array(
            'book' => $this->bookModel->first('id = ?', array($data['id']))
        ));
    }

    public function update() {
        $data = $this->request->post;

        $this->bookModel->validateAndUpdate($data);

        $_SESSION['flash'] = $this->bookModel->getFlash();

        return $this->redirect('edit/' . $data['book']['id']);
    }

    public function delete() {
        $data = $this->request->post;

        $this->bookModel->validateAndDelete($data);

        $_SESSION['flash'] = $this->bookModel->getFlash();

        return $this->redirect('home');
    }
}