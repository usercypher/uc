<?php

class Book_Pipe_Update {
    private $app, $session;
    private $bookRepo;

    public function args($args) {
        list(
            $this->app, 
            $this->session, 
            $this->bookRepo
        ) = $args;
    } 

    public function process($input, $output) {
        $success = true;

        $route = $input->query['redirect'];
        $book = $input->frame['book'];
        $bookOld = $input->frame['book_old'];

        list($book, $error) = $this->app->cast($book, $this->bookRepo->getSchema('update', array(
            'book_old' => $bookOld,
        )));
        if ($error) {
            foreach ($error as $e) {
                $this->bookRepo->addMessage($e['type'], $e['message'], $e['meta']);
            }
        } else {
            $this->bookRepo->update($book);
            $this->bookRepo->addMessage('success', 'book updated successfully.');
        }

        $this->session->set('flash', $this->bookRepo->getMessages());

        $output->header['location'] = $this->app->urlRoute(trim($route, '/'));

        return array($input, $output, $success);
    }
}