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

$content = $code . '. ' . $t->t('error_500_description') . "\n\n" . $error;

echo json_encode(array('error' => $content));

?>
