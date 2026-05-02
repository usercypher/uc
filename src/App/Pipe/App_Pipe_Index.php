<?php

class App_Pipe_Index {
    private $app;

    public function args($args) {
        list(
            $this->app, 
        ) = $args;
    } 

    public function process($input, $output) {
        $success = true;

        $output->content = $this->app->template($this->app->dir('ROOT', 'src/App/res/index.html.php'), array(
            'app' => $this->app
        ));

        return array($input, $output, $success);
    }
}