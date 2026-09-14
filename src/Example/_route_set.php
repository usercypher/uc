<?php

// GET
// ==========
$group = array(
    'prepend' => array(
        'Example_Pipe_Lang', 'User_Pipe_Lang'
    )
);

$app->routeGroup($group, 'GET', 'example/user', array(
    'User_Pipe_Init', 'Shared_Pipe_SessionTokenGenerate', 'Example_Pipe_User'
));

$app->routeGroup($group, 'GET', 'example/user/:lang', array(
    'User_Pipe_Init', 'Shared_Pipe_SessionTokenGenerate', 'Example_Pipe_User'
));

$app->routeGroup($group, 'GET', 'example/game', array(
    'User_Pipe_Init', 'Shared_Pipe_SessionTokenGenerate', 'Example_Pipe_Game'
));
