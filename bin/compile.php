<?php

require str_replace('\\', '/', dirname(__FILE__)) . '/../uc.php';
require str_replace('\\', '/', dirname(__FILE__)) . '/../config.php';

function compile() {
    $app = new App();
    $app->init();

    set_error_handler(array($app, 'errorHandler'));

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
        'data' => array(),
        'compile' => array(),
        'unit_add' => array(),
        'unit_set' => array(),
        'route_set' => array(),
    );

    compile_scan_dir($app->dir('root', 'src'), $files, $exclude, $app);

    foreach (array('unit_add', 'unit_set', 'route_set', 'compile') as $phase) {
        usort($files[$phase], 'compile_usort');
    }

    require $app->dir('root', 'src/_unit_scan.php');

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
        $output->code = $app->env['sapi'] === 'cli' ? 1 : 500;
    } else {
        $ok = 1;
        foreach (array('unit_add', 'unit_set', 'route_set', 'compile') as $phase) {
            foreach ($files[$phase] as $file) {
                $result = compile_require_wrapper($file, $app, $input, $output);
                if ($result !== 1) {
                    $ok = $result;
                }
            }
        }

        if ($ok === 1) {
            $appStateFile = 'var/lib/app.state.dat';

            $app->save($appStateFile);

            $output->content .= 'File created: ' . $appStateFile . "\n";
        } else {
            $output->code = $app->env['sapi'] === 'cli' ? 1 : 500;
        }
    }

    $output->content .= "Tip: use " . ($app->env['sapi'] === 'cli' ? "--exclude=module1,module2" : "?exclude=module1,module2") . " to exclude modules from compilation.\n";

    $output->call($output->content, $output->code);

    $app->term();
    $input->term();
    $output->term();

    exit($app->env['sapi'] === 'cli' ? $output->code : 0);
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
            if (in_array($item, $exclude)) {
                continue;
            }

            $subhandle = opendir($path);

            if ($subhandle !== false) {
                while (($subitem = readdir($subhandle)) !== false) {
                    if ($subitem === '.' || $subitem === '..') {
                        continue;
                    }

                    $subpath = $path . '/' . $subitem;

                    if (is_file($subpath)) {
                        if (substr($subitem, -9) === '_data.php') {
                            $result['data'][basename(dirname($subpath))] = $app->data($subpath);
                        } elseif (substr($subitem, -12) === '_compile.php') {
                            $result['compile'][] = $subpath;
                        } elseif (substr($subitem, -13) === '_unit_add.php') {
                            $result['unit_add'][] = $subpath;
                        } elseif (substr($subitem, -13) === '_unit_set.php') {
                            $result['unit_set'][] = $subpath;
                        } elseif (substr($subitem, -14) === '_route_set.php') {
                            $result['route_set'][] = $subpath;
                        }
                    }
                }
                closedir($subhandle);
            }
            continue;
        }
    }

    closedir($handle);
}

function compile_usort($a, $b) {
    return strnatcmp(basename($a), basename($b));
}

compile();
