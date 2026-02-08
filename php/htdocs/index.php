<?php

// Uncomment to generate 'var/data/app.state.dat' or run 'php bin/compile.php' on terminal.
//require 'bin/compile.php';  // Generates config and exits script

require 'uc.php';
require 'config.php';

function index() {
    $app = new App();
    $app->init();

    $app->setEnv('DIR_ROOT', $app->dir(dirname(__FILE__)) . '/');

    $config = config();
    $mode = $config['mode'][basename(__FILE__)];

    foreach ($config['ini'][$mode] as $key => $value) {
        $app->setIni($key, $value);
    }

    foreach ($config['env'][$mode] as $key => $value) {
        $app->setEnv($key, $value);
    }

    $app->load('var/data/app.state.dat');

    $input = $app->getEnv('SAPI') === 'cli' ? input_cli(new Input()) : input_http(new Input());
    if ($app->getEnv('SAPI') !== 'cli' && !$app->getEnv('ROUTE_REWRITE')) {
        $app->setEnv('URL_ROUTE', $input->route . '?route=/');
        $input->route = isset($input->query['route']) ? $input->query['route'] : '/';
    }

    $app->setEnv('HANDLE_ERROR_DEFAULT_CONTEXT', array(
        'ACCEPT' => isset($input->header['accept']) ? $input->header['accept'] : '',
        'HEADER' => array()
    ));

    $output = $app->getEnv('SAPI') === 'cli' ? output_cli(new Output()) : output_http(new Output());
    $output->version = $input->version;

    list($input, $output) = $app->pipe($input, $output, $config['handler']);

    $result = $app->resolveRoute($input->method, $input->route);

    if (isset($result['error'])) {
        $description = '';
        if ($result['error'] === 405) {
            $description = 'Method not allowed: ' . $input->method . ' ' . $input->route . '. allow: ' . $result['header']['allow'];
            $app->setEnv('HANDLE_ERROR_DEFAULT_CONTEXT', array(
                'ACCEPT' => isset($input->header['accept']) ? $input->header['accept'] : '',
                'HEADER' => $result['header']
            ));
            $output->header += $result['header'];
        } else {
            $description = 'Route not found: ' . $input->method . ' ' . $input->route;
        }
        trigger_error($result['error'] . '|' . $description, E_USER_WARNING);
    } else {
        $input->param = $result['param'];
        list($input, $output) = $app->pipe($input, $output, $result['handler']);
    }

    $output->io($output->content, (int) ($app->getEnv('SAPI') === 'cli' && $output->code > 0));

    if ($app->getEnv('SAPI') === 'cli') {
        exit($output->code);
    }
}

index();
