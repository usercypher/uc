<?php
// index.php

require('uc.package.php');

$app = app('dev');

// [CONFIG] start

// Auto-load classes from 'src/' directory and set path metadata (max depth 2)
$app->autoSetClass('uc.src' . DS, array('max' => 2));

// Set up the 'Database' class with caching enabled, ensuring a single instance is used.
$app->setClass('Database', array('args' => array('App'), 'cache' => true));

// Define and inject dependencies: 'BookModel' depends on 'Database'
$app->setClasses(array(
    'args' => array('Database')
), array(
    array('BookModel')
));

$app->setClass('Session', array('cache' => true));

$app->setClasses(array(
    'args' => array('App', 'Session', 'BookModel')
), array(
    array('BookCreate'),
    array('BookDelete'),
    array('BookEdit'),
    array('BookHome'),
    array('BookStore'),
    array('BookUpdate'),
));

$app->setClasses(array(
    'args' => array('Session')
), array(
    array('CsrfGenerate'),
    array('CsrfValidate'),
));

// Define s to handle data sanitization, CSRF generation
$app->setComponents(array(
    // preppend component to all routes component
    'prepend' => array(
        'Sanitize',
        'CsrfGenerate', 
    ),
    // append component to all routes component
    'append' => array()
));

// Define routes
$app->setRoute('GET', '', array('component' => array('BookHome', 'ResponseCompression'))); // Default route

// Define additional routes for 'home' and 'create'
$app->setRoutes(array( 
    'append' => array('ResponseCompression') // append component to route define in group
), array(
    array('GET', 'home', array('component' => array('BookHome'))),
    array('GET', 'create', array('component' => array('BookCreate'))),
    // Define a route for editing a book, with an ID parameter (only digits allowed)
    array('GET', 'edit/{id:^\d+$}', array('component' => array('BookEdit', 'ResponseCompression')))
));

// Define routes for 'book/' prefix with CSRF validation 
$app->setRoutes(array(
    'prefix' => 'book/',
    'prepend' => array('CsrfValidate'), // prepend component to route define in group
    'ignore' => array('CsrfGenerate')
), array(
    array('POST', 'store', array('component' => array('BookStore'))),
    array('POST', 'update', array('component' => array('BookUpdate'))),
    array('POST', 'delete', array('component' => array('BookDelete')))
));
// [CONFIG] end

// Uncomment to save the configuration once, then load it on subsequent runs.
// The configuration is saved using 'saveConfig' and loaded with 'loadConfig'.
// After the initial run, you can comment out 'saveConfig' and just use 'loadConfig'.
// When using 'loadConfig', remove or comment out the [CONFIG] body to avoid redundant config setting.
//$app->saveConfig('var/data/app.config');exit; // Save the configuration once
//$app->loadConfig('var/data/app.config'); // Load the saved configuration on subsequent runs

// Load base classes (Model), it included files base on class name
$app->loadClasses(array('Model'));

// Run the application
$response = $app->dispatch();
$response->send();