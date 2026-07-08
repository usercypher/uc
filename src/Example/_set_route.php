<?php

// GET
// ==========
$group = array(
    
);

$app->groupRoute($group, 'GET', 'example/user', array(
    'User_Pipe_Init', 'Shared_Pipe_SessionTokenGenerate', 'Example_Pipe_User'
));

$app->groupRoute($group, 'GET', 'example/game', array(
    'User_Pipe_Init', 'Shared_Pipe_SessionTokenGenerate', 'Example_Pipe_Game'
));
