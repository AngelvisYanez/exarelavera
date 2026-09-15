/**
 * EXA Adquisiciones Solicitud Form JS Logic
 * Manejo dinámico de líneas, totalizaciones y carga asíncrona.
 * @author Oz <oz-agent@warp.dev>
 */

let lineIndex = 0;
let cotIndex = 0;
let reqConfig = {
    Trq_Req_Fac: 1,
    Trq_Req_Cot: 1,
    Trq_Min_Cot: 1,
    Trq_Req_Pro: 0,
    Trq_Req_Adj: 0
};

$(document).ready(function() {
    agregarLinea(); // Inicializar con una línea en blanco
    setupProveedorSugeridoSelect();
    
    $('#frmSolicitud').on('submit', function(e) {
        e.preventDefault();
        enviarSolicitud();
    });
});

function cargarConfiguracionTipo(trqCod) {
    if (!trqCod) {
        $('#divProveedorSugerido').hide();
        $('#cotizacionesStateInitial').show();
        $('#cotizacionesStateActive').hide();
        return;
    }

    $.getJSON('adq_solicitud.php', { ajax_get_trq_details: true, trq_cod: trqCod }, function(res) {
        if (res.success && res.data) {
            reqConfig = res.data;
            
            // Configurar visibilidad del proveedor sugerido
            if (parseInt(reqConfig.Trq_Req_Pro) === 1) {
                $('#divProveedorSugerido').show();
                $('#Prv_Sug').prop('required', true);
            } else {
                $('#divProveedorSugerido').hide();
                $('#Prv_Sug').prop('required', false).val(null).trigger('change');
            }

            // Configurar visibilidad/estado de cotizaciones
            $('#cotizacionesStateInitial').hide();
            $('#cotizacionesStateActive').show();
            $('#cotizacionesList').empty();
            cotIndex = 0;

            if (parseInt(reqConfig.Trq_Req_Cot) === 1) {
                const min = parseInt(reqConfig.Trq_Min_Cot) || 1;
                
                // Configurar alerta de requerido
                $('#cotizacionesAlert')
                    .removeClass('alert-info')
                    .addClass('alert-warning')
                    .html(`<i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 14px; margin-right: 6px;"></i> <strong>REQUERIDO:</strong> Este tipo de requerimiento requiere obligatoriamente adjuntar al menos <strong>${min}</strong> cotización(es) física(s) de sustento. Por favor, ingrese los datos y adjunte los archivos PDF correspondientes.`);
                
                // Crear inputs iniciales obligatorios para cotizaciones según el mínimo
                for (let i = 0; i < min; i++) {
                    agregarCotizacionHTML(true);
                }
            } else {
                // Configurar alerta de opcional
                $('#cotizacionesAlert')
                    .removeClass('alert-warning')
                    .addClass('alert-info')
                    .html(`<i class="bi bi-info-circle-fill text-info" style="font-size: 14px; margin-right: 6px;"></i> <strong>OPCIONAL:</strong> Para este tipo de requerimiento <strong>no es obligatorio</strong> adjuntar cotizaciones. Puede proceder a enviar la solicitud directamente o, si lo prefiere, adjuntar cotizaciones opcionales como sustento.`);
            }
        }
    });
}

function setupProveedorSugeridoSelect() {
    // Configurar autocompletado AJAX con Select2 apuntando al nuevo endpoint local
    $('#Prv_Sug').select2({
        placeholder: "Busque un proveedor por RUC o Razón Social...",
        allowClear: true,
        minimumInputLength: 1,
        ajax: {
            url: 'adq_solicitud.php?ajax_search_proveedores=1',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // parámetro buscado
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });
}

function agregarLinea() {
    const $tbody = $('#tblItems tbody');
    const idx = lineIndex;
    lineIndex++;

    const $row = $(`
        <tr id="row_item_${idx}">
            <td class="text-center fw-bold text-muted line-number">${$tbody.children().length + 1}</td>
            <td>
                <input type="text" class="form-control form-control-sm form-control-adq" name="items[${idx}][Sde_Des]" required placeholder="Ej. Computadora portátil Core i7, 16GB RAM">
                <input type="hidden" name="items[${idx}][Pro_Cod]" value="">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm text-center txt-cant form-control-adq" name="items[${idx}][Sde_Can]" min="0.0001" step="any" value="1.0000" required oninput="calcularFila(${idx})">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm text-end txt-pru form-control-adq" name="items[${idx}][Sde_Pru]" min="0.00" step="any" value="0.00" required oninput="calcularFila(${idx})">
            </td>
            <td class="text-center">
                <input type="checkbox" class="form-check-input chk-iva" name="items[${idx}][Sde_Iva]" value="1" checked onchange="calcularFila(${idx})">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm text-end txt-total font-monospace bg-light form-control-adq" value="0.00" readonly>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger p-1 py-0 border-0" onclick="eliminarLinea(${idx})"><i class="bi bi-trash"></i></button>
            </td>
        </tr>
    `);

    $tbody.append($row);
    recalcularNumeracion();
}

function eliminarLinea(idx) {
    if ($('#tblItems tbody tr').length <= 1) {
        alert('Debe registrar al menos un ítem o servicio en el pedido.');
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
        const filaTotal = parseFloat($(this).find('.txt-total').val()) || 0;
        totalG += filaTotal;
    });
    $('#lblTotalEstimado').text(totalG.toFixed(2));
    $('#Sol_Val_Est').val(totalG.toFixed(2));
}

function agregarCotizacionHTML(required = false) {
    const $list = $('#cotizacionesList');
    const idx = cotIndex;
    cotIndex++;

    const isRequiredAttr = required ? 'required' : '';
    const labelRequired = required ? ' *' : '';

    const $cotEl = $(`
        <div class="col-md-6" id="cot_box_${idx}" style="margin-bottom: 15px;">
            <div class="card p-3 bg-white" style="border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3" style="border-color: #f1f5f9 !important;">
                    <span class="fw-bold text-primary" style="font-size: 13px;"><i class="bi bi-file-earmark-arrow-up"></i> Sustento de Cotización #${idx + 1}</span>
                    ${!required ? `<button type="button" class="btn btn-xs p-0 border-0" onclick="eliminarCotizacion(${idx})"><i class="bi bi-x-circle text-danger"></i></button>` : ''}
                </div>
                <input type="hidden" name="cot_index[]" value="${idx}">
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size: 11px; color: #475569; margin-bottom: 4px;">Proveedor Cotizante *</label>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <div style="flex: 1; min-width: 0;">
                            <select class="form-control form-control-sm select2-prov-cot" name="cotizaciones[${idx}][Prv_Cod]" required style="width: 100%;">
                                <option value=""></option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-sm btn-success" onclick="abrirModalNuevoProveedor(${idx})" title="Agregar Nuevo Proveedor" style="height: 31px; padding: 0 10px; display: flex; align-items: center; justify-content: center; background-color: #10b981; border-color: #10b981; color: white; border-radius: 4px;"><i class="bi bi-plus-lg" style="font-size: 13px; font-weight: bold;"></i></button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold" style="font-size: 11px; color: #475569; margin-bottom: 4px;">Monto Cotizado ($) *</label>
                        <input type="number" class="form-control form-control-sm text-end form-control-adq" name="cotizaciones[${idx}][Cot_Val]" min="0.01" step="any" placeholder="0.00" required style="border-radius: 4px; border: 1px solid #cbd5e1; padding: 4px 8px;">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold" style="font-size: 11px; color: #475569; margin-bottom: 4px;">Archivo Adjunto (PDF)${labelRequired}</label>
                        <input type="file" class="form-control form-control-sm form-control-adq" name="cotizacion_archivos[]" accept=".pdf,image/*" ${isRequiredAttr} style="border-radius: 4px; border: 1px solid #cbd5e1; padding: 2px 6px;">
                    </div>
                </div>
                <div class="mb-2 form-check" style="background: #f8fafc; padding: 8px 12px 8px 28px; border-radius: 6px; border: 1px dashed #cbd5e1; margin-left: 0;">
                    <input type="checkbox" class="form-check-input chk-cot-sel" name="cotizaciones[${idx}][Cot_Sel]" value="1" id="chk_sel_cot_${idx}" onchange="seleccionarCotizacionUnica(${idx})">
                    <label class="form-check-label fw-bold text-success" for="chk_sel_cot_${idx}" style="font-size: 12px; cursor: pointer; user-select: none;">Elegir esta cotización como ganadora</label>
                </div>
                <div class="mb-0 div-just-cot" style="display: none; margin-top: 10px;">
                    <label class="form-label fw-semibold text-danger" style="font-size: 11px; margin-bottom: 4px;">Justificación de elección *</label>
                    <textarea class="form-control form-control-sm form-control-adq" name="cotizaciones[${idx}][Cot_Jus]" rows="2" placeholder="Indique por qué eligió este proveedor (ej. mejor precio, menor tiempo de entrega, garantía...)" style="border-radius: 4px; border: 1px solid #cbd5e1;"></textarea>
                </div>
            </div>
        </div>
    `);

    $list.append($cotEl);
    setupProveedorCotSelect($cotEl.find('.select2-prov-cot'));
}

function eliminarCotizacion(idx) {
    $(`#cot_box_${idx}`).remove();
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
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });
}

function seleccionarCotizacionUnica(activeIdx) {
    $('.chk-cot-sel').each(function(index, el) {
        const nameAttr = $(el).attr('name');
        if (!nameAttr.includes(`[${activeIdx}]`)) {
            $(el).prop('checked', false);
            $(el).closest('.card').find('.div-just-cot').hide().find('textarea').prop('required', false);
        } else {
            const isChecked = $(el).is(':checked');
            const $just = $(el).closest('.card').find('.div-just-cot');
            if (isChecked) {
                $just.show().find('textarea').prop('required', true);
            } else {
                $just.hide().find('textarea').prop('required', false);
            }
        }
    });
}

function limpiarFormulario() {
    if (confirm('¿Desea limpiar todo el formulario?')) {
        $('#frmSolicitud')[0].reset();
        $('#Trq_Cod').val('').trigger('change');
        $('#tblItems tbody').empty();
        agregarLinea();
        recalcularTotalGeneral();
    }
}

function enviarSolicitud() {
    // Validaciones avanzadas
    if ($('#tblItems tbody tr').length === 0) {
        alert('Debe registrar al menos un artículo/servicio en el pedido.');
        return;
    }

    if (parseInt(reqConfig.Trq_Req_Cot) === 1) {
        const countCot = $('#cotizacionesList .card').length;
        const minRequired = parseInt(reqConfig.Trq_Min_Cot) || 1;
        if (countCot < minRequired) {
            alert(`Para este tipo de requerimiento se requiere adjuntar al menos ${minRequired} cotizaciones físicas.`);
            return;
        }

        // Validar si seleccionó al menos una cotización elegida
        let algunSeleccionado = false;
        $('.chk-cot-sel').each(function() {
            if ($(this).is(':checked')) algunSeleccionado = true;
        });

        if (!algunSeleccionado) {
            alert('Debe marcar cuál de las cotizaciones físicas cargadas es la ganadora/seleccionada para el proceso.');
            return;
        }
    }

    const formData = new FormData($('#frmSolicitud')[0]);

    // Mostrar spinner de guardando
    const btnSubmit = $('#frmSolicitud').find('button[type="submit"]');
    const originalText = btnSubmit.html();
    btnSubmit.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...').prop('disabled', true);

    $.ajax({
        url: 'adq_solicitud.php?ajax_save_solicitud=1',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                alert(`¡Solicitud registrada correctamente!\nSe ha generado la Solicitud # ${res.Num} y se ha instanciado su respectivo Workflow de aprobaciones.`);
                window.location.href = 'adq_bandeja.php';
            } else {
                alert('Error al registrar la solicitud: ' + res.message);
                btnSubmit.html(originalText).prop('disabled', false);
            }
        },
        error: function() {
            alert('Error crítico de red al procesar la solicitud.');
            btnSubmit.html(originalText).prop('disabled', false);
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
            alert('Proveedor registrado con éxito.');

            // Crear opción para Select2
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
