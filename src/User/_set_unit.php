<?php

/**
 * ------------------------------------------------------------------------
 * Repo
 * ------------------------------------------------------------------------
 */
$group = array(
    'args_prepend' => array('App', 'Shared_Lib_Database', 'Shared_Lib_Cast_Standard', 'Shared_Lib_Cast_Db'),
    'load_prepend' => array('Shared_Lib_DatabaseHelper')
);
$app->groupUnit($group, 'User_Repo', array(
    'args' => array($app->argUnitData('default'), $app->argUnitData('user'))
));

/**
 * ------------------------------------------------------------------------
 * Pipe
 * ------------------------------------------------------------------------
 */

$app->setUnit('User_Pipe_Lang', array(
    'base' => 'Shared_Pipe_Lang',
    'args' => array(
        'App',
        'Shared_Lib_Translator',
        $app->argUnitData('user'),
        $app->argUnitData('en'),
        $app->argUnitData(array(
            'en', 'es', 'fr', 'de', 'pt'
        )),
        $app->argUnitData('src/User/res/lang/'),
    )
));

$group = array(
    'args_prepend' => array('App', 'App_Lib_Session')
);

$app->groupUnit($group, 'User_Pipe_Store', array('args' => array('User_Repo', 'Shared_Lib_Translator')));
$app->groupUnit($group, 'User_Pipe_Update', array('args' => array('User_Repo', 'Shared_Lib_Translator')));
$app->groupUnit($group, 'User_Pipe_Delete', array('args' => array('User_Repo', 'Shared_Lib_Translator')));
$app->groupUnit($group, 'User_Pipe_SessionUnset', array('args' => array()));
$app->groupUnit($group, 'User_Pipe_SessionVerify', array('args' => array('User_Repo', 'Shared_Lib_Translator')));
$app->groupUnit($group, 'User_Pipe_IsAuth', array('args' => array()));
$app->groupUnit($group, 'User_Pipe_IsNotAuth', array('args' => array()));

/**
 * ------------------------------------------------------------------------
 * Pipe/Cli
 * ------------------------------------------------------------------------
 */
 
$group = array(
    'args_prepend' => array('App')
);
$app->groupUnit($group, 'User_Pipe_Cli_Create', array('args' => array()));
