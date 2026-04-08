/* 
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */
var searchGrid;
$(function(){
    searchGrid=$('#searchGridReten');
    if(searchGrid.length>0)
    searchGrid.createGrid({
        caption:'Resultado de la Búsqueda', height: 270, datatype: "local",
        stateCol:'Ret_Est', stateConfig:{I:'cellRed2',Autorizado:'cellGreen2'}, stateCondition:function(o){ return o.Ret_Aut==='S'?'Autorizado':undefined;  },
        leyenda:[{icon:'remove red',label:'Anulados/Inactivos'},{icon:'remove orange',label:'Retención Electronica No Autorizada'},{icon:'ok blue',label:'Retención Asumida'},{icon:'fa-globe green',label:'Retención Electronica Validada'}],
        colModel: [  
            { label: 'Cód. Int.', name: 'Ret_Cod', width: 15 ,align:"center", key:true, hidden:true }, 
            { label: 'Cód. Int.', name: 'Cop_Cod', width: 15 ,align:"center", hidden:true }, 
            { label: 'No. Retención', name: 'Secuencia', width: 40 ,align:"center"},
            { label: 'Aut.', name: 'Autorizacion', width: 10, align: "center", formatter:'truefalse', formatoptions:{ yesValue:function(cv){ return cv!=='PENDIENTE'&&cv.trim()!==''; }, yesMsg:function(o){ return o.Autorizacion; }, yesIcon:'info-sign', yesColor:'blue', noColor:'orange', noMsg:'No se Encuentra Autorizado!', noText:true }, title:false },
            { label: 'Fecha', name: 'Ret_Fec', width: 30, align:"center" },
            { label: 'Documento', name: 'Tic_Des', width: 25 },
            { label: 'No. Documento', name: 'Cop_Num', width: 40, align:"center" },        
            { label: 'Proveedor', name: 'Proveedor', width: 60 },      
            { label: 'DEPOS.', name: 'Tot_Depo', width: 20,align:"right", formatter:'currency' },
            { label: 'RENTA', name: 'Tot_Renta', width: 20,align:"right", formatter:'currency' },                
            { label: 'IVA', name: 'Tot_Iva', width: 20,align:"right", formatter:'currency' },
            { label: 'TOTAL', name: 'Total', width: 20,align:"right", formatter:'currency' },
            { label: 'Asum.', name: 'Ret_Asu', width: 10, align: "center", formatter:'truefalse', formatoptions:{ yesMsg:'Valor Retención Asumido', yesColor:'blue', noMsg:' ', noText:true }, title:false },
            { label: 'PDF', name: 'act03', width: 15, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{ action:'viewPdf', data:'Ret_Cod', title:'Ver PDF', icon:'file', type:'info', conditional:function(o){ return o.Ret_Est!=='I'&&!$.isEmpty(o.Ret_Cod)&&!$.isEmpty(o.Ret_Xml); } }, title:false },
            { label: 'Estado', name: 'Ret_Est', width: 20,align:"center", formatter:'estado', title:false, hidden:true },
            { label: $.createIcon('eye-open'), name: 'act01', width: 15, viewable: false, formatter: 'gridButton', formatoptions:{action:'viewRetencion', icon:'info-sign', type:'info', title:'Ver Informacion', conditional:function(o){ return o.Ret_Est==='A'; } } }            
        ].concat($.isset('extraSearch')&&$.isArray(extraSearch)?extraSearch:[])
    },false,'#searchGridRetenPager',{refresh: true});
    if($('#detaRete').length>0)
        $('#detaRete').createGrid({
            height:'auto', width:550, caption:null, rownumbers:false, sortable:true, sortname: 'Ren_Rete', sortorder: "desc", footerrow:true, responsive:false,
            totalCols:['Total'],totalDefault:{Ren_Por:"<div style='text-align:right;'>TOTAL:</div>"},
            colModel: [
                { label: 'Cód.Int.', name: 'Ren_Cod', key: true, width: 15, align:"center", hidden:true },                    
                { label: 'Ret.', name: 'Ren_Ret', width: 15, align: 'center', formatter:'estado', formatoptions:{full:true, types:{R:'RENTA',I:'IVA'} } },
                { label: 'Código ', name: 'Ren_Sri', width: 15, align: 'center' },
                { label: 'Descripción ', name: 'Ren_Con', width: 50 },
                { label: 'Importe', name: 'Tot_Base', width: 30, align: 'right', formatter:'currency', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryType: "sum"},
                { label: 'Porc.(%)', name: 'Ren_Por', width: 20, align: 'right' },
                { label: 'Retención.', name: 'Total', width: 30, align: 'right', formatter:'currency', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:'0.00'},summaryType: "sum"}
            ]
        },true).getFootRow(true); 
    if($('#detaDocu').length>0)
        $('#detaDocu').createGrid({                                                        
            height:'auto',width:550, responsive:false, postData: {CheListAjax:true},caption:null, rownumbers:false,           
            colModel: [
                { label: 'Cód.Int.', name: 'Cop_Int', key: true, width: 15,align:"center", hidden:true },                                     
                { label: 'Cantidad ', name: 'Cop_Can', width: 45, align: 'right' },                      
                { label: 'Item', name: 'Cop_Pro', width: 130  },
                { label: 'P. Unit.', name: 'Cop_Pru', width: 65, align: 'right'},
                { label: 'Importe', name: 'Cop_Imp', width: 65, align: 'right', formatter:'currency', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryType: "sum"}
            ]       
        },true);     
    if($('#docDetaDialog').length>0)
        $('#docDetaDialog').createDialog({height:400,width:600,noTitleStuff:false,noBorder:true});
});
function setOpt(val){ if(val==='d'||val==='b') $('.search_pec').attr('disabled','disabled'); else $('.search_pec').removeAttr('disabled'); }
function viewPdf(Ret_Cod){ window.open('../COMPONENTES/tesPdfElectronicos.php?type=RETENC'+'&Doc_Cod='+Ret_Cod); }
function viewRetencion(data){
    $('#docDetaDialog').setData(data); 
    $.getDataJson('',{docDetalle:true, Ret_Cod:data.Ret_Cod},function(resp){
        $('#detaDocu').setRows(resp['items']);
        $('#detaRete').setRows(resp['detalle']);
        $('#docDetaDialog').dialog('open').updateGridsSizes();
    });
}