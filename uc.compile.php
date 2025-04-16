<?php
// uc.compile.php

require('uc.package.php');
compile(config(app('dev')), 'var/data/app.config');

function compile($app, $configFile) {
    $app->saveConfig($configFile);
    exit;
}

// you can put this in separate file for more clean structure
function config($app) {
    define('GET', 'GET');
    define('POST', 'POST');

    // Auto-load classes from 'src/' directory and set path metadata (max depth 2), 'ignore' => array('ignore*.pattern', 'ignore.file')
    $app->autoSetClass('uc.src' . DS, array('max' => 2));
    
    // Set up the 'Database' class with caching enabled, ensuring a single instance is used.
    $app->setClass('Database', array('args' => array('App'), 'cache' => true));
    
    // Define and inject dependencies: 'BookModel' depends on 'Database'
    // inject load: 'Bookmodel' loads 'Model'
    $app->setClasses(array(
        'args_prepend' => array('Database'),
        'load_prepend' => array('Model'),
    ), array(
        array('BookModel')
    ));
    
    $app->setClass('Session', array('cache' => true));
    
    $app->setClasses(array(
        'args_prepend' => array('App', 'Session'),
    ), array(
        array('BookCreate'),
        array('BookDelete', array('args' => array('BookModel'))),
        array('BookEdit', array('args' => array('BookModel'))),
        array('BookHome', array('args' => array('BookModel'))),
        array('BookStore', array('args' => array('BookModel'))),
        array('BookUpdate', array('args' => array('BookModel'))),
    ));
    
    $app->setClasses(array(
        'args_prepend' => array('Session')
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
    $app->setRoute(GET, '', array('component' => array('BookHome', 'ResponseCompression'))); // Default route
    
    // Define additional routes for env 'home' and 'create'
    $app->setRoutes(array( 
        'component_append' => array('ResponseCompression') // append component to route define in group
    ), array(
        array(GET, 'home', array('component' => array('BookHome'))),
        array(GET, 'create', array('component' => array('BookCreate'))),
        // Define a route for env editing a book, with an ID parameter (only digits allowed)
        array(GET, 'edit/{id:^\d+$}', array('component' => array('BookEdit')))
    ));
    
    // Define routes for env 'book/' prefix with CSRF validation 
    $app->setRoutes(array(
        'prefix' => 'book/',
        'component_prepend' => array('CsrfValidate'), // prepend component to route define in group
        'ignore' => array('CsrfGenerate')
    ), array(
        array(POST, 'store', array('component' => array('BookStore'))),
        array(POST, 'update', array('component' => array('BookUpdate'))),
        array(POST, 'delete', array('component' => array('BookDelete')))
    ));
    return $app;
}