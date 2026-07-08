<?php

class User_Pipe_Cli_Help {
    function process($input, $output) {
        $success = true;

        $message = '';
        $option = isset($input->param['on-unknown-option']) ? array_map('rawurldecode', explode('/', $input->param['on-unknown-option'])) : null;
        if ($option) {
            $message .= 'Error: Missing or unknown option \'' . $option[0] . '\'.' . PHP_EOL;
        }

        $message .= 'Usage: php [file] user [option]' . PHP_EOL;
        $message .= 'Options:' . PHP_EOL;
        $message .= '  create       create user using --username=[value] --password=[value]' . PHP_EOL;
        $output->content = $message;
        $output->code = 1;

        return array($input, $output, $success);
    }
}
