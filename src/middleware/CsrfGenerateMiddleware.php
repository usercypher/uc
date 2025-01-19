<?php

class CsrfGenerateMiddleware {
    public function process($request, $response, $next) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        return $next->process($request, $response, $next);
    }
}