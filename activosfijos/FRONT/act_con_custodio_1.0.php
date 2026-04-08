<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?php 
/**
 * Permite consultar los custodios registrados
 *
 * @author : Didimo Zamora
 * @version 1.0
 * @author Didimo Zamora
 * @version 1.0
 * @Fecha de actualización:	26-04-2013 
 *
 * @package activosfijos.FRONT
 */
		
require_once '../../administrador/LOGICA/seguridad.php';
require_once('../LOGICA/act_log_custodio.php');  	  
require_once '../../Librerias/procedimientos/almacenados_standar.php';	  
	
/**
 * Creacion del Objeto de conexion
 */ 
$obBD_conexion = new Class_Log_Conexion_Cch;
	
/**
 * Cracion del objeto mysql para las consultas
 */ 
$obBD_con1 =  new Class_Log_Datos_Cch;
?>
<html>
<head>
 <title><?Php echo $Ses_Sys_Nom; ?></title>
 <?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>    
 <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
 <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
 <script type="text/javascript">$(function() { $('#set1 *').tooltip({showURL: false}); });</script>
 <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<body>
<div id='set1'>
	<table width="100%">
  		<tr class="BarraTitulo">
    		<td height="10">&raquo; consultar custodio</td>
  		</tr>
  	</table>
  	<?php 
  	if(!isset($op)){
  		$op = 1;
  	}
  	$pag1= $_SERVER['PHP_SELF']."?op=1";
  	$pag2= $_SERVER['PHP_SELF']."?op=2";
  	tabs(2,'Individual*Todos', $pag1.'*'.$pag2,$op);
  	?>
  	<table width="100%">
	  	<tr>
		  	<td>
			  	<div id="ContTabul">
			  	<?php 
			  	switch($op){
			  		case 1:
			  			?>
			  			<form name="Buscador" method="post" action="<?Php $_SERVER['PHP_SELF']?>">
							<?Php require_once("../../componentes/FRONT/com_con_persona.php"); ?>
							<input type="hidden" name="op" value="1">
						</form>
						<?php 
						if(isset($_POST['txt_busqueda'])){
							/**
							 * Resultados de la busqueda del personal
							 */
							$Arr_Busqueda = $obBD_con1->getArrayConsulta($_POST['op_opciones'] == 'd' ? 5 : 6 , $Ses_Emp_Cod.'*'.$_POST['txt_busqueda'], $obBD_conexion);
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
									  </tr>
									</thead> 
									<tbody>
									<?php foreach($Arr_Busqueda as $row){
										if($row['Cus_Est'] == 'I'){	
											$rojo = 'style="color: red;"';
											if(!isset($com_leyenda[1]))$com_leyenda[1]=1;
										}else{
											$rojo='';
										}
										?>
									   <tr>
									     <td align="center" <?php echo $rojo;?>><?php echo $row['Cus_Cod'];?></td>
										 <td align="left" <?php echo $rojo;?>><?php echo $row['Prs_Ced']?></td>
										 <td align="left" <?php echo $rojo;?>><?Php echo marcar_cadena($_POST['txt_busqueda'], $row['Prs_Ape']." ".$row['Prs_Nom'],'#FFFF00', 1);?></td>
										 <td align="left" <?php echo $rojo;?>><?php echo $row['Tic_Des']?></td>
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
						require_once('../../componentes/FRONT/com_con_leyenda.php');?>
			  			<?php
			  			break;
					case 2:
						/**
						 * Resultados de la busqueda del personal
						 */
						$Arr_Busqueda = $obBD_con1->getArrayConsulta(5, $Ses_Emp_Cod.'*', $obBD_conexion);
						?>
						<FIELDSET>
							<LEGEND>
								<label class="Titulos2">Resultados de la busqueda</label>
							</LEGEND>
							<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader03">
								<thead>
									<tr>
										<th width="5%" >Cód. Int. </th>
										<th width="10%" >Cédula</th>
										<th >Personal </th>
										<th >Cargo </th>
									</tr>
								</thead> 
								<tbody>
								<?php 
									foreach($Arr_Busqueda as $row){
										if($row['Cus_Est'] == 'I'){	
												$rojo = 'style="color: red;"';
												if(!isset($com_leyenda[1]))$com_leyenda[1]=1;
										}else{
												$rojo='';
										}
								?>
										<tr>
										    <td align="center" <?php echo $rojo;?>><?php echo $row['Cus_Cod'];?></td>
										 	<td align="left" <?php echo $rojo;?>><?php echo $row['Prs_Ced']?></td>
											<td align="left" <?php echo $rojo;?>><?Php echo $row['Prs_Ape']." ".$row['Prs_Nom'];?></td>
											<td align="left" <?php echo $rojo;?>><?php echo $row['Tic_Des']?></td>
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
						?>
						<table>
							<tr>
								<td width="110">
									<form action="act_pri_custodio_1.0.php" method="post" target="_blank" name="form2" id="form2">
								  		<button type="button" class="btn btn-primary start" title="Imprmir" onclick="this.form.submit()">
								           <i class=" icon-print icon-white"></i>
								           <span>Imprimir</span>
										</button>
								     </form>
								</td>
							</tr>
						</table>
						<br>
						<?php
						require_once('../../componentes/FRONT/com_con_leyenda.php');
						break;
			  	}
			  	?>
			  	</div>
		  	</td>
	  	</tr>
  	</table>
	</div>
	<script type="text/javascript" src="../VALIDACIONES/act_par_custodio.js"></script>
	<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
</div>
</body>
</html>
<?php 
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>