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

    $input = SAPI === 'cli' ? input_cli(new Input()) : input_http(new Input());

    $app->setEnv('ROUTE_FILE', substr((($pos = strpos($input->uri, '?')) !== false) ? substr($input->uri, 0, $pos) : $input->uri, 1));
    $app->setEnv('ERROR_ACCEPT', $input->getFrom($input->header, 'accept', ''));

    $output = new Output();
    $output->code = SAPI === 'cli' ? 0 : 200;

    $app->load('var/data/app.state.dat');

    list($input, $output) = $app->process($input, $output);

    $output->version = $input->version;

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