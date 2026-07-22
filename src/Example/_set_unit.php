<?php

/**
 * ------------------------------------------------------------------------
 * Pipe
 * ------------------------------------------------------------------------
 */
$app->setUnit('Example_Pipe_User', array('args' => array('App', 'Shared_Lib_Session', 'Shared_Lib_Translator')));
$app->setUnit('Example_Pipe_Game', array('args' => array('App', 'Shared_Lib_Session', 'Shared_Lib_Translator')));
$app->setUnit('Example_Pipe_Lang', array('args' => array('Shared_Pipe_Lang')));
