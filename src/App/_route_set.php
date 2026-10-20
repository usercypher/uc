<?php

// GET
// ==========
$group = array(
    'prepend' => array(
        
    ),
    'append' => array(
        'Shared_Pipe_OutputCompression'
    ),
);

$app->routeGroup($group, 'GET', '', array(
    'App_Pipe_Index'
));

$app->routeGroup($group, 'GET', 'home/:lang', array(
    'App_Pipe_Index'
));

$app->routeGroup($group, 'GET', 'home', array(
    'App_Pipe_Index'
));
