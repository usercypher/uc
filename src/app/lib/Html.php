<?php

class Html {
    public function __construct($args = array()) {}

    public function encode(&$array, $stripTags = false, $allowedTags = '') {
        $this->htmlProcessor($array, true, $stripTags, $allowedTags);
    }

    public function decode(&$array, $stripTags = false, $allowedTags = '') {
        $this->htmlProcessor($array, false, $stripTags, $allowedTags);
    }

    public function htmlProcessor(&$array, $encode, $stripTags, $allowedTags) {
        $stack = array();
        $stack[] = array(&$array);

        while (!empty($stack)) {
            $current = array_pop($stack);
            $currentArray = &$current[0];

            foreach ($currentArray as $key => $value) {
                if (is_array($currentArray[$key])) {
                    $stack[] = array(&$currentArray[$key]);
                } elseif (is_string($currentArray[$key])) {
                    if ($encode) {
                        $currentArray[$key] = trim($currentArray[$key]);
                        $currentArray[$key] = ($stripTags) ? strip_tags($currentArray[$key], $allowedTags) : $currentArray[$key];
                        $currentArray[$key] = htmlspecialchars($currentArray[$key], ENT_QUOTES);
                    } else {
                        $currentArray[$key] = html_entity_decode($currentArray[$key]);
                        $currentArray[$key] = ($stripTags) ? strip_tags($currentArray[$key], $allowedTags) : $currentArray[$key];
                    }
                }
            }
        }
    }
}
