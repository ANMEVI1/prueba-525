[CmdletBinding()]
param()

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$pruner = Join-Path $projectRoot 'scripts\powershell\Prune-Backups.ps1'
$exampleConfig = Join-Path $projectRoot 'config\backup.config.example.json'
$powerShellExe = Join-Path $PSHOME 'powershell.exe'
$testRoot = Join-Path ([System.IO.Path]::GetTempPath()) ('backup-retention-test-' + [Guid]::NewGuid().ToString('N'))
$destination = Join-Path $testRoot 'destination'
$configPath = Join-Path $testRoot 'backup.config.json'

function Invoke-Pruner {
    param([switch] $Apply)

    $arguments = @('-NoProfile', '-NonInteractive', '-File', $pruner, '-ConfigPath', $configPath)
    if ($Apply) {
        $arguments += '-Apply'
    }
    & $powerShellExe @arguments | Out-Null
    return $LASTEXITCODE
}

try {
    $null = New-Item -ItemType Directory -Path $destination
    $configuration = Get-Content -LiteralPath $exampleConfig -Raw | ConvertFrom-Json
    $configuration.platform_profiles.windows.secondary_destination = $destination
    $configuration.retention.enabled = $true
    $configuration.retention.daily_copies = 1
    $configuration.retention.weekly_copies = 1
    $configuration.retention.monthly_copies = 12
    $configuration | ConvertTo-Json -Depth 10 | Set-Content -LiteralPath $configPath -Encoding UTF8

    $artifacts = @()
    for ($monthOffset = 0; $monthOffset -le 12; $monthOffset++) {
        $timestamp = [DateTime]::UtcNow.Date.AddDays(-1).AddMonths(-$monthOffset).AddHours(12)
        $stamp = $timestamp.ToString('yyyyMMddTHHmmssZ')
        $name = "backup-windows-$stamp-synthetic$monthOffset.tar.gz.age"
        $path = Join-Path $destination $name
        [System.IO.File]::WriteAllText($path, "retention test artifact $monthOffset")
        $hash = (Get-FileHash -LiteralPath $path -Algorithm SHA256).Hash.ToLowerInvariant()
        [System.IO.File]::WriteAllText("$path.sha256", "$hash  $name`n", [System.Text.UTF8Encoding]::new($false))
        $artifacts += $path
    }

    $oldestArtifact = $artifacts[-1]
    if ((Invoke-Pruner) -ne 0) {
        throw 'Retention dry-run failed for valid checksummed artifacts.'
    }
    if (-not (Test-Path -LiteralPath $oldestArtifact -PathType Leaf)) {
        throw 'Retention dry-run deleted an artifact.'
    }

    $newestArtifact = $artifacts[0]
    [System.IO.File]::WriteAllText("$newestArtifact.sha256", ('0' * 64) + "  $([System.IO.Path]::GetFileName($newestArtifact))`n", [System.Text.UTF8Encoding]::new($false))
    if ((Invoke-Pruner -Apply) -eq 0) {
        throw 'Retention did not fail closed for a checksum mismatch.'
    }
    if (-not (Test-Path -LiteralPath $oldestArtifact -PathType Leaf)) {
        throw 'Retention deleted an artifact after detecting a checksum mismatch.'
    }

    $actualHash = (Get-FileHash -LiteralPath $newestArtifact -Algorithm SHA256).Hash.ToLowerInvariant()
    [System.IO.File]::WriteAllText("$newestArtifact.sha256", "$actualHash  $([System.IO.Path]::GetFileName($newestArtifact))`n", [System.Text.UTF8Encoding]::new($false))
    if ((Invoke-Pruner -Apply) -ne 0) {
        throw 'Retention apply failed for valid checksummed artifacts.'
    }
    if (Test-Path -LiteralPath $oldestArtifact) {
        throw 'Retention failed to remove the expired oldest artifact.'
    }
    if (-not (Test-Path -LiteralPath $newestArtifact -PathType Leaf)) {
        throw 'Retention removed the newest verified artifact.'
    }

    Write-Output 'Retention dry-run, checksum fail-closed behavior, and configured bucket pruning passed.'
} finally {
    if (Test-Path -LiteralPath $testRoot) {
        Remove-Item -LiteralPath $testRoot -Recurse -Force
    }
}
