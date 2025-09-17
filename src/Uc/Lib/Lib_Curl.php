<?php

class Lib_Curl {
    var $result = null, $headersRaw = array();

    function send($url, $options = array()) {
        $this->result = new stdClass();
        $this->headersRaw = array();
        $ch = curl_init();

        $method = isset($options['method']) ? strtoupper($options['method']) : 'GET';
        $headers = isset($options['headers']) ? $options['headers'] : array();
        $timeout = isset($options['timeout']) ? (int)$options['timeout'] : 30;
        $content = isset($options['content']) ? $options['content'] : '';

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($content !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        }

        $verify_ssl = isset($options['verify_ssl']) ? $options['verify_ssl'] : true;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify_ssl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verify_ssl ? 2 : 0);

        if (isset($options['ca_info'])) {
            curl_setopt($ch, CURLOPT_CAINFO, $options['ca_info']);
        }

        if (!empty($headers)) {
            $parsedHeaders = array();
            foreach ($headers as $key => $value) $parsedHeaders[] = $key . ':' . $value;
            curl_setopt($ch, CURLOPT_HTTPHEADER, $parsedHeaders);
        }

        $that =& $this;

        curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($that, 'header'));

        $this->result->headers = array();
        $this->result->content = curl_exec($ch);
        $this->result->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->result->type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $this->result->error = curl_error($ch);
        $this->result->info = curl_getinfo($ch);

        curl_close($ch);

        return $this->result;
    }

    function header($ch, $headerLine) {
        $trimmed = trim($headerLine);

        if ($trimmed === '') {
            $this->result->headers = $this->parseHeaders($this->headersRaw);
            $this->headersRaw = array();
        } else {
            $this->headersRaw[] = $headerLine;
        }

        return strlen($headerLine);
    }

    function parseHeaders($headerLines) {
        $headers = array();
        foreach ($headerLines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $key = strtolower(trim($key));
                $value = trim($value);
                if (!isset($headers[$key])) {
                    $headers[$key] = $value;
                } else {
                    if (is_array($headers[$key])) {
                        $headers[$key][] = $value;
                    } else {
                        $headers[$key] = array($headers[$key], $value);
                    }
                }
            }
        }
        return $headers;
    }
}