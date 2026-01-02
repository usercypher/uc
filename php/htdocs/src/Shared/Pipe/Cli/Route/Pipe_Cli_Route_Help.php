<?php

class Pipe_Cli_Route_Help {
    function process($input, $output) {
        $success = true;

        $message = '';
        $option = $input->getFrom($input->param, 'on-unknown-option');
        if ($option) {
            $message .= 'Error: Missing or unknown option \'' . $option[0] . '\'.'. EOL;
        }

        $message .= 'Usage: php [file] route [option]' . EOL;
        $message .= 'Options:' . EOL;
        $message .= '  print    Show all defined routes' . EOL;
        $message .= '  resolve  Simulate resolving a request using --method=[value] and --route=[value]' . EOL;
        $message .= '  run      run a request using --method=[value], --route=[value], --header=[value], --content=[value] and --query=[value]' . EOL;
        $output->content = $message;
        $output->code = 1;

        return array($input, $output, $success);
    }
}