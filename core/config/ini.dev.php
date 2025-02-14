<?php

/**
* PHP Configuration Settings
*
*/

return array(
    // Timezone
    'date.timezone' => 'Asia/Manila', // Set to your timezone

    // Error Reporting
    'display_errors' => 'On', // Display errors (dev only)
    'display_startup_errors' => 'On', // Display startup errors
    'error_reporting' => E_ALL, // Report all errors
    'log_errors' => 1, // Log errors
    'error_log' => 'error_log', // Log file path

    // General Settings
    'default_charset' => 'UTF-8', // Charset UTF-8

    // Performance Settings
    'memory_limit' => '128M', // Increase memory limit for dev
    'max_execution_time' => 7200, // Max execution time for dev
);