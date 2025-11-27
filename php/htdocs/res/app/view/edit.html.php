<?php 

$app = $data['app'];
$output = $data['output'];
$currentRoute = $data['current_route'];

$flash = isset($data['flash']) ? $data['flash'] : array();
$csrfToken = $data['csrf_token'];
$book = $data['book'];

?>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book - Edit</title>
    <link rel="stylesheet" href="<?php echo($app->urlWeb('asset/css/uc.css')); ?>">
    <link rel="stylesheet" href="<?php echo($app->urlWeb('asset/css/style.css')); ?>">
    <script src="<?php echo($app->urlWeb('asset/js/uc.js')); ?>"></script>
</head>
<body>
    <h1>Edit Book</h1>
    <ul>
        <li><a href="<?php echo($app->urlRoute('home')); ?>">Home</a></li>
    </ul>
    <hr>
    <form action="<?php echo($app->urlRoute('book/update?redirect=:redirect', array(':redirect' => $currentRoute))); ?>" method="post">
        <fieldset>
            <legend>Book Information</legend>

            <input type="hidden" name="csrf_token" value="<?php echo $output->htmlEncode($csrfToken); ?>">
            <input type="hidden" name="book[id]" value="<?php echo($output->htmlEncode($book['id'])); ?>">
            <input type="hidden" name="book[title][current]" value="<?php echo($output->htmlEncode($book['title'])); ?>">

            <label>Title:</label>
            <p>
                <input type="text" name="book[title][new]" value="<?php echo($output->htmlEncode($book['title'])); ?>" required>
            </p>

            <label>Author:</label>
            <p>
                <input type="text" name="book[author]" value="<?php echo($output->htmlEncode($book['author'])); ?>">
            </p>

            <label>Publisher:</label>
            <p>
                <input type="text" name="book[publisher]" value="<?php echo($output->htmlEncode($book['publisher'])); ?>">
            </p>

            <label>Year:</label>
            <p>
                <input type="date" name="book[year]" value="<?php echo($output->htmlEncode($book['year'])); ?>">
            </p>

            <button>Update</button>
        </fieldset>
    </form>
    <?php require($app->dirRoot('res/app/view/include/script.html.php')); ?>
</body>
</html>