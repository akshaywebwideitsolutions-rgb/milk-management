$ErrorActionPreference = 'Stop'
$projectPath = $PSScriptRoot
$electronPath = Join-Path $projectPath 'vendor/nativephp/electron/resources/js'
$archivePath = Join-Path $projectPath 'nativephp-php/bin/win/x64/php-8.3.zip'
$runtimePath = Join-Path $electronPath 'resources/php'
$viteCommand = Join-Path $electronPath 'node_modules/.bin/electron-vite.cmd'

if (!(Test-Path -LiteralPath $archivePath)) {
    throw 'NativePHP PHP archive is missing. Run composer install first.'
}
if (!(Test-Path -LiteralPath $viteCommand) -or !(Test-Path -LiteralPath (Join-Path $electronPath 'node_modules/electron/dist/electron.exe'))) {
    throw 'Electron dependencies are missing. Run NativePHP dependency installation first.'
}

# The package unzip script may finish before the PHP stream is complete.
# Extract synchronously, and verify the executable before launching Electron.
Expand-Archive -LiteralPath $archivePath -DestinationPath $runtimePath -Force
$extensionPath = (Join-Path $runtimePath 'ext').Replace('\', '/')
@"
extension_dir="$extensionPath"
extension=php_mbstring.dll
extension=php_openssl.dll
extension=php_curl.dll
extension=php_fileinfo.dll
extension=php_pdo_mysql.dll
extension=php_pdo_sqlite.dll
extension=php_sqlite3.dll
extension=php_zip.dll
extension=php_gd.dll
date.timezone=Asia/Kolkata
memory_limit=512M
"@ | Set-Content -LiteralPath (Join-Path $runtimePath 'php.ini') -Encoding ASCII
& (Join-Path $runtimePath 'php.exe') -v
if ($LASTEXITCODE -ne 0) { throw 'The bundled PHP runtime could not start.' }

$environmentNames = @('APP_PATH', 'APP_DEBUG', 'ELECTRON_RUN_AS_NODE', 'NATIVE_PHP_SKIP_QUEUE', 'PHPRC')
$previousEnvironment = @{}
foreach ($name in $environmentNames) {
    $previousEnvironment[$name] = [Environment]::GetEnvironmentVariable($name, 'Process')
}

Push-Location $electronPath
try {
    $env:APP_PATH = $projectPath
    $env:PHPRC = $runtimePath
    $env:APP_DEBUG = 'true'
    & (Join-Path $runtimePath 'php.exe') (Join-Path $projectPath 'artisan') native:migrate --no-interaction
    if ($LASTEXITCODE -ne 0) { throw 'NativePHP database migration failed.' }
    # VS Code can inherit this variable; Electron must start in desktop mode.
    Remove-Item Env:ELECTRON_RUN_AS_NODE -ErrorAction SilentlyContinue
    Remove-Item Env:NATIVE_PHP_SKIP_QUEUE -ErrorAction SilentlyContinue
    & $viteCommand dev --watch
    if ($LASTEXITCODE -ne 0) { throw "NativePHP exited with code $LASTEXITCODE." }
} finally {
    Pop-Location
    foreach ($name in $environmentNames) {
        [Environment]::SetEnvironmentVariable($name, $previousEnvironment[$name], 'Process')
    }
}
