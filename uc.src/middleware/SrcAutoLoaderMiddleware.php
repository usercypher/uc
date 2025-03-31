<?php

class SrcAutoLoaderMiddleware {
    private $src;

    public function process($request, $response, $next) {
        $app = $next;

        $this->src = $app->path('root', 'src' . DS);
        spl_autoload_register(array($this, 'autoLoader'));

        return $next->process($request, $response, $next);
    }

    public function autoLoader($class) {
        require $this->src . str_replace("\\", "/", $class) . ".php";
    }
}
