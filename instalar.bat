@echo off
setlocal EnableExtensions EnableDelayedExpansion
chcp 65001 >nul
title Instalador - Facturacion e Inventario

REM ============================================================
REM  INSTALADOR PARA WINDOWS + XAMPP
REM
REM  - Descarga el proyecto (git clone o ZIP).
REM  - Instala Composer si falta.
REM  - Crea la base de datos y ejecuta migraciones + seeders.
REM  - Compila los assets con Vite.
REM  - Configura el VirtualHost de Apache (sin borrar los existentes).
REM  - Deja Apache y MySQL arrancando al iniciar sesion de Windows.
REM
REM  Requisitos: XAMPP (PHP 8.2+ y MySQL). Node.js y Git se intentan
REM  instalar automaticamente si faltan.
REM
REM  Si tu MySQL tiene contrasena, edita DB_PASS aqui abajo.
REM ============================================================

set "REPO=https://github.com/joseoho/facturacion-e-inventario.git"
set "APP_HOST=facturacion-inventario.test"
set "DB_NAME=facturacion_db"
set "DB_USER=root"
set "DB_PASS="
set "XAMPP=C:\xampp"
set "TARGET_DIR=%XAMPP%\htdocs\facturacion-inventario"

if /i "%~1"=="--check" goto :check

echo ============================================================
echo   INSTALADOR - FACTURACION E INVENTARIO
echo ============================================================
echo.

net session >nul 2>&1
if errorlevel 1 (
    echo [i] Se necesitan permisos de administrador para hosts y Apache.
    echo     Acepta el aviso de Windows...
    powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
    exit /b
)

cd /d "%~dp0"

if not exist "%XAMPP%\php\php.exe" (
    echo [X] No se encontro XAMPP en %XAMPP%.
    echo     Instala XAMPP con PHP 8.2 o superior y vuelve a ejecutar.
    goto :fin_error
)

set "PHP=%XAMPP%\php\php.exe"
set "MYSQL=%XAMPP%\mysql\bin\mysql.exe"

REM ------------------------------------------------------------
REM 1) Obtener el proyecto
REM ------------------------------------------------------------
set "APP_DIR=%~dp0"
if exist "%~dp0artisan" goto :proyecto_listo

if exist "%TARGET_DIR%\artisan" (
    set "APP_DIR=%TARGET_DIR%"
    goto :proyecto_listo
)

if exist "%~dp0facturacion-inventario\artisan" (
    set "APP_DIR=%~dp0facturacion-inventario"
    goto :proyecto_listo
)

echo [1/9] Descargando el proyecto en %TARGET_DIR%...
if not exist "%XAMPP%\htdocs" mkdir "%XAMPP%\htdocs"

if exist "%TARGET_DIR%" (
    echo [X] La carpeta ya existe pero no parece el proyecto:
    echo     %TARGET_DIR%
    echo     Renombrala o eliminala y vuelve a ejecutar.
    goto :fin_error
)

where git >nul 2>&1
if not errorlevel 1 (
    git clone "%REPO%" "%TARGET_DIR%"
) else (
    powershell -NoProfile -ExecutionPolicy Bypass -Command "$ErrorActionPreference='Stop'; $zip=Join-Path $env:TEMP 'facturacion.zip'; $tmp=Join-Path $env:TEMP 'facturacion_tmp'; if (Test-Path $zip) { Remove-Item $zip -Force }; if (Test-Path $tmp) { Remove-Item $tmp -Recurse -Force }; Invoke-WebRequest -Uri 'https://github.com/joseoho/facturacion-e-inventario/archive/refs/heads/main.zip' -OutFile $zip; Expand-Archive -Path $zip -DestinationPath $tmp -Force; Move-Item (Join-Path $tmp 'facturacion-e-inventario-main') '%TARGET_DIR%'; Remove-Item $zip -Force; Remove-Item $tmp -Recurse -Force"
)

if not exist "%TARGET_DIR%\artisan" (
    echo [X] No se pudo obtener el proyecto en %TARGET_DIR%.
    goto :fin_error
)

set "APP_DIR=%TARGET_DIR%"

:proyecto_listo
if "%APP_DIR:~-1%"=="\" set "APP_DIR=%APP_DIR:~0,-1%"
cd /d "%APP_DIR%"
echo [OK] Proyecto en %APP_DIR%

REM Si es un clon de Git, traer los ultimos cambios antes de instalar.
if exist ".git" (
    where git >nul 2>&1
    if not errorlevel 1 (
        echo Actualizando codigo con git pull...
        git pull --ff-only
        if errorlevel 1 (
            echo [AVISO] No se pudo actualizar el codigo. Se continua con la version local.
        )
    )
)

REM ------------------------------------------------------------
REM 2) Composer
REM ------------------------------------------------------------
echo [2/9] Preparando Composer...
set "COMPOSER_CMD="
where composer >nul 2>&1
if not errorlevel 1 set "COMPOSER_CMD=composer"

if not defined COMPOSER_CMD (
    if not exist "composer.phar" (
        echo     Descargando composer.phar...
        "%PHP%" -r "copy('https://getcomposer.org/installer','composer-setup.php');"
        "%PHP%" composer-setup.php --quiet
        if exist composer-setup.php del composer-setup.php >nul 2>&1
    )
    if not exist "composer.phar" (
        echo [X] No se pudo instalar Composer.
        goto :fin_error
    )
    set "COMPOSER_CMD=%PHP% composer.phar"
)

REM ------------------------------------------------------------
REM 3) .env inicial
REM ------------------------------------------------------------
if not exist ".env" (
    echo [3/9] Creando .env desde .env.example...
    copy /y ".env.example" ".env" >nul
) else (
    echo [3/9] .env ya existe, se conserva.
)

REM ------------------------------------------------------------
REM 4) Dependencias PHP
REM ------------------------------------------------------------
echo [4/9] Instalando dependencias PHP (composer)...
call %COMPOSER_CMD% install --no-interaction --prefer-dist --no-progress
if errorlevel 1 (
    echo [X] Fallo composer install.
    goto :fin_error
)

findstr /b /c:"APP_KEY=base64:" ".env" >nul 2>&1
if errorlevel 1 (
    "%PHP%" artisan key:generate --force
    if errorlevel 1 goto :fin_error
)

REM ------------------------------------------------------------
REM 5) MySQL y base de datos
REM ------------------------------------------------------------
echo [5/9] Preparando MySQL...
set "MYSQL_AUTH=-u %DB_USER%"
if not "%DB_PASS%"=="" set "MYSQL_AUTH=-u %DB_USER% -p%DB_PASS%"

"%MYSQL%" %MYSQL_AUTH% -e "SELECT 1" >nul 2>&1
if errorlevel 1 (
    echo     Arrancando MySQL...
    powershell -NoProfile -Command "Start-Process -FilePath '%XAMPP%\mysql\bin\mysqld.exe' -ArgumentList '--defaults-file=%XAMPP:\=/%/mysql/bin/my.ini','--standalone' -WindowStyle Hidden"
    timeout /t 8 /nobreak >nul
)

"%MYSQL%" %MYSQL_AUTH% -e "SELECT 1" >nul 2>&1
if errorlevel 1 (
    echo [X] No se pudo conectar a MySQL.
    echo     Abre XAMPP y pulsa Start en MySQL, luego reintenta.
    goto :fin_error
)

"%MYSQL%" %MYSQL_AUTH% -e "CREATE DATABASE IF NOT EXISTS %DB_NAME% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if errorlevel 1 (
    echo [X] No se pudo crear la base de datos %DB_NAME%.
    goto :fin_error
)
echo [OK] Base de datos %DB_NAME% disponible.

REM ------------------------------------------------------------
REM 6) Configuracion del .env
REM ------------------------------------------------------------
echo [6/9] Configurando .env...
"%PHP%" scripts\set-env.php ".env" "APP_URL=http://%APP_HOST%" "DB_CONNECTION=mysql" "DB_HOST=127.0.0.1" "DB_PORT=3306" "DB_DATABASE=%DB_NAME%" "DB_USERNAME=%DB_USER%" "DB_PASSWORD=%DB_PASS%"
if errorlevel 1 goto :fin_error

"%PHP%" artisan optimize:clear >nul 2>&1

REM ------------------------------------------------------------
REM 7) Migraciones y datos iniciales
REM ------------------------------------------------------------
echo [7/9] Migrando la base de datos...
"%PHP%" artisan migrate --force
if errorlevel 1 (
    echo [X] Fallaron las migraciones.
    goto :fin_error
)

REM Seeder idempotente: crea lo que falte y no borra datos existentes.
echo     Cargando datos iniciales...
"%PHP%" artisan db:seed --class=InstalacionSeeder --force > "%APP_DIR%\storage\logs\seed-instalacion.log" 2>&1
set "SEED_EXIT=%errorlevel%"
type "%APP_DIR%\storage\logs\seed-instalacion.log"

if not "%SEED_EXIT%"=="0" (
    echo [AVISO] Reintentando seeders con autoload regenerado...
    call %COMPOSER_CMD% dump-autoload --no-interaction >nul 2>&1
    "%PHP%" artisan db:seed --class=InstalacionSeeder --force
    if errorlevel 1 (
        echo [X] Fallaron los seeders.
        echo     Detalle guardado en storage\logs\seed-instalacion.log
        goto :fin_error
    )
)

echo [OK] Migraciones y datos iniciales aplicados.

REM ------------------------------------------------------------
REM 8) Assets (Node + Vite)
REM ------------------------------------------------------------
echo [8/9] Compilando assets...
where npm >nul 2>&1
if errorlevel 1 (
    echo     Node.js no encontrado. Intentando instalar Node LTS...
    winget install --id OpenJS.NodeJS.LTS -e --silent --accept-package-agreements --accept-source-agreements
    set "PATH=%PATH%;C:\Program Files\nodejs"
)

where npm >nul 2>&1
if errorlevel 1 (
    echo [X] Falta Node.js 20 o superior. Instalalo desde https://nodejs.org
    goto :fin_error
)

if exist "package-lock.json" (
    call npm ci --no-audit --no-fund
) else (
    call npm install --no-audit --no-fund
)
if errorlevel 1 (
    echo     npm ci fallo, reintentando con npm install...
    call npm install --no-audit --no-fund
    if errorlevel 1 goto :fin_error
)

call npm run build
if errorlevel 1 goto :fin_error

if not exist "public\build\manifest.json" (
    echo [X] Vite no genero public\build\manifest.json.
    echo     Verifica Node.js 20 o superior y ejecuta manualmente:
    echo         npm install
    echo         npm run build
    goto :fin_error
)

"%PHP%" artisan storage:link >nul 2>&1
echo [OK] Assets compilados.

REM ------------------------------------------------------------
REM 9) VirtualHost
REM ------------------------------------------------------------
echo [9/9] Configurando Apache...
powershell -NoProfile -ExecutionPolicy Bypass -File "scripts\setup-vhost.ps1" -AppPath "%APP_DIR%" -HostName "%APP_HOST%"
if errorlevel 1 (
    echo [AVISO] No se pudo configurar el vhost automaticamente.
    echo     Revisa los permisos de C:\xampp\apache o configura Apache a mano.
)

REM ------------------------------------------------------------
REM 10) Arranque automatico de XAMPP al iniciar sesion
REM ------------------------------------------------------------
echo Configurando arranque automatico de XAMPP...
powershell -NoProfile -ExecutionPolicy Bypass -File "scripts\autostart.ps1" -AppPath "%APP_DIR%"
if errorlevel 1 (
    echo [AVISO] No se pudo configurar el arranque automatico.
    echo     Puedes hacerlo luego con: powershell -File scripts\autostart.ps1
)

echo.
echo ============================================================
echo   INSTALACION COMPLETADA
echo.
echo   URL:    http://%APP_HOST%
echo   Admin:  admin@facturador.com / Admin123!
echo   Datos:  Cambia la contrasena al entrar.
echo ============================================================
echo.

start "" "http://%APP_HOST%"
pause
exit /b 0

REM ============================================================
REM  MODO DIAGNOSTICO:  instalar.bat --check
REM ============================================================
:check
echo Modo diagnostico (no instala nada)
echo ------------------------------------------------

if exist "%XAMPP%\php\php.exe" (
    echo [OK] PHP encontrado en %XAMPP%\php:
    "%XAMPP%\php\php.exe" -v | findstr /b /c:"PHP "
) else (
    echo [X] PHP no encontrado en %XAMPP%\php
)

if exist "%XAMPP%\mysql\bin\mysql.exe" (
    echo [OK] MySQL cliente encontrado
) else (
    echo [X] MySQL no encontrado en %XAMPP%\mysql
)

where composer >nul 2>&1
if not errorlevel 1 (
    echo [OK] Composer disponible:
    call composer --version
) else (
    echo [AVISO] Composer no esta en PATH (el instalador lo descarga solo)
)

where npm >nul 2>&1
if not errorlevel 1 (
    echo [OK] Node/npm disponible:
    call npm --version
) else (
    echo [AVISO] Node/npm no esta en PATH (el instalador intenta instalarlo)
)

where git >nul 2>&1
if not errorlevel 1 (
    echo [OK] Git disponible
) else (
    echo [AVISO] Git no esta en PATH (se usara la descarga por ZIP)
)

if exist "%~dp0artisan" (
    echo [OK] Proyecto detectado en %~dp0
) else if exist "%TARGET_DIR%\artisan" (
    echo [OK] Proyecto ya instalado en %TARGET_DIR%
) else (
    echo [AVISO] El proyecto se descargara en %TARGET_DIR%
)

echo ------------------------------------------------
echo Diagnostico terminado.
pause
exit /b 0

:fin_error
echo.
echo [X] La instalacion no termino. Revisa el mensaje anterior.
pause
exit /b 1
