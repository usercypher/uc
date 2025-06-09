<?php
// .scan.php

/**
 * ------------------------------------------------------------------------
 * Auto-load Units
 * ------------------------------------------------------------------------
 * Automatically scan and load units from the 'src/app/' directory.
 * Options:
 *  - 'max' => 2          // Max directory depth to scan (-1 = unlimited)
 *  - 'ignore' => [...]   // Patterns/files to ignore
 *  - 'dir_as_namespace' => true // Use directory structure as namespace prefix
 */
$app->scanUnits('src'.DS.'app'.DS, array());