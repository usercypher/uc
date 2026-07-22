<?php

/**
 * ------------------------------------------------------------------------
 * Pipe
 * ------------------------------------------------------------------------
 */
$app->setUnit('App_Pipe_Init', array('args' => array('App', 'Shared_Lib_Session')));
$app->setUnit('App_Pipe_Index', array('args' => array('App', 'Shared_Lib_Translator')));
$app->setUnit('App_Pipe_Lang', array('args' => array('Shared_Pipe_Lang')));
