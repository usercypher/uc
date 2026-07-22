<?php

foreach (array(
    'app',
    'code',
    'error'
) as $v) {
    $$v = $data[$v];
}

$t = $app->makeUnit('Shared_Lib_Translator');
$langMap = $app->getEnv('ERROR_TEMPLATES_LANG', array());
$lang = $app->mimeNegotiate($app->getEnv('ACCEPT_LANGUAGE', ''), array_keys($langMap));
$t->load($app->dir('ROOT', $langMap[$lang]));

$content = $code . '. ' . $t->t('error_500_description') . "\n\n" . $error;

echo json_encode(array('error' => $content));

?>
