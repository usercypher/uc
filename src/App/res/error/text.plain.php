<?php

foreach (array(
    'app',
    'code',
    'error'
) as $v) {
    $$v = $data[$v];
}

$translator = $app->makeUnit('Shared_Lib_Translator');
$t = $translator->get('app');

$httpMap = array(
    400 => array($t->t('error_400_title'), $t->t('error_400_description')),
    401 => array($t->t('error_401_title'), $t->t('error_401_description')),
    403 => array($t->t('error_403_title'), $t->t('error_403_description')),
    404 => array($t->t('error_404_title'), $t->t('error_404_description')),
    405 => array($t->t('error_405_title'), $t->t('error_405_description')),
    414 => array($t->t('error_414_title'), $t->t('error_414_description')),
    422 => array($t->t('error_422_title'), $t->t('error_422_description')),
    500 => array($t->t('error_500_title'), $t->t('error_500_description')),
);

list($title, $description) = isset($httpMap[$code]) ? $httpMap[$code] : $httpMap[500];

echo $title . "\n" . $code . '. ' . $description . ".\n\n" . $error;

?>
