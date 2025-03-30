<?php

class LibExternalAutoLoaderMiddleware {
    private $lib;

    public function process($request, $response, $next) {
        $app = $next;

        $this->lib = $app->path('src', 'lib-external' . DS);
        spl_autoload_register(array($this, 'autoLoader'));

        return $next->process($request, $response, $next);
    }

    public function autoLoader($class) {
        require $this->lib . str_replace("\\", "/", $class) . ".php";
    }
}
