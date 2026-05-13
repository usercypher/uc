<?php

require str_replace('\\', '/', dirname(__FILE__)) . '/../uc.php';
require str_replace('\\', '/', dirname(__FILE__)) . '/../config.php';

function compile() {
    $app = new App();
    $app->init();

    $app->setEnv('DIR_ROOT', $app->dirToUnix(dirname(__FILE__)) . '/../');

    $config = config($app);
    $mode = $config['mode'][basename(__FILE__)];

    foreach ($config['ini'][$mode] as $key => $value) {
        $app->setIni($key, $value);
    }

    foreach ($config['env'][$mode] as $key => $value) {
        $app->setEnv($key, $value);
    }

    $files = array(
        'data' => array(),
        'add_unit' => array(),
        'set_unit' => array(),
        'set_route' => array(),
    );

    scan_dir($app->dir('ROOT', 'src'), $files);

    require str_replace('\\', '/', dirname(__FILE__)) . '/../src/_auto_add_unit.php';

    $datas = array();
    foreach ($files['data'] as $file) {
        $datas[basename(dirname($file))] = require $file;
    }

    foreach ($datas as $dirbasename => $data) {
        if (!isset($data['use']) || !is_array($data['use'])) {
            continue;
        }
        foreach ($data['use'] as $matadirbasename => $dataversion) {
            if (!isset($datas[$matadirbasename]['version'])) {
                echo "Use error: folder '{$dirbasename}' requires '{$matadirbasename}' (version {$dataversion}), but '{$matadirbasename}' is missing.\n";
                exit(1);
            }
            $available = $datas[$matadirbasename]['version'];
            $r_parts = explode('.', $dataversion);
            $a_parts = explode('.', $available);
            while (count($r_parts) < 3) { $r_parts[] = '0'; }
            while (count($a_parts) < 3) { $a_parts[] = '0'; }
            $r0 = intval($r_parts[0]); $r1 = intval($r_parts[1]); $r2 = intval($r_parts[2]);
            $a0 = intval($a_parts[0]); $a1 = intval($a_parts[1]); $a2 = intval($a_parts[2]);
            if ($a0 < $r0) {
                echo "Version mismatch: folder '{$dirbasename}' requires '{$matadirbasename}' major {$r0}, minor >= {$r1}, but found {$available}.\n";
                exit(1);
            }
            if ($a0 > $r0) {
                continue;
            }
            if ($a1 < $r1) {
                echo "Version mismatch: folder '{$dirbasename}' requires '{$matadirbasename}' major {$r0}, minor >= {$r1}, but found {$available}.\n";
                exit(1);
            }
        }
    }

    foreach ($files['add_unit'] as $file) {
        require $file;
    }

    foreach ($files['set_unit'] as $file) {
        require $file;
    }

    foreach ($files['set_route'] as $file) {
        require $file;
    }

    $appStateFile = 'var/dat/app.state.dat';

    $app->save($appStateFile);

    $app->term();

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
            if (substr($item, -9) === '_data.php') {
                $result['data'][] = $path;
            } elseif (substr($item, -13) === '_add_unit.php') {
                $result['add_unit'][] = $path;
            } elseif (substr($item, -13) === '_set_unit.php') {
                $result['set_unit'][] = $path;
            } elseif (substr($item, -14) === '_set_route.php') {
                $result['set_route'][] = $path;
            }
        }
    }

    closedir($handle);
}

compile();
