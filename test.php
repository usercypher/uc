<?php

define('DS', '\\');

$path = 'path/path\\path/';

echo str_replace(array('/', '\\'), DS, $path) . "\n";
