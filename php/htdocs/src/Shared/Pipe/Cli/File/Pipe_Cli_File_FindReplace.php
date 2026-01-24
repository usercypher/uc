<?php

class Pipe_Cli_File_FindReplace {
    function process($input, $output) {
        $success = true;
        $message = '';

        if (empty($input->query['search']) || empty($input->query['replace'])) {
            $message .= 'Error: Missing required parameters.' . "\n";
            $message .= 'Usage: php [file] file find-replace --search="searchString" --replace="replaceString" --dir="directoryPath"' . "\n";
            $output->content = $message;
            $output->code = 1;
            $success = false;
            return array($input, $output, $success);
        }

        $search = $input->query['search'];
        $replace = $input->query['replace'];

        $directory = !empty($input->query['dir']) ? $input->query['dir'] : getcwd();

        if (!is_dir($directory)) {
            $message .= "Error: Directory does not exist: $directory" . "\n";
            $output->content = $message;
            $output->code = 1;
            $success = false;
            return array($input, $output, $success);
        }

        $files = $this->getFilesRecursive($directory);
        $updatedFiles = array();

        for ($i = 0; $i < count($files); $i++) {
            $file = $files[$i];
            if ($this->replaceStringInFile($file, $search, $replace)) {
                $updatedFiles[] = $file;
            }
        }

        $message .= 'Done. ' . count($files) . ' files scanned. ' . (count($updatedFiles) ? count($updatedFiles) : 0) . ' updated.' . "\n\n";

        if (count($updatedFiles)) {
            $message .= 'Updated files:' . "\n";
            for ($i = 0; $i < count($updatedFiles); $i++) {
                $message .= ' - ' . $updatedFiles[$i] . "\n";
            }
        } else {
            $message .= 'No files updated.' . "\n";
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

    function replaceStringInFile($filePath, $search, $replace) {
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
