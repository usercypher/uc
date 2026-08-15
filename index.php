<?php

// Uncomment to generate 'var/lib/app.state.dat' or run ./compile.sh or compile.bat on terminal.
//require 'bin/compile.php';  // Generates config and exits script

require 'uc.php';

function index() {
    $app = new App();
    $app->init();

    $app->setEnv('DIR_ROOT', $app->dirToUnix(dirname(__FILE__)) . '/');

    $config = $app->data($app->dir('ROOT', 'config.data.php'), array(
        'app' => $app
    ));

    $mode = $config['mode'][basename(__FILE__)];

    foreach ($config['ini'][$mode] as $key => $value) {
        $app->setIni($key, $value);
    }

    foreach ($config['env'][$mode] as $key => $value) {
        $app->setEnv($key, $value);
    }

    $app->load('var/lib/app.state.dat');

    $input = $app->getEnv('SAPI') === 'cli' ? new InputCli : new InputCgi;
    $input->init();

    $app->setEnv('ACCEPT_LANGUAGE', isset($input->header['accept-language']) ? $input->header['accept-language'] : 'en');
    if ($app->getEnv('SAPI') !== 'cli' && !$app->getEnv('ROUTE_REWRITE')) {
        $app->setEnv('URL_ROUTE', $app->getEnv('URL_ROOT', '/') . $input->route . '?route=/');
        $input->route = isset($input->query['route']) ? $input->query['route'] : '/';
    }

    $app->setEnv('HANDLE_ERROR_DEFAULT_CONTEXT', array(
        'ACCEPT' => isset($input->header['accept']) ? $input->header['accept'] : '',
        'HEADER' => array()
    ));

    $output = $app->getEnv('SAPI') === 'cli' ? new OutputCli : new OutputCgi;
    $output->init();
    $output->version = $input->version;

    list($input, $output) = $app->pipe($input, $output, $config['global']);

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
        list($input, $output) = $app->pipe($input, $output, array_merge($config['prepend'], $result['handler'], $config['append']));
    }

    $output->io($output->content, (int) $output->code);

    $app->term();
    $input->term();
    $output->term();

    if ($app->getEnv('SAPI') === 'cli') {
        exit($output->code);
    }
}

index();
