<?php

class SessionMiddleware {
    public function process($request, $response, $next) {
        if (version_compare(phpversion(),'5.4.0','>')){
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
        } else{
            if(session_id() == '') {
                session_start();
            }
        }

        return $next->process($request, $response, $next);
    }
}