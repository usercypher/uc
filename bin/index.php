<?php

chdir(dirname(__FILE__));

if (isset($argv)) {
    array_splice($argv, 1, 0, 'cli');
    $argc++;
}

include str_replace('\\', '/', dirname(__FILE__)) . '/../index.php';
