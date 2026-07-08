<?php

$app = $data['app'];
$code = $data['code'];
$error = $data['error'];

$httpMap = array(
    400 => array('Bad Request', 'The request could not be processed. Please verify the URL or parameters.'),
    401 => array('Unauthorized', 'Authentication is required to access this resource.'),
    403 => array('Forbidden', 'You do not have permission to access this resource.'),
    404 => array('Not Found', 'The requested page could not be found.'),
    405 => array('Method Not Allowed', 'The HTTP method used is not allowed for this resource.'),
    414 => array('Request-URI Too Long', 'The URI provided in the request is too long. Please shorten the URL and try again.'),
    422 => array('Unprocessable Entity', 'The request was well-formed but could not be followed due to semantic errors.'),
    500 => array('Internal Server Error', 'An unexpected error occurred. Please try again later.'),
);

list($title, $description) = isset($httpMap[$code]) ? $httpMap[$code] : $httpMap[500];

echo $title . "\n" . $code . '. ' . $description . ".\n\n" . $error;

?>
