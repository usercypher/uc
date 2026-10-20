@echo off

cd /d "%~dp0"

if "%PROCESSOR_ARCHITECTURE%"=="x86" (
    bin\uc-web\dist\uc-web-windows-386.exe bin\uc-web\uc-web-windows-386.json
) else (
    bin\uc-web\dist\uc-web-windows-amd64.exe bin\uc-web\uc-web-windows-amd64.json
)
