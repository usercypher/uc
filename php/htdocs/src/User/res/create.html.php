<?php 

$app = $data['app'];
$currentRoute = $data['current_route'];

$csrfToken = $data['csrf_token'];

$userRoles = $data['user_roles'];

$partialScript = $data['partial_script'];

?>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User - Create</title>
    <link rel="stylesheet" href="<?php echo($app->urlWeb('asset/css/uc.css')); ?>">
    <link rel="stylesheet" href="<?php echo($app->urlWeb('asset/css/style.css')); ?>">
    <script src="<?php echo($app->urlWeb('asset/js/uc.js')); ?>"></script>
</head>
<body>
    <form 
        onsubmit="this.querySelector('button').disabled=true; return true;"
        target="_top"
        method="post"
        action="<?php echo($app->urlRoute('user/store?redirect=:redirect&redirect_alt=:redirect_alt', array(':redirect' => $currentRoute, ':redirect_alt' => ''))); ?>" 
    >
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    
        <p>
            <label>
                Username<br>
                <input type="text" name="user[username]" required>
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
                        <option value="<?= htmlspecialchars($role, ENT_QUOTES) ?>">
                            <?= htmlspecialchars($role, ENT_QUOTES) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </p>
        
        <p>
            <label>
                Password<br>
                <input type="password" name="user[password]" required>
            </label>
        </p>
        
        <p>
            <label>
                Confirm Password<br>
                <input type="password" name="user_confirm[password]" required>
            </label>
        </p>
    
        <p>
            <button type="submit">Submit</button>
        </p>
    </form>
    <?php echo $partialScript; ?>
</body>
</html>