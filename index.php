<?php

// Uncomment to generate 'var/lib/app.state.dat' or run ./compile.sh or compile.bat on terminal.
//require 'bin/compile.php';  // Generates config and exits script

require 'uc.php';
require 'config.php';

function index() {
    $app = new App();
    $app->init();

    set_error_handler(array($app, 'handleError'));

    config($app, basename(__FILE__));

    if ($app->versionCompare($app->env['uc'], $app->version) === -1) {
        $errorContent = 'Error: installed UC version (' . $app->version . ') is older than the required version (' . $app->env['uc'] . '). Please update UC and try again.' . "\n";
        if ($app->env['sapi'] === 'cli') {
            fwrite(STDERR, $errorContent);
        } else {
            header('content-type: text/plain');
            echo $errorContent;
        }

        exit(1);
    }

    $app->load('var/lib/app.state.dat');

    $input = $app->env['sapi'] === 'cli' ? new InputCli : new InputHttp;
    $input->init();

    if ($app->env['sapi'] !== 'cli' && !$app->env['route_rewrite']) {
        $app->env['url']['route'] = $app->env['url']['root'] . $input->route . '?route=';
        $input->route = isset($input->query['route']) ? $input->query['route'] : '';
    }

    $app->env['handle_error_context'] = array(
        'accept' => isset($input->header['accept']) ? $input->header['accept'] : '',
        'header' => array()
    );

    $output = $app->env['sapi'] === 'cli' ? new OutputCli : new OutputHttp;
    $output->init();
    $output->version = $input->version;

    list($input, $output) = $app->pipe($input, $output, $app->env['route_handler_global']);

    $result = $app->resolveRoute($input->method, $input->route);

    if (isset($result['error'])) {
        list($input, $output) = $app->pipe($input, $output, array_merge($app->env['route_handler_prepend'], $app->env['route_handler_append']));
        $description = '';
        if ($result['error'] === 405) {
            $description = 'Method not allowed: ' . $input->method . ' ' . $input->route . '. allow: ' . $result['header']['allow'];
            $app->env['handle_error_context'] = array(
                'accept' => isset($input->header['accept']) ? $input->header['accept'] : '',
                'header' => $result['header']
            );
            $output->header += $result['header'];
        } else {
            $description = 'Route not found: ' . $input->method . ' ' . $input->route;
        }
        user_error($result['error'] . '|' . $description, E_USER_WARNING);
    } else {
        $input->param = $result['param'];
        list($input, $output) = $app->pipe($input, $output, array_merge($app->env['route_handler_prepend'], $result['handler'], $app->env['route_handler_append']));
    }

    $output->call($output->content, (int) $output->code);

    $app->term();
    $input->term();
    $output->term();

    if ($app->env['sapi'] === 'cli') {
        exit($output->code);
    }
}

index();
