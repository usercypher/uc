<?php

class Lib_Curl {
    var $result = null;

    function send($url, $options = array()) {
        $ch = curl_init();

        $method  = isset($options['method']) ? strtoupper($options['method']) : 'GET';
        $headers = isset($options['headers']) ? $options['headers'] : array();
        $timeout = isset($options['timeout']) ? (int)$options['timeout'] : 30;
        $content = isset($options['content']) ? $options['content'] : '';

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($content !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        }

        if (!empty($headers)) {
            $parsedHeaders = array();
            foreach ($headers as $key => $value) $parsedHeaders[] = $key . ':' . $value;
            curl_setopt($ch, CURLOPT_HTTPHEADER, $parsedHeaders);
        }

        curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, 'header'));

        $this->result = new stdClass();
        $this->result->headers = array();
        $this->result->content = curl_exec($ch);
        $this->result->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->result->type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $this->result->error = curl_error($ch);
        $this->result->info = curl_getinfo($ch);

        curl_close($ch);

        return $this->result;
    }

    function header($ch, $header) {
        if (strpos($header, ':') !== false) {
            list($key, $value) = explode(':', $header, 2);
            $this->result->headers[strtolower(trim($key))] = trim($value);
        }
        return strlen($header);
    }
}