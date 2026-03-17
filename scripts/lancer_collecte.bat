@echo off
chcp 65001 >nul
echo Lancement de la collecte inventaire...
echo.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0core\collect_windows.ps1"
