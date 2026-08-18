<?php

class Phpinfo_Pipe_Index {
    private $app;

    public function args($args) {
        list(
            $this->app, 
        ) = $args;
    } 

    public function call($input, $output) {
        $success = true;

        $output->content = $this->app->template($this->app->dir('ROOT', 'src/Phpinfo/res/index.html.php'));

        return array($input, $output, $success);
    }
}