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

        $slug = explode('-', isset($input->param['slug']) ? $input->param['slug'] : '');

        $bookId = $slug[count($slug) - 1];
        if (!is_numeric($bookId)) {
            trigger_error('500|id not found in url.', E_USER_WARNING);
            return array($input, $output, $success);
        }

        $output->content = $this->app->template($this->app->dirRoot('res/App/view/edit.html.php'), array(
            'app' => $this->app,
            'current_route' => $input->route,
            'csrf_token' => $this->session->get('csrf_token'),
            'book' => $this->bookRepo->one('WHERE id = ?', array($bookId)),
            'partial_script' => $this->app->template($this->app->dirRoot('res/App/view/partial/script.html.php'), array(
               'flash' => $this->session->unset('flash'),
            )),
        ));

        return array($input, $output, $success);
    }
}