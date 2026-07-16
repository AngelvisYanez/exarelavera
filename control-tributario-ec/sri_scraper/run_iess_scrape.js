// c:/xampp/htdocs/control-tributario-ec/sri_scraper/run_iess_scrape.js
/**
 * Scraper nativo de Playwright para descarga de Planillas del IESS (Portal Empleadores)
 * URL: https://www.iess.gob.ec/empleador-web/pages/principal.jsf
 */
const fs = require('fs');
const path = require('path');
const os = require('os');

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
        console.error("Uso: node run_iess_scrape.js <ruta_config.json | base64_config>");
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
                    if (rawConfig.length > 5 && rawConfig.includes('{')) break;
                } catch (e) {}
            }
            await new Promise(r => setTimeout(r, 200));
        }
        if (!rawConfig) {
            console.error("Error: El archivo config.json está vacío o inaccesible.");
            process.exit(1);
        }
        config = JSON.parse(rawConfig);
    }

    const { ruc, password, anio, mes, output_dir } = config;

    if (!fs.existsSync(output_dir)) fs.mkdirSync(output_dir, { recursive: true });

    const logFile      = path.join(output_dir, 'scrape.log');
    const resultFile   = path.join(output_dir, 'result.json');
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

    function escribirResultado(obj) {
        try {
            fs.writeFileSync(resultFile, JSON.stringify(obj));
        } catch(e) {}
    }

    log(`Iniciando scraper IESS: Cédula=${ruc}, Año=${anio}, Mes=${mes}`);

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
    const userDataDir = 'C:\\xampp\\htdocs\\control-tributario-ec\\sri_scraper\\chrome_profile_' + ruc;
    const context = await playwright.chromium.launchPersistentContext(userDataDir, {
        headless: true,
        slowMo: 0,
        acceptDownloads: true,
        viewport: null,
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--start-maximized'
        ]
    });
    const page = context.pages().length > 0 ? context.pages()[0] : await context.newPage();

    // Helper: esperar N ms
    const wait = ms => page.waitForTimeout(ms);

    // Helper: screenshot live view
    async function captureScreen() {
        try {
            const scPath = path.join(output_dir, 'live_view.jpg');
            await page.screenshot({ path: scPath, type: 'jpeg', quality: 70, fullPage: false }).catch(() => {});
        } catch(e) {}
    }

    try {
        // ==============================================================
        // PASO 1 – Navegar a la página de login del IESS
        // ==============================================================
        prog('Conectando a IESS...', 'Navegando al portal de Empleadores', 1);
        await page.goto('https://www.iess.gob.ec/empleador-web/pages/principal.jsf', {
            waitUntil: 'domcontentloaded',
            timeout: 60000
        });
        await wait(300);
        await captureScreen();

        // ==============================================================
        // PASO 1 – Login: Esperar campos y completar formulario
        // ==============================================================
        prog('Iniciando sesión...', `Ingresando cédula ${ruc}`, 2);
        log('Buscando campo Cédula...');

        // Revisar si estamos en la pantalla "Escoja una Sucursal" o sesión activa y hay un botón "Salir"
        const salirBtn = await page.evaluate(() => {
            const links = Array.from(document.querySelectorAll('a, button, input[type="button"], input[type="submit"]'));
            return links.find(el => (el.innerText || el.value || '').trim().toUpperCase() === 'SALIR') !== undefined;
        });

        if (salirBtn) {
            log('Detectada sesión previa o pantalla de Sucursal. Haciendo clic en "Salir" para limpiar sesión...');
            await page.evaluate(() => {
                const links = Array.from(document.querySelectorAll('a, button, input[type="button"], input[type="submit"]'));
                const btn = links.find(el => (el.innerText || el.value || '').trim().toUpperCase() === 'SALIR');
                if (btn) btn.click();
            });
            await wait(3000);
            await captureScreen();
        }

        // Esperar el campo cédula con varios selectores posibles
        const cedulaSelector = 'input[name="j_username"], input[id*="cedula"], input[id*="username"], input[id*="usuario"], input[type="text"]:first-of-type';
        let cedulaInput = null;
        for (let i = 0; i < 20; i++) {
            cedulaInput = await page.$(cedulaSelector).catch(() => null);
            if (cedulaInput) break;
            await wait(500);
        }

        if (!cedulaInput) {
            // Intentar con evaluate para encontrar inputs visibles
            cedulaInput = await page.evaluateHandle(() => {
                const inputs = Array.from(document.querySelectorAll('input[type="text"], input:not([type])'));
                return inputs.find(el => el.offsetParent !== null) || null;
            });
        }

        if (cedulaInput) {
            await cedulaInput.click().catch(() => {});
            await wait(500);
            await page.keyboard.type(ruc, { delay: 10 });
            await wait(1000);
            log(`Cédula ingresada: ${ruc}`);
        } else {
            throw new Error('No se encontró el campo de cédula en la página de login del IESS.');
        }

        // Campo contraseña
        log('Buscando campo Clave...');
        const claveSelector = 'input[name="j_password"], input[id*="clave"], input[id*="password"], input[type="password"]';
        let claveInput = await page.$(claveSelector).catch(() => null);
        if (!claveInput) {
            for (let i = 0; i < 10; i++) {
                claveInput = await page.$(claveSelector).catch(() => null);
                if (claveInput) break;
                await wait(400);
            }
        }

        if (claveInput) {
            await claveInput.click().catch(() => {});
            await wait(500);
            await page.keyboard.type(password, { delay: 10 });
            await wait(500);
            log('Contraseña ingresada.');
        } else {
            throw new Error('No se encontró el campo de contraseña.');
        }

        await captureScreen();

        // Clic en botón Ingresar
        log('Haciendo clic en Ingresar...');
        const btnSelectors = [
            'input[value="Ingresar"]',
            'button:has-text("Ingresar")',
            'input[type="submit"]',
            'button[type="submit"]',
            '.ui-button',
            'a:has-text("Ingresar")'
        ];

        let clicked = false;
        for (const sel of btnSelectors) {
            const btn = await page.$(sel).catch(() => null);
            if (btn) {
                await btn.click().catch(() => {});
                clicked = true;
                log(`Clic en: ${sel}`);
                break;
            }
        }
        if (!clicked) await page.keyboard.press('Enter');

        // ==============================================================
        // PASO 2 – Verificar ingreso: esperar pantalla del empleador
        // ==============================================================
        log('Esperando pantalla de bienvenida del empleador...');
        let loggedIn = false;
        for (let i = 0; i < 60; i++) { // 60 intentos de 500ms = 30 segundos
            await wait(500);
            if (i % 2 === 0) await captureScreen(); // Capturar pantalla solo cada segundo para no cargar mucho
            const bodyText = await page.evaluate(() => (document.body?.innerText || '').toUpperCase()).catch(() => '');
            if (
                bodyText.includes('SE\u00d1OR EMPLEADOR') ||
                bodyText.includes('SEÑOR EMPLEADOR') ||
                bodyText.includes('BIENVENIDO') ||
                bodyText.includes('PRINCIPAL') ||
                bodyText.includes('PLANILLAS') ||
                page.url().includes('principal') && !page.url().includes('login')
            ) {
                loggedIn = true;
                log('Pantalla del empleador detectada.');
                break;
            }
            // Verificar si hay error de login
            if (bodyText.includes('CLAVE INCORRECTA') || bodyText.includes('USUARIO NO EXISTE') || bodyText.includes('ERROR') && bodyText.includes('CREDENCIAL')) {
                throw new Error('Credenciales incorrectas. Verifique la cédula y clave del IESS.');
            }
        }

        if (!loggedIn) {
            const currentUrl = page.url();
            if (currentUrl.includes('principal') || currentUrl.includes('employer')) {
                loggedIn = true;
                log('Autenticación inferida por URL: ' + currentUrl);
            } else {
                throw new Error('No se pudo verificar el inicio de sesión en el IESS. La página no mostró la pantalla esperada.');
            }
        }

        await wait(300);
        prog('Sesión iniciada', 'Acceso al portal del empleador confirmado', 2);

        // ==============================================================
        // PASO 3 y 4 – Ir a Consulta de Aportes (Planillas)
        // ==============================================================
        prog('Abriendo Consulta de Aportes...', 'Navegando directamente a la sección de planillas', 3);
        log('Navegando a https://www.iess.gob.ec/empleador-web/pages/consultas/consultaPlanillas.jsf...');
        
        await page.goto('https://www.iess.gob.ec/empleador-web/pages/consultas/consultaPlanillas.jsf', {
            waitUntil: 'domcontentloaded',
            timeout: 60000
        }).catch(e => log('Error al navegar directo: ' + e.message));

        await wait(300);
        await captureScreen();

        // Esperar que cargue el formulario de consulta
        log('Esperando formulario CONSULTA DE PLANILLAS...');
        for (let i = 0; i < 50; i++) {
            await wait(300);
            if (i % 3 === 0) await captureScreen();
            const bodyText = await page.evaluate(() => (document.body?.innerText || '').toUpperCase()).catch(() => '');
            if (bodyText.includes('CONSULTA DE PLANILLAS') || bodyText.includes('CRITERIOS DE BÚSQUEDA') || bodyText.includes('ALCANCE DE CONSULTA')) {
                log('Formulario de consulta detectado.');
                break;
            }
        }

        await wait(300);

        // ==============================================================
        // PASO 5 – Configurar la consulta
        // ==============================================================
        prog('Configurando consulta...', 'Seleccionando Consolidado y período', 4);

        // Construir período desde/hasta
        let periodoDesde = '';
        let periodoHasta = '';
        if (mes === 'rango' && typeof anio === 'string' && anio.includes('_')) {
            const parts = anio.split('_');
            periodoDesde = parts[0];
            periodoHasta = parts[1];
        } else {
            const mesStr = mes === 'todos' ? '01' : (String(mes).length === 1 ? '0' + mes : String(mes));
            const mesHastaStr = mes === 'todos' ? '12' : mesStr;
            periodoDesde = `${anio}-${mesStr}`;
            periodoHasta = `${anio}-${mesHastaStr}`;
        }

        log(`Período configurado: Desde ${periodoDesde} Hasta ${periodoHasta}`);
        await captureScreen();

        // Alcance de Consulta → Seleccionar "Consolidado"
        log('Configurando Alcance = Consolidado...');
        const alcanceSet = await page.evaluate(() => {
            // Buscar selects o radios relacionados con "Alcance"
            const labels = Array.from(document.querySelectorAll('label, td, th, span'));
            for (const lbl of labels) {
                if ((lbl.innerText || '').toUpperCase().includes('ALCANCE')) {
                    // Buscar el select o radio cercano
                    const parent = lbl.closest('tr, div, td, form') || lbl.parentElement;
                    if (parent) {
                        const sel = parent.querySelector('select');
                        if (sel) {
                            for (const opt of sel.options) {
                                if (opt.text.toUpperCase().includes('CONSOLIDADO')) {
                                    sel.value = opt.value;
                                    sel.dispatchEvent(new Event('change', { bubbles: true }));
                                    return 'select-' + opt.text;
                                }
                            }
                        }
                        // Buscar radio buttons
                        const radios = parent.querySelectorAll('input[type="radio"]');
                        for (const r of radios) {
                            const rLbl = document.querySelector(`label[for="${r.id}"]`);
                            if (rLbl && rLbl.innerText.toUpperCase().includes('CONSOLIDADO')) {
                                r.click();
                                return 'radio-' + rLbl.innerText;
                            }
                        }
                    }
                }
            }
            // Fallback: buscar cualquier opción "Consolidado"
            const allOpts = Array.from(document.querySelectorAll('option, input[type="radio"]'));
            for (const opt of allOpts) {
                const txt = (opt.innerText || opt.value || '').toUpperCase();
                if (txt.includes('CONSOLIDADO')) {
                    if (opt.tagName === 'OPTION') {
                        opt.selected = true;
                        opt.parentElement.dispatchEvent(new Event('change', { bubbles: true }));
                        return 'option-' + opt.innerText;
                    } else {
                        opt.click();
                        return 'radio-' + opt.value;
                    }
                }
            }
            return null;
        }).catch(() => null);

        log(`Alcance configurado: ${alcanceSet || 'no encontrado, continuando...'}`);
        // IMPORTANTE: Esperar a que PrimeFaces termine el AJAX refresh de la página al cambiar a Consolidado
        await wait(3000);
        await captureScreen();

        // Configurar campos "Desde" y "Hasta"
        log(`Ingresando período Desde: ${periodoDesde} y Hasta: ${periodoHasta}`);
        const periodoSet = await page.evaluate(({desde, hasta}) => {
            // Obtener todos los inputs de texto visibles que no estén deshabilitados
            const inputs = Array.from(document.querySelectorAll('input[type="text"], input:not([type])'))
                .filter(el => el.offsetParent !== null && !el.disabled && !el.readOnly);
            
            // En el formulario de Consulta de Planillas, los últimos dos inputs de texto
            // suelen ser "Desde" y "Hasta" (el primero a veces es "Cédula del Afiliado").
            if (inputs.length >= 2) {
                const desdeInp = inputs[inputs.length - 2];
                const hastaInp = inputs[inputs.length - 1];

                desdeInp.value = '';
                desdeInp.focus();
                desdeInp.value = desde;
                desdeInp.dispatchEvent(new Event('input', { bubbles: true }));
                desdeInp.dispatchEvent(new Event('change', { bubbles: true }));
                desdeInp.dispatchEvent(new KeyboardEvent('keyup', { bubbles: true }));

                hastaInp.value = '';
                hastaInp.focus();
                hastaInp.value = hasta;
                hastaInp.dispatchEvent(new Event('input', { bubbles: true }));
                hastaInp.dispatchEvent(new Event('change', { bubbles: true }));
                hastaInp.dispatchEvent(new KeyboardEvent('keyup', { bubbles: true }));
                
                return `set: desde=${desdeInp.value}, hasta=${hastaInp.value}`;
            }
            return 'not-found';
        }, {desde: periodoDesde, hasta: periodoHasta}).catch(() => 'error setting fechas');
        
        log(`Campos Período: ${periodoSet}`);
        // Esperar un segundo después de llenar para que se asienten los valores en el DOM
        await wait(1000);
        await captureScreen();

        // ==============================================================
        // PASO 6 – Clic en Aceptar / Consultar
        // ==============================================================
        prog('Consultando planillas...', `Buscando planillas del período ${periodoDesde} al ${periodoHasta}`, 4);
        log('Haciendo clic en Aceptar/Consultar...');

        const aceptarResult = await page.evaluate(() => {
            const btns = Array.from(document.querySelectorAll('input[type="submit"], input[type="button"], button, a'));
            const btn = btns.find(b => {
                const t = (b.innerText || b.value || b.textContent || '').trim().toUpperCase();
                return t === 'ACEPTAR' || t === 'CONSULTAR' || t === 'BUSCAR';
            });
            if (btn) { btn.click(); return btn.innerText || btn.value || 'clicked'; }
            return null;
        }).catch(() => null);

        if (!aceptarResult) {
            await page.locator('text=Aceptar').first().click().catch(() => {});
            await page.locator('text=Consultar').first().click().catch(() => {});
        }

        log(`Consulta iniciada: ${aceptarResult}`);

        // Esperar a que el modal de carga "Por favor espere" desaparezca
        log('Esperando que desaparezca el mensaje de carga...');
        for (let i = 0; i < 150; i++) {
            await wait(200);
            const loadingModal = await page.evaluate(() => {
                const text = document.body?.innerText?.toUpperCase() || '';
                return text.includes('POR FAVOR ESPERE MIENTRAS EL SISTEMA') || text.includes('PROCESANDO');
            }).catch(() => false);
            if (!loadingModal) break;
        }

        // Esperar resultados (hasta 30 segundos)
        log('Esperando tabla de resultados...');
        let tablaEncontrada = false;
        for (let i = 0; i < 100; i++) {
            await wait(300);
            if (i % 3 === 0) await captureScreen();
            const hayTabla = await page.evaluate(() => {
                const rows = document.querySelectorAll('table tbody tr, .ui-datatable tbody tr');
                return rows.length > 0 && !(document.body?.innerText || '').toUpperCase().includes('NO EXISTEN REGISTROS');
            }).catch(() => false);
            if (hayTabla) {
                log('Tabla de resultados detectada.');
                tablaEncontrada = true;
                break;
            }
        }
        
        if (!tablaEncontrada) {
            log('No se detectó la tabla de resultados después de la espera.');
        }

        await wait(300);
        await captureScreen();

        // ==============================================================
        // PASO 7-9 – Ir a Descarga de Archivos y hacer clic en PDF
        // ==============================================================
        prog('Descargando planilla PDF...', 'Buscando sección de Descarga de Archivos', 5);
        log('Buscando sección "Descarga de Archivos"...');

        // Scroll hacia abajo para ver la sección de descarga
        await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight)).catch(() => {});
        await wait(500);
        await captureScreen();

        // Esperar descarga en la página actual o en cualquier pestaña nueva (popup)
        const downloadPromise = new Promise((resolve) => {
            page.once('download', resolve);
            context.on('page', newPage => {
                newPage.once('download', resolve);
            });
            setTimeout(() => resolve(null), 60000);
        });

        let pdfClicked = false;
        try {
            const targetSelector = 'a:has(img[src*="pdf.png" i]), img[src*="pdf.png" i], img[id*="imgExportarPdf" i]';
            await page.waitForSelector(targetSelector, { timeout: 10000 });
            await page.evaluate((sel) => {
                const el = document.querySelector(sel);
                if (el) {
                    const anchor = el.closest('a') || el;
                    anchor.click();
                }
            }, targetSelector);
            pdfClicked = 'eval-click-anchor';
        } catch (e) {
            pdfClicked = 'error: ' + e.message;
        }

        log(`PDF clic: ${pdfClicked}`);

        // ==============================================================
        // PASO 10 – Esperar y guardar el PDF descargado
        // ==============================================================
        prog('Guardando PDF...', 'Esperando que el navegador genere el documento PDF', 5);
        const downloadEvt = await downloadPromise;

        let archivos = [];

        if (downloadEvt) {
            log('Descarga detectada: ' + (downloadEvt.suggestedFilename() || 'planilla_iess.pdf'));
            
            let nombreArchivo = `PLANILLA_IESS_${anio}_${ruc}.pdf`;
            const os = require('os');
            let dirDescargas = path.join(os.homedir(), 'Downloads');
            if (!fs.existsSync(dirDescargas)) dirDescargas = path.join(os.homedir(), 'Descargas');
            
            if (fs.existsSync(dirDescargas)) {
                nombreArchivo = obtenerNombreDisponible(dirDescargas, nombreArchivo);
            }
            nombreArchivo = obtenerNombreDisponible(output_dir, nombreArchivo);
            const rutaGuardado = path.join(output_dir, nombreArchivo);
            
            log('Esperando que finalice la descarga en el disco (Playwright Temp)...');
            await downloadEvt.saveAs(rutaGuardado).catch(e => log('Error al guardar: ' + e.message));
            
            await wait(1000);

            if (fs.existsSync(rutaGuardado)) {
                const stats = fs.statSync(rutaGuardado);
                if (stats.size > 0) {
                    log(`PDF guardado correctamente (${stats.size} bytes): ${rutaGuardado}`);
                    
                    if (fs.existsSync(dirDescargas)) {
                        try {
                            fs.copyFileSync(rutaGuardado, path.join(dirDescargas, nombreArchivo));
                            log(`✅ Archivo copiado exitosamente a: ${dirDescargas}`);
                        } catch (eCop) {
                            log(`⚠️ No se pudo copiar a Descargas: ${eCop.message}`);
                        }
                    }
                    
                    archivos.push(nombreArchivo);
                } else {
                    throw new Error('El archivo se descargó pero tiene 0 bytes.');
                }
            } else {
                throw new Error('El archivo no se encontró tras el intento de guardado en: ' + rutaGuardado);
            }
        } else {
            throw new Error('El scraper hizo clic, pero el navegador no disparó la descarga del PDF dentro de los 45 segundos.');
        }

        await captureScreen();
        log('Proceso finalizado. Cerrando navegador...');
        await context.close().catch(() => {});

        if (archivos.length > 0) {
            log('¡Proceso completado! Archivos descargados: ' + archivos.join(', '));
            escribirResultado({ status: 'ok', archivos: archivos });
            process.exit(0);
        } else {
            // Si el proceso llegó hasta aquí pero no hay archivos, igual reportamos ok con mensaje
            log('El proceso navigó correctamente pero no se pudo capturar el archivo PDF. Posiblemente el portal generó el archivo en el navegador sin disparar un evento de descarga.');
            escribirResultado({
                status: 'error',
                error: 'El scraper navegó al IESS y llegó al paso de descarga, pero el archivo PDF no se capturó automáticamente. Intente la descarga manual desde el portal.',
                archivos: []
            });
            process.exit(1);
        }

    } catch (e) {
        log(`Error general: ${e.message}`);
        log(e.stack || '');
        await captureScreen();
        await context.close().catch(() => {});
        escribirResultado({ status: 'error', error: e.message });
        process.exit(1);
    }
}

main();
