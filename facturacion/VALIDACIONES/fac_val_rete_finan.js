/* 
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */
var retencion, Tic_Cod, Tic_Cod_Sus, index=0;

$(function(){
    Pec_Cod=$('#Pec_Cod').val();;
    Tic_Cod=$('#Tic_Cod').val();
    Tic_Cod_Sus=$('#Tic_Cod_Sus').val();
    $('#divDocumento').css('visibility','').hide();
    retencion=$('#retencion');
    if(retencion.length>0){
        retencion=$('#retencion').createGrid({
            caption:'Retencion', height: 270, datatype: "local", footerrow:true, totalCols:['Total'], totalDefault:{Ren_Por:'TOTAL:'},
            colModel: [  
                { label: 'Index', name: 'index', width: 30 ,align:"center", key:true, hidden:true },  
                { label: $.createIcon('check'), name:'select', width:15, align:'center',viewable: false, formatter:'gridButton', formatoptions:{ action:'openItemSelector', icon:'check', title:'Seleccionar Item', data:'index' }, resizable: false },            
                { label: 'Cód.Int.', name: 'Ren_Cod', width: 15, align:"center" },                    
                { label: 'Ret.', name: 'Ren_Ret', width: 15, align: 'center', formatter:'estado', formatoptions:{full:true, types:{R:'RENTA',I:'IVA'} } },
                { label: 'Código ', name: 'Ren_Sri', width: 15, align: 'center' },
                { label: 'Descripción ', name: 'Ren_Con', width: 50 },
                { label: 'Deposito', name: 'Ret_Dep', width: 30, align: 'right', formatter:'textboxExa', formatoptions:{dataInit:'initNumberTxt', dataEvents:{ keypress:'return validar_decimal(event);' }, name:true }, summaryType: "sum"},
                { label: 'Importe', name: 'Ret_Bas', width: 30, align: 'right', formatter:'textboxExa', formatoptions:{dataInit:'initNumberTxt', dataEvents:{ keypress:'return validar_decimal(event);', keyup:'updateFila($(this).data("rowId"));'}, name:true }, summaryType: "sum"},
                { label: 'Porc.(%)', name: 'Ren_Por', width: 20, align: 'right' },
                { label: 'Retención', name: 'Total', width: 30, align: 'right', formatter:'currency', formatoptions:{}, summaryType: "sum", classes:'columnHighlight1'},
                { label: $.createIcon('remove'), name:'delete', width:30, align:'center',viewable: false, formatter:'gridButton', formatoptions:{ action:'deleteItem', icon:'remove', title:'Eliminar Item', type:'danger', data:'index', attr:{'tabindex':'-1'}/*, conditional:function(o){ return !(!$.varValid(o['Pro_Cod'])||o['Pro_Cod']===''); }*/ }, resizable: false }
            ]
        },true,'#retencionPager',{view: false}).gridButtonsAdd([
            {caption:'Agregar Codigo',buttonicon:'glyphicon glyphicon-plus', onClickButton: function(){ index=0; $('#codiDialog').dialog('open'); } },
            {caption:'Remover Todos',buttonicon:'glyphicon glyphicon-remove', onClickButton: function(){ retencion.clearGrid(); } }
        ]).clearFootRow(['Total']);
    }
    if($('#provDialog').length>0)
    $('#provDialog').createSearchDialog({colModel:[
            { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 15,align:"center",hidden:true },                                
            { label: 'Cédula/RUC', name: 'Prs_Ced', width: 50 },                      
            { label: 'Proveedor', name: 'Proveedor', width: 100},
            { label: 'Cont.', name: 'Prv_Con', width: 20,align:"center", labelLong:'Obligado a Llevar Contabilidad', formatter:'truefalse', formatoptions:{msg:false}  }, 
            { label: 'Espe.', name: 'Prv_Esp', width: 20,align:"center", labelLong:'Contribuyente Especial', formatter:'truefalse', formatoptions:{msg:false} }, 
            { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:'selectProvee'} }
        ]},{ title:'Proveedor' }); 
    $('.datepickers').createDatePickers({checkAvailability:true,hideMsg:false,clean:true});
    if($('#codiDialog').length>0)
    $('#codiDialog').createSearchDialog({colModel:[
            { label: 'Cód.Int.', name: 'Ren_Cod', key: true, width: 25,align:"center" },                                
            { label: 'Código', name: 'Ren_Sri', width: 25, align:"center" },                      
            { label: 'Descripción', name: 'Ren_Con', width: 100 },
            { label: 'Porc.(%)', name: 'Ren_Por', width: 25,align:"center" },
            { label: 'Adq.', name: 'Ren_Tipo', width: 30,align:"center" },
            { label: 'Tipo', name: 'Ren_Rete', width: 30,align:"center"}, 
            { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:'selectItem'/*, conditional:function(o){ return !(Cof_Con==='S'&&(!$.varValid(o['Pld_Cod'])||o['Pld_Cod']==='')); }, caseFalse:function(){ return '<i class="glyphicon glyphicon-lock orange" title="No se ha Parametrizado una Cuenta Contable!"></i>'; }*/ } }
        ]},{ title:'Codigos', options:[{label:'&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;',value:'d'},{label:'&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;',value:'c'}] });
    checkImportacion();
    $('#Pec_Cod').on('change',function(){
        var year=$(this).find('option:selected').data('Year');
        if($.vv(year)){ $('#Ret_Fec').val('').dateLimits(year+'-01-01',year+'-12-31'); }
    }).trigger('change');
    $('#Ret_Fec').on('change',function(){
        if(this.value.length>8){
            var d=$(this).datepicker('getDate');  
            d.setDate(d.getDate() - 5);
            $('#Cop_Fec').dateLimits(d.toISOString().substring(0, 10),this.value);
        }
    });
});
function initNumberTxt(e,obj,opt){ e.style.textAlign='right';  e.placeholder='0.00'; }
function updateFila(index){ 
    var item=retencion.getRowData(index);
    var dec=String(item['Ret_Bas']).split("."); 
    if(typeof dec[1]!=='undefined'&&dec[1].length>2){ 
        item['Ret_Bas']=$.toFixed(item['Ret_Bas']);        
        retencion.find('tr#'+index+' input[data-name=Ret_Bas]').val(item['Ret_Bas']).focus();        
    }
    retencion.setCell(index, 'Total', item['Ren_Por']*1===0?0:$.round((item['Ret_Bas']*1)*(item['Ren_Por']*1/100)));
    updateDocument();
}
function editRetencion(Ret_Cod){
    clear();
    $.getDataJson('',{editRetencion:true, Ret_Cod:Ret_Cod}, function(r){
        r.autoriz['Ret_Num']=r.retencion['Ret_Num'];
        selectProvee(r.proveedor);
        $('#copForm').setData(r.compra,'name');
        $('#retForm').setData(r.retencion);
        $('#Pec_Cod').val($('#Pec_Cod').find('option[data--year='+r.retencion['Ret_Fec'].substring(0,4)+']').val());
        setAutoriza(r.autoriz);
        retencion.setRowsByIndex(r.detalle);
        updateDocument();
        $('#divSearch').moveComp('#divDocumento').updateGridsSizes();
    });
}
function validaDocument(){
    var data=$('#formDocumento').getData('saveDocument');
    data['detalle']=retencion.getGridBatch();
    if($.isEmpty(data.Prv_Cod)) return $.alert("Debe escoger un <b class='green'>PROVEEDOR</b>!",null,'alert');
    if($.isEmpty(data['detalle'])) return $.alert("Debe escoger al menos un <b class='green'>CODIGO DE RETENCION</b>!",null,'alert');
    if(retencion.jqGrid('getCol','Total',false,'sum')===0) return $.alert("El Total de la Retencion no puede ser <b class='red'>cero</b>!",null,'alert');
    $.arraySpliceFields(data['detalle'],['index','delete','select']);
    data['autoriz']=$('#Aut_Cod').data();
    data['autoriz_old']=$('#Aut_Cod_Old').data();
    $.createDialogConfirm('¿Esta seguro de guardar la <b class="green">Retención Financiera</b>?', data, saveDocument);
}
function saveDocument(data){
    $.saveDataJson('',data,
        function (resp){ 
            var msg="<h3>La <b class='green'>Retencion</b> se ha Guardado con Exito!</h3>";
            clear();
            validaRetNum();  
            var divSearch=$('#divSearch');
            if(divSearch.length===1){
                searchGrid.trigger("reloadGrid");
                $('#divDocumento').moveComp('#divSearch').updateGridsSizes();
            }
            if($.varValid(resp['mail'])){
                msg+="<div class='center'><button class='btn btn-xs btn-success' type='button' onclick='viewPdf("+resp['Ret_Cod']+")'><i class='fa fa-file-pdf-o'></i> Ver PDF</button></div><br/>";
                msg+=(resp['mail']===true?"El Mail se envio Correctamente.":"<u class='red'>NOTA:</u> Surgio un problema al enviar el mail.");                
            }else{
                msg+="<div class='center'><button class='btn btn-xs btn-success' type='button' onclick='$.imprimirUrl(\""+resp['Ret_Link']+"\")'><i class='glyphicon glyphicon-print'></i> Imprimir</button></div><br/>";
            }            
            $.alert(msg,null,'ok');
            return false;
        }
    ); 
}
function clear(){
    retencion.clearGrid();
    $('#formDocumento').setData({Ret_Fec:hoy, Tic_Cod:Tic_Cod, Tic_Cod_Sus:Tic_Cod_Sus, Pec_Cod:Pec_Cod});
    $('#formDocumento').setData({},'name');
    changeAutoriza($('#Aut_Cod').data());
}
function checkImportacion(){
    var Importa=$('#Tic_Cod_Sus option:selected').data('Tic_Sri')*1===17;
    $('#Cop_Num').unmask().mask(Importa?"999-9999-99-99999999":"999-999-999999999",{placeholder:"_"});    
}
function updateDocument(){
    retencion.setGridSummary(['Total']);
}
function selectItem(item){    
    if(retencion.existsValByCol('Ren_Cod',item['Ren_Cod'])) return $.alert(`El Codigo de Retencion <br><b class="green"><u>${item['Ren_Cod']}</u>: ${item['Ren_Con']} ya se encuentra en el listado.</b>`);
    if(index===0){ 
        addItem(item);              
    }else{
        $('#codiDialog').dialog('close');
        retencion.changeRow(index,item);
        retencion.highlightRow(index);
        index=0;
        setTimeout(function (){ $('#'+(index)+'_Ret_Bas').focus(); },0);
    }
    updateDocument();
}
// Añade un item al documento
function addItem(item){
    item['index']=retencion.nextIndex();
    retencion.jqGrid('addRowData',item['index'],$.extend(item,{Ret_Bas:0.00, Total:0.00}),'last');
    retencion.highlightRow(item['index']);    
    resize();
}
// Abre dialogo producto para cambiar item
function openItemSelector(id){ index=id; $('#codiDialog').dialog('open'); }
// Elimina item
function deleteItem(index){ var data=retencion.jqGrid('getRowData',index); if(data['Ren_Cod']!==''){ retencion.jqGrid('delRowData',index); updateDocument(); resize(); } }
function resize(){ if(retencion.width()!==$('#retencionGridParent').width()) retencion.jqGrid("resizeGrid");  }

// Valida q no existe la retencion
function validaRetNum(){ 
    var rnum=$('#Ret_Num'), aut_data=$('#Aut_Cod').data(), old_data=$('#Aut_Cod_Old').data(), 
        data={Ret_Num:rnum.val(),Ret_Cod:$('#Ret_Cod').val(),Aut_Data:aut_data,Aut_Data_Old:old_data,validaRetNum:true};  
    if( aut_data['Aut_Cod']*1===old_data['Aut_Cod']*1 && data['Ret_Num']*1===old_data['Ret_Num']*1){       
        rnum.fieldValid();
        return;
    }
    $('#Ret_Num').getValidationJson('',data,function(r){ 
        if(r['Ret_Num']*1>r['Aut_Fin']){
            rnum.fieldValid(false,'Ya no quedan números disponibles en el rango ('+r['Aut_Ini']+' - '+r['Aut_Fin']+')!');
            r['Ret_Num']='';
        }else{ 
            if(r['success']===true){
                if(r['Ret_Num_Old']!==''){
                    if(r['Ret_Num_Old']*1>=r['Aut_Ini']&&r['Ret_Num_Old']*1<=r['Aut_Fin'])
                        r['Ret_Num']=r['Ret_Num_Old'];
                    else{
                        rnum.fieldValid(false,'El número '+r['Ret_Num_Old']+' no está en el rango ('+r['Aut_Ini']+' - '+r['Aut_Fin']+')!');
                        if(aut_data['Aut_Cod']*1===old_data['Aut_Cod']*1&& $.vv(old_data['Ret_Num'])) r['Ret_Num']=old_data['Ret_Num'];                    
                    }
                }
            }
        }
        rnum.val(r.Ret_Num);
    });
}
function setAutoriza(data){     
    $('#Aut_Cod_Old').val(data['Aut_Cod']).data(data);
    changeAutoriza(data);
}
function changeAutoriza(data){
    $('#Aut_Cod').val(data['Aut_Cod']).data(data);
    $('#Aut_Sri').html(data['Aut_Tem']==='E'?'Electronica':data['Aut_Sri']);
    $('#Aut_Tem').val(data['Aut_Tem']);
}
function selectProvee(provee){            
    $('#provFormTemp').setData($.extend(provee,{op_opciones:'c'}),'name').find('.dialogSearch').addClass('x');
    $('#Prv_Con').removeAttr('class').addClass('glyphicon glyphicon-'+(provee['Prv_Con']==='S'?'ok green':'remove blue'));
    $('#Prv_Esp').removeAttr('class').addClass('glyphicon glyphicon-'+(provee['Prv_Esp']==='S'?'ok green':'remove blue'));            
    $('#provDialog').dialog('close');
} 
function viewPdf(Ret_Cod){ window.open('../COMPONENTES/tesPdfElectronicos.php?type=RETENC'+'&Doc_Cod='+Ret_Cod); }