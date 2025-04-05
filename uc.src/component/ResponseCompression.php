<?php

class ResponseCompression {
    public function __construct($args) {}

    public function process($request, $response) {
        if (isset($request->server['HTTP_ACCEPT_ENCODING']) && strpos($request->server['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
            $response->content = gzencode($response->content, 1);
            $response->headers['Content-Encoding'] = 'gzip';
        }

        return array($request, $response);
    }
}
