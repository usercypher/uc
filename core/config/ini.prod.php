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
    'error_reporting' => E_ALL & ~E_NOTICE & ~E_DEPRECATED, // Log only critical errors
    'log_errors' => 1, // Log errors
    'error_log' => 'error_log', // Log file path

    // Session Settings
    'session.use_only_cookies' => true, // Use cookies for sessions
    'session.use_cookies' => 'On', // Ensure cookies for sessions
    'session.use_trans_sid' => 'Off', // No session IDs in URL
    'session.cookie_httponly' => 'On', // Protect cookies from JS
    'session.cookie_lifetime' => 3600, // Cookie lifetime for production
    'session.gc_maxlifetime' => 3600, // Max session lifetime
    'session.gc_probability' => 1, // GC probability
    'session.gc_divisor' => 100, // GC divisor
    'session.cookie_secure' => 'On', // Secure cookies (production)
    'session.cookie_samesite' => 'Strict', // CSRF protection (production)

    // General Settings
    'default_charset' => 'UTF-8', // Charset UTF-8

    // Performance Settings
    'memory_limit' => '256M', // Increase memory limit for production
    'max_execution_time' => 30, // Max execution time (production)
);