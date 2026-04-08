//inicializamos plugins y componentes a utilizar
$( document ).ready(function() {
  $( "#editDialog" ).createDialog({width:500,height:480,icon:'pencil'});
  $( "#detAportacion" ).createDialog({width:700,height:500,icon:'list'});
  $( "#successDialog" ).createDialog({width:500,height:200,icon:'print'});

  $('#btnModificar').on("click", function(){
    saveFormMod();
  });

  $("#tabs").tabs();
  $("#tabs_apo").tabs();
  $("#Prs_Ced").blur(function(){
    comprobarForm();
  });
  // creamos lo0s datepickers a utilizar
  $.createDatePickers("#Prs_Fec");
  $.createDatePickers("#Soc_Fec");
  $.createDatePickers("#Soc_Fec_m");
  $.createDatePickers("#Com_Fec");
  $.createDatePickers("#Che_Fec");
  $.createDatePickers("#Apo_ini_fec");
  $.createDatePickers("#Com_Fec_mod");
  $.createDatePickers("#Che_Fec_mod");
  $.createDatePickers("#Apo_Fec_m");

  $("#Ciu_Cod").createChosen('input-xs', {
    template: function (text, templateData) {
      if (typeof templateData === 'undefined')
      console.log(text, templateData);
      return [
        "<div>" + text + "</div>",
        "<div style='font-size:11px;'><b>Provincia:</b> " + templateData.provincia + " <b>Pais:</b> " + templateData.pais + "</div>"
      ].join("");
    }
  });
  $("#Ciu_Cod_m").createChosen('input-xs', {
    template: function (text, templateData) {
      if (typeof templateData === 'undefined')
      console.log(text, templateData);
      return [
        "<div>" + text + "</div>",
        "<div style='font-size:11px;'><b>Provincia:</b> " + templateData.provincia + " <b>Pais:</b> " + templateData.pais + "</div>"
      ].join("");
    }
  });

  $('#apo_asien').jqGrid({
      datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },
      width:590, height:100,postData: {CheListAjax:true},caption:'Asiento Contable',
      cmTemplate: {sortable:false},colModel: [
          { label: 'Cód.Int.', name: 'Asi_Cod', key: true, width: 15,align:"center", hidden:true },
                  { label: 'Tipo', name: 'Asi_Deh', hidden:true },
                  { label: 'Código', name: 'Pld_Cdc', width: 45 },
                  { label: 'Cuenta', name: 'Pld_Des', width: 130  },
                  { label: 'Glosa', name: 'Asi_Glo', width: 130},
                  { label: 'Debe', name: 'Debe', width: 65, align: 'right', formatter:'currency', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryType: "sum"},
                  { label: 'Haber',name: 'Haber',width: 65,align: 'right', formatter:'currency', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryType: "sum"}
      ],
      loadComplete: function (data) { $(this).jqGrid('footerData', 'set', { Glosa:"<div style='text-align:right;'>TOTALES:</div>",Debe: $(this).jqGrid('getCol', 'Debe', true, 'sum'),Haber: $(this).jqGrid('getCol', 'Haber', true, 'sum') },true); },
      rowNum: 10000, gridview: true, viewrecords: true,footerrow: true, userDataOnFooter: false
  }); $.clearFooterDiario("#apo_asien");

});

//funcion para agregar aportaciones
function totalSocios(){
  $("#aport_soc").addClass("hidden");
  $("#aport_soc").moveComp("#tot_soc").updateGridsSizes();
}

//linpiamos el formulario para la creacion de socios
function limpiar() {
  $("[id^='Prs_']").val('');
  $("[id^='Soc_']").val('');
  $('#Ciu_Cod').val('').trigger('chosen:updated');
  $('#Prs_Sex').prop('selectedIndex', 0);
  $('#Ide_Des').prop('selectedIndex', 0);
  $("#Apo_ini_val").val("");
  $("#Apo_ini_fec").attr("disabled","disabled");
  $("#Apo_ini_val").attr("disabled","disabled");
  $('#checkApoIni').prop('checked', false);
}

$.fn.createInputAporte = function(element,tipo){
    var jgrid=this, rowId=$(element).closest('tr.jqgrow').attr('id'), tip=jgrid.jqGrid('getCell',rowId,'Det_Tip');
    $(element).parent().removeAttr("title");
    if(tip===tipo){
        $(element).on('change', function(){ $(this).val($.toFixed($(this).val())); jgrid.updateGridDiario();/*$('#Apo_Val').val($(this).val());*/});

        $(element).attr('onkeypress','return  validar_decimal(event)');
        if(parseFloat($(element).val())===0) $(element).val("");
        $(element).css('text-align', 'right').focus();
    }else{ $(element).parent().html(''); };
};

//funcion para generar botones en los asientos de mnodificar aportaciones
function btns_asiento2(row,opt){
  if(row.grid_tipp==="acs"){
    return "-";
  }else{
    if(opt==="del"){
      if(row.grid_tipp==="sirm"){
        return '<button onclick="$(\'#mod_apo_asien\').jqGrid(\'delRowData\',\''+row.Pld_Cod+'\');$(\'#mod_apo_asien\').updateGridDiario();" class="btn btn-danger btn-xs" title="Eliminar"><i class="glyphicon glyphicon-remove"></></button>';
      }else{
        return "-";
      }
    }
    if(opt==="sel"){
      return '<button onclick="select_other_plan(\''+row.Pld_Cod+'\',\''+row.Det_Tip+'\');$(\'#cuenmod2Dialog\').dialog(\'open\');" class="btn btn-success btn-xs" title="Cambiar cuenta"><i class="glyphicon glyphicon-edit"></></button>';
    }
  }
}

//funcion para generar botones en los asientos al insertar aportaciones
function btns_asiento(row,opt){
  if(row.grid_tipp==="acs"){
    return "-";
  }else{
    if(opt==="del"){
      if(row.grid_tipp==="sirm"){
        return '<button onclick="$(\'#comp\').jqGrid(\'delRowData\',\''+row.Pld_Cod+'\');$(\'#comp\').updateGridDiario();" class="btn btn-danger btn-xs" title="Eliminar"><i class="glyphicon glyphicon-remove"></></button>';
      } else {
        return "-";
      }
    }
    if(opt==="sel"){
      return '<button onclick="select_other_plan(\''+row.Pld_Cod+'\',\''+row.Det_Tip+'\');$(\'#cuen2Dialog\').dialog(\'open\');" class="btn btn-success btn-xs" title="Cambiar cuenta"><i class="glyphicon glyphicon-edit"></></button>';
    }
  }
}

//variables utilizadas como globales para cambiar el id de la fila
//en los asientos de crear aportacion y modificar aportacion
//al cambiar la cuenta
var idrow = "";
var dttip = "";

//asignamos el valor del id actual de la fila y el tipo de campo(debe o haber)
//al cambiar de plan un asiento
function select_other_plan(idrow1,detipo1){
  idrow = idrow1;
  dttip = detipo1;
}

// cambiamos el rango de fechas disponilobes de acuerdo al periodo contable seleccionado
//al agregar o modificar aportaciones
function setPeriodo(){
    var perio_cont=getPeriodo();
    $("#Com_Fec").dateLimits(perio_cont["Pec_Fei"],perio_cont["Pec_Fef"]);
    var perio_cont2=getPeriodo2();
    $("#Com_Fec_mod").dateLimits(perio_cont2["Pec_Fei"],perio_cont2["Pec_Fef"]);
}
function getPeriodo(){ return $('#Pec_Cod').val()===''?{Pec_Cod:null}:$('#Pec_Cod option:selected').data(); }
function getPeriodo2(){ return $('#Pec_Cod_mod').val()===''?{Pec_Cod:null}:$('#Pec_Cod_mod option:selected').data(); }

//creamos modales al cargar la pagina
$(function() {
  if($('#mod_apo_asien').length===1){
    gridCompAsienMod=$("#mod_apo_asien");
    gridCompAsienMod.createGrid({
        datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },
        height:100,postData: {CheListAjax:true},caption:'Asiento Contable',
        cmTemplate: {sortable:false},colModel: [
          { label:'<center><i class="ui-icon ui-icon-pencil"></i></center>', name: 'act0', width: 30, align: 'center',
              formatter:function (cellvalue, options, rowObject) {
                return btns_asiento2(rowObject, "sel");
              }, unformat:$.unformatCellHtml

          },
          { label: '&nbsp;', name: 'grid_tipp', hidden:true },
          { label: 'Cód.Int.', name: 'Pld_Cod',key: true, width: 20,align:"center", hidden:false },
          { label: 'Tipo', name: 'Det_Tip', hidden:true },
          { label: 'Codigo', name: 'Pld_Cdc', width: 45 },
          { label: 'Cuenta', name: 'Pld_Des', width: 150  },
          { label: 'Glosa', name: 'GlosaMod', width: 150,editable:true },
          { label: 'Debe', name: 'DebeMod', width: 50, align: 'right', formatter:'currency', editable:true,
               formatoptions: { defaultValue:'' },
               editoptions: { dataInit: function (element) { gridCompAsienMod.createInputDiario2(element,"D","Det_Tip");} }
          },
          { label: 'Haber', name: 'HaberMod', width: 50,align: 'right', formatter:'currency', editable:true,
               formatoptions: { defaultValue:'' },
               editoptions: { dataInit: function (element) { gridCompAsienMod.createInputDiario2(element,"H","Det_Tip");} }
          },
          { label:'<center><i class="ui-icon ui-icon-trash"></i></center>', name: 'act1', width: 30, align: 'center',viewable: false,
          formatter:function (cv, opts, rObj) {
            return btns_asiento2(rObj, "del");
          }, unformat:$.unformatCellHtml  }
        ],
        loadComplete: function (data) {
          $(this).jqGrid('footerData', 'set', {
            GlosaMod:"<div style='text-align:right;'>TOTALES:</div>",
            DebeMod: $(this).jqGrid('getCol', 'DebeMod', true, 'sum'),
            HaberMod: $(this).jqGrid('getCol', 'HaberMod', true, 'sum')
          },true);
        },
        rowNum: 10000, gridview: true, viewrecords: true,footerrow: true, userDataOnFooter: false
    });
    $.clearFooterDiario("#mod_apo_asien",true,'#Apo_Val_mod');
  }

  if($('#cuen2Dialog').length===1)
  $.createSearchDialog('cuen2Dialog',[
    {label: 'Cod. Int.', name: 'Pld_Cod', width: 20, align: "left"},
    {label: 'C&oacute;digo', name: 'Pld_Cdc', width: 50, align: "left"},
    {label: 'Cuenta Contable', name: 'Pld_Des', width: 100, align: "left"},
    { label:'&nbsp;', name: 'plsel', width: 15, align: 'center',viewable: false,title:false,
      formatter:function (cellvalue, options, rowObject) {
        return '<button onclick="asign_other_plan(\''+rowObject.Pld_Cod+'\',\''+rowObject.Pld_Cdc+'\',\''+rowObject.Pld_Des+'\')" class="btn btn-success btn-xs" title="Seleccionar cuenta"><i class="glyphicon glyphicon-ok"></></button>';
      }
    }
     ],null,null,null,null,{ title:'Cuenta', options:[{label:'&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;',value:'d'},{label:'&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;',value:'c'}] })
     .find('.form-group-options').append('<div class="col-md-4"> <label class="control-label label-xs">Plan de Cuentas:</label><input name="Periodo" type="text" size="6" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /> <input name="Pec_Cod" type="hidden" /><input name="Asi_Cod" type="hidden" /></div>');

  // DIALOG BUSCAR CUENTAS
  if($('#cuenDialog').length===1)
  $.createSearchDialog('cuenDialog',[
    {label: 'Cod. Int.', name: 'Pld_Cod', width: 20, align: "left"},
    {label: 'C&oacute;digo', name: 'Pld_Cdc', width: 50, align: "left"},
    {label: 'Cuenta Contable', name: 'Pld_Des', width: 100, align: "left"},
    { label:'&nbsp;', name: 'plsel', width: 15, align: 'center',viewable: false,title:false,
      formatter:function (cellvalue, options, rowObject) {
        return '<button onclick="agregarCuenta(\''+rowObject.Pld_Cod+'\',\'D\',\''+rowObject.Pld_Cdc+'\',\''+rowObject.Pld_Des+'\')" class="btn btn-success btn-xs" title="Enviar al Debe">D</button>';
      }
    },
    { label:'&nbsp;', name: 'plsel', width: 15, align: 'center',viewable: false,title:false,
      formatter:function (cellvalue, options, rowObject) {
        return '<button onclick="agregarCuenta(\''+rowObject.Pld_Cod+'\',\'H\',\''+rowObject.Pld_Cdc+'\',\''+rowObject.Pld_Des+'\')" class="btn btn-success btn-xs" title="Enviar al Haber">H</button>';
      }
    }
      ],null,null,null,null,{ title:'Cuenta', options:[{label:'&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;',value:'d'},{label:'&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;',value:'c'}] })
      .find('.form-group-options').append('<div class="col-md-4"> <label class="control-label label-xs">Plan de Cuentas:&nbsp;</label><input id="Index" name="Index" type="hidden" /><input name="Pec_Cod" type="hidden" /><input name="Periodo" type="text" size="6" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /></div>');
  // DIALOG BUSCAR CUENTAS 2

  if($('#cuenmod2Dialog').length===1)
  $.createSearchDialog('cuenmod2Dialog',[
    {label: 'Cod. Int.', name: 'Pld_Cod', width: 20, align: "left"},
    {label: 'C&oacute;digo', name: 'Pld_Cdc', width: 50, align: "left"},
    {label: 'Cuenta Contable', name: 'Pld_Des', width: 100, align: "left"},
    { label:'&nbsp;', name: 'plsel', width: 15, align: 'center',viewable: false,title:false,
      formatter:function (cellvalue, options, rowObject) {
        return '<button onclick="asign_other_plan_mod(\''+rowObject.Pld_Cod+'\',\''+rowObject.Pld_Cdc+'\',\''+rowObject.Pld_Des+'\')" class="btn btn-success btn-xs" title="Seleccionar cuenta"><i class="glyphicon glyphicon-ok"></></button>';
      }
    }
     ],null,null,null,null,{ title:'Cuenta', options:[{label:'&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;',value:'d'},{label:'&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;',value:'c'}] })
     .find('.form-group-options').append('<div class="col-md-4"> <label class="control-label label-xs">Plan de Cuentas:</label><input name="Periodo" type="text" size="6" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /> <input name="Pec_Cod" type="hidden" /><input name="Asi_Cod" type="hidden" /></div>');

  // DIALOG BUSCAR CUENTAS
  if($('#cuenmodDialog').length===1)
  $.createSearchDialog('cuenmodDialog',[
    {label: 'Cod. Int.', name: 'Pld_Cod', width: 20, align: "left"},
    {label: 'C&oacute;digo', name: 'Pld_Cdc', width: 50, align: "left"},
    {label: 'Cuenta Contable', name: 'Pld_Des', width: 100, align: "left"},
    { label:'&nbsp;', name: 'plsel', width: 15, align: 'center',viewable: false,title:false,
      formatter:function (cellvalue, options, rowObject) {
        return '<button onclick="agregarCuentaMod(\''+rowObject.Pld_Cod+'\',\'D\',\''+rowObject.Pld_Cdc+'\',\''+rowObject.Pld_Des+'\')" class="btn btn-success btn-xs" title="Enviar al Debe">D</button>';
      }
    },
    { label:'&nbsp;', name: 'plsel', width: 15, align: 'center',viewable: false,title:false,
      formatter:function (cellvalue, options, rowObject) {
        return '<button onclick="agregarCuentaMod(\''+rowObject.Pld_Cod+'\',\'H\',\''+rowObject.Pld_Cdc+'\',\''+rowObject.Pld_Des+'\')" class="btn btn-success btn-xs" title="Enviar al Haber">H</button>';
      }
    }
      ],null,null,null,null,{ title:'Cuenta', options:[{label:'&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;',value:'d'},{label:'&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;',value:'c'}] })
      .find('.form-group-options').append('<div class="col-md-4"> <label class="control-label label-xs">Plan de Cuentas:&nbsp;</label><input id="Index" name="Index" type="hidden" /><input name="Pec_Cod" type="hidden" /><input name="Periodo" type="text" size="6" readonly style="text-align: center;display: inline-block;width: auto;" class="form-control input-xs" /></div>');


  if($('#comp').length===1){
      gridCompAsien=$("#comp");
      gridCompAsien.createGrid({
          colModel: [
              { label:'&nbsp;', name: 'act0', width: 30, align: 'center',
                  formatter:function (cellvalue, options, rowObject) {
                    return btns_asiento(rowObject, "sel");
                  }, unformat:$.unformatCellHtml

              },
              { label: '&nbsp;', name: 'grid_tipp', hidden:true },
              { label: 'Cód.Int.', name: 'Index', key: true, width: 15, align:"center", hidden:true },
              { label: 'Cód.Int.', name: 'Pld_Cod', width: 20,align:"center", hidden:false },
              { label: 'Tipo', name: 'Det_Tip', hidden:true },
              { label: 'Codigo', name: 'Pld_Cdc', width: 45 },
              { label: 'Cuenta', name: 'Pld_Des', width: 150  },
              { label: 'Glosa', name: 'Glosa', width: 150,editable:true },
              { label: 'Debe', name: 'Debe', width: 50, align: 'right', formatter:'currency', editable:true,
                   formatoptions: { defaultValue:'' },
                   editoptions: { dataInit: function (element) { gridCompAsien.createInputDiario(element,"D","Det_Tip");} }
              },
              { label: 'Haber', name: 'Haber', width: 50,align: 'right', formatter:'currency', editable:true,
                   formatoptions: { defaultValue:'' },
                   editoptions: { dataInit: function (element) { gridCompAsien.createInputDiario(element,"H","Det_Tip");} }
              },
              { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
              formatter:function (cv, opts, rObj) {
                return btns_asiento(rObj, "del");
              }, unformat:$.unformatCellHtml  }
          ], height: 'auto', caption:"Datos del Asiento Contable", footerrow: true, userDataOnFooter: false // set a footer row
      },true, "#compAsienPager", {view:false} ).gridButtonAdd({caption:"Agregar Cuenta", buttonicon:"glyphicon glyphicon-plus", title:'Agregar Cuenta', onClickButton: function (){ $('#Index').val(''); $('#cuenDialog').dialog('open'); }});
      $.clearFooterDiario("#comp",true,'#Apo_Val');
  }
});

// actualizamos lo valores al cambiar en los asientos
function updateValores(){  $("#Apo_Val").val($.toFixed($("#Apo_Val").val())); }

//limpiamos el formulario el formulario de agregar aportaciones y eliminamos los asientos agregados
function limpiarFormComp(){
  $("#Apo_Val").val("");
  $("#Com_Con").val("");
  $('#Pag_Cod').prop('selectedIndex', 0);
  $('#Tipo_comp').prop('selectedIndex', 0);
  $('#Tia_Cod').prop('selectedIndex', 0);
  $('#Bak_Cod').prop('selectedIndex', 0);
  $("#Che_Num").val("");
  $("#Che_Cta").val("");
  $("#comp").jqGrid("clearGridData", true).trigger("reloadGrid");
  //removemos todos los elementos del select para tipo de asiento
  $('#Tia_Cod option').remove();
  //agregamos una opcion sin valor por defecto para el select de tipos de asiento
  $("#Tia_Cod").append('<option value="" selected="selected">Seleccione un tipo de comprobante</option>');
  $("#Tia_Cod").attr("disabled","disabled");
  $("#add_cnt").attr("disabled","disabled");

  //removemos todos los elementos del select para tipo de asiento
  $('#Tipo_comp option').remove();
  //agregamos una opcion sin valor por defecto para el select de tipos de asiento
  $("#Tipo_comp").append('<option value="nada" selected="selected">Seleccione un modo de pago</option>');
  $("#Tipo_comp").attr("disabled","disabled");
}

//limpiamos el formulario de modificar aportaciones y eliminamos los asientos agregados
function limpiarFormCompMod(){
  $("#Apo_Val_mod").val("");
  $("#Com_Con_mod").val("");
  $('#Pag_Cod_mod').prop('selectedIndex', 0);
  $('#Tipo_comp_mod').prop('selectedIndex', 0);
  $('#Tia_Cod_mod').prop('selectedIndex', 0);
  $('#Bak_Cod_mod').prop('selectedIndex', 0);
  $("#Che_Num_mod").val("");
  $("#Che_Cta_mod").val("");
  gridCompAsienMod.jqGrid("clearGridData", true).trigger("reloadGrid");
  //removemos todos los elementos del select para tipo de asiento
  $('#Tia_Cod_mod option').remove();
  //agregamos una opcion sin valor por defecto para el select de tipos de asiento
  $("#Tia_Cod_mod").append('<option value="" selected="selected">Seleccione un tipo de comprobante</option>');
  $("#Tia_Cod_mod").attr("disabled","disabled");

  $("#add_cnt_mod").addClass("hidden");

  //removemos todos los elementos del select para tipo de asiento
  $('#Tipo_comp_mod option').remove();
  //agregamos una opcion sin valor por defecto para el select de tipos de asiento
  $("#Tipo_comp_mod").append('<option value="nada" selected="selected">Seleccione un modo de pago</option>');
  $("#Tipo_comp_mod").attr("disabled","disabled");
}

// cambiamos de plan de cuentas un asiento, cambiando inclusive el id de la fila en al tabla
function asign_other_plan(plcod, plcdc, pldes){
  gridCompAsien.jqGrid('setCell', idrow,'Pld_Cod', plcod);
  gridCompAsien.jqGrid('setCell', idrow,'Det_Tip', dttip);
  gridCompAsien.jqGrid('setCell', idrow,'Pld_Cdc', plcdc);
  gridCompAsien.jqGrid('setCell', idrow,'Pld_Des', pldes);

  gridCompAsien.startGridEdit();
  gridCompAsien.updateGridDiario();
  // $("#"+idrow).attr('id',plcod);

  $("#comp tr[id="+ idrow +"]").attr('id',plcod);

  $("#"+idrow+"_Glosa").attr('id',plcod+"_Glosa");
  if(dttip==="D"){
    $("#"+idrow+"_Debe").attr('rowId',plcod);
    $("#"+idrow+"_Debe").attr('id',plcod+"_Debe");
  }
  if(dttip==="H"){
    $("#"+idrow+"_Haber").attr('rowId',plcod);
    $("#"+idrow+"_Haber").attr('id',plcod+"_Haber");
  }
  $('#cuen2Dialog').dialog('close');
}

//agregamos un nuevo asiento a la tabla en el debe o el Haber
//dependiendo de lo que se escoga
function agregarCuenta(plcod, dttip, plcdc, pldes){
  if (dttip==="D") {
    if(!$("#comp").existsId(plcod)){
      gridCompAsien.jqGrid('addRowData', plcod, {Pld_Cod:plcod,grid_tipp:"sirm",Det_Tip:"D",Pld_Cdc:plcdc,Pld_Des:pldes,Glosa:"",Debe:0,Haber:0},"first");
      gridCompAsien.startGridEdit();
      gridCompAsien.updateGridDiario();
    }
  }
  if (dttip==="H") {
    if(!$("#comp").existsId(plcod)){
      gridCompAsien.jqGrid('addRowData', plcod, {Pld_Cod:plcod,grid_tipp:"sirm",Det_Tip:"H",Pld_Cdc:plcdc,Pld_Des:pldes,Glosa:"",Debe:0,Haber:0},"first");
      gridCompAsien.startGridEdit();
      gridCompAsien.updateGridDiario();
    }
  }
}

//cambiamos de plan de cuentas un asiento, cambiando inclusive el id de la fila en al tabla,
// para modificar aportaciones
function asign_other_plan_mod(plcod, plcdc, pldes){
  console.log("|"+idrow+"|"+plcod+"|"+plcdc+"|"+pldes+"|");
  gridCompAsienMod.jqGrid('setCell', idrow,'Pld_Cod', plcod);
  gridCompAsienMod.jqGrid('setCell', idrow,'Det_Tip', dttip);
  gridCompAsienMod.jqGrid('setCell', idrow,'Pld_Cdc', plcdc);
  gridCompAsienMod.jqGrid('setCell', idrow,'Pld_Des', pldes);

  gridCompAsienMod.startGridEdit();
  gridCompAsienMod.updateGridDiario2();
  // $('#mod_apo_asien tr:eq('+idrow+')').attr('id',plcod);

  $("#mod_apo_asien tr[id="+ idrow +"]").attr('id',plcod);

  // $("#"+idrow).attr('id',plcod);
  $("#"+idrow+"_GlosaMod").attr('id',plcod+"_Glosa");
  if(dttip==="D"){
    $("#"+idrow+"_DebeMod").attr('rowId',plcod);
    $("#"+idrow+"_DebeMod").attr('id',plcod+"_DebeMod");
  }
  if(dttip==="H"){
    $("#"+idrow+"_HaberMod").attr('rowId',plcod);
    $("#"+idrow+"_HaberMod").attr('id',plcod+"_HaberMod");
  }
  $('#cuenmod2Dialog').dialog('close');
}

//agregamos un nuevo asiento a la tabla en el debe o el Haber
//dependiendo de lo que se escoga, para modificar aportaciones
function agregarCuentaMod(plcod, dttip, plcdc, pldes){
  if (dttip==="D") {
    if(!gridCompAsienMod.existsId(plcod)){
      console.log("entro para agregar");
      gridCompAsienMod.jqGrid('addRowData', plcod, {Pld_Cod:plcod,grid_tipp:"sirm",Det_Tip:"D",Pld_Cdc:plcdc,Pld_Des:pldes,GlosaMod:"",DebeMod:0,HaberMod:0},"first");
      gridCompAsienMod.startGridEdit();
      gridCompAsienMod.updateGridDiario2();
    }
  }
  if (dttip==="H") {
    if(!gridCompAsienMod.existsId(plcod)){
      gridCompAsienMod.jqGrid('addRowData', plcod, {Pld_Cod:plcod,grid_tipp:"sirm",Det_Tip:"H",Pld_Cdc:plcdc,Pld_Des:pldes,GlosaMod:"",DebeMod:0,HaberMod:0},"first");
      gridCompAsienMod.startGridEdit();
      gridCompAsienMod.updateGridDiario2();
    }
  }
}

// actualiza los totales de acuerdo a al debe y haber (DebeMod, HaberMod)
// (debido a que existe otra tabla con campos de ID Debe y Haber)
$.fn.updateGridDiario2=function(){
    var total_haber=0,total_debe=0;
    var ids= this.jqGrid('getDataIDs');
    for(var i=0;i<ids.length;i++){
        var tip=this.jqGrid('getCell',ids[i],'Det_Tip'), monto;
        if(tip==='D'){monto=$('#'+ids[i]+'_DebeMod');total_debe +=Number(monto.val());}
        else {monto=$('#'+ids[i]+'_HaberMod');total_haber +=Number(monto.val());}
        if(!parseFloat(monto.val())){monto.val("");}
    };
    this.jqGrid("footerData", "set", {GlosaMod: "<div style='text-align:right;'>TOTALES:</div>", DebeMod:total_debe, HaberMod:total_haber});
    if(this.data('Diff')===true) $('#'+this.attr('id')+'_diferencia').val($.toFixed(Math.abs(total_debe-total_haber)));
    if($.isText(this.data('Com_Val'))) $(this.data('Com_Val')).val($.toFixed(total_debe));
};

//creamos un input para el debe y haber con nombres: DebeMod y HaberMod
//(debido a que existe otra tabla con campos de ID Debe y Haber)
$.fn.createInputDiario2=function(element,tipo){
    var jgrid=this, rowId=$(element).closest('tr.jqgrow').attr('id'), tip=jgrid.jqGrid('getCell',rowId,'Det_Tip');
    $(element).parent().removeAttr("title");
    if(tip===tipo){
        $(element).on('change', function(){ $(this).val($.toFixed($(this).val())); jgrid.updateGridDiario2();});
        $(element).attr('onkeypress','return  validar_decimal(event)');
        if(parseFloat($(element).val())===0) $(element).val("");
        $(element).css('text-align', 'right').focus();
    }else{ $(element).parent().html(''); };
};

//cambiamos a la ventana de agregar aportaciones
function agg_aportaciones(row){
  $("#aport_soc").removeClass("hidden");
  $("#tot_soc").moveComp("#aport_soc").updateGridsSizes();
  $("#Prs_Nom_ap").val(row.nombre);
  $("#Prs_Ced_ap").val(row.Prs_Ced);

  $("#Soc_Cod_apo").val(row.Soc_Cod);
  $("#Prs_Cod_apo").val(row.persona_cod);

  setPeriodo();
}

// cargar subgrilla con aprtaciones de los socios
function cargarSocios(){
  gridCompAsienMod.jqGrid("clearGridData", true).trigger("reloadGrid");
  totalSocios();
  //funcion para cargar la grid con los socios
  $("#tableResult").createGrid({
    postData: $("#frm_bus").getData("sociosAjax"), height: 250,
    colModel: [
      { label: '&nbsp;', name: 'persona_cod', width: 15,align:"center", hidden:true },
      {label: 'Cod. Int.', name: 'Soc_Cod', key:true, width: 30, align: "left"},
      {label: 'C&eacute;dula', name: 'Prs_Ced', width: 50, align: "left",cellattr:function(){return 'style="'+excelFormats.text+'"';}},
      {label: 'Nombre', name: 'nombre', width: 100, align: "left"},
      {label: 'Socio desde', name: 'Soc_Fec', width: 50, align: "left"},
      { label: 'Total de aportaciones', name: 'totapo', width: 80, align: 'right',
      formatter:'currency', formatoptions: {
        prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''
      },summaryType: "sum"},
      {label: 'Genero', name: 'Prs_Sex', width: 50, align: "left"},
      {label: 'Tel&eacute;fono', name: 'Prs_Tel', width: 50, align: "left"},
      {label: 'Correo', name: 'Prs_Cor', width: 65, align: "left"},
      {label: 'Direci&oacute;n', name: 'Prs_Dir', width: 100, align: "left"},
      {label: 'Estado', name: 'Soc_Est', width: 25, align: "left"},
      {label: 'Opciones', name: 'act3', width: 40, align: 'center', viewable: false,
      formatter:function(cellvalue, options, rowObject){
        return $.getGridButton(modificarSocio, rowObject, 'Modificar socio', 'pencil','','info')+'&nbsp;&nbsp;'+$.getGridButton(aportaciones, rowObject, 'Agregar aportacion', 'plus');
      }
    }
  ],
  rowNum:400,gridview: true, viewrecords: true,footerrow: true, userDataOnFooter: false,
  onSelectRow: function(rowid, e) { $("#tableResult").resetSelection();},
  multiselect: false,
loadComplete: function(sociosAjax){
  $('#tableResult tr').each(function () {
    if($(this).find("td").eq(9).text()==="Inactivo"){
      $(this).addClass("cellRed2");
      $(this).addClass("myAltRowClass");
    }
  });
  $('#tableResult').jqGrid('footerData', 'set', {
    Soc_Fec:"<div style='text-align:right;'>TOTAL:</div>",
    totapo: $(this).jqGrid('getCol', 'totapo', true, 'sum')
  },true);
}
}, false, "#tableResultPager");

$("#tableResult").trigger('reloadGrid',[]);
}

//utlizada para permitir solo numeros en los campos de nuemero de cheque y numero de cuenta
function soloNumeros(e){
  // valor = valor.replace(/[^0-9]/g,'');
  var key = window.Event ? e.which : e.keyCode
  return (key >= 48 && key <= 57)
}

//utilizada al crear un socio para habilitar o desabilitar los campos de aportacion inical
function ActivarApoIni(){
  // Apo_ini_fec Apo_ini_val
  if ($("#checkApoIni").prop('checked')) {
    $("#Apo_ini_fec").removeAttr("disabled");
    $("#Apo_ini_val").removeAttr("disabled");
  }else {
  $("#Apo_ini_fec").attr("disabled","disabled");
  $("#Apo_ini_val").attr("disabled","disabled");
  }
}

//exportamos a excel un reporte general de aportaciones de los socios
function exportarAportacion(){
  $('#tablaExportaApoGen').html($('#tableResult').jqGrid('exportGridInnerHTML',{footer:true,bodyBorder:false,removeHiddens:true,removeCols:[6,10,11]}));
  $.downloadFile($.exportarExcelBlob($('#exportarGen').html(), 'Aportaciones_generales'), 'Aportaciones_denerales_' + $.getDate() + '.xls');
}

//generamos una vista de impresion con un reporte general de las aportaciones de los socios
function imprimirAportacion(){
  $('#tablaimpaApoGen').html($('#tableResult').jqGrid('exportGridInnerHTML',{footer:true,generated:false,removeHiddens:true,removeCols:[6,10,11]}));
  $('#imprimirGen').printElement();
}
