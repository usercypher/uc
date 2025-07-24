<?php

class Pipe_Landing {
    private $app;

    public function args($args) {
        list(
            $this->app,
        ) = $args;
    }

    public function process($input, $output) {
        $success = true;

        $output->html($this->app->dirRoot('res/app/view/extra/landing.php'), array(
            'app' => $this->app,
        ));

        return array($input, $output, $success);
    }
}