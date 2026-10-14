// Build a NativePHP shell for the hosted dairy application without local secrets/data.
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const { spawnSync } = require('node:child_process');

const root = __dirname;
const liveUrl = process.argv[2] || 'https://booking.advancetaxiservice.com/test-project/public/';
if (new URL(liveUrl).protocol !== 'https:') throw new Error('The live URL must use HTTPS.');
const electronRoot = path.join(root, 'vendor/nativephp/electron/resources/js');
const staging = process.env.DESKTOP_BUILD_STAGE || fs.mkdtempSync(path.join(root, 'build-desktop-stage-'));
if (path.dirname(path.resolve(staging)) !== root || !path.basename(staging).startsWith('build-desktop-stage-')) {
  throw new Error('Build staging must be a dedicated directory inside this project.');
}
const appStage = path.join(staging, 'app');
const phpStage = path.join(staging, 'php');
const version = '1.0.2';
const output = path.join(root, 'dist', version);

function run(command, args, cwd = root) {
  const result = spawnSync(command, args, { cwd, stdio: 'inherit', env: process.env });
  if (result.error) throw result.error;
  if (result.status !== 0) throw new Error(`${command} failed (${result.status}).`);
}

async function build() {
  if (!process.env.DESKTOP_BUILD_STAGE) {
  fs.mkdirSync(appStage, { recursive: true });
  for (const directory of ['app', 'config', 'routes', 'resources', 'vendor', 'bootstrap', 'public']) {
    fs.cpSync(path.join(root, directory), path.join(appStage, directory), {
      recursive: true,
      filter(source) {
        const relative = path.relative(root, source).replaceAll('\\', '/');
        return ![
          'vendor/nativephp/electron/resources', 'vendor/nativephp/php-bin',
          'bootstrap/cache', 'public/storage', 'public/hot',
        ].some(excluded => relative === excluded || relative.startsWith(excluded + '/'));
      },
    });
  }
  for (const filename of ['artisan', 'composer.json', 'composer.lock']) {
    fs.copyFileSync(path.join(root, filename), path.join(appStage, filename));
  }
  fs.cpSync(path.join(root, 'database/migrations'), path.join(appStage, 'database/migrations'), { recursive: true });
  for (const directory of ['bootstrap/cache', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/testing', 'storage/framework/views', 'storage/app/public', 'storage/logs']) {
    fs.mkdirSync(path.join(appStage, directory), { recursive: true });
    fs.writeFileSync(path.join(appStage, directory, '_native.json'), '{}');
  }
  const environment = {
    APP_NAME: 'Jay Malhar Dairy', APP_ENV: 'production', APP_DEBUG: 'false',
    APP_KEY: 'base64:' + crypto.randomBytes(32).toString('base64'),
    APP_URL: liveUrl.replace(/\/$/, ''),
    DESKTOP_WEB_URL: liveUrl,
    DESKTOP_API_BASE_URL: liveUrl.replace(/\/$/, '') + '/api',
    DB_CONNECTION: 'sqlite', SESSION_DRIVER: 'file', CACHE_STORE: 'file',
    QUEUE_CONNECTION: 'sync', LOG_CHANNEL: 'single',
    NATIVEPHP_APP_ID: 'com.jaymalhar.dairy', NATIVEPHP_APP_VERSION: version,
    NATIVEPHP_UPDATER_ENABLED: 'false',
  };
  fs.writeFileSync(path.join(appStage, '.env'), Object.entries(environment).map(([key, value]) => `${key}="${value}"`).join('\n') + '\n');
  const archive = path.join(root, 'nativephp-php/bin/win/x64/php-8.3.zip');
  // Argument values are fixed local paths, quoted for PowerShell without interpolation.
  const quote = value => "'" + value.replaceAll("'", "''") + "'";
  run('powershell.exe', ['-NoProfile', '-Command', `Expand-Archive -LiteralPath ${quote(archive)} -DestinationPath ${quote(phpStage)} -Force`]);
  fs.writeFileSync(path.join(phpStage, 'php.ini'), [
    'extension_dir=ext', ...['mbstring', 'openssl', 'curl', 'fileinfo', 'pdo_sqlite', 'sqlite3', 'zip', 'gd'].map(name => `extension=php_${name}.dll`),
    'date.timezone=Asia/Kolkata', 'memory_limit=512M',
  ].join('\n'));
  run(path.join(phpStage, 'php.exe'), ['artisan', 'package:discover', '--no-ansi', '--quiet'], appStage);
  }
  // Use the existing NativePHP Electron project and installed, pinned dependencies.
  process.env.APP_PATH = appStage;
  delete process.env.ELECTRON_RUN_AS_NODE;
  process.env.CSC_IDENTITY_AUTO_DISCOVERY = 'false';
  const packagePath = path.join(electronRoot, 'package.json');
  const metadata = JSON.parse(fs.readFileSync(packagePath, 'utf8'));
  metadata.name = 'jay-malhar-dairy';
  fs.writeFileSync(packagePath, JSON.stringify(metadata, null, 2));
  const mainPath = path.join(electronRoot, 'src/main/index.js');
  const apiPath = path.join(electronRoot, 'electron-plugin/dist/server/api.js');
  const originalMain = fs.readFileSync(mainPath, 'utf8');
  const originalApi = fs.readFileSync(apiPath, 'utf8');
  const { scopedUrl } = require('./desktop/navigation.cjs');
  const main = fs.readFileSync(path.join(root, 'desktop/main.js'), 'utf8')
    .replace('__DAIRY_LIVE_URL__', JSON.stringify(liveUrl))
    .replace('__DAIRY_SCOPED_URL__', scopedUrl.toString());
  try {
    fs.writeFileSync(mainPath, main);
    fs.writeFileSync(apiPath, originalApi.replace('port: getPort.makeRange(4000, 5000),', 'port: 0, host: "127.0.0.1",').replace('httpServer.listen(port, () => {', 'httpServer.listen(port, "127.0.0.1", () => {'));
    run(process.execPath, [path.join(electronRoot, 'node_modules/electron-vite/bin/electron-vite.js'), 'build'], electronRoot);
  } finally {
    fs.writeFileSync(mainPath, originalMain);
    fs.writeFileSync(apiPath, originalApi);
  }
  const { build: packageApp } = require(path.join(electronRoot, 'node_modules/electron-builder'));
  const packageOptions = {
    projectDir: electronRoot,
    config: {
      appId: 'com.jaymalhar.dairy', productName: 'Jay Malhar Dairy',
      directories: { output, buildResources: path.join(electronRoot, 'build') },
      electronDist: path.join(electronRoot, 'node_modules/electron/dist'),
      files: ['out/**/*', 'electron-plugin/dist/preload/**/*', 'package.json', 'resources/icon.png', 'resources/cacert.pem'],
      asarUnpack: ['resources/**'],
      extraResources: [
        { from: appStage, to: 'app.asar.unpacked/resources/app', filter: ['**/*', '.env'] },
        { from: phpStage, to: 'app.asar.unpacked/resources/php' },
      ],
      extraMetadata: { name: 'jay-malhar-dairy', version, author: 'Jay Malhar Dairy', homepage: liveUrl },
      npmRebuild: false,
      win: { target: [{ target: 'nsis', arch: ['x64'] }], executableName: 'JayMalharDairy', signAndEditExecutable: false },
      nsis: { oneClick: false, allowToChangeInstallationDirectory: true, createDesktopShortcut: true, artifactName: 'JayMalharDairy-${version}-Setup.${ext}' },
      publish: null,
    },
  };
  const packageConfigPath = path.join(staging, 'electron-builder.json');
  fs.writeFileSync(packageConfigPath, JSON.stringify(packageOptions.config, null, 2));
  await packageApp({ projectDir: electronRoot, config: packageConfigPath });
  const asar = require(path.join(electronRoot, 'node_modules/@electron/asar'));
  const archivePath = path.join(output, 'win-unpacked/resources/app.asar');
  const packagedFiles = asar.listPackage(archivePath).map(filename => filename.split(path.sep).join('/'));
  if (!packagedFiles.includes('/electron-plugin/dist/preload/index.js')) {
    throw new Error('Packaged NativePHP preload is missing.');
  }
  console.log(`Installer created in ${output}`);
  fs.rmSync(staging, { recursive: true, force: true });
}

build().catch(error => { console.error(error); console.error(`Build staging retained for diagnosis: ${staging}`); process.exitCode = 1; });
