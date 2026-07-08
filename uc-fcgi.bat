@echo off

cd /d "%~dp0"

if "%PROCESSOR_ARCHITECTURE%"=="x86" (
    bin\uc-fcgi\dist\uc-fcgi-windows-386.exe uc-fcgi-windows.json
) else (
    bin\uc-fcgi\dist\uc-fcgi-windows-amd64.exe uc-fcgi-windows.json
)
