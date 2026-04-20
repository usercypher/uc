<?php

/**
 * ------------------------------------------------------------------------
 * Auto-Add Units
 * ------------------------------------------------------------------------
 * Automatically scan and add units from the 'src/' directory.
 * Options:
 *  - 'max' => 2          // Max directory depth to scan (-1 = unlimited)
 *  - 'ignore' => [...]   // files/folder to ignore, exact match or strpos by prefixing '?' (eg. '?file')
 *  - 'dir_as_namespace' => true // Use directory structure as namespace prefix
 */
$app->autoAddUnit('src/', array(
    'ignore' => array('config', 'res', basename(__FILE__), '?_set_route.php', '?_set_unit.php')
));
