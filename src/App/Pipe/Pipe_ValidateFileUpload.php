<?php

class Pipe_ValidateFileUpload {
    public function __construct($args = array()) {}

    private $allowedFileTypes = array(
        'image/jpeg' => 5 * 1024 * 1024,       // JPEG files, max 5MB
        'image/png'  => 5 * 1024 * 1024,       // PNG files, max 5MB
        'application/pdf' => 10 * 1024 * 1024, // PDF files, max 10MB
    );

    public function pipe($request, $response) {
        if ($request->server->method === 'POST') {
            if (isset($request->files['upload'])) {
                $file = $request->files['upload'];

                $validationResult = validateFileUpload($file, $this->allowedFileTypes);

                if ($validationResult !== true) {
                    trigger_error($validationResult);
                    exit();
                }
            }
        }

        return array($request, $response);
    }

    function validateFileUpload($file, $allowedFileTypes) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return "Error during file upload.";
        }

        $fileTmpPath = $file['tmp_name'];
        $fileType = mime_content_type($fileTmpPath);  // MIME type (e.g., image/jpeg)
        $fileSize = $file['size'];                    // File size in bytes

        if (!array_key_exists($fileType, $allowedFileTypes)) {
            return "Invalid file type. Allowed types are: JPEG, PNG, PDF.";
        }

        if ($fileSize > $allowedFileTypes[$fileType]) {
            return "File is too large. Maximum allowed size is " . ($allowedFileTypes[$fileType] / (1024 * 1024)) . "MB.";
        }

        return true;
    }
}