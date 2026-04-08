/* OBJETOS JQUERY */
$(function() { 
    gridFact=$("#documento");
    gridFact.createGrid({
        data:[], caption: "Detalle Documento", rowNum: 10000000, height: 'auto', footerrow:true, headertitles:true, selectGridRows:false,
        colModel:[
            {name:'select',label:'<i class="glyphicon glyphicon-check"></i>', width:30, align:'center',viewable: false, formatter:'gridButton', formatoptions:{ action:openItemSelector, icon:'check', title:'Seleccionar Item', data:function(o){ return o.index; } }, resizable: false },
            {name:'index',label:'Index', width:20, sorttype:"int",align:'center',key:true,hidden:true},
            {name:'Pro_Cod',label:'Cód.Int.', width:20, sorttype:"int",align:'center',hidden:true},                    
            {name:'Cop_Can',label:'Cant.', labelLong:'Cantidad', width:40, align:"right", title:false, editable:true, editoptions:{dataInit:styleCant}},
            {name:'Uni_Des',label:'Uni.', labelLong:'Unidad', width:25, resizable: false },
            {name:'Ite_Lar',label:'Descripción', width:150},
            {name:'Pld_Cod',label:'Pld_Cod', width:20,hidden:true},
            {name:'Pld_Cdc',label:'Cuenta', width:50, align:"center", formatter:'title', formatoptions:{title:function(o){return o['Pld_Cdc']+' - '+o['Pld_Des'];}}, title:false },
            {name:'Pld_Des',label:'Pld_Des', width:20,hidden:true},
            {name:'Cop_Dec',label:'Descuen.', labelLong:'Descuento', align:"right", width:20},
            {name:'Cop_Pru',label:'P. Unitario', labelLong:'Precio Unitario', width:60, align:"right", title:false/*, summaryRound: 8,formatter:"currency",formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.',decimalPlaces: 8, defaultValue: ''}*/, editable:true, editoptions:{dataInit:stylePru}},                    
            {name:'Cop_Imp',label:'Importe', width:70, align:"right", summaryRound: 2,formatter:"currency",formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.', defaultValue: '0.00'},classes:'columnHighlight1'},
            {name:'Iva_Cod',label:'CodIva', width:20,hidden:true},  
            {name:'Iva_Por',label:'IVA', width:15,align:"center", formatter:'truefalse', formatoptions:{yesMsg:'Graba IVA',noMsg:'No Graba IVA'}, title:false, resizable: false },             
            //{name:'Ice_Int',label:'Ice_Int', width:20,hidden:true}, 
            {name:'Cop_Ice',label:'ICE', width:20, align:"right", title:true, resizable: false, formatter:'ice' },
            {name:'Iva_Cos',label:'Cos.', labelLong:'IVA al Costo', width:20,align:"center",formatter:'checkboxExa', formatoptions:{yes:'S',no:'N',nullifField:'Adq_Cor',nullifValue:'A'}, resizable: false },
            {name:'Adq_Cod',label:'CodAdq', width:20,hidden:true},                    
            {name:'Ret_Ren_Sri',label:'I. Renta',labelLong:'Impuesto a la Renta', width:35,align:"center", title:false, formatter:'impRenta', resizable: false  },
            {name:'Ret_Ren_Cod',label:'Ret Ren_Cod', width:20,hidden:true}, 
            {name:'Ret_Ren_Por',label:'Ret Ren_Por', width:20,hidden:true}, 
            {name:'Ret_Ren_Con',label:'Ret Ren_Con', width:20,hidden:true}, 
            {name:'Iva_Ren_Sri',label:'Ret. IVA',labelLong:'Retención del IVA', width:35,align:"center", title:false, formatter:'retIva', resizable: false  },
            {name:'Iva_Ren_Cod',label:'Iva Ren_Cod', width:20,hidden:true}, 
            {name:'Iva_Ren_Por',label:'Iva Ren_Por', width:20,hidden:true}, 
            {name:'Iva_Ren_Con',label:'Iva Ren_Con', width:20,hidden:true}, 
            {name:'Adq_Cor',label:'Adq.',labelLong:'Adquisiciones', width:20,align:"center", title:false, formatter:'title', formatoptions:{title:function(o){return o['Adq_Des'];}}, resizable: false  },
            {name:'delete',label:'<i class="glyphicon glyphicon-remove"></i>', width:30, align:'center',viewable: false, formatter:'gridButton', formatoptions:{ action:deleteItem, icon:'remove', title:'Eliminar Item', type:'danger', data:function(o){ return o.index; }, attr:{'tabindex':'-1'}, conditional:function(o){ return !(!$.varValid(o['Pro_Cod'])||o['Pro_Cod']===''); } }, resizable: false }
        ]
    },true,'documentoPager',{view:false}).gridButtonsAdd([
        {caption:'Agregar Productos',buttonicon:'glyphicon glyphicon-plus', onClickButton: function(){ index=0;$('#proDialog').dialog('open'); } },
        {caption:'Remover Todos',buttonicon:'glyphicon glyphicon-remove', onClickButton: function(){ gridFact.clearGrid(); addItem({}); } }
    ]);            
    gridFact.getFootRow(true);          
    gridFact.jqGrid('footerData', 'set',{
        Cop_Can:'<div class="footerFact"><label>Observación:</label></div>',
        Ite_Lar:'<div class="footerFact formDatos" class="formDatos"><textarea name="Cop_Obs" tabindex="12" class="text"></textarea></div>',
        Cop_Pru:'<div class="footerFact"><label>SUBTOTAL:</label><label>TARIFA 0%:</label><label>TARIFA <span class="iva_por"></span>%:</label><label><span class="iva_por"></span>% IVA:</label><label>ICE:</label><label>DESCUENTO:</label><label class="total">TOTAL:</label></div>',
        Cop_Imp:'<div class="footerFact formDatos" id="formTotales"><input id="t_subtotal" name="t_subtotal" type="text" readonly/><input name="t_iva0" type="text" readonly/><input name="t_iva12" type="text" readonly/><input name="t_iva" type="text" readonly/><input name="t_ice" type="text" readonly/><input id="t_descuento" name="t_descuento" type="text" onchange="if(!isNaN(this.value)&&this.value*1===0)$(\'#Cop_Des\').val(\'0\'); updateDocument();" class="text" /><input id="t_rubros" name="t_rubros" type="text"  class="total" readonly/></div>',
        Iva_Por:'<div class="footerFact formDatos"><div style="height:56px;"></div><div style="position:absolute;text-align: left;"><select id="Iva_Cod" name="Iva_Cod" style="max-width:'+(Cof_Con==='S'?'25':'100')+'%;" onchange="changeIvas()" class="text"></select>'+(Cof_Con==='S'?'<select id="Iva_Pag" name="Iva_Pag" style="max-width:70%" class="text"></select>':'')+'</div><div style="height:75px;padding-top:38px;text-align: left;"><input id="Cop_Des" name="Cop_Des" style="height:19px;position:absolute;display:none;" /></div>'
    },false); 
    $('#formTotales,.reteTot:not(.cod_banano)').find('input:not(#t_descuento)').attr('tabindex','-1');
    addItem({});
    $(".secuencia").mask("999-999-999999999",{placeholder:"_"});
    $('#Ciu_Cod').createChosen('input-xs',{tabIndex:6, width:'100%',template:function (t,d){ return '<div class="over"><b>'+t+'</b></div><div class="over desc">'+d['prov']+'</div>';}});
    $('.datepickers').createDatePickers({checkAvailability:true,hideMsg:false,clean:true});
    if(Cof_Con==='S'){ 
        $('#Cop_Fec').on('change',function (){ $('.Cop_Fec').val(this.value); $('#Com_Fec').val(this.value).datepicker("option","minDate",this.value); });
        $('#Com_Fec').createFlyout('El comprobante y el documento no estan en el mismo Periodo!',{icon:'exclamation',placement:'left_top'});
        $('#Ret_Asu').on('click',function(){
            if($(this).prop('checked')){ $.createDialogConfirm('¿Está seguro que desea <u><b>asumir</b> el valor</u> de la <b>Retención</b>?',null,function(){},function(){ $('#Ret_Asu').prop('checked',false).trigger('change'); }); }
        });
    }
    $('#Cop_Fec').on('change',function (){ 
        $('#Cop_Imf').datepicker("option","maxDate",this.value); $('#Cop_Cad').datepicker("option","minDate",this.value); $('#Ret_Fec').val(this.value).datepicker("option","minDate",this.value); var d=new Date(this.value); d.setDate(d.getDate()+15); $('#Cpp_Ven').datepicker("setDate",d).datepicker("option","minDate",this.value);
        if(this.value.length>0){
            var anio=this.value.substring(0, 4);
            if($(this).data('anio')!==anio){
                checkFechaIva(this.value);
                if(Cof_Con==='S'){ checkCuentaPago(); $.Search('pro'); }
            } $(this).data('anio',anio);
        }
    }); $('#Cop_Fec').data('anio',$('#Cop_Fec').val().substring(0, 4));
    $('#For_Cod').on('change',function (){ $('.pagoCredito')[this.value*1===2?'show':'hide']();  (('0'+this.value)*1===2?$('#Cpp_Ven').attr('required','required'):$('#Cpp_Ven').removeAttr('required')); });
    $('#Ret_Asu').on('change',function (){ calculaRetencion(); });
    $('#Cop_Aut').createFlyout('El campo debe tener 10, 37 o 49 digitos!',{icon:'exclamation',placement:'right'});
    $('#Prv_Btn').createFlyout('Debe Seleccionar el Proveedor!',{icon:'exclamation',placement:'right'});
    $('#Ciu_Cod_chosen').createFlyout('Escoje una Ciudad!',{icon:'exclamation',placement:'right'});
    $('#Cop_Num').createFlyout('El Documento ya Existe!',{icon:'exclamation',placement:'right'});
    $('#Prs_Ced').createFlyout('La Cedula es Incorrecta!',{icon:'exclamation',placement:'bottom'});            
    $('#provCreateDialog').createDialog({icon:'plus', width:500, height:430});
    $('#Tic_Cod').on('change',function (){
        var val=this.value!==''?this.value*1:'', sel=$(this).find('option:selected'), sri=sel.data('ticsri'), des=this.value!==''?sel.text():'';
        $('#asumirRet')[(sri===1||sri===3)&&Cof_Con==='S'?'show':'hide']();
        $('#Ret_Asu').prop('checked',false);
        checkImportacion(sri);
        if(sri===3) checkLiquidacion();
        updateDocument();
    });
    $('#Cop_Aut').on('change',function (){ var val=$(this).val(),aut=val.length; $(this).attr('title',val); if(aut!==0&&aut!==10&&aut!==37&&aut!==49){ $(this).fieldValid(false,'El campo debe tener 10, 37 o 49 digitos!'); }else{ $(this).fieldValid(aut===0?'':true); } }); 
    //validaRetNum(); 
}); 

/* BUSQUEDAS */
// Selecciona Producto
function selectItem(item){
    var lastId=gridFact.jqGrid('getCol','index',false,'max'), close=true;
    if(index===0){ index=lastId; close=false; }    
    gridFact.changeRow(index,$.extend(item,item['Iva_Por']*1>0?{Iva_Cod:$('#Iva_Cod').val(), Iva_Por:$('#Iva_Cod option:selected').data('ivapor'),Cop_Ice:null}:{Iva_Ren_Cod:'',Iva_Ren_Con:'',Iva_Ren_Por:'',Iva_Ren_Sri:'',Cop_Ice:null}));
    var last=gridFact.jqGrid('getRowData',lastId);
    if(last['Pro_Cod']!=='') addItem({});
    if(close){ $('#proDialog').dialog('close'); setTimeout(function (){ $('#'+(index)+'_Cop_Can').focus(); },0); }else index=0;
    updateDocument();
}

/* DATOS GENERALES */
// valida cedula
function validaNoIdentif(number){
    var digitos = number.split(""), dto=digitos.length, acu=0, resp={success:false,message:''}, 
    coef={'NA':[2,1,2,1,2,1,2,1,2],'PU':[3,2,7,6,5,4,3,2,0],'PR':[4,3,2,7,6,5,4,3,2]}, modulo, acum=0;
    if(dto===0) resp['message']='No has ingresado ning\u00fan dato!'; 
    else{
     for(var i=0; i<dto; i++) if(!isNaN(digitos[i])){ digitos[i]=digitos[i]*1; acu = acu+1; }
     if(acu===dto){
      var tipo = digitos[2];
      if (tipo===7||tipo===8) resp['message']='"El tercer d\u00edgito ingresado es inv\u00e1lido"'; else{ tipo=(tipo<6?'NA':(tipo===6?'PU':(tipo===9?'PR':''))); modulo=(tipo==='NA'?10:11); resp['tipo_abrev']=tipo; resp['tipo']=(tipo==='NA'?'Natural':(tipo==='PR'?'Privada':(tipo==='PU'?'P\u00fablica':''))); }
          if(dto!==10&&dto!==13){ resp['message']='La cantidad de d\u00EDgitos deben ser 10 o 13'; return resp; }else{ resp['doc_abr']=(dto===10?'C':(dto===13?'R':'')); resp['doc']=(dto===10?'C\u00E9dula':(dto===13?'R.U.C.':'')); }   
          if(number.substring(0,2)*1>24) resp['message']='Los dos primeros d\u00EDgitos no pueden ser mayores a 24.';	
          if(dto===13){
                  if(number.substring(10,13)!=='001') resp['message']='Los tres \u00faltimos d\u00EDgitos no tienen el c\u00F3digo del RUC 001.'; 	
                  if(tipo==='PU'&&number.substring(9,13)!=='0001') resp['message']='El R.U.C. de la empresa del sector p\u00fablico debe terminar con 0001';
          }else if((tipo==='PU'||tipo==='PR')) resp['message']='El R.U.C. de las empresas '+resp['tipo']+'s deben tener 13 digitos!';
          if(resp['message'].length>0) return resp; 

          for(var a=0;a<9;a++){
                  var resul=digitos[a]*coef[tipo][a];
                  acum+=(resul-(tipo==='NA'&&resul>=10?9:0));
          }	
          var residuo=acum%modulo, digitoVerificador = residuo===0 ? 0: modulo - residuo;
          if(digitos[(tipo==='PU'?8:9)]!==digitoVerificador) resp['message'] = 'El n\u00famero de '+resp['doc']+' de la '+(tipo==='NA'?'Persona Natural':'Empresa '+resp['tipo'])+' ingresado es inv\u00E1lido!';

          if(resp['message'].length===0) resp['success']=true;
     }else resp['message']="ERROR: Solo debe contener d\u00EDgitos!";
    }
    return resp;
}
// Valida q no existe el documento
function validaCopNum(){ var data=$.extend($('#docuFormTemp').getData('ajaxCopNum'),$('#provFormTemp').getData()); /*if($('#Cop_Num').data('old_num')!==data['Cop_Num'])*/ $('#Cop_Num').getValidationJson('',data); /*else $('#Cop_Num').fieldValid(true); */  }
// Valida q no existe la retencion
function validaRetNum(){ 
    var rnum=$('#Ret_Num'), data={Ret_Num:rnum.val(),Ret_Cod:$('#Ret_Cod').val(),validaRetNum:true};     
    if($.varValid(rnum.data('Ret_Num'))&&data['Ret_Num']*1===rnum.data('Ret_Num')*1){
        var old_data=rnum.data();        
        $('#reteFormTemp').setData(old_data,false);
        $('#Aut_Cod').html(old_data['Aut_Cod']);
        rnum.fieldValid();
        return;
    }
    $('#Ret_Num').getValidationJson('',data,function(r){  
        var rnum=$('#Ret_Num');
        if(r['success']===false){
            if(r['Ret_Num_Old']==='') rnum.fieldValid(true);                    
        }else{
            if(r['Ret_Num']*1>r['Aut_Fin']){
                rnum.fieldValid(false,'Ya no quedan números disponibles en el rango ('+r['Aut_Ini']+' - '+r['Aut_Fin']+')!');
                r['Ret_Num']='';
            }else{
                if(r['Ret_Num_Old']*1>=r['Aut_Ini']&&r['Ret_Num_Old']*1<=r['Aut_Fin'])
                    delete r['Ret_Num'];
                else
                    rnum.fieldValid(false,'El número '+r['Ret_Num_Old']+' no está en el rango ('+r['Aut_Ini']+' - '+r['Aut_Fin']+')!');
            }
        }
        if($.varValid(r['Ret_Num'])&&$.varValid(rnum.data('Ret_Num'))&&rnum.data('Ret_Num')!=='')
            r=$.extend(r,rnum.data());
        $('#reteFormTemp').setData(r,false);
        $('#Aut_Cod').html(r['Aut_Cod']);
    }); 
}
// carga las cuentas pago
function checkCuentaPago(Pld_Cod){
    if($('#Cop_Fec').val()===''||$('#For_Cod').val()===''||Cof_Con==='N') return;
    $('#Pag_Pld').attr('disabled','disabled');
    $.post( "",{cuentasPago:true,For_Cod:$('#For_Cod').val(),Cop_Fec:$('#Cop_Fec').val(),Pld_Cod:Pld_Cod}, function( response ) {
        if(response['success']===true){                                 
            if(response['total']>0){
                $('#Pag_Pld').html(response['cuentas']);                         
            }else{ $('#Pag_Pld').val('').html(''); $.alert('Error al buscar la cuenta pago para la fecha indicada');}
        }                                   
    },'json').fail(function (){ $.alert('Error al buscar el IVA para la fecha indicada'); })
            .always(function (){ if(!$.varValid($('#Pag_Pld').data('disabled'))||$('#Pag_Pld').data('disabled')===false) $('#Pag_Pld').removeAttr('disabled'); });
 }
// carga los ivas pala fecha escogida
function checkFechaIva(fecha,Iva_Cod,Pld_Cod){
    $.post( "",{Check_Iva:true,Cop_Fec:fecha,Tic_Cod:$('#Tic_Cod').val(),Tic_Sri:$('#Tic_Cod option:selected').data('ticsri'),Iva_Cod:Iva_Cod,Pld_Cod:Pld_Cod}, function( response ) {
        if(response['success']===true){                                 
            if(response['total']>0){
                $('#Iva_Cod').html(response['options'])[(response['varIvas']?'show':'hide')](); 
                if(Cof_Con==='S') $('#Iva_Pag').css('max-width',response['varIvas']?'70%':'100%').html(response['cuentas']);
                changeIvas();
            }else{ $.alert('Error al buscar el IVA para la fecha indicada');}
        }                                   
    },'json').fail(function (){ $.alert('Error al buscar el IVA para la fecha indicada'); });
 }
 // cambia los ivas de los items
 function changeIvas(){
    var ids = gridFact.jqGrid('getDataIDs'), iva={Iva_Cod:$('#Iva_Cod').val(), Iva_Por:$('#Iva_Cod option:selected').data('ivapor')}; $('.iva_por').html(iva['Iva_Por']);
    for (var i = 0; i < ids.length; i++){ if('0'+gridFact.jqGrid('getCell',ids[i],'Iva_Por')*1>0) gridFact.changeRow(ids[i],iva); } updateDocument();
 }        
// estilo cantidad
function styleCant(e,obj,opt){            
    e.style.textAlign = 'right';  e.placeholder='0'; 
    $(e).on('keyup',function (){
       if(isNaN(this.value)/*||this.value===''||(!isNaN(this.value)&&this.value*1===0)*/){ $(this).val('1').focus();   }
       else if (this.value % 1 !== 0){ var dec=String(this.value).split("."); if(typeof dec[1]!=='undefined'&&dec[1].length>2) this.value=$.toFixed(this.value);  } 
       updateRowItem(obj);
    });
}
// estilo precio unitario
function stylePru(e,obj,opt){            
    e.style.textAlign = 'right'; e.placeholder='0.00';
    $(e).on('keyup',function (){
       if(isNaN(this.value)/*||this.value===''||(!isNaN(this.value)&&this.value*1===0)*/){ $(this).val('').focus();; }
       else if (this.value % 1 !== 0){ var dec=String(this.value).split("."); if(typeof dec[1]!=='undefined'&&dec[1].length>8) this.value=$.toFixed(this.value,8);  } 
       updateRowItem(obj);
    });            
}
// Actualiza los valores de la fila
function updateRowItem(obj){
    var row=$.extend({},gridFact.jqGrid('getRowData',obj['rowId']),gridFact.find('tr#'+obj['rowId']).getDataForced());
    row['Cop_Imp']=row['Cop_Can']*(0+row['Cop_Pru'])*1;
    row['Cop_Imp']=row['Cop_Imp']-(('0'+row['Cop_Dec'])*1>0?row['Cop_Imp']*row['Cop_Dec']/100:0);
    gridFact.changeRow(obj['rowId'],row);
    updateDocument();
}
// Añade un item al documento
function addItem(item){
    var next=gridFact.jqGrid('getCol','index',false,'max');
    next=(isNaN(next)?1:next+1);
    gridFact.jqGrid('addRowData',next,$.extend(item,{index:next,Cop_Can:1,Cop_Pru:''}),'last');
    gridFact.jqGrid('editRow',next);
    updateRowItem({rowId:next});            
    resize();
}
// Abre dialogo producto para cambiar item
function openItemSelector(id){ index=id; $('#proDialog').dialog('open'); }
// Elimina item
function deleteItem(index){ var data=gridFact.jqGrid('getRowData',index); if(data['Pro_Cod']!==''){ gridFact.jqGrid('delRowData',index); updateDocument(); resize(); } }
function resize(){ if(gridFact.width()!==$('#documentoMain').width()) gridFact.jqGrid("resizeGrid");  }
// Actualiza los valores totales
function updateDocument(){
    var rows = gridFact.jqGrid('getRowData'),des_val=$('#t_descuento').val(), des_por=$('#Cop_Des').val(), tot={t_subtotal:0,t_iva0:0,t_iva12:0,t_iva:0,t_ice:0,t_descuento:(!isNaN(des_val)?des_val*1:0),Cop_Des:(!isNaN(des_por)?des_por*1:0),t_rubros:0},                    
        Tic_Sri=$('#Tic_Cod').find('option:selected').data('ticsri')*1, rise=(Tic_Sri===2||Tic_Sri===9);
    for (var i=0, z=rows.length-1; i<z ; i++){
        var row=rows[i];
        row['Cop_Imp']=(row['Cop_Imp']*1);
        row['Iva_Por']=rise?0:('0'+row['Iva_Por'])*1;
        row['Ice_Por']=('0'+row['Cop_Ice'])*1;
        tot['t_subtotal']=tot['t_subtotal']+row['Cop_Imp'];
        if(row['Iva_Por']===0||rise) tot['t_iva0']=tot['t_iva0']+row['Cop_Imp']; 
        else tot['t_iva12']=tot['t_iva12']+row['Cop_Imp']; 
    }
    tot['Cop_Des']=(tot['t_descuento']>0?(tot['t_subtotal']>=tot['t_descuento']?tot['t_descuento']*100/tot['t_subtotal']:100):tot['Cop_Des']); 
    for (var i=0, z=rows.length-1; i<z ; i++){
        var row=rows[i], des_glob=(tot['Cop_Des']>0?row['Cop_Imp']*tot['Cop_Des']/100:0), ice=(row['Ice_Por']>0?(row['Cop_Imp']-des_glob)*row['Ice_Por']/100:0);
        if(row['Iva_Por']>0&&!rise){            
            tot['t_ice']=tot['t_ice']+ice;
            tot['t_iva']=tot['t_iva']+(row['Cop_Imp']+ice-des_glob)*row['Iva_Por']/100;
        }
    }
    tot['t_iva']=$.round(tot['t_iva']); tot['t_ice']=$.round(tot['t_ice']);    
    tot['t_rubros']=tot['t_subtotal']+tot['t_iva']+tot['t_ice']-tot['t_descuento'];
    $('.pagoSri')[(tot['t_rubros']>=1000?'show':'hide')]();
    (tot['t_rubros']>=1000?$('#Tpc_Cod').attr('required','required'):$('#Tpc_Cod').removeAttr('required'));   
    $.each(tot,function (k,v){ tot[k]=$.toFixed(v,k!=='Cop_Des'?2:10); });   
    $('#formTotales').setData(tot);
    $('#Cop_Des').val(tot['Cop_Des']); 
    calculaRetencion();            
}
// Valida Todo antes de guardar
function validaDocument(){  
   // console.log('ver');            
   if(($('#Val_Pcc').val())*1<0){ $.alert('El valor a pagar no puede ser negativo!<br/>Revise los datos.',null,'remove'); return; }
   var data=$('.formDatos').getData('saveDocument'), aut=data['Cop_Aut'].length;
   if(Cof_Con==='S'){ 
        if(data['Cop_Fec'].substring(0, 4)!==data['Com_Fec'].substring(0, 4)) { $('#Com_Fec').flyout('show').focus();  return; }
   } 
   if(!data['Prv_Cod'].length){ $('#Prv_Btn').focus(); $('#Prv_Btn').flyout('show'); return; }
   if(aut!==10&&aut!==37&&aut!==49){ $('#Cop_Aut').flyout('show').focus();  return; }           
   if(!data['Ciu_Cod'].length){ $('#Ciu_Cod_chosen').flyout('show').focus(); return; }                     
   if(gridFact.jqGrid('getDataIDs').length-1<=0){ $.alert('Debe seleccionar al menos un <u>Item</u>!',null,'remove'); return; }
   data['items']=gridFact.getGridBatch(); 
   gridFact.startGridEdit();
   data['Old_Num']=$('#Cop_Num').data('old_num');
   data['Tic_Sri']=$("#Tic_Cod option:selected").data('ticsri');
   data['Tic_Des']=$("#Tic_Cod option:selected").text();
   data['rets']=$('#retencion').getGridBatch(); 
   data['items'].splice(data['items'].length-1, 1);
   for(var i=0; i<data['items'].length; i++){
       if(data['items'][i]['Cop_Imp']*1<=0){ $.alert('El producto <u>'+data['items'][i]['Ite_Lar']+'</u> no puede tener <i>Importe cero</i>!',null,'remove'); return; }
   }  
   if((data['Tic_Sri']*1!==1&&data['Tic_Sri']*1!==3)&&data['rets'].length>0){ $.alert('El <u>Comprobante de Retención</u> solo se aplica a <i>Facturas/Liquidaciones</i>!',null,'remove'); return; }
   $.arraySpliceFields(data['items'],['index','delete','select','Uni_Des','Pld_Cdc','Pld_Des']);
   if(data['Tic_Sri']*1===3){ //liquidacion compras               
       if(($('#t_rubros').val()*1+('0'+$('#infoLiquida').data('actual'))*1)>13000)
            { $.alert('Las <u>liquidaciones en Compras</u> de este Proveedor exceden el limite!',null,'remove'); return; }
   }
   if(!$('#pagoFormTemp').valid()){ setTimeout(function (){$('#pagoFormTemp').formSubmit();},0); return; };
   if($('#Ren_Tot').val()*1>0&&!$('#reteFormTemp').valid()){ setTimeout(function (){$('#reteFormTemp').formSubmit();},0); return; };
   if($.varValid(data['Cpp_Min'])&&data['Cpp_Min']*1>0&&data['Val_Pcc']*1<data['Cpp_Min']*1){ $.alert('El valor de los <i>Pagos Activos</i> es superior al valor a pagar.<br/><b class="green">Pagos:</b> <u>'+$.toFixed(data['Cpp_Min'])+'</u><br/><b class="red">A pagar:</b> <u>'+data['Val_Pcc']+'</u>',null,'remove'); return;  } 
   
   $.createDialogConfirm('¿Esta seguro de guardar el Documento?',data,saveDocument);
}        
// Guardar documento
function saveDocument(data){ console.log(data); console.log(data['rets']);
    $.saveDataJson('',data,
    function (resp){
        $('#resultContent').setData(resp);             
        $('#copForm').setData(resp['Cop_Data']);$('#copresult').setRows(resp['Cop_Rows']); $('#btnCopPrint').data('url',resp['Cop_Link']);
        if(Cof_Con==='S'){ 
            $('#compForm').setData(resp['Com_Data']);$('#asiento').setRows(resp['Com_Rows']); $('#btnComPrint').data('url',resp['Com_Link']);
        }        
        if($.varValid(resp['Ret_Cod'])&&resp['Ret_Cod']!==''){
            var ret=$('#reteFormTemp').getData();
            if(('0'+ret['Ren_Tot'])*1===0) ret['Ret_Num']='Ninguno';
            $('#retForm').setData(ret);$('#reteresult').setRows($('#retencion').getGridBatch()); $('#btnRetPrint').data('url',resp['Ret_Link']);
            if($.varValid(resp['mail'])){
                if(resp['mail']===false) $.alert('Surgio un problema al enviar el mail <u>Comprobante Electronico</u> al <i>Proveedor</i>!');
                if(resp['mail']===true) $.alert('El mail del <u>Comprobante Electronico</u> al <i>Proveedor</i> se envio correctamente!',null,'ok green');
            }
            $('#btnRetXml')[$.varValid(resp['Ret_Xmls'])?'show':'hide']().data('url',resp['Ret_Xmls']);            
            $('#retForm').show();
        }else{ $('#retForm').hide(); }
        $('#documentoMain').moveComp('#documentoResult').updateGridsSizes();  return false;
    }); 
}
// Revisa si existe el proveedor
// buscar una persona
function searchProvee(ced){            
    $.post("",{provAjax2:true,Prs_Ced:ced.substring(0,10)}, function( response ) {
        if(response['total']*1===1){                     
           if(!$.varValid(response['rows'][0]['Prv_Cod'])||response['rows'][0]['Prv_Cod'].length===0){
               $('#provCreateForm').setData(response['rows'][0]);                       
           }else{
               selectProvee(response['rows'][0]);
               $('#provCreateDialog').dialog('close');
           }
        }                                   
    },'json').fail(function (){ $('#provCreateForm').setData({}); }).always(function (){});
 }
// guardar un proveedor
function guardaProvee(){            
    $.saveDataJson("",$('#provCreateForm').getData('guardaProvAjax'), function( resp ){ selectProvee(resp['prov']); $('#provCreateDialog').dialog('close'); return false; });
 }

function checkImportacion(Tic_Sri){
    var Importa=Tic_Sri*1===17;
    $('#Cop_Num').unmask().mask(Importa?"999-9999-99-99999999":"999-999-999999999",{placeholder:"_"});    
}
function checkLiquidacion(){
    $('#infoLiquida').hide();
    var Tic_Sri=$('#Tic_Cod').find('option:selected').data('ticsri')*1, Prv_Cod=$('#provFormTemp').getData()['Prv_Cod']; if(Tic_Sri!==3||Prv_Cod==='') return;    
    $.post("",{liquida:true, Tic_Sri:Tic_Sri,Prv_Cod:Prv_Cod,Cop_Fec:$('#Cop_Fec').val()}, function( response ) {
       if(response['success']===true){           
           var liquida={actual:(!$.varValid(response['total'])||response['total']===''?0:(response['total']*1))};
           $('#infoLiquida').data(liquida);
           if(liquida['actual']>=13000){               
               $.alert('Las <u>liquidaciones en Compras</u> de este Proveedor ya exceden el limite 13000!',null,'remove'); return;
               $('#infoLiquida').attr('title','Las <u>liquidaciones en Compras</u> de este Proveedor ya exceden el limite 13000!');
           }
           $('#infoLiquida').attr('title','El total de Liquidaciones es '+liquida['actual']);
       }                                   
   },'json');
}

// retenciones
function calculaRetencion(){ 
    var ids=gridFact.jqGrid('getDataIDs'), rets=[],tot={Ret_Ren_Tot:0,Iva_Ren_Tot:0,Ren_Tot:0,Val_Pcc:0,Ret_Uca:0,Ret_Pca:0,Ret_Ica:0},
            Tic_Sri=$('#Tic_Cod').find('option:selected').data('ticsri')*1, rise=(Tic_Sri===2||Tic_Sri===9), Cop_Des=$('#Cop_Des').val()*1;
    if(ids.length<=1){ $('#retencion').clearGrid(); $('.reteTot').setData({Val_Pcc:'0.00'}); return;}    
    for (var i=0, z=ids.length-1; i<z; i++){
        var row=gridFact.jqGrid('getLocalRow',ids[i]),row_Imp=((row['Cop_Imp']*1)-(Cop_Des>0?(row['Cop_Imp']*Cop_Des/100):0));
        if($.varValid(row['Ret_Ren_Cod'])&&row['Ret_Ren_Cod'].length>0){
            var add=true, ret={Ren_Ret:'R', Ren_Rete:'RENTA', Ren_Cod:row['Ret_Ren_Cod'],Ren_Por:row['Ret_Ren_Por'],Ren_Sri:row['Ret_Ren_Sri'],Ren_Con:row['Ret_Ren_Con'],Ren_Imp:row_Imp};
            $.each(rets,function(i,v){ if(ret['Ren_Cod']===v['Ren_Cod']){ rets[i]['Ren_Imp']+=ret['Ren_Imp']; add=false; } });  
            if(add) rets.push(ret);            
            if(String(ret['Ren_Sri'])===String(cod_banano)){ tot['Ret_Uca']+=row['Cop_Can']*1;tot['Ret_Ica']+=row_Imp; }
        }
        if($.varValid(row['Iva_Ren_Cod'])&&row['Iva_Ren_Cod'].length>0&&!rise){            
            var add=true, ret={Ren_Ret:'I', Ren_Rete:'IVA',Ren_Cod:row['Iva_Ren_Cod'],Ren_Por:row['Iva_Ren_Por'],Ren_Sri:row['Iva_Ren_Sri'],Ren_Con:row['Iva_Ren_Con'],Ren_Imp:row_Imp*(row['Iva_Por']/100)};
            $.each(rets,function(i,v){ if(ret['Ren_Cod']===v['Ren_Cod']){ rets[i]['Ren_Imp']+=ret['Ren_Imp']; add=false; } });  
            if(add) rets.push(ret);
        }
    }
    $.each(rets,function(i,v){         
        rets[i]['Ren_Val']=$.round(v['Ren_Imp']*v['Ren_Por']/100);
        tot[(v['Ren_Ret']==='R'?'Ret':'Iva')+'_Ren_Tot']+=rets[i]['Ren_Val'];        
    });
    tot['Ren_Tot']=tot['Ret_Ren_Tot']+tot['Iva_Ren_Tot'];
    tot['Val_Pcc']=$('#t_rubros').val()*1-($('#Ret_Asu').prop('checked')?0:tot['Ren_Tot']);
    (tot['Ren_Tot']>0?$('.ret_field').removeAttr('disabled'):$('.ret_field').attr('disabled','disabled'));
    $.each(tot,function (k,v){ tot[k]=$.toFixed(v); });
    
    if(tot['Ret_Uca']*1>0&&tot['Ret_Ica']*1>0){ tot['Ret_Pca']=$.round(tot['Ret_Ica']/tot['Ret_Uca'],8); tot['Ret_Uca']=$.round(tot['Ret_Uca'],0); $('.cod_banano').show().find('input').attr('required','required'); }else{ tot['Ret_Uca']=tot['Ret_Pca']=tot['Ret_Ica']=''; $('.cod_banano').hide().find('input').removeAttr('required'); }
    
    $('.reteTot').setData(tot);         
    $('#retencion').setRows(rets);
}
function seleccionaRetencion(data){ $('#codiForm').setData(data).formSubmit(); $('#codiDialog').dialog('open'); }
function agregaRetencion(data){ 
    var form=$('#codiForm').getData(),ret={}; 
    $.each(data,function(k,v){ ret[(form['tipo']==='R'?'Ret_':'Iva_')+k]=v; }); 
    if(form['checkRentaIva']==='N')
        gridFact.changeRow(form['index'],ret); 
    else{
        var ids = gridFact.jqGrid('getDataIDs'); 
        for (var i=0, z=ids.length-1; i<z; i++)
            gridFact.changeRow(ids[i],ret); 
    }
    calculaRetencion();
    $('#codiDialog').dialog('close'); 
}
function eliminaRetencion(form){ var retBasic={Ren_Cod:'',Ren_Sri:'',Ren_Por:'',Ren_Con:''},ret={}; $.each(retBasic,function(k,v){ ret[(form['tipo']==='R'?'Ret_':'Iva_')+k]=v; }); gridFact.changeRow(form['index'],ret); calculaRetencion(); }
function getRentaButton(cv,data,cObjt){  
    var obj,valid=($.varValid(cv)&&cv!=='');
    obj=$('<div class="input-group input-group-xs ret"><span type="text" class="form-control center" title="'+(valid?cObjt[(data['tipo']==='R'?'Ret_':'Iva_')+'Ren_Por']+'% - ':'')+(valid?cObjt[(data['tipo']==='R'?'Ret_':'Iva_')+'Ren_Con']:'')+'">'+(valid?cv:'')+'</span><span class="input-group-btn"><button type="button" onclick="'+(valid?'elimina':'selecciona')+'Retencion($(this).parent().data(\'originaldata\'));" class="btn btn-'+(valid?'warning':'info')+'" title="'+(valid?'Quitar':'Agregar')+' '+(data['tipo']==='R'?'Imp. a la Renta':'Ret. del Iva')+'" tabindex="-1"><i class="glyphicon glyphicon-'+(valid?'minus':'plus')+'"></i></button></span></div>');
    obj.find('.input-group-btn').attr('data-originaldata',$.jsonParser($.extend(data,valid?{}:{search:'',op_opciones:'p',checkRentaIva:'N',Cop_Fec:$("#Cop_Fec").val()})));
    return obj.prop('outerHTML'); 
}
$.fn.fmatter.ice=function(cv,opts,cObjt){ var ice_por=cObjt['Cop_Ice']||cObjt['Ice_Por']; if($.varValid(ice_por)&&ice_por!==''&&!isNaN(ice_por)&&ice_por*1>0) return ice_por+' %'; else return ''; };
$.fn.fmatter.ice.unformat=function(cv,opts,cObjt){ return cv.replace(' %',''); };
$.fn.fmatter.impRenta=function(cv,opts,cObjt){ if(!$.varValid(cObjt['Pro_Cod'])||cObjt['Pro_Cod']==='') return ''; return getRentaButton(cv,{tipo:'R',index:cObjt['index']},cObjt); };
$.fn.fmatter.impRenta.unformat=$.unformatCellHtml;
$.fn.fmatter.retIva=function(cv,opts,cObjt){ if(!$.varValid(cObjt['Pro_Cod'])||cObjt['Pro_Cod']==='') return ''; if(cObjt['Iva_Por']*1===0) return ''; return getRentaButton(cv,{tipo:'I',index:cObjt['index']},cObjt);  };
$.fn.fmatter.retIva.unformat=$.unformatCellHtml;
$.fn.fmatter.edicion=function(cv,opts,cObjt){ 
    if(cObjt['Com_Edit']==='N') return '<i title="El comprobante contable es formato anterior" class="glyphicon glyphicon-lock orange"></i>';
    if(cObjt['Cop_Est']!=='A') return '<i title="Registro Anulado/Inactivo" class="glyphicon glyphicon-remove red"></i>';
    if(cObjt['Ret_Aut']==='S') return '<i title="Retencion Electronica Validada" class="fa fa-globe green"></i>';
    //if(cObjt['Cpp_Det']==='S') return '<i title="Contiene Pagos Activos" class="fa fa-money green"></i>';
    if(cObjt['Rcc_Det']==='S') return '<i title="Reposición de Caja Chica" class="fa fa-creative-commons purple"></i>';
    return $.getGridButton(editDocument,cObjt);  
};
$.fn.fmatter.edicion.unformat=$.unformatCellHtml;