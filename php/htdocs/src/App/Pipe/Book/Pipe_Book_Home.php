<?php

class Pipe_Book_Home {
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
        
        $output->content = $this->app->template($this->app->dirRoot('res/App/view/home.html.php'), array(
            'app' => $this->app,
            'current_route' => $input->route,
            'csrf_token' => $this->session->get('csrf_token'),
            'books' => $this->bookRepo->all(),
            'partial_script' => $this->app->template($this->app->dirRoot('res/App/view/partial/script.html.php'), array(
               'flash' => $this->session->unset('flash'),
            )),
        ));

        return array($input, $output, $success);
    }
}