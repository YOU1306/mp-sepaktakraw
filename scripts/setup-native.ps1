#Requires -Version 5.1
<#
.SYNOPSIS
  Phase 0 bootstrap without Docker — for company laptops without admin rights.
.DESCRIPTION
  Uses Scoop-installed PHP + Composer. Local dev uses SQLite + file cache/queue
  (no MySQL/Redis install required). Production VPS still uses MySQL + Redis per ARCHITECTURE.md.
#>
$ErrorActionPreference = "Stop"

$ProjectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $ProjectRoot

function Write-Step($message) {
    Write-Host "`n>> $message" -ForegroundColor Cyan
}

function Ensure-ScoopTool {
    param([string]$Name, [string]$InstallHint)
    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "$Name not found. $InstallHint"
    }
}

# Scoop shims may not be on PATH in a fresh shell
$scoopShims = Join-Path $env:USERPROFILE "scoop\shims"
if (Test-Path $scoopShims) {
    $env:Path = "$scoopShims;$env:Path"
}

Ensure-ScoopTool "php" "Run: Set-ExecutionPolicy RemoteSigned -Scope CurrentUser; irm get.scoop.sh | iex;  scoop install php composer"
Ensure-ScoopTool "composer" "Run: scoop install composer"

Write-Step "Configuring SSL for corporate network (if needed)"
$sslFix = Join-Path $ProjectRoot "scripts\fix-corporate-ssl.ps1"
if (Test-Path $sslFix) {
    & $sslFix
}

Write-Step "Checking PHP extensions"
$required = @("openssl", "mbstring", "curl", "fileinfo", "pdo_sqlite", "zip", "intl")
$loaded = (php -m) -join "`n"
foreach ($ext in $required) {
    if ($loaded -notmatch $ext) {
        throw "PHP extension '$ext' missing. Run .\scripts\fix-corporate-ssl.ps1 and ensure php.ini is loaded."
    }
}

# Scoop PHP may not load php.ini unless copied to PHP root
$phpRoot = Join-Path $env:USERPROFILE "scoop\apps\php\current"
$iniSource = Join-Path $phpRoot "cli\php.ini"
$iniTarget = Join-Path $phpRoot "php.ini"
if ((Test-Path $iniSource) -and -not (Test-Path $iniTarget)) {
    Copy-Item $iniSource $iniTarget
}
[Environment]::SetEnvironmentVariable('PHPRC', $phpRoot, 'User')
$env:PHPRC = $phpRoot

if (Test-Path (Join-Path $ProjectRoot "artisan")) {
    Write-Host "Laravel already scaffolded (artisan found). Skipping create-project." -ForegroundColor Yellow
} else {
    Write-Step "Creating Laravel project"
    $scaffoldDir = Join-Path $ProjectRoot ".laravel-scaffold"
    if (Test-Path $scaffoldDir) {
        Remove-Item -Recurse -Force $scaffoldDir
    }

    composer create-project laravel/laravel .laravel-scaffold --prefer-dist --no-interaction
    if ($LASTEXITCODE -ne 0) { throw "composer create-project failed" }

    Write-Step "Merging Laravel files into project root (preserving docs/, data/, scripts/)"
    Get-ChildItem -Path $scaffoldDir -Force | ForEach-Object {
        $dest = Join-Path $ProjectRoot $_.Name
        if ($_.Name -in @("docs", "data", "scripts", "README.md", ".env.example")) {
            return
        }
        if (Test-Path $dest) {
            Remove-Item -Recurse -Force $dest
        }
        Move-Item -Path $_.FullName -Destination $ProjectRoot
    }
    Remove-Item -Recurse -Force $scaffoldDir
}

Write-Step "Installing core application packages"
composer require `
    filament/filament:"^4.0" `
    laravel/fortify `
    spatie/laravel-permission `
    livewire/livewire `
    intervention/image-laravel `
    razorpay/razorpay `
    --no-interaction
if ($LASTEXITCODE -ne 0) { throw "composer require failed" }

Write-Step "Creating .env for native dev (SQLite, no Redis)"
$envFile = Join-Path $ProjectRoot ".env"
$envExample = Join-Path $ProjectRoot ".env.example"
if (-not (Test-Path $envFile)) {
    if (Test-Path $envExample) {
        Copy-Item $envExample $envFile
    } else {
        Copy-Item (Join-Path $ProjectRoot ".env.example") $envFile -ErrorAction SilentlyContinue
    }
}

# Patch .env for SQLite + file drivers (safe for company laptop, no extra services)
$dbPath = Join-Path $ProjectRoot "database\database.sqlite"
if (-not (Test-Path $dbPath)) {
    New-Item -ItemType File -Path $dbPath -Force | Out-Null
}

$envContent = Get-Content $envFile -Raw
$replacements = @{
    'DB_CONNECTION=mysql' = 'DB_CONNECTION=sqlite'
    'DB_HOST=mysql'       = '# DB_HOST=mysql'
    'DB_PORT=3306'        = '# DB_PORT=3306'
    'DB_DATABASE=mp_sepaktakraw' = '# DB_DATABASE=mp_sepaktakraw'
    'DB_USERNAME=sail'    = '# DB_USERNAME=sail'
    'DB_PASSWORD=password'= '# DB_PASSWORD=password'
    'SESSION_DRIVER=redis'= 'SESSION_DRIVER=file'
    'QUEUE_CONNECTION=redis' = 'QUEUE_CONNECTION=sync'
    'CACHE_STORE=redis'   = 'CACHE_STORE=file'
}
foreach ($pair in $replacements.GetEnumerator()) {
    $envContent = $envContent -replace [regex]::Escape($pair.Key), $pair.Value
}
if ($envContent -notmatch 'DB_DATABASE=') {
    $envContent += "`nDB_DATABASE=$($dbPath -replace '\\', '/')"
}
Set-Content -Path $envFile -Value $envContent -NoNewline

Write-Step "Generating app key and running migrations placeholder"
php artisan key:generate --force
if ($LASTEXITCODE -ne 0) { throw "artisan key:generate failed" }

Write-Host "`n=== Native Phase 0 scaffold complete ===" -ForegroundColor Green
Write-Host @"

Start the dev server:
  php artisan serve
  → http://127.0.0.1:8000

Next (we will add in follow-up commits):
  php artisan migrate --seed
  php artisan filament:install --panels
  npm install && npm run dev

Notes:
  • Local dev uses SQLite — production VPS will use MySQL + Redis (no code rewrite needed).
  • Docker/Sail optional later if IT grants admin access.
  • Fill SMTP + Razorpay test keys in .env when ready.

"@
