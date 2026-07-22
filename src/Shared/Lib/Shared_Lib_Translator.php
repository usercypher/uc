<?php

class Shared_Lib_Translator {
    var $translations = array();
    var $key = '';
    var $plural = null;

    function load($file) {
        if (isset($this->translations[$file])) {
            $this->key = $file;
            return;
        }
        if (!is_array($output = require $file)) {
            user_error('Translation file must return an array', E_USER_WARNING);
            return;
        }
        $this->key = $file;
        $this->translations[$file] = $output;
    }

    function setDefaultPlural() {
        $this->plural = &$this;
    }

    function rule($n) {
        return $n == 0 ? 0 : ($n == 1 ? 1 : 2);
    }

    function t($key, $placeholders = array()) {
        return $this->translate($key, null, $placeholders);
    }

    function nt($key, $count, $placeholders = array()) {
        return $this->translate($key, $count, $placeholders);
    }

    function translate($key, $count = null, $placeholders = array()) {
        if (isset($this->translations[$this->key][$key])) {
            $value = $this->translations[$this->key][$key];
        } else {
            $value = $key;
        }

        $isArray = is_array($value);
        if ($isArray && $count !== null) {
            $form = $this->plural->rule($count);
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
