<?php

class Pipe_Cli_File_Help {
    public function pipe($input, $output) {
        $break = false;

        $option = $input->getFrom($input->params, 'onUnknownOption');
        if ($option) {
            $message = 'Error: Missing or unknown option \'' . $option[0] . '\'.'. EOL;
        }

        $message .= 'Usage: php [file] file [option]' . EOL;
        $message .= 'Options:' . EOL;
        $message .= '  find          find using --search=[value] and --dir=[value]' . EOL;
        $message .= '  find-replace  find and replace using --search=[value] and --replace=[value] and --dir=[value]' . EOL;
        $output->content = $message;
        $output->code = 1;

        return array($input, $output, $break);
    }
}
