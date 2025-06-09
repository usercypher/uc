<?php
// units.php

/**
 * ------------------------------------------------------------------------
 * Auto-load Units
 * ------------------------------------------------------------------------
 * Automatically scan and load units from the 'src/app/' directory.
 * Options:
 *  - 'max' => 2          // Max directory depth to scan (-1 = unlimited)
 *  - 'ignore' => [...]   // Patterns/files to ignore
 *  - 'dir_as_namespace' => true // Use directory structure as namespace prefix
 */
$app->scanUnits('src'.DS.'app'.DS, array());

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
 * Define 'Model_Book' with dependencies on 'Lib_Model' and 'Lib_Database'.
 */
$group = array(
    'args_prepend' => array('Lib_Database'),  // Inject 'Lib_Database' as first constructor argument
    'load_prepend' => array('Lib_Model')      // Load 'Lib_Model' before 'Model_Book'
);
$app->groupUnit($group, 'Model_Book');

/**
 * ------------------------------------------------------------------------
 * CLI and Source AutoLoader Pipes
 * ------------------------------------------------------------------------
 * Define pipe units handling CLI and source auto-loading.
 */
$group = array(
    'args_prepend' => array('App')
);
$app->groupUnit($group, 'Pipe_Cli_Landing');
$app->groupUnit($group, 'Pipe_Cli_Pipe');
$app->groupUnit($group, 'Pipe_Cli_Route');
$app->groupUnit($group, 'Pipe_SrcAutoLoader');

/**
 * ------------------------------------------------------------------------
 * Book-related Pipe Units with Dependencies
 * ------------------------------------------------------------------------
 * Pipes related to book functionality, with dependencies injected.
 */
$group = array(
    'args_prepend' => array('App', 'Lib_Session')
);
$app->groupUnit($group, 'Pipe_Book_Create');
$app->groupUnit($group, 'Pipe_Book_Store', array('args' => array('Model_Book')));
$app->groupUnit($group, 'Pipe_Book_Update', array('args' => array('Model_Book')));
$app->groupUnit($group, 'Pipe_Book_Delete', array('args' => array('Model_Book')));
$app->groupUnit($group, 'Pipe_Book_Edit', array('args' => array('Model_Book')));
$app->groupUnit($group, 'Pipe_Book_Home', array('args' => array('Model_Book')));

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
