var gestionarItem, container
var reporteTabla;
var edit_reten = false;
var Nota_CreDeb = false
var es_Nueva = 0;

$(function() {
    $('.datepickers').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });

    gestionarItem = $("#items");
    reporteTabla = $("#datosTabla5");
    gestionarItem.createGrid({
        caption: (Nota_CreDeb === true ? '<div class="pull-right" formDatos><span>Afecta Inventario:&nbsp;</span><input id="afecta_inventario" name="Cal_Inv" type="checkbox"/>&nbsp;</div>' : ''),
        data: [],
        rowNum: 10000000,
        height: 'auto',
        footerrow: true,
        headertitles: true,
        selectGridRows: false,
        colModel: [
            { name: 'select', label: '<i class="glyphicon glyphicon-check"></i>', width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: openItemSelector, icon: 'check', title: 'Seleccionar Item', data: function(o) { return o.index; } }, resizable: false },
            { name: 'Vet_Index', label: 'Vet_Index', width: 40, align: 'center', hidden: true },
            { name: 'index', label: 'Index', width: 20, sorttype: 'int', align: 'center', key: true, hidden: true },
            { name: 'Pro_Cod', label: 'C�d.Int.', width: 20, sorttype: 'int', align: 'center', hidden: true },
            { name: 'Vet_Can', label: 'Cant.', labelLong: 'Cantidad', width: 40, align: 'right', title: false, editable: (true), editoptions: { dataInit: styleCant } },
            { name: 'Uni_Des', label: 'Uni.', labelLong: 'Unidad', width: 25, resizable: false },
            { name: 'Ite_Lar', label: 'Descripci&oacute;n', width: 100 },
            {name:'Req_Adi',label:'Des. Adicional', width:75, editable:true},
            { name: 'Iva_Cod', label: 'CodIva', width: 20, hidden: true },
            { name: 'Ice_Int', label: 'CodIce', width: 20, hidden: true },
            { name: 'Ret_Mod', label: 'Ret Mod.', width: 20, hidden: true, formatter: 'truefalse', title: false, resizable: false },
            { name: 'Ret_Ren_Cod', label: 'Ret Ren_Cod', width: 20, hidden: true },
            { name: 'Ret_Ren_Por', label: 'Ret Ren_Por', width: 20, hidden: true },
            { name: 'Ret_Ren_Con', label: 'Ret Ren_Con', width: 20, hidden: true },
            { name: 'Iva_Ren_Cod', label: 'Iva Ren_Cod', width: 20, hidden: true },
            { name: 'Iva_Ren_Por', label: 'Iva Ren_Por', width: 20, hidden: true },
            { name: 'Iva_Ren_Con', label: 'Iva Ren_Con', width: 20, hidden: true },
            { name: 'Pld_Cod', label: 'Pld_Cod', width: 20, hidden: true },
            { name: 'Pld_Des', label: 'Pld_Des', width: 20, hidden: true },
            { name: 'Adq_Cod', label: 'CodAdq', width: 20, hidden: true },
            { name: 'delete', label: '<i class="glyphicon glyphicon-remove icon-grey"></i>', width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: (edit_reten ? '' : deleteItem), icon: 'remove', title: (edit_reten ? 'No es posible Eliminar Item' : 'Eliminar Item'), type: 'danger', data: function(o) { return o.index; }, attr: { 'tabindex': '-1' } }, resizable: false }
        ]
    }, true, 'itemsPager', { view: false }).gridButtonsAdd([
        {
            caption: 'Remover Todos',
            buttonicon: 'glyphicon glyphicon-remove',
            onClickButton: function() {
                gestionarItem.clearGridData();
                changeIvas();
                addItem({});
            }
        },
    ]);
    gestionarItem.getFootRow(true);
    gestionarItem.jqGrid('footerData', 'set', {
        Ite_Lar: '<div class="footerFact formDatos" class="formDatos"><label style="position:relative;text-align: left;">Observaci&oacute;n:</label><textarea id="Req_Obs" name="Req_Obs" tabindex="12" class="text" onchange=""></textarea></div><div>&nbsp;</div><div>&nbsp;</div>',
        Vet_Pru: '<div class="footerFact"><label>SUBTOTAL:</label><label>TARIFA 0%:</label><label>TARIFA <span class="iva_por"></span>%:</label><label><span class="iva_por"></span>% IVA:</label><label>ICE:</label><label>DESCUENTO:</label><label class="total">TOTAL:</label></div>',
        Vet_Imp: '<div class="footerFact formDatos" id="formTotales"><input id="t_subtotal" name="t_subtotal" type="text" readonly/><input name="t_iva0" type="text" readonly/><input name="t_iva12" type="text" readonly/><input name="t_iva" type="text" readonly/><input name="t_ice" type="text" readonly/><input id="t_descuento" name="t_descuento" type="text" onchange="updateDocument();" class="text" /><input id="t_rubros" name="t_rubros" type="text"  class="total" readonly/></div>',
        Iva_Por: '<div class="footerFact formDatos"><div style="height:56px;"></div><div style="position:absolute;text-align: left;"><select id="Iva_Cod" name="Iva_Cod" style="max-width:100%;" onchange="changeIvas();" class="text">' + $('#Def_Ivas').html() + '</select></div><div style="height:75px;padding-top:38px;text-align: left;"><input id="Vet_Des" name="Vet_Des" style="height:19px;position:absolute;display:none;" /></div>'
    }, false);

    $.fn.fmatter.impRenta = function(cv, opts, cObjt) {
        if (!$.varValid(cObjt['Pro_Cod']) || cObjt['Pro_Cod'] === '') return '';
        return getRentaButton(cv, { tipo: 'R', index: cObjt['index'] }, cObjt);
    };
    $.fn.fmatter.impRenta.unformat = $.unformatCellHtml;
    $.fn.fmatter.retIva = function(cv, opts, cObjt) { if (!$.varValid(cObjt['Pro_Cod']) || cObjt['Pro_Cod'] === '') return ''; if (cObjt['Iva_Por'] * 1 === 0) return ''; return getRentaButton(cv, { tipo: 'I', index: cObjt['index'] }, cObjt); };
    $.fn.fmatter.retIva.unformat = $.unformatCellHtml;
    addItem({});
    changeIvas();

    container = $("#container");
    container.createGrid({
        postData: $("#searchContainer").getData("profAjax"),
        height: 300,
        colModel: [
            { name: "Req_Cod", hidden: true, key: true },
            { label: 'Fecha', name: 'Req_Fec', align: "center", width: 10 },
            { label: 'Cliente', name: 'Cliente', align: "center", width: 10 },
            { label: 'Número Requisición', name: 'Req_Num', align: "center", width: 20 },
            { label: 'Vendedor', name: 'Vnd_Cod', align: "center", width: 3 },
            { label: $.createIcon('print'), name: 'actReg', align: "center", width: 1, formatter: 'gridButton', formatoptions: { action: impRequisicion, conditional: function(o) { return o.Prd_Est !== 'I'; }, icon: 'print', type: 'success', title: 'Imprimir requisición' } },

        ],
    }, true, "#containerPager", {}).gridButtonsAdd([{ //caption: 'Agregar Productor', buttonicon: 'plus-sign', classes: 'a', onClickButton: function () { containerForm.data({tipo:'saveContainer'}); limpiarFormContainer(); gestionarContainer.dialog('open'); }
        }
    ]);

    $('#Req_Ent_Par').prop('checked', false);
    $('#Req_Ent_Com').hide();
    $('#Req_Ent_Com_Lab').hide();

});

$("#Req_Ent_Par").change(function() {
    if(this.checked) {
        $('#Req_Ent_Com').show();
        $('#Req_Ent_Com_Lab').show();
        console.log()
    }else{
        $('#Req_Ent_Com').hide();
        $('#Req_Ent_Com_Lab').hide();
        $('#Req_Ent_Com').val('');
    }
});

function selectRequisitor(requisitor) {
    console.log("DENTRO SELECT REQUISITOR",requisitor)
    $('#requisitorFormTemp').setData($.extend(requisitor, { op_opciones: 'c' }));
    $('#requisitorDialog').dialog('close');
}

$.createSearchDialog('requisitorSearch', [
    { label: 'C&oacute;d.Int.', name: 'Per_Cod', key: true, width: 15, align: "center", hidden: true },
    { label: 'C&eacute;dula', name: 'Prs_Ced', width: 50 },
    { label: 'Requisitor', name: 'Req_Nom', width: 100 },
    { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
    { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectRequisitor } }
], null, null, null, { headertitles: true }, { title: 'Requisitor' });

async function initialize() {
    let response = await $.ajax({
        url: '/compras/FRONT/requisiciones/registrar_req.php', 
        type: 'GET', 
        data: {
            initialize:true,
        }, 
        dataType: "json",
    })
    console.log('REQ_NUM',response.Req_Num);
    $('input:text[name=Req_Num]').val(parseInt(response.Req_Num["total"])+1);
    response.tipos.forEach(element => {
        $("select#Req_Tip")
            .append( $("<option>")
            .val(element.Rtp_Cod)
            .html(element.Rtp_Des))  
    });
}

initialize()

$.createSearchDialog('proDialog', [
    { label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, width: 20, align: "center", hidden: false },
    { label: 'Descripci&oacute;n', name: 'Ite_Lar', width: 110 },
    { label: 'Marca', name: 'Mar_Des', width: 40 },
    { label: 'Categoria', name: 'Cat_Des', width: 90, align: "center" },
    { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectItem } }
], null, null, null, null, { title: 'Producto', options: [{ label: '&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;', value: 'd' }, { label: '&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;', value: 'c' }] });

// Abre dialogo producto para cambiar item
function openItemSelector(id) {
    index = id;
    $('#proDialog').dialog('open');
    $.Search('pro');
}

function selectItem(item) {
    //console.log(item);
    var lastId = gestionarItem.jqGrid('getCol', 'index', false, 'max'),
        close = false;
    if (index === 0) {
        index = lastId;
        close = false;
    }
    var new_item = gestionarItem.changeRow(index, $.extend(item, item['Iva_Por'] * 1 > 0 ? { Iva_Cod: $('#Iva_Cod').val(), Iva_Por: $('#Iva_Cod option:selected').data('ivapor'), Cop_Ice: null } : { Iva_Ren_Cod: '', Iva_Ren_Con: '', Iva_Ren_Por: '', Iva_Ren_Sri: '', Cop_Ice: null }));
    gestionarItem.changeRow(index, new_item, null, { Vet_Pru: item['Pre_Pvp'] });
    updateRowItem({ rowId: index });
    var last = gestionarItem.jqGrid('getRowData', lastId);
    gestionarItem.jqGrid('editRow', last);
    if (last['Pro_Cod'] !== '') addItem({});
    if (close) {
        $('#proDialog').dialog('close');
        setTimeout(function() { $('#' + (index) + '_Vet_Can').focus(); }, 0);
    } else if (available()) index = 0;
    else index = lastId * 1 + 1;
    updateDocument();
}

//Actualiza los valores de la fila
function updateRowItem(obj) {
    var datosa = gestionarItem.jqGrid('getRowData', obj['rowId']);
    var datosb = gestionarItem.find('tr#' + obj['rowId']).getDataForced();
    var row = $.extend({}, datosa, datosb);
    row['Vet_Imp'] = row['Vet_Can'] * (0 + row['Vet_Pru']) * 1;
    row['Vet_Imp'] = row['Vet_Imp'] - (('0' + row['Vet_Dec']) * 1 > 0 ? row['Vet_Imp'] * row['Vet_Dec'] / 100 : 0);
    gestionarItem.changeRow(obj['rowId'], row);
    updateDocument();
}

// cambia los ivas de los items
function changeIvas() {
    var ids = gestionarItem.jqGrid('getDataIDs'),
        iva = { Iva_Cod: $('#Iva_Cod').val(), Iva_Por: $('#Iva_Cod option:selected').data('ivapor') };
    $('.iva_por').html(iva['Iva_Por']);
    for (var i = 0; i < ids.length; i++) {
        if ('0' + gestionarItem.jqGrid('getCell', ids[i], 'Iva_Por') * 1 > 0)
            gestionarItem.changeRow(ids[i], iva);
    }
    updateDocument();
}

// Elimina item
function deleteItem(index) {
    var row = gestionarItem.jqGrid('getRowData', index),
        lastId = gestionarItem.jqGrid('getCol', 'index', false, 'max');
    if (row['Pro_Cod'] !== '') {
        gestionarItem.jqGrid('delRowData', index);
        //if(items.jqGrid('getRowData',lastId)['Pro_Cod']!=='') addItem({});
        updateDocument();
    }
}

function updateDocument() {
    var filaCalc = addItem({});
    var rows = gestionarItem.jqGrid('getRowData'),
        des_val = $('#t_descuento').val(),
        des_por = $('#Vet_Des').val(),
        tot = { t_subtotal: 0, t_iva0: 0, t_iva12: 0, t_iva: 0, t_ice: 0, t_descuento: (isNaN(des_val) ? 0 : des_val * 1), Vet_Des: (isNaN(des_por) || des_por * 1 === 0 ? 0 : des_por * 1), t_rubros: 0 },
        Tic_Sri = $('#Tic_Cod').find('option:selected').data('ticsri') * 1,
        rise = (Tic_Sri === 2 || Tic_Sri === 9);

    for (var i = 0, z = rows.length; i < z; i++) {
        var row = rows[i];
        if (row['Pro_Cod'] !== '') {
            row['Vet_Imp'] = (row['Vet_Imp'] * 1);
            row['Iva_Por'] = rise ? 0 : ('0' + row['Iva_Por']) * 1;
            row['Ice_Por'] = ('0' + row['Ice_Por']) * 1;
            tot['t_subtotal'] = tot['t_subtotal'] + row['Vet_Imp'];
            if (row['Iva_Por'] === 0 || rise) tot['t_iva0'] = tot['t_iva0'] + row['Vet_Imp'];
            else tot['t_iva12'] = tot['t_iva12'] + row['Vet_Imp'];
        }
    }
    tot['Vet_Des'] = (tot['t_descuento'] > 0 ? (tot['t_subtotal'] >= tot['t_descuento'] ? tot['t_descuento'] * 100 / tot['t_subtotal'] : 100) : tot['t_descuento'] * 1);

    for (var i = 0, z = rows.length; i < z; i++) {
        var row = rows[i],
            des_glob = (tot['Vet_Des'] > 0 ? row['Vet_Imp'] * tot['Vet_Des'] / 100 : 0),
            ice = (row['Ice_Por'] > 0 ? (row['Vet_Imp'] - des_glob) * row['Ice_Por'] / 100 : 0);
        if (row['Pro_Cod'] !== '') {
            if (row['Iva_Por'] > 0 && !rise) {
                tot['t_ice'] = tot['t_ice'] + ice;
                tot['t_iva'] = tot['t_iva'] + (row['Vet_Imp'] + ice - des_glob) * row['Iva_Por'] / 100;
            }
        }
    }
    tot['t_iva'] = $.round(tot['t_iva']);
    tot['t_ice'] = $.round(tot['t_ice']);
    tot['t_rubros'] = tot['t_subtotal'] + tot['t_iva'] + tot['t_ice'] - tot['t_descuento'];

    //var pagos_tot=pagos.jqGrid('getCol', 'Vet_Tot', false, 'sum');
    //$('#Val_Pcc').val( $.toFixed(tot['t_rubros']-pagos_tot) );



    $.each(tot, function(k, v) {
        tot[k] = $.toFixed(v, k !== 'Vet_Des' ? 2 : 10);
    });
    $('#formTotales').setData(tot);
    $('#Vet_Des').val(tot['Vet_Des']);
    //calculaRetencion();
    gestionarItem.jqGrid('delRowData', filaCalc);
    return tot;

}


/* $( "#requisitorSearch" ).click(function() {
    $.ajax({
        url: '/compras/api/requisiciones/registrar.php', 
        type: 'GET', 
        data: {
            cliSearchAjax:true,
            Org_Niv: this.value
        }, 
        dataType: "json",
    })
});
 */


// COPIA SECCION DE PROFORMAS


var stado = 0;

var actReq = parseInt(/* numReqs['total'] */100);


function impRequisicion(row) {
    //console.log(row);

}




$('#Val_Pcc_2').on('change', function() {

});

function verVentanaRequisiciones() {
    $('#requisicion').moveComp('#documentoMain').updateGridsSizes();
}




function available() {
    var its = gestionarItem.jqGrid('getDataIDs').length,
        max = $('#Tic_Cod').find('option:selected').data('autima'),
        seguir = true;
    seguir = ($.isNumeric(max) ? (its < max) ? true : false : true);
    return seguir;

}
//Estilo para cantidad
function styleCant(e, obj, opt) {

    e.style.textAlign = 'right';
    e.placeholder = '0';
    $(e).on('keyup', function() {
        if (isNaN(this.value)) { $(this).val('1').focus(); } else if (this.value % 1 !== 0) { var dec = String(this.value).split('.'); if (typeof dec[1] !== 'undefined' && dec[1].length > 2) this.value = $.toFixed(this.value); }
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



/*
// Selecciona Producto
function selectItem(item) {
	console.log(item);
	//gestionarItem.setRow(item);
	//items.jqGrid('getDataIDs')
	$("#items").setRow(item);
	$('#Ite_Lar').val(item.Ite_Lar);
	console.log(item);
	//	$("#items").trigger("reloadGrid");
	$('#proDialog').dialog('close');
}
*/


// A�ade un item al documento
function addItem(item, Vet_Can = 1, Vet_Pru = '') {
    //console.log("Estamos en el addItem");
    //console.log(item);
    var next = gestionarItem.jqGrid('getCol', 'index', false, 'max');
    //console.log(next);
    next = (isNaN(next) ? 1 : next + 1);
    gestionarItem.jqGrid('addRowData', next, $.extend(item, { index: next, Vet_Can: Vet_Can, Vet_Pru: Vet_Pru }), 'last');
    gestionarItem.jqGrid('editRow', next);
    return next;
}

function fechaMayorQue(fechaInicial, fechaFinal) {
    valuesStart = fechaInicial.split('-');
    valuesEnd = fechaFinal.split('-');
    // Verificamos que la fecha no sea posterior a la fecha final
    var dateStart = new Date(valuesStart[0], (valuesStart[1] - 1), valuesStart[2]);
    var dateEnd = new Date(valuesEnd[0], (valuesEnd[1] - 1), valuesEnd[2]);
    if (dateStart >= dateEnd) {
        return 1;
    }
    return 0;
}

function setDefaultIva() {
    if (ivas_venta.length) {
        var iva_sel = ivas_venta[0]['Iva_Por'];
        var fecha_sel = $('#Req_Fec').val();
        $.each(ivas_venta, function(i, iva) {
            if (!fechaMayorQue(fecha_sel, iva['Iva_Fin'])) {
                if (!fechaMayorQue(iva['Iva_Ini'], fecha_sel))
                    iva_sel = iva['Iva_Por'];
            }
        });
        $('#Iva_Cod').val($('*[data-ivapor="' + iva_sel + '"]').val());
        return iva_sel;
    }
}


// Selecciona Producto


$.createSearchDialog('cliSearch', [
    { label: 'C&oacute;d.Int.', name: 'Per_Cod', key: true, width: 15, align: "center", hidden: true },
    { label: 'C&eacute;dula', name: 'Prs_Ced', width: 50 },
    { label: 'Cliente', name: 'Req_Nom', width: 100 },
    //{ label: 'Apellido', name: 'Prs_Ape', width: 100 },
    { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
    { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectCliente2 } }
], null, null, null, { headertitles: true }, { title: 'Cliente' });

function selectCliente2(cliente) {
    //console.log(es_Nueva);
    //console.log(cliente);
    //generarNumero(cliente);

    searchNumReq(cliente);
    $('#clieFormTemp').setData($.extend(cliente, { op_opciones: 'c' }));
    $('#Req_Nva').val(es_Nueva);
    $('#cliSearch').dialog('close');
}

$.createSearchDialog('requisitorDialog', [
    { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15, align: "center", hidden: true },
    { label: 'C�dula/RUC', name: 'Prs_Ced', width: 50 },
    { label: 'Cliente', name: 'Cliente', width: 100 },
    { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
    { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectCliente } }
], null, null, null, { headertitles: true }, { title: 'Cliente' });

function selectCliente(cliente) {
    //console.log('clieDialog');
    $('#clieFormTemp').setData($.extend(cliente, { op_opciones: 'c' }));
    $('#Req_Nva').val(es_Nueva);
    searchNumReq(cliente);
    $('#clieDialog').dialog('close');

}

$('#Req_Fec').change(function() {
    setDefaultIva();
});

function paddy(num, padlen, padchar) {
    var pad_char = typeof padchar !== 'undefined' ? padchar : '0';
    var pad = new Array(1 + padlen).join(pad_char);
    return (pad + num).slice(-pad.length);
}

function searchNumReq(cliente) {
    var d = new Date();
    //console.log("searchNumReq");
    $.getDataJson("", { numRequisicionCliente: true, Cli_Cod: cliente.Cli_Cod, Prs_Cod: cliente.Prs_Cod, Prs_Ced: cliente.Prs_Ced, Emp_Cod: cliente.Emp_Cod }, function(busquedaNum) {
        var valor = parseInt(busquedaNum.numeroReq.total) + 1;
        $('input:text[name=Req_Num]').val(valor);
        $('#titleReporte').text(valor);
    });


}


$('#clieCreateDialog').createDialog({ icon: 'plus', width: 500, height: 430 });
//clieAgreDialog

function searchCliente(ced) {
    $.post('', { provAjax2: true, Prs_Ced: ced.substring(0, 10) }, function(response) {
        if (response['total'] * 1 === 1) {
            if (!$.varValid(response['rows'][0]['Cli_Cod']) || response['rows'][0]['Cli_Cod'].length === 0) {
                $('#clieCreateForm').setData(response['rows'][0]);
            } else {
                selectCliente(response['rows'][0]);
                $('#clieCreateDialog').dialog('close');
            }
        }
    }, 'json').fail(function() { $('#clieCreateForm').setData({}); }).always(function() {});
}

// Valida cedula
function validaNoIdentif(number) {
    var digitos = number.split(''),
        dto = digitos.length,
        acu = 0,
        resp = { success: false, message: '' },
        coef = { 'NA': [2, 1, 2, 1, 2, 1, 2, 1, 2], 'PU': [3, 2, 7, 6, 5, 4, 3, 2, 0], 'PR': [4, 3, 2, 7, 6, 5, 4, 3, 2] },
        modulo, acum = 0;
    if (dto === 0) resp['message'] = 'No has ingresado ning\u00fan dato!';
    else {
        for (var i = 0; i < dto; i++)
            if (!isNaN(digitos[i])) {
                digitos[i] = digitos[i] * 1;
                acu = acu + 1;
            }
        if (acu === dto) {
            var tipo = digitos[2];
            if (tipo === 7 || tipo === 8) resp['message'] = '"El tercer d\u00edgito ingresado es inv\u00e1lido"';
            else {
                tipo = (tipo < 6 ? 'NA' : (tipo === 6 ? 'PU' : (tipo === 9 ? 'PR' : '')));
                modulo = (tipo === 'NA' ? 10 : 11);
                resp['tipo_abrev'] = tipo;
                resp['tipo'] = (tipo === 'NA' ? 'Natural' : (tipo === 'PR' ? 'Privada' : (tipo === 'PU' ? 'P\u00fablica' : '')));
            }
            if (dto !== 10 && dto !== 13) { resp['message'] = 'La cantidad de d\u00EDgitos deben ser 10 o 13'; return resp; } else {
                resp['doc_abr'] = (dto === 10 ? 'C' : (dto === 13 ? 'R' : ''));
                resp['doc'] = (dto === 10 ? 'C\u00E9dula' : (dto === 13 ? 'R.U.C.' : ''));
            }
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
            var residuo = acum % modulo,
                digitoVerificador = residuo === 0 ? 0 : modulo - residuo;
            if (digitos[(tipo === 'PU' ? 8 : 9)] !== digitoVerificador) resp['message'] = 'El n\u00famero de ' + resp['doc'] + ' de la ' + (tipo === 'NA' ? 'Persona Natural' : 'Empresa ' + resp['tipo']) + ' ingresado es inv\u00E1lido!';

            if (resp['message'].length === 0) resp['success'] = true;
        } else resp['message'] = 'ERROR: Solo debe contener d\u00EDgitos!';
    }
    return resp;
}

function generateUUID() {
    var d = new Date().getTime();
    var uuid = 'xxxxxxxx-xxxx4xxx-yxxxxxxx-xxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = (d + Math.random() * 16) % 16 | 0;
        d = Math.floor(d / 16);
        return (c == 'x' ? r : (r & 0x3 | 0x8)).toString(16);
    });
    $('input:text[name=Vet_Num]').val(uuid);
    return uuid;
}

$("#gen_num_req").on("click", function() {
    // lo que queramos realizar
    //console.log('lola clicl');
    $('#gen_num_req').attr('disabled', 'disabled');
    //$('input:text[name=Vet_Num]').attr('disabled', 'disabled');
    //document.getElementById('gen_num_req').disabled=true;
});

function generarNumero() {
    //console.log('lola');
}
//Habilita el boton de guardar
$(document).on('change', '#items', function() {
    //$("#guardar").removeAttr("disabled");
});


function validarFormRequisicion(editaDocument = false) {
    updateDocument();
    var data = $('#formDocumento').getData('saveDocumento');
    //console.log(data);
    //console.log(data['items']);
    data['items'] = $('#items').getGridBatch();
    $('#items').startGridEdit();
    var cant_items = $('#items').jqGrid('getDataIDs').length;
    $.arraySpliceFields(data['items'], ['Index', 'delet']);
    if (cant_items < 1 || (cant_items <= 1 && !editaDocument)) {
        $.alert('Debe seleccionar al menos un <u>Item</u>!', null, 'remove');
        $('#items').clearGrid();
        addItem({});
        return;
    }
    if (data['items'].length === 0) return $.alert("Debe ingresar al menos un Producto!", null, 'alert');
    if (data['Vnd_Cod'] === 0) return $.alert("Requisicion sin vendedor", null, 'alert');
    (isNaN(parseInt(data['items'][data['items'].length - 1]['Pro_Cod'])) ? data['items'].splice(data['items'].length - 1, 1) : '');
    for (var i = 0; i < data['items'].length; i++) {
        if (data['items'][i]['Vet_Imp'] * 1 <= 0) {
            $.alert('El producto <u>' + data['items'][i]['Ite_Lar'] + '</u> no puede tener <i>Importe cero</i>!', null, 'remove');
            updateDocument();
            return;
        }
    }
    $('#items').startGridEdit();
    setDefaultIva();
    $.createDialogConfirm((editaDocument ? 'Est&aacute; seguro de editar el Documento?' : 'Est&aacute; seguro que desea guardar los cambios?'), data, saveDocument);
}

function mensajeConNumeroProf(data) {
    //console.log(data);
    var valorReq = actReq + 1;
    //console.log(numReqs['total']);
    if (data['Num_Vtas'] > 0 && data['Es_Mod'] > 0) {
        $.getDataJson("", { numRequisicionCliente: true, Cli_Cod: data['Cli_Cod'] }, function(busquedaNum) {
            var valor = parseInt(busquedaNum.numeroReq.total) + 1;
            $.alert('Se genero una nueva requisicion no.' + parseInt(valor), null, 'remove');
            $('#titleReporte').text(valor);
        });

    }

}

function imprimirRequisicion(reporteTabla, campos) {
    var total = 0;
    var porcActual = 0;
    var valorPorcentajeMC = 0;
    //console.log(campos);
    updateDocument();
    $('#tablita').empty();
    $('#tablita').clearGrid();
    $('#datosTabla').clearGrid();
    //console.log('llamando a el imprimir');
    //console.log(campos);

    //console.log(campos['t_rubros']);
    //$('#datosTabla').html($(reporteTabla).jqGrid('exportGridInnerHTML', { generated: false, caption: false, footer: true, removeHiddens:true, bodyBorder: false }));
    //$('#titleReporte').html($(reporteTabla).getCaption());
    //reporteTabla.setData(campos);
    //$('#cabeceraTabla').setData(campos);
    //$('#datosTabla').html($('#items').jqGrid('exportGridInnerHTML', { generated: false, caption: false, footer: true, removeHiddens:true, bodyBorder: false }));
    campos['items'].forEach(function(valor) {
        //console.log('forEach');
        //console.log(valor);
        if (valor['Pro_Cod'] > 0) {
            if (valor['Iva_Por'] > 0) { porcActual = parseFloat(valor['Iva_Por']); }
            //console.log(porcActual);
            total = total + parseFloat(valor['Vet_Imp']);
            //console.log(total);
            var fila = "<tr><td>" + valor['Vet_Can'] + "</td><td  align=left>" + valor['Ite_Lar'] + " " + valor['Req_Adi'] +"</td><td>" + valor['Vet_Pru'] + "</td><td>" + valor['Vet_Imp'] + "</td><td align=right>" + valor['Vet_Imp'] + "</td></tr>";
            var btn = document.createElement("TR");
            btn.innerHTML = fila;
            document.getElementById("tablita").appendChild(btn);
        }
        //$('#datosTabla').setRows(valor.rows);
    });

    $('.iva_por').html(porcActual);
    $('#datosTabla5').printElement({ pageTitle: "", overrideElementCSS: [{ href: '../../mascaras/model1/estilos/print.css', media: 'print' }] });
    //startEdit();
    $('#tablita').clearGrid();

}

function saveDocument(data) {
    //console.log('saveDocument');
    $('#datosTabla').clearGrid();
    console.log("SAVEDOCUMENTJS",data);
    var shortDateFormat = 'yy-mm-dd';
    //edit_reten === true;
    reporteTabla.setData(data);
    $('#cabeceraTabla').setData(data);
    $('#datosTabla').setRows(data['items'].rows);
    mensajeConNumeroProf(data);
    //console.log('save docuemnto');
    //console.log(data);//console.log(data); console.log(data['rets']);
    $.post('', data,
        function(resp) {
            console.log("RESPUESTAJS",resp)
            if (resp['success']) {
                $('#items').clearGrid();
                //console.log(vendedor['Vendedor']);
                $('#formDocumento').setData(vendedor || {});
                $('input:text[name=Vendedor]').text(vendedor['Vendedor']);
                $('input:text[name=Prs_Ced]').val('');
                $('#Prs_Dir').text('');
                $('input:text[name=Req_Fec]').val($.datepicker.formatDate(shortDateFormat, new Date()));
                $('input:text[name=Req_Fec_Ent_Bod]').val($.datepicker.formatDate(shortDateFormat, new Date()));
                $("#Req_Tip").val($("#Req_Tip option:first").val());
                $('#Req_Ent_Com').hide();
                $('#Req_Ent_Com_Lab').hide();
                $('#Req_Ent_Com').val('');
                //$('#titleReporte').text((parseInt(numReqs['total']) + 1));
                addItem({});
                setDefaultIva();
                // imprimirRequisicion(reporteTabla, data);
                //mensajeConNumeroProf(data);
                //if($resp['Num_Vtas'] > 0 && $resp['Es_Mod'] > 0){}
                //$('#guardar').attr('disabled', 'disabled');
                initialize()
                $.alert("Transacci&oacute;n exitosa!")
                return false;
            }
        },"json").fail(function(error){
            console.log("ERROOORRRRRRRRRRRRR",error)
        });

    //this.location.reload();
}

function startEdit() {
    var aux = $.jgrid.inlineEdit;
    $.jgrid.inlineEdit = { focusField: false };
    gestionarItem.jqGrid('editRow', 'c_1');
    gestionarItem.jqGrid('editRow', 'p_1');
    gestionarItem.jqGrid('editRow', 'p_99');
    $.jgrid.inlineEdit = aux;
}

//MOFICAR REQUISICIONES
$(function() {

    if ($('#reqDialog').length > 0)
        $.createSearchDialog('reqDialog', [
            { label: 'Cod.Int', name: "Req_Cod", align: "center", hidden: false, key: true, width: 2 },
            { label: 'Fecha', name: 'Req_Fec', align: "center", width: 4 },
            { label: 'Proform.', name: 'Req_Num', align: "center", width: 3 },
            { label: 'CI/RUC', name: 'Prs_Ced', align: "center", width: 6 },
            { label: 'Cliente', name: 'Cliente', align: "left", width: 17 },
            { label: 'Obser.', name: 'Req_Obs', hidden: true, align: "center", width: 10 },
            { label: 'Estado', name: 'Req_Est', align: "center", width: 5, hidden: true, title: true },
            { label: 'Vendedor', name: 'Vnd_Cod', align: "center", width: 5, hidden: true, title: false },
            { label: $.createIcon('home'), name: 'actReg', align: "center", width: 2, formatter: 'gridButton', formatoptions: { action: verRequisicion, conditional: function(o) { return o.Req_Est !== 'Inactiva'; }, icon: 'arrow-right', type: 'success', title: 'Ver requisición' } },
        ], null, null, null, { headertitles: true }, { title: 'Requisición', options: [{ label: '&nbsp;&nbsp;Cliente&nbsp;&nbsp;', value: 'd' }, { label: '&nbsp;&nbsp;N&uacute;mero&nbsp;&nbsp;', value: 'c' }] });
});

function verRequisicion(row) {
    var resultado = (isNaN(parseInt(row['Req_Ord'])) ? '' : parseInt(row['Req_Ord']));
    $('#clieFormTemp').setData($.extend(row, { op_opciones: 'd' }));
    $('input:text[name=Req_Fec]').val(row['Req_Fec']);
    $('input:text[name=Req_Num]').val(row['Req_Num']);
    $('input:text[name=Req_Mod_Cod]').val(row['Req_Cod']);
    $('#Req_Num_Ext').val(resultado);
    $('#reqDialog').dialog('close');
    obtenerDetalle(row);
    updateDocument();
}
$('#Prof_btn').on('click', function() {
    stado++;
    es_Nueva = 0;
});

$('#Agr_Cli_Btn').on('click', function() {
    stado = 0;
    es_Nueva++;
});

function obtenerDetalle(req) {
    //console.log('obtenerDetalle');
    updateDocument();
    $('#items').clearGrid();
    //console.log(req);
    //console.log(es_Nueva);
    $('#Es_Mod').val(stado);
    $('#Req_Obs').val(req['Req_Obs']);
    $.getDataJson("", { detalleRequisiciones: true, Req_Cod: req.Req_Cod }, function(resuladoDetalle) {
        resuladoDetalle.detReqms.forEach(function(valor) {
            //console.log(valor);
            var next = $("#items").jqGrid('getCol', 'index', false, 'max');
            next = (isNaN(next) ? 1 : next + 1);
            $("#items").jqGrid('addRowData', next, $.extend(valor, { index: next, Vet_Can: valor['Req_Cant'], Ite_Lar: valor['Ite_Lar'], Vet_Pru: valor['Req_Pru'], Vet_Imp: parseFloat(valor['Req_Imp']).toFixed(2) }), 'last');
            gestionarItem.jqGrid('editRow', next);
        });
        resuladoDetalle.numVentasAct.forEach(function(valor) {
            //console.log('numeroRequisicion:')
            //console.log(valor);
            $('#Num_Vtas').val(valor['total']);
        });
        $('#titleReporte').text(req['Req_Num']);
        addItem({});
        updateDocument();
        $('#items').startGridEdit();
    });

}