<?php

$unitPath = 'src/Shared/Pipe/Cli/';

$app->addUnit('Pipe_Cli_Help', $unitPath);

$unitPath = 'src/Shared/Pipe/Cli/File/';

$app->addUnit('Pipe_Cli_File_Find', $unitPath);
$app->addUnit('Pipe_Cli_File_FindReplace', $unitPath);
$app->addUnit('Pipe_Cli_File_Help', $unitPath);

$unitPath = 'src/Shared/Pipe/Cli/Route/';

$app->addUnit('Pipe_Cli_Route_Help', $unitPath);
$app->addUnit('Pipe_Cli_Route_Print', $unitPath);
$app->addUnit('Pipe_Cli_Route_Resolve', $unitPath);
$app->addUnit('Pipe_Cli_Route_Run', $unitPath);

$unitPath = 'src/Shared/Pipe/Cli/Unit/';

$app->addUnit('Pipe_Cli_Unit_Create', $unitPath);
$app->addUnit('Pipe_Cli_Unit_Help', $unitPath);
