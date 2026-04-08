// on load
$(function(){
	// inicializando dataPickers
	$('.datepickers').createDatePickers();
	
	// inicalizando dialog de retenciones de banano
	$('#RetencionDialog').createDialog({height:350,width:520,icon:'usd'});


	// cargar los tipos de ajuste para egresos en Tia_Cod Seleccion.
	cargarTiaCod('E').then(data=>{
		data.map((obj,ind,arr)=>{
			let option=$('<option></option>').text(obj.Tia_Des).val(obj.Tia_Cod);
			$('#Tia_Cod').append(option);	
		});
	}).catch((err)=>console.log(err));


	$.getDataJson('',{'getTipoRetenciones':true},function(ret){
		ret.data.map(val=>{
			let opt =$(`<option value=${val.Con_Tip}>${val.Con_Tip}</option>`).data(val);
			$('#tipo_ret_ban').append(opt);
		});
	});



$('#Bod_Cod').on('change',function(event){
	limpiar_tabla('#descuentos');
	get_descuentos($('#Bod_Cod').val()).then(data=>{
		data.map(obj=>{
			obj.getStokLiquidacion=true;
			obj.Bod_Cod=$('#Bod_Cod').val();
			$.getDataJson('',obj,function(resp){
				obj.Pro_Can=(obj.Pro_Can*1)+(resp.data.valor*1);
				
				obj.Pro_Can>0?addItem(obj,$('#descuentos')):'';
			},function(err){

			})
			
		});
	});
});

$('#tipo_ret_ban').on('change',function(e){
	$('#lista_retenciones li').remove();
	OpenRetBanano($('#tipo_ret_ban').val());
});


// inicalizando dialog search de productor
$.createSearchDialog('prodDialog',[
        { label: 'C&oacute;d.Int.', name: 'Prd_Cod', key: true, width: 15,align:"center",hidden:true },                                
        { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },                      
        { label: 'Productor', name: 'Prd_Nom', width: 100},
        { label: 'Cod MAG.', name: 'Prd_Mag', width: 20,align:"center", labelLong:'C&oacute;digo de MAGAP '}, 
        { label: 'Est.', name: 'Prd_Est', width: 20,align:"center", labelLong:'Estado de Productor', formatter:'truefalse', formatoptions:{msg:false} }, 
        { label: 'Bodega.', name: 'bod', width: 20,align:"center", labelLong:'Bodega Asignada', formatter:'truefalse', formatoptions:{msg:false} }, 
        { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false,
        	formatter: function(cell,op,row){
        		let retorno="<span class='col-xs-12 fa fa-lock orange'></span>";
        		if (row.bod*1>0)
        			retorno=$.getGridButton(selectProd,row,'Cargar Productor','arrow-right',undefined,'success');
        			return retorno;
        } 
    	}
    ],null,null,null,{headertitles:true},{ title:'Productor', text:'Prs_Ced' }); 



	// inicalizando dialog search de ingresos
	$.createSearchDialog('ingresoDialog',[
        { label: 'C&oacute;d.Int.', name: 'Ite_Cod', key: true, width: 15,align:"center"},                                
        { label: 'Categoria', name: 'Cat_Des', width: 50 },                      
        { label: 'Descripci&oacute;n', name: 'Ite_Lar', width: 100},
        { label: 'Marca', name: 'Mar_Des', width: 20,align:"center"}, 
        { label: 'Precio', name: 'Pre_Pvp', width: 20,align:"right" ,formatter:'currency'},
        { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false,
        	formatter: function(cell,op,row){
        		let retorno="<span class='col-xs-12 fa fa-lock orange'></span>";
        		// if (row.bod*1>0)
        			retorno=$.getGridButton(addItem,$.extend(row,{'selector':'#ingresos','modal':'#ingresoDialog'}) ,'Cargar Ingreso','arrow-right',undefined,'success');
        			return retorno;
        	} 
    	}
    ],null,null,null,{headertitles:true},{ title:'Ingresos', text:'Prs_Ced' }); 


// inicalizando dialog search de descuentos
	$.createSearchDialog('descuentoDialog',[
        { label: 'C&oacute;d.Int.', name: 'Ite_Cod', key: true, width: 15,align:"center"},                                
        { label: 'Categoria', name: 'Cat_Des', width: 50 },                      
        { label: 'Descripci&oacute;n', name: 'Ite_Lar', width: 100},
        { label: 'Marca', name: 'Mar_Des', width: 20,align:"center"}, 
        { label: 'Cant.', name: 'Pro_Can', labelLong:'Cantidad a Descontar', width: 20,align:"center"},
        { label: 'Precio', name: 'Pre_Pvp', width: 20,align:"right" ,formatter:'currency'},
        { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false,
        	formatter: function(cell,op,row){
        		let retorno="<span class='col-xs-12 fa fa-lock orange'></span>";
        		// if (row.bod*1>0)
        			retorno=$.getGridButton(addItem,$.extend(row,{'selector':'#descuentos','modal':'#descuentoDialog'}) ,'Cargar Descuento','arrow-right',undefined,'success');
        			return retorno;
        	} 
    	}
    ],null,null,null,{headertitles:true},{ title:'Descuentos', text:'Prs_Ced' }); 




// inicalizando dialog search de descuentos
	$.createSearchDialog('retencionDialog',[
        { label: 'C&oacute;d.Int.', name: 'Ite_Cod', key: true, width: 15,align:"center"},                                
        { label: 'Categoria', name: 'Cat_Des', width: 50 },                      
        { label: 'Descripci&oacute;n', name: 'Ite_Lar', width: 100},
        { label: 'Marca', name: 'Mar_Des', width: 20,align:"center"}, 
        { label: 'Precio', name: 'Pre_Pvp', width: 20,align:"right" ,formatter:'currency'},
        { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false,
        	formatter: function(cell,op,row){
        		let retorno="<span class='col-xs-12 fa fa-lock orange'></span>";
        		// if (row.bod*1>0)
        			retorno=$.getGridButton(addItem,$.extend(row,{'selector':'#descuentos'}) ,'Cargar Descuento','arrow-right',undefined,'success');
        			return retorno;
        	} 
    	}
    ],null,null,null,{headertitles:true},{ title:'Descuentos', text:'Prs_Ced' }); 



	var tabla_ingresos=$('#ingresos');
	tabla_ingresos.createGrid({
		'data':[],'caption':'<p align="center">INGRESOS</p>', rowNum: 10000000, height: 'auto',footerrow:true,headertitles:true, selectGridRows:false,
		'colModel':
		[
      {name:'select',label:'<i class="glyphicon glyphicon-check"></i>', width:30, align:'center',viewable: false, formatter:'gridButton', formatoptions:{ action:openItemSelector, icon:'check', title:'Seleccionar Item', data:function(o){ return {'indice':o.index,'tabla':tabla_ingresos,'tipo':o.tipo,'dialog':'#ingresoDialog'}; } }, resizable: false },
      {name:'index',label:'Index', width:20, sorttype:"int",align:'center',key:true,hidden:true},
      {name:'Pro_Cod',label:'C&oacute;d.Int.', width:20, sorttype:"int",align:'center',hidden:true},                    
      {name:'Pro_Can',label:'Cant.', labelLong:'Cantidad', width:40, align:"right", title:false, editable:true, editoptions:{dataInit:styleCant}},
      {name:'Uni_Des',label:'Uni.', labelLong:'Unidad', width:25, resizable: false },
      {name:'Ite_Lar',label:'Descripci&oacute;n', width:150},
      {name:'Adq_Cor',label:'Adq_Cor',hidden:true, width:45},
      {name:'tipo',label:'tipo', width:50,hidden:false},
      {name:'Pro_Pru',label:'P. Unitario', labelLong:'Precio Unitario', width:60, align:"right", title:false, editable:true, editoptions:{dataInit:stylePru}},                    
      {name:'Pro_Imp',label:'Importe', width:70, align:"right", summaryRound: 2,formatter:"currency",formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.', defaultValue: '0.00'},classes:'columnHighlight1'},
      {name:'delete',label:'<i class="glyphicon glyphicon-remove"></i>', width:30, align:'center',viewable: false, formatter:'gridButton', formatoptions:{ action:deleteItem, icon:'remove', title:'Eliminar Item', type:'danger', data:function(o){ return {'index':o.index,'selector':'#ingresos'}; }, attr:{'tabindex':'-1'}, conditional:function(o){ return !(!$.varValid(o['Pro_Cod'])||o['Pro_Cod']===''); } }, resizable: false }
    ],loadComplete: function (data) {
      $(this).setGridSummary(['Pro_Imp'], {Pro_Pru: '<div style="text-align:right;">TOTAL:</div>'});
    }
  },true,'ingresosPager',{view:false}).gridButtonsAdd([
        {caption:'Cajas',buttonicon:'glyphicon glyphicon-plus', onClickButton: function(){ $('#ingresoDialog').data({'tipo':'CAJA','indice':undefined});index=0;$('#ingresoDialog').dialog('open'); } },
        {caption:'Carton',buttonicon:'glyphicon glyphicon-plus', onClickButton: function(){ $('#ingresoDialog').data({'tipo':'CARTON','indice':undefined});index=0;$('#ingresoDialog').dialog('open'); } },
        {caption:'Materiales Peque&ntilde;os',buttonicon:'glyphicon glyphicon-plus', onClickButton: function(){ $('#ingresoDialog').data({'tipo':'MATERIAL','indice':undefined});index=0;$('#ingresoDialog').dialog('open'); } },
        {caption:'Remover Todos',buttonicon:'glyphicon glyphicon-remove', onClickButton: function(){ tabla_ingresos.clearGrid(); } }
  ]);             
    
  var tabla_retenciones=$('#reten')
  tabla_retenciones.createGrid({
  	'data':[],rowNum:10000000,height: 'auto',headertitles:true,footerrow:true,
  	'colModel':
		[
      {name:'Con_Cod',label:'C&oacute;d.Int.', width:20, sorttype:"int",align:'center',hidden:true,key:true},                    
      {name:'rango',label:'Rango', labelLong:'Rango de Retenci&oacute;n', width:60, align:"right", title:false, editable:false},
      {name:'Con_Por',label:' % ', labelLong:'Porcentaje de Retenci&oacute;n', width:25, resizable: false,editable:false },
      {name:'Pro_Can',label:'Cant.', width:30,labelLong:'Cantidad en Rango',editable:true,editoptions:{dataInit:styleCantRet}},
      {name:'Pro_Pru',label:'P. Unitario', width:70,formatter:'currency',editable:true,editoptions:{dataInit:stylePruRet}},
      {name:'Pro_Fac',label:'Fac',  width:60, align:"right", formatter:"currency"},
      {name:'Pro_Imp',label:'Importe',  width:60, align:"right", formatter:"currency"}
    ],loadComplete: function (data) {
      $(this).setGridSummary(['Pro_Imp'], {Pro_Pru: '<div style="text-align:right;">TOTAL:</div>'});
    }
  },true);


	var tabla_descuentos=$('#descuentos');
	tabla_descuentos.createGrid({
		'data':[],'caption':'<p align="center">DESCUENTOS</p>', rowNum: 10000000, height: 'auto',footerrow:true,headertitles:true, selectGridRows:false,
		'colModel':
		[
      {name:'select',label:'<i class="glyphicon glyphicon-check"></i>', width:30, align:'center',viewable: false, formatter:'gridButton', formatoptions:{ action:openItemSelector, icon:'check', title:'Seleccionar Item', data:function(o){ return {'indice':o.index,'tabla':tabla_descuentos,'tipo':o.tipo,'dialog':'#descuentoDialog'}; } },conditional:function(o){ return !(!$.varValid(o['Pro_Cod'])||o['Pro_Cod']===''); }, resizable: false },
      {name:'index',label:'Index', width:20, sorttype:"int",align:'center',key:true,hidden:true},
      {name:'Pro_Cod',label:'C&oacute;d.Int.', width:20, sorttype:"int",align:'center',hidden:true},                    
      {name:'Pro_Can',label:'Cant.', labelLong:'Cantidad', width:40, align:"right", title:false, editable:true, editoptions:{dataInit:styleCant}},
      {name:'Uni_Des',label:'Uni.', labelLong:'Unidad', width:25, resizable: false },
      {name:'Ite_Lar',label:'Descripci&oacute;n', width:150},

      {name:'Adq_Cor',label:'Adq_Cor', width:150,hidden:true},
      {name:'tipo',label:'tipo', width:50,hidden:false,hidden:true},
      {name:'Con_Cod',label:'Configuracion', width:50,hidden:true},
      {name:'Ret_Bas',label:'Base Imp',hidden:true,labelLong:'Base de Retencion',formatter:'currency', width:50},
      {name:'Pro_Pru',label:'P. Unitario', labelLong:'Precio Unitario', width:60, align:"right", title:false/*, summaryRound: 8,formatter:"currency",formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.',decimalPlaces: 8, defaultValue: ''}*/, editable:true, editoptions:{dataInit:stylePru}},                    
      {name:'Pro_Imp',label:'Importe', width:70, align:"right", summaryRound: 2,formatter:"currency",formatoptions: {prefix:'', thousandsSeparator:',',decimalSeparator:'.', defaultValue: '0.00'},classes:'columnHighlight1'},
      {name:'delete',label:'<i class="glyphicon glyphicon-remove"></i>', width:30, align:'center',viewable: false, formatter:'gridButton', formatoptions:{ action:deleteItem, icon:'remove', title:'Eliminar Item', type:'danger', data:function(o){ return {'index':o.index,'selector':'#descuentos'}; }, attr:{'tabindex':'-1'}}, resizable: false }
    ],loadComplete: function (data) {
            $(this).setGridSummary(['Pro_Imp'], {Pro_Pru: '<div style="text-align:right;">TOTAL:</div>'});
    }
  },true,'descuentosPager',{view:false}).gridButtonsAdd([
        {caption:'Agregar',buttonicon:'glyphicon glyphicon-plus', onClickButton: function(){ if (isNaN($('#Bod_Cod').val()*1)){/*alerta de eleccion de bodega para descuentos*/return false;} else {index=0;$('#bodega_descuento').val($('#Bod_Cod').val());$('#descuentoDialog').dialog('open'); }}},
        {caption:'Retenci&oacute;n',buttonicon:'glyphicon glyphicon-plus', onClickButton: function(){ index=0; $('#RetencionDialog').dialog('open'); $('#tipo_ret_ban').trigger('change')} },
        {caption:'Remover Todos',buttonicon:'glyphicon glyphicon-remove', onClickButton: function(){ tabla_descuentos.clearGrid();  } }
    ]); 

  let elementoTipo=$('<input class="hidden" id="bodega_descuento" name="Bod_Cod"/>');
  $('#descuentoForm').append(elementoTipo);
});


// edita item de tablas de igresos y egresos
function openItemSelector(data){
	console.log(data);
	if (data.tipo==="RET")
		$('#RetencionDialog').dialog('open');			
	else
		$(data.dialog).data(data).dialog('open');
};


function OpenRetBanano(Con_Tip){
	cargarRetenciones(Con_Tip).then(msg=>{

		var ingresos=$('#ingresos').getGridBatch().filter(val=>val.tipo==='CAJA');
		let cantidad=ingresos.reduce((ant,act)=>ant+act.Pro_Can*1,0);
		
		var retenciones=msg.map(fila=>{
			let data_default={'Pro_Can':0.00,'Pro_Pru':0.00};
			if ($.vv(ingresos[0]))
				data_default.Pro_Pru=ingresos[0].Pro_Pru;
			if (cantidad - fila.Con_Ini*1 >= 0){
				let sub=cantidad-fila.Con_Fin*1
				if(fila.Con_Fin*1<=0 || sub <=0)
					sub=0
				data_default.Pro_Can=(cantidad+1-fila.Con_Ini*1)-(sub);
			}
			let retorno= $.extend(data_default,fila);
			retorno.Pro_Imp=retorno.Pro_Can*retorno.Pro_Pru*(fila.Con_Por/100)
			retorno.Pro_Fac=retorno.Pro_Can*retorno.Pro_Pru
			return retorno
		});

		console.log(retenciones);
		$('#reten').jqGrid('clearGridData');
		$('#reten').jqGrid('addRowData','Con_Cod',retenciones,'last');
		$('#reten').setGridSummary(['Pro_Imp']);

		let tot_a_ret=$('#reten').getGridSummary(['Pro_Imp']).Pro_Imp;
		$('#total_ret').val(tot_a_ret);
		let result=0
		let fact=$('#reten').getGridSummary(['Pro_Fac']).Pro_Fac*1;
		if (fact>0)
		result=tot_a_ret/fact;
		$('#total_tarifa').val($.toFixed(result*100,4));
		$('#Ret_Bas').val(fact);

		$('#reten').startGridEdit();
		$('#ingresos').startGridEdit();

	}).catch(obj=>{
		// si ocurre error de sql
		$.alert('ocurrio un error :( al consultar retenciones ');
		console.log(obj);
	});
	
}



function cargarRetenciones(Con_Tip='PRODUCTOR'){
	return new Promise((resolv,reject)=>{
		$.getDataJson('',{'retencionAjax':true,'Con_Tip':Con_Tip},function(res){
			resolv(res.data);
		},function(err){
			reject(res);
		});
	});
}

// borra item de tablas
function deleteItem(obj){ 
	let tabla_ref=$(obj.selector);
	var data=tabla_ref.jqGrid('delRowData',obj.index);
	tabla_ref.setGridSummary(['Pro_Imp']);
	setSumaTotales();
};


// agrega un item al documento
function addItem(item,selectGrid,classCss){
		if ($.vv(item.modal)) {
			item.tipo=$(item.modal).data('tipo');
		}
		if (selectGrid===undefined) {
			selectGrid=$(item.selector);
		}
    var next=selectGrid.jqGrid('getCol','index',false,'max');
    next=(isNaN(next)?1:next+1);
    item.Pro_Can=item.Pro_Can===undefined?1:item.Pro_Can;
    item.Pro_Pru=item.Pro_Pru===undefined?item.Pre_Pvp:item.Pro_Pru;
    let row = $.extend(item,{index:next,'Pro_Pru':item.Pre_Pvp,'Pro_Imp':item.Pro_Pru*item.Pro_Can});
    console.log('indice_edicion',$(item.modal).data());
    if($(item.modal).data('indice')!==undefined){
    	selectGrid.jqGrid('setRowData',$(item.modal).data('indice')*1,row);
    	selectGrid.jqGrid('saveRow',$(item.modal).data('indice')*1,false,'clientArray');
    	selectGrid.jqGrid('editRow',$(item.modal).data('indice')*1);
    	next=$(item.modal).data('indice')*1;
    }else{
    	selectGrid.jqGrid('addRowData',next,row,'last');    	
    }
    if (classCss) {
    	selectGrid.jqGrid('setRowData',next,false,'myAltRowClass');
    	selectGrid.jqGrid('setRowData',next,false,classCss);
    } 
    selectGrid.jqGrid('editRow',next);
    selectGrid.setGridSummary(['Pro_Imp']);
    setSumaTotales();
}

	function getTot(id_tabla) {
		return $(id_tabla).getCol('Pro_Imp',false).reduce((ant,act)=>ant+(act*1),0);
	}

	function setSumaTotales(){
		let des=getTot('#descuentos'),ing=getTot('#ingresos');
		$('#resumenes').setData({'res_ingreso':$.toFixed(ing),'res_descuento':$.toFixed(des),'res_total':$.toFixed(ing-des)});
	}


// Actualiza los valores de la fila
function updateRowItem(obj,selectGrid){
    var row=$.extend({},selectGrid.jqGrid('getRowData',obj['rowId']),selectGrid.find('tr#'+obj['rowId']).getDataForced());
    row['Cop_Imp']=row['Cop_Can']*(0+row['Cop_Pru'])*1;
    row['Cop_Imp']=row['Cop_Imp']-(('0'+row['Cop_Dec'])*1>0?row['Cop_Imp']*row['Cop_Dec']/100:0);
    selectGrid.changeRow(obj['rowId'],row);
}





// seleccionar productor 
function selectProd(prod){
	console.log(prod);
	$('#datos_productor').setData(prod);
	$('#Bod_Cod').find('option').remove();
	getBodegas(prod.Prd_Cod).then(obj=>{
		obj.map(ele=>{
		let opcion=`<option value=${ele.Bod_Cod}>${ele.Bod_Nom}</option>`;
		$('#Bod_Cod').append(opcion);});
		$('#Bod_Cod').trigger('change');
	}).catch(obj=>console.log(obj));
	$('#prodDialog').dialog('close');
}


// consultar bodegas de productor
function getBodegas(id_productor){
	return new Promise((resolve,reject)=>{
	$.getDataJson('',{'Prd_Cod':id_productor,'getDobegas':true},function(res){
		resolve(res.data);
	},function(err){
		reject(err);
	})});
}


function limpiar_tabla(selector){
	$(selector).clearGrid();
}

function get_descuentos(Bod_Cod){
	return new Promise((resolve,reject)=>{
		$.getDataJson('',{'Bod_Cod':Bod_Cod,'get_descuentos':true},function(res){
			resolve(res.data);
		},function(err){
			reject(err)
		});	
	});	
}


function CambiarRetencion(event){
	let object_form=$('#form_change_rete').getData();
	let rows_reten=$('#reten').getGridBatch();
	// borrando retencion
	$('#descuentos').jqGrid('getCol','tipo',true).filter(obj=>obj.value==='RET').map(row=>$('#descuentos').jqGrid('delRowData',row.id));
	rows_reten.map(ret_obj=>{
		let default_array={'reemplazar':1,'selector':'#descuentos','tipo':'RET','Ite_Lar':'RETENCI&Oacute;N '+ret_obj.rango,'Pro_Pru':ret_obj.Pro_Imp,'Pro_Can':1,'Ret_Bas':ret_obj.Pro_Fac};
		addItem($.extend(ret_obj,default_array),undefined,'cellGreen2');
	});
	$('#RetencionDialog').dialog('close');
	$('#reten').startGridEdit();
	return false;
}


// funciones de calculo de ingresos y descuentos
// estilos de tablas de ingresos y descuentos 

function styleCant(e,obj,opt){
console.log('e',e,'obj',obj,'opt',opt);  
		let tabla_ref=$(e).parents('table');
    e.style.textAlign = 'right';  e.placeholder='0'; 
    $(e).on('keyup',function (){
       if(isNaN(this.value)){ $(this).val('1').focus();   }
       else if (this.value % 1 !== 0){ 
       	var dec=String(this.value).split("."); if(typeof dec[1]!=='undefined'&&dec[1].length>2) this.value=$.toFixed(this.value);  
       } 
        updateRowItem(obj,tabla_ref);
    });

    if (tabla_ref.jqGrid('getRowData',obj.rowId).tipo==='RET')
    	$(e).prop('disabled',true);

};
function stylePru(e,obj,opt){  
		let tabla_ref=$(e).parents('table');          
    e.style.textAlign = 'right'; e.placeholder='0.00';
    $(e).on('keyup',function (){
       if(isNaN(this.value)/*||this.value===''||(!isNaN(this.value)&&this.value*1===0)*/){ $(this).val('').focus();; }
       else if (this.value % 1 !== 0){ var dec=String(this.value).split("."); if(typeof dec[1]!=='undefined'&&dec[1].length>8) this.value=$.toFixed(this.value,8);  } 
         updateRowItem(obj,tabla_ref);
    });
    if (tabla_ref.jqGrid('getRowData',obj.rowId).tipo==='RET')
    	$(e).prop('disabled',true);            
};

//Actualiza los valores de la fila
function updateRowItem(obj,items){
	var datosa=items.jqGrid('getRowData',obj['rowId']);
	var datosb=items.find('tr#'+obj['rowId']).getDataForced();
	var row=$.extend({},datosa,datosb);
	row['Pro_Imp']=row['Pro_Can']*(0+row['Pro_Pru'])*1;
	items.changeRow(obj['rowId'],row);
	items.setGridSummary(['Pro_Imp']);
	setSumaTotales();
}


function styleCantRet(e,obj,opt){  
		let tabla_ref=$(e).parents('table');
    e.style.textAlign = 'right';  e.placeholder='0'; 
    $(e).on('keyup',function (){
       if(isNaN(this.value)){ $(this).val('1').focus();   }
       else if (this.value % 1 !== 0){ 
       	var dec=String(this.value).split("."); if(typeof dec[1]!=='undefined'&&dec[1].length>2) this.value=$.toFixed(this.value);  
       } 
        updateRowItemRet(obj,tabla_ref);
    });
};
function stylePruRet(e,obj,opt){  
		let tabla_ref=$(e).parents('table');          
    e.style.textAlign = 'right'; e.placeholder='0.00';
    $(e).on('keyup',function (){
       if(isNaN(this.value)/*||this.value===''||(!isNaN(this.value)&&this.value*1===0)*/){ $(this).val('').focus();; }
       else if (this.value % 1 !== 0){ var dec=String(this.value).split("."); if(typeof dec[1]!=='undefined'&&dec[1].length>8) this.value=$.toFixed(this.value,8);  } 
         updateRowItemRet(obj,tabla_ref);
    });            
};


//Actualiza los valores de la fila
function updateRowItemRet(obj,items){
	var datosa=items.jqGrid('getRowData',obj['rowId']);
	var datosb=items.find('tr#'+obj['rowId']).getDataForced();
	var row=$.extend({},datosa,datosb);
	row['Pro_Imp']=row['Pro_Can']*(0+row['Pro_Pru'])*1*(row['Con_Por']/100);
	row['Pro_Fac']=row['Pro_Can']*(0+row['Pro_Pru'])*1;
	items.changeRow(obj['rowId'],row);
	items.setGridSummary(['Pro_Imp']);
	let tot_a_ret=items.getGridSummary(['Pro_Imp']).Pro_Imp;
	$('#total_ret').val(tot_a_ret);
	let result=0;
	let fact=items.getGridSummary(['Pro_Fac']).Pro_Fac*1;
	if (fact>0)
		result=tot_a_ret/fact;
	$('#Ret_Bas').val(fact);
	$('#total_tarifa').val($.toFixed(result*100,4));
}



function validaLiquidacion(){
	let dataForm=$('#formDocumento').getData();
	console.log(dataForm);

	// filtrando descuentos
	let tabla_descuentos=$('#descuentos').getGridBatch();
	let tabla_ingresos=$('#ingresos').getGridBatch();
	$('#descuentos').startGridEdit();
	$('#ingresos').startGridEdit();
	dataForm.descuentos=tabla_descuentos.filter(elem=>elem.tipo!=='RET');
	dataForm.retencion=tabla_descuentos.filter(elem=>elem.tipo==='RET');
	dataForm.cajas=tabla_ingresos.filter(elem=>elem.tipo==='CAJA');
	dataForm.ingresos=tabla_ingresos.filter(elem=>elem.tipo!=='CAJA');
	dataForm.Liq_Tot=$('input[name=res_total]').val();

	if (dataForm.cajas.length<=0) {
		$.alert('Ingrese cajas recibidas');
		return false;
	}

	if (dataForm.ingresos.length<=0) {
		$.alert('Ingrese material y carton recibido');
		return false;
	}


	dataForm.saveLiquidacion=true; //variable php de consulta
	if(dataForm.Pro_Pru.filter(obj=>obj*1<=0).length>0){
		$.alert('Los Precios Unitarios deben ser mayores a <em>$ 0.00</em>');
		return false;
	}else
		if(dataForm.Pro_Can.filter(obj=>obj*1<=0).length>0){
			$.alert('Las Cantidades deben ser mayores a <em>$ 0.00</em>');
			return false;
		}

		
		guardarLiquidacion(dataForm).then(msg=>{
			console.log(msg);
			// esperando proxima accion despues de guardado;
			// limpiando Cabecera de Liquidacion.
			$('#formDocumento').setData({});
			limpiar_tabla('#descuentos');
			limpiar_tabla('#ingresos');
			$('#Bod_Cod').find('option').remove();
			setSumaTotales();
		}).catch(msg_err=>{
			console.log(msg_err);
		});

}


function guardarLiquidacion(data){
	return new Promise((resolve,reject)=>{
		$.saveDataJson('',data,function(resp){
			resolve(resp);
		},function(err){
			reject(err);
		});	
	});
			
}

function cargarTiaCod(tipo='E'){
	return new Promise((resolve,reject)=>{
		$.getDataJson('',{'Tia_Tra':tipo,'getTipoAjus':true},function(res){
			resolve(res.data);
		},function(err){
			reject(err);
		});
	});
}

