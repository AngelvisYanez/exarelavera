<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?php 
/**
 * Permite registrar al custodio-> para activos fijos.
 *
 * @author : Didimo Zamora  :)
 * @version 1.0
 * @Fecha de actualización:	25-04-2013
 * @author Didimo Zamora
 * @version 1.0
 * @Fecha de actualización:	26-04-2013 
 *
 * @package activosfijos.FRONT
 */
	
require_once '../../administrador/LOGICA/seguridad.php';
require_once('../LOGICA/act_log_custodio.php');  	  
require_once '../../Librerias/procedimientos/almacenados_standar.php';	
require_once '../../Librerias/postclass.php';	  
	
/**
 * Creacion del Objeto de conexion
 */ 
$obBD_conexion = new Class_Log_Conexion_Cch;
	
/**
 * Cracion del objeto mysql para las consultas
 */ 
$obBD_con1 =  new Class_Log_Datos_Cch;
	
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
		 * Iniciar transacción
		 */
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
		
		$obBD_con1->operacionobBD(4, $_POST['conCodigo'], $obBD_conexion);
		
		$obBD_con1->grabarAuditoria($_SERVER['PHP_SELF'], $Ses_Usu_Cod, $obBD_conexion);
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
    		<td height="10">&raquo; registrar custodio</td>
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
		$Arr_Busqueda = $obBD_con1->getArrayConsulta($_POST['op_opciones'] == 'd' ? 1 : 2 , $Ses_Emp_Cod.'*'.$_POST['txt_busqueda'], $obBD_conexion);
		?>
		<FIELDSET>
			<LEGEND>
			  	<label class="Titulos2">Resultados de la busqueda</label>
			</LEGEND>
			<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
			   <thead>
			      <tr>
					<th width="5%" >Cód. Int. </th>
					<th width="10%" >Cédula</th>
					<th >Personal </th>
					<th >Cargo </th>
					<th width="3%" >&nbsp;</th>
				  </tr>
				</thead> 
				<tbody>
				<?php foreach($Arr_Busqueda as $row){?>
				   <tr>
				     <td align="center"><?php echo $row['Con_Cod'];?></td>
					 <td align="left"><?php echo $row['Prs_Ced']?></td>
					 <td align="left"><?Php echo marcar_cadena($_POST['txt_busqueda'], $row['Prs_Ape']." ".$row['Prs_Nom'],'#FFFF00', 1);?></td>
					 <td align="left"><?php echo $row['Tic_Des']?></td>
					 <td align="center">
					 <?php 
					 		/**
							 * Consultar si es un custodio
					 		 */
					 		$aut = $obBD_con1->getRowConsulta(3, $row['Con_Cod'], $obBD_conexion);
					 		if($aut['count'] == 0){
							?>
								<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
							 		<button type='button' class='btn btn-success btn-mini' title="Elegir" 
							 				onclick="Muestra_Aparecer();$('#conCodigo').val('<?php echo $row['Con_Cod'];?>');$('#cargo').val('<?php echo $row['Tic_Des'];?>');$('#nombre').val('<?php echo $row['Prs_Ape']." ".$row['Prs_Nom'];?>');"><i class='icon-arrow-right icon-white'></i></button>
								</form>
							<?php 			
					 		}else{
					 		?>
					 			<img src="../../mascaras/model1/imagenes/32x32/encrypted.png" width="25" height="25" title="Ya es un Custodio">
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
	  	  				<td><?Php echo error_alerta("No hay resultados que mostrar", 1) ?></td>
						<td>&nbsp;</td>
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
			    	<label class="Titulos2">Receptor a:</label>
			    </LEGEND>
	    		 <table width="100%" cellpadding="0" cellspacing="0">
				    <tr>
				    	<td width="10%" class="Etiqueta1"><span class="Asterisco">*</span>&nbsp;Código:</td>
				    	<td class="LetraNegra">&nbsp;<input type="text" name="conCodigo" id="conCodigo" value="" size="6" readonly="readonly" style="border: none;"></td>
				    </tr>
				    <tr>
				    	<td class="Etiqueta1"><span class="Asterisco">*</span>&nbsp;Cargo:</td>
				    	<td class="LetraNegra">&nbsp;<input type="text" name="cargo" id="cargo" value="" size="50" readonly="readonly" style="border: none;"></td>
				    </tr>
				    <tr>
				    	<td class="Etiqueta1"><span class="Asterisco">*</span>&nbsp;Personal:</td>
				    	<td class="LetraNegra">&nbsp;<input type="text" name="nombre" id="nombre" value="" size="50" readonly="readonly" style="border: none;"></td>
				    </tr>
				</table>
		   </FIELDSET>
		   <br>
		   <table cellpadding="0" cellspacing="0">
		   	<tr>
		   		<td width="110">
		   			<button type="button" class="btn btn-primary fileinput-button" onclick="validar_requeridos(this.form,'conCodigo',1);"><i class=" icon-book icon-white"></i><span>&nbsp;&nbsp;Guardar&nbsp;&nbsp;</span></button>
		   			<input type="hidden" name="hdd_save" value="1">
		   			<input type="hidden" name="op_opciones" value="<?php echo $_POST['op_opciones'];?>">
					<input type="hidden" name="txt_busqueda" value="<?php echo $_POST['txt_busqueda'];?>">
		   		</td>
		   	</tr>
		   </table>
		   </form>
    	</div>
	</div>
	<script type="text/javascript" src="../VALIDACIONES/act_par_custodio.js"></script>
	<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
</div>
</body>
</html>
<?php 
/**
* Cierra las conexiones
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>