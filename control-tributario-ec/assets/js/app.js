// assets/js/app.js

const fmt = new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' });

document.addEventListener('DOMContentLoaded', () => {

    // Selectores
    const selectAnio = document.getElementById('select-anio');
    const selectRegimen = document.getElementById('select-regimen');

    // Listeners para selects
    selectAnio.addEventListener('change', updateState);
    selectRegimen.addEventListener('change', updateState);

    // Inicializar subida drag & drop
    setupDropZone('drop-104', '104', 'chips-104');
    setupDropZone('drop-103', '103', 'chips-103');
    setupDropZone('drop-renta', 'renta', 'chips-renta');
    setupDropZone('drop-iess', 'iess', 'chips-iess');
    setupDropZone('drop-retenciones', 'retenciones_rec', 'chips-retenciones');
    setupDropZone('drop-ats-docs', 'ats', 'ats-loaded-months');

    // Botón Generar Informe
    document.getElementById('btn-generar').addEventListener('click', () => {
        try {
            updateState();
            // Dirigir al usuario al módulo (Tabla Maestra) sin imprimir nada
            document.getElementById('tabla-tab').click();
            showToast('Generado', 'Datos actualizados y redirigido a la Tabla Maestra', 'success');
        } catch (e) {
            console.error("Error al generar informe:", e);
            alert("Hubo un problema al generar el informe. Revisa la consola.");
        }
    });

    // Initial fetch
    updateState();
});

window.editarContribuyente = function () {
    const currentRuc = document.querySelector('.ruc').innerText;
    const currentNombre = document.querySelector('.nombre').innerText;

    const newRuc = prompt("Ingresa el RUC del contribuyente:", currentRuc);
    if (newRuc !== null && newRuc.trim() !== "") {
        const newNombre = prompt("Ingresa el Nombre/Razón Social del contribuyente:", currentNombre);
        if (newNombre !== null && newNombre.trim() !== "") {
            fetch(window.BASE_URL + 'ajax/recalcular.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ruc: newRuc.trim(), nombre: newNombre.trim() })
            })
                .then(() => updateState())
                .catch(e => console.error("Error al actualizar contribuyente:", e));
        }
    }
};

window.clearEstimados = function () {
    const ids = [
        'input-ventas-estimado', 'input-compras-estimado', 'input-sueldos-estimado', 'input-seguridad-social-estimado',
        'input-ret-recibidas-estimado', 'input-gastos-personales', 'input-gastos-adicionales', 'input-rendimientos',
        'input-sueldo-107', 'input-depreciacion', 'input-gastos-nd', 'input-credito-anterior', 'input-anticipo-pagado',
        'input-perdidas'
    ];
    ids.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
};

function updateState() {
    const anio = document.getElementById('select-anio').value;
    const regimen = document.getElementById('select-regimen').value;

    // Actualizar labels en UI
    document.getElementById('param-anio-text').innerText = anio;
    const regText = document.getElementById('select-regimen').options[document.getElementById('select-regimen').selectedIndex].text;
    document.getElementById('param-regimen-text').innerText = regText;

    // UI lógica régimen
    if (regimen.startsWith('rimpe')) {
        document.querySelector('#card-103 .drop-zone').classList.add('d-none');
        document.querySelector('.alert-rimpe').classList.remove('d-none');
        document.getElementById('card-103').classList.add('rimpe-dimmed');
    } else {
        document.querySelector('#card-103 .drop-zone').classList.remove('d-none');
        document.querySelector('.alert-rimpe').classList.add('d-none');
        document.getElementById('card-103').classList.remove('rimpe-dimmed');
    }

    // Leer valores manuales si existen
    const gpInput = document.getElementById('input-gastos-personales');
    const gpDeclInput = document.getElementById('input-gastos-personales-decl');
    const gaInput = document.getElementById('input-otros-gastos-ad');
    const gaDeclInput = document.getElementById('input-otros-gastos-ad-decl');
    const rendInput = document.getElementById('input-rendimientos');
    const rendDeclInput = document.getElementById('input-rendimientos-decl');
    const sueldo107Input = document.getElementById('input-sueldo-107');
    const sueldo107DeclInput = document.getElementById('input-sueldo-107-decl');
    const depInput = document.getElementById('input-depreciacion');
    const depDeclInput = document.getElementById('input-depreciacion-decl');
    const ndInput = document.getElementById('input-gastos-nd');
    const ndDeclInput = document.getElementById('input-gastos-nd-decl');
    const credInput = document.getElementById('input-credito-anterior');
    const credDeclInput = document.getElementById('input-credito-anterior-decl');
    const anticipoInput = document.getElementById('input-anticipo');
    const anticipoDeclInput = document.getElementById('input-anticipo-decl');
    const perdidasInput = document.getElementById('input-perdidas');
    const perdidasDeclInput = document.getElementById('input-perdidas-decl');
    const getVal = (el) => el && el.value !== '' ? (parseFloat(el.value) || 0) : null;
    const getValDecl = (el) => el && el.value !== '' ? (parseFloat(el.value) || 0) : 0; // Declarado values can default to 0

    const gastos_personales = getVal(gpInput);
    const gastos_personales_decl = getValDecl(gpDeclInput);
    const gastos_adicionales = getVal(gaInput);
    const gastos_adicionales_decl = getValDecl(gaDeclInput);
    const rendimientos = getVal(rendInput);
    const rendimientos_decl = getValDecl(rendDeclInput);
    const sueldo_107 = getVal(sueldo107Input);
    const sueldo_107_decl = getValDecl(sueldo107DeclInput);
    const depreciacion = getVal(depInput);
    const depreciacion_decl = getValDecl(depDeclInput);
    const gastos_nd = getVal(ndInput);
    const gastos_nd_decl = getValDecl(ndDeclInput);

    const credito_anterior = getVal(credInput);
    const credito_anterior_decl = getValDecl(credDeclInput);
    const anticipo_pagado = getVal(anticipoInput);
    const anticipo_pagado_decl = getValDecl(anticipoDeclInput);
    const perdida_amortizable = getVal(perdidasInput);
    const perdida_amortizable_decl = getValDecl(perdidasDeclInput);

    const v_est = document.getElementById('input-ventas-estimado');
    const c_est = document.getElementById('input-compras-estimado');
    const s_est = document.getElementById('input-sueldos-estimado');
    const ss_est = document.getElementById('input-seguridad-social-estimado');
    const ret_rec_est = document.getElementById('input-ret-recibidas-estimado');

    const payload = {
        anio, regimen,
        gastos_personales, gastos_personales_decl, gastos_adicionales, gastos_adicionales_decl,
        rendimientos, rendimientos_decl, sueldo_107, sueldo_107_decl,
        depreciacion, depreciacion_decl, gastos_nd, gastos_nd_decl,
        credito_anterior, credito_anterior_decl,
        anticipo_pagado, anticipo_pagado_decl, perdida_amortizable, perdida_amortizable_decl
    };

    if (v_est) payload.ventas_estimado = getVal(v_est);
    if (c_est) payload.compras_estimado = getVal(c_est);
    if (s_est) payload.sueldos_estimado = getVal(s_est);
    if (ss_est) payload.seguridad_social_estimado = getVal(ss_est);
    if (ret_rec_est) payload.ret_recibidas_estimado = getVal(ret_rec_est);

    // Fetch al backend
    fetch(window.BASE_URL + 'ajax/recalcular.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
        .then(res => res.json())
        .then(data => {
            window.currentData = data;
            renderMaestra(data);
            renderIR(data, regimen);
            renderDashboard(data);
            renderIESS(data);
            renderF101(data);
            renderAnalisisRetenciones(data);

            if (data.ruc) {
                document.querySelector('.ruc').innerText = data.ruc;
                if (data.ruc.includes('0703703413')) {
                    const configNav = document.getElementById('nav-config');
                    if (configNav) configNav.style.display = 'block';
                }
            }
            if (data.nombre) document.querySelector('.nombre').innerText = data.nombre;
        });
}

function setupDropZone(id, tipo, chipsId) {
    const dropZone = document.getElementById(id);
    if (!dropZone) return;
    const fileInput = dropZone.querySelector('input');

    dropZone.addEventListener('click', () => {
        if (['104', '103', 'renta', 'retenciones_rec', 'ats', 'iess'].includes(tipo)) {
            abrirModalCargaSRI(tipo);
        } else {
            fileInput.click();
        }
    });

    dropZone.addEventListener('dragover', e => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', e => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            if (tipo === 'ats') return; // Handled by xml-app.js
            handleFiles(e.dataTransfer.files, tipo, chipsId);
        }
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) {
            if (tipo === 'ats') {
                if (typeof handleMultipleFiles === 'function') handleMultipleFiles(fileInput.files);
                fileInput.value = '';
                return;
            }
            handleFiles(fileInput.files, tipo, chipsId);
        }
    });
}

function handleFiles(files, tipo, chipsId) {
    // Show a loading overlay or change cursor
    document.body.style.cursor = 'wait';

    // Create a simple loading alert if not using SweetAlert
    let loadingMsg = document.createElement('div');
    loadingMsg.id = 'loading-alert';
    loadingMsg.style.position = 'fixed';
    loadingMsg.style.top = '20px';
    loadingMsg.style.right = '20px';
    loadingMsg.style.background = '#2e6589'; // EXA Blue
    loadingMsg.style.color = '#fff';
    loadingMsg.style.padding = '15px 25px';
    loadingMsg.style.borderRadius = '8px';
    loadingMsg.style.zIndex = '9999';
    loadingMsg.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    loadingMsg.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i> Cargando documento...';
    document.body.appendChild(loadingMsg);

    let uploads = Array.from(files).map(file => {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('tipo', tipo);

        const endpoint = (tipo === 'retenciones_rec') ? window.BASE_URL + 'ajax/upload_retenciones.php' : window.BASE_URL + 'ajax/upload.php';

        return fetch(endpoint, {
            method: 'POST',
            body: formData
        })
            .then(res => res.text()) // Use text to catch PHP errors
            .then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error("Error from server:", text);
                    throw new Error("El servidor devolvió un error (Pantalla Blanca o HTML). Revisa la consola.");
                }
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                } else {
                    return data;
                }
            });
    });

    Promise.all(uploads)
        .then(results => {
            // Actualizar RUC y nombre en la navbar si el servidor los detectó
            results.forEach(data => {
                if (data.ruc_detectado) {
                    const rucEl = document.querySelector('.ruc');
                    if (rucEl) rucEl.innerText = data.ruc_detectado;
                }
                if (data.nombre_detectado) {
                    const nombreEl = document.querySelector('.nombre');
                    if (nombreEl) nombreEl.innerText = data.nombre_detectado;
                }
            });
            window.clearEstimados();
            updateState();
            if (tipo === 'renta' || tipo === '101' || tipo === '102') {
                const f101Tab = document.getElementById('f101-tab');
                if (f101Tab) f101Tab.click();
            }
            // Success Message
            loadingMsg.style.background = '#27ae60'; // Success Green
            loadingMsg.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i> CARGA EXITOSA';
            setTimeout(() => {
                loadingMsg.remove();
            }, 3000);
        })
        .catch(err => {
            // Error Message
            loadingMsg.style.background = '#a02525'; // EXA Red
            loadingMsg.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i> ' + err.message;
            setTimeout(() => {
                loadingMsg.remove();
            }, 5000);
            alert("Hubo un problema al subir el archivo: " + err.message);
        })
        .finally(() => {
            document.body.style.cursor = 'default';
        });
}

function renderMaestra(data) {
    console.log(data.ret_rec_cols);
    function renderChip(text) {
        let t = text;
        let icon = '';
        if (text.includes('[SUST]')) {
            t = text.replace(' [SUST]', '');
            icon = `<span class="d-inline-flex align-items-center justify-content-center text-white rounded-circle me-1 fw-bold" style="width: 14px; height: 14px; font-size: 9px; background-color: #eab308; vertical-align: middle;">S</span>`;
        } else if (text.includes('[ORIG]')) {
            t = text.replace(' [ORIG]', '');
            icon = `<span class="d-inline-flex align-items-center justify-content-center text-white rounded-circle me-1 fw-bold" style="width: 14px; height: 14px; font-size: 9px; background-color: #0ea5e9; vertical-align: middle;">O</span>`;
        } else {
            icon = `<i class="bi bi-check-circle-fill text-success"></i> `;
        }
        return `<span class="chip" data-mes="${t.trim().toUpperCase()}">${icon}${t.trim()}</span>`;
    }

    // Render Chips
    if (document.getElementById('chips-104')) {
        document.getElementById('chips-104').innerHTML = data.chips_104.map(renderChip).join('');
    }
    if (document.getElementById('chips-103')) {
        document.getElementById('chips-103').innerHTML = data.chips_103.map(renderChip).join('');
    }
    if (document.getElementById('chips-iess')) {
        document.getElementById('chips-iess').innerHTML = data.chips_iess.map(renderChip).join('');
    }
    if (document.getElementById('chips-renta')) {
        document.getElementById('chips-renta').innerHTML = data.chips_renta.map(renderChip).join('');
    }
    if (document.getElementById('chips-retenciones') && data.chips_retenciones) {
        document.getElementById('chips-retenciones').innerHTML = data.chips_retenciones.map(renderChip).join('');
    }

    // KPIs
    document.getElementById('kpi-ventas').innerText = fmt.format(data.kpis.ventas);
    document.getElementById('kpi-pagado').innerText = fmt.format(data.kpis.pagado);
    document.getElementById('kpi-iva-causado').innerText = fmt.format(data.kpis.iva_causado);
    document.getElementById('kpi-nomina').innerText = fmt.format(data.kpis.nomina);
    document.getElementById('kpi-iva-pend').innerText = fmt.format(data.kpis.iva_pend);

    // Tabla subheaders F103
    const trSub = document.getElementById('tr-subheaders');
    // Remove old dynamic F103 headers if any
    document.querySelectorAll('.f103-dyn').forEach(e => e.remove());

    let f103ThGroup = document.getElementById('th-f103-group');
    if (data.f103_cols.length === 0) {
        f103ThGroup.colSpan = 1;
        f103ThGroup.innerText = "Form. 103 (Sin datos)";
    } else {
        f103ThGroup.colSpan = data.f103_cols.length + 1;
        f103ThGroup.innerText = "Form. 103 Retenciones IR";

        const thTotal = document.getElementById('th-f103-total');
        data.f103_cols.forEach(c => {
            const th = document.createElement('th');
            th.className = 'th-morado th-sub f103-dyn';
            th.innerText = c;
            trSub.insertBefore(th, thTotal);
        });
    }

    // Retenciones Recibidas (Dinamicas unificadas)
    document.querySelectorAll('.col-ret-dinamica').forEach(e => e.remove());
    document.querySelectorAll('.th-padre-retenciones').forEach(e => e.remove());

    let thResult = document.querySelector('.th-dinamico-retenciones'); // RESULTADO (sub-header anchor)
    let currentTh = thResult;

    const retColsRenta = data.ret_cols_renta || [];
    const retColsIva = data.ret_cols_iva || [];
    const hasRenta = retColsRenta.length > 0;
    const hasIva = retColsIva.length > 0;

    if (hasRenta || hasIva) {
        let colSpanTotal = 0;
        if (hasRenta) colSpanTotal += retColsRenta.length + 1; // cols + TOTAL RENTA
        if (hasIva) colSpanTotal += retColsIva.length + 1; // cols + TOTAL IVA

        // 1. HEADER PADRE
        let thPadre = document.createElement('th');
        thPadre.className = "text-center th-padre-retenciones text-white th-morado";
        thPadre.colSpan = colSpanTotal;
        thPadre.innerHTML = "RETENCIONES RECIBIDAS";
        document.querySelector('th.th-dark').insertAdjacentElement('afterend', thPadre);

        // 2. SUBHEADERS
        if (hasRenta) {
            retColsRenta.forEach(col => {
                let th = document.createElement('th');
                th.className = "text-center col-ret-dinamica align-middle th-sub th-morado";
                th.innerHTML = col;
                currentTh.insertAdjacentElement('afterend', th);
                currentTh = th;
            });
            let thTotRenta = document.createElement('th');
            thTotRenta.className = "text-center col-ret-dinamica align-middle th-sub fw-bold th-morado";
            thTotRenta.innerHTML = "TOTAL RENTA";
            currentTh.insertAdjacentElement('afterend', thTotRenta);
            currentTh = thTotRenta;
        }

        if (hasIva) {
            retColsIva.forEach(col => {
                let th = document.createElement('th');
                th.className = "text-center col-ret-dinamica align-middle th-sub th-morado";
                th.innerHTML = col;
                currentTh.insertAdjacentElement('afterend', th);
                currentTh = th;
            });
            let thTotIva = document.createElement('th');
            thTotIva.className = "text-center col-ret-dinamica align-middle th-sub fw-bold th-morado";
            thTotIva.innerHTML = "TOTAL IVA";
            currentTh.insertAdjacentElement('afterend', thTotIva);
            currentTh = thTotIva;
        }
    }

    // Ocultar F103 si es RIMPE
    const regimen = document.getElementById('select-regimen').value;
    if (regimen.startsWith('rimpe')) {
        f103ThGroup.style.display = 'none';
        document.querySelectorAll('.th-f103').forEach(e => e.style.display = 'none');
    } else {
        f103ThGroup.style.display = '';
        document.querySelectorAll('.th-f103').forEach(e => e.style.display = '');
    }

    const tbody = document.getElementById('tbody-maestra');
    tbody.innerHTML = '';

    data.maestra.forEach((r, i) => {
        let f103Html = '';
        if (!regimen.startsWith('rimpe')) {
            data.f103_cols.forEach(c => {
                f103Html += `<td class="text-end td-morado">${fmt.format(r.f103[c] || 0)}</td>`;
            });
            f103Html += `<td class="text-end td-morado fw-bold">${fmt.format(r.tot_f103)}</td>`;
        }

        let pillClass = r.estado == 'Cumplida' ? 'pill-cumplida' : 'pill-falta';

        // Encadenamiento IVA warning (simplificado)
        let l_617 = fmt.format(r.l_617);
        let l_606 = fmt.format(r.l_606);
        let class606 = '';
        if (i > 0 && r.l_606 != data.maestra[i - 1].l_617 && data.maestra[i - 1].l_617 > 0) {
            class606 = 'cell-error text-white fw-bold';
            l_606 = `⚠️ ${l_606}`;
        }

        let html = `<tr>
            <td class="fw-bold sticky-col-mes">${r.mes_nombre}</td>
            <td class="text-end td-azul">${fmt.format(r.v_401)}</td>
            <td class="text-end td-azul">${fmt.format(r.v_403)}</td>
            <td class="text-end td-azul">${fmt.format(r.nc_15)}</td>
            <td class="text-end td-azul">${fmt.format(r.nc_0)}</td>
            <td class="text-end td-azul">${fmt.format(r.v_429)}</td>
            <td class="text-end td-azul fw-bold">${fmt.format(r.tot_v)}</td>
            
            <td class="text-end td-verde">${fmt.format(r.c_500)}</td>
            <td class="text-end td-verde">${fmt.format(r.c_507 + r.c_508)}</td>
            <td class="text-end td-verde">${fmt.format(r.nc_c_15)}</td>
            <td class="text-end td-verde">${fmt.format(r.nc_c_0 + r.nc_c_rise)}</td>
            <td class="text-end td-verde">${fmt.format(r.c_529)}</td>
            <td class="text-end td-verde fw-bold">${fmt.format(r.tot_c)}</td>
            
            <td class="text-end fw-bold ${r.v_c < 0 ? 'text-danger bg-danger-subtle' : 'text-dark'}" style="${r.v_c < 0 ? '' : 'background-color: #f8f9fa;'}">${fmt.format(r.v_c)}</td>
            <td class="d-none"></td>`;

        // Inyectar celdas de retenciones unificadas
        const retColsRenta = data.ret_cols_renta || [];
        const retColsIva = data.ret_cols_iva || [];
        const hasRenta = retColsRenta.length > 0;
        const hasIva = retColsIva.length > 0;

        if (hasRenta || hasIva) {
            if (hasRenta) {
                retColsRenta.forEach(col => {
                    html += `<td class="text-end col-ret-dinamica td-morado">${fmt.format(r.ret_dinamicas ? r.ret_dinamicas[col] : 0)}</td>`;
                });
                html += `<td class="text-end fw-bold col-ret-dinamica td-morado">${fmt.format(r.tot_renta || 0)}</td>`;
            }

            if (hasIva) {
                retColsIva.forEach(col => {
                    html += `<td class="text-end col-ret-dinamica td-morado">${fmt.format(r.ret_dinamicas ? r.ret_dinamicas[col] : 0)}</td>`;
                });
                html += `<td class="text-end fw-bold col-ret-dinamica td-morado">${fmt.format(r.tot_iva || 0)}</td>`;
            }
        }

        html += `<td class="text-end td-naranja col-601">${fmt.format(r.l_601)}</td>
            <td class="text-end td-naranja col-606 ${class606}">${l_606}</td>
            <td class="text-end td-naranja col-617">${l_617}</td>
            <td class="text-end td-naranja col-485">${fmt.format(r.l_485)}</td>
            <td class="text-end td-naranja col-902 d-none">${fmt.format(r.l_902)}</td>
            <td class="text-end td-naranja col-903 d-none ${r.l_903 > 0 ? 'text-danger fw-bold bg-danger-subtle' : ''}">${fmt.format(r.l_903)}</td>
            <td class="text-end td-naranja col-904 d-none ${r.l_904 > 0 ? 'text-danger fw-bold bg-danger-subtle' : ''}">${fmt.format(r.l_904)}</td>
            <td class="text-end td-naranja fw-bold col-999">${fmt.format(r.l_999)}</td>
            <td class="text-end td-naranja col-721">${fmt.format(r.l_721)}</td>
            <td class="text-end td-naranja col-723">${fmt.format(r.l_723)}</td>
            <td class="text-end td-naranja col-725">${fmt.format(r.l_725)}</td>
            <td class="text-end td-naranja col-727">${fmt.format(r.l_727)}</td>
            <td class="text-end td-naranja col-729">${fmt.format(r.l_729)}</td>
            <td class="text-end td-naranja col-731">${fmt.format(r.l_731)}</td>
            <td class="text-end td-naranja col-799">${fmt.format(r.l_799)}</td>
            <td class="text-end td-naranja fw-bold col-801">${fmt.format(r.l_801)}</td>
            
            ${f103Html}
            
            <td class="text-end td-rojo">${fmt.format(r.n_bruta)}</td>
            <td class="text-end td-rojo">${fmt.format(r.n_pat)}</td>
            <td class="text-end td-rojo">${fmt.format(r.n_ind)}</td>
            <td class="text-end td-rojo">${fmt.format(r.n_ccc)}</td>
            <td class="text-end td-rojo">${fmt.format(r.n_prov1314)}</td>
            <td class="text-end td-rojo">${fmt.format(r.n_vac)}</td>
            
            <td class="text-end td-gris fw-bold">${fmt.format(r.tot_pag)}</td>
            <td class="text-center"><span class="badge rounded-pill ${pillClass}">${r.estado}</span></td>
        </tr>`;
        tbody.innerHTML += html;
    });

    // Totales
    const t = data.totales;
    let tf103 = '';
    if (!regimen.startsWith('rimpe')) {
        data.f103_cols.forEach(c => {
            tf103 += `<td class="text-end td-morado">${fmt.format(t['f103_' + c])}</td>`;
        });
        tf103 += `<td class="text-end td-morado fw-bold text-info">${fmt.format(t.tot_f103)}</td>`;
    } else {
        tf103 = `<td class="d-none"></td>`;
    }

    let tfootHtml = `<tr>
        <td class="fw-bold sticky-col-mes">TOTAL ANUAL</td>
        <td class="text-end td-azul">${fmt.format(t.v_401)}</td>
        <td class="text-end td-azul">${fmt.format(t.v_403)}</td>
        <td class="text-end td-azul">${fmt.format(t.nc_15)}</td>
        <td class="text-end td-azul">${fmt.format(t.nc_0)}</td>
        <td class="text-end td-azul">${fmt.format(t.v_429)}</td>
        <td class="text-end td-azul fw-bold">${fmt.format(t.tot_v)}</td>
        
        <td class="text-end td-verde">${fmt.format(t.c_500)}</td>
        <td class="text-end td-verde">${fmt.format(t.c_507 + t.c_508)}</td>
        <td class="text-end td-verde">${fmt.format(t.nc_c_15)}</td>
        <td class="text-end td-verde">${fmt.format(t.nc_c_0 + t.nc_c_rise)}</td>
        <td class="text-end td-verde">${fmt.format(t.c_529)}</td>
        <td class="text-end td-verde fw-bold">${fmt.format(t.tot_c)}</td>
        
        <td class="text-end fw-bold ${t.v_c < 0 ? 'text-danger bg-danger-subtle' : 'text-dark'}" style="${t.v_c < 0 ? '' : 'background-color: #e9ecef;'}">${fmt.format(t.v_c)}</td>
        <td class="d-none"></td>`;

    if (hasRenta || hasIva) {
        if (hasRenta) {
            retColsRenta.forEach(col => {
                let val = data.ret_rec_tot ? (data.ret_rec_tot[col] || 0) : 0;
                tfootHtml += `<td class="text-end fw-bold col-ret-dinamica td-morado">${fmt.format(val)}</td>`;
            });
            tfootHtml += `<td class="text-end fw-bold col-ret-dinamica td-morado">${fmt.format(data.tot_renta_global || 0)}</td>`;
        }
        if (hasIva) {
            retColsIva.forEach(col => {
                let val = data.ret_rec_tot ? (data.ret_rec_tot[col] || 0) : 0;
                tfootHtml += `<td class="text-end fw-bold col-ret-dinamica td-morado">${fmt.format(val)}</td>`;
            });
            tfootHtml += `<td class="text-end fw-bold col-ret-dinamica td-morado">${fmt.format(data.tot_iva_global || 0)}</td>`;
        }
    }

    tfootHtml += `<td class="text-end td-naranja col-601">${fmt.format(t.l_601)}</td>
        <td class="text-end td-naranja col-606">${fmt.format(t.l_606)}</td>
        <td class="text-end td-naranja col-617">${fmt.format(t.l_617)}</td>
        <td class="text-end td-naranja col-485">${fmt.format(t.l_485)}</td>
        <td class="text-end td-naranja col-902 d-none">${fmt.format(t.l_902)}</td>
        <td class="text-end td-naranja col-903 d-none ${t.l_903 > 0 ? 'text-danger fw-bold' : ''}">${fmt.format(t.l_903)}</td>
        <td class="text-end td-naranja col-904 d-none ${t.l_904 > 0 ? 'text-danger fw-bold' : ''}">${fmt.format(t.l_904)}</td>
        <td class="text-end td-naranja text-info fw-bold col-999">${fmt.format(t.l_999)}</td>
        <td class="text-end td-naranja col-721">${fmt.format(t.l_721)}</td>
        <td class="text-end td-naranja col-723">${fmt.format(t.l_723)}</td>
        <td class="text-end td-naranja col-725">${fmt.format(t.l_725)}</td>
        <td class="text-end td-naranja col-727">${fmt.format(t.l_727)}</td>
        <td class="text-end td-naranja col-729">${fmt.format(t.l_729)}</td>
        <td class="text-end td-naranja col-731">${fmt.format(t.l_731)}</td>
        <td class="text-end td-naranja col-799">${fmt.format(t.l_799)}</td>
        <td class="text-end td-naranja fw-bold col-801">${fmt.format(t.l_801)}</td>
        
        ${tf103}
        
        <td class="text-end td-rojo">${fmt.format(t.n_bruta)}</td>
        <td class="text-end td-rojo">${fmt.format(t.n_pat)}</td>
        <td class="text-end td-rojo">${fmt.format(t.n_ind)}</td>
        <td class="text-end td-rojo">${fmt.format(t.n_ccc)}</td>
        <td class="text-end td-rojo">${fmt.format(t.n_prov1314)}</td>
        <td class="text-end td-rojo">${fmt.format(t.n_vac)}</td>
        
        <td class="text-end td-gris fw-bold text-success">${fmt.format(t.tot_pag)}</td>
        <td></td>
    </tr>`;

    document.getElementById('tfoot-maestra').innerHTML = tfootHtml;

    let liqColspan = 13;
    ['601', '606', '617', '485', '999', '721', '723', '725', '727', '729', '731', '799', '801'].forEach(c => {
        if (t['l_' + c] === 0) {
            document.querySelectorAll('.col-' + c).forEach(el => el.classList.add('d-none'));
            liqColspan--;
        } else {
            document.querySelectorAll('.col-' + c).forEach(el => el.classList.remove('d-none'));
        }
    });
    const thLiqIva = document.getElementById('th-liq-iva');
    if (thLiqIva) {
        if (liqColspan === 0) {
            thLiqIva.classList.add('d-none');
        } else {
            thLiqIva.classList.remove('d-none');
            thLiqIva.setAttribute('colspan', liqColspan);
        }
    }
}

function renderIR(data, regimen) {
    document.getElementById('ir-meses-count').innerText = data.meses_cargados;
    if (data.meses_cargados < 12 && data.meses_cargados > 0) {
        document.getElementById('ir-alert-partial').classList.remove('d-none');
    } else {
        document.getElementById('ir-alert-partial').classList.add('d-none');
    }

    document.getElementById('ir-kpi-ingresos').innerText = fmt.format(data.kpis.ventas);
    document.getElementById('ir-kpi-base').innerText = fmt.format(data.base_imponible);
    document.getElementById('ir-kpi-causado').innerText = fmt.format(data.ir_causado);
    document.getElementById('ir-kpi-nomina').innerText = fmt.format(data.ir.sueldos);

    const colLeft = document.getElementById('ir-inputs-col');
    const colRight = document.getElementById('ir-conciliacion-col');

    let inputsHtml = '';
    let rightHtml = '';

    if (regimen == 'pn' || regimen == 'soc') {
        colLeft.className = "col-lg-8";
        if (colRight.parentElement) colRight.parentElement.className = "col-lg-4";
        colRight.style.display = 'block';

        let totalIngresos = (data.ir?.ventas_estimado ?? data.kpis.ventas) + (data.rendimientos || 0) + (data.sueldo_107 || 0);

        // TARIFA 15% (c_500) + TARIFA 0% (c_507+c_508) - N/C 15% - N/C 0%
        let comprasNetasBase = (data.ir.compras_500 || 0) + (data.ir.compras_507 || 0) + (data.ir.compras_508 || 0) - (data.ir.nc_c_15 || 0) - (data.ir.nc_c_0 || 0);
        let comprasNetas = data.ir?.compras_estimado ?? comprasNetasBase;

        let sueldos = data.ir?.sueldos_estimado ?? data.ir.sueldos;
        let seguridadSocial = data.ir?.seguridad_social_estimado ?? (data.ir.patronal + (data.ir.ccc || 0));
        let totalSueldos = sueldos + seguridadSocial; // Just for total calculations
        let totalGastos = comprasNetas + totalSueldos + (data.depreciacion || 0) + (data.gastos_adicionales || 0);
        let totalGastosDecl = comprasNetas + totalSueldos + (data.depreciacion_decl || 0) + (data.gastos_adicionales_decl || 0);

        let utilAntes = data.utilidad_antes_part !== undefined ? data.utilidad_antes_part : (totalIngresos - totalGastos);
        let utilAntesDecl = data.utilidad_antes_part_decl !== undefined ? data.utilidad_antes_part_decl : utilAntes;
        let perdidasAmortizables = data.perdidas_amortizables || 0;
        let utilidadGravable = data.base_imponible || 0; // Calculado backend

        // Utilidad Neta = Utilidad Gravable - Impuesto a la Renta Causado
        let utilNeta = utilidadGravable - data.ir_causado;

        let utilidadGravableDecl = data.base_imponible_decl || 0;
        let utilNetaDecl = utilidadGravableDecl - (data.ir_causado_decl || 0);

        let anticipoPagado = data.anticipo_pagado || 0;
        let saldoPagar = data.ir_causado - data.ir.ret_recibidas - (data.credito_anterior || 0) - anticipoPagado;

        let saldoPagarDecl = data.ir_causado_decl - data.ir.ret_recibidas - (data.credito_anterior_decl || 0) - (data.anticipo_pagado_decl || 0);

        // Clases para inputs editables (sin color rosa según pedido)
        const inputClass = 'form-control form-control-sm text-end calc-trigger';

        inputsHtml = `
              <input type="hidden" id="ir-table-regimen" value="${regimen}">
              <div class="table-responsive p-3 bg-white rounded shadow-sm border border-secondary border-opacity-25" style="width: 100%;">
                  <h4 class="text-center text-dark mb-1 fw-bold">${data.nombre || 'CONTRIBUYENTE'}</h4>
                  <h6 class="text-center text-muted mb-3">BORRADOR DE IMPUESTO A LA RENTA ${data.anio || 2026} (${regimen == 'soc' ? 'SOCIEDAD' : 'PERSONA NATURAL'})</h6>
                  
                  <table class="table table-bordered table-hover table-sm align-middle mb-0" style="font-size: 0.9rem;">
                      <thead class="table-dark text-center align-middle">
                          <tr>
                              <th class="text-start">RUBRO</th>
                              <th width="180" style="background-color: #475569;">
                                  <i class="bi bi-file-earmark-text"></i> DECLARADO
                                  <div class="fw-normal" style="font-size: 0.75rem;">(Valores Originales)</div>
                              </th>
                              <th width="180" style="background-color: #2563eb;">
                                  <i class="bi bi-calculator"></i> ESTIMADO
                                  <div class="fw-normal" style="font-size: 0.75rem;">(Borrador a Proyectar)</div>
                              </th>
                          </tr>
                      </thead>
                      <tbody>
                          <tr class="table-primary fw-bold"><td colspan="3"><i class="bi bi-cash-stack"></i> INGRESOS</td></tr>
                          <tr>
                              <td class="fw-semibold text-secondary">VENTAS</td>
                              <td id="val-ventas-decl" class="text-end text-muted bg-light">${fmt.format(data.kpis.ventas)}</td>
                              <td class="bg-primary bg-opacity-10 p-1"><input type="number" step="any" id="input-ventas-estimado" class="${inputClass} border-primary" value="${data.ir?.ventas_estimado ?? data.kpis.ventas}"></td>
                          </tr>
                          <tr>
                              <td class="fw-semibold text-secondary">RENDIMIENTOS FINANCIEROS</td>
                              <td class="bg-light p-1"><input type="number" step="any" id="input-rendimientos-decl" class="${inputClass}" value="${data.rendimientos_decl || 0}"></td>
                              <td class="bg-primary bg-opacity-10 p-1"><input type="number" step="any" id="input-rendimientos" class="${inputClass} border-primary" value="${data.rendimientos || 0}"></td>
                          </tr>
                          <tr>
                              <td class="fw-semibold text-secondary">BASE SUELDO 107</td>
                              <td class="bg-light p-1"><input type="number" step="any" id="input-sueldo-107-decl" class="${inputClass}" value="${data.sueldo_107_decl || 0}"></td>
                              <td class="bg-primary bg-opacity-10 p-1"><input type="number" step="any" id="input-sueldo-107" class="${inputClass} border-primary" value="${data.sueldo_107 || 0}"></td>
                          </tr>
                          <tr class="fw-bold" style="background-color: #f1f5f9;">
                              <td class="text-end text-dark">TOTAL DE INGRESOS</td>
                              <td class="text-end text-muted">${fmt.format(data.kpis.ventas)}</td>
                              <td class="text-end text-primary fs-6">${fmt.format(totalIngresos)}</td>
                          </tr>
                          
                          <tr class="table-primary fw-bold"><td colspan="3"><i class="bi bi-cart-dash"></i> COSTOS Y GASTOS</td></tr>
                          <tr>
                              <td class="fw-semibold text-secondary">COMPRAS</td>
                              <td id="val-compras-decl" class="text-end text-muted bg-light">${fmt.format(comprasNetasBase)}</td>
                              <td class="bg-primary bg-opacity-10 p-1"><input type="number" step="any" id="input-compras-estimado" class="${inputClass} border-primary" value="${data.ir?.compras_estimado ?? comprasNetas}"></td>
                          </tr>
                          <tr>
                              <td class="fw-semibold text-secondary">SUELDOS</td>
                              <td id="val-sueldos-decl" class="text-end text-muted bg-light">${fmt.format(data.ir.sueldos)}</td>
                              <td class="bg-primary bg-opacity-10 p-1"><input type="number" step="any" id="input-sueldos-estimado" class="${inputClass} border-primary" value="${data.ir?.sueldos_estimado ?? sueldos}"></td>
                          </tr>
                          <tr>
                              <td class="fw-semibold text-secondary">SEGURIDAD SOCIAL</td>
                              <td id="val-seguridad-decl" class="text-end text-muted bg-light">${fmt.format(data.ir.patronal + (data.ir.ccc || 0))}</td>
                              <td class="bg-primary bg-opacity-10 p-1"><input type="number" step="any" id="input-seguridad-social-estimado" class="${inputClass} border-primary" value="${data.ir?.seguridad_social_estimado ?? seguridadSocial}"></td>
                          </tr>
                          <tr>
                              <td class="fw-semibold text-secondary">DEPRECIACION Y PROVISION</td>
                              <td class="bg-light p-1"><input type="number" step="any" id="input-depreciacion-decl" class="${inputClass}" value="${data.depreciacion_decl || 0}"></td>
                              <td class="bg-primary bg-opacity-10 p-1"><input type="number" step="any" id="input-depreciacion" class="${inputClass} border-primary" value="${data.depreciacion || 0}"></td>
                          </tr>
                          <tr>
                              <td class="fw-semibold text-secondary">OTROS COSTOS Y GASTOS</td>
                              <td class="bg-light p-1">
                                  <input type="number" step="any" id="input-otros-gastos-ad-decl" class="${inputClass}" value="${data.gastos_adicionales_decl || 0}">
                              </td>
                              <td class="bg-primary bg-opacity-10 p-1">
                                  <input type="number" step="any" id="input-otros-gastos-ad" class="${inputClass} border-primary" value="${data.gastos_adicionales || 0}">
                              </td>
                          </tr>
                          <tr class="fw-bold" style="background-color: #f1f5f9;">
                              <td class="text-end text-dark">TOTAL DE GASTOS</td>
                              <td class="text-end text-muted">${fmt.format(totalGastosDecl)}</td>
                              <td class="text-end text-primary fs-6">${fmt.format(totalGastos)}</td>
                          </tr>
                          
                          <tr class="table-dark fw-bold text-white"><td colspan="3"><i class="bi bi-bar-chart-steps"></i> CONCILIACION TRIBUTARIA</td></tr>
                          <tr class="fw-bold">
                              <td class="text-dark">UTILIDAD ANTES DE PARTICIPACION <br><small class="text-muted fw-normal">(Ingresos - Gastos)</small></td>
                              <td class="text-end text-dark bg-light p-1 align-middle">${fmt.format(data.utilidad_antes_part_decl || 0)}</td>
                              <td class="text-end text-primary bg-primary bg-opacity-10 p-1 align-middle">${fmt.format(data.utilidad_antes_part || 0)}</td>
                          </tr>
                          <tr>
                              <td class="fw-semibold text-secondary">(-) PARTICIPACION 15% TRABAJADORES</td>
                              <td class="text-end fw-bold text-danger bg-light">${fmt.format(data.participacion_15_decl || 0)}</td>
                              <td class="text-end fw-bold text-danger bg-primary bg-opacity-10">${fmt.format(data.participacion_15 || 0)}</td>
                          </tr>
                          <tr>
                              <td class="fw-semibold text-secondary">(+) GASTOS NO DEDUCIBLES</td>
                              <td class="bg-light p-1"><input type="number" step="any" id="input-gastos-nd-decl" class="${inputClass}" value="${data.gastos_nd_decl || 0}"></td>
                              <td class="bg-primary bg-opacity-10 p-1"><input type="number" step="any" id="input-gastos-nd" class="${inputClass} border-primary" value="${data.gastos_nd || 0}"></td>
                          </tr>
                          ${regimen === 'pn' ? `
                          <tr>
                              <td class="fw-semibold text-secondary">(-) GASTOS PERSONALES</td>
                              <td class="bg-light p-1"><input type="number" step="any" id="input-gastos-personales-decl" class="${inputClass}" value="${data.gastos_personales_decl || 0}"></td>
                              <td class="bg-primary bg-opacity-10 p-1"><input type="number" step="any" id="input-gastos-personales" class="${inputClass} border-primary" value="${data.gastos_personales || 0}"></td>
                          </tr>
                          ` : ''}
                          <tr>
                              <td class="fw-semibold text-secondary">(-) AMORTIZACION PERDIDAS TRIBUTARIAS <br><small class="text-info"><i class="bi bi-file-earmark-bar-graph"></i> Cas. 837 F101</small></td>
                              <td class="bg-light p-1 align-middle"><input type="number" step="any" id="input-perdidas-decl" class="${inputClass}" value="${data.perdidas_amortizables_decl || 0}"></td>
                              <td class="bg-primary bg-opacity-10 p-1"><input type="number" step="any" id="input-perdidas" class="${inputClass} border-primary" value="${data.perdidas_amortizables || 0}"></td>
                          </tr>
                          <tr class="fw-bold" style="background-color: #f8fafc;">
                              <td class="text-dark fs-6">UTILIDAD GRAVABLE (Base Imponible)</td>
                              <td class="text-end text-dark">${fmt.format(utilidadGravableDecl)}</td>
                              <td class="text-end text-primary fs-6">${fmt.format(utilidadGravable)}</td>
                          </tr>
                          <tr>
                              <td class="fw-semibold text-secondary">IMPUESTO A LA RENTA CAUSADO</td>
                              <td class="text-end text-dark bg-light">${fmt.format(data.ir_causado_decl || 0)}</td>
                              <td class="text-end fw-bold text-danger bg-primary bg-opacity-10">${fmt.format(data.ir_causado)}</td>
                          </tr>
                          <tr class="fw-bold" style="background-color: #f1f5f9;">
                              <td class="text-dark">UTILIDAD NETA <br><small class="text-muted fw-normal">(Utilidad Gravable - IR Causado)</small></td>
                              <td class="text-end text-dark">${fmt.format(utilNetaDecl)}</td>
                              <td class="text-end text-success fs-6">${fmt.format(utilNeta)}</td>
                          </tr>
                          
                          <tr class="table-dark fw-bold text-white"><td colspan="3"><i class="bi bi-wallet2"></i> LIQUIDACION DE IMPUESTO</td></tr>
                          <tr>
                              <td class="fw-bold text-dark">IMPUESTO CAUSADO</td>
                              <td class="text-end fw-bold text-dark bg-light">${fmt.format(data.ir_causado_decl || 0)}</td>
                              <td class="text-end fw-bold text-danger bg-primary bg-opacity-10 fs-6">${fmt.format(data.ir_causado)}</td>
                          </tr>
                          <tr>
                              <td class="fw-semibold text-secondary">RETENCIONES RECIBIDAS</td>
                              <td class="text-end text-muted bg-light">${fmt.format(data.ir.ret_recibidas_decl)}</td>
                              <td class="bg-primary bg-opacity-10 p-1"><input type="number" step="any" id="input-ret-recibidas-estimado" class="${inputClass} border-primary" value="${data.ir?.ret_recibidas ?? 0}"></td>
                          </tr>
                          <tr>
                              <td class="fw-semibold text-secondary">CREDITO TRIBUTARIO AÑO ANTERIOR</td>
                              <td class="bg-light p-1"><input type="number" step="any" id="input-credito-anterior-decl" class="${inputClass}" value="${data.credito_anterior_decl || 0}"></td>
                              <td class="bg-primary bg-opacity-10 p-1"><input type="number" step="any" id="input-credito-anterior" class="${inputClass} border-primary" value="${data.credito_anterior || 0}"></td>
                          </tr>
                          <tr>
                              <td class="fw-semibold text-secondary">ANTICIPO PAGADO (AÑO ANTERIOR)</td>
                              <td class="bg-light p-1"><input type="number" step="any" id="input-anticipo-decl" class="${inputClass}" value="${data.anticipo_pagado_decl || 0}"></td>
                              <td class="bg-primary bg-opacity-10 p-1"><input type="number" step="any" id="input-anticipo" class="${inputClass} border-primary" value="${data.anticipo_pagado || 0}"></td>
                          </tr>
                          <tr class="fw-bold" style="background-color: #eff6ff; border-top: 2px solid #2563eb;">
                              <td class="text-end text-primary fs-6">SALDO A PAGAR / A FAVOR</td>
                              <td class="text-end text-dark fs-5 bg-white border-end-0 shadow-sm">${fmt.format(saldoPagarDecl)}</td>
                              <td class="text-end text-primary fs-5 bg-white border-start-0 shadow-sm">${fmt.format(saldoPagar)}</td>
                          </tr>
                      </tbody>
                  </table>
                  <div class="text-end mt-3">
                      <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir Borrador</button>
                  </div>
              </div>
          `;

        let calcHtml = '';
        const buildCalcTable = (details, base, ir, title, bg, typeLabel) => {
            if (!details) return '';
            let rowsHtml = '';
            if (details.tipo == 'tabla') {
                rowsHtml = `
                      <tr class="bg-light fw-bold">
                          <td>BASE IMPONIBLE</td>
                          <td class="text-end">${fmt.format(base)}</td>
                          <td class="bg-secondary bg-opacity-25"></td>
                      </tr>
                      <tr>
                          <td>FRACCION BASICA</td>
                          <td class="text-end">${fmt.format(details.fb)}</td>
                          <td class="text-end">${fmt.format(details.imp_fb)}</td>
                      </tr>
                      <tr>
                          <td>FRACCION EXCEDENTE</td>
                          <td class="text-end">${fmt.format(details.fe)} <span class="text-muted small">(${details.porc * 100}%)</span></td>
                          <td class="text-end fw-bold">${fmt.format(details.imp_fe)}</td>
                      </tr>`;
            } else if (details.tipo == 'flat') {
                rowsHtml = `
                      <tr class="bg-light fw-bold">
                          <td>BASE IMPONIBLE</td>
                          <td class="text-end">${fmt.format(base)}</td>
                          <td class="bg-secondary bg-opacity-25"></td>
                      </tr>
                      <tr>
                          <td>TARIFA SOCIEDAD</td>
                          <td class="text-end">${details.porc * 100}%</td>
                          <td class="text-end fw-bold">${fmt.format(ir)}</td>
                      </tr>`;
            }

            return `
              <table class="table table-sm mb-0 bg-white" style="font-size: 0.85rem;">
                  <tbody>
                      ${rowsHtml}
                      <tr class="table-warning fw-bold border-top border-secondary">
                          <td colspan="2" class="text-end">IMPUESTO CAUSADO</td>
                          <td class="text-end">${fmt.format(ir)}</td>
                      </tr>
                  </tbody>
              </table>
              `;
        };

        let infoTexto = data.calc_details?.tipo == 'tabla' ? 'Según Tabla progresiva SRI' : 'Tarifa plana Sociedades';
        calcHtml += `<p class="small text-muted mb-2"><i class="bi bi-info-circle"></i> ${infoTexto}</p>`;

        let declTable = data.calc_details_decl ? buildCalcTable(data.calc_details_decl, data.base_imponible_decl, data.ir_causado_decl, '', '') : '';
        let estTable = data.calc_details ? buildCalcTable(data.calc_details, data.base_imponible, data.ir_causado, '', '') : '';

        // SRI Progressive Tables Data (Always render for reference)
        const sriTables = {
            "2026": [[0, 12208, 0, 0], [12208, 15549, 0, 0.05], [15549, 20188, 167, 0.1], [20188, 26700, 631, 0.12], [26700, 35136, 1412, 0.15], [35136, 46575, 2678, 0.2], [46575, 62005, 4965, 0.25], [62005, 82679, 8823, 0.3], [82679, 109956, 15025, 0.35], [109956, 9999999, 24572, 0.37]],
            "2025": [[0, 12081, 0, 0], [12081, 15387, 0, 0.05], [15387, 19978, 165, 0.1], [19978, 26422, 624, 0.12], [26422, 34770, 1398, 0.15], [34770, 46089, 2650, 0.2], [46089, 61359, 4914, 0.25], [61359, 81817, 8731, 0.3], [81817, 108810, 14869, 0.35], [108810, 9999999, 24316, 0.37]],
            "2024": [[0, 11902, 0, 0], [11902, 15159, 0, 0.05], [15159, 19682, 163, 0.1], [19682, 26031, 615, 0.12], [26031, 34255, 1377, 0.15], [34255, 45407, 2611, 0.2], [45407, 60450, 4841, 0.25], [60450, 80605, 8602, 0.3], [80605, 107243, 14698, 0.35], [107243, 9999999, 24027, 0.37]]
        };

        let yearStr = data.anio ? data.anio.toString() : "2026";

        const buildSRITable = (calcDet) => {
            if (!sriTables[yearStr]) return '';
            let rowsHtml = '';
            sriTables[yearStr].forEach((row, i) => {
                let fracBasica = fmt.format(row[0]);
                let excesoHasta = row[1] > 1000000 ? "En adelante" : fmt.format(row[1]);
                let impFB = fmt.format(row[2]);
                let pctExc = (row[3] * 100).toFixed(0) + "%";

                let isHighlighted = false;
                if (calcDet && calcDet.tipo == 'tabla' && Math.abs(Number(calcDet.fb) - Number(row[0])) < 0.1) {
                    isHighlighted = true;
                }

                let rowStyles = isHighlighted
                    ? "background-color: #334155; color: #ffffff; font-weight: 700; border-left: 4px solid #8b5cf6; box-shadow: inset 0 0 10px rgba(0,0,0,0.2);"
                    : ((i % 2 === 0) ? "background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569;" : "background-color: #f1f5f9; border-bottom: 1px solid #e2e8f0; color: #475569;");

                let iconHtml = isHighlighted ? `<i class="bi bi-arrow-right-circle-fill text-warning me-1"></i> ` : '';

                rowsHtml += `
                      <tr style="${rowStyles}">
                          <td class="text-end py-1 px-2">${iconHtml}${fracBasica}</td>
                          <td class="text-end py-1 px-2">${excesoHasta}</td>
                          <td class="text-end py-1 px-2">${impFB}</td>
                          <td class="text-end py-1 px-2">${pctExc}</td>
                      </tr>
                  `;
            });
            return `
              <div class="mt-3 p-0 rounded-3 shadow-sm bg-white" style="border: 1px solid #cbd5e1; border-radius: 8px; overflow: hidden;">
                  <div class="d-flex justify-content-between align-items-center p-2" style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                      <h6 class="mb-0 fw-bold" style="color: #0f172a; font-size: 0.9rem;"><i class="bi bi-grid-3x3-gap text-danger me-2"></i> Tabla Impuesto a la Renta</h6>
                      <span class="badge rounded-pill" style="background-color: #8b5cf6; color: white;">${yearStr}</span>
                  </div>
                  <div class="table-responsive">
                      <table class="table table-borderless mb-0" style="font-size: 0.8rem;">
                          <thead style="background-color: #1e293b; color: white;">
                              <tr>
                                  <th class="text-center py-2 px-1">Fracción<br>Básica</th>
                                  <th class="text-center py-2 px-1">Exceso<br>Hasta</th>
                                  <th class="text-center py-2 px-1">Impuesto<br>FB</th>
                                  <th class="text-center py-2 px-1">% Imp.<br>Exc.</th>
                              </tr>
                          </thead>
                          <tbody>
                              ${rowsHtml}
                          </tbody>
                      </table>
                  </div>
                  <div class="p-1 text-center" style="background-color: #eff6ff; border-top: 1px solid #bfdbfe;">
                      <p class="mb-0" style="color: #1e40af; font-size: 0.7rem;">
                          <strong>Res:</strong> NAC-DGERCGC24-00000041 (18/12/2024)
                      </p>
                  </div>
              </div>
              `;
        };

        if (declTable || estTable) {
            calcHtml += `
              <ul class="nav nav-pills nav-fill mb-3 bg-light rounded p-1" id="calcTabs" role="tablist">
                <li class="nav-item" role="presentation">
                  <button class="nav-link active fw-bold text-secondary py-1" id="decl-tab" data-bs-toggle="pill" data-bs-target="#decl-tab-pane" type="button" role="tab" aria-controls="decl-tab-pane" aria-selected="true" style="font-size: 0.85rem;">
                    <i class="bi bi-file-earmark-text"></i> DECLARADO
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link fw-bold py-1" id="est-tab" data-bs-toggle="pill" data-bs-target="#est-tab-pane" type="button" role="tab" aria-controls="est-tab-pane" aria-selected="false" style="font-size: 0.85rem;">
                    <i class="bi bi-calculator"></i> ESTIMADO
                  </button>
                </li>
              </ul>
              <div class="tab-content border rounded p-2 mb-3 bg-white" id="calcTabsContent">
                <div class="tab-pane fade show active" id="decl-tab-pane" role="tabpanel" aria-labelledby="decl-tab" tabindex="0">
                  ${declTable}
                  ${buildSRITable(data.calc_details_decl)}
                </div>
                <div class="tab-pane fade" id="est-tab-pane" role="tabpanel" aria-labelledby="est-tab" tabindex="0">
                  ${estTable}
                  ${buildSRITable(data.calc_details)}
                </div>
              </div>
              `;
        }


        // Table HTML logic removed, it is now generated dynamically inside buildSRITable

        rightHtml = `
              <div class="p-3 bg-white rounded shadow-sm border border-secondary border-opacity-25 sticky-top" style="top: 20px;">
                  <h6 class="text-primary mb-3"><i class="bi bi-calculator"></i> CALCULO DEL IMPUESTO</h6>
                  ${calcHtml}
              </div>
          `;
    } else if (regimen == 'rimpe-e') {
        inputsHtml = `
            <div class="mb-3 input-ir-azul p-3 rounded">
                <h6 class="text-primary mb-2">Ingresos Brutos (Form. 104)</h6>
                <div class="d-flex justify-content-between mb-1"><span class="small">Ventas Totales</span><span class="fw-bold">${fmt.format(data.kpis.ventas)}</span></div>
            </div>
            <div class="alert alert-info py-2 small border-info bg-opacity-10"><i class="bi bi-info-circle"></i> Los gastos NO reducen la base en RIMPE</div>
        `;
        rightHtml = `
            <h5 class="text-center text-primary mb-4">Liquidación RIMPE Emprendedor</h5>
            <div class="d-flex justify-content-between mb-3"><span class="fw-bold text-warning">Total Ingresos Brutos</span><span class="fw-bold text-warning fs-5">${fmt.format(data.base_imponible)}</span></div>
            <div class="text-center my-4">
                <h1 class="text-white opacity-25 fw-bold" style="font-size: 4rem; position: absolute; left: 50%; transform: translateX(-50%); z-index: 0;">2%</h1>
                <div style="position: relative; z-index: 1;">
                    <p class="small text-muted mb-1">Tarifa flat sobre ingresos brutos</p>
                    <h2 class="text-danger fw-bold mb-0">${fmt.format(data.ir_causado)}</h2>
                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger">IR Causado</span>
                </div>
            </div>
            <hr class="border-secondary">
            <div class="d-flex justify-content-between mt-3 p-2 rounded" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444;">
                <span class="fw-bold">SALDO A PAGAR</span><span class="fw-bold fs-5">${fmt.format(data.ir_causado)}</span>
            </div>
            ${data.base_imponible > 300000 ? '<div class="alert alert-danger mt-3 py-2 small"><i class="bi bi-exclamation-triangle"></i> Supera $300.000. Pasa a Régimen General.</div>' : ''}
        `;
    } else if (regimen == 'rimpe-np') {
        inputsHtml = `
            <div class="mb-3 input-ir-azul p-3 rounded">
                <h6 class="text-primary mb-2">Ingresos (Form. 104)</h6>
                <div class="d-flex justify-content-between mb-1"><span class="small">Ventas Totales</span><span class="fw-bold">${fmt.format(data.kpis.ventas)}</span></div>
            </div>
        `;
        rightHtml = `
            <h5 class="text-center text-primary mb-4">Liquidación RIMPE Negocio Popular</h5>
            <div class="d-flex justify-content-between mb-3"><span class="fw-bold text-warning">Total Ingresos</span><span class="fw-bold text-warning fs-5">${fmt.format(data.base_imponible)}</span></div>
            <div class="text-center my-4">
                <p class="small text-muted mb-1">Cuota fija semestral x 2</p>
                <h2 class="text-danger fw-bold mb-0">${fmt.format(data.ir_causado)}</h2>
                <span class="badge bg-danger bg-opacity-25 text-danger border border-danger">IR Causado Anual</span>
            </div>
            <hr class="border-secondary">
            <div class="d-flex justify-content-between mt-3 p-2 rounded" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444;">
                <span class="fw-bold">SALDO A PAGAR</span><span class="fw-bold fs-5">${fmt.format(data.ir_causado)}</span>
            </div>
            ${data.base_imponible > 20000 ? '<div class="alert alert-danger mt-3 py-2 small"><i class="bi bi-exclamation-triangle"></i> Supera $20.000. Pasa a RIMPE Emprendedor.</div>' : ''}
        `;
    }

    let isRendered = document.getElementById('ir-table-regimen');
    if (isRendered && isRendered.value === regimen) {
        const temp = document.createElement('div');
        temp.innerHTML = inputsHtml;
        const currentCells = colLeft.querySelectorAll('td');
        const newCells = temp.querySelectorAll('td');
        for (let i = 0; i < currentCells.length; i++) {
            if (i < newCells.length && !currentCells[i].querySelector('input')) {
                if (currentCells[i].innerHTML !== newCells[i].innerHTML) {
                    currentCells[i].innerHTML = newCells[i].innerHTML;
                }
            }
        }
        colRight.innerHTML = rightHtml;
        return;
    }

    colLeft.innerHTML = inputsHtml;
    colRight.innerHTML = rightHtml;

    let updateTimeout;
    document.querySelectorAll('.calc-trigger').forEach(input => {
        input.addEventListener('change', () => {
            updateState();
        });

        input.addEventListener('input', () => {
            clearTimeout(updateTimeout);
            updateTimeout = setTimeout(() => {
                updateState();
            }, 800);
        });
    });
}

function renderDashboard(data) {
    document.getElementById('dash-cumplidas').innerText = data.meses_cargados;
    document.getElementById('dash-faltantes').innerText = 12 - data.meses_cargados;
    document.getElementById('dash-iva').innerText = fmt.format(data.kpis.iva_pend);

    let html104 = '';
    let html103 = '';
    let f103Pendientes = 0;

    data.maestra.forEach(m => {
        let pillStyle = m.estado == 'Cumplida' ? 'background-color: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;' : 'background-color: #fff1f2; color: #e11d48; border: 1px solid #fecdd3;';

        let iconHtml = '';
        if (m.estado === 'Cumplida') {
            let letra = (m.tipo_declaracion === 'SUSTITUTIVA') ? 'S' : 'O';
            let bgCls = (letra === 'S') ? 'bg-warning text-dark' : 'bg-success text-white';
            iconHtml = `<span class="d-inline-flex align-items-center justify-content-center rounded-circle ${bgCls} me-1" style="width: 16px; height: 16px; font-size: 0.65rem; font-weight: bold;">${letra}</span>`;
        } else {
            iconHtml = '<i class="bi bi-exclamation-circle-fill me-1 text-danger"></i>';
        }

        let textEstado = m.estado;
        let badgesDerecha = `<span class="badge rounded-pill fw-bold px-2 py-1" style="${pillStyle}">${iconHtml}${textEstado}</span>`;
        if (m.estado === 'Cumplida') {
            if (m.l_903 > 0) {
                badgesDerecha = `<span class="badge bg-danger text-white me-1" style="font-size: 0.65rem;">Mora $${fmt.format(m.l_903)}</span>` + badgesDerecha;
            }
            if (m.tipo_declaracion === 'SUSTITUTIVA') {
                badgesDerecha = `<span class="badge bg-warning text-dark me-1" style="font-size: 0.65rem;">Sustitutiva</span>` + badgesDerecha;
            }
        }

        let fechaHtml = '';
        if (m.estado === 'Cumplida' && m.fecha_presentacion) {
            fechaHtml = `<div class="text-muted" style="font-size: 0.75rem; margin-top: 2px;"><i class="bi bi-calendar3 me-1"></i>${m.fecha_presentacion}</div>`;
        }

        html104 += `<div class="d-flex justify-content-between align-items-center p-2 border-bottom" style="border-color: #e2e8f0 !important;">
            <div>
                <span class="small text-dark fw-medium">${m.mes_nombre}</span>
                ${fechaHtml}
            </div>
            <div class="text-end">
                ${badgesDerecha}
            </div>
        </div>`;

        // Formulario 103 logic
        let hasF103Data = m.tot_f103 > 0 || (m.f103_tipo_declaracion && m.f103_tipo_declaracion !== '');
        let estado103 = hasF103Data ? 'Cumplida' : 'Falta PDF';

        if (!hasF103Data) {
            f103Pendientes++;
        }

        let style103 = estado103 == 'Cumplida' ? 'background-color: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;' : 'background-color: #fff1f2; color: #e11d48; border: 1px solid #fecdd3;';

        let iconHtml103 = '';
        if (estado103 === 'Cumplida') {
            let letra103 = (m.f103_tipo_declaracion === 'SUSTITUTIVA') ? 'S' : 'O';
            let bgCls103 = (letra103 === 'S') ? 'bg-warning text-dark' : 'bg-success text-white';
            iconHtml103 = `<span class="d-inline-flex align-items-center justify-content-center rounded-circle ${bgCls103} me-1" style="width: 16px; height: 16px; font-size: 0.65rem; font-weight: bold;">${letra103}</span>`;
        } else {
            iconHtml103 = '<i class="bi bi-exclamation-circle-fill me-1 text-danger"></i>';
        }

        let textEstado103 = estado103;
        let badges103 = `<span class="badge rounded-pill fw-bold px-2 py-1" style="${style103}">${iconHtml103}${textEstado103}</span>`;
        if (estado103 === 'Cumplida') {
            if (m.f103_l_903 > 0) {
                badges103 = `<span class="badge bg-danger text-white me-1" style="font-size: 0.65rem;">Mora $${fmt.format(m.f103_l_903)}</span>` + badges103;
            }
            if (m.f103_tipo_declaracion === 'SUSTITUTIVA') {
                badges103 = `<span class="badge bg-warning text-dark me-1" style="font-size: 0.65rem;">Sustitutiva</span>` + badges103;
            }
        }

        let fechaHtml103 = '';
        if (estado103 === 'Cumplida' && m.f103_fecha_presentacion) {
            fechaHtml103 = `<div class="text-muted" style="font-size: 0.75rem; margin-top: 2px;"><i class="bi bi-calendar3 me-1"></i>${m.f103_fecha_presentacion}</div>`;
        }

        html103 += `<div class="d-flex justify-content-between align-items-center p-2 border-bottom" style="border-color: #e2e8f0 !important;">
            <div>
                <span class="small text-dark fw-medium">${m.mes_nombre}</span>
                ${fechaHtml103}
            </div>
            <div class="text-end">
                ${badges103}
            </div>
        </div>`;
    });

    document.getElementById('dash-list-104').innerHTML = html104;

    let el103 = document.getElementById('dash-list-103');
    if (el103) el103.innerHTML = html103;

    let elDashF103 = document.getElementById('dash-f103');
    if (elDashF103) elDashF103.innerText = f103Pendientes;

    // Encadenamiento
    let encHtml = '';
    for (let i = 1; i < data.maestra.length; i++) {
        let prev = data.maestra[i - 1];
        let curr = data.maestra[i];
        if (prev.estado == 'Cumplida' && curr.estado == 'Cumplida') {
            let isOk = (prev.l_617 == curr.l_606);
            let icon = isOk ? 'link' : 'link-45deg';
            let alertStyle = isOk
                ? 'background-color: #f8fafc; border: 1px solid #cbd5e1; color: #334155;'
                : 'background-color: #334155; border: 1px solid #1e293b; color: #ffffff; font-weight: 600;';

            encHtml += `<div class="py-2 px-3 mb-2 small rounded shadow-sm" style="${alertStyle}">
                <i class="bi bi-${icon} me-1"></i> ${prev.mes_nombre}-${curr.mes_nombre}: 617($${prev.l_617}) &rarr; 606($${curr.l_606})
            </div>`;
        }
    }
    if (!encHtml) encHtml = '<p class="text-muted small mb-0 text-center mt-3"><i class="bi bi-file-earmark-pdf me-1"></i>Sube PDFs para verificar encadenamiento</p>';
    document.getElementById('dash-encadenamiento').innerHTML = encHtml;
}

function renderIESS(data) {
    const tbody = document.getElementById('tbody-iess');
    const tfoot = document.getElementById('tfoot-iess');

    if (!tbody || !tfoot) return;

    tbody.innerHTML = '';

    let t_emp = 0, t_bruta = 0, t_pat = 0, t_ind = 0, t_secap = 0, t_1314 = 0, t_vac = 0;
    const meses_nombres = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    for (let i = 1; i <= 12; i++) {
        let iess = data.iess_detalle && data.iess_detalle[i] ? data.iess_detalle[i] : null;
        let html = '';

        if (iess && Object.keys(iess).length > 0) {
            let emp = iess.empleados || 0;
            let bruta = iess.n_bruta || 0;
            let pat = iess.n_pat || 0;
            let ind = iess.n_ind || 0;
            let secap = iess.n_ccc || 0;
            let prov = iess.n_prov1314 || 0;
            let vac = iess.n_vac || 0;

            t_emp += emp; t_bruta += bruta; t_pat += pat; t_ind += ind; t_secap += secap; t_1314 += prov; t_vac += vac;

            html = `<tr>
                <td class="fw-bold">${meses_nombres[i - 1]}</td>
                <td class="text-end td-rojo">${emp}</td>
                <td class="text-end td-rojo fw-bold">${fmt.format(bruta)}</td>
                <td class="text-end td-rojo">${fmt.format(pat)}</td>
                <td class="text-end td-rojo">${fmt.format(ind)}</td>
                <td class="text-end td-rojo">${fmt.format(secap)}</td>
                <td class="text-end td-rojo">${fmt.format(prov)}</td>
                <td class="text-end td-rojo">${fmt.format(vac)}</td>
            </tr>`;
        } else {
            html = `<tr>
                <td class="fw-bold text-muted">${meses_nombres[i - 1]}</td>
                <td colspan="7" class="text-center text-muted small fst-italic">Sin datos - Sube la planilla consolidada</td>
            </tr>`;
        }
        tbody.innerHTML += html;
    }

    tfoot.innerHTML = `<tr>
        <td class="fw-bold">TOTAL ANUAL</td>
        <td class="text-end fw-bold td-rojo">-</td>
        <td class="text-end fw-bold td-rojo text-primary">${fmt.format(t_bruta)}</td>
        <td class="text-end fw-bold td-rojo">${fmt.format(t_pat)}</td>
        <td class="text-end fw-bold td-rojo">${fmt.format(t_ind)}</td>
        <td class="text-end fw-bold td-rojo">${fmt.format(t_secap)}</td>
        <td class="text-end fw-bold td-rojo">${fmt.format(t_1314)}</td>
        <td class="text-end fw-bold td-rojo">${fmt.format(t_vac)}</td>
    </tr>`;
}

window.iniciarAuditoria = function () {
    // Switch to Auditoria tab
    const tabBtn = document.getElementById('auditoria-tab');
    if (tabBtn) {
        new bootstrap.Tab(tabBtn).show();
    }
    // Execute comparison/audit immediately using whatever data is loaded
    window.realizarConciliacion();
};

window.obtenerDatosExaDesdeArray = function (dataArray) {
    let colMes = 0;
    let colVentas15 = -1;
    let colVentas0 = -1;
    let colIvaVentas = -1;
    let colTotalVentas = -1;
    let colCompras15 = -1;
    let colCompras0 = -1;
    let colIvaCompras = -1;
    let colTotalCompras = -1;
    let colRetIvaVentas = -1;
    let colRetRentaVentas = -1;

    let colVentasStart = -1;
    let colComprasStart = -1;
    let colRetStart = -1;
    let colF103Start = -1;

    // Scan Row 1 for MESES header row
    let headerRow1Index = -1;
    for (let r = 0; r < dataArray.length; r++) {
        for (let c = 0; c < dataArray[r].length; c++) {
            if (String(dataArray[r][c] || '').toUpperCase().includes('MESES')) {
                headerRow1Index = r;
                break;
            }
        }
        if (headerRow1Index !== -1) break;
    }

    if (headerRow1Index === -1) {
        headerRow1Index = 0;
    }

    let headerRow2Index = headerRow1Index + 1;
    let headerRow3Index = headerRow1Index + 2;

    // Scan Row 2 for section headers
    if (dataArray[headerRow2Index]) {
        for (let c = 0; c < dataArray[headerRow2Index].length; c++) {
            const val = String(dataArray[headerRow2Index][c] || '').toUpperCase().trim();
            if (val.includes('VENTAS')) colVentasStart = c;
            else if (val.includes('COMPRAS')) colComprasStart = c;
            else if (val.includes('RETENCIONES IVA') || val.includes('RETENCIONES')) colRetStart = c;
        }

        // Scan Row 2 for Totales
        for (let c = 0; c < dataArray[headerRow2Index].length; c++) {
            const val = String(dataArray[headerRow2Index][c] || '').toUpperCase().trim();
            if (val.includes('TOTAL')) {
                if (colVentasStart !== -1 && colComprasStart !== -1 && c > colVentasStart && c < colComprasStart) {
                    colTotalVentas = c;
                } else if (colComprasStart !== -1 && c > colComprasStart && (colRetStart === -1 || c < colRetStart)) {
                    colTotalCompras = c;
                }
            }
        }
    }

    // Scan Row 3 for subheaders
    if (dataArray[headerRow3Index]) {
        for (let c = 0; c < dataArray[headerRow3Index].length; c++) {
            const val = String(dataArray[headerRow3Index][c] || '').toUpperCase().trim();
            if (colVentasStart !== -1 && colComprasStart !== -1 && c >= colVentasStart && c < colComprasStart) {
                if (val.includes('15%') || val.includes('12%')) colVentas15 = c;
                else if (val.includes('0%')) colVentas0 = c;
                else if (val.includes('I.V.A.') || val === 'IVA') colIvaVentas = c;
            } else if (colComprasStart !== -1 && c >= colComprasStart && (colRetStart === -1 || c < colRetStart)) {
                if (val.includes('15%') || val.includes('12%')) colCompras15 = c;
                else if (val.includes('0%')) colCompras0 = c;
                else if (val.includes('I.V.A.') || val === 'IVA') colIvaCompras = c;
            }
        }
    }

    // Fallbacks if columns not found
    if (colTotalVentas === -1) colTotalVentas = 11;
    if (colTotalCompras === -1) colTotalCompras = 21;

    // Scan Row 1 for Form 103 section start
    if (dataArray[headerRow1Index]) {
        for (let c = 0; c < dataArray[headerRow1Index].length; c++) {
            const val = String(dataArray[headerRow1Index][c] || '').toUpperCase().trim();
            if (val.includes('FORMULARIO 103')) {
                colF103Start = c;
                break;
            }
        }
    }

    // Parse Month values
    const mesesMap = {
        'ENERO': 1, 'FEBRERO': 2, 'MARZO': 3, 'ABRIL': 4,
        'MAYO': 5, 'JUNIO': 6, 'JULIO': 7, 'AGOSTO': 8,
        'SEPTIEMBRE': 9, 'OCTUBRE': 10, 'NOVIEMBRE': 11, 'DICIEMBRE': 12
    };

    let meses = {};
    for (let i = 1; i <= 12; i++) {
        meses[i] = {
            v_base15: 0, v_base0: 0, v_iva: 0, v_retIva: 0, v_retRenta: 0, v_total: 0,
            c_base15: 0, c_base0: 0, c_iva: 0, c_total: 0
        };
    }

    let totales = {
        v_base15: 0, v_base0: 0, v_iva: 0, v_retIva: 0, v_retRenta: 0, v_total: 0,
        c_base15: 0, c_base0: 0, c_iva: 0, c_total: 0
    };

    const pVal = (val) => {
        if (val === undefined || val === null) return 0;
        let s = String(val).trim();
        if (s === '' || s === '-') return 0;
        
        s = s.replace(/[$\s%]/g, '');
        const lastDot = s.lastIndexOf('.');
        const lastComma = s.lastIndexOf(',');
        
        if (lastDot !== -1 && lastComma !== -1) {
            if (lastDot < lastComma) {
                s = s.replace(/\./g, '').replace(/,/g, '.');
            } else {
                s = s.replace(/,/g, '');
            }
        } else if (lastComma !== -1) {
            const afterComma = s.length - lastComma - 1;
            if (afterComma === 3) {
                s = s.replace(/,/g, '');
            } else {
                s = s.replace(/,/g, '.');
            }
        } else if (lastDot !== -1) {
            const afterDot = s.length - lastDot - 1;
            if (afterDot === 3) {
                s = s.replace(/\./g, '');
            }
        }
        
        const n = parseFloat(s);
        return isNaN(n) ? 0 : n;
    };

    for (let r = headerRow1Index + 3; r < dataArray.length; r++) {
        const row = dataArray[r];
        if (!row || row.length === 0 || !row[0]) continue;
        const mesKey = String(row[0]).toUpperCase().trim();
        if (mesKey.includes('TOTAL')) continue; // skip total row if present

        if (mesesMap[mesKey]) {
            let mNum = mesesMap[mesKey];

            let v_base15 = colVentas15 !== -1 ? pVal(row[colVentas15]) : 0;
            let v_base0 = colVentas0 !== -1 ? pVal(row[colVentas0]) : 0;
            let v_iva = colIvaVentas !== -1 ? pVal(row[colIvaVentas]) : 0;
            let v_total = colTotalVentas !== -1 ? pVal(row[colTotalVentas]) : (v_base15 + v_base0);

            let c_base15 = colCompras15 !== -1 ? pVal(row[colCompras15]) : 0;
            let c_base0 = colCompras0 !== -1 ? pVal(row[colCompras0]) : 0;
            let c_iva = colIvaCompras !== -1 ? pVal(row[colIvaCompras]) : 0;
            let c_total = colTotalCompras !== -1 ? pVal(row[colTotalCompras]) : (c_base15 + c_base0);

            // Retenciones IVA sum (under Retenciones section in columns colRetStart to colF103Start - 1 or end)
            let v_retIva = 0;
            if (colRetStart !== -1) {
                let endCol = colF103Start !== -1 ? colF103Start : row.length;
                for (let c = colRetStart; c < endCol; c++) {
                    const subHeader = String(dataArray[headerRow2Index][c] || '').toUpperCase().trim();
                    if (subHeader.includes('TOTAL')) {
                        v_retIva = pVal(row[c]);
                        break;
                    }
                }
            }

            // Retenciones Renta sum (under Form 103 section from colF103Start onwards)
            let v_retRenta = 0;
            if (colF103Start !== -1) {
                for (let c = colF103Start; c < row.length; c++) {
                    const subHeader = String(dataArray[headerRow2Index][c] || '').toUpperCase().trim();
                    if (subHeader.includes('TOTAL')) {
                        v_retRenta = pVal(row[c]);
                        break;
                    }
                }
            }

            meses[mNum] = {
                v_base15, v_base0, v_iva, v_retIva, v_retRenta, v_total,
                c_base15, c_base0, c_iva, c_total
            };

            totales.v_base15 += v_base15;
            totales.v_base0 += v_base0;
            totales.v_iva += v_iva;
            totales.v_retIva += v_retIva;
            totales.v_retRenta += v_retRenta;
            totales.v_total += v_total;

            totales.c_base15 += c_base15;
            totales.c_base0 += c_base0;
            totales.c_iva += c_iva;
            totales.c_total += c_total;
        }
    }

    return { meses, totales };
};

window.realizarConciliacion = function () {
    const anio = document.getElementById('select-anio').value;
    const mesVal = 0; // Forced to 0 (Anual)

    const btn = document.getElementById('btn-ejecutar-exa');
    const btnGlobal = document.getElementById('btn-conciliar');

    if (btn) { btn.innerHTML = '<i class="spinner-border spinner-border-sm"></i> Comparando...'; btn.disabled = true; }
    if (btnGlobal) { btnGlobal.innerHTML = '<i class="spinner-border spinner-border-sm"></i> Auditando...'; btnGlobal.disabled = true; }

    // Check if we have EXA data loaded locally (from Excel upload)
    if (window.exaDataArray && window.exaDataArray.length > 0) {
        setTimeout(() => {
            if (btn) { btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Ejecutar Comparación'; btn.disabled = false; }
            if (btnGlobal) { btnGlobal.innerHTML = '<i class="bi bi-shield-check"></i> Auditar vs EXA ERP'; btnGlobal.disabled = false; }

            const exaParsed = window.obtenerDatosExaDesdeArray(window.exaDataArray);
            const data = {
                success: true,
                totales_exa: exaParsed.totales,
                meses_exa: exaParsed.meses,
                tabla: `<div class="alert alert-info py-2 mb-0 mt-2 small"><i class="bi bi-info-circle-fill"></i> Datos tomados del archivo Excel EXA cargado.</div>`
            };

            window.renderizarResultadosConciliacion(data, mesVal);
        }, 150);
        return;
    }

    // Fallback: Fetch from database
    const formData = new URLSearchParams();
    formData.append('save', '1');
    formData.append('anio', anio);

    if (mesVal > 0) {
        let mesStr = mesVal.toString().padStart(2, '0');
        formData.append('From', `${anio}-${mesStr}`);
        formData.append('To', `${anio}-${mesStr}`);
        formData.append('fromMonth', mesVal.toString());
        formData.append('toMonth', mesVal.toString());
    } else {
        formData.append('From', `${anio}-01`);
        formData.append('To', `${anio}-12`);
        formData.append('fromMonth', '1');
        formData.append('toMonth', '12');
    }

    fetch(window.BASE_URL + '../tesoreria/FRONT/tes_alt_con_trib_1.0.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
        .then(r => r.text())
        .then(text => {
            if (btn) { btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Ejecutar Comparación'; btn.disabled = false; }
            if (btnGlobal) { btnGlobal.innerHTML = '<i class="bi bi-shield-check"></i> Auditar vs EXA ERP'; btnGlobal.disabled = false; }

            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error("No se pudo parsear el JSON de tesoreria, usando fallback:", text);
                data = {
                    success: false,
                    message: "No se pudo conectar correctamente con el módulo de Tesorería (posiblemente sesión expirada o ruta incorrecta).",
                    totales_exa: { ventas: 0, compras: 0 },
                    meses_exa: {}
                };
            }

            if (data.success === false) {
                console.warn("ERP session failed, proceeding with fallback comparison:", data.message);
                if (data.message && (data.message.includes("database selected") || data.message.includes("Debes iniciar sesi"))) {
                    let hasLocalData = (window.currentData && window.currentData.meses_cargados > 0) || (window.atsData && window.atsData.cargado);
                    if (!hasLocalData) {
                        alert("Debes iniciar sesión en EXA ERP y seleccionar una empresa (base de datos) antes de poder auditar, o cargar un archivo Excel de EXA / Declaración / ATS.");
                        return;
                    }
                }
            }

            window.renderizarResultadosConciliacion(data, mesVal);
        })
        .catch(err => {
            if (btn) { btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Ejecutar Comparación'; btn.disabled = false; }
            if (btnGlobal) { btnGlobal.innerHTML = '<i class="bi bi-shield-check"></i> Auditar vs EXA ERP'; btnGlobal.disabled = false; }
            console.error("Error in fetch:", err);
            const data = {
                success: false,
                message: "Error de red/servidor al conectar con EXA ERP.",
                totales_exa: { ventas: 0, compras: 0 },
                meses_exa: {}
            };
            window.renderizarResultadosConciliacion(data, mesVal);
        });
};

window.renderizarResultadosConciliacion = function (data, mesVal) {
    const detalleDiv = document.getElementById('div-auditoria-detalle');
    if (detalleDiv && data.tabla) {
        detalleDiv.innerHTML = data.tabla;
        const exaTable = detalleDiv.querySelector('table');
        if (exaTable) {
            exaTable.classList.add('table', 'table-bordered', 'table-sm', 'mt-2', 'bg-white');
            exaTable.style.width = '100%';
        }
    }

    // Integrar estados iniciales
    document.getElementById('auditoria-estado-inicial').classList.add('d-none');
    document.getElementById('auditoria-resultados').classList.remove('d-none');

    let f104_cargado = (window.currentData && window.currentData.meses_cargados > 0);
    let ats_cargado = (window.atsData && window.atsData.cargado);
    let exa_cargado = (window.exaDataArray && window.exaDataArray.length > 0) || (data.success && data.totales_exa && (data.totales_exa.ventas > 0 || data.totales_exa.compras > 0));

    if (f104_cargado) {
        document.getElementById('badge-aud-104').className = "badge bg-success text-white";
        document.getElementById('badge-aud-104').innerHTML = `<i class="bi bi-check-circle"></i> Cargado (${window.currentData.meses_cargados} meses)`;
        document.getElementById('nota-fuente-decl').innerText = "Declaración 104 cargada. ";
    } else {
        document.getElementById('badge-aud-104').className = "badge bg-warning text-dark";
        document.getElementById('badge-aud-104').innerHTML = `<i class="bi bi-exclamation-triangle"></i> No cargado`;
        document.getElementById('nota-fuente-decl').innerText = "Declaración 104 NO cargada (se usa $0.00). ";
    }

    if (ats_cargado) {
        let cantAts = window.atsData.ventas.length + window.atsData.compras.length;
        document.getElementById('badge-aud-ats').className = "badge bg-success text-white";
        document.getElementById('badge-aud-ats').innerHTML = `<i class="bi bi-check-circle"></i> Cargado (${window.atsData.periodo})`;
        document.getElementById('nota-fuente-ats').innerText = `ATS período ${window.atsData.periodo}. `;
    } else {
        document.getElementById('badge-aud-ats').className = "badge bg-warning text-dark";
        document.getElementById('badge-aud-ats').innerHTML = `<i class="bi bi-exclamation-triangle"></i> No cargado`;
        document.getElementById('nota-fuente-ats').innerText = "ATS NO cargado (se usa $0.00). ";
    }

    const badgeExa = document.getElementById('badge-aud-exa');
    if (badgeExa) {
        if (exa_cargado) {
            badgeExa.className = "badge bg-success text-white";
            badgeExa.innerHTML = `<i class="bi bi-check-circle"></i> Cargado`;
        } else {
            badgeExa.className = "badge bg-warning text-dark";
            badgeExa.innerHTML = `<i class="bi bi-exclamation-triangle"></i> No cargado`;
        }
    }

    const pVentas = (val) => parseFloat(String(val).replace(/,/g, '')) || 0;

    // 1. Datos EXA
    let exa = {
        v_base15: 0, v_base0: 0, v_iva: 0, v_retIva: 0, v_retRenta: 0, v_total: 0,
        c_base15: 0, c_base0: 0, c_iva: 0, c_total: 0
    };

    if (exa_cargado) {
        if (window.exaDataArray && window.exaDataArray.length > 0) {
            const localExa = window.obtenerDatosExaDesdeArray(window.exaDataArray);
            if (mesVal > 0) {
                exa = localExa.meses[mesVal] || exa;
            } else {
                exa = localExa.totales || exa;
            }
        } else {
            exa = {
                v_base15: pVentas(data.totales_exa?.ventas_15 || data.totales_exa?.ventas || 0),
                v_base0: pVentas(data.totales_exa?.ventas_0 || 0),
                v_iva: pVentas(data.totales_exa?.iva_ventas || 0),
                v_retIva: pVentas(data.totales_exa?.ret_iva_ventas || 0),
                v_retRenta: pVentas(data.totales_exa?.ret_renta_ventas || 0),
                v_total: pVentas(data.totales_exa?.total_ventas || data.totales_exa?.ventas || 0),

                c_base15: pVentas(data.totales_exa?.compras_15 || data.totales_exa?.compras || 0),
                c_base0: pVentas(data.totales_exa?.compras_0 || 0),
                c_iva: pVentas(data.totales_exa?.iva_compras || 0),
                c_total: pVentas(data.totales_exa?.total_compras || data.totales_exa?.compras || 0)
            };
        }
    }

    // 2. Datos ATS
    let ats = { v_base15: 0, v_base0: 0, v_iva: 0, v_retIva: 0, v_retRenta: 0, v_total: 0, c_base15: 0, c_base0: 0, c_iva: 0, c_total: 0 };
    if (ats_cargado) {
        let ventasATS = window.atsData.ventas;
        let comprasATS = window.atsData.compras;

        if (mesVal > 0 && window.atsData.meses && window.atsData.meses[mesVal]) {
            ventasATS = window.atsData.meses[mesVal].ventas;
            comprasATS = window.atsData.meses[mesVal].compras;
        }

        ventasATS.forEach(v => {
            let sign = (v.tipo === '04') ? -1 : 1;
            ats.v_base15 += v.base15 * sign; ats.v_base0 += v.base0 * sign; ats.v_iva += v.iva * sign;
            ats.v_retIva += v.retIva; ats.v_retRenta += v.retRenta; ats.v_total += (v.base15 + (v.base0 || 0)) * sign;
        });
        comprasATS.forEach(c => {
            let sign = (c.tipo === '04') ? -1 : 1;
            ats.c_base15 += c.base15 * sign; ats.c_base0 += c.base0 * sign; ats.c_iva += c.iva * sign; ats.c_total += (c.base15 + (c.base0 || 0)) * sign;
        });
    }

    // 3. Datos DECLARACIÓN
    let decl = { v_base15: 0, v_base0: 0, v_iva: 0, v_retIva: 0, v_retRenta: 0, v_total: 0, c_base15: 0, c_base0: 0, c_iva: 0, c_total: 0 };
    if (f104_cargado) {
        let t = null;
        if (mesVal > 0 && window.currentData.maestra) {
            const mData = window.currentData.maestra.find(m => parseInt(m.mes) === mesVal);
            if (mData) t = mData;
        } else if (window.currentData.totales) {
            t = window.currentData.totales;
        }

        if (t) {
            decl.v_base15 = parseFloat(t.v_401 || 0);
            decl.v_base0 = parseFloat(t.v_403 || 0);
            decl.v_iva = parseFloat(t.v_429 || 0);
            decl.v_retIva = parseFloat(t.tot_iva || 0);
            decl.v_retRenta = parseFloat(t.tot_renta || 0);
            decl.v_total = parseFloat(t.tot_v || 0) || (decl.v_base15 + decl.v_base0);

            decl.c_base15 = parseFloat(t.c_500 || 0);
            decl.c_base0 = parseFloat(t.c_507 || 0) + parseFloat(t.c_508 || 0);
            decl.c_iva = parseFloat(t.c_529 || 0);
            decl.c_total = parseFloat(t.tot_c || 0) || (decl.c_base15 + decl.c_base0);
        }
    }

    let anyDescuadre = false;

    function drawAuditoriaRow(concepto, valDecl, valExa, valAts, tipo, detallesEncoded) {
        let loadedCount = (f104_cargado ? 1 : 0) + (ats_cargado ? 1 : 0) + (exa_cargado ? 1 : 0);

        let diffStr = '';
        let isOk = true;

        if (loadedCount < 2) {
            diffStr = '<span class="text-muted">--</span>';
        } else if (loadedCount === 3) {
            let diffExa = valDecl - valExa;
            let diffAts = valDecl - valAts;
            let diffAtsExa = valAts - valExa;

            let okExa = Math.abs(diffExa) < 0.01;
            let okAts = Math.abs(diffAts) < 0.01;
            let okAtsExa = Math.abs(diffAtsExa) < 0.01;

            isOk = okExa && okAts && okAtsExa;

            if (isOk) {
                diffStr = '<span class="text-success">cuadrado</span>';
            } else {
                anyDescuadre = true;
                let diffsHtml = [];
                if (!okExa) diffsHtml.push('<span class="text-danger fw-bold d-block" style="font-size: 0.85em;">Maestra no cuadra con EXA. Dif: ' + fmt.format(Math.abs(diffExa)) + '</span>');
                if (!okAts) diffsHtml.push('<span class="text-warning fw-bold text-dark d-block" style="font-size: 0.85em;">Maestra no cuadra con ATS. Dif: ' + fmt.format(Math.abs(diffAts)) + '</span>');
                if (okExa && okAts && !okAtsExa) diffsHtml.push('<span class="text-info fw-bold d-block" style="font-size: 0.85em;">ATS no cuadra con EXA. Dif: ' + fmt.format(Math.abs(diffAtsExa)) + '</span>');

                diffStr = diffsHtml.join('') + `<button class="btn btn-sm btn-outline-danger mt-1 py-0 px-2" onclick="window.abrirModalAuditoria('${concepto}', '${tipo}', '${detallesEncoded}')"><i class="bi bi-search"></i> Ver</button>`;
            }
        } else {
            // Exactamente 2 fuentes cargadas
            if (f104_cargado && exa_cargado) {
                let diff = valDecl - valExa;
                isOk = Math.abs(diff) < 0.01;
                if (isOk) {
                    diffStr = '<span class="text-success">cuadrado</span>';
                } else {
                    anyDescuadre = true;
                    diffStr = '<span class="text-danger fw-bold d-block" style="font-size: 0.85em;">Maestra no cuadra con EXA. Dif: ' + fmt.format(Math.abs(diff)) + '</span>';
                    diffStr += `<button class="btn btn-sm btn-outline-danger mt-1 py-0 px-2" onclick="window.abrirModalAuditoria('${concepto}', '${tipo}', '${detallesEncoded}')"><i class="bi bi-search"></i> Ver</button>`;
                }
            } else if (f104_cargado && ats_cargado) {
                let diff = valDecl - valAts;
                isOk = Math.abs(diff) < 0.01;
                if (isOk) {
                    diffStr = '<span class="text-success">cuadrado</span>';
                } else {
                    anyDescuadre = true;
                    diffStr = '<span class="text-warning fw-bold text-dark d-block" style="font-size: 0.85em;">Maestra no cuadra con ATS. Dif: ' + fmt.format(Math.abs(diff)) + '</span>';
                    diffStr += `<button class="btn btn-sm btn-outline-danger mt-1 py-0 px-2" onclick="window.abrirModalAuditoria('${concepto}', '${tipo}', '${detallesEncoded}')"><i class="bi bi-search"></i> Ver</button>`;
                }
            } else if (ats_cargado && exa_cargado) {
                let diff = valAts - valExa;
                isOk = Math.abs(diff) < 0.01;
                if (isOk) {
                    diffStr = '<span class="text-success">cuadrado</span>';
                } else {
                    anyDescuadre = true;
                    diffStr = '<span class="text-info fw-bold d-block" style="font-size: 0.85em;">ATS no cuadra con EXA. Dif: ' + fmt.format(Math.abs(diff)) + '</span>';
                    diffStr += `<button class="btn btn-sm btn-outline-danger mt-1 py-0 px-2" onclick="window.abrirModalAuditoria('${concepto}', '${tipo}', '${detallesEncoded}')"><i class="bi bi-search"></i> Ver</button>`;
                }
            }
        }

        let showDecl = f104_cargado ? fmt.format(valDecl) : '<span class="opacity-50">$0.00</span>';
        let showExa = exa_cargado ? fmt.format(valExa) : '<span class="opacity-50">$0.00</span>';
        let showAts = ats_cargado ? fmt.format(valAts) : '<span class="opacity-50">$0.00</span>';

        // Fix missing concepto to string bug if it is a span element
        let cName = typeof concepto === 'string' && concepto.includes('<span') ? concepto : concepto.toUpperCase();
        return `<tr>
            <td class="fw-bold ps-2">${cName}</td>
            <td class="text-end text-muted">${showDecl}</td>
            <td class="text-end fw-bold" style="color: #185FA5;">${showExa}</td>
            <td class="text-end text-muted">${showAts}</td>
            <td class="text-start">${diffStr}</td>
        </tr>`;
    }

    const tbodyV = document.getElementById('tbody-auditoria-ventas');
    const tbodyC = document.getElementById('tbody-auditoria-compras');
    tbodyV.innerHTML = '';
    tbodyC.innerHTML = '';

    // Vista Anual: Desglose por mes
    const m_nombres = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    
    let localExa = null;
    if (exa_cargado && window.exaDataArray && window.exaDataArray.length > 0) {
        localExa = window.obtenerDatosExaDesdeArray(window.exaDataArray);
    }

    for (let i = 1; i <= 12; i++) {
        // Breakdowns
        let dv = {b15:{d:0,e:0,a:0}, b0:{d:0,e:0,a:0}, iva:{d:0,e:0,a:0}};
        let dc = {b15:{d:0,e:0,a:0}, b0:{d:0,e:0,a:0}, iva:{d:0,e:0,a:0}};

        // EXA monthly
        let mExaV = 0, mExaC = 0;
        if (localExa) {
            if (localExa.meses && localExa.meses[i]) {
                mExaV = localExa.meses[i].v_total || 0;
                mExaC = localExa.meses[i].c_total || 0;
                dv.b15.e = localExa.meses[i].v_base15||0; dv.b0.e = localExa.meses[i].v_base0||0; dv.iva.e = localExa.meses[i].v_iva||0;
                dc.b15.e = localExa.meses[i].c_base15||0; dc.b0.e = localExa.meses[i].c_base0||0; dc.iva.e = localExa.meses[i].c_iva||0;
            }
        } else if (data.meses_exa && data.meses_exa[i]) {
            mExaV = pVentas(data.meses_exa[i].ventas || 0);
            mExaC = pVentas(data.meses_exa[i].compras || 0);
            // Sin desglose exacto si viene de BD antiguo
        }

        // DECL monthly
        let mDeclV = 0, mDeclC = 0;
        if (f104_cargado && window.currentData.maestra) {
            const mData = window.currentData.maestra.find(m => parseInt(m.mes) === i);
            if (mData) {
                mDeclV = parseFloat(mData.tot_v || 0) || (parseFloat(mData.v_411 || 0) + parseFloat(mData.v_403 || 0));
                mDeclC = parseFloat(mData.tot_c || 0) || (parseFloat(mData.c_510 || 0) + parseFloat(mData.c_507 || 0) + parseFloat(mData.c_508 || 0));
                dv.b15.d = parseFloat(mData.v_411||0); dv.b0.d = parseFloat(mData.v_403||0); dv.iva.d = parseFloat(mData.v_429||0);
                dc.b15.d = parseFloat(mData.c_500||0); dc.b0.d = (parseFloat(mData.c_507||0)+parseFloat(mData.c_508||0)); dc.iva.d = parseFloat(mData.c_529||0);
            }
        }

        // ATS monthly
        let mAtsV = 0, mAtsC = 0;
        if (ats_cargado && window.atsData.meses && window.atsData.meses[i]) {
            window.atsData.meses[i].ventas.forEach(v => {
                let sign = (v.tipo === '04') ? -1 : 1;
                mAtsV += (v.base15 + (v.base0 || 0)) * sign;
                dv.b15.a += v.base15 * sign; dv.b0.a += (v.base0||0) * sign; dv.iva.a += v.iva * sign;
            });
            window.atsData.meses[i].compras.forEach(c => {
                let sign = (c.tipo === '04') ? -1 : 1;
                mAtsC += (c.base15 + (c.base0 || 0)) * sign;
                dc.b15.a += c.base15 * sign; dc.b0.a += (c.base0||0) * sign; dc.iva.a += c.iva * sign;
            });
        }

        let encV = encodeURIComponent(JSON.stringify(dv));
        let encC = encodeURIComponent(JSON.stringify(dc));

        tbodyV.innerHTML += drawAuditoriaRow(m_nombres[i - 1], mDeclV, mExaV, mAtsV, 'VENTAS', encV);
        tbodyC.innerHTML += drawAuditoriaRow(m_nombres[i - 1], mDeclC, mExaC, mAtsC, 'COMPRAS', encC);
    }
    // Totales
    tbodyV.innerHTML += drawAuditoriaRow('<span class="fs-5">TOTAL</span>', decl.v_total, exa.v_total, ats.v_total, 'VENTAS', '');
    tbodyC.innerHTML += drawAuditoriaRow('<span class="fs-5">TOTAL</span>', decl.c_total, exa.c_total, ats.c_total, 'COMPRAS', '');

    const banner = document.getElementById('auditoria-banner');
    if (loadedCount >= 2) {
        if (anyDescuadre) {
            banner.className = "alert alert-danger text-center py-2 fw-bold shadow-sm mb-4";
            banner.innerHTML = '<i class="bi bi-exclamation-octagon-fill me-2"></i> Descuadrado (Diferencias encontradas)';
        } else {
            banner.className = "alert alert-success text-center py-2 fw-bold shadow-sm mb-4";
            banner.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i> Cuadrado (Sin diferencias)';
        }
    } else {
        banner.className = "alert alert-warning text-center py-2 fw-bold shadow-sm mb-4 text-dark";
        banner.innerHTML = '<i class="bi bi-info-circle-fill me-2"></i> Se necesitan al menos 2 fuentes de datos cargadas para auditar.';
    }
};

window.abrirModalAuditoria = function(mes, tipo, detallesEncoded) {
    if (!detallesEncoded) return;
    
    document.getElementById('modal-aud-mes').innerText = mes.toUpperCase();
    document.getElementById('modal-aud-tipo').innerText = tipo;
    
    let dv = {};
    try {
        dv = JSON.parse(decodeURIComponent(detallesEncoded));
    } catch(e) {
        console.error("Error parsing details", e);
        return;
    }

    const tbody = document.getElementById('modal-aud-tbody');
    tbody.innerHTML = '';

    const drawRow = (concepto, d, e, a) => {
        let okE = Math.abs(d - e) < 0.01;
        let okA = Math.abs(d - a) < 0.01;
        let cE = okE ? 'text-muted' : 'text-danger fw-bold';
        let cA = okA ? 'text-muted' : 'text-warning text-dark fw-bold';
        
        return `<tr>
            <td class="fw-bold">${concepto}</td>
            <td class="text-end text-muted">${fmt.format(d)}</td>
            <td class="text-end ${cE}">${fmt.format(e)}</td>
            <td class="text-end ${cA}">${fmt.format(a)}</td>
        </tr>`;
    };

    tbody.innerHTML += drawRow('Base 15%', dv.b15.d, dv.b15.e, dv.b15.a);
    tbody.innerHTML += drawRow('Base 0%', dv.b0.d, dv.b0.e, dv.b0.a);
    tbody.innerHTML += drawRow('IVA', dv.iva.d, dv.iva.e, dv.iva.a);
    
    let tD = dv.b15.d + dv.b0.d;
    let tE = dv.b15.e + dv.b0.e;
    let tA = dv.b15.a + dv.b0.a;
    
    tbody.innerHTML += `<tr class="table-light">
        <td class="fw-bold fs-6">SUBTOTAL (Base)</td>
        <td class="text-end fw-bold">${fmt.format(tD)}</td>
        <td class="text-end fw-bold">${fmt.format(tE)}</td>
        <td class="text-end fw-bold">${fmt.format(tA)}</td>
    </tr>`;

    const myModal = new bootstrap.Modal(document.getElementById('modalAuditoriaDetalle'));
    myModal.show();
};

// =============================================
// RENDER F101 / F102 - Análisis Impuesto a la Renta (Año Anterior)
// =============================================
function renderF101(data) {
    const noData = document.getElementById('f101-no-data');
    const analisis = document.getElementById('f101-analisis');
    if (!noData || !analisis) return;

    // Check if renta data exists and is a parsed object (not just 'OK')
    const r = data.renta_analisis;
    if (!r || typeof r !== 'object' || !r.datos) {
        noData.classList.remove('d-none');
        analisis.classList.add('d-none');
        return;
    }
    noData.classList.add('d-none');
    analisis.classList.remove('d-none');

    const d = r.datos;
    const v = (key) => parseFloat(d[key]) || 0;

    // === HEADER: Datos de identificación ===
    document.getElementById('f101-tipo-form').innerText = 'Formulario ' + (r.tipo_formulario || '101/102');
    document.getElementById('f101-razon-social').innerText = r.razon_social || '—';
    document.getElementById('f101-ruc').innerText = r.ruc || '—';
    document.getElementById('f101-anio').innerText = r.anio || '—';
    document.getElementById('f101-tipo-decl').innerText = r.tipo_declaracion || 'Original';
    document.getElementById('f101-serie').innerText = r.numero_serie || '—';

    // === KPIs ===
    document.getElementById('f101-kpi-ingresos').innerText = fmt.format(v('6999'));
    document.getElementById('f101-kpi-gastos').innerText = fmt.format(v('7999'));
    const utilidad = v('801') > 0 ? v('801') : -v('802');
    const kpiUtil = document.getElementById('f101-kpi-utilidad');
    kpiUtil.innerText = fmt.format(utilidad);
    kpiUtil.className = 'kpi-value ' + (utilidad >= 0 ? 'text-success' : 'text-danger');
    document.getElementById('f101-kpi-ir').innerText = fmt.format(v('850'));
    document.getElementById('f101-kpi-pagado').innerText = fmt.format(v('999'));
    document.getElementById('f101-kpi-activos').innerText = fmt.format(v('499'));

    // Helper: render rows in a tbody
    function renderRows(tbodyId, items) {
        const tbody = document.getElementById(tbodyId);
        if (!tbody) return;
        tbody.innerHTML = items
            .filter(i => v(i.key) !== 0 || i.force)
            .map(i => {
                const val = v(i.key);
                const cls = i.highlight ? ' class="table-warning fw-bold"' : '';
                return `<tr${cls}><td>${i.key}</td><td>${i.label}</td><td class="text-end">${fmt.format(val)}</td></tr>`;
            }).join('');
    }

    // === BALANCE: Activos ===
    renderRows('f101-activos-tbody', [
        { key: '311', label: 'Efectivo y equivalentes' },
        { key: '315', label: 'Cuentas por cobrar no relacionadas locales' },
        { key: '312', label: 'Cuentas por cobrar relacionadas locales' },
        { key: '313', label: 'Cuentas por cobrar relacionadas exterior' },
        { key: '317', label: '(-) Provisión cuentas incobrables' },
        { key: '324', label: 'Crédito tributario IVA' },
        { key: '336', label: 'Crédito tributario IVA (activo)' },
        { key: '337', label: 'Crédito tributario IR', highlight: true },
        { key: '360', label: 'Otros activos corrientes' },
        { key: '361', label: 'TOTAL ACTIVOS CORRIENTES', highlight: true },
        { key: '362', label: 'Terrenos' },
        { key: '363', label: 'Edificios e inmuebles' },
        { key: '365', label: 'Instalaciones' },
        { key: '369', label: 'Maquinaria y equipo' },
        { key: '371', label: 'Muebles y enseres' },
        { key: '373', label: 'Equipo de computación' },
        { key: '375', label: 'Vehículos' },
        { key: '384', label: '(-) Depreciación acumulada PPE', highlight: true },
        { key: '386', label: '(-) Deterioro acumulado PPE' },
        { key: '387', label: 'Plusvalía (goodwill)' },
        { key: '388', label: 'Marcas, patentes, licencias' },
        { key: '392', label: '(-) Amortización intangibles' },
        { key: '440', label: 'Activos por impuestos diferidos' },
        { key: '449', label: 'TOTAL ACTIVOS NO CORRIENTES', highlight: true },
    ]);
    document.getElementById('f101-total-activos').innerText = fmt.format(v('499'));

    // === BALANCE: Pasivos ===
    renderRows('f101-pasivos-tbody', [
        { key: '513', label: 'Cuentas por pagar comerciales' },
        { key: '532', label: 'IR por pagar del ejercicio', highlight: true },
        { key: '533', label: 'Participación trabajadores por pagar', highlight: true },
        { key: '534', label: 'Obligaciones IESS' },
        { key: '545', label: 'Anticipos de clientes' },
        { key: '549', label: 'Otros pasivos corrientes' },
        { key: '550', label: 'TOTAL PASIVOS CORRIENTES', highlight: true },
        { key: '563', label: 'Obligaciones financieras LP (locales)' },
        { key: '566', label: 'Obligaciones financieras LP (exterior)' },
        { key: '573', label: 'Jubilación patronal', highlight: true },
        { key: '574', label: 'Desahucio', highlight: true },
        { key: '589', label: 'TOTAL PASIVOS NO CORRIENTES', highlight: true },
    ]);
    document.getElementById('f101-total-pasivos').innerText = fmt.format(v('599'));

    // === BALANCE: Patrimonio ===
    renderRows('f101-patrimonio-tbody', [
        { key: '601', label: 'Capital suscrito/pagado' },
        { key: '602', label: 'Capital suscrito no pagado (-)' },
        { key: '604', label: 'Reserva legal' },
        { key: '611', label: 'Utilidades acumuladas ejercicios anteriores' },
        { key: '615', label: 'Utilidad del ejercicio', highlight: true },
        { key: '616', label: 'Pérdida del ejercicio' },
    ]);
    document.getElementById('f101-total-patrimonio').innerText = fmt.format(v('698'));

    // Ecuación contable
    const activo = v('499');
    const pasivo = v('599');
    const patrimonio = v('698');
    const diff_ec = Math.abs(activo - (pasivo + patrimonio));
    const ecAlert = document.getElementById('f101-ecuacion-alert');
    const ecTexto = document.getElementById('f101-ecuacion-texto');
    if (diff_ec < 0.02) {
        ecAlert.className = 'alert alert-success mt-3 p-3';
        ecTexto.innerHTML = `✅ Activo (${fmt.format(activo)}) = Pasivo (${fmt.format(pasivo)}) + Patrimonio (${fmt.format(patrimonio)})`;
    } else {
        ecAlert.className = 'alert alert-danger mt-3 p-3';
        ecTexto.innerHTML = `❌ ERROR: Activo (${fmt.format(activo)}) ≠ Pasivo (${fmt.format(pasivo)}) + Patrimonio (${fmt.format(patrimonio)}). Diferencia: ${fmt.format(diff_ec)}`;
    }

    // === Análisis Detallado del Balance ===
    const c311 = v('311');
    const gastos = v('7999');
    const mesesEfectivo = gastos > 0 ? (c311 / (gastos / 12)).toFixed(1) : 'N/A';
    const c315 = v('315');
    const ventas = v('6999'); // Total ingresos
    const diasCobro = ventas > 0 ? ((c315 / ventas) * 365).toFixed(0) : 'N/A';
    const c317 = v('317');
    const c324 = v('324');
    const c327 = v('327');
    const c337 = v('337');
    const c336 = v('336');
    const c361 = v('361');
    const c550 = v('550');
    const liquidez = c550 > 0 ? (c361 / c550).toFixed(2) : 'N/A';

    const ppeBruto = v('362') + v('363') + v('365') + v('369') + v('371') + v('373') + v('375');
    const c384 = v('384');
    const pctAgotamiento = ppeBruto > 0 ? ((Math.abs(c384) / ppeBruto) * 100).toFixed(1) : '0.0';
    const c386 = v('386');
    const c388 = v('388');
    const defActivos = v('440') + v('441') + v('442') + v('443');
    const c449 = v('449');

    const c532 = v('532');
    const irCausado = v('850');
    const c533 = v('533');
    const ptCausado = v('803');
    const c534 = v('534');
    const c573 = v('573');
    const c574 = v('574');

    const c601 = v('601');
    const c602 = v('602');
    const capitalNeto = c601 - c602;
    const c604 = v('604');
    const c611 = v('611');
    const resEjercicio = v('615') > 0 ? v('615') : -v('616');
    const c698 = v('698');
    const deRatio = c698 > 0 ? (pasivo / c698).toFixed(2) : 'N/A';

    const analisisHtml = `
    <div class="row g-3">
        <!-- ACTIVOS CORRIENTES -->
        <div class="col-md-6">
            <div class="card h-100 border-info shadow-sm">
                <div class="card-header bg-info bg-opacity-10 text-info fw-bold"><i class="bi bi-wallet2"></i> ACTIVOS CORRIENTES (311-361)</div>
                <div class="card-body" style="font-size:0.85rem">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><strong>311 Efectivo:</strong> $${fmt.format(c311)}. <br><span class="text-muted">¿Cubre al menos 1 mes de gastos operativos?</span> <br>➡️ ${mesesEfectivo} meses. ${(mesesEfectivo !== 'N/A' && parseFloat(mesesEfectivo) < 1) ? '<span class="text-danger fw-bold">⚠️ Menor a 1 mes</span>' : '✅'}</li>
                        <li class="mb-2"><strong>315 Cuentas x cobrar:</strong> $${fmt.format(c315)}. <br>➡️ Días de cobro = ${diasCobro} días. ¿Hay política de cobranza?</li>
                        <li class="mb-2"><strong>317, 324, 327 Provisiones:</strong> <br>➡️ ¿Calculadas correctamente? (Sugerencia: 1% anual cartera >180d).</li>
                        <li class="mb-2"><strong>337 Crédito tributario IR:</strong> $${fmt.format(c337)} <br>➡️ Debe repetirse en casilla 857: $${fmt.format(v('857'))}.</li>
                        <li class="mb-2"><strong>336 Crédito tributario IVA:</strong> $${fmt.format(c336)} <br>➡️ Verificar que no esté cargado a gasto.</li>
                        <li class="mb-2"><strong>361 Total Corriente:</strong> $${fmt.format(c361)}. <br>➡️ Liquidez (361/550) = ${liquidez}.</li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- ACTIVOS NO CORRIENTES -->
        <div class="col-md-6">
            <div class="card h-100 border-info shadow-sm">
                <div class="card-header bg-info bg-opacity-10 text-info fw-bold"><i class="bi bi-building"></i> ACTIVOS NO CORRIENTES (362-449)</div>
                <div class="card-body" style="font-size:0.85rem">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><strong>PPE (362-375):</strong> <br>➡️ Costo histórico bruto = $${fmt.format(ppeBruto)}. Revisar vida útil y tasa de depreciación.</li>
                        <li class="mb-2"><strong>384 Depreciación acumulada:</strong> $${fmt.format(c384)}. <br>➡️ % Agotamiento = ${pctAgotamiento}%.</li>
                        <li class="mb-2"><strong>386 Deterioro PPE:</strong> $${fmt.format(c386)}. <br>➡️ ${c386 > 0 ? '<span class="text-warning fw-bold">⚠️ ¿Hay estudio técnico de valor recuperable?</span>' : 'Sin deterioro.'}</li>
                        <li class="mb-2"><strong>388 Intangibles:</strong> $${fmt.format(c388)}. <br>➡️ ¿Se amortizan? ¿Es plusvalía (no amortizable) o licencia?</li>
                        <li class="mb-2"><strong>440-443 Impuestos diferidos:</strong> $${fmt.format(defActivos)}. <br>➡️ ¿Qué diferencia temporaria los genera?</li>
                        <li class="mb-2"><strong>449 Total No Corriente:</strong> $${fmt.format(c449)}.</li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- PASIVOS -->
        <div class="col-md-6">
            <div class="card h-100 border-danger shadow-sm">
                <div class="card-header bg-danger bg-opacity-10 text-danger fw-bold"><i class="bi bi-journal-minus"></i> PASIVOS (511-589)</div>
                <div class="card-body" style="font-size:0.85rem">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><strong>532 IR x pagar:</strong> $${fmt.format(c532)}. <br>➡️ Debe coincidir con casilla 850 ($${fmt.format(irCausado)}). ${(c532 !== irCausado && c532 > 0) ? '<span class="text-danger fw-bold">⚠️ Diferencia detectada.</span>' : ''}</li>
                        <li class="mb-2"><strong>533 PT x pagar:</strong> $${fmt.format(c533)}. <br>➡️ Debe coincidir con casilla 803 ($${fmt.format(ptCausado)}).</li>
                        <li class="mb-2"><strong>534 IESS:</strong> $${fmt.format(c534)}. <br>➡️ ¿Al día? Si hay mora, hay riesgo de multas.</li>
                        <li class="mb-2"><strong>550 Total Pasivo Corriente:</strong> $${fmt.format(c550)}.</li>
                        <li class="mb-2"><strong>573 Jubilación patronal LP:</strong> $${fmt.format(c573)}. <br>➡️ ${c573 === 0 ? '<span class="text-warning fw-bold">⚠️ Si hay empleados con >10 años y es $0, revisar provisión.</span>' : '✅ Provisión registrada.'}</li>
                        <li class="mb-2"><strong>574 Desahucio:</strong> $${fmt.format(c574)}. <br>➡️ Verificar provisión acumulada.</li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- PATRIMONIO -->
        <div class="col-md-6">
            <div class="card h-100 shadow-sm" style="border-color:#7c3aed">
                <div class="card-header fw-bold" style="background:#ede9fe; color:#7c3aed"><i class="bi bi-pie-chart-fill"></i> PATRIMONIO (601-698)</div>
                <div class="card-body" style="font-size:0.85rem">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><strong>601-602 Capital pagado neto:</strong> $${fmt.format(capitalNeto)}.</li>
                        <li class="mb-2"><strong>604 Reserva legal:</strong> $${fmt.format(c604)}. <br>➡️ ¿Cumple Art. 297 Ley Cías? (5% utilidad hasta 10% del capital).</li>
                        <li class="mb-2"><strong>611 Utilidades retenidas:</strong> $${fmt.format(c611)}. <br>➡️ ¿Hay política de distribución de dividendos?</li>
                        <li class="mb-2"><strong>615/616 Resultado del ejercicio:</strong> $${fmt.format(resEjercicio)}.</li>
                        <li class="mb-2"><strong>698 Total Patrimonio:</strong> $${fmt.format(c698)}. <br>➡️ Ratio D/E (Pasivo/Patrimonio) = ${deRatio}.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    `;

    const analisisContainer = document.getElementById('f101-analisis-bloques');
    if (analisisContainer) {
        analisisContainer.innerHTML = analisisHtml;
    }

    // === ESTADO DE RESULTADOS: Ingresos ===
    renderRows('f101-ingresos-tbody', [
        { key: '6001', label: 'Ventas locales gravadas tarifa ≠ 0%' },
        { key: '6003', label: 'Ventas locales gravadas tarifa 0% / exentas' },
        { key: '6005', label: 'Prestación de servicios gravados' },
        { key: '6007', label: 'Prestación servicios tarifa 0%' },
        { key: '6009', label: 'Exportaciones de bienes' },
        { key: '6011', label: 'Exportaciones de servicios' },
        { key: '1005', label: 'TOTAL INGRESOS ORDINARIOS', highlight: true },
        { key: '6021', label: 'Dividendos' },
        { key: '6024', label: 'Regalías' },
        { key: '6027', label: 'Intereses financieros' },
        { key: '6132', label: 'Otros ingresos' },
        { key: '6134', label: 'Ingresos por reembolso de seguros' },
        { key: '6152', label: 'Ingresos brutos según contabilidad' },
    ]);
    document.getElementById('f101-total-ingresos').innerText = fmt.format(v('6999'));

    // === ESTADO DE RESULTADOS: Costos y Gastos ===
    renderRows('f101-gastos-tbody', [
        { key: '7041', label: 'Sueldos, salarios (gravados IESS)', highlight: true },
        { key: '7044', label: 'Beneficios sociales no gravados IESS' },
        { key: '7047', label: 'Aporte patronal IESS', highlight: true },
        { key: '7050', label: 'Honorarios profesionales' },
        { key: '7053', label: 'Honorarios a no residentes' },
        { key: '7056', label: 'Jubilación patronal (gasto)', highlight: true },
        { key: '7059', label: 'Desahucio (gasto)', highlight: true },
        { key: '7065', label: 'Depreciación acelerada' },
        { key: '7068', label: 'Depreciación no acelerada' },
        { key: '7095', label: 'Amortización de intangibles' },
        { key: '7173', label: 'Promoción y publicidad' },
        { key: '7179', label: 'Combustibles' },
        { key: '7191', label: 'Suministros y materiales' },
        { key: '7197', label: 'Mantenimiento y reparaciones' },
        { key: '7209', label: 'Impuestos, contribuciones y otros', highlight: true },
        { key: '7242', label: 'Servicios públicos' },
        { key: '7248', label: 'Otros gastos', highlight: true },
        { key: '7269', label: 'Gastos financieros (comisiones)' },
        { key: '7991', label: 'TOTAL COSTOS OPERACIONALES', highlight: true },
        { key: '7992', label: 'TOTAL GASTOS' },
    ]);
    document.getElementById('f101-total-gastos').innerText = fmt.format(v('7999'));

    // === CONCILIACIÓN TRIBUTARIA ===
    const concItems = [
        { key: '801', label: 'Utilidad del ejercicio', obs: v('801') > 0 ? '6999 - 7999 = ' + fmt.format(v('6999') - v('7999')) : '' },
        { key: '802', label: 'Pérdida del ejercicio', obs: v('802') > 0 ? 'Amortizable en 5 períodos siguientes' : '' },
        { key: '098', label: 'Base cálculo Participación Trabajadores' },
        { key: '803', label: '(-) Participación trabajadores 15%', obs: Math.abs(v('803') - v('098') * 0.15) < 1 ? '✅ Cuadra' : '⚠️ Verificar: 098 × 15% = ' + fmt.format(v('098') * 0.15) },
        { key: '804', label: '(-) Dividendos exentos' },
        { key: '805', label: '(-) Otras rentas exentas / no objeto IR' },
        { key: '806', label: '(+) Gastos no deducibles locales', obs: v('806') > 0 ? '⚠️ Aumentan base gravable' : '' },
        { key: '807', label: '(+) Gastos no deducibles del exterior', obs: v('807') > 0 ? '⚠️ Aumentan base gravable' : '' },
        { key: '808', label: '(+) Gastos para generar rentas exentas' },
        { key: '809', label: '(-) PT atribuible a ingresos exentos' },
        { key: '810', label: '(-) Deducciones adicionales (empleo, etc.)' },
        { key: '811', label: '(+/-) Ajuste precios de transferencia' },
        { key: '836', label: 'UTILIDAD GRAVABLE', obs: '', highlight: true },
        { key: '837', label: 'Pérdida sujeta a amortización' },
        { key: '849', label: 'Saldo utilidad gravable (fuera ZEDE)' },
        { key: '850', label: 'TOTAL IR CAUSADO', obs: '', highlight: true },
        { key: '857', label: '(-) Retenciones en la fuente recibidas', obs: Math.abs(v('857') - v('337')) < 1 ? '✅ Coincide con cas. 337' : '⚠️ No coincide con cas. 337 (' + fmt.format(v('337')) + ')' },
        { key: '858', label: '(-) Retenciones por dividendos anticipados' },
        { key: '859', label: '(-) Crédito tributario impuestos pagados exterior' },
        { key: '861', label: '(-) Crédito tributario años anteriores' },
        { key: '869', label: 'IR A PAGAR', obs: '', highlight: true },
        { key: '870', label: 'Saldo a favor contribuyente' },
        { key: '871', label: 'Anticipo calculado próximo año' },
        { key: '902', label: 'Total impuesto a pagar' },
        { key: '903', label: 'Interés por mora' },
        { key: '904', label: 'Multa' },
        { key: '999', label: 'TOTAL PAGADO', obs: '', highlight: true },
    ];
    const concTbody = document.getElementById('f101-conciliacion-tbody');
    if (concTbody) {
        concTbody.innerHTML = concItems
            .filter(i => v(i.key) !== 0 || i.highlight)
            .map(i => {
                const cls = i.highlight ? ' class="table-warning fw-bold"' : '';
                return `<tr${cls}><td>${i.key}</td><td>${i.label}</td><td class="text-end">${fmt.format(v(i.key))}</td><td class="small text-muted">${i.obs || ''}</td></tr>`;
            }).join('');
    }

    // === VERIFICACIONES CRUZADAS ===
    function verifRow(desc, valA, valB) {
        const diff = Math.abs(valA - valB);
        const ok = diff < 1;
        return `<tr class="${ok ? '' : 'table-danger'}">
            <td>${ok ? '✅' : '❌'}</td>
            <td>${desc}</td>
            <td class="text-end">${fmt.format(valA)}</td>
            <td class="text-end">${fmt.format(valB)}</td>
            <td class="text-end">${fmt.format(diff)}</td>
            <td><span class="badge ${ok ? 'bg-success' : 'bg-danger'}">${ok ? 'Cuadra' : 'Diferencia'}</span></td>
        </tr>`;
    }

    const verifTbody = document.getElementById('f101-verif-tbody');
    if (verifTbody) {
        verifTbody.innerHTML = [
            verifRow('Ecuación contable: 499 = 599 + 698', v('499'), v('599') + v('698')),
            verifRow('IR Balance (532) = IR Causado (850)', v('532'), v('850')),
            verifRow('PT Balance (533) = PT Conciliación (803)', v('533'), v('803')),
            verifRow('Crédito Trib. (337) = Retenciones (857)', v('337'), v('857')),
            verifRow('Ingresos - Gastos (6999-7999) = Utilidad (801)', v('6999') - v('7999'), v('801')),
            verifRow('Utilidad neta (615) ≈ 801 - 803 - 850', v('615'), v('801') - v('803') - v('850')),
            verifRow('Aporte patronal (7047) ≈ Sueldos (7041) × 12.15%', v('7047'), v('7041') * 0.1215),
            verifRow('PT = Base 098 × 15%', v('803'), v('098') * 0.15),
        ].join('');
    }

    // === INDICADORES FINANCIEROS ===
    function indRow(nombre, formula, valor, comment) {
        return `<tr><td class="fw-bold">${nombre}</td><td class="small text-muted">${formula}</td><td class="text-end fw-bold">${valor}</td><td class="small">${comment}</td></tr>`;
    }

    const totalIngresos = v('6999');
    const totalGastos = v('7999');
    const indTbody = document.getElementById('f101-indicadores-tbody');
    if (indTbody) {
        const margenNeto = totalIngresos > 0 ? (v('615') / totalIngresos * 100) : 0;
        const cargaTrib = v('801') > 0 ? (v('850') / v('801') * 100) : 0;
        const presionFiscal = totalIngresos > 0 ? ((v('850') + v('803')) / totalIngresos * 100) : 0;
        const ratioDeuda = v('698') > 0 ? (v('599') / v('698')) : 0;
        const liquidez = v('550') > 0 ? (v('361') / v('550')) : 0;
        const diasCobro = totalIngresos > 0 ? ((v('315')) / (totalIngresos / 365)) : 0;
        const diasPago = totalGastos > 0 ? ((v('513')) / (totalGastos / 365)) : 0;
        const rotActivos = v('499') > 0 ? (totalIngresos / v('499')) : 0;
        const sumaPPE = v('362') + v('363') + v('365') + v('369') + v('371') + v('373') + v('375');
        const pctDeprec = sumaPPE > 0 ? (v('384') / sumaPPE * 100) : 0;

        indTbody.innerHTML = [
            indRow('Margen neto', '615 / 6999 × 100', margenNeto.toFixed(2) + '%', margenNeto > 10 ? '✅ Saludable' : margenNeto > 0 ? '⚠️ Bajo' : '❌ Negativo'),
            indRow('Carga tributaria efectiva', '850 / 801 × 100', cargaTrib.toFixed(2) + '%', Math.abs(cargaTrib - 22) < 5 ? '✅ Cercana a tarifa' : cargaTrib < 15 ? '⚠️ Baja' : '🔴 Alta'),
            indRow('Presión fiscal total', '(850+803) / 6999 × 100', presionFiscal.toFixed(2) + '%', presionFiscal < 5 ? '✅ Baja' : presionFiscal < 15 ? 'Moderada' : '🔴 Alta'),
            indRow('Ratio endeudamiento', '599 / 698', ratioDeuda.toFixed(2), ratioDeuda < 1 ? '✅ Bajo riesgo' : ratioDeuda < 2 ? '⚠️ Moderado' : '🔴 Alto'),
            indRow('Liquidez corriente', '361 / 550', liquidez.toFixed(2), liquidez > 1.5 ? '✅ Buena' : liquidez > 1 ? '⚠️ Ajustada' : '🔴 Riesgo'),
            indRow('Días de cobro', '315 / (6999/365)', diasCobro.toFixed(0) + ' días', diasCobro < 30 ? '✅ Excelente' : diasCobro < 60 ? 'Normal' : '⚠️ Lento'),
            indRow('Días de pago', '513 / (7999/365)', diasPago.toFixed(0) + ' días', diasPago < 30 ? 'Rápido' : diasPago < 60 ? '✅ Normal' : '⚠️ Retraso'),
            indRow('Rotación de activos', '6999 / 499', rotActivos.toFixed(2), rotActivos > 1 ? '✅ Eficiente' : '⚠️ Baja rotación'),
            indRow('% Depreciación acumulada', '384 / Σ(362-375) × 100', pctDeprec.toFixed(1) + '%', pctDeprec > 90 ? '🔴 Activos casi agotados' : pctDeprec > 60 ? '⚠️ Activos maduros' : '✅ Vida útil disponible'),
        ].join('');
    }

    // === ALERTAS Y RIESGOS ===
    const alertas = [];
    function addAlerta(riesgo, desc, cas, val) {
        const color = riesgo === 'ALTO' ? 'danger' : riesgo === 'MEDIO' ? 'warning' : 'info';
        alertas.push(`<tr><td><span class="badge bg-${color}">${riesgo}</span></td><td>${desc}</td><td>${cas}</td><td>${fmt.format(val)}</td></tr>`);
    }

    if (v('806') > 0) addAlerta('MEDIO', 'Gastos no deducibles declarados — verificar si hay más no detectados', '806', v('806'));
    if (v('807') > 0) addAlerta('MEDIO', 'Gastos no deducibles del exterior', '807', v('807'));
    if (v('7053') > 0) addAlerta('ALTO', 'Honorarios a no residentes — sujeto a retención Art. 92 LRTI', '7053', v('7053'));
    if (totalIngresos > 0 && (v('7173') / totalIngresos) > 0.04) addAlerta('MEDIO', 'Publicidad supera el 4% de ingresos — exceso no deducible (Art. 28 RLRTI)', '7173', v('7173'));
    if (v('312') > 0 || v('313') > 0) addAlerta('MEDIO', 'Cuentas por cobrar con relacionadas — obligación de precios de transferencia', '312/313', v('312') + v('313'));
    const sumaPPE2 = v('362') + v('363') + v('365') + v('369') + v('371') + v('373') + v('375');
    if (sumaPPE2 > 0 && (v('384') / sumaPPE2) > 0.9) addAlerta('BAJO', 'PPE casi totalmente depreciada — posible subvaloración', '384', v('384'));
    if (v('573') === 0 && v('7041') > 0) addAlerta('ALTO', 'Jubilación patronal en 0 con nómina existente — riesgo omisión provisión', '573', 0);
    if (v('574') === 0 && v('7041') > 0) addAlerta('ALTO', 'Desahucio en 0 con nómina existente — riesgo omisión provisión', '574', 0);
    if (v('837') > 0) addAlerta('MEDIO', 'Pérdida tributaria — verificar amortización correcta en siguientes períodos', '837', v('837'));
    if (v('857') > v('850')) addAlerta('BAJO', 'Retenciones superiores a IR causado — saldo a favor, verificar devolución o arrastre', '857 vs 850', v('857') - v('850'));
    if (v('7248') > 0 && totalGastos > 0 && (v('7248') / totalGastos) > 0.2) addAlerta('ALTO', '"Otros gastos" supera el 20% del total — riesgo de impugnación por falta de sustento', '7248', v('7248'));
    if (v('360') > 0 && v('499') > 0 && (v('360') / v('499')) > 0.1) addAlerta('MEDIO', 'Otros activos corrientes significativos — solicitar detalle', '360', v('360'));
    if (v('7056') === 0 && v('7041') > 0) addAlerta('MEDIO', 'Gasto jubilación patronal en 0 con nómina — verificar si aplica', '7056', 0);
    if (v('7059') === 0 && v('7041') > 0) addAlerta('MEDIO', 'Gasto desahucio en 0 con nómina — verificar si aplica', '7059', 0);

    if (alertas.length === 0) {
        alertas.push('<tr><td colspan="4" class="text-center text-success py-3"><i class="bi bi-check-circle-fill me-2"></i> No se detectaron alertas significativas</td></tr>');
    }

    const alertTbody = document.getElementById('f101-alertas-tbody');
    if (alertTbody) {
        alertTbody.innerHTML = alertas.join('');
    }
}

function renderAnalisisRetenciones(data) {
    if (!data.ret_analisis) return;

    const ana = data.ret_analisis;

    // KPIs
    document.getElementById('ret-kpi-docs').innerText = ana.total_docs || 0;
    document.getElementById('ret-kpi-total').innerText = fmt.format(data.tot_ret_global || 0);

    const countAgentes = Object.keys(ana.agentes || {}).length;
    document.getElementById('ret-kpi-agentes').innerText = countAgentes;
    document.getElementById('ret-kpi-promedio').innerText = fmt.format((data.tot_ret_global || 0) / 12);

    // Progress Bars Renta vs IVA
    const totRenta = data.tot_renta_global || 0;
    const totIva = data.tot_iva_global || 0;
    const totRet = data.tot_ret_global || 0;

    document.getElementById('ret-kpi-renta-total').innerText = fmt.format(totRenta);
    document.getElementById('ret-kpi-iva-total').innerText = fmt.format(totIva);

    if (totRet > 0) {
        const pctRenta = ((totRenta / totRet) * 100).toFixed(1);
        const pctIva = ((totIva / totRet) * 100).toFixed(1);

        document.getElementById('ret-bar-renta').style.width = pctRenta + '%';
        document.getElementById('ret-pct-renta').innerText = pctRenta + '% del total retenido';

        document.getElementById('ret-bar-iva').style.width = pctIva + '%';
        document.getElementById('ret-pct-iva').innerText = pctIva + '% del total retenido';
    }

    // Tabla Evolución Mensual
    let evHtml = '';
    const mesesStr = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    let maxRetMes = 0;

    for (let i = 0; i < 12; i++) {
        const r = data.maestra[i] || {};
        const tr = r.tot_ret_rec || 0;
        if (tr > maxRetMes) maxRetMes = tr;
    }

    for (let i = 0; i < 12; i++) {
        const r = data.maestra[i] || {};
        const docsMes = ana.docs_por_mes[i + 1] || 0;
        const r_renta = r.tot_renta || 0;
        const r_iva = r.tot_iva || 0;
        const r_tot = r.tot_ret_rec || 0;

        let pctBarRenta = maxRetMes > 0 ? (r_renta / maxRetMes * 100) : 0;
        let pctBarIva = maxRetMes > 0 ? (r_iva / maxRetMes * 100) : 0;

        evHtml += `<tr>
            <td>${mesesStr[i]}</td>
            <td>${docsMes}</td>
            <td class="text-end text-primary">${fmt.format(r_renta)}</td>
            <td class="text-end text-info">${fmt.format(r_iva)}</td>
            <td class="text-end fw-bold">${fmt.format(r_tot)}</td>
            <td class="text-start align-middle">
                <div class="progress" style="height: 6px; background-color: transparent;">
                    <div class="progress-bar bg-primary" style="width: ${pctBarRenta}%"></div>
                    <div class="progress-bar bg-info" style="width: ${pctBarIva}%"></div>
                </div>
            </td>
        </tr>`;
    }
    document.getElementById('ret-evolucion-tbody').innerHTML = evHtml;

    document.getElementById('ret-evolucion-tfoot').innerHTML = `<tr class="th-gris">
        <th class="text-end fw-bold">TOTALES</th>
        <th class="fw-bold">${ana.total_docs || 0}</th>
        <th class="text-end fw-bold text-primary">${fmt.format(totRenta)}</th>
        <th class="text-end fw-bold text-info">${fmt.format(totIva)}</th>
        <th class="text-end fw-bold">${fmt.format(totRet)}</th>
        <th></th>
    </tr>`;

    // Códigos Renta e IVA
    const cods = Object.values(ana.codigos || {});
    const codsRenta = cods.filter(c => c.tipo == 'RENTA').sort((a, b) => b.retenido - a.retenido);
    const codsIva = cods.filter(c => c.tipo == 'IVA').sort((a, b) => b.retenido - a.retenido);

    let htmlRenta = '';
    codsRenta.forEach(c => {
        htmlRenta += `<tr>
            <td class="fw-bold">${c.codigo}</td>
            <td class="text-center">${c.veces}</td>
            <td class="text-end text-muted">${fmt.format(c.base)}</td>
            <td class="text-end fw-bold text-primary">${fmt.format(c.retenido)}</td>
        </tr>`;
    });
    document.getElementById('ret-codigos-renta-tbody').innerHTML = htmlRenta;

    let htmlIva = '';
    codsIva.forEach(c => {
        htmlIva += `<tr>
            <td class="fw-bold">${c.codigo}</td>
            <td class="text-center">${c.veces}</td>
            <td class="text-end text-muted">${fmt.format(c.base)}</td>
            <td class="text-end fw-bold text-info">${fmt.format(c.retenido)}</td>
        </tr>`;
    });
    document.getElementById('ret-codigos-iva-tbody').innerHTML = htmlIva;

    // Top 10 Agentes
    const agentes = Object.values(ana.agentes || {}).sort((a, b) => b.total - a.total).slice(0, 10);
    const maxAgente = agentes.length > 0 ? agentes[0].total : 0;

    let htmlAgentes = '';
    agentes.forEach(a => {
        const pctAgente = maxAgente > 0 ? (a.total / maxAgente * 100) : 0;
        htmlAgentes += `<tr>
            <td class="text-truncate" style="max-width: 200px;" title="${a.nombre}">${a.nombre}</td>
            <td>${a.ruc}</td>
            <td class="text-center">${a.docs}</td>
            <td class="text-end fw-bold text-success">${fmt.format(a.total)}</td>
            <td class="text-start align-middle">
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-success" style="width: ${pctAgente}%"></div>
                </div>
            </td>
        </tr>`;
    });
    document.getElementById('ret-agentes-tbody').innerHTML = htmlAgentes;
}

// ==========================================
// WIZARD UNIVERSAL DE CARGA SRI (3 PASOS)
// ==========================================

function abrirModalCargaSRI(tipo) {
    document.getElementById('wizard-tipo-doc').value = tipo;
    const titulos = {
        '104': 'Formulario 104 - Declaración de IVA',
        '103': 'Formulario 103 - Retenciones en la Fuente',
        'renta': 'Formulario 101/102 - Impuesto a la Renta',
        'retenciones_rec': 'Retenciones Recibidas Electrónicas (XML)',
        'ats': 'Anexo Transaccional Simplificado (ATS - XML)',
        'iess': 'Planilla IESS consolidada',
        'exa': 'Auditoría con EXA ERP'
    };
    const titleEl = document.getElementById('modalCargaSRITitle');
    if (titleEl) {
        const spanEl = titleEl.querySelector('span');
        if (spanEl) spanEl.innerText = titulos[tipo] || 'Carga de Documentos SRI';
    }

    const contMes = document.getElementById('contenedor-wizard-auto-mes');
    const contAnio = document.getElementById('contenedor-wizard-auto-anio');
    if (contMes && contAnio) {
        if (tipo === 'renta' || tipo === '101' || tipo === '102') {
            contMes.classList.add('d-none');
            contAnio.className = 'col-md-12';
        } else {
            contMes.classList.remove('d-none');
            contAnio.className = 'col-md-6';
        }
    }


    const manualCard = document.getElementById('wizard-card-manual');
    const autoCard = document.getElementById('wizard-card-auto');
    if (manualCard && autoCard) {
        const desc = manualCard.querySelector('p');
        if (tipo === 'retenciones_rec' || tipo === 'exa') {
            autoCard.classList.add('d-none');
            manualCard.classList.replace('col-md-6', 'col-md-12');
            if (desc) {
                if (tipo === 'exa') {
                    desc.innerHTML = 'Suba el archivo Excel (.xlsx o .xls) exportado de EXA ERP. El sistema procesará el detalle automáticamente.';
                } else {
                    desc.innerHTML = `Sube el ZIP consolidado o los XMLs locales. <br><div class="alert alert-warning mt-2 mb-0 py-2 small border-warning"><i class="bi bi-info-circle-fill"></i> <strong>Recomendación:</strong> Por la seguridad del reCAPTCHA del SRI, la descarga automática puede demorar. Le recomendamos usar la extensión de <strong>EXA-ATI</strong> en su navegador para descargar el ZIP del año directamente desde srienlinea en segundos, y luego arrastrar ese ZIP aquí.</div>`;
                }
            }
        } else {
            autoCard.classList.remove('d-none');
            manualCard.classList.replace('col-md-12', 'col-md-6');
            if (desc) {
                desc.innerHTML = 'Suba archivos PDF o XML locales. El sistema validará mes, RUC y aplicará prioridad automática a declaraciones <strong>Sustitutivas</strong>.';
            }
        }
    }

    const autoTitle = document.getElementById('wizard-auto-title');
    const autoUserLabel = document.getElementById('wizard-auto-user-label');
    const autoPassLabel = document.getElementById('wizard-auto-pass-label');
    const autoRucInput = document.getElementById('wizard-auto-ruc');
    const autoPassInput = document.getElementById('wizard-auto-password');
    const autoCardDesc = document.getElementById('wizard-auto-card-desc');
    const autoInfoDesc = document.getElementById('wizard-auto-info-desc');
    const liveTitle = document.getElementById('wizard-live-title');
    const livePlaceholderText = document.getElementById('wizard-live-placeholder-text');

    const contExa = document.getElementById('contenedor-exa-empresa');
    if (contExa) {
        if (tipo === 'exa') contExa.classList.remove('d-none');
        else contExa.classList.add('d-none');
    }

    if (tipo === 'exa') {
        if (autoTitle) autoTitle.innerText = 'Conexión Directa a EXA ERP';
        if (autoUserLabel) autoUserLabel.innerText = 'Usuario (RUC/CI)';
        if (autoPassLabel) autoPassLabel.innerText = 'Contraseña EXA';
        if (autoRucInput) autoRucInput.placeholder = 'Ej. 0703703413';
        if (autoPassInput) autoPassInput.placeholder = 'Clave de EXA';
        if (autoCardDesc) autoCardDesc.innerText = 'Conexión directa al portal EXA ERP. Ingrese credenciales y conecte su sesión.';
        if (autoInfoDesc) autoInfoDesc.innerText = 'El scraper nativo se conectará a exa.ofsercont.com con tus credenciales.';
        if (liveTitle) liveTitle.innerText = 'Vista de EXA ERP: Abriendo Sesión...';
        if (livePlaceholderText) livePlaceholderText.innerText = 'Conectando con el navegador a EXA...';
        
        const contSri = document.getElementById('contenedor-sri-periodo');
        const contIess = document.getElementById('contenedor-iess-periodo');
        const contExaPeriodo = document.getElementById('contenedor-exa-periodo');
        if (contSri) contSri.classList.add('d-none');
        if (contIess) contIess.classList.add('d-none');
        if (contExaPeriodo) contExaPeriodo.classList.remove('d-none');
    } else if (tipo === 'iess') {
        if (autoTitle) autoTitle.innerText = 'Conexión Directa al IESS';
        if (autoUserLabel) autoUserLabel.innerText = 'Cédula del Empleador (IESS)';
        if (autoPassLabel) autoPassLabel.innerText = 'Clave del IESS';
        if (autoRucInput) autoRucInput.placeholder = 'Cédula del empleador';
        if (autoPassInput) autoPassInput.placeholder = 'Clave del IESS';
        if (autoCardDesc) autoCardDesc.innerText = 'Conexión directa al portal de Empleadores del IESS vía Playwright interno. Descarga y sincroniza las planillas consolidadas automáticamente.';
        if (autoInfoDesc) autoInfoDesc.innerText = 'El scraper nativo se ejecutará localmente para navegar el portal del IESS, autenticarse con su cédula y clave, y descargar las planillas del período seleccionado.';
        if (liveTitle) liveTitle.innerText = 'Vista del IESS: Ejecutando Scraper Nativo - Local';
        if (livePlaceholderText) livePlaceholderText.innerText = 'Conectando con el navegador al portal oficial del IESS...';
        // Mostrar campos Desde/Hasta y ocultar Año/Mes del SRI
        const contSri = document.getElementById('contenedor-sri-periodo');
        const contIess = document.getElementById('contenedor-iess-periodo');
        const contExaPeriodo = document.getElementById('contenedor-exa-periodo');
        if (contSri) contSri.classList.add('d-none');
        if (contIess) contIess.classList.remove('d-none');
        if (contExaPeriodo) contExaPeriodo.classList.add('d-none');
        // Pre-llenar con el año actual completo
        const anioActual = new Date().getFullYear();
        const desdeInput = document.getElementById('iess-periodo-desde');
        const hastaInput = document.getElementById('iess-periodo-hasta');
        if (desdeInput && !desdeInput.value) desdeInput.value = `${anioActual}-01`;
        if (hastaInput && !hastaInput.value) hastaInput.value = `${anioActual}-12`;
    } else {
        if (autoTitle) autoTitle.innerText = 'Conexión Directa al SRI en Línea';
        if (autoUserLabel) autoUserLabel.innerText = 'RUC del Contribuyente';
        if (autoPassLabel) autoPassLabel.innerText = 'Contraseña SRI en Línea';
        if (autoRucInput) autoRucInput.placeholder = 'Ej. 0703703413001';
        if (autoPassInput) autoPassInput.placeholder = 'Clave del SRI';
        if (autoCardDesc) autoCardDesc.innerText = 'Conexión directa al portal SRI en Línea vía Playwright interno. Descarga y sincroniza las declaraciones del mes automáticamente.';
        if (autoInfoDesc) autoInfoDesc.innerText = 'El scraper nativo de este proyecto se ejecutará localmente para navegar, autenticarse y descargar los archivos del mes sin intermediarios.';
        if (liveTitle) liveTitle.innerText = 'Vista del SRI: Ejecutando Scraper Nativo - Local';
        if (livePlaceholderText) livePlaceholderText.innerText = 'Conectando con el navegador al portal oficial del SRI...';
        // Mostrar campos Año/Mes del SRI y ocultar IESS
        const contSri = document.getElementById('contenedor-sri-periodo');
        const contIess = document.getElementById('contenedor-iess-periodo');
        const contExaPeriodo = document.getElementById('contenedor-exa-periodo');
        if (contSri) contSri.classList.remove('d-none');
        if (contIess) contIess.classList.add('d-none');
        if (contExaPeriodo) contExaPeriodo.classList.add('d-none');
    }

    // Reset a Paso 1 inmediatamente (por si el modal ya estaba abierto o en transición)
    mostrarPasoWizard(1);

    const rucActual = document.querySelector('.ruc')?.innerText?.trim() || '';
    const rucInput = document.getElementById('wizard-auto-ruc');
    if (rucInput && tipo !== 'iess' && rucActual && rucActual !== '0000000000001') {
        rucInput.value = rucActual;
    } else if (rucInput && tipo === 'iess') {
        rucInput.value = ''; // Para IESS se ingresa cédula, no RUC
    }

    const modalEl = document.getElementById('modalCargaSRI');
    // Usar getOrCreateInstance para evitar conflicto si el modal ya tiene una instancia Bootstrap
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    // Reset a Paso 1 directamente e inmediatamente antes de mostrar
    mostrarPasoWizard(1);
    modal.show();
}

function mostrarPasoWizard(paso, submodo = null) {
    // Ocultar todos los panes
    document.querySelectorAll('.step-pane').forEach(el => el.classList.add('d-none'));

    // Reset indicadores
    [1, 2, 3].forEach(p => {
        const ind = document.getElementById(`step-indicator-${p}`);
        if (ind) {
            ind.classList.remove('text-primary', 'text-success');
            ind.classList.add('text-muted');
        }
    });

    if (paso === 1) {
        document.getElementById('wizard-step-1').classList.remove('d-none');
        document.getElementById('step-indicator-1').classList.add('text-primary');
        document.getElementById('step-indicator-1').classList.remove('text-muted');
    } else if (paso === 2) {
        if (submodo === 'manual') {
            document.getElementById('wizard-step-2-manual').classList.remove('d-none');
        } else {
            document.getElementById('wizard-step-2-auto').classList.remove('d-none');
        }
        document.getElementById('step-indicator-2').classList.add('text-primary');
        document.getElementById('step-indicator-2').classList.remove('text-muted');
    } else if (paso === 3) {
        document.getElementById('wizard-step-3').classList.remove('d-none');
        document.getElementById('step-indicator-3').classList.add('text-success');
        document.getElementById('step-indicator-3').classList.remove('text-muted');
    }
}

function seleccionarModalidadWizard(modo) {
    mostrarPasoWizard(2, modo);
}

function volverAPaso1() {
    mostrarPasoWizard(1);
}

document.addEventListener('DOMContentLoaded', () => {
    const manualFilesInput = document.getElementById('wizard-manual-files');
    if (manualFilesInput) {
        manualFilesInput.addEventListener('change', function () {
            try {
                if (!this.files.length) return;
                const tipo = document.getElementById('wizard-tipo-doc').value;
                
                // Lectura defensiva de año y mes manuales
                const manualAnioEl = document.getElementById('wizard-manual-anio');
                const manualMesEl = document.getElementById('wizard-manual-mes');
                const anio = manualAnioEl ? manualAnioEl.value : '';
                const mes = manualMesEl ? manualMesEl.value : '';
                let mesNombre = '';
                if (manualMesEl && manualMesEl.selectedIndex >= 0) {
                    mesNombre = manualMesEl.options[manualMesEl.selectedIndex].text;
                }

                const listEl = document.getElementById('wizard-manual-files-list');
                if (listEl) {
                    listEl.innerHTML = `<span class="badge bg-primary p-2"><i class="spinner-border spinner-border-sm me-1"></i> Validando ${this.files.length} archivo(s)...</span>`;
                }

                if (tipo === 'ats' && typeof handleMultipleFiles === 'function') {
                    handleMultipleFiles(this.files);
                }

                let uploads = Array.from(this.files).map(file => {
                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('tipo', tipo);
                    return fetch(window.BASE_URL + 'ajax/upload.php', { method: 'POST', body: formData })
                        .then(r => {
                            if (!r.ok) throw new Error('HTTP error ' + r.status);
                            return r.json();
                        });
                });

                Promise.all(uploads).then(results => {
                    try {
                        let sustitutivaAplicada = false;
                        results.forEach(res => {
                            if (res && res.exa_data) {
                                window.exaDataArray = res.exa_data;
                                if (typeof renderExaData === 'function') {
                                    renderExaData(res.exa_data);
                                }
                            }
                            if (res && res.ruc_detectado) {
                                const rucEl = document.querySelector('.ruc');
                                if (rucEl) rucEl.innerText = res.ruc_detectado;
                            }
                            if (res && res.nombre_detectado) {
                                const nombreEl = document.querySelector('.nombre');
                                if (nombreEl) nombreEl.innerText = res.nombre_detectado;
                            }
                            if (res && (res.reemplazo_sustitutiva || res.tipo_declaracion === 'SUSTITUTIVA')) {
                                sustitutivaAplicada = true;
                            }
                        });

                        // Llenar datos de informe (Paso 3) con validación segura de mesNombre
                        let nombreMesLimpio = "Periodo";
                        if (typeof mesNombre === 'string' && mesNombre.trim() !== '') {
                            nombreMesLimpio = mesNombre.split(' ')[0];
                        }
                        let realPeriodo = `${nombreMesLimpio} ${anio || ''}`;
                        
                        if (tipo === 'ats' && window.atsData && window.atsData.periodo && window.atsData.periodo !== 'Desconocido') {
                            realPeriodo = window.atsData.periodo;
                        } else if (results.length > 0 && results[0] && results[0].nombre_mes) {
                            const rMes = results[0].nombre_mes.split(' ')[0];
                            const rAnio = results[0].anio || anio;
                            realPeriodo = `${rMes} ${rAnio}`;
                        }

                        const informePeriodoEl = document.getElementById('informe-periodo');
                        if (informePeriodoEl) informePeriodoEl.innerText = realPeriodo;

                        const badgeTipo = document.getElementById('informe-badge-tipo');
                        if (badgeTipo) {
                            badgeTipo.innerText = sustitutivaAplicada ? 'SUSTITUTIVA' : 'ORIGINAL';
                            badgeTipo.className = sustitutivaAplicada ? 'badge bg-warning text-dark px-3 py-2 mt-1' : 'badge bg-primary px-3 py-2 mt-1';
                        }

                        const alerta = document.getElementById('informe-alerta-sustitutiva');
                        if (alerta) {
                            if (sustitutivaAplicada) alerta.classList.remove('d-none');
                            else alerta.classList.add('d-none');
                        }

                        if (listEl) listEl.innerHTML = ''; // Limpiar spinner de carga

                        // Llenar caja de archivos cargados (Paso 3)
                        const descBox = document.getElementById('informe-descargas-box');
                        const listArchivos = document.getElementById('lista-archivos-pdf');
                        if (listArchivos && descBox) {
                            descBox.classList.remove('d-none');
                            listArchivos.innerHTML = '';
                            Array.from(this.files).forEach(f => {
                                let icono = f.name.toLowerCase().endsWith('.pdf') ? 'bi-file-earmark-pdf-fill text-danger' :
                                           (f.name.toLowerCase().endsWith('.xls') || f.name.toLowerCase().endsWith('.xlsx') ? 'bi-file-earmark-excel-fill text-success' : 'bi-file-earmark-code-fill text-primary');
                                listArchivos.innerHTML += `
                                <div class="btn-group shadow-sm mb-1 w-100">
                                    <button type="button" class="btn btn-success btn-sm d-flex align-items-center shadow px-3 py-2 fw-bold w-100 justify-content-center text-truncate" title="${f.name}">
                                        <i class="bi ${icono} fs-5 me-2"></i>
                                        <span class="text-truncate">Cargado Manual: ${f.name}</span>
                                    </button>
                                </div>`;
                            });
                        }
                    } catch (innerErr) {
                        console.error('Error interno en procesamiento de respuesta:', innerErr);
                    }

                    mostrarPasoWizard(3);
                    window.clearEstimados();
                    updateState();
                }).catch(err => {
                    console.error('Error en carga manual wizard (uploads):', err);
                    if (listEl) {
                        listEl.innerHTML = `<span class="text-danger"><i class="bi bi-x-circle-fill"></i> Error al validar: ${err.message}</span>`;
                    }
                });
            } catch (globalErr) {
                console.error('Error global en evento manual wizard:', globalErr);
            }
        });
    }
});

function ejecutarScrapeNativo() {
    const ruc = document.getElementById('wizard-auto-ruc').value.trim();
    const password = document.getElementById('wizard-auto-password').value.trim();
    const tipo = document.getElementById('wizard-tipo-doc').value;
    const esRenta = (tipo === 'renta' || tipo === '101' || tipo === '102');

    let anio, mes, mesNombre, mesDesdeExa, mesHastaExa;
    if (tipo === 'iess') {
        const pDesde = document.getElementById('iess-periodo-desde').value.trim();
        const pHasta = document.getElementById('iess-periodo-hasta').value.trim();
        // Usamos anio para guardar el rango completo a nivel de visualización del frontend
        anio = pDesde + '_' + pHasta;
        mes = 'rango';
        mesNombre = `Desde ${pDesde} Hasta ${pHasta}`;
    } else if (tipo === 'exa') {
        anio = document.getElementById('wizard-exa-anio').value;
        mesDesdeExa = document.getElementById('wizard-exa-mes-desde').value;
        mesHastaExa = document.getElementById('wizard-exa-mes-hasta').value;
        mes = 'rango';
        mesNombre = `Mes ${mesDesdeExa} a ${mesHastaExa}`;
    } else {
        anio = document.getElementById('wizard-auto-anio').value;
        mes = esRenta ? '0' : document.getElementById('wizard-auto-mes').value;
        mesNombre = esRenta ? 'Año Fiscal Completo' : document.getElementById('wizard-auto-mes').options[document.getElementById('wizard-auto-mes').selectedIndex].text;
    }

    if (!ruc || !password) {
        alert('Por favor ingrese el usuario (RUC o Cédula) y la contraseña.');
        return;
    }

    let omitir_meses = [];
    // Deshabilitado: SIEMPRE enviamos omitir_meses vacío cuando es 'todos', 'sem1' o 'sem2'
    // para forzar al scraper a re-procesar/sobreescribir todos los meses seleccionados,
    // ignorando los que ya existan visualmente en los chips.
    if (false && (mes === 'todos' || mes === 'sem1' || mes === 'sem2')) {
        const mesesNombresMap = { 'ENERO': 1, 'FEBRERO': 2, 'MARZO': 3, 'ABRIL': 4, 'MAYO': 5, 'JUNIO': 6, 'JULIO': 7, 'AGOSTO': 8, 'SEPTIEMBRE': 9, 'OCTUBRE': 10, 'NOVIEMBRE': 11, 'DICIEMBRE': 12 };
        const chipsEl = document.getElementById(tipo === '104' ? 'chips-104' : 'chips-103');
        if (chipsEl) {
            chipsEl.querySelectorAll('.chip').forEach(c => {
                const txt = c.innerText.trim().toUpperCase();
                if (mesesNombresMap[txt]) omitir_meses.push(mesesNombresMap[txt]);
            });
        }
    }

    function updateAutoScrapeSteps(currentStep) {
        const badge = document.getElementById('wizard-auto-status-step-badge');
        if (badge) badge.innerText = `Paso ${Math.min(currentStep, 5)} de 5`;

        for (let i = 1; i <= 5; i++) {
            const item = document.getElementById(`auto-step-item-${i}`);
            const iconBox = document.getElementById(`auto-step-icon-${i}`);
            const statusEl = document.getElementById(`auto-step-status-${i}`);
            if (!item || !iconBox || !statusEl) continue;

            if (i < currentStep || currentStep >= 6) {
                iconBox.className = 'step-icon d-flex align-items-center justify-content-center rounded-circle bg-success text-white shadow-sm mb-1 transition-all';
                iconBox.innerHTML = '<i class="bi bi-check-lg fw-bold fs-6"></i>';
                statusEl.className = 'text-success fw-bold';
                statusEl.style.fontSize = '0.68rem';
                statusEl.innerHTML = '✔ Listo';
            } else if (i === currentStep) {
                iconBox.className = 'step-icon d-flex align-items-center justify-content-center rounded-circle bg-info text-dark shadow-sm mb-1 transition-all';
                iconBox.innerHTML = '<span class="spinner-border spinner-border-sm text-dark" role="status" style="width:1rem;height:1rem;"></span>';
                statusEl.className = 'text-info fw-bold';
                statusEl.style.fontSize = '0.68rem';
                statusEl.innerHTML = 'En curso...';
            } else {
                iconBox.className = 'step-icon d-flex align-items-center justify-content-center rounded-circle bg-dark border text-muted shadow-sm mb-1 transition-all';
                iconBox.style.borderColor = '#475569';
                statusEl.className = 'text-muted';
                statusEl.style.fontSize = '0.65rem';
                statusEl.innerHTML = 'Pendiente';
            }
        }
    }

    const jobId = 'job_' + Date.now() + '_' + Math.floor(Math.random() * 10000);
    const spinnerEl = document.getElementById('wizard-auto-spinner');
    const btnEl = document.getElementById('btn-iniciar-scrape');
    spinnerEl.classList.remove('d-none');
    btnEl.disabled = true;

    if (tipo === 'retenciones_rec' || tipo === 'retenciones') {
        const t2 = document.getElementById('auto-step-title-2'); if (t2) t2.innerText = '2. Módulo';
        const t3 = document.getElementById('auto-step-title-3'); if (t3) t3.innerText = '3. Consulta';
        const t4 = document.getElementById('auto-step-title-4'); if (t4) t4.innerText = '4. Búsqueda';
        const t5 = document.getElementById('auto-step-title-5'); if (t5) t5.innerText = '5. XMLs';
    } else if (tipo === 'iess') {
        const t2 = document.getElementById('auto-step-title-2'); if (t2) t2.innerText = '2. Login';
        const t3 = document.getElementById('auto-step-title-3'); if (t3) t3.innerText = '3. Planillas';
        const t4 = document.getElementById('auto-step-title-4'); if (t4) t4.innerText = '4. Período';
        const t5 = document.getElementById('auto-step-title-5'); if (t5) t5.innerText = '5. Descarga';
    } else {
        const t2 = document.getElementById('auto-step-title-2'); if (t2) t2.innerText = '2. Obligación';
        const t3 = document.getElementById('auto-step-title-3'); if (t3) t3.innerText = '3. Período';
        const t4 = document.getElementById('auto-step-title-4'); if (t4) t4.innerText = '4. Prioridad';
        const t5 = document.getElementById('auto-step-title-5'); if (t5) t5.innerText = '5. Descarga';
    }
    updateAutoScrapeSteps(1);

    const titleEl = document.getElementById('wizard-auto-status-title');
    const descEl = document.getElementById('wizard-auto-status-desc');
    if (titleEl) {
        titleEl.innerText = (tipo === 'iess') ? 'Conectando con www.iess.gob.ec...' : 'Conectando con srienlinea.sri.gob.ec...';
    }
    if (descEl) descEl.innerText = 'Abriendo navegador y preparando motor de descarga...';

    const liveImg = document.getElementById('wizard-live-img');
    const livePh = document.getElementById('wizard-live-placeholder');
    if (liveImg && livePh) {
        liveImg.style.display = 'none';
        livePh.style.display = 'block';
    }

    const progressTimer = setInterval(() => {
        fetch(window.BASE_URL + `uploads/sri_auto/${jobId}/progress.json?t=${Date.now()}`)
            .then(r => r.json())
            .then(p => {
                if (p && p.title && titleEl) titleEl.innerText = p.title;
                if (p && p.desc && descEl) descEl.innerText = p.desc;
                if (p && p.step) updateAutoScrapeSteps(p.step);
            }).catch(() => { });

        if (liveImg && livePh) {
            const imgUrl = `uploads/sri_auto/${jobId}/live_view.jpg?t=${Date.now()}`;
            const tempImg = new Image();
            tempImg.onload = () => {
                liveImg.src = imgUrl;
                liveImg.style.display = 'block';
                livePh.style.display = 'none';
            };
            tempImg.src = imgUrl;
        }
    }, 700);

    const targetUrl = (tipo === 'exa') ? window.BASE_URL + 'ajax/run_exa_scraper.php' : window.BASE_URL + 'ajax/modal_sri_auto.php';
    const empresa = document.getElementById('wizard-auto-empresa')?.value || '';
    
    let bodyData;
    if (tipo === 'exa') {
        bodyData = { username: ruc, password: password, empresa: empresa, job_id: jobId, anio: anio, mes_desde: mesDesdeExa, mes_hasta: mesHastaExa };
    } else {
        bodyData = { ruc, password, anio, mes, tipo_doc: tipo, omitir_meses, job_id: jobId };
    }

    fetch(targetUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(bodyData)
    })
        .then(res => {
            if (!res.ok) throw new Error('HTTP error ' + res.status);
            return res.json();
        })
        .then(data => {
            try {
                clearInterval(progressTimer);
                updateAutoScrapeSteps(6);
                setTimeout(() => {
                    if (spinnerEl) spinnerEl.classList.add('d-none');
                }, 800);
                if (btnEl) btnEl.disabled = false;

                if (tipo === 'exa') {
                    if (data.success) {
                        // Success for EXA
                        const infPer = document.getElementById('informe-periodo');
                        if (infPer) infPer.innerText = 'Sesión Iniciada';
                        const badgeTipo = document.getElementById('informe-badge-tipo');
                        if (badgeTipo) {
                            badgeTipo.innerText = 'CONECTADO';
                            badgeTipo.className = 'badge bg-success px-3 py-2 mt-1';
                        }
                        
                        if (data.exa_data && data.exa_data.length > 0) {
                            window.exaDataArray = data.exa_data;
                            if (typeof renderExaData === 'function') {
                                renderExaData(data.exa_data);
                            }
                            if (data.warnings && data.warnings.length > 0) {
                                console.warn("[SCRAPER WARNINGS]:", data.warnings);
                            }
                        }
                        const descBox = document.getElementById('informe-descargas-box');
                        const listEl = document.getElementById('lista-archivos-pdf');
                        if (data.archivos && data.archivos.length > 0) {
                            if (descBox) descBox.classList.remove('d-none');
                            if (listEl) {
                                listEl.innerHTML = '';
                                data.archivos.forEach(f => {
                                    let pathUrl = `uploads/sri_auto/${data.job_id || jobId}/${f}`;
                                    listEl.innerHTML += `
                                    <a href="${pathUrl}" download="${f}" class="btn btn-success btn-sm d-flex align-items-center shadow px-3 py-2 fw-bold w-100 justify-content-center mb-1 text-truncate" title="${f}">
                                        <i class="bi bi-file-earmark-excel-fill fs-5 me-2"></i>
                                        <span class="text-truncate" style="max-width: 85%;">Descargar Reporte EXA (${f})</span>
                                    </a>`;
                                });
                            }
                        } else {
                            if (descBox) descBox.classList.add('d-none');
                        }
                        mostrarPasoWizard(3);
                    } else {
                        alert("Error: " + data.error + "\n" + (data.output || ''));
                        mostrarPasoWizard(2, 'auto');
                    }
                    return;
                }

                if (data.status === 'ok') {
                    let sustitutivaAplicada = false;
                    try {
                        sustitutivaAplicada = data.reemplazo_sustitutiva || data.sustitutiva_aplicada;
                    } catch(e){}

                    let realPeriodo = "Periodo";
                    try {
                        let nombreMesLimpio = "Periodo";
                        if (typeof mesNombre === 'string' && mesNombre.trim() !== '') {
                            nombreMesLimpio = mesNombre.split(' ')[0];
                        }
                        realPeriodo = `${nombreMesLimpio} ${anio || ''}`;
                    } catch(e){}

                    const informePeriodoEl = document.getElementById('informe-periodo');
                    if (informePeriodoEl) informePeriodoEl.innerText = realPeriodo;

                    const badgeTipo = document.getElementById('informe-badge-tipo');
                    if (badgeTipo) {
                        badgeTipo.innerText = sustitutivaAplicada ? 'SUSTITUTIVA' : 'ORIGINAL';
                        badgeTipo.className = sustitutivaAplicada ? 'badge bg-warning text-dark px-3 py-2 mt-1' : 'badge bg-primary px-3 py-2 mt-1';
                    }

                    const alerta = document.getElementById('informe-alerta-sustitutiva');
                    if (alerta) {
                        if (sustitutivaAplicada) alerta.classList.remove('d-none');
                        else alerta.classList.add('d-none');
                    }

                    const descBox = document.getElementById('informe-descargas-box');
                    const listEl = document.getElementById('lista-archivos-pdf');
                    if (listEl && descBox) {
                        listEl.innerHTML = '';
                        if (data.archivos && data.archivos.length > 0) {
                            descBox.classList.remove('d-none');
                            data.archivos.forEach(f => {
                                if (!f) return;
                                let pathUrl = `uploads/sri_auto/${data.job_id || jobId}/${f}`;
                                if (f.endsWith('.zip')) {
                                    listEl.innerHTML += `
                                    <a href="${pathUrl}" download="${f}" class="btn btn-success btn-sm d-flex align-items-center shadow px-3 py-2 fw-bold w-100 justify-content-center mb-1 text-truncate" title="${f}">
                                        <i class="bi bi-file-earmark-zip-fill fs-5 me-2"></i>
                                        <span class="text-truncate" style="max-width: 85%;">Descargar Paquete Consolidado ZIP (${f})</span>
                                    </a>`;
                                } else {
                                    let icono = f.endsWith('.pdf') ? 'bi-file-earmark-pdf-fill text-danger' : 'bi-file-earmark-code-fill text-primary';
                                    listEl.innerHTML += `
                                    <div class="btn-group shadow-sm mb-1 w-100">
                                        <button type="button" class="btn btn-outline-dark btn-sm d-flex align-items-center bg-white px-3 py-2 text-truncate" style="max-width: 70%;" onclick="abrirVisorArchivo('${pathUrl}', '${f}')" title="${f}">
                                            <i class="bi ${icono} fs-5 me-2"></i>
                                            <span class="text-truncate">Ver en Modal (${f})</span>
                                        </button>
                                        <a href="${pathUrl}" download="${f}" class="btn btn-primary btn-sm d-flex align-items-center px-3 py-2 fw-bold" title="Descargar archivo a tu computadora">
                                            <i class="bi bi-download me-1"></i> Descargar
                                        </a>
                                    </div>`;
                                }
                            });
                        } else {
                            descBox.classList.remove('d-none');
                            listEl.innerHTML = '<span class="text-muted">No se obtuvieron archivos adicionales.</span>';
                        }

                        // Eliminar alertas anteriores para evitar duplicados
                        if (descBox) {
                            const oldAlerts = descBox.querySelectorAll('.alert-success, .alert-warning');
                            oldAlerts.forEach(a => a.remove());
                        }

                        // Mostrar alertas de archivos migrados o pendientes de revisión manual
                        if (data.migrated && data.migrated.length > 0) {
                            let migratedHtml = `
                            <div class="alert alert-success mt-2 mb-0 py-2 px-3 small border-0 shadow-sm" style="background-color: #f0fdf4; border-left: 4px solid #16a34a !important;">
                                <div class="fw-bold text-success mb-1" style="font-size: 0.76rem;"><i class="bi bi-check-circle-fill me-1"></i> Archivos Huérfanos Migrados Correctamente:</div>
                                <ul class="mb-0 ps-3 text-secondary" style="font-size: 0.72rem; list-style-type: square;">
                            `;
                            data.migrated.forEach(m => {
                                migratedHtml += `<li>Se renombró <code>${m.old}</code> a <code>${m.new}</code> (RUC: ${m.ruc})</li>`;
                            });
                            migratedHtml += `</ul></div>`;
                            listEl.insertAdjacentHTML('afterend', migratedHtml);
                        }

                        if (data.unmigrated && data.unmigrated.length > 0) {
                            let unmigratedHtml = `
                            <div class="alert alert-warning mt-2 mb-0 py-2 px-3 small border-0 shadow-sm" style="background-color: #fffbeb; border-left: 4px solid #d97706 !important;">
                                <div class="fw-bold text-warning mb-1" style="font-size: 0.76rem;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Archivos Huérfanos Pendientes de Revisión:</div>
                                <div class="text-muted mb-1" style="font-size: 0.7rem;">Los siguientes archivos no se pudieron migrar (RUC ilegible o formato corrupto) y se ocultaron por seguridad:</div>
                                <ul class="mb-0 ps-3 text-danger" style="font-size: 0.72rem; list-style-type: square;">
                            `;
                            data.unmigrated.forEach(f => {
                                unmigratedHtml += `<li><code>${f}</code></li>`;
                            });
                            unmigratedHtml += `</ul></div>`;
                            listEl.insertAdjacentHTML('afterend', unmigratedHtml);
                        }
                    }

                    if (tipo === 'ats' && data.archivos && typeof handleMultipleFiles === 'function') {
                        const atsFiles = data.archivos.filter(f => f && (f.toLowerCase().endsWith('.xml') || f.toLowerCase().endsWith('.zip')));
                        if (atsFiles.length > 0) {
                            if (!window.atsData) window.atsData = { ventas: [], compras: [], cargado: false, periodo: 'Múltiples', meses: {} };

                            Promise.all(atsFiles.map(f => fetch(window.BASE_URL + `uploads/sri_auto/${data.job_id || jobId}/${encodeURIComponent(f)}`)
                                .then(r => r.blob())
                                .then(blob => new File([blob], f, { type: f.endsWith('.zip') ? 'application/zip' : 'application/xml' }))
                            )).then(files => {
                                return handleMultipleFiles(files);
                            }).then(() => {
                                window.atsData.cargado = true;
                                if (window.xmlLoadingMsg) {
                                    window.xmlLoadingMsg.style.background = '#27ae60';
                                    window.xmlLoadingMsg.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i> ATS CARGADO EXITOSAMENTE';
                                    setTimeout(() => { if (window.xmlLoadingMsg) window.xmlLoadingMsg.remove(); }, 2500);
                                }
                                const pContainer = document.getElementById('xml-progress-container');
                                const sText = document.getElementById('xml-status');
                                if (pContainer) pContainer.classList.add('d-none');
                                if (sText) sText.classList.add('d-none');
                                if (typeof renderAtsUI === 'function') renderAtsUI();
                                updateState();
                            }).catch(() => { });
                        }
                    }

                    // Inyectar dinámicamente los chips de los meses procesados en la UI principal
                    if (data.meses_procesados && data.meses_procesados.length > 0) {
                        const mNombres = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                        const targetChipsId = (tipo === '104') ? 'chips-104' : ((tipo === '103') ? 'chips-103' : null);
                        if (targetChipsId) {
                            const cEl = document.getElementById(targetChipsId);
                            if (cEl) {
                                data.meses_procesados.forEach(mNum => {
                                    if (mNum >= 1 && mNum <= 12) {
                                        const mStr = mNombres[mNum - 1];
                                        let exists = false;
                                        cEl.querySelectorAll('.chip').forEach(c => {
                                            if (c.getAttribute('data-mes') === mStr.toUpperCase()) exists = true;
                                            else if (c.innerText.replace(/\[.*?\]/g, '').trim().toUpperCase() === mStr.toUpperCase()) exists = true;
                                        });
                                        if (!exists) {
                                            cEl.insertAdjacentHTML('beforeend', `<span class="chip" data-mes="${mStr.toUpperCase()}"><i class="bi bi-check-circle-fill text-success"></i> ${mStr}</span>`);
                                        }
                                    }
                                });
                            }
                        }
                    }

                    mostrarPasoWizard(3);
                    window.clearEstimados();
                    updateState();
                } else {
                    alert('Error en sincronización en línea: ' + (data.error || 'Problema de conexión con SRI en Línea.'));
                }
            } catch (errInner) {
                console.error("Error procesando callback exitoso de scraper:", errInner);
                // Forzar el paso 3 incluso si hubo un error en la inyección de datos visuales
                mostrarPasoWizard(3);
            }
        })
        .catch(err => {
            if (spinnerEl) spinnerEl.classList.add('d-none');
            if (btnEl) btnEl.disabled = false;
            alert('Error de conexión con el servidor: ' + err.message);
        });
}

function confirmarYCargarCalculo() {
    updateState();
    const modalEl = document.getElementById('modalCargaSRI');
    const modal = bootstrap.Modal.getInstance(modalEl);
    const tipo = document.getElementById('wizard-tipo-doc').value;

    // Esperar a que el modal termine de cerrarse antes de navegar al tab
    // (evita conflictos con la animación de fade de Bootstrap)
    function navegarAlTab() {
        if (tipo === 'renta' || tipo === '101' || tipo === '102') {
            const f101Tab = document.getElementById('f101-tab');
            if (f101Tab) f101Tab.click();
        } else if (tipo === 'ats') {
            const xmlTab = document.getElementById('xml-tab');
            if (xmlTab) xmlTab.click();
            if (typeof renderAtsUI === 'function') renderAtsUI();
        } else if (tipo === 'iess') {
            const iessTab = document.getElementById('iess-tab');
            if (iessTab) iessTab.click();
        } else if (tipo === 'exa') {
            const exaTab = document.getElementById('exa-detalle-tab');
            if (exaTab) exaTab.click();
            renderExaData(window.exaDataArray);
        } else {
            const tablaTab = document.getElementById('tabla-tab');
            if (tablaTab) tablaTab.click();
        }
    }

    if (modal) {
        // Registrar navegación al completarse el cierre del modal
        const onHiddenHandler = function() {
            modalEl.removeEventListener('hidden.bs.modal', onHiddenHandler);
            navegarAlTab();
        };
        modalEl.addEventListener('hidden.bs.modal', onHiddenHandler);
        modal.hide();
    } else {
        // Modal no estaba abierto, navegar directamente
        navegarAlTab();
    }
}

function abrirVisorArchivo(url, nombre) {
    document.getElementById('visor-titulo').innerText = nombre;
    document.getElementById('iframe-visor-archivo').src = url;
    document.getElementById('btn-visor-descargar').href = url;
    const visorEl = document.getElementById('modalVisorArchivo');
    const visorModal = bootstrap.Modal.getOrCreateInstance(visorEl);
    visorModal.show();
}

function renderExaData(dataArray) {
    const tbody = document.querySelector('#tabla-exa-detalle tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (!dataArray || dataArray.length === 0) {
        tbody.innerHTML = '<tr><td colspan="32" class="text-center text-muted py-4">No hay datos extraídos de EXA para este período.</td></tr>';
        document.getElementById('chip-exa-estado').innerHTML = '<i class="bi bi-x-circle"></i> Datos EXA: Vacío';
        document.getElementById('chip-exa-estado').className = 'badge bg-secondary text-white me-1';
        return;
    }

    // Default Fallbacks
    let colMes = 0;
    let colVC = 22;
    let colTotalRet = 29;
    let colTotal103 = 31;

    // Arrays to hold column indices dynamically
    let colsVentas = [];      // Will hold 11 columns
    let colsCompras = [];     // Will hold 10 columns
    let colsRetenciones = []; // Will hold 7 columns
    let colF103Casilleros = []; // Will hold dynamic Form 103 Casilleros

    let colVentasStart = -1;
    let colComprasStart = -1;
    let colRetStart = -1;
    let colF103Start = -1;

    // Scan Row 1 for FORMULARIO 103 section start
    if (dataArray.length >= 2) {
        for (let c = 0; c < dataArray[0].length; c++) {
            const val = String(dataArray[0][c] || '').toUpperCase().trim();
            if (val.includes('FORMULARIO 103')) {
                colF103Start = c;
                break;
            }
        }
    }

    // Scan Row 2 for sub-sections
    if (dataArray.length >= 2) {
        for (let c = 0; c < dataArray[1].length; c++) {
            const val = String(dataArray[1][c] || '').toUpperCase().trim();
            if (val.includes('VENTAS')) colVentasStart = c;
            else if (val.includes('COMPRAS')) colComprasStart = c;
            else if (val.includes('RETENCIONES IVA') || val.includes('RETENCIONES')) colRetStart = c;
            else if (val === 'V - C' || val === 'V-C') colVC = c;
        }
    }

    // Map Ventas (from colVentasStart to colComprasStart - 1)
    if (colVentasStart !== -1 && colComprasStart !== -1) {
        for (let c = colVentasStart; c < colComprasStart; c++) {
            colsVentas.push(c);
        }
    } else {
        // Fallback
        for (let c = 1; c <= 11; c++) colsVentas.push(c);
    }

    // Map Compras (from colComprasStart to colVC)
    if (colComprasStart !== -1 && colVC !== -1) {
        for (let c = colComprasStart; c < colVC; c++) {
            colsCompras.push(c);
        }
    } else {
        // Fallback
        for (let c = 12; c <= 21; c++) colsCompras.push(c);
    }

    // Map Retenciones (from colRetStart to colF103Start or colTotalRet)
    if (colRetStart !== -1) {
        let retEnd = (colF103Start !== -1) ? colF103Start : dataArray[1].length;
        for (let c = colRetStart; c < retEnd; c++) {
            colsRetenciones.push(c);
            if (String(dataArray[1][c] || '').toUpperCase().trim().includes('TOTAL')) {
                colTotalRet = c;
            }
        }
    } else {
        // Fallback
        for (let c = 23; c <= 29; c++) colsRetenciones.push(c);
    }

    // Map Form 103
    if (colF103Start !== -1 && dataArray.length >= 2) {
        for (let c = colF103Start; c < dataArray[1].length; c++) {
            const val = String(dataArray[1][c] || '').trim();
            if (/^\d+$/.test(val)) {
                colF103Casilleros.push({ col: c, code: val });
            } else if (val.toUpperCase().includes('TOTAL')) {
                colTotal103 = c;
            }
        }
    }

    // Dynamic header rendering in JavaScript
    const theadEl = document.querySelector('#tabla-exa-detalle thead');
    if (theadEl) {
        let totalF104Cols = colsVentas.length + colsCompras.length + 1 + colsRetenciones.length;
        let totalF103Cols = colF103Casilleros.length + (colTotal103 !== -1 ? 1 : 0);
        
        let headerHtml = `
            <!-- NIVEL 1: FORMULARIO 104 y FORMULARIO 103 -->
            <tr>
                <th rowspan="3" class="text-center align-middle sticky-col" style="background-color: #4f46e5; color: white;">MES</th>
                <th colspan="${totalF104Cols}" class="text-center bg-primary text-white py-2">FORMULARIO 104</th>
                <th colspan="${totalF103Cols}" class="text-center bg-secondary text-white py-2" id="header-f103-title">FORMULARIO 103</th>
            </tr>
            <!-- NIVEL 2: VENTAS, COMPRAS, V - C, RETENCIONES IVA -->
            <tr>
                <th colspan="${colsVentas.length}" class="text-center th-azul py-2">VENTAS</th>
                <th colspan="${colsCompras.length}" class="text-center th-verde py-2">COMPRAS</th>
                <th rowspan="2" class="text-center align-middle th-azul py-2">V - C</th>
                <th colspan="${colsRetenciones.length}" class="text-center th-naranja py-2">RETENCIONES IVA</th>
                <th colspan="${totalF103Cols}" class="text-center th-gris py-2" id="header-f103-subtitle">FORMULARIO 103</th>
            </tr>
            <!-- NIVEL 3: DETALLE -->
            <tr id="header-row-details">
        `;
        
        // Ventas Subcolumns
        colsVentas.forEach(c => {
            let label = dataArray[2][c] || (String(dataArray[1][c]).toUpperCase().includes('TOTAL') ? 'TOTAL' : '-');
            headerHtml += `<th class="text-center small th-azul">${label}</th>`;
        });
        
        // Compras Subcolumns
        colsCompras.forEach(c => {
            let label = dataArray[2][c] || (String(dataArray[1][c]).toUpperCase().includes('TOTAL') ? 'TOTAL' : '-');
            headerHtml += `<th class="text-center small th-verde">${label}</th>`;
        });
        
        // Retenciones Subcolumns
        colsRetenciones.forEach(c => {
            let label = dataArray[2][c] || (String(dataArray[1][c]).toUpperCase().includes('TOTAL') ? 'TOTAL' : '-');
            if (label === '0.1') label = '10%';
            else if (label === '0.2') label = '20%';
            else if (label === '0.3') label = '30%';
            else if (label === '0.5') label = '50%';
            else if (label === '0.7') label = '70%';
            else if (label === '1') label = '100%';
            headerHtml += `<th class="text-center small th-naranja">${label}</th>`;
        });

        // Form 103 Subcolumns
        colF103Casilleros.forEach(cas => {
            headerHtml += `<th class="text-center small th-gris">${cas.code}</th>`;
        });
        if (colTotal103 !== -1) {
            headerHtml += `<th class="text-center small th-gris fw-bold">TOTAL</th>`;
        }

        headerHtml += `</tr>`;
        theadEl.innerHTML = headerHtml;
    }

    const parseAndFormat = (val) => {
        if (val === undefined || val === null) return '0.00';
        let s = String(val).trim();
        if (s === '' || s === '-') return '0.00';
        
        s = s.replace(/[$\s%]/g, '');
        const lastDot = s.lastIndexOf('.');
        const lastComma = s.lastIndexOf(',');
        
        if (lastDot !== -1 && lastComma !== -1) {
            if (lastDot < lastComma) {
                s = s.replace(/\./g, '').replace(/,/g, '.');
            } else {
                s = s.replace(/,/g, '');
            }
        } else if (lastComma !== -1) {
            const afterComma = s.length - lastComma - 1;
            if (afterComma === 3) {
                s = s.replace(/,/g, '');
            } else {
                s = s.replace(/,/g, '.');
            }
        } else if (lastDot !== -1) {
            const afterDot = s.length - lastDot - 1;
            if (afterDot === 3) {
                s = s.replace(/\./g, '');
            }
        }
        
        const n = parseFloat(s);
        return isNaN(n) ? '0.00' : n.toFixed(2);
    };

    let procesados = 0;

    dataArray.forEach((row, i) => {
        if (!row || row.length === 0 || !row[0]) return;
        
        const primerCelda = String(row[0]).toLowerCase();
        if (primerCelda.includes('meses') || primerCelda.includes('ventas') || primerCelda.includes('bi 15%')) return;
        
        if (row.length > 5 && String(row[0]).trim() !== '') {
            const tr = document.createElement('tr');
            
            // MES (sticky column)
            let html = `<td class="sticky-col"><strong>${row[colMes] || '-'}</strong></td>`; 
            
            // VENTAS Subcolumns
            colsVentas.forEach(c => {
                let isBold = String(dataArray[1][c] || '').toUpperCase().includes('TOTAL');
                html += `<td class="text-end ${isBold ? 'fw-bold text-primary' : ''}">${parseAndFormat(row[c])}</td>`;
            });
            
            // COMPRAS Subcolumns
            colsCompras.forEach(c => {
                let isBold = String(dataArray[1][c] || '').toUpperCase().includes('TOTAL');
                html += `<td class="text-end ${isBold ? 'fw-bold text-success' : ''}">${parseAndFormat(row[c])}</td>`;
            });

            // V - C
            html += `<td class="text-end text-primary fw-semibold">${parseAndFormat(row[colVC])}</td>`;

            // RETENCIONES Subcolumns
            colsRetenciones.forEach(c => {
                let isBold = String(dataArray[1][c] || '').toUpperCase().includes('TOTAL');
                html += `<td class="text-end ${isBold ? 'fw-bold text-naranja' : ''}">${parseAndFormat(row[c])}</td>`;
            });
            
            // FORMULARIO 103 Casilleros Dinámicos
            colF103Casilleros.forEach(cas => {
                html += `<td class="text-end">${parseAndFormat(row[cas.col])}</td>`;
            });
            
            // FORMULARIO 103 Total
            if (colTotal103 !== -1) {
                html += `<td class="text-end text-dark fw-bold">${parseAndFormat(row[colTotal103])}</td>`;
            }
            
            tr.innerHTML = html;
            tbody.appendChild(tr);
            procesados++;
        }
    });
    
    const totalCols = 1 + colsVentas.length + colsCompras.length + 1 + colsRetenciones.length + colF103Casilleros.length + (colTotal103 !== -1 ? 1 : 0);
    if (procesados > 0) {
        document.getElementById('chip-exa-estado').innerHTML = '<i class="bi bi-check-circle-fill"></i> Datos EXA: Sincronizado';
        document.getElementById('chip-exa-estado').className = 'badge bg-success text-white me-1';
    } else {
        tbody.innerHTML = `<tr><td colspan="${totalCols}" class="text-center text-muted py-4">No se encontraron filas de datos válidos.</td></tr>`;
    }
}

// Support for uploading variable EXA Excel (.xlsx) files
function setupExaExcelUpload() {
    const dropZone = document.getElementById('drop-exa-excel');
    if (!dropZone) return;
    const fileInput = document.getElementById('input-exa-excel-nuevo');
    if (!fileInput) return;

    dropZone.addEventListener('click', () => {
        fileInput.click();
    });

    dropZone.addEventListener('dragover', e => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', e => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if (e.dataTransfer.files.length > 0) {
            const file = e.dataTransfer.files[0];
            if (file.name.toLowerCase().endsWith('.xlsx')) {
                procesarExaExcel(file);
            } else {
                alert('Por favor, suba un archivo Excel (.xlsx)');
            }
        }
    });

    fileInput.addEventListener('change', function () {
        if (this.files.length > 0) {
            procesarExaExcel(this.files[0]);
        }
    });
}

function procesarExaExcel(file) {
    const chipsContainer = document.getElementById('chips-exa-excel-nuevo');
    if (chipsContainer) {
        chipsContainer.innerHTML = '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Procesando...</span>';
    }

    const formData = new FormData();
    formData.append('file', file);
    formData.append('tipo', 'exa_excel');

    fetch(window.BASE_URL + 'ajax/upload_exa_excel.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'ok') {
                if (chipsContainer) {
                    chipsContainer.innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Excel Cargado</span>';
                }
                llenarInformeExaExcel(data);
            } else {
                throw new Error(data.error || 'Error desconocido');
            }
        })
        .catch(err => {
            console.error("Error al procesar Excel EXA:", err);
            if (chipsContainer) {
                chipsContainer.innerHTML = '<span class="badge bg-danger"><i class="bi bi-x-circle-fill"></i> Error</span>';
            }
            alert("Error al cargar Excel de EXA: " + err.message);
        });
}

function llenarInformeExaExcel(data) {
    window.exaDataArray = data.exa_data;
    if (typeof renderExaData === 'function') {
        renderExaData(data.exa_data);
    }
    if (typeof updateState === 'function') {
        updateState();
    }
}

// Initialize EXA Excel upload handlers
setupExaExcelUpload();


