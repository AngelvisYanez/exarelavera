$(function(){
// creando choosen ajax para busqueda de usuarios
	asyncGetAjaxUsers().then($('#Usu_Cod').chosen());
//  cargar Productores
	getProductores().then(productores=>{
		productores.map(prod=>{
			let option=$(`<option value=${prod.Prd_Cod}>${prod.Prd_Nom}</option>`);
			$('#Prd_Cod').append(option);
		});
	})
// valida envio de datos de Formulario de Bodega
	$("#BodegaForm").submit(function(e){
		e.preventDefault();
		let form_before=$('#BodegaForm').data();
		let form_last=$('#BodegaForm').getData();
		let form_data=$.extend(form_before,form_last);
		if(validarFormBodega()){
			$.createDialogConfirm('¿Est&aacute; seguro que desea guardar una nueva Bodega?',
				$.extend(form_data,{'Usu_Cod':getUsersSelected(),'saveBodega':true}),
				asyncGuardarBodega);

		}else {
			alertaAuto(`Asigne un Usuario`,'#Usu_Cod_chosen','right_top');
		}
	   return false;
	});
});


// funcion asincrona para agrupar promesasde carga de usuarios
async function asyncGetAjaxUsers(){
	try{
		let lista=await getUsers();
		lista.map((val,ind)=>{
			$('#Usu_Cod').append($('<option></option>').attr('value',val.Usu_Cod).text(val.Prs_Ape+' '+val.Prs_Nom +' - '+ val.Prs_Ced ));
		});
		$('#Usu_Cod').trigger("chosen:updated");
	}catch(e){
		console.log(e);
	}	
}

function getProductores(){
	return new Promise((resolve,reject)=>{
		$.getDataJson('',{'getProductores':true},function(res){
			resolve(res.data);
		},function(err){
			reject(err);
		});
	});
}




/**
 * funcion que retorna promesa con lista de usuarios
 * @return {Promise} lista de usuarios de sucursal
 */
function getUsers(){
	return new Promise((res,rej)=>{
		$.getDataJson('',{'usersAjax':true},function(resp){
				res(resp.rows);
			},function(er){
				console.log(er)
				rej(er);
			}	
		);
	}); 
}



/**
 * Funcion asincrona para guardado de Bodegas
 * @param  {dataForm} data json con los campos del formulario de bodega
 * @return {Promise}      resolve de guardado de Bodega
 */
async function asyncGuardarBodega(data){
	try{
		let array_resp=await saveBodega(data)
		console.log(array_resp);
		$('#BodegaForm').setData({});
		$('#Usu_Cod').trigger("chosen:updated");
		$('#BodegaForm').trigger('submit_success');
	}catch(err){
		console.log(err);
		//return 'error en funcion asincrona';
	}
}




function saveBodega(data){
	return new Promise((res,rej)=>{
		$.saveDataJson('',data,success=>res(success),err=>rej(err));
	})
}



function getUsersSelected(){
	let users_bod=[]
	$.each($("#Usu_Cod option:selected"),function(){
		users_bod.push($(this).val());
	});
	return users_bod;
}

function validarFormBodega(){
	return $("#Usu_Cod option:selected").val()===undefined?false:true;
}



function alertaAuto(mensaje,componente,direccion){
    $(componente).flyout('hide');
    $(componente).createFlyout(mensaje,{'icon':'exclamation','placement':direccion,'timeDismis':6000});
    $(componente).flyout('show');
}