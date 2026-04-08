/*
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */
var jgridResumen,jgridElect,jgridExporta,jgridBusqSri,zipFile;
$(function(){
    jgridResumen=$('#gridResumen');
    jgridElect=$('#gridElectroni');
    jgridExporta=$('#gridExporta');
    // Creo las Tabs
    $("#tabsMain").createTabs({init:{
        'tabs-1':function(){
            jgridResumen.createGrid({
                height: 250,caption:'Reporte ATS Unificado', rownumbers: false, footerrow: true, userDataOnFooter: false,
                totalCols:['sub0','sub12','iva','valret','total','renta','iva10','iva20','iva30','iva70','iva100'],
                totalDefault:{prov:$.fieldSummary({msg:'TOTAL GLOBAL'})}, clearFootRow:true,
                colModel: [
                    { label: 'RUC Emp.', name: 'idEmpresa', width: 100,cellattr: function() {return 'style="'+excelFormats.text+'"';}, hidden:true },
                    { label: 'Empresa', name: 'empresa', width: 100,cellattr: function() {return 'style="'+excelFormats.text+'"';}, hidden:true, summaryType:$.fieldHeader},
                    { label: 'Periodo', name: 'periodo', width: 45, cellattr: function() {return 'style="'+excelFormats.text+'"';}, hidden:true, align:'center' },
                    { label: 'Fecha', name: 'fecha', width: 45, cellattr: function() {return 'style="'+excelFormats.date+'"';}, align:'center' },
                    { label: 'Tipo', name: 'tipo', width: 100, hidden:true },
                    { label: 'Sustento', name: 'sustento', width: 20, align:'center' },
                    { label: 'Tipo', name: 'tipo_long', width: 75, hidden:true },
                    { label: 'Tipo', name: 'tipo_union', width: 50, formatter:'union', formatoptions:{cols:['tipo','tipo_long'],sep:'-'} },
                    { label: 'Numero', name: 'documento', width: 75, cellattr: function() {return 'style="'+excelFormats.text+'"';} },
                    { label: 'Autorizacion', name: 'autorizacion', width: 50, cellattr: function() {return 'style="'+excelFormats.text+'"';} },
                    { label: 'Ruc. Prov.', name: 'ruc', width: 45,align:"center",cellattr: function() {return 'style="'+excelFormats.text+'"';} },
                    { label: 'Proveedor', name: 'prov', width: 100, cellattr: function() {return 'style="'+excelFormats.text+'"';}, summaryType:$.fieldSummarys },
                    { label: 'Pago SRI', name: 'pago_sri', width: 30, align: 'center' },

                    { label: 'Sub.0%', name: 'sub0', width: 45, align: 'right', formatter:'number', summaryTpl:"{0}", summaryType:"sumNotNC",summaryRound:'2', summaryRoundType:'round'},
                    { label: 'Sub.12%', name: 'sub12', width: 45, align: 'right', formatter:'number',summaryTpl:"{0}", summaryType:"sumNotNC",summaryRound:'2', summaryRoundType:'round' },
                    { label: 'Iva', name: 'iva', width: 35, align: 'right', formatter:'number', summaryTpl:"{0}", summaryType:"sumNotNC",summaryRound:'2', summaryRoundType:'round'},
                    { label: 'Total', name: 'total', width: 55, align: 'right', formatter:'number',summaryTpl:"{0}", summaryType:"sumNotNC",summaryRound:'2', summaryRoundType:'round'},
                    { label: 'Retencion', name: 'retencion', width: 75, hidden:true, cellattr: function() {return 'style="'+excelFormats.text+'"';} },
                    { label: 'Autorizacion', name: 'aut_retencion', width: 50, hidden:true, cellattr: function() {return 'style="'+excelFormats.text+'"';} },
                    { label: 'Codigos', name: 'codsri', width: 30, hidden:true },
                    { label: 'Porcentajes', name: 'porrenta', width: 30, hidden:true },
                    { label: 'Renta', name: 'renta', width: 35, hidden:true, align: 'right', formatter:'number', summaryTpl:"{0}", summaryType:"sumNotNC",summaryRound:'2', summaryRoundType:'round'},
                    { label: '10% IVA', name: 'iva10', width: 35, hidden:true, align: 'right', formatter:'number', summaryTpl:"{0}", summaryType:"sumNotNC",summaryRound:'2', summaryRoundType:'round'},
                    { label: '20% IVA', name: 'iva20', width: 35, hidden:true, align: 'right', formatter:'number', summaryTpl:"{0}", summaryType:"sumNotNC",summaryRound:'2', summaryRoundType:'round'},
                    { label: '30% IVA', name: 'iva30', width: 35, hidden:true, align: 'right', formatter:'number', summaryTpl:"{0}", summaryType:"sumNotNC",summaryRound:'2', summaryRoundType:'round'},
                    { label: '70% IVA', name: 'iva70', width: 35, hidden:true, align: 'right', formatter:'number', summaryTpl:"{0}", summaryType:"sumNotNC",summaryRound:'2', summaryRoundType:'round'},
                    { label: '100% IVA', name: 'iva100', width: 35, hidden:true, align: 'right', formatter:'number', summaryTpl:"{0}", summaryType:"sumNotNC",summaryRound:'2', summaryRoundType:'round'},

                    { label: 'T.Reten.', name: 'valret', width: 35, align: 'right', hidden:true, formatter:'number', summaryTpl:"{0}", summaryType:"sumNotNC",summaryRound:'2', summaryRoundType:'round'},
                    { label: '#', name: 'id', key: true, width: 30,align:"center", hidden:true }
                ],// pgtext: "Mostrando {0} Documentos.",
                groupingView: {
                    groupField: ["idEmpresa"/*,"tipo_long"*/],groupColumnShow:[false,false],
                    groupText: ["<div class='txtLeft'><b> {empresa} <span class='hidden'>&nbsp;-&nbsp;</span> </b><span style='float:right;'> {1} Item(s)</span></div>","<div class='txtLeft'><b>{0}</b></div>"],
                    groupOrder: ["asc","asc"], groupSummary:[false,true],groupCollapse: false
                },grouping: true
            },true,"#gridResumenPager",{}).gridButtonsAdd([
                { caption: ' Imprimir', buttonicon:'print', onClickButton:function(){ jgridResumen.jqGrid('printGrid',{nombre:'Reporte ATS',bodyBorder:false, removeHiddens:true}); } },
                { caption: ' Descargar Excel', buttonicon:'download-alt', onClickButton:function(){ jgridResumen.jqGrid('exportGridExcel',{nombre:'Reporte_ATS',hoja:'Hoja ATS',caption:true,generated:false, print:true, removeHiddens:true}); } }
            ]);
        },
        'tabs-4':function(){
            jgridElect.createGrid({
                height: 300, caption:'&nbsp;', rownumbers: false, pgtext: "Mostrando {0} Documentos.", footerrow: true, userDataOnFooter: false, clearFootRow:true,
                //totalCols:['base12','base0','renta'], totalDefault:{prov:$.fieldSummarys()},
                colModel: [
                    { label: '#', name: 'id', key: true, width: 10,align:"center"},
                    { label: 'Ruc', name: 'ruc', width: 30,align:"center",cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';}},
                    { label: 'Comprobante', name: 'tipo', width: 30,cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';} },
                    { label: 'Numero', width: 30,name: 'numero',align:"center", sorttype:"date"},
                    { label: 'Aplica', width: 30,name: 'numero2',align:"center", sorttype:"date"},
                    { label: 'Clave de Acceso', name: 'clave', width: 100,align:"center", cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';}},
                    { label: 'Fecha', name: 'fecha', width: 30,align:"center", cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';}}
                ]
            },true,"#gridElectroniPager").gridButtonsAdd([
                { caption: ' Imprimir', buttonicon:'print', onClickButton:function(){ jgridElect.jqGrid('printGrid',{nombre:'Reporte Docs. Electronicos',bodyBorder:false, removeHiddens:true}); } },
                { caption: ' Descargar Excel', buttonicon:'download-alt', onClickButton:function(){ jgridElect.jqGrid('exportGridExcel',{nombre:'Reporte_Electronicos',hoja:'Hoja ATS',caption:true,generated:false, print:true, removeHiddens:true}); } }
            ]);
        },
        'tabs-5':function(){
            jgridExporta.createGrid({
                height: 300,caption:'&nbsp;', rownumbers: false, totalCols:['val_fac','val_fob'], totalDefault:{autorizacion:$.fieldSummarys()}, clearFootRow:true,
                colModel: [
                    { label: 'Referendo', name: 'ref', key: true, width: 25,align:"center",cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';} },
                    { label: 'Nom. Trans.', name: 'trans', width: 20,align:"center",cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';}},
                    { label: 'Emisión', name: 'fecha', width: 15, sorttype:"date", align: 'center',cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.date+'"';} },
                    { label: 'Serie', width: 20,name: 'serie',align:"center",cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';}  },
                    { label: 'Secuencia', name: 'num', width: 20,align:"center", cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';}},
                    { label: 'Autorización', name: 'autorizacion', width: 65,cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';} },
                    { label: 'Valor Factura', name: 'val_fac', width: 20,align: 'right',formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'}, cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.currency+'"';} , formatter:'currency', decimalPlaces: '2', summaryRound: 2},
                    { label: 'Valor FOB', name: 'val_fob',  width: 20,align: 'right',formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'}, cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.currency+'"';} , formatter:'currency', decimalPlaces: '2', summaryRound: 2}
                ], pgtext: "Mostrando {0} Documentos.", footerrow: true, userDataOnFooter: false
            },true,'gridExportaPager').gridButtonsAdd([
                { caption: ' Imprimir', buttonicon:'print', onClickButton:function(){ jgridExporta.jqGrid('printGrid',{nombre:'Reporte ATS Export.',bodyBorder:false, removeHiddens:true}); } },
                { caption: ' Descargar Excel', buttonicon:'download-alt', onClickButton:function(){ jgridExporta.jqGrid('exportGridExcel',{nombre:'Reporte_ATS_Export',hoja:'Hoja ATS',caption:true,generated:false, print:true, removeHiddens:true}); } }
            ]);
            jgridExporta.setGroupHeaders({useColSpanStyle: true, groupHeaders: [{ "numberOfColumns": 3, "titleText": "Factura", "startColumnName": "serie" }]});
        },
        'tabs-6':function(){
            gridDevol=$("#ivaDevol").createGrid({
                height:250, caption:'&nbsp;', footerrow:true, userDataOnFooter:false, rownumbers:false, viewrecords:true, pginput:false, pgbuttons:false,
                totalCols:['base','iva','ivadev'], totalDefault:{autret:$.fieldSummary({msg:'TOTAL'})}, clearFootRow:true,
                colModel: [
                    { label: 'Cód.Int.', name: 'id', key: true, width: 55,align:"center",hidden:true },
                    { label: 'Código', name: 'codigo', width: 75,hidden:false,align:"center",cellattr:function(){return 'style="'+excelFormats.text+'"';} },
                    { label: 'C.I/R.U.C', name: 'ruc', width: 50,align:"center",cellattr: function(){return 'style="'+excelFormats.text+'"';} },
                    { label: 'TipoPrv', width: 45, name: 'tpIdProv',align:"center",hidden:true, classes:'bgNoRight bgNoColor'},
                    { label: 'Empresa', width: 45, name: 'empresa',align:"center",hidden:false, cellattr: function(){return 'style="'+excelFormats.text+'"';} },
                    { label: 'Anio', width: 45, name: 'anio',align:"center",hidden:true },
                    { label: 'Mes', width: 45, name: 'mes',align:"center",hidden:true },
                    { label: 'TipoDoc', width: 45, name: 'tipo', align:"center",hidden:true },
                    { label: 'Tipo', name: 'tipoComprobante', width: 40,align:"left",cellattr: function () {return 'style="'+excelFormats.text+'"';} },
                    { label: 'Sustento', name: 'sustento', width: 25,align:"center",cellattr: function () {return 'style="'+excelFormats.text+'"';} },
                    { label: 'Proveedor', name: 'proveedor', width: 75,cellattr: function () {return 'style="'+excelFormats.text+'"';} },

                    { label: 'Fecha', width: 45,name: 'fecha',align:"center", sorttype:"date"},
                    { label: 'Estab.', width: 20,name: 'estab',align:"center",cellattr: function(){return 'style="'+excelFormats.text+'"';} },
                    { label: 'Punto', width: 20,name: 'impre',align:"center",cellattr: function(){return 'style="'+excelFormats.text+'"';} },
                    { label: 'Secuencia', name: 'documento', width: 40,align:"center", cellattr:function(){return 'style="'+excelFormats.text+'"';} },
                    { label: 'Aut. Compra', name: 'autorizacion', width: 40,cellattr: function(){return 'style="'+excelFormats.text+'"';} },
                    { label: 'Aut. Reten.', name: 'autret', width: 40,hidden:false, cellattr: function(){return 'style="'+excelFormats.text+'"';} },
                    { label: 'Base Imp.', name: 'base', width: 45, align: 'right',formatter:'number', formatoptions: {}, summaryType: sumNotNC },
                    { label: 'Iva', name: 'iva', width: 45, align: 'right',formatter:'number', formatoptions: {}, summaryType:sumNotNC },
                    { label: 'Devol', name: 'ivadev', width: 45, align: 'right',formatter:'number', summaryType: sumNotNC },
                    { label: '&nbsp;',  width: 15,name: 'act', formatter:'gridButton',formatoptions:{title:"Quitar Item",action:'deleteRow',data:'id',type:'danger',icon:'remove'}}
                ]
            },true,'#ivaDevolPager');
        },
        'tabs-7':function(){
            jgridBusqSri=$("#gridBusSri").createGrid({
                height: 300, caption:'Busqueda del Sri', rownumbers: false, pgtext: "Mostrando {0} Documentos.", footerrow: true, userDataOnFooter: false, clearFootRow:true,
                totalCols:['IMPORTE_TOTAL'], totalDefault:{FECHA_AUTORIZACION:$.fieldSummarys()},
                colModel: [
                    { label: '#', name: 'id', key: true, width: 10,align:"center"},
                    { label: 'Ruc Emisor', name: 'RUC_EMISOR', width: 30,align:"center",cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';}},
                    { label: 'Emisor', name: 'RAZON_SOCIAL_EMISOR', width: 50},
                    { label: 'Fecha', name: 'FECHA_EMISION', width: 25, align:"center"},
                    { label: 'Cod.', width: 10,name: 'TIPO_DOC',align:"center",cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';}},
                    { label: 'Comprobante', name: 'COMPROBANTE', width: 20},
                    { label: 'Ruc Receptor', name: 'IDENTIFICACION_RECEPTOR', width: 30,cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';}},
                    { label: 'Numero', width: 40,name: 'SERIE_COMPROBANTE',align:"center"},
                    { label: 'Clave Acceso', width: 40,name: 'CLAVE_ACCESO',align:"center",cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';}},
                    { label: 'Autorizacion', width: 40,name: 'NUMERO_AUTORIZACION',align:"center",cellattr: function (rowId, val, rawObject, cm, rdata) {return 'style="'+excelFormats.text+'"';}},
                    { label: 'Fecha Aut.', name: 'FECHA_AUTORIZACION', width: 25, align:"center"},
                    { label: 'Total', name: 'IMPORTE_TOTAL', width: 35, align: 'right', formatter:'currency' },
                    { label: 'XML', name: 'actXml', width: 20, formatter:'gridButton', formatoptions:{ action:'downloadFileSri',data:['CLAVE_ACCESO',{'type':'xml'}], title:'Descargar XML', icon:'fa-file-code-o', type:'primary' } },
                    { label: 'PDF', name: 'actXml', width: 20, formatter:'gridButton', formatoptions:{ action:'downloadFileSri',data:['CLAVE_ACCESO',{'type':'pdf'}], title:'Descargar PDF', icon:'fa-file-pdf-o', type:'primary' } }
                ]
            },true,"#gridBusSriPager").gridButtonsAdd([
                { caption: ' Imprimir', buttonicon:'print', onClickButton:function(){ jgridBusqSri.jqGrid('printGrid',{nombre:'Reporte Docs. Electronicos',bodyBorder:false, removeHiddens:true, footer:true, removeCols:[0,12,13]}); } },
                { caption: ' Descargar Excel', buttonicon:'download-alt', onClickButton:function(){ jgridBusqSri.jqGrid('exportGridExcel',{nombre:'Reporte_BusquedaSri',hoja:'Hoja ATS',caption:true,generated:false, footer:true, print:true, removeHiddens:true, removeCols:[0,12,13]}); } },
                { caption: ' Descargar Comprimido', buttonicon:'download-alt', onClickButton:downloadAllFilesSri }
            ]);
        }
    }});
});
function sumNotNC(v,n,obj){ return isNaN(v)?0:(obj['tipo']==='04'?-1*v:v); }
// ---- REVISAR ATSs - BUSQUEDA DE DATOS EN ATSs
function clearResumen(){ jgridResumen.jqGrid("clearGridData"); }
function loadResumenXML(){
    clearResumen();
    $("#formResumen").effect("highlight",{},500);
    var retencion=['retencion','aut_retencion','valret'], det_retencion=['codsri','porrenta','renta','iva10','iva20','iva30','iva70','iva100'],  type = $("#changeGroupGrid").val();
    jgridResumen.gridColUpdate('show',['ruc','prov','fecha','sustento','tipo_union','documento','autorizacion','periodo'].concat(retencion).concat(det_retencion));
    if(!$('#setRetencion').is(':checked')) jgridResumen.gridColUpdate('hide',retencion);
    if(!$('#setDetalleRet').is(':checked')) jgridResumen.gridColUpdate('hide',det_retencion);
    jgridResumen.jqGrid('groupingGroupBy',["idEmpresa"]);
    if($('#isAgrupado').is(':checked')){
        var hiddens=['sustento','documento','autorizacion'].concat(['retencion','aut_retencion','codsri','porrenta']);
        switch(type){
            case 'fecha': jgridResumen.gridColUpdate('hide',hiddens.concat(['ruc','prov','tipo_union','periodo'])); break;
            case 'tipo_long': jgridResumen.gridColUpdate('hide',hiddens.concat(['ruc','prov','fecha','periodo'])); break;
            case 'ruc': jgridResumen.gridColUpdate('hide',hiddens.concat(['fecha','tipo_union','periodo'])); break;
            case 'periodo': jgridResumen.gridColUpdate('hide',hiddens.concat(['ruc','prov','tipo_union','fecha'])); break;
        }
    }else{
        switch(type){
            case 'fecha': jgridResumen.gridColUpdate('hide',['fecha']); break;
            case 'tipo_long': jgridResumen.gridColUpdate('hide',['tipo_union']); break;
            case 'ruc': jgridResumen.gridColUpdate('hide',['ruc','prov']); break;
            case 'periodo': jgridResumen.gridColUpdate('hide',['periodo']); break;
        }
        if(type!=='clear') jgridResumen.jqGrid('groupingGroupBy',["idEmpresa",type]);
    }
    $.postMultiPartJson('',$.getFormData("formResumen"),function(r){
        jgridResumen.setRowsByIndex(r['rows'],'id');//jgridResumen.setRowsByIndex(r['rows'],'id');
    }, clearResumen, clearResumen);
}
function setResumen(){
    var grp=$("#changeGroupGrid");
    grp.val(this.is(':checked')?'ruc':'clear');
    grp.find('option[value=clear]').prop('disabled',this.is(':checked'));
}
// ---- CONVERTIR UN ATS AL ULTIMO FORMATO
var archivos=[],editorConvert;
function loadAtsConvertXML(){
    $('#editorConvertTitle').html('');
    $('#archivos').html('');
    editorConvert.setValue("",-1);
    $("#formConvertAts").effect("highlight",{},500);
    $.postMultiPartJson('',$.getFormData("formConvertAts"),function(r){
        var options='';
        archivos=r['rows'];
        for(var i=0;i<(archivos.length);i++){
            options=options+'<option value="'+archivos[i]['nombre']+'">'+archivos[i]['nombre']+'</option>';
        }
        $('#archivos').html(options);
        $('#editorConvertTitle').html(archivos[0]['nombre']);
        editorConvert.setValue(vkbeautify.xml(archivos[0]['xml']), -1);
    });
}
function setArchivo(nombre){
    $('#editorConvertTitle').html('');
    for(var i=0;i<(archivos.length);i++){
        if(archivos[i]['nombre']===nombre){
            $('#editorConvertTitle').html(nombre);
            editorConvert.setValue(vkbeautify.xml(archivos[i]['xml']), -1);
            break;
        }
    }
}
$(document).ready(function () {
    $("#editorConvert").css('height','350px');
    editorConvert = ace.edit("editorConvert");
    editorConvert.setTheme("ace/theme/sqlserver");
    editorConvert.session.setMode("ace/mode/xml");
});
// ---- REVISAR UN XML CUALQUIERA EN EL EDITOR
var editor, nameFile='ninguno.xml';
function loadXML(){
    $("#formXml").effect("highlight",{},500);
    var reader = new FileReader();
    reader.onload = function(e) {
        nameFile=document.getElementById("archivoXML").value.replace(/.*[\/\\]/, '');
        editor.setValue(vkbeautify.xml(reader.result), -1);
        $('#editorTitle').html(nameFile);
    };
    reader.readAsText(document.getElementById("archivoXML").files[0]);
}
$(document).ready(function () {
    $("#editor").css('height','350px');
    editor = ace.edit("editor");
    editor.setTheme("ace/theme/sqlserver");
    editor.session.setMode("ace/mode/xml");
    editor.$blockScrolling = Infinity;
});
// ---- REVISAR DOCUMENTOS ELECTRONICOS
function clearElect(){ jgridElect.jqGrid("clearGridData"); jgridElect.jqGrid("setCaption",' ');  }
function loadElectronicos(){
    $("#formElectronicos").effect("highlight",{},500);
    $.postMultiPartJson('',$.getFormData("formElectronicos"),function(r){
        jgridElect.jqGrid("setCaption",r['empresa']);
        jgridElect.setRows(r['rows']);
    }, clearElect, clearElect);
}
// ---- ATS DE EXPORTACIONES - REVISA EXPORTACIONES
function clearExporta(){ jgridExporta.jqGrid("clearGridData"); jgridExporta.jqGrid("setCaption",' ');  }
function loadExporta(){
    $("#formExporta").effect("highlight",{},500);
    $.postMultiPartJson('',$.getFormData("formExporta"),function(r){
        jgridExporta.jqGrid("setCaption",r['empresa']);
        jgridExporta.setRows(r['rows']);
    }, clearExporta, clearExporta);
}
// ---- DEVOLUCION DE IVA Y REVISAR IVA
var gridDevol;
function clearDevolIva(){ gridDevol.jqGrid("clearGridData"); }
function loadDevolXML(){
    $("#formDevolIva").effect("highlight",{},500);
    $.postMultiPartJson('',$.getFormData("formDevolIva"),function(r){
        gridDevol.jqGrid("setCaption",r['empresa']);
        gridDevol.setRowsByIndex(r['rows'],'id');
    }, clearDevolIva, clearDevolIva);
}
function deleteRow(id){ //console.log(id);
    gridDevol.delRowData(id);
    gridDevol.setGridSummary(['ivadev','base','iva'],{autret:'<div style="text-align:right">TOTALES:</div>'});
}
function tagXml(tag,val){ return `<${tag}>${val}</${tag}>`; }
function createAts(ruc,anio,mes,xml){ return '<'+'?xml version="1.0" encoding="ISO-8859-1" standalone="yes"?><devIva><numeroRuc>'+ruc+'</numeroRuc><anio>'+anio+'</anio><mes>'+mes+'</mes><compras>'+xml+'</compras><importaciones><importacionesBienes><baseImponible>0</baseImponible><montoIva>0</montoIva></importacionesBienes><importacionesActivosFijos><baseImponible>0</baseImponible><montoIva>0</montoIva></importacionesActivosFijos></importaciones></devIva>'; }
function saveXml(){
    $("#loader").show();
    var data=gridDevol.getGridBatch(),ats="";
    $.each(data,function(i,v){
        ats+="<detalleCompras>";
        ats+=tagXml('codSustento',v['sustento']);
        ats+=tagXml('tpIdProv',v['tpIdProv']);
        ats+=tagXml('idProv',v['ruc']);
        ats+=tagXml('tipoComprobante',v['tipo']);
        ats+=tagXml('fechaRegistro',v['fecha']);
        ats+=tagXml('establecimiento',v['estab']);
        ats+=tagXml('puntoEmision',v['impre']);
        ats+=tagXml('secuencial',v['documento']);
        ats+=tagXml('autorizacion',v['autorizacion']);
        ats+=tagXml('baseImponible',v['base']);
        ats+=tagXml('montoIva',v['iva']);
        ats+=tagXml('ivaSolicitado',v['ivadev']);
        ats+="</detalleCompras>";
    });
    $.downloadFile(createAts(data[0]['empresa'],data[0]['anio'],data[0]['mes'],ats),'Devolucion_Iva_'+data[0]['empresa']+'_'+data[0]['anio']+'_'+data[0]['mes']+'.xml');
    $("#loader").hide();
}
// cargar Busqueda Sri
function loadBusquedaSri(){
    $("#formBusquedaSri").effect("highlight",{},500);
    $.postMultiPartJson('',$.getFormData("formBusquedaSri"),function(r){
        jgridBusqSri.jqGrid("setCaption","Busqueda del Sri: "+r['file']);
        jgridBusqSri[0].p['file']=r['file'];
        jgridBusqSri.setRowsByIndex(r['rows'],'id');
    }, clearElect, clearElect);
}
// Create Base64 Object
var base64toBlob=function(r,e,n){e=e||"",n=n||512;for(var t=atob(r),a=[],o=0;o<t.length;o+=n){for(var l=t.slice(o,o+n),h=new Array(l.length),b=0;b<l.length;b++)h[b]=l.charCodeAt(b);var v=new Uint8Array(h);a.push(v);}return new Blob(a,{type:e});};
function downloadFileSri(data){
    data['downloadFileSri']=true;
    $.getDataJson("",data, function(r){
        var blob = base64toBlob(r[data.type],data.type==='pdf'?'application/pdf':"text/plain;charset=utf-8");
        saveAs(blob, r.name);
    },function(r){ return $.alert("No se logro descargar el archivo, "+r.error+"!"); });
}
var files=0;
function notFile(clave){ zipFile.file(clave+".txt", "No se logro descargar este xml!"); return false; }
function downloadAllFilesSri(){
    if($.isUnd(jgridBusqSri[0].p['file'])) return $.alert("Debe cargar un archivo de busqueda!");
    var datos=jgridBusqSri.getGridBatch();
    if(datos.length===0) return $.alert("No existen archivos para descargar!");
    $('#loader').show();
    files=datos.length;
    zipFile=new JSZip();
    zipFile.file(jgridBusqSri[0].p['file']+".xls",new Blob([jgridBusqSri.jqGrid('exportGridElement',{removeHiddens:true, removeCols:[0,12,13], footer:true}).exportarExcelBlob('Listado')], {type: 'text/plain'}));
    $.each(datos,function(i,v){
        var data={downloadFileSri:true,CLAVE_ACCESO:v.CLAVE_ACCESO,type:'all'};
        $.getDataJson("",data, function(r){
            zipFile.file(v.CLAVE_ACCESO+".xml", base64toBlob(r.xml,"text/plain;charset=utf-8"));
            if(r.pdf!=='')
                zipFile.file(v.CLAVE_ACCESO+".pdf", base64toBlob(r.pdf,"application/pdf"));
        },function(){ return notFile(v.CLAVE_ACCESO); }, function(){ return notFile(v.CLAVE_ACCESO); }, function(){
            files--;
            if(files<=0){
                zipFile.generateAsync({type:"blob"}).then(function (blob) {
                    saveAs(blob, jgridBusqSri[0].p['file']+".zip");
                    $('#loader').fadeOut();
                }, function(err){ $.alert(err); $('#loader').fadeOut(); });
            }
        });
    });
}