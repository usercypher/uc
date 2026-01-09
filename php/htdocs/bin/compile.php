<?php

require str_replace('\\', '/', dirname(__FILE__)) . '/../src/Framework/uc.php';
require str_replace('\\', '/', dirname(__FILE__)) . '/../config/settings.php';

function compile() {
    $app = new App();
    $app->init();

    $app->setEnv('DIR_ROOT', $app->dir(dirname(__FILE__)) . '/../');

    $settings = settings();
    $mode = $settings['mode'][basename(__FILE__)];

    foreach ($settings['ini'][$mode] as $key => $value) {
        $app->setIni($key, $value);
    }

    foreach ($settings['env'][$mode] as $key => $value) {
        $app->setEnv($key, $value);
    }

    $files = array(
        'add_unit' => array(),
        'set_unit' => array(),
        'set_route' => array(),
    );

    scan_dir($app->dirRoot('config'), $files);

    require str_replace('\\', '/', dirname(__FILE__)) . '/../config/auto_add_unit.php';

    foreach ($files['add_unit'] as $file) {
        require $file;
    }

    foreach ($files['set_unit'] as $file) {
        require $file;
    }

    foreach ($files['set_route'] as $file) {
        require $file;
    }

    $appStateFile = 'var/data/app.state.dat';

    $app->save($appStateFile);

    exit('File created: ' . $appStateFile . "\n");
}

function scan_dir($dir, &$result) {
    $handle = opendir($dir);

    if ($handle === false) {
        return;
    }

    while (($item = readdir($handle)) !== false) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $dir . '/' . $item;

        if (is_dir($path)) {
            scan_dir($path, $result);
            continue;
        }

        if (is_file($path)) {
            if (substr($item, -13) === '.add_unit.php') {
                $result['add_unit'][] = $path;
            } elseif (substr($item, -13) === '.set_unit.php') {
                $result['set_unit'][] = $path;
            } elseif (substr($item, -14) === '.set_route.php') {
                $result['set_route'][] = $path;
            }
        }
    }

    closedir($handle);
}

compile();
