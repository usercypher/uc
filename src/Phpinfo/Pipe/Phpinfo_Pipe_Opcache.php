<?php

class Phpinfo_Pipe_Opcache {
    private $app;

    public function args($args) {
        list(
            $this->app, 
        ) = $args;
    } 

    public function call($input, $output) {
        $success = true;

        $output->content = $this->app->template($this->app->dir('ROOT', 'src/Phpinfo/res/opcache.html.php'));

        return array($input, $output, $success);
    }
}
