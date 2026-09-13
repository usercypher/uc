<?php

/**
 * ------------------------------------------------------------------------
 * Repo
 * ------------------------------------------------------------------------
 */
$group = array(
    'args_prepend' => array('App', 'Shared_Lib_Database', 'Shared_Lib_Cast_Standard', 'Shared_Lib_Cast_Db'),
    'load_prepend' => array('Shared_Lib_DatabaseHelper')
);
$app->groupUnit($group, 'Game_PlayerRepo', array(
    'args' => array($app->argUnitData('game'), $app->argUnitData('player'))
));
$app->groupUnit($group, 'Game_TickRepo', array(
    'args' => array($app->argUnitData('game'), $app->argUnitData('tick'))
));

/**
 * ------------------------------------------------------------------------
 * Pipe
 * ------------------------------------------------------------------------
 */

$app->setUnit('Game_Pipe_Ws', array('args' => array('App', 'Shared_Lib_Curl', 'Game_PlayerRepo', 'Game_TickRepo')));


