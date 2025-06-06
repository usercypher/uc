<?php

class Pipe_SrcAutoLoader {
    private $src, $app;

    public function __construct($args = array()) {
        list(
            $this->app, 
        ) = $args;
    }

    public function pipe($request, $response) {
        $break = false;
        $this->src = $this->app->path('root', 'src' . DS);
        spl_autoload_register(array($this, 'autoLoader'));

        return array($request, $response, $break);
    }

    public function autoLoader($class) {
        require $this->src . str_replace("\\", "/", $class) . ".php";
    }
}
