<#
.SYNOPSIS
    Configura un VirtualHost de Apache para este proyecto sin borrar
    ni modificar los vhosts existentes.

.DESCRIPTION
    - Hace respaldo de httpd.conf / httpd-vhosts.conf antes de tocarlos.
    - Habilita el Include de httpd-vhosts.conf solo si esta comentado.
    - Agrega el vhost unicamente si no existe (idempotente).
    - Agrega la entrada en hosts si falta y hay permisos.
    - Valida la configuracion con httpd -t y reinicia Apache.

.EJEMPLO
    powershell -ExecutionPolicy Bypass -File scripts\setup-vhost.ps1 -AppPath "C:\xampp\htdocs\facturacion-inventario"
#>
param(
    [Parameter(Mandatory = $true)][string]$AppPath,
    [string]$HostName = 'facturacion-inventario.test',
    [string]$ApacheHome = 'C:\xampp\apache'
)

$ErrorActionPreference = 'Stop'

function Write-Ok($m) { Write-Host "[OK] $m" -ForegroundColor Green }
function Write-Warn($m) { Write-Host "[!] $m" -ForegroundColor Yellow }
function Write-Err($m) { Write-Host "[X] $m" -ForegroundColor Red }

$public = Join-Path $AppPath 'public'

if (-not (Test-Path (Join-Path $public 'index.php'))) {
    Write-Err "No se encontro $public\index.php"
    exit 1
}

$httpdConf = Join-Path $ApacheHome 'conf\httpd.conf'
$vhostsConf = Join-Path $ApacheHome 'conf\extra\httpd-vhosts.conf'
$httpdExe = Join-Path $ApacheHome 'bin\httpd.exe'

foreach ($archivo in @($httpdConf, $vhostsConf, $httpdExe)) {
    if (-not (Test-Path $archivo)) {
        Write-Err "No se encontro $archivo"
        exit 1
    }
}

$publicUnix = $public -replace '\\', '/'
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'

# ------------------------------------------------------------------
# 1) Include de httpd-vhosts.conf
# ------------------------------------------------------------------
$main = Get-Content $httpdConf -Raw

if ($main -notmatch '(?m)^\s*Include\s+conf/extra/httpd-vhosts\.conf') {
    Copy-Item $httpdConf "$httpdConf.bak-$stamp"

    if ($main -match '(?m)^\s*#\s*Include\s+conf/extra/httpd-vhosts\.conf') {
        $main = $main -replace '(?m)^\s*#\s*Include\s+conf/extra/httpd-vhosts\.conf', 'Include conf/extra/httpd-vhosts.conf'
    } else {
        $main = $main.TrimEnd() + "`r`nInclude conf/extra/httpd-vhosts.conf`r`n"
    }

    [System.IO.File]::WriteAllText($httpdConf, $main, [System.Text.Encoding]::ASCII)
    Write-Ok 'Include de httpd-vhosts.conf habilitado'
} else {
    Write-Ok 'Include de httpd-vhosts.conf ya estaba habilitado'
}

# ------------------------------------------------------------------
# 2) VirtualHost (solo si no existe)
# ------------------------------------------------------------------
$vhosts = Get-Content $vhostsConf -Raw
$existeVhost = $vhosts -match "(?m)^\s*ServerName\s+$([regex]::Escape($HostName))\s*$"

if ($existeVhost) {
    Write-Ok "El vhost $HostName ya existe (no se duplica)"
} else {
    Copy-Item $vhostsConf "$vhostsConf.bak-$stamp"

    $bloque = @"

# --- $HostName (Laravel) - agregado por instalar.bat ---
<VirtualHost *:80>
    ServerName $HostName
    DocumentRoot "$publicUnix"

    <Directory "$publicUnix">
        Options FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
    </Directory>

    ErrorLog "logs/facturacion-error.log"
    CustomLog "logs/facturacion-access.log" combined
</VirtualHost>

"@

    Add-Content -Path $vhostsConf -Value $bloque -Encoding ASCII
    Write-Ok "vhost $HostName agregado"
}

# ------------------------------------------------------------------
# 3) Entrada en hosts
# ------------------------------------------------------------------
$hostsFile = Join-Path $env:SystemRoot 'System32\drivers\etc\hosts'
$hosts = Get-Content $hostsFile -Raw
$patronHosts = "(?m)^\s*127\.0\.0\.1\s+$([regex]::Escape($HostName))(\s|$)"

if ($hosts -match $patronHosts) {
    Write-Ok "hosts ya resuelve $HostName"
} else {
    $esAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole(
        [Security.Principal.WindowsBuiltInRole]::Administrator
    )

    if ($esAdmin) {
        Add-Content -Path $hostsFile -Value "`r`n127.0.0.1`t$HostName" -Encoding ASCII
        Write-Ok "Entrada agregada a hosts"
    } else {
        Write-Warn "Falta $HostName en hosts y no hay permisos de administrador"
    }
}

# ------------------------------------------------------------------
# 4) Validar configuracion
# ------------------------------------------------------------------
# httpd -t escribe "Syntax OK" por stderr. Con ErrorActionPreference=Stop,
# PowerShell 5.1 lo convierte en error terminante, asi que lo aislamos.
$errorPrevio = $ErrorActionPreference
$ErrorActionPreference = 'Continue'
$salida = (& $httpdExe -t 2>&1 | Out-String)
$codigoHttpd = $LASTEXITCODE
$ErrorActionPreference = $errorPrevio

if ($codigoHttpd -ne 0) {
    Write-Err "La configuracion de Apache es invalida:`n$salida"
    exit 1
}

Write-Ok 'Configuracion de Apache valida'

# ------------------------------------------------------------------
# 5) Reiniciar Apache (servicio o proceso de XAMPP)
# ------------------------------------------------------------------
$servicio = Get-Service -Name 'Apache2.4' -ErrorAction SilentlyContinue

if ($servicio) {
    try {
        Restart-Service -Name $servicio.Name -Force
        Start-Sleep -Seconds 2
        Write-Ok 'Apache reiniciado (servicio)'
    } catch {
        Write-Warn "No se pudo reiniciar el servicio: $($_.Exception.Message)"
    }
} else {
    Get-Process httpd -ErrorAction SilentlyContinue | Stop-Process -Force
    Start-Sleep -Seconds 1

    Start-Process -FilePath $httpdExe -ArgumentList '-d', ($ApacheHome -replace '\\', '/') -WindowStyle Hidden
    Start-Sleep -Seconds 3

    if (-not (Get-Process httpd -ErrorAction SilentlyContinue)) {
        Write-Err 'Apache no pudo iniciarse'
        exit 1
    }

    Write-Ok 'Apache reiniciado (proceso)'
}

# ------------------------------------------------------------------
# 6) Verificar respuesta
# ------------------------------------------------------------------
try {
    $codigo = & curl.exe -s -o NUL -w "%{http_code}" -H "Host: $HostName" 'http://127.0.0.1/' 2>$null

    if ($codigo -eq '200' -or $codigo -eq '302') {
        Write-Ok "http://$HostName responde ($codigo)"
    } else {
        Write-Warn "Respuesta HTTP $codigo en http://$HostName"
    }
} catch {
    Write-Warn 'No se pudo verificar la respuesta HTTP'
}

Write-Host ''
Write-Host "Listo: http://$HostName" -ForegroundColor Cyan

exit 0
