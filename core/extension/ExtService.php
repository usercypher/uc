<?php

class ExtService {
    protected $errors = array();

    public function getErrors() {
        return $this->errors;
    }

    public function addError($field, $message) {
        $this->errors[$field] = $message;
    }
}