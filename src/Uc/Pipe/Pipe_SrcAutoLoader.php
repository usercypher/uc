<?php

class Pipe_SrcAutoLoader {
    private $src, $app;

    public function args($args) {
        list(
            $this->app, 
        ) = $args;
    }

    public function process($input, $output) {
        $success = true;
        $this->src = $this->app->dirRoot('src' . DS);
        spl_autoload_register(array($this, 'autoLoader'));

        return array($input, $output, $success);
    }

    public function autoLoader($class) {
        require $this->src . str_replace("\\", "/", $class) . ".php";
    }
}
