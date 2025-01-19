<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="<?php echo(App::buildLink('relative', 'asset/css/common.css')); ?>">
    <script src="<?php echo(App::buildLink('relative', 'asset/js/confirm.js')); ?>"></script>
</head>
<body>
    <?php include(App::buildPath('src/view/loading-and-confirm.php'));?>

    <h1>Edit Book</h1>
    <ul>
        <li><a href="<?php echo(App::buildLink('route', '/home')); ?>">Home</a></li>
    </ul>
    <br>

    <div class='container-form'>
        <form action="<?php echo(App::buildLink('route', '/book/update')); ?>" method="post" onsubmit="return confirm(event, 'Are you sure you want to update this information? Changes will overwrite the current data');">
            <input type="hidden" name="_token" value="<?php echo(isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : null); ?>">
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

    <?php
    if (isset($_SESSION['errors'])) {
        foreach ($_SESSION['errors'] as $error) {
            echo('<p class="error">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</p>');
        }
        unset($_SESSION['errors']);
    }
    ?>
</body>
</html>
