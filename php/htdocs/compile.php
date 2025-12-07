<?php

compile(
    'uc.php',
    'uc.config.php',
    'var/compiled/app.state'
);

function compile($coreFile, $coreConfigFile, $appStateFile) {
    require($coreFile);

    $app = new App();
    $app->init();

    require($coreConfigFile);

    $settings = settings();
    $mode = $settings['mode'][basename(__FILE__)];

    foreach ($settings['ini'][$mode] as $key => $value) {
        $app->setIni($key, $value);
    }

    foreach ($settings['env'][$mode] as $key => $value) {
        $app->setEnv($key, $value);
    }

    require('config/scan.php');

    if ($files = glob($app->getEnv('DIR_ROOT') . 'config/*/*.units.php')) {
        foreach ($files as $file) {
            require($file);
        }
    }

    require('config/pipes.php');

    if ($files = glob($app->getEnv('DIR_ROOT') . 'config/*/*.routes.php')) {
        foreach ($files as $file) {
            require($file);
        }
    }

    $app->save($appStateFile);

    exit;
}