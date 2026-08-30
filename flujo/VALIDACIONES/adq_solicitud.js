/**
 * EXA Adquisiciones Solicitud Form JS Logic
 * @author Oz <oz-agent@warp.dev>
 */

let lineIndex = 0;
let cotIndex = 0;
let reqConfig = {
    Sol_Req_Fac: 1,
    Sol_Req_Cot: 1,
    Sol_Min_Cot: 1,
    Sol_Req_Pro: 0,
    Sol_Req_Adj: 0,
    Sol_Tiempo_Est: null
};

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
    syncReqConfigFromForm();
    $('#cotizacionesStateInitial').hide();
    $('#cotizacionesStateActive').show();

    const esObservada = $('#Sol_Modo_Edicion').val() === 'observada';
    const accionGuardar = esObservada ? 'guardar correccion' : 'guardar borrador';
    const accionEnviar = esObservada ? 'reenviar correccion' : 'enviar a aprobacion';

    if (parseInt(reqConfig.Sol_Req_Cot, 10) === 1) {
        asegurarCotizacionesMinimas();
        const min = parseInt(reqConfig.Sol_Min_Cot, 10) || 1;
        const total = contarCotizacionesEnFormulario();
        $('#cotizacionesAlert')
            .removeClass('alert-info')
            .addClass('alert-warning')
            .html(`<i class="bi bi-info-circle-fill text-warning" style="font-size: 14px; margin-right: 6px;"></i> <strong>AL ${accionEnviar.toUpperCase()}:</strong> debera adjuntar al menos <strong>${min}</strong> cotizacion(es) con PDF. Hay <strong>${total}</strong> formulario(s) en pantalla; puede <strong>anadir mas</strong> o <strong>${accionGuardar}</strong> ahora y completarlas despues.`);
    } else {
        $('#cotizacionesAlert')
            .removeClass('alert-warning')
            .addClass('alert-info')
            .html(`<i class="bi bi-info-circle-fill text-info" style="font-size: 14px; margin-right: 6px;"></i> <strong>OPCIONAL:</strong> Para esta solicitud no es obligatorio adjuntar cotizaciones.`);
    }
}

function contarCotizacionesEnFormulario() {
    return $('#cotizacionesList .adq-cot-col').length;
}

function asegurarCotizacionesMinimas() {
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

function cargarConfiguracionTipo(trqCod) {
    if (!trqCod) {
        $('#divRequisitosSolicitud').hide();
        $('#divProveedorSugerido').hide();
        $('#cotizacionesStateInitial').show();
        $('#cotizacionesStateActive').hide();
        return;
    }

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
    });
}

function setModoEdicionFormulario(modo, solNum, observacion) {
    modo = modo || '';
    const esObservada = (modo === 'observada');
    const esCotizaciones = (modo === 'cotizaciones');
    $('#Sol_Modo_Edicion').val(modo);
    $('#bannerEdicionBorrador').toggle(modo === 'borrador');
    $('#bannerEdicionObservada').toggle(esObservada);
    $('#bannerEdicionCotizaciones').toggle(esCotizaciones);
    $('#adqFormActionsDefault').toggle(!esObservada && !esCotizaciones);
    $('#adqFormActionsObservada').toggle(esObservada);
    $('#adqFormActionsCotizaciones').toggle(esCotizaciones);
    if (esCotizaciones) {
        $('#lblCotizacionesNum').text(solNum ? ('# ' + solNum) : '');
    }

    const $trq = $('#Trq_Cod');
    if (esObservada) {
        const trqVal = $trq.val();
        $trq.prop('disabled', false).addClass('adq-trq-readonly').attr('tabindex', '-1');
        if (!$('#Trq_Cod_Locked').length) {
            $trq.after('<input type="hidden" id="Trq_Cod_Locked" name="Trq_Cod" value="">');
        }
        $('#Trq_Cod_Locked').val(trqVal);
        $trq.removeAttr('name');
        $trq.off('mousedown.adqLock change.adqLock').on('mousedown.adqLock change.adqLock', function(e) {
            e.preventDefault();
            $(this).val(trqVal);
            return false;
        });
    } else {
        $trq.off('mousedown.adqLock change.adqLock');
        $trq.prop('disabled', false).removeClass('adq-trq-readonly').removeAttr('tabindex');
        if (!$trq.attr('name')) {
            $trq.attr('name', 'Trq_Cod');
        }
        $('#Trq_Cod_Locked').remove();
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
    if ($('#tblItems tbody tr').length === 0) {
        alert('Debe registrar al menos un articulo/servicio en el pedido.');
        return false;
    }
    if (!$('#Trq_Cod').val()) {
        alert('Debe seleccionar un Tipo de Requerimiento.');
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
    if (!validarPdfsCotizacionesFormulario()) {
        return false;
    }
    if (parseInt(reqConfig.Sol_Req_Pro, 10) === 1 && !$('#Prv_Sug').val()) {
        alert('Debe seleccionar un proveedor sugerido para enviar esta solicitud.');
        return false;
    }
    if (parseInt(reqConfig.Sol_Req_Cot, 10) === 1) {
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
        if (!stats.ganadora) {
            alert('Debe marcar cual de las cotizaciones cargadas es la ganadora/seleccionada.');
            return false;
        }
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

function cargarBorradorEnFormulario(solCod) {
    if (!$('#frmSolicitud').length) {
        alert('El formulario de solicitud no esta disponible en pantalla.');
        return;
    }

    $.getJSON('adq_solicitud.php', { ajax_get_borrador: true, sol_cod: solCod }, function(res) {
        if (!res.success) {
            alert('No se pudo cargar la solicitud: ' + (res.message || 'Error desconocido'));
            return;
        }
        const s = res.solicitud;
        const modo = res.modo_edicion || (s.Sol_Est === 'O' ? 'observada' : 'borrador');
        $('#Sol_Cod').val(s.Sol_Cod);
        setModoEdicionFormulario(modo, s.Sol_Num, res.ultima_observacion || null);

        $('#Trq_Cod').val(s.Trq_Cod);

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
            agregarLinea();
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
        if (modo === 'observada') {
            habilitarEdicionCotizacionesObservada();
        }
    }).fail(function() {
        alert('Error de red al cargar la solicitud.');
    });
}

function bloquearFormularioSoloCotizaciones() {
    const $form = $('#frmSolicitud');
    $form.find('input, select, textarea').prop('disabled', true);
    $form.find('button').prop('disabled', true);
    const $cotZone = $('#cotizacionesList, #divBtnAddCot, #cotizacionesStateActive');
    $cotZone.find('input, select, textarea, button').prop('disabled', false);
    $('#divBtnAddCot').show().find('button').prop('disabled', false);
    $('#adqFormActionsCotizaciones button').prop('disabled', false);
    $('#Sol_Cod').prop('disabled', false);
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
        setModoEdicionFormulario('cotizaciones', s.Sol_Num, null);
        $('#lblCotizacionesEtapa').text(res.etapa_nombre ? ('Etapa actual: ' + res.etapa_nombre + '. ') : '');

        $('#Trq_Cod').val(s.Trq_Cod);
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
    if (!validarPdfsCotizacionesFormulario()) {
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

    $('#cotizacionesList .adq-cot-col').each(function() {
        const $box = $(this);
        const prv = obtenerValorProveedorCot($box);
        const val = obtenerMontoCot($box);
        const hasPdf = cotizacionTieneAdjuntoEnFormulario($box);
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

        if ($box.find('.chk-cot-sel').is(':checked')) {
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
    return $box.find('select[name*="[Prv_Cod]"]').val();
}

function obtenerMontoCot($box) {
    const raw = $box.find('input[name*="[Cot_Val]"]').val();
    return parseFloat(String(raw || '').replace(',', '.')) || 0;
}

function initAdqSolicitudForm() {
    if (!$('#frmSolicitud').length) {
        return;
    }

    if ($('#Prv_Sug').length && $('#Prv_Sug').hasClass('select2-hidden-accessible')) {
        $('#Prv_Sug').select2('destroy');
    }

    toggleMinCotizaciones();
    toggleSlaDias();
    toggleProveedorSugerido();

    $('#frmSolicitud').off('submit.adqSol').on('submit.adqSol', function(e) {
        e.preventDefault();
        if ($('#Sol_Modo_Edicion').val() === 'observada') {
            guardarBorrador();
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
});

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
                <input type="number" class="form-control form-control-sm text-end txt-pru form-control-adq" name="items[${idx}][Sde_Pru]" min="0.00" step="any" value="${pru}" required oninput="calcularFila(${idx})">
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

function htmlAdqCotPdfUploads(fieldName, inputIdBase, compact) {
    const compactClass = compact ? ' adq-cot-pdf-compact' : '';
    const addBtn = compact
        ? `<button type="button" class="btn btn-xs btn-primary adq-btn-add-pdf-cot" onclick="agregarPdfCotizacionFila(this)" title="Agregar otro PDF"><i class="bi bi-plus-lg"></i><span class="adq-btn-add-pdf-label">PDF</span></button>`
        : `<button type="button" class="btn btn-sm btn-primary mt-1 adq-btn-add-pdf-cot" onclick="agregarPdfCotizacionFila(this)"><i class="bi bi-plus-circle"></i> Agregar otro PDF</button>
           <small class="text-muted d-block mt-1">Solo se permiten archivos PDF.</small>`;
    return `
        <div class="adq-cot-pdf-zone${compactClass}">
            <div class="adq-cot-pdf-rows">
                <div class="adq-cot-pdf-row">
                    ${htmlAdqFileUpload(fieldName + '[]', inputIdBase + '_0', compact ? 'PDF' : 'Solo archivos PDF', compact)}
                    <button type="button" class="btn btn-link adq-pdf-row-remove" style="display:none;" title="Quitar archivo" onclick="quitarPdfCotizacionFila(this)"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            ${addBtn}
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
                <a href="../../DATA/${safePath}" target="_blank" class="btn btn-sm btn-outline-primary adq-cot-pdf-btn"><i class="bi bi-file-earmark-pdf"></i> ${label}</a>`;
        }).join('');
        return `<div class="adq-cot-pdfs-guardados adq-cot-pdfs-inline">${btns}</div>`;
    }
    const items = adjuntos.map(function(path) {
        const fileName = adqEscHtml(path.split('/').pop());
        const safePath = adqEscHtml(path);
        return `
            <div class="adq-pdf-guardado-item">
                <input type="hidden" name="${fieldName}[]" value="${safePath}">
                <a href="../../DATA/${safePath}" target="_blank"><i class="bi bi-file-earmark-pdf"></i> ${fileName}</a>
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
    $scope.find('.adq-cot-pdf-zone').each(function() {
        actualizarBotonesPdfCotizacion($(this));
    });
}

function agregarPdfCotizacionFila(btn) {
    const $zone = $(btn).closest('.adq-cot-pdf-zone');
    const compact = $zone.hasClass('adq-cot-pdf-compact');
    const $rows = $zone.find('.adq-cot-pdf-rows');
    const fieldName = $rows.find('input[type="file"]').first().attr('name');
    const baseId = 'cot_pdf_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
    const $row = $(`
        <div class="adq-cot-pdf-row">
            ${htmlAdqFileUpload(fieldName, baseId, compact ? 'PDF' : 'Solo archivos PDF', compact)}
            <button type="button" class="btn btn-link adq-pdf-row-remove" title="Quitar archivo" onclick="quitarPdfCotizacionFila(this)"><i class="bi bi-x-lg"></i></button>
        </div>
    `);
    $rows.append($row);
    setupAdqFileUpload($row);
    actualizarBotonesPdfCotizacion($zone);
}

function quitarPdfCotizacionFila(btn) {
    const $zone = $(btn).closest('.adq-cot-pdf-zone');
    const $rows = $zone.find('.adq-cot-pdf-row');
    if ($rows.length <= 1) {
        const $input = $rows.find('input[type="file"]');
        $input.val('');
        $input.closest('.adq-file-upload').find('.adq-file-main').text('Seleccionar PDF');
        $input.closest('.adq-file-upload').find('.adq-file-name').text('Solo archivos PDF');
    } else {
        $(btn).closest('.adq-cot-pdf-row').remove();
    }
    actualizarBotonesPdfCotizacion($zone);
    actualizarEstadoAdjuntoCotizacion($zone.closest('.adq-cot-col'));
}

function actualizarBotonesPdfCotizacion($zone) {
    const $rows = $zone.find('.adq-cot-pdf-row');
    $rows.find('.adq-pdf-row-remove').toggle($rows.length > 1);
}

function quitarPdfGuardado(btn) {
    const $box = $(btn).closest('.adq-cot-col');
    $(btn).closest('.adq-pdf-guardado-item').remove();
    if ($box.find('.adq-cot-pdfs-guardados .adq-pdf-guardado-item').length === 0) {
        $box.find('.adq-cot-pdfs-guardados').remove();
    }
    actualizarEstadoAdjuntoCotizacion($box);
}

function cotizacionTieneAdjuntoEnFormulario($box) {
    const kept = $box.find('input[name*="[Cot_Adj_Keep]"]').length;
    let nuevos = 0;
    $box.find('input[type="file"]').each(function() {
        if (this.files && this.files.length > 0) {
            nuevos++;
        }
    });
    if (kept > 0 || nuevos > 0) {
        return true;
    }
    return parseInt($box.attr('data-has-adj'), 10) === 1;
}

function actualizarEstadoAdjuntoCotizacion($box) {
    if (!$box || !$box.length) {
        return;
    }
    $box.attr('data-has-adj', cotizacionTieneAdjuntoEnFormulario($box) ? 1 : 0);
}

function validarPdfsCotizacionesFormulario() {
    let invalido = false;
    $('#cotizacionesList .adq-cot-pdf-zone input[type="file"]').each(function() {
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
    return true;
}

function agregarCotizacionHTML() {
    const $list = $('#cotizacionesList');
    const idx = cotIndex;
    cotIndex++;

    const $cotEl = $(`
        <div class="adq-cot-col cot-nueva" id="cot_box_${idx}" data-has-adj="0">
            <div class="adq-cot-card card adq-cot-card-inline">
                <div class="adq-cot-main-row">
                    <div class="adq-cot-top-prov adq-cot-field">
                        <label class="adq-cot-label">Proveedor</label>
                        <div class="adq-cot-provider-row">
                            <div class="select-wrap">
                                <select class="form-control adq-cot-control select2-prov-cot" name="cotizaciones[${idx}][Prv_Cod]" style="width: 100%;"><option value=""></option></select>
                            </div>
                            <button type="button" class="btn btn-success adq-cot-add-provider" onclick="abrirModalNuevoProveedor('${idx}')" title="Agregar proveedor"><i class="bi bi-plus-lg"></i></button>
                        </div>
                    </div>
                    <div class="adq-cot-top-val adq-cot-field">
                        <label class="adq-cot-label">Valor ($)</label>
                        <input type="number" class="form-control text-end form-control-adq adq-cot-control" name="cotizaciones[${idx}][Cot_Val]" min="0.01" step="any" placeholder="0.00">
                    </div>
                    <div class="adq-cot-top-jus div-just-cot adq-cot-field" style="display: none;">
                        <label class="adq-cot-label text-danger">Justificacion</label>
                        <textarea class="form-control form-control-adq adq-cot-control" name="cotizaciones[${idx}][Cot_Jus]" rows="2" placeholder="Por que se eligio esta cotizacion..."></textarea>
                    </div>
                    <div class="adq-cot-top-actions">
                        <div class="form-check adq-cot-winner">
                            <input type="checkbox" class="form-check-input chk-cot-sel" name="cotizaciones[${idx}][Cot_Sel]" value="1" id="chk_sel_cot_${idx}" onchange="seleccionarCotizacionUnica(${idx})">
                            <label class="form-check-label fw-bold text-success" for="chk_sel_cot_${idx}" title="Cotizacion ganadora"><i class="bi bi-trophy"></i></label>
                        </div>
                        <button type="button" class="btn btn-link adq-cot-remove" onclick="eliminarCotizacion(${idx})" title="Quitar cotizacion"><i class="bi bi-x-lg"></i></button>
                    </div>
                </div>
                <div class="adq-cot-pdf-section">
                    <label class="adq-cot-label"><i class="bi bi-file-earmark-pdf"></i> Proformas PDF</label>
                    <div class="adq-cot-pdf-strip">
                        ${htmlAdqCotPdfUploads('cotizacion_archivos[' + idx + ']', 'cot_file_' + idx, true)}
                    </div>
                </div>
            </div>
        </div>
    `);
    $list.append($cotEl);
    setupProveedorCotSelect($cotEl.find('.select2-prov-cot'));
    setupAdqCotPdfUploads($cotEl);
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
    const cotJus = adqEscHtml(cot.Cot_Jus || '');

    const $cotEl = $(`
        <div class="adq-cot-col cot-existente" id="cot_box_${idx}" data-has-adj="${hasAdj}" data-sco-cod="${scoCod}">
            <div class="adq-cot-card card adq-cot-card-inline${parseInt(cot.Cot_Sel, 10) === 1 ? ' adq-cot-card-ganadora' : ''}">
                <div class="adq-cot-main-row">
                    <div class="adq-cot-top-prov adq-cot-field">
                        <label class="adq-cot-label">Proveedor</label>
                        <div class="adq-cot-provider-row">
                            <div class="select-wrap">
                                <select class="form-control adq-cot-control select2-prov-cot" name="cotizaciones_existentes[${scoCod}][Prv_Cod]" style="width: 100%;">
                                    <option value="${cot.Prv_Cod}" selected>${nombreProv}</option>
                                </select>
                            </div>
                            <button type="button" class="btn btn-success adq-cot-add-provider" onclick="abrirModalNuevoProveedor('${idx}')" title="Agregar proveedor"><i class="bi bi-plus-lg"></i></button>
                        </div>
                    </div>
                    <div class="adq-cot-top-val adq-cot-field">
                        <label class="adq-cot-label">Valor ($)</label>
                        <input type="number" class="form-control text-end form-control-adq adq-cot-control" name="cotizaciones_existentes[${scoCod}][Cot_Val]" value="${cot.Cot_Val}" min="0.01" step="any">
                    </div>
                    <div class="adq-cot-top-jus div-just-cot adq-cot-field" style="display: ${parseInt(cot.Cot_Sel, 10) === 1 ? 'block' : 'none'};">
                        <label class="adq-cot-label text-danger">Justificacion</label>
                        <textarea class="form-control form-control-adq adq-cot-control" name="cotizaciones_existentes[${scoCod}][Cot_Jus]" rows="2" placeholder="Por que se eligio esta cotizacion...">${cotJus}</textarea>
                    </div>
                    <div class="adq-cot-top-actions">
                        <div class="form-check adq-cot-winner">
                            <input type="checkbox" class="form-check-input chk-cot-sel" name="cotizaciones_existentes[${scoCod}][Cot_Sel]" value="1" id="chk_sel_cot_${idx}" ${parseInt(cot.Cot_Sel, 10) === 1 ? 'checked' : ''} onchange="seleccionarCotizacionUnica('${idx}')">
                            <label class="form-check-label fw-bold text-success" for="chk_sel_cot_${idx}" title="Cotizacion ganadora"><i class="bi bi-trophy"></i></label>
                        </div>
                        <button type="button" class="btn btn-link adq-cot-remove" onclick="eliminarCotizacionExistente(${scoCod}, '${idx}')" title="Quitar cotizacion"><i class="bi bi-x-lg"></i></button>
                    </div>
                </div>
                <div class="adq-cot-pdf-section">
                    <label class="adq-cot-label"><i class="bi bi-file-earmark-pdf"></i> Proformas PDF</label>
                    <div class="adq-cot-pdf-strip">
                        ${pdfsGuardados}
                        ${htmlAdqCotPdfUploads('cotizacion_archivos_existentes[' + scoCod + ']', 'cot_file_ex_' + scoCod, true)}
                    </div>
                </div>
            </div>
        </div>
    `);
    $('#cotizacionesList').append($cotEl);
    setupProveedorCotSelect($cotEl.find('.select2-prov-cot'));
    setupAdqCotPdfUploads($cotEl);
}

function eliminarCotizacion(idx) {
    $(`#cot_box_${idx}`).remove();
}

function eliminarCotizacionExistente(scoCod, idx) {
    $(`#cot_box_${idx}`).remove();
    $('#cotEliminarContainer').append(`<input type="hidden" name="cot_eliminar[]" value="${scoCod}">`);
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
}

function seleccionarCotizacionUnica(activeIdx) {
    const activeId = 'cot_box_' + activeIdx;
    $('.chk-cot-sel').each(function() {
        const $chk = $(this);
        const $box = $chk.closest('[id^="cot_box_"]');
        const $card = $chk.closest('.adq-cot-card');
        if ($box.attr('id') !== activeId) {
            $chk.prop('checked', false);
            $card.find('.div-just-cot').hide();
            $card.removeClass('adq-cot-card-ganadora');
        } else if ($chk.is(':checked')) {
            $card.find('.div-just-cot').show();
            $card.addClass('adq-cot-card-ganadora');
        } else {
            $card.find('.div-just-cot').hide();
            $card.removeClass('adq-cot-card-ganadora');
        }
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
    if (!validarFormularioBase() || !validarRequisitosEnvioFormulario()) {
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

    if (!validarPdfsCotizacionesFormulario()) {
        return;
    }

    if (!esBorrador) {
        if (!validarFormularioBase() || !validarRequisitosEnvioFormulario()) {
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

    $.ajax({
        url: esBorrador ? 'adq_solicitud.php?ajax_save_borrador=1' : 'adq_solicitud.php?ajax_save_solicitud=1',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                const esObservada = $('#Sol_Modo_Edicion').val() === 'observada';
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
                window.location.href = 'adq_bandeja.php';
            } else {
                alert('Error: ' + res.message);
                $btnSubmit.html(originalSubmit).prop('disabled', false);
                $btnBorrador.html(originalBorrador).prop('disabled', false);
            }
        },
        error: function() {
            alert('Error critico de red al procesar la solicitud.');
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
