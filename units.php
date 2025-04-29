<?php
// units.php

// Auto-load units from 'src/app/' directory and set path metadata (max depth 2), options: 'ignore' => array('ignore*.pattern', 'ignore.file'), dir_as_namespace => true
// Unit names will be generated based on the file path, with directory separators replaced by dots.
$app->autoSetUnit('src'.DS.'app'.DS, array('max' => 2));

// Set up the 'Database' class with caching enabled, ensuring a single instance is used.
$app->setUnit('lib.Database', array('args' => array('App'), 'cache' => true));

// Define and inject dependencies: 'BookModel' depends on 'Database'
// imports: 'BookModel' loads 'Model'
$app->setUnits(array(
    'args_prepend' => array('lib.Database'),
    'load_prepend' => array('lib.Model'),
), array(
    array('model.BookModel')
));

$app->setUnit('lib.Session', array('cache' => true));

$app->setUnits(array(
    'args_prepend' => array('App', 'lib.Session'),
), array(
    array('pipe.book.BookCreate'),
    array('pipe.book.BookDelete', array('args' => array('model.BookModel'))),
    array('pipe.book.BookEdit', array('args' => array('model.BookModel'))),
    array('pipe.book.BookHome', array('args' => array('model.BookModel'))),
    array('pipe.book.BookStore', array('args' => array('model.BookModel'))),
    array('pipe.book.BookUpdate', array('args' => array('model.BookModel'))),
));

$app->setUnits(array(
    'args_prepend' => array('lib.Session')
), array(
    array('pipe.CsrfGenerate'),
    array('pipe.CsrfValidate'),
));