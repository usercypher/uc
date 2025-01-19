<?php

class CsrfValidateMiddleware {
    public function process($request, $response, $next) {
        // Validate CSRF Token
        if (!isset($request->post['_token'])) {
            throw new Exception('Invalid CSRF token', 403);
        }

        if (!isset($_SESSION['csrf_token'])) {
            throw new Exception('Invalid CSRF token', 403);
        }

        if ($request->post['_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid CSRF token', 403);
        }

        return $next->process($request, $response, $next);
    }
}