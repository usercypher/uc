<?php

class Book_Pipe_Create {
    private $app, $session;

    public function args($args) {
        list(
            $this->app, 
            $this->session
        ) = $args;
    } 

    public function process($input, $output) {
        $success = true;

        $output->content = $this->app->template($this->app->dirRoot('src/Book/res/create.html.php'), array(
            'app' => $this->app,
            'current_route' => $input->route,
            'csrf_token' => $this->session->get('csrf_token'),
            'partial_script' => $this->app->template($this->app->dirRoot('src/App/res/partial/script.html.php'), array(
               'flash' => $this->session->unset('flash'),
            )),
        ));

        return array($input, $output, $success);
    }
}