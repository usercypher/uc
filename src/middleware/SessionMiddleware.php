<?php

class SessionMiddleware {
    public function process($request, $response, $next) {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $next->process($request, $response, $next);
    }
}