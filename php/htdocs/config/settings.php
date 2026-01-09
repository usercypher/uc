<?php

function settings() {
    $env = array(
        // Environment Settings
        'DIR_WEB' => 'web/',
        'URL_WEB' => '/web/', // URL path for web access
        // Error Settings
        'ERROR_TEMPLATES' => array(
            'text/plain' => 'res/Shared/view/error/text.plain.php',
            'text/html' => 'res/Shared/view/error/text.html.php',
            'application/json' => 'res/Shared/view/error/application.json.php',
        ),
        'ERROR_NON_FATAL' => E_NOTICE | E_DEPRECATED | E_USER_NOTICE | E_USER_DEPRECATED, // set non fatal, it only logs (if log error is enabled)
        'ERROR_LOG_FILE' => 'error.log', // Error log file
        'ERROR_MAX_LENGTH' => 4096, // Error string max length
        'SHOW_ERRORS' => 1, // Enable (1) or disable (0) detailed error messages
        'LOG_ERRORS' => 1, // Enable (1) or disable (0) error logging
        // Logging Configuration
        'DIR_LOG' => 'var/log/',
        'DIR_LOG_TIMESTAMP' => 'var/data/',
        'LOG_SIZE_LIMIT_MB' => 5,
        'LOG_CLEANUP_INTERVAL_DAYS' => 1,
        'LOG_RETENTION_DAYS' => 7,
        'MAX_LOG_FILES' => 10,
    );

    $ini = array(
        // Timezone
        'date.timezone' => 'Asia/Manila',
        // Error Reporting
        'display_errors' => 1,
        'display_startup_errors' => 1,
        'error_reporting' => E_ALL,
        'log_errors' => 1,
        // General Settings
        'default_charset' => 'UTF-8',
        // Performance Settings
        'memory_limit' => '128M',
        'max_execution_time' => 7200,
    );

    return array(
        'handler' => array('Pipe_ErrorHandler', 'Pipe_Init'),
        'mode' => array(
            'index.php' => 'dev',
            'compile.php' => 'dev',
        ),
        'env' => array(
            'dev' => array_merge($env, array(
                'ROUTE_REWRITE' => 0,

                'DB_HOST' => '127.0.0.1',
                'DB_PORT' => '3306',
                'DB_NAME' => 'library',
                'DB_USER' => 'root',
                'DB_PASS' => '',
                'DB_TIME' => '+08:00',
            )),
            'prod' => array_merge($env, array(
                'ROUTE_REWRITE' => 0,

                'SHOW_ERRORS' => 0,

                'DB_HOST' => '127.0.0.1',
                'DB_PORT' => '3306',
                'DB_NAME' => 'library',
                'DB_USER' => 'root',
                'DB_PASS' => '',
                'DB_TIME' => '+08:00',
            )),
        ),
        'ini' => array(
            'dev' => array_merge($ini, array(
                
            )),
            'prod' => array_merge($ini, array(
                'display_errors' => 0,
                'display_startup_errors' => 0,
                'error_reporting' => E_ALL & ~E_NOTICE & ~E_DEPRECATED,
                'log_errors' => 1,

                'memory_limit' => '256M',
                'max_execution_time' => 30,
            )),
        )
    );
}