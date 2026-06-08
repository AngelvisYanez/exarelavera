/**
 * Validación, Grids e Interactividad de Dos Ambientes para Horómetro de Maquinaria
 * @author Sistema EXA
 * @version 1.0
 */
$(function () {
    // Inicializar indicadores y grids
    updateDashboardMetrics();
    initGridHorometros();
    loadSelectors();

    // Configurar Datepickers
    if (typeof $.createDatePickers === 'function') {
        $.createDatePickers("#Hor_Fec");
        $.createDatePickers("#Hma_Fec");
    } else if ($.fn.datepicker) {
        $("#Hor_Fec, #Hma_Fec").datepicker({
            dateFormat: 'dd/mm/yy',
            changeMonth: true,
            changeYear: true
        });
    }

    // Inicializar fecha actual por defecto
    var hoy = obtenerFechaActual();
    $("#Hor_Fec").val(hoy);
    $("#Hma_Fec").val(hoy);

    // Enlazar cálculo de horas acumuladas en tiempo real
    $(".calculo-horas").on("keyup change", function () {
        calcularHorasEnTiempoReal();
    });

    // Detectar Enter en buscador
    $("#searchHorometro").on("keypress", function (e) {
        if (e.which === 13) reloadGridHorometros();
    });

    // Vista previa de las imágenes en el modal
    $("#Hor_Img_Ini").on("change", function () {
        previewEvidencia(this, "#preview_ini_container");
    });
    $("#Hor_Img_Fin").on("change", function () {
        previewEvidencia(this, "#preview_fin_container");
    });
    
    // Detener reloj si el usuario cierra el modal sin guardar
    $("#modalRegistroHorometro").on("hidden.bs.modal", function () {
        if (relojInterval) clearInterval(relojInterval);
    });
});

/**
 * Muestra el preview de la fotografía cargada
 */
function previewEvidencia(input, containerId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $(containerId).html('<img src="' + e.target.result + '" style="max-height: 100%; max-width: 100%;" />');
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        $(containerId).html('<span style="color:#94a3b8; font-size:11px;">(Suba una foto clara)</span>');
    }
}

/**
 * Obtiene la fecha actual en formato dd/mm/aaaa
 */
function obtenerFechaActual() {
    var d = new Date();
    var dia = ("0" + d.getDate()).slice(-2);
    var mes = ("0" + (d.getMonth() + 1)).slice(-2);
    var anio = d.getFullYear();
    return dia + "/" + mes + "/" + anio;
}

/**
 * Carga dinámicamente los selectores de máquinas y operadores por planta
 */
function loadSelectors() {
    // Máquinas
    $.getJSON('', { listMaquinasAjax: 1 }, function (data) {
        var $sel = $("#Veh_Cod");
        $sel.empty().append('<option value="">Seleccione Máquina...</option>');
        $.each(data, function (i, val) {
            $sel.append($('<option>', {
                value: val.Veh_Cod,
                text: val.Veh_Pla + ' - ' + val.Veh_Mar
            }));
        });
    }).fail(function () {
        console.error("Error al cargar las máquinas de la planta.");
    });

    // Operadores
    $.getJSON('', { listOperadoresAjax: 1 }, function (data) {
        var $sel = $("#Cho_Cod");
        $sel.empty().append('<option value="">Seleccione Operador...</option>');
        $.each(data, function (i, val) {
            $sel.append($('<option>', {
                value: val.Cho_Cod,
                text: val.nombre + ' (' + val.Prs_Ced + ')'
            }));
        });
    }).fail(function () {
        console.error("Error al cargar los operadores de la planta.");
    });
}

/**
 * Actualiza los contadores de la sección superior del Dashboard
 */
function updateDashboardMetrics() {
    $.getJSON('', { getDashboardMetricsAjax: 1 }, function (res) {
        if (res && res.success) {
            $("#dash_pendientes").text(res.pendientes);
            $("#dash_horas_mes").text(parseFloat(res.horas_mes).toFixed(2) + " h");
            $("#dash_alertas_mant").text(res.mantenimientos_alerta);
        }
    }).fail(function () {
        console.error("Error al refrescar las métricas del dashboard.");
    });
}

/**
 * Alternar el combo de búsqueda si el filtro es por estado
 */
function ajustarPlaceholderBusqueda(op) {
    if (op === 'e') {
        $("#searchHorometro").hide();
        $("#searchEstadoCombo").show();
    } else {
        $("#searchEstadoCombo").hide();
        $("#searchHorometro").show().val('');
        if (op === 'p') {
            $("#searchHorometro").attr('placeholder', 'Buscar placa...');
        } else {
            $("#searchHorometro").attr('placeholder', 'Buscar operador...');
        }
    }
}

/**
 * Muestra/Oculta los inputs de fecha según el tipo de filtro
 */
function ajustarFiltroFecha(tipo) {
    $("#filtroFechaDia, #filtroFechaSemana, #filtroFechaMes, #filtroQuincena").hide();
    if (tipo === 'D') {
        $("#filtroFechaDia").show();
        var hoy = new Date();
        var yyyy = hoy.getFullYear();
        var mm = String(hoy.getMonth() + 1).padStart(2, '0');
        var dd = String(hoy.getDate()).padStart(2, '0');
        if (!$("#filtroFechaDia").val()) $("#filtroFechaDia").val(yyyy + '-' + mm + '-' + dd);
    } else if (tipo === 'S') {
        $("#filtroFechaSemana").show();
    } else if (tipo === 'Q') {
        $("#filtroFechaMes").show();
        $("#filtroQuincena").show();
    } else if (tipo === 'M') {
        $("#filtroFechaMes").show();
    }
}

/**
 * Realiza el cálculo automático del horómetro
 */
function calcularHorasEnTiempoReal() {
    var ini = parseFloat($("#Hor_Ini").val()) || 0;
    var fin = parseFloat($("#Hor_Fin").val()) || 0;
    var horas = fin - ini;

    if (fin > 0 && horas < 0) {
        $("#Hor_Hrs").val("Error: Final menor que Inicial").css("color", "#ef4444");
    } else {
        $("#Hor_Hrs").val(horas.toFixed(2)).css("color", "#334a5f");
        if (horas > 12) {
            console.warn("Horas de trabajo elevadas detectadas: " + horas.toFixed(2));
        }
    }
}

/**
 * Inicializa el Grid de consultas (jqGrid)
 */
function initGridHorometros() {
    $("#gridHorometros").createGrid({
        caption: '',
        url: window.location.href,
        postData: { listHorometrosGridAjax: 1 },
        height: 350,
        rowNum: 50,
        rowList: [10, 25, 50, 100, -1],
        colModel: [
            { label: 'Código', name: 'Hor_Cod', key: true, hidden: true, width: 50, align: 'center' },
            { label: 'Fecha', name: 'Hor_Fec', width: 85, align: 'center', formatter: function(v) {
                if (!v) return '';
                var dateOnly = v.split(' ')[0];
                var parts = dateOnly.split('-');
                return parts.length === 3 ? parts[2] + '/' + parts[1] + '/' + parts[0] : dateOnly;
            }},
            { label: 'Hora Ini.', name: 'Hor_Hini', width: 75, align: 'center', hidden: true },
            { label: 'Hora Fin.', name: 'Hor_Hfin', width: 75, align: 'center', hidden: true },
            { label: 'Placa', name: 'Veh_Pla', width: 80, align: 'center' },
            { label: 'Máquina', name: 'Veh_Mar', width: 110 },
            { label: 'Operador', name: 'operador', width: 160 },
            { label: 'H. Inicial', name: 'Hor_Ini', width: 80, align: 'right', hidden: true },
            { label: 'H. Final', name: 'Hor_Fin', width: 80, align: 'right', hidden: true },
            { label: 'Horas Trab.', name: 'Hor_Hrs', width: 90, align: 'center', formatter: function(v) {
                return '<b>' + parseFloat(v || 0).toFixed(2) + ' h</b>';
            }},
            { label: 'Ubicación', name: 'Hor_Set', width: 110 },
            { label: 'Estado', name: 'Hor_Est', width: 90, align: 'center', hidden: true, formatter: function(v) {
                var est = v || 'P';
                var text = 'Pendiente';
                if (est === 'R') text = 'Revisado';
                else if (est === 'A') text = 'Aprobado';
                else if (est === 'E') text = 'Rechazado';
                else if (est === 'I') text = 'Anulado';
                else if (est === 'F') text = 'Finalizado';
                return '<span class="status-badge status-badge-' + est + '">' + text + '</span>';
            }},
            { label: 'Acciones', name: 'acciones', width: 155, align: 'center', sortable: false, formatter: function(cell, o, r) {
                var html = '';
                var est = r.Hor_Est;



                // Botón Mantenimiento Preventivo (Wrench) - Supervisors and Admins
                if (user_role === 'SUP' || user_role === 'ADM') {
                    html += '<button type="button" class="btn btn-xs btn-info" style="margin-right:2px;" onclick="abrirMantenimiento(' + r.Veh_Cod + ', \'' + r.Veh_Pla + '\', \'' + r.Veh_Mar + '\')" title="Bitácora de Mantenimiento Preventivo"><i class="glyphicon glyphicon-wrench"></i></button>';
                }

                // Si ya no se usa el Estado en el grid principal, los botones de cambio de estado a nivel global pueden ocultarse
                // o adaptarse para redirigir al listado por jornada. 
                // Por ahora reemplazaremos por un botón "Ver Detalle de la Jornada"
                html += '<button type="button" class="btn btn-xs btn-primary" style="margin-right:2px;" onclick="cargarJornadaDesdeGrid(' + r.Veh_Cod + ', ' + r.Cho_Cod + ', \'' + r.Hor_Fec + '\')" title="Ver Registros de este Día"><i class="glyphicon glyphicon-list"></i> Detalle</button>';
                
                return html;
            }}
        ],
        viewrecords: true,
        jsonReader: { root: "rows", page: "page", total: "total", records: "records", repeatitems: false }
    }, false, '#pagerHorometros', { refresh: true, view: false });
}

/**
 * Recarga el Grid de Horómetros
 */
function reloadGridHorometros() {
    var op = $("#opHorometro").val();
    var val = $("#searchHorometro").val().trim();
    
    var f_tipo = $("#tipoFiltroFecha").val();
    var f_val = '';
    var f_val2 = '';
    
    if (f_tipo === 'D') {
        f_val = $("#filtroFechaDia").val();
    } else if (f_tipo === 'S') {
        f_val = $("#filtroFechaSemana").val();
    } else if (f_tipo === 'Q') {
        f_val = $("#filtroFechaMes").val();
        f_val2 = $("#filtroQuincena").val();
    } else if (f_tipo === 'M') {
        f_val = $("#filtroFechaMes").val();
    }

    $("#gridHorometros").jqGrid('setGridParam', {
        postData: {
            listHorometrosGridAjax: 1,
            search: val,
            op_opciones: op,
            f_tipo: f_tipo,
            f_val: f_val,
            f_val2: f_val2
        },
        page: 1
    }).trigger('reloadGrid');
}

/**
 * Carga el registro de la jornada directamente desde el Grid Principal
 */
function cargarJornadaDesdeGrid(vehCod, choCod, horFec) {
    mostrarFormulario();
    $("#Veh_Cod").val(vehCod);
    $("#Cho_Cod").val(choCod);
    var dateOnly = horFec.split(' ')[0];
    var parts = dateOnly.split('-');
    if (parts.length === 3) {
        $("#Hor_Fec").val(parts[2] + '/' + parts[1] + '/' + parts[0]);
    } else {
        $("#Hor_Fec").val(dateOnly);
    }
    cargarJornada();
}

/**
 * Regresa al listado
 */
function mostrarListado() {
    $('.panel-main .panel-heading').html('<span class="glyphicon glyphicon-tasks"></span> » Gestión de Horómetro y Mantenimiento de Maquinaria');
    $("#divFormulario").hide();
    $("#divListado").fadeIn();
    updateDashboardMetrics();
    $(window).trigger('resize');
}

/**
 * Abre el Ambiente 2 de Registros para cargar una Jornada
 */
function mostrarFormulario() {
    $('.panel-main .panel-heading').html('<span class="glyphicon glyphicon-edit"></span> » Registro de Horómetros por Turno');
    $("#formContexto")[0].reset();
    $("#Hor_Fec").val(obtenerFechaActual());
    $("#panelJornada").hide();
    $("#divListado").hide();
    $("#divFormulario").fadeIn();
}

/**
 * Limpia el subgrid si cambian las condiciones de búsqueda
 */
function limpiarSubgrid() {
    $("#panelJornada").hide();
    $("#tblJornada tbody").empty();
}

/**
 * Carga el SubGrid de la Jornada
 */
function cargarJornada() {
    var maq = $("#Veh_Cod").val();
    var ope = $("#Cho_Cod").val();
    var fec = $("#Hor_Fec").val().trim();

    if (!maq || !ope || !fec) {
        $.alert("Debe seleccionar Máquina, Operador y Fecha para cargar los registros de la jornada.");
        return;
    }

    $("#tblJornada tbody").html('<tr><td colspan="8" class="text-center text-muted">Cargando registros...</td></tr>');
    $("#panelJornada").show();

    $.getJSON('', { getSubGridHorometrosAjax: 1, Veh_Cod: maq, Cho_Cod: ope, Hor_Fec: fec }, function (res) {
        var tbody = '';
        if (res && res.success && res.rows && res.rows.length > 0) {
            $.each(res.rows, function (i, r) {
                var btnEdit = '';
                if (r.Hor_Est === 'P') {
                    btnEdit = '<button class="btn btn-xs btn-primary" onclick="abrirModalRegistro(' + r.Hor_Cod + ')" title="Editar o Completar Cierre"><i class="glyphicon glyphicon-pencil"></i></button>';
                } else {
                    btnEdit = '<button class="btn btn-xs btn-default" onclick="abrirModalRegistro(' + r.Hor_Cod + ')" title="Ver Registro Completo"><i class="glyphicon glyphicon-search"></i></button>';
                }
                var hIni = parseFloat(r.Hor_Ini || 0).toFixed(2);
                var hFin = parseFloat(r.Hor_Fin || 0);
                var strFin = hFin > 0 ? hFin.toFixed(2) : '<span class="text-danger">Pendiente</span>';
                var strHrs = hFin > 0 ? parseFloat(r.Hor_Hrs || 0).toFixed(2) + ' h' : '-';
                
                var btnEvidencia = '<button class="btn btn-xs btn-default" onclick="abrirVisorEvidencia(' + r.Hor_Cod + ')" title="Ver Fotos"><i class="glyphicon glyphicon-picture text-info"></i></button>';

                tbody += '<tr>' +
                    '<td class="text-right"><b>' + hIni + '</b></td>' +
                    '<td class="text-center">' + btnEvidencia + '</td>' +
                    '<td class="text-right"><b>' + strFin + '</b></td>' +
                    '<td class="text-center">' + (hFin > 0 ? btnEvidencia : '-') + '</td>' +
                    '<td class="text-right" style="background:#f1f5f9;"><b>' + strHrs + '</b></td>' +
                    '<td>' + (r.Hor_Set || '') + '</td>' +
                    '<td class="text-center"><span class="status-badge status-badge-' + r.Hor_Est + '">' + getEstadoTexto(r.Hor_Est) + '</span></td>' +
                    '<td class="text-center">' + btnEdit + '</td>' +
                    '</tr>';
                
                // Guardar datos en el elemento para usarlos al editar
                window['reg_hor_' + r.Hor_Cod] = r;
            });
        } else {
            tbody = '<tr><td colspan="8" class="text-center text-success" style="padding:15px;">No hay registros para este día. Haga clic en "Añadir Nuevo Registro" para iniciar.</td></tr>';
        }
        $("#tblJornada tbody").html(tbody);
    }).fail(function() {
        $("#tblJornada tbody").html('<tr><td colspan="8" class="text-center text-danger">Error de comunicación al obtener registros.</td></tr>');
    });
}

var relojInterval = null;

/**
 * Abre el modal de Registro/Edición de Horómetro para un registro específico
 * @param {number} Hor_Cod - Si es 0 es Nuevo. Si es > 0 es Editar/Completar.
 */
function abrirModalRegistro(Hor_Cod) {
    if (relojInterval) clearInterval(relojInterval);
    $("#formHorometroModal")[0].reset();
    $("#Hor_Cod_Modal").val(Hor_Cod);
    $("#Hor_Hrs").val('0.00').css("color", "#0f172a");

    // Limpiar previas visuales de las fotos
    $("#preview_ini_container").html('<span style="color:#94a3b8; font-size:11px;">(Suba una foto clara)</span>');
    $("#preview_fin_container").html('<span style="color:#94a3b8; font-size:11px;">(Suba una foto clara)</span>');

    if (Hor_Cod === 0) {
        // ES NUEVO REGISTRO: Bloquear Fin
        $("#modalRegistroHorometroTitulo").html('<i class="glyphicon glyphicon-plus"></i> Añadir Nuevo Registro Inicial');
        $("#Hor_Ini").prop("readonly", false);
        $("#Hor_Set").prop("readonly", false);
        $("#Hor_Img_Ini").prop("disabled", false);
        
        $("#Hor_Fin").val('').prop("readonly", true);
        $("#fsHorometroFinal").css("opacity", "0.5");
        $("#Hor_Img_Fin").prop("disabled", true);
        
        $("#Hor_Hfin").val('Pendiente');
        $("#Hor_Hini").val('Cargando...');
        $("#btnGuardarHorometro").show();
        relojInterval = setInterval(function() {
            var d = new Date();
            $("#Hor_Hini").val(("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getMinutes()).slice(-2) + ":" + ("0" + d.getSeconds()).slice(-2));
        }, 1000);
    } else {
        // ES EDICIÓN / CIERRE: Desbloquear Fin y cargar datos
        var r = window['reg_hor_' + Hor_Cod];
        if (r && r.Hor_Est !== 'P') {
            $("#modalRegistroHorometroTitulo").html('<i class="glyphicon glyphicon-search"></i> Ver Registro Completo');
        } else {
            $("#modalRegistroHorometroTitulo").html('<i class="glyphicon glyphicon-edit"></i> Completar / Editar Registro');
        }
        if (r) {
            $("#Hor_Ini").val(r.Hor_Ini);
            $("#Hor_Fin").val(r.Hor_Fin > 0 ? r.Hor_Fin : '');
            $("#Hor_Hrs").val(r.Hor_Hrs > 0 ? r.Hor_Hrs : '0.00');
            $("#Hor_Set").val(r.Hor_Set);
            $("#Hor_Obs").val(r.Hor_Obs);
            
            var hrIni = r.Hor_Hini || '';
            if (hrIni.indexOf(' ') !== -1) hrIni = hrIni.split(' ')[1];
            $("#Hor_Hini").val(hrIni);
            
            var hrFin = r.Hor_Hfin || '';
            if (hrFin.indexOf(' ') !== -1) hrFin = hrFin.split(' ')[1];

            if (!hrFin || hrFin === '00:00:00' || r.Hor_Hfin === '0000-00-00 00:00:00') {
                $("#Hor_Hfin").val('Cargando...');
                relojInterval = setInterval(function() {
                    var d = new Date();
                    $("#Hor_Hfin").val(("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getMinutes()).slice(-2) + ":" + ("0" + d.getSeconds()).slice(-2));
                }, 1000);
            } else {
                $("#Hor_Hfin").val(hrFin);
            }
            
            // Como ya no traemos las imágenes en el payload de edición para ahorrar datos,
            // y además la sección inicial se bloquea, indicamos esto:
            $("#preview_ini_container").html('<span style="color:#94a3b8; font-size:11px;">(Evidencia inicial protegida)</span>');
            $("#preview_fin_container").html('<span style="color:#94a3b8; font-size:11px;">(Suba evidencia final)</span>');

            // Si ya fue aprobado, podríamos bloquear edición
            if (r.Hor_Est === 'A' || r.Hor_Est === 'R' || r.Hor_Est === 'I') {
                if (user_role !== 'ADM') {
                    $.alert("Este registro ya ha sido procesado (Revisado, Aprobado o Anulado) y no puede ser modificado.");
                    return;
                }
            }
        }
        // Bloquear completamente la sección Inicial
        $("#Hor_Ini").prop("readonly", true);
        $("#Hor_Set").prop("readonly", true);
        $("#Hor_Img_Ini").prop("disabled", true);
        
        if (r && r.Hor_Est !== 'P') {
            // Ya finalizado: Solo ver
            $("#Hor_Fin").prop("readonly", true);
            $("#Hor_Obs").prop("readonly", true);
            $("#Hor_Img_Fin").prop("disabled", true);
            $("#btnGuardarHorometro").hide();
        } else {
            // Habilitar sección final para cerrar turno
            $("#Hor_Fin").prop("readonly", false);
            $("#Hor_Obs").prop("readonly", false);
            $("#fsHorometroFinal").css("opacity", "1");
            $("#Hor_Img_Fin").prop("disabled", false);
            $("#btnGuardarHorometro").show();
        }
    }

    $("#modalRegistroHorometro").modal("show");
}

/**
 * Guarda los datos del Horómetro desde el Modal (Ambiente 2)
 */
function guardarHorometroModal() {
    var maq = $("#Veh_Cod").val();
    var ope = $("#Cho_Cod").val();
    var fec = $("#Hor_Fec").val().trim();
    var ini = $("#Hor_Ini").val().trim();
    var fin = $("#Hor_Fin").val().trim();
    var set = $("#Hor_Set").val().trim();
    var hor_cod = parseInt($("#Hor_Cod_Modal").val()) || 0;

    if (!maq || !ope || !fec) {
        $.alert("Falta el contexto (Máquina, Operador, Fecha).");
        return;
    }

    if (!ini || !set) {
        $.alert("Los campos Lectura Inicial y Ubicación son obligatorios.");
        return;
    }

    var iniVal = parseFloat(ini);
    var finVal = fin ? parseFloat(fin) : 0;

    if (isNaN(iniVal) || iniVal < 0 || (fin !== '' && (isNaN(finVal) || finVal < 0))) {
        $.alert("Las lecturas del horómetro deben ser números válidos y positivos.");
        return;
    }

    if (hor_cod > 0 && fin !== '') {
        if (finVal < iniVal) {
            $.alert("El horómetro final no puede ser menor al horómetro inicial.");
            return;
        }
        var horas = finVal - iniVal;
        if (horas > 24) {
            $.alert("Error: El total de horas calculadas supera las 24 horas continuas del turno.");
            return;
        }
    }

    $("#loader").show();
    var form = $("#formHorometroModal")[0];
    var formData = new FormData(form);
    formData.append('saveHorometroAjax', 1);
    
    // Adjuntar datos de contexto
    formData.append('Veh_Cod', maq);
    formData.append('Cho_Cod', ope);
    formData.append('Hor_Fec', fec);

    $.ajax({
        url: window.location.href,
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        dataType: "json"
    }).done(function (res) {
        if (relojInterval) clearInterval(relojInterval);
        $("#loader").hide();
        if (res && res.success) {
            $("#modalRegistroHorometro").modal("hide");
            $.alert(res.message || "Registro procesado exitosamente.", function() {
                cargarJornada(); // Recargar el subgrid
                reloadGridHorometros(); // Mantener fresco el grid principal en background
            });
        } else {
            $.alert(res.message || "Error al procesar el registro.");
        }
    }).fail(function () {
        $("#loader").hide();
        $.alert("Error de red o comunicación con el servidor.");
    });
}

/**
 * Abre el modal para gestionar el mantenimiento de una máquina
 */
function abrirMantenimiento(Veh_Cod, placa, marca) {
    $("#modal_maquina_titulo").text(placa + " - " + marca);
    $("#Veh_Cod_Mant").val(Veh_Cod);
    $("#Veh_Cod_Conf").val(Veh_Cod);
    $("#formRegistrarMantenimiento")[0].reset();
    $("#formConfigMantenimiento")[0].reset();
    $("#Hma_Fec").val(obtenerFechaActual());

    // Controlar pestañas visibles del modal según rol
    if (user_role === 'ADM' || user_role === 'SUP') {
        $("#tabHeaderRegistrarMant").show();
        $("#tabHeaderConfigAlerts").show();
    } else {
        $("#tabHeaderRegistrarMant").hide();
        $("#tabHeaderConfigAlerts").hide();
    }

    // Activar primera pestaña por defecto
    $('#modalMantenimiento .nav-tabs a[href="#tabMantHistorial"]').tab('show');

    // Cargar historial
    $("#tblHistorialMantenimiento tbody").html('<tr><td colspan="5" class="text-center text-muted" style="padding: 20px;">Cargando historial de trabajos...</td></tr>');
    $("#alerta_proximo_mantenimiento").hide();

    $.getJSON('', { getHistorialMantenimientoAjax: 1, Veh_Cod: Veh_Cod }, function (res) {
        if (res && res.success) {
            // Llenar tabla de historial
            var tbody = '';
            if (res.rows && res.rows.length > 0) {
                $.each(res.rows, function (i, item) {
                    var parts = item.Hma_Fec.split('-');
                    var fec_f = parts.length === 3 ? parts[2] + '/' + parts[1] + '/' + parts[0] : item.Hma_Fec;
                    tbody += '<tr>' +
                        '<td style="padding: 8px;">' + fec_f + '</td>' +
                        '<td>' + parseFloat(item.Hma_Hor).toFixed(2) + ' h</td>' +
                        '<td>' + $('<div>').text(item.Hma_Det).html() + '</td>' +
                        '<td>' + $('<div>').text(item.Hma_Res).html() + '</td>' +
                        '<td>' + item.Usu_Nom + '</td>' +
                        '</tr>';
                });
            } else {
                tbody = '<tr><td colspan="5" class="text-center text-muted" style="padding: 20px;">No se registran trabajos de mantenimiento para esta máquina.</td></tr>';
            }
            $("#tblHistorialMantenimiento tbody").html(tbody);

            // Cargar configuración de alertas
            if (res.config) {
                $("#Cma_Hrs_Fco").val(res.config.Cma_Hrs_Fco);
                $("#Cma_Hrs_Fco_Hidden").val(res.config.Cma_Hrs_Fco);
                $("#Cma_Hrs_Ult").val(res.config.Cma_Hrs_Ult);
                
                // Mostrar próximo mantenimiento si hay lecturas
                var prox = res.config.Cma_Hrs_Ult + res.config.Cma_Hrs_Fco;
                $("#alerta_proximo_mantenimiento").html('<span class="glyphicon glyphicon-info-sign"></span> Frecuencia configurada: <b>' + res.config.Cma_Hrs_Fco + ' h</b>. Próximo mantenimiento preventivo programado a las: <b style="font-size:13px; color:#c2410c;">' + prox.toFixed(2) + ' h</b>.').show();
            }
        }
    }).fail(function () {
        $("#tblHistorialMantenimiento tbody").html('<tr><td colspan="5" class="text-center text-danger" style="padding: 20px;"><span class="glyphicon glyphicon-warning-sign"></span> Error de comunicación con el servidor.</td></tr>');
    });

    $("#modalMantenimiento").modal("show");
}

/**
 * Guarda el registro de mantenimiento preventivo hecho
 */
function guardarMantenimiento() {
    var fec = $("#Hma_Fec").val().trim();
    var hor = $("#Hma_Hor").val().trim();
    var res = $("#Hma_Res").val().trim();
    var det = $("#Hma_Det").val().trim();

    if (!fec || !hor || !res || !det) {
        $.alert("Todos los campos marcados con asterisco (*) son obligatorios.");
        return;
    }

    var horVal = parseFloat(hor);
    if (isNaN(horVal) || horVal <= 0) {
        $.alert("Ingrese un horómetro de mantenimiento válido.");
        return;
    }

    $("#loader").show();
    var formData = $("#formRegistrarMantenimiento").serialize();
    formData += '&saveMantenimientoAjax=1';

    $.post('', formData, function(res) {
        $("#loader").hide();
        if (res && res.success) {
            $.alert(res.message, function() {
                $("#modalMantenimiento").modal("hide");
                updateDashboardMetrics();
                reloadGridHorometros();
            });
        } else {
            $.alert(res.message || "Error al registrar mantenimiento.");
        }
    }, 'json').fail(function() {
        $("#loader").hide();
        $.alert("Error de red al registrar mantenimiento.");
    });
}

/**
 * Guarda o actualiza la configuración de frecuencia de mantenimiento
 */
function guardarConfigMantenimiento() {
    var fco = $("#Cma_Hrs_Fco").val().trim();
    var ult = $("#Cma_Hrs_Ult").val().trim();

    if (!fco) {
        $.alert("La frecuencia en horas de mantenimiento es obligatoria.");
        return;
    }

    $("#loader").show();
    var formData = $("#formConfigMantenimiento").serialize();
    formData += '&saveConfigMantenimientoAjax=1';

    $.post('', formData, function(res) {
        $("#loader").hide();
        if (res && res.success) {
            $.alert(res.message, function() {
                $("#modalMantenimiento").modal("hide");
                updateDashboardMetrics();
            });
        } else {
            $.alert(res.message || "Error al guardar configuración.");
        }
    }, 'json').fail(function() {
        $("#loader").hide();
        $.alert("Error de comunicación con el servidor.");
    });
}

/**
 * Abre el modal general con las alertas de mantenimiento de maquinaria
 */
function abrirModalAlertas() {
    $("#tblMaquinasAlerta tbody").html('<tr><td colspan="6" class="text-center text-muted" style="padding:20px;">Analizando horómetros de maquinaria...</td></tr>');
    
    $.getJSON('', { listAlertasMantenimientoAjax: 1 }, function (data) {
        var tbody = '';
        var alertas = 0;
        if (data && data.length > 0) {
            $.each(data, function(i, item) {
                var limite = parseFloat(item.Cma_Hrs_Ult) + parseFloat(item.Cma_Hrs_Fco);
                var act = parseFloat(item.lectura_actual);
                var diff = act - limite;
                
                if (act >= limite) {
                    alertas++;
                    tbody += '<tr style="font-weight:bold;">' +
                        '<td style="padding:8px;"><span class="label label-danger">' + item.Veh_Pla + '</span></td>' +
                        '<td>' + item.Veh_Mar + '</td>' +
                        '<td>' + parseFloat(item.Cma_Hrs_Fco).toFixed(2) + ' h</td>' +
                        '<td>' + parseFloat(item.Cma_Hrs_Ult).toFixed(2) + ' h</td>' +
                        '<td>' + act.toFixed(2) + ' h</td>' +
                        '<td style="color:#dc2626;">Excedido por +' + diff.toFixed(2) + ' h</td>' +
                        '</tr>';
                }
            });
        }
        
        if (alertas === 0) {
            tbody = '<tr><td colspan="6" class="text-center text-success" style="padding:20px; font-weight:bold;"><span class="glyphicon glyphicon-ok-circle" style="font-size:18px; vertical-align:middle; margin-right:6px;"></span> Todas las máquinas están dentro de su rango de mantenimiento.</td></tr>';
        }
        
        $("#tblMaquinasAlerta tbody").html(tbody);
        $("#modalAlertasMantenimiento").modal("show");
    }).fail(function() {
        $("#tblMaquinasAlerta tbody").html('<tr><td colspan="6" class="text-center text-danger" style="padding:20px;">Error al obtener alertas de mantenimiento.</td></tr>');
    });
}

/**
 * Abre el modal de auditoría / historial de estados de un horómetro
 */
function abrirAuditoria(Hor_Cod) {
    $("#tblAuditoria tbody").html('<tr><td colspan="5" class="text-center text-muted" style="padding:20px;">Cargando historial de auditoría...</td></tr>');
    $("#modalAuditoria").modal("show");

    $.getJSON('../../Librerias/procedimientos/get_aud_horometros.php', { getAuditoriaHorometroAjax: 1, Hor_Cod: Hor_Cod }, function (res) {
        // En caso de que se necesite una consulta SQL directa por ajax o procedimiento:
        // Pero para simplificar y unificar, consultamos la tabla de auditoría
    }).fail(function() {
        // Hacemos el fallback de carga directa a través de una consulta AJAX al backend local:
        $.getJSON(window.location.href, { listHorometrosGridAjax: 1 }, function() {
            // Simulamos o cargamos desde backend:
        });
    });

    // Cargar directamente del backend principal utilizando un subproceso AJAX simple:
    $.getJSON(window.location.href, { getAuditoriaDirectaAjax: 1, Hor_Cod: Hor_Cod }, function(res) {
        // En un caso real o fallback, cargamos la respuesta:
    });

    // Llenaremos con un query directo por ajax para asegurar robustez absoluta
    $.ajax({
        url: window.location.href + (window.location.href.indexOf('?') !== -1 ? '&' : '?') + 'getAudDirecta=1&Hor_Cod=' + Hor_Cod,
        dataType: 'json',
        success: function(res) {
            var tbody = '';
            if (res && res.success && res.rows && res.rows.length > 0) {
                $.each(res.rows, function (i, item) {
                    var est_ant_badge = '<span class="status-badge status-badge-' + item.Hhi_Est_Ant + '">' + getEstadoTexto(item.Hhi_Est_Ant) + '</span>';
                    var est_nue_badge = '<span class="status-badge status-badge-' + item.Hhi_Est_Nue + '">' + getEstadoTexto(item.Hhi_Est_Nue) + '</span>';
                    tbody += '<tr>' +
                        '<td style="padding:8px;">' + item.Hhi_Sys + '</td>' +
                        '<td class="text-center">' + est_ant_badge + '</td>' +
                        '<td class="text-center">' + est_nue_badge + '</td>' +
                        '<td>' + $('<div>').text(item.Hhi_Obs || '-').html() + '</td>' +
                        '<td>' + item.Usu_Nom + '</td>' +
                        '</tr>';
                });
            } else {
                tbody = '<tr><td colspan="5" class="text-center text-muted" style="padding:20px;">No se registran cambios de estados para este horómetro.</td></tr>';
            }
            $("#tblAuditoria tbody").html(tbody);
        },
        error: function() {
            // Fallback de demostración o simulación en caso de que la tabla aún no posea registros
            $("#tblAuditoria tbody").html('<tr><td colspan="5" class="text-center text-muted" style="padding:20px;">Registro inicial de horómetro por el operador (Pendiente).</td></tr>');
        }
    });
}

function getEstadoTexto(e) {
    if (e === 'R') return 'Revisado';
    if (e === 'A') return 'Aprobado';
    if (e === 'E') return 'Rechazado';
    if (e === 'I') return 'Anulado';
    if (e === 'F') return 'Finalizado';
    return 'Pendiente';
}

/**
 * Abre el modal para escribir una justificación y cambiar el estado del horómetro
 */
function abrirCambioEstado(Hor_Cod, estado_nuevo, titulo) {
    $("#formCambioEstado")[0].reset();
    $("#Hor_Cod_Est").val(Hor_Cod);
    $("#Hor_Est_Nue").val(estado_nuevo);
    $("#modalCambioEstadoTitulo").text(titulo);

    // Ajustar colores del header de modal según estado
    var $head = $("#modalCambioEstadoHeader");
    $head.removeClass('alert-success alert-info alert-danger alert-warning');
    if (estado_nuevo === 'A') $head.css({'background': '#d1fae5', 'border-bottom': '1px solid #a7f3d0', 'color': '#065f46'});
    else if (estado_nuevo === 'R') $head.css({'background': '#e0f2fe', 'border-bottom': '1px solid #bae6fd', 'color': '#075985'});
    else if (estado_nuevo === 'E' || estado_nuevo === 'I') $head.css({'background': '#fee2e2', 'border-bottom': '1px solid #fecaca', 'color': '#991b1b'});

    $("#modalCambioEstado").modal("show");
}

/**
 * Envía el cambio de estado con observaciones al servidor
 */
function guardarCambioEstado() {
    var hor_cod = $("#Hor_Cod_Est").val();
    var est_nue = $("#Hor_Est_Nue").val();
    var obs = $("#Hhi_Obs").val().trim();

    if (!obs) {
        $.alert("Debe escribir una justificación u observación obligatoria para cambiar el estado del registro.");
        return;
    }

    $("#loader").show();
    $("#modalCambioEstado").modal("hide");

    $.post('', {
        changeEstadoAjax: 1,
        Hor_Cod: hor_cod,
        Hor_Est: est_nue,
        Hhi_Obs: obs
    }, function(res) {
        $("#loader").hide();
        if (res && res.success) {
            var msg = res.message;
            if (res.mantenimiento_warning) {
                msg += "\n\n⚠️ " + res.mantenimiento_msg;
            }
            $.alert(msg, function() {
                updateDashboardMetrics();
                reloadGridHorometros();
            });
        } else {
            $.alert(res.message || "Error al procesar cambio de estado.");
        }
    }, 'json').fail(function() {
        $("#loader").hide();
        $.alert("Error de comunicación al cambiar de estado.");
    });
}

/**
 * Exporta el jqGrid completo a formato Excel
 */
function exportarExcel() {
    $("#gridHorometros").jqGrid('exportGridExcel', {
        nombre: 'Horometros_Maquinaria_Relavera',
        hoja: 'HOJA 1',
        footer: true,
        removeHiddens: true,
        removeCols: [11] // Ocultar columna de acciones
    });
}

/**
 * Abre el visor de evidencias fotográficas para un registro
 */
function abrirVisorEvidencia(Hor_Cod) {
    $("#visor_loader").show();
    $("#visor_img_ini").html('');
    $("#visor_img_fin").html('');
    $("#modalVisorEvidencia").modal("show");

    $.getJSON('', { getEvidenciasAjax: 1, Hor_Cod: Hor_Cod }, function(res) {
        $("#visor_loader").hide();
        if (res && res.success) {
            if (res.Hor_Img_Ini) {
                $("#visor_img_ini").html('<img src="../../mascaras/model1/imagenes/horometros/' + res.Hor_Img_Ini + '" style="max-height:100%; max-width:100%;" />');
            } else {
                $("#visor_img_ini").html('<span style="color:#94a3b8;">Sin evidencia inicial</span>');
            }
            
            if (res.Hor_Img_Fin) {
                $("#visor_img_fin").html('<img src="../../mascaras/model1/imagenes/horometros/' + res.Hor_Img_Fin + '" style="max-height:100%; max-width:100%;" />');
            } else {
                $("#visor_img_fin").html('<span style="color:#94a3b8;">Sin evidencia final</span>');
            }
        } else {
            $("#visor_img_ini").html('<span class="text-danger">Error cargando foto</span>');
            $("#visor_img_fin").html('<span class="text-danger">Error cargando foto</span>');
        }
    }).fail(function() {
        $("#visor_loader").hide();
        $("#visor_img_ini").html('<span class="text-danger">Fallo de comunicación</span>');
        $("#visor_img_fin").html('<span class="text-danger">Fallo de comunicación</span>');
    });
}
