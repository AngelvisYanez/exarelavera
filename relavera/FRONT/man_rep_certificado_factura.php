<?php
/**
 * Certificado B.07.01 por factura (HTML, sin firma digital embebida).
 * Con firma electronica usar man_rep_certificado_factura_pdf.php (igual que rango en man_alt_manifiesto).
 * embed=1: impresion desde iframe en man_fac_man.php (firmar=0).
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_man_fac_1.0.php');
require_once('../LOGICA/man_cert_firma_helper.php');

header('Content-Type: text/html; charset=UTF-8');

function toUtf8($s) {
    if ($s === null) return '';
    $s = (string)$s;
    // Si ya es UTF-8 v?lido, se mantiene.
    $u = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
    if ($u !== false && $u !== '') return $u;
    // Si viene como Windows-1252 / Latin1, convertir.
    $u = @iconv('Windows-1252', 'UTF-8//IGNORE', $s);
    if ($u !== false && $u !== '') return $u;
    $u = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $s);
    if ($u !== false && $u !== '') return $u;
    return $s;
}

function h($s) {
    return htmlspecialchars(toUtf8($s), ENT_QUOTES, 'UTF-8');
}

$obBD_conexion = new Class_Log_Conexion_manifiesto($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_manifiesto;

$embed = !empty($_GET['embed']);
$firmar = isset($_GET['firmar']) && $_GET['firmar'] !== '' && $_GET['firmar'] !== '0';

$Vet_Cod = isset($_GET['Vet_Cod']) ? intval($_GET['Vet_Cod']) : 0;

/* Redireccion si pidieron firma: el PDF lleva setSignature como man_rep_certificado_rango_pdf.php */
if ($firmar && $Vet_Cod > 0) {
    if ($embed) {
        echo '<script>if (window.parent && window.parent.alert) { window.parent.alert("La firma electronica se genera en PDF. Elija Firmar: Si en el listado de facturas."); }</script>';
        exit;
    }
    $redir = 'man_rep_certificado_factura_pdf.php?Vet_Cod=' . $Vet_Cod;
    if (!empty($_GET['Pla_Cod_Usuario']) && intval($_GET['Pla_Cod_Usuario']) > 0) {
        $redir .= '&Pla_Cod_Usuario=' . intval($_GET['Pla_Cod_Usuario']);
    }
    header('Location: ' . $redir);
    exit;
}
if ($Vet_Cod <= 0) {
    if ($embed) {
        echo '<script>if (window.parent && window.parent.alert) window.parent.alert("Factura no valida");</script>';
        exit;
    }
    die('Factura no valida');
}

$params = array('Vet_Cod' => $Vet_Cod);
$Usu_Cod = isset($_SESSION['Ses_Usu_Cod']) ? intval($_SESSION['Ses_Usu_Cod']) : 0;
$pla_asignada = $obBD_con1->getArrayConsulta(75, array('Usu_Cod' => $Usu_Cod), $obBD_conexion);
$Pla_Cod_Asignada = (is_array($pla_asignada) && count($pla_asignada) > 0) ? intval($pla_asignada[0]['Pla_Cod']) : 0;
if ($Pla_Cod_Asignada > 0) {
    $params['Pla_Cod_Usuario'] = $Pla_Cod_Asignada;
} elseif (!empty($_GET['Pla_Cod_Usuario']) && intval($_GET['Pla_Cod_Usuario']) > 0) {
    $params['Pla_Cod_Usuario'] = intval($_GET['Pla_Cod_Usuario']);
}

$cabecera = $obBD_con1->getRowConsulta(89, $params, $obBD_conexion);
$factura = $obBD_con1->getRowConsulta(87, $params, $obBD_conexion);
$listado = $obBD_con1->getArrayConsulta(88, $params, $obBD_conexion);
if (!is_array($listado)) $listado = array();

if (!$cabecera || count($listado) === 0) {
    $msg = 'No hay datos para imprimir';
    if ($embed) {
        echo '<script>if (window.parent && window.parent.alert) window.parent.alert(' . json_encode($msg) . ');</script>';
        exit;
    }
    die($msg);
}

$facturados = 0;
$no_facturados = 0;
$Fec_Des = null;
$Fec_Has = null;
foreach ($listado as $l) {
    if (!empty($l['Facturado'])) $facturados++; else $no_facturados++;
    $f = isset($l['Fecha']) ? substr((string)$l['Fecha'], 0, 10) : null;
    if ($f) {
        if ($Fec_Des === null || $f < $Fec_Des) $Fec_Des = $f;
        if ($Fec_Has === null || $f > $Fec_Has) $Fec_Has = $f;
    }
}
$total_entregados = count($listado);

$firma_blocks = man_cert_firma_html_blocks($firmar, $Ses_Emp_Cod, $obBD_con1, $obBD_conexion, date('Y-m-d'));
$emp_cod_verf = isset($Ses_Emp_Cod) ? (int)$Ses_Emp_Cod : 0;
$verf_qr_html = man_cert_verificacion_qr_html($Vet_Cod, $emp_cod_verf);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado de Manifiestos</title>
    <style>
        /* Mismo lenguaje visual que btnExportarPDFMan (man_fac_man.php) */
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 14px 18px;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 13px;
            color: #1e293b;
            background: #f1f5f9;
            line-height: 1.45;
            position: relative;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .signature-section {
            padding: 16px 22px 8px;
            background: #fff;
            position: relative;
            z-index: 1;
        }
        .signature-box { display: inline-block; text-align: left; min-width: 350px; background: #fff; position: relative; }
        .signature-box-content { display: flex; align-items: center; margin-bottom: 5px; padding-right: 15px; }
        .signature-qr { width: 85px; height: 85px; margin-right: 12px; }
        .signature-details { font-size: 11px; line-height: 1.2; font-family: Arial, sans-serif; }
        .signature-details .label { font-size: 10px; color: #1a1a1a; display: block; margin-bottom: 1px; }
        .signature-details .name { font-size: 15px; font-weight: bold; color: #000; display: block; margin: 2px 0 3px 0; }
        .signature-details .check { font-size: 10px; color: #1a1a1a; display: block; }
        .signature-line-bottom { border-top: 1.5px solid #000; width: 100%; max-width: 350px; margin: 4px 0 10px 0; }
        .signature-company { font-size: 16px; font-weight: bold; text-align: center; text-transform: uppercase; max-width: 350px; letter-spacing: 1px; }
        .report-wrap { position: relative; z-index: 1; }
        .report-wrap {
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
        }
        .report-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
        }
        .report-header .brand {
            flex: 1;
            min-width: 200px;
            padding-left: 10px;
            border-left: 3px solid #0f766e;
        }
        .report-header .brand h1 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 750;
            letter-spacing: -0.01em;
            color: #0f172a;
            line-height: 1.1;
        }
        .report-header .brand .doc-type {
            margin: 4px 0 0;
            font-size: 0.62rem;
            font-weight: 650;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #64748b;
        }
        .report-header .logo-wrap {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            min-width: 140px;
        }
        .report-header .logo-frame {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            background: transparent;
            border: none;
        }
        .report-header .logo-frame img {
            display: block;
            max-height: 72px;
            max-width: 200px;
            width: auto;
            height: auto;
            object-fit: contain;
        }
        /* Encabezado oficial B.07.01 (siempre visible, centrado) */
        .cert-official-header {
            text-align: center;
            padding: 18px 22px 16px;
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .cert-official-header .cert-project-title {
            margin: 0 0 10px;
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.35;
        }
        .cert-official-header .cert-main-title {
            margin: 0 0 10px;
            font-size: 12.5px;
            font-weight: 600;
            color: #334155;
            line-height: 1.4;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .cert-official-header .cert-operator {
            margin: 0;
            font-size: 11.5px;
            font-weight: 700;
            color: #475569;
        }
        .doc-meta {
            padding: 16px 22px 8px;
            background: #fff;
        }
        .section-title {
            margin: 0 0 10px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #64748b;
        }
        table.meta-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            font-size: 12.5px;
        }
        table.meta-table th,
        table.meta-table td {
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        table.meta-table th {
            width: 32%;
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            text-align: left;
            white-space: nowrap;
        }
        table.meta-table td {
            background: #fff;
            color: #0f172a;
            font-weight: 500;
        }
        table.meta-table tr:nth-child(even) th { background: #f1f5f9; }
        table.meta-table tr:nth-child(even) td { background: #fafbfc; }
        .cert-statement {
            margin: 0 22px 12px;
            padding: 10px 12px;
            font-size: 12px;
            line-height: 1.5;
            color: #334155;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            text-align: justify;
        }
        .table-block {
            padding: 8px 22px 20px;
            background: #fff;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            font-size: 10.5px;
            table-layout: fixed;
        }
        table.data-table caption {
            caption-side: top;
            text-align: left;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            padding: 14px 0 8px;
        }
        table.data-table th,
        table.data-table td {
            border: 1px solid #e2e8f0;
            padding: 3px 4px;
            text-align: center;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        table.data-table tbody td {
            white-space: normal;
            overflow: visible;
        }
        table.data-table tbody td.chofer-col {
            text-align: left;
        }
        table.data-table th:nth-child(3),
        table.data-table td:nth-child(3) {
            width: 20%;
        }
        table.data-table th:nth-child(7),
        table.data-table td:nth-child(7) {
            width: 9%;
        }
        table.data-table th:nth-child(9),
        table.data-table td:nth-child(9) {
            width: 8%;
        }
        table.data-table thead th {
            background: #fff;
            color: #0f172a;
            font-weight: 700;
            font-size: 10px;
            border-color: #e2e8f0;
            white-space: nowrap;
            overflow: visible;
            text-overflow: clip;
        }
        table.data-table tbody tr:nth-child(even) { background: #f8fafc; }
        table.data-table th:nth-child(1),
        table.data-table td:nth-child(1) {
            width: 42px;
            max-width: 42px;
            min-width: 42px;
            padding: 2px 4px;
            text-align: center;
            font-variant-numeric: tabular-nums;
        }
        table.data-table tbody td.numeric {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
        table.data-table tr.total-row td {
            background: #f1f5f9;
            font-weight: 700;
            border-color: #cbd5e1;
            color: #0f172a;
            overflow: visible;
            text-overflow: clip;
        }
        table.data-table tr.total-row td.numeric { text-align: right !important; }
        .no-factura { color: #b45309; font-weight: 700; }
        .footer-exa {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            padding: 10px 22px 14px;
            font-size: 10px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            background: #fff;
        }
        .footer-exa strong { color: #475569; }
        .verf-qr-section {
            padding: 12px 22px 16px;
            background: #fff;
            border-top: 1px dashed #e2e8f0;
            display: flex;
            justify-content: center;
            align-items: center;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .verf-qr-box {
            text-align: center;
            width: 100%;
            max-width: 220px;
            margin: 0 auto;
        }
        .verf-qr-img {
            width: 120px;
            height: 120px;
            display: block;
            margin: 0 auto 6px;
        }
        .verf-qr-caption {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #0f766e;
        }
        .verf-qr-hint {
            font-size: 9px;
            color: #64748b;
            margin-top: 3px;
            line-height: 1.3;
        }
        .btn-print {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #0f766e;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
        }
        @media print {
            html, body {
                background: #fff !important;
                padding: 0;
                margin: 0;
                height: auto !important;
                overflow: visible !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .btn-print { display: none; }
            .report-wrap {
                display: block !important;
                visibility: visible !important;
                border: none;
                box-shadow: none;
                border-radius: 0;
            }
            .report-header,
            .cert-official-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; page-break-inside: avoid; break-inside: avoid; }
            table.data-table thead th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        @page { margin: 10mm; }
    </style>
</head>
<body<?php echo $embed ? '' : ' onload="window.print();"'; ?>>

    <?php if (!$embed) { ?>
    <button class="btn-print" onclick="window.print();">Imprimir Certificado</button>
    <?php } ?>

    <?php
    $empresa_cert = 'Ecoparkmining';
    $logo_path_web = '';
    if (!empty($_SESSION['Ses_Emp_Log']) && is_string($_SESSION['Ses_Emp_Log'])) {
        $logo_path_web = $_SESSION['Ses_Emp_Log'];
    }
    if ($logo_path_web === '' || !file_exists($logo_path_web)) {
        $Ses_Emp_Cod_logo = isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 0;
        $logo_path_web = "../../imagenes/$Ses_Emp_Cod_logo/relavera.png";
        if (!file_exists($logo_path_web)) {
            $logo_path_web = "../../imagenes/620/relavera.png";
        }
    }
    $fec_des_txt = $Fec_Des ? date('d/m/Y', strtotime($Fec_Des)) : '-';
    $fec_has_txt = $Fec_Has ? date('d/m/Y', strtotime($Fec_Has)) : '-';
    ?>
    <div class="report-wrap">
        <header class="report-header">
            <div class="brand">
                <h1><?php echo h($empresa_cert); ?></h1>
                <p class="doc-type">Certificado de manifiestos B.07.01</p>
            </div>
            <div class="logo-wrap">
                <div class="logo-frame">
                    <img src="<?php echo h($logo_path_web); ?>" alt="<?php echo h($empresa_cert); ?>">
                </div>
            </div>
        </header>

        <div class="cert-official-header">
            <p class="cert-project-title">Proyecto ambiental asociativo Relavera Comunitaria &quot;EL TABL&Oacute;N&quot;</p>
            <p class="cert-main-title">Certificado de manifiestos &uacute;nico en la fase de disposici&oacute;n final<br>de desechos peligrosos y/o especiales B.07.01</p>
            <p class="cert-operator">ECOPARKMINING S.A. operador asociado del Gobierno Aut&oacute;nomo Provincial de El Oro</p>
        </div>

        <section class="doc-meta">
            <h2 class="section-title">Datos del documento</h2>
            <table class="meta-table" role="presentation">
                <tr>
                    <th>No. factura</th>
                    <td><?php echo h(isset($factura['Vet_Num_Completo']) ? $factura['Vet_Num_Completo'] : ''); ?></td>
                </tr>
                <tr>
                    <th>Fecha factura</th>
                    <td><?php echo h(isset($factura['Vet_Fec']) ? $factura['Vet_Fec'] : ''); ?></td>
                </tr>
            </table>
        </section>

        <section class="doc-meta">
            <h2 class="section-title">Informaci&oacute;n del generador</h2>
            <table class="meta-table" role="presentation">
                <tr>
                    <th>RUC</th>
                    <td><?php echo h($cabecera['Prs_Ced']); ?></td>
                </tr>
                <tr>
                    <th>Representante</th>
                    <td><?php echo h($cabecera['Representante']); ?></td>
                </tr>
                <tr>
                    <th>Nombre de planta</th>
                    <td><?php echo h($cabecera['Pla_Nom']); ?></td>
                </tr>
                <tr>
                    <th>C&oacute;digo de planta</th>
                    <td><?php echo h($cabecera['Pla_Car']); ?></td>
                </tr>
            </table>
        </section>

        <section class="doc-meta">
            <h2 class="section-title">Resumen del certificado</h2>
            <table class="meta-table" role="presentation">
                <tr>
                    <th>Rango manifiestos facturados</th>
                    <td>Desde <?php echo h($fec_des_txt); ?> &mdash; Hasta <?php echo h($fec_has_txt); ?></td>
                </tr>
                <tr>
                    <th>Fecha de emisi&oacute;n</th>
                    <td><?php echo date('d/m/Y'); ?></td>
                </tr>
                <tr>
                    <th>Cant. manifiestos facturados</th>
                    <td><?php echo (int)$facturados; ?></td>
                </tr>
            </table>
        </section>

        <p class="cert-statement">
            EL presente certificado detalla los manifiestos emitidos por la entrega de Desechos Peligrosos B.07.01 al proyecto ambiental asociativo &quot;EL TABL&Oacute;N&quot;, por parte del generador de Desechos Peligrosos.
        </p>

        <div class="table-block">
            <table class="data-table">
                <caption>Manifiestos generados</caption>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Chofer</th>
                        <th>No. manif.</th>
                        <th>Gu&iacute;a</th>
                        <th>Peso (kg)</th>
                        <th>Factura</th>
                        <th>Veh&iacute;culo</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $count = 1;
                    $suma_total = 0;
                    $suma_peso = 0;
                    foreach ($listado as $item):
                        $suma_total += (float)$item['Valor'];
                        $suma_peso += (float)$item['Man_Pes'];
                    ?>
                    <tr>
                        <td><?php echo $count++; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($item['Fecha'])); ?></td>
                        <td class="chofer-col"><?php echo h(isset($item['chofer']) ? $item['chofer'] : ''); ?></td>
                        <td><?php echo h($item['Man_Num_Full']); ?></td>
                        <td><?php echo h(isset($item['Man_Gui']) ? $item['Man_Gui'] : ''); ?></td>
                        <td class="numeric"><?php echo number_format((float)$item['Man_Pes'], 2, '.', ','); ?></td>
                        <td<?php echo ((int)$item['Facturado'] === 0) ? ' class="no-factura"' : ''; ?>>
                            <?php echo ((int)$item['Facturado'] === 1) ? h($item['Factura']) : '-'; ?>
                        </td>
                        <td><?php echo h($item['Veh_Pla']); ?></td>
                        <td class="numeric">$ <?php echo number_format((float)$item['Valor'], 2, '.', ','); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="5">TOTALES</td>
                        <td class="numeric"><?php echo number_format($suma_peso, 2, '.', ','); ?></td>
                        <td></td>
                        <td></td>
                        <td class="numeric">$ <?php echo number_format($suma_total, 2, '.', ','); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="signature-section">
            <?php echo $firma_blocks['signature']; ?>
        </div>

        <?php if ($verf_qr_html !== '') { echo $verf_qr_html; } ?>

        <footer class="footer-exa">
            <div><strong>Generado por:</strong> <?php echo h($_SESSION['Ses_Prs_Nom'] . ' ' . $_SESSION['Ses_Prs_Ape']); ?></div>
            <div>Generado el <?php echo date('d/m/Y'); ?> &mdash; EXA Software Contable</div>
        </footer>
    </div>

</body>
</html>
