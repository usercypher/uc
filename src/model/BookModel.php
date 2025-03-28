<?php

class BookModel extends Model {
    public function __construct($dependencies) {
        parent::setConn($dependencies['Database']->getConnection());
        parent::setTable('books');
    }

    public function validateAndCreate($data) {
        $bookData = $data['book'];
        if ($this->exists('title = ?', array($bookData['title']))) {
            $this->addFlash('error', 'Title Already Exists.');
            return false;
        }

        $this->create($bookData);

        $this->addFlash('success', 'Book created successfully.');

        return true;
    }

    public function validateAndUpdate($data) {
        $bookData = $data['book'];
        if (empty($bookData['title'])) {
            $this->addFlash('error', 'Title is empty.');
            return false;
        }

        if ($this->exists('title = ?', array($bookData['title']['new'])) && $bookData['title']['current'] !== $bookData['title']['new']) {
            $this->addFlash('error', 'Title Already Exists.');
            return false;
        }

        $book = $this->first('id = ?', array($bookData['id']));

        $book['title'] = $bookData['title']['new'];
        $book['author'] = $bookData['author'];
        $book['publisher'] = $bookData['publisher'];
        $book['year'] = $bookData['year'];

        $this->update($book['id'], $book);

        $this->addFlash('success', 'Book updated successfully.');

        return true;
    }

    public function validateAndDelete($data) {
        $bookData = $data['book'];

        if (!$this->exists('id = ?', array($bookData['id']))) {
            $this->addFlash('error', 'Book not found.');
            return false;
        }

        $this->delete($bookData['id']);

        $this->addFlash('success', 'Book deleted successfully.');

        return true;
    }
}
?>