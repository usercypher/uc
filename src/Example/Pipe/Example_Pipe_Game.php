<?php

class Example_Pipe_Game {
    private $app, $session;

    public function args($args) {
        list(
            $this->app,
            $this->session,
        ) = $args;
    } 

    public function process($input, $output) {
        $success = true;

        $output->content = $this->app->template($this->app->dir('ROOT', 'src/Game/res/index.html.php'), array(
            'app' => $this->app
        ));

        return array($input, $output, $success);
    }
}