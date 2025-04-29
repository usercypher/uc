<?php

class Sanitize {
    public function __construct($args) {}

    public function pipe($request, $response) {
        if (isset($request->post)) {
            $this->sanitize($request->post);
        }

        if (isset($request->get)) {
            $this->sanitize($request->get);
        }

        if (isset($request->params)) {
            $this->sanitize($request->params);
        }

        return array($request, $response);
    }

    public function sanitize(&$array) {
        $stack = array();
        $stack[] = array(&$array);

        while (!empty($stack)) {
            $current = array_pop($stack);
            $currentArray = &$current[0];

            foreach ($currentArray as $key => $value) {
                if (is_array($currentArray[$key])) {
                    $stack[] = array(&$currentArray[$key]);
                } elseif (is_string($currentArray[$key])) {
                    $currentArray[$key] = trim($currentArray[$key]);
                    $currentArray[$key] = strip_tags($currentArray[$key], '<b><i><u><a>');
                    $currentArray[$key] = htmlspecialchars($currentArray[$key], ENT_QUOTES);
                }
            }
        }
    }
}
