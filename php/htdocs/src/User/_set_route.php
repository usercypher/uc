<?php

// GET
// ==========
$group = array(
    'prepend' => array('Shared_Pipe_CsrfGenerate'),
    'append' => array('Shared_Pipe_OutputCompression')
);

$app->groupRoute($group, 'GET', 'user/create', array(
    'User_Pipe_Init', 'User_Pipe_Create'
));


// POST
// ==========
$group = array(
    'prepend' => array('Shared_Pipe_CsrfValidate')
);

$app->groupRoute($group, 'POST', 'user/store', array(
    'User_Pipe_Init', 'User_Pipe_Store'
));