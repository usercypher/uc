<?php
// uc.units.php

/**
 * ------------------------------------------------------------------------
 * Lib
 * ------------------------------------------------------------------------
 */

$app->setUnit('Lib_Database', array(
    'cache' => true
));

$app->setUnit('Lib_Session', array(
    'cache' => true
));

$app->setUnit('Lib_Curl', array(
    'cache' => true
));

$app->setUnit('Lib_GoogleApiGmail', array(
    'args' => array('Lib_Curl'),
    'cache' => true
));

$app->setUnit('Lib_Exception', array(
    'args' => array('App'),
    'cache' => true
));

/**
 * ------------------------------------------------------------------------
 * Pipe
 * ------------------------------------------------------------------------
 */

$group = array(
    'args_prepend' => array('App', 'Lib_Session')
);
$app->groupUnit($group, 'Pipe_CsrfGenerate');
$app->groupUnit($group, 'Pipe_CsrfValidate');

$app->groupUnit($group, 'Pipe_OtpGenerate');
$app->groupUnit($group, 'Pipe_OtpValidate');
$app->groupUnit($group, 'Pipe_OtpExist');
