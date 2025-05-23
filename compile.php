<?php
// compile.php

// Run the compilation
compile(
    'dev',
    'uc.php',
    'settings.php',
    'var/data/app/config'
);

function config($app) {
    require('units.php');
    require('routes.php');
    return $app;
}

function compile($mode, $packageFile, $settingsFile, $configFile) {
    require($packageFile);

    $app = new App(array(new Request, new Response));

    $settings = require($settingsFile);

    $app->setInis($settings['ini'][$mode]);
    $app->setEnvs($settings['env'][$mode]);

    set_error_handler(array($app, 'error'));
    register_shutdown_function(array($app, 'shutdown'));

    // Configure the app with units and routes
    $app = config($app);

    $app->saveConfig($configFile);

    exit;
}
