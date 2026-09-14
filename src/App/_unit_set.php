<?php

/**
 * ------------------------------------------------------------------------
 * Pipe
 * ------------------------------------------------------------------------
 */
$app->unitSet('App_Pipe_Index', array('args' => array('App', 'Shared_Lib_Translator')));

$app->unitSet('App_Pipe_Lang', array(
    'base' => 'Shared_Pipe_Lang',
    'args' => array(
        'App',
        'Shared_Lib_Translator',
        $app->unitArgData('app'),
        $app->unitArgData('en'),
        $app->unitArgData(array(
            'en', 'es', 'fr', 'de', 'pt'
        )),
        $app->unitArgData('src/App/res/lang/'),
    )
));


$app->unitSet('App_Lib_Session', array(
    'base' => 'Shared_Lib_Session',
    'args' => array(
        $app->unitArgData(array(
            'name' => $app->env['app_id'] . ':session'
        )),
    )
));