<?php
// units.php

// Auto-load units from 'src/app/' directory and set path metadata (max depth 2), options: 'ignore' => array('ignore*.pattern', 'ignore.file'), 'dir_as_namespace' => true
// Unit names will be generated based on the file path, with directory separators replaced by dots.
$app->autoSetUnit('src'.DS.'app'.DS, array('max' => 2));

// Set up the 'Lib_Database' class with caching enabled, ensuring a single instance is used.
$app->setUnit('Lib_Database', array('args' => array('App'), 'cache' => true));

// Define and inject dependencies: 'Model_Book' depends on 'Lib_Model'
// imports: 'Model_Book' loads 'Lib_Model'
$group = array(
    'args_prepend' => array('Lib_Database'),
    'load_prepend' => array('Lib_Model'),
);
$app->addUnit($group, 'Model_Book');

$app->setUnit('Lib_Session', array('cache' => true));

$group = array(
    'args_prepend' => array('App'),
);
$app->addUnit($group, 'Pipe_Cli_PipeCreate');

$group = array(
    'args_prepend' => array('App', 'Lib_Session'),
);
$app->addUnit($group, 'Pipe_Book_Create');
$app->addUnit($group, 'Pipe_Book_Delete', array('args' => array('Model_Book')));
$app->addUnit($group, 'Pipe_Book_Edit', array('args' => array('Model_Book')));
$app->addUnit($group, 'Pipe_Book_Home', array('args' => array('Model_Book')));
$app->addUnit($group, 'Pipe_Book_Store', array('args' => array('Model_Book')));
$app->addUnit($group, 'Pipe_Book_Update', array('args' => array('Model_Book')));

$group = array(
    'args_prepend' => array('Lib_Session')
);
$app->addUnit($group, 'Pipe_CsrfGenerate');
$app->addUnit($group, 'Pipe_CsrfValidate');