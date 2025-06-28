<?php

class Pipe_SrcAutoLoader {
    private $src, $app;

    public function args($args) {
        list(
            $this->app, 
        ) = $args;
    }

    public function pipe($input, $output) {
        $break = false;
        $this->src = $this->app->path('root', 'src' . DS);
        spl_autoload_register(array($this, 'autoLoader'));

        return array($input, $output, $break);
    }

    public function autoLoader($class) {
        require $this->src . str_replace("\\", "/", $class) . ".php";
    }
}
