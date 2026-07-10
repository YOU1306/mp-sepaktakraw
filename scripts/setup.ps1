#Requires -Version 5.1
<#
.SYNOPSIS
  Phase 0 bootstrap: Laravel + Sail + core packages for MP Sepaktakraw portal.
.DESCRIPTION
  Requires Docker Desktop (running). Uses the official Composer Docker image so PHP
  does not need to be installed natively on Windows.
#>
$ErrorActionPreference = "Stop"

$ProjectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $ProjectRoot

function Write-Step($message) {
    Write-Host "`n>> $message" -ForegroundColor Cyan
}

function Assert-Docker {
    if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
        throw "Docker not found. Install Docker Desktop, start it, then re-run this script."
    }
    docker info 2>$null | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "Docker daemon is not running. Start Docker Desktop and wait for 'Engine running'."
    }
}

function Invoke-Composer {
    param([string[]]$Arguments)
    docker run --rm `
        -v "${ProjectRoot}:/app" `
        -w /app `
        -e COMPOSER_ALLOW_SUPERUSER=1 `
        composer:2 @Arguments
    if ($LASTEXITCODE -ne 0) { throw "Composer failed: $($Arguments -join ' ')" }
}

Assert-Docker

if (Test-Path (Join-Path $ProjectRoot "artisan")) {
    Write-Host "Laravel already scaffolded (artisan found). Skipping create-project." -ForegroundColor Yellow
} else {
    Write-Step "Creating Laravel 11 project in temporary directory"
    $scaffoldDir = Join-Path $ProjectRoot ".laravel-scaffold"
    if (Test-Path $scaffoldDir) {
        Remove-Item -Recurse -Force $scaffoldDir
    }

    Invoke-Composer @("create-project", "laravel/laravel", ".laravel-scaffold", "--prefer-dist", "--no-interaction")

    Write-Step "Merging Laravel files into project root (preserving docs/, data/, scripts/)"
    Get-ChildItem -Path $scaffoldDir -Force | ForEach-Object {
        $dest = Join-Path $ProjectRoot $_.Name
        if ($_.Name -in @("docs", "data", "scripts", "README.md")) {
            return
        }
        if (Test-Path $dest) {
            Remove-Item -Recurse -Force $dest
        }
        Move-Item -Path $_.FullName -Destination $ProjectRoot
    }
    Remove-Item -Recurse -Force $scaffoldDir
}

Write-Step "Installing Laravel Sail (dev dependency)"
Invoke-Composer @("require", "laravel/sail", "--dev", "--no-interaction")

Write-Step "Publishing Sail Docker configuration"
if (-not (Test-Path (Join-Path $ProjectRoot "docker-compose.yml"))) {
    docker run --rm `
        -v "${ProjectRoot}:/app" `
        -w /app `
        laravelsail/php83-composer:latest `
        php artisan sail:install --with=mysql,redis --no-interaction
    if ($LASTEXITCODE -ne 0) { throw "sail:install failed" }
}

Write-Step "Installing core application packages"
$packages = @(
    "filament/filament:^3.2",
    "laravel/fortify",
    "spatie/laravel-permission",
    "livewire/livewire",
    "intervention/image-laravel",
    "razorpay/razorpay"
)
Invoke-Composer @("require") + $packages + @("--no-interaction")

Write-Step "Creating .env from .env.example (if missing)"
$envFile = Join-Path $ProjectRoot ".env"
$envExample = Join-Path $ProjectRoot ".env.example"
if (-not (Test-Path $envFile) -and (Test-Path $envExample)) {
    Copy-Item $envExample $envFile
}

Write-Step "Starting Sail containers"
$vendorBin = Join-Path $ProjectRoot "vendor\bin\sail"
if (-not (Test-Path $vendorBin)) {
    throw "vendor/bin/sail not found after composer install."
}

& bash $vendorBin up -d
if ($LASTEXITCODE -ne 0) {
    Write-Host "If bash is unavailable on Windows, run manually after setup:" -ForegroundColor Yellow
    Write-Host "  .\vendor\bin\sail up -d" -ForegroundColor Yellow
}

Write-Step "Generating app key"
& bash $vendorBin artisan key:generate --force 2>$null

Write-Host "`n=== Phase 0 scaffold complete ===" -ForegroundColor Green
Write-Host @"

Next (after containers are up):
  1. Copy .env.example values for mail + Razorpay into .env
  2. .\vendor\bin\sail artisan migrate --seed
  3. .\vendor\bin\sail artisan filament:install --panels
  4. Visit http://localhost

District seed data: data/mp-districts.json (55 districts)
Default super admin (change before launch): superadmin@mpsepaktakraw.test

"@
