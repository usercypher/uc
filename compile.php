<?php
// compile.php

/**
 * ------------------------------------------------------------------------
 * Run Compilation Process
 * ------------------------------------------------------------------------
 * Bootstraps and compiles the app configuration for the given mode.
 */
compile(
    'dev',                  // Mode (dev, prod, etc.)
    'uc.php',               // Package/autoload file
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
    require('units.php');
    require('routes.php');
    return $app;
}

/**
 * Compile the app configuration.
 *
 * @param string $mode          Application mode
 * @param string $packageFile   Package or autoload file
 * @param string $settingsFile  Environment and ini settings file
 * @param string $configFile    Output configuration path
 */
function compile($mode, $packageFile, $settingsFile, $configFile) {
    require($packageFile);

    // Initialize app
    $app = new App();

    // Load and apply settings based on mode
    $settings = require($settingsFile);
    $app->setInis($settings['ini'][$mode]);
    $app->setEnvs($settings['env'][$mode]);

    // Register error and shutdown handlers
    set_error_handler(array($app, 'error'));
    register_shutdown_function(array($app, 'shutdown'));

    // Configure app units and routes
    $app = config($app);

    // Save compiled configuration
    $app->save($configFile);

    // Exit after compilation
    exit;
}
