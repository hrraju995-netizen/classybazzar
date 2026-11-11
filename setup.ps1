#requires -Version 5.1
<#
    Classybazar - local setup for Windows (Laragon friendly)

        .\setup.ps1            first run, or to repair an install
        .\setup.ps1 -Fresh     drop the database and reseed from scratch

    If PowerShell refuses to run this file, launch it like so:
        powershell -ExecutionPolicy Bypass -File .\setup.ps1
#>

[CmdletBinding()]
param(
    [switch]$Fresh
)

$ErrorActionPreference = 'Stop'

$Root      = Split-Path -Parent $MyInvocation.MyCommand.Definition
$ApiDir    = Join-Path $Root 'api'
$ClientDir = Join-Path $Root 'client'

function Write-Step { param([string]$Text) Write-Host ""; Write-Host "> $Text" -ForegroundColor Cyan }
function Write-Ok   { param([string]$Text) Write-Host "  [ok] $Text" -ForegroundColor Green }
function Write-Warn { param([string]$Text) Write-Host "  [!]  $Text" -ForegroundColor Yellow }
function Write-Fail {
    param([string]$Text)
    Write-Host ""
    Write-Host "  [x] $Text" -ForegroundColor Red
    Write-Host ""
    exit 1
}

# ---------------------------------------------------------------------------
# Laragon puts PHP, Composer and Node under C:\laragon\bin but only adds them
# to PATH inside its own terminal. Plain PowerShell usually cannot see them,
# so find them ourselves and prepend to PATH for this session.
# ---------------------------------------------------------------------------
function Add-LaragonToPath {
    $laragonRoots = @('C:\laragon\bin', 'D:\laragon\bin', "$env:SystemDrive\laragon\bin")
    $added = @()

    foreach ($binRoot in $laragonRoots) {
        if (-not (Test-Path $binRoot)) { continue }

        $candidates = @(
            @{ Name = 'php';      Pattern = 'php\php-*';           Exe = 'php.exe' },
            @{ Name = 'composer'; Pattern = 'composer';            Exe = 'composer.bat' },
            @{ Name = 'node';     Pattern = 'nodejs\node-*';       Exe = 'node.exe' }
        )

        foreach ($c in $candidates) {
            # Never shadow a tool that already works. Prepending a broken or
            # ancient Laragon build in front of a healthy PATH entry is worse
            # than doing nothing at all.
            if (Get-Command $c.Name -ErrorAction SilentlyContinue) { continue }

            $dirs = Get-ChildItem -Path (Join-Path $binRoot $c.Pattern) -Directory -ErrorAction SilentlyContinue
            if (-not $dirs) {
                $direct = Join-Path $binRoot $c.Pattern
                if (Test-Path $direct) { $dirs = @(Get-Item $direct) }
            }
            if (-not $dirs) { continue }

            # Newest version wins when several are installed side by side.
            $dir = $dirs | Sort-Object Name -Descending | Select-Object -First 1
            if (Test-Path (Join-Path $dir.FullName $c.Exe)) {
                $env:Path = "$($dir.FullName);$env:Path"
                $added += $c.Name
            }
        }

        if ($added.Count -gt 0) { break }
    }

    return $added
}

# ---------------------------------------------------------------------------
Write-Host ""
Write-Host "Classybazar - local setup" -ForegroundColor White
Write-Step "Checking prerequisites"

$found = Add-LaragonToPath
if ($found.Count -gt 0) {
    Write-Ok ("Laragon binaries found: " + ($found -join ', '))
}

foreach ($tool in @('php', 'composer', 'node', 'npm')) {
    if (-not (Get-Command $tool -ErrorAction SilentlyContinue)) {
        switch ($tool) {
            'php'      { Write-Fail "PHP not found. Open Laragon > Menu > Tools > Path > Add Laragon to Path, then reopen PowerShell." }
            'composer' { Write-Fail "Composer not found. Laragon ships it - enable it in Laragon, or install from getcomposer.org." }
            'node'     { Write-Fail "Node.js not found. Install Node 20+ from nodejs.org (or enable it in Laragon)." }
            'npm'      { Write-Fail "npm not found - it ships with Node.js. Reinstall Node." }
        }
    }
}

# Read versions from --version output. Two things go wrong on Windows here:
# passing snippets with embedded double quotes gets mangled by the command
# line, and a php.exe that cannot start (missing VC++ runtime, broken php.ini)
# writes to stderr and leaves stdout empty. So: resolve the real exe path,
# merge stderr into stdout, and show both if parsing fails.
$phpCmd = Get-Command php -ErrorAction SilentlyContinue
$phpExe = if ($phpCmd) { $phpCmd.Source } else { 'php' }

$phpRaw = ''
try { $phpRaw = (& $phpExe --version 2>&1 | Out-String).Trim() } catch { $phpRaw = "$_" }

if ($phpRaw -notmatch 'PHP\s+(\d+)\.(\d+)\.(\d+)') {
    Write-Host ""
    Write-Host "  php.exe used : $phpExe" -ForegroundColor Yellow
    Write-Host "  output       : $(if ($phpRaw) { $phpRaw } else { '(nothing at all)' })" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "  Most likely one of:" -ForegroundColor Yellow
    Write-Host "    - php.exe cannot start (missing Visual C++ Redistributable):" -ForegroundColor Yellow
    Write-Host "      install https://aka.ms/vs/17/release/vc_redist.x64.exe and retry" -ForegroundColor Yellow
    Write-Host "    - php.ini has a fatal error - run '$phpExe --version' by hand to see it" -ForegroundColor Yellow
    Write-Host "    - PHP is not enabled in Laragon (tray icon > PHP > Version)" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "  Run .\doctor.ps1 for a full diagnostic." -ForegroundColor Yellow
    Write-Fail "Could not read the PHP version."
}

$phpMajor   = [int]$Matches[1]
$phpMinor   = [int]$Matches[2]
$phpVersion = "$phpMajor.$phpMinor.$($Matches[3])"

if ($phpMajor -lt 8 -or ($phpMajor -eq 8 -and $phpMinor -lt 2)) {
    Write-Host ""
    Write-Host "  PHP $phpVersion found at:" -ForegroundColor Yellow
    Write-Host "    $phpExe" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "  Laravel 12 needs PHP 8.2 or newer. Versions installed in Laragon:" -ForegroundColor Yellow
    foreach ($r in @('C:\laragon\bin\php', 'D:\laragon\bin\php')) {
        if (Test-Path $r) {
            Get-ChildItem $r -Directory -ErrorAction SilentlyContinue |
                ForEach-Object { Write-Host "    - $($_.Name)" -ForegroundColor Yellow }
        }
    }
    Write-Host ""
    Write-Host "  If none of those is 8.2+, install one:" -ForegroundColor Yellow
    Write-Host "    1. Download 'VS17 x64 Thread Safe' from https://windows.php.net/download" -ForegroundColor Yellow
    Write-Host "    2. Extract it into C:\laragon\bin\php\" -ForegroundColor Yellow
    Write-Host "    3. Laragon tray icon > PHP > Version > pick the new one" -ForegroundColor Yellow
    Write-Host "    4. Reopen PowerShell and rerun this script" -ForegroundColor Yellow
    Write-Host ""
    Write-Fail "PHP 8.2+ required."
}
Write-Ok "PHP $phpVersion  ($phpExe)"

$nodeCmd = Get-Command node -ErrorAction SilentlyContinue
$nodeExe = if ($nodeCmd) { $nodeCmd.Source } else { 'node' }

$nodeRaw = ''
try { $nodeRaw = (& $nodeExe --version 2>&1 | Out-String).Trim() } catch { $nodeRaw = "$_" }

if ($nodeRaw -notmatch 'v?(\d+)\.') {
    Write-Fail "Could not read the Node version. node.exe used: $nodeExe - output: $(if ($nodeRaw) { $nodeRaw } else { '(nothing at all)' })"
}
if ([int]$Matches[1] -lt 20) {
    Write-Fail "Node $nodeRaw found, but Next.js 16 needs Node 20+."
}
Write-Ok "Node $nodeRaw"

# ---------------------------------------------------------------------------
# pdo_sqlite is the one extension Laragon usually ships but leaves commented
# out in php.ini. Enable it automatically rather than making the user hunt.
# ---------------------------------------------------------------------------
$modules = (& php -m) -join "`n"
if ($modules -notmatch '(?im)^pdo_sqlite$') {
    Write-Warn "PHP is missing the pdo_sqlite extension - trying to enable it"

    # No embedded double quotes here, so this survives Windows arg parsing.
    $iniPath = (& php -r 'echo php_ini_loaded_file();' | Out-String).Trim()

    if (-not $iniPath -or -not (Test-Path $iniPath)) {
        Write-Fail "No php.ini is loaded, so pdo_sqlite cannot be enabled automatically. In Laragon: tray icon > PHP > php.ini, then uncomment the lines 'extension=pdo_sqlite' and 'extension=sqlite3'."
    }

    Copy-Item $iniPath "$iniPath.classybazar-backup" -Force
    $ini = Get-Content $iniPath -Raw

    # Uncomment the two extension lines if they exist but are disabled.
    $ini = $ini -replace '(?m)^\s*;\s*(extension\s*=\s*(php_)?pdo_sqlite(\.dll)?)\s*$', '$1'
    $ini = $ini -replace '(?m)^\s*;\s*(extension\s*=\s*(php_)?sqlite3(\.dll)?)\s*$',   '$1'

    Set-Content -Path $iniPath -Value $ini -Encoding UTF8

    $modules = (& php -m) -join "`n"
    if ($modules -notmatch '(?im)^pdo_sqlite$') {
        Write-Fail "Could not enable pdo_sqlite automatically. Open $iniPath and uncomment 'extension=pdo_sqlite' and 'extension=sqlite3' by hand. A backup was saved as $iniPath.classybazar-backup"
    }

    Write-Ok "pdo_sqlite enabled (php.ini backed up alongside the original)"
} else {
    Write-Ok "pdo_sqlite enabled"
}

# ---------------------------------------------------------------------------
Write-Step "Setting up the API (Laravel)"
Push-Location $ApiDir
try {
    if (-not (Test-Path 'vendor')) {
        & composer install --no-interaction --prefer-dist
        if ($LASTEXITCODE -ne 0) { Write-Fail "composer install failed - see the output above." }
        Write-Ok "Composer dependencies installed"
    } else {
        Write-Ok "Composer dependencies already present"
    }

    if (-not (Test-Path '.env')) {
        Copy-Item '.env.example' '.env'
        Write-Ok ".env created from .env.example"
    } else {
        Write-Ok ".env already exists (left untouched)"
    }

    if ((Get-Content '.env' -Raw) -notmatch 'APP_KEY=base64:') {
        & php artisan key:generate
        Write-Ok "Application key generated"
    }

    # SQLite means no MySQL database to create, no credentials to configure.
    $dbFile = Join-Path (Join-Path $ApiDir 'database') 'database.sqlite'
    if (-not (Test-Path $dbFile)) {
        New-Item -ItemType File -Path $dbFile -Force | Out-Null
    }
    Write-Ok "SQLite database file ready"

    if ($Fresh) {
        Write-Warn "-Fresh: dropping all tables and reseeding"
        & php artisan migrate:fresh --seed --no-interaction
    } else {
        & php artisan migrate --seed --no-interaction
    }
    if ($LASTEXITCODE -ne 0) { Write-Fail "Migrations failed - see the output above." }

    # storage:link uses a real symlink, which Windows blocks without admin
    # rights or Developer Mode. A directory junction does the same job and
    # needs no privileges, so fall back to that.
    $linkPath = Join-Path (Join-Path $ApiDir 'public') 'storage'
    if (-not (Test-Path $linkPath)) {
        & php artisan storage:link 2>$null
        if (-not (Test-Path $linkPath)) {
            $target = Join-Path (Join-Path (Join-Path $ApiDir 'storage') 'app') 'public'
            New-Item -ItemType Directory -Path $target -Force | Out-Null
            New-Item -ItemType Junction -Path $linkPath -Target $target -ErrorAction SilentlyContinue | Out-Null
            if (Test-Path $linkPath) {
                Write-Ok "Storage junction created (symlink needed admin rights, junction does not)"
            } else {
                Write-Warn "Could not link public/storage. Uploaded images will 404 until you run 'php artisan storage:link' in an admin terminal."
            }
        } else {
            Write-Ok "Storage symlink in place"
        }
    } else {
        Write-Ok "Storage link already in place"
    }

    # Publishes the admin panel's CSS/JS into public/. composer install
    # normally does this via filament:upgrade; running it explicitly costs
    # nothing and saves an unstyled panel if that hook was skipped.
    & php artisan filament:assets | Out-Null
    Write-Ok "Admin panel assets published"

    & php artisan optimize:clear | Out-Null
}
finally {
    Pop-Location
}

# ---------------------------------------------------------------------------
Write-Step "Setting up the storefront (Next.js)"
Push-Location $ClientDir
try {
    if (-not (Test-Path 'node_modules')) {
        & npm install --no-audit --no-fund
        if ($LASTEXITCODE -ne 0) { Write-Fail "npm install failed - see the output above." }
        Write-Ok "npm dependencies installed"
    } else {
        Write-Ok "npm dependencies already present"
    }

    if (-not (Test-Path '.env.local')) {
        Copy-Item '.env.example' '.env.local'
        Write-Ok ".env.local created"
    } else {
        Write-Ok ".env.local already exists (left untouched)"
    }
}
finally {
    Pop-Location
}

# ---------------------------------------------------------------------------
Write-Step "Setup complete"
Write-Host ""
Write-Host "  Start everything with:  " -NoNewline
Write-Host ".\start.ps1" -ForegroundColor White
Write-Host ""
Write-Host "  Storefront   http://localhost:3000"
Write-Host "  API          http://127.0.0.1:8000/api"
Write-Host "  Admin panel  http://127.0.0.1:8000/admin"
Write-Host ""
Write-Host "  Admin        admin@classybazar.com / password"
Write-Host "  Customer     customer@classybazar.com / password"
Write-Host ""
