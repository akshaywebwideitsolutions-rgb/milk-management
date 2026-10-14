param([int]$Port = 8000)

$ErrorActionPreference = 'Stop'
$webPhp = 'C:/xampp/php/php.exe'
if (!(Test-Path -LiteralPath $webPhp)) { $webPhp = 'php' }

Push-Location $PSScriptRoot
try {
    Write-Host "Web app: http://127.0.0.1:$Port"
    & $webPhp artisan serve --host=127.0.0.1 --port=$Port
} finally {
    Pop-Location
}
