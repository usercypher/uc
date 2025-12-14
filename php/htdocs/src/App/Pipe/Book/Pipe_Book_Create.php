<?php

class Pipe_Book_Create {
    private $app, $session;

    public function args($args) {
        list(
            $this->app, 
            $this->session
        ) = $args;
    } 

    public function process($input, $output) {
        $success = true;

        $output->content = $this->app->template($this->app->dirRoot('res/app/view/create.html.php'), array(
            'app' => $this->app,
            'current_route' => $input->route,
            'flash' => $this->session->unset('flash'),
            'csrf_token' => $this->session->get('csrf_token'),
        ));

        return array($input, $output, $success);
    }
}