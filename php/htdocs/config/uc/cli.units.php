<?php
// extra.cli.units.php

/**
 * ------------------------------------------------------------------------
 * CLI
 * ------------------------------------------------------------------------
 * Define pipe units handling CLI
 */
$group = array(
    'args_prepend' => array('App')
);
$app->groupUnit($group, 'Pipe_Cli_Help');
$app->groupUnit($group, 'Pipe_Cli_Pipe_Create');
$app->groupUnit($group, 'Pipe_Cli_Route_Print');
$app->groupUnit($group, 'Pipe_Cli_Route_Resolve');
$app->groupUnit($group, 'Pipe_Cli_Route_Run');
