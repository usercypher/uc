<?php
// index.php

$dir = __DIR__ . '/';

// Include core class
include($dir . 'core/App.php');
include($dir . 'core/Request.php');
include($dir . 'core/Response.php');

// Load environment and config
App::setEnvs(array(
    // Environment Settings
    'DIR' => $dir, // Set directory path
    'DIR_RELATIVE' => '/public/', // Ensures static resources are always relative to index.php

    'SHOW_ERRORS' => 1, // Enable or disable detailed error messages (1: Show, 0: Hide)

    'ROUTE_MAIN_FILE' => 'index.php',
    // Routing Configuration
    'ROUTE_REWRITE' => 0, // Enable or disable URL rewriting (1: Yes, 0: No).
    // If enabled, routing is handled via clean URLs (e.g., /home),

    /*
     * Web Server Configuration for URL Rewriting:
     *
     * Apache (.htaccess):
     *     RewriteEngine On
     *     RewriteBase /
     *     RewriteCond %{REQUEST_FILENAME} !-f
     *     RewriteCond %{REQUEST_FILENAME} !-d
     *     RewriteRule ^(.*)$ index.php [QSA,L]
     *
     * Nginx:
     *     location / {
     *         try_files $uri $uri/ /index.php?$query_string;
     *     }
     */

    // Database Configuration
    'DB_HOST' => '127.0.0.1', // Database host, usually 'localhost' or an IP address.
    'DB_NAME' => 'library', // Name of the database to connect to.
    'DB_USER' => 'root', // Username for database authentication.
    'DB_PASS' => '', // Password for the database user. Leave empty for no password.
));

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