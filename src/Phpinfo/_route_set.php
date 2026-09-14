<?php

// GET
// ==========
$group = array(
    
);

$app->routeGroup($group, 'GET', 'phpinfo', array(
    'Phpinfo_Pipe_Index'
));

$app->routeGroup($group, 'GET', 'phpinfo/opcache', array(
    'Phpinfo_Pipe_Opcache'
));
