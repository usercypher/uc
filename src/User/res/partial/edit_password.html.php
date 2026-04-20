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
    action="<?php echo($app->urlRoute('user/update?redirect=:redirect&redirect_alt=:redirect_alt', array(':redirect' => $redirect, ':redirect_alt' => $redirectAlt))); ?>" 
>
    <input type="hidden" name="session_token" value="<?php echo $app->htmlEncode($sessionToken); ?>">
    <input type="hidden" name="context[update_password]" value="1">
    <input type="hidden" name="user[id]" value="<?php echo($app->htmlEncode($user['id'])); ?>">

    <p>
        <label>
            Old Password<br>
            <input type="password" name="user_old[password]" required>
        </label>
    </p>
    <p>
        <label>
            New Password<br>
            <input id="password" type="password" name="user[password]" required>
        </label>
    </p>
    <p>
        <label>
            Confirm Password<br>
            <input id="confirm" type="password" name="user_confirm[password]" required>
        </label>
    </p>

    <button>Submit</button>
</form>

<script>
    (function () {
        const pw = document.getElementById('password');
        const confirm = document.getElementById('confirm');
        function validateConfirm() {
          confirm.setCustomValidity(
            confirm.value && pw.value !== confirm.value ? 'Passwords do not match.' : ''
          );
        }
        pw.oninput = validateConfirm;
        confirm.oninput = validateConfirm;
    })();
</script>