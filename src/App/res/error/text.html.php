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
?>

<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <style>
        html {font-size: 16px;}
        body {font-family: Arial, sans-serif; margin: 1em;}
        h1 {font-size: 2.7em; font-weight: 500; margin-top: 1em;}
        p {line-height: 1.6;}
        a {text-decoration: none;}
        a:hover {text-decoration: underline;}
        pre {white-space: pre; overflow-x: auto; text-align: left;}
    </style>
</head>
<body>
    <h1><?php echo $title; ?></h1>
    <p><b><?php echo $code; ?>.</b> <?php echo $description; ?> <a href="<?php echo $app->url('ROUTE', ''); ?>"><?php echo $t->t('go_to_homepage'); ?></a></p>
    <pre><?php echo $app->htmlEncode($error); ?></pre>
</body>
</html>
