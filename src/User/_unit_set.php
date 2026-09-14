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
$app->unitGroup($group, 'User_Repo', array(
    'args' => array($app->unitArgData('default'), $app->unitArgData('user'))
));

/**
 * ------------------------------------------------------------------------
 * Pipe
 * ------------------------------------------------------------------------
 */

$app->unitSet('User_Pipe_Lang', array(
    'base' => 'Shared_Pipe_Lang',
    'args' => array(
        'App',
        'Shared_Lib_Translator',
        $app->unitArgData('user'),
        $app->unitArgData('en'),
        $app->unitArgData(array(
            'en', 'es', 'fr', 'de', 'pt'
        )),
        $app->unitArgData('src/User/res/lang/'),
    )
));

$group = array(
    'args_prepend' => array('App', 'App_Lib_Session')
);

$app->unitGroup($group, 'User_Pipe_Store', array('args' => array('User_Repo', 'Shared_Lib_Translator')));
$app->unitGroup($group, 'User_Pipe_Update', array('args' => array('User_Repo', 'Shared_Lib_Translator')));
$app->unitGroup($group, 'User_Pipe_Delete', array('args' => array('User_Repo', 'Shared_Lib_Translator')));
$app->unitGroup($group, 'User_Pipe_SessionUnset', array('args' => array()));
$app->unitGroup($group, 'User_Pipe_SessionVerify', array('args' => array('User_Repo', 'Shared_Lib_Translator')));
$app->unitGroup($group, 'User_Pipe_IsAuth', array('args' => array()));
$app->unitGroup($group, 'User_Pipe_IsNotAuth', array('args' => array()));

/**
 * ------------------------------------------------------------------------
 * Pipe/Cli
 * ------------------------------------------------------------------------
 */
 
$group = array(
    'args_prepend' => array('App')
);
$app->unitGroup($group, 'User_Pipe_Cli_Create', array('args' => array()));
