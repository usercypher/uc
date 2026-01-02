<?php

class Pipe_Cli_File_Find {
    function process($input, $output) {
        $success = true;
        $message = '';

        $directory = $input->getFrom($input->query, 'dir');

        // If dir is not provided, use current working directory
        if (empty($directory)) {
            $directory = getcwd();
        }

        $search = $input->getFrom($input->query, 'search');

        if (empty($search)) {
            $message .= 'Error: Missing required parameters.' . "\n";
            $message .= 'Usage: php [file] file find --search="searchString" [--dir="directoryPath"]' . "\n";
            $output->content = $message;
            $output->code = 1;
            $success = false;
            return array($input, $output, $success);
        }

        if (!is_dir($directory)) {
            $message .= "Error: Directory does not exist: $directory" . "\n";
            $output->content = $message;
            $output->code = 1;
            $success = false;
            return array($input, $output, $success);
        }

        $output->std("Scanning..." . "\n");
        $files = $this->getFilesRecursive($directory);
        $foundFiles = array();

        for ($i = 0; $i < count($files); $i++) {
            $file = $files[$i];
            if ($this->fileContainsString($file, $search)) {
                $foundFiles[] = $file;
            }
        }

        $output->std("Done. " . count($files) . " files scanned. " . (count($foundFiles) ? count($foundFiles) : 0) . " found." . "\n\n");

        if (count($foundFiles)) {
            $message .= "Files containing '$search':" . "\n";
            for ($i = 0; $i < count($foundFiles); $i++) {
                $message .= " - " . $foundFiles[$i] . "\n";
            }
        } else {
            $message .= "No files containing '$search' found." . "\n";
        }

        $output->content = $message;
        return array($input, $output, $success);
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

    function fileContainsString($filePath, $search) {
        $handle = @fopen($filePath, 'r');
        if (!$handle) {
            return false;
        }

        while (($line = fgets($handle)) !== false) {
            if (strpos($line, $search) !== false) {
                fclose($handle);
                return true;
            }
        }
        fclose($handle);
        return false;
    }
}
