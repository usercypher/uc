<?php

// Uncomment to generate 'var/compiled/app.state' or run 'php compile.php' on terminal.
//require('compile.php');  // Generates config and exits script

function index() {
    require('uc.php');
    $app = new App();
    $app->init();

    require('uc.config.php');
    $settings = settings();
    $mode = $settings['mode'][basename(__FILE__)];

    foreach ($settings['ini'][$mode] as $key => $value) {
        $app->setIni($key, $value);
    }

    foreach ($settings['env'][$mode] as $key => $value) {
        $app->setEnv($key, $value);
    }

    $input = SAPI === 'cli' ? input_cli(new Input()) : input_http(new Input());

    $app->setEnv('URL_ROOT', (($input->getFrom($input->server, 'HTTPS', 'off') !== 'off') ? 'https' : 'http') . "://" . $input->getFrom($input->headers, 'host', '127.0.0.1') . '/');
    $app->setEnv('ERROR_ACCEPT', strtolower($input->getFrom($input->headers, 'accept', '')));

    $output = new Output();
    $output->code = SAPI === 'cli' ? 0 : 200;

    $app->load('var/compiled/app.state');

    extension($app, $input, $output);

    $output = $app->dispatch($input, $output);

    switch ($input->source) {
        case 'cli':
            $output->std($output->content, $output->code > 0);
            exit($output->code);
        case 'http':
            return $output->http($output->content);
        default:
            echo('Unknown input source:' . $input->source);
    }
}

index();