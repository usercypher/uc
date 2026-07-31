<?php

// GET
// ==========
$group = array(
    
);

$app->groupRoute($group, 'GET', 'phpinfo', array(
    'Phpinfo_Pipe_Index'
));

$app->groupRoute($group, 'GET', 'phpinfo/opcache', array(
    'Phpinfo_Pipe_Opcache'
));
