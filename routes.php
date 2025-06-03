<?php
// routes.php

/**
 * ------------------------------------------------------------------------
 * Global Links
 * ------------------------------------------------------------------------
 * These are applied to all routes automatically.
 */
$app->setLinks(array(
    'prepend' => array(
        'Link_Sanitize',         // Sanitize all incoming data
        'Link_CsrfGenerate',     // Generate CSRF token for GET requests
    ),
    'append' => array(
        // No global append links
    )
));


/**
 * ------------------------------------------------------------------------
 * CLI Link Route
 * ------------------------------------------------------------------------
 * Handles dynamic CLI piping through optional route params.
 */
$group = array(
    'ignore' => array('--global')
);

$app->groupRoute($group, '', 'link/{option?}/{class?}', array(
    'link' => array('Link_Cli_Link'),
));

$app->groupRoute($group, '', 'route', array(
    'link' => array('Link_Cli_Route'),
));



/**
 * ------------------------------------------------------------------------
 * Default Route (Homepage)
 * ------------------------------------------------------------------------
 */
$app->setRoute('GET', '', array(
    'link' => array('Link_Book_Home', 'Link_ResponseCompression')
));


/**
 * ------------------------------------------------------------------------
 * Basic GET Routes: /home, /create, /edit
 * ------------------------------------------------------------------------
 * These routes share response compression via group link_append.
 */
$group = array(
    'link_append' => array('Link_ResponseCompression')
);

// GET /home
$app->groupRoute($group, 'GET', 'home', array(
    'link' => array('Link_Book_Home')
));

// GET /create
$app->groupRoute($group, 'GET', 'create', array(
    'link' => array('Link_Book_Create')
));

// GET /edit/{title-id}
$app->groupRoute($group, 'GET', 'edit/{title_id:([a-zA-Z0-9-]+)-([0-9]+)}', array(
    'link' => array('Link_Book_Edit')
));

// GET /edit/{id}
$app->groupRoute($group, 'GET', 'edit/{id:[0-9]+}', array(
    'link' => array('Link_Book_Edit')
));


/**
 * ------------------------------------------------------------------------
 * POST Routes (Protected with CSRF Validation)
 * ------------------------------------------------------------------------
 * All routes under 'book/' prefix require CSRF token validation.
 */
$group = array(
    'prefix' => 'book/',
    'link_prepend' => array('Link_CsrfValidate'),
    'ignore' => array('Link_CsrfGenerate')
);

// POST /book/store
$app->groupRoute($group, 'POST', 'store', array(
    'link' => array('Link_Book_Store')
));

// POST /book/update
$app->groupRoute($group, 'POST', 'update', array(
    'link' => array('Link_Book_Update')
));

// POST /book/delete
$app->groupRoute($group, 'POST', 'delete', array(
    'link' => array('Link_Book_Delete')
));
