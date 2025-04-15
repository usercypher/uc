<?php
// index.php

// Uncomment this line during initial setup to compile and generate configurations
// Alternatively, you can run 'php uc.compile.php' from the terminal to generate the config and exit the script
//require('uc.compile.php');  // This generates config and exits the script after saving

$configFile = 'var/data/app.config';
$mode = 'prod';

// After the configuration has been generated, you can just load the package and continue
require('uc.package.php');

// Create and initialize the application in the 'dev' environment
$app = app($mode);

// Load the already saved configuration from the compiled file
$app->loadConfig($configFile);  // This will use the previously compiled configuration

// Load any base classes (like Model) required for the app
$app->loadClasses(array('Model'));

// Dispatch the application and get the response
$response = $app->dispatch();

// Send the response to the client
$response->send();
