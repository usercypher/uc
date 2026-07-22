<?php

foreach (array(
    'app',
    't',
    'translation_dir',
    'languages',
    'lang'
) as $v) {
    $$v = $data[$v];
}

$t->load($translation_dir);

?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t->t('framework_title'); ?></title>
    <?php foreach ($languages as $l): ?>
        <link rel="alternate" hreflang="<?php echo $l; ?>" href="<?php echo $app->url('ROUTE', 'home/:lang', array(':lang' => $l)); ?>" />
    <?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="<?php echo $app->url('ROUTE', ''); ?>" />
    <style>
        html {font-size: 16px;}
        body {font-family: Arial, sans-serif; margin: 1em;}
        h1 {font-size: 2.7em; font-weight: 500; margin-top: 1em;}
        p {line-height: 1.6;}
        a {text-decoration: none; color: #0066cc;}
        a:hover {text-decoration: underline;}
        pre {white-space: pre; overflow-x: auto; text-align: left;}
    </style>
</head>
<body>
    <h1><?php echo $t->t('framework_title'); ?></h1>
    <p><?php echo $t->t('welcome_message'); ?></p>
    <p>
        <a href="https://github.com/usercypher/uc"><?php echo $t->t('view_on_github'); ?></a> | 
        <a href="<?php echo $app->url('ROUTE', 'phpinfo'); ?>"><?php echo $t->t('php_info'); ?></a> | 
        <a href="<?php echo $app->url('ROUTE', 'example/user/:lang', array(':lang' => $lang)); ?>"><?php echo $t->t('example_user'); ?></a> |
        <a href="<?php echo $app->url('ROUTE', 'example/game/:lang', array(':lang' => $lang)); ?>"><?php echo $t->t('example_game'); ?></a>
    </p>
    <p>
        <?php foreach ($languages as $l): ?>
            <a href="<?php echo $app->url('ROUTE', 'home/:lang', array(':lang' => $l)); ?>"><?php echo strtoupper($l); ?></a><?php if ($l !== end($languages)): ?> | <?php endif; ?>
        <?php endforeach; ?>
    </p>
</body>
</html>
