$(document).ready(function () {
	$('#Pro_Gen').on('click',check_generar);
	$('#btn_atras:submit').click(function() { return false; });
	$('#btn_atras').on('click',()=>cambiar_ventana('#modif_producto','#search_producto'));
	$('#formProductMod').on('submit',()=>{$.createDialogConfirm(null,null,saveProduct);return false;});
	$('#Ite_Lar').on('change',()=>validaName($('#Ite_Lar')));
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



	// $.get( "",{typesPrecio:true}, function(response){
	// 	type = JSON.parse(response);
	// 	console.log(type[0]['Tpv_Des']);
	// });

	var type;
	var typesPrecio=true;
	$.ajax({
		url: '' + "?typesPrecio=true",
		type: 'GET',
		datatType: "json",
		async: false,
		data: typesPrecio,
		success: function(response){
			type = JSON.parse(response);
		},
		
	});
	
	if(type[0]){
		var tipo1= type[0]['Tpv_Des'];
		var tipo1Name= type[0]['Tpv_Cod'];
	}else{
		var tipo1='Standar';
		var tipo1Name='Standar';
	}

	if(type[1]){
		var tipo2= type[1]['Tpv_Des'];
		var tipo2Name= type[1]['Tpv_Cod'];
	}else{
		var tipo2='Precio 2';
		var tipo2Name='Precio 2';
	}

	if(type[2]){
		var tipo3= type[2]['Tpv_Des'];
		var tipo3Name= type[2]['Tpv_Cod'];
	}else{
		var tipo3='Precio 3';
		var tipo3Name='Precio 3';
	}

	if(type[3]){
		var tipo4= type[3]['Tpv_Des'];
		var tipo4Name= type[3]['Tpv_Cod'];
	}else{
		var tipo4='Precio 4';
		var tipo4Name='Precio 4';
	}

	if(type[4]){
		var tipo5= type[4]['Tpv_Des'];
		var tipo5Name= type[4]['Tpv_Cod'];
	}else{
		var tipo5='Precio 5';
		var tipo5Name='Precio 5';
	}

	Grid.createGrid({
		colModel: [
			{ label: 'Cod. Int.', name: 'Pro_Cod', width: 20, align: 'left' },
			{ label: 'Categoria', name: 'Cat_Des', width: 55 },		
			{ label: 'Desc. Larga', name: 'Ite_Lar', width: 100, classes:"highlightSearch" },
			{ label: 'Desc. Corta', name: 'Ite_Cor', width: 40 },
			{ label: 'Marca', name: 'Mar_Des', width: 40 },
			{ label: tipo1, name: tipo1Name, width: 20 },
			{ label: tipo2, name: tipo2Name, width: 20 },
			{ label: tipo3, name: tipo3Name, width: 20 },
			{ label: tipo4, name: tipo4Name, width: 20 },
			{ label: tipo5, name: tipo5Name, width: 20 },
			{ label: '&nbsp;', name: 'act0', width: 10, align: 'center',viewable: false, formatter:(cv,opts,cObjt)=>$.getGridButton(cargarDoc,cObjt), title:false }
			
			//{ label: 'P.V.P', name: 'Provee', width: 30  },
		],
		height: 400, caption: ' ', loadonce: true, rowNum: 100000000, pginput: false, pgbuttons: false, pgtext: 'Mostrando {0} Documentos.', sortname: 'Cha_Cod', sortorder: 'asc',
		groupingView: {
			groupField: ['Ubi_Des'], groupColumnShow: [true],
			groupText: ['<div><span style=\'float:right;\'> {1} Item(s)</span> <b> &nbsp;-&nbsp; {0} &nbsp;-&nbsp; </b>  </div>'],
			groupOrder: ['asc'], groupSummary: [false], groupCollapse: false
		}, grouping: false
	}, true, '#gridPager', { refresh: false, view: true })
		.gridButtonsAdd([
			{ caption: 'Exportar', buttonicon: 'glyphicon glyphicon-download', onClickButton: function () { Grid.jqGrid('exportGridExcel', { nombre: 'Productos', hoja: 'HOJA 1' }); } },
			{ caption: 'Imprimir', buttonicon: 'glyphicon glyphicon-print', onClickButton: function () { Grid.jqGrid('printGrid', { nombre: 'Precios-productos', hoja: 'HOJA 1' }); } }
		]);
	var inp	=$('#txt_busqueda');	
	Grid.on('jqGridAfterLoadComplete',function (ev,glc){ Grid.highlightSearch(inp.val().trim()); });	
	//loadData();
});

function importar($scope){
	$scope.uploadFile = function(event){
        var files = event.target.files;
        files[0].name = 'xxxxx'  // change file name 
        $scope.FileName = files[0].name;
        console.log($scope.FileName)
        $http({
            url     : "/UploadFIle",
            method  : "post",
            file    : files[0] // send your file
        }).success(function(response) {
            console.log(response);
        });
    };
}

function selectComp(data){
    Com_Cod=data['Com_Cod'];
    editing=false;
    var editable=noEdit;
    if(editable===true||duplica){
        if((data['Com_Gen']==='A'||data['Doc']!=='')&&!duplica) editable=false;
        else{
            editing=true;
            tipo=(data['Tia_Ini']==='I'?'Ingreso':(data['Tia_Ini']==='E'?'Egreso':'Diario'));
            data['Old_Com_Num']=data['Com_Num'];
            data['Old_Com_Fec']=data['Com_Fec'];
            data['Old_Pec_Cod']=data['Pec_Cod'];
            $('#title_comp').html(tipo);
            updateTiaCod(data['Tia_Ini'],'Tia_Cod_Comp');
            data['proveedor']=data['cliente']=data['Persona'];
            $('#formCompConta').find('.persona').hide();
            $('#formCompConta').find('.persona.'+(data['Tia_Ini']==='I'?'cliente':'proveedor')).show();
            $('#formCompConta').setData(data);           
            dataSend={'loadData':true, /*notDoc:true,*/ 'Com_Cod':data['Com_Cod'],'Cop_Cod':data['Cop_Cod'],'Vet_Cod':data['Vet_Cod']};
            $.get( "",dataSend, function( response ) {
                if(response['success']===true){  
                    $('input[name=Periodo]').val(response['compro']['Periodo']);
                    $('input[name=Pec_Cod]').val(response['compro']['Pec_Cod']); 
                    if(!duplica){
                    $('#Com_Fec').dateLimits(response['compro']['Pec_Fei'],response['compro']['Pec_Fef']);    
                    }
                    $('#Com_Fec').val(response['compro']['Com_Fec']);
                    gridCompAsien.setRowsByIndex(response['compro']['detalle'],'Index');
                    gridCompAsien.startGridEdit().updateGridDiario();     
                    $('#main-panel').moveComp('#modificar-panel').updateGridsSizes();
                 }else{$.alert(response['message']);}                                   
            },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); }); 
        }
    }
    if(editable===false){
        if(data['Doc']!==''){
            $('#doc_type').html(data['Doc']);$('.doc_panel').show();$('.no_doc_panel').hide();
        }else{
            $('.doc_panel').hide();$('.no_doc_panel').show();
        } //console.log(data);
        $('#asientoAutomatico').setData(data,null,'compr');
        $('#Doc_Doc_Num').val(data['Doc_Num']); 

        dataSend={'loadData':true,'Com_Cod':data['Com_Cod'],'Cop_Cod':data['Cop_Cod'],'Vet_Cod':data['Vet_Cod']};
        $.get( "",dataSend, function( response ) {
            if(response['success']===true){   
                compNoEdit.setRows(response['compro']['detalle']);
                $('input[name=Periodo]').val(response['compro']['Periodo']);
                $('input[name=Pec_Cod]').val(response['compro']['Pec_Cod']); 
                if(!duplica){
                $('#Com_Com_Fec').dateLimits(response['compro']['Pec_Fei'],response['compro']['Pec_Fef']);    
                }
                
                $('#Com_Com_Fec').val(response['compro']['Com_Fec']);                    

                if(response['cheques']['conteo']*1===0){
                    $('#btnAlta').attr("disabled", false);
                    $('#btnBaja').attr("disabled", false);
                    $('#btn_agr').hide();  
                }else{
                    if(alta){
                        $('#btnAlta').attr("disabled", true);
                        $.alert('No se puede dar de Alta al Comprobante, tiene cheques asociados');
                    }
                    if(anula){
                        $('#btnBaja').attr("disabled", true);
                        $.alert('No se puede dar de Baja al Comprobante, tiene cheques asociados');
                    }
                    $('#chequesDialog').getDialogGrid().setRows(response['cheques']['detalle']); 
                    $('#btn_agr').show(); 
                }
                if(typeof response['compra']!=='undefined'){
                    $('#doc_panel').setData(response['compra'],null,'compra');
                    docuView.setRowsByIndex(response['compra']['detalle'],'Index');
                }
                if(typeof response['venta']!=='undefined'){
                    $('#doc_panel').setData(response['venta'],null,'venta');
                    docuView.setRowsByIndex(response['venta']['detalle'],'Index');
                }
                $('#main-panel').moveComp('#edit-panel').updateGridsSizes();
            }else{$.alert(response['message']);}                                   
         },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); }); 
    }
}

function loadData() {
	$('#grid').Search('#formProduct', 'prodAjax');
}

function loadType(rowObj){
	$.get( "",{tipo_precio: true}, function( response ) {
        if(response['success']===true){  
            pricesView.setRows(response['rows']);
        }else{
        	$.alert(response['message']);
        }
    },'json').fail(function(error) {
    	console.log("no vale");
    	$.alert("El Servidor ha fallado en responder!"); 
    }).always(function(response){ 
    	console.log(response); 
    });
}

function cargarDoc(rowObj){
	producto.cargarProducto(rowObj);
	producto.cargarIva();
	cambiar_ventana('#search_producto','#modif_producto');
	llenar(rowObj);
}

function llenar (rowObj){
	$.get( "",{precios: rowObj}, function( response ) {
        if(response['success']===true){  
            docuView.setRows(response['rows']);
        }else{
        	$.alert(response['message']);
        }
    },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); }).always(function(response){ console.log(response); });
}

function calcular2()
{
	var Pre_Com = document.getElementById('Pre_Com').value != "" ? document.getElementById('Pre_Com').value : 0;
	var Pre_Por = document.getElementById('Pre_Por').value != "" ? document.getElementById('Pre_Por').value : 0;
	
	var obj = Math.round(((Pre_Com * Pre_Por)/100)*100)/100;
	document.getElementById('Pre_Uti').value = isNaN(obj) ? 0 : obj;
	
	var Pre_Uti = document.getElementById('Pre_Uti').value != "" ? document.getElementById('Pre_Uti').value : 0;
	//obj = Math.round((parseFloat(Pre_Com)+parseFloat(Pre_Uti))*100)/100;
	//document.getElementById('Pre_Pvp1').value = isNaN(obj) ? 0 : obj;
	
	var Pre_Pvp1 = document.getElementById('Pre_Pvp1').value != "" ? document.getElementById('Pre_Pvp1').value : 0;
	var Pre_Dcs = document.getElementById('Pre_Dcs').value != "" ? document.getElementById('Pre_Dcs').value : 0;
	obj = Math.round(((Pre_Pvp1*Pre_Dcs)/100)*100)/100;
	document.getElementById('Pre_Dct').value = isNaN(obj) ? 0 : obj;
	
	var Pre_Dct = document.getElementById('Pre_Dct').value != "" ? document.getElementById('Pre_Dct').value : 0;
	obj = Math.round((parseFloat(Pre_Pvp1)-parseFloat(Pre_Dct))*100)/100;
	document.getElementById('Pre_Tot').value = isNaN(obj) ? 0 : obj;
	
	var Pre_Tot = document.getElementById('Pre_Tot').value != "" ? document.getElementById('Pre_Tot').value: 0;
	obj = Math.round((parseFloat(Pre_Tot)-parseFloat(Pre_Com))*100)/100;
	document.getElementById('Pre_Gan').value = isNaN(obj) ? 0 : obj;

	ColorGanancia();
}

function calcular()
{
	var Pre_Com = document.getElementById('Pre_Com').value != "" ? document.getElementById('Pre_Com').value : 0;
	var Pre_Por = document.getElementById('Pre_Por').value != "" ? document.getElementById('Pre_Por').value : 0;
	
	var obj = Math.round(((Pre_Com * Pre_Por)/100)*100)/100;
	document.getElementById('Pre_Uti').value = isNaN(obj) ? 0 : obj;
	
	var Pre_Uti = document.getElementById('Pre_Uti').value != "" ? document.getElementById('Pre_Uti').value : 0;
	obj = Math.round((parseFloat(Pre_Com)+parseFloat(Pre_Uti))*100)/100;
	document.getElementById('Pre_Pvp1').value = isNaN(obj) ? 0 : obj;
	
	var Pre_Pvp1 = document.getElementById('Pre_Pvp1').value != "" ? document.getElementById('Pre_Pvp1').value : 0;
	var Pre_Dcs = document.getElementById('Pre_Dcs').value != "" ? document.getElementById('Pre_Dcs').value : 0;
	obj = Math.round(((Pre_Pvp1*Pre_Dcs)/100)*100)/100;
	document.getElementById('Pre_Dct').value = isNaN(obj) ? 0 : obj;
	
	var Pre_Dct = document.getElementById('Pre_Dct').value != "" ? document.getElementById('Pre_Dct').value : 0;
	obj = Math.round((parseFloat(Pre_Pvp1)-parseFloat(Pre_Dct))*100)/100;
	document.getElementById('Pre_Tot').value = isNaN(obj) ? 0 : obj;
	
	var Pre_Tot = document.getElementById('Pre_Tot').value != "" ? document.getElementById('Pre_Tot').value: 0;
	obj = Math.round((parseFloat(Pre_Tot)-parseFloat(Pre_Com))*100)/100;
	document.getElementById('Pre_Gan').value = isNaN(obj) ? 0 : obj;
	ColorGanancia();
}


function ColorGanancia()
{
	if(document.getElementById('Pre_Gan').value<0)
	{
		document.getElementById('Pre_Gan').style.color="red";
	}
	else
	{
		document.getElementById('Pre_Gan').style.color="green";
	}
}


/* funciones de agregado de productos */

$(document).ready(function(){
	$('#marcaDialog').createDialog({height:150, width:530, icon:'plus'});
	$('#lineaDialog').createDialog({height:180, width:530, icon:'plus'});
	$('#ubicaDialog').createDialog({height:200, width:530, icon:'plus'});
	$('#categoDialog').createDialog({height:225, width:530, icon:'plus'});
	$('#uploadFile').createDialog({height:155, width:530, icon:'upload'});
	$('#modifDialog').createDialog({height:430, width:700, icon:'pencil'});
	$('#modif_producto').find('#Cat_Cod').createChosen('input-sm', { allow_single_deselect: true });
	$('#search_producto').find('#Cat_Cod').createChosen('input-xs', { allow_single_deselect: true });
	$('#Cat_Cod_chosen').createFlyout('Seleccione una categoria',{placement:'bottom_right'});
	$('#formProductMod').find('#Cat_Rec').createChosen('input-sm');  
	/* $('#Cat_Rec_chosen').createFlyout('Seleccione una categoria',{placement:'bottom_right'}); */
	addActions('marca');
	addActions('linea');
	addActions('ubica');
	addActions('catego');
	addActions('upload');
	addActions('modif');
});         
function changeIva(){ if($('#ChkNet').is(':checked')) updateNeto($('#PreNet').val()); else updateUnitario($('#Pre_Pvp1').val()); }
function resetForm(){ $('#formProduct')[0].reset(); $('#Cat_Cod').trigger('chosen:updated'); $('#Ite_Lar').fieldValid(); }
function saveProduct(){ producto.update_producto($('#formProductMod').getData());producto.saveProducto();}
function saveForm(o){ $.saveDataJson('',$.extend(o,$('#'+o['nameSave']+'Form').getData('save'+o['nameSave'].charAt(0).toUpperCase()+o['nameSave'].slice(1))),function(resp){
	$('#'+resp['select']+'_Cod').append('<option value="'+resp[resp['select']+'_Cod']+'">'+resp[resp['select']+'_Des']+'</option>').val(resp[resp['select']+'_Cod']);
	$('#'+resp['nameSave']+'Dialog').dialog('close');
	if(resp['nameSave']==='catego') $('#Cat_Cod').trigger('chosen:updated');
	return false;
} ); }
function changeUnitario(){  
	if($('#ChkNet').is(':checked')) {                           
		$('#Pre_Pvp1').attr('readonly','readonly');
		$('#PreNet').removeAttr('readonly');
	} else {  
		$('#PreNet').attr('readonly','readonly');
		$('#Pre_Pvp1').removeAttr('readonly');
	}      
	$('#PreNet').val('');$('#Pre_Pvp1').val('');
}
function updateNeto(value){
	var Iva_Por=$('#Iva_Cod option:selected').text().replace('%', '');value='0'+value;
	if(!isNaN(Iva_Por))
		$('#Pre_Pvp1').val(Math.round(10000*parseFloat(value)/(1+(parseFloat(Iva_Por)/100)))/10000);
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

function cambiar_ventana(select_desde,select_hacia){
	$(select_desde).moveComp(select_hacia).updateGridsSizes();
}

/* Valida si genera o no codigo de barra */
function check_generar()
{
					
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
	saveProducto(){
		let prod_act=this.get_obj()
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