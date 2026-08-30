/**
 * Validaciones y funciones JavaScript para Estado de Cuenta
 * @author Exa-Contable
 * @version 1.0
 */

var gridEstadoCuenta = $("#gridEstadoCuenta");
var gridPlantas = $("#gridPlantas");
window._manEstPlantaTarget = 'ind';

$(document).ready(function() {
    // Inicializar datepickers
    $("#Fec_IniM, #Fec_FinM").datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        yearRange: "-10:+1"
    });
    $("#Fec_IniM_Cons, #Fec_FinM_Cons").datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        yearRange: "-10:+1"
    });

    // Establecer fecha por defecto: Inicio de año y hoy
    var today = new Date();
    var firstDayOfYear = new Date(today.getFullYear(), 0, 1);
    
    $("#Fec_IniM").val($.datepicker.formatDate('yy-mm-dd', firstDayOfYear));
    $("#Fec_FinM").val($.datepicker.formatDate('yy-mm-dd', today));
    $("#Fec_IniM_Cons").val($.datepicker.formatDate('yy-mm-dd', firstDayOfYear));
    $("#Fec_FinM_Cons").val($.datepicker.formatDate('yy-mm-dd', today));

    // Inicializar Grid Principal
    gridEstadoCuenta.createGrid({
        caption: 'Datos del estado',
        url: window.location.pathname,
        postData: {
            loadEstadoCuentaAjax: true,
            Fec_IniM: function() { return $("#Fec_IniM").val(); },
            Fec_FinM: function() { return $("#Fec_FinM").val(); },
            Mes_Cod: function() { return $("#Mes_Cod").val(); },
            Pla_Cod: function() { return $("#Pla_Cod").val(); }
        },
        colModel: [
            { label: 'Cod.Int', name: 'Ama_Cod', width: 30, align: 'center' },
            { label: 'No. Compr.', name: 'codigoAnti', width: 50, align: "center" },
            { label: 'Fecha', name: 'Ama_Fec', width: 65, align: 'center' },
            { label: 'Responsable', name: 'Responsable', width: 150 },
            { label: 'Cli_Cod', name: 'Cli_Cod', hidden: true }, // Campo oculto para detalle
            { label: 'Cliente', name: 'Cliente', width: 150 },
            { label: 'Forma Pago', name: 'Pag_Des', width: 100, align: 'center' },
            { label: 'Cuenta Acr.', name: 'Ban_Cue', width: 100, align: 'center' },
            { label: 'N° de Trfs.', name: 'Ama_Doc', width: 100, align: 'center' },
            { label: 'Ingreso', name: 'Ama_Val', width: 80, align: 'center', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalPlaces: 2 }, cellattr: function() { return 'style="color: #28a745; font-weight: bold;"'; } },
            { label: 'Egresos', name: 'Abono', width: 80, align: 'center', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalPlaces: 2 } },
            { label: 'Saldo', name: 'Saldo', width: 80, align: 'center', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalPlaces: 2 } }
        ],
        rowNum: 50,
        height: 400,
        footerrow: true,
        userDataOnFooter: false,
        loadComplete: function() {
            var ids = gridEstadoCuenta.jqGrid('getDataIDs');
            var totalVal = 0, totalAbono = 0, totalSaldo = 0;
            
            for (var i = 0; i < ids.length; i++) {
                var r = gridEstadoCuenta.jqGrid('getRowData', ids[i]);
                var val = parseFloat(String(r.Ama_Val).replace(/[^\d.-]/g, '')) || 0;
                var abono = parseFloat(String(r.Abono).replace(/[^\d.-]/g, '')) || 0;
                var saldo = parseFloat(String(r.Saldo).replace(/[^\d.-]/g, '')) || (val - abono);

                totalVal += val;
                totalAbono += abono;
                totalSaldo += saldo;
            }
            
            gridEstadoCuenta.jqGrid('footerData', 'set', {
                Ama_Doc: 'TOTALES:',
                Ama_Val: totalVal,
                Abono: totalAbono,
                Saldo: totalSaldo
            });
        },
        onSelectRow: function(rowid) {
            // No action on select row
        },
        subGrid: true,
        subGridRowExpanded: function(subgrid_id, row_id) {
            var subgrid_table_id, pager_id;
            subgrid_table_id = subgrid_id + "_t";
            pager_id = "p_" + subgrid_table_id;
            
            // Obtener datos de la fila padre
            var rowData = gridEstadoCuenta.jqGrid('getRowData', row_id);
            var amaCod = rowData.Ama_Cod;

            // Header para el subgrid
            $("#" + subgrid_id).html(
                '<div style="padding: 10px; background-color: #f9f9f9; border-bottom: 1px solid #ddd;">' +
                '<h4 style="margin: 0; color: #254463; font-size: 14px; font-weight: bold;">Detalle del movimiento</h4>' +
                '</div>' + 
                '<table id="' + subgrid_table_id + '" class="scroll"></table>'
            );

            // Inicializar subgrid
            $("#" + subgrid_table_id).jqGrid({
                url: window.location.pathname,
                datatype: "json",
                mtype: "POST",
                postData: {
                    loadDetalleMovimientoAjax: true,
                    Ama_Cod: amaCod
                },
                colModel: [
                    { label: 'No. Compr.', name: 'No_Compr', width: 100, align: 'center' },
                    { label: 'Fecha Compr.', name: 'Fec_Compr', width: 80, align: 'center' },
                    { label: 'Fecha Ant.', name: 'Fec_Ant', width: 80, align: 'center' },
                    { label: 'Observación', name: 'Observacion', width: 200 },
                    { label: 'Concepto', name: 'Concepto', width: 200 },
                    { label: 'Valor', name: 'Valor', width: 80, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalPlaces: 2 } }
                ],
                rowNum: 1000,
                height: '100%',
                width: '100%',
                autowidth: true,
                viewrecords: false,
                jsonReader: {
                    root: "rows",
                    page: "page",
                    total: "total",
                    records: "records",
                    repeatitems: false,
                    id: "0"
                }
            });
        }
    }, true, 'pagerEstadoCuenta', { view: false, refresh: true }).gridButtonsAdd([
        { caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download',
            onClickButton: function () {
                gridEstadoCuenta.jqGrid('exportGridExcel', {
                    nombre: 'Estado_Cuenta',
                    hoja: 'HOJA 1',
                    footer: true
                });
            }
        }
    ]);

    // Inicializar Dialogo Planta
    $("#plantaDialog").dialog({
        modal: true,
        width: 850,
        height: 435,
        resizable: true,
        autoOpen: false,
        position: { my: "center", at: "center", of: window },
        open: function() {
            // Ajustar ancho inmediatamente al abrir
            var width = $(this).width();
            $("#gridPlantas").jqGrid('setGridWidth', width - 20);
        }
    });

    // Grid Busqueda Plantas
    gridPlantas.createGrid({
        caption: 'Datos de Plantas',
        url: '' /*window.location.pathname*/,
        postData: {
            loadPlantasAjax: true,
            search: function() { return $("#searchPlantaInput").val(); }
        },
        colModel: [
            { label: 'Cod. ', name: 'Pla_Cod', key: true, width: 60, align: 'center' },
            { label: 'Planta', name: 'Pla_Nom', width: 250 },
            { label: 'Ciudad', name: 'Ciu_Des', width: 120, align: 'center' },
            { label: 'Cliente', name: 'Cliente', width: 200 },
            { label: 'Cli_Cod', name: 'Cli_Cod', hidden: true },
            { label: '', name: 'Accion', width: 50, align: 'center', sortable: false,
                formatter: function(cellvalue, options, rowObject) {
                    var safeId = String(options.rowId).replace(/"/g, '&quot;').replace(/'/g, "\\'");
                    return '<button type="button" class="btn btn-success btn-xs" title="Seleccionar" onclick="seleccionarPlantaBtn(\'' + safeId + '\')"><span class="glyphicon glyphicon-arrow-right"></span></button>';
                }
            }
        ],
        rowNum: 20,
        rowList: [10, 20, 50, 100],
        viewrecords: true,
        pgbuttons: true,
        pginput: true,
        loadonce: true,
        height: 250
    }, true, 'pagerPlantas', { view: false, refresh: true });

    // Permitir búsqueda con Enter en principal
    $("#searchEstCuenta").on("keypress", function(e) {
        if (e.which === 13) {
            e.preventDefault();
            buscarEstadoCuenta();
        }
    });

    // Planta search listeners
    $("#btnBuscarPlanta").click(function() {
        window._manEstPlantaTarget = 'ind';
        $("#plantaDialog").dialog("open");
        gridPlantas.trigger("reloadGrid");
    });

    $("#btnLimpiarPlanta").click(function() {
        $("#Pla_Cod").val("");
        $("#Pla_Nom").val("");
        gridEstadoCuenta.trigger("reloadGrid");
    });

    $("#btnBuscarPlantaCons").click(function() {
        window._manEstPlantaTarget = 'cons';
        $("#plantaDialog").dialog("open");
        gridPlantas.trigger("reloadGrid");
    });

    $("#btnLimpiarPlantaCons").click(function() {
        $("#Pla_Cod_Cons").val("");
        $("#Pla_Nom_Cons").val("");
        $("#Cli_Cod_Cons").val("");
        $("#Cli_Nom_Cons").val("");
        $("#detalle_consolidado_container").empty();
    });

    // Enter en búsqueda plantas
    $("#searchPlantaInput").on("keypress", function(e) {
        if (e.which === 13) {
            e.preventDefault();
            buscarPlantas();
        }
    });

    // Acción botón Buscar principal (redundancia por seguridad)
    $("#btnBuscar").click(function() { buscarEstadoCuenta(); });
    $("#btnBuscarConsolidado").click(function() { buscarEstadoCuentaConsolidado(); });
});

function buscarEstadoCuenta() {
    var cliCod = $("#Cli_Cod").val();
    
    // Si hay un cliente seleccionado, cargar el detalle HTML
    if (cliCod) {
        loadDetalleCliente(cliCod);
    } else {
        // Si no hay cliente (solo filtros generales), quizás mostrar alerta o nada
        // Pero el grid está oculto, así que no se verá nada si recargamos el grid
        if ($("#Pla_Cod").val() === "") {
            // Si no hay planta/cliente, limpiar
            $("#detalle_container").empty();
            return;
        }
        // Si hay planta pero no Cli_Cod (caso raro si seleccionó del grid), intentar recargar grid?
        // Pero el usuario quiere el HTML View.
        // Asumimos que seleccionarPlanta siempre setea Cli_Cod.
    }
}

function buscarPlantas() {
    var searchVal = $("#searchPlantaInput").val();
    gridPlantas.jqGrid('setGridParam', {
        postData: { search: searchVal },
        datatype: 'json',
        mtype: 'POST',
        page: 1
    }).trigger("reloadGrid");
}

function seleccionarPlantaBtn(rowid) {
    var row = gridPlantas.jqGrid('getRowData', rowid);
    seleccionarPlanta(row);
}

function seleccionarPlanta(row) {
    var t = window._manEstPlantaTarget || 'ind';
    if (t === 'cons') {
        $("#Pla_Cod_Cons").val(row.Pla_Cod);
        $("#Pla_Nom_Cons").val(row.Pla_Nom);
        $("#Cli_Cod_Cons").val(row.Cli_Cod);
        $("#Cli_Nom_Cons").val(row.Cliente);
    } else {
        $("#Pla_Cod").val(row.Pla_Cod);
        $("#Pla_Nom").val(row.Pla_Nom);
        $("#Cli_Cod").val(row.Cli_Cod);
        $("#Cli_Nom").val(row.Cliente);
    }
    $("#plantaDialog").dialog("close");
    // gridEstadoCuenta.trigger("reloadGrid"); // OLD
    // buscarEstadoCuenta(); // Call search which will load detail - REMOVED per user request
}

/* Cambiar período y habilitar/deshabilitar campos de fecha */
function cambiarPeriodo() {
    var pec_cod = $("#Pec_Cod").val();
    var mes_cod = $("#Mes_Cod").val();
    
    if (pec_cod === 'PF') {
        // Por Fechas - habilitar campos de fecha, deshabilitar mes
        $("#Fec_IniM, #Fec_FinM").prop('disabled', false);
        $("#Mes_Cod").prop('disabled', true);
        $("#Mes_Cod").val('00'); // Resetear mes a TODOS para evitar conflicto en filtros
        
        // Solo establecemos valores por defecto si están vacíos
        if ($("#Fec_IniM").val() === '') {
            var today = new Date();
            var firstDayOfYear = new Date(today.getFullYear(), 0, 1);
            $("#Fec_IniM").val($.datepicker.formatDate('yy-mm-dd', firstDayOfYear));
            $("#Fec_FinM").val($.datepicker.formatDate('yy-mm-dd', today));
        }
    } else if (pec_cod === 'T') {
        // TODOS - deshabilitar fechas y mes (o habilitar mes? Asumiremos deshabilitar mes para simplificar lógica de "Todos los años")
        $("#Fec_IniM, #Fec_FinM").prop('disabled', true);
        $("#Mes_Cod").prop('disabled', true); // Deshabilitamos mes en "Todos" los periodos
        $("#Mes_Cod").val('00');
        
        // Obtener rango total
        var minDate = '';
        var maxDate = $.datepicker.formatDate('yy-mm-dd', new Date()); // Fecha actual
        
        $("#Pec_Cod option").each(function() {
            var start = $(this).data('inicio');
            if (start && (!minDate || start < minDate)) {
                minDate = start;
            }
        });
        
        if (minDate) {
            var minYear = minDate.substring(0, 4);
            $("#Fec_IniM").val(minYear + '-01-01');
        } else {
            $("#Fec_IniM").val('2020-01-01'); 
        }
        $("#Fec_FinM").val(maxDate);
        
    } else {
        // Período específico (Año)
        $("#Fec_IniM, #Fec_FinM").prop('disabled', true);
        $("#Mes_Cod").prop('disabled', false); // Habilitar select de mes
        
        var selectedOption = $("#Pec_Cod option:selected");
        var fecIniAnio = selectedOption.data('inicio'); // Ej: 2026-01-01
        
        // Obtener el año del periodo seleccionado
        var year = fecIniAnio.substring(0, 4);
        
        if (mes_cod === '00') {
            // Si es "TODOS" los meses, seleccionamos todo el año
            $("#Fec_IniM").val(year + '-01-01');
            $("#Fec_FinM").val(year + '-12-31');
        } else {
            // Si es un mes específico
            // Calcular primer y último día del mes seleccionado
            var mesIndex = parseInt(mes_cod, 10) - 1; // 0-based index
            var firstDay = new Date(year, mesIndex, 1);
            var lastDay = new Date(year, mesIndex + 1, 0); // Día 0 del siguiente mes es el último del actual
            
            $("#Fec_IniM").val($.datepicker.formatDate('yy-mm-dd', firstDay));
            $("#Fec_FinM").val($.datepicker.formatDate('yy-mm-dd', lastDay));
        }
    }
}

/* Intercambiar valores de las fechas */
function intercambiarFechas() {
    var fecIni = $("#Fec_IniM").val();
    var fecFin = $("#Fec_FinM").val();
    $("#Fec_IniM").val(fecFin);
    $("#Fec_FinM").val(fecIni);
}

/* Período / fechas — pestaña Consolidado */
function cambiarPeriodoCons() {
    if (!$("#Pec_Cod_Cons").length) {
        return;
    }
    var pec_cod = $("#Pec_Cod_Cons").val();
    var mes_cod = $("#Mes_Cod_Cons").val();

    if (pec_cod === 'PF') {
        $("#Fec_IniM_Cons, #Fec_FinM_Cons").prop('disabled', false);
        $("#Mes_Cod_Cons").prop('disabled', true);
        $("#Mes_Cod_Cons").val('00');
        if ($("#Fec_IniM_Cons").val() === '') {
            var today = new Date();
            var firstDayOfYear = new Date(today.getFullYear(), 0, 1);
            $("#Fec_IniM_Cons").val($.datepicker.formatDate('yy-mm-dd', firstDayOfYear));
            $("#Fec_FinM_Cons").val($.datepicker.formatDate('yy-mm-dd', today));
        }
    } else if (pec_cod === 'T') {
        $("#Fec_IniM_Cons, #Fec_FinM_Cons").prop('disabled', true);
        $("#Mes_Cod_Cons").prop('disabled', true);
        $("#Mes_Cod_Cons").val('00');
        var minDate = '';
        var maxDate = $.datepicker.formatDate('yy-mm-dd', new Date());
        $("#Pec_Cod_Cons option").each(function() {
            var start = $(this).data('inicio');
            if (start && (!minDate || start < minDate)) {
                minDate = start;
            }
        });
        if (minDate) {
            var minYear = minDate.substring(0, 4);
            $("#Fec_IniM_Cons").val(minYear + '-01-01');
        } else {
            $("#Fec_IniM_Cons").val('2020-01-01');
        }
        $("#Fec_FinM_Cons").val(maxDate);
    } else {
        $("#Fec_IniM_Cons, #Fec_FinM_Cons").prop('disabled', true);
        $("#Mes_Cod_Cons").prop('disabled', false);
        var selectedOption = $("#Pec_Cod_Cons option:selected");
        var fecIniAnio = selectedOption.data('inicio');
        var year = fecIniAnio.substring(0, 4);
        if (mes_cod === '00') {
            $("#Fec_IniM_Cons").val(year + '-01-01');
            $("#Fec_FinM_Cons").val(year + '-12-31');
        } else {
            var mesIndex = parseInt(mes_cod, 10) - 1;
            var firstDay = new Date(year, mesIndex, 1);
            var lastDay = new Date(year, mesIndex + 1, 0);
            $("#Fec_IniM_Cons").val($.datepicker.formatDate('yy-mm-dd', firstDay));
            $("#Fec_FinM_Cons").val($.datepicker.formatDate('yy-mm-dd', lastDay));
        }
    }
}

function intercambiarFechasCons() {
    var fecIni = $("#Fec_IniM_Cons").val();
    var fecFin = $("#Fec_FinM_Cons").val();
    $("#Fec_IniM_Cons").val(fecFin);
    $("#Fec_FinM_Cons").val(fecIni);
}

function mapMatEarDesc(v) {
    var m = { TR: 'Transporte', AT: 'Almacenamiento Temporal', EL: 'Eliminación', DF: 'Disposición Final', CT: 'Cierre Técnico' };
    return m[v] || v || '';
}

function mapManTipTecLabel(tip) {
    var u = (tip || '').toString().toUpperCase();
    if (u === 'F') return 'Facturado';
    if (u === 'GS') return 'Salida (técnico)';
    if (u === 'GE') return 'Entrada (garita)';
    if (u === 'P') return 'Pendiente';
    return tip || '';
}

function formatManTecCodigoRow(row) {
    var pla = row.Pla_Cod != null ? String(row.Pla_Cod) : '';
    var mn = row.Man_Num != null ? parseInt(row.Man_Num, 10) : (row.Man_Cod != null ? parseInt(row.Man_Cod, 10) : 0);
    if (isNaN(mn)) mn = 0;
    var s = String(mn);
    while (s.length < 4) s = '0' + s;
    return 'M' + pla + '-' + s;
}

function buscarEstadoCuentaConsolidado() {
    var cliCod = $("#Cli_Cod_Cons").val();
    if (!cliCod) {
        alert('Seleccione una planta con cliente asociado.');
        return;
    }
    var fi = $("#Fec_IniM_Cons").val();
    var ff = $("#Fec_FinM_Cons").val();
    var filtro = $("input[name='op_opciones']:checked").val() || '';
    var search = ($("#searchEstCuenta").val() || '').trim();
    if (!fi || !ff) {
        alert('Defina el rango de fechas del período.');
        return;
    }
    var pla = $("#Pla_Cod_Cons").val() || '';
    $.ajax({
        url: window.location.pathname,
        type: 'GET',
        data: {
            loadConsolidadoTecAjax: true,
            Cli_Cod: cliCod,
            Pla_Cod: pla,
            Fec_IniM: fi,
            Fec_FinM: ff,
            filtro: filtro,
            search: search
        },
        dataType: 'json',
        beforeSend: function() {
            $("#detalle_consolidado_container").html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Cargando manifiestos técnicos...</p></div>').show();
        },
        success: function(response) {
            if (response.success) {
                renderConsolidadoTecHTML(response.rows || [], response);
            } else {
                $("#detalle_consolidado_container").html('<div class="alert alert-warning">' + (response.message || 'No se pudo cargar el consolidado.') + '</div>');
            }
        },
        error: function() {
            $("#detalle_consolidado_container").html('<div class="alert alert-danger">Error al cargar el consolidado.</div>');
        }
    });
}

function limpiarFiltrosConsolidado() {
    $("#searchEstCuenta").val('');
    $("#Pec_Cod_Cons").val('T');
    $("#Pla_Cod_Cons").val("");
    $("#Pla_Nom_Cons").val("");
    $("#Cli_Cod_Cons").val("");
    $("#Cli_Nom_Cons").val("");
    $("#Fec_IniM_Cons, #Fec_FinM_Cons").prop('disabled', true).val('');
    $("#detalle_consolidado_container").empty();
    cambiarPeriodoCons();
}

/** Fecha yyyy-mm-dd → dd/mm/yyyy (como en Excel del estado de cuenta) */
function fecIsoADMY(fecIso) {
    if (!fecIso || String(fecIso).length < 10) return '';
    var p = String(fecIso).substring(0, 10).split('-');
    if (p.length !== 3) return '';
    return p[2] + '/' + p[1] + '/' + p[0];
}

/** Texto "SALDO AL dd, de Mes, yyyy" para el día anterior al inicio del período */
function etiquetaSaldoInicialDesde(fecIniIso) {
    if (!fecIniIso || String(fecIniIso).length < 10) return 'SALDO INICIAL';
    var p = String(fecIniIso).substring(0, 10).split('-');
    var d = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
    d.setDate(d.getDate() - 1);
    var meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    return 'SALDO AL ' + d.getDate() + ', de ' + meses[d.getMonth()] + ', ' + d.getFullYear();
}

/* Consolidado: formato Detalle/Concepto como estado de cuenta (igual referencia Excel) */
function renderConsolidadoTecHTML(rows, response) {
    var cliente = response.cliente || $("#Cli_Nom_Cons").val() || '';
    var ruc = response.cliente_ruc || '';
    var fecIni = $("#Fec_IniM_Cons").val();
    var fecFin = $("#Fec_FinM_Cons").val();
    var empresaEmisora = $("#Ses_Emp_Nom").val() || 'RELA VERA S.A.';
    var pendMonto = parseFloat(response.manif_pend_monto) || 0;
    var pendCnt = parseInt(response.manif_pend_cnt, 10) || 0;
    var ultFecFact = (response.ult_fec_fact || '').toString().substring(0, 10);
    var ultFecManGen = (response.ult_fec_man_gen || '').toString().substring(0, 10);
    var saldoInicial = response.saldo_inicial != null ? parseFloat(response.saldo_inicial) : 0;
    if (isNaN(saldoInicial)) saldoInicial = 0;
    var movimientos = $.isArray(response.detalle_ec) ? response.detalle_ec : [];

    function sumarDiaIso(fecIso) {
        if (!fecIso || fecIso.length < 10) return '';
        var p = fecIso.substring(0, 10).split('-');
        if (p.length !== 3) return '';
        var d = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
        d.setDate(d.getDate() + 1);
        var mm = String(d.getMonth() + 1);
        var dd = String(d.getDate());
        if (mm.length < 2) mm = '0' + mm;
        if (dd.length < 2) dd = '0' + dd;
        return d.getFullYear() + '-' + mm + '-' + dd;
    }

    function formatearNumeroFactura(v) {
        var s = (v || '').toString().trim();
        if (!s || s.toUpperCase() === 'S/N') return 'S/N';
        if (/^\d{3}-\d{3}-\d{8}$/.test(s)) return s;
        var solo = s.replace(/\D/g, '');
        if (!solo) return s;
        while (solo.length < 14) solo = '0' + solo;
        if (solo.length > 14) solo = solo.substring(solo.length - 14);
        return solo.substring(0, 3) + '-' + solo.substring(3, 6) + '-' + solo.substring(6);
    }

    var html = '';
    html += '<div class="text-right" style="margin-bottom: 10px;">';
    html += '<button type="button" class="btn btn-success btn-sm" onclick="exportarExcelConsolidadoTec()" title="Exportar a Excel"><i class="glyphicon glyphicon-download-alt"></i> Excel</button> ';
    html += '<button type="button" class="btn btn-primary btn-sm" onclick="imprimirConsolidadoTec()" title="Imprimir / PDF"><i class="glyphicon glyphicon-print"></i> PDF / Imprimir</button>';
    html += '</div>';
    html += '<div id="reporte-content-consolidado" style="max-width: 95%; margin: 0 auto;">';
    html += '<div class="panel panel-default" style="border: 2px solid #ccc; border-radius: 10px; overflow: hidden;">';
    html += '<table id="header_exportacion_cons" style="width: 100%; display: none; margin-bottom: 20px; border: none;">';
    html += '<tr><td colspan="7" align="center" style="text-align: center; border: none;">';
    html += '<h3 style="margin: 0; font-weight: bold; color: #000; text-transform: uppercase; font-size: 18px;">' + $('<div/>').text(empresaEmisora).html() + '</h3>';
    html += '<h4 style="margin: 5px 0; font-weight: bold; color: #333; letter-spacing: 2px; font-size: 16px;">ESTADO DE CUENTA</h4>';
    html += '<div style="font-size: 14px; color: #000;">Desde: <b>' + fecIni + '</b> &nbsp;|&nbsp; Hasta: <b>' + fecFin + '</b></div>';
    html += '</td></tr></table>';
    html += '<div class="panel-heading" style="background-color: #f8f9fa; border-top: 2px solid #333; border-bottom: 2px solid #333; padding: 10px 15px;">';
    html += '<h4 style="margin: 0; font-weight: bold; font-size: 15px; color: #333; text-transform: uppercase;">' + $('<div/>').text(cliente).html() + '</h4>';
    html += '<h5 style="margin: 2px 0 0 0; font-weight: bold; font-size: 14px; color: #333;">RUC: ' + $('<div/>').text(ruc).html() + '</h5>';
    html += '</div>';
    html += '<div class="table-responsive" style="margin-top: 10px;">';
    html += '<table class="table table-striped table-bordered table-condensed" border="1" style="margin-bottom: 0; border-collapse: collapse; width: 100%; border: 1px solid #ddd; font-size: 14px;">';
    html += '<thead><tr style="background-color: #34495e;">';
    html += '<th class="text-center" style="background-color: #34495e; border: 1px solid #ddd; padding: 6px; font-weight: bold; color: #fff;">Nº</th>';
    html += '<th class="text-center" style="background-color: #34495e; border: 1px solid #ddd; padding: 6px; font-weight: bold; color: #fff;">No. Compr.</th>';
    html += '<th class="text-center" style="background-color: #34495e; border: 1px solid #ddd; padding: 6px; font-weight: bold; color: #fff;">Fecha</th>';
    html += '<th class="text-center" style="background-color: #34495e; border: 1px solid #ddd; padding: 6px; font-weight: bold; color: #fff;">Detalle/Concepto</th>';
    html += '<th style="text-align: right; background-color: #34495e; border: 1px solid #ddd; padding: 6px; font-weight: bold; color: #fff;">Ingreso</th>';
    html += '<th style="text-align: right; background-color: #34495e; border: 1px solid #ddd; padding: 6px; font-weight: bold; color: #fff;">Egresos</th>';
    html += '<th style="text-align: right; background-color: #34495e; border: 1px solid #ddd; padding: 6px; font-weight: bold; color: #fff;">Saldo</th>';
    html += '</tr></thead><tbody>';

    var saldoAcumulado = saldoInicial;
    var contador = 1;
    var totalIngresos = 0;
    var totalEgresos = 0;

    html += '<tr>';
    html += '<td class="text-center" style="border: 1px solid #ccc; padding: 5px;">' + (contador++) + '</td>';
    html += '<td class="text-center" style="border: 1px solid #ccc; padding: 5px;"></td>';
    html += '<td class="text-center" style="border: 1px solid #ccc; padding: 5px;"></td>';
    html += '<td style="border: 1px solid #ccc; padding: 5px; text-align: center; font-weight: bold;">' + $('<div/>').text(etiquetaSaldoInicialDesde(fecIni)).html() + '</td>';
    html += '<td style="text-align: right; border: 1px solid #ccc; padding: 5px;">0.00</td>';
    html += '<td style="text-align: right; border: 1px solid #ccc; padding: 5px;">0.00</td>';
    html += '<td style="text-align: right; font-weight: bold; border: 1px solid #ccc; padding: 5px;">$ ' + formatNumber(saldoAcumulado, 2) + '</td>';
    html += '</tr>';

    $.each(movimientos, function(idx, row) {
        var ingreso = parseFloat(row.Valor || 0);
        var egreso = parseFloat(row.Abono || 0);
        if (ingreso === 0 && egreso === 0) return;

        saldoAcumulado = parseFloat((saldoAcumulado + ingreso - egreso).toFixed(2));
        totalIngresos += ingreso;
        totalEgresos += egreso;

        var noCompr = row.codigoAnti || row.Documento || '';
        var detalle = '';
        if (row.FormaPago === 'Comprobante') {
            var nf = formatearNumeroFactura(row.Documento || noCompr);
            var cantViajes = parseInt(row.CantViajes, 10) || 0;
            if (nf === 'S/N') {
                detalle = 'Comprobante: S/N';
            } else {
                detalle = 'Factura ' + nf + (cantViajes > 0 ? (' (' + cantViajes + ' viaje' + (cantViajes === 1 ? '' : 's') + ')') : '');
            }
        } else {
            detalle = (row.FormaPago || 'Transferencia') + ' / Doc,Num: ' + (row.Documento || '');
        }

        html += '<tr>';
        html += '<td class="text-center" style="border: 1px solid #ccc; padding: 5px;">' + (contador++) + '</td>';
        html += '<td class="text-center" style="border: 1px solid #ccc; padding: 5px;">' + $('<div/>').text(noCompr).html() + '</td>';
        html += '<td class="text-center" style="border: 1px solid #ccc; padding: 5px;">' + $('<div/>').text(fecIsoADMY((row.Fecha || '').toString().substring(0, 10))).html() + '</td>';
        html += '<td style="border: 1px solid #ccc; padding: 5px;">' + $('<div/>').text(detalle).html() + '</td>';
        html += '<td style="text-align: right; color: ' + (ingreso > 0 ? '#28a745' : '#000') + '; font-weight: ' + (ingreso > 0 ? 'bold' : 'normal') + '; border: 1px solid #ccc; padding: 5px;">' + (ingreso > 0 ? '$ ' + formatNumber(ingreso, 2) : '0.00') + '</td>';
        html += '<td style="text-align: right; color: ' + (egreso > 0 ? '#1f5fbf' : '#000') + '; font-weight: ' + (egreso > 0 ? 'bold' : 'normal') + '; border: 1px solid #ccc; padding: 5px;">' + (egreso > 0 ? '$ ' + formatNumber(egreso, 2) : '0.00') + '</td>';
        html += '<td style="text-align: right; font-weight: bold; border: 1px solid #ccc; padding: 5px;">$ ' + formatNumber(saldoAcumulado, 2) + '</td>';
        html += '</tr>';
    });

    saldoAcumulado = parseFloat((saldoAcumulado - pendMonto).toFixed(2));
    totalEgresos += pendMonto;
    html += '<tr>';
    html += '<td class="text-center" style="border: 1px solid #ccc; padding: 5px;">' + (contador++) + '</td>';
    html += '<td class="text-center" style="border: 1px solid #ccc; padding: 5px;"></td>';
    html += '<td class="text-center" style="border: 1px solid #ccc; padding: 5px;"></td>';
    var fecDesdePend = sumarDiaIso(ultFecFact) || fecIni;
    var fecHastaPend = ultFecManGen || fecFin;
    html += '<td style="border: 1px solid #ccc; padding: 5px; color: #d60000; font-weight: bold;">POR FACTURAR (' + pendCnt + ' VIAJES) DEL ' + $('<div/>').text(fecDesdePend).html() + ' AL ' + $('<div/>').text(fecHastaPend).html() + '</td>';
    html += '<td style="text-align: right; border: 1px solid #ccc; padding: 5px;">0.00</td>';
    html += '<td style="text-align: right; color: #d60000; font-weight: bold; border: 1px solid #ccc; padding: 5px;">$ ' + formatNumber(pendMonto, 2) + '</td>';
    html += '<td style="text-align: right; color: #d60000; font-weight: bold; border: 1px solid #ccc; padding: 5px;">$ ' + formatNumber(saldoAcumulado, 2) + '</td>';
    html += '</tr>';

    html += '<tr style="font-weight: bold;">';
    html += '<td colspan="4" style="text-align: right; border: 1px solid #ccc; padding: 5px;">Totales</td>';
    html += '<td style="text-align: right; border: 1px solid #ccc; padding: 5px;">$ ' + formatNumber(totalIngresos, 2) + '</td>';
    html += '<td style="text-align: right; border: 1px solid #ccc; padding: 5px;">$ ' + formatNumber(totalEgresos, 2) + '</td>';
    html += '<td style="text-align: right; border: 1px solid #ccc; padding: 5px;">$ ' + formatNumber(saldoAcumulado, 2) + '</td>';
    html += '</tr>';

    html += '</tbody></table></div>';
    var fechaGen = new Date();
    var fechaGenStr = fechaGen.getDate() + '/' + (fechaGen.getMonth() + 1) + '/' + fechaGen.getFullYear() + ' ' + fechaGen.getHours() + ':' + (fechaGen.getMinutes() < 10 ? '0' : '') + fechaGen.getMinutes();
    html += '<table id="footer_exportacion_cons" style="width: 100%; display: none; margin-top: 10px; border: none;">';
    html += '<tr><td colspan="7" style="text-align: right; font-size: 10px; font-style: italic; color: #555; border: none;">Generado por: EXA [Software Contable] el ' + fechaGenStr + '</td></tr></table>';
    html += '</div></div>';
    $("#detalle_consolidado_container").html(html);
}

function exportarExcelConsolidadoTec() {
    var $clone = $('#reporte-content-consolidado').clone();
    if (!$clone.length) {
        alert('No hay datos para exportar.');
        return;
    }
    $clone.find('#header_exportacion_cons').css('display', 'table');
    $clone.find('#footer_exportacion_cons').css('display', 'table');
    var contenido = $clone.html();
    var cliNom = $("#Cli_Nom_Cons").val() || 'Cliente';
    var fecha = new Date();
    var fechaStr = fecha.getDate() + '-' + (fecha.getMonth() + 1) + '-' + fecha.getFullYear();
    var nombreArchivo = 'Estado_Cuenta_Consolidado_Tec_' + cliNom.replace(/[^a-zA-Z0-9]/g, '_') + '_' + fechaStr;
    var form = $('<form>', { method: 'POST', action: '../../Librerias/exportar/ficheroExcel.php', target: '_blank' });
    form.append($('<input>', { type: 'hidden', name: 'datos_a_enviar', value: contenido }));
    form.append($('<input>', { type: 'hidden', name: 'nombre', value: nombreArchivo }));
    form.append($('<input>', { type: 'hidden', name: 'hoja', value: 'Consolidado Técnico' }));
    $('body').append(form);
    form.submit();
    form.remove();
}

function imprimirConsolidadoTec() {
    var $clone = $('#reporte-content-consolidado').clone();
    if (!$clone.length) {
        alert('No hay datos para imprimir.');
        return;
    }
    $clone.find('#header_exportacion_cons').css('display', 'table');
    $clone.find('#footer_exportacion_cons').css('display', 'table');
    var contenido = $clone.html();
    var ventana = window.open('', '_blank');
    ventana.document.write('<html><head><title>Estado de Cuenta</title>');
    ventana.document.write('<style>');
    ventana.document.write('body { font-family: Arial, sans-serif; font-size: 12px; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }');
    ventana.document.write('@media print { body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; } }');
    ventana.document.write('.panel { border: 1px solid #ccc; margin-bottom: 20px; }');
    ventana.document.write('.table { width: 100%; border-collapse: collapse; margin-bottom: 0; }');
    ventana.document.write('.table th, .table td { padding: 8px; line-height: 1.42857143; vertical-align: top; border: 1px solid #ddd; }');
    ventana.document.write('.text-center { text-align: center; }');
    ventana.document.write('.text-right { text-align: right; }');
    ventana.document.write('h4, h5 { margin: 5px 0; }');
    ventana.document.write('</style></head><body>');
    ventana.document.write(contenido);
    ventana.document.write('</body></html>');
    ventana.document.close();
    ventana.focus();
    ventana.print();
}

/* Cargar detalle/balance de un cliente */
function loadDetalleCliente(cliCod) {
    if (!cliCod) {
        return;
    }

    var formData = {
        loadDetalleAjax: true,
        Cli_Cod: cliCod,
        Pla_Cod: $("#Pla_Cod").val(),
        Fec_Ini: $("#Fec_IniM").val(),
        Fec_Fin: $("#Fec_FinM").val(),
        Mes_Cod: $("#Mes_Cod").val()
    };

    $.ajax({
        url: window.location.pathname,
        type: 'GET',
        data: formData,
        dataType: 'json',
        beforeSend: function() {
            $("#detalle_container").html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Cargando detalle...</p></div>');
            $("#detalle_container").show();
        },
        success: function(response) {
            if (response.success) {
                renderDetalleHTML(response.data, response.resumen, response.cliente, response.cliente_ruc, response.cliente_cuenta);
            } else {
                $("#detalle_container").html('<div class="alert alert-warning">No se encontró información de detalle.</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error al cargar detalle:", error);
            $("#detalle_container").html('<div class="alert alert-danger">Error al cargar el detalle.</div>');
        }
    });
}

/* Renderizar el detalle en formato balance plano */
function renderDetalleHTML(data, resumen, cliente, cliente_ruc, cliente_cuenta) {
    // Preparar datos de cabecera
    // Priorizamos los datos explícitos enviados en la respuesta (cliente, cliente_ruc, cliente_cuenta)
    // Si no vienen (fallback), intentamos tomarlos del primer registro del data (legacy)
    
    var responsable = cliente || '';
    var ruc = cliente_ruc || '';
    var cuenta = cliente_cuenta || '';
    
    if (data && data.length > 0) {
        if (!responsable) responsable = data[0].Responsable || '';
        // Si no tenemos cuenta explícita, intentamos buscarla en los datos (solo si es anticipo tendrá cuenta)
        if (!cuenta) {
             // Buscar el primer registro que tenga cuenta
            var registroConCuenta = data.find(function(item) { return item.CuentaBancaria && item.CuentaBancaria !== ''; });
            if (registroConCuenta) {
                cuenta = registroConCuenta.CuentaBancaria;
            }
        }
    }

    var html = '';
    
    // Botones de exportación (Excel y PDF/Imprimir)
    html += '<div class="text-right" style="margin-bottom: 10px;">';
    html += '<button type="button" class="btn btn-success btn-sm" onclick="exportarExcelDetalle()" title="Exportar a Excel"><i class="glyphicon glyphicon-download-alt"></i> Excel</button> ';
    html += '<button type="button" class="btn btn-primary btn-sm" onclick="imprimirDetalle()" title="Imprimir / Guardar como PDF"><i class="glyphicon glyphicon-print"></i> PDF / Imprimir</button>';
    html += '</div>';

    html += '<div id="reporte-content" style="max-width: 95%; margin: 0 auto;">'; // Wrapper para exportación con ancho limitado centrado
    html += '<div class="panel panel-default" style="border: 2px solid #ccc; border-radius: 10px; overflow: hidden;">';
    
    // Obtener datos adicionales para cabecera
    var empresaEmisora = $("#Ses_Emp_Nom").val() || 'RELA VERA S.A.';
    var fecIni = $("#Fec_IniM").val();
    var fecFin = $("#Fec_FinM").val();

    // Cabecera Estilo Gerencial (Empresa Emisora, Título, Fechas)
    // OCULTO EN PANTALLA (display: none), SOLO VISIBLE EN EXPORTACIÓN (Usamos tabla para forzar centrado en Excel)
    html += '<table id="header_exportacion" style="width: 100%; display: none; margin-bottom: 20px; border: none;">';
    html += '<tr>';
    html += '<td colspan="7" align="center" style="text-align: center; border: none;">';
    html += '<center>'; // Etiqueta center para compatibilidad extra con PDF/Excel
    html += '<h3 style="margin: 0; font-weight: bold; color: #000; text-transform: uppercase; font-size: 18px; text-align: center;">' + empresaEmisora + '</h3>';
    html += '<h4 style="margin: 5px 0; font-weight: bold; color: #333; letter-spacing: 2px; font-size: 16px; text-align: center;">ESTADO DE CUENTA</h4>';
    html += '<div style="font-size: 14px; color: #000; text-align: center;">Desde: <b>' + fecIni + '</b> &nbsp;|&nbsp; Hasta: <b>' + fecFin + '</b></div>';
    html += '</center>';
    html += '</td>';
    html += '</tr>';
    html += '</table>';

    // Información del Cliente (Responsable, RUC, Cuenta)
    html += '<div class="panel-heading" style="background-color: #f8f9fa; border-top: 2px solid #333; border-bottom: 2px solid #333; padding: 10px 15px;">';
    html += '<table style="width: 100%; background-color: transparent; border: none;">';
    html += '<tr>';
    html += '<td style="text-align: left; border: none; vertical-align: bottom;">';
    html += '<h4 style="margin: 0; font-weight: bold; font-size: 15px; color: #333; text-transform: uppercase;">' + responsable + '</h4>';
    html += '<h5 style="margin: 2px 0 0 0; font-weight: bold; font-size: 14px; color: #333;">RUC: ' + ruc + '</h5>';
    html += '</td>';
    html += '<td style="text-align: right; border: none; vertical-align: bottom;">';
    html += '</td>';
    html += '</tr>';
    html += '</table>';
    html += '</div>'; // Fin panel-heading

    // Tabla de movimientos plana
    html += '<div class="table-responsive" style="margin-top: 10px;">';
    html += '<table class="table table-striped table-bordered table-condensed" border="1" style="margin-bottom: 0; border-collapse: collapse; width: 100%; border: 1px solid #ddd; font-size: 14px;">';
    html += '<thead>';
    html += '<tr style="background-color: #34495e;">'; // Fondo oscuro para el encabezado
    html += '<th class="text-center" style="background-color: #34495e; border: 1px solid #ddd; padding: 6px; font-weight: bold; color: #ffffff;">Nº</th>'; 
    html += '<th class="text-center" style="background-color: #34495e; border: 1px solid #ddd; padding: 6px; font-weight: bold; color: #ffffff;">No. Compr.</th>'; 
    html += '<th class="text-center" style="background-color: #34495e; border: 1px solid #ddd; padding: 6px; font-weight: bold; color: #ffffff;">Fecha</th>'; 
    html += '<th class="text-center" style="background-color: #34495e; border: 1px solid #ddd; padding: 6px; font-weight: bold; color: #ffffff;">Detalle/Concepto</th>'; 
    html += '<th style="text-align: right; background-color: #34495e; border: 1px solid #ddd; padding: 6px; font-weight: bold; color: #ffffff;">Ingreso</th>'; 
    html += '<th style="text-align: right; background-color: #34495e; border: 1px solid #ddd; padding: 6px; font-weight: bold; color: #ffffff;">Egresos</th>'; 
    html += '<th style="text-align: right; background-color: #34495e; border: 1px solid #ddd; padding: 6px; font-weight: bold; color: #ffffff;">Saldo</th>'; 
    html += '</tr>';
    html += '</thead>';
    html += '<tbody>';

    if (data && data.length > 0) {
        var saldoAcumulado = 0;
        var contador = 1;
        var totalIngresosPeriodo = 0;
        var totalEgresosPeriodo = 0;

        $.each(data, function(index, row) {
            var ingreso = parseFloat(row.Valor || 0);
            var egreso = parseFloat(row.Abono || 0);
            
            // Manejo especial para Saldo Inicial
            if (row.FormaPago === 'Saldo Inicial') {
                saldoAcumulado = parseFloat(row.Saldo_Inicial_Hidden || 0);
                // No sumamos ingreso/egreso para el acumulado en esta fila
            } else {
                saldoAcumulado += (ingreso - egreso);
                totalIngresosPeriodo += ingreso;
                totalEgresosPeriodo += egreso;
            }

            var noCompr = row.codigoAnti || row.Documento;
            var detalle = '';
            
            if (row.FormaPago === 'Manifiesto') {
                detalle = row.Detalle || 'Factura Nº '; 
            } else if (row.FormaPago === 'Saldo Inicial') {
                detalle = row.Detalle;
            } else {
                detalle = (row.FormaPago || 'Transferencia') + ' / Doc.Num: ' + (row.Documento || '');
            }

            html += '<tr>';
            html += '<td class="text-center" style="border: 1px solid #ccc; padding: 5px;">' + contador++ + '</td>';
            html += '<td class="text-center" style="border: 1px solid #ccc; padding: 5px;">' + (noCompr || '') + '</td>';
            
            // Ocultar fecha si es Saldo Inicial
            if (row.FormaPago === 'Saldo Inicial') {
                html += '<td class="text-center" style="border: 1px solid #ccc; padding: 5px;"></td>';
            } else {
                html += '<td class="text-center" style="border: 1px solid #ccc; padding: 5px;">' + (row.Fecha || '') + '</td>';
            }

            // Estilizar Detalle si es Saldo Inicial (centrado y negrita)
            if (row.FormaPago === 'Saldo Inicial') {
                html += '<td style="border: 1px solid #ccc; padding: 5px; text-align: center; font-weight: bold;">' + detalle + '</td>';
            } else {
                html += '<td style="border: 1px solid #ccc; padding: 5px;">' + detalle + '</td>';
            }
            
            // Columna Ingreso
            if (ingreso > 0) {
                html += '<td style="text-align: right; color: #28a745; font-weight: bold; border: 1px solid #ccc; padding: 5px; white-space: nowrap;">$ ' + formatNumber(ingreso, 2) + '</td>';
            } else {
                html += '<td style="text-align: right; border: 1px solid #ccc; padding: 5px; white-space: nowrap;">0.00</td>';
            }

            // Columna Egresos
            if (egreso > 0) {
                html += '<td style="text-align: right; color: #dc3545; font-weight: bold; border: 1px solid #ccc; padding: 5px; white-space: nowrap;">$ ' + formatNumber(egreso, 2) + '</td>';
            } else {
                html += '<td style="text-align: right; border: 1px solid #ccc; padding: 5px; white-space: nowrap;">0.00</td>';
            }

            // Columna Saldo
            html += '<td style="text-align: right; font-weight: bold; border: 1px solid #ccc; padding: 5px; white-space: nowrap;">$ ' + formatNumber(saldoAcumulado, 2) + '</td>';
            html += '</tr>';
        });

    } else {
        html += '<tr><td colspan="7" class="text-center">No hay movimientos para mostrar</td></tr>';
    }

    html += '</tbody>';
    html += '</table>';
    html += '</div>';

    // Sección Resumen del Período
    
    // Obtener valores del resumen (si existen)
    var r_saldoInicial = resumen ? parseFloat(resumen.SaldoInicial || 0) : 0;
    var r_depositos = resumen ? parseFloat(resumen.Depositos || 0) : 0;
    var r_retenciones = resumen ? parseFloat(resumen.Retenciones || 0) : 0;
    var r_manifiestosFact = resumen ? parseFloat(resumen.ManifiestosFact || 0) : 0;
    var r_manifiestosPend = resumen ? parseFloat(resumen.ManifiestosPend || 0) : 0;

    // VERSIÓN PDF/PANTALLA: Tabla flotante alineada a la derecha
    html += '<div id="resumen_print" style="width: 100%; overflow: hidden; margin-top: 20px;">';
    html += '<div style="float: right; width: 40%; min-width: 300px;">';
    html += '<table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd; font-family: Arial, sans-serif; font-size: 14px;">';
    html += '  <tr style="background-color: #f1f1f1;">';
    html += '    <td colspan="2" style="padding: 10px; font-weight: bold; border-bottom: 1px solid #ddd; text-transform: uppercase;">RESUMEN DEL PERÍODO</td>';
    html += '  </tr>';
    
    // Saldo Inicial
    html += '  <tr>';
    html += '    <td style="padding: 8px 15px;">• Saldo Inicial:</td>';
    html += '    <td style="padding: 8px 15px; text-align: right; font-weight: bold;">USD ' + formatNumber(r_saldoInicial, 2) + '</td>';
    html += '  </tr>';
    
    // Depositos (Verde +)
    html += '  <tr>';
    html += '    <td style="padding: 8px 15px;">• Depositos:</td>';
    html += '    <td style="padding: 8px 15px; text-align: right; font-weight: bold; color: #28a745;">+ USD ' + formatNumber(r_depositos, 2) + '</td>';
    html += '  </tr>';

    // Retenciones (Verde +)
    html += '  <tr>';
    html += '    <td style="padding: 8px 15px;">• Retenciones:</td>';
    html += '    <td style="padding: 8px 15px; text-align: right; font-weight: bold; color: #28a745;">+ USD ' + formatNumber(r_retenciones, 2) + '</td>';
    html += '  </tr>';
    
    // Manifiestos Fact (Rojo -)
    html += '  <tr>';
    html += '    <td style="padding: 8px 15px;">• Manifiestos Fact:</td>';
    html += '    <td style="padding: 8px 15px; text-align: right; font-weight: bold; color: #dc3545;">- USD ' + formatNumber(r_manifiestosFact, 2) + '</td>';
    html += '  </tr>';

    // Manifiestos Pend (Rojo -)
    html += '  <tr>';
    html += '    <td style="padding: 8px 15px;">• Manifiestos Pend:</td>';
    html += '    <td style="padding: 8px 15px; text-align: right; font-weight: bold; color: #dc3545;">- USD ' + formatNumber(r_manifiestosPend, 2) + '</td>';
    html += '  </tr>';

    html += '  <tr style="background-color: #34495e; border-top: 1px solid #ddd;">'; // Fondo oscuro para Saldo Final
    html += '    <td style="padding: 10px 15px; font-weight: bold; text-transform: uppercase; color: #ffffff;">SALDO FINAL</td>'; // Texto blanco
    html += '    <td style="padding: 10px 15px; text-align: right; font-weight: bold; font-size: 16px; color: #ffffff;">USD ' + formatNumber(saldoAcumulado, 2) + '</td>'; // Texto blanco
    html += '  </tr>';
    html += '</table>';
    html += '</div>';
    html += '</div>';

    // VERSIÓN EXCEL: Mapeado directo a columnas (OCULTO EN PANTALLA)
    html += '<table id="resumen_excel" style="width: 100%; margin-top: 20px; border: none; border-collapse: collapse; display: none;">';
    
    // Título RESUMEN DEL PERÍODO
    html += '<tr>';
    html += '<td colspan="4" style="border: none;"></td>'; 
    html += '<td colspan="3" style="background-color: #f1f1f1; padding: 10px; font-weight: bold; border: 1px solid #ddd; text-transform: uppercase;">RESUMEN DEL PERÍODO</td>';
    html += '</tr>';

    // Saldo Inicial
    html += '<tr>';
    html += '<td colspan="4" style="border: none;"></td>'; 
    html += '<td colspan="2" style="border: 1px solid #ddd; padding: 5px 15px; text-align: left;">• Saldo Inicial:</td>';
    html += '<td align="right" style="border: 1px solid #ddd; padding: 5px; text-align: right; font-weight: bold;">USD ' + formatNumber(r_saldoInicial, 2) + '</td>';
    html += '</tr>';

    // Depositos
    html += '<tr>';
    html += '<td colspan="4" style="border: none;"></td>'; 
    html += '<td colspan="2" style="border: 1px solid #ddd; padding: 5px 15px; text-align: left;">• Depositos:</td>';
    html += '<td align="right" style="border: 1px solid #ddd; padding: 5px; text-align: right; font-weight: bold; color: #28a745;">+ USD ' + formatNumber(r_depositos, 2) + '</td>';
    html += '</tr>';

    // Retenciones
    html += '<tr>';
    html += '<td colspan="4" style="border: none;"></td>'; 
    html += '<td colspan="2" style="border: 1px solid #ddd; padding: 5px 15px; text-align: left;">• Retenciones:</td>';
    html += '<td align="right" style="border: 1px solid #ddd; padding: 5px; text-align: right; font-weight: bold; color: #28a745;">+ USD ' + formatNumber(r_retenciones, 2) + '</td>';
    html += '</tr>';

    // Manifiestos Fact
    html += '<tr>';
    html += '<td colspan="4" style="border: none;"></td>'; 
    html += '<td colspan="2" style="border: 1px solid #ddd; padding: 5px 15px; text-align: left;">• Manifiestos Fact:</td>';
    html += '<td align="right" style="border: 1px solid #ddd; padding: 5px; text-align: right; font-weight: bold; color: #dc3545;">- USD ' + formatNumber(r_manifiestosFact, 2) + '</td>';
    html += '</tr>';

    //  Manifiestos Pend
    html += '<tr>';
    html += '<td colspan="4" style="border: none;"></td>'; 
    html += '<td colspan="2" style="border: 1px solid #ddd; padding: 5px 15px; text-align: left;">• Manifiestos Pend:</td>';
    html += '<td align="right" style="border: 1px solid #ddd; padding: 5px; text-align: right; font-weight: bold; color: #dc3545;">- USD ' + formatNumber(r_manifiestosPend, 2) + '</td>';
    html += '</tr>';

    // SALDO FINAL
    html += '<tr>';
    html += '<td colspan="4" style="border: none;"></td>'; 
    html += '<td colspan="2" style="background-color: #34495e; border: 1px solid #ddd; padding: 10px 15px; font-weight: bold; text-transform: uppercase; text-align: left; color: #ffffff;">SALDO FINAL</td>'; // Fondo oscuro y texto blanco
    html += '<td align="right" style="background-color: #34495e; border: 1px solid #ddd; padding: 10px 5px; text-align: right; font-weight: bold; font-size: 16px; color: #ffffff;">USD ' + formatNumber(saldoAcumulado, 2) + '</td>'; // Fondo oscuro y texto blanco
    html += '</tr>';

    html += '</table>';

    // Pie de página: Generado por...
    var fechaGen = new Date();
    var fechaGenStr = fechaGen.getDate() + '/' + (fechaGen.getMonth() + 1) + '/' + fechaGen.getFullYear() + ' ' + fechaGen.getHours() + ':' + (fechaGen.getMinutes()<10?'0':'') + fechaGen.getMinutes();
    
    // OCULTO EN PANTALLA (display: none), SOLO VISIBLE EN EXPORTACIÓN
    html += '<table id="footer_exportacion" style="width: 100%; display: none; margin-top: 10px; border: none;">';
    html += '<tr>';
    html += '<td colspan="7" style="text-align: right; font-size: 10px; font-style: italic; color: #555; border: none;">';
    html += 'Generado por: EXA [Software Contable] el ' + fechaGenStr;
    html += '</td>';
    html += '</tr>';
    html += '</table>';

    html += '</div>'; // panel
    html += '</div>'; // wrapper reporte-content

    $("#detalle_container").html(html);
}

/* Exportar detalle a Excel */
function exportarExcelDetalle() {
    // Clonar el contenido para manipularlo sin afectar la vista actual
    var $clone = $('#reporte-content').clone();
    
    // Hacer visible el encabezado en el clon
    $clone.find('#header_exportacion').css('display', 'table');
    // Hacer visible el pie de página en el clon
    $clone.find('#footer_exportacion').css('display', 'table');
    
    // MANEJO DE RESUMEN:
    // 1. Eliminar la versión de pantalla/PDF
    $clone.find('#resumen_print').remove();
    // 2. Hacer visible la versión de Excel (usamos display: table para asegurar estructura)
    $clone.find('#resumen_excel').css('display', 'table');

    var contenido = $clone.html();
    
    if (!contenido || contenido.trim() === '') {
        alert('No hay datos para exportar.');
        return;
    }
    
    var cliNom = $("#Cli_Nom").val() || 'Cliente';
    var fecha = new Date();
    var fechaStr = fecha.getDate() + '-' + (fecha.getMonth() + 1) + '-' + fecha.getFullYear();
    var nombreArchivo = 'Estado_Cuenta_' + cliNom.replace(/[^a-zA-Z0-9]/g, '_') + '_' + fechaStr;
    
    // Crear formulario para enviar a ficheroExcel.php
    var form = $('<form>', {
        method: 'POST',
        action: '../../Librerias/exportar/ficheroExcel.php',
        target: '_blank'
    });
    
    form.append($('<input>', { type: 'hidden', name: 'datos_a_enviar', value: contenido }));
    form.append($('<input>', { type: 'hidden', name: 'nombre', value: nombreArchivo }));
    form.append($('<input>', { type: 'hidden', name: 'hoja', value: 'Estado de Cuenta' }));
    
    $('body').append(form);
    form.submit();
    form.remove();
}

/* Imprimir detalle (o guardar como PDF) */
function imprimirDetalle() {
    // Clonar el contenido para manipularlo sin afectar la vista actual
    var $clone = $('#reporte-content').clone();
    
    // Hacer visible el encabezado en el clon
    $clone.find('#header_exportacion').css('display', 'table');
    // Hacer visible el pie de página en el clon
    $clone.find('#footer_exportacion').css('display', 'table');
    
    // MANEJO DE RESUMEN:
    // Asegurar que se ve la versión de pantalla/PDF y se oculta la de Excel
    // (Por defecto ya es así, pero por seguridad)
    $clone.find('#resumen_print').css('display', 'block');
    $clone.find('#resumen_excel').remove(); // Eliminamos el de Excel para limpiar el DOM de impresión

    var contenido = $clone.html();
    
    if (!contenido || contenido.trim() === '') {
        alert('No hay datos para imprimir.');
        return;
    }
    
    var ventana = window.open('', '_blank');
    ventana.document.write('<html><head><title>Estado de Cuenta</title>');
    // Incluir estilos básicos para impresión
    ventana.document.write('<style>');
    ventana.document.write('body { font-family: Arial, sans-serif; font-size: 12px; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }');
    ventana.document.write('@media print { body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; } }');
    ventana.document.write('.panel { border: 1px solid #ccc; margin-bottom: 20px; }');
    ventana.document.write('.panel-heading { background-color: #eee; padding: 10px; border-bottom: 1px solid #ccc; }');
    ventana.document.write('.table { width: 100%; border-collapse: collapse; margin-bottom: 0; }');
    ventana.document.write('.table th, .table td { padding: 8px; line-height: 1.42857143; vertical-align: top; border: 1px solid #ddd; }');
    ventana.document.write('.text-center { text-align: center; }');
    ventana.document.write('.text-right { text-align: right; }');
    ventana.document.write('h4, h5 { margin: 5px 0; }');
    ventana.document.write('</style>');
    ventana.document.write('</head><body>');
    ventana.document.write(contenido);
    ventana.document.write('</body></html>');
    ventana.document.close();
    ventana.focus();
    ventana.print();
}

/* Limpiar filtros */
function limpiarFiltros() {
    $("#searchEstCuenta").val('');
    $("#Pec_Cod").val('T');
    $("input[name='op_opciones'][value='cl']").prop('checked', true);
    
    $("#Pla_Cod").val("");
    $("#Pla_Nom").val("");

    // Deshabilitar y limpiar fechas
    $("#Fec_IniM, #Fec_FinM").prop('disabled', true).val('');
    
    gridEstadoCuenta.trigger("reloadGrid");
    $("#detalle_container").empty();
}

/* Formatear números con separador de miles y decimales */
function formatNumber(num, decimals) {
    if (isNaN(num)) return '0.00';
    return parseFloat(num).toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// ========================================
// FUNCIONES PARA PESTAÑA GRUPAL
// ========================================

// Inicializar datepickers para Grupal
$(document).ready(function() {
    $("#Fec_IniM_Grupal, #Fec_FinM_Grupal").datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        yearRange: "-10:+1"
    });

    // Establecer fecha por defecto: Inicio de año y hoy
    var today = new Date();
    var firstDayOfYear = new Date(today.getFullYear(), 0, 1);
    
    $("#Fec_IniM_Grupal").val($.datepicker.formatDate('yy-mm-dd', firstDayOfYear));
    $("#Fec_FinM_Grupal").val($.datepicker.formatDate('yy-mm-dd', today));

    if ($("#Fec_IniM_Comp").length) {
        $("#Fec_IniM_Comp, #Fec_FinM_Comp").datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            yearRange: "-10:+1"
        });
        $("#Fec_IniM_Comp").val($.datepicker.formatDate('yy-mm-dd', firstDayOfYear));
        $("#Fec_FinM_Comp").val($.datepicker.formatDate('yy-mm-dd', today));
    }
});

/* Cambiar período Grupal y habilitar/deshabilitar campos de fecha */
function cambiarPeriodoGrupal() {
    var pec_cod = $("#Pec_Cod_Grupal").val();
    var mes_cod = $("#Mes_Cod_Grupal").val();
    
    if (pec_cod === 'PF') {
        // Por Fechas - habilitar campos de fecha, deshabilitar mes
        $("#Fec_IniM_Grupal, #Fec_FinM_Grupal").prop('disabled', false);
        $("#Mes_Cod_Grupal").prop('disabled', true);
        $("#Mes_Cod_Grupal").val('00');
        
        if ($("#Fec_IniM_Grupal").val() === '') {
            var today = new Date();
            var firstDayOfYear = new Date(today.getFullYear(), 0, 1);
            $("#Fec_IniM_Grupal").val($.datepicker.formatDate('yy-mm-dd', firstDayOfYear));
            $("#Fec_FinM_Grupal").val($.datepicker.formatDate('yy-mm-dd', today));
        }
    } else if (pec_cod === 'T') {
        // TODOS - deshabilitar fechas y mes
        $("#Fec_IniM_Grupal, #Fec_FinM_Grupal").prop('disabled', true);
        $("#Mes_Cod_Grupal").prop('disabled', true);
        $("#Mes_Cod_Grupal").val('00');
        
        var minDate = '';
        var maxDate = $.datepicker.formatDate('yy-mm-dd', new Date());
        
        $("#Pec_Cod_Grupal option").each(function() {
            var start = $(this).data('inicio');
            if (start && (!minDate || start < minDate)) {
                minDate = start;
            }
        });
        
        if (minDate) {
            var minYear = minDate.substring(0, 4);
            $("#Fec_IniM_Grupal").val(minYear + '-01-01');
        } else {
            $("#Fec_IniM_Grupal").val('2020-01-01'); 
        }
        $("#Fec_FinM_Grupal").val(maxDate);
        
    } else {
        // Período específico (Año)
        $("#Fec_IniM_Grupal, #Fec_FinM_Grupal").prop('disabled', true);
        $("#Mes_Cod_Grupal").prop('disabled', false);
        
        var selectedOption = $("#Pec_Cod_Grupal option:selected");
        var fecIniAnio = selectedOption.data('inicio');
        var year = fecIniAnio.substring(0, 4);
        
        if (mes_cod === '00') {
            $("#Fec_IniM_Grupal").val(year + '-01-01');
            $("#Fec_FinM_Grupal").val(year + '-12-31');
        } else {
            var mesIndex = parseInt(mes_cod, 10) - 1;
            var firstDay = new Date(year, mesIndex, 1);
            var lastDay = new Date(year, mesIndex + 1, 0);
            
            $("#Fec_IniM_Grupal").val($.datepicker.formatDate('yy-mm-dd', firstDay));
            $("#Fec_FinM_Grupal").val($.datepicker.formatDate('yy-mm-dd', lastDay));
        }
    }
}

/* Intercambiar valores de las fechas Grupal */
function intercambiarFechasGrupal() {
    var fecIni = $("#Fec_IniM_Grupal").val();
    var fecFin = $("#Fec_FinM_Grupal").val();
    $("#Fec_IniM_Grupal").val(fecFin);
    $("#Fec_FinM_Grupal").val(fecIni);
}

/* Buscar Estado de Cuenta Grupal */
function buscarEstadoCuentaGrupal() {
    var fecIni = $("#Fec_IniM_Grupal").val();
    var fecFin = $("#Fec_FinM_Grupal").val();
    var mesCod = $("#Mes_Cod_Grupal").val();
    
    $.ajax({
        url: window.location.pathname,
        type: 'POST',
        data: {
            loadEstadoCuentaGrupalAjax: true,
            Fec_IniM: fecIni,
            Fec_FinM: fecFin,
            Mes_Cod: mesCod
        },
        dataType: 'json',
        beforeSend: function() {
            $("#detalle_grupal_container").html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Cargando detalle...</p></div>');
            $("#detalle_grupal_container").show();
        },
        success: function(response) {
            if (response.success && response.rows) {
                renderDetalleGrupalHTML(response.rows, fecIni, fecFin);
            } else {
                $("#detalle_grupal_container").html('<div class="alert alert-warning">No se encontraron datos para el período seleccionado.</div>');
            }
        },
        error: function() {
            $("#detalle_grupal_container").html('<div class="alert alert-danger">Error al cargar los datos.</div>');
        }
    });
}

/* Renderizar la tabla HTML con resumen abajo a la derecha */
function renderDetalleGrupalHTML(rows, fecIni, fecFin) {
    if (!rows || rows.length === 0) {
        $("#detalle_grupal_container").html('<div class="alert alert-info">No hay datos disponibles.</div>');
        return;
    }
    
    // Calcular totales
    var totales = {
        saldoInicial: 0,
        depositos: 0,
        retenciones: 0,
        manifiestosFact: 0,
        manifiestosPend: 0,
        saldoFinal: 0
    };
    
    rows.forEach(function(row) {
        var saldoInicial = parseFloat(row.Saldo_Inicial) || 0;
        var depositos = parseFloat(row.Depositos) || 0;
        var retenciones = parseFloat(row.Retenciones) || 0;
        var manifiestosFact = parseFloat(row.Manifiestos_Fact) || 0;
        var manifiestosPend = parseFloat(row.Manifiestos_Pend) || 0;
        
        totales.saldoInicial += saldoInicial;
        totales.depositos += depositos;
        totales.retenciones += retenciones;
        totales.manifiestosFact += manifiestosFact;
        totales.manifiestosPend += manifiestosPend;
    });
    
    totales.saldoFinal = totales.saldoInicial + totales.depositos + totales.retenciones - totales.manifiestosFact - totales.manifiestosPend;
    
    var html = '';
    
    // Tabla con ancho ajustado (similar al Tab Individual)
    html += '<div class="row">';
    html += '<div class="col-md-10 col-md-offset-1">';
    html += '<div style="overflow-x: auto;">';
    html += '<table class="table table-bordered table-striped table-hover" style="margin-bottom: 20px;">';
    html += '<thead style="background-color: #34495e; color: white;">';
    html += '<tr>';
    html += '<th style="text-align: center; width: 40px;">Nº</th>';
    html += '<th style="text-align: center; min-width: 200px;">Planta</th>';
    html += '<th style="text-align: right; width: 110px;">Saldo Inicial</th>';
    html += '<th style="text-align: right; width: 120px;">Depositos</th>';
    html += '<th style="text-align: right; width: 120px;">Retenciones</th>';
    html += '<th style="text-align: right; width: 130px;">Manifiestos Fact</th>';
    html += '<th style="text-align: right; width: 135px;">Manifiestos Pend</th>';
    html += '<th style="text-align: right; width: 120px;">Saldo Final</th>';
    html += '</tr>';
    html += '</thead>';
    html += '<tbody>';
    
    // Filas de datos
    rows.forEach(function(row, index) {
        var saldoInicial = parseFloat(row.Saldo_Inicial) || 0;
        var depositos = parseFloat(row.Depositos) || 0;
        var retenciones = parseFloat(row.Retenciones) || 0;
        var manifiestosFact = parseFloat(row.Manifiestos_Fact) || 0;
        var manifiestosPend = parseFloat(row.Manifiestos_Pend) || 0;
        var saldoFinal = saldoInicial + depositos + retenciones - manifiestosFact - manifiestosPend;
        
        html += '<tr>';
        html += '<td style="text-align: center;">' + (index + 1) + '</td>';
        html += '<td>' + (row.Planta || '') + '</td>';
        html += '<td style="text-align: right;">$ ' + formatNumber(saldoInicial, 2) + '</td>';
        html += '<td style="text-align: right; color: #28a745; font-weight: bold;">$ ' + formatNumber(depositos, 2) + '</td>';
        html += '<td style="text-align: right; color: #28a745; font-weight: bold;">$ ' + formatNumber(retenciones, 2) + '</td>';
        html += '<td style="text-align: right; color: #dc3545; font-weight: bold;">$ ' + formatNumber(manifiestosFact, 2) + '</td>';
        html += '<td style="text-align: right; color: #dc3545; font-weight: bold;">$ ' + formatNumber(manifiestosPend, 2) + '</td>';
        html += '<td style="text-align: right; font-weight: bold;">$ ' + formatNumber(saldoFinal, 2) + '</td>';
        html += '</tr>';
    });
    
    html += '</tbody>';
    html += '</table>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    
    // Panel de resumen debajo, alineado a la derecha
    html += '<div class="row">';
    html += '<div class="col-md-4 col-md-offset-7">';
    html += '<div style="border: 2px solid #dddddd; border-radius: 4px; padding: 20px; background-color: #fff;">';
    html += '<h4 style="margin-top: 0; margin-bottom: 20px; font-size: 16px; font-weight: bold; text-align: center; border-bottom: 1px solid #ddd; padding-bottom: 10px;">RESUMEN DEL PERÍODO</h4>';
    
    html += '<div style="margin-bottom: 12px;">';
    html += '<div style="display: flex; justify-content: space-between; padding: 5px 0;">';
    html += '<span style="color: #333;">• Saldo Inicial:</span>';
    html += '<span style="font-weight: bold; color: #000;">USD ' + formatNumber(totales.saldoInicial, 2) + '</span>';
    html += '</div>';
    html += '</div>';
    
    html += '<div style="margin-bottom: 12px;">';
    html += '<div style="display: flex; justify-content: space-between; padding: 5px 0;">';
    html += '<span style="color: #333;">• Depositos:</span>';
    html += '<span style="font-weight: bold; color: #28a745;">+ USD ' + formatNumber(totales.depositos, 2) + '</span>';
    html += '</div>';
    html += '</div>';
    
    html += '<div style="margin-bottom: 12px;">';
    html += '<div style="display: flex; justify-content: space-between; padding: 5px 0;">';
    html += '<span style="color: #333;">• Retenciones:</span>';
    html += '<span style="font-weight: bold; color: #28a745;">+ USD ' + formatNumber(totales.retenciones, 2) + '</span>';
    html += '</div>';
    html += '</div>';
    
    html += '<div style="margin-bottom: 12px;">';
    html += '<div style="display: flex; justify-content: space-between; padding: 5px 0;">';
    html += '<span style="color: #333;">• Manifiestos Fact:</span>';
    html += '<span style="font-weight: bold; color: #dc3545;">- USD ' + formatNumber(totales.manifiestosFact, 2) + '</span>';
    html += '</div>';
    html += '</div>';
    
    html += '<div style="margin-bottom: 20px;">';
    html += '<div style="display: flex; justify-content: space-between; padding: 5px 0;">';
    html += '<span style="color: #333;">• Manifiestos Pend:</span>';
    html += '<span style="font-weight: bold; color: #dc3545;">- USD ' + formatNumber(totales.manifiestosPend, 2) + '</span>';
    html += '</div>';
    html += '</div>';
    
    html += '<div style="background-color: #34495e; color: white; padding: 15px; border-radius: 4px;">';
    html += '<div style="display: flex; justify-content: space-between; align-items: center;">';
    html += '<span style="font-weight: bold; font-size: 14px;">SALDO FINAL</span>';
    html += '<span style="font-weight: bold; font-size: 18px;">USD ' + formatNumber(totales.saldoFinal, 2) + '</span>';
    html += '</div>';
    html += '</div>';
    
    html += '</div>';
    html += '</div>';
    html += '</div>';
    
    $("#detalle_grupal_container").html(html);
}

/* Exportar Estado de Cuenta Grupal a Excel */
function exportarExcelGrupal() {
    var contenido = $('#detalle_grupal_container').html();
    
    if (!contenido || contenido.trim() === '' || contenido.includes('No hay datos disponibles')) {
        alert('No hay datos para exportar. Por favor realice una búsqueda primero.');
        return;
    }
    
    // Obtener fechas para el nombre del archivo
    var fecIni = $("#Fec_IniM_Grupal").val() || '';
    var fecFin = $("#Fec_FinM_Grupal").val() || '';
    var fecha = new Date();
    var fechaStr = fecha.getDate() + '-' + (fecha.getMonth() + 1) + '-' + fecha.getFullYear();
    var nombreArchivo = 'Estado_Cuenta_Grupal_' + fecIni + '_a_' + fecFin + '_' + fechaStr;
    nombreArchivo = nombreArchivo.replace(/[^a-zA-Z0-9_-]/g, '_');
    
    // Procesar el contenido para separar tabla y resumen
    var tempDiv = document.createElement('div');
    tempDiv.innerHTML = contenido;
    
    // Extraer la tabla
    var tablaDiv = tempDiv.querySelector('.row .col-md-10');
    var tablaHTML = tablaDiv ? tablaDiv.innerHTML : '';
    
    // Extraer los totales del resumen (parseando el HTML)
    var resumenDiv = tempDiv.querySelector('.row .col-md-4');
    var totales = {
        saldoInicial: '',
        depositos: '',
        retenciones: '',
        manifiestosFact: '',
        manifiestosPend: '',
        saldoFinal: ''
    };
    
    if (resumenDiv) {
        var resumenText = resumenDiv.textContent || resumenDiv.innerText;
        
        // Extraer valores usando regex
        var saldoInicialMatch = resumenText.match(/Saldo Inicial:\s*USD\s*([\d,.-]+)/);
        var depositosMatch = resumenText.match(/Depositos:\s*\+?\s*USD\s*([\d,.-]+)/);
        var retencionesMatch = resumenText.match(/Retenciones:\s*\+?\s*USD\s*([\d,.-]+)/);
        var manifestosFactMatch = resumenText.match(/Manifiestos Fact:\s*-?\s*USD\s*([\d,.-]+)/);
        var manifestosPendMatch = resumenText.match(/Manifiestos Pend:\s*-?\s*USD\s*([\d,.-]+)/);
        var saldoFinalMatch = resumenText.match(/SALDO FINAL\s*USD\s*([\d,.-]+)/);
        
        totales.saldoInicial = saldoInicialMatch ? saldoInicialMatch[1] : '0.00';
        totales.depositos = depositosMatch ? depositosMatch[1] : '0.00';
        totales.retenciones = retencionesMatch ? retencionesMatch[1] : '0.00';
        totales.manifiestosFact = manifestosFactMatch ? manifestosFactMatch[1] : '0.00';
        totales.manifiestosPend = manifestosPendMatch ? manifestosPendMatch[1] : '0.00';
        totales.saldoFinal = saldoFinalMatch ? saldoFinalMatch[1] : '0.00';
    }
    
    // Crear un contenedor con encabezado y contenido reorganizado
    var contenidoCompleto = '<div style="text-align: center; margin-bottom: 25px;">';
    contenidoCompleto += '<h2 style="margin: 0; padding: 0; font-size: 16px; font-weight: bold;">ECOPARKMINING S.A.</h2>';
    contenidoCompleto += '<h3 style="margin: 5px 0 0 0; padding: 0; font-size: 14px; font-weight: bold;">ESTADO DE CUENTA</h3>';
    contenidoCompleto += '<p style="margin: 5px 0 0 0; padding: 0; font-size: 12px;"><strong>Desde:</strong> ' + fecIni + ' | <strong>Hasta:</strong> ' + fecFin + '</p>';
    contenidoCompleto += '</div>';
    
    // Agregar la tabla con estilos mejorados para Excel
    if (tablaHTML) {
        // Procesar el HTML de la tabla para agregar estilos inline a los encabezados
        var tablaConEstilos = tablaHTML;
        
        // Reemplazar todos los <th> para agregar estilos inline
        tablaConEstilos = tablaConEstilos.replace(
            /<th([^>]*)>/gi,
            function(match, attributes) {
                // Verificar si ya tiene style
                if (attributes.indexOf('style=') !== -1) {
                    // Ya tiene style, agregar los estilos necesarios
                    return match.replace(
                        /style="([^"]*)"/i,
                        'style="$1 background-color: #34495e !important; color: white !important; font-weight: bold; padding: 10px; border: 1px solid #ddd;"'
                    );
                } else {
                    // No tiene style, agregarlo
                    return '<th' + attributes + ' style="background-color: #34495e !important; color: white !important; font-weight: bold; padding: 10px; border: 1px solid #ddd;">';
                }
            }
        );
        
        contenidoCompleto += '<div style="margin-bottom: 20px;">';
        contenidoCompleto += tablaConEstilos;
        contenidoCompleto += '</div>';
    }
    
    // Agregar el resumen alineado a la derecha con diseño estructurado
    contenidoCompleto += '<table style="width: 100%; border-collapse: collapse; margin-top: 20px;">';
    contenidoCompleto += '<tr>';
    contenidoCompleto += '<td style="width: 10%; border: 0;">&nbsp;</td>';
    contenidoCompleto += '<td style="width: 10%; border: 0;">&nbsp;</td>';
    contenidoCompleto += '<td style="width: 10%; border: 0;">&nbsp;</td>';
    contenidoCompleto += '<td style="width: 10%; border: 0;">&nbsp;</td>';
    contenidoCompleto += '<td style="width: 10%; border: 0;">&nbsp;</td>';
    contenidoCompleto += '<td style="width: 10%; border: 0;">&nbsp;</td>';
    contenidoCompleto += '<td style="width: 40%; border: 0; vertical-align: top;">'; 
    
    // Tabla del resumen con bordes y diseño
    contenidoCompleto += '<table style="width: 100%; border: 2px solid #dddddd; border-collapse: collapse; background-color: #ffffff;">';
    
    // Título del resumen
    contenidoCompleto += '<tr>';
    contenidoCompleto += '<td colspan="2" style="background-color: #f8f9fa; border: 1px solid #dddddd; padding: 10px; text-align: center; font-weight: bold; font-size: 13px;">RESUMEN DEL PERÍODO</td>';
    contenidoCompleto += '</tr>';
    
    // Saldo Inicial
    contenidoCompleto += '<tr>';
    contenidoCompleto += '<td style="border: 1px solid #dddddd; padding: 8px; color: #333;">• Saldo Inicial:</td>';
    contenidoCompleto += '<td style="border: 1px solid #dddddd; padding: 8px; font-weight: bold; text-align: right;">USD ' + totales.saldoInicial + '</td>';
    contenidoCompleto += '</tr>';
    
    // Depositos
    contenidoCompleto += '<tr>';
    contenidoCompleto += '<td style="border: 1px solid #dddddd; padding: 8px; color: #333;">• Depositos:</td>';
    contenidoCompleto += '<td style="border: 1px solid #dddddd; padding: 8px; font-weight: bold; color: #28a745; text-align: right;">+ USD ' + totales.depositos + '</td>';
    contenidoCompleto += '</tr>';
    
    // Retenciones
    contenidoCompleto += '<tr>';
    contenidoCompleto += '<td style="border: 1px solid #dddddd; padding: 8px; color: #333;">• Retenciones:</td>';
    contenidoCompleto += '<td style="border: 1px solid #dddddd; padding: 8px; font-weight: bold; color: #28a745; text-align: right;">+ USD ' + totales.retenciones + '</td>';
    contenidoCompleto += '</tr>';
    
    // Manifiestos Fact
    contenidoCompleto += '<tr>';
    contenidoCompleto += '<td style="border: 1px solid #dddddd; padding: 8px; color: #333;">• Manifiestos Fact:</td>';
    contenidoCompleto += '<td style="border: 1px solid #dddddd; padding: 8px; font-weight: bold; color: #dc3545; text-align: right;">- USD ' + totales.manifiestosFact + '</td>';
    contenidoCompleto += '</tr>';
    
    // Manifiestos Pend
    contenidoCompleto += '<tr>';
    contenidoCompleto += '<td style="border: 1px solid #dddddd; padding: 8px; color: #333;">• Manifiestos Pend:</td>';
    contenidoCompleto += '<td style="border: 1px solid #dddddd; padding: 8px; font-weight: bold; color: #dc3545; text-align: right;">- USD ' + totales.manifiestosPend + '</td>';
    contenidoCompleto += '</tr>';
    
    // Saldo Final
    contenidoCompleto += '<tr>';
    contenidoCompleto += '<td style="border: 1px solid #dddddd; padding: 12px; background-color: #34495e; color: white; font-weight: bold;">SALDO FINAL</td>';
    contenidoCompleto += '<td style="border: 1px solid #dddddd; padding: 12px; background-color: #34495e; color: white; font-weight: bold; font-size: 16px; text-align: right;">USD ' + totales.saldoFinal + '</td>';
    contenidoCompleto += '</tr>';
    
    contenidoCompleto += '</table>'; // Cierre tabla resumen
    contenidoCompleto += '</td>';
    contenidoCompleto += '</tr>';
    contenidoCompleto += '</table>'; // Cierre tabla contenedor
    
    // Crear formulario para enviar a ficheroExcel.php
    var form = $('<form>', {
        method: 'POST',
        action: '../../Librerias/exportar/ficheroExcel.php',
        target: '_blank'
    });
    
    form.append($('<input>', { type: 'hidden', name: 'datos_a_enviar', value: contenidoCompleto }));
    form.append($('<input>', { type: 'hidden', name: 'nombre', value: nombreArchivo }));
    form.append($('<input>', { type: 'hidden', name: 'hoja', value: 'Estado de Cuenta Grupal' }));
    
    $('body').append(form);
    form.submit();
    form.remove();
}

/* Exportar Estado de Cuenta Grupal a PDF */
function exportarPDFGrupal() {
    var contenido = $('#detalle_grupal_container').html();
    
    if (!contenido || contenido.trim() === '' || contenido.includes('No hay datos disponibles')) {
        alert('No hay datos para imprimir. Por favor realice una búsqueda primero.');
        return;
    }
    
    // Obtener fechas para el encabezado
    var fecIni = $("#Fec_IniM_Grupal").val() || '';
    var fecFin = $("#Fec_FinM_Grupal").val() || '';
    
    var ventana = window.open('', '_blank');
    ventana.document.write('<html><head><title>Estado de Cuenta Grupal</title>');
    
    // Incluir estilos para impresión/PDF
    ventana.document.write('<style>');
    ventana.document.write('* { margin: 0; padding: 0; box-sizing: border-box; }');
    ventana.document.write('html, body { height: auto; }');
    ventana.document.write('body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 20px; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }');
    ventana.document.write('@page { margin: 1cm; }');
    ventana.document.write('@media print { body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; margin: 0; padding: 15px; } }');
    
    // Estilos del encabezado
    ventana.document.write('.header-container { width: 100%; margin-bottom: 20px; page-break-after: avoid; }');
    ventana.document.write('.header-title { text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 5px; }');
    ventana.document.write('.header-subtitle { text-align: center; font-size: 13px; margin-bottom: 5px; }');
    ventana.document.write('.header-period { text-align: center; font-size: 11px; margin-bottom: 10px; }');
    ventana.document.write('.header-divider { border-top: 2px solid #333; margin: 10px 0 20px 0; }');
    
    // Estilos de la tabla
    ventana.document.write('.table-container { width: 100%; page-break-inside: auto; margin-bottom: 20px; }');
    ventana.document.write('.table { width: 100%; border-collapse: collapse; margin-bottom: 0; font-size: 12px; }');
    ventana.document.write('.table th, .table td { padding: 8px; border: 1px solid #ddd; font-size: 12px; }');
    ventana.document.write('.table th { background-color: #34495e !important; color: white !important; text-align: center; font-weight: bold; }');
    ventana.document.write('.table-striped tbody tr:nth-of-type(odd) { background-color: #f9f9f9; }');
    ventana.document.write('.table tbody td { vertical-align: middle; }');
    
    // Estilos del resumen
    ventana.document.write('.summary-container { width: 100%; margin-top: 20px; page-break-inside: avoid; }');
    ventana.document.write('.summary-box { width: 400px; float: right; border: 2px solid #dddddd; border-radius: 4px; padding: 20px; background-color: #fff; }');
    ventana.document.write('.summary-title { font-size: 14px; font-weight: bold; text-align: center; border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 15px; }');
    ventana.document.write('.summary-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 12px; }');
    ventana.document.write('.summary-label { color: #333; }');
    ventana.document.write('.summary-value { font-weight: bold; }');
    ventana.document.write('.summary-value.green { color: #28a745; }');
    ventana.document.write('.summary-value.red { color: #dc3545; }');
    ventana.document.write('.summary-total { background-color: #34495e; color: white; padding: 15px; border-radius: 4px; margin-top: 20px; display: flex; justify-content: space-between; align-items: center; }');
    ventana.document.write('.summary-total-label { font-weight: bold; font-size: 13px; }');
    ventana.document.write('.summary-total-value { font-weight: bold; font-size: 16px; }');
    ventana.document.write('.clearfix::after { content: ""; display: table; clear: both; }');
    
    // Alineaciones
    ventana.document.write('.text-center { text-align: center; }');
    ventana.document.write('.text-right { text-align: right; }');
    ventana.document.write('.text-left { text-align: left; }');
    
    // Evitar saltos de página no deseados
    ventana.document.write('@media print { ');
    ventana.document.write('.header-container { page-break-after: avoid; }');
    ventana.document.write('.table-container { page-break-before: avoid; page-break-inside: auto; }');
    ventana.document.write('.summary-container { page-break-inside: avoid; }');
    ventana.document.write('thead { display: table-header-group; }');
    ventana.document.write('tr { page-break-inside: avoid; }');
    ventana.document.write('}');
    
    ventana.document.write('</style>');
    
    ventana.document.write('</head><body>');
    
    // Agregar encabezado profesional
    ventana.document.write('<div class="header-container">');
    ventana.document.write('<div class="header-title">ECOPARKMINING S.A.</div>');
    ventana.document.write('<div class="header-subtitle">ESTADO DE CUENTA GRUPAL</div>');
    ventana.document.write('<div class="header-period">Desde: ' + fecIni + ' | Hasta: ' + fecFin + '</div>');
    ventana.document.write('<div class="header-divider"></div>');
    ventana.document.write('</div>');
    
    // Procesar el contenido para separar tabla y resumen
    var tempDiv = document.createElement('div');
    tempDiv.innerHTML = contenido;
    
    // Extraer la tabla
    var tablaDiv = tempDiv.querySelector('.row .col-md-10');
    var tablaHTML = tablaDiv ? tablaDiv.innerHTML : '';
    
    // Extraer el resumen
    var resumenDiv = tempDiv.querySelector('.row .col-md-4');
    var resumenHTML = resumenDiv ? resumenDiv.innerHTML : '';
    
    // Agregar la tabla inmediatamente después del encabezado
    if (tablaHTML) {
        ventana.document.write('<div class="table-container">');
        ventana.document.write(tablaHTML);
        ventana.document.write('</div>');
    }
    
    // Agregar el resumen alineado a la derecha
    if (resumenHTML) {
        ventana.document.write('<div class="summary-container clearfix">');
        ventana.document.write('<div class="summary-box">');
        ventana.document.write(resumenHTML);
        ventana.document.write('</div>');
        ventana.document.write('</div>');
    }
    
    ventana.document.write('</body></html>');
    ventana.document.close();
    ventana.focus();
    
    // Dar tiempo para que se cargue el contenido antes de imprimir
    setTimeout(function() {
        ventana.print();
    }, 500);
}

// ========================================
// PESTAÑA COMPARACIÓN (solo administradores)
// ========================================

function cambiarPeriodoComp() {
    if (!$("#Pec_Cod_Comp").length) {
        return;
    }
    var pec_cod = $("#Pec_Cod_Comp").val();
    var mes_cod = $("#Mes_Cod_Comp").val();

    if (pec_cod === 'PF') {
        $("#Fec_IniM_Comp, #Fec_FinM_Comp").prop('disabled', false);
        $("#Mes_Cod_Comp").prop('disabled', true);
        $("#Mes_Cod_Comp").val('00');

        if ($("#Fec_IniM_Comp").val() === '') {
            var today = new Date();
            var firstDayOfYear = new Date(today.getFullYear(), 0, 1);
            $("#Fec_IniM_Comp").val($.datepicker.formatDate('yy-mm-dd', firstDayOfYear));
            $("#Fec_FinM_Comp").val($.datepicker.formatDate('yy-mm-dd', today));
        }
    } else if (pec_cod === 'T') {
        $("#Fec_IniM_Comp, #Fec_FinM_Comp").prop('disabled', true);
        $("#Mes_Cod_Comp").prop('disabled', true);
        $("#Mes_Cod_Comp").val('00');

        var minDate = '';
        var maxDate = $.datepicker.formatDate('yy-mm-dd', new Date());

        $("#Pec_Cod_Comp option").each(function() {
            var start = $(this).data('inicio');
            if (start && (!minDate || start < minDate)) {
                minDate = start;
            }
        });

        if (minDate) {
            var minYear = minDate.substring(0, 4);
            $("#Fec_IniM_Comp").val(minYear + '-01-01');
        } else {
            $("#Fec_IniM_Comp").val('2020-01-01');
        }
        $("#Fec_FinM_Comp").val(maxDate);

    } else {
        $("#Fec_IniM_Comp, #Fec_FinM_Comp").prop('disabled', true);
        $("#Mes_Cod_Comp").prop('disabled', false);

        var selectedOption = $("#Pec_Cod_Comp option:selected");
        var fecIniAnio = selectedOption.data('inicio');
        var year = fecIniAnio ? fecIniAnio.substring(0, 4) : new Date().getFullYear().toString();

        if (mes_cod === '00') {
            $("#Fec_IniM_Comp").val(year + '-01-01');
            $("#Fec_FinM_Comp").val(year + '-12-31');
        } else {
            var mesIndex = parseInt(mes_cod, 10) - 1;
            var firstDay = new Date(year, mesIndex, 1);
            var lastDay = new Date(year, mesIndex + 1, 0);

            $("#Fec_IniM_Comp").val($.datepicker.formatDate('yy-mm-dd', firstDay));
            $("#Fec_FinM_Comp").val($.datepicker.formatDate('yy-mm-dd', lastDay));
        }
    }
}

function intercambiarFechasComp() {
    var fecIni = $("#Fec_IniM_Comp").val();
    var fecFin = $("#Fec_FinM_Comp").val();
    $("#Fec_IniM_Comp").val(fecFin);
    $("#Fec_FinM_Comp").val(fecIni);
}

function buscarComparacionSaldos() {
    var todas = $("#comp_todas_plantas").length && $("#comp_todas_plantas").is(":checked");
    var plaCod = $("#Pla_Cod_Comp").val();
    if (!todas && (!plaCod || plaCod === "")) {
        alert("Seleccione una planta o marque «Todas las plantas».");
        return;
    }
    var fecIni = $("#Fec_IniM_Comp").val();
    var fecFin = $("#Fec_FinM_Comp").val();

    var postData = {
        loadComparacionSaldosAjax: true,
        Fec_IniM: fecIni,
        Fec_FinM: fecFin
    };
    if (todas) {
        postData.comparar_todas = 1;
    } else {
        postData.Pla_Cod = plaCod;
    }

    $.ajax({
        url: window.location.pathname,
        type: "POST",
        data: postData,
        dataType: "json",
        beforeSend: function() {
            var msg = todas ? "Calculando comparación para todas las plantas..." : "Calculando comparación...";
            $("#detalle_comparacion_container").html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>' + msg + "</p></div>");
        },
        success: function(response) {
            if (response.success) {
                if (response.modo === "todas") {
                    renderComparacionTodasHTML(response);
                } else {
                    renderComparacionHTML(response, fecIni, fecFin);
                }
            } else {
                $("#detalle_comparacion_container").html('<div class="alert alert-warning">' + (response.message || "No se pudo obtener la comparación.") + "</div>");
            }
        },
        error: function() {
            $("#detalle_comparacion_container").html('<div class="alert alert-danger">Error al cargar la comparación.</div>');
        }
    });
}

function renderComparacionHTML(data, fecIni, fecFin) {
    var planta = data.planta || '';
    var sec = data.detalle_ec || {};
    var saldoEc = parseFloat(data.saldo_estado_cuenta) || 0;
    var anticipo = parseFloat(data.anticipo) || 0;
    var sinF = parseFloat(data.sin_facturar) || 0;
    var saldoCab = parseFloat(data.saldo_cabecera_manifiesto) || 0;
    var dif = parseFloat(data.diferencia) || 0;

    var difClass = Math.abs(dif) < 0.01 ? '#198754' : '#dc3545';
    var difLabel = Math.abs(dif) < 0.01 ? 'Sin diferencia' : 'Diferencia';

    var html = '';
    html += '<div class="row">';
    html += '<div class="col-sm-10 col-sm-offset-1">';
    html += '<p style="font-size: 13px;"><strong>Planta:</strong> ' + $('<div/>').text(planta).html() + ' &nbsp;|&nbsp; <strong>Período:</strong> ' + fecIni + ' al ' + fecFin + '</p>';

    html += '<div class="row" style="margin-bottom: 15px;">';
    html += '<div class="col-sm-4">';
    html += '<div style="border: 2px solid #0a58ca; border-radius: 8px; padding: 12px; background: #f0f7ff;">';
    html += '<div style="font-size: 10px; font-weight: 600; color: #0a58ca; text-transform: uppercase;">Anticipos (A) — cabecera</div>';
    html += '<div style="font-size: 20px; font-weight: 800;">$ ' + formatNumber(anticipo, 2) + '</div></div></div>';
    html += '<div class="col-sm-4">';
    html += '<div style="border: 2px solid #b8860b; border-radius: 8px; padding: 12px; background: #fffbf0;">';
    html += '<div style="font-size: 10px; font-weight: 600; color: #856404; text-transform: uppercase;">Sin facturar (B) — cabecera</div>';
    html += '<div style="font-size: 20px; font-weight: 800;">$ ' + formatNumber(sinF, 2) + '</div></div></div>';
    html += '<div class="col-sm-4">';
    html += '<div style="border: 2px solid #198754; border-radius: 8px; padding: 12px; background: #f4fff8;">';
    html += '<div style="font-size: 10px; font-weight: 600; color: #0f5132; text-transform: uppercase;">Saldo total (A−B) manifiesto</div>';
    html += '<div style="font-size: 20px; font-weight: 800; color: #198754;">$ ' + formatNumber(saldoCab, 2) + '</div></div></div>';
    html += '</div>';

    html += '<div style="border: 1px solid #dee2e6; border-radius: 6px; padding: 15px; margin-bottom: 15px; background: #fafafa;">';
    html += '<strong style="color: #254463;">Desglose estado de cuenta (mismo período que reporte grupal)</strong>';
    html += '<table class="table table-condensed" style="margin-top: 10px; margin-bottom: 0;"><tbody>';
    html += '<tr><td>Saldo inicial</td><td style="text-align: right;">$ ' + formatNumber(parseFloat(sec.saldo_inicial) || 0, 2) + '</td></tr>';
    html += '<tr><td>Depósitos</td><td style="text-align: right; color: #28a745;">+ $ ' + formatNumber(parseFloat(sec.depositos) || 0, 2) + '</td></tr>';
    html += '<tr><td>Retenciones</td><td style="text-align: right; color: #28a745;">+ $ ' + formatNumber(parseFloat(sec.retenciones) || 0, 2) + '</td></tr>';
    html += '<tr><td>Manifiestos facturados</td><td style="text-align: right; color: #dc3545;">− $ ' + formatNumber(parseFloat(sec.manifiestos_fact) || 0, 2) + '</td></tr>';
    html += '<tr><td>Manifiestos pendientes</td><td style="text-align: right; color: #dc3545;">− $ ' + formatNumber(parseFloat(sec.manifiestos_pend) || 0, 2) + '</td></tr>';
    html += '<tr style="font-weight: bold; background: #e9ecef;"><td>Saldo final estado de cuenta</td><td style="text-align: right;">$ ' + formatNumber(saldoEc, 2) + '</td></tr>';
    html += '</tbody></table></div>';

    html += '<div style="border: 2px solid ' + difClass + '; border-radius: 8px; padding: 16px; text-align: center; background: ' + (Math.abs(dif) < 0.01 ? '#d1e7dd' : '#f8d7da') + ';">';
    html += '<div style="font-size: 11px; font-weight: 700; color: ' + difClass + '; text-transform: uppercase;">' + difLabel + ' (EC − cabecera)</div>';
    html += '<div style="font-size: 24px; font-weight: 800; color: ' + difClass + ';">$ ' + formatNumber(dif, 2) + '</div>';
    html += '</div>';

    html += '</div></div>';

    $("#detalle_comparacion_container").html(html);
}

function renderComparacionTodasHTML(data) {
    var rows = data.rows || [];
    var fecIni = data.fec_ini || "";
    var fecFin = data.fec_fin || "";
    var n = rows.length;
    var conDif = 0;
    var i, r, dif, rowBg;

    var html = "";
    html += '<div class="row">';
    html += '<div class="col-sm-12">';
    html += '<p style="font-size: 13px;"><strong>Período:</strong> ' + fecIni + " al " + fecFin + " &nbsp;|&nbsp; <strong>Plantas incluidas:</strong> " + n + "</p>";
    html += '<div style="overflow-x: auto;">';
    html += '<table class="table table-bordered table-hover" style="font-size: 12px; margin-bottom: 0; border-collapse: collapse;">';
    var thGrp = 'text-align: center; vertical-align: middle; background-color: #34495e; color: #fff; border: 1px solid #2c3e50; font-weight: 700; padding: 6px 4px;';
    var thSub = 'text-align: center; background-color: #34495e; color: #fff; border: 1px solid #2c3e50; font-weight: 700; padding: 5px 4px;';
    html += '<thead>';
    html += '<tr>';
    html += '<th rowspan="2" style="' + thGrp + ' width: 38px;">N°</th>';
    html += '<th rowspan="2" style="' + thGrp + ' min-width: 180px;">Planta</th>';
    html += '<th colspan="3" style="' + thGrp + '">PLATAFORMA DE PLANTAS</th>';
    html += '<th colspan="1" style="' + thGrp + '">ADMINISTRACIÓN</th>';
    html += '<th rowspan="2" style="' + thGrp + ' width: 90px;">Diferencia</th>';
    html += '</tr><tr>';
    html += '<th style="' + thSub + '">Anticipos (A)</th>';
    html += '<th style="' + thSub + '">Sin fact. (B)</th>';
    html += '<th style="' + thSub + '">Saldo A–B manif.</th>';
    html += '<th style="' + thSub + '">Saldo final EC</th>';
    html += '</tr></thead><tbody>';

    if (n === 0) {
        html += '<tr><td colspan="7" class="text-center text-muted">No hay plantas con cliente en esta empresa para comparar.</td></tr>';
    } else {
        for (i = 0; i < n; i++) {
            r = rows[i];
            dif = parseFloat(r.diferencia) || 0;
            if (Math.abs(dif) >= 0.01) {
                conDif++;
            }
            rowBg = Math.abs(dif) >= 0.01 ? "background-color: #fff3cd;" : "";
            html += '<tr style="' + rowBg + '">';
            html += '<td style="text-align: center; border: 1px solid #ccc;">' + (i + 1) + "</td>";
            html += '<td style="text-align: center; border: 1px solid #ccc;">' + $("<div/>").text(r.planta || "").html() + "</td>";
            html += '<td style="text-align: center; border: 1px solid #ccc;">$ ' + formatNumber(parseFloat(r.anticipo) || 0, 2) + "</td>";
            html += '<td style="text-align: center; border: 1px solid #ccc;">$ ' + formatNumber(parseFloat(r.sin_facturar) || 0, 2) + "</td>";
            html += '<td style="text-align: center; border: 1px solid #ccc; font-weight: 600; color: #198754;">$ ' + formatNumber(parseFloat(r.saldo_cabecera_manifiesto) || 0, 2) + "</td>";
            html += '<td style="text-align: center; border: 1px solid #ccc; font-weight: 700;">$ ' + formatNumber(parseFloat(r.saldo_estado_cuenta) || 0, 2) + "</td>";
            html += '<td style="text-align: center; border: 1px solid #ccc; font-weight: 700; color: ' + (Math.abs(dif) < 0.01 ? "#198754" : "#c0392b") + ';">$ ' + formatNumber(dif, 2) + "</td>";
            html += "</tr>";
        }
    }

    html += "</tbody></table></div>";
    if (n > 0) {
        html += '<p style="margin-top: 10px; font-size: 12px; color: #555;">Filas con diferencia distinta de cero (±0,01): <strong>' + conDif + "</strong> de " + n + "</p>";
    }
    html += "</div></div>";

    $("#detalle_comparacion_container").html(html);
}
