<?php
// index.php

// Uncomment this line during initial setup to compile and generate configurations
// Alternatively, you can run 'php uc.compile.php' from the terminal to generate the config and exit the script
//require('uc.compile.php');  // This generates config and exits the script after saving

require('uc.package.php');
index(app('dev'), 'var/data/app.config'); 

function index($app, $configFile) {
    $app->loadConfig($configFile);
    
    $response = $app->dispatch();
    $response->send();
}
