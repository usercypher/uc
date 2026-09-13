<?php

/**
 * ------------------------------------------------------------------------
 * Pipe
 * ------------------------------------------------------------------------
 */
$app->setUnit('Example_Pipe_User', array('args' => array('App', 'App_Lib_Session', 'Shared_Lib_Translator')));
$app->setUnit('Example_Pipe_Game', array('args' => array('App', 'App_Lib_Session', 'Shared_Lib_Translator')));

$app->setUnit('Example_Pipe_Lang', array(
    'base' => 'Shared_Pipe_Lang',
    'args' => array(
        'App',
        'Shared_Lib_Translator',
        $app->argUnitData('example'),
        $app->argUnitData('en'),
        $app->argUnitData(array(
            'en', 'es', 'fr', 'de', 'pt'
        )),
        $app->argUnitData('src/Example/res/lang/'),
    )
));