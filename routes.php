<?php
// routes.php

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


/**
 * ------------------------------------------------------------------------
 * CLI Pipe Route
 * ------------------------------------------------------------------------
 * Handles dynamic CLI piping through optional route params.
 */
$group = array();
$app->groupRoute($group, '', 'pipe/{option?}/{class?}', array(
    'pipe' => array('Pipe_Cli_Pipe'),
    'ignore' => array('--global')
));


/**
 * ------------------------------------------------------------------------
 * Default Route (Homepage)
 * ------------------------------------------------------------------------
 */
$app->setRoute('GET', '', array(
    'pipe' => array('Pipe_Book_Home', 'Pipe_ResponseCompression')
));


/**
 * ------------------------------------------------------------------------
 * Basic GET Routes: /home, /create, /edit
 * ------------------------------------------------------------------------
 * These routes share response compression via group pipe_append.
 */
$group = array(
    'pipe_append' => array('Pipe_ResponseCompression')
);

// GET /home
$app->groupRoute($group, 'GET', 'home', array(
    'pipe' => array('Pipe_Book_Home')
));

// GET /create
$app->groupRoute($group, 'GET', 'create', array(
    'pipe' => array('Pipe_Book_Create')
));

// GET /edit/{title-id}
$app->groupRoute($group, 'GET', 'edit/{title_id:([a-zA-Z0-9-]+)-([0-9]+)}', array(
    'pipe' => array('Pipe_Book_Edit')
));

// GET /edit/{id}
$app->groupRoute($group, 'GET', 'edit/{id:[0-9]+}', array(
    'pipe' => array('Pipe_Book_Edit')
));


/**
 * ------------------------------------------------------------------------
 * POST Routes (Protected with CSRF Validation)
 * ------------------------------------------------------------------------
 * All routes under 'book/' prefix require CSRF token validation.
 */
$group = array(
    'prefix' => 'book/',
    'pipe_prepend' => array('Pipe_CsrfValidate'),
    'ignore' => array('Pipe_CsrfGenerate')
);

// POST /book/store
$app->groupRoute($group, 'POST', 'store', array(
    'pipe' => array('Pipe_Book_Store')
));

// POST /book/update
$app->groupRoute($group, 'POST', 'update', array(
    'pipe' => array('Pipe_Book_Update')
));

// POST /book/delete
$app->groupRoute($group, 'POST', 'delete', array(
    'pipe' => array('Pipe_Book_Delete')
));
