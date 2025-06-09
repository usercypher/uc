<?php
// cli.routes.php

/**
 * ------------------------------------------------------------------------
 * CLI Pipe
 * ------------------------------------------------------------------------
 * Handles dynamic CLI piping through optional route params.
 */
$group = array(
    'ignore' => array('--global')
);

$app->groupRoute($group, '', '{onUnknownRoute:*:}', array(
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

$app->groupRoute($group, '', '/{onUnknownOption:*:}', array(
    'pipe' => array('Pipe_Cli_Route_Help'),
));

$app->groupRoute($group, '', '/print', array(
    'pipe' => array('Pipe_Cli_Route_Print'),
));

$app->groupRoute($group, '', '/resolve', array(
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

$app->groupRoute($group, '', '/{onUnknownOption:*:}', array(
    'pipe' => array('Pipe_Cli_Pipe_Help'),
));

$app->groupRoute($group, '', '/create/{class:?:}', array(
    'pipe' => array('Pipe_Cli_Pipe_Create'),
));
