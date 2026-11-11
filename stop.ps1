#requires -Version 5.1
<#
    Classybazar - free the project ports on Windows.
    Only needed if start.ps1 was killed uncleanly and something is still
    holding port 8000 or 3000.

        .\stop.ps1
        .\stop.ps1 -ApiPort 8001 -WebPort 3001
#>

[CmdletBinding()]
param(
    [int]$ApiPort = 8000,
    [int]$WebPort = 3000
)

function Get-ListenerPids {
    param([int]$Port)
    try {
        $conns = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
        if ($conns) { return @($conns | Select-Object -ExpandProperty OwningProcess -Unique) }
        return @()
    } catch {
        # Fallback for systems without the NetTCPIP module.
        $lines = netstat -ano | Select-String ":$Port\s.*LISTENING"
        $ids = @()
        foreach ($line in $lines) {
            $parts = ($line.ToString() -split '\s+') | Where-Object { $_ -ne '' }
            if ($parts.Count -gt 0) { $ids += $parts[-1] }
        }
        return @($ids | Sort-Object -Unique)
    }
}

Write-Host ""
foreach ($port in @($ApiPort, $WebPort)) {
    $procIds = Get-ListenerPids -Port $port

    if ($procIds.Count -eq 0) {
        Write-Host "  Port $port already free" -ForegroundColor Green
        continue
    }

    foreach ($procId in $procIds) {
        $name = try { (Get-Process -Id $procId -ErrorAction Stop).ProcessName } catch { 'unknown' }
        Write-Host "  Stopping port $port  (pid $procId, $name)" -ForegroundColor Yellow
        # /T also kills children, which npm and php artisan serve both spawn.
        & taskkill /PID $procId /T /F 2>$null | Out-Null
    }
}

Start-Sleep -Milliseconds 500

$stillBusy = @()
foreach ($port in @($ApiPort, $WebPort)) {
    if ((Get-ListenerPids -Port $port).Count -gt 0) { $stillBusy += $port }
}

Write-Host ""
if ($stillBusy.Count -gt 0) {
    Write-Host "  Still in use: $($stillBusy -join ', ')" -ForegroundColor Red
    Write-Host "  Try running PowerShell as Administrator." -ForegroundColor Red
} else {
    Write-Host "  Done." -ForegroundColor Green
}
Write-Host ""
