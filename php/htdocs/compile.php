<?php

function compile() {
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

    require('config/scan.php');

    if ($files = glob($app->dirRoot('config/*/*.units.php'))) {
        foreach ($files as $file) {
            require($file);
        }
    }

    require('config/pipes.php');

    if ($files = glob($app->dirRoot('config/*/*.routes.php'))) {
        foreach ($files as $file) {
            require($file);
        }
    }

    $app->save('var/compiled/app.state');

    exit(0);
}

compile();