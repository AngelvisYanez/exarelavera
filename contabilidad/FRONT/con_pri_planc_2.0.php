<?	
/** 
 * Descripción: Permite imprimir el plan de cuentas
 * Fecha de actualización:	2012-04-23
 * Desarrollador:	Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_planc_2.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	  

/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/**
 * Creacion del objeto mysql para las consultas
 */
$obBD_con1 =  new Class_Log_Datos_Con;
	  	  
/**
 * Cargado de la cabecera del Reporte del Plan de Cuenta
 */
$row_cabplan = $obBD_con1->getRowConsulta(2,$codigo,$obBD_conexion); 

?>	
<?php header('Content-Type: text/html; charset=ISO-8859-1'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">			
<html>
<head>
    <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <?Php require_once("../../mascaras/model1/estilos/print.php"); ?>    
</head>
<body>
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td valign="top">
	<? if(count($row_cabplan) > 0) { ?>
	<table width="80%" border="0" align="center" cellpadding="0" cellspacing="0">
      <tr align="center">
        <td colspan="4">&nbsp;<?php $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, "Plan de Cuentas", " ", $obBD_conexion); ?>&nbsp;</td>
        </tr>
	 </table>
 <table width="458" border="0" align="center" cellpadding="0" cellspacing="0">

      <tr align="center">
        <td width="81" valign="top" class="TITULO_REPORTE"><div align="left">Descripci&Oacute;N:</div></td>
        <td width="217" class="Texto_Reporte"><div align="left">&nbsp;<? echo $row_cabplan['Pla_Obs']; ?></div></td>
        <td width="55" valign="top" class="TITULO_REPORTE"><div align="right">ESTADO:</div></td>
        <td width="105" valign="top" class="Texto_Reporte"><div align="left">&nbsp;<? echo $row_cabplan['Pla_Est']; ?></div></td>
      </tr>
      </table>
	<? } ?>
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
        <td colspan="4">
        	<table  width="100%" border="1" style="border-collapse:collapse" cellpadding="0" cellspacing="0">
				<tr class="Texto_Listados">
					<td width="12%" align="center" bgcolor="#CCCCCC" ><strong>C&oacute;digo</strong></td>
				  <td width="88%" align="center" bgcolor="#CCCCCC" ><strong>Cuentas</strong></td>
			    </tr>
				<?php
				echo $obBD_con1->obtenerPlanCuentas($codigo, 0, $obBD_conexion);
				?>
			</table>
        </td>
        </tr>
      <tr class="Texto_normal_10">
        <td colspan="4" align="center"><?Php $obBD_con1->pieReporteStandar($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?></td>
      </tr>
    </table></td>
  </tr>
</table>
</body>
</html>
<?php
/**
* Cierra la conexion
*/
$obBD_conexion->cerrar();
?>