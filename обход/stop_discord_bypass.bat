@echo off
chcp 65001 >nul
title Остановка Discord Bypass

echo Останавливаю скрипт...
taskkill /f /im python.exe /fi "WINDOWTITLE eq Discord DPI Bypass*" 2>nul
taskkill /f /im py.exe /fi "WINDOWTITLE eq Discord DPI Bypass*" 2>nul

if %errorlevel% equ 0 (
    echo [✓] Скрипт остановлен.
) else (
    echo [!] Не удалось найти процесс. Возможно, уже остановлен.
)

pause