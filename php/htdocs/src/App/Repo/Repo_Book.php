<?php

class Repo_Book extends Lib_DatabaseHelper {
    public function args($args) {
        list(
            $app,
            $database
        ) = $args;

        parent::setDb($database, $database->connect([
            'host' => $app->getEnv('DB_HOST'), 
            'port' => $app->getEnv('DB_PORT'),
            'name' => $app->getEnv('DB_NAME'),
            'user' => $app->getEnv('DB_USER'),
            'pass' => $app->getEnv('DB_PASS'),
            'time' => $app->getEnv('DB_TIME', '+00:00')
        ]));
        parent::setTable('books');
        parent::setSchema([
            'id' => 'integer',
            'title' => 'string',
            'publisher' => 'string',
            'author' => 'string',
            'year' => 'string',
        ]);
    }

    public function validateAndInsert($data) {
        $bookData = $this->cast($data['book']);

        if ($this->exists('WHERE title = ?', array($bookData['title']))) {
            $this->addMessage('error', 'Title Already Exists.');
            return false;
        }

        $this->insert($bookData);

        $this->addMessage('success', 'Book created successfully.');

        return true;
    }

    public function validateAndUpdate($data) {
        $bookData = $this->cast($data['book']);
        $bookOldData = $this->cast($data['book_old']);

        if ($bookOldData['title'] !== $bookData['title'] && $this->exists('WHERE title = ?', array($bookData['title']))) {
            $this->addMessage('error', 'Title Already Exists.');
            return false;
        }

        $this->update($bookData);

        $this->addMessage('success', 'Book updated successfully.');

        return true;
    }

    public function validateAndDelete($data) {
        $bookData = $this->cast($data['book']);

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