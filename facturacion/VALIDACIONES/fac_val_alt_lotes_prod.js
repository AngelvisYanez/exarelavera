var rowIde = 0,
    arrayLoteAct = [],
    arrayVacios = [],
    arrayValLotes = [];

$(function() {
    //Grid de Lotes de s
    var grid = $('#lotesGrid');




    /**Formatters */
    $.fn.fmatter.inputN = function(cv, opts, cObjt) {
        var set = opts['colModel']['formatoptions'],
            el;
        //console.log(opts);
        if (set['id'] === '1') {
            el = $(
                '<div class="input-group input-group-xs ret"><input type="number" step="0.01" min="0" id="' +
                opts['rowId'] +
                '" name="' +
                opts['colModel']['name'] +
                '" class="form-control input-xs ' +
                set['class'] +
                '"/><span class="input-group-btn"><button class="btn btn-info" type="button" title="' +
                set['title'] +
                '" onclick="' +
                set['action'] +
                '(' +
                opts['rowId'] +
                ')"><span class="glyphicon glyphicon-search"></span></button></span></div>'
            );
        } else {
            el = $(
                '<input type="number"  style="text-align: right;" step="0.01" pattern="^\d+(?:\.\d{1,3})?$" min="0" id="' +
                opts['rowId'] +
                '_' +
                opts['colModel']['name'] +
                '" name="' +
                opts['colModel']['name'] +
                '" class="form-control input-xs" ' +
                set['attr'] +
                '/>'
            );
        }
        return el.prop('outerHTML');
    };
    $.fn.fmatter.inputN.unformat = function(cv, opts, cObjt) {
        return $(cObjt).find(':input').val();
    };
    /** */
    if ($('#provDialog').length > 0) {
        $.createSearchDialog('#provDialog', [
            { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 15, align: "center", hidden: true },
            { label: 'Cédula/RUC', name: 'Prs_Ced', width: 50 },
            { label: 'Proveedor', name: 'Proveedor', width: 100 },
            { label: 'Cont.', name: 'Prv_Con', width: 20, align: "center", labelLong: 'Obligado a Llevar Contabilidad', formatter: 'truefalse', formatoptions: { msg: false } },
            { label: 'Espe.', name: 'Prv_Esp', width: 20, align: "center", labelLong: 'Contribuyente Especial', formatter: 'truefalse', formatoptions: { msg: false } },
            { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectProvee } }

        ], null, null, null, { headertitles: true }, { title: 'Proveedor', text: 'Prs_Ced' });
    }

    if ($('#prodDialog').length > 0) {
        $.createSearchDialog('#prodDialog', [
            { label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, width: 20, align: "center", hidden: false },
            { label: 'Descripción', name: 'Ite_Lar', width: 110 },
            { label: 'Marca', name: 'Mar_Des', width: 40 },
            { label: 'Categoria', name: 'Cat_Des', width: 90, align: "center" },
            { label: 'IVA', name: 'Iva_Por', width: 20, align: "center", formatter: 'truefalse', formatoptions: { yesMsg: 'Grava IVA', noMsg: 'No Grava IVA' }, title: false },
            { label: 'Adq.', name: 'Adq_Des', width: 30, align: "center", formatter: 'title', formatoptions: { title: function(o) { return o['Adq_Des']; } } },
            { label: 'PVP', name: 'Pre_Pvp', width: 40, align: "right", formatter: 'currency' },
            { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectItem } }

        ], null, null, null, null, { title: '', options: [{ label: '&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;', value: 'd' }, { label: '&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;', value: 'c' }] });
    }

    var opts = {
        height: 75,
        colModel: [
            { label: 'Cód.Int.', name: 'Lte_Cod', key: true, width: 12, align: 'center', hidden: false },
            { label: 'Serie ', name: 'Lte_Ser', width: 28, align: 'left' },
            { label: 'F. Elaboraci&oacute;n', name: 'Lte_Alt', width: 20, align: 'center' },
            { label: 'F. Caducidad', name: 'Lte_Cad', width: 19, align: 'center' },
            { label: 'Proveedor', name: 'Prs_Ced', width: 21, align: 'center' },
            { name: 'delete', label: '&nbsp;', width: 6, viewable: false, formatter: 'gridButton', formatoptions: { action: delLabor, data: function(o) { return o; }, conditional: function(o) { return o.Lte_Cod !== '' && o.Pro_Cod > 0; }, caseFalse: function() { return '<i class="glyphicon glyphicon-lock orange" title="No existen registros actualmente!"></i>'; }, icon: 'trash', type: 'danger', title: 'Anular Lote' }, resizable: false }
        ]
    };
    if ($('#detaLotesAct').length > 0)
        $('#detaLotesAct').createGrid(
            $.extend(opts, {
                height: 300,
                width: 500,
                responsive: false,
                caption: null,
                rownumbers: false
            }), true
        );


    grid.createGrid({
            caption: 'REGISTRO DE LOTES',
            height: '450',
            colModel: [
                { name: 'index', label: 'Index', width: 20, sorttype: 'int', align: 'center', hidden: true, key: true },
                { label: 'Cod', name: 'Pro_Cod', width: 20, sorttype: 'int', align: 'center', hidden: true, formatter: 'textboxExa' },
                { label: '<span class="required"></span>Serie', name: 'Lte_Ser', width: 25, align: 'right', sortable: false, hidden: false, formatter: 'textboxExa' },
                //{ label: 'Cantidad', name: 'Lte_Cnt', width: 10, align: 'right', sortable: false, hidden: false, formatter: 'textboxExa' }, Te lo dije viejo lucho
                { label: 'Observaci&oacute;n', name: 'Lte_Obs', width: 30, align: 'center', title: false, formatter: 'textboxExa', formatoptions: {} },
                { label: '<span class="required"></span>F. Elaboración', name: 'Lte_Alt', width: 15, align: 'right', sortable: false, hidden: false, formatter: 'textboxExa', formatoptions: { afterInit: function(el) { /* $.createDatePickers(el); */ } } },
                { label: '<span class="required"></span>F. Caducidad', name: 'Lte_Cad', width: 15, align: 'right', sortable: false, hidden: false, formatter: 'textboxExa', formatoptions: { afterInit: function(el) { /* $.createDatePickers(el); */ } } },
                { name: 'Prv_Cod', width: 10, sorttype: 'int', align: 'center', hidden: true, formatter: 'textboxExa' },
                { label: 'Proveedor', name: 'Prs_Ced', width: 18, align: 'center', title: false, formatter: 'textboxExa', formatoptions: { attr: { readonly: 'readonly' }, append: { div: 'btn', value: function(o) { return $.getGridButton('pConsole', o, 'Ver Proveedores', 'search') /* + $.getGridButton('ver') */ ; } } } },
                { label: '<span class="required"></span>Dias.', name: 'Lte_Nti', width: 12, align: 'right', sortable: false, hidden: false, formatter: 'inputN', formatoptions: { id: '2', attr: '' } },
                { name: 'delete', label: '<i class="glyphicon glyphicon-remove"></i>', width: 6, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: quitarLote, icon: 'remove', type: 'danger', title: 'Eliminar Item', data: function(o) { return o; } }, resizable: false }


            ],
            loadComplete: function(data) {

            }


        }, true, '#lotesGridPager', { refresh: false, view: false })
        .gridButtonsAdd([{ caption: 'Agregar Lote', buttonicon: 'glyphicon glyphicon-plus', onClickButton: function() { agregarFila.call(grid, 0); } }]);
    grid.getFootRow(true);

});
var ver = [{ k: 'ff', v: 'sdf', c: 'dfg' }, { k: 'gg', v: 'gg', c: 'gg' }];
/**Seccion Labores */
function delLabor(row) {
    console.log(' det labor');
    $.createDialogConfirm('Desea Eliminar el lote seleccionado..!!', null, function() {
        $.saveDataJson("", { deleteLote: true, Lte_Cod: row['Lte_Cod'], Pro_Cod: row['Pro_Cod'] }, (respuesta) => {
            if (respuesta['success']) {
                $('#detaLotesAct').jqGrid('delRowData', row.id);
                $('#detaLotesAct').trigger("reloadGrid");
                $.alert('La transacci&oacute;n se realizo con exito.');
                return false;
            }
        });

    });
}

function selectItem(row) {
    $('#frm_actividades').setData(row);
    $('#prodDialog').dialog('close');
    dataLotes(row);
    verficaDataGrid();
}

function selectProvee(row) {
    //console.log('Seleccionar Proveedor', row);
    $('#lotesGrid').find('#' + rowIde + "_Prs_Ced").val(row['Prs_Ced']);
    $('#lotesGrid').find('#' + rowIde + "_Prv_Cod").val(row['Prv_Cod']);
    $('#provDialog').dialog('close');
    rowIde = 0;

}

function setDataGridLotes(arrayLotes) {
    $("#detaLotesAct").clearGrid(true);
    var next = $("#detaLotesAct").jqGrid('getCol', 'index', false, 'max');
    next = (isNaN(next) ? 1 : next + 1);
    if (arrayLotes.length > 0) {
        $('#detaLotesAct').setRows(arrayLotes);
    } else {
        $('#detaLotesAct').jqGrid('addRowData', next, $.extend({
            index: next,
            Lte_Ser: 'No se encontrarón',
            Lte_Alt: 'Registros'

        }), 'last');
    }
}


function dataLotes(row) {
    arrayLoteAct.length = 0;
    const datos = async() => { arrayLoteAct = await searchLotesActivos(row); }
    datos().then(() => {
        setDataGridLotes(arrayLoteAct);
    });
}


async function searchLotesActivos(row) {
    //llamar a la promesa de lotes Activos por
    let result = await getLoteByProCod(row);
    return result;
}

function getLoteByProCod(row) {
    return new Promise((resolve, reject) => {
        $.getDataJson("", { searchLotesProd: true, Pro_Cod: row['Pro_Cod'] }, (result) => {
            resolve(result.getLabor);
        }, (err) => {
            reject(err);
        });

    });
}
/**Fin Seccion Labores */

/**
 * Validaciones
 */
function dataLotesProd(valor, ProCod, id) {
    var campo = '_Lte_Ser';
    arrayValLotes.length = 0;
    var serie = $('#lotesGrid').find('tr#' + id).find('#' + id + campo).val();
    const datos = async() => { arrayValLotes = await searchProdLotes(valor, ProCod); }
    datos().then(() => {
        //console.log('Resultado promesa busqueda actualizada');
        //console.log(arrayValLotes);
        if (arrayValLotes.length > 0) {
            $.alert('La serie ' + valor + 'ya se encuentra resgistrada con el producto revise datos de la fila:' + id);
            $('#lotesGrid').find('tr#' + id).find('#' + id + campo).val('');
            $('#btn_gua_act').attr('disabled', 'disabled');
        } else {
            $('#btn_gua_act').removeAttr('disabled');
        }
    });

}

async function searchProdLotes(valor, ProCod) {
    let result = await getLotesProd(valor, ProCod);
    return result;
}

function getLotesProd(valor, ProCod) {
    return new Promise((resolve, reject) => {
        $.getDataJson("", { searchActProdLot: true, Pro_Cod: ProCod, Serie: valor }, (result) => {
            resolve(result.getAtLotesProd);
        }, (err) => {
            reject(err);
        });
    });
}

/**
 * Metodos Grid registro lotes
 */
function agregarFila(aux) {
    var campo = '_Lte_Ser';
    var $this = $('#lotesGrid');
    var ProCod = $('#Pro_Cod').val();
    var id = $this.nextIndex();
    //console.log(this)
    this.setRow({ index: id, Pro_Cod: ProCod });
    dateForRange(id);
    //$this.jqGrid('addRowData', id, { index: id });
    //$this.jqGrid('editRow', id);
    $this.find('tr#' + id).find('#' + id + campo).on('change', function() {
        var valSerie = $this.find('tr#' + id).find('#' + id + campo).val();
        var valProd = $this.find('tr#' + id).find('#' + id + '_Pro_Cod').val();
        $('#btn_gua_act').removeAttr('disabled');
        //verficaSerieProd(valSerie, valProd, id);
    });
}

function verficaSerieProd(valor, valProd, id) {
    // console.log('Lola->1:', valor);
    // console.log('Lola->2:', valProd);
    dataLotesProd(valor, valProd, id);
}

function pConsole(row) {
    // console.log('la la la la', row);
    rowIde = row['index'];
    $('#provDialog').dialog('open');
}

function dateForRange(id) {
    $.createDateRange('#' + id + '_Lte_Alt', '#' + id + '_Lte_Cad');
}

function verficaDataGrid() {
    var lotes = $('#lotesGrid').getGridBatch();
    if (lotes.length > 0) {
        for (var i = 0; i < lotes.length; i++) {

            $("#lotesGrid").jqGrid('delRowData', lotes[i]['index']);

        }

    }

}

function quitarLote(row) {
    $.createDialogConfirm('Desea Eliminar el item seleccionado..!!', null, function() {
        $('#lotesGrid').jqGrid('delRowData', row.id);
    });
}

function saveData(formulario, accion, dialogo) {
    var indiceAct = 0,
        vfG = false;
    var data = $('#' + formulario).getData('save');
    data[accion] = true;
    data['lotes'] = $('#lotesGrid').getGridBatch();
    $.each(data['lotes'], function(pos, valor) {
        if (valor['Lte_Ser'] === '' || valor['Lte_Alt'] === '' || valor['Lte_Cad'] === '' || valor['Pro_Cod'] === '') {
            index = $("#lotesGrid").jqGrid('getInd', valor['index']);
            indiceAct = index;
            vfG = true;
            return false;
        }
    });
    if (vfG) {
        $.alert('Verifique la información en la fila: ' + indiceAct);
        return false;
        vfG = false;
    }
    if ((data['lotes'].length) < 1) { $.alert('Debe existir al menos un registro.!!'); return false; }
    $.arraySpliceFields(data['lotes'], ['index', 'Prs_Ced', 'delete']);
    $.createDialogConfirm('¿Est&aacute; seguro que desea guardar los cambios?', null, function() {
        $.saveDataJson('', data, function(resp) {
            if (resp['success']) {
                $('#' + formulario)[0].reset();
                $('#lotesGrid').clearGrid(true);
                $('#detaLotesAct').clearGrid(true);
                $.alert('La transaccii&oacute;n se realizo con exito.');
                return false;
            }
        });
    });
}


/**
 *Fin Metodos del grid
 */