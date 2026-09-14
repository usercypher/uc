<?php

// GET
// ==========
$group = array(
    'prepend' => array('Shared_Pipe_SessionTokenGenerate'),
    'append' => array('Shared_Pipe_OutputCompression')
);

$app->routeGroup($group, 'GET', 'user/session-unset', array(
    'User_Pipe_Init', 'User_Pipe_SessionUnset'
));

// POST
// ==========
$group = array(
    'prepend' => array('Shared_Pipe_SessionTokenValidate', 'User_Pipe_Lang')
);

$app->routeGroup($group, 'POST', 'user/store', array(
    'User_Pipe_Init', 'User_Pipe_Store'
));

$app->routeGroup($group, 'POST', 'user/update', array(
    'User_Pipe_Init', 'User_Pipe_Update'
));

$app->routeGroup($group, 'POST', 'user/delete', array(
    'User_Pipe_Init', 'User_Pipe_Delete'
));

$app->routeGroup($group, 'POST', 'user/session-verify', array(
    'User_Pipe_Init', 'User_Pipe_SessionVerify'
));


/**
 * ------------------------------------------------------------------------
 * Cli
 * ------------------------------------------------------------------------
 */
$group = array(

);

$app->routeGroup($group, '', 'cli/user/:on-unknown-option*', array(
    'User_Pipe_Cli_Help'
));

$app->routeGroup($group, '', 'cli/user/create/:*', array(
    'User_Pipe_Lang', 'User_Pipe_Init', 'User_Pipe_Cli_Create', 'User_Pipe_Store', 'Shared_Pipe_ExtractFlash'
));