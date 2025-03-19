<?php
// init.php

// Set the base directory path
$dir = __DIR__ . '/';

// Include core classes for application functionality
require($dir . 'core/App.php');
require($dir . 'core/Request.php');
require($dir . 'core/Response.php');

$mode = 'dev';

// Load environment variables and configuration settings
App::setInis(require($dir . 'config/ini.' . $mode . '.php'));
App::setEnvs(require($dir . 'config/env.' . $mode . '.php'));

App::setEnv('DIR', $dir);

// Initialize the app with Request and Response objects
$app = new App(array(
    'Request' => new Request, 
    'Response' => new Response
));