<?php
// dbsetup.php

require('../uc.package.php');

// Initialize the application (prod environment)
$app = init('dev');

// [CONFIG] Auto-load classes from 'src/' directory (max depth 1)
$app->autoSetClass('uc.src' . DS, array('max' => 1));

// Set up the 'Database' class with caching enabled
$app->setClass('Database', array('args' => array('App'), 'cache' => true));

$app->setClass('Seeder', array('args' => array('Database')));

$app->getClass('Seeder')->run();

echo('done.' . PHP_EOL);