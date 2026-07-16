// c:/xampp/htdocs/control-tributario-ec/sri_scraper/run_scrape.js
/**
 * Scraper nativo de Playwright para descarga de Declaraciones y Comprobantes del SRI
 * Ejecutable localmente en control-tributario-ec
 */
const fs = require('fs');
const path = require('path');
const { ensureSession } = require('./sri_auth');
const { solveAndSubmit, clickConsultarExa, tryBuster, detectCaptchaError, limpiarMensajesError } = require('./captcha_solver');
const os = require('os');
const { execSync } = require('child_process');

function obtenerNombreDisponible(carpetaDestino, nombreBase) {
    const ext = path.extname(nombreBase);
    const base = path.basename(nombreBase, ext);
    let intento = 0;
    let nombreFinal = nombreBase;
    while (fs.existsSync(path.join(carpetaDestino, nombreFinal))) {
        intento++;
        nombreFinal = `${base} (${intento})${ext}`;
    }
    return nombreFinal;
}


async function main() {
    const argInput = process.argv[2];
    if (!argInput) {
        console.error("Uso: node run_scrape.js <ruta_config.json | base64_config>");
        process.exit(1);
    }

    let config;
    if (argInput.startsWith('base64:')) {
        const jsonStr = Buffer.from(argInput.substring(7), 'base64').toString('utf8');
        config = JSON.parse(jsonStr);
    } else {
        let rawConfig = '';
        for (let attempt = 0; attempt < 15; attempt++) {
            if (fs.existsSync(argInput)) {
                try {
                    rawConfig = fs.readFileSync(argInput, 'utf8').trim();
                    if (rawConfig.length > 5 && rawConfig.includes('{')) {
                        break;
                    }
                } catch (e) {}
            }
            await new Promise(r => setTimeout(r, 200));
        }
        if (!rawConfig) {
            console.error("Error: El archivo config.json está vacío o inaccesible en: " + argInput);
            process.exit(1);
        }
        config = JSON.parse(rawConfig);
    }
    const { ruc, password, anio, mes, tipo_doc, output_dir } = config;

    if (!fs.existsSync(output_dir)) {
        fs.mkdirSync(output_dir, { recursive: true });
    }

    const logFile = path.join(output_dir, 'scrape.log');
    const resultFile = path.join(output_dir, 'result.json');
    const progressFile = path.join(output_dir, 'progress.json');

    function log(msg) {
        const line = `[${new Date().toISOString()}] ${msg}`;
        console.log(line);
        fs.appendFileSync(logFile, line + '\n');
    }

    function prog(title, desc, step = 1) {
        log(`[PROGRESS Step ${step}] ${title} | ${desc}`);
        try {
            const tmpProg = progressFile + '.tmp';
            fs.writeFileSync(tmpProg, JSON.stringify({ title, desc, step, timestamp: Date.now() }));
            try { fs.renameSync(tmpProg, progressFile); } catch(e) { fs.copyFileSync(tmpProg, progressFile); }
        } catch(e) {}
    }

    log(`Iniciando tarea de descarga nativa SRI: RUC=${ruc}, Año=${anio}, Mes=${mes}, Tipo=${tipo_doc}`);

    async function esperarCargaSRI(page) {
        try {
            // Esperar a que desaparezca cualquier overlay, mask o bloqueador AJAX de PrimeFaces en el portal SRI
            await page.waitForSelector('.ui-widget-overlay, .ui-blockui, .loading, #status', { state: 'hidden', timeout: 8000 }).catch(() => {});
        } catch (e) {}
        // Regla del usuario: esperar 1 segundo de lo que está cargado antes de dar el siguiente clic
        await page.waitForTimeout(300);
    }



    async function verificarYRecargarSiDenegado(page) {
        try {
            const bodyText = await page.innerText('body').catch(() => '');
            const upper = bodyText.toUpperCase();
            if (upper.includes('ACCESO DENEGADO') || upper.includes('ACCESS DENIED') || upper.includes('ERROR 403')) {
                log("⚠️ ¡Detectada pantalla de ACCESO DENEGADO en el SRI! Recargando página según instrucción para intentar la descarga...");
                await page.reload({ waitUntil: 'domcontentloaded', timeout: 45000 }).catch(() => {});
                await esperarCargaSRI(page);
                return true;
            }
        } catch (e) {}
        return false;
    }

    let playwright;
    try {
        const { chromium } = require('playwright-extra');
        const stealth = require('puppeteer-extra-plugin-stealth')();
        chromium.use(stealth);
        playwright = { chromium };
    } catch (e) {
        try {
            playwright = require('playwright');
        } catch (e2) {
            log('Error: La librería Playwright no está instalada.');
            fs.writeFileSync(resultFile, JSON.stringify({ status: 'error', error: 'Playwright no instalado.' }));
            process.exit(1);
        }
    }
    const userDataDir = path.resolve(__dirname, `chrome_profile_${ruc}`);
    const busterPath = path.resolve(__dirname, 'buster');
    const hasBuster = fs.existsSync(busterPath);

    const launchArgs = [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-web-security',
        '--disable-blink-features=AutomationControlled',
        '--disable-features=IsolateOrigins,site-per-process',
        '--hide-scrollbars',
        '--start-maximized'
    ];

    if (hasBuster) {
        log('🧩 Extensión Buster encontrada. Cargando para resolver CAPTCHA de audio...');
        launchArgs.push(`--disable-extensions-except=${busterPath}`);
        launchArgs.push(`--load-extension=${busterPath}`);
    } else {
        log('⚠️  Buster no encontrado en: ' + busterPath);
    }

    const context = await playwright.chromium.launchPersistentContext(userDataDir, {
        channel: 'chrome',
        headless: true, // Ejecución en segundo plano sin abrir ventana
        slowMo: 0,
        acceptDownloads: true,
        viewport: null,
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        locale: 'es-EC',
        timezoneId: 'America/Guayaquil',
        args: launchArgs
    });

    // Anti-detección: idéntico al Exa-ATI sri-playwright-scraper.ts
    await context.addInitScript(() => {
        Object.defineProperty(navigator, 'webdriver', { get: () => false });
        Object.defineProperty(navigator, 'plugins',   { get: () => [1, 2, 3, 4, 5] });
        Object.defineProperty(navigator, 'languages', { get: () => ['es-EC', 'es', 'en'] });
        Object.defineProperty(navigator, 'hardwareConcurrency', { get: () => 8 });
        window.navigator.chrome = { runtime: {} };
    });

    const page = context.pages().length > 0 ? context.pages()[0] : await context.newPage();

    let liveViewTimer = setInterval(async () => {
        try {
            if (page && !page.isClosed()) {
                const livePath = path.join(output_dir, 'live_view.jpg');
                await page.screenshot({ path: livePath, quality: 55, type: 'jpeg' }).catch(() => {});
            }
        } catch (e) {}
    }, 700);

    try {
        prog("Autenticando en el SRI...", "Ingresando RUC y contraseña en el portal SRI en Línea...", 1);
        await ensureSession(page, ruc, password, (m) => {
            log(m);
            if (m.includes("Iniciando sesión")) prog("Iniciando sesión en SRI...", `Validando RUC ${ruc} y clave...`, 1);
            else if (m.includes("clic en el botón")) prog("¡Credenciales enviadas!", "Verificando acceso al portal del contribuyente...", 1);
        });

        prog("¡Sesión ingresada con éxito!", "Preparando navegación en portal SRI...", 2);
        await page.waitForTimeout(300);
        const espereCargando = await page.evaluate(() => document.body?.innerText?.includes('Espere por favor') || false).catch(() => false);
        if (espereCargando) {
            log("⚠️ Portal SRI pegado en 'Espere por favor'. Deteniendo carga (X) y recargando página...");
            await page.evaluate(() => window.stop()).catch(() => {});
            await page.waitForTimeout(300);
            await page.reload({ waitUntil: 'domcontentloaded', timeout: 35000 }).catch(() => {});
            await page.waitForTimeout(300);
        }

        const esRetencionesRec = (tipo_doc === 'retenciones_rec' || tipo_doc === 'retenciones');
        const esAts = (tipo_doc === 'ats');

        let descargados = [];
        let mesesDescargados = [];
        let sustitutivaEncontrada = false;

        if (esRetencionesRec) {
            log("Iniciando Paso a Paso: Descarga de Retenciones Recibidas");

            log("Abriendo menú lateral (hamburguesa) si está cerrado...");
            try {
                await page.evaluate(() => {
                    const btnMenu = document.querySelector('a.top-icono-menu, .sri-menu-icon-menu-hamburguesa');
                    if (btnMenu) btnMenu.click();
                    else if (typeof mostrarOcultaSidebar === 'function') mostrarOcultaSidebar();
                });
            } catch (e) {}
            await page.waitForTimeout(600);

            // 2. Abrir comprobantes recibidos
            log("Dar clic en Comprobantes Electrónicos");
            const locFactElec = page.locator('a:has-text("Comprobantes Electrónicos"), span:has-text("Comprobantes Electrónicos"), a:has-text("Facturación Electrónica"), span:has-text("Facturación Electrónica")').first();
            await locFactElec.waitFor({ state: 'visible', timeout: 15000 }).catch(() => {});
            if(await locFactElec.isVisible()) {
                await locFactElec.click();
            } else {
                await page.evaluate(() => {
                    const els = Array.from(document.querySelectorAll('a, span, div'));
                    const el = els.find(e => e.innerText?.trim() === 'Comprobantes Electrónicos' || e.innerText?.trim() === 'Facturación Electrónica');
                    if (el) el.click();
                });
            }
            await page.waitForTimeout(800);

            log("Dar clic en Comprobantes Electrónicos Recibidos");
            const locCompRec = page.locator('a:has-text("Comprobantes Electrónicos Recibidos"), span:has-text("Comprobantes Electrónicos Recibidos"), a:has-text("Comprobantes electrónicos recibidos")').first();
            await locCompRec.waitFor({ state: 'visible', timeout: 15000 }).catch(() => {});
            if(await locCompRec.isVisible()) {
                await locCompRec.click();
            } else {
                await page.evaluate(() => {
                    const els = Array.from(document.querySelectorAll('a, span, li'));
                    const el = els.find(e => e.innerText?.trim().includes('Comprobantes electrónicos recibidos') || e.innerText?.trim().includes('Comprobantes Electrónicos Recibidos'));
                    if (el) el.click();
                });
            }
            await page.waitForTimeout(2000);

            if (!page.url().includes('comprobantesRecibidos.jsf')) {
                log("Navegando directamente a la URL de comprobantes recibidos...");
                await page.goto('https://srienlinea.sri.gob.ec/comprobantes-electronicos-internet/pages/consultas/recibidos/comprobantesRecibidos.jsf', {
                    waitUntil: 'domcontentloaded',
                    timeout: 60000
                }).catch(() => {});
                await page.waitForTimeout(1000);
            }

            log("Esperando indicador de carga...");
            await page.waitForSelector('.ui-widget-overlay, .ui-blockui, .loading, #status, .spinner', { state: 'hidden', timeout: 15000 }).catch(() => {});
            await page.waitForLoadState('networkidle').catch(() => {});

            let mesesProcesar = [];
            if (mes === 'todos') mesesProcesar = [1,2,3,4,5,6,7,8,9,10,11,12];
            else if (mes === 'sem1') mesesProcesar = [1,2,3,4,5,6];
            else if (mes === 'sem2') mesesProcesar = [7,8,9,10,11,12];
            else mesesProcesar = [parseInt(mes)];

            if (Array.isArray(config.omitir_meses) && config.omitir_meses.length > 0) {
                log(`Excluyendo meses: ${config.omitir_meses.join(', ')}`);
                mesesProcesar = mesesProcesar.filter(m => !config.omitir_meses.includes(m));
            }

            const mesesNombres = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

            for (const m of mesesProcesar) {
                const mesNombre = mesesNombres[m - 1];
                log(`Consultando mes: ${mesNombre} ${anio}`);
                prog(`Consultando retenciones de ${mesNombre} ${anio}...`, `Configurando período de emisión...`, 3);

                // 3. Seleccionar tipo
                await page.waitForTimeout(700);
                log("Dar clic en el combo Tipo de comprobante y Seleccionar Comprobante de Retención");
                await page.evaluate(() => {
                    const sels = Array.from(document.querySelectorAll('select'));
                    for (const s of sels) {
                        const txt = s.innerText || '';
                        if (txt.includes('Factura') || txt.includes('Retención')) {
                            Array.from(s.options).forEach(opt => {
                                if (opt.text.includes('Retención') || opt.text.includes('retención')) {
                                    s.value = opt.value;
                                }
                            });
                            s.dispatchEvent(new Event('change', { bubbles: true }));
                            break;
                        }
                    }
                });
                await page.waitForTimeout(500);

                // 4. Seleccionar período
                log("Seleccionar el año");
                await page.evaluate(({ anioStr }) => {
                    const sels = Array.from(document.querySelectorAll('select'));
                    for (const s of sels) {
                        const txt = s.innerText || '';
                        if (txt.includes('2026') || txt.includes('2025') || txt.includes('2024')) {
                            Array.from(s.options).forEach(opt => {
                                if (opt.text.trim() === anioStr || opt.value === anioStr) s.value = opt.value;
                            });
                            s.dispatchEvent(new Event('change', { bubbles: true }));
                            break;
                        }
                    }
                }, { anioStr: String(anio) });
                await page.waitForTimeout(300);

                log("Seleccionar el mes");
                await page.evaluate(({ mesStr }) => {
                    const sels = Array.from(document.querySelectorAll('select'));
                    for (const s of sels) {
                        const txt = s.innerText || '';
                        if (txt.includes('Enero') || txt.includes('Julio') || txt.includes('Diciembre')) {
                            Array.from(s.options).forEach(opt => {
                                if (opt.text.trim().toLowerCase() === mesStr.toLowerCase()) s.value = opt.value;
                            });
                            s.dispatchEvent(new Event('change', { bubbles: true }));
                            break;
                        }
                    }
                }, { mesStr: mesNombre });
                await page.waitForTimeout(500);

                // Seleccionar día Todos
                await page.evaluate(() => {
                    const sels = Array.from(document.querySelectorAll('select'));
                    for (const s of sels) {
                        const txt = s.innerText || '';
                        if (txt.includes('\n1\n') || txt.includes('\n15\n') || txt.includes('Todos') || (s.options.length >= 28 && s.options.length <= 33)) {
                            Array.from(s.options).forEach(opt => {
                                if (opt.text.trim().toLowerCase() === 'todos' || opt.value === '0' || opt.value === '') {
                                    s.value = opt.value;
                                }
                            });
                            s.dispatchEvent(new Event('change', { bubbles: true }));
                            break;
                        }
                    }
                });
                await page.waitForTimeout(300);

                // 5. Buscar
                log("Dar clic en Buscar");
                prog(`Buscando comprobantes en portal...`, `Consultando SRI para ${mesNombre.toUpperCase()} ${anio}...`, 4);
                
                const captchaKeys = {
                    anticaptchaKey: config.anticaptcha_key || process.env.ANTICAPTCHA_KEY || '',
                    twocaptchaKey:  config.twocaptcha_key  || process.env.TWOCAPTCHA_KEY  || ''
                };
                
                let filasCargadas = false;
                for (let attempt = 0; attempt < 10; attempt++) {
                    log(`Iniciando búsqueda (Intento ${attempt + 1}/3)...`);
                    await limpiarMensajesError(page).catch(() => {});
                    
                    let waitingResult = await solveAndSubmit(page, captchaKeys, log).catch(()=>false);
                    if (!waitingResult) {
                         await clickConsultarExa(page, log).catch(()=>false);
                    }

                    let searchResult = 'timeout';
                    for (let wait = 0; wait < 15; wait++) {
                        await page.waitForTimeout(2000);
                        
                        // Intentar resolver desafío visual de recaptcha usando Buster si aparece
                        await tryBuster(page, log).catch(() => {});
                        
                        const hasError = await detectCaptchaError(page).catch(() => false);
                        if (hasError) {
                            searchResult = 'captcha_error';
                            break;
                        }
                        
                        const state = await page.evaluate(() => {
                            const text = document.body?.innerText || '';
                            if (document.querySelector('.ui-datatable-data tr') && text.match(/\d{49}/)) return 'table';
                            if (text.includes('No se encontraron') || text.includes('Ningún registro') || text.includes('No existen')) return 'no_results';
                            return 'waiting';
                        }).catch(() => 'waiting');
                        
                        if (state === 'table' || state === 'no_results') {
                            searchResult = state;
                            break;
                        }
                    }

                    log(`Resultado de búsqueda en intento ${attempt + 1}: ${searchResult}`);

                    if (searchResult === 'table' || searchResult === 'no_results') {
                        filasCargadas = true;
                        break;
                    }
                    if (searchResult === 'captcha_error') {
                        log(`CAPTCHA error detectado. Como el navegador está visible, el bot pausará por 30 segundos para que puedas resolver el captcha manualmente haciendo clic en las imágenes...`);
                        await limpiarMensajesError(page).catch(() => {});
                        await page.waitForTimeout(30000); // 30 segundos de pausa para resolución manual
                        continue;
                    }
                }
                
                if (filasCargadas) {
                    await page.waitForSelector('.ui-widget-overlay, .ui-blockui, .loading, #status', { state: 'hidden', timeout: 15000 }).catch(() => {});
                    await page.waitForLoadState('networkidle').catch(() => {});
                    await page.waitForSelector('.ui-datatable-data tr', { state: 'visible', timeout: 5000 }).catch(() => {});
                } else {
                    log('No se pudieron obtener resultados tras 10 intentos. Pasando al siguiente mes...');
                    continue; // Pasa al siguiente mes
                }

                // Ampliar paginador a 100 si existe para acelerar
                try {
                    const selectPaginator = page.locator('select.ui-paginator-rpp-options').first();
                    if (await selectPaginator.count() > 0) {
                        await selectPaginator.selectOption({ label: '100'  }, { force: true }).catch(() => selectPaginator.selectOption({ index: 3  }, { force: true }).catch(() => {}));
                        await page.waitForTimeout(300);
                        await page.waitForSelector('.ui-widget-overlay, .ui-blockui, .loading, #status', { state: 'hidden', timeout: 15000 }).catch(() => {});
                    }
                } catch (ePag) {}

                // 6. Recorrer resultados
                let hayPaginaSiguiente = true;
                let xmlsDelMes = 0;

                while (hayPaginaSiguiente) {
                    await page.waitForTimeout(700);
                    const filas = await page.locator('.ui-datatable-data tr').count().catch(()=>0);
                    if (filas > 0) {
                        prog(`¡Lista cargada en ${mesNombre}!`, `Se detectaron ${filas} filas. Iniciando descargas...`, 5);
                    }
                    
                    for (let f = 0; f < filas; f++) {
                        prog(`Descargando XMLs en ${mesNombre}...`, `Guardando XML ${f + 1} de ${filas}...`, 5);
                        const tr = page.locator('.ui-datatable-data tr').nth(f);
                        const rowText = await tr.innerText().catch(()=>'');
                        if (rowText.includes('Ningún registro') || rowText.includes('No se encontraron')) continue;

                        // 8. Descargar XML (y no PDF según instrucción)
                        const xmlLink = tr.locator('a[id*="xml" i], a[title*="xml" i], img[src*="xml" i], a:has(img[src*="xml" i])').first();
                        
                        if (await xmlLink.isVisible().catch(()=>false)) {
                            // Mover mouse
                            await xmlLink.hover().catch(()=>{});
                            await page.waitForTimeout(300);
                            
                            // Dar clic y esperar descarga
                            const downloadPromise = page.waitForEvent('download', { timeout: 30000 }).catch(() => null);
                            await xmlLink.click({ force: true }).catch(()=>{});
                            await page.waitForTimeout(2000);
                            
                            const download = await downloadPromise;
                            if (download) {
                                const tempXml = path.join(output_dir, `temp_xml_${Date.now()}.xml`);
                                await download.saveAs(tempXml).catch(()=>{});
                                
                                if (fs.existsSync(tempXml)) {
                                    let uniqueName = `Retencion_${anio}_${mesNombre}_item${f+1}_${Date.now()}.xml`;
                                    try {
                                        const xmlContent = fs.readFileSync(tempXml, 'utf8');
                                        const mClave = xmlContent.match(/<claveAcceso>(\d+)<\/claveAcceso>/i);
                                        if (mClave && mClave[1]) {
                                            uniqueName = `RET_${mClave[1]}.xml`;
                                        }
                                    } catch (eParse) {}
                                    
                                    uniqueName = obtenerNombreDisponible(output_dir, uniqueName);
                                    const finalXmlPath = path.join(output_dir, uniqueName);
                                    try {
                                        fs.renameSync(tempXml, finalXmlPath);
                                    } catch (eRen) {
                                        fs.copyFileSync(tempXml, finalXmlPath);
                                    }
                                    
                                    log(`Descargado XML: ${uniqueName}`);
                                    descargados.push(uniqueName);
                                    xmlsDelMes++;
                                }
                            }
                            await page.waitForTimeout(500);
                        }
                    }

                    // 9. Si existen varias páginas
                    const btnSiguiente = page.locator('.ui-paginator-next:not(.ui-state-disabled), a.ui-paginator-next:not([aria-disabled="true"])').first();
                    const isNextVisible = await btnSiguiente.isVisible().catch(()=>false);
                    const isNextEnabled = await btnSiguiente.isEnabled().catch(()=>false);
                    
                    if (isNextVisible && isNextEnabled) {
                        await page.waitForTimeout(500);
                        await btnSiguiente.click().catch(()=>{});
                        await page.waitForTimeout(2500);
                        await page.waitForSelector('.ui-widget-overlay, .ui-blockui, .loading, #status', { state: 'hidden', timeout: 15000 }).catch(() => {});
                        await page.waitForLoadState('networkidle').catch(() => {});
                    } else {
                        hayPaginaSiguiente = false;
                    }
                }
                
                if (xmlsDelMes > 0) {
                    mesesDescargados.push(m);
                }
                
                // Limpiar estado de JSF y Token de reCAPTCHA recargando la página para el siguiente mes
                if (mesesProcesar.indexOf(m) < mesesProcesar.length - 1) {
                    log("Recargando la página para limpiar caché del CAPTCHA para el próximo mes...");
                    await page.reload({ waitUntil: 'domcontentloaded', timeout: 40000 }).catch(() => {});
                    await page.waitForTimeout(2500);
                    await page.waitForSelector('.ui-widget-overlay, .ui-blockui, .loading, #status', { state: 'hidden', timeout: 15000 }).catch(() => {});
                }
            }
        } else if (esAts) {
            // Paso 1. Esperar 1 segundo para que cargue el menú principal
            await page.waitForTimeout(300);

            // Paso 2. Abrir el módulo de Anexos
            prog("Abriendo el módulo de Anexos...", "Dar clic en Anexos en el menú lateral...", 2);
            log("Paso 2: Abrir el módulo de Anexos");
            try {
                await page.evaluate(() => {
                    const btnMenu = document.querySelector('a.top-icono-menu, .sri-menu-icon-menu-hamburguesa');
                    if (btnMenu) btnMenu.click();
                    else if (typeof mostrarOcultaSidebar === 'function') mostrarOcultaSidebar();
                });
                await page.waitForTimeout(500);
            } catch (e) {}

            await page.evaluate(() => {
                const links = Array.from(document.querySelectorAll('a, span'));
                for (const el of links) {
                    if ((el.innerText || '').trim() === 'Anexos') {
                        el.click();
                        break;
                    }
                }
            });
            await page.waitForTimeout(500);

            // Paso 3. Ingresar a "Envío y consulta de anexos"
            prog("Ingresando a Envío y consulta de anexos...", "Dar clic en Envío y consulta de anexos...", 2);
            log("Paso 3: Ingresar a Envío y consulta de anexos");
            await page.evaluate(() => {
                const links = Array.from(document.querySelectorAll('a, span'));
                for (const el of links) {
                    const txt = (el.innerText || '').trim();
                    if (txt.includes('Envío y consulta de anexos') || txt.includes('Envio y consulta de anexos')) {
                        const target = el.tagName === 'SPAN' ? el.closest('a') || el : el;
                        target.click();
                        break;
                    }
                }
            });
            await page.waitForTimeout(300);
            await esperarCargaSRI(page);

            // Paso 4. Seleccionar ATS
            prog("Seleccionando ATS...", "Dar clic en Anexo Transaccional Simplificado (ATS)...", 3);
            log("Paso 4: Seleccionar ATS");
            await page.evaluate(() => {
                const links = Array.from(document.querySelectorAll('a, span, button'));
                for (const el of links) {
                    const txt = (el.innerText || '').trim();
                    if (txt.includes('Anexo Transaccional Simplificado') || txt === 'ATS' || txt.includes('(ATS)')) {
                        const target = el.tagName === 'SPAN' ? el.closest('a, button') || el : el;
                        target.click();
                        break;
                    }
                }
            });
            await page.waitForTimeout(300);
            await esperarCargaSRI(page);

            // Paso 5. Abrir "Recuperar archivo cargado"
            prog("Abriendo Recuperar archivo cargado...", "Localizando sección Recuperar archivo cargado...", 3);
            log("Paso 5: Abrir Recuperar archivo cargado");
            await page.evaluate(() => {
                const all = Array.from(document.querySelectorAll('a, h1, h2, h3, h4, h5, div, span, td, li'));
                const exact = all.filter(el => {
                    const t = (el.innerText || '').trim();
                    return (t === 'Recuperar archivo cargado' || t === '► Recuperar archivo cargado' || t.includes('Recuperar archivo cargado')) && t.length < 40;
                });
                if (exact.length > 0) {
                    exact.sort((a, b) => (a.innerText || '').length - (b.innerText || '').length);
                    const el = exact[0];
                    const target = el.closest('a, h3, .ui-accordion-header, button') || el;
                    target.click();
                }
            });
            await page.waitForTimeout(500);
            await esperarCargaSRI(page);
            
            // Verificar que el panel "Recuperar archivo cargado" esté expandido (visible los selectores)
            log("Paso 5b: Verificar panel Recuperar archivo cargado expandido");
            for (let retryPanel = 0; retryPanel < 5; retryPanel++) {
                const panelVisible = await page.evaluate(() => {
                    // Buscamos un select de año o el form visible dentro del panel de recuperar archivo
                    const selects = Array.from(document.querySelectorAll('.ui-selectonemenu, select'));
                    return selects.some(s => {
                        const rect = s.getBoundingClientRect();
                        return rect.width > 0 && rect.height > 0;
                    });
                }).catch(() => false);
                if (panelVisible) break;
                log(`Intento ${retryPanel + 1}: panel no visible aún, re-clic...`);
                await page.evaluate(() => {
                    const all = Array.from(document.querySelectorAll('a, h3, div, span'));
                    const exact = all.filter(el => {
                        const t = (el.innerText || '').trim();
                        return t.includes('Recuperar archivo cargado') && t.length < 40;
                    });
                    if (exact.length > 0) {
                        exact.sort((a, b) => (a.innerText || '').length - (b.innerText || '').length);
                        const el = exact[0];
                        const target = el.closest('a, h3, .ui-accordion-header, button') || el;
                        target.click();
                    }
                });
                await page.waitForTimeout(300);
                await esperarCargaSRI(page);
            }

            const NOMBRES_MESES_ATS = ['', 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
            const mesNum = parseInt(mes);
            const usarTodos = (mesNum === 0 || isNaN(mesNum));
            const mesNombreFiltro = usarTodos ? null : (NOMBRES_MESES_ATS[mesNum] || null);

            // ── PASO 1: Esperar que el formulario Recuperar archivo cargado muestre sus selects con opciones
            prog(`Esperando formulario ATS...`, `Esperando que los combos carguen opciones...`, 3);
            log('Paso 6a: Esperar formulario Recuperar archivo cargado con opciones');
            let selectAnioLocator = null;
            for (let fw = 0; fw < 30; fw++) {
                const count = await page.locator('form[id*="frmRecuperarArchivoCargado"] select, .ui-accordion-content:visible select').count().catch(() => 0);
                for (let si = 0; si < count; si++) {
                    const sel = page.locator('form[id*="frmRecuperarArchivoCargado"] select, .ui-accordion-content:visible select').nth(si);
                    const opts = await sel.locator('option').allTextContents().catch(() => []);
                    const tieneAnio = opts.some(t => t.trim().includes(anio) || t.trim().includes(String(parseInt(anio) - 1)));
                    if (tieneAnio) {
                        selectAnioLocator = sel;
                        log(`Select de Año encontrado en posición ${si}: ${opts.join(' | ')}`);
                        break;
                    }
                }
                if (selectAnioLocator) break;
                await page.waitForTimeout(500);
            }

            if (!selectAnioLocator) {
                const errMsg = `No se encontró el combo de Año en el formulario del SRI.`;
                log(`ERROR: ${errMsg}`);
                prog(errMsg, errMsg, 3);
                throw new Error(errMsg);
            }

            // ── PASO 2 y 3: Seleccionar AÑO usando Playwright selectOption (dispara events JSF correctamente)
            prog(`Seleccionando Año ${anio}...`, `Aplicando año en el combo del SRI...`, 3);
            log(`Paso 6b: selectOption año="${anio}"`);
            const optsAnio = await selectAnioLocator.locator('option').allTextContents();
            const labelAnio = optsAnio.find(t => t.trim() === anio || t.trim().includes(anio));
            if (!labelAnio) {
                const errMsg = `El año ${anio} no está disponible en el combo. Disponibles: ${optsAnio.join(', ')}`;
                log(`ERROR: ${errMsg}`);
                throw new Error(errMsg);
            }
            // Interacción física con PrimeFaces usando Localizadores puros de Playwright
            const dropdownDivId = await selectAnioLocator.evaluate(sel => sel.closest('.ui-selectonemenu').id);
            const triggerLocator = page.locator(`[id="${dropdownDivId}"] .ui-selectonemenu-trigger`);
            await page.waitForTimeout(1000);
            await triggerLocator.click({ force: true }); // Abrir el menú
            
            // Esperar a que el panel de PrimeFaces sea visible en el DOM (PrimeFaces los añade al body)
            const panelLocator = page.locator('.ui-selectonemenu-panel:visible').first();
            await panelLocator.waitFor({ state: 'visible', timeout: 20000 });
            
            // Buscar y dar clic en la opción correcta dentro del panel abierto
            const optionLocator = panelLocator.locator('li').filter({ hasText: labelAnio.trim() }).first();
            await page.waitForTimeout(500);
            await optionLocator.click({ force: true });
            await page.waitForTimeout(500);
            await page.keyboard.press('Escape').catch(() => {});
            await page.waitForTimeout(500);
            log(`Año "${labelAnio.trim()}" seleccionado con localizadores nativos de Playwright`);

            // ── PASO 4: Esperar que el combo Período cargue los meses disponibles
            prog(`Esperando que carguen los períodos...`, `Aguardando opciones de Período...`, 3);
            log('Paso 6c: Esperar que el select de Período tenga opciones de meses');
            await page.waitForTimeout(300);
            await esperarCargaSRI(page);

            let selectPeriodoLocator = null;
            for (let pw = 0; pw < 20; pw++) {
                const count = await page.locator('form[id*="frmRecuperarArchivoCargado"] select, .ui-accordion-content:visible select').count().catch(() => 0);
                for (let si = 0; si < count; si++) {
                    const sel = page.locator('form[id*="frmRecuperarArchivoCargado"] select, .ui-accordion-content:visible select').nth(si);
                    const opts = await sel.locator('option').allTextContents().catch(() => []);
                    const tieneMeses = opts.some(t => {
                        const u = t.trim().toUpperCase();
                        return u.includes('TODOS') || u.includes('ENERO') || u.includes('FEBRERO') || u.includes('MARZO');
                    });
                    if (tieneMeses) {
                        selectPeriodoLocator = sel;
                        log(`Select de Período encontrado en posición ${si}: ${opts.join(' | ')}`);
                        break;
                    }
                }
                if (selectPeriodoLocator) break;
                await page.waitForTimeout(500);
            }

            if (!selectPeriodoLocator) {
                const errMsg = `No se encontró el combo de Período en el formulario del SRI.`;
                log(`ERROR: ${errMsg}`);
                prog(errMsg, errMsg, 3);
                throw new Error(errMsg);
            }

            // ── PASO 5 y 6: Seleccionar PERÍODO por texto visible, nunca por índice
            prog(`Seleccionando Período...`, `Buscando período por texto visible del combo...`, 3);
            log(`Paso 6d: selectOption período usarTodos=${usarTodos} mes="${mesNombreFiltro}"`);
            const optsPeriodo = await selectPeriodoLocator.locator('option').allTextContents();
            log(`Opciones de período disponibles: ${optsPeriodo.join(' | ')}`);

            let labelPeriodo = null;
            if (usarTodos) {
                labelPeriodo = optsPeriodo.find(t => t.trim().toUpperCase().includes('TODOS') && !t.trim().toUpperCase().includes('SELECCIONAR'));
            } else {
                labelPeriodo = optsPeriodo.find(t => {
                    const u = t.trim().toUpperCase();
                    return u.includes(mesNombreFiltro.toUpperCase()) && !u.includes('SELECCIONAR');
                });
            }

            // ── PASO 7 y 9: Validar que se encontró el período, error si no existe
            if (!labelPeriodo) {
                const errPeriodo = `No existe el período seleccionado en el portal del SRI. Disponibles: ${optsPeriodo.filter(t => !t.includes('Seleccionar')).join(', ')}`;
                log(`ERROR: ${errPeriodo}`);
                prog(errPeriodo, errPeriodo, 3);
                throw new Error(errPeriodo);
            }

            // Interacción física con PrimeFaces usando Localizadores puros de Playwright
            const dropdownPeriodoId = await selectPeriodoLocator.evaluate(sel => sel.closest('.ui-selectonemenu').id);
            const triggerPeriodoLocator = page.locator(`[id="${dropdownPeriodoId}"] .ui-selectonemenu-trigger`);
            await page.waitForTimeout(1000);
            await triggerPeriodoLocator.click({ force: true }); // Abrir el menú
            
            // Esperar a que el panel de PrimeFaces sea visible en el DOM
            const panelPeriodoLocator = page.locator('.ui-selectonemenu-panel:visible').first();
            await panelPeriodoLocator.waitFor({ state: 'visible', timeout: 20000 });
            
            // Buscar y dar clic en la opción correcta dentro del panel abierto
            const optionPeriodoLocator = panelPeriodoLocator.locator('li').filter({ hasText: labelPeriodo.trim() }).first();
            await page.waitForTimeout(500);
            await optionPeriodoLocator.click({ force: true });
            await page.waitForTimeout(500);
            await page.keyboard.press('Escape').catch(() => {});
            await page.waitForTimeout(500);
            log(`Período "${labelPeriodo.trim()}" seleccionado con localizadores nativos de Playwright`);

            // ── PASO 8: periodoLabel toma el texto exacto del combo seleccionado (no predefinido)
            const periodoLabel = labelPeriodo.trim();
            log(`periodoLabel desde combo: "${periodoLabel}"`);

            await page.waitForTimeout(300);
            await esperarCargaSRI(page);

            // ── PASO 10: Validar selección visual y clic en Buscar (solo dentro del panel ATS)
            prog(`Buscando archivos ATS (${periodoLabel})...`, `Año y Período validados. Dando clic en Buscar...`, 4);
            log(`Paso 8: Buscar — año="${anio}" periodo="${periodoLabel}"`);

            // Clic en el botón Buscar asegurándonos de que esté visible y pertenezca al formulario de Recuperar Archivo
            const buscarBtn = page.locator('form[id*="frmRecuperarArchivoCargado"] button:has-text("Buscar"), .ui-accordion-content:visible button:has-text("Buscar"), .ui-accordion-content:visible input[value="Buscar"]').first();
            const hayBuscar = await buscarBtn.count().then(c => c > 0).catch(() => false);
            if (hayBuscar) {
                await buscarBtn.click({ force: true });
            } else {
                // Fallback: buscar el botón 'Buscar' visible que esté debajo del combo de período
                await page.evaluate(() => {
                    const btns = Array.from(document.querySelectorAll('input[type="submit"], input[type="button"], button'));
                    const b = btns.reverse().find(el => {
                        const txt = (el.value || el.innerText || '').trim().toUpperCase();
                        return txt === 'BUSCAR' && el.closest('form') && (el.offsetWidth > 0 || el.offsetHeight > 0);
                    });
                    if (b) b.click();
                });
            }
            await page.waitForTimeout(1000);
            await esperarCargaSRI(page);

            // Esperar lista de resultados (filas reales de tabla ATS: deben tener columna "Ver Archivo")
            prog(`Verificando tabla de resultados...`, `Esperando que aparezcan los archivos disponibles...`, 4);
            log('Paso 9: Esperar tabla de resultados ATS');
            for (let w = 0; w < 40; w++) {
                const hayFilas = await page.evaluate(() => {
                    const trs = Array.from(document.querySelectorAll('table tbody tr'));
                    return trs.some(tr => {
                        const tds = tr.querySelectorAll('td');
                        if (tds.length < 2) return false;
                        const texto = (tr.innerText || '').toUpperCase();
                        return !texto.includes('NINGÚN REGISTRO') && !texto.includes('NO EXISTEN') && !texto.includes('SE CONSIDERA ANEXO SUSTITUTIVO') && !texto.includes('NOTIFICADO EL RESPECTIVO');
                    });
                }).catch(() => false);
                if (hayFilas) break;
                await page.waitForTimeout(300);
            }
            await page.waitForTimeout(800);

            // Paso 10. Obtener lista de filas disponibles y descargar cada una
            // Si hay paginación, procesar todas las páginas
            let paginaActual = 1;
            let hayMasPaginas = true;

            while (hayMasPaginas) {
                const filasInfo = await page.evaluate(() => {
                    const trs = Array.from(document.querySelectorAll('table tbody tr'));
                    return trs
                        .map((tr, idx) => {
                            const tds = Array.from(tr.querySelectorAll('td'));
                            if (tds.length < 6) return null;
                            const periodo = (tds[2] || tds[1] || tds[0] || {innerText: ''}).innerText.trim().toUpperCase();
                            return { idx, periodo, tr };
                        })
                        .filter(obj => {
                            if (!obj) return false;
                            // Periodo must strictly look like "ENERO 2025" or similar month name
                            const hasMonth = /^(ENERO|FEBRERO|MARZO|ABRIL|MAYO|JUNIO|JULIO|AGOSTO|SEPTIEMBRE|OCTUBRE|NOVIEMBRE|DICIEMBRE)(\s+\d{4})?$/i.test(obj.periodo);
                            return hasMonth && !obj.periodo.includes('SUSTITUTIVO');
                        })
                        .map(obj => ({ idx: obj.idx, periodo: obj.periodo }));
                });

                log(`Página ${paginaActual}: ${filasInfo.length} filas encontradas`);
                prog(`Descargando ATS (${periodoLabel})...`, `Procesando ${filasInfo.length} registros en página ${paginaActual}...`, 5);

                for (const fila of filasInfo) {
                    const mesDeDescarga = (fila.periodo || `fila_${fila.idx}`).replace(/\s+/g, '_');
                    log(`Descargando fila ${fila.idx}: ${fila.periodo}`);
                    prog(`Descargando ${fila.periodo}...`, `Clic en Ver Archivo de la fila ${fila.periodo}...`, 5);

                    let descargaAts = null;
                    const dlHandlerAts = d => { descargaAts = d; };
                    page.on('download', dlHandlerAts);
                    context.on('download', dlHandlerAts);
                    const dlPromise = page.waitForEvent('download', { timeout: 20000 }).catch(() => null);

                    // Clic en el botón "Ver Archivo" de esta fila específica
                    const hizoClic = await page.evaluate(({ originalRowIdx }) => {
                        const trs = Array.from(document.querySelectorAll('table tbody tr'));
                        const tr = trs[originalRowIdx];
                        if (!tr) return false;
                        // Buscar input[value="Ver Archivo"] o botón similar en esta fila (sin selectores jQuery)
                        const btn = tr.querySelector('input[value="Ver Archivo"], input[type="submit"]');
                        if (btn) { btn.click(); return true; }
                        const clickables = Array.from(tr.querySelectorAll('a, button, input[type="image"], input[type="submit"], input[type="button"]'));
                        const verBtn = clickables.find(el => {
                            const t = (el.innerText || el.value || el.title || '').toLowerCase();
                            return t.includes('ver') || t.includes('descarg') || t.includes('archiv');
                        }) || clickables[clickables.length - 1];
                        if (verBtn) { verBtn.click(); return true; }
                        return false;
                    }, { originalRowIdx: fila.idx });

                    if (!hizoClic) {
                        // Intentar con Playwright locator
                        try {
                            const loc = page.locator(`table tbody tr:nth-child(${fila.idx + 1}) input[value="Ver Archivo"], table tbody tr:nth-child(${fila.idx + 1}) input[type="submit"]`).first();
                            if (await loc.count() > 0) await loc.click();
                        } catch(e) {}
                    }

                    const dlEvt = await dlPromise;
                    if (dlEvt && !descargaAts) descargaAts = dlEvt;
                    let espAts = 0;
                    while (!descargaAts && espAts < 40) { await page.waitForTimeout(150); espAts++; }
                    page.off('download', dlHandlerAts);
                    context.off('download', dlHandlerAts);
                    await page.waitForTimeout(300);

                    if (descargaAts) {
                        const tempZipName = `temp_ats_${Date.now()}.zip`;
                        const tempZipPath = path.join(output_dir, tempZipName);
                        await descargaAts.saveAs(tempZipPath).catch(() => {});

                        if (fs.existsSync(tempZipPath)) {
                            let finalZipName = `Anexo_ATS_${anio}_${mesDeDescarga}_${ruc}.zip`;
                            finalZipName = obtenerNombreDisponible(output_dir, finalZipName);
                            const finalZipPath = path.join(output_dir, finalZipName);
                            try { fs.renameSync(tempZipPath, finalZipPath); } catch(e) { try { fs.copyFileSync(tempZipPath, finalZipPath); } catch(e2){} }

                            prog(`Descargando ATS...`, `Archivo guardado.`, 5);
                            descargados.push(finalZipName);


                            // Extraer número de mes del periodo (ej: "ENERO 2025" -> 1)
                            const mMatch = NOMBRES_MESES_ATS.findIndex(n => n && fila.periodo.toUpperCase().includes(n));
                            if (mMatch > 0) mesesDescargados.push(mMatch);
                            log(`✔ ATS ${fila.periodo} descargado: ${finalZipName}`);
                        }
                    } else {
                        log(`⚠ No se detectó descarga para fila ${fila.periodo}.`);
                    }
                }

                // Verificar si hay página siguiente
                hayMasPaginas = await page.evaluate(() => {
                    const nextBtn = document.querySelector('.ui-paginator-next:not(.ui-state-disabled), a.ui-paginator-next:not([aria-disabled="true"])');
                    return !!nextBtn && !nextBtn.classList.contains('ui-state-disabled');
                }).catch(() => false);

                if (hayMasPaginas) {
                    log(`Pasando a página ${paginaActual + 1}...`);
                    await page.evaluate(() => {
                        const nextBtn = document.querySelector('.ui-paginator-next:not(.ui-state-disabled)');
                        if (nextBtn) nextBtn.click();
                    });
                    await page.waitForTimeout(500);
                    await esperarCargaSRI(page);
                    paginaActual++;
                }
            }

            prog(`¡ATS descargado!`, `${descargados.length} archivo(s) obtenidos exitosamente.`, 5);
        } else {
            prog("Ingresando a Consulta de Declaraciones...", "Cargando portal de obligaciones...", 2);
            log(`Navegando a consulta de declaraciones del SRI (lista-obligaciones.jsf)...`);
            await page.goto('https://srienlinea.sri.gob.ec/sri-eyr-consulta-web-internet/pages/consulta/obligacion/lista-obligaciones.jsf', {
                waitUntil: 'domcontentloaded',
                timeout: 60000
            });

            await page.waitForTimeout(500);
            await verificarYRecargarSiDenegado(page);

        // 104 = IVA, 103 = Retenciones en la fuente, 101/102/renta = Impuesto a la Renta
        const esRenta = (tipo_doc === 'renta' || tipo_doc === '101' || tipo_doc === '102');
        let palabraClave = 'IVA';
        if (tipo_doc === '103') palabraClave = 'RETENCIONES';
        else if (esRenta) palabraClave = 'RENTA';

        prog("Configurando formulario...", `Seleccionando obligación oficial: ${palabraClave} (Formulario ${tipo_doc})...`, 2);
        log(`Seleccionando obligación oficial en portal: ${palabraClave} (Formulario ${tipo_doc})...`);
        await esperarCargaSRI(page);
        try {
            const filasObli = await page.$$('table tbody tr');
            let filaSeleccionar = null;
            let textoSeleccionado = '';

            for (const f of filasObli) {
                const txt = await f.innerText();
                const txtUp = txt.toUpperCase();
                if (esRenta) {
                    if (txtUp.includes('RENTA NATURALES') || txtUp.includes('RENTA SOCIEDADES')) {
                        filaSeleccionar = f;
                        textoSeleccionado = txt.trim();
                        break;
                    }
                } else {
                    if (txtUp.includes(palabraClave)) {
                        filaSeleccionar = f;
                        textoSeleccionado = txt.trim();
                        break;
                    }
                }
            }

            // Fallback si no dice literalmente NATURALES o SOCIEDADES (excluyendo estrictamente ANTICIPO)
            if (!filaSeleccionar && esRenta) {
                for (const f of filasObli) {
                    const txt = await f.innerText();
                    const txtUp = txt.toUpperCase();
                    if (txtUp.includes('RENTA') && !txtUp.includes('ANTICIPO') && !txtUp.includes('MICROEMPRESAS') && !txtUp.includes('MÚLTIPLE') && !txtUp.includes('MULTIPLE')) {
                        filaSeleccionar = f;
                        textoSeleccionado = txt.trim();
                        break;
                    }
                }
            }

            if (filaSeleccionar) {
                const chk = await filaSeleccionar.$('div.ui-chkbox-box, input[type="checkbox"], td:first-child');
                if (chk) {
                    await chk.click();
                    const esperaEsquema = esRenta ? 600 : 1000;
                    prog("Obligación seleccionada", `Casillero marcado (${textoSeleccionado}). Esperando para dar clic en buscar...`, 2);
                    log(`Casillero marcado (${textoSeleccionado}). Esperando ${esperaEsquema}ms antes de dar clic en buscar...`);
                    await page.waitForTimeout(esperaEsquema);
                    await esperarCargaSRI(page);
                }
            }
        } catch (e) {}

        prog("Abriendo filtro de período...", "Pulsando botón Buscar para cargar menú de años...", 3);
        log("Pulsando botón Buscar exacto visto en DevTools (btnBuscarGrupoObligacion)...");
        await esperarCargaSRI(page);
        try {
            const btnExacto = page.locator('button[id*="btnBuscarGrupoObligacion"], button[name*="btnBuscarGrupoObligacion"]').first();
            if (await btnExacto.count() > 0) {
                await btnExacto.click();
            } else {
                const locBuscar = page.locator('button:has-text("Buscar"), .ui-button:has-text("Buscar")').first();
                if (await locBuscar.count() > 0) await locBuscar.click();
                else await page.click('text="Buscar"').catch(() => {});
            }
            await esperarCargaSRI(page); // Esperar apertura de modal de período
        } catch (e) {
            log("Ejecutando clic secundario en Buscar por DOM...");
            try {
                await page.evaluate(() => {
                    const btns = Array.from(document.querySelectorAll('button, a, input'));
                    const b = btns.find(el => el.id?.includes('btnBuscarGrupoObligacion') || (el.innerText && el.innerText.trim() === 'Buscar') || el.value === 'Buscar');
                    if (b) b.click();
                });
            } catch (ex) {}
            await esperarCargaSRI(page);
        }

        let mesesProcesar = [];
        if (esRenta) {
            mesesProcesar = [0]; // 0 indica declaración anual (1 solo documento por año fiscal)
        } else if (mes === 'todos') mesesProcesar = [1,2,3,4,5,6,7,8,9,10,11,12];
        else if (mes === 'sem1') mesesProcesar = [1,2,3,4,5,6];
        else if (mes === 'sem2') mesesProcesar = [7,8,9,10,11,12];
        else mesesProcesar = [parseInt(mes)];

        if (Array.isArray(config.omitir_meses) && config.omitir_meses.length > 0) {
            log(`⚡ Excluyendo meses ya cargados previamente en tu sistema: ${config.omitir_meses.join(', ')}`);
            mesesProcesar = mesesProcesar.filter(m => !config.omitir_meses.includes(m));
        }

        prog("Seleccionando Año Fiscal...", `Eligiendo el año ${anio} en el modal de período...`, 3);
        log(`Modal de período abierto. Seleccionando año fiscal de forma rápida: ${anio}...`);
        await page.waitForTimeout(500);
        try {
            const pfMenu = page.locator('.ui-dialog .ui-selectonemenu').first();
            if (await pfMenu.count() > 0) {
                await pfMenu.click({ timeout: 2000 }).catch(() => {});
                await page.waitForTimeout(400);
                const opcionAnio = page.locator(`.ui-selectonemenu-items li:has-text("${anio}")`).first();
                if (await opcionAnio.count() > 0) await opcionAnio.click({ timeout: 2000 }).catch(() => {});
            } else {
                const sel = await page.$('select');
                if (sel) await sel.selectOption({ label: String(anio)  }, { force: true }).catch(() => {});
            }
        } catch (e) {}

        log("Esperando menos de 1 segundo tras seleccionar el año antes de pulsar Aceptar...");
        await page.waitForTimeout(600);

        log("Pulsando botón Aceptar en el modal de período fiscal...");
        try {
            const btnAceptarExacto = page.locator('button[id*="btnAceptar"], button[name*="btnAceptar"]').first();
            if (await btnAceptarExacto.count() > 0) {
                await btnAceptarExacto.click();
            } else {
                const locAceptar = page.locator('.ui-dialog button:has-text("Aceptar"), button:has-text("Aceptar"), .ui-button:has-text("Aceptar")').first();
                if (await locAceptar.count() > 0) await locAceptar.click();
                else await page.click('text="Aceptar"').catch(() => {});
            }
            await esperarCargaSRI(page);
        } catch (e) {
            try {
                await page.evaluate(() => {
                    const btns = Array.from(document.querySelectorAll('button, a, input'));
                    const b = btns.find(el => el.id?.includes('btnAceptar') || (el.innerText && el.innerText.trim() === 'Aceptar') || el.value === 'Aceptar');
                    if (b) b.click();
                });
            } catch (ex) {}
            await esperarCargaSRI(page);
        }

        const mesesNombres = ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];

        prog("Análisis de Declaraciones...", `Inspeccionando tabla para detectar Originales y Sustitutivas...`, 4);
        log(`Procesando meses seleccionados (${mesesProcesar.map(m => mesesNombres[m-1]).join(', ')}) y aplicando regla de prioridad para el año ${anio}...`);
        
        // Intentar ampliar la tabla a 50 filas para ver todos los meses sin cortar
        try {
            const selectPaginator = page.locator('select.ui-paginator-rpp-options').first();
            if (await selectPaginator.count() > 0) {
                log("⚡ Ampliando paginador a 50 filas por página para ver todo el año...");
                await selectPaginator.selectOption({ label: '50'  }, { force: true }).catch(() => selectPaginator.selectOption({ index: 2  }, { force: true }).catch(() => {}));
                await page.waitForTimeout(1200);
                await esperarCargaSRI(page);
            }
        } catch (exPag) {}

        let sustitutivaEncontrada = false;

        for (const m of mesesProcesar) {
            try {
            const esAnual = (m === 0 || esRenta);
            const mesNombre = esAnual ? 'ANUAL' : mesesNombres[m - 1];
            prog(`Analizando período: ${esAnual ? 'Año Fiscal ' + anio : mesNombre}...`, `Comparando fechas para seleccionar la declaración más actual...`, 4);
            const busqueda = esAnual ? String(anio) : `${mesNombre}`;

            // Contar cuántas páginas tiene el paginador
            let totalPaginas = 1;
            try {
                const numPaginas = await page.evaluate(() => document.querySelectorAll('.ui-paginator-pages .ui-paginator-page').length);
                if (numPaginas > 0) totalPaginas = numPaginas;
            } catch (e) {}

            const candidatos = [];

            for (let p = 1; p <= totalPaginas; p++) {
                try {
                    const pagActual = await page.evaluate(() => {
                        const el = document.querySelector('.ui-paginator-pages .ui-paginator-page.ui-state-active');
                        return el ? parseInt(el.innerText, 10) : 1;
                    });
                    if (pagActual !== p) {
                        log(`🔍 Navegando a página ${p} de la tabla SRI para buscar ${mesNombre}...`);
                        const pagBtn = page.locator(`.ui-paginator-pages .ui-paginator-page:has-text("${p}")`).first();
                        if (await pagBtn.count() > 0) {
                            await pagBtn.click();
                            await page.waitForTimeout(300);
                            await esperarCargaSRI(page);
                        } else {
                            const nextBtn = page.locator('.ui-paginator-next:not(.ui-state-disabled)').first();
                            if (await nextBtn.count() > 0) {
                                await nextBtn.click();
                                await page.waitForTimeout(300);
                                await esperarCargaSRI(page);
                            }
                        }
                    }
                } catch (ePag) {}

                try {
                    const filasEnPagina = await page.evaluate(({ esAnual, busqueda, anioStr, p }) => {
                        const trs = Array.from(document.querySelectorAll('table tbody tr'));
                        const list = [];
                        trs.forEach((tr, idx) => {
                            const textoFila = tr.innerText || '';
                            let coincideFila = false;
                            if (esAnual) {
                                if (textoFila.includes(anioStr) && (textoFila.includes('101') || textoFila.includes('102') || textoFila.toUpperCase().includes('RENTA'))) {
                                    coincideFila = true;
                                }
                            } else {
                                if (textoFila.toUpperCase().includes(busqueda) && textoFila.includes(anioStr)) {
                                    coincideFila = true;
                                }
                            }
                            if (coincideFila) {
                                const esSust = textoFila.toUpperCase().includes('SUSTITUTIVA');
                                const tds = Array.from(tr.querySelectorAll('td')).map(td => td.innerText.trim());
                                let fechaMs = 0;
                                for (const celda of tds) {
                                    const matchDDMM = celda.match(/(\d{2})[\/\-](\d{2})[\/\-](\d{4})/);
                                    if (matchDDMM) {
                                        fechaMs = new Date(Number(matchDDMM[3]), Number(matchDDMM[2]) - 1, Number(matchDDMM[1])).getTime();
                                        break;
                                    }
                                    const matchYYYY = celda.match(/(\d{4})[\/\-](\d{2})[\/\-](\d{2})/);
                                    if (matchYYYY) {
                                        fechaMs = new Date(Number(matchYYYY[1]), Number(matchYYYY[2]) - 1, Number(matchYYYY[3])).getTime();
                                        break;
                                    }
                                }
                                const serieStr = tds[4] || '';
                                const serieNum = parseInt(serieStr.replace(/\D/g, ''), 10) || 0;
                                list.push({ p, idx, esSust, fechaMs, serieNum });
                            }
                        });
                        return list;
                    }, { esAnual, busqueda, anioStr: String(anio), p });

                    filasEnPagina.forEach(c => {
                        if (c.esSust) sustitutivaEncontrada = true;
                        candidatos.push(c);
                    });
                } catch (eEval) {}
            }

            let esFilaSustitutiva = false;

            if (candidatos.length > 0) {
                // Ordenar candidatos: primero Sustitutivas sobre Originales, luego fecha de declaración más actual (mayor timestamp), luego número de serie mayor
                candidatos.sort((a, b) => {
                    if (a.esSust !== b.esSust) return a.esSust ? -1 : 1;
                    if (a.fechaMs !== b.fechaMs) return b.fechaMs - a.fechaMs;
                    return b.serieNum - a.serieNum;
                });

                const ganador = candidatos[0];
                esFilaSustitutiva = ganador.esSust;
                // Problema 3: Confirmar selección correcta de fila
                log(`🔍 Candidatos encontrados para ${mesNombre}: ${candidatos.length}. Ganador esSust=${ganador.esSust}, fecha=${new Date(ganador.fechaMs).toISOString()}, serie=${ganador.serieNum}`);

                // Si el ganador está en otra página, regresar a esa página
                try {
                    const pagActual = await page.evaluate(() => {
                        const el = document.querySelector('.ui-paginator-pages .ui-paginator-page.ui-state-active');
                        return el ? parseInt(el.innerText, 10) : 1;
                    });
                    if (pagActual !== ganador.p) {
                        const pagBtn = page.locator(`.ui-paginator-pages .ui-paginator-page:has-text("${ganador.p}")`).first();
                        if (await pagBtn.count() > 0) {
                            await pagBtn.click();
                            await page.waitForTimeout(300);
                            await esperarCargaSRI(page);
                        }
                    }
                } catch (eNav) {}

                log(`📌 ${esAnual ? 'Declaración Anual Formulario ' + tipo_doc : 'Mes ' + mesNombre} ${anio}: Seleccionada declaración ${esFilaSustitutiva ? 'SUSTITUTIVA' : 'ORIGINAL'} ${candidatos.length > 1 ? '(la de fecha más actual entre ' + candidatos.length + ' encontradas)' : ''}. Presionando ícono de impresora...`);
                prog(`Descargando ${esAnual ? 'Form. ' + tipo_doc : mesNombre} ${anio}...`, `Solicitando archivo al portal SRI (${mesesDescargados.length + 1} de ${mesesProcesar.length})...`, 5);
                log(`Disparando descarga para ${esAnual ? 'año fiscal ' + anio : 'mes ' + mesNombre}...`);

                // Problema 1: Registro de inicio de espera
                const tInicioEspera = Date.now();
                let descargaCapturada = null;
                const dlHandler = d => { 
                    descargaCapturada = d; 
                    // Problema 1: Registro al capturar la descarga
                    log(`✅ Evento 'download' capturado en ${((Date.now() - tInicioEspera) / 1000).toFixed(1)} segundos.`);
                };
                page.on('download', dlHandler);
                context.on('download', dlHandler);

                // Presionar botón de descarga de forma atómica en el navegador
                await page.evaluate(({ idx }) => {
                    const trs = Array.from(document.querySelectorAll('table tbody tr'));
                    const tr = trs[idx] || trs[0];
                    if (!tr) return;
                    const btns = Array.from(tr.querySelectorAll('button'));
                    let btn = btns.find(b => b.innerHTML.includes('ui-icon-print') || b.innerText.includes('Declaración completa') || (b.title && b.title.includes('Declaración completa')));
                    if (!btn && btns.length > 0) btn = btns[btns.length - 1];
                    if (btn) btn.click();
                }, { idx: ganador.idx });

                // Problema 1: Mostrar hora de inicio de la espera
                log(`Clic en impresora ejecutado para ${mesNombre}. Esperando generación del archivo en SRI (inicio: ${new Date(tInicioEspera).toISOString()})...`);

                let esperaSegundos = 0;
                // Problema 1: Aumentar espera de 40 a 200 (60 segundos)
                const maxEsperaSegundos = 200; 
                while (!descargaCapturada && esperaSegundos < maxEsperaSegundos) {
                    await page.waitForTimeout(300);
                    esperaSegundos++;
                    if (esperaSegundos % 6 === 0) {
                        const recargado = await verificarYRecargarSiDenegado(page);
                        if (recargado) {
                            log("🔄 Página recargada tras Acceso Denegado. Reintentando clic en ícono de impresora...");
                            await page.evaluate(({ idx }) => {
                                const trs = Array.from(document.querySelectorAll('table tbody tr'));
                                const tr = trs[idx] || trs[0];
                                if (!tr) return;
                                const btns = Array.from(tr.querySelectorAll('button'));
                                let btn = btns.find(b => b.innerHTML.includes('ui-icon-print') || b.innerText.includes('Declaración completa') || (b.title && b.title.includes('Declaración completa')));
                                if (!btn && btns.length > 0) btn = btns[btns.length - 1];
                                if (btn) btn.click();
                            }, { idx: ganador.idx });
                        }
                    }
                }

                if (!descargaCapturada) {
                    const recargado = await verificarYRecargarSiDenegado(page);
                    if (recargado) {
                        log("🔄 Reintentando espera de descarga tras recargar...");
                        await page.evaluate(({ idx }) => {
                            const trs = Array.from(document.querySelectorAll('table tbody tr'));
                            const tr = trs[idx] || trs[0];
                            if (!tr) return;
                            const btns = Array.from(tr.querySelectorAll('button'));
                            let btn = btns.find(b => b.innerHTML.includes('ui-icon-print') || b.innerText.includes('Declaración completa') || (b.title && b.title.includes('Declaración completa')));
                            if (!btn && btns.length > 0) btn = btns[btns.length - 1];
                            if (btn) btn.click();
                        }, { idx: ganador.idx });
                        let esperaExtra = 0;
                        // Problema 1: Aumentar espera extra de 25 a 100 (30 segundos adicionales)
                        const maxEsperaExtra = 100;
                        while (!descargaCapturada && esperaExtra < maxEsperaExtra) {
                            await page.waitForTimeout(300);
                            esperaExtra++;
                        }
                    }
                }

                page.off('download', dlHandler);
                context.off('download', dlHandler);

                // Problema 2: Incluir sufijo _SUST si es declaración sustitutiva
                const sufijoSust = esFilaSustitutiva ? '_SUST' : '';
                let nombreClaro = `IVA_104_${mesNombre}_${anio}_${ruc}${sufijoSust}.pdf`;
                if (tipo_doc === '103') nombreClaro = `RETENCIONES_103_${mesNombre}_${anio}_${ruc}${sufijoSust}.pdf`;
                else if (esRenta) nombreClaro = `RENTA_ANUAL_101_102_${anio}_${ruc}${sufijoSust}.pdf`;

                const os = require('os');
                let dirDescargas = path.join(os.homedir(), 'Downloads');
                if (!fs.existsSync(dirDescargas)) dirDescargas = path.join(os.homedir(), 'Descargas');
                
                if (fs.existsSync(dirDescargas)) {
                    nombreClaro = obtenerNombreDisponible(dirDescargas, nombreClaro);
                }
                nombreClaro = obtenerNombreDisponible(output_dir, nombreClaro);
                const destPath = path.join(output_dir, nombreClaro);
                if (descargaCapturada) {
                    log(`⬇️ Descarga en curso (${descargaCapturada.suggestedFilename()}). Esperando finalización completa de la red...`);
                    await descargaCapturada.path().catch(() => null);
                    await descargaCapturada.saveAs(destPath);

                    if (fs.existsSync(destPath)) {
                        const stats = fs.statSync(destPath);
                        log(`📄 PDF auténtico descargado del SRI (${stats.size} bytes) guardado como: ${nombreClaro}`);
                        
                        if (fs.existsSync(dirDescargas)) {
                            try {
                                fs.copyFileSync(destPath, path.join(dirDescargas, nombreClaro));
                                log(`✅ Archivo copiado exitosamente a: ${dirDescargas}`);
                            } catch (eCop) {
                                log(`⚠️ No se pudo copiar a Descargas: ${eCop.message}`);
                            }
                        }
                        
                        descargados.push(nombreClaro);
                        mesesDescargados.push(m);
                        prog(`¡${mesNombre} descargado con éxito!`, `Archivos capturados: ${mesesDescargados.length} mes(es).`, 5);
                    } else {
                        log(`⚠️ No se logró guardar en disco el archivo para ${mesNombre}. Se dejará en 0.`);
                    }
                } else {
                    log(`⚠️ No se capturó la descarga para ${mesNombre} tras el tiempo de espera. Se dejará en 0.`);
                }
            } else {
                log(`⚠️ No se encontró ninguna declaración que coincida en la tabla para ${mesNombre} ${anio}.`);
            }
            } catch (errIteracion) {
                log(`⚠️ Error crítico procesando el mes ${m}: ${errIteracion.message}. Saltando al siguiente mes...`);
            }
        }
        } // Fin de bloque else (declaraciones 104/103/renta)

        log(sustitutivaEncontrada 
            ? "⚠️ Se detectó declaración SUSTITUTIVA en el período. Aplicando regla de prioridad: sustitutiva sobre original." 
            : "Declaraciones verificadas en el portal.");

        const datosMeses = {};
        
        if (descargados.length === 0) {
            prog("Sin registros encontrados", `No se encontraron documentos electrónicos ni declaraciones en el portal para este período.`, 6);
        } else {
            prog("¡Sincronización finalizada!", `Se descargaron y guardaron ${descargados.length} archivos.`, 6);
        }
        fs.writeFileSync(resultFile, JSON.stringify({
            status: 'ok',
            msg: descargados.length === 0 ? `No se encontraron documentos en el portal SRI para ${tipo_doc} año ${anio}.` : `Sincronización con SRI completada para ${tipo_doc} año ${anio}.`,
            sustitutiva_aplicada: sustitutivaEncontrada,
            meses_procesados: mesesDescargados,
            datos_meses: datosMeses,
            total_pagado: 0,
            archivos: descargados
        }, null, 2));

        log("Descarga y sincronización finalizadas con éxito.");
    } catch (err) {
        log(`Error en scraping: ${err.message}`);
        fs.writeFileSync(resultFile, JSON.stringify({
            status: 'error',
            error: err.message
        }, null, 2));
        process.exit(1);
    } finally {
        if (typeof liveViewTimer !== 'undefined') clearInterval(liveViewTimer);
        await context.close().catch(() => {});
        process.exit(0);
    }
}

main();



