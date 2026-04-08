var idRow = $('#detalleM').nextIndex();
var esCrear = false;
var esMod = false;
var gridName = '';
var formName = '';
var arrayRetenciones = [];
var arrayModRetenciones = [];
var valorInicial = '';
var mesRetVenta;
$(function() {

    //$('#retencionesDialog').createDialog({ height: 270, width: 475 });   
    //retencionesExistentes();
    $.createDateRange('#Fec_Ini', '#Fec_Fin');
    clickInactivos();
    var opts = {
        height: 75,
        colModel: [
            { label: 'Cód.Int.', name: 'Rvt_Cod', key: true, width: 15, align: "center", hidden: false },
            { label: 'C&oacute;digo', name: 'Ren_Sri', width: 15, align: 'center' },
            { name: 'index', label: 'Index', width: 20, sorttype: 'int', align: 'center', hidden: true },
            { label: 'Impuesto ', name: 'Ren_Con', width: 70, align: 'center' },
            { label: 'Base Imp. ', name: 'Rvt_Bas', width: 15, align: 'center' },
            { label: 'Procentaje', name: 'Ren_Por', width: 20, align: 'center' },
            { label: 'Importe', name: 'Total', width: 15, align: 'right' }
        ],
        footerrow: true,
        userDataOnFooter: true,
    };

    $("#tabsRetencion").createTabs();
    if ($('#docDetaDialog').length > 0)
        $('#docDetaDialog').createDialog({ height: 400, width: 600, noTitleStuff: false, noBorder: true });
    if ($('#detaDocu').length > 0)
        $('#detaDocu').createGrid($.extend(opts, { height: 'auto', width: 550, responsive: false, caption: null, rownumbers: false }), true);

    $('.datepickers').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });
    var sel_fecha = $("#Pec_Cod").find('option:selected');
    //console.log(sel_fecha);
    $('#Caj_Fec').dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'));
    $('#Rvt_Fec').dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'));
    $('#Rvt_Doc').unmask().mask("999-999-999999999", { placeholder: "_" });
    $('#Rvt_Num').unmask().mask("999-999-999999999", { placeholder: "_" });
    $('#frm_mod_ret_edi').find('#Rvt_Num').unmask().mask("999-999-999999999", { placeholder: "_" });

    $('#formFinal').find('.form-group-options').append('<div class="col-md-4"> <label class="control-label label-xs">Plan de Cuentas:&nbsp;</label><input id="Index" name="Index" type="hidden" value="" /><input data-name="Pec_Cod" id="Pec_Cod" name="Pec_Cod" type="hidden" /><input data-name="Year" name="Periodo" type="text" size="6" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /></div>');
    //$('#formFinal').find('.form-group-options').append('<div class="col-md-4"> <label class="control-label label-xs">Tipo de Compra&nbsp;</label><input id="Index" name="Index" type="hidden" value="" /><input data-name="Tic_Cod" name="Tic_Cod" type="hidden" /><input data-name="Tic_Des" name="Tic_Des" type="text" size="6" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /></div>');
    //console.log("Valor enviado");
    if (detPlanCuenta.length > 0 && Cof_Con === "S") {
        //console.log(combinaParametro());
        $('#Pld_Cod').val(detPlanCuenta[0].Pld_Cod);
        $('#Pld_Cod').html(detPlanCuenta[0].Pld_Cod);
        $('#Pld_Cdc').val(combinaParametro());
        //$('#Pld_Des').val(detPlanCuenta[0].Pld_Des);
    } else {
        $('#Pld_Cdc').val('No existe cuenta parametrizada');
        //$('#Pld_Des').val('No existe cuenta parametrizada');
    }

    //Pld_Cdc, Pld_Des

    $('#tableRetenciones').createGrid({
        caption: 'Retenciones Bancarias',
        height: 330,
        //stateConfig: { Inactivo: 'cellRed2' }, stateCondition: function (row) { if (row['Rvt_Est'] !== 'A') return 'Inactivo'; },
        colModel: [
            { label: 'C&oacute;d. Int.', name: 'Rvt_Cod', hidden: true, width: 2, align: "center" },
            { label: 'Cliente', name: 'cliente', hidden: false, width: 20, align: "center" },
            { label: 'Cuenta en Movimiento', name: 'cuenta', hidden: false, width: 20, align: "center" },
            { label: 'Fecha', name: 'Rvt_Fec', hidden: false, width: 10, align: "center" },
            { label: 'Retencion Estado.', name: 'Rvt_Est', hidden: true, width: 10, align: "center" },
            { label: 'Retención Num.', name: 'Rvt_Num', hidden: false, width: 10, align: "center" },
            //{ label: 'Tipo Emisión', name: 'tipo', align: "center", width: 10 },
            { label: 'Observaciones', name: 'Rvt_Obs', align: "center", width: 30 },
            { label: $.createIcon('glyphicon glyphicon-pencil'), name: 'actReg', align: "center", width: 5, formatter: 'gridButton', formatoptions: { action: verRetencion, conditional: function(o) { return o.Rvt_Est !== 'I'; }, caseFalse: function(o) { if (o.Rvt_Est !== "A") return $.createIcon('lock orange', false, 'title="Inactivo/Anulado!"'); }, icon: 'glyphicon glyphicon-pencil', type: 'success', title: 'Modificar Retención' } },
            { label: $.createIcon('info-sign'), name: 'act0', width: 5, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: viewInfo, conditional: function(o) { return o.Rvt_Est !== 'I'; }, caseFalse: function(o) { if (o.Rvt_Est !== "A") return $.createIcon('lock orange', false, 'title="Inactivo/Anulado!"'); }, title: 'Detalle Retención', icon: 'info-sign', type: 'info' }, title: false },
            {
                label: $.createIcon('remove'),
                name: 'delete',
                width: 5,
                align: 'center',
                viewable: false,
                formatter: 'gridButton',
                formatoptions: { action: clearFila, conditional: function(o) { return o.Rvt_Est === 'A'; }, caseFalse: function(o) { if (o.Rvt_Est !== "A") return $.createIcon('remove red', false, 'title="Inactivo/Anulado!"'); }, icon: 'trash', type: 'danger', title: 'Anular Retención', data: function(o) { return o; } },
                resizable: false
            }


        ],
        loadComplete: function(data) {
            if ($.varValid(data.rows)) {
                for (var i = 0, z = data.rows.length; i < z; i++) {
                    if (data.rows[i]['Rvt_Est'] === 'I') { $("#tableRetenciones").find("#" + data.rows[i].Rvt_Cod + ' td:not(.jqgrid-rownum)').addClass('cellRed2'); }
                    // if (data.rows[i]['Rvt_Est'] === 'A') { $("#tableRetenciones").find("#" + data.rows[i].Rvt_Cod + ' td:not(.jqgrid-rownum)').addClass('cellGreen2'); }

                }
            }

        }
    }, true, '#tableRetencionesPager', {});

    $('#tableRetencionesC').createGrid({
        caption: 'Retenciones Bancarias',
        height: 330,
        //stateConfig: { Inactivo: 'cellRed2' }, stateCondition: function (row) { if (row['Rvt_Est'] !== 'A') return 'Inactivo'; },
        colModel: [
            { label: 'C&oacute;d. Int.', name: 'Rvt_Cod', hidden: false, width: 5, align: "center" },
            { label: 'Ruc', name: 'ruc', hidden: false, width: 12, align: "center" },
            { label: 'Cliente', name: 'cliente', hidden: false, width: 20, align: "center" },
            { label: 'Cuenta en Movimiento', name: 'cuenta', hidden: false, width: 20, align: "center" },
            { label: 'Fecha', name: 'Rvt_Fec', hidden: false, width: 10, align: "center" },
            { label: 'Retencion Estado.', name: 'Rvt_Est', hidden: true, width: 10, align: "center" },
            { label: 'Retención Num.', name: 'Rvt_Num', hidden: false, width: 10, align: "center" },
            {
                label: 'Total Renta',
                name: 'renTot',
                hidden: false,
                width: 10,
                align: "right",
                formatter: function(cellvalue, options, rowObject) {
                    if (rowObject['renTot'] === '' || rowObject['renTot'] === null) { return "0.00"; } else { return formatMoney(rowObject['renTot']); }
                }

            },
            {
                label: 'Total IVA',
                name: 'ivaTot',
                hidden: false,
                width: 10,
                align: "right",
                formatter: function(cellvalue, options, rowObject) {
                    if (rowObject['ivaTot'] === '' || rowObject['ivaTot'] === null) { return "0.00"; } else { return formatMoney(rowObject['ivaTot']); }
                }
            },
            {
                label: 'Total',
                name: 'Total',
                hidden: false,
                width: 10,
                align: "right",
                formatter: function(cellvalue, options, rowObject) {
                    if (rowObject['Total'] === '' || rowObject['Total'] === null) { return "0.00"; } else { return formatMoney(rowObject['Total']); }
                }
            },

            //{ label: 'Tipo Emisi´pn', name: 'tipo', align: "center", width: 10 },
            /*{ label: 'Observaciones', name: 'Rvt_Obs', align: "center", width: 30 },
            { label: $.createIcon('glyphicon glyphicon-edit'), name: 'actReg', align: "center", width: 5, formatter: 'gridButton', formatoptions: { action: verRetencion, conditional: function(o) { return o.Rvt_Est !== 'I'; }, caseFalse: function(o) { if (o.Rvt_Est !== "A") return $.createIcon('lock orange', false, 'title="Inactivo/Anulado!"'); }, icon: 'glyphicon glyphicon-edit', type: 'success', title: 'Modificar Retención' } },
            { label: $.createIcon('info-sign'), name: 'act0', width: 5, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: viewInfo, conditional: function(o) { return o.Rvt_Est !== 'I'; }, caseFalse: function(o) { if (o.Rvt_Est !== "A") return $.createIcon('lock orange', false, 'title="Inactivo/Anulado!"'); }, title: 'Detalle Retención', icon: 'info-sign', type: 'info' }, title: false },
            {
                label: $.createIcon('remove'),
                name: 'delete',
                width: 5,
                align: 'center',
                viewable: false,
                formatter: 'gridButton',
                formatoptions: { action: clearFila, conditional: function(o) { return o.Rvt_Est === 'A'; }, caseFalse: function(o) { if (o.Rvt_Est !== "A") return $.createIcon('remove red', false, 'title="Inactivo/Anulado!"'); }, icon: 'trash', type: 'danger', title: 'Anular Retención', data: function(o) { return o; } },
                resizable: false
            }*/


        ],
        footerrow: true,
        userDataOnFooter: true,
        rowNum: 10000,
        gridview: true,
        viewrecords: true,
        loadComplete: function(data) {
            if ($.varValid(data.rows)) {
                for (var i = 0, z = data.rows.length; i < z; i++) {
                    if (data.rows[i]['Rvt_Est'] === 'I') { $("#tableRetencionesC").find("#" + data.rows[i].Rvt_Cod + ' td:not(.jqgrid-rownum)').addClass('cellRed2'); }
                    if (data.rows[i]['Rvt_Est'] === 'A') { $("#tableRetencionesC").find("#" + data.rows[i].Rvt_Cod + ' td:not(.jqgrid-rownum)').addClass('cellGreen2'); }
                }


            }
            calculoValores();

        }
    }, true, '#tableRetencionesCPager', { refresh: false, view: false });

    $('#tableRetencionesC').getFootRow(true);






    $('#detalle').createGrid({
        caption: '*Detalle Retención Bancaria',
        height: 330,
        datatype: "local",
        colModel: [
            { label: 'C&oacute;d. Int.', name: 'Rvt_Cod', hidden: true, width: 30, align: "center" },
            { name: 'index', label: 'Index', width: 20, sorttype: 'int', align: 'center', hidden: true },
            { name: 'Ren_Cod', key: true, width: 20, align: "center", hidden: true },
            { name: 'Pld_Cod', key: true, width: 20, align: "center", hidden: true },
            { name: 'Pla_Cod', key: true, width: 20, align: "center", hidden: true },
            { label: 'C&oacute;d. Retenci&oacuten.', name: 'Ren_Sri', width: 15, align: "left", title: true, formatter: 'input2', formatoptions: { id: '1', title: 'Agregar Retención', action: "dialogRetOpen", data: function(o) { return o; } }, resizable: false },
            { label: 'Impuesto.', name: 'Ren_Con', width: 80, align: "center" },
            { label: 'Base Imp.', name: 'Rvt_Bas', width: 15, align: "center", formatter: 'input2', formatoptions: { id: '2', attr: '' } },
            { label: 'Porcentaje', name: 'Ren_Por', width: 15, align: "center" },
            { label: 'Importe', name: 'total_val', width: 15, align: "center", formatter: 'input3', formatoptions: { id: '2', attr: '' } },
            { name: 'delete', label: '<i class="glyphicon glyphicon-remove"></i>', width: 10, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: delFila, /*conditional: function(o) { console.log(o); return o.tarja === 'n' && o.Prh_Est !== 'I'; },*/ icon: 'remove', type: 'danger', title: 'Eliminar Item', data: function(o) { return o; } }, resizable: false }
        ],
        rowNum: 10000000,
        footerrow: true,
        userDataOnFooter: true,
        viewrecords: false,
        /*loadComplete: function () {
            //var pagos_tot = pagos.jqGrid('getCol', 'Vet_Tot', false, 'sum');
            $(this).setGridSummary(['total_val'], { Ren_Por: '<div style="text-align:right;">TOTAL:</div>' });
        }*/
    }, true, '#detallePager', { view: false }).gridButtonsAdd([{
        id: 'btn_agr',
        caption: 'Agregar Detalle',
        buttonicon: 'glyphicon glyphicon-plus',
        onClickButton: function() { agregarFila(0); }
    }]);

    $("#detaDocu").getFootRow(true);
    $("#detaDocu").jqGrid('footerData', 'set', {
        Ren_Por: '<div class="footerFact formDatos" class="formDatos"><label class="total">TOTAL:</label></div>',
        Total: '<div class="footerFact formDatos" id="formTotales"><input id="t_retencion_view" name="t_retencion_view" type="text"  class="total" readonly/></div>'
    }, false);

    $("#detalle").getFootRow(true);
    $("#detalle").jqGrid('footerData', 'set', {
        Ren_Por: '<div class="footerFact formDatos" class="formDatos"><label class="total">TOTAL:</label></div>',
        total_val: '<div class="footerFact formDatos" id="formTotales"><input id="t_retencion" name="t_retencion" type="text"  class="total" readonly/></div>'
    }, false);
    $.fn.fmatter.input2 = function(cv, opts, cObjt) {
        var set = opts['colModel']['formatoptions'],
            el;
        //console.log(opts);
        if (set['id'] === '1') { el = $('<div class="input-group input-group-xs ret"><input type="text" id="' + opts['rowId'] + '" name="' + opts['colModel']['name'] + '" class="form-control input-xs ' + set['class'] + '"/><span class="input-group-btn"><button class="btn btn-info" type="button" title="' + set['title'] + '" onclick="' + set['action'] + '(' + opts['rowId'] + ')"><span class="glyphicon glyphicon-search"></span></button></span></div>'); } else { el = $('<input type="text" id="' + opts['rowId'] + '_' + opts['colModel']['name'] + '" name="' + opts['colModel']['name'] + '" class="form-control input-xs" ' + set['attr'] + '/>'); }
        return el.prop('outerHTML');
    };
    $.fn.fmatter.input2.unformat = function(cv, opts, cObjt) { return $(cObjt).find(':input').val(); };
    $.fn.fmatter.input3 = function(cv, opts, cObjt) {
        var set = opts['colModel']['formatoptions'],
            el;
        //console.log(opts);
        if (set['id'] === '1') { el = $('<div class="input-group input-group-xs ret"><input type="text" id="' + opts['rowId'] + '" class="form-control input-xs ' + set['class'] + '"/><span class="input-group-btn"><button class="btn btn-info" type="button" title="' + set['title'] + '" onclick="' + set['action'] + '(' + opts['rowId'] + ')"><span class="glyphicon glyphicon-search"></span></button></span></div>'); } else { el = $('<input type="text"   style="text-align: right;" id="' + opts['rowId'] + '_' + opts['colModel']['name'] + '" name="' + opts['colModel']['name'] + '" class="form-control input-xs" ' + set['attr'] + 'readonly />'); }
        return el.prop('outerHTML');
    };
    $.fn.fmatter.input3.unformat = function(cv, opts, cObjt) { return $(cObjt).find(':input').val(); };
    if (Cof_Con === 'N') { $('#retFormTemp4').hide(); }
    //buscarAllRetencioes();

    $('#detalleM').createGrid({
        caption: '**Detalle Retención Bancarias',
        height: 330,
        datatype: "local",
        colModel: [
            { label: 'C&oacute;d. Int.', name: 'Rvt_Cod', hidden: true, width: 30, align: "center" },
            { name: 'index', label: 'Index', width: 20, sorttype: 'int', align: 'center', hidden: true },
            { name: 'Ren_Cod', key: true, width: 20, align: "center", hidden: true },
            { name: 'Pld_Cod', key: true, width: 20, align: "center", hidden: true },
            { name: 'Pla_Cod', key: true, width: 20, align: "center", hidden: true },
            { label: 'C&oacute;d. Retenci&oacuten.', name: 'Ren_Sri', width: 15, align: "center", title: true, formatter: 'input2', formatoptions: { id: '1', title: 'Agregar Retención', action: "dialogRetOpen", data: function(o) { return o; } }, resizable: false },
            { label: 'Impuesto.', name: 'Ren_Con', width: 80, align: "center" },
            { label: 'Base Imp.', name: 'Rvt_Bas_Mod', width: 15, align: "center", formatter: 'input2', formatoptions: { id: '2', attr: '' } },
            { label: '%', name: 'Ren_Por', width: 15, align: "center" },
            { label: 'Importe', name: 'total_val', width: 15, align: "center", formatter: 'input3', formatoptions: { id: '2', attr: '' } },
            { name: 'delete', label: '<i class="glyphicon glyphicon-remove"></i>', width: 10, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: delFila2, /*conditional: function(o) { console.log(o); return o.tarja === 'n' && o.Prh_Est !== 'I'; },*/ icon: 'remove', type: 'danger', title: 'Eliminar Item', data: function(o) { return o; } }, resizable: false }
        ],
        rowNum: 10000000,
        footerrow: true,
        userDataOnFooter: true,
        viewrecords: false,
    }, true, '#detalleMPager', { view: false }).gridButtonsAdd([{
        id: 'btn_agr_det',
        caption: 'Agregar Detalle',
        buttonicon: 'glyphicon glyphicon-plus',
        onClickButton: function() {  agregarFila(1); }
    }]);


    $("#detalleM").getFootRow(true);
    $("#detalleM").jqGrid('footerData', 'set', {
        Ren_Por: '<div class="footerFact formDatos" class="formDatos"><label class="total">TOTAL:</label></div>',
        total_val: '<div class="footerFact formDatos" id="formTotales"><input id="t_retencion_m" name="t_retencion_m" type="text"  class="total" readonly/></div>'
    }, false);





});

function calculoValores() {
    $('#tableRetencionesC').startGridEdit();
    let ids = $('#tableRetencionesC').jqGrid('getDataIDs');
    var totRent = 0,
        totIva = 0,
        tot = 0;
    for (let i = 0; i < ids.length; i++) {
        let reg_actividad = $('#tableRetencionesC').jqGrid('getRowData', ids[i]);
        var priceRent = parseFloat(reg_actividad['renTot'].replace(/[^0-9-.]/g, ''));
        var priceIva = parseFloat(reg_actividad['ivaTot'].replace(/[^0-9-.]/g, ''));
        var totalG = parseFloat(reg_actividad['Total'].replace(/[^0-9-.]/g, ''));
        totRent = totRent + parseFloat(priceRent * 1);
        totIva += parseFloat(priceIva * 1);
        tot += parseFloat(totalG * 1);
    }
    $('#tableRetencionesC').jqGrid('footerData', 'set', { Rvt_Num: "TOTALES:" });
    $('#tableRetencionesC').jqGrid('footerData', 'set', { renTot: "" + totRent });
    $('#tableRetencionesC').jqGrid('footerData', 'set', { ivaTot: "" + totIva });
    $('#tableRetencionesC').jqGrid('footerData', 'set', { Total: "" + tot });






}


function clickInactivos() {
    $('#rad_bac4').on('click', function() {
        $('#divFecha').show();
        $('#Fec_Ini').removeAttr('disabled');
        $('#Fec_Fin').removeAttr('disabled');
        //$('#frm_mod_ret_con').find('#search').attr('disabled', 'disabled');
    });
    $('#rad_bac3').on('click', function() {
        $('#divFecha').hide();
        $('#Fec_Ini').attr('disabled', 'disabled');
        $('#Fec_Fin').attr('disabled', 'disabled');
        $('#frm_mod_ret_con').find('#search').attr('disabled', 'disabled');
    });
    $('#rad_bac2').on('click', function() {
        $('#divFecha').hide();
        $('#Fec_Ini').attr('disabled', 'disabled');
        $('#Fec_Fin').attr('disabled', 'disabled');
        $('#frm_mod_ret_con').find('#search').removeAttr('disabled');
    });
    $('#rad_bac1').on('click', function() {
        $('#divFecha').hide();
        $('#Fec_Ini').attr('disabled', 'disabled');
        $('#Fec_Fin').attr('disabled', 'disabled');
        $('#frm_mod_ret_con').find('#search').removeAttr('disabled');
    });


}

function combinaParametro() {
    if (Cof_Con === "S") {
        var union = detPlanCuenta[0].Pld_Cdc + ' - ' + detPlanCuenta[0].Pld_Des;
        return union;
    } else {
        return 0;
    }

}

function clearFila(row) {
    $.createDialogConfirm('Una vez realizado la transacci&oacute;n no se podrá revertir los cambios<br/> <strong>¿Desea continuar?</strong>', null, function() {
        console.log(row);
        $.saveDataJson("", { setInactiveRet: true, Rvt_Cod: row['Rvt_Cod'], Com_Cod: row['Com_Cod'] }, (responce) => {
            if (responce['success']) {
                $('#tableRetenciones').changeRow(row['Rvt_Cod'], { Rvt_Est: 'I', actDel: '' });
                $('#tableRetenciones').trigger("reloadGrid");
                $.alert('La transacci&oacute;n se realizo con exito.');
                return false;
            }
        });

    });
}

function viewInfo(row) {
    var importeTotal;
    var frase = "";
    var frase2 = "";
    var next = $("#detaDocu").jqGrid('getCol', 'index', false, 'max');
    next = (isNaN(next) ? 1 : next + 1);
    //console.log('estamos en el viewInfo ', row);
    //detalleRetenAjax
    $.getDataJson("", { detalleRetenAjax: true }, function(detalle) {
        $('#detaDocu').clearGrid();
        detalle.detRet.forEach(function(respuesta) {
            if (respuesta['Rvt_Cod'] === row['Rvt_Cod']) {
                //console.log(respuesta);
                importeTotal = ((respuesta['Rvt_Bas'] * respuesta['Ren_Por']) / 100);
                //console.log(respuesta['Ren_Con'].length);
                if (respuesta['Ren_Con'].length > 50) {
                    var splitted = respuesta['Ren_Con'].split(" ");
                    for (var i = 0; i < 7; i++) {
                        var palabra = splitted[i];
                        frase = frase + palabra + " ";
                    }
                    for (var i = 7; i < splitted.length; i++) {
                        var palabra = splitted[i];
                        frase2 = frase2 + palabra + " ";
                    }
                    var salto = frase + '\n' + frase2;
                } else { var salto = respuesta['Ren_Con']; }
                $("#detaDocu").jqGrid('addRowData', next, $.extend(respuesta, { index: next, Rvt_Cod: respuesta['Rvt_Cod'], Ren_Con: salto, Rvt_Bas: parseFloat(respuesta['Rvt_Bas']).toFixed(2), Ren_Por: respuesta['Ren_Por'], Total: parseFloat(importeTotal).toFixed(2) }), 'last');
                $('#docDetaDialog').dialog('open').updateGridsSizes();
                importeTotal = 0;
                frase = "";
                frase2 = "";
            }

        });
        let total_ret = $('#detaDocu').jqGrid('getCol', 'Total', false, 'sum');
        console.log(total_ret);
        $('#t_retencion_view').val(parseFloat(total_ret).toFixed(2));
    });

    $('#docDetaDialog').setData(row);
    $('#docDetaDialog').dialog('open');

}

function buscarAllRetencioes() {
    $.getDataJson("", { searchAllRet: true }, function(resultado) {      
        $('#tableRetenciones').setRows(resultado.rows);
    });
}

function clearDocument() {
    $('#documentoVistaER').hide();
}


function verRetencion(row) {    
	var anhoPeriodo = row['Pec_Fei'];	
	var anio=anhoPeriodo.split('-');
	esMod = true;
    
    if (Cof_Con === 'S') {        
        //$('#Pec_Fei_anho').val(anhoPeriodo[0]);
        //$('input:text[name=Pec_Fei_anho]').val(anhoPeriodo[0]);
        $('#Pld_Cod').val(detPlanCuenta[0].Pld_Cod);
        $('#param_Cuenta').val(combinaParametro());
    }
    mesRetVenta = row['Rvt_Fec'].split('-');
       $('#btn_atras').show();
    getDetalle(row);
    $('#documentoVistaER').setData(row);
    $('input:text[name=Pec_Fei_anho]').val(anio[0]);

    $('#tab2').moveComp('#documentoVistaER').updateGridsSizes();
    $('#Rvt_Num').unmask().mask("999-999-999999999", { placeholder: "_" });
    valorInicial = $('#frm_mod_ret_edi').find('#Rvt_Num').val();

}





function getDetalle(ret) {
    //console.log('estamos en el getDetalle');

    $.getDataJson("", { detalleRetenAjax: true }, function(detalle) {
        $('#detalleM').clearGrid();
        detalle.detRet.forEach(function(respuesta) {
            if (respuesta['Rvt_Cod'] === ret['Rvt_Cod']) {
                //console.log(respuesta);
                importeTotal = ((respuesta['Rvt_Bas'] * respuesta['Ren_Por']) / 100);
                var next = $("#detalleM").jqGrid('getCol', 'index', false, 'max');
                next = (isNaN(next) ? 1 : next + 1);
                $("#detalleM").jqGrid('addRowData', next, $.extend(respuesta, { index: next, Pld_Cod: respuesta['Pld_Cod'], Rvt_Cod: respuesta['Rvt_Cod'], Ren_Con: respuesta['Ren_Con'], Rvt_Bas_Mod: parseFloat(respuesta['Rvt_Bas']).toFixed(2), Ren_Por: respuesta['Ren_Por'], Total: parseFloat(importeTotal).toFixed(2) }), 'last');
                $('#documentoVistaER').updateGridsSizes();
                ////console.log(respuesta['Ren_Sri']);

                $("#detalleM").find('#' + next).val(respuesta['Ren_Sri']);
                $("#detalleM").find('#' + next + '_Rvt_Bas_Mod').val(respuesta['Rvt_Bas_Mod']);

                $("#detalleM").find('#' + next + '_total_val').val(parseFloat(importeTotal).toFixed(2));
                $('#detalleM').find('#' + next + '_Rvt_Bas_Mod').on('change', function() {
                    //console.log('entro en el onchange');
                    actualizarValor();
                    $('#btn_guardado').prop('disabled', false);
                }).trigger('change');
                importeTotal = 0;
                actualizarValor();
                $('#detalle').clearGrid();
            }
        });

    });



}


function dialogRetOpen(id) {
    $('#codiDialog').dialog('open');
    $('#RetCodAux').val(id);

}


function busquedaRetenciones(row) {
    //console.log(row);
    //console.log($('#Caj_Fec').val());
    $('#codiDialog').dialog('open');

}

function delFila2(row) {
    $("#detalleM").jqGrid('delRowData', row.id);
    actualizarValor();
}

function delFila(row) {
    //$('#detalle').delRowData(row.Index);
    //console.log('delFila');
    //console.log(row);
    $("#detalle").jqGrid('delRowData', row.id);
    updateRowItem();
    //$("#detalle").trigger("reloadGrid");
    /*$.saveDataJson("", { delHacienda: true, Prh_Cod: row.Prh_Cod }, function (responce) {
        $('#haciendas').changeRow(row.Prh_Cod, { Prh_Est: 'I', actDel: '' });
        $("#haciendas").trigger("reloadGrid");
        return false;
    });*/
}


function agregarFila(aux) {
    var campoGrid = '';
    $('#PecCod').val($("#Pec_Cod option:selected").attr("data--pec-cod"));
    if (aux > 0) {
        var $this = $('#detalleM');
        esMod = true;
        campoGrid = '_Rvt_Bas_Mod'
    } else {
        var $this = $('#detalle');
        esCrear = true;
        campoGrid = '_Rvt_Bas'
    }
    //var $this = $('#detalle');
    /*$('#t_retencion').val('0.00');
    var ids = $('#detalle').jqGrid('getDataIDs');
    //console.log(ids.length);
    $('#detalle').jqGrid('resizeGrid');
    var $this = $('#detalle'),
        id, nuevo;
    if (aux < 1 && ids <= 0) {
        id = ($this.jqGrid('getCol', 'id', false, 'max') + 1) || 0;
        nuevo = 'N';
    } else {
        id = 1 + ids.length;
        nuevo = 'A';
    }
    console.log(id);*/
    var id = $this.nextIndex();
    $this.jqGrid('addRowData', id, { 'index': id });
    $this.jqGrid('editRow', id);
    //updateRowItem();
    //console.log(campoGrid);
    //console.log(id);
    $this.find('tr#' + id).find('#' + id + campoGrid).on('change', function() {
        //console.log('entro en el change');
        //console.log(campoGrid);

        if (campoGrid === '_Rvt_Bas') { updateRowItem(); }
        if (campoGrid === '_Rvt_Bas_Mod') {
            actualizarValor();
            $('#btn_guardado').prop('disabled', false);
        }
    }).trigger('change');


    //$('#btn_agr').addClass('ui-state-disabled');

}
if ($('#factCompraDialog').length > 0)
    $.createSearchDialog('factCompraDialog', [
        { label: 'Cód. Int.', name: 'Cop_Cod', width: 20, align: "center", key: true, hidden: true },
        { label: 'Tipo Documento', name: 'Tic_Des', width: 100, hidden: true },
        { label: 'No. Documento', name: 'Cop_Num', width: 45, align: "center" },
        { label: 'Fecha', name: 'Cop_Fec', width: 25, align: "center", hidden: false },
        { label: 'Ruc/Cedula', name: 'Prs_Ced', width: 35, align: "center" },
        { label: 'Proveedor', name: 'proveedor', width: 75, align: "center" },
        { label: 'Importe', name: 'Importe', width: 20, hidden: true, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } },
        { label: 'Iva', name: 'Iva_Tot', width: 20, hidden: true, align: 'right', formatter: 'currency', /*formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '0.00' }*/ },
        { label: 'Total', name: 'Total', width: 25, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } },
        { label: 'Estado', name: 'Cop_Est', width: 15, align: "center", hidden: true, formatter: 'estado', title: false },
        { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selecinarFacturaCompra } }
    ], null, null, null, null, { title: 'Factura Compra', options: [{ label: '&nbsp;&nbsp;Proveedor&nbsp;&nbsp;', value: 'p' }, { label: '&nbsp;&nbsp;Nº&nbsp;Documento&nbsp;', value: 'n' }] });


function deleteDetalle() {
    //console.log('detalle');
}

function selecinarFacturaCompra(row) {
    //console.log(row);
    $('#Cop_Num_Fc').val(row['Cop_Num']);
    $('input:text[name=Asi_Cod_Fc]').val(row['Asi_Cod']);
    $('input:text[name=Asi_Deh_Fc]').val(row['Asi_Deh']);
    $('input:text[name=Com_Cod_Fc]').val(row['Com_Cod']);
    $('input:text[name=Ciu_Cod_Fc]').val(row['Ciu_Cod']);
    $('input:text[name=Cop_Cod_Fc]').val(row['Cop_Cod']);
    $('input:text[name=Pec_Cod_Fc]').val(row['Pec_Cod']);
    $('input:text[name=Pld_Cod_Fc]').val(row['Pld_Cod']);
    $('input:text[name=Prs_Cod_Fc]').val(row['Prs_Cod']);
    $('input:text[name=Prv_Cod_Fc]').val(row['Prv_Cod']);
    $('input:text[name=Tic_Cod_Fc]').val(row['Tic_Cod']);
    $('input:text[name=Tpc_Cod_Fc]').val(row['Tpc_Cod']);
    $('input:text[name=Tri_Cod_Fc]').val(row['Tri_Cod']);
    $('input:text[name=Vnd_Cod_Fc]').val(row['Vnd_Cod']);
    $('#factCompraDialog').dialog('close');
}

$.createSearchDialog('#clientDialog', [
    { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15, align: "center", hidden: true },
    { label: 'Cédula/RUC', name: 'Prs_Ced', width: 50 },
    { label: 'Cliente', name: 'Cliente', width: 180 },
    { label: 'Total', name: 'valorTotal', align: 'right', formatter: 'currency', width: 30 },
    //{ label: 'Direcc.', name: 'Prs_Dir', width: 60 },
    { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectCliente2 } }
], null, null, null, { headertitles: true }, { title: 'Cliente' });

if ($('#proveeDialog').length > 0) {
    $.createSearchDialog('#proveeDialog', [
        { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15, align: "center", hidden: true },
        { label: 'Cédula/RUC', name: 'Prs_Ced', width: 50 },
        { label: 'Cliente', name: 'Cliente', width: 180 },
        { name: 'Cli_Cod', width: 20, align: "center", hidden: true },
        //{ label: 'Total', name: 'valorTotal', align: 'right', formatter: 'currency', width: 30 },
        //{ label: 'Direcc.', name: 'Prs_Dir', width: 60 },
        { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectClientePuro } } //selectProv
    ], null, null, null, { headertitles: true }, { title: 'Cliente' });
}


if ($('#codiDialog').length > 0)
    $.createSearchDialog('codiDialog', [
        { label: 'Cód.Int.', name: 'Ren_Cod', key: true, width: 25, align: "center" },
        { label: 'Código', name: 'Ren_Sri', width: 25, align: "center" },
        { label: 'Descripción', name: 'Ren_Con', width: 100 },
        { label: 'Porc.(%)', name: 'Ren_Por', width: 25, align: "center" },
        { label: 'Adq.', name: 'Ren_Tipo', width: 30, align: "center" },
        { label: 'Tipo', name: 'Ren_Rete', width: 30, align: "center" },
        {
            label: '&nbsp;',
            name: 'act1',
            width: 20,
            align: 'center',
            viewable: false,
            formatter: 'gridButton',
            formatoptions: {
                action: agregaRetencion,
                conditional: function(o) {
                    return !(Cof_Con === 'S' && (!$.varValid(o['Pld_Cod']) || o['Pld_Cod'] === ''));
                },
                caseFalse: function() { return '<i class="glyphicon glyphicon-lock orange" title="No se ha Parametrizado una Cuenta Contable!"></i>'; }
            }
        }
    ], null, null, null, null, {
        title: 'Búsqueda',
        options: [{ label: '&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;', value: 'd' },
            { label: '&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;', value: 'c' }
        ]
    });

function selectCliente2(cliente) {
    //console.log(cliente);
    $('#retFormTemp2').setData($.extend(cliente, { op_opciones: 'c' }));
    $('input:text[name=Prs_Ced]').val(cliente['Prs_Ced']);
    $('#clientDialog').dialog('close');
}

function selectProv(prov) {
    //console.log(prov);
    $('#retFormTemp2').setData($.extend(prov, { op_opciones: 'c' }));
    $('input:text[name=Prs_Ced]').val(prov['Prs_Ced']);
    //console.log('cerrar');
    $('#proveeDialog').dialog('close');
}

function selectClientePuro(cp) {
    $('#retFormTemp2').setData($.extend(cp, { op_opciones: 'c' }));
    $('input:text[name=Prs_Ced]').val(cp['Prs_Ced']);
    //console.log('cerrar');
    $('#proveeDialog').dialog('close');
    //retencionesExistentes();
}

$('#Pec_Cod').on('change', function() {
    //console.log('change del peirodo');
    var sel_fecha = $(this).find('option:selected');
    fechas(sel_fecha.data('inicio'), sel_fecha.data('fin'), sel_fecha.data('placod'));
    $('#Caj_Fec').trigger('change');
    //console.log(sel_fecha.data('inicio') + '*' + sel_fecha.data('fin') + '*' + sel_fecha.data('placod'));
    //$('#retFormTemp').find('[name=fecha_inicio]').val($(this).find('option:selected').data('inicio'));
    //$('#retFormTemp').find('[name=fecha_fin]').val($(this).find('option:selected').data('fin')); /*$('input[name=order]').val($(this).val());*/
});


$('#Bak_Cod').on('change', function() {
    var sel_bank = $(this).find('option:selected');
    var Pld_Cod_Bank = sel_bank.val();
    //console.log(Pld_Cod_Bank);
    $('#BakCodAux').val(Pld_Cod_Bank);

}).trigger('change');





//Funcion para setear el datepicker al periodo seleccionado
function fechas(inicio, fin, placod) {
    $('#Caj_Fec').dateLimits(inicio, fin);
    //Rvt_Fec
}


function agregaRetencion(dato) {
    //console.log("agregarRetencion");
    //console.log(data);
    //console.log(esMod);
    //console.log(esCrear);
    //frm_mod_ret_edi , frm_ret_ban
    if (esMod && !esCrear) {
        esMod = false;
        gridName = 'detalleM';
        formName = 'frm_mod_ret_edi';
    }
    if (!esMod && esCrear) {
        esCrear = false;
        gridName = 'detalle';
        formName = 'frm_ret_ban';
    }
    //console.log(gridName);
    var resultado = 0;
    var verifica = false;
    var ids = $('#' + gridName).jqGrid('getDataIDs');
    var datose = $('#' + gridName).jqGrid('getRowData');
    //$('#detalle').setRow(data);
    //console.log(ids.length);
    //console.log(datose.length);
    //if (ids.length > 1) {
    var data = { 'items': $('#' + formName).find('#' + gridName).getGridBatch() };
    /*console.log(data['items']);
    console.log(data['items']['Ren_Cod']);
    console.log(data['Ren_Cod']);*/

    var change = true;
    var elim = false;
    for (var i = 0; i < data['items'].length; i++) {
        if (data['items'][i]['Ren_Cod'] == dato['Ren_Cod']) {
            $.alert('La retención seleccionada ya existe en la lista!<br/>Revise los datos.', null, 'remove');
            change = false;
            elim = true;
            $('#codiDialog').dialog('close');
            break;
        }


        /*console.log(data['items'][i]['Ren_Cod']);
        console.log(data['items'][i]['index']);
        if (data['items'][i]['Ren_Cod'] === data['Ren_Cod']) {
            $.alert('La retención seleccionada ya existe en la lista!<br/>Revise los datos.', null, 'remove');
            break;
        } else {
            console.log(data['items'][i]['index']);
            console.log(data['index']);
            console.log(data);
            var datosa = $('#detalle').jqGrid('getRowData', ids[i]);
            //var columna = $('#detalle').jqGrid('getCell', data['items'][i]['index'], 'index');
            //$('#btn_agr').removeClass('ui-state-disabled');
            console.log(datosa);
            if (data['items'][i]['Ren_Cod'] === '' && data['items'][i]['index'] === datosa['index']) { console.log('hola lola'); $('#detalle').changeRow(data['items'][i]['index'], data); $('#detalle').find('tr#' + data['items'][i]['index']).setData(data, false); }
        }*/
    }
    //$('#detalle').find('tr#' + data['items'][i]['index']).setData(data, false);
    if (elim) {
        //console.log('entrando en el eliminar');
        $('#' + formName).find("#" + gridName).jqGrid('delRowData', $('#RetCodAux').val());
        updateRowItem();
    }
    if (change) {
        //console.log(dato);
        //console.log($('#RetCodAux').val());
        var valorId = $('#RetCodAux').val();
        $('#' + formName).find('#' + gridName).find('#' + valorId + '_total_val').val('0');
        $('#' + formName).find('#' + gridName).changeRow($('#RetCodAux').val(), dato);
        $('#' + formName).find('#' + gridName).find('tr#' + valorId).setData(dato, false);
        //$('#' + valorId).setData($.toFixed(dato['Ren_Sri']));
        //$('#' + valorId).val(dato['Ren_Sri']);
        //$('#detalle').find('tr#' + $('#RetCodAux').val()).setData(dato['Ren_Sri'], false);
        //$('#detalle').trigger('reloadGrid');
        /*$('input:text[name=Ren_Bas]').on('change', function () {
            updateRowItem();
        });*/
        $('#codiDialog').dialog('close');
        if (gridName === 'detalleM') {
            $('#' + formName).find('#' + gridName).find('#' + valorId + '_Rvt_Bas_Mod').on('change', function() {
                //console.log('entro en el onchange');
                actualizarValor();
                $('#btn_guardado').prop('disabled', false);
            }).trigger('change');
        }

        /*$('input:text[name=Rvt_Bas]').on('change', function () {
            updateRowItem();
        });*/
    }
    /*} else {
        $('#detalle').changeRow(ids[0], data);
        $('#detalle').find('tr#' + ids[0]).setData(data, false);

    }*/
}

function guardarModificacion(formulario, accion) {
    $.createDialogConfirm(`¿Est&aacute; seguro que desea guardar los datos de la retetención?`, null, function() {
        //console.log(formulario);
        //console.log(accion);
        //console.log("*** JS SAVE DATOS MODIFICAR***");
        var data = $('#' + formulario).getData('saveDocumento');
        var fecMod = data['Rvt_Fec'].split('-');
        //console.log(data);

        if (fecMod['1'] !== mesRetVenta['1']) { data['verificador'] = true; } else { data['verificador'] = false; }
        //console.log(data);
        data['retenciones'] = $('#detalleM').getGridBatch();
        $.each(data['retenciones'], function(pos, valor) {
            if (valor['Ren_Cod'] === '' || valor['Ren_Con'] === '' || valor['Ren_Por'] === '' || valor['Ren_Sri'] === '' || valor['Rvt_Bas_Mod'] === '') {
                index = $("#detalle").jqGrid('getInd', valor['index']);
                $.alert('Debe completar información en la fila: ' + index);
                $('#detalle').startGridEdit();
                return false;
            }
        });
        if ((data['retenciones'].length) < 1) { $.alert('Debe existir al menos un registro en detalle retenciones..!!'); return false; }
        if (data['Rvt_Tem'] === undefined) { $.alert('Debe elegir un tipo de emisión..!!'); return false; }
        data[accion] = true;
        $.saveDataJson('', data, function(resp) {
            if (resp['success']) {
                $('#detalleM').clearGrid();
                $('#frm_mod_ret_edi')[0].reset();
                //frm_mod_ret_edi
                $('#tableRetenciones').trigger("reloadGrid");
                $('#documentoVistaER').moveComp('#tab2').updateGridsSizes();
                $.alert('La transacci&oacute;n se realizo con exito.');
                return false;
            }
        });
    });
}

function saveRetBancaria(formulario, accion) {
    $.createDialogConfirm(`¿Est&aacute; seguro que desea guardar los datos de la retetención?`, null, function() {
        var index;
        var shortDateFormat = 'yy-mm-dd';
        var data = $('#' + formulario).getData('saveDocumento');
        data['retenciones'] = $('#detalle').getGridBatch();
        if (detPlanCuenta.length === 0 && Cof_Con === 'S') { $.alert('No existe cuenta parametrizada...!'); return false; }
        if (data['Cli_Cod'] === '' || data['Prs_Cod'] === '') { $.alert('Debe seleccionar un cliente..!! '); return false; }
        if (data['Cop_Num_Fc'] === '') { $.alert('Debe seleccionar una Factura Compra. '); return false; }
        //if (data['Rvt_Doc'].length > 17) { $.alert('Verifique numero de factura. '); return false; }
        $.each(data['retenciones'], function(pos, valor) {
            if (valor['Ren_Cod'] === '' || valor['Ren_Con'] === '' || valor['Ren_Por'] === '' || valor['Ren_Sri'] === '' || valor['Rvt_Bas'] === '') {
                index = $("#detalle").jqGrid('getInd', valor['index']);
                $.alert('Debe completar información en la fila: ' + index);
                $('#detalle').startGridEdit();
                return false;
            }
        });
        if (index * 1 > 0) return false;
        //$('input:text[name=total_val]').val(row['total_val']);
        if ((data['retenciones'].length) < 1) { $.alert('Debe existir al menos un registro en detalle retenciones..!!'); return false; }
        if (data['Rvt_Tem'] === undefined) { $.alert('Debe elegir un tipo de emisión..!!'); return false; }
        data[accion] = true;

        // Guarda los valores actuales de Pec_Cod y Pld_Cod antes de limpiar el formulario
        const prevPecCod = $('#Pec_Cod').val();
        const prevPldCod = $('#Pld_Cod').val();
        const prevTpcCod = $('#Tpc_Cod').val();

        $.saveDataJson('', data, function(resp) {
            if (resp['success']) {
                $('#detalle').clearGrid();
                $('#frm_ret_ban').setData({});

                // Restaurar los valores de Pec_Cod y Pld_Cod después de limpiar
                $('#Pec_Cod').val(prevPecCod);
                $('#Pld_Cod').val(prevPldCod);
                $('#Tpc_Cod').val(prevTpcCod);
                
                $('#Pld_Cdc').val(combinaParametro());
                $('input:text[name=Caj_Fec]').val($.datepicker.formatDate(shortDateFormat, new Date()));
                $('#Tic_Cod').val('1');
                $.alert('La transacci&oacute;n se realizo con exito.');
                /*  $.createDialogConfirm('¿Desea imprimir comprobante?', null, function () {
                     console.log('lola');

                 }); */
                return false;
            }

        });

    });


}

function confirmaGuardado() {
    $.createDialogConfirm(`¿Est&aacute; seguro que desea guardar los datos de la retetención?`, null, saveRetBancaria);
}


function actualizarValor() {
    let ids = $('#detalleM').jqGrid('getDataIDs');
    let datos = $('#detalleM').jqGrid('getRowData');
    for (var i = 0; i < datos.length; i++) {
        var columna = $('#detalleM').jqGrid('getCell', ids[i], 'index');
        var valorId = datos[i]['index'];
        if (datos[i]['Rvt_Bas_Mod'] != '') {
            datos['total_val'] = datos[i]['Rvt_Bas_Mod'] * (0 + datos[i]['Ren_Por']) * 1;
            datos['total_val'] = datos['total_val'] / 100;
            $('#' + valorId + '_total_val').val(parseFloat(datos['total_val']).toFixed(2));
        }
    }
    let ret_tot = $('#detalleM').jqGrid('getCol', 'total_val', false, 'sum');
    //console.log(ret_tot);
    $('#t_retencion_m').val(parseFloat(ret_tot).toFixed(2));
}

//Actualiza los valores de la fila
function updateRowItem() {
    //console.log(data);
    //console.log("updateRowItem");
    var ids = $('#detalle').jqGrid('getDataIDs');
    var datos = $('#detalle').jqGrid('getRowData');
    //console.log(datos.length);
    //console.log(datos);
    for (var i = 0; i < datos.length; i++) {
        //console.log('updateRowItem');
        //console.log(datos[i]);
        var columna = $('#detalle').jqGrid('getCell', ids[i], 'index');
        //console.log(columna);
        var valorId = datos[i]['index'];
        //console.log(datos[i]['index']);
        if (datos[i]['Rvt_Bas'] != '') {
            datos['total_val'] = datos[i]['Rvt_Bas'] * (0 + datos[i]['Ren_Por']) * 1;
            datos['total_val'] = datos['total_val'] / 100;
            $('#' + valorId + '_total_val').val(parseFloat(datos['total_val']).toFixed(2));
            //$('#btn_agr').removeClass('ui-state-disabled');
        }



    }
    /*
    //console.log(obj);
    var datosa = $('#detalle').jqGrid('getRowData', ids[0]);
    console.log(datosa);
    var datosb = $('#detalle').find('tr#' + ids[0]).getDataForced();
    var row = $.extend({}, datosa, datosb);
    row['total_val'] = row['Rvt_Bas'] * (0 + row['Ren_Por']) * 1;
    row['total_val'] = row['total_val'] / 100;
    $('input:text[name=total_val]').val(row['total_val']);

    console.log(ids);
    console.log(row);
    //console.log(obj);


    //$('#detalle').changeRow(ids['total_val'], row);

    //$('#detalle').trigger('reloadGrid');*/
    var ret_tot = $('#detalle').jqGrid('getCol', 'total_val', false, 'sum');
    //console.log(ret_tot);
    $('#t_retencion').val(parseFloat(ret_tot).toFixed(2));
}

function calculoTotal(row) {
    var datos = $('#detalle').getGridBatch();
    var ids = $('#detalle').jqGrid('getDataIDs');
    //console.log('lola lolita holis', datos, datos[0]['Rvt_Bas'], datos[0]['Ren_Por']);
    resultado = (parseFloat((datos[0]['Rvt_Bas'])) * parseFloat((datos[0]['Ren_Por'])) / 100);
    $('input:text[name=total_val]').val(resultado);
    //console.log(resultado);

}




//Estilo para cantidad
function styleCant(e, obj, opt) {
    e.style.textAlign = 'right';
    e.placeholder = '0';
    $(e).on('keyup', function() {
        if (isNaN(this.value)) { $(this).val('1').focus(); } else if (this.value % 1 !== 0) { var dec = String(this.value).split('.'); if (typeof dec[1] !== 'undefined' && dec[1].length > 5) this.value = $.toFixed(this.value, 5); }
        updateRowItem(obj);
    });
}



//Estilo para precio unitario
function stylePru(e, obj, opt) {

    e.style.textAlign = 'right';
    e.placeholder = '0.00';
    $(e).on('keyup', function() {
        if (isNaN(this.value) /*||this.value===''||(!isNaN(this.value)&&this.value*1===0)*/ ) { $(this).val('').focus(); } else if (this.value % 1 !== 0) { var dec = String(this.value).split('.'); if (typeof dec[1] !== 'undefined' && dec[1].length > 8) this.value = $.toFixed(this.value, 8); }
        updateRowItem(obj);
    });
}

function verficaRetencion() {
    //retencionesExistentes();
    //console.log('estamos en el verifica retencion');
    var valorInput = $('#Rvt_Num').val();
    var validado = true;
    if ((valorInput) !== '') {
        if (arrayRetenciones.length <= 0) {
            $('#Rvt_Num').fieldValid(true);
            validado = true;
        } else {
            for (var i = 0; i < arrayRetenciones.length; i++) {
                //console.log(arrayRetenciones[i]['Rvt_Num']);
                if (arrayRetenciones[i]['Rvt_Num'] === valorInput) {
                    $('#Rvt_Num').fieldValid(false, 'El numero de retencion ' + valorInput + ' ya se encuentra registrado');
                    validado = false;
                    arrayRetenciones.length = 0;
                    $('#Rvt_Num').val('');
                } else {
                    $('#Rvt_Num').fieldValid(true);
                    validado = true;
                }
            }
        }


    } else {
        $('#Rvt_Num').fieldValid(false, 'Escriba un valor correcto');
        validado = false;

    }
    return validado;

}

function retencionesExistentes() {
    var codigoCliente = $('#Cli_Cod').val();
    var numeroRetencion = $('#Rvt_Num').val();
    //console.log(Cli_Cod);
    arrayRetenciones.length = 0;

    $.getDataJson('', { buscarRetencionesTodas: true, Cli_Cod: codigoCliente, Rvt_Num: numeroRetencion }, function(resp) {
        resp.retLista.forEach(function(resultado) {
            //console.log(resultado);
            arrayRetenciones.push(resultado);
        });
        verficaRetencion();
    });
}

function verificarTamanho() {
    //console.log('En el onchange');
    var codCliente = $('#frm_mod_ret_edi').find('#Cli_Cod').val();
    var numRetencion = $('#frm_mod_ret_edi').find('#Rvt_Num').val();
    //console.log(codCliente);
    arrayModRetenciones.length = 0;
    //console.log(numRetencion.split('_').length);
    if (numRetencion.split('_').length > 1) {
        verficaModRetencion();
    } else {
        $.getDataJson('', { buscarRetencionesTodas: true, Cli_Cod: codCliente, Rvt_Num: numRetencion }, function(resp) {
            resp.retLista.forEach(function(resultado) {
                //console.log(resultado);
                arrayModRetenciones.push(resultado);
            });
            verficaModRetencion();
        });

    }

}

function verficaModRetencion() {
    //console.log(arrayModRetenciones.length);
    var valueInput = $('#frm_mod_ret_edi').find('#Rvt_Num').val();
    //console.log(valueInput.split('_').length);
    var validado = true;
    if (valueInput !== '' && valueInput.split('_').length <= 3) {
        if (arrayModRetenciones.length <= 0) {
            $('#frm_mod_ret_edi').find('#Rvt_Num').fieldValid(true);
            validado = true;
        } else {
            if (arrayModRetenciones[0].Rvt_Num === valorInicial) {
                $('#frm_mod_ret_edi').find('#Rvt_Num').fieldValid(true);
                validado = true;
            } else {
                $('#frm_mod_ret_edi').find('#Rvt_Num').fieldValid(false, 'El numero de retencion ' + valueInput + ' ya se encuentra registrado');
                validado = false;
                arrayModRetenciones.length = 0;
            }
            /* console.log(arrayModRetenciones[0].Rvt_Num);
            console.log('entro por aqaui');
            console.log(valorInicial);
            $('#frm_mod_ret_edi').find('#Rvt_Num').fieldValid(false, 'El numero de retencion ' + valueInput + ' ya se encuentra registrado');
            validado = false;
            arrayModRetenciones.length = 0; */
            //$('#frm_mod_ret_edi').find('#Rvt_Num').val('');
        }
    } else {
        $('#frm_mod_ret_edi').find('#Rvt_Num').fieldValid(false, "Escriba un valor correcto");
        validado = false;
    }
    return validado;
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