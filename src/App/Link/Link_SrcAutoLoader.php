<?php

class Link_SrcAutoLoader {
    private $src, $app;

    public function __construct($args = array()) {
        list(
            $this->app, 
        ) = $args;
    }

    public function link($request, $response) {
        $this->src = $this->app->path('root', 'src' . DS);
        spl_autoload_register(array($this, 'autoLoader'));

        return true;
    }

    public function autoLoader($class) {
        require $this->src . str_replace("\\", "/", $class) . ".php";
    }
}
