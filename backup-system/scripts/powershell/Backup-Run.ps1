[CmdletBinding()]
param(
    [string] $ConfigPath = (Join-Path $PSScriptRoot '..\..\config\backup.config.json'),
    [switch] $ValidateOnly
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$runId = '{0}-{1}' -f (Get-Date).ToUniversalTime().ToString('yyyyMMddTHHmmssZ'), [Guid]::NewGuid().ToString('N')
$logPath = $null
$stagingPath = $null
$archivePath = $null
$encryptedPartialPath = $null
$checksumPartialPath = $null

function Write-BackupLog {
    param(
        [Parameter(Mandatory)][ValidateSet('INFO', 'ERROR')][string] $Level,
        [Parameter(Mandatory)][string] $Message
    )

    $entry = [ordered]@{
        timestamp = (Get-Date).ToUniversalTime().ToString('o')
        level     = $Level
        run_id    = $runId
        message   = $Message
    } | ConvertTo-Json -Compress

    Write-Output $entry
    if ($logPath) {
        Add-Content -LiteralPath $logPath -Value $entry -Encoding UTF8
    }
}

function Assert-Configuration {
    param([Parameter(Mandatory)] $Configuration)

    if ($Configuration.schema_version -ne 1 -or
        $Configuration.platform_profiles.windows.source_paths.Count -lt 1 -or
        $Configuration.encryption.enabled -ne $true -or
        $Configuration.encryption.recipients.Count -lt 1) {
        throw 'Configuration schema is incomplete or encryption is disabled.'
    }

}

try {
    if (-not (Test-Path -LiteralPath $ConfigPath -PathType Leaf)) {
        throw "Configuration file does not exist: $ConfigPath"
    }
    $configuration = Get-Content -LiteralPath $ConfigPath -Raw | ConvertFrom-Json
    Assert-Configuration -Configuration $configuration

    if ($ValidateOnly) {
        Write-Output 'Configuration schema is valid. Host paths and backup execution were not tested.'
        exit 0
    }
    if ($configuration.enabled -ne $true) {
        throw 'Backups are disabled in configuration.'
    }
    foreach ($value in @(
        [string]$configuration.platform_profiles.windows.secondary_destination,
        [string]$configuration.logging.production_log_directory,
        (@($configuration.encryption.recipients) -join ',')
    )) {
        if ($value -match 'REEMPLAZAR') {
            throw 'Replace all example destination, log, and recipient values before enabling backups.'
        }
    }
    if ($configuration.archive.format -ne 'tar' -or $configuration.archive.compression -ne 'gzip') {
        throw 'Only tar/gzip archives are supported by this runner.'
    }
    if ($configuration.integrity.verify_after_transfer -ne $true) {
        throw 'Post-transfer integrity verification must remain enabled.'
    }

    $destination = [string]$configuration.platform_profiles.windows.secondary_destination
    if (-not (Test-Path -LiteralPath $destination -PathType Container)) {
        throw 'Secondary destination must already exist and be reachable; it is never created implicitly.'
    }

    $logDirectory = [string]$configuration.logging.production_log_directory
    if (-not (Test-Path -LiteralPath $logDirectory -PathType Container)) {
        New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null
    }
    $logPath = Join-Path $logDirectory 'backup-windows.jsonl'

    $ageCommand = Get-Command 'age.exe' -ErrorAction Stop
    $tarCommand = Get-Command 'tar.exe' -ErrorAction Stop
    $sourcePaths = @($configuration.platform_profiles.windows.source_paths)
    foreach ($sourcePath in $sourcePaths) {
        if (-not (Test-Path -LiteralPath $sourcePath)) {
            throw "Configured source does not exist: $sourcePath"
        }
        $sourceItem = Get-Item -LiteralPath $sourcePath -Force
        if (($sourceItem.Attributes -band [System.IO.FileAttributes]::ReparsePoint) -ne 0) {
            throw 'Symbolic links, junctions, and other reparse-point sources are not supported.'
        }
        if ($sourceItem.PSIsContainer) {
            $reparsePoint = Get-ChildItem -LiteralPath $sourcePath -Force -Recurse |
                Where-Object { ($_.Attributes -band [System.IO.FileAttributes]::ReparsePoint) -ne 0 } |
                Select-Object -First 1
            if ($reparsePoint) {
                throw "Source contains an unsupported reparse point: $($reparsePoint.FullName)"
            }
        }
        $resolvedSource = (Resolve-Path -LiteralPath $sourcePath).Path.TrimEnd('\')
        $resolvedDestination = (Resolve-Path -LiteralPath $destination).Path.TrimEnd('\')
        if ($resolvedDestination.StartsWith("$resolvedSource\", [StringComparison]::OrdinalIgnoreCase) -or
            $resolvedDestination -eq $resolvedSource) {
            throw 'Backup destination must not be equal to or inside a source path.'
        }
    }

    $lockPath = Join-Path $destination '.backup-windows.lock'
    $lock = $null
    try {
        $lock = [System.IO.File]::Open($lockPath, [System.IO.FileMode]::OpenOrCreate, [System.IO.FileAccess]::ReadWrite, [System.IO.FileShare]::None)
    } catch {
        throw 'Another backup process holds the destination lock.'
    }

    try {
        $stagingPath = Join-Path ([System.IO.Path]::GetTempPath()) ("backup-" + $runId)
        $null = New-Item -ItemType Directory -Path $stagingPath
        $manifestSources = @()
        $index = 0
        foreach ($sourcePath in $sourcePaths) {
            $index++
            $slot = 'source-{0:D3}' -f $index
            $slotPath = Join-Path $stagingPath $slot
            $null = New-Item -ItemType Directory -Path $slotPath
            $item = Get-Item -LiteralPath $sourcePath -Force
            if ($item.PSIsContainer) {
                $null = & robocopy.exe $item.FullName $slotPath /E /COPY:DAT /DCOPY:DAT /R:2 /W:2 /XJ /NFL /NDL /NJH /NJS
                $robocopyExitCode = $LASTEXITCODE
                if ($robocopyExitCode -ge 8) {
                    throw "Robocopy failed for source slot $slot (exit code $robocopyExitCode)."
                }
            } else {
                Copy-Item -LiteralPath $item.FullName -Destination $slotPath
            }
            $manifestSources += [ordered]@{ slot = $slot; original_path = $item.FullName }
        }

        $manifest = [ordered]@{
            run_id     = $runId
            created_at = (Get-Date).ToUniversalTime().ToString('o')
            platform   = 'windows'
            sources    = $manifestSources
        } | ConvertTo-Json -Depth 6
        [System.IO.File]::WriteAllText((Join-Path $stagingPath 'backup-manifest.json'), $manifest, [System.Text.UTF8Encoding]::new($false))

        $archivePath = Join-Path ([System.IO.Path]::GetTempPath()) ("backup-" + $runId + '.tar.gz')
        & $tarCommand.Source -czf $archivePath -C $stagingPath .
        if ($LASTEXITCODE -ne 0 -or -not (Test-Path -LiteralPath $archivePath -PathType Leaf)) {
            throw "tar.exe failed to create the archive (exit code $LASTEXITCODE)."
        }

        $artifactName = "backup-windows-$runId.tar.gz.age"
        $artifactPath = Join-Path $destination $artifactName
        $checksumPath = "$artifactPath.sha256"
        $encryptedPartialPath = Join-Path $destination ".$runId.age.partial"
        $checksumPartialPath = Join-Path $destination ".$runId.sha256.partial"

        $ageArguments = @()
        foreach ($recipient in @($configuration.encryption.recipients)) {
            if ([string]$recipient -match 'REEMPLAZAR') {
                throw 'An example age recipient cannot be used.'
            }
            $ageArguments += @('--recipient', [string]$recipient)
        }
        $ageArguments += @('--output', $encryptedPartialPath, $archivePath)
        & $ageCommand.Source @ageArguments
        if ($LASTEXITCODE -ne 0 -or -not (Test-Path -LiteralPath $encryptedPartialPath -PathType Leaf)) {
            throw "age encryption failed (exit code $LASTEXITCODE)."
        }

        Move-Item -LiteralPath $encryptedPartialPath -Destination $artifactPath
        $hash = (Get-FileHash -LiteralPath $artifactPath -Algorithm SHA256).Hash.ToLowerInvariant()
        [System.IO.File]::WriteAllText($checksumPartialPath, "$hash  $artifactName`n", [System.Text.UTF8Encoding]::new($false))
        Move-Item -LiteralPath $checksumPartialPath -Destination $checksumPath

        if ($configuration.integrity.verify_after_transfer -eq $true) {
            $verifiedHash = (Get-FileHash -LiteralPath $artifactPath -Algorithm SHA256).Hash.ToLowerInvariant()
            if ($verifiedHash -ne $hash) {
                throw 'Post-transfer SHA-256 verification failed.'
            }
        }
        Write-BackupLog -Level INFO -Message "Encrypted backup completed and verified: $artifactName"
    } finally {
        if ($lock) {
            $lock.Dispose()
        }
    }
    exit 0
} catch {
    try {
        Write-BackupLog -Level ERROR -Message $_.Exception.Message
    } catch {
        [Console]::Error.WriteLine($_.Exception.Message)
    }
    exit 1
} finally {
    foreach ($temporaryPath in @($stagingPath, $archivePath, $encryptedPartialPath, $checksumPartialPath)) {
        if ($temporaryPath -and (Test-Path -LiteralPath $temporaryPath)) {
            Remove-Item -LiteralPath $temporaryPath -Recurse -Force -ErrorAction SilentlyContinue
        }
    }
}
