<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?php 
/**
 * Permite imprimir reporte de los autorizadores
 *
 * @author car.87cod  :)
 * @version 1.0
 * @Fecha de actualización:	23-04-2013
 * @author Didimo Zamora
 * @version 1.0
 * @Fecha de actualización:	26-04-2013 
 *
 * @package activosfijos.FRONT
 */
require_once '../../administrador/LOGICA/seguridad.php';
require_once('../LOGICA/cch_log_receptor.php');  	  
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
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/print.php"); ?> 
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>   
</head>
<body>
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td valign="top">
	<table width="80%" border="0" align="center" cellpadding="0" cellspacing="0">
      <tr align="center">
        <td colspan="4">
        <?php
        	/**
        	 * Consulta de la cabecera del reporte 
        	 */
			$row_institucion = $obBD_con1->getRowConsulta(22, $Ses_Suc_Cod, $obBD_conexion);
			
			/**
			 * Consulta la provicia y pais de la sucursal 
			 */
			$row_provincia = $obBD_con1->getRowConsulta(21, $row_institucion['Ciu_Cod'], $obBD_conexion);
		?>
			<table width="80%" border="0" cellpadding="0" cellspacing="0">
			  <tr align="center">
			    <td width="5%" rowspan="5" valign="top"><img src="<?php echo $row_institucion['Emp_Log']; ?>" width="83" height="67" /></td>
			    <td width="75%" class="TITULO_REPORTE_2"><?Php echo $row_institucion['Emp_Nom']; ?></td>
			  </tr>
			  <tr align="center">
			    <td valign="top" class="Texto_Reporte"><div align="center"><strong>R.U.C.:</strong> &nbsp;<?php echo $row_institucion['Emp_Ruc']; ?>&nbsp;		      <strong>TELEFONO:</strong>&nbsp;<?php echo $row_institucion['Suc_Te1']; ?></div></td>
		      </tr>
			  <tr align="center">
			    <td valign="top" class="Texto_Reporte"><div align="center"><strong>DIRECCION:</strong>&nbsp;<?php echo $row_institucion['Suc_Dir']; ?></div></td>
		      </tr>
			  <tr align="center">
			    <td valign="top" class="Texto_Reporte"><div align="center"><strong>E-MAIL:</strong> &nbsp;<?php echo $row_institucion['Suc_Cor']; ?></div></td>
		      </tr>
			  <tr align="center">
			    <td align="center" valign="top" class="Texto_Reporte"><div align="center"><?Php 
				if (count($row_provincia) > 0)
				{
					$provincia = " - ".$row_provincia['Pro_Nom'].' - '.$row_provincia['Pas_Nom'];
				}
				else
				{
					$provincia = "";					
				}
				echo $row_institucion['Ciu_Des'].$provincia;?></div></td>
	  		  </tr>
			  <tr align="center">
			    <td colspan="2" valign="top"><hr /></td>
	  		  </tr>
			  <tr align="center">
			    <td colspan="2" valign="top" class="TITULO_REPORTE">LISTADO DEL PERSONAL CUSTODIO</td>
	  		  </tr>
			  <tr align="center">
			    <td colspan="2" valign="top" class="TITULO_REPORTE"></td>
		      </tr>
		    </table>
        </td>
        </tr>
	 </table>
 	<table width="100%" border="1" cellpadding="0" cellspacing="0">
      <tr>
      	<td width="5%" class="TITULO_REPORTE">Cód. Int. </td>
		<td width="10%" class="TITULO_REPORTE">Cédula</td>
		<td width="40%" class="TITULO_REPORTE">Personal </td>
		<td width="35%" class="TITULO_REPORTE">Cargo </td>
		<td width="10%" class="TITULO_REPORTE">Estado</td>
      </tr>
	  <?php 
	  /**
	   * Resultados de la busqueda de planificadores
	   */
	  $Arr_Busqueda = $obBD_con1->getArrayConsulta(5, $Ses_Emp_Cod.'*', $obBD_conexion);
	  foreach($Arr_Busqueda as $row){
	  ?>
		<tr>
			<td class="Texto_Reporte">&nbsp;<?php echo $row['Cus_Cod'];?></td>
			<td class="Texto_Reporte">&nbsp;<?php echo $row['Prs_Ced']?></td>
			<td class="Texto_Reporte">&nbsp;<?Php echo $row['Prs_Ape']." ".$row['Prs_Nom'];?></td>
			<td class="Texto_Reporte">&nbsp;<?php echo $row['Tic_Des']?></td>
			<td class="Texto_Reporte">&nbsp;<?php echo $row['Cus_Est'] == "A"? "ACTIVO" : "INACTIVO";?></td>
		</tr>
	 <?php }
	?>
</table>
	</td>
  </tr>
  <tr valign="top">
    <td valign="top"><table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" class="Texto_Reporte">
      <tr>
        <td>&nbsp;</td>
        <td colspan="2">&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td colspan="4"><?Php
        	
			/**
			 * Consulta los datos del usuario 
			 */
			$row_usuario = $obBD_con1->getRowConsulta(23, $Ses_Usu_Cod, $obBD_conexion);
			$fecha=explode("-",date("Y-m-d"));	
	   	    $fechaHoy =	$row_institucion['Ciu_Des'].", ".$fecha[2]." de ".mes($fecha[1],1)." ".$fecha[0] ;	
				
		?>
			<table width="80%" border="0" cellpadding="0" cellspacing="0" align="center">
	   		  <tr align="center">
			    <td valign="top"><hr /></td>
	  		  </tr>
			  <tr align="center">
			    <td width="75%" valign="top" class="Texto_Reporte"><div align="center"><strong>FECHA IMPRESI&Oacute;N:</strong> &nbsp;<?php echo $fechaHoy; ?>&nbsp;<strong>USUARIO:</strong>&nbsp;<?php echo $row_usuario['Prs_Ape'].' '.$row_usuario['Prs_Nom'] ; ?></div></td>
		      </tr>
		    </table>
			</td>
      </tr>
    </table></td>
  </tr>
</table>
</body>
</html>
<?php 
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>