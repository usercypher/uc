<?php

class Pipe_Cli_Route_Help {
    public function pipe($input, $output) {
        $break = false;

        $message = '';
        $option = $input->getFrom($input->params, 'onUnknownOption');
        if ($option) {
            $message .= 'Error: Missing or unknown option \'' . $option[0] . '\'.'. EOL;
        }

        $message .= 'Usage: php [file] route [option]' . EOL;
        $message .= 'Options:' . EOL;
        $message .= '  print    Show all defined routes' . EOL;
        $message .= '  resolve  Simulate resolving a request using --type=[value] and --path=[value]' . EOL;
        $output->content = $message;
        $output->code = 1;

        return array($input, $output, $break);
    }
}
