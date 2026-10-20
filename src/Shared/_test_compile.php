<?php

require dirname(__FILE__) . '/_test_init.php';

$passed = 0;
$failed = 0;

$dir = $testapp->pathToSlash(dirname(__FILE__));
$dirTest = $dir . '/Test';
$module = basename($dir);

$output->content .= "Test '$module': Started.\n";

if ($handle = $testapp->dopen($dirTest)) {
    while ($file = $testapp->dread($handle)) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        try {
            $unit = $testapp->unitMake(basename(substr($file, 0, -4)));
            $unit->call();
            $passed++;
            $output->content .= "Test '$module': [pass] " . basename($file) . "\n";
        } catch (Shared_Lib_Assert_Exception $e) {
            $failed++;
            $output->content .= "Test '$module': [fail] " . basename($file) . "\n";
            $output->content .= "  " . str_replace("\n", "\n  ", $e->getMessage()) . "\n";
        } catch (Throwable $e) {
            $result = $testapp->error(method_exists($e, 'getSeverity') ? $e->getSeverity() : 1, $e->getMessage(), $e->getFile(), $e->getLine(), array(
                'trace' => $testapp->env['error_display'] ? $e->getTrace() : array(),
                'accept' => isset($input->header['accept']) ? $input->header['accept'] : '',
                'header' => $output->header
            ));
            $failed++;
            $output->content .= "Test '$module': [error] " . basename($file) . "\n";
            $output->content .= "  " . get_class($e) . ": " . str_replace("\n", "\n  ", $result['content']) . "\n";
        }
    }
    $testapp->dclose($handle);
}

$output->content .= "Test '$module': $passed passed, $failed failed\n";

$testapp->term();

return $failed > 0 ? 0 : 1;
