<?php

$passed = 0;
$failed = 0;

$dir = $app->pathToSlash(dirname(__FILE__));
$dirTest = $dir . '/Test';
$module = basename($dir);

$output->content .= "Test '$module': Started.\n";

if ($handle = $app->dopen($dirTest)) {
    while ($file = $app->dread($handle)) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        try {
            $unit = $app->makeUnit(basename(substr($file, 0, -4)));
            $unit->call();
            $passed++;
            $output->content .= "Test '$module': [pass] " . basename($file) . "\n";
        } catch (Exception $e) {
            $failed++;
            $output->code = 1;
            $output->content .= "Test '$module': [fail] " . basename($file) . "\n";
            $output->content .= "  " . str_replace("\n", "\n  ", $e->getMessage()) . "\n";
        }
    }
    $app->dclose($handle);
}

$output->content .= "Test '$module': $passed passed, $failed failed\n";
