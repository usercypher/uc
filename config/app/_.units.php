<?php
// app.units.php

/**
 * ------------------------------------------------------------------------
 * Model Units with Dependencies
 * ------------------------------------------------------------------------
 * Define 'Repo_Book' with dependencies on 'Lib_DatabaseHelper' and 'Lib_Database'.
 */
$group = array(
    'args_prepend' => array('Lib_Database'),  // Inject 'Lib_Database' as first constructor argument
    'load_prepend' => array('Lib_DatabaseHelper')      // Load 'Lib_DatabaseHelper' before 'Repo_Book'
);
$app->groupUnit($group, 'Repo_Book');

/**
 * ------------------------------------------------------------------------
 * Book-related Pipe Units with Dependencies
 * ------------------------------------------------------------------------
 * Pipes related to book functionality, with dependencies injected.
 */
$group = array(
    'args_prepend' => array('App', 'Lib_Session')
);
$app->groupUnit($group, 'Pipe_Book_Store', array('args' => array('Repo_Book')));
$app->groupUnit($group, 'Pipe_Book_Update', array('args' => array('Repo_Book')));
$app->groupUnit($group, 'Pipe_Book_Delete', array('args' => array('Repo_Book')));

// renderer
$app->groupUnit($group, 'Pipe_Book_Create', array('args' => array()));
$app->groupUnit($group, 'Pipe_Book_Edit', array('args' => array('Repo_Book')));
$app->groupUnit($group, 'Pipe_Book_Home', array('args' => array('Repo_Book')));
