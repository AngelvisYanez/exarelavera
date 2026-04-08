<?php
/**
 * Permite visualizar los usuarios para impresion
 *
 * @author car.87cod :)
 * @version 1.0
 * Fecha de actualización:	2012-04-25
 * @author lewis.chimarro
 * @version 1.0
 * Fecha de actualización:	2014-05-29
 *
 * @package administrador.FRONT
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cliente.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * objeto para la conexion
 * @var Class_Log_Conexion_Adm
 */
$obBD_conexion = new Class_Log_Conexion_Cli($Ses_Dat_Dis);

/**
 * objeto para consultas
 * @var Class_Log_Datos_Adm
 */
$obBD_con1 =  new Class_Log_Datos_Cli;

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
    <td height="249" valign="top">
	<table width="80%" border="0" align="center" cellpadding="0" cellspacing="0">
      <tr align="center">
        <td colspan="4">&nbsp;<?php $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, "ANTICIPOS A PROVEEDORES", " ", $obBD_conexion); ?>&nbsp;</td>
        </tr>
	 </table>
 	<table width="100%" border="1" cellpadding="0" cellspacing="0">
      <tr>
        <td width="8%" class="TITULO_REPORTE">No Compr.</td>
        <td width="8%" align="center" class="TITULO_REPORTE">Fecha</td>
        <td width="13%" class="TITULO_REPORTE">Cédula/R.U.C.</td>
        <td width="41%" class="TITULO_REPORTE">Proveedor </td>
        <td width="16%" align="right" class="TITULO_REPORTE">Valor Inicial</td>
        <td width="14%" align="right" class="TITULO_REPORTE">Saldo</td>
      </tr>
<?php
	
	$Arr_Cliente = $obBD_con1->getArrayConsulta(9,$Ses_Emp_Cod.'*'.'', $obBD_conexion);
	
	foreach($Arr_Cliente as $row)
	{
?>
	<tr>
	  <td class="Texto_Reporte" align="center"><?Php echo $row['Prs_Cod']; ?></td>
	  <td class="Texto_Reporte">&nbsp;</td>
	  <td class="Texto_Reporte"><?Php echo $row['Prs_Ced']; ?></td>
	  <td class="Texto_Reporte"><?Php echo $row['Prs_Ape']." ".$row['Prs_Nom'];?></td>
	  <td align="right" class="Texto_Reporte"><?Php echo $row['Prs_Ape']." ".$row['Prs_Nom'];?></td>
	  <td align="right" class="Texto_Reporte"><?Php echo $row['Prs_Ape']." ".$row['Prs_Nom'];?></td>
	  </tr>
	<tr>
		<td height="84" colspan="6" align="left" valign="top" class="Texto_Reporte">
        <table width="85%" border="0" cellpadding="0" cellspacing="0">
        	<tr>
        	  <td width="105">No Compr.</td>
        	  <td width="142" align="center">Fecha</td>
        	  <td width="154">Facturas</td>
        	  <td width="491">Obsercacion</td>
        	  <td width="122" align="right">Valor</td>
      	    </tr>
        	<tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            </tr>
        </table>
        </td>
		</tr>
<?php
	}
?>
</table></td>
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
/**
* Cierra las conexiones
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>