const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright-extra');
const stealth = require('puppeteer-extra-plugin-stealth')();

chromium.use(stealth);

async function main() {
    const argInput = process.argv[2];
    if (!argInput) {
        console.error("Uso: node run_exa_scrape.js <ruta_config.json | base64_config>");
        process.exit(1);
    }

    let config;
    if (argInput.startsWith('base64:')) {
        const jsonStr = Buffer.from(argInput.substring(7), 'base64').toString('utf8');
        config = JSON.parse(jsonStr);
    } else {
        const rawConfig = fs.readFileSync(argInput, 'utf8');
        config = JSON.parse(rawConfig);
    }

    const { username, password, empresa, job_id, anio, mes_desde, mes_hasta } = config;

    const output_dir = path.resolve(__dirname, `../uploads/sri_auto/${job_id || 'exa_manual'}`);
    if (!fs.existsSync(output_dir)) {
        fs.mkdirSync(output_dir, { recursive: true });
    }

    const progressFile = path.join(output_dir, 'progress.json');
    function prog(title, desc, step = 1) {
        console.log(`[PROGRESS Step ${step}] ${title} | ${desc}`);
        try {
            const tmpProg = progressFile + '.tmp';
            fs.writeFileSync(tmpProg, JSON.stringify({ title, desc, step, timestamp: Date.now() }));
            try { fs.renameSync(tmpProg, progressFile); } catch(e) { fs.copyFileSync(tmpProg, progressFile); }
        } catch(e) {}
    }

    console.log(`Iniciando conexión a EXA ERP con usuario: ${username}`);
    prog("Iniciando navegador...", "Preparando la sesión de Playwright para EXA ERP...", 1);

    const browser = await chromium.launch({
        headless: false, // para que el usuario pueda ver, si es necesario, o false para headless
        args: ['--start-maximized']
    });

    const context = await browser.newContext({
        viewport: null
    });

    const page = await context.newPage();
    
    // Configurar tiempo máximo de espera a 2 minutos (120000ms) para conexiones lentas
    page.setDefaultNavigationTimeout(120000);
    page.setDefaultTimeout(120000);

    let liveViewTimer = setInterval(async () => {
        try {
            if (page && !page.isClosed()) {
                const livePath = path.join(output_dir, 'live_view.jpg');
                await page.screenshot({ path: livePath, quality: 55, type: 'jpeg' }).catch(() => {});
            }
        } catch (e) {}
    }, 700);

    try {
        prog("Abriendo EXA...", "Accediendo a la URL principal de EXA...", 1);
        console.log("Navegando a https://exa.ofsercont.com/index.php...");
        await page.goto('https://exa.ofsercont.com/index.php', { waitUntil: 'domcontentloaded' });

        prog("Esperando formulario de acceso...", "Buscando los campos de login de EXA...", 1);
        console.log("Esperando formulario de login...");
        
        // --- PASO 1 USUARIO ---
        prog("Ingresando usuario...", "Escribiendo el nombre de usuario...", 1);
        const userSelector = 'input[type="text"]:not([readonly]), input[name="usuario"], input[id="usuario"], input[placeholder*="usuario" i], input[placeholder*="RUC" i]';
        await page.waitForSelector(userSelector, { timeout: 15000, state: 'visible' }).catch(() => null);
        const userInputs = await page.$$(userSelector);
        
        await page.waitForTimeout(1000);
        let userFilled = false;
        if (userInputs.length > 0) {
            await userInputs[0].click();
            await page.waitForTimeout(200);
            await userInputs[0].type(username, { delay: 50 });
            let val = await userInputs[0].inputValue();
            if (!val) await userInputs[0].fill(username);
            
            val = await userInputs[0].inputValue();
            if(val) userFilled = true;
        } else {
            await page.keyboard.type(username, { delay: 50 });
            userFilled = true;
        }
        
        if(!userFilled) throw new Error("No se pudo escribir el usuario");
        prog("Usuario ingresado correctamente", "Validado", 1);
        console.log("Usuario ingresado correctamente");

        // --- PASO 2 CONTRASEÑA ---
        prog("Ingresando contraseña...", "Escribiendo la clave...", 1);
        const passSelector = 'input[type="password"]';
        await page.waitForSelector(passSelector, { timeout: 15000, state: 'visible' }).catch(() => null);
        const passInputs = await page.$$(passSelector);
        
        let passFilled = false;
        if (passInputs.length > 0) {
            await passInputs[0].click();
            await page.waitForTimeout(200);
            await passInputs[0].type(password, { delay: 50 });
            let val = await passInputs[0].inputValue();
            if (!val) await passInputs[0].fill(password);
            
            val = await passInputs[0].inputValue();
            if(val) passFilled = true;
        } else {
            await page.keyboard.press('Tab');
            await page.waitForTimeout(200);
            await page.keyboard.type(password, { delay: 50 });
            passFilled = true;
        }
        
        if(!passFilled) throw new Error("No se pudo escribir la contraseña");
        prog("Contraseña ingresada correctamente", "Validado", 1);
        console.log("Contraseña ingresada correctamente");

        // --- PASO 3, 4 y 5 CARGA Y SELECCIÓN DE EMPRESAS ---
        let empresaSelectedAndVerified = false;
        if (empresa) {
            prog("Esperando carga de empresas...", "El sistema está obteniendo las empresas disponibles...", 2);
            console.log("Esperando carga de empresas...");
            
            // El usuario indicó esperar menos de 2 segundos después de la contraseña
            await page.waitForTimeout(1500);
            
            // Verificar si hay algún selector presente (ampliando búsqueda)
            const hasSelectors = await page.$('select, .select2-selection, input[name*="emp" i], input[id*="emp" i], input[name*="comp" i], input[type="text"]');
            if(!hasSelectors) throw new Error("No se pudo habilitar el selector de empresas");
            
            prog(`Buscando empresa: ${empresa}...`, "Examinando la lista...", 2);
            console.log(`Buscando empresa: ${empresa}...`);
            let empresaEncontrada = false;
            
            // Select2
            const select2 = await page.$('.select2-selection').catch(() => null);
            if (select2 && await select2.isVisible()) {
                empresaEncontrada = true;
                prog("Empresa encontrada", "Componente Select2 detectado", 2);
                prog("Seleccionando empresa...", "Escribiendo en Select2...", 2);
                
                await select2.click();
                await page.waitForTimeout(1000);
                await page.keyboard.type(empresa, { delay: 50 });
                await page.waitForTimeout(1000);
                await page.keyboard.press('Enter');
                await page.waitForTimeout(500);
                
                // Validar select subyacente
                const realSelect = await page.$('select');
                if(realSelect) {
                    const val = await page.evaluate(s => s.value, realSelect);
                    if(val && val !== '') empresaSelectedAndVerified = true;
                } else {
                    empresaSelectedAndVerified = true;
                }
            } 
            
            // Select normal
            if (!empresaEncontrada) {
                const selects = await page.$$('select');
                for (const s of selects) {
                    if (await s.isVisible()) {
                        const options = await s.$$eval('option', opts => opts.map(o => ({ value: o.value, text: o.innerText })));
                        const match = options.find(o => o.text.toLowerCase().includes(empresa.toLowerCase()));
                        if (match) {
                            empresaEncontrada = true;
                            prog("Empresa encontrada", "Select tradicional detectado", 2);
                            prog("Seleccionando empresa...", "Escogiendo opción...", 2);
                            
                            await s.selectOption(match.value);
                            
                            // Forzar eventos
                            await s.dispatchEvent('change');
                            await s.dispatchEvent('input');
                            await s.dispatchEvent('blur');
                            
                            // Verificar que quedó seleccionada
                            const selectedVal = await page.evaluate(el => el.value, s);
                            if(selectedVal === match.value) empresaSelectedAndVerified = true;
                            break;
                        }
                    }
                }
            }
                
            // Input predictivo o fallback amplio
            if (!empresaEncontrada) {
                // Buscamos cualquier input que no sea usuario ni contraseña (podría ser el tercero)
                const empInput = await page.$('input[name*="emp" i], input[id*="emp" i], input[name*="comp" i], input[placeholder*="empresa" i]');
                
                let targetInput = empInput;
                if (!targetInput) {
                    // Fallback: buscar el siguiente input de texto después del usuario
                    const allTextInputs = await page.$$('input[type="text"]:not([readonly])');
                    if (allTextInputs.length > 1) {
                        targetInput = allTextInputs[allTextInputs.length - 1]; // Asumimos que el último es la empresa
                    }
                }
                
                if (targetInput && await targetInput.isVisible()) {
                    empresaEncontrada = true;
                    prog("Empresa encontrada", "Input de texto detectado", 2);
                    prog("Seleccionando empresa...", "Escribiendo nombre...", 2);
                    
                    await targetInput.click();
                    await targetInput.fill('');
                    await targetInput.type(empresa, { delay: 50 });
                    await page.waitForTimeout(1000);
                    await page.keyboard.press('ArrowDown');
                    await page.waitForTimeout(500);
                    await page.keyboard.press('Enter');
                    
                    await targetInput.dispatchEvent('change');
                    await targetInput.dispatchEvent('blur');
                    
                    const val = await targetInput.inputValue();
                    // Relajamos un poco la validación en caso de que el sistema asigne IDs en lugar del nombre al input visible
                    if(val) empresaSelectedAndVerified = true;
                }
            }
            
            if (!empresaEncontrada) {
                throw new Error(`No se encontró la empresa: ${empresa}`);
            }
            
            if (!empresaSelectedAndVerified) {
                throw new Error("La empresa fue encontrada, pero no pudo seleccionarse correctamente");
            }
            
            prog(`Empresa seleccionada correctamente: ${empresa}`, "Validado", 2);
            console.log(`Empresa seleccionada correctamente: ${empresa}`);
        } else {
            empresaSelectedAndVerified = true; // Si no hay empresa configurada
        }

        // --- PASO 6 VALIDACIÓN FINAL ---
        prog("Verificando datos de acceso...", "Validando formulario antes de enviar...", 2);
        
        let allValid = true;
        const uInputs = await page.$$(userSelector);
        if(uInputs.length > 0) {
            if(!(await uInputs[0].inputValue())) allValid = false;
        }
        const pInputs = await page.$$(passSelector);
        if(pInputs.length > 0) {
            if(!(await pInputs[0].inputValue())) allValid = false;
        }
        if(!empresaSelectedAndVerified) allValid = false;
        
        if(!allValid) {
            throw new Error("Error en validación final: campos incompletos");
        }
        
        prog("Datos de acceso completos", "Listos para enviar", 2);

        // --- PASO 7 INICIAR SESIÓN ---
        prog("Iniciando sesión en EXA...", "Enviando formulario...", 2);
        
        const btnEntrar = await page.$('input[type="submit"], button[type="submit"], button:has-text("Ingresar"), button:has-text("Entrar")');
        
        const currentUrl = page.url();
        
        if (btnEntrar) {
            await Promise.all([
                page.waitForNavigation({ waitUntil: 'networkidle', timeout: 120000 }).catch(() => {}),
                btnEntrar.click()
            ]);
        } else {
            await Promise.all([
                page.waitForNavigation({ waitUntil: 'networkidle', timeout: 120000 }).catch(() => {}),
                page.keyboard.press('Enter')
            ]);
        }
        
        // Verificar resultado real
        const postUrl = page.url();
        const hasError = await page.$('text="Datos incorrectos", text="error", .error-message, .alert-danger').catch(() => null);
        
        if(hasError && await hasError.isVisible()) {
            throw new Error("Datos incorrectos u error en el sistema al iniciar sesión");
        }
        if(postUrl === currentUrl || postUrl.includes('index.php')) {
            // Verificar si el dashboard en realidad cargó pero la URL no cambió (usando SPA)
            const dashboardMenu = await page.$('.menu, nav, #sidebar, .dashboard').catch(() => null);
            if(!dashboardMenu) {
                throw new Error("No se pudo iniciar sesión. Sigue en el login.");
            }
        }
        
        prog("Sesión iniciada correctamente", "Ingreso exitoso a EXA", 2);
        console.log("Sesión iniciada correctamente");
        
        prog("Buscando Módulo...", "Navegando a Control Tributario...", 3);
        
        await page.goto('https://exa.ofsercont.com/tesoreria/FRONT/tes_alt_con_trib_1.0.php', { waitUntil: 'domcontentloaded' });
        await page.waitForLoadState('networkidle').catch(() => null);

        prog("Configurando Filtros...", "Ingresando año y mes en el reporte...", 4);
        
        // Seleccionar año
        if (anio) {
            console.log(`Seleccionando año: ${anio}`);
            const anioSelect = await page.$('select[name*="anio"], select[id*="anio"], select[name*="year"], select[id*="year"]');
            if (anioSelect) {
                await anioSelect.selectOption({ label: anio.toString() }).catch(async () => {
                    await anioSelect.selectOption(anio.toString()).catch(() => {});
                });
            } else {
                console.log("No se encontró dropdown de año, intentando type...");
                const anioInput = await page.$('input[name*="anio"], input[id*="anio"]');
                if (anioInput) {
                    await anioInput.fill(anio.toString());
                }
            }
        }

        // Seleccionar meses (desde y hasta)
        if (mes_desde) {
            console.log(`Seleccionando mes desde: ${mes_desde}`);
            const selectMesDesde = await page.$('select[name*="mes_desde"], select[name*="mes1"], select[id*="mes_desde"], select[name*="mes"]');
            if (selectMesDesde) {
                await selectMesDesde.selectOption({ label: mes_desde.toString().padStart(2, '0') }).catch(async () => {
                    await selectMesDesde.selectOption(mes_desde.toString()).catch(() => {});
                });
            }
        }
        if (mes_hasta) {
            console.log(`Seleccionando mes hasta: ${mes_hasta}`);
            const selectMesHasta = await page.$('select[name*="mes_hasta"], select[name*="mes2"], select[id*="mes_hasta"]');
            if (selectMesHasta) {
                await selectMesHasta.selectOption({ label: mes_hasta.toString().padStart(2, '0') }).catch(async () => {
                    await selectMesHasta.selectOption(mes_hasta.toString()).catch(() => {});
                });
            }
        }

        let targetPage = page;
        let newPage = null;
        
        // Buscar el botón de "Vista Previa" como prioridad, seguido de otros posibles
        const btnConsultar = await page.$('input[value*="Vista Previa" i], button:has-text("Vista Previa"), a:has-text("Vista Previa"), button:has-text("Consultar"), button:has-text("Buscar"), input[value="Consultar"]');
        
        if (btnConsultar) {
            console.log("Clic en Consultar/Buscar/Vista Previa");
            // Reducimos radicalmente el tiempo de espera de nueva pestaña a 3 segundos. 
            // Si el reporte se abre en la misma pestaña, no queremos quedarnos trabados 60 segundos.
            const [popup] = await Promise.all([
                context.waitForEvent('page', { timeout: 3000 }).catch(() => null),
                btnConsultar.click()
            ]);
            
            newPage = popup;
            
            if (newPage) {
                console.log("Se abrió una nueva pestaña, cambiando el foco a la nueva pestaña.");
                targetPage = newPage;
                await targetPage.waitForLoadState('domcontentloaded');
            } else {
                console.log("No se detectó pestaña nueva. Asumiendo que el reporte cargó en la misma ventana.");
            }
        }

        prog("Procesando reporte...", "Esperando a que la tabla cargue (puede tardar un momento)...", 5);

        // Encontrar botón Exportar a Excel y prepararnos para la descarga
        console.log("Buscando botón de Excel en la nueva pestaña...");
        const excelSelector = 'a:has(img[src*="excel" i]), a:has(img[src*="xls" i]), img[src*="excel" i], img[src*="xls" i], button:has-text("Excel"), a:has-text("Excel")';
        
        // Esperamos hasta 60 segundos por si el reporte es pesado
        await targetPage.waitForSelector(excelSelector, { timeout: 60000 }).catch(() => console.log("No se encontró el botón de Excel con el selector principal."));
        
        const btnExportar = await targetPage.$(excelSelector);
        
        if (btnExportar) {
            console.log("¡Botón de Excel encontrado! Intentando descargar...");
            const [download] = await Promise.all([
                targetPage.waitForEvent('download', { timeout: 45000 }).catch(() => null),
                btnExportar.click().catch(() => targetPage.evaluate(sel => document.querySelector(sel)?.click(), excelSelector))
            ]);
            
            if (download) {
                const fileName = download.suggestedFilename() || `ControlTrib-${anio}-${mes_desde || mes}.xls`;
                // Guardar en output_dir local (uploads/sri_auto/job_id)
                const finalPath = path.join(output_dir, fileName);
                await download.saveAs(finalPath);
                console.log(`¡Archivo guardado internamente en ${finalPath}!`);
                
                // Además, guardamos una copia en el directorio de descargas del usuario (Windows)
                try {
                    const os = require('os');
                    const userDownloadsDir = path.join(os.homedir(), 'Downloads');
                    const finalDownloadsPath = path.join(userDownloadsDir, fileName);
                    fs.copyFileSync(finalPath, finalDownloadsPath);
                    console.log(`¡Copia guardada en Descargas locales: ${finalDownloadsPath}!`);
                } catch(e) {
                    console.error("No se pudo copiar a Descargas: ", e);
                }
                
                // Extraer datos de la tabla respetando colspan y rowspan para que las columnas cuadren siempre
                // Buscamos la tabla que tenga el texto FORMULARIO 104 para evitar tablas de debug o datepickers
                const exa_data = await targetPage.$$eval('table', tables => {
                    let targetTable = null;
                    for (const tbl of tables) {
                        if (tbl.innerText.includes('FORMULARIO 104')) {
                            targetTable = tbl;
                            break;
                        }
                    }
                    if (!targetTable) return [];
                    
                    let grid = [];
                    const trs = Array.from(targetTable.querySelectorAll('tr'));
                    trs.forEach((tr, rowIndex) => {
                        if (!grid[rowIndex]) grid[rowIndex] = [];
                        let colIndex = 0;
                        Array.from(tr.querySelectorAll('th, td')).forEach(cell => {
                            while (grid[rowIndex][colIndex] !== undefined) {
                                colIndex++;
                            }
                            const rowspan = parseInt(cell.getAttribute('rowspan') || '1', 10);
                            const colspan = parseInt(cell.getAttribute('colspan') || '1', 10);
                            const text = cell.innerText.trim();
                            for (let r = 0; r < rowspan; r++) {
                                for (let c = 0; c < colspan; c++) {
                                    if (!grid[rowIndex + r]) grid[rowIndex + r] = [];
                                    grid[rowIndex + r][colIndex + c] = text;
                                }
                            }
                        });
                    });
                    return grid;
                }).catch(() => []);
                console.log(`Extraídas ${exa_data.length} filas de la tabla EXA.`);
                
                // Actualizar json para que la UI sepa que se descargó y pueda procesarlo
                const resultFile = path.join(output_dir, 'result.json');
                fs.writeFileSync(resultFile, JSON.stringify({
                    status: 'ok',
                    job_id: job_id,
                    archivos: [fileName],
                    archivo_path: finalPath,
                    exa_data: exa_data
                }));
                
                // Cerrar la ventana del reporte (popup) tal como se hace manualmente
                if (newPage && !newPage.isClosed()) {
                    console.log("Cerrando la pestaña del reporte EXA...");
                    await newPage.close();
                }
                
            } else {
                console.log("No se detectó el evento de descarga.");
            }
        } else {
            console.log("No se encontró el botón Exportar a Excel.");
        }

        prog("¡Auditoría EXA Completada!", "El reporte de Control Tributario fue extraído.", 6);

        // Opcional: Cerrar más rápido
        await page.waitForTimeout(500);
        
    } catch (err) {
        prog("Error de conexión", err.message, 1);
        console.error("Error en la conexión:", err);
    } finally {
        clearInterval(liveViewTimer);
        // Si queremos mantener el navegador abierto, omitimos close()
        // await browser.close();
        console.log("Finalizando script run_exa_scrape.js");
    }
}

main();
