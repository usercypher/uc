<?php

class Pipe_Cli_Pipe_Help {
    public function pipe($request, $response) {
        $break = false;

        $output = 'Error: Missing or unknown option \'' . (isset($request->params['onUnknownOption'][0]) ? $request->params['onUnknownOption'][0] : '') . '\'.'. EOL;
        $output .= 'Usage: php [file] pipe [option]' . EOL;
        $output .= 'Options:' . EOL;
        $output .= '  create [name]   create pipe using --path=[value] --args=[value]' . EOL;
        $response->std($output, true);

        return array($request, $response, $break);
    }
}
