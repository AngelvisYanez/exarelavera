<?php
/**
 * Permite visualizar los usuarios para impresion
 *
 * @author car.87cod :)
 * @version 1.0
 * Fecha de actualización:	2012-04-25
 *
 * @package administrador.FRONT
 */

require_once('../LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_usuarios.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * objeto para la conexion
 * @var Class_Log_Conexion_Adm
 */
$obBD_conexion = new Class_Log_Conexion_Admu($Ses_Dat_Dis);

/**
 * objeto para consultas
 * @var Class_Log_Datos_Adm
 */
$obBD_con1 =  new Class_Log_Datos_Admu;

?>
<!DOCTYPE unspecified PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
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
        <td colspan="4">&nbsp;<?php $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, "Listado de Usuarios", " ", $obBD_conexion); ?>&nbsp;</td>
        </tr>
	 </table>
 	<table width="100%" border="1" cellpadding="0" cellspacing="0">
      <tr>
        <td width="5%" class="TITULO_REPORTE">Cod. Int. </td>
        <td width="8%" class="TITULO_REPORTE">Usuarios</td>
        <td width="81%" class="TITULO_REPORTE">Apellidos y Nombres </td>
      </tr>
<?php 

	/**
	 * personas encontradas
	 * @var array
	 */
	$Arr_Persona = $obBD_con1->getArrayConsulta(14,$Ses_Emp_Cod.'*'.'', $obBD_conexion);
	
	foreach($Arr_Persona as $row)
	{
?>
	<tr>
		<td class="Texto_Reporte" align="center"><?Php echo $row['Prs_Cod']; ?></td>
		<td class="Texto_Reporte">&nbsp;<?Php echo $row['Prs_Ced']; ?></td>
		<td class="Texto_Reporte">&nbsp;<?Php echo $row['Prs_Ape']." ".$row['Prs_Nom'];?></td>
	</tr>
<?php
	}
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
        <td colspan="4"><?Php $obBD_con1->pieReporteStandar($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?></td>
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