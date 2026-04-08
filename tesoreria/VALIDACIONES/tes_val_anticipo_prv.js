/*function onload*/
$(function () {

	//Inicio de if para crear cuentas (Agregar otros a proveedores)
	if ($('#cuentasDialog').length === 1) {
		$.createSearchDialog('cuentasDialog', [
			{ label: 'Cod. Int.', name: 'Pld_Cod', width: 20, align: "left" },
			{ label: 'C&oacute;digo', name: 'Pld_Cdc', width: 50, align: "left" },
			{ label: 'Cuenta Contable', name: 'Pld_Des', width: 100, align: "left" },
			{
				label: '&nbsp;',
				name: 'plsel',
				width: 15,
				align: 'center',
				viewable: false,
				title: false,
				formatter: function (cellvalue, options, rowObject) {
					return $.getGridButton(cambiarCuenta, rowObject, 'Seleccionar cuenta', 'check', '', 'success');
				}
			}
		], null, null, null, null, { title: 'Cuenta', options: [{ label: '&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;', value: 'd' }, { label: '&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;', value: 'c' }] })
			.find('.form-group-options').append('<div class="col-md-4"> <label class="control-label label-xs">Plan de Cuentas:</label><input name="Periodo" type="text" size="6" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /> <input name="Pec_Cod" type="hidden" /><input name="Asi_Cod" type="hidden" /></div>');
	} //fin de cuadro de dialogo para crear cuentas




	$( "#successDialog" ).createDialog({width:500,height:355,icon:'print'});
	$( "#verPagosDialog" ).createDialog({width:400,height:270,icon:'info-sign'});

	$.post( "", {obtenerPeriodoMinMax:true}, function( responce ) {
		if(responce['success']===true){
			$("#Atp_Fec").dateLimits(responce['data']['minimo'],responce['data']['maximo']);
		}else{
			console.log(responce['message']);
		}
	},'json')
	.fail(function(error) {
		console.log("El Servidor ha fallado en responder!");
	});

	// inicializa componentes de fecha en formulario de anticipos
	$('.datepicker').createDatePickers({checkAvailability:true,hideMsg:false}).mask('9999-99-99',{placeholder:'_'});

	// inicializa el dialog de registrar pagos de anticipo
	$('#pagosDialog').createDialog({height:325,icon:'usd'});
		$('#pagos').createGrid({viewrecords:false,
			data:[], rowNum: 10000000, height: 150, footerrow:true,
			onSelectRow: function(rowid, e) { $(this).resetSelection();},
			colModel:[



				{//Nuevo método para agregar la opcion otros en anticipos
					label: '<center><span class="glyphicon glyphicon-check"></span></center>',
					name: 'sel_item',
					width: 5,
					align: 'center',
					viewable: false,
					formatter: function (cellvalue, options, rowObject) {
						if (rowObject.Pag_Abr === 'OTR') {
							return $.getGridButton(AbrirCuentas, rowObject, 'Cambiar cuenta', 'check', '', 'success');
						} else {
							return "-";
						}
					},
					title: false
				},//fin del nuevo método





				{ label: 'index', name: 'index',hidden:true, classes:'bgNoRight' },
				{ label: ' ', name: 'Det_Tip', hidden:true },
				{ label: ' ', name: 'grid_tipp', hidden:true },
				{ label: 'Pag_Cod', name: 'Pag_Cod',hidden:true, classes:'bgNoRight' },
				{ label: 'Pag_Abr', name: 'Pag_Abr',hidden:true, classes:'bgNoRight' },
				{ label: 'Tipo', name: 'Pag_Des', width: 10, align:"center", classes:'bgNoRight'},
				{ label: 'Ban_Cod', name: 'Ban_Cod',hidden:true, classes:'bgNoRight' },
				{ label: 'Che_Num', name: 'Che_Num',hidden:true, classes:'bgNoRight' },
				{ label: 'Che_Fec', name: 'Che_Fec',hidden:true, classes:'bgNoRight' },
				{ label: 'Pap_Cto', name: 'Pap_Cto',hidden:true, classes:'bgNoRight' },
				{ label: 'Pap_Ctd', name: 'Pap_Ctd',hidden:true, classes:'bgNoRight' },
				{ label: 'Cuenta_Pld', name: 'Pld_Cod', width: 30,hidden:true, classes:'bgNoRight' },
				{ label: 'C&oacute;digo', name: 'Pld_Cdc', width: 10, classes:'bgNoRight'},
				{ label: 'Cuenta', name: 'Pld_Des', width: 30, classes:'bgNoRight'},
				{ label: 'Glosa', name: 'Glosa', width: 20,editable:true },
				{ label: 'Debe', name: 'Debe', width: 10, align: 'right', formatter:'currency', editable:true,
						 formatoptions: { defaultValue:'' },
						 editoptions: { dataInit: function (element) { $(this).createInputDiario3(element,"D","Det_Tip");} }
				},
				{ label: 'Haber', name: 'Haber', width: 10,align: 'right', formatter:'currency', editable:true,
						 formatoptions: { defaultValue:'' },
						 editoptions: { dataInit: function (element) { $(this).createInputDiario3(element,"H","Det_Tip");} }
				},
				{ label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
					name: 'Pag_Item', width: 10, align: 'center', viewable: false,
					formatter:function (cellvalue, options, rowObject) {
						if (rowObject.grid_tipp==='inicial') {
							return "-";
						}else {
							return $.getGridButton(mostrarPago, rowObject, 'Ver pago', 'info-sign','','info')+"&nbsp;"+
							$.getGridButton(borrarPago, rowObject, 'Borrar pago', 'remove','','danger');;
						}
					},
					title: false
				}
			], loadComplete:function(){
					// $(this).setGridSummary(['Debe'],{Pag_Des:'<div style="text-align:right;">TOTAL:</div>'});
					$(this).jqGrid('footerData', 'set', {
            Glosa:"<div style='text-align:right;'>TOTALES:</div>",
            Debe: $(this).jqGrid('getCol', 'Debe', true, 'sum'),
            Haber: $(this).jqGrid('getCol', 'Haber', true, 'sum')
          },true);
				}
		},true,'pagosPager',{view:false}).gridButtonsAdd([
			{caption:'Agregar',buttonicon:'glyphicon glyphicon-plus',class:'a', onClickButton: function(){   if($('#Val_Pcc_2').val()*1<=0){ $.alert('El saldo a cobrar es cero!'); return; } openDialogPagos(); } }
		]);
		$.clearFooterDiario("#pagos");
		$('#pagos').updateGridDiario();

    //Dialog buscar clientes
    $.createSearchDialog('proveedoresDialog',[
       	{ label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 15,align:"center",hidden:true },
       	{ label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
       	{ label: 'Proveedor', name: 'nombre', width: 100},
       	{ label: 'Direcc.', name: 'Prs_Dir', width: 60 },
       	{ label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:selectProveedor} }
   	],null,null,null,{headertitles:true},{ title:'Proveedor', text:'searchPrv' });

    // $('#Ant_Val').numFormat();
    // Dialog create cliente
    $('#proveedoresCreateDialog').createDialog({icon:'plus', width:500, height:430});
    $('#For_Cod').val(1).trigger('change');
});

//insertamos una fila con un asiento por defecto en la grid
function cargarReginicial(){
	var data = new Object();
	data['tipo'] = 'INICIAL';
	data['cargar_cuentas_pagos'] = true;
	var ids_pagos= $('#pagos').jqGrid('getDataIDs').length+1;
	$.post( "", data, function( responce ) {
		if(responce['success']===true){
			if(responce['bandera']==true){
				$('#pagos').jqGrid('addRowData', ids_pagos,
				{
					index:ids_pagos,
					grid_tipp:'inicial',
					Pag_Cod:'',
					Pag_Abr:'',
					Pag_Des:'-',
					Pld_Des:responce['data'].Pld_Des,
					Pld_Cdc:responce['data'].Pld_Cdc,
					Ban_Cod:'',
					Che_Num:'',
					Che_Fec:'',
					Pap_Ctd:"",
					Pap_Cto:'',
					Pld_Cod:'',
					Det_Tip:'D',
					Glosa:'Anticipo a proveedores',
					Debe:'0.00',
					Haber:'',
					Pag_Item:""
				},"first");
				$('#pagos').startGridEdit();
				$('#pagos').updateGridDiario();
				$("#save_bnd").val("s");
			} else {
				$.alert("NO EXISTE UNA CUENTA PARAMETRIZADA PARA "+responce['message']);
				$("#save_bnd").val("n");
			}
		}else{
			console.log(responce['message']);
		}
	},'json')
	.fail(function(error) {
		console.log("El Servidor ha fallado en responder!");
	});
}

/**
 * cargar clientes
 * @param  {object} proveedor row seleccionada del dialogo de proveedores
 * @return {void}
 */
function selectProveedor(proveedor){
		limpiarFormAnticipos();
		$("#bandera_prov").val("sel");
		$("#Atp_Obs").val("ANTICIPO A PROVEEDOR - "+ proveedor.nombre);
   	$('#AnticipoPrvForm').setData($.extend(proveedor,{op_opciones:'c'}),false);
   	$('#proveedoresDialog').dialog('close');
		cargarReginicial();
}

/**
 * abrir dialog de pagos
 * @return {void}
 */
function openDialogPagos(){
	// console.log(window.location.href); $("#save_bnd").val("n");
	if($("#bandera_prov").val()!=="sel"){return $.alert("Primero debe seleccionar un proveedor!");}
	if($("#save_bnd").val()!=="s"){return $.alert("Primero Verifique que esten parametrizadas las cuentas necesarias!");}
	$('#Pag_Cod').trigger('change');
	$('#pagosDialog').dialog('open');
	$('#pagosForm').removeData();
}

//muestra los datos del pago ingresado
function mostrarPago(row){
	$('#verPagosForm').children().not(':first,:last').addClass('hidden');
	$('#verPagosForm').find('.'+row.Pag_Des).removeClass('hidden');
	$('#verPagosForm').find('.'+row.Pag_Des).find('.form-control').prop('required',true);

	$("#pago_ver").val(row.Pag_Des);
	$("#cuenta_ver").val(row.Pap_Cto);
	$("#destino_ver").val(row.Pap_Ctd);
	var glosa = row.Glosa || "";
	var match = glosa.match(/Doc\. #(\S+)/);
	$("#Num_DocPv").val(match ? match[1] : "");
	$("#fecha_ver").val(row.Che_Fec);
	$("#numero_ver").val(row.Che_Num);
	$("#valor_ver").val("$ "+row.Haber);

	$('#verPagosDialog').dialog('open');
}

var numeroCheque=false;
//verifica si el numero de un cheque ya se encuentra registrado
function verificarNoCheque(valor){
	datach ={"verificarCheNum":true,"Che_Num":valor,"Ban_Cod":$("#Ban_Cod option:selected").attr("value")};
	$.post( "",datach, function( response ) {
		if (response['numero_che'] === true) {
			$("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
			$("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
			$("#indicadorChe").addClass("red glyphicon glyphicon-remove");
			numeroCheque=true;
		} else {
			numeroCheque=false;
			if(valor===""){
				$("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
				$("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
				$("#indicadorChe").addClass("red glyphicon glyphicon-remove");
			}else{
				$("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
				$("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
				$("#indicadorChe").addClass("green glyphicon glyphicon-ok");
			}
		}
	},'json');
}

// valida que los campos no esten vacios dependiendo del tipo de pago seleccionado
function validarPagosForm(tipo){
	var bandera_pagos=false;
	if(tipo==="Efectivo"){
		if($("#Pap_Val").val()!=""){
			bandera_pagos=false;
		}else{
			bandera_pagos=true;
		}
	}
	if(tipo==="Deposito" || tipo==="Transferencia"){
		if($("#Pap_Val").val()!="" && $("#Pap_Ctd").val()!=""){
			bandera_pagos=false;
		}else{
			bandera_pagos=true;
		}
	}
	if(tipo==="Cheque"){
		if($("#Pap_Val").val()!="" && $("#Che_Num").val()!=""){
			bandera_pagos=false;
		}else{
			bandera_pagos=true;
		}
	}
	return bandera_pagos;
}

//agrega los datos del pago ingresado
function AgregarPago(){
	var bandera_cheque_pago=false;
	if($("#Pag_Cod option:selected").attr("data-abr")=="CHE"){
		criterio = $('#pagos').jqGrid("getCol", "Che_Num", false);
		for(i=0; i < criterio.length; i++){
			if($("#Che_Num").val()==criterio[i]){
				bandera_cheque_pago=true;
			}
		}
	}
	if(bandera_cheque_pago===false){
		if(!(validarPagosForm($("#Pag_Cod option:selected").text()))){
			var total=parseFloat($("#Atp_Val").val());
			total+=parseFloat($("#Pap_Val").val());
			if(numeroCheque===false){
				var ids_pagos= $('#pagos').jqGrid('getDataIDs').length+1;
				if($("#Pag_Cod option:selected").attr("data-abr")=="EFE"){
					if($("#pagos td:contains('EFE')").text().search("EFE")==-1){
						$("#Atp_Val").val(total.toFixed(2));
						$("#1_Debe").val(total.toFixed(2));
						$('#pagos').jqGrid('addRowData', ids_pagos,
						{
							index:ids_pagos,
							grid_tipp:'pago',
							Pag_Cod:$("#Pag_Cod").val(),
							Pag_Abr:$("#Pag_Cod option:selected").attr("data-abr"),
							Pag_Des:$("#Pag_Cod option:selected").text(),
							Pld_Des:$("#Ban_Cod option:selected").attr("data-des"),
							Pld_Cdc:$("#Ban_Cod option:selected").attr("data-cdc"),

							Pap_Ctd:$("#Pap_Ctd").val(),
							Ban_Cod:$("#Ban_Cod option:selected").attr("value"),
							Che_Num:$("#Che_Num").val(),
							Che_Fec:$("#Che_Fec").val(),
							Pap_Cto:'',
							Pld_Cod:$("#Ban_Cod option:selected").attr("data-pla"),
							Det_Tip:'H',
							Glosa:'Ant. prov. '+$("#Pag_Cod option:selected").text(),
							Debe:'',
							Haber:parseFloat($("#Pap_Val").val()),
							Pag_Item:""
						},"last");
						$('#pagos').startGridEdit();
						$('#pagos').updateGridDiario();
					}
				}else if($("#Pag_Cod option:selected").attr("data-abr")=="CHE"){
					$("#Atp_Val").val(total.toFixed(2));
					$("#1_Debe").val(total.toFixed(2));
					$('#pagos').jqGrid('addRowData', ids_pagos,
					{
						index:ids_pagos,
						grid_tipp:'pago',
						Pag_Cod:$("#Pag_Cod").val(),
						Pag_Abr:$("#Pag_Cod option:selected").attr("data-abr"),
						Pag_Des:$("#Pag_Cod option:selected").text(),
						Pld_Des:$("#Ban_Cod option:selected").attr("data-des"),
						Pld_Cdc:$("#Ban_Cod option:selected").attr("data-cdc"),
						Pap_Ctd:$("#Pap_Ctd").val(),
						Ban_Cod:$("#Ban_Cod option:selected").attr("value"),
						Che_Num:$("#Che_Num").val(),
						Che_Fec:$("#Che_Fec").val(),
						Pap_Cto:$("#Ban_Cod option:selected").attr("data-cue"),
						Pld_Cod:$("#Ban_Cod option:selected").attr("data-pla"),
						Det_Tip:'H',
						Glosa:$("#Pag_Cod option:selected").text()+" NO. "+$("#Che_Num").val(),
						Debe:'',
						Haber:parseFloat($("#Pap_Val").val()),
						Pag_Item:""
					},"last");
					$('#pagos').startGridEdit();
					$('#pagos').updateGridDiario();
				} else if($("#Pag_Cod option:selected").attr("data-abr")=="TRF"){
					$("#Atp_Val").val(total.toFixed(2));
					$("#1_Debe").val(total.toFixed(2));
					var glosaText = 'Ant. prov. ' + $("#Pag_Cod option:selected").text();
					if ($("#Num_Doc").length && $("#Num_Doc").val() && $("#Num_Doc").val().trim() !== "") {
						glosaText += ". Doc. #" + $("#Num_Doc").val();
					}
					
					$('#pagos').jqGrid('addRowData', ids_pagos,
					{ index: ids_pagos,
						grid_tipp: 'pago',
						Pag_Cod: $("#Pag_Cod").val(),
						Pag_Abr: $("#Pag_Cod option:selected").attr("data-abr"),
						Pag_Des: $("#Pag_Cod option:selected").text(),
						Pld_Des: $("#Ban_Cod option:selected").attr("data-des"),
						Pld_Cdc: $("#Ban_Cod option:selected").attr("data-cdc"),
						Pap_Ctd: $("#Pap_Ctd").val(),
						Ban_Cod: $("#Ban_Cod option:selected").attr("value"),
						Che_Num: $("#Che_Num").val(),
						Che_Fec: $("#Che_Fec").val(),
						Pap_Cto: $("#Ban_Cod option:selected").attr("data-cue"),
						Pld_Cod: $("#Ban_Cod option:selected").attr("data-pla"),
						Det_Tip: 'H',
						Glosa: glosaText,
						Debe: '',
						Haber: parseFloat($("#Pap_Val").val()),
						Pag_Item: ""
					}, "last");
					$('#pagos').startGridEdit();
					$('#pagos').updateGridDiario();
					$('#pagosDialog').dialog('close');
				}else {
					$("#Atp_Val").val(total.toFixed(2));
					$("#1_Debe").val(total.toFixed(2));
					$('#pagos').jqGrid('addRowData', ids_pagos,
					{
						index:ids_pagos,
						grid_tipp:'pago',
						Pag_Cod:$("#Pag_Cod").val(),
						Pag_Abr:$("#Pag_Cod option:selected").attr("data-abr"),
						Pag_Des:$("#Pag_Cod option:selected").text(),
						Pld_Des:$("#Ban_Cod option:selected").attr("data-des"),
						Pld_Cdc:$("#Ban_Cod option:selected").attr("data-cdc"),
						Pap_Ctd:$("#Pap_Ctd").val(),
						Ban_Cod:$("#Ban_Cod option:selected").attr("value"),
						Che_Num:$("#Che_Num").val(),
						Che_Fec:$("#Che_Fec").val(),
						Pap_Cto:$("#Ban_Cod option:selected").attr("data-cue"),
						Pld_Cod:$("#Ban_Cod option:selected").attr("data-pla"),
						Det_Tip:'H',
						Glosa:'Ant. prov. '+$("#Pag_Cod option:selected").text(),
						Debe:'',
						Haber:parseFloat($("#Pap_Val").val()),
						Pag_Item:""
					},"last");
					$('#pagos').startGridEdit();
					$('#pagos').updateGridDiario();
				}
			}else{
				$.alert("El numero de cheque ("+$("#Che_Num").val()+") ya fue emitido");
			}
		}else{
			$.alert("Complete todos los campos");
		}
	}else{
		$.alert("No puede ingresar dos pagos con el mismo n&uacute;mero de cheque");
	}
	$('#pagos').updateGridsSizes();
}

// habilita y deshabilita campos dependiendo del tipo de pago seleccionado, recive el tipo de pago y su abrebiatura
function cambiarCamposPagos(tipo_pago,tipo_pago_abr){
	$("#Pap_Ctd").val("");
	var data = new Object();
	data['tipo'] = tipo_pago_abr;
	data['cargar_cuentas_pagos'] = true;
	$.post( "", data, function( responce ) {
		if(responce['success']===true){
			$("#Ban_Cod option").remove();
			for (i=0;i<responce['data'].length;i++){
				if(tipo_pago_abr=="EFE" || tipo_pago_abr=="DEP" || tipo_pago_abr == "OTR"){
					$("#Ban_Cod").append("<option value='"+responce['data'][i].Ban_Cod+"' data-pla='"+responce['data'][i].Pld_Cod+"' data-cdc='"+responce['data'][i].Pld_Cdc+"' data-cue='"+responce['data'][i].Ban_Cue+"' data-des='"+responce['data'][i].Pld_Des+"'>"+responce['data'][i].Pld_Des+"</option>");
				}else{
					$("#Ban_Cod").append("<option value='"+responce['data'][i].Ban_Cod+"' data-pla='"+responce['data'][i].Pld_Cod+"' data-cdc='"+responce['data'][i].Pld_Cdc+"' data-cue='"+responce['data'][i].Ban_Cue+"' data-des='"+responce['data'][i].Pld_Des+"'>"+responce['data'][i].Pld_Des+" - "+responce['data'][i].Ban_Cue+"</option>");
				}
			}
			$('#pagosForm').children().not(':first,:last').addClass('hidden');
			$('#pagosForm').find('.'+tipo_pago).removeClass('hidden');
			$('#pagosForm').find('.'+tipo_pago).find('.form-control').prop('required',true);

		}else{
			console.log(responce['message']);
		}
	},'json')
	.fail(function(error) {
		console.log("El Servidor ha fallado en responder!");
	});
}

// borra el pago seleccionado y actualiza los totales
function borrarPago(row){
	$('#pagos').jqGrid('delRowData',''+row.index+'');
	$('#pagos').updateGridDiario();
	var totalesgrid = actualizarTotales();
	var hab = totalesgrid.haber;
	var deb = totalesgrid.debe;
	$("#Atp_Val").val(parseFloat(hab).toFixed(2));
	$("#1_Debe").val($("#Atp_Val").val());
	$('#pagos').jqGrid("footerData", "set", {Glosa: "<div style='text-align:right;'>TOTALES:</div>", Debe:hab, Haber:hab});
}

// limpia el formulario y la tabla despues de guardar
function limpiarFormAnticipos(){
	$("#bandera_prov").val("nosel");
	$('#Tia_Cod').prop('selectedIndex', 0);
	$('#Pag_Cod').prop('selectedIndex', 0);

	$("#Prs_Cod").val("");
	$("#Prv_Cod").val("");
	$("#Atp_Val").val("0.00");
	$("#Prs_Ced").val("");
	$("#nombre").val("");
	$("#Prs_Dir").val("");
	$("#Atp_Obs").val("");
	$("#Pap_Ctd").val("");
	$("#Pap_Val").val("");
	$("#Che_Num").val("");
	$("#Num_Doc").val("");
	$("#NumDocPv").val("");
	$("#Num_Neg").val("");
	$("#Cod_Neg").val("");
	$("#pagos").jqGrid("clearGridData").trigger("reloadGrid");
	$('#pagos').updateGridDiario();
}

//permite el ingreso unicamente de numeros
function soloNumeros(e){
  // valor = valor.replace(/[^0-9]/g,'');
  var key = window.Event ? e.which : e.keyCode
  return (key >= 48 && key <= 57)
}

//verifica si hay al menos un pago antes de guardar unj anticipo
function preguardadopagos(){
	var ids= $('#pagos').jqGrid('getDataIDs');
	if(ids.length>1){
		$('#AnticipoPrvForm').formSubmit();
	}else {
		$.alert("Debe agregar al menos un pago");
	}
}


//funcion para guardar anticipos a proveedores
function guardar_anticipo(){

	var totalesgrid = actualizarTotales();

	var deb = totalesgrid.debe,
	hab = totalesgrid.haber;
	// comparacion entre totales para guardar anticipo a proveedor seleccionado
	
	console.log(deb.toFixed(4)+"  "+hab.toFixed(4) );
	
	if(deb.toFixed(4)===hab.toFixed(4) && (deb!="0" && hab!="0")){
		$("#Atp_Val").val(parseFloat(deb).toFixed(2));
		var data=$('#AnticipoPrvForm').serializeObject();
		data["saveAnticipo"]=true;

		var batch = $('#pagos').getGridBatch();
		data["pago_anticipo_proveedores"]=batch;

		$.post( "", data, function( responce ) {
			if(responce['success']===true){
				//
				limpiarFormAnticipos();
				$('#impCompr').attr('href',responce['link']);

				if(responce['bnd_che']===true){
					$("#successDialog").dialog({width: 500,height:355});
					$("#siche").removeAttr("hidden");
					$("#Che_imp option").remove();
					for(i=0;i<responce['arrayche'].length;i++){
						//responce['arrayche'][i].link
						$("#Che_imp").append("<option value='"+i+"' data-link='"+responce['arrayche'][i].link+"'>"+responce['arrayche'][i].che+"</option>");
					}
					$("#Che_imp").trigger("onchange");
				}else{
					$("#successDialog").dialog({width: 500,height:200});
				}

				$('#successDialog').dialog('open');
			}else{
				$.alert(responce['message']);
				$('#pagos').startGridEdit();
			}
		},'json')
		.fail(function(error) {
			$.alert("El Servidor ha fallado en responder!");
			$('#pagos').startGridEdit();
			$('#pagos').updateGridDiario();
		});
	}else{
		$.alert("Los totales no coinciden!");
		$('#pagos').startGridEdit();
		$("#Atp_Val").val(parseFloat(hab).toFixed(2));
		$("#1_Debe").val($("#Atp_Val").val());
		$('#pagos').updateGridDiario();
		$('#pagos').startGridEdit();
	}
}

//actualiza los totales y debuelve dichos totales
function actualizarTotales(){
	var total=parseFloat($("#Atp_Val").val());
	total+=parseFloat($("#Pap_Val").val());

	//obtener todos los ids para buscar valores de debe y haber
	var ids= $('#pagos').jqGrid('getDataIDs');
	var tot_obj = new Object();
	var debe=0.00;
	var haber=0.00;
	for(i=0; i< ids.length; i++){
		//
		if($('#'+ids[i]+'_Debe').val()!=undefined){
			debe+=parseFloat($('#'+ids[i]+'_Debe').val());
		}
		if($('#'+ids[i]+'_Haber').val()!=undefined){
			haber+=parseFloat($('#'+ids[i]+'_Haber').val());
		}
	}
	tot_obj['debe']=debe;
	tot_obj['haber']=haber;
	return tot_obj;
}

function cambiarChe(){
	// $("#impChe").attr("href",$("#Che_imp option:selected").attr("data-link"));
	$("#impchetd td").each(function(){
		$(this).children("a").attr("href", $(this).children("a").attr("data-ruta")+""+$("#Che_imp option:selected").attr("data-link"));
	});
}

$.fn.createInputDiario3=function(element,tipo){
    var jgrid=this, rowId=$(element).closest('tr.jqgrow').attr('id'), tip=jgrid.jqGrid('getCell',rowId,'Det_Tip');
    $(element).parent().removeAttr("title");
    if(tip===tipo){
        $(element).on('change', function(){
					var totalesgrid = actualizarTotales();
					var hab = totalesgrid.haber;
					$("#Atp_Val").val(parseFloat(hab).toFixed(2));
					$("#1_Debe").val($("#Atp_Val").val());
					$(this).val($.toFixed($(this).val())); jgrid.updateGridDiario();
				});
        $(element).attr('onkeypress','return  validar_decimal(event)');
        if(parseFloat($(element).val())===0) $(element).val("");
        $(element).css('text-align', 'right').focus();
    }else{ $(element).parent().html(''); };
};


//NUEVOS METODOS PARA HABILITAR LA CUENTA OTROS
let id_ccambiar_cuenta = "";
function AbrirCuentas(row) {
	id_ccambiar_cuenta = row.index;
	$('#cuentasDialog').dialog('open');
}

function cambiarCuenta(row) {
    $('#pagos').jqGrid('setCell', id_ccambiar_cuenta, 'Pld_Cod', row.Pld_Cod);
    $('#pagos').jqGrid('setCell', id_ccambiar_cuenta, 'Pld_Cdc', row.Pld_Cdc);
    $('#pagos').jqGrid('setCell', id_ccambiar_cuenta, 'Pld_Des', row.Pld_Des);
    $('#cuentasDialog').dialog('close');
}
