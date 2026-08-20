<?php

require str_replace('\\', '/', dirname(__FILE__)) . '/../uc.php';

function compile() {
    $app = new App();
    $app->init();

    set_error_handler(array($app, 'handleError'));

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

    $appVerResult = $app->versionCompare($app->getEnv('UC', $app->version), $app->version);

    $errorContent = '';
    if ($appVerResult === -1) {
        $errorContent = 'Error: installed UC version (' . $app->version . ') is older than the required version (' . $app->getEnv('UC', $app->version) . '). Please update UC and try again.' . "\n";
    } elseif ($appVerResult === 1) {
        $errorContent = 'Warning: installed UC version (' . $app->version . ') is newer than the version this build was created for (' . $app->getEnv('UC', $app->version) . '). The build may still work, but compatibility issues are possible.' . "\n";
    }

    if ($appVerResult != 0) {
        if ($app->getEnv('SAPI') === 'cli') {
            fwrite(STDERR, $errorContent);
        } else {
            echo $errorContent;
        }

        if ($appVerResult === -1) {
            exit(1);
        }
    }

    $input = $app->getEnv('SAPI') === 'cli' ? new InputCli : new InputHttp;
    $input->init();

    $output = $app->getEnv('SAPI') === 'cli' ? new OutputCli : new OutputHttp;
    $output->init();

    $exclude = isset($input->query['exclude']) ? explode(',', $input->query['exclude']) : array();

    $files = array(
        'data' => array(),
        'add_unit' => array(),
        'set_unit' => array(),
        'set_route' => array(),
    );

    scan_dir($app->dir('ROOT', 'src'), $files);

    require str_replace('\\', '/', dirname(__FILE__)) . '/../src/_scan_units.php';

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
            if ($app->versionCompare($required_php, $current_php) === -1) {
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
            $result = $app->versionCompare($dataversion, $available);

            if ($result === -1) {
                $errors[] = "Version mismatch: folder '{$dirbasename}' requires '{$matadirbasename}' {$dataversion}, but found {$available}.\n";
            } else if ($result === 1) {
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

    $output->call($output->content, $errors ? 1 : 0);

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
