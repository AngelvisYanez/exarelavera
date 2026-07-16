// c:/xampp/htdocs/control-tributario-ec/sri_scraper/captcha_solver.js
/**
 * Resolución de reCAPTCHA Enterprise para el portal SRI
 * Portado desde Exa-ATI/src/lib/scraping/sri-playwright-scraper.ts
 *
 * ESTRATEGIA A — Con API key (Anti-Captcha o 2captcha):
 *   1. Resolver via API externa
 *   2. Inyectar token en g-recaptcha-response + sobrescribir executeRecaptcha
 *   3. Bypass PrimeFaces AJAX: inyectar input hidden + form.submit() directo
 *
 * ESTRATEGIA B — Sin API key (Fallback visual con Buster):
 *   1. Llamar window.executeRecaptcha() directamente si existe en la página
 *   2. Si aparece desafío visual → Buster audio solver en iframe api2/bframe
 *   3. Re-enviar via PrimeFaces.ab si es necesario
 */

const RECAPTCHA_SITE_KEY = '6LdukTQsAAAAAIcciM4GZq4ibeyplUhmWvlScuQE';

/**
 * ESTRATEGIA A — Resolver CAPTCHA vía API, inyectar token y hacer form.submit()
 * Retorna true si se ejecutó el POST y hay que esperar resultados.
 * Retorna false si no hay API key configurada.
 */
async function solveAndSubmit(page, keys, logFn) {
    const log = logFn || console.log;
    const anticaptchaKey = (keys && keys.anticaptchaKey) || process.env.ANTICAPTCHA_KEY || '';
    const twocaptchaKey  = (keys && keys.twocaptchaKey)  || process.env.TWOCAPTCHA_KEY  || '';
    const currentUrl = page.url();

    let token = null;

    // -- Paso 1: Obtener token ---------------------------------------------------
    if (anticaptchaKey) {
        log('[CAPTCHA-A] Resolviendo via Anti-Captcha API...');
        try { token = await solveWithAnticaptcha(anticaptchaKey, currentUrl, 'consulta_cel_recibidos', log); }
        catch(e) { log('[CAPTCHA-A] Anti-Captcha fallo: ' + e.message); }
    }

    if (!token && twocaptchaKey) {
        log('[CAPTCHA-A] Resolviendo via 2captcha API...');
        try { token = await solveWith2captcha(twocaptchaKey, currentUrl, log); }
        catch(e) { log('[CAPTCHA-A] 2captcha fallo: ' + e.message); }
    }

    if (!token) return false; // Sin API key → usar Estrategia B

    // -- Paso 2: Inyectar token en memoria + sobrescribir executeRecaptcha --------
    log('[CAPTCHA-A] Token obtenido. Inyectando en la pagina...');
    await injectToken(page, token);

    // -- Paso 3: Bypass de PrimeFaces AJAX → form.submit() directo ---------------
    //    Evita discrepancias de javax.faces.ViewState que causan CAPTCHA incorrecto
    log('[CAPTCHA-A] Enviando formulario via POST directo (bypass AJAX PrimeFaces)...');
    const submitted = await page.evaluate(function() {
        var form = document.getElementById('frmPrincipal');
        if (!form) return false;
        // Inyectar hidden que indica el botón presionado (requerido por JSF)
        var hidden = form.querySelector('input[name="frmPrincipal:btnBuscar"]');
        if (!hidden) {
            hidden = document.createElement('input');
            hidden.type  = 'hidden';
            hidden.name  = 'frmPrincipal:btnBuscar';
            hidden.value = 'Consultar';
            form.appendChild(hidden);
        }
        form.submit();
        return true;
    });

    return submitted;
}

/**
 * ESTRATEGIA B — Sin API key.
 * 1. Llama window.executeRecaptcha() directamente si el portal lo expone.
 * 2. Si no, hace clic en el botón Consultar.
 * Retorna true si pudo llamar executeRecaptcha, false si recurrió al clic.
 */
async function clickConsultarExa(page, logFn) {
    const log = logFn || console.log;



    // Fallback: clic físico en el botón Consultar
    log('[CAPTCHA-B] executeRecaptcha no disponible. Haciendo clic en Consultar...');
    const clicked = await page.evaluate(function() {
        var btns = Array.from(document.querySelectorAll('button, input, a, span'));
        for (var i = 0; i < btns.length; i++) {
            var el = btns[i];
            var txt = (el.innerText || el.value || '').trim();
            if (txt === 'Consultar') {
                var target = el.tagName === 'SPAN' ? (el.closest('button, a') || el) : el;
                target.click();
                return true;
            }
        }
        return false;
    });

    if (!clicked) {
        // Último fallback: PrimeFaces.ab
        await page.evaluate(function() {
            var prime = window.PrimeFaces;
            if (prime && prime.ab) {
                try { prime.ab({ source: 'frmPrincipal:btnBuscar' }); } catch(e) {}
            }
        }).catch(function() {});
    }
    return false;
}

/**
 * Intenta resolver el desafío visual de reCAPTCHA usando la extensión Buster.
 * Busca el iframe api2/bframe y hace clic en #solver-button.
 * Portado EXACTAMENTE desde Exa-ATI _tryBuster().
 */
async function tryBuster(page, logFn) {
    const log = logFn || console.log;
    try {
        for (var i = 0; i < page.frames().length; i++) {
            var frame = page.frames()[i];
            var url  = frame.url();
            var name = frame.name();
            if (url.includes('api2/bframe') || name.startsWith('c-')) {
                var btn = frame.locator('#solver-button');
                var visible = await btn.isVisible().catch(function() { return false; });
                if (visible) {
                    log('[CAPTCHA-B] Buster detectado en iframe CAPTCHA. Resolviendo audio...');
                    await btn.click();
                    await page.waitForTimeout(5000);
                    // Tras Buster: re-enviar via POST (Bypass de AJAX para evitar corrupción JSF)
                    await page.evaluate(function() {
                        var form = document.getElementById('frmPrincipal');
                        if (form) {
                            var hidden = form.querySelector('input[name="frmPrincipal:btnBuscar"]');
                            if (!hidden) {
                                hidden = document.createElement('input');
                                hidden.type = 'hidden';
                                hidden.name = 'frmPrincipal:btnBuscar';
                                hidden.value = 'Consultar';
                                form.appendChild(hidden);
                            }
                            form.submit();
                        }
                    }).catch(function() {});
                    return true;
                }
            }
        }
    } catch(e) {}
    return false;
}

/**
 * Detecta si la página muestra un error de CAPTCHA incorrecto.
 * Revisa selectores .ui-messages-error, .rf-msg-err y texto "captcha incorrecta/incorrecto".
 */
async function detectCaptchaError(page) {
    return page.evaluate(function() {
        var text = (document.body && document.body.innerText) ? document.body.innerText.toLowerCase() : '';
        var cleanText = text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        if (cleanText.includes('captcha incorrecta') || cleanText.includes('captcha incorrecto')) return true;
        var errEls = document.querySelectorAll('.ui-messages-error, .ui-message-error, .rf-msg-err, [class*="error"], [class*="alert"]');
        for (var i = 0; i < errEls.length; i++) {
            var t = (errEls[i].innerText || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            if (t.includes('captcha') || t.includes('incorrecta') || t.includes('incorrecto')) return true;
        }
        return false;
    }).catch(function() { return false; });
}

/**
 * Limpia los mensajes de error de PrimeFaces/JSF de la pantalla.
 * Paso de recuperación antes de reintentar.
 */
async function limpiarMensajesError(page) {
    await page.evaluate(function() {
        var closeBtn = document.querySelectorAll('.ui-messages-close, [class*="close"], .rf-msg-close');
        closeBtn.forEach(function(el) { el.click(); });
    }).catch(function() {});
}

/**
 * Inyecta el token en la página — sobrescribe g-recaptcha-response,
 * grecaptcha.enterprise.execute y window.executeRecaptcha.
 * Portado EXACTAMENTE desde Exa-ATI sri-playwright-scraper.ts líneas 1261-1317.
 */
async function injectToken(page, token) {
    await page.evaluate(function(t) {
        // 1. textarea estándar de reCAPTCHA
        var ta = document.getElementById('g-recaptcha-response');
        if (ta) ta.value = t;

        // 2. grecaptcha.enterprise mock
        if (!window.grecaptcha) window.grecaptcha = {};
        if (!window.grecaptcha.enterprise) window.grecaptcha.enterprise = {};
        window.grecaptcha.enterprise.execute = function() { return Promise.resolve(t); };
        window.grecaptcha.enterprise.ready   = function(cb) { if (typeof cb === 'function') cb(); };

        // 3. window.executeRecaptcha — función clave del portal SRI
        window.executeRecaptcha = function(_action, _source) {
            var ta2 = document.getElementById('g-recaptcha-response');
            if (ta2) ta2.value = t;
            return t;
        };

        // 4. Callback v2 si existe
        try {
            if (typeof window.___grecaptcha_cfg !== 'undefined') {
                var clients = Object.values(window.___grecaptcha_cfg.clients || {});
                for (var i = 0; i < clients.length; i++) {
                    var vals = Object.values(clients[i]);
                    for (var j = 0; j < vals.length; j++) {
                        if (vals[j] && typeof vals[j].callback === 'function') vals[j].callback(t);
                    }
                }
            }
        } catch(e) {}
    }, token);
}

// ── APIs externas ────────────────────────────────────────────────────────────
async function solveWithAnticaptcha(apiKey, pageUrl, action, log) {
    var body = JSON.stringify({
        clientKey: apiKey,
        task: {
            type: 'RecaptchaV2EnterpriseTaskProxyless',
            websiteURL: pageUrl,
            websiteKey: RECAPTCHA_SITE_KEY,
            enterprisePayload: action ? { s: action } : {}
        },
        softId: 0
    });
    var createData = JSON.parse(await httpPost('api.anti-captcha.com', '/createTask', body));
    if (createData.errorId !== 0) throw new Error(createData.errorDescription);
    var taskId = createData.taskId;
    log('[Anti-Captcha] Tarea ID=' + taskId + ' creada. Esperando solucion...');
    for (var i = 0; i < 60; i++) {
        await sleep(2000);
        var res = JSON.parse(await httpPost('api.anti-captcha.com', '/getTaskResult',
            JSON.stringify({ clientKey: apiKey, taskId: taskId })));
        if (res.errorId !== 0) throw new Error(res.errorDescription);
        if (res.status === 'ready' && res.solution && res.solution.gRecaptchaResponse) {
            log('[Anti-Captcha] Token obtenido.');
            return res.solution.gRecaptchaResponse;
        }
        log('[Anti-Captcha] Esperando... ' + (i+1) + '/60');
    }
    throw new Error('Timeout Anti-Captcha');
}

async function solveWith2captcha(apiKey, pageUrl, log) {
    var p = 'key=' + encodeURIComponent(apiKey) + '&method=userrecaptcha&enterprise=1'
        + '&googlekey=' + encodeURIComponent(RECAPTCHA_SITE_KEY)
        + '&pageurl=' + encodeURIComponent(pageUrl) + '&json=1';
    var sub = JSON.parse(await httpGet('2captcha.com', '/in.php?' + p));
    if (sub.status !== 1) throw new Error(sub.request);
    var id = sub.request;
    log('[2captcha] Tarea ID=' + id + ' enviada. Esperando...');
    await sleep(15000);
    for (var i = 0; i < 30; i++) {
        await sleep(5000);
        var r = JSON.parse(await httpGet('2captcha.com',
            '/res.php?key=' + encodeURIComponent(apiKey) + '&action=get&id=' + id + '&json=1'));
        if (r.status === 1) { log('[2captcha] Token obtenido.'); return r.request; }
        if (r.request !== 'CAPCHA_NOT_READY') throw new Error(r.request);
        log('[2captcha] Esperando... ' + (i+1) + '/30');
    }
    throw new Error('Timeout 2captcha');
}

function httpPost(host, path, body) {
    return new Promise(function(resolve, reject) {
        var https = require('https');
        var opts = { hostname: host, path: path, method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Content-Length': Buffer.byteLength(body) } };
        var req = https.request(opts, function(res) {
            var d = ''; res.on('data', function(c) { d += c; }); res.on('end', function() { resolve(d); });
        });
        req.on('error', reject); req.write(body); req.end();
    });
}

function httpGet(host, path) {
    return new Promise(function(resolve, reject) {
        var https = require('https');
        https.get({ hostname: host, path: path }, function(res) {
            var d = ''; res.on('data', function(c) { d += c; }); res.on('end', function() { resolve(d); });
        }).on('error', reject);
    });
}

function sleep(ms) { return new Promise(function(r) { setTimeout(r, ms); }); }

module.exports = { solveAndSubmit, clickConsultarExa, tryBuster, detectCaptchaError, limpiarMensajesError, injectToken, RECAPTCHA_SITE_KEY };
