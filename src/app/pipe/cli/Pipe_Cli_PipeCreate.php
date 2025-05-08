<?php

class Pipe_Cli_PipeCreate {
    private $app;

    public function __construct($args = array()) {
        list(
            $this->app,
        ) = $args;
    } 

    public function pipe($request, $response) {
        if (!isset($request->params['class']) || !isset($request->params['class_path'])) {
            $response->plain('Error: Usage - php [file] pipe-create [class_path] [class] --class_args=[value]' . EOL);
            $response->send();
        }

        $className = $request->params['class'];
        $classPath = $request->params['class_path'] . $className . '.php';
        $classDeps = empty($request->cli['option']['class_args']) ? array() : explode(',', $request->cli['option']['class_args']);

        $classContent = $this->classContent($className, $classDeps);

        file_put_contents($this->app->path('src', $classPath) , $classContent);

        return array($request, $response->plain(EOL . $request->params['class'] . ' created successfully! in ' . $this->app->path('src', $classPath) . EOL));
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
