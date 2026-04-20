<?php

class Cli_Pipe_Db_Print {
    var $app;

    function args($args) {
        list($this->app) = $args;
    }

    function process($input, $output) {
        $success = true;
        $message = '';

        $directory = $this->app->getEnv('DIR_ROOT');

        $files = $this->getFilesRecursive($directory);

        uasort($files, array($this, 'compareByBasenameCi'));

        $seen = array();

        for ($i = 0; $i < count($files); $i++) {
            $file = $files[$i];
            if (substr($file, -3) === '.db') {
                $message .= "\n" . file_get_contents($file) . "\n";
            }
        }

        $output->content = $message;
        return array($input, $output, $success);
    }

    function compareByBasenameCi($a, $b) {
        $an = basename($a);
        $bn = basename($b);
        return strcasecmp($an, $bn);
    }

    function getFilesRecursive($dir, &$files = array()) {
        $dh = @opendir($dir);
        if ($dh) {
            while (($file = readdir($dh)) !== false) {
                if ($file == '.' || $file == '..') {
                    continue;
                }

                $fullPath = $dir . '/' . $file;

                if (is_file($fullPath)) {
                    $files[] = $fullPath;
                } elseif (is_dir($fullPath)) {
                    $this->getFilesRecursive($fullPath, $files);
                }
            }
            closedir($dh);
        }
        return $files;
    }
}
