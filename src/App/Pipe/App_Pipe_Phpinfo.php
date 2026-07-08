<?php

class App_Pipe_Phpinfo {
    private $app;

    public function args($args) {
        list(
            $this->app, 
        ) = $args;
    } 

    public function process($input, $output) {
        $success = true;

        $output->content = $this->app->template($this->app->dir('ROOT', 'src/App/res/phpinfo.html.php'));

        return array($input, $output, $success);
    }
}