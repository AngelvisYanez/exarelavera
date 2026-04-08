$(function () {
  $( "#verPagosDialogMod" ).createDialog({width:700,height:435,icon:'info-sign'});
  $("#tabs_abo_det").tabs();
  $('.datepicker').createDatePickers({checkAvailability:true,hideMsg:false}).mask('9999-99-99',{placeholder:'_'});

  ////Dialog buscar clientes
  $.createSearchDialog('proveedoresDialog',[
      { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 15,align:"center",hidden:true },
      { label: 'C&eacute;dula/RUC', name: 'Prs_Ced', width: 50 },
      { label: 'Proveedor', name: 'nombre', width: 100},
      { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
      { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:selectProveedor} }
  ],null,null,null,{headertitles:true},{ title:'Proveedor', text:'searchPrv' });

  cargarAbonos();

  //tabla de cheques
  $('#showPagosChe').createGrid({viewrecords:false,
    caption:"<center>Cheques emitidos en este Abono</center>",
    data:[], rowNum: 100, height: 100, width: 650, footerrow:true,responsive:false,
    onSelectRow: function(rowid, e) { $(this).resetSelection();},
    colModel:[
      { label: 'index', key:true, name: 'index',hidden:true, classes:'bgNoRight' },
      { label: 'No. Che.', name: 'Che_Num', width: 15, align:"left"},
      { label: 'Fecha', name: 'Che_Fec', width: 15, align:"left"},
      { label: 'Observaci&oacute;n', name: 'Che_Obs', width: 25, align:"left"},
      { label: 'Valor', name: 'Che_Val', width: 15, align: 'right', formatter:'currency', editable:true,
        formatoptions: {
          prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''
        }
      },
			{ label: 'Estado', name: 'Che_Est_det', width: 15, align:"left"}
    ]
  },true,'',{view:false});

  //tabala para visualizar asientos de un abono
  $('#showPagosAsi').createGrid({viewrecords:false,
    caption:"<center>Detalle del Abono</center>",
    data:[], rowNum: 100, height: 100, width: 650, footerrow:true,responsive:false,
    onSelectRow: function(rowid, e) { $(this).resetSelection();},
    colModel:[
      { label: 'index', name: 'index',hidden:true, classes:'bgNoRight' },
      { label: 'Codigo', name: 'Pld_Cdc', width: 10, align:"left"},
      { label: 'Cuenta', name: 'Pld_Des', width: 30, align:"left"},
      { label: 'Glosa', name: 'Glosa', width: 20, align:"left"},
      { label: 'Debe', name: 'Debe', width: 15, align: 'right', formatter:'currency', editable:true,
        formatoptions: {
          prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''
        }
      },
      { label: 'Haber', name: 'Haber', width: 15, align: 'right', formatter:'currency', editable:true,
        formatoptions: {
          prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''
        }
      }
    ]
  },true,'',{view:false});


});

$( document ).ready(function() {
	$("#sel_ven").trigger("onchange");
  $("#radsc1").trigger("onchange");
});

function cargarAbonos(){
  var mostrarColumnas = (isnego === 'S');
  $("#searchGrid").createGrid({
    caption: 'Resultados de la Busqueda' + (typeof isnego !== 'undefined' && isnego === 'S'
            ? ' <div class="pull-right"><b>FILTRADO POR:</b>&nbsp;<select id="FilterBy" onchange="cargarSelect();"><option value="">No filtrar</option><option value="L">Larva</option><option value="B">Balanceado</option><option value="F">Flete Falso</option><option value="I">Insumos</option></select>&nbsp;</div>'
            : ''),
    postData: $("#searchCcpp").getData("getFactsProvee"), height: 300,responsive:false,
    colModel: [
      { label: 'Cód.Int.', name: 'Cpp_Cod', key: true, hidden:true,viewable:true },
      { label: 'Cód.Int.', name: 'Asi_Cod',  hidden:true },
      {label:'Pld_Cod.',name:"Pld_Cod",hidden:true},
      {label:'Pld_Cdc.',name:"Pld_Cdc",hidden:true},
      {label:'Pld_Des.',name:"Pld_Des",hidden:true},
      { label: 'No. Compr.', name: 'Com_Codigo',align:"center", width: 25 },
      { label: 'Fecha Emis.', name: 'Cop_Fec',align:"center", width: 30  },
      { label: 'Fecha Venc.', name: 'Cpp_Ven', align:"center", width: 30},
      { label: 'Vencimiento', name: 'vencimiento', align:"center", width: 25},
      { label: 'Total', name: 'Asi_Val', width: 30, align: 'right', formatter:'currency', decimalPlaces: '2', summaryRound: 2,
        formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "Total: {0}",summaryType: "sum"
      },
      { label: 'Abono', name: 'Abono', width: 30, align: 'right', decimalPlaces: '2', summaryRound: 2,formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},
        formatter: function (cellValue, options, rowObject) { if(!parseFloat(rowObject.Abono)) rowObject.Abono=0;return $.fn.fmatter.call(this, "currency",rowObject.Abono, options);},
        unformat: function (cellValue, options, cell) {let opt = $.extend(true, {}, options);opt.colModel.formatter = "currency";delete opt.colModel.unformat;return $.unformat.call(this, cell, opt);},
        summaryTpl: "Total: {0}",summaryType: "sum" // set the formula to calculate the summary type
      },
      { label: 'Saldo', name: 'Saldo', width: 30, align: 'right',  decimalPlaces: '2', summaryRound: 2,
        formatoptions: {prefix:'$ ',  thousandsSeparator:',',decimalSeparator:'.'},
        formatter: function (cellValue, options, rowObject) {
          if(!parseFloat(rowObject.Saldo)){
            rowObject.Saldo = parseFloat(rowObject.Asi_Val) - parseFloat(rowObject.Abono);
          }
          //if(parseFloat(rowObject.Abono)===parseFloat(rowObject.Asi_Val)) return 'Pagado'; else
          if (parseFloat(rowObject.Abono) === parseFloat(rowObject.Asi_Val)) return "0.00";else
          return $.fn.fmatter.call(this, "currency", rowObject.Saldo, options);
        },
        unformat: function (cellValue, options, cell) {let opt = $.extend(true, {}, options);opt.colModel.formatter = "currency";delete opt.colModel.unformat;return $.unformat.call(this, cell, opt);},
        summaryTpl: "Total: {0}",summaryType: "sum" // set the formula to calculate the summary type
      },
      { label: 'No. Docum.', name: 'Cop_Num', width: 45, align:"center"},
      { label: 'Obs. Docum.', name: 'Cop_Obs', width: 55},
      { label: 'Num.Neg', name: 'Num_Neg', width: 65, align: 'center', hidden: !mostrarColumnas },
      { label: 'Tipo Producto', name: 'Tip_Prod', width: 70, align: 'center', hidden: !mostrarColumnas,
        formatter: function(cellvalue) {
                if (cellvalue === 'B') return 'Balanceado';
                if (cellvalue === 'L') return 'Larva';
                if (cellvalue === 'F') return 'Flete';
                if (cellvalue === 'I') return 'Insumos';
                if (cellvalue === null) return '';
                return cellvalue;
          }
      },
      {label:'Prv_Cod.',name:"Prv_Cod",hidden:true},
      {label:'Prs_Ape.',name:"Prs_Ape",hidden:true},
      {label:'Prs_Nom.',name:"Prs_Nom",hidden:true},
      {label:'',name:"ruc",hidden:true},
      { label: 'Proveedor', name: 'proveedor', width: 75}
    ],
    subGridOptions: {
      "plusicon"  : "ui-icon-triangle-1-e","minusicon" : "ui-icon-triangle-1-s","openicon"  : "ui-icon-arrowreturn-1-e","reloadOnExpand" : false,"selectOnExpand" : true
    },subGrid: true,multiselect: false,
    subGridRowExpanded: function(subgrid_id, row_id) {
      let subgrid_table_id = subgrid_id+"_t";
      let cpp_data = $('#searchGrid').jqGrid('getRowData', row_id);
      $("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table>");
      $("#"+subgrid_table_id).createGrid({
        url:"?abonosDetAjax="+cpp_data.Cpp_Cod,datatype: "json",regional : 'es',
        height: 'auto',responsive:false,
        colModel: [
          { label: 'Cod. Comprobante', name: 'Com_Cod',  width: 30,  key:true,hidden:false },
          { label: '', name: 'Cpp_Cod',hidden:true },
          { label: '', name: 'Com_Val',hidden:true },
          { label: '', name: 'Com_Con',hidden:true },
          { label: '', name: 'Pec_Cod',hidden:true },
          { label: 'No. Compr.', name: 'codigo_compro', width: 30, align: "left" },
          { label: 'Fecha', name: 'Com_Fec', width: 50, align: "left" },
          { label: 'Valor', name: 'Pag_Val', width: 50, align: 'right',
              formatter:'currency', formatoptions: {
                prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''
              }
          },
          { label: 'Observaci&oacute;n', name: 'Com_Obs', width: 140, align: "left",
            formatter: function(cellValue, options, rowObject) {
              return cellValue + (rowObject.Num_Doc ? " / Transf. N#: " + rowObject.Num_Doc : "");
            }
          },
          { label: 'T. Pago', name: 'Pag_Des', width: 20, align: "center" },
          { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'btns_anti', width: 25, align: 'center',viewable: false,
              formatter:function (cellvalue, options, rowObject) {
                let parm_getdet = [rowObject, row_id];
                return  $.getGridButton(verAbono, parm_getdet, 'Ver abono', 'info-sign','','info')+"&nbsp;";
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
    loadComplete:function(data){
      actualizarTotalesSG();

      $('#searchGrid tr').each(function () {
        if($(this).find("td").eq(10).text()==="Vencido"){
					$(this).addClass("cellRed2");
					$(this).addClass("myAltRowClass");
				}
        if($(this).find("td").eq(10).text()==="Pagado"){
					$(this).css("background","#DDFAE2");
					$(this).addClass("myAltRowClass");
				}
			});

    },
    rowNum:10000,gridview: true, viewrecords: true,footerrow: true, userDataOnFooter: false,
    onSelectRow: function(rowid, e) { $("#searchGrid").resetSelection();},
    multiselect: false
  }, false, "#pag_sg",{view:false, refresh:false});
}

// cargar el select de filtrado - NUEVA
function cargarSelect(){
  let filtro = $("#FilterBy").val();
  if(filtro===""){
    $("#filtroCCxPP").val("");
  }else{
    $("#filtroCCxPP").val(filtro);
  }
  // $('#searchGrid').trigger("reloadGrid");
  $('#searchGrid').submit();
}

function verAbono(row){
  $("#showPagosAsi").updateGridsSizes();$("#showPagosChe").updateGridsSizes();
  $("#showPagosAsi").jqGrid("clearGridData").trigger("reloadGrid");
  $("#showPagosChe").jqGrid("clearGridData").trigger("reloadGrid");

  $("#ant_detasi").children("a").trigger("click");

  let cpp_data = $('#searchGrid').jqGrid('getRowData', row[1]);
  $("#prov_show").val(cpp_data.proveedor);
  $("#ruc_show").val(cpp_data.ruc);

  $("#compr_show").val(row[0].codigo_compro);
  $("#fec_show").val(row[0].Com_Fec);
  $("#obs_show").val(row[0].Com_Obs);

  $('#verPagosDialogMod').dialog('open');
  $('div#verPagosDialog').siblings('.ui-dialog-titlebar').children('span').text($("#nombre").val());
  $.post( "", {getAsientosAbono:true,Com_Cod:row[0].Com_Cod}, function( responce ) {
		if(responce['success']===true){
			//agregamos asientos
      for(let i=0;i<responce['data'].length;i++){
        let ids_pg= $('#showPagosAsi').jqGrid('getDataIDs').length+1;
        let a_deb="",a_hab="";

        if(responce['data'][i].Asi_Deh === 'D'){
          a_deb=responce['data'][i].Asi_Val;a_hab="";
        }else{
          a_deb="";a_hab=responce['data'][i].Asi_Val;
        }

        $('#showPagosAsi').jqGrid('addRowData', ids_pg,
  			{
  				index:ids_pg,
          tip_pago:responce['data'][i].tip_pago,
          Pld_Cdc:responce['data'][i].Pld_Cdc,
  				Pld_Des:responce['data'][i].Pld_Des,
  				Glosa:responce['data'][i].Asi_Glo,
  				Debe:a_deb,
  				Haber:a_hab
  			},"last");
      }

      $('#showPagosAsi').jqGrid('footerData', 'set', {
        Glosa:"<div style='text-align:right;'>TOTALES:</div>",
        Debe: $('#showPagosAsi').jqGrid('getCol', 'Debe', true, 'sum'),
        Haber: $('#showPagosAsi').jqGrid('getCol', 'Haber', true, 'sum')
      },true);

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
				if(responce['data_che'][i].Che_Est === "A") estado_che="Sin cobrar";
				if(responce['data_che'][i].Che_Est === "I") estado_che="Anulado";
				if(responce['data_che'][i].Che_Est === "C") estado_che="Cobrado";
        $('#showPagosChe').jqGrid('addRowData', ids_pg,
  			{
  				index:ids_pg,
  				Che_Num:responce['data_che'][i].Che_Num,
  				Che_Fec:responce['data_che'][i].Che_Fec,
  				Che_Val:responce['data_che'][i].Che_Val,
  				Che_Obs:responce['data_che'][i].Che_Obs,
					Che_Est_det:estado_che
  			},"last");
      }
      $('#showPagosChe').jqGrid('footerData', 'set', {
        Che_Obs:"<div style='text-align:right;'>TOTAL:</div>",
        Che_Val: $('#showPagosChe').jqGrid('getCol', 'Che_Val', true, 'sum')
      },true);

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

function actualizarTotalesSG(){
	//obtener todos los ids para buscar valores de debe y haber
	let ids= $('#searchGrid').jqGrid('getDataIDs');

  let abonos=0,saldos=0,tot=0;
	for(let i=0; i< ids.length; i++){
    let reg_pago = $('#searchGrid').jqGrid('getRowData',ids[i]);

    tot=tot+parseFloat(reg_pago.Asi_Val);
    abonos=abonos+parseFloat(reg_pago.Abono);
    if(!isNaN(reg_pago.Saldo)){
      saldos=saldos+parseFloat(reg_pago.Saldo);
    }
	}

  $('#searchGrid').jqGrid('footerData', 'set', { vencimiento:"TOTALES:",Asi_Val: $('#searchGrid').jqGrid('getCol', 'Asi_Val', false, 'sum') });
  $('#searchGrid').jqGrid('footerData', 'set', { Asi_Val: ""+tot });
  $('#searchGrid').jqGrid('footerData', 'set', { Abono: ""+abonos });
  $('#searchGrid').jqGrid('footerData', 'set', { Saldo: ""+saldos });
}

function selectProveedor(proveedor){
  $("#Prv_Cod").val(proveedor.Prv_Cod);
  $("#Prs_Ced").val(proveedor.Prs_Ced);
  $("#nombre").val(proveedor.nombre);
  $("#Prs_Dir").val(proveedor.Prs_Dir);
  $('#proveedoresDialog').dialog('close');
  $('#searchGrid').Search('#searchCcpp','getFactsProvee');
}

function delProveedor(proveedor){
  $("#Prv_Cod").val("");
  $("#Prs_Ced").val("");
  $("#nombre").val("");
  $("#Prs_Dir").val("");
  $('#searchGrid').Search('#searchCcpp','getFactsProvee');
}

function cambioPreiodoSearch(parm_peri){
  if(parm_peri==='peri'){
	  if($('#sel_ven').val()==='ini')
	{
			$('#txt_fec_ini').attr('disabled',false);
			$('#txt_fec_fin').attr('disabled',false);
	}else{
		$('#txt_fec_ini').attr('disabled',true);
		$('#txt_fec_fin').attr('disabled',true);
	}
    $("#txt_fec_ini").dateLimits($("#sel_ven option:selected").attr("data-inicio"),$("#sel_ven option:selected").attr("data-fin"));
    $("#txt_fec_ini").val($("#sel_ven option:selected").attr("data-inicio"));
    $("#txt_fec_fin").dateLimits($("#txt_fec_ini").val(),$("#sel_ven option:selected").attr("data-fin"));
    $("#txt_fec_fin").val($("#sel_ven option:selected").attr("data-fin"));
  }else{
    $("#txt_fec_fin").dateLimits($("#txt_fec_ini").val(),$("#sel_ven option:selected").attr("data-fin"));
  }
}

function imprimir_ccpp(){
  // $('#imprimir_ccpp').printElement();
  $('#tabla_export').html("");
  $.post( "",{getReportAbono:true,sel_ven:$("#sel_ven").val(),op_opciones:$("#op_opciones2").val(), Prv_Cod:$("#Prv_Cod").val(),txt_fec_fin:$("#txt_fec_fin").val(),txt_fec_ini:$("#txt_fec_ini").val()}, function( response ) {
    if(response['success']===true){
      $('#tabla_export').html(""+response['html']);
      $('#imprimir_ccpp').printElement();
    }else{$.alert(response['message']);}
  },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });
}
function exportar_ccpp(){
  $('#tabla_export_ex').html("");
  $.post( "",{getReportAbono:true,sel_ven:$("#sel_ven").val(),op_opciones:$("#op_opciones2").val(), Prv_Cod:$("#Prv_Cod").val(),txt_fec_fin:$("#txt_fec_fin").val(),txt_fec_ini:$("#txt_fec_ini").val()}, function( response ) {
    if(response['success']===true){
      $('#tabla_export_ex').html(""+response['html']);
      $.downloadFile($.exportarExcelBlob($('#exportar').html(), 'CCPP'), 'CCPP_' + $.getDate() + '.xls');
    }else{$.alert(response['message']);}
  },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });
}
