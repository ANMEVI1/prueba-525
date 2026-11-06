[CmdletBinding()]
param(
    [string] $TaskName = 'IESP-Encrypted-Backup'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

try {
    $task = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
    if (-not $task) {
        throw "Scheduled task '$TaskName' does not exist."
    }
    Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
    Write-Output "Removed scheduled task '$TaskName'."
} catch {
    [Console]::Error.WriteLine($_.Exception.Message)
    exit 1
}
