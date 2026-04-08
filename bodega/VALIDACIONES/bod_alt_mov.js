$(function(){
	// inicializar alta de movimientos
	$('.datepicker').createDatePickers({checkAvailability:true,hideMsg:false}).mask('9999-99-99',{placeholder:'_'});
	
	cargarTiaCod('E').then(data=>{
		data.map((obj,ind,arr)=>{
			let option=$('<option></option>').text(obj.Tia_Des).val(obj.Tia_Cod);
			$('#Tia_Cod').append(option);	
		});
	}).catch((err)=>console.log(err));

	let opt_bod=$('<option></option>').text('------').val(-1);
		$('#Bod_Ori').append(opt_bod);
		$('#Bod_Des').append(opt_bod.clone(true));


	cargarBodegas().then(data=>data.map((val,ind,arr)=>{
		let opt_bod=$('<option></option>').addClass(val.Bod_Tip==='P'?'bolden':'').text(val.Bod_Nom).val(val.Bod_Cod).data(val);
		$('#Bod_Ori').append(opt_bod);		
	})).catch(err=>console.log(err));

	loadAllBodegas().then(data=>data.map((val,ind,arr)=>{
		let opt_bod=$('<option></option>').addClass(val.Bod_Tip==='P'?'bolden':'').text(val.Bod_Nom).val(val.Bod_Cod).data(val);
		$('#Bod_Des').append(opt_bod);		
	})).catch(err=>console.log(err));

	// evento cambio de bodega origen o destino
	$('.bodega').on('change',(e)=>{
		let rec=$(e.target).val()*1;
		if(rec>=0){
			let origen=$('#Bod_Ori').val();
			let destino=$('#Bod_Des').val();
			if(origen===destino){
				$(e.target).val(-1);
			}	
		}
	});

	// inicializar tabla de Productos
	$('#items').createGrid({
		data:[], rowNum: 10000000, height: 'auto', footerrow:true,
		colModel:[
			{name:'select',label:'<i class="glyphicon glyphicon-check"></i>', width:30, align:'center',viewable: false, formatter:'gridButton', formatoptions:{ action:openItemSelector, icon:'check', title:'Seleccionar Item', data:function(o){ return o.index; } }, resizable: false },
			{name:'index',label:'Index', width:20, sorttype:'int',align:'center',hidden:true},
			{name:'Pro_Cod',label:'C&oacute;d.Int.',key:true, width:20, sorttype:'int',align:'center',hidden:true},
			{name:'Pro_Can',label:'Cant.', labelLong:'Cantidad', width:40, align:'right', title:false, editable:true, editoptions:{dataInit:styleCant}},
			{name:'Uni_Des',label:'Uni.', labelLong:'Unidad', width:25, resizable: false },
			{name:'Ite_Lar',label:'Descripci&oacute;n', width:150},
			{name: 'btn_remover',label: '<i class="glyphicon glyphicon-remove"></i>',width: 20, align: 'center', formatter:'gridButton',
				formatoptions:{ action:deleteItem, data:function(o){ return o.Pro_Cod; },
				icon:'remove', type:'danger' }
			}
		]},true,'itemsPager',{view:false}).gridButtonsAdd([
		{caption:'Agregar',buttonicon:'glyphicon glyphicon-plus',class:'a', onClickButton: function(){agregarItems(); } },{},
	]);

	$.createSearchDialog('proDialog',[
			{ label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, width: 20,align:"center",hidden:false }, 
			{ label:'Unidad',name:'Uni_Des' ,width: 20,align:"center",hidden:false},
			{ label: 'Descripci&oacute;n', name: 'Ite_Lar', width: 110 },                      
			{ label: 'Marca', name: 'Mar_Des', width: 40},            
			{ label: 'Categoria', name: 'Cat_Des', width: 90,align:"center" },
			{ label: 'Disponibilidad', name: 'Pro_Can', width: 90,align:"center" },
			{ label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:selectItem, conditional:function(o){ 
				return !(o['Pro_Can']*1<=0); }, caseFalse:function(){ return '<i class="glyphicon glyphicon-lock orange" title="No tiene este Producto en la Bodega de Origen!"></i>'; } } }
		],null,null,null,null,{ title:'Producto', options:[{label:'&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;',value:'d'},{label:'&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;',value:'c'}] 
	}); 


	// onchange_bod_origen
	$('#Bod_Ori').on('change',function(e){
		$('#proDialog').find('input[name=Bod_Cod]').val($('#Bod_Ori').find(':selected').val());
	});

	$('#MovimientoForm').submit(e=>{
		e.preventDefault();
		let form_before=$('#MovimientoForm').data();
		let form_last=$('#MovimientoForm').getData();
		let form_data=$.extend(form_before,form_last);
		if(validarFormMovimiento()){
			$.createDialogConfirm('&iquest;Est&aacute; seguro que desea Registrar este Movimiento?',
				$.extend(form_data,{'saveMovimiento':true,'items':$('#items').getGridBatch()}),
				asyncGuardarMovimiento);

		}else {
			alertaAuto(`Asigne un Usuario`,'#Usu_Cod_chosen','right_top');
		}
		return false;
	});
});
// fin de onload


function agregarItems(){
	if($('#Bod_Ori').find(':selected').val()*1>=0){
		$('#proDialog').dialog('open');
		$.Search('pro');
	}else{
		alertaAuto(`Elija la bodega de Origen`,'#Bod_Ori','right_top');
	}
}


async function asyncGuardarMovimiento(data){
	let resp=await new Promise((res,rej)=>{$.saveDataJson('',data,success=>res(success),err=>rej(err));});
	$('#items').getDataIDs().map((val,ind)=>{$('#items').jqGrid('delRowData',val);});
}

function deleteItem(index){
	console.log('deleting item...');
	$('#items').jqGrid('delRowData',index);
}


function openItemSelector(id){
	index=id; $('#proDialog').dialog('open');
	$.Search('pro');
}

function cargarBodegas(){return new Promise((res,rej)=>{
		$.getDataJson('',{'bodActive':true},function(resp){
			res(resp.data);
		},function(err){
			rej(err);
		});
	});
}


function loadAllBodegas(){
	return new Promise((res,rej)=>{
		$.getDataJson('',{'bodSuc':true},function(resp){
			res(resp.data);
		},function(err){
			rej(err);
		})
	})
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

//Estilo para cantidad
function styleCant(e,obj,opt){

	e.style.textAlign = 'right';  e.placeholder='0';
	$(e).on('keyup',function (){
		if(isNaN(this.value)){ $(this).val('1').focus();   }
		else if (this.value % 1 !== 0){ var dec=String(this.value).split('.'); if(typeof dec[1]!=='undefined'&&dec[1].length>2) this.value=$.toFixed(this.value);  }
	});
}

// function to select product_form product
function selectItem(row_select){
	if ($('#items').getDataIDs().indexOf(row_select.id)<0){
		$('#items').jqGrid('addRowData','Pro_Cod',[$.extend(row_select,{'Pro_Can':1})],'last');
		$('#items').jqGrid('editRow',row_select.id);
	}else{
		$('#items').setSelection(row_select.id);
	}
}
/**
 * popup de alerta utilizado para validaciones
 * @param  {string} mensaje    descripccion
 * @param  {string} componente asociado a popup
 * @param  {string} direccion  direccion de popup con respecto a componente
 * @return {void}         
 */
function alertaAuto(mensaje,componente,direccion){
    $(componente).flyout('hide');
    $(componente).createFlyout(mensaje,{'icon':'exclamation','placement':direccion,'timeDismis':6000});
    $(componente).flyout('show');
}
/**
 * verifica que todo este en orden antes de generar un movimiento
 * @return {bool} verificacion
 */
function validarFormMovimiento(){
	let estado=false;
	let items_exist=$('#items').getDataIDs().length>0;
	if(items_exist){
		let items_more_cero=$('#items').getCol('Pro_Can').every(ind=>{
			let bool_condicion=$(`#${$(ind).attr('id')}`).val()*1>0
			if(!bool_condicion)
				console.log('cer=>',$(`#${$(ind).attr('id')}`).val());
			return $(`#${$(ind).attr('id')}`).val()*1>0;});
		if(items_more_cero){
			estado=true
		}
	}
	return estado
}


