<?php

// GET
// ==========
$group = array(
    'prepend' => array(
        'App_Pipe_Lang'
    ),
    'append' => array(
        'Shared_Pipe_OutputCompression'
    ),
);

$app->groupRoute($group, 'GET', '', array(
    'App_Pipe_Index'
));

$app->groupRoute($group, 'GET', 'home/:lang', array(
    'App_Pipe_Index'
));

$app->groupRoute($group, 'GET', 'home', array(
    'App_Pipe_Index'
));
