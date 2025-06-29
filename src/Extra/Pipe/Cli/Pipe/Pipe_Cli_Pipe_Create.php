<?php

class Pipe_Cli_Pipe_Create {
    private $app;

    public function args($args) {
        list(
            $this->app,
        ) = $args;
    } 

    public function pipe($input, $output) {
        $break = false;

        $message = '';

        $className = $input->getFrom($input->params, 'class');

        if (!$className) {
            $message .= 'Error: Missing required parameters.' . EOL;
            $message .= 'Usage: php [file] pipe create [name]' . EOL;
            $output->content = $message;
            $output->stderr = true;
            $break = true;
            return array($input, $output, $break);
        }

        $classPath = $input->getFrom($input->options, 'class', '') . $className . '.php';
        $tempDeps = $input->getFrom($input->options, 'args');
        $classDeps = $tempDeps ? explode(',', $tempDeps) : array();

        $classContent = $this->classContent($className, $classDeps);

        $fullPath = $this->app->path('src', $classPath);
        if (file_put_contents($fullPath , $classContent) !== false) {
            $message .= EOL . $className . ' created successfully!' . EOL;
            $message .= 'Location: ' . $fullPath . EOL;
        } else {
            $message = 'Error: Failed to write file at ' . $fullPath . EOL;
            $output->stderr = true;
        }

        $output->content = $message;

        return array($input, $output, $break);
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
    public function pipe(\$input, \$output) {
        \$break = false;
        // code
        return array(\$input, \$output, \$break);
    }
}";
    }
}
