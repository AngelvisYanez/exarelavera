<?php	  
/**
* Descripcion: Registro de Reposicion Caja Chica
* Fecha de actualizacion:	20-07-2016
* Desarrollador:	Jose Cumbicos
* Actualizacion:        Asael Tello 14-09-2017
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
$obBD_con1->setAudit(true);
/* Buscamos la facturas pendientes por reponer */
if(isset($ajaxSubgrid)){ 
	$responce['pages']=1;$responce['total']=1;
    $responce['rows'] = $obBD_con1->getArrayConsulta(3, $Ses_Emp_Cod, $obBD_conexion);
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

if(isset($valChe)){
    $obBD_con1->echoLog("ENTRANDO A VALIDAR EL CHEQUE");
    $obBD_con1->echoLog($_GET);
    $conteo = $obBD_con1->getRowConsulta(7, array("Ban_Cod" => $banco, "numero" => $numero), $obBD_conexion);
    $obBD_con1->echoLog("CONTEOOOOO : ".$conteo['conteo']);
    $contar = $obBD_con1->getRowConsulta(6, $banco, $obBD_conexion);
    $obBD_con1->echoLog("CONTAR : ".$contar);
    $contar['success']=true;
    if($conteo['conteo']==0){
      $contar['valid']=true;
    } else {
      $contar['valid']=false;
    }
    echo json_encode($contar);exit();
}

if(isset($numCheIni)){ 
	$contar = $obBD_con1->getRowConsulta(6, $Ban_Cod, $obBD_conexion);
	$contar['success']=true;
	echo json_encode($contar);exit();
}

if (isset($ajax_mov)){
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
if (isset($hdd_save)){ 
	//$obBD_con1->validaCierrePeriodo('comprobantes','Com_Fec','Com_Cod',$fecha,null,$obBD_conexion);
	/* Consultamos si existe un perido segun la fecha de reposicion */
	$contar = $obBD_con1->getArrayConsulta(11, $Ses_Emp_Cod.'*'.$fecha, $obBD_conexion);
	if (count($contar)==0){
		$responce['success']=false; 
		$responce['message']='La fecha ingresada no pertenece a un periodo contable!'; 
		echo json_encode($responce);exit();	
	}
	
	/* Inicio de la transaccion*/
	$obBD_con1->inicio_transaccion($obBD_conexion->conexion);		
	/*control para sacar el numero de reposicion*/
	$anio=explode('-',$hoy);
	
	$numReposi=$obBD_con1->getRowConsulta(31, $Ses_Suc_Cod.'*'.$anio[0].'-01-01*'.$anio[0].'-12-31', $obBD_conexion);
	if($numReposi['Rep_Num']==''){
		$numeroRep=1;
	}else{
		$numeroRep=$numReposi['Rep_Num'] + 1;
	}
	
	if($opn=='C'){
		/*separamos los datos del banco*/
		$varDatBan=explode('*',$bancos);
	} else {
		/*consultamos proveedores varios*/
		$PrvCodVarios = $obBD_con1->getRowConsulta(33,$Ses_Emp_Cod, $obBD_conexion);
		$Prv_Cod=$PrvCodVarios['Prv_Cod'];
		/*consultamos cuenta caja-banco*/
		$CtaCjaBanco = $obBD_con1->getRowConsulta(32,'C*'.$Ses_Emp_Cod, $obBD_conexion);
	}
	/*control para obtener codigo de comprobante*/
	$var_mes=explode('-',$fecha);
	$Com_Num = $obBD_con1->codigoComprAuto($Tia_Cod, $contar[0]['Pec_Cod'], $var_mes[1], $obBD_conexion);
	/*insertamos el comprobante*/
	$obBD_con1->operacionobBD(10,$contar[0]['Pec_Cod'].'*'.$Prv_Cod.'*'.$Ses_Usu_Cod.'*'.$Tia_Cod.'*'.$Com_Num.'*'.$fecha.'*REPOSICION CAJA CHICA*'.$monto_rep.'*'.'A',$obBD_conexion);  
	$Com_Cod = $obBD_con1->insercionid ($obBD_conexion->conexion);	
	
	/*consultamos la cuenta de la reposicion*/
	$ctaReposi = $obBD_con1->getRowConsulta(12, 'RC'.'*'.$Ses_Emp_Cod, $obBD_conexion);
	/*insertamos el asiento contable*/
	$obBD_con1->operacionobBD(13,$Com_Cod.'*'.'D'.'*'.$monto_rep.'*'.$ctaReposi['Pld_Cod'].'*'.'P/R REPOSICION CAJA CHICA', $obBD_conexion);
	if($opn=='C'){ // reposicion con cheque
		$obBD_con1->operacionobBD(13,$Com_Cod.'*'.'H'.'*'.$monto_rep.'*'.$varDatBan[1].'*'.'P/R RECHEQUE DE REPOSICION No. '.$Che_Num, $obBD_conexion);
		$Asi_Cod = $obBD_con1->insercionid ($obBD_conexion->conexion);		
		/*insertamos datos del cheque*/
		$obBD_con1->operacionobBD(14,$Prv_Cod.'*'.$varDatBan[0].'*'.$Asi_Cod.'*'.$Che_Num.'*'.$fecha.'*'.$monto_rep.'*1', $obBD_conexion);	
	}else{         // reposicion en efectivo	   
		$obBD_con1->operacionobBD(13,$Com_Cod.'*'.'H'.'*'.$monto_rep.'*'.$CtaCjaBanco['Pld_Cod'].'*'.'REPOSICION EN EFECTIVO', $obBD_conexion);
		$Asi_Cod = $obBD_con1->insercionid ($obBD_conexion->conexion);	       
	}
		
	/*insertamos la cabecera de la reposicion*/
	$obBD_con1->operacionobBD(8,$Cch_Cod.'*'.$Ses_Usu_Cod.'*'.$fecha.'*'.$obs.'*'.$Com_Cod.'*'.$numeroRep.'*'.$opn, $obBD_conexion);  
	$Rep_Cod = $obBD_con1->insercionid ($obBD_conexion->conexion);		
	
	foreach($gridDatos as $filas)
	{
		if ($filas['act']=='Yes'){
			$obBD_con1->operacionobBD(9,$Rep_Cod.'*R*'.$filas['Cop_Cod'], $obBD_conexion);
		}		
	}
	/**
	* FInaliza la transaccion
	*/										
	$obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);	
	$responce['Rep_Cod']=$Rep_Cod;
	$responce['Tia_Cod']=$Tia_Cod;
	$responce['Com_Cod']=$Com_Cod;
	$responce['Pec_Cod']=$contar[0]['Pec_Cod'];
	$responce['paramACT']=$opn;
	if($opn=='C')
        {
            $responce['paramChe']="?codigo2=1&asi=$Asi_Cod&ban=$varDatBan[0]&pro=$Prv_Cod";
        }
	else
        {
            $responce['paramChe']="";            
        }
	if($obBD_con1->Error==0) $responce['success']=true;
	else {$responce['success']=false;  $responce['message']=$obBD_con1->MsgError;}	
	echo json_encode($responce);exit();					
}

/**
/* Buscamos El monto de la Caja Chica
*/
$rs_busCajaCh = $obBD_con1->getRowConsulta(1, $Ses_Emp_Cod, $obBD_conexion);

/**
/* Buscamos los Bancos Existentes
*/
$rs_busBancos = $obBD_con1->getArrayConsulta(2, $Ses_Emp_Cod, $obBD_conexion);	

/**
/* Buscamos los asientos de tipo EGRESO
*/
$rs_TipAsiento = $obBD_con1->getArrayConsulta(5, 'E', $obBD_conexion);	

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML><HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom;?></TITLE>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>         
        <script type="text/javascript"> 
        $(function() {
			$('#set1 *').tooltip({showURL: false});
		});              			
		</script>		
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <script language="javascript" src="../VALIDACIONES/cch_par_reposicion.js?a=6"></script>
		<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
	</HEAD>
<BODY>
<div class="panel panel-main">
	<div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registrar Reposici&oacute;n</h3></div>
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
                                                                                        <input id="opn" name="opn" type="radio" value="C" onClick="
                                                                                        $('#chequeG').css('display','');$('#bancosG').css('display','');$('#btnBusca').css('display','');
                                                                                        $('#bancos').attr('required');
                                                                                        $('#Che_Num').attr('required');
                                                                                        $('#docu').attr('required');" checked="checked"> &nbsp; <label>Cheque</label>&nbsp;&nbsp;&nbsp;													
                                                                                        <input id="opn" name="opn" type="radio" value="E" onClick="
                                                                                        $('#chequeG').css('display','none'); 
                                                                                        $('#bancosG').css('display','none'); 
                                                                                        $('#btnBusca').css('display','none') 

                                                                                        $('#bancos').removeAttr('required');
                                                                                        $('#Che_Num').removeAttr('required');
                                                                                        $('#docu').removeAttr('required');" > &nbsp; <label>Efectivo</label>
                                                                        </div>											
                                                                </div>																				
                                                                <!-- select bancos-->
                                                                <div class="form-group" id="bancosG">
                                                                  <label class="col-sm-3 control-label label-sm required" for="banco">Banco:</label>  
                                                                  <div class="col-sm-4">
                                                                            <select name="bancos" id="bancos" onchange="setCheques(this.value)" class="form-control input-sm" required>
                                                                                  <option value="">Seleccione...</option>
                                                                                    <?Php 													
                                                                                        foreach($rs_busBancos as $row){ ?>
                                                                                        <option value="<?php echo $row['Ban_Cod'].'*'.$row['Pld_Cod']?>"><?php echo $row['Pld_Des']?></option>
                                                                                        <?php } ?>
                                                                            </select>
                                                                  </div>
                                                                </div>	
                                                                <!-- Text input Numero Cheque-->
                                                                <div class="form-group" id="chequeG">
                                                                  <label class="col-sm-3 control-label label-sm required" for="Che_Num">No. cheque:</label>  
                                                                  <div class="col-sm-4">                                    
                                                                                <input id="Che_Num" name="Che_Num" class="form-control input-sm" type="text" onkeypress="return validar_numeric(event);" onChange="validaCheque(this.value);" required>	
                                                                                <img class="imgMsg" /><label class="lblMsg"></label>												
                                                                  </div>                                 
                                                                </div>																									

                                                                <!-- Text input destinatario el cheque-->
                                                                <div class="form-group" id="btnBusca">
                                                                  <label class="col-sm-3 control-label label-sm required" for="cod_cuenta">Emitido a:</label>  
                                                                  <div class="col-sm-8">                                    
                                                                        <div class="input-group input-group-sm">                                                
                                                                                <input type="text" name="Prv_Cod" id="PrvCodBus" value="" style="display: none" />  
                                                                                <input id="docu" name="docu" type="text" class="form-control" placeholder="Seleccione destinatario de cheque..." required readonly />
                                                                                <span class="input-group-btn" >
                                                                                  <button class="btn btn-success" onclick="$('#provDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Proveedor"></span></button>
                                                                                </span>
                                                                        </div><!-- /input-group -->                              
                                                                  </div>                                  
                                                                </div>

                                                                <!-- select tipo asiento-->
                                                                <div class="form-group">
                                                                    <label class="col-sm-3 control-label label-sm required" for="Tia_Cod">Tipo de Egreso:</label>  
                                                                    <div class="col-sm-3">
                                                                              <select name="Tia_Cod" id="Tia_Cod" onchange="" class="form-control input-sm-3" required>
                                                                                    <option value="">Seleccione...</option>
                                                                                      <?Php 													
                                                                                          foreach($rs_TipAsiento as $row){ ?>
                                                                                          <option value="<?php echo $row['Tia_Cod']?>"><?php echo $row['Tia_Des'];?></option>
                                                                                          <?php } ?>
                                                                                  </select>
                                                                    </div>
                                                                    <label class="col-sm-3 control-label label-sm required" for="Cop_Num">Valor Efe./Che.:</label>  
                                                                    <div class="col-sm-3">                                    
                                                                              <input id="monto_rep" name="monto_rep" class="form-control input-sm" placeholder="0.00" type="text" required>					  
                                                                    </div> 
                                                                </div>
                                                            </fieldset>
							</div>
                        <div class="col-sm-6">											
                                <fieldset class="exa-fieldset">                           
                                <legend class="Titulos2">Datos de la Reposici&oacuten</legend>
                                        <!-- Text input Fecha-->
                                        <div class="form-group">
                                          <label class="col-sm-3 control-label label-sm required" for="fecha">Emitido:</label>  
                                          <div class="col-sm-3">                                    
                                                          <input id="fecha" name="fecha" class="form-control input-sm dateType" placeholder="0000-00-00" type="text" required />											  
                                          </div>                                          
                                          
                                        </div>										
                                        <!-- Text input Monto caja chica-->
                                        <div class="form-group">
                                          <label class="col-sm-3 control-label label-sm required" for="monto">Monto de Caja:</label>  
                                          <div class="col-sm-3">                                    
                                                        <input id="monto" name="monto" class="form-control input-sm" readonly placeholder="0.00" type="text" value="<?php echo number_format((float)$rs_busCajaCh['Cch_Val'],2,'.','');?>">					  
                                                        <input type="hidden" id="Cch_Cod" name="Cch_Cod" value="<?php echo $rs_busCajaCh['Cch_Cod'];?>">
                                          </div>   
                                          
                                          <label class="col-sm-3 control-label label-sm" for="Sal_Act">Saldo Actual:</label>  
                                          <div class="col-sm-3">                                    
                                                <input id="sal_act" name="sal_act" class="form-control input-sm" readonly placeholder="0.00" type="text"/>					  
                                          </div>
                                          <!--
                                          <label class="col-sm-3 control-label label-sm" for="Cop_Num">Saldo Anterior:</label>
                                            <div class="col-sm-3">                                    
                                                <input id="sal_ant" name="sal_ant" class="form-control input-sm" readonly placeholder="0.00" type="text" />
                                            </div> --> 									  
                                        </div> 
                                        <!-- Text input monto a reponer-->
                                        <div class="form-group">
                                          <label class="col-sm-3 control-label label-sm required" for="Cop_Num">Facturas/Vales:</label>  
                                          <div class="col-sm-3">                                    
                                                        <input id="fac_val" name="fac_val" class="form-control input-sm" readonly placeholder="0.00" type="text" required/>					  
                                          </div> 
                                          
                                          <label class="col-sm-3 control-label label-sm" for="Cop_Num">Reposicion Total:</label>  
                                          <div class="col-sm-3">                                    
                                            <input id="monto_rep_tot" name="monto_rep_tot" class="form-control input-sm" readonly placeholder="0.00" type="text"/>					  
                                          </div> 									  
                                        </div>

                                        <!-- Text input observacion -->
                                        <div class="form-group">
                                          <label class="col-sm-3 control-label label-sm" for="Cop_Num">Observaci&oacute;n:</label> 
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
							<div class="col-sm-4">
								<div class="form-group">  
									<div class="col-sm-60 center">
										<button type="button" onclick="imprimeGastos()" class="btn btn-success"><span class="glyphicon glyphicon-print"></span> Imprimir</button>
										<button type="button" onclick="resetForm();" class="btn btn-danger"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>
										<button type="submit"  class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
										<input type="hidden" id="hdd_save" name="hdd_save" value="1">
									</div>
								</div>									
							</div>
							<div class="col-sm-6">
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
        <center id="printCheque">
        <div id="modelo">
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
		</center>
		<center> 
            <button type="button" onclick="$('#successDialog').dialog('close');" class="btn btn-inverse fileinput-button" style="display: inline;" >
                    <i class="icon-ban-circle icon-white"></i>
                    <span>Cerrar</span>
             </button>            
            <a id="impReposi" target="_blank" href=""  style="display: inline;" title="Imprimir informe de reposici&oacute;n"><span  class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Reposici&oacute;n</span></span> </a>
			<a id="impCompr" target="_blank" href=""  style="display: inline;" title="Imprimir Comprobante Contable"><span  class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Asiento</span></span> </a>               
        </center>        
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
<!-- FIN DEL DIALOGO PROVEEDOR-->
<div id="imprimir" style="display: none">
	<div style="width: 1030px;">
	  <?php echo $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, '<p style="margin-left:10%;">GASTOS DE CAJA CHICA</p>', '<span style="margin-left:10%;" class="subtitle">Total de registros</span>', $obBD_conexion,false,9) ?>
	  <table id="tablaimpaApo" cellspacing="0" cellpadding="0" style="width: 1030px; border-collapse: collapse;table-layout: fixed;"></table>
	  <?php echo $obBD_con1->pieReporteStandar($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>
	</div>
</div>
<script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput/jquery.maskedinput.1.4.1.min.js"></script>
<script>
    
    
    
	
     
        
    
	
    
</script>

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