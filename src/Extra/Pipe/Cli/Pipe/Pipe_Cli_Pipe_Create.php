<?php

class Pipe_Cli_Pipe_Create {
    private $app;

    public function args($args) {
        list(
            $this->app,
        ) = $args;
    } 

    public function pipe($request, $response) {
        $break = false;

        $output = '';
        if (!isset($request->params['class'])) {
            $output .= 'Error: Missing required parameters.' . EOL;
            $output .= 'Usage: php [file] pipe create [name]' . EOL;
            $response->std($output, true);
            return array($request, $response, $break);
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

        return array($request, $response, $break);
    }

    private function classContent($className, $classDependency) {
        // Handling empty dependencies
        $classVar = empty($classDependency) ? '' : EOL . "    private $" . implode(";" . EOL . "    private $", $classDependency) . ";" . EOL;
        $classVarList = empty($classDependency) ? "//list() = \$args;" : "list(" . EOL . "            \$this->" . implode("," . EOL . "            \$this->", $classDependency) . "," . EOL . "        ) = \$args;";
        $functionArgs = empty($classDependency) ? "" : "
    public function args(\$args) {
        // add dependency-only class
        " . $classVarList . "
    }
";

        return "<?php

class $className {" . $classVar . $functionArgs . "
    public function pipe(\$request, \$response) {
        \$break = false;
        // code
        return array(\$request, \$response, \$break);
    }
}";
    }
}
