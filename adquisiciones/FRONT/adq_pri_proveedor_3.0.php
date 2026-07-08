<?php

/**
 * Permite visualizar los proveedores para impresion
 *
 * @author car.87cod :)
 * @version 2.0
 * Fecha de actualizaci�n:	2012-04-30
 *
 * @package administrador.FRONT
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/adq_log_proveedor.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * objeto para la conexion
 * @var Class_Log_Conexion_Tes
 */
$obBD_conexion = new Class_Log_Conexion_Prv($Ses_Dat_Dis);

/**
 * objeto para consultas
 * @var Class_Log_Datos_Tes
 */
$obBD_con1 =  new Class_Log_Datos_Prv;

?>
<!DOCTYPE unspecified PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
  <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
  <?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
</head>

<body>
  <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
    <tr>
      <td valign="top">
        <table width="80%" border="0" align="center" cellpadding="0" cellspacing="0">
          <tr align="center">
            <td colspan="4">&nbsp;<?php $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, "Listado de Proveedores", " ", $obBD_conexion); ?>&nbsp;</td>
          </tr>
        </table>
        <table width="100%" border="1" cellpadding="0" cellspacing="0" style="border-collapse:collapse">
          <tr class="Texto_Listados">
            <td bgcolor="#CCCCCC"><strong>RUC</strong></td>
            <td bgcolor="#CCCCCC"><strong>Proveedor</strong></td>
            <td bgcolor="#CCCCCC"><strong>Nombre comercial</strong></td>
            <td bgcolor="#CCCCCC"><strong>Ciudad</strong></td>
            <td bgcolor="#CCCCCC"><strong>Direcci&oacute;n</strong></td>
            <td bgcolor="#CCCCCC"><strong>Tel&eacute;fono 1</strong></td>
            <td bgcolor="#CCCCCC"><strong>Tel&eacute;fono 2</strong></td>
            <td bgcolor="#CCCCCC"><strong>Emial</strong></td>
          </tr>

          <?php
          $Arr_Proveedor = $obBD_con1->getArrayConsulta(9, '' . '*' . $Ses_Emp_Cod, $obBD_conexion);
          foreach ($Arr_Proveedor as $row) {
          ?>
            <tr class="Texto_Reporte">
              <td>&nbsp;<?Php echo $row['Prs_Ced']; ?></td>
              <td>&nbsp;<?Php echo $row['Prs_Ape'] . ' ' . $row['Prs_Nom']; ?></td>
              <td><?Php echo $row['Prv_Com']; ?></td>
              <td>&nbsp;<?Php echo $row['Ciu_Des']; ?></td>
              <td>&nbsp;<?Php echo $row['Prs_Dir']; ?></td>
              <td>&nbsp;<?Php echo $row['Prs_Tel']; ?></td>
              <td>&nbsp;<?Php echo $row['Prs_Te2']; ?></td>
              <td>&nbsp;<?Php echo $row['Prs_Cor']; ?></td>
            </tr>
          <?php
          }
          ?>
        </table>
      </td>
    </tr>
    <tr valign="top">
      <td valign="top">
        <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" class="Texto_Reporte">
          <tr>
            <td>&nbsp;</td>
            <td colspan="2">&nbsp;</td>
            <td>&nbsp;</td>
          </tr>
          <tr>
            <td colspan="4"><?Php $obBD_con1->pieReporteStandar($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>

</html>
<?php
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>