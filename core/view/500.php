<?php

$head_title = '500 Internal Server Error';
$title = '500';
$description = 'Something went wrong on our end. Please try again later.';

include(App::path('root', 'core/view/template/error.php'));
?>