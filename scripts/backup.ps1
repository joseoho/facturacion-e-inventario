param(
    [string]$AppDir = (Split-Path -Parent $PSScriptRoot),
    [string]$Xampp = 'C:\xampp',
    [string]$Db = '',
    [string]$User = '',
    [string]$Pass = '',
    [string]$DbHost = ''
)

$ErrorActionPreference = 'Stop'

# Leer credenciales del .env si no se pasaron por parametro.
$envFile = Join-Path $AppDir '.env'
$config = @{}

if (Test-Path -LiteralPath $envFile) {
    Get-Content -LiteralPath $envFile | ForEach-Object {
        if ($_ -match '^\s*([A-Za-z0-9_]+)\s*=\s*(.*)$') {
            $key = $Matches[1]
            $value = $Matches[2].Trim()

            if ($value -match '^"(.*)"$') {
                $value = $Matches[1]
            } elseif ($value -match "^'(.*)'$") {
                $value = $Matches[1]
            }

            $config[$key] = $value
        }
    }
}

if (-not $PSBoundParameters.ContainsKey('Db')) {
    $Db = $config['DB_DATABASE']
}

if (-not $PSBoundParameters.ContainsKey('User')) {
    $User = $config['DB_USERNAME']
}

if (-not $PSBoundParameters.ContainsKey('Pass')) {
    $Pass = $config['DB_PASSWORD']
}

if (-not $PSBoundParameters.ContainsKey('DbHost')) {
    $DbHost = $config['DB_HOST']
}

if ([string]::IsNullOrWhiteSpace($Db)) {
    $Db = 'facturacion_db'
}

if ([string]::IsNullOrWhiteSpace($User)) {
    $User = 'root'
}

$mysql = Join-Path $Xampp 'mysql\bin\mysql.exe'
$dump = Join-Path $Xampp 'mysql\bin\mysqldump.exe'

if (-not (Test-Path -LiteralPath $dump)) {
    Write-Host '[X] No se encontro mysqldump.exe.'
    exit 1
}

$env:MYSQL_PWD = $Pass

$mysqlArgs = @('-u', $User)

if (-not [string]::IsNullOrWhiteSpace($DbHost)) {
    $mysqlArgs += @('-h', $DbHost)
}

$mysqlArgs += @('-N', '-e', "SHOW DATABASES LIKE '$Db';")
$existe = & $mysql @mysqlArgs 2>$null

if (-not $existe) {
    Remove-Item Env:\MYSQL_PWD -ErrorAction SilentlyContinue
    Write-Host "[AVISO] La base $Db no existe; no hay datos que respaldar."
    exit 0
}

$stamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$dir = Join-Path $AppDir "backups\$stamp"
New-Item -ItemType Directory -Path $dir -Force | Out-Null

$sql = Join-Path $dir "$Db.sql"
$hostArg = ''

if (-not [string]::IsNullOrWhiteSpace($DbHost)) {
    $hostArg = "-h `"$DbHost`" "
}

$dumpArgs = "-u `"$User`" $hostArg--single-transaction --quick --routines --triggers --databases `"$Db`""

cmd /c "`"$dump`" $dumpArgs > `"$sql`""

if ($LASTEXITCODE -ne 0 -or -not (Test-Path -LiteralPath $sql)) {
    Remove-Item Env:\MYSQL_PWD -ErrorAction SilentlyContinue
    Write-Host '[X] Fallo mysqldump.'
    exit 1
}

$storage = Join-Path $AppDir 'storage\app\public'

if (Test-Path -LiteralPath $storage) {
    Copy-Item -LiteralPath $storage -Destination (Join-Path $dir 'storage') -Recurse -Force
}

if (Test-Path -LiteralPath $envFile) {
    Copy-Item -LiteralPath $envFile -Destination (Join-Path $dir '.env') -Force
}

Remove-Item Env:\MYSQL_PWD -ErrorAction SilentlyContinue

Write-Host "[OK] Respaldo creado en $dir"
