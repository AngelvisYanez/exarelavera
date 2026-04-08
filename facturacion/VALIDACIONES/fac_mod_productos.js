$(document).ready(function () {
	$('#Pro_Gen').on('click',check_generar);
	$('#btn_atras:submit').click(function() { return false; });
	$('#btn_atras').on('click',()=>cambiar_ventana('#modif_producto','#search_producto'));
	$('#formProductMod').on('submit',()=>{$.createDialogConfirm(null,null,saveProduct);return false;});
	$('#Ite_Lar').on('change',()=>validaName($('#Ite_Lar')));
	$('#Pro_Bar_Emp').on('change',()=>validaCodigo($('#Pro_Bar_Emp')));
	$('#modif_producto').css('visibility','').hide();
	var Grid = $('#grid');

	

	$('.pagination').find('li a').click(function () {
		$('.pagination').find('li').removeClass('active');
		$(this).parent().addClass('active');
		$('#letra').val($(this).text());
		if ($(this).text() === 'TODOS') $('#txt_busqueda').removeAttr('disabled');
		else $('#txt_busqueda').attr('disabled', 'disabled').val('');
		loadData();
	});
	$('#grupo').change(function () {
		var vl = $(this).val();
		if (vl) { if (vl === 'clear') Grid.jqGrid('groupingRemove', true); else Grid.jqGrid('groupingGroupBy', vl); } //loadData();
	});
	Grid.createGrid({
		colModel: [
			{ label: 'Cod.Int.', name: 'Pro_Cod', key: true, width: 40,hidden: false, viewable: true },
			{ label: 'Cod.Emp', name: 'Cha_Cod', width: 40, align: 'center',hidden: true, sorttype: 'string' },
			{ label: 'Categoria', name: 'Cat_Des', width: 65 },		
			{ label: 'Desc. Larga', name: 'Ite_Lar', width: 100, classes:"highlightSearch" },
			{ label: 'Desc. Corta', name: 'Ite_Cor', width: 40 },
			{ label: 'Detalle', name: 'Pro_Obs', width: 50, classes:"highlightSearch" },
			{ label: 'Marca', name: 'Mar_Des', width: 40 },
			{ label: 'Ubic.', name: 'Ubi_Des', width: 40 },
			{ label: 'Linea', name: 'Lin_Des', width: 40 },
			{ label: 'Pres.', name: 'Pre_Des', width: 40 },
			{ label: 'Unid.', name: 'Uni_Des', width: 30 },
			{ label: 'Aqd.', name: 'Adq_Des', width: 40 },
			{ label: 'M.', name: 'Pro_Uni', width: 20 },
			{ label: 'Stock', name: 'Stk_Can', width: 20 },
			{label:"IVA(%)",name:"Iva_Por",width:30,align:"right"},
			{ label: 'Precio', name: 'Pre_Pvp', width: 30, align: 'right',formatter:'currency' },
			{ label: '&nbsp;', name: 'act0', width: 20, align: 'center',viewable: false, formatter:(cv,opts,cObjt)=>$.getGridButton(cargarDoc,cObjt), title:false }
			
			//{ label: 'P.V.P', name: 'Provee', width: 30  },
		],
		height: 270, caption: ' ', loadonce: true, rowNum: 100000000, pginput: false, pgbuttons: false, pgtext: 'Mostrando {0} Documentos.', sortname: 'Cha_Cod', sortorder: 'asc',
		groupingView: {
			groupField: ['Ubi_Des'], groupColumnShow: [true],
			groupText: ['<div><span style=\'float:right;\'> {1} Item(s)</span> <b> &nbsp;-&nbsp; {0} &nbsp;-&nbsp; </b>  </div>'],
			groupOrder: ['asc'], groupSummary: [false], groupCollapse: false
		}, grouping: false
	}, true, '#gridPager', { refresh: false, view: true })
		.gridButtonsAdd([
			{ caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download', onClickButton: function () { Grid.jqGrid('exportGridExcel', { nombre: 'Productos', hoja: 'HOJA 1' }); } },
			{ caption: 'Imprimir', buttonicon: 'glyphicon glyphicon-print', onClickButton: function () { Grid.jqGrid('printGrid', { nombre: 'Productos', hoja: 'HOJA 1' }); } }
		]);
	var inp	=$('#txt_busqueda');	
	Grid.on('jqGridAfterLoadComplete',function (ev,glc){ Grid.highlightSearch(inp.val().trim()); });	
	//loadData();
});
function loadData() {
	$('#grid').Search('#formProduct', 'prodAjax');
}

function cargarDoc(rowObj){
	producto.cargarProducto(rowObj);
	producto.cargarIva();
	producto.ActivarGenerado(rowObj.Pro_Gen);
	producto.ActivarGeneradoEmpresa(rowObj.Pro_Gen_Emp);
	// console.info(producto);
	cambiar_ventana('#search_producto','#modif_producto');
}


/* funciones de agregado de productos */

$(document).ready(function(){
	$('#marcaDialog').createDialog({height:150, width:530, icon:'plus'});
	$('#lineaDialog').createDialog({height:180, width:530, icon:'plus'});
	$('#ubicaDialog').createDialog({height:200, width:530, icon:'plus'});
	$('#categoDialog').createDialog({height:225, width:530, icon:'plus'});
	$('#modif_producto').find('#Cat_Cod').createChosen('input-sm', { allow_single_deselect: true });
	$('#search_producto').find('#Cat_Cod').createChosen('input-xs', { allow_single_deselect: true });
	$('#Cat_Cod_chosen').createFlyout('Seleccione una categoria',{placement:'bottom_right'});
	$('#formProductMod').find('#Cat_Rec').createChosen('input-sm');  
	/* $('#Cat_Rec_chosen').createFlyout('Seleccione una categoria',{placement:'bottom_right'}); */
	addActions('marca');
	addActions('linea');
	addActions('ubica');
	addActions('catego');
});         
function changeIva(){ if($('#ChkNet').is(':checked')) updateNeto($('#PreNet').val()); else updateUnitario($('#Pre_Pvp').val()); }
function resetForm(){ $('#formProduct')[0].reset(); $('#Cat_Cod').trigger('chosen:updated'); $('#Ite_Lar').fieldValid(); $('#Pro_Bar_Emp').fieldValid();}
function saveProduct(){ producto.update_producto($('#formProductMod').getData());producto.saveProducto();}
function saveForm(o){ $.saveDataJson('',$.extend(o,$('#'+o['nameSave']+'Form').getData('save'+o['nameSave'].charAt(0).toUpperCase()+o['nameSave'].slice(1))),function(resp){
	$('#'+resp['select']+'_Cod').append('<option value="'+resp[resp['select']+'_Cod']+'">'+resp[resp['select']+'_Des']+'</option>').val(resp[resp['select']+'_Cod']);
	$('#'+resp['nameSave']+'Dialog').dialog('close');
	if(resp['nameSave']==='catego') $('#Cat_Cod').trigger('chosen:updated');
	return false;
} ); }
function changeUnitario(){  
	if($('#ChkNet').is(':checked')) {                           
		$('#Pre_Pvp').attr('readonly','readonly');
		$('#PreNet').removeAttr('readonly');
	} else {  
		$('#PreNet').attr('readonly','readonly');
		$('#Pre_Pvp').removeAttr('readonly');
	}      
	$('#PreNet').val('');$('#Pre_Pvp').val('');
}
function updateNeto(value){
	var Iva_Por=$('#Iva_Cod option:selected').text().replace('%', '');value='0'+value;
	if(!isNaN(Iva_Por))
		$('#Pre_Pvp').val(Math.round(10000*parseFloat(value)/(1+(parseFloat(Iva_Por)/100)))/10000);
}
function updateUnitario(value){
	var Iva_Por=$('#Iva_Cod option:selected').text().replace('%', '');value='0'+value;
	if(!isNaN(Iva_Por))
		$('#PreNet').val(Math.round(10000*(parseFloat(value)+parseFloat(value)*((parseFloat(Iva_Por)/100))))/10000);
}
function addActions(name){
	$('#'+name+'Form').append('<div class="form-group" style="padding-top:10px;"><label class="col-sm-4 control-label"></label><div class="col-sm-8">'+
               '<button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>'+
               '<button type="button" onclick="$(\'#'+name+'Dialog\').dialog(\'close\');"  class="btn btn-danger"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>'+
           '</div></div><div class="form-group Titulos2"><div class="col-md-12"><hr/><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.</div></div>');
}
function validaName($el){
	if($el.val()===''){ $el.fieldValid(''); return; }
	$el.getValidationJson('',{'validaIteLar':true, 'Ite_Lar':$el.val(),'Pro_Cod':producto.get_obj().Pro_Cod});
}

function validaCodigo($el){
    if($el.val()===''){ $el.fieldValid(''); return; }
    $el.getValidationJson('',{validaCodEmp:true, Pro_Cod_Emp:$el.val()});
}


function cambiar_ventana(select_desde,select_hacia){
	$(select_desde).moveComp(select_hacia).updateGridsSizes();
}

/* Valida si genera o no codigo de barra */
function check_generar()
{
	if(document.getElementById('Pro_Gen').checked==true)
	{
		document.getElementById('Pro_Gen').value=1;
		document.getElementById('contenedorcheck').innerHTML='Genera el c&oacutedigo del producto';
		document.getElementById('Pro_Bar').disabled=true;
	}
	else
	{ 
		document.getElementById('Pro_Gen').value=0;
		document.getElementById('Pro_Bar').disabled=false;		
		document.getElementById('contenedorcheck').innerHTML='Ingrese el c&oacutedigo de barra del producto';	
	}				
}

function check_generar_empresa()
{
	if(document.getElementById('Pro_Gen_Emp').checked==true)
	{
		document.getElementById('Pro_Gen_Emp').value=1;
		document.getElementById('contenedorcheckempresa').innerHTML="Genera el codigo de empresa del producto";
		document.getElementById('Pro_Bar_Emp').disabled=true;
	}
	else
	{ 
		document.getElementById('Pro_Gen_Emp').value=0;
		document.getElementById('Pro_Bar_Emp').disabled=false;		
		document.getElementById('contenedorcheckempresa').innerHTML="Ingrese el codigo de empresa del producto";		
	}				
}


var producto={
	produc:null,
	get_obj(){
		return  this.produc;
	},
	cargarProducto(prod){
		this.produc=prod;
		$('#ChkNet').attr('checked',false).trigger('change')
		$('#formProductMod').setData(prod);
		$('#Ite_Lar').trigger('change');
		$('#Pro_Bar_Emp').trigger('change');
		$('#modif_producto').find('#Cat_Cod').trigger('chosen:updated');
	},cargarIva(){
		if($('#Iva_Cod').val()===null)
			$('#Iva_Cod').val($('#Iva_Cod').find(`option:contains('${this.get_obj().Iva_Por}')`).val());
	},
	update_producto(prod){
		if(prod.Ice_Int*1===0){prod.Ice_Int='NULL';}
		this.produc=$.extend(this.produc,prod);
	},
	ActivarGenerado(char_gen){
		char_gen==='G'
		?$('#Pro_Gen').attr('checked',true).trigger('change')
		:$('#Pro_Gen').attr('checked',false).trigger('change');
		check_generar();
	},
	ActivarGeneradoEmpresa(char_gen){
		char_gen==='G'
		?$('#Pro_Gen_Emp').attr('checked',true).trigger('change')
		:$('#Pro_Gen_Emp').attr('checked',false).trigger('change');
		check_generar_empresa();
	},
	saveProducto(){
		let prod_act=this.get_obj()
		prod_act.Pro_Gen_Emp=prod_act.Pro_Gen_Emp*1===0?'M':'G'
		prod_act.Pro_Gen=prod_act.Pro_Gen*1===0?'M':'G'
		$.saveDataJson('',$.extend(prod_act,{'updateProd':true}),
			(msg_success)=>{
				loadData();
				cambiar_ventana('#modif_producto','#search_producto');
			},
			(msg_error)=>console.log(msg_error)
			);
		
	}
};



