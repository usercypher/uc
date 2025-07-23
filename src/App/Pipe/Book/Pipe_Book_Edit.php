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

        $data = $input->params;
        $bookId = isset($data['title_id'][2]) ? $data['title_id'][2] : $data['id'];

        $output->html($this->app->dirRes('app/edit.html.php'), array(
            'app' => $this->app,
            'output' => $output,

            'flash' => $this->session->unset('flash'),
            'csrf_token' => $this->session->get('csrf_token'),
            'book' => $this->bookRepo->first('id = ?', array($bookId)),
        ));

        return array($input, $output, $success);
    }
}