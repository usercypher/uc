<?php

$app = $data['app'];

$errorMap = array(
    500 => array('Internal Server Error', '500', 'Something went wrong on our end. Please try again later.'),
    404 => array('Not Found', '404', 'The page you are looking for could not be found.'),
    // add more
);

$error = isset($errorMap[$data['error_code']]) ? $errorMap[$data['error_code']] : array('Oops! Something went wrong', 'Oops!', 'Something went wrong on our side. We\'re working to fix it.');

$head_title = $error[0];
$title = $error[1];
$description = $error[2];

?>

<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $head_title; ?></title>
    <style>
        * { box-sizing: border-box; }
        html { font-size: 16px; }
        body { font-family: Arial, sans-serif; padding: 0; margin: 0; }
        div { word-wrap: break-word; padding: 3em; text-align: center; }
        h1 { font-size: 4em; color: #e74c3c; font-weight: 900; }
        p { font-size: 1em; line-height: 1.6;}
        a { color: #3498db; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div>
        <h1><?php echo $title; ?></h1>
        <p><?php echo $description; ?> <a href="<?php echo $app->url('route', ''); ?>">GO BACK</a></p>
    </div>
</body>
</html>