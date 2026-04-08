$(function(){
	// crear boton atras en form_bodega
	let btn_atras=$('<button></button>').on('click',function(e){
		e.preventDefault();
		$('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();
		return false;
	}).addClass('black btn btn-sm btn-inverse').prepend('<i class="glyphicon glyphicon-arrow-left"></i> Atras');
	$('#BodegaForm > div:nth-child(2) > div.center').prepend(btn_atras);

	$('#documentoMain').css('visibility','').hide();
    // cargar todas las bodegas

    // inicializar jqGrid de Busqueda de Bodegas
   $('#searchGrid').createGrid({
    	caption: 'Resultado de la Búsqueda', height: 270, datatype: "local",
    	colModel: [
    	{label: 'C&oacute;d. Int.', name: 'Bod_Cod', width: 30, align: "center", key: true},
    	{label: 'Nombre', name: 'Bod_Nom', align: "center",width: 40},
    	{label: 'Direcci&oacute;n', name: 'Bod_Dir', width: 50, align: "center"},
    	{label: 'Responsable', name: 'Bod_Res', width: 120, align: "center"},
    	{label: 'Estado', name: 'Bod_Est', width: 20, formatter: 'estado',align: "center"},
    	{label: '&nbsp;', name: 'act0', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: {action: showBodega, title: 'Editar'}, title: false}
    	]
   }, true, '#searchGridPager', {refresh: true});

   $('#BodegaForm').on('submit_success',function(e){
   	$('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();
		$('#searchGrid').trigger('reloadGrid');
   });


});




function showBodega(data){
	cargarBodega(data.Bod_Cod);
}


async function cargarBodega(cod){
	let data_resp=await getDatosBodega(cod);
	// asignando valores a formulario
	$('#BodegaForm').setData(data_resp);
	$('#BodegaForm').data(data_resp);
	// asignando usuarios
	data_resp.users.map((obj,ind,arr)=>{
		$('#Usu_Cod').find("option[value="+obj.Usu_Cod+"]").prop("selected", true);
	});
	$('#Usu_Cod').trigger("chosen:updated");
	// presentando formulario de edicion
	$('#documentoSearch').moveComp('#documentoMain').updateGridsSizes();
}


function getDatosBodega(id_Bodega){
	return new Promise((res,rej)=>{
		$.getDataJson('',{'Bod_Cod':id_Bodega,'getBodData':true},function(resp){
			res(resp.data);
		},function(err){
			rej(err)
		});
	});
}



