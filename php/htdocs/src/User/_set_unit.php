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
$app->groupUnit($group, 'User_Repo');

/**
 * ------------------------------------------------------------------------
 * Pipe
 * ------------------------------------------------------------------------
 */
$group = array(
    'args_prepend' => array('App', 'Shared_Lib_Session')
);

$app->groupUnit($group, 'User_Pipe_Create', array('args' => array()));
$app->groupUnit($group, 'User_Pipe_Store', array('args' => array('User_Repo')));
