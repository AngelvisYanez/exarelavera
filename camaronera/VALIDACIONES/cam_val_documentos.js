var container = $("#container");
var containerVentas = $("#containerVentas");
var containerCompras = $("#containerCompras");
var containerAntiPrv = $("#containerAntiPrv");
var containerAntiCli = $("#containerAntiCli");

var containerEgresos = $("#containerEgresos");
var containerLiquidacion = $("#containerLiquidacion");
var tablasPrecLiq = $("#tablasPrecLiq");

var tablasVentasBL = $("#tablasVentasBL");

$(function () {
    // VENTAS
    $.createDateRange('#Fec_Ini', '#Fec_Fin');
    $.createDateRange($('#frm_cliente_frecuencia').find('input[name="Fec_Ini"]'), $('#frm_cliente_frecuencia').find('input[name="Fec_Fin"]'));
    armarGridPrincipal();
    
    $('#OrderBy').on('change', function () {
        $('input[name=order]').val($(this).val());
        $('#frm_prod_ven').formSubmit();
    });

    //COMPRAS
    $.createDateRange('#Fec_IniC', '#Fec_FinC');
    $.createDateRange($('#frm_proveedor_frecuencia').find('input[name="Fec_IniC"]'), $('#frm_proveedor_frecuencia').find('input[name="Fec_FinC"]'));
    armarGridCompras();

    $('#OrderBy').on('change', function () {
        $('input[name=order]').val($(this).val());
        $('#frm_prod_cop').formSubmit();
    });

    // Anticipos Proveedores
    $.createDateRange('#Fec_IniPr', '#Fec_FinPr');
    $.createDateRange($('#frm_antprov_frecuencia').find('input[name="Fec_IniPr"]'), $('#frm_antprov_frecuencia').find('input[name="Fec_FinPr"]'));
    armarGridAntiProv();

    $('#OrderBy').on('change', function () {
        $('input[name=order]').val($(this).val());
        $('#frm_ant_prov').formSubmit();
    });

    // Anticipos Clientes
    $.createDateRange('#Fec_IniCli', '#Fec_FinCli');
    $.createDateRange($('#frm_antcli_frecuencia').find('input[name="Fec_IniCli"]'), $('#frm_antcli_frecuencia').find('input[name="Fec_FinCli"]'));
    armarGridAntiCli();

    $('#OrderBy').on('change', function () {
        $('input[name=order]').val($(this).val());
        $('#frm_ant_cli').formSubmit();
    });
    
});

function cargarSelectV() {
    $('#filtroV').val($('#FilterVBy').val());
    $('#frm_prod_ven').submit();
}

function cargarSelectC() {
    $('#filtroC').val($('#FilterCBy').val());
    $('#frm_prod_cop').submit();
}


// Grid de Ventas
function armarGridPrincipal() {
    containerVentas.createGrid({
        // caption: 'Negociaciones <div class="pull-right"><b>ORDENAR POR:</b>&nbsp;<select id="OrderBy"><option value="">No ordenar</option><option value=" ng.Cod_Neg ASC">Nro.Neg. Asc</option> <option value="Fec_Neg DESC">Fecha DESC.</option> <option value="Fec_Neg ASC">Fecha ASC.</option><select>&nbsp;</div>',
        caption: 'Negociaciones de Ventas <div class="pull-right"><b>FILTRADO POR:</b>&nbsp;<select id="FilterVBy" onchange="cargarSelectV();"><option value="">No filtrar</option><option value="L">Larva</option><option value="B">Balanceado</option><option value="F">Flete Falso</option><option value="I">Insumos</option><select>&nbsp;</div>',
        height: 200,
        autowidth: true,
        shrinkToFit: false,
        resize: true,
        colModel: [
            { label: 'Cod.Ven', name: 'Vet_Cod', width: 60, align: 'center', hidden: false },
            { label: 'Est.Vent.', name: 'Vet_Est', width: 50, align: 'center',
                formatter: function(cellvalue) {
                    if (cellvalue === 'A') return '<i class="fa fa-check" style="color: green; font-size: 16px;"></i>';
                    return '<i class="fa fa-times" style="color: red; font-size: 16px;"></i>';
                }
            },
            { label: 'Est.Comp.', name: 'Com_Est', width: 50, align: 'center',
                formatter: function(cellvalue) {
                    if (cellvalue === 'A') return '<i class="fa fa-check" style="color: green; font-size: 16px;"></i>';
                    return '<i class="fa fa-times" style="color: red; font-size: 16px;"></i>';
                }
            },
            { label: 'Num.Fac', name: 'Vet_Num', width: 150, align: 'center' },
            { label: 'Num.Neg', name: 'Num_Neg', width: 150, align: 'center' },
            { label: 'Tipo Producto', name: 'Tip_Prod', width: 150, align: 'center',
                formatter: function(cellvalue) {
                    if (cellvalue === 'B') return 'Balanceado';
                    if (cellvalue === 'L') return 'Larva';
                    if (cellvalue === 'F') return 'Flete';
                    if (cellvalue === 'I') return 'Insumos';
                    if (cellvalue === null) return '';
                    return cellvalue;
                }
            },
            { label: 'Fechas', name: 'Com_Fec', width: 100, align: 'center' },
            { label: 'Cedula', name: 'Prs_Ced', width: 150, align: 'center' },
            { label: 'Cliente', name: 'cliente', width: 300 },
            { label: 'Subtotal', name: 'subtotal', formatter: 'currency', width: 100, align: 'center' },
            { label: 'Iva', name: 'Iva', formatter: 'currency', width: 100, align: 'center' },
            { label: 'Total', name: 'total', formatter: 'currency', width: 100, align: 'center' },
            // { label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selecNeg } },
            // { label: '&nbsp;', name: 'act4', width: 30, align: 'center', viewable: false,
            //     formatter: function (cellvalue, options, rowObject) {
            //         return "<button  style='border: 1px solid #285e8e; border-radius: 2px;' id='btn_edit_nego' class='icon-btn btn-primary' data-row-id='" + options.rowId + "'><i class='fa fa-edit'></i></button>";
            //     }
            // },
        ],
        subGrid: false,
        multiselect: false,
        footerrow: true,
        loadComplete: function () {
            $(this).jqGrid('footerData', 'set', {
                cliente: "<div style='text-align:right;'>TOTAL: </div>",
                Iva: $(this).jqGrid('getCol', 'Iva', true, 'sum'),
                subtotal: $(this).jqGrid('getCol', 'subtotal', true, 'sum'),
                total: $(this).jqGrid('getCol', 'total', true, 'sum')
            }, true);
        }
    }, true, '#containerVentasPager', { view: true, refresh: true })
        .gridButtonsAdd([
            { caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download', onClickButton: function () { exportar(); } },
            { caption: 'Imprimir Reporte', buttonicon: 'glyphicon glyphicon-print', onClickButton: function () { imprimir(); } }
        ]);
}

// Grid de Compras
function armarGridCompras() {
    containerCompras.createGrid({
        // caption: 'Negociaciones <div class="pull-right"><b>ORDENAR POR:</b>&nbsp;<select id="OrderBy"><option value="">No ordenar</option><option value=" ng.Cod_Neg ASC">Nro.Neg. Asc</option> <option value="Fec_Neg DESC">Fecha DESC.</option> <option value="Fec_Neg ASC">Fecha ASC.</option><select>&nbsp;</div>',
        caption: 'Negociaciones de Compras <div class="pull-right"><b>FILTRADO POR:</b>&nbsp;<select id="FilterCBy" onchange="cargarSelectC();"><option value="">No filtrar</option><option value="L">Larva</option><option value="B">Balanceado</option><select>&nbsp;</div>',
        height: 200,
        autowidth: true,
        shrinkToFit: false,
        resize: true,
        colModel: [
            { label: 'Cod.Cop', name: 'Cop_Cod', width: 60, align: 'center', hidden: false },
            { label: 'Est.Cop.', name: 'Cop_Est', width: 50, align: 'center',
                formatter: function(cellvalue) {
                    if (cellvalue === 'A') return '<i class="fa fa-check" style="color: green; font-size: 16px;"></i>';
                    return '<i class="fa fa-times" style="color: red; font-size: 16px;"></i>';
                }
            },
            { label: 'Est.Compr', name: 'Com_Est', width: 50, align: 'center',
                formatter: function(cellvalue) {
                    if (cellvalue === 'A') return '<i class="fa fa-check" style="color: green; font-size: 16px;"></i>';
                    return '<i class="fa fa-times" style="color: red; font-size: 16px;"></i>';
                }
            },
            { label: 'Num.Fac', name: 'Cop_Num', width: 150, align: 'center' },
            { label: 'Num.Neg', name: 'Num_Neg', width: 150, align: 'center' },
            { label: 'Tipo Producto', name: 'Tip_Prod', width: 150, align: 'center',
                formatter: function(cellvalue) {
                    if (cellvalue === 'B') return 'Balanceado';
                    if (cellvalue === 'L') return 'Larva';
                    if (cellvalue === 'F') return 'Flete';
                    if (cellvalue === null) return '';
                    return cellvalue;
                }
            },
            { label: 'Fechas', name: 'Com_Fec', width: 100, align: 'center' },
            { label: 'Cedula', name: 'Prs_Ced', width: 150, align: 'center' },
            { label: 'Proveedor', name: 'proveedor', width: 300 },
            { label: 'Subtotal', name: 'subtotal', formatter: 'currency', width: 100, align: 'center' },
            { label: 'Iva', name: 'Iva', formatter: 'currency', width: 100, align: 'center' },
            { label: 'Total', name: 'total', formatter: 'currency', width: 100, align: 'center' },
            // { label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selecNeg } },
            // { label: '&nbsp;', name: 'act4', width: 30, align: 'center', viewable: false,
            //     formatter: function (cellvalue, options, rowObject) {
            //         return "<button  style='border: 1px solid #285e8e; border-radius: 2px;' id='btn_edit_nego' class='icon-btn btn-primary' data-row-id='" + options.rowId + "'><i class='fa fa-edit'></i></button>";
            //     }
            // },
        ],
        subGrid: false,
        multiselect: false,
        footerrow: true,
        loadComplete: function () {
            $(this).jqGrid('footerData', 'set', {
                cliente: "<div style='text-align:center;'>TOTAL: </div>",
                Iva: $(this).jqGrid('getCol', 'Iva', true, 'sum'),
                subtotal: $(this).jqGrid('getCol', 'subtotal', true, 'sum'),
                total: $(this).jqGrid('getCol', 'total', true, 'sum')
            }, true);
        }
    }, true, '#containerComprasPager', { view: true, refresh: true })
        .gridButtonsAdd([
            { caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download', onClickButton: function () { exportar(); } },
            { caption: 'Imprimir Reporte', buttonicon: 'glyphicon glyphicon-print', onClickButton: function () { imprimir(); } }
        ]);
}

// Grid de Anticipos Proveedores
function armarGridAntiProv() {
    containerAntiPrv.createGrid({
        caption: 'Negociaciones de Anticipo Proveedores', 
        stateCol: 'Atp_Est',
        height: '450',
        resize: true,
        colModel: [
            { label: 'Cod. Int.', name: 'Atp_Cod', key: true, width: 25, align: "left" },
            { label: 'No. Compr.', name: 'codigoCompra', width: 30, align: "left" },
            { label: 'Fecha', name: 'Atp_Fec', width: 30, align: "left" },
            { label: ' ', name: 'Prs_Cod', hidden: true },
            { label: ' ', name: 'Pap_Cod', hidden: true },
            { label: ' ', name: 'Prv_Cod', hidden: true },
            { label: ' ', name: 'Com_Cod', hidden: true },
            { label: '', name: 'Atp_Est', hidden: true },
            { label: '', name: 'Pag_Des', hidden: true },
            { label: 'C&eacute;dula', name: 'cedProv', width: 40, align: "left", cellattr: function () { return 'style="' + excelFormats.text + '"'; } },
            { label: 'Proveedor', name: 'nombre', width: 100, align: "left" },
            { label: 'No. Neg.', name: 'Num_Neg', width: 50, align: "center", hidden: (typeof Ses_Emp_Cod !== 'undefined' ? Ses_Emp_Cod != 569 : true) },
            { label: 'Direci&oacute;n', name: 'Prs_Dir', hidden: true, width: 100, align: "left" },
            { label: 'Valor', name: 'sumaAtpVal', width: 60, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } },
            { label: 'Pagos', name: 'sumaDacVal', width: 60, align: 'right', formatter: function (cellvalue, options, rowObject) { if (rowObject['sumaDacVal'] === '' || rowObject['sumaDacVal'] === null) { return "0.00"; } else { return formatMoney(rowObject['sumaDacVal']); } } },
            { label: 'Saldo', name: 'tot_anti', width: 60, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' }, summaryType: "sum" }
            // { label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
            //     name: 'btns_anti',
            //     width: 40,
            //     align: 'center',
            //     viewable: false,
            //     // formatter: function (cellvalue, options, rowObject) {
            //     //     var parm_anu = [rowObject];
            //     //     var parm_getdet = [rowObject];
            //     //     //console.log(rowObject);
            //     //     if (rowObject.Atp_Est === "I") {
            //     //         return $.createIcon('remove red');
            //     //     } 
            //         // else if (rowObject.Atp_Est !== "A" || rowObject.Pag_Des == "Anticipos") {
            //         //     return $.getGridButton(/*verAnticipo,*/ parm_getdet, 'ver anticipo', 'info-sign', '', 'info');
            //         // } else {
            //             //console.log(prf[0]['Per_Des']);
            //             // if (prf[0]['Per_Des'] === 'Administrador de Sistemas') {
            //             //     return $.getGridButton(verAnticipo, parm_getdet, 'ver anticipo', 'info-sign', '', 'info') + "&nbsp;" +
            //             //         $.getGridButton(modificarAnticipo, parm_getdet, 'Modificar anticipo', 'pencil', '', 'success') + "&nbsp;" +
            //             //         $.getGridButton(preanularAnticipo, parm_anu, 'Anular anticipo', 'remove', '', 'danger');
            //             // } else {
            //                 // return $.getGridButton(/*verAnticipo,*/ parm_getdet, 'ver anticipo', 'info-sign', '', 'info') + "&nbsp;" +
            //                 //     $.getGridButton(/*modificarAnticipo,*/ parm_getdet, 'Modificar anticipo', 'pencil', '', 'success') + "&nbsp;" +
            //                 //     $.getGridButton(/*preanularAnticipo,*/ parm_anu, 'Anular anticipo', 'remove', '', 'danger');
            //             // }
            //         // }
            //     // }
            // }
        ],
        footerrow: true,
        userDataOnFooter: true,
        // subGrid: true,
        rowNum: 10000,
        gridview: true,
        viewrecords: true,
        // subGridOptions: {
        //     "plusicon": "ui-icon-triangle-1-e",
        //     "minusicon": "ui-icon-triangle-1-s",
        //     "openicon": "ui-icon-arrowreturn-1-e",
        //     "reloadOnExpand": false,
        //     "selectOnExpand": true
        // },
        // subGridRowExpanded: function (subgrid_id, row_id) {
        //     let subgrid_table_id = subgrid_id + "_t";
        //     let rowData = $("#containerAntiPrv").getRowData(row_id);
        //     $("#" + subgrid_id).html("<table id='" + subgrid_table_id + "' class='scroll'></table>");
        //     $("#" + subgrid_table_id).createGrid({
        //         url: "?detalleAntiProv_Ajax=1&Atp_Cod=" + rowData['Atp_Cod'] + "&Fec_IniPr=" + $("#Fec_IniPr").val() + "&Fec_FinPr=" + $("#Fec_FinPr").val(),
        //         datatype: "json",
        //         regional: 'es',
        //         height: 'auto',
        //         responsive: true,
        //         colModel: [
        //             { label: '', name: 'Atp_Cod', key: true, hidden: true },
        //             { label: 'Compr.', name: 'Com_Cod', width: 20, align: "center", hidden: true },
        //             { label: '', name: 'Atp_Est', hidden: true },
        //             { label: '', name: 'Tia_Cod', hidden: true },
        //             { label: '', name: 'Com_Num', hidden: true },
        //             { label: 'No. Compr.', name: 'codigoCompra', width: 30, align: "left" },
        //             { label: 'Fecha', name: 'Com_Fec', width: 20, align: "left" },
        //             { label: 'Observaci&oacute;n', name: 'Atp_Obs', width: 90, align: "left" },
        //             { label: 'Concepto', name: 'Com_Con', width: 50, align: "left" },
        //             { label: 'Valor', name: 'sumaDacVal', width: 50, align: 'right', formatter: function (cellvalue, options, rowObject) { return formatMoney(rowObject['Dac_Val']); } },
        //             { label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
        //                 name: 'btns_sub_anti', width: 40, align: 'center', viewable: false,
        //                 // formatter: function (cellvalue, options, rowObject) {
        //                 //     var parm_getdet = [rowObject];
        //                     //console.log(rowObject);
        //                     // return $.getGridButton(verMovimiento, parm_getdet, 'ver asiento', 'info-sign', '', 'info') + "&nbsp;" +
        //                     //     $.getGridButton(imprimirAsiento, parm_getdet, 'Imprimir', 'print', '', 'success');
        //                 // }
        //             }
        //         ]
        //     });
        // },
        loadComplete: function (data) {
            calculateValFooter();
            cellColors();
        }
    }, true, '#containerAntiPrvPager', { view: true ,  refresh: true })
        .gridButtonsAdd([
            { caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download', onClickButton: function () { exportar(); } },
            { caption: 'Imprimir Reporte', buttonicon: 'glyphicon glyphicon-print', onClickButton: function () { imprimir(); } }
        ]);
}

// Grid de Anticipos Clientes
function armarGridAntiCli() {
    containerAntiCli.createGrid({
        caption: 'Negociaciones de  Anticipos Clientes',
        height: '450',
        resize: true,
        colModel: [
            { label: 'Cod. Int.', name: 'Ant_Cod', key: true, width: 25, align: "center" },
            { label: 'No. Compr.', name: 'codigoCompra', width: 30, align: "center" },
            { label: 'Fecha', name: 'Ant_Fec', width: 30, align: "center" },
            { label: ' ', name: 'Prs_Cod', hidden: true },
            { label: ' ', name: 'Pac_Cod', hidden: true },
            { label: ' ', name: 'Cli_Cod', hidden: true },
            { label: ' ', name: 'Com_Cod', hidden: true },
            { label: '', name: 'Ant_Est', hidden: true, },
            { label: 'C&eacute;dula', name: 'cedProv', width: 50, align: "left", cellattr: function () { return 'style="' + excelFormats.text + '"'; } },
            { label: 'Cliente', name: 'nombre', width: 100, align: "left" },
            { label: 'No. Neg.', name: 'Num_Neg', width: 50, align: "center", hidden: (typeof Ses_Emp_Cod !== 'undefined' ? Ses_Emp_Cod != 569 : true) },
            { label: 'Direci&oacute;n', name: 'Prs_Dir', width: 100, align: "left" },
            { label: 'Valor', name: 'sumaAntVal', width: 60, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } },
            { label: 'Pagos', name: 'sumaDdcVal', width: 60, align: 'right', formatter: function (cellvalue, options, rowObject) { if (rowObject['sumaDdcVal'] === '' || rowObject['sumaDdcVal'] === null) { return "0.00"; } else { return formatMoney(rowObject['sumaDdcVal']); } } },
            { label: 'Saldo', name: 'tot_anti', width: 60, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '$ 0.00' }, summaryType: "sum" }
            // { label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
            //     name: 'btns_anti', width: 40, align: 'center', viewable: false,
            //     // formatter: function (cellvalue, options, rowObject) {
            //     //     var parm_anu = [rowObject];
            //     //     var parm_getdet = [rowObject];
            //     //     //console.log(rowObject);

            //     //     if (rowObject.Ant_Est !== "A") {
            //     //         return $.getGridButton(verAnticipo, parm_getdet, 'ver anticipo', 'info-sign', '', 'info');
            //     //     } else {
            //     //         //console.log(prf[0]['Per_Des']);
            //     //         if (rowObject.sumaDdcVal.toNum() === 0/*prf[0]['Per_Des'] === 'Administrador de Sistemas'*/) {
            //     //             return $.getGridButton(verAnticipo, parm_getdet, 'ver anticipo', 'info-sign', '', 'info') + "&nbsp;" +
            //     //                 $.getGridButton(modificarAnticipo, parm_getdet, 'Modificar anticipo', 'pencil', '', 'success') + "&nbsp;" +
            //     //                 $.getGridButton(preanularAnticipo, parm_anu, 'Anular anticipo', 'remove', '', 'danger');

            //     //         } else {
            //     //             return $.getGridButton(verAnticipo, parm_getdet, 'ver anticipo', 'info-sign', '', 'info') + "&nbsp;" +
            //     //                 $.getGridButton(modificarAnticipo, parm_getdet, 'Modificar anticipo', 'pencil', '', 'success') + "&nbsp;" +
            //     //                 $.getGridButton(preanularAnticipo, parm_anu, 'Anular anticipo', 'remove', '', 'danger');
            //     //         }
            //     //     }
            //     // }
            // }

        ],
        footerrow: true,
        userDataOnFooter: true,
        // subGrid: true,
        rowNum: 10000,
        gridview: true,
        viewrecords: true,
        // subGridOptions: {
        //     "plusicon": "ui-icon-triangle-1-e",
        //     "minusicon": "ui-icon-triangle-1-s",
        //     "openicon": "ui-icon-arrowreturn-1-e",
        //     "reloadOnExpand": false,
        //     "selectOnExpand": true
        // },
        // subGridRowExpanded: function (subgrid_id, row_id) {
        //     //console.log(subgrid_id);
        //     //console.log(row_id);
        //     let subgrid_table_id = subgrid_id + "_t";
        //     let rowData = $("#searchGrid").getRowData(row_id);
        //     $("#" + subgrid_id).html("<table id='" + subgrid_table_id + "' class='scroll'></table>");
        //     $("#" + subgrid_table_id).createGrid({
        //         url: "?movAnticipo=" + rowData['Ant_Cod'] + "&txt_fec_ini=" + $("#txt_fec_ini").val() + "&txt_fec_fin=" + $("#txt_fec_fin").val(),
        //         datatype: "json",
        //         regional: 'es',
        //         height: 'auto',
        //         responsive: true,
        //         colModel: [
        //             { label: '', name: 'Ant_Cod', key: true, hidden: true },
        //             { label: '', name: 'Com_Cod', hidden: true },
        //             { label: '', name: 'Ant_Est', hidden: true },
        //             { label: '', name: 'Tia_Cod', hidden: true },
        //             { label: '', name: 'Com_Num', hidden: true },
        //             { label: 'No. Compr.', name: 'codigoCompra', width: 30, align: "left" },
        //             { label: 'Fecha', name: 'Com_Fec', width: 20, align: "center" },
        //             { label: 'Fecha', name: 'Ant_Fec', width: 20, align: "left" },
        //             { label: 'Observaci&oacute;n', name: 'Ant_Obs', width: 90, align: "left" },
        //             { label: 'Concepto', name: 'Com_Con', width: 50, align: "left" },
        //             { label: 'Valor', name: 'sumaDacVal', width: 50, align: 'right', formatter: function (cellvalue, options, rowObject) { return formatMoney(rowObject['Ddc_Val']); } },
        //             {
        //                 label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
        //                 name: 'btns_sub_anti',
        //                 width: 40,
        //                 align: 'center',
        //                 viewable: false,
        //                 formatter: function (cellvalue, options, rowObject) {
        //                     var parm_getdet = [rowObject];
        //                     //console.log(rowObject);
        //                     return $.getGridButton(verMovimiento, parm_getdet, 'ver asiento', 'info-sign', '', 'info') + "&nbsp;" +
        //                         $.getGridButton(imprimirAsiento, parm_getdet, 'Imprimir', 'print', '', 'success');

        //                 }
        //             }

        //         ]
        //     });

        // },
        loadComplete: function (data) {
            calculateValFooter();
            cellColors();
        }

    }, true, '#containerAntiCliPager', { view: true,  refresh: true })
        .gridButtonsAdd([
            { caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download', onClickButton: function () { exportar(); } },
            { caption: 'Imprimir Reporte', buttonicon: 'glyphicon glyphicon-print', onClickButton: function () { imprimir(); } }
        ]);
}

function imprimir() {
    var activeTab = $('#documentoSearch').tabs('option', 'active');
    var grid, removeCols = [];
    if (activeTab === 0) {
        grid = $('#containerVentas');
        removeCols = [0, 11];
    } else if (activeTab === 1) {
        grid = $('#containerCompras');
        removeCols = [0, 11];
    } else if (activeTab === 2) {
        grid = $('#containerAntiPrv');
        removeCols = [0, 10];
    } else if (activeTab === 3) {
        grid = $('#containerAntiCli');
        removeCols = [0, 10];
    } else {
        $.alert('No se encontró el grid para imprimir.');
        return;
    }

    $('#tablaReporte').html(grid.jqGrid('exportGridInnerHTML', {
        footer: true,
        generated: false,
        removeHiddens: true,
        removeCols: removeCols
    }));
    $('#imprimir').printElement();
}

function exportar() {
    var activeTab = $('#documentoSearch').tabs('option', 'active');
    var grid, removeCols = [], nombreArchivo = 'Negociaciones_';
    if (activeTab === 0) {
        grid = $('#containerVentas');
        removeCols = [0, 11];
        nombreArchivo = 'Negociaciones-Ventas_';
    } else if (activeTab === 1) {
        grid = $('#containerCompras');
        removeCols = [0, 11];
        nombreArchivo = 'Negociaciones-Compras_';
    } else if (activeTab === 2) {
        grid = $('#containerAntiPrv');
        removeCols = [0, 10];
        nombreArchivo = 'Negociaciones-Anticipo-Proveedores_';
    } else if (activeTab === 3) {
        grid = $('#containerAntiCli');
        removeCols = [0, 10];
        nombreArchivo = 'Negociaciones-Anticipo-Clientes_';
    } else {
        $.alert('No se encontró el grid para imprimir.');
        return;
    }

    $('#tablaExporta').html(grid.jqGrid('exportGridInnerHTML', {
        footer: true,
        bodyBorder: false,
        removeHiddens: true,
        removeCols: removeCols
    }));
    $.downloadFile($.exportarExcelBlob($('#exportar').html(), nombreArchivo + $.getDate() + '.xls'), nombreArchivo + $.getDate() + '.xls');
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
    let ids = $('#containerAntiPrv').jqGrid('getDataIDs');
    var valorDet = 0,
        valorAtp = 0;
    for (let i = 0; i < ids.length; i++) {
        let reg_pago = $('#containerAntiPrv').jqGrid('getRowData', ids[i]);
        var detVal = (reg_pago['sumaDacVal'].replace(/[^0-9-.]/g, '') * 1);
        valorDet = valorDet + (detVal * 1);
        valorAtp = valorAtp + (reg_pago['sumaAtpVal'] * 1);
    }
    $('#containerAntiPrv').jqGrid('footerData', 'set', { nombre: "<div style='text-align:right;'>TOTALES:</div>", tot_anti: $('#containerAntiPrv').jqGrid('getCol', 'tot_anti', false, 'sum') });
    $('#containerAntiPrv').jqGrid('footerData', 'set', { sumaDacVal: "" + valorDet });
    $('#containerAntiPrv').jqGrid('footerData', 'set', { sumaAtpVal: "" + valorAtp });
}

function cellColors() {
    let data = $('#containerAntiPrv').jqGrid('getDataIDs');
    //console.log(data);
    if ($.varValid(data)) {
        for (let i = 0, z = data.length; i < z; i++) {
            //console.log(data[i]);
            let getRowData = $('#containerAntiPrv').jqGrid('getRowData', data[i]);
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

function selecNeg(auto) {
    //Cargar las liquidaciones
    load_liquidacion(auto);
    //Cargar los documentos
    load_documentos_negociacion(auto);
    load_doc_negociacion(auto);
    load_pag_compras(auto);
    load_pag_ventas(auto);
}

// Anular negociación
function delFila(data) {
    $("<div></div>").dialog({
        modal: true, title: "Confirmación de Anulación",
        open: function () {
            var markup = "¿Estás seguro de que deseas anular la negociación?";
            $(this).html(markup);
        }, buttons: {
            "Anular": function () {
                $.post('', { anularNegAjax: true, Cod_Neg: data.Cod_Neg }, function (response) {
                    if (response.success) {
                        $("#container").jqGrid('delRowData', data.id);
                        $.alert(response.message)
                    } else { $.alert(response.message) }
                }, 'json').fail(function () { $.alert("Error en la comunicación con el servidor.") });
                $(this).dialog("close");
            }, "Cancelar": function () { $(this).dialog("close"); }
        },
        close: function () { $(this).remove(); }
    });
}