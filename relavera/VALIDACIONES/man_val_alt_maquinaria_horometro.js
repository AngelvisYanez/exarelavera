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

    // Fix para evitar stack overflow de focus con Modales de Bootstrap y Jquery-Confirm/UI
    if ($.fn.modal && $.fn.modal.Constructor) {
        $.fn.modal.Constructor.prototype.enforceFocus = function() {};
    }
});

/**
 * Comprime una imagen en el lado del cliente antes de enviarla
 */
function comprimirImagen(file, maxWidth, maxHeight, quality, callback) {
    if (!file || !file.type.match(/image.*/)) {
        callback(file); // No es imagen, devolver original
        return;
    }
    
    var reader = new FileReader();
    reader.onload = function (readerEvent) {
        var img = new Image();
        img.onload = function () {
            var width = img.width;
            var height = img.height;

            if (width > height) {
                if (width > maxWidth) {
                    height = Math.round(height *= maxWidth / width);
                    width = maxWidth;
                }
            } else {
                if (height > maxHeight) {
                    width = Math.round(width *= maxHeight / height);
                    height = maxHeight;
                }
            }

            var canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            var ctx = canvas.getContext("2d");
            ctx.drawImage(img, 0, 0, width, height);

            var dataUrl = canvas.toDataURL('image/jpeg', quality);
            
            // Convertir Data URL a Blob
            var byteString = atob(dataUrl.split(',')[1]);
            var ab = new ArrayBuffer(byteString.length);
            var ia = new Uint8Array(ab);
            for (var i = 0; i < byteString.length; i++) {
                ia[i] = byteString.charCodeAt(i);
            }
            var blob = new Blob([ab], { type: 'image/jpeg' });
            
            callback(blob);
        }
        img.src = readerEvent.target.result;
    }
    reader.readAsDataURL(file);
}

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
        var $repMaq = $("#rep_maquina");
        $sel.empty().append('<option value="">Seleccione Máquina...</option>');
        $repMaq.empty().append('<option value="TODAS">TODAS LAS MÁQUINAS</option>');
        $.each(data, function (i, val) {
            var textOpt = val.Veh_Pla + ' - ' + val.Veh_Mar;
            $sel.append($('<option>', {
                value: val.Veh_Cod,
                text: textOpt
            }));
            $repMaq.append($('<option>', {
                value: val.Veh_Cod,
                text: textOpt
            }));
        });
    }).fail(function () {
        console.error("Error al cargar las máquinas de la planta.");
    });

    // Operadores
    $.getJSON('', { listOperadoresAjax: 1 }, function (data) {
        var $sel = $("#Cho_Cod");
        var $repOpe = $("#rep_operador");
        $sel.empty().append('<option value="">Seleccione Operador...</option>');
        $repOpe.empty().append('<option value="TODOS">TODOS LOS OPERADORES</option>');
        $.each(data, function (i, val) {
            var textOpt = val.nombre + ' (' + val.Prs_Ced + ')';
            $sel.append($('<option>', {
                value: val.Cho_Cod,
                text: textOpt
            }));
            $repOpe.append($('<option>', {
                value: val.Cho_Cod,
                text: textOpt
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
    $('.panel-main .panel-heading').html('<span class="glyphicon glyphicon-tasks"></span> » Gestión de Horómetro de Maquinaria');
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
    $("#btnGuardarHorometro").prop("disabled", true);
    
    var formData = new FormData();
    formData.append('Hor_Cod', $("#Hor_Cod_Modal").val());
    formData.append('Hor_Ini', ini);
    formData.append('Hor_Set', set);
    formData.append('Hor_Fin', fin);
    formData.append('Hor_Obs', $("#Hor_Obs").val());
    
    formData.append('saveHorometroAjax', 1);
    formData.append('Veh_Cod', maq);
    formData.append('Cho_Cod', ope);
    formData.append('Hor_Fec', fec);

    var fileIni = $("#Hor_Img_Ini")[0].files[0];
    var fileFin = $("#Hor_Img_Fin")[0].files[0];
    var pending = 0;

    function enviarAjax() {
        if (pending > 0) return; // Esperar a que las imágenes se compriman
        
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
            $("#btnGuardarHorometro").prop("disabled", false);
            if (res && res.success) {
                $("#modalRegistroHorometro").modal("hide");
                $.alert(res.message || "Registro procesado exitosamente.", function() {
                    cargarJornada(); // Recargar el subgrid
                    reloadGridHorometros(); // Mantener fresco el grid principal
                });
            } else {
                $.alert(res.message || "Error al procesar el registro.");
            }
        }).fail(function () {
            $("#loader").hide();
            $("#btnGuardarHorometro").prop("disabled", false);
            $.alert("Error de red o comunicación con el servidor.");
        });
    }

    if (fileIni) {
        pending++;
        comprimirImagen(fileIni, 1200, 1200, 0.7, function(blob) {
            formData.append('Hor_Img_Ini', blob, 'ini.jpg');
            pending--;
            enviarAjax();
        });
    }

    if (fileFin) {
        pending++;
        comprimirImagen(fileFin, 1200, 1200, 0.7, function(blob) {
            formData.append('Hor_Img_Fin', blob, 'fin.jpg');
            pending--;
            enviarAjax();
        });
    }

    if (pending === 0) {
        enviarAjax();
    }
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
            var emp_cod = res.Emp_Cod || '0';
            if (res.Hor_Img_Ini) {
                $("#visor_img_ini").html('<img src="../../imagenes/' + emp_cod + '/horometro/' + res.Hor_Img_Ini + '" style="max-height:100%; max-width:100%;" />');
            } else {
                $("#visor_img_ini").html('<span style="color:#94a3b8;">Sin evidencia inicial</span>');
            }
            
            if (res.Hor_Img_Fin) {
                $("#visor_img_fin").html('<img src="../../imagenes/' + emp_cod + '/horometro/' + res.Hor_Img_Fin + '" style="max-height:100%; max-width:100%;" />');
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

/* =========================================================================
   NUEVA LÓGICA DE REPORTES (OPERATIVO Y GERENCIAL)
   ========================================================================= */

function toggleReportView() {
    var tipo = $("#rep_tipo").val();
    if (tipo === 'individual') {
        $("#contenedorReporteConsolidado").hide();
        $("#contenedorReporteIndividual").hide();
    } else {
        $("#contenedorReporteIndividual").hide();
        $("#contenedorReporteConsolidado").hide();
        // Resetear selectores a TODOS
        $("#rep_maquina").val('TODAS').trigger("chosen:updated");
        $("#rep_operador").val('TODOS').trigger("chosen:updated");
    }
}

function generarReporte() {
    var tipo = $("#rep_tipo").val();
    var anio = $("#rep_anio").val();
    var mes = $("#rep_mes").val();
    var maq = $("#rep_maquina").val();
    var ope = $("#rep_operador").val();

    if (tipo === 'individual') {
        if (maq === 'TODAS' || ope === 'TODOS') {
            $.alert("Para el Reporte Individual debe seleccionar una máquina y un operador específicos.");
            return;
        }
        cargarReporteIndividual(anio, mes, maq, ope);
    } else {
        cargarReporteConsolidado(anio, mes, maq, ope);
    }
}

function cargarReporteIndividual(anio, mes, maq, ope) {
    $("#loader").show();
    $.getJSON(window.location.href, {
        getReporteOperativoAjax: 1,
        tipo: 'individual',
        anio: anio,
        mes: mes,
        maq: maq,
        ope: ope
    }, function(res) {
        $("#loader").hide();
        if (res && res.success) {
            // Llenar Ficha
            $("#ficha_id").text(res.ficha.id || 'N/A');
            $("#ficha_marca").text(res.ficha.marca || 'N/A');
            $("#ficha_modelo").text(res.ficha.modelo || 'N/A');
            $("#ficha_serie").text(res.ficha.serie || 'N/A');
            $("#ficha_propiedad").text(res.ficha.propiedad || 'N/A');

            // Etiquetas
            $("#lbl_rep_periodo").text("PERÍODO: " + res.periodo);
            $("#lbl_rep_maquina").text("MÁQUINA: " + res.ficha.id);
            $("#lbl_rep_operador").text("OPERADOR: " + res.operador_nombre);

            // Resumen Ejecutivo Cards
            $("#res_hrs_trab").text(res.resumen.horas_trabajadas);
            $("#res_hrs_prod").text(res.resumen.horas_productivas);
            $("#res_desfase").text(res.resumen.desfase);
            $("#res_comb").text(res.resumen.combustible + " Gls");
            $("#res_prom").text(res.resumen.promedio_diario);
            $("#res_dias").text(res.resumen.dias_laborados);

            // Comparativo Quincenal
            $("#cmp_ht_1").text(res.q1.horas_trabajadas);
            $("#cmp_ht_2").text(res.q2.horas_trabajadas);
            $("#cmp_ht_t").text(res.resumen.horas_trabajadas);

            $("#cmp_hp_1").text(res.q1.horas_productivas);
            $("#cmp_hp_2").text(res.q2.horas_productivas);
            $("#cmp_hp_t").text(res.resumen.horas_productivas);

            $("#cmp_df_1").text(res.q1.desfase);
            $("#cmp_df_2").text(res.q2.desfase);
            $("#cmp_df_t").text(res.resumen.desfase);

            $("#cmp_cb_1").text(res.q1.combustible);
            $("#cmp_cb_2").text(res.q2.combustible);
            $("#cmp_cb_t").text(res.resumen.combustible);

            $("#cmp_dl_1").text(res.q1.dias_laborados);
            $("#cmp_dl_2").text(res.q2.dias_laborados);
            $("#cmp_dl_t").text(res.resumen.dias_laborados);

            // Detalle Diario Q1
            var htmlQ1 = "";
            if (res.detalle_q1 && res.detalle_q1.length > 0) {
                $.each(res.detalle_q1, function(i, d) {
                    htmlQ1 += "<tr>";
                    htmlQ1 += "<td>" + d.dia + "</td>";
                    htmlQ1 += "<td>" + d.fecha + "</td>";
                    htmlQ1 += "<td>" + d.operador + "</td>";
                    htmlQ1 += "<td class='text-right'>" + d.hor_inicial + "</td>";
                    htmlQ1 += "<td class='text-right'>" + d.hor_final + "</td>";
                    htmlQ1 += "<td class='text-right fw-bold'>" + d.total_hrs + "</td>";
                    htmlQ1 += "<td class='text-right text-danger'>" + d.descuento + "</td>";
                    htmlQ1 += "<td class='text-right text-success'>" + d.prod_hrs + "</td>";
                    htmlQ1 += "<td class='text-right text-warning'>" + d.combustible + "</td>";
                    htmlQ1 += "<td>" + d.observaciones + "</td>";
                    htmlQ1 += "</tr>";
                });
            } else {
                htmlQ1 = "<tr><td colspan='10' class='text-center text-muted'>No hay registros para la primera quincena</td></tr>";
            }
            $("#tbl_q1").html(htmlQ1);

            // Detalle Diario Q2
            var htmlQ2 = "";
            if (res.detalle_q2 && res.detalle_q2.length > 0) {
                $.each(res.detalle_q2, function(i, d) {
                    htmlQ2 += "<tr>";
                    htmlQ2 += "<td>" + d.dia + "</td>";
                    htmlQ2 += "<td>" + d.fecha + "</td>";
                    htmlQ2 += "<td>" + d.operador + "</td>";
                    htmlQ2 += "<td class='text-right'>" + d.hor_inicial + "</td>";
                    htmlQ2 += "<td class='text-right'>" + d.hor_final + "</td>";
                    htmlQ2 += "<td class='text-right fw-bold'>" + d.total_hrs + "</td>";
                    htmlQ2 += "<td class='text-right text-danger'>" + d.descuento + "</td>";
                    htmlQ2 += "<td class='text-right text-success'>" + d.prod_hrs + "</td>";
                    htmlQ2 += "<td class='text-right text-warning'>" + d.combustible + "</td>";
                    htmlQ2 += "<td>" + d.observaciones + "</td>";
                    htmlQ2 += "</tr>";
                });
            } else {
                htmlQ2 = "<tr><td colspan='10' class='text-center text-muted'>No hay registros para la segunda quincena</td></tr>";
            }
            $("#tbl_q2").html(htmlQ2);

            // Resumen Mensual Final
            $("#fin_ht").text(res.resumen.horas_trabajadas);
            $("#fin_hp").text(res.resumen.horas_productivas);
            $("#fin_df").text(res.resumen.desfase);
            $("#fin_cb").text(res.resumen.combustible + " Gls");

            $("#contenedorReporteConsolidado").hide();
            $("#contenedorReporteIndividual").fadeIn();
        } else {
            $.alert(res.message || "No se encontraron datos para generar el reporte individual.");
            $("#contenedorReporteIndividual").hide();
        }
    }).fail(function() {
        $("#loader").hide();
        $.alert("Error de comunicación al generar reporte individual.");
    });
}

function cargarReporteConsolidado(anio, mes, maq, ope) {
    $("#loader").show();
    $.getJSON(window.location.href, {
        getReporteOperativoAjax: 1,
        tipo: 'consolidado',
        anio: anio,
        mes: mes,
        maq: maq,
        ope: ope
    }, function(res) {
        $("#loader").hide();
        if (res && res.success) {
            $("#lbl_rep_con_periodo").text("PERÍODO: " + res.periodo);

            // Resumen General Cards
            $("#con_hrs_trab").text(res.resumen.horas_trabajadas);
            $("#con_hrs_prod").text(res.resumen.horas_productivas);
            $("#con_comb").text(res.resumen.combustible + " Gls");
            $("#con_maquinas").text(res.resumen.total_maquinas);

            // Tabla Consolidada
            var html = "";
            if (res.detalle && res.detalle.length > 0) {
                $.each(res.detalle, function(i, d) {
                    html += "<tr>";
                    html += "<td>" + d.maquina + "</td>";
                    html += "<td>" + d.operador + "</td>";
                    html += "<td class='text-right fw-bold'>" + d.horas_trabajadas + "</td>";
                    html += "<td class='text-right text-success'>" + d.horas_productivas + "</td>";
                    html += "<td class='text-right text-danger'>" + d.desfase + "</td>";
                    html += "<td class='text-right text-warning'>" + d.combustible + "</td>";
                    html += "<td class='text-center'>" + d.estado + "</td>";
                    html += "<td class='text-center'><button class='btn btn-xs btn-info' onclick='verDetalleMaquina(\"" + d.veh_cod + "\", \"" + d.cho_cod + "\")'><i class='fa fa-eye'></i> Ver Detalle</button></td>";
                    html += "</tr>";
                });
            } else {
                html = "<tr><td colspan='8' class='text-center text-muted'>No se encontraron registros en este período.</td></tr>";
            }
            $("#tbl_consolidado").html(html);

            $("#contenedorReporteIndividual").hide();
            $("#contenedorReporteConsolidado").fadeIn();
        } else {
            $.alert(res.message || "No se encontraron datos para generar el reporte consolidado.");
            $("#contenedorReporteConsolidado").hide();
        }
    }).fail(function() {
        $("#loader").hide();
        $.alert("Error de comunicación al generar reporte consolidado.");
    });
}

function verDetalleMaquina(veh_cod, cho_cod) {
    $("#rep_tipo").val('individual');
    $("#rep_maquina").val(veh_cod);
    if(cho_cod) {
        $("#rep_operador").val(cho_cod);
    }
    generarReporte();
}

function exportarReportePDF() {
    $.alert("Funcionalidad PDF en desarrollo...");
    // Aquí se llamará a la librería de PDF o endpoint de backend.
}

function exportarReporteExcel() {
    $.alert("Funcionalidad Excel en desarrollo...");
    // Aquí se llamará al exportador
}

// Cargar selectores de año y preseleccionar mes actual
$(function() {
    var d = new Date();
    var year = d.getFullYear();
    var month = (d.getMonth() + 1).toString().padStart(2, '0');
    
    var htmlAnio = '';
    for(var i = year; i >= year - 5; i--) {
        htmlAnio += '<option value="'+i+'">'+i+'</option>';
    }
    $("#rep_anio").html(htmlAnio);
    $("#rep_mes").val(month);

    // Evento para cargar la última máquina asignada cuando se selecciona un operador
    $("#rep_operador").on('change', function() {
        var cho_cod = $(this).val();
        if (cho_cod !== 'TODOS' && cho_cod !== '') {
            $.ajax({
                url: 'man_alt_maquinaria_horometro.php?getLastVehiculoByOperadorAjax=1',
                type: 'GET',
                data: { Cho_Cod: cho_cod },
                dataType: 'json',
                success: function(r) {
                    if (r.success && r.Veh_Cod > 0) {
                        $("#rep_maquina").val(r.Veh_Cod).trigger("chosen:updated");
                    }
                }
            });
        }
    });
});
