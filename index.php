<?php
// index.php

// Set the base directory path
$dir = __DIR__ . '/';

// Include core classes for application functionality
include($dir . 'core/App.php');
include($dir . 'core/Request.php');
include($dir . 'core/Response.php');

// Load environment variables and configuration settings
App::setInis(include($dir . 'core/config/ini.dev.php'));
App::setEnvs(include($dir . 'core/config/env.prod.php'));
App::setEnv('DIR', $dir);

// Initialize the app with Request and Response objects
$app = new App(array(
    'Request' => new Request, 
    'Response' => new Response
));

// Auto-load classes from the 'src/' directory (max 1 class, ignore 'view' folder)
$app->autoSetClass('src/', array('max' => 1, 'ignore' => array('view')));

// Define extensions from the 'core/extension/' directory
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

// Load base classes (Controller, Model)
$app->loadClasses(array('Controller', 'Model'));

// Run the application
$app->run();
