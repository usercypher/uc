<?php

class User_Pipe_IsNotAuth {
    var $app, $session;

    function args($args) {
        list(
            $this->app,
            $this->session,
        ) = $args;
    }

    function process($input, $output) {
        $success = true;

        $app = $this->app;
        $route = $input->data['user_is_not_auth_route'];

        $user = $this->session->get('user');
        if ($user) {
            $output->header['location'] = $app->url('ROUTE', $route);
            $success = false;
        }
        return array($input, $output, $success);
    }
}