@echo off
title Surat Anonim - Panel Kontrol Server
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0server_manager.ps1" -Action menu
