<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oops! Something went wrong</title>
    <link rel="stylesheet" href="<?php echo App::buildLink('relative', 'asset/css/error.css'); ?>">
</head>
<body>
    <h1>Oops!</h1>
    <p>
        Something went wrong on our side. We're working to fix it.
    </p>
    <p>
        <a href="<?php echo App::buildLink('route', ''); ?>">Go back to the homepage</a>
    </p>
</body>
</html>