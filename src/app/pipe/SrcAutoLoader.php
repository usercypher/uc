<?php

class SrcAutoLoader {
    private $src, $app;

    public function __construct($args = array()) {
        list(
            $this->app, 
        ) = $args;
    }

    public function pipe($request, $response) {
        $this->src = $app->path('root', 'src' . DS);
        spl_autoload_register(array($this, 'autoLoader'));

        return array($request, $response);
    }

    public function autoLoader($class) {
        require $this->src . str_replace("\\", "/", $class) . ".php";
    }
}
