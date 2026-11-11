#requires -Version 5.1
<#
    Classybazar - environment diagnostic.

    Run this when setup.ps1 fails and paste the whole output back.

        powershell -ExecutionPolicy Bypass -File .\doctor.ps1
#>

$ErrorActionPreference = 'Continue'

function Section { param([string]$T) Write-Host ""; Write-Host "--- $T ---" -ForegroundColor Cyan }

Write-Host ""
Write-Host "Classybazar environment report" -ForegroundColor White

Section "Windows / PowerShell"
Write-Host "  PowerShell : $($PSVersionTable.PSVersion)"
Write-Host "  OS         : $([System.Environment]::OSVersion.VersionString)"
Write-Host "  64-bit     : $([System.Environment]::Is64BitOperatingSystem)"
Write-Host "  Policy     : $(Get-ExecutionPolicy -Scope CurrentUser)"
Write-Host "  Folder     : $(Get-Location)"

Section "Laragon install"
foreach ($root in @('C:\laragon\bin', 'D:\laragon\bin')) {
    if (Test-Path $root) {
        Write-Host "  Found: $root" -ForegroundColor Green
        foreach ($sub in @('php', 'nodejs', 'composer')) {
            $path = Join-Path $root $sub
            if (Test-Path $path) {
                $kids = Get-ChildItem $path -Directory -ErrorAction SilentlyContinue |
                        Select-Object -ExpandProperty Name
                if ($kids) {
                    Write-Host "    $sub : $($kids -join ', ')"
                } else {
                    Write-Host "    $sub : (present, no version subfolders)"
                }
            } else {
                Write-Host "    $sub : missing" -ForegroundColor Yellow
            }
        }
    } else {
        Write-Host "  Not found: $root"
    }
}

Section "Tools on PATH"
foreach ($tool in @('php', 'composer', 'node', 'npm')) {
    $cmd = Get-Command $tool -ErrorAction SilentlyContinue
    if ($cmd) {
        Write-Host "  $tool -> $($cmd.Source)" -ForegroundColor Green
    } else {
        Write-Host "  $tool -> NOT FOUND" -ForegroundColor Yellow
    }
}

Section "php --version (stdout + stderr)"
$phpCmd = Get-Command php -ErrorAction SilentlyContinue
if ($phpCmd) {
    $out = (& $phpCmd.Source --version 2>&1 | Out-String).Trim()
    if ($out) { Write-Host $out } else { Write-Host "  (no output at all)" -ForegroundColor Red }

    Section "php --ini"
    $ini = (& $phpCmd.Source --ini 2>&1 | Out-String).Trim()
    if ($ini) { Write-Host $ini } else { Write-Host "  (no output)" -ForegroundColor Red }

    Section "extensions"
    # Compare line by line. Matching with a regex against Out-String output
    # silently fails on Windows: the line ends with \r\n, so '^ext$' never
    # matches because $ anchors before \n while \r is still in the way.
    $mods = @(& $phpCmd.Source -m 2>&1) | ForEach-Object { "$_".Trim() }
    foreach ($ext in @('pdo_sqlite', 'sqlite3', 'pdo_mysql', 'openssl', 'mbstring', 'curl', 'fileinfo', 'zip', 'tokenizer')) {
        if ($mods -contains $ext) {
            Write-Host "  $ext : enabled" -ForegroundColor Green
        } else {
            Write-Host "  $ext : MISSING" -ForegroundColor Yellow
        }
    }

    Section "raw php -m output"
    $mods | Where-Object { $_ -ne '' } | ForEach-Object { Write-Host "  $_" }
} else {
    Write-Host "  php not on PATH, skipping" -ForegroundColor Yellow
}

Section "node / npm"
foreach ($pair in @(@('node','--version'), @('npm.cmd','--version'))) {
    $cmd = Get-Command $pair[0] -ErrorAction SilentlyContinue
    if ($cmd) {
        $v = (& $cmd.Source $pair[1] 2>&1 | Out-String).Trim()
        Write-Host "  $($pair[0]) : $(if ($v) { $v } else { '(no output)' })"
    } else {
        Write-Host "  $($pair[0]) : NOT FOUND" -ForegroundColor Yellow
    }
}

Section "Project files"
foreach ($p in @('setup.ps1', 'api\artisan', 'api\.env', 'api\vendor', 'client\package.json', 'client\node_modules', 'client\.env.local')) {
    if (Test-Path $p) {
        Write-Host "  $p : present" -ForegroundColor Green
    } else {
        Write-Host "  $p : missing"
    }
}

Section "Ports"
foreach ($port in @(8000, 3000)) {
    $busy = $null
    try { $busy = Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue } catch { }
    if ($busy) {
        Write-Host "  $port : IN USE (pid $($busy.OwningProcess -join ', '))" -ForegroundColor Yellow
    } else {
        Write-Host "  $port : free" -ForegroundColor Green
    }
}

Write-Host ""
Write-Host "Copy everything above and send it back." -ForegroundColor White
Write-Host ""
