let ids_pagos = 0;
var Lista_Anticipos;
$(function () {
    Lista_Anticipos = $("#Lista_Anticipos");

    try {
        load_anticipos();
    } catch {
        console.log("anticipos null");
    }
    // inicializa componentes de fecha en formulario de anticipos
    $('.datepicker').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });

    if ($('#successDialog').length === 1)
        $("#successDialog").createDialog({ width: 500, height: 200, icon: 'print' });
    if ($('#comprasDialog').length === 1)
        $("#comprasDialog").createDialog({width:930,height:570,icon:'random'});
    if ($('#verFactsDialog').length === 1)
        $("#verFactsDialog").createDialog({ width: 700, height: 400, icon: 'info-sign' });

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

    if ($('#verPagosDialogMod').length === 1)
        $("#verPagosDialogMod").createDialog({ width: 700, height: 490, icon: 'info-sign' });

    if ($('#searchGrid').length === 1) {
        if ($('#clientesDialog').length === 1)
            $.createSearchDialog('clientesDialog', [
                { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15, align: "center", hidden: true },
                { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
                { label: 'Cliente', name: 'nombre', width: 100 },
                { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
                { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectCliente } }
            ], null, null, null, { headertitles: true }, { title: 'Cliente', text: 'searchCli' });

        cargarFaturasClientes();
    }
    if ($('#searchGrid_mod').length === 1) {
        if ($('#clientesDialog').length === 1)
            $.createSearchDialog('clientesDialog', [
                { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15, align: "center", hidden: true },
                { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
                { label: 'Cliente', name: 'nombre', width: 100 },
                { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
                { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectCliente_mod } }
            ], null, null, null, { headertitles: true }, { title: 'Cliente', text: 'searchCli' });

        cargarFaturasClientesMod();
    }

    //tabla de cheques
    if ($('#showPagosChe').length === 1)
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
                { label: 'Banco', name: 'Bak_Des', width: 15 },
                { label: 'No. Che.', name: 'Che_Num', width: 15, align: "left" },
                { label: 'Fecha', name: 'Che_Fec', width: 15, align: "left" },
                { label: 'Observaci&oacute;n', name: 'Che_Obs', width: 25, align: "left" },
                {
                    label: 'Valor', name: 'Che_Val', width: 15, align: 'right', formatter: 'currency', editable: true,
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
                        if (rowObject.Che_Est === 'S' || rowObject.Che_Est === 'A') {
                            return $.getGridButton(preProtestarCheque, rowObject, 'Marcar como protestado', 'ban-circle', '', 'danger');
                        } else {
                            return "-";
                        }
                    }
                }
            ]
        }, true, '', { view: false });

    //tabala para visualizar asientos de un abono
    if ($('#showPagosAsi').length === 1)
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
                    label: 'Debe', name: 'Debe', width: 10, align: 'right', formatter: 'currency', editable: true,
                    formatoptions: {
                        prefix: '$ ',
                        thousandsSeparator: ',',
                        decimalSeparator: '.',
                        defaultValue: ''
                    }
                },
                {
                    label: 'Haber', name: 'Haber', width: 10, align: 'right', formatter: 'currency', editable: true,
                    formatoptions: {
                        prefix: '$ ',
                        thousandsSeparator: ',',
                        decimalSeparator: '.',
                        defaultValue: ''
                    }
                }
            ]
        }, true, '', { view: false });

    $("#tabs_abo_det").tabs();

    $('#OrderBy').on('change', function () {
        console.log("Buscando");
        $('input[name=order]').val($(this).val());
        $('#searchCccc').formSubmit();
    });
    if ($('#crucesGrid').length === 1)
        gridCompras();
    //FIN DE FUNCION INICIAL*******
});

$(document).ready(function () {
    $("#Tia_Cod option[data-abr='IN']").prop("selected", true);
});

function setFecPeriodoCom() {
    $("#Com_Fec").dateLimits($("#Pec_Cod option:selected").attr("data-pec-fei"), $("#Pec_Cod option:selected").attr("data-pec-fef"));
}

//permite el ingreso unicamente de numeros
function soloNumeros(e) {
    // valor = valor.replace(/[^0-9]/g,'');
    let key = window.Event ? e.which : e.keyCode
    return (key >= 48 && key <= 57)
}

var numeroCheque = false;
//verifica si el numero de un cheque ya se encuentra registrado
function verificarNoCheque(valor) {
    datach = { "verificarCheNum": true, "Che_Num": valor, "Bak_Cod": $("#Bak_Cod option:selected").attr("value"), Cli_Cod: $("#agg_Cli_Cod").val() };
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

//funcion para ejecutar despues de seleccionar el cliente
function selectCliente(cliente) {
    $("#Cli_Cod").val(cliente.Cli_Cod);
    $("#Prs_Ced").val(cliente.Prs_Ced);
    $("#nombre").val(cliente.nombre);
    $("#Prs_Dir").val(cliente.Prs_Dir);
    $('#clientesDialog').dialog('close');
    $('#searchGrid').Search('#searchCccc', 'ajaxComprobante');
}
//funcion para ejecutar despues de seleccionar el cliente
function selectCliente_mod(cliente) {
    $("#Cli_Cod").val(cliente.Cli_Cod);
    $("#Prs_Ced").val(cliente.Prs_Ced);
    $("#nombre").val(cliente.nombre);
    $("#Prs_Dir").val(cliente.Prs_Dir);
    $('#clientesDialog').dialog('close');
    $('#searchGrid_mod').Search('#searchCccc', 'ajaxComprobante');
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
        { label: 'factu', name: 'factu', hidden: true },
        { label: 'Asi_Cod', name: 'Asi_Cod', hidden: true, classes: 'bgNoRight' },
        { label: 'Pag_Cod', name: 'Pag_Cod', hidden: true, classes: 'bgNoRight' },
        { label: 'Pag_Abr', name: 'Pag_Abr', hidden: true, classes: 'bgNoRight' },
        { label: 'Tipo', name: 'Pag_Des', width: 10, align: "center", classes: 'bgNoRight' },
        { label: 'Bak_Cod', name: 'Bak_Cod', hidden: true, classes: 'bgNoRight' },
        { label: 'No. che.', name: 'Che_Num', width: 10, classes: 'bgNoRight' },
        { label: 'Che_Fec', name: 'Che_Fec', hidden: true, classes: 'bgNoRight' },
        { name: 'Che_Cli', hidden: true },
        { label: 'Pap_Cto', name: 'Pac_Cto', hidden: true, classes: 'bgNoRight' },
        { label: 'Pap_Ctd', name: 'Pac_Ctd', hidden: true, classes: 'bgNoRight' },
        { label: 'Cuenta_Pld', name: 'Pld_Cod', hidden: true, classes: 'bgNoRight' },
        { label: 'C&oacute;digo', name: 'Pld_Cdc', width: 10, classes: 'bgNoRight' },
        { label: 'Cuenta', name: 'Pld_Des', width: 20, classes: 'bgNoRight' },
        { label: '', name: 'concepto', hidden: true },
        { label: 'Glosa', name: 'Glosa', width: 20, editable: true },
        {
            label: 'Debe', name: 'Debe', width: 10, align: 'right', formatter: 'currency', editable: true,
            formatoptions: {
                defaultValue: ''
            },
            editoptions: {
                dataInit: function (element) {
                    $(this).createInputDiario3(element, "D", "Det_Tip");
                }
            }
        },
        {
            label: 'Haber', name: 'Haber', width: 10, align: 'right', formatter: 'currency', editable: true,
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

function cargarFaturasClientes() {
    $("#searchGrid").createGrid({
        caption: 'Lista de documentos <div class="pull-right"><b>ORDENAR POR:</b>&nbsp;<select id="OrderBy"><option value="">No ordenar</option><option value="Vet_Num ASC">Nro. Doc.</option><option value="ccpp_cobrar.Com_Cod Asc">No Compr Asc.</option>  <option value="caja_aper.Caj_Fec DESC">Fecha Emi. DESC.</option> <option value="caja_aper.Caj_Fec ASC">Fecha Emi ASC.</option></select>&nbsp;</div>',
        postData: $("#searchCccc").getData("ajaxComprobante"),
        height: 300,
        mtype: "GET",
        datatype: "local",
        regional: 'es',
        shrinkToFit: true,
        colModel: [
            { label: 'Cód.Int.', name: 'Cpc_Cod', key: true, hidden: true, viewable: true },
            { label: 'Cód.Int.', name: 'Asi_Cod', hidden: true },
            { label: 'Pld_Cod.', name: "Pld_Cod", hidden: true },
            { label: 'Pld_Cdc.', name: "Pld_Cdc", hidden: true },
            { label: 'Pld_Des.', name: "Pld_Des", hidden: true },
            { label: 'No. Compr.', name: 'Com_Codigo', align: "center", width: 25 },
            { label: 'Fecha Emis.', name: 'Caj_Fec', align: "center", width: 30 },
            { label: 'Fecha Venc.', name: 'Cpc_Ven', align: "center", width: 30 },
            { label: 'Tipo Documento', name: 'Tic_Des', width: 40 },
            { label: 'Vencimiento', name: 'vencimiento', align: "center", width: 25 },
            {
                label: 'Total',
                name: 'Asi_Val',
                width: 45,
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
                width: 45,
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
                width: 45,
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
            { label: 'No. Docum.', name: 'Vet_Num', width: 55, align: "center" },
            { name: "Cli_Cod", hidden: true },
            { label: 'Prs_Ape.', name: "Prs_Ape", hidden: true },
            { label: 'Prs_Nom.', name: "Prs_Nom", hidden: true },
            { label: 'Cliente', name: 'cliente_n', width: 75 },
            { label: 'Obs', name: 'Vet_Obs', width: 75 },
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
                        return '<input id="sg_act_' + rowObject.Cpc_Cod + '" type="checkbox" value="" onChange="setPagoCell(\'' + rowObject.Cpc_Cod + '\')" offval="no">';
                    }
                },
                title: false,
                formatoptions: { disabled: false },
                resizable: false
            },
            {
                classes: 'columnDisabled no_padding',
                label: 'A Cobrar',
                name: 'Pago',
                width: 35,
                align: 'right',
                formatter: function (cellvalue, options, rowObject) {
                    if (rowObject.vencimiento === 'Pagado') {
                        return "";
                    } else {
                        return '<input id="sg_pago_' + rowObject.Cpc_Cod + '" class="" type="text" value="0.00" onChange="actualizarTotalesSG();$(this).val(parseFloat($(this).val()).toFixed(2))" autocomplete="off" onkeypress="return  validar_decimal(event)" readonly>';
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
        subGridRowExpanded: function (subgrid_id, row_id) {
            let subgrid_table_id = subgrid_id + "_t";
            let ccc_data = $('#searchGrid').jqGrid('getRowData', row_id);
            $("#" + subgrid_id).html("<table id='" + subgrid_table_id + "' class='scroll'></table>");
            $("#" + subgrid_table_id).createGrid({
                url: "?abonosDetAjax=" + ccc_data.Cpc_Cod,
                datatype: "json",
                regional: 'es',
                height: 'auto',
                colModel: [
                    { label: '', name: 'indx', key: true, hidden: true },
                    { label: '', name: 'Com_Cod', hidden: true },
                    { label: '', name: 'Cpc_Cod', hidden: true },
                    { label: '', name: 'reg_editable', hidden: true },
                    { label: '', name: 'Com_Val', hidden: true },
                    { label: '', name: 'Com_Con', hidden: true },
                    { label: '', name: 'Com_Obs', hidden: true },
                    { label: '', name: 'Pec_Cod', hidden: true },
                    { label: '', name: 'Pag_Abr', hidden: true },
                    { label: 'No. Compr.', name: 'Com_Codigo', width: 30, align: "left" },
                    { label: 'Fecha', name: 'Com_Fec', width: 50, align: "left" },
                    {
                        label: 'Valor',
                        name: 'Cpc_Val',
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
                    { label: 'Observaci&oacute;n', name: 'Cpc_Obs', width: 140, align: "left" },
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
}

function cargarFaturasClientesMod() {
    $("#searchGrid_mod").createGrid({
        postData: $("#searchCccc").getData("ajaxComprobante"),
        height: 300,
        mtype: "GET",
        datatype: "local",
        regional: 'es',
        shrinkToFit: true,
        colModel: [
            { label: 'Cód.Int.', name: 'Cpc_Cod', key: true, hidden: true, viewable: true },
            { label: 'Cód.Int.', name: 'Asi_Cod', hidden: true },
            { label: 'Pld_Cod.', name: "Pld_Cod", hidden: true },
            { label: 'Pld_Cdc.', name: "Pld_Cdc", hidden: true },
            { label: 'Pld_Des.', name: "Pld_Des", hidden: true },
            { label: 'No. Compr.', name: 'Com_Codigo', align: "center", width: 25 },
            { label: 'Fecha Emis.', name: 'Caj_Fec', align: "center", width: 30 },
            { label: 'Fecha Venc.', name: 'Cpc_Ven', align: "center", width: 30 },
            { label: 'Vencimiento', name: 'vencimiento', align: "center", width: 25 },
            {
                label: 'Total',
                name: 'Asi_Val',
                width: 45,
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
                width: 45,
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
                width: 45,
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
            { label: 'No. Docum.', name: 'Vet_Num', width: 55, align: "center" },
            { name: "Cli_Cod", hidden: true },
            { label: 'Prs_Ape.', name: "Prs_Ape", hidden: true },
            { label: 'Prs_Nom.', name: "Prs_Nom", hidden: true },
            { label: 'Cliente', name: 'cliente_n', width: 75 }
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
            let ccc_data = $('#searchGrid_mod').jqGrid('getRowData', row_id);
            $("#" + subgrid_id).html("<table id='" + subgrid_table_id + "' class='scroll'></table>");
            $("#" + subgrid_table_id).createGrid({
                url: "?abonosDetAjax=" + ccc_data.Cpc_Cod,
                datatype: "json",
                regional: 'es',
                height: 'auto',
                colModel: [
                    { label: '', name: 'indx', key: true, hidden: true },
                    { label: '', name: 'Com_Cod', hidden: true },
                    { label: '', name: 'Cpc_Cod', hidden: true },
                    { label: '', name: 'reg_editable', hidden: true },
                    { label: '', name: 'Com_Val', hidden: true },
                    { label: '', name: 'Com_Con', hidden: true },
                    { label: '', name: 'Com_Obs', hidden: true },
                    { label: '', name: 'Pec_Cod', hidden: true },
                    { label: '', name: 'Pag_Abr', hidden: true },
                    { label: 'No. Compr.', name: 'Com_Codigo', width: 30, align: "left" },
                    { label: 'Fecha', name: 'Com_Fec', width: 50, align: "left" },
                    {
                        label: 'Valor',
                        name: 'Cpc_Val',
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
                        label: 'Observaci&oacute;n', name: 'Cpc_Obs', width: 140, align: "left",
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
                            //para verificar si se trata de un abono negativo en caso de cheques protestados
                            if (rowObject.Pag_Abr == "RET") {
                                return $.getGridButton(verAbono, parm_getdet, 'Ver abono', 'info-sign', '', 'info');
                            }
                            else {
                                if (rowObject.reg_editable === "s") {
                                    return $.getGridButton(verAbono, parm_getdet, 'Ver abono', 'info-sign', '', 'info') + "&nbsp;" +
                                        $.getGridButton(modificarAbono, parm_getdet, 'Modificar abono', 'pencil', '', 'success') + "&nbsp;" +
                                        $.getGridButton(preanularAbono, parm_anu, 'Anular abono', 'trash', '', 'danger');
                                }
                                if (rowObject.reg_editable === "n" && rowObject.ndc === "s") {
                                    return $.getGridButton(verAbono, parm_getdet, 'Ver abono', 'info-sign', '', 'info') + "&nbsp;" +
                                        $.getGridButton(preanularAbono, parm_anu, 'Anular abono', 'remove', '', 'danger');
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

            actualizarTotalesSGmod();

            $('#searchGrid_mod tr').each(function () {
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
        onSelectRow: function (rowid, e) { $("#searchGrid_mod").resetSelection(); },
        multiselect: false
    }, false, "#sgPager", { view: false });
    $("#searchGrid_mod").updateGridsSizes();
    $("#pagosGrid").updateGridsSizes();
}

function cargarFactsGrid() {
    $('#factsGrid').createGrid({
        viewrecords: false,
        caption: "<center>Facturas del Abono</center>",
        data: [],
        rowNum: 100,
        height: 150,
        width: 650,
        responsive: false,
        onSelectRow: function (rowid, e) { $(this).resetSelection(); },
        colModel: [
            { label: '', name: 'Cpc_Cod', hidden: true, key: true },
            { label: '', name: 'Com_Cod', hidden: true },
            { label: '', name: 'flag', hidden: true },
            { label: 'No. Compr.', name: 'Com_Codigo', width: 15, align: "left" },
            { label: 'Fecha Emis.', name: 'Caj_Fec', width: 20, align: "left" },
            { label: 'Fecha Venc.', name: 'Cpc_Ven', width: 20, align: "left" },
            { label: 'No. Doc.', name: 'Vet_Num', width: 20, align: "left" },
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
                label: 'Cobrado',
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
                    return '<input id="fct_act_' + rowObject.Cpc_Cod + '" type="checkbox" value="" onChange="setFactCell(\'' + rowObject.Cpc_Cod + '\')" offval="no" checked="true">';
                },
                title: false,
                formatoptions: { disabled: false },
                resizable: false
            }
        ]
    }, true, '', { view: false });
}
/************** Codigo de Jose **************/
function gridCompras() {
    if ($('#crucesGrid').length !== 1) return;
    $('#crucesGrid').createGrid({
        viewrecords: false,
        caption: "<center>Facturas de Compras</center>",
        data: [], rowNum: 100,height: 350,width: 900,footerrow: true,responsive: false,totalCols:['saldo','cruce','pendiente'],
        onSelectRow: function (rowid, e) { $(this).resetSelection(); },
        colModel: [
            //{ label: 'index', name: 'index', hidden: true, classes: 'bgNoRight' },
            { label: 'ID', key: true,name: 'Cpp_Cod', width: 5 },            
            { label: 'Fecha', name: 'Cop_Fec', width: 8, align: "center" },
            { label: 'Proveedor', name: 'nombre', width: 22, align: "left" },
            { label: 'Factura', name: 'Cop_Num', width: 10, align: "left" },            
            { label:'<i class="ui-icon ui-icon-circle-check"></i>', name: 'chkAnt',width: 3, align:"center", formatter:'checkboxExa',formatoptions:{ dataEvents:{ Change:'setPagoCellCruce(this.dataset.rowId);'}}},
            //{ label: 'Obser.', name: 'Atp_Obs', width: 25, align: "left" },            
            { label: 'Saldo', name: 'saldo', width: 7, align: 'right', formatter:'currency', decimalPlaces: '2', summaryRound: 2,formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: {0}",summaryType: "sum"},
            { label: 'A Cruzar', name:'cruce',width: 7, classes:'columnDisabled no_padding', align:"right", title:false, formatter:'textboxExa', formatoptions:{type:'decimal', decimals:8,attr: {disabled:'disabled'}, dataEvents:{ change:'validaDocsValor.call(this)', keyup:'updateRowItemCruce.call(this);'}} },                        
            { label: 'Pendiente', name: 'pendiente',width: 7, align: 'right', formatter:'currency', decimalPlaces: '2', summaryRound: 2,formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: {0}",summaryType: "sum"}
        ]
    }, true, '', { view: false });    
}
function validaDocsValor(){
     let rowId=$(this).data('rowId');
    let saldo=$('#crucesGrid').getCell(rowId,'saldo');
  	let cruce_act=$('#crucesGrid').getCell(rowId,'cruce');
	let total= $('#crucesGrid').getGridSummary(['cruce']);
    let pag=$('#pagosGrid').getData(); 
    let abo= $.isEmpty(pag.Debe)?0:pag.Debe*1;
    if(cruce_act*1 > saldo*1){
        let valor= pag.Haber*1 - abo.toNum();
        valor= saldo*1<=valor*1?saldo*1:valor*1;
        $("#"+rowId+"_cruce").val( $.toFixed(valor,2));         
        $(this).val(valor).trigger('keyup');
        //$.alert("El valor sobrepasa el pago a realizar!");
    }
}
function setPagoCellCruce(row){
    let saldo = $('#crucesGrid').getCell(row,'saldo');    
    let arr_abo= $('#crucesGrid').getGridSummary(['cruce']);
    let pag=$('#pagosGrid').getData(); 
    let abo= $.isEmpty(pag.Debe)?0:pag.Debe*1;

    if($("#"+row+"_chkAnt").prop('checked')){
        if(arr_abo.cruce*1==0) 
            if(saldo.toNum()>($("#Com_Val").val()*1-abo)) 
                saldo=$("#Com_Val").val()*1 - abo; 
            else{ 
                saldo=($("#Com_Val").val()*1 - abo)>=saldo.toNum()?saldo.toNum():saldo.toNum()-($("#Com_Val").val()*1 - abo);
            }
        else{
            if(arr_abo.cruce==$("#Com_Val").val()*1-abo) return false;
            if(saldo.toNum()+arr_abo.cruce*1>($("#Com_Val").val()*1-abo)) 
                saldo=($("#Com_Val").val()*1 - (abo + arr_abo.cruce*1)); //- saldo*1; 
            else{ 
                saldo=($("#Com_Val").val()*1 - abo)>=(saldo.toNum()+arr_abo.cruce*1)?saldo.toNum():saldo.toNum()-($("#Com_Val").val()*1 - abo);
            }
            //saldo=($("#Com_Val").val()*1 - abo) - arr_abo.cruce *1 ;
        }
		$("#"+row+"_cruce").prop("disabled", false);
		$("#"+row+"_cruce").val(saldo.toNum());
		$('#crucesGrid').setCell(row,'pendiente',$('#crucesGrid').getCell(row,'saldo')*1 - saldo);
	}else{
		$("#"+row+"_cruce").prop("disabled", true);
		$("#"+row+"_cruce").val("0.00");
		$('#crucesGrid').setCell(row,'pendiente',saldo);
	}
	let sum_cruce_ant= $('#crucesGrid').getGridSummary(['cruce']);
	let sum_pendi= $('#crucesGrid').getGridSummary(['pendiente']);
	$('#crucesGrid').jqGrid("footerData", "set", {cruce:""+sum_cruce_ant.cruce.toFixed(2),pendiente:sum_pendi.pendiente.toFixed(2)});
	$('#Com_Val_pago').val(sum_cruce_ant.cruce.toFixed(2));
    $("#lim_val_pago_cc").val(sum_cruce_ant.cruce.toFixed(2));
}
function updateRowItemCruce(){
    let rowId=$(this).data('rowId');
    let saldo=$('#crucesGrid').getCell(rowId,'saldo');
  	let cruce_act=$('#crucesGrid').getCell(rowId,'cruce');
	let total= $('#crucesGrid').getGridSummary(['cruce']);
    let pag=$('#pagosGrid').getData(); 
    let abo= $.isEmpty(pag.Debe)?0:pag.Debe*1;

    if((cruce_act.toNum() >= saldo.toNum())){        
        let valor= pag.Haber*1 - abo.toNum();
        valor= saldo*1<=valor*1?saldo*1:valor*1;
        $("#"+rowId+"_cruce").val( $.toFixed(valor,2));         
        $('#crucesGrid').setCell(rowId,'pendiente',$('#crucesGrid').getCell(rowId,'saldo')*1 - valor);
        //$('#'+rowId+'_chk').prop("checked", false).trigger("onchange");
    }else{
		if(total.cruce*1>($("#Com_Val").val()*1 - abo)){ 
            saldo=total.cruce.toNum()-($("#Com_Val").val()*1 - abo);
            saldo=cruce_act.toNum()-saldo;
            $("#"+rowId+"_cruce").val(saldo);            
        }else{ 
            saldo=cruce_act*1==0?($("#Com_Val").val()*1 - abo)-total.cruce.toNum():cruce_act;
            $(this).val(saldo);
        }
        $('#crucesGrid').setCell(rowId,'pendiente',$('#crucesGrid').getCell(rowId,'saldo')*1 - saldo);
    
    }
	let sum_cruce_ant= $('#crucesGrid').getGridSummary(['cruce']);
	let sum_pendi= $('#crucesGrid').getGridSummary(['pendiente'])
	$('#crucesGrid').jqGrid("footerData", "set", {cruce:""+sum_cruce_ant.cruce.toFixed(2),pendiente:sum_pendi.pendiente.toFixed(2)});
	$('#Com_Val_pago').val(sum_cruce_ant.cruce.toFixed(2));
    $("#lim_val_pago_cc").val(sum_cruce_ant.cruce.toFixed(2));
}
/******************************************/

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
    } else {
        $.alert("No puede descartar todas las facturas");
        $("#fct_act_" + fact).prop('checked', true);
    }
}

function verFacturasAbono(compro) {
    $("#factsGrid").jqGrid("clearGridData").trigger("reloadGrid");
    cargarFactsGrid();
    $.getDataJson('', { getFactsAbono: true, Com_Cod: $("#Com_Cod").val(), Cli_Cod: $("#agg_Cli_Cod").val() }, function (responce) {
        for (let i = 0; i < responce['data'].length; i++) {
            let cpp_data = $('#searchGrid_mod').jqGrid('getRowData', responce['data'][i].Cpc_Cod);
            let saldo_mod = "0";
            if (isNaN(cpp_data.Saldo)) {
                saldo_mod = "0.00";
            } else {
                saldo_mod = cpp_data.Saldo;
            }

            $('#factsGrid').jqGrid('addRowData', responce['data'][i].Cpp_Cod, {
                Cpc_Cod: responce['data'][i].Cpc_Cod,
                Com_Cod: cpp_data.Com_Cod,
                flag: "s",
                Com_Codigo: cpp_data.Com_Codigo,
                Caj_Fec: cpp_data.Caj_Fec,
                Cpc_Ven: cpp_data.Cpc_Ven,
                Vet_Num: cpp_data.Vet_Num,
                total: cpp_data.Asi_Val,
                Abono: responce['data'][i].Abono,
                Saldo: saldo_mod
            }, "last");
        }
    }, function (err) {
        $.alert(err['message']);
    });
}

function modificarAbono(row) {
    console.log("-----------------");
    console.log(row);
    let valr_pagar = 0.00;
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
    $("#agg_Cli_Cod").val($("#Cli_Cod").val());
    $("#agg_Prs_Ced").val($("#Prs_Ced").val());
    $("#agg_Prs_Dir").val($("#Prs_Dir").val());
    $("#saldo_info1").text("" + parseFloat(row[0].Com_Val).toFixed(2));
    $("#Pec_Cod option[value=" + row[0].Pec_Cod + "]").prop("selected", true);
    $("#Pec_Cod").trigger("onchange");

    /*addPagoIni();

    $.getDataJson('', { getPagsAbono: true, Com_Cod: row[0].Com_Cod, Cli_Cod: $("#agg_Cli_Cod").val() }, function (responce) {
        //agregar pagos correspondientes a este pago de la o las facturas
        for (let i = 0; i < responce['data'].length; i++) {
            let par_array = [
                responce['data'][i].Asi_Deh,
                "pago",
                responce['data'][i].Che_Cod,
                responce['data'][i].Asi_Cod,
                responce['data'][i].Pag_Cod,
                responce['data'][i].Pag_Abr,
                responce['data'][i].Pag_Des,
                responce['data'][i].Bak_Cod,
                responce['data'][i].Che_Num,
                responce['data'][i].Che_Fec,
                responce['data'][i].Che_Cta,
                "",
                responce['data'][i].Pld_Cod,
                responce['data'][i].Pld_Cdc,
                responce['data'][i].Pld_Des,
                responce['data'][i].Asi_Con,
                responce['data'][i].Asi_Glo,
                responce['data'][i].Asi_Val,
                "",
                responce['data'][i].Che_Est,
                "last",
                responce['data'][i].Che_Cli
            ];
            addPago(par_array);
            incrementarSaldoInfo();
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
    }, function (err) {
        $.alert(err['message']);
    });*/

     $.getDataJson('', { getAsientosAbono: true, Com_Cod: row[0].Com_Cod }, function (respAsi) {
        if (respAsi && respAsi.success && respAsi.data && respAsi.data.length) {
            var asientoH = null;
            for (let k = 0; k < respAsi.data.length; k++) {
                if (respAsi.data[k].Asi_Deh === 'H') {
                    asientoH = respAsi.data[k];
                    break;
                }
            }
            if (asientoH) {
                addPago([
                    "H", "inicial", "", "",
                    "", "", "", "", "", "", "", "",
                    asientoH.Pld_Cod, asientoH.Pld_Cdc, asientoH.Pld_Des,
                    $("#Com_Con").val(), $("#Com_Con").val(),
                    "", $("#Com_Val").val(), "", "first"
                ]);
            }
        }

        $.getDataJson('', { getPagsAbono: true, Com_Cod: row[0].Com_Cod, Cli_Cod: $("#agg_Cli_Cod").val() }, function (responce) {
            //agregar pagos correspondientes a este pago de la o las facturas
            for (let i = 0; i < responce['data'].length; i++) {
                let par_array = [
                    responce['data'][i].Asi_Deh,
                    "pago",
                    responce['data'][i].Che_Cod,
                    responce['data'][i].Asi_Cod,
                    responce['data'][i].Pag_Cod,
                    responce['data'][i].Pag_Abr,
                    responce['data'][i].Pag_Des,
                    responce['data'][i].Bak_Cod,
                    responce['data'][i].Che_Num,
                    responce['data'][i].Che_Fec,
                    responce['data'][i].Che_Cta,
                    "",
                    responce['data'][i].Pld_Cod,
                    responce['data'][i].Pld_Cdc,
                    responce['data'][i].Pld_Des,
                    responce['data'][i].Asi_Con,
                    responce['data'][i].Asi_Glo,
                    responce['data'][i].Asi_Val,
                    "",
                    responce['data'][i].Che_Est,
                    "last",
                    responce['data'][i].Che_Cli
                ];
                addPago(par_array);
                incrementarSaldoInfo();
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
        }, function (err) {
            $.alert(err['message']);
        });
    }, function (err) {
        // si falla la carga de asientos, igual intentamos cargar los pagos
        $.getDataJson('', { getPagsAbono: true, Com_Cod: row[0].Com_Cod, Cli_Cod: $("#agg_Cli_Cod").val() }, function () { }, function () { });
        if (err && err.message) $.alert(err.message);
    });


    moveToAggCcpp();
    verFacturasAbono(row[0].Com_Cod);
    $("#Tia_Cod option[data-abr='IN']").prop("selected", true);
}

function preProtestarCheque() {
    //
}

function preanularAbono(row) {
    $.createDialogConfirm('?Est&aacute; seguro que desea anular este Abono?', row, anularAbono);
}

function anularAbono(row) {

    $.saveDataJson("", { delAbono: true, Com_Cod: row[0].Com_Cod, fila: row[0] }, function (responce) {
        $("#searchGrid_mod").trigger("reloadGrid");
        $.alert("Abono anulado con &eacute;xito!");
        return false;
    }, function (responce) { $.alert(responce['message']); },
        function (responce) { $.alert(responce['message']); });
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
        $("#sel_ven").append('<option value="4">Por vencer a 90 d?s >></option>');
        $("#sel_ven").append('<option value="3">Por vencer a 60 d?s >></option>');
        $("#sel_ven").append('<option value="2">Por vencer a 30 d?s >></option>');
    }
}

//en caso de escoger una factura
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

function verAbono(row) {
    $("#showPagosAsi").jqGrid("clearGridData").trigger("reloadGrid");
    $("#showPagosChe").jqGrid("clearGridData").trigger("reloadGrid");

    $("#ant_detasi").children("a").trigger("click");
    $('#impCanc').attr('href', "./tes_pri_recibocobro_1.1.php?Com_Cod=" + row[0].Com_Cod);

    $("#cli_show").val($("#nombre").val());
    $("#ruc_show").val($("#Prs_Ced").val());
    $("#compr_show").val(row[0].Com_Codigo);
    $("#fec_show").val(row[0].Com_Fec);
    $("#obs_show").val(row[0].Com_Obs);
    $("#Com_Cod_view").val(row[0].Com_Cod);

    $('#verPagosDialogMod').dialog('open');
    $('div#verPagosDialog').siblings('.ui-dialog-titlebar').children('span').text($("#nombre").val());

    $.getDataJson('', { getAsientosAbono: true, Com_Cod: row[0].Com_Cod }, function (responce) {
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
            if (responce['data_che'][i].Che_Est === "A") estado_che = "Activo";
            if (responce['data_che'][i].Che_Est === "I") estado_che = "Inactivo";
            if (responce['data_che'][i].Che_Est === "C") estado_che = "Cobrado";
            $('#showPagosChe').jqGrid('addRowData', ids_pg, {
                index: ids_pg,
                Che_Num: responce['data_che'][i].Che_Num,
                Che_Fec: responce['data_che'][i].Che_Fec,
                Che_Val: responce['data_che'][i].Che_Val,
                Che_Obs: responce['data_che'][i].Che_Obs,
                Bak_Des: responce['data_che'][i].Bak_Des,
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
    }, function (err) {
        $.alert(err['message']);
    });

    //hacemos foco sobre el numero de comprobante
    document.getElementById("compr_show").focus();
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
        $("#txt_fec_fin").val($("#sel_per option:selected").attr("data-fin"));
    } else {
        $("#txt_fec_fin").dateLimits($("#txt_fec_ini").val(), $("#sel_per option:selected").attr("data-fin"));
    }
}

function moveToList() {
    $("#agregar_cccc").moveComp("#listar_cccc").updateGridsSizes();
}

function moveToAggCcpp() {
    $("#listar_cccc").moveComp("#agregar_cccc").updateGridsSizes();
}






function gestionarPago() {
    $('#Pag_Cod').trigger("change");
    $("#tip_trans").val("add");

    if ($("#Cli_Cod").val() === "") { return $.alert("Seleccione un cliente!"); }
    if (actualizarTotalesSG() === 0) { return $.alert("Indique al menos un valor a pagar!"); }

    $("#Pec_Cod").trigger("onchange");
    createPagosGrid();

    let ids = $('#searchGrid').jqGrid('getDataIDs'), str_con = "ABONO FACTS. ";
    let val_pagar = 0;
    let Pld_Cdc = null;
    for (let i = 0; i < ids.length; i++) {
        if ($("#sg_act_" + ids[i]).prop('checked')) {
            let reg_pago = $('#searchGrid').jqGrid('getRowData', ids[i]);
            str_con += "/" + reg_pago.Vet_Num;
            val_pagar += parseFloat($('#sg_pago_' + ids[i]).val());
            Pld_Cdc = reg_pago.Pld_Cdc;
            // console.log(reg_pago.Pld_Cdc);
        }
    }
    //Pld_Cdc
    $("#Che_Ben_N").val($("#nombre").val());
    $("#Com_Val").val(parseFloat(val_pagar).toFixed(2));
    $("#Com_Val_pago").val(parseFloat(val_pagar).toFixed(2));
    $("#Com_Con").val(str_con);
    $("#Com_Obs").val(str_con);
    $("#agg_nombre").val($("#nombre").val());
    $("#agg_Cli_Cod").val($("#Cli_Cod").val());
    $("#agg_Prs_Ced").val($("#Prs_Ced").val());
    $("#agg_Prs_Dir").val($("#Prs_Dir").val());
    $("#saldo_info1").text(parseFloat(val_pagar).toFixed(2));
    $("#saldo_info2").text("0.00");
    addPagoIni(Pld_Cdc);
    moveToAggCcpp();
    $("#Tia_Cod option[data-abr='IN']").prop("selected", true);

    $.post("", { enableDisableCampos: true, tipo: "ANT", Cli_Cod: $("#agg_Cli_Cod").val() }, function (responce) {

        if (responce['success'] === true) {
            $("#ant_msg").html(responce['data_ant'] === 'none' || !(responce['data_ant'] > 0) ? "$ 0.00" : $.numFormat(responce['data_ant']));
            $("#ant_msg")[responce['data_ant'] === 'none' || !(responce['data_ant'] > 0) ? 'removeClass' : 'addClass']('alert alert-danger bold');
            $("#detal").html(responce['deta'] === 'none' ? "" : responce['deta']);
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


function addPagoIni(Pld_Cdc) {
    //  console.log("Pld_Cod= "+Pld_Cdc);

    $.getDataJson('', { getPagoIniAjax: true, Pld_Cdc: Pld_Cdc }, function (responce) {


        let prm_array = [
            "H", "inicial", "", "", "", "", "", "", "", "", "", "",
            responce['data'].Pld_Cod, responce['data'].Pld_Cdc, responce['data'].Pld_Des, $("#Com_Con").val(),
            $("#Com_Con").val(), "", $("#Com_Val").val(), "", "first"
        ];
        addPago(prm_array);
    }, function (err) {
        $.alert(err['message']);
    });
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
    let prm_array, tp_pg = $("#Pag_Cod option:selected").attr("data-abr"),
        pg_des = $.trim($("#Pag_Cod option:selected").text()),
        pg_cod = $("#Pag_Cod").val(),
        con_tx = $("#Com_Con").val(),
        val_agg = $("#Com_Val_pago").val(),
        bn_cue = $("#Ban_Cod option:selected").attr("data-cue"),
        bn_cdc = $("#Ban_Cod option:selected").attr("data-cdc"),
        bn_des = $("#Ban_Cod option:selected").attr("data-des"),
        bn_pld = $("#Ban_Cod option:selected").attr("data-pla"),
    bk_pld = $("#Bak_Cod option:selected").attr("data-pla"), bk_cdc = $("#Bak_Cod option:selected").attr("data-cdc"), bk_des = $("#Bak_Cod option:selected").attr("data-des"), bk_cod = $("#Bak_Cod option:selected").attr("value");
    
    if ($("#pagosGrid td:contains('EFE')").text().search("EFE") == -1 && tp_pg === "EFE") {
        prm_array = ["D", "pago", "", "", pg_cod, tp_pg, pg_des, "", "", "", "", "", bn_pld, bn_cdc, bn_des, con_tx, con_tx, val_agg, "", "", "last"];
        addPago(prm_array);
    } else if ($("#pagosGrid td:contains('CPL')").text().search("CPL") == -1 && tp_pg === "CPL") {
        prm_array = ["D", "pago", "", "", pg_cod, tp_pg, pg_des, "", "", "", "", "", bn_pld, bn_cdc, bn_des, con_tx, con_tx, val_agg, "", "", "last"];
        addPago(prm_array);
    } else if (tp_pg === "OTR" || tp_pg === "NDC") {
        prm_array = ["D", "pago", "", "", pg_cod, tp_pg, pg_des, "", "", "", "", "", bn_pld, bn_cdc, bn_des, con_tx, con_tx, val_agg, "", "", "last"];
        addPago(prm_array);

    } else if ($("td[aria-describedby='pagosGrid_Pag_Des']").text().search("Anticipos") == -1 && tp_pg === "ANT") {
        if ($("#lim_val_pago").val() === "none") {
            $.alert("No tiene anticipos disponibles de este cliente!");
        } else {
            open_anticipos();
            prm_array = ["D", "pago", "", "", pg_cod, tp_pg, pg_des, "", "", "", "", "", bn_pld, bn_cdc, bn_des, con_tx, con_tx, val_agg, "", "", "last"];
            addPago(prm_array);
        }

    } else if ($("#pagosGrid td:contains('CDC')").text().search("CDC") == -1 && tp_pg === "CDC") {

        if ($("#lim_val_pago_cc").val() === "none") {
            $.alert("No tiene una cantidad sificiente para el cruce de cuentas con este cliente!");
        } else {
            let fact = $.jsonParser($.map($('#crucesGrid').getGridBatch(o=>o.cruce>0),o=>[{Cpp_Cod:o.Cpp_Cod,cruce:o.cruce,Cop_Num:o.Cop_Num}]));
            $("#Pag_Cod option[data-abr='CDC']").attr("disabled", true);
            prm_array = ["D", "pago", "", "", pg_cod, tp_pg, pg_des, "", "", "", "", "", bn_pld, bn_cdc, bn_des, con_tx, con_tx, val_agg, "", "", "last","",fact];
            $("#Com_Val_pago").attr("readonly",false);
            $("#Pag_Cod").val(1).trigger('change');
            addPago(prm_array);
        }
    } else if ($("#pagosGrid td:contains('DEP')").text().search("DEP") == -1 && tp_pg === "DEP") {
        prm_array = ["D", "pago", "", "", pg_cod, tp_pg, pg_des, "", "", "", "", bn_cue, bn_pld, bn_cdc, bn_des, con_tx, con_tx, val_agg, "", "", "last"];
        addPago(prm_array);
    } else if (tp_pg === "TRF") {
        // $("#Num_Doc").prop("disabled", false);
        prm_array = ["D", "pago", "", "", pg_cod, tp_pg, pg_des, "", "", "", $("#Che_Cta").val(), bn_cue, bn_pld, bn_cdc, bn_des, con_tx, con_tx, val_agg, "", "", "last"];
        addPago(prm_array);
    } else if (tp_pg === "TDC") {
        prm_array = ["D", "pago", "", "", pg_cod, tp_pg, pg_des, "", "", "", "", bn_cue, bn_pld, bn_cdc, bn_des, con_tx, con_tx, val_agg, "", "", "last"];
        addPago(prm_array);

    } else if (tp_pg === "CHE") {
        console.log("ESTO ES CHEQUE::::: "+tp_pg);
        let bandera_cheque_pago = false;
        criterio = $('#pagosGrid').jqGrid("getCol", "Che_Num", false);
        if(!$.isEmpty(criterio.Che_Num)){
            for (let i = 0; i < criterio.length; i++) {
                if ($("#Che_Num").val() == criterio[i]) {
                    bandera_cheque_pago = true;
                }
            }
        }
         console.log("-----"+ bandera_cheque_pago);
        if (numeroCheque === false) {
            if (bandera_cheque_pago === false) {
                prm_array = ["D", "pago", "", "", pg_cod, tp_pg, pg_des, bk_cod, $("#Che_Num").val(), $("#Che_Fec").val(), $("#Che_Cta").val(), bn_cue, bk_pld, bk_cdc, bk_des, "CHEQUE NO. " + $("#Che_Num").val(), "CHEQUE NO. " + $("#Che_Num").val(), val_agg, "", "", "last", $("#Che_Ben_N").val()];
                console.log("prm_array"+ prm_array);
                addPago(prm_array);
            }
        } else if ($("#pagosGrid td:contains('DEP')").text().search("DEP") == -1 && tp_pg === "DEP") {
            prm_array = ["D", "pago", "", "", pg_cod, tp_pg, pg_des, "", "", "", "", bn_cue, bn_pld, bn_cdc, bn_des, con_tx, con_tx, val_agg, "", "", "last"];
            addPago(prm_array);
        } else if (tp_pg === "TRF") {
            // $("#Num_Doc").prop("disabled", false);
            prm_array = ["D", "pago", "", "", pg_cod, tp_pg, pg_des, "", "", "", $("#Che_Cta").val(), bn_cue, bn_pld, bn_cdc, bn_des, con_tx, con_tx, val_agg, "", "", "last"];
            addPago(prm_array);
        } else if (tp_pg === "TDC") {
            prm_array = ["D", "pago", "", "", pg_cod, tp_pg, pg_des, "", "", "", "", bn_cue, bn_pld, bn_cdc, bn_des, con_tx, con_tx, val_agg, "", "", "last"];
            addPago(prm_array);
        } else if (tp_pg === "CHE") {
            let bandera_cheque_pago = false;
            criterio = $('#pagosGrid').jqGrid("getCol", "Che_Num", false);
            if(!$.isEmpty(criterio.Che_Num)){
                for (let i = 0; i < criterio.length; i++) {
                    if ($("#Che_Num").val() == criterio[i]) {
                        bandera_cheque_pago = true; 
                    }
                }
            }
            console.log(":::::::::::::::::::::::::::::::-----------------");
            if (numeroCheque === false) {
                if (bandera_cheque_pago === false) {
                    console.log("Verificar x que no ingresa");
                    prm_array = ["D", "pago", "", "", pg_cod, tp_pg, pg_des, bk_cod, $("#Che_Num").val(), $("#Che_Fec").val(), $("#Che_Cta").val(), bn_cue, bk_pld, bk_cdc, bk_des, "CHEQUE NO. " + $("#Che_Num").val(), "CHEQUE NO. " + $("#Che_Num").val(), val_agg, "", "", "last", $("#Che_Ben_N").val()];
                    addPago(prm_array);
                } else {
                    $.alert("No puede ingresar dos pagos con el mismo n&uacute;mero de cheque");
                }
            } else {
                $.alert("El numero de cheque (" + $("#Che_Num").val() + ") ya fue emitido");
            }
        }

        console.log("Ninguno");
    }
}
function addPago(prm_array) {
    // console.log(prm_array);
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
        Bak_Cod: prm_array[7],
        Che_Num: prm_array[8],
        Che_Cli: prm_array[21],
        Che_Fec: prm_array[9],
        Pac_Cto: prm_array[10],
        Pac_Ctd: prm_array[11],
        Pld_Cod: prm_array[12],
        Pld_Cdc: prm_array[13],
        Pld_Des: prm_array[14],
        concepto: prm_array[15],
        Glosa: prm_array[16],
        Debe: prm_array[17],
        Haber: prm_array[18],
        factu: prm_array[22],
        Pag_Item: "",
        Che_Est: prm_array[19]
    }, "" + prm_array[20]);
    // $("#1_Debe").trigger("onChange");
    $('#pagosGrid').startGridEdit();
    $('#pagosGrid').updateGridDiario();
    totalPagos();
    disableDebe();
    ActualizarSaldoInfo();
    //colocamos el valor que falte agregar en el campo de valor a agregar
    $("#Com_Val_pago").val((parseFloat($("#Com_Val").val()) - parseFloat(totalPagos().debe)).toFixed(2));
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
        saldos = saldos + parseFloat(reg_pago.Saldo);  // Sumar Saldo

        // Obtener el valor de "A Cobrar"
        let pago = parseFloat($('#sg_pago_' + ids[i]).val());
        // Validar que el pago no exceda el saldo
        if (pago > parseFloat(reg_pago.Saldo)) {
            $.alert("El valor exede el Saldo disponible" + "<br>Por favor, verifique el ingreso");
            $('#sg_pago_' + ids[i]).val(reg_pago.Saldo);  // Restablecer a el mismo valor d la columna Saldo si excede
            pago = 0.00;  // Restablecer valor de pago a 0
        }
        // Sumar el valor validado de pago
        total_pago += pago;
        //Seccion original funcional sin validacion de saldo
        //     if (typeof $('#sg_pago_' + ids[i]).val() !== 'undefined') {
        //         saldos = saldos + parseFloat(reg_pago.Saldo);
        //         total_pago += parseFloat($('#sg_pago_' + ids[i]).val());
        //     }
    }


    $('#searchGrid').jqGrid('footerData', 'set', { vencimiento: "TOTALES:", Asi_Val: $('#searchGrid').jqGrid('getCol', 'Asi_Val', false, 'sum') });
    $('#searchGrid').jqGrid('footerData', 'set', { Asi_Val: "" + tot });
    $('#searchGrid').jqGrid('footerData', 'set', { Abono: "" + abonos });
    $('#searchGrid').jqGrid('footerData', 'set', { Saldo: "" + saldos });
    $('#searchGrid').jqGrid("footerData", "set", { cliente_n: "<div style='text-align:right;'>TOTAL A COBRAR:</div>", Pago: "" + total_pago });

    $("#sg_pago_undefined").parent().append("$ " + parseFloat(total_pago).toFixed(2));
    $("#sg_pago_undefined").remove();

    return total_pago;
}

function actualizarTotalesSGmod() {
    //obtener todos los ids para buscar valores de debe y haber
    let ids = $('#searchGrid_mod').jqGrid('getDataIDs');

    let abonos = 0,
        saldos = 0,
        total_pago = 0.00,
        tot = 0;
    for (let i = 0; i < ids.length; i++) {
        let reg_pago = $('#searchGrid_mod').jqGrid('getRowData', ids[i]);

        tot = tot + parseFloat(reg_pago.Asi_Val);
        abonos = abonos + parseFloat(reg_pago.Abono);
        if ($.isNumeric(reg_pago.Saldo)) {
            saldos = saldos + parseFloat(reg_pago.Saldo);
        }
    }

    $('#searchGrid_mod').jqGrid('footerData', 'set', { vencimiento: "TOTALES:", Asi_Val: $('#searchGrid_mod').jqGrid('getCol', 'Asi_Val', false, 'sum') });
    $('#searchGrid_mod').jqGrid('footerData', 'set', { Asi_Val: "" + tot });
    $('#searchGrid_mod').jqGrid('footerData', 'set', { Abono: "" + abonos });
    $('#searchGrid_mod').jqGrid('footerData', 'set', { Saldo: "" + saldos });
}

function preGuardarPago() {
    let tots = totalPagos();
    if (parseFloat(tots.debe).toFixed(2) !== parseFloat(tots.haber).toFixed(2)) { return $.alert("Los totales no coinciden"); }
    if (parseFloat(tots.debe).toFixed(2) !== parseFloat($("#Com_Val").val()).toFixed(2)) { return $.alert("El valor total de pagos debe ser de " + $("#Com_Val").val()); }

    let data = $('#formPagos').serializeObject();
    let ids = $('#searchGrid').jqGrid('getDataIDs');
    let obj_ccpp = new Object();
    let contador_fcs = 0;
    for (let i = 0; i < ids.length; i++) {
        if ($("#sg_act_" + ids[i]).prop('checked')) {
            let reg_pago = $('#searchGrid').jqGrid('getRowData', ids[i]);
            obj_ccpp[contador_fcs] = { Cpc_Cod: reg_pago.Cpc_Cod, Cpc_Val: $('#sg_pago_' + ids[i]).val() };
            contador_fcs++;
        }
    }
    data["save_cp"] = obj_ccpp;

    let batch = $('#pagosGrid').getGridBatch();
    let obj_sp_mod = new Object();
    let contador_sp = 0;
    for (let j = 0; j < batch.length; j++) {
        delete batch[j]["Pag_Item"];
        obj_sp_mod[contador_sp] = batch[j];
        contador_sp++;
    }
    data["save_p"] = obj_sp_mod;
    data["savePago"] = true;

    data["save_p"] = $('#pagosGrid').getGridBatch();
    data["save_pago_anticipos"] = $('#Lista_Anticipos').getGridBatch();

    data["valor_tota_pago"] = parseFloat($('td[aria-describedby="Lista_Anticipos_saldo_pagar"].columnDisabled').text().replace(/[^\d.-]/g, ''));

    for (let i = 0; i < data["save_pago_anticipos"].length; i++) {
        let item = data["save_pago_anticipos"][i];
        let saldo_pagar = $('#sg_pago_' + item.Ant_Cod).val();

        data["save_pago_anticipos"][i].saldo_pagar = saldo_pagar
    }

    console.log(data);
    $.createDialogConfirm('?Est&aacute; seguro que desea guardar los datos?', data, guardarPago);
}

function guardarPago(data) {
    
    $.saveDataJson("", data, function (responce) {
        $('#impCompr').attr('href', responce['link']);
        $('#impComprCanc').attr('href', responce['link2']);
        $('#successDialog').dialog('open');
        limpiarPagos();
        moveToList();
        $("#Pag_Cod option[data-abr='CDC']").attr("disabled", false);
        $("#searchGrid").trigger("reloadGrid");
        return false;
    }, function (responce) {
        $('#pagosGrid').startGridEdit();
    }, function (responce) {
        $('#pagosGrid').startGridEdit();
    });
}

function preModificarPago() {
    let tots = totalPagos();
    if (parseFloat(tots.debe).toFixed(2) !== parseFloat(tots.haber).toFixed(2)) { return $.alert("Los totales no coinciden"); }
    let v_max = recTotalPagar();
    if (parseFloat(tots.debe).toFixed(2) !== parseFloat($("#Com_Val").val()).toFixed(2)) {
        if (parseFloat($("#Com_Val").val()) > parseFloat($("#Com_Val_temp").val())) {
            return $.alert("El valor total de pagos no puede exeder los " + v_max);
        } else {
            return $.alert("El valor total de pagos debe ser de " + $("#Com_Val").val());
        }
    }

    $.createDialogConfirm('?Est&aacute; seguro que desea guardar los datos?', null, mmodificarPago)
}

function mmodificarPago() {
    let data = $('#formPagos').serializeObject();

    let obj_ccpp_mod = new Object();
    let contador_fcsm = 0;
    let idsfc = $('#factsGrid').jqGrid('getDataIDs');
    for (let i = 0; i < idsfc.length; i++) {
        let reg_pagofc = $('#factsGrid').jqGrid('getRowData', idsfc[i]);
        if (reg_pagofc.flag === 's') {
            obj_ccpp_mod[contador_fcsm] = { Cpc_Cod: reg_pagofc.Cpc_Cod, Cpc_Val: reg_pagofc.Abono, total: reg_pagofc.total, Saldo: reg_pagofc.Saldo };
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

    $.saveDataJson("", data, function (responce) {
        $('#impCompr').attr('href', responce['link']);
        $('#impComprCanc').attr('href', responce['link2']);
        $('#successDialog').dialog('open');
        limpiarPagos();
        moveToList();
        $("#searchGrid_mod").trigger("reloadGrid");
        return false;
    }, function (responce) {
        $('#pagosGrid').startGridEdit();
    }, function (responce) {
        $('#pagosGrid').startGridEdit();
    });
}

function totalPagos() {
    let total = parseFloat($("#Atp_Val").val());
    total += parseFloat($("#Pap_Val").val());
    $('#pagosGrid').startGridEdit();
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

function disableDebe() {
    var ids_p = $('#pagosGrid').jqGrid('getDataIDs');
    for (let i = 0; i < ids_p.length; i++) {
        let reg_pago = $('#pagosGrid').jqGrid('getRowData', ids_p[i]);
        if (reg_pago.Pag_Abr === "CHE" && reg_pago.Che_Est === "P") {
            $("#" + ids_p[i] + "_Debe").attr("readonly", "");
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

function enableDisableCampos() {
    $("#Com_Val_pago").attr("readonly",false);
    $(".ed_element").attr("disabled", "");
    $("#Che_Num").val("");
    $('#Che_Num').trigger('onkeyup');
    $("#cont_anticipo_info").attr("hidden", "");    
    $("#cont_ccc_info").attr("hidden", "");
    let tp_pg = $("#Pag_Cod option:selected").attr("data-abr");
    $(".ed_" + tp_pg).removeAttr("disabled");

    if (tp_pg !== 'CHE') {
        $("#indicadorChe").attr("class", "");
        $("#Ban_Cod").removeAttr("disabled");
    }
    if (tp_pg === 'CHE') {
        //$("#Ban_Cod").attr("disabled", "");
    }

    if (tp_pg === 'TRF' || tp_pg === 'DEP') {
        $("#Num_Doc").prop("disabled", false);
    } else {
        $("#Num_Doc").attr("disabled", "");
    }

    // if (tp_pg === 'DEP') {
    //     $("#Num_Doc").prop("disabled", false);
    // } else{
    //     $("#Num_Doc").attr("disabled", "");
    // }

    $.getDataJson('', { enableDisableCampos: true, tipo: tp_pg, Cli_Cod: $("#agg_Cli_Cod").val() }, function (result) {
        $("#Ban_Cod option").remove();
        for (i = 0; i < result['data'].length; i++) {
            if (tp_pg == "EFE" || tp_pg === "OTR" || tp_pg === "NDC" || tp_pg === "CPL") {
                $("#Ban_Cod").append("<option value='" + result['data'][i].Ban_Cod + "' data-pla='" + result['data'][i].Pld_Cod + "' data-cdc='" + result['data'][i].Pld_Cdc + "' data-cue='" + result['data'][i].Ban_Cue + "' data-des='" + result['data'][i].Pld_Des + "'>" + result['data'][i].Pld_Des + "</option>");
            } else if (tp_pg === "TRF" || tp_pg === "TDC" || tp_pg == "DEP" || tp_pg === "CHE") {
                // $("#Num_Doc").prop("disabled", false);
                $("#Ban_Cod").append("<option value='" + result['data'][i].Ban_Cod + "' data-pla='" + result['data'][i].Pld_Cod + "' data-cdc='" + result['data'][i].Pld_Cdc + "' data-cue='" + result['data'][i].Ban_Cue + "' data-des='" + result['data'][i].Pld_Des + "'>" + result['data'][i].Pld_Des + " - " + result['data'][i].Ban_Cue + "</option>");
            } else if (tp_pg === "ANT" || tp_pg === "CDC") {
                $("#Ban_Cod").append("<option value='" + result['data'][i].Pld_Cod + "' data-pla='" + result['data'][i].Pld_Cod + "' data-cdc='" + result['data'][i].Pld_Cdc + "' data-des='" + result['data'][i].Pld_Des + "'>" + result['data'][i].Pld_Des + "</option>");
            }
        }
        //************************************************* */
        if (tp_pg === 'ANT') {
            $("#cont_anticipo_info").removeAttr("hidden");
            if (result['data_ant'] === 'none' || result['data_ant'] === 0) {
                $("#anticipo_info").text("0.00");
                $("#lim_val_pago").val("none");
            } else {
                $("#lim_val_pago").val("" + parseFloat(result['data_ant']).toFixed(2));
                $("#anticipo_info").text("" + parseFloat(result['data_ant']).toFixed(2));
                $("#Com_Val_pago").val("" + parseFloat(result['data_ant']).toFixed(2));
            }
        } else if (tp_pg === 'CDC') {
            $("#cont_ccc_info").removeAttr("hidden");
            $("#Com_Val_pago").attr("readonly",true).val('0.00');
            if (result['data_cdc'] === 'none') {
                $("#ccc_info").text("0.00");
                $("#lim_val_pago_cc").val("none");
            } else {
                //$("#lim_val_pago_cc").val("" + parseFloat(result['data_cdc']).toFixed(2));
                $("#ccc_info").text("" + parseFloat(result['data_cdc']).toFixed(2));
                //$("#Com_Val_pago").val("" + parseFloat(result['data_cdc']).toFixed(2));
                $('#crucesGrid').setRows(result.rows);
            }
        } else {
            $("#Com_Val_pago").val((parseFloat($("#Com_Val").val()) - parseFloat(totalPagos().debe)).toFixed(2));
        }
        //************************************************* */
    }, function (err) {
        $.alert(err['message']);
    });
}

function limpiarPagos() {
    $("#pagosGrid").jqGrid("clearGridData").trigger("reloadGrid");
    $("#Che_Ben_N").val("");
    $("#Che_Cta").val("");
    $("#Com_Val").val("0.00");
    $("#Com_Val_pago").val("0.00");
    $("#Com_Con").val("");
    $("#Com_Obs").val("");

    $("#agg_nombre").val("");
    $("#agg_Cli_Cod").val("");
    $("#agg_Prs_Ced").val("");
    $("#agg_Prs_Dir").val("");

    $("#saldo_info1").text("0.00");
    $("#saldo_info2").text("0.00");

    $("#Che_Num").val("");
    $("#Num_Doc").val("");
    $('#Pag_Cod').prop('selectedIndex', 0);
    $('#Pag_Cod').trigger("onChange");
    $("#saldo_info").removeClass("txt-green");
    $("#saldo_info").removeClass("txt-red");
    $("#saldo_info").addClass("txt-red");

    $("#cont_anticipo_info").attr("hidden", "");
    $("#cont_ccc_info").attr("hidden", "");
    $("#Num_Doc").attr("disabled", "");

    $("#lim_val_pago").val("none");
    $("#lim_val_pago_cc").val("none");
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

function delPago(row) {
    $('#pagosGrid').jqGrid('delRowData', row.index);
    totalPagos();
    ActualizarSaldoInfo();
    $("#Com_Val_pago").val((parseFloat($("#Com_Val").val()) - parseFloat(totalPagos().debe)).toFixed(2));
    if(row.Pag_Des=='Cruce de Cuentas')
        $("#Pag_Cod option[data-abr='CDC']").attr("disabled", false);
}

function ActualizarSaldoInfo() {
    let saldo_info = 0.00;
    let tots = totalPagos();
    saldo_info = saldo_info + parseFloat(tots.debe);
    $("#saldo_info2").text(parseFloat(saldo_info).toFixed(2));
    if (parseFloat($("#saldo_info1").text()) === parseFloat($("#saldo_info2").text())) {
        $("#saldo_info").removeClass("txt-red");
        $("#saldo_info").addClass("txt-green");
    } else {
        $("#saldo_info").removeClass("txt-green");
        $("#saldo_info").addClass("txt-red");
    }
}

function recTotAPagar() {
    let ids = $('#factsGrid').jqGrid('getDataIDs');
    let tot_a_pagar = 0.00;
    let glo_pag = "ABONO FACTS. ";
    for (let i = 0; i < ids.length; i++) {
        let reg_pago = $('#factsGrid').jqGrid('getRowData', ids[i]);
        if ($("#fct_act_" + ids[i]).prop('checked')) {
            tot_a_pagar += parseFloat(reg_pago.Abono);
            glo_pag += "/" + reg_pago.Vet_Num;
        }
    }
    $("[id*='_Haber']").val('' + tot_a_pagar.toFixed(2));
    $("[id*='_Glosa']").val(glo_pag);

    $("#Com_Con").val(glo_pag);
    $("#Com_Obs").val(glo_pag);

    $("#Com_Val").val("" + tot_a_pagar.toFixed(2));

    $("#saldo_info1").text("" + tot_a_pagar.toFixed(2));

    if (parseFloat($("#saldo_info1").text()) === parseFloat($("#Com_Val").val())) {
        $("#saldo_info").removeClass("txt-red");
        $("#saldo_info").addClass("txt-green");
    } else {
        $("#saldo_info").removeClass("txt-green");
        $("#saldo_info").addClass("txt-red");
    }

    totalPagos();
    ActualizarSaldoInfo();
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
            let tots = totalPagos();

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

            if ($("#Com_Val_temp").length === 1) {
                let v_max = recTotalPagar();
                if (parseFloat(tots.debe) > parseFloat(v_max)) {
                    $("#PgabrEx").val(reg_pago.Pag_Abr);
                }
                if (reg_pago.Pag_Abr === "") {
                    let v_max = recTotalPagar();
                    if (parseFloat($(this).val()) > parseFloat(v_max)) {
                        $(this).val(parseFloat($(this).val()) - (parseFloat($(this).val()) - parseFloat(v_max)));
                        $("#saldo_info1").text(parseFloat($(this).val()).toFixed(2));
                    } else {
                        $("#Com_Val").val(parseFloat($(this).val()).toFixed(2));
                        $("#saldo_info1").text(parseFloat($(this).val()).toFixed(2));
                    }
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


//Nuevos metodos 
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
            var fecha_anticipo = $(this).find("td[aria-describedby='Lista_Anticipos_Ant_Fec']").text();//Capturar las fechas
            var cod_anticipo = $(this).find("td[aria-describedby='Lista_Anticipos_Ant_Cod']").text();//Capturar las fechas
            //console.log(fecha_anticipo);
            if ($fecha_pago_factura < fecha_anticipo) {
                msg_alerta_anticipos($fecha_pago_factura, cod_anticipo, fecha_anticipo);
                // $.alert(" La fecha de la factura a cancelar es: " + $fecha_pago_factura + " y el anticipos Nro: " + cod_anticipo + " esta con fecha: " + fecha_anticipo + ", por este motivo no se puede cancelar la factura con fecha anterior al anticipo.");
                return false;
            }

            $("#sg_act_" + codFila).prop('checked', true);
            $("#sg_pago_" + codFila).removeAttr("readonly");
            var saldoNumero = parseFloat(sal);
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
    var fecha_anticipo = ($('tr#' + row + ' td[aria-describedby="Lista_Anticipos_Ant_Fec"]').text());
    var cod_anticipo = ($('tr#' + row + ' td[aria-describedby="Lista_Anticipos_Ant_Cod"]').text());
    if ($fecha_pago_factura < fecha_anticipo) {
        msg_alerta_anticipos($fecha_pago_factura, cod_anticipo, fecha_anticipo);
        $("#sg_act_" + row).prop('checked', false);
        return false;
    } else {
        if ($("#sg_act_" + row).prop('checked')) {
            $("#sg_pago_" + row).removeAttr("readonly");
            var saldoPagar = parseFloat($('tr#' + row + ' td[aria-describedby="Lista_Anticipos_saldo_aux"]').text().replace(/[^\d.-]/g, '')).toFixed(2);
            $("#sg_pago_" + row).val(saldoPagar);
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


function open_anticipos() {
    load_anticipos();
    $('#Lista_Anticipos').Search('#formPagos_anticipo', 'loadAnticipos');
    $("#monto_total").val($("#Com_Val_pago").val());
    $('#agregar_anticipos').dialog({
        autoOpen: false, // No abrir automáticamente al crear
        modal: true, // Modal (fondo oscurecido y bloqueo de fondo)
        width: '65%', // Ancho automático según el contenido
        title: 'Pagar con Anticipos', // Título del modal
        buttons: {
            Siguiente: {
                text: "Siguiente",
                class: 'btn-siguiente', // Clase CSS para el botón
                click: function () {
                    var valor = $('td[aria-describedby="Lista_Anticipos_saldo_pagar"].columnDisabled').text();
                    var numero = parseFloat(valor.replace(/[^\d.-]/g, ''));
                    var monto_total = $("#monto_total").val();
                    //Validar fechas

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
            $(this).parent().find('.btn-siguiente').addClass('btn-primary'); // Ejemplo: btn-primary de Bootstrap
        }
    });
    $('#agregar_anticipos').dialog('open');
}



function load_anticipos() {
    $("#cli_cod_ant").val($("#Cli_Cod").val());
    // console.log($("#Cli_Cod").val());

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
            name: 'Ant_Cod',
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
            name: 'Ant_Fec',
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
            label: 'Nombre',
            name: 'nombre',
            width: 250,
            align: "left"
        },
        {
            label: 'Total',
            name: 'Ant_Val',
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
            width: 75,
            hidden: true,
            align: "center",
        },

        {
            label: 'Saldo aux',
            name: 'saldo_aux',
            width: 75,
            formatter: function (cellValue) {
                return parseFloat(cellValue).toFixed(2);
            },
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
                    return '<input id="sg_act_' + rowObject.Ant_Cod + '" type="checkbox" value="" onChange="setPagoCellAnticipo(\'' + rowObject.Ant_Cod + '\')" offval="no">';
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
                    return '<input id="sg_pago_' + rowObject.Ant_Cod + '" class="form-control input-xs" type="text" value="0.00" onChange="actualizarTotalesAnt();$(this).val(parseFloat($(this).val()).toFixed(2))" autocomplete="off" onkeypress="return  validar_decimal(event)" readonly>';
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
                Ant_Val: $('#Lista_Anticipos').jqGrid('getCol', 'Ant_Val', false, 'sum')
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

            var saldo_restante = (redondear(parseFloat(reg_pago.saldo_aux), 2) - auxiliar);
            console.log("Este valor resulta:" + parseFloat(reg_pago.saldo_aux) + "   " + auxiliar + "=" + saldo_restante);

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

    $('#Lista_Anticipos').jqGrid('footerData', 'set', { Ant_Val: $('#Lista_Anticipos').jqGrid('getCol', 'Ant_Val', false, 'sum') });
    $('#Lista_Anticipos').jqGrid('footerData', 'set', { Dac_Val: "" + tot });
    $('#Lista_Anticipos').jqGrid('footerData', 'set', { saldo: "" + abonos });
    $('#Lista_Anticipos').jqGrid("footerData", "set", { saldo_pagar: "" + total_pago });
    $("#sg_pago_undefined").parent().append("$ " + parseFloat(total_pago).toFixed(2));
    $("#sg_pago_undefined").remove();
    return total_pago;
}



