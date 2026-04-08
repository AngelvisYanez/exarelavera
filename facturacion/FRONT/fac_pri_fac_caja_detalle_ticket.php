<?php
/*
* Descripción: Reporte agrupado por tipos de pago - versión compacta sin espacios
* Autor original: Jose Cumbicos
* Modificado por Wilson Belduma
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_aper_caja.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Tes;
$optest = "A";
$row_FormasVentas = $obBD_con1->getArrayConsulta(30, $Caj_Cod, $obBD_conexion);
$total_ventas = count($row_FormasVentas);
?>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title><?php echo $Ses_Sys_Nom; ?></title>
  <?php require_once("../../mascaras/model1/estilos/print.php"); ?>

  <style>
    /* === CONFIGURACIÓN COMPACTA DE IMPRESIÓN === */
    @page { size: 80mm auto; margin: 0; }

    html, body {
      width: 80mm;
      margin: 0;
      padding: 10px;
      font-family: "Lucida Console", monospace;
      font-size: 10px;
      line-height: 1;
      color: #000;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      border-spacing: 0;
      margin: 0;
      padding: 0;
      font-size: 9px;
    }

    td, th {
      padding: 1px 2px;
      margin: 0;
      vertical-align: top;
    }

    .TITULO_REPORTE_2, .TITULO_REPORTE {
      margin: 0;
      padding: 0;
      text-align: center;
      line-height: 1.1;
      font-weight: bold;
    }

    .TITULO_REPORTE_2 { font-size: 12px; }
    .TITULO_REPORTE { font-size: 10px; }

    .Texto_Reporte { font-size: 8px; line-height: 1; }
    .TablaRepCompr { font-size: 9px; }

    hr {
      border: none;
      border-top: 1px dashed #000;
      margin: 2px 0;
    }

    tr, td, table { page-break-inside: avoid; }

    /* MODO PANTALLA */
    @media screen {
      html, body { width: 320px; background: #f7f7f7; padding: 2px; }
      table { border: 1px solid #ccc; }
    }
  </style>
</head>

<body>
  <table align="center">
    <tr>
      <td align="center">
        <?php
        $titulo = "<div class='TITULO_REPORTE_2'>DETALLE DE FACTURAS DE VENTA</div>";
        $subtitulo = "<div class='TITULO_REPORTE'>DETALLADO POR TIPO DE PAGO</div>";
        $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, $titulo, $subtitulo, $obBD_conexion);
        ?>
      </td>
    </tr>

    <tr>
      <td>
        <table>
          <tr>
            <td class="Texto_Reporte"><strong>Fecha Apertura:</strong> <?php echo $row_FormasVentas[0]['Caj_Fec'].' '.$row_FormasVentas[0]['Caj_Hoi']; ?></td>
            <td class="Texto_Reporte"><strong>Caja:</strong> <?php echo $row_FormasVentas[0]['Pun_Des']; ?></td>
          </tr>
          <tr>
            <td class="Texto_Reporte"><strong>Fecha Cierre:</strong> <?php echo $row_FormasVentas[0]['Caj_Fef'].' '.$row_FormasVentas[0]['Caj_Hof']; ?></td>
            <td class="Texto_Reporte"><strong>Estado:</strong> <?php echo $row_FormasVentas[0]['Caj_Est']; ?></td>
          </tr>
        </table>
      </td>
    </tr>

    <tr>
      <td>
        <?php $resultados_total = explode('*', $obBD_con1->calculosConsultaVentas($Caj_Cod, $optest, $obBD_conexion)); ?>
        <table border="1">
          <tr class="TablaRepCompr">
            <td align="center">Bco.Che.</td>
            <td align="center">Num.Doc</td>
            <td align="right">Sub 0%</td>
            <td align="right">SubTotal</td>
            <td align="right">Desc.</td>
            <td align="right">IVA</td>
            <td align="right">Total</td>
          </tr>

          <?php
          if ($total_ventas != 0) {
            $subtotalesEfectivo = array('Sub0'=>0,'SubIva'=>0,'Descuento'=>0,'Iva'=>0,'total'=>0);
            $totalVentas = 0;
            foreach ($row_FormasVentas as $datos_forma) {
              $totalDetalle = 0;
              $row_ventas = $obBD_con1->getArrayConsulta(31, $Caj_Cod.'*'.$datos_forma['Pag_Cod'], $obBD_conexion);
              echo "<tr class='Texto_Reporte'><td colspan='7'><strong>".strtoupper($datos_forma['Pag_Des'])."</strong></td></tr>";
              foreach ($row_ventas as $datos) {
                echo "<tr class='Texto_Reporte' style='background:#eee;'>
                        <td colspan='5'>". utf8_encode( strtoupper($datos['Prs_Ape'].' '.$datos['Prs_Nom']) )  ."</td>
                        <td colspan='2'>".strtoupper($datos['Tic_Des'])."</td>
                      </tr>";
                echo "<tr class='Texto_Reporte'>
                        <td align='center'>".(($datos['Bak_Des']!='Ninguno')?$datos['Bak_Des'].'-'.$datos['Vet_Cue'].'-'.$datos['Vet_Che']:'-')."</td>
                        <td align='center'>".$datos['Vet_Num']."</td>
                        <td align='right'>".formato_numero($datos['Sub0'],2,2)."</td>
                        <td align='right'>".formato_numero($datos['SubIva'],2,2)."</td>
                        <td align='right'>".formato_numero($datos['Descuento'],2,2)."</td>
                        <td align='right'>".formato_numero($datos['Iva'],2,2)."</td>
                        <td align='right'>".formato_numero(($datos['Sub0']+$datos['SubIva']+$datos['Iva'])-$datos['Descuento'],2,2)."</td>
                      </tr>";

                if ($datos_forma['Pag_Cod']==1) {
                  $subtotalesEfectivo['Sub0'] += $datos['Sub0'];
                  $subtotalesEfectivo['SubIva'] += $datos['SubIva'];
                  $subtotalesEfectivo['Descuento'] += $datos['Descuento'];
                  $subtotalesEfectivo['Iva'] += $datos['Iva'];
                  $subtotalesEfectivo['total'] += (($datos['Sub0']+$datos['SubIva']+$datos['Iva'])-$datos['Descuento']);
                }
                $totalDetalle += ($datos['Sub0']+$datos['SubIva']+$datos['Iva'])-$datos['Descuento'];
                $totalVentas += (($datos['Sub0']+$datos['SubIva']+$datos['Iva'])-$datos['Descuento']);
              }
              echo "<tr class='Texto_Reporte'><td colspan='6' align='right'><strong>TOTAL ".strtoupper($datos_forma['Pag_Des'])."</strong></td>
                    <td align='right'><strong>".formato_numero($totalDetalle,2,2)."</strong></td></tr>";
            }
          } else {
            echo "<tr class='Texto_Reporte'><td colspan='7' align='center'>SIN REGISTROS</td></tr>";
          }
          ?>
        </table>

        <table>
          <tr class="Texto_Reporte">
            <td colspan="2" align="center"><strong>TOTALES EN EFECTIVO</strong></td>
            <td colspan="2" align="center"><strong>TOTALES GENERALES</strong></td>
          </tr>
          <tr class="Texto_Reporte">
            <td>Subtotal</td><td><?php echo formato_numero($subtotalesEfectivo['total'],2,2); ?></td>
            <td align="right">Subtotal</td><td align="right"><?php echo formato_numero($totalVentas,2,2); ?></td>
          </tr>
          <tr class="Texto_Reporte">
            <td>Sub. 0%</td><td><?php echo formato_numero($subtotalesEfectivo['Sub0'],2,2); ?></td>
            <td align="right">Sub. 0%</td><td align="right"><?php echo formato_numero($resultados_total[1],2,2); ?></td>
          </tr>
          <tr class="Texto_Reporte">
            <td>IVA</td><td><?php echo formato_numero($subtotalesEfectivo['Iva'],2,2); ?></td>
            <td align="right">IVA</td><td align="right"><?php echo formato_numero($resultados_total[3],2,2); ?></td>
          </tr>
          <tr class="Texto_Reporte">
            <td><strong>Total</strong></td>
            <td><strong><?php echo formato_numero($subtotalesEfectivo['total'],2,2); ?></strong></td>
            <td align="right"><strong>Total</strong></td>
            <td align="right"><strong><?php echo formato_numero($resultados_total[5],2,2); ?></strong></td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td><strong>Observación:</strong> <?php echo $row_FormasVentas[0]['Caj_Obs']; ?></td>
    </tr>
    <tr>
      <td align="center">
        <?php $obBD_con1->pieReporteStandar($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
      </td>
    </tr>
  </table>
</body>
</html>
<?php
$obBD_conexion->cerrar();
$obBD_con1->liberar();
?>
