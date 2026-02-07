<?php

class App_Pipe_Default {
    private $app;

    public function args($args) {
        list(
            $this->app,
        ) = $args;
    } 

    public function process($input, $output) {
        $success = true;

        $output->content = $this->app->template($this->app->dirRoot('src/App/res/default.html.php'));

        return array($input, $output, $success);
    }
}