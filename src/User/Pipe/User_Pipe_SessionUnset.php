<?php

class User_Pipe_SessionUnset {
    var $app, $session;

    function args($args) {
        list(
            $this->app,
            $this->session,
        ) = $args;
    }

    function process($input, $output) {
        $success = true;

        $this->session->unset('user');
        $route = isset($input->query['redirect']) ? $input->query['redirect'] : '';

        $output->header['location'] = $this->app->urlRoute($route);

        return array($input, $output, $success);
    }
}