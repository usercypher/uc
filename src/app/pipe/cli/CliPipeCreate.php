<?php

class CliPipeCreate {
    private $app;

    public function __construct($args = array()) {
        list(
            $this->app,
        ) = $args;
    } 

    public function pipe($request, $response) {
        $response->type = 'text/plain';

        if (!isset($request->params['class']) || !isset($request->params['class_path'])) {
            $response->content = 'Error: Usage — php [file] pipe-create [class_path] [class] --class_args=[value]';
            $response->send();
        }

        $className = $request->params['class'];
        $classPath = $request->params['class_path'];
        $classDeps = isset($request->cli['option']['class_args']) ? explode(',', $request->cli['option']['class_args']) : array();

        $classContent = $this->classContent($className, $classDeps);

        file_put_contents($this->app->path('src', $classPath), $classContent);
        $response->content = EOL . $request->params['class'] . ' created successfully! in ' . $this->app->path('src', $classPath) . EOL;

        return array($request, $response);
    }

    private function classContent($className, $classDependency) {
        // Handling empty dependencies
        $classVar = empty($classDependency) ? '' : EOL . "    private $" . implode(";" . EOL . "    private $", $classDependency) . ";" . EOL;
        $classVarList = empty($classDependency) ? "//list() = \$args;" : "list(" . EOL . "            \$this->" . implode("," . EOL . "            \$this->", $classDependency) . "," . EOL . "        ) = \$args;";
        return "<?php

class $className {" . $classVar . "
    public function __construct(\$args = array()) {
        // add dependency-only class
        " . $classVarList . "
    }

    public function pipe(\$request, \$response) {
        // code
        return array(\$request, \$response);
    }
}";
    }
}
