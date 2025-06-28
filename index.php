<?php
// index.php

/**
 * ------------------------------------------------------------------------
 * Optional Profiling Setup
 * ------------------------------------------------------------------------
 * Uncomment to enable profiling via TickProfiler.
 */
// profiler('TickProfiler');

/**
 * Initialize and start the TickProfiler.
 *
 * @param string $name Profiler class and log filename prefix.
 * @return TickProfiler
 */
function profiler($name) {
    declare(ticks=1);
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
    'uc.php',               // Package file
    'settings.php',         // Environment and ini settings
    'var/data/app/config'   // Application configuration directory/file
);

/**
 * Application entry point.
 *
 * @param string $packageFile   File to require for package setup
 * @param string $settingsFile  Settings file with env and ini configurations
 * @param string $configFile    Application config file or directory to load
 */
function index($packageFile, $settingsFile, $configFile) {
    require($packageFile);

    // Create app instance
    $app = new App();
    $app->init();

    // Register error and shutdown handlers
    set_error_handler(array($app, 'error'));
    register_shutdown_function(array($app, 'shutdown'));

    // Load application configuration
    $app->load($configFile);

    // Load environment and ini settings
    $settings = require($settingsFile);
    $mode = $settings['mode'][basename(__FILE__)];
    $app->setInis($settings['ini'][$mode]);
    $app->setEnvs($settings['env'][$mode]);

    $input = input_from_environment();

    $app->setEnv('XMLHTTPREQUEST', isset($input->headers['x-requested-with']) && strtolower($input->headers['x-requested-with']) === 'xmlhttprequest');

    $output = $app->dispatch($input, new Output());

    // Send the response to the client
    switch ($input->source) {
        case 'cli':
            return $output->std($output->content, $output->stderr);
        case 'http':
            return $output->http();
        default:
            echo('Unknown input source:' . $input->source);
    }
}