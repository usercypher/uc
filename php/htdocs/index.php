<?php

// Uncomment to generate 'var/data/app.state.dat' or run 'php bin/compile.php' on terminal.
//require 'bin/compile.php';  // Generates settings and exits script

require 'src/Framework/uc.php';
require 'config/settings.php';

function index() {
    $app = new App();
    $app->init();

    $app->setEnv('DIR_ROOT', $app->dir(dirname(__FILE__)) . '/');

    $settings = settings();
    $mode = $settings['mode'][basename(__FILE__)];

    foreach ($settings['ini'][$mode] as $key => $value) {
        $app->setIni($key, $value);
    }

    foreach ($settings['env'][$mode] as $key => $value) {
        $app->setEnv($key, $value);
    }

    $app->load('var/data/app.state.dat');

    $input = $app->getEnv('SAPI') === 'cli' ? input_cli(new Input()) : input_http(new Input());
    if ($input->source !== 'cli' && !$app->getEnv('ROUTE_REWRITE')) {
        $app->setEnv('URL_ROUTE', $input->route . '?route=/');
        $input->route = isset($input->query['route']) ? $input->query['route'] : '/';
    }

    $app->setEnv('HANDLE_ERROR_DEFAULT_ACCEPT', isset($input->header['accept']) ? $input->header['accept'] : '');

    $output = $app->getEnv('SAPI') === 'cli' ? output_cli(new Output()) : output_http(new Output());
    $output->version = $input->version;

    list($input, $output) = $app->pipe($input, $output, $settings['handler']);

    $result = $app->resolveRoute($input->method, $input->route);

    $input->param = $result['param'];

    list($input, $output) = $app->pipe($input, $output, $result['handler']);

    if (isset($result['error'])) {
        trigger_error($result['error'], E_USER_WARNING);
    }

    $output->io($output->content, (int) ($input->source === 'cli' && $output->code > 0));

    if ($input->source === 'cli') {
        exit($output->code);
    }
}

index();
