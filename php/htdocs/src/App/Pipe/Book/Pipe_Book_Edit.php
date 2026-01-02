<?php

class Pipe_Book_Edit {
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

        $data = $input->param;
        $bookId = isset($data['title_id'][2]) ? $data['title_id'][2] : $data['id'];

        $output->content = $this->app->template($this->app->dirRoot('res/App/view/edit.html.php'), array(
            'app' => $this->app,
            'current_route' => $input->route,
            'flash' => $this->session->unset('flash'),
            'csrf_token' => $this->session->get('csrf_token'),
            'book' => $this->bookRepo->one('WHERE id = ?', array($bookId)),
        ));

        return array($input, $output, $success);
    }
}