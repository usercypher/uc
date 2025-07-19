<?php 

$app = $data['app'];
$output = $data['output'];

$flash = $data['flash'];
$csrfToken = $data['csrf_token'];
$book = $data['book'];

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
        <h1>Edit Book</h1>
        <ul>
            <li><a href="<?php echo($app->urlRoute('home')); ?>">Home</a></li>
        </ul>
        <br>
        <div class='container-form'>
            <form action="<?php echo($app->urlRoute('book/update')); ?>" method="post" onsubmit="return submitWithConfirm(event);">
                <input type="hidden" name="csrf_token" value="<?php echo $output->htmlEncode($csrfToken); ?>">
                <input type="hidden" name="book[id]" value="<?php echo($output->htmlEncode($book['id'])); ?>">
                <input type="hidden" name="book[title][current]" value="<?php echo($output->htmlEncode($book['title'])); ?>">

                <label>Title:</label>
                <input type="text" name="book[title][new]" value="<?php echo($output->htmlEncode($book['title'])); ?>" required>

                <label>Author:</label>
                <input type="text" name="book[author]" value="<?php echo($output->htmlEncode($book['author'])); ?>">

                <label>Publisher:</label>
                <input type="text" name="book[publisher]" value="<?php echo($output->htmlEncode($book['publisher'])); ?>">

                <label>Year:</label>
                <input type="date" name="book[year]" value="<?php echo($output->htmlEncode($book['year'])); ?>">

                <input type="submit" value="Update">
            </form>
        </div>
    </div>
    <?php require($app->dirRes('html/template' . DS . 'script.php')); ?>
</body>
</html>