@echo off
title Surat Anonim - Saklar Otomatis (On / Off)
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0server_manager.ps1" -Action toggle
echo.
pause
