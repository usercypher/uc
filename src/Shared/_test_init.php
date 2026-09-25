<?php

/* **********
 * clone app
 */

$testapp = $app->inst();

/* **********
 * unit
 */

$testapp->unitScan('src/' . basename(dirname(__FILE__)) . '/Test/', array());

$group = array(
    'args_prepend' => array('App', 'Shared_Lib_Assert')
);
$testapp->unitGroup($group, 'Test_Translator', array('args' => array('Shared_Lib_Translator')));
