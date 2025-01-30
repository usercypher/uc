<?php
// index.php
$dir = __DIR__ . '/';

// Include core class
include($dir . 'core/App.php');
include($dir . 'core/Request.php');
include($dir . 'core/Response.php');

// Load environment and config
App::setEnvs(include($dir . 'core/config/env.dev.php'));
App::setEnv('DIR', $dir);

$app = new App(new Request, new Response);
// Define files and extensions
$app->setFiles('core/extension/', array('ExtController', 'ExtModel'));
$app->autoSetFiles('src/', array('max' => 1, 'ignore' => array('view')));

// Define classes and dependencies
$app->setClasses(array('cache' => true), array('Database'));
$app->setClasses(array('args' => array('Database')), array('BookModel'));
$app->setClass('BookController', array('args' => array('BookModel')));

// Define middlewares
$app->setMiddlewares(array('SessionMiddleware', 'CsrfGenerateMiddleware', 'SanitizeMiddleware'));

// Define routes
$app->setRoute('GET', '', 'index', array('controller' => 'BookController'));
$app->setRoutes(array(), array(
    array('GET', 'home', 'index', array('controller' => 'BookController')),
    array('GET', 'create', 'create', array('controller' => 'BookController'))
));
$app->setRoutes(array('controller' => 'BookController'), array(
    array('GET', 'edit/{id:^\d+$}', 'edit')
));
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

// Load extensions
$app->loadClasses(array('ExtController', 'ExtModel'));

// Execute the app
$app->run();