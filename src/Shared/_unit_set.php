<?php

/**
 * ------------------------------------------------------------------------
 * Lib
 * ------------------------------------------------------------------------
 */

$group = array(
    'cache' => true
);
$app->unitGroup($group, 'Shared_Lib_Curl');
$app->unitGroup($group, 'Shared_Lib_Database');
$app->unitGroup($group, 'Shared_Lib_GoogleApiGmail', array('args' => array('Shared_Lib_Curl')));
$app->unitGroup($group, 'Shared_Lib_Html');
$app->unitGroup($group, 'Shared_Lib_Session');
$app->unitGroup($group, 'Shared_Lib_Standard');
$app->unitGroup($group, 'Shared_Lib_Translator');
$app->unitGroup($group, 'Shared_Lib_Cast_Standard', array('args' => array('Shared_Lib_Translator')));
$app->unitGroup($group, 'Shared_Lib_Cast_Db', array('args' => array('App', 'Shared_Lib_Database', 'Shared_Lib_Translator')));

/**
 * ------------------------------------------------------------------------
 * Pipe
 * ------------------------------------------------------------------------
 */

$group = array(
    'args_prepend' => array('App'),
    'cache' => true,
);

$app->unitGroup($group, 'Shared_Pipe_ErrorHandler');
$app->unitGroup($group, 'Shared_Pipe_Lang', array('args' => array('Shared_Lib_Translator')));
$app->unitGroup($group, 'Shared_Pipe_Header');
$app->unitGroup($group, 'Shared_Pipe_Redirect');
$app->unitGroup($group, 'Shared_Pipe_Theme');

$group = array(
    'args_prepend' => array('App', 'Shared_Lib_Session')
);
$app->unitGroup($group, 'Shared_Pipe_SessionInit');
$app->unitGroup($group, 'Shared_Pipe_SessionTokenGenerate');
$app->unitGroup($group, 'Shared_Pipe_SessionTokenValidate');
$app->unitGroup($group, 'Shared_Pipe_ExtractFlash');

$app->unitGroup($group, 'Shared_Pipe_OtpGenerate');
$app->unitGroup($group, 'Shared_Pipe_OtpValidate');
$app->unitGroup($group, 'Shared_Pipe_OtpExist');
