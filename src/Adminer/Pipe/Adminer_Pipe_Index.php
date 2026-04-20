<?php

class Adminer_Pipe_Index {
    private $app;

    public function args($args) {
        list(
            $this->app, 
        ) = $args;
    } 

    public function process($input, $output) {
        $success = true;

        $output->content = $this->app->template($this->app->dirRoot('src/Adminer/res/index.php'));

        return array($input, $output, $success);
    }
}