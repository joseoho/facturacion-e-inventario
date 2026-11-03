param(
    [string]$AppDir = (Split-Path -Parent $PSScriptRoot),
    [switch]$Force
)

$ErrorActionPreference = 'Stop'

Set-Location -LiteralPath $AppDir

function Get-Php {
    $cmd = Get-Command php -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }

    $xampp = 'C:\xampp\php\php.exe'
    if (Test-Path -LiteralPath $xampp) { return $xampp }

    throw 'No se encontro PHP. Agrega C:\xampp\php al PATH.'
}

$php = Get-Php

$before = (& git rev-parse HEAD).Trim()

Write-Host '[1/6] Trayendo cambios del repositorio...'
& git pull --ff-only
if ($LASTEXITCODE -ne 0) {
    Write-Host '[X] git pull fallo. Revisa cambios locales con: git status'
    exit 1
}

$after = (& git rev-parse HEAD).Trim()
$changed = @()

if ($before -ne $after) {
    $changed = @(& git diff --name-only $before $after)
}

if (-not $Force -and $changed.Count -eq 0) {
    Write-Host '[OK] No hay cambios para aplicar.'
    exit 0
}

if ($changed.Count -gt 0) {
    Write-Host '[OK] Archivos actualizados:'
    $changed | ForEach-Object { Write-Host "    $_" }
}

Write-Host '[2/6] Creando respaldo...'
$backupScript = Join-Path $PSScriptRoot 'backup.ps1'
& powershell -NoProfile -ExecutionPolicy Bypass -File $backupScript -AppDir $AppDir
if ($LASTEXITCODE -ne 0) {
    Write-Host '[X] No se pudo crear el respaldo. Se cancela la actualizacion.'
    exit 1
}

$composerChanged = @($changed | Where-Object { $_ -match '^composer\.(json|lock)$' }).Count -gt 0
$assetsChanged = @($changed | Where-Object {
    $_ -match '^(package(-lock)?\.json|vite\.config\.js)$' -or
    $_ -match '^resources/(js|css)/'
}).Count -gt 0
$seedersChanged = @($changed | Where-Object { $_ -match '^database/seeders/' }).Count -gt 0

if ($composerChanged) {
    Write-Host '[3/6] Instalando dependencias PHP...'
    if (Get-Command composer -ErrorAction SilentlyContinue) {
        & composer install --no-interaction --prefer-dist
        if ($LASTEXITCODE -ne 0) {
            Write-Host '[X] composer install fallo.'
            exit 1
        }
    } else {
        Write-Host '[AVISO] Composer no esta en PATH; instala las dependencias manualmente.'
    }
} else {
    Write-Host '[3/6] Sin cambios en composer.json/lock.'
}

Write-Host '[4/6] Aplicando migraciones...'
& $php artisan migrate --force
if ($LASTEXITCODE -ne 0) {
    Write-Host '[X] Fallaron las migraciones.'
    exit 1
}

if ($seedersChanged) {
    Write-Host '       Cargando datos base (idempotente)...'
    & $php artisan db:seed --class=InstalacionSeeder --force
    if ($LASTEXITCODE -ne 0) {
        Write-Host '[X] Fallaron los seeders.'
        exit 1
    }
} else {
    Write-Host '       Sin cambios en seeders.'
}

if ($assetsChanged) {
    Write-Host '[5/6] Compilando assets...'
    if (Get-Command npm -ErrorAction SilentlyContinue) {
        if (Test-Path -LiteralPath (Join-Path $AppDir 'package-lock.json')) {
            & npm ci
        } else {
            & npm install
        }

        if ($LASTEXITCODE -ne 0) {
            Write-Host '[X] npm install/ci fallo.'
            exit 1
        }

        & npm run build
        if ($LASTEXITCODE -ne 0) {
            Write-Host '[X] npm run build fallo.'
            exit 1
        }
    } else {
        Write-Host '[AVISO] Node/npm no esta en PATH; compila los assets en otro equipo.'
    }
} else {
    Write-Host '[5/6] Sin cambios de frontend.'
}

Write-Host '[6/6] Limpiando caches y enlaces...'
& $php artisan optimize:clear
& (Join-Path $PSScriptRoot 'storage-link.ps1') -AppDir $AppDir

Write-Host ''
Write-Host '[OK] Actualizacion terminada.' -ForegroundColor Green
Write-Host 'Reinicia Apache si el navegador sigue mostrando la version anterior.'
