var id_pagos = 0;
$(function() {

    // inicializa componentes de fecha en formulario de anticipos
    $('.datepicker').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });

    if ($("#periodos").length === 1)
        $("#periodos").trigger("onchange");

    if ($('#verPagosDialogMod').length === 1)
        $("#verPagosDialogMod").createDialog({ width: 700, height: 435, icon: 'info-sign' });

    if ($('#showPagosAsi').length === 1)
        crearGridShowPagosAsi();

    if ($('#showPagosChe').length === 1)
        crearGridshowPagosChe();

    if ($('#tabs_ant_det').length === 1)
        $("#tabs_ant_det").tabs();

    if ($('#cuentasDialog').length === 1)
        $.createSearchDialog('cuentasDialog', [
            { label: 'Cod. Int.', name: 'Pld_Cod', width: 20, align: "left" },
            { label: 'C&oacute;digo', name: 'Pld_Cdc', width: 50, align: "left" },
            { label: 'Cuenta Contable', name: 'Pld_Des', width: 100, align: "left" },
            {
                label: '&nbsp;',
                name: 'plsel',
                width: 15,
                align: 'center',
                viewable: false,
                title: false,
                formatter: function(cellvalue, options, rowObject) {
                    return $.getGridButton(cambiarCuenta, rowObject, 'Seleccionar cuenta', 'check', '', 'success');
                }
            }
        ], null, null, null, null, { title: 'Cuenta', options: [{ label: '&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;', value: 'd' }, { label: '&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;', value: 'c' }] })
        .find('.form-group-options').append('<div class="col-md-4"> <label class="control-label label-xs">Plan de Cuentas:</label><input name="Periodo" type="text" size="6" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /> <input name="Pec_Cod" type="hidden" /><input name="Asi_Cod" type="hidden" /></div>');

    //Dialog buscar clientes
    if ($('#clientesDialog').length === 1)
        $.createSearchDialog('clientesDialog', [
            { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15, align: "center", hidden: true },
            { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
            { label: 'Cliente', name: 'nombre', width: 100 },
            { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
            { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectCliente } }
        ], null, null, null, { headertitles: true }, { title: 'Cliente', text: 'searchCli' });

    if ($('#successDialog').length === 1)
        $("#successDialog").createDialog({ width: 500, height: 200, icon: 'print' });

    if ($('#verPagosDialog').length === 1)
        $("#verPagosDialog").createDialog({ width: 400, height: 270, icon: 'info-sign' });

    // inicializa el dialog de registrar pagos de anticipo
    if ($('#pagosDialog').length === 1)
        $('#pagosDialog').createDialog({ height: 325, icon: 'usd' });

    $('#pagos').createGrid({
        viewrecords: false,
        data: [],
        rowNum: 10000000,
        height: 150,
        footerrow: true,
        onSelectRow: function(rowid, e) { $(this).resetSelection(); },
        colModel: [{
                label: '<center><span class="glyphicon glyphicon-check"></span></center>',
                name: 'sel_item',
                width: 5,
                align: 'center',
                viewable: false,
                formatter: function(cellvalue, options, rowObject) {
                    if (rowObject.Pag_Abr === 'OTR') {
                        return $.getGridButton(AbrirCuentas, rowObject, 'Cambiar cuenta', 'check', '', 'success');
                    } else {
                        return "-";
                    }
                },
                title: false
            },
            { name: 'index', hidden: true, classes: 'bgNoRight' },
            { name: 'Det_Tip', hidden: true },
            { name: 'grid_tipp', hidden: true },
            { name: 'Pag_Cod', hidden: true },
            { name: 'Pac_Num', hidden: true },
            { name: 'Pac_Cod', hidden: true },
            { name: 'Pac_Cto', hidden: true },
            { name: 'Pac_Ctd', hidden: true },
            { name: 'Pag_Abr', hidden: true },
            { label: 'Tipo', name: 'Pag_Des', width: 10, align: "center", classes: 'bgNoRight' },
            { name: 'Ban_Cod', hidden: true, classes: 'bgNoRight' },
            { name: 'Che_Num', hidden: true, classes: 'bgNoRight' },
            { name: 'Che_Fec', hidden: true, classes: 'bgNoRight' },
            { name: 'Che_Est', hidden: true },
            { name: 'Pac_Cto', hidden: true, classes: 'bgNoRight' },
            { name: 'Pld_Cod', width: 30, hidden: true, classes: 'bgNoRight' },
            { label: 'C&oacute;digo', name: 'Pld_Cdc', width: 10, classes: 'bgNoRight' },
            { label: 'Cuenta', name: 'Pld_Des', width: 30, classes: 'bgNoRight' },
            { label: 'Glosa', name: 'Glosa', width: 20, editable: true },
            {
                label: 'Debe',
                name: 'Debe',
                width: 10,
                align: 'right',
                formatter: 'currency',
                editable: true,
                formatoptions: { defaultValue: '' },
                editoptions: { dataInit: function(element) { $(this).createInputDiario3(element, "D", "Det_Tip"); } }
            },
            {
                label: 'Haber',
                name: 'Haber',
                width: 10,
                align: 'right',
                formatter: 'currency',
                editable: true,
                formatoptions: { defaultValue: '' },
                editoptions: { dataInit: function(element) { $(this).createInputDiario3(element, "H", "Det_Tip"); } }
            },
            {
                label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
                name: 'Pag_Item',
                width: 10,
                align: 'center',
                viewable: false,
                formatter: function(cellvalue, options, rowObject) {
                    if (rowObject.grid_tipp === 'inicial' || rowObject.grid_tipp === "che_prot") {
                        return "-";
                    } else {
                        return $.getGridButton(mostrarPago, rowObject, 'Ver pago', 'info-sign', '', 'info') + "&nbsp;" +
                            $.getGridButton(borrarPago, rowObject, 'Borrar pago', 'remove', '', 'danger');;
                    }
                },
                title: false
            }
        ],
        loadComplete: function() {
            // $(this).setGridSummary(['Debe'],{Pag_Des:'<div style="text-align:right;">TOTAL:</div>'});
            $(this).jqGrid('footerData', 'set', {
                Glosa: "<div style='text-align:right;'>TOTALES:</div>",
                Debe: $(this).jqGrid('getCol', 'Debe', true, 'sum'),
                Haber: $(this).jqGrid('getCol', 'Haber', true, 'sum')
            }, true);
        }
    }, true, 'pagosPager', { view: false }).gridButtonsAdd([
        { caption: 'Agregar', buttonicon: 'glyphicon glyphicon-plus', class: 'a', onClickButton: function() { gestionarPago(); } }
    ]);
    $.clearFooterDiario("#pagos");
    $('#pagos').updateGridDiario();

});

$(document).ready(function() {
    if ($('#searchGrid').length === 1)
        cargarAnticipos();

    if ($("#Ant_Fec").length === 1)
        $.post("", { obtenerPeriodoMinMax: true }, function(responce) {
            if (responce['success'] === true) {
                $("#Ant_Fec").dateLimits(responce['data']['minimo'], responce['data']['maximo']);
            } else {
                console.log(responce['message']);
            }
        }, 'json')
        .fail(function(error) {
            console.log("El Servidor ha fallado en responder!");
        });
    $("#Tia_Cod option[data-abr='IN']").prop("selected", true);
});

function crearGridShowPagosAsi() {
    $('#showPagosAsi').createGrid({
        viewrecords: false,
        caption: "<center>Detalle del anticipo</center>",
        data: [],
        rowNum: 100,
        height: 100,
        width: 650,
        footerrow: true,
        responsive: false,
        onSelectRow: function(rowid, e) { $(this).resetSelection(); },
        colModel: [
            { label: 'index', name: 'index', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Pap_Est', hidden: true },
            { label: 'Codigo', name: 'Pld_Cdc', width: 10, align: "left" },
            { label: 'Cuenta', name: 'Pld_Des', width: 30, align: "left" },
            { label: 'Glosa', name: 'Glosa', width: 25, align: "left" },
            {
                label: 'Debe',
                name: 'Debe',
                width: 10,
                align: 'right',
                formatter: 'currency',
                editable: true,
                formatoptions: {
                    prefix: '$ ',
                    thousandsSeparator: ',',
                    decimalSeparator: '.',
                    defaultValue: ''
                }
            },
            {
                label: 'Haber',
                name: 'Haber',
                width: 10,
                align: 'right',
                formatter: 'currency',
                editable: true,
                formatoptions: {
                    prefix: '$ ',
                    thousandsSeparator: ',',
                    decimalSeparator: '.',
                    defaultValue: ''
                }
            }
        ]
    }, true, '', { view: false });
}

function crearGridshowPagosChe() {
    $('#showPagosChe').createGrid({
        viewrecords: false,
        caption: "<center>Cheques emitidos en este anticipo</center>",
        data: [],
        rowNum: 100,
        height: 100,
        width: 650,
        footerrow: true,
        responsive: false,
        onSelectRow: function(rowid, e) { $(this).resetSelection(); },
        colModel: [
            { label: 'index', key: true, name: 'index', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Pac_Cod', hidden: true },
            { label: '', name: 'Pld_Cod', hidden: true },
            { label: '', name: 'Asi_Cod', hidden: true },
            { label: '', name: 'Ant_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Ant_Val', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Com_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'No. Cta.', name: 'Che_Cta', width: 15, align: "left" },
            { label: '', name: 'Ant_Fec', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Che_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Cli_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Bak_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'No. Che.', name: 'Che_Num', width: 15, align: "left" },
            { label: 'Fecha', name: 'Che_Fec', width: 15, align: "left" },
            { label: 'Observaci&oacute;n', name: 'Che_Obs', width: 25, align: "left" },
            {
                label: 'Valor',
                name: 'Che_Val',
                width: 15,
                align: 'right',
                formatter: 'currency',
                editable: true,
                formatoptions: {
                    prefix: '$ ',
                    thousandsSeparator: ',',
                    decimalSeparator: '.',
                    defaultValue: ''
                }
            },
            { label: '', name: 'Che_Est', hidden: true, width: 15, align: "left" },
            { label: 'Estado', name: 'Che_Est_det', width: 15, align: "left" },
            {
                label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
                name: 'btns_anti',
                width: 10,
                align: 'center',
                viewable: false,
                formatter: function(cellvalue, options, rowObject) {
                    if (rowObject.Che_Est === 'P') {
                        return "-";
                    } else {
                        return $.getGridButton(preProtestarCheque, rowObject, 'Marcar como protestado', 'ban-circle', '', 'danger');
                    }
                }
            }
        ]
    }, true, '', { view: false });
}

var searchGridLoad = false;

function cargarAnticipos() {
    $("#searchGrid").createGrid({
        postData: $("#searchAnticipos").getData("anticiposAjax"),
        height: 200,
        colModel: [
            { label: 'Cod. Int.', name: 'Cli_Cod', key: true, width: 30, align: "left" },
            { label: ' ', name: 'Prs_Cod', hidden: true },
            { label: 'C&eacute;dula', name: 'Prs_Ced', width: 50, align: "left", cellattr: function() { return 'style="' + excelFormats.text + '"'; } },
            { label: 'Nombre', name: 'nombre', width: 100, align: "left" },
            { label: 'Direci&oacute;n', name: 'Prs_Dir', width: 140, align: "left" },
            {
                label: 'Saldo',
                name: 'tot_anti',
                width: 80,
                align: 'right',
                formatter: 'currency',
                formatoptions: {
                    prefix: '$ ',
                    thousandsSeparator: ',',
                    decimalSeparator: '.',
                    defaultValue: ''
                },
                summaryType: "sum"
            }
        ],
        subGridOptions: {
            "plusicon": "ui-icon-triangle-1-e",
            "minusicon": "ui-icon-triangle-1-s",
            "openicon": "ui-icon-arrowreturn-1-e",
            "reloadOnExpand": false,
            "selectOnExpand": true
        },
        subGrid: true,
        multiselect: false,
        subGridRowExpanded: function(subgrid_id, row_id) {
            var subgrid_table_id = subgrid_id + "_t";
            var cliente_data = $('#searchGrid').jqGrid('getRowData', row_id);
            $("#" + subgrid_id).html("<div class='condensed /*jqHeaderFirst*/ jqFirst'><table id='" + subgrid_table_id + "' class='scroll'></table></div>");
            $("#" + subgrid_table_id).createGrid({
                url: "" + "?anticiposDetAjax=" + cliente_data.Cli_Cod + "&txt_fec_ini=" + $("#txt_fec_ini").val() + "&txt_fec_fin=" + $("#txt_fec_fin").val(),
                datatype: "json",
                regional: 'es',
                height: 'auto',
                caption: "<center>Anticipos de cliente " + cliente_data.nombre + "</center>",
                responsive: false,
                colModel: [
                    { label: '', name: 'Ant_Cod', key: true, hidden: true },
                    { label: '', name: 'Com_Cod', hidden: true },
                    { label: '', name: 'Ant_Est', hidden: true },
                    { label: '', name: 'Tia_Cod', hidden: true },
                    { label: '', name: 'Com_Num', hidden: true },
                    { label: 'No. Compr.', name: 'codigo_compro', width: 30, align: "left" },
                    { label: 'No. Ant.', name: 'Ant_num', width: 30, align: "left" },
                    { label: 'Fecha', name: 'Ant_Fec', width: 50, align: "left" },
                    { label: 'Observaci&oacute;n', name: 'Ant_Obs', width: 140, align: "left" },
                    { label: 'Cant. Pagos', name: 'cnt_pagos', width: 20, align: "center" },
                    {
                        label: 'Valor',
                        name: 'Ant_Val',
                        width: 50,
                        align: 'right',
                        formatter: 'currency',
                        formatoptions: {
                            prefix: '$ ',
                            thousandsSeparator: ',',
                            decimalSeparator: '.',
                            defaultValue: '$ 0.00'
                        }
                    },
                    {
                        label: 'Saldo',
                        name: 'tot_sald',
                        width: 50,
                        align: 'right',
                        formatter: 'currency',
                        formatoptions: {
                            prefix: '$ ',
                            thousandsSeparator: ',',
                            decimalSeparator: '.',
                            defaultValue: '$ 0.00'
                        }
                    },
                    {
                        label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
                        name: 'btns_anti',
                        width: 50,
                        align: 'center',
                        viewable: false,
                        formatter: function(cellvalue, options, rowObject) {
                            var parm_anu = [rowObject, "" + subgrid_table_id];
                            var parm_getdet = [rowObject, row_id];
                            if (rowObject.Ant_Est === "C") {
                                return $.getGridButton(verAnticipo, parm_getdet, 'ver anticipo', 'info-sign', '', 'info');
                            } else {
                                return $.getGridButton(verAnticipo, parm_getdet, 'ver anticipo', 'info-sign', '', 'info') + "&nbsp;" +
                                    $.getGridButton(modificarAnticipo, parm_getdet, 'Modificar anticipo', 'pencil', '', 'success') + "&nbsp;" +
                                    $.getGridButton(preanularAnticipo, parm_anu, 'Anular anticipo', 'remove', '', 'danger');
                            }
                        }
                    }
                ],
                loadComplete: function() {
                    //
                },
                beforeSelectRow: function(rowid, e) { return false; },
                rowNum: 100,
                pager: ""
            });
        },
        loadComplete: function() {
            // $(this).setGridSummary(['Debe'],{Pag_Des:'<div style="text-align:right;">TOTAL:</div>'});
            if (searchGridLoad === false) {
                searchGridLoad = true;
                $(this).jqGrid('footerData', 'set', {
                    nombre: "<div style='text-align:right;'>TOTAL GENERAL:</div>",
                    Prs_Dir: "<span id='tot_general'>$ " + parseFloat($(this).jqGrid('getCol', 'tot_anti', true, 'sum')).toFixed(2) + " </span>"
                }, true);
            }
            $(this).jqGrid('footerData', 'set', {
                sld_dsp: "<div style='text-align:right;'>TOTAL:</div>",
                tot_anti: $(this).jqGrid('getCol', 'tot_anti', true, 'sum')
            }, true);
            $("#tot_general").parent().attr("style", "text-align:right;");
        },
        rowNum: 100,
        gridview: true,
        viewrecords: true,
        footerrow: true,
        userDataOnFooter: false,
        onSelectRow: function(rowid, e) { $("#searchGrid").resetSelection(); },
        multiselect: false
    }, false, "#searchGridPager");
}

function preProtestarCheque(row) {
    $.createDialogConfirm('�Est&aacute; seguro que desea marcar como protestado este cheque?', row, protestarCheque);
}

function protestarCheque(row) {
    $.saveDataJson("", { protestarChe: true, row: row, Cli_Cod_prt: $("#Cli_Cod_prt").val() }, function(responce) {
        if (responce['pec_ban'] === "si") {
            //
            $("#searchGrid").trigger("reloadGrid");
            $('#verPagosDialogMod').dialog('close');
            $('#impCompr').attr('href', responce['link']);
            $('#successDialog').dialog('open');
        } else {
            $.alert(responce['message']);
        }
        return false;
    }, function(responce) {
        $.alert(responce['message']);
    }, function(responce) {
        $.alert(responce['message']);
    });
}

function verAnticipo(params) {
    $("#showPagosAsi").updateGridsSizes();
    $("#showPagosChe").updateGridsSizes();

    $("#ant_detasi").children("a").trigger("click");

    $("#showPagosAsi").jqGrid("clearGridData").trigger("reloadGrid");
    $("#showPagosChe").jqGrid("clearGridData").trigger("reloadGrid");

    var cliente_data = $('#searchGrid').jqGrid('getRowData', params[1]);

    $("#prov_show").val(cliente_data.nombre);
    $("#Cli_Cod_prt").val(cliente_data.Cli_Cod);
    $("#ruc_show").val(cliente_data.Prs_Ced);
    $("#compr_show").val(params[0].codigo_compro);
    $("#fec_show").val(params[0].Ant_Fec);
    $("#obs_show").val(params[0].Ant_Obs);

    $('#verPagosDialogMod').dialog('open');
    $('div#verPagosDialog').siblings('.ui-dialog-titlebar').children('span').text(cliente_data.nombre);

    $.getDataJson('', { 'getAsientosAnticipo': true, Com_Cod: params[0].Com_Cod, Cli_Cod: params[1], Ant_Cod: params[0].Ant_Cod }, function(responce) {
        var ids_pagos = $('#showPagosAsi').jqGrid('getDataIDs').length + 1;
        //agregamos asientos
        for (i = 0; i < responce['data'].length; i++) {
            var ids_pagos = $('#showPagosAsi').jqGrid('getDataIDs').length + 1;
            $('#showPagosAsi').jqGrid('addRowData', ids_pagos, {
                index: ids_pagos,
                Pld_Cdc: responce['data'][i].Pld_Cdc,
                Pac_Est: responce['data'][i].Pac_Est,
                Pld_Des: responce['data'][i].Pld_Des,
                Glosa: responce['data'][i].Asi_Glo,
                Debe: responce['data'][i].Debe,
                Haber: responce['data'][i].Haber
            }, "last");
        }

        $('#showPagosAsi').jqGrid('footerData', 'set', {
            Glosa: "<div style='text-align:right;'>TOTALES:</div>",
            Debe: $('#showPagosAsi').jqGrid('getCol', 'Debe', true, 'sum'),
            Haber: $('#showPagosAsi').jqGrid('getCol', 'Haber', true, 'sum')
        }, true);

        //en caso de existir cheques mostramos la pestania de cheques
        if (responce['data_che'].length === 0) {
            $("#ant_detche").hide();
        } else {
            $("#ant_detche").show();
        }

        //agregamos cheques
        for (let i = 0; i < responce['data_che'].length; i++) {
            let ids_pg = $('#showPagosChe').jqGrid('getDataIDs').length + 1;
            let estado_che = "";
            if (responce['data_che'][i].Che_Est === "P") estado_che = "Protestado";
            if (responce['data_che'][i].Che_Est === "S") estado_che = "Sin cobrar";
            if (responce['data_che'][i].Che_Est === "I") estado_che = "Anulado";
            if (responce['data_che'][i].Che_Est === "A") estado_che = "Activo";
            if (responce['data_che'][i].Che_Est === "C") estado_che = "Cobrado";
            $('#showPagosChe').jqGrid('addRowData', ids_pg, {
                index: ids_pg,
                Che_Num: responce['data_che'][i].Che_Num,
                Che_Fec: responce['data_che'][i].Che_Fec,
                Che_Val: responce['data_che'][i].Che_Val,
                Che_Obs: responce['data_che'][i].Che_Obs,
                Che_Est_det: estado_che,
                Pac_Cod: responce['data_che'][i].Pac_Cod,
                Pld_Cod: responce['data_che'][i].Pld_Cod,
                Asi_Cod: responce['data_che'][i].Asi_Cod,
                Cli_Cod: responce['data_che'][i].Cli_Cod,
                Che_Est: responce['data_che'][i].Che_Est,
                Che_Cod: responce['data_che'][i].Che_Cod,
                Bak_Cod: responce['data_che'][i].Bak_Cod,
                Che_Cta: responce['data_che'][i].Che_Cta
            }, "last");
        }

        //---------------------------
    }, function(err) {
        $.alert(err['message']);
    });
    //
}

function modificarAnticipo(parm_mod) {
    var cliente_data = $('#searchGrid').jqGrid('getRowData', parm_mod[1]);

    $("#pagos").jqGrid("clearGridData").trigger("reloadGrid");

    //rows  id
    $("#bandera_prov").val("sel");
    $("#Prs_Cod").val(cliente_data.Prs_Cod);
    $("#Cli_Cod").val(cliente_data.Cli_Cod);

    $("#Prs_Ced").val(cliente_data.Prs_Ced);
    $("#nombre").val(cliente_data.nombre);
    $("#Prs_Dir").val(cliente_data.Prs_Dir);

    $("#Com_Cod").val(parm_mod[0].Com_Cod);
    $("#Ant_Cod").val(parm_mod[0].Ant_Cod);

    $("#Tia_Cod option[value=" + parm_mod[0].Tia_Cod + "]").prop("selected", true);
    $("#Tia_Cod_temp").val(parm_mod[0].Tia_Cod);
    $("#Com_Num").val(parm_mod[0].Com_Num);
    $("#Ant_Obs").val(parm_mod[0].Ant_Obs);

    //****************
    $.getDataJson('', { getAsientosAnticipoMod: true, Cli_Cod: cliente_data.Cli_Cod, Com_Cod: parm_mod[0].Com_Cod }, function(responce) {
        //agregamos asientos
        $("#save_bnd").val("s");
        for (i = 0; i < responce['data'].length; i++) {
            id_pagos++;
            var total = parseFloat($("#Ant_Val").val());
            total += parseFloat($("#Pac_Val").val());
            $("#Ant_Val").val(total.toFixed(2));
            $("[id*='_Haber']").val(total.toFixed(2));
            let tpg_gr = "";
            if (responce['data'][i].Asi_Deh === 'H') { tpg_gr = "inicial" } else { tpg_gr = 'pago' }
            if (responce['data'][i].Che_Est === "P") { tpg_gr = "che_prot"; }
            $('#pagos').jqGrid('addRowData', id_pagos, {
                index: id_pagos,
                grid_tipp: tpg_gr,
                Che_Cod: responce['data'][i].Che_Cod,
                Asi_Cod: responce['data'][i].Asi_Cod,
                Pac_Cod: responce['data'][i].Pac_Cod,
                Pag_Cod: responce['data'][i].Pag_Cod,
                Pag_Abr: responce['data'][i].Pag_Abr,
                Pag_Des: responce['data'][i].Pag_Des,
                Pld_Des: responce['data'][i].Pld_Des,
                Pld_Cdc: responce['data'][i].Pld_Cdc,
                Pac_Ctd: responce['data'][i].Pac_Ctd,
                Ban_Cod: responce['data'][i].Bak_Cod,
                Che_Num: responce['data'][i].Che_Num,
                Pac_Num: responce['data'][i].Pac_Num,
                Che_Est: responce['data'][i].Che_Est,
                Che_Fec: responce['data'][i].Che_Fec,
                Pac_Cto: responce['data'][i].Pac_Cto,
                Pld_Cod: responce['data'][i].Pld_Cod,
                Det_Tip: responce['data'][i].Asi_Deh,
                Glosa: responce['data'][i].Asi_Glo,
                Debe: responce['data'][i].Debe,
                Haber: responce['data'][i].Haber,
                Pag_Item: ""
            }, "last");
        }
        $('#pagos').startGridEdit();
        $('#pagos').updateGridDiario();
        $("[id*='_Haber']").attr("readonly", "");
        bloquearprot();

    }, function(err) {
        $.alert(err['message']);
    });

    moveToUpdate();
}

function bloquearprot() {
    var ids_p = $('#pagos').jqGrid('getDataIDs');
    for (let i = 0; i < ids_p.length; i++) {
        let reg_pago = $('#pagos').jqGrid('getRowData', ids_p[i]);
        if (reg_pago.Pag_Abr === "CHE" && reg_pago.Che_Est === "P") {
            $("#" + ids_p[i] + "_Haber").attr("readonly", "");
            $("#" + ids_p[i] + "_Glosa").attr("readonly", "");
        }
    }
}

//dirigirse de la pantalla de listar anticipos a la de modificar anticipos
function moveToUpdate() {
    $("#documentoSearch").moveComp("#documentoUpdate").updateGridsSizes();
}

//dirigirse de la ventana de modificar anticipos a la de listar anticipos
function moveToList() {
    $("#documentoUpdate").moveComp("#documentoSearch").updateGridsSizes();
    $("#searchGrid").trigger("reloadGrid");
}

function preanularAnticipo(parms) {
    $.createDialogConfirm('�Est&aacute; seguro que desea anular este Anticipo?', parms, anularAnticipo);
}

function anularAnticipo(parms) {
    data = { delAnticipo: true, Ant_Cod: parms[0].Ant_Cod, Com_Cod: parms[0].Com_Cod };
    $.saveDataJson("", data, function(responce) {
        $.alert("Anticipo anulado!");
        $("#" + parms[1]).trigger("reloadGrid");
        return false;
    }, function(responce) {
        $.alert(responce['message']);
    }, function(responce) {
        $.alert(responce['message']);
    });
}

// borra el pago seleccionado y actualiza los totales
function borrarPago(row) {
    $('#pagos').jqGrid('delRowData', '' + row.index + '');
    $('#pagos').updateGridDiario();
    var totalesgrid = actualizarTotales();
    var hab = totalesgrid.haber;
    var deb = totalesgrid.debe;
    if (deb !== hab) { hab = deb; }
    $("#Ant_Val").val(parseFloat(hab).toFixed(2));
    $("[id*='_Haber']").val($("#Ant_Val").val());
    $('#pagos').jqGrid("footerData", "set", { Glosa: "<div style='text-align:right;'>TOTALES:</div>", Debe: hab, Haber: hab });
}

//muestra los datos del pago ingresado
function mostrarPago(row) {
    $('#verPagosForm').children().not(':first,:last').addClass('hidden');
    $('#verPagosForm').find('.' + row.Pag_Des).removeClass('hidden');
    $('#verPagosForm').find('.' + row.Pag_Des).find('.form-control').prop('required', true);

    $("#pago_ver").val(row.Pag_Des);
    $("#Pac_Num_ver").val(row.Pac_Num);
    $("#cuenta_ver").val(row.Pac_Cto);
    $("#destino_ver").val(row.Pac_Ctd);
    $("#fecha_ver").val(row.Che_Fec);
    $("#numero_ver").val(row.Che_Num);
    $("#valor_ver").val("$ " + row.Debe);

    $('#verPagosDialog').dialog('open');
}

var numeroCheque = false;
//verifica si el numero de un cheque ya se encuentra registrado
function verificarNoCheque(valor) {
    datach = { "verificarCheNum": true, "Che_Num": valor, "Bak_Cod": $("#Bak_Cod option:selected").attr("value"), Cli_Cod: $("#Cli_Cod").val() };
    $.post("", datach, function(response) {
        if (response['numero_che'] === true) {
            $("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
            $("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
            $("#indicadorChe").addClass("red glyphicon glyphicon-remove");
            numeroCheque = true;
        } else {
            numeroCheque = false;
            if (valor === "") {
                $("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
                $("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
                $("#indicadorChe").addClass("red glyphicon glyphicon-remove");
            } else {
                $("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
                $("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
                $("#indicadorChe").addClass("green glyphicon glyphicon-ok");
            }
        }
    }, 'json');
}

//para asignar un cliente al anticipo a crear
function selectCliente(cliente) {
    limpiarFormAnticipos();
    $.getDataJson('', { 'get_ant_doc': true }, function(result) {
        $("#Ant_Doc").val(result['data']);
        $("#Ant_Doc_ver").val("ANT - " + result['data']);
    }, function(err) {
        $.alert(err['message']);
    });

    $("#bandera_prov").val("sel");
    $("#Ant_Obs").val("ANTICIPO DE CLIENTE - " + cliente.nombre);
    $('#AnticipoCliForm').setData($.extend(cliente, { op_opciones: 'c' }), false);
    $('#clientesDialog').dialog('close');
    cuentaAnticipoIni();
}

function cuentaAnticipoIni() {
    $.getDataJson('', { 'cargar_cuentas_pagos': true, tipo: "INICIAL" }, function(result) {
        if (result['bandera'] === true) {
            array_p = ["inicial", "", "", "-", result['data'].Pld_Des, result['data'].Pld_Cdc, "", "", "", "", "", result['data'].Pld_Cod, "H", "Anticipo del cliente " + $("#nombre").val(), "", "0.00", "first"];
            addPago(array_p);
            $("[id*='_Haber']").attr("readonly", "");
            $("#save_bnd").val("s");
        } else {
            $.alert("NO EXISTE UNA CUENTA PARAMETRIZADA PARA " + result['message']);
            $("#save_bnd").val("n");
        }
    }, function(err) {
        $.alert(err['message']);
    });
}

function gestionarPago() {
    if ($("#bandera_prov").val() !== "sel") { return $.alert("Primero debe seleccionar un Cliente!"); }
    if ($("#save_bnd").val() !== "s") { return $.alert("Primero Verifique que esten parametrizadas las cuentas necesarias!"); }
    $('#Pag_Cod').trigger('change');
    $('#Pac_Val').trigger('change');
    $('#pagosDialog').dialog('open');
    $('#pagosForm').removeData();
}

// habilita y deshabilita campos dependiendo del tipo de pago seleccionado, recive el tipo de pago y su abrebiatura
function cambiarCamposPagos(tipo_pago, tipo_pago_abr) {
    $("#Pac_Cto").val("");
    console.log('Tipo de pago: ' + tipo_pago_abr);
    var data = new Object();
    data['tipo'] = tipo_pago_abr;
    data['cargar_cuentas_pagos'] = true;

    $.getDataJson('', { 'cargar_cuentas_pagos': true, tipo: tipo_pago_abr }, function(result) {
        $("#Ban_Cod option").remove();
        $("#Bak_Cod option").remove();
	
        for (i = 0; i < result['data'].length; i++) {
            if (tipo_pago_abr == "EFE" || tipo_pago_abr === "OTR") {
                $("#Ban_Cod").append("<option value='" + result['data'][i].Ban_Cod + "' data-pla='" + result['data'][i].Pld_Cod + "' data-cdc='" + result['data'][i].Pld_Cdc + "' data-cue='" + result['data'][i].Ban_Cue + "' data-des='" + result['data'][i].Pld_Des + "'>" + result['data'][i].Pld_Des + "</option>");
            } else if (tipo_pago_abr === "TRF" || tipo_pago_abr == "DEP" || tipo_pago_abr == "NDC") {
                $("#Ban_Cod").append("<option value='" + result['data'][i].Ban_Cod + "' data-pla='" + result['data'][i].Pld_Cod + "' data-cdc='" + result['data'][i].Pld_Cdc + "' data-cue='" + result['data'][i].Ban_Cue + "' data-des='" + result['data'][i].Pld_Des + "'>" + result['data'][i].Pld_Des + " - " + result['data'][i].Ban_Cue + "</option>");
            } else if (tipo_pago_abr === "CHE") {
                $("#Bak_Cod").append("<option value='" + result['data'][i].Bak_Cod + "' data-pla='" + result['data'][i].Pld_Cod + "' data-cdc='" + result['data'][i].Pld_Cdc + "' data-cue='" + result['data'][i].Bak_Des + "' data-des='" + result['data'][i].Pld_Des + "'>" + result['data'][i].Bak_Des + "</option>");
            }
        }
        if (result['data_ban'] !== null) {
            for (i = 0; i < result['data_ban'].length; i++) {
                $("#Ban_Cod").append("<option value='" + result['data_ban'][i].Ban_Cod + "' data-pla='" + result['data_ban'][i].Pld_Cod + "' data-cdc='" + result['data_ban'][i].Pld_Cdc + "' data-cue='" + result['data_ban'][i].Ban_Cue + "' data-des='" + result['data_ban'][i].Pld_Des + "'>" + result['data_ban'][i].Pld_Des + " - " + result['data_ban'][i].Ban_Cue + "</option>");
            }
        }
	
	var opcion = $('#Pag_Cod').children("option:selected").text();
        if(opcion == 'NotaCredito'){
            $('#doc').text("Referencia:");
        }

        $('#pagosForm').children().not(':first,:last').addClass('hidden');
        $('#pagosForm').find('.' + tipo_pago).removeClass('hidden');
        $('#pagosForm').find('.' + tipo_pago).find('.form-control').prop('required', true);
    }, function(err) {
        $.alert(err['message']);
    });
}

// valida que los campos no esten vacios dependiendo del tipo de pago seleccionado
function validarPagosForm(tipo) {
    var bandera_pagos = false;
    if (tipo === "EFE") {
        if ($("#Pac_Val").val() != "") {
            bandera_pagos = false;
        } else {
            bandera_pagos = true;
        }
    }
    if (tipo === "DEP" || tipo === "TRF" || tipo === "NDC") {
        if ($("#Pac_Val").val() !== "" || $("#Pac_Cto").val() !== "") {
            bandera_pagos = false;
        } else {
            bandera_pagos = true;
        }
    }
    if (tipo === "CHE") {
        if ($("#Pac_Val").val() != "" && $("#Che_Num").val() != "" && $("#Pac_Cto").val() !== "") {
            bandera_pagos = false;
        } else {
            bandera_pagos = true;
        }
    }
    return bandera_pagos;
}

//anadimos un pago a la tabla de pagos
function AgregarPago() {
    var bandera_cheque_pago = false;
    if ($("#Pag_Cod option:selected").attr("data-abr") == "CHE") {
        criterio = $('#pagos').jqGrid("getCol", "Che_Num", false);
        for (i = 0; i < criterio.length; i++) {
            if ($("#Che_Num").val() == criterio[i]) {
                bandera_cheque_pago = true;
            }
        }
    }

    if ((validarPagosForm($("#Pag_Cod option:selected").attr("data-abr")))) { return $.alert("Complete todos los campos"); }
    if (bandera_cheque_pago === true) { return $.alert("No puede ingresar dos pagos con el mismo n&uacute;mero de cheque"); }
    if (numeroCheque === true) { return $.alert("El numero de cheque (" + $("#Che_Num").val() + ") ya fue emitido"); }

    var total = parseFloat($("#Ant_Val").val());
    total += parseFloat($("#Pac_Val").val());

    if ($("#Pag_Cod option:selected").attr("data-abr") === "EFE") {
        if ($("#pagos td:contains('EFE')").text().search("EFE") == -1) {
            $("#Ant_Val").val(total.toFixed(2));
            $("[id*='_Haber']").val(total.toFixed(2));

            array_p = ["pago", $("#Pag_Cod").val(), $("#Pag_Cod option:selected").attr("data-abr"), $("#Pag_Cod option:selected").text(),
                $("#Ban_Cod option:selected").attr("data-des"), $("#Ban_Cod option:selected").attr("data-cdc"), $("#Ban_Cod option:selected").attr("value"),
                $("#Che_Num").val(), $("#Che_Fec").val(), $("#Ban_Cod option:selected").attr("data-cue"),
                "", $("#Ban_Cod option:selected").attr("data-pla"), "D",
                'Ant. cli. ' + $("#Pag_Cod option:selected").text(), parseFloat($("#Pac_Val").val()).toFixed(2), "", $("#Pac_Num").val(),
                "last"
            ];
            addPago(array_p);
        }
    } else if ($("#Pag_Cod option:selected").attr("data-abr") === "CHE") {
        $("#Ant_Val").val(total.toFixed(2));
        $("[id*='_Haber']").val(total.toFixed(2));
        array_p = ["pago", $("#Pag_Cod").val(), $("#Pag_Cod option:selected").attr("data-abr"), $("#Pag_Cod option:selected").text(),
            $("#Bak_Cod option:selected").attr("data-des"), $("#Bak_Cod option:selected").attr("data-cdc"), $("#Bak_Cod option:selected").attr("value"),
            $("#Che_Num").val(), $("#Che_Fec").val(), "",
            $("#Pac_Cto").val(), $("#Bak_Cod option:selected").attr("data-pla"), "D",
            $("#Pag_Cod option:selected").text() + " NO. " + $("#Che_Num").val(), parseFloat($("#Pac_Val").val()).toFixed(2), "", $("#Pac_Num").val(),
            "last"
        ];
        addPago(array_p);
    } else {
        $("#Ant_Val").val(total.toFixed(2));
        $("[id*='_Haber']").val(total.toFixed(2));

        var pacNum = $("#Pac_Num").val();
        var glosa = 'Ant. cli. ' + $("#Pag_Cod option:selected").text();
        if (pacNum) {
            glosa += '. Doc. # ' + pacNum;
        }

        array_p = ["pago", $("#Pag_Cod").val(),
            $("#Pag_Cod option:selected").attr("data-abr"),
            $("#Pag_Cod option:selected").text(),
            $("#Ban_Cod option:selected").attr("data-des"),
            $("#Ban_Cod option:selected").attr("data-cdc"),
            $("#Ban_Cod option:selected").attr("value"),
            $("#Che_Num").val(), $("#Che_Fec").val(),
            $("#Ban_Cod option:selected").attr("data-cue"),
            "", $("#Ban_Cod option:selected").attr("data-pla"), "D",
            // 'Ant. cli. ' + $("#Pag_Cod option:selected").text(), parseFloat($("#Pac_Val").val()).toFixed(2), "", $("#Pac_Num").val(),
            // "last"
            glosa,
            parseFloat($("#Pac_Val").val()).toFixed(2), "", pacNum, "last"
        ];
        addPago(array_p);
    }
}

function addPago(array_p) {
    id_pagos++;
    $('#pagos').jqGrid('addRowData', id_pagos, {
        index: id_pagos,
        grid_tipp: array_p[0],
        Pag_Cod: array_p[1],
        Pag_Abr: array_p[2],
        Pag_Des: array_p[3],
        Pld_Des: array_p[4],
        Pld_Cdc: array_p[5],
        Ban_Cod: array_p[6],
        Che_Num: array_p[7],
        Che_Fec: array_p[8],
        Pac_Ctd: array_p[9],
        Pac_Cto: array_p[10],
        Pld_Cod: array_p[11],
        Det_Tip: array_p[12],
        Glosa: array_p[13],
        Debe: array_p[14],
        Haber: array_p[15],
        Pac_Num: array_p[16]
    }, array_p[17]);
    $('#pagos').startGridEdit();
    $('#pagos').updateGridDiario();
}

function cambioValPago(elemento) {
    if (elemento.val() != '') {
        elemento.val(parseFloat(elemento.val()).toFixed(2));
    } else {
        elemento.val("0.00");
    }
}

//permite el ingreso unicamente de numeros
function soloNumeros(e) {
    // valor = valor.replace(/[^0-9]/g,'');
    var key = window.Event ? e.which : e.keyCode
    return (key >= 48 && key <= 57)
}

//verifica si hay al menos un pago antes de guardar unj anticipo
function preguardadopagos() {
    var ids = $('#pagos').jqGrid('getDataIDs');
    if (ids.length < 2) { return $.alert("Debe agregar al menos un pago"); }
    $('#AnticipoCliForm').formSubmit();
}

//funcion para guardar anticipos a proveedores
function guardar_anticipo() {

    var totalesgrid = actualizarTotales();

    var deb = totalesgrid.debe,
        hab = totalesgrid.haber;
    // comparacion entre totales para guardar anticipo del cliente seleccionado
    if (deb !== hab) {
        $('#pagos').startGridEdit();
        $('#pagos').updateGridDiario();
        return $.alert("Los totales no coinciden!");
    }

    $("#Ant_Val").val(parseFloat(deb).toFixed(2));
    var data = $('#AnticipoCliForm').serializeObject();
    data["saveAnticipo"] = true;

    var batch = $('#pagos').getGridBatch();
    data["pago_anticipo_clientes"] = batch;

    $.saveDataJson("", data, function(responce) {
        limpiarFormAnticipos();
        $('#impCompr').attr('href', responce['link']);
        $('#impAnt').attr('href', responce['link2']);
        $('#successDialog').dialog('open');
        moveToList();
        return false;
    }, function(responce) {
        $('#pagos').startGridEdit();
        $("[id*='_Haber']").attr("readonly", "");
    }, function(responce) {
        $('#pagos').startGridEdit();
        $("[id*='_Haber']").attr("readonly", "");
    });
}

// limpia el formulario y la tabla despues de guardar
function limpiarFormAnticipos() {
    $("#bandera_prov").val("nosel");
    $("#Ant_Doc").val("");
    $("#Ant_Doc_ver").val("");
    $("#Tia_Cod option[data-abr='IN']").prop("selected", true);
    $('#Pag_Cod').prop('selectedIndex', 0);
    $("#Prs_Cod").val("");
    $("#Ant_Doc").val("");
    $("#Cli_Cod").val("");
    $("#Ant_Val").val("0.00");
    $("#Prs_Ced").val("");
    $("#nombre").val("");
    $("#Prs_Dir").val("");
    $("#Ant_Obs").val("");
    $("#Pac_Cto").val("");
    $("#Pac_Num").val("");
    $("#Pac_Val").val("");
    $("#Che_Num").val("");
	$("#Num_Neg").val("");
	$("#Cod_Neg").val("");
    $("#pagos").jqGrid("clearGridData").trigger("reloadGrid");
    $('#pagos').updateGridDiario();
}

//actualiza los totales y debuelve dichos totales
function actualizarTotales() {
    var total = parseFloat($("#Ant_Val").val());
    total += parseFloat($("#Pac_Val").val());

    //obtener todos los ids para buscar valores de debe y haber
    var ids = $('#pagos').jqGrid('getDataIDs');
    var tot_obj = new Object();
    var debe = 0.00;
    var haber = 0.00;
    for (i = 0; i < ids.length; i++) {
        //
        if ($('#' + ids[i] + '_Debe').val() != undefined) {
            debe += parseFloat($('#' + ids[i] + '_Debe').val());
        }
        if ($('#' + ids[i] + '_Haber').val() != undefined) {
            haber += parseFloat($('#' + ids[i] + '_Haber').val());
        }
    }
    tot_obj['debe'] = debe;
    tot_obj['haber'] = haber;
    return tot_obj;
}

let id_ccambiar_cuenta = "";

function AbrirCuentas(row) {
    id_ccambiar_cuenta = row.index;
    $('#cuentasDialog').dialog('open');
}

function cambiarCuenta(row) {
    $('#pagos').jqGrid('setCell', id_ccambiar_cuenta, 'Pld_Cod', row.Pld_Cod);
    $('#pagos').jqGrid('setCell', id_ccambiar_cuenta, 'Pld_Cdc', row.Pld_Cdc);
    $('#pagos').jqGrid('setCell', id_ccambiar_cuenta, 'Pld_Des', row.Pld_Des);
    $('#cuentasDialog').dialog('close');
}

function cambioPreiodoSearch(parm_peri) {
    if (parm_peri === 'peri') {
        $("#txt_fec_ini").dateLimits($("#periodos option:selected").attr("data-inicio"), $("#periodos option:selected").attr("data-fin"));
        $("#txt_fec_ini").val($("#periodos option:selected").attr("data-inicio"));
        $("#txt_fec_fin").dateLimits($("#txt_fec_ini").val(), $("#periodos option:selected").attr("data-fin"));
    } else {
        $("#txt_fec_fin").dateLimits($("#txt_fec_ini").val(), $("#periodos option:selected").attr("data-fin"));
    }
}

$.fn.createInputDiario3 = function(element, tipo) {
    var jgrid = this,
        rowId = $(element).closest('tr.jqgrow').attr('id'),
        tip = jgrid.jqGrid('getCell', rowId, 'Det_Tip');
    $(element).parent().removeAttr("title");
    if (tip === tipo) {
        $(element).on('change', function() {
            var totalesgrid = actualizarTotales();
            var hab = totalesgrid.haber;
            var deb = totalesgrid.debe;
            if (deb !== hab) { hab = deb; }
            $("#Ant_Val").val(parseFloat(hab).toFixed(2));
            $("[id*='_Haber']").val($("#Ant_Val").val());
            $(this).val($.toFixed($(this).val()));
            jgrid.updateGridDiario();
        });
        $(element).attr('onkeypress', 'return  validar_decimal(event)');
        if (parseFloat($(element).val()) === 0) $(element).val("");
        $(element).css('text-align', 'right').focus();
    } else { $(element).parent().html(''); };
};