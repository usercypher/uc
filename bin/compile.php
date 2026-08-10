<?php

require str_replace('\\', '/', dirname(__FILE__)) . '/../uc.php';

function compile() {
    $app = new App();
    $app->init();

    $app->setEnv('DIR_ROOT', $app->dirToUnix(dirname(__FILE__)) . '/../');

    $config = $app->data($app->dir('ROOT', 'config.data.php'), array(
        'app' => $app
    ));

    $mode = $config['mode'][basename(__FILE__)];

    foreach ($config['ini'][$mode] as $key => $value) {
        $app->setIni($key, $value);
    }

    foreach ($config['env'][$mode] as $key => $value) {
        $app->setEnv($key, $value);
    }

    $input = $app->getEnv('SAPI') === 'cli' ? input_cli(new Input()) : input_http(new Input());
    $output = $app->getEnv('SAPI') === 'cli' ? output_cli(new Output()) : output_http(new Output());
    $exclude = isset($input->query['exclude']) ? explode(',', $input->query['exclude']) : array();

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
        $dirbasename = basename(dirname($file));
        if (in_array($dirbasename, $exclude)) {
            continue;
        }
        $datas[$dirbasename] = require $file;
    }

    $errors = array();
    foreach ($datas as $dirbasename => $data) {
        if (isset($data['php'])) {
            $required_php = $data['php'];
            $current_php = PHP_VERSION;
            $r_parts = explode('.', $required_php);
            $c_parts = explode('.', $current_php);
            while (count($r_parts) < 3) { $r_parts[] = '0'; }
            while (count($c_parts) < 3) { $c_parts[] = '0'; }
            $r0 = intval($r_parts[0]); $r1 = intval($r_parts[1]); $r2 = intval($r_parts[2]);
            $c0 = intval($c_parts[0]); $c1 = intval($c_parts[1]); $c2 = intval($c_parts[2]);
            if ($c0 < $r0 || ($c0 === $r0 && $c1 < $r1)) {
                $errors[] = "PHP version error: folder '{$dirbasename}' requires PHP {$required_php}, but {$current_php} is installed.\n";
            }
        }        
        if (isset($data['ext']) && is_array($data['ext'])) {
            foreach ($data['ext'] as $ext) {
                if (!extension_loaded($ext)) {
                    $errors[] = "Extension error: folder '{$dirbasename}' requires extension '{$ext}', but it is not loaded.\n";
                }
            }
        }
        if (!isset($data['use']) || !is_array($data['use'])) {
            continue;
        }
        foreach ($data['use'] as $matadirbasename => $dataversion) {
            if (!isset($datas[$matadirbasename]['version'])) {
                $errors[] = "Use error: folder '{$dirbasename}' requires '{$matadirbasename}' (version {$dataversion}), but '{$matadirbasename}' is " . (in_array($matadirbasename, $exclude) ? "excluded" : "missing") . ".\n";
                continue;
            }
            $available = $datas[$matadirbasename]['version'];
            $r_parts = explode('.', $dataversion);
            $a_parts = explode('.', $available);
            while (count($r_parts) < 3) { $r_parts[] = '0'; }
            while (count($a_parts) < 3) { $a_parts[] = '0'; }
            $r0 = intval($r_parts[0]); $r1 = intval($r_parts[1]); $r2 = intval($r_parts[2]);
            $a0 = intval($a_parts[0]); $a1 = intval($a_parts[1]); $a2 = intval($a_parts[2]);
            if ($a0 < $r0) {
                $errors[] = "Version mismatch: folder '{$dirbasename}' requires '{$matadirbasename}' major {$r0}, minor >= {$r1}, but found {$available}.\n";
            } else if ($a0 === $r0) {
                if ($a1 < $r1) {
                    $errors[] = "Version mismatch: folder '{$dirbasename}' requires '{$matadirbasename}' major {$r0}, minor >= {$r1}, but found {$available}.\n";
                }
            } else {
                $output->content .= "Warning: folder '{$dirbasename}' uses '{$matadirbasename}' version {$available}, which is newer than required version {$dataversion}.\n";
            }
        }
    }
    if ($errors) {
        foreach ($errors as $error) {
            $output->content .= $error;
        }
    } else {
        foreach ($files['add_unit'] as $file) {
            require $file;
        }

        foreach ($files['set_unit'] as $file) {
            require $file;
        }

        foreach ($files['set_route'] as $file) {
            require $file;
        }

        $appStateFile = 'var/lib/app.state.dat';

        $app->save($appStateFile);

        $output->content .= 'File created: ' . $appStateFile . "\n";
    }

    $output->content .= "\nTip: use --exclude=module1,module2 to exclude modules from compilation.\n";

    $output->io($output->content);

    $app->term();
    $input->term();
    $output->term();

    exit($errors ? 1 : 0);
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
