<?php

$app = $data['app'];
$code = $data['code'];
$error = $data['error'];

$content = $code . '. An unexpected error occurred.' . "\n\n" . $error;

echo json_encode(array('error' => $content));

?>