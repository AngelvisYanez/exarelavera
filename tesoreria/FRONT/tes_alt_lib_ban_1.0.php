<?php	
/**
* @abstract Permite registrar los cheques 
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-08
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cheque.php');
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

if(isset($cuenAjax)){ 
    $contar = $obBD_con1->getRowConsulta(352, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows']=  $obBD_con1->getArrayConsulta(352, $search.'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);	    
    echo json_encode($responce);exit();
}
if(isset($provAjax)){ 
    $contar = $obBD_con1->getRowConsulta(351, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows'] =  $obBD_con1->getArrayConsulta(351, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);	    
    echo json_encode($responce);exit();
}
if(isset($save)){    
    $rs_buscar =  $obBD_con1->getArrayConsulta(358, $Num_Doc."*".$Ses_Emp_Cod.'*'.$Pld_Cod, $obBD_conexion);
     if(count($rs_buscar)==0){
                /**
		* Inicio de la transaccion
		*/
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
		/** 
		* Mes del comprobante 
        	*/
		$var_mes = explode('-', $Com_Fec);
		$Com_Num = $obBD_con1->codigoComprAutomatic($op, $Pec_Cod, $var_mes[1], $obBD_conexion);		
		/** 
		* Inserci�n del Comprobante 
		*/
		if ($op=="I") { $tabla="cliente"; $campo="Cli_Cod"; } if ($op=="E" || $op=="D") { $tabla="proveedore"; $campo="Prv_Cod"; }
		$obBD_con1->grabarv_registros(sentencias_che(356,$obBD_con1->parametros($Pec_Cod.'*'.$Codigo.'*'.$Com_Num.'*'.$Com_Fec.'*'.$Com_Con.'*'.$Tia_Cod
											.'*'.$Com_Val.'*'.$Com_Obs.'*'.$Com_Tipo.'*'.$campo.'*'.$Num_Doc)),$obBD_conexion->conexion);
		$ultimo = $obBD_con1->insercionid ($obBD_conexion->conexion);	
		/** 
		* Recorre el arreglo de los datos de las cuentas seleccionadas 
		*/
                foreach ($save as $row)
                {
                    if($row['Det_Tip']=='D') {$valor=$row['Debe'];}
                    else {$valor=$row['Haber'];}
                    $obBD_con1->grabarv_registros(sentencias_che(357,$obBD_con1->parametros($ultimo.'*'.$row['Det_Tip'].'*'.$valor.'*'.$row['Pld_Des'].'*'.$row['Glosa'].'*'.
											$row['Pld_Cod'])),$obBD_conexion->conexion);
                }		
		/**
		* Finaliza la transacci�n
		*/
		$responce['success']=$obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
                $responce['message']="No se ha logrado realizar la Trasaccion";
         
     }else{
                $responce['success']=false;
                $responce['message']="El Documento Bancario ya esta Registrado!";
     }
                echo json_encode($responce);
	exit();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/basic.php"); ?>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid.php")?> 
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                <meta http-equiv="X-UA-Compatible" content="IE=edge" />
                <style>                   
                    
                </style>
	</HEAD>
<BODY>
<div id="set1">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Registrar Libro Bancos<?Php echo $periodo; ?></td>
        </tr>
      <tr>
      <td height="389" align="left" valign="top">
          
<!-- INICIO FORMULARIO BUSQUEDA -->
        <form enctype="multipart/form-data" action="<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="post" name= "form1" id= "form1">
            <table width="50%" border="0" cellpadding="0" cellspacing="0">
                <tr>  
                     <td width="350">
            <FIELDSET style="height: 50px;">
		<LEGEND>
                    <label class="Titulos2">Seleccione Periodo Contable</label>
		</LEGEND>
		<table width="100%" height="36" border="0" cellpadding="0" cellspacing="0" >
			<tr>
			  <td width="150" class="BarraBusqueda" style="border-right: 0px;"><div align="right" >Periodo Contable:</div></td>
			  <td class="BarraBusqueda" style="border-left: 0px;padding-left: 15px;">
                              <select name="periodos" id="periodos" onchange="setPeriodo()" >
<?php
    $row_rs_periodos = $obBD_con1->getArrayConsulta(214, $Ses_Emp_Cod, $obBD_conexion);
    if (count($row_rs_periodos) > 0) 
    { 
        $periodo = current($row_rs_periodos);
        foreach ($row_rs_periodos as $row){  
?>
                                  <option <?php if($periodo['Pec_Cod']==$row['Pec_Cod']) {echo "selected";} ?> value="<?php echo $row['Pec_Cod']; ?>"><?php echo " ".$row['Periodo']." "; ?></option>
<?php
        }?>
<script>
    var periodos=<?php echo json_encode($row_rs_periodos)?>;    
    var dataFromRow=[];
    function setPeriodo(){
        $("input[name='Pec_Cod']").val(getPeriodo()["Pec_Cod"]);
        $("input[name='periodo']").val(getPeriodo()["Periodo"]);  
        $('#Com_Fec').dateLimits(getPeriodo()["Pec_Fei"],getPeriodo()["Pec_Fef"]);
    }
    function getPeriodo(){
        for(var i=0;i<periodos.length;i++)
            if(periodos[i]['Pec_Cod']===$("#periodos").val())
                return periodos[i];
    }        
</script>                                  
<?php        
    }
?>
                              </select>
                          </td>			 
			</tr>
                 </table>
            </FIELDSET>
                    </td>                     
                </tr>
            </table>
        </form>
<!-- FIN FORMULARIO BUSQUEDA -->          
          
<!-- INICIO PANEL COMPROBANTE -->          
          <div id="comprobante" style="width: 100%;">
               <FIELDSET>
		<LEGEND>
			<label class="Titulos2">Datos del Comprobante</label>
		</LEGEND>
                <fieldset>
	<legend>
	<label class="Titulos2">Generales</label>
	</legend>	
                    <form name="formComp" id="formComp" method="post" action="javascript:$.createDialogConfirm('¿Est&aacute; seguro que desea guardar los datos?',null,saveComp)">
        <table width="100%" border="0" cellpadding="0" cellspacing="0">  
  <tbody><tr>
    <td class="Etiqueta1">Tipo Comprobante:</td>
    <td class="LetraNegra">       
    <select class="text"  name="Tia_Cod" id="Tia_Cod" style="width: 300px;" class="isSelectMenu" required>
    	<option value="">Seleccione...</option>
<?Php 
$row_rs_tipo_asien = $obBD_con1->getArrayConsulta(347, "", $obBD_conexion);
foreach ($row_rs_tipo_asien as $row) 
{
?>
            <option value="<?php echo $row['Tia_Cod']; ?>"><?php echo $row['Tia_Des'] ?> </option>
<?php
}
?>
          </select>
    </td>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Fecha:</td>
    <td class="LetraNegra">      <input name="Com_Fec" type="text" id="Com_Fec" style="text-align: center" size="10" maxlength="10" class="isDatePicker text ui-corner-all" required />
      </td>
    </tr>
  <tr>
    <td width="106" class="Etiqueta1">Proveedor/Cliente:</td>
    <td width="509" class="LetraNegra" style="padding-top:3px;padding-bottom: 3px;">
        &nbsp;<label id="lblProvee" style="font-weight: bold;">Seleccione un proveedor...</label> &nbsp; &nbsp;
        <a onclick="$('#provDialog').dialog('open')" title="Búqueda de Proveedores" class="btn btn-success btn-mini"><i class=" icon-check icon-white"></i></a></td>
    <td class="Etiqueta1">No. Externo:</td>
    <td><input  class="text ui-corner-all" style="text-align: center" name="Num_Doc" type="text" id="Num_Doc" size="10" onkeypress="return  validar_numeric(event)" /></td>
    </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Concepto:</td>
    <td class="LetraNegra">
      <textarea class="text ui-corner-all" name="Com_Con" cols="73" style="text-transform:uppercase" id="Com_Con" onkeypress="return  validar_injections(event)"></textarea></td>
    <td class="Etiqueta1" style="vertical-align: top;">Valor:</td>
    <td style="vertical-align: top;"><input  class="money text ui-corner-all" name="Com_Val" type="text" id="Com_Val" size="10" maxlength="12" style="text-align:right" onkeypress="return  validar_decimal(event)" required /></td>
  </tr>
  <tr>
      <td class="Etiqueta1">Observaci&oacute;n:</td>
    <td><textarea class="text ui-corner-all" name="Com_Obs" cols="73" style="text-transform:uppercase" id="Com_Obs" onkeypress="return  validar_injections(event)"></textarea></td>
    <td class="Etiqueta1" style="vertical-align: top;">Periodo Contable:</td><td style="vertical-align: top;">
        <input  class="text ui-widget-content ui-corner-all" readOnly name="periodo" size="10" style="text-align: center" type="text" /> 
        <input type="hidden" name="Pec_Cod" />
        <input type="hidden" name="Pld_Cod" />
        <input type="hidden" id="cod_pvr" name="Codigo" value="" /> 
  </tr>
  </tbody></table></form>
	</fieldset>
                <div id="compGrilla" style="padding-top: 6px;">   
                                    <table id="comp"></table>
                                    <div id="compPager"></div>
                </div>
                <script type="text/javascript">  
                    function resetForm(){
                        $("#comp").clearGrid();
                        $("#comp").updateGridDiario();
                        $("#Tia_Cod").val('');//$('#Tia_Cod').selectmenu('refresh', true);                        
                        $("#Com_Val").val("");
                        $("#Com_Con").val("");                       
                        $("#Com_Obs").val(""); 
                        $("#Num_Doc").val("");
                        $("#lblProvee").html("Seleccione un proveedor...");
                        $("#cod_pvr").val(""); 
                    }
                    function saveComp(){
                        var gridComp = $("#comp");
                        var batch = gridComp.getGridBatch();
                        gridComp.trigger('reloadGrid');
                        var     tot=parseFloat($("#Com_Val").val()),
                                deb = gridComp.jqGrid("getCol", "Debe", false, "sum"),
                                hab = gridComp.jqGrid("getCol", "Haber", false, "sum");                       
                        if(deb===tot&&hab===tot){                            
                            if($('#Tia_Cod').val()!==''){
                                if($('#cod_pvr').val()!==''){                                    
                                    if(batch.length>0){ 
                                        var data=$('#formComp').serializeObject();
                                        data["save"]=batch;
                                        $.post( "<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",data, function( response ) {
                                            if(response['success']===true){
                                                $.alert("Transaccion Realizada con &Eacute;xito!");
                                                resetForm();
                                            }else{$.alert(response['message']);}
                                         },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});
                                    }
                                }else{$.alert("Seleccione El Proveedor");}
                            }else{$.alert("Seleccione Tipo de Comprobante");}                            
                        }else{$.alert("Los Totales no Coinciden!");}
                        gridComp.startGridEdit();
                    }
                    function addFilaCuenta(cuenta,tipo){                         
                        cuenta['Glosa']=$('#Com_Con').val();
                        cuenta['Debe']=cuenta['Haber']=0;                        
                        cuenta['Det_Tip']=tipo;        
                        var grid=$("#comp");
                        grid.jqGrid("addRowData", cuenta["Pld_Cod"], cuenta, "last");        
                        grid.startGridEdit();
                    }
                    function SelectCta(id,tipo){                        
                        if(!$("#comp").existsId(id)){
                            addFilaCuenta($.getDialogGrid("#cuenDialog").jqGrid('getRowData', id),tipo);                           
                        }
                    }                    
                    $(document).ready(function () {  
                        var gridComp=$("#comp");
                        gridComp.jqGrid({
                            url: '<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>',
                            mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },                             
                            autowidth : true, shrinkToFit: true, height: 120,
                            cmTemplate: {sortable:false},
                            colModel: [
                                { label: 'Cód.Int.', name: 'Pld_Cod', key: true, width: 15,align:"center", hidden:true },  
                                { label: 'Tipo', name: 'Det_Tip', hidden:true },
                                { label: 'Codigo', name: 'Pld_Cdc', width: 45 },                      
                                { label: 'Cuenta', name: 'Pld_Des', width: 150  },
                                { label: 'Glosa', name: 'Glosa', width: 150,editable:true },
                                { label: 'Debe', name: 'Debe', width: 50, align: 'right', formatter:'currency',
                                     formatoptions: {prefix:'$ ', thousandsSeparator:'.'},summaryTpl: "Total: <b>{0}<b/>", summaryType: "sum",
                                     editoptions: {
                                        dataInit: function (element) {$("#comp").createInputDiario(element,"D","Det_Tip");}
                                    },editable:true 
                                },                                         
                                { label: 'Haber', name: 'Haber', width: 50,align: 'right', formatter:'currency', 
                                     formatoptions: {prefix:'$ ', thousandsSeparator:'.'},summaryTpl: "Total: <b>{0}</b>", summaryType: "sum",
                                     editoptions: {
                                        dataInit: function (element) {$("#comp").createInputDiario(element,"H","Det_Tip");}
                                    },editable:true 
                                },
                                    { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false,
                                        formatter:function (cellvalue, options, rowObject) { 
                                            return  '<span class="btn btn-danger btn-mini" title="Eliminar" onclick="$(\'#comp\').jqGrid(\'delRowData\',\''+rowObject.Pld_Cod+'\');$(\'#comp\').updateGridDiario();"><i class="icon-remove icon-white"></i></span>'; 
                                        }
                                    }
                            ],
                            //caption:"Cuentas",hidegrid: false,     
                            footerrow: true, userDataOnFooter: false,// set a footer row                             
                            rowNum: 10000000, pager: "#compPager", gridview: true, rownumbers: true, viewrecords: true, altRows: true, altclass: "myAltRowClass",pgbuttons: false,pgtext: null
                        });                        
                        gridComp.navGrid('#compPager',{ edit: false, add: false, del: false, search: false, refresh: false, view: false, position: "left", cloneToTop: false });
                        gridComp.jqGrid('bindKeys');                                               
                        $.clearFooterDiario("#comp");
                    });  
                </script>  
                 <div style="padding-top: 6px;">
                     <button onclick="$('#formComp').formSubmit();" title="Guardar Comprobante" type="button" class="btn btn-primary start" ><i class="icon-book icon-white"></i><span>Guardar</span></button><span style="width: 15px;"></span>
                  <button onclick="$('#cuenDialog').dialog('open');" title="Buscar Cuentas" type="button" class="btn btn-success fileinput-button"><i class="icon-list-alt icon-white"></i><span>Agregar</span></button>
                 </div>
               </FIELDSET>
              
          </div>
<!-- FN PANEL COMPROBANTE -->
        </td>
      </tr>     
    </table>
   	
</div>	
<!--INICIO DEL DIALOGO BUSCAR CUENTA--> 
    <div id="cuenDialog" title="B&uacute;squeda de Cuentas">     
        <form>          	
                <FIELDSET>
                    <LEGEND>
                        <label class="Titulos2">B&uacute;squeda de Cuentas</label>
                    </LEGEND>
                    
                    <table border="0" cellpadding="0" cellspacing="0">
                            <tr>
                              <td width="156"><input name="op_opciones" type="radio" checked="checked" value="d" onclick="setfocus(this.form.search)" />
                                    <span class="LetraNegra"><strong>Descripci&oacute;n</strong></span></td>
                              <td width="156"><input name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" />
                                    <span class="LetraNegra"><strong>C&oacute;digo</strong></span></td>
                                <td>Plan de Cuentas:
                                    <input name="periodo" type="text" size="6" readonly style="text-align: center" class="text ui-corner-all ui-widget-content"/> 
                                   <input name="Pec_Cod" type="hidden" /> 
                                </td>
                            </tr>
                    </table>
                    <table height="36" border="0" cellpadding="0" cellspacing="0">
                        <tbody>
                      <tr>
                          <td width="80" height="28" class="BarraBusqueda" style="border-right: 0px;padding-right: 10px;padding-left: 10px;"><div align="right"><strong>B&uacute;squeda</strong></div></td>
                          <td width="387" class="BarraBusqueda" style="border-left: 0px;"><input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" style="text-transform:uppercase" placeholder="Ingrese palabra a buscar..." autofocus /></td>
                          <td width="109" align="center">
                            <button type="button" onclick="this.form.submit()" class="btn btn-success fileinput-button" title="Buscar Cuenta" >
                           <i class="icon-search icon-white"></i>
                           <span>Buscar</span>
                           </button></td>
                      </tr>
                        </tbody>
                    </table>                                                  
                    </FIELDSET>              
            </form>
    </div>
<!-- FIN DEL DIALOGO CUENTAS-->

<!--INICIO DEL DIALOGO BUSCAR PROVEEDOR--> 
    <div id="provDialog"  title="B&uacute;squeda de Proveedores">  
       <form> 
        <fieldset>
		<legend>
                    <label class="Titulos2">Búsqueda de Proveedor</label>
		</legend>
		<table border="0">
                    <tbody><tr>
			  <td width="205"><input name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" />
			  <span class="LetraNegra"><strong>Apellido</strong></span></td>
			  <td width="266"><input name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" />
				<span class="LetraNegra"><strong>Cédula/R.U.C.</strong></span></td>
			</tr>
                    </tbody>
                </table>
                <table height="36" border="0" cellpadding="0" cellspacing="0">
                        <tbody>
                      <tr>
                          <td width="80" height="28" class="BarraBusqueda" style="border-right: 0px;padding-right: 10px;padding-left: 10px;"><div align="right"><strong>B&uacute;squeda</strong></div></td>
                          <td width="387" class="BarraBusqueda" style="border-left: 0px;"><input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" style="text-transform:uppercase" placeholder="Ingrese palabra a buscar..." autofocus /><input type="text" style="display:none"/></td>
                          <td width="109" align="center">
                              <button type="button" onclick="this.form.submit()" class="btn btn-success fileinput-button" title="Buscar Proveedor" >
                           <i class="icon-search icon-white"></i>
                           <span>Buscar</span>
                           </button></td>
                      </tr>
                        </tbody>
                </table>
        </fieldset>  
       </form>
    </div>
<!-- FIN DEL DIALOGO PROVEEDOR-->

<script type="text/javascript"> 
    $(document).ready(function() {       
        //$(".isSelectMenu").selectmenu();
        $.createDatePickers('.isDatePicker');
        setPeriodo(); 
            // DIALOG BUSCAR CUENTAS
             $.createSearchDialog('cuenDialog',[
                    { label: 'Cód.Int.', name: 'Pld_Cod', key: true, width: 15,align:"center",hidden:true },                                
                    { label: 'Codigo', name: 'Pld_Cdc', width: 45 },                      
                    { label: 'Cuenta', name: 'Pld_Des', width: 80, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
                    { label: 'Grupo', name: 'Pld_Grupo', width: 110, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},
                    { label: 'Tipo', name: 'Pld_Tip', width: 30,align:"center" },
                    { label: 'Estado', name: 'Pld_Est', width: 30,align:"center"}, 
                        { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 30, align: 'center',viewable: false,
                            formatter:function (cellvalue, options, rowObject) { 
                                return  '<span class="btn btn-success btn-mini" title="Enviar al Débito" onclick="SelectCta(\''+rowObject.Pld_Cod+'\',\'D\');"><b>D</b></span>&nbsp;'
                                        +'<span class="btn btn-success btn-mini" title="Enviar al Crédito" onclick="SelectCta(\''+rowObject.Pld_Cod+'\',\'H\');"><b>H</b></span>'; 
                            }
                        }
                ]);   
            // DIALOG BUSCAR PREOVEEDOR    
             $.createSearchDialog('provDialog',[
                    { label: 'Cód.Int.', name: 'Prv_Cod', key: true, width: 20,align:"center",hidden:true,viewable: true },                                
                    { label: 'Cédula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
                    { label: 'Proveedor', name: 'proveedor', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
                    { label: 'Dirección', name: 'Prs_Dir',hidden:true,viewable: true },                      
                        { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                            formatter:function (cellvalue, options, rowObject) { 
                               var clic='$("#cod_pvr").val("'+rowObject.Prv_Cod+'");$("#lblProvee").html("'+rowObject.proveedor+'");$("#provDialog").dialog("close");';
                                return  '<span class="btn btn-success btn-mini" title="Seleccionar" onclick=\''+clic+'\'><i class="icon-arrow-right icon-white"></span>'; 
                            }
                        }
                ]);            

    }); 
    </script>
</BODY>
</HTML>