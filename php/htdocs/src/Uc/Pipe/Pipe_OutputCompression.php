<?php

class Pipe_OutputCompression {
    function process($input, $output) {
        $success = true;

        if (!empty($output->content) && isset($input->headers['accept-encoding']) && is_string($input->headers['accept-encoding']) && strpos($input->headers['accept-encoding'], 'gzip') !== false && function_exists('gzencode')) {
            $output->content = gzencode($output->content, 1);
            $output->headers['content-encoding'] = 'gzip';
        }

        return array($input, $output, $success);
    }
}