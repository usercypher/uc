<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 Internal Server Error</title>
    <link rel="stylesheet" href="<?php echo App::buildLink('relative', 'asset/css/error.css'); ?>">
</head>
<body>
    <h1>500</h1>
    <p>
        Something went wrong on our end. Please try again later.
    </p>
    <p>
        <a href="<?php echo App::buildLink('route', '/'); ?>">Go back to the homepage</a>
    </p>
</body>
</html>