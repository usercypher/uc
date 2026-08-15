<?php 

foreach (array(
    'app',
    't',
    'redirect',
    'redirect_alt',
    'session_token',
    'user_roles',
    'user'
) as $v) {
    $$v = $data[$v];
}

?>
<form 
    onsubmit="this.querySelector('button').disabled=true; return true;"
    target="_top"
    method="post"
    action="<?php echo($app->url('ROUTE', 'user/update?redirect=:redirect&redirect_alt=:redirect_alt', array(':redirect' => $redirect, ':redirect_alt' => $redirect_alt))); ?>" 
>
    <input type="hidden" name="session_token" value="<?php echo $app->htmlEncode($session_token); ?>">
    <input type="hidden" name="context[update_account]" value="1">
    <input type="hidden" name="user[id]" value="<?php echo($app->htmlEncode($user['id'])); ?>">
    <input type="hidden" name="user_old[username]" value="<?php echo($app->htmlEncode($user['username'])); ?>">
    <input type="hidden" name="user_old[email]" value="<?php echo($app->htmlEncode($user['email'])); ?>">

    <p>
        <label>
            <?php echo $t->t('username'); ?><br>
            <input type="text" name="user[username]" required value="<?php echo($app->htmlEncode($user['username'])); ?>">
        </label>
    </p>

    <p>
        <label>
            <?php echo $t->t('email'); ?><br>
            <input type="email" name="user[email]" value="<?php echo($app->htmlEncode($user['email'])); ?>">
        </label>
    </p>

    <p>
        <label>
            <?php echo $t->t('firstname'); ?><br>
            <input type="text" name="user[first_name]" value="<?php echo($app->htmlEncode($user['first_name'])); ?>">
        </label>
    </p>

    <p>
        <label>
            <?php echo $t->t('lastname'); ?><br>
            <input type="text" name="user[last_name]" value="<?php echo($app->htmlEncode($user['last_name'])); ?>">
        </label>
    </p>

    <p>
        <label>
            <?php echo $t->t('role'); ?><br>
            <select name="user[role]">
                <?php foreach ($user_roles as $role): ?>
                    <option value="<?= htmlspecialchars($role, ENT_QUOTES) ?>" <?= $user['role'] === $role ? 'selected' : '' ?>>
                        <?= htmlspecialchars($role, ENT_QUOTES) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </p>

    <button><?php echo $t->t('submit'); ?></button>
</form>
