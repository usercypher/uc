<?php

class Translator {
    var $translations = array();
    var $pluralRule = null;

    function setDefaultPluralRule() {
        $this->pluralRule = array(&$this, 'simplePlural');
    }

    function simplePlural($n) {
        return ($n == 0) ? 0 : (($n == 1) ? 1 : 2);
    }

    function t($key, $placeholders = array()) {
        return $this->translate($key, null, $placeholders);
    }

    function nt($key, $count, $placeholders = array()) {
        return $this->translate($key, $count, $placeholders);
    }

    function translate($key, $count = null, $placeholders = array()) {
        if (isset($this->translations[$key])) {
            $value = $this->translations[$key];
        } else {
            $value = $key;
        }

        $isArray = is_array($value);
        if ($isArray && $count !== null) {
            list($obj, $func) = $this->pluralRule;
            $form = $obj-> {
                $func
            } ($count);
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