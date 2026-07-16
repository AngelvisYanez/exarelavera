const fs = require('fs');
const file = 'C:/xampp/htdocs/control-tributario-ec/sri_scraper/run_scrape.js';
const content = fs.readFileSync(file, 'utf8');
const startIdx = content.indexOf('if (esRetencionesRec) {');
const endIdx = content.indexOf('} else if (esAts) {');

if (startIdx !== -1 && endIdx !== -1) {
    const newContent = content.substring(0, startIdx) + 
`if (esRetencionesRec) {
            log("Iniciando Paso a Paso: Descarga de Retenciones Recibidas");

            // 2. Abrir comprobantes recibidos
            log("Dar clic en Comprobantes Electrónicos");
            const locFactElec = page.locator('a:has-text("Comprobantes Electrónicos"), span:has-text("Comprobantes Electrónicos")').first();
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

            log("Esperando indicador de carga...");
            await page.waitForSelector('.ui-widget-overlay, .ui-blockui, .loading, #status, .spinner', { state: 'hidden', timeout: 15000 }).catch(() => {});
            await page.waitForLoadState('networkidle').catch(() => {});

            let mesesProcesar = [];
            if (mes === 'todos') mesesProcesar = [1,2,3,4,5,6,7,8,9,10,11,12];
            else if (mes === 'sem1') mesesProcesar = [1,2,3,4,5,6];
            else if (mes === 'sem2') mesesProcesar = [7,8,9,10,11,12];
            else mesesProcesar = [parseInt(mes)];

            if (Array.isArray(config.omitir_meses) && config.omitir_meses.length > 0) {
                log(\`Excluyendo meses: \${config.omitir_meses.join(', ')}\`);
                mesesProcesar = mesesProcesar.filter(m => !config.omitir_meses.includes(m));
            }

            const mesesNombres = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

            for (const m of mesesProcesar) {
                const mesNombre = mesesNombres[m - 1];
                log(\`Consultando mes: \${mesNombre} \${anio}\`);

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
                        if (txt.includes('\\n1\\n') || txt.includes('\\n15\\n') || txt.includes('Todos') || (s.options.length >= 28 && s.options.length <= 33)) {
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
                
                const captchaKeys = {
                    anticaptchaKey: config.anticaptcha_key || process.env.ANTICAPTCHA_KEY || '',
                    twocaptchaKey:  config.twocaptcha_key  || process.env.TWOCAPTCHA_KEY  || ''
                };
                let waitingResult = await solveAndSubmit(page, captchaKeys, log).catch(()=>false);
                if (!waitingResult) {
                     await clickConsultarExa(page, log).catch(()=>false);
                }

                log("Esperar entre 2 y 3 segundos.");
                await page.waitForTimeout(2500);

                log("Esperar que desaparezca el spinner, aparezca la tabla y existan filas cargadas.");
                await page.waitForSelector('.ui-widget-overlay, .ui-blockui, .loading, #status', { state: 'hidden', timeout: 15000 }).catch(() => {});
                await page.waitForLoadState('networkidle').catch(() => {});
                await page.waitForSelector('table tbody tr', { state: 'visible', timeout: 15000 }).catch(() => {});

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
                    const filas = await page.locator('table tbody tr').count().catch(()=>0);
                    
                    for (let f = 0; f < filas; f++) {
                        const tr = page.locator('table tbody tr').nth(f);
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
                                const tempXml = path.join(output_dir, \`temp_xml_\${Date.now()}.xml\`);
                                await download.saveAs(tempXml).catch(()=>{});
                                
                                if (fs.existsSync(tempXml)) {
                                    let uniqueName = \`Retencion_\${anio}_\${mesNombre}_item\${f+1}_\${Date.now()}.xml\`;
                                    try {
                                        const xmlContent = fs.readFileSync(tempXml, 'utf8');
                                        const mClave = xmlContent.match(/<claveAcceso>(\\d+)<\\/claveAcceso>/i);
                                        if (mClave && mClave[1]) {
                                            uniqueName = \`RET_\${mClave[1]}.xml\`;
                                        }
                                    } catch (eParse) {}
                                    
                                    const finalXmlPath = path.join(output_dir, uniqueName);
                                    try {
                                        fs.renameSync(tempXml, finalXmlPath);
                                    } catch (eRen) {
                                        fs.copyFileSync(tempXml, finalXmlPath);
                                    }
                                    
                                    log(\`Descargado XML: \${uniqueName}\`);
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
            }
        }` + 
        content.substring(endIdx);
    
    fs.writeFileSync(file, newContent, 'utf8');
    console.log('Replaced successfully.');
} else {
    console.log('Error: indices not found.');
}
