<?php
/*
* Descripci�n: Reporte agupado por tipoa de pago: Efectivo, Cheque, Tarjeta... etc
* Fecha de actualizaci�n: 2016-1028
* Desarrollador: Jose Cumbicos
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_aper_caja.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/*
*  Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/* 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Tes;
$optest = "A";
$row_FormasVentas = $obBD_con1->getArrayConsulta(30, $Caj_Cod, $obBD_conexion);
$total_ventas = count($row_FormasVentas);
?>
<HTML>

<HEAD>
  <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
  <?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
  <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
</HEAD>

<BODY>
  <table width="100%" border="0" align="center">
    <tr align="center">
      <td width="100%" valign="top" align="center">
        <?php
        if (($optest) == "A") {
          $estado = 'Activas';
        } else {
          $estado = 'Anuladas';
        } //Fin del if (($optest) == "A")
        $tip = $row_rs_cabcomp['Tia_Ini'];
        $num = $row_rs_cabcomp['Com_Num'];
        $titulo = "<strong><span class='TITULO_REPORTE_2'>Detalle de facturas de Venta</span></strong>";
        $subtitulo = "<strong><span class='TITULO_REPORTE'>Detallado por tipo de pago</span></strong>";
        $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, $titulo, $subtitulo, $obBD_conexion); ?></td>
    </tr>
    <tr>
      <td align="left"><br>
        <table width="647" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="132" align="left" class="Texto_Reporte"><strong>Fecha de Apertura:</strong></td>
            <td width="236" class="Texto_Reporte">&nbsp;<?Php echo $row_FormasVentas[0]['Caj_Fec'] . ' ' . $row_FormasVentas[0]['Caj_Hoi']; ?></td>
            <td width="139" class="Texto_Reporte"><strong>Caja:</strong></td>
            <td width="140" class="Texto_Reporte"><?php echo $row_FormasVentas[0]['Pun_Des']; ?></td>
          </tr>
          <tr>
            <td width="132" class="Texto_Reporte"><strong>Fecha de Cierre:</strong></td>
            <td width="236" class="Texto_Reporte">&nbsp;<?Php echo $row_FormasVentas[0]['Caj_Fef'] . ' ' . $row_FormasVentas[0]['Caj_Hof']; ?></td>
            <td width="139" class="Texto_Reporte"><strong>Estado de la Caja:</strong></td>
            <td width="140" class="Texto_Reporte">&nbsp;<?Php echo $row_FormasVentas[0]['Caj_Est']; ?></td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td valign="top"><?Php

                        $resultados_total = explode('*', $obBD_con1->calculosConsultaVentas($Caj_Cod, $optest, $obBD_conexion));


                        ?>
        <table width="100%" border="1" cellpadding="0" cellspacing="0" style="border-collapse:collapse">
          <tr class="TablaRepCompr">
            <td width="11%" align="left" bgcolor="#CCCCCC" class="TablaRepCompr">&nbsp;TIPO DE PAGO</td>
            <td width="25%" align="center" bgcolor="#CCCCCC" class="TablaRepCompr">CLIENTE</td>
            <td width="15%" align="center" bgcolor="#CCCCCC" class="TablaRepCompr">BCO-CTA-CHE.</td>
            <td width="14%" align="center" bgcolor="#CCCCCC" class="TablaRepCompr">N&Uacute;MERO DOCUMENTOS</td>
            <td width="6%" align="right" bgcolor="#CCCCCC" class="TablaRepCompr">SUB 0%</td>
            <td width="6%" align="right" bgcolor="#CCCCCC" class="TablaRepCompr">SUB TOTAL</td>
            <td width="6%" align="right" bgcolor="#CCCCCC" class="TablaRepCompr">DESCUENTO</td>
            <td width="6%" align="right" bgcolor="#CCCCCC" class="TablaRepCompr">IVA</td>
            <td width="6%" align="right" bgcolor="#CCCCCC" class="TablaRepCompr">TOTAL</td>
           
          </tr>
          <?php if ($total_ventas != 0) {
            $subtotalesEfectivo;
            $totalVentas = 0;
            foreach ($row_FormasVentas as $datos_forma) {
              $totalDetalle = 0;
              $row_ventas = $obBD_con1->getArrayConsulta(31, $Caj_Cod . '*' . $datos_forma['Pag_Cod'], $obBD_conexion);

          ?>
              <tr class="Texto_Reporte">

                <!--Etiquetas de contado y transferencia-->
                <td colspan="9" align="left">&nbsp;<strong><?php echo strtoupper($datos_forma['Pag_Des']); ?></strong></td>
              </tr>




              <?php foreach ($row_ventas as $datos) {  ?>
                <tr class="Texto_Reporte">
                  <td align="left">&nbsp; <?php echo "&nbsp;&nbsp;&nbsp;&nbsp;" . strtoupper($datos['Tic_Des']);  ?></td>
                  <td align="left"><?php echo strtoupper($datos['Prs_Ape'] . ' ' . $datos['Prs_Nom']);  ?></td>
                  <td align="center"><?php if ($datos['Bak_Des'] != 'Ninguno') {
                                        echo $datos['Bak_Des'] . ' - ' . $datos['Vet_Cue'] . ' - ' . $datos['Vet_Che'];
                                      } else {
                                        echo '-';
                                      } ?></td>

                  <td align="center"><?Php echo str_pad($datos['Vet_Num'], 9, '0', STR_PAD_LEFT); ?></td>
                  <td align="right"><?Php echo formato_numero($datos['Sub0'], 2, 2); ?></td>
                  <td align="right"><?Php echo formato_numero($datos['SubIva'], 2, 2); ?></td>
                  <td align="right"><?Php echo formato_numero($datos['Descuento'], 2, 2); ?></td>
                  <td align="right"><?Php echo formato_numero($datos['Iva'], 2, 2); ?></td>
                  <td align="right"><?Php echo formato_numero(($datos['Sub0'] + $datos['SubIva'] + $datos['Iva']) - $datos['Descuento'], 2, 2); ?></td>
                

                </tr>
              <?Php
                if ($datos_forma['Pag_Cod'] == 1) {
                  $subtotalesEfectivo['Sub0'] += $datos['Sub0'];
                  $subtotalesEfectivo['SubIva'] += $datos['SubIva'];
                  $subtotalesEfectivo['Descuento'] += $datos['Descuento'];
                  $subtotalesEfectivo['Iva'] += $datos['Iva'];
                  $subtotalesEfectivo['total'] += (($datos['Sub0'] + $datos['SubIva'] + $datos['Iva']) - $datos['Descuento']);
                }
               $totalDetalle += ($datos['Sub0'] + $datos['SubIva'] + $datos['Iva']) - $datos['Descuento'];
                //$totalDetalle += ($datos['total']);

                $totalVentas += (($datos['Sub0'] + $datos['SubIva'] + $datos['Iva']) - $datos['Descuento']);
              }
              ?>


              <!--Totales de efectivo y transferencia (forma de pago)-->
              <tr class="Texto_Reporte">
                <td colspan="8" align="right"><strong><?php echo "TOTAL " . strtoupper($datos_forma['Pag_Des']) ?></strong>&nbsp;</td>
                <td align="right"><strong><?php echo formato_numero($totalDetalle, 2, 2); ?></strong></td>
              </tr>








            <?php }
          } else {
            ?>
            <tr class="Texto_Reporte">
              <td align="left">-</td>
              <td align="center">&nbsp;</td>
              <td align="center">-</td>
              <td align="right">0.00</td>
              <td align="right">0.00</td>
              <td align="right">0.00</td>
              <td align="right">0.00</td>
              <td align="right">0.00</td>
            </tr>
          <?php } ?>



          <?php if ($total_ventas != 0) {
            $totalRetenciones = 0;
            $totalRetencionesEfectivo = 0;
            foreach ($row_FormasVentas as $datos_forma) {
              $totalDetalle = 0;
              $row_ventas = $obBD_con1->getArrayConsulta(32, $Caj_Cod . '*' . $datos_forma['Pag_Cod'], $obBD_conexion);
              $tipos = count($row_ventas);
              if ($tipos) {
          ?>
                <tr class="Texto_Reporte">
                  <td colspan="9" align="left">&nbsp;<strong><?php echo 'RETENCIONES - ' . strtoupper($datos_forma['Pag_Des']); ?></strong></td>
                </tr>



                <?php foreach ($row_ventas as $datos) {  ?>
                  <tr class="Texto_Reporte">
                    <td align="left">&nbsp; <?php echo "&nbsp;&nbsp;&nbsp;&nbsp; RETENCION" ?></td>
                    <td align="left"><?php echo strtoupper($datos['Prs_Ape'] . ' ' . $datos['Prs_Nom']);  ?></td>
                    <td align="center"><?Php echo '-' ?></td>
                    <td align="center"><?Php echo  str_pad($datos['Vet_Num'], 9, '0', STR_PAD_LEFT); ?></td>
                    <td align="right"><?Php echo '-' ?></td>
                    <td align="right"><?Php echo '-' ?></td>
                    <td align="right"><?Php echo '-' ?></td>
                    <td align="right"><?Php echo '-' ?></td>
                    <td align="right"><?Php echo formato_numero($datos['Total'], 2, 2) ?></td>
                  </tr>
                <?Php
                  if ($datos_forma['Pag_Cod'] == 1) {
                    $totalRetencionesEfectivo += $datos['Total'];
                  }

                  $totalDetalle += $datos['Total'];
                  $totalRetenciones += $datos['Total'];
                } ?>
                <tr class="Texto_Reporte">
                  <td colspan="8" align="right"><strong><?php echo "TOTAL RETENCIONES " . strtoupper($datos_forma['Pag_Des']) ?></strong>&nbsp;</td>
                  <td align="right"><strong><?php echo formato_numero($totalDetalle, 2, 2); ?></strong></td>
                </tr>
          <?php
              }
            }
          }
          ?>
        </table>

        <br>
        <table width="100%" border="0" cellpadding="0" cellspacing="0">
          <tr class="Texto_Reporte">
            <td width="10%" colspan="3" align="right"><strong>Totales en Efectivo </strong></td>
            <td width="10%" colspan="2" align="center"><strong>Totales en General</strong></td>
          </tr>

          <tr class="Texto_Reporte">
            <td width="65%" rowspan="8" valign="top">
              <table width="94%" height="119" border="0" cellpadding="0" cellspacing="0" class="Texto_Reporte">
                <tr>
                  <td valign="top"><strong>Observaci&oacute;n:</strong>&nbsp;&nbsp;<?Php echo $row_FormasVentas[0]['Caj_Obs']; ?></td>
                </tr>
              </table>
            </td>
            <td width="9%"><strong>Subtotal</strong></td>
            <td width="8%" align="left"><?php echo formato_numero($subtotalesEfectivo['total'], 2, 2); ?></td>

            <td width="9%" align="right"><strong>Subtotal</strong></td>
            <td width="8%" align="right"><?php echo formato_numero($totalVentas, 2, 2); ?></td>
          </tr>

          <tr class="Texto_Reporte">
            <td><strong>Sub. 0%</strong></td>
            <td align="left"><?Php echo formato_numero($subtotalesEfectivo['Sub0'], 2, 2); ?></td>

            <td align="right"><strong>Sub. 0%</strong></td>
            <td align="right"><?Php echo formato_numero($resultados_total[1], 2, 2); ?></td>

          </tr>
          <tr class="Texto_Reporte">
            <td><strong>Sub Iva</strong></td>
            <td align="left"><?Php echo formato_numero($subtotalesEfectivo['SubIva'], 2, 2); ?></td>

            <td align="right"><strong>Sub Iva</strong></td>
            <td align="right"><?Php echo formato_numero($resultados_total[2], 2, 2); ?></td>

          </tr>
          <tr class="Texto_Reporte">
            <td><strong>IVA</strong></td>
            <td align="left"><?Php echo formato_numero($subtotalesEfectivo['Iva'], 2, 2); ?></td>

            <td align="right"><strong>IVA</strong></td>
            <td align="right"><?Php echo formato_numero($resultados_total[3], 2, 2); ?></td>

          </tr>
          <tr class="Texto_Reporte">
            <td><strong>Descuento</strong></td>
            <td align="left"><?Php echo formato_numero($subtotalesEfectivo['Descuento'], 2, 2); ?></td>

            <td align="right"><strong>Descuento</strong></td>
            <td align="right"><?Php echo formato_numero($resultados_total[4], 2, 2); ?></td>

          </tr>
          <tr class="Texto_Reporte">
            <td><strong>Monto inicial</strong></td>
            <td align="left"><?php echo formato_numero($row_FormasVentas[0]['Caj_Exi'] + 0, 2, 2); ?></td>

            <td align="right"><strong>Monto inicial</strong></td>
            <td align="right"><?php echo formato_numero($row_FormasVentas[0]['Caj_Exi'] + 0, 2, 2); ?></td>

          </tr>
          <tr class="Texto_Reporte">
            <td><strong>Retenciones</strong></td>
            <td align="left"><?Php echo '-' . formato_numero($totalRetencionesEfectivo, 2, 2); ?></td>

            <td align="right"><strong>Retenciones</strong></td>
            <td align="right"><?Php echo '-' . formato_numero($totalRetenciones, 2, 2); ?></td>

          </tr>
          <tr class="Texto_Reporte">
            <td><strong>Total</strong></td>
            <td align="left"><strong><?php echo formato_numero($subtotalesEfectivo['total'] - $totalRetenciones + $row_FormasVentas[0]['Caj_Exi'], 2, 2); ?></strong></td>

            <td align="right"><strong>Total</strong></td>
            <td align="right"><strong><?php echo formato_numero($resultados_total[5] - $totalRetenciones + $row_FormasVentas[0]['Caj_Exi'], 2, 2); ?></strong></td>

          </tr>
        </table>

      </td>
    </tr>
    <tr>
      <td align="center">
        <div align="center"><?Php $obBD_con1->pieReporteStandar($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?></div>
      </td>
    </tr>
  </table>


</BODY>

</HTML>
<?php
$obBD_conexion->cerrar();
$obBD_con1->liberar();
?>