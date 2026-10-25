param(
    [string]$Xampp = 'C:\xampp'
)

# Arranca Apache y MySQL de XAMPP solo si no estan escuchando.
# Pensado para ejecutarse al iniciar sesion Windows.

$ErrorActionPreference = 'SilentlyContinue'

function Test-Listening([int]$Port) {
    return [bool](Get-NetTCPConnection -State Listen -LocalPort $Port -ErrorAction SilentlyContinue)
}

$logDir = Join-Path (Split-Path -Parent $PSScriptRoot) 'storage\logs'

if (Test-Path $logDir) {
    $log = Join-Path $logDir 'xampp-autostart.log'
} else {
    $log = Join-Path $env:TEMP 'xampp-autostart.log'
}

function Write-Log([string]$Mensaje) {
    "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') $Mensaje" |
        Out-File -FilePath $log -Append -Encoding utf8
}

if (-not (Test-Listening 3306)) {
    $mysqld = Join-Path $Xampp 'mysql\bin\mysqld.exe'

    if (Test-Path $mysqld) {
        $ini = ($Xampp -replace '\\', '/') + '/mysql/bin/my.ini'

        Start-Process -FilePath $mysqld `
            -ArgumentList "--defaults-file=$ini", '--standalone' `
            -WindowStyle Hidden

        Write-Log 'MySQL iniciado'
        Start-Sleep -Seconds 3
    } else {
        Write-Log "No se encontro $mysqld"
    }
}

if (-not (Test-Listening 80)) {
    $httpd = Join-Path $Xampp 'apache\bin\httpd.exe'

    if (Test-Path $httpd) {
        $apacheHome = ($Xampp -replace '\\', '/') + '/apache'

        Start-Process -FilePath $httpd `
            -ArgumentList '-d', $apacheHome `
            -WindowStyle Hidden

        Write-Log 'Apache iniciado'
        Start-Sleep -Seconds 3
    } else {
        Write-Log "No se encontro $httpd"
    }
}

if ((Test-Listening 3306) -and (Test-Listening 80)) {
    Write-Log 'Servicios disponibles (80 y 3306)'
} else {
    Write-Log 'Advertencia: algun servicio no responde (80 o 3306)'
}
