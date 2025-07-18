<?php

class Lib_Html {
    // html escape
    function h($string) {
        return htmlspecialchars($string, ENT_QUOTES);
    }

    // html strip tags
    function s($string, $allowedTags = '') {
        return strip_tags($string, $allowedTags);
    }

    // url query encode
    function q($string) {
        return urlencode($string);
    }

    // url path segment encode
    function p($string) {
        return rawurlencode($string);
    }

    // json encode, for js
    function j($string) {
        return json_encode($string);
    }
}
