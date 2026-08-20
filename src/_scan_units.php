<?php

/**
 * ------------------------------------------------------------------------
 * Scan Units
 * ------------------------------------------------------------------------
 * Automatically scan and add units from the 'src/' directory.
 * Options:
 *  - 'max' => 2                 // Max directory depth to scan (-1 = unlimited)
 *  - 'ignore' => [...]          // Files/folders to ignore; patterns: ^ (prefix), $ (suffix), * (contains), = or empty (exact)
 *  - 'dir_as_namespace' => true // Use directory structure as namespace prefix
 */
$app->scanUnits('src/', array(
    'ignore' => array(basename(__FILE__), '*/res/', '$/_set_route.php', '$/_set_unit.php', '$/_data.php')
));
