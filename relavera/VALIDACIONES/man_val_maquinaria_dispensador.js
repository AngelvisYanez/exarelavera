$(document).ready(function () {
    // Inicializar grids
    inicializarGridDispensador();
    inicializarGridIngresos();
    inicializarGridDespachos();
    inicializarGridAjustes();
    inicializarGridKardex();
    inicializarGridCierres();

    // Reajustar grids si la ventana cambia de tamaño
    $(window).resize(function () {
        if (typeof exaUiFitJqGrid === "function") {
            if ($("#tab-dispensadores").hasClass("active")) {
                exaUiFitJqGrid('#gridData', '#tab-dispensadores .exa-ui-grid-host');
            }
            if ($("#tab-ingresos").hasClass("active")) {
                exaUiFitJqGrid('#gridIngresos', '#tab-ingresos .exa-ui-grid-host');
            }
            if ($("#tab-despachos").hasClass("active")) {
                exaUiFitJqGrid('#gridDespachos', '#tab-despachos .exa-ui-grid-host');
            }
            if ($("#tab-ajustes").hasClass("active")) {
                exaUiFitJqGrid('#gridAjustes', '#tab-ajustes .exa-ui-grid-host');
            }
            if ($("#tab-kardex").hasClass("active")) {
                exaUiFitJqGrid('#gridKardex', '#tab-kardex .exa-ui-grid-host');
            }
            if ($("#tab-cierre").hasClass("active")) {
                exaUiFitJqGrid('#gridCierre', '#tab-cierre .exa-ui-grid-host');
            }
        }
    });

    // Reajustar al cambiar de tab
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr("href");
        if (typeof exaUiFitJqGrid === "function") {
            if (target === "#tab-dispensadores") {
                exaUiFitJqGrid('#gridData', '#tab-dispensadores .exa-ui-grid-host');
            } else if (target === "#tab-ingresos") {
                exaUiFitJqGrid('#gridIngresos', '#tab-ingresos .exa-ui-grid-host');
            } else if (target === "#tab-despachos") {
                exaUiFitJqGrid('#gridDespachos', '#tab-despachos .exa-ui-grid-host');
            } else if (target === "#tab-ajustes") {
                exaUiFitJqGrid('#gridAjustes', '#tab-ajustes .exa-ui-grid-host');
            } else if (target === "#tab-kardex") {
                exaUiFitJqGrid('#gridKardex', '#tab-kardex .exa-ui-grid-host');
            } else if (target === "#tab-cierre") {
                exaUiFitJqGrid('#gridCierre', '#tab-cierre .exa-ui-grid-host');
            }
        }
    });
});;

// ======================================================================
// FASE 1: DISPENSADORES
// ======================================================================
function inicializarGridDispensador() {
    $("#gridData").jqGrid({
        url: 'man_alt_maquinaria_dispensador.php?listGridAjax=true',
        datatype: "json",
        mtype: "GET",
        colNames: ['Cod', 'Nombre', 'Capacidad', 'Combustible', 'Unidad', 'Estado', 'Opciones'],
        colModel: [
            { name: 'Dis_Cod', index: 'Dis_Cod', width: 50, align: 'center', key: true },
            { name: 'Dis_Nom', index: 'Dis_Nom', width: 200 },
            { name: 'Dis_Cap', index: 'Dis_Cap', width: 100, align: 'right' },
            { name: 'Dis_Tip', index: 'Dis_Tip', width: 100, align: 'center', formatter: formatoCombustible },
            { name: 'Dis_Uni', index: 'Dis_Uni', width: 100, align: 'center', formatter: formatoUnidad },
            { name: 'Dis_Est', index: 'Dis_Est', width: 80, align: 'center', formatter: formatoEstadoDispensador },
            { name: 'opciones', index: 'opciones', width: 100, align: 'center', sortable: false, formatter: formatoOpcionesDispensador }
        ],
        rowNum: 50,
        rowList: [20, 50, 100],
        pager: '#pagerData',
        sortname: 'Dis_Cod',
        viewrecords: true,
        sortorder: "desc",
        height: 350,
        autowidth: true,
        shrinkToFit: true,
        loadComplete: function () {
            if (typeof exaUiFitJqGrid === "function") {
                exaUiFitJqGrid('#gridData', '#tab-dispensadores .exa-ui-grid-host');
            }
        }
    });
    $("#gridData").jqGrid('navGrid', '#pagerData', { edit: false, add: false, del: false, search: false, refresh: false });
}

function formatoCombustible(cellvalue, options, rowObject) {
    if (cellvalue == 'DI' || cellvalue == 'DIESEL') return 'DIESEL';
    if (cellvalue == 'SU' || cellvalue == 'SUPER') return 'SUPER';
    if (cellvalue == 'EC' || cellvalue == 'ECO') return 'ECO';
    if (cellvalue == 'EX' || cellvalue == 'EXTRA') return 'EXTRA';
    return cellvalue;
}

function formatoUnidad(cellvalue, options, rowObject) {
    if (cellvalue == 'GA' || cellvalue == 'GALONES') return 'GALONES';
    if (cellvalue == 'LI' || cellvalue == 'LITROS') return 'LITROS';
    return cellvalue;
}

function formatoEstadoDispensador(cellvalue, options, rowObject) {
    if (cellvalue == 'A') {
        return '<span class="label label-success">ACTIVO</span>';
    } else {
        return '<span class="label label-danger">INACTIVO</span>';
    }
}

function formatoOpcionesDispensador(cellvalue, options, rowObject) {
    var disCod = rowObject.Dis_Cod;
    var est = rowObject.Dis_Est;
    var rowDataStr = encodeURIComponent(JSON.stringify(rowObject));
    
    var btnEditar = '<button class="btn btn-xs btn-primary" onclick="abrirModalEditar(\'' + rowDataStr + '\')" title="Editar"><i class="glyphicon glyphicon-edit"></i></button>';
    var btnEstado = '';
    
    if (est == 'A') {
        btnEstado = '<button class="btn btn-xs btn-danger" onclick="cambiarEstado(' + disCod + ',\'I\')" title="Inactivar"><i class="glyphicon glyphicon-ban-circle"></i></button>';
    } else {
        btnEstado = '<button class="btn btn-xs btn-success" onclick="cambiarEstado(' + disCod + ',\'A\')" title="Activar"><i class="glyphicon glyphicon-ok-circle"></i></button>';
    }
    
    return btnEditar + " " + btnEstado;
}

function searchGrid() {
    var search = $("#search_dis_nom").val();
    $("#gridData").jqGrid('setGridParam', {
        url: 'man_alt_maquinaria_dispensador.php?listGridAjax=true&search=' + search,
        page: 1
    }).trigger("reloadGrid");
}

function reloadGrid() {
    $("#gridData").trigger("reloadGrid");
}

function abrirModalNuevo() {
    $("#formDispensador")[0].reset();
    $("#Dis_Cod").val("0");
    $("#modalFormulario").modal('show');
}

function abrirModalEditar(rowDataStr) {
    var rowData = JSON.parse(decodeURIComponent(rowDataStr));
    $("#Dis_Cod").val(rowData.Dis_Cod);
    $("#Dis_Nom").val(rowData.Dis_Nom);
    $("#Dis_Cap").val(rowData.Dis_Cap);
    $("#Dis_Tip").val(rowData.Dis_Tip);
    $("#Dis_Uni").val(rowData.Dis_Uni);
    $("#modalFormulario").modal('show');
}

function guardar(btn) {
    var $btn = $(btn);
    var disNom = $.trim($("#Dis_Nom").val());
    var disCap = parseFloat($("#Dis_Cap").val());
    var disTip = $("#Dis_Tip").val();
    var disUni = $("#Dis_Uni").val();

    if (disNom == "") { $.alert("El nombre del dispensador es obligatorio."); return; }
    if (isNaN(disCap) || disCap <= 0) { $.alert("La capacidad debe ser mayor a cero."); return; }
    if (disTip == "") { $.alert("Seleccione el tipo de combustible."); return; }
    if (disUni == "") { $.alert("Seleccione la unidad de medida."); return; }

    var formData = $("#formDispensador").serialize();
    formData += '&saveAjax=true';

    if ($btn.prop('disabled')) { return; }
    $btn.prop('disabled', true).addClass('is-loading');
    if (typeof $.carga === "function") { $.carga('show'); }

    $.ajax({
        url: 'man_alt_maquinaria_dispensador.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function (res) {
            $btn.prop('disabled', false).removeClass('is-loading');
            if (typeof $.carga === "function") { $.carga('hide'); }
            
            if (res.success) {
                $("#modalFormulario").modal('hide');
                $.alert(res.message || "Registro guardado correctamente.");
                reloadGrid();
            } else {
                $.alert("Error: " + res.message);
            }
        },
        error: function () {
            $btn.prop('disabled', false).removeClass('is-loading');
            if (typeof $.carga === "function") { $.carga('hide'); }
            $.alert("Error de comunicacion con el servidor. La transaccion fue reversada.");
        }
    });
}

function cambiarEstado(disCod, nuevoEstado) {
    var accion = (nuevoEstado == 'A') ? "activar" : "inactivar";
    if (typeof $.createDialogConfirm === "function") {
        $.createDialogConfirm("Esta seguro de " + accion + " este dispensador?", { disCod: disCod, nuevoEstado: nuevoEstado }, function(data) {
            ejecutarCambioEstado(data.disCod, data.nuevoEstado);
        });
    } else {
        if (confirm("Esta seguro de " + accion + " este dispensador?")) {
            ejecutarCambioEstado(disCod, nuevoEstado);
        }
    }
}

function ejecutarCambioEstado(disCod, nuevoEstado) {
    if (typeof $.carga === "function") { $.carga('show'); }
    $.ajax({
        url: 'man_alt_maquinaria_dispensador.php',
        type: 'POST',
        data: {
            changeEstadoAjax: true,
            Dis_Cod: disCod,
            Dis_Est: nuevoEstado
        },
        dataType: 'json',
        success: function (res) {
            if (typeof $.carga === "function") { $.carga('hide'); }
            if (res.success) {
                $.alert(res.message || "Estado actualizado correctamente.");
                reloadGrid();
            } else {
                $.alert("Error: " + res.message);
            }
        },
        error: function () {
            if (typeof $.carga === "function") { $.carga('hide'); }
            $.alert("Error de comunicacion con el servidor.");
        }
    });
}

// ======================================================================
// FASE 2: CARGA DE COMBUSTIBLE
// ======================================================================
function inicializarGridIngresos() {
    $("#gridIngresos").jqGrid({
        url: 'man_alt_maquinaria_dispensador.php?listIngresosGridAjax=true',
        datatype: "json",
        mtype: "GET",
        colNames: ['Cod', 'Tipo', 'Fecha', 'Dispensador', 'Responsable', 'Cantidad', 'Precio Unit.', 'Total Ref.', 'Estado', 'Opciones'],
        colModel: [
            { name: 'Did_Cod', index: 'Did_Cod', width: 50, align: 'center', key: true },
            { name: 'Did_Tip', index: 'Did_Tip', width: 120, align: 'center', formatter: formatoTipoIngreso },
            { name: 'Did_Fec', index: 'Did_Fec', width: 80, align: 'center' },
            { name: 'Dis_Nom', index: 'Dis_Nom', width: 150 },
            { name: 'responsable', index: 'responsable', width: 180, formatter: formatoResponsableIngreso },
            { name: 'Did_Can', index: 'Did_Can', width: 80, align: 'right', formatter: 'number' },
            { name: 'Did_Pun', index: 'Did_Pun', width: 80, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ' } },
            { name: 'total_calculado', index: 'total_calculado', width: 100, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ' } },
            { name: 'Did_Est', index: 'Did_Est', width: 60, align: 'center', formatter: formatoEstadoIngreso },
            { name: 'opciones', index: 'opciones', width: 60, align: 'center', sortable: false, formatter: formatoOpcionesIngreso }
        ],
        rowNum: 50,
        rowList: [20, 50, 100],
        pager: '#pagerIngresos',
        sortname: 'Did_Cod',
        viewrecords: true,
        sortorder: "desc",
        height: 350,
        autowidth: true,
        shrinkToFit: true,
        footerrow: true,
        userDataOnFooter: false,
        loadComplete: function () {
            var grid = $(this);
            var ids = grid.jqGrid('getDataIDs');
            var sumCan = 0, sumPun = 0, sumTotal = 0;
            for (var i = 0; i < ids.length; i++) {
                var r = grid.jqGrid('getRowData', ids[i]);
                sumCan += parseFloat(String(r.Did_Can).replace(/[^\d.-]/g, '')) || 0;
                sumPun += parseFloat(String(r.Did_Pun).replace(/[^\d.-]/g, '')) || 0;
                sumTotal += parseFloat(String(r.total_calculado).replace(/[^\d.-]/g, '')) || 0;
            }
            grid.jqGrid('footerData', 'set', {
                responsable: 'TOTALES:',
                Did_Can: sumCan,
                Did_Pun: sumPun,
                total_calculado: sumTotal
            });

            if (typeof exaUiFitJqGrid === "function" && $("#tab-ingresos").hasClass("active")) {
                exaUiFitJqGrid('#gridIngresos', '#tab-ingresos .exa-ui-grid-host');
            }
        }
    });
    $("#gridIngresos").jqGrid('navGrid', '#pagerIngresos', { edit: false, add: false, del: false, search: false, refresh: false });
}

function formatoTipoIngreso(cellvalue, options, rowObject) {
    if (cellvalue == 'IN') return 'Compra a Proveedor';
    if (cellvalue == 'IC') return 'Ingreso Consignado';
    return cellvalue;
}

function formatoResponsableIngreso(cellvalue, options, rowObject) {
    if (rowObject.Did_Tip == 'IN') return rowObject.proveedor_nombre || '';
    if (rowObject.Did_Tip == 'IC') return rowObject.vehiculo_nombre || '';
    return '';
}

function formatoEstadoIngreso(cellvalue, options, rowObject) {
    if (cellvalue == 'A') {
        return '<span class="label label-success">ACTIVO</span>';
    } else {
        return '<span class="label label-danger">ANULADO</span>';
    }
}

function formatoOpcionesIngreso(cellvalue, options, rowObject) {
    var didCod = rowObject.Did_Cod;
    var est = rowObject.Did_Est;
    var btnAnular = '';
    
    if (est == 'A') {
        btnAnular = '<button class="btn btn-xs btn-danger" onclick="anularIngreso(' + didCod + ')" title="Anular Carga"><i class="glyphicon glyphicon-remove"></i></button>';
    }
    
    return btnAnular;
}

function reloadGridIngresos() {
    var fec_ini = $("#filtro_fec_ini").val();
    var fec_fin = $("#filtro_fec_fin").val();
    var Dis_Cod = $("#filtro_Dis_Cod_In").val();
    var Prv_Cod = $("#filtro_Prv_Cod_In").val();

    $("#gridIngresos").jqGrid('setGridParam', {
        url: 'man_alt_maquinaria_dispensador.php?listIngresosGridAjax=true&fec_ini=' + fec_ini + '&fec_fin=' + fec_fin + '&Dis_Cod=' + Dis_Cod + '&Prv_Cod=' + Prv_Cod,
        page: 1
    }).trigger("reloadGrid");
}

function limpiarFiltrosIngresos() {
    $("#formFiltrosIngresos")[0].reset();
    reloadGridIngresos();
}

function abrirModalIngreso() {
    $("#formIngreso")[0].reset();
    $("#infoDispensadorBox").hide();
    $("#capacidad_disponible").val(0);
    $("#lbl_Total").text("0.00");
    cambiarTipoIngreso();
    $("#modalFormularioIngreso").modal('show');
}

function cambiarTipoIngreso() {
    var tip = $("#Did_Tip").val();
    if (tip == 'IN') {
        $("#div_proveedor").slideDown();
        $("#div_vehiculo").slideUp();
        $("#Veh_Cod_In").val('');
    } else if (tip == 'IC') {
        $("#div_proveedor").slideUp();
        $("#div_vehiculo").slideDown();
        $("#Prv_Cod_In").val('');
    } else {
        $("#div_proveedor").slideUp();
        $("#div_vehiculo").slideUp();
        $("#Prv_Cod_In").val('');
        $("#Veh_Cod_In").val('');
    }
}

function cargarInfoDispensador(dis_cod) {
    if (dis_cod == "") {
        $("#infoDispensadorBox").hide();
        $("#capacidad_disponible").val(0);
        return;
    }

    if (typeof $.carga === "function") { $.carga('show'); }

    $.ajax({
        url: 'man_alt_maquinaria_dispensador.php',
        type: 'POST',
        data: { getInfoDispensadorAjax: true, Dis_Cod: dis_cod },
        dataType: 'json',
        success: function(res) {
            if (typeof $.carga === "function") { $.carga('hide'); }
            if (res.success && res.data) {
                var d = res.data;
                var cap = parseFloat(d.Dis_Cap) || 0;
                var ext = parseFloat(d.existencia) || 0;
                var disp = cap - ext;
                
                var tip = d.Dis_Tip;
                if (tip == 'DI') tip = 'DIESEL';
                if (tip == 'SU') tip = 'SUPER';
                if (tip == 'EC') tip = 'ECO';
                if (tip == 'EX') tip = 'EXTRA';
                
                var uni = d.Dis_Uni;
                if (uni == 'GA') uni = 'GALONES';
                if (uni == 'LI') uni = 'LITROS';

                $("#lbl_Dis_Tip").text(tip);
                $("#lbl_Dis_Uni").text(uni);
                $("#lbl_Dis_Cap").text(cap.toFixed(2));
                $("#lbl_Dis_Ext").text(ext.toFixed(2));
                $("#lbl_Dis_Dispo").text(disp.toFixed(2));
                $("#capacidad_disponible").val(disp);

                $("#infoDispensadorBox").show();
            } else {
                $.alert("Error al obtener informacion del dispensador.");
                $("#infoDispensadorBox").hide();
                $("#capacidad_disponible").val(0);
            }
        },
        error: function() {
            if (typeof $.carga === "function") { $.carga('hide'); }
            $.alert("Error de comunicacion al consultar dispensador.");
        }
    });
}

function calcularTotal() {
    var can = parseFloat($("#Did_Can").val()) || 0;
    var pun = parseFloat($("#Did_Pun").val()) || 0;
    var total = can * pun;
    $("#lbl_Total").text(total.toFixed(2));
}

function guardarIngreso(btn) {
    var $btn = $(btn);

    var Dis_Cod = $("#Dis_Cod_In").val();
    var Did_Tip = $("#Did_Tip").val();
    var Prv_Cod = $("#Prv_Cod_In").val();
    var Veh_Cod = $("#Veh_Cod_In").val();
    var Did_Fec = $("#Did_Fec").val();
    var Did_Can = parseFloat($("#Did_Can").val());
    var Did_Pun = parseFloat($("#Did_Pun").val());
    var capDispo = parseFloat($("#capacidad_disponible").val());

    if (Did_Tip == "") { $.alert("Seleccione el tipo de ingreso."); return; }
    if (Did_Tip == "IN" && Prv_Cod == "") { $.alert("Seleccione un proveedor."); return; }
    if (Did_Tip == "IC" && Veh_Cod == "") { $.alert("Seleccione un vehículo consignado."); return; }
    if (Dis_Cod == "") { $.alert("Seleccione un dispensador."); return; }
    if (Did_Fec == "") { $.alert("Ingrese la fecha."); return; }
    if (isNaN(Did_Can) || Did_Can <= 0) { $.alert("La cantidad debe ser mayor a cero."); return; }
    
    if (isNaN(Did_Pun) || Did_Pun < 0) { 
        Did_Pun = 0;
        $("#Did_Pun").val(0);
    }
    
    if (Did_Can > capDispo) {
        $.alert("La cantidad ingresada supera la capacidad disponible del dispensador (" + capDispo.toFixed(2) + ").");
        return;
    }

    var formData = $("#formIngreso").serialize();
    formData += '&saveIngresoAjax=true';

    if ($btn.prop('disabled')) { return; }
    $btn.prop('disabled', true).addClass('is-loading');
    if (typeof $.carga === "function") { $.carga('show'); }

    $.ajax({
        url: 'man_alt_maquinaria_dispensador.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function (res) {
            $btn.prop('disabled', false).removeClass('is-loading');
            if (typeof $.carga === "function") { $.carga('hide'); }
            
            if (res.success) {
                $("#modalFormularioIngreso").modal('hide');
                setTimeout(function() { $.alert(res.message || "Carga interna registrada correctamente."); }, 300);
                reloadGridIngresos();
            } else {
                $.alert("Error: " + res.message);
            }
        },
        error: function () {
            $btn.prop('disabled', false).removeClass('is-loading');
            if (typeof $.carga === "function") { $.carga('hide'); }
            $.alert("Error de comunicacion con el servidor. La transaccion fue reversada.");
        }
    });
}

function anularIngreso(didCod) {
    if (typeof $.createDialogConfirm === "function") {
        $.createDialogConfirm("Esta seguro de anular esta carga? Esto afectara el calculo de existencias.", { didCod: didCod }, function(data) {
            ejecutarAnulacionIngreso(data.didCod);
        });
    } else {
        if (confirm("Esta seguro de anular esta carga? Esto afectara el calculo de existencias.")) {
            ejecutarAnulacionIngreso(didCod);
        }
    }
}

function ejecutarAnulacionIngreso(didCod) {
    if (typeof $.carga === "function") { $.carga('show'); }
    $.ajax({
        url: 'man_alt_maquinaria_dispensador.php',
        type: 'POST',
        data: {
            changeEstadoIngresoAjax: true,
            Did_Cod: didCod
        },
        dataType: 'json',
        success: function (res) {
            if (typeof $.carga === "function") { $.carga('hide'); }
            if (res.success) {
                $.alert(res.message || "Carga anulada correctamente.");
                reloadGridIngresos();
            } else {
                $.alert("Error: " + res.message);
            }
        },
        error: function () {
            if (typeof $.carga === "function") { $.carga('hide'); }
            $.alert("Error de comunicacion con el servidor.");
        }
    });
}


// ======================================================================
// FASE 3: DESPACHOS DE COMBUSTIBLE
// ======================================================================
function inicializarGridDespachos() {
    $("#gridDespachos").jqGrid({
        url: 'man_alt_maquinaria_dispensador.php?listDespachosGridAjax=true',
        datatype: "json",
        mtype: "GET",
        colNames: ['Cod', 'Tipo', 'Fecha', 'Dispensador', 'Responsable / Destino', 'Cantidad', 'Precio Ref.', 'Total Ref.', 'Estado', 'Opciones'],
        colModel: [
            { name: 'Did_Cod', index: 'Did_Cod', width: 50, align: 'center', key: true },
            { name: 'Did_Tip', index: 'Did_Tip', width: 120, align: 'center', formatter: formatoTipoDespacho },
            { name: 'Did_Fec', index: 'Did_Fec', width: 80, align: 'center' },
            { name: 'Dis_Nom', index: 'Dis_Nom', width: 150 },
            { name: 'responsable', index: 'responsable', width: 180, formatter: formatoResponsableDespacho },
            { name: 'Did_Can', index: 'Did_Can', width: 80, align: 'right', formatter: 'number' },
            { name: 'Did_Pun', index: 'Did_Pun', width: 80, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ' } },
            { name: 'total_calculado', index: 'total_calculado', width: 100, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ' } },
            { name: 'Did_Est', index: 'Did_Est', width: 60, align: 'center', formatter: formatoEstadoIngreso },
            { name: 'opciones', index: 'opciones', width: 60, align: 'center', sortable: false, formatter: formatoOpcionesDespacho }
        ],
        rowNum: 50,
        rowList: [20, 50, 100],
        pager: '#pagerDespachos',
        sortname: 'Did_Cod',
        viewrecords: true,
        sortorder: "desc",
        height: 350,
        autowidth: true,
        shrinkToFit: true,
        footerrow: true,
        userDataOnFooter: false,
        loadComplete: function () {
            var grid = $(this);
            var ids = grid.jqGrid('getDataIDs');
            var sumCan = 0, sumPun = 0, sumTotal = 0;
            for (var i = 0; i < ids.length; i++) {
                var r = grid.jqGrid('getRowData', ids[i]);
                sumCan += parseFloat(String(r.Did_Can).replace(/[^\d.-]/g, '')) || 0;
                sumPun += parseFloat(String(r.Did_Pun).replace(/[^\d.-]/g, '')) || 0;
                sumTotal += parseFloat(String(r.total_calculado).replace(/[^\d.-]/g, '')) || 0;
            }
            grid.jqGrid('footerData', 'set', {
                responsable: 'TOTALES:',
                Did_Can: sumCan,
                Did_Pun: sumPun,
                total_calculado: sumTotal
            });

            if (typeof exaUiFitJqGrid === "function" && $("#tab-despachos").hasClass("active")) {
                exaUiFitJqGrid('#gridDespachos', '#tab-despachos .exa-ui-grid-host');
            }
        }
    });
    $("#gridDespachos").jqGrid('navGrid', '#pagerDespachos', { edit: false, add: false, del: false, search: false, refresh: false });
}

function formatoTipoDespacho(cellvalue, options, rowObject) {
    if (cellvalue == 'SA') return 'Abastecimiento a Maquinaria';
    if (cellvalue == 'SC') return 'Ajuste Negativo';
    return cellvalue;
}

function formatoResponsableDespacho(cellvalue, options, rowObject) {
    if (rowObject.Did_Tip == 'SA') return rowObject.vehiculo_nombre || '';
    if (rowObject.Did_Tip == 'SC') return rowObject.Did_Obs || '';
    return '';
}

function formatoOpcionesDespacho(cellvalue, options, rowObject) {
    var didCod = rowObject.Did_Cod;
    var est = rowObject.Did_Est;
    var btnAnular = '';
    
    if (est == 'A') {
        btnAnular = '<button class="btn btn-xs btn-danger" onclick="anularDespacho(' + didCod + ')" title="Anular Salida"><i class="glyphicon glyphicon-remove"></i></button>';
    }
    
    return btnAnular;
}

function reloadGridDespachos() {
    var fec_ini = $("#filtro_fec_ini_out").val();
    var fec_fin = $("#filtro_fec_fin_out").val();
    var Dis_Cod = $("#filtro_Dis_Cod_Out").val();
    var Did_Tip = $("#filtro_Did_Tip_Out").val();

    $("#gridDespachos").jqGrid('setGridParam', {
        url: 'man_alt_maquinaria_dispensador.php?listDespachosGridAjax=true&fec_ini=' + fec_ini + '&fec_fin=' + fec_fin + '&Dis_Cod=' + Dis_Cod + '&Did_Tip=' + Did_Tip,
        page: 1
    }).trigger("reloadGrid");
}

function limpiarFiltrosDespachos() {
    $("#formFiltrosDespachos")[0].reset();
    reloadGridDespachos();
}

function abrirModalDespacho() {
    $("#formDespacho")[0].reset();
    $("#infoDispensadorBoxOut").hide();
    $("#existencia_actual_out").val(0);
    $("#lbl_Total_Out").text("0.00");
    cambiarTipoSalida();
    $("#modalFormularioDespacho").modal('show');
}

function cambiarTipoSalida() {
    var tip = $("#Did_Tip_Out").val();
    if (tip == 'SA') {
        $("#div_vehiculo_out").slideDown();
        $("#div_motivo_out").slideUp();
        $("#div_precio_out").slideDown();
        $("#div_total_out").slideDown();
        $("#Did_Obs_Out").val('');
    } else if (tip == 'SC') {
        $("#div_vehiculo_out").slideUp();
        $("#div_motivo_out").slideDown();
        $("#div_precio_out").slideUp();
        $("#div_total_out").slideUp();
        $("#Veh_Cod_Out").val('');
        $("#Did_Pun_Out").val('0');
        calcularTotalOut();
    } else {
        $("#div_vehiculo_out").slideUp();
        $("#div_motivo_out").slideUp();
        $("#div_precio_out").slideDown();
        $("#div_total_out").slideDown();
        $("#Did_Obs_Out").val('');
        $("#Veh_Cod_Out").val('');
    }
}

function cargarInfoDispensadorOut(dis_cod) {
    if (dis_cod == "") {
        $("#infoDispensadorBoxOut").hide();
        $("#existencia_actual_out").val(0);
        return;
    }

    if (typeof $.carga === "function") { $.carga('show'); }

    $.ajax({
        url: 'man_alt_maquinaria_dispensador.php',
        type: 'POST',
        data: { getInfoDispensadorAjax: true, Dis_Cod: dis_cod },
        dataType: 'json',
        success: function(res) {
            if (typeof $.carga === "function") { $.carga('hide'); }
            if (res.success && res.data) {
                var d = res.data;
                var cap = parseFloat(d.Dis_Cap) || 0;
                var ext = parseFloat(d.existencia) || 0;
                
                var tip = d.Dis_Tip;
                if (tip == 'DI') tip = 'DIESEL';
                if (tip == 'SU') tip = 'SUPER';
                if (tip == 'EC') tip = 'ECO';
                if (tip == 'EX') tip = 'EXTRA';
                
                var uni = d.Dis_Uni;
                if (uni == 'GA') uni = 'GALONES';
                if (uni == 'LI') uni = 'LITROS';

                $("#lbl_Dis_Tip_Out").text(tip);
                $("#lbl_Dis_Uni_Out").text(uni);
                $("#lbl_Dis_Cap_Out").text(cap.toFixed(2));
                $("#lbl_Dis_Ext_Out").text(ext.toFixed(2));
                $("#existencia_actual_out").val(ext);
                
                calcularExistenciaPosterior();
                $("#infoDispensadorBoxOut").show();
            } else {
                $.alert("Error al obtener informacion del dispensador.");
                $("#infoDispensadorBoxOut").hide();
                $("#existencia_actual_out").val(0);
            }
        },
        error: function() {
            if (typeof $.carga === "function") { $.carga('hide'); }
            $.alert("Error de comunicacion al consultar dispensador.");
        }
    });
}

function calcularTotalOut() {
    var can = parseFloat($("#Did_Can_Out").val()) || 0;
    var pun = parseFloat($("#Did_Pun_Out").val()) || 0;
    var total = can * pun;
    $("#lbl_Total_Out").text(total.toFixed(2));
}

function calcularExistenciaPosterior() {
    calcularTotalOut();
    var extActual = parseFloat($("#existencia_actual_out").val()) || 0;
    var can = parseFloat($("#Did_Can_Out").val()) || 0;
    var extPost = extActual - can;
    
    var $lblExtPost = $("#lbl_Dis_ExtPost_Out");
    $lblExtPost.text(extPost.toFixed(2));
    
    if (extPost < 0) {
        $lblExtPost.removeClass('text-success').addClass('text-danger');
    } else {
        $lblExtPost.removeClass('text-danger').addClass('text-success');
    }
}

function guardarDespacho(btn) {
    var $btn = $(btn);

    var Dis_Cod = $("#Dis_Cod_Out").val();
    var Did_Tip = $("#Did_Tip_Out").val();
    var Veh_Cod = $("#Veh_Cod_Out").val();
    var Did_Obs = $.trim($("#Did_Obs_Out").val());
    var Did_Fec = $("#Did_Fec_Out").val();
    var Did_Can = parseFloat($("#Did_Can_Out").val());
    var Did_Pun = parseFloat($("#Did_Pun_Out").val());
    var extActual = parseFloat($("#existencia_actual_out").val());

    if (Did_Tip == "") { $.alert("Seleccione el tipo de salida."); return; }
    if (Did_Tip == "SA" && Veh_Cod == "") { $.alert("Seleccione una maquinaria/vehículo."); return; }
    if (Did_Tip == "SC" && Did_Obs == "") { $.alert("Debe ingresar el motivo del ajuste negativo."); return; }
    if (Dis_Cod == "") { $.alert("Seleccione un dispensador."); return; }
    if (Did_Fec == "") { $.alert("Ingrese la fecha."); return; }
    if (isNaN(Did_Can) || Did_Can <= 0) { $.alert("La cantidad debe ser mayor a cero."); return; }
    
    if (isNaN(Did_Pun) || Did_Pun < 0) { 
        Did_Pun = 0;
        $("#Did_Pun_Out").val(0);
    }
    
    var extPost = extActual - Did_Can;
    if (extPost < 0) {
        $.alert("No existe suficiente combustible disponible para realizar la salida. (Faltan " + Math.abs(extPost).toFixed(2) + " unid)");
        return;
    }

    var formData = $("#formDespacho").serialize();
    formData += '&saveDespachoAjax=true';

    if ($btn.prop('disabled')) { return; }
    $btn.prop('disabled', true).addClass('is-loading');
    if (typeof $.carga === "function") { $.carga('show'); }

    $.ajax({
        url: 'man_alt_maquinaria_dispensador.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function (res) {
            $btn.prop('disabled', false).removeClass('is-loading');
            if (typeof $.carga === "function") { $.carga('hide'); }
            
            if (res.success) {
                $("#modalFormularioDespacho").modal('hide');
                setTimeout(function() { $.alert(res.message || "Salida registrada correctamente."); }, 300);
                reloadGridDespachos();
            } else {
                $.alert("Error: " + res.message);
            }
        },
        error: function () {
            $btn.prop('disabled', false).removeClass('is-loading');
            if (typeof $.carga === "function") { $.carga('hide'); }
            $.alert("Error de comunicacion con el servidor. La transaccion fue reversada.");
        }
    });
}

function anularDespacho(didCod) {
    if (typeof $.createDialogConfirm === "function") {
        $.createDialogConfirm("Esta seguro de anular esta salida? El combustible regresara a la existencia del dispensador.", { didCod: didCod }, function(data) {
            ejecutarAnulacionDespacho(data.didCod);
        });
    } else {
        if (confirm("Esta seguro de anular esta salida? El combustible regresara a la existencia del dispensador.")) {
            ejecutarAnulacionDespacho(didCod);
        }
    }
}

function ejecutarAnulacionDespacho(didCod) {
    if (typeof $.carga === "function") { $.carga('show'); }
    $.ajax({
        url: 'man_alt_maquinaria_dispensador.php',
        type: 'POST',
        data: {
            changeEstadoDespachoAjax: true,
            Did_Cod: didCod
        },
        dataType: 'json',
        success: function (res) {
            if (typeof $.carga === "function") { $.carga('hide'); }
            if (res.success) {
                $.alert(res.message || "Salida anulada correctamente.");
                reloadGridDespachos();
            } else {
                $.alert("Error: " + res.message);
            }
        },
        error: function () {
            if (typeof $.carga === "function") { $.carga('hide'); }
            $.alert("Error de comunicacion con el servidor.");
        }
    });
}
// ======================================================================
// FASE 4A: AJUSTES DE INVENTARIO
// ======================================================================
function inicializarGridAjustes() {
    $('#gridAjustes').jqGrid({
        url: window.location.href + (window.location.href.indexOf('?') > -1 ? '&' : '?') + 'listAjustesGridAjax=true',
        mtype: 'GET',
        datatype: 'json',
        postData: {
            fec_ini: function () { return $('#filtro_fec_ini_aj').val(); },
            fec_fin: function () { return $('#filtro_fec_fin_aj').val(); },
            Dis_Cod: function () { return $('#filtro_Dis_Cod_Aj').val(); },
            Did_Tip: function () { return $('#filtro_Did_Tip_Aj').val(); }
        },
        colNames: ['ID', 'Fecha', 'Dispensador', 'Tipo Ajuste', 'Cantidad', 'Motivo', 'Usuario', 'Estado', 'Acciones'],
        colModel: [
            { name: 'Did_Cod', index: 'Did_Cod', hidden: true },
            { name: 'Did_Fec', index: 'Did_Fec', width: 120, align: 'center' },
            { name: 'Dis_Nom', index: 'Dis_Nom', width: 150 },
            { name: 'Did_Tip', index: 'Did_Tip', width: 150, formatter: function(cellvalue) {
                if (cellvalue == 'IC') return 'IC - Positivo';
                if (cellvalue == 'SC') return 'SC - Negativo';
                return cellvalue;
            }},
            { name: 'Did_Can', index: 'Did_Can', width: 100, align: 'right', formatter: 'number' },
            { name: 'Did_Obs', index: 'Did_Obs', width: 250 },
            { name: 'usuario_nombre', index: 'usuario_nombre', width: 150 },
            { name: 'Did_Est', index: 'Did_Est', width: 80, align: 'center', formatter: function(cellvalue) {
                return cellvalue == 'A' ? '<span class=\"label label-success\">Activo</span>' : '<span class=\"label label-danger\">Anulado</span>';
            }},
            { name: 'acciones', width: 80, align: 'center', sortable: false, search: false, formatter: function(cellvalue, options, rowObject) {
                if (rowObject.Did_Est == 'A') {
                    return '<button class=\"btn btn-xs btn-danger\" onclick=\"anularAjuste(' + rowObject.Did_Cod + ')\" title=\"Anular Ajuste\"><i class=\"glyphicon glyphicon-remove\"></i></button>';
                }
                return '';
            }}
        ],
        pager: '#pagerAjustes',
        rowNum: 50,
        rowList: [50, 100, 200, 500],
        sortname: 'Did_Cod',
        sortorder: 'desc',
        viewrecords: true,
        height: 350,
        autowidth: true,
        shrinkToFit: true,
        loadComplete: function () {
            if (typeof exaUiFitJqGrid === 'function' && $('#tab-ajustes').hasClass('active')) {
                exaUiFitJqGrid('#gridAjustes', '#tab-ajustes .exa-ui-grid-host');
            }
        }
    });
}

function reloadGridAjustes() {
    $('#gridAjustes').trigger('reloadGrid');
}

function limpiarFiltrosAjustes() {
    $('#formFiltrosAjustes')[0].reset();
    reloadGridAjustes();
}

function abrirModalAjuste() {
    $('#formAjuste')[0].reset();
    $('#infoDispensadorBoxAj').hide();
    $('#Did_Fec_Aj').val(new Date().toISOString().slice(0, 16));
    $('#modalFormularioAjuste').modal('show');
}

function cambiarTipoAjuste() {
    calcularExistenciaPosteriorAj();
}

function cargarInfoDispensadorAj(dis_cod) {
    if (!dis_cod) {
        $('#infoDispensadorBoxAj').hide();
        $('#capacidad_total_aj').val(0);
        $('#existencia_actual_aj').val(0);
        calcularExistenciaPosteriorAj();
        return;
    }
    
    if (typeof $.carga === 'function') { $.carga('show'); }
    $.ajax({
        url: window.location.href,
        type: 'GET',
        dataType: 'json',
        data: { getInfoDispensadorAjax: true, Dis_Cod: dis_cod },
        success: function (res) {
            if (typeof $.carga === 'function') { $.carga('hide'); }
            if (res.success && res.data) {
                $('#lbl_Dis_Tip_Aj').text(res.data.Dis_Tip);
                $('#lbl_Dis_Uni_Aj').text(res.data.Dis_Uni);
                $('#lbl_Dis_Cap_Aj').text(parseFloat(res.data.Dis_Cap).toFixed(2));
                $('#lbl_Dis_Ext_Aj').text(parseFloat(res.data.existencia).toFixed(2));
                
                $('#capacidad_total_aj').val(res.data.Dis_Cap);
                $('#existencia_actual_aj').val(res.data.existencia);
                
                $('#infoDispensadorBoxAj').show();
                calcularExistenciaPosteriorAj();
            } else {
                $.alert(res.message || 'Error al obtener datos del dispensador.');
                $('#infoDispensadorBoxAj').hide();
            }
        },
        error: function () {
            if (typeof $.carga === 'function') { $.carga('hide'); }
            $.alert('Error de conexión.');
        }
    });
}

function calcularExistenciaPosteriorAj() {
    var existenciaActual = parseFloat($('#existencia_actual_aj').val()) || 0;
    var cantidad = parseFloat($('#Did_Can_Aj').val()) || 0;
    var tipo = $('#Did_Tip_Aj').val();
    
    var existenciaPost = existenciaActual;
    
    if (tipo === 'IC') { // Positivo
        existenciaPost = existenciaActual + cantidad;
    } else if (tipo === 'SC') { // Negativo
        existenciaPost = existenciaActual - cantidad;
    }
    
    $('#lbl_Dis_ExtPost_Aj').text(existenciaPost.toFixed(2));
    
    var capacidad = parseFloat($('#capacidad_total_aj').val()) || 0;
    
    if (tipo === 'IC' && existenciaPost > capacidad) {
        $('#lbl_Dis_ExtPost_Aj').removeClass('text-success').addClass('text-danger');
    } else if (tipo === 'SC' && existenciaPost < 0) {
        $('#lbl_Dis_ExtPost_Aj').removeClass('text-success').addClass('text-danger');
    } else {
        $('#lbl_Dis_ExtPost_Aj').removeClass('text-danger').addClass('text-success');
    }
}

function guardarAjuste(btn) {
    var $btn = $(btn);
    var Dis_Cod = $('#Dis_Cod_Aj').val();
    var Did_Tip = $('#Did_Tip_Aj').val();
    var Did_Fec = $('#Did_Fec_Aj').val();
    var Did_Can = parseFloat($('#Did_Can_Aj').val());
    var Did_Obs = $.trim($('#Did_Obs_Aj').val());

    if (!Dis_Cod || !Did_Tip || !Did_Fec || isNaN(Did_Can) || Did_Can <= 0 || !Did_Obs) {
        $.alert('Por favor, complete todos los campos obligatorios (*) y asegúrese de que la cantidad sea mayor a 0. El motivo es indispensable.');
        return;
    }

    var existencia = parseFloat($('#existencia_actual_aj').val()) || 0;
    var capacidad = parseFloat($('#capacidad_total_aj').val()) || 0;

    if (Did_Tip === 'IC' && (existencia + Did_Can) > capacidad) {
        $.alert('Error: El ajuste positivo excede la capacidad del tanque.');
        return;
    }

    if (Did_Tip === 'SC' && (existencia - Did_Can) < 0) {
        $.alert('Error: El ajuste negativo supera la existencia actual. No puede quedar saldo negativo.');
        return;
    }

    if ($btn.prop('disabled')) { return; }
    $btn.prop('disabled', true).addClass('is-loading');
    
    if (typeof $.carga === 'function') { $.carga('show'); }

    $.ajax({
        url: window.location.href,
        type: 'POST',
        dataType: 'json',
        data: {
            saveAjusteAjax: true,
            Dis_Cod: Dis_Cod,
            Did_Tip: Did_Tip,
            Did_Fec: Did_Fec,
            Did_Can: Did_Can,
            Did_Obs: Did_Obs
        },
        success: function (res) {
            $btn.prop('disabled', false).removeClass('is-loading');
            if (typeof $.carga === 'function') { $.carga('hide'); }
            
            if (res.success) {
                $('#modalFormularioAjuste').modal('hide');
                $.alert(res.message || 'Ajuste guardado.');
                reloadGridAjustes();
            } else {
                $.alert('Error: ' + res.message);
            }
        },
        error: function () {
            $btn.prop('disabled', false).removeClass('is-loading');
            if (typeof $.carga === 'function') { $.carga('hide'); }
            $.alert('Error de conexión.');
        }
    });
}

function anularAjuste(didCod) {
    if (confirm('¿Está seguro de anular este ajuste? La existencia será recalculada.')) {
        if (typeof $.carga === 'function') { $.carga('show'); }
        $.ajax({
            url: window.location.href,
            type: 'POST',
            dataType: 'json',
            data: { changeEstadoAjusteAjax: true, Did_Cod: didCod },
            success: function (res) {
                if (typeof $.carga === 'function') { $.carga('hide'); }
                if (res.success) {
                    $.alert(res.message);
                    reloadGridAjustes();
                } else {
                    $.alert('Error: ' + res.message);
                }
            },
            error: function () {
                if (typeof $.carga === 'function') { $.carga('hide'); }
                $.alert('Error de conexión.');
            }
        });
    }
}

// ======================================================================
// FASE 4B: KARDEX DE COMBUSTIBLE
// ======================================================================
function inicializarGridKardex() {
    $('#gridKardex').jqGrid({
        url: window.location.href + (window.location.href.indexOf('?') > -1 ? '&' : '?') + 'listKardexAjax=true',
        mtype: 'GET',
        datatype: 'local', // Inicia local para no cargar automáticamente al inicio
        postData: {
            fec_ini: function () { return $('#filtro_fec_ini_kx').val(); },
            fec_fin: function () { return $('#filtro_fec_fin_kx').val(); },
            Dis_Cod: function () { return $('#filtro_Dis_Cod_Kx').val(); },
            Did_Tip: function () { return $('#filtro_Did_Tip_Kx').val(); }
        },
        colNames: ['Fecha', 'Dispensador', 'Tipo Movimiento', 'Responsable / Origen / Destino', 'Entrada', 'Salida', 'Precio Unit.', 'Total Ref.', 'Saldo', 'Usuario Reg.', 'Estado'],
        colModel: [
            { name: 'Did_Fec', index: 'Did_Fec', width: 130, align: 'center' },
            { name: 'Dis_Nom', index: 'Dis_Nom', width: 150 },
            { name: 'Did_Tip', index: 'Did_Tip', width: 100, align: 'center', formatter: function(cellvalue) {
                if (cellvalue == 'IN') return '<span class=\"label label-success\">IN - Compra</span>';
                if (cellvalue == 'IC') return '<span class=\"label label-warning\">IC - Ajuste (+)</span>';
                if (cellvalue == 'SA') return '<span class=\"label label-danger\">SA - Salida</span>';
                if (cellvalue == 'SC') return '<span class=\"label label-warning\">SC - Ajuste (-)</span>';
                return cellvalue;
            }},
            { name: 'responsable', index: 'responsable', width: 220 },
            { name: 'entrada', index: 'entrada', width: 80, align: 'right', formatter: 'number' },
            { name: 'salida', index: 'salida', width: 80, align: 'right', formatter: 'number' },
            { name: 'Did_Pun', index: 'Did_Pun', width: 80, align: 'right', formatter: 'currency' },
            { name: 'total_ref', index: 'total_ref', width: 90, align: 'right', formatter: 'currency' },
            { name: 'saldo', index: 'saldo', width: 90, align: 'right', formatter: 'number', classes: 'font-bold text-primary' },
            { name: 'usuario_nombre', index: 'usuario_nombre', width: 150 },
            { name: 'Did_Est', index: 'Did_Est', width: 80, align: 'center', formatter: function(cellvalue) {
                return cellvalue == 'A' ? 'Activo' : 'Anulado';
            }}
        ],
        pager: '#pagerKardex',
        rowNum: 999999, // Mostrar todos
        pgbuttons: false,
        pgtext: null,
        viewrecords: true,
        height: 400,
        autowidth: true,
        shrinkToFit: true,
        loadComplete: function (data) {
            if (data && data.userdata && typeof data.userdata.sum_entradas !== 'undefined') {
                $('#lbl_Kx_In').text(parseFloat(data.userdata.sum_entradas).toFixed(2));
                $('#lbl_Kx_Out').text(parseFloat(data.userdata.sum_salidas).toFixed(2));
                $('#lbl_Kx_Mov').text(data.userdata.count_mov);
                $('#lbl_Kx_Saldo').text(parseFloat(data.userdata.saldo_final).toFixed(2));
            }
            if (typeof exaUiFitJqGrid === 'function' && $('#tab-kardex').hasClass('active')) {
                exaUiFitJqGrid('#gridKardex', '#tab-kardex .exa-ui-grid-host');
            }
        }
    });
}

function consultarKardex() {
    var Dis_Cod = $('#filtro_Dis_Cod_Kx').val();
    if (!Dis_Cod) {
        $.alert('Por favor, seleccione un dispensador para poder calcular el Kardex correctamente.');
        return;
    }

    // Cambiar datatype a json para que jqGrid dispare el AJAX nativamente con su propio loader
    $('#gridKardex').jqGrid('setGridParam', { datatype: 'json' }).trigger('reloadGrid');
}

function imprimirKardex() {
    var Dis_Cod = $('#filtro_Dis_Cod_Kx').val();
    if (!Dis_Cod) {
        $.alert('Debe consultar primero el Kardex para poder imprimir.');
        return;
    }
    
    var printContents = document.getElementById('divKardexPrint').innerHTML;
    var originalContents = document.body.innerHTML;
    
    var fec_ini = $('#filtro_fec_ini_kx').val();
    var fec_fin = $('#filtro_fec_fin_kx').val();
    var dis_nom = $('#filtro_Dis_Cod_Kx option:selected').text();
    var now = new Date().toLocaleString();

    var header = "<div style=\"text-align:center; margin-bottom: 20px;\"><h2>CONTROL DE COMBUSTIBLE</h2><h3>KARDEX DE DISPENSADOR</h3><p><strong>Período:</strong> " + fec_ini + " a " + fec_fin + "</p><p><strong>Dispensador:</strong> " + dis_nom + "</p><p><strong>Fecha generación:</strong> " + now + "</p></div>";

    document.body.innerHTML = header + printContents;
    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload(); // Recargar para restaurar eventos JS
}
// ======================================================================
// FASE 5: CIERRE DIARIO DE COMBUSTIBLE
// ======================================================================
function inicializarGridCierres() {
    $('#gridCierre').jqGrid({
        url: window.location.href + (window.location.href.indexOf('?') > -1 ? '&' : '?') + 'listCierresAjax=true',
        mtype: 'GET',
        datatype: 'json',
        postData: {
            fec_ini: function () { return $('#filtro_fec_ini_cie').val(); },
            fec_fin: function () { return $('#filtro_fec_fin_cie').val(); },
            Dis_Cod: function () { return $('#filtro_Dis_Cod_Cie').val(); },
            Cie_Estado: function () { return $('#filtro_Cie_Estado').val(); }
        },
        colNames: ['ID', 'Fecha', 'Dispensador', 'Inicial', 'Ingresos', 'Salidas', 'Teórico', 'Físico', 'Diferencia', 'Estado', 'Usuario', 'Acciones'],
        colModel: [
            { name: 'Cie_Cod', index: 'Cie_Cod', hidden: true },
            { name: 'Cie_Fec', index: 'Cie_Fec', width: 100, align: 'center' },
            { name: 'Dis_Nom', index: 'Dis_Nom', width: 150 },
            { name: 'Cie_Ini', index: 'Cie_Ini', width: 80, align: 'right', formatter: 'number' },
            { name: 'Cie_Ing', index: 'Cie_Ing', width: 80, align: 'right', formatter: 'number' },
            { name: 'Cie_Sal', index: 'Cie_Sal', width: 80, align: 'right', formatter: 'number' },
            { name: 'Cie_Teo', index: 'Cie_Teo', width: 80, align: 'right', formatter: 'number', classes: 'font-bold text-info' },
            { name: 'Cie_Fis', index: 'Cie_Fis', width: 80, align: 'right', formatter: 'number', classes: 'font-bold' },
            { name: 'Cie_Dif', index: 'Cie_Dif', width: 80, align: 'right', formatter: 'number' },
            { name: 'Cie_Estado', index: 'Cie_Estado', width: 100, align: 'center', formatter: function(cellvalue, options, rowObject) {
                if (rowObject.Cie_Est != 'A') return '<span class=\"label label-default\">Anulado</span>';
                if (cellvalue == 'CUADRADO') return '<span class=\"label label-success\">CUADRADO</span>';
                if (cellvalue == 'SOBRANTE') return '<span class=\"label label-warning\">SOBRANTE</span>';
                if (cellvalue == 'DESCUADRADO') return '<span class=\"label label-danger\">DESCUADRADO</span>';
                return cellvalue;
            }},
            { name: 'usuario_nombre', index: 'usuario_nombre', width: 150 },
            { name: 'acciones', width: 80, align: 'center', sortable: false, search: false, formatter: function(cellvalue, options, rowObject) {
                if (rowObject.Cie_Est == 'A') {
                    return '<button class=\"btn btn-xs btn-danger\" onclick=\"anularCierre(' + rowObject.Cie_Cod + ')\" title=\"Anular Cierre\"><i class=\"glyphicon glyphicon-remove\"></i></button>';
                }
                return '';
            }}
        ],
        pager: '#pagerCierre',
        rowNum: 50,
        rowList: [50, 100, 200],
        sortname: 'Cie_Fec',
        sortorder: 'desc',
        viewrecords: true,
        height: 350,
        autowidth: true,
        shrinkToFit: true,
        loadComplete: function () {
            if (typeof exaUiFitJqGrid === 'function' && $('#tab-cierre').hasClass('active')) {
                exaUiFitJqGrid('#gridCierre', '#tab-cierre .exa-ui-grid-host');
            }
        }
    });
}

function reloadGridCierres() {
    $('#gridCierre').trigger('reloadGrid');
}

function limpiarFiltrosCierres() {
    $('#formFiltrosCierre')[0].reset();
    reloadGridCierres();
}

function abrirModalCierre() {
    $('#formCierre')[0].reset();
    $('#boxCalculoTeorico').hide();
    $('#btnGuardarCierre').prop('disabled', true);
    
    // Resetear labels
    $('#lbl_cie_ini, #lbl_cie_ing, #lbl_cie_sal, #lbl_cie_teo').text('0.00');
    $('#lbl_cie_estado').text('-').css({'background-color': '#d2d6de', 'color': '#000'});
    
    $('#modalFormularioCierre').modal('show');
}

function cargarCalculoPrevioCierre() {
    var dis_cod = $('#Cie_Dis_Cod').val();
    var fec = $('#Cie_Fec').val();
    
    if (!dis_cod || !fec) {
        $('#boxCalculoTeorico').hide();
        $('#btnGuardarCierre').prop('disabled', true);
        return;
    }
    
    if (typeof $.carga === 'function') { $.carga('show'); }
    $.ajax({
        url: window.location.href,
        type: 'GET',
        dataType: 'json',
        data: { getCalculoPrevioCierreAjax: true, Dis_Cod: dis_cod, Cie_Fec: fec },
        success: function (res) {
            if (typeof $.carga === 'function') { $.carga('hide'); }
            if (res.success && res.data) {
                if (parseInt(res.data.existe_cierre) > 0) {
                    $.alert('Ya existe un cierre registrado para este dispensador en la fecha seleccionada.');
                    $('#boxCalculoTeorico').hide();
                    $('#btnGuardarCierre').prop('disabled', true);
                    return;
                }
                
                var cie_ini = parseFloat(res.data.cie_ini) || 0;
                var cie_ing = parseFloat(res.data.cie_ing) || 0;
                var cie_sal = parseFloat(res.data.cie_sal) || 0;
                var cie_teo = cie_ini + cie_ing - cie_sal;
                
                $('#cie_ini_val').val(cie_ini);
                $('#cie_ing_val').val(cie_ing);
                $('#cie_sal_val').val(cie_sal);
                $('#cie_teo_val').val(cie_teo);
                
                $('#lbl_cie_ini').text(cie_ini.toFixed(2));
                $('#lbl_cie_ing').text(cie_ing.toFixed(2));
                $('#lbl_cie_sal').text(cie_sal.toFixed(2));
                $('#lbl_cie_teo').text(cie_teo.toFixed(2));
                
                $('#Cie_Fis').val(cie_teo.toFixed(2));
                
                $('#boxCalculoTeorico').show();
                calcularDiferenciaCierre();
            } else {
                $.alert(res.message || 'Error al obtener cálculo.');
                $('#boxCalculoTeorico').hide();
                $('#btnGuardarCierre').prop('disabled', true);
            }
        },
        error: function () {
            if (typeof $.carga === 'function') { $.carga('hide'); }
            $.alert('Error de conexión.');
        }
    });
}

function calcularDiferenciaCierre() {
    var cie_teo = parseFloat($('#cie_teo_val').val()) || 0;
    var cie_fis_str = $('#Cie_Fis').val();
    
    if (cie_fis_str === '') {
        $('#Cie_Dif').val('');
        $('#lbl_cie_estado').text('-').css({'background-color': '#d2d6de', 'color': '#000'});
        $('#cie_estado_val').val('');
        $('#btnGuardarCierre').prop('disabled', true);
        return;
    }
    
    var cie_fis = parseFloat(cie_fis_str);
    var dif = cie_fis - cie_teo;
    
    $('#cie_dif_val').val(dif);
    $('#Cie_Dif').val(dif.toFixed(2));
    
    var estado = '';
    $('#lbl_cie_estado').removeClass();
    if (dif === 0) {
        estado = 'CUADRADO';
        $('#lbl_cie_estado').text('CUADRADO').css({'background-color': '#00a65a', 'color': '#fff'});
    } else if (dif > 0) {
        estado = 'SOBRANTE';
        $('#lbl_cie_estado').text('SOBRANTE (+)').css({'background-color': '#ff851b', 'color': '#fff'});
    } else {
        estado = 'DESCUADRADO';
        $('#lbl_cie_estado').text('DESCUADRADO (-)').css({'background-color': '#dd4b39', 'color': '#fff'});
    }
    $('#cie_estado_val').val(estado);
    $('#btnGuardarCierre').prop('disabled', false);
}

function guardarCierre(form) {
    var dis_cod = $('#Cie_Dis_Cod').val();
    var fec = $('#Cie_Fec').val();
    var fis_str = $('#Cie_Fis').val();
    var estado = $('#cie_estado_val').val();
    
    if (!dis_cod || !fec || fis_str === '' || !estado) {
        $.alert('Debe completar todos los datos requeridos.');
        return;
    }
    
    var $btn = $('#btnGuardarCierre');
    if ($btn.prop('disabled')) { return; }
    $btn.prop('disabled', true).addClass('is-loading');
    
    if (typeof $.carga === 'function') { $.carga('show'); }
    
    $.ajax({
        url: window.location.href,
        type: 'POST',
        dataType: 'json',
        data: {
            saveCierreAjax: true,
            Cie_Fec: fec,
            Cie_Dis_Cod: dis_cod,
            Cie_Ini: $('#cie_ini_val').val(),
            Cie_Ing: $('#cie_ing_val').val(),
            Cie_Sal: $('#cie_sal_val').val(),
            Cie_Teo: $('#cie_teo_val').val(),
            Cie_Fis: $('#Cie_Fis').val(),
            Cie_Dif: $('#cie_dif_val').val(),
            Cie_Estado: estado,
            Cie_Obs: $.trim($('#Cie_Obs').val())
        },
        success: function (res) {
            $btn.prop('disabled', false).removeClass('is-loading');
            if (typeof $.carga === 'function') { $.carga('hide'); }
            
            if (res.success) {
                $('#modalFormularioCierre').modal('hide');
                $.alert(res.message);
                reloadGridCierres();
            } else {
                $.alert('Error: ' + res.message);
            }
        },
        error: function () {
            $btn.prop('disabled', false).removeClass('is-loading');
            if (typeof $.carga === 'function') { $.carga('hide'); }
            $.alert('Error de conexión.');
        }
    });
}

function anularCierre(cieCod) {
    if (confirm('¿Está seguro de anular este Cierre Diario?')) {
        if (typeof $.carga === 'function') { $.carga('show'); }
        $.ajax({
            url: window.location.href,
            type: 'POST',
            dataType: 'json',
            data: { changeEstadoCierreAjax: true, Cie_Cod: cieCod },
            success: function (res) {
                if (typeof $.carga === 'function') { $.carga('hide'); }
                if (res.success) {
                    $.alert(res.message);
                    reloadGridCierres();
                } else {
                    $.alert('Error: ' + res.message);
                }
            },
            error: function () {
                if (typeof $.carga === 'function') { $.carga('hide'); }
                $.alert('Error de conexión.');
            }
        });
    }
}


// ==========================================
// DASHBOARD EJECUTIVO (FASE 6)
// ==========================================

var chartConsumo = null;

function loadDashboard() {
    var ini = $('#dash_fec_ini').val();
    var fin = $('#dash_fec_fin').val();
    var dis = $('#dash_dis_cod').val();
    
    if (!ini || !fin) {
        $.alert('Debe seleccionar fecha de inicio y fin.');
        return;
    }
    
    $.ajax({
        url: window.location.href,
        type: 'POST',
        dataType: 'json',
        data: {
            getDashboardAjax: true,
            fecha_ini: ini,
            fecha_fin: fin,
            dis_cod: dis
        },
        success: function(res) {
            if (res.success) {
                // 1. Resumen General
                $('#dash_gen_disp').text(res.general.total_dispensadores || 0);
                $('#dash_gen_ext').text(parseFloat(res.general.existencia_total || 0).toFixed(2));
                $('#dash_gen_cierre').text(res.general.ultimo_cierre ? res.general.ultimo_cierre : 'N/A');
                
                $('#dash_gen_ing').text(parseFloat(res.movimientos.ingresos_mes || 0).toFixed(2));
                $('#dash_gen_sal').text(parseFloat(res.movimientos.despachos_mes || 0).toFixed(2));
                $('#dash_gen_condia').text(parseFloat(res.movimientos.consumo_dia || 0).toFixed(2));
                
                // 3. Movimientos del DÃ­a
                $('#dash_dia_in').text(parseFloat(res.movimientos.in_dia || 0).toFixed(2));
                $('#dash_dia_ic').text(parseFloat(res.movimientos.ic_dia || 0).toFixed(2));
                $('#dash_dia_sa').text(parseFloat(res.movimientos.consumo_dia || 0).toFixed(2));
                $('#dash_dia_sc').text(parseFloat(res.movimientos.sc_dia || 0).toFixed(2));
                var tot_dia = parseFloat(res.movimientos.in_dia||0) + parseFloat(res.movimientos.ic_dia||0) + parseFloat(res.movimientos.consumo_dia||0) + parseFloat(res.movimientos.sc_dia||0);
                $('#dash_dia_total').text(tot_dia.toFixed(2));
                
                // 4. Cierres
                $('#dash_cierre_fecha').text(res.cierre.Cie_Fec);
                $('#dash_cierre_estado').text(res.cierre.Cie_Estado);
                $('#dash_cierre_dif').text(parseFloat(res.cierre.Cie_Dif || 0).toFixed(2));
                $('#dash_cierre_estado').removeClass('text-success text-warning text-danger');
                if (res.cierre.Cie_Estado == 'CUADRADO') $('#dash_cierre_estado').addClass('text-success');
                else if (res.cierre.Cie_Estado == 'SOBRANTE') $('#dash_cierre_estado').addClass('text-warning');
                else $('#dash_cierre_estado').addClass('text-danger');
                
                // 2. Estado Dispensadores
                var dispHtml = '';
                if (res.dispensadores.length > 0) {
                    $.each(res.dispensadores, function(i, d) {
                        var pct = parseFloat(d.pct_usado || 0);
                        var bg = 'bg-red';
                        if (pct > 50) bg = 'bg-green';
                        else if (pct >= 20) bg = 'bg-yellow';
                        
                        var disp = d.Dis_Cap - d.existencia;
                        var pct_disp = 100 - pct;
                        
                        dispHtml += '<div class="col-md-6 col-lg-4">' +
                                    '<div class="box box-solid">' +
                                    '<div class="box-header with-border">' +
                                    '<h3 class="box-title">' + d.Dis_Nom + ' <small>(' + d.Dis_Com + ')</small></h3>' +
                                    '</div>' +
                                    '<div class="box-body text-center">' +
                                    '<h4>Existencia: <strong>' + parseFloat(d.existencia).toFixed(2) + '</strong> / ' + parseFloat(d.Dis_Cap).toFixed(2) + ' ' + d.Dis_Uni + '</h4>' +
                                    '<div class="progress" style="height:20px; border-radius:10px;">' +
                                    '<div class="progress-bar ' + bg + '" style="width: ' + pct + '%; line-height:20px;">' + pct + '% Usado</div>' +
                                    '</div>' +
                                    '<p class="text-muted">Disponible: ' + parseFloat(disp).toFixed(2) + ' ' + d.Dis_Uni + ' (' + pct_disp.toFixed(2) + '%)</p>' +
                                    '</div>' +
                                    '</div>' +
                                    '</div>';
                    });
                } else {
                    dispHtml = '<div class="col-md-12"><p class="text-center text-muted">No hay dispensadores activos.</p></div>';
                }
                $('#dash_dispensadores_container').html(dispHtml);
                
                // 5. Top Maquinarias
                var topHtml = '';
                if (res.top_maq.length > 0) {
                    $.each(res.top_maq, function(i, m) {
                        topHtml += '<tr>' +
                                   '<td>' + (i+1) + '</td>' +
                                   '<td>' + m.vehiculo_nombre + '</td>' +
                                   '<td class="text-right"><strong>' + parseFloat(m.consumo).toFixed(2) + '</strong></td>' +
                                   '<td>' + m.Usu_Ape + ' ' + m.Usu_Nom + '</td>' +
                                   '</tr>';
                    });
                } else {
                    topHtml = '<tr><td colspan="4" class="text-center">No existen datos para el perÃ­odo seleccionado.</td></tr>';
                }
                $('#dash_top_maq_body').html(topHtml);
                
                // 6. Alertas
                var alertHtml = '';
                if (res.alertas.length > 0) {
                    $.each(res.alertas, function(i, a) {
                        alertHtml += '<li>' + a + '</li>';
                    });
                    $('#dash_alertas_list').html(alertHtml);
                    $('#dash_alertas_container').show();
                } else {
                    $('#dash_alertas_container').hide();
                }
                
                // 7. GrÃ¡fico
                renderChart(res.grafico);
                
            } else {
                $.alert('Error al cargar dashboard');
            }
        },
        error: function() {
            $.alert('Error de conexiÃ³n al cargar Dashboard');
        }
    });
}

function renderChart(datos) {
    if (typeof Chart === 'undefined') {
        console.log('Chart.js no estÃ¡ disponible.');
        return;
    }
    
    var labels = [];
    var data = [];
    
    if (datos.length > 0) {
        $.each(datos, function(i, val) {
            labels.push(val.fecha);
            data.push(parseFloat(val.consumo));
        });
    }
    
    var ctx = document.getElementById('chartConsumoDiario').getContext('2d');
    
    if (chartConsumo) {
        chartConsumo.destroy();
    }
    
    chartConsumo = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Consumo (SA)',
                data: data,
                backgroundColor: 'rgba(0, 166, 90, 0.7)',
                borderColor: 'rgba(0, 166, 90, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                yAxes: [{
                    ticks: { beginAtZero: true }
                }]
            }
        }
    });
}

// Cargar dashboard automÃ¡ticamente al entrar a la pestaÃ±a si no se ha cargado
$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
    var target = $(e.target).attr("href");
    if (target === '#tab-dashboard' && $('#dash_gen_disp').text() === '0') {
        loadDashboard();
    }
});
