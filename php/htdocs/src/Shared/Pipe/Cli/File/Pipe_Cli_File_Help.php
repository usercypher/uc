<?php

class Pipe_Cli_File_Help {
    function process($input, $output) {
        $success = true;

        $message = '';
        $option = $input->getFrom($input->param, 'on-unknown-option');
        if ($option) {
            $message .= 'Error: Missing or unknown option \'' . $option[0] . '\'.'. PHP_EOL;
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