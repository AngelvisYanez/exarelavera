<?
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
  <TITLE><?php echo $Ses_Sys_Nom; ?></TITLE>
  <?php require_once("../../mascaras/model1/estilos/print.php"); ?>
  <meta http-equiv="Content-Type" content="text/html;UTF-8">
  <meta charset="UTF-8">
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
            <td width="236" class="Texto_Reporte">&nbsp;<?php echo $row_FormasVentas[0]['Caj_Fec'] . ' ' . $row_FormasVentas[0]['Caj_Hoi']; ?></td>
            <td width="139" class="Texto_Reporte"><strong>Caja:</strong></td>
            <td width="140" class="Texto_Reporte"><? echo $row_FormasVentas[0]['Pun_Des']; ?></td>
          </tr>
          <tr>
            <td width="132" class="Texto_Reporte"><strong>Fecha de Cierre:</strong></td>
            <td width="236" class="Texto_Reporte">&nbsp;<?php echo $row_FormasVentas[0]['Caj_Fef'] . ' ' . $row_FormasVentas[0]['Caj_Hof']; ?></td>
            <td width="139" class="Texto_Reporte"><strong>Estado de la Caja:</strong></td>
            <td width="140" class="Texto_Reporte">&nbsp;<?php echo $row_FormasVentas[0]['Caj_Est']; ?></td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td valign="top"><?php

                        $resultados_total = explode('*', $obBD_con1->calculosConsultaVentas($Caj_Cod, $optest, $obBD_conexion));
                        ?>
        <table width="100%" border="1" cellpadding="0" cellspacing="0" style="border-collapse:collapse">
          <tr class="TablaRepCompr">
            <td width="11%" align="left" bgcolor="#CCCCCC" class="TablaRepCompr">&nbsp;TIPO DE PAGO</td>
            <td width="25%" align="center" bgcolor="#CCCCCC" class="TablaRepCompr">CLIENTE</td>
            <td width="20%" align="center" bgcolor="#CCCCCC" class="TablaRepCompr">BCO-CTA-CHE.</td>
            <td width="14%" align="center" bgcolor="#CCCCCC" class="TablaRepCompr">N&Uacute;MERO DOCUMENTOS</td>
            <td width="6%" align="right" bgcolor="#CCCCCC" class="TablaRepCompr">SUB 0%</td>
            <td width="6%" align="right" bgcolor="#CCCCCC" class="TablaRepCompr">SUB IVA</td>
            <td width="6%" align="right" bgcolor="#CCCCCC" class="TablaRepCompr">DESCUENTO</td>
            <!--td width="6%" align="right" bgcolor="#CCCCCC" class="TablaRepCompr">IVA</td-->
            <td width="6%" align="right" bgcolor="#CCCCCC" class="TablaRepCompr">TOTAL</td>
          </tr>
          <?php if ($total_ventas != 0) {
            $subtotalesEfectivo;
            $totalVentas = 0;
            foreach ($row_FormasVentas as $datos_forma) {
              $totalDetalle = 0;
              $row_ventas = $obBD_con1->getArrayConsulta(34, $Caj_Cod . '*' . $datos_forma['Pag_Cod'], $obBD_conexion);

          ?>
              <tr class="Texto_Reporte">
                <td colspan="9" align="left">&nbsp;<strong><? echo strtoupper($datos_forma['Pag_Des']); ?></strong></td>
              </tr>
              <? foreach ($row_ventas as $datos) {  ?>
                <tr class="Texto_Reporte">
                  <td align="left">&nbsp; <?php echo "&nbsp;&nbsp;&nbsp;&nbsp;" . strtoupper($datos['Tic_Des']);  ?></td>
                  <td align="left"><?php echo strtoupper($datos['Prs_Ape'] . ' ' . $datos['Prs_Nom']);  ?></td>


                  <td align="center"><?php if ($datos['Bak_Des'] != 'Ninguno') {
                                        echo $datos['Bak_Des'] . ' - ' . $datos['Vet_Cue'] . ' - ' . $datos['Vet_Che'];
                                      } else {
                                      
                                        if(!empty($datos['Vet_Che'])) {  echo   "Número de Voucher: " . $datos['Vet_Che'];    } else { 
                                          echo    '    -     ';
                                        } 
                                    
                                    
                                    } ?></td>


                  <td align="center"><?php echo str_pad($datos['Vet_Num'], 9, '0', STR_PAD_LEFT); ?></td>
                  <td align="right"><?php echo formato_numero($datos['Sub0'], 2, 2); ?></td>
                  <td align="right"><?php echo formato_numero($datos['SubIva'], 2, 2); ?></td>
                  <td align="right"><?php echo "0.00"; /*formato_numero($datos['Descuento'], 2, 2)*/; ?></td>
                  <!--td align="right"><?php echo formato_numero($datos['Iva'], 2, 2); ?></td-->
                  <td align="right"><?php echo formato_numero(($datos['Sub0'] + $datos['SubIva'] + $datos['Iva']) /*- $datos['Descuento']*/ , 2, 2); ?></td>
                </tr>
              <?php
                if ($datos_forma['Pag_Cod'] == 1) {
                  $subtotalesEfectivo['Sub0'] += $datos['Sub0'];
                  $subtotalesEfectivo['SubIva'] += $datos['SubIva'];
                  $subtotalesEfectivo['Descuento'] += $datos['Descuento'];
                  $subtotalesEfectivo['Iva'] += $datos['Iva'];
                  $subtotalesEfectivo['total'] += (($datos['Sub0'] + $datos['SubIva'] + $datos['Iva']) /*- $datos['Descuento']*/ );
                }
                $totalDetalle += ($datos['Sub0'] + $datos['SubIva'] + $datos['Iva']) /*- $datos['Descuento']*/ ;
                $totalVentas += (($datos['Sub0'] + $datos['SubIva'] + $datos['Iva']) /*- $datos['Descuento']*/ );
              }
              ?>

              <tr class="Texto_Reporte">
                <td colspan="7" align="right"><strong><? echo "TOTAL " . strtoupper($datos_forma['Pag_Des']) ?></strong>&nbsp;</td>
                <td align="right"><strong><? echo formato_numero($totalDetalle, 2, 2); ?></strong></td>
              </tr>
            <? }
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
          <? } ?>



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
                  <td colspan="9" align="left">&nbsp;<strong><? echo 'RETENCIONES - ' . strtoupper($datos_forma['Pag_Des']); ?></strong></td>
                </tr>
                <? foreach ($row_ventas as $datos) {  ?>
                  <tr class="Texto_Reporte">
                    <td align="left">&nbsp; <?php echo "&nbsp;&nbsp;&nbsp;&nbsp; RETENCIÓN" ?></td>
                    <td align="left"><?php echo strtoupper($datos['Prs_Ape'] . ' ' . $datos['Prs_Nom']);  ?></td>
                    <td align="center"><?php echo '-' ?></td>
                    <td align="center"><?php echo  str_pad($datos['Vet_Num'], 9, '0', STR_PAD_LEFT); ?></td>
                    <td align="right"><?php echo '-' ?></td>
                    <td align="right"><?php echo '-' ?></td>
                    <td align="right"><?php echo '-' ?></td>
                    <td align="right"><?php echo '-' ?></td>
                    <td align="right"><?php echo formato_numero($datos['Total'], 2, 2) ?></td>
                  </tr>
                <?php
                  if ($datos_forma['Pag_Cod'] == 1) {
                    $totalRetencionesEfectivo += $datos['Total'];
                  }
                  $totalDetalle += $datos['Total'];
                  $totalRetenciones += $datos['Total'];
                } ?>
                <tr class="Texto_Reporte">
                  <td colspan="8" align="right"><strong><? echo "TOTAL RETENCIONES " . strtoupper($datos_forma['Pag_Des']) ?></strong>&nbsp;</td>
                  <td align="right"><strong><? echo formato_numero($totalDetalle, 2, 2); ?></strong></td>
                </tr>
          <?php
              }
            }
          }
          ?>
        </table>

        <br>
        <table width="100%" border="0" cellpadding="0" cellspacing="0">
          <!--  <tr class="Texto_Reporte">             
            <td width="10%" colspan="3" align="right"><strong>Totales en Efectivo </strong></td>
            <td width="10%" colspan="2" align="center"><strong>Totales en General</strong></td>
          </tr> -->

          <tr class="Texto_Reporte">
            <td width="65%" rowspan="8" valign="top">
              <table width="94%" height="119" border="0" cellpadding="0" cellspacing="0" class="Texto_Reporte">
                <tr>
                  <td valign="top"><strong>Observaci&oacute;n:</strong>&nbsp;&nbsp;<?php echo $row_FormasVentas[0]['Caj_Obs']; ?></td>
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
            <td align="left"><?php echo formato_numero($subtotalesEfectivo['Sub0'], 2, 2); ?></td>

            <td align="right"><strong>Sub. 0%</strong></td>
            <td align="right"><?php echo formato_numero($resultados_total[1], 2, 2); ?></td>

          </tr>
          <tr class="Texto_Reporte">
            <td><strong>Sub Iva</strong></td>
            <td align="left"><?php echo formato_numero($subtotalesEfectivo['SubIva'], 2, 2); ?></td>

            <td align="right"><strong>Sub Iva</strong></td>
            <td align="right"><?php echo formato_numero($resultados_total[2], 2, 2); ?></td>

          </tr>
          <tr class="Texto_Reporte">
            <td><strong>IVA</strong></td>
            <td align="left"><?php echo formato_numero($subtotalesEfectivo['Iva'], 2, 2); ?></td>

            <td align="right"><strong>IVA</strong></td>
            <td align="right"><?php echo formato_numero($resultados_total[3], 2, 2); ?></td>

          </tr>
          <tr class="Texto_Reporte">
            <td><strong>Descuento</strong></td>
            <td align="left"><?php echo formato_numero($subtotalesEfectivo['Descuento'], 2, 2); ?></td>

            <td align="right"><strong>Descuento</strong></td>
            <td align="right"><?php echo formato_numero($resultados_total[4], 2, 2); ?></td>

          </tr>
          <tr class="Texto_Reporte">
            <td><strong>Monto inicial</strong></td>
            <td align="left"><? echo formato_numero($row_FormasVentas[0]['Caj_Exi'] + 0, 2, 2); ?></td>

            <td align="right"><strong>Monto inicial</strong></td>
            <td align="right"><? echo formato_numero($row_FormasVentas[0]['Caj_Exi'] + 0, 2, 2); ?></td>

          </tr>
          <tr class="Texto_Reporte">
            <td><strong>Retenciones</strong></td>
            <td align="left"><?php echo '-' . formato_numero($totalRetencionesEfectivo, 2, 2); ?></td>

            <td align="right"><strong>Retenciones</strong></td>
            <td align="right"><?php echo '-' . formato_numero($totalRetenciones, 2, 2); ?></td>

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
        <div align="center"><?php $obBD_con1->pieReporteStandar($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?></div>
      </td>
    </tr>
  </table>


</BODY>

</HTML>
<?php
$obBD_conexion->cerrar();
$obBD_con1->liberar();
?>