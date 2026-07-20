<!DOCTYPE unspecified PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?php	
/** 
* Descripci�n: Permite registrar las cuentas del plan de cuentas
* Fecha de actualizaci�n:	2011-04-13
* Desarrollador:	Lewis Chimarro
* Fecha de actualizaci�n:	2012-04-18
* Desarrollador:	Lewis Chimarro
* Fecha de actualizaci�n:	2012-04-18
* Desarrollador:	Lewis Chimarro
* Fecha de actualizaci�n:	2013-04-10
* Desarrollador:	Lewis Chimarro
* Fecha de actualizaci�n:	2015-03-07
* Desarrollador:	Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_planc_2.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	  
require_once('../../Librerias/postclass.php');	  
/**
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Con;
/**
* Creaci�n del objeto para evitar el reenvio 
*/
$thisPost = new Post_Block;  

/**
 * Grabado de un nuevo plan de cuentas
 */
if ($thisPost->postBlock($_POST['postID']))
{	
	if (isset($_POST['hdd_save']))
	{
		
		/**
		 * Opciones de guardado
		 */
		switch($_POST['hdd_save']){
			case 1:
				/**
				 * Inicio de transaccion
				 */
				$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
				$fecha = date("Y-m-d");
				$obBD_con1->operacionobBD(311, $des_plan.'*'.'A'.'*'.$_POST['plaCodigo'], $obBD_conexion);
				/**
				 * Guardar auditoria
				 */
				//$obBD_con1->grabarAuditoria($_SERVER['PHP_SELF'], $Ses_Usu_Cod, $obBD_conexion);
				/**
				 * Fin de transacci�n
				*/
				$obBD_con1->fin_transaccion($obBD_conexion->conexion);
			break;
			case 2:
				$count = $obBD_con1->getRowConsulta(7, $codpla.'*'.$Ses_Emp_Cod.'*'.$cod_cuenta.'*'.$pldCodigo, $obBD_conexion);
					
				if ($count['count'] > 0)
				{ ?>
				    <script language="javascript">
						alert('El c&oacute;digo de cuenta <?Php echo $cod_cuenta; ?> ya existe');
					</script>
				<?Php
				} 
				else 
				{
					/**
					 * Inicio de transaccion
					 */
					$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
					
					$obBD_con1->operacionobBD(8, $cod_cuenta.'*'.$des_cuenta.'*'.$tip_cuenta.'*'.$Pld_Deb.'*'.$Pld_Cre.'*'.$pldCodigo, $obBD_conexion);
					
					/**
					 * Guardar auditoria
					 */
					//$obBD_con1->grabarAuditoria($_SERVER['PHP_SELF'], $Ses_Usu_Cod, $obBD_conexion);
					/**
					 * Fin de transacci�n
					*/
					$obBD_con1->fin_transaccion($obBD_conexion->conexion);
				}
			break;
		}
	}
}

if(isset($_GET['ajax_mod'])){
	/**
	 * Obtener datos de este detalle de cuenta
	 */
	$row_det_plan = $obBD_con1->getRowConsulta(5, $_GET['Pld_Cod'], $obBD_conexion); 
?>
<FIELDSET>
<LEGEND>
	<label class="Titulos2">Agregar Cuenta</label>
</LEGEND>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
	   <tr>
		<td width="58" class="Etiqueta1"><span class="Asterisco">*</span> C&oacute;digo:</td>
		<td colspan="2" class="LetraNegra">
			<input name="cod_cuenta" type="text" id="cod_cuenta" value="<?php echo $row_det_plan['Pld_Cdc'];?>" onBlur="validar_cuentas(this.form, this); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_codigo&codpla=<?Php echo $row_det_plan['Pla_Cod'];?>&pldCodigo=<?php echo $_GET['Pld_Cod'];?>&cod_cuenta='+this.value, 'div_existe')"><div id="div_existe"></div>
			<input type="hidden" name="pldCodigo" id="pldCodigo" value="<?php echo htmlspecialchars($_GET['Pld_Cod'], ENT_QUOTES, 'UTF-8');?>" >
		</td>
		</tr>
		<tr>
			<td class="Etiqueta1"><span class="Asterisco">*</span> Cuenta:</td>
			<td class="LetraNegra"><textarea rows="3" cols="40" name="des_cuenta" id="des_cuenta" ><?php echo $row_det_plan['Pld_Des'];?></textarea></td>
		</tr>
		<tr>
			<td class="Etiqueta1">Tipo:</td>
			<td class="LetraNegra">&nbsp;
			<?php 
				/**
				 * Contar subcuentas
				 */
				$count = $obBD_con1->getRowConsulta(6, $Pld_Cod, $obBD_conexion);
				
				if($count['count'] == 0){
					?>
					<select name="tip_cuenta" id="tip_cuenta" onchange="bloqueo_asociacion();">
					   <option value="G" <?php if($row_det_plan['Pld_Tip'] == 'G'){echo 'selected';}?>>Grupo</option>
					   <option value="D" <?php if($row_det_plan['Pld_Tip'] == 'D'){echo 'selected';}?>>Detalle</option>
					</select>
					<?php
				}else{
					echo 'Grupo&nbsp;&nbsp;&nbsp;
					<img src="../../mascaras/model1/imagenes/32x32/advertencia.png" width="20" height="20" /><font color="#FF0000"> No se puede cambiar el tipo porque posee sub-cuentas</font>
					<input name="tip_cuenta" id="tip_cuenta" type="hidden" value="'.$row_det_plan['Pld_Tip'].'">';
					
				}
			?>
            
			</td>
		</tr>
	</table>
</FIELDSET>
<FIELDSET>
	<LEGEND><label class="Titulos2">Asociaci&oacute;n Presupuestaria</label></LEGEND>            
	<table width="100%" border="0" cellpadding="0" cellspacing="0">            
		<tr>
			<td width="58" class="Etiqueta1">D&eacute;bitos:</td>
			<td class="LetraNegra"><input name="Pld_Deb" type="text" <?php if($row_det_plan['Pld_Tip'] == 'G'){echo 'disabled="disabled"';}?> id="Pld_Deb" value="<?php echo $row_det_plan['Pld_Deb'];?>" /></td>
		</tr>
		<tr>
			<td class="Etiqueta1">Cr&eacute;ditos:</td>
			<td class="LetraNegra"><input name="Pld_Cre" type="text" <?php if($row_det_plan['Pld_Tip'] == 'G'){echo 'disabled="disabled"';}?> id="Pld_Cre" value="<?php echo $row_det_plan['Pld_Cre'];?>"/></td>
		</tr>
	</table>
</FIELDSET>
<?php
$obBD_con1->liberar();
$obBD_conexion->cerrar();	
exit();
}

if (isset($ajax_codigo))
{
	$count = $obBD_con1->getRowConsulta(7, $codpla.'*'.$Ses_Emp_Cod.'*'.$cod_cuenta.'*'.$pldCodigo, $obBD_conexion);

	if ($count['count'] > 0)
	{
	?>
    	<font color="#FF0000">El c&oacute;digo de cuenta <?Php echo $cod_cuenta; ?> ya existe</font>
    <?Php	
	}
	
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}
?>
<html>
<head>
 <title><?Php echo $Ses_Sys_Nom; ?></title>
 <?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>    
 <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
 <script language="javascript" src="../VALIDACIONES/con_val_planc.js"></script>
 <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
 <script type="text/javascript">$(function() { $('#set1 *').tooltip({showURL: false}); });</script>
 <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>
 <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<body>
<div id='set1'>
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
<tr class="BarraTitulo">
    <td height="10">&raquo; Modificar Plan de Cuentas</td>
</tr>
<tr>
<td height="400" valign="top">
	
  	<?php 
  	switch($pag){
  		default:
  			/**
  			 * Cargado de los planes de cuenta de una empresa en especifico.
  			 */
  			$row_rs_planes = $obBD_con1->getArrayConsulta(302, $Ses_Emp_Cod,$obBD_conexion);
  			?>
  			<FIELDSET>
				<LEGEND>
					<label class="Titulos2">Resultados de la Busqueda</label>
				</LEGEND>
				<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
					<thead>
      					<tr>
        					<th width="8%"><strong>C&oacute;d. Int.</strong></th>
        					<th ><strong>Descripci&oacute;n</strong></th>
        					<th width="4%">&nbsp;</th>
        					<th width="4%">&nbsp;</th>
      					</tr>
					</thead>
    				<tbody>
      				<?php foreach($row_rs_planes as $row){
	 		 			if($row['Pla_Est']=='Inactivo'){ 
							$rojo='#FF0000';
							if(!isset($com_leyenda[1]))$com_leyenda[1]=1;
						}else{
							$rojo='';
						}			
						?>
					      <tr>
					        	<td align="center"><FONT COLOR="<?php echo $rojo;?>"><?php echo $row['Pla_Cod']; ?></FONT></td>
					        	<td><FONT COLOR="<?php echo $rojo;?>"><?php echo $row['Pla_Obs']; ?></FONT></td>
					        	<td align="center">
					        		<button type="button" class="btn btn-primary btn-mini" title="Editar" onclick="Muestra_Aparecer();$('#plaCodigo').val('<?php echo $row['Pla_Cod']; ?>');$('#des_plan').val('<?php echo $row['Pla_Obs']; ?>');"><i class=" icon-edit icon-white"></i></button>
					        	</td>
					        	<td align="center">
							        <form name='form1' method='post'  action='<?php echo $_SERVER['PHP_SELF'];?>'>
							        <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
							        	<i class=" icon-arrow-right icon-white"></i>
							        </button>
							        <input type="hidden" id="codpla" name="codpla" value="<?php echo $row['Pla_Cod']; ?>" />
							        <input type="hidden" id="pag" name="pag" value="1" />
							        <input type="hidden" id="np" name="np" value="0" />
							       </form> 
								</td>
					      </tr>
				      <?php 
      				}
					  /**
					   * Mostrar un mensaje si no existen planes creados 
					   */
					  if (count($row_rs_planes) == 0) 
					  {
						?>
							<tr>
								<td>&nbsp;</td>
					  	  		<td><?Php echo error_alerta("�No hay resultados que mostrar!", 1) ?></td>
					  	  		<td>&nbsp;</td>
					  		</tr>
						<?php
					  }
					  ?>
			 		</tbody>        
			    </table>   
			</FIELDSET>
  			<?php
  			echo barra_estado(count($row_rs_planes)).'<br>';
  			require_once('../../componentes/FRONT/com_con_leyenda.php');
  			?>
  			<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
  			<div id="bgmodal"  class="bgmodal" style="display:none" >
 				<div id="ajax_modal">
 				<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form2" id="form2">
 				<FIELDSET>
					<LEGEND>
						<label class="Titulos2">Modificar Plan de Cuentas</label>
					</LEGEND>
						<?php
						mensaje_requerido(); 
						$thisPost->startPost();	 ?>
						<table width="100%" border="0" cellpadding="0" cellspacing="0">
						  <tr>
							<td width="14%" class="Etiqueta1"><span class="Asterisco">*</span> Descripci&oacute;n: </td>
							<td width="86%" class="LetraNegra">
							  &nbsp;
							  <input name="des_plan" type="text" id="des_plan" size="50" maxlength="50">
							</td>
						  </tr>
						  <tr>
						    <td>&nbsp;</td>
						    <td><span class="LetraNegra">
						      <input name="codemp" type="hidden" id="codemp" value="<?php echo $txt_busqueda; ?>">
						      </span></td>
						    </tr>
						  </table>
   			 	</FIELDSET>
   			 	<br>
   			 	<table>
   			 		<tr>
   			 			<td width="110">
							<button type="button" class="btn btn-primary start" title="Guardar" onclick="validar_requeridos(this.form, 'des_plan', 1)">
				            <i class="icon-book icon-white"></i>
				            <span>Guardar</span>
				    		</button>
				    		<input type="hidden" name="hdd_save" id="hdd_save" value="1">
				    		<input type="hidden" name="plaCodigo" id="plaCodigo" value="">
   			 			</td>
   			 		</tr>
   			 	</table>
   			 	</form> 
 				</div>
 			</div>
  			<?php
  			break;
		case 1:
			/**
			 * Cargado de las cuentas del plan
			 */
			$row_rs_nodos = $obBD_con1->getArrayConsulta(303, $Ses_Emp_Cod.'*'.$codpla.'*'.$np, $obBD_conexion);
			
			/**
			 * Cargado del plan de cuenta de una empresa en especifico.
			*/
			$row_rs_plan = $obBD_con1->getRowConsulta(310, $codpla, $obBD_conexion);
			?>
			<FIELDSET>
				<LEGEND>
					<label class="Titulos2"><?Php echo $row_rs_plan['Pla_Obs']; ?></label>
				</LEGEND>
				<table width="516" border="0" cellpadding="0" cellspacing="0">
				  <tr>
					<td width="123" class="Etiqueta1">Usted esta editando: </td>
					<td width="393" class="LetraNegra"><strong>&nbsp;
					  <?Php if ($np == 0) {
							echo "INICIO del Plan de Cuentas";
							$separador='';
						}else{
							$row_rs_direc = $obBD_con1->getRowConsulta(305, $np, $obBD_conexion);
							echo $row_rs_direc['Pld_Cdc'].".-  ".$row_rs_direc['Pld_Des'];
							$separador='.';
						}
						?>
					</strong>
					</td>
				  </tr>
				</table>
			</FIELDSET>
			<fieldset>
				<LEGEND>
					<label class="Titulos2">Detalle de Cuentas:</label>
				</LEGEND>
				<table width="100%" cellpadding="0" cellspacing="0" class="fixedHeader03">
				    <thead>
					  <tr>
						<th width="8%" class="Cabecera"><strong>C&oacute;d. Int.</strong></th>
						<th width="9%" class="Cabecera">C&oacute;digo</th>
						<th ><strong>Cuenta</strong></th>
						<th width="10%" class="Cabecera"><strong>Tipo</strong></th>
						<th width="10%" class="Cabecera"><strong>Estado</strong></th>
						<th width="13%" class="Cabecera">A. P. D&eacute;bito</th>
						<th width="14%" class="Cabecera">A. P. Cr&eacute;dito</th>
						<th width="4%" class="Cabecera">&nbsp;</th>
						<th width="4%" class="Cabecera">&nbsp;</th>
						</tr>
				   </thead>
				   <tbody>
				   <?php 				   
				   foreach($row_rs_nodos as $row){
						$num_cuenta = $row['Pld_Cdc'];
						if ($row['Pld_Est'] == 'Inactivo'){ 
							$color_d = '#FF0000'; 
							if(!isset($com_leyenda[1]))$com_leyenda[1]=1;
						}else{
							$color_d = '';	
						}?>
					  <tr>
							<td align="center"><font color="<?php echo $color_d; ?>"><?php echo $row['Pld_Cod']; ?></font></td>
							<td><font color="<?php echo $color_d; ?>"><?php echo $row['Pld_Cdc']; ?></font></td>
							<td><font color="<?php echo $color_d; ?>"><?php echo $row['Pld_Des']; ?></font></td>
							<td align="center"><font color="<?php echo $color_d; ?>"><?php echo $row['Pld_Tip']; ?></font></td>
							<td align="center"><font color="<?php echo $color_d; ?>"><?php echo $row['Pld_Est']; ?></font></td>
							<td align="center"><font color="<?php echo $color_d; ?>"><?php echo $row['Pld_Deb']; ?></font></td>
							<td align="center"><font color="<?php echo $color_d; ?>"><?php echo $row['Pld_Cre']; ?></font></td>
							<td align="center">
								<button type="button" class="btn btn-primary btn-mini" title="Editar" onclick="ajax_datos('<?php echo $_SERVER['PHP_SELF'];?>?ajax_mod=1&Pld_Cod=<?php echo $row['Pld_Cod'];?>','muestra');Muestra_Aparecer();bloqueo_asociacion();"><i class=" icon-edit icon-white"></i></button>
							</td>
							<td align="center">
					        <?Php
							if ($row['Pld_Tip']=='GRUPO')
							{
							?>
						        <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form3">
							        <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
							        	<i class=" icon-arrow-right icon-white"></i>
							        </button>
							        <input type="hidden" id="pag" name="pag" value="1" />
							        <input type="hidden" id="codpla" name="codpla" value="<?php echo $codpla; ?>" />
							        <input type="hidden" id="np" name="np" value="<?php echo $row['Pld_Cod']; ?>" />
						        </form>
					        <?Php
					        }
					        else
					        {
								echo "&nbsp;";	
							}
							?>
					        </td>
					</tr>
	  			<?php }
	  				if(count($row_rs_nodos)==0){
	  					?>
	  					<tr><td>&nbsp;</td>
					  	  <td>&nbsp;</td>
					  	  <td><?Php echo error_alerta("No hay ninguna cuenta creada", 1) ?></td>
					  	  <td>&nbsp;</td>
					  	  <td>&nbsp;</td>
					  	  <td>&nbsp;</td>
					  	  <td>&nbsp;</td>
					  	  <td>&nbsp;</td>
					  	</tr>
	  					<?php
	  				}?>
				   </tbody>
				</table>
			</fieldset>
			<?php
			echo barra_estado(count($row_rs_nodos)).'<br>';
			?>
			<table>
			  	<tr>
			  		<td width="110">
			  			<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form3">
				        	<button type="button" class="btn btn-inverse fileinput-button" title="Volver al Plan de Cuentas" onclick="this.form.submit();"> <i class="icon-step-backward icon-white"></i> <span>&nbsp;&nbsp;Inicio&nbsp;&nbsp;</span></button>
				        </form>
			  		</td>
			  		<?php 
			  		if ($np != 0){
				  		/**
				  		 * Link para volver atr�s
				  		 */
				  		$row_rs_direca = $obBD_con1->getRowConsulta(306, $np, $obBD_conexion);
			  		?>
			  		<td width="110">
				  		<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form3">
							<button type="button" class="btn btn-inverse fileinput-button" title="Atr�s" onClick="this.form.submit()"><i class=" icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span></button>
	       					<input type="hidden" id="pag" name="pag" value="1" />
	       					<input type="hidden" id="codpla" name="codpla" value="<?php echo $codpla; ?>" />
	       					<input type="hidden" id="np" name="np" value="<?php echo $row_rs_direca['Pld_Rec']; ?>" />
	       				</form>
	       			</td>
	       			<?php }?>
			  	</tr>
			  	</table>
			 <?php
			require_once('../../componentes/FRONT/com_con_leyenda.php');
			?>
			<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
  			<div id="bgmodal"  class="bgmodal" style="display:none" >
 				<div id="ajax_modal">
 				<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form3" id="form3">
				    <?php 
				    	mensaje_requerido();
						$thisPost->startPost();
					?>
						<div id="muestra"></div>
				    <br />
				    <table width="300" border="0" cellspacing="0" cellpadding="0">
				      <tr>
				        <td width="212">         
				          <button type="button" class="btn btn-primary start" title="Guardar" onclick="validar_requeridos(this.form,'cod_cuenta*des_cuenta', 1)">
				            <i class="icon-book icon-white"></i>
				            <span>Guardar</span>
				          </button>
				          <input type="hidden" name="hdd_save" id="hdd_save" value="2">
				          <input type="hidden" name="pag" id="pag" value="1">  
				          <input name="np" type="hidden" id="np" value="<?php echo $np; ?>">    
				          <input name="ncuenta" type="hidden" id="ncuenta" value="ncuenta">
				          <input name="codpla" type="hidden" id="codpla" value="<?php echo $codpla; ?>">
				        </td>
				      </tr>
				    </table>
				   </form> 
 				</div>
 			</div>
			<?php
			break;
  	}  	
  	?>
    </td>
    </tr>
    </table>    
  </div>
<script type="text/javascript" src="../VALIDACIONES/con_par_planc.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
</body>
</html>
<?php 
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>