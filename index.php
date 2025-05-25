<?php
// index.php

/**
 * ------------------------------------------------------------------------
 * Optional Profiling Setup
 * ------------------------------------------------------------------------
 * Uncomment to enable profiling via TickProfiler.
 */
// declare(ticks=1);
// profiler('TickProfiler');

/**
 * Initialize and start the TickProfiler.
 *
 * @param string $name Profiler class and log filename prefix.
 * @return TickProfiler
 */
function profiler($name) {
    require($name . '.php');
    $tickProfiler = new TickProfiler();
    $tickProfiler->init($name . '.log');
    return $tickProfiler;
}

/**
 * ------------------------------------------------------------------------
 * Initial Setup (Optional)
 * ------------------------------------------------------------------------
 * Uncomment to generate configuration or run compile script.
 */
// require('compile.php');  // Generates config and exits script

/**
 * ------------------------------------------------------------------------
 * Run Application
 * ------------------------------------------------------------------------
 * Bootstraps the application with environment and configuration files.
 */
index(
    'dev',                  // Mode (e.g., dev, prod)
    'uc.php',               // Package/autoload file
    'settings.php',         // Environment and ini settings
    'var/data/app/config'   // Application configuration directory/file
);

/**
 * Application entry point.
 *
 * @param string $mode          Application mode (dev, prod, etc.)
 * @param string $packageFile   File to require for package/autoload setup
 * @param string $settingsFile  Settings file with env and ini configurations
 * @param string $configFile    Application config file or directory to load
 */
function index($mode, $packageFile, $settingsFile, $configFile) {
    require($packageFile);

    // Create app instance with request and response handlers
    $app = new App(array(new Request, new Response));

    // Load environment and ini settings
    $settings = require($settingsFile);
    $app->setInis($settings['ini'][$mode]);
    $app->setEnvs($settings['env'][$mode]);

    // Set error and shutdown handlers
    set_error_handler(array($app, 'error'));
    register_shutdown_function(array($app, 'shutdown'));

    // Load application configuration and run the app
    $app->load($configFile);
    $response = $app->run();

    // Send the response to the client
    $response->send();
}
