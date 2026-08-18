<?php

foreach (array(
    'app',
    'code',
    'error'
) as $v) {
    $$v = $data[$v];
}


$langMap = $app->getEnv('ERROR_TEMPLATES_LANG', array());
$lang = $app->httpNegotiate($app->getEnv('ACCEPT_LANGUAGE', ''), array_keys($langMap));

$translator = $app->makeUnit('Shared_Lib_Translator');
$translator->set('error', require($app->dir('ROOT', $langMap[$lang])));

$t = $translator->get('error');

$content = $code . '. ' . $t->t('error_500_description') . "\n\n" . $error;

echo json_encode(array('error' => $content));

?>
