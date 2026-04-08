<?php
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_manifiesto.php');

$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Mani;

$Cli_Cod = $_GET['Cli_Cod'];
$Pla_Cod = $_GET['Pla_Cod'];
$Fec_Des = $_GET['Fec_Des'];
$Fec_Has = $_GET['Fec_Has'];

// 1. Obtener datos de cabecera
$cabecera = $obBD_con1->getRowConsulta(8, array('Cli_Cod' => $Cli_Cod, 'Pla_Cod' => $Pla_Cod), $obBD_conexion);

// 2. Obtener listado de manifiestos
$listado = $obBD_con1->getArrayConsulta(9, array('Cli_Cod' => $Cli_Cod, 'Pla_Cod' => $Pla_Cod, 'Fec_Des' => $Fec_Des, 'Fec_Has' => $Fec_Has), $obBD_conexion);

$facturados = 0;
$no_facturados = 0;
foreach($listado as $l) {
    if($l['Facturado'] == 1) $facturados++;
    else $no_facturados++;
}
$total_entregados = count($listado);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado de Manifiestos</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; padding: 30px; color: #333; position: relative;}
        /* Marca de Agua BORRADOR */
        .draft-watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 110px;
            font-weight: bold;
            color: rgba(200, 0, 0, 0.1);
            z-index: 0;
            pointer-events: none;
            white-space: nowrap;
            border: 15px solid rgba(200, 0, 0, 0.1);
            padding: 20px;
            border-radius: 30px;
            text-transform: uppercase;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        .header-text { text-align: center; margin-bottom: 20px; }
        .header-text h2 { margin: 5px 0; font-size: 15px; }
        .header-text h3 { margin: 5px 0; font-size: 13px; font-weight: normal; }
        
        .info-container { display: flex; justify-content: space-between; margin-bottom: 20px; position: relative; }
        .info-table { border-collapse: collapse; width: 70%; }
        .info-table td { padding: 3px 0; }
        .info-label { font-weight: bold; width: 170px; }
        .info-value { padding: 2px 5px; min-width: 250px; display: inline-block; }
        
        .logo { position: absolute; right: 0; top: 0; width: 150px; }
        
        .date-range-container { margin: 15px 0; font-weight: bold; }
        .date-box { padding: 3px 10px; border: 1px solid #ccc; }
        
        .summary-black { color: #000; font-weight: bold; margin-top: 15px; }
        .summary-black div { margin-bottom: 5px; }
        .summary-row { display: flex; width: 450px; border-bottom: 1px dotted #ccc; padding: 2px 0; }
        .summary-label { flex: 1; }
        .summary-value { width: 100px; text-align: right; }
        
        .total-row-summary { display: flex; width: 450px; padding: 5px 0; font-weight: bold; margin-top: 5px; }
        .underline { text-decoration: underline; }
        
        .manifest-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .manifest-table th { background-color: #fff; border: 2px solid #000; padding: 6px; text-align: center; }
        .manifest-table td { border: 1px solid #000; padding: 4px; text-align: center; }
        
        .no-factura { font-weight: bold; }
        .total-row-cell { border: 2px solid #000 !important; font-weight: bold; text-align: right; padding-right: 10px !important; }
        
        .footer-exa { text-align: right; margin-top: 5px; font-size: 10px; border-top: 1px solid #000; padding-top: 2px; }
        
        /* .signature-section { margin-top: 80px; text-align: left; padding-left: 20px; width: 250px; }
        .signature-line { border-top: 1px solid #337ab7; margin-bottom: 5px; }
        .signature-name { text-align: center; font-weight: bold; margin: 0; display: block; }
        .signature-text { height: 40px; } */
        
        @media print {
            .btn-print { display: none; }
            body { padding: 10px; }
        }
        
        .btn-print {
            position: fixed; top: 20px; right: 20px;
            background: #337ab7; color: #fff; border: none;
            padding: 10px 20px; border-radius: 5px; cursor: pointer;
            font-size: 14px; z-index: 1000;
        }
    </style>
</head>
<body onload="window.print();">

    <div class="draft-watermark">BORRADOR</div>

    <button class="btn-print" onclick="window.print();">Imprimir Certificado</button>

    <div class="header-text">
        <h2>Proyecto ambiental asociativo Relavera Comunitaria "EL TABLON"</h2>
        <h3>CERTIFICADO DE MANIFIESTOS UNICO EN LA FASE DE DISPOSICION FINAL<br>DE DESECHOS PELIGROSOS Y/O ESPECIALES B.07.01</h3>
        <p style="font-weight: bold;">ECOPARKMINING S.A. operador asociado del Gobierno Autonomo Provincial de El Oro</p>
    </div>

    <div class="info-container">
        <table class="info-table">
            <tr>
                <td class="info-label">RUC</td>
                <td><span class="info-value"><?php echo $cabecera['Prs_Ced']; ?></span></td>
            </tr>
            <tr>
                <td class="info-label">REPRESENTANTE</td>
                <td><span class="info-value"><?php echo $cabecera['Representante']; ?></span></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td class="info-label">NOMBRE DE PLANTA</td>
                <td><span class="info-value"><?php echo $cabecera['Pla_Nom']; ?></span></td>
            </tr>
            <tr>
                <td class="info-label">CODIGO DE PLANTA</td>
                <td><span class="info-value"><?php echo $cabecera['Pla_Car']; ?></span></td>
            </tr>
        </table>
        <div class="logo">
            <?php 
            $logo_path_web = "../../imagenes/$Ses_Emp_Cod/relavera.png"; 
            if (!file_exists($logo_path_web)) $logo_path_web = "../../imagenes/620/relavera.png";
            ?>
            <img src="<?php echo $logo_path_web; ?>" alt="Logo Relavera" style="width: 100%;">
        </div>
    </div>

    <div class="date-range-container">
        FECHA DE CORTE DE MANIFIESTO &nbsp;&nbsp;&nbsp;&nbsp; 
        <span class="date-box">DESDE: &nbsp;&nbsp; <?php echo date("j/n/Y", strtotime($Fec_Des)); ?></span> &nbsp;&nbsp;
        <span class="date-box">HASTA: &nbsp;&nbsp; <?php echo date("j/n/Y", strtotime($Fec_Has)); ?></span>
    </div>

    <div class="summary-black">
        <div class="summary-row">
            <span class="summary-label">FECHA DE EMISION DEL CERTIFICADO:</span>
            <span class="summary-value"><?php echo date("d/m/Y"); ?></span>
        </div>
        <div class="summary-row">
            <span class="summary-label">CANTIDAD DE MANIFIESTOS FACTURADOS:</span>
            <span class="summary-value"><?php echo $facturados; ?></span>
        </div>
        <div class="summary-row">
            <span class="summary-label">CANTIDAD DE MANIFIESTOS NO FACTURADOS:</span>
            <span class="summary-value"><?php echo $no_facturados; ?></span>
        </div>
        <div class="total-row-summary">
            <span class="summary-label underline">TOTAL MANIFIESTOS ENTREGADOS</span>
            <span class="summary-value"><?php echo $total_entregados; ?></span>
        </div>
    </div>

    <h4 style="margin-top: 20px; font-size: 12px;">Manifiestos Generados</h4>
    <table class="manifest-table">
        <thead>
            <tr>
                <th style="width: 40px;">#</th>
                <th>Cod. Int.</th>
                <th>Fecha</th>
                <th>H. Llegada</th>
                <th>No Manif.</th>
                <th>Peso (KG)</th>
                <th>Factura</th>
                <th>Vehiculo</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $count = 1;
            $suma_total = 0;
            foreach($listado as $item): 
                $suma_total += $item['Valor'];
            ?>
            <tr>
                <td><?php echo $count++; ?></td>
                <td><?php echo $item['Man_Cod']; ?></td>
                <td><?php echo date("d/m/Y", strtotime($item['Fecha'])); ?></td>
                <td><?php echo substr($item['Llegada'], 0, 5); ?></td>
                <td><?php echo $item['Man_Num_Full']; ?></td>
                <td><?php echo number_format($item['Man_Pes'], 2, '.', ','); ?></td>
                <td <?php echo ($item['Facturado'] == 0) ? 'class="no-factura"' : ''; ?>>
                    <?php echo ($item['Facturado'] == 1) ? $item['Factura'] : '-'; ?>
                </td>
                <td><?php echo $item['Veh_Pla']; ?></td>
                <td>$ <?php echo number_format($item['Valor'], 2, '.', ','); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="8" style="border:none; text-align:right; padding-right: 10px; font-weight:bold; padding-top: 10px;">TOTAL:</td>
                <td class="total-row-cell" style="padding-top: 10px;">$ <?php echo number_format($suma_total, 2, '.', ','); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="footer-exa" style="margin-top: 30px; display: flex; justify-content: space-between; border-top: 1px solid #000; padding: 2px 0; font-size: 10px;">
        <div>Generado por: <?php echo $_SESSION['Ses_Prs_Nom'] . ' ' . $_SESSION['Ses_Prs_Ape']; ?></div>
        <div style="text-align: right;">Generado el <?php echo date("d-m-Y"); ?> en EXA [Software Contable]</div>
    </div>

</body>
</html>
