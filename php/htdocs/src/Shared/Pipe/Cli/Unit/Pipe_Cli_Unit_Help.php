<?php

class Pipe_Cli_Unit_Help {
    function process($input, $output) {
        $success = true;

        $message = '';
        $option = isset($input->param['on-unknown-option']) ? $input->param['on-unknown-option'] : null;
        if ($option) {
            $message .= 'Error: Missing or unknown option \'' . $option[0] . '\'.' . PHP_EOL;
        }

        $message .= 'Usage: php [file] unit [option]' . PHP_EOL;
        $message .= 'Options:' . PHP_EOL;
        $message .= '  create [name]   create pipe using --pipe, --path=[value], and --args=[value]' . PHP_EOL;
        $output->content = $message;
        $output->code = 1;

        return array($input, $output, $success);
    }
}
