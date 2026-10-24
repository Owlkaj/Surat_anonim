@echo off
title Surat Anonim - Hidupkan Server
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0server_manager.ps1" -Action start
echo.
pause
