<?php
// routes.php

// Define pipes to handle data sanitization, CSRF generation
$app->setPipes(array(
    // preppend component to all routes component
    'prepend' => array(
        'Pipe_Sanitize',
        'Pipe_CsrfGenerate', 
    ),
    // append component to all routes component
    'append' => array()
));

// Define route for cli empty method
$group = array( 

);
$app->addRoute($group, '', 'pipe-create/{class_path?}/{class?}', array('pipe' => array('Pipe_Cli_PipeCreate')));

// Define routes
$app->setRoute('GET', '', array('pipe' => array('Pipe_Book_Home', 'Pipe_ResponseCompression'))); // Default route

// Define additional routes for env 'home' and 'create'
$group = array( 
    'pipe_append' => array('Pipe_ResponseCompression') // append component to route define in group
);
$app->addRoute($group, 'GET', 'home', array('pipe' => array('Pipe_Book_Home')));
$app->addRoute($group, 'GET', 'create', array('pipe' => array('Pipe_Book_Create')));
// Define a route for env editing a book, with an ID parameter (only digits allowed)
$app->addRoute($group, 'GET', 'edit/{title_id:([a-zA-Z0-9-]+)-([0-9]+)}', array('pipe' => array('Pipe_Book_Edit')));
$app->addRoute($group, 'GET', 'edit/{id:[0-9]+}', array('pipe' => array('Pipe_Book_Edit')));

// Define routes for env 'book/' prefix with CSRF validation 
$group = array(
    'prefix' => 'book/',
    'pipe_prepend' => array('Pipe_CsrfValidate'), // prepend component to route define in group
    'ignore' => array('Pipe_CsrfGenerate')
);
$app->addRoute($group, 'POST', 'store', array('pipe' => array('Pipe_Book_Store')));
$app->addRoute($group, 'POST', 'update', array('pipe' => array('Pipe_Book_Update')));
$app->addRoute($group, 'POST', 'delete', array('pipe' => array('Pipe_Book_Delete')));