<?php
// extra.cli.routes.php

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
    'prefix' => 'route',
    'ignore' => array('--global')
);

$app->groupRoute($group, '', '/:on-unknown-option:*:', array(
    'pipe' => array('Pipe_Cli_Route_Help'),
));

$app->groupRoute($group, '', '/print/::*:', array(
    'pipe' => array('Pipe_Cli_Route_Print'),
));

$app->groupRoute($group, '', '/resolve/::*:', array(
    'pipe' => array('Pipe_Cli_Route_Resolve'),
));

/**
 * ------------------------------------------------------------------------
 * pipe
 * ------------------------------------------------------------------------
 */
$group = array(
    'prefix' => 'pipe',
    'ignore' => array('--global')
);

$app->groupRoute($group, '', '/:on-unknown-option:*:', array(
    'pipe' => array('Pipe_Cli_Pipe_Help'),
));

$app->groupRoute($group, '', '/create/:class:?:/::*:', array(
    'pipe' => array('Pipe_Cli_Pipe_Create'),
));

/**
 * ------------------------------------------------------------------------
 * file
 * ------------------------------------------------------------------------
 */
$group = array(
    'prefix' => 'file',
    'ignore' => array('--global')
);

$app->groupRoute($group, '', '/:on-unknown-option:*:', array(
    'pipe' => array('Pipe_Cli_File_Help'),
));

$app->groupRoute($group, '', '/find/::*:', array(
    'pipe' => array('Pipe_Cli_File_Find'),
));

$app->groupRoute($group, '', '/find-replace/::*:', array(
    'pipe' => array('Pipe_Cli_File_FindReplace'),
));
