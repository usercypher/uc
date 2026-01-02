<?php

$app = $data['app'];
$code = $data['code'];
$error = $data['error'];

$httpMap = array(
    400 => array('Bad Request', '400', 'The request could not be processed. Please verify the URL or parameters.'),
    401 => array('Unauthorized', '401', 'Authentication is required to access this resource.'),
    403 => array('Forbidden', '403', 'You do not have permission to access this resource.'),
    404 => array('Not Found', '404', 'The requested page could not be found.'),
    405 => array('Method Not Allowed', '405', 'The HTTP method used is not allowed for this resource.'),
    414 => array('Request-URI Too Long', '414', 'The URI provided in the request is too long. Please shorten the URL and try again.'),
    422 => array('Unprocessable Entity', '422', 'The request was well-formed but could not be followed due to semantic errors.'),
    500 => array('Internal Server Error', '500', 'An unexpected error occurred. Please try again later.')
);

list($title, $httpCode, $description) = isset($httpMap[$code]) ? $httpMap[$code] : $httpMap[500];

?>

<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <style>
        html {font-size: 16px;}
        body {font-family: Arial, sans-serif; margin: 1em;}
        h1 {font-size: 2.7em; font-weight: 500; margin-top: 1em;}
        p {line-height: 1.6;}
        a {text-decoration: none;}
        a:hover {text-decoration: underline;}
        pre {white-space: pre; overflow-x: auto; text-align: left;}
    </style>
</head>
<body>
    <h1><?php echo $title; ?></h1>
    <p><b><?php echo $httpCode; ?>.</b> <?php echo $description; ?> <a href="<?php echo $app->urlRoute(''); ?>">Go to homepage</a></p>
    <pre><?php echo $app->htmlEncode($error); ?></pre>
</body>
</html>