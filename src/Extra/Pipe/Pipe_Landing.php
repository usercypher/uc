<?php

class Pipe_Landing {
    private $app;

    public function args($args) {
        list(
            $this->app,
        ) = $args;
    }

    public function pipe($request, $response) {
        $break = false;

        $response->html($this->app->path('res', 'html/landing.php'), array(
            'app' => $this->app,
        ));

        return array($request, $response, $break);
    }
}