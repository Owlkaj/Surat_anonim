@echo off
title Surat Anonim - Matikan Server
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0server_manager.ps1" -Action stop
echo.
pause
