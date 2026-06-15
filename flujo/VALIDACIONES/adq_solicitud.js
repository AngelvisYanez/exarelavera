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
        $('#divCotizaciones').hide();
        return;
    }

    $.getJSON('', { ajax_get_trq_details: true, trq_cod: trqCod }, function(res) {
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

            // Configurar visibilidad de cotizaciones
            if (parseInt(reqConfig.Trq_Req_Cot) === 1) {
                $('#lblMinCot').text(reqConfig.Trq_Min_Cot);
                $('#divCotizaciones').show();
                $('#cotizacionesList').empty();
                cotIndex = 0;
                // Crear inputs iniciales para cotizaciones según el mínimo
                const min = parseInt(reqConfig.Trq_Min_Cot) || 1;
                for (let i = 0; i < min; i++) {
                    agregarCotizacionHTML(true);
                }
            } else {
                $('#divCotizaciones').hide();
                $('#cotizacionesList').empty();
            }
        }
    });
}

function setupProveedorSugeridoSelect() {
    // Configurar autocompletado AJAX con Select2
    // Apunta al archivo existente en adquisiciones o compras
    $('#Prv_Sug').select2({
        placeholder: "Busque un proveedor por RUC o Razón Social...",
        allowClear: true,
        minimumInputLength: 1,
        ajax: {
            url: '../../adquisiciones/FRONT/adq_con_proveedor_3.0.php', // O el buscador estandar
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term, // parámetro buscado
                    op_opciones: 'h' // busqueda por nombre en exa_standard
                };
            },
            processResults: function (data) {
                // EXA JqGrid standard retorna { rows: [...] }
                const rows = data.rows || data;
                return {
                    results: $.map(rows, function (item) {
                        return {
                            id: item.Prv_Cod || item.id,
                            text: item.Proveedor || item.text || item.Prs_Nom
                        };
                    })
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
                <input type="text" class="form-control form-control-sm" name="items[${idx}][Sde_Des]" required placeholder="Ej. Computadora portátil Core i7, 16GB RAM">
                <input type="hidden" name="items[${idx}][Pro_Cod]" value="">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm text-center txt-cant" name="items[${idx}][Sde_Can]" min="0.0001" step="any" value="1.0000" required oninput="calcularFila(${idx})">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm text-end txt-pru" name="items[${idx}][Sde_Pru]" min="0.00" step="any" value="0.00" required oninput="calcularFila(${idx})">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm text-end txt-total font-monospace bg-light" value="0.00" readonly>
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
    const total = cant * pru;
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
        <div class="col-md-6" id="cot_box_${idx}">
            <div class="card p-3 bg-light border-secondary-subtle">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <span class="fw-bold text-dark"><i class="bi bi-file-earmark-arrow-up"></i> Sustento de Cotización #${idx + 1}</span>
                    ${!required ? `<button type="button" class="btn btn-xs p-0 border-0" onclick="eliminarCotizacion(${idx})"><i class="bi bi-x-circle text-danger"></i></button>` : ''}
                </div>
                <input type="hidden" name="cot_index[]" value="${idx}">
                <div class="mb-2">
                    <label class="form-label" style="font-size: 12px;">Proveedor Cotizante *</label>
                    <select class="form-select form-select-sm select2-prov-cot" name="cotizaciones[${idx}][Prv_Cod]" required>
                        <option value=""></option>
                    </select>
                </div>
                <div class="row g-2">
                    <div class="col-6 mb-2">
                        <label class="form-label" style="font-size: 12px;">Monto Cotizado *</label>
                        <input type="number" class="form-control form-control-sm text-end" name="cotizaciones[${idx}][Cot_Val]" min="0.01" step="any" required>
                    </div>
                    <div class="col-6 mb-2">
                        <label class="form-label" style="font-size: 12px;">Archivo Adjunto (PDF)${labelRequired}</label>
                        <input type="file" class="form-control form-control-sm" name="cotizacion_archivos[]" accept=".pdf,image/*" ${isRequiredAttr}>
                    </div>
                </div>
                <div class="mb-1 form-check">
                    <input type="checkbox" class="form-check-input chk-cot-sel" name="cotizaciones[${idx}][Cot_Sel]" value="1" onchange="seleccionarCotizacionUnica(${idx})">
                    <label class="form-check-label" style="font-size: 12px;">Elegir esta cotización</label>
                </div>
                <div class="mb-0 div-just-cot" style="display: none;">
                    <label class="form-label" style="font-size: 12px;">Justificación de elección *</label>
                    <textarea class="form-control form-control-sm" name="cotizaciones[${idx}][Cot_Jus]" rows="1" placeholder="Indique por qué eligió esta cotización..."></textarea>
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
            url: '../../adquisiciones/FRONT/adq_con_proveedor_3.0.php',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { search: params.term, op_opciones: 'h' };
            },
            processResults: function (data) {
                const rows = data.rows || data;
                return {
                    results: $.map(rows, function (item) {
                        return {
                            id: item.Prv_Cod || item.id,
                            text: item.Proveedor || item.text || item.Prs_Nom
                        };
                    })
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
        url: '?ajax_save_solicitud=1',
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
