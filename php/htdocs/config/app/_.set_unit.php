<?php

/**
 * ------------------------------------------------------------------------
 * Repo
 * ------------------------------------------------------------------------
 */
$group = array(
    'args_prepend' => array('App', 'Lib_Database'),
    'load_prepend' => array('Lib_DatabaseHelper')
);
$app->groupUnit($group, 'Repo_Book');

/**
 * ------------------------------------------------------------------------
 * Pipe
 * ------------------------------------------------------------------------
 */
$app->setUnit('Pipe_Init', array('args' => array('App', 'Lib_Session')));

/**
 * ------------------------------------------------------------------------
 * Pipe Book
 * ------------------------------------------------------------------------
 */
$group = array(
    'args_prepend' => array('App', 'Lib_Session')
);
$app->groupUnit($group, 'Pipe_Book_Store', array('args' => array('Repo_Book')));
$app->groupUnit($group, 'Pipe_Book_Update', array('args' => array('Repo_Book')));
$app->groupUnit($group, 'Pipe_Book_Delete', array('args' => array('Repo_Book')));

$app->groupUnit($group, 'Pipe_Book_Create', array('args' => array()));
$app->groupUnit($group, 'Pipe_Book_Edit', array('args' => array('Repo_Book')));
$app->groupUnit($group, 'Pipe_Book_Home', array('args' => array('Repo_Book')));
