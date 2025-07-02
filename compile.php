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
    'settings.php',         // Environment and ini settings
    'var/data/app/config'   // Output config directory/file
);

/**
 * Configure the application by loading units and routes.
 *
 * @param App $app
 * @return App
 */
function config($app) {
    require('config' . DS . '.scan.php');

    if ($files = glob($app->getEnv('DIR_ROOT') . 'config' . DS . '*.units.php')) {
        foreach ($files as $file) {
            require($file);
        }
    }

    require('config' . DS . '.pipes.php');    

    if ($files = glob($app->getEnv('DIR_ROOT') . 'config' . DS . '*.routes.php')) {
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
 * @param string $settingsFile  Settings file with env and ini configurations
 * @param string $configFile    Output config file or directory to save
 */
function compile($packageFile, $settingsFile, $configFile) {
    require($packageFile);

    // Initialize app
    $app = new App();
    $app->init();

    // Register error and shutdown handlers
    set_error_handler(array($app, 'error'));
    register_shutdown_function(array($app, 'shutdown'));

    // Load and apply settings based on mode
    $settings = require($settingsFile);
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
