@echo off

if "%PROCESSOR_ARCHITECTURE%"=="x86" (
    "%~dp0bin\php\php-windows-386\php.exe" -c "%~dp0bin\php\php-windows-386.ini" "%~dp0bin\index.php" %*
) else (
    "%~dp0bin\php\php-windows-amd64\php.exe" -c "%~dp0bin\php\php-windows-amd64.ini" "%~dp0bin\index.php" %*
)
