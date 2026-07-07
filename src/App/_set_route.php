<?php

// GET
// ==========
$group = array(
    
);

$app->groupRoute($group, 'GET', '', array(
    'App_Pipe_Index'
));

$app->groupRoute($group, 'GET', 'phpinfo', array(
    'App_Pipe_Phpinfo'
));
