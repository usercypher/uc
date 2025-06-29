<?php

class Pipe_Cli_Route_Help {
    public function pipe($input, $output) {
        $break = false;

        $option = $input->getFrom($input->params, 'onUnknownOption', array(''));

        $message = 'Error: Missing or unknown option \'' . $option[0] . '\'.'. EOL;
        $message .= 'Usage: php [file] route [option]' . EOL;
        $message .= 'Options:' . EOL;
        $message .= '  print    Show all defined routes' . EOL;
        $message .= '  resolve  Simulate resolving a request using --type and --path' . EOL;
        $output->content = $message;
        $output->stderr = true;

        return array($input, $output, $break);
    }
}
