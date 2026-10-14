const assert = require('node:assert/strict');
const port = process.argv[2] || '9224';
const liveUrl = 'https://booking.advancetaxiservice.com/test-project/public/';
const keepAlive = setInterval(() => {}, 1000);

async function verify() {
  const pages = await fetch(`http://127.0.0.1:${port}/json/list`, { signal: AbortSignal.timeout(5000) }).then(response => response.json());
  const page = pages.find(item => item.type === 'page');
  assert.ok(page, 'Desktop window must exist');
  const socket = new WebSocket(page.webSocketDebuggerUrl);
  await new Promise((resolve, reject) => { socket.onopen = resolve; socket.onerror = reject; });
  let nextId = 0;
  const pending = new Map();
  socket.onmessage = ({ data }) => {
    const message = JSON.parse(data);
    if (pending.has(message.id)) {
      const { resolve, reject } = pending.get(message.id);
      pending.delete(message.id);
      message.error ? reject(message.error) : resolve(message.result);
    }
  };
  const command = (method, params = {}) => new Promise((resolve, reject) => {
    const id = ++nextId;
    pending.set(id, { resolve, reject });
    socket.send(JSON.stringify({ id, method, params }));
  });
  const evaluate = async expression => {
    const result = await command('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
    assert.ok(!result.exceptionDetails, 'Renderer evaluation must succeed');
    return result.result.value;
  };
  try {
    assert.equal(await evaluate('typeof window.Native'), 'object', 'Native preload must load');
    await command('Page.navigate', { url: new URL('/', liveUrl).href });
    await new Promise(resolve => setTimeout(resolve, 1500));
    assert.ok((await evaluate('location.href')).startsWith(liveUrl), 'Root home/logout navigation must stay inside the project');
    await command('Page.navigate', { url: new URL('/dashboard', liveUrl).href });
    await new Promise(resolve => setTimeout(resolve, 2000));
    const pageState = await evaluate('({url:location.href,title:document.title,preload:typeof window.Native})');
    assert.ok(pageState.url.startsWith(liveUrl), 'Root navigation must retain the project subdirectory');
    assert.ok(!pageState.title.includes('404'), 'Dashboard must not show a 404 page');
    assert.equal(pageState.preload, 'object');
    const apiStatus = await evaluate("fetch('/api/me', {headers:{Accept:'application/json'}}).then(response => response.status)");
    assert.ok([200, 401].includes(apiStatus), `API must resolve to the project API, got ${apiStatus}`);
    console.log(JSON.stringify({ ...pageState, apiStatus, result: 'PASS' }, null, 2));
  } finally {
    socket.close();
  }
}

verify().catch(error => { console.error(error); process.exitCode = 1; }).finally(() => clearInterval(keepAlive));
