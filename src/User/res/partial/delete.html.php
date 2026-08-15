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
    action="<?php echo($app->url('ROUTE', 'user/delete?redirect=:redirect&redirect_alt=:redirect_alt', array(':redirect' => $redirect, ':redirect_alt' => $redirect_alt))); ?>" 
>
    <input type="hidden" name="session_token" value="<?php echo $app->htmlEncode($session_token); ?>">
    <input type="hidden" name="user[id]" value="<?php echo($app->htmlEncode($user['id'])); ?>">

    <p>
        <label>
            <?php echo $t->t('password'); ?><br>
            <input type="password" name="user[password]" required>
        </label>
    </p>

    <button><?php echo $t->t('submit'); ?></button>
</form>
