<?php
// uc.compile.php

require('uc.package.php');
compile(config(app('dev')), 'var/data/app.config');

function compile($app, $configFile) {
    $app->saveConfig($configFile);
    exit;
}

function config($app) {
    require('units.php');
    require('routes.php');
    return $app;
}