<?php

class Pipe_Cli_Route_Help {
    public function pipe($request, $response) {
        $break = false;

        $output = 'Error: Missing or unknown option \'' . (isset($request->params['onUnknownOption'][0]) ? $request->params['onUnknownOption'][0] : '') . '\'.'. EOL;
        $output .= 'Usage: php [file] route [option]' . EOL;
        $output .= 'Options:' . EOL;
        $output .= '  print    Show all defined routes' . EOL;
        $output .= '  resolve  Simulate resolving a request using --type and --path' . EOL;
        $response->std($output, true);

        return array($request, $response, $break);
    }
}
