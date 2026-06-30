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
    $('#divSolMinCot').toggle(on);
    if (!on) {
        $('#Sol_Min_Cot').val(0);
    } else if (!$('#Sol_Min_Cot').val() || parseInt($('#Sol_Min_Cot').val(), 10) < 1) {
        $('#Sol_Min_Cot').val(1);
    }
}

function toggleSlaDias() {
    const on = $('#Sol_Define_Sla').is(':checked');
    $('#divSolTiempoEst').toggle(on);
    if (!on) {
        $('#Sol_Tiempo_Est').val('');
    }
}

function toggleProveedorSugerido() {
    if ($('#Sol_Req_Pro').is(':checked')) {
        $('#divProveedorSugerido').show();
    } else {
        $('#divProveedorSugerido').hide();
        $('#Prv_Sug').prop('required', false).val(null).trigger('change');
    }
}

function aplicarReglasCotizaciones() {
    syncReqConfigFromForm();
    $('#cotizacionesStateInitial').hide();
    $('#cotizacionesStateActive').show();

    if (parseInt(reqConfig.Sol_Req_Cot, 10) === 1) {
        const min = parseInt(reqConfig.Sol_Min_Cot, 10) || 1;
        $('#cotizacionesAlert')
            .removeClass('alert-info')
            .addClass('alert-warning')
            .html(`<i class="bi bi-info-circle-fill text-warning" style="font-size: 14px; margin-right: 6px;"></i> <strong>AL ENVIAR A APROBACION:</strong> debera adjuntar al menos <strong>${min}</strong> cotizacion(es) con PDF. Puede <strong>guardar borrador</strong> ahora y completarlas despues.`);
    } else {
        $('#cotizacionesAlert')
            .removeClass('alert-warning')
            .addClass('alert-info')
            .html(`<i class="bi bi-info-circle-fill text-info" style="font-size: 14px; margin-right: 6px;"></i> <strong>OPCIONAL:</strong> Para esta solicitud no es obligatorio adjuntar cotizaciones.`);
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

function cargarBorradorEnFormulario(solCod) {
    $.getJSON('adq_solicitud.php', { ajax_get_borrador: true, sol_cod: solCod }, function(res) {
        if (!res.success) {
            alert('No se pudo cargar el borrador: ' + (res.message || 'Error desconocido'));
            return;
        }
        const s = res.solicitud;
        $('#Sol_Cod').val(s.Sol_Cod);
        $('#bannerEdicionBorrador').show();
        $('#lblBorradorNum').text('# ' + s.Sol_Num);

        $('#Trq_Cod').val(s.Trq_Cod);
        aplicarRequisitosDesdeSolicitud(s);

        $('#Sol_Pri').val(s.Sol_Pri);
        $('#Cdc_Cod').val(s.Cdc_Cod || '');
        $('#Sol_Jus').val(s.Sol_Jus);
        $('#Sol_Det').val(s.Sol_Det);

        if (s.Prv_Sug && res.prv_sug_text) {
            $('#Prv_Sug').empty().append(new Option(res.prv_sug_text, s.Prv_Sug, true, true)).trigger('change');
        }

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
        aplicarReglasCotizaciones();
    });
}

function contarCotizacionesParaEnvio() {
    let total = 0;
    let ganadora = false;
    $('#cotizacionesList > div').each(function() {
        const $box = $(this);
        const prv = $box.find('select[name*="[Prv_Cod]"]').val();
        const val = parseFloat($box.find('input[name*="[Cot_Val]"]').val()) || 0;
        const hasAdjGuardado = parseInt($box.data('has-adj'), 10) === 1;
        const hasArchivoNuevo = $box.find('input[type="file"]').filter(function() {
            return this.files && this.files.length > 0;
        }).length > 0;
        if (prv && val > 0 && (hasAdjGuardado || hasArchivoNuevo)) {
            total++;
        }
        if ($box.find('.chk-cot-sel').is(':checked')) {
            ganadora = true;
        }
    });
    return { total: total, ganadora: ganadora };
}

$(document).ready(function() {
    if (!$('#Sol_Cod').val()) {
        agregarLinea();
    }
    setupProveedorSugeridoSelect();
    $('#frmSolicitud').on('submit', function(e) {
        e.preventDefault();
        enviarSolicitud();
    });
});

function setupProveedorSugeridoSelect() {
    $('#Prv_Sug').select2({
        placeholder: "Busque un proveedor por RUC o Razon Social...",
        allowClear: true,
        minimumInputLength: 1,
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

function agregarCotizacionHTML() {
    const $list = $('#cotizacionesList');
    const idx = cotIndex;
    cotIndex++;

    const $cotEl = $(`
        <div class="col-md-6 cot-nueva" id="cot_box_${idx}" style="margin-bottom: 15px;" data-has-adj="0">
            <div class="card p-3 bg-white" style="border: 1px solid #cbd5e1; border-radius: 8px;">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <span class="fw-bold text-primary" style="font-size: 13px;"><i class="bi bi-file-earmark-arrow-up"></i> Nueva Cotizacion</span>
                    <button type="button" class="btn btn-xs p-0 border-0" onclick="eliminarCotizacion(${idx})"><i class="bi bi-x-circle text-danger"></i></button>
                </div>
                <input type="hidden" name="cot_index[]" value="${idx}">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Proveedor Cotizante</label>
                    <select class="form-control form-control-sm select2-prov-cot" name="cotizaciones[${idx}][Prv_Cod]" style="width: 100%;"><option value=""></option></select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold small">Monto Cotizado ($)</label>
                        <input type="number" class="form-control form-control-sm text-end form-control-adq" name="cotizaciones[${idx}][Cot_Val]" min="0.01" step="any" placeholder="0.00">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold small">Archivo Adjunto (PDF)</label>
                        <input type="file" class="form-control form-control-sm form-control-adq" name="cotizacion_archivos[]" accept=".pdf,image/*">
                    </div>
                </div>
                <div class="mb-2 form-check" style="background: #f8fafc; padding: 8px 12px 8px 28px; border-radius: 6px;">
                    <input type="checkbox" class="form-check-input chk-cot-sel" name="cotizaciones[${idx}][Cot_Sel]" value="1" id="chk_sel_cot_${idx}" onchange="seleccionarCotizacionUnica(${idx})">
                    <label class="form-check-label fw-bold text-success small" for="chk_sel_cot_${idx}">Elegir esta cotizacion como ganadora</label>
                </div>
                <div class="mb-0 div-just-cot" style="display: none;">
                    <label class="form-label fw-semibold text-danger small">Justificacion de eleccion</label>
                    <textarea class="form-control form-control-sm form-control-adq" name="cotizaciones[${idx}][Cot_Jus]" rows="2"></textarea>
                </div>
            </div>
        </div>
    `);
    $list.append($cotEl);
    setupProveedorCotSelect($cotEl.find('.select2-prov-cot'));
}

function agregarCotizacionExistente(cot) {
    const scoCod = cot.Sco_Cod;
    const idx = 'ex' + scoCod;
    const nombreProv = cot.Prv_Com || ((cot.Prs_Nom || '') + ' ' + (cot.Prs_Ape || '')).trim();
    const hasAdj = cot.Cot_Adj ? 1 : 0;
    const adjLink = cot.Cot_Adj ? `<a href="../../DATA/${cot.Cot_Adj}" target="_blank" class="btn btn-xs btn-outline-primary mt-1"><i class="bi bi-file-earmark-pdf"></i> Ver PDF actual</a>` : '';

    const $cotEl = $(`
        <div class="col-md-6 cot-existente" id="cot_box_${idx}" style="margin-bottom: 15px;" data-has-adj="${hasAdj}" data-sco-cod="${scoCod}">
            <div class="card p-3 bg-white" style="border: 1px solid #cbd5e1; border-radius: 8px;">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <span class="fw-bold text-primary" style="font-size: 13px;"><i class="bi bi-file-earmark-check"></i> Cotizacion guardada</span>
                    <button type="button" class="btn btn-xs p-0 border-0" onclick="eliminarCotizacionExistente(${scoCod}, '${idx}')"><i class="bi bi-x-circle text-danger"></i></button>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Proveedor Cotizante</label>
                    <select class="form-control form-control-sm select2-prov-cot" name="cotizaciones_existentes[${scoCod}][Prv_Cod]" style="width: 100%;">
                        <option value="${cot.Prv_Cod}" selected>${nombreProv}</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold small">Monto Cotizado ($)</label>
                        <input type="number" class="form-control form-control-sm text-end form-control-adq" name="cotizaciones_existentes[${scoCod}][Cot_Val]" value="${cot.Cot_Val}" min="0.01" step="any">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold small">Reemplazar PDF (opcional)</label>
                        <input type="file" class="form-control form-control-sm form-control-adq" name="cotizacion_archivos_existentes[${scoCod}]" accept=".pdf,image/*">
                        ${adjLink}
                    </div>
                </div>
                <div class="mb-2 form-check" style="background: #f8fafc; padding: 8px 12px 8px 28px; border-radius: 6px;">
                    <input type="checkbox" class="form-check-input chk-cot-sel" name="cotizaciones_existentes[${scoCod}][Cot_Sel]" value="1" id="chk_sel_cot_${idx}" ${parseInt(cot.Cot_Sel, 10) === 1 ? 'checked' : ''} onchange="seleccionarCotizacionUnica('${idx}')">
                    <label class="form-check-label fw-bold text-success small" for="chk_sel_cot_${idx}">Elegir esta cotizacion como ganadora</label>
                </div>
                <div class="mb-0 div-just-cot" style="display: ${parseInt(cot.Cot_Sel, 10) === 1 ? 'block' : 'none'};">
                    <label class="form-label fw-semibold text-danger small">Justificacion de eleccion</label>
                    <textarea class="form-control form-control-sm form-control-adq" name="cotizaciones_existentes[${scoCod}][Cot_Jus]" rows="2">${cot.Cot_Jus || ''}</textarea>
                </div>
            </div>
        </div>
    `);
    $('#cotizacionesList').append($cotEl);
    setupProveedorCotSelect($cotEl.find('.select2-prov-cot'));
}

function eliminarCotizacion(idx) {
    $(`#cot_box_${idx}`).remove();
}

function eliminarCotizacionExistente(scoCod, idx) {
    $(`#cot_box_${idx}`).remove();
    $('#cotEliminarContainer').append(`<input type="hidden" name="cot_eliminar[]" value="${scoCod}">`);
}

function setupProveedorCotSelect($el) {
    $el.select2({
        placeholder: "Seleccione proveedor...",
        allowClear: true,
        minimumInputLength: 1,
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
        const $card = $chk.closest('.card');
        if ($box.attr('id') !== activeId) {
            $chk.prop('checked', false);
            $card.find('.div-just-cot').hide();
        } else if ($chk.is(':checked')) {
            $card.find('.div-just-cot').show();
        } else {
            $card.find('.div-just-cot').hide();
        }
    });
}

function limpiarFormulario() {
    if (confirm('Desea limpiar todo el formulario?')) {
        $('#frmSolicitud')[0].reset();
        $('#Sol_Cod').val('');
        $('#cotEliminarContainer').empty();
        $('#bannerEdicionBorrador').hide();
        $('#Trq_Cod').val('').trigger('change');
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
    if ($('#tblItems tbody tr').length === 0) {
        alert('Debe registrar al menos un articulo/servicio en el pedido.');
        return;
    }
    if (!$('#Trq_Cod').val()) {
        alert('Debe seleccionar un Tipo de Requerimiento.');
        return;
    }
    if (!$('#Sol_Jus').val().trim()) {
        alert('Debe ingresar la justificacion de la solicitud.');
        return;
    }
    procesarSolicitud(true);
}

function procesarSolicitud(esBorrador) {
    syncReqConfigFromForm();

    if (!esBorrador) {
        if ($('#tblItems tbody tr').length === 0) {
            alert('Debe registrar al menos un articulo/servicio en el pedido.');
            return;
        }
        if (parseInt(reqConfig.Sol_Req_Pro, 10) === 1 && !$('#Prv_Sug').val()) {
            alert('Debe seleccionar un proveedor sugerido para enviar esta solicitud.');
            return;
        }
        if (parseInt(reqConfig.Sol_Req_Cot, 10) === 1) {
            const stats = contarCotizacionesParaEnvio();
            const minRequired = parseInt(reqConfig.Sol_Min_Cot, 10) || 1;
            if (stats.total < minRequired) {
                alert(`Para enviar a aprobacion se requieren al menos ${minRequired} cotizacion(es) con proveedor, monto y archivo PDF. Puede guardar borrador y completarlas despues.`);
                return;
            }
            if (!stats.ganadora) {
                alert('Debe marcar cual de las cotizaciones cargadas es la ganadora/seleccionada.');
                return;
            }
        }
    }

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

    const $btnSubmit = $('#frmSolicitud').find('button[type="submit"]');
    const $btnBorrador = $('#frmSolicitud').find('button').filter(function() {
        return $(this).text().indexOf('Guardar Borrador') !== -1;
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
                if (esBorrador) {
                    alert(`Borrador guardado correctamente.\nSolicitud # ${res.Num}. Complete cotizaciones y envie cuando este listo.`);
                    if (res.Sol_Cod) {
                        $('#Sol_Cod').val(res.Sol_Cod);
                        $('#bannerEdicionBorrador').show();
                        $('#lblBorradorNum').text('# ' + res.Num);
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
    $('#mdlNuevoProveedor').modal('show');
}

function guardarNuevoProveedor(e) {
    e.preventDefault();
    const targetIdx = $('#prov_target_idx').val();
    const data = $('#frmNuevoProveedor').serialize();
    $.post('adq_solicitud.php?ajax_save_proveedor=1', data, function(res) {
        if (res.success) {
            $('#mdlNuevoProveedor').modal('hide');
            alert('Proveedor registrado con exito.');
            const newOption = new Option(res.text, res.id, true, true);
            if (targetIdx === 'sugerido') {
                $('#Prv_Sug').append(newOption).trigger('change');
            } else {
                $(`#cot_box_${targetIdx} .select2-prov-cot`).append(newOption).trigger('change');
            }
        } else {
            alert('Error al guardar: ' + res.message);
        }
    }, 'json');
}
