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

    // Auto-load units from 'uc.src/' directory and set path metadata (max depth 2), options: 'ignore' => array('ignore*.pattern', 'ignore.file'), dir_as_namespace => true
    $app->autoSetUnit('uc.src' . DS, array('max' => 2));
    
    // Set up the 'Database' class with caching enabled, ensuring a single instance is used.
    $app->setUnit('lib/Database', array('args' => array('App'), 'cache' => true));
    
    // Define and inject dependencies: 'BookModel' depends on 'Database'
    // imports: 'Bookmodel' loads 'Model'
    $app->setUnits(array(
        'args_prepend' => array('lib/Database'),
        'load_prepend' => array('lib/Model'),
    ), array(
        array('model/BookModel')
    ));
    
    $app->setUnit('lib/Session', array('cache' => true));
    
    $app->setUnits(array(
        'args_prepend' => array('App', 'lib/Session'),
    ), array(
        array('book/BookCreate'),
        array('book/BookDelete', array('args' => array('model/BookModel'))),
        array('book/BookEdit', array('args' => array('model/BookModel'))),
        array('book/BookHome', array('args' => array('model/BookModel'))),
        array('book/BookStore', array('args' => array('model/BookModel'))),
        array('book/BookUpdate', array('args' => array('model/BookModel'))),
    ));
    
    $app->setUnits(array(
        'args_prepend' => array('lib/Session')
    ), array(
        array('CsrfGenerate'),
        array('CsrfValidate'),
    ));
    
    // Define pipes to handle data sanitization, CSRF generation
    $app->setPipes(array(
        // preppend component to all routes component
        'prepend' => array(
            'Sanitize',
            'CsrfGenerate', 
        ),
        // append component to all routes component
        'append' => array()
    ));
    
    // Define routes
    $app->setRoute(GET, '', array('pipe' => array('book/BookHome', 'ResponseCompression'))); // Default route
    
    // Define additional routes for env 'home' and 'create'
    $app->setRoutes(array( 
        'pipe_append' => array('ResponseCompression') // append component to route define in group
    ), array(
        array(GET, 'home', array('pipe' => array('book/BookHome'))),
        array(GET, 'create', array('pipe' => array('book/BookCreate'))),
        // Define a route for env editing a book, with an ID parameter (only digits allowed)
        array(GET, 'edit/{id:^\d+$}', array('pipe' => array('book/BookEdit')))
    ));
    
    // Define routes for env 'book/' prefix with CSRF validation 
    $app->setRoutes(array(
        'prefix' => 'book/',
        'pipe_prepend' => array('CsrfValidate'), // prepend component to route define in group
        'ignore' => array('CsrfGenerate')
    ), array(
        array(POST, 'store', array('pipe' => array('book/BookStore'))),
        array(POST, 'update', array('pipe' => array('book/BookUpdate'))),
        array(POST, 'delete', array('pipe' => array('book/BookDelete')))
    ));
    return $app;
}