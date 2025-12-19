<?php
// _.add_unit.php

$unitPath = 'src/Uc/Lib/';

$app->addUnit('Lib_Curl', $unitPath);
$app->addUnit('Lib_Database', $unitPath);
$app->addUnit('Lib_DatabaseHelper', $unitPath);
$app->addUnit('Lib_GoogleApiGmail', $unitPath);
$app->addUnit('Lib_Html', $unitPath);
$app->addUnit('Lib_Session', $unitPath);
$app->addUnit('Lib_Translator', $unitPath);

$unitPath = 'src/Uc/Pipe/';

$app->addUnit('Pipe_CsrfGenerate', $unitPath);
$app->addUnit('Pipe_CsrfValidate', $unitPath);
$app->addUnit('Pipe_ErrorHandler', $unitPath);
$app->addUnit('Pipe_OtpExist', $unitPath);
$app->addUnit('Pipe_OtpGenerate', $unitPath);
$app->addUnit('Pipe_OtpValidate', $unitPath);
$app->addUnit('Pipe_OutputCompression', $unitPath);
