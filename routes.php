<?php
// routes.php

// Define pipes to handle data sanitization, CSRF generation
$app->setPipes(array(
    // preppend component to all routes component
    'prepend' => array(
        'pipe.Sanitize',
        'pipe.CsrfGenerate', 
    ),
    // append component to all routes component
    'append' => array()
));

// Define routes
$app->setRoute('GET', '', array('pipe' => array('pipe.book.BookHome', 'pipe.ResponseCompression'))); // Default route

// Define additional routes for env 'home' and 'create'
$group = array( 
    'pipe_append' => array('pipe.ResponseCompression') // append component to route define in group
);
$app->addRoute($group, 'GET', 'home', array('pipe' => array('pipe.book.BookHome')));
$app->addRoute($group, 'GET', 'create', array('pipe' => array('pipe.book.BookCreate')));
// Define a route for env editing a book, with an ID parameter (only digits allowed)
$app->addRoute($group, 'GET', 'edit/{id:^\d+$}', array('pipe' => array('pipe.book.BookEdit')));

// Define routes for env 'book/' prefix with CSRF validation 
$group = array(
    'prefix' => 'book/',
    'pipe_prepend' => array('pipe.CsrfValidate'), // prepend component to route define in group
    'ignore' => array('pipe.CsrfGenerate')
);
$app->addRoute($group, 'POST', 'store', array('pipe' => array('pipe.book.BookStore')));
$app->addRoute($group, 'POST', 'update', array('pipe' => array('pipe.book.BookUpdate')));
$app->addRoute($group, 'POST', 'delete', array('pipe' => array('pipe.book.BookDelete')));