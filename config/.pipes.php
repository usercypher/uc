<?php
// .pipes.php

/**
 * ------------------------------------------------------------------------
 * Global Pipes
 * ------------------------------------------------------------------------
 * These are applied to all routes automatically.
 */
$app->setPipes(array(
    'prepend' => array(
        'Pipe_Sanitize',         // Sanitize all incoming data
        'Pipe_CsrfGenerate',     // Generate CSRF token for GET requests
    ),
    'append' => array(
        // No global append pipes
    )
));