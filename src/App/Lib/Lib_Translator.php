<?php

define('CTX', "\x04");

class Translator {
    var $translations = array();
    var $pluralRule = null;

    function setDefaultPluralRule() {
        $this->pluralRule = array(&$this, 'simplePlural');
    }

    function setPluralRule($callback) {
        $this->pluralRule = $callback;
    }

    function simplePlural($n) {
        return ($n == 0) ? 0 : (($n == 1) ? 1 : 2);
    }

    function t($key, $count = null, $placeholders = array(), $context = null) {
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

        if (is_array($value) && $count !== null) {
            list($obj, $func) = $this->pluralRule;
            $form = $obj-> {$func} ($count);
            if (!isset($value[$form])) {
                end($value);
                $form = key($value);
            }
            $text = str_replace('{count}', $count, $value[$form]);
        } else {
            $text = $value;
        }

        foreach ($placeholders as $ph => $val) {
            $text = str_replace('{' . $ph . '}', $val, $text);
        }

        return $text;
    }
}