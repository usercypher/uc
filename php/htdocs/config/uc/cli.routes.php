<?php
// uc.cli.routes.php

/**
 * ------------------------------------------------------------------------
 * CLI Pipe
 * ------------------------------------------------------------------------
 * Handles dynamic CLI piping through optional route params.
 */
$group = array(
    'ignore' => array('--global')
);

$app->groupRoute($group, '', ':on-unknown-route:*:', array(
    'pipe' => array('Pipe_Cli_Help'),
));

/**
 * ------------------------------------------------------------------------
 * route
 * ------------------------------------------------------------------------
 */
$group = array(
    'ignore' => array('--global')
);

$app->groupRoute($group, '', 'route/:on-unknown-option:*:', array(
    'pipe' => array('Pipe_Cli_Route_Help'),
));

$app->groupRoute($group, '', 'route/print/::*:', array(
    'pipe' => array('Pipe_Cli_Route_Print'),
));

$app->groupRoute($group, '', 'route/resolve/::*:', array(
    'pipe' => array('Pipe_Cli_Route_Resolve'),
));

$app->groupRoute($group, '', 'route/run/::*:', array(
    'pipe' => array('Pipe_Cli_Route_Run'),
));

/**
 * ------------------------------------------------------------------------
 * pipe
 * ------------------------------------------------------------------------
 */
$group = array(
    'ignore' => array('--global')
);

$app->groupRoute($group, '', 'pipe/:on-unknown-option:*:', array(
    'pipe' => array('Pipe_Cli_Pipe_Help'),
));

// route=pipe/create/:class
$app->groupRoute($group, '', 'pipe/create/:class:?:/::*:', array(
    'pipe' => array('Pipe_Cli_Pipe_Create'),
));

/**
 * ------------------------------------------------------------------------
 * file
 * ------------------------------------------------------------------------
 */
$group = array(
    'ignore' => array('--global')
);

$app->groupRoute($group, '', 'file/:on-unknown-option:*:', array(
    'pipe' => array('Pipe_Cli_File_Help'),
));

$app->groupRoute($group, '', 'file/find/::*:', array(
    'pipe' => array('Pipe_Cli_File_Find'),
));

$app->groupRoute($group, '', 'file/find-replace/::*:', array(
    'pipe' => array('Pipe_Cli_File_FindReplace'),
));