<?php

/**
 * ------------------------------------------------------------------------
 * Pipe
 * ------------------------------------------------------------------------
 */
$app->unitSet('Example_Pipe_User', array('args' => array('App', 'App_Lib_Session', 'Shared_Lib_Translator')));
$app->unitSet('Example_Pipe_Game', array('args' => array('App', 'App_Lib_Session', 'Shared_Lib_Translator')));

$app->unitSet('Example_Pipe_Lang', array(
    'base' => 'Shared_Pipe_Lang',
    'args' => array(
        'App',
        'Shared_Lib_Translator',
        $app->unitArgData('example'),
        $app->unitArgData('en'),
        $app->unitArgData(array(
            'en', 'es', 'fr', 'de', 'pt'
        )),
        $app->unitArgData('src/Example/res/lang/'),
    )
));