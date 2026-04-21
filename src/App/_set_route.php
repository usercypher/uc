<?php

// GET
// ==========
$group = array(
    
);

$app->groupRoute($group, 'GET', '', array(
    'User_Pipe_Init', 'Shared_Pipe_SessionTokenGenerate', 'App_Pipe_Index'
));
