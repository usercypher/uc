<?php 

$app = $data['app'];
$redirect = $data['redirect'];
$redirectAlt = $data['redirect_alt'];
$sessionToken = $data['session_token'];
$user = $data['user'];

?>

<form 
    onsubmit="this.querySelector('button').disabled=true; return true;"
    target="_top"
    method="post"
    action="<?php echo($app->urlRoute('user/delete?redirect=:redirect&redirect_alt=:redirect_alt', array(':redirect' => $redirect, ':redirect_alt' => $redirectAlt))); ?>" 
>
    <input type="hidden" name="session_token" value="<?php echo $app->htmlEncode($sessionToken); ?>">
    <input type="hidden" name="user[id]" value="<?php echo($app->htmlEncode($user['id'])); ?>">

    <p>
        <label>
            Password<br>
            <input type="password" name="user[password]" required>
        </label>
    </p>

    <button>Submit</button>
</form>