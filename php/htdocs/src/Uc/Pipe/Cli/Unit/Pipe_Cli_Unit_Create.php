<?php

class Pipe_Cli_Unit_Create {
    var $app;

    function args($args) {
        list(
            $this->app,
        ) = $args;
    }

    function process($input, $output) {
        $success = true;
        $message = '';

        $className = $input->getFrom($input->params, 'name');

        if (!$className) {
            $message .= 'Error: Missing required parameters.' . "\n";
            $message .= 'Usage: php [file] unit create [name]' . "\n";
            $output->content = $message;
            $output->code = 1;
            $success = false;
            return array($input, $output, $success);
        }

        $classPath = $input->getFrom($input->options, 'path', '') . $className . '.php';
        $tempDeps = $input->getFrom($input->options, 'args');
        $classDeps = $tempDeps ? explode(',', $tempDeps) : array();

        $classContent = $this->classContent($className, $classDeps, $input->getFrom($input->flags, 'pipe'));

        $fullPath = $this->app->dirRoot('' . $classPath);
        $this->app->write($fullPath, $classContent);

        $message .= "\n" . $className . ' created successfully!' . "\n";
        $message .= 'Location: ' . $fullPath . "\n";

        $output->content = $message;

        return array($input, $output, $success);
    }

    function classContent($className, $classDependency, $isPipe) {
        $classVar = '';
        if (!empty($classDependency)) {
            $classVar = "\n";
            for ($i = 0; $i < count($classDependency); $i++) {
                $classVar .= "    var $" . $classDependency[$i] . ";\n";
            }
        }

        $classVarList = '';
        $functionArgs = '';
        if (!empty($classDependency)) {
            $classVarList = "list(\n";
            for ($i = 0; $i < count($classDependency); $i++) {
                $classVarList .= "            \$this->" . $classDependency[$i];
                if ($i < count($classDependency) - 1) {
                    $classVarList .= ",\n";
                } else {
                    $classVarList .= "\n";
                }
            }
            $classVarList .= "        ) = \$args;";

            $functionArgs = "\n    function args(\$args) {\n        " . $classVarList . "\n    }\n";
        }

        $functionProcess = $isPipe ? "\n    function process(\$input, \$output) {\n        \$success = true;\n        // code\n        return array(\$input, \$output, \$success);\n    }\n" : "";

        return "<?php\n\nclass $className {" . $classVar . $functionArgs . $functionProcess . "}";
    }
}
