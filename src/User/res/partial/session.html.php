<?php

foreach (array(
    'app',
    't',
    'redirect',
    'redirect_alt',
    'session_token',
) as $v) {
    $$v = $data[$v];
}

?>


<form 
    onsubmit="this.querySelector('button').disabled=true; return true;"
    target="_top"
    method="post"
    action="<?php echo($app->url('ROUTE', 'user/session-verify?redirect=:redirect&redirect_alt=:redirect_alt', array(':redirect' => $redirect, ':redirect_alt' => $redirect_alt))); ?>" 
>
    <input type="hidden" name="session_token" value="<?php echo $app->htmlEncode($session_token); ?>">

    <p>
        <label>
            <?php echo $t->t('username_or_email'); ?><br>
            <input type="text" name="user[username]" required>
        </label>
    </p>
    <p>
        <label>
            <?php echo $t->t('password'); ?><br>
            <input type="password" name="user[password]" required>
        </label>
    </p>

    <button><?php echo $t->t('submit'); ?></button>
</form>
