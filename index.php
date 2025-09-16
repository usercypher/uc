<?php

define('START_TIME', microtime(true));
define('START_MEMORY', memory_get_usage());

// Uncomment to enable profiling via TickProfiler.
//profiler('TickProfiler');

function profiler($name) {
    declare(ticks=1);
    require($name . '.php');
    $tickProfiler = new TickProfiler();
    $tickProfiler->init($name . '.log');
    return $tickProfiler;
}

// Uncomment to generate configuration or run compile script.
//require('compile.php');  // Generates config and exits script

index(
    'uc.php',
    'settings.php',
    'extension.php',
    'var/data/app/config'
);

function index($packageFile, $settingsFile, $extensionFile, $configFile) {
    require($packageFile);

    $app = new App();
    $app->init();

    set_error_handler(array($app, 'error'));

    $settings = require($app->dirRoot($settingsFile));
    $mode = $settings['mode'][basename(__FILE__)];
    $app->setInis($settings['ini'][$mode]);
    $app->setEnvs($settings['env'][$mode]);

    $app->load($configFile);

    $input = SAPI === 'cli' ? input_cli(new Input()) : input_http(new Input());

    $app->setEnv('URL_ROOT', (($input->getFrom($input->server, 'HTTPS', 'off') !== 'off') ? 'https' : 'http') . "://" . $input->getFrom($input->headers, 'host', '127.0.0.1') . '/');
    $app->setEnv('ACCEPT', strtolower($input->getFrom($input->headers, 'accept', '')));

    $app = require($app->dirRoot($extensionFile));

    $output = new Output();
    $output->code = SAPI === 'cli' ? 0 : 200;

    $output = $app->dispatch($input, $output);

    switch ($input->source) {
        case 'cli':
            $output->std($output->content, $output->code > 0);
            exit($output->code);
        case 'http':
            setcookie('app_exec_time_ms', number_format((microtime(true) - START_TIME) * 1000, 2), time() + 3600, '/');
            setcookie('app_memory_kb', number_format((memory_get_usage() - START_MEMORY) / 1024, 2), time() + 3600, '/');            

            return $output->http();
        default:
            echo('Unknown input source:' . $input->source);
    }
}