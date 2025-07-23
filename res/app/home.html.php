<?php 

$app = $data['app'];
$output = $data['output'];

$flash = $data['flash'];
$csrfToken = $data['csrf_token'];
$books = $data['books'];

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
        <h1>Books</h1>
        <ul>
            <li><a href="<?php echo($app->urlRoute('home')); ?>">Refresh</a></li>
        </ul>
        <br>

        <a href="<?php echo($app->urlRoute('create')); ?>">
            <button class="add">Add Book</button>
        </a>
        <br><br>

        <div class="book-grid">
            <!-- Display books here -->
            <?php foreach ($books as $book) : ?>

            <div class="book-card">
                <h3><?php echo $output->htmlEncode($book['title']); ?></h3>
                <p><strong>Author:</strong> <?php echo $output->htmlEncode($book['author']); ?></p>
                <p><strong>Publisher:</strong> <?php echo $output->htmlEncode($book['publisher']); ?></p>
                <p><strong>Year:</strong> <?php echo $output->htmlEncode($book['year']); ?></p>

                <!-- Actions (Edit & Delete) -->
                <div class="actions">
                    <a href="<?php echo $app->urlRoute('edit/:title_id', array(':title_id' => $app->strSlug($book['title'] . '-' . rawurlencode($book['id'])))); ?>"><button>Edit</button></a>
                    <form action="<?php echo $app->urlRoute('book/delete'); ?>" method="post" style="display:inline;" onsubmit="return submitWithConfirm(event, <?php echo $output->htmlEncode(json_encode('Delete book' . ($book['title']) . '?')); ?>);">
                        <input type="hidden" name="csrf_token" value="<?php echo $output->htmlEncode($csrfToken); ?>">
                        <input type="hidden" name="book[id]" value="<?php echo $output->htmlEncode($book['id']); ?>">
                        <input type="submit" value="Delete">
                    </form>
                </div>
            </div>
            <?php endforeach; ?>

        </div>
    </div>
    <?php require($app->dirRes('app/template/script.html.php')); ?>
</body>
</html>