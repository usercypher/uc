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

    require('Lib_Exception.php');
    $exception = new Lib_Exception();
    $exception->args(array($app));
    $exception->init();

    // Load environment and ini settings
    $settings = require($settingsFile);
    $mode = $settings['mode'][basename(__FILE__)];
    $app->setInis($settings['ini'][$mode]);
    $app->setEnvs($settings['env'][$mode]);

    // Load application configuration
    $app->load($configFile);

    $input = SAPI === 'cli' ? input_cli(new Input()) : input_http(new Input());

    $app->setEnv('URL_ROOT', (($input->getFrom($input->server, 'HTTPS', 'off') !== 'off') ? 'https' : 'http') . "://" . $input->getFrom($input->headers, 'host', '127.0.0.1') . '/');
    $app->setEnv('ACCEPT', strtolower($input->getFrom($input->headers, 'accept', '')));

    $output = new Output();
    $output->code = SAPI === 'cli' ? 0 : 200;

    $output = $app->dispatch($input, $output);

    // Send the response to the client
    switch ($input->source) {
        case 'cli':
            $output->std($output->content, $output->code > 0);
            exit($output->code);
        case 'http':
            return $output->http();
        default:
            echo('Unknown input source:' . $input->source);
    }
}