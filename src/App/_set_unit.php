<?php

/**
 * ------------------------------------------------------------------------
 * Pipe
 * ------------------------------------------------------------------------
 */
$app->setUnit('App_Pipe_Index', array('args' => array('App', 'Shared_Lib_Translator')));

$app->setUnit('App_Pipe_Lang', array(
    'base' => 'Shared_Pipe_Lang',
    'args' => array(
        'App',
        'Shared_Lib_Translator',
        $app->argUnitData('app'),
        $app->argUnitData('en'),
        $app->argUnitData(array(
            'en', 'es', 'fr', 'de', 'pt'
        )),
        $app->argUnitData('src/App/res/lang/'),
    )
));


$app->setUnit('App_Lib_Session', array(
    'base' => 'Shared_Lib_Session',
    'args' => array(
        $app->argUnitData(array(
            'name' => $app->env['app_id'] . ':session'
        )),
    )
));