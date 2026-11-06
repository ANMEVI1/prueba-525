[CmdletBinding()]
param(
    [Parameter(Mandatory)][string] $ArtifactPath,
    [Parameter(Mandatory)][string] $IdentityPath,
    [Parameter(Mandatory)][string] $TargetPath
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
$temporaryPath = $null

try {
    $artifact = (Resolve-Path -LiteralPath $ArtifactPath).Path
    $identity = (Resolve-Path -LiteralPath $IdentityPath).Path
    $checksumPath = "$artifact.sha256"
    if (-not (Test-Path -LiteralPath $checksumPath -PathType Leaf)) {
        throw 'Checksum sidecar is missing.'
    }
    if (-not (Test-Path -LiteralPath $identity -PathType Leaf)) {
        throw 'Age identity file does not exist.'
    }
    if (Test-Path -LiteralPath $TargetPath) {
        throw 'Restore target must not already exist; this script never overwrites data.'
    }

    $checksumLine = Get-Content -LiteralPath $checksumPath -Raw
    if ($checksumLine -notmatch '^(?<hash>[A-Fa-f0-9]{64})\s+') {
        throw 'Checksum sidecar has an invalid format.'
    }
    $expectedHash = $Matches.hash.ToLowerInvariant()
    $actualHash = (Get-FileHash -LiteralPath $artifact -Algorithm SHA256).Hash.ToLowerInvariant()
    if ($actualHash -ne $expectedHash) {
        throw 'Artifact SHA-256 verification failed.'
    }

    $ageCommand = Get-Command 'age.exe' -ErrorAction Stop
    $tarCommand = Get-Command 'tar.exe' -ErrorAction Stop
    $targetParent = Split-Path -Parent ([System.IO.Path]::GetFullPath($TargetPath))
    if (-not (Test-Path -LiteralPath $targetParent -PathType Container)) {
        throw 'Restore target parent must already exist.'
    }
    $temporaryPath = Join-Path $targetParent ('.restore-' + [Guid]::NewGuid().ToString('N'))
    $null = New-Item -ItemType Directory -Path $temporaryPath
    $decryptedArchive = Join-Path $temporaryPath 'backup.tar.gz'
    $extractPath = Join-Path $temporaryPath 'contents'
    $null = New-Item -ItemType Directory -Path $extractPath

    & $ageCommand.Source --decrypt --identity $identity --output $decryptedArchive $artifact
    if ($LASTEXITCODE -ne 0 -or -not (Test-Path -LiteralPath $decryptedArchive -PathType Leaf)) {
        throw "age decryption failed (exit code $LASTEXITCODE)."
    }
    $archiveEntries = @(& $tarCommand.Source -tzf $decryptedArchive)
    if ($LASTEXITCODE -ne 0) {
        throw 'Archive listing validation failed.'
    }
    foreach ($entry in $archiveEntries) {
        if ([string]$entry -match '^(?:/|[A-Za-z]:|\\\\)|(?:^|[\\/])\.\.(?:[\\/]|$)') {
            throw 'Archive contains an absolute or parent-traversal path; refusing extraction.'
        }
    }
    $verboseEntries = @(& $tarCommand.Source -tvzf $decryptedArchive)
    if ($LASTEXITCODE -ne 0) {
        throw 'Archive entry-type validation failed.'
    }
    foreach ($entry in $verboseEntries) {
        if ([string]$entry -notmatch '^[-d]') {
            throw 'Archive contains a link or unsupported special file; refusing extraction.'
        }
    }
    & $tarCommand.Source -xzf $decryptedArchive -C $extractPath
    if ($LASTEXITCODE -ne 0 -or -not (Test-Path -LiteralPath (Join-Path $extractPath 'backup-manifest.json') -PathType Leaf)) {
        throw 'Archive extraction or manifest validation failed.'
    }

    Move-Item -LiteralPath $extractPath -Destination $TargetPath
    Write-Output "Restore completed in: $TargetPath"
} catch {
    [Console]::Error.WriteLine($_.Exception.Message)
    exit 1
} finally {
    if ($temporaryPath -and (Test-Path -LiteralPath $temporaryPath)) {
        Remove-Item -LiteralPath $temporaryPath -Recurse -Force -ErrorAction SilentlyContinue
    }
}
