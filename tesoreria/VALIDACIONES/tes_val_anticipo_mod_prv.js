var id_pagos = 0;
$(function () {
	$( "#successDialog" ).createDialog({width:500,height:200,icon:'print'});
	$( "#verPagosDialog" ).createDialog({width:400,height:270,icon:'info-sign'});
	$('#pagosDialog').createDialog({height:325,icon:'usd'});
});
$( document ).ready(function() {
	// inicializa componentes de fecha en formulario de anticipos
	$('.datepicker').createDatePickers({checkAvailability:true,hideMsg:false}).mask('9999-99-99',{placeholder:'_'});
	$("#periodos").trigger("onchange");
	//
	cargarAnticipos();
	crearGridShowPagosAsi();
	crearGridshowPagosChe();
	createPagosModGrid();

	$( "#verPagosDialogMod" ).createDialog({width:700,height:435,icon:'info-sign'});

	$("#tabs_ant_det").tabs();

	$("#showPagosAsi").updateGridsSizes();
	$("#showPagosChe").updateGridsSizes();

});
var searchGridLoad =false;

function cargarAnticipos(){
  $("#searchGrid").createGrid({
    postData: $("#searchAnticipos").getData("anticiposAjax"), height: 200,
    colModel: [
      {label: 'Cod. Int.', name: 'Prv_Cod', key:true, width: 30, align: "left"},
			{label: ' ', name: 'Prs_Cod', hidden:true},
      {label: 'C&eacute;dula', name: 'Prs_Ced', width: 50, align: "left",cellattr:function(){return 'style="'+excelFormats.text+'"';}},
      {label: 'Nombre', name: 'nombre', width: 100, align: "left"},
      {label: 'Direci&oacute;n', name: 'Prs_Dir', width: 140, align: "left"},
      {label: 'Saldo', name: 'tot_anti', width: 80, align: 'right',
      formatter:'currency', formatoptions: {
        prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''
      },summaryType: "sum"}
    ],
    subGridOptions: {
      "plusicon"  : "ui-icon-triangle-1-e","minusicon" : "ui-icon-triangle-1-s","openicon"  : "ui-icon-arrowreturn-1-e","reloadOnExpand" : false,"selectOnExpand" : true
    },subGrid: true,multiselect: false,
    subGridRowExpanded: function(subgrid_id, row_id) {
      var subgrid_table_id = subgrid_id+"_t";
      var proveedor_data = jQuery('#searchGrid').jqGrid('getRowData', row_id);
      $("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table>");
      $("#"+subgrid_table_id).createGrid({
        url:""+"?anticiposDetAjax="+proveedor_data.Prv_Cod+"&txt_fec_ini="+$("#txt_fec_ini").val()+"&txt_fec_fin="+$("#txt_fec_fin").val(),datatype: "json",regional : 'es',
        height: 'auto',
        caption:"<center>Anticipos de proveedor "+proveedor_data.nombre+"</center>",
        colModel: [
          {label: '', name: 'Atp_Cod', key:true,hidden:true},
          {label: '', name: 'Com_Cod',hidden:true},
					{label: '', name: 'Atp_Est',hidden:true},
					{label: '', name: 'Tia_Cod',hidden:true},
					{label: '', name: 'Com_Num',hidden:true},
          {label: 'No. Compr.', name: 'codigo_compro', width: 30, align: "left"},
          {label: 'Fecha', name: 'Atp_Fec', width: 20, align: "left"},
          {label: 'Observaci&oacute;n', name: 'Atp_Obs', width: 140, align: "left"},
          {label: 'Cant. Pagos', name: 'cnt_pagos', width: 30, align: "center"},
					{label: 'Valor', name: 'Atp_Val', width: 50, align: 'right',
            formatter:'currency', formatoptions: {
              prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''
            }
          },
					{label: 'Saldo', name: 'tot_sald', width: 50, align: 'right',
            formatter:'currency', formatoptions: {
              prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''
            }
          },
          {label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'btns_anti', width: 40, align: 'center',viewable: false,
            formatter:function (cellvalue, options, rowObject) {
              var parm_anu = [rowObject, ""+subgrid_table_id];
              var parm_getdet = [rowObject, row_id];
							if(rowObject.Atp_Est === "U"){
								return  $.getGridButton(verAnticipo, parm_getdet, 'ver anticipo', 'info-sign','','info');
							}else{
								return  $.getGridButton(verAnticipo, parm_getdet, 'ver anticipo', 'info-sign','','info')+"&nbsp;"+
	              $.getGridButton(modificarAnticipo, parm_getdet, 'Modificar anticipo', 'pencil','','success')+"&nbsp;"+
	              $.getGridButton(preanularAnticipo, parm_anu, 'Anular anticipo', 'remove','','danger');
							}
            }
          }
        ],
        loadComplete:function(){
          //
        },
        beforeSelectRow: function(rowid, e) {return false;},
        rowNum:100, pager: ""
      });
    },
    loadComplete:function(){
      // $(this).setGridSummary(['Debe'],{Pag_Des:'<div style="text-align:right;">TOTAL:</div>'});
			if(searchGridLoad === false){
				searchGridLoad=true;
				$(this).jqGrid('footerData', 'set', {
					nombre:"<div style='text-align:right;'>TOTAL GENERAL:</div>",
					Prs_Dir: "<span id='tot_general'>$ "+parseFloat($(this).jqGrid('getCol', 'tot_anti', true, 'sum')).toFixed(2)+" </span>"
				},true);
			}
			$(this).jqGrid('footerData', 'set', {
				sld_dsp:"<div style='text-align:right;'>TOTAL:</div>",
				tot_anti: $(this).jqGrid('getCol', 'tot_anti', true, 'sum')
			},true);
			$("#tot_general").parent().attr("style","text-align:right;");
    },
    rowNum:100,gridview: true, viewrecords: true,footerrow: true, userDataOnFooter: false,
    onSelectRow: function(rowid, e) { $("#searchGrid").resetSelection();},
    multiselect: false
  }, false, "#searchGridPager");
}

function verAnticipo(params){
	$("#showPagosAsi").updateGridsSizes();$("#showPagosChe").updateGridsSizes();

  $("#ant_detasi").children("a").trigger("click");

  $("#showPagosAsi").jqGrid("clearGridData").trigger("reloadGrid");
  $("#showPagosChe").jqGrid("clearGridData").trigger("reloadGrid");

  var proveedor_data = jQuery('#searchGrid').jqGrid('getRowData', params[1]);

  // $("#obs_show").val(proveedor_data.nombre);
  $("#prov_show").val(proveedor_data.nombre);
  $("#ruc_show").val(proveedor_data.Prs_Ced);
  $("#compr_show").val(params[0].codigo_compro);
  $("#fec_show").val(params[0].Atp_Fec);
  $("#obs_show").val(params[0].Atp_Obs);

  $('#verPagosDialogMod').dialog('open');
  $('div#verPagosDialog').siblings('.ui-dialog-titlebar').children('span').text(proveedor_data.nombre);
  // $('#verPagosDialog').attr("title",proveedor_data.nombre);
  $.post( "", {getAsientosAnticipo:true,Com_Cod:params[0].Com_Cod ,Prv_Cod:params[1],Atp_Cod:params[0].Atp_Cod}, function( responce ) {
		if(responce['success']===true){
			//---------------pagos iniciales sin cheques protestados------------------
			var ids_pagos= $('#showPagosAsi').jqGrid('getDataIDs').length+1;

			//agregamos asientos
      for(i=0;i<responce['data'].length;i++){
        var ids_pagos= $('#showPagosAsi').jqGrid('getDataIDs').length+1;
        $('#showPagosAsi').jqGrid('addRowData', ids_pagos,
  			{
  				index:ids_pagos,
          Pld_Cdc:responce['data'][i].Pld_Cdc,
					Pap_Est:responce['data'][i].Pap_Est,
  				Pld_Des:responce['data'][i].Pld_Des,
  				Glosa:responce['data'][i].Asi_Glo,
  				Debe:responce['data'][i].Debe,
  				Haber:responce['data'][i].Haber
  			},"last");
      }

      $('#showPagosAsi').jqGrid('footerData', 'set', {
        Glosa:"<div style='text-align:right;'>TOTALES:</div>",
        Debe: $('#showPagosAsi').jqGrid('getCol', 'Debe', true, 'sum'),
        Haber: $('#showPagosAsi').jqGrid('getCol', 'Haber', true, 'sum')
      },true);

			//en caso de existir cheques mostramos la pestania de cheques
      if(responce['data_che'].length === 0){
        $("#ant_detche").hide();
      }else{
        $("#ant_detche").show();
      }

			//agregamos cheques
      for(let i=0;i<responce['data_che'].length;i++){
        let ids_pg= $('#showPagosChe').jqGrid('getDataIDs').length+1;
				let estado_che ="";
				if(responce['data_che'][i].Che_Est === "P") estado_che="Protestado";
				if(responce['data_che'][i].Che_Est === "S") estado_che="Sin cobrar";
				if(responce['data_che'][i].Che_Est === "I") estado_che="Anulado";
        if(responce['data_che'][i].Che_Est === "A") estado_che="Activo";
				if(responce['data_che'][i].Che_Est === "C") estado_che="Cobrado";
        $('#showPagosChe').jqGrid('addRowData', ids_pg,
  			{
  				index:ids_pg,
  				Che_Num:responce['data_che'][i].Che_Num,
  				Che_Fec:responce['data_che'][i].Che_Fec,
  				Che_Val:responce['data_che'][i].Che_Val,
  				Che_Obs:responce['data_che'][i].Che_Obs,
					Che_Est_det:estado_che,
					Pap_Cod:responce['data_che'][i].Pap_Cod,
					Atp_Cod:responce['data_che'][i].Atp_Cod,
					Tia_Cod:responce['data_che'][i].Tia_Cod,
					Com_Cod:responce['data_che'][i].Com_Cod,
					Atp_Fec:responce['data_che'][i].Atp_Fec,
					Prv_Cod:responce['data_che'][i].Prv_Cod,
          Che_Est:responce['data_che'][i].Che_Est,
          Che_Cod:responce['data_che'][i].Che_Cod,
          Ban_Cod:responce['data_che'][i].Ban_Cod,
          Asi_Cod:responce['data_che'][i].Asi_Cod,
          Pld_Cod:responce['data_che'][i].Pld_Cod
  			},"last");
      }

      $('#showPagosChe').jqGrid('footerData', 'set', {
        Che_Obs:"<div style='text-align:right;'>TOTAL:</div>",
        Che_Val: $('#showPagosChe').jqGrid('getCol', 'Che_Val', true, 'sum')
      },true);
      $('#showPagosChe tr').each(function () {
        if($(this).find("td").eq(2).text()==="P"){
					$(this).addClass("cellRed2");
					$(this).addClass("myAltRowClass");
				}
			});

      $("#showPagosAsi").updateGridsSizes();
      $("#showPagosChe").updateGridsSizes();
		}else{
			console.log(responce['message']);
		}
	},'json')
	.fail(function(error) {
		console.log("El Servidor ha fallado en responder!");
	});
  document.getElementById("compr_show").focus();
}

function modificarAnticipo(parm_mod){
	var proveedor_data = $('#searchGrid').jqGrid('getRowData', parm_mod[1]);

	$("#pagos").jqGrid("clearGridData").trigger("reloadGrid");

  //rows  id
	$("#bandera_prov").val("sel");
	$("#Prs_Cod").val(proveedor_data.Prs_Cod);
	$("#Prv_Cod").val(proveedor_data.Prv_Cod);

  $("#Prs_Ced").val(proveedor_data.Prs_Ced);
  $("#nombre").val(proveedor_data.nombre);
  $("#Prs_Dir").val(proveedor_data.Prs_Dir);

	$("#Com_Cod").val(parm_mod[0].Com_Cod);
	$("#Atp_Cod").val(parm_mod[0].Atp_Cod);

  $.post( "", {obtenerPeriodoMinMax:true}, function( responce ) {
		if(responce['success']===true){
			$("#Atp_Fec").dateLimits(responce['data']['minimo'],responce['data']['maximo']);
      $("#Atp_Fec").val(parm_mod[0].Atp_Fec);
		}else{
			console.log(responce['message']);
		}
	},'json')
	.fail(function(error) {
		console.log("El Servidor ha fallado en responder!");
	});

	$("#Tia_Cod option[value="+ parm_mod[0].Tia_Cod +"]").prop("selected",true);
	$("#Tia_Cod_temp").val(parm_mod[0].Tia_Cod);
	$("#Com_Num").val(parm_mod[0].Com_Num);
  $("#Atp_Obs").val(parm_mod[0].Atp_Obs);

	$.post( "", {getAsientosAnticipoMod:true,Com_Cod:parm_mod[0].Com_Cod}, function( responce ) {
    if(responce['success']===true){
			//agregamos asientos
      for(i=0;i<responce['data'].length;i++){
				id_pagos++;
				var total=parseFloat($("#Atp_Val").val());
				total+=parseFloat($("#Pap_Val").val());
				$("#Atp_Val").val(total.toFixed(2));
				$("#1_Debe").val(total.toFixed(2));
				let tpg_gr="";
      	if(responce['data'][i].Asi_Deh === 'D'){tpg_gr="inicial"}else{tpg_gr='pago'}
				if(responce['data'][i].Che_Est==="P"){tpg_gr="che_prot";}
				$('#pagos').jqGrid('addRowData', id_pagos,
				{
					index:id_pagos,
					grid_tipp:tpg_gr,
					Che_Cod:responce['data'][i].Che_Cod,
					Asi_Cod:responce['data'][i].Asi_Cod,
					Pap_Cod:responce['data'][i].Pap_Cod,
					Pag_Cod:responce['data'][i].Pag_Cod,
					Pag_Abr:responce['data'][i].Pag_Abr,
					Pag_Des:responce['data'][i].Pag_Des,
					Pld_Des:responce['data'][i].Pld_Des,
					Pld_Cdc:responce['data'][i].Pld_Cdc,
					Pap_Ctd:responce['data'][i].Pap_Ctd,
					Ban_Cod:responce['data'][i].Ban_Cod,
					Che_Est:responce['data'][i].Che_Est,
					Che_Num:responce['data'][i].Che_Num,
					Che_Fec:responce['data'][i].Che_Fec,
					Pap_Cto:responce['data'][i].Pap_Cto,
					Pld_Cod:responce['data'][i].Pld_Cod,
					Det_Tip:responce['data'][i].Asi_Deh,
					Glosa:responce['data'][i].Asi_Glo,
					Debe:responce['data'][i].Debe,
					Haber:responce['data'][i].Haber,
					Pag_Item:""
				},"last");
      }
			$('#pagos').startGridEdit();
			$('#pagos').updateGridDiario();
			$("#1_Debe").attr("readonly","");
			bloquearprot();
			//
    }else{
      console.log(responce['message']);
    }
  },'json')
  .fail(function(error) {
    console.log("El Servidor ha fallado en responder!");
  });

	moveToUpdate();
}

function bloquearprot(){
  var ids_p= $('#pagos').jqGrid('getDataIDs');
  for(let i=0; i< ids_p.length; i++){
    let reg_pago = $('#pagos').jqGrid('getRowData',ids_p[i]);
    if(reg_pago.Pag_Abr === "CHE" && reg_pago.Che_Est === "P"){
      $("#"+ids_p[i]+"_Haber").attr("readonly","");
      $("#"+ids_p[i]+"_Glosa").attr("readonly","");
    }
  }
}

//previo a la eliminacion se muestra un dialogo de confirmacion
function preanularAnticipo(parms){
  $.createDialogConfirm('¿Est&aacute; seguro que desea anular este Anticipo?',parms,anularAnticipo);
}

//eliminamos un anticipo en caso de haberlo registrado incorrectamente
function anularAnticipo(parms){
  $.post( "", {delAnticipo:true,Atp_Cod:parms[0].Atp_Cod,Com_Cod:parms[0].Com_Cod}, function( responce ) {
    if(responce['success']===true){
      $.alert("Anticipo anulado!");
      $("#"+parms[1]).trigger("reloadGrid");
    }else{
      $.alert(responce['message']);
    }
  },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});
}

//dirigirse de la pantalla de listar anticipos a la de modificar anticipos
function moveToUpdate(){
  $("#documentoSearch").moveComp("#documentoUpdate").updateGridsSizes();
}

//dirigirse de la ventana de modificar anticipos a la de listar anticipos
function moveToList(){
  $("#documentoUpdate").moveComp("#documentoSearch").updateGridsSizes();
  $("#searchGrid").trigger("reloadGrid");
}

function cambioPreiodoSearch(parm_peri){
  if(parm_peri==='peri'){
    $("#txt_fec_ini").dateLimits($("#periodos option:selected").attr("data-inicio"),$("#periodos option:selected").attr("data-fin"));
    $("#txt_fec_ini").val($("#periodos option:selected").attr("data-inicio"));
    $("#txt_fec_fin").dateLimits($("#txt_fec_ini").val(),$("#periodos option:selected").attr("data-fin"));
  }else{
    $("#txt_fec_fin").dateLimits($("#txt_fec_ini").val(),$("#periodos option:selected").attr("data-fin"));
  }
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

//muestra los datos del pago ingresado
function mostrarPago(row){
	$('#verPagosForm').children().not(':first,:last').addClass('hidden');
	$('#verPagosForm').find('.'+row.Pag_Des).removeClass('hidden');
	$('#verPagosForm').find('.'+row.Pag_Des).find('.form-control').prop('required',true);

	$("#pago_ver").val(row.Pag_Des);
	$("#cuenta_ver").val(row.Pap_Cto);
	$("#destino_ver").val(row.Pap_Ctd);
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
				if(tipo_pago_abr=="EFE" || tipo_pago_abr=="DEP"){
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
				if($("#Pag_Cod option:selected").attr("data-abr")=="EFE"){
					if($("#pagos td:contains('EFE')").text().search("EFE")==-1){
						$("#Atp_Val").val(total.toFixed(2));
						$("#1_Debe").val(total.toFixed(2));
						id_pagos++;
						$('#pagos').jqGrid('addRowData', id_pagos,
						{
							index:id_pagos,
							grid_tipp:'pago',
							Che_Cod:'',
							Asi_Cod:'',
							Pap_Cod:'',
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
				}else{
					$("#Atp_Val").val(total.toFixed(2));
					$("#1_Debe").val(total.toFixed(2));
					id_pagos++;
					$('#pagos').jqGrid('addRowData', id_pagos,
					{
						index:id_pagos,
						grid_tipp:'pago',
						Che_Cod:'',
						Asi_Cod:'',
						Pap_Cod:'',
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

	var totalesgrid = actualizarTotales();
	$("#Atp_Val").val(parseFloat(totalesgrid.haber).toFixed(2));
	$("#1_Debe").val($("#Atp_Val").val());
	$('#pagos').updateGridsSizes();
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

//verifica si hay al menos un pago antes de guardar unj anticipo
function preguardadopagos(){
	var ids= $('#pagos').jqGrid('getDataIDs');
	if(ids.length>1){
		$('#AnticipoPrvForm').formSubmit();
	}else {
		$.alert("Debe agregar al menos un pago");
	}
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

	$("#pagos").jqGrid("clearGridData").trigger("reloadGrid");
	$('#pagos').updateGridDiario();
}

//funcion para guardar anticipos a proveedores
function guardar_anticipo(){
	var totalesgrid = actualizarTotales();

	var deb = totalesgrid.debe,
	hab = totalesgrid.haber;
	// comparacion entre totales para guardar anticipo a proveedor seleccionado
	if(deb===hab && (deb!="0" && hab!="0")){
		$("#Atp_Val").val(parseFloat(deb).toFixed(2));
		var data=$('#AnticipoPrvForm').serializeObject();
		data["saveAnticipo"]=true;

		var batch = $('#pagos').getGridBatch();
		data["pago_anticipo_proveedores"]=batch;

		$.saveDataJson("", data, function (responce) {
			limpiarFormAnticipos();
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
			$('#impCompr').attr('href',responce['link']);
			$('#successDialog').dialog('open');
			moveToList();
			return false;
		}, function (responce) {
			//$.alert(responce['message']);
			$('#pagos').startGridEdit();
		}, function (responce) {
			//$.alert(responce['message']);
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

//permite el ingreso unicamente de numeros
function soloNumeros(e){
  // valor = valor.replace(/[^0-9]/g,'');
  var key = window.Event ? e.which : e.keyCode
  return (key >= 48 && key <= 57)
}

function openDialogPagos(){
	if($("#bandera_prov").val()=="sel"){
		$('#Pag_Cod').trigger('change');
		$('#pagosDialog').dialog('open');
		$('#pagosForm').removeData();
	}else{
		$.alert("Primero debe seleccionar un proveedor!");
	}
}

function preProtestarCheque(row){
  $.createDialogConfirm('¿Est&aacute; seguro que desea marcar como protestado este cheque?',row,protestarCheque);
}

//funcion que permite protestar un cheque, desactivar el pago respectivo y modificar el total del anticipo
function protestarCheque(row){
	$.saveDataJson("", {protestarChe:true,row:row}, function (responce) {
    if(responce['pec_ban']==="si"){
			//
			$("#searchGrid").trigger("reloadGrid");
			$('#verPagosDialogMod').dialog('close');
			$('#impCompr').attr('href',responce['link']);
			$('#successDialog').dialog('open');
		}else{
			$.alert(responce['message']);
		}
    return false;
  }, function (responce) {
    $.alert(responce['message']);
  }, function (responce) {
    $.alert(responce['message']);
	});
}

function crearGridShowPagosAsi(){
  $('#showPagosAsi').createGrid({viewrecords:false,
    caption:"<center>Detalle del anticipo</center>",
    data:[], rowNum: 100, height: 100, width: 650, footerrow:true, responsive:false,
    onSelectRow: function(rowid, e) { $(this).resetSelection();},
    colModel:[
      { label: 'index', name: 'index',hidden:true, classes:'bgNoRight' },
			{ label: '', name: 'Pap_Est',hidden:true },
      { label: 'Codigo', name: 'Pld_Cdc', width: 10, align:"left"},
      { label: 'Cuenta', name: 'Pld_Des', width: 30, align:"left"},
      { label: 'Glosa', name: 'Glosa', width: 25, align:"left"},
      { label: 'Debe', name: 'Debe', width: 10, align: 'right', formatter:'currency', editable:true,
        formatoptions: {
          prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''
        }
      },
      { label: 'Haber', name: 'Haber', width: 10, align: 'right', formatter:'currency', editable:true,
        formatoptions: {
          prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''
        }
      }
    ]
  },true,'',{view:false});
}

function crearGridshowPagosChe(){
  $('#showPagosChe').createGrid({viewrecords:false,
    caption:"<center>Cheques emitidos en este anticipo</center>",
    data:[], rowNum: 100, height: 100, width: 650, footerrow:true, responsive:false,
    onSelectRow: function(rowid, e) { $(this).resetSelection();},
    colModel:[
      { label: 'index', key:true, name: 'index',hidden:true, classes:'bgNoRight' },
			{ label: '', name: 'Pap_Cod',hidden:true, classes:'bgNoRight' },
			{ label: '', name: 'Atp_Cod',hidden:true, classes:'bgNoRight' },
			{ label: '', name: 'Atp_Val',hidden:true, classes:'bgNoRight' },
			{ label: '', name: 'Com_Cod',hidden:true, classes:'bgNoRight' },
			{ label: '', name: 'Tia_Cod',hidden:true, classes:'bgNoRight' },
			{ label: '', name: 'Pld_Cod',hidden:true, classes:'bgNoRight' },
			{ label: '', name: 'Atp_Fec',hidden:true, classes:'bgNoRight' },
			{ label: '', name: 'Che_Cod',hidden:true, classes:'bgNoRight' },
			{ label: '', name: 'Prv_Cod',hidden:true, classes:'bgNoRight' },
			{ label: '', name: 'Ban_Cod',hidden:true, classes:'bgNoRight' },
			{ label: '', name: 'Asi_Cod',hidden:true, classes:'bgNoRight' },
      { label: 'No. Che.', name: 'Che_Num', width: 15, align:"left"},
      { label: 'Fecha', name: 'Che_Fec', width: 15, align:"left"},
      { label: 'Observaci&oacute;n', name: 'Che_Obs', width: 25, align:"left"},
      { label: 'Valor', name: 'Che_Val', width: 15, align: 'right', formatter:'currency', editable:true,
        formatoptions: {
          prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''
        }
      },
			{ label: '', name: 'Che_Est',hidden:true, width: 15, align:"left"},
			{ label: 'Estado', name: 'Che_Est_det', width: 15, align:"left"},
			{label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'btns_anti', width: 10, align: 'center',viewable: false,
				formatter:function (cellvalue, options, rowObject) {
					if(rowObject.Che_Est==='P'){
						return "-";
					} else{
						return $.getGridButton(preProtestarCheque, rowObject, 'Marcar como protestado', 'ban-circle','','danger');
					}
				}
			}
    ]
  },true,'',{view:false});
}

function createPagosModGrid(){
  $('#pagos').createGrid({viewrecords:false,
    data:[], rowNum: 100, height: 150, footerrow:true,
    onSelectRow: function(rowid, e) { $(this).resetSelection();},
    colModel:[
      { label: 'index', name: 'index',hidden:true, classes:'bgNoRight' },
      { label: ' ', name: 'Det_Tip', hidden:true },
      { label: ' ', name: 'grid_tipp', hidden:true },
			{ label: ' ', name: 'Che_Cod', hidden:true},
      { label: 'Asi_Cod', name: 'Asi_Cod',hidden:true, classes:'bgNoRight' },
			{ label: 'Pap_Cod', name: 'Pap_Cod',hidden:true, classes:'bgNoRight' },
			{ label: 'Pag_Cod', name: 'Pag_Cod',hidden:true, classes:'bgNoRight' },
      { label: 'Pag_Abr', name: 'Pag_Abr',hidden:true, classes:'bgNoRight' },
      { label: 'Tipo', name: 'Pag_Des', width: 10, align:"center", classes:'bgNoRight'},
      { label: 'Ban_Cod', name: 'Ban_Cod',hidden:true, classes:'bgNoRight' },
			{ label: 'Che_Num', name: 'Che_Num',hidden:true, classes:'bgNoRight' },
			{ label: '', name: 'Che_Est',hidden:true },
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
          if (rowObject.grid_tipp==='inicial' || rowObject.grid_tipp==='che_prot') {
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
