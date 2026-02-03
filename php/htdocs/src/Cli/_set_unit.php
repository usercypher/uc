<?php
// cli.set_unit.php

/**
 * ------------------------------------------------------------------------
 * CLI
 * ------------------------------------------------------------------------
 * Define pipe units handling CLI
 */
$group = array(
    'args_prepend' => array('App')
);
$app->groupUnit($group, 'Cli_Pipe_Help');
$app->groupUnit($group, 'Cli_Pipe_Unit_Create');
$app->groupUnit($group, 'Cli_Pipe_Route_Print');
$app->groupUnit($group, 'Cli_Pipe_Route_Resolve');
$app->groupUnit($group, 'Cli_Pipe_Route_Run');
$app->groupUnit($group, 'Cli_Pipe_File_Find');
$app->groupUnit($group, 'Cli_Pipe_File_FindReplace');
$app->groupUnit($group, 'Cli_Pipe_Sql_Print');
