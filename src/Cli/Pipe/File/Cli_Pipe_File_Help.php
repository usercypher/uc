<?php

class Cli_Pipe_File_Help {
    function process($input, $output) {
        $success = true;

        $message = '';
        $option = isset($input->param['on-unknown-option']) ? array_map('rawurldecode', explode('/', $input->param['on-unknown-option'])) : null;
        if ($option) {
            $message .= 'Error: Missing or unknown option \'' . $option[0] . '\'.' . PHP_EOL;
        }

        $message .= 'Usage: php [file] file [option]' . PHP_EOL;
        $message .= 'Options:' . PHP_EOL;
        $message .= '  find          find using --search=[value] and --dir=[value]' . PHP_EOL;
        $message .= '  find-replace  find and replace using --search=[value] and --replace=[value] and --dir=[value]' . PHP_EOL;
        $output->content = $message;
        $output->code = 1;

        return array($input, $output, $success);
    }
}
