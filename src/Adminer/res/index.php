<?php

function adminer_object() {
    require __DIR__ . "/plugins/login-ip.php";
	require __DIR__ . "/plugins/login-password-less.php";

    return new Adminer\Plugins(array(
        new AdminerLoginPasswordLess(password_hash("root", PASSWORD_DEFAULT)),
        new AdminerLoginIp(array('127.0.0.1')),
	));
}

require __DIR__ . "/adminer.php";