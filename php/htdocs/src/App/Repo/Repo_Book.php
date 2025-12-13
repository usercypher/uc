<?php

class Repo_Book extends Lib_DatabaseHelper {
    public function args($args) {
        list(
            $app,
            $database
        ) = $args;

        $database->connect([
            'host' => $app->getEnv('DB_HOST'), 
            'port' => $app->getEnv('DB_PORT'),
            'name' => $app->getEnv('DB_NAME'),
            'user' => $app->getEnv('DB_USER'),
            'pass' => $app->getEnv('DB_PASS'),
            'time' => $app->getEnv('DB_TIME', '+00:00')
        ]);

        parent::setDb($database);
        parent::setTable('books');
    }

    public function validateAndInsert($data) {
        $bookData = $data['book'];
        if ($this->exists('WHERE title = ?', array($bookData['title']))) {
            $this->addMessage('error', 'Title Already Exists.');
            return false;
        }

        $this->insert($bookData);

        $this->addMessage('success', 'Book created successfully.');

        return true;
    }

    public function validateAndUpdate($data) {
        $bookData = $data['book'];
        if ($this->exists('WHERE title = ?', array($bookData['title']['new'])) && $bookData['title']['current'] !== $bookData['title']['new']) {
            $this->addMessage('error', 'Title Already Exists.');
            return false;
        }

        $book = $this->one('WHERE id = ?', array($bookData['id']));

        $book['title'] = $bookData['title']['new'];
        $book['author'] = $bookData['author'];
        $book['publisher'] = $bookData['publisher'];
        $book['year'] = $bookData['year'];

        $this->update($book);

        $this->addMessage('success', 'Book updated successfully.');

        return true;
    }

    public function validateAndDelete($data) {
        $bookData = $data['book'];

        if (!$this->exists('WHERE id = ?', array($bookData['id']))) {
            $this->addMessage('error', 'Book not found.');
            return false;
        }

        $this->delete($bookData['id']);

        $this->addMessage('success', 'Book deleted successfully.');

        return true;
    }
}
?>