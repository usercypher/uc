<?php 

$app = $data['app'];
$redirect = $data['redirect'];
$redirectAlt = $data['redirect_alt'];
$sessionToken = $data['session_token'];
$userRoles = $data['user_roles'];

?>

<form 
    onsubmit="this.querySelector('button').disabled=true; return true;"
    target="_top"
    method="post"
    action="<?php echo($app->urlRoute('user/store?redirect=:redirect&redirect_alt=:redirect_alt', array(':redirect' => $redirect, ':redirect_alt' => $redirectAlt))); ?>" 
>
    <input type="hidden" name="session_token" value="<?php echo $sessionToken; ?>">

    <p>
        <label>
            Username<br>
            <input type="text" name="user[username]" required x-use-modal-tab-start data-ref="register">
        </label>
    </p>

    <p>
        <label>
            Email<br>
            <input type="email" name="user[email]">
        </label>
    </p>

    <p>
        <label>
            Firstname<br>
            <input type="text" name="user[first_name]">
        </label>
    </p>

    <p>
        <label>
            Lastname<br>
            <input type="text" name="user[last_name]">
        </label>
    </p>

    <p>
        <label>
            Role<br>
            <select name="user[role]">
                <?php foreach ($userRoles as $role): ?>
                    <option value="<?php echo(htmlspecialchars($role, ENT_QUOTES)); ?>">
                        <?php echo(htmlspecialchars($role, ENT_QUOTES)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </p>
    
    <p>
        <label>
            Password<br>
            <input id="password" type="password" name="user[password]" required>
        </label>
    </p>
    
    <p>
        <label>
            Confirm Password<br>
            <input id="confirm" type="password" name="user_confirm[password]" required>
        </label>
    </p>

    <p>
        <button type="submit">Submit</button>
    </p>
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