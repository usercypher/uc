<?php

class SanitizeMiddleware {
    public function process($request, $response, $next) {
        // Sanitize $_POST
        $post = $request->post;

        if (isset($post)) {
            $request->post = $this->sanitizeArray($post);
        }

        // Sanitize $_GET
        $get = $request->get;

        if (isset($get)) {
            $request->get = $this->sanitizeArray($get);
        }

        return $next->process($request, $response, $next);
    }

    private function sanitizeArray($array) {
        foreach ($array as $key => $value) {
            if (is_string($value)) {
                $array[$key] = $this->sanitizeString($value);
            } elseif (is_array($value)) {
                $array[$key] = $this->sanitizeArray($value);
            }
        }
        return $array;
    }

    private function sanitizeString($string) {
        return trim(htmlspecialchars(strip_tags($string)));
    }
}