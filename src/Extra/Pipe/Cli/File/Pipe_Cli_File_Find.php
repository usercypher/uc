<?php

class Pipe_Cli_File_Find {
    public function process($input, $output) {
        $success = true;
        $message = '';

        $directory = $input->getFrom($input->options, 'dir');

        // If dir is not provided, use current working directory
        if ($directory === null || $directory === '') {
            $directory = getcwd();
        }

        $search = $input->getFrom($input->options, 'search');

        if ($search === null) {
            $message .= 'Error: Missing required parameters.' . EOL;
            $message .= 'Usage: php [file] file find --search="searchString" [--dir="directoryPath"]' . EOL;
            $output->content = $message;
            $output->code = 1;
            $success = false;
            return array($input, $output, $success);
        }

        if (!is_dir($directory)) {
            $message .= "Error: Directory does not exist: $directory" . EOL;
            $output->content = $message;
            $output->code = 1;
            $success = false;
            return array($input, $output, $success);
        }

        $output->std("Scanning..." . EOL);
        $files = $this->getFilesRecursive($directory);
        $foundFiles = [];

        foreach ($files as $i => $file) {
            if ($this->fileContainsString($file, $search)) {
                $foundFiles[] = $file;
            }
        }

        $output->std("Done. " . count($files) . " files scanned." . EOL . EOL,);

        if ($foundFiles) {
            $message .= "Files containing '$search':" . EOL;
            foreach ($foundFiles as $file) {
                $message .= " - $file" . EOL;
            }
        } else {
            $message .= "No files containing '$search' found." . EOL;
        }

        $output->content = $message;
        return array($input, $output, $success);
    }

    private function getFilesRecursive($dir) {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
        return $files;
    }

    private function fileContainsString($filePath, $search) {
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
