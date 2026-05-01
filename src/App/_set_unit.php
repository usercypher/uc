<?php

/**
 * ------------------------------------------------------------------------
 * Pipe
 * ------------------------------------------------------------------------
 */
$app->setUnit('App_Pipe_Init', array('args' => array('App', 'Shared_Lib_Session')));
$app->setUnit('App_Pipe_Index', array('args' => array('App')));
$app->setUnit('App_Pipe_PhpInfo', array('args' => array('App')));
