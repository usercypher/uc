<?php

// GET
// ==========
$group = array(
    'prepend' => array('Shared_Pipe_CsrfGenerate'),
    'append' => array('Shared_Pipe_OutputCompression')
);

$app->groupRoute($group, 'GET', '', array(
    'Book_Pipe_Home'
));

$app->groupRoute($group, 'GET', 'home', array(
    'Book_Pipe_Home'
));

$app->groupRoute($group, 'GET', 'books', array(
    'Book_Pipe_Home'
));

$app->groupRoute($group, 'GET', 'create', array(
    'Book_Pipe_Create'
));

// route=edit/:slug
$app->groupRoute($group, 'GET', 'edit/:slug', array(
    'Book_Pipe_Edit'
));

// POST
// ==========
$group = array(
    'prepend' => array('Shared_Pipe_CsrfValidate')
);

$app->groupRoute($group, 'POST', 'book/store', array(
    'Book_Pipe_Store'
));

$app->groupRoute($group, 'POST', 'book/update', array(
    'Book_Pipe_Update'
));

$app->groupRoute($group, 'POST', 'book/delete', array(
    'Book_Pipe_Delete'
));
