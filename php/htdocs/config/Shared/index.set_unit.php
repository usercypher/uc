<?php

/**
 * ------------------------------------------------------------------------
 * Lib
 * ------------------------------------------------------------------------
 */

$group = array(
    'cache' => true
);
$app->groupUnit($group, 'Lib_Curl');
$app->groupUnit($group, 'Lib_Database');
$app->groupUnit($group, 'Lib_GoogleApiGmail', array('args' => array('Lib_Curl')));
$app->groupUnit($group, 'Lib_Html');
$app->groupUnit($group, 'Lib_Session');
$app->groupUnit($group, 'Lib_Standard');
$app->groupUnit($group, 'Lib_Translator');
$app->groupUnit($group, 'Lib_Cast_Standard');
$app->groupUnit($group, 'Lib_Cast_Db', array('args' => array('App', 'Lib_Database')));

/**
 * ------------------------------------------------------------------------
 * Pipe
 * ------------------------------------------------------------------------
 */

$app->setUnit('Pipe_ErrorHandler', array('args' => array('App')));

$group = array(
    'args_prepend' => array('App', 'Lib_Session')
);
$app->groupUnit($group, 'Pipe_CsrfGenerate');
$app->groupUnit($group, 'Pipe_CsrfValidate');

$app->groupUnit($group, 'Pipe_OtpGenerate');
$app->groupUnit($group, 'Pipe_OtpValidate');
$app->groupUnit($group, 'Pipe_OtpExist');
