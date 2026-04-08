
function showProductor(productor){
	$('#documentoSearch').moveComp('#documentoMain').updateGridsSizes();
	$('#ProductorForm').data($.extend(productor,{'Ant_Prv_Cod':productor.Prv_Cod})).setData(productor);
	$('#Prv_Con').removeAttr('class').addClass('glyphicon glyphicon-'+(productor['Prv_Con']==='S'?'ok green':'remove blue'));
   $('#Prv_Esp').removeAttr('class').addClass('glyphicon glyphicon-'+(productor['Prv_Esp']==='S'?'ok green':'remove blue'));
}

function showSearch(e){
	$('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();	
}

$(function(){
	$('#ProductorForm').find('.btn-inverse').each((ind,btn)=>btn.addEventListener('click',showSearch));
	var tabla_search=$('#searchGrid');
	// inicializar jqGrid de Busqueda de Bodegas
   	tabla_search.createGrid({
    	caption: 'Resultado de la B&uacute;squeda', height: 270, datatype: "local",
    	colModel: [
    	{label: 'C&oacute;d. Int.', name: 'Prd_Cod', width: 30, align: "center", key: true},
    	{label: 'Nombre', name: 'Prd_Nom', align: "center",width: 40},
    	{label: 'Direcci&oacute;n', name: 'Prs_Dir', width: 50, align: "center"},
    	{label: 'Cod. MAG.', name: 'Prd_Mag', width: 120, align: "center"},
    	{label: 'Estado', name: 'Prd_Est', width: 20, formatter:'estado', align: "center"},
    	{label: '&nbsp;', name: 'act0', width: 20, align: 'center', viewable: false, formatter: 'gridButton', formatoptions: {action: showProductor, title: 'Editar'}, title: false}
    	]
   }, true, '#searchGridPager', {refresh: true});
});

