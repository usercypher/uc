<?php

class Pipe_Cli_Route_Help {
    function process($input, $output) {
        $success = true;

        $message = '';
        $option = $input->getFrom($input->param, 'on-unknown-option');
        if ($option) {
            $message .= 'Error: Missing or unknown option \'' . $option[0] . '\'.'. PHP_EOL;
        }

        $message .= 'Usage: php [file] route [option]' . PHP_EOL;
        $message .= 'Options:' . PHP_EOL;
        $message .= '  print    Show all defined routes' . PHP_EOL;
        $message .= '  resolve  Simulate resolving a request using --method=[value] and --route=[value]' . PHP_EOL;
        $message .= '  run      run a request using --method=[value], --route=[value], --header=[value], --content=[value] and --query=[value]' . PHP_EOL;
        $output->content = $message;
        $output->code = 1;

        return array($input, $output, $success);
    }
}