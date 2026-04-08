<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por lotes
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_ccpp.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
/** 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Che;
/**
* Evita el reenvio 
*/
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($provAjax)){ 
   $data=filter_input_array(INPUT_GET);
   $data["Emp_Cod"]=$Ses_Emp_Cod;   
    $contar = $obBD_con1->getRowConsulta(3, $data, $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $data["limits"]=$pagination['limits'];
    if($contar['total']>0)
        $responce['rows'] =  $obBD_con1->getArrayConsulta(3, $data, $obBD_conexion);	    
    echo json_encode($responce);exit();
}
if(isset($provAjax)){
    $contar = $obBD_con1->getRowConsulta(1, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows'] =  $obBD_con1->getArrayConsulta(1, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);	    
    echo json_encode($responce);exit();
}
if(isset($ajaxComprobante)){ 
    $contar = $obBD_con1->getRowConsulta(2, $Ses_Emp_Cod.'*'.$Pvr_Cod.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows'] =  $obBD_con1->getArrayConsulta(2, $Ses_Emp_Cod.'*'.$Prv_Cod.'*'.$pagination['limits'], $obBD_conexion);	    
    echo json_encode($responce);exit();
}
if(isset($ajaxSubgrid)){ 
    $contar = $obBD_con1->getRowConsulta(1, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], 1, 5);
    $responce=$pagination['data'];
    $responce['rows'] =  $obBD_con1->getArrayConsulta(1, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);	    
    echo json_encode($responce);exit();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/basic.php"); ?>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid.php")?> 
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                <style>
                    
                </style>
	</HEAD>
<BODY>
<div id="set1">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table" style="table-layout:fixed;">
	<tr class="BarraTitulo">
            <td colspan="2" height="10">&raquo; Registrar Cancelaci&oacute;n por Lotes<?Php echo $periodo; ?></td>
        </tr>
        <tr>
            <td>
                
             <FIELDSET>
		<LEGEND>
                    <label class="Titulos2">Seleccionar Proveedor</label>
		</LEGEND>
                    <form id="provFormTemp">
                       <div class="segmento">Cédula/R.U.C.:</div>                          
                       <div class="datasegmento">

                               <input id="docu" name="search" maxlength="13" onkeydown='if (event.keyCode === 13) $.SearchOrDialog("#provDialog",selectProvee);' type="text" class="search ui-corner-all" placeholder="Ingrese Cedula/R.U.C. ..." title="Buscar Proveedor Por Documento o Descripción" autofocus />
                               <input type="text" name="op_opciones" value="c" style="display: none;" /> 
                               <input type="hidden" name="Prv_Cod" value="" />
                               <a onclick="$('#provDialog').dialog('open');//$('#docu').removeAttr('readOnly');" title="Búsqueda de Proveedores"  class="btn btn-success btn-mini"><i class=" icon-check icon-white" ></i></a>

                       </div>
                    </form>
                    <div class="segmento">Proveedor:</div><div  class="datasegmento"><input id="lblProv" type="text" class="label ui-widget-content ui-corner-all" readonly /></div><br />
                    <div class="segmento">Dirección:</div><div  class="datasegmento"><input id="lblDirec" type="text" class="label ui-widget-content ui-corner-all" readonly /></div>
             </FIELDSET>    
              
             
               
            </td>
            <td>
                <FIELDSET>
		<LEGEND>
                    <label class="Titulos2">Filtros</label>
		</LEGEND>
                  <form id="formCompTemp">
                    <input type="hidden" name="Prv_Cod" value="" />  
                    <div>
                        <div class="segmento">Vencidos: <input name="op_opciones" type="radio" value="v" checked alt="" /></div> 
                        <div class="segmento">Por Vencer: <input name="op_opciones" type="radio" value="p" alt="" /></div>
                        <div class="segmento">Todos: <input name="op_opciones" type="radio" value="t" alt="" /></div>
                    </div>
                    <div>
                        <div class="segmento">Por Fecha: <input onchange="$('#rangeDates').toggleClass('disabled').find('input').toggleAttr('disabled')" name="op_fecha" type="checkbox" value="true" offval="false" /></div> 
                        <div id="rangeDates" class="datasegmento" style="text-align: center;">
                            Desde:<input name="txt_fec_ini" type="text" id="txt_fec_ini" size="10" class="focus ui-widget-content ui-corner-all" style="text-align: center;"  />
                            Hasta:<input name="txt_fec_fin" type="text" id="txt_fec_fin" size="10" class="focus ui-widget-content ui-corner-all" style="text-align: center;"  />
                        </div>
                    </div>
                    <div style="text-align: center;">
                        <button type="button" onclick="this.form.submit()" class="btn btn-success" style="height: 27px;" title="Filtrar Documentos" >
                           <i class="icon-search icon-white"></i>
                           <span>Buscar</span>
                           </button>
                    </div>
                  </form>
             </FIELDSET> 
            </td>
        </tr>
      <tr>
          
          <td colspan="2" height="389" align="left" valign="top"> 
            <FIELDSET>
		<LEGEND>
			<label class="Titulos2">Resultados de la busqueda</label>
		</LEGEND>
                <div id="grillaComp">   
                                    <table id="list"></table>
                                    <div id="listPager"></div>
                </div>
                 <script type="text/javascript">  
                        function selectProvee(data){ 
                            $("#lblProv").val(data['proveedor']);
                            $("#lblDirec").val(data['Prs_Dir']);
                            $("input[name='Prv_Cod']").val(data['Prv_Cod']);
                            $("#docu").val(data['Prs_Ced']);        
                            $("#provDialog").dialog("close");        
                            $('#list').Search('#formCompTemp','ajaxComprobante');
                            //$("#docu").attr("readOnly","readOnly");
                        }
                        function clearFooter(){ 
                            var $footRow = $("#grillaComp .ui-jqgrid-sdiv .footrow");
                            var $name = $footRow.find('>td[aria-describedby="list_Fac_Num"]'),
                            $invdate = $footRow.find('>td[aria-describedby="list_act1"]'),
                            width2 = $name.width()  + $invdate.outerWidth();
                            $invdate.css("display", "none");
                            $name.attr("colspan", "2").width(width2);

                            $footRow.find('>td[aria-describedby="list_subgrid"]').css("border-right-color", "transparent");                            
                            $footRow.find('>td[aria-describedby="list_Id_Com"]').css("border-right-color", "transparent");
                            $footRow.find('>td[aria-describedby="list_Com_Fec"]').css("border-right-color", "transparent");
     
                            $footRow.find('>td[aria-describedby="list_subgrid"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="list_Id_Com"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="list_Com_Fec"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="list_Com_Fec2"]').css("background-color", "white");
                            $footRow.find('>td[aria-describedby="list_Fac_Num"]').css("background-color", "white");
                        }
                        function updateTotals(grid){ 
                            var abonos=0,saldos=0,rows= grid.jqGrid('getRowData');
                            for(var i=0;i<rows.length;i++){abonos=abonos+parseFloat(rows[i]['Abono']);saldos=saldos+parseFloat(rows[i]['Saldo']);}
                            grid.jqGrid('footerData', 'set', { Com_Fec2:"<div style='text-align:right;'>TOTALES:</div>",Com_Val: grid.jqGrid('getCol', 'Com_Val', false, 'sum') });
                            grid.jqGrid('footerData', 'set', { Abono: ""+abonos });
                            grid.jqGrid('footerData', 'set', { Saldo: ""+saldos });
                            grid.jqGrid("footerData", "set", {Fac_Num: "<div style='text-align:right;'>TOTAL A PAGAR:</div>",Pago:grid.jqGrid('getCol', 'Pago', false, 'sum')});
                        }
                    $(document).ready(function () {  
                        $.createDateRange('#txt_fec_ini','#txt_fec_fin'); 
                        var compGrid=$("#list");
                        compGrid.jqGrid({
                            url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                            mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },
                            //postData: $("#form1").getData("ajaxGrid"),
                            autowidth : true, shrinkToFit: true, height: 270,
                            cmTemplate: {sortable:false},
                            colModel: [
                                { label: 'Cód.Int.', name: 'Com_Cod', key: true, width: 15,align:"center", hidden:true },                                
                                { label: 'No. Compr.', name: 'Id_Com',align:"center", width: 40 },                      
                                { label: 'Fecha', name: 'Com_Fec',align:"center", width: 35  },
                                { label: 'Fecha Venc.', name: 'Com_Fec2', align:"center", width: 35},
                                { label: 'Total', name: 'Com_Val', width: 45, align: 'right', formatter:'currency', decimalPlaces: '2', summaryRound: 2,
                                        formatoptions: {prefix:'$ ', thousandsSeparator:'.'},summaryTpl: "Total: {0}",summaryType: "sum" 
                                },
                                { label: 'Abono', name: 'Abono', width: 45, align: 'right', decimalPlaces: '2', summaryRound: 2,formatoptions: {prefix:'$ ', thousandsSeparator:'.'},
                                        formatter: function (cellValue, options, rowObject) { if(!parseFloat(rowObject.Abono)) rowObject.Abono=0;return $.fn.fmatter.call(this, "currency",rowObject.Abono, options);},
                                        unformat: function (cellValue, options, cell) {var opt = $.extend(true, {}, options);opt.colModel.formatter = "currency";delete opt.colModel.unformat;return $.unformat.call(this, cell, opt);},
                                        summaryTpl: "Total: {0}",summaryType: "sum" // set the formula to calculate the summary type 
                                }, 
                                { label: 'Saldo', name: 'Saldo', width: 45, align: 'right',  decimalPlaces: '2', summaryRound: 2,
                                        formatoptions: {prefix:'$ ', thousandsSeparator:'.'},
                                        formatter: function (cellValue, options, rowObject) {
                                            if(!parseFloat(rowObject.Saldo)){
                                            rowObject.Saldo = parseFloat(rowObject.Com_Val) - parseFloat(rowObject.Abono);
                                            }return $.fn.fmatter.call(this, "currency", rowObject.Saldo, options);
                                        },
                                        unformat: function (cellValue, options, cell) {var opt = $.extend(true, {}, options);opt.colModel.formatter = "currency";delete opt.colModel.unformat;return $.unformat.call(this, cell, opt);},
                                        summaryTpl: "Total: {0}",summaryType: "sum" // set the formula to calculate the summary type 
                                },
                                { label: 'No. Fact.', name: 'Fac_Num', width: 60, align:"center",resizable:false},
                                { label:'<center><i class="ui-icon ui-icon-circle-check"></i></center>', name: 'act1', width: 10, align: 'center',viewable: false, formatter: 'checkbox',
                                    formatoptions: { disabled: false },resizable:false
                                },
                                { classes:'columnHighlight2',label: 'A Pagar', name: 'Pago', width: 45, align: 'right', formatter:'currency', decimalPlaces: '2', summaryRound: 2,
                                        formatoptions: {prefix:'$ ', thousandsSeparator:'.'},
                                        summaryTpl: "Total: {0}",summaryType: "sum" // set the formula to calculate the summary type 
                                }    
                            ],    
                            footerrow: true, userDataOnFooter: false,
                            rowNum: 10000000, pager: "#listPager", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass",pgbuttons: false,pgtext: null,                           
                            loadComplete: function () {                                 
                                var grid=$(this), iCol = grid.getColumnIndexByName('act1'), rows = this.rows, i, c = rows.length;
                                updateTotals(grid);                                
                                for (i = 0; i < c; i += 1) {                                    
                                    $(rows[i].cells[iCol]).click(function (e) {                                        
                                        var id = $(e.target).closest('tr')[0].id, isChecked = $(e.target).is(':checked');
                                        if(isChecked){grid.jqGrid("setCell", id, "Pago", grid.jqGrid('getCell',id,'Saldo'));}
                                        else{grid.jqGrid("setCell", id, "Pago", "0.00");}
                                        updateTotals(grid);    
                                    });
                                }
                            },                            
                            subGridOptions: {
                                "plusicon"  : "ui-icon-triangle-1-e","minusicon" : "ui-icon-triangle-1-s","openicon"  : "ui-icon-arrowreturn-1-e","reloadOnExpand" : false,"selectOnExpand" : true
                            },subGrid: true,multiselect: false,
                            subGridRowExpanded: function(subgrid_id, row_id) {
                                var subgrid_table_id = subgrid_id+"_t";         
                                $("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table>");
                                $("#"+subgrid_table_id).jqGrid({
                                        url:"<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>?ajaxSubgrid=="+row_id,datatype: "json",regional : 'es',
                                        autowidth : true, shrinkToFit: true,cmTemplate: {sortable:false},//colNames: ['No','Item','Qty','Unit','Line Total'],
                                        colModel: [
                                                {label:'Cod.Int.',name:"Prv_Cod",width:80,key:true,align:"center"},
                                                {label:'Cedula',name:"Prs_Ced",width:120},
                                                {label:'Proveedor',name:"proveedor",width:270},
                                                {label:'Fax',name:"Prv_Fax",width:100},
                                                {label:'Direccion',name:"Prs_Dir",width:370}
                                        ],
                                        rowNum:10000000, pager: "",height: '100%'
                                });                                
                            }
                        });                        
                        compGrid.navGrid('#listPager',{ edit: false, add: false, del: false, search: false, refresh: true, view: true, position: "left", cloneToTop: false });
                        compGrid.jqGrid('bindKeys');                        
                        clearFooter();
                    });  
               </script>
	</FIELDSET>
        </td>
      </tr>    
    </table>
   	
</div>
    
<!--INICIO DEL DIALOGO BUSCAR PROVEEDOR--> 
    <div id="provDialog" title="Búsqueda de Proveedores">  
       <form> 
        <fieldset>
		<legend><label class="Titulos2">Búsqueda de Proveedor</label></legend>
		<table border="0">
                   <tr>
			  <td width="205"><input name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" />
			  <span class="LetraNegra"><strong>Apellido</strong></span></td>
			  <td width="266"><input name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" />
				<span class="LetraNegra"><strong>Cédula/R.U.C.</strong></span></td>
                    </tr>
                </table>
                <table height="36" border="0" cellpadding="0" cellspacing="0">                    
                      <tr>
                          <td width="80" height="28" class="BarraBusqueda" style="border-right: 0px;padding-right: 10px;padding-left: 10px;"><div align="right"><strong>B&uacute;squeda</strong></div></td>
                          <td width="387" class="BarraBusqueda" style="border-left: 0px;"><input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" style="text-transform:uppercase" placeholder="Ingrese palabra a buscar..." autofocus /><input type="text" style="display:none"/></td>
                          <td width="109" align="center">
                              <button type="button" onclick="this.form.submit()" class="btn btn-success fileinput-button" title="Buscar cuenta" >
                           <i class="icon-search icon-white"></i>
                           <span>Buscar</span>
                           </button></td>
                      </tr>                    
                </table>
        </fieldset>  
       </form>
    </div>
    <script type="text/javascript">
        $(document).ready(function() {               
                $.createSearchDialog('#provDialog',[
                        { label: 'Cód.Int.', name: 'Prv_Cod', key: true,hidden:true,viewable: true },                                
                        { label: 'Cédula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
                        { label: 'Proveedor', name: 'proveedor', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
                        { label: 'Dirección', name: 'Prs_Dir',hidden:true,viewable: true },                      
                            { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                                formatter:function (cellvalue, options, rowObject) { 
                                    var clic='selectProvee($("#provGrid").jqGrid("getRowData",'+rowObject.Prv_Cod+'))';
                                    return  '<span class="btn btn-success btn-mini" title="Seleccionar" onclick=\''+clic+'\'><i class="icon-arrow-right icon-white"></span>'; 
                                }
                            }
                    ]);  
                    $("#rangeDates").toggleClass("disabled").find("input").toggleAttr('disabled');                    
        }); 
    </script>
<!-- FIN DEL DIALOGO PROVEEDOR-->


</BODY>
</HTML>