<?php 

$app = $data['app'];
$flash = $data['flash'];
$csrfToken = $data['csrf_token'];

?>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="<?php echo($app->urlWeb('asset/css/dialog.css')); ?>">
    <link rel="stylesheet" href="<?php echo($app->urlWeb('asset/css/general-button.css')); ?>">
    <link rel="stylesheet" href="<?php echo($app->urlWeb('asset/css/general.css')); ?>">
    <script src="<?php echo($app->urlWeb('asset/js/dialog.js')); ?>"></script>
</head>
<body>
    <div class="container">
        <h1>Add Book</h1>
        <ul>
            <li><a href="<?php echo($app->urlRoute('home')); ?>">Home</a></li>
        </ul>
        <br>
        <div class='container-form'>
            <form class="submit-form" action="<?php echo($app->urlRoute('book/store')); ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                <label>Title:</label>
                <input type="text" name="book[title]" required>

                <label>Author:</label>
                <input type="text" name="book[author]">

                <label>Publisher:</label>
                <input type="text" name="book[publisher]">

                <label>Year:</label>
                <input type="date" name="book[year]">

                <input type="submit" value="Create">
            </form>
        </div>
    </div>
    <?php require($app->dirRes('html/template' . DS . 'script.php')); ?>
</body>
</html>