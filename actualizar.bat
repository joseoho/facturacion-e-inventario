@echo off
setlocal
chcp 65001 >nul
title Actualizar - Facturacion e Inventario

echo ============================================================
echo   ACTUALIZAR FACTURACION E INVENTARIO
echo ============================================================
echo.

set "APP_DIR=%~dp0"
if "%APP_DIR:~-1%"=="\" set "APP_DIR=%APP_DIR:~0,-1%"

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\update.ps1" -AppDir "%APP_DIR%"

if errorlevel 1 (
    echo.
    echo [X] La actualizacion no termino correctamente.
    echo     Revisa el mensaje anterior y el respaldo en backups.
    pause
    exit /b 1
)

echo.
echo [OK] Actualizacion completada.
pause
