<?php

class Cli_Pipe_Sql_Help {
    function process($input, $output) {
        $success = true;

        $message = '';
        $option = isset($input->param['on-unknown-option']) ? array_map('rawurldecode', explode('/', $input->param['on-unknown-option'])) : null;
        if ($option) {
            $message .= 'Error: Missing or unknown option \'' . $option[0] . '\'.' . PHP_EOL;
        }

        $message .= 'Usage: php [file] sql [option]' . PHP_EOL;
        $message .= 'Options:' . PHP_EOL;
        $message .= '  print       aggregate sql files' . PHP_EOL;
        $output->content = $message;
        $output->code = 1;

        return array($input, $output, $success);
    }
}
