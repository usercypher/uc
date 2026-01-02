<?php

class Pipe_Cli_Unit_Help {
    function process($input, $output) {
        $success = true;

        $message = '';
        $option = $input->getFrom($input->param, 'on-unknown-option');
        if ($option) {
            $message .= 'Error: Missing or unknown option \'' . $option[0] . '\'.'. EOL;
        }

        $message .= 'Usage: php [file] unit [option]' . EOL;
        $message .= 'Options:' . EOL;
        $message .= '  create [name]   create pipe using --pipe, --path=[value], and --args=[value]' . EOL;
        $output->content = $message;
        $output->code = 1;

        return array($input, $output, $success);
    }
}