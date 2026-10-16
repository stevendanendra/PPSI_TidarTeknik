// Helper bersama untuk uji E2E Playwright (SIT/UAT otomatis).
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8080/';
const ROOT = path.resolve(__dirname, '..');
const DB = process.env.DB_NAME || 'ppsi_tidarteknik';
const ADMIN = { user: process.env.ADMIN_USER || 'tidarteknik', pass: process.env.ADMIN_PASS || 'tidarteknik333777' };

const results = [];
function sql(q) {
  return execSync(`mysql -uroot ${DB} -N -B -e ${JSON.stringify(q)}`, { encoding: 'utf8' }).trim();
}
function resetDb() {
  execSync(`mysql -uroot -e "DROP DATABASE IF EXISTS ${DB}; CREATE DATABASE ${DB}"`);
  execSync(`mysql -uroot ${DB} < ${ROOT}/db/ppsi_tidarteknik.sql`, { shell: '/bin/bash' });
  const up = path.join(ROOT, 'uploads');
  if (fs.existsSync(up)) for (const d of fs.readdirSync(up)) {
    const p = path.join(up, d);
    if (fs.statSync(p).isDirectory()) fs.rmSync(p, { recursive: true, force: true });
  }
}
function assert(cond, msg) { if (!cond) throw new Error(msg || 'assertion gagal'); }

async function t(id, name, fn) {
  const t0 = Date.now();
  try { await fn(); results.push({ id, name, ok: true, ms: Date.now() - t0 }); console.log(`  ✔ ${id} ${name}`); }
  catch (e) { results.push({ id, name, ok: false, err: String(e.message || e).split('\n')[0], ms: Date.now() - t0 }); console.log(`  ✘ ${id} ${name} -> ${String(e.message || e).split('\n')[0]}`); }
}
function skip(id, name, why) { results.push({ id, name, ok: null, err: why }); console.log(`  - ${id} ${name} (dilewati: ${why})`); }

async function adminLogin(page, pass = ADMIN.pass, user = ADMIN.user) {
  await page.goto(BASE + 'login.php');
  await page.fill('#username', user); await page.fill('#password', pass);
  await Promise.all([page.waitForLoadState('load'), page.click('button[type=submit]')]);
}
async function custLogin(page, email, pass) {
  await page.goto(BASE + 'customer-login.php');
  await page.fill('#email', email); await page.fill('#password', pass);
  await Promise.all([page.waitForLoadState('load'), page.click('#btn-login-cust')]);
}
async function bodyText(page) { return (await page.locator('body').innerText()).replace(/\s+/g, ' '); }
const PNG = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==', 'base64');
function tmpFile(name, buf) { const p = path.join('/tmp', name); fs.writeFileSync(p, buf); return p; }

module.exports = { BASE, ROOT, ADMIN, results, sql, resetDb, assert, t, skip, adminLogin, custLogin, bodyText, PNG, tmpFile };
