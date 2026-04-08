/* 
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */
var data_guia = [], docs, items, index;
var compGrid, searchGrid;
$(function () {
    /* formulario ingreso/edicion */
    docs = $('#documentos');
    if (docs.length > 0) {
        items = $('#items');
        if ($('#successDialog').length > 0) $('#successDialog').createDialog({ height: 160, width: 350, icon: 'ok', buttons: [{ text: "Cerrar", click: function () { $(this).dialog("close"); }, icons: { primary: "ui-icon-closethick" } }] });

        //Visualizar el formulario para editar transportista  y destinatario
        if ($('#guiaPersonaDialogEdit').length > 0) $('#guiaPersonaDialogEdit').createDialog({ icon: 'plus', width: 500, height: 430 });
        if ($('#guiaPersonaDialog').length > 0) $('#guiaPersonaDialog').createDialog({ icon: 'plus', width: 500, height: 430 });


        docs.createGrid({
            data: [],
            caption: "Destinatarios",
            rowNum: 10000000,
            height: 'auto', rownumbers: false,
            onSelectRow: function (index) { selectDoc(index); },
            ondblClickRow: function () { return false; },
            colModel: [
                { label: '<i class="glyphicon glyphicon-pencil"></i>', name: 'Vet_Hour', width: 20, align: 'center', formatter: 'gridButton', formatoptions: { action: createDoc, data: function (o) { return o.Gui_Index; }, icon: 'pencil' } },
                { label: 'Cód.Int.', name: 'Gui_Index', key: true, width: 15, align: "center", hidden: true },
                { label: 'Destinat.', name: 'Prs_Ced', width: 50, align: "center", formatter: 'title', formatoptions: { title: 'destinatario' } },
                { label: 'Codigos', name: 'Gui_Ces', width: 20, title: false, align: 'center', formatter: 'codigos' },
                { label: 'Docume.', name: 'Vet_Num', width: 20, title: false, align: 'center', formatter: 'documento' },
                { label: 'Descrip', name: 'Gui_Dde', width: 20, title: false, align: 'center', formatter: 'descrip' },
                { label: 'Items', name: 'items', width: 25, align: 'center', formatter: 'conteo' },
                { label: '<i class="glyphicon glyphicon-remove"></i>', name: 'Vet_Hour', width: 20, align: 'center', formatter: 'gridButton', formatoptions: { action: deleteDoc, data: function (o) { return o.Gui_Index; }, icon: 'remove', type: 'danger' } }
            ]
        }, true, 'documentosPager', { view: false }).gridButtonsAdd([{ id: 'addDocBtn', caption: 'Agregar Destinatario', buttonicon: 'glyphicon glyphicon-plus', onClickButton: function (index) { createDoc(); } }]).unbind("contextmenu");
        $('#documentosPager_center').css('width', '0px');
        items.createGrid({
            data: [], caption: "Detalle Guia Destinatario",
            rowNum: 10000000, height: 'auto', footerrow: false, headertitles: true, selectGridRows: false,
            colModel: [
                { name: 'select', label: '<i class="glyphicon glyphicon-check"></i>', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: openItemSelector, icon: 'check', title: 'Seleccionar Item', data: function (o) { return o.index; } }, resizable: false },
                { name: 'Gui_Index', label: 'Gui_Index', width: 40, align: 'center', hidden: true },
                { name: 'index', label: 'Index', width: 20, sorttype: "int", align: 'center', key: true, hidden: true },
                { name: 'Pro_Cod', label: 'Cód.Int.', width: 20, sorttype: "int", align: 'center', hidden: true },
                { name: 'Pro_Bar', label: 'Barras', width: 50, sorttype: "int", align: 'left', title: false, editable: true, editoptions: { dataInit: styleBar } },
                { name: 'Gde_Can', label: 'Cant.', labelLong: 'Cantidad', width: 40, align: "right", title: false, editable: true, editoptions: { dataInit: styleCant } },
                { name: 'Uni_Des', label: 'Uni.', labelLong: 'Unidad', width: 25, resizable: false },
                { name: 'product', label: 'Descripción', width: 150 },
                { name: 'Adq_Cod', label: 'CodAdq', width: 20, hidden: true },
                { name: 'Adq_Cor', label: 'Adq.', labelLong: 'Adquisiciones', width: 20, align: "center", title: false, formatter: 'title', formatoptions: { title: function (o) { return o['Adq_Des']; } }, resizable: false },
                { name: 'delete', label: '<i class="glyphicon glyphicon-remove"></i>', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: deleteItem, icon: 'remove', title: 'Eliminar Item', type: 'danger', data: function (o) { return o.index; }, attr: { 'tabindex': '-1' }, conditional: function (o) { return !(!$.varValid(o['Pro_Cod']) || o['Pro_Cod'] === ''); } }, resizable: false }
            ]
        }, true, 'itemsPager', { view: false }).gridButtonsAdd([
            { caption: 'Agregar Productos', buttonicon: 'glyphicon glyphicon-plus', onClickButton: function () { if (!isDocSelected()) return; if (!available()) { $.alert('No hay espacio para mas items en este documento!'); return; } index = 0; $('#proDialog').dialog('open'); } },
            { caption: 'Remover Todos', buttonicon: 'glyphicon glyphicon-remove', onClickButton: function () { if (!isDocSelected()) return; items.clearGrid(); $.arrayGetItem(data_guia, 'Gui_Index', $('#Gui_Index').val())['items'] = []; addItem({}); } }
        ]);

        $.createDateRange('#Gui_Fei', '#Gui_Fef', 0);
        $('#Gui_Fei').on('change', function () { $('#Gui_Fec').val(this.value); });

        // DIALOG BUSCAR proveedor 
        if ($('#transDialog').length > 0)
            $.createSearchDialog('transDialog', [
                { label: 'C&oacute;d.Int.', name: 'Gpe_Cod', key: true, width: 15, align: "center", hidden: false },
                { label: 'Cédula/RUC', name: 'Prs_Ced', width: 50 },
                { label: 'Transportista', name: 'transportista', width: 100 },
                { label: 'Placa', name: 'Gpe_Pla', width: 50, align: "center" },
                { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectTrans } }
            ], null, null, null, { headertitles: true }, { title: 'Proveedor', text: 'Prs_Ced' });
        // DIALOG BUSCAR proveedor 
        if ($('#destiDialog').length > 0)
            $.createSearchDialog('destiDialog', [
                { label: 'C&oacute;d.Int.', name: 'Gpe_Cod', key: true, width: 15, align: "center", hidden: false },
                { label: 'Cédula/RUC', name: 'Prs_Ced', width: 50 },
                { label: 'Destinatario', name: 'destinatario', width: 100 },
                { label: 'Establecimiento', name: 'Gpe_Ces', width: 40, align: "center" },
                { label: 'D. Aduanero', name: 'Gpe_Dad', width: 60, align: "center" },
                { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectDest } }
            ], null, null, null, { headertitles: true }, { title: 'Proveedor', text: 'Prs_Ced' });
        // DIALOG BUSCAR Producto            
        $.createSearchDialog('proDialog', [
            { label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, width: 20, align: "center", hidden: false },
            { label: 'Descripción', name: 'product', width: 100 },
            { label: 'Marca', name: 'Mar_Des', width: 25 },
            { label: 'Categoria', name: 'Cat_Des', width: 40, align: "center" },
            { label: 'Stock', name: 'Stk_Can', width: 30, align: 'right' },
            { label: 'IVA', name: 'Iva_Por', width: 20, align: "center", formatter: 'truefalse', formatoptions: { yesMsg: 'Grava IVA', noMsg: 'No Grava IVA' }, title: false },
            { label: 'Adq.', name: 'Adq_Cor', width: 20, align: "center", formatter: 'title', formatoptions: { title: function (o) { return o['Adq_Des']; } } },
            { label: 'Ubic.', name: 'Ubi_Des', width: 25, align: 'right' },
            { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectItem } }
        ], null, 700, null, null, { title: 'Producto', options: [{ label: '&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;', value: 'd' }, { label: '&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;', value: 'c' }] });
        // DIALOG BUSCAR proveedor                 
        $.createSearchDialog('vetDialog', [
            { label: 'C&oacute;d.Int.', name: 'Vet_Cod', key: true, width: 20, align: "center", hidden: false },
            { label: 'Fecha', name: 'Caj_Fec', width: 30 },
            { label: 'Cédula/RUC', name: 'Prs_Ced', width: 40 },
            { label: 'Cliente', name: 'cliente', width: 70 },
            { label: 'Secuencia', name: 'Secuencia', width: 50, align: "center" },
            { label: 'Tipo Compr.', name: 'Tic_Des', width: 50, align: "center" },
            { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectVenta } }
        ], null, null, null, { headertitles: true }, { title: 'Cliente', text: 'Prs_Ced' });

        if ($.fn.flyout) {
            $('#Prs_Ced').createFlyout('La Cedula es Incorrecta!', { icon: 'exclamation', placement: 'bottom' });
        }
        if (Aut_Tem !== 'E') $('#addDocBtn').css({ display: 'none' });
        $('#Mot_Aux').on('change', function () {
            if (this.value === "OTROS") {
                $('#Gui_Mot').prop('required', true).val('').parent().css('display', '');
            } else {
                $('#Gui_Mot').prop('required', false).val(this.value).parent().css('display', 'none');
            }
        });
        //$('#documentoMain').hide().css('visibility','');
        $('#panelGuiaRemi').css('visibility', '');

    }
    /* busquedas */
    searchGrid = $('#searchGrid');
    if (searchGrid.length > 0) {
        var verDoc = $.extend(
            { action: verDocument, title: 'Ver Documento', conditional: function (o) { return o.Gui_Est !== 'I' && o.Gui_Aut !== 'S'; }, caseFalse: function (o) { return o.Gui_Est === 'I' ? '<i class="glyphicon glyphicon-remove red" title="Anulado"></i>' : (o.Gui_Aut === 'S' ? '<i class="fa fa-globe green" title="Autorizado"></i>' : ''); } },
            (!$.isset('modificar') ?
                { conditional: function (o) { return o.Gui_Est !== 'I'; }, caseFalse: function (o) { return o.Gui_Est === 'I' ? '<i class="glyphicon glyphicon-remove red" title="Anulado"></i>' : ''; } } : {}
            )
        );

        // var verDoc = {
        //     action: verDocument,
        //     title: 'Ver Documento',
        //     conditional: function (o) {
        //         // Anulado: no botón
        //         if (o.Gui_Est === 'I') return false;
        //         // Modificar y autorizado: solo icono
        //         if ($.isset('modificar') && o.Gui_Aut === 'S') return false;
        //         // Permitir anular si no autorizado
        //         if (o.Gui_Aut === 'N') return true;
        //         // No permitir anular si autorizado y no es modificar
        //         if (o.Gui_Aut === 'S' && !$.isset('modificar')) return false;
        //         // Solo mostrar botón si no está anulado, autorizado, y no bloqueado por fecha
        //         if (o.Gui_Aut !== 'S') return false;
        //         if (!$.isset('modificar')) {
        //             if (isFechaBloqueada(o.Gui_Fei)) return false;
        //         }
        //         return true;
        //     },
        //     caseFalse: function (o) {
        //         if (o.Gui_Est === 'I') {
        //             return '<i class="glyphicon glyphicon-remove red" title="Anulado"></i>';
        //         }
        //         if ($.isset('modificar') && o.Gui_Aut === 'S') {
        //             return '<i class="fa fa-globe green" title="Autorizado"></i>';
        //         }
        //         if (o.Gui_Aut === 'S' && !$.isset('modificar')) {
        //             if (isFechaBloqueada(o.Gui_Fei)) {
        //                 return '<i class="fa fa-lock orange" title="Bloqueado por normativa de SRI, solo se puede anular este documento hasta el 10 del siguiente mes."></i>';
        //             }
        //             return '<i class="fa fa-globe green" title="Autorizado"></i>';
        //         }
        //         if (o.Gui_Aut === 'N') {
        //             return '<i class="fa fa-ban red" title="Anular"></i>';
        //         }
        //         return '';
        //     }
        // };

        // // Si no es modificar, sobreescribe conditional y caseFalse
        // if (!$.isset('modificar')) {
        //     verDoc.conditional = function (o) {
        //         if (o.Gui_Est === 'I') return false;
        //         if (o.Gui_Aut === 'N') return true;
        //         if (o.Gui_Aut === 'S') return false;
        //         if (isFechaBloqueada(o.Gui_Fei)) return false;
        //         return true;
        //     };
        //     verDoc.caseFalse = function (o) {
        //         if (o.Gui_Est === 'I') {
        //             return '<i class="glyphicon glyphicon-remove red" title="Anulado"></i>';
        //         }
        //         if (isFechaBloqueada(o.Gui_Fei)) {
        //             return '<i class="fa fa-lock orange" title="Bloqueado por normativa de SRI, solo se puede anular este documento hasta el 10 del siguiente mes."></i>';
        //         }
        //         if (o.Gui_Aut === 'N') {
        //             return '<i class="fa fa-ban red" title="Anular"></i>';
        //         }
        //         return '';
        //     };
        // }

        // // Función auxiliar para validar bloqueo por fecha SRI
        // function isFechaBloqueada(fechaEmision) {
        //     if (!fechaEmision) return false;
        //     var f = new Date(fechaEmision.replace(/-/g, '/'));
        //     f.setMonth(f.getMonth() + 1);
        //     f.setDate(10);
        //     var hoy = new Date();
        //     hoy.setHours(0, 0, 0, 0);
        //     return hoy > f;
        // } 

        searchGrid.createGrid({
            caption: 'Resultado de la Búsqueda <div class="pull-right"><b>FILTRADO POR:</b>&nbsp;<select id="FilterVBy" onchange="cargarSelectV();"><option value="">No filtrar</option><option value="Ar">Fec. Arribo ASC</option><option value="Ar1">Fec. Arribo DESC</option><option value="Doc">Nº de Doc ASC</option><option value="Doc1">Nº de Doc DESC</option><option value="Pl">Placa ASC</option><option value="Pl1">Placa DESC</option></select>&nbsp;</div>',
            height: 270,
            datatype: "local",
            colModel: $.merge([
                { label: 'Cód. Int.', name: 'Gui_Cod', width: 30, align: "center", key: true },
                { label: 'No. Documento', name: 'Secuencia', width: 90, align: "center" },
                { label: 'Salida', name: 'Gui_Fei', width: 45, align: "center" },
                { label: 'Arribo', name: 'Gui_Fef', width: 45, align: "center" },
                { label: 'CI/RUC', name: 'Prs_Ced', width: 50, align: "center" },
                { label: 'Transportista', name: 'transportista', width: 150 },
                { label: 'Dir. Salida', name: 'Gui_Dor', width: 110 },
                { label: 'Placa', name: 'Gui_Pla', width: 45, align: "center" },
                { label: 'Estado', name: 'Gui_Est', width: 25, align: "center", formatter: 'estado', title: false }
            ], $.merge((!$.isset('eliminar') ? [
                { label: 'XML', name: 'act01', width: 25, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: viewXml, title: 'Ver XML', icon: 'paperclip', type: 'info', conditional: function (o) { return o.Gui_Est !== 'I' && o.Gui_Aut === 'S'; }, caseFalse: function (o) { return o.Gui_Est !== 'I' && !$.isEmpty(o.Gui_Xml) ? $.createIcon('info-sign orange', null, 'title="PENDIENTE"') : ''; } }, title: false },
                { label: 'PDF', name: 'act02', width: 25, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: viewPdf, title: 'Ver PDF', icon: 'file', type: 'info', conditional: function (o) { return o.Gui_Est !== 'I' && !$.isEmpty(o.Gui_Xml); } }, title: false }
            ] : []),
                [
                    { label: '<i class="glyphicon glyphicon-check"></i>', name: 'act1', width: 25, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: verDoc, title: false }
                ])),
            loadComplete: function (data) {
                if ($.varValid(data.rows))
                    for (var i = 0, z = data.rows.length; i < z; i++) {
                        if (data.rows[i]['Gui_Est'] === 'I' || data.rows[i]['Gui_Est'] === 'E') searchGrid.find("#" + data.rows[i].Gui_Cod + ' td:not(.jqgrid-rownum)').addClass('cellRed2');
                        if (data.rows[i]['Gui_Aut'] === 'S') searchGrid.find("#" + data.rows[i].Gui_Cod + ' td:not(.jqgrid-rownum)').addClass('cellGreen2');
                        //if(data.rows[i]['Cpp_Det'] ==='S' || data.rows[i]['Cpp_Edit'] ==='N' ) $("#"+data.rows[i].Cop_Cod+' td:not(.jqgrid-rownum)').addClass('cellGreen2');
                    }
            },
            rowNum: 250,
            rowList: [250, 500, 1000, 1500, 2000],
        }, false, '#searchGridPager', { view: false, refresh: true }).gridButtonsAdd([null,
            { caption: "Exportar Excel&nbsp;", buttonicon: "download-alt",
                onClickButton: function() {
                    searchGrid.jqGrid('exportGridExcel', {
                        nombre: "GuiasRemision",
                        hoja: "HOJA 1",
                        footer: true
                    });
                },
                position: "last"
            }
        ]);
    }

    // Función para cargar el ordenamiento desde el select
    window.cargarSelectV = function() {
        var filterValue = $('#FilterVBy').val();
        // Agregar el valor al formulario como campo oculto
        var form = $('#serachDocDorm');
        // Eliminar el campo anterior si existe
        form.find('input[name="FilterVBy"]').remove();
        // Agregar el nuevo valor
        if (filterValue) {
            form.append('<input type="hidden" name="FilterVBy" value="' + filterValue + '">');
        }
        // Ejecutar la búsqueda
        $('#searchGrid').Search('#serachDocDorm', 'searchDocument');
    };

    compGrid = $("#guiasConsult");
    if (compGrid.length > 0) {
        compGrid.createGrid({
            height: 160,
            caption: 'Destinatarios / Detalles <div class="pull-right" ><span id="clave"></span></div>',
            colModel: [
                { label: 'Cod.Int.', name: 'id', key: true, hidden: true, viewable: true },
                { label: 'Cod.Int.', name: 'Gui_Cod', hidden: true },
                { label: 'Cod.Int.', name: 'Gui_Int', hidden: true },
                { label: 'CI/RUC', name: 'Prs_Ced', align: "center", width: 40 },
                { label: 'Destinatario', name: "destinatario", width: 90 },
                { label: 'Dir. Destino', name: "Gui_Dde", width: 90 },
                { label: 'Motivo', name: "Gui_Mot", width: 90 },
                { label: 'Ruta', name: "Gui_Rut", width: 50 },
                { label: 'Establec.', name: 'Gui_Ces', align: "center", width: 20 },
                { label: 'D.Aduanero', name: 'Gui_Dad', align: "center", width: 30 },
                { label: 'Documento', name: 'Vet_Num', align: "center", width: 40, title: false, formatter: 'documento' },
                { label: 'Items', name: 'items', align: "center", width: 20 }
            ],
            subGrid: true, multiselect: false,
            subGridOptions: { "plusicon": "ui-icon-triangle-1-e", "minusicon": "ui-icon-triangle-1-s", "openicon": "ui-icon-arrowreturn-1-e", "reloadOnExpand": false, "selectOnExpand": true },
            subGridRowExpanded: function (subgrid_id, row_id) {
                var subgrid_table_id = subgrid_id + "_t";
                $("#" + subgrid_id).addClass('condensed-header').html("<table id='" + subgrid_table_id + "' class='scroll'></table>");
                $("#" + subgrid_table_id).createGrid({
                    url: "?ajaxSubgrid=" + row_id, datatype: "json", regional: 'es',
                    onSelectRow: function (rowid, e) { $("#" + subgrid_table_id).resetSelection(); },
                    colModel: [
                        { label: 'Cod.Int.', name: "id", width: 80, key: true, align: "center", hidden: true },
                        { label: 'Cod.Int.', name: "Pro_Cod", width: 20, align: "center" },
                        { label: 'Cantidad', name: "Gde_Can", width: 45, align: 'right', formatter: 'number', decimalPlaces: '2' },
                        { label: 'Unidad', name: "Uni_Des", width: 50, align: "center" },
                        { label: 'Descripción', name: "Gde_Des", width: 200 },
                        { label: 'Marca', name: "Mar_Des", width: 50, align: "center" },
                        { name: 'Adq_Cor', label: 'Adq.', labelLong: 'Adquisiciones', width: 20, align: "center", title: false, formatter: 'title', formatoptions: { title: function (o) { return o['Adq_Des']; } }, resizable: false }
                    ], height: '100%'
                }, false);
            },
            onSelectRow: function (rowid, e) { compGrid.resetSelection(); },
            loadComplete: function (data) {
                //console.log(data);
                for (var i = 0; i < data.records; i++) {
                    $("#" + data.rows[i].id + ' td:not(.jqgrid-rownum)').addClass('cellGreen1');
                }
                //$.each(compGrid.getDataIDs(), function (index, rowId) { compGrid.expandSubGridRow(rowId); $('#guiasConsult_'+rowId).updateGridsSizes();   }); 
            }
        }, true, 'guiasConsultPager');

    }
    if (!$.isset('registrar')) $('#documentoMain').css('visibility', '').hide();
});
/* FORMATTERS */
$.fn.fmatter.conteo = function (cv, opts, cObjt) { var s = $('<div class="other-title" title="' + cObjt['items'].length + ' item(s)">' + cObjt['items'].length + '</div>'); return s.prop('outerHTML');; }; $.fn.fmatter.conteo.unformat = $.unformatCellHtml;
$.fn.fmatter.codigos = function (cv, opts, cObjt) { var s = $('<div title=\' <u class="green">Establecimiento:</u> ' + cObjt['Gui_Ces'] + ' <br/> <u class="green">D. Aduanero:</u> ' + cObjt['Gui_Dad'] + ' \'><i class="glyphicon glyphicon-info-sign blue"></i></div>'); return s.prop('outerHTML');; }; $.fn.fmatter.codigos.unformat = $.unformatCellHtml;
$.fn.fmatter.descrip = function (cv, opts, cObjt) { var s = $('<div title=\' <u class="green">Destino:</u> ' + cObjt['Gui_Dde'] + ' <br/> <u class="green">Motivo:</u> ' + cObjt['Gui_Mot'] + ' <br/>  <u class="green">Ruta:</u> ' + cObjt['Gui_Rut'] + '  \'><i class="glyphicon glyphicon-info-sign blue"></i></div>'); return s.prop('outerHTML');; }; $.fn.fmatter.descrip.unformat = $.unformatCellHtml;
$.fn.fmatter.documento = function (cv, opts, cObjt) { if ($.isEmpty(cObjt['Vet_Num'])) return ''; var s = $('<div title=\' <u class="green">Tipo:</u> ' + cObjt['Tic_Cod_Txt'] + ' <br/> <u class="green">Doc.:</u> ' + cObjt['Vet_Prefix'] + ((cObjt['Vet_Num'] * 1).padLeft(9)) + ' <br/>  <u class="green">Aut.:</u> ' + cObjt['Aut_Sri'] + '  \'><i class="glyphicon glyphicon-info-sign"></i></div>'); return s.prop('outerHTML');; }; $.fn.fmatter.documento.unformat = $.unformatCellHtml;
//$.fn.fmatter.documento=function(cv,opts,cObjt){ if($.isEmpty(cObjt['Vet_Num'])) return ''; var s=$('<div title=\' <u class="green">Tipo:</u> '+cObjt['Tic_Cod_Txt']+' <br/> <u class="green">Doc.:</u> '+cObjt['Vet_Prefix']+((cObjt['Vet_Num']*1).padLeft(9))+' <br/>   \'><i class="glyphicon glyphicon-info-sign blue"></i></div>'); return s.prop('outerHTML');; }; $.fn.fmatter.documento.unformat=$.unformatCellHtml;
function styleCant(e, obj, opt) {
    e.style.textAlign = 'right'; e.placeholder = '0';
    $(e).on('keyup', function () {
        if (isNaN(this.value)/*||this.value===''||(!isNaN(this.value)&&this.value*1===0)*/) { $(this).val('1').focus(); }
        else if (this.value % 1 !== 0) { var dec = String(this.value).split("."); if (typeof dec[1] !== 'undefined' && dec[1].length > 2) this.value = $.toFixed(this.value); }
        updateRowItem(obj);
    });
}
function styleBar(e, obj, opt) {
    e.placeholder = 'BAR.COD.';
    e.value = '';
    $(e).on('keyup', function (e) {
        if (this.value !== '' && e.keyCode === 13) {
            index = obj['rowId'];
            $.SearchOrDialogArray('#proDialog', selectItem, $.extend($('#proForm').getData(), { search: this.value, op_opciones: 'c' }));
            this.value = '';
        }
    });
}
/* FUNCIONES GENERALES */
/* guias */
// JavaScript Document
$(window).on('resize', function () {
    if (typeof resize === 'function') resize();
});
function validaDocument() {
    var data = $('#formDocumento').getData('saveDocument'), destinos = $.cloneData(data_guia);
    if ($.isEmpty(data['Gpe_Cod'])) return $.alert('Debe Seleccionar el Transportista designado!', null, 'remove');
    if (destinos.length === 0) return $.alert('Debe registrar al menos un destino!', null, 'remove');
    data['destinos'] = destinos;
    for (var i = 0, z = destinos.length; i < z; i++) {
        var dest = destinos[i], items = dest['items'];
        if (items.length > 0 && $.isEmpty(items[(items.length - 1)]['Pro_Cod'])) items.splice((items.length - 1), 1);
        if (items.length === 0) return $.alert('El destino "<i class=\"green\">' + dest['Prs_Ced'] + ' - ' + dest['destinatario'] + '</i>" no posee <u class=\"red\">items</u>, seleccione al menos uno!', null, 'remove');
        for (var j = 0, y = items.length; j < y; j++) {
            if ($.isEmpty(items[j]['Gde_Can'] * 1)) return $.alert('El destino "<i class=\"green\">' + dest['Prs_Ced'] + ' - ' + dest['destinatario'] + '</i>" no puede tener cantidad <u class=\"red\">cero</u> en el item "<i class=\"green\">' + items[j]['product'] + '</i>"!', null, 'remove');
        }
    }
    // console.log(data);
    delete data['Mou_Aux'];
    $.createDialogConfirm('¿Esta seguro de guardar la Guia de Remision?', data, saveDocument);
}
function saveDocument(data) {
    $.saveDataJson('', data,
        function (resp) {
            $('#formDocumento').setData({})[0].reset();
            $('#Bodega_Nom').val(null).trigger('change'); // Limpiar Select2 de Bodegas
            $('#formTempDestinat').setData({});
            $('#formDesti').setData({});
            $('#Mot_Aux').trigger('change');
            $('#panelGuiaRemi').hide();
            $('#panelDestinatario').show();
            data_guia = [];
            docs.clearGrid();
            items.clearGrid();
            validaVetNum();

            //$('#panelVentas').find(':input,td.btn').attr('disabled','disabled').addClass('readOnly');                        
            //$('#btnVetPrint').removeAttr('disabled').show().data('url',resp['Vet_Link']);  

            $('.bntSuccess').hide();
            if ($.varValid(resp['pdf'])) {
                $('#btnImpPdf').show().data('url', resp['pdf']);
            } else {
                $('#btnImpGuia').show().data('url', $.ifvv(resp['imprimir']['1'], ''));
            }
            if ($.isset('modificar')) {
                $('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();
                searchGrid.trigger('reloadGrid', []);
            }
            $('#successDialog').dialog('open');
            return false;
        });
}
function selectTrans(data) { $('#gpeEditForm #Prs_Ced').val(data["Prs_Ced"]).change(); $('.trasportista').setData(data, 'trans'); $('#transDialog').dialog('close'); }
/* destinatarios */
// function selectDest(data) { $('#gpeEditForm #Prs_Ced').val(data["Prs_Ced"]).change(); $('.destinatario').setData(data, 'desti'); $('#destiDialog').dialog('close'); }
function selectDest(data) { 
    $('#gpeEditForm #Prs_Ced').val(data["Prs_Ced"]).change(); 
    $('.destinatario').setData(data, 'desti'); 
    $('#destiDialog').dialog('close'); 
    // Cargar bodegas del destinatario seleccionado
    if (typeof cargarBodegasDestinatario === "function" && data['Gpe_Cod']) {
        cargarBodegasDestinatario(data['Gpe_Cod']);
    }
}
function selectVenta(data) { $('.venta').setData(data, 'venta'); $('#vetDialog').dialog('close'); }
function validaDestinatario() { //console.log(data);
    var data = $('#formDesti').getData();
    if ($.isEmpty(data['Tic_Cod'])) data['Tic_Cod_Txt'] = undefined;
    if ($.isEmpty(data['Gpe_Cod'])) return $.alert('Debe Seleccionar el Destinatario!', null, 'remove');
    if ($.isEmpty(data['Gui_Index'])) {
        $.extend(data, { items: [], Gui_Index: docs.nextIndex('Gui_Index') });
        data_guia.push(data);
        docs.setRow(data);
        $('#Gui_Index').val(data['Gui_Index']);
        addItem({});
    } else {
        $.extend($.arrayGetItem(data_guia, 'Gui_Index', data['Gui_Index']), data);
        docs.changeRow(data['Gui_Index'], data);
    }
    docs.jqGrid('setSelection', data['Gui_Index'], false);
    selectDoc(data['Gui_Index']);
    $('#panelDestinatario').hide();
    $('#panelGuiaRemi').show().updateGridsSizes();
}
function isDocSelected() { if ($('#Gui_Index').val() === '') { $.alert('Seleccione un Destinatario!'); return false; } return true; }
function createDoc(Gui_Index) {
    //console.log(Gui_Index);
    var data = $.vv(Gui_Index) ? $.arrayGetItem(data_guia, 'Gui_Index', Gui_Index) : {};
    if (!$.isEmpty(data['Mot_Aux']) && data['Mot_Aux'] === 'OTROS') $('#Mot_Aux').val('OTROS').trigger('change');
    $('#formDesti').setData(data);

    // Sincronizar Select2 de Bodegas
    var $bodegaSelect = $('#Bodega_Nom');
    if ($bodegaSelect.length > 0) {
        if (data['Bodega_Nom']) {
            // Asegurarse de que las opciones existan antes de seleccionarlas (si son tags dinámicos)
            if (Array.isArray(data['Bodega_Nom'])) {
                $.each(data['Bodega_Nom'], function(i, val) {
                    if ($bodegaSelect.find("option[value='" + val + "']").length === 0) {
                        var newOption = new Option(val, val, true, true);
                        $bodegaSelect.append(newOption);
                    }
                });
            }
            $bodegaSelect.val(data['Bodega_Nom']).trigger('change');
        } else {
            $bodegaSelect.val(null).trigger('change');
        }
    }

    $('#btnAddDesti').html($.vv(Gui_Index) ? '<i class="glyphicon glyphicon-pencil"></i>&nbsp;Editar Destino' : '<i class="glyphicon glyphicon-plus"></i>&nbsp;Agregar Destino a la Guia');
    $('#panelGuiaRemi').hide();
    $('#panelDestinatario').show();
}
function deleteDoc(index) {
    //console.log(index);
    docs.jqGrid('delRowData', index);
    items.clearGrid();
    $('#formTempDestinat').setData({});
    $.arraySpliceWhere(data_guia, 'Gui_Index', index);
    if (docs.nextIndex('Gui_Index') === 1) createDoc();
}
function selectDoc(Gui_Index) {
    var aux = $.cloneData($.arrayGetItem(data_guia, 'Gui_Index', Gui_Index));
    aux['documento'] = aux['Vet_Prefix'] + aux['Vet_Num'];
    $('#formTempDestinat').setData(aux);
    items.setRows(aux['items']);
    //console.log(aux['items'].length);
    if (aux.length === 0 || ((!$.isEmpty(aux['items'][aux['items'].length - 1]['Pro_Cod'])))) addItem({});
    items.startGridEdit();
}
/* items */
function selectItem(item) {
    var lastId = items.jqGrid('getCol', 'index', false, 'max'), close = true, full = !available(), Gui_Index = $('#Gui_Index').val();
    if (items.jqGrid('getDataIDs').length === 0) { addItem({}); lastId = 1; } else if (!full && items.jqGrid('getRowData', lastId)['Pro_Cod'] !== '') { addItem({}); lastId = lastId * 1 + 1; }
    if (index === 0) { index = lastId; close = false; }
    var new_item = $.extend(item, {});
    items.changeRow(index, new_item, null, {});
    updateRowItem({ rowId: index });

    $.extend($.arrayGetItem($.arrayGetItem(data_guia, 'Gui_Index', Gui_Index)['items'], 'index', index, 'item_index'), new_item);
    var last = items.jqGrid('getRowData', lastId);
    if (last['Pro_Cod'] !== '' && available()) { addItem({}); }
    if (full) { $('#proDialog').dialog('close'); return; }
    if (close) { $('#proDialog').dialog('close'); setTimeout(function () { $('#' + (index) + '_Gde_Can').focus(); }, 0); } else if (available()) index = 0; else index = lastId * 1 + 1;
}
// A�ade un item al documento
function addItem(item) {
    var next = items.nextIndex(), Gui_Index = $('#Gui_Index').val();
    var new_item = $.extend(item, { index: next, Gde_Can: 1, Gui_Index: Gui_Index });
    items.jqGrid('addRowData', next, new_item, 'last');
    items.jqGrid('editRow', next);
    $.arrayGetItem(data_guia, 'Gui_Index', Gui_Index)['items'].push(new_item);
    updateRowItem({ rowId: next });
    resize();
}
// Abre dialogo producto para cambiar item
function openItemSelector(id) { index = id; $('#proDialog').dialog('open'); }
// Elimina item
function deleteItem(index) {
    var row = items.jqGrid('getRowData', index), lastId = items.jqGrid('getCol', 'index', false, 'max'), Gui_Index = $('#Gui_Index').val();
    if (row['Pro_Cod'] !== '') {
        items.jqGrid('delRowData', index);
        $.arraySpliceWhere($.arrayGetItem(data_guia, 'Gui_Index', Gui_Index)['items'], 'index', index);
        if (items.jqGrid('getRowData', lastId)['Pro_Cod'] !== '') addItem({});
        resize();
    }
}
// Actualiza los valores de la fila
function updateRowItem(obj) {
    var row = $.extend({}, items.jqGrid('getRowData', obj['rowId']), items.find('tr#' + obj['rowId']).getDataForced()), Gui_Index = $('#Gui_Index').val();
    items.changeRow(obj['rowId'], { 'delete': '' });
    $.extend($.arrayGetItem($.arrayGetItem(data_guia, 'Gui_Index', Gui_Index)['items'], 'index', obj['rowId'], 'item_index'), row);
}
function available() { var its = items.jqGrid('getDataIDs').length, max = $('#Tic_Cod_Guia').find('option:selected').data('autima'), available = ((isNaN(max) || max === '') || ($.round(('0' + max), 0) !== 0) && its < $.round(('0' + max), 0)), full = !available; return available; }
// function resize() { if (items.width() !== $('#itemsContainer').width()) items.jqGrid("resizeGrid"); }
function resize() { if (items && items.length > 0 && items.width() !== $('#itemsContainer').width()) items.jqGrid("resizeGrid"); }
// validar numero de guia
function validaVetNum() {
    var pun = $('#Tic_Cod_Guia').find('option:selected').data() || {}, vnum = $('#Gui_Num'), Vet_Num = vnum.val(), set_old = false;
    if (typeof pun['Aut_Ini'] === 'undefined') { vnum.val('').fieldValid(''); return; }
    if (isNaN(Vet_Num)) { vnum.val('').fieldValid(false, 'El dato "' + Vet_Num + '" no es válido!'); set_old = true; }
    Vet_Num = (Vet_Num !== '' && !isNaN(Vet_Num)) ? Vet_Num * 1 : '';
    if (Vet_Num !== '' && (Vet_Num < pun['Aut_Ini'] || Vet_Num > pun['Aut_Fin'])) { vnum.val('').fieldValid(false, 'El número ' + Vet_Num + ' no está en el rango (' + pun['Aut_Ini'] + ' - ' + pun['Aut_Fin'] + ')!'); set_old = true; }
    if (set_old && $.varValid(vnum.data('old_vet_num')) && vnum.data('old_vet_num').length > 0) { vnum.val(vnum.data('old_vet_num')); return; }
    var new_pun = $.extend(true, {}, pun, { validaVetNum: true, Vet_Num: Vet_Num, Vet_Num_Old: vnum.data('vet_num_old') });
    $('#Gui_Num').getValidationJson('', new_pun, function (r) {
        var rnum = $('#Gui_Num');
        if (r['success'] === false) {
            if (r['Vet_Num_Old'] === '') rnum.fieldValid(true);
        } else {
            if (r['Vet_Num'] * 1 > r['Aut_Fin']) {
                rnum.fieldValid(false, 'Ya no quedan números disponibles en el rango (' + r['Aut_Ini'] + ' - ' + r['Aut_Fin'] + ')!');
                r['Vet_Num'] = '';
            } else {
                if (r['Vet_Num_Old'] * 1 >= r['Aut_Ini'] && r['Vet_Num_Old'] * 1 <= r['Aut_Fin'])
                    delete r['Vet_Num'];
                else
                    rnum.fieldValid(false, 'El número ' + r['Vet_Num_Old'] + ' no está en el rango (' + r['Aut_Ini'] + ' - ' + r['Aut_Fin'] + ')!');
            }
        }
        if ($.varValid(r['Vet_Num'])) {
            if ($.vv(rnum.data('Gui_Num'))) r['Vet_Num'] = rnum.data('Gui_Num');
            rnum.val(r['Vet_Num']).data('old_vet_num', r['Vet_Num']);
        }
    });
}
// anula guia
function anulaGuia(Gui_Cod) {
    $.saveDataJson('', { anulador: true, Gui_Cod: Gui_Cod },
        function (resp) {
            searchGrid.trigger('reloadGrid', []);
            $('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();
            $.alert('Se ha anulado Correctamente la <i>GUIA DE REMISION</i>!');
            return false;
        });
}
// opciones busquedas
function setOpt(val) { if (val === 'd') $('.search_pec').attr('disabled', 'disabled'); else $('.search_pec').removeAttr('disabled'); }
function verDocument(doc) { }
// Ver Electronicos
function viewPdf(doc) {
    window.open('../COMPONENTES/tesPdfElectronicos.php?type=GUIAS&Doc_Cod=' + doc['Gui_Cod']);
}
function viewXml(doc) {
    window.open('?doc_xml=' + doc['Gui_Xml']);
}
// guardar un transportista/destinatario
function guardaProvee() {
    $.saveDataJson("", $('#gpeCreateForm').getData('guardaProvAjax'), function (resp) { (resp['gpe']['Gpe_Tip'] === 'T' ? selectTrans : selectDest)(resp['gpe']); $('#guiaPersonaDialog').dialog('close'); return false; });
}


// buscar una persona

function searchProvee(ced) {
    var Gpe_Tip = $('#gpeCreateForm #Gpe_Tip').val();
    $.post("", {
        provAjax2: true,
        Gpe_Tip: Gpe_Tip,
        Prs_Ced: ced.substring(0, 10)
    },
        function (response) {
            if (response['total'] * 1 === 1) {
                if (!$.varValid(response['rows'][0]['Gpe_Cod']) || response['rows'][0]['Gpe_Cod'].length === 0) {
                    $('#gpeCreateForm').setData(response['rows'][0]);
                } else {
                    (Gpe_Tip === 'T' ? selectTrans : selectDest)(response['rows'][0]);
                    $('#guiaPersonaDialog').dialog('close');
                }

            }
        }, 'json').fail(function () { $('#guiaPersonaDialog').setData({}); }).always(function () { });
}

//Editar un transportista/destinatario
function editarProvee() {
    $.saveDataJson("",
        $('#gpeEditForm').getData('editarProvAjax'), function (resp) {
            (resp['gpe']['Gpe_Tip'] === 'T' ? selectTrans : selectDest)(resp['gpe']);
            $('#guiaPersonaDialogEdit').dialog('close');
            return false;
        });
}

// Función para buscar una persona para editar
function searchProveeEdit(ced) {
    var Gpe_Tip = $('#gpeEditForm #Gpe_Tip').val();
    $.post("",
        {
            provAjax2: true,
            Gpe_Tip: Gpe_Tip,
            Prs_Ced: ced.substring(0, 10)
        },
        function (response) {
            console.log(response);
            if (response.total === 1) {
                $('#gpeEditForm').setData(response.rows[0]);

                if (response.rows[0].Gpe_Tip === 'D') {
                    $('.destinatario_edit').show();
                    $('.transportista_save').hide();
                } else {
                    $('.destinatario_edit').hide();
                    $('.transportista_save').show();
                }
            }
        }, 'json').fail(function () {
            $('#guiaPersonaDialogEdit').setData({});
        });
}


// valida cedula

/*
function validaNoIdentif(number) {
    var digitos = number.split(""), dto = digitos.length, acu = 0, resp = { success: false, message: '' },
        coef = { 'NA': [2, 1, 2, 1, 2, 1, 2, 1, 2], 'PU': [3, 2, 7, 6, 5, 4, 3, 2, 0], 'PR': [4, 3, 2, 7, 6, 5, 4, 3, 2] }, modulo, acum = 0;
    if (dto === 0) resp['message'] = 'No has ingresado ning\u00fan dato!';
    else {
        for (var i = 0; i < dto; i++) if (!isNaN(digitos[i])) { digitos[i] = digitos[i] * 1; acu = acu + 1; }
        if (acu === dto) {
            var tipo = digitos[2];
            var tercer_digito = tipo;
            if (tipo === 7 || tipo === 8) resp['message'] = '"El tercer d\u00edgito ingresado es inv\u00e1lido"'; else { tipo = (tipo <= 6 ? 'NA' : (tipo === 6 ? 'PU' : (tipo === 9 ? 'PR' : ''))); modulo = (tipo === 'NA' ? 10 : 11); resp['tipo_abrev'] = tipo; resp['tipo'] = (tipo === 'NA' ? 'Natural' : (tipo === 'PR' ? 'Privada' : (tipo === 'PU' ? 'P\u00fablica' : ''))); }
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

            /// if(resp['message'].length===0) resp['success']=true;
            if (resp['message'].length === 0) {
                resp['success'] = true
            } else if (tipo == "PR" && tercer_digito == 9) {//Validar RUC privado
                resp['success'] = true;
            }

        } else resp['message'] = "ERROR: Solo debe contener d\u00EDgitos!";
    }
    return resp;
}*/


function validaNoIdentif(number) {
    number = number.trim();
    var digitos = number.split(""), dto = digitos.length, acu = 0, resp = { success: false, message: '', doc_num: number },
        coef = { 'NA': [2, 1, 2, 1, 2, 1, 2, 1, 2], 'PU': [3, 2, 7, 6, 5, 4, 3, 2, 0], 'PR': [4, 3, 2, 7, 6, 5, 4, 3, 2] }, modulo, acum = 0;
    if (dto === 0) resp['message'] = 'No has ingresado ning\u00fan dato!';
    else {
        for (var i = 0; i < dto; i++) if (!isNaN(digitos[i])) { digitos[i] = digitos[i] * 1; acu = acu + 1; }
        if (acu === dto) {
            var tipo = digitos[2], prov = number.substring(0, 2) * 1;
            tercer_digito = tipo;
            if (tipo === 7 || tipo === 8) resp['message'] = '"El tercer d\u00edgito ingresado es inv\u00e1lido"';
            else {
                tipo = (tipo < 6 ? 'NA' : (tipo === 6 ? 'PU' : (tipo === 9 ? 'PR' : '')));
                if ((tipo === 'PU' && dto === 10) || (tipo === 'PU' && dto === 13 && number.substring(9, 13) !== '0001')) tipo = 'NA';
                modulo = (tipo === 'NA' ? 10 : 11); resp['tipo_abrev'] = tipo;
                resp['tipo'] = (tipo === 'NA' ? 'Natural' : (tipo === 'PR' ? 'Privada' : (tipo === 'PU' ? 'P\u00fablica' : '')));
            }
            if (dto !== 10 && dto !== 13) { resp['message'] = 'La cantidad de d\u00EDgitos deben ser 10 o 13'; return resp; } else { resp['doc_abr'] = (dto === 10 ? 'C' : (dto === 13 ? 'R' : '')); resp['doc'] = (dto === 10 ? 'C\u00E9dula' : (dto === 13 ? 'R.U.C.' : '')); }
            if (prov < 1 && prov > 24 && prov != 30) resp['message'] = 'Los dos primeros d\u00EDgitos no pueden ser mayores a 24, o diferente a 30.';
            if (dto === 13) {
                if (number.substring(10, 13) !== '001') resp['message'] = 'Los tres \u00faltimos d\u00EDgitos no tienen el c\u00F3digo del RUC 001.';
                if (tipo === 'PU' && number.substring(9, 13) !== '0001') resp['message'] = 'El R.U.C. de la empresa del sector p\u00fablico debe terminar con 0001';
            } else if ((tipo === 'PU' || tipo === 'PR')) resp['message'] = 'El R.U.C. de las empresas ' + resp['tipo'] + 's deben tener 13 digitos!';


            if (resp['message'].length > 0) { return resp };

            for (var a = 0; a < 9; a++) {
                var resul = digitos[a] * coef[tipo][a];
                acum += (resul - (tipo === 'NA' && resul >= 10 ? 9 : 0));
            }
            var residuo = acum % modulo, digitoVerificador = residuo === 0 ? 0 : modulo - residuo;
            if (digitos[(tipo === 'PU' ? 8 : 9)] !== digitoVerificador) resp['message'] = 'El n\u00famero de ' + resp['doc'] + ' de la ' + (tipo === 'NA' ? 'Persona Natural' : 'Empresa ' + resp['tipo']) + ' ingresado es inv\u00E1lido!';

            // if(resp['message'].length===0) resp['success']=true;
            if (resp['message'].length === 0) {
                resp['success'] = true
            } else if (tipo == "PR" && tercer_digito == 9) {//Validar RUC privado
                resp['success'] = true;
            }
            if ($('#isextrangero').is(':checked')) { resp['success'] = true; }

        } else resp['message'] = "ERROR: Solo debe contener d\u00EDgitos!";
    }
    return resp;
}