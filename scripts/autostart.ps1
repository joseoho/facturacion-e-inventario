param(
    [switch]$Remove,
    [string]$AppPath = (Split-Path -Parent $PSScriptRoot)
)

# Crea (o elimina) un acceso directo en la carpeta de Inicio de Windows
# para levantar Apache y MySQL de XAMPP al iniciar sesion.

$nombre = 'Facturacion - XAMPP.lnk'
$carpetaUsuario = [Environment]::GetFolderPath('Startup')
$carpetaComun = [Environment]::GetFolderPath('CommonStartup')

$esAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole(
    [Security.Principal.WindowsBuiltInRole]::Administrator
)

if ($esAdmin) {
    $carpeta = $carpetaComun
    $alcance = 'todos los usuarios'
} else {
    $carpeta = $carpetaUsuario
    $alcance = 'el usuario actual'
}

$acceso = Join-Path $carpeta $nombre

# Elimina restos en ambas carpetas para no duplicar el arranque.
foreach ($c in @($carpetaUsuario, $carpetaComun)) {
    Remove-Item (Join-Path $c $nombre) -Force -ErrorAction SilentlyContinue
}

if ($Remove) {
    foreach ($c in @($carpetaUsuario, $carpetaComun)) {
        Remove-Item (Join-Path $c $nombre) -Force -ErrorAction SilentlyContinue
    }

    if (Test-Path $acceso) {
        Write-Host "[X] No se pudo eliminar $acceso" -ForegroundColor Red
        exit 1
    }

    Write-Host "[OK] Inicio automatico eliminado" -ForegroundColor Green
    exit 0
}

$vbs = Join-Path $AppPath 'scripts\xampp-autostart.vbs'

if (-not (Test-Path $vbs)) {
    Write-Host "[X] No se encontro $vbs" -ForegroundColor Red
    exit 1
}

$shell = New-Object -ComObject WScript.Shell
$shortcut = $shell.CreateShortcut($acceso)

$shortcut.TargetPath = "$env:SystemRoot\System32\wscript.exe"
$shortcut.Arguments = "`"$vbs`""
$shortcut.WorkingDirectory = $AppPath
$shortcut.Description = 'Inicia Apache y MySQL de XAMPP'
$shortcut.Save()

Write-Host "[OK] Inicio automatico configurado para $alcance" -ForegroundColor Green
Write-Host "     $acceso"
