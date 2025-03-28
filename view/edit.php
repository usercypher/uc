<?php 

$app = $data['app'];

?>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="<?php echo($app->url('web', 'asset/css/dialog.css')); ?>">
    <link rel="stylesheet" href="<?php echo($app->url('web', 'asset/css/general-button.css')); ?>">
    <link rel="stylesheet" href="<?php echo($app->url('web', 'asset/css/general.css')); ?>">
    <script src="<?php echo($app->url('web', 'asset/js/dialog.js')); ?>"></script>
</head>
<body>
    <div class="container">
        <h1>Edit Book</h1>
        <ul>
            <li><a href="<?php echo($app->url('route', 'home')); ?>">Home</a></li>
        </ul>
        <br>
        <div class='container-form'>
            <form action="<?php echo($app->url('route', 'book/update')); ?>" method="post" onsubmit="return submitWithConfirm(event);">
                <input type="hidden" name="csrf_token" value="<?php echo $data['csrf_token']; ?>">
                <input type="hidden" name="book[id]" value="<?php echo($data['book']['id']); ?>">
                <input type="hidden" name="book[title][current]" value="<?php echo($data['book']['title']); ?>">

                <label>Title:</label>
                <input type="text" name="book[title][new]" value="<?php echo($data['book']['title']); ?>" required>

                <label>Author:</label>
                <input type="text" name="book[author]" value="<?php echo($data['book']['author']); ?>">

                <label>Publisher:</label>
                <input type="text" name="book[publisher]" value="<?php echo($data['book']['publisher']); ?>">

                <label>Year:</label>
                <input type="date" name="book[year]" value="<?php echo($data['book']['year']); ?>">

                <input type="submit" value="Update">
            </form>
        </div>
    </div>
    <?php require($app->path('view', 'template' . DS . 'script.php')); ?>
</body>
</html>