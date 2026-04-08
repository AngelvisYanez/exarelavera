<?	  
/**
* Descripcion: Registro de Reposicion Caja Chica
* Fecha de actualizacion:	20-07-2016
* Desarrollador:	Jose Cumbicos
*/	
require_once('../../administrador/LOGICA/seguridad.php');	  
require_once('../LOGICA/cch_log_reposicion.php');  	  
require_once('../../Librerias/procedimientos/almacenados_standar.php');		  
require_once('../../Librerias/postclass.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Cch($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Cch;
/* Llamado de la libreria para evitar el reenvio de datos */
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
//$obBD_con1->debug(true);

/* Buscamos la facturas pendientes por reponer */
//var_dump($ajaxSubgrid);
if(isset($ajaxSubgrid)){ 
	$responce['pages']=1;$responce['total']=1;
  $responce['rows'] = $obBD_con1->getArrayConsulta(23, $RepCod.'*'.$Ses_Emp_Cod, $obBD_conexion);
  utf8_encode_deep($responce['rows']);
  $responce['records']=count($responce['rows']);
  echo json_encode($responce);exit();
}

if(isset($provAjax)){
  $contar = $obBD_con1->getRowConsulta(4, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
  $pagination= pages($contar['total'], $page, $rows);
  $responce=$pagination['data'];
  $responce['rows'] = $obBD_con1->getArrayConsulta(4, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);
  utf8_encode_deep($responce['rows']);
  echo json_encode($responce);exit();
}

if(isset($reposiAjax)){
	if($op_opciones=='n'){ //buscar x numero de cheque
		$responce['rows']=$obBD_con1->getArrayConsulta(21, $search.'*'.$Ses_Emp_Cod, $obBD_conexion);	
	}else{ //busqueda por rango d efecha
		$responce['rows']=$obBD_con1->getArrayConsulta(22, $ini.'*'.$fin.'*'.$Ses_Emp_Cod, $obBD_conexion);	
	}
	$responce['success']=true;
	utf8_encode_deep($responce['rows']);
	echo json_encode($responce);exit();
}

if(isset($ajaxCargaRep)) {
    $contar = $obBD_con1->getRowConsulta(6, $_POST['Ban_Cod'], $obBD_conexion);
    $contar['success'] = true;    
    $obBD_con1->echoJson($contar);
}

if(isset($valChe)) {
    $obBD_con1->echoLog("ENTRANDO A VALIDAR EL CHEQUE");
    $obBD_con1->echoLog($_GET);
    $conteo = $obBD_con1->getRowConsulta(7, array("Ban_Cod" => $valBanco, "numero" => $numero), $obBD_conexion);
    $obBD_con1->echoLog("CONTEOOOOO : ".$conteo);
    $contar = $obBD_con1->getRowConsulta(6, $valBanco, $obBD_conexion);
    $obBD_con1->echoLog("CONTAR : ".$contar);
    $contar['success']=true;
    if($conteo['conteo']==0)$contar['valid']=true; else $contar['valid']=false;
    echo json_encode($contar);exit();
}

/* Get Facturas de compra en reposicion con caja chica actual */
if(isset($getTotal)){
    $data['data'] = $obBD_con1->getArrayConsulta(3, $Ses_Emp_Cod , $obBD_conexion);
    $data['success'] = true;
    $obBD_con1->echoLog($data);
    $obBD_con1->echoJson($data);
}

if(isset($numCheIni)){ 
	$contar = $obBD_con1->getRowConsulta(6, $Ban_Cod, $obBD_conexion);
	$contar['success']=true;
	echo json_encode($contar);exit();
}
				
if (isset($ajax_mov)) {
  /* Consulta los tipos de ajustes: compras-inventario-baja etc */
	$rs_tpaj= $obBD_con1->getArrayConsulta(1050, $Ses_Emp_Cod.'*'.$Tia_IoE, $obBD_conexion);
?>
    <select name="Tia_Cod" id="Tia_Cod" >
      <option value="">Seleccione...</option>
      <?php  foreach($rs_tpaj as $row_rs_tpaj) {  	 ?>
      <option   value="<?Php echo $row_rs_tpaj['Tia_Cod']; ?>">
      <?php  echo $row_rs_tpaj['Tia_Des'];		?>
      </option>
      <?php	}?>
    </select>
<?Php
	unset($rs_tpaj);
	exit();
}

/* Evitar el reenvio de formularios */
if (isset($hdd_save)) {
	$obBD_con1->validaCierrePeriodo('comprobantes', 'Com_Fec', 'Com_Cod',null,$Com_Cod,$obBD_conexion);
	/* Consultamos si existe un perido segun la fecha de reposicion */
	$contar = $obBD_con1->getArrayConsulta(11, $Ses_Emp_Cod.'*'.$fecha, $obBD_conexion);
	if (count($contar)==0){
		$responce['success']=false; 
		$responce['message']='La fecha ingresada no pertenece a un periodo contable!'; 
		echo json_encode($responce);exit();	
	}
	
	/* Inicio de la transaccion */
	$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
	/*separamos los datos del banco*/
	$varDatBan=explode('*',$bancos);
	/*control para obtener codigo de comprobante*/
	$var_mes=explode('-',$fecha);	
	/*actualiza el comprobante*/
	$obBD_con1->operacionobBD(24,$contar[0]['Pec_Cod'].'*'.$Prv_Cod.'*'.$Ses_Usu_Cod.'*'.$Tia_Cod.'*'.$fecha.'*REPOSICION CAJA CHICA*'.$monto_rep.'*'.'A'.'*'.$Com_Cod,$obBD_conexion);
	/*consultamos la cuenta de la reposicion*/
	$ctaReposi = $obBD_con1->getRowConsulta(12, 'RC'.'*'.$Ses_Emp_Cod, $obBD_conexion);
	/*Eliminamos datos Asiento*/
	$obBD_con1->operacionobBD(25,$Com_Cod, $obBD_conexion);
	/*insertamos el asiento contable*/
	$obBD_con1->operacionobBD(13,$Com_Cod.'*'.'D'.'*'.$monto_rep.'*'.$ctaReposi['Pld_Cod'].'*'.'P/R REPOSICION CAJA CHICA', $obBD_conexion); //debe

  /*ingresa datos del cheque*/
  if ($opn === "C") {
    $obBD_con1->operacionobBD(13, $Com_Cod . '*' . 'H' . '*' . $monto_rep . '*' . $varDatBan[1] . '*' . 'CHEQUE DE REPOSICION No. ' . $Che_Num, $obBD_conexion); //haber cheque
    $Asi_Cod = $obBD_con1->insercionid($obBD_conexion); //get asi_cod
    $obBD_con1->operacionobBD(14, $Prv_Cod . '*' . $varDatBan[0] . '*' . $Asi_Cod . '*' . $Che_Num . '*' . $fecha . '*' . $monto_rep . '*1', $obBD_conexion); // insert cheque
    $responce['paramChe'] = "?codigo2=1&asi=$Asi_Cod&ban=$varDatBan[0]&pro=$Prv_Cod";
  }
  if ($opn === "E") {
    $CtaCjaBanco = $obBD_con1->getRowConsulta(32, 'C*' . $Ses_Emp_Cod, $obBD_conexion);
    $obBD_con1->operacionobBD(13, $Com_Cod . '*' . 'H' . '*' . $monto_rep . '*' . $CtaCjaBanco['Pld_Cod'] . '*' . 'EFECTIVO', $obBD_conexion); // haber efectivo
    $responce['paramChe'] = "";
  }
	
	
	/*insertamos la cabecera de la reposicion*/
	$obBD_con1->operacionobBD(27,$Cch_Cod.'*'.$Ses_Usu_Cod.'*'.$fecha.'*'.$obs.'*'.$Com_Cod.'*'.$RepCod, $obBD_conexion);  
	$Rep_Cod = $RepCod;

  foreach ($gridDatos as $filas) {
    if ($filas['act'] == 'Yes') {
      $obBD_con1->operacionobBD(9, $Rep_Cod . '*R*' . $filas['Cop_Cod'], $obBD_conexion); //asignamos codigo d reposicion
      $obBD_con1->echoLog("ENTRANDO A GUARDAR EL DETALLE");
      $obBD_con1->echoLog("CODIGO REPOSICION: " . $Rep_Cod);
      $obBD_con1->echoLog("CODIGO COMPRA: " . $filas['Cop_Cod']);
    } else {
      $obBD_con1->operacionobBD(9, $Rep_Cod . '*P*' . $filas['Cop_Cod'], $obBD_conexion); //ponemos compra en estado Pendiente
    }
  }
	/* FInaliza la transaccion */										
	$obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);	
	$responce['Rep_Cod']=$Rep_Cod;
	$responce['Tia_Cod']=$Tia_Cod;
	$responce['Com_Cod']=$Com_Cod;
	$responce['Pec_Cod']=$contar[0]['Pec_Cod'];	
	if($obBD_con1->Error==0){
    $responce['success']=true;
  } else {
    $responce['success']=false;
    $responce['message']=$obBD_con1->MsgError;
  }	
	$obBD_con1->echoJson($responce);
}

/* Liberacion de registros de reposicion de caja chica */
if(isset($liberarAjax)){
  // actualiza el estado del detalle de la reposicion y encera la reposicion
  $obBD_con1->operacionobBD(154, $RepCod, $obBD_conexion);
  // actualiza el estado del comprobante de reposicion
  $obBD_con1->operacionobBD(155, $Com_Cod, $obBD_conexion, true);
  // actualiza el estado de la creposicion
  $obBD_con1->operacionobBD(156, $RepCod, $obBD_conexion);
  if ($obBD_con1->Error == 0) {
    $response['success'] = true;
  } else {
    $response['success'] = false;
    $response['message'] = $obBD_con1->MsgError;
  }
  $obBD_con1->echoJson($response);
  exit();
}

if(isset($liberaCompraAjax)){
  $result = $obBD_con1->operacionobBD(157, $Cop_Cod, $obBD_conexion);
  if ($obBD_con1->Error == 0) {
    $response['success'] = true;
  } else {
    $response['success'] = false;
    $response['message'] = $obBD_con1->MsgError;
  }
  $obBD_con1->echoJson($response);
  exit();
}

/* Buscamos El monto de la Caja Chica */
$rs_busCajaCh = $obBD_con1->getRowConsulta(1, $Ses_Emp_Cod, $obBD_conexion);	
/* Buscamos los Bancos Existentes */
$rs_busBancos = $obBD_con1->getArrayConsulta(2, $Ses_Emp_Cod, $obBD_conexion);	
/* Buscamos los asientos de tipo EGRESO */
$rs_TipAsiento = $obBD_con1->getArrayConsulta(5, 'E', $obBD_conexion);	

?>
<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?Php echo $Ses_Sys_Nom;?></TITLE>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
        <script language="javascript" src="../VALIDACIONES/cch_val_lib_reposicion.js?a=2"> </script>
    </HEAD>
<BODY>
<div class="panel panel-main">
	<div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Modificar Reposici&oacute;n</h3></div>
		<div class="panel-body ui-widget-content ui-corner-bottom exa-body">
				<form id="formReposi" class="form-horizontal normal"  action="javascript:$.createDialogConfirm(null,null,saveForm); "  >
						<div class="row">
						<div class="col-sm-12"></div>
							<div class="col-sm-6">
								<fieldset class="exa-fieldset">                           
								<legend class="Titulos2">Datos del Cheque</legend> <!-- Form Name -->
                  <!-- Text input-->
                  <div class="form-group">
                    <label class="col-sm-3 control-label label-sm" for="Cop_Num"></label>
                    <div class="col-sm-5"> 				
                      <input id="opn" name="opn" type="radio" value="C" onClick="document.getElementById('Pld_Cod').removeAttribute('disabled'); document.getElementById('Che_Num').removeAttribute('disabled');$('#btnBusca').css('display','');" checked="checked" /> &nbsp; <label>Cheque</label>&nbsp;&nbsp;&nbsp;													
                      <input id="opm" name="opn" disabled type="radio" value="E" onClick="document.getElementById('Pld_Cod').disabled='true'; document.getElementById('Che_Num').disabled='true';$('#btnBusca').css('display','none')" /> &nbsp; <label>Efectivo</label>
                    </div>											
                  </div>																				
                  
                  <!-- select bancos-->
                  <div class="form-group" id="banco">
                    <label class="col-sm-3 control-label label-sm required" for="Pld_Cod">Banco:</label>  
                    <div class="col-sm-4">
                      <select name="bancos" id="bancos" onchange="setCheques(this.value)" class="form-control input-sm" >
                        <option value="">Seleccione...</option>
                          <?Php 													
                              foreach($rs_busBancos as $row){ ?>
                              <option value="<?php echo $row['Ban_Cod'].'*'.$row['Pld_Cod']?>"><?php echo $row['Pld_Des']?></option>
                          <?php } ?>
                      </select>
                    </div>
                  </div>
                  
                  <!-- Text input Numero Cheque-->
                  <div class="form-group" id="cheque">
                    <label class="col-sm-3 control-label label-sm required" for="Che_Num">No. cheque:</label>  
                    <div class="col-sm-4">                                    
                      <input id="Che_Num" name="Che_Num" class="form-control input-sm" type="text" onkeypress="return validar_numeric(event);" onChange="validaCheque(this.value);" />	
                      <img class="imgMsg" /><label class="lblMsg"></label>												
                    </div>                                 
                  </div>																									

                  <!-- Text input destinatario el cheque-->
                  <div class="form-group" id="btnBusca">
                    <label class="col-sm-3 control-label label-sm required" for="cod_cuenta">Emitido a:</label>  
                    <div class="col-sm-8">                                    
                      <div class="input-group input-group-sm">
                        <input type="hidden" name="Prv_Cod" id="PrvCodBus" value="" />  
                        <input id="docu" name="Provee" type="text" class="form-control" placeholder="Seleccione destinatario de cheque..." required readonly />
                        <span class="input-group-btn" >
                          <button class="btn btn-success" onclick="$('#provDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Proveedor"></span></button>
                        </span>
                      </div><!-- /input-group -->                              
                    </div>                                  
                  </div>

                  <!-- select tipo asiento-->
                  <div class="form-group">
                    <label class="col-sm-3 control-label label-sm required" for="Tia_Cod">Tipo de Egreso:</label>  
                    <div class="col-sm-4">
                        <select name="Tia_Cod" id="Tia_Cod" onchange="" class="form-control input-sm" required>
                              <option value="">Seleccione...</option>
                                <?Php 													
                                    foreach($rs_TipAsiento as $row){ ?>
                                    <option value="<?php echo $row['Tia_Cod']?>"><?php echo $row['Tia_Des'];?></option>
                                <?php } ?>
                        </select>
                    </div>
                  </div>
								</fieldset>
                
                <fieldset class="exa-fieldset">                           
								<legend class="Titulos2">Acciones</legend>
                  <div class="col-sm-12">
                    <div class="form-group">  
                      <div class="col-sm-12 ">
                        <button type="button" class="btn btn-success fileinput-button" title="Buscar reposiciones" onClick="$('#reposiDialog').dialog('open');"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                        <button class="btn btn-primary" type="button" onclick="liberarReposicion();"><span class="glyphicon glyphicon-ban-circle"></span> Liberar</button>
                        <button type="button" onclick="resetForm();" class="btn btn-danger"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>
                      </div>
                    </div>
                  </div>
                </fieldset>

							</div>
              
							<div class="col-sm-6">											
								<fieldset class="exa-fieldset">                           
								<legend class="Titulos2">Datos de la Reposici&oacuten</legend>
									<!-- Text input Fecha-->
									<div class="form-group">
									  <label class="col-sm-4 control-label label-sm required" for="fecha">Fecha de reposici&oacuten:</label>  
									  <div class="col-sm-3">                                    
											  <input id="fecha" name="fecha" class="form-control input-sm dateType" placeholder="0000-00-00" type="text" required />
											  <input id="RepCod" name="RepCod" type="hidden" />
											  <input id="Com_Cod" name="Com_Cod" type="hidden" />
									  </div>                                 
									</div>										
									<!-- Text input Monto caja chica-->
									<div class="form-group">
                    <label class="col-sm-4 control-label label-sm required" for="monto">Monto de Caja:</label>  
                    <div class="col-sm-2">                                    
											<input id="monto" name="monto" class="form-control input-sm" readonly placeholder="0.00" type="text" value="<? echo number_format((float)$rs_busCajaCh['Cch_Val'],2,'.','');?>" />					  
											<input type="hidden" id="Cch_Cod" name="Cch_Cod" value="<? echo $rs_busCajaCh['Cch_Cod'];?>" />
                    </div>
                    <label class="col-sm-3 control-label label-sm" for="Sal_Act">Saldo Actual:</label>  
                    <div class="col-sm-3">                                    
                          <input id="sal_act" name="sal_act" class="form-control input-sm" readonly placeholder="0.00" type="text"/>					  
                    </div>
									</div>
									<!-- Text input monto a reponer-->
									<div class="form-group">
									  <label class="col-sm-4 control-label label-sm required" for="Cop_Num">Facturas/Vale:</label>  
									  <div class="col-sm-2">                                    
                      <input id="monto_rep" name="monto_rep" class="form-control input-sm" readonly placeholder="0.00" type="text" required />					  
									  </div>
                    
                      <label class="col-sm-3 control-label label-sm" for="Rep_Tot">Reposicion Total:</label>  
                      <div class="col-sm-3">                                    
                          <input id="monto_rep_tot" name="monto_rep_tot" class="form-control input-sm" readonly placeholder="0.00" type="text"/>
                      </div>
									</div>
									
									<!-- Text input observacion -->
									<div class="form-group">
									  <label class="col-sm-4 control-label label-sm" for="Cop_Num">Observaci&oacute;n:</label> 
									  <div class="col-sm-7">                                  
											<textarea id="obs" name="obs" cols="50" rows="3" class="form-control input-sm" placeholder="Observaci&oacute;n" ></textarea>					  
									  </div>                                 
									</div>
								</fieldset>
							</div>																					
							<div class="col-sm-4"></div>
							<div class="col-sm-12">
								<fieldset class="exa-fieldset">                           
									<legend class="Titulos2">Facturas de la Reposici&oacuten</legend> <!-- Form Name -->
									<div style="min-height: 350px"> 
										<table id="list"></table>
										<div id="listPager"></div>
									</div>
								</fieldset>
							</div>
							
							<div class="col-sm-12">
								<div class="form-group Titulos2">
                                <div class="col-sm-12"><hr/><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.</div>
								</div>  
							</div>
					</div>
				</form>
		</div>
</div>
    <!--INICIO DEL DIALOGO IMPRIMIR --> 
    <div id="successDialog"  title="Mensaje del Sistema">  
        <center><h4>Imprimir documentos!</h4></center>  
        <center id="printCheque"></center>
		<center> 
            <button type="button" onclick="$('#successDialog').dialog('close');" class="btn btn-inverse fileinput-button" style="display: inline;" >
                    <i class="icon-ban-circle icon-white"></i>
                    <span>Cerrar</span>
            </button>            
            <a id="impReposi" target="_blank" href=""  style="display: inline;" title="Imprimir informe de reposici&oacute;n"><span  class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Reposici&oacute;n</span></span> </a>
			<a id="impCompr" target="_blank" href=""  style="display: inline;" title="Imprimir Comprobante Contable"><span  class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Asiento</span></span> </a>               
        </center> 
        <div id="modelo" style="display: none;">
		<table style="margin-bottom:40px;" cellpadding="1" border="1">
			<tr><td align="center" class="ui-widget-header" colspan="6"><label autofocus> Imprimir Cheque </label></td></tr>
			<tr><td align="center" class="ui-widget-content" colspan="6"><b id="nomBan">&nbsp;</b></td></tr>
			<tr>
				<td align="center"><a href="../../tesoreria/FRONT/tes_pri_cheque_mac_1.0.php{link}" target="_blank" title="Banco de Machala"><img src="../../mascaras/model1/imagenes/32x32/banco_machala.jpg" width="22" height="35"/></a></td>
				<td align="center"><a href="../../tesoreria/FRONT/tes_pri_cheque_pac_1.0.php{link}" target="_blank" title="Banco del Pacifico"><img src="../../mascaras/model1/imagenes/32x32/banco_pacifico.jpg" width="24" height="23"/></a></td>
				<td align="center"><a href="../../tesoreria/FRONT/tes_pri_cheque_rum_1.0.php{link}" target="_blank" title="Banco del Rumiñahui"><img src="../../mascaras/model1/imagenes/32x32/banco_ruminahui.jpg" width="30" height="15"/></a></td>
				<td align="center"><a href="../../tesoreria/FRONT/tes_pri_cheque_gua_1.0.php{link}" target="_blank" title="Banco del Guayaquil"><img src="../../mascaras/model1/imagenes/32x32/banco_guayaquil.JPG" width="36" height="18"/></a></td>
				<td align="center"><a href="../../tesoreria/FRONT/tes_pri_cheque_pch_1.0.php{link}" target="_blank" title="Banco del Pichincha"><img src="../../mascaras/model1/imagenes/32x32/banco_pichincha.JPG" width="36" height="30"/></a></td>
				<td align="center"><a href="../../tesoreria/FRONT/tes_pri_cheque_int_1.0.php{link}" target="_blank" title="Banco Internacional"><img src="../../mascaras/model1/imagenes/32x32/ban_int.jpg" width="32" height="32"/></a></td>
			</tr>
		</table>
		</div>
    </div>
<!--INICIO DEL DIALOGO BUSCAR PROVEEDOR--> 
    <div id="provDialog" title="B&uacute;squeda de Proveedores">  
      <form class="form-horizontal normal"> 
        <fieldset>
		<legend>Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-md-8 radioset" >
                          <input id="rad1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="rad1">&nbsp;&nbsp;Apellido&nbsp;&nbsp;</label>
                          <input id="rad2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="rad2">&nbsp;&nbsp;C&eacute;dula/R.U.C.&nbsp;&nbsp;</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">B&uacute;squeda:</label>
                    <div class="col-md-7" >                 
                      <div class="input-group">                        
                        <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese proveedor a buscar..." autofocus class="form-control input-sm " /><input type="text" style="display:none"/>
                        <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar cuenta" ><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button></span>
                      </div><!-- /input-group -->                          
                    </div>                    
                </div>
        </fieldset>  
      </form>    
    </div>
	
	<!--INICIO DEL DIALOGO BUSCAR REPOSICION--> 
    <div id="reposiDialog" title="B&uacute;squeda de Reposiciones">  
      <form class="form-horizontal normal"> 
        <fieldset class="exa-fieldset">
		    <legend class="Titulos2">Filtros</legend>
          <div class="form-group">
              <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
              <div class="col-md-8 radioset" >
                    <input id="radx" name="op_opciones" type="radio" value="n" checked="" onclick="$('#div_numCheque').toggleClass('hide'); $('#div_rangoFecha').toggleClass('hide'); setfocus(this.form.search)" alt="" /><label for="radx">&nbsp;&nbsp;Num. Cheque&nbsp;&nbsp;</label>
                    <input id="rady" name="op_opciones" type="radio" value="f" onclick="$('#div_numCheque').toggleClass('hide'); $('#div_rangoFecha').toggleClass('hide'); setfocus(this.form.search)" alt="" /><label for="rady">&nbsp;&nbsp;Rango de Fechas&nbsp;&nbsp;</label>
              </div>
          </div>
          <div id="div_numCheque" class="form-group">
              <label class="col-md-2 control-label">B&uacute;squeda:</label>
              <div class="col-md-7" >                 
                <div class="input-group">                        
                  <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="N&uacute;mero de cheque" autofocus class="form-control input-sm " />
                  <input type="text" style="display:none"/>
                  <span class="input-group-btn">
                    <button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar cuenta" >
                      <span class="glyphicon glyphicon-search"></span>
                      <span> Buscar</span>
                    </button>
                  </span>
                </div><!-- /input-group -->                          
              </div>                    
          </div>
            <div id="div_rangoFecha" class="form-group hide" >                                          
                <label class="col-xs-2 control-label label-xs">Desde:</label>
                <div class="col-xs-3" >
                  <input id="ini" name="ini" class="form-control input-sm " placeholder="0000-00-00" type="text" required />
                </div>                      
                <label class="col-xs-2 control-label label-xs">Hasta:</label>
                <div class="col-xs-3">
                  <input id="fin" name="fin" class="form-control input-sm " placeholder="0000-00-00" type="text" required />
                </div>
                <div class="col-xs-2">
                  <button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar cuenta" >
                    <span class="glyphicon glyphicon-search"></span>
                    <span> Buscar</span>
                  </button>
                </div>          
            </div>
        </fieldset>  
      </form>    
    </div>
<!-- FIN DEL DIALOGO PROVEEDOR-->

<script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput/jquery.maskedinput.1.4.1.min.js"></script>
<!-- Librerias para el tratamiento de la interfaz - cajas de texto -->
<!--<script type="text/javascript" src="../VALIDACIONES/fac_par_aju.js?x=2"></script>-->
<!--<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>   -->
</BODY>
</HTML>
<?php
/**
* Cierra las conexiones 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();	
?>