<?php

class AuthMiddleware {
    public function process($request, $response, $next) {
        if (empty($_SESSION['user'])) {
            $response->headers['Location'] = App::url('route', '/');
            return $response;
        }

        return $next->process($request, $response, $next);
    }
}