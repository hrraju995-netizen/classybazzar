#requires -Version 5.1
<#
    Classybazar - start both servers on Windows

        .\start.ps1              development (hot reload)
        .\start.ps1 -Prod        production build, served locally
        .\start.ps1 -ApiPort 8001 -WebPort 3001

    Ctrl+C stops both. Logs go to logs\api.log and logs\web.log.

    If PowerShell refuses to run this file:
        powershell -ExecutionPolicy Bypass -File .\start.ps1
#>

[CmdletBinding()]
param(
    [switch]$Prod,
    [int]$ApiPort = 8000,
    [int]$WebPort = 3000
)

$ErrorActionPreference = 'Stop'

$Root      = Split-Path -Parent $MyInvocation.MyCommand.Definition
$ApiDir    = Join-Path $Root 'api'
$ClientDir = Join-Path $Root 'client'
$LogDir    = Join-Path $Root 'logs'

function Write-Ok   { param([string]$Text) Write-Host "  [ok] $Text" -ForegroundColor Green }
function Write-Warn { param([string]$Text) Write-Host "  [!]  $Text" -ForegroundColor Yellow }
function Write-Fail {
    param([string]$Text)
    Write-Host ""
    Write-Host "  [x] $Text" -ForegroundColor Red
    Write-Host ""
    exit 1
}

# Laragon's binaries are not on PATH outside its own terminal.
function Add-LaragonToPath {
    foreach ($binRoot in @('C:\laragon\bin', 'D:\laragon\bin', "$env:SystemDrive\laragon\bin")) {
        if (-not (Test-Path $binRoot)) { continue }
        foreach ($c in @(
            @{ Name = 'php';      Pattern = 'php\php-*';     Exe = 'php.exe' },
            @{ Name = 'composer'; Pattern = 'composer';      Exe = 'composer.bat' },
            @{ Name = 'node';     Pattern = 'nodejs\node-*'; Exe = 'node.exe' }
        )) {
            # Never shadow a tool that already resolves and works.
            if (Get-Command $c.Name -ErrorAction SilentlyContinue) { continue }
            $dirs = Get-ChildItem -Path (Join-Path $binRoot $c.Pattern) -Directory -ErrorAction SilentlyContinue
            if (-not $dirs) {
                $direct = Join-Path $binRoot $c.Pattern
                if (Test-Path $direct) { $dirs = @(Get-Item $direct) }
            }
            if (-not $dirs) { continue }
            $dir = $dirs | Sort-Object Name -Descending | Select-Object -First 1
            if (Test-Path (Join-Path $dir.FullName $c.Exe)) {
                $env:Path = "$($dir.FullName);$env:Path"
            }
        }
    }
}

function Test-PortBusy {
    param([int]$Port)
    try {
        $conn = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
        return ($null -ne $conn)
    } catch {
        # Get-NetTCPConnection is missing on very old Windows - fall back.
        $out = netstat -ano | Select-String ":$Port\s.*LISTENING"
        return ($null -ne $out)
    }
}

function Wait-ForUrl {
    param([string]$Url, [int]$TimeoutSeconds, [string]$LogFile, [string]$Label)
    for ($i = 0; $i -lt $TimeoutSeconds; $i++) {
        try {
            Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 3 | Out-Null
            return $true
        } catch {
            Start-Sleep -Seconds 1
        }
    }
    if (Test-Path $LogFile) { Get-Content $LogFile -Tail 30 | Write-Host }
    Write-Fail "$Label failed to start. See $LogFile"
}

Add-LaragonToPath
New-Item -ItemType Directory -Path $LogDir -Force | Out-Null

if (-not (Test-Path (Join-Path $ApiDir 'vendor')))        { Write-Fail "API dependencies missing. Run .\setup.ps1 first." }
if (-not (Test-Path (Join-Path $ClientDir 'node_modules'))) { Write-Fail "Storefront dependencies missing. Run .\setup.ps1 first." }

if (Test-PortBusy $ApiPort) { Write-Fail "Port $ApiPort is already in use. Run .\stop.ps1, or start with: .\start.ps1 -ApiPort 8001" }
if (Test-PortBusy $WebPort) { Write-Fail "Port $WebPort is already in use. Run .\stop.ps1, or start with: .\start.ps1 -WebPort 3001" }

$mode = if ($Prod) { 'production' } else { 'development' }
Write-Host ""
Write-Host "Starting Classybazar ($mode)" -ForegroundColor White
Write-Host ""

$procs = @()
$apiLog   = Join-Path $LogDir 'api.log'
$webLog   = Join-Path $LogDir 'web.log'
$buildLog = Join-Path $LogDir 'build.log'

try {
    # ---- API -------------------------------------------------------------
    $procs += Start-Process -FilePath 'php' `
        -ArgumentList 'artisan', 'serve', "--port=$ApiPort" `
        -WorkingDirectory $ApiDir `
        -NoNewWindow -PassThru `
        -RedirectStandardOutput $apiLog `
        -RedirectStandardError (Join-Path $LogDir 'api.err.log')

    Wait-ForUrl -Url "http://127.0.0.1:$ApiPort/api/settings" -TimeoutSeconds 40 -LogFile $apiLog -Label 'API' | Out-Null
    Write-Ok "API        http://127.0.0.1:$ApiPort/api"

    # ---- Storefront ------------------------------------------------------
    # npm on Windows is a .cmd shim, so it must be invoked as npm.cmd.
    if ($Prod) {
        Write-Host "  Building production bundle (this takes a minute)..."
        $build = Start-Process -FilePath 'npm.cmd' -ArgumentList 'run', 'build' `
            -WorkingDirectory $ClientDir -NoNewWindow -PassThru -Wait `
            -RedirectStandardOutput $buildLog `
            -RedirectStandardError (Join-Path $LogDir 'build.err.log')

        if ($build.ExitCode -ne 0) {
            if (Test-Path $buildLog) { Get-Content $buildLog -Tail 30 | Write-Host }
            Write-Fail "Build failed. See $buildLog"
        }
        Write-Ok "Production build complete"

        $procs += Start-Process -FilePath 'npm.cmd' `
            -ArgumentList 'run', 'start', '--', '--port', "$WebPort" `
            -WorkingDirectory $ClientDir -NoNewWindow -PassThru `
            -RedirectStandardOutput $webLog `
            -RedirectStandardError (Join-Path $LogDir 'web.err.log')
    } else {
        $procs += Start-Process -FilePath 'npm.cmd' `
            -ArgumentList 'run', 'dev', '--', '--port', "$WebPort" `
            -WorkingDirectory $ClientDir -NoNewWindow -PassThru `
            -RedirectStandardOutput $webLog `
            -RedirectStandardError (Join-Path $LogDir 'web.err.log')
    }

    Wait-ForUrl -Url "http://127.0.0.1:$WebPort" -TimeoutSeconds 120 -LogFile $webLog -Label 'Storefront' | Out-Null
    Write-Ok "Storefront http://localhost:$WebPort"

    Write-Host ""
    Write-Host "  Both servers are running." -ForegroundColor White
    Write-Host ""
    Write-Host "  Storefront   http://localhost:$WebPort"
    Write-Host "  API          http://127.0.0.1:$ApiPort/api"
    Write-Host "  Admin panel  http://127.0.0.1:$ApiPort/admin"
    Write-Host "  Logs         logs\api.log, logs\web.log"
    Write-Host ""
    Write-Host "  Admin        admin@classybazar.com / password"
    Write-Host "  Customer     customer@classybazar.com / password"
    Write-Host ""
    Write-Host "  Press Ctrl+C to stop both."
    Write-Host ""

    while ($true) {
        Start-Sleep -Seconds 1
        foreach ($p in $procs) {
            if ($p -and $p.HasExited) {
                Write-Warn "A server exited unexpectedly - check the logs folder."
                return
            }
        }
    }
}
finally {
    Write-Host ""
    Write-Host "  Shutting down..."
    foreach ($p in $procs) {
        if ($p -and -not $p.HasExited) {
            # /T kills the child processes too - `npm run dev` spawns node.
            & taskkill /PID $p.Id /T /F 2>$null | Out-Null
        }
    }
    Write-Host "  Stopped."
    Write-Host ""
}
