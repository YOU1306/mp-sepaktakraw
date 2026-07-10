#Requires -Version 5.1
param(
    [switch]$Native
)

Write-Host "`n=== MP Sepaktakraw — prerequisite check ===`n" -ForegroundColor Cyan

$scoopShims = Join-Path $env:USERPROFILE "scoop\shims"
if (Test-Path $scoopShims) {
    $env:Path = "$scoopShims;$env:Path"
}

if ($Native) {
    $checks = @(
        @{ Name = "Scoop PHP"; Test = { Get-Command php -ErrorAction SilentlyContinue }; Fix = "scoop install php" },
        @{ Name = "Composer"; Test = { Get-Command composer -ErrorAction SilentlyContinue }; Fix = "scoop install composer" },
        @{ Name = "PHP openssl"; Test = { (php -m) -match "openssl" }; Fix = "Enable extension=openssl in ~\scoop\apps\php\current\cli\php.ini" },
        @{ Name = "PHP pdo_sqlite"; Test = { (php -m) -match "pdo_sqlite" }; Fix = "Enable extension=pdo_sqlite in php.ini" },
        @{ Name = "Node.js (asset build)"; Test = { Get-Command node -ErrorAction SilentlyContinue }; Fix = "scoop install nodejs-lts (optional until npm run dev)" }
    )
    $setupScript = ".\scripts\setup-native.ps1"
} else {
    $checks = @(
        @{ Name = "Git"; Test = { Get-Command git -ErrorAction SilentlyContinue }; Fix = "Install Git or: scoop install git" },
        @{ Name = "Docker"; Test = { Get-Command docker -ErrorAction SilentlyContinue }; Fix = "Docker Desktop (needs admin) or use: .\scripts\check-prerequisites.ps1 -Native" },
        @{ Name = "Docker daemon running"; Test = { if (-not (Get-Command docker -ErrorAction SilentlyContinue)) { return $false }; docker info 2>$null | Out-Null; $LASTEXITCODE -eq 0 }; Fix = "Start Docker Desktop" },
        @{ Name = "Node.js (asset build)"; Test = { Get-Command node -ErrorAction SilentlyContinue }; Fix = "Install Node LTS" }
    )
    $setupScript = ".\scripts\setup.ps1"
}

$failed = 0
foreach ($check in $checks) {
    $ok = & $check.Test
    if ($ok) {
        Write-Host "[OK]   $($check.Name)" -ForegroundColor Green
    } else {
        Write-Host "[FAIL] $($check.Name)" -ForegroundColor Red
        Write-Host "       $($check.Fix)" -ForegroundColor Yellow
        $failed++
    }
}

Write-Host ""
if ($failed -eq 0) {
    Write-Host "All required tools ready. Run: $setupScript" -ForegroundColor Green
    exit 0
}

Write-Host "$failed check(s) failed. Fix the items above, then re-run this script." -ForegroundColor Red
exit 1
