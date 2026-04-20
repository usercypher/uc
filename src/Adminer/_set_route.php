<?php

$group = array();

$methods = array('GET','POST','PUT','DELETE','PATCH','OPTIONS','HEAD');

foreach ($methods as $method) {
    $app->groupRoute($group, $method, 'adminer', array(
        'Adminer_Pipe_Index'
    ));
}
