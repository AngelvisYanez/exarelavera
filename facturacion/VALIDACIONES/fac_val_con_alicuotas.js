var valorId, num,
    arrayGridVentaAct = [],
    arraySubGridDet = [];
var arrayCuotas = [];
var arrayObjetoImpresion = [];
const formatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2
});
$(function() {
    var grid = $('#Cuo_Grid');
    var gridMovAli = $('#tableAlicuotas');
    var div = document.getElementById("ventasDialog");

    $("#tabsAlicuotas").createTabs();

    $('.pagination').find('li a').click(function() {
        $('.pagination').find('li').removeClass('active');
        $(this).parent().addClass('active');
        $('#letra').val($(this).text());
        if ($(this).text() === 'TODOS') $('#search').removeAttr('disabled');
        else $('#search').attr('disabled', 'disabled').val('');
        buscarAJax();
        //getFinalSubGrid();
        document.getElementById("btnDetail").setAttribute("disabled", "disabled");
    });

    /// Grid Listado AliCuotas
    gridMovAli.createGrid({
            caption: 'REGISTRO DE FACTURAS  <div class="pull-right"><b>ORDENAR POR:</b>&nbsp;<select id="OrderBy" class="select_busqueda"><option value="">Todos</option><option value="Resto ASC ">Pagadas</option><option value="Pagos ASC ">No Pagadas</option><select>&nbsp;</div>',
            height: '450',
            shrinkToFit: true,
            colModel: [
                { label: 'C&oacute;d. Int.', name: 'VetCod', key: true, width: 15, align: "center", hidden: true },
                { label: 'Factura', name: 'Num_Vent', align: 'center', hidden: true, width: 30 },
                { label: 'Cliente', name: 'Cliente', align: 'left', width: 65 },
                { label: 'Tipo Venta', name: 'Tic_Des', hidden: true, width: 20 },
                { label: 'Fecha', name: 'Caj_Fec', align: 'center', width: 20 },
                {
                    label: 'Total',
                    name: 'Total',
                    align: 'right',
                    width: 15,
                    formatter: 'currency',
                    formatoptions: {
                        prefix: '$ ',
                        thousandsSeparator: ',',
                        decimalSeparator: '.',
                        defaultValue: ''
                    }
                },
                {
                    label: 'Abonos',
                    name: 'Pagos',
                    align: 'right',
                    width: 15,
                    formatter: function(cellvalue, options, rowObject) {
                        if (rowObject['Pagos'] === '' || rowObject['Pagos'] === null) {
                            return "0.00";
                        } else {
                            return formatMoney(rowObject['Pagos']);
                        }
                    }
                },
                {
                    label: 'Saldo',
                    name: 'Resto',
                    align: 'right',
                    width: 15,
                    formatter: function(cellvalue, options, rowObject) {
                        if (rowObject['Resto'] === '0.00') {
                            return "0.00";
                        } else {
                            return formatMoney(rowObject['Resto']);
                        }
                    }
                },


            ],
            footerrow: true,
            userDataOnFooter: true,
            subGrid: true,
            rowNum: 10000,
            gridview: true,
            viewrecords: true,
            subGridOptions: {
                "plusicon": "ui-icon-triangle-1-e",
                "minusicon": "ui-icon-triangle-1-s",
                "openicon": "ui-icon-arrowreturn-1-e",
                "reloadOnExpand": false,
                "selectOnExpand": true
            },
            subGridRowExpanded: function(subgrid_id, row_id) {
                //console.log(subgrid_id);
                //console.log(row_id);
                let subgrid_table_id = subgrid_id + "_t";
                let rowData = $("#tableAlicuotas").getRowData(row_id);
                $("#" + subgrid_id).html("<table id='" + subgrid_table_id + "' class='scroll'></table>");
                //console.log(rowData['VetCod']);
                $("#" + subgrid_table_id).createGrid({
                    url: "?movAlicuota=" + rowData['VetCod'],
                    datatype: "json",
                    regional: 'es',
                    height: 'auto',
                    responsive: false,
                    colModel: [
                        { label: 'Cod. Int', name: 'Ali_Cod', width: 8, key: true, hidden: false },
                        { label: 'Fecha', name: 'Ali_Fec', width: 30, align: "center" },
                        {
                            label: 'Abono',
                            name: 'Ali_Pag',
                            width: 30,
                            align: "center",
                            formatter: 'currency',
                            formatoptions: {
                                prefix: '$ ',
                                thousandsSeparator: ',',
                                decimalSeparator: '.',
                                defaultValue: ''
                            }
                        }

                    ]
                });
            },
            loadComplete: function(data) {
                if ($.varValid(data.rows)) {
                    for (var i = 0, z = data.rows.length; i < z; i++) {
                        if (data.rows[i]['Resto'] === '0.00') { $("#" + data.rows[i].VetCod + ' td:not(.jqgrid-rownum)').addClass('cellGreen2'); }
                    }
                }

                actualizarValoresAli();
                getFinalSubGrid();
            }

        }, true, '#tableAlicuotasPager', { refresh: false, view: false })
        .gridButtonsAdd([{ caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download', onClickButton: function() { gridMovAli.jqGrid('exportGridExcel', { nombre: 'Alicuotas', hoja: 'HOJA 1' }); } }])
        .gridButtonsAdd([{ id: 'btnDetail', caption: 'Exportar Excel Detallado', buttonicon: 'glyphicon glyphicon-download' /* , onClickButton: function() { gridMovAli.jqGrid('exportGridExcel', { nombre: 'Alicuotas Detallado', hoja: 'HOJA 1' }); } */ }]);
    gridMovAli.getFootRow(true);
    document.getElementById("btnDetail").setAttribute("disabled", "disabled");
    $.createSearchDialog('#ventasDialog',

        [
            { label: 'C&oacute;d.Int.', name: 'Vet_Cod', key: true, width: 15, align: "center", hidden: true },
            { label: 'Tipo Venta', name: 'Tic_Des', width: 50 },
            { label: 'Número Venta', name: 'Num_Vent', align: 'center', width: 50 },
            { name: 'Cli_Cod', align: 'center', width: 50, hidden: true },
            { label: 'Fecha', name: 'Caj_Fec', width: 50 },
            { label: 'Total', name: 'Total', align: 'right', width: 40 },
            { label: 'Abonos', name: 'Pagos', align: 'right', width: 40 },
            { label: 'Saldo', name: 'Resto', align: 'right', width: 40 },
            {
                label: '&nbsp;',
                name: 'act1',
                width: 20,
                align: 'center',
                viewable: false,
                formatter: 'gridButton',
                formatoptions: {
                    action: selectVenta,
                    conditional: function(o) { return o.Resto > 0; },
                    caseFalse: function() { return '<i class="glyphicon glyphicon-lock orange" title="Factura Pagada !"></i>'; },
                    title: 'Seleccionar Factura',
                    icon: 'arrow-right',
                    type: 'success'
                },
                title: false
            }

        ], null, null, null, { headertitles: true }, {
            title: 'Ventas',
            height: '600',
            //caption: "<center>Facturas del cliente " + cliente_data.Cliente + "</center>",
            //Cliente
            options: [
                { label: '&nbsp;&nbsp;C&oacute;d. Cliente&nbsp;&nbsp;', value: 'cd' },
                { label: '&nbsp;&nbsp;Num. Venta&nbsp;&nbsp;', value: 'd' }

            ]

        }).find('.form-group-options').append('<label class="control-label label-xs">Cliente:</label><input id="clienteSearch" name="clienteSearch" type="text" size="19" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" />');


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
        if (set['id'] === '2') { el = $('<div class="input-group input-group-xs ret"><input type="text" id="' + opts['rowId'] + '" name="' + opts['colModel']['name'] + '" class="form-control input-xs ' + set['class'] + '"/><span class="input-group-btn"><button class="btn btn-info" type="button" title="' + set['title'] + '" onclick="' + set['action'] + '(' + opts['rowId'] + ')"><span class="glyphicon glyphicon-search"></span></button></span></div>'); } else { el = $('<input type="text" style="text-align: center;" id="' + opts['rowId'] + '_' + opts['colModel']['name'] + '" name="' + opts['colModel']['name'] + '" class="form-control input-xs ret2" ' + set['attr'] + 'readonly/>'); }
        return el.prop('outerHTML');
    };
    $.fn.fmatter.input3.unformat = function(cv, opts, cObjt) { return $(cObjt).find(':input').val(); };



    agregarFila(0);


    $('#OrderBy').on('change', () => {
        $('input[name=order]').val($('.select_busqueda').find('option:selected').val());
        $('#frm_mod_ali_cuotas').formSubmit();
    });

    $("#rad_ba3").on("click", () => {
        $('#Fec_Ini').removeAttr('disabled');
        $('#Fec_Fin').removeAttr('disabled');
        $('#search').attr('disabled', 'disabled');
    });
    $("#rad_ba2").on("click", () => {
        $('#Fec_Ini').attr('disabled', 'disabled');
        $('#Fec_Fin').attr('disabled', 'disabled');
        $('#search').removeAttr('disabled');
    });
    $("#rad_ba1").on("click", () => {
        $('#Fec_Ini').attr('disabled', 'disabled');
        $('#Fec_Fin').attr('disabled', 'disabled');
        $('#search').removeAttr('disabled');
    });
    $.createDateRange('#Fec_Ini', '#Fec_Fin');
    //$.createDatePickers('#txt_fec_fin');
    validaFechaF();
    validaFechaI();


});

function buscarAJax() {
    $('#tableAlicuotas').Search('#frm_mod_ali_cuotas', 'searchAllFacVentas');
}

function validaFechaF() {
    $("#Fec_Fin").on("change", () => {
        if ($('#Fec_Fin').val() < $('#Fec_Ini').val()) {
            $.alert('El valor de Hasta debe ser superior o igual al Desde');
            $('#Fec_Fin').val('');
            $("#Fec_Fin").focus();
        }
    }).trigger('change');
}

function validaFechaI() {
    $("#Fec_Ini").on("change", () => {
        if ($("#Fec_Ini").val() > $("#Fec_Fin").val()) {
            $.alert('El valor de Desde es superior a Hasta');
            $('#Fec_Ini').val('');
            $("#Fec_Ini").focus();
        }
    }).trigger('change');

}

function agregarFila(aux) {
    var campoGrid = '_Ali_Pag';
    var $this = $('#Cuo_Grid');
    var id = $this.nextIndex();
    $this.jqGrid('addRowData', id, { 'index': id });
    $this.jqGrid('editRow', id);
    $.createDatePickers('#' + id + '_Ali_Fec_Venta');
    $this.find('tr#' + id).find('#' + id + campoGrid).on('change', function() {
        //console.log('entro en el change');
        //console.log(campoGrid);
        realizarCalculo();
    }).trigger('change');
}
/**
 * Método que se encarga de ir registro a registro sumando su abono y verificando que no sea superior al saldo
 */
function realizarCalculo() {
    var grid = $('#Cuo_Grid');
    let ids = grid.jqGrid('getDataIDs');
    let datos = grid.jqGrid('getRowData');
    //console.log(ids);
    //console.log(datos);
    for (let i in datos) {
        var valorId = datos[i]['index'];
        if (datos[i]['Ali_Pag'] !== '') {
            if (existenPagosFactura(arrayCuotas, datos[i]['Vet_Cod'])) {
                recalcular(datos, arrayCuotas, datos[i]['Vet_Cod']);
                //modificaExistente(arrayCuotas, datos[i]['Vet_Cod'], datos[i]['Ali_Pag']);
                verificaAbono(arrayCuotas);
            } else {
                //console.log(datos[i]);
                arrayCuotas.unshift(datos[i]);
                verificaAbono(arrayCuotas);
            }

        }

    }
    //console.log(arrayCuotas);
}

function formatMoney2(amount, decimalCount = 2, decimal = ".", thousands = ",") {
    try {
        decimalCount = Math.abs(decimalCount);
        decimalCount = isNaN(decimalCount) ? 2 : decimalCount;

        const negativeSign = amount < 0 ? "-" : "";

        let i = parseInt(amount = Math.abs(Number(amount) || 0).toFixed(decimalCount)).toString();
        let j = (i.length > 3) ? i.length % 3 : 0;

        return negativeSign + (j ? i.substr(0, j) + thousands : '') + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands) + (decimalCount ? decimal + Math.abs(amount - i).toFixed(decimalCount).slice(2) : "");
    } catch (e) {
        console.log(e);
    }
}

function actualizarValoresAli() {
    $('#tableAlicuotas').startGridEdit();
    let ids = $('#tableAlicuotas').jqGrid('getDataIDs');
    let abonos = 0,
        saldos = 0,
        total_pago = 0,
        tot = 0;
    for (let i = 0; i < ids.length; i++) {
        let reg_pago = $('#tableAlicuotas').jqGrid('getRowData', ids[i]);
        /* if (reg_pago['Resto'] === null || reg_pago['Resto'] === '') {
            //$('#Cuo_Grid').find('#' + valorId + '_Cli_Cod_Ali').val(row.Cli_Cod);
            //$('input:text[name=Cli_Cod_Ali]').val(row.Cli_Cod);
            let row = { Caj_Fec: reg_pago['Caj_Fec'], Cliente: reg_pago['Cliente'], Num_Vent: reg_pago['Num_Vent'], Pagos: reg_pago['Pagos'], Resto: "0", Tic_Des: reg_pago['Tic_Des'], Total: reg_pago['Total'], VetCod: reg_pago['VetCod'] };
            $('#tableAlicuotas').find('tr#' + ids[i]).setData(row, false);
            console.log(reg_pago);
        } */
        var priceVal = parseFloat(reg_pago['Resto'].replace(/[^0-9-.]/g, ''));
        var priceValAbono = parseFloat(reg_pago['Pagos'].replace(/[^0-9-.]/g, ''));
        //console.log(priceVal);
        tot = tot + parseFloat(priceVal * 1);
        abonos = abonos + parseFloat(priceValAbono * 1);
        //console.log(reg_pago);
    }

    //console.log(abonos);
    //console.log(tot);


    $('#tableAlicuotas').jqGrid('footerData', 'set', { Caj_Fec: "TOTALES:", Total: $('#tableAlicuotas').jqGrid('getCol', 'Total', false, 'sum') });
    $('#tableAlicuotas').jqGrid('footerData', 'set', { Pagos: "" + abonos });
    $('#tableAlicuotas').jqGrid('footerData', 'set', { Resto: "" + tot });
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

function existenPagosFactura(lista, campo) {
    var verifica = false;
    for (var i = 0; i < lista.length; i++) {
        if (lista[i]['Vet_Cod'] === campo) {
            verifica = true;
        }
    }
    return verifica;
}

function verificaAbono(cuotas) {
    for (var i = 0; i < cuotas.length; i++) {
        if ((cuotas[i]['Ali_Pag'] * 1) > (cuotas[i]['Saldo'] * 1)) {
            $.alert('El Abono (' + cuotas[i]['Ali_Pag'] + ') es superior al valor del saldo actual (' + cuotas[i]['Saldo'] + ')!<br/>Revise los datos.', null, 'remove');
            $('#Cuo_Grid').find('#' + cuotas[i]['index'] + '_Ali_Pag').val('0.00');
            break;
        }
    }
}

function modificaExistente(lista, campo, valor) {
    for (var i = 0; i < lista.length; i++) {
        if (lista[i]['Vet_Cod'] === campo) {
            lista[i]['Ali_Pag'] = ((valor * 1));
        }
    }

}
/**
 *
 * @param {Datos de la grid} lista
 * @param {listado de suma de abonos por venta} listaCalculo
 * @param {Codigo de venta para recalcular en el array} venta
 */
function recalcular(lista, listaCalculo, venta) {
    var valor = 0;
    for (var i = 0; i < lista.length; i++) {
        if (lista[i]['Vet_Cod'] === venta) {
            valor = (valor + (lista[i]['Ali_Pag'] * 1));
        }
    }

    modificaExistente(listaCalculo, venta, valor);

}

function quitarViaje(row) {
    $.createDialogConfirm('Desea Eliminar el item seleccionado..!!', null, function() {
        $("#Cuo_Grid").jqGrid('delRowData', row.id);
    });
}

//Estilo precio unitario
function stylePru(e, obj, opt) {
    e.style.textAlign = 'right';
    e.placeholder = '0.00';
    $(e).on('keyup', function() {
        if (isNaN(this.value)) { $(this).val('').focus();; } else if (this.value % 1 !== 0) { var dec = String(this.value).split("."); if (typeof dec[1] !== 'undefined' && dec[1].length > 8) this.value = $.toFixed(this.value, 8); }

    });
}
//Dialogo Clientes
function abrirDialogCliente(cliente) {
    $('#clienteDialog').dialog('open');
    $('#CodFormBus').val(cliente);

}
//Dialogo Ventas
function abrirDialogVenta(row) {
    //console.log(row);
    valorId = row;
    if (valorId > 0) {

        $('#ventasDialog').dialog('open');
        $.Search('ventas');
        var cliente_data = $('#Cuo_Grid').jqGrid('getRowData', valorId);
        //console.log(cliente_data);
        $('#clienteSearch').val(cliente_data.Cliente);
        $('#ParamCodAux').val(cliente_data.Cli_Cod_Ali);
    } else {
        $.alert('Debe seleccionar un Cliente antes.!!');
    }
}


if ($('#clienteDialog').length > 0) {
    $.createSearchDialog('#clienteDialog', [
        { label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, width: 15, align: "center", hidden: true },
        { label: 'Cédula/RUC', name: 'Prs_Ced', width: 50 },
        { label: 'Cliente', name: 'Cliente', width: 100 },
        //{ label: 'Apellido', name: 'Prs_Ape', width: 100 },
        { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
        { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectCliente2 } }
    ], null, null, null, { headertitles: true }, {
        title: 'Clientes',
        options: [{ label: '&nbsp;&nbsp;Apellido/Nombre&nbsp;&nbsp;', value: 'd' },
            { label: '&nbsp;&nbsp;C&eacute;dula/R.U.C&nbsp;&nbsp;', value: 'c' }
        ]

    });
}

function selectCliente2(row) {
    //console.log(row);
    //Aqui debo hacer las validaciones si existe mas registros.
    //$('input:text[name=Cliente]').val(row.Cliente);
    valorId = $('#CodFormBus').val();
    //console.log(valorId);

    $('#ParamCodAux').val(row['Cli_Cod']);
    $('#Cuo_Grid').changeRow($('#CodFormBus').val(), row);
    $('#Cuo_Grid').find('#' + valorId + '_Cli_Cod_Ali').val(row.Cli_Cod);
    //$('input:text[name=Cli_Cod_Ali]').val(row.Cli_Cod);
    $('#Cuo_Grid').find('tr#' + valorId).setData(row, false);
    //$('input:text[name=Cli_Cod_Ali]').val(row['Cli_Cod']);
    $('#Cuo_Grid').find('#' + valorId + '_Cli_Cod_Ali').val(row['Cli_Cod']);
    $('#clienteDialog').dialog('close');
    asignarClienteEnVenta(row);


}

function asignarClienteEnVenta(row) {
    $('input:text[name=clienteSearch]').val(row.Cliente);
}

function selectVenta(venta) {
    //console.log(venta);
    //var valorIdC = $('#CodFormVen').val();
    //console.log(valorId);
    //ParamCodAux
    //ventasDialog
    var saldo = venta.Total - venta.Pagos;
    //console.log(saldo);

    venta['Saldo'] = saldo;

    $('#Cuo_Grid').changeRow(valorId, venta);
    $('#Cuo_Grid').find('tr#' + valorId).setData(venta, false);
    //$('#Cuo_Grid').find('tr#' + valorId).find().val(venta['']);
    $('#Cuo_Grid').find('#' + valorId + '_Ali_Pag').val('');

    $('#Cuo_Grid').find('#' + valorId + '_Vet_Cod_Ali').val(venta['VetCod']);
    //$('input:text[name=Vet_Cod_Ali]').val(venta['VetCod']);
    $('#ventasDialog').dialog('close');



}

function saveAliCuota(formulario, accion) {
    var index;
    var shortDateFormat = 'yy-mm-dd';
    $.createDialogConfirm(`¿Est&aacute; seguro que desea guardar los datos de la alicuota?`, null, function() {
        var data = $('#' + formulario).getData('saveDocumento');
        data['alicuotas'] = $('#Cuo_Grid').getGridBatch();
        //console.log(data);
        $.each(data['alicuotas'], function(pos, valor) {
            if (valor['Cliente'] === '' || valor['Vet_Num'] === '' || valor['Caj_Fec'] === '' || valor['Ali_Pag'] === '') {
                index = $("#Cuo_Grid").jqGrid('getInd', valor['index']);
                $.alert('Debe completar información en la fila: ' + index);
                $('#Cuo_Grid').startGridEdit();
                $.alert('La transacci&oacute;n se realizo con exito.');
                return false;
            }
        });
        if (index * 1 > 0) return false;
        data[accion] = true;
        $('#datosTabla').clearGrid();
        $.saveDataJson('', data, function(resp) {
            if (resp['success']) {
                $('#Cuo_Grid').clearGrid();
                $('input:text[name=Caj_Fec]').val($.datepicker.formatDate(shortDateFormat, new Date()));
                imprimirAliCuota(data);
                return false;
            }
        });
    });

}

function imprimirAliCuota(datos) {
    $('#tablita').clearGrid();
    $('#datosTabla').clearGrid();
    var valores;
    datos['alicuotas'].forEach((valor) => {
        if (valor['Cli_Cod_Ali'] > 0) {
            arrayObjetoImpresion.push(valor);
        }
    });
    const esImpresion = async() => {
        await Imprimirpromise();

    }
    esImpresion();
}

function Imprimirpromise() {
    return new Promise((resolve, reject) => {
        let html = "<style> @media all { div.saltopagina{ display: none; } } @media print{ div.saltopagina:not(:last-child){ display:block; page-break-before:always; } } </style>";
        for (let i in arrayObjetoImpresion) {
            $('#tablita').empty();
            $('#tablita').clearGrid();
            $('#datosTabla').clearGrid();
            $('#cabeceraTabla').clearGrid();
            $('#Prs_Ced').text(arrayObjetoImpresion[i]['Prs_Ced']);
            $('#Caj_Fec').text(arrayObjetoImpresion[i]['Ali_Fec_Venta']);
            $('#Cliente').text(arrayObjetoImpresion[i]['Cliente']);
            $('#Prs_Dir').text(arrayObjetoImpresion[i]['Prs_Dir']);
            $('#Prs_Cor').text(arrayObjetoImpresion[i]['Prs_Cor']);
            $('#t_abono').text((arrayObjetoImpresion[i]['Ali_Pag'] * 1).toFixed(2));

            var fila = "<tr><td>" + arrayObjetoImpresion[i]['Vet_Num'] + "</td><td>" + 'Abono Factura' + "</td><td align=right>" + (arrayObjetoImpresion[i]['Ali_Pag'] * 1).toFixed(2) + "</td></tr>";
            var btn = document.createElement("TR");
            btn.innerHTML = fila;
            document.getElementById("tablita").appendChild(btn);
            valores = true;
            html += $('#datosReporte').html();
            html += "<div class='saltopagina'></div>";

        }
        let auxDiv = $("<div></div>").html(html);
        resolve(auxDiv.printElement({ pageTitle: "", overrideElementCSS: [{ href: '../../mascaras/model1/estilos/print.css', media: 'print' }] }));
        $('#tablita').clearGrid();
        arrayObjetoImpresion.length = 0;
    }, function(err) {
        reject(err);
    });
}

function getFinalSubGrid() {
    arrayGridVentaAct.length = 0;
    arraySubGridDet.length = 0;
    arrayGridVentaAct = $('#tableAlicuotas').getGridBatch();
    arrayGridVentaAct.forEach((respuesta) => {
        //console.log(respuesta);
        getSubGridDet(respuesta['VetCod']);
    });

}

async function getSubGridDet(Vet_Cod) {
    num = 0;
    const dSG = await detalleSubGrid(Vet_Cod);
    for (let v = 0; v < dSG.length; v++) {
        arraySubGridDet.push(dSG[v]);
    }
    num++;
    if (arrayGridVentaAct.length === num) { habilitarExporDetail(); }


}

function habilitarExporDetail() {
    $("#btnDetail").removeAttr("disabled");
    $("#btnDetail").on('click', function() {
        fnExcelReport();
    });
}

function fnExcelReport() {
    console.log('si');
    createHiddenTable();
    tab_text = '<html xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8">';
    tab_text = tab_text + '<head><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';

    tab_text = tab_text + '<x:Name>Hoja 1</x:Name>';

    tab_text = tab_text + '<x:WorksheetOptions><x:Panes></x:Panes></x:WorksheetOptions></x:ExcelWorksheet>';
    tab_text = tab_text + '</x:ExcelWorksheets></x:ExcelWorkbook></xml></head><body>';

    tab_text = tab_text + "<table border='1px'>";
    tab_text = tab_text + $('#myModifiedTable').html();;
    tab_text = tab_text + '</table></body></html>';

    data_type = 'data:application/vnd.ms-excel; charset=UTF-8';

    var ua = window.navigator.userAgent;
    var msie = ua.indexOf("MSIE ");

    if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)) {
        if (window.navigator.msSaveBlob) {
            var blob = new Blob([tab_text], {
                type: "application/csv;charset=utf-8;"
            });
            navigator.msSaveBlob(blob, 'Test file.xls');
        }
    } else {
        //console.log(data_type);
        //console.log(tab_text);
        $('#testAnchor')[0].click()
    }
    $('#MessageHolder').html("");
}
$($("#testAnchor")[0]).click(function() {
    //console.log(data_type);
    //console.log(tab_text);
    $('#testAnchor').attr('href', data_type + ', ' + encodeURIComponent(tab_text));
    $('#testAnchor').attr('download', 'Resumen_Detallado_Alicuotas.xls');
});

function createHiddenTable() {
    var vacio = '';
    var verifica = false;
    var ColumnHead = ['C&oacute;d. Int.', 'Factura', 'Cliente', 'Tipo Venta', 'Fecha', 'Total', 'Abonos', 'Saldo'];
    var ColumnHeadSubGrid = ['', 'C&oacute;d. Int.', 'Fecha Abono', 'Valor'];
    var TableMarkUp = '<table id="myModifiedTable" class="visibilityHide"><thead><tr ><th><b>' + ColumnHead[0] + '</b></th><th><b>' + ColumnHead[1] + '</b></th><th><b>' + ColumnHead[2] + '</b></th><th><b>' + ColumnHead[3] + '</b></th><th><b>' + ColumnHead[4] + '</b></th><th><b>' + ColumnHead[5] + '</b></th><th><b>' + ColumnHead[6] + '</b></th><th><b>' + ColumnHead[7] + '</b></th>  </tr></thead><tbody>';
    for (let q = 0; q < arrayGridVentaAct.length; q++) {
        TableMarkUp += '<tr bgcolor="#DFDFDF"><td>' + arrayGridVentaAct[q]['VetCod'] + '</td><td>' + arrayGridVentaAct[q]['Num_Vent'] + '</td><td>' + arrayGridVentaAct[q]['Cliente'] + '</td><td>' + arrayGridVentaAct[q]['Tic_Des'] + '</td><td>' + arrayGridVentaAct[q]['Caj_Fec'] + '</td><td>' + arrayGridVentaAct[q]['Total'] + '</td><td>' + arrayGridVentaAct[q]['Pagos'] + '</td><td>' + arrayGridVentaAct[q]['Resto'] + '</td></tr>';
        if (arraySubGridDet.length > 0) {
            for (let t = 0; t < arraySubGridDet.length; t++) {
                if (arrayGridVentaAct[q]['VetCod'] === arraySubGridDet[t]['Vet_Cod']) {
                    if (!verifica || it !== q) {
                        var it = 0;
                        it += q;
                        verifica = true;
                        TableMarkUp += '<thead><tr bgcolor="#DFDFDF"><th><b>' + ColumnHeadSubGrid[0] + '</b></th><th><b>' + ColumnHeadSubGrid[1] + '</b></th><th><b>' + ColumnHeadSubGrid[2] + '</b></th><th><b>' + ColumnHeadSubGrid[3] + '</b></th></tr></thead>';
                    }

                    TableMarkUp += '<tr><td>' + vacio + '</td><td>' + arraySubGridDet[t]['Ali_Cod'] + '</td><td>' + (arraySubGridDet[t]['Ali_Fec']) + '</td><td>' + roundN(arraySubGridDet[t]['Ali_Pag'], 2) + '</td></tr>';
                }
            }
        }
    }
    TableMarkUp += "</tbody></table>";
    $('#MessageHolder').append(TableMarkUp);

}


//Promesa Detalle por Vet_Cod
function detalleSubGrid(Vet_Cod) {
    return new Promise((resolve, reject) => {
        $.getDataJson("", { detallePorVenta: true, Vet_Cod: Vet_Cod }, (result) => {
            resolve(result.detSubGridDetail);
        }, (err) => {
            reject(err);
        });

    });
}

function roundN(num, n) {
    return parseFloat(Math.round(num * Math.pow(10, n)) / Math.pow(10, n)).toFixed(n);
}

function limpiarArrays() {

}