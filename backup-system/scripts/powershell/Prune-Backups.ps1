[CmdletBinding()]
param(
    [string] $ConfigPath = (Join-Path $PSScriptRoot '..\..\config\backup.config.json'),
    [switch] $Apply
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

try {
    if (-not (Test-Path -LiteralPath $ConfigPath -PathType Leaf)) {
        throw "Configuration file does not exist: $ConfigPath"
    }
    $configuration = Get-Content -LiteralPath $ConfigPath -Raw | ConvertFrom-Json
    $destination = [string]$configuration.platform_profiles.windows.secondary_destination
    if ($destination -match 'REEMPLAZAR' -or -not (Test-Path -LiteralPath $destination -PathType Container)) {
        throw 'The configured Windows destination must be replaced and reachable.'
    }
    if ($Apply -and $configuration.retention.enabled -ne $true) {
        throw 'Retention is disabled in configuration; refusing to delete artifacts.'
    }

    $dailyLimit = [int]$configuration.retention.daily_copies
    $weeklyLimit = [int]$configuration.retention.weekly_copies
    $monthlyLimit = [int]$configuration.retention.monthly_copies
    if ($dailyLimit -lt 1 -or $weeklyLimit -lt 1 -or $monthlyLimit -lt 1) {
        throw 'Each retention bucket must keep at least one copy.'
    }

    $artifacts = @()
    foreach ($file in Get-ChildItem -LiteralPath $destination -File -Filter 'backup-windows-*.tar.gz.age') {
        if ($file.Name -notmatch '^backup-windows-(?<stamp>\d{8}T\d{6}Z)-.*\.tar\.gz\.age$') {
            continue
        }
        $stampText = $Matches.stamp
        $checksumPath = "$($file.FullName).sha256"
        if (-not (Test-Path -LiteralPath $checksumPath -PathType Leaf)) {
            throw "Checksum sidecar is missing; refusing retention: $($file.Name)"
        }
        $checksumText = Get-Content -LiteralPath $checksumPath -Raw
        if ($checksumText -notmatch '^(?<hash>[A-Fa-f0-9]{64})\s+') {
            throw "Checksum sidecar is invalid; refusing retention: $($file.Name)"
        }
        $expectedHash = $Matches.hash.ToLowerInvariant()
        $actualHash = (Get-FileHash -LiteralPath $file.FullName -Algorithm SHA256).Hash.ToLowerInvariant()
        if ($actualHash -ne $expectedHash) {
            throw "Checksum verification failed; refusing retention: $($file.Name)"
        }
        $timestamp = [DateTime]::ParseExact($stampText, 'yyyyMMddTHHmmssZ', [Globalization.CultureInfo]::InvariantCulture, [Globalization.DateTimeStyles]::AssumeUniversal).ToUniversalTime()
        $artifacts += [PSCustomObject]@{ File = $file; Timestamp = $timestamp }
    }

    if ($artifacts.Count -eq 0) {
        Write-Output 'No verified Windows backup artifacts found; nothing to prune.'
        exit 0
    }

    $artifacts = @($artifacts | Sort-Object Timestamp -Descending)
    $keep = [System.Collections.Generic.HashSet[string]]::new([StringComparer]::OrdinalIgnoreCase)
    $dailyBuckets = [System.Collections.Generic.HashSet[string]]::new([StringComparer]::Ordinal)
    $weeklyBuckets = [System.Collections.Generic.HashSet[string]]::new([StringComparer]::Ordinal)
    $monthlyBuckets = [System.Collections.Generic.HashSet[string]]::new([StringComparer]::Ordinal)
    $dailyCount = 0
    $weeklyCount = 0
    $monthlyCount = 0

    foreach ($artifact in $artifacts) {
        $stamp = $artifact.Timestamp
        $dateKey = $stamp.ToString('yyyyMMdd')
        $weekStart = $stamp.Date.AddDays(-((([int]$stamp.DayOfWeek + 6) % 7)))
        $weekKey = $weekStart.ToString('yyyyMMdd')
        $monthKey = $stamp.ToString('yyyyMM')

        if ($dailyBuckets.Add($dateKey)) {
            if ($dailyCount -lt $dailyLimit) {
                $null = $keep.Add($artifact.File.FullName)
                $dailyCount++
            }
        }
        if ($weeklyBuckets.Add($weekKey)) {
            if ($weeklyCount -lt $weeklyLimit) {
                $null = $keep.Add($artifact.File.FullName)
                $weeklyCount++
            }
        }
        if ($monthlyBuckets.Add($monthKey)) {
            if ($monthlyCount -lt $monthlyLimit) {
                $null = $keep.Add($artifact.File.FullName)
                $monthlyCount++
            }
        }
    }

    $deleteCandidates = @($artifacts | Where-Object { -not $keep.Contains($_.File.FullName) })
    foreach ($candidate in $deleteCandidates) {
        if ($Apply) {
            Remove-Item -LiteralPath $candidate.File.FullName -Force
            Remove-Item -LiteralPath "$($candidate.File.FullName).sha256" -Force
            Write-Output "Deleted verified artifact: $($candidate.File.Name)"
        } else {
            Write-Output "Would delete verified artifact: $($candidate.File.Name)"
        }
    }
    if (-not $Apply) {
        Write-Output "Dry run only: $($deleteCandidates.Count) verified artifact(s) would be deleted."
    } elseif ($deleteCandidates.Count -eq 0) {
        Write-Output 'Retention selected no artifacts for deletion.'
    }
} catch {
    [Console]::Error.WriteLine($_.Exception.Message)
    exit 1
}
