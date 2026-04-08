let ids_pagos = 0;

$(function () {
    // **********************datepickers y tabs *************************
    $('.datepicker').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });

    //***********************lanzar eventos de elementos
    $("#Pec_Cod").trigger("onchange");
    $("#tabs_abo_det").tabs();

    //***********************creacion de dialogos************************
    $("#successDialog").createDialog({ width: 500, height: 200, icon: 'print' });

    $("#optExedDialog").createDialog({ width: 500, height: 200, icon: 'warning-sign' });

    $("#anular_abo_dialog").createDialog({ width: 300, height: 150, icon: 'warning-sign' });

    $("#verPagosDialogMod").createDialog({ width: 700, height: 450, icon: 'info-sign' });
    $("#verFactsDialog").createDialog({ width: 700, height: 400, icon: 'info-sign' });

    $("#searchGrid").createGrid({
        postData: $("#searchCcpp").getData("ajaxComprobante"),
        height: 300,
        mtype: "GET",
        datatype: "local",
        regional: 'es',
        shrinkToFit: true,
        colModel: [
            { label: 'Cód.Int.', name: 'Cpp_Cod', key: true, hidden: true, viewable: true },
            { label: 'Cód.Int.', name: 'Asi_Cod', hidden: true },
            { label: 'Pld_Cod.', name: "Pld_Cod", hidden: true },
            { label: 'Pld_Cdc.', name: "Pld_Cdc", hidden: true },
            { label: 'Pld_Des.', name: "Pld_Des", hidden: true },
            { label: 'No. Compr.', name: 'Com_Codigo', align: "center", width: 25 },
            { label: 'Fecha Emis.', name: 'Cop_Fec', align: "center", width: 30 },
            { label: 'Fecha Venc.', name: 'Cpp_Ven', align: "center", width: 30 },
            { label: 'Vencimiento', name: 'vencimiento', align: "center", width: 25 },
            {
                label: 'Total',
                name: 'Asi_Val',
                width: 30,
                align: 'right',
                formatter: 'currency',
                decimalPlaces: '2',
                summaryRound: 2,
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.' },
                summaryTpl: "Total: {0}",
                summaryType: "sum"
            },
            {
                label: 'Abono',
                name: 'Abono',
                width: 30,
                align: 'right',
                decimalPlaces: '2',
                summaryRound: 2,
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.' },
                formatter: function (cellValue, options, rowObject) { if (!parseFloat(rowObject.Abono)) rowObject.Abono = 0; return $.fn.fmatter.call(this, "currency", rowObject.Abono, options); },
                unformat: function (cellValue, options, cell) {
                    let opt = $.extend(true, {}, options);
                    opt.colModel.formatter = "currency";
                    delete opt.colModel.unformat;
                    return $.unformat.call(this, cell, opt);
                },
                summaryTpl: "Total: {0}",
                summaryType: "sum" // set the formula to calculate the summary type
            },
            {
                label: 'Saldo',
                name: 'Saldo',
                width: 30,
                align: 'right',
                decimalPlaces: '2',
                summaryRound: 2,
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.' },
                formatter: function (cellValue, options, rowObject) {
                    if (!parseFloat(rowObject.Saldo)) {
                        rowObject.Saldo = parseFloat(rowObject.Asi_Val) - parseFloat(rowObject.Abono);
                    }
                    if (parseFloat(rowObject.Abono) === parseFloat(rowObject.Asi_Val)) return 'Pagado';
                    else
                        return $.fn.fmatter.call(this, "currency", rowObject.Saldo, options);
                },
                unformat: function (cellValue, options, cell) {
                    let opt = $.extend(true, {}, options);
                    opt.colModel.formatter = "currency";
                    delete opt.colModel.unformat;
                    return $.unformat.call(this, cell, opt);
                },
                summaryTpl: "Total: {0}",
                summaryType: "sum" // set the formula to calculate the summary type
            },
            { label: 'No. Docum.', name: 'Cop_Num', width: 45, align: "center" },
            { label: 'Obs. Docum.', name: 'Cop_Obs', width: 55 },

            { label: 'Prv_Cod.', name: "Prv_Cod", hidden: true },
            { label: 'Prs_Ape.', name: "Prs_Ape", hidden: true },
            { label: 'Prs_Nom.', name: "Prs_Nom", hidden: true },
            { label: 'Proveedor', name: 'proveedor', width: 75 }
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
        subGridRowExpanded: function (subgrid_id, row_id) {
            let subgrid_table_id = subgrid_id + "_t";
            let cpp_data = jQuery('#searchGrid').jqGrid('getRowData', row_id);
            $("#" + subgrid_id).html("<table id='" + subgrid_table_id + "' class='scroll'></table>");
            $("#" + subgrid_table_id).createGrid({
                url: "?abonosDetAjax=" + cpp_data.Cpp_Cod,
                datatype: "json",
                regional: 'es',
                height: 'auto',
                colModel: [
                    { label: '', name: 'indx', key: true, hidden: true },
                    { label: '', name: 'Com_Cod', hidden: true },
                    { label: '', name: 'Tia_Cod', hidden: true },
                    { label: '', name: 'Com_Num', hidden: true },
                    { label: '', name: 'Cpp_Cod', hidden: true },
                    { label: '', name: 'reg_editable', hidden: true },
                    { label: '', name: 'ndc', hidden: true },
                    { label: '', name: 'Com_Val', hidden: true },
                    { label: '', name: 'Com_Con', hidden: true },
                    { label: '', name: 'Com_Obs', hidden: true },
                    { label: '', name: 'Pec_Cod', hidden: true },
                    { label: '', name: 'Pag_Abr', hidden: true },
                    { label: 'No. Compr.', name: 'codigo_compro', width: 30, align: "left" },
                    { label: 'Fecha', name: 'Com_Fec', width: 50, align: "left" },
                    {
                        label: 'Valor',
                        name: 'Pag_Val',
                        width: 50,
                        align: 'right',
                        formatter: 'currency',
                        formatoptions: {
                            prefix: '$ ',
                            thousandsSeparator: ',',
                            decimalSeparator: '.',
                            defaultValue: ''
                        }
                    },
                    {
                        label: 'Observaci&oacute;n', name: 'Pag_Obs', width: 140, align: "left",
                        formatter: function (cellValue, options, rowObject) {
                            return cellValue + (rowObject.Num_Doc ? " / Transf. N#: " + rowObject.Num_Doc : "");
                        }
                    },
                    { label: 'T. Pago', name: 'Pag_Des', width: 30, align: "left" },
                    {
                        label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
                        name: 'btns_anti',
                        width: 25,
                        align: 'center',
                        viewable: false,
                        formatter: function (cellvalue, options, rowObject) {
                            let parm_anu = [rowObject, "" + subgrid_table_id];
                            let parm_getdet = [rowObject, row_id];

                            if (rowObject.Pag_Abr == "RET") {
                                return $.getGridButton(verAbono, parm_getdet, 'Ver abono', 'info-sign', '', 'info');
                            }
                            else {
                                //para verificar si se trata de un abono negativo en caso de cheques protestados
                                if (rowObject.reg_editable === "s") {
                                    return $.getGridButton(verAbono, parm_getdet, 'Ver abono', 'info-sign', '', 'info') + "&nbsp;" +
                                        (rowObject.conci=='N'?($.getGridButton(modificarAbono, parm_getdet, 'Modificar abono', 'pencil', '', 'success') +"&nbsp;"+ $.getGridButton(preanularAbono, parm_anu, 'Anular abono', 'trash', '', 'danger')): $.getGridButton('',null,'CONCILIADO','fa-university',null,'warning',null));
                                        
                                        
                                }
                                if (rowObject.reg_editable === "n" && rowObject.ndc === "s") {
                                    return $.getGridButton(verAbono, parm_getdet, 'Ver abono', 'info-sign', '', 'info') + "&nbsp;" +
                                        (rowObject.conc=='N'?$.getGridButton(preanularAbono, parm_anu, 'Anular abono', 'trash', '', 'danger'): $.getGridButton('',null,'CONCILIADO','fa-university',null,'warning',null));
                                }
                                if (rowObject.reg_editable === "n" && rowObject.ndc === "n") {
                                    return $.getGridButton(verAbono, parm_getdet, 'Ver abono', 'info-sign', '', 'info');
                                }
                            }


                        }
                    }
                ],
                loadComplete: function () {
                    //
                },
                beforeSelectRow: function (rowid, e) { return false; },
                rowNum: 10000,
                pager: ""
            });
        },
        loadComplete: function (data) {

            actualizarTotalesSG();

            $('#searchGrid tr').each(function () {
                if ($(this).find("td").eq(10).text() === "Vencido") {
                    $(this).addClass("cellRed2");
                    $(this).addClass("myAltRowClass");
                }
                if ($(this).find("td").eq(10).text() === "Pagado") {
                    $(this).css("background", "#DDFAE2");
                    $(this).addClass("myAltRowClass");
                }
            });

        },
        pager: "#sgPager",
        rownumbers: true,
        rowNum: 10000,
        gridview: true,
        viewrecords: true,
        footerrow: true,
        userDataOnFooter: false,
        onSelectRow: function (rowid, e) { $("#searchGrid").resetSelection(); },
        multiselect: false
    }, false, "#sgPager");
    $("#searchGrid").updateGridsSizes();
    $("#pagosGrid").updateGridsSizes();

    //tabla de cheques
    $('#showPagosChe').createGrid({
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
            { label: '', name: 'Pld_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Atp_Fec', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Che_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Prv_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Ban_Cod', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Asi_Cod', hidden: true, classes: 'bgNoRight' },
            { label: 'No. Che.', name: 'Che_Num', width: 15, align: "left" },
            { label: 'No. Cta.', name: 'Pap_Cto', width: 20, align: "left" },
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
                formatter: function (cellvalue, options, rowObject) {
                    if (rowObject.Che_Est === 'P') {
                        return "-";
                    } else {
                        return $.getGridButton(preProtestarCheque, rowObject, 'Marcar como protestado', 'ban-circle', '', 'danger');
                    }
                }
            }
        ]
    }, true, '', { view: false });

    //tabala para visualizar asientos de un abono
    $('#showPagosAsi').createGrid({
        viewrecords: false,
        caption: "<center>Detalle del Asiento Contable</center>",
        data: [],
        rowNum: 100,
        height: 100,
        width: 650,
        footerrow: true,
        responsive: false,
        onSelectRow: function (rowid, e) { $(this).resetSelection(); },
        colModel: [
            { label: 'index', name: 'index', hidden: true, classes: 'bgNoRight' },
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

    //Dialog buscar clientes
    $.createSearchDialog('proveedoresDialog', [
        { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 15, align: "center", hidden: true },
        { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
        { label: 'Proveedor', name: 'nombre', width: 100 },
        { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
        { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectProveedor } }
    ], null, null, null, { headertitles: true }, { title: 'Proveedor', text: 'searchPrv' });

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
                formatter: function (cellvalue, options, rowObject) {
                    return $.getGridButton(cambiarCuenta, rowObject, 'Seleccionar cuenta', 'check', '', 'success');
                }
            }
        ], null, null, null, null, { title: 'Cuenta', options: [{ label: '&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;', value: 'd' }, { label: '&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;', value: 'c' }] })
            .find('.form-group-options').append('<div class="col-md-4"> <label class="control-label label-xs">Plan de Cuentas:</label><input name="Periodo" type="text" size="6" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /> <input name="Pec_Cod" type="hidden" /><input name="Asi_Cod" type="hidden" /></div>');
    cargarFactsGrid();
});

function cargarFactsGrid() {
    $('#factsGrid').createGrid({
        viewrecords: false,
        caption: "<center>Facturas del Abono</center>",
        data: [],
        rowNum: 100,
        height: 150,
        width: 650,
        footerrow: true,
        responsive: false,
        onSelectRow: function (rowid, e) { $(this).resetSelection(); },
        colModel: [
            { label: '', name: 'Cpp_Cod', hidden: true, key: true },
            { label: '', name: 'Com_Cod', hidden: true },
            { label: '', name: 'flag', hidden: true },
            { label: 'No. Compr.', name: 'Com_Codigo', width: 15, align: "left" },
            { label: 'Fecha Emis.', name: 'Cop_Fec', width: 20, align: "left" },
            { label: 'Fecha Venc.', name: 'Cpp_Ven', width: 20, align: "left" },
            { label: 'No. Doc.', name: 'Cop_Num', width: 20, align: "left" },
            {
                label: 'Total',
                name: 'total',
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
            {
                label: 'Pagado',
                name: 'Abono',
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
            { label: 'Saldo', name: 'Saldo', width: 15, align: 'right', formatter: 'currency' },
            {
                label: '<i class="ui-icon ui-icon-circle-check"></i>',
                name: 'fctact',
                width: 6,
                align: 'center',
                viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    return '<input id="fct_act_' + rowObject.Cpp_Cod + '" type="checkbox" value="" onChange="setFactCell(\'' + rowObject.Cpp_Cod + '\')" offval="no" checked="true">';
                },
                title: false,
                formatoptions: { disabled: false },
                resizable: false
            },
            $.originalRow()
        ]
    }, true, '', { view: false });
}

function cambiarFiltro() {
    if ($("#f_periodo").prop('checked')) {
        $("#por_peri").val("s");
        $("#radsc1").click();

        $("#sel_ven").attr("disabled", "");
        $("#radsc1").attr("disabled", "");
        $("#radsc2").attr("disabled", "");
        $("#radsc3").attr("disabled", "");

        $("#sel_per").removeAttr("disabled");
        $("#txt_fec_fin").removeAttr("disabled");
        $("#txt_fec_ini").removeAttr("disabled");
    } else {
        $("#por_peri").val("n");

        $("#sel_per").attr("disabled", "");
        $("#txt_fec_fin").attr("disabled", "");
        $("#txt_fec_ini").attr("disabled", "");

        $("#sel_ven").removeAttr("disabled");
        $("#radsc1").removeAttr("disabled");
        $("#radsc2").removeAttr("disabled");
        $("#radsc3").removeAttr("disabled");
    }
}

function cambioPreiodoSearch(parm_peri) {
    if (parm_peri === 'peri') {
        $("#txt_fec_ini").dateLimits($("#sel_per option:selected").attr("data-inicio"), $("#sel_per option:selected").attr("data-fin"));
        $("#txt_fec_ini").val($("#sel_per option:selected").attr("data-inicio"));
        $("#txt_fec_fin").dateLimits($("#txt_fec_ini").val(), $("#sel_per option:selected").attr("data-fin"));
    } else {
        $("#txt_fec_fin").dateLimits($("#txt_fec_ini").val(), $("#sel_per option:selected").attr("data-fin"));
    }
}

function preProtestarCheque(row) {
    $.createDialogConfirm('�Est&aacute; seguro que desea marcar como protestado este cheque?', row, protestarCheque);
}

//funcion que permite protestar un cheque, desactivar el pago respectivo y modificar el total del anticipo
function protestarCheque(row) {
    $.post("", { protestarChe: true, Che_Fec: row.Che_Fec, Pld_Cod: row.Pld_Cod, Com_Cod: $("#Com_Cod_view").val(), Che_Num: row.Che_Num, Che_Val: row.Che_Val, Che_Cod: row.Che_Cod, Prv_Cod: $("#Prv_Cod").val(), Ban_Cod: row.Ban_Cod, Asi_Cod: row.Asi_Cod }, function (responce) {
        if (responce['success'] === true) {
            if (responce['pec_ban'] === "si") {
                $("#searchGrid").trigger("reloadGrid");
                $('#verPagosDialogMod').dialog('close');
                $('#impCompr').attr('href', responce['link']);
                $('#successDialog').dialog('open');
            } else {
                $.alert(responce['message']);
            }
        } else {
            $.alert(responce['message']);
        }
    }, 'json')
        .fail(function (error) {
            $.alert("El Servidor ha fallado en responder!");
        });
}

function recTotAPagar() {
    /* let ids= $('#factsGrid').jqGrid('getDataIDs');
    let tot_a_pagar=0.00;
    let glo_pag="ABONO FACTS. ";
    for(let i=0; i< ids.length; i++){
      let reg_pago = $('#factsGrid').jqGrid('getRowData',ids[i]);
      if($("#fct_act_"+ids[i]).prop('checked')){
        tot_a_pagar+=parseFloat(reg_pago.Abono);
        glo_pag+="/"+reg_pago.Cop_Num;
      }
    }
    $("[id*='_Debe']").val(''+tot_a_pagar.toFixed(2));
    $("[id*='_Glosa']").val(glo_pag);

    $("#Com_Val").val(""+tot_a_pagar.toFixed(2));

    $("#saldo_info1").text(""+tot_a_pagar.toFixed(2));

    if(parseFloat($("#saldo_info1").text()) === parseFloat($("#Com_Val").val())){
      $("#saldo_info").removeClass("txt-red");
      $("#saldo_info").addClass("txt-green");
    }else{
      $("#saldo_info").removeClass("txt-green");
      $("#saldo_info").addClass("txt-red");
    } */

    totalPagos();
}

function setFactCell(fact) {
    let flag_del = 0;
    let ids = $('#factsGrid').jqGrid('getDataIDs');
    for (let i = 0; i < ids.length; i++) {
        let reg_pago = $('#factsGrid').jqGrid('getRowData', ids[i]);
        if ($("#fct_act_" + ids[i]).prop('checked')) {
            flag_del++;
        }
    }
    if (flag_del >= 1) {
        if ($("#fct_act_" + fact).prop('checked')) {
            $('#factsGrid').jqGrid('setCell', fact, 'flag', "s");
        } else {
            $('#factsGrid').jqGrid('setCell', fact, 'flag', "n");
        }
        var data = $('#factsGrid').getSelectedByCol('flag', "s"),
            abonos = $('#pagosGrid').getSelectedByCol('grid_tipp', "inicial");
        $("#saldo_info1").html($.numFormat($("#Com_Val").val($.arraySumVal(data, 'Abono')).val(), 'number'));
        $.each(abonos, function (i, o) {
            $('#pagosGrid').jqGrid('delRowData', o.index);
        });
        var array = [];
        $.each(data, function (i, o) {
            var add = true;
            $.each(array, function (j, b) {
                if (b.Pld_Cod === o.OriginalData.Pld_Cod) {
                    array[j]['Abono'] = array[j]['Abono'] * 1 + o.OriginalData.Abono * 1;
                    add = false;
                    return false;
                }
            });
            if (add) array.push(o.OriginalData);
        });
        $.each(array, function (i, v) { addPago(["D", "inicial", "", "", "", "", "", "", "", "", "", "", v.Pld_Cod, v.Pld_Cdc, v.Pld_Des, v.Abono, "", v.Asi_Con, "first", ""]); });
        totalPagos();
    } else {
        $.alert("No puede descartar todas las facturas");
        $("#fct_act_" + fact).prop('checked', true);
    }
}

//ver facturas ligadas a un abono
function verFacturasAbono(compro) {
    $("#factsGrid").jqGrid("clearGridData").trigger("reloadGrid");
    cargarFactsGrid();
    $.post("", { getFactsAbono: true, Com_Cod: $("#Com_Cod").val(), Prv_Cod: $("#agg_Prv_Cod").val() }, function (responce) {
        if (responce['success'] === true) {
            for (let i = 0; i < responce['data'].length; i++) {
                let cpp_data = $('#searchGrid').jqGrid('getRowData', responce['data'][i].Cpp_Cod);
                let saldo_mod = "0";
                if (isNaN(cpp_data.Saldo)) {
                    saldo_mod = "0.00";
                } else {
                    saldo_mod = cpp_data.Saldo;
                }
                console.log();
                $('#factsGrid').jqGrid('addRowData', responce['data'][i].Cpp_Cod, {
                    Cpp_Cod: responce['data'][i].Cpp_Cod,
                    Com_Cod: cpp_data.Com_Cod,
                    flag: "s",
                    Com_Codigo: cpp_data.Com_Codigo,
                    Cop_Fec: cpp_data.Cop_Fec,
                    Cpp_Ven: cpp_data.Cpp_Ven,
                    Cop_Num: cpp_data.Cop_Num,
                    total: cpp_data.Asi_Val,
                    Abono: responce['data'][i].Abono,
                    Saldo: saldo_mod
                }, "last");
            }
        } else {
            $.alert(responce['message']);
        }
    }, 'json')
        .fail(function (error) {
            $.alert("El Servidor ha fallado en responder!");
        });
}


function createPagosGrid() {
    $('#pagosGrid').createGrid({
        viewrecords: false,
        data: [],
        rowNum: 100,
        height: 150,
        footerrow: true,
        onSelectRow: function (rowid, e) { $(this).resetSelection(); },
        colModel: [{
            label: '<center><span class="glyphicon glyphicon-check"></span></center>',
            name: 'sel_item',
            width: 5,
            align: 'center',
            viewable: false,
            formatter: function (cellvalue, options, rowObject) {
                if (rowObject.Pag_Abr === 'OTR') {
                    return $.getGridButton(AbrirCuentas, rowObject, 'Cambiar cuenta', 'check', '', 'success');
                } else {
                    return "-";
                }
            },
            title: false
        },
        { label: 'index', name: 'index', key: true, hidden: true, classes: 'bgNoRight' },
        { label: ' ', name: 'Det_Tip', hidden: true },
        { label: ' ', name: 'Che_Est', hidden: true },
        { label: ' ', name: 'grid_tipp', hidden: true },
        { label: ' ', name: 'Che_Cod', hidden: true },
        { label: 'Asi_Cod', name: 'Asi_Cod', hidden: true, classes: 'bgNoRight' },
        { label: 'Pag_Cod', name: 'Pag_Cod', hidden: true, classes: 'bgNoRight' },
        { label: 'Pag_Abr', name: 'Pag_Abr', hidden: true, classes: 'bgNoRight' },
        { label: 'Tipo', name: 'Pag_Des', width: 10, align: "center", classes: 'bgNoRight' },
        { label: 'Ban_Cod', name: 'Ban_Cod', hidden: true, classes: 'bgNoRight' },
        { label: 'No. che.', name: 'Che_Num', width: 10, classes: 'bgNoRight' },
        { label: 'Che_Fec', name: 'Che_Fec', hidden: true, classes: 'bgNoRight' },
        { label: 'Pap_Cto', name: 'Pap_Cto', hidden: true, classes: 'bgNoRight' },
        { label: 'Pap_Ctd', name: 'Pap_Ctd', hidden: true, classes: 'bgNoRight' },
        { label: 'Cuenta_Pld', name: 'Pld_Cod', hidden: true, classes: 'bgNoRight' },
        { label: 'C&oacute;digo', name: 'Pld_Cdc', width: 10, classes: 'bgNoRight' },
        { label: 'Cuenta', name: 'Pld_Des', width: 20, classes: 'bgNoRight' },
        { label: '', name: 'concepto', hidden: true },
        { label: 'Glosa', name: 'Glosa', width: 20, editable: true },
        {
            label: 'Debe',
            name: 'Debe',
            width: 10,
            align: 'right',
            formatter: 'currency',
            editable: true,
            formatoptions: { defaultValue: '' },
            editoptions: { dataInit: function (element) { $(this).createInputDiario3(element, "D", "Det_Tip"); } }
        },
        {
            label: 'Haber',
            name: 'Haber',
            width: 10,
            align: 'right',
            formatter: 'currency',
            editable: true,
            formatoptions: { defaultValue: '' },
            editoptions: { dataInit: function (element) { $(this).createInputDiario3(element, "H", "Det_Tip"); } }
        },
        {
            label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
            name: 'Pag_Item',
            width: 5,
            align: 'center',
            viewable: false,
            formatter: function (cellvalue, options, rowObject) {
                if (rowObject.grid_tipp === 'inicial' || rowObject.Che_Est === "P") {
                    return "-";
                } else {
                    return $.getGridButton(delPago, rowObject, 'Borrar pago', 'remove', '', 'danger');
                }
            },
            title: false
        }
        ],
        loadComplete: function () {

            $(this).jqGrid('footerData', 'set', {
                Glosa: "<div style='text-align:right;'>TOTALES:</div>",
                Debe: $(this).jqGrid('getCol', 'Debe', true, 'sum'),
                Haber: $(this).jqGrid('getCol', 'Haber', true, 'sum')
            }, true);
        }
    }, true, 'pagosPager', { view: false });
}

function disableDebe() {
    var ids_p = $('#pagosGrid').jqGrid('getDataIDs');
    for (let i = 0; i < ids_p.length; i++) {
        let reg_pago = $('#pagosGrid').jqGrid('getRowData', ids_p[i]);
        if (reg_pago.Pag_Abr === "CHE" && reg_pago.Che_Est === "P") {
            // $('#pagosGrid').setColProp('Debe',{editable:false});
            // $('#pagosGrid').jqGrid('editRow', ids_p[i], {editable:false});
            $("#" + ids_p[i] + "_Haber").attr("readonly", "");
            $("#" + ids_p[i] + "_Glosa").attr("readonly", "");
        }
    }

    $('#pagosGrid tr').each(function () {
        if ($(this).find("td").eq(4).text() === "P") {
            $(this).addClass("cellRed2");
            $(this).addClass("myAltRowClass");
        }
    });
}

function verAbono(row) {
    $("#ndc_info").attr("hidden", "");

    $("#showPagosAsi").jqGrid("clearGridData").trigger("reloadGrid");
    $("#showPagosChe").jqGrid("clearGridData").trigger("reloadGrid");

    $("#ant_detasi").children("a").trigger("click");

    $("#prov_show").val($("#nombre").val());
    $("#ruc_show").val($("#Prs_Ced").val());
    $("#compr_show").val(row[0].codigo_compro);
    $("#fec_show").val(row[0].Com_Fec);
    $("#obs_show").val(row[0].Com_Obs);
    $("#Com_Cod_view").val(row[0].Com_Cod);

    if (row[0].ndc === "n") {
        $("#ndc_info").removeAttr("hidden");
    }

    $('#verPagosDialogMod').dialog('open');
    $('div#verPagosDialog').siblings('.ui-dialog-titlebar').children('span').text($("#nombre").val());
    $.post("", { getAsientosAbono: true, Com_Cod: row[0].Com_Cod }, function (responce) {
        if (responce['success'] === true) {
            //agregamos asientos
            for (let i = 0; i < responce['data'].length; i++) {
                let ids_pg = $('#showPagosAsi').jqGrid('getDataIDs').length + 1;
                let a_deb = "",
                    a_hab = "";

                if (responce['data'][i].Asi_Deh === 'D') {
                    a_deb = responce['data'][i].Asi_Val;
                    a_hab = "";
                } else {
                    a_deb = "";
                    a_hab = responce['data'][i].Asi_Val;
                }

                $('#showPagosAsi').jqGrid('addRowData', ids_pg, {
                    index: ids_pg,
                    Pld_Cdc: responce['data'][i].Pld_Cdc,
                    Pld_Des: responce['data'][i].Pld_Des,
                    Glosa: responce['data'][i].Asi_Glo,
                    Debe: a_deb,
                    Haber: a_hab
                }, "last");
            }

            $('#showPagosAsi').jqGrid('footerData', 'set', {
                Glosa: "<div style='text-align:right;'>TOTALES:</div>",
                Debe: $('#showPagosAsi').jqGrid('getCol', 'Debe', true, 'sum'),
                Haber: $('#showPagosAsi').jqGrid('getCol', 'Haber', true, 'sum')
            }, true);

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
                    Che_Est: responce['data_che'][i].Che_Est,
                    Che_Cod: responce['data_che'][i].Che_Cod,
                    Ban_Cod: responce['data_che'][i].Ban_Cod,
                    Asi_Cod: responce['data_che'][i].Asi_Cod,
                    Pld_Cod: responce['data_che'][i].Pld_Cod
                }, "last");
            }
            $('#showPagosChe').jqGrid('footerData', 'set', {
                Che_Obs: "<div style='text-align:right;'>TOTAL:</div>",
                Che_Val: $('#showPagosChe').jqGrid('getCol', 'Che_Val', true, 'sum')
            }, true);
            $('#showPagosChe tr').each(function () {
                if ($(this).find("td").eq(2).text() === "P") {
                    $(this).addClass("cellRed2");
                    $(this).addClass("myAltRowClass");
                }
            });
        } else {
            console.log(responce['message']);
        }
    }, 'json')
        .fail(function (error) {
            console.log("El Servidor ha fallado en responder!");
        });
    document.getElementById("compr_show").focus();
}

function preanularAbono(row) {
    //$( "#anular_abo_dialog" ).dialog('open');
    $.createDialogConfirm('�Est&aacute; seguro que desea anular este Abono?', row, anularAbono);
}

function anularAbono(row) {
    $.post("", { delAbono: true, Com_Cod: row[0].Com_Cod, Pag_Abr: row[0].Pag_Abr, Cpp_Cod: row[0].Cpp_Cod }, function (responce) {
        if (responce['success'] === true) {
            $("#" + row[1]).trigger("reloadGrid");
            $.alert("Abono anulado con &eacute;xito!");
        } else {
            $.alert(responce['message']);
        }
    }, 'json')
        .fail(function (error) {
            $.alert("El Servidor ha fallado en responder!");
        });
}

let id_ccambiar_cuenta = "";

function AbrirCuentas(row) {
    id_ccambiar_cuenta = row.index;
    $('#cuentasDialog').dialog('open');
}

function cambiarCuenta(row) {
    $('#pagosGrid').jqGrid('setCell', id_ccambiar_cuenta, 'Pld_Cod', row.Pld_Cod);
    $('#pagosGrid').jqGrid('setCell', id_ccambiar_cuenta, 'Pld_Cdc', row.Pld_Cdc);
    $('#pagosGrid').jqGrid('setCell', id_ccambiar_cuenta, 'Pld_Des', row.Pld_Des);
    $('#cuentasDialog').dialog('close');
}

function enableDisableCampos() {
    $(".ed_element").attr("disabled", "");
    $(".ed_" + $("#Pag_Cod option:selected").attr("data-abr")).removeAttr("disabled");

    $("#cont_anticipo_info").attr("hidden", "");
    $("#cont_ccc_info").attr("hidden", "");

    if ($("#Pag_Cod option:selected").attr("data-abr") === 'ANT') {
        $("#cont_anticipo_info").removeAttr("hidden");
        $.post("", { getAnticipoCantAjax: true, Prv_Cod: $("#agg_Prv_Cod").val() }, function (responce) {
            if (responce['success'] === true) {
                if (responce['data'] === 'none' || responce['data_ant'] === 0) {
                    $("#anticipo_info").text("0.00");
                    $("#lim_val_pago").val("none");
                } else {
                    $("#lim_val_pago").val("" + parseFloat(responce['data']).toFixed(2));
                    $("#anticipo_info").text("" + parseFloat(responce['data']).toFixed(2));
                    $("#Com_Val_pago").val("" + parseFloat(responce['data']).toFixed(2));
                }
            } else {
                $.alert(responce['message']);
            }
        }, 'json')
            .fail(function (error) {
                $.alert("El Servidor ha fallado en responder!");
            });
    } else if ($("#Pag_Cod option:selected").attr("data-abr") === 'CDC') {
        $("#cont_ccc_info").removeAttr("hidden");
        $.post("", { getCccAjax: true, Prv_Cod: $("#agg_Prv_Cod").val(), Pec_Cod: $("#Pec_Cod").val() }, function (responce) {
            if (responce['success'] === true) {
                if (responce['data'] === 'none') {
                    $("#ccc_info").text("0.00");
                    $("#lim_val_pago_cc").val("none");
                } else {
                    $("#lim_val_pago_cc").val("" + parseFloat(responce['data']).toFixed(2));
                    $("#ccc_info").text("" + parseFloat(responce['data']).toFixed(2));
                    $("#Com_Val_pago").val("" + parseFloat(responce['data']).toFixed(2));
                }
            } else {
                $.alert(responce['message']);
            }
        }, 'json')
            .fail(function (error) {
                $.alert("El Servidor ha fallado en responder!");
            });
    } else {
        $("#Com_Val_pago").val((parseFloat($("#Com_Val").val()) - parseFloat(totalPagos().haber)).toFixed(2));
    }

    if($("#Pag_Cod option:selected").attr("data-abr") === 'DEP' || $("#Pag_Cod option:selected").attr("data-abr") === 'TRF' || $("#Pag_Cod option:selected").attr("data-abr") === 'NDD'){
        // console.log("Deposito seleccionado");
        $("#Num_Doc").prop("disabled", false);
    } else {
        $("#Num_Doc").attr("disabled", "");
    }
}

function cambioValPago(elemento) {
    if (elemento.val() != '') {
        if ($("#Pag_Cod option:selected").attr("data-abr") === 'ANT') {
            if ($("#lim_val_pago").val() !== "none") {
                if (parseFloat(parseFloat(elemento.val()).toFixed(2)) <= parseFloat(parseFloat($("#lim_val_pago").val()).toFixed(2))) {
                    elemento.val(parseFloat(elemento.val()).toFixed(2));
                } else {
                    elemento.val(parseFloat($("#lim_val_pago").val()).toFixed(2));
                }
            } else {
                elemento.val("0.00");
            }
        } else if ($("#Pag_Cod option:selected").attr("data-abr") === 'CDC') {
            if ($("#lim_val_pago_cc").val() !== "none") {
                if (parseFloat(parseFloat(elemento.val()).toFixed(2)) <= parseFloat(parseFloat($("#lim_val_pago_cc").val()).toFixed(2))) {
                    elemento.val(parseFloat(elemento.val()).toFixed(2));
                } else {
                    elemento.val(parseFloat($("#lim_val_pago_cc").val()).toFixed(2));
                }
            } else {
                elemento.val("0.00");
            }
        } else {
            elemento.val(parseFloat(elemento.val()).toFixed(2));
        }
    } else {
        elemento.val("0.00");
    }
}

function addPagoIni() {
    $.post("", { getPagoIniAjax: true }, function (responce) {
        if (responce['success'] === true) {
            let prm_array = ["D", "inicial", "", "", "", "", "", "", "", "", "", "",
                responce['data'].Pld_Cod, responce['data'].Pld_Cdc,
                responce['data'].Pld_Des, $("#Com_Val").val(), "", $("#Com_Con").val(), "first", ""];
            addPago(prm_array);
        } else {
            $.alert(responce['message']);
        }
    }, 'json')
        .fail(function (error) {
            $.alert("El Servidor ha fallado en responder!");
        });
}

let numeroCheque = false;
//verifica si el numero de un cheque ya se encuentra registrado
function verificarNoCheque(valor) {
    datach = { "verificarCheNum": true, "Che_Num": valor, "Ban_Cod": $("#Ban_Cod option:selected").attr("value") };
    $.post("", datach, function (response) {
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

function incrementarSaldoInfo() {
    let saldo_info = 0.00;
    saldo_info = saldo_info + parseFloat($("#Com_Val_pago").val());
    saldo_info = saldo_info + parseFloat($("#saldo_info2").text());
    $("#saldo_info2").text(parseFloat(saldo_info).toFixed(2));
    if (parseFloat($("#saldo_info1").text()) === parseFloat($("#saldo_info2").text())) {
        $("#saldo_info").removeClass("txt-red");
        $("#saldo_info").addClass("txt-green");
    } else {
        $("#saldo_info").removeClass("txt-green");
        $("#saldo_info").addClass("txt-red");
    }
}

function preAddPago() {
    let prm_array;

    if ($("#Pag_Cod option:selected").attr("data-abr") === "EFE" || $("#Pag_Cod option:selected").attr("data-abr") === "OTR" || $("#Pag_Cod option:selected").attr("data-abr") === "DEP" || $("#Pag_Cod option:selected").attr("data-abr") === "ANT" || $("#Pag_Cod option:selected").attr("data-abr") === "CDC"|| $("#Pag_Cod option:selected").attr("data-abr") === "RC") {
        $.post("", { getPagoCtaAjax: true, tipo: "" + $("#Pag_Cod option:selected").attr("data-abr") }, function (responce) {
            if (responce['success'] === true) {
                if ($("#pagosGrid td:contains('EFE')").text().search("EFE") == -1 && $("#Pag_Cod option:selected").attr("data-abr") === "EFE") {
                    prm_array = ["H", "pago", "", "", $("#Pag_Cod").val(), $("#Pag_Cod option:selected").attr("data-abr"), $("#Pag_Cod option:selected").text(), "", "", "", "", "", responce['data'].Pld_Cod, responce['data'].Pld_Cdc, responce['data'].Pld_Des, "", $("#Com_Val_pago").val(), $("#Com_Con").val(), "last", ""];
                    incrementarSaldoInfo();
                    addPago(prm_array);
                }
                if ($("#pagosGrid td:contains('DEP')").text().search("DEP") == -1 && $("#Pag_Cod option:selected").attr("data-abr") === "DEP") {
                    prm_array = ["H", "pago", "", "", $("#Pag_Cod").val(), $("#Pag_Cod option:selected").attr("data-abr"), $("#Pag_Cod option:selected").text(), "", "", "", "", "", responce['data'].Pld_Cod, responce['data'].Pld_Cdc, responce['data'].Pld_Des, "", $("#Com_Val_pago").val(), $("#Com_Con").val(), "last", ""];
                    incrementarSaldoInfo();
                    addPago(prm_array);
                }
                if ($("#pagosGrid td:contains('ANT')").text().search("ANT") == -1 && $("#Pag_Cod option:selected").attr("data-abr") === "ANT") {
                    if ($("#lim_val_pago").val() === "none") {
                        $.alert("No tiene anticipos disponibles para este proveedor!");
                    } else {
                        prm_array = ["H", "pago", "", "", $("#Pag_Cod").val(), $("#Pag_Cod option:selected").attr("data-abr"), $("#Pag_Cod option:selected").text(), "", "", "", "", "", responce['data'].Pld_Cod, responce['data'].Pld_Cdc, responce['data'].Pld_Des, "", $("#Com_Val_pago").val(), $("#Com_Con").val(), "last", ""];
                        incrementarSaldoInfo();
                        addPago(prm_array);
                    }
                }
                if ($("#pagosGrid td:contains('CDC')").text().search("CDC") == -1 && $("#Pag_Cod option:selected").attr("data-abr") === "CDC") {
                    if ($("#lim_val_pago_cc").val() === "none") {
                        $.alert("No tiene una cantidad sificiente para el cruce de cuentas con este proveedor!");
                    } else {
                        prm_array = ["H", "pago", "", "", $("#Pag_Cod").val(), $("#Pag_Cod option:selected").attr("data-abr"), $("#Pag_Cod option:selected").text(), "", "", "", "", "", responce['data'].Pld_Cod, responce['data'].Pld_Cdc, responce['data'].Pld_Des, "", $("#Com_Val_pago").val(), $("#Com_Con").val(), "last", ""];
                        incrementarSaldoInfo();
                        addPago(prm_array);
                    }
                }
                if ($("#Pag_Cod option:selected").attr("data-abr") === "OTR") {
                    prm_array = ["H", "pago", "", "", $("#Pag_Cod").val(), $("#Pag_Cod option:selected").attr("data-abr"), $("#Pag_Cod option:selected").text(), "", "", "", "", "", responce['data'].Pld_Cod, responce['data'].Pld_Cdc, responce['data'].Pld_Des, "", $("#Com_Val_pago").val(), $("#Com_Con").val(), "last", ""];
                    incrementarSaldoInfo();
                    addPago(prm_array);
                }
                if ($("#Pag_Cod option:selected").attr("data-abr") === "RC") {//REPOSICION DE CAJA CHICA
                    let data = responce['data'];
                    if (data && data.Pld_Cod) {
                        prm_array = ["H", "pago", "", "", $("#Pag_Cod").val(), $("#Pag_Cod option:selected").attr("data-abr"), $.trim($("#Pag_Cod option:selected").text()), "", "", "", "", "", responce['data'].Pld_Cod, responce['data'].Pld_Cdc, responce['data'].Pld_Des, "", $("#Com_Val_pago").val(), $("#Com_Con").val(), "last", ""];
                        incrementarSaldoInfo();
                        addPago(prm_array);
                    } else {
                        $.alert("Verifica si la cuenta reposicion de caja chica esta parametrizada! Ve a: <span style='color:#0000FF'>(Parametrizacion->Productos->General)<span>");
                    }
                }
            } else {
                $.alert(responce['message']);
            }
        }, 'json')
            .fail(function (error) {
                $.alert("El Servidor ha fallado en responder!");
            });
    }
    if ($("#Pag_Cod option:selected").attr("data-abr") === "CHE") {
        let bandera_cheque_pago = false;
        criterio = $('#pagosGrid').jqGrid("getCol", "Che_Num", false);
        for (let i = 0; i < criterio.length; i++) {
            if ($("#Che_Num").val() == criterio[i]) {
                bandera_cheque_pago = true;
            }
        }
        if (numeroCheque === false) {
            if (bandera_cheque_pago === false) {
                prm_array = [
                    "H", "pago", "", "",
                    $("#Pag_Cod").val(),
                    $("#Pag_Cod option:selected").attr("data-abr"),
                    $("#Pag_Cod option:selected").text(),
                    $("#Ban_Cod option:selected").attr("value"),
                    $("#Che_Num").val(),
                    $("#Che_Fec").val(),
                    "", "",
                    $("#Ban_Cod option:selected").attr("data-pla"),
                    $("#Ban_Cod option:selected").attr("data-cdc"),
                    $("#Ban_Cod option:selected").attr("data-des"),
                    "",
                    $("#Com_Val_pago").val(),
                    $("#Com_Con").val(),
                    "last", ""
                ];
                incrementarSaldoInfo();
                addPago(prm_array);
            } else {
                $.alert("No puede ingresar dos pagos con el mismo n&uacute;mero de cheque");
            }
        } else {
            $.alert("El numero de cheque (" + $("#Che_Num").val() + ") ya fue emitido");
        }
    }
    if ($("#Pag_Cod option:selected").attr("data-abr") === "TDC" || $("#Pag_Cod option:selected").attr("data-abr") === "TRF" || $("#Pag_Cod option:selected").attr("data-abr") === "NDD") {
        prm_array = [
            "H", "pago", "", "",
            $("#Pag_Cod").val(),
            $("#Pag_Cod option:selected").attr("data-abr"),
            $("#Pag_Cod option:selected").text(),
            $("#Ban_Cod option:selected").attr("value"),
            "", "", "", "",
            $("#Ban_Cod option:selected").attr("data-pla"),
            $("#Ban_Cod option:selected").attr("data-cdc"),
            $("#Ban_Cod option:selected").attr("data-des"),
            "",
            $("#Com_Val_pago").val(),
            $("#Com_Con").val()
        ],
            "last", "";
        incrementarSaldoInfo();
        addPago(prm_array);
    }
}

function addPago(prm_array) {
    ids_pagos++;
    $("#pagosGrid").jqGrid('addRowData', ids_pagos, {
        index: ids_pagos,
        Det_Tip: prm_array[0],
        grid_tipp: prm_array[1],
        Che_Cod: prm_array[2],
        Asi_Cod: prm_array[3],
        Pag_Cod: prm_array[4],
        Pag_Abr: prm_array[5],
        Pag_Des: prm_array[6],
        Ban_Cod: prm_array[7],
        Che_Num: prm_array[8],
        Che_Fec: prm_array[9],
        Pap_Cto: prm_array[10],
        Pap_Ctd: prm_array[11],
        Pld_Cod: prm_array[12],
        Pld_Cdc: prm_array[13],
        Pld_Des: prm_array[14],
        concepto: prm_array[17],
        Glosa: prm_array[17],
        Debe: prm_array[15],
        Haber: prm_array[16],
        Pag_Item: "",
        Che_Est: prm_array[19]
    }, "" + prm_array[18]);
    // $("#1_Debe").trigger("onChange");
    $('#pagosGrid').startGridEdit();
    $('#pagosGrid').updateGridDiario();
    if (prm_array[0] === 'D') $("#" + ids_pagos + "_Debe").prop('readonly', true);
    totalPagos();
    disableDebe();
}

function recTotalPagar() {
    let ids = $('#factsGrid').jqGrid('getDataIDs'),
        valor_maximo = 0;
    for (let i = 0; i < ids.length; i++) {
        let reg_pagofc = $('#factsGrid').jqGrid('getRowData', ids[i]);
        if (reg_pagofc.flag === 's') {
            valor_maximo = valor_maximo + (parseFloat(reg_pagofc.Abono) + parseFloat(reg_pagofc.Saldo));
        }
    }
    return valor_maximo;
}

function exedenteDialog() {
    let tots = totalPagos();
    let maxim = recTotalPagar();
    let exedente = parseFloat(tots.debe) - parseFloat(maxim);
    $("#maximo_info").text(maxim.toFixed(2));
    $("#exedente_info").text(exedente.toFixed(2));
    $("#optExedDialog").dialog("open");
}

function preGuardarPago() {
    let tots = totalPagos();
    if (parseFloat(tots.debe).toFixed(2) !== parseFloat(tots.haber).toFixed(2)) { return $.alert("Los <span class='green'>TOTALES</span> no coinciden"); }
    if (parseFloat($("#Com_Val").val()) > parseFloat($("#Com_Val_temp").val())) {
        let v_max = recTotalPagar();
        if (parseFloat(tots.debe) > parseFloat(v_max)) { return exedenteDialog(); }
    }

    $.createDialogConfirm('�Est&aacute; seguro que desea guardar los datos?', "n", guardarPago);
}

function guardarPago(opt_guardar) {
    let data = $('#formPagos').serializeObject();
    let obj_ccpp_mod = new Object();
    let contador_fcsm = 0;
    let disminuir = 0.00;

    if (opt_guardar === "a" || opt_guardar === "f") {
        //en caso de existir un exdente y enviar a anticipos o facturas ese exedente
        let tots = totalPagos();
        let maxim = recTotalPagar();
        disminuir = parseFloat(tots.debe) - parseFloat(maxim);
    }

    let idsfc = $('#factsGrid').jqGrid('getDataIDs');
    for (let i = 0; i < idsfc.length; i++) {
        let reg_pagofc = $('#factsGrid').jqGrid('getRowData', idsfc[i]);
        if (reg_pagofc.flag === 's') {
            obj_ccpp_mod[contador_fcsm] = { Cpp_Cod: reg_pagofc.Cpp_Cod, Pag_Val: reg_pagofc.Abono, total: reg_pagofc.total, Saldo: reg_pagofc.Saldo };
            contador_fcsm++;
        }
    }
    data["save_cp"] = obj_ccpp_mod;

    let batch = $('#pagosGrid').getGridBatch();
    let obj_sp_mod = new Object();
    let contador_sp = 0;
    for (let j = 0; j < batch.length; j++) {
        if (batch[j].Che_Est !== 'P') {
            delete batch[j]["Pag_Item"];
            obj_sp_mod[contador_sp] = batch[j];
            contador_sp++;
        }
    }

    data["save_p"] = obj_sp_mod;
    data["modPago"] = true;
    data["opt_guardar"] = opt_guardar;
    data["disminuir"] = disminuir.toFixed(2);

    $.saveDataJson("", data, function (responce) {
        if (responce['bnd_che'] === true) {
            $("#successDialog").dialog({ width: 500, height: 355 });
            $("#siche").removeAttr("hidden");
            $("#Che_imp option").remove();
            for (i = 0; i < responce['arrayche'].length; i++) {
                $("#Che_imp").append("<option value='" + i + "' data-link='" + responce['arrayche'][i].link + "'>" + responce['arrayche'][i].che + "</option>");
            }
            $("#Che_imp").trigger("onchange");
        } else {
            $("#successDialog").dialog({ width: 500, height: 200 });
        }
        $('#impCompr').attr('href', responce['link']);
        $('#successDialog').dialog('open');
        limpiarPagos();
        moveToList();
        $("#searchGrid").trigger("reloadGrid");
        return false;
    }, function (responce) {
        $('#pagosGrid').startGridEdit();
    }, function (responce) {
        $('#pagosGrid').startGridEdit();
    });
}

function setFecPeriodoCom() {
    $("#Com_Fec").dateLimits($("#Pec_Cod option:selected").attr("data-pec-fei"), $("#Pec_Cod option:selected").attr("data-pec-fef"));
}

// borra el pago seleccionado y actualiza los totales
function delPago(row) {
    // $('#protAnuChe').dialog('open');
    let saldo_info = parseFloat($("#saldo_info2").text());
    saldo_info = saldo_info - parseFloat(row.Haber);
    $("#saldo_info2").text(parseFloat(saldo_info).toFixed(2));
    if (parseFloat($("#saldo_info1").text()) === parseFloat($("#saldo_info2").text())) {
        $("#saldo_info").removeClass("txt-red");
        $("#saldo_info").addClass("txt-green");
    } else {
        $("#saldo_info").removeClass("txt-green");
        $("#saldo_info").addClass("txt-red");
    }

    $('#pagosGrid').jqGrid('delRowData', row.index);
    totalPagos();
}

function ActualizarSaldoInfo() {
    let saldo_info = 0.00;
    let tots = totalPagos();
    saldo_info = saldo_info + parseFloat(tots.haber);
    $("#saldo_info2").text(parseFloat(saldo_info).toFixed(2));
    if (parseFloat($("#saldo_info1").text()) === parseFloat($("#saldo_info2").text())) {
        $("#saldo_info").removeClass("txt-red");
        $("#saldo_info").addClass("txt-green");
    } else {
        $("#saldo_info").removeClass("txt-green");
        $("#saldo_info").addClass("txt-red");
    }
}

function totalPagos() {
    let total = parseFloat($("#Atp_Val").val());
    total += parseFloat($("#Pap_Val").val());

    //obtener todos los ids para buscar valores de debe y haber
    let ids = $('#pagosGrid').jqGrid('getDataIDs');
    let tot_obj = new Object();
    let debe = 0.00;
    let haber = 0.00;
    for (let i = 0; i < ids.length; i++) {
        let reg_pagot = $('#pagosGrid').jqGrid('getRowData', ids[i]);
        if (reg_pagot.Che_Est !== 'P') {
            if ($('#' + ids[i] + '_Debe').val() != undefined) {
                debe += parseFloat($('#' + ids[i] + '_Debe').val());
            }
            if ($('#' + ids[i] + '_Haber').val() != undefined) {
                haber += parseFloat($('#' + ids[i] + '_Haber').val());
            }
        }
    }
    tot_obj['debe'] = debe;
    tot_obj['haber'] = haber;

    $("#pagosGrid").jqGrid('footerData', 'set', {
        Glosa: "<div style='text-align:right;'>TOTALES:</div>",
        Debe: '' + debe,
        Haber: '' + haber
    }, true);

    return tot_obj;
}

function numdecimal(elem, valor) {
    let str_valid = valor.replace(/[^0-9\.]/g, "");
    let contadorp = 0;
    let str_final = "";
    for (let i = 0; i < str_valid.length; i++) {
        if (str_valid.charAt(i) === '.') {
            contadorp++;
            if (contadorp <= 1) {
                str_final += str_valid.charAt(i);
            }
        } else {
            str_final += str_valid.charAt(i);
        }
    }
    elem.val(str_final);
}

//permite el ingreso unicamente de numeros
function soloNumeros(e) {
    // valor = valor.replace(/[^0-9]/g,'');
    let key = window.Event ? e.which : e.keyCode
    return (key >= 48 && key <= 57)
}

function limpiarPagos() {
    $("#pagosGrid").jqGrid("clearGridData").trigger("reloadGrid");
    $("#Che_Ben_N").val("");
    $("#Com_Val").val("0.00");
    $("#Com_Val_pago").val("0.00");
    $("#Com_Con").val("");
    $("#Com_Obs").val("");

    $("#agg_nombre").val("");
    $("#agg_Prv_Cod").val("");
    $("#agg_Prs_Ced").val("");
    $("#agg_Prs_Dir").val("");

    $("#saldo_info1").text("0.00");
    $("#saldo_info2").text("0.00");

    $("#Che_Num").val("");
    $('#Pag_Cod').prop('selectedIndex', 0);
    $('#Pag_Cod').trigger("onChange");
    $("#saldo_info").removeClass("txt-green");
    $("#saldo_info").removeClass("txt-red");
    $("#saldo_info").addClass("txt-red");

    $("#cont_anticipo_info").attr("hidden", "");
    $("#cont_ccc_info").attr("hidden", "");

    $("#lim_val_pago").val("none");
    $("#lim_val_pago_cc").val("none");
}

function modificarAbono(row) {
    let valr_pagar = 0.00;
    $("#btn_view_facts").removeAttr("hidden");
    createPagosGrid();
    $("#tip_trans").val("mod");
    $("#Com_Cod").val(row[0].Com_Cod);
    $("#Com_Num").val(row[0].Com_Num);
    $("#Com_Fec").val(row[0].Com_Fec);
    $("#Tia_Cod_temp").val(row[0].Tia_Cod);
    $("#Tia_Cod option[value=" + row[0].Tia_Cod + "]").prop("selected", true);
    $("#Com_Val").val(row[0].Com_Val);
    $("#Com_Val_temp").val(row[0].Com_Val);
    $("#Che_Ben_N").val($("#nombre").val());
    $("#Com_Val_pago").val("0.00");
    $("#Com_Con").val(row[0].Com_Con);
    $("#Com_Obs").val(row[0].Com_Obs);

    $("#agg_nombre").val($("#nombre").val());
    $("#agg_Prv_Cod").val($("#Prv_Cod").val());
    $("#agg_Prs_Ced").val($("#Prs_Ced").val());
    $("#agg_Prs_Dir").val($("#Prs_Dir").val());

    $("#saldo_info1").text("" + parseFloat(row[0].Com_Val).toFixed(2));

    $("#Pec_Cod option[value=" + row[0].Pec_Cod + "]").prop("selected", true);
    $("#Pec_Cod").trigger("onchange");

    //addPagoIni();

    $.post("", { getPagsAbono: true, Com_Cod: row[0].Com_Cod, Prv_Cod: $("#agg_Prv_Cod").val() }, function (responce) {
        if (responce['success'] === true) {
            var fechas=[];
            $('#factsGrid').clearGrid();
            $.each(responce['data_fact'], function (i, v) {
                fechas[i] = v.Cop_Fec;
                $('#factsGrid').jqGrid('addRowData', v.Cpp_Data, $.extend(true, { flag: "s" }, v));
                let prm_array = ["D", "inicial", "", "", "", "", "", "", "", "", "", "", v.Pld_Cod, v.Pld_Cdc, v.Pld_Des, v.Abono, "", v.Asi_Con, "first", ""];
                addPago(prm_array);
            });
            let fechaMayor = fechas.reduce((max, actual) => {
                return new Date(actual) > new Date(max) ? actual : max;
              });  
            $("#Com_Fec").dateLimits(fechaMayor, $("#Pec_Cod option:selected").attr("data-pec-fef"));
            //agregar pago inicial
            for (let i = 0; i < responce['data'].length; i++) {
                let par_array = [
                    "" + responce['data'][i].Asi_Deh,
                    "pago",
                    "" + responce['data'][i].Che_Cod,
                    "" + responce['data'][i].Asi_Cod,
                    "" + responce['data'][i].Pag_Cod,
                    "" + responce['data'][i].Pag_Abr,
                    "" + responce['data'][i].Pag_Des,
                    "" + responce['data'][i].Ban_Cod,
                    "" + responce['data'][i].Che_Num,
                    "" + responce['data'][i].Che_Fec,
                    "",
                    "",
                    "" + responce['data'][i].Pld_Cod,
                    "" + responce['data'][i].Pld_Cdc,
                    "" + responce['data'][i].Pld_Des,
                    "",
                    "" + responce['data'][i].Asi_Val,
                    "" + responce['data'][i].Asi_Glo,
                    "last",
                    "" + responce['data'][i].Che_Est
                ];
                addPago(par_array);
                if (responce['data'][i].Pag_Abr === "ANT") {
                    if (responce['data_ant'] === 'none') {
                        $("#lim_val_pago").val("none");
                    } else {
                        $("#lim_val_pago").val("" + parseFloat(responce['data_ant']).toFixed(2));
                    }
                } else if (responce['data'][i].Pag_Abr === "CDC") {
                    if (responce['data_ccc'] === 'none') {
                        $("#lim_val_pago_cc").val("none");
                    } else {
                        $("#lim_val_pago_cc").val("" + parseFloat(responce['data_ccc']).toFixed(2));
                    }
                }
                //valor real a pagar
                if (responce['data'][i].Che_Est === 'P') {
                    valr_pagar += parseFloat(responce['data'][i].Asi_Val);
                }
            }
            //asignamos valor real a pagar
            if (parseFloat(totalPagos().haber) < parseFloat(row[0].Com_Val)) {
                $("#saldo_info").removeClass("txt-green");
                $("#saldo_info").addClass("txt-red");
                $("#saldo_info2").text("" + parseFloat(totalPagos().haber).toFixed(2));
            } else {
                $("#saldo_info").removeClass("txt-red");
                $("#saldo_info").addClass("txt-green");
                $("#saldo_info2").text("" + parseFloat(totalPagos().haber).toFixed(2));
            }
            $("#Com_Val_dism").val(parseFloat(valr_pagar).toFixed(2));

        } else {
            $.alert(responce['message']);
        }
    }, 'json')
        .fail(function (error) {
            $.alert("El Servidor ha fallado en responder!");
        });

    moveToAggCcpp();
    //verFacturasAbono(row[0].Com_Cod);
}

function moveToList() {
    $("#agregar_ccpp").moveComp("#listar_ccpp").updateGridsSizes();
}

function moveToAggCcpp() {
    $("#listar_ccpp").moveComp("#agregar_ccpp").updateGridsSizes();
}

function actualizarTotalesSG() {
    //obtener todos los ids para buscar valores de debe y haber
    let ids = $('#searchGrid').jqGrid('getDataIDs');

    let abonos = 0,
        saldos = 0,
        total_pago = 0.00,
        tot = 0;
    for (let i = 0; i < ids.length; i++) {
        let reg_pago = $('#searchGrid').jqGrid('getRowData', ids[i]);

        tot = tot + parseFloat(reg_pago.Asi_Val);
        abonos = abonos + parseFloat(reg_pago.Abono);
    }

    $('#searchGrid').jqGrid('footerData', 'set', { vencimiento: "TOTALES:", Asi_Val: $('#searchGrid').jqGrid('getCol', 'Asi_Val', false, 'sum') });
    $('#searchGrid').jqGrid('footerData', 'set', { Asi_Val: "" + tot });
    $('#searchGrid').jqGrid('footerData', 'set', { Abono: "" + abonos });
    $('#searchGrid').jqGrid('footerData', 'set', { Saldo: "" + saldos });
}

function selectProveedor(proveedor) {
    $("#Prv_Cod").val(proveedor.Prv_Cod);
    $("#Prs_Ced").val(proveedor.Prs_Ced);
    $("#nombre").val(proveedor.nombre);
    $("#Prs_Dir").val(proveedor.Prs_Dir);
    $('#proveedoresDialog').dialog('close');
    $('#searchGrid').Search('#searchCcpp', 'ajaxComprobante');
}

function setSelVen(valor) {
    $("#sel_ven option").remove();
    if (valor === "T") {
        $("#sel_ven").append('<option value="1">Todos</option>');
    }
    if (valor === "V") {
        $("#sel_ven").append('<option value="7"><< Vencidas a 90 dias</option>');
        $("#sel_ven").append('<option value="6"><< Vencidas a 60 dias</option>');
        $("#sel_ven").append('<option value="5"><< Vencidas a 30 dias</option>');
    }
    if (valor === "P") {
        $("#sel_ven").append('<option value="4">Por vencer a 90 d�as >></option>');
        $("#sel_ven").append('<option value="3">Por vencer a 60 d�as >></option>');
        $("#sel_ven").append('<option value="2">Por vencer a 30 d�as >></option>');
    }
}

function cambiarChe() {
    // $("#impChe").attr("href",$("#Che_imp option:selected").attr("data-link"));
    $("#impchetd td").each(function () {
        $(this).children("a").attr("href", $(this).children("a").attr("data-ruta") + "" + $("#Che_imp option:selected").attr("data-link"));
    });
}

$.fn.createInputDiario3 = function (element, tipo) {
    let jgrid = this,
        rowId = $(element).closest('tr.jqgrow').attr('id'),
        tip = jgrid.jqGrid('getCell', rowId, 'Det_Tip');
    $(element).parent().removeAttr("title");
    if (tip === tipo) {
        $(element).on('change', function () {
            let reg_pago = $('#pagosGrid').jqGrid('getRowData', rowId);
            let tots = totalPagos();

            if (reg_pago.Pag_Abr !== "") {
                let v_max = recTotalPagar();
                if (parseFloat(tots.debe) > parseFloat(v_max)) {
                    console.log("exedente...");
                    $("#PgabrEx").val(reg_pago.Pag_Abr);
                }
            }

            if ($(this).val() != '') {
                $(this).val($.toFixed($(this).val()));
                jgrid.updateGridDiario();
            } else {
                $(this).val($.toFixed("0.00"));
                jgrid.updateGridDiario();
            }

            if (reg_pago.Pag_Abr === "ANT") {
                if (parseFloat($(this).val()) <= parseFloat($("#lim_val_pago").val())) {
                    $(this).val(parseFloat($(this).val()).toFixed(2));
                } else {
                    $(this).val(parseFloat($("#lim_val_pago").val()).toFixed(2));
                }
            }
            if (reg_pago.Pag_Abr === "CDC") {
                if (parseFloat($(this).val()) <= parseFloat($("#lim_val_pago_cc").val())) {
                    $(this).val(parseFloat($(this).val()).toFixed(2));
                } else {
                    $(this).val(parseFloat($("#lim_val_pago_cc").val()).toFixed(2));
                }
            }
            if (reg_pago.Pag_Abr === "") {
                $("#Com_Val").val(parseFloat($(this).val()).toFixed(2));
                $("#saldo_info1").text(parseFloat($(this).val()).toFixed(2));
            }
            jgrid.updateGridDiario();
            ActualizarSaldoInfo();
        });
        $(element).attr('onkeypress', 'return  validar_decimal(event)');
        if (parseFloat($(element).val()) === 0) $(element).val("");
        $(element).css('text-align', 'right').focus();
    } else { $(element).parent().html(''); };
};