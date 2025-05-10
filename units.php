<?php
// units.php

// Auto-load units from the 'src/app/' directory and set path metadata (max depth 2).
// Options: 
// 'max' => '0' // 0 = no limits.
// 'ignore' => array('ignore*.pattern', 'ignore.file')  // Ignore files matching the given patterns.
// 'dir_as_namespace' => true  // If enabled, treat directories as namespaces. Each directory level becomes part of the namespace prefix, separated by '\'. 
// Unit names will be derived from the filename. If 'dir_as_namespace' is true, the directory structure is included in the namespace, 
// with the root directory (in this case, 'src/app/') excluded from the namespace.
// For example, 'src/app/Model/Book.php' would become 'Model\Book' when 'dir_as_namespace' is enabled.
$app->autoSetUnit('src'.DS.'app'.DS, array('max' => 2));

// Set up the 'Lib_Database' class, enabling caching to ensure only a single instance is used.
// The 'args' option specifies that 'App' is passed as an argument when the class is instantiated.
$app->setUnit('Lib_Database', array('args' => array('App'), 'cache' => true));

// Define and inject dependencies: 'Model_Book' depends on 'Lib_Model'.
// This means that before 'Model_Book' is instantiated, 'Lib_Model' will be loaded first.
$group = array(
    'args_prepend' => array('Lib_Database'),  // 'Lib_Database' is injected as the first argument.
    'load_prepend' => array('Lib_Model'),     // 'Lib_Model' is loaded before 'Model_Book'.
);
$app->addUnit($group, 'Model_Book');

// Set up 'Lib_Session' with caching enabled to reuse the same instance across the application.
$app->setUnit('Lib_Session', array('cache' => true));

// Define a group for 'Pipe_Cli_PipeCreate', where 'App' is injected as a required argument.
$group = array(
    'args_prepend' => array('App'),
);
$app->addUnit($group, 'Pipe_Cli_PipeCreate');
$app->addUnit($group, 'Pipe_SrcAutoLoader');

// Define other pipe units, specifying their dependencies:
$group = array(
    'args_prepend' => array('App', 'Lib_Session'),  // Inject 'App' and 'Lib_Session' as arguments for the pipes.
);
$app->addUnit($group, 'Pipe_Book_Create');
$app->addUnit($group, 'Pipe_Book_Delete', array('args' => array('Model_Book')));
$app->addUnit($group, 'Pipe_Book_Edit', array('args' => array('Model_Book')));
$app->addUnit($group, 'Pipe_Book_Home', array('args' => array('Model_Book')));
$app->addUnit($group, 'Pipe_Book_Store', array('args' => array('Model_Book')));
$app->addUnit($group, 'Pipe_Book_Update', array('args' => array('Model_Book')));

// Set up CSRF protection pipes, with 'Lib_Session' injected as a dependency.
$group = array(
    'args_prepend' => array('Lib_Session')
);
$app->addUnit($group, 'Pipe_CsrfGenerate');
$app->addUnit($group, 'Pipe_CsrfValidate');
