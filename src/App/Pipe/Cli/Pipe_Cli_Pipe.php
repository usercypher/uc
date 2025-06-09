<?php

class Pipe_Cli_Pipe {
    private $app;

    public function args($args) {
        list(
            $this->app,
        ) = $args;
    } 

    public function pipe($request, $response) {
        $break = false;

        $option = isset($request->params['option']) ? $request->params['option'] : null;

        switch ($option) {
            case 'create':
                list($request, $response) = $this->create($request, $response);
                break;
            default:
                $output = 'Error: Missing or unknown option \'' . $option . '\'.'. EOL;
                $output .= 'Usage: php [file] pipe [option]' . EOL;
                $output .= 'Options:' . EOL;
                $output .= '  create [name]   create pipe using --path=[value] --args=[value]' . EOL;
                $response->std($output, true);
        }

        return array($request, $response, $break);
    }
    private function create($request, $response) {
        $output = '';
        if (!isset($request->params['class'])) {
            $output .= 'Error: Missing required parameters.' . EOL;
            $output .= 'Usage: php [file] pipe create [name]' . EOL;
            $response->std($output, true);
            return array($request, $response);
        }

        $className = $request->params['class'];
        $classPath = (isset($request->cli['option']['path']) ? $request->cli['option']['path'] : '') . $className . '.php';
        $classDeps = empty($request->cli['option']['args']) ? array() : explode(',', $request->cli['option']['args']);

        $classContent = $this->classContent($className, $classDeps);

        $fullPath = $this->app->path('src', $classPath);
        if (file_put_contents($fullPath , $classContent) !== false) {
            $output .= EOL . $className . ' created successfully!' . EOL;
            $output .= 'Location: ' . $fullPath . EOL;
        } else {
            $output = 'Error: Failed to write file at ' . $fullPath . EOL;
        }

        $response->std($output);

        return array($request, $response);
    }

    private function classContent($className, $classDependency) {
        // Handling empty dependencies
        $classVar = empty($classDependency) ? '' : EOL . "    private $" . implode(";" . EOL . "    private $", $classDependency) . ";" . EOL;
        $classVarList = empty($classDependency) ? "//list() = \$args;" : "list(" . EOL . "            \$this->" . implode("," . EOL . "            \$this->", $classDependency) . "," . EOL . "        ) = \$args;";
        return "<?php

class $className {" . $classVar . "
    public function args(\$args) {
        // add dependency-only class
        " . $classVarList . "
    }

    public function pipe(\$request, \$response) {
        \$break = false;
        // code
        return array(\$request, \$response, \$break);
    }
}";
    }
}
