<?php

// GET
// ==========
$group = array(
    'prepend' => array('Shared_Pipe_SessionTokenGenerate'),
    'append' => array('Shared_Pipe_OutputCompression')
);

$app->groupRoute($group, 'GET', 'user/session-unset', array(
    'User_Pipe_Init', 'User_Pipe_SessionUnset'
));


// POST
// ==========
$group = array(
    'prepend' => array('Shared_Pipe_SessionTokenValidate')
);

$app->groupRoute($group, 'POST', 'user/store', array(
    'User_Pipe_Init', 'User_Pipe_Store'
));

$app->groupRoute($group, 'POST', 'user/update', array(
    'User_Pipe_Init', 'User_Pipe_Update'
));

$app->groupRoute($group, 'POST', 'user/delete', array(
    'User_Pipe_Init', 'User_Pipe_Delete'
));

$app->groupRoute($group, 'POST', 'user/session-verify', array(
    'User_Pipe_Init', 'User_Pipe_SessionVerify'
));