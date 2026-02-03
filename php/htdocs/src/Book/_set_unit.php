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
$app->groupUnit($group, 'Book_Repo');

/**
 * ------------------------------------------------------------------------
 * Pipe
 * ------------------------------------------------------------------------
 */
$group = array(
    'args_prepend' => array('App', 'Shared_Lib_Session')
);
$app->groupUnit($group, 'Book_Pipe_Store', array('args' => array('Book_Repo')));
$app->groupUnit($group, 'Book_Pipe_Update', array('args' => array('Book_Repo')));
$app->groupUnit($group, 'Book_Pipe_Delete', array('args' => array('Book_Repo')));

$app->groupUnit($group, 'Book_Pipe_Create', array('args' => array()));
$app->groupUnit($group, 'Book_Pipe_Edit', array('args' => array('Book_Repo')));
$app->groupUnit($group, 'Book_Pipe_Home', array('args' => array('Book_Repo')));
