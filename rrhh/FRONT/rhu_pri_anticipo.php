<?php

/**
 * @abstract Reporte de ventas para la impresiï¿½n en factura o nota de venta
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualizaciï¿½n  2012-05-23
 * @author Lewis Chimarro
 */
require_once('../../Librerias/config.php/register_globals.php');
require_once($APP_REAL_PATH . '/administrador/LOGICA/logica.php');
require_once('../LOGICA/rhu_log_roles.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

if (!isset($Ant_Cod)) die();
/*
*  Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Rol($Ses_Dat_Dis);
/* 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Rol;



$row_info_Empresa = $obBD_con1->getSucursal($Ses_Suc_Cod, $obBD_conexion);
$rs_datos =  $obBD_con1->getArrayConsulta(42, $Ant_Cod, $obBD_conexion);
$rs_detail = $obBD_con1->getArrayConsulta(43, $Ant_Cod, $obBD_conexion);

if (empty($fecha_ant) || trim($fecha_ant) === "") {
  $fechas_rol = $rs_datos[0]['Ant_Fec']; 
} else {
  $fechas_rol =   $fecha_ant;
}

list($anio, $mes, $dia) = preg_split('![-]!', $fechas_rol);
//list($anio, $mes, $dia) = preg_split('![-]!', $rs_datos[0]['Ant_Fec']);


?>
<html>

<head>
  <title><?Php echo $Ses_Sys_Nom; ?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
  <style type="text/css">
    body {
      margin-left: 0px;
    }
  </style>
  <style type="text/css">
    <!--
    .style2 {
      color: #000099
    }

    .Estilo1 {
      font-size: 12px
    }
    -->
    .tituloFact {
      font:
    9pt
    Tahoma,
    Geneva,
    sans-serif;
    }
    .titulotabla {
      font:
    9pt
    Tahoma,
    Geneva,
    sans-serif;
    }
    .tituloFact2 {
      font:
    9pt
    Tahoma,
    Geneva,
    sans-serif;
    }
    .etiquetaFact {
      font:
    9pt
    Tahoma,
    Geneva,
    sans-serif;
    }
    .subtitulo {
      font:
    10pt
    Tahoma,
    Geneva,
    sans-serif;
    }
  </style>
</head>

<body>
  <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
    <tr>
      <td align="left" valign="top">
        <table width="100%" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td colspan="6" align="center" class="LetraPlan">
              <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="13%" align="left"><img src="<? echo $Ses_Emp_Log; ?>" width="90" height="80"></td>
                  <td width="87%">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td align="center" class="tituloFact"><strong><? echo $Ses_Emp_Nom; ?></strong></td>
                      </tr>
                      <tr>
                        <td align="center" class="tituloFact2"><? echo "Tel&eacute;fono 1:" . $row_info_Empresa['Suc_Te1'] . "&nbsp;&nbsp;&nbsp;" . "Tel&eacute;fono 2:" . $row_info_Empresa['Suc_Te2'] ?></td>
                      </tr>
                      <tr>
                        <td align="center" class="tituloFact2"><? echo $row_info_Empresa['Suc_Dir']; ?></td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td colspan="6" align="left">
              <hr>
            </td>
          </tr>
          <tr>
            <td height="24" colspan="6" align="center" class="subtitulo">
              <strong>
                <?php switch ($rs_datos[0]['Ant_Tip']) {
                  case 'A':
                    echo 'ANTICIPOS ROL DE PAGOS';
                    break;
                  case 'B':
                    echo 'PAGO/ABONO ROL DE PAGOS';
                    break;
                  case 'D':
                    echo 'DESCUENTOS ROL DE PAGOS';
                    break;
                } ?>
              </strong>
            </td>
          </tr>
          <tr>
            <td width="10%" align="left" class="etiquetaFact"><strong>R.U.C / C.I:</strong></td>
            <td width="25%" class="etiquetaFact"><? echo $rs_datos[0]['Prs_Ced'] ?>&nbsp;</td>
            <td width="10%" align="left" class="etiquetaFact"><strong>EMISI&Oacute;N:</strong></td>
            <td width="16%" align="left" class="etiquetaFact"><? echo $rs_datos[0]['Ant_Fec']; ?>&nbsp;</td>
            <td width="14%" align="left" class="etiquetaFact"><strong>MES:</strong></td>
            <td width="25%" align="left" class="etiquetaFact"><?php echo mes($mes, 1); ?></td>
          </tr>
          <tr>
            <td align="left" class="etiquetaFact"><strong>PERSONAL:</strong></td>
            <td colspan="5" class="etiquetaFact"><? echo $rs_datos[0]['Personal']; ?>&nbsp;</td>
          </tr>
          <tr>
            <td align="left" class="etiquetaFact"><strong>OBSERVACI&oacute;N:</strong></td>
            <td colspan="5" align="left"><span class="etiquetaFact"><? echo $rs_datos[0]['Ant_Obs']; ?>&nbsp;</span></td>
          </tr>
          <tr>
            <td height="94" colspan="6" align="left" valign="top"><?Php
                                                                  $tarifa_0 = 0;
                                                                  $tarifa_12 = 0;
                                                                  ?>
              <br>
              <table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse">
                <tr class="tituloFact2">
                  <td width="216" height="19" align="left"><strong>COMP. VENTA</strong></td>
                  <td width="182" align="center"><strong>FECHA CHEQ.</strong></td>
                  <td width="197" align="center"><strong># CHEQUE</strong></td>
                  <td width="303" align="center"><strong>BANCO CHEQ.</strong></td>
                  <td width="118" align="right"><strong>VALOR</strong></td>
                </tr>
                <tr>
                  <td colspan="6">
                    <hr style="border: 0; border-top: 1px solid #999; border-bottom: 1px solid #333; height:0;">
                  </td>
                </tr>
                <? $total = 0;
                foreach ($rs_detail as $datos) { ?>
                  <tr class="tituloFact2">
                    <td align="left">&nbsp;<? echo $datos['Pag_Des']; ?></td>
                    <td align="center"><? if ($datos['Che_Fec'] != '') {
                                          echo $datos['Che_Fec'];
                                        } else {
                                          echo '-';
                                        } ?></td>
                    <td align="center"><? if ($datos['Che_Num'] != '') {
                                          echo $datos['Che_Num'];
                                        } else {
                                          echo '-';
                                        } ?></td>
                    <td align="center"><? if ($datos['Che_Num'] != '' && $datos['Pld_Des'] != '') {
                                          echo $datos['Pld_Des'];
                                        } else {
                                          echo '-';
                                        } ?></td>
                    <td align="right"><? echo formato_numero($datos['Ant_Val'], 2, 1);
                                      $total += $datos['Ant_Val']; ?></td>
                  </tr>
                <? } ?>
                <tr>
                  <td height="38">&nbsp;</td>
                  <td>&nbsp;</td>
                  <td>&nbsp;</td>
                  <td align="right" class="tituloFact2">&nbsp;</td>
                  <td>&nbsp;</td>
                </tr>
                <tr>
                  <td colspan="3">&nbsp;</td>
                  <td align="right" class="tituloFact2"><strong>TOTAL:</strong></td>
                  <td align="right" class="etiquetaFact"><strong><? echo formato_numero($total, 2, 1); ?></strong>&nbsp;</td>
                </tr>
                <tr>
                  <td colspan="6">
                    <hr style="border: 0; border-top: 1px solid #999; border-bottom: 1px solid #333; height:0;">
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td height="25" align="left" valign="top">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td height="39">&nbsp;</td>
            <td align="center" valign="bottom">__________________</td>
            <td valign="bottom">&nbsp;</td>
            <td align="center" valign="bottom">&nbsp;</td>
            <td align="center" valign="bottom">&nbsp;</td>
            <td align="center" valign="bottom">__________________</td>
            <td>&nbsp;</td>
          </tr>
          <tr class="tituloFact2">
            <td>&nbsp;</td>
            <td align="center">REALIZADO POR:</td>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
            <td align="center">RECIBIDO:</td>
            <td>&nbsp;</td>
          </tr>
          <tr class="tituloFact2">
            <td>&nbsp;</td>
            <td align="center"><? /*echo $rs_datos[0]['usuApe']//.' '.$rs_datos[0]['usuNom'];; */ ?></td>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
            <td align="center"><? echo $rs_datos[0]['Personal'] //.' '.$rs_datos[0]['usuNom'];;
                                ?></td>
            <td>&nbsp;</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
  </tr>
  </table>

</body>

</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>