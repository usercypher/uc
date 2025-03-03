<?php

/**
* PHP Configuration Settings
*
*/

return array(
    // Timezone
    'date.timezone' => 'Asia/Manila', // Set to your timezone

    // Error Reporting
    'display_errors' => 'Off', // Hide errors (production only)
    'display_startup_errors' => 'Off',
    'error_reporting' => E_ALL & ~E_NOTICE & ~E_DEPRECATED, // Log only critical errors
    'log_errors' => 1, // Log errors
    'error_log' => 'error_log', // Log file path

    // General Settings
    'default_charset' => 'UTF-8', // Charset UTF-8

    // Performance Settings
    'memory_limit' => '256M', // Increase memory limit for production
    'max_execution_time' => 30, // Max execution time (production)
);