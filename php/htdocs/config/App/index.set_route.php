<?php

/**
 * ------------------------------------------------------------------------
 * BOOK
 * ------------------------------------------------------------------------
 */

// GET
// ==========
$group = array(
    'prepend' => array('Pipe_CsrfGenerate'),
    'append' => array('Pipe_OutputCompression')
);

$app->groupRoute($group, 'GET', '', array(
    'Pipe_Book_Home'
));

$app->groupRoute($group, 'GET', 'home', array(
    'Pipe_Book_Home'
));

$app->groupRoute($group, 'GET', 'books', array(
    'Pipe_Book_Home'
));

$app->groupRoute($group, 'GET', 'create', array(
    'Pipe_Book_Create'
));

// route=edit/:slug
$app->groupRoute($group, 'GET', 'edit/:slug', array(
    'Pipe_Book_Edit'
));

// POST
// ==========
$group = array(
    'prepend' => array('Pipe_CsrfValidate')
);

$app->groupRoute($group, 'POST', 'book/store', array(
    'Pipe_Book_Store'
));

$app->groupRoute($group, 'POST', 'book/update', array(
    'Pipe_Book_Update'
));

$app->groupRoute($group, 'POST', 'book/delete', array(
    'Pipe_Book_Delete'
));
