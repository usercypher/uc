<?php

class Pipe_Landing {
    private $app;

    public function args($args) {
        list(
            $this->app,
        ) = $args;
    }

    public function pipe($input, $output) {
        $break = false;

        $output->html($this->app->path('res', 'html/landing.php'), array(
            'app' => $this->app,
        ));

        return array($input, $output, $break);
    }
}