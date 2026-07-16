// assets/js/xml-app.js

const exaCatalog = {
    "1": "Suministros de Oficina",
    "2": "Inventario Mercadería",
    "3": "Honorarios Profesionales",
    "4": "Servicios Básicos",
    "5": "Mantenimiento Equipos"
};

window.atsData = {
    ventas: [],
    compras: [],
    cargado: false,
    periodo: '',
    meses: {}
};

document.addEventListener('DOMContentLoaded', () => {
    mapeoModal = new bootstrap.Modal(document.getElementById('modalMapeo'));
    
    const dropZone = document.getElementById('drop-xml');
    const fileInput = document.getElementById('file-xml');
    
    if (dropZone) {
        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.style.background = 'rgba(255,255,255,0.1)'; });
        dropZone.addEventListener('dragleave', (e) => { e.preventDefault(); dropZone.style.background = 'rgba(255,255,255,0.05)'; });
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.background = 'rgba(255,255,255,0.05)';
            if (e.dataTransfer.files.length) handleMultipleFiles(e.dataTransfer.files);
        });
    }
    
    if (fileInput) {
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length) handleMultipleFiles(e.target.files);
            // Reset input so it fires again if the same file is selected
            e.target.value = '';
        });
    }

    const dropAtsDocs = document.getElementById('drop-ats-docs');
    if (dropAtsDocs) {
        dropAtsDocs.addEventListener('dragover', (e) => { e.preventDefault(); dropAtsDocs.style.background = '#f8fafc'; });
        dropAtsDocs.addEventListener('dragleave', (e) => { e.preventDefault(); dropAtsDocs.style.background = '#ffffff'; });
        dropAtsDocs.addEventListener('drop', (e) => {
            e.preventDefault();
            dropAtsDocs.style.background = '#ffffff';
            if (e.dataTransfer.files.length) handleMultipleFiles(e.dataTransfer.files);
        });
    }
    
    const xmlTabBtn = document.getElementById('xml-tab');
    if (xmlTabBtn) {
        xmlTabBtn.addEventListener('shown.bs.tab', () => renderAtsUI());
    }

    // SubTabs para ATS
    document.querySelectorAll('#atsSubTabs button[data-bs-toggle="tab"]').forEach(btn => {
        btn.addEventListener('shown.bs.tab', () => renderAtsUI());
    });

    const selectMes = document.getElementById('select-mes');
    if (selectMes) {
        selectMes.addEventListener('change', () => renderAtsUI());
    }
});

async function handleMultipleFiles(files) {
    const zipFiles = Array.from(files).filter(f => f.name.toLowerCase().endsWith('.zip'));
    const xmlFiles = Array.from(files).filter(f => f.name.toLowerCase().endsWith('.xml'));

    if (zipFiles.length === 0 && xmlFiles.length === 0) {
        alert('Por favor sube archivos .zip o .xml de ATS');
        return;
    }

    if (!window.atsData) {
        window.atsData = { ventas: [], compras: [], anulados: [], retenciones: [], cargado: false, periodo: 'Múltiples', meses: {} };
    } else {
        window.atsData.periodo = 'Múltiples';
    }

    // Process all ZIPs sequentially to avoid UI overlapping
    for (const zip of zipFiles) {
        await handleZip(zip, true); // pass true to accumulate
    }

    // Si hay múltiples XMLs, o si se procesaron ZIPs (para renderizar UI)
    const progressContainer = document.getElementById('xml-progress-container');
    const progressBar = document.getElementById('xml-progress');
    const statusText = document.getElementById('xml-status');
    
    if(progressContainer) progressContainer.classList.remove('d-none');
    if(statusText) statusText.classList.remove('d-none');
    
    if(progressBar) {
        progressBar.style.width = '20%'; 
        progressBar.innerText = '20%';
    }
    if(statusText) statusText.innerText = "Procesando " + xmlFiles.length + " archivos...";

    // Pop-up visual igual a app.js
    let loadingMsg = document.createElement('div');
    loadingMsg.id = 'xml-loading-alert';
    loadingMsg.style.position = 'fixed';
    loadingMsg.style.top = '20px';
    loadingMsg.style.right = '20px';
    loadingMsg.style.background = '#2e6589'; // EXA Blue
    loadingMsg.style.color = '#fff';
    loadingMsg.style.padding = '15px 25px';
    loadingMsg.style.borderRadius = '8px';
    loadingMsg.style.zIndex = '9999';
    loadingMsg.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    loadingMsg.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i> Cargando ATS XML...';
    document.body.appendChild(loadingMsg);

    if (!window.atsData) {
        window.atsData = { ventas: [], compras: [], anulados: [], retenciones: [], cargado: false, periodo: 'Múltiples', meses: {} };
    } else {
        window.atsData.periodo = 'Múltiples';
    }

    for (let xmlFile of xmlFiles) {
        await handleAtsXml(xmlFile, true);
    }

    window.atsData.cargado = true;
    if(progressBar) progressBar.style.width = '100%'; 
    if(statusText) {
        statusText.innerText = "Listo";
        statusText.classList.remove('text-info');
        statusText.classList.add('text-success');
    }
    
    renderAtsUI();
    
    // Success Message
    loadingMsg.style.background = '#27ae60'; // Success Green
    loadingMsg.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i> CARGA EXITOSA';
    setTimeout(() => {
        loadingMsg.remove();
        if(progressContainer) progressContainer.classList.add('d-none');
        if(statusText) statusText.classList.add('d-none');
    }, 3000);
}

async function handleZip(file, isBatch = false) {
    if (file.name.toLowerCase().endsWith('.xml')) {
        return handleAtsXml(file);
    }
    if (!file.name.toLowerCase().endsWith('.zip')) {
        alert('Por favor sube un archivo .zip o un .xml de ATS');
        return;
    }
    
    const progressContainer = document.getElementById('xml-progress-container');
    const progressBar = document.getElementById('xml-progress');
    const statusText = document.getElementById('xml-status');
    
    if(progressContainer) progressContainer.classList.remove('d-none');
    if(statusText) statusText.classList.remove('d-none');
    
    if(progressBar) {
        progressBar.style.width = '10%'; 
        progressBar.innerText = '10%';
    }
    if(statusText) statusText.innerText = "Descomprimiendo " + file.name + "...";
    
    xmlDocuments = [];
    
    try {
        const zip = await JSZip.loadAsync(file);
        const xmlFiles = Object.values(zip.files).filter(f => !f.dir && f.name.toLowerCase().endsWith('.xml'));
        
        if(xmlFiles.length === 0) {
            console.log('No se encontraron archivos XML en el ZIP: ' + file.name);
            return;
        }
        
        progressBar.style.width = '30%'; statusText.innerText = "Leyendo XMLs...";
        
        // Extract ALL ATS files
        let atsFiles = xmlFiles.filter(f => {
            const nom = f.name.toLowerCase();
            return nom.includes('ats') || nom.includes('at-') || nom.includes('anexo');
        });
        if (atsFiles.length === 0) atsFiles = xmlFiles;
        
        if (atsFiles.length > 0) {
            if (!isBatch || !window.atsData) {
                window.atsData = { ventas: [], compras: [], anulados: [], retenciones: [], cargado: false, periodo: '', meses: {} };
            }
            
            for (let atsFile of atsFiles) {
                const xmlContent = await atsFile.async("string");
                const fakeFile = new File([xmlContent], atsFile.name, {type: "application/xml"});
                await handleAtsXml(fakeFile, true);
            }
            
            // Render UI once at the end
            window.atsData.cargado = true;
            progressBar.style.width = '100%'; 
            statusText.innerText = "Listo";
            statusText.classList.remove('text-info');
            statusText.classList.add('text-success');
            
            if (!isBatch) {
                renderAtsUI();
            }
            
            setTimeout(() => {
                progressContainer.classList.add('d-none');
                statusText.classList.add('d-none');
            }, 2000);
            
            return;
        } else {
            alert("No se encontró un archivo XML válido en el ZIP");
            progressContainer.classList.add('d-none');
        }
        
    } catch (err) {
        console.error(err);
        alert('Error al leer el archivo ZIP');
        progressContainer.classList.add('d-none');
        statusText.classList.add('d-none');
    }
}

async function handleAtsXml(file, isMultiple = false) {
    const progressContainer = document.getElementById('xml-progress-container');
    const progressBar = document.getElementById('xml-progress');
    const statusText = document.getElementById('xml-status');
    
    if (progressContainer) progressContainer.classList.remove('d-none');
    if (statusText) statusText.classList.remove('d-none');
    
    if (progressBar) {
        progressBar.style.width = '20%'; 
        progressBar.innerText = '20%';
    }
    if (statusText) statusText.innerText = "Leyendo ATS...";
    
    if (!isMultiple) {
        if (!window.atsData) {
            window.atsData = {
                ventas: [],
                compras: [],
                cargado: false,
                periodo: '',
                meses: {}
            };
        }
        if (!window.atsData.meses) {
            window.atsData.meses = {};
        }
        
        // Pop-up visual igual a app.js (para 1 solo archivo)
        window.xmlLoadingMsg = document.createElement('div');
        window.xmlLoadingMsg.style.position = 'fixed';
        window.xmlLoadingMsg.style.top = '20px';
        window.xmlLoadingMsg.style.right = '20px';
        window.xmlLoadingMsg.style.background = '#2e6589'; // EXA Blue
        window.xmlLoadingMsg.style.color = '#fff';
        window.xmlLoadingMsg.style.padding = '15px 25px';
        window.xmlLoadingMsg.style.borderRadius = '8px';
        window.xmlLoadingMsg.style.zIndex = '9999';
        window.xmlLoadingMsg.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        window.xmlLoadingMsg.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i> Cargando ATS XML...';
        document.body.appendChild(window.xmlLoadingMsg);
    }
    
    try {
        const text = typeof file === 'string' ? file : await file.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(text, "application/xml");
        
        const anio = getNodeText(doc, 'Anio') || getNodeText(doc, 'anio') || '';
        const mes = getNodeText(doc, 'Mes') || getNodeText(doc, 'mes') || '';
        if (anio && mes) {
            const meses_nombres = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            const nMes = meses_nombres[parseInt(mes) - 1] || mes;
            window.atsData.periodo = !isMultiple ? `${nMes} ${anio}` : `Múltiples (${anio})`;
            const infP = document.getElementById('informe-periodo');
            if (infP && !isMultiple) infP.innerText = `${nMes} ${anio}`;
            const selAnio = document.getElementById('select-anio');
            if (selAnio && !isMultiple) selAnio.value = anio;
        } else {
            if (!isMultiple) window.atsData.periodo = 'Desconocido';
        }
        
        let mesNum = parseInt(mes);
        
        if (window.atsData.meses[mesNum]) {
            window.atsData.ventas = (window.atsData.ventas || []).filter(v => v._mes !== mesNum);
            window.atsData.compras = (window.atsData.compras || []).filter(c => c._mes !== mesNum);
            window.atsData.anulados = (window.atsData.anulados || []).filter(a => a._mes !== mesNum);
            window.atsData.retenciones = (window.atsData.retenciones || []).filter(r => r._mes !== mesNum);
        }
        
        window.atsData.meses[mesNum] = { ventas: [], compras: [], anulados: [], retenciones: [] };
        
        // Find purchases
        const comprasNodes = doc.querySelectorAll('compras > detalleCompras');
        const comprasArr = Array.from(comprasNodes);
        for (let i = 0; i < comprasArr.length; i++) {
            if (i % 500 === 0) await new Promise(r => setTimeout(r, 0)); // Yield to avoid freezing
            const compra = comprasArr[i];
            const ruc = getNodeText(compra, 'idProv');
            const estab = getNodeText(compra, 'establecimiento');
            const ptoEmi = getNodeText(compra, 'puntoEmision');
            const secuencial = getNodeText(compra, 'secuencial');
            const numDoc = estab + '-' + ptoEmi + '-' + secuencial;
            const fecha = getNodeText(compra, 'fechaEmision');
            const base0 = parseFloat(getNodeText(compra, 'baseImponible') || 0);
            const base15 = parseFloat(getNodeText(compra, 'baseImpGrav') || 0);
            const baseNoGraIva = parseFloat(getNodeText(compra, 'baseNoGraIva') || 0);
            const iva = parseFloat(getNodeText(compra, 'montoIva') || 0);
            const total = base0 + base15 + baseNoGraIva + iva;
            
            const tipo = getNodeText(compra, 'tipoComprobante') || getNodeText(compra, 'codSustento') || 'N/A';
            
            // IVA Retentions (hechas por mi)
            const rI10 = parseFloat(getNodeText(compra, 'valRetBien10') || 0);
            const rI20 = parseFloat(getNodeText(compra, 'valRetServ20') || 0);
            const rI30 = parseFloat(getNodeText(compra, 'valorRetBienes') || 0);
            const rI50 = parseFloat(getNodeText(compra, 'valRetServ50') || 0);
            const rI70 = parseFloat(getNodeText(compra, 'valorRetServicios') || 0);
            const rI100 = parseFloat(getNodeText(compra, 'valRetServ100') || 0);
            const rIA = parseFloat(getNodeText(compra, 'valRetAsuIva') || 0);
            const retIva = rI10 + rI20 + rI30 + rI50 + rI70 + rI100 + rIA;
            
            const obj = { ruc, fecha, numDoc, base0, base15, iva, retIva, total, _mes: mesNum, tipo };
            window.atsData.compras.push(obj);
            if (mesNum) window.atsData.meses[mesNum].compras.push(obj);
            
            // Parse Retenciones AIR
            let airNodes = compra.querySelectorAll('air > detalleAir');
            for(let j=0; j<airNodes.length; j++) {
                let air = airNodes[j];
                let codRetAir = getNodeText(air, 'codRetAir');
                let baseImpAir = parseFloat(getNodeText(air, 'baseImpAir') || 0);
                let porcentajeAir = parseFloat(getNodeText(air, 'porcentajeAir') || 0);
                let valRetAir = parseFloat(getNodeText(air, 'valRetAir') || 0);
                
                if (codRetAir) {
                    let objAir = { _mes: mesNum, codRetAir, baseImpAir, porcentajeAir, valRetAir };
                    window.atsData.retenciones = window.atsData.retenciones || [];
                    window.atsData.retenciones.push(objAir);
                    if (mesNum) {
                        window.atsData.meses[mesNum].retenciones = window.atsData.meses[mesNum].retenciones || [];
                        window.atsData.meses[mesNum].retenciones.push(objAir);
                    }
                }
            }
        }
        
        // Find sales
        const ventasNodes = doc.querySelectorAll('ventas > detalleVentas');
        const ventasArr = Array.from(ventasNodes);
        for (let i = 0; i < ventasArr.length; i++) {
            if (i % 500 === 0) await new Promise(r => setTimeout(r, 0)); // Yield to avoid freezing
            const venta = ventasArr[i];
            const ruc = getNodeText(venta, 'idCliente');
            const tipo = getNodeText(venta, 'tipoComprobante');
            const base0 = parseFloat(getNodeText(venta, 'baseImponible') || 0);
            const base15 = parseFloat(getNodeText(venta, 'baseImpGrav') || 0);
            const iva = parseFloat(getNodeText(venta, 'montoIva') || 0);
            const retIva = parseFloat(getNodeText(venta, 'valorRetIva') || 0);
            const retRenta = parseFloat(getNodeText(venta, 'valorRetRenta') || 0);
            const total = base0 + base15 + iva; // Total factura simplificado
            
            const obj = { ruc, tipo, base0, base15, iva, retIva, retRenta, total, _mes: mesNum };
            window.atsData.ventas.push(obj);
            if (mesNum) window.atsData.meses[mesNum].ventas.push(obj);
        }
        
        // Find anulados
        const anuladosNodes = doc.querySelectorAll('anulados > detalleAnulados');
        const anuladosArr = Array.from(anuladosNodes);
        for (let i = 0; i < anuladosArr.length; i++) {
            if (i % 500 === 0) await new Promise(r => setTimeout(r, 0));
            const anulado = anuladosArr[i];
            const tipo = getNodeText(anulado, 'tipoComprobante');
            const estab = getNodeText(anulado, 'establecimiento');
            const ptoEmi = getNodeText(anulado, 'puntoEmision');
            const secIni = getNodeText(anulado, 'secuencialInicio');
            const secFin = getNodeText(anulado, 'secuencialFin');
            const auth = getNodeText(anulado, 'autorizacion');
            
            const obj = { tipo, estab, ptoEmi, secIni, secFin, auth, _mes: mesNum };
            window.atsData.anulados = window.atsData.anulados || [];
            window.atsData.anulados.push(obj);
            if (mesNum) {
                window.atsData.meses[mesNum].anulados = window.atsData.meses[mesNum].anulados || [];
                window.atsData.meses[mesNum].anulados.push(obj);
            }
        }
        
        if (!isMultiple) {
            window.atsData.cargado = true;
            
            if (progressBar) {
                progressBar.style.width = '100%';
                progressBar.innerText = '100%';
            } 
            if (statusText) {
                statusText.innerText = "¡Listo!";
                statusText.classList.remove('text-info');
                statusText.classList.add('text-success');
            }
            
            renderAtsUI();
            
            // Success Message
            if (window.xmlLoadingMsg) {
                window.xmlLoadingMsg.style.background = '#27ae60'; // Success Green
                window.xmlLoadingMsg.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i> CARGA EXITOSA';
            }
            
            setTimeout(() => {
                if (window.xmlLoadingMsg) window.xmlLoadingMsg.remove();
                if (progressContainer) progressContainer.classList.add('d-none');
                if (statusText) statusText.classList.add('d-none');
            }, 3000);
        }
        
    } catch (e) {
        console.error(e);
        alert('Error al leer el archivo XML ATS');
        if (window.xmlLoadingMsg) window.xmlLoadingMsg.remove();
        if (progressContainer) progressContainer.classList.add('d-none');
        if (statusText) statusText.classList.add('d-none');
    }
}

function renderAtsUI() {
    let f104_cargado = (window.currentData && window.currentData.meses_cargados > 0);
    
    const chip104 = document.getElementById('chip-104-estado');
    if (chip104) {
        if (f104_cargado) {
            chip104.className = "badge bg-success";
            chip104.innerHTML = `<i class="bi bi-check-circle"></i> F104: Cargado`;
        } else {
            chip104.className = "badge bg-warning text-dark";
            chip104.innerHTML = `<i class="bi bi-exclamation-triangle"></i> F104: No cargado`;
        }
    }

    const chipAts = document.getElementById('chip-ats-estado');
    if (chipAts) {
        if (window.atsData && window.atsData.cargado) {
            chipAts.className = "badge bg-success";
            chipAts.innerHTML = `<i class="bi bi-check-circle"></i> ATS: Cargado`;
        } else {
            chipAts.className = "badge bg-warning text-dark";
            chipAts.innerHTML = `<i class="bi bi-exclamation-triangle"></i> ATS: No cargado`;
        }
    }
    // Mostrar qué meses están cargados en la pestaña Documentos
    const atsLoadedContainer = document.getElementById('ats-loaded-months');
    if (atsLoadedContainer) {
        if (window.atsData && window.atsData.cargado && Object.keys(window.atsData.meses).length > 0) {
            const meses_nombres = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            const keys = Object.keys(window.atsData.meses).sort((a,b) => parseInt(a) - parseInt(b));
            const names = keys.map(m => meses_nombres[parseInt(m) - 1]);
            atsLoadedContainer.innerHTML = `<i class="bi bi-calendar-check text-success"></i> <span class="text-dark fw-bold">Meses detectados:</span> <span class="text-success fw-bold">${names.join(', ')}</span>`;
            atsLoadedContainer.classList.remove('d-none');
        } else {
            atsLoadedContainer.classList.add('d-none');
        }
    }

    if (!window.atsData || !window.atsData.cargado) return;
    
    // Dynamic Month Tabs UI
    const monthTabsEl = document.getElementById('atsMonthTabs');
    const monthDivider = document.getElementById('atsMonthDivider');
    let mesSel = window.atsData.selectedMonth || 0; // Default to 0 (Consolidado)

    if (monthTabsEl && Object.keys(window.atsData.meses).length > 0) {
        const meses_nombres = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        const keys = Object.keys(window.atsData.meses).sort((a,b) => parseInt(a) - parseInt(b));
        
        let tabsHTML = `
            <li class="nav-item" role="presentation">
                <button class="nav-link ${mesSel === 0 ? 'active fw-bold bg-primary text-white' : 'text-dark'}" onclick="seleccionarMesAts(0)" type="button" role="tab">Consolidado</button>
            </li>`;
            
        keys.forEach(m => {
            const num = parseInt(m);
            const name = meses_nombres[num - 1];
            tabsHTML += `
            <li class="nav-item" role="presentation">
                <button class="nav-link ${mesSel === num ? 'active fw-bold bg-primary text-white' : 'text-dark'}" onclick="seleccionarMesAts(${num})" type="button" role="tab">${name}</button>
            </li>`;
        });
        
        monthTabsEl.innerHTML = tabsHTML;
        monthTabsEl.classList.remove('d-none');
        if (monthDivider) monthDivider.classList.remove('d-none');
    }

    const fmtXml = new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' });
    
    let listaVentas = window.atsData.ventas || [];
    let listaCompras = window.atsData.compras || [];
    let listaAnulados = window.atsData.anulados || [];
    let listaRetenciones = window.atsData.retenciones || [];
    
    const meses_nombres_ats = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    
    // --- Resumen Consolidado ---
    const tbodyR = document.getElementById('tbody-ats-resumen');
    const trHeaders = document.getElementById('tr-headers-ats');
    const trSubheaders = document.getElementById('tr-subheaders-ats');
    
    // Cleanup any dynamic TH from previous renders
    if (trHeaders) trHeaders.querySelectorAll('.dynamic-th').forEach(el => el.remove());
    if (trSubheaders) trSubheaders.querySelectorAll('.dynamic-th').forEach(el => el.remove());
    
    if (tbodyR) tbodyR.innerHTML = '';
    
    // Extraer códigos únicos de retención
    let uniqueRetCodes = new Set();
    listaRetenciones.forEach(r => {
        if (r.codRetAir) uniqueRetCodes.add(r.codRetAir);
    });
    let sortedCodes = Array.from(uniqueRetCodes).sort((a,b) => a.localeCompare(b));
    
    // Inyectar cabeceras dinámicas F103
    if (sortedCodes.length > 0 && trHeaders && trSubheaders) {
        let thMain = document.createElement('th');
        thMain.className = 'text-center dynamic-th';
        thMain.style.backgroundColor = '#1f2937';
        thMain.style.setProperty('color', '#ffffff', 'important');
        thMain.colSpan = sortedCodes.length + 1;
        thMain.innerText = 'XML RETENCIONES';
        trHeaders.appendChild(thMain);
        
        sortedCodes.forEach(cod => {
            let thCod = document.createElement('th');
            thCod.className = 'th-sub dynamic-th text-center';
            thCod.style.backgroundColor = '#374151';
            thCod.style.setProperty('color', '#ffffff', 'important');
            thCod.innerText = cod;
            trSubheaders.appendChild(thCod);
        });
        
        let thTotal = document.createElement('th');
        thTotal.className = 'th-sub fw-bold dynamic-th text-center';
        thTotal.style.backgroundColor = '#111827';
        thTotal.style.setProperty('color', '#ffffff', 'important');
        thTotal.innerText = 'TOTAL';
        trSubheaders.appendChild(thTotal);
    }
    
    // Group everything by month
    let mesesResumen = {};
    for(let m=1; m<=12; m++) {
        mesesResumen[m] = {
            v_bruto15: 0, v_bruto0: 0, v_nc15: 0, v_nc0: 0, v_iva_gen: 0,
            v_ret_iva: 0, v_ret_renta: 0,
            c_tarifa15: 0, c_tarifa0: 0, c_nc15: 0, c_nc0: 0, c_iva: 0,
            c_ret_iva: 0,
            ret: {},
            hasData: false
        };
        sortedCodes.forEach(c => mesesResumen[m].ret[c] = 0);
    }
    
    listaVentas.forEach(v => {
        let m = v._mes;
        mesesResumen[m].hasData = true;
        if (v.tipo === '04') { // Nota Credito
            mesesResumen[m].v_nc15 += v.base15;
            mesesResumen[m].v_nc0 += v.base0;
            mesesResumen[m].v_iva_gen -= v.iva;
        } else { // Facturas y otros
            mesesResumen[m].v_bruto15 += v.base15;
            mesesResumen[m].v_bruto0 += v.base0;
            mesesResumen[m].v_iva_gen += v.iva;
        }
        mesesResumen[m].v_ret_iva += (v.retIva || 0);
        mesesResumen[m].v_ret_renta += (v.retRenta || 0);
    });
    
    listaCompras.forEach(c => {
        let m = c._mes;
        mesesResumen[m].hasData = true;
        if (c.tipo === '04') { // Nota Credito
            mesesResumen[m].c_nc15 += c.base15;
            mesesResumen[m].c_nc0 += c.base0;
            mesesResumen[m].c_iva -= c.iva;
        } else { // Facturas y otros
            mesesResumen[m].c_tarifa15 += c.base15;
            mesesResumen[m].c_tarifa0 += c.base0;
            mesesResumen[m].c_iva += c.iva;
        }
        mesesResumen[m].c_ret_iva += (c.retIva || 0);
    });
    
    listaRetenciones.forEach(r => {
        let m = r._mes;
        mesesResumen[m].hasData = true;
        if (mesesResumen[m].ret[r.codRetAir] !== undefined) {
            mesesResumen[m].ret[r.codRetAir] += r.valRetAir;
        }
    });
    
    let t_v_b15=0, t_v_b0=0, t_v_nc15=0, t_v_nc0=0, t_v_iva=0, t_v_netas=0;
    let t_c_t15=0, t_c_t0=0, t_c_nc15=0, t_c_nc0=0, t_c_iva=0, t_c_neto=0;
    let t_ret_iva_ventas=0, t_ret_renta_ventas=0;
    let t_ret_iva_compras=0;
    let t_vc=0;
    
    let t_ret_totales = {};
    sortedCodes.forEach(c => t_ret_totales[c] = 0);
    let g_ret_total = 0;
    
    let hasAnyData = false;
    
    for(let m=1; m<=12; m++) {
        if (!mesesResumen[m].hasData) continue;
        hasAnyData = true;
        let d = mesesResumen[m];
        
        let v_netas = d.v_bruto15 + d.v_bruto0 - d.v_nc15 - d.v_nc0;
        let c_neto = d.c_tarifa15 + d.c_tarifa0 - d.c_nc15 - d.c_nc0;
        let v_c = v_netas - c_neto; // Neto Ventas - Neto Compras
        
        t_v_b15 += d.v_bruto15; t_v_b0 += d.v_bruto0; t_v_nc15 += d.v_nc15; t_v_nc0 += d.v_nc0; t_v_iva += d.v_iva_gen; t_v_netas += v_netas;
        t_c_t15 += d.c_tarifa15; t_c_t0 += d.c_tarifa0; t_c_nc15 += d.c_nc15; t_c_nc0 += d.c_nc0; t_c_iva += d.c_iva; t_c_neto += c_neto;
        t_ret_iva_ventas += d.v_ret_iva;
        t_ret_renta_ventas += d.v_ret_renta;
        t_ret_iva_compras += d.c_ret_iva;
        t_vc += v_c;
        
        if (tbodyR) {
            let rowHtml = `<tr>
                <td class="text-center fw-bold sticky-header-mes" style="background-color: #f8f9fa;">${meses_nombres_ats[m - 1]}</td>
                
                <td class="text-end">${fmtXml.format(d.v_bruto15)}</td>
                <td class="text-end">${fmtXml.format(d.v_bruto0)}</td>
                <td class="text-end">${fmtXml.format(d.v_nc15)}</td>
                <td class="text-end">${fmtXml.format(d.v_nc0)}</td>
                <td class="text-end">${fmtXml.format(d.v_iva_gen)}</td>
                <td class="text-end fw-bold">${fmtXml.format(v_netas)}</td>
                
                <td class="text-end">${fmtXml.format(d.c_tarifa15)}</td>
                <td class="text-end">${fmtXml.format(d.c_tarifa0)}</td>
                <td class="text-end">${fmtXml.format(d.c_nc15)}</td>
                <td class="text-end">${fmtXml.format(d.c_nc0)}</td>
                <td class="text-end">${fmtXml.format(d.c_iva)}</td>
                <td class="text-end fw-bold">${fmtXml.format(c_neto)}</td>
                
                <td class="text-end text-info">${fmtXml.format(d.v_ret_iva)}</td>
                <td class="text-end text-info">${fmtXml.format(d.v_ret_renta)}</td>
                
                <td class="text-end" style="color: #b45309;">${fmtXml.format(d.c_ret_iva)}</td>
                
                <td class="text-end fw-bold" style="background-color: #f8f9fa;">${fmtXml.format(v_c)}</td>`;
                
            let mesF103Total = 0;
            sortedCodes.forEach(c => {
                let val = d.ret[c] || 0;
                rowHtml += `<td class="text-end">${fmtXml.format(val)}</td>`;
                mesF103Total += val;
                t_ret_totales[c] += val;
            });
            if (sortedCodes.length > 0) {
                rowHtml += `<td class="text-end fw-bold">${fmtXml.format(mesF103Total)}</td>`;
                g_ret_total += mesF103Total;
            }
                
            rowHtml += `</tr>`;
            tbodyR.innerHTML += rowHtml;
        }
    }
    
    if (tbodyR) {
        if (!hasAnyData) {
            let totalCols = 17 + sortedCodes.length + (sortedCodes.length > 0 ? 1 : 0);
            tbodyR.innerHTML = `<tr><td colspan="${totalCols}" class="text-center text-muted py-4"><i class="bi bi-info-circle"></i> No hay datos en el ATS para mostrar</td></tr>`;
            document.getElementById('tfoot-ats-resumen').innerHTML = '';
        } else {
            let tfootHtml = `<tr class="fw-bold" style="background-color: #e9ecef;">
                <td class="text-end">TOTALES</td>
                <td class="text-end">${fmtXml.format(t_v_b15)}</td>
                <td class="text-end">${fmtXml.format(t_v_b0)}</td>
                <td class="text-end">${fmtXml.format(t_v_nc15)}</td>
                <td class="text-end">${fmtXml.format(t_v_nc0)}</td>
                <td class="text-end">${fmtXml.format(t_v_iva)}</td>
                <td class="text-end fw-bold">${fmtXml.format(t_v_netas)}</td>
                
                <td class="text-end">${fmtXml.format(t_c_t15)}</td>
                <td class="text-end">${fmtXml.format(t_c_t0)}</td>
                <td class="text-end">${fmtXml.format(t_c_nc15)}</td>
                <td class="text-end">${fmtXml.format(t_c_nc0)}</td>
                <td class="text-end">${fmtXml.format(t_c_iva)}</td>
                <td class="text-end fw-bold">${fmtXml.format(t_c_neto)}</td>
                
                <td class="text-end text-info">${fmtXml.format(t_ret_iva_ventas)}</td>
                <td class="text-end text-info">${fmtXml.format(t_ret_renta_ventas)}</td>
                
                <td class="text-end" style="color: #b45309;">${fmtXml.format(t_ret_iva_compras)}</td>
                
                <td class="text-end fw-bold">${fmtXml.format(t_vc)}</td>`;
                
            sortedCodes.forEach(c => {
                tfootHtml += `<td class="text-end">${fmtXml.format(t_ret_totales[c])}</td>`;
            });
            if (sortedCodes.length > 0) {
                tfootHtml += `<td class="text-end fw-bold">${fmtXml.format(g_ret_total)}</td>`;
            }
                
            tfootHtml += `</tr>`;
            document.getElementById('tfoot-ats-resumen').innerHTML = tfootHtml;
        }
    }
    
    // --- Anulados ---
    const tbodyA = document.getElementById('tbody-ats-anulados');
    if (tbodyA) {
        tbodyA.innerHTML = '';
        if (listaAnulados && listaAnulados.length > 0) {
            let arrA = [...listaAnulados].sort((a,b) => a._mes - b._mes);
            arrA.forEach(a => {
                tbodyA.innerHTML += `<tr>
                    <td class="text-center fw-bold bg-light" style="color: #1a2233;">${meses_nombres_ats[a._mes - 1] || 'N/A'}</td>
                    <td class="text-center">${a.tipo}</td>
                    <td class="text-center">${a.estab}</td>
                    <td class="text-center">${a.ptoEmi}</td>
                    <td class="text-center">${a.secIni}</td>
                    <td class="text-center">${a.secFin}</td>
                    <td class="text-center">${a.auth}</td>
                </tr>`;
            });
        } else {
            tbodyA.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-info-circle"></i> No hay comprobantes anulados en este ATS</td></tr>';
        }
    }
}

window.seleccionarMesAts = function(mesNum) {
    if (window.atsData) {
        window.atsData.selectedMonth = mesNum;
        renderAtsUI();
    }
};

function getNodeText(parent, nodeName) {
    if (!parent) return '';
    let nodes = parent.getElementsByTagName(nodeName);
    if (nodes.length > 0) return nodes[0].textContent.trim();
    
    nodes = parent.getElementsByTagName(nodeName.toLowerCase());
    if (nodes.length > 0) return nodes[0].textContent.trim();
    
    nodes = parent.getElementsByTagName(nodeName.toUpperCase());
    if (nodes.length > 0) return nodes[0].textContent.trim();
    
    return '';
}
