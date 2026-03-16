<?php 

$app = $data['app'];
$redirect = $data['redirect'];
$redirectAlt = $data['redirect_alt'];
$sessionToken = $data['session_token'];

?>

<form 
    onsubmit="this.querySelector('button').disabled=true; return true;"
    target="_top"
    method="post"
    action="<?php echo($app->urlRoute('user/session-verify?redirect=:redirect&redirect_alt=:redirect_alt', array(':redirect' => $redirect, ':redirect_alt' => $redirectAlt))); ?>" 
>
    <input type="hidden" name="session_token" value="<?php echo $app->htmlEncode($sessionToken); ?>">

    <p>
        <label>
            Username or email<br>
            <input type="text" name="user[username]" required>
        </label>
    </p>
    <p>
        <label>
            Password<br>
            <input type="password" name="user[password]" required>
        </label>
    </p>

    <button>Submit</button>
</form>
