<!DOCTYPE unspecified PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?php	
/** 
* Descripción: Permite registrar las cuentas del plan de cuentas
* Fecha de actualización:	2011-04-13
* Desarrollador:	Lewis Chimarro
* Fecha de actualización:	2012-04-18
* Desarrollador:	Lewis Chimarro
* Fecha de actualización:	2012-04-18
* Desarrollador:	Lewis Chimarro
* Fecha de actualización:	2013-04-10
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
* Creación del objeto para evitar el reenvio 
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
		 * obciones de guardado
		 */
		switch($_POST['hdd_save']){
			case 1:
				/**
				 * Inicio de transaccion
				 */
				$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
				$fecha = date("Y-m-d");
				$obBD_con1->operacionobBD(309, $Ses_Emp_Cod.'*'.$fecha.'*'.$des_plan, $obBD_conexion);
				/**
				 * Guardar auditoria
				 */
				//$obBD_con1->grabarAuditoria($_SERVER['PHP_SELF'], $Ses_Usu_Cod, $obBD_conexion);
				/**
				 * Fin de transacción
				*/
				$obBD_con1->fin_transaccion($obBD_conexion->conexion);
			break;
			case 2:
				$row_rs_vercodigo = $obBD_con1->getRowConsulta(1, $codpla.'*'.$cod_cuenta.'*'.$Ses_Emp_Cod, $obBD_conexion);
					
				if (count($row_rs_vercodigo) > 0)
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
					
					$obBD_con1->operacionobBD(322, $np.'*'.$codpla.'*'.$cod_cuenta.'*'.$des_cuenta.'*'.$tip_cuenta.'*'.$Pld_Deb.'*'.$Pld_Cre, $obBD_conexion);
					/**
					 * Guardar auditoria
					 */
					//$obBD_con1->grabarAuditoria($_SERVER['PHP_SELF'], $Ses_Usu_Cod, $obBD_conexion);
					/**
					 * Fin de transacción
					*/
					$obBD_con1->fin_transaccion($obBD_conexion->conexion);
				}
			break;
		}
	}
}

if (isset($ajax_codigo))
{
	$row_rs_vercodigo = $obBD_con1->getRowConsulta(1, $codpla.'*'.$cod_cuenta.'*'.$Ses_Emp_Cod, $obBD_conexion);

	if (count($row_rs_vercodigo) > 0)
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
    <td height="10">&raquo; Registrar Plan de Cuentas</td>
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
				<table  width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
					<thead>
      					<tr>
        					<th width="8%"><strong>C&oacute;d. Int.</strong></th>
        					<th width="88%"><strong>Descripci&oacute;n</strong></th>
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
						        	<?php 
						        		if($row['Pla_Est'] == 'Inactivo'){
						        			?>
								        	<img src="../../mascaras/model1/imagenes/32x32/encrypted.png" width="25" height="25" title="La cuenta esta inactiva">
								        	<?php
						        		}else{
						        			?>
						        			<form name='form1' method='post'  action='<?php echo $_SERVER['PHP_SELF'];?>'>
										        <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
										        	<i class=" icon-arrow-right icon-white"></i>
										        </button>
										        <input type="hidden" id="codpla" name="codpla" value="<?php echo $row['Pla_Cod']; ?>" />
										        <input type="hidden" id="pag" name="pag" value="1" />
										        <input type="hidden" id="np" name="np" value="0" />
										    </form> 
						        			<?php
						        		}
						        	?>
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
					  	  		<td><?Php echo error_alerta("¡No hay resultados que mostrar!", 1) ?></td>
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
  			?>
  			<table>
  				<tr>
  					<td width="110">
  					<form action="#" name="form" id="form">
  						<button type="button" class="btn btn-success fileinput-button" name="button2" id="button2" title="Nuevo Plan de Cuentas"><i class="icon-file icon-white"></i><span>&nbsp;&nbsp;Nuevo&nbsp;&nbsp;</span></button>
  					</form>
  					</td>
  				</tr>
  			</table>
  			<?php
  			require_once('../../componentes/FRONT/com_con_leyenda.php');
  			?>
  			<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
  			<div id="bgmodal"  class="bgmodal" style="display:none" >
 				<div id="ajax_modal">
 				<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form2" id="form2">
 				<FIELDSET>
					<LEGEND>
						<label class="Titulos2">Nuevo Plan de Cuentas</label>
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
					  <?php if ($np == 0) {
							echo "INICIO del Plan de Cuentas";
							$separador='';
						}else{
							$row_rs_direc = $obBD_con1->getRowConsulta(305, $np, $obBD_conexion);
							echo $row_rs_direc['Pld_Cdc']."  ".$row_rs_direc['Pld_Des'];
							$CodPadre = $row_rs_direc['Pld_Cdc'];
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
						<th width="28%" class="Cabecera"><strong>Cuenta</strong></th>
						<th width="10%" class="Cabecera"><strong>Tipo</strong></th>
						<th width="10%" class="Cabecera"><strong>Estado</strong></th>
						<th width="13%" class="Cabecera">A. P. D&eacute;bito</th>
						<th width="14%" class="Cabecera">A. P. Cr&eacute;dito</th>
						<th width="8%" class="Cabecera">&nbsp;</th>
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
							<td><font color="<?php echo $color_d; ?>">        
					        <?php echo $row['Pld_Des']; ?>        
					        </font>
					        </td>
							<td align="center"><font color="<?php echo $color_d; ?>"><?php echo $row['Pld_Tip']; ?></font></td>
							<td align="center"><font color="<?php echo $color_d; ?>"><?php echo $row['Pld_Est']; ?></font></td>
							<td align="center"><font color="<?php echo $color_d; ?>"><?php echo $row['Pld_Deb']; ?></font></td>
							<td align="center"><font color="<?php echo $color_d; ?>"><?php echo $row['Pld_Cre']; ?></font></td>
							<td align="center">
					        <?Php
					        if($row['Pld_Est'] == 'Inactivo'){
					        	?>
					        	<img src="../../mascaras/model1/imagenes/32x32/encrypted.png" width="25" height="25" title="La cuenta esta inactiva">
					        	<?php
					        }else{
					        	if ($row['Pld_Tip']=='GRUPO'){
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
				  		 * Link para volver atrás
				  		 */
				  		$row_rs_direca = $obBD_con1->getRowConsulta(306, $np, $obBD_conexion);
			  		?>
			  		<td width="110">
				  		<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form3">
							<button type="button" class="btn btn-inverse fileinput-button" title="Atrás" onClick="this.form.submit()"><i class=" icon-arrow-left icon-white"></i><span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span></button>
	       					<input type="hidden" id="pag" name="pag" value="1" />
	       					<input type="hidden" id="codpla" name="codpla" value="<?php echo $codpla; ?>" />
	       					<input type="hidden" id="np" name="np" value="<?php echo $row_rs_direca['Pld_Rec']; ?>" />
	       				</form>
	       			</td>
	       			<?php }?>
			  		<td width="110">
	  					<form action="#" name="form" id="form">
	  						<button type="button" class="btn btn-success fileinput-button" name="button2" id="button2" title="Nueva Cuenta"><i class="icon-file icon-white"></i><span>&nbsp;&nbsp;Nuevo&nbsp;&nbsp;</span></button>
	  					</form>
  					</td>
			  	</tr>
			  	</table>
			 <?php
			require_once('../../componentes/FRONT/com_con_leyenda.php');
			?>
			<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
  			<div id="bgmodal"  class="bgmodal" style="display:none" >
 				<div id="ajax_modal">
 				<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form3">
				    <?php 
				    	mensaje_requerido();
						$thisPost->startPost();
					?>		  
					<FIELDSET id="fagregar">
					<LEGEND>
						<label class="Titulos2">
						  Agregar Cuenta</label>
					</LEGEND>
						<table width="574" border="0" cellpadding="0" cellspacing="0">
					      <tr>
					        <td width="58" class="Etiqueta1"><span class="Asterisco">*</span> C&oacute;digo:</td>
					        <td colspan="2" class="LetraNegra">
				            	<?php 								
				            	if($np == 0){
									
									$num_cuenta = $num_cuenta + 1;				            											
				            	}else{
				            		//$num_cuenta = (substr($num_cuenta, $ini ,strlen($num_cuenta)) + 1);
									//$num_cuenta = $CodPadre."1";
					            	$ini = strripos($num_cuenta,'.');
									$num_cuenta = $row_rs_direc['Pld_Cdc'].$separador.(substr($num_cuenta, $ini + 1 ,strlen($num_cuenta)) + 1);
				            	}
				            	
				            	?>
					          <input name="cod_cuenta" type="text" id="cod_cuenta" value="<?php echo $num_cuenta; ?>" onBlur="validar_cuentas(this.form, this); 
				              ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_codigo&codpla=<?Php echo $codpla; ?>&cod_cuenta='+this.value, 'div_existe')">
				              <div id="div_existe"></div></td>
					        </tr>
					      <tr>
					        <td class="Etiqueta1"><span class="Asterisco">*</span> Cuenta:</td>
					        <td width="316" class="LetraNegra"><textarea rows="3" cols="40" name="des_cuenta" id="des_cuenta" ></textarea></td>
					        <td width="200" class="LetraNegra">&nbsp;</td>
				          </tr>
					      <tr>
					        <td class="Etiqueta1">Tipo:</td>
					        <td class="LetraNegra"><select name="tip_cuenta" id="tip_cuenta" onchange="bloqueo_asociacion()">
				              <option value="G">Grupo</option>
				              <option value="D">Detalle</option>
				            </select></td>
					        <td class="LetraNegra">&nbsp;</td>
				          </tr>
					      <tr>
					        <td class="LetraNegra">&nbsp;</td>
					        <td class="LetraNegra"><input name="codpla" type="hidden" id="codpla" value="<?php echo $codpla; ?>">
					          <input name="np" type="hidden" id="np" value="<?php echo $np; ?>">
   					          <input name="np2" type="hidden" id="np2" value="<?php echo $np; ?>">
					          <input name="ncuenta" type="hidden" id="ncuenta" value="ncuenta">
					         </td>
					        <td class="LetraNegra">&nbsp;</td>
					        </tr>
				            </table>
				        </FIELDSET>
				        <FIELDSET>
				        <LEGEND>
				            <label class="Titulos2">
				              Asociación Presupuestaria</label>
				        </LEGEND>            
						<table width="574" border="0" cellpadding="0" cellspacing="0">            
					      <tr>
					        <td width="64" class="Etiqueta1">D&eacute;bitos:</td>
					        <td width="318" class="LetraNegra"><input name="Pld_Deb" type="text" disabled="disabled" id="Pld_Deb" /></td>
					        <td width="192" class="LetraNegra">&nbsp;</td>
					        </tr>
					      <tr>
					        <td class="Etiqueta1">Cr&eacute;ditos:</td>
					        <td class="LetraNegra"><input name="Pld_Cre" type="text" disabled="disabled" id="Pld_Cre" /></td>
					        <td class="LetraNegra">&nbsp;</td>
					        </tr>
				        </table>
						</FIELDSET>
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