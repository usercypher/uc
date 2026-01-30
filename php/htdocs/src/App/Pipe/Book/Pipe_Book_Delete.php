<?php

class Pipe_Book_Delete {
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

        list($book, $error) = $this->app->cast($book, $this->bookRepo->getSchema('delete'));

        if ($error) {
            foreach ($error as $e) {
                $this->bookRepo->addMessage($e['type'], $e['message'], $e['meta']);
            }
        } else {
            $this->bookRepo->delete($book['id']);
            $this->bookRepo->addMessage('success', 'book deleted successfully.');
        }

        $this->session->set('flash', $this->bookRepo->getMessages());

        $output->header['location'] = $this->app->urlRoute(trim($route, '/'));

        return array($input, $output, $success);
    }
}