<?php

class ResponseCompression {
    public function process($request, $response, $next) {
        $response = $next->process($request, $response, $next);

        if (isset($request->server['HTTP_ACCEPT_ENCODING']) && strpos($request->server['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
            $response->content = gzencode($response->content, 1);
            $response->headers['Content-Encoding'] = 'gzip';
        }

        return $response;
    }
}
