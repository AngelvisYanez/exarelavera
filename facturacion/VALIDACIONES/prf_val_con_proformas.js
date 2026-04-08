var container;
var today = new Date();

$(function() {
    $('.datepickers').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });
    container = $("#container");
    container.createGrid({
        postData: $("#searchContainer").getData("profAjax"),
        height: 250,
        caption: 'Proformas Registradas',
        stateCol: 'Prf_Est',
        stateConfig: { Inactiva: 'cellRed2' },
        colModel: [
            { label: 'Cod.Int', name: 'Prf_Cod', align: "center", hidden: false, key: true, width: 10 },
            { label: 'Fecha', name: 'Prf_Fec', align: "center", width: 15 },
            { label: 'No. Proforma', name: 'Prf_Num', align: "center", width: 15 },
            { label: 'Cliente', name: 'Cliente', hidden: false, width: 55 },
            { label: 'Observaciones', name: 'Prf_Obs', width: 38, classes: 'nowrap' },
            { label: 'Vendedor', name: 'Vendedor', width: 25, /*formatoptions: { action: cambioVendedor },*/ /* title: false */ },
            //{ label: 'Vendedor', name: 'Vnd_Cod', align: "center", width: 3 },
            //{ label: $.createIcon('print'), name: 'actMod', align: "center", width: 1, formatter:'gridButton', formatoptions:{ action:impProforma,  icon:'print', conditional:function(o){ return o.Prd_Est!=='I';  }, data:function(o){ return o; }, type: 'primary', title: 'Imprimir proforma'}, title:false },
            // { label: $.createIcon('remove'), name: 'actDel', align: "center", width: 1, formatter: 'gridButton', formatoptions: { action: preDelContainer, data: 'Prd_Cod', conditional: function (o) { return o.Prd_Est !== 'I'; }, icon: 'remove', type: 'danger', title: 'Eliminar Productor' }, title: false },
            { label: 'Estado', name: 'Prf_Est', width: 10, align: "center", formatter:'estado', formatoptions:{full:true} },
            { label: '&nbsp;', name: 'act0', width: 7, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: viewInfo, title: 'Ventas Asociadas', icon: 'info-sign', type: 'info' }, title: false },
            { label: $.createIcon('home'), name: 'actReg', align: "center", width: 7, formatter: 'gridButton', formatoptions: { action: verProforma, conditional: function(o) { return o.Prf_Est !== 'Inactiva'; }, icon: 'arrow-right', type: 'success', title: 'Ver proforma' } },
        ],
        //selectGridRows: false
    }, true, "#containerPager", {}).gridButtonsAdd([{ //caption: 'Agregar Productor', buttonicon: 'plus-sign', classes: 'a', onClickButton: function () { containerForm.data({tipo:'saveContainer'}); limpiarFormContainer(); gestionarContainer.dialog('open'); }
        }

    ]);

    var opts = {
        height: 75,
        colModel: [
            { label: 'C&oacute;d.Int.', name: 'Vet_Cod', key: true, width: 15, align: "center", hidden: false },
            { label: 'N&uacute;mero ', name: 'Vet_Num', width: 15, align: 'center' },
            { label: 'Cliente ', name: 'Cliente', width: 85, align: 'center' },
            { label: 'Fecha', name: 'Vet_Sys', width: 25, align: 'center' }
        ]
    };

    var optis = {
        height: 'auto',
        footerrow: true,
        userDataOnFooter: true,
        colModel: [
            { label: 'C&oacute;d.Int.', name: 'Vet_Cod', key: true, width: 15, align: "center", hidden: true },
            { label: 'Cantidad ', name: 'Prf_Cant', width: 25, align: 'center' },
            { label: 'Descripci&oacute;n ', name: 'Ite_Lar', width: 85, align: 'left' },
            { label: 'P.Unitario', name: 'Prf_Pru', width: 25, align: 'right' },
            { label: 'Importe', name: 'Prf_Imp', width: 45, align: 'right' },
            { label: 'Total', name: 'Prf_ImpT', width: 25, align: 'right' }
        ],
        loadComplete: function() {
            //$(this).setGridSummary(['Prf_ImpT'], { Prf_Imp: "<div style='text-align:right;'>T.PROFORMA:</div>" });
            $('#prubaTabla').getFootRow(true);
            $('#prubaTabla').jqGrid('footerData', 'set', {
                Ite_Lar: '<div class="footerFact formDatos" class="formDatos"><div style="text-align: left;">Observaci&oacute;n:</div><textarea id="Cop_Obs" name="Cop_Obs" tabindex="12" class="text" readonly></textarea></div>',
                Prf_Imp: '<div class="footerFact"><label>SUBTOTAL:</label><label>TARIFA 0%:</label><label>TARIFA <span class="iva_por"></span>%:</label><label><span class="iva_por"></span>% IVA:</label><label>DESCUENTO:</label><label class="total">TOTAL:</label></div>',
                Prf_ImpT: '<div class="footerFact formDatos" id="formTotales"><input id="t_subtotal" name="t_subtotal" type="text" readonly/><input name="t_iva0" type="text" readonly/><input name="t_iva12" type="text" readonly/><input id="t_iva" name="t_iva" type="text" readonly/><input id="t_descuento" name="t_descuento" type="text" class="text" readonly/><input id="t_rubros" name="t_rubros" type="text"  class="total" readonly/></div>',
            });

        }
    };
    let desde = $('#desdeT');
    let hasta = $('#hastaT');
    $.createDateRange(desde, hasta);
    /* $.createDatePickers(desde);
    $.createDatePickers(hasta); */
    $("#tabsProformas").createTabs();
    createGridTotales();
    enableDateOne();
    enableDateTwo();
    if ($('#docDetaDialog').length > 0)
        $('#docDetaDialog').createDialog({ height: 400, width: 600, noTitleStuff: false, noBorder: true });
    if ($('#detaDocu').length > 0)
        $('#detaDocu').createGrid($.extend(opts, { height: 'auto', width: 550, responsive: false, caption: null, rownumbers: false }), true);
    if ($('#prubaTabla').length > 0)
        $('#prubaTabla').createGrid($.extend(optis, { height: 'auto', width: 850, responsive: false, caption: null, rownumbers: false }), true);

});

function enableDateOne() {
    $('#radsct3').on("click", function() {
        $('#divFecha').show();
        $('#desdeT').removeAttr('disabled');
        $('#hastaT').removeAttr('disabled');
        $('#search').attr('disabled', 'disabled');

    });
}

function enableDateTwo() {
    $('#radsct1').on("click", function() {
        $('#divFecha').hide();
        $('#desdeT').attr('disabled', 'disabled');
        $('#hastaT').attr('disabled', 'disabled');
        $('#search').removeAttr('disabled');
    });
    $('#radsct2').on("click", function() {
        $('#divFecha').hide();
        $('#desdeT').attr('disabled', 'disabled');
        $('#hastaT').attr('disabled', 'disabled');
        $('#search').removeAttr('disabled');
    });
}

function createGridTotales() {
    $("#containerT").createGrid({
        caption: 'Proformas',
        height: 330,
        colModel: [
            { label: 'Cod.Int', name: 'Prf_Cod', align: "center", hidden: false, key: true, width: 3 },
            { label: 'Fecha', name: 'Prf_Fec', align: "center", width: 4 },
            { label: 'Cliente', name: 'Cliente', align: "center", width: 25 },
            { label: 'No. Proforma', name: 'Prf_Num', align: "center", width: 7 },
            { label: 'Estado', name: 'Prf_Est', align: "center", width: 5, title: true },
            { label: '', name: 'Vnd_Cod', hidden: true, align: "center", width: 8 },
            { label: 'Vendedor', name: 'Vendedor', align: "center", width: 12 },
            { label: 'SubTotal', name: 'Valores', align: "right", width: 5, formatter:'currency' },
            { label: 'IVA', name: 'Iva', align: "right", width: 5, formatter:'currency' },
            { label: 'Total', name: 'Total', align: "right", width: 5, formatter:'currency' }
        ],
        footerrow: true,
        userDataOnFooter: true,
        clearFootRow: true, totalCols:['Valores','Iva','Total'], totalDefault:{Vendedor: "TOTALES:"}
        /*rowNum: 10000,
        gridview: true,
        viewrecords: true,
        loadComplete: function(data) { calculoValoresFootter(); }*/

    }, true, '#containerPagerT', { refresh: false, view: false }).gridButtonsAdd([
        { id: 'btnExel', caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download', onClickButton: function() { $("#containerT").jqGrid('exportGridExcel', { nombre: 'Resumen', hoja: 'HOJA 1' }); } }
    ]);
    //$('#containerT').clearFootRow(true);
}

/*function calculoValoresFootter() {
    $('#containerT').startGridEdit();
    let ids = $('#containerT').jqGrid('getDataIDs');
    var totRent = 0,
        totIva = 0,
        tot = 0;
    for (let i = 0; i < ids.length; i++) {
        let reg_actividad = $('#containerT').jqGrid('getRowData', ids[i]);
        var priceProf = parseFloat(reg_actividad['Valores'].replace(/[^0-9-.]/g, ''));
        var priceIva = parseFloat(reg_actividad['Iva'].replace(/[^0-9-.]/g, ''));
        var totalG = parseFloat(reg_actividad['Total'].replace(/[^0-9-.]/g, ''));
        totRent = totRent + parseFloat(priceProf * 1);
        totIva += parseFloat(priceIva * 1);
        tot += parseFloat(totalG * 1);
    }
    $('#containerT').jqGrid('footerData', 'set', { Vendedor: "TOTALES:" });
    $('#containerT').jqGrid('footerData', 'set', { Valores: "" + formatMoney(totRent) });
    $('#containerT').jqGrid('footerData', 'set', { Iva: "" + formatMoney(totIva) });
    $('#containerT').jqGrid('footerData', 'set', { total: "" + formatMoney(tot) });
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
}*/

function impProforma() {
    //console.log('Impriemindo');
    //obtenerDetalle(row);
    $('#datosPrf').printElement({ pageTitle: "", overrideElementCSS: [{ href: '../../mascaras/model1/estilos/print.css', media: 'print' }] });

}
Object.size = function(obj) {
    var size = 0,
        key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
};

function viewInfo(prf) {
    //console.log(prf);
    var numVentas;
    var next = $("#detaDocu").jqGrid('getCol', 'index', false, 'max');
    next = (isNaN(next) ? 1 : next + 1);
    /*$.getDataJson("", { profDetalleAjax: true, Prf_Cod: prf.Prf_Cod }, function(resProformas) {
        resProformas.numVentasAct.forEach(function(respuest) {
            numVentas = parseFloat(respuest['total']).toFixed(2);
        });
    });
    $.getDataJson("", { profDetalleAjax: true, Prf_Cod: prf.Prf_Cod, Vnd_Cod: prf.Vnd_Cod }, function(resProformas) {
        resProformas.vendedorAct.forEach(function(respuest) {
            //console.log(respuest);
            $('#vendedor').html(respuest['Vendedor']);
        });
    });*/
    $.getDataJson("", { profDetalleAjax: true, Prf_Cod: prf.Prf_Cod, Vnd_Cod: prf.Vnd_Cod  }, function(resProformas) {
        $('#detaDocu').clearGrid();
        resProformas.numVentasAct.forEach(function(respuest) {
            numVentas = parseFloat(respuest['total']).toFixed(2);
        });
        if (numVentas <= 0.00) { $("#detaDocu").jqGrid('addRowData', next, $.extend({ index: next, Cliente: 'No se Encontraron Registros' }), 'last'); }
        resProformas.ventasAct.forEach(function(respuest) {
            //console.log(respuest);
            //var size = Object.keys(respuest).length;
            //var size = Object.size(respuest);
            //var size = Object.values(respuest).length;
            $("#detaDocu").jqGrid('addRowData', next, $.extend(respuest, { index: next, Vet_Cod: respuest['Vet_Cod'], Vet_Num: respuest['Vet_Num'], Vet_Sys: respuest['Caj_Fec'], Cliente: respuest['Cliente'] }), 'last');
            $('#docDetaDialog').dialog('open').updateGridsSizes();

        });
        resProformas.vendedorAct.forEach(function(respuest) {
            //console.log(respuest);
            $('#vendedor').html(respuest['Vendedor']);
        });
    });
    $('#docDetaDialog').setData(prf);
    $('#docDetaDialog').dialog('open');;
}


$("#radsc3").on("click", function() {
    $('#searchProf').find('#desde').removeAttr('disabled');
    $('#searchProf').find('#desde').show();
    $('#searchProf').find('#hasta').removeAttr('disabled');
    $('#searchProf').find('#hasta').show();
    $('#searchProf').find('#h').removeAttr("style");
    $('#searchProf').find('#d').removeAttr("style");
    $('#searchProf').find('#dl').show();
    $('#searchProf').find('#dlh').show();
    $('#searchProf').find('#search').attr('disabled', 'disabled');
});
$('#searchProf').find("#desde").on("change", function() {
    if ($('#searchProf').find('#desde').val() > $('#searchProf').find('#hasta').val()) {
        //console.log('verifica')
        $.alert('El valor de Desde es superior a Hasta');
        $('#desde').val('');
    }

});
$('#searchProf').find("#hasta").on("change", function() {
    if ($('#searchProf').find('#hasta').val() < $('#searchProf').find('#desde').val()) {
        //console.log('verifica')
        $.alert('El valor de Hasta debe ser superior o igual al Desde');
        $('#hasta').val('');
    }


});


$("#radsc1").on("click", function() {
    $('#searchProf').find('#desde').attr('disabled', 'disabled');
    $('#searchProf').find('#hasta').attr('disabled', 'disabled');
    $('#searchProf').find('#search').removeAttr('disabled');
    //$('#desde').toggle();
    $('#searchProf').find('#desde').hide();
    $('#searchProf').find('#dl').hide();
    $('#searchProf').find('#hasta').hide();
    $('#searchProf').find('#dlh').hide();
    $('#searchProf').find('#search').val('');
});
$("#radsc2").on("click", function() {
    $('#searchProf').find('#desde').attr('disabled', 'disabled');
    $('#searchProf').find('#hasta').attr('disabled', 'disabled');
    $('#searchProf').find('#search').removeAttr('disabled');
    //$('#desde').toggle();
    $('#searchProf').find('#desde').hide();
    $('#searchProf').find('#dl').hide();
    $('#searchProf').find('#hasta').hide();
    $('#searchProf').find('#dlh').hide();
    $('#searchProf').find('#search').val('');

});

function cambioVendedor(vnd) {
    console.log('cambioVendedor');
    //console.log(vnd);
    $.getDataJson("", { profDetalleAjax: true, Prf_Cod: vnd.Prf_Cod, Vnd_Cod: vnd.Vnd_Cod }, function(resProformas) {
        resProformas.vendedorAct.forEach(function(respuest) {
            //console.log(respuest);
            $('#Vnd_Cod').html(respuest['Vendedor']);
        });
    });
}

function vendedorCambioP(vendedor) {

    return new Promise((resolve, reject) => {
        $.getDataJson("", { profDetalleAjax: true, Prf_Cod: vendedor.Prf_Cod, Vnd_Cod: vendedor.Vnd_Cod }, function(result) {
            resolve(result.vendedorAct);
            console.log('promessa vendedor');
        }, function(result) {
            reject(result);
        });
    });
}

function vistaDetalleP(proforma) {

    return new Promise((resolve, reject) => {
        $.getDataJson("", { profDetalleAjax: true, Prf_Cod: proforma.Prf_Cod, Vnd_Cod: proforma.Vnd_Cod }, function(result) {
            resolve(result.todasPrf);
            console.log('promesa vista detalle');
        }, function(result) {
            reject(result);
        });
    });
}
async function asyncCallVista(proforma) {
    var ivaDoce = '12';
    var ivaCero = '0';
    var valorIvaD = 0;
    var valorIvaC = 0;
    var docePor = 0;
    var valorPorcentajeMC = 0;
    var valorSubTotal = 0;
    var next = $("#prubaTabla").jqGrid('getCol', 'index', false, 'max');
    next = (isNaN(next) ? 1 : next + 1);
    document.getElementById('t_descuentoD').innerHTML = parseFloat(proforma['Prf_Des']).toFixed(2);
    $('input:text[name=t_descuento]').val(parseFloat(proforma['Prf_Des']).toFixed(2));
    try {
        console.log('Llamando');
        //var result = await vistaDetalleP(proforma);
        var result = await Promise.all([vistaDetalleP(proforma), vendedorCambioP(proforma)]);
        const [mut1, mut2] = await Promise.all(result);
        console.log(mut1, mut2);
        mut2.forEach(function(valor) {
            $('#Vnd_CodD').html(valor['Vendedor']);
        });
        mut1.forEach(function(valor) {
            console.log(valor);
            $("#prubaTabla").jqGrid('addRowData', next, $.extend(valor, { index: next, Prf_Cant: valor['Prf_Cant'], Ite_Lar: valor['Ite_Lar'], Prf_Pru: valor['Prf_Pru'], Prf_Imp: parseFloat(valor['Prf_Imp']).toFixed(2), Prf_ImpT: parseFloat(valor['Prf_Imp']).toFixed(2) }), 'last');

            if (valor['Iva_Por'] > 0) {
                valorIvaD = (valorIvaD + parseFloat(valor['Prf_Imp']));
                valorPorcentajeMC = parseFloat(valor['Iva_Por']);
            } else { valorIvaC = (valorIvaC + parseFloat(valor['Prf_Imp'])); }

            valorSubTotal = (valorSubTotal + parseFloat(valor['Prf_Imp']));
            var fila = "<tr><td>" + valor['Prf_Cant'] + "</td><td>" + valor['Ite_Lar'] + "</td><td>" + valor['Prf_Pru'] + "</td><td>" + parseFloat(valor['Prf_Imp']).toFixed(2) + "</td><td>" + parseFloat(valor['Prf_Imp']).toFixed(2) + "</td></tr>";
            var btn = document.createElement("TR");
            btn.innerHTML = fila;
            document.getElementById("tablitaDos").appendChild(btn);

        });
        docePor = (valorIvaD * valorPorcentajeMC) / 100;
        $('#t_tarifaD').html((docePor).toFixed(2));
        var valorTotal = ((valorSubTotal - parseFloat(proforma['Prf_Des']) + docePor)).toFixed(2);
        $('#t_subtotalD').html(valorSubTotal.toFixed(2));
        $('#t_tarifaC').html(valorIvaC.toFixed(2));
        $('#t_tarifaDC').html(valorIvaD.toFixed(2));
        $('#t_rubrosD').html(valorTotal);
        $('#numProfor').text('Proforma N� ' + proforma['Prf_Num']);

        $('input:text[name=t_subtotal]').val(valorSubTotal.toFixed(2));
        $('input:text[name=t_iva0]').val(valorIvaC.toFixed(2));
        $('input:text[name=t_iva12]').val(valorIvaD.toFixed(2));
        $('input:text[name=t_iva]').val(docePor.toFixed(2));
        $('input:text[name=t_rubros]').val(valorTotal);
        $('#Cop_Obs').val(proforma['Prf_Obs']);
        $("#searchProf").trigger("reloadGrid");

    } catch (error) {
        console.log(error);
    }

}

function detalleVista(prf) {
    console.log('detalle vista ');
    $('#prubaTabla').clearGrid();
    var ivaDoce = '12';
    var ivaCero = '0';
    var valorIvaD = 0;
    var valorIvaC = 0;
    var docePor = 0;
    var valorPorcentajeMC = 0;
    var valorSubTotal = 0;
    var next = $("#prubaTabla").jqGrid('getCol', 'index', false, 'max');
    next = (isNaN(next) ? 1 : next + 1);
    $.getDataJson("", { profDetalleAjax: true, Prf_Cod: prf.Prf_Cod, Vnd_Cod: prf.Vnd_Cod }, function(resProformas) {
        document.getElementById('t_descuentoD').innerHTML = parseFloat(prf['Prf_Des']).toFixed(2);
        $('input:text[name=t_descuento]').val(parseFloat(prf['Prf_Des']).toFixed(2));
        resProformas.todasPrf.forEach(function(valor) {

            //console.log(valor);
            //if (valor['Prf_Iva'] === ivaDoce) {  valorIvaD = (valorIvaD + parseFloat(valor['Prf_Imp']));} else if (valor['Prf_Iva'] === ivaCero) {valorIvaC = (valorIvaC + parseFloat(valor['Prf_Imp'])) ;}

            $("#prubaTabla").jqGrid('addRowData', next, $.extend(valor, { index: next, Prf_Cant: valor['Prf_Cant'], Ite_Lar: valor['Ite_Lar'], Prf_Pru: valor['Prf_Pru'], Prf_Imp: parseFloat(valor['Prf_Imp']).toFixed(2), Prf_ImpT: parseFloat(valor['Prf_Imp']).toFixed(2) }), 'last');

            if (valor['Iva_Por'] > 0) {
                valorIvaD = (valorIvaD + parseFloat(valor['Prf_Imp']));
                valorPorcentajeMC = parseFloat(valor['Iva_Por']);
            } else { valorIvaC = (valorIvaC + parseFloat(valor['Prf_Imp'])); }

            valorSubTotal = (valorSubTotal + parseFloat(valor['Prf_Imp']));
            var fila = "<tr><td>" + valor['Prf_Cant'] + "</td><td>" + valor['Ite_Lar'] + "</td><td>" + valor['Prf_Pru'] + "</td><td>" + parseFloat(valor['Prf_Imp']).toFixed(2) + "</td><td>" + parseFloat(valor['Prf_Imp']).toFixed(2) + "</td></tr>";
            var btn = document.createElement("TR");
            btn.innerHTML = fila;
            document.getElementById("tablitaDos").appendChild(btn);

            //var $grid = $('#prubaTabla');
            //var colSum = $grid.jqGrid('getCol', 'Prf_Imp', false, 'sum');
            //console.log(colSum);
            //$grid.jqGrid('footerData', 'set', { Prf_ImpT:parseFloat(colSum.toFixed(2))});
        });
        //$('.iva_por').html(valorPorcentajeMC);
        docePor = (valorIvaD * valorPorcentajeMC) / 100;
        $('#t_tarifaD').html((docePor).toFixed(2));
        var valorTotal = ((valorSubTotal - parseFloat(prf['Prf_Des']) + docePor)).toFixed(2);
        $('#t_subtotalD').html(valorSubTotal.toFixed(2));
        $('#t_tarifaC').html(valorIvaC.toFixed(2));
        $('#t_tarifaDC').html(valorIvaD.toFixed(2));
        $('#t_rubrosD').html(valorTotal);
        $('#numProfor').text('Proforma N� ' + prf['Prf_Num']);

        $('input:text[name=t_subtotal]').val(valorSubTotal.toFixed(2));
        $('input:text[name=t_iva0]').val(valorIvaC.toFixed(2));
        $('input:text[name=t_iva12]').val(valorIvaD.toFixed(2));
        $('input:text[name=t_iva]').val(docePor.toFixed(2));
        $('input:text[name=t_rubros]').val(valorTotal);
        $('#Cop_Obs').val(prf['Prf_Obs']);

    });
}

function obtenerDetalle(prf) {
    console.log('ObtenerDetalle', prf);

    var valorSubTotal = 0;
    var ivaDoce = '12';
    var ivaCero = '0';
    var valorIvaD = 0;
    var valorIvaC = 0;
    var valorPorcentajeMC = 0;
    var docePor = 12;
    var ivasBs;
    $.getDataJson("", { profDetalleAjax: true, Prf_Cod: prf.Prf_Cod, Vnd_Cod: prf.Vnd_Cod, Prf_Fec: prf.Prf_Fec }, function(resProformas) {
        document.getElementById('t_descuento').innerHTML = parseFloat(prf['Prf_Des']).toFixed(2);
        resProformas.vendedorAct.forEach(function(respuest) {
            //console.log(respuest);
            $('#Vnd_Cod').html(respuest['Vendedor']);
            $('#Vnd_CodD').html(respuest['Vendedor']);
            console.log('obtener detalle 1');

        });
        resProformas.ivas.forEach(function(valorIVa) {
            console.log(valorIVa);
            ivasBs = valorIVa;
            console.log('obtener detalle 2');
        });
        resProformas.todasPrf.forEach(function(valor) {
            //console.log('2');
            //console.log(valor);
            console.log('obtener detalle 3');
            //if (valor['Prf_Iva'] === ivaDoce) {  valorIvaD = (valorIvaD + parseFloat(valor['Prf_Imp']));} else if (valor['Prf_Iva'] === ivaCero) {valorIvaC = (valorIvaC + parseFloat(valor['Prf_Imp'])) ;}

            if (valor['Iva_Por'] > 0) {
                valorIvaD = (valorIvaD + parseFloat(valor['Prf_Imp']));
                valorPorcentajeMC = parseFloat(valor['Iva_Por']);
            } else { valorIvaC = (valorIvaC + parseFloat(valor['Prf_Imp'])); }
            valorSubTotal = (valorSubTotal + parseFloat(valor['Prf_Imp']));
            var fila = "<tr><td>" + valor['Prf_Cant'] + "</td><td  align=left>" + valor['Ite_Lar'] + "</td><td>" + valor['Prf_Pru'] + "</td><td>" + parseFloat(valor['Prf_Imp']).toFixed(2) + "</td><td  align=right>" + parseFloat(valor['Prf_Imp']).toFixed(2) + "</td></tr>";
            var btn = document.createElement("TR");
            btn.innerHTML = fila;
            document.getElementById("tablita").appendChild(btn);

            /*if (valor['Prf_Cod'] === prf['Prf_Cod']) {
                console.log(valor);
                valorSubTotal = (valorSubTotal + parseFloat(valor['Prf_Imp']));
                var fila = "<tr><td>" + valor['Prf_Cant'] + "</td><td>" + valor['Ite_Lar'] + "</td><td>" + valor['Prf_Pru'] + "</td><td>" + valor['Prf_Imp'] + "</td><td>" + valor['Prf_Imp'] + "</td></tr>";
                var btn = document.createElement("TR");
                btn.innerHTML = fila;
                document.getElementById("tablita").appendChild(btn);
            }*/
            cargarDespuesDelAjx(valorPorcentajeMC, docePor, valorIvaD, valorSubTotal, valorIvaC, prf, ivasBs);
        });
        //titleReporte
        /* $('.iva_por').html(valorPorcentajeMC);
        docePor = (valorIvaD * valorPorcentajeMC) / 100;
        $('#t_tarifaDI').html((docePor).toFixed(2));
        var valorTotal = ((valorSubTotal - parseFloat(prf['Prf_Des']) + docePor)).toFixed(2);
        //document.getElementById('t_subtotal').innerHTML = valorSubTotal.toFixed(2);
        //document.getElementById('t_rubros').innerHTML = valorTotal;
        $('#t_subtotal').html(valorSubTotal.toFixed(2));
        $('#t_tarifaCI').html(valorIvaC.toFixed(2));
        $('#t_tarifaDCI').html(valorIvaD.toFixed(2));
        $('#t_rubros').html(valorTotal); */


    });
}

function cargarDespuesDelAjx(valorPorcentajeMC, docePor, valorIvaD, valorSubTotal, valorIvaC, prf, ivasBs) {
    if (valorPorcentajeMC === 0) { $('.iva_por').html(ivasBs['Iva_Por']); } else { $('.iva_por').html(valorPorcentajeMC); }

    docePor = (valorIvaD * valorPorcentajeMC) / 100;
    $('#t_tarifaDI').html((docePor).toFixed(2));
    var valorTotal = ((valorSubTotal - parseFloat(prf['Prf_Des']) + docePor)).toFixed(2);
    //document.getElementById('t_subtotal').innerHTML = valorSubTotal.toFixed(2);
    //document.getElementById('t_rubros').innerHTML = valorTotal;
    $('#t_subtotal').html(valorSubTotal.toFixed(2));
    $('#t_tarifaCI').html(valorIvaC.toFixed(2));
    $('#t_tarifaDCI').html(valorIvaD.toFixed(2));
    $('#t_rubros').html(valorTotal);
    $('#Prf_Obs').html(prf['Prf_Obs']);
    $('#titleReporte').text(prf['Prf_Num']);
}

function clearDocument() {
    //$("tablita").clearGridData();
    var Table = document.getElementById("tablita");
    var vistaTable = document.getElementById("tablitaDos");
    Table.innerHTML = "";
    vistaTable.innerHTML = "";

}

function verProforma(row) {
    //console.log(row);
    $('#prubaTabla').clearGrid();
    var ivaDoce = '12';
    var ivaCero = '0';
    var valorIvaD = 0;
    var valorIvaC = 0;
    var docePor = 0;
    var valorPorcentajeMC = 0;
    var valorSubTotal = 0;
    var next = $("#prubaTabla").jqGrid('getCol', 'index', false, 'max');
    next = (isNaN(next) ? 1 : next + 1);
    document.getElementById('t_descuentoD').innerHTML = parseFloat(row['Prf_Des']).toFixed(2);
    $('input:text[name=t_descuento]').val(parseFloat(row['Prf_Des']).toFixed(2));
    /*  vendedorCambioP(row).then((obj) => {
         $.each(obj, function (ind, val) {
             console.log(val);
             $('#Vnd_CodD').html(val['Vendedor']);

         });
     }).catch((obj) => console.log(obj)); */

    //detalleVista(row);
    //$('#loader').show();
    //asyncCallVista(row); //para ejecutar una funcion esperar que termine y ejecutar otra seguidamente
    /* vistaDetalleP(row).then((obj) => {
        $.each(obj, function (indi, valor) {
            console.log(valor);
            $("#prubaTabla").jqGrid('addRowData', next, $.extend(valor, { index: next, Prf_Cant: valor['Prf_Cant'], Ite_Lar: valor['Ite_Lar'], Prf_Pru: valor['Prf_Pru'], Prf_Imp: parseFloat(valor['Prf_Imp']).toFixed(2), Prf_ImpT: parseFloat(valor['Prf_Imp']).toFixed(2) }), 'last');

            if (valor['Iva_Por'] > 0) { valorIvaD = (valorIvaD + parseFloat(valor['Prf_Imp'])); valorPorcentajeMC = parseFloat(valor['Iva_Por']); } else { valorIvaC = (valorIvaC + parseFloat(valor['Prf_Imp'])); }

            valorSubTotal = (valorSubTotal + parseFloat(valor['Prf_Imp']));
            var fila = "<tr><td>" + valor['Prf_Cant'] + "</td><td>" + valor['Ite_Lar'] + "</td><td>" + valor['Prf_Pru'] + "</td><td>" + parseFloat(valor['Prf_Imp']).toFixed(2) + "</td><td>" + parseFloat(valor['Prf_Imp']).toFixed(2) + "</td></tr>";
            var btn = document.createElement("TR");
            btn.innerHTML = fila;
            document.getElementById("tablitaDos").appendChild(btn);
        });
        docePor = (valorIvaD * valorPorcentajeMC) / 100;
        $('#t_tarifaD').html((docePor).toFixed(2));
        var valorTotal = ((valorSubTotal - parseFloat(row['Prf_Des']) + docePor)).toFixed(2);
        $('#t_subtotalD').html(valorSubTotal.toFixed(2));
        $('#t_tarifaC').html(valorIvaC.toFixed(2));
        $('#t_tarifaDC').html(valorIvaD.toFixed(2));
        $('#t_rubrosD').html(valorTotal);
        $('#numProfor').text('Proforma N� ' + row['Prf_Num']);

        $('input:text[name=t_subtotal]').val(valorSubTotal.toFixed(2));
        $('input:text[name=t_iva0]').val(valorIvaC.toFixed(2));
        $('input:text[name=t_iva12]').val(valorIvaD.toFixed(2));
        $('input:text[name=t_iva]').val(docePor.toFixed(2));
        $('input:text[name=t_rubros]').val(valorTotal);
        $('#Cop_Obs').val(row['Prf_Obs']);
        $("#searchProf").trigger("reloadGrid");

    }).catch((obj) => console.log(obj)); */

    //Ejecutar varias promesas a la ves. (asincrono)
    Promise.all([vendedorCambioP(row), vistaDetalleP(row)]).then(([vendedoPromesa, detallePromes]) => {
        console.log('ejecuta ambas promesas');
        $.each(vendedoPromesa, function(ind, val) {
            //console.log(val);
            $('#Vnd_CodD').html(val['Vendedor']);


        });
        $.each(detallePromes, function(indi, valor) {
            //console.log(valor);
            $("#prubaTabla").jqGrid('addRowData', next, $.extend(valor, { index: next, Prf_Cant: valor['Prf_Cant'], Ite_Lar: valor['Ite_Lar'], Prf_Pru: valor['Prf_Pru'], Prf_Imp: parseFloat(valor['Prf_Imp']).toFixed(2), Prf_ImpT: parseFloat(valor['Prf_Imp']).toFixed(2) }), 'last');

            if (valor['Iva_Por'] > 0) {
                valorIvaD = (valorIvaD + parseFloat(valor['Prf_Imp']));
                valorPorcentajeMC = parseFloat(valor['Iva_Por']);
            } else { valorIvaC = (valorIvaC + parseFloat(valor['Prf_Imp'])); }

            valorSubTotal = (valorSubTotal + parseFloat(valor['Prf_Imp']));
            var fila = "<tr><td>" + valor['Prf_Cant'] + "</td><td>" + valor['Ite_Lar'] + "</td><td>" + valor['Prf_Pru'] + "</td><td>" + parseFloat(valor['Prf_Imp']).toFixed(2) + "</td><td>" + parseFloat(valor['Prf_Imp']).toFixed(2) + "</td></tr>";
            var btn = document.createElement("TR");
            btn.innerHTML = fila;
            document.getElementById("tablitaDos").appendChild(btn);
        });
        docePor = (valorIvaD * valorPorcentajeMC) / 100;
        $('#t_tarifaD').html((docePor).toFixed(2));
        var valorTotal = ((valorSubTotal - parseFloat(row['Prf_Des']) + docePor)).toFixed(2);
        $('#t_subtotalD').html(valorSubTotal.toFixed(2));
        $('#t_tarifaC').html(valorIvaC.toFixed(2));
        $('#t_tarifaDC').html(valorIvaD.toFixed(2));
        $('#t_rubrosD').html(valorTotal);
        $('#numProfor').text('Proforma N� ' + row['Prf_Num']);

        $('input:text[name=t_subtotal]').val(valorSubTotal.toFixed(2));
        $('input:text[name=t_iva0]').val(valorIvaC.toFixed(2));
        $('input:text[name=t_iva12]').val(valorIvaD.toFixed(2));
        $('input:text[name=t_iva]').val(docePor.toFixed(2));
        $('input:text[name=t_rubros]').val(valorTotal);
        $('#Cop_Obs').val(row['Prf_Obs']);
        //$("#searchProf").trigger("reloadGrid");
    }).catch(([vendedoPromesa, detallePromes]) => console.log(vendedoPromesa, detallePromes));


    $('#datosProf').setData(row);
    $('#datosProf2').setData(row);
    $('#allProformas').moveComp('#documentoVistaD').updateGridsSizes();
    obtenerDetalle(row);

}