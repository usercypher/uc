<?php

function extension($app, $input, $output) {
    $error = $app->loadClass('Lib_Error');
    $error->init($app, $input, $output);

    $session = $app->loadClass('Lib_Session');
    $session->name('SESSION_ID');
    $session->start();
}

function settings() {
    return array(
        'mode' => array(
            'index.php' => 'dev',
            'compile.php' => 'dev',
        ),
        'env' => array(
            'dev' => array(
                // Environment Settings
                'DIR_WEB' => 'web/',
                'URL_WEB' => 'web/', // URL path for web access
                // Error Settings
                'ERROR_TEMPLATES' => array(
                    'text/plain' => 'res/uc/view/text.plain.php',
                    'text/html' => 'res/uc/view/text.html.php',
                    'application/json' => 'res/uc/view/application.json.php',
                ),
                'ERROR_NON_FATAL' => E_NOTICE | E_DEPRECATED | E_USER_NOTICE | E_USER_DEPRECATED, // set non fatal, it only logs (if log error is enabled)
                'ERROR_LOG_FILE' => 'error', // Error log file
                'SHOW_ERRORS' => 1, // Enable (1) or disable (0) detailed error messages
                'LOG_ERRORS' => 1, // Enable (1) or disable (0) error logging
                // Routing Configuration
                'ROUTE_FILE' => 'index.php', // file for index (usually server root), become useless when route rewrite is enable
                'ROUTE_REWRITE' => 0, // Enable or disable URL rewriting (1: Yes, 0: No).
                // If enabled, routing is handled via clean URLs (e.g., /home),
                /*
                 * Web Server Configuration for URL Rewriting:
                 *
                 * Apache (.htaccess):
                 *     RewriteEngine On
                 *     RewriteBase /
                 *     RewriteCond %{REQUEST_FILENAME} !-f
                 *     RewriteCond %{REQUEST_FILENAME} !-d
                 *     RewriteRule ^(.*)$ index.php [QSA,L]
                 *
                 * Nginx:
                 *     location / {
                 *         try_files $uri $uri/ /index.php?$query_string;
                 *     }
                 */
                // Logging Configuration
                'DIR_LOG' => 'var/log/',
                'DIR_LOG_TIMESTAMP' => 'var/data/',
                'LOG_SIZE_LIMIT_MB' => 5,
                'LOG_CLEANUP_INTERVAL_DAYS' => 1,
                'LOG_RETENTION_DAYS' => 7,
                'MAX_LOG_FILES' => 10,
                // Database Configuration
                'DB_HOST' => '127.0.0.1',
                'DB_PORT' => '3306',
                'DB_NAME' => 'library',
                'DB_USER' => 'root',
                'DB_PASS' => '',
                'DB_TIME' => '+08:00',
                /*
                 * Timezones
                 *
                 * -05:00 = Eastern Standard Time (EST)
                 * +08:00 = Philippine Standard Time (PST)
                 * +09:00 = Japan Standard Time (JST)
                 */
            ),
            'prod' => array(
                // Environment Settings
                'DIR_WEB' => 'web/',
                'URL_WEB' => 'web/',
                // Error Settings
                'ERROR_TEMPLATES' => array(
                    'text/plain' => 'res/uc/view/text.plain.php',
                    'text/html' => 'res/uc/view/text.html.php',
                    'application/json' => 'res/uc/view/application.json.php',
                ),
                'ERROR_NON_FATAL' => E_NOTICE | E_DEPRECATED | E_USER_NOTICE | E_USER_DEPRECATED,
                'ERROR_LOG_FILE' => 'error',
                'SHOW_ERRORS' => 0,
                'LOG_ERRORS' => 1,
                // Routing Configuration
                'ROUTE_FILE' => 'index.php',
                'ROUTE_REWRITE' => 0,
                // Logging Configuration
                'DIR_LOG' => 'var/log/',
                'DIR_LOG_TIMESTAMP' => 'var/data/',
                'LOG_SIZE_LIMIT_MB' => 5,
                'LOG_CLEANUP_INTERVAL_DAYS' => 1,
                'LOG_RETENTION_DAYS' => 7,
                'MAX_LOG_FILES' => 10,
                // Database Configuration
                'DB_HOST' => '127.0.0.1',
                'DB_PORT' => '3306',
                'DB_NAME' => 'library',
                'DB_USER' => 'root',
                'DB_PASS' => '',
                'DB_TIME' => '+08:00',
            ),
        ),
        'ini' => array(
            'dev' => array(
                // Timezone
                'date.timezone' => 'Asia/Manila', // Set to your timezone
                // Error Reporting
                'display_errors' => 1, // Display errors
                'display_startup_errors' => 1, // Display startup errors
                'error_reporting' => E_ALL, // Report all errors
                'log_errors' => 1, // Log errors
                // General Settings
                'default_charset' => 'UTF-8', // Charset UTF-8
                // Performance Settings
                'memory_limit' => '128M', // Increase memory limit
                'max_execution_time' => 7200, // Max execution time
            ),
            'prod' => array(
                // Timezone
                'date.timezone' => 'Asia/Manila',
                // Error Reporting
                'display_errors' => 0,
                'display_startup_errors' => 0,
                'error_reporting' => E_ALL & ~E_NOTICE & ~E_DEPRECATED,
                'log_errors' => 1,
                // General Settings
                'default_charset' => 'UTF-8',
                // Performance Settings
                'memory_limit' => '256M',
                'max_execution_time' => 30,
            ),
        )
    );
}