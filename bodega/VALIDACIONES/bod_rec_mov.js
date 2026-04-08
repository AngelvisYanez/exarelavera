(function(){
	'use strict';
	var rec_mov={
		items:$('#items'),
		tia_cod:$('#Tia_Cod')
	};

  /*****************************************************************************
   *
   * Event listeners for UI elements
   *
   ****************************************************************************/

	// on load UI
	$(function() {
		$('#MovimientoForm').submit(e=>{
			e.preventDefault();
			let form_before=$('#MovimientoForm').data();
			let form_last=$('#MovimientoForm').getData();
			let form_data=$.extend(form_before,form_last);
			if(rec_mov.validarFormMovimiento()){
				let items_mov=$('#items').getGridBatch();
				$.createDialogConfirm(`${function(){
					let alert=false;
					let send_items=$('#MovimientoForm').data('send_items');
					send_items.map((val,ind,arr)=>{
						if (send_items[ind].Pro_Can*1!==items_mov[ind].Pro_Can*1) {
							alert=true;		
						}
					});
					form_data.alert=alert;
					return alert;
				}()?'<b>Existen Novedades con Cantidades de Items Recibidos</b></br>':''}&iquest;Est&aacute; seguro que desea Registrar este Movimiento?`,
				$.extend(form_data,{'saveMovimiento':true,'items':items_mov}),
				rec_mov.asyncGuardarMovimiento);
				$('#items').getDataIDs().map(val=>$('#items').jqGrid('editRow',val));
			}else {
				rec_mov.alertaAuto(`Asigne un Usuario`,'#Usu_Cod_chosen','right_top');
			}
			return false;
		});


		rec_mov.initDateComponents();

		// add button back in main Interface movimiento
		let btn_atras=$('<button></button>').on('click',function(e){e.preventDefault();rec_mov.atrasAction();return false;
		}).addClass('black btn btn-sm btn-inverse').prepend('<i class="glyphicon glyphicon-arrow-left"></i> Atras');
		$('#MovimientoForm > div:nth-child(2) > div.center').prepend(btn_atras);

		rec_mov.cargarTiaCod().then(data=>{
			data.map((obj,ind,arr)=>{
				let option=$('<option></option>').text(obj.Tia_Des).val(obj.Tia_Cod);
				$('#Tia_Cod').append(option);	
			});
		}).catch((err)=>console.log(err));

		$('#items').createGrid({
			data:[], rowNum: 10000000, height: 'auto', footerrow:true,
			colModel:[
			{name:'index',label:'Index', width:20, sorttype:'int',align:'center',hidden:true},
			{name:'Pro_Cod',label:'C&oacute;d.Int.',key:true, width:20, sorttype:'int',align:'center',hidden:true},
			{name:'Pro_Can',label:'Cant.', labelLong:'Cantidad', width:40, align:'right', title:false, editable:true, editoptions:{dataInit:styleCant}},
			{name:'Uni_Des',label:'Uni.', labelLong:'Unidad', width:25, resizable: false },
			{name:'Ite_Lar',label:'Descripci&oacute;n', width:150},
			]},true,'itemsPager',{view:false});

		// inicializar jqGrid de Busqueda de Movimientos
		$('#searchGrid').createGrid({
			caption: 'Resultado de la B&uacute;squeda', height: 270, datatype: "local",
			colModel: [
			{label: 'C&oacute;d. Int.', name: 'Mov_Cod', width: 30, align: "center", key: true},
			{label: 'Fecha', name: 'Mov_Fec', align: "center",width: 40},
			{label: 'Origen_cod', name: 'Bod_Ori', width: 50, align: "center" ,hidden:true},
			{label: 'Origen', name: 'origen', width: 50, align: "center",formatter:rec_mov.formatterOrigen, unformat:rec_mov.unformatterPrincipal},
			{label: 'Destino', name: 'destino', width: 50, align: "center" ,formatter:rec_mov.formatterDestino, unformat:rec_mov.formatterPrincipal},
			{label: 'Destino_cod', name: 'Bod_Des', width: 120, align: "center", hidden:true},
			{label: 'Estado', name: 'Mov_Est', width: 20, formatter: function(cv, opts, rObj){ return cv==='F'?'Finalizado':'En Espera'; } ,align: "center"},
			{label: '&nbsp;', name: 'act0', width: 20, align: 'center', viewable: true, formatter: 'gridButton',formatoptions:{action:rec_mov.showMovimiento, title:'Ver Documento', icon:'arrow-right', type:'success' } , title: false}
			],
			loadComplete: function(data){   
				$('#searchGrid tr td button').map((ind,arr)=>{
					let data_arr=$(arr).data('originaldata');
					if (data_arr.Mov_Est!=='F')
						arr.addEventListener('click',()=>rec_mov.showMovimiento(data_arr))
					else if (data_arr.Mov_Ale*1) {
						$(arr).parent().html('<span class="glyphicon glyphicon-alert red" ></span>').attr('title','Novedades Con items Recibidos');
					} else {
						$(arr).parent().html('<span class="glyphicon glyphicon-check green"></span>')

					}
				});
			}
		}, true, '#searchGridPager', {refresh: true});

		$('#documentoMain').css('visibility','').hide();
	});





  /*****************************************************************************
   *
   * Methods to update/refresh the UI
   *
   ****************************************************************************/

  rec_mov.formatterOrigen=function(cell,option,row){
   	let color='';
   	if (row.tipo_origen==='P') {
   		color='style="color:green;"';
   	}
   	return`<span ${color}>${cell}</span>`;
  }

  rec_mov.formatterDestino=function(cell,option,row){
   	let color='';
   	if (row.tipo_destino==='P') {
   		color='style="color:green;"';
   	}
		return `<span ${color}>${cell}</span>`;
  }

  rec_mov.unformatterPrincipal=function(cell,options){
   	return $(cell).textContent();
  }

  rec_mov.showMovimiento=function(row){
   	$('#MovimientoForm').data({});
   	row.Aju_Fec=row.Mov_Fec;
   	$('#MovimientoForm').setData(row,false);
   	$('#MovimientoForm').data(row);
   	rec_mov.cargarItemsMov(row.Mov_Cod);
   	$('#documentoSearch').moveComp('#documentoMain').updateGridsSizes();
   	$('#Bod_Ori')
   	.find('option')
   	.remove()
   	.end()
   	.append(`<option value=${row.Bod_Ori*1}>${row.origen}</option>`)
   	.val(row.Bod_Ori*1);

   	$('#Bod_Des')
   	.find('option')
   	.remove()
   	.end()
   	.append(`<option value=${row.Bod_Des*1}>${row.destino}</option>`)
   	.val(row.Bod_Des*1);
   };


   rec_mov.atrasAction=function(){
   	$('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();
   };




   rec_mov.asyncGuardarMovimiento=async function (data){
   	let resp=await new Promise((res,rej)=>{$.saveDataJson('',data,success=>res(success),err=>rej(err));});
   	$('#items').getDataIDs().map((val,ind)=>{$('#items').jqGrid('delRowData',val);});
   	$('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();
   	$('#searchGrid').trigger('reloadGrid');

   };



   rec_mov.cargarTiaCod=function (tipo='E'){
   	return new Promise((resolve,reject)=>{
   		$.getDataJson('',{'Tia_Tra':tipo,'getTipoAjus':true},function(res){
   			resolve(res.data);
   		},function(err){
   			reject(err);
   		});
   	});
   };



   rec_mov.cargarItemsMov=async function(Mov_Cod){
   	$('#items').getDataIDs().map((val,ind)=>{$('#items').jqGrid('delRowData',val);});
   	let items= await new Promise((resolve,reject)=>{
   		$.getDataJson('',{'Mov_Cod':Mov_Cod,'getItems':true},res=>{resolve(res.data)},err=>{reject(err)});
   	});
   	$('#MovimientoForm').data('send_items',items);
   	$('#items').jqGrid('addRowData','Pro_Cod',items,'last');
   	$('#items').getDataIDs().map(val=>$('#items').jqGrid('editRow',val));

   }

   rec_mov.initDateComponents=function(){
   	$('.datepicker').createDatePickers({checkAvailability:true,hideMsg:false}).mask('9999-99-99',{placeholder:'_'})
   }

//Estilo para cantidad
function styleCant(e,obj,opt){
	e.style.textAlign = 'right';  e.placeholder='0';
	$(e).on('keyup',function (){
		if(isNaN(this.value)){ $(this).val('1').focus();   }
		else if (this.value % 1 !== 0){ var dec=String(this.value).split('.'); if(typeof dec[1]!=='undefined'&&dec[1].length>2) this.value=$.toFixed(this.value);  }
	});
}

/**
 * popup de alerta utilizado para validaciones
 * @param  {string} mensaje    descripccion
 * @param  {string} componente asociado a popup
 * @param  {string} direccion  direccion de popup con respecto a componente
 * @return {void}         
 */
 rec_mov.alertaAuto=function (mensaje,componente,direccion){
 	$(componente).flyout('hide');
 	$(componente).createFlyout(mensaje,{'icon':'exclamation','placement':direccion,'timeDismis':6000});
 	$(componente).flyout('show');
 }
/**
 * verifica que todo este en orden antes de generar un movimiento
 * @return {bool} verificacion
 */
 rec_mov.validarFormMovimiento=function(){
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


})();
