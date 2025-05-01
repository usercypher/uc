<?php
// units.php

// Auto-load units from 'src/app/' directory and set path metadata (max depth 2), options: IGNORE => array('ignore*.pattern', 'ignore.file'), DIR_AS_NAMESPACE => true
// Unit names will be generated based on the file path, with directory separators replaced by dots.
$app->autoSetUnit('src'.DS.'app'.DS, array(MAX => 2));

// Set up the 'Database' class with caching enabled, ensuring a single instance is used.
$app->setUnit('lib.Database', array(ARGS => array('App'), CACHE => true));

// Define and inject dependencies: 'BookModel' depends on 'Database'
// imports: 'BookModel' loads 'Model'
$group = array(
    ARGS_PREPEND => array('lib.Database'),
    LOAD_PREPEND => array('lib.Model'),
);
$app->addUnit($group, 'model.BookModel');

$app->setUnit('lib.Session', array(CACHE => true));

$group = array(
    ARGS_PREPEND => array('App', 'lib.Session'),
);
$app->addUnit($group, 'pipe.book.BookCreate');
$app->addUnit($group, 'pipe.book.BookDelete', array(ARGS => array('model.BookModel')));
$app->addUnit($group, 'pipe.book.BookEdit', array(ARGS => array('model.BookModel')));
$app->addUnit($group, 'pipe.book.BookHome', array(ARGS => array('model.BookModel')));
$app->addUnit($group, 'pipe.book.BookStore', array(ARGS => array('model.BookModel')));
$app->addUnit($group, 'pipe.book.BookUpdate', array(ARGS => array('model.BookModel')));

$group = array(
    ARGS_PREPEND => array('lib.Session')
);
$app->addUnit($group, 'pipe.CsrfGenerate');
$app->addUnit($group, 'pipe.CsrfValidate');