<?php 

/** 
 * Alias:	Insertar
 * Descripción: Permite dar de alta el mantenimiento del activo que se planifico.
 * Desarrollador:	Didimo Zamora
 * **********************************
 * Fecha de actualización:	2011-04-21
 * Desarrollador: Dídimo Zamora M.
 * Fecha de actualización:	2013-05-28
 * Fecha de actualización:	2013-08-07
 */
 
//Variables de Sesion estaticas 
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_mantenimie.php');	
require_once('../../Librerias/procedimientos/almacenados_standar.php');	  
require_once('../../Librerias/postclass.php');	 	


/**
 * Creacion del Objeto de conexion 
 */
$obBD_conexion = new Class_Log_Conexion_Mantenimiento($Ses_Dat_Dis);
/**
 * Cracion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_Mantenimiento;
/** 
 * Creación del objeto para evitar el reenvio 
 */
$thisPost = new Post_Block;

/**
 * Modal para la verificación del mantenimiento del activo.
 */
if (isset($ajax_Rev))
{	
	/**
	 * Consulta de mantenimientos por codigo del mantenimeito
	 */
		$rs_Mante= $obBD_con1->getRowConsulta(458,$Man_Cod, $obBD_conexion);
	/**
	* Consulta el detalle de los departamentos  por 
	*/
		$rs_cargo = $obBD_con1->getRowConsulta(672,$Tic_Cod, $obBD_conexion);
		$total_rs_cargo = count($rs_cargo);
		$Man_Ter_aux=$Man_Ter1;		
	/**
	 * Toma la fechas del mantenimiento para validar entrada de  fechas de termino.
	 */
		$anio=substr($rs_Mante['Man_Fec'], 0, 4);
		$mes=substr($rs_Mante['Man_Fec'], 5, 2);
		$dia=substr($rs_Mante['Man_Fec'], 8, 2);
		
?>
	<script>
		$(function() { 
				 $( "#Man_Fet" ).datepicker({
				  changeMonth:true, 
				  changeYear:true, 
				  dateFormat: "yy-mm-dd",
				  minDate: new Date(<?Php echo $anio;?>, <?Php echo $mes - 1?>, <?Php echo $dia;?>),
				 });
				 });
	</script>
	<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form8" id="form8">
	<fieldset>
		<LEGEND>
			<label class="Titulos2">Detalles de la reparaci&oacute;n del mantenimiento</label>
		</LEGEND>
<?php //Creacion del campo REPOST
	$thisPost->startPost();
?>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td colspan="2"><?Php echo mensaje_requerido(); echo $Man_Pro;?></td>
        </tr>
        <tr>
            <td width="16%" align="right" class="Etiqueta1"><div align="right"> <span class="Asterisco">*</span>Fecha Terminaci&oacute;n:</div></td>
            <td width="84%">
			<input id="Man_Fec" name="Man_Fec" type="hidden" value="<?Php echo $rs_Mante['Man_Fec'];?>"> 
            		<input name="Man_Fet" type="text" id="Man_Fet" value="<?php echo $rs_Mante['Man_Fec'];?>" size="10" onKeyUp="mascara(this,'-',patron,true)">
             
        	</td>
        </tr>
        <tr>
            <td width="16%" align="right" class="Etiqueta1"><span class="Asterisco">*</span>Detalle de la Reparaci&oacute;n:</td>
            <td width="84%"><?Php if($rs_Mante['Man_Pro']=='1'){ echo "<div class='LetraNegra' align='left'>&nbsp;".$rs_Mante['Man_Des']."</div>";}else{?><textarea name="Man_Des" id="Man_Des" cols="50"></textarea><?php }?>
            </td>
        </tr>
        <tr>
            <td width="20%" align="right" class="Etiqueta1"><span class="Asterisco">*</span>Observaciones:</td>
            <td><?Php if($rs_Mante['Man_Pro']=='1'){ echo "<div class='LetraNegra' align='left'>&nbsp;".$rs_Mante['Man_Obs']."</div>";}else{?><textarea  name="Man_Obs" id="Man_Obs" cols="50"></textarea><?php }?>
            </td>
        </tr>
	</table>	
	</fieldset>
<br> 
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
		<td width="100%" height="50">
        <?Php if($rs_Mante['Man_Pro']=='0'){?>
	  	  <button name="boton_guardar" id="boton_guardar" type="button" class="btn btn-primary fileinput-button" value="Guardar" title="Guardar verificaci&oacute;n de mantenimiento" onClick="validar_requeridos(this.form,'Man_Fet*Man_Des*Man_Obs',1)">         
        <i class="icon-book icon-white"></i>
		<span>&nbsp;Guardar&nbsp;</span>
          </button>
          <input name="Act_Cod" type="hidden" id="Act_Cod" value="<?php echo $Act_Cod; ?>">
          <input name="Man_Cod" type="hidden" id="Man_Cod" value="<?php echo $Man_Cod; ?>">         
          <input name="hdd_save_rev" type="hidden" id="hdd_save_rev" value="insertar">         
          <?php }?>
		</td>
    </tr>
	</table>     
	</form>
<?Php
exit();
}
/**
 * Consulta los Estados 
 */
$rs_est_act = $obBD_con1->consulta(sentencias_con(423,''), $obBD_conexion->conexion);
$row_rs_est_act = $obBD_con1->registros();
$total_rs_est_act = $obBD_con1->numregistros();
if ($thisPost->postBlock($_POST['postID'])){ 
	if (isset($hdd_save) && !isset($hdd_volver)){ 
		
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);				
		/** 
		 * Insercion del mantenimiento
		 */
		$obBD_con1->operacionobBD(456, $Tma_Cod.'*'.$Act_Cod.'*'.$Ema_Cod.'*'.$Man_Fec.'*'.$Est_Cod, $obBD_conexion);
		
		//$obBD_con1->grabarAuditoria($_SERVER['PHP_SELF'], $Ses_Usu_Cod, $obBD_conexion);
		//$Act_Cod = $obBD_con1->insercionid($obBD_conexion->conexion);						
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);		
	 }
	 
	 if (isset($hdd_save_rev) && !isset($hdd_volver)){ 	
	 	/**
		 * Actualiza los datos de la reparación del Activo.
		 */
		 $obBD_con1->inicio_transaccion($obBD_conexion->conexion);	
	 	$obBD_con1->operacionobBD(481, $Man_Des.'*'.$Man_Fet.'*'.$Man_Obs.'*'.$Man_Cod, $obBD_conexion);
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);	
	 }
	 	 
	 if($txt_busqueda != "")
	 { 	 
	 	if ($op_opciones == "d")
		{			
			/**
			 * Busqueda del activo x medio de la descripcion
			 */
			$rs_buscar = $obBD_con1->getArrayConsulta(500,$txt_busqueda.'*'.$Ses_Emp_Cod, $obBD_conexion);		
		}
		if ($op_opciones == "cs")
		{			
			/**
			 * Busqueda del activo x medio del codigo secuencial
			 */
		 	$rs_buscar = $obBD_con1->getArrayConsulta(501,$txt_busqueda.'*'.$Ses_Emp_Cod, $obBD_conexion);					
		}
		if ($op_opciones == "cb")
		{			
			/**
			 * Busqueda del activo x medio del codigo de barra
			 */
		 	$rs_buscar = $obBD_con1->getArrayConsulta(502,$txt_busqueda.'*'.$Ses_Emp_Cod, $obBD_conexion);		
		}	
		if ($op_opciones == "ns")
		{			
			if (isset($Cam_Cod)){
			 /**
			  * Busqueda del activo x medio del  código del campo
			  */
		 	$rs_buscar = $obBD_con1->getArrayConsulta(503,$Cam_Cod.'*'.$txt_busqueda, $obBD_conexion);		
			}
		}	
		$total_rs_buscar = count($rs_buscar);	
	 }else
		{
		/** 
		 * Consulta realizada en base al código seleccionado 
		 */
			if (isset($codigo))
			{
				$rs_consultar = $obBD_con1->getRowConsulta(431, $codigo, $obBD_conexion);
				$row_rs_consultar = $rs_consultar;
				$total_rs_consultar =count($codigo);
			}
		}	
}
?> 
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
			<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
			<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
			<script language="javascript" src="../VALIDACIONES/Validaciones.js"></script>
			<script language="javascript" src="../VALIDACIONES/act_val_mantenimie.js"></script>        
			<script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
			<script type="text/javascript">$(function() { $('#set1 *').tooltip({showURL: false}); });</script>
			<script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>
			<script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script>
			<script>
				$(function() { 
				$( "#Man_Fec" ).datepicker({changeMonth:true, changeYear:true, dateFormat: "yy-mm-dd"});
				 });		 
				
			</script>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>

<div id='set1'>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr class="BarraTitulo">
		<td height="10">&raquo; Ejecutar el mantenimiento del activo</td>
	</tr>
	<tr>
		<td valign="top">  
		<fieldset>
		<LEGEND>
			<label class="Titulos2">Buscar por:&nbsp;</label>
		</LEGEND>
		<form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">
		<table width="762" height="72" border="0" cellpadding="0" cellspacing="0">
		<tr>    
		<td colspan="3">
			<table width="633" border="0">
				<tr>
					<td width="105"><input name="op_opciones" type="radio" value="d"  checked  onClick="document.getElementById('op_cam').value=this.value; busquedaCampos();setfocus(this.form.txt_busqueda);">              
                    <span class="LetraNegra">Descripción</span> <input name="op_cam" id="op_cam" type="hidden" value="d"></td>
					<td width="125"><input type="radio" name="op_opciones" value="cb" <?Php if($op_opciones== 'cb'){?> checked <?php } ?> onClick="document.getElementById('op_cam').value=this.value; busquedaCampos();setfocus(this.form.txt_busqueda);">
                    <span class="LetraNegra">Código de Barra</span></td>
					<td width="122"><input type="radio" name="op_opciones" value="cs" <?Php if($op_opciones== 'cs'){?> checked <?php } ?> onClick="document.getElementById('op_cam').value=this.value; busquedaCampos();setfocus(this.form.txt_busqueda);" >
                    <span class="LetraNegra">Código Secuencial</span></td>
                    <td width="263"><input type="radio" name="op_opciones" value="ns" <?Php if($op_opciones== 'ns'){  ?> checked <?php } ?> onClick="document.getElementById('op_cam').value=this.value; busquedaCampos();setfocus(this.form.Cam_Cod);">
                    <span class="LetraNegra">Por Campo</span>
<?Php
					/**
					 * consulto los campos que esten definidos como busqueda 
					 */
					$rs_campos=$obBD_con1->getArrayConsulta(470, '', $obBD_conexion);
?>
                    <select name="Cam_Cod" id="Cam_Cod" onChange="setfocus(this.form.txt_busqueda);">
<?Php 
						foreach($rs_campos as $row_rs_campos){
?>  
							<option  value="<?php echo $row_rs_campos['Cam_Cod'];?>"><?PHP  echo $row_rs_campos['Cam_Cor'];?></option>
<?Php 
						} //fin foreach($rs_campos as $row_rs_campos){
?> 
                    </select>
					</td>
				</tr>             
			</table>
		</td>
		</tr>
		<tr>
			<td width="119" height="44"class="BarraBusqueda"><div align="right"><span class="Asterisco">*</span> Activo: </div></td>
			<td width="368"  class="BarraBusqueda">&nbsp;<input size="50" name="txt_busqueda" type="text" id="txt_busqueda">
			</td>
			<td align="left" width="144" class="BarraBusqueda">
				<div align="left">
				<button name="btn_aceptar" type="submit" class="btn btn-success fileinput-button" id="btn_aceptar" value="Aceptar" title="Listar Activos">
				<i class="icon-search icon-white"></i>
				<span>&nbsp;Buscar&nbsp;</span>
				</button>
				</div>
			</td>
			<td width="131"></td>
		</tr>
		</table>
	<script> 
        ShowHide('Cam_Cod');
    </script>
		</form>
     </fieldset>   
        
<?php 
if (isset($txt_busqueda)){?>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Resultados de la busqueda</label>
	</LEGEND>
		<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
		<thead>
		<tr>
			<th width="5%">Cód. Int.</th>
			<th width="35">SubGrupo</th>
			<th width="40">Descripci&oacute;n </th>
			<th width="20">Secuencial</th>
			<th>&nbsp;</th>
		</tr>
		</thead>
		<tbody>
<?Php 
		if ($total_rs_buscar > 0){  
			foreach($rs_buscar as $row_rs_buscar){   	
				if($row_rs_buscar['Act_Est']=='I'){ 
					$rojo='#FF0000'; $anulada++; 
				}else{
					$rojo='';
					 }
?>
		<tr>
			<td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php echo $row_rs_buscar['Act_Cod'];?></FONT></td>
			<td title="<?php echo $row_rs_buscar['Tia_Des'];?>"><FONT COLOR="<?php echo $rojo;?>"><?Php echo marcar_cadena($txt_busqueda, $row_rs_buscar['Tia_Des'],'#FFFF00', 1);?></FONT></td>
			<td  title="<?php echo $row_rs_buscar['Act_Des'];?>"><FONT COLOR="<?php echo $rojo;?>"><?Php echo marcar_cadena($txt_busqueda, $row_rs_buscar['Act_Des'],'#FFFF00', 1);?></FONT></td>
			<td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php echo  $row_rs_buscar['Act_Cdc'];?></FONT></td>
			<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "frml" id="forml">
			<td align="center" width="5%">
<?Php 
			if($row_rs_buscar['Act_Est']=='A'){
?>
			<button type="button" name="imageField"  class="btn btn-success btn-mini"  width="22" height="22" title="Seleccionar" onClick="this.form.submit()">	
				<i class="icon-arrow-right icon-white"></i>
			</button>  				
			<input type="hidden" name="codigo" id="codigo" value="<?Php echo $row_rs_buscar['Act_Cod'];?>"/>
			<input type="hidden" name="hdd_aux" id="hdd_aux" value="1">
			<input type="hidden" name="volver_busqueda" id="volver_busqueda" value="<?Php echo $txt_busqueda;?>"/>
			<input type="hidden" name="volver_opciones" id="volver_opciones" value="<?php echo $op_opciones;?>">
			<input type="hidden" name="volver_Cam_Cod" id="volver_Cam_Cod" value="<?php echo $Cam_Cod;?>">
<?Php
			}
			else{ echo "&nbsp;";}
?>		     
			</td>
			</form>
			</tr>
<?Php 
			} //Fin foreach($row_rs_buscar as $row_rs_buscar){      
		}else{
?>
		<tr>
			<td> </td>
			<td> </td>
			<td align="center"><?Php echo error_alerta("¡No hay resultados que mostrar!", 1) ?></td>
			<td> </td>
			<td> </td>
		</tr>
<?php
		} // fin del if ($total_rs_buscar > 0)
?>
		</tbody>
		</table>
<?Php
	/**
	 * Muestra la barra de estados con la cantidad de registros encontrados 
	 */
	echo barra_estado($total_rs_buscar+0);
?>
	</FIELDSET>
<?php
}
if ($hdd_aux==1){ 
	$rs_mant=$obBD_con1->getArrayConsulta(479,$codigo,$obBD_conexion);
	$rs_mant_Tot=count($rs_mant);
?>
	<form method="post" name= "form2" action="<?php echo $_SERVER['PHP_SELF'];?>">
<?php 
	//Creacion del campo REPOST
	$thisPost->startPost();
?>  
    <br>
    <FIELDSET>
        <LEGEND>
            <label class="Titulos2">Mantenimientos por ejecutarse</label>
        </LEGEND>
            
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td class="Etiqueta1"> Código Activo:</td>
                <td width="28%" class="LetraNegra">&nbsp;<?php echo $row_rs_consultar["Act_Cdc"]?></td>
                <td width="8%" ><span class="Etiqueta1">Descripción:</span></td>
                <td width="50%" class="LetraNegra">&nbsp;<?php echo $row_rs_consultar["Act_Des"]?></td>
            </tr>
            <tr>
                <td width="14%" class="Etiqueta1"> Tipo de Activo:</td>
                <td class="LetraNegra">&nbsp;<?php echo $row_rs_consultar["Tia_Des"];?></td>
                <td></td>
                <td width="50%"></td>
            </tr>
            <tr>
                <td width="14%" align="left" class="Etiqueta1"> Proveedor:</td>
                <td class="LetraNegra">&nbsp;<?php
               /**
                * Consulta los Proveedores por codigo del proveedor
                */
                $rs_prv_act = $obBD_con1->getRowConsulta(467,$Ses_Emp_Cod.'*'.$rs_consultar["Prv_Cod"], $obBD_conexion);
                echo $rs_prv_act['Nombre'];
?>
                </td>
                <td><span class="Etiqueta1">Custodio :</span></td>
                <td width="50%">&nbsp;<span class="LetraNegra">
<?php 
                /** 
                * Consulta el  Custodio 
                */
                $rs_cus_act = $obBD_con1->getRowConsulta(432,$rs_consultar["Act_Cod"], $obBD_conexion);
                echo $rs_cus_act["Nombre"];  ?>
                </span></td>
            </tr>
			</table>            
            <br>
            <table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader03">
            <thead>
              <th width="5%">Cod Int</th>
                <th width="8%">Fecha Ini</th>
                <th width="10%">Estado del Activo</th>
                <th width="8%">Fecha Fin</th>
                <th width="20%">Encargado Empresa</th> 
                <th width="20%">Detalles del Mant</th> 
                <th width="24%">Observaciones del Mant</th>               
                <th width="5%"> </th>
            </thead>
            <tbody>
<?Php
		if($rs_mant_Tot>0){
			foreach($rs_mant as $row_rs_mant){	
?>
            <tr>
                <td align="center"><?php echo $row_rs_mant['Man_Cod'];?></td>
                <td align="center"><?php echo $row_rs_mant['Man_Fec'];?></td>
                <td align="center"><?php echo $row_rs_mant['Est_Des'];?></td>
                <td align="center"><?php echo $row_rs_mant['Man_Fet'];?></td>
                <td align="left"><?php echo $row_rs_mant['Encargado'];?></td>
                <td align="left"><?php echo $row_rs_mant['Man_Des'];?></td>
                <td align="left"><?php echo $row_rs_mant['Man_Obs'];?></td>              
                <td>
                <input id="Man_Cod" name="Man_Cod" value="<?Php echo $row_rs_mant['Man_Cod'];?>" type="hidden">
<?Php
				/** :=)
				 *  Segun el estado del Mantenimiento presento el modal. 
				 * (solo mostrar datos o dar de alta)
				 */
				switch($row_rs_mant['Man_Pro']){
					case 0:
?>
					<button type="button" name="imageField2" width="22" height="22" title="Dar de alta Mantenimiento"  class='btn btn-success fileinput-button' onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_Rev=1&Man_Cod=<?Php echo $row_rs_mant['Man_Cod'];?>&Act_Cod=<?php echo $codigo;?>&volver_opciones=<?php echo $volver_opciones?>&volver_busqueda=<?Php echo $volver_busqueda?>','ajax_modal');">	
                   	 <i class='icon-ok-sign icon-white'></i>
                	</button>			
<?Php
					break;
					case 1:
?>
					<button  type="button" name="imageField" class="btn btn-primary btn-mini"  width="32" height="32" title="Procesado.. ver detalles!!"  onClick="Muestra_Aparecer();ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_Rev=1&Man_Cod=<?Php echo $row_rs_mant['Man_Cod'];?>&Act_Cod=<?php echo $codigo;?>&volver_opciones=<?php echo $volver_opciones?>&volver_busqueda=<?Php echo $volver_busqueda?>','ajax_modal');">	
                    <i class='icon-info-sign icon-white'></i>
                	</button>	
<?Php				
					break;
				}
?>
									
                </td>
            </tr>
<?Php
			}
		}else{
?>			 
			<tr>
            	<td align="center" class="LetraNegra">&nbsp;</td>
            	<td align="center" class="LetraNegra">&nbsp;</td>
            	<td align="center" class="LetraNegra">&nbsp;</td>
                <td align="center" class="LetraNegra">&nbsp;</td>
                <td align="center" class="LetraNegra"><?Php echo error_alerta("¡No hay resultados que mostrar!", 1) ?></td>
                <td align="center" class="LetraNegra">&nbsp;</td>
                <td align="center" class="LetraNegra">&nbsp;</td>
                <td align="center" class="LetraNegra">&nbsp;</td>
			</tr>
<?Php
		}
?>
            </tbody>
          	</table>            
<?Php
	if($rs_mant_Tot>0){
		echo barra_estado($rs_mant_Tot);	
	}
?>
	</FIELDSET>  

<br> 
<table width="112" height="36" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="36%">
			<button type="button" name="btn_atras" id="btn_atras" value="Enviar" class="btn btn-inverse fileinput-button" title="Atras" onClick="campos_hide(this.form, '<?Php echo "txt_busqueda*op_opciones*hdd_volver*Cam_Cod"; ?>','<?Php echo $volver_busqueda.'*'.$volver_opciones.'*'.'1'.'*'.$volver_Cam_Cod; ?>')">
			<i class=" icon-arrow-left icon-white"></i>
			<span>&nbsp;Atr&aacute;s&nbsp;</span>
			</button>
		</td>
		
	</tr>
</table>
<?Php 
if ($anulada > 0)
{		
	$com_leyenda[1]=$anulada;
}//Fin del if ($anulada > 0)
?>
<br/>
<?php
require_once('../../componentes/FRONT/com_con_leyenda.php');?>    
	</form>		
		</td>
	</tr>
</table>
<?php } ?>
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
	  	<div id="bgmodal"  class="bgmodal" style="display:none" >
	 		<div id="ajax_modal">
				<div id="muestra"></div>
	 		</div>
	 	</div>    
</div>        
	<script type="text/javascript" src="../VALIDACIONES/act_par_mantenimie.js"></script>
    <script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
</BODY>
</HTML>
<?php
/**
 * Cerrado de las conexiones 
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>