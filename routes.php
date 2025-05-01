<?php
// routes.php

// Define pipes to handle data sanitization, CSRF generation
$app->setPipes(array(
    // preppend component to all routes component
    PREPEND => array(
        'pipe.Sanitize',
        'pipe.CsrfGenerate', 
    ),
    // append component to all routes component
    APPEND => array()
));

// Define routes
$app->setRoute(GET, '', array(PIPE => array('pipe.book.BookHome', 'pipe.ResponseCompression'))); // Default route

// Define additional routes for env 'home' and 'create'
$group = array( 
    PIPE_APPEND => array('pipe.ResponseCompression') // append component to route define in group
);
$app->addRoute($group, GET, 'home', array(PIPE => array('pipe.book.BookHome')));
$app->addRoute($group, GET, 'create', array(PIPE => array('pipe.book.BookCreate')));
// Define a route for env editing a book, with an ID parameter (only digits allowed)
$app->addRoute($group, GET, 'edit/{id:^\d+$}', array(PIPE => array('pipe.book.BookEdit')));

// Define routes for env 'book/' prefix with CSRF validation 
$group = array(
    PREFIX => 'book/',
    PIPE_PREPEND => array('pipe.CsrfValidate'), // prepend component to route define in group
    IGNORE => array('pipe.CsrfGenerate')
);
$app->addRoute($group, POST, 'store', array(PIPE => array('pipe.book.BookStore')));
$app->addRoute($group, POST, 'update', array(PIPE => array('pipe.book.BookUpdate')));
$app->addRoute($group, POST, 'delete', array(PIPE => array('pipe.book.BookDelete')));