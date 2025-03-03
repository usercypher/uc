<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="<?php echo(App::url('web', 'asset/css/dialog.css')); ?>">
    <link rel="stylesheet" href="<?php echo(App::url('web', 'asset/css/general-button.css')); ?>">
    <link rel="stylesheet" href="<?php echo(App::url('web', 'asset/css/general.css')); ?>">
    <script src="<?php echo(App::url('web', 'asset/js/dialog.js')); ?>"></script>
</head>
<body>
    <div class="container">
        <h1>Add Book</h1>
        <ul>
            <li><a href="<?php echo(App::url('route', 'home')); ?>">Home</a></li>
        </ul>
        <br>
        <div class='container-form'>
            <form class="submit-form" action="<?php echo(App::url('route', 'book/create')); ?>" method="post">
                <input type="hidden" name="_token" value="<?php echo(isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : null); ?>">

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
    <?php require(App::path('src', 'view/script.php')); ?>
</body>
</html>