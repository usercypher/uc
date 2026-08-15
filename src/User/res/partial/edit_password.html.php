<?php 

foreach (array(
    'app',
    't',
    'redirect',
    'redirect_alt',
    'session_token',
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
    <input type="hidden" name="context[update_password]" value="1">
    <input type="hidden" name="user[id]" value="<?php echo($app->htmlEncode($user['id'])); ?>">

    <p>
        <label>
            <?php echo $t->t('old_password'); ?><br>
            <input type="password" name="user_old[password]" required>
        </label>
    </p>
    <p>
        <label>
            <?php echo $t->t('new_password'); ?><br>
            <input id="edit-password" type="password" name="user[password]" required>
        </label>
    </p>
    <p>
        <label>
            <?php echo $t->t('confirm_password'); ?><br>
            <input id="edit-confirm" type="password" name="user_confirm[password]" required>
        </label>
    </p>

    <button><?php echo $t->t('submit'); ?></button>
</form>

<script>
    (function () {
        const pw = document.getElementById('edit-password');
        const confirm = document.getElementById('edit-confirm');
        function validateConfirm() {
          confirm.setCustomValidity(
            confirm.value && pw.value !== confirm.value ? '<?php echo $t->t('passwords_do_not_match'); ?>' : ''
          );
        }
        pw.oninput = validateConfirm;
        confirm.oninput = validateConfirm;
    })();
</script>
