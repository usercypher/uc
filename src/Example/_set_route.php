<?php

// GET
// ==========
$group = array(
    
);

$app->groupRoute($group, 'GET', 'example/user', array(
    'User_Pipe_Init', 'Example_Pipe_User'
));
