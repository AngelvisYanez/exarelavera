

(function(){
	'use strict';
	var aju={
		items:   	$('#items'),
		Bod_Cod:    $('#Bod_Cod'),
		Tia_Tra:    $('#Tia_Tra'),
		Tia_Cod:    $('#Tia_Cod'),
		Aju_Fec:    $('#Aju_Fec'),
		Form:       $('#AjusteForm')
	};
	/**
	 * event listeners UI
	 */

	$('#AjusteForm').submit(e=>{
		e.preventDefault();
		let form_before=$('#AjusteForm').data();
		let form_last=$('#AjusteForm').getData();
		let form_data=$.extend(form_before,form_last);
		if(aju.validarFormAjuste()){
			$.createDialogConfirm('&iquest;Est&aacute; seguro que desea Registrar Ajuste?',
				$.extend(form_data,{'saveAjuste':true,'items':$('#items').getGridBatch()}),
				aju.asyncGuardarAjuste);
		}else {
			aju.alertaAuto(`Asigne un Usuario`,'#Usu_Cod_chosen','right_top');
		}
			return false;
	});


	aju.asyncGuardarAjuste=async function(data){
		let resp=await new Promise((res,rej)=>{$.saveDataJson('',data,success=>res(success),err=>rej(err));});
		$('#items').getDataIDs().map((val,ind)=>{$('#items').jqGrid('delRowData',val);});
		$('#Aju_Obs').val('');
	}

	aju.validarFormAjuste=function(){
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

	aju.cargarTia_Cod=function(Tia_Tra){
		return new Promise((resolve,reject)=>{
			$.getDataJson('',{'Tia_Tra':Tia_Tra,'getTipoAjus':true},function(res){
				resolve(res.data);
			},function(err){
				reject(err);
			});
		});		
	}

	aju.cargarBodegas=function(){
		$('#Bod_Cod')
    	.find('option')
    	.remove();
		return new Promise((res,rej)=>{
			$.getDataJson('',{'bodActive':true},function(resp){
				res(resp.data);
			},function(err){
				rej(err);
			});
		});
	}

	aju.initDateComponents=function(){
 		$('.datepicker').createDatePickers({checkAvailability:true,hideMsg:false}).mask('9999-99-99',{placeholder:'_'})
	}

	aju.agregarItems=function(){
		if($('#Bod_Cod').find(':selected').val()*1>0){
			$('#proDialog').dialog('open');
			$.Search('pro');
		}else{
			aju.alertaAuto(`Elija la bodega `,'#Bod_Cod','right_top');
		}
	}

	aju.alertaAuto=function (mensaje,componente,direccion){
	    $(componente).flyout('hide');
	    $(componente).createFlyout(mensaje,{'icon':'exclamation','placement':direccion,'timeDismis':6000});
	    $(componente).flyout('show');
	}	

	aju.selectItem=function(row_select){
		if ($('#items').getDataIDs().indexOf(row_select.id)<0){
			$('#items').jqGrid('addRowData','Pro_Cod',[$.extend(row_select,{'Pro_Can':1})],'last');
			$('#items').jqGrid('editRow',row_select.id);
		}else{
			$('#items').setSelection(row_select.id);
		}
	}
	aju.openItemSelector=function(id){
		index=id; $('#proDialog').dialog('open');
		$.Search('pro');
	}

	aju.styleCant=function(e,obj,opt){
		e.style.textAlign = 'right';  e.placeholder='0';
		$(e).on('keyup',function (){
			if(isNaN(this.value)){ $(this).val('1').focus();   }
			else if (this.value % 1 !== 0){ var dec=String(this.value).split('.'); if(typeof dec[1]!=='undefined'&&dec[1].length>2) this.value=$.toFixed(this.value);  }
		});
	}

	$('#items').createGrid({
		data:[], rowNum: 10000000, height: 'auto', footerrow:true,
		colModel:[
			{name:'select',label:'<i class="glyphicon glyphicon-check"></i>', width:30, align:'center',viewable: false, formatter:'gridButton', formatoptions:{ action:aju.openItemSelector, icon:'check', title:'Seleccionar Item', data:function(o){ return o.index; } }, resizable: false },
			{name:'index',label:'Index', width:20, sorttype:'int',align:'center',hidden:true},
			{name:'Pro_Cod',label:'C&oacute;d.Int.',key:true, width:20, sorttype:'int',align:'center',hidden:true},
			{name:'Pro_Can',label:'Cant.', labelLong:'Cantidad', width:40, align:'right', title:false, editable:true, editoptions:{dataInit:aju.styleCant}},
			{name:'Uni_Des',label:'Uni.', labelLong:'Unidad', width:25, resizable: false },
			{name:'Ite_Lar',label:'Descripci&oacute;n', width:150},
			{name: 'btn_remover',label: '<i class="glyphicon glyphicon-remove"></i>',width: 20, align: 'center', formatter:'gridButton',
				formatoptions:{ action:deleteItem, data:function(o){ return o.Pro_Cod; },
				icon:'remove', type:'danger' }
			}
		]},true,'itemsPager',{view:false}).gridButtonsAdd([
		{caption:'Agregar',buttonicon:'glyphicon glyphicon-plus',class:'a', onClickButton: function(){  aju.agregarItems() } },{},
	]);

	$('#proDialog').createSearchDialog({caption:'Productos',colModel:[
			{ label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, width: 20,align:"center",hidden:false }, 
			{ label:'Unidad',name:'Uni_Des' ,width: 20,align:"center",hidden:false},
			{ label: 'Descripci&oacute;n', name: 'Ite_Lar', width: 110 },                      
			{ label: 'Marca', name: 'Mar_Des', width: 40},            
			{ label: 'Categoria', name: 'Cat_Des', width: 90,align:"center" },
			{ label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:'' } }
		],loadComplete:function(){     	    	
			$('#gbox_proGrid tr td button').map((ind,arr)=>{
	    	    arr.addEventListener('click',function(){
	    	    	aju.selectItem($(arr).data('originaldata'));
	    	    });
    	    }) 
		}},
		{ title:'Producto', options:[{label:'&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;',value:'d'},{label:'&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;',value:'c'}] 
	}); 

	// onchange_bod_origen
	$('#Bod_Cod').on('change',function(e){
		$('#proDialog').find('input[name=Bod_Cod]').val($('#Bod_Cod').find(':selected').val());
	});


	aju.initDateComponents();

	aju.cargarBodegas().then(data=>{data.map((val,ind,arr)=>{
		let opt_bod=$('<option></option>').text(val.Bod_Nom).val(val.Bod_Cod).data(val);
		$('#Bod_Cod').append(opt_bod);		
	});$('#Bod_Cod').trigger('change');
	}).catch(err=>console.log(err));

	$('#Tia_Tra').on('change',function(event){
		$('#Tia_Cod')
    	.find('option')
    	.remove();
		aju.cargarTia_Cod($('#Tia_Tra').val()).then(data=>{
			data.map((obj,ind,arr)=>{
				let option=$('<option></option>').text(obj.Tia_Des).val(obj.Tia_Cod);
				$('#Tia_Cod').append(option);	
			});
		}).catch(msg=>console.log(msg))
	}).trigger('change');



})();

	function deleteItem(index){
		console.log('deleting item...');
		$('#items').jqGrid('delRowData',index*1);
	}