/* 
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */
if (!$.isset('alta')) var alta = false;
if (!$.isset('listSearch')) var listSearch = false;
if (!$.isset('baja')) var baja = false;
if (!$.isset('duplica')) var duplica = false;
if (!$.isset('anula')) var anula = false;
var Com_Cod = '';

$(function () {
    if ($('#tabsInsert').length === 1) {
        $("#tabsInsert").tabs(
            {
                activate: function (event, ui) {
                    tipo = ui.newTab[0].getElementsByTagName("a")[0].innerHTML;
                    $('#formCompConta').parent().effect("highlight", {}, 500);
                    $('.persona').hide();
                    $(tipo === 'Ingreso' ? '.cliente' : '.proveedor').show();
                    $('#title_comp').html(tipo);
                    resetForm();
                }
            });
    }
    if ($('#tabsSearch').length === 1)
        $("#tabsSearch").tabs({
            cache: true,
            beforeActivate: function (event, ui) { $("#loader").show(); },
            activate: function (event, ui) {
                listSearch.clearGrid();
                $("#loader").fadeOut("slow");
            }
        });

    /* listado de busquedas */
    if ($('#listsearch').length === 1) {
        listSearch = $("#listsearch");
        listSearch.createGrid($.extend({
            datatype: "local", height: 295, caption: 'Resultados <div class="pull-right"><b>ORDENAR POR:</b>&nbsp;<select id="OrderBy"><option value="">No ordenar</option><option value="Com_Fec DESC">Fecha Asiento</option><option value="Tia_Abr,MONTH(Com_Fec),Com_Num ">Num.Asiento</option><select>&nbsp;</div>',
            colModel: [
                { label: 'Cód.Int.', name: 'Com_Cod', key: true, width: 15, align: "center", hidden: false },
                { label: 'Gen.', name: 'Com_Gen', width: 10, align: "center", formatter: 'estado', formatoptions: { types: { A: 'Automatico', M: 'Manual' } }, title: false },
                { label: 'Comp.', name: 'Tipo', width: 20, align: "center", formatter: 'title', formatoptions: { title: function (o) { return '[' + o['Tipo'].toUpperCase() + '] - ' + o['Tia_Des']; } }, title: false },
                { label: 'Asiento', name: 'Codigo', width: 20, align: "center" },
                { label: 'Fecha Asien.', name: 'Com_Fec', width: 20, align: "center" },
                { label: 'Plan', name: 'Pld_Des', width: 20, align: "center", hidden: true },
                { label: 'Provee/Cliente', name: 'Nom_ClientProvee', width: 90 },
                { label: 'Cédula/RUC', name: 'Prs_Ced', width: 30, align: "center", formatter: 'title', formatoptions: { title: function (o) { return o['Prs_Ced'] + ' - ' + o['Persona']; } }, title: false },
                { label: 'Concepto', name: 'Com_Con', width: 80 },
                { label: 'Doc.', name: 'Doc', width: 20, align: "center" },
                { label: 'Fecha Docu.', name: 'Doc_Fec', width: 20, align: "center" },
                { label: 'Doc.Num.', name: 'Doc_Num', width: 40, align: "center" },
                { label: 'Valor', name: 'Com_Val', width: 25, align: "right", formatter: 'currency' },
                { label: 'Est.', name: 'Com_Est', width: 10, align: "center", formatter: 'estado' },
                { label: 'Responsable', name: 'responsable', width: 35, align: "right" },
                { label: '&nbsp;', name: 'act2', width: 15, align: 'center', formatter: 'gridButton', formatoptions: { action: ImpCom, title: 'Imprimir Comprobante', icon: 'print', type: 'info' }, title: false },
                { label: '&nbsp;', name: 'act1', width: 15, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: {
                        action: selectComp,
                        conditional: function (ro) {
                            if (alta) {
                                return (ro.Com_Est === 'I' || ro.Com_Est === 'B');
                            } else {
                                return (ro.Com_Est === 'A' || (duplica));
                            }
                        },
                        caseFalse: function () {
                            if (alta) {
                                return $.createIcon('ok green', null, 'title="Activo"');
                            } else {
                                return $.createIcon('remove red', null, 'title="Anulado/Inactivo"');
                            }
                        }
                    }
                }
            ]
        }, (anula || alta) ? {
            loadComplete: function (data) {
                if ($.varValid(data.rows))
                    for (var i = 0, z = data.rows.length; i < z; i++) {
                        if (data.rows[i]['Com_Est'] === 'I')
                            $("#listsearch #" + data.rows[i].Com_Cod + ' td:not(.jqgrid-rownum)').addClass('cellRed2');
                        if ((data.rows[i]['Com_Gen'] === 'A' || data.rows[i]['Doc'] !== '')) {
                            if (data.rows[i]['Com_Est'] === 'I') {
                                $("#listsearch #" + data.rows[i].Com_Cod + ' td[aria-describedby=listsearch_act1]').html('<i class="glyphicon glyphicon-remove red" />');
                            } else {
                                $("#listsearch #" + data.rows[i].Com_Cod + ' td[aria-describedby=listsearch_act1]').html('<i class="glyphicon glyphicon-lock yellow" />');
                            }
                        }
                    }
            }
        } : (!duplica) ? {
            loadComplete: function (data) {
                if ($.varValid(data.rows))
                    for (var i = 0, z = data.rows.length; i < z; i++) {
                        if (data.rows[i]['Com_Est'] === 'I') $("#listsearch #" + data.rows[i].Com_Cod + ' td:not(.jqgrid-rownum)').addClass('cellRed2');
                        if ((data.rows[i]['Com_Gen'] === 'A' || data.rows[i]['Com_Gen'] === 'B' || data.rows[i]['Doc'] !== '') && noEdit === true) $("#listsearch #" + data.rows[i].Com_Cod + ' td[aria-describedby=listsearch_act1] button').addClass('btn-warning').removeClass('btn-success');
                    }
            }
        } : {}), false, "#listsearchPager");
        $('#OrderBy').on('change', function () { $('input[name=order]').val($(this).val()); $('#tabs-' + ($("#tabsSearch").tabs("option", "active") + 1) + ' form').formSubmit(); });
    }
    if ($('#compAsien').length === 1) {
        gridCompAsien = $("#compAsien");
        gridCompAsien.createGrid({
            colModel: [
                { label: '&nbsp;', name: 'act0', width: 30, align: 'center',
                    formatter: function (cellvalue, options, rowObject) { return '<span class="btn btn-success btn-xs" title="Cambiar" onclick="$(\'#cuenDialog\').dialog(\'open\');$(\'#Index\').val(\'' + rowObject.Index + '\');"><i class="glyphicon glyphicon-check"></i></span>'; }, unformat: $.unformatCellHtml
                },
                { label: 'Cód.Int.', name: 'Index', key: true, width: 15, align: "center", hidden: true },
                { label: 'Cód.Int.', name: 'Pld_Cod', width: 20, align: "center", hidden: false },
                { label: 'Tipo', name: 'Det_Tip', hidden: true },
                { label: 'Codigo', name: 'Pld_Cdc', width: 45 },
                { label: 'Cuenta', name: 'Pld_Des', width: 150 },
                { label: 'Glosa', name: 'Glosa', width: 150, editable: true },
                { label: 'Debe', name: 'Debe', width: 50, align: 'right', formatter: 'currency', editable: true,
                    formatoptions: { defaultValue: '' },
                    editoptions: { dataInit: function (element) { gridCompAsien.createInputDiario(element, "D", "Det_Tip"); } }
                },
                { label: 'Haber', name: 'Haber', width: 50, align: 'right', formatter: 'currency', editable: true,
                    formatoptions: { defaultValue: '' },
                    editoptions: { dataInit: function (element) { gridCompAsien.createInputDiario(element, "H", "Det_Tip"); } }
                },
                { label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false, formatter: function (cv, opts, rObj) { return ($.getGridButton(deleteFilaCuenta, rObj.Index, 'Quitar', 'remove', null, 'danger')); }, unformat: $.unformatCellHtml }
            ], height: 'auto', caption: "Datos del Asiento Contable", footerrow: true, userDataOnFooter: false // set a footer row
        }, true, "#compAsienPager", { view: false }).gridButtonAdd({ caption: "Agregar Cuenta", buttonicon: "glyphicon glyphicon-plus", title: 'Agregar Cuenta', onClickButton: function () { $('#Index').val(''); $('#cuenDialog').dialog('open'); } });
        $.clearFooterDiario("#compAsien", true, '#Com_Val');
    }
    // DIALOG BUSCAR CUENTAS
    if ($('#cuenDialog').length === 1)
        $.createSearchDialog('cuenDialog', [
            { label: 'C&oacute;d.Int.', name: 'Pld_Cod', key: true, width: 15, align: "center", hidden: true },
            { label: 'Codigo', name: 'Pld_Cdc', width: 45 },
            { label: 'Cuenta', name: 'Pld_Des', width: 80, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; } },
            { label: 'Grupo', name: 'Pld_Grupo', width: 110, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; } },
            { label: 'Tipo', name: 'Pld_Tip', width: 30, align: "center" },
            { label: 'Estado', name: 'Pld_Est', width: 30, align: "center" },
            { label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 30, align: 'center', viewable: false, formatter: function (cv, opts, rObj) { return $.getGridButton(SelectCta, { Pld_Cod: rObj.Pld_Cod, tipo: 'D' }, 'Seleccione Cuentas', '', 'D') + '&nbsp;' + $.getGridButton(SelectCta, { Pld_Cod: rObj.Pld_Cod, tipo: 'H' }, 'Seleccione Cuentas', '', 'H'); } }
        ], null, null, null, null, { title: 'Cuenta', options: [{ label: '&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;', value: 'd' }, { label: '&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;', value: 'c' }] })
            .find('.form-group-options').append('<div class="col-md-4"> <label class="control-label label-xs">Plan de Cuentas:&nbsp;</label><input id="Index" name="Index" type="hidden" /><input name="Pec_Cod" type="hidden" /><input name="Periodo" type="text" size="6" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /></div>');
    // DIALOG BUSCAR CUENTAS 2   
    if ($('#cuen2Dialog').length === 1)
        $.createSearchDialog('cuen2Dialog', [
            { label: 'C&oacute;d.Int.', name: 'Pld_Cod', key: true, width: 15, align: "center", hidden: true },
            { label: 'Codigo', name: 'Pld_Cdc', width: 45 },
            { label: 'Cuenta', name: 'Pld_Des', width: 80, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; } },
            { label: 'Grupo', name: 'Pld_Grupo', width: 110, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; } },
            { label: 'Tipo', name: 'Pld_Tip', width: 30, align: "center" },
            { label: 'Estado', name: 'Pld_Est', width: 30, align: "center" },
            { label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 30, align: 'center', viewable: false, formatter: function (cellvalue, options, rowObject) { return $.getGridButton(SelectCta2, rowObject); } }
        ], null, null, null, null, { title: 'Cuenta', options: [{ label: '&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;', value: 'd' }, { label: '&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;', value: 'c' }] })
            .find('.form-group-options').append('<div class="col-md-4"> <label class="control-label label-xs">Plan de Cuentas:</label><input name="Periodo" type="text" size="6" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /> <input name="Pec_Cod" type="hidden" /><input name="Asi_Cod" type="hidden" /></div>');
    // DIALOG BUSCAR PROVEEDOR   
    if ($('#provDialog').length === 1)
        $.createSearchDialog('provDialog', [
            { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 20, align: "center", hidden: true, viewable: true },
            { label: 'C&eacute;dula/R.U.C.', name: 'Prs_Ced', width: 50 },
            { label: 'Proveedor', name: 'proveedor', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; } },
            { label: 'Apellidos', name: 'Prs_Ape', hidden: true },
            { label: 'Nombres', name: 'Prs_Nom', hidden: true },
            { label: 'Direcci&oacute;n', name: 'Prs_Dir', hidden: true, viewable: true },
            { label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center', viewable: false, formatter: function (cv, opts, rObj) { return $.getGridButton(selectProv, rObj, 'Seleccione Proveedor'); } }
        ], null, null, null, null, { title: 'Proveedor' });
    // DIALOG BUSCAR CLIENTE  
    if ($('#cliDialog').length === 1)
        $.createSearchDialog('cliDialog', [
            { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 20, align: "center", hidden: true, viewable: true },
            { label: 'C&eacute;dula/R.U.C.', name: 'Prs_Ced', width: 50 },
            { label: 'Cliente', name: 'cliente', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; } },
            { label: 'Direcci&oacute;n', name: 'Prs_Dir', hidden: true, viewable: true },
            { label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center', viewable: false, formatter: function (cv, opts, rObj) { return $.getGridButton(selectClie, { Cli_Cod: rObj.Cli_Cod, cliente: rObj.cliente }, 'Seleccione Cliente'); } }
        ], null, null, null, null, { title: 'Cliente' });
    // DIALOG BUSCAR PROVEEDORES  
    if ($('#persDialog').length === 1)
        $.createSearchDialog('persDialog', [
            { label: 'Cód.Int.', name: 'Prs_Cod', key: true, hidden: true },
            { label: 'Cód.Int.', name: 'Cli_Cod', hidden: true },
            { label: 'Cód.Int.', name: 'Prv_Cod', hidden: true },
            { label: 'Cédula/R.U.C.', name: 'Prs_Ced', width: 50 },
            { label: 'Proveedor', name: 'Persona', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; } },
            { label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 20, align: 'center', viewable: false, formatter: function (cellvalue, options, rowObject) { return $.getGridButton(selectPerss, rowObject); } }
        ], null, null, null, null, { title: 'Proveedor/Cliente' });
    // DIALOG success
    if ($('#successDialog').length === 1)
        $('#successDialog').createDialog({ height: 200, width: 350, icon: 'ok', buttons: [{ text: "Cerrar", click: function () { $(this).dialog("close"); }, icons: { primary: "ui-icon-closethick" } }] });
    // DIALOG detalle cheques
    if ($('#chequesDialog').length === 1)
        $('#chequesDialog').createDialogDetail({
            caption: 'Cheques Girados',
            cmTemplate: { sortable: false }, colModel: [
                { label: 'Cód.Int.', name: 'Che_Cod', key: true, hidden: true, viewable: true },
                { label: 'Fecha', name: 'Che_Fec', key: true, width: 50 },
                { label: 'Num.', name: 'Che_Num', key: true, width: 30, align: "center" },
                { label: 'Banco', name: 'Pld_Des', width: 100, title: 'Cuenta Bancaria' },
                { label: 'Beneficiario', name: 'Beneficiario', width: 150 },
                { label: 'Valor', name: 'Che_Val', key: true, width: 60, align: "right", formatter: 'currency', decimalPlaces: '2', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.' } }
            ]
        }, { icon: 'usd' });
    // DIALOG documentos compras/ventas
    if ($('#docuView').length === 1) {
        docuView = $("#docuView");
        docuView.createGrid({
            height: 155, caption: 'Detalle Documento', cmTemplate: { sortable: false, title: false },
            colModel: [
                { label: 'Cód.Int.', name: 'Index', key: true, width: 15, align: "center", hidden: true },
                { label: 'Cód.Int.', name: 'Doc_Int', width: 15, align: "center", hidden: true },
                { label: 'Cód.Int.', name: 'Pro_Cod', width: 15, align: "center", hidden: true },
                { label: 'Cant.', name: 'Doc_Can', width: 15, align: "center" },
                { label: 'Descripción', name: 'Ite_Lar', width: 150 },
                { label: 'Precio', name: 'Doc_Pru', width: 30, align: "right" },
                { label: 'Importe', name: 'Doc_Imp', width: 30, align: "right", formatter: 'currency' }
            ]
        }, true, "#docuViewPager", { view: false, refresh: false });
    }
    // comprobate no editable   
    if ($("#compNoEdit").length === 1) {
        compNoEdit = $("#compNoEdit");
        compNoEdit.createGrid({
            height: 135, caption: 'Asiento Contable', cmTemplate: { sortable: false, title: false },
            colModel: [
                {
                    label: '&nbsp;', name: 'act0', width: 30, align: 'center', hidden: (anula),
                    formatter: function (cellvalue, options, rowObject) { return '<span class="btn btn-success btn-xs" title="Cambiar" onclick="$(\'#cuen2Dialog\').dialog(\'open\');$(\'input[name=Asi_Cod]\').val(\'' + rowObject.Asi_Cod + '\');"><i class="glyphicon glyphicon-check"></i></span>'; }
                },
                { label: 'Cód.Int.', name: 'Asi_Cod', key: true, width: 15, align: "center", hidden: true },
                { label: 'Cód.Int.', name: 'Com_Cod', width: 15, align: "center", hidden: true },
                { label: 'Cód.Int.', name: 'Pld_Cod', width: 15, align: "center", hidden: true },
                { label: 'Tipo', name: 'Det_Tip', hidden: true },
                { label: 'Codigo', name: 'Pld_Cdc', width: 45 },
                { label: 'Cuenta', name: 'Pld_Des', width: 90 },
                { label: 'Glosa', name: 'Glosa', width: 90, editable: true },
                { label: 'Debe', name: 'Debe', width: 60, align: 'right', formatter: 'currency', formatoptions: { defaultValue: '' } },
                { label: 'Haber', name: 'Haber', width: 60, align: 'right', formatter: 'currency', formatoptions: { defaultValue: '' } },
                { label: '&nbsp;', name: 'act1', width: 30, align: 'center', hidden: true }
            ], footerrow: true, userDataOnFooter: false,
            loadComplete: function () { $(this).setGridSummary(['Debe', 'Haber'], { Glosa: '<div style="text-align:right;">Totales:</div>' }); }
        }, true, "#compNoEditPager", { view: false, refresh: false }).gridButtonAdd({ caption: "Cheques", id: 'btn_agr', buttonicon: "fa fa-money", title: 'Ver Cheques', onClickButton: function () { $('#chequesDialog').dialog('open'); } });
        $.clearFooterDiario("#compNoEdit");
    }
    // otros componentes
    if ($('#Month').length === 1) {
        $("#Month").createPeriodPicker(pec_min, pec_max, 'Periodo', updateNumCom);
        updateNumCom();
    }
    if ($('#Asi_Ini').length === 1) $.createDateRange('#Asi_Ini', '#Asi_Fin');
    if ($('#Cop_Ini').length === 1) {
        $.createDateRange('#Cop_Ini', '#Cop_Fin');
        $('#Cop_Ini').prop('disabled', true); $('#Cop_Fin').prop('disabled', true);
        $('#chk_sr').click(function () {
            if (!$('#chk_sr').is(':checked')) { $('#Cop_Ini').prop('disabled', true); $('#Cop_Fin').prop('disabled', true); }
            else { $('#Cop_Ini').prop('disabled', false); $('#Cop_Fin').prop('disabled', false); }
        });
    }
    if ($('#Ven_Ini').length === 1) {
        $.createDateRange('#Ven_Ini', '#Ven_Fin');
        $('#Ven_Ini').prop('disabled', true); $('#Ven_Fin').prop('disabled', true);
        $('#chk_sr1').click(function () {
            if (!$('#chk_sr1').is(':checked')) { $('#Ven_Ini').prop('disabled', true); $('#Ven_Fin').prop('disabled', true); }
            else { $('#Ven_Ini').prop('disabled', false); $('#Ven_Fin').prop('disabled', false); }
        });
    }
    if ($('#Com_Fec').length === 1) $("#Com_Fec").createDatePickers({ checkAvailability: true });
    if ($('#Com_Com_Fec').length === 1) $('#Com_Com_Fec').createDatePickers({ checkAvailability: true });
    if ($('#edit-panel').length === 1) $("#edit-panel").hide();
    if ($('#perio_cont').length === 1) setPeriodo();
});
/* comprobante funciones */
function setPeriodo() {
    $('#cuenDialog').getDialogGrid().clearGrid();
    var perio_cont = getPeriodo();
    $("input[name='Pec_Cod']").val(perio_cont["Pec_Cod"]);
    $("input[name='Old_Pec_Cod']").val(perio_cont["Pec_Cod"]);
    $("input[name='Periodo']").val(perio_cont["periodo"]);
    $('#bancos').val('');
    if (perio_cont['Pec_Cod'] === null) {
        $('.perio_cont').attr('disabled', 'disabled');
    } else {
        if (!duplica) {
            $("input[name='Com_Fec']").dateLimits(perio_cont["Pec_Fei"], perio_cont["Pec_Fef"]);
            $('.perio_cont').removeAttr('disabled');
        }
    }
    resetForm();
}
function getPeriodo() { var opt = $('#perio_cont option:selected'); return (!opt.length || opt.val() === '' || opt.val() === null) ? { Pec_Cod: null } : opt.data(); }
function deleteFilaCuenta(Pld_Cod) { gridCompAsien.jqGrid('delRowData', Pld_Cod); resizeGridComp(); gridCompAsien.updateGridDiario(); }
function addFilaCuenta(cuenta, tipo) {
    var setter = { Index: $('#Index').val(), Glosa: '', Det_Tip: tipo, Debe: 0, Haber: 0 };
    if (setter['Index'] === '') {
        var max = gridCompAsien.jqGrid('getCol', 'Index', false, 'max'), next = (isNaN(max) ? 1 : max + 1);
        setter['Index'] = next;
        gridCompAsien.jqGrid("addRowData", setter['Index'], $.extend(cuenta, setter), "last");
        resizeGridComp();
    } else {
        gridCompAsien.jqGrid('saveRow', setter['Index'], false, 'clientArray');
        var old_data = gridCompAsien.jqGrid('getRowData', setter['Index']);
        setter['Glosa'] = old_data['Glosa'];
        setter[tipo === 'D' ? 'Debe' : 'Haber'] = old_data[old_data['Det_Tip'] === 'D' ? 'Debe' : 'Haber'];
        gridCompAsien.jqGrid('setRowData', setter['Index'], $.extend(cuenta, setter));
        $('#cuenDialog').dialog('close');
    }
    gridCompAsien.jqGrid('editRow', setter['Index']);
    gridCompAsien.updateGridDiario();
}
function updateValores() { $("#Com_Val").val($.toFixed($("#Com_Val").val())); }
function resizeGridComp() { var w = $('#compGrilla').width(); if (gridCompAsien.width() > (w + 2) || gridCompAsien.width() < (w - 2)) gridCompAsien.jqGrid('resizeGrid'); }
function resetForm() {
    gridCompAsien.clearGrid().updateGridDiario();
    var dat_reset = {};
    $('#formCompConta').setData(dat_reset);
    updateTiaCod((tipo === 'Ingreso' ? 'I' : (tipo === 'Egreso' ? 'E' : 'D')), 'Tia_Cod_Comp');
    return false;
}

function verificarAnio() {
    var anio_verificar = $('#Com_Fec').val().split('-')[0];
    if ($('#Old_Com_Fec').val().split('-')[0] !== anio_verificar) {
        var Per_Con = $('#Pec_Cod').val();
        $.post("", { verificarAnio: true, 'anio': anio_verificar, 'Pec_Cod': Per_Con },
            function (responce) {
                if (!responce['state']) {
                    $.alert('Imposible Duplicar Comprobante en la Fecha Requerida', function () {
                        $('#Com_Fec').val($('#Old_Com_Fec').val());
                        $('#Pec_Cod').val($('#Old_Pec_Cod').val());
                    }, 'alert');
                } else {
                    $('#Pec_Cod').val(responce['Pec_Cod']);
                }
            }
            , 'json').fail(function () {
                $.alert();
            });
    }
}

function validaGrid() {
    var batch = gridCompAsien.getGridBatch(), ban = true, msg = '';
    var deb = $.round(gridCompAsien.jqGrid("getCol", "Debe", false, "sum")),
        hab = $.round(gridCompAsien.jqGrid("getCol", "Haber", false, "sum"));
    gridCompAsien.startGridEdit();
    if ((deb === hab && deb === 0) || batch.length === 0) { msg = ("El comprobante no puede tener valor <i>cero</i>!"); ban = false; }
    if (!(deb === hab)) { msg = ("Los Totales no Coinciden!"); ban = false; }
    $.each(batch, function (i, v) {
        if (('0' + v[v['Det_Tip'] === 'D' ? 'Debe' : 'Haber']) * 1 === 0) { msg = ("El valor de la cuenta <u>No. " + (i + 1) + ": " + v['Pld_Des'] + "</u> no puede ser cero!"); ban = false; return ban; }
    });
    if (ban === false) { $.alert(msg); return ban; }
    return batch;
}
function validaComp() {
    if (tipo === 'Ingreso') {
        if ($('#cod_cli').val() === '') { $.alert("Seleccione El Cliente"); return; }
    } else {
        if ($('#cod_pvr').val() === '') { $.alert("Seleccione El Proveedor"); return; }
    }
    var batch = validaGrid(); if (batch === false) return;
    var data = $.extend($('#formCompConta').serializeObject(), {
        Com_Tip: (tipo === 'Ingreso' ? 'I' : (tipo === 'Egreso' ? 'E' : 'D')),
        saveForm: true,
        cuentas: batch,
        Tia_Abr: $('#Tia_Cod_Comp option:selected').data('Tia_Abr'),
        Com_Est: $('#Com_Est option:selected').data('Com_Est')
    }, $('#periodoForm').serializeObject());

    //Validaciones para Duplicar Comprobantes
    if (duplica) {
        $.createDialogConfirm('¿Est&aacute; seguro que desea Duplicar el Comprobante?', data, saveComp);

    } else {
        $.createDialogConfirm('¿Est&aacute; seguro que desea guardar los datos?', data, saveComp);
    }


}
function saveComp(data) {
    $.saveDataJson("", data, function (r) {
        $('#btnImpCompr').data('url', r['link']);
        $('#successCodigo').html(r['codigo']);
        $('#successDialog').dialog('open');
        if ($.varValid(listSearch)) {
            editing = false;
            listSearch.trigger('reloadGrid', []);
            $('#modificar-panel').moveComp('#main-panel').updateGridsSizes();
        }
        return resetForm();
    });
}
/* fin comprobante funciones */
function updateNumCom() {
    var month = ($('#Month').val().split('-'))[1];
    $('#Com_Num').html(' ' + ($('#Tia_Cod').val() === '' ? '' : $('#Tia_Cod option:selected').data('abre') + '-') + ('00').substring(month.toString().length) + month + '- ');
}
function selectFiltro(tip) {
    $('.filtros').css('display', 'none');
    if (tip === 'a') $('#asien').css('display', '');
    if (tip === 'r') $('#rangos').css('display', '');
    // nuevo filtro de cuenta contable
    if(tip==='c'){
        $('#rangos').css('display', '');
        $('#Cuent_Cont').css('display','');
    } else {
        $('#Cuent_Cont').css('display','none');
        $('#Pld_Des_Com').val('');
    }
    if (tip === 't') $('#todos').css('display', '');
    if (tip === 'n') $('#rangos').css('display', '');
}
function updateTiaCod(Tia_Ini, id) {
    var el = $('#' + id);
    el.find('option').css('display', 'none');
    if (Tia_Ini !== '') el.find('option[data-type=' + Tia_Ini + ']').css('display', '');
    el.find('option.todos').css('display', '');
    el.val('');
}

function ImpCom(rowVenta) {
    $.getDataJson('', { 'cargarReportes': true }, function (res) {
        var reportes = res['reportes'];
        $.varValid(reportes[2]) ? $.imprimirUrl(reportes[2] + '?codigo=' + rowVenta.Com_Cod) : $.alert('Sin Reportes Asociados');
    }, function (err) {
        console.log(err['message']);
    });
}
/* guardado no edit */
function selectComp(data) {
    Com_Cod = data['Com_Cod'];
    editing = false;
    var editable = noEdit;
    if (editable === true || duplica) {
        if ((data['Com_Gen'] === 'A' || data['Doc'] !== '') && !duplica) editable = false;
        else {
            editing = true;
            tipo = (data['Tia_Ini'] === 'I' ? 'Ingreso' : (data['Tia_Ini'] === 'E' ? 'Egreso' : 'Diario'));
            data['Old_Com_Num'] = data['Com_Num'];
            data['Old_Com_Fec'] = data['Com_Fec'];
            data['Old_Pec_Cod'] = data['Pec_Cod'];
            $('#title_comp').html(tipo);
            updateTiaCod(data['Tia_Ini'], 'Tia_Cod_Comp');
            data['proveedor'] = data['cliente'] = data['Persona'];
            $('#formCompConta').find('.persona').hide();
            $('#formCompConta').find('.persona.' + (data['Tia_Ini'] === 'I' ? 'cliente' : 'proveedor')).show();
            $('#formCompConta').setData(data);
            dataSend = { 'loadData': true, /*notDoc:true,*/ 'Com_Cod': data['Com_Cod'], 'Cop_Cod': data['Cop_Cod'], 'Vet_Cod': data['Vet_Cod'] };
            $.get("", dataSend, function (response) {
                if (response['success'] === true) {
                    $('input[name=Periodo]').val(response['compro']['Periodo']);
                    $('input[name=Pec_Cod]').val(response['compro']['Pec_Cod']);
                    if (!duplica) {
                        $('#Com_Fec').dateLimits(response['compro']['Pec_Fei'], response['compro']['Pec_Fef']);
                    }
                    $('#Com_Fec').val(response['compro']['Com_Fec']);
                    gridCompAsien.setRowsByIndex(response['compro']['detalle'], 'Index');
                    gridCompAsien.startGridEdit().updateGridDiario();
                    $('#main-panel').moveComp('#modificar-panel').updateGridsSizes();
                } else { $.alert(response['message']); }
            }, 'json').fail(function (error) { $.alert("El Servidor ha fallado en responder!"); });
        }
    }
    if (editable === false) {
        if (data['Doc'] !== '') {
            $('#doc_type').html(data['Doc']); $('.doc_panel').show(); $('.no_doc_panel').hide();
        } else {
            $('.doc_panel').hide(); $('.no_doc_panel').show();
        } //console.log(data);
        $('#asientoAutomatico').setData(data, null, 'compr');
        $('#Doc_Doc_Num').val(data['Doc_Num']);

        dataSend = { 'loadData': true, 'Com_Cod': data['Com_Cod'], 'Cop_Cod': data['Cop_Cod'], 'Vet_Cod': data['Vet_Cod'] };
        $.get("", dataSend, function (response) {
            if (response['success'] === true) {
                compNoEdit.setRows(response['compro']['detalle']);
                $('input[name=Periodo]').val(response['compro']['Periodo']);
                $('input[name=Pec_Cod]').val(response['compro']['Pec_Cod']);
                if (!duplica) {
                    $('#Com_Com_Fec').dateLimits(response['compro']['Pec_Fei'], response['compro']['Pec_Fef']);
                }

                $('#Com_Com_Fec').val(response['compro']['Com_Fec']);

                if (response['cheques']['conteo'] * 1 === 0) {
                    $('#btnAlta').attr("disabled", false);
                    $('#btnBaja').attr("disabled", false);
                    $('#btn_agr').hide();
                } else {
                    if (alta) {
                        $('#btnAlta').attr("disabled", true);
                        $.alert('No se puede dar de Alta al Comprobante, tiene cheques asociados');
                    }
                    if (anula) {
                        $('#btnBaja').attr("disabled", true);
                        $.alert('No se puede dar de Baja al Comprobante, tiene cheques asociados');
                    }
                    $('#chequesDialog').getDialogGrid().setRows(response['cheques']['detalle']);
                    $('#btn_agr').show();
                }
                if (typeof response['compra'] !== 'undefined') {
                    $('#doc_panel').setData(response['compra'], null, 'compra');
                    docuView.setRowsByIndex(response['compra']['detalle'], 'Index');
                }
                if (typeof response['venta'] !== 'undefined') {
                    $('#doc_panel').setData(response['venta'], null, 'venta');
                    docuView.setRowsByIndex(response['venta']['detalle'], 'Index');
                }
                $('#main-panel').moveComp('#edit-panel').updateGridsSizes();
            } else { $.alert(response['message']); }
        }, 'json').fail(function (error) { $.alert("El Servidor ha fallado en responder!"); });
    }

}
function saveNoEdit() {
    dataSend['saveData'] = true;
    dataSend['form'] = $('#formAsien').getData();
    dataSend['asien'] = new Array();
    var grid = compNoEdit, rows = grid.jqGrid('getRowData');
    for (var i = 0; i < rows.length; i++) {
        if ($.toBool(rows[i].act1)) { dataSend['asien'].push(rows[i]); }
    }
    dataSend['form']['Com_Val'] = grid.jqGrid('getCol', 'Debe', false, 'sum');
    dataSend['form']['Com_Cod'] = dataSend['Com_Cod'];
    dataSend['form']['Tia_Abr'] = $('#Com_Tia_Cod').find('option:selected').data('abre');
    //console.log(dataSend);
    $.saveDataJson("", dataSend, function (response) {
        listSearch.trigger('reloadGrid', []);
        $('#edit-panel').moveComp('#main-panel').updateGridsSizes();
        $('#btnImpCompr').data('url', response['link']);
        $('#successCodigo').html(response['codigo']);
        $('#successDialog').dialog('open');
        return false;
    });
}


function anularComp() {
    var dataSend = { 'anularComp': true, 'Com_Cod': Com_Cod };
    $.createDialogConfirm('Desea Dar de Baja al Comprobante?', null,
        function () {
            $.saveDataJson('', dataSend, function (res) {
                $('#listsearch').trigger('reloadGrid');
                $('#edit-panel').moveComp('#main-panel').updateGridsSizes();
            });
        }, function () {

        });
}


function activarComp() {
    var dataSend = { 'activarComp': true, 'Com_Cod': Com_Cod };
    $.createDialogConfirm('Desea Dar de Alta al Comprobante?', null,
        function () {
            $.saveDataJson('', dataSend, function (res) {
                $('#listsearch').trigger('reloadGrid');
                $('#edit-panel').moveComp('#main-panel').updateGridsSizes();
            });
        }, function () {

        });
}