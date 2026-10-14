function scopedUrl(requestUrl, liveUrl) {
  const request = new URL(requestUrl);
  const site = new URL(liveUrl);
  const prefix = site.pathname.replace(/\/+$/, '');
  if (!prefix || request.origin !== site.origin || request.pathname === prefix || request.pathname.startsWith(prefix + '/')) return null;
  if (request.pathname === '/') {
    request.pathname = prefix + '/';
    return request.href;
  }
  if (!/^\/(dashboard|login|logout|farmers|milk-collection|milk-rates|invoices|bill-cycles|deductions|farmer-history|reports|settings|api|css|js|build)(\/|$)/.test(request.pathname)) return null;
  request.pathname = prefix + request.pathname;
  return request.href;
}

module.exports = { scopedUrl };
