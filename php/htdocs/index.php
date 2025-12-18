<?php

// Uncomment to generate 'var/compiled/app.state' or run 'php compile.php' on terminal.
//require('compile.php');  // Generates config and exits script

function index() {
    require('uc.php');
    $app = new App();
    $app->init();

    require('uc.config.php');
    $config = config();
    $mode = $config['mode'][basename(__FILE__)];

    foreach ($config['ini'][$mode] as $key => $value) {
        $app->setIni($key, $value);
    }

    foreach ($config['env'][$mode] as $key => $value) {
        $app->setEnv($key, $value);
    }

    $app->load('var/compiled/app.state');

    $input = SAPI === 'cli' ? input_cli(new Input()) : input_http(new Input());

    $app->setEnv('URL_ROOT', (($input->getFrom($input->server, 'HTTPS', 'off') !== 'off') ? 'https' : 'http') . "://" . $input->getFrom($input->headers, 'host', '127.0.0.1') . '/');
    $app->setEnv('ERROR_ACCEPT', $input->getFrom($input->headers, 'accept', ''));

    $output = new Output();
    $output->code = SAPI === 'cli' ? 0 : 200;

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