<?php

class Pipe_ResponseCompression {
    public function pipe($request, $response) {
        $break = false;

        if (!empty($response->content) && isset($request->server['HTTP_ACCEPT_ENCODING']) && is_string($request->server['HTTP_ACCEPT_ENCODING']) && strpos($request->server['HTTP_ACCEPT_ENCODING'], 'gzip') !== false && function_exists('gzencode')) {
            $response->content = gzencode($response->content, 1);
            $response->headers['Content-Encoding'] = 'gzip';
        }

        return array($request, $response, $break);
    }
}
