<?php

class Response {
    public $headers, $code, $contentType, $content;

    public function __construct() {
        $this->headers = array();
        $this->code = 200;
        $this->contentType = 'text/html';
        $this->content = '';
    }

    public function send() {
        header('HTTP/1.1 ' . $this->code);
        foreach ($this->headers as $key => $value) {
            header($key . ': ' . $value);
        }

        if (!isset($this->headers['Content-Type'])) {
            header('Content-Type: ' . $this->contentType);
        }

        echo($this->content);
    }
}