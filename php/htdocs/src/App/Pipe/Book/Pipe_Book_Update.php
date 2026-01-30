<?php

class Pipe_Book_Update {
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

        $data = $input->frame;

        $route = trim(isset($input->query['redirect']) ? $input->query['redirect'] : '', '/');

        list($book, $error) = $this->app->cast($data['book'], $this->bookRepo->getSchema('update', array(
            'book_old' => $data['book_old'],
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

        $output->header['location'] = $this->app->urlRoute($route);

        return array($input, $output, $success);
    }
}