<?php

/**
 * ------------------------------------------------------------------------
 * CLI Pipe
 * ------------------------------------------------------------------------
 * Handles dynamic CLI piping through optional route param.
 */
$group = array(

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
 * unit
 * ------------------------------------------------------------------------
 */
$group = array(

);

$app->groupRoute($group, '', 'unit/:on-unknown-option:*:', array(
    'pipe' => array('Pipe_Cli_Unit_Help'),
));

// route=unit/create/:name
$app->groupRoute($group, '', 'unit/create/:name:?:/::*:', array(
    'pipe' => array('Pipe_Cli_Unit_Create'),
));

/**
 * ------------------------------------------------------------------------
 * file
 * ------------------------------------------------------------------------
 */
$group = array(

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