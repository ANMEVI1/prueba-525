[CmdletBinding()]
param()

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$runner = Join-Path $projectRoot 'scripts\powershell\Backup-Run.ps1'
$pruner = Join-Path $projectRoot 'scripts\powershell\Prune-Backups.ps1'
$restorer = Join-Path $projectRoot 'recovery\powershell\Restore-Backup.ps1'
$ageKeygen = Get-Command 'age-keygen.exe' -ErrorAction SilentlyContinue
$age = Get-Command 'age.exe' -ErrorAction SilentlyContinue
$tar = Get-Command 'tar.exe' -ErrorAction SilentlyContinue
if (-not $ageKeygen -or -not $age -or -not $tar) {
    throw 'End-to-end tests require age-keygen.exe, age.exe, and tar.exe on PATH.'
}

$testRoot = Join-Path ([System.IO.Path]::GetTempPath()) ('backup-workflow-test-' + [Guid]::NewGuid().ToString('N'))
$identityPath = Join-Path $testRoot 'test-identity.txt'
$sourcePath = Join-Path $testRoot 'source'
$destinationPath = Join-Path $testRoot 'destination'
$restorePath = Join-Path $testRoot 'restored'
$logPath = Join-Path $testRoot 'logs'
$configPath = Join-Path $testRoot 'backup.config.json'
$childPowerShell = Join-Path $PSHOME 'powershell.exe'

try {
    $null = New-Item -ItemType Directory -Path $sourcePath, $destinationPath, $logPath
    Set-Content -LiteralPath (Join-Path $sourcePath 'synthetic.txt') -Value 'IESP synthetic recovery test' -NoNewline
    & $ageKeygen.Source -o $identityPath
    if ($LASTEXITCODE -ne 0) {
        throw 'age-keygen failed to create a test-only identity.'
    }
    $publicKeyMatch = [regex]::Match((Get-Content -LiteralPath $identityPath -Raw), '(?m)^# public key: (?<key>age1\S+)$')
    if (-not $publicKeyMatch.Success) {
        throw 'Could not extract the public key from the temporary test identity.'
    }

    $exampleConfig = Join-Path $projectRoot 'config\backup.config.example.json'
    $configuration = Get-Content -LiteralPath $exampleConfig -Raw | ConvertFrom-Json
    $configuration.enabled = $true
    $configuration.platform_profiles.windows.source_paths = @($sourcePath)
    $configuration.platform_profiles.windows.secondary_destination = $destinationPath
    $configuration.encryption.recipients = @($publicKeyMatch.Groups['key'].Value)
    $configuration.logging.production_log_directory = $logPath
    $configuration.retention.enabled = $false
    $configuration | ConvertTo-Json -Depth 10 | Set-Content -LiteralPath $configPath -Encoding UTF8

    & $childPowerShell -NoProfile -NonInteractive -File $runner -ConfigPath $configPath
    if ($LASTEXITCODE -ne 0) {
        throw "Synthetic backup failed with exit code $LASTEXITCODE."
    }

    $artifact = Get-ChildItem -LiteralPath $destinationPath -File -Filter 'backup-windows-*.tar.gz.age' |
        Select-Object -First 1
    if (-not $artifact -or -not (Test-Path -LiteralPath "$($artifact.FullName).sha256" -PathType Leaf)) {
        throw 'Synthetic backup artifact or checksum sidecar was not created.'
    }

    & $childPowerShell -NoProfile -NonInteractive -File $restorer `
        -ArtifactPath $artifact.FullName -IdentityPath $identityPath -TargetPath $restorePath
    if ($LASTEXITCODE -ne 0) {
        throw "Synthetic restore failed with exit code $LASTEXITCODE."
    }
    $restoredFile = Join-Path $restorePath 'source-001\synthetic.txt'
    if ((Get-Content -LiteralPath $restoredFile -Raw) -ne 'IESP synthetic recovery test') {
        throw 'Restored synthetic file content does not match the source.'
    }

    $existingTargetExit = 0
    & $childPowerShell -NoProfile -NonInteractive -File $restorer `
        -ArtifactPath $artifact.FullName -IdentityPath $identityPath -TargetPath $restorePath
    $existingTargetExit = $LASTEXITCODE
    if ($existingTargetExit -eq 0) {
        throw 'Restore unexpectedly overwrote an existing target.'
    }

    & $childPowerShell -NoProfile -NonInteractive -File $pruner -ConfigPath $configPath -Apply
    if ($LASTEXITCODE -eq 0) {
        throw 'Retention unexpectedly deleted artifacts while disabled.'
    }

    $configuration.retention.enabled = $true
    $configuration.retention.daily_copies = 1
    $configuration.retention.weekly_copies = 1
    $configuration.retention.monthly_copies = 12
    $syntheticArtifacts = @()
    for ($monthOffset = 0; $monthOffset -le 12; $monthOffset++) {
        $timestamp = [DateTime]::UtcNow.Date.AddDays(-1).AddMonths(-$monthOffset).AddHours(12)
        $stamp = $timestamp.ToString('yyyyMMddTHHmmssZ')
        $name = "backup-windows-$stamp-synthetic$monthOffset.tar.gz.age"
        $path = Join-Path $destinationPath $name
        [System.IO.File]::WriteAllText($path, "synthetic retention item $monthOffset")
        $hash = (Get-FileHash -LiteralPath $path -Algorithm SHA256).Hash.ToLowerInvariant()
        [System.IO.File]::WriteAllText("$path.sha256", "$hash  $name`n", [System.Text.UTF8Encoding]::new($false))
        $syntheticArtifacts += $path
    }
    $configuration | ConvertTo-Json -Depth 10 | Set-Content -LiteralPath $configPath -Encoding UTF8
    $oldestSyntheticArtifact = $syntheticArtifacts[-1]

    & $childPowerShell -NoProfile -NonInteractive -File $pruner -ConfigPath $configPath
    if ($LASTEXITCODE -ne 0) {
        throw 'Retention dry-run failed on verified synthetic artifacts.'
    }
    if (-not (Test-Path -LiteralPath $oldestSyntheticArtifact -PathType Leaf)) {
        throw 'Retention dry-run deleted an artifact.'
    }

    & $childPowerShell -NoProfile -NonInteractive -File $pruner -ConfigPath $configPath -Apply
    if ($LASTEXITCODE -ne 0) {
        throw 'Retention apply failed on verified synthetic artifacts.'
    }
    if (Test-Path -LiteralPath $oldestSyntheticArtifact) {
        throw 'Retention did not remove the artifact outside all configured retention buckets.'
    }

    Write-Output 'Synthetic backup, authenticated decryption, restore verification, no-overwrite guard, and retention safety checks passed.'
} finally {
    if (Test-Path -LiteralPath $testRoot) {
        Remove-Item -LiteralPath $testRoot -Recurse -Force
    }
}
