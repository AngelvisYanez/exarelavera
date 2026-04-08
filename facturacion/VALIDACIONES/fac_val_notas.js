/* OBJETOS JQUERY */
$(function () {
    gridFact = $("#documento");
    gridFact.createGrid({
        data: [], rowNum: 10000000, height: 'auto', footerrow: true, headertitles: true, selectGridRows: false,
        caption: "Detalle Documento <div class='pull-right formDatos'><span>Afecta Inventario:&nbsp;</span><input id='Cal_Inv' name='Cal_Inv' type='checkbox' value='S' offval='N' />&nbsp;</div>",
        colModel: [
            { name: 'select', label: '<i class="glyphicon glyphicon-check"></i>', width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: openItemSelector, icon: 'check', title: 'Seleccionar Item', data: function (o) { return o.index; } }, resizable: false },
            { name: 'index', label: 'Index', width: 20, sorttype: "int", align: 'center', key: true, hidden: true },
            { name: 'Pro_Cod', label: 'Cód.Int.', width: 20, sorttype: "int", align: 'center', hidden: true },
            { name: 'Cop_Can', label: 'Cant.', labelLong: 'Cantidad', width: 40, align: "right", title: false, editable: true, editoptions: { dataInit: styleCant } },
            { name: 'Uni_Des', label: 'Uni.', labelLong: 'Unidad', width: 25, resizable: false },
            { name: 'Ite_Lar', label: 'Descripción', width: 150 },
            { name: 'selectCta', label: '<i class="glyphicon glyphicon-check"></i>', width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: 'openCtaSelector', icon: 'check', type: 'brown', title: 'Cambiar Cuenta', data: function (o) { return o.index; }, conditional: function (o) { return !$.isEmpty(o.Pro_Cod); } }, resizable: false }, // nueva columna
            { name: 'Pld_Cod', label: 'Pld_Cod', width: 20, hidden: true },
            { name: 'Pld_Cdc', label: 'Cuenta', width: 50, align: "center", formatter: 'title', formatoptions: { title: function (o) { return o['Pld_Cdc'] + ' - ' + o['Pld_Des']; } }, title: false },
            { name: 'Pld_Des', label: 'Pld_Des', width: 20, hidden: true },
            { name: 'Cop_Dec', label: 'Descuen.', labelLong: 'Descuento', align: "right", width: 20, title: false },
            { name: 'Cop_Pru', label: 'P. Unitario', labelLong: 'Precio Unitario', width: 60, align: "right", title: false/*, summaryRound: 8,formatter:"currency",formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.',decimalPlaces: 8, defaultValue: ''}*/, editable: true, editoptions: { dataInit: stylePru } },
            { name: 'Cop_Imp', label: 'Importe', width: 70, align: "right", summaryRound: 2, formatter: "currency", formatoptions: { prefix: '', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '0.00' }, classes: 'columnHighlight1' },
            { name: 'Iva_Cod', label: 'CodIva', width: 20, hidden: true },
            // {name:'Iva_Por',label:'IVA', width:15,align:"center", formatter:'truefalse', formatoptions:{yesMsg:'Grava IVA',noMsg:'No Grava IVA'}, resizable: false },
            //Nuevo campo
            { name: 'Iva_Por', label: 'IVA(%)', width: 15, align: "center", resizable: false },
            { name: 'Iva_Cos', label: 'Cos.', labelLong: 'IVA al Costo', width: 20, align: "center", hidden: true, resizable: false },
            { name: 'Ice_Int', label: 'CodIce', width: 20, hidden: true },
            { name: 'Ice_Por', label: 'ICE', width: 20, align: "right", title: false, resizable: false },
            { name: 'Adq_Cod', label: 'CodAdq', width: 20, hidden: true },
            { name: 'Adq_Cor', label: 'Adq.', labelLong: 'Adquisiciones', width: 20, align: "center", title: false, formatter: 'title', formatoptions: { title: function (o) { return o['Adq_Des']; } }, resizable: false },
            { name: 'delete', label: '<i class="glyphicon glyphicon-remove"></i>', width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: deleteItem, icon: 'remove', title: 'Eliminar Item', type: 'danger', data: function (o) { return o.index; }, attr: { 'tabindex': '-1' }, conditional: function (o) { return !(!$.varValid(o['Pro_Cod']) || o['Pro_Cod'] === ''); } }, resizable: false }
        ]
    }, true, 'documentoPager', { view: false }).gridButtonsAdd([
        { caption: 'Agregar Productos', buttonicon: 'glyphicon glyphicon-plus', onClickButton: function () { index = 0; $('#proDialog').dialog('open'); } },
        { caption: 'Remover Todos', buttonicon: 'glyphicon glyphicon-remove', onClickButton: function () { gridFact.clearGrid(); addItem({}); } }
    ]);
    gridFact.getFootRow(true);
    gridFact.jqGrid('footerData', 'set', {
        Cop_Can: '<div class="footerFact"><label>Observación:</label></div>',
        Ite_Lar: '<div class="footerFact formDatos" class="formDatos"><textarea name="Cop_Obs" tabindex="15" class="text"></textarea></div>',
        /* Cop_Pru:'<div class="footerFact"><label>SUBTOTAL:</label><label>TARIFA 0%:</label><label>TARIFA <span class="iva_por"></span>%:</label><label><span class="iva_por"></span>% IVA:</label><label>ICE:</label><label>DESCUENTO:</label><label class="total">TOTAL:</label></div>',
        Cop_Imp:'<div class="footerFact formDatos" id="formTotales"><input name="t_subtotal" type="text" readonly/><input name="t_iva0" type="text" readonly/><input name="t_iva12" type="text" readonly/><input name="t_iva" type="text" readonly/><input name="t_ice" type="text" readonly/><input name="t_descuento" type="text" readonly/><input name="t_rubros" type="text"  class="total" readonly/></div>',
        */
        Cop_Pru: '<div class="footerFact">' +
            '<label>SUBTOTAL:</label><label>TARIFA 0%:</label>' +
            '<label>TARIFA <span class="iva_por_5">5%</span>:</label> ' +
            '<label>TARIFA <span class="iva_por"></span>%:</label>' +
            '<label><span class="iva_por"></span>% IVA:</label><label>ICE:</label><label>DESCUENTO:</label>' +
            '<label class="total">TOTAL:</label></div>',
        Cop_Imp: '<div class="footerFact formDatos" id="formTotales">' +
            '<input name="t_subtotal" type="text" readonly/>' +
            '<input name="t_iva0" type="text" readonly/>' +
            '<input name="t_iva5" type="text" readonly/>' +
            '<input name="t_iva12" type="text" readonly/>' +
            '<input name="t_iva" type="text" readonly/>' +
            '<input name="t_ice" type="text" readonly/>' +
            '<input name="t_descuento" type="text" readonly/>' +
            '<input name="t_rubros" type="text"  class="total" readonly/></div>',
        Iva_Por: '<div class="footerFact formDatos"><div style="height:56px;"></div><div style="position:absolute;text-align: left;"><select id="Iva_Cod" name="Iva_Cod" style="max-width:' + (Cof_Con === 'S' ? '25' : '100') + '%;" onchange="changeIvas()" class="text"></select>' + (Cof_Con === 'S' ? '<select id="Iva_Pag" name="Iva_Pag" style="max-width:70%" class="text"></select>' : '') + '</div><div style="height:75px;"></div>'
    }, false); $('#formTotales').find('input').attr('tabindex', '-1');
    addItem({});
    $(".secuencia").mask("999-999-999999999", { placeholder: "_" });
    $('#Ciu_Cod').createChosen('input-xs', { tabIndex: 6, width: '100%', template: function (t, d) { return '<div class="over"><b>' + t + '</b></div><div class="over desc">' + d['prov'] + '</div>'; } });
    $('.datepickers').createDatePickers({ checkAvailability: true, hideMsg: false, clean: true });
    if (Cof_Con === 'S') {
        $('#Cop_Fec').on('change', function () { $('.Cop_Fec').val(this.value); $('#Com_Fec').val($(this).val()).datepicker("option", "minDate", $(this).val()); });
        $('#Com_Fec').createFlyout('El comprobante y el documento no estan en el mismo Periodo!', { icon: 'exclamation', placement: 'left_top' });
    }
    $('#Cop_Fec').on('change', function () {
        $('#Cop_Imf').datepicker("option", "maxDate", this.value); $('#Cop_Cad').datepicker("option", "minDate", this.value);
        if (this.value.length > 0) {
            var fec_cop = this.value.split("-"), anio = fec_cop[0], mes = fec_cop[1];
            if ($(this).data('mes') !== mes) checkFechaIva(this.value);
            if ($(this).data('anio') !== anio) {
                if (Cof_Con === 'S') { checkCuentaPago(); $.Search('pro'); }
            } $(this).data('anio', anio);
        }
    }); $('#Cop_Fec').data('anio', $('#Cop_Fec').val().substring(0, 4)).data('mes', $('#Cop_Fec').val().substring(5, 7));

    $('#Cop_Aut').createFlyout('El campo debe tener 10, 37 o 49 digitos!', { icon: 'exclamation', placement: 'right' });
    $('#Prv_Btn').createFlyout('Debe Seleccionar el Proveedor!', { icon: 'exclamation', placement: 'right' });
    $('#Ciu_Cod_chosen').createFlyout('Escoje una Ciudad!', { icon: 'exclamation', placement: 'right' });
    $('#Cop_Num').createFlyout('El Documento ya Existe!', { icon: 'exclamation', placement: 'right' });
    $('#Prs_Ced').createFlyout('La Cedula es Incorrecta!', { icon: 'exclamation', placement: 'bottom' });
    $('#provCreateDialog').createDialog({ icon: 'plus', width: 500, height: 430 });
    $('#Cop_Aut').on('change', function () { var val = $(this).val(), aut = val.length; $(this).attr('title', val); if (aut !== 0 && aut !== 10 && aut !== 37 && aut !== 49) { $(this).fieldValid(false, 'El campo debe tener 10, 37 o 49 digitos!'); } else { $(this).fieldValid(aut === 0 ? '' : true); } });
    $('#Cop_Nna').on('change', function () { var val = $(this).val(), aut = val.length; $(this).attr('title', val); if (aut !== 0 && aut !== 10 && aut !== 37 && aut !== 49) { $(this).fieldValid(false, 'El campo debe tener 10, 37 o 49 digitos!'); } else { $(this).fieldValid(aut === 0 ? '' : true); } });

    // nueva seccion para el dialogo de cuentas
    if ($('#cuenDialog').length > 0)
        $('#cuenDialog').createSearchDialog({
            datatype: 'local',
            colModel: [
                { label: 'C&oacute;d.Int.', name: 'Pld_Cod', key: true, width: 15, align: "center", hidden: false },
                { label: 'C&oacute;digo', name: 'Pld_Cdc', width: 45 },
                { label: 'Cuenta', name: 'Pld_Des', width: 80, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; } },
                { label: 'Grupo', name: 'Pld_Grupo', width: 110, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; } },
                { label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: 'SelectCta', title: 'Seleccione Cuentas', data: ['Pld_Cod', 'Pld_Cdc', 'Pld_Des'] } }
            ]
        }, { title: 'Cuenta', options: [{ label: '&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;', value: 'd' }, { label: '&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;', value: 'c' }] })
        .find('.form-group-options').append('<div class="col-md-4"><input type="text" name="Cop_Fec" class="Cop_Fec" value="" style="display: none;"></div>');
});

/* BUSQUEDAS */
// Selecciona Producto
function selectItem(item) {
    // console.log(item);
    var lastId = gridFact.jqGrid('getCol', 'index', false, 'max'), close = true;
    if (index === 0) { index = lastId; close = false; }
    gridFact.changeRow(index, $.extend(item, item['Iva_Por'] * 1 > 0 ? {
        /* Iva_Cod: $('#Iva_Cod').val(),
        va_Por: $('#Iva_Por').val()*/
        Iva_Cod: item.Iva_Cod,  //$('#Iva_Cod').val(),
        va_Por: item.Iva_Por  //$('#Iva_Por').val()
        //Iva_Por:$('#Iva_Cod option:selected').data('ivapor')
    } : {}));
    var last = gridFact.jqGrid('getRowData', lastId);
    if (last['Pro_Cod'] !== '') addItem({});
    if (close) { $('#proDialog').dialog('close'); setTimeout(function () { $('#' + (index) + '_Cop_Can').focus(); }, 0); } else index = 0;
    updateDocument();
}

/* DATOS GENERALES */
// valida cedula
function validaNoIdentif(number) {
    var digitos = number.split(""), dto = digitos.length, acu = 0, resp = { success: false, message: '' },
        coef = { 'NA': [2, 1, 2, 1, 2, 1, 2, 1, 2], 'PU': [3, 2, 7, 6, 5, 4, 3, 2, 0], 'PR': [4, 3, 2, 7, 6, 5, 4, 3, 2] }, modulo, acum = 0;
    if (dto === 0) resp['message'] = 'No has ingresado ning\u00fan dato!';
    else {
        for (var i = 0; i < dto; i++) if (!isNaN(digitos[i])) { digitos[i] = digitos[i] * 1; acu = acu + 1; }
        if (acu === dto) {
            var tipo = digitos[2];
            if (tipo === 7 || tipo === 8) resp['message'] = '"El tercer d\u00edgito ingresado es inv\u00e1lido"'; else { tipo = (tipo < 6 ? 'NA' : (tipo === 6 ? 'PU' : (tipo === 9 ? 'PR' : ''))); modulo = (tipo === 'NA' ? 10 : 11); resp['tipo_abrev'] = tipo; resp['tipo'] = (tipo === 'NA' ? 'Natural' : (tipo === 'PR' ? 'Privada' : (tipo === 'PU' ? 'P\u00fablica' : ''))); }
            if (dto !== 10 && dto !== 13) { resp['message'] = 'La cantidad de d\u00EDgitos deben ser 10 o 13'; return resp; } else { resp['doc_abr'] = (dto === 10 ? 'C' : (dto === 13 ? 'R' : '')); resp['doc'] = (dto === 10 ? 'C\u00E9dula' : (dto === 13 ? 'R.U.C.' : '')); }
            if (number.substring(0, 2) * 1 > 24) resp['message'] = 'Los dos primeros d\u00EDgitos no pueden ser mayores a 24.';
            if (dto === 13) {
                if (number.substring(10, 13) !== '001') resp['message'] = 'Los tres \u00faltimos d\u00EDgitos no tienen el c\u00F3digo del RUC 001.';
                if (tipo === 'PU' && number.substring(9, 13) !== '0001') resp['message'] = 'El R.U.C. de la empresa del sector p\u00fablico debe terminar con 0001';
            } else if ((tipo === 'PU' || tipo === 'PR')) resp['message'] = 'El R.U.C. de las empresas ' + resp['tipo'] + 's deben tener 13 digitos!';
            if (resp['message'].length > 0) return resp;

            for (var a = 0; a < 9; a++) {
                var resul = digitos[a] * coef[tipo][a];
                acum += (resul - (tipo === 'NA' && resul >= 10 ? 9 : 0));
            }
            var residuo = acum % modulo, digitoVerificador = residuo === 0 ? 0 : modulo - residuo;
            if (digitos[(tipo === 'PU' ? 8 : 9)] !== digitoVerificador) resp['message'] = 'El n\u00famero de ' + resp['doc'] + ' de la ' + (tipo === 'NA' ? 'Persona Natural' : 'Empresa ' + resp['tipo']) + ' ingresado es inv\u00E1lido!';

            if (resp['message'].length === 0) resp['success'] = true;
        } else resp['message'] = "ERROR: Solo debe contener d\u00EDgitos!";
    }
    return resp;
}
// Valida q no existe el documento
function validaCopNum() { var data = $.extend($('#docuFormTemp').getData('ajaxCopNum'), $('#provFormTemp').getData()); /*if($('#Cop_Num').data('old_num')!==data['Cop_Num'])*/ $('#Cop_Num').getValidationJson('', data); /*else $('#Cop_Num').fieldValid(true); */ }
// carga las cuentas pago
function checkCuentaPago(Pld_Cod) {
    if ($('#Cop_Fec').val() === '' || $('#For_Cod').val() === '' || Cof_Con === 'N') return;
    $('#Pag_Pld').attr('disabled', 'disabled');
    $.post("", { cuentasPago: true, For_Cod: $('#For_Cod').val(), Cop_Fec: $('#Cop_Fec').val(), Pld_Cod: Pld_Cod }, function (response) {
        if (response['success'] === true) {
            if (response['total'] > 0) {
                $('#Pag_Pld').html(response['cuentas']);
            } else { $.alert('Error al buscar la cuenta pago para la fecha indicada'); }
        }
    }, 'json').fail(function () { $.alert('Error al buscar el IVA para la fecha indicada'); })
        .always(function () { $('#Pag_Pld').removeAttr('disabled'); });
}
// carga los ivas pala fecha escogida
function checkFechaIva(fecha, Iva_Cod, Pld_Cod) {
    $.post("", {
        Check_Iva: true,
        Cop_Fec: fecha,
        Tic_Cod: $('#Tic_Cod').val(),
        Tic_Sri: $('#Tic_Cod option:selected').data('ticsri'),
        Iva_Cod: Iva_Cod,
        Pld_Cod: Pld_Cod
    }, function (response) {
        if (response['success'] === true) {
            if (response['total'] > 0) {
                $('#Iva_Cod').html(response['options'])[(response['varIvas'] ? 'show' : 'hide')]();
                if (Cof_Con === 'S') $('#Iva_Pag').css('max-width', response['varIvas'] ? '70%' : '100%').html(response['cuentas']);
               // changeIvas();
            } else { $.alert('Error al buscar el IVA para la fecha indicada'); }
        }
    }, 'json').fail(function () { $.alert('Error al buscar el IVA para la fecha indicada'); });
}
// cambia los ivas de los items
function changeIvas() {
    var ids = gridFact.jqGrid('getDataIDs'),
        iva = {
            Iva_Cod: $('#Iva_Cod').val(),
            Iva_Por: $('#Iva_Cod option:selected').data('ivapor')
        };
    $('.iva_por').html(iva['Iva_Por']);

    for (var i = 0; i < ids.length; i++) {
        if ('0' + gridFact.jqGrid('getCell', ids[i], 'Iva_Por') * 1 > 0) gridFact.changeRow(ids[i], iva);
    } 
    updateDocument();
}
// estilo cantidad
function styleCant(e, obj, opt) {
    e.style.textAlign = 'right'; e.placeholder = '0';
    $(e).on('keyup', function () {
        if (isNaN(this.value)/*||this.value===''||(!isNaN(this.value)&&this.value*1===0)*/) { $(this).val('1').focus(); }
        else if (this.value % 1 !== 0) { var dec = String(this.value).split("."); if (typeof dec[1] !== 'undefined' && dec[1].length > 2) this.value = $.toFixed(this.value); }
        updateRowItem(obj);
    });
}
// estilo precio unitario
function stylePru(e, obj, opt) {
    e.style.textAlign = 'right'; e.placeholder = '0.00';
    $(e).on('keyup', function () {
        if (isNaN(this.value)/*||this.value===''||(!isNaN(this.value)&&this.value*1===0)*/) { $(this).val('').focus();; }
        else if (this.value % 1 !== 0) { var dec = String(this.value).split("."); if (typeof dec[1] !== 'undefined' && dec[1].length > 8) this.value = $.toFixed(this.value, 8); }
        updateRowItem(obj);
    });
}
// Actualiza los valores de la fila
function updateRowItem(obj) {
    var row = $.extend({}, gridFact.jqGrid('getRowData', obj['rowId']), gridFact.find('tr#' + obj['rowId']).getDataForced());
    row['Cop_Imp'] = row['Cop_Can'] * (0 + row['Cop_Pru']) * 1;
    gridFact.changeRow(obj['rowId'], row);
    updateDocument();
}
// Añade un item al documento
function addItem(item) {
    var next = gridFact.jqGrid('getCol', 'index', false, 'max');
    next = (isNaN(next) ? 1 : next + 1);
    gridFact.jqGrid('addRowData', next, $.extend(item, { index: next, Cop_Can: 1, Cop_Pru: '' }), 'last');
    gridFact.jqGrid('editRow', next);
    updateRowItem({ rowId: next });
    resize();
}
// Abre dialogo producto para cambiar item
function openItemSelector(id) { index = id; $('#proDialog').dialog('open'); }
function openCtaSelector(id) { index = id; $('#cuenDialog').dialog('open'); } // nueva funcion para abrir el dialogo de cuentas

//nueva funcion
function SelectCta(cta) {
    // Mantener los valores actuales de cantidad y precio unitario
    cta.Cop_Can = $('#' + index + '_Cop_Can').val();
    cta.Cop_Pru = $('#' + index + '_Cop_Pru').val();
    $('#cuenDialog').dialog('close');
    // Cambiar solo los datos de la cuenta, sin afectar el modo de edición
    gridFact.changeRow(index, $.extend({}, gridFact.jqGrid('getRowData', index), cta));
    gridFact.jqGrid('editRow', index); // Volver a poner la fila en modo edición
    gridFact.highlightRow(index);
}

// Elimina item
function deleteItem(index) { var data = gridFact.jqGrid('getRowData', index); if (data['Pro_Cod'] !== '') { gridFact.jqGrid('delRowData', index); updateDocument(); resize(); } }
function resize() { if (gridFact.width() !== $('#documentoMain').width()) gridFact.jqGrid("resizeGrid"); }
// Actualiza los valores totales
function updateDocument() {
    var rows = gridFact.jqGrid('getRowData'), tot = { t_subtotal: 0, t_iva0: 0, t_iva5: 0, t_iva12: 0, t_iva: 0, t_ice: 0, t_descuento: 0, t_rubros: 0 };
    for (var i = 0, z = rows.length; i < z; i++) {
        var row = rows[i];
        row['Cop_Imp'] = (row['Cop_Imp'] * 1);
        row['Iva_Por'] = ('0' + row['Iva_Por']) * 1;
        row['Ice_Por'] = ('0' + row['Ice_Por']) * 1;
        tot['t_subtotal'] = tot['t_subtotal'] + row['Cop_Imp'];
        if (row['Iva_Por'] === 0) { tot['t_iva0'] = tot['t_iva0'] + row['Cop_Imp']; }
        else if (row['Iva_Por'] === 5) {//Porcentaje del 5%
            var ice = (row['Ice_Por'] > 0 ? row['Cop_Imp'] * row['Ice_Por'] / 100 : 0);
            tot['t_iva5'] = tot['t_iva5'] + row['Cop_Imp'];
            tot['t_ice'] = tot['t_ice'] + ice;
            tot['t_iva'] = tot['t_iva'] + (row['Cop_Imp'] + ice) * row['Iva_Por'] / 100;
        } else {
            var ice = (row['Ice_Por'] > 0 ? row['Cop_Imp'] * row['Ice_Por'] / 100 : 0);
            tot['t_iva12'] = tot['t_iva12'] + row['Cop_Imp'];
            tot['t_ice'] = tot['t_ice'] + ice;
            tot['t_iva'] = tot['t_iva'] + (row['Cop_Imp'] + ice) * row['Iva_Por'] / 100;
        }
    }
    tot['t_iva'] = $.round(tot['t_iva']); tot['t_ice'] = $.round(tot['t_ice']);
    tot['t_rubros'] = tot['t_subtotal'] + tot['t_iva'] + tot['t_ice'] - tot['t_descuento'];
    $.each(tot, function (k, v) { tot[k] = $.toFixed(v); });
    $('#formTotales').setData(tot);

    var totalDocumento = $("input[name=t_rubros]").val();
    var saldoDocumento = $("input[name=Cop_Saldo]").val();
    if (parseFloat(totalDocumento) > parseFloat(saldoDocumento)) {
        var anticipoDocumento = totalDocumento - saldoDocumento;
        $('input[name=Ant_Prov]').val(anticipoDocumento.toFixed(2));
    } else {
        $('input[name=Ant_Prov]').val("0.00");
    }
}
// Valida Todo antes de guardar
function validaDocument() {
    var data = $('.formDatos').getData('saveDocument'), aut = data['Cop_Aut'].length, modaut = data['Cop_Nna'].length;
    if (Cof_Con === 'S') {
        if (data['Cop_Fec'].substring(0, 4) !== data['Com_Fec'].substring(0, 4)) { $('#Com_Fec').flyout('show').focus(); return; }
    }
    if (!data['Prv_Cod'].length) { $('#Prv_Btn').focus(); $('#Prv_Btn').flyout('show'); return; }
    if (aut !== 10 && aut !== 37 && aut !== 49) { $('#Cop_Aut').flyout('show').focus(); return; }
    if (modaut !== 10 && modaut !== 37 && modaut !== 49) { $('#Cop_Nna').flyout('show').focus(); return; }
    if (!data['Ciu_Cod'].length) { $('#Ciu_Cod_chosen').flyout('show').focus(); return; }
    if (gridFact.jqGrid('getDataIDs').length - 1 <= 0) { $.alert('Debe seleccionar al menos un <u>Item</u>!', null, 'remove'); return; }
    data['items'] = gridFact.getGridBatch();
    gridFact.startGridEdit();
    data['items'].splice(data['items'].length - 1, 1);
    for (var i = 0; i < data['items'].length; i++) {
        if (data['items'][i]['Cop_Imp'] * 1 <= 0) { $.alert('El producto <u>' + data['items'][i]['Ite_Lar'] + '</u> no puede tener <i>Importe cero</i>!', null, 'remove'); return; }
    }
    data['Old_Num'] = $('#Cop_Num').data('old_num');
    data['Tic_Sri'] = $("#Tic_Cod option:selected").data('ticsri');
    data['Tic_Des'] = $("#Tic_Cod option:selected").text();
    $.arraySpliceFields(data['items'], ['index', 'delete', 'select']);
    $.createDialogConfirm('¿Esta seguro de guardar el Documento?', data, saveDocument);
}
// Guardar documento
function saveDocument(data) {
    $.saveDataJson('', data,
        function (resp) {
            $('#resultContent').setData(resp);
            $('#copForm').setData(resp['Cop_Data']); $('#copresult').setRows(resp['Cop_Rows']); $('#btnCopPrint').data('url', resp['Cop_Link']);
            if (Cof_Con === 'S') {
                $('#compForm').setData(resp['Com_Data']); $('#asiento').setRows(resp['Com_Rows']); $('#btnComPrint').data('url', resp['Com_Link']);
            }
            $('#documentoMain').moveComp('#documentoResult').updateGridsSizes(); return false;
        });
}
// Revisa si existe el proveedor
// buscar una persona
function searchProvee(ced) {
    $.post("", { provAjax2: true, Prs_Ced: ced.substring(0, 10) }, function (response) {
        if (response['total'] * 1 === 1) {
            if (!$.varValid(response['rows'][0]['Prv_Cod']) || response['rows'][0]['Prv_Cod'].length === 0) {
                $('#provCreateForm').setData(response['rows'][0]);
            } else {
                selectProvee(response['rows'][0]);
                $('#provCreateDialog').dialog('close');
            }
        }
    }, 'json').fail(function () { $('#provCreateForm').setData({}); }).always(function () { });
}
// guardar un proveedor
function guardaProvee() {
    $.saveDataJson("", $('#provCreateForm').getData('guardaProvAjax'), function (resp) { selectProvee(resp['prov']); $('#provCreateDialog').dialog('close'); return false; });
}

/* SOLO NOTAS DE CREDITO */
// Valida el Tipo de documento a Modificar
function selectTicSri() {
    $('#Cop_Ntd').getValidationJson('', $('#modiFormTemp').getData('ajaxCodDoc'), function (res) {
        checkImportacion(res['Cop_Ntd']);
        var data = $('#modiFormTemp').setData(res, false).getData();
        data['Cop_Nns'] = data['Cop_Nns'].replace(/_/g, ""); data['Cop_Nns'] = data['Cop_Nns'].replace(/-/g, "");
        if (data['Cop_Nns'] !== '') selectModDoc(); else $('#Cop_Nns').fieldValid();
    });
}
// Valida el Numero de Documento a Modificar
function selectModDoc() {
    $('#Cop_Nns').getValidationJson('', $.extend($('#modiFormTemp').getData('ajaxModDoc'), $('#provFormTemp').getData()), function (res) {
        if (res['Mod_Cop_Cod'] !== '') {
            var saldo = res['Cop_Saldo'];
            $('#modiFormTemp').setData(res, false);
            $('#For_Cod').val($.varValid(res['Mod_Cpp_Cod']) && res['Mod_Cpp_Cod'] !== '' ? 2 : 1).attr('disabled', 'disabled');
            checkCuentaPago(res['Pld_Cod_Pag']);
            $('#Cop_Saldo').val(saldo);
        } else {
            $('#modiFormTemp').setData({ Mod_Cop_Fec: '', Mod_Cop_Cod: '', Mod_Cpp_Cod: '' }, false);
            $('#For_Cod').val(1).removeAttr('disabled').trigger('change');
        }
    });
}

function selectModDocModificar() {
    $('#Cop_Nns').getValidationJson('', $.extend($('#modiFormTemp').getData('ajaxModDoc'), $('#provFormTemp').getData()), function (res) {
        if (res['Mod_Cop_Cod'] !== '') {
            var valorNC = $('#Cop_Saldo').val();
            var saldoCCPP = res['Cop_Saldo'];
            // console.log(valorNC);
            // console.log(saldoCCPP);
            var saldoTotal = parseFloat(valorNC) + parseFloat(saldoCCPP);

            $('#modiFormTemp').setData(res, false);
            $('#For_Cod').val($.varValid(res['Mod_Cpp_Cod']) && res['Mod_Cpp_Cod'] !== '' ? 2 : 1).attr('disabled', 'disabled');
            checkCuentaPago(res['Pld_Cod_Pag']);
            $('#Cop_Saldo').val(saldoTotal);
        } else {
            $('#modiFormTemp').setData({ Mod_Cop_Fec: '', Mod_Cop_Cod: '', Mod_Cpp_Cod: '' }, false);
            $('#For_Cod').val(1).removeAttr('disabled').trigger('change');
        }
    });
}

function checkImportacion(Cop_Ntd) {
    var Importa = Cop_Ntd * 1 === 17;
    $('#Cop_Num').unmask().mask(Importa ? "999-9999-99-99999999" : "999-999-999999999", { placeholder: "_" });
    $('#Cop_Nns').unmask().mask(Importa ? "999-9999-99-99999999" : "999-999-999999999", { placeholder: "_" });
}
$.fn.fmatter.edicion = function (cv, opts, cObjt) {
    if (cObjt['Com_Edit'] === 'N') return '<i title="El comprobante contable es formato anterior" class="glyphicon glyphicon-lock red"></i>';
    if (cObjt['Cop_Est'] !== 'A') return '<i title="Registro Anulado/Inactivo" class="glyphicon glyphicon-remove red"></i>';
    return $.getGridButton(editDocument, cObjt);
};
$.fn.fmatter.edicion.unformat = $.unformatCellHtml;