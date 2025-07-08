<?php

class Pipe_Sanitize {
    public function process($input, $output) {
        $success = true;

        if (isset($input->parsed)) {
            $this->sanitize($input->parsed);
        }

        if (isset($input->query)) {
            $this->sanitize($input->query);
        }

        if (isset($input->params)) {
            $this->sanitize($input->params);
        }

        return array($input, $output, $success);
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
