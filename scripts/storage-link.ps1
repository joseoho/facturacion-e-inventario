param(
    [string]$AppDir = (Split-Path -Parent $PSScriptRoot)
)

$ErrorActionPreference = 'Stop'

$link = Join-Path $AppDir 'public\storage'
$target = Join-Path $AppDir 'storage\app\public'

if (-not (Test-Path -LiteralPath $target)) {
    New-Item -ItemType Directory -Path $target -Force | Out-Null
}

if (Test-Path -LiteralPath $link) {
    $item = Get-Item -LiteralPath $link -Force
    $esEnlace = [bool]($item.Attributes -band [IO.FileAttributes]::ReparsePoint)

    if ($esEnlace) {
        Write-Host '[OK] public\storage ya es un enlace.'
        exit 0
    }

    $contenido = @(Get-ChildItem -LiteralPath $link -Force -ErrorAction SilentlyContinue)

    if ($contenido.Count -gt 0) {
        Write-Host "[X] public\storage es una carpeta con $($contenido.Count) archivo(s); no se elimina."
        Write-Host '    Mueve su contenido a storage\app\public y vuelve a ejecutar.'
        exit 1
    }

    Remove-Item -LiteralPath $link -Force
}

try {
    New-Item -ItemType Junction -Path $link -Target $target | Out-Null
} catch {
    cmd /c mklink /J "$link" "$target" | Out-Null

    if ($LASTEXITCODE -ne 0) {
        Write-Host '[X] No se pudo crear el enlace public\storage.'
        exit 1
    }
}

$item = Get-Item -LiteralPath $link -Force

if (-not ($item.Attributes -band [IO.FileAttributes]::ReparsePoint)) {
    Write-Host '[X] public\storage no quedo como enlace.'
    exit 1
}

Write-Host '[OK] public\storage -> storage\app\public'
