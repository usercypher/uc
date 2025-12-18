<?php

/**
 * ------------------------------------------------------------------------
 * Auto-Add Units
 * ------------------------------------------------------------------------
 * Automatically scan and add units from the 'src/' directory.
 * Options:
 *  - 'max' => 2          // Max directory depth to scan (-1 = unlimited)
 *  - 'ignore' => [...]   // Patterns/files to ignore
 *  - 'dir_as_namespace' => true // Use directory structure as namespace prefix
 */
$app->autoAddUnit('src/', array());