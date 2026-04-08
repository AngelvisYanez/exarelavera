/*
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */
var liquidaciones;

/* Alta */
var form = {}, cajasTarja = 0, cajasLiqAnteriores = 0, tarjas, liquidacionesTemp;
var prods, cartones, retencion, resumen;
var cajas = [], rets = [], liquidacion = { 'INGRESOS': {}, 'DESCUENTOS': {} };
var tarjasSelected, liquidacionInt;
var totalIngresos, totalDescuentos;

$(function () {
    liquidaciones = $('#liquidaciones');
    /* registar */
    liquidacionesTemp = $('#liquidacionesTemp');
    prods = $("#prods"); tarjas = $('#tarjas'); cartones = $("#cartones"); retencion = $("#retencion"), resumen = $("#resumen");

    if (liquidaciones.length > 0)
        liquidaciones.createGrid({
            caption: 'Liquidaciones',
            orderOptions: [{ label: 'Semana', value: 'Lib_Sem DESC', def: true }, { label: 'Productor', value: 'Prs_Ape' }, { label: 'Fecha', value: 'Lib_Fec DESC' }, { label: 'Numero', value: 'Lib_Num DESC' }], orderMirror: 'input[name=order]', orderId: 'OrderBy',
            height: 250, datatype: "local", selectGridRows: true, bindKeys: false,
            stateCol: 'Lib_Est', stateConfig: { I: 'cellRed2', Cop_Cod: 'cellGreen2' },
            stateCondition: function (row) { if (!$.isEmpty(row['Cop_Cod'])) return "Cop_Cod"; },
            leyenda: [{ icon: 'stop green', label: 'Ya Fue Usado en Compras' }, { icon: 'remove red', label: 'Anulados/Inactivos' }],
            footerrow: true, totalCols: ['Lib_Caj'], totalDefault: { Productor: '<div class="txtRight">TOTAL:<div>' },
            colModel: [
                { label: 'Cód. Int.', name: 'Lib_Cod', width: 15, align: "center", key: true, hidden: true },
                { label: 'Cód. Int.', name: 'Com_Cod', width: 15, align: "center", hidden: true },
                { label: 'Periodo', name: 'Lib_Ano', width: 20, align: "center", classes: 'bgNoRight bgNoColor' },
                { label: 'Semana', name: 'Lib_Sem', width: 20, align: "center", classes: 'bgNoRight bgNoColor' },
                { label: 'Num.', name: 'Lib_Num', width: 30, align: "center", classes: 'bgNoRight bgNoColor' },
                { label: 'Fecha', name: 'Lib_Fec', width: 40, align: "center", classes: 'bgNoRight bgNoColor' },
                { label: 'Marca', name: 'Bam_Nom', width: 100, classes: 'bgNoRight bgNoColor', formatter: 'tags', formatoptions: { type: 'success' } },
                { label: 'Ruc', name: 'Prs_Ced', width: 50, classes: 'bgNoRight bgNoColor' },
                { label: 'Productor', name: 'Productor', width: 75, classes: 'bgNoColor' },
                { label: 'Liquidadas:::', name: 'Lib_Caj', width: 30, classes: 'columnHighlight10', align: "right" },
                { label: $.createIcon('calendar'), name: 'Lib_Sys', width: 25, align: "center", formatter: 'truefalse', formatoptions: { yesMsg: function (o) { return o.Lib_Sys; }, noMsg: ' ', yesIcon: 'info-sign', noIcon: ' ', yesColor: 'blue', noText: true }, title: false }
            ].concat($.isset('buttonExtra') ? buttonExtra : [])
        }, false, '#liquidacionesPager');

    if (tarjas.length > 0)
        tarjas.createGrid({
            caption: 'Tarjas(Recepción Cajas)', height: 125, datatype: "local", footerrow: true, selectGridRows: false, bindKeys: false,
            colModel: [
                { label: 'Cód. Int.', name: 'Prt_Cod', width: 15, align: "center", key: true, hidden: true },
                { label: '&nbsp;', name: 'select', width: 10, align: "center", formatter: 'checkboxExa', formatoptions: { dataEvents: { change: 'updateTotalTarja()' } }, classes: 'bgNoColor bgNoRight' },
                { label: 'Semana', name: 'Prt_Sem', width: 20, align: "center", classes: 'bgNoColor bgNoRight' },
                { label: 'Num.', name: 'Prt_Num', width: 20, align: "center", classes: 'bgNoColor bgNoRight' },
                { label: 'Fecha', name: 'Prt_Fec', width: 70, align: "center", classes: 'bgNoColor' },
                { label: 'Embarcadas', name: 'Prt_Car', width: 35, align: 'right' }
            ]
        }, true);
    if (liquidacionesTemp.length > 0)
        liquidacionesTemp.createGrid({
            caption: 'Liquidaciones Semana', height: 125, datatype: "local", footerrow: true, selectGridRows: false, bindKeys: false,
            colModel: [
                { label: 'Cód. Int.', name: 'Lib_Cod', width: 15, align: "center", key: true, hidden: true },
                { label: 'Semana', name: 'Lib_Sem', width: 30, align: "center", classes: 'bgNoColor bgNoRight' },
                { label: 'Num.', name: 'Lib_Num', width: 30, align: "center", classes: 'bgNoColor bgNoRight' },
                { label: 'Fecha', name: 'Lib_Fec', width: 75, classes: 'bgNoColor' },
                { label: 'Liquidadas', name: 'Lib_Caj', width: 35, align: 'right' }
            ]
        }, true);
    if (prods.length > 0)
        prods.createGrid({
            height: 187, caption: '&nbsp;',
            totalCols: ['Lid_Imp'], totalDefault: { Lid_Pru: '<div class="txtRight">TOTAL:</div>' }, totalNoFormat: ['Lid_Pru'],
            colModel: [
                { label: 'Cod.Int.', name: 'index', key: true, hidden: true, width: 20, align: 'center', classes: 'bgNoColor bgNoRight' },
                { label: 'Cod.Int.', name: 'Pro_Cod', hidden: true, width: 20, align: 'center', classes: 'bgNoColor bgNoRight' },
                { label: 'Detalle', name: 'Producto', width: 100, classes: 'bgNoColor bgNoRight' },
                { label: 'Stock', name: 'Stock', width: 30, align: 'right', classes: 'columnHighlight3 bgNoColor bgNoRight' },
                { label: 'P.Bodega', name: 'Stock_Prp', width: 30, align: 'right', classes: 'columnHighlight3 bgNoColor bgNoRight' },
                { label: 'Cant.', name: 'Lid_Can', width: 30, classes: 'columnHighlight7 bgNoColor bgNoRight', align: 'right', formatter: 'textboxExa', formatoptions: { conditional: function () { var tipo = getActiveTab(); return true;/*(tipo['grupo']!==0);*/ }, dataInit: function (e, o, c) { e.style.textAlign = 'right'; e.style.paddingRight = '5px'; e.type = 'number'; e.min = '0'; if (!$.isUnd(c['Stock'])) { e.value = c['Stock']; e.max = c['Stock']; } e.className += ' nospin'; }, dataEvents: { keyup: 'updateFilaMateriales($(this).data("rowId"));' } } },
                { label: 'Unid.', name: 'Uni_Des', width: 30, align: 'right', classes: 'bgNoColor bgNoRight' },
                { label: 'P.Unit.', name: 'Lid_Pru', width: 40, align: 'right', classes: 'columnHighlight7 bgNoColor', formatter: 'textboxExa', formatoptions: { conditional: function () { var tipo = getActiveTab(); return true;/*(tipo['grupo']!==0)*/; }, dataInit: function (e, o, c) { e.style.textAlign = 'right'; e.style.paddingRight = '5px'; }, dataEvents: { keypress: 'return validar_decimal(event);', keyup: 'updateFilaMateriales($(this).data("rowId"));' } } },
                { label: 'Impor.', name: 'Lid_Imp', width: 50, align: 'right', classes: 'columnHighlight4', formatter: 'currency' },
                {
                    label: '&nbsp;', name: 'act1', width: 25, align: 'center', viewable: false, classes: 'bgNoColor',
                    formatter: function (cellvalue, options, rowObject) {
                        var tipo = getActiveTab();
                        if (tipo['grupo'] !== 0)
                            return '<button type="button" class="btn btn-danger btn-xs btn-frm" title="Eliminar" onclick="deleteItemMaterial(' + rowObject.index + ');"><i class="glyphicon glyphicon-trash"></i></button>';
                        else return '';
                    }
                }
            ],
            footerrow: true, userDataOnFooter: false
        }, true, "#prodsPager").gridButtonsAdd([
            { id: 'btnAddProdMat', classes: 'btnAddProds', caption: 'Agregar Producto', buttonicon: 'glyphicon glyphicon-plus', onClickButton: function () { $('#prodSearchForm').setData($('#btnAddProdMat').data()); $('#prodSearchDialog').dialog('open'); } }
        ]);
    if (cartones.length > 0)
        cartones.createGrid({
            caption: 'Cajas Embarcadas', height: 46, datatype: "local", selectGridRows: false, bindKeys: false,
            totalCols: ['Total', 'Caj_Imp'], totalDefault: { Producto: '<div class="txtRight">CAJAS EMBARCADAS:</div>', Caj_Pru: '<div class="txtRight">IMPORTE:</div>' },
            colModel: [
                { label: 'Cód. Int.', name: 'Pro_Cod', width: 15, align: "center", key: true, hidden: true },
                { label: 'Descripcion', name: 'Producto', width: 75, classes: 'bgNoColor' },
                { label: 'Cantidad', name: 'Total', width: 35, align: 'right', classes: 'columnHighlight7' },
                { label: 'Unid.', name: 'Uni_Des', width: 30, align: 'right', classes: 'bgNoColor bgNoRight' },
                { label: 'P.Unit.', name: 'Caj_Pru', width: 45, align: 'right', classes: 'bgNoColor' },
                { label: 'Importe', name: 'Caj_Imp', width: 45, align: 'right', classes: 'columnHighlight4', formatter: 'currency' }
            ]
        }, true);
    if (retencion.length > 0)
        retencion.createGrid({
            caption: 'Retencion', height: 46, datatype: "local", selectGridRows: false, bindKeys: false,
            totalCols: ['Retenido'], totalDefault: { Importe: '<div class="Importe">TOTAL:</div>' }, totalNoFormat: ['Importe'],
            colModel: [
                { label: 'Cód. Int.', name: 'index', width: 10, align: "center", key: true, hidden: true, classes: 'bgNoColor bgNoRight' },
                { label: 'Descripcion', name: 'Desc', width: 75, classes: 'bgNoColor bgNoRight' },
                { label: 'Porcen.', name: 'Porc', width: 25, align: 'right', classes: 'bgNoColor bgNoRight columnHighlight7', formatter: 'number' },
                { label: 'Cajas', name: 'Cajas', width: 35, align: 'right', classes: 'bgNoColor bgNoRight columnHighlight3' },
                { label: 'Importe', name: 'Importe', width: 50, align: 'right', classes: 'bgNoColor', formatter: 'currency' },
                { label: 'Retenido', name: 'Retenido', width: 40, align: 'right', classes: 'columnHighlight4', formatter: 'currency' }
            ]
        }, true);
    if (resumen.length > 0)
        resumen.createGrid({
            caption: 'Resumen Liquidacion <div class="pull-right"> RETENER:&nbsp;<input type="checkbox" id="ReteSN" name="ReteSN" checked="" class="check-big" onchange="updateRetencion();" /></div>', height: 200, datatype: "local", selectGridRows: false, bindKeys: false, rownumbers: false, footerrow: true,
            colModel: [
                { label: 'Cód. Int.', name: 'index', width: 10, align: "center", key: true, hidden: true },
                { label: 'Grupo', name: 'Grupo', width: 75, hidden: true },
                { label: 'Concepto', name: 'Conc', width: 75, classes: 'bgNoColor bgNoRight bgSumNoRight' },
                { label: 'Deescripcion', name: 'Desc', width: 75, classes: 'bgNoColor bgNoRight bgSumNoRight' },
                { label: 'Cant.', name: 'Cant', width: 35, align: 'right', classes: 'bgNoColor bgNoRight bgSumNoRight' },
                { label: 'Unitario', name: 'Unit', width: 40, align: 'right', classes: 'bgNoColor', summaryType: $.fieldSummary },
                { label: 'Total', name: 'Tota', width: 60, align: 'right', classes: 'columnHighlight4', formatter: 'currency', summaryType: "sum" }
            ],
            grouping: true, groupingView: { groupField: ['Grupo'], groupOrder: ['desc'], groupColumnShow: [false], groupSummary: [true], groupCollapse: false, showSummaryOnHide: true }
        }, true);

    if ($('#tabsLiqui').length > 0) {
        $('#tabsLiqui').tabs({
            cache: false, activate: function (event, ui) {
                ui.newPanel.updateGridsSizes();
                $("#" + ($("#tabsLiqui").tabs('option', 'active') === 0 ? "tabsIngresos" : "tabsDescuentos")).tabs({ active: 0 });
                setTabProds();
            }
        });
        $('#tabsIngresos').tabs({ cache: false, activate: setTabProds });
        $('#tabsDescuentos').tabs({ cache: false, activate: setTabProds });



        setTabProds();
    }
    $('#liquidacion').hide().css('visibility', '');
    $('#Lib_Fec').createDatePickers();

    if ($('#successDialog').length > 0)
        $('#successDialog').createDialog({ height: 300, width: 350, icon: 'ok', buttons: [{ text: "Cerrar", click: function () { $(this).dialog("close"); }, icons: { primary: "ui-icon-closethick" } }] });

    if ($('#provDialog').length > 0)
        $('#provDialog').createSearchDialog({
            colModel: [
                { label: 'C&oacute;d.Int.', name: 'Prd_Cod', key: true, width: 15, align: "center", hidden: true },
                { label: 'C&oacute;d.Int.', name: 'Prv_Cod', width: 15, align: "center", hidden: true },
                { label: 'Códula/RUC', name: 'Prs_Ced', width: 50 },
                { label: 'Productor', name: 'Productor', width: 100 },
                { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: 'selectProvee' } }
            ]
        }, { title: 'Productor' });


    if ($('#prodSearchDialog').length > 0)
        $('#prodSearchDialog').createSearchDialog({
            colModel: [
                { label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, width: 15, align: "center", hidden: false },
                { label: 'Producto', name: 'Producto', width: 100 },
                {
                    label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton',
                    formatoptions: { action: addItemMaterial }
                }
            ], datatype: 'local'
        }, {
            title: 'Materiales', options: [{ label: '&nbsp;&nbsp;Producto&nbsp;&nbsp;', value: 'd' },
            { label: '&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;', value: 'c' }]
        });
    setSemanas();
});
function selectProvee(provee) {
    $('#provFormTemp').setData($.extend(provee, { op_opciones: 'c' }), 'name').find('.dialogSearch').addClass('x');
    $('#provDialog').dialog('close');
}
function setSemanas() {
    var sem = $('#Prt_Sem'), html = '<option value="">Seleccione Semana...</option>';
    if (!sem.length) return;
    for (var i = 1; i <= 52; i++) html += ('<option value="' + i + '">' + i.ordinal(true) + ' Semana </option>');
    sem.html(html);
}
function getActiveTab() {
    var data1 = ['cartones', 'materiales', 'materiales2'];
    var data2 = ['Cartones', 'Material Chico', 'Material Chico Extra'];
    var ingresos = $("#tabsLiqui").tabs('option', 'active') === 0;
    var grupo = ($("#" + (ingresos ? "tabsIngresos" : "tabsDescuentos")).tabs('option', 'active'));
    return { ingresos: ingresos, text: ingresos ? "INGRESOS" : "DESCUENTOS", grupo: grupo, grupoData: data1[grupo], grupoText: data2[grupo] };
}

function setTabProds() {
    var grupo = getActiveTab();
    $('#prodsContainer').attr('class', '');
    $('#prodsContainer').addClass(grupo['ingresos'] ? "jqHeaderFirst jqFirst" : "jqHeaderSecond jqSecond");
    prods.setCaption(grupo['text'] + " - " + grupo['grupoText']);
    prods.setRowsByIndex(liquidacion[grupo['text']][grupo['grupoData']]);
    $('.btnAddProds').hide();
    if (grupo['grupo'] !== 0)
        $('#btnAddProdMat').css('display', '').data({ ingresos: grupo['ingresos'] ? 1 : 0, grupo: grupo['grupo'] });;

}
// editar
/*
function editarLiquidacion(Lib_Cod) {
    $.getDataJson("", { getDetalle: true, Lib_Cod: Lib_Cod }, function (re) {
        console.log(re['dato']);
        createCtaPagar(re.ctaPagar);
        form = re.dato;
        val_caja_bana = form['Lib_Pru'] * 1;
        cajasLiqAnteriores = form['Lib_Cin'] * 1;
        cajasTarja = form['Lib_Caj'] * 1;
        $('#ReteSN').prop('checked', re['retencion'].length > 0);
        cajas = re.cajas;
        //retencion=re.cajas;
        liquidacion['INGRESOS'] = re.ingresos;
        liquidacion['DESCUENTOS'] = re.descuentos;
        $('#formDocumentoMain').setData(form, 'name');
        $('#formDocumentoMain').setData(form);
        $('#Lib_Num').data('Lib_Num', re['dato']['Lib_Num']);
        $('#tabsLiqui').tabs({ active: 0 });
        $('#tabsIngresos').tabs({ active: 0 });
        setTabProds();
        changePruCajas();
        $('#main').moveComp('#liquidacion').updateGridsSizes();
    });
}*/



function editarLiquidacion(Lib_Cod) {
    $.getDataJson("", { getDetalle: true, Lib_Cod: Lib_Cod }, function (re) {
        console.log(re['dato']);
        createCtaPagar(re.ctaPagar);
        form = re.dato;
        val_caja_bana = form['Lib_Pru'] * 1;
        cajasLiqAnteriores = form['Lib_Cin'] * 1;
        cajasTarja = form['Lib_Caj'] * 1;
        var tipo = form['Lib_Mag'];
        $('#racimosForm')[tipo === 'S' ? 'hide' : 'show']();
       // setReteVal(tipo === 'S' ? 0 : 1);
        $('#ReteSN').prop('checked', re['retencion'].length > 0);
        cajas = re.cajas;
        //retencion=re.cajas;
        liquidacion['INGRESOS'] = re.ingresos;
        liquidacion['DESCUENTOS'] = re.descuentos;
        $('#formDocumentoMain').setData(form, 'name');
        $('#formDocumentoMain').setData(form);
        $('#Lib_Num').data('Lib_Num', re['dato']['Lib_Num']);
        $('#tabsLiqui').tabs({ active: 0 });
        $('#tabsIngresos').tabs({ active: 0 });
        setTabProds();
        changePruCajas();
        // updateRacimosCant();
        $('#main').moveComp('#liquidacion').updateGridsSizes();
    });
}





// registrar
function loadTarjas() {
    form = $('#formDocumento').getData();
    if ($.isEmpty(form.Prd_Cod)) return $.alert("Debe escoger un productor!", null, 'alert');
    $.getDataJson('', $.extend({ getDataTarjas: true }, form), function (r) {
        tarjas.setRows(r.tarjas);
        tarjas.selectAllByCol('select', 'S', true);
        liquidacionesTemp.setRows(r.liquidaciones);
        updateTotalLiquidaciones();
        updateTotalTarja();
        return false;
    });
}
function crearLiquidacion() {
    tarjasSelected = [];
    liquidacionInt = liquidacionesTemp.getGridBatch().length + 1;
    var tarjasSelectedGrid = tarjas.getSelectedByCol('select', 'S', true);

    if (tarjasSelectedGrid.length === 0) return $.alert("Debe seleccionar al menos una <u>Tarja</u> a Liquidar!", null, 'alert');
    $.each(tarjasSelectedGrid, function (i, v) { tarjasSelected.push(v['Prt_Cod']); });

    $.getDataJson('', { getDetalleLiq: true, Tar_Cod: tarjasSelected, Prd_Cod: $('input[name=Prd_Cod]').val(), Lib_Ano: form['Lib_Ano'] }, function (r) {
        //            $('#cajasEmbarcadas').html(cajasTarja);
        //            $('#cajasLiquidaciones').html(cajasLiqAnteriores);
        $('#formDocumentoMain').setData({ Lib_Fec: hoy, Lib_Pru: val_caja_bana });
        $('#formDocumentoMain').setData(form, 'name');
        $('#ReteSN').prop('checked', true);
        cajas = r.cajas;
        liquidacion['INGRESOS'] = r.ingresos;
        liquidacion['DESCUENTOS'] = r.descuentos;
        $('#tabsLiqui').tabs({ active: 0 });
        $('#tabsIngresos').tabs({ active: 0 });
        setTabProds();
        changePruCajas();
        validatNum();
        createCtaPagar(r.ctaPagar);
        $('#main').moveComp('#liquidacion').updateGridsSizes();
    });
}
function createCtaPagar(ctas) {
    var html = '';
    if (ctas.length !== 1) html += '<option value="">Seleccione Cuenta..</option>';
    $.each(ctas, function (i, v) { html += '<option value="' + v.Pld_Cod + '">' + v.Pld_Des + '</option>'; });
    $('#Pld_Cod').html(html);
}
function validaDocument() {
    var data = { saveDocument: true, form: $.extend(true, form, $('#formDocumentoMain').getData()), tarjas: tarjasSelected, cajas: cajas, rets: rets, ing: liquidacion.INGRESOS, des: liquidacion.DESCUENTOS };
    data = $.cloneData(data);

    $.arraySpliceFields(data.cajas, ['Adq_Des', 'Cat_Cod', 'Cat_Des', 'Ite_Cor', 'Ite_Est', 'Ite_Lar', 'Iva_Por', 'Mar_Des', 'Ubi_Des', 'Uni_Des']);
    var cols = ['Adq_Cod', 'Adq_Des', 'Cat_Cod', 'Cat_Des', 'Ice_Int', 'Ite_Cod', 'Ite_Cor', 'Ite_Est', 'Ite_Lar', 'Iva_Cod', 'Iva_Por', 'Lin_Cod', 'Mar_Cod', 'Mar_Des', 'Mes_Can', 'Mes_Cod', 'Mes_Int', 'Mes_Tip', 'Pre_Cod', 'Pro_Bar', 'Pro_Cdc', 'Pro_Dsc', 'Pro_Fec', 'Pro_Gen', 'Pro_Ide', 'Pro_Obs', 'Pro_Sys', 'Ubi_Cod', 'Ubi_Des', 'Uni_Cod', 'Uni_Des'];
    $.each(data.ing, function (k1, v1) { $.arraySpliceFields(data.ing[k1], cols); });
    $.each(data.des, function (k1, v1) { $.arraySpliceFields(data.des[k1], cols); });

    data['Pld_Cod'] = $('#Pld_Cod').val();
    data['totalIngresos'] = totalIngresos;
    data['totalDescuentos'] = totalDescuentos;
    data['totalCartIngresos'] = $.arraySumVal(data.ing['cartones'], 'Lid_Imp');
    data['totalCartDescuentos'] = $.arraySumVal(data.des['cartones'], 'Lid_Imp');
    data.form['Lib_Int'] = liquidacionInt;
    data.form['Lib_Cin'] = cajasLiqAnteriores;
    data.form['Lib_Caj'] = cajasTarja;


    console.log(data);
    $.createDialogConfirm('¿Esta seguro de guardar la Liquidacion de Fruta?', data, saveDocument);
}
function saveDocument(data) { //console.log(data); console.log(data['rets']);
    $.saveDataJson('', data,
        function (r) {
            clearSearch();
            $('#liquidacion').moveComp('#main').updateGridsSizes();

            $('#btnImpCompr').data('url', r['linkCompr']);
            $('#btnImpCompr').prop('disabled', $.isUnd(r['linkCompr']));
            $('#successCodigo').html($.isUnd(r['linkCompr']) ? 'NINGUNO' : r['codigo']);

            $('#btnImpLiqui').data('url', r['linkLiqui']);
            $('#btnImpDetal').data('url', r['linkLiquiDet']);
            if (liquidaciones.length > 0)
                liquidaciones.trigger("reloadGrid");

            $('#successDialog').dialog('open');
            return false;
        }
    );
}
function changePruCajas() {
    val_caja_bana = ('0' + $('#Lib_Pru').val()) * 1;
    updateCajasBanano();
}
function updateCajasBanano() {
    //cajasLiqAnteriores=2000;
    $.each(cajas, function (i, v) {
        cajas[i]['Caj_Pru'] = val_caja_bana;
        cajas[i]['Caj_Imp'] = cajas[i]['Total'] * cajas[i]['Caj_Pru'];
    });
    cartones.setRows(cajas);
    cartones.setCaption('Cajas Embarcadas <div class="pull-right">CAJAS ANTERIORES: ' + cajasLiqAnteriores + '<div>');
    updateRetencion();
}
function updateRetencion() {
  
    rets = [];
    if ($('#ReteSN').is(':checked')) {
        var caj = cajasTarja + cajasLiqAnteriores;
       
        $.each(calculadora_bana, function (i, v) {
            if (caj * 1 >= v.Hast) {
                rets.push($.extend(true, { Cajas: v.Hast - v.Desd + 1 }, v));
            } else if (caj * 1 <= v.Hast && caj * 1 >= v.Desd) {
                rets.push($.extend(true, { Cajas: caj - v.Desd + 1 }, v));
            }
        });
        if (cajasLiqAnteriores > 0) {
            var max = rets.length > 0 ? rets.length - 1 : 0;
            for (var i = max; i >= 0; i--) {
                if (rets[i]['Hast'] <= cajasLiqAnteriores)
                    rets.splice(i, 1);
                else
                    if (rets[i]['Desd'] < cajasLiqAnteriores && cajasLiqAnteriores < rets[i]['Hast'])
                        rets[i]['Cajas'] = rets[i]['Cajas'] - (cajasLiqAnteriores - rets[i]['Desd'] + 1);
            }
        }
        $.each(rets, function (i, v) {
            rets[i]['Precio'] = val_caja_bana;
            rets[i]['Importe'] = rets[i]['Cajas'] * val_caja_bana;
            rets[i]['Retenido'] = rets[i]['Importe'] * rets[i]['Porc'] / 100;
        });
        retencion.setRows(rets);
    } else
        retencion.clearGrid();
    retencion.setCaption('Retencion <div class="pull-right">CAJAS ANTERIORES: ' + cajasLiqAnteriores + '<div>');
    updateResumen();
}
function updateResumen() {
    var resu = [], grupos = ['INGRESOS', 'DESCUENTOS'], grupos2 = { cartones: 'Cartones', materiales: 'Material Chico', materiales2: 'Material Chico 2' };
    var totalGlobal = 0;
    totalIngresos = 0; totalDescuentos = 0;
    $.each(cajas, function (i, v) {
        resu.push({ Grupo: grupos[0], Conc: 'Cajas Embarcadas', Cant: v.Total, Desc: v.Producto, Unit: v.Caj_Pru, Tota: v.Caj_Imp });
        totalGlobal += v.Caj_Imp * 1;
    });
    $.each(liquidacion[grupos[0]], function (k, l) {
        var total = 0;
        $.each(l, function (i, v) { total += ($.isUnd(v.Lid_Imp) ? 0 : ('0' + v.Lid_Imp) * 1); });
        //if(total>0){
        resu.push({ Grupo: grupos[0], Conc: grupos2[k], Cant: null, Desc: '', Unit: null, Tota: total });
        totalGlobal += total;
        totalIngresos += total;
        //}
    });
    $.each(rets, function (i, v) {
        resu.push({ Grupo: grupos[1], Conc: 'Retenciones', Cant: v.Cajas, Desc: v.Porc + '% Impuesto a la Renta', Unit: null, Tota: v.Retenido });
        totalGlobal -= v.Retenido * 1;
    });
    $.each(liquidacion[grupos[1]], function (k, l) {
        var total = 0;
        $.each(l, function (i, v) { total += ($.isUnd(v.Lid_Imp) ? 0 : ('0' + v.Lid_Imp) * 1); });
        //if(total>0){
        resu.push({ Grupo: grupos[1], Conc: grupos2[k], Cant: null, Desc: '', Unit: null, Tota: total });
        totalGlobal -= total;
        totalDescuentos += total;
        //}
    });
   
    resumen.setRowsByIndex(resu, 'index');
    resumen.jqGrid('footerData', 'set', { Tota: totalGlobal, Unit: '<div class="txtRight">A PAGAR:</div>' });
}
function updateTotalTarja() {
    var acum = 0;
    var data = tarjas.getSelectedByCol('select', 'S', true);
    $.each(data, function (i, v) { acum += ("0" + v.Prt_Car) * 1; });
    cajasTarja = acum;
    tarjas.footerData('set', { Prt_Fec: '<div class="txtRight">TOTAL:</div>', Prt_Car: cajasTarja });
}
function updateTotalLiquidaciones() {
    cajasLiqAnteriores = liquidacionesTemp.getCol('Lib_Caj', false, 'sum');
    liquidacionesTemp.footerData('set', { Lib_Fec: '<div class="txtRight">TOTAL:</div>', Lib_Caj: cajasLiqAnteriores });
}
function validatNum() {
    var Lib_Cod = $('#Lib_Cod');
    $('#Lib_Num').getValidationJson('', { validaNum: true, Lib_Num: $('#Lib_Num').val(), Lib_Cod: Lib_Cod.length > 0 ? Lib_Cod.val() : undefined }, function (r) {
        var rnum = $('#Lib_Num');
        if (!r['valid']) {
            rnum.fieldValid(false, r['message']);
            if ($.vv(rnum.data('Lib_Num')))
                r['Lib_Num'] = rnum.data('Lib_Num');
        }
        rnum.val(r['Lib_Num']);
        return false;
    });
}
function updateFilaMateriales(index) {
    var grupo = getActiveTab();
    var prod = prods.getRowData(index);
    var Lid_Imp = prod['Lid_Can'] * prod['Lid_Pru'];
    $.extend(liquidacion[grupo['text']][grupo['grupoData']][index], { Lid_Can: prod['Lid_Can'], Lid_Pru: prod['Lid_Pru'], Lid_Imp: Lid_Imp });
    prods.setCell(index, 'Lid_Imp', Lid_Imp);
    prods.loadUpdate();
    updateResumen();
}
function addItemMaterial(prod) {
    var grupo = getActiveTab();
    /*prod['Stock']=0;
    prod['Stock_Prp']=0.00;
    prod['Lid_Can']=1;
    prod['Lid_Pru']=0.00;
    prod['Lid_Imp']=0.00;*/

    liquidacion[grupo['text']][grupo['grupoData']].push(prod);
    prods.setRowsByIndex(liquidacion[grupo['text']][grupo['grupoData']]);
    prods.highlightRow(liquidacion[grupo['text']][grupo['grupoData']].length - 1);
    updateResumen();
}
function deleteItemMaterial(index) {
    var grupo = getActiveTab();
    liquidacion[grupo['text']][grupo['grupoData']].splice(index, 1);
    prods.setRowsByIndex(liquidacion[grupo['text']][grupo['grupoData']]);
    updateResumen();
}
function clearSearch() {
    $('#formDocumento').setData({});
    $('#formDocumento').setData({}, 'name');
    if (liquidacionesTemp.lenght > 0) {
        liquidacionesTemp.clearGrid();
        tarjas.clearGrid();
    }
}
//eliminar
function validaEliminacion(Lib_Cod) {
    var row = liquidaciones.jqGrid('getRowData', Lib_Cod);
    $.createDialogConfirm('¿Esta seguro de <u class="red"><b>ANULAR</b></u> la <b>Liquidacion de Fruta No. ' + row['Lib_Num'] + '</b> al Productor <b><i>' + row['Productor'] + '</i></b>?', { deleteLib: true, Lib_Cod: Lib_Cod, Com_Cod: row['Com_Cod'] || null }, eliminaLiquidacion);
}
function eliminaLiquidacion(data) {
    //console.log(data);
    $.saveDataJson("", data, function (responce) {
        liquidaciones.trigger("reloadGrid");
    });
}
