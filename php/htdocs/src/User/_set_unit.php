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

$app->groupUnit($group, 'User_Pipe_Store', array('args' => array('User_Repo')));
$app->groupUnit($group, 'User_Pipe_Update', array('args' => array('User_Repo')));
$app->groupUnit($group, 'User_Pipe_Delete', array('args' => array('User_Repo')));
$app->groupUnit($group, 'User_Pipe_SessionUnset', array('args' => array()));
$app->groupUnit($group, 'User_Pipe_SessionVerify', array('args' => array('User_Repo')));
$app->groupUnit($group, 'User_Pipe_IsAuth', array('args' => array()));
$app->groupUnit($group, 'User_Pipe_IsNotAuth', array('args' => array()));
