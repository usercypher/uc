<?php

class Shared_Pipe_OutputCompression {
    function process($input, $output) {
        $success = true;

        if (!empty($output->content) && isset($input->header['accept-encoding']) && is_string($input->header['accept-encoding']) && strpos($input->header['accept-encoding'], 'gzip') !== false && function_exists('gzencode')) {
            $output->content = gzencode($output->content, 1);
            $output->header['content-encoding'] = 'gzip';
        }

        return array($input, $output, $success);
    }
}
