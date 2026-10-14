const { test } = require('node:test');
const assert = require('node:assert/strict');
const { scopedUrl } = require('./navigation.cjs');
const site = 'https://booking.advancetaxiservice.com/test-project/public/';

test('root dashboard and API URLs retain the live project prefix', () => {
  assert.equal(scopedUrl(new URL('/', site).href, site), site);
  assert.equal(scopedUrl(new URL('/?logout=1', site).href, site), site + '?logout=1');
  assert.equal(scopedUrl(new URL('/dashboard', site).href, site), site + 'dashboard');
  assert.equal(scopedUrl(new URL('/api/farmers?page=2', site).href, site), site + 'api/farmers?page=2');
  assert.equal(scopedUrl(new URL('/invoices/7/print#total', site).href, site), site + 'invoices/7/print#total');
});

test('existing project URLs and unrelated sites are preserved', () => {
  assert.equal(scopedUrl(site + 'dashboard', site), null);
  assert.equal(scopedUrl('https://other.example/dashboard', site), null);
  assert.equal(scopedUrl(new URL('/unrelated', site).href, site), null);
  assert.equal(scopedUrl('https://example.com/dashboard', 'https://example.com/'), null);
});
