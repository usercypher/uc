<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found</title>
    <link rel="stylesheet" href="<?php echo App::buildLink('relative', 'asset/css/error.css'); ?>">
</head>
<body>
    <h1>404</h1>
    <p>
        The page you are looking for could not be found.
    </p>
    <p>
        <a href="<?php echo App::buildLink('route', '/'); ?>">Go back to the homepage</a>
    </p>
</body>
</html>