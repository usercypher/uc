<?php

compile(
    'uc.php',
    'settings.php',
    'var/data/app/config'
);

function config($app) {
    require('config' . DS . 'scan.php');

    if ($files = glob($app->getEnv('DIR_ROOT') . 'config/*/*.units.php')) {
        foreach ($files as $file) {
            require($file);
        }
    }

    require('config' . DS . 'pipes.php');    

    if ($files = glob($app->getEnv('DIR_ROOT') . 'config/*/*.routes.php')) {
        foreach ($files as $file) {
            require($file);
        }
    }

    return $app;
}

function compile($packageFile, $settingsFile, $configFile) {
    require($packageFile);

    // Initialize app
    $app = new App();
    $app->init();

    // Set error handler
    set_error_handler(array($app, 'error'));

    // Load environment and ini settings
    $settings = require($app->dirRoot($settingsFile));
    $mode = $settings['mode'][basename(__FILE__)];
    $app->setInis($settings['ini'][$mode]);
    $app->setEnvs($settings['env'][$mode]);

    // Configure app units and routes
    $app = config($app);

    // Save compiled configuration
    $app->save($configFile);

    // Exit after compilation
    exit;
}
