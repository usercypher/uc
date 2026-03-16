<?php

$app = $data['app'];
$isAuth = $data['is_auth'];

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
  <title>default</title>
  <script src="<?= $app->urlWeb('asset/js/uc.js') ?>"></script>
  <link rel="stylesheet" href="<?= $app->urlWeb('asset/css/uc.css') ?>">
  <link rel="stylesheet" href="<?= $app->urlWeb('asset/css/style.css') ?>">
</head>
<body>

<?php if (!$isAuth): ?>

    <h1>Login</h1>
    <fieldset>

        <?= $partialUserSession ?>

    </fieldset>
    
    <h1>Register</h1>
    <fieldset>

        <?= $partialUserCreate ?>

    </fieldset>

<?php endif; ?>


<?php if ($isAuth): ?>

    <h1>Edit</h1>
    <fieldset>
        <legend>Account</legend>
        
        <?= $partialUserEditAccount ?>

    </fieldset>
    
    <fieldset>
        <legend>Password</legend>

        <?= $partialUserEditPassword ?>

    </fieldset>
    
    <h1>Delete</h1>
    <fieldset>

        <?= $partialUserDelete ?>

    </fieldset>

<?php endif; ?>

<?= $partialAppScript ?>

</body>
</html>