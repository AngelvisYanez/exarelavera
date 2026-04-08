let ids_pagos = 0;
var Lista_Anticipos;
var list_facturas_cruze;
$(function () {
    Lista_Anticipos = $("#Lista_Anticipos");
    list_facturas_cruze = $("#lista_facturas_cruze");

    lista_facturas_cruze();


    load_anticipos();

    // **********************datepickers y tabs *************************
    $('.datepicker').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });

    //***********************lanzar eventos de elementos
    $("#Pec_Cod").trigger("onchange");
    $("#tabs_abo_det").tabs();

    //***********************creacion de dialogos************************
    $("#successDialog").createDialog({ width: 500, height: 200, icon: 'print' });
    $("#altr_ant").createDialog({ width: 420, height: 150, icon: 'info' });
    $("#anular_abo_dialog").createDialog({ width: 300, height: 150, icon: 'warning-sign' });
    $("#verPagosDialogMod").createDialog({ width: 700, height: 435, icon: 'info-sign' });
    $("#searchGrid").createGrid({
        caption: 'Lista de documentos <div class="pull-right"><b>ORDENAR POR:</b>&nbsp;<select id="OrderBy"><option value="">No ordenar</option><option value="compras.Cop_Num ASC">Nro. Doc.</option><option value="Com_Codigo Asc">No Compr Asc.</option>  <option value="compras.Cop_Fec DESC">Fecha Emi. DESC.</option> <option value="compras.Cop_Fec ASC">Fecha Emi ASC.</option><select>&nbsp;</div>',
        postData: $("#searchCcpp").getData("ajaxComprobante"),
        height: 300,
        mtype: "GET",
        datatype: "local",
        regional: 'es',
        shrinkToFit: true,
        colModel: [
            { label: 'Cód.Int.', name: 'Cpp_Cod', key: true, hidden: true, viewable: true },
            { label: 'Cód.Int.', name: 'Asi_Cod', hidden: true },

            { label: 'Cop_Cod', name: "Cop_Cod", hidden: true },

            { label: 'Pld_Cod.', name: "Pld_Cod", hidden: true },
            { label: 'Pld_Cdc.', name: "Pld_Cdc", hidden: true },
            { label: 'Pld_Des.', name: "Pld_Des", hidden: true },
            { label: 'No. Compr.', name: 'Com_Codigo', align: "center", width: 25 },
            { label: 'Fecha Emis.', name: 'Cop_Fec', align: "center", width: 30 },
            { label: 'Fecha Venc.', name: 'Cpp_Ven', align: "center", width: 30 },
            { label: 'Tipo Documento', name: 'Tic_Des', width: 30 },
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
            { label: 'Obs. Docum.', name: 'Cop_Obs', width: 45 },
            { label: 'Prv_Cod.', name: "Prv_Cod", hidden: true },
            { label: 'Prs_Ape.', name: "Prs_Ape", hidden: true },
            { label: 'Prs_Nom.', name: "Prs_Nom", hidden: true },
            { label: 'Proveedor', name: 'proveedor', width: 75 },
            {
                label: '<i class="ui-icon ui-icon-circle-check"></i>',
                name: 'act',
                width: 10,
                align: 'center',
                viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    if (rowObject.vencimiento === 'Pagado') {
                        return "";
                    } else {
                        return '<input id="sg_act_' + rowObject.Cpp_Cod + '" type="checkbox" value="" onChange="setPagoCell(\'' + rowObject.Cpp_Cod + '\')" offval="no">';
                    }
                },
                title: false,
                formatoptions: { disabled: false },
                resizable: false
            },
            {
                classes: 'columnDisabled no_padding',
                label: 'A Pagar',
                name: 'Pago',
                width: 45,
                align: 'right',
                formatter: function (cellvalue, options, rowObject) {
                    if (rowObject.vencimiento === 'Pagado') {
                        return "";
                    } else {
                        return '<input id="sg_pago_' + rowObject.Cpp_Cod + '" class="" type="text" value="0.00" onChange="actualizarTotalesSG();$(this).val(parseFloat($(this).val()).toFixed(2))" autocomplete="off" onkeypress="return  validar_decimal(event)" readonly>';
                    }
                },
                decimalPlaces: '2',
                summaryRound: 2,
                formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '0.00' },
                summaryTpl: "Total: {0}",
                summaryType: "sum", // set the formula to calculate the summary type
                editable: true,
                edittype: 'text',
                editoptions: { type: 'number', style: 'text-align: right;', min: 0, onchange: "validaVal($(this).attr('id'),this.value);" }
            },
            { label: 'Cop_Obs.', name: "Cop_Obs", hidden: true }
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
            let cpp_data = $('#searchGrid').jqGrid('getRowData', row_id);
            $("#" + subgrid_id).html("<table id='" + subgrid_table_id + "' class='scroll'></table>");
            $("#" + subgrid_table_id).createGrid({
                url: "?abonosDetAjax=" + cpp_data.Cpp_Cod,
                datatype: "json",
                regional: 'es',
                height: 'auto',
                colModel: [
                    { label: '', name: 'indx', key: true, hidden: true },
                    { label: '', name: 'Com_Cod', hidden: true },
                    { label: '', name: 'Cpp_Cod', hidden: true },
                    { label: '', name: 'reg_editable', hidden: true },
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
                    { label: 'Observaci&oacute;n', name: 'Pag_Obs', width: 140, align: "left" },
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
                            //para verificar si se trata de un abono negativo en caso de cheques protestados
                            return $.getGridButton(verAbono, parm_getdet, 'Ver abono', 'info-sign', '', 'info');
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
                if ($(this).find("td").eq(11).text() === "Vencido") {
                    $(this).addClass("cellRed2");
                    $(this).addClass("myAltRowClass");
                }
                if ($(this).find("td").eq(11).text() === "Pagado") {
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
    }, false, "#sgPager", { view: false, refresh: false }).gridButtonsAdd([{
        caption: 'Marcar todos',
        buttonicon: 'glyphicon glyphicon-check',
        class: 'btn-sg-pg',
        id: "mrc_btn_sg",
        onClickButton: function () {
            $("[id^='sg_act_']:not(:checked)").trigger("click");
        }
    },
    {
        caption: 'Desmarcar todos',
        buttonicon: 'glyphicon glyphicon-unchecked',
        class: 'btn-sg-pg',
        onClickButton: function () {
            $("[id^='sg_act_']:checked").trigger("click");
        }
    }
    ]);
    $("#searchGrid").updateGridsSizes();
    $("#pagosGrid").updateGridsSizes();

    //tabla de cheques
    $('#showPagosChe').createGrid({
        viewrecords: false,
        caption: "<center>Cheques emitidos en este Abono</center>",
        data: [],
        rowNum: 100,
        height: 80,
        width: 650,
        footerrow: true,
        responsive: false,
        onSelectRow: function (rowid, e) { $(this).resetSelection(); },
        colModel: [
            { label: 'index', key: true, name: 'index', hidden: true, classes: 'bgNoRight' },
            { label: '', name: 'Che_Est', hidden: true },
            { label: '', name: 'Che_Cod', hidden: true },
            { label: '', name: 'Ban_Cod', hidden: true },
            { label: '', name: 'Asi_Cod', hidden: true },
            { label: '', name: 'Pld_Cod', hidden: true },
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
            { label: 'Estado', name: 'Che_Est_det', width: 15, align: "left" },
            {
                label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
                name: 'btns_anti',
                width: 10,
                align: 'center',
                viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    if (rowObject.Che_Est === 'S') {
                        return $.getGridButton(preProtestarCheque, rowObject, 'Marcar como protestado', 'ban-circle', '', 'danger');
                    } else {
                        return "-";
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


    $('#OrderBy').on('change', function () {
        console.log("Buscando");
        $('input[name=order]').val($(this).val());
        $('#searchCcpp').formSubmit();
    });



});

$(document).ready(function () {
    $("#Tia_Cod option[data-abr='EG']").prop("selected", true);
});

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
    $.createDialogConfirm('¿Est&aacute; seguro que desea marcar como protestado este cheque?', row, protestarCheque);
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
        { label: 'Cop_Cod', name: 'Cop_Cod', hidden: true },
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
    $("#showPagosAsi").jqGrid("clearGridData").trigger("reloadGrid");
    $("#showPagosChe").jqGrid("clearGridData").trigger("reloadGrid");

    $("#ant_detasi").children("a").trigger("click");

    $("#prov_show").val($("#nombre").val());
    $("#ruc_show").val($("#Prs_Ced").val());
    $("#compr_show").val(row[0].codigo_compro);
    $("#fec_show").val(row[0].Com_Fec);
    $("#obs_show").val(row[0].Com_Obs);
    $("#Com_Cod_view").val(row[0].Com_Cod);

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
                if (responce['data_che'][i].Che_Est === "A") estado_che = "Anulado";
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
    $("#anular_abo_dialog").dialog('open');
    // $.createDialogConfirm('�Est&aacute; seguro que desea anular este Abono?',row,anularAbono);
}

function anularAbono(row) {
    //console.log("VerficarRow:"+row);
    $.post("", { delAbono: true, Com_Cod: row[0].Com_Cod /*, Pag_Abr:row[0].Pag_Abr */ }, function (responce) {
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

    $(".ed_element").attr("disabled", "");//bloquea todos los .ed_elemento
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
                    //$("#Com_Val_pago").val((parseFloat($("#Com_Val").val()) * 1 - parseFloat(totalPagos().haber) * 1).toFixed(2));
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
        $("#Com_Val_pago").val((parseFloat($("#Com_Val").val()) * 1 - parseFloat(totalPagos().haber) * 1).toFixed(2));
    }
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
                    //$("#Com_Val_pago").val((parseFloat($("#Com_Val").val()) * 1 - parseFloat(totalPagos().haber) * 1).toFixed(2));
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
        $("#Com_Val_pago").val((parseFloat($("#Com_Val").val()) * 1 - parseFloat(totalPagos().haber) * 1).toFixed(2));
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
    let ids = $('#searchGrid').jqGrid('getDataIDs'),
        concep = $("#Com_Con").val();
    let array_pagos = [];
    for (let i = 0; i < ids.length; i++) {
        if ($("#sg_act_" + ids[i]).prop('checked')) {
            let addFila = true;
            let reg_pago = $('#searchGrid').jqGrid('getRowData', ids[i]);

            for (let j = 0; j < array_pagos.length; j++) {
                if (reg_pago.Pld_Cod === array_pagos[j][12]) {
                    array_pagos[j][15] += parseFloat($('#sg_pago_' + ids[i]).val());
                    addFila = false;
                    break;
                }
            }

            // console.log( "ESTO ES UNA PRUEBA PARA VER EL CODIGO DE LA COMPRA:::"+reg_pago.Pld_Cod);

            if (addFila) {
                let prm_array = ["D", "inicial", "", "", "", "", "", "", "", "", "", "", reg_pago.Pld_Cod, reg_pago.Pld_Cdc, reg_pago.Pld_Des, parseFloat($('#sg_pago_' + ids[i]).val()), "", concep, "first", "", reg_pago.Cop_Cod];
                array_pagos.push(prm_array);
            }
        }
    }
    for (let i = 0; i < array_pagos.length; i++) {
        addPago(array_pagos[i]);
    }
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



function open_anticipos() {
    load_anticipos();
    $('#Lista_Anticipos').Search('#formPagos_anticipo', 'loadAnticipos');
    $("#monto_total").val($("#Com_Val_pago").val());
    $('#agregar_anticipos').dialog({
        autoOpen: false, // No abrir automáticamente al crear
        modal: true,
        width: '65%',
        title: 'Pagar con Anticipos',
        dialogClass: 'no-close', // jQuery UI way to remove default close button (X)
        buttons: {
            Siguiente: {
                text: "Siguiente",
                class: 'btn-siguiente',
                click: function () {
                    var valor = $('td[aria-describedby="Lista_Anticipos_saldo_pagar"].columnDisabled').text();
                    var numero = parseFloat(valor.replace(/[^\d.-]/g, ''));
                    var monto_total = $("#monto_total").val();
                    if (monto_total == numero) {
                        $(this).dialog('close');
                    } else {
                        $.alert("Existen errores en la asignación de cantidades, vuelva a revisar");
                    }
                }
            }
        },
        open: function (event, ui) {
            // Agregar clase adicional al botón al abrir el diálogo
            $(this).parent().find('.btn-siguiente').addClass('btn-primary');
            // Esconde el botón X de cierre a nivel de DOM/UI
            $(this).parent().find('.ui-dialog-titlebar-close').hide();
        }
    });
    $('#agregar_anticipos').dialog('open');
}


function preAddPago() {
    let prm_array;
    if ($("#Pag_Cod option:selected").attr("data-abr") === "EFE" || $("#Pag_Cod option:selected").attr("data-abr") === "OTR"
        || $("#Pag_Cod option:selected").attr("data-abr") === "RC"
        || $("#Pag_Cod option:selected").attr("data-abr") === "DEP" || $("#Pag_Cod option:selected").attr("data-abr") === "ANT"
        || $("#Pag_Cod option:selected").attr("data-abr") === "CDC" /*|| $("Pag_Cod option:selected").attr("data-abr") === "RC"*/) {

        $.post("", { getPagoCtaAjax: true, tipo: "" + $("#Pag_Cod option:selected").attr("data-abr") }, function (responce) {
            if (responce['success'] === true) {
                if ($("#pagosGrid td:contains('EFE')").text().search("EFE") == -1 && $("#Pag_Cod option:selected").attr("data-abr") === "EFE") {
                    prm_array = ["H", "pago", "", "", $("#Pag_Cod").val(), $("#Pag_Cod option:selected").attr("data-abr"), $.trim($("#Pag_Cod option:selected").text()), "", "", "", "", "", responce['data'].Pld_Cod, responce['data'].Pld_Cdc, responce['data'].Pld_Des, "", $("#Com_Val_pago").val(), $("#Com_Con").val(), "last", ""];
                    incrementarSaldoInfo();
                    addPago(prm_array);
                }
                if ($("#pagosGrid td:contains('DEP')").text().search("DEP") == -1 && $("#Pag_Cod option:selected").attr("data-abr") === "DEP") {
                    prm_array = ["H", "pago", "", "", $("#Pag_Cod").val(), $("#Pag_Cod option:selected").attr("data-abr"), $.trim($("#Pag_Cod option:selected").text()), "", "", "", "", "", responce['data'].Pld_Cod, responce['data'].Pld_Cdc, responce['data'].Pld_Des, "", $("#Com_Val_pago").val(), $("#Com_Con").val(), "last", ""];
                    incrementarSaldoInfo();
                    addPago(prm_array);
                }
                if ($("#pagosGrid td:contains('ANT')").text().search("ANT") == -1 && $("#Pag_Cod option:selected").attr("data-abr") === "ANT") {
                    if ($("#lim_val_pago").val() === "none") {
                        $.alert("No tiene anticipos disponibles para este proveedor!");
                    } else {
                        open_anticipos();//Abrir modal
                        prm_array = ["H", "pago", "", "", $("#Pag_Cod").val(), $("#Pag_Cod option:selected").attr("data-abr"), $.trim($("#Pag_Cod option:selected").text()), "", "", "", "", "", responce['data'].Pld_Cod, responce['data'].Pld_Cdc, responce['data'].Pld_Des, "", $("#Com_Val_pago").val(), $("#Com_Con").val(), "last", ""];
                        incrementarSaldoInfo();
                        addPago(prm_array);
                    }
                }



                if ($("#pagosGrid td:contains('CDC')").text().search("CDC") == -1 && $("#Pag_Cod option:selected").attr("data-abr") === "CDC") {
                    if ($("#lim_val_pago_cc").val() === "none") {
                        $.alert("No tiene una cantidad suficiente para el cruce de cuentas con este proveedor!");
                    } else {
                        open_facturas_cruze();
                        prm_array = ["H", "pago", "", "", $("#Pag_Cod").val(), $("#Pag_Cod option:selected").attr("data-abr"), $.trim($("#Pag_Cod option:selected").text()), "", "", "", "", "", responce['data'].Pld_Cod, responce['data'].Pld_Cdc, responce['data'].Pld_Des, "", $("#Com_Val_pago").val(), $("#Com_Con").val(), "last", ""];
                        incrementarSaldoInfo();
                        addPago(prm_array);
                    }
                }







                if ($("#Pag_Cod option:selected").attr("data-abr") === "OTR") {
                    prm_array = ["H", "pago", "", "", $("#Pag_Cod").val(), $("#Pag_Cod option:selected").attr("data-abr"), $.trim($("#Pag_Cod option:selected").text()), "", "", "", "", "", responce['data'].Pld_Cod, responce['data'].Pld_Cdc, responce['data'].Pld_Des, "", $("#Com_Val_pago").val(), $("#Com_Con").val(), "last", ""];
                    incrementarSaldoInfo();
                    addPago(prm_array);
                }

                if ($("#Pag_Cod option:selected").attr("data-abr") === "RC") {//CAJA REPOSICION
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
                    $.trim($("#Pag_Cod option:selected").text()),
                    $("#Ban_Cod option:selected").attr("value"),
                    $("#Che_Num").val(),
                    $("#Che_Fec").val(),
                    "", "",
                    $("#Ban_Cod option:selected").attr("data-pla"),
                    $("#Ban_Cod option:selected").attr("data-cdc"),
                    $("#Ban_Cod option:selected").attr("data-des"),
                    "",
                    $("#Com_Val_pago").val(),
                    "CHEQUE NO. " + $("#Che_Num").val(),
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
            $.trim($("#Pag_Cod option:selected").text()),
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


//let prm_array = ["D", "inicial", "", "", "", "", "", "", "", "", "", "",reg_pago.Pld_Cod,   reg_pago.Pld_Cod, reg_pago.Pld_Cdc, reg_pago.Pld_Des, parseFloat($('#sg_pago_' + ids[i]).val()), "", concep, "first", ""];


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
        Che_Est: prm_array[19],
        Cop_Cod: prm_array[20]
    }, "" + prm_array[18]);
    // $("#1_Debe").trigger("onChange");
    $('#pagosGrid').startGridEdit();
    $('#pagosGrid').updateGridDiario();
    if (prm_array[0] === 'D') $("#" + ids_pagos + "_Debe").prop('readonly', true);
    totalPagos();
    disableDebe();
}

function guardarPago() {
    let tots = totalPagos();
    console.log(parseFloat(tots.debe).toFixed(2) + "===" + parseFloat(tots.haber).toFixed(2));

    if (parseFloat(tots.debe).toFixed(2) === parseFloat(tots.haber).toFixed(2)) {
        if (parseFloat(tots.debe).toFixed(2) === parseFloat($("#Com_Val").val()).toFixed(2)) {

            let data = $('#formPagos').serializeObject();
            if ($("#tip_trans").val() === "add") {
                let ids = $('#searchGrid').jqGrid('getDataIDs');
                let obj_ccpp = new Object();
                let contador_fcs = 0;
                for (let i = 0; i < ids.length; i++) {
                    if ($("#sg_act_" + ids[i]).prop('checked')) {
                        let reg_pago = $('#searchGrid').jqGrid('getRowData', ids[i]);
                        obj_ccpp[contador_fcs] = { Cpp_Cod: reg_pago.Cpp_Cod, Pag_Val: $('#sg_pago_' + ids[i]).val() };
                        contador_fcs++;
                    }
                }

                data["save_cp"] = obj_ccpp;
                data["save_p"] = $('#pagosGrid').getGridBatch();
                //  console.log($('#pagosGrid').getGridBatch());
                data["savePago"] = true;
                data["save_pago_anticipos"] = $('#Lista_Anticipos').getGridBatch();
                
                
                // Para obtener el valor del input con id="sg_pago_undefined", se puede usar:
                var valor_tota_pago = $("#sg_pago_undefined").val();

             
               /* alert(valor_tota_pago);
                return false;*/
                
               
                
                
                data["valor_tota_pago"] = parseFloat($('td[aria-describedby="Lista_Anticipos_saldo_pagar"].columnDisabled').text().replace(/[^\d.-]/g, ''));
                
                console.log("SALDO A PAGAR::" + data["valor_tota_pago"]);
                
                
                for (let i = 0; i < data["save_pago_anticipos"].length; i++) {
                    let item = data["save_pago_anticipos"][i];
                    let saldo_pagar = $('#sg_pago_' + item.Atp_Cod).val();
                    data["save_pago_anticipos"][i].saldo_pagar = saldo_pagar;
                }

                
                console.log("save_pago_anticipos:" + data["save_pago_anticipos"]);


                var selected = $("#Pag_Cod option:selected");
                var tipo_pago = selected.data("abr"); // Obtienes "CDC"
                var pago_cdc = 0;
                if (tipo_pago === 'CDC') {
                    data["ccc_cnt"] = $('#lista_facturas_cruze').getGridBatch();
                    for (let i = 0; i < data["ccc_cnt"].length; i++) {
                        let item = data["ccc_cnt"][i];
                        let saldo_pagar = parseFloat($('#sg_pago_' + item.Vet_Cod).val());
                        data["ccc_cnt"][i].saldo_pagar = saldo_pagar;
                        pago_cdc += saldo_pagar;
                    }
                    //if (    tots.debe != pago_cdc   ) {
                    if (Number(tots.debe.toFixed(4)) !== Number(pago_cdc.toFixed(4))) {
                        return $.alert("Ingrese todas las facturas para cubrir el valor de pago.");
                    }
                }
                console.log(data);
                $.post("", data, function (responce) {
                    if (responce['success'] === true) {
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
                    } else {
                        $.alert(responce['message']);
                    }
                }, 'json')
                    .fail(function (error) {
                        $.alert("El Servidor ha fallado en responder!");
                    });
            }

        } else {
            $.alert("El valor total de pagos debe ser de " + $("#Com_Val").val());
        }
    } else {
        $.alert("Los totales no coinciden");
    }
}

function setFecPeriodoCom() {
    $("#Com_Fec").dateLimits($("#Pec_Cod option:selected").attr("data-pec-fei"), $("#Pec_Cod option:selected").attr("data-pec-fef"));
}

// borra el pago seleccionado y actualiza los totales AbrirCuentas
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

function gestionarPago() {

    $("#tip_trans").val("add");
    if ($("#Prv_Cod").val() !== "") {
        if (actualizarTotalesSG() !== 0) {
            $("#Pec_Cod").trigger("onchange");
            createPagosGrid();
            var fechas=[];
            let ids = $('#searchGrid').jqGrid('getDataIDs'),
                str_con = "ABONO FACTS. ";
            let val_pagar = 0, Conc_Cop_Cod = 0,
                cop_observa = '';
            for (let i = 0; i < ids.length; i++) {                
                if ($("#sg_act_" + ids[i]).prop('checked')) {
                    fechas[ids[i]] = $('#searchGrid').jqGrid('getCell', ids[i], 'Cop_Fec');
                    let reg_pago = $('#searchGrid').jqGrid('getRowData', ids[i]);
                    str_con += "/" + reg_pago.Cop_Num;
                    val_pagar += parseFloat($('#sg_pago_' + ids[i]).val());
                    cop_observa += reg_pago.Cop_Obs + ", ";
                    //Cop_Cod += reg_pago.Cop_Cod;
                    //  Conc_Cop_Cod += reg_pago.Cop_Cod + ",";
                    // console.log( "coge el codigo de compra::: "+ Conc_Cop_Cod);
                }
            }
            let fechaMayor = fechas.reduce((max, actual) => {
                return new Date(actual) > new Date(max) ? actual : max;
              });  
            $("#Com_Fec").dateLimits(fechaMayor, $("#Pec_Cod option:selected").attr("data-pec-fef"));
            $("#Che_Ben_N").val($("#nombre").val());
            $("#Com_Val").val(parseFloat(val_pagar).toFixed(2));
            $("#Com_Val_pago").val(parseFloat(val_pagar).toFixed(2));
            $("#monto_total").val($("#Com_Val_pago").val());
            $("#Com_Con").val(str_con);
            $("#Com_Obs").val(str_con);
            $("#Com_Con").data('facturas', str_con);
            $("#Com_Con").data('observacion', cop_observa);
            $('#chkObs').prop('checked', false);
            $("#agg_nombre").val($("#nombre").val());
            $("#agg_Prv_Cod").val($("#Prv_Cod").val());
            $("#agg_Prs_Ced").val($("#Prs_Ced").val());
            $("#agg_Prs_Dir").val($("#Prs_Dir").val());
            $("#saldo_info1").text(parseFloat(val_pagar).toFixed(2));
            $("#saldo_info2").text("0.00");


            // $("#Cop_Cod").val(Conc_Cop_Cod);


            addPagoIni();
            moveToAggCcpp();
        } else {
            $.alert("Indique al menos un valor a pagar");
        }
    } else {
        $.alert("Seleccione un proveedor");
    }

    $.post("", { getAnticipoCantAjax: true, Prv_Cod: $("#agg_Prv_Cod").val() }, function (responce) {


        if (responce['success'] === true) {
            $("#ant_msg").html(responce['data'] === 'none' || !(responce['data'] > 0) ? "$ 0.00" : $.numFormat(responce['data']));
            $("#ant_msg")[responce['data'] === 'none' || !(responce['data'] > 0) ? 'removeClass' : 'addClass']('alert alert-danger bold');
        } else {
            $("#ant_msg").html("$ 0.00");
            $("#ant_msg").removeClass('alert alert-danger bold');
            $.alert(responce['message']);
        }
    }, 'json')
        .fail(function (error) {
            $.alert("El Servidor ha fallado en responder!");
        });
}

//Validacion de los anticipos

function validar_fecha_comprobante() {
    $.post("", { getAnticipoCantAjax: true, Prv_Cod: $("#agg_Prv_Cod").val() }, function (responce) {
        if ($("#Com_Fec").val() < responce['Atp_Fec']) {
            alert("No existen anticipos para estas fechas");

            $("#Com_Fec").val(responce['Atp_Fec']);
        }

    }, 'json')
        .fail(function (error) {
            $.alert("El Servidor ha fallado en responder!");
        });
    console.log("Fecha hoy:" + $("#Com_Fec").val());//fecha de registro de anticipo
    //Fecha en que se registro el anticipo
}

//Fin de los anticipos

function usarAnticipo() {
    $("#btn_addpago").trigger("click");
    $("#altr_ant").dialog("close");
}

function moveToList() {
    $("#agregar_ccpp").moveComp("#listar_ccpp").updateGridsSizes();
}

function moveToAggCcpp() {
    $("#listar_ccpp").moveComp("#agregar_ccpp").updateGridsSizes();
}

function setPagoCell(row) {
    let reg_pago = $('#searchGrid').jqGrid('getRowData', row);
    if ($("#sg_act_" + row).prop('checked')) {
        $("#sg_pago_" + row).removeAttr("readonly");
        $("#sg_pago_" + row).val("" + reg_pago.Saldo);
    } else {
        $("#sg_pago_" + row).attr("readonly", "");
        $("#sg_pago_" + row).val("0.00");
    }
    actualizarTotalesSG();
}

function actualizarTotalesSG() {
    //obtener todos los ids para buscar valores de debe y haber
    let ids = $('#searchGrid').jqGrid('getDataIDs');
    let abonos = 0, saldos = 0, total_pago = 0.00, tot = 0;
    for (let i = 0; i < ids.length; i++) {
        let reg_pago = $('#searchGrid').jqGrid('getRowData', ids[i]);
        tot = tot + parseFloat(reg_pago.Asi_Val);
        abonos = abonos + parseFloat(reg_pago.Abono);
        if (typeof $('#sg_pago_' + ids[i]).val() !== 'undefined') {
            saldos = saldos + parseFloat(reg_pago.Saldo);
            total_pago += parseFloat($('#sg_pago_' + ids[i]).val());
        }
    }
    $('#searchGrid').jqGrid('footerData', 'set', { vencimiento: "TOTALES:", Asi_Val: $('#searchGrid').jqGrid('getCol', 'Asi_Val', false, 'sum') });
    $('#searchGrid').jqGrid('footerData', 'set', { Asi_Val: "" + tot });
    $('#searchGrid').jqGrid('footerData', 'set', { Abono: "" + abonos });
    $('#searchGrid').jqGrid('footerData', 'set', { Saldo: "" + saldos });
    $('#searchGrid').jqGrid("footerData", "set", { proveedor: "<div style='text-align:right;'>TOTAL A PAGAR:</div>", Pago: "" + total_pago });
    $("#sg_pago_undefined").parent().append("$ " + parseFloat(total_pago).toFixed(2));
    $("#sg_pago_undefined").remove();
    return total_pago;
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
        $("#sel_ven").append('<option value="4">Por vencer a 90 días >></option>');
        $("#sel_ven").append('<option value="3">Por vencer a 60 días >></option>');
        $("#sel_ven").append('<option value="2">Por vencer a 30 días >></option>');
    }
}

function cambiarChe() {
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
            if ($(this).val() != '') {
                $(this).val($.toFixed($(this).val()));
                jgrid.updateGridDiario();
            } else {
                $(this).val($.toFixed("0.00"));
                jgrid.updateGridDiario();
            }

            let reg_pago = $('#pagosGrid').jqGrid('getRowData', rowId);
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
            jgrid.updateGridDiario();
            ActualizarSaldoInfo();
        });
        $(element).attr('onkeypress', 'return  validar_decimal(event)');
        if (parseFloat($(element).val()) === 0) $(element).val("");
        $(element).css('text-align', 'right').focus();
    } else { $(element).parent().html(''); };
};



/**CARGAR DATOS DE LOS ANTICIPOS */

function loadAnticipos() {
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

//NUEVOS METODOS POR WILSON BELDUMA

function calcularMontosPagar() {
    //Obtener el saldo
    limpiarPagoAnt();
    valorNumero = $("#monto_total").val();
    var i = 0;
    $fecha_pago_factura = $("#Com_Fec").val();//fecha del pago de factura
    $("#Lista_Anticipos tbody tr[role='row']").each(function (i) {
        if (i != 0) {
            var codFila = $(this).attr('id');
            var sal = $(this).find("td[aria-describedby='Lista_Anticipos_saldo_aux']").text().replace(',', '');

            var fecha_anticipo = $(this).find("td[aria-describedby='Lista_Anticipos_Atp_Fec']").text();//Capturar las fechas
            var cod_anticipo = $(this).find("td[aria-describedby='Lista_Anticipos_Atp_Cod']").text();//Capturar cod. Ant

            if ($fecha_pago_factura < fecha_anticipo) {
                msg_alerta_anticipos($fecha_pago_factura, cod_anticipo, fecha_anticipo);
                return false;
            }

            $("#sg_act_" + codFila).prop('checked', true);
            $("#sg_pago_" + codFila).removeAttr("readonly");

            var saldoNumero = parseFloat(sal);
            // console.log(valorNumero + ">=" + saldoNumero);

            if (valorNumero >= saldoNumero) {
                $("#sg_pago_" + codFila).val(saldoNumero.toFixed(2));
                valorNumero -= saldoNumero;
            } else {
                $("#sg_pago_" + codFila).val(parseFloat(valorNumero).toFixed(2));
                return false;
            }
        }
        i++;
    });
    actualizarTotalesAnt();
}




function limpiarPagoAnt() {
    var i = 0;
    $("#Lista_Anticipos tbody tr[role='row']").each(function (i) {
        if (i != 0) {
            var codFila = $(this).attr('id');
            $("#sg_act_" + codFila).prop('checked', false);
            $("#sg_pago_" + codFila).attr("readonly", "");
            $("#sg_pago_" + codFila).val("0.00");
        }
        i++;
    });
}

function setPagoCellAnticipo(row) {

    var $fecha_pago_factura = $("#Com_Fec").val();
    var fecha_anticipo = ($('tr#' + row + ' td[aria-describedby="Lista_Anticipos_Atp_Fec"]').text());
    var cod_anticipo = ($('tr#' + row + ' td[aria-describedby="Lista_Anticipos_Atp_Cod"]').text());
    if ($fecha_pago_factura < fecha_anticipo) {
        msg_alerta_anticipos($fecha_pago_factura, cod_anticipo, fecha_anticipo);
        $("#sg_act_" + row).prop('checked', false);
        return false;
    } else {

        if ($("#sg_act_" + row).prop('checked')) {
            $("#sg_pago_" + row).removeAttr("readonly");
            var saldoPagar = parseFloat($('tr#' + row + ' td[aria-describedby="Lista_Anticipos_saldo_aux"]').text().replace(/[^\d.-]/g, '')).toFixed(2);
            $("#sg_pago_" + row).val(saldoPagar);
            // $("#sg_pago_" + row).val("0.00");

        } else {
            $("#sg_pago_" + row).attr("readonly", "");
            $("#sg_pago_" + row).val("0.00");
        }
        actualizarTotalesAnt();
    }
}

function msg_alerta_anticipos($fecha_pago_factura, cod_anticipo, fecha_anticipo) {
    $.alert(" La fecha de la factura a cancelar es: " + $fecha_pago_factura + " y el anticipos Nro: " + cod_anticipo + " esta con fecha: " + fecha_anticipo + ", por este motivo no se puede cancelar la factura con fecha anterior al anticipo.");

}

function redondear(num, decimales) {
    const factor = Math.pow(10, decimales);
    return Math.round(num * factor) / factor;
}


function actualizarTotalesAnt() {
    //obtener todos los ids para buscar valores de debe y haber
    let ids = $('#Lista_Anticipos').jqGrid('getDataIDs');
    let abonos = 0, total_pago = 0.00, tot = 0, auxiliar = 0, valor_pagado = 0;
    for (let i = 0; i < ids.length; i++) {
        let reg_pago = $('#Lista_Anticipos').jqGrid('getRowData', ids[i]);
        if (typeof $('#sg_pago_' + ids[i]).val() !== 'undefined') {
            auxiliar = parseFloat($('#sg_pago_' + ids[i]).val());
            valor_pagado = (parseFloat(reg_pago.Dac_Val_Aux) + auxiliar);
            //  var saldo_restante =    (parseFloat(reg_pago.saldo_aux) - auxiliar);
            var saldo_restante = (redondear(parseFloat(reg_pago.saldo_aux), 2) - auxiliar);
            console.log("Id:" + reg_pago.Atp_Cod + "Este valor resulta:" + parseFloat(reg_pago.saldo_aux) + " -  " + auxiliar + "=" + saldo_restante);
            if (saldo_restante < 0) {
                $.alert("Cantidad ingresada supera al saldo del anticipo");
                $("#sg_pago_" + ids[i]).val("0.00");
            } else {
                $('#Lista_Anticipos').jqGrid('setCell', ids[i], "saldo", (parseFloat(reg_pago.saldo_aux) - auxiliar));
                $('#Lista_Anticipos').jqGrid('setCell', ids[i], "Dac_Val", (parseFloat(valor_pagado)));
                total_pago += parseFloat($('#sg_pago_' + ids[i]).val());
            }
        }
    }

    let ids_actual = $('#Lista_Anticipos').jqGrid('getDataIDs');
    for (let i = 0; i < ids_actual.length; i++) {
        let reg_pago = $('#Lista_Anticipos').jqGrid('getRowData', ids[i]);
        tot = tot + parseFloat(reg_pago.Dac_Val);//pagado
        abonos = abonos + parseFloat(reg_pago.saldo);
    }

    $('#Lista_Anticipos').jqGrid('footerData', 'set', { Atp_Val: $('#Lista_Anticipos').jqGrid('getCol', 'Atp_Val', false, 'sum') });
    $('#Lista_Anticipos').jqGrid('footerData', 'set', { Dac_Val: "" + tot });
    $('#Lista_Anticipos').jqGrid('footerData', 'set', { saldo: "" + abonos });
    $('#Lista_Anticipos').jqGrid("footerData", "set", { saldo_pagar: "" + total_pago });
    $("#sg_pago_undefined").parent().append("$ " + parseFloat(total_pago).toFixed(2));
    $("#sg_pago_undefined").remove();
    return total_pago;
}


function load_anticipos() {
    $("#Prv_Cod_ant").val($("#agg_Prv_Cod").val());
    console.log($("#agg_Prv_Cod").val());
    Lista_Anticipos.createGrid({
        width: '100%',
        height: 350,
        mtype: "GET",
        datatype: "json",
        regional: 'es',
        responsive: false,
        autowidth: true,
        shrinkToFit: true,
        cmTemplate: {
            sortable: false
        },

        colModel: [{
            label: 'Cod.Int.',
            name: 'Atp_Cod',
            width: 70,
            key: true,
            align: "left"
        },
        {
            label: 'No. Compr.',
            name: 'Com_Cod',
            width: 80,
            align: "left"
        },
        {
            label: 'Fecha',
            name: 'Atp_Fec',
            width: 80,
            align: "left"
        },
        {
            label: 'Cédula',
            name: 'Prs_Ced',
            width: 100,
            align: "left"
        },
        {
            label: 'Proveedor',
            name: 'nombre',
            width: 250,
            align: "left"
        },
        {
            label: 'Total',
            name: 'Atp_Val',
            width: 75,
            align: "left",
            formatter: 'currency',
            summaryType: "sum"
        },

        {
            label: 'Pagado',
            name: 'Dac_Val',
            width: 75,
            formatter: 'currency',
            align: "center",
            summaryType: "sum"
        },

        {
            label: 'Pagado',
            name: 'Dac_Val_Aux',
            formatter: 'currency',
            width: 75,
            hidden: true,
            align: "center",
        },

        {
            label: 'Saldo aux',
            name: 'saldo_aux',
            width: 75,
            align: "center",
            hidden: true
        },

        {
            label: 'Saldo',
            name: 'saldo',
            width: 75,
            formatter: 'currency',
            align: "center",
            summaryType: "sum"
        },

        {
            label: '<i class="ui-icon ui-icon-circle-check"></i>',
            name: 'act',
            width: 35,
            align: 'center',
            viewable: false,
            formatter: function (cellvalue, options, rowObject) {
                if (rowObject.vencimiento === 'Pagado') {
                    return "";
                } else {
                    return '<input id="sg_act_' + rowObject.Atp_Cod + '" type="checkbox" value="" onChange="setPagoCellAnticipo(\'' + rowObject.Atp_Cod + '\')" offval="no">';
                }
            },
            title: false,
            formatoptions: {
                disabled: false
            },
            resizable: false
        },

        {
            classes: 'columnDisabled no_padding',
            label: 'Cantidad a pagar',
            name: 'saldo_pagar',
            width: 90,
            align: "left",
            formatter: function (cellvalue, options, rowObject) {
                if (rowObject.vencimiento === 'Pagado') {
                    return "";
                } else {
                    return '<input id="sg_pago_' + rowObject.Atp_Cod + '" class="form-control input-xs" type="text" value="0.00" onChange="actualizarTotalesAnt();$(this).val(parseFloat($(this).val()).toFixed(2))" autocomplete="off" onkeypress="return  validar_decimal(event)" readonly>';
                }
            },
            editable: true,
            edittype: 'text',
            editoptions: {
                type: 'number',
                style: 'text-align: right;',
                min: 0,
                onchange: "validaVal($(this).attr('id'),this.value);"
            }
        }
        ],
        pager: "#Pag_Cli",
        rownumbers: true,
        rowNum: 10000,
        gridview: true,
        viewrecords: true,
        footerrow: true,
        userDataOnFooter: true,

        loadComplete: function () {

            $('#Lista_Anticipos').jqGrid('footerData', 'set', {
                Atp_Val: $('#Lista_Anticipos').jqGrid('getCol', 'Atp_Val', false, 'sum')
            });

            $('#Lista_Anticipos').jqGrid('footerData', 'set', {
                Dac_Val: $('#Lista_Anticipos').jqGrid('getCol', 'Dac_Val', false, 'sum')
            });

            $('#Lista_Anticipos').jqGrid('footerData', 'set', {
                saldo: $('#Lista_Anticipos').jqGrid('getCol', 'saldo', false, 'sum')
            });
            $('#Lista_Anticipos').jqGrid('footerData', 'set', {
                saldo_pagar: $('#Lista_Anticipos').jqGrid('getCol', 'saldo_pagar', false, 'sum')
            });
        }
    }, false, "");
}








//NUEVOS METODOS POR Asignar el motno al cruze de cuentas
function calcularMontosPagarCruze() {
    limpiarPagoCruzeCuentas();
    var valorNumero = $("#monto_total_cruze").val();
    $("#lista_facturas_cruze tbody tr[role='row']").each(function (i) {
        if (i != 0) {
            var codFila = $(this).attr('id');
            var saldoNumero = parseFloat($(this).find("td[aria-describedby='lista_facturas_cruze_saldo']").text().replace(',', '').replace(/[$\s]/g, ""));
            $("#sg_act_" + codFila).prop('checked', true);
            $("#sg_pago_" + codFila).removeAttr("readonly");
            if (valorNumero >= saldoNumero) {
                $("#sg_pago_" + codFila).val(saldoNumero.toFixed(2));
                valorNumero -= saldoNumero;
            } else {
                $("#sg_pago_" + codFila).val(parseFloat(valorNumero).toFixed(2));
                return false;
            }
        }
        i++;
    });
    actualizarTotalesCruze();
}
//FACTURAS PARA EL CRUZE DE CUENTAS

function lista_facturas_cruze() {
    $("#formPago_cdc #Prv_Cod_Cdc").val($("#agg_Prv_Cod").val());
    console.log("codigo del cliente:  " + $("#agg_Prv_Cod").val());
    list_facturas_cruze.createGrid({
        width: '100%', height: 350, mtype: "GET", datatype: "json", regional: 'es', responsive: false, autowidth: true, shrinkToFit: true,
        cmTemplate: { sortable: false },
        colModel: [{ label: 'Cod.', name: 'Vet_Cod', width: 50, key: true, align: "left" },
        { label: 'No. Compr.', name: 'Com_Cod', width: 80, align: "left", hidden: true },
        { label: 'Fecha', name: 'Caj_Fec', width: 80, align: "left" },
        // { label: 'Cédula', name: 'Prs_Ced', width: 100, align: "left" },
        { label: 'Num.Vet', name: 'Vet_Num', width: 130, align: "left" },
        { label: 'Cpc_Cod', name: 'Cpc_Cod', width: 130, align: "left", hidden: true },
        { label: 'Negoci.', name: 'Num_Neg', width: 80, align: "left", hidden: false },
        { label: 'Proveedor', name: 'proveedor', width: 250, align: "left" },
        { label: 'Total', name: 'Asi_Val', width: 75, align: "left", formatter: 'currency', summaryType: "sum" },
        { label: 'Abono', name: 'Abono', width: 75, formatter: 'currency', align: "center", summaryType: "sum" },
        { label: 'Saldo', name: 'saldo', width: 75, formatter: 'currency', align: "center", summaryType: "sum" },
        {
            label: '<i class="ui-icon ui-icon-circle-check"></i>', name: 'act', width: 35, align: 'center', viewable: false,
            formatter: function (cellvalue, options, rowObject) {
                if (rowObject.vencimiento === 'Pagado') {
                    return "";
                } else {
                    return '<input id="sg_act_' + rowObject.Vet_Cod + '" type="checkbox" value="" onChange="setPagoCellCruzeCuentas(\'' + rowObject.Vet_Cod + '\')" offval="no">';
                }
            },
            title: false,
            formatoptions: {
                disabled: false
            },
            resizable: false
        },
        {
            classes: 'columnDisabled no_padding',
            label: 'Cantidad a pagar',
            name: 'saldo_pagar',
            width: 90,
            align: "left",
            formatter: function (cellvalue, options, rowObject) {
                if (rowObject.vencimiento === 'Pagado') {
                    return "";
                } else {
                    return '<input id="sg_pago_' + rowObject.Vet_Cod + '" class="form-control input-xs" type="text" value="0.00" onChange="actualizarTotalesAnt();$(this).val(parseFloat($(this).val()).toFixed(2))" autocomplete="off" onkeypress="return  validar_decimal(event)" readonly>';
                }
            },
            editable: true,
            edittype: 'text',
            editoptions: {
                type: 'number',
                style: 'text-align: right;',
                min: 0,
                onchange: "validaVal($(this).attr('id'),this.value);"
            }
        }
        ],
        pager: "#Pag_Cli_CDC",
        rownumbers: true,
        rowNum: 10000,
        gridview: true,
        viewrecords: true,
        footerrow: true,
        userDataOnFooter: true,
        loadComplete: function () {
            $('#lista_facturas_cruze').jqGrid('footerData', 'set', {
                Asi_Val: $('#lista_facturas_cruze').jqGrid('getCol', 'Asi_Val', false, 'sum')
            });
            $('#lista_facturas_cruze').jqGrid('footerData', 'set', {
                Dac_Val: $('#lista_facturas_cruze').jqGrid('getCol', 'Dac_Val', false, 'sum')
            });
            $('#lista_facturas_cruze').jqGrid('footerData', 'set', {
                saldo: $('#lista_facturas_cruze').jqGrid('getCol', 'saldo', false, 'sum')
            });
            $('#lista_facturas_cruze').jqGrid('footerData', 'set', {
                saldo_pagar: $('#lista_facturas_cruze').jqGrid('getCol', 'saldo_pagar', false, 'sum')
            });
        }
    }, false, "");
}

//Abrir un modal para realizar el cruze de cuentas

function open_facturas_cruze() {
    lista_facturas_cruze();
    $('#lista_facturas_cruze').Search('#formPago_cdc', 'loadCruzeCuentas');
    $("#monto_total_cruze").val($("#Com_Val_pago").val());
    $('#agregar_cruze_cuentas').dialog({
        autoOpen: false, // No abrir automáticamente al crear
        modal: true, // Modal (fondo oscurecido y bloqueo de fondo)
        width: '65%', // Ancho automático según el contenido
        title: 'Facturas para realizar cruzes', // Título del modal
        buttons: {
            Siguiente: {
                text: "Siguiente",
                class: 'btn-siguiente', // Clase CSS para el botón
                click: function () {
                    var valor = $('td[aria-describedby="lista_facturas_cruze_saldo_pagar"].columnDisabled').text();
                    var numero = parseFloat(valor.replace(/[^\d.-]/g, ''));
                    var monto_total = $("#monto_total_cruze").val();
                    if (monto_total == numero) {
                        $(this).dialog('close');
                    } else {
                        $.alert("Existen errores en la asignación de cantidades, vuelva a revisar");
                    }
                }
            }
        },
        open: function (event, ui) {
            $(this).parent().find('.btn-siguiente').addClass('btn-primary'); // Ejemplo: btn-primary de Bootstrap
        }
    });
    $('#agregar_cruze_cuentas').dialog('open');
}




// Actualizar totales con cruze de cuentas
function actualizarTotalesCruze() {
    //obtener todos los ids para buscar valores de debe y haber
    let ids = $('#lista_facturas_cruze').jqGrid('getDataIDs');
    let abonos = 0, total_pago = 0.00, tot = 0, auxiliar = 0, valor_pagado = 0;
    for (let i = 0; i < ids.length; i++) {
        let reg_pago = $('#lista_facturas_cruze').jqGrid('getRowData', ids[i]);
        if (typeof $('#sg_pago_' + ids[i]).val() !== 'undefined') {
            // auxiliar = parseFloat($('#sg_pago_' + ids[i]).val());
            valor_pagado = (parseFloat(reg_pago.Dac_Val) + auxiliar);
            var saldo_restante = (redondear(parseFloat(reg_pago.saldo), 2) /*- auxiliar*/);
            $('#lista_facturas_cruze').jqGrid('setCell', ids[i], "saldo", (parseFloat(reg_pago.saldo) /*- auxiliar*/));
            $('#lista_facturas_cruze').jqGrid('setCell', ids[i], "Dac_Val", (parseFloat(valor_pagado)));
            total_pago += parseFloat($('#sg_pago_' + ids[i]).val());
            // }
        }
    }
    let ids_actual = $('#lista_facturas_cruze').jqGrid('getDataIDs');
    for (let i = 0; i < ids_actual.length; i++) {
        let reg_pago = $('#lista_facturas_cruze').jqGrid('getRowData', ids[i]);
        tot = tot + parseFloat(reg_pago.Dac_Val);//pagado
        abonos = abonos + parseFloat(reg_pago.saldo);
    }
    $('#lista_facturas_cruze').jqGrid('footerData', 'set', { Asi_Val: $('#lista_facturas_cruze').jqGrid('getCol', 'Asi_Val', false, 'sum') });
    $('#lista_facturas_cruze').jqGrid('footerData', 'set', { Dac_Val: "" + tot });
    $('#lista_facturas_cruze').jqGrid('footerData', 'set', { saldo: "" + abonos });
    $('#lista_facturas_cruze').jqGrid("footerData", "set", { saldo_pagar: "" + total_pago });
    $("#sg_pago_undefined").parent().append("$ " + parseFloat(total_pago).toFixed(2));
    $("#sg_pago_undefined").remove();
    return total_pago;
}





//Marcar 
function setPagoCellCruzeCuentas(row) {
    let valor_pagar = parseFloat($("#monto_total_cruze").val()) || 0;

    // Calcular el total ya asignado en otras filas activas
    let ids = $('#lista_facturas_cruze').jqGrid('getDataIDs');
    let valor_asignado = 0;

    ids.forEach(function (id) {
        if (id !== row && $("#sg_act_" + id).prop('checked')) {
            let val = parseFloat($("#sg_pago_" + id).val()) || 0;
            valor_asignado += val;
        }
    });

    let valor_restante = valor_pagar - valor_asignado;

    if ($("#sg_act_" + row).prop('checked')) {
        $("#sg_pago_" + row).removeAttr("readonly");

        // Obtener saldo real de la fila
        let saldoPagar = parseFloat($('tr#' + row + ' td[aria-describedby="lista_facturas_cruze_saldo"]').text().replace(/[^\d.-]/g, '')) || 0;

        // Validar si aún hay valor para asignar
        if (valor_restante <= 0) {
            $.alert("El valor es mayor al monto a pagar.");
            $("#sg_pago_" + row).val("0.00");
        } else {
            // Solo pagar lo que quede disponible o el saldo, lo que sea menor
            let pagar = Math.min(valor_restante, saldoPagar);
            $("#sg_pago_" + row).val(pagar.toFixed(2));
        }

    } else {
        $("#sg_pago_" + row).attr("readonly", "");
        $("#sg_pago_" + row).val("0.00");
    }

    actualizarTotalesCruzeCuentas(row);
}


function actualizarTotalesCruzeCuentas(row) {
    let valor_pagar = $("#monto_total_cruze").val();
    let abonos = 0, total_pago = 0.00, tot = 0, auxiliar = 0, valor_pagado = 0, acum_aux = 0;
    let reg_pago = $('#lista_facturas_cruze').jqGrid('getRowData', row);
    if (typeof $('#sg_pago_' + row).val() !== 'undefined' && $('#sg_pago_' + row).val() > 0) {
        acum_aux = acum_aux + parseFloat($('#sg_pago_' + row).val());
        auxiliar = parseFloat($('#sg_pago_' + row).val());
        valor_pagado = (parseFloat(reg_pago.Abono) + auxiliar);
  
        $('#lista_facturas_cruze').jqGrid('setCell', row, "saldo", (parseFloat(reg_pago.saldo) - auxiliar));
        $('#lista_facturas_cruze').jqGrid('setCell', row, "Abono", (parseFloat(valor_pagado)));
        // }
    }
    let ids_actual = $('#lista_facturas_cruze').jqGrid('getDataIDs');
    for (let i = 0; i < ids_actual.length; i++) {
        let reg_pago = $('#lista_facturas_cruze').jqGrid('getRowData', ids_actual[i]);

        tot = tot + parseFloat(reg_pago.Abono);//pagado
        abonos = abonos + parseFloat(reg_pago.saldo);
        total_pago += parseFloat($("#sg_pago_" + ids_actual[i]).val());

        // }
    }
    $('#lista_facturas_cruze').jqGrid('footerData', 'set', { Asi_Val: $('#lista_facturas_cruze').jqGrid('getCol', 'Asi_Val', false, 'sum') });
    $('#lista_facturas_cruze').jqGrid('footerData', 'set', { Abono: "" + tot });
    $('#lista_facturas_cruze').jqGrid('footerData', 'set', { saldo: "" + abonos });
    $('#lista_facturas_cruze').jqGrid("footerData", "set", { saldo_pagar: "" + total_pago });
    $("#sg_pago_undefined").parent().append("$ " + parseFloat(total_pago).toFixed(2));
    $("#sg_pago_undefined").remove();
    return total_pago;
}



//Limpiar campos cruze
function limpiarPagoCruzeCuentas() {
    var i = 0;
    $("#lista_facturas_cruze tbody tr[role='row']").each(function (i) {
        if (i != 0) {
            var codFila = $(this).attr('id');
            $("#sg_act_" + codFila).prop('checked', false);
            $("#sg_pago_" + codFila).attr("readonly", "");
            $("#sg_pago_" + codFila).val("0.00");
        }
        i++;
    });
}
