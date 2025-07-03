<?php

class Pipe_Cli_File_FindReplace {
    public function pipe($input, $output) {
        $break = false;
        $message = '';

        $directory = $input->getFrom($input->options, 'dir');

        // If dir is not provided, use current working directory
        if ($directory === null || $directory === '') {
            $directory = getcwd();
        }

        $search = $input->getFrom($input->options, 'search');
        $replace = $input->getFrom($input->options, 'replace');

        if ($search === null || $replace === null) {
            $message .= 'Error: Missing required parameters.' . EOL;
            $message .= 'Usage: php [file] file find-replace --search="searchString" --replace="replaceString" --dir="directoryPath"' . EOL;
            $output->content = $message;
            $output->code = 1;
            $break = true;
            return array($input, $output, $break);
        }

        if (!is_dir($directory)) {
            $message .= "Error: Directory does not exist: $directory" . EOL;
            $output->content = $message;
            $output->code = 1;
            $break = true;
            return array($input, $output, $break);
        }

        $files = $this->getFilesRecursive($directory);
        $updatedFiles = [];

        foreach ($files as $file) {
            if ($this->replaceStringInFile($file, $search, $replace)) {
                $updatedFiles[] = $file;
            }
        }

        if ($updatedFiles) {
            $message .= "Updated files:" . EOL;
            foreach ($updatedFiles as $file) {
                $message .= " - $file" . EOL;
            }
        } else {
            $message .= "No files updated." . EOL;
        }

        $output->content = $message;
        return array($input, $output, $break);
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

    private function replaceStringInFile($filePath, $search, $replace) {
        $tempFile = tempnam(sys_get_temp_dir(), 'rep');

        $readHandle = @fopen($filePath, 'r');
        if (!$readHandle) {
            return false;
        }

        $writeHandle = @fopen($tempFile, 'w');
        if (!$writeHandle) {
            fclose($readHandle);
            return false;
        }

        $found = false;

        while (($line = fgets($readHandle)) !== false) {
            if (!$found && strpos($line, $search) !== false) {
                $found = true;
            }
            $line = str_replace($search, $replace, $line);
            fwrite($writeHandle, $line);
        }

        fclose($readHandle);
        fclose($writeHandle);

        if (!$found) {
            unlink($tempFile);
            return false;
        }

        if (!rename($tempFile, $filePath)) {
            unlink($tempFile);
            return false;
        }

        return true;
    }

}
