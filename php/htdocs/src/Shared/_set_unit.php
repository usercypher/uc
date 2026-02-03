<?php

/**
 * ------------------------------------------------------------------------
 * Lib
 * ------------------------------------------------------------------------
 */

$group = array(
    'cache' => true
);
$app->groupUnit($group, 'Shared_Lib_Curl');
$app->groupUnit($group, 'Shared_Lib_Database');
$app->groupUnit($group, 'Shared_Lib_GoogleApiGmail', array('args' => array('Shared_Lib_Curl')));
$app->groupUnit($group, 'Shared_Lib_Html');
$app->groupUnit($group, 'Shared_Lib_Session');
$app->groupUnit($group, 'Shared_Lib_Standard');
$app->groupUnit($group, 'Shared_Lib_Translator');
$app->groupUnit($group, 'Shared_Lib_Cast_Standard');
$app->groupUnit($group, 'Shared_Lib_Cast_Db', array('args' => array('App', 'Shared_Lib_Database')));

/**
 * ------------------------------------------------------------------------
 * Pipe
 * ------------------------------------------------------------------------
 */

$app->setUnit('Shared_Pipe_ErrorHandler', array('args' => array('App')));

$group = array(
    'args_prepend' => array('App', 'Shared_Lib_Session')
);
$app->groupUnit($group, 'Shared_Pipe_CsrfGenerate');
$app->groupUnit($group, 'Shared_Pipe_CsrfValidate');

$app->groupUnit($group, 'Shared_Pipe_OtpGenerate');
$app->groupUnit($group, 'Shared_Pipe_OtpValidate');
$app->groupUnit($group, 'Shared_Pipe_OtpExist');
