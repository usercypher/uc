<?php
// routes.php

// Define pipes to handle data sanitization and CSRF generation for all routes
$app->setPipes(array(
    // 'prepend' - these pipes will be applied to all routes before the route's specific pipes
    'prepend' => array(
        'Pipe_Sanitize',  // Data sanitization pipe applied before each route
        'Pipe_CsrfGenerate',  // CSRF token generation pipe applied before each route
    ),
    // 'append' - these pipes will be applied to all routes after the route's specific pipes
    'append' => array()  // No pipes are appended to the routes in this example
));

// Define a route for creating a pipe with optional class_path and class parameters in the URL
$group = array( 
    // No additional configurations for this route group
);
$app->addRoute($group, '', 'pipe-create/{class_path?}/{class?}', array('pipe' => array('Pipe_Cli_PipeCreate'), 'ignore' => array('--global')));

// Define the default route which triggers the 'Pipe_Book_Home' and 'Pipe_ResponseCompression' pipes
$app->setRoute('GET', '', array('pipe' => array('Pipe_Book_Home', 'Pipe_ResponseCompression'))); // This is the default route, triggered for a basic GET request

// Define additional routes for 'home' and 'create' using the 'GET' method
$group = array( 
    'pipe_append' => array('Pipe_ResponseCompression') // Append the 'Pipe_ResponseCompression' to the routes in this group
);
$app->addRoute($group, 'GET', 'home', array('pipe' => array('Pipe_Book_Home'))); // This route is for 'GET /home', triggers 'Pipe_Book_Home'
$app->addRoute($group, 'GET', 'create', array('pipe' => array('Pipe_Book_Create'))); // This route is for 'GET /create', triggers 'Pipe_Book_Create'

// Define a route for editing a book, with an ID parameter (title_id and ID are extracted with regex)
$app->addRoute($group, 'GET', 'edit/{title_id:([a-zA-Z0-9-]+)-([0-9]+)}', array('pipe' => array('Pipe_Book_Edit'))); // Route with a title_id and ID regex pattern for 'GET /edit/{title_id}-{id}'
$app->addRoute($group, 'GET', 'edit/{id:[0-9]+}', array('pipe' => array('Pipe_Book_Edit'))); // Fallback route for 'GET /edit/{id}' where the ID is numeric

// Define routes for the 'book/' prefix, with CSRF validation as a prerequisite for POST requests
$group = array(
    'prefix' => 'book/',  // Prefix 'book/' for all routes in this group
    'pipe_prepend' => array('Pipe_CsrfValidate'),  // Prepend CSRF validation pipe before route-specific pipes
    'ignore' => array('Pipe_CsrfGenerate')  // Ignore the 'Pipe_CsrfGenerate' for these routes, CSRF validation already handled by prepend
);
$app->addRoute($group, 'POST', 'store', array('pipe' => array('Pipe_Book_Store'))); // 'POST /book/store' triggers 'Pipe_Book_Store'
$app->addRoute($group, 'POST', 'update', array('pipe' => array('Pipe_Book_Update'))); // 'POST /book/update' triggers 'Pipe_Book_Update'
$app->addRoute($group, 'POST', 'delete', array('pipe' => array('Pipe_Book_Delete'))); // 'POST /book/delete' triggers 'Pipe_Book_Delete'
