import { app, BrowserWindow, session } from 'electron';
import NativePHP from '#plugin';
import path from 'path';
import defaultIcon from '../../resources/icon.png?asset&asarUnpack';
import certificate from '../../resources/cacert.pem?asset&asarUnpack';

const liveUrl = __DAIRY_LIVE_URL__;
const scopedUrl = __DAIRY_SCOPED_URL__;

if (!app.requestSingleInstanceLock()) {
  app.quit();
} else {
  app.on('second-instance', () => {
    const window = BrowserWindow.getAllWindows()[0];
    if (window) {
      if (window.isMinimized()) window.restore();
      window.show();
      window.focus();
    }
  });
  app.whenReady().then(() => {
    session.defaultSession.webRequest.onBeforeRequest({ urls: [new URL(liveUrl).origin + '/*'] }, (details, callback) => {
      const redirectURL = scopedUrl(details.url, liveUrl);
      callback(redirectURL ? { redirectURL } : {});
    });
  });
  const binary = process.platform === 'win32' ? 'php.exe' : 'php';
  const phpPath = path.join(__dirname, '../../resources/php', binary).replace('app.asar', 'app.asar.unpacked');
  NativePHP.bootstrap(app, defaultIcon, phpPath, certificate);
}
