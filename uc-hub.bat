@echo off

cd /d "%~dp0"

if "%PROCESSOR_ARCHITECTURE%"=="x86" (
    bin\uc-hub\dist\uc-hub-windows-386.exe uc-hub.json
) else (
    bin\uc-hub\dist\uc-hub-windows-amd64.exe uc-hub.json
)
