[CmdletBinding()]
param(
    [string] $Database = 'iesp_continuidad_demo',
    [switch] $Force
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectRoot = Resolve-Path (Join-Path $scriptDir '..\..\..')

$phpCandidates = @(
    "C:\laragon\bin\php\php-8.3.35-Win32-vs16-x64\php.exe",
    (Join-Path $projectRoot "..\..\laragon\bin\php\php-8.3.35-Win32-vs16-x64\php.exe"),
    "php.exe"
)

$phpExe = $null
foreach ($cand in $phpCandidates) {
    if (Test-Path $cand) {
        $phpExe = $cand
        break
    }
}
if (-not $phpExe) { $phpExe = "php" }

$artisan = Join-Path $projectRoot "artisan"

Write-Host "===========================================================" -ForegroundColor Green
Write-Host " Tarea Programada de Respaldo - IESP J.A. Quiñones" -ForegroundColor White
Write-Host "===========================================================" -ForegroundColor Green

$arguments = @($artisan, "backup:auto-run", "--database=$Database")
if ($Force) {
    $arguments += "--force"
}

& $phpExe @arguments
exit $LASTEXITCODE
