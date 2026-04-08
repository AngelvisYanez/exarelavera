/*
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */
var jgridResumen, jgridElect, jgridExporta, jgridBusqSri, zipFile;


$(function () {

    jgridResumen = $('#gridResumen');
    jgridElect = $('#gridElectroni');
    jgridExporta = $('#gridExporta');
    // Creo las Tabs
    $("#tabsMain").createTabs({
        init: {
            'tabs-1': function () {
                jgridBusqSri = $("#gridBusSri").createGrid({
                    height: 300, caption: 'Busqueda del Sri', rownumbers: false, pgtext: "Mostrando {0} Documentos.", footerrow: true, userDataOnFooter: false, clearFootRow: true,
                    totalCols: ['IMPORTE_TOTAL'], totalDefault: { FECHA_AUTORIZACION: $.fieldSummarys() },
                    colModel: [
                        { label: '#', name: 'id', key: true, width: 10, align: "center" },
                        { label: 'Ruc Emisor', name: 'RUC_EMISOR', width: 30, align: "center", cellattr: function (rowId, val, rawObject, cm, rdata) { return 'style="' + excelFormats.text + '"'; } },
                        { label: 'Emisor', name: 'RAZON_SOCIAL_EMISOR', width: 50 },
                        { label: 'Fecha', name: 'FECHA_EMISION', width: 25, align: "center" },
                        { label: 'Cod.', width: 10, name: 'TIPO_DOC', align: "center", cellattr: function (rowId, val, rawObject, cm, rdata) { return 'style="' + excelFormats.text + '"'; } },
                        { label: 'Comprobante', name: 'COMPROBANTE', width: 20 },
                        { label: 'Ruc Receptor', name: 'IDENTIFICACION_RECEPTOR', width: 30, cellattr: function (rowId, val, rawObject, cm, rdata) { return 'style="' + excelFormats.text + '"'; } },
                        { label: 'Numero', width: 40, name: 'SERIE_COMPROBANTE', align: "center" },
                        { label: 'Clave Acceso', width: 40, name: 'CLAVE_ACCESO', align: "center", cellattr: function (rowId, val, rawObject, cm, rdata) { return 'style="' + excelFormats.text + '"'; } },
                        { label: 'Autorizacion', width: 40, name: 'NUMERO_AUTORIZACION', align: "center", cellattr: function (rowId, val, rawObject, cm, rdata) { return 'style="' + excelFormats.text + '"'; } },
                        { label: 'Fecha Aut.', name: 'FECHA_AUTORIZACION', width: 25, align: "center" },
                        { label: 'NO OB. IVA', name: 'NOOBIVA', width: 25, align: 'right', formatter: 'currency' },
                        { label: 'Tarifa 5%', name: 'TARIFA5', width: 25, align: 'right', formatter: 'currency' },
                        { label: 'Tarifa 8%', name: 'TARIFA8', width: 25, align: 'right', formatter: 'currency' },                    
                        { label: 'Tarifa 12%', name: 'TARIFA12', width: 25, align: 'right', formatter: 'currency' },
                        { label: 'Tarifa 15%', name: 'TARIFA15', width: 25, align: 'right', formatter: 'currency' },             
                        { label: 'Tarifa 0%', name: 'TARIFA0', width: 25, align: 'right', formatter: 'currency' },
                        { label: 'Iva', name: 'IVA', width: 20, align: 'right', formatter: 'currency' },
                        { label: 'Descuento', name: 'DESCUENTO', width: 20, align: 'right', formatter: 'currency' }, // nuevo campo v2
                        { label: 'Propina', name: 'PROPINA', width: 20, align: 'right', formatter: 'currency' }, // nuevo campo
                        { label: 'Total', name: 'IMPORTE_TOTAL', width: 30, align: 'right', formatter: 'currency' },
                        { label: 'XML', name: 'actXml', width: 20, formatter: 'gridButton', formatoptions: { action: 'downloadFileSri', data: ['CLAVE_ACCESO', { 'type': 'xml' }], title: 'Descargar XML', icon: 'fa-file-code-o', type: 'primary' } },
                        { label: 'PDF', name: 'actXml', width: 20, formatter: 'gridButton', formatoptions: { action: 'downloadFileSri', data: ['CLAVE_ACCESO', { 'type': 'pdf' }], title: 'Descargar PDF', icon: 'fa-file-pdf-o', type: 'primary' } }
                    ],
                    loadComplete: function (data) {
                        var rowData = $(this).jqGrid('getRowData', 0);
                        if ($.varValid(data.rows) && rowData['id'] != null) {
                            colores(true);
                        }
                        $(this).jqGrid('footerData', 'set', {
                            NOOBIVA: $(this).jqGrid('getCol', 'NOOBIVA', true, 'sum'),
                            TARIFA5: $(this).jqGrid('getCol', 'TARIFA5', true, 'sum'),
                            TARIFA8 :$(this).jqGrid('getCol', 'TARIFA8', true, 'sum'),
                            TARIFA12: $(this).jqGrid('getCol', 'TARIFA12', true, 'sum'),
                            TARIFA15: $(this).jqGrid('getCol', 'TARIFA15', true, 'sum'),                           
                            TARIFA0: $(this).jqGrid('getCol', 'TARIFA0', true, 'sum'),
                            IVA: $(this).jqGrid('getCol', 'IVA', true, 'sum'),
                            DESCUENTO: $(this).jqGrid('getCol', 'DESCUENTO', true, 'sum'),
                            PROPINA: $(this).jqGrid('getCol', 'PROPINA', true, 'sum'),
                        }, true);
                    }
                }, true, "#gridBusSriPager").gridButtonsAdd([
                    { caption: ' Imprimir', buttonicon: 'print', onClickButton: function () {
                            jgridBusqSri.jqGrid('printGrid', { nombre: 'Reporte Docs. Electronicos', bodyBorder: false, removeHiddens: true, footer: true, removeCols: [0, 12, 13] });
                        }
                    },
                    { caption: ' Descargar Excel', buttonicon: 'download-alt', onClickButton: function () { jgridBusqSri.jqGrid('exportGridExcel', { nombre: 'Reporte_BusquedaSri', hoja: 'Hoja ATS', caption: true, generated: false, footer: true, print: true, removeHiddens: true, removeCols: [0, 12, 13] }); } },
                    { caption: ' Descargar Comprimido', buttonicon: 'download-alt', onClickButton: downloadAllFilesSri }
                ]);
            },

            'tabs-2': function () {
                jgridBusPre = $("#gridPreCar").createGrid({
                    height: 300, caption: 'Busqueda', rownumbers: false, pgtext: "Mostrando {0} Documentos.", footerrow: true, userDataOnFooter: false, clearFootRow: true,
                    totalCols: ['Carm_Tot'], totalDefault: { FECHA_AUTORIZACION: $.fieldSummarys() },
                    colModel: [
                        { label: 'Cod', name: 'Carm_Id', key: true, width: 10, align: "center" },
                        { label: 'Ruc Emisor', name: 'Carm_Ruc', width: 30, align: "center", cellattr: function (rowId, val, rawObject, cm, rdata) { return 'style="' + excelFormats.text + '"'; } },
                        { label: 'Emisor', name: 'Carm_Emi', width: 50 },
                        { label: 'Fecha', name: 'Carm_Fec', width: 25, align: "center" },
                        { label: 'Cod.', width: 10, name: 'Carm_Cod', align: "center", cellattr: function (rowId, val, rawObject, cm, rdata) { return 'style="' + excelFormats.text + '"'; } },
                        { label: 'Comprobante', name: 'Carm_Com', width: 20 },
                        { label: 'Ruc Receptor', name: 'Carm_Rur', width: 30, cellattr: function (rowId, val, rawObject, cm, rdata) { return 'style="' + excelFormats.text + '"'; } },
                        { label: 'Numero', width: 40, name: 'Carm_Num', align: "center" },
                        { label: 'Clave Acceso', width: 40, name: 'Carm_Cla', align: "center", cellattr: function (rowId, val, rawObject, cm, rdata) { return 'style="' + excelFormats.text + '"'; } },
                        { label: 'Autorizacion', width: 40, name: 'Carm_Aut', align: "center", cellattr: function (rowId, val, rawObject, cm, rdata) { return 'style="' + excelFormats.text + '"'; } },
                        { label: 'Fecha Aut.', name: 'Carm_Fea', width: 25, align: "center" },
                        { label: 'NO OB. IVA', name: 'Carm_NOIVA', width: 25, align: 'right', formatter: 'currency' }, //nuevo campo
                        { label: 'Iva 12%', name: 'Carm_Tard', width: 25, align: 'right', formatter: 'currency' },
                        { label: 'Iva 5%', name: 'Carm_Tarcnco', width: 25, align: 'right', formatter: 'currency' },
                        { label: 'Iva 8%', name: 'Carm_Taroch', width: 25, align: 'right', formatter: 'currency' },                    
                        { label: 'Iva 15%', name: 'Carm_Tarqnce', width: 25, align: 'right', formatter: 'currency' },
                        { label: 'Iva 0%', name: 'Carm_Tarc', width: 25, align: 'right', formatter: 'currency' },
                        { label: 'Iva', name: 'Carm_Iva', width: 20, align: 'right', formatter: 'currency' },
                        { label: 'Descuento', name: 'Carm_Desc', width: 20, align: 'right', formatter: 'currency' }, // nuevo campo v2
                        { label: 'Propina', name: 'Carm_Prop', width: 20, align: 'right', formatter: 'currency' }, // nuevo campo
                        { label: 'Total', name: 'Carm_Tot', width: 20, align: 'right', formatter: 'currency' },
                        { label: 'XML', name: 'actXml', width: 20, formatter: 'gridButton', formatoptions: { action: 'downloadFileSri', data: ['Carm_Cla', { 'type': 'xml' }], title: 'Descargar XML', icon: 'fa-file-code-o', type: 'primary' } },
                        { label: 'PDF', name: 'actXml', width: 20, formatter: 'gridButton', formatoptions: { action: 'downloadFileSri', data: ['Carm_Cla', { 'type': 'pdf' }], title: 'Descargar PDF', icon: 'fa-file-pdf-o', type: 'primary' } },
                        { label: 'Cargar', name: 'act3', width: 15, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: 'cargarFactura', icon: 'plus' } },
                        { label: 'Omitir', name: 'act4', width: 15, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: 'omitirFactura', icon: 'transfer' } }
                    ]
                }, true, "#gridPreCarPager").gridButtonsAdd([
                    { caption: ' Imprimir', buttonicon: 'print', onClickButton: function () { jgridBusPre.jqGrid('printGrid', { nombre: 'Reporte Docs. Electronicos', bodyBorder: false, removeHiddens: true, footer: true, removeCols: [0, 12, 13] }); } },
                    { caption: ' Descargar Excel', buttonicon: 'download-alt', onClickButton: function () { jgridBusPre.jqGrid('exportGridExcel', { nombre: 'Reporte_BusquedaSri', hoja: 'Hoja ATS', caption: true, generated: false, footer: true, print: true, removeHiddens: true, removeCols: [0, 12, 13] }); } },
                    { caption: ' Descargar Comprimido', buttonicon: 'download-alt', onClickButton: downloadAllFilesSri }
                ]);
                fetchData();
            },

            'tabs-3': function () {
                jgridBusqExi = $("#gridCarExi").createGrid({
                    height: 300, caption: 'Busqueda', rownumbers: false, pgtext: "Mostrando {0} Documentos.", footerrow: true, userDataOnFooter: false, clearFootRow: true,
                    totalCols: ['Carm_Tot'], totalDefault: { FECHA_AUTORIZACION: $.fieldSummarys() },
                    colModel: [
                        { label: 'Cod', name: 'Carm_Id', key: true, width: 10, align: "center" },
                        { label: 'Compr', name: 'Com_Num', width: 20, align: "center" },
                        { label: 'Ruc Emisor', name: 'Carm_Ruc', width: 30, align: "center", cellattr: function (rowId, val, rawObject, cm, rdata) { return 'style="' + excelFormats.text + '"'; } },
                        { label: 'Emisor', name: 'Carm_Emi', width: 50 },
                        { label: 'Fecha', name: 'Carm_Fec', width: 25, align: "center" },
                        { label: 'Cod.', width: 10, name: 'Carm_Cod', align: "center", cellattr: function (rowId, val, rawObject, cm, rdata) { return 'style="' + excelFormats.text + '"'; } },
                        { label: 'Comprobante', name: 'Carm_Com', width: 20 },
                        { label: 'Ruc Receptor', name: 'Carm_Rur', width: 30, cellattr: function (rowId, val, rawObject, cm, rdata) { return 'style="' + excelFormats.text + '"'; } },
                        { label: 'Numero', width: 40, name: 'Carm_Num', align: "center" },
                        { label: 'Clave Acceso', width: 40, name: 'Carm_Cla', align: "center", cellattr: function (rowId, val, rawObject, cm, rdata) { return 'style="' + excelFormats.text + '"'; } },
                        { label: 'Autorizacion', width: 40, name: 'Carm_Aut', align: "center", cellattr: function (rowId, val, rawObject, cm, rdata) { return 'style="' + excelFormats.text + '"'; } },
                        { label: 'Fecha Aut.', name: 'Carm_Fea', width: 25, align: "center" },
                        { label: 'NO OB. IVA', name: 'Carm_NOIVA', width: 25, align: 'right', formatter: 'currency' }, //nuevo campo
                        { label: 'Iva 12%', name: 'Carm_Tard', width: 25, align: 'right', formatter: 'currency' },
                        { label: 'Iva 5%', name: 'Carm_Tarcnco', width: 25, align: 'right', formatter: 'currency' },
                        { label: 'Iva 8%', name: 'Carm_Taroch', width: 25, align: 'right', formatter: 'currency' },                    
                        { label: 'Iva 15%', name: 'Carm_Tarqnce', width: 25, align: 'right', formatter: 'currency' },
                        { label: 'Iva 0%', name: 'Carm_Tarc', width: 25, align: 'right', formatter: 'currency' },
                        { label: 'Iva', name: 'Carm_Iva', width: 20, align: 'right', formatter: 'currency' },
                        { label: 'Descuento', name: 'Carm_Desc', width: 20, align: 'right', formatter: 'currency' }, // nuevo campo v2
                        { label: 'Propina', name: 'Carm_Prop', width: 20, align: 'right', formatter: 'currency' }, // nuevo campo
                        { label: 'Total', name: 'Carm_Tot', width: 20, align: 'right', formatter: 'currency' },
                        { label: 'XML', name: 'actXml', width: 20, formatter: 'gridButton', formatoptions: { action: 'downloadFileSri', data: ['Carm_Cla', { 'type': 'xml' }], title: 'Descargar XML', icon: 'fa-file-code-o', type: 'primary' } },
                        { label: 'PDF', name: 'actXml', width: 20, formatter: 'gridButton', formatoptions: { action: 'downloadFileSri', data: ['Carm_Cla', { 'type': 'pdf' }], title: 'Descargar PDF', icon: 'fa-file-pdf-o', type: 'primary' } }
                    ]
                }, true, "#gridCarExiPager").gridButtonsAdd([
                    { caption: ' Imprimir', buttonicon: 'print', onClickButton: function () { jgridBusqExi.jqGrid('printGrid', { nombre: 'Reporte Docs. Electronicos', bodyBorder: false, removeHiddens: true, footer: true, removeCols: [0, 12, 13] }); } },
                    { caption: ' Descargar Excel', buttonicon: 'download-alt', onClickButton: function () { jgridBusqExi.jqGrid('exportGridExcel', { nombre: 'Reporte_BusquedaSri', hoja: 'Hoja ATS', caption: true, generated: false, footer: true, print: true, removeHiddens: true, removeCols: [0, 12, 13] }); } },
                    { caption: ' Descargar Comprimido', buttonicon: 'download-alt', onClickButton: downloadAllFilesSri }
                ]);
                fetchDataExi();
            },

            'tabs-4': function () {
                jgridBusqOmi = $("#gridOmiCar").createGrid({
                    height: 300, caption: 'Busqueda', rownumbers: false, pgtext: "Mostrando {0} Documentos.", footerrow: true, userDataOnFooter: false, clearFootRow: true,
                    totalCols: ['Carm_Tot'], totalDefault: { FECHA_AUTORIZACION: $.fieldSummarys() },
                    colModel: [
                        { label: 'Cod', name: 'Carm_Id', key: true, width: 10, align: "center" },
                        { label: 'Ruc Emisor', name: 'Carm_Ruc', width: 30, align: "center", cellattr: function (rowId, val, rawObject, cm, rdata) { return 'style="' + excelFormats.text + '"'; } },
                        { label: 'Emisor', name: 'Carm_Emi', width: 50 },
                        { label: 'Fecha', name: 'Carm_Fec', width: 25, align: "center" },
                        { label: 'Cod.', width: 10, name: 'Carm_Cod', align: "center", cellattr: function (rowId, val, rawObject, cm, rdata) { return 'style="' + excelFormats.text + '"'; } },
                        { label: 'Comprobante', name: 'Carm_Com', width: 20 },
                        { label: 'Ruc Receptor', name: 'Carm_Rur', width: 30, cellattr: function (rowId, val, rawObject, cm, rdata) { return 'style="' + excelFormats.text + '"'; } },
                        { label: 'Numero', width: 40, name: 'Carm_Num', align: "center" },
                        { label: 'Clave Acceso', width: 40, name: 'Carm_Cla', align: "center", cellattr: function (rowId, val, rawObject, cm, rdata) { return 'style="' + excelFormats.text + '"'; } },
                        { label: 'Autorizacion', width: 40, name: 'Carm_Aut', align: "center", cellattr: function (rowId, val, rawObject, cm, rdata) { return 'style="' + excelFormats.text + '"'; } },
                        { label: 'Fecha Aut.', name: 'Carm_Fea', width: 25, align: "center" },
                        { label: 'NO OB. IVA', name: 'Carm_NOIVA', width: 25, align: 'right', formatter: 'currency' },
                        { label: 'Iva 12%', name: 'Carm_Tard', width: 25, align: 'right', formatter: 'currency' },
                        { label: 'Iva 5%', name: 'Carm_Tarcnco', width: 25, align: 'right', formatter: 'currency' },
                        { label: 'Iva 8%', name: 'Carm_Taroch', width: 25, align: 'right', formatter: 'currency' },                    
                        { label: 'Iva 15%', name: 'Carm_Tarqnce', width: 25, align: 'right', formatter: 'currency' },
                        { label: 'Iva 0%', name: 'Carm_Tarc', width: 25, align: 'right', formatter: 'currency' },
                        { label: 'Iva', name: 'Carm_Iva', width: 20, align: 'right', formatter: 'currency' },
                        { label: 'Descuento', name: 'Carm_Desc', width: 20, align: 'right', formatter: 'currency' }, // nuevo campo v2
                        { label: 'Propina', name: 'Carm_Prop', width: 20, align: 'right', formatter: 'currency' }, // nuevo campo
                        { label: 'Total', name: 'Carm_Tot', width: 20, align: 'right', formatter: 'currency' },
                        { label: 'XML', name: 'actXml', width: 20, formatter: 'gridButton', formatoptions: { action: 'downloadFileSri', data: ['Carm_Cla', { 'type': 'xml' }], title: 'Descargar XML', icon: 'fa-file-code-o', type: 'primary' } },
                        { label: 'PDF', name: 'actXml', width: 20, formatter: 'gridButton', formatoptions: { action: 'downloadFileSri', data: ['Carm_Cla', { 'type': 'pdf' }], title: 'Descargar PDF', icon: 'fa-file-pdf-o', type: 'primary' } },
                        { label: 'PreCar.', name: 'act4', width: 15, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: 'preCargarFactura', icon: 'transfer' } }
                    ]
                }, true, "#gridOmiCarPager").gridButtonsAdd([
                    { caption: ' Imprimir', buttonicon: 'print', onClickButton: function () { jgridBusqOmi.jqGrid('printGrid', { nombre: 'Reporte Docs. Electronicos', bodyBorder: false, removeHiddens: true, footer: true, removeCols: [0, 12, 13] }); } },
                    { caption: ' Descargar Excel', buttonicon: 'download-alt', onClickButton: function () { jgridBusqOmi.jqGrid('exportGridExcel', { nombre: 'Reporte_BusquedaSri', hoja: 'Hoja ATS', caption: true, generated: false, footer: true, print: true, removeHiddens: true, removeCols: [0, 12, 13] }); } },
                    { caption: ' Descargar Comprimido', buttonicon: 'download-alt', onClickButton: downloadAllFilesSri }
                ]);
                fetchDataOmi();
            }


        }
    });
});

//Funcion para omitir las facturas (cambiar el estado a O)
function omitirFactura(row) {
    $.getDataJson('', { 'omitirFactura': true, 'id': row.Carm_Id },
        function (res) {
            $.alert("Se omitio la factura correctamente!")
            fetchData();
        });
}

//Funcion para cambiar el estado a PreCargado de la factura omitida 
function preCargarFactura(row) {
    $.getDataJson('', { 'preCargarFactura': true, 'id': row.Carm_Id },
        function (res) {
            $.alert("Se envio a precargados la factura correctamente!")
            fetchDataOmi();
        });
}

function cargarFactura(row) {
    $.getDataJson('', { 'cargarFactura': true, 'idCarga': row.Carm_Id, 'claCarga': row.Carm_Cla },
        function (res) {
            window.open('../../facturacion/FRONT/fac_alt_fac_com_3.0.php');
        });
}

function exitosasFactura(row) {
    $.getDataJson('', { 'preCargarFactura': true, 'id': row.Carm_Id },
        function (res) {
            $.alert("Se envio a precargados la factura correctamente!")
            fetchDataOmi();
        });
}

//Carga los datos de la tabla carga_masiva en los precargados 
function fetchData() {
    var numero = $('input[name=searchPreCargados]').val();
    var opcion = $('input:checked[name=pre_op_opciones]').val();
    // console.log(opcion);
    var ocultoPre = $('input[name=filtroExtraPre]').val();
    var Fec_Ini = $('input[name=Carm_Ini_Pre]').val();
    var Fec_Fin = $('input[name=Carm_Fin_Pre]').val();
    $.getDataJson('', { 'searchDocument': true, 'num_doc': numero, 'opc': opcion, 'filtroExtraPre': ocultoPre, 'Fec_Ini': Fec_Ini, 'Fec_Fin': Fec_Fin },
        function (res) {
            $('#gridPreCar').setRows(res.rows);
        },
        function (err) { });
    document.getElementById("valid").disabled = false;
}

function fetchDataOmi() {
    var numero = $('input[name=searchOmitidas]').val();
    var opcion = $('input:checked[name=omi_op_opciones]').val();
    // console.log(opcion);
    var ocultoOmi = $('input[name=filtroExtraOmi]').val();
    var Fec_Ini_Omi = $('input[name=Carm_Ini_Omi]').val();
    var Fec_Fin_Omi = $('input[name=Carm_Fin_Omi]').val();
    $.getDataJson('', { 'searchDocumentOmi': true, 'num_doc': numero, 'opc': opcion,'filtroExtraOmi': ocultoOmi, 'Fec_Ini_Omi': Fec_Ini_Omi, 'Fec_Fin_Omi': Fec_Fin_Omi },
        function (res) {
            $('#gridOmiCar').setRows(res.rows);
        },
        function (err) { });
}

function fetchDataExi() {
    var numero = $('input[name=searchExitosas]').val();
    var opcion = $('input:checked[name=exi_op_opciones]').val();
    // console.log(opcion);
    var ocultoExi = $('input[name=filtroExtraExi]').val();
    var Fec_Ini_Exi = $('input[name=Carm_Ini_Exi]').val();
    var Fec_Fin_Exi = $('input[name=Carm_Fin_Exi]').val();

    $.getDataJson('', { 'searchDocumentExi': true, 'num_doc': numero, 'opc': opcion,'filtroExtraExi': ocultoExi, 'Fec_Ini_Exi': Fec_Ini_Exi, 'Fec_Fin_Exi': Fec_Fin_Exi },
        function (res) {
            $('#gridCarExi').setRows(res.rows);
        },
        function (err) { });
}

function sumNotNC(v, n, obj) { return isNaN(v) ? 0 : (obj['tipo'] === '04' ? -1 * v : v); }
// ---- REVISAR ATSs - BUSQUEDA DE DATOS EN ATSs
function clearResumen() { jgridResumen.jqGrid("clearGridData"); }

function loadResumenXML() {
    clearResumen();
    $("#formResumen").effect("highlight", {}, 500);
    var retencion = ['retencion', 'aut_retencion', 'valret'], det_retencion = ['codsri', 'porrenta', 'renta', 'iva10', 'iva20', 'iva30', 'iva70', 'iva100'], type = $("#changeGroupGrid").val();
    jgridResumen.gridColUpdate('show', ['ruc', 'prov', 'fecha', 'sustento', 'tipo_union', 'documento', 'autorizacion', 'periodo'].concat(retencion).concat(det_retencion));
    if (!$('#setRetencion').is(':checked')) jgridResumen.gridColUpdate('hide', retencion);
    if (!$('#setDetalleRet').is(':checked')) jgridResumen.gridColUpdate('hide', det_retencion);
    jgridResumen.jqGrid('groupingGroupBy', ["idEmpresa"]);
    if ($('#isAgrupado').is(':checked')) {
        var hiddens = ['sustento', 'documento', 'autorizacion'].concat(['retencion', 'aut_retencion', 'codsri', 'porrenta']);
        switch (type) {
            case 'fecha': jgridResumen.gridColUpdate('hide', hiddens.concat(['ruc', 'prov', 'tipo_union', 'periodo'])); break;
            case 'tipo_long': jgridResumen.gridColUpdate('hide', hiddens.concat(['ruc', 'prov', 'fecha', 'periodo'])); break;
            case 'ruc': jgridResumen.gridColUpdate('hide', hiddens.concat(['fecha', 'tipo_union', 'periodo'])); break;
            case 'periodo': jgridResumen.gridColUpdate('hide', hiddens.concat(['ruc', 'prov', 'tipo_union', 'fecha'])); break;
        }
    } else {
        switch (type) {
            case 'fecha': jgridResumen.gridColUpdate('hide', ['fecha']); break;
            case 'tipo_long': jgridResumen.gridColUpdate('hide', ['tipo_union']); break;
            case 'ruc': jgridResumen.gridColUpdate('hide', ['ruc', 'prov']); break;
            case 'periodo': jgridResumen.gridColUpdate('hide', ['periodo']); break;
        }
        if (type !== 'clear') jgridResumen.jqGrid('groupingGroupBy', ["idEmpresa", type]);
    }
    $.postMultiPartJson('', $.getFormData("formResumen"), function (r) {
        jgridResumen.setRowsByIndex(r['rows'], 'id');//jgridResumen.setRowsByIndex(r['rows'],'id');
    }, clearResumen, clearResumen);
}
function setResumen() {
    var grp = $("#changeGroupGrid");
    grp.val(this.is(':checked') ? 'ruc' : 'clear');
    grp.find('option[value=clear]').prop('disabled', this.is(':checked'));
}
// ---- CONVERTIR UN ATS AL ULTIMO FORMATO
var archivos = [], editorConvert;
function loadAtsConvertXML() {
    $('#editorConvertTitle').html('');
    $('#archivos').html('');
    editorConvert.setValue("", -1);
    $("#formConvertAts").effect("highlight", {}, 500);
    $.postMultiPartJson('', $.getFormData("formConvertAts"), function (r) {
        var options = '';
        archivos = r['rows'];
        for (var i = 0; i < (archivos.length); i++) {
            options = options + '<option value="' + archivos[i]['nombre'] + '">' + archivos[i]['nombre'] + '</option>';
        }
        $('#archivos').html(options);
        $('#editorConvertTitle').html(archivos[0]['nombre']);
        editorConvert.setValue(vkbeautify.xml(archivos[0]['xml']), -1);
    });
}
function setArchivo(nombre) {
    $('#editorConvertTitle').html('');
    for (var i = 0; i < (archivos.length); i++) {
        if (archivos[i]['nombre'] === nombre) {
            $('#editorConvertTitle').html(nombre);
            editorConvert.setValue(vkbeautify.xml(archivos[i]['xml']), -1);
            break;
        }
    }
}
/*
$(document).ready(function () {
    $("#editorConvert").css('height', '350px');
    editorConvert = ace.edit("editorConvert");
    editorConvert.setTheme("ace/theme/sqlserver");
    editorConvert.session.setMode("ace/mode/xml");
});*/
// ---- REVISAR UN XML CUALQUIERA EN EL EDITOR
var editor, nameFile = 'ninguno.xml';
function loadXML() {
    $("#formXml").effect("highlight", {}, 500);
    var reader = new FileReader();
    reader.onload = function (e) {
        nameFile = document.getElementById("archivoXML").value.replace(/.*[\/\\]/, '');
        editor.setValue(vkbeautify.xml(reader.result), -1);
        $('#editorTitle').html(nameFile);
    };
    reader.readAsText(document.getElementById("archivoXML").files[0]);
}
/*
$(document).ready(function () {
    $("#editor").css('height', '350px');
    editor = ace.edit("editor");
    editor.setTheme("ace/theme/sqlserver");
    editor.session.setMode("ace/mode/xml");
    editor.$blockScrolling = Infinity;
});*/
// ---- REVISAR DOCUMENTOS ELECTRONICOS
function clearElect() { jgridElect.jqGrid("clearGridData"); jgridElect.jqGrid("setCaption", ' '); }
function loadElectronicos() {
    $("#formElectronicos").effect("highlight", {}, 500);
    $.postMultiPartJson('', $.getFormData("formElectronicos"), function (r) {
        jgridElect.jqGrid("setCaption", r['empresa']);
        jgridElect.setRows(r['rows']);
    }, clearElect, clearElect);
}
// ---- ATS DE EXPORTACIONES - REVISA EXPORTACIONES
function clearExporta() { jgridExporta.jqGrid("clearGridData"); jgridExporta.jqGrid("setCaption", ' '); }
function loadExporta() {
    $("#formExporta").effect("highlight", {}, 500);
    $.postMultiPartJson('', $.getFormData("formExporta"), function (r) {
        jgridExporta.jqGrid("setCaption", r['empresa']);
        jgridExporta.setRows(r['rows']);
    }, clearExporta, clearExporta);
}
// ---- DEVOLUCION DE IVA Y REVISAR IVA
var gridDevol;
function clearDevolIva() { gridDevol.jqGrid("clearGridData"); }
function loadDevolXML() {
    $("#formDevolIva").effect("highlight", {}, 500);
    $.postMultiPartJson('', $.getFormData("formDevolIva"), function (r) {
        gridDevol.jqGrid("setCaption", r['empresa']);
        gridDevol.setRowsByIndex(r['rows'], 'id');
    }, clearDevolIva, clearDevolIva);
}
function deleteRow(id) { //console.log(id);
    gridDevol.delRowData(id);
    gridDevol.setGridSummary(['ivadev', 'base', 'iva'], { autret: '<div style="text-align:right">TOTALES:</div>' });
}
function tagXml(tag, val) { return `<${tag}>${val}</${tag}>`; }
function createAts(ruc, anio, mes, xml) { return '<' + '?xml version="1.0" encoding="ISO-8859-1" standalone="yes"?><devIva><numeroRuc>' + ruc + '</numeroRuc><anio>' + anio + '</anio><mes>' + mes + '</mes><compras>' + xml + '</compras><importaciones><importacionesBienes><baseImponible>0</baseImponible><montoIva>0</montoIva></importacionesBienes><importacionesActivosFijos><baseImponible>0</baseImponible><montoIva>0</montoIva></importacionesActivosFijos></importaciones></devIva>'; }
function saveXml() {
    $("#loader").show();
    var data = gridDevol.getGridBatch(), ats = "";
    $.each(data, function (i, v) {
        ats += "<detalleCompras>";
        ats += tagXml('codSustento', v['sustento']);
        ats += tagXml('tpIdProv', v['tpIdProv']);
        ats += tagXml('idProv', v['ruc']);
        ats += tagXml('tipoComprobante', v['tipo']);
        ats += tagXml('fechaRegistro', v['fecha']);
        ats += tagXml('establecimiento', v['estab']);
        ats += tagXml('puntoEmision', v['impre']);
        ats += tagXml('secuencial', v['documento']);
        ats += tagXml('autorizacion', v['autorizacion']);
        ats += tagXml('baseImponible', v['base']);
        ats += tagXml('montoIva', v['iva']);
        ats += tagXml('ivaSolicitado', v['ivadev']);
        ats += "</detalleCompras>";
    });
    $.downloadFile(createAts(data[0]['empresa'], data[0]['anio'], data[0]['mes'], ats), 'Devolucion_Iva_' + data[0]['empresa'] + '_' + data[0]['anio'] + '_' + data[0]['mes'] + '.xml');
    $("#loader").hide();
}



function colores(subtotalesPeticion) {
    var totalRows = $("#gridBusSri").getGridParam("reccount");
    var increase = 100 / totalRows;
    var porcen = 0;
    $('#process').css('display', 'block');
    $("#gridBusSri tbody tr[role='row']").each(function (i) {
        var codFila = $(this).attr('id');
        var claveAcceso = $(this).find("td[aria-describedby='gridBusSri_CLAVE_ACCESO']").text();
        var numero = $(this).find("td[aria-describedby='gridBusSri_SERIE_COMPROBANTE']").text();
        var cedula = $(this).find("td[aria-describedby='gridBusSri_RUC_EMISOR']").text();
        var estado = 'A';
        // console.log(claveAcceso + " " + numero + " " + cedula + " " + subtotalesPeticion);
        if (numero) {
            $.getDataJson("",
                {
                    colores: true,
                    clave: claveAcceso,
                    cop_num: numero,
                    prs_ced: cedula,
                    subtotalesPeti: subtotalesPeticion
                },
                function (responce) {
                    // console.log(responce);
                    porcen = porcen + increase;
                    $('.progress-bar').css('width', porcen + '%');
                    if (porcen > 99.99) {
                        document.getElementById("cargardatos").disabled = false;
                        $.alert("Carga exitosa!");
                        $('#process').css('display', 'none');
                    }
                    var filas = responce.rows;
                    var filasCompras = responce.rows2;
                    //REMOVER Y ESTABLECER LOS COLORES 
                    $("#" + codFila).removeClass(' myAltRowClass cellBlue2 cellGreen2 cellRed2 cellOrange2');
                    if (filasCompras.length > 0) {
                        $("#" + codFila).addClass(' myAltRowClass cellGreen2 ');
                    } else if (filas.length > 0) {
                        estado = filas[0]['Carm_Est'];
                        if (estado == 'P') $("#" + codFila).addClass(' myAltRowClass cellBlue2 ');
                        if (estado == 'C') $("#" + codFila).addClass(' myAltRowClass cellGreen2 ');
                        if (estado == 'O') $("#" + codFila).addClass(' myAltRowClass cellRed2 ');
                    } else {
                        $("#" + codFila).removeClass('myAltRowClass');
                    }
                }, function (r) {
                    $("#" + codFila).removeClass('myAltRowClass');
                    porcen = porcen + increase;
                    if (porcen > 99.99) {
                        document.getElementById("cargardatos").disabled = false;
                        $('#process').css('display', 'none');
                    }
                });
        }

    });
}

function validar() {
    $.getDataJson("", { validar: true }, function (responce) {
        $.alert("Se validaron correctamente los registros!");
        fetchData();
    });
}

function validarPro() {
    var xhttp;
    xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function () {
        if (this.readyState == 4 && this.status == 200) {
            $.alert("Se validaron correctamente los registros!");
            fetchData();
        }
    };
    var fec_ini = $("#Carm_Ini_Pre").val();
    var fec_fin = $("#Carm_Fin_Pre").val();
    xhttp.open("GET", "/tesoreria/FRONT/tes_alt_carga_masiva1.0.php?validar=true&fec_ini=" + fec_ini + "&fec_fin=" + fec_fin, true);
    //xhttp.open("GET", "/tesoreria/FRONT/tes_alt_carga_masiva.php?validar=true", true);
    xhttp.send();
}

function preGuardarCargados() {
    var datosTabla = $('#gridBusSri').getGridParam('data');
    preGuardar(datosTabla);
}

function preGuardar(datos) {
    $.saveDataJson("", { preGuardar: true, datosTabla: datos }, function (responce) {
        $.alert("Se preguardo correctamente !");
        //colores(false);
        return false;
    });
}

function preGuardarPro() {
    var totalRows = $("#gridBusSri tbody tr[role='row']").not(".myAltRowClass").length - 1;
    var increase = 100 / totalRows;
    var porcen = 0;
    $('#process').css('display', 'block');
    $('.progress-bar').css('width', 0 + '%');
    $("#gridBusSri tbody tr[role='row']").not(".myAltRowClass").each(function (i) {
        var fila = new Object();
        fila["RUC_EMISOR"] = $(this).find("td[aria-describedby='gridBusSri_RUC_EMISOR']").text();
        fila["RAZON_SOCIAL_EMISOR"] = $(this).find("td[aria-describedby='gridBusSri_RAZON_SOCIAL_EMISOR']").text();
        fila["FECHA_EMISION"] = $(this).find("td[aria-describedby='gridBusSri_FECHA_EMISION']").text();
        fila["IDENTIFICACION_RECEPTOR"] = $(this).find("td[aria-describedby='gridBusSri_IDENTIFICACION_RECEPTOR']").text();
        fila["TIPO_DOC"] = $(this).find("td[aria-describedby='gridBusSri_TIPO_DOC']").text();
        fila["COMPROBANTE"] = $(this).find("td[aria-describedby='gridBusSri_COMPROBANTE']").text();
        fila["SERIE_COMPROBANTE"] = $(this).find("td[aria-describedby='gridBusSri_SERIE_COMPROBANTE']").text();
        fila["IDENTIFICACION_RECEPTOR"] = $(this).find("td[aria-describedby='gridBusSri_IDENTIFICACION_RECEPTOR']").text();
        fila["CLAVE_ACCESO"] = $(this).find("td[aria-describedby='gridBusSri_CLAVE_ACCESO']").text();
        fila["NUMERO_AUTORIZACION"] = $(this).find("td[aria-describedby='gridBusSri_NUMERO_AUTORIZACION']").text();
        fila["FECHA_AUTORIZACION"] = $(this).find("td[aria-describedby='gridBusSri_FECHA_AUTORIZACION']").text();
        
        fila["NOOBIVA"] = parseFloat($(this).find("td[aria-describedby='gridBusSri_NOOBIVA']").text().replace(/[$]/, ""));
        fila["TARIFA12"] = parseFloat($(this).find("td[aria-describedby='gridBusSri_TARIFA12']").text().replace(/[$]/, ""));
        fila["TARIFA15"] = parseFloat($(this).find("td[aria-describedby='gridBusSri_TARIFA15']").text().replace(/[$]/, ""));
        fila["TARIFA8"] = parseFloat($(this).find("td[aria-describedby='gridBusSri_TARIFA8']").text().replace(/[$]/, ""));
        fila["TARIFA5"] = parseFloat($(this).find("td[aria-describedby='gridBusSri_TARIFA5']").text().replace(/[$]/, ""));
        fila["TARIFA0"] = parseFloat($(this).find("td[aria-describedby='gridBusSri_TARIFA0']").text().replace(/[$]/, ""));
        fila["IVA"] = parseFloat($(this).find("td[aria-describedby='gridBusSri_IVA']").text().replace(/[$]/, ""));

        fila["DESCUENTO"] = parseFloat($(this).find("td[aria-describedby='gridBusSri_DESCUENTO']").text().replace(/[$]/, "")); // nuevo campo v2
        fila["PROPINA"] = parseFloat($(this).find("td[aria-describedby='gridBusSri_PROPINA']").text().replace(/[$]/, "")); // nuevo campo
        fila["IMPORTE_TOTAL"] = parseFloat($(this).find("td[aria-describedby='gridBusSri_IMPORTE_TOTAL']").text().replace(/[$]/, ""));
        if (fila["RUC_EMISOR"]) {
            $.getDataJson("", { preGuardarPro: true, dataFila: fila }, function (responce) {
                porcen = porcen + increase;
                $('.progress-bar').css('width', porcen + '%');
                if (porcen > 99.99) {
                    $.alert("Precargado terminado!");
                    $('#process').css('display', 'none')
                }
            });
        }
    });
}

// cargar Busqueda Sri 1
function loadBusquedaSri1() {
    document.getElementById("cargardatos").disabled = true;
    $("#formBusquedaSri1").effect("highlight", {}, 500);
    // console.log($.getFormData("formBusquedaSri1"));
    $.postMultiPartJson('', $.getFormData("formBusquedaSri1"), function (r) {
        console.log(r);
        if (!r.success) {
            $.alert(r.message);
        }
        jgridBusqSri.jqGrid("setCaption", "Busqueda del Sri: " + r['file']);
        jgridBusqSri[0].p['file'] = r['file'];
        jgridBusqSri.setRowsByIndex(r['rows'], 'id');
    }, clearElect, clearElect);
}

// Create Base64 Object
var base64toBlob = function (r, e, n) { e = e || "", n = n || 512; for (var t = atob(r), a = [], o = 0; o < t.length; o += n) { for (var l = t.slice(o, o + n), h = new Array(l.length), b = 0; b < l.length; b++)h[b] = l.charCodeAt(b); var v = new Uint8Array(h); a.push(v); } return new Blob(a, { type: e }); };
function downloadFileSri(data) {
    // console.log(data);
    data['downloadFileSri'] = true;
    $.getDataJson("", data, function (r) {
        var blob = base64toBlob(r[data.type], data.type === 'pdf' ? 'application/pdf' : "text/plain;charset=utf-8");
        saveAs(blob, r.name);
    }, function (r) { return $.alert("No se logro descargar el archivo, " + r.error + "!"); });
}

var files = 0;
function notFile(clave) { zipFile.file(clave + ".txt", "No se logro descargar este xml!"); return false; }
function downloadAllFilesSri() {
    if ($.isUnd(jgridBusqSri[0].p['file'])) return $.alert("Debe cargar un archivo de busqueda!");
    var datos = jgridBusqSri.getGridBatch();
    if (datos.length === 0) return $.alert("No existen archivos para descargar!");
    $('#loader').show();
    files = datos.length;
    zipFile = new JSZip();
    zipFile.file(jgridBusqSri[0].p['file'] + ".xls", new Blob([jgridBusqSri.jqGrid('exportGridElement', { removeHiddens: true, removeCols: [0, 12, 13], footer: true }).exportarExcelBlob('Listado')], { type: 'text/plain' }));
    $.each(datos, function (i, v) {
        var data = { downloadFileSri: true, CLAVE_ACCESO: v.CLAVE_ACCESO, type: 'all' };
        $.getDataJson("", data, function (r) {
            zipFile.file(v.CLAVE_ACCESO + ".xml", base64toBlob(r.xml, "text/plain;charset=utf-8"));
            if (r.pdf !== '')
                zipFile.file(v.CLAVE_ACCESO + ".pdf", base64toBlob(r.pdf, "application/pdf"));
        }, function () { return notFile(v.CLAVE_ACCESO); }, function () { return notFile(v.CLAVE_ACCESO); }, function () {
            files--;
            if (files <= 0) {
                zipFile.generateAsync({ type: "blob" }).then(function (blob) {
                    saveAs(blob, jgridBusqSri[0].p['file'] + ".zip");
                    $('#loader').fadeOut();
                }, function (err) { $.alert(err); $('#loader').fadeOut(); });
            }
        });
    });
}