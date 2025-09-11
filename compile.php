<?php
// compile.php

/**
 * ------------------------------------------------------------------------
 * Run Compilation Process
 * ------------------------------------------------------------------------
 * Bootstraps and compiles the app configuration for the given mode.
 */
compile(
    'uc.php',               // Package file
    'var/data/app/config'   // Application configuration file
);

/**
 * Configure the application by loading units and routes.
 *
 * @param App $app
 * @return App
 */
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

/**
 * Compile the app configuration.
 *
 * @param string $packageFile   File to require for package setup
 * @param string $configFile    Output config file or directory to save
 */
function compile($packageFile, $configFile) {
    require($packageFile);

    // Initialize app
    $app = new App();
    $app->init();

    // Set error handler
    set_error_handler(array($app, 'error'));

    // Load environment and ini settings
    $settings = require($app->dirRoot('settings.php'));
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
