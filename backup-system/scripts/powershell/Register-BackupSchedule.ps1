[CmdletBinding()]
param(
    [string] $ConfigPath = (Join-Path $PSScriptRoot '..\..\config\backup.config.json'),
    [string] $TaskName = 'IESP-Encrypted-Backup'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

try {
    if (-not (Test-Path -LiteralPath $ConfigPath -PathType Leaf)) {
        throw "Configuration file does not exist: $ConfigPath"
    }
    $configPathResolved = (Resolve-Path -LiteralPath $ConfigPath).Path
    $configuration = Get-Content -LiteralPath $configPathResolved -Raw | ConvertFrom-Json
    if ($configuration.enabled -ne $true -or
        $configuration.schedule.frequency -ne 'daily' -or
        $configuration.encryption.enabled -ne $true -or
        $configuration.integrity.verify_after_transfer -ne $true) {
        throw 'Enable a daily encrypted backup with integrity verification before registering the task.'
    }
    if ([string]$configuration.platform_profiles.windows.secondary_destination -match 'REEMPLAZAR' -or
        [string]$configuration.logging.production_log_directory -match 'REEMPLAZAR' -or
        (@($configuration.encryption.recipients) -join ',') -match 'REEMPLAZAR') {
        throw 'Replace all example destinations, recipients, and log paths before registering the task.'
    }
    if ($configuration.schedule.local_time -notmatch '^(?:[01]\d|2[0-3]):[0-5]\d$') {
        throw 'Schedule local_time must use 24-hour HH:mm format.'
    }

    $windowsTimeZoneId = switch ([string]$configuration.timezone) {
        'America/Lima' { 'SA Pacific Standard Time'; break }
        default { [string]$configuration.timezone }
    }
    $configuredTimeZone = [TimeZoneInfo]::FindSystemTimeZoneById($windowsTimeZoneId)
    if ($configuredTimeZone.Id -ne [TimeZoneInfo]::Local.Id) {
        throw "Server local timezone '$([TimeZoneInfo]::Local.Id)' does not match configured timezone '$($configuredTimeZone.Id)'. Set the server timezone before scheduling."
    }

    if (-not (Test-Path -LiteralPath $configuration.platform_profiles.windows.secondary_destination -PathType Container)) {
        throw 'Secondary destination must already exist and be reachable.'
    }

    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = [Security.Principal.WindowsPrincipal]::new($identity)
    if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
        throw 'Run this command in an elevated PowerShell session to register a SYSTEM task.'
    }
    if (Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue) {
        throw "Task '$TaskName' already exists. Review/remove it before re-registering."
    }

    $scheduleParts = [string]$configuration.schedule.local_time -split ':'
    $triggerTime = [DateTime]::Today.AddHours([int]$scheduleParts[0]).AddMinutes([int]$scheduleParts[1])
    $actionScript = Join-Path $PSScriptRoot 'Invoke-ScheduledBackup.ps1'
    $action = New-ScheduledTaskAction -Execute (Join-Path $PSHOME 'powershell.exe') `
        -Argument "-NoProfile -NonInteractive -ExecutionPolicy RemoteSigned -File `"$actionScript`" -ConfigPath `"$configPathResolved`""
    $trigger = New-ScheduledTaskTrigger -Daily -At $triggerTime
    $taskPrincipal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest
    $settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -MultipleInstances IgnoreNew
    $description = 'Runs the IESP encrypted backup and, when enabled, verified-artifact retention.'

    Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger `
        -Principal $taskPrincipal -Settings $settings -Description $description | Out-Null
    Write-Output "Registered daily task '$TaskName' for $($configuration.schedule.local_time) ($windowsTimeZoneId)."
    Write-Output 'The task runs as SYSTEM; grant that identity only the required source/destination permissions.'
} catch {
    [Console]::Error.WriteLine($_.Exception.Message)
    exit 1
}
