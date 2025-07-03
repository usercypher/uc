<?php

class Pipe_Cli_Pipe_Help {
    public function pipe($input, $output) {
        $break = false;

        $option = $input->getFrom($input->params, 'onUnknownOption');
        if ($option) {
            $message = 'Error: Missing or unknown option \'' . $option[0] . '\'.'. EOL;
        }

        $message .= 'Usage: php [file] pipe [option]' . EOL;
        $message .= 'Options:' . EOL;
        $message .= '  create [name]   create pipe using --path=[value] --args=[value]' . EOL;
        $output->content = $message;
        $output->code = 1;

        return array($input, $output, $break);
    }
}
