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
        <h1>Books</h1>
        <ul>
            <li><a href="<?php echo($app->url('route', 'home')); ?>">Refresh</a></li>
        </ul>
        <br>

        <a href="<?php echo($app->url('route', 'create')); ?>">
            <button class="add">Add Book</button>
        </a>
        <br><br>

        <div class="book-grid">
            <!-- Display books here -->
            <?php foreach ($data['books'] as $book) : ?>

            <div class="book-card">
                <h3><?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><strong>Author:</strong> <?php echo htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Publisher:</strong> <?php echo htmlspecialchars($book['publisher'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Year:</strong> <?php echo htmlspecialchars($book['year'], ENT_QUOTES, 'UTF-8'); ?></p>

                <!-- Actions (Edit & Delete) -->
                <div class="actions">
                    <a href="<?php echo $app->url('route', 'edit/' . $book['id']); ?>"><button>Edit</button></a>
                    <form action="<?php echo $app->url('route', 'book/delete'); ?>" method="post" style="display:inline;" onsubmit="return submitWithConfirm(event, 'Delete book <?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?>?');">
                        <input type="hidden" name="csrf_token" value="<?php echo $data['csrf_token']; ?>">
                        <input type="hidden" name="book[id]" value="<?php echo $book['id']; ?>">
                        <input type="submit" value="Delete">
                    </form>
                </div>
            </div>
            <?php endforeach; ?>

        </div>
    </div>
    <?php require($app->path('view', 'template' . DS . 'script.php')); ?>
</body>
</html>