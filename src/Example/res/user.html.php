<?php

foreach (array(
    'app',
    't',
    'translation_dir',
    'languages',
    'lang',
    'is_auth',
    'route',
    'partial_app_script',
    'partial_user_session',
    'partial_user_create',
    'partial_user_edit_account',
    'partial_user_edit_password',
    'partial_user_delete'
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

  <title><?= $t->t('title') ?></title>
  <script src="<?= $app->url('WEB', 'asset/js/uc.js') ?>"></script>
  <link rel="stylesheet" href="<?= $app->url('WEB', 'asset/css/uc.css') ?>">
  <link rel="stylesheet" href="<?= $app->url('WEB', 'asset/css/style.css') ?>">
  <?php foreach ($languages as $l): ?>
    <link rel="alternate" hreflang="<?php echo $l; ?>" href="<?php echo $app->url('ROUTE', 'example/user/:lang', array(':lang' => $l)); ?>" />
  <?php endforeach; ?>
  <style>
      .section { padding: 1em; }
  </style>
</head>
<body>
    <div class="section">

        <h1><?= $t->t('title') ?></h1>

<?php if (!$is_auth): ?>
        
        <ul>
            <li><a href="<?= $app->url('ROUTE', 'home/:lang', array(':lang' => $lang)) ?>"><?= $t->t('home') ?></a></li>
            <li>

            <?php foreach ($languages as $l): ?>
                <a href="<?php echo $app->url('ROUTE', 'example/user/:lang', array(':lang' => $l)); ?>"><?php echo strtoupper($l); ?></a><?php if ($l !== end($languages)): ?> | <?php endif; ?>
            <?php endforeach; ?>

            </li>
        </ul>
    
    </div>

    <hr>
        
    <div class="section">

        <h2><?= $t->t('login') ?></h2>
        <fieldset>
            <legend><?= $t->t('account') ?></legend>
    
            <?= $partial_user_session ?>
    
        </fieldset>
        
        <h2><?= $t->t('register') ?></h2>
        <fieldset>
            <legend><?= $t->t('account') ?></legend>
    
            <?= $partial_user_create ?>
    
        </fieldset>

<?php endif; ?>


<?php if ($is_auth): ?>

        <ul>
            <li><a href="<?= $app->url('ROUTE', 'home/:lang', array(':lang' => $lang)) ?>"><?= $t->t('home') ?></a></li>
            <li><a href="<?= $app->url('ROUTE', 'user/session-unset?redirect=:redirect', array(':redirect' => trim($route, '/'))) ?>"><?= $t->t('logout') ?></a></li>
            <?php foreach ($languages as $l): ?>
                <a href="<?php echo $app->url('ROUTE', 'example/user/:lang', array(':lang' => $l)); ?>"><?php echo strtoupper($l); ?></a><?php if ($l !== end($languages)): ?> | <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </div>

    <hr>

    <div class="section">

        <h2><?= $t->t('edit') ?></h2>
        <fieldset>
            <legend><?= $t->t('account') ?></legend>
            
            <?= $partial_user_edit_account ?>
    
        </fieldset>
        
        <fieldset>
            <legend><?= $t->t('password') ?></legend>
    
            <?= $partial_user_edit_password ?>
    
        </fieldset>
        
        <h2><?= $t->t('delete') ?></h2>
        <fieldset>
    
            <?= $partial_user_delete ?>
    
        </fieldset>

<?php endif; ?>

    </div>

<?= $partial_app_script ?>

</body>
</html>
