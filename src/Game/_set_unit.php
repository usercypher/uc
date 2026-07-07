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
$app->groupUnit($group, 'Game_PlayerRepo');
$app->groupUnit($group, 'Game_TickRepo');

/**
 * ------------------------------------------------------------------------
 * Pipe
 * ------------------------------------------------------------------------
 */

$app->setUnit('Game_Pipe_Ws', array('args' => array('App', 'Shared_Lib_Curl', 'Game_PlayerRepo', 'Game_TickRepo')));


