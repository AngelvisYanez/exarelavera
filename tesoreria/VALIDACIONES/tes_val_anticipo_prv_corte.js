var grid = $('#searchGrid');

// Variable para controlar si se está en modo "verAntic"
var isVerAntic = false;

// Inicialización dinámica de gridPagosAsi según el modo
var gridPagosAsi = isVerAntic ? $('#viewPagosAsi') : $('#showPagosAsi');
var gridPagosChe = isVerAntic ? $('#viewPagosChe') : $('#showPagosChe');
var subGridAsi = isVerAntic ? $('#viewSubGridAsi') : $('#showSubGridAsi');
var containerNegoci = $("#containerNegoci");

var arrayAsiento = [],
    arrayCheques = [],
    arrayModAsiento = [],
    arrayCuentasPlan = [],
    arrayDetAsiento = [];

var perCodAct = 0,
    existeCheq = false;
$(function () {
    $("#successDialog").createDialog({ width: 500, height: 200, icon: 'print' });
    $("#verPagosDialog").createDialog({ width: 400, height: 290, icon: 'info-sign' });
    $("#viewPagosDialog").createDialog({ width: 400, height: 290, icon: 'info-sign' }); //nueva
    $("#negDialog").createDialog({ width: 300, height: 290, icon: 'info-sign' }); //nueva
    
    $("#verPagosDialogMod").createDialog({ width: 700, height: 450, icon: 'info-sign' });
    $("#ModPagosDialogCam").createDialog({ width: 700, height: 450, icon: 'eye-open' }); //nueva
    $("#verAsientoDialogMod").createDialog({ width: 700, height: 350, icon: 'info-data' });
    $("#viewAsientoDialogMod").createDialog({ width: 700, height: 350, icon: 'info-data' }); //nueva

    $('#pagosDialog').createDialog({ height: 325, icon: 'usd' });

    $("#tabs_ant_det").tabs();
    $("#tabs_ant_view").tabs(); //nueva
    $('#tabs_sub_ant_det').tabs();
    $('#tabs_sub_ant_view').tabs(); //nueva

    $('.datepicker').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });
    $('.pagination').find('li a').click(function () {
        $('.pagination').find('li').removeClass('active');
        $(this).parent().addClass('active');
        $('#letra').val($(this).text());
        busquedaAjax();
    });

    /* Metodos */
    changePeriodo();
    createGrid();
    crearGridShowPagosAsi();
    crearGridshowPagosChe();
    createPagosModGrid();
    createGridShowAsiDetalle();
    changeCuentaCod();
});

function createGrid() {
    grid.createGrid({
        caption: 'Anticipos', stateCol: 'Atp_Est',
        height: '450',
        colModel: [
            { label: 'Cod. Int.', name: 'Atp_Cod', key: true, width: 25, align: "left" },
            { label: 'No. Compr.', name: 'codigoCompra', width: 30, align: "left" },
            { label: 'Fecha Ant.', name: 'Atp_Fec', width: 30, align: "left" },
            { label: ' ', name: 'Prs_Cod', hidden: true },
            { label: ' ', name: 'Pap_Cod', hidden: true },
            { label: ' ', name: 'Prv_Cod', hidden: true },  
            { label: ' ', name: 'Com_Cod', hidden: true },
            { label: '', name: 'Atp_Est', hidden: true },
            { label: '', name: 'Pag_Des', hidden: true },
            { label: 'C&eacute;dula', name: 'cedProv', width: 40, align: "left", cellattr: function () { return 'style="' + excelFormats.text + '"'; } },
            { label: 'Proveedor', name: 'nombre', width: 100, align: "left" },
            { label: 'Direci&oacute;n', name: 'Prs_Dir', hidden: true, width: 100, align: "left" },
            { label: 'Valor', name: 'sumaAtpVal', width: 60, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } },
            { label: 'Pagos', name: 'sumaDacVal', width: 60, align: 'right', formatter: function (cellvalue, options, rowObject) { if (rowObject['sumaDacVal'] === '' || rowObject['sumaDacVal'] === null) { return "0.00"; } else { return formatMoney(rowObject['sumaDacVal']); } } },
            { label: 'Saldo', name: 'tot_anti', width: 60, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' }, summaryType: "sum" },
            { label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'btns_anti', width: 40, align: 'center', viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    var parm_anu = [rowObject];
                    var parm_getdet = [rowObject];
                    //console.log(rowObject);
                    if (rowObject.Atp_Est === "I") {
                        return $.createIcon('remove red');
                    } else if (rowObject.Atp_Est !== "A" || rowObject.Pag_Des == "Anticipos") {
                        return $.getGridButton(verAnticipo, parm_getdet, 'ver anticipo', 'info-sign', '', 'info');
                    } else {
                        //console.log(prf[0]['Per_Des']);
                        if (prf[0]['Per_Des'] === 'Administrador de Sistemas') {
                            return $.getGridButton(verAnticipo, parm_getdet, 'ver anticipo', 'info-sign', '', 'info');
                            // + "&nbsp;" +
                            //     $.getGridButton(modificarAnticipo, parm_getdet, 'Modificar anticipo', 'pencil', '', 'success') + "&nbsp;" +
                            //     $.getGridButton(preanularAnticipo, parm_anu, 'Anular anticipo', 'remove', '', 'danger');
                        } else {
                            return $.getGridButton(verAnticipo, parm_getdet, 'ver anticipo', 'info-sign', '', 'info');
                            // + "&nbsp;" +
                            //     $.getGridButton(modificarAnticipo, parm_getdet, 'Modificar anticipo', 'pencil', '', 'success') + "&nbsp;" +
                            //     $.getGridButton(preanularAnticipo, parm_anu, 'Anular anticipo', 'remove', '', 'danger');
                        }
                    }
                }
            }
        ],
        footerrow: true,
        userDataOnFooter: true,
        subGrid: true,
        rowNum: 10000,
        gridview: true,
        viewrecords: true,
        subGridOptions: {
            "plusicon": "ui-icon-triangle-1-e",
            "minusicon": "ui-icon-triangle-1-s",
            "openicon": "ui-icon-arrowreturn-1-e",
            "reloadOnExpand": false,
            "selectOnExpand": true
        },
        subGridRowExpanded: function (subgrid_id, row_id) {
            //console.log(subgrid_id);
            //console.log(row_id);
            let subgrid_table_id = subgrid_id + "_t";
            let rowData = $("#searchGrid").getRowData(row_id);
            $("#" + subgrid_id).html("<table id='" + subgrid_table_id + "' class='scroll'></table>");
            $("#" + subgrid_table_id).createGrid({
                url: "?movAnticipo=" + rowData['Atp_Cod'] + "&Pec_Cod=" + $("#Pec_Cod").val() + "&txt_fec_ini=" + $("#txt_fec_ini").val() + "&txt_fec_fin=" + $("#txt_fec_fin").val(),
                datatype: "json",
                regional: 'es',
                height: 'auto',
                responsive: true,
                colModel: [
                    { label: '', name: 'Atp_Cod', width: 15, key: true, hidden: true },
                    { label: 'Cod. Compr.', name: 'Com_Cod', width: 20, align: "center", hidden: false },
                    { label: 'Est.', name: 'Atp_Est', width: 10, align: 'center', hidden: false },
                    { label: '', name: 'Tia_Cod', hidden: true },
                    { label: '', name: 'Com_Num', hidden: true },
                    { label: 'No. Compr.', name: 'codigoCompra', width: 30, align: "center" },
                    { label: 'Fecha Com.', name: 'Com_Fec', width: 20, align: "center" },
                    { label: 'Observaci&oacute;n', name: 'Atp_Obs', width: 90, align: "left" },
                    { label: 'Concepto', name: 'Com_Con', width: 50, align: "left" },
                    { label: 'Valor', name: 'sumaDacVal', width: 50, align: 'right', formatter: function (cellvalue, options, rowObject) { return formatMoney(rowObject['Dac_Val']); } },
                    { label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'btns_sub_anti', width: 40, align: 'center', viewable: false,
                        formatter: function (cellvalue, options, rowObject) {
                            var parm_getdet = [rowObject];
                            return $.getGridButton(verMovimiento, parm_getdet, 'ver asiento', 'info-sign', '', 'info') + "&nbsp;" +
                                $.getGridButton(imprimirAsiento, parm_getdet, 'Imprimir', 'print', '', 'success');
                        }
                    }
                ]
            });
        },
        loadComplete: function (data) {
            calculateValFooter();
            cellColors();
        }
    }, true, '#searchGridPager', { 
        refresh: true, view: false 
    }).gridButtonsAdd([
        { caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download',
            onClickButton: function () {
                grid.jqGrid('exportGridExcel', {
                    nombre: 'Ant-Prov', 
                    hoja: 'HOJA 1',
                    footer: true
                });
            }
        },
        { caption: 'Exportar PDF', buttonicon: 'glyphicon glyphicon-download',
            onClickButton: function () {
                imprimir();
            }
        },
        { caption: 'Expandir Todos', buttonicon: 'glyphicon glyphicon-resize-full',
            onClickButton: function () {
                ExpandirAll();
            }
        },
        { caption: 'Contraer Todos', buttonicon: 'glyphicon glyphicon-resize-small',
            onClickButton: function () {
                ContraerAll();
            }, hidden:true 
        }
    ]);

    function ExpandirAll(){
        let ids = grid.getDataIDs();
        for (let i = 0; i < ids.length; i++) {
            grid.expandSubGridRow(ids[i]);
        }
    }
    
    function ContraerAll(){
        let ids = grid.getDataIDs();
        for (let i = 0; i < ids.length; i++) {
            grid.collapseSubGridRow(ids[i]);
        }
    }
    
    function imprimir() {
        $('#tablaReporte').html(grid.jqGrid('exportGridInnerHTML', {
            footer: true,
            generated: false,
            removeHiddens: true,
            removeCols: [1, 10]
        }));
        $('#imprimir').printElement();
    }
}

function verificaChequeEstado() {
    let ids = verficaDataInGrid();
    if ($.varValid(ids)) {
        for (let j = 0, z = ids.length; j < z; j++) {
            let getRowData = $('#pagos').jqGrid('getRowData', ids[j]);
            if (getRowData['grid_tipp'] !== 'inicial' && getRowData['Che_Est'] !== 'A') {
                //console.log(getRowData);
                $("#" + getRowData['index'] + "_Haber").attr("readonly", "");
            }
        }
    }
}

function cellColors() {
    let data = $('#searchGrid').jqGrid('getDataIDs');
    //console.log(data);
    if ($.varValid(data)) {
        for (let i = 0, z = data.length; i < z; i++) {
            //console.log(data[i]);
            let getRowData = $('#searchGrid').jqGrid('getRowData', data[i]);
            //console.log(getRowData);
            if (getRowData['Atp_Est'] === 'U') {
                //console.log(getRowData);
                $("tr#" + data[i] + ' td:not(.jqgrid-rownum)').addClass('cellGreen2');
            }
            if (getRowData['Atp_Est'] === 'C') {
                //console.log(getRowData);
                $("tr#" + data[i] + ' td:not(.jqgrid-rownum)').addClass('cellGray');
            }

        }
    }
}

/*
function cambioPreiodoSearch(parm_peri) {
   // $("#txt_fec_ini").dateLimits($("#Pec_Cod option:selected").attr("data-inicio"), $("#Pec_Cod option:selected").attr("data-fin"));
   // $("#txt_fec_fin").dateLimits($("#Pec_Cod option:selected").attr("data-inicio"), $("#Pec_Cod option:selected").attr("data-fin"));
    $("#txt_fec_ini").val($("#Pec_Cod option:selected").attr("data-inicio"));
    $("#txt_fec_fin").val($("#Pec_Cod option:selected").attr("data-fin"));
}*/

// function cambioPreiodoSearch(parm_peri) {
//     var selectedOption = $("#Pec_Cod option:selected");
//     var inicio = selectedOption.attr("data-inicio");
//     var fin = selectedOption.attr("data-fin");
//     var value = selectedOption.val();

//     // Si el valor seleccionado es "T" (Todos), no tocar las fechas actuales
//     if (value === "T") {
//         // Permitir que el usuario ingrese cualquier fecha sin sobrescribir
//         $("#txt_fec_ini").datepicker("option", "minDate", null);
//         $("#txt_fec_ini").datepicker("option", "maxDate", null);
//         $("#txt_fec_fin").datepicker("option", "minDate", null);
//         $("#txt_fec_fin").datepicker("option", "maxDate", null);
//     } else {
//         // Si es un período específico, limitar las fechas al rango del período
//         $("#txt_fec_ini").val(inicio);
//         $("#txt_fec_fin").val(fin);

//         // Configurar límites de fecha basados en el período
//         $("#txt_fec_ini").datepicker("option", "minDate", new Date(inicio));
//         $("#txt_fec_ini").datepicker("option", "maxDate", new Date(fin));
//         $("#txt_fec_fin").datepicker("option", "minDate", new Date(inicio));
//         $("#txt_fec_fin").datepicker("option", "maxDate", new Date(fin));
//     }
// }

// Variables para almacenar las fechas seleccionadas
let selectedStartDate = null;
let selectedEndDate = null;

function cambioPreiodoSearch(parm_peri) {
    var selectedOption = $("#Pec_Cod option:selected");
    var value = selectedOption.val();

    if (value === "T") {
        // Caso "Todos" - Usar fechas del periodo máximo
        const inicio = selectedOption.data("inicio");
        const fin = selectedOption.data("fin");
        
        // Establecer fechas sin restricciones
        $("#txt_fec_ini").datepicker("setDate", inicio);
        $("#txt_fec_fin").datepicker("setDate", fin);
        
        // Remover límites del datepicker
        $("#txt_fec_ini, #txt_fec_fin").datepicker("option", {
            minDate: null,
            maxDate: null
        });

        // Resetear las fechas seleccionadas
        selectedStartDate = null;
        selectedEndDate = null;
    } else if (value === "Corte") {
        // Extraer años solo de los periodos reales (excluyendo 'Todos' y 'Corte')
        const years = [];
        $("#Pec_Cod option").each(function() {
            const $option = $(this);
            // Excluir opciones no numéricas
            if ($option.val() !== "T" && $option.val() !== "Corte") {
                const year = parseInt($option.data("year"));
                if (!isNaN(year)) years.push(year);
            }
        });

        const minYear = years.length > 0 ? Math.min(...years) : new Date().getFullYear();

        // Establecer la fecha de inicio al año mínimo
        if (!selectedStartDate) {
            selectedStartDate = `${minYear}-01-01`;
        }
        if (!selectedEndDate) {
            selectedEndDate = new Date(); // Fecha actual
        }

        $("#txt_fec_ini").datepicker("setDate", selectedStartDate);
        $("#txt_fec_fin").datepicker("setDate", selectedEndDate);

        $("#txt_fec_ini, #txt_fec_fin").datepicker("option", {
            minDate: new Date(minYear, 0, 1),
            maxDate: new Date()
        });
    } else {
        // Caso períodos normales
        var inicio = selectedOption.data('inicio');
        var fin = selectedOption.data('fin');
        
        $("#txt_fec_ini").datepicker("setDate", new Date(inicio));
        $("#txt_fec_fin").datepicker("setDate", new Date(fin));
        
        $("#txt_fec_ini, #txt_fec_fin").datepicker("option", {
            minDate: new Date(inicio),
            maxDate: new Date(fin)
        });

        // Resetear las fechas seleccionadas
        selectedStartDate = null;
        selectedEndDate = null;
    }
}

// Evento para cambiar las fechas
$("#txt_fec_ini").change(function() {
    selectedStartDate = $(this).val(); // Actualiza la fecha de inicio seleccionada
});

$("#txt_fec_fin").change(function() {
    selectedEndDate = $(this).val(); // Actualiza la fecha de fin seleccionada
});

function changePeriodo() {
    $('#Pec_Cod').on('change', function () {
        var sel_fecha = $(this).find('option:selected');
        //console.log(sel_fecha.data('inicio'));
        //console.log(sel_fecha.data('fin'));
        $("#txt_fec_ini").val(sel_fecha.data('inicio'));
        $("#txt_fec_fin").val(sel_fecha.data('fin'));
        $('#txt_fec_ini').dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'));
        $('#txt_fec_fin').dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'));
        $('#txt_fec_ini').trigger('change');
        $('#txt_fec_fin').trigger('change');
    }).trigger('change');
}

function formatMoney(number, places, symbol, thousand, decimal) {
    number = number || 0;
    places = !isNaN(places = Math.abs(places)) ? places : 2;
    symbol = symbol !== undefined ? symbol : "$";
    thousand = thousand || ",";
    decimal = decimal || ".";
    var negative = number < 0 ? "-" : "",
        i = parseInt(number = Math.abs(+number || 0).toFixed(places), 10) + "",
        j = (j = i.length) > 3 ? j % 3 : 0;
    return symbol + negative + (j ? i.substr(0, j) + thousand : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousand) + (places ? decimal + Math.abs(number - i).toFixed(places).slice(2) : "");
}

function calculateValFooter() {
    let ids = $('#searchGrid').jqGrid('getDataIDs');
    var valorDet = 0,
        valorAtp = 0;
    for (let i = 0; i < ids.length; i++) {
        let reg_pago = $('#searchGrid').jqGrid('getRowData', ids[i]);
        var detVal = (reg_pago['sumaDacVal'].replace(/[^0-9-.]/g, '') * 1);
        valorDet = valorDet + (detVal * 1);
        valorAtp = valorAtp + (reg_pago['sumaAtpVal'] * 1);
    }
    $('#searchGrid').jqGrid('footerData', 'set', { nombre: "<div style='text-align:right;'>TOTALES:</div>", tot_anti: $('#searchGrid').jqGrid('getCol', 'tot_anti', false, 'sum') });
    $('#searchGrid').jqGrid('footerData', 'set', { sumaDacVal: "" + valorDet });
    $('#searchGrid').jqGrid('footerData', 'set', { sumaAtpVal: "" + valorAtp });

}

function calCheFooter() {
    //showPagosChe
    let ids = $('#showPagosChe').jqGrid('getDataIDs');
    var valor = 0;
    //console.log(ids.length);
    for (let j = 0; j < ids.length; j++) {
        let reg_pagoC = $('#showPagosChe').jqGrid('getRowData', ids[j]);
        //console.log(reg_pagoC);
        valor = valor + (reg_pagoC['Che_Val'] * 1);
    }
    $('#showPagosChe').jqGrid('footerData', 'set', { Che_Obs: "<div style='text-align:right;'>TOTALES:</div>" });
    $('#showPagosChe').jqGrid('footerData', 'set', { Che_Val: "" + valor });
}

function setColorGrid() {
    let ids = $('#showPagosChe').jqGrid('getDataIDs');
    for (let j = 0; j < ids.length; j++) {
        let getRow = $('#showPagosChe').jqGrid('getRowData', ids[j]);
        if (getRow['Che_Est'] === 'P') {
            $('#showPagosChe').find("tr#" + (j + 1) + ' td:not(.jqgrid-rownum)').addClass('cellRed2');
        }
    }
}

function calSumFooter() {
    let ids = gridPagosAsi.jqGrid('getDataIDs');
    var valorDebe = 0,
        valorHaber = 0;
    for (let i = 0; i < ids.length; i++) {
        let reg_pago = gridPagosAsi.jqGrid('getRowData', ids[i]);
        //console.log(reg_pago);
        valorDebe = valorDebe + (reg_pago['Debe'] * 1);
        valorHaber = valorHaber + (reg_pago['Haber'] * 1);
    }
    gridPagosAsi.jqGrid('footerData', 'set', { Asi_Glo: "<div style='text-align:right;'>TOTALES:</div>" });
    gridPagosAsi.jqGrid('footerData', 'set', { Debe: "" + valorDebe });
    gridPagosAsi.jqGrid('footerData', 'set', { Haber: "" + valorHaber });
}

function calFooterSubGrid() {
    let ids = subGridAsi.jqGrid('getDataIDs');
    var valorDebe = 0,
        valorHaber = 0;
    for (let i = 0; i < ids.length; i++) {
        let reg_pago = subGridAsi.jqGrid('getRowData', ids[i]);
        //console.log(reg_pago);
        valorDebe = valorDebe + (reg_pago['Debe'] * 1);
        valorHaber = valorHaber + (reg_pago['Haber'] * 1);
    }
    subGridAsi.jqGrid('footerData', 'set', { Asi_Glo: "<div style='text-align:right;'>TOTALES:</div>" });
    subGridAsi.jqGrid('footerData', 'set', { Debe: "" + valorDebe });
    subGridAsi.jqGrid('footerData', 'set', { Haber: "" + valorHaber });
}

function verMovimiento(params) {
    //console.log('lola verMovimiento', params);
    const getDataByDet = async () => {
        arrayDetAsiento.length = 0;
        arrayDetAsiento = await asientoSubGridAsync(params[0]);
    }
    getDataByDet().then(() => {
        subGridAsi.setRows(arrayDetAsiento);
        calFooterSubGrid();
    });
    $('#verAsientoDialogMod').dialog('open');
}

function verAnticipo(params) {
    isVerAntic = false;
    console.log("estado: " + isVerAntic);
    $("#showPagosAsi").updateGridsSizes();
    $("#showPagosChe").updateGridsSizes();

    $("#ant_detasi").children("a").trigger("click");

    $('#showPagosAsi').clearGrid(true);
    $('#showPagosChe').clearGrid(true);
    $('#showPagosAsi').trigger("reloadGrid");
    $('#showPagosChe').trigger("reloadGrid");

    $("#prov_show").val(params[0].nombre);
    $("#ruc_show").val(params[0].Prs_Ced);
    $("#compr_show").val(params[0].codigoCompra);
    $("#fec_show").val(params[0].Atp_Fec);
    $("#obs_show").val(params[0].Atp_Obs);
    $("#usuario").html(params[0].usuario + '-');
    $("#Com_Sys").html(params[0].Com_Sys);

    const getDataAsiento = async () => {
        arrayAsiento.length = 0;
        arrayAsiento = await asientoAsync(params[0]);
    }
    const getDataCheque = async () => {
        arrayCheques.length = 0;
        arrayCheques = await chequesAsync(params[0]);
    }
    getDataAsiento().then(() => {
        // $('#showPagosAsi').setRows(arrayAsiento);
        gridPagosAsi.setRows(arrayAsiento);
        calSumFooter();
    });
    getDataCheque().then(() => {
        if (arrayCheques.length === 0) {
            $("#ant_detche").hide();
        } else {
            $("#ant_detche").show();
            // $('#showPagosChe').setRows(arrayCheques);
            gridPagosChe.setRows(arrayCheques);
            calCheFooter();
            setColorGrid();
        }
    });

    $('#verPagosDialogMod').dialog('open');
    $('div#verPagosDialog').siblings('.ui-dialog-titlebar').children('span').text(params[0].nombre);
    //console.log(params);
}

function verAsiento(){
    // alert("funcion en construccion");
    $('#negDialog').dialog('open');
    armargrid();
}

//LISTA DE NEGOCIACIÓN
function armargrid() {
	containerNegoci.createGrid({
		width: 260, height: 140,
		colModel: [{ label: 'Cod.Cop', name: 'Cod_Neg', width: 30 },
		{ label: 'Num.Agu', name: 'Num_Neg', width: 80 },
		{ label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectNego } },
		],
		jsonReader: { root: "response", repeatitems: false },
		datatype: "local",
		footerrow: false,
	});
}

function selectNego(data) {
	var isFacAut = $("#isUpdateNego").val();
	if (isFacAut == "facIsAut") {
		$('#asigNegCamForm #Num_Neg').val(data['Num_Neg']);
		$('#asigNegCamForm #Cod_Neg').val(data['Cod_Neg']);

	} else {
		$('#formDocumento #Num_Neg').val(data['Num_Neg']);
		$('#formDocumento #Cod_Neg').val(data['Cod_Neg']);
	}
	$('#negDialog').dialog('close');
}
// nuevo
function verAntic(params) {
    isVerAntic = true;
    console.log("estado: " + isVerAntic);
    $("#viewPagosAsi").updateGridsSizes();
    $("#viewPagosChe").updateGridsSizes();

    $("#ant_viewasi").children("a").trigger("click");

    $('#viewPagosAsi').clearGrid(true);
    $('#viewPagosChe').clearGrid(true);
    $('#viewPagosAsi').trigger("reloadGrid");
    $('#viewPagosChe').trigger("reloadGrid");

    $("#Prov_view").val(params[0].nombre);
    $("#RUC_view").val(params[0].Prs_Ced);
    $("#Compr_view").val(params[0].codigoCompra);
    $("#Fec_view").val(params[0].Atp_Fec);
    $("#OBS_view").val(params[0].Atp_Obs);
    $("#user").html(params[0].usuario + '-');
    $("#Fec_Com_Sys").html(params[0].Com_Sys);

    const getDataAsiento = async () => {
        arrayAsiento.length = 0;
        arrayAsiento = await asientoAsync(params[0]);
    }
    const getDataCheque = async () => {
        arrayCheques.length = 0;
        arrayCheques = await chequesAsync(params[0]);
    }
    getDataAsiento().then(() => {
        gridPagosAsi.setRows(arrayAsiento);
        calSumFooter();
    });
    getDataCheque().then(() => {
        if (arrayCheques.length === 0) {
            $("#ant_viewche").hide();
        } else {
            $("#ant_viewche").show();
            gridPagosChe.setRows(arrayCheques);
            calCheFooter();
            setColorGrid();
        }
    });

    $('#ModPagosDialogCam').dialog('open');
    $('div#ModPagosDialog').siblings('.ui-dialog-titlebar').children('span').text(params[0].nombre);
}

function modificarAnticipo(parm_mod) {
    perCodAct = 0;
    //console.log(parm_mod);
    //console.log(peridodo);
    arrayModAsiento.length = 0;
    const getDataAsiMod = async () => { arrayModAsiento = await asientoAsync(parm_mod[0]); }
    getDataAsiMod().then(() => {
        $('#anticipoPrvForm').setData(parm_mod[0]);
        $("#Tia_Cod_temp").val(parm_mod[0].Tia_Cod);
        let f_Act = $('#anticipoPrvForm').find('#Atp_Fec').val();
        peridodo.forEach(per => { if (f_Act > per.Pec_Fei && f_Act < per.Pec_Fef) { $("#Atp_Fec").dateLimits(per.Pec_Fei, per.Pec_Fef); } });
        moveToUpdate();
        llenarModAsient(arrayModAsiento);
        verificaChequeEstado();
        perCodAct = parm_mod[0]['Pec_Cod'];
        //console.log(perCodAct);
    });
}

function llenarModAsient(data) {
    // console.log(data);
    $('#pagos').clearGrid(true);
    $("#loader").show();
    let lengthDatos = data.length;
    var next = 0;
    let tipoData = '';
    data.forEach(respuesta => {
        //console.log(respuesta);
        if (respuesta['Asi_Deh'] === 'D') { tipoData = 'inicial'; } else { tipoData = 'pago'; }
        next = $("#pagos").jqGrid('getCol', 'index', false, 'max');
        next = (isNaN(next) ? 1 : next + 1);
        $("#pagos").jqGrid('addRowData', next, $.extend(respuesta, { index: next, grid_tipp: tipoData, Asi_Cod: respuesta['Asi_Cod'], Che_Cod: respuesta['Che_Cod'], Pap_Cod: respuesta['Pap_Cod'], Pag_Cod: respuesta['Pag_Cod'], Pag_Abr: respuesta['Pag_Abr'], Pag_Des: respuesta['Pag_Des'], Pld_Des: respuesta['Pld_Des'], Pld_Cdc: respuesta['Pld_Cdc'], Pap_Ctd: respuesta['Pap_Ctd'], Ban_Cod: respuesta['Ban_Cod'], Che_Est: respuesta['Che_Est'], Che_Num: respuesta['Che_Num'], Che_Fec: respuesta['Che_Fec'], Pap_Cto: respuesta['Pap_Cto'], Pld_Cod: respuesta['Pld_Cod'], Det_Tip: respuesta['Det_Tip'], Glosa: respuesta['Glosa'], Debe: respuesta['Debe'], Haber: respuesta['Haber'] }), 'last');


        $("#pagos").find('#' + next + '_Haber').on('change', function () {
            if ($(this).val() > 0) {
                //console.log($(this).val());
                let tnmGrid = $('#pagos').getGridBatch();
                reCalculateHaber(tnmGrid.length);
                $("#_Haber").attr("readonly", "");
                $("#" + next + "_Haber").css('text-align', 'right');

                formaterFotter(next);
            } else {
                $("#" + next + "_Debe").css('text-align', 'right');
                $("#" + next + "_Haber").attr("readonly", "");
            }

        }).trigger('change');

    });
    formaterFotter(next);
    if (data.length > 0) { $("#loader").hide(); }
}

function reCalculateHaber(tmano) {
    let grid = $("#pagos"),
        valDebe = 0,
        inxD = 0,
        indxH = 0,
        valHaber = 0;
    let tnmGrid = grid.getGridBatch();
    if (tmano === tnmGrid.length) {
        tnmGrid.forEach(gridTam => {
            //console.log(gridTam);
            if ((gridTam['Debe'] * 1) > 0 || gridTam['grid_tipp'] === 'inicial') {
                valDebe += (gridTam['Debe'] * 1);
                inxD = (gridTam['index'] * 1);
            }
            if ((gridTam['Haber'] * 1) > 0 || gridTam['grid_tipp'] === 'pago') {
                valHaber += (gridTam['Haber'] * 1);
                indxH = (gridTam['index'] * 1);
            }
        });
        if (valDebe !== valHaber) { grid.find('#' + inxD + '_Debe').val((valHaber * 1).toFixed(4)); }

        grid.jqGrid('footerData', 'set', { Asi_Glo: "<div style='text-align:right;'>TOTALES:</div>" });
        grid.jqGrid('footerData', 'set', { Debe: "" + formatMoney((valHaber * 1).toFixed(4)) });
        grid.jqGrid('footerData', 'set', { Haber: "" + formatMoney((valHaber * 1).toFixed(4)) });
        $('#totalFinal').val('' + valHaber);
    }
}

function createGridShowAsiDetalle() {
    subGridAsi.createGrid({
        viewrecords: false,
        caption: "<center>Detalle del anticipo</center>",
        data: [],
        rowNum: 100,
        height: 180,
        width: 650,
        footerrow: true,
        responsive: false,
        onSelectRow: function (rowid, e) { $(this).resetSelection(); },
        colModel: [
            { label: '', name: 'Pap_Est', hidden: true },
            { label: 'index', name: 'index', hidden: true, classes: 'bgNoRight' },
            { label: 'Codigo', name: 'Pld_Cdc', width: 10, align: "left" },
            { label: 'Cuenta', name: 'Pld_Des', width: 30, align: "left" },
            { label: 'Glosa', name: 'Asi_Glo', width: 25, align: "left" },
            { label: 'Debe', name: 'Debe', width: 10, align: 'right', formatter: 'currency', editable: true, formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } },
            { label: 'Haber', name: 'Haber', width: 10, align: 'right', formatter: 'currency', editable: true, formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } }
        ]
    }, true, '', { view: false });
}

function createPagosModGrid() {
    $('#pagos').createGrid({
        viewrecords: false,
        data: [],
        rowNum: 100,
        height: 150,
        footerrow: true,
        onSelectRow: function (rowid, e) { $(this).resetSelection(); },
        colModel: [
            { label: ' ', name: 'Det_Tip', hidden: true },
            { label: ' ', name: 'grid_tipp', hidden: true },
            { label: ' ', name: 'Che_Cod', hidden: true },
            { label: 'index', name: 'index', hidden: true, classes: 'bgNoRight' },
            { label: 'Asi_Cod', name: 'Asi_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'Pap_Cod', name: 'Pap_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'Pag_Cod', name: 'Pag_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'Pag_Abr', name: 'Pag_Abr', hidden: true, classes: 'bgNoRight' },
            { label: 'Tipo', name: 'Pag_Des', width: 10, align: "center", classes: 'bgNoRight', formatter: function (cellvalue, options, rowObject) { if (rowObject.Asi_Deh === 'D') { return "-"; } else { return rowObject.Pag_Des } } },
            { label: 'Ban_Cod', name: 'Ban_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'Che_Num', name: 'Che_Num', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Che_Est', hidden: true },
            { label: 'Che_Fec', name: 'Che_Fec', hidden: true, classes: 'bgNoRight' },
            { label: 'Pap_Cto', name: 'Pap_Cto', hidden: true, classes: 'bgNoRight' },
            { label: 'Pap_Ctd', name: 'Pap_Ctd', hidden: true, classes: 'bgNoRight' },
            { label: 'Cuenta_Pld', name: 'Pld_Cod', width: 30, hidden: true, classes: 'bgNoRight' },
            { label: 'C&oacute;digo', name: 'Pld_Cdc', width: 10, classes: 'bgNoRight' },
            { label: 'Cuenta', name: 'Pld_Des', width: 30, classes: 'bgNoRight' },
            { label: 'Glosa', name: 'Asi_Glo', width: 20, editable: true },
            { label: 'Debe', name: 'Debe', width: 10, align: 'right', formatter: 'textboxExa', formatoptions: { attr: { readonly: 'readonly' } } },
            { label: 'Haber', name: 'Haber', width: 10, align: 'right', formatter: 'textboxExa' },
            //{ label: 'Debe', name: 'Debe', width: 10, align: 'right', formatter: 'currency', editable: true, formatoptions: { defaultValue: '' }, editoptions: { dataInit: function(element) { $(this).createInputDiario3(element, "D", "Det_Tip"); } } },
            { label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'Pag_Item', width: 10, align: 'center', viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    if (rowObject.Asi_Deh === 'D') {
                        return "-";
                    } else if (rowObject.Che_Est === 'C' || rowObject.Che_Est === 'U') {
                        return $.createIcon('lock orange', false, 'title="Cheque usado."');
                    } else {
                        return $.getGridButton(borrarPago, rowObject, 'Borrar pago', 'remove', '', 'danger');
                    }

                },
                title: false
            }
        ],
        loadComplete: function () {
            //verificaChequeEstado();
        }
    },
        true,
        'pagosPager', { view: false }).gridButtonsAdd([{
            id: 'btn_mod_agr',
            caption: 'Agregar Pago',
            buttonicon: 'glyphicon glyphicon-plus',
            onClickButton: function () { /*agregarFila(1);*/ openDialogPagos(); }
        }]);
}

function crearGridShowPagosAsi() {
    gridPagosAsi.createGrid({
        viewrecords: false,
        caption: "<center>Detalle del anticipo</center>",
        data: [],
        rowNum: 100,
        height: 100,
        width: 650,
        footerrow: true,
        responsive: false,
        onSelectRow: function (rowid, e) { $(this).resetSelection(); },
        colModel: [
            { label: 'index', name: 'index', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Pap_Est', hidden: true },
            { label: 'Codigo', name: 'Pld_Cdc', width: 10, align: "left" },
            { label: 'Cuenta', name: 'Pld_Des', width: 30, align: "left" },
            { label: 'Glosa', name: 'Asi_Glo', width: 25, align: "left" },
            { label: 'Debe', name: 'Debe', width: 10, align: 'right', formatter: 'currency', editable: true, formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } },
            { label: 'Haber', name: 'Haber', width: 10, align: 'right', formatter: 'currency', editable: true, formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } }
        ]
    }, true, '', { view: false });
}

function crearGridshowPagosChe() {
    gridPagosChe.createGrid({
        viewrecords: false,
        caption: "<center>Cheques emitidos en este anticipo</center>",
        data: [],
        rowNum: 100,
        height: 100,
        width: 650,
        footerrow: true,
        responsive: false,
        onSelectRow: function (rowid, e) { $(this).resetSelection(); },
        colModel: [
            { label: 'index', key: true, name: 'index', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Pap_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Atp_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Atp_Val', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Com_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Tia_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Pld_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Atp_Fec', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Che_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Prv_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Ban_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Asi_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'No. Che.', name: 'Che_Num', width: 15, align: "left" },
            { label: 'Fecha', name: 'Che_Fec', width: 15, align: "left" },
            { label: 'Observaci&oacute;n', name: 'Che_Obs', width: 25, align: "left" },
            { label: 'Valor', name: 'Che_Val', width: 15, align: 'right', formatter: 'currency', editable: true,
                formatoptions: { 
                    prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: ''
                }
            },
            { label: '', name: 'Che_Est', hidden: true, width: 15, align: "left" },
            { label: 'Estado', name: 'estado', width: 15, align: "center" },
            { label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'btns_anti', width: 10, align: 'center', viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    //console.log(rowObject);
                    if (rowObject.Che_Est === 'P') {
                        return "-";
                    } else {
                        if (rowObject.Che_Est === 'A') {
                            if (rowObject.Pap_Est === 'A') {
                                return $.getGridButton(preProtestarCheque, rowObject, 'Marcar como protestado', 'ban-circle', '', 'danger');
                            } else {
                                return $.createIcon('lock orange', false, 'title="El Cheque esta siendo usado!"');
                            }
                        } else {
                            return $.createIcon('lock orange', false, 'title="Esta siendo usado!"');
                        }
                    }
                }
            }
        ]
    }, true, '', { view: false });
}

function imprimirAsiento(params) {
    $.saveDataJson("", { impAsiento: true, params: params }, function (responce) {
        if (responce['success']) {
            window.open(responce['link']);
            return false;
        }
    });
}

function preanularAnticipo(params) {
    //console.log(params);
    $.createDialogConfirm('¿Est&aacute; seguro que desea anular este anticipo?', null, function () {
        $.saveDataJson("", { anularAnticipo: true, data: params }, function (responce) {
            if (responce['success']) {
                $("#searchGrid").trigger("reloadGrid");
                return false;
            }
        });
    });
}

function preProtestarCheque(row) {
    //console.log(row);
    let shChq = false,
        i = 0;
    row['fechaActual'] = new Date();
    $.createDialogConfirm('¿Est&aacute; seguro que desea marcar como protestado este cheque?', null, function () {
        //console.log(row);
        $.saveDataJson("", { protestarCheq: true, row: row }, function (responce) {
            if (responce['success']) {
                $("#searchGrid").trigger("reloadGrid");
                $('#verPagosDialogMod').dialog('close');
                $('#impCompr').attr('href', responce['link']);
                $.alert('La transacci&oacute;n se realizo con exito.');
                // Verifica chueques
                $("#Che_imp option").remove();
                const vrchq = async () => {
                    arrayCheques.length = 0;
                    arrayCheques = await chequesAsync(row);
                }
                vrchq().then(() => {
                    console.log(arrayCheques);
                    arrayCheques.forEach((data) => {
                        //console.log(data);
                        if (data['Che_Est'] === "A") {
                            i++;
                            shChq = true;
                            $("#Che_imp").append("<option value='" + i + "' data-link='?codigo2=" + data['Che_Cod'] + "&asi=" + data['Asi_Cod'] + "&ban=" + data['Ban_Cod'] + "&pro=" + data['Prv_Cod'] + "'>No.:" + data['Che_Num'] + " - Valor:" + data['Che_Val'] + "</option>");
                            cambiarChek();
                        }
                    });
                    if (shChq) {
                        $("#successDialog").dialog({ width: 500, height: 355 });
                        $("#siche").removeAttr("hidden");
                    } else {
                        $("#successDialog").dialog({ width: 500, height: 200 });
                        $("#siche").attr("hidden", "");
                    }
                    $('#successDialog').dialog('open');
                });
                return false;
            }
        });
    });
}

function busquedaAjax() {
    //anticiposAjax
    //searchGrid
    $('#searchGrid').Search('#searchAnticipos', 'anticiposAjax');
}

async function asientoSubGridAsync(row) {
    let resultado = await getDataSubGridAsientoProm(row);
    return resultado;
}
async function asientoAsync(row) {
    let result = await getDataAsientosProm(row);
    return result;
}

async function chequesAsync(row) {
    let chq = await getDataChequesProm(row);
    return chq;
}

async function planCuentaWithNum(tipoAbr, pecCod) {
    let pln = await getNumCWithPlan(tipoAbr, pecCod);
    return pln;
}

async function getCheqNum(valor, banCod) {
    let numChq = await getNumCheque(valor, banCod);
    return numChq;
}

//Promesa plan de cuentas y No. Cuenta del banco para anticipos con cheques
function getNumCWithPlan(abr, pecCod) {
    return new Promise((resolve, reject) => {
        $.getDataJson("", { getPlanCuentasCheq: true, Ban_Tip: abr, Pec_Cod: pecCod }, (result) => {
            resolve(result.getData);
        }, (err) => {
            reject(err);
        })
    });
}

//Promesa asiento detalle
function getDataSubGridAsientoProm(data) {
    //console.log(data);
    return new Promise((resolve, reject) => {
        $.getDataJson("", { getSubGridAsient: true, Com_Cod: data['Com_Cod'] }, (result) => {
            resolve(result.dataSubAsiento);
        }, (err) => {
            reject(err);
        });
    });
}

// Promesa de asientos
function getDataAsientosProm(data) {
    //console.log(data);
    return new Promise((resolve, reject) => {
        $.getDataJson("", { getAsientos: true, Com_Cod: data['Com_Cod'] }, (result) => {
            resolve(result.dataASiento);
        }, (err) => {
            reject(err);
        });
    });

}
//Promesa de cheques
function getDataChequesProm(data) {
    //console.log(data);
    return new Promise((resolve, reject) => {
        $.getDataJson("", { getCheques: true, Atp_Cod: data['Atp_Cod'], Prv_Cod: data['Prv_Cod'] }, (result) => {
            resolve(result.dataCheque);
        }, (err) => {
            reject(err);
        });
    });
}
//Promesa num cheque
function getNumCheque(valor, banCod) {
    return new Promise((resolve, reject) => {
        $.getDataJson('', { verificaCheque: true, Che_Num: valor, Ban_Cod: banCod }, (resultado) => {
            resolve(resultado.numCheque);
        }, (err) => {
            reject(err);
        });
    });
}
//moverse a editar anticipos
function moveToUpdate() {
    $("#documentoSearch").moveComp("#documentoUpdate").updateGridsSizes();
}
//moverse a el principal
function moveToMain() {
    $("#documentoUpdate").moveComp("#documentoSearch").updateGridsSizes();
    $("#searchGrid").trigger("reloadGrid");
}

function limpiarFormAnticipos() {
    // console.log('lolga');
    $("#pagos").jqGrid("clearGridData").trigger("reloadGrid");
    $('#anticipoPrvForm').setData({});
}

function openDialogPagos() {
    $('#Pag_Cod').trigger('change');
    $('#pagosDialog').dialog('open');
}

function cambiarCamposPagos(tipoPago, tipoAbr) {
    //console.log(tipoPago, tipoAbr);
    $("#Pap_Ctd").val("");
    $("#Che_Num").val("");
    $("#Pap_Val").val("");
    $("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
    $("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
    $("#Che_Fec").dateLimits($("#Atp_Fec").val(), $("#Pec_Cod option:selected").attr("data-fin"));
    const getCuentaPlan = async () => {
        arrayCuentasPlan.length = 0;
        arrayCuentasPlan = await planCuentaWithNum(tipoAbr, perCodAct);
    }

    getCuentaPlan().then(() => {
        //console.log(arrayCuentasPlan);
        $("#Ban_Cod option").remove();
        for (let i = 0; i < arrayCuentasPlan.length; i++) {
            if (tipoAbr == "EFE" || tipoAbr == "DEP") {
                $("#Ban_Cod").append("<option value='" + arrayCuentasPlan[i].Ban_Cod + "' data-pla='" + arrayCuentasPlan[i].Pld_Cod + "' data-cdc='" + arrayCuentasPlan[i].Pld_Cdc + "' data-cue='" + arrayCuentasPlan[i].Ban_Cue + "' data-des='" + arrayCuentasPlan[i].Pld_Des + "'>" + arrayCuentasPlan[i].Pld_Des + "</option>");
            } else {
                $("#Ban_Cod").append("<option value='" + arrayCuentasPlan[i].Ban_Cod + "' data-pla='" + arrayCuentasPlan[i].Pld_Cod + "' data-cdc='" + arrayCuentasPlan[i].Pld_Cdc + "' data-cue='" + arrayCuentasPlan[i].Ban_Cue + "' data-des='" + arrayCuentasPlan[i].Pld_Des + "'>" + arrayCuentasPlan[i].Pld_Des + " - " + arrayCuentasPlan[i].Ban_Cue + "</option>");
            }
        }
        $('#pagosForm').children().not(':first,:last').addClass('hidden');
        $('#pagosForm').find('.' + tipoPago).removeClass('hidden');
        $('#pagosForm').find('.' + tipoPago).find('.form-control').prop('required', true);
    });
}

function borrarPago(row) {
    //console.log('borrar', row);
    let editar = true;
    $('#pagos').jqGrid('delRowData', row.index);
    //console.log(arrayModAsiento.length);
    arrayModAsiento.length = 0;
    arrayModAsiento = $('#pagos').getGridBatch();
    reCalculateHaber(arrayModAsiento.length);
    formaterFotter(row.index);
}

function formaterFotter(indice) {
    $("#" + indice + "_Haber").css('text-align', 'right');
    $("#_Haber").attr("readonly", "");
    $("#_Haber").css('text-align', 'right');
    $("#_Debe").css('text-align', 'right');
}

function agregarFila(aux) {
    changeValueInPago();
    var glosaDes = '',
        papCto = '';
    var tipoAbrv = $("#Pag_Cod option:selected").attr("data-abr");
    if (verficaPago()) {
        if (!verifyChequeInGrid()) {
            if (!existeCheq) {
                //console.log(aux);
                //console.log(tipoAbrv);
                if (tipoAbrv === 'CHE') {
                    glosaDes = $("#Pag_Cod option:selected").text() + " NO. " + $("#Che_Num").val();
                    papCto = $("#Ban_Cod option:selected").attr("data-cue");
                } else { glosaDes = 'Ant. prov. ' + $("#Pag_Cod option:selected").text(); }
                var $this = $('#pagos');
                var campoGrid = '_Haber';
                var id = $this.nextIndex();
                $this.jqGrid('addRowData', id, { index: id, grid_tipp: 'pago', Che_Cod: '', Asi_Cod: '', Pap_Cod: '', Pag_Cod: $("#Pag_Cod").val(), Pag_Abr: $("#Pag_Cod option:selected").attr("data-abr"), Pag_Des: $("#Pag_Cod option:selected").text(), Pld_Des: $("#Ban_Cod option:selected").attr("data-des"), Pld_Cdc: $("#Ban_Cod option:selected").attr("data-cdc"), Pap_Ctd: $("#Pap_Ctd").val(), Ban_Cod: $("#Ban_Cod option:selected").attr("value"), Che_Num: $("#Che_Num").val(), Che_Fec: $("#Che_Fec").val(), Pap_Cto: papCto, Pld_Cod: $("#Ban_Cod option:selected").attr("data-pla"), Det_Tip: 'H', Glosa: glosaDes, Debe: '', Haber: parseFloat($("#Pap_Val").val()), Pag_Item: "" }, 'last');
                $this.jqGrid('editRow', id);
                $this.find('#' + id + "_Asi_Glo").val(glosaDes);
                $this.find('#' + id + "_Haber").val(($("#Pap_Val").val() * 1).toFixed(4));
                //$("#Pap_Val").val() 5_Haber
                $this.find('tr#' + id).find('#' + id + campoGrid).on('change', function () {
                    //console.log('LOLA', $(this).val());
                    let tnmGrid = $('#pagos').getGridBatch();
                    reCalculateHaber(tnmGrid.length);
                    formaterFotter(id);
                }).trigger('change');
                let tnmGrid = $this.getGridBatch();
                //console.log(tnmGrid);
                reCalculateHaber(tnmGrid.length);
                formaterFotter(id);
                glosaDes = '';
                papCto = '';
                tipoAbrv = '';
                clearModalPago();
            } else {
                $.alert("Ya existe el registro de pago con el mismo n&uacute;mero de cheque");
            }
        } else {
            clearModalPago();
            $.alert("No puede ingresar dos pagos con el mismo n&uacute;mero de cheque");
        }
    } else {
        $.alert("Complete todos los campos");
        $('#btnGuardar').attr('disabled', '');
    }
}

function clearModalPago() {
    $("#Che_Num").val("");
    $("#Pap_Val").val("");
    $("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
    $("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
}

function verFCheque() {
    var tipoAbrv = $("#Pag_Cod option:selected").attr("data-abr");
    let banCod = $("#Ban_Cod option:selected").attr("value");
    let numAct = $("#Che_Num").val();
    let arrayVerifiCh = [];
    if (tipoAbrv === 'CHE') {
        const vC = async () => {
            arrayVerifiCh.length = 0;
            arrayVerifiCh = await getCheqNum(numAct, banCod);
        }
        vC().then(() => {
            if (arrayVerifiCh.length > 0) {
                existeCheq = true;
            } else {
                existeCheq = false;
            }
        });
    }
}

function verificarNoCheque(valor) {
    $('#btnGuardar').attr('disabled', 'disabled');
    //console.log(valor);
    let arrayVerifiCh = [];
    let banCod = $("#Ban_Cod option:selected").attr("value");
    //console.log(banCod);
    const cheque = async () => {
        arrayVerifiCh.length = 0;
        arrayVerifiCh = await getCheqNum(valor, banCod);
    }
    if (valor > 0 && banCod > 0) {
        cheque().then(() => {
            //console.log(arrayVerifiCh.length);
            if (arrayVerifiCh.length > 0) {
                //console.log('si');
                $("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
                $("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
                $("#indicadorChe").addClass("red glyphicon glyphicon-remove");
                $('span.validate').attr('title', 'El <u> CHEQUE No. <strong>' + valor + '</strong></u> ya se encuentra registrado');
                $('#btnGuardar').attr('disabled', '');
                existeCheq = true;
            } else {
                //console.log('no');
                $('span.validate').attr('title', '');
                $("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
                $("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
                $("#indicadorChe").addClass("green glyphicon glyphicon-ok");
                existeCheq = false;
                if (!verifyChequeInGrid()) { $('#btnGuardar').removeAttr('disabled'); }
            }
        });
    }
}

//Verifica en grid el numero de cheque
function verifyChequeInGrid() {
    let stado = false;
    let numAct = $("#Che_Num").val();
    let getData = $('#pagos').jqGrid("getCol", "Che_Num", false);
    if (numAct !== '') {
        for (let i = 0; i < getData.length; i++) {
            if (numAct === getData[i]) {
                stado = true;
                break;
            }
        }
    }
    return stado;
}
//Guardar Anticipo
function updateData(formulario, accion, dialogo) {
    var data = $('#' + formulario).getData('save');
    let ids = verficaDataInGrid();
    $.createDialogConfirm(`¿Est&aacute; seguro que desea guardar los datos del anticipo proveedor?`, null, function () {
        if (ids.length > 1) {
            //console.log('si');
            //data = $('#AnticipoPrvForm').serializeObject();
            data['anticipoGrid'] = $('#pagos').getGridBatch();
            $.arraySpliceFields(data['anticipoGrid'], ['index', 'Pag_Item', 'false']);
            data[accion] = true;
            $.saveDataJson('', data, function (resp) {
                if (resp['success']) {
                    $('#' + formulario)[0].reset();
                    $('#pagos').clearGrid('true');
                    if (resp['isCheque']) {
                        $("#successDialog").dialog({ width: 500, height: 355 });
                        $("#siche").removeAttr("hidden");
                        $("#Che_imp option").remove();
                        for (let i = 0; i < resp['arrayCheques'].length; i++) {
                            $("#Che_imp").append("<option value='" + i + "' data-link='" + resp['arrayCheques'][i].link + "'>" + resp['arrayCheques'][i].che + "</option>");
                        }
                        $("#Che_imp").trigger("onchange");
                    } else {
                        $("#successDialog").dialog({ width: 500, height: 200 });
                        $("#siche").attr("hidden", "");
                    }
                    $('#impCompr').attr('href', resp['link']);
                    $('#successDialog').dialog('open');

                    encerarFotter();
                    moveToMain();
                    $.alert('La transacci&oacute;n se realizo con exito.');
                    return false;
                }
            });
        } else {
            $.alert("Debe agregar al menos un pago");
        }
    });
}

function cambiarChek() {
    $("#impchetd td").each(function () {
        $(this).children("a").attr("href", $(this).children("a").attr("data-ruta") + "" + $("#Che_imp option:selected").attr("data-link"));
    });
}

function encerarFotter() {
    $('#_Debe').val('0.00');
    $('#_Haber').val('0.00');
}

function verficaDataInGrid() {
    var ids = $('#pagos').jqGrid('getDataIDs');
    return ids;
}

function verficaPago() {
    let verifica = false;
    let tipoSelect = $("#Pag_Cod option:selected").text();
    if (tipoSelect === 'Efectivo') {
        if ($("#Pap_Val").val() !== "") {
            verifica = true;
        }
    } else if (tipoSelect === 'Deposito' || tipoSelect === "Transferencia") {
        if ($("#Pap_Val").val() !== "" && $("#Pap_Ctd").val() !== "") {
            verifica = true;
        }
    } else {
        if ($("#Pap_Val").val() !== "" && $("#Che_Num").val() !== "") {
            verifica = true;
        }
    }
    return verifica;
}

function changeCuentaCod() {
    $('#Ban_Cod').on('change', function () {
        clearModalPago();
    }).trigger('change');
}

function changeValueInPago() {
    $('#Pap_Val').on('change', function () {
        if ($(this).val() * 1 > 0) {
            if (verficaPago()) {
                verFCheque();
                $('#btnGuardar').removeAttr('disabled');
            }
        } else {
            $.alert('El Valor ingresado del Cheque debe superior a 0');
            $(this).val('');
        }
    }).trigger('change');
}

//permite el ingreso unicamente de numeros
function soloNumeros(e) {
    // valor = valor.replace(/[^0-9]/g,'');
    var key = window.Event ? e.which : e.keyCode
    return (key >= 48 && key <= 57)
}

function formatMoney(number, places, symbol, thousand, decimal) {
    number = number || 0;
    places = !isNaN(places = Math.abs(places)) ? places : 2;
    symbol = symbol !== undefined ? symbol : "$";
    thousand = thousand || ",";
    decimal = decimal || ".";
    var negative = number < 0 ? "-" : "",
        i = parseInt(number = Math.abs(+number || 0).toFixed(places), 10) + "",
        j = (j = i.length) > 3 ? j % 3 : 0;
    return symbol + negative + (j ? i.substr(0, j) + thousand : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousand) + (places ? decimal + Math.abs(number - i).toFixed(places).slice(2) : "");
}