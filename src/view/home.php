<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="<?php echo(App::buildLink('relative', 'asset/css/common.css')); ?>">
    <script src="<?php echo(App::buildLink('relative', 'asset/js/common.js')); ?>"></script>
</head>
<body>
    <?php include(App::buildPath('src/view/flash-and-loading-and-confirm.php')); ?>

    <h1>Books</h1>
    <ul>
        <li><a href="<?php echo(App::buildLink('route', 'home')); ?>">Refresh</a></li>
    </ul>
    <br>

    <a href="<?php echo(App::buildLink('route', 'create')); ?>">
        <button class="add">Add Book</button>
    </a>
    <br><br>

    <div class="book-grid">
        <!-- Display books here -->
        <?php
        foreach ($data['books'] as $book) {
            echo('<div class="book-card">');
            echo('    <h3>' . htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') . '</h3>');
            echo('    <p><strong>Author:</strong> ' . htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8') . '</p>');
            echo('    <p><strong>Publisher:</strong> ' . htmlspecialchars($book['publisher'], ENT_QUOTES, 'UTF-8') . '</p>');
            echo('    <p><strong>Year:</strong> ' . htmlspecialchars($book['year'], ENT_QUOTES, 'UTF-8') . '</p>');
            // Actions (Edit & Delete)
            echo('    <div class="actions">');
            echo('        <a href="' . App::buildLink('route', 'edit/' . $book['id']) . '"><button>Edit</button></a>');
            echo('<form action="' . App::buildLink('route', 'book/delete') . '" method="post" style="display:inline;" onsubmit="return confirm(event, \'Are you sure you want to delete this book?\');">');
            echo('            <input type="hidden" name="_token" value="' . (isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : null) . '">');
            echo('            <input type="hidden" name="book[id]" value="' . $book['id'] . '">');
            echo('            <input type="submit" value="Delete">');
            echo('        </form>');
            echo('    </div>');
            echo('</div>');
        }
        ?>
    </div>
</body>
</html>