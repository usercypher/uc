<?php 

foreach (array(
    'app',
    't',
    'redirect',
    'redirect_alt',
    'session_token',
    'user_roles'
) as $v) {
    $$v = $data[$v];
}

?>

<form 
    onsubmit="this.querySelector('button').disabled=true; return true;"
    target="_top"
    method="post"
    action="<?php echo($app->url('ROUTE', 'user/store?redirect=:redirect&redirect_alt=:redirect_alt', array(':redirect' => $redirect, ':redirect_alt' => $redirect_alt))); ?>" 
>
    <input type="hidden" name="session_token" value="<?php echo $session_token; ?>">

    <p>
        <label>
            <?php echo $t->t('username'); ?><br>
            <input type="text" name="user[username]" required x-use-modal-tab-start data-ref="register">
        </label>
    </p>

    <p>
        <label>
            <?php echo $t->t('email'); ?><br>
            <input type="email" name="user[email]">
        </label>
    </p>

    <p>
        <label>
            <?php echo $t->t('firstname'); ?><br>
            <input type="text" name="user[first_name]">
        </label>
    </p>

    <p>
        <label>
            <?php echo $t->t('lastname'); ?><br>
            <input type="text" name="user[last_name]">
        </label>
    </p>

    <p>
        <label>
            <?php echo $t->t('role'); ?><br>
            <select name="user[role]">
                <?php foreach ($user_roles as $role): ?>
                    <option value="<?php echo(htmlspecialchars($role, ENT_QUOTES)); ?>">
                        <?php echo(htmlspecialchars($role, ENT_QUOTES)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </p>
    
    <p>
        <label>
            <?php echo $t->t('password'); ?><br>
            <input id="create-password" type="password" name="user[password]" required>
        </label>
    </p>
    
    <p>
        <label>
            <?php echo $t->t('confirm_password'); ?><br>
            <input id="create-confirm" type="password" name="user_confirm[password]" required>
        </label>
    </p>

    <p>
        <button type="submit"><?php echo $t->t('submit'); ?></button>
    </p>
</form>

<script>
    (function () {
        const pw = document.getElementById('create-password');
        const confirm = document.getElementById('create-confirm');
        function validateConfirm() {
          confirm.setCustomValidity(
            confirm.value && pw.value !== confirm.value ? '<?php echo $t->t('passwords_do_not_match'); ?>' : ''
          );
        }
        pw.oninput = validateConfirm;
        confirm.oninput = validateConfirm;
    })();
</script>
