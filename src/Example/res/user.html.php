<?php

$app = $data['app'];
$isAuth = $data['is_auth'];
$route = $data['route'];

$partialAppScript = $data['partial_app_script'] ?? '';
$partialUserSession = $data['partial_user_session'] ?? '';
$partialUserCreate = $data['partial_user_create'] ?? '';
$partialUserEditAccount = $data['partial_user_edit_account'] ?? '';
$partialUserEditPassword = $data['partial_user_edit_password'] ?? '';
$partialUserDelete = $data['partial_user_delete'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Example - User</title>
  <script src="<?= $app->urlWeb('asset/js/uc.js') ?>"></script>
  <link rel="stylesheet" href="<?= $app->urlWeb('asset/css/uc.css') ?>">
  <link rel="stylesheet" href="<?= $app->urlWeb('asset/css/style.css') ?>">
  <style>
      .section { padding: 1em; }
  </style>
</head>
<body>
    <div class="section">

        <h1>Example - User</h1>

<?php if (!$isAuth): ?>
        
        <ul>
            <li><a href="<?= $app->urlRoute('') ?>">Home</a></li>
        </ul>
    
    </div>

    <hr>
        
    <div class="section">

        <h2>Login</h2>
        <fieldset>
            <legend>Account</legend>
    
            <?= $partialUserSession ?>
    
        </fieldset>
        
        <h2>Register</h2>
        <fieldset>
            <legend>Account</legend>
    
            <?= $partialUserCreate ?>
    
        </fieldset>

<?php endif; ?>


<?php if ($isAuth): ?>

        <ul>
            <li><a href="<?= $app->urlRoute('') ?>">Home</a></li>
            <li><a href="<?= $app->urlRoute('user/session-unset?redirect=:redirect', array(':redirect' => trim($route, '/'))) ?>">Logout</a></li>
        </ul>
    </div>

    <hr>

    <div class="section">

        <h2>Edit</h2>
        <fieldset>
            <legend>Account</legend>
            
            <?= $partialUserEditAccount ?>
    
        </fieldset>
        
        <fieldset>
            <legend>Password</legend>
    
            <?= $partialUserEditPassword ?>
    
        </fieldset>
        
        <h2>Delete</h2>
        <fieldset>
    
            <?= $partialUserDelete ?>
    
        </fieldset>

<?php endif; ?>

    </div>

<?= $partialAppScript ?>

</body>
</html>