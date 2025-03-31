<?php

class SanitizeMiddleware {
    public function process($request, $response, $next) {
        $post = $request->post;

        if (isset($post)) {
            $request->post = $this->sanitizeArray($post);
        }

        $get = $request->get;

        if (isset($get)) {
            $request->get = $this->sanitizeArray($get);
        }

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

    private function sanitizeString($string) {
        $string = trim($string);

        $allowedTags = '<b><i><u><a>';
        $string = strip_tags($string, $allowedTags);
        
        return htmlspecialchars($string, ENT_QUOTES);
    }
}
