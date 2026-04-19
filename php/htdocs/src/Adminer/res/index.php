<?php

function adminer_object() {
    require __DIR__ . "/plugins/login-ip.php";
	require __DIR__ . "/plugins/login-password-less.php";

    return new Adminer\Plugins(array(
        new AdminerLoginIp(array('127.0.0.1')),
        new AdminerLoginPasswordLess(password_hash("ROOT", PASSWORD_DEFAULT)),
	));
}

require __DIR__ . "/adminer.php";