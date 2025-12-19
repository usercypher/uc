<?php

function compile() {
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

    require('config/auto_add_unit.php');

    if ($files = glob($app->dirRoot('config/*/*.add_unit.php'))) {
        foreach ($files as $file) {
            require($file);
        }
    }

    if ($files = glob($app->dirRoot('config/*/*.set_unit.php'))) {
        foreach ($files as $file) {
            require($file);
        }
    }

    require('config/set_pipes.php');

    if ($files = glob($app->dirRoot('config/*/*.set_route.php'))) {
        foreach ($files as $file) {
            require($file);
        }
    }

    $app->save('var/compiled/app.state');

    exit(0);
}

compile();