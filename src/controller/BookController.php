<?php

class BookController extends ExtController {
    protected $request, $response;
    private $bookModel;

    public function __construct($dependencies) {
        $this->request = $dependencies['Request'];
        $this->response = $dependencies['Response'];
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

        $route = $this->createBook($data) ? 'home' : 'create';

        $_SESSION['flash'] = $this->getFlash();

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

        $this->updateBook($data);

        $_SESSION['flash'] = $this->getFlash();

        return $this->redirect('edit/' . $data['book']['id']);
    }

    public function delete() {
        $data = $this->request->post;

        $this->deleteBook($data);

        $_SESSION['flash'] = $this->getFlash();

        return $this->redirect('home');
    }

    /**
    * Helper Methods
    *
    */

    private function createBook($data) {
        $bookData = $data['book'];
        if ($this->bookModel->exists('title = ?', array($bookData['title']))) {
            $this->addFlash('error', 'Title Already Exists.');
            return false;
        }

        $this->bookModel->create($bookData);

        $this->addFlash('success', 'Book created successfully.');

        return true;
    }

    private function updateBook($data) {
        $bookData = $data['book'];
        if (empty($bookData['title'])) {
            $this->addFlash('error', 'Title is empty.');
            return false;
        }

        if ($this->bookModel->exists('title = ?', array($bookData['title']['new'])) && $bookData['title']['current'] !== $bookData['title']['new']) {
            $this->addFlash('error', 'Title Already Exists.');
            return false;
        }

        $book = $this->bookModel->first('id = ?', array($bookData['id']));

        $book['title'] = $bookData['title']['new'];
        $book['author'] = $bookData['author'];
        $book['publisher'] = $bookData['publisher'];
        $book['year'] = $bookData['year'];

        $this->bookModel->update($book['id'], $book);

        $this->addFlash('success', 'Book updated successfully.');

        return true;
    }

    private function deleteBook($data) {
        $bookData = $data['book'];

        if (!$this->bookModel->exists('id = ?', array($bookData['id']))) {
            $this->addFlash('error', 'Book not found.');
            return false;
        }

        $this->bookModel->delete($bookData['id']);

        $this->addFlash('success', 'Book deleted successfully.');

        return true;
    }
}