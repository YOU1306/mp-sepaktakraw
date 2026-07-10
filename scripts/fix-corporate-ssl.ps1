#Requires -Version 5.1
<#
.SYNOPSIS
  Fix Composer/PHP SSL errors on company laptops with HTTPS inspection.
.DESCRIPTION
  Merges Mozilla CA bundle + Windows root certificates so PHP trusts the corporate proxy.
  Run once after: scoop install php composer
#>
$ErrorActionPreference = "Stop"

$persist = Join-Path $env:USERPROFILE "scoop\persist\php"
New-Item -ItemType Directory -Force -Path $persist | Out-Null

$mozilla = Join-Path $persist "cacert.pem"
$windows = Join-Path $persist "windows-roots.pem"
$combined = Join-Path $persist "cacert-combined.pem"

if (-not (Test-Path $mozilla)) {
    Write-Host "Downloading Mozilla CA bundle..."
    Invoke-WebRequest -Uri "https://curl.se/ca/cacert.pem" -OutFile $mozilla -UseBasicParsing
}

Write-Host "Exporting Windows root certificates..."
$lines = New-Object System.Collections.Generic.List[string]
Get-ChildItem Cert:\LocalMachine\Root, Cert:\CurrentUser\Root -ErrorAction SilentlyContinue | ForEach-Object {
    $b64 = [Convert]::ToBase64String($_.RawData, 'InsertLineBreaks')
    $lines.Add('-----BEGIN CERTIFICATE-----')
    $lines.Add($b64)
    $lines.Add('-----END CERTIFICATE-----')
}
$lines -join "`n" | Set-Content -Path $windows -Encoding ascii

Get-Content $mozilla, $windows | Set-Content -Path $combined -Encoding ascii

$phpIni = Join-Path $env:USERPROFILE "scoop\apps\php\current\cli\php.ini"
if (Test-Path $phpIni) {
    (Get-Content $phpIni -Raw) `
        -replace ';curl\.cainfo =.*', "curl.cainfo = `"$combined`"" `
        -replace 'curl\.cainfo = ".*"', "curl.cainfo = `"$combined`"" `
        -replace ';openssl\.cafile=.*', "openssl.cafile=`"$combined`"" `
        -replace 'openssl\.cafile=".*"', "openssl.cafile=`"$combined`"" |
        Set-Content $phpIni -NoNewline
}

$scoopShims = Join-Path $env:USERPROFILE "scoop\shims"
if (Test-Path $scoopShims) {
    $env:Path = "$scoopShims;$env:Path"
    composer config -g cafile $combined
}

Write-Host "Combined CA bundle: $combined" -ForegroundColor Green
Write-Host "Test: php -r `"echo file_get_contents('https://repo.packagist.org/packages.json') ? 'ok' : 'fail';`""
