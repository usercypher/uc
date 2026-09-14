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
$app->unitGroup($group, 'Cli_Pipe_Help');
$app->unitGroup($group, 'Cli_Pipe_Unit_Create');
$app->unitGroup($group, 'Cli_Pipe_Route_Print');
$app->unitGroup($group, 'Cli_Pipe_Route_Resolve');
$app->unitGroup($group, 'Cli_Pipe_Route_Run');
$app->unitGroup($group, 'Cli_Pipe_File_Find');
$app->unitGroup($group, 'Cli_Pipe_File_FindReplace');
$app->unitGroup($group, 'Cli_Pipe_Db_Print');
$app->unitGroup($group, 'Cli_Pipe_Db_Exec', array('args' => array('Shared_Lib_Database')));
