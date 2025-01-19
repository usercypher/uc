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
        
        if ($this->createBook($data)){
            return $this->redirect('/home');
        } else {
            $_SESSION['errors'] = $this->getErrors();
            return $this->redirect('/create');
        }
    }
    
    public function edit($param) {
        $data = $param;

        return $this->view('edit.php', array(
            'book' => $this->bookModel->first('id = ?', array($data['id']))
        ));
    }
    
    public function update() {
        $data = $this->request->post;
        
        if ($this->updateBook($data)){
            return $this->redirect('/edit/' . $data['book']['id']);
        } else {
            $_SESSION['errors'] = $this->getErrors();
            return $this->redirect('/edit/' . $data['book']['id']);
        }
    }
    
    public function delete() {
        $data = $this->request->post;
        
        if ($this->deleteBook($data)){
            return $this->redirect('/home');
        } else {
            $_SESSION['errors'] = $this->getErrors();
            return $this->redirect('/home');
        }
    }
    
/**
 * Helper Methods
 * 
*/
    
    private function createBook($data) {
        $bookData = $data['book'];
        if ($this->bookModel->exists('title = ?', array($bookData['title']))){
            $this->addError('title', 'Title Already Exists.');
            return false;
        } 
                
        $this->bookModel->create($bookData);
        
        return true;
    }
    
    private function updateBook($data) {
        $bookData = $data['book'];
        if (empty($bookData['title'])) {
            $this->addError('title', 'Title is empty.');
            return false;
        }
        
        if ($this->bookModel->exists('title = ?', array($bookData['title']['new'])) && $bookData['title']['current'] !== $bookData['title']['new']){
            $this->addError('title', 'Title Already Exists.');
            return false;
        } 
        
        $book = $this->bookModel->first('id = ?', array($bookData['id']));
        
        $book['title'] = $bookData['title']['new'];
        $book['publisher'] = $bookData['publisher'];
        $book['year'] = $bookData['year'];

        $this->bookModel->update($book['id'], $book);
        
        return true;
    }
    
    private function deleteBook($data) {
        $bookData = $data['book'];
        
        if (!$this->bookModel->exists('id = ?', array($bookData['id']))) {
            $this->addError('book', 'Book not found.');
            return false;
        }
        
        $this->bookModel->delete($bookData['id']);

        return true;
    }
}
