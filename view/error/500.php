<?php

$head_title = '500 Internal Server Error';
$title = '500';
$description = 'Something went wrong on our end. Please try again later.';

?>
   
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $head_title; ?></title>
    <style>
       * { box-sizing: border-box; }
        html, body { height: 100%; }
        html { font-size: 16px; }
        body { font-family: Arial, sans-serif; padding: 0; margin: 0; }
        @media (max-width: 768px) { html { font-size: 14px; } }
        div { overflow-wrap: break-word; padding: 3rem; }
        h1 { font-size: 4.5rem; color: #e74c3c; }
        p { font-size: 1.125rem; }
        a { color: #3498db; text-decoration: none; font-weight: bold; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div>
        <h1><?php echo $title; ?></h1>
        <p>
            <?php echo $description; ?>
        </p>
        <p>
            <a href="<?php echo App::url('route', ''); ?>">Go back to the homepage</a>
        </p>
    </div>
</body>
</html>