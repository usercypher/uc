<?php

// GET
// ==========
$group = array(
    'prepend' => array(
        'App_Pipe_Lang', 'Example_Pipe_Lang'
    )
);

$app->groupRoute($group, 'GET', 'example/user', array(
    'User_Pipe_Init', 'Shared_Pipe_SessionTokenGenerate', 'Example_Pipe_User'
));

$app->groupRoute($group, 'GET', 'example/user/:lang', array(
    'User_Pipe_Init', 'Shared_Pipe_SessionTokenGenerate', 'Example_Pipe_User'
));

$app->groupRoute($group, 'GET', 'example/game', array(
    'User_Pipe_Init', 'Shared_Pipe_SessionTokenGenerate', 'Example_Pipe_Game'
));
