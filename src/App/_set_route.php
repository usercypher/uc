<?php

// GET
// ==========
$group = array(
    
);

$app->groupRoute($group, 'GET', '', array(
    'App_Pipe_Index'
));

$app->groupRoute($group, 'GET', 'php-info', array(
    'App_Pipe_PhpInfo'
));
