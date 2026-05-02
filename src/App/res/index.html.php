<?php

$app = $data['app'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UC Framework</title>
    <style>
        html {font-size: 16px;}
        body {font-family: Arial, sans-serif; margin: 1em;}
        h1 {font-size: 2.7em; font-weight: 500; margin-top: 1em;}
        p {line-height: 1.6;}
        a {text-decoration: none; color: #0066cc;}
        a:hover {text-decoration: underline;}
        pre {white-space: pre; overflow-x: auto; text-align: left;}
    </style>
</head>
<body>
    <h1>UC Framework</h1>
    <p>Welcome to UC Framework. Explore the code, see how it works.</p>
    <p>
        <a href="https://github.com/usercypher/uc">View on GitHub</a> | 
        <a href="<?php echo $app->url('ROUTE', 'php-info'); ?>">PHP Info</a> | 
        <a href="<?php echo $app->url('ROUTE', 'example/user'); ?>">Example - User</a>
    </p>
</body>
</html>
