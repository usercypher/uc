<?php

class Shared_Lib_Translator {
    var $objects;

    function set($key, $translations = array(), $plural = null) {
        $object = new Shared_Lib_Translator_Object;
        $object->translations = $translations;
        if ($plural) {
            $object->plural = $plural;
        } else {
            $object->setDefaultPlural();
        }
        $this->objects[$key] = $object;
    }

    function get($key) {
        return $this->objects[$key];
    }
}

class Shared_Lib_Translator_Object {
    var $translations = array();
    var $plural = null;

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
        if (isset($this->translations[$key])) {
            $value = $this->translations[$key];
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
