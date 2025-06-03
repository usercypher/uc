<?php

class Link_Cli_Link {
    private $app;

    public function __construct($args = array()) {
        list(
            $this->app,
        ) = $args;
    } 

    public function link($request, $response) {
        if (!isset($request->params['option']) || !isset($request->params['class'])) {
            $response->std('Error: Usage - php [file] pipe [option:eg. create] [class] --path=[value] --args=[value]' . EOL, true);
            return false;
        }

        $className = $request->params['class'];
        $classPath = (isset($request->cli['option']['path']) ? $request->cli['option']['path'] : '') . $className . '.php';
        $classDeps = empty($request->cli['option']['args']) ? array() : explode(',', $request->cli['option']['args']);

        $classContent = $this->classContent($className, $classDeps);

        switch ($request->params['option']) {
            case 'create':
                file_put_contents($this->app->path('src', $classPath) , $classContent);
                $response->std(EOL . $request->params['class'] . ' created successfully! in ' . $this->app->path('src', $classPath) . EOL);
                break;
            default:
                $response->std('Error: Usage - php [file] pipe [option:eg. create] [class] --path=[value] --args=[value]' . EOL, true);
                return false;
        }

        return true;
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

    public function link(\$request, \$response) {
        // code
        return true;
    }
}";
    }
}
