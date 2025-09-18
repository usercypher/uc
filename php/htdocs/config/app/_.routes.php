<?php

/**
 * ------------------------------------------------------------------------
 * BOOK
 * ------------------------------------------------------------------------
 */

// GET
// ==========
$group = array(
    'pipe_append' => array('Pipe_OutputCompression')
);

$app->groupRoute($group, 'GET', '', array(
    'pipe' => array('Pipe_Book_Home')
));

$app->groupRoute($group, 'GET', 'home', array(
    'pipe' => array('Pipe_Book_Home')
));

$app->groupRoute($group, 'GET', 'books', array(
    'pipe' => array('Pipe_Book_Home')
));

$app->groupRoute($group, 'GET', 'create', array(
    'pipe' => array('Pipe_Book_Create')
));

// route=edit/:title_id
$app->groupRoute($group, 'GET', 'edit/:title_id::([a-zA-Z0-9-]+)-([0-9]+)', array(
    'pipe' => array('Pipe_Book_Edit')
));

// route=edit/:id
$app->groupRoute($group, 'GET', 'edit/:id::[0-9]+', array(
    'pipe' => array('Pipe_Book_Edit')
));

// POST
// ==========
$group = array(
    'pipe_prepend' => array('Pipe_CsrfValidate'),
    'ignore' => array('Pipe_CsrfGenerate')
);

$app->groupRoute($group, 'POST', 'book/store', array(
    'pipe' => array('Pipe_Book_Store')
));

$app->groupRoute($group, 'POST', 'book/update', array(
    'pipe' => array('Pipe_Book_Update')
));

$app->groupRoute($group, 'POST', 'book/delete', array(
    'pipe' => array('Pipe_Book_Delete')
));
