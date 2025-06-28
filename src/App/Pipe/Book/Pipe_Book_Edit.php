<?php

class Pipe_Book_Edit {
    private $app, $session;
    private $bookModel;

    public function args($args) {
        list(
            $this->app, 
            $this->session, 
            $this->bookModel
        ) = $args;
    } 

    public function pipe($input, $output) {
        $break = false;

        $data = $input->params;
        $bookId = isset($data['title_id'][2]) ? $data['title_id'][2] : $data['id'];

        $output->html($this->app->path('res', 'html/edit.php'), array(
            'app' => $this->app,
            'flash' => $this->session->unset('flash'),
            'csrf_token' => $this->session->get('csrf_token'),
            'book' => $this->bookModel->first('id = ?', array($bookId))
        ));

        return array($input, $output, $break);
    }
}