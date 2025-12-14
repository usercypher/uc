<?php 

$app = $data['app'];
$currentRoute = $data['current_route'];

$flash = isset($data['flash']) ? $data['flash'] : array();
$csrfToken = $data['csrf_token'];

?>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book - Create</title>
    <link rel="stylesheet" href="<?php echo($app->urlWeb('asset/css/uc.css')); ?>">
    <link rel="stylesheet" href="<?php echo($app->urlWeb('asset/css/style.css')); ?>">
    <script src="<?php echo($app->urlWeb('asset/js/uc.js')); ?>"></script>
</head>
<body>
    <h1>Add Book</h1>
    <ul>
        <li><a href="<?php echo($app->urlRoute('home')); ?>">Home</a></li>
    </ul>
    <hr>
    <form class="submit-form" action="<?php echo($app->urlRoute('book/store?redirect=:redirect', array(':redirect' => $currentRoute))); ?>" method="post">
        <fieldset>
            <legend>Book Information</legend>

            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

            <label>Title:</label>
            <p>
                <input type="text" name="book[title]" required>
            </p>

            <label>Author:</label>
            <p>
                <input type="text" name="book[author]">
            </p>

            <label>Publisher:</label>
            <p>
                <input type="text" name="book[publisher]">
            </p>

            <label>Year:</label>
            <p>
                <input type="date" name="book[year]">
            </p>

            <button>Create</button>
        </fieldset>
    </form>
    <?php require($app->dirRoot('res/app/view/include/script.html.php')); ?>
</body>
</html>