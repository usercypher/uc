<?php
// index.php

// Set the base directory path
$dir = __DIR__ . '/';

// Include core classes for application functionality
require($dir . 'core/App.php');
require($dir . 'core/Request.php');
require($dir . 'core/Response.php');

// Load environment variables and configuration settings
App::setInis(require($dir . 'config/ini.dev.php'));
App::setEnvs(require($dir . 'config/env.dev.php'));

App::setEnv('DIR', $dir);

// Initialize the app with Request and Response objects
$app = new App(array(
    'Request' => new Request, 
    'Response' => new Response
));

// [CONFIG] start
// Auto-load classes from the 'src/' directory (max 1 class, ignore 'view' folder)
$app->autoSetClass('src/', array('max' => 1, 'ignore' => array('view')));

// Define base from the 'core/base/' directory
$app->setClasses(array(
    'path' => 'core/base/'
), array(
    array('Controller'), 
    array('Model')
));

// Set up the 'Database' class with caching enabled
$app->setClass('Database', array('cache' => true));

// Define classes and inject dependencies (e.g., 'BookModel' depends on 'Database')
$app->setClasses(array(
    'args' => array('Database')
), array(
    array('BookModel')
));

// Define the 'BookController' class, which depends on 'BookModel'
$app->setClass('BookController', array('args' => array('BookModel')));

// Define middlewares to handle session, CSRF generation, and data sanitization
$app->setMiddlewares(array(
    'AppCleanerMiddleware', 
    'SessionMiddleware', 
    'CsrfGenerateMiddleware', 
    'SanitizeMiddleware'
));

// Define routes
$app->setRoute('GET', '', 'index', array('controller' => 'BookController')); // Default route

// Define additional routes for 'home' and 'create' actions in 'BookController'
$app->setRoutes(array(), array(
    array('GET', 'home', 'index', array('controller' => 'BookController')),
    array('GET', 'create', 'create', array('controller' => 'BookController'))
));

// Define a route for editing a book, with an ID parameter (only digits allowed)
$app->setRoutes(array('controller' => 'BookController'), array(
    array('GET', 'edit/{id:^\d+$}', 'edit')
));

// Define routes for 'book/' prefix with CSRF validation middleware
$app->setRoutes(array(
    'prefix' => 'book/',
    'controller' => 'BookController',
    'middleware' => array('CsrfValidateMiddleware'),
    'ignore' => array('CsrfGenerateMiddleware')
), array(
    array('POST', 'create', 'store'),
    array('POST', 'update', 'update'),
    array('POST', 'delete', 'delete')
));
// [CONFIG] end

// Uncomment to save the configuration once, then load it on subsequent runs.
// The configuration is saved using 'saveConfig' and loaded with 'loadConfig'.
// After the initial run, you can comment out 'saveConfig' and just use 'loadConfig'.
// When using 'loadConfig', remove or comment out the [CONFIG] body to avoid redundant config setting.
//$app->saveConfig('app.config'); // Save the configuration once
//$app->loadConfig('app.config'); // Load the saved configuration on subsequent runs

// Load base classes (Controller, Model)
$app->loadClasses(array('Controller', 'Model'));

// Run the application
$app->run();
