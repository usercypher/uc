<?php 

$app = $data['app'];
$output = $data['output'];
$currentRoute = $data['current_route'];

$flash = isset($data['flash']) ? $data['flash'] : array();
$csrfToken = $data['csrf_token'];
$books = $data['books'];

?>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book - Home</title>
    <link rel="stylesheet" href="<?php echo($app->urlWeb('asset/css/uc.css')); ?>">
    <link rel="stylesheet" href="<?php echo($app->urlWeb('asset/css/style.css')); ?>">
    <script src="<?php echo($app->urlWeb('asset/js/uc.js')); ?>"></script>
</head>
<body>
    <h1>Books</h1>
    <ul>
        <li><a href="<?php echo($app->urlRoute('home')); ?>">Refresh</a></li>
        <li><a href="<?php echo($app->urlRoute('create')); ?>">Add Book</a></li>
    </ul>

    <hr>
    <!-- Display books here -->
    <?php foreach ($books as $i => $book) : ?>

    <div class="block">
        <h3><?php echo $output->htmlEncode($book['title']); ?></h3>
        <p><strong>Author:</strong> <?php echo $output->htmlEncode($book['author']); ?></p>
        <p><strong>Publisher:</strong> <?php echo $output->htmlEncode($book['publisher']); ?></p>
        <p><strong>Year:</strong> <?php echo $output->htmlEncode($book['year']); ?></p>

        <!-- Actions (Edit & Delete) -->
        <div class="actions">
            <a class="button" href="<?php echo $app->urlRoute('edit/:title_id', array(':title_id' => $app->strSlug($book['title'] . '-' . rawurlencode($book['id'])))); ?>">Edit</a>
            <button
                type="button"
                class="button negative"
                x-ref--book-delete-open-<?= $i ?>
                x-on-click
                x-rot--book-delete=""
                x-rot--book-delete-content=""
                x-focus="-book-delete-tab-last"
                x-tab="-book-delete-tab-first:-book-delete-tab-last"
                x-set-window.x-on-key-window-escape="-book-delete-close"
                x-set-window.x-run--book-delete-close="x-on-click"
                x-set--book-delete-close.x-focus="-book-delete-open-<?= $i ?>"
                x-val-book_id="<?= $output->htmlEncode($book['id']) ?>"
                x-val-book_title="<?= $output->htmlEncode($book['title']) ?>"
            >
                Delete
            </button>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="modal hidden" x-ref--book-delete x-on-click x-run--book-delete-close="x-on-click">
        <div class="modal-content small" x-ref--book-delete-content x-on-click x-no-prop>
            <span class="modal-close" x-ref--book-delete-close x-on-click x-rot--book-delete="hidden" x-rot--book-delete-content="small" x-focus="-book-delete-open">&times;</span>
            <h2>Delete</h2>
            <p>Do you want to delete book "<span x-ref-book_title></span>"?</p>
            <form method="POST" action="<?php echo $app->urlRoute('book/delete?redirect=:redirect', array(':redirect' => $currentRoute)); ?>">
                <input type="hidden" name="csrf_token" value="<?= $output->htmlEncode($csrfToken) ?>"/>
                <input type="hidden" name="book[id]" value="" required x-ref-book_id>

                <button type="submit" class="button negative" x-ref--book-delete-tab-first>Delete</button>
                <button type="button" class="button neutral" x-ref--book-delete-tab-last x-on-click x-run--book-delete-close="x-on-click">Cancel</button>
            </form>
        </div>
    </div>

    <?php require($app->dirRoot('res/app/view/include/script.html.php')); ?>
</body>
</html>