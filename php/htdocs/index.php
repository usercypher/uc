<?php

// Uncomment to generate configuration or run compile script.
//require('compile.php');  // Generates config and exits script

define('SCRIPT_START_TIME', microtime(true));
define('SCRIPT_START_MEMORY', memory_get_usage());

index(
    'uc.php',
    'uc.config.php',
    'var/compiled/app.state'
);

function index($coreFile, $coreConfigFile, $appStateFile) {
    require($coreFile);

    $app = new App();
    $app->init();

    require($app->dirRoot($coreConfigFile));

    $settings = settings();
    $mode = $settings['mode'][basename(__FILE__)];
    $app->setInis($settings['ini'][$mode]);
    $app->setEnvs($settings['env'][$mode]);

    $app->load($appStateFile);

    $input = SAPI === 'cli' ? input_cli(new Input()) : input_http(new Input());

    $app->setEnv('URL_ROOT', (($input->getFrom($input->server, 'HTTPS', 'off') !== 'off') ? 'https' : 'http') . "://" . $input->getFrom($input->headers, 'host', '127.0.0.1') . '/');
    $app->setEnv('ACCEPT', strtolower($input->getFrom($input->headers, 'accept', '')));

    $app = extension($app);

    $output = new Output();
    $output->code = SAPI === 'cli' ? 0 : 200;

    $output = $app->dispatch($input, $output);

    switch ($input->source) {
        case 'cli':
            $output->std($output->content, $output->code > 0);
            exit($output->code);
        case 'http':
            $output->headers['set-cookie'][] = 'php_exec_time_ms=' . number_format((microtime(true) - SCRIPT_START_TIME) * 1000, 2) . '; Max-Age=3600; Path=/';
            $output->headers['set-cookie'][] = 'php_memory_usage_kb=' . number_format((memory_get_usage() - SCRIPT_START_MEMORY) / 1024, 2) . '; Max-Age=3600; Path=/';

            $output->http($output->content);
        default:
            echo('Unknown input source:' . $input->source);
    }
}