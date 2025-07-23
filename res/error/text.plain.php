<?php

$app = $data['app'];
$code = $data['code'];
$error = $data['error'];

echo $app->getEnv('SHOW_ERRORS') ? $content : 'An unexpected error occurred. Please try again later.' . EOL;

?>