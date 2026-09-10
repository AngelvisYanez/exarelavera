/**
 * Prueba E2E con navegador: login, varios modulos, validacion de monitoreo.
 *
 * Requisitos:
 *   - Servidor local: .\start-local.ps1  (http://127.0.0.1:8080)
 *   - AUDIT_E2E_USER / AUDIT_E2E_PASS en entorno o .env.local
 *
 * Ejecutar:
 *   cd auditoria/TEST
 *   npm install
 *   npx playwright install chromium
 *   npm run test:browser
 */
import { chromium } from 'playwright';
import { spawnSync } from 'node:child_process';
import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '../..');
const BASE = process.env.EXA_BASE_URL || 'http://127.0.0.1:8080';
const HEADED = process.env.HEADED === '1' || process.env.HEADED === 'true';
const ROUTE_LIMIT = process.env.AUDIT_E2E_ROUTE_LIMIT || '120';

function phpJson(scriptRel, args = []) {
  const script = path.join(ROOT, scriptRel);
  const res = spawnSync('php', [script, ...args], {
    cwd: ROOT,
    encoding: 'utf8',
    env: { ...process.env },
  });
  const out = (res.stdout || '').trim();
  const err = (res.stderr || '').trim();
  if (res.status !== 0) {
    throw new Error(`PHP ${scriptRel} fallo (${res.status}): ${err || out}`);
  }
  const line = out.split('\n').filter(Boolean).pop();
  return JSON.parse(line);
}

function md5(text) {
  return crypto.createHash('md5').update(String(text)).digest('hex');
}

function loadEnvFile(filePath) {
  if (!fs.existsSync(filePath)) return;
  const raw = fs.readFileSync(filePath, 'utf8');
  for (const line of raw.split('\n')) {
    const t = line.trim();
    if (!t || t.startsWith('#')) continue;
    const eq = t.indexOf('=');
    if (eq <= 0) continue;
    const key = t.slice(0, eq).trim();
    let val = t.slice(eq + 1).trim();
    if ((val.startsWith('"') && val.endsWith('"')) || (val.startsWith("'") && val.endsWith("'"))) {
      val = val.slice(1, -1);
    }
    if (process.env[key] === undefined) process.env[key] = val;
  }
}

loadEnvFile(path.join(ROOT, '.env'));
loadEnvFile(path.join(__dirname, '.env.e2e'));

let E2E_USER = process.env.AUDIT_E2E_USER || '';
let E2E_PASS = process.env.AUDIT_E2E_PASS || '';

function assert(cond, msg) {
  if (!cond) throw new Error(msg);
}

async function waitForServer(url, attempts = 30) {
  for (let i = 0; i < attempts; i++) {
    try {
      const res = await fetch(url, { redirect: 'manual' });
      if (res.status > 0) return;
    } catch (_) {}
    await new Promise((r) => setTimeout(r, 1000));
  }
  throw new Error(`Servidor no responde en ${url}. Ejecute .\\start-local.ps1`);
}

async function doLogin(page, loginInfo) {
  await page.goto(`${BASE}/index.php`, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.fill('#user_name', E2E_USER);
  await page.fill('#password', E2E_PASS);
  await page.locator('#user_name').blur();
  await page.waitForFunction(() => {
    const sel = document.querySelector('#Emp_Cod');
    return sel && sel.options && sel.options.length > 0 && sel.options[0].value !== '';
  }, { timeout: 30000 }).catch(() => page.waitForTimeout(2000));

  const empVal = String(loginInfo.emp_cod);
  await page.selectOption('#Emp_Cod', empVal).catch(async () => {
    await page.evaluate((emp) => {
      const sel = document.querySelector('#Emp_Cod');
      if (!sel) return;
      for (const opt of sel.options) {
        if (opt.value === emp) {
          sel.value = emp;
          sel.dispatchEvent(new Event('change', { bubbles: true }));
          return;
        }
      }
    }, empVal);
  });

  await page.evaluate(({ passMd5, emp, suc }) => {
    const sucEl = document.getElementById('Suc_Cod');
    if (sucEl) sucEl.value = String(suc);
    document.getElementById('encryptor').value = passMd5;
    const sel = document.getElementById('Emp_Cod');
    if (sel) sel.value = String(emp);
  }, { passMd5: md5(E2E_PASS), emp: loginInfo.emp_cod, suc: loginInfo.suc_cod });

  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60000 }).catch(() => null),
    page.locator('#acceso button[type="button"]').click(),
  ]);

  const url = page.url();
  assert(url.includes('home.php') || !url.includes('errorusuario'), `Login fallo, URL actual: ${url}`);
}

async function triggerAuditWrites(page) {
  const resp = await page.goto(`${BASE}/auditoria/TEST/aud_e2e_trigger.php`, {
    waitUntil: 'domcontentloaded',
    timeout: 45000,
  });
  const body = await page.evaluate(() => document.body.innerText || '');
  let data = null;
  try {
    data = JSON.parse(body);
  } catch (_) {
    return { ok: false, error: 'Trigger no devolvio JSON', raw: body.slice(0, 200) };
  }
  return data;
}

async function visitModules(page, routes) {
  const visited = [];
  const phrase = 'El Servidor ha fallado en responder!';
  for (const route of routes) {
    const url = `${BASE}${route.path}`;
    const jsErrors = [];
    const consoleErrors = [];
    const requestFailures = [];
    const pageErrorHandler = (err) => jsErrors.push(String(err && err.message ? err.message : err));
    const consoleHandler = (msg) => {
      const txt = msg.text();
      if (msg.type() === 'error' || txt.includes(phrase)) {
        consoleErrors.push(txt);
      }
    };
    const requestFailedHandler = (req) => {
      requestFailures.push(`${req.method()} ${req.url()} :: ${req.failure() ? req.failure().errorText : 'failed'}`);
    };
    page.on('pageerror', pageErrorHandler);
    page.on('console', consoleHandler);
    page.on('requestfailed', requestFailedHandler);
    try {
      const resp = await page.goto(url, { waitUntil: 'networkidle', timeout: 45000 });
      const status = resp ? resp.status() : 0;
      await page.waitForTimeout(1200);
      const markers = await page.evaluate((serverPhrase) => {
        const text = (document.body && document.body.innerText) ? document.body.innerText : '';
        return {
          hasServerErrorPhrase: text.indexOf(serverPhrase) !== -1,
          title: document.title || '',
          bodySample: text.replace(/\s+/g, ' ').trim().slice(0, 300)
        };
      }, phrase);
      const ok = status >= 200 && status < 400
        && !markers.hasServerErrorPhrase
        && consoleErrors.every((txt) => !txt.includes(phrase))
        && requestFailures.length === 0
        && jsErrors.length === 0;
      visited.push({
        ...route,
        status,
        ok,
        hasServerErrorPhrase: markers.hasServerErrorPhrase,
        consoleErrors,
        requestFailures,
        jsErrors,
        bodySample: markers.bodySample
      });
    } catch (e) {
      visited.push({ ...route, status: 0, ok: false, error: String(e.message || e) });
    } finally {
      page.off('pageerror', pageErrorHandler);
      page.off('console', consoleHandler);
      page.off('requestfailed', requestFailedHandler);
    }
  }
  return visited;
}

async function checkMonitoreoGrid(page) {
  const url = `${BASE}/auditoria/FRONT/aud_con_monitoreo_1.0.php`;
  await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForTimeout(2000);

  const gridData = await page.evaluate(async () => {
    const today = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    const to = `${today.getFullYear()}-${pad(today.getMonth() + 1)}-${pad(today.getDate())}`;
    const fromDate = new Date(today.getTime() - 30 * 86400000);
    const from = `${fromDate.getFullYear()}-${pad(fromDate.getMonth() + 1)}-${pad(fromDate.getDate())}`;
    const body = new URLSearchParams({
      listMonitoreoGridAjax: '1',
      page: '1',
      rows: '25',
      from,
      to,
    });
    const res = await fetch(window.location.pathname + window.location.search, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString(),
      credentials: 'same-origin',
    });
    const txt = await res.text();
    try {
      return JSON.parse(txt);
    } catch (_) {
      return { records: 0, parseError: true, raw: txt.slice(0, 200) };
    }
  });

  return gridData;
}

async function main() {
  console.log('\n== E2E Browser: monitoreo auditoria ==\n');
  console.log(`Base URL: ${BASE}`);

  if (!E2E_USER || !E2E_PASS) {
    console.log('Sin AUDIT_E2E_USER/PASS: creando usuario E2E local...');
    const boot = phpJson('auditoria/TEST/aud_e2e_bootstrap.php');
    assert(boot.ok, boot.error || 'Bootstrap E2E fallo');
    E2E_USER = boot.user;
    E2E_PASS = boot.pass;
    process.env.AUDIT_E2E_USER = E2E_USER;
    process.env.AUDIT_E2E_PASS = E2E_PASS;
    console.log(`Usuario E2E: ${E2E_USER} (Emp_Cod=${boot.emp_cod})`);
  }

  await waitForServer(`${BASE}/index.php`);

  const loginInfo = phpJson('auditoria/TEST/aud_e2e_resolve_login.php');
  assert(loginInfo.ok, loginInfo.error || 'Login invalido en BD');
  console.log(`Login OK: Usu_Cod=${loginInfo.usu_cod}, Emp_Cod=${loginInfo.emp_cod}, BD=${loginInfo.dat_dis}`);

  const before = phpJson('auditoria/TEST/aud_e2e_log_count.php', [
    `--emp=${loginInfo.emp_cod}`,
    `--usu=${loginInfo.usu_cod}`,
    '--minutes=60',
  ]);
  console.log(`Logs recientes (60 min, usuario): ${before.count} | reglas cfg_monitoreo: ${before.cfg_rules}`);

  const routesInfo = phpJson('auditoria/TEST/aud_e2e_routes.php', [`--limit=${ROUTE_LIMIT}`]);
  const routes = routesInfo.routes || [];
  assert(routes.length >= 3, 'Se necesitan al menos 3 rutas de modulos para la prueba');

  const browser = await chromium.launch({ headless: !HEADED });
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  const page = await context.newPage();

  let failed = 0;
  try {
    console.log('\n-- Login navegador --');
    await doLogin(page, loginInfo);
    console.log('  OK  Sesion iniciada');

    console.log('\n-- Disparar escrituras auditables (I/U/D) --');
    const trigger = await triggerAuditWrites(page);
    if (trigger.modules) {
      for (const m of trigger.modules) {
        console.log(`  ${m.queued > 0 ? 'OK' : 'WARN'}  [${m.mod}] encolados=${m.queued}`);
      }
      const triggered = trigger.modules.filter((m) => m.queued > 0).length;
      assert(triggered >= 1, 'Ningun modulo encolo actividad auditada');
    } else {
      console.log('  WARN  Trigger:', trigger.error || trigger.raw || 'sin respuesta');
      failed++;
    }

    console.log('\n-- Recorrer modulos/procesos --');
    const visited = await visitModules(page, routes);
    for (const v of visited) {
      const mark = v.ok ? 'OK' : 'WARN';
      let extra = '';
      if (!v.ok) {
        if (v.hasServerErrorPhrase) extra = ' | alert=Servidor';
        else if (v.requestFailures && v.requestFailures.length) extra = ' | net=' + v.requestFailures[0];
        else if (v.jsErrors && v.jsErrors.length) extra = ' | js=' + v.jsErrors[0];
        else if (v.consoleErrors && v.consoleErrors.length) extra = ' | console=' + v.consoleErrors[0];
        else if (v.error) extra = ' | err=' + v.error;
      }
      console.log(`  ${mark}  [${v.mod}] ${v.label} -> ${v.path} (${v.status || 'err'})${extra}`);
    }

    const okModules = visited.filter((v) => v.ok).length;
    const badModules = visited.filter((v) => !v.ok);
    assert(okModules >= 2, `Solo ${okModules} procesos cargaron correctamente`);
    console.log(`  OK  ${okModules}/${visited.length} procesos accesibles sin alerta de servidor`);
    assert(badModules.length === 0, `Se detectaron ${badModules.length} procesos con fallo de respuesta`);

    const after = phpJson('auditoria/TEST/aud_e2e_log_count.php', [
      `--emp=${loginInfo.emp_cod}`,
      `--usu=${loginInfo.usu_cod}`,
      '--minutes=60',
    ]);
    const delta = after.count - before.count;
    console.log(`\nLogs despues de navegar: ${after.count} (delta +${delta})`);

    console.log('\n-- Consultar grid monitoreo --');
    const grid = await checkMonitoreoGrid(page);
    const records = Number(grid.records || 0);
    console.log(`  Grid registros (30 dias): ${records}`);

    if (before.cfg_rules > 0) {
      assert(delta >= 1, `Se esperaba al menos 1 log nuevo (delta=${delta})`);
      console.log('  OK  Actividad persistida segun cfg_monitoreo');
    } else {
      console.log('  INFO  Sin reglas cfg_monitoreo: solo tablas AUDIT_TABLES generan logs');
      assert(delta >= 1, `Se esperaba al menos 1 log en AUDIT_TABLES (delta=${delta})`);
      console.log('  OK  Logs en tablas AUDIT_TABLES');
    }

    assert(!grid.parseError, 'Grid monitoreo no devolvio JSON (permiso o error PHP)');
    assert(records >= 1, 'Grid monitoreo no muestra actividad (records=' + records + ')');
    console.log('  OK  Grid monitoreo con registros visibles');
  } catch (e) {
    failed++;
    console.error('\nFAIL ', e.message || e);
  } finally {
    await browser.close();
  }

  console.log('\n========================================');
  if (failed > 0) {
    console.log(`FALLOS / ADVERTENCIAS: ${failed}`);
    process.exit(1);
  }
  console.log('E2E BROWSER OK');
  console.log('========================================\n');
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
