/**
 * EXA Adquisiciones Solicitud Form JS Logic
 * @author Oz <oz-agent@warp.dev>
 */

let lineIndex = 0;
let cotIndex = 0;
let adjIndex = 0;
let decisionesFlujoCache = { decisiones: [], campos: [] };
let reqConfig = {
    Sol_Req_Fac: 1,
    Sol_Req_Cot: 1,
    Sol_Min_Cot: 1,
    Sol_Req_Pro: 0,
    Sol_Req_Adj: 0,
    Sol_Tiempo_Est: null
};

/** false = etapa actual NO permite editar/cargar proformas (Nod_Cot_Edit = 0). */
let adqEtapaPermiteCotizaciones = true;
/** false = etapa actual NO permite marcar cotizacion ganadora (Nod_Cot_Sel = 0). */
let adqEtapaPermiteSeleccionarGanadora = true;

function adqCollectRubros() {
    const ids = [];
    const seen = {};
    $('.chk-rubro-ppto:checked, #rubrosHidden input[name="rubros[]"]').each(function() {
        const v = parseInt($(this).val(), 10);
        if (v > 0 && !seen[v]) {
            seen[v] = true;
            ids.push(v);
        }
    });
    return ids;
}

function adqSyncRubrosHidden() {
    const $h = $('#rubrosHidden');
    if ($h.length) {
        $h.empty();
        adqCollectRubros().forEach(function(id) {
            $h.append($('<input type="hidden" name="rubros[]">').val(id));
        });
    }
    const n = adqCollectRubros().length;
    const txt = n + (n === 1 ? ' seleccionado' : ' seleccionados');
    $('#lblRubrosSel, #lblRubrosSelModal').text(txt);
    const $res = $('#resumenRubrosPpto');
    if ($res.length) {
        $res.empty();
        $('.chk-rubro-ppto:checked').each(function() {
            const nom = $(this).closest('tr').attr('data-rubro') || $(this).val();
            $res.append($('<span class="adq-rubro-chip"></span>').text(nom));
        });
    }
}

function adqValidarRubros() {
    if ($('#tblRubrosPpto tbody tr.adq-rubro-row').length && adqCollectRubros().length === 0) {
        alert('Debe seleccionar al menos un tipo de rubro presupuestario.');
        return false;
    }
    return true;
}

function adqAplicarRubros(ids) {
    const set = {};
    (ids || []).forEach(function(id) {
        set[parseInt(id, 10)] = true;
    });
    $('.chk-rubro-ppto').each(function() {
        $(this).prop('checked', !!set[parseInt($(this).val(), 10)]);
    });
    adqSyncRubrosHidden();
}

if (typeof window !== 'undefined') {
    window.adqCollectRubros = adqCollectRubros;
    window.adqValidarRubros = adqValidarRubros;
    window.adqAplicarRubros = adqAplicarRubros;
}

function adqSetEtapaPermiteSeleccionarGanadora(permite) {
    adqEtapaPermiteSeleccionarGanadora = !!permite;
    if (typeof window !== 'undefined') {
        window.adqEtapaPermiteSeleccionarGanadora = adqEtapaPermiteSeleccionarGanadora;
    }
    adqAplicarModoCotizacionesUi();
}

function adqSetEtapaPermiteCotizaciones(permite) {
    adqEtapaPermiteCotizaciones = !!permite;
    if (typeof window !== 'undefined') {
        window.adqEtapaPermiteCotizaciones = adqEtapaPermiteCotizaciones;
    }
    adqAplicarModoCotizacionesUi();
}

/** Muestra/oculta y bloquea controles según cargar cotizaciones vs seleccionar ganadora. */
function adqAplicarModoCotizacionesUi() {
    const verSeccion = adqEtapaPermiteCotizaciones || adqEtapaPermiteSeleccionarGanadora;
    if (!verSeccion) {
        $('#divCotizaciones').hide();
        $('#cotizacionesStateInitial').hide();
        $('#cotizacionesStateActive').hide();
        $('#divBtnAddCot').hide();
        return;
    }
    $('#divCotizaciones').show();
    $('#cotizacionesStateInitial').hide();
    $('#cotizacionesStateActive').show();
    $('#divCotizaciones').attr('data-adq-cot-edit', adqEtapaPermiteCotizaciones ? '1' : '0');
    $('#divCotizaciones').attr('data-adq-cot-sel', adqEtapaPermiteSeleccionarGanadora ? '1' : '0');

    if (adqEtapaPermiteCotizaciones) {
        $('#divBtnAddCot').show();
        $('#cotizacionesList').find('input, select, textarea, button').prop('disabled', false).removeAttr('disabled');
        $('#cotizacionesList').find('.select2-hidden-accessible').prop('disabled', false);
        $('#cotizacionesList .adq-proforma-remove, #cotizacionesList .adq-btn-add-pdf-cot, #cotizacionesList .adq-cot-remove').show();
        $('#cotizacionesList .adq-file-upload').show();
    } else {
        // Solo seleccionar ganadora: ver proformas existentes, sin alta/edicion.
        $('#divBtnAddCot').hide();
        $('#cotizacionesList').find('input, select, textarea, button').prop('disabled', true);
        $('#cotizacionesList').find('.select2-hidden-accessible').prop('disabled', true);
        $('#cotizacionesList .adq-proforma-remove, #cotizacionesList .adq-btn-add-pdf-cot, #cotizacionesList .adq-cot-remove').hide();
        $('#cotizacionesList .adq-file-upload input[type="file"]').closest('.adq-file-upload').hide();
    }

    if (adqEtapaPermiteSeleccionarGanadora) {
        $('#cotizacionesList .adq-cot-winner')
            .removeClass('adq-cot-winner-off')
            .addClass('adq-cot-winner-on')
            .css('display', 'inline-flex')
            .show();
        $('#cotizacionesList .chk-cot-sel')
            .prop('disabled', false)
            .removeAttr('disabled')
            .css({ 'pointer-events': 'auto', opacity: 1 });
        $('#cotizacionesList .div-just-cot textarea').prop('disabled', false).removeAttr('disabled');
    } else {
        $('#cotizacionesList .adq-cot-winner')
            .removeClass('adq-cot-winner-on')
            .addClass('adq-cot-winner-off')
            .hide();
        $('#cotizacionesList .chk-cot-sel').prop('disabled', true);
        $('#cotizacionesList .div-just-cot textarea').prop('disabled', true);
    }
}

/** URL de documento desde flujo/FRONT (nuevo documentos_flujo o legado DATA). */
function adqUrlDocumento(path) {
    path = String(path || '').replace(/\\/g, '/').replace(/^\/+/, '');
    if (!path) {
        return '';
    }
    if (path.indexOf('documentos_flujo/') === 0) {
        return '../' + path;
    }
    if (path.indexOf('DATA/') === 0) {
        return '../../' + path;
    }
    if (path.indexOf('adquisiciones_sustentos/') === 0) {
        return '../../DATA/' + path;
    }
    if (path.indexOf('../') === 0 || path.indexOf('http') === 0) {
        return path;
    }
    return '../documentos_flujo/' + path;
}

function syncReqConfigFromForm() {
    reqConfig.Sol_Req_Fac = $('#Sol_Req_Fac').is(':checked') ? 1 : 0;
    reqConfig.Sol_Req_Cot = $('#Sol_Req_Cot').is(':checked') ? 1 : 0;
    reqConfig.Sol_Min_Cot = parseInt($('#Sol_Min_Cot').val(), 10) || 1;
    reqConfig.Sol_Req_Pro = $('#Sol_Req_Pro').is(':checked') ? 1 : 0;
    reqConfig.Sol_Req_Adj = $('#Sol_Req_Adj').is(':checked') ? 1 : 0;
    reqConfig.Sol_Tiempo_Est = $('#Sol_Define_Sla').is(':checked') ? (parseInt($('#Sol_Tiempo_Est').val(), 10) || null) : null;
}

function toggleMinCotizaciones() {
    const on = $('#Sol_Req_Cot').is(':checked');
    const $input = $('#Sol_Min_Cot');
    $('#divSolMinCot').toggle(on);
    // El minimo de cotizaciones lo define el tipo de requerimiento: visible pero bloqueado.
    $input.prop('readonly', true);
    if (on && (!$input.val() || parseInt($input.val(), 10) < 1)) {
        $input.val(1);
    }
}

function toggleSlaDias() {
    const on = $('#Sol_Define_Sla').is(':checked');
    const $input = $('#Sol_Tiempo_Est');
    $('#divSolTiempoEst').toggle(on);
    if (!on) {
        $input.prop('disabled', true).removeAttr('min').val('');
    } else {
        $input.prop('disabled', false).attr('min', '1');
    }
}

function toggleProveedorSugerido() {
    if ($('#Sol_Req_Pro').is(':checked')) {
        $('#divProveedorSugerido').show();
        $('#Prv_Sug').prop('required', true);
        if (!$('#Prv_Sug').hasClass('select2-hidden-accessible')) {
            const valGuardado = $('#Prv_Sug').val();
            const txtGuardado = $('#Prv_Sug option:selected').text();
            setupProveedorSugeridoSelect();
            if (valGuardado) {
                if (!$('#Prv_Sug').find('option[value="' + valGuardado + '"]').length) {
                    $('#Prv_Sug').append(new Option(txtGuardado, valGuardado, true, true));
                }
                $('#Prv_Sug').val(valGuardado).trigger('change');
            }
        } else {
            $('#Prv_Sug').next('.select2-container').css('width', '100%');
        }
    } else {
        $('#divProveedorSugerido').hide();
        $('#Prv_Sug').prop('required', false).val(null).trigger('change');
        if ($('#Prv_Sug').hasClass('select2-hidden-accessible')) {
            $('#Prv_Sug').select2('destroy');
        }
    }
    $('#divJustificacionComercial .form-label-req').css({ display: 'block', visibility: 'visible' });
}

function aplicarReglasCotizaciones() {
    // Si la etapa no permite cargar ni seleccionar ganadora, ocultar seccion.
    if (!adqEtapaPermiteCotizaciones && !adqEtapaPermiteSeleccionarGanadora) {
        adqAplicarModoCotizacionesUi();
        return;
    }
    syncReqConfigFromForm();
    adqAplicarModoCotizacionesUi();

    const esObservada = $('#Sol_Modo_Edicion').val() === 'observada';
    const accionGuardar = esObservada ? 'guardar correccion' : 'guardar borrador';
    const accionEnviar = esObservada ? 'reenviar correccion' : 'enviar a aprobacion';
    const soloGanadora = !adqEtapaPermiteCotizaciones && adqEtapaPermiteSeleccionarGanadora;

    if (soloGanadora) {
        $('#cotizacionesAlert')
            .removeClass('alert-info')
            .addClass('alert-warning')
            .html(`<i class="bi bi-trophy-fill text-warning" style="font-size: 14px; margin-right: 6px;"></i> <strong>SELECCIONAR GANADORA:</strong> marque con el trofeo la cotizacion ganadora. No puede cargar ni editar proformas en esta etapa.`);
        return;
    }

    if (parseInt(reqConfig.Sol_Req_Cot, 10) === 1) {
        asegurarCotizacionesMinimas();
        const min = parseInt(reqConfig.Sol_Min_Cot, 10) || 1;
        const total = contarCotizacionesEnFormulario();
        let msgGanadora = adqEtapaPermiteSeleccionarGanadora
            ? ' Marque tambien la <strong>cotizacion ganadora</strong>.'
            : ' La seleccion de ganadora se realiza en otra etapa.';
        $('#cotizacionesAlert')
            .removeClass('alert-info')
            .addClass('alert-warning')
            .html(`<i class="bi bi-info-circle-fill text-warning" style="font-size: 14px; margin-right: 6px;"></i> <strong>AL ${accionEnviar.toUpperCase()}:</strong> debera registrar al menos <strong>${min}</strong> cotizacion(es) con proveedor, monto y <strong>PDF obligatorio</strong>. Hay <strong>${total}</strong> formulario(s) en pantalla; puede <strong>anadir mas</strong> o <strong>${accionGuardar}</strong> ahora y completarlas despues.${msgGanadora}`);
    } else {
        $('#cotizacionesAlert')
            .removeClass('alert-warning')
            .addClass('alert-info')
            .html(`<i class="bi bi-info-circle-fill text-info" style="font-size: 14px; margin-right: 6px;"></i> <strong>OPCIONAL:</strong> Para esta solicitud no es obligatorio adjuntar cotizaciones.`);
    }
}

function contarCotizacionesEnFormulario() {
    return $('#cotizacionesList .adq-proforma-row').length;
}

function asegurarCotizacionesMinimas() {
    if (!adqEtapaPermiteCotizaciones) {
        return;
    }
    if (parseInt(reqConfig.Sol_Req_Cot, 10) !== 1) {
        return;
    }
    if (!$('#cotizacionesStateActive').is(':visible')) {
        return;
    }
    const min = Math.max(1, parseInt(reqConfig.Sol_Min_Cot, 10) || 1);
    const actuales = contarCotizacionesEnFormulario();
    const faltantes = min - actuales;
    for (let i = 0; i < faltantes; i++) {
        agregarCotizacionHTML();
    }
}

function aplicarRequisitosDesdeSolicitud(s) {
    $('#divRequisitosSolicitud').show();
    $('#Sol_Req_Fac').prop('checked', parseInt(s.Sol_Req_Fac, 10) === 1);
    $('#Sol_Per_Cie').prop('checked', parseInt(s.Sol_Per_Cie, 10) === 1);
    $('#Sol_Req_Cot').prop('checked', parseInt(s.Sol_Req_Cot, 10) === 1);
    $('#Sol_Min_Cot').val(s.Sol_Min_Cot || 1);
    $('#Sol_Req_Pre').prop('checked', parseInt(s.Sol_Req_Pre, 10) === 1);
    $('#Sol_Req_Adj').prop('checked', parseInt(s.Sol_Req_Adj, 10) === 1);
    $('#Sol_Req_Pro').prop('checked', parseInt(s.Sol_Req_Pro, 10) === 1);
    if (s.Sol_Tiempo_Est !== null && s.Sol_Tiempo_Est !== '' && s.Sol_Tiempo_Est !== undefined) {
        $('#Sol_Define_Sla').prop('checked', true);
        $('#Sol_Tiempo_Est').val(s.Sol_Tiempo_Est);
    } else {
        $('#Sol_Define_Sla').prop('checked', false);
        $('#Sol_Tiempo_Est').val('');
    }
    toggleMinCotizaciones();
    toggleSlaDias();
    toggleProveedorSugerido();
    syncReqConfigFromForm();
}

function evalCondicionDecisionLocal(cond, valores) {
    if (!cond || !cond.campo) return true;
    var campo = cond.campo === 'Dep_Cod' ? 'Dep_Sol' : cond.campo;
    var real = valores[campo];
    if (real === undefined || real === null || real === '') return false;
    var op = cond.operador || '=';
    var esperado = cond.valor;
    var nReal = parseFloat(real);
    var nEsp = parseFloat(esperado);
    var numOk = !isNaN(nReal) && !isNaN(nEsp);
    switch (op) {
        case '>': return numOk ? nReal > nEsp : false;
        case '<': return numOk ? nReal < nEsp : false;
        case '>=': return numOk ? nReal >= nEsp : false;
        case '<=': return numOk ? nReal <= nEsp : false;
        case '!=': return String(real) !== String(esperado);
        case '=':
        default: return String(real) === String(esperado);
    }
}

function leerValoresDecisionCompleta() {
    var vals = {};
    $('#camposDecisionesCompleta [data-decision-campo]').each(function() {
        vals[$(this).data('decision-campo')] = $(this).val();
    });
    if (vals.Sol_Val_Est === undefined || vals.Sol_Val_Est === '') {
        vals.Sol_Val_Est = $('#Sol_Val_Est').val() || '';
    }
    if (vals.Sol_Pri === undefined || vals.Sol_Pri === '') {
        vals.Sol_Pri = $('#Sol_Pri').val() || '';
    }
    if (vals.Sol_Tiempo_Est === undefined || vals.Sol_Tiempo_Est === '') {
        vals.Sol_Tiempo_Est = $('#Sol_Tiempo_Est').val() || '';
    }
    return vals;
}

function actualizarPreviewRamasCompleta() {
    var $box = $('#previewRamasCompleta').empty();
    if (!decisionesFlujoCache.decisiones || !decisionesFlujoCache.decisiones.length) return;
    var vals = leerValoresDecisionCompleta();
    var html = '<div class="small"><strong>Ruta estimada:</strong><ul class="mb-0 mt-1">';
    decisionesFlujoCache.decisiones.forEach(function(dec) {
        var elegida = null;
        var defecto = null;
        (dec.ramas || []).forEach(function(r) {
            if (parseInt(r.es_default, 10) === 1) {
                defecto = r;
                return;
            }
            if (!elegida && evalCondicionDecisionLocal(r.condicion, vals)) {
                elegida = r;
            }
        });
        var rama = elegida || defecto;
        var destino = rama ? (rama.Destino_Nom || ('Nodo ' + rama.Nod_Des)) : '(sin destino)';
        var texto = rama ? rama.texto : 'Sin coincidencia';
        html += '<li><span class="fw-bold">' + $('<div>').text(dec.Nod_Nom).html() + '</span>: ' +
            $('<div>').text(texto).html() + ' → <em>' + $('<div>').text(destino).html() + '</em></li>';
    });
    html += '</ul></div>';
    $box.html(html);
}

function renderCamposDecisionCompleta(info, valoresPrevios) {
    decisionesFlujoCache = info || { decisiones: [], campos: [] };
    valoresPrevios = valoresPrevios || {};
    var $panel = $('#panelDecisionesCompleta');
    var $campos = $('#camposDecisionesCompleta').empty();
    if (!info || !info.campos || !info.campos.length) {
        $panel.hide();
        $('#previewRamasCompleta').empty();
        return;
    }
    info.campos.forEach(function(c) {
        if (c.campo === 'Sol_Cod' || c.campo === 'Trq_Cod') return;
        var $col = $('<div class="col-md-6 adq-field-block"></div>');
        var $lab = $('<label class="form-label-req"></label>').text(c.etiqueta + ' *');
        var $input;
        if (c.opciones && c.opciones.length) {
            $input = $('<select class="form-control form-control-adq" required></select>');
            $input.append('<option value="">[Seleccione]</option>');
            c.opciones.forEach(function(op) {
                $input.append($('<option></option>').val(op).text(op));
            });
        } else {
            $input = $('<input class="form-control form-control-adq" required />');
            $input.attr('type', c.tipo === 'number' ? 'number' : 'text');
            if (c.tipo === 'number') {
                $input.attr({ step: '0.01', min: '0' });
                if (c.campo === 'Sol_Val_Est') {
                    $input.attr('min', '0.01');
                    $input.attr('placeholder', '00.00');
                }
            }
        }
        $input.attr({
            name: 'decision_vals[' + c.campo + ']',
            'data-decision-campo': c.campo,
            id: 'dec_completa_' + c.campo
        });
        var prev = valoresPrevios[c.campo];
        if (prev === undefined || prev === null || prev === '') {
            if (c.campo === 'Sol_Val_Est') prev = $('#Sol_Val_Est').val();
            if (c.campo === 'Sol_Pri') prev = $('#Sol_Pri').val();
            if (c.campo === 'Sol_Tiempo_Est') prev = $('#Sol_Tiempo_Est').val();
        }
        if (prev !== undefined && prev !== null && prev !== '') {
            $input.val(prev);
        }
        $input.on('input change', function() {
            if (c.campo === 'Sol_Val_Est') {
                sincronizarItemEstimadoDesdeValor($(this).val());
            }
            actualizarPreviewRamasCompleta();
        });
        $col.append($lab).append($input);
        $campos.append($col);
    });
    $panel.show();
    actualizarPreviewRamasCompleta();
}

function cargarDecisionesFlujo(trqCod, valoresPrevios) {
    if (!trqCod) {
        renderCamposDecisionCompleta({ decisiones: [], campos: [] });
        return;
    }
    $.getJSON('adq_solicitud.php', { ajax_get_decisiones_flujo: 1, trq_cod: trqCod }, function(res) {
        renderCamposDecisionCompleta(res && res.success ? res : { decisiones: [], campos: [] }, valoresPrevios || {});
    }).fail(function() {
        renderCamposDecisionCompleta({ decisiones: [], campos: [] });
    });
}

let seleccionUsuariosPack = { activo: false, nodos: [], success: false };
let seleccionUsuariosValores = {};
let seleccionUsuariosPendienteEnvio = false;

function escHtml(str) {
    return $('<div>').text(str == null ? '' : String(str)).html();
}

function nodosSeleccionables(pack) {
    pack = pack || seleccionUsuariosPack;
    if (!pack || !pack.activo || !pack.nodos || !pack.nodos.length) {
        return [];
    }
    return pack.nodos
        .filter(function(nodo) {
            return (parseInt(nodo.Nod_Cod, 10) || 0) > 0 && nodo.usuarios && nodo.usuarios.length >= 2;
        })
        .slice()
        .sort(function(a, b) {
            return (parseInt(a.Nod_Cod, 10) || 0) - (parseInt(b.Nod_Cod, 10) || 0);
        });
}

function syncHiddenNodoUsuarios() {
    var $box = $('#nodoUsuariosHidden');
    if (!$box.length) {
        return;
    }
    $box.empty();
    Object.keys(seleccionUsuariosValores || {}).forEach(function(nodCod) {
        var usu = seleccionUsuariosValores[nodCod];
        if (!usu) return;
        $box.append(
            $('<input type="hidden">').attr({
                name: 'nodo_usuarios[' + nodCod + ']',
                value: usu
            })
        );
    });
}

function actualizarResumenSeleccionUsuarios() {
    var $panel = $('#panelSeleccionUsuariosNodos');
    if (!$panel.length) {
        return;
    }
    var nodos = nodosSeleccionables();
    if (!seleccionUsuariosPack.activo) {
        if (seleccionUsuariosPack.message) {
            $('#lblRespSummaryTitle').text('Asignación de responsables');
            $('#lblRespSummaryDesc').text(seleccionUsuariosPack.message);
            $('#btnAbrirModalResponsables').hide();
            $panel.show();
        } else {
            $panel.hide();
        }
        syncHiddenNodoUsuarios();
        return;
    }
    if (!nodos.length) {
        $('#lblRespSummaryTitle').text('Asignación de responsables');
        $('#lblRespSummaryDesc').text(
            seleccionUsuariosPack.message || 'La opción está activa, pero ningún nodo tiene más de un usuario asignado.'
        );
        $('#btnAbrirModalResponsables').hide();
        $panel.show();
        syncHiddenNodoUsuarios();
        return;
    }
    var total = nodos.length;
    var asignados = 0;
    nodos.forEach(function(n) {
        var cod = String(n.Nod_Cod);
        if (seleccionUsuariosValores[cod] || seleccionUsuariosValores[n.Nod_Cod]) {
            asignados++;
        }
    });
    $('#lblRespSummaryTitle').text('Asignación de responsables por etapa (obligatorio)');
    $('#lblRespSummaryDesc').text(
        asignados >= total
            ? ('Listo: ' + asignados + ' de ' + total + ' etapas con responsable definido.')
            : ('Debe elegir un responsable en cada etapa (' + asignados + ' / ' + total + '). Sin esto no podrá enviar la solicitud.')
    );
    $('#btnAbrirModalResponsables')
        .show()
        .html(
            asignados >= total
                ? '<i class="bi bi-pencil-square"></i> Revisar responsables'
                : '<i class="bi bi-person-check"></i> Asignar responsables'
        );
    $panel.show();
    syncHiddenNodoUsuarios();
}

function actualizarProgresoModalResponsables() {
    var nodos = nodosSeleccionables();
    var total = nodos.length;
    var asignados = 0;
    $('#seleccionUsuariosNodosList .adq-nodo-usu-card').each(function() {
        var $card = $(this);
        var checked = $card.find('.chk-nodo-usu:checked').length > 0;
        $card.toggleClass('is-complete', checked);
        if (checked) {
            $card.removeClass('is-missing');
            asignados++;
        }
    });
    $('#lblRespProgress').text(
        asignados + ' / ' + total + ' etapas asignadas' +
        (asignados < total ? ' (obligatorio completar todas)' : '')
    );
}

function marcarEtapasSinResponsable() {
    var faltantes = [];
    $('#seleccionUsuariosNodosList .adq-nodo-usu-card').each(function() {
        var $card = $(this);
        var checked = $card.find('.chk-nodo-usu:checked').length > 0;
        $card.toggleClass('is-missing', !checked);
        $card.toggleClass('is-complete', checked);
        if (!checked) {
            faltantes.push($.trim($card.find('.adq-nodo-usu-title').first().text()) || 'etapa');
        }
    });
    return faltantes;
}

function validarSeleccionUsuariosNodos(silentOpen) {
    var nodos = nodosSeleccionables();
    if (!seleccionUsuariosPack.activo || !nodos.length) {
        return true;
    }
    var faltantes = [];
    nodos.forEach(function(nodo) {
        var nodCod = String(nodo.Nod_Cod);
        if (!seleccionUsuariosValores[nodCod] && !seleccionUsuariosValores[nodo.Nod_Cod]) {
            faltantes.push(nodo.Nod_Nom || ('Nodo #' + nodCod));
        }
    });
    if (faltantes.length) {
        if (!silentOpen) {
            abrirModalSeleccionResponsables(true);
            setTimeout(function() {
                marcarEtapasSinResponsable();
            }, 250);
            return false;
        }
        marcarEtapasSinResponsable();
        alert(
            'La asignación de responsable es obligatoria en cada etapa.\n\nPendiente:\n- ' +
            faltantes.join('\n- ')
        );
        return false;
    }
    syncHiddenNodoUsuarios();
    return true;
}

function renderSeleccionUsuariosNodos(pack, valoresPrevios) {
    seleccionUsuariosPack = pack && typeof pack === 'object'
        ? pack
        : { activo: false, nodos: [], success: false };
    if (valoresPrevios && typeof valoresPrevios === 'object') {
        seleccionUsuariosValores = {};
        Object.keys(valoresPrevios).forEach(function(k) {
            if (valoresPrevios[k]) {
                seleccionUsuariosValores[String(k)] = String(valoresPrevios[k]);
            }
        });
    } else if (arguments.length >= 2) {
        seleccionUsuariosValores = {};
    }
    actualizarResumenSeleccionUsuarios();
    if ($('#mdlSeleccionResponsables').hasClass('in') || $('#mdlSeleccionResponsables').hasClass('show')) {
        pintarModalSeleccionResponsables();
    }
}

function pintarModalSeleccionResponsables() {
    var $list = $('#seleccionUsuariosNodosList');
    var $empty = $('#seleccionUsuariosNodosEmpty');
    if (!$list.length) {
        return;
    }
    $list.empty();
    var nodos = nodosSeleccionables();
    if (!seleccionUsuariosPack.activo) {
        $empty
            .html('<i class="bi bi-info-circle d-block mb-2" style="font-size:22px;"></i>' + escHtml(seleccionUsuariosPack.message || 'La selección de responsables no está habilitada para este flujo.'))
            .show();
        $('#btnConfirmarResponsables').prop('disabled', true);
        $('#lblRespProgress').text('0 / 0 etapas asignadas');
        return;
    }
    if (!nodos.length) {
        $empty
            .html('<i class="bi bi-info-circle d-block mb-2" style="font-size:22px;"></i>' + escHtml(seleccionUsuariosPack.message || 'Ningún nodo tiene más de un usuario asignado.'))
            .show();
        $('#btnConfirmarResponsables').prop('disabled', true);
        $('#lblRespProgress').text('0 / 0 etapas asignadas');
        return;
    }
    $empty.hide();
    $('#btnConfirmarResponsables').prop('disabled', false);

    nodos.forEach(function(nodo) {
        var nodCod = parseInt(nodo.Nod_Cod, 10) || 0;
        var prev = seleccionUsuariosValores[String(nodCod)] || seleccionUsuariosValores[nodCod] || '';
        var tip = (nodo.Nod_Tip || '').toUpperCase();
        var $card = $('<div class="adq-nodo-usu-card"></div>').attr('data-nod-cod', nodCod);
        var $head = $('<div class="adq-nodo-usu-head"></div>');
        var $titleWrap = $('<div></div>');
        $titleWrap.append(
            $('<p class="adq-nodo-usu-title"></p>').text(nodo.Nod_Nom || ('Nodo #' + nodCod))
        );
        $titleWrap.append(
            $('<span class="adq-nodo-usu-subtitle"></span>').text('Obligatorio: seleccione un responsable')
        );
        $head.append($titleWrap);
        if (tip) {
            $head.append($('<span class="adq-nodo-usu-badge"></span>').text(tip));
        }
        $card.append($head);

        var $opts = $('<div class="adq-nodo-usu-options"></div>');
        nodo.usuarios.forEach(function(u) {
            var usuCod = parseInt(u.Usu_Cod, 10) || 0;
            if (!usuCod) return;
            var id = 'nodo_usu_' + nodCod + '_' + usuCod;
            var selected = String(prev) === String(usuCod);
            var $lab = $('<label class="adq-nodo-usu-option"></label>')
                .attr('for', id)
                .toggleClass('is-selected', selected);
            var $chk = $('<input type="checkbox" class="chk-nodo-usu form-check-input">')
                .attr({
                    id: id,
                    name: 'modal_nodo_usuarios_' + nodCod,
                    value: usuCod,
                    'data-nod-cod': nodCod
                })
                .prop('checked', selected);
            $lab.append($chk).append($('<span></span>').text(u.Nombre || ('Usuario #' + usuCod)));
            $opts.append($lab);
        });
        $card.append($opts);
        $list.append($card);
    });

    $list.off('change.adqNodoUsu', '.chk-nodo-usu').on('change.adqNodoUsu', '.chk-nodo-usu', function() {
        var $t = $(this);
        var nod = $t.attr('data-nod-cod');
        var $card = $t.closest('.adq-nodo-usu-card');
        if ($t.is(':checked')) {
            $card.find('.chk-nodo-usu').not($t).prop('checked', false);
            seleccionUsuariosValores[String(nod)] = String($t.val());
        } else {
            delete seleccionUsuariosValores[String(nod)];
        }
        $card.find('.adq-nodo-usu-option').each(function() {
            var $lab = $(this);
            $lab.toggleClass('is-selected', $lab.find('.chk-nodo-usu').is(':checked'));
        });
        syncHiddenNodoUsuarios();
        actualizarProgresoModalResponsables();
        actualizarResumenSeleccionUsuarios();
    });
    actualizarProgresoModalResponsables();
}

function abrirModalSeleccionResponsables(fromSubmit) {
    seleccionUsuariosPendienteEnvio = !!fromSubmit;
    pintarModalSeleccionResponsables();
    var $mdl = $('#mdlSeleccionResponsables');
    if ($mdl.length && typeof $mdl.modal === 'function') {
        $mdl.modal('show');
    }
}

function confirmarSeleccionResponsables() {
    if (!validarSeleccionUsuariosNodos(true)) {
        return;
    }
    syncHiddenNodoUsuarios();
    actualizarResumenSeleccionUsuarios();
    $('#mdlSeleccionResponsables').modal('hide');
    if (seleccionUsuariosPendienteEnvio) {
        seleccionUsuariosPendienteEnvio = false;
        procesarSolicitud(false);
    }
}

function cargarSeleccionUsuariosFlujo(trqCod, valoresPrevios) {
    if (!trqCod && !$('#Sol_Cod').val()) {
        renderSeleccionUsuariosNodos({ activo: false, nodos: [] });
        return;
    }
    var params = {
        ajax_get_seleccion_usuarios_flujo: 1,
        trq_cod: trqCod || $('#Trq_Cod').val() || 0,
        sol_cod: $('#Sol_Cod').val() || 0
    };
    $.getJSON('adq_solicitud.php', params, function(res) {
        renderSeleccionUsuariosNodos(
            res && res.success ? res : { activo: false, nodos: [] },
            valoresPrevios || {}
        );
    }).fail(function() {
        renderSeleccionUsuariosNodos({ activo: false, nodos: [] }, {});
    });
}

function cargarConfiguracionTipo(trqCod) {
    if (!trqCod) {
        $('#divRequisitosSolicitud').hide();
        $('#divProveedorSugerido').hide();
        if (adqEtapaPermiteCotizaciones) {
            $('#cotizacionesStateInitial').show();
            $('#cotizacionesStateActive').hide();
        } else {
            adqSetEtapaPermiteCotizaciones(false);
        }
        renderCamposDecisionCompleta({ decisiones: [], campos: [] });
        renderSeleccionUsuariosNodos({ activo: false, nodos: [] });
        return;
    }

    cargarDecisionesFlujo(trqCod);
    cargarSeleccionUsuariosFlujo(trqCod);

    if ($('#Sol_Cod').val()) {
        aplicarReglasCotizaciones();
        return;
    }

    $.getJSON('adq_solicitud.php', { ajax_get_trq_details: true, trq_cod: trqCod }, function(res) {
        if (!res.success || !res.data) {
            return;
        }
        const d = res.data;
        $('#divRequisitosSolicitud').show();
        $('#Sol_Req_Fac').prop('checked', parseInt(d.Trq_Req_Fac, 10) === 1);
        $('#Sol_Per_Cie').prop('checked', parseInt(d.Trq_Per_Cie, 10) === 1);
        $('#Sol_Req_Cot').prop('checked', parseInt(d.Trq_Req_Cot, 10) === 1);
        $('#Sol_Min_Cot').val(d.Trq_Min_Cot || 1);
        $('#Sol_Req_Pre').prop('checked', parseInt(d.Trq_Req_Pre, 10) === 1);
        $('#Sol_Req_Adj').prop('checked', parseInt(d.Trq_Req_Adj, 10) === 1);
        $('#Sol_Req_Pro').prop('checked', parseInt(d.Trq_Req_Pro, 10) === 1);
        if (d.Trq_Tiempo_Est !== null && d.Trq_Tiempo_Est !== '') {
            $('#Sol_Define_Sla').prop('checked', true);
            $('#Sol_Tiempo_Est').val(d.Trq_Tiempo_Est);
        } else {
            $('#Sol_Define_Sla').prop('checked', false);
            $('#Sol_Tiempo_Est').val('');
        }
        toggleMinCotizaciones();
        toggleSlaDias();
        toggleProveedorSugerido();
        syncReqConfigFromForm();
        if (!$('#Sol_Cod').val()) {
            $('#cotizacionesList').empty();
            cotIndex = 0;
        }
        aplicarReglasCotizaciones();
        actualizarPreviewRamasCompleta();
    });
}

function setModoEdicionFormulario(modo, solNum, observacion, trqCodForzado) {
    modo = modo || '';
    const esObservada = (modo === 'observada');
    const esCotizaciones = (modo === 'cotizaciones');
    const esCompletarNodo = (modo === 'completar_nodo');
    $('#Sol_Modo_Edicion').val(modo);
    $('#bannerEdicionBorrador').toggle(modo === 'borrador');
    $('#bannerEdicionObservada').toggle(esObservada);
    $('#bannerEdicionCotizaciones').toggle(esCotizaciones);
    $('#bannerCompletarNodo').toggle(esCompletarNodo);
    $('#adqFormActionsDefault').toggle(!esObservada && !esCotizaciones);
    $('#adqFormActionsObservada').toggle(esObservada);
    $('#adqFormActionsCotizaciones').toggle(esCotizaciones);
    if (esCompletarNodo) {
        $('#adqFormActionsDefault button').not('#btnEnviarSolicitud').hide();
        $('#btnEnviarSolicitud').html('<i class="bi bi-check2-circle"></i> Completar solicitud');
    } else {
        $('#adqFormActionsDefault button').show();
        $('#btnEnviarSolicitud').html('<i class="bi bi-send-check"></i> Enviar Solicitud a Aprobación');
    }
    if (esCotizaciones) {
        $('#lblCotizacionesNum').text(solNum ? ('# ' + solNum) : '');
    }

    const $trq = $('#Trq_Cod');
    if (esObservada || esCompletarNodo || esCotizaciones) {
        const trqVal = (trqCodForzado !== undefined && trqCodForzado !== null && trqCodForzado !== '')
            ? String(trqCodForzado)
            : String($trq.val() || '');
        if (trqVal) {
            $trq.val(trqVal);
        }
        $trq.prop('disabled', false).addClass('adq-trq-readonly').attr('tabindex', '-1');
        if (!$('#Trq_Cod_Locked').length) {
            $trq.after('<input type="hidden" id="Trq_Cod_Locked" name="Trq_Cod" value="">');
        }
        $('#Trq_Cod_Locked').val(trqVal);
        $trq.removeAttr('name');
        $trq.off('mousedown.adqLock change.adqLock').on('mousedown.adqLock change.adqLock', function(e) {
            e.preventDefault();
            $(this).val(trqVal).trigger('change.select2');
            return false;
        });
        if ($trq.hasClass('select2-hidden-accessible')) {
            $trq.prop('disabled', true).trigger('change.select2');
        } else {
            $trq.prop('disabled', true);
        }
    } else {
        $trq.off('mousedown.adqLock change.adqLock');
        $trq.prop('disabled', false).removeClass('adq-trq-readonly').removeAttr('tabindex');
        if (!$trq.attr('name')) {
            $trq.attr('name', 'Trq_Cod');
        }
        $('#Trq_Cod_Locked').remove();
        setupTipoRequerimientoSelect();
    }

    if (modo === 'borrador') {
        $('#lblBorradorNum').text(solNum ? ('# ' + solNum) : '');
    }
    if (esObservada) {
        $('#lblObservadaNum').text(solNum ? ('# ' + solNum) : '');
        let detalle = '';
        if (observacion && observacion.Isn_Com) {
            detalle = ' Observacion: ' + observacion.Isn_Com;
            if (observacion.Nod_Nom) {
                detalle += ' (Etapa: ' + observacion.Nod_Nom + ')';
            }
            detalle += '.';
        }
        $('#lblObservadaDetalle').text(detalle);
        habilitarEdicionCotizacionesObservada();
    } else {
        $('#lblObservadaDetalle').text('');
    }
}

function habilitarEdicionCotizacionesObservada() {
    $('#cotizacionesStateInitial').hide();
    $('#cotizacionesStateActive').show();
    $('#divBtnAddCot').show();
    $('#cotizacionesList .chk-cot-sel').prop('disabled', false).prop('checked', false);
    $('#cotizacionesList .div-just-cot').hide();
    $('#cotizacionesList input, #cotizacionesList select, #cotizacionesList textarea').prop('disabled', false);
    $('#cotizacionesList .adq-file-upload input[type="file"]').prop('disabled', false);
}

function validarFormularioBase() {
    if (!$('#Sol_Tit').val().trim()) {
        alert('Debe ingresar el nombre de la solicitud.');
        return false;
    }
    if ($('#tblItems tbody tr').length === 0) {
        alert('Debe registrar al menos un articulo/servicio en el pedido.');
        return false;
    }
    if ((parseFloat($('#Sol_Val_Est').val()) || 0) <= 0) {
        alert('Debe ingresar un valor estimado mayor que cero.');
        return false;
    }
    if (!$('#Trq_Cod').val()) {
        alert('Debe seleccionar un Tipo de Requerimiento.');
        return false;
    }
    if (typeof window.adqValidarRubros === 'function' && !window.adqValidarRubros()) {
        return false;
    }
    if (!$('#Sol_Jus').val().trim()) {
        alert('Debe ingresar la justificacion de la solicitud.');
        return false;
    }
    return true;
}

function validarRequisitosEnvioFormulario() {
    syncReqConfigFromForm();
    if (adqEtapaPermiteCotizaciones && !validarPdfsCotizacionesFormulario(true)) {
        return false;
    }
    if (parseInt(reqConfig.Sol_Req_Pro, 10) === 1 && !$('#Prv_Sug').val()) {
        alert('Debe seleccionar un proveedor sugerido para enviar esta solicitud.');
        return false;
    }
    if (adqEtapaPermiteCotizaciones && parseInt(reqConfig.Sol_Req_Cot, 10) === 1) {
        const stats = contarCotizacionesParaEnvio();
        const minRequired = parseInt(reqConfig.Sol_Min_Cot, 10) || 1;
        if (stats.total < minRequired) {
            let msg = `Para enviar a aprobacion se requieren al menos ${minRequired} cotizacion(es) completas (proveedor, monto y PDF). Completas detectadas: ${stats.total}.`;
            if (stats.detalle.sinProveedor) {
                msg += `\n- Falta proveedor en ${stats.detalle.sinProveedor} cotizacion(es).`;
            }
            if (stats.detalle.sinMonto) {
                msg += `\n- Falta monto en ${stats.detalle.sinMonto} cotizacion(es).`;
            }
            if (stats.detalle.sinPdf) {
                msg += `\n- Falta PDF en ${stats.detalle.sinPdf} cotizacion(es).`;
            }
            alert(msg);
            return false;
        }
    }
    if (adqEtapaPermiteSeleccionarGanadora && parseInt(reqConfig.Sol_Req_Cot, 10) === 1) {
        const statsSel = contarCotizacionesParaEnvio();
        if (!statsSel.ganadora) {
            alert('Debe marcar cual de las cotizaciones cargadas es la ganadora/seleccionada.');
            return false;
        }
    }
    // Paso 2B: los PDF de soporte son opcionales; solo se valida si hay filas agregadas.
    if (!validarAdjuntosSolicitudFormulario(false)) {
        return false;
    }
    return true;
}

function construirFormDataSolicitud() {
    const formData = new FormData($('#frmSolicitud')[0]);
    formData.set('Sol_Req_Fac', reqConfig.Sol_Req_Fac);
    formData.set('Sol_Per_Cie', $('#Sol_Per_Cie').is(':checked') ? 1 : 0);
    formData.set('Sol_Req_Cot', reqConfig.Sol_Req_Cot);
    formData.set('Sol_Min_Cot', reqConfig.Sol_Min_Cot);
    formData.set('Sol_Req_Pre', $('#Sol_Req_Pre').is(':checked') ? 1 : 0);
    formData.set('Sol_Req_Adj', reqConfig.Sol_Req_Adj);
    formData.set('Sol_Req_Pro', reqConfig.Sol_Req_Pro);
    if ($('#Sol_Define_Sla').is(':checked') && $('#Sol_Tiempo_Est').val()) {
        formData.set('Sol_Define_Sla', '1');
        formData.set('Sol_Tiempo_Est', $('#Sol_Tiempo_Est').val());
    } else {
        formData.delete('Sol_Define_Sla');
        formData.delete('Sol_Tiempo_Est');
    }
    return formData;
}

function cargarBorradorEnFormulario(solCod, porNodo) {
    if (!$('#frmSolicitud').length) {
        alert('El formulario de solicitud no esta disponible en pantalla.');
        return;
    }

    $.getJSON('adq_solicitud.php', { ajax_get_borrador: true, sol_cod: solCod, por_nodo: porNodo ? 1 : 0 }, function(res) {
        if (!res.success) {
            alert('No se pudo cargar la solicitud: ' + (res.message || 'Error desconocido'));
            return;
        }
        const s = res.solicitud;
        const modo = res.modo_edicion || (s.Sol_Est === 'O' ? 'observada' : 'borrador');
        $('#Sol_Cod').val(s.Sol_Cod);
        $('#Sol_Tit').val(s.Sol_Tit || '');
        adqAsegurarTipoRequerimiento(s.Trq_Cod, s.Trq_Des);
        setModoEdicionFormulario(modo, s.Sol_Num, res.ultima_observacion || null, s.Trq_Cod);
        adqAsegurarTipoRequerimiento(s.Trq_Cod, s.Trq_Des);
        if ($('#Trq_Cod_Locked').length) {
            $('#Trq_Cod_Locked').val(s.Trq_Cod);
        }

        if (s.Prv_Sug && res.prv_sug_text) {
            $('#Prv_Sug').empty().append(new Option(res.prv_sug_text, s.Prv_Sug, true, true));
        } else {
            $('#Prv_Sug').empty().append('<option value=""></option>');
        }

        aplicarRequisitosDesdeSolicitud(s);

        $('#Sol_Pri').val(s.Sol_Pri);
        $('#Cdc_Cod').val(s.Cdc_Cod || '');
        $('#Sol_Jus').val(s.Sol_Jus);
        $('#Sol_Det').val(s.Sol_Det);

        $('#tblItems tbody').empty();
        lineIndex = 0;
        if (res.items && res.items.length) {
            res.items.forEach(function(item) {
                agregarLinea(item);
            });
        } else {
            agregarLinea(itemEstimadoDesdeValor(s.Sol_Val_Est));
        }
        recalcularTotalGeneral();

        $('#cotizacionesList').empty();
        $('#cotEliminarContainer').empty();
        cotIndex = 0;
        if (res.cotizaciones && res.cotizaciones.length) {
            res.cotizaciones.forEach(function(c) {
                agregarCotizacionExistente(c);
            });
        }

        $('#adjuntosList').empty();
        $('#adjEliminarContainer').empty();
        adjIndex = 0;
        if (res.adjuntos && res.adjuntos.length) {
            res.adjuntos.forEach(function(a) {
                agregarAdjuntoExistente(a);
            });
        }

        const puedeCotEtapa = (modo === 'observada')
            ? true
            : (modo === 'completar_nodo'
                ? (parseInt(res.puede_cargar_cotizaciones, 10) === 1)
                : true);
        const puedeSelEtapa = (modo === 'observada')
            ? true
            : (modo === 'completar_nodo'
                ? (parseInt(res.puede_seleccionar_ganadora, 10) === 1)
                : true);
        adqSetEtapaPermiteCotizaciones(puedeCotEtapa);
        adqSetEtapaPermiteSeleccionarGanadora(puedeSelEtapa);

        if (puedeCotEtapa || puedeSelEtapa) {
            $('#cotizacionesStateInitial').hide();
            $('#cotizacionesStateActive').show();
            aplicarReglasCotizaciones();
        }
        if (modo === 'observada') {
            adqSetEtapaPermiteCotizaciones(true);
            adqSetEtapaPermiteSeleccionarGanadora(true);
            $('#cotizacionesStateInitial').hide();
            $('#cotizacionesStateActive').show();
            aplicarReglasCotizaciones();
            habilitarEdicionCotizacionesObservada();
        }

        cargarDecisionesFlujo(s.Trq_Cod, res.decision_vals || {});
        cargarSeleccionUsuariosFlujo(s.Trq_Cod, res.nodo_usuarios || {});
        if (typeof window.adqAplicarRubros === 'function') {
            window.adqAplicarRubros(res.rubros || []);
        }
    }).fail(function() {
        alert('Error de red al cargar la solicitud.');
    });
}

function cargarSolicitudParaCompletar(solCod) {
    cargarBorradorEnFormulario(solCod, true);
}

function bloquearFormularioSoloCotizaciones() {
    const $form = $('#frmSolicitud');
    $form.find('input, select, textarea').prop('disabled', true);
    $form.find('button').prop('disabled', true);
    const $cotZone = $('#cotizacionesList, #divBtnAddCot, #cotizacionesStateActive');
    $cotZone.find('input, select, textarea, button').prop('disabled', false);
    $('#adqFormActionsCotizaciones button').prop('disabled', false);
    $('#Sol_Cod').prop('disabled', false);
    adqAplicarModoCotizacionesUi();
}

function cargarSolicitudParaCotizaciones(solCod) {
    if (!$('#frmSolicitud').length) {
        alert('El formulario de solicitud no esta disponible en pantalla.');
        return;
    }

    $.getJSON('adq_solicitud.php', { ajax_get_solicitud_cot: true, sol_cod: solCod }, function(res) {
        if (!res.success) {
            alert('No se pudo abrir la carga de cotizaciones: ' + (res.message || 'Error desconocido'));
            return;
        }
        const s = res.solicitud;
        $('#Sol_Cod').val(s.Sol_Cod);
        $('#Sol_Tit').val(s.Sol_Tit || '');
        adqAsegurarTipoRequerimiento(s.Trq_Cod, s.Trq_Des);
        setModoEdicionFormulario('cotizaciones', s.Sol_Num, null, s.Trq_Cod);
        adqSetEtapaPermiteCotizaciones(parseInt(res.puede_cargar_cotizaciones, 10) === 1);
        adqSetEtapaPermiteSeleccionarGanadora(parseInt(res.puede_seleccionar_ganadora, 10) === 1);
        const soloGanadora = !adqEtapaPermiteCotizaciones && adqEtapaPermiteSeleccionarGanadora;
        const $btnGuardarCot = $('#btnGuardarCotizacionesEtapa');
        if ($btnGuardarCot.length) {
            $btnGuardarCot.html(soloGanadora
                ? '<i class="bi bi-trophy"></i> Guardar cotización ganadora'
                : '<i class="bi bi-save"></i> Guardar Cotizaciones');
        }
        $('#lblCotizacionesEtapa').text(res.etapa_nombre ? ('Etapa actual: ' + res.etapa_nombre + '. ') : '');

        // Reaplicar titulo/tipo tras setModo (bloqueo/Select2).
        $('#Sol_Tit').val(s.Sol_Tit || '');
        adqAsegurarTipoRequerimiento(s.Trq_Cod, s.Trq_Des);

        if (s.Prv_Sug && res.prv_sug_text) {
            $('#Prv_Sug').empty().append(new Option(res.prv_sug_text, s.Prv_Sug, true, true));
        } else {
            $('#Prv_Sug').empty().append('<option value=""></option>');
        }

        aplicarRequisitosDesdeSolicitud(s);

        $('#Sol_Pri').val(s.Sol_Pri);
        $('#Cdc_Cod').val(s.Cdc_Cod || '');
        $('#Sol_Jus').val(s.Sol_Jus);
        $('#Sol_Det').val(s.Sol_Det);

        $('#tblItems tbody').empty();
        lineIndex = 0;
        if (res.items && res.items.length) {
            res.items.forEach(function(item) {
                agregarLinea(item);
            });
        } else {
            agregarLinea(itemEstimadoDesdeValor(s.Sol_Val_Est));
        }
        recalcularTotalGeneral();

        $('#cotizacionesList').empty();
        $('#cotEliminarContainer').empty();
        cotIndex = 0;
        if (res.cotizaciones && res.cotizaciones.length) {
            res.cotizaciones.forEach(function(c) {
                agregarCotizacionExistente(c);
            });
        }

        $('#cotizacionesStateInitial').hide();
        $('#cotizacionesStateActive').show();
        aplicarReglasCotizaciones();
        bloquearFormularioSoloCotizaciones();

        // Mantener visibles titulo y tipo aunque el resto quede bloqueado.
        $('#Sol_Tit').prop('disabled', true);
        adqAsegurarTipoRequerimiento(s.Trq_Cod, s.Trq_Des);
        $('#Trq_Cod').prop('disabled', true);
        if ($('#Trq_Cod').hasClass('select2-hidden-accessible')) {
            $('#Trq_Cod').trigger('change.select2');
        }
    }).fail(function() {
        alert('Error de red al cargar la solicitud.');
    });
}

function guardarCotizacionesEtapa() {
    const solCod = parseInt($('#Sol_Cod').val(), 10);
    if (!solCod) {
        alert('No se encontro la solicitud.');
        return;
    }
    if (!validarPdfsCotizacionesFormulario(true)) {
        return;
    }

    const formData = new FormData($('#frmSolicitud')[0]);
    formData.set('Sol_Cod', solCod);

    const $btn = $('#adqFormActionsCotizaciones button');
    const original = $btn.html();
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

    $.ajax({
        url: 'adq_solicitud.php?ajax_save_cotizaciones=1',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                alert('Cotizaciones guardadas correctamente en la solicitud # ' + res.Num + '.');
                window.location.href = 'adq_bandeja.php';
            } else {
                alert('Error al guardar las cotizaciones: ' + (res.message || 'Error desconocido'));
                $btn.prop('disabled', false).html(original);
            }
        },
        error: function() {
            alert('Error de red al guardar las cotizaciones.');
            $btn.prop('disabled', false).html(original);
        }
    });
}

function contarCotizacionesParaEnvio() {
    let total = 0;
    let ganadora = false;
    const detalle = { sinProveedor: 0, sinMonto: 0, sinPdf: 0 };

    $('#cotizacionesList .adq-proforma-row').each(function() {
        const $row = $(this);
        const $box = $row.closest('.adq-cot-col');
        const prv = obtenerValorProveedorCot($box);
        const val = obtenerMontoProforma($row);
        const hasPdf = proformaTieneAdjunto($row);
        const parcial = !!(prv || val > 0 || hasPdf);

        if (prv && val > 0 && hasPdf) {
            total++;
        } else if (parcial) {
            if (!prv) {
                detalle.sinProveedor++;
            }
            if (!(val > 0)) {
                detalle.sinMonto++;
            }
            if (!hasPdf) {
                detalle.sinPdf++;
            }
        }

        if ($row.find('.chk-cot-sel').is(':checked')) {
            ganadora = true;
        }
    });

    return { total: total, ganadora: ganadora, detalle: detalle };
}

function obtenerValorProveedorCot($box) {
    const $sel = $box.find('select.select2-prov-cot');
    if ($sel.length) {
        return $sel.val();
    }
    return $box.find('.cot-prv-hidden').first().val() || '';
}

function obtenerMontoProforma($row) {
    const rawSub = $row.find('input[name*="[Cot_Sub]"]').val();
    if (rawSub !== undefined && rawSub !== null && String(rawSub).trim() !== '') {
        return parseFloat(String(rawSub || '').replace(',', '.')) || 0;
    }
    const raw = $row.find('input[name*="[Cot_Val]"]').val();
    return parseFloat(String(raw || '').replace(',', '.')) || 0;
}

function obtenerMontoCot($box) {
    const $row = $box.find('.adq-proforma-row').first();
    if ($row.length) {
        return obtenerMontoProforma($row);
    }
    const rawSub = $box.find('input[name*="[Cot_Sub]"]').val();
    if (rawSub !== undefined && rawSub !== null && String(rawSub).trim() !== '') {
        return parseFloat(String(rawSub || '').replace(',', '.')) || 0;
    }
    const raw = $box.find('input[name*="[Cot_Val]"]').val();
    return parseFloat(String(raw || '').replace(',', '.')) || 0;
}

const ADQ_COT_IVA_FACTOR = 1.15;

function recalcularTotalesProformaRow($row) {
    if (!$row || !$row.length) {
        return;
    }
    const sub = parseFloat(String($row.find('input[name*="[Cot_Sub]"]').val() || '').replace(',', '.')) || 0;
    const conIva = $row.find('.chk-cot-iva').is(':checked');
    const total = Math.round(sub * (conIva ? ADQ_COT_IVA_FACTOR : 1) * 100) / 100;
    $row.find('input[name*="[Cot_Val]"]').val(total > 0 ? total.toFixed(2) : '');
    $row.find('.adq-cot-total-view').text(total.toFixed(2));
}

function setupProformaMontos($scope) {
    const $root = $scope && $scope.length ? $scope : $(document);
    $root.find('.adq-proforma-row').each(function() {
        recalcularTotalesProformaRow($(this));
    });
    $root.find('input[name*="[Cot_Sub]"], .chk-cot-iva').off('input.adqCotMontos change.adqCotMontos')
        .on('input.adqCotMontos change.adqCotMontos', function() {
            recalcularTotalesProformaRow($(this).closest('.adq-proforma-row'));
        });
}

function setupTipoRequerimientoSelect() {
    const $el = $('#Trq_Cod');
    if (!$el.length || typeof $.fn.select2 !== 'function') {
        return;
    }
    if ($el.hasClass('select2-hidden-accessible')) {
        $el.select2('destroy');
    }
    $el.select2({
        placeholder: '[Seleccione un Tipo]',
        allowClear: true,
        width: '100%',
        dropdownCssClass: 'adq-select2-dropdown',
        language: {
            noResults: function() { return 'Sin resultados'; },
            searching: function() { return 'Buscando...'; }
        }
    });
    $el.off('change.adqTrqCfg').on('change.adqTrqCfg', function() {
        if (typeof cargarConfiguracionTipo === 'function') {
            cargarConfiguracionTipo($(this).val());
        }
    });
}

/**
 * Asegura que el tipo exista en el combo y quede seleccionado (incluye Select2).
 * Necesario al editar: el listado de creacion puede no incluir el Trq de la solicitud.
 */
function adqAsegurarTipoRequerimiento(trqCod, trqDes) {
    const $trq = $('#Trq_Cod');
    if (!$trq.length || !trqCod) {
        return;
    }
    const val = String(trqCod);
    let $opt = $trq.find('option[value="' + val.replace(/"/g, '\\"') + '"]');
    if (!$opt.length) {
        const label = (trqDes && String(trqDes).trim() !== '') ? String(trqDes) : ('Tipo #' + val);
        $trq.append(new Option(label, val, true, true));
    }
    $trq.val(val);
    if ($('#Trq_Cod_Locked').length) {
        $('#Trq_Cod_Locked').val(val);
    }
    if (typeof $.fn.select2 === 'function') {
        if (!$trq.hasClass('select2-hidden-accessible')) {
            setupTipoRequerimientoSelect();
        }
        $trq.val(val).trigger('change.select2');
    } else {
        $trq.trigger('change');
    }
}

function initAdqSolicitudForm() {
    if (!$('#frmSolicitud').length) {
        return;
    }

    // Respetar flags del HTML (server) si la etapa no permite cotizaciones / ganadora.
    const cotEditAttr = $('#divCotizaciones').attr('data-adq-cot-edit');
    const cotSelAttr = $('#divCotizaciones').attr('data-adq-cot-sel');
    if (cotEditAttr === '0' || cotEditAttr === '1') {
        adqEtapaPermiteCotizaciones = (cotEditAttr === '1');
    }
    if (cotSelAttr === '0' || cotSelAttr === '1') {
        adqEtapaPermiteSeleccionarGanadora = (cotSelAttr === '1');
    }
    adqAplicarModoCotizacionesUi();

    if ($('#Prv_Sug').length && $('#Prv_Sug').hasClass('select2-hidden-accessible')) {
        $('#Prv_Sug').select2('destroy');
    }

    setupTipoRequerimientoSelect();
    toggleMinCotizaciones();
    toggleSlaDias();
    toggleProveedorSugerido();

    $('#frmSolicitud').off('submit.adqSol').on('submit.adqSol', function(e) {
        e.preventDefault();
        if ($('#Sol_Modo_Edicion').val() === 'observada') {
            guardarBorrador();
            return;
        }
        if ($('#Sol_Modo_Edicion').val() === 'completar_nodo') {
            procesarSolicitud(false);
            return;
        }
        toggleMinCotizaciones();
        toggleSlaDias();
        toggleProveedorSugerido();
        enviarSolicitud();
    });

    if (!$('#Sol_Cod').val() && $('#tblItems tbody tr').length === 0) {
        agregarLinea();
    }

    initModalNuevoProveedor();
}

function initModalNuevoProveedor() {
    const $ced = $('#new_Prs_Ced');
    if (!$ced.length) {
        return;
    }

    $ced.off('.adqProv').on('input.adqProv', function() {
        $('#new_Prv_Cod').val('');
        $('#new_Prv_LookupMsg').hide().text('');
        if ($(this).val().trim().length < 10) {
            setEstadoCedulaProveedor('pendiente');
        }
    });

    $ced.off('blur.adqProv').on('blur.adqProv', function() {
        validarYBuscarProveedorPorCedula();
    });
}

function setEstadoCedulaProveedor(estado) {
    const $icon = $('#new_Prs_Ced_Est');
    if (!$icon.length) {
        return;
    }
    if (estado === 'ok') {
        $icon.html('<i class="bi bi-check-circle-fill text-success"></i>');
    } else if (estado === 'error') {
        $icon.html('<i class="bi bi-x-circle-fill text-danger"></i>');
    } else if (estado === 'loading') {
        $icon.html('<span class="spinner-border spinner-border-sm text-primary" role="status"></span>');
    } else {
        $icon.html('');
    }
}

function mostrarMensajeLookupProveedor(texto, tipo) {
    const $msg = $('#new_Prv_LookupMsg');
    if (!$msg.length) {
        return;
    }
    if (!texto) {
        $msg.hide().text('');
        return;
    }
    const cls = tipo === 'info' ? 'text-primary' : (tipo === 'warn' ? 'text-warning' : 'text-danger');
    $msg.removeClass('text-primary text-warning text-danger').addClass(cls).text(texto).show();
}

function cargarDatosProveedorEnModal(data) {
    if (!data) {
        return;
    }
    $('#new_Prs_Ced').val(data.Prs_Ced || '');
    $('#new_Prs_Ape').val(data.Prs_Ape || '');
    $('#new_Prv_Com').val(data.Prv_Com || '');
    // Correo y telefono provienen de persona (Prs_Cor / Prs_Tel)
    $('#new_Prv_Cor').val(data.Prv_Cor || '');
    $('#new_Prv_Tel').val(data.Prv_Tel || '');
    $('#new_Prv_Cod').val(data.Prv_Cod || '');
}

function validarYBuscarProveedorPorCedula() {
    const cedula = ($('#new_Prs_Ced').val() || '').trim();
    $('#new_Prv_Cod').val('');
    mostrarMensajeLookupProveedor('', 'info');

    if (cedula.length < 10) {
        setEstadoCedulaProveedor('pendiente');
        return false;
    }

    if (typeof validaNoIdentif !== 'function') {
        setEstadoCedulaProveedor('error');
        mostrarMensajeLookupProveedor('No se cargo el validador de cedula/RUC.', 'error');
        return false;
    }

    const validacion = validaNoIdentif(cedula);
    if (!validacion.success) {
        setEstadoCedulaProveedor('error');
        mostrarMensajeLookupProveedor(validacion.message || 'Cedula o RUC invalido.', 'error');
        return false;
    }

    setEstadoCedulaProveedor('loading');
    $.getJSON('adq_solicitud.php', { ajax_lookup_proveedor: 1, Prs_Ced: cedula }, function(res) {
        if (!res.success) {
            setEstadoCedulaProveedor('error');
            mostrarMensajeLookupProveedor(res.message || 'No se pudo consultar el proveedor.', 'error');
            return;
        }

        if (!res.existe) {
            setEstadoCedulaProveedor('ok');
            mostrarMensajeLookupProveedor('Identificacion valida. Puede registrar el nuevo proveedor.', 'info');
            return;
        }

        cargarDatosProveedorEnModal(res.data);
        setEstadoCedulaProveedor('ok');
        if (res.proveedor_existe) {
            mostrarMensajeLookupProveedor('Persona y proveedor encontrados. Datos cargados desde la tabla persona.', 'info');
        } else {
            mostrarMensajeLookupProveedor('Persona encontrada en la tabla persona. Datos cargados; guarde para registrarla como proveedor de esta empresa.', 'warn');
        }
    }).fail(function() {
        setEstadoCedulaProveedor('error');
        mostrarMensajeLookupProveedor('Error de red al consultar la identificacion.', 'error');
    });

    return true;
}

function seleccionarProveedorEnDestino(targetIdx, id, text) {
    const newOption = new Option(text, id, true, true);
    if (targetIdx === 'sugerido') {
        const $sel = $('#Prv_Sug');
        if ($sel.find(`option[value="${id}"]`).length === 0) {
            $sel.append(newOption);
        }
        $sel.val(id).trigger('change');
        return;
    }
    const $selCot = $(`#cot_box_${targetIdx} .select2-prov-cot`);
    if ($selCot.find(`option[value="${id}"]`).length === 0) {
        $selCot.append(newOption);
    }
    $selCot.val(id).trigger('change');
}

$(document).ready(function() {
    initAdqSolicitudForm();
    $('#mdlSeleccionResponsables').on('hidden.bs.modal', function() {
        seleccionUsuariosPendienteEnvio = false;
    });
});

if (typeof window !== 'undefined') {
    window.abrirModalSeleccionResponsables = abrirModalSeleccionResponsables;
    window.confirmarSeleccionResponsables = confirmarSeleccionResponsables;
}

function setupProveedorSugeridoSelect() {
    const $el = $('#Prv_Sug');
    if (!$el.length) {
        return;
    }
    if ($el.hasClass('select2-hidden-accessible')) {
        $el.select2('destroy');
    }
    $el.select2({
        placeholder: "Busque un proveedor por RUC o Razon Social...",
        allowClear: true,
        minimumInputLength: 1,
        width: '100%',
        dropdownCssClass: 'adq-select2-dropdown',
        ajax: {
            url: 'adq_solicitud.php?ajax_search_proveedores=1',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { q: params.term };
            },
            processResults: function(data) {
                return { results: data };
            },
            cache: true
        }
    });
}

function itemEstimadoDesdeValor(valor) {
    const monto = parseFloat(valor) || 0;
    if (monto <= 0) {
        return null;
    }
    return {
        Sde_Des: 'Monto estimado',
        Sde_Can: '1.0000',
        Sde_Pru: monto.toFixed(2),
        Sde_Iva: 0,
        Pro_Cod: ''
    };
}

function sincronizarItemEstimadoDesdeValor(valor) {
    const monto = parseFloat(valor) || 0;
    if (monto <= 0) {
        return;
    }
    const $filas = $('#tblItems tbody tr');
    if ($filas.length !== 1) {
        return;
    }
    const $fila = $filas.first();
    const descripcion = $.trim($fila.find('[name*="[Sde_Des]"]').val() || '');
    const producto = $fila.find('[name*="[Pro_Cod]"]').val() || '';
    if (producto || (descripcion !== '' && descripcion.toLowerCase() !== 'estimado' && descripcion.toLowerCase() !== 'monto estimado')) {
        return;
    }
    $fila.find('[name*="[Sde_Des]"]').val('Monto estimado');
    $fila.find('.txt-cant').val('1.0000');
    $fila.find('.txt-pru').val(monto.toFixed(2));
    $fila.find('.chk-iva').prop('checked', false);
    const idx = parseInt(($fila.attr('id') || '').replace('row_item_', ''), 10);
    if (!isNaN(idx)) {
        calcularFila(idx);
    }
}

function agregarLinea(itemData) {
    const $tbody = $('#tblItems tbody');
    const idx = lineIndex;
    lineIndex++;
    const des = itemData ? (itemData.Sde_Des || '') : '';
    const can = itemData ? itemData.Sde_Can : '1.0000';
    const pru = itemData ? itemData.Sde_Pru : '0.00';
    const ivaChecked = itemData ? (parseInt(itemData.Sde_Iva, 10) === 1 ? 'checked' : '') : 'checked';
    const proCod = itemData && itemData.Pro_Cod ? itemData.Pro_Cod : '';

    const $row = $(`
        <tr id="row_item_${idx}">
            <td class="text-center fw-bold text-muted line-number">${$tbody.children().length + 1}</td>
            <td>
                <input type="text" class="form-control form-control-sm form-control-adq" name="items[${idx}][Sde_Des]" required placeholder="Ej. Computadora portatil Core i7, 16GB RAM">
                <input type="hidden" name="items[${idx}][Pro_Cod]" value="">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm text-center txt-cant form-control-adq" name="items[${idx}][Sde_Can]" min="0.0001" step="any" value="${can}" required oninput="calcularFila(${idx})">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm text-end txt-pru form-control-adq" name="items[${idx}][Sde_Pru]" min="0.01" step="any" value="${pru}" required oninput="calcularFila(${idx})">
            </td>
            <td class="text-center">
                <input type="checkbox" class="form-check-input chk-iva" name="items[${idx}][Sde_Iva]" value="1" ${ivaChecked} onchange="calcularFila(${idx})">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm text-end txt-total font-monospace bg-light form-control-adq" value="0.00" readonly>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger p-1 py-0 border-0" onclick="eliminarLinea(${idx})"><i class="bi bi-trash"></i></button>
            </td>
        </tr>
    `);
    $row.find('[name*="[Sde_Des]"]').val(des);
    $row.find('[name*="[Pro_Cod]"]').val(proCod);
    $tbody.append($row);
    calcularFila(idx);
    recalcularNumeracion();
}

function eliminarLinea(idx) {
    if ($('#tblItems tbody tr').length <= 1) {
        alert('Debe registrar al menos un item o servicio en el pedido.');
        return;
    }
    $(`#row_item_${idx}`).remove();
    recalcularNumeracion();
    recalcularTotalGeneral();
}

function recalcularNumeracion() {
    $('#tblItems tbody tr').each(function(index) {
        $(this).find('.line-number').text(index + 1);
    });
}

function calcularFila(idx) {
    const $row = $(`#row_item_${idx}`);
    const cant = parseFloat($row.find('.txt-cant').val()) || 0;
    const pru = parseFloat($row.find('.txt-pru').val()) || 0;
    const tieneIva = $row.find('.chk-iva').is(':checked');
    const total = cant * pru * (tieneIva ? 1.15 : 1.0);
    $row.find('.txt-total').val(total.toFixed(2));
    recalcularTotalGeneral();
}

function recalcularTotalGeneral() {
    let totalG = 0;
    $('#tblItems tbody tr').each(function() {
        totalG += parseFloat($(this).find('.txt-total').val()) || 0;
    });
    $('#lblTotalEstimado').text(totalG.toFixed(2));
    $('#Sol_Val_Est').val(totalG.toFixed(2));
    var $decVal = $('#camposDecisionesCompleta [data-decision-campo="Sol_Val_Est"]');
    if ($decVal.length) {
        $decVal.val(totalG.toFixed(2));
    }
    actualizarPreviewRamasCompleta();
}

function htmlAdqFileUpload(name, inputId, helpText, compact) {
    const compactClass = compact ? ' adq-file-upload-compact' : '';
    const mainLabel = compact ? 'Subir PDF' : 'Seleccionar PDF';
    return `
        <div class="adq-file-upload${compactClass}">
            <input type="file" id="${inputId}" name="${name}" accept=".pdf,application/pdf">
            <label class="adq-file-drop" for="${inputId}">
                <span class="adq-file-icon"><i class="bi bi-cloud-arrow-up"></i></span>
                <span style="min-width: 0;">
                    <span class="adq-file-main">${mainLabel}</span>
                    <span class="adq-file-name">${helpText || 'Solo archivos PDF'}</span>
                </span>
            </label>
        </div>
    `;
}

function agregarAdjuntoSolicitud(datos) {
    const idx = adjIndex++;
    const des = datos && datos.Sad_Des ? datos.Sad_Des : '';
    const html = `
        <div class="adq-adjunto-row border rounded p-3 mb-2" data-adj-idx="${idx}" style="background:#f8fafc;">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <strong style="font-size:13px;"><i class="bi bi-file-earmark-pdf text-danger"></i> Archivo de soporte</strong>
                <button type="button" class="btn btn-xs btn-outline-danger" onclick="quitarAdjuntoSolicitud(this)" title="Quitar"><i class="bi bi-trash"></i></button>
            </div>
            <div class="mb-2">
                <label class="form-label-req small mb-1">Descripción del PDF *</label>
                <input type="text" class="form-control form-control-adq" name="adjuntos[${idx}][Sad_Des]" value="${$('<div>').text(des).html()}" maxlength="500" placeholder="Ej. Especificaciones técnicas, memorando, orden de compra...">
            </div>
            <div>
                <label class="form-label-req small mb-1">Archivo PDF *</label>
                ${htmlAdqFileUpload('adjunto_archivos[' + idx + ']', 'adj_pdf_' + idx, 'Solo archivos PDF (si agrega esta fila)', false)}
            </div>
        </div>
    `;
    $('#adjuntosList').append(html);
    setupAdqFileUpload($('#adjuntosList .adq-adjunto-row').last());
}

function agregarAdjuntoExistente(adj) {
    const sadCod = adj.Sad_Cod;
    const des = adj.Sad_Des || '';
    const path = adj.Sad_Adj || '';
    const fileName = path ? path.split('/').pop() : '';
    const html = `
        <div class="adq-adjunto-row border rounded p-3 mb-2" data-sad-cod="${sadCod}" style="background:#f8fafc;">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <strong style="font-size:13px;"><i class="bi bi-file-earmark-pdf text-danger"></i> Archivo de soporte</strong>
                <button type="button" class="btn btn-xs btn-outline-danger" onclick="quitarAdjuntoExistente(this, ${sadCod})" title="Quitar"><i class="bi bi-trash"></i></button>
            </div>
            <div class="mb-2">
                <label class="form-label-req small mb-1">Descripción del PDF *</label>
                <input type="text" class="form-control form-control-adq" name="adjuntos_existentes[${sadCod}][Sad_Des]" value="${$('<div>').text(des).html()}" maxlength="500">
            </div>
            <div class="mb-2">
                <input type="hidden" name="adjuntos_existentes[${sadCod}][Sad_Adj_Keep]" value="${$('<div>').text(path).html()}">
                <a href="${adqUrlDocumento(path)}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-pdf"></i> ${$('<div>').text(fileName || 'Ver PDF').html()}</a>
            </div>
            <div>
                <label class="form-label-req small mb-1">Reemplazar PDF (opcional)</label>
                ${htmlAdqFileUpload('adjunto_archivos_existentes[' + sadCod + ']', 'adj_pdf_ex_' + sadCod, 'Dejar vacío para conservar el actual', false)}
            </div>
        </div>
    `;
    $('#adjuntosList').append(html);
    setupAdqFileUpload($('#adjuntosList .adq-adjunto-row').last());
}

function quitarAdjuntoSolicitud(btn) {
    $(btn).closest('.adq-adjunto-row').remove();
}

function quitarAdjuntoExistente(btn, sadCod) {
    $('#adjEliminarContainer').append('<input type="hidden" name="adj_eliminar[]" value="' + sadCod + '">');
    $(btn).closest('.adq-adjunto-row').remove();
}

function validarAdjuntosSolicitudFormulario(obligatorio) {
    const $rows = $('#adjuntosList .adq-adjunto-row');
    // Sin filas es válido: el Paso 2B es opcional.
    if (!$rows.length) {
        if (obligatorio) {
            alert('Debe cargar al menos un archivo PDF de soporte con su descripcion.');
            return false;
        }
        return true;
    }
    let ok = true;
    $rows.each(function() {
        const $row = $(this);
        const des = $.trim($row.find('input[name*="[Sad_Des]"]').val() || '');
        const hasKeep = $row.find('input[name*="[Sad_Adj_Keep]"]').length > 0;
        const fileInput = $row.find('input[type="file"]')[0];
        const hasNewFile = fileInput && fileInput.files && fileInput.files.length > 0;
        if (!des) {
            alert('Cada PDF de soporte debe tener una descripcion.');
            ok = false;
            return false;
        }
        if (!hasKeep && !hasNewFile) {
            alert('Seleccione el archivo PDF para cada fila de soporte, o quite la fila si no desea adjuntarlo.');
            ok = false;
            return false;
        }
    });
    return ok;
}

function adqParseCotAdjuntos(cotAdj) {
    if (!cotAdj) {
        return [];
    }
    const texto = String(cotAdj).trim();
    if (!texto) {
        return [];
    }
    if (texto.charAt(0) === '[') {
        try {
            const parsed = JSON.parse(texto);
            return Array.isArray(parsed) ? parsed.filter(Boolean) : [];
        } catch (e) {
            return [];
        }
    }
    return [texto];
}

function adqEsArchivoPdf(file) {
    if (!file) {
        return false;
    }
    const nombre = (file.name || '').toLowerCase();
    return nombre.endsWith('.pdf') || file.type === 'application/pdf' || file.type === 'application/x-pdf';
}

function htmlAdqProformaRow(opts) {
    const idx = opts.idx;
    const isExisting = !!opts.isExisting;
    const scoCod = opts.scoCod || '';
    const nameBase = isExisting
        ? ('cotizaciones_existentes[' + scoCod + ']')
        : ('cotizaciones[' + idx + ']');
    const fileBase = isExisting
        ? ('cotizacion_archivos_existentes[' + scoCod + ']')
        : ('cotizacion_archivos[' + idx + ']');
    const inputIdBase = isExisting ? ('cot_file_ex_' + scoCod) : ('cot_file_' + idx);
    const chkId = 'chk_sel_cot_' + idx;
    const chkIvaId = 'chk_iva_cot_' + idx;
    let subtotal = (opts.subtotal !== undefined && opts.subtotal !== null && opts.subtotal !== '')
        ? opts.subtotal
        : ((opts.valor !== undefined && opts.valor !== null) ? opts.valor : '');
    const ivaChecked = parseInt(opts.iva, 10) === 1 ? ' checked' : '';
    const subNum = parseFloat(String(subtotal || '').replace(',', '.')) || 0;
    const totalNum = Math.round(subNum * (parseInt(opts.iva, 10) === 1 ? ADQ_COT_IVA_FACTOR : 1) * 100) / 100;
    const totalStr = totalNum > 0 ? totalNum.toFixed(2) : '0.00';
    const selChecked = parseInt(opts.sel, 10) === 1 ? ' checked' : '';
    const jusShow = parseInt(opts.sel, 10) === 1 ? 'block' : 'none';
    const cotJus = adqEscHtml(opts.jus || '');
    const pdfsGuardados = opts.pdfsGuardadosHtml || '';
    const rowClass = 'adq-proforma-row' + (parseInt(opts.sel, 10) === 1 ? ' adq-proforma-ganadora' : '');
    const scoAttr = isExisting ? (' data-sco-cod="' + scoCod + '"') : '';

    return `
        <div class="${rowClass}" data-cot-key="${idx}"${scoAttr}>
            <input type="hidden" class="cot-prv-hidden" name="${nameBase}[Prv_Cod]" value="${adqEscHtml(opts.prvCod || '')}">
            <input type="hidden" name="${nameBase}[Cot_Val]" value="${totalStr}">
            <div class="adq-proforma-fields">
                <div class="adq-proforma-pdf">
                    ${pdfsGuardados}
                    ${htmlAdqFileUpload(fileBase + '[]', inputIdBase + '_0', 'PDF obligatorio', true)}
                </div>
                <div class="adq-proforma-val adq-cot-field">
                    <label class="adq-cot-label">Subtotal ($)</label>
                    <input type="number" class="form-control text-end form-control-adq adq-cot-control cot-sub-input" name="${nameBase}[Cot_Sub]" value="${adqEscHtml(subtotal)}" min="0.01" step="any" placeholder="0.00">
                </div>
                <div class="adq-proforma-iva adq-cot-field">
                    <label class="adq-cot-label" for="${chkIvaId}">IVA 15%</label>
                    <div class="form-check adq-cot-iva-check">
                        <input type="checkbox" class="form-check-input chk-cot-iva" name="${nameBase}[Cot_Iva]" value="1" id="${chkIvaId}"${ivaChecked}>
                        <label class="form-check-label" for="${chkIvaId}">Incluye</label>
                    </div>
                </div>
                <div class="adq-proforma-total adq-cot-field">
                    <label class="adq-cot-label">Total ($)</label>
                    <div class="adq-cot-total-box font-monospace fw-bold">$ <span class="adq-cot-total-view">${totalStr}</span></div>
                </div>
                <div class="adq-proforma-actions">
                    <div class="form-check adq-cot-winner adq-cot-winner-on">
                        <input type="checkbox" class="form-check-input chk-cot-sel" name="${nameBase}[Cot_Sel]" value="1" id="${chkId}" data-cot-key="${idx}"${selChecked} onchange="seleccionarCotizacionUnica('${idx}')">
                        <label class="form-check-label fw-bold text-success" for="${chkId}" title="Marcar cotizacion ganadora">
                            <i class="bi bi-trophy-fill"></i> <span class="adq-cot-winner-text">Ganadora</span>
                        </label>
                    </div>
                    <button type="button" class="btn btn-link adq-proforma-remove" title="Quitar proforma" onclick="quitarProformaFila(this)"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="adq-proforma-jus div-just-cot adq-cot-field" style="display: ${jusShow};">
                <label class="adq-cot-label text-danger">Justificacion</label>
                <textarea class="form-control form-control-adq adq-cot-control" name="${nameBase}[Cot_Jus]" rows="2" placeholder="Por que se eligio esta cotizacion...">${cotJus}</textarea>
            </div>
        </div>
    `;
}

function htmlAdqPdfsGuardados(fieldName, adjuntos, inline) {
    if (!adjuntos || !adjuntos.length) {
        return '';
    }
    if (inline) {
        const btns = adjuntos.map(function(path, i) {
            const safePath = adqEscHtml(path);
            const label = adjuntos.length > 1 ? ('PDF ' + (i + 1)) : 'Ver PDF';
            return `<input type="hidden" name="${fieldName}[]" value="${safePath}">
                <a href="${adqUrlDocumento(safePath)}" target="_blank" class="btn btn-sm btn-outline-primary adq-cot-pdf-btn"><i class="bi bi-file-earmark-pdf"></i> ${label}</a>`;
        }).join('');
        return `<div class="adq-cot-pdfs-guardados adq-cot-pdfs-inline">${btns}</div>`;
    }
    const items = adjuntos.map(function(path) {
        const fileName = adqEscHtml(path.split('/').pop());
        const safePath = adqEscHtml(path);
        return `
            <div class="adq-pdf-guardado-item">
                <input type="hidden" name="${fieldName}[]" value="${safePath}">
                <a href="${adqUrlDocumento(safePath)}" target="_blank"><i class="bi bi-file-earmark-pdf"></i> ${fileName}</a>
                <button type="button" class="adq-pdf-guardado-remove" title="Quitar PDF" onclick="quitarPdfGuardado(this)"><i class="bi bi-x-lg"></i></button>
            </div>
        `;
    }).join('');
    return `<div class="adq-cot-pdfs-guardados mb-2">${items}</div>`;
}

function setupAdqFileUpload($scope) {
    $scope.find('.adq-file-upload input[type="file"]').off('change.adqFile').on('change.adqFile', function() {
        const file = this.files && this.files[0] ? this.files[0] : null;
        if (file && !adqEsArchivoPdf(file)) {
            alert('Solo se permiten archivos PDF.');
            this.value = '';
            return;
        }
        const hasFile = !!file;
        const fileName = hasFile ? file.name : 'Solo archivos PDF';
        const $upload = $(this).closest('.adq-file-upload');
        $upload.find('.adq-file-name').text(fileName);
        $upload.find('.adq-file-main').text(hasFile ? 'PDF seleccionado' : 'Seleccionar PDF');
        actualizarEstadoAdjuntoCotizacion($(this).closest('.adq-cot-col'));
    });
}

function setupAdqCotPdfUploads($scope) {
    setupAdqFileUpload($scope);
    actualizarBotonesProforma($scope.closest('.adq-cot-col').length ? $scope.closest('.adq-cot-col') : $scope);
}

function syncProveedorGrupo($box) {
    if (!$box || !$box.length) {
        return;
    }
    const val = $box.find('select.select2-prov-cot').val() || '';
    $box.find('.cot-prv-hidden').val(val);
}

/**
 * Agrega otra proforma debajo del mismo proveedor (PDF + valor + ganadora).
 */
function agregarProformaMismoProveedor(btn) {
    const $box = $(btn).closest('.adq-cot-col');
    if (!$box.length) {
        agregarCotizacionHTML();
        return;
    }

    const prv = obtenerValorProveedorCot($box);
    if (!prv) {
        alert('Seleccione primero el proveedor.');
        const $sel = $box.find('select.select2-prov-cot');
        if ($sel.hasClass('select2-hidden-accessible')) {
            $sel.select2('open');
        }
        return;
    }

    const idx = cotIndex++;
    const $row = $(htmlAdqProformaRow({
        idx: idx,
        isExisting: false,
        prvCod: prv,
        subtotal: '',
        iva: 0,
        sel: 0,
        jus: ''
    }));
    $box.find('.adq-proformas-list').append($row);
    setupAdqFileUpload($row);
    setupProformaMontos($row);
    syncProveedorGrupo($box);
    actualizarBotonesProforma($box);
    actualizarEstadoAdjuntoCotizacion($box);

    const $valor = $row.find('input[name*="[Cot_Sub]"]').first();
    if ($valor.length) {
        $valor.focus();
    }
}

function agregarPdfCotizacionFila(btn) {
    agregarProformaMismoProveedor(btn);
}

function quitarProformaFila(btn) {
    const $row = $(btn).closest('.adq-proforma-row');
    const $box = $row.closest('.adq-cot-col');
    const $rows = $box.find('.adq-proforma-row');

    if ($rows.length <= 1) {
        const groupKey = $box.attr('data-group-key') || $box.attr('id').replace('cot_box_', '');
        eliminarCotizacion(groupKey);
        return;
    }

    const scoCod = $row.attr('data-sco-cod');
    if (scoCod) {
        $('#cotEliminarContainer').append('<input type="hidden" name="cot_eliminar[]" value="' + scoCod + '">');
    }
    $row.remove();
    actualizarBotonesProforma($box);
    actualizarEstadoAdjuntoCotizacion($box);
    $('.adq-cot-card').each(function() {
        $(this).toggleClass('adq-cot-card-ganadora', $(this).find('.chk-cot-sel:checked').length > 0);
    });
}

function actualizarBotonesProforma($box) {
    if (!$box || !$box.length) {
        return;
    }
    const $rows = $box.find('.adq-proforma-row');
    $rows.find('.adq-proforma-remove').toggle($rows.length > 1);
}

function quitarPdfGuardado(btn) {
    const $box = $(btn).closest('.adq-cot-col');
    const $item = $(btn).closest('.adq-pdf-guardado-item');
    if ($item.length) {
        $item.remove();
    }
    const $wrap = $box.find('.adq-cot-pdfs-guardados').filter(function() {
        return $(this).find('input[name*="[Cot_Adj_Keep]"]').length === 0 && $(this).find('a').length === 0;
    });
    $wrap.remove();
    actualizarEstadoAdjuntoCotizacion($box);
}

function proformaTieneAdjunto($row) {
    if ($row.find('input[name*="[Cot_Adj_Keep]"]').length > 0) {
        return true;
    }
    let nuevos = 0;
    $row.find('input[type="file"]').each(function() {
        if (this.files && this.files.length > 0) {
            nuevos++;
        }
    });
    return nuevos > 0;
}

function cotizacionTieneAdjuntoEnFormulario($box) {
    let ok = false;
    $box.find('.adq-proforma-row').each(function() {
        if (proformaTieneAdjunto($(this))) {
            ok = true;
            return false;
        }
    });
    if (ok) {
        return true;
    }
    return parseInt($box.attr('data-has-adj'), 10) === 1;
}

function actualizarEstadoAdjuntoCotizacion($box) {
    if (!$box || !$box.length) {
        return;
    }
    let has = false;
    $box.find('.adq-proforma-row').each(function() {
        if (proformaTieneAdjunto($(this))) {
            has = true;
            return false;
        }
    });
    $box.attr('data-has-adj', has ? 1 : 0);
}

function validarPdfsCotizacionesFormulario(exigirAdjunto) {
    if (!adqEtapaPermiteCotizaciones) {
        return true;
    }
    let invalido = false;
    $('#cotizacionesList .adq-proforma-row input[type="file"]').each(function() {
        if (!this.files || !this.files.length) {
            return;
        }
        for (let i = 0; i < this.files.length; i++) {
            if (!adqEsArchivoPdf(this.files[i])) {
                invalido = true;
                return false;
            }
        }
    });
    if (invalido) {
        alert('Solo se permiten archivos PDF en las proformas.');
        return false;
    }
    if (exigirAdjunto) {
        let faltaPdf = false;
        $('#cotizacionesList .adq-proforma-row').each(function() {
            const $row = $(this);
            const $box = $row.closest('.adq-cot-col');
            const prv = obtenerValorProveedorCot($box);
            const val = obtenerMontoProforma($row);
            if ((prv || val > 0) && !proformaTieneAdjunto($row)) {
                faltaPdf = true;
                return false;
            }
        });
        if (faltaPdf) {
            alert('Cada cotizacion debe incluir el archivo PDF de sustento.');
            return false;
        }
    }
    return true;
}

function agregarCotizacionHTML() {
    const $list = $('#cotizacionesList');
    const idx = cotIndex;
    cotIndex++;

    const $cotEl = $(`
        <div class="adq-cot-col cot-nueva" id="cot_box_${idx}" data-group-key="${idx}" data-has-adj="0">
            <div class="adq-cot-card card adq-cot-card-inline">
                <div class="adq-cot-main-row">
                    <div class="adq-cot-top-prov adq-cot-field">
                        <label class="adq-cot-label">Proveedor</label>
                        <div class="adq-cot-provider-row">
                            <div class="select-wrap">
                                <select class="form-control adq-cot-control select2-prov-cot adq-cot-prov-select" style="width: 100%;"><option value=""></option></select>
                            </div>
                            <button type="button" class="btn btn-success adq-cot-add-provider" onclick="abrirModalNuevoProveedor('${idx}')" title="Agregar proveedor"><i class="bi bi-plus-lg"></i></button>
                            <button type="button" class="btn btn-link adq-cot-remove" onclick="eliminarCotizacion('${idx}')" title="Quitar cotizacion"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </div>
                </div>
                <div class="adq-cot-pdf-section">
                    <div class="adq-proformas-head">
                        <label class="adq-cot-label"><i class="bi bi-file-earmark-pdf"></i> Proformas</label>
                        <button type="button" class="btn btn-xs btn-primary adq-btn-add-pdf-cot" onclick="agregarProformaMismoProveedor(this)" title="Agregar otra proforma del mismo proveedor">
                            <i class="bi bi-plus-lg"></i><span class="adq-btn-add-pdf-label">Proforma</span>
                        </button>
                    </div>
                    <div class="adq-proformas-list">
                        ${htmlAdqProformaRow({ idx: idx, isExisting: false, prvCod: '', subtotal: '', iva: 0, sel: 0, jus: '' })}
                    </div>
                </div>
            </div>
        </div>
    `);
    $list.append($cotEl);
    setupProveedorCotSelect($cotEl.find('.select2-prov-cot'));
    setupAdqFileUpload($cotEl);
    setupProformaMontos($cotEl);
    actualizarBotonesProforma($cotEl);
    adqAplicarModoCotizacionesUi();
}

function adqEscHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function agregarCotizacionExistente(cot) {
    const scoCod = cot.Sco_Cod;
    const idx = 'ex' + scoCod;
    const nombreProv = adqEscHtml(cot.Prv_Com || ((cot.Prs_Nom || '') + ' ' + (cot.Prs_Ape || '')).trim() || ('Proveedor #' + cot.Prv_Cod));
    const adjuntos = adqParseCotAdjuntos(cot.Cot_Adj);
    const hasAdj = adjuntos.length ? 1 : 0;
    const pdfsGuardados = htmlAdqPdfsGuardados('cotizaciones_existentes[' + scoCod + '][Cot_Adj_Keep]', adjuntos, true);
    const isGanadora = parseInt(cot.Cot_Sel, 10) === 1;

    const $cotEl = $(`
        <div class="adq-cot-col cot-existente" id="cot_box_${idx}" data-group-key="${idx}" data-has-adj="${hasAdj}" data-sco-cod="${scoCod}">
            <div class="adq-cot-card card adq-cot-card-inline${isGanadora ? ' adq-cot-card-ganadora' : ''}">
                <div class="adq-cot-main-row">
                    <div class="adq-cot-top-prov adq-cot-field">
                        <label class="adq-cot-label">Proveedor</label>
                        <div class="adq-cot-provider-row">
                            <div class="select-wrap">
                                <select class="form-control adq-cot-control select2-prov-cot adq-cot-prov-select" style="width: 100%;">
                                    <option value="${cot.Prv_Cod}" selected>${nombreProv}</option>
                                </select>
                            </div>
                            <button type="button" class="btn btn-success adq-cot-add-provider" onclick="abrirModalNuevoProveedor('${idx}')" title="Agregar proveedor"><i class="bi bi-plus-lg"></i></button>
                            <button type="button" class="btn btn-link adq-cot-remove" onclick="eliminarCotizacionExistente(${scoCod}, '${idx}')" title="Quitar cotizacion"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </div>
                </div>
                <div class="adq-cot-pdf-section">
                    <div class="adq-proformas-head">
                        <label class="adq-cot-label"><i class="bi bi-file-earmark-pdf"></i> Proformas</label>
                        <button type="button" class="btn btn-xs btn-primary adq-btn-add-pdf-cot" onclick="agregarProformaMismoProveedor(this)" title="Agregar otra proforma del mismo proveedor">
                            <i class="bi bi-plus-lg"></i><span class="adq-btn-add-pdf-label">Proforma</span>
                        </button>
                    </div>
                    <div class="adq-proformas-list">
                        ${htmlAdqProformaRow({
                            idx: idx,
                            isExisting: true,
                            scoCod: scoCod,
                            prvCod: cot.Prv_Cod,
                            subtotal: (cot.Cot_Sub !== undefined && cot.Cot_Sub !== null && parseFloat(cot.Cot_Sub) > 0)
                                ? cot.Cot_Sub
                                : cot.Cot_Val,
                            iva: cot.Cot_Iva,
                            valor: cot.Cot_Val,
                            sel: cot.Cot_Sel,
                            jus: cot.Cot_Jus || '',
                            pdfsGuardadosHtml: pdfsGuardados
                        })}
                    </div>
                </div>
            </div>
        </div>
    `);
    $('#cotizacionesList').append($cotEl);
    setupProveedorCotSelect($cotEl.find('.select2-prov-cot'));
    setupAdqFileUpload($cotEl);
    setupProformaMontos($cotEl);
    actualizarBotonesProforma($cotEl);
    syncProveedorGrupo($cotEl);
    adqAplicarModoCotizacionesUi();
}

function eliminarCotizacion(idx) {
    const $box = $(`#cot_box_${idx}`);
    $box.find('.adq-proforma-row[data-sco-cod]').each(function() {
        const scoCod = $(this).attr('data-sco-cod');
        if (scoCod) {
            $('#cotEliminarContainer').append('<input type="hidden" name="cot_eliminar[]" value="' + scoCod + '">');
        }
    });
    $box.remove();
}

function eliminarCotizacionExistente(scoCod, idx) {
    eliminarCotizacion(idx);
}

function setupProveedorCotSelect($el) {
    if (!$el.length) {
        return;
    }
    if ($el.hasClass('select2-hidden-accessible')) {
        $el.select2('destroy');
    }
    $el.select2({
        placeholder: "Seleccione proveedor...",
        allowClear: true,
        minimumInputLength: 1,
        width: '100%',
        dropdownCssClass: 'adq-select2-dropdown',
        ajax: {
            url: 'adq_solicitud.php?ajax_search_proveedores=1',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { q: params.term };
            },
            processResults: function(data) {
                return { results: data };
            },
            cache: true
        }
    });
    $el.off('change.adqProvSync').on('change.adqProvSync', function() {
        syncProveedorGrupo($(this).closest('.adq-cot-col'));
    });
}

function seleccionarCotizacionUnica(activeKey) {
    $('.chk-cot-sel').each(function() {
        const $chk = $(this);
        const key = String($chk.attr('data-cot-key') || '');
        const $row = $chk.closest('.adq-proforma-row');
        if (key !== String(activeKey)) {
            $chk.prop('checked', false);
            $row.find('.div-just-cot').hide();
            $row.removeClass('adq-proforma-ganadora');
        } else if ($chk.is(':checked')) {
            $row.find('.div-just-cot').show();
            $row.addClass('adq-proforma-ganadora');
        } else {
            $row.find('.div-just-cot').hide();
            $row.removeClass('adq-proforma-ganadora');
        }
    });
    $('.adq-cot-card').each(function() {
        $(this).toggleClass('adq-cot-card-ganadora', $(this).find('.chk-cot-sel:checked').length > 0);
    });
}

function limpiarFormulario() {
    if (confirm('Desea limpiar todo el formulario?')) {
        $('#frmSolicitud')[0].reset();
        $('#Sol_Cod').val('');
        $('#cotEliminarContainer').empty();
        setModoEdicionFormulario('', null, null);
        $('#Trq_Cod').prop('disabled', false).removeClass('adq-trq-readonly').removeAttr('tabindex').attr('name', 'Trq_Cod').val('').trigger('change');
        $('#tblItems tbody').empty();
        lineIndex = 0;
        cotIndex = 0;
        agregarLinea();
        $('#cotizacionesList').empty();
        $('#adjuntosList').empty();
        $('#adjEliminarContainer').empty();
        adjIndex = 0;
        renderCamposDecisionCompleta({ decisiones: [], campos: [] });
        renderSeleccionUsuariosNodos({ activo: false, nodos: [] }, {});
        seleccionUsuariosPendienteEnvio = false;
        recalcularTotalGeneral();
    }
}

function enviarSolicitud() {
    procesarSolicitud(false);
}

function guardarBorrador() {
    if (!validarFormularioBase()) {
        return;
    }
    procesarSolicitud(true);
}

function reenviarCorreccionObservada() {
    if ($('#Sol_Modo_Edicion').val() !== 'observada') {
        return;
    }
    if (!validarFormularioBase() || !validarSeleccionUsuariosNodos() || !validarRequisitosEnvioFormulario()) {
        return;
    }
    const solCod = parseInt($('#Sol_Cod').val(), 10);
    if (!solCod) {
        alert('No se encontro la solicitud observada.');
        return;
    }
    if (!confirm('¿Desea guardar la correccion y reenviar la solicitud a aprobacion?')) {
        return;
    }

    toggleMinCotizaciones();
    toggleSlaDias();
    toggleProveedorSugerido();
    syncReqConfigFromForm();

    const formData = construirFormDataSolicitud();
    const $btn = $('#adqFormActionsObservada button');
    const original = $btn.html();
    $btn.prop('disabled', true);
    $('#adqFormActionsObservada button:first').html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

    $.ajax({
        url: 'adq_solicitud.php?ajax_save_borrador=1',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(res) {
            if (!res.success) {
                alert('Error al guardar la correccion: ' + res.message);
                $btn.prop('disabled', false).html(original);
                return;
            }
            $.post('adq_bandeja.php', { ajax_reenviar_observada: 1, Sol_Cod: solCod }, function(res2) {
                if (res2.success) {
                    alert('La solicitud #' + res2.Num + ' fue reenviada a aprobacion correctamente.');
                    window.location.href = 'adq_bandeja.php';
                } else {
                    let msg = res2.message || 'Error desconocido';
                    if (res2.requiere_completar) {
                        msg += '\n\nComplete la informacion faltante antes de reenviar.';
                    }
                    alert('No se pudo reenviar: ' + msg);
                    $btn.prop('disabled', false).html(original);
                }
            }, 'json').fail(function() {
                alert('Error de red al reenviar la solicitud.');
                $btn.prop('disabled', false).html(original);
            });
        },
        error: function() {
            alert('Error de red al guardar la correccion.');
            $btn.prop('disabled', false).html(original);
        }
    });
}

function procesarSolicitud(esBorrador) {
    toggleMinCotizaciones();
    toggleSlaDias();
    toggleProveedorSugerido();
    syncReqConfigFromForm();

    if (adqEtapaPermiteCotizaciones && !validarPdfsCotizacionesFormulario()) {
        return;
    }

    if (!esBorrador) {
        if (!validarFormularioBase() || !validarSeleccionUsuariosNodos() || !validarRequisitosEnvioFormulario()) {
            return;
        }
    }

    const formData = construirFormDataSolicitud();

    const $btnSubmit = $('#frmSolicitud').find('button[type="submit"]');
    const $btnBorrador = $('#adqFormActionsDefault button, #adqFormActionsObservada button').filter(function() {
        const txt = $(this).text();
        return txt.indexOf('Guardar Borrador') !== -1 || txt.indexOf('Guardar Correccion') !== -1 || txt.indexOf('Guardar Corrección') !== -1;
    });
    const originalSubmit = $btnSubmit.html();
    const originalBorrador = $btnBorrador.html();
    $btnSubmit.prop('disabled', true);
    $btnBorrador.prop('disabled', true);
    if (esBorrador) {
        $btnBorrador.html('<span class="spinner-border spinner-border-sm"></span> Guardando...');
    } else {
        $btnSubmit.html('<span class="spinner-border spinner-border-sm"></span> Enviando...');
    }

    const completarNodo = $('#Sol_Modo_Edicion').val() === 'completar_nodo';
    if (completarNodo) {
        formData.set('_completar_nodo', '1');
    }
    $.ajax({
        url: completarNodo ? 'adq_solicitud.php?ajax_completar_solicitud=1' : (esBorrador ? 'adq_solicitud.php?ajax_save_borrador=1' : 'adq_solicitud.php?ajax_save_solicitud=1'),
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'text',
        success: function(raw) {
            var res = null;
            try {
                res = (typeof raw === 'string') ? JSON.parse(raw.replace(/^\uFEFF/, '').trim()) : raw;
            } catch (e) {
                var m = String(raw || '').match(/\{[\s\S]*\}/);
                if (m) {
                    try { res = JSON.parse(m[0]); } catch (e2) { res = null; }
                }
            }
            if (!res || typeof res !== 'object') {
                alert('Error critico de red al procesar la solicitud.');
                $btnSubmit.html(originalSubmit).prop('disabled', false);
                $btnBorrador.html(originalBorrador).prop('disabled', false);
                return;
            }
            if (res.success) {
                const esObservada = $('#Sol_Modo_Edicion').val() === 'observada';
                if (completarNodo) {
                    alert(`Solicitud # ${res.Num} completada. Permanece en la misma etapa; use Resolver para continuar el proceso del nodo.`);
                    window.location.href = 'adq_bandeja.php';
                    return;
                }
                if (esBorrador) {
                    if (esObservada) {
                        alert(`Correccion guardada correctamente.\nSolicitud # ${res.Num}. Cuando este listo pulse Reenviar correccion.`);
                        $btnSubmit.html(originalSubmit).prop('disabled', false);
                        $btnBorrador.html(originalBorrador).prop('disabled', false);
                        return;
                    }
                    alert(`Borrador guardado correctamente.\nSolicitud # ${res.Num}. El flujo ya quedo activo para el siguiente aprobador. Complete la informacion pendiente y envie cuando este listo.`);
                    if (res.Sol_Cod) {
                        $('#Sol_Cod').val(res.Sol_Cod);
                        setModoEdicionFormulario('borrador', res.Num, null);
                    }
                } else {
                    alert(`Solicitud registrada correctamente.\nSolicitud # ${res.Num} enviada a aprobacion.`);
                }
                window.location.href = 'adq_bandeja.php?tab=mis_solicitudes';
            } else {
                alert('Error: ' + (res.message || 'No se pudo procesar la solicitud.'));
                $btnSubmit.html(originalSubmit).prop('disabled', false);
                $btnBorrador.html(originalBorrador).prop('disabled', false);
            }
        },
        error: function(xhr) {
            var msg = 'Error critico de red al procesar la solicitud.';
            if (xhr && xhr.responseText) {
                try {
                    var res = JSON.parse(String(xhr.responseText).replace(/^\uFEFF/, '').trim());
                    if (res && res.message) {
                        msg = 'Error: ' + res.message;
                    }
                } catch (e) { /* ignore */ }
            }
            alert(msg);
            $btnSubmit.html(originalSubmit).prop('disabled', false);
            $btnBorrador.html(originalBorrador).prop('disabled', false);
        }
    });
}

function abrirModalNuevoProveedor(targetIdx) {
    $('#frmNuevoProveedor')[0].reset();
    $('#prov_target_idx').val(targetIdx);
    $('#new_Prv_Cod').val('');
    setEstadoCedulaProveedor('pendiente');
    mostrarMensajeLookupProveedor('', 'info');
    $('#mdlNuevoProveedor').modal('show');
}

function guardarNuevoProveedor(e) {
    e.preventDefault();

    const cedula = ($('#new_Prs_Ced').val() || '').trim();
    if (cedula.length < 10) {
        alert('Ingrese un RUC o Cedula valido.');
        $('#new_Prs_Ced').focus();
        return;
    }

    if (typeof validaNoIdentif !== 'function') {
        alert('No se cargo el validador de cedula/RUC.');
        return;
    }

    const validacion = validaNoIdentif(cedula);
    if (!validacion.success) {
        alert(validacion.message || 'Cedula o RUC invalido.');
        $('#new_Prs_Ced').focus();
        return;
    }

    const targetIdx = $('#prov_target_idx').val();
    const prvCodExistente = parseInt($('#new_Prv_Cod').val(), 10);

    if (prvCodExistente > 0) {
        const label = ($('#new_Prs_Ape').val() || '').trim();
        const com = ($('#new_Prv_Com').val() || '').trim();
        const texto = com ? `${label} (${com}) - RUC: ${cedula}` : `${label} - RUC: ${cedula}`;
        $('#mdlNuevoProveedor').modal('hide');
        seleccionarProveedorEnDestino(targetIdx, prvCodExistente, texto);
        return;
    }

    const data = $('#frmNuevoProveedor').serialize();
    $.post('adq_solicitud.php?ajax_save_proveedor=1', data, function(res) {
        if (res.success) {
            $('#mdlNuevoProveedor').modal('hide');
            const msg = res.existente ? 'Proveedor existente seleccionado.' : 'Proveedor registrado con exito.';
            alert(msg);
            seleccionarProveedorEnDestino(targetIdx, res.id, res.text);
        } else {
            alert('Error al guardar: ' + res.message);
        }
    }, 'json');
}
