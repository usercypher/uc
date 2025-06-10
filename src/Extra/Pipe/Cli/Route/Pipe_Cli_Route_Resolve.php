<?php

class Pipe_Cli_Route_Resolve {
    private $app;

    public function args($args) {
        list(
            $this->app
        ) = $args;
    }

    public function pipe($request, $response) {
        $break = false;

        $unitList = isset($this->app->unitList) ? $this->app->unitList : array();

        $output = '';
        if (!isset($request->cli['option']['type']) || !isset($request->cli['option']['path'])) {
            $output .= 'Error: Missing required parameters.' . EOL;
            $output .= 'Usage: --type=GET|POST --path=/route/path' . EOL;
            $response->std($output, true);
            return array($request, $response, $break);
        }
        $result = $this->app->resolveRoute($request->cli['option']['type'], $request->cli['option']['path']);

        if (isset($result['error'])) {
            $output .= 'Route Error [http ' . $result['http'] . ']: ' . $result['error'] . EOL;
            return array($request, $response, $break);
        }
        $output .= 'RESOLVED ROUTE' . EOL;
        $output .= '  Method : ' . $request->cli['option']['type'] . EOL;
        $output .= '  Path   : ' . $request->cli['option']['path'] . EOL;

        $output .= '  Pipe   :' . EOL;
        foreach ($result['pipe'] as $i => $index) {
            $output .= '    #' . str_pad($i, 2, ' ', STR_PAD_LEFT) . '  ' . $unitList[$index] . EOL;
        }

        // Show dynamic params if any
        if (!empty($result['params'])) {
            $output .= '  Params :' . EOL;
            foreach ($result['params'] as $key => $value) {
                $output .= '    ' . str_pad($key, 12) . ' = ' . (is_array($value) ? print_r($value, true) : $value) . EOL;
            }
        }

        $response->std($output);

        return array($request, $response, $break);
    }
}
