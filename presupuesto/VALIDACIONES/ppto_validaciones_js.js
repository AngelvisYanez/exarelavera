/**
 * ppto_validaciones_js.js
 * Validaciones y Funcionalidades JS para el Mï¿½dulo de Presupuestos (EXA PPTO).
 */

/**
 * Realiza el cambio de pestaï¿½as (tabs) en la interfaz administrativa.
 *
 * @param {number|string} tabId Identificador numï¿½rico de la pestaï¿½a.
 */
function switchTab(tabId) {
    const tabs = document.querySelectorAll('.tab-link, .exa-pre-tab-link');
    const contents = document.querySelectorAll('.tab-content, .panels-area > div');
    
    tabs.forEach(tab => {
        tab.classList.remove('active');
        const href = tab.getAttribute('href');
        if (href && href.includes('tab=' + tabId)) {
            tab.closest('li').classList.add('active');
        }
    });

    contents.forEach(content => {
        if (content.id && content.id.startsWith('tab_content_')) {
            content.style.display = 'none';
        }
    });

    const activeContent = document.getElementById('tab_content_' + tabId);
    if (activeContent) {
        activeContent.style.display = 'block';
    }
}

/**
 * Calcula dinï¿½micamente la sumatoria de las celdas editables mensuales de una partida.
 *
 * @param {HTMLElement} cell Elemento celda editable que disparï¿½ el evento.
 */
function calcularTotalFila(cell) {
    var tr = cell.closest('tr');
    var partId = tr.getAttribute('data-partida');
    var mes = cell.getAttribute('data-mes');
    var val = pptoParseNumber(cell.innerText);
    cell.innerText = formatNumber(val, 2);
    
    var hiddenInput = tr.querySelector('input[name="valores[' + partId + '][' + mes + ']"]');
    if (hiddenInput) {
        hiddenInput.value = val;
    }

    var total = 0;
    tr.querySelectorAll('.exa-pre-editable-cell').forEach(function (c) {
        total += pptoParseNumber(c.innerText);
    });
    tr.querySelector('.total-fila').innerText = formatCurrency(total);
}

/**
 * Calcula dinï¿½micamente la sumatoria mensual al cargar presupuestos manuales.
 *
 * @param {HTMLElement} cell Elemento celda editable de carga manual.
 */
function calcularTotalFilaCargar(cell) {
    var tr = cell.closest('tr');
    var partId = tr.getAttribute('data-partida-cargar');
    var mes = cell.getAttribute('data-mes');
    var val = pptoParseNumber(cell.innerText);
    cell.innerText = formatNumber(val, 2);
    
    var hiddenInput = tr.querySelector('input[name="valores_cargar[' + partId + '][' + mes + ']"]');
    if (hiddenInput) {
        hiddenInput.value = val;
    }

    var total = 0;
    tr.querySelectorAll('.cell-cargar').forEach(function (c) {
        total += pptoParseNumber(c.innerText);
    });
    tr.querySelector('.total-fila-cargar').innerText = formatCurrency(total);
}

/**
 * Muestra una ventana modal dinï¿½mica en pantalla.
 *
 * @param {string} titulo Tï¿½tulo de la modal.
 * @param {string} htmlContenido Contenido HTML a inyectar en el cuerpo.
 */
function mostrarModal(titulo, htmlContenido) {
    let modal = document.getElementById('pre_custom_modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'pre_custom_modal';
        modal.style.position = 'fixed';
        modal.style.zIndex = '99999';
        modal.style.left = '0';
        modal.style.top = '0';
        modal.style.width = '100%';
        modal.style.height = '100%';
        modal.style.overflow = 'auto';
        modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
        modal.style.display = 'none';
        
        const contentWrapper = document.createElement('div');
        contentWrapper.style.backgroundColor = '#ffffff';
        contentWrapper.style.margin = '10% auto';
        contentWrapper.style.padding = '24px';
        contentWrapper.style.border = '1px solid #cbd5e0';
        contentWrapper.style.width = '60%';
        contentWrapper.style.borderRadius = '8px';
        contentWrapper.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        contentWrapper.style.position = 'relative';

        const closeBtn = document.createElement('span');
        closeBtn.innerHTML = '&times;';
        closeBtn.style.position = 'absolute';
        closeBtn.style.right = '20px';
        closeBtn.style.top = '15px';
        closeBtn.style.fontSize = '24px';
        closeBtn.style.fontWeight = 'bold';
        closeBtn.style.cursor = 'pointer';
        closeBtn.style.color = '#718096';
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        };

        const titleEl = document.createElement('h3');
        titleEl.id = 'pre_custom_modal_title';
        titleEl.style.marginTop = '0';
        titleEl.style.color = '#1a2e4a';

        const bodyEl = document.createElement('div');
        bodyEl.id = 'pre_custom_modal_body';
        bodyEl.style.marginTop = '20px';

        contentWrapper.appendChild(closeBtn);
        contentWrapper.appendChild(titleEl);
        contentWrapper.appendChild(bodyEl);
        modal.appendChild(contentWrapper);
        document.body.appendChild(modal);

        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    }

    document.getElementById('pre_custom_modal_title').innerText = titulo;
    document.getElementById('pre_custom_modal_body').innerHTML = htmlContenido;
    modal.style.display = 'block';
}

/**
 * Peticiï¿½n asï¿½ncrona para marcar una alerta presupuestaria como leï¿½da.
 *
 * @param {number} palCod ID de la alerta.
 * @param {HTMLElement} alertElementDom Elemento del DOM de la tarjeta de alerta.
 */
function marcarAlertaLeida(palCod, alertElementDom) {
    if (!palCod) return;
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'ppto_consulta_front.php?marcar_leida=' + palCod, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            if (alertElementDom) {
                alertElementDom.style.transition = 'opacity 0.4s ease';
                alertElementDom.style.opacity = '0';
                setTimeout(() => {
                    alertElementDom.remove();
                }, 400);
            }
        }
    };
    xhr.send('pal_id=' + palCod);
}

/**
 * Retorna la etiqueta HTML semaforizada segï¿½n el progreso de ejecuciï¿½n presupuestaria.
 *
 * @param {number|string} pct Porcentaje de ejecuciï¿½n.
 * @return {string} Cï¿½digo HTML de la etiqueta/badge de estado.
 */
function semaforo(pct) {
    const value = parseFloat(pct) || 0.00;
    let bg, fg, dot, text;
    if (value < 80.00) {
        bg = '#c6f6d5'; fg = '#22543d'; dot = '#38a169'; text = 'A tiempo';
    } else if (value >= 80.00 && value < 100.00) {
        bg = '#feebc8'; fg = '#744210'; dot = '#dd6b20'; text = 'En riesgo';
    } else {
        bg = '#fed7d7'; fg = '#742a2a'; dot = '#e53e3e'; text = 'Superado';
    }

    return `<span class="dot-badge" style="background-color: ${bg}; color: ${fg}; display: inline-flex; align-items: center; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 12px; gap: 6px;">
        <span class="dot" style="width: 8px; height: 8px; border-radius: 50%; display: inline-block; background-color: ${dot};"></span>
        ${text}
    </span>`;
}

/**
 * Solicita confirmaciï¿½n antes de eliminar o realizar una acciï¿½n crï¿½tica.
 *
 * @param {string} mensaje Texto explicativo.
 * @param {Function} callback Funciï¿½n de retorno si se confirma.
 */
function confirmarEliminar(mensaje, callback) {
    const res = confirm(mensaje || 'ï¿½Estï¿½ seguro de que desea realizar esta acciï¿½n?');
    if (res && typeof callback === 'function') {
        callback();
    }
}

/**
 * Valida los datos del formulario de Carga de Presupuestos.
 *
 * @param {HTMLFormElement} formElement Formulario de carga.
 * @return {boolean} Retorna true si es vï¿½lido, false en caso contrario.
 */
function validarCargarPresupuesto(formElement) {
    if (!formElement) return false;
    const ani = parseInt(formElement.querySelector('[name="Ppe_Ani"]').value, 10);
    
    // RESOLUCIï¿½N DE ISS-03: Rango de aï¿½o dinï¿½mico en lugar de lï¿½mites fijos
    const currentYear = new Date().getFullYear();
    if (isNaN(ani) || ani < currentYear - 5 || ani > currentYear + 10) {
        alert('El aï¿½o del presupuesto debe ser un ejercicio fiscal vï¿½lido cercano al actual (ej: ' + currentYear + ').');
        return false;
    }

    let tieneMontoValido = false;
    const hiddenInputs = formElement.querySelectorAll('input[name^="valores_cargar"]');
    hiddenInputs.forEach(input => {
        if (parseFloat(input.value) > 0) {
            tieneMontoValido = true;
        }
    });

    var modoCargar = formElement.querySelector('[name="cargar_modo"]').value;

    if (modoCargar === 'manual' && !tieneMontoValido) {
        alert('Debe ingresar al menos un monto mayor a cero en las partidas.');
        return false;
    }

    return true;
}

/**
 * Valida los datos del formulario de Nueva Partida y realiza la comprobaciï¿½n AJAX de duplicados.
 *
 * @param {HTMLFormElement} formElement Formulario de la partida.
 * @return {boolean} Retorna false para esperar la validacion asincrona.
 */
function validarNuevaPartida(formElement) {
    if (!formElement) return false;
    const cla = formElement.querySelector('[name="Ppa_Cla"]').value.trim();
    const des = formElement.querySelector('[name="Ppa_Des"]').value.trim();
    const tip = formElement.querySelector('[name="Ppa_Tip"]').value;
    
    const formatReg = /^[0-9]+(\.[0-9]+)*$/;
    if (!formatReg.test(cla)) {
        alert('El codigo visible debe usar solo numeros; las subpartidas se separan con punto (ej: 03 o 03.01).');
        return false;
    }

    if (des.length < 3) {
        alert('La descripcion de la partida debe tener al menos 3 caracteres.');
        return false;
    }

    if (!['I', 'G', 'V'].includes(tip)) {
        alert('Debe seleccionar un tipo de partida valido.');
        return false;
    }

    var codNiv = pptoPartidaNivelDesdeCodigo(cla);
    var nivHidden = parseInt(formElement.querySelector('#form_ppa_nivel').value, 10) || 1;
    if (codNiv !== nivHidden) {
        alert('El codigo "' + cla + '" corresponde a nivel ' + codNiv + '. Revise la ubicacion o el contenedor padre.');
        return false;
    }

    var modoUb = formElement.querySelector('input[name="partida_modo_ub"]:checked');
    if (modoUb && modoUb.value === 'hijo') {
        var padVal = formElement.querySelector('#form_ppa_padre_id').value;
        if (!padVal) {
            alert('Seleccione el contenedor (partida padre) para la subpartida.');
            return false;
        }
        var padreGrupo = pptoPartidaBuscarPorId(padVal);
        if (!padreGrupo || padreGrupo.Ppa_Clase !== 'G') {
            alert('El contenedor padre debe ser una partida con clase Grupo (agrupadora).');
            return false;
        }
    }

    if (codNiv > 1) {
        var padSel = formElement.querySelector('#form_ppa_padre_id');
        var padValNiv = padSel ? padSel.value : '';
        var padreCod = '';
        if (padValNiv) {
            var padObj = pptoPartidaBuscarPorId(padValNiv);
            if (padObj && padObj.Ppa_Cla) {
                padreCod = padObj.Ppa_Cla;
            }
        }
        var prefijoPadre = pptoPartidaPrefijoPadreCodigo(cla);
        if (prefijoPadre && padreCod && prefijoPadre !== padreCod) {
            alert('El codigo debe colgar del contenedor seleccionado (' + padreCod + ').');
            return false;
        }
        if (prefijoPadre && padreCod && cla.indexOf(padreCod + '.') !== 0) {
            alert('El codigo debe comenzar con ' + padreCod + '.');
            return false;
        }
    }

    const Ppa_Cod = formElement.querySelector('[name="Ppa_Cod"]').value;
    const emp_cod = formElement.querySelector('[name="emp_cod"]');
    const empParam = emp_cod && emp_cod.value ? ('&emp_cod=' + encodeURIComponent(emp_cod.value)) : '';
    
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'ppto_admin_front.php?ajax_check_partida=1&cla=' + encodeURIComponent(cla) + '&Ppa_Cod=' + encodeURIComponent(Ppa_Cod) + empParam, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                const res = JSON.parse(xhr.responseText);
                if (res && res.existe) {
                    alert('El codigo de partida ingresado ya existe en la empresa.');
                } else {
                    var padSel = formElement.querySelector('#form_ppa_padre_id');
                    if (padSel && padSel.disabled) {
                        padSel.disabled = false;
                    }
                    HTMLFormElement.prototype.submit.call(formElement);
                }
            } catch (e) {
                console.error('Error al procesar validacion AJAX de partida: ', e, xhr.responseText);
                alert('Ocurrio un error en el servidor al validar la partida.');
            }
        }
    };
    xhr.send();

    return false;
}

/**
 * Valida los campos del formulario de Reglas de Asignaciï¿½n Automï¿½tica.
 *
 * @param {HTMLFormElement} formElement Formulario de regla.
 * @return {boolean} Retorna true si es vï¿½lido, false en caso contrario.
 */
function validarNuevaRegla(formElement) {
    if (!formElement) return false;
    const tipdoc = formElement.querySelector('[name="Prg_TipDoc"]').value;
    const ppa = formElement.querySelector('[name="Ppa_Cod"]').value;
    const pri = parseInt(formElement.querySelector('[name="Prg_Pri"]').value);
    const campo = formElement.querySelector('[name="Prg_Campo"]').value.trim();
    const valor = formElement.querySelector('[name="Prg_Valor"]').value.trim();

    if (!tipdoc) {
        alert('El tipo de documento origen es requerido.');
        return false;
    }

    if (!ppa) {
        alert('Debe especificar la partida presupuestaria destino.');
        return false;
    }

    if (isNaN(pri) || pri < 1 || pri > 99) {
        alert('La prioridad de evaluaciï¿½n debe ser un nï¿½mero entre 1 y 99.');
        return false;
    }

    if ((campo !== '' && valor === '') || (campo === '' && valor !== '')) {
        alert('Si define un Campo de Evaluaciï¿½n, debe especificar su Valor esperado (y viceversa).');
        return false;
    }

    var camMon = formElement.querySelector('[name="Prg_CamMon"]').value.trim();
    if (!camMon) {
        alert('Debe indicar que monto se imputa al presupuesto.');
        return false;
    }

    if (typeof pptoReglaEsAdmin === 'function' && !pptoReglaEsAdmin()) {
        if (typeof pptoReglaValorEnCatalogo === 'function' && !pptoReglaValorEnCatalogo(campo, valor, camMon)) {
            alert('Solo un perfil Administrador puede guardar reglas con campos personalizados.');
            return false;
        }
    }

    return true;
}

function pptoRefrescarPadresPartida() {
    pptoRellenarPadresArbol();
}

function pptoPartidaNivelDesdeCodigo(cla) {
    if (!cla) return 1;
    var partes = cla.split('.').filter(function (s) { return s !== ''; });
    return Math.max(1, partes.length);
}

function pptoPartidaPrefijoPadreCodigo(cla) {
    if (!cla || cla.indexOf('.') === -1) return null;
    var partes = cla.split('.');
    partes.pop();
    return partes.join('.');
}

var pptoPartidaSyncCodigoTimer = null;
function pptoPartidaSyncDesdeCodigoDebounced() {
    if (pptoPartidaSyncCodigoTimer) clearTimeout(pptoPartidaSyncCodigoTimer);
    pptoPartidaSyncCodigoTimer = setTimeout(pptoPartidaSyncDesdeCodigo, 280);
}

function pptoPartidaBuscarPorCodigo(codigo) {
    var cat = window.PPTO_PARTIDAS_CATALOGO || [];
    for (var i = 0; i < cat.length; i++) {
        if (cat[i].Ppa_Cla === codigo) return cat[i];
    }
    return null;
}

function pptoPartidaBuscarPorId(id) {
    var pool = window.PPTO_PARTIDAS_PADRE_POOL || [];
    var cat = window.PPTO_PARTIDAS_CATALOGO || [];
    var i, p;
    for (i = 0; i < pool.length; i++) {
        if (String(pool[i].Ppa_Cod) === String(id)) return pool[i];
    }
    for (i = 0; i < cat.length; i++) {
        if (String(cat[i].Ppa_Cod) === String(id)) return cat[i];
    }
    return null;
}

function pptoPartidaSugerirCodigo(padreId) {
    var catalogo = window.PPTO_PARTIDAS_CATALOGO || [];
    var prefijo = '';
    var nivelEsperado = 1;
    if (padreId) {
        var padre = pptoPartidaBuscarPorId(padreId);
        if (!padre) return '';
        prefijo = padre.Ppa_Cla + '.';
        nivelEsperado = parseInt(padre.Ppa_Niv, 10) + 1;
    }
    var maxSeg = 0;
    catalogo.forEach(function (p) {
        if (parseInt(p.Ppa_Niv, 10) !== nivelEsperado) return;
        var cod = p.Ppa_Cla;
        var seg;
        if (padreId) {
            if (cod.indexOf(prefijo) !== 0) return;
            var resto = cod.substring(prefijo.length);
            if (!resto || resto.indexOf('.') !== -1) return;
            seg = parseInt(resto, 10);
        } else {
            if (cod.indexOf('.') !== -1) return;
            seg = parseInt(cod, 10);
        }
        if (!isNaN(seg) && seg > maxSeg) maxSeg = seg;
    });
    var sig = maxSeg + 1;
    var segStr = sig < 10 ? ('0' + sig) : String(sig);
    return padreId ? (prefijo + segStr) : segStr;
}

function pptoPartidaActualizarResumenUb() {
    var niv = parseInt(document.getElementById('form_ppa_nivel').value, 10) || 1;
    var badge = document.getElementById('ppto_partida_ub_nivel_txt');
    if (badge) badge.textContent = 'Nivel ' + niv;
}

function pptoRellenarPadresArbol(presetPadreId) {
    var pool = window.PPTO_PARTIDAS_PADRE_POOL || [];
    var sel = document.getElementById('form_ppa_padre_id');
    var ayuda = document.getElementById('form_ppa_pad_ayuda');
    if (!sel) return;

    var ppaId = parseInt(document.getElementById('form_ppa_id').value, 10) || 0;
    var valorActual = presetPadreId ? String(presetPadreId) : sel.value;

    sel.innerHTML = '<option value="">â€” Seleccione contenedor â€”</option>';
    sel.disabled = false;

    var candidatas = pool.filter(function (p) {
        return parseInt(p.Ppa_Cod, 10) !== ppaId && p.Ppa_Clase === 'G';
    });

    candidatas.forEach(function (p) {
        var opt = document.createElement('option');
        var depth = Math.max(0, parseInt(p.Ppa_Niv, 10) - 1);
        var indent = '';
        for (var i = 0; i < depth; i++) indent += '   ';
        opt.value = String(p.Ppa_Cod);
        opt.textContent = indent + p.Ppa_Cla + ' â€” ' + p.Ppa_Des;
        sel.appendChild(opt);
    });

    if (valorActual && !sel.querySelector('option[value="' + valorActual + '"]')) {
        var extra = pptoPartidaBuscarPorId(valorActual);
        if (extra && extra.Ppa_Clase === 'G') {
            var optExtra = document.createElement('option');
            optExtra.value = String(extra.Ppa_Cod);
            optExtra.textContent = extra.Ppa_Cla + ' â€” ' + extra.Ppa_Des;
            sel.appendChild(optExtra);
        }
    }

    if (valorActual && sel.querySelector('option[value="' + valorActual + '"]')) {
        sel.value = valorActual;
    }

    if (ayuda) {
        ayuda.textContent = candidatas.length === 0
            ? 'No hay partidas con clase Grupo activas. Marque un capitulo como Grupo antes de crear subpartidas.'
            : 'Solo se listan partidas con clase Grupo (agrupadoras).';
    }
}

function pptoPartidaCambioModoUbicacion(silent, presetPadreId) {
    var modoEl = document.querySelector('input[name="partida_modo_ub"]:checked');
    var modo = modoEl ? modoEl.value : 'raiz';
    var padreWrap = document.getElementById('ppto_partida_padre_wrap');
    var padSel = document.getElementById('form_ppa_padre_id');
    var claInput = document.getElementById('form_ppa_codigo_clasificacion');

    if (modo === 'raiz') {
        if (padreWrap) padreWrap.style.display = 'none';
        if (padSel) padSel.value = '';
        document.getElementById('form_ppa_nivel').value = '1';
        if (!silent && claInput && (!claInput.value || claInput.value.indexOf('.') !== -1)) {
            claInput.value = pptoPartidaSugerirCodigo(null);
        }
    } else {
        if (padreWrap) padreWrap.style.display = 'block';
        pptoRellenarPadresArbol(presetPadreId);
    }
    pptoPartidaActualizarResumenUb();
    var sug = document.getElementById('ppto_partida_ub_codigo_sug');
    if (sug && modo === 'raiz' && claInput) {
        sug.textContent = claInput.value ? ('Sugerido: ' + claInput.value) : '';
    }
}

function pptoPartidaSyncDesdePadre() {
    var padId = document.getElementById('form_ppa_padre_id').value;
    if (!padId) {
        pptoPartidaActualizarResumenUb();
        return;
    }
    var padre = pptoPartidaBuscarPorId(padId);
    if (!padre) return;

    document.getElementById('form_ppa_nivel').value = parseInt(padre.Ppa_Niv, 10) + 1;

    var sugerido = pptoPartidaSugerirCodigo(padId);
    var claInput = document.getElementById('form_ppa_codigo_clasificacion');
    if (claInput && (!claInput.value || claInput.value.indexOf(padre.Ppa_Cla) !== 0)) {
        claInput.value = sugerido;
    }

    var sug = document.getElementById('ppto_partida_ub_codigo_sug');
    if (sug) sug.textContent = sugerido ? ('Sugerido: ' + sugerido) : '';

    pptoPartidaActualizarResumenUb();
}

function pptoPartidaSyncDesdeCodigo() {
    var cla = document.getElementById('form_ppa_codigo_clasificacion').value.trim();
    var niv = pptoPartidaNivelDesdeCodigo(cla);
    document.getElementById('form_ppa_nivel').value = niv;

    var esEdicion = !!document.getElementById('form_ppa_id').value;
    var ubicNueva = document.getElementById('ppto_partida_ubicacion_nueva');

    if (!esEdicion && ubicNueva && ubicNueva.style.display !== 'none') {
        if (niv === 1) {
            var raiz = document.querySelector('input[name="partida_modo_ub"][value="raiz"]');
            if (raiz) raiz.checked = true;
            pptoPartidaCambioModoUbicacion(true);
        } else {
            var hijo = document.querySelector('input[name="partida_modo_ub"][value="hijo"]');
            if (hijo) hijo.checked = true;
            pptoPartidaCambioModoUbicacion(true);
            var prefijo = pptoPartidaPrefijoPadreCodigo(cla);
            var padre = prefijo ? pptoPartidaBuscarPorCodigo(prefijo) : null;
            if (padre && padre.Ppa_Clase === 'G') {
                document.getElementById('form_ppa_padre_id').value = padre.Ppa_Cod;
            }
        }
    }

    pptoPartidaActualizarResumenUb();
}

function pptoPartidaCambioClase() {
    var clase = document.getElementById('form_ppa_clase').value;
    var ayuda = document.getElementById('form_ppa_clase_ayuda');
    var pctWrap = document.getElementById('form_ppa_pct_wrap');
    if (pctWrap) {
        pctWrap.style.display = (clase === 'G') ? 'block' : 'none';
    }
    if (!ayuda) return;
    ayuda.textContent = clase === 'G'
        ? 'Grupo = agrupa hijos. Defina % tope para limitar el presupuesto de sus rubros detalle.'
        : 'Detalle = recibe gastos e imputaciones.';
}

function pptoPartidaResetUiNueva() {
    var ubNueva = document.getElementById('ppto_partida_ubicacion_nueva');
    var ubEdit = document.getElementById('ppto_partida_edit_ub');
    if (ubNueva) ubNueva.style.display = 'block';
    if (ubEdit) { ubEdit.style.display = 'none'; ubEdit.innerHTML = ''; }
    var raiz = document.querySelector('input[name="partida_modo_ub"][value="raiz"]');
    if (raiz) raiz.checked = true;
}

function pptoPartidaMostrarUiEdicion(data) {
    var ubNueva = document.getElementById('ppto_partida_ubicacion_nueva');
    var ubEdit = document.getElementById('ppto_partida_edit_ub');
    if (ubNueva) ubNueva.style.display = 'none';
    if (!ubEdit) return;

    var padreTxt = 'Sin padre (raiz)';
    if (data.Ppa_Pad) {
        var pad = pptoPartidaBuscarPorId(data.Ppa_Pad);
        if (pad) padreTxt = pad.Ppa_Cla + ' â€” ' + pad.Ppa_Des;
    }
    ubEdit.style.display = 'block';
    ubEdit.innerHTML = '<strong>Ubicacion:</strong> Nivel ' + data.Ppa_Niv + ' &middot; Padre: ' + padreTxt;

    if (parseInt(data.Ppa_Niv, 10) > 1) {
        document.getElementById('ppto_partida_padre_wrap').style.display = 'block';
        pptoRellenarPadresArbol();
        document.getElementById('form_ppa_padre_id').value = data.Ppa_Pad || '';
    }
}

/**
 * Rellena el combo Partida Padre segun el nivel del arbol.
 * Nivel 2 -> padres Grupo nivel 1; nivel 3 -> padres Grupo nivel 2, etc.
 */
function pptoRefrescarPadresPartidaLegacy() {
    var pool = window.PPTO_PARTIDAS_PADRE_POOL || [];
    var sel = document.getElementById('form_ppa_padre_id');
    var ayuda = document.getElementById('form_ppa_pad_ayuda');
    if (!sel) {
        return;
    }

    var niv = parseInt(document.getElementById('form_ppa_nivel').value, 10) || 1;
    var ppaId = parseInt(document.getElementById('form_ppa_id').value, 10) || 0;
    var valorActual = sel.value;
    var nivelPadre = niv - 1;

    sel.innerHTML = '<option value="">Ninguna (Raiz)</option>';

    if (nivelPadre < 1) {
        sel.disabled = true;
        if (ayuda) {
            ayuda.textContent = 'Las partidas de nivel 1 no tienen padre.';
        }
        return;
    }

    sel.disabled = false;

    var candidatas = pool.filter(function (p) {
        return parseInt(p.Ppa_Niv, 10) === nivelPadre && parseInt(p.Ppa_Cod, 10) !== ppaId;
    });

    var grupos = candidatas.filter(function (p) {
        return p.Ppa_Clase === 'G';
    });

    if (grupos.length > 0) {
        candidatas = grupos;
    } else if (nivelPadre === 1) {
        /* Respaldo: todas las cuentas activas de nivel 1 */
    }

    candidatas.forEach(function (p) {
        var opt = document.createElement('option');
        opt.value = p.Ppa_Cod;
        opt.textContent = p.Ppa_Cla + ' - ' + p.Ppa_Des;
        sel.appendChild(opt);
    });

    if (valorActual && sel.querySelector('option[value="' + valorActual + '"]')) {
        sel.value = valorActual;
    }

    if (ayuda) {
        if (candidatas.length === 0) {
            ayuda.textContent = 'No hay cuentas Grupo activas en nivel ' + nivelPadre + '. Marque la cuenta padre como Grupo en el catalogo.';
        } else {
            ayuda.textContent = 'Seleccione la cuenta Grupo de nivel ' + nivelPadre + '.';
        }
    }
}

/**
 * Abre la modal de creaciï¿½n de partidas reseteando el formulario.
 */
function nuevaPartida() {
    document.getElementById('modal_partida_titulo').innerText = 'Registrar Nueva Partida Presupuestaria';
    document.getElementById('form_ppa_id').value = '';
    document.getElementById('form_ppa_codigo_clasificacion').value = '';
    document.getElementById('form_ppa_descripcion').value = '';
    document.getElementById('form_ppa_tipo').value = 'G';
    document.getElementById('form_ppa_naturaleza').value = 'OPE';
    document.getElementById('form_ppa_padre_id').value = '';
    document.getElementById('form_ppa_nivel').value = '1';
    document.getElementById('form_ppa_clase').value = 'D';
    document.getElementById('form_ppa_estado').value = 'A';
    pptoPartidaResetUiNueva();
    pptoPartidaCambioModoUbicacion(true);
    document.getElementById('form_ppa_codigo_clasificacion').value = pptoPartidaSugerirCodigo(null);
    pptoPartidaActualizarResumenUb();
    var sug = document.getElementById('ppto_partida_ub_codigo_sug');
    if (sug) sug.textContent = 'Sugerido: ' + document.getElementById('form_ppa_codigo_clasificacion').value;
    document.getElementById('modal_partida').style.display = 'block';
}

/**
 * Abre la modal de partidas precargando los datos para ediciï¿½n.
 *
 * @param {Object} data Datos de la partida de la BD.
 */
function nuevaPartidaHijoPorId(ppaId) {
    var padre = pptoPartidaBuscarPorId(ppaId);
    if (!padre) {
        alert('No se encontro la partida contenedora. Recargue la pagina e intente de nuevo.');
        return;
    }
    nuevaPartidaHijo(padre);
}

function nuevaPartidaHijo(padre) {
    if (!padre || !padre.Ppa_Cod) {
        return;
    }

    nuevaPartida();
    document.getElementById('modal_partida_titulo').innerText = 'Nueva subpartida bajo ' + (padre.Ppa_Cla || '');
    var hijo = document.querySelector('input[name="partida_modo_ub"][value="hijo"]');
    if (hijo) hijo.checked = true;
    pptoPartidaCambioModoUbicacion(true, padre.Ppa_Cod);
    if (padre.Ppa_Tip) document.getElementById('form_ppa_tipo').value = padre.Ppa_Tip;
    if (padre.Ppa_Nat) document.getElementById('form_ppa_naturaleza').value = padre.Ppa_Nat;
    document.getElementById('form_ppa_clase').value = 'D';
    pptoPartidaSyncDesdePadre();
    document.getElementById('modal_partida').style.display = 'block';
}

function pptoPartidaInitCatalogoAcciones() {
    var tabla = document.getElementById('tabla_partidas_catalogo');
    if (!tabla || tabla.getAttribute('data-ppto-subpartida-bound') === '1') {
        return;
    }
    tabla.setAttribute('data-ppto-subpartida-bound', '1');
    tabla.addEventListener('click', function (e) {
        var btn = e.target.closest('.js-ppto-nueva-subpartida');
        if (!btn) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        var ppaId = btn.getAttribute('data-ppa-id');
        if (ppaId) {
            nuevaPartidaHijoPorId(ppaId);
        }
    });
}

function editarPartida(data) {
    document.getElementById('modal_partida_titulo').innerText = 'Editar Partida Presupuestaria';
    document.getElementById('form_ppa_id').value = data.Ppa_Cod;
    document.getElementById('form_ppa_codigo_clasificacion').value = data.Ppa_Cla;
    document.getElementById('form_ppa_descripcion').value = data.Ppa_Des;
    document.getElementById('form_ppa_tipo').value = data.Ppa_Tip;
    document.getElementById('form_ppa_naturaleza').value = data.Ppa_Nat;
    document.getElementById('form_ppa_nivel').value = data.Ppa_Niv;
    document.getElementById('form_ppa_clase').value = (data.Ppa_Clase === 'G') ? 'G' : 'D';
    document.getElementById('form_ppa_estado').value = data.Ppa_Est;
    var pctEl = document.getElementById('form_ppa_porcentaje_tope');
    if (pctEl) {
        pctEl.value = (data.Ppa_Pct !== null && data.Ppa_Pct !== undefined && data.Ppa_Pct !== '')
            ? data.Ppa_Pct : '';
    }
    pptoPartidaCambioClase();
    pptoPartidaMostrarUiEdicion(data);
    pptoPartidaActualizarResumenUb();
    document.getElementById('modal_partida').style.display = 'block';
}

/**
 * Muestra u oculta partidas inactivas en el catalogo.
 *
 * @param {HTMLInputElement} el Checkbox.
 */
function pptoToggleVerInactivos(el) {
    var params = new URLSearchParams(window.location.search);
    params.set('tab', '2');
    if (el && el.checked) {
        params.set('ver_inactivos', '1');
    } else {
        params.delete('ver_inactivos');
    }
    window.location.href = 'ppto_admin_front.php?' + params.toString();
}

/**
 * Muestra u oculta reglas inactivas en la pestana de asignacion.
 *
 * @param {HTMLInputElement} el Checkbox.
 */
function pptoToggleVerReglasInactivas(el) {
    var params = new URLSearchParams(window.location.search);
    params.set('tab', '5');
    if (el && el.checked) {
        params.set('ver_reglas_inactivas', '1');
    } else {
        params.delete('ver_reglas_inactivas');
    }
    window.location.href = 'ppto_admin_front.php?' + params.toString();
}

/**
 * @return {string}
 */
function pptoAdminQsBase() {
    var qs = (typeof PPTO_ADMIN_QS !== 'undefined' && PPTO_ADMIN_QS) ? PPTO_ADMIN_QS : '';
    if (qs && qs.charAt(0) !== '&') {
        qs = '&' + qs;
    }
    return qs;
}

/**
 * Anula (inactiva) una partida presupuestaria.
 *
 * @param {number} ppaId
 * @param {string} codigo
 */
function anularPartida(ppaId, codigo) {
    var msg = 'Desea anular la partida "' + codigo + '"?\n\nQuedara inactiva. Si tiene reglas de asignacion activas, tambien se inactivaran.';
    if (typeof jQuery !== 'undefined' && jQuery.confirm) {
        jQuery.confirm(msg, function () {
            window.location.href = 'ppto_admin_front.php?estado_partida=' + encodeURIComponent(ppaId) + '&nuevo_est=I&tab=2' + pptoAdminQsBase() + '&ver_inactivos=1';
        });
        return;
    }
    if (!confirm(msg)) {
        return;
    }
    window.location.href = 'ppto_admin_front.php?estado_partida=' + encodeURIComponent(ppaId) + '&nuevo_est=I&tab=2' + pptoAdminQsBase() + '&ver_inactivos=1';
}

/**
 * Reactiva una partida presupuestaria inactiva.
 *
 * @param {number} ppaId
 * @param {string} codigo
 */
function activarPartida(ppaId, codigo) {
    var msg = 'Desea reactivar la partida "' + codigo + '"?';
    if (typeof jQuery !== 'undefined' && jQuery.confirm) {
        jQuery.confirm(msg, function () {
            window.location.href = 'ppto_admin_front.php?estado_partida=' + encodeURIComponent(ppaId) + '&nuevo_est=A&tab=2' + pptoAdminQsBase() + '&ver_inactivos=1';
        });
        return;
    }
    if (!confirm(msg)) {
        return;
    }
    window.location.href = 'ppto_admin_front.php?estado_partida=' + encodeURIComponent(ppaId) + '&nuevo_est=A&tab=2' + pptoAdminQsBase() + '&ver_inactivos=1';
}

/**
 * Cierra la modal de formulario de partidas.
 */
function cerrarModalPartida() {
    document.getElementById('modal_partida').style.display = 'none';
}

/**
 * Indica si el usuario actual puede ver/editar opciones avanzadas de reglas.
 */
function pptoReglaEsAdmin() {
    if (typeof PPTO_USUARIO_ES_ADMIN === 'undefined') {
        return false;
    }
    return PPTO_USUARIO_ES_ADMIN === true || PPTO_USUARIO_ES_ADMIN === 'true' || PPTO_USUARIO_ES_ADMIN === 1;
}

function pptoReglaAplicarModoAdminUI() {
    var esAdmin = pptoReglaEsAdmin();
    var badge = document.getElementById('regla_admin_badge');
    var adv = document.getElementById('regla_condicion_avanzada');
    var inputMon = document.getElementById('form_prg_cammon');
    if (badge) {
        badge.style.display = esAdmin ? 'inline-block' : 'none';
    }
    if (esAdmin && adv) {
        adv.style.display = 'block';
    } else if (!esAdmin && adv && document.getElementById('form_prg_condicion_sel') &&
        document.getElementById('form_prg_condicion_sel').value !== '__custom__') {
        adv.style.display = 'none';
    }
    if (esAdmin && inputMon) {
        inputMon.style.display = 'block';
        inputMon.placeholder = 'Campo tecnico del monto (ej. Vet_Sub) ï¿½ puede editarlo';
    }
}

function pptoReglaCatalogoActual() {
    var cat = (typeof PPTO_REGLAS_CATALOGO !== 'undefined') ? PPTO_REGLAS_CATALOGO : {};
    var tip = document.getElementById('form_prg_tipdoc').value;
    return cat[tip] || cat._default || {
        label: 'Documento',
        montos: [{ v: '', t: 'Sin catalogo' }],
        condiciones: [{ campo: '', valor: '', t: 'Sin condicion (recomendado)' }]
    };
}

function pptoReglaValorEnCatalogo(campo, valor, monto) {
    var cat = pptoReglaCatalogoActual();
    campo = (campo || '').trim();
    valor = (valor || '').trim();
    monto = (monto || '').trim();
    var mOk = false;
    cat.montos.forEach(function (m) {
        if (m.v === monto) {
            mOk = true;
        }
    });
    if (!mOk) {
        return false;
    }
    for (var i = 0; i < cat.condiciones.length; i++) {
        var c = cat.condiciones[i];
        if ((c.campo || '') === campo && (c.valor || '') === valor) {
            return true;
        }
    }
    return false;
}

function pptoReglaMostrarBloqueoAvanzado(bloquear) {
    var box = document.getElementById('regla_avanzada_bloqueada');
    var selCond = document.getElementById('form_prg_condicion_sel');
    var selMon = document.getElementById('form_prg_cammon_sel');
    if (box) {
        box.style.display = bloquear ? 'block' : 'none';
    }
    if (selCond) {
        selCond.disabled = !!bloquear;
    }
    if (selMon) {
        selMon.disabled = !!bloquear;
    }
}

function pptoReglaActualizarAyudas(preservarValores) {
    var cat = pptoReglaCatalogoActual();
    var selCond = document.getElementById('form_prg_condicion_sel');
    var selMon = document.getElementById('form_prg_cammon_sel');
    var ayudaMon = document.getElementById('regla_monto_ayuda');
    var esAdmin = pptoReglaEsAdmin();

    if (!selCond || !selMon) {
        return;
    }

    selCond.innerHTML = '';
    cat.condiciones.forEach(function (c, idx) {
        var opt = document.createElement('option');
        opt.value = String(idx);
        opt.textContent = c.t;
        opt.setAttribute('data-campo', c.campo || '');
        opt.setAttribute('data-valor', c.valor || '');
        selCond.appendChild(opt);
    });
    if (esAdmin) {
        var optCustom = document.createElement('option');
        optCustom.value = '__custom__';
        optCustom.textContent = 'Condicion personalizada (administrador)';
        selCond.appendChild(optCustom);
    }

    selMon.innerHTML = '';
    cat.montos.forEach(function (m) {
        var optM = document.createElement('option');
        optM.value = m.v;
        optM.textContent = m.t;
        selMon.appendChild(optM);
    });
    if (esAdmin) {
        var optMonCustom = document.createElement('option');
        optMonCustom.value = '__custom__';
        optMonCustom.textContent = 'Otro campo (administrador)';
        selMon.appendChild(optMonCustom);
    }

    if (ayudaMon) {
        ayudaMon.textContent = esAdmin
            ? ('Sugerido para ' + cat.label + '. El administrador puede definir campos personalizados.')
            : ('Sugerido para ' + cat.label + '. Elija una opcion de la lista.');
    }

    if (!preservarValores) {
        pptoReglaAplicarCondicion();
        pptoReglaAplicarMonto();
        pptoReglaMostrarBloqueoAvanzado(false);
    }
    pptoReglaAplicarModoAdminUI();
}

function pptoReglaAplicarCondicion() {
    var sel = document.getElementById('form_prg_condicion_sel');
    var adv = document.getElementById('regla_condicion_avanzada');
    var campo = document.getElementById('form_prg_campo');
    var valor = document.getElementById('form_prg_valor');
    if (!sel || !adv || !campo || !valor) {
        return;
    }

    if (sel.value === '__custom__' && pptoReglaEsAdmin()) {
        adv.style.display = 'block';
        return;
    }

    if (!pptoReglaEsAdmin()) {
        adv.style.display = 'none';
    }
    var opt = sel.options[sel.selectedIndex];
    if (opt) {
        campo.value = opt.getAttribute('data-campo') || '';
        valor.value = opt.getAttribute('data-valor') || '';
    }
    pptoReglaAplicarModoAdminUI();
}

function pptoReglaSincronizarCondicionDesdeCampos() {
    var sel = document.getElementById('form_prg_condicion_sel');
    var campo = (document.getElementById('form_prg_campo').value || '').trim();
    var valor = (document.getElementById('form_prg_valor').value || '').trim();
    var monto = (document.getElementById('form_prg_cammon').value || '').trim();
    if (!sel) {
        return;
    }

    var found = false;
    for (var i = 0; i < sel.options.length; i++) {
        var opt = sel.options[i];
        if (opt.value === '__custom__') {
            continue;
        }
        if ((opt.getAttribute('data-campo') || '') === campo && (opt.getAttribute('data-valor') || '') === valor) {
            sel.selectedIndex = i;
            found = true;
            break;
        }
    }

    if (!found && (campo !== '' || valor !== '')) {
        if (pptoReglaEsAdmin()) {
            sel.value = '__custom__';
            document.getElementById('regla_condicion_avanzada').style.display = 'block';
        }
    } else {
        pptoReglaAplicarCondicion();
    }

    if (!pptoReglaEsAdmin() && !pptoReglaValorEnCatalogo(campo, valor, monto)) {
        pptoReglaMostrarBloqueoAvanzado(true);
    } else {
        pptoReglaMostrarBloqueoAvanzado(false);
    }
    pptoReglaAplicarModoAdminUI();
}

function pptoReglaAplicarMonto() {
    var sel = document.getElementById('form_prg_cammon_sel');
    var input = document.getElementById('form_prg_cammon');
    if (!sel || !input) {
        return;
    }

    if (sel.value === '__custom__' && pptoReglaEsAdmin()) {
        input.style.display = 'block';
        if (!input.value) {
            input.focus();
        }
        return;
    }

    if (!pptoReglaEsAdmin()) {
        input.style.display = 'none';
    }
    if (sel.value && sel.value !== '__custom__') {
        input.value = sel.value;
    }
    pptoReglaAplicarModoAdminUI();
}

function pptoReglaSincronizarMontoDesdeCampo() {
    var sel = document.getElementById('form_prg_cammon_sel');
    var input = document.getElementById('form_prg_cammon');
    if (!sel || !input) {
        return;
    }

    var val = (input.value || '').trim();
    var found = false;
    for (var i = 0; i < sel.options.length; i++) {
        if (sel.options[i].value === val) {
            sel.selectedIndex = i;
            found = true;
            break;
        }
    }

    if (!found && val !== '') {
        if (pptoReglaEsAdmin()) {
            sel.value = '__custom__';
            input.style.display = 'block';
        }
    } else {
        pptoReglaAplicarMonto();
    }
}

document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('form_prg_tipdoc')) {
        pptoReglaActualizarAyudas(false);
    }
});

/**
 * Abre la modal de creaciï¿½n de reglas de asignaciï¿½n con valores por defecto.
 */
function nuevaRegla() {
    document.getElementById('modal_regla_titulo').innerText = 'Nueva Regla de Asignaciï¿½n Automï¿½tica';
    document.getElementById('form_prg_cod').value = '';
    document.getElementById('form_prg_des').value = '';
    document.getElementById('form_prg_tipdoc').value = 'liquidacion_nomina';
    document.getElementById('form_prg_pri').value = '1';
    document.getElementById('form_prg_campo').value = '';
    document.getElementById('form_prg_valor').value = '';
    document.getElementById('form_prg_ppa_cod').selectedIndex = 0;
    document.getElementById('form_prg_signo').value = '+';
    document.getElementById('form_prg_cammon').value = '';
    document.getElementById('form_prg_est').value = 'A';
    pptoReglaActualizarAyudas(false);
    pptoReglaAplicarModoAdminUI();
    goStep(1);
    document.getElementById('modal_regla').style.display = 'block';
}

/**
 * Abre la modal de reglas cargando los datos para ediciï¿½n.
 *
 * @param {Object} data Datos de la regla de la BD.
 */
function editarRegla(data) {
    document.getElementById('modal_regla_titulo').innerText = 'Editar Regla de Asignaciï¿½n';
    document.getElementById('form_prg_cod').value = data.Prg_Cod;
    document.getElementById('form_prg_des').value = data.Prg_Des;
    document.getElementById('form_prg_tipdoc').value = data.Prg_TipDoc;
    document.getElementById('form_prg_pri').value = data.Prg_Pri;
    document.getElementById('form_prg_campo').value = data.prg_campo_evaluacion || '';
    document.getElementById('form_prg_valor').value = data.Prg_Valor || '';
    document.getElementById('form_prg_ppa_cod').value = data.Ppa_Cod;
    document.getElementById('form_prg_signo').value = data.Prg_Signo;
    document.getElementById('form_prg_cammon').value = data.Prg_CamMon;
    document.getElementById('form_prg_est').value = data.Prg_Est;
    pptoReglaActualizarAyudas(true);
    pptoReglaSincronizarCondicionDesdeCampos();
    pptoReglaSincronizarMontoDesdeCampo();
    pptoReglaAplicarModoAdminUI();
    goStep(1);
    document.getElementById('modal_regla').style.display = 'block';
}

/**
 * Anula (inactiva) una regla de asignacion.
 *
 * @param {number} prgId
 * @param {string} descripcion
 */
function anularRegla(prgId, descripcion) {
    prgId = parseInt(prgId, 10) || 0;
    if (!prgId) {
        return;
    }
    var lbl = descripcion || ('Regla #' + prgId);
    var msg = 'Desea anular la regla "' + lbl + '"?\n\nQuedara inactiva y dejara de aplicarse en la asignacion automatica.';
    var url = 'ppto_admin_front.php?estado_regla=' + encodeURIComponent(prgId) + '&nuevo_est=I&tab=5' + pptoAdminQsBase();
    if (typeof jQuery !== 'undefined' && jQuery.confirm) {
        jQuery.confirm(msg, function () {
            window.location.href = url;
        });
        return;
    }
    if (confirm(msg)) {
        window.location.href = url;
    }
}

/**
 * Reactiva una regla de asignacion inactiva.
 *
 * @param {number} prgId
 * @param {string} descripcion
 */
function activarRegla(prgId, descripcion) {
    prgId = parseInt(prgId, 10) || 0;
    if (!prgId) {
        return;
    }
    var lbl = descripcion || ('Regla #' + prgId);
    var msg = 'Desea reactivar la regla "' + lbl + '"?';
    var url = 'ppto_admin_front.php?estado_regla=' + encodeURIComponent(prgId) + '&nuevo_est=A&tab=5' + pptoAdminQsBase();
    if (typeof jQuery !== 'undefined' && jQuery.confirm) {
        jQuery.confirm(msg, function () {
            window.location.href = url;
        });
        return;
    }
    if (confirm(msg)) {
        window.location.href = url;
    }
}

/**
 * Enlaza botones Anular/Activar de reglas (data attributes).
 */
function pptoReglaInitAcciones() {
    if (typeof jQuery === 'undefined') {
        return;
    }
    jQuery(document).off('click.pptoReglaAn', '.btn-anular-regla').on('click.pptoReglaAn', '.btn-anular-regla', function (e) {
        e.preventDefault();
        var $b = jQuery(this);
        anularRegla($b.attr('data-prg-id'), $b.attr('data-descripcion') || '');
    });
    jQuery(document).off('click.pptoReglaAct', '.btn-activar-regla').on('click.pptoReglaAct', '.btn-activar-regla', function (e) {
        e.preventDefault();
        var $b = jQuery(this);
        activarRegla($b.attr('data-prg-id'), $b.attr('data-descripcion') || '');
    });
}

/**
 * Cierra la modal de reglas de asignaciï¿½n.
 */
function cerrarModalRegla() {
    document.getElementById('modal_regla').style.display = 'none';
}

/**
 * Controla la navegaciï¿½n del asistente (wizard) de reglas.
 *
 * @param {number} step Nï¿½mero del paso (1 a 4).
 */
function goStep(step) {
    for (var i = 1; i <= 4; i++) {
        document.getElementById('step_' + i).classList.remove('active');
        document.getElementById('step_indicator_' + i).className = 'inactive-step';
    }
    document.getElementById('step_' + step).classList.add('active');
    document.getElementById('step_indicator_' + step).className = 'active-step';

    if (step === 4) {
        document.getElementById('confirm_pri').innerText = document.getElementById('form_prg_pri').value;
        
        var campo = document.getElementById('form_prg_campo').value;
        var valor = document.getElementById('form_prg_valor').value;
        var selCond = document.getElementById('form_prg_condicion_sel');
        if (campo && valor) {
            document.getElementById('confirm_condicion').innerText = selCond && selCond.selectedIndex >= 0
                ? selCond.options[selCond.selectedIndex].text
                : ("Evalï¿½a que '" + campo + "' sea igual a '" + valor + "'");
        } else {
            document.getElementById('confirm_condicion').innerText = "Sin condiciï¿½n especial (aplica directo)";
        }

        var partSelect = document.getElementById('form_prg_ppa_cod');
        document.getElementById('confirm_partida').innerText = partSelect.options[partSelect.selectedIndex].text;
        document.getElementById('confirm_signo').innerText = document.getElementById('form_prg_signo').value;
        var selMon = document.getElementById('form_prg_cammon_sel');
        var confirmMonto = document.getElementById('confirm_monto');
        if (selMon && selMon.value !== '__custom__' && selMon.selectedIndex >= 0) {
            confirmMonto.innerText = selMon.options[selMon.selectedIndex].text;
        } else {
            confirmMonto.innerText = document.getElementById('form_prg_cammon').value;
        }
        var tipSel = document.getElementById('form_prg_tipdoc');
        if (tipSel && tipSel.selectedIndex >= 0) {
            document.getElementById('confirm_tipdoc').innerText = tipSel.options[tipSel.selectedIndex].text;
        }
    }
}

/**
 * Restringe la digitaciï¿½n en inputs exclusivamente a nï¿½meros y punto decimal.
 *
 * @param {Event} e Evento de teclado.
 * @return {boolean} Retorna true si es carï¿½cter numï¿½rico o punto, false de lo contrario.
 */
function soloNumeros(e) {
    var charCode = (e.which) ? e.which : e.keyCode;
    if (charCode != 46 && charCode > 31 && (charCode < 48 || charCode > 57)) {
        return false;
    }
    return true;
}

/**
 * Simula la carga de datos desde una plantilla Excel inyectando nï¿½meros aleatorios en la grilla.
 * (Mantiene el comportamiento actual del sistema).
 */
function simularImportacionExcel() {
    var fileInput = document.getElementById('excel_file');
    if (fileInput.files.length === 0) return;
    
    alert('Simulaciï¿½n de carga Excel: procesando registros...');
    
    document.querySelectorAll('[data-partida-cargar]').forEach(function (tr) {
        tr.querySelectorAll('.cell-cargar').forEach(function (c) {
            var valSimulado = formatNumber(Math.random() * 5000 + 500, 2);
            c.innerText = valSimulado;
            calcularTotalFilaCargar(c);
        });
    });
}

/**
 * Establece el modo de carga del presupuesto en la pestaï¿½a correspondiente.
 *
 * @param {string} modo Modo de carga ('manual', 'anual', 'copiar').
 */
function setModoCargar(modo) {
    document.getElementById('cargar_modo_input').value = modo;
    
    document.querySelectorAll('.btn-mode-cargar').forEach(function(btn) {
        btn.classList.remove('btn-primary');
        btn.classList.remove('active');
        btn.classList.add('btn-default');
    });

    var panelMan = document.getElementById('modo_manual_panel');
    var panelAnu = document.getElementById('modo_anual_panel');
    var panelCop = document.getElementById('modo_copiar_panel');
    if (panelMan) panelMan.style.display = 'none';
    if (panelAnu) panelAnu.style.display = 'none';
    if (panelCop) panelCop.style.display = 'none';

    if (modo === 'solo_cabecera') {
        modo = 'manual';
        document.getElementById('cargar_modo_input').value = modo;
    }

    if (modo === 'manual') {
        document.getElementById('btn_modo_manual').classList.remove('btn-default');
        document.getElementById('btn_modo_manual').classList.add('btn-primary');
        document.getElementById('btn_modo_manual').classList.add('active');
        if (panelMan) panelMan.style.display = 'block';
    } else if (modo === 'anual') {
        document.getElementById('btn_modo_anual').classList.remove('btn-default');
        document.getElementById('btn_modo_anual').classList.add('btn-primary');
        document.getElementById('btn_modo_anual').classList.add('active');
        if (panelAnu) panelAnu.style.display = 'block';
    } else if (modo === 'copiar') {
        document.getElementById('btn_modo_copiar').classList.remove('btn-default');
        document.getElementById('btn_modo_copiar').classList.add('btn-primary');
        document.getElementById('btn_modo_copiar').classList.add('active');
        if (panelCop) panelCop.style.display = 'block';
    }
}

/**
 * Quita msg/err/cnt de la URL tras mostrar avisos flash (evita que F5 repita el mensaje).
 */
function pptoAdminLimpiarFlashUrl() {
    if (!window.history || !window.history.replaceState) {
        return;
    }
    var params = new URLSearchParams(window.location.search);
    var flashKeys = ['msg', 'err', 'cnt'];
    var changed = false;

    flashKeys.forEach(function (key) {
        if (params.has(key)) {
            params.delete(key);
            changed = true;
        }
    });

    if (!changed) {
        return;
    }

    var qs = params.toString();
    var nuevaUrl = window.location.pathname + (qs ? '?' + qs : '') + window.location.hash;
    window.history.replaceState({}, document.title, nuevaUrl);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', pptoAdminLimpiarFlashUrl);
} else {
    pptoAdminLimpiarFlashUrl();
}
