// ==================== GLOBAL VARIABLES ====================
var gridAlimentacion;
var comidasRegistradas = [];

// ==================== INITIALIZATION ====================
$(function() {
    // Inicializar datepicker para modal
    if (typeof $.createDatePickers === 'function') {
        $.createDatePickers("#txtFechaModal");
    } else if ($.fn.datepicker) {
        $("#txtFechaModal").datepicker({
            dateFormat: 'dd/mm/yy',
            changeMonth: true,
            changeYear: true
        });
    }

    // Inicializar Chosen para selects en modal
    $(".chosen-select").chosen({
        no_results_text: "No hay resultados",
        width: "100%"
    });

    // Hacer el modal arrastrable (draggable) con la cabecera como manejador
    if (typeof $.fn.draggable === 'function') {
        $("#modalRegistroAlimentacion .modal-dialog").draggable({
            handle: ".modal-header"
        });
    }

    // Cargar selects iniciales
    loadChoferesModal();
    loadVehiculosModal();
    loadChoferesReporte();
    loadVehiculosReporte();

    // Inicializar grid al cargar la página
    initializeGrid();

    // Event listeners para modal
    $('#cboChoferModal').on('change', function() {
        onChoferChangeModal($(this).val());
    });

    $('#cboVehiculoModal').on('change', function() {
        cargarComidasRegistradas();
    });

    // Detectar cambios en la fecha (datepicker nativo o input)
    $('#txtFechaModal').on('change changeDate input', function() {
        cargarComidasRegistradas();
    });

    $('#btnNuevoAlimentacion').on('click', function() {
        nuevoRegistroModal();
        cargarComidasRegistradas();
        $('#modalRegistroAlimentacion').modal('show');
    });

    $('#btnGuardarModal').on('click', guardarAlimentacionModal);

    $('#btnBuscar').on('click', reloadGridAlimentacion);
    $('#btnGenerarReporte').on('click', generarReporte);
});

// ==================== LOAD SELECTS ====================
function loadChoferesModal() {
    $.ajax({
        url: 'man_alt_alimentacion.php',
        data: { listChoferesAjax: 1 },
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            var $select = $('#cboChoferModal');
            $select.empty();
            $select.append('<option value="">Seleccione Chofer</option>');
            if (data) {
                $.each(data, function(i, item) {
                    $select.append('<option value="' + item.Cho_Cod + '">' + item.nombre + '</option>');
                });
            }
            $select.trigger("chosen:updated");
        }
    });
}

function loadVehiculosModal() {
    $.ajax({
        url: 'man_alt_alimentacion.php',
        data: { listVehiculosAjax: 1 },
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            var $select = $('#cboVehiculoModal');
            $select.empty();
            $select.append('<option value="">Seleccione Vehículo</option>');
            if (data) {
                $.each(data, function(i, item) {
                    $select.append('<option value="' + item.Veh_Cod + '">' + item.Veh_Pla + ' - ' + item.Veh_Mar + '</option>');
                });
            }
            $select.trigger("chosen:updated");
        }
    });
}

function loadChoferesReporte() {
    $.ajax({
        url: 'man_alt_alimentacion.php',
        data: { listChoferesAjax: 1 },
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            var $select = $('#cboChoferReporte');
            $select.empty();
            $select.append('<option value="">Todos</option>');
            if (data) {
                $.each(data, function(i, item) {
                    $select.append('<option value="' + item.Cho_Cod + '">' + item.nombre + '</option>');
                });
            }
            $select.trigger("chosen:updated");
        }
    });
}

function loadVehiculosReporte() {
    $.ajax({
        url: 'man_alt_alimentacion.php',
        data: { listVehiculosAjax: 1 },
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            var $select = $('#cboVehiculoReporte');
            $select.empty();
            $select.append('<option value="">Todos</option>');
            if (data) {
                $.each(data, function(i, item) {
                    $select.append('<option value="' + item.Veh_Cod + '">' + item.Veh_Pla + ' - ' + item.Veh_Mar + '</option>');
                });
            }
            $select.trigger("chosen:updated");
        }
    });
}

// ==================== MODAL REGISTRO ====================
function onChoferChangeModal(cho_cod) {
    var fecha = $('#txtFechaModal').val();

    if (!cho_cod) {
        $('#cboVehiculoModal').val('').trigger('chosen:updated');
        cargarComidasRegistradas();
        return;
    }

    $.ajax({
        url: 'man_alt_alimentacion.php',
        data: { 
            getUltimoVehiculoChoferAjax: 1, 
            Cho_Cod: cho_cod,
            Mal_Fec: fecha
        },
        type: 'GET',
        dataType: 'json',
        success: function(resp) {
            var $select = $('#cboVehiculoModal');
            // Resetear checkboxes primero
            $('input[name="chkAlimentacionModal"]').prop('checked', false).prop('disabled', false);
            comidasRegistradas = [];

            if (resp && resp.vehiculo && resp.vehiculo.length > 0) {
                $select.val(resp.vehiculo[0].Veh_Cod);
                
                // Cargar raciones directamente desde la respuesta optimizada
                if (resp.comidas && Array.isArray(resp.comidas)) {
                    comidasRegistradas = resp.comidas;
                    $.each(resp.comidas, function(i, val) {
                        $('input[name="chkAlimentacionModal"][value="' + val + '"]')
                            .prop('checked', true)
                            .prop('disabled', true);
                    });
                }
            } else {
                $select.val('');
            }
            $select.trigger('chosen:updated');
        },
        error: function() {
            // En caso de error, resetear
            $('input[name="chkAlimentacionModal"]').prop('checked', false).prop('disabled', false);
            comidasRegistradas = [];
        }
    });
}

function cargarComidasRegistradas() {
    var fecha = $('#txtFechaModal').val();
    var chofer = $('#cboChoferModal').val();
    var vehiculo = $('#cboVehiculoModal').val();

    // Resetear checkboxes (quitar selección y habilitar todos)
    $('input[name="chkAlimentacionModal"]').prop('checked', false).prop('disabled', false);
    comidasRegistradas = [];

    if (!fecha || !chofer || !vehiculo) {
        return;
    }

    $.ajax({
        url: 'man_alt_alimentacion.php',
        data: {
            getAlimentacionRegistradaAjax: 1,
            Cho_Cod: chofer,
            Veh_Cod: vehiculo,
            Mal_Fec: fecha
        },
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data && Array.isArray(data)) {
                comidasRegistradas = data;
                $.each(data, function(i, val) {
                    $('input[name="chkAlimentacionModal"][value="' + val + '"]')
                        .prop('checked', true)
                        .prop('disabled', true);
                });
            }
        }
    });
}

function guardarAlimentacionModal() {
    var fecha = $('#txtFechaModal').val();
    var chofer = $('#cboChoferModal').val();
    var vehiculo = $('#cboVehiculoModal').val();

    var tiposSeleccionados = [];
    var nuevosTipos = [];
    
    $('input[name="chkAlimentacionModal"]:checked').each(function() {
        var val = $(this).val();
        tiposSeleccionados.push(val);
        if (comidasRegistradas.indexOf(val) === -1) {
            nuevosTipos.push(val);
        }
    });

    // Validar y bloquear el proceso si existen errores
    if (!fecha || fecha.trim() === "") {
        mostrarAlertaError('Debe ingresar o seleccionar la fecha.');
        return;
    }
    if (!chofer || chofer.trim() === "") {
        mostrarAlertaError('Debe seleccionar un chofer.');
        return;
    }
    if (!vehiculo || vehiculo.trim() === "") {
        mostrarAlertaError('Debe seleccionar un vehículo.');
        return;
    }
    if (tiposSeleccionados.length === 0) {
        mostrarAlertaError('Debe seleccionar al menos un tipo de alimentación.');
        return;
    }
    
    // Bloquear si todas las raciones seleccionadas ya existían previamente
    if (nuevosTipos.length === 0 && tiposSeleccionados.length > 0) {
        mostrarAlertaError('No ha seleccionado ninguna ración de alimentación nueva para guardar (las seleccionadas ya fueron registradas).');
        return;
    }

    $.ajax({
        url: 'man_alt_alimentacion.php',
        data: {
            saveAlimentacionAjax: 1,
            txtFecha: fecha,
            cboChofer: chofer,
            cboVehiculo: vehiculo,
            cboTipos: tiposSeleccionados
        },
        type: 'POST',
        dataType: 'json',
        beforeSend: function() {
            $("#btnGuardarModal").prop('disabled', true);
            $("#loaderAlimentacion").show();
        },
        success: function(resp) {
            if (resp.success) {
                mostrarAlertaExito(resp.message);
                $('#modalRegistroAlimentacion').modal('hide');
                nuevoRegistroModal();
                reloadGridAlimentacion();
            } else {
                mostrarAlertaError(resp.message);
            }
        },
        error: function(xhr) {
            mostrarAlertaError('Error al guardar: ' + xhr.responseText);
        },
        complete: function() {
            $("#btnGuardarModal").prop('disabled', false);
            $("#loaderAlimentacion").hide();
        }
    });
}

// Funciones auxiliares para mostrar alertas propias del proyecto
function mostrarAlertaError(mensaje) {
    if (typeof $.alert === 'function') {
        $.alert(mensaje);
    } else if (typeof alertError === 'function') {
        alertError(mensaje);
    } else {
        alert(mensaje.replace(/<br>/g, "\n").replace(/<b>/g, "").replace(/<\/b>/g, ""));
    }
}

function mostrarAlertaExito(mensaje) {
    if (typeof $.alert === 'function') {
        $.alert(mensaje);
    } else if (typeof alertExito === 'function') {
        alertExito(mensaje);
    } else {
        alert(mensaje.replace(/<br>/g, "\n").replace(/<b>/g, "").replace(/<\/b>/g, ""));
    }
}

function nuevoRegistroModal() {
    $("#txtFechaModal").val($.datepicker ? $.datepicker.formatDate('dd/mm/yy', new Date()) : "");
    $("#cboChoferModal").val("").trigger('chosen:updated');
    $("#cboVehiculoModal").val("").trigger('chosen:updated');
    $('input[name="chkAlimentacionModal"]').prop('checked', false).prop('disabled', false);
    comidasRegistradas = [];
}

// ==================== FILTRO PERIODO ====================
function ajustarFiltroFecha(tipo) {
    $('#filtroFechaDia').hide();
    $('#filtroFechaSemana').hide();
    $('#filtroFechaMes').hide();
    $('#filtroQuincena').hide();

    if (tipo === 'D') {
        $('#filtroFechaDia').show();
    } else if (tipo === 'S') {
        $('#filtroFechaSemana').show();
    } else if (tipo === 'Q') {
        $('#filtroFechaMes').show();
        $('#filtroQuincena').show();
    } else if (tipo === 'M') {
        $('#filtroFechaMes').show();
    }
}

// Limita el año a 4 dígitos en inputs nativos type=date/week/month
function limitarAnioInput($input) {
    var val = $input.val();
    if (!val) return;

    // El valor de estos inputs siempre tiene el formato YYYY-... o YYYY-W...
    var partes = val.split('-');
    if (partes[0] && partes[0].length > 4) {
        partes[0] = partes[0].substring(0, 4);
        $input.val(partes.join('-'));
    }
}

$(function() {
    // Restringir año a 4 dígitos en los filtros de fecha nativos
    $('#filtroFechaDia, #filtroFechaSemana, #filtroFechaMes').on('input change', function() {
        limitarAnioInput($(this));
    });
});

// ==================== GRID ====================
function initializeGrid() {
    gridAlimentacion = $("#gridAlimentacion").createGrid({
        caption: '',
        url: 'man_alt_alimentacion.php',
        postData: { listAlimentacionGridAjax: 1 },
        height: 350,
        rowNum: 20,
        rowList: [10, 20, 50, 100],
        colModel: [
            { label: 'Código', name: 'Mal_Cod', key: true, hidden: true, width: 50, align: 'center' },
            { label: 'Active_Ids', name: 'Active_Ids', hidden: true },
            { label: 'Fecha', name: 'Mal_Fec', width: 90, formatter: 'date', formatoptions: { newformat: 'd/m/Y' } },
            { label: 'Chofer', name: 'Cho_Nom', width: 190 },
            { label: 'Vehículo', name: 'Veh_Pla', width: 100 },
            { label: 'D', name: 'Tip_D', width: 45, align: 'center', formatter: function(cell) {
                return cell == 1 ? '<span style="color: #10b981; font-weight: bold; font-size: 14px;">✔</span>' : '<span style="color: #cbd5e1;">-</span>';
            }},
            { label: 'A', name: 'Tip_A', width: 45, align: 'center', formatter: function(cell) {
                return cell == 1 ? '<span style="color: #10b981; font-weight: bold; font-size: 14px;">✔</span>' : '<span style="color: #cbd5e1;">-</span>';
            }},
            { label: 'M', name: 'Tip_M', width: 45, align: 'center', formatter: function(cell) {
                return cell == 1 ? '<span style="color: #10b981; font-weight: bold; font-size: 14px;">✔</span>' : '<span style="color: #cbd5e1;">-</span>';
            }},
            { label: 'C', name: 'Tip_C', width: 45, align: 'center', formatter: function(cell) {
                return cell == 1 ? '<span style="color: #10b981; font-weight: bold; font-size: 14px;">✔</span>' : '<span style="color: #cbd5e1;">-</span>';
            }},
            { label: 'Usuario', name: 'Usu_Nom', width: 160 },
            { label: 'Estado', name: 'Mal_Est', width: 80, formatter: function(cell) {
                return cell == 'A' ? 'Activo' : 'Inactivo';
            }},
            { label: 'Acciones', name: 'acciones', width: 90, align: 'center', sortable: false, formatter: function(cell, opts, rowData) {
                if (rowData.Mal_Est == 'A' && rowData.Active_Ids) {
                    return '<button type="button" class="btn btn-xs exa-ui-btn-danger" onclick="anularRegistro(\'' + rowData.Active_Ids + '\');">Anular</button>';
                }
                return '';
            }}
        ],
        viewrecords: true,
        jsonReader: { root: "rows", page: "page", total: "total", records: "records", repeatitems: false }
    }, false, '#pagerAlimentacion', { refresh: true, view: false });

    // Agregar botón de Excel al pager
    $("#gridAlimentacion").jqGrid('navButtonAdd', '#pagerAlimentacion', {
        caption: 'Excel',
        title: 'Exportar a Excel',
        buttonicon: 'glyphicon glyphicon-file',
        onClickButton: function() {
            exportarExcelAlimentacion();
        },
        position: 'last'
    });
}

// Función para exportar a Excel
function exportarExcelAlimentacion() {
    if (typeof $.printExport === 'function') {
        $("#gridAlimentacion").printExport({
            type: 'excel',
            filename: 'Alimentacion_' + new Date().toISOString().slice(0,10).replace(/-/g,'')
        });
    } else {
        alert('La función de exportación a Excel no está disponible en este momento.');
    }
}

function reloadGridAlimentacion() {
    if (!gridAlimentacion) {
        initializeGrid();
    }

    gridAlimentacion.setGridParam({
        url: 'man_alt_alimentacion.php',
        datatype: "json",
        postData: {
            listAlimentacionGridAjax: 1,
            f_tipo: $('#tipoFiltroFecha').val(),
            f_val_dia: $('#filtroFechaDia').val(),
            f_val_semana: $('#filtroFechaSemana').val(),
            f_val_mes: $('#filtroFechaMes').val(),
            f_quincena: $('#filtroQuincena').val(),
            f_buscar: $('#txtBuscar').val(),
            f_tipo_busqueda: $('#tipoBusqueda').val(),
            f_estado: $('#cboEstado').val()
        },
        page: 1
    }).trigger('reloadGrid');
}

function anularRegistro(mal_cod) {
    if (confirm('¿Está seguro de anular este registro?')) {
        $.ajax({
            url: 'man_alt_alimentacion.php',
            data: { anularAlimentacionAjax: 1, Mal_Cod: mal_cod },
            type: 'POST',
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    if (typeof alertExito === 'function') {
                        alertExito(resp.message);
                    } else {
                        alert(resp.message);
                    }
                    reloadGridAlimentacion();
                } else {
                    if (typeof alertError === 'function') {
                        alertError(resp.message);
                    } else {
                        alert(resp.message);
                    }
                }
            }
        });
    }
}

// ==================== REPORTE ====================
function generarReporte() {
    $.ajax({
        url: 'man_alt_alimentacion.php',
        data: {
            getReporteAlimentacionAjax: 1,
            cboMes: $('#cboMes').val(),
            cboAnio: $('#cboAnio').val(),
            cboVehiculoReporte: $('#cboVehiculoReporte').val(),
            cboChoferReporte: $('#cboChoferReporte').val()
        },
        type: 'GET',
        dataType: 'json',
        success: function(reporte) {
            var html = '';
            var mes_nombre = $('#cboMes option:selected').text();
            var anio = $('#cboAnio').val();

            $.each(reporte, function(key, veh_data) {
                html += '<div style="margin-bottom: 30px;">';
                html += '<h4 style="font-weight: bold; margin-bottom: 10px;">Vehículo: ' + veh_data.Veh_Pla + '</h4>';

                // Primera quincena
                html += '<div style="margin-bottom: 15px;">';
                html += '<h5 style="font-weight: bold;">Primera Quincena</h5>';
                html += '<table class="table table-bordered table-condensed table-striped" style="width: auto;">';
                html += '<thead><tr><th>Fecha</th><th>Desayuno</th><th>Almuerzo</th><th>Merienda</th></tr></thead>';
                html += '<tbody>';
                var total1_desayuno = 0, total1_almuerzo = 0, total1_merienda = 0;

                for (var dia = 1; dia <= 15; dia++) {
                    var mes_str = String($('#cboMes').val()).padStart(2, '0');
                    var dia_str = String(dia).padStart(2, '0');
                    var fecha_str = anio + '-' + mes_str + '-' + dia_str;
                    var datos = veh_data.datos[fecha_str] || { Desayuno:0, Almuerzo:0, Merienda:0 };
                    total1_desayuno += datos.Desayuno;
                    total1_almuerzo += datos.Almuerzo;
                    total1_merienda += datos.Merienda;

                    html += '<tr>';
                    html += '<td>' + dia_str + '/' + mes_str + '/' + anio + '</td>';
                    html += '<td style="text-align: center;">' + datos.Desayuno + '</td>';
                    html += '<td style="text-align: center;">' + datos.Almuerzo + '</td>';
                    html += '<td style="text-align: center;">' + datos.Merienda + '</td>';
                    html += '</tr>';
                }

                html += '<tr style="font-weight: bold;">';
                html += '<td>Subtotal</td>';
                html += '<td style="text-align: center;">' + total1_desayuno + '</td>';
                html += '<td style="text-align: center;">' + total1_almuerzo + '</td>';
                html += '<td style="text-align: center;">' + total1_merienda + '</td>';
                html += '</tr>';
                html += '</tbody></table></div>';

                // Segunda quincena
                html += '<div style="margin-bottom:15px;">';
                html += '<h5 style="font-weight: bold;">Segunda Quincena</h5>';
                html += '<table class="table table-bordered table-condensed table-striped" style="width: auto;">';
                html += '<thead><tr><th>Fecha</th><th>Desayuno</th><th>Almuerzo</th><th>Merienda</th></tr></thead>';
                html += '<tbody>';
                var total2_desayuno = 0, total2_almuerzo = 0, total2_merienda = 0;
                var ultimo_dia = new Date(anio, parseInt($('#cboMes').val()), 0).getDate();

                for (var dia = 16; dia <= ultimo_dia; dia++) {
                    var mes_str = String($('#cboMes').val()).padStart(2, '0');
                    var dia_str = String(dia).padStart(2, '0');
                    var fecha_str = anio + '-' + mes_str + '-' + dia_str;
                    var datos = veh_data.datos[fecha_str] || { Desayuno:0, Almuerzo:0, Merienda:0 };
                    total2_desayuno += datos.Desayuno;
                    total2_almuerzo += datos.Almuerzo;
                    total2_merienda += datos.Merienda;

                    html += '<tr>';
                    html += '<td>' + dia_str + '/' + mes_str + '/' + anio + '</td>';
                    html += '<td style="text-align: center;">' + datos.Desayuno + '</td>';
                    html += '<td style="text-align: center;">' + datos.Almuerzo + '</td>';
                    html += '<td style="text-align: center;">' + datos.Merienda + '</td>';
                    html += '</tr>';
                }

                html += '<tr style="font-weight: bold;">';
                html += '<td>Subtotal</td>';
                html += '<td style="text-align: center;">' + total2_desayuno + '</td>';
                html += '<td style="text-align: center;">' + total2_almuerzo + '</td>';
                html += '<td style="text-align: center;">' + total2_merienda + '</td>';
                html += '</tr>';
                html += '</tbody></table></div>';

                // Total mensual
                html += '<div>';
                html += '<h5 style="font-weight: bold;">Total Mensual</h5>';
                html += '<table class="table table-bordered table-condensed" style="width: auto;">';
                html += '<tbody>';
                html += '<tr style="font-weight: bold;">';
                html += '<td>Total</td>';
                html += '<td style="text-align: center;">' + (total1_desayuno + total2_desayuno) + '</td>';
                html += '<td style="text-align: center;">' + (total1_almuerzo + total2_almuerzo) + '</td>';
                html += '<td style="text-align: center;">' + (total1_merienda + total2_merienda) + '</td>';
                html += '</tr>';
                html += '</tbody></table></div>';

                html += '</div>';
            });

            if (html === '') {
                html = '<p style="font-style: italic; color: #888;">No hay datos para mostrar.</p>';
            }

            $('#divReporte').html(html);
        }
    });
}
