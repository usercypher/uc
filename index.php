<?php
// index.php

// Uncomment for initial setup to generate config or run 'php compile.php' to generate config
//require('compile.php');  // Generates config and exits script

// Run the application
index(
    'dev',
    'uc.php',
    'settings.php',
    'var/data/app.config'
); 

function index($mode, $packageFile, $settingsFile, $configFile) {
    require($packageFile);

    $app = new App(array(new Request, new Response));

    require($settingsFile);
    $settings = settings();

    $app->setInis($settings['ini'][$mode]);
    $app->setEnvs($settings['env'][$mode]);

    $app->init();

    set_error_handler(array($app, 'error'));
    register_shutdown_function(array($app, 'shutdown'));

    // Load app configuration and dispatch the response
    $app->loadConfig($configFile);
    $response = $app->dispatch();
    $response->send();
}
