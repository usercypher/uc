<?php

/**
 * ------------------------------------------------------------------------
 * CLI Pipe
 * ------------------------------------------------------------------------
 * Handles dynamic CLI piping through optional route param.
 */
$group = array(

);

$app->groupRoute($group, '', 'cli/:on-unknown-route*', array(
    'Cli_Pipe_Help'
));

/**
 * ------------------------------------------------------------------------
 * route
 * ------------------------------------------------------------------------
 */
$group = array(

);

$app->groupRoute($group, '', 'cli/route/:on-unknown-option*', array(
    'Cli_Pipe_Route_Help'
));

$app->groupRoute($group, '', 'cli/route/print/:*', array(
    'Cli_Pipe_Route_Print'
));

$app->groupRoute($group, '', 'cli/route/resolve/:*', array(
    'Cli_Pipe_Route_Resolve'
));

$app->groupRoute($group, '', 'cli/route/run/:*', array(
    'Cli_Pipe_Route_Run'
));

/**
 * ------------------------------------------------------------------------
 * unit
 * ------------------------------------------------------------------------
 */
$group = array(

);

$app->groupRoute($group, '', 'cli/unit/:on-unknown-option*', array(
    'Cli_Pipe_Unit_Help'
));

// route=unit/create/:name
$app->groupRoute($group, '', 'cli/unit/create/:name/:*', array(
    'Cli_Pipe_Unit_Create'
));

/**
 * ------------------------------------------------------------------------
 * file
 * ------------------------------------------------------------------------
 */
$group = array(

);

$app->groupRoute($group, '', 'cli/file/:on-unknown-option*', array(
    'Cli_Pipe_File_Help'
));

$app->groupRoute($group, '', 'cli/file/find/:*', array(
    'Cli_Pipe_File_Find'
));

$app->groupRoute($group, '', 'cli/file/find-replace/:*', array(
    'Cli_Pipe_File_FindReplace'
));

/**
 * ------------------------------------------------------------------------
 * sql
 * ------------------------------------------------------------------------
 */
$group = array(

);

$app->groupRoute($group, '', 'cli/sql/:on-unknown-option*', array(
    'Cli_Pipe_Sql_Help'
));

$app->groupRoute($group, '', 'cli/sql/print/:*', array(
    'Cli_Pipe_Sql_Print'
));
