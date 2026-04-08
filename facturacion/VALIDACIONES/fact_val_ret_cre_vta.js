
$(function () {

    //$('#retencionesDialog').createDialog({ height: 270, width: 475 });

    $('.datepickers').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });
    var sel_fecha = $("#Pec_Cod").find('option:selected');
    //console.log(sel_fecha);
    $('#Caj_Fec').dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'));
    $('#Rvt_Doc').unmask().mask("999-999-999999999", { placeholder: "_" });
    $('#formFinal').find('.form-group-options').append('<div class="col-md-4"> <label class="control-label label-xs">Plan de Cuentas:&nbsp;</label><input id="Index" name="Index" type="hidden" value="" /><input data-name="Pec_Cod" id="Pec_Cod" name="Pec_Cod" type="hidden" /><input data-name="Year" name="Periodo" type="text" size="6" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /></div>');
    //$('#formFinal').find('.form-group-options').append('<div class="col-md-4"> <label class="control-label label-xs">Tipo de Compra&nbsp;</label><input id="Index" name="Index" type="hidden" value="" /><input data-name="Tic_Cod" name="Tic_Cod" type="hidden" /><input data-name="Tic_Des" name="Tic_Des" type="text" size="6" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /></div>');
    $('#detalle').createGrid({
        caption: 'Detalle Retención Bancaria',
        height: 270,
        datatype: "local",
        colModel: [
            { label: 'C&oacute;d. Int.', name: 'Rvt_Cod', hidden: true, width: 30, align: "center" },
            { name: 'index', label: 'Index', width: 20, sorttype: 'int', align: 'center', hidden: true },
            { name: 'Ren_Cod', key: true, width: 20, align: "center", hidden: true },
            { label: 'C&oacute;d. Retenci&oacuten.', name: 'Ren_Sri', width: 15, align: "left", title: true, formatter: 'input2', formatoptions: { id: '1', title: 'Agregar Retención', action: "dialogRetOpen", data: function (o) { return o; } }, resizable: false },
            { label: 'Impuesto.', name: 'Ren_Con', width: 80, align: "center" },
            { label: 'Base Imp.', name: 'Rvt_Bas', width: 15, align: "center", formatter: 'input2', formatoptions: { id: '2', attr: '' } },
            { label: 'Porcentaje', name: 'Ren_Por', width: 15, align: "center" },
            { label: 'Importe', name: 'total_val', width: 15, align: "center", formatter: 'input3', formatoptions: { id: '2', attr: '' } },
            { name: 'delete', label: '<i class="glyphicon glyphicon-remove"></i>', width: 10, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: delFila, /*conditional: function(o) { console.log(o); return o.tarja === 'n' && o.Prh_Est !== 'I'; },*/ icon: 'remove', type: 'danger', title: 'Eliminar Item', data: function (o) { return o; } }, resizable: false }
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
        onClickButton: function () { agregarFila(0); }
    }]);

    $("#detalle").getFootRow(true);
    $("#detalle").jqGrid('footerData', 'set', {
        Ren_Por: '<div class="footerFact formDatos" class="formDatos"><label class="total">TOTAL:</label></div>',
        total_val: '<div class="footerFact formDatos" id="formTotales"><input id="t_retencion" name="t_retencion" type="text"  class="total" readonly/></div>'
    }, false);
    $.fn.fmatter.input2 = function (cv, opts, cObjt) {
        var set = opts['colModel']['formatoptions'],
            el;
        //console.log(opts);
        if (set['id'] === '1') { el = $('<div class="input-group input-group-xs ret"><input type="text" id="' + opts['rowId'] + '" name="' + opts['colModel']['name'] + '" class="form-control input-xs ' + set['class'] + '"/><span class="input-group-btn"><button class="btn btn-info" type="button" title="' + set['title'] + '" onclick="' + set['action'] + '(' + opts['rowId'] + ')"><span class="glyphicon glyphicon-search"></span></button></span></div>'); } else { el = $('<input type="text" id="' + opts['rowId'] + '_' + opts['colModel']['name'] + '" name="' + opts['colModel']['name'] + '" class="form-control input-xs" ' + set['attr'] + '/>'); }
        return el.prop('outerHTML');
    };
    $.fn.fmatter.input2.unformat = function (cv, opts, cObjt) { return $(cObjt).find(':input').val(); };
    $.fn.fmatter.input3 = function (cv, opts, cObjt) {
        var set = opts['colModel']['formatoptions'],
            el;
        //console.log(opts);
        if (set['id'] === '1') { el = $('<div class="input-group input-group-xs ret"><input type="text" id="' + opts['rowId'] + '" class="form-control input-xs ' + set['class'] + '"/><span class="input-group-btn"><button class="btn btn-info" type="button" title="' + set['title'] + '" onclick="' + set['action'] + '(' + opts['rowId'] + ')"><span class="glyphicon glyphicon-search"></span></button></span></div>'); } else { el = $('<input type="text"   style="text-align: right;" id="' + opts['rowId'] + '_' + opts['colModel']['name'] + '" name="' + opts['colModel']['name'] + '" class="form-control input-xs" ' + set['attr'] + 'readonly />'); }
        return el.prop('outerHTML');
    };
    $.fn.fmatter.input3.unformat = function (cv, opts, cObjt) { return $(cObjt).find(':input').val(); };


});

function dialogRetOpen(id) {
    $('#codiDialog').dialog('open');
    $('#RetCodAux').val(id);

}
function busquedaRetenciones(row) {
    //console.log(row);
    //console.log($('#Caj_Fec').val());
    $('#codiDialog').dialog('open');

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
    var $this = $('#detalle');
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
    $this.find('tr#' + id).find('#' + id + '_Rvt_Bas').on('change', function () {
        updateRowItem();
    }).trigger('change');
    //$('#btn_agr').addClass('ui-state-disabled');

}

function deleteDetalle() {
    console.log('detalle');
}

$.createSearchDialog('#clientDialog', [
    { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15, align: "center", hidden: true },
    { label: 'Cédula/RUC', name: 'Prs_Ced', width: 50 },
    { label: 'Cliente', name: 'Cliente', width: 100 },
    //{ label: 'Apellido', name: 'Prs_Ape', width: 100 },
    { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
    { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectCliente2 } }
], null, null, null, { headertitles: true }, { title: 'Cliente' });

if ($('#codiDialog').length > 0)
    $.createSearchDialog('codiDialog', [
        { label: 'Cód.Int.', name: 'Ren_Cod', key: true, width: 25, align: "center" },
        { label: 'Código', name: 'Ren_Sri', width: 25, align: "center" },
        { label: 'Descripción', name: 'Ren_Con', width: 100 },
        { label: 'Porc.(%)', name: 'Ren_Por', width: 25, align: "center" },
        { label: 'Adq.', name: 'Ren_Tipo', width: 30, align: "center" },
        { label: 'Tipo', name: 'Ren_Rete', width: 30, align: "center" },
        {
            label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton',
            formatoptions: {
                action: agregaRetencion,
                conditional: function (o) {
                    return !(Cof_Con === 'S' && (!$.varValid(o['Pld_Cod']) || o['Pld_Cod'] === ''));
                }, caseFalse: function () { return '<i class="glyphicon glyphicon-lock orange" title="No se ha Parametrizado una Cuenta Contable!"></i>'; }
            }
        }
    ], null, null, null, null, { title: 'Búsqueda', options: [] });

function selectCliente2(cliente) {
    //console.log(cliente);
    $('#retFormTemp2').setData($.extend(cliente, { op_opciones: 'c' }));
    $('input:text[name=Prs_Ced]').val(cliente['Prs_Ced']);
    $('#clientDialog').dialog('close');
}


function agregaRetencion(dato) {
    //console.log("agregarRetencion");
    //console.log(data);
    var resultado = 0;
    var verifica = false;
    var ids = $('#detalle').jqGrid('getDataIDs');
    var datose = $('#detalle').jqGrid('getRowData');
    //$('#detalle').setRow(data);
    //console.log(ids.length);
    //console.log(datose.length);
    //if (ids.length > 1) {
    var data = { 'items': $('#detalle').getGridBatch() };
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
        $("#detalle").jqGrid('delRowData', $('#RetCodAux').val());
        updateRowItem();
    }
    if (change) {
        //console.log(dato);
        //console.log($('#RetCodAux').val());
        var valorId = $('#RetCodAux').val();
        $('#detalle').changeRow($('#RetCodAux').val(), dato);
        $('#detalle').find('tr#' + valorId).setData(dato, false);
        //$('#' + valorId).setData($.toFixed(dato['Ren_Sri']));
        //$('#' + valorId).val(dato['Ren_Sri']);
        //$('#detalle').find('tr#' + $('#RetCodAux').val()).setData(dato['Ren_Sri'], false);
        //$('#detalle').trigger('reloadGrid');
        /*$('input:text[name=Ren_Bas]').on('change', function () {
            updateRowItem();
        });*/
        $('#codiDialog').dialog('close');
        /*$('input:text[name=Rvt_Bas]').on('change', function () {
            updateRowItem();
        });*/
    }
    /*} else {
        $('#detalle').changeRow(ids[0], data);
        $('#detalle').find('tr#' + ids[0]).setData(data, false);

    }*/



}
function saveRetBancaria() {
    var index;
    var shortDateFormat = 'yy-mm-dd';
    var data = $('#frm_ret_ban').getData('saveDocumento');
    data['retenciones'] = $('#detalle').getGridBatch();
    if (data['Cli_Cod'] === '' || data['Prs_Cod'] === '') { $.alert('Debe seleccionar un cliente..!! '); return false; }
    if (data['Rvt_Doc'].length > 17) { $.alert('Verifique numero de factura. '); return false; }
    $.each(data['retenciones'], function (pos, valor) {
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
    $.saveDataJson('', data, function (resp) {
        if (resp['success']) {
            $('#detalle').clearGrid();
            $('#frm_ret_ban').setData({});
            $('input:text[name=Caj_Fec]').val($.datepicker.formatDate(shortDateFormat, new Date()));
            $('#Tic_Cod').val('1');
            return false;
        }

    });

}

function confirmaGuardado() {
    $.createDialogConfirm(`¿Est&aacute; seguro que desea guardar los datos de la retetención?`, null, saveRetBancaria);
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
    $(e).on('keyup', function () {
        if (isNaN(this.value)) { $(this).val('1').focus(); } else if (this.value % 1 !== 0) { var dec = String(this.value).split('.'); if (typeof dec[1] !== 'undefined' && dec[1].length > 5) this.value = $.toFixed(this.value, 5); }
        updateRowItem(obj);
    });
}



//Estilo para precio unitario
function stylePru(e, obj, opt) {

    e.style.textAlign = 'right';
    e.placeholder = '0.00';
    $(e).on('keyup', function () {
        if (isNaN(this.value) /*||this.value===''||(!isNaN(this.value)&&this.value*1===0)*/) { $(this).val('').focus(); } else if (this.value % 1 !== 0) { var dec = String(this.value).split('.'); if (typeof dec[1] !== 'undefined' && dec[1].length > 8) this.value = $.toFixed(this.value, 8); }
        updateRowItem(obj);
    });
}

/*$("#Rvt_Doc").on('keyup', function () {
    var thisVal = $(this).val();
    console.log(thisVal);
    if (thisVal.length == 3 || thisVal.length == 7) {
        $(this).val(thisVal + '-');
    }
    if (thisVal.length === 18) {
        $(this).val(thisVal.substring(0, 17));
    }

});*/