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

        // Sanitize dynamic route params
        $params = $request->params;

        if (isset($params)) {
            $request->params = $this->sanitizeArray($params);
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

    // Proper sanitization for string input
    private function sanitizeString($string) {
        // Trim whitespace and remove any tags
        $string = trim($string);

        // Here you can use a more sophisticated method to allow specific tags
        // e.g., allow <b>, <i>, <a> tags for rich-text formatting
        $allowedTags = '<b><i><u><a>';
        $string = strip_tags($string, $allowedTags);
        
        // Escape HTML entities to prevent XSS
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
