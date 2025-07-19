<?php
// app.units.php

/**
 * ------------------------------------------------------------------------
 * Core Library Units
 * ------------------------------------------------------------------------
 * Configure core library units with caching and constructor args.
 */

// Set 'Lib_Database' with 'App' argument and enable caching (singleton instance)
$app->setUnit('Lib_Database', array(
    'args' => array('App'),
    'cache' => true
));

// Set 'Lib_Session' with caching enabled
$app->setUnit('Lib_Session', array(
    'cache' => true
));

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

/**
 * ------------------------------------------------------------------------
 * CSRF Protection Pipes
 * ------------------------------------------------------------------------
 * Pipes for CSRF token generation and validation, requiring session support.
 */
$group = array(
    'args_prepend' => array('Lib_Session')
);
$app->groupUnit($group, 'Pipe_CsrfGenerate');
$app->groupUnit($group, 'Pipe_CsrfValidate');
