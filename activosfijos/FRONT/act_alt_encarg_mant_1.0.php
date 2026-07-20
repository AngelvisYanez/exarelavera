<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?php 
/**
 * Permite registrar al custodio-> para activos fijos.
 *
 * @author : Didimo Zamora  :)
 * @version 1.0
 * @Fecha de actualizaci�n:	25-04-2013
 *
 * @package cajachica.FRONT
 */
	
require_once '../../administrador/LOGICA/seguridad.php';
require_once('../LOGICA/act_log_mantenimie.php');  	  
require_once '../../Librerias/procedimientos/almacenados_standar.php';	
require_once '../../Librerias/postclass.php';	
	
/**
 * Creacion del Objeto de conexion
 */ 
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Sis);
	
/**
 * Cracion del objeto mysql para las consultas
 */ 
$obBD_con1 =  new Class_Log_Datos_Con;
	
/**
 * Llamado de la libreria para evitar el reenvio de datos
 * @var Post_Block
 */
$thisPost = new Post_Block;
	
if (isset($_POST['hdd_save']))
{
	if ($thisPost->postBlock($_POST['postID']))
	{
		/**
		 * Iniciar transacci�n
		 */
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
		$obBD_con1->operacionobBD(477, $_POST['conCodigo'].'*'. $_POST['observacion'].'*'. $_POST['especialidad'], $obBD_conexion);
		
		//$obBD_con1->grabarAuditoria($_SERVER['PHP_SELF'], $Ses_Usu_Cod, $obBD_conexion);
		/**
		 * fin de transaccion
		 */
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);
	}
}
?>
<html>
<head>
 <title><?Php echo $Ses_Sys_Nom; ?></title>
 <?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>    
 <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
 <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
 <script type="text/javascript">$(function() { $('#set1 *').tooltip({showURL: false}); });</script>
 <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>
 <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<body>
<div id='set1'>
	<table width="100%">
  		<tr class="BarraTitulo">
    		<td height="10">&raquo; registrar encargado de mantenimiento de activo fijo</td>
  		</tr>
  	</table>
  	<form name="Buscador" method="post" action="<?Php $_SERVER['PHP_SELF']?>">
		<?Php require_once("../../componentes/FRONT/com_con_persona.php"); ?>
	</form>
	<?php 
	if(isset($_POST['txt_busqueda'])){
		/**
		 * Resultados de la busqueda del personal
		 */
		$Arr_Busqueda = $obBD_con1->getArrayConsulta($_POST['op_opciones'] == 'd' ? 476 : 475 , $_POST['txt_busqueda'], $obBD_conexion);
		?>
		<FIELDSET>
			<LEGEND>
			  	<label class="Titulos2">Resultados de la busqueda</label>
			</LEGEND>
			<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
			   <thead>
			      <tr>
					<th width="6%" >C�d. Int. </th>
					<th width="12%" >C�dula</th>
					<th width="79%" >Persona</th>
					<th width="3%" >&nbsp;</th>
				  </tr>
				</thead> 
				<tbody>
				<?php foreach($Arr_Busqueda as $row){?>
				   <tr>
				     <td align="center"><?php echo $row['Prs_Cod'];?></td>
					 <td align="center"><?php echo $row['Prs_Ced']?></td>
					 <td align="left"><?Php echo marcar_cadena($_POST['txt_busqueda'],$row['Nombre'],'#FFFF00', 1);?></td>
					 <td align="center">
					 <?php 
					 		/**
							 * Consultar si  ya est� registrado  este codigo de persona.
					 		 */
					 		$aut = $obBD_con1->getRowConsulta(478, $row['Prs_Cod'], $obBD_conexion);
					 		if($aut['count'] == 0){
							?>
								<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
							 		<button  type='button' class='btn btn-success btn-mini' title="Seleccionar como encargado de mantenimiento" 
							 				onclick="Muestra_Aparecer();$('#conCodigo').val('<?php echo $row['Prs_Cod'];?>');$('#nombre').val('<?php echo $row['Nombre'];?>');"><i class='icon-ok icon-white'></i></button>
								</form>
							<?php 			
					 		}else{
					 		?>
					 			<img src="../../mascaras/model1/imagenes/32x32/encrypted.png" width="25" height="25" title="Ya es un encargado de mantenimiento.!!">
					 		<?php	
					 		}
					 ?>
					 </td>
				   </tr>
				 <?php }
				 if(count($Arr_Busqueda)==0){
				 	?>
				 	<tr>
					 	<td>&nbsp;</td>
						<td>&nbsp;</td>
	  	  				<td align="center"><?Php echo error_alerta("No hay resultados que mostrar", 1) ?></td>
						<td>&nbsp;</td>
					<tr>
				 	<?php
				 }?>
				 </tbody>
			 </table>
		 </FIELDSET>
			<?php
	echo barra_estado(count($Arr_Busqueda));
	}
	?>
	<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
  	<div id="bgmodal"  class="bgmodal" style="display:none" >
 		<div id="ajax_modal">
    		<?php mensaje_requerido(); ?>
    		<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
    		<?php $thisPost->startPost(); ?>
    		<FIELDSET>
			    <LEGEND>
			    	<label class="Titulos2">Datos del encargado de mantenimiento de activo fijo:</label>
			    </LEGEND>
	    		 <table width="100%" cellpadding="0" cellspacing="0">
				    <tr>
				    	<td width="10%" class="Etiqueta1"><span class="Asterisco">*</span>&nbsp;C�digo:</td>
				    	<td class="LetraNegra">&nbsp;<input type="text" name="conCodigo" id="conCodigo" value="" size="6" readonly="readonly" style="border: none;"></td>
				    </tr>
				    <tr>
				    	<td class="Etiqueta1"><span class="Asterisco">*</span>&nbsp;Persona:</td>
				    	<td class="LetraNegra">&nbsp;<input type="text" name="nombre" id="nombre" value="" size="50" readonly="readonly" style="border: none;"></td>
				    </tr>
                     <tr>
				    	<td class="Etiqueta1"><span class="Asterisco">*</span>&nbsp;Observaci�n:</td>
				    	<td class="LetraNegra">&nbsp;<input type="text" name="observacion" id="observacion" value="" size="50"  ></td>
				    </tr>
                    
                     <tr>
				    	<td class="Etiqueta1"><span class="Asterisco">*</span>&nbsp;Especialidad:</td>
				    	<td class="LetraNegra">&nbsp;<input type="text" name="especialidad" id="especialidad" value="" size="50" ></td>
				    </tr>
				</table>
		   </FIELDSET>
		   <br>
		   <table cellpadding="0" cellspacing="0">
		   	<tr>
		   		<td width="110">
		   			<button type="button" class="btn btn-primary fileinput-button" onclick="validar_requeridos(this.form,'conCodigo*nombre*observacion*especialidad',1);"><i class=" icon-book icon-white"></i><span>&nbsp;&nbsp;Guardar&nbsp;&nbsp;</span></button>
		   			<input type="hidden" name="hdd_save" value="1">
		   			<input type="hidden" name="op_opciones" value="<?php echo htmlspecialchars($_POST['op_opciones'], ENT_QUOTES, 'UTF-8');?>">
					<input type="hidden" name="txt_busqueda" value="<?php echo htmlspecialchars($_POST['txt_busqueda'], ENT_QUOTES, 'UTF-8');?>">
		   		</td>
		   	</tr>
		   </table>
		   </form>
    	</div>
	</div>
	<script type="text/javascript" src="../VALIDACIONES/act_par_mantenimie.js"></script>
	<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
</div>
</body>
</html>
<?php 
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>