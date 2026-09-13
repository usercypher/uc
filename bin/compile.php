<?php

require str_replace('\\', '/', dirname(__FILE__)) . '/../uc.php';
require str_replace('\\', '/', dirname(__FILE__)) . '/../config.php';

function compile() {
    $app = new App();
    $app->init();

    set_error_handler(array($app, 'handleError'));

    config($app, basename(__FILE__));

    $appVerResult = $app->versionCompare($app->env['uc'], $app->version);

    if ($appVerResult === -1) {
        $errorContent = 'Error: installed UC version (' . $app->version . ') is older than the required version (' . $app->env['uc'] . '). Please update UC and try again.' . "\n";
        if ($app->env['sapi'] === 'cli') {
            fwrite(STDERR, $errorContent);
        } else {
            header('content-type: text/plain');
            echo $errorContent;
        }

        exit(1);
    }

    $errors = array();
    $warnings = array();

    if ($appVerResult === 1) {
        $warnings[] = 'Warning: installed UC version (' . $app->version . ') is newer than the version this build was created for (' . $app->env['uc'] . '). The build may still work, but compatibility issues are possible.' . "\n";
    }

    $input = $app->env['sapi'] === 'cli' ? new InputCli : new InputHttp;
    $input->init();

    $output = $app->env['sapi'] === 'cli' ? new OutputCli : new OutputHttp;
    $output->init();

    $output->header['content-type'] = 'text/plain';

    $exclude = isset($input->query['exclude']) ? explode(',', $input->query['exclude']) : array();

    $files = array(
        'test' => array(),
        'data' => array(),
        'add_unit' => array(),
        'set_unit' => array(),
        'set_route' => array(),
    );

    compile_scan_dir($app->dir('root', 'src'), $files, $exclude, $app);

    require $app->dir('root', 'src/_scan_units.php');

    $datas = $files['data'];

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
                $warnings[] = "Warning: folder '{$dirbasename}' uses '{$matadirbasename}' version {$available}, which is newer than required version {$dataversion}.\n";
            }
        }
    }

    foreach ($warnings as $warning) {
        $output->content .= $warning;
    }

    if ($errors) {
        foreach ($errors as $error) {
            $output->content .= $error;
        }
    } else {
        foreach (array('add_unit', 'set_unit', 'set_route', 'test') as $files_temp) {
            foreach ($files[$files_temp] as $file) {
                compile_require_wrapper($file, $app, $input, $output);
            }
        }

        if (1 > $output->code) {
            $appStateFile = 'var/lib/app.state.dat';

            $app->save($appStateFile);

            $output->content .= 'File created: ' . $appStateFile . "\n";
        }
    }

    $output->content .= "Tip: use " . ($app->env['sapi'] === 'cli' ? "--exclude=module1,module2" : "?exclude=module1,module2") . " to exclude modules from compilation.\n";

    $output->code = $app->env['sapi'] === 'cli' ? ($errors ? 1 : 0) : 200;

    $output->call($output->content, $output->code);

    $app->term();
    $input->term();
    $output->term();

    exit($errors ? 1 : 0);
}

function compile_require_wrapper($file, $app, $input, $output) {
    return require $file;
}

function compile_scan_dir($dir, &$result, &$exclude, $app) {
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
            compile_scan_dir($path, $result, $exclude, $app);
            continue;
        }

        if (is_file($path)) {
            if (in_array(basename(dirname($path)), $exclude)) {
                continue;
            }
            if (substr($item, -9) === '_test.php') {
                $result['test'][] = $path;
            } elseif (substr($item, -9) === '_data.php') {
                $result['data'][basename(dirname($path))] = $app->data($path);
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
