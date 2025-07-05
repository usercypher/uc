<?php

define('CTX', "\x04");

class Translator {
    var $translations = array();
    var $pluralRule = null;

    function setDefaultPluralRule() {
        $this->pluralRule = array(&$this, 'simplePlural');
    }

    function simplePlural($n) {
        return ($n == 0) ? 0 : (($n == 1) ? 1 : 2);
    }

    function text($key, $placeholders = array(), $context = null) {
        return $this->translate($key, null, $placeholders, $context);
    }

    function ntext($key, $count, $placeholders = array(), $context = null) {
        return $this->translate($key, $count, $placeholders, $context);
    }

    function translate($key, $count = null, $placeholders = array(), $context = null) {
        $lookupKey = ($context !== null) ? $context . CTX . $key : $key;

        if (isset($this->translations[$lookupKey])) {
            $value = $this->translations[$lookupKey];
        } elseif (isset($this->translations[$key])) {
            $value = $this->translations[$key];
        } else {
            $value = $key;
        }

        $isArray = is_array($value);
        if ($isArray && $count !== null) {
            list($obj, $func) = $this->pluralRule;
            $form = $obj-> {$func} ($count);
            if (!isset($value[$form])) {
                end($value);
                $form = key($value);
            }
            $text = $value[$form];
        } elseif ($isArray) {
            $text = $value[1];
        } else {
            $text = $value;
        }

        return strtr($text, $placeholders);
    }
}