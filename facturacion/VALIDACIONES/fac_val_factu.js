var editDoc = false;
if (!$.isset('anula')) anula = false;

function delFila(row) {
    if (String(row['Ret_Aut']).toUpperCase() === 'S') {
        var fechaVenta = row['Ret_Fec']; // Obtener fecha de la venta
        var partes = fechaVenta.split('-');
        var anio = parseInt(partes[0], 10);
        var mes = parseInt(partes[1], 10) - 1;
        var fechaLimite = new Date(anio, mes + 1, 7); // mes+1 porque Date usa 0-index
        var hoy = new Date(); // Obtener fecha actual
        if (hoy > fechaLimite) { return true; }
    }
    return false;
}

/* OBJETOS JQUERY */
$(function () {
    if ($('#searchGrid').length > 0)
        $('#searchGrid').createGrid({
            caption: 'Resultado de la B&uacute;squeda', height: 270, datatype: "local",
            colModel: [
                { label: 'C&oacute;d.Int.', name: 'Cop_Cod', width: 30, align: "center", key: true },
                { label: 'Alerta', name: 'Alerta', width: 15, align: "center", formatter: 'truefalse', formatoptions: { yesMsg: ' ', noMsg: function (o) { return (o['Com_Mes'] === 'N' ? '<u class="red">Comprobante</u> no se Encuentra en el mismo <u class="red">Mes</u>' : '') + (o['Com_Mes'] === 'N' && o['Com_Est'] === 'I' && o['Cop_Est'] === 'A' ? '<br/>' : '') + (o['Com_Est'] === 'I' && o['Cop_Est'] === 'A' ? 'Comprobante <u class="red">Inactivo</u>, Compra <u class="green">Activa</u>' : ''); }, noIcon: function (o) { return (o['Com_Mes'] === 'N' || (o['Com_Est'] === 'I' && o['Cop_Est'] === 'A')) ? 'fa-exclamation-triangle orange' : ''; }, noText: true }, title: false },
                { label: 'Compr.', name: 'Com_Exi', width: 20, align: "center", formatter: 'truefalse', formatoptions: { yesMsg: function (o) { return 'Comprobante:\u00A0<u class="blue">' + o.Com_Codigo + '</u>'; }, noMsg: ' ', yesColor: function (o) { return o['Com_Est'] === 'I' ? 'red' : 'green'; } }, title: false },
                { label: 'Ret.', name: 'Ret_Exi', width: 20, align: "center", formatter: 'truefalse', formatoptions: { yesMsg: function (o) { return (o.Ret_Num === '0' ? 'Sin Numero (0%)' : 'Ret. Num.:\u00A0<u class="blue">' + o.Ret_Num + '</u>'); }, noMsg: ' ' }, title: false },
                { label: 'Csm.', name: 'Con_Cod', width: 20, align: "center", formatter: 'truefalse', formatoptions: { yesMsg: function (o) { return (o.Con_Cod === '' ? 'Sin Numero (0%)' : '<u class="blue">Posee consumo</u>'); }, noMsg: ' ' }, title: false },
                { label: 'Pago', name: 'Pago', width: 35, align: "center" },
                { label: 'P. SRI', name: 'Tpc_Sri', width: 20, align: "center", formatter: 'title', formatoptions: { title: function (o) { return o['Tpc_Des']; } }, title: false },
                { label: 'Tipo Documento', name: 'Tic_Des', width: 80 },
                { label: 'Com_Cod', name: 'Com_Cod', width: 100, hidden: true },
                { label: 'Ret_Cod', name: 'Ret_Cod', width: 100, hidden: true },
                { label: 'No. Documento', name: 'Cop_Num', width: 90, align: "center" },
                { label: 'Fecha', name: 'Cop_Fec', width: 45, align: "center" },
                { label: 'Proveedor', name: 'proveedor', width: 150 },
                { label: 'Estado', name: 'Cop_Est', width: 20, align: "center", formatter: 'estado', title: false },
                { label: '&nbsp;', name: 'act0', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: viewInfo, title: 'Ver Documento', icon: 'info-sign', type: 'info' }, title: false },
                { label: '&nbsp;', name: 'act2', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: ImpCom, title: 'Imprimir Comprobante', icon: 'print', type: 'info' }, title: false },
                { label: '&nbsp;', name: 'act3', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: editObservacion, title: 'Editar observación', icon: 'search', type: 'info' }, title: false },
                { label: 'Ret.Xml', name: 'act01', width: 25, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: descargar, title: 'Ver XML', icon: 'fa-file-code-o', type: 'primary', conditional: function (o) { return o.Ret_Est !== 'I' && !$.isEmpty(o.Ret_Cod) && !$.isEmpty(o.Ret_Xml); } }, title: false },
                { label: 'Ret.Pdf', name: 'act02', width: 25, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: viewPdf, title: 'Ver PDF', icon: 'fa-file-pdf-o', type: 'primary', conditional: function (o) { return o.Ret_Est !== 'I' && !$.isEmpty(o.Ret_Cod) && !$.isEmpty(o.Ret_Xml); } }, title: false },
                { label: 'Liq.Xml', name: 'act01', width: 25, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: descargarLiq, title: 'Ver XML', icon: 'fa-file-code-o', type: 'primary', conditional: function (o) { return (o.Tic_Cod == 3 && !$.isEmpty(o.aut_cod_sri)); } }, title: false },
                { label: 'Liq.Pdf', name: 'act02', width: 25, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: viewPdfLiq, title: 'Ver PDF', icon: 'fa-file-pdf-o', type: 'primary', conditional: function (o) { return (o.Tic_Cod == 3 && !$.isEmpty(o.aut_cod_sri)); } }, title: false }

            ].concat({ label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'edicion', title: false }),
            loadComplete: function (data) {
                if ($.varValid(data.rows))
                    for (var i = 0, z = data.rows.length; i < z; i++) {
                        if (data.rows[i]['Cop_Est'] === 'I' || data.rows[i]['Cop_Est'] === 'E') $("#" + data.rows[i].Cop_Cod + ' td:not(.jqgrid-rownum)').addClass('cellRed2');
                        if (data.rows[i]['Ret_Aut'] === 'S' || data.rows[i]['Rcc_Det'] === 'S') $("#" + data.rows[i].Cop_Cod + ' td:not(.jqgrid-rownum)').addClass('cellBlue2');
                        if (data.rows[i]['Cpp_Det'] === 'S' || data.rows[i]['Cpp_Edit'] === 'N') $("#" + data.rows[i].Cop_Cod + ' td:not(.jqgrid-rownum)').addClass('cellGreen2');
                    }
            }
        }, false, '#searchGridPager', { refresh: true });
    gridFact = $("#documento");
    if (gridFact.length === 1) {
        gridFact.createGrid({
            data: [], caption: "Detalle Documento  <span  style='float: right;'>Si eres Agente de Retención: Autoriza dentro de los 5 días posteriores a la emisión del documento.</span>", rowNum: 10000000, height: 'auto', footerrow: true, headertitles: true, selectGridRows: false,
            excludeChangeCols: ['Cop_Can', 'Cop_Dec', 'Cop_Ice', 'Cop_Pru'], defaultChangeRow: {},
            colModel: [
                { name: 'select', label: '<i class="glyphicon glyphicon-check"></i>', width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: 'openItemSelector', icon: 'check', title: 'Seleccionar Item', data: function (o) { return o.index; } }, resizable: false },
                { name: 'index', label: 'Index', width: 20, sorttype: "int", align: 'center', key: true, hidden: true },
                { name: 'Pro_Cod', label: 'C&oacute;d.Int.', width: 20, sorttype: "int", align: 'center', hidden: true },
                { name: 'Pro_Bar', label: 'bar', width: 20, sorttype: "int", align: 'center', hidden: true },
                { name: 'Cop_Can', label: 'Cant.', labelLong: 'Cantidad', width: 40, align: "right", title: false/*, editable:true, editoptions:{dataInit:styleCant}*/, formatter: 'textboxExa', formatoptions: { type: 'decimal', decimals: 2, attr: { type: 'number', min: '0', step: 2 }, dataEvents: { keyup: 'updateRowItem(this.dataset);' } } },
                { name: 'Uni_Des', label: 'Uni.', labelLong: 'Unidad', width: 25, resizable: false },
                { name: 'Ite_Lar', label: 'Descripci&oacute;n', width: 140 },
                { name: 'selectCta', label: '<i class="glyphicon glyphicon-check"></i>', width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: 'openCtaSelector', icon: 'check', type: 'brown', title: 'Cambiar Cuenta', data: function (o) { return o.index; }, conditional: function (o) { return !$.isEmpty(o.Pro_Cod); } }, resizable: false },
                { name: 'Pld_Cod', label: 'Pld_Cod', width: 20, hidden: true },
                { name: 'Pld_Cdc', label: 'Cuenta', width: 40, align: "center", formatter: 'title', formatoptions: { title: function (o) { return o['Pld_Cdc'] + ' - ' + o['Pld_Des']; } }, title: false },
                { name: 'Pld_Des', label: 'Pld_Des', width: 20, hidden: true },
                { name: 'Cop_Dec', label: '%Descuen.', labelLong: '% Descuento', align: "right", width: 25, formatter: 'textboxExa', formatoptions: { type: 'decimal', dataEvents: { keyup: 'updateRowItem(this.dataset,"val_por");' } } },
                { name: 'Cop_Decv', label: 'Val.Desc.', labelLong: '% Descuento', align: "right", width: 25, formatter: 'textboxExa', formatoptions: { type: 'decimal', dataEvents: { keyup: 'updateRowItem(this.dataset,"val_des");' } } },
                { name: 'Cop_Pru', label: 'P. Unitario', labelLong: 'Precio Unitario', width: 60, align: "right", title: false, /*editable:true, editoptions:{dataInit:stylePru},*/ formatter: 'textboxExa', formatoptions: { type: 'decimal', decimals: 8, dataEvents: { keyup: 'updateRowItem(this.dataset);' } } },
                { name: 'Cop_Imp', label: 'Importe', width: 70, align: "right", summaryRound: 4, formatter: "currency", formatoptions: { prefix: '', thousandsSeparator: ',', decimalSeparator: '.', decimalPlaces: dynamicFormatter, defaultValue: '0.00', }, classes: 'columnHighlight1' },
                { name: 'Iva_Cod', label: 'CodIva', width: 20, hidden: true },
                //{name:'Iva_Por',label:'IVA', width:15,align:"center", formatter:'truefalse', formatoptions:{yesMsg:'Grava IVA',noMsg:'No Grava IVA'}, title:false, resizable: false },
                //CAMPO NUEVO
                { name: 'Iva_Por', label: 'IVA(%)', labelLong: 'Porcentaje IVA', width: 35, align: 'center', title: false, resizable: false },
                { name: 'Iva_Sri', label: 'IVA_SRI', labelLong: 'IVA_SRI', width: 25, align: 'center', title: false, resizable: false, hidden: true }, // nuevo campo
                //{name:'Ice_Int',label:'Ice_Int', width:20,hidden:true},
                { name: 'Cop_Ice', label: '%ICE', labelLong: '% ICE', width: 25, align: "right", title: true, resizable: false, formatter: 'textboxExa', formatoptions: {/*classes:'clearable', */type: 'decimal',/* prepend:{div:'btn', value:function(o){ return $.getGridButton({action:'alert',data:o.index}); } }, append:{type:'info', value:'%'},*/ dataEvents: { keyup: 'updateDocument();' }, data: function (o) { var ice_por = o['Cop_Ice'] || o['Ice_Por']; if ($.varValid(ice_por) && ice_por !== '' && !isNaN(ice_por) && ice_por * 1 > 0) return ice_por; else return ''; } } },
                { name: 'Iva_Cos', label: 'Cos.', labelLong: 'IVA al Costo', width: 20, align: "center", formatter: 'checkboxExa', formatoptions: { yes: 'S', no: 'N', conditional: function (o) { return o.Adq_Cor === 'A'; }/*,nullifField:'Adq_Cor',nullifValue:'A'*/ }, resizable: false },
                { name: 'Adq_Cod', label: 'CodAdq', width: 20, hidden: true },
                { name: 'Ret_Ren_Sri', label: 'I. Renta', labelLong: 'Impuesto a la Renta', width: 35, align: "center", title: false, formatter: 'impRenta', resizable: false },
                { name: 'Ret_Ren_Cod', label: 'Ret Ren_Cod', width: 20, hidden: true },
                { name: 'Ret_Ren_Por', label: 'Ret Ren_Por', width: 20, hidden: true },
                { name: 'Ret_Ren_Con', label: 'Ret Ren_Con', width: 20, hidden: true },
                { name: 'Iva_Ren_Sri', label: 'Ret. IVA', labelLong: 'Retenci&oacute;n del IVA', width: 35, align: "center", title: false, formatter: 'retIva', resizable: false },
                { name: 'Iva_Ren_Cod', label: 'Iva Ren_Cod', width: 20, hidden: true },
                { name: 'Iva_Ren_Por', label: 'Iva Ren_Por', width: 20, hidden: true },
                { name: 'Iva_Ren_Con', label: 'Iva Ren_Con', width: 20, hidden: true },
                { name: 'Adq_Cor', label: 'Adq.', labelLong: 'Adquisiciones', width: 20, align: "center", title: false, formatter: 'title', formatoptions: { title: 'Adq_Des' }, resizable: false },
                { name: 'delete', label: '<i class="glyphicon glyphicon-remove"></i>', width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: deleteItem, icon: 'remove', title: 'Eliminar Item', type: 'danger', data: function (o) { return o.index; }, attr: { 'tabindex': '-1' }, conditional: function (o) { return !(!$.varValid(o['Pro_Cod']) || o['Pro_Cod'] === ''); } }, resizable: false }
            ]
        }, true, 'documentoPager', { view: false }).gridButtonsAdd([
            { caption: 'Agregar Productos', buttonicon: 'glyphicon glyphicon-plus', onClickButton: function () { index = 0; $('#proDialog').dialog('open'); } },
            { caption: 'Remover Todos', buttonicon: 'glyphicon glyphicon-remove', onClickButton: function () { gridFact.clearGrid(); addItem({}); } }
        ]);
        gridFact.getFootRow(true);
        gridFact.jqGrid('footerData', 'set', {
            //Cop_Can:'<div class="footerFact"><label>Observaci&oacute;n:</label></div>',
            Ite_Lar: '<div class="footerFact formDatos" class="formDatos"><div style="text-align: left;">Observaci&oacute;n:</div><textarea name="Cop_Obs" tabindex="12" class="text"></textarea></div>',
            /* Cop_Pru:'<div class="footerFact"><label>TARIFA 0%:</label><label>TARIFA <span class="iva_por"></span>%:</label><label>SUBTOTAL:</label><label>DESCUENTO:</label><label>ICE:</label><label><span class="iva_por"></span>% IVA:</label><label>IRBPNR:</label><label class="total">TOTAL:</label></div>',
            Cop_Imp:'<div class="footerFact formDatos" id="formTotales"><input name="t_iva0" type="text" readonly/><input name="t_iva12" type="text" readonly/><input id="t_subtotal" name="t_subtotal" type="text" readonly/><input id="t_descuento" name="t_descuento" type="text" onchange="if(!isNaN(this.value)&&this.value*1===0)$(\'#Cop_Des\').val(\'0\'); updateDocument();" class="text" /><input name="t_ice" type="text" readonly/><input name="t_iva" type="text" readonly/><input id="Cop_Irb" name="Cop_Irb" type="text" onchange="if(isNaN(this.value)&&this.value*1===0) this.value=\'0.00\'; updateDocument();" class="text"  /><input id="t_rubros" name="t_rubros" type="text"  class="total" readonly/></div>',
           */
            Cop_Pru: '<div class="footerFact">' +
                '<label>SUBTOTAL:</label>' +
                '<label>NO OBJ. IVA:</label>' +
                '<label>TARIFA 0%:</label>' +
                '<label>TARIFA <span class="iva_por_5">5%</span>:</label> ' +
                '<label>TARIFA <span class="iva_por"></span>%:</label>' +
                '<label>Desc.Prod:</label>' +
                '<label>DESCUENTO:</label>' +
                '<label>ICE:</label>' +
                '<label><span class="iva_por_"></span>TOTAL IVA:</label> ' +
                '<label><input type="checkbox" style="cursor: pointer;transform: scale(1.2);" id="c_tresxmil" name="c_tresxmil" onchange="if(isNaN(this.value)&&this.value*1===0) this.value=\'0.00\'; updateDocument();"  class="text"> 3xMil Imp.Rta:</label>' +
                '<label>IVA presuntivo:</label>' +
                /* Casilleros adicionales */
                // '<label><input type="checkbox" style="cursor: pointer;transform: scale(1.2);" id="ch_prop" name="t_prop" onchange="if(this.checked){ if(isNaN(this.value)&&this.value*1===0) this.value=\'0.00\'; } else { $(\'#t_prop\').val(0); } updateDocument();" class="text"> Propina 10%:</label>' + // forma anterior calculada por el 10%
                '<label><input type="checkbox" style="cursor: pointer;transform: scale(1.2);" id="ch_prop" name="t_prop" onchange="if(this.checked){ if(isNaN(this.value)&&this.value*1===0) this.value=\'0.00\'; $(\'#t_prop\').removeAttr(\'readonly\').focus(); } else { $(\'#t_prop\').attr(\'readonly\', \'readonly\').val(0); } updateDocument();" class="text"> Propina:</label>' +
                '<label><input type="checkbox" style="cursor: pointer;transform: scale(1.2);" id="ch_adic" name="ch_adic" onchange="if(this.checked){ if(isNaN(this.value)&&this.value*1===0) this.value=\'0.00\'; $(\'#t_adic\').removeAttr(\'readonly\').focus(); } else { $(\'#t_adic\').attr(\'readonly\', \'readonly\').val(0); } updateDocument();" class="text"> Otros:</label>' +
                // '<label><span class="iva_por"></span>% IVA:</label><label>IRBPNR:</label>'+
                '<label>IRBPNR:</label>' +
                '<label class="total">TOTAL:</label></div>',

            Cop_Imp: '<div class="footerFact formDatos" id="formTotales">' +
                '<input id="t_subtotal" name="t_subtotal" type="text" readonly/>' +
                '<input name="t_noiva" type="text" readonly/>' +
                '<input name="t_iva0" type="text" readonly/>' +
                '<input name="t_iva5" type="text" readonly/>' +
                '<input name="t_iva12" type="text" readonly/>' +
                '<input name="t_pdescuento" id="t_pdescuento" type="text" readonly/>' +
                '<input id="t_descuento" name="t_descuento" type="text" onchange="if(!isNaN(this.value)&&this.value*1===0)$(\'#Cop_Des\').val(\'0\'); updateDocument();" class="text" />' +
                '<input name="t_ice" type="text" readonly/>' +
                '<input name="t_iva" type="text" readonly/>' +
                '<input name="t_imp_combustible" type="text" readonly onchange="if(isNaN(this.value)&&this.value*1===0) this.value=\'0.00\';"  class="text"  />' +
                '<input name="t_iva_pres" id="t_iva_pres"  type="text"  onchange="if(isNaN(this.value)&&this.value*1===0) this.value=\'0.00\'; "  class="text"  />' +
                /* Casilleros adicionales */
                '<input id="t_prop" name="t_prop" readonly type="text" onchange="if(isNaN(this.value)&&this.value*1===0) this.value=\'0.00\'; updateDocument();"  class="text"  />' +
                '<input id="t_adic" name="t_adic" readonly type="text" onchange="if(isNaN(this.value)&&this.value*1===0) this.value=\'0.00\'; updateDocument();" class="text"  />' +

                '<input id="Cop_Irb" name="Cop_Irb" type="text" onchange="if(isNaN(this.value)&&this.value*1===0) this.value=\'0.00\'; updateDocument();" class="text"  />' +
                '<input id="t_rubros" name="t_rubros" type="text"  class="total" readonly/></div>',

            Iva_Por: '<div class="footerFact formDatos">' +
                '<div style="height:57px;"></div>' +
                '<div style="height:19px;text-align: left;">' +
                '<input id="Cop_Des" name="Cop_Des" style="height:19px;position:absolute;display:none;" />' +
                '</div><div style="position:absolute;text-align: left;padding-top:19px;">' +
                '<select id="Iva_Cod" name="Iva_Cod" style="max-width:' + (Cof_Con === 'S' ? '25' : '100') + '%;" onchange="changeIvas()" class="text">' +
                '</select>' + (Cof_Con === 'S' ? '<select id="Iva_Pag" name="Iva_Pag" style="max-width:70%" class="text"></select>' : '') + '</div>' +
                '<div style="height:76px;"></div>' +
                '</div>'
        }, false);
        $('#formTotales,.reteTot:not(.cod_banano)').find('input:not(#t_descuento)').attr('tabindex', '-1');
        addItem({});
    }
    if ($.mask) $(".secuencia").mask("999-999-999999999", { placeholder: "_" });
    if ($.fn.chosen) $('#Ciu_Cod').createChosen('input-xs', { tabIndex: 6, width: '100%', template: function (t, d) { return '<div class="over"><b>' + t + '</b></div><div class="over desc">' + d['prov'] + '</div>'; } });
    var flyout = (!!$.fn.flyout);
    $('.datepickers').createDatePickers({ checkAvailability: true, hideMsg: false, clean: true });
    if (Cof_Con === 'S') {
        $('#Cop_Fec').on('change', function () { $('.Cop_Fec').val(this.value); $('#Com_Fec').val(this.value).datepicker("option", "minDate", this.value); });
        if (flyout) $('#Com_Fec').createFlyout('El comprobante y el documento no estan en el mismo Periodo!', { icon: 'exclamation', placement: 'left_top' });
        $('#Ret_Asu').on('click', function () {
            if ($(this).prop('checked')) {
                $.createDialogConfirm('Est&aacute; seguro que desea <u><b>asumir</b> el valor</u> de la <b>retenci&oacute;n</b>?', null, function () { }, function () {
                    $('#Ret_Asu').prop('checked', false).trigger('change');
                });
            }
        });
    } else {
        gridFact.gridColUpdate('hide', ['selectCta', 'Pld_Cdc']);
    }

    // Activar edición de fecha de retención al hacer clic en el checkbox
    $("#Ret_Fec").prop("readonly", true).datepicker("option", "beforeShow", function (input, inst) { return $(input).prop("readonly") ? false : {}; });
    $('#edit_fec_ret').on('click', function () {
        validarFechaRetencion();
        if ($(this).prop('checked')) {
            $.createDialogConfirm('<img src="../../mascaras/model1/imagenes/32x32/advertencia.png" height="20" width="20" style="" alt=""> La fecha de retención debe ser como máximo 5 días después de la compra y en el mismo mes para que sea válida.', null, function () {
                $("#Ret_Fec").prop("readonly", false);
                $("#Ret_Fec").datepicker("option", "beforeShow", null);
            }, function () { $('#edit_fec_ret').prop('checked', false); });
        } else {
            $("#Ret_Fec").prop("readonly", true);
            $("#Ret_Fec").datepicker("option", "beforeShow", function (input, inst) { return false; });
        }
    });


    /* if ($('#Cop_Fec').length === 1) {
         $('#Cop_Fec').on('change', function () {
             $('#Cop_Imf').datepicker("option", "maxDate", this.value); 
             $('#Cop_Cad').datepicker("option", "minDate", this.value); 
             $('#Ret_Fec').val(this.value).datepicker("option", "minDate", this.value); 
             var d = new Date(this.value); 
             d.setDate(d.getDate() + 15); 
             $('#Cpp_Ven').datepicker("setDate", d).datepicker("option", "minDate", this.value);
             if (this.value.length > 0) {
                 var fec_cop = this.value.split("-"), anio = fec_cop[0], mes = fec_cop[1];
                 if ($(this).data('mes') !== mes) checkFechaIva(this.value);
                 if ($(this).data('anio') !== anio) {
                     if (Cof_Con === 'S') { checkCuentaPago(); $.Search('pro'); }
                 } $(this).data('anio', anio); $(this).data('mes', mes);
             }
             if ($('#Rem_Fec').length === 1)
                 $('#Rem_Fec').datepicker("option", "maxDate", this.value);
         });
         $('#Cop_Fec').data('anio', $('#Cop_Fec').val().substring(0, 4)).data('mes', $('#Cop_Fec').val().substring(5, 7));
     }*/
    if ($('#Cop_Fec').length === 1) {

        $('#Cop_Fec').on('change', function () {
            /* $('#Cop_Imf').datepicker("option", "maxDate", this.value);
             $('#Cop_Cad').datepicker("option", "minDate", this.value);
             $('#Ret_Fec').val(this.value).datepicker("option", "minDate", this.value);
             var d = new Date(this.value);
             d.setDate(d.getDate() + 15);
             $('#Cpp_Ven').datepicker("setDate", d).datepicker("option", "minDate", this.value);
             if (this.value.length > 0) {
                 var fec_cop = this.value.split("-"), anio = fec_cop[0], mes = fec_cop[1];
                 if ($(this).data('mes') !== mes) checkFechaIva(this.value);
                 if ($(this).data('anio') !== anio) {
                     if (Cof_Con === 'S') { checkCuentaPago(); $.Search('pro'); }
                 } $(this).data('anio', anio); $(this).data('mes', mes);
             }
             if ($('#Ret_Fec').length === 1) {
                 // Permite seleccionar sólo fechas dentro de los 5 días posteriores a la compra.
                 var minDate = this.value; var maxDate = "";
                 if (this.value) {
                     var d = new Date(this.value);
                     //d.setDate(d.getDate() + 4);
                      d.setDate(d.getDate() + 9);
                     maxDate = d.toISOString().split('T')[0];
                 }
                 $('#Ret_Fec').datepicker("option", { "minDate": minDate, "maxDate": maxDate });
             }*/
            validarFechaRetencion();
        });
        $('#Cop_Fec').data('anio', $('#Cop_Fec').val().substring(0, 4)).data('mes', $('#Cop_Fec').val().substring(5, 7));
    }



    function validarFechaRetencion() {
        // Obtener el valor del campo Cop_Fec directamente
        var copFecValue = $('#Cop_Fec').val();
        if (!copFecValue) {
            return; // Si no hay valor, salir de la función
        }
        $('#Cop_Imf').datepicker("option", "maxDate", copFecValue);
        $('#Cop_Cad').datepicker("option", "minDate", copFecValue);
        // Establecer Ret_Fec con la fecha actual solo si no existe fecha de retención
        var retFecValue = $('#Ret_Fec').val();
        if (!retFecValue || retFecValue === '' || retFecValue === copFecValue) {
            var hoy = new Date();
            var mes = (hoy.getMonth() + 1);
            var dia = hoy.getDate();
            var fechaActual = hoy.getFullYear() + '-' + (mes < 10 ? '0' : '') + mes + '-' + (dia < 10 ? '0' : '') + dia;
            $('#Ret_Fec').val(fechaActual);
        }
        $('#Ret_Fec').datepicker("option", "minDate", copFecValue);
        var d = new Date(copFecValue);
        d.setDate(d.getDate() + 15);
        $('#Cpp_Ven').datepicker("setDate", d).datepicker("option", "minDate", copFecValue);
        if (copFecValue.length > 0) {
            var fec_cop = copFecValue.split("-"), anio = fec_cop[0], mes = fec_cop[1];
            if ($('#Cop_Fec').data('mes') !== mes) checkFechaIva(copFecValue);
            if ($('#Cop_Fec').data('anio') !== anio) {
                if (Cof_Con === 'S') { checkCuentaPago(); $.Search('pro'); }
            }
            $('#Cop_Fec').data('anio', anio).data('mes', mes);
        }
        if ($('#Ret_Fec').length === 1) {
            // Permite seleccionar sólo fechas dentro de los 5 días posteriores a la compra.
            var minDate = copFecValue;
            var maxDate = "";
            if (copFecValue) {
                var d = new Date(copFecValue);
                //d.setDate(d.getDate() + 4);
                d.setDate(d.getDate() + 8);
                maxDate = d.toISOString().split('T')[0];
            }
            $('#Ret_Fec').datepicker("option", { "minDate": minDate, "maxDate": maxDate });
        }
    }














    $('#For_Cod').on('change', function () { $('.pagoCredito')[this.value * 1 === 2 ? 'show' : 'hide'](); $('.Caj_Ven_Div')[this.value * 1 === 1 ? 'show' : 'hide'](); (('0' + this.value) * 1 === 2 ? $('#Cpp_Ven').attr('required', 'required') : $('#Cpp_Ven').removeAttr('required')); });
    $('#For_Cod2').on('change', function () { $('.pagoCredito2')[this.value * 1 === 2 ? 'show' : 'hide'](); (('0' + this.value) * 1 === 2 ? $('#Cpp_Ven2').attr('required', 'required') : $('#Cpp_Ven2').removeAttr('required')); });
    $('#Ret_Asu').on('change', function () { calculaRetencion(); });
    if (flyout) {
        $('#Cop_Aut').createFlyout('El campo debe tener 10, 37 o 49 digitos!', { icon: 'exclamation', placement: 'right' });
        $('#Prv_Btn').createFlyout('Debe Seleccionar el Proveedor!', { icon: 'exclamation', placement: 'right' });
        $('#Ciu_Cod_chosen').createFlyout('Escoje una Ciudad!', { icon: 'exclamation', placement: 'right' });
        $('#Cop_Num').createFlyout('El Documento ya Existe!', { icon: 'exclamation', placement: 'right' });
        $('#Prs_Ced').createFlyout('La Cedula es Incorrecta!', { icon: 'exclamation', placement: 'bottom' });
    }
    if ($('#provCreateDialog').length > 0) $('#provCreateDialog').createDialog({ icon: 'plus', width: 500, height: 430 });

    $('#Tic_Cod').on('change', function () {
        var val = this.value !== '' ? this.value * 1 : '', sel = $(this).find('option:selected'), sri = sel.data('ticsri'), des = this.value !== '' ? sel.text() : '';
        $("#Cop_Num").prop("readonly", false);
        $("#Pun_Sri").hide();
        $("#Cop_Aut").prop("disabled", false);
        $("#Aut_Codliq").val("").prop("disabled", false);

        if (val == 3 && (array_documentos.length > 0)) { //valor de liquidacion de compras
            $("#Pun_Sri").show();
            $("#Cop_Num").prop("readonly", true);

            //bloquear el campo para ingresar autorizacion
            if (edit_doc != 'S') {
                $("#Cop_Aut").prop("disabled", true).removeAttr("required").val("9".repeat(49));
            }
            console.log(array_documentos.length);
            var Suc_Sri = null;
            var documento_sel = $('#Tic_Cod').find('option:selected').text().split('-')[1];
            $.each(array_documentos, function (i, v) {
                tic_cod = v['Tic_Cod'];
                aut_sri = v['Aut_Sri'];
                pun_sri = v['Pun_Sri'];
                aut_cod = v['Aut_Cod'];
                Suc_Sri = v['Suc_Sri'];
            });

            if (edit_doc == 'S') { $('#Aut_Codliq').val(aut_cod); }

            $.post('', {
                'Tic_Cod': tic_cod,
                'Aut_Sri': aut_sri,
                'Pun_Sri': pun_sri,
                'Aut_Cod': aut_cod,
                'numeroSec': true
            }, function (response) {
                console.log(response);
                var vnum = ((editDoc) ? (!$.vv(response['Aut_Cod']) || AutCod * 1 === response['Aut_Cod'] * 1 ? vet_num_ant : response['Cop_Num']) : response['Cop_Num']);
                console.log(vnum);
                $('#formDocumento').setData({ 'Pun_Sri': Suc_Sri + '-' + response['Pun_Sri'] + '-', 'Cop_Num': vnum, 'Aut_Codliq': response['Aut_Cod'] /*, 'Cop_Aut': ""*/ }, false);
                var doc_disponibles = (response['Aut_Fin'] * 1 - response['Aut_Ini'] * 1) - response['contador'];
                if (doc_disponibles <= response['Aut_Ads'] * 1 && Nota_CreDeb === false)
                    alertaAuto(`Quedan <b>${doc_disponibles} ${documento_sel}S</b> disponibles`, '#Vet_Num', 'right');
                // validarTic_Cod(true);
                num_old = response.Cop_Sec;
            }, 'json').fail(function () { $.alert(); });
        }

        $('#asumirRet')[(sri === 1 || sri === 3) && Cof_Con === 'S' ? 'show' : 'hide']();
        $('#Ret_Asu').prop('checked', false);
        checkImportacion(sri);
        if (sri === 3) checkLiquidacion();
        setReembolsosGrid(sri === 41);
        updateDocument();

    });
    if ($('#changePagoDialog').length > 0) $('#changePagoDialog').createDialog({ icon: 'transfer', width: 600, height: 300 });
    $('#Cop_Aut').on('change', function () { var val = $(this).val(), aut = val.length; $(this).attr('title', val); if (aut !== 0 && aut !== 10 && aut !== 37 && aut !== 49) { $(this).fieldValid(false, 'El campo debe tener 10, 37 o 49 digitos!'); } else { $(this).fieldValid(aut === 0 ? '' : true); } });
    //validaRetNum();//ESTO VERIFICAR
});


function cambioTipoDoc(i = null) {
    if ($('input[name="Prs_Ced"]').val() !== '') {
        //var op= $.isEmpty(i)?$('input:radio[name=Cop_Ide]:checked').val():i;            
        if (!$.isEmpty(i)) {
            i = i == '3' ? '2' : '1';
            $('#op_ide' + i).prop('checked', true).trigger('change');
            var op = i;
        } else {
            var op = $('input:radio[name=Cop_Ide]:checked').val();
        }

        if (op == '1' || i !== '3') {
            if ($('input[name="Prs_Ced"]').val().length < 13) $('input[name="Prs_Ced"]').val($('input[name="Prs_Ced"]').val() + '001');
            if ($.isEmpty(i)) $('#Tic_Cod').val(1);
        }
        if (op == '2' || i == '3') {
            if ($('input[name="Prs_Ced"]').val().length > 10) $('input[name="Prs_Ced"]').val($('input[name="Prs_Ced"]').val().substring(0, 10));
            if ($.isEmpty(i)) $('#Tic_Cod').val(3);
        }
        /* if(op =='3'){
             if($('input[name="Prs_Ced"]').val().length>10) $('input[name="Prs_Ced"]').val($('input[name="Prs_Ced"]').val().substring(0,10));
             //if($.isEmpty(i))$('#Tic_Cod').val(3);
         }*/

    }
}
function tipoComprobanteHide(i, y = null) {
    var x = [
        { s: 1, c: [1, 3, 4, 5, 11, 12, 21, 41, 42, 43, 47, 48, 374, 0] },
        { s: 2, c: [1, 2, 3, 4, 5, 9, 11, 12, 19, 20, 21, 41, 42, 43, 47, 48, 364, 374, 0] },
        { s: 3, c: [1, 4, 3, 5, 41, 42, 47, 48, 374, 0] },
        { s: 4, c: [1, 2, 3, 4, 5, 41, 42, 47, 48, 374, 0] },
        { s: 5, c: [1, 2, 3, 4, 5, 11, 41, 42, 374, 0] },
        { s: 6, c: [1, 3, 4, 5, 41, 43, 47, 48, 374, 0] },
        { s: 7, c: [1, 2, 3, 4, 5, 41, 43, 47, 48, 364, 374, 0] },
        { s: 8, c: [1, 2, 3, 4, 5, 21, 0] },
        { s: 9, c: [1, 4, 5, 54, 0] },
        { s: 10, c: [19, 0] },
        { s: 11, c: [12, 0] },
        { s: 12, c: [42, 0] },
        { s: 13, c: [19, 0] },
        { s: 14, c: [1, 2, 4, 5, 0] },
        { s: 15, c: [1, 2, 4, 5, 12, 0] }
    ]
    $(document).ready(function () { $('#Tic_Cod option').hide(); }); //Pone a visible los option del select Tic_Cod
    var r = x.find(x => x.s === i * 1)
    $.each(r.c, function (index, v) {
        if ($('input:radio[name=Cop_Ide]:checked').val() == 1 && v !== 3)
            $("#Tic_Cod option[data-ticsri='" + v + "']").show(); // Ocultamos los option segun id de sustento
        if ($('input:radio[name=Cop_Ide]:checked').val() == 2 && (v == 3 || v == 0))
            $("#Tic_Cod option[data-ticsri='" + v + "']").show(); // Ocultamos los option segun id de sustento
        if ($('input:radio[name=Cop_Ide]:checked').val() == 3 && v !== 1)
            $("#Tic_Cod option[data-ticsri='" + v + "']").show(); // Ocultamos los option segun id de sustento
    });
    $('#Tic_Cod').val(y);
}

function imprimirRetencion(retCod) {
    if (retCod && retCod !== '') {
        var codigo = retCod;
        if (retCod.indexOf('Ret_Cod=') !== -1) {
            codigo = retCod.split('Ret_Cod=')[1];
            if (codigo.indexOf('&') !== -1) { codigo = codigo.split('&')[0]; }
        }
        var url = '../COMPONENTES/tesPdfElectronicos.php?type=RETENC&Doc_Cod=' + codigo;
        window.open(url, '_blank');
    } else {
        $.alert('No hay código de retención disponible para imprimir.');
    }
}

function viewPdf(doc) {
    window.open('../COMPONENTES/tesPdfElectronicos.php?type=RETENC' + '&Doc_Cod=' + doc['Ret_Cod']);
    /* if (!$.isEmpty(doc['Ret_Cod'])) {
         window.open('../COMPONENTES/tesPdfElectronicos.php?type=RETENC' + '&Doc_Cod=' + doc['Ret_Cod']);
     } else {
         window.open('../COMPONENTES/tesPdfElectronicos.php?type=LIQUIDC' + '&Doc_Cod=' + doc['Cop_Cod']);
     }*/
}

function viewPdfLiq(doc) {
    // window.open('../COMPONENTES/tesPdfElectronicos.php?type=RETENC' + '&Doc_Cod=' + doc['Ret_Cod']);
    window.open('../COMPONENTES/tesPdfElectronicos.php?type=LIQUIDC' + '&Doc_Cod=' + doc['Cop_Cod']);
}

function descargar(data) {
    var save = document.createElement('a'), clicEvent = new MouseEvent('click', { 'view': window, 'bubbles': true, 'cancelable': true });
    save.href = "../FRONT/" + data['Emp_Cod'] + "/" + data['Ret_Xml'] + "_A.xml";//RETENCION
    //console.log(save.href);
    save.target = '_blank';
    save.download = data['Ret_Xml'] + '.xml';
    save.dispatchEvent(clicEvent);
    //window.open(data['file_xml']);
    //console.log(data);
}

function descargarLiq(data) {
    var save = document.createElement('a'), clicEvent = new MouseEvent('click', { 'view': window, 'bubbles': true, 'cancelable': true });
    save.href = "../FRONT/" + data['Emp_Cod'] + "/" + data['Cop_Aut'] + "_A.xml";//LIQUIDACION
    save.target = '_blank';
    save.download = data['Cop_Aut'] + '.xml';
    save.dispatchEvent(clicEvent);
}

/* BUSQUEDAS */
function setOpt(val) { if (val === 'd' || val === 'r') $('.search_pec').attr('disabled', 'disabled'); else $('.search_pec').removeAttr('disabled'); }
// Selecciona Producto
function selectItem(item) {
    var lastId = gridFact.jqGrid('getCol', 'index', false, 'max'), close = true;
    if (index === 0) { index = lastId; close = false; }
    delete (item['Cop_Ice']); delete (item['Ice_Por']);
    item['selectCta'] = '';
    gridFact.changeRowData(index, $.extend($.extend(item, {
        Ret_Ren_Cod: '', Ret_Ren_Con: '', Ret_Ren_Por: '', Ret_Ren_Sri: '', Iva_Cos: ''
    }),

        item['Iva_Por'] * 1 > 0 ? {
            // Iva_Cod: $('#Iva_Cod').val(),
            Iva_Cod: item.Iva_Cod,
            Iva_Por: item.Iva_Por,
            //Iva_Por:$('#Iva_Cod option:selected').data('ivapor'),
            Iva_Ren_Cod: '',
            Iva_Ren_Con: '', Iva_Ren_Por: '', Iva_Ren_Sri: ''
        } : {}));
    gridFact.highlightRow(index);
    var last = gridFact.jqGrid('getRowData', lastId);
    if (last['Pro_Cod'] !== '') addItem({});
    if (close) { $('#proDialog').dialog('close'); setTimeout(function () { $('#' + (index) + '_Cop_Can').focus(); }, 0); } else index = 0;

    updateDocument();
}


function SelectCta(cta) {
    $('#cuenDialog').dialog('close');
    gridFact.changeRowData(index, cta);
    gridFact.highlightRow(index);
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

            if (resp['message'].length === 0) {
                resp['success'] = true
            } else if ((tipo == "PR" || (tipo == "PU" || tipo == "NA")) && (tercer_digito == 9 || tercer_digito == 6)) {//Validar RUC privado
                resp['success'] = true;
            }

            //if(resp['message'].length===0) resp['success']=true;
        } else resp['message'] = "ERROR: Solo debe contener d\u00EDgitos!";
    }
    return resp;
}
// Valida q no existe el documento
function validaCopNum() {
    if (anula === true/* Condicion agregada xq se repite el numero de DAE*/ || $("#Tic_Cod").find('option:selected').data('ticsri') === 17) { $('#Cop_Num').fieldValid(''); return; }
    var data = $.extend($('#docuFormTemp').getData('ajaxCopNum'), $('#provFormTemp').getData()), completo = (data['Cop_Num'] !== '' && data['Prv_Cod'] !== '' && data['Tic_Cod'] !== ''); if (/*$('#Cop_Num').data('old_num')!==data['Cop_Num']&&*/ completo) $('#Cop_Num').getValidationJson('', data); else $('#Cop_Num').fieldValid(!completo ? '' : true);
}


//Valida para registrar liquiedacion sin crear xml
function validaIngresoAut() {
    $("#Pun_Sri").hide();
    $("#Cop_Num").prop("readonly", false);
    // $("#Cop_Aut").prop("disabled",false);
    $("#Cop_Aut").val("").prop("disabled", false);
    $("#Aut_Codliq").val("").prop("disabled", false);
    $("#Cop_Num").val("").prop("disabled", false);
}

// Valida q no existe la retencion
function validaRetNum(saltar) {
    var rnum = $('#Ret_Num'), Aut_Cod = $('#Aut_Cod_Old'), old_data = rnum.data(),
        data = { Ret_Num: rnum.val(), Ret_Cod: $('#Ret_Cod').val(), Aut_Cod_Old: Aut_Cod.length > 0 ? Aut_Cod.val() : '', validaRetNum: true },
        Old_Ret_Num = $.varValid(old_data['Ret_Num']) && old_data['Ret_Num'].trim() !== '' ? old_data['Ret_Num'] * 1 : '',
        Old_Aut_Cod = $.varValid(old_data['Aut_Cod']) && old_data['Aut_Cod'].trim() !== '' ? old_data['Aut_Cod'] * 1 : '';
    if (data['Ret_Num'] * 1 === Old_Ret_Num && !saltar) {
        $('#reteFormTemp').setData(old_data, false);
        $('#Aut_Cod').html(old_data['Aut_Cod']);
        rnum.fieldValid();
        return;
    }
    $('#Ret_Num').getValidationJson('', data, function (r) {
        var rnum = $('#Ret_Num');
        if (r['success'] === false) {
            if (r['Ret_Num_Old'] === '') rnum.fieldValid(true);
            if (Old_Aut_Cod === r['Aut_Cod'] * 1 && Old_Ret_Num !== '') r['Ret_Num'] = Old_Ret_Num;
            if (r['Ret_Num'] * 1 > r['Aut_Fin']) {
                rnum.fieldValid(false, 'Ya no quedan números disponibles en el rango (' + r['Aut_Ini'] + ' - ' + r['Aut_Fin'] + ')!');
                r['Ret_Num'] = '';
            }
        } else {
            if (r['Ret_Num_Old'] !== '') {
                if (r['Ret_Num_Old'] * 1 >= r['Aut_Ini'] && r['Ret_Num_Old'] * 1 <= r['Aut_Fin'])
                    delete r['Ret_Num'];
                else {
                    rnum.fieldValid(false, 'El número ' + r['Ret_Num_Old'] + ' no está en el rango (' + r['Aut_Ini'] + ' - ' + r['Aut_Fin'] + ')!');
                    if (Old_Aut_Cod === r['Aut_Cod'] * 1 && Old_Ret_Num !== '') r['Ret_Num'] = Old_Ret_Num;
                }
            } else {
                if (r['Ret_Num'] * 1 > r['Aut_Fin']) {
                    rnum.fieldValid(false, 'Ya no quedan números disponibles en el rango (' + r['Aut_Ini'] + ' - ' + r['Aut_Fin'] + ')!');
                    r['Ret_Num'] = '';
                }
            }
        }
        //if(!$.varValid(r['Ret_Num'])&&$.varValid(rnum.data('Ret_Num'))&&rnum.data('Ret_Num')!==''&&data['Aut_Cod_Old']==='')
        //    r=$.extend(r,rnum.data());
        //console.log(r);
        //if(('0'+data['Aut_Cod_Old'])*1===('0'+rnum.data('Aut_Cod'))*1 && ('0'+rnum.data('Aut_Cod'))*1!==0 && (!$.varValid(r['Ret_Num'])||r['Ret_Num_Old']!==''))
        //    r=$.extend(r,rnum.data());
        //var Mod_Ret_Num=rnum.data('Ret_Num_Mod');
        //if($.varValid(Mod_Ret_Num) && Mod_Ret_Num!=='' && ('0'+data['Aut_Cod_Old'])*1===('0'+rnum.data('Aut_Cod'))*1 && ('0'+rnum.data('Aut_Cod'))*1!==0 )
        //r['Ret_Num']=Mod_Ret_Num;
        //console.log(rnum.data());
        $('#reteFormTemp').setData(r, false);
        $('#Aut_Cod').html(r['Aut_Cod']);
        $('#Ret_Fec').data({ Aut_Fci: r['Aut_Fci'], Aut_Cad: r['Aut_Cad'] });
        $("#btnClaveExterna").css('display', r['Aut_Tem'] === "E" ? "" : "none");
    });
}
// carga las cuentas pago
function checkCuentaPago(Pld_Cod) {
    if ($('#Cop_Fec').val() === '' || $('#For_Cod').val() === '' || Cof_Con === 'N') return;
    $('#Pag_Pld').attr('disabled', 'disabled');
    $.post("", { cuentasPago: true, For_Cod: $('#For_Cod').val(), Cop_Fec: $('#Cop_Fec').val(), Pld_Cod: Pld_Cod }, function (response) {
        if (response['success'] === true) {
            if (response['total'] > 0) {
                $('#Pag_Pld').html(response['cuentas']);
            } else { $('#Pag_Pld').val('').html(''); $.alert('Error al buscar la cuenta pago para la fecha indicada'); }
        }
    }, 'json').fail(function () { $.alert('Error al buscar las cuentas pago'); })
        .always(function () { if (!$.varValid($('#Pag_Pld').data('disabled')) || $('#Pag_Pld').data('disabled') === false) $('#Pag_Pld').removeAttr('disabled'); });
}
// carga los ivas para la fecha escogida
function checkFechaIva(fecha, Iva_Cod, Pld_Cod) {
    $.post("", {
        Check_Iva: true, Cop_Fec: fecha,
        Tic_Cod: $('#Tic_Cod').val(),
        Tic_Sri: $('#Tic_Cod option:selected').data('ticsri'),
        Iva_Cod: Iva_Cod, Pld_Cod: Pld_Cod
    }, function (response) {
        if (response['success'] === true) {
            if (response['total'] > 0) {
                $('#Iva_Cod').html(response['options'])[(response['varIvas'] ? 'show' : 'hide')]();
                if (Cof_Con === 'S') $('#Iva_Pag').css('max-width', response['varIvas'] ? '70%' : '100%').html(response['cuentas']);
                // changeIvas(); //EVITA QUE LOS IVAS SE CARGUEN POR PRODUCTO AL EDITAR
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

    //$('.iva_por' + (iva['Iva_Por'] == 5 ? '_5' : '')).html(iva['Iva_Por'] + '%');
    $('.iva_por').html(iva['Iva_Por']);
    for (var i = 0; i < ids.length; i++) {
        if ('0' + gridFact.jqGrid('getCell', ids[i], 'Iva_Por') * 1 > 0) gridFact.changeRowData(ids[i], iva);
    }
    updateDocument();
}
// estilo cantidad
//function styleCant(e,obj,opt){
//    e.style.textAlign = 'right';  e.placeholder='0';
//    $(e).on('keyup',function (){
//       if(isNaN(this.value)/*||this.value===''||(!isNaN(this.value)&&this.value*1===0)*/){ $(this).val('1').focus();   }
//       else if (this.value % 1 !== 0){ var dec=String(this.value).split("."); if(typeof dec[1]!=='undefined'&&dec[1].length>2) this.value=$.toFixed(this.value);  }
//       updateRowItem(obj);
//    });
//}
// estilo precio unitario
//function stylePru(e,obj,opt){
//    e.style.textAlign = 'right'; e.placeholder='0.00';
//    $(e).on('keyup',function (){
//       if(isNaN(this.value)/*||this.value===''||(!isNaN(this.value)&&this.value*1===0)*/){ $(this).val('').focus();; }
//       else if (this.value % 1 !== 0){ var dec=String(this.value).split("."); if(typeof dec[1]!=='undefined'&&dec[1].length>8) this.value=$.toFixed(this.value,8);  }
//       updateRowItem(obj);
//    });
//}

/*
// Actualiza los valores de la fila
function updateRowItem(obj, opc) {
    var row = $.extend({}, gridFact.jqGrid('getRowData', obj['rowId']));
    var newRow = { Cop_Imp: row['Cop_Can'] * (0 + row['Cop_Pru']) * 1 }; //row['Cop_Imp']=row['Cop_Can']*(0+row['Cop_Pru'])*1;
    // newRow['Cop_Imp'] = newRow['Cop_Imp'] - (('0' + row['Cop_Dec']) * 1 > 0 ? newRow['Cop_Imp'] * row['Cop_Dec'] / 100 : 0);
    if ((row['Cop_Dec']) > 0 || (row['Cop_Decv'] > 0)) {
        if (opc == 'val_des') {
            newRow['Cop_Dec'] = (row['Cop_Decv']) / (row['Cop_Can'] * (row['Cop_Pru']) * 1) * 100;
            gridFact.setCell(obj['rowId'], 'Cop_Dec', newRow['Cop_Dec']);
        } else if (opc == 'val_por') {
            newRow['Cop_Decv'] = (row['Cop_Dec'] / 100) * (row['Cop_Can'] * (0 + row['Cop_Pru']) * 1);
            newRow['Cop_Dec'] = newRow['Cop_Decv'];
            //console.log("COP_DEC: " + newRow['Cop_Decv']);
            gridFact.setCell(obj['rowId'], 'Cop_Decv', newRow['Cop_Decv']);
        } else {
            //console.log(" NO DEBE INGRESAR ");
            newRow['Cop_Dec'] = row['Cop_Dec'];
            newRow['Cop_Decv'] = (row['Cop_Dec'] / 100) * (row['Cop_Can'] * (0 + row['Cop_Pru']) * 1);
            gridFact.setCell(obj['rowId'], 'Cop_Decv', newRow['Cop_Decv']);
        }
        //console.log("COP_DEC----- : " + newRow['Cop_Dec']);
        newRow['Cop_Imp'] = newRow['Cop_Imp'] - (('0' + newRow['Cop_Dec']) * 1 > 0 ? newRow['Cop_Imp'] * newRow['Cop_Dec'] / 100 : 0);
    }
    gridFact.setCell(obj['rowId'], 'Cop_Imp', newRow['Cop_Imp']);
    updateDocument();
}*/

// Actualiza los valores de la fila
function updateRowItem(obj, opc) {
    var row = $.extend({}, gridFact.jqGrid('getRowData', obj['rowId']));
    var newRow = { Cop_Imp: row['Cop_Can'] * (0 + row['Cop_Pru']) * 1 }; //row['Cop_Imp']=row['Cop_Can']*(0+row['Cop_Pru'])*1;
    console.log(row['Cop_Dec']) + " > 0 ||" + (row['Cop_Decv']);
    if ((row['Cop_Dec']) > 0 || (row['Cop_Decv'] > 0)) {
        if (opc == 'val_des') {

            newRow['Cop_Dec'] = ((row['Cop_Decv']) / (row['Cop_Can'] * (row['Cop_Pru']) * 1)) * 100;
            gridFact.setCell(obj['rowId'], 'Cop_Dec', newRow['Cop_Dec']);
            newRow['Cop_Dec'] = row['Cop_Decv'];

        } else if (opc == 'val_por') {
            newRow['Cop_Decv'] = (row['Cop_Dec'] / 100) * (row['Cop_Can'] * (0 + row['Cop_Pru']) * 1);
            newRow['Cop_Dec'] = newRow['Cop_Decv'];
            gridFact.setCell(obj['rowId'], 'Cop_Decv', newRow['Cop_Decv']);
        } else {
            newRow['Cop_Dec'] = row['Cop_Dec'];
            newRow['Cop_Decv'] = (row['Cop_Dec'] / 100) * (row['Cop_Can'] * (0 + row['Cop_Pru']) * 1);
            gridFact.setCell(obj['rowId'], 'Cop_Decv', newRow['Cop_Decv']);
            newRow['Cop_Dec'] = newRow['Cop_Decv'];
        }
        newRow['Cop_Imp'] = newRow['Cop_Imp'] - ((newRow['Cop_Dec']) * 1 > 0 ? (newRow['Cop_Dec'] * 1) : 0);
    }
    gridFact.setCell(obj['rowId'], 'Cop_Imp', newRow['Cop_Imp']);
    updateDocument();
}

// Añade un item al documento
function addItem(item) {
    var next = gridFact.jqGrid('getCol', 'index', false, 'max');
    next = (isNaN(next) ? 1 : next + 1);
    gridFact.jqGrid('addRowData', next, $.extend(item, { index: next, Cop_Can: 1, Cop_Pru: '', Cop_Ice: '', 'delete': '' }), 'last');
    gridFact.jqGrid('editRow', next);
    updateRowItem({ rowId: next });
    resize();
}
// Abre dialogo producto para cambiar item
function openItemSelector(id) { index = id; $('#proDialog').dialog('open'); }
function openCtaSelector(id) { index = id; $('#cuenDialog').dialog('open'); }
// Elimina item
function deleteItem(index) { var data = gridFact.jqGrid('getRowData', index); if (data['Pro_Cod'] !== '') { gridFact.jqGrid('delRowData', index); updateDocument(); resize(); } }
function resize() { if (gridFact.width() !== $('#documentoMain').width()) gridFact.jqGrid("resizeGrid"); }
// Actualiza los valores totales
function updateDocument() {
    cod_bar_combustible = null, des_prod = 0;
    var rows = gridFact.jqGrid('getRowData'), des_val = $('#t_descuento').val(), des_val_prod = $('#t_pdescuento').val(), irbpnr = $('#Cop_Irb').val(),
        tresxmil = $('#t_imp_combustible').val(),
        t_iva_pres = $('#t_iva_pres').val(),
        prop = $('#t_prop').val(),
        adic = $('#t_adic').val(),
        des_por = $('#Cop_Des').val(),
        tot = {
            t_subtotal: 0,
            t_noiva: 0, //nuevo campo
            t_iva0: 0,
            t_iva5: 0,
            t_iva12: 0,
            t_iva: 0,
            t_ice: 0,
            Cop_Irb: (!isNaN(irbpnr) ? irbpnr * 1 : 0),
            t_descuento: (!isNaN(des_val) ? des_val * 1 : 0),
            t_pdescuento: (!isNaN(des_val_prod) ? des_val_prod * 1 : 0),
            t_imp_combustible: (!isNaN(tresxmil) ? tresxmil * 1 : 0),
            t_iva_pres: (!isNaN(t_iva_pres) ? (t_iva_pres * 1) : 0),
            t_prop: (!isNaN(prop) ? prop * 1 : 0),
            t_adic: (!isNaN(adic) ? adic * 1 : 0),
            Cop_Des: (!isNaN(des_por) ? des_por * 1 : 0), t_rubros: 0
        },
        Tic_Sri = $('#Tic_Cod').find('option:selected').data('ticsri') * 1, rise = (Tic_Sri === 2 || Tic_Sri === 9);
    //console.log("valor iva presuntivo: " + tot['t_imp_combustible']);
    desc_val_prod = 0;
    for (var i = 0, z = rows.length - 1; i < z; i++) {
        var row = rows[i];
        row['Cop_Imp'] = (row['Cop_Imp'] * 1);
        des_prod += ((row['Cop_Dec'] * 1) / 100) * (row['Cop_Pru'] * 1);
        desc_val_prod += row['Cop_Decv'] * 1;
        row['Iva_Por'] = rise ? 0 : ('0' + row['Iva_Por']) * 1;
        row['Ice_Por'] = ('0' + row['Cop_Ice']) * 1;
        tot['t_subtotal'] = tot['t_subtotal'] + row['Cop_Imp'];
        if (row['Iva_Por'] === 0 || rise) {
            // tot['t_iva0'] = tot['t_iva0'] + row['Cop_Imp']; //0%
            if (row['Iva_Sri'] == 6) { // nueva validacion para no objeto iva
                console.log("entro con el no objeto iva");
                tot['t_noiva'] = tot['t_noiva'] + row['Cop_Imp']; //nuevo campo para total sin iva
            } else {
                tot['t_iva0'] = tot['t_iva0'] + row['Cop_Imp']; //0%
            }
        } else if (row['Iva_Por'] === 5 || rise) {
            tot['t_iva5'] = tot['t_iva5'] + row['Cop_Imp'];//5%
        } else {
            tot['t_iva12'] = tot['t_iva12'] + row['Cop_Imp'];//15%
        }
        if ($('#c_tresxmil').is(':checked')) {//Solo es con un item producto de combustible
            cod_bar_combustible = row['Pro_Bar'];
        }
    }

    //Descuento de los productos
    tot['t_pdescuento'] = desc_val_prod;

    tot['Cop_Des'] = (tot['t_descuento'] > 0 ? (tot['t_subtotal'] >= tot['t_descuento'] ? tot['t_descuento'] * 100 / tot['t_subtotal'] : 100) : tot['Cop_Des']);
    for (var i = 0, z = rows.length - 1; i < z; i++) {
        var row = rows[i], des_glob = (tot['Cop_Des'] > 0 ? row['Cop_Imp'] * tot['Cop_Des'] / 100 : 0), ice = (row['Ice_Por'] > 0 ? (row['Cop_Imp'] - des_glob) * row['Ice_Por'] / 100 : 0);
        if (row['Iva_Por'] > 0 && !rise) {
            tot['t_ice'] = tot['t_ice'] + ice;
            tot['t_iva'] = tot['t_iva'] + (row['Cop_Imp'] + ice - des_glob) * row['Iva_Por'] / 100;
        }
    }

    tot['t_iva'] = Number(redondeoDosDecimales(tot['t_iva']));
    // tot['t_iva'] = $.round(tot['t_iva']); 
    tot['t_ice'] = $.round(tot['t_ice']); /* tot['t_iva'] = Math.round(tot['t_iva'] * 100) / 100;*/

    if ($('#c_tresxmil').is(':checked')) {
        tot['t_imp_combustible'] = ((tot['t_subtotal'] * 3) / 1000);
        console.log(tot['t_imp_combustible']);
        var por_iva_pres = 0;
        if (cod_bar_combustible == "0103") { //SUPER  //Super 9.50 %  del IVA
            por_iva_pres = 0.095;
        } else if (cod_bar_combustible == "0121") {//DIESEL  //Diesel 2% del IVA
            por_iva_pres = 0.02;
        } else if (cod_bar_combustible == "0174") {//ECOPAIS  //Ecopais   2% del IVA
            por_iva_pres = 0.02;
        }
        tot['t_iva_pres'] = ((tot['t_iva'] * por_iva_pres));
    }

    tot['t_rubros'] = tot['t_subtotal'] - tot['t_descuento'] + tot['t_iva'] + tot['t_ice'] + tot['Cop_Irb'] + $.round(tot['t_imp_combustible']) + $.round(tot['t_iva_pres']) + tot['t_prop'] + tot['t_adic'];
    console.log("Rubros" + tot['t_rubros']);
    var opcionDeshabilitar = "01 - SIN UTILIZACION DEL SISTEMA FINANCIERO";
    if (tot['t_rubros'] >= 500) {
        $("#Tpc_Cod option:contains('" + opcionDeshabilitar + "')").prop("disabled", true);
    }
    else {
        $("#Tpc_Cod option:contains('" + opcionDeshabilitar + "')").prop("disabled", false);
    }
    (tot['t_rubros'] >= 500 ? $('#Tpc_Cod').attr('required', 'required') : $('#Tpc_Cod').removeAttr('required'));
    $.each(tot, function (k, v) { tot[k] = $.toFixed(v, k !== 'Cop_Des' ? 2 : 10); });
    $('#formTotales').setData(tot);
    $('#Cop_Des').val(tot['Cop_Des']);
    document.getElementById("t_descuento").disabled = desc_val_prod > 0 ? true : false; //Bloquear campo si existe descuento en productos.
    calculaRetencion();
}
// Valida Todo antes de guardar
function validaDocument() {
    // console.log('ver');
    if (($('#Val_Pcc').val()) * 1 < 0) { $.alert('El valor a pagar no puede ser negativo!<br/>Revise los datos.', null, 'remove'); return; }
    var data = $('.formDatos').getData('saveDocument'), aut = data['Cop_Aut'].length;
    if (Cof_Con === 'S') {
        if (data['Cop_Fec'].substring(0, 4) !== data['Com_Fec'].substring(0, 4)) { $('#Com_Fec').flyout('show').focus(); return; }
    }
    if (!data['Prv_Cod'].length) { $('#Prv_Btn').focus(); $('#Prv_Btn').flyout('show'); return; }
    if (aut !== 10 && aut !== 37 && aut !== 49) { $('#Cop_Aut').flyout('show').focus(); return; }
    if (!data['Ciu_Cod'].length) { $('#Ciu_Cod_chosen').flyout('show').focus(); return; }
    if (gridFact.jqGrid('getDataIDs').length - 1 <= 0) { $.alert('Debe seleccionar al menos un <u>Item</u>!', null, 'remove'); return; }
    data['items'] = gridFact.getGridBatch();
    gridFact.startGridEdit();
    data['Old_Num'] = $('#Cop_Num').data('old_num');
    data['Tic_Sri'] = $("#Tic_Cod option:selected").data('ticsri');
    data['Tic_Des'] = $("#Tic_Cod option:selected").text();
    data['rets'] = $('#retencion').getGridBatch();
    data['items'].splice(data['items'].length - 1, 1);
    for (var i = 0; i < data['items'].length; i++) {
        if (data['items'][i]['Pro_Cod'] === '') { $.alert('Seleccione producto en la fila ' + (i + 1) + '!', null, 'remove'); return; }
        if (data['items'][i]['Cop_Imp'] * 1 <= 0) { $.alert('El producto <u>' + data['items'][i]['Ite_Lar'] + '</u> no puede tener <i>Importe cero</i>!', null, 'remove'); return; }
    }
    if ((data['Tic_Sri'] * 1 !== 1 && data['Tic_Sri'] * 1 !== 2 && data['Tic_Sri'] * 1 !== 3 && data['Tic_Sri'] * 1 !== 42) && data['rets'].length > 0) { $.alert('El <u>Comprobante de Retención</u> solo se aplica a <i>Facturas/Liquidaciones</i>!', null, 'remove'); return; }
    $.arraySpliceFields(data['items'], ['index', 'delete', 'select', 'Uni_Des', 'Pld_Cdc', 'Pld_Des']);
    if (data['Tic_Sri'] * 1 === 3) { //liquidacion compras
        if (($('#t_rubros').val() * 1 + ('0' + $('#infoLiquida').data('actual')) * 1) > 13000) { $.alert('Las <u>liquidaciones en Compras</u> de este Proveedor exceden el limite!', null, 'remove'); return; }
    }
    if (!$('#pagoFormTemp').valid()) { setTimeout(function () { $('#pagoFormTemp').formSubmit(); }, 0); return; };
    if ($('#Ren_Tot').val() * 1 > 0) {
        if (!$('#reteFormTemp').valid()) { setTimeout(function () { $('#reteFormTemp').formSubmit(); }, 0); return; };
        var Ret_Fec = $('#Ret_Fec').val(), Aut_Cad = $('#Ret_Fec').data('Aut_Cad');
        if ($.vv(Aut_Cad) && Aut_Cad.length > 0) {
            if (Ret_Fec > Aut_Cad) {
                //$('#Ret_Fec').createFlyout('No puede ser mayor a <u class="orange">' + Aut_Cad + '</u> !', { icon: 'exclamation', placement: 'right_bottom' });
                // $('#Ret_Fec').val('').flyout('show');
                // return;
            }
        }
    }
    if ($.varValid(data['Cpp_Min']) && data['Cpp_Min'] * 1 > 0 && data['Val_Pcc'] * 1 < data['Cpp_Min'] * 1) { $.alert('El valor de los <i>Pagos Activos</i> es superior al valor a pagar.<br/><b class="green">Pagos:</b> <u>' + $.toFixed(data['Cpp_Min']) + '</u><br/><b class="red">A pagar:</b> <u>' + data['Val_Pcc'] + '</u>', null, 'remove'); return; }
    data['reembolsos'] = '';
    if (data['Tic_Sri'] * 1 === 41) {

        //if($('#t_rubros').val().toNum() < reembolsos.jqGrid('getCol','Total',true,'sum')) {
        if (parseFloat($('#t_rubros').val().toNum()).toFixed(4) < parseFloat(reembolsos.jqGrid('getCol', 'Total', true, 'sum')).toFixed(4)) {
            return $.alert("El valor <u>TOTAL</u> del Documento no puede ser menor a los <u>REEMBOLSOS</u>!", null, "alert");
        }
        var datosReem = reembolsos.getGridColumn('OriginalData');
        $.arraySpliceFields(datosReem, ['id', 'Total', 'index']);
        data['reembolsos'] = datosReem;
        if (datosReem.length === 0)
            return $.alert("Debe ingresar las facturas a reembolsar!", null, "alert");
    }
    /* if (data['rets'].length > 0) {
         var fecha_actual = new Date();
         var fecha_retencion = $('#Ret_Fec').val();
         if (fecha_retencion) {
             var parts = fecha_retencion.split("-");
             if (parts.length === 3) {
                 var fecha_ret = new Date(parts[0], parts[1] - 1, parts[2]);
                 // Solo comparar fechas (ignorar horas)
                 fecha_actual.setHours(0, 0, 0, 0);
                 fecha_ret.setHours(0, 0, 0, 0);
                 if (fecha_ret < fecha_actual) {
                     $('#Ret_Fec').createFlyout('La fecha de retención no puede ser menor a la fecha actual!', { icon: 'exclamation', placement: 'right_bottom' });
                     $('#Ret_Fec').val('').flyout('show');
                     return;
                 }
             }
         }
     }*/
    $.createDialogConfirm('¿Est&aacute; seguro de guardar el documento?', data, saveDocument);
}
// Guardar documento
function saveDocument(data) { //console.log(data); console.log(data['rets']);

    $.arraySpliceFields(data.items, ['select', 'delete', 'Vet_Index', 'Uni_Des']);
    data.items = $.jsonParser(data.items);

    $.saveDataJson('', data,
        function (resp) {
            $('#resultContent').setData(resp);
            $('#copForm').setData(resp['Cop_Data']);
            $('#copresult').setRows(resp['Cop_Rows']);
            $('#btnCopPrint').data('url', resp['Cop_Link']);

            $("#btnAllPrint").data("url", resp["All_Link"]);

            if (Cof_Con === 'S') {
                $('#compForm').setData(resp['Com_Data']);
                $('#asiento').setRows(resp['Com_Rows']);
                $('#btnComPrint').data('url', resp['Com_Link']);

                $('#compFormRet').setData(resp['Com_Data_Ret']);
                $('#asientoRet').setRows(resp['Com_Rows_Ret']);
                $('#btnComPrintRet').data('url', resp['Com_Link_Ret']);
            }

            if (!$.varValid(resp['Com_Rows_Ret'])) {
                $('#compFormRet').addClass('hidden');
            } else {
                $('#compFormRet').removeClass('hidden');
            }

            if ($.varValid(resp['Ret_Cod']) && resp['Ret_Cod'] !== '') {
                var ret = $('#reteFormTemp').getData();
                if (('0' + ret['Ren_Tot']) * 1 === 0) ret['Ret_Num'] = 'Ninguno';
                $('#retForm').setData(ret); $('#reteresult').setRows($('#retencion').getGridBatch()); $('#btnRetPrint').data('url', resp['Ret_Link']);

                // Establecer URL para imprimir PDF de retención
                var retPrintUrl = resp['Ret_Link'];
                // Si no hay Ret_Link o está vacío, usar tesPdfElectronicos.php como alternativa
                if (!$.varValid(retPrintUrl) || retPrintUrl === '') {
                    retPrintUrl = '../COMPONENTES/tesPdfElectronicos.php?type=RETENC&Doc_Cod=' + resp['Ret_Cod'];
                }
                $('#btnRetPrint').data('url', retPrintUrl).show();
                if ($.varValid(resp['mail'])) {
                    if (resp['mail'] === false) $.alert('Surgio un problema al enviar el mail <u>Comprobante Electronico</u> al <i>Proveedor</i>!');
                    if (resp['mail'] === true) $.alert('El mail del <u>Comprobante Electronico</u> al <i>Proveedor</i> se envio correctamente!', null, 'ok green');
                }
                $('#btnRetXml')[$.varValid(resp['Ret_Xmls']) ? 'show' : 'hide']().data('url', resp['Ret_Xmls']);
                $('#retForm').show();
            } else {
                $('#retForm').hide();
                $('#btnRetPrint').hide();//Este es nuevo
            }
            $('#documentoMain').moveComp('#documentoResult').updateGridsSizes(); return false;
        });
}
function viewInfo(doc) {

    $('.formDatos').setData(doc, false);
    $('#docDetaDialog').setData(doc);
    $('#retViewGrid')[$.varValid(doc['Ret_Cod']) && doc['Ret_Cod'] !== '' ? 'show' : 'hide']();

    $.getDataJson('', { docDetalle: true, Cop_Cod: doc['Cop_Cod'], Com_Cod: doc['Com_Cod'], Cop_Fec: doc['Cop_Fec'], Ret_Cod: doc['Ret_Cod'] }, function (resp) {
        $('#documento').setRows(resp['items']).startGridEdit();
        $.each(resp['items'], function (i, v) { updateRowItem({ rowId: v['index'] }); });
        addItem({});
        $('#t_descuento').val($.toFixed($("#t_subtotal").val() * 1 * ('0' + $('#Cop_Des').val()) / 100));
        updateDocument();
        $('#detaDocu').setRows(resp['items']);
        $('#detaRete').setRows($('#retencion').getGridBatch());
        $('#docDetaDialog').dialog('open').updateGridsSizes();
    });
}

var metodo = 0;
function editObservacion(doc) {
    $('#docDetaObservacion').setData(doc);
    $('#docDetaObservacion').dialog('open').updateGridsSizes();
    var codigo = document.getElementById('Cop_Codigo').value;
    if (metodo == 0) {
        document.getElementById('btnEditarObservacion').addEventListener('click', function () {
            guardarObservacion(doc.Cop_Obs, codigo)
        });
        metodo++;
    }

}

function guardarObservacion(obser, codigo) {
    var observacion = document.getElementById('Cop_Observacion').value;
    obser = observacion;
    $.post("", { editarObservacion: true, Cop_Observacion: observacion, Cop_Codigo: codigo }, function (response) {
        if (response['success'] == true) {
            $('#docDetaObservacion').dialog('close');
            $('#searchGrid').Search('#serachDocDorm', 'searchDocument');
        }
    }, 'json');
}

// Revisa si existe el proveedor
// buscar una persona
function searchProvee(ced) {
    $.post("", { provAjax2: true, Prs_Ced: ced.substring(0, 10) }, function (response) {
        if (response['total'] * 1 === 1) {
            if (!$.varValid(response['rows'][0]['Prv_Cod']) || response['rows'][0]['Prv_Cod'].length === 0) {
                $('#provCreateForm').setData(response['rows'][0]);
                $('#Prv_Tic').val(validaNoIdentif(response['rows'][0]['Prs_Ced'])['tipo_abrev'] === 'NA' ? 'N' : 'J').trigger('change');
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

function checkImportacion(Tic_Sri) {
    var Importa = Tic_Sri * 1 === 17;
    if ($.mask) $('#Cop_Num').unmask().mask(Importa ? "999-9999-99-99999999" : "999-999-999999999", { placeholder: "_" });
}
function checkLiquidacion() {
    $('#infoLiquida').hide();
    var Tic_Sri = $('#Tic_Cod').find('option:selected').data('ticsri') * 1, Prv_Cod = $('#provFormTemp').getData()['Prv_Cod']; if (Tic_Sri !== 3 || Prv_Cod === '') return;
    $.post("", { liquida: true, Tic_Sri: Tic_Sri, Prv_Cod: Prv_Cod, Cop_Fec: $('#Cop_Fec').val() }, function (response) {
        if (response['success'] === true) {
            var liquida = { actual: (!$.varValid(response['total']) || response['total'] === '' ? 0 : (response['total'] * 1)) };
            $('#infoLiquida').data(liquida);
            if (liquida['actual'] >= 13000) {
                $.alert('Las <u>liquidaciones en Compras</u> de este Proveedor ya exceden el limite 13000!', null, 'remove'); return;
                $('#infoLiquida').attr('title', 'Las <u>liquidaciones en Compras</u> de este Proveedor ya exceden el limite 13000!');
            }
            $('#infoLiquida').attr('title', 'El total de Liquidaciones es ' + liquida['actual']);
        }
    }, 'json');
}

// retenciones
function calculaRetencion() {
    var ids = gridFact.jqGrid('getDataIDs'), rets = [], tot = { Ret_Ren_Tot: 0, Iva_Ren_Tot: 0, Ren_Tot: 0, Val_Pcc: 0, Ret_Uca: 0, Ret_Pca: 0, Ret_Ica: 0 },
        Tic_Sri = $('#Tic_Cod').find('option:selected').data('ticsri') * 1, rise = (Tic_Sri === 2 || Tic_Sri === 9), Cop_Des = $('#Cop_Des').val() * 1;
    if (ids.length <= 1) { $('#retencion').clearGrid(); $('.reteTot').setData({ Val_Pcc: '0.00' }); return; }
    for (var i = 0, z = ids.length - 1; i < z; i++) {
        var row = gridFact.jqGrid('getLocalRow', ids[i]), row_Imp = ((row['Cop_Imp'] * 1) - (Cop_Des > 0 ? (row['Cop_Imp'] * Cop_Des / 100) : 0));
        if ($.varValid(row['Ret_Ren_Cod']) && row['Ret_Ren_Cod'].length > 0) {
            var add = true, ret = { Ren_Ret: 'R', Ren_Rete: 'RENTA', Ren_Cod: row['Ret_Ren_Cod'], Ren_Por: row['Ret_Ren_Por'], Ren_Sri: row['Ret_Ren_Sri'], Ren_Con: row['Ret_Ren_Con'], Ren_Imp: row_Imp };
            $.each(rets, function (i, v) { if (ret['Ren_Cod'] === v['Ren_Cod']) { rets[i]['Ren_Imp'] += ret['Ren_Imp']; add = false; } });
            if (add) rets.push(ret);
            if (String(ret['Ren_Sri']) === String(cod_banano)) { tot['Ret_Uca'] += row['Cop_Can'] * 1; tot['Ret_Ica'] += row_Imp; }
        }
        if ($.varValid(row['Iva_Ren_Cod']) && row['Iva_Ren_Cod'].length > 0 && !rise) {
            var ice_por = ('0' + row['Cop_Ice']) * 1, ice = (ice_por > 0 ? row_Imp * ice_por / 100 : 0);
            var add = true, ret = { Ren_Ret: 'I', Ren_Rete: 'IVA', Ren_Cod: row['Iva_Ren_Cod'], Ren_Por: row['Iva_Ren_Por'], Ren_Sri: row['Iva_Ren_Sri'], Ren_Con: row['Iva_Ren_Con'], Ren_Imp: (row_Imp + ice) * (row['Iva_Por'] / 100) };
            $.each(rets, function (i, v) { if (ret['Ren_Cod'] === v['Ren_Cod']) { rets[i]['Ren_Imp'] += ret['Ren_Imp']; add = false; } });
            if (add) rets.push(ret);
        }
    }
    $.each(rets, function (i, v) {
        rets[i]['Ren_Val'] = $.round($.round(v['Ren_Imp']) * v['Ren_Por'] / 100);
        tot[(v['Ren_Ret'] === 'R' ? 'Ret' : 'Iva') + '_Ren_Tot'] += rets[i]['Ren_Val'];
    });
    tot['Ren_Tot'] = tot['Ret_Ren_Tot'] + tot['Iva_Ren_Tot'];
    tot['Val_Pcc'] = $('#t_rubros').val() * 1 - ($('#Ret_Asu').prop('checked') ? 0 : tot['Ren_Tot']);

    //Desabilita la fecha de la retencion si se selecciona un producto
    //(rets.length > 0 ? $('.ret_field').removeAttr('disabled') : $('.ret_field').attr('disabled', 'disabled'));

    $.each(tot, function (k, v) { tot[k] = $.toFixed(v); });

    if (tot['Ret_Uca'] * 1 > 0 && tot['Ret_Ica'] * 1 > 0) { tot['Ret_Pca'] = $.round(tot['Ret_Ica'] / tot['Ret_Uca'], 8); tot['Ret_Uca'] = $.round(tot['Ret_Uca'], 0); $('.cod_banano').show().find('input').attr('required', 'required'); } else { tot['Ret_Uca'] = tot['Ret_Pca'] = tot['Ret_Ica'] = ''; $('.cod_banano').hide().find('input').removeAttr('required'); }

    $('.reteTot').setData(tot);
    $('#retencion').setRows(rets);
}

function seleccionaRetencion(data) { $('#codiForm').setData(data).formSubmit(); $('#codiDialog').dialog('open'); }

function agregaRetencion(data) {
    var form = $('#codiForm').getData(), ret = {};
    $.each(data, function (k, v) { ret[(form['tipo'] === 'R' ? 'Ret_' : 'Iva_') + k] = v; });
    if (form['checkRentaIva'] === 'N') {
        gridFact.changeRowData(form['index'], ret);
    } else {
        var ids = gridFact.jqGrid('getDataIDs');
        for (var i = 0, z = ids.length - 1; i < z; i++)
            gridFact.changeRowData(ids[i], ret);
    }
    calculaRetencion();
    // if ($.isset('validaRetFec')) validaRetFec();
    $('#codiDialog').dialog('close');
}

function eliminaRetencion(form) { var retBasic = { Ren_Cod: '', Ren_Sri: '', Ren_Por: '', Ren_Con: '' }, ret = {}; $.each(retBasic, function (k, v) { ret[(form['tipo'] === 'R' ? 'Ret_' : 'Iva_') + k] = v; }); gridFact.changeRowData(form['index'], ret); calculaRetencion(); }
function getRentaButton(cv, data, cObjt) {
    var obj, valid = ($.varValid(cv) && cv !== '');
    obj = $('<div class="input-group input-group-xs ret"><span type="text" class="form-control center" title="' + (valid ? cObjt[(data['tipo'] === 'R' ? 'Ret_' : 'Iva_') + 'Ren_Por'] + '% - ' : '') + (valid ? cObjt[(data['tipo'] === 'R' ? 'Ret_' : 'Iva_') + 'Ren_Con'] : '') + '">' + (valid ? cv : '') + '</span><span class="input-group-btn"><button type="button" onclick="' + (valid ? 'elimina' : 'selecciona') + 'Retencion($(this).parent().data(\'originaldata\'));" class="btn btn-' + (valid ? 'warning' : 'info') + '" title="' + (valid ? 'Quitar' : 'Agregar') + ' ' + (data['tipo'] === 'R' ? 'Imp. a la Renta' : 'Ret. del Iva') + '" tabindex="-1"><i class="glyphicon glyphicon-' + (valid ? 'minus' : 'plus') + '"></i></button></span></div>');
    obj.find('.input-group-btn').attr('data-originaldata', $.jsonParser($.extend(data, valid ? {} : { search: '', op_opciones: 'p', checkRentaIva: 'N', Cop_Fec: $("#Cop_Fec").val() })));
    return obj.prop('outerHTML');
}

//VALIDAR FORMATO PARA VARIOS DECIMALES
function dynamicFormatter(cellValue, options, rowObject) {
    if (cellValue == null) { cellValue = 0; }
    var useCurrencyFormat = true; // Cambia esta variable según tu lógica
    if (Ses_Emp_Cod == 534 || Ses_Emp_Cod == 531 || Ses_Emp_Cod == 44 || Ses_Emp_Cod == 340 || Ses_Emp_Cod == 570 || Ses_Emp_Cod == 554 || Ses_Emp_Cod == 432) {
        useCurrencyFormat = false;
    }
    if (useCurrencyFormat) {
        var formattedValue = parseFloat(cellValue).toFixed(2); // Ajusta el número de decimales si es necesario
        return formattedValue; // Devuelve el valor con dos decimales, sin símbolo de moneda
    } else {
        var numberOfDecimals = 4; // Cambia este valor según el número de decimales que necesitas
        var formattedValue = parseFloat(cellValue).toFixed(numberOfDecimals); // Ajusta los decimales
        return formattedValue; // Añade separadores de miles
    }
}

$(function () {
    // DIALOG BUSCAR proveedor
    if ($('#provDialog').length > 0)
        $.createSearchDialog('provDialog', [
            { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 15, align: "center", hidden: true },
            { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
            { label: 'Proveedor', name: 'proveedor', width: 100 },
            { label: 'Cont.', name: 'Prv_Con', width: 20, align: "center", labelLong: 'Obligado a Llevar Contabilidad', formatter: 'truefalse', formatoptions: { msg: false } },
            { label: 'Espe.', name: 'Prv_Esp', width: 20, align: "center", labelLong: 'Contribuyente Especial', formatter: 'truefalse', formatoptions: { msg: false } },
            { label: 'Reg.Micro.', name: 'Prv_Reg', width: 20, align: "center", labelLong: 'Regimen microempresa', formatter: 'truefalse', formatoptions: { msg: false } },
            { label: 'RISE', name: 'Prv_Ris', width: 20, align: "center", labelLong: 'RISE', formatter: 'truefalse', formatoptions: { msg: false } },

            { label: 'G.Cont', name: 'Prv_Gct', width: 20, align: "center", labelLong: 'Gran.Contribuyente ', formatter: 'truefalse', formatoptions: { msg: false } },
            { label: 'Rim.Emp.', name: 'Prv_Rim_Emp', width: 20, align: "center", labelLong: 'Rimpe Emprendedor', formatter: 'truefalse', formatoptions: { msg: false } },
            { label: 'Rim.Neg.p', name: 'Prv_Rim_Np', width: 20, align: "center", labelLong: 'Rimpe Negocio popular', formatter: 'truefalse', formatoptions: { msg: false } },
            { label: 'Ag.Ret', name: 'Prv_Ag_Ret', width: 20, align: "center", labelLong: 'Agente de retención', formatter: 'truefalse', formatoptions: { msg: false } },

            { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectProvee } }
        ], null, null, null, { headertitles: true }, { title: 'Proveedor', text: 'Prs_Ced' });
    // DIALOG BUSCAR Producto
    if ($('#proDialog').length > 0)
        $.createSearchDialog('proDialog', [
            { label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, width: 20, align: "center", hidden: false },
            { label: 'Descripci&oacute;n', name: 'Ite_Lar', width: 110 },
            { label: 'Cod.Barra', name: 'Pro_Bar', width: 110, hidden: true },
            { label: 'Marca', name: 'Mar_Des', width: 40 },
            { label: 'Categor&iacute;a', name: 'Cat_Des', width: 90, align: "center" },
            { label: 'IVA', name: 'Iva_Por', width: 20, align: "center", formatter: 'truefalse', formatoptions: { yesMsg: 'Grava IVA', noMsg: 'No Grava IVA' }, title: false },
            { label: 'Adq.', name: 'Adq_Cor', width: 20, align: "center", formatter: 'title', formatoptions: { title: function (o) { return o['Adq_Des']; } } },
            { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: selectItem, conditional: function (o) { return !(Cof_Con === 'S' && (!$.varValid(o['Pld_Cod']) || o['Pld_Cod'] === '')); }, caseFalse: function () { return '<i class="glyphicon glyphicon-lock orange" title="No se ha Parametrizado una Cuenta Contable!"></i>'; } } }
        ], null, null, null, null, { title: 'Producto', options: [{ label: '&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;', value: 'd' }, { label: '&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;', value: 'c' }] });
    // codigo sri
    if ($('#codiDialog').length > 0)
        $.createSearchDialog('codiDialog', [
            { label: 'C&oacute;d.Int.', name: 'Ren_Cod', key: true, width: 25, align: "center" },
            { label: 'C&oacute;digo', name: 'Ren_Sri', width: 25, align: "center" },
            { label: 'Descripci&oacute;n', name: 'Ren_Con', width: 100 },
            { label: 'Porc.(%)', name: 'Ren_Por', width: 25, align: "center" },
            { label: 'Adq.', name: 'Ren_Tipo', width: 30, align: "center" },
            { label: 'Tipo', name: 'Ren_Rete', width: 30, align: "center" },
            { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: agregaRetencion, conditional: function (o) { return !(Cof_Con === 'S' && (!$.varValid(o['Pld_Cod']) || o['Pld_Cod'] === '')); }, caseFalse: function () { return '<i class="glyphicon glyphicon-lock orange" title="No se ha Parametrizado una Cuenta Contable!"></i>'; } } }
        ], null, null, null, null, { title: 'B&uacute;squeda', options: [] });
    if ($('#asiento').length > 0) {
        $('#asiento').createGrid({
            height: 75, postData: { CheListAjax: true }, caption: 'Asiento Contable <button id="btnComPrint" onclick="$.imprimirUrl($(this).data(\'url\'))" class="btn btn-success btn-xs pull-right" style="margin-top: -2px;"><i class="glyphicon glyphicon-print"></i> Imprimir</button>',
            rowNum: 10000, footerrow: true, userDataOnFooter: true,
            colModel: [
                { label: 'C&oacute;d.Int.', name: 'Asi_Cod', key: true, width: 15, align: "center", hidden: true },
                { label: 'Tipo', name: 'Asi_Deh', hidden: true },
                { label: 'C&oacute;digo', name: 'Pld_Cdc', width: 45 },
                { label: 'Cuenta', name: 'Pld_Des', width: 130 },
                { label: 'Glosa', name: 'Glosa', width: 130 },
                { label: 'Debe', name: 'Debe', width: 65, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' }, summaryType: "sum" },
                { label: 'Haber', name: 'Haber', width: 65, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' }, summaryType: "sum" }
            ],
            loadComplete: function () { $(this).setGridSummary(['Debe', 'Haber'], { Glosa: "<div style='text-align:right;'>TOTALES:</div>" }); }
        }, true); $.clearFooterDiario("#asiento");
    }

    if ($('#asientoRet').length > 0) {
        $('#asientoRet').createGrid({
            height: 75, postData: { CheListAjax: true }, caption: 'Asiento Contable <button id="btnComPrintRet" onclick="$.imprimirUrl($(this).data(\'url\'))" class="btn btn-success btn-xs pull-right" style="margin-top: -2px;"><i class="glyphicon glyphicon-print"></i> Imprimir</button>',
            rowNum: 10000, footerrow: true, userDataOnFooter: true,
            colModel: [
                { label: 'C&oacute;d.Int.', name: 'Asi_Cod', key: true, width: 15, align: "center", hidden: true },
                { label: 'Tipo', name: 'Asi_Deh', hidden: true },
                { label: 'C&oacute;digo', name: 'Pld_Cdc', width: 45 },
                { label: 'Cuenta', name: 'Pld_Des', width: 130 },
                { label: 'Glosa', name: 'Glosa', width: 130 },
                { label: 'Debe', name: 'Debe', width: 65, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' }, summaryType: "sum" },
                { label: 'Haber', name: 'Haber', width: 65, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' }, summaryType: "sum" }
            ],
            loadComplete: function () { $(this).setGridSummary(['Debe', 'Haber'], { Glosa: "<div style='text-align:right;'>TOTALES:</div>" }); }
        }, true); $.clearFooterDiario("#asientoRet");
    }

    var opts = {
        height: 75, postData: { CheListAjax: true },
        caption: 'Detalle Compra <button id="btnCopPrint" onclick="$.imprimirUrl($(this).data(\'url\'))" class="btn btn-success btn-xs pull-right" style="margin-top: -2px;"><i class="glyphicon glyphicon-print"></i> Imprimir</button> ',
        colModel: [
            { label: 'C&oacute;d.Int.', name: 'Cop_Int', key: true, width: 15, align: "center", hidden: true },
            { label: 'Cantidad ', name: 'Cop_Can', width: 45, align: 'right' },
            { label: 'Item', name: 'Ite_Lar', width: 130 },
            { label: 'Desc(%)', name: 'Cop_Dec', width: 65, align: 'right' },
            { label: 'P. Unit.', name: 'Cop_Pru', width: 65, align: 'right' },
            { label: 'Importe', name: 'Cop_Imp', width: 65, align: 'right', formatter: 'currency', formatoptions: { prefix: '', thousandsSeparator: ',', decimalSeparator: '.', decimalPlaces: dynamicFormatter, defaultValue: '0.00' }, summaryType: "sum" }
        ]
    };
    if ($('#productosFull').length > 0) {
        $('.gridProductosCalculo').hide();
        $('#productosFull').createGrid($.extend(opts, { height: 169, caption: 'Detalle Compra', rownumbers: false }), true);
    }
    if ($('#copresult').length > 0)
        $('#copresult').createGrid(opts, true);
    if ($('#detaDocu').length > 0)
        $('#detaDocu').createGrid($.extend(opts, { height: 'auto', width: 550, responsive: false, caption: null, rownumbers: false }), true);

    opts = {
        height: 75, caption: 'Detalle Retención', sortable: true, sortname: 'Ren_Rete', sortorder: "desc", footerrow: true,
        totalCols: ['Ren_Val'], totalDefault: { Ren_Por: $.fieldSummary() },
        colModel: [
            { label: 'C&oacute;d.Int.', name: 'Ren_Cod', key: true, width: 15, align: "center", hidden: true },
            { label: 'C&oacute;d.Int.', name: 'Ren_Ret', width: 15, align: "center", hidden: true },
            { label: 'Ret.', name: 'Ren_Rete', width: 15, align: 'center' },
            { label: 'C&oacute;digo ', name: 'Ren_Sri', width: 15, align: 'center' },
            { label: 'Descripci&oacute;n ', name: 'Ren_Con', width: 50 },
            { label: 'Importe', name: 'Ren_Imp', width: 30, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' }, summaryType: "sum" },
            { label: 'Porc.(%)', name: 'Ren_Por', width: 20, align: 'right' },
            { label: 'Retenci&oacute;n.', name: 'Ren_Val', width: 30, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '0.00' }, summaryType: "sum" }
        ]//,
        //loadComplete: function (){ $(this).setGridSummary(['Ren_Val'],{Ren_Por:"<div style='text-align:right;'>TOTAL:</div>"}); }
    };
    if ($('#retencionFull').length > 0) {
        $('#retencionFull').createGrid($.extend(opts, { height: 150, caption: 'Detalle Retenci&oacute;n' }), true).clearFootRow(['Ren_Val']);
    }
    /*if ($('#reteresult').length > 0)
        $('#reteresult').createGrid($.extend(opts, { caption: 'Detalle Retenci&oacute;n <button id="btnRetPrint" onclick="$.imprimirUrl($(this).data(\'url\'))" class="btn btn-success btn-xs pull-right" style="margin-top: -2px;"><i class="glyphicon glyphicon-print"></i> Imprimir</button><button id="btnRetXml" onclick="window.open($(this).data(\'url\'));" class="btn btn-success btn-xs pull-right" style="margin-top: -2px; display:none; margin-right:2px; "><i class="glyphicon glyphicon-download-alt"></i> Descargar XML</button>' }), true).clearFootRow(['Ren_Val']);
    */
    if ($('#reteresult').length > 0)
        $('#reteresult').createGrid($.extend(opts, { caption: 'Detalle Retenci&oacute;n <button id="btnRetPrint" onclick="imprimirRetencion($(this).data(\'url\'))" class="btn btn-success btn-xs pull-right" style="margin-top: -2px;"><i class="glyphicon glyphicon-print"></i> Imprimir Ret.</button><button id="btnRetXml" onclick="window.open($(this).data(\'url\'));" class="btn btn-success btn-xs pull-right" style="margin-top: -2px; display:none; margin-right:2px; "><i class="glyphicon glyphicon-download-alt"></i> Descargar XML</button>' }), true).clearFootRow(['Ren_Val']);
    if ($('#retencion').length > 0)
        $('#retencion').createGrid($.extend(opts, { height: 219, width: 593, responsive: false, caption: 'Detalle Retención <button type="button" role="button" tabindex="-1" class="ui-button ui-widget ui-state-default ui-corner-all pull-right" title="Cerrar Ventana" onclick="$(\'#retDetaDialog\').dialog(\'close\')"><span class="ui-button-icon-primary ui-icon ui-icon-closethick"></span></button>' }), true).clearFootRow(['Ren_Val']);
    if ($('#detaRete').length > 0)
        $('#detaRete').createGrid($.extend(opts, { height: 'auto', width: 550, responsive: false, caption: null, rownumbers: false }), true).clearFootRow(['Ren_Val']);
    if ($('#retDetaDialog').length > 0)
        $('#retDetaDialog').createDialog({ height: 293, width: 600, noTitleStuff: false, noBorder: true, noOverflow: true, extraClass: 'noMargin' });
    if ($('#docDetaDialog').length > 0)
        $('#docDetaDialog').createDialog({ height: 400, width: 600, noTitleStuff: false, noBorder: true });
    if ($('#docDetaObservacion').length > 0)
        $('#docDetaObservacion').createDialog({ height: 200, width: 600, noTitleStuff: false, noBorder: true });
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
/* REEMBOLSOS */
function setReembolsosGrid(el) {
    if (el) {
        $('#gridReembolsos').show().updateGridsSizes();
    } else {
        reembolsos.clearGrid();
        $('#gridReembolsos').hide();
    }
}
var reembolsos;
$(function () {
    reembolsos = $('#reembolsos');
    if (reembolsos.length > 0) {
        reembolsos.createGrid({
            height: 'auto',
            footerrow: true, totalCols: ['Total'], totalDefault: { Rem_Ced: '<div class="txtRight">TOTAL:<div>' },
            colModel: [
                { label: 'C&oacute;d. Int.', name: 'Rem_Int', width: 30, align: "center", key: true, hidden: true },
                { label: 'Tipo Documento', name: 'Rem_Tic', width: 20, align: "center", classes: 'bgNoRight' },
                { label: 'No. Documento', name: 'Rem_Num', width: 80, align: "center", classes: 'bgNoRight' },
                { label: 'Fecha', name: 'Rem_Fec', width: 45, align: "center", classes: 'bgNoRight' },
                { label: 'Ide.', name: 'Rem_Ide', width: 20, align: "center", classes: 'bgNoRight' },
                { label: 'RUC/C&eacute;dula', name: 'Rem_Ced', width: 75, align: "center" },
                { label: 'Total', name: 'Total', width: 50, align: 'right', formatter: 'number', summaryType: 'sum', summaryRound: 2, summaryRoundType: 'round', classes: 'bgNoColor' },

                { name: 'delete', label: $.createIcon('remove'), width: 30, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: { action: 'RemoveReembolso', icon: 'remove', title: 'Remover Compra', type: 'danger', data: 'Rem_Int' }, resizable: false },
                $.originalRow()
            ]
        }, true, 'reembolsosPager').gridButtonsAdd([
            null, {
                caption: 'Agregar Compra', buttonicon: 'glyphicon glyphicon-plus', onClickButton: function () {
                    var d = '0.00';
                    $('#comprasReembolsoForm').setData({ Rem_Niv: d, Rem_Siv: d, Rem_Oiv: d, Rem_Eiv: d, Rem_Iva: d, Rem_Ice: d });
                    $('#comprasReembolsoDialog').dialog('open');
                }
            }
        ]);
        $('#gridReembolsos').hide();
        if ($('#comprasReembolsoDialog').length > 0) $('#comprasReembolsoDialog').createDialog({ icon: 'plus', width: 500, height: 450 });
        if ($.mask) $('#Rem_Num').mask("999-999-999999999", { placeholder: "_" });
        $('#Rem_Aut').on('change', function () { var val = $(this).val(), aut = val.length; $(this).attr('title', val); if (aut !== 0 && aut !== 10 && aut !== 37 && aut !== 49) { $(this).fieldValid(false, 'El campo debe tener 10, 37 o 49 digitos!'); } else { $(this).fieldValid(aut === 0 ? '' : true); } });
        $('#Cop_Fec').trigger('change');
    }
});
function RemoveReembolso(index) { reembolsos.delRowData(index); reembolsos.gridUpdate(); }
function AgregaReembolso() {
    var data = $('#comprasReembolsoForm').getData();
    data['Rem_Int'] = reembolsos.nextIndex('Rem_Int');
    data['Rem_Ide'] = data['Rem_Ide'].toNum().padLeft(2);
    data['Rem_Tic'] = data['Rem_Tic'].toNum().padLeft(2);
    data['Total'] = (data['Rem_Niv'].toNum() + data['Rem_Siv'].toNum() + data['Rem_Oiv'].toNum() + data['Rem_Eiv'].toNum());
    if (data['Total'] === 0) {
        return $.alert("Los Importe no pueden estar en cero!", null, "alert");
    }
    if (data['Rem_Siv'].toNum() > 0 && data['Rem_Iva'].toNum() === 0) {
        return $.alert("El valor de IVA de " + data['Rem_Siv'] + " no puede ser cero!", null, "alert");
    }
    data['Total'] += (data['Rem_Iva'].toNum() + data['Rem_Ice'].toNum());
    $('#comprasReembolsoDialog').dialog('close');
    reembolsos.setRow(data).gridUpdate();
}
function cambiarPago(data) {
    $('#For_Cod2').data(data);
    data['For_Cod'] = data['Pago'] === 'Contado' ? 1 : 2;
    $('#changePagoForm').setData(data);
    $("#Cpp_Ven2").datepicker("option", "minDate", data['Cop_Fec']);
    if ($.isEmpty(data['Cpp_Ven'])) {
        var d = new Date(data['Cop_Fec']); d.setDate(d.getDate() + 15); $('#Cpp_Ven2').datepicker("setDate", d);
    }
    $('#changePagoDialog').dialog('open');
}

function checkCuentaPago2() {
    var data = $('#For_Cod2').data();
    $.postDataJson("", { cuentasPago: true, For_Cod: $('#For_Cod2').val(), Cop_Fec: data['Cop_Fec'], Pld_Cod: data['Pld_Cod_Pag'] }, function (r) {
        if (r['total'] > 0) {
            $('#Pag_Pld2').html(r['cuentas']);
        } else {
            $('#Pag_Pld2').val('').html(''); $.alert('Error al buscar la cuenta pago para la fecha indicada');
        }
    }, function () { return $.alert('Error al Buscar las cuentas Pago'); }, function () { return $.alert('Error al Buscar las cuentas Pago'); },
        function () { });
}

function saveChangePago() {
    var data = $('#changePagoForm').getData('saveChangePago');
    //console.log(data);
    $.createDialogConfirm('¿Est&aacute; seguro que desea guardar los cambios?', data, function (d) {
        $.saveDataJson('', d, function (r) {
            $('#searchGrid').trigger('reloadGrid', []);
            $('#changePagoDialog').dialog('close');
        });
    });
}

function ImpCom(rowCompra) {
    $.getDataJson('', { 'cargarReportes': true }, function (res) {
        var reportes = res['reportes'];
        $.varValid(reportes[1]) ? $.imprimirUrl(reportes[1] + '?codigo=' + rowCompra.Com_Cod) : $.alert('Sin reportes asociados');
    }, function (err) {
        console.log(err['message']);
    });
}

/**/
$.fn.fmatter.ice = function (cv, opts, cObjt) { var ice_por = cObjt['Cop_Ice'] || cObjt['Ice_Por']; if ($.varValid(ice_por) && ice_por !== '' && !isNaN(ice_por) && ice_por * 1 > 0) return ice_por + ' %'; else return ''; };
$.fn.fmatter.ice.unformat = function (cv, opts, cObjt) { return cv.replace(' %', ''); };
$.fn.fmatter.impRenta = function (cv, opts, cObjt) { if (!$.varValid(cObjt['Pro_Cod']) || cObjt['Pro_Cod'] === '') return ''; return getRentaButton(cv, { tipo: 'R', index: cObjt['index'] }, cObjt); };
$.fn.fmatter.impRenta.unformat = $.unformatCellHtml;
$.fn.fmatter.retIva = function (cv, opts, cObjt) { if (!$.varValid(cObjt['Pro_Cod']) || cObjt['Pro_Cod'] === '') return ''; if (cObjt['Iva_Por'] * 1 === 0) return ''; return getRentaButton(cv, { tipo: 'I', index: cObjt['index'] }, cObjt); };
$.fn.fmatter.retIva.unformat = $.unformatCellHtml;
$.fn.fmatter.edicion = function (cv, opts, cObjt) {


    var is_consultar = $("#is_consultar").length ? $("#is_consultar").val() : "";
    if (is_consultar != 'CNS') {
        if (cObjt['Ret_Aut'] === 'S' && anula == true && delFila(cObjt)) {
            return '<i title="Bloqueado por Normativa SRI, La normativa del SRI Resolución NAC-DGERCGC25-00000017, establece que los documentos solo pueden ser anulados hasta el dia 07 del mes siguiente a su emisión." class="glyphicon glyphicon-lock orange"></i>';
        }
    }

    if (cObjt['Com_Edit'] === 'N') return '<i title="El comprobante contable es formato anterior" class="glyphicon glyphicon-lock orange"></i>';
    if (cObjt['Cop_Est'] !== 'A') return '<i title="Registro Anulado/Inactivo" class="glyphicon glyphicon-remove red"></i>';
    if (cObjt['Ret_Aut'] === 'S' && anula !== true) {
        return (cObjt['Pago'] === 'Credito' || cObjt['Pago'] === 'Contado') && (cObjt['Cpp_Det'] !== 'S' || cObjt['onlyRetencion'] == true) ? $.getGridButton('cambiarPago', cObjt, "Cambiar Forma de Pago", null, null, 'warning') : '<i title="Retencion Electronica Validada" class="fa fa-globe green"></i>';
    }
    if (cObjt['Cpp_Det'] === 'S' && anula === true && !$.isset('consultar')) return '<i title="Contiene Pagos Activos" class="fa fa-money green"></i>';
    if (cObjt['Rcc_Det'] === 'S') return '<i title="Reposici&oacute;n de caja chica" class="fa fa-creative-commons purple"></i>';

    //Validar que ya esta autorizada la liquidacion de compras
    if (cObjt['Aut_Cop'] === 'S' && anula !== true) {
        return (cObjt['Pago'] === 'Credito' || cObjt['Pago'] === 'Contado') && (cObjt['Cpp_Det'] !== 'S' || cObjt['onlyRetencion'] == true) ? $.getGridButton('cambiarPago', cObjt, "Cambiar Forma de Pago", null, null, 'warning') : '<i title="Retencion Electronica Validada" class="fa fa-globe green"></i>';
    }
    return $.getGridButton(editDocument, cObjt, "Seleccionar Documento");
};
$.fn.fmatter.edicion.unformat = $.unformatCellHtml;

//Metodo para redondear tres decimales
function redondeoDosDecimales(numero) {
    return Number(Math.round(Number(numero + 'e2')) + 'e-2').toFixed(2);
}

//Limpiar campos de la negociacion
function limpiarCamposNego() {
    document.getElementById("Num_Neg").value = "";
    document.getElementById("Cod_Neg").value = "";
}