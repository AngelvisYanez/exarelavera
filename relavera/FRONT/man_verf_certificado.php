<?php
/**
 * Verificacion publica del certificado B.07.01 por codesales (Vet_Cod) y codecompany (Emp_Cod).
 * Sin sesion: ?codesales=181X...&codecompany=620Y...
 */
require_once(__DIR__ . '/../../Librerias/config.php/register_globals.php');
require_once(__DIR__ . '/../../DATA/MysqlConexion.php');
require_once(__DIR__ . '/../../administrador/LOGICA/logica.php');
require_once(__DIR__ . '/../LOGICA/log_man_fac_1.0.php');
require_once(__DIR__ . '/../LOGICA/man_cert_firma_helper.php');

header('Content-Type: text/html; charset=UTF-8');

function toUtf8_verf($s) {
    if ($s === null) return '';
    $s = (string)$s;
    if (function_exists('mb_check_encoding') && mb_check_encoding($s, 'UTF-8')) {
        return $s;
    }
    $u = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
    if ($u !== false && $u !== '') return $u;
    $u = @iconv('Windows-1252', 'UTF-8//IGNORE', $s);
    if ($u !== false && $u !== '') return $u;
    $u = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $s);
    if ($u !== false && $u !== '') return $u;
    return $s;
}

function h_verf($s) {
    return htmlspecialchars(toUtf8_verf($s), ENT_QUOTES, 'UTF-8');
}

function nf_verf($n, $dec = 2) {
    return number_format((float)$n, $dec, '.', ',');
}

function verf_row_utf8($row) {
    if (!is_array($row)) return $row;
    foreach ($row as $k => $v) {
        if (is_string($v)) {
            $row[$k] = toUtf8_verf($v);
        }
    }
    return $row;
}

function man_verf_resolve_dat_dis($vet_cod, $emp_cod = 0) {
    $vet_cod = (int)$vet_cod;
    if ($vet_cod <= 0) return null;

    $obBD_master = new Class_Log_Conexion_Adm();
    $obBD_adm = new Class_Log_Datos_Adm();

    if ($emp_cod > 0) {
        $row = $obBD_adm->getRowConsultaSql(
            'SELECT Dat_Dis FROM exa_master.data WHERE Emp_Cod = ' . (int)$emp_cod . ' LIMIT 1',
            $obBD_master
        );
        if (is_array($row) && !empty($row['Dat_Dis'])) {
            $dat = trim($row['Dat_Dis']);
            $conn = new Class_Log_Conexion_manifiesto($dat);
            if (!empty($conn->conexion) && function_exists('mysqli_set_charset')) {
                @mysqli_set_charset($conn->conexion, 'utf8mb4');
            }
            $chk = $obBD_adm->getRowConsultaSql(
                'SELECT Vet_Cod FROM ventas WHERE Vet_Cod = ' . $vet_cod . " AND Vet_Est = 'A' LIMIT 1",
                $conn
            );
            if (is_array($chk) && !empty($chk['Vet_Cod'])) {
                return $dat;
            }
        }
    }

    $bases = $obBD_adm->getArrayConsultaSql(
        "SELECT DISTINCT Dat_Dis FROM exa_master.data WHERE Dat_Dis IS NOT NULL AND Dat_Dis != '' ORDER BY Dat_Dis",
        $obBD_master
    );
    if (!is_array($bases)) {
        return null;
    }
    foreach ($bases as $b) {
        if (empty($b['Dat_Dis'])) continue;
        $dat = trim($b['Dat_Dis']);
        $conn = new Class_Log_Conexion_manifiesto($dat);
        if (!empty($conn->conexion) && function_exists('mysqli_set_charset')) {
            @mysqli_set_charset($conn->conexion, 'utf8mb4');
        }
        $chk = $obBD_adm->getRowConsultaSql(
            'SELECT Vet_Cod FROM ventas WHERE Vet_Cod = ' . $vet_cod . " AND Vet_Est = 'A' LIMIT 1",
            $conn
        );
        if (is_array($chk) && !empty($chk['Vet_Cod'])) {
            return $dat;
        }
    }
    return null;
}

$cod_ven = 0;
if (!empty($_GET['codesales'])) {
    $cod_ven = man_cert_deobfuscate_code($_GET['codesales']);
} elseif (!empty($_GET['Cod_Ven'])) {
    $cod_ven = (int)$_GET['Cod_Ven'];
} elseif (!empty($_GET['Vet_Cod'])) {
    $cod_ven = (int)$_GET['Vet_Cod'];
}
$emp_cod_url = 0;
if (!empty($_GET['codecompany'])) {
    $emp_cod_url = man_cert_deobfuscate_code($_GET['codecompany']);
} elseif (!empty($_GET['Emp_Cod'])) {
    $emp_cod_url = (int)$_GET['Emp_Cod'];
}

$error = '';
$row = null;

if ($cod_ven <= 0) {
    $error = 'Enlace de verificacion no valido. Debe incluir el parametro codesales.';
} else {
    $dat_dis = man_verf_resolve_dat_dis($cod_ven, $emp_cod_url);
    if ($dat_dis === null) {
        $error = 'No se encontro la factura solicitada.';
    } else {
        $obBD_conexion = new Class_Log_Conexion_manifiesto($dat_dis);
        if (!empty($obBD_conexion->conexion) && function_exists('mysqli_set_charset')) {
            @mysqli_set_charset($obBD_conexion->conexion, 'utf8mb4');
        }
        $obBD_con1 = new Class_Log_Datos_manifiesto;
        $row = $obBD_con1->getRowConsulta(90, array('Vet_Cod' => $cod_ven), $obBD_conexion);
        if (!$row || empty($row['Vet_Cod'])) {
            $error = 'No se encontro la factura solicitada.';
            $row = null;
        } else {
            $row = verf_row_utf8($row);
        }
    }
}

$fec_des_txt = '-';
$fec_has_txt = '-';
if ($row) {
    if (!empty($row['Fec_Des'])) {
        $fec_des_txt = date('d/m/Y', strtotime($row['Fec_Des']));
    }
    if (!empty($row['Fec_Has'])) {
        $fec_has_txt = date('d/m/Y', strtotime($row['Fec_Has']));
    }
}

$exa_logo_web = '../../skins/img/newlogo_white.png';
$exa_logo_fs = __DIR__ . '/../../skins/img/newlogo_white.png';
if (!file_exists($exa_logo_fs)) {
    $exa_logo_web = '../../skins/img/newlogo.png';
    $exa_logo_fs = __DIR__ . '/../../skins/img/newlogo.png';
}
if (!file_exists($exa_logo_fs)) {
    $exa_logo_web = '../../skins/img/logoexa.png';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificaci&oacute;n certificado<?php echo $row ? ' - ' . h_verf($row['Vet_Num_Completo']) : ''; ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 16px;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            font-size: 14px;
            color: #1e293b;
            background: #f1f5f9;
            line-height: 1.45;
        }
        .page-shell {
            max-width: 1020px;
            margin: 0 auto;
        }
        .wrap {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
            overflow: hidden;
        }
        .exa-page-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 10px 18px;
            background: linear-gradient(135deg, #2C5D94 0%, #3d7bb8 100%);
            color: #fff;
            border-radius: 12px 12px 0 0;
            border: 1px solid #2C5D94;
            border-bottom: none;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .08);
        }
        .exa-page-header img {
            height: 32px;
            width: auto;
            display: block;
            flex-shrink: 0;
        }
        .exa-page-header .exa-brand-title {
            display: block;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            line-height: 1.2;
        }
        .exa-page-header .exa-brand-sub {
            display: block;
            font-size: 0.72rem;
            font-weight: 500;
            opacity: 0.92;
            margin-top: 2px;
        }
        .head {
            padding: 16px 20px;
            border-bottom: 3px solid #0f766e;
        }
        .head h1 { margin: 0 0 4px; font-size: 1.1rem; color: #0f172a; }
        .head .sub {
            margin: 0;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: #64748b;
        }
        .note {
            margin: 12px 20px 0;
            padding: 10px 12px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            font-size: 12px;
            color: #1e40af;
        }
        .body-pad { padding: 12px 20px 20px; }
        .alert {
            padding: 12px 14px;
            border-radius: 8px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        .section-title {
            margin: 16px 0 8px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: #64748b;
        }
        table.meta {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        table.meta th,
        table.meta td {
            border: 1px solid #e2e8f0;
            padding: 7px 10px;
        }
        table.meta th {
            width: 42%;
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            text-align: left;
        }
        .section-title.totales-cert {
            font-size: 0.78rem;
            color: #0f766e;
            margin-top: 20px;
        }
        .totals-key {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin: 10px 0 16px;
            padding: 16px;
            background: linear-gradient(180deg, #ecfdf5 0%, #f0fdf4 100%);
            border: 2px solid #0f766e;
            border-radius: 10px;
        }
        .tot-key {
            text-align: center;
            padding: 14px 10px;
            background: #fff;
            border: 1px solid #6ee7b7;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(15, 118, 110, 0.12);
        }
        .tot-key .lbl {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: #0f766e;
            margin-bottom: 8px;
        }
        .tot-key .val {
            display: block;
            font-size: 1.85rem;
            font-weight: 800;
            color: #064e3b;
            line-height: 1.15;
            font-variant-numeric: tabular-nums;
        }
        .tot-key .val.money {
            font-size: 1.65rem;
        }
        .tot-key .unit {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            margin-top: 4px;
        }
        .section-title.totales-fact {
            font-size: 0.78rem;
            color: #0369a1;
            margin-top: 8px;
        }
        .totals-fact {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin: 10px 0 0;
            padding: 16px;
            background: linear-gradient(180deg, #eff6ff 0%, #f0f9ff 100%);
            border: 2px solid #0369a1;
            border-radius: 10px;
        }
        .tot-fact {
            text-align: center;
            padding: 14px 10px;
            background: #fff;
            border: 1px solid #7dd3fc;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(3, 105, 161, 0.12);
        }
        .tot-fact .lbl {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: #0369a1;
            margin-bottom: 8px;
        }
        .tot-fact .val {
            display: block;
            font-size: 1.85rem;
            font-weight: 800;
            color: #0c4a6e;
            line-height: 1.15;
            font-variant-numeric: tabular-nums;
        }
        .tot-fact.tot-fact-final {
            grid-column: span 1;
            background: #fff;
            border: 2px solid #0284c7;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.2);
        }
        .tot-fact.tot-fact-final .lbl {
            color: #0c4a6e;
            font-size: 0.78rem;
        }
        .tot-fact.tot-fact-final .val {
            font-size: 2.1rem;
            color: #075985;
        }
        @media (max-width: 900px) {
            .totals-key,
            .totals-fact { grid-template-columns: repeat(2, 1fr); }
            .tot-key .val,
            .tot-fact .val { font-size: 1.55rem; }
            .tot-fact.tot-fact-final .val { font-size: 1.75rem; }
            .tot-fact.tot-fact-final { grid-column: span 2; }
        }
        @media (max-width: 560px) {
            .totals-key,
            .totals-fact { grid-template-columns: 1fr; }
            .tot-key .val,
            .tot-fact .val { font-size: 1.45rem; }
            .tot-fact.tot-fact-final { grid-column: span 1; }
            .tot-fact.tot-fact-final .val { font-size: 1.65rem; }
        }
        .foot {
            padding: 10px 20px;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .page-shell { max-width: none; }
            .exa-page-header,
            .wrap { box-shadow: none; border-radius: 0; }
            .exa-page-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .wrap { box-shadow: none; border: none; }
            .note { display: none; }
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <header class="exa-page-header">
            <img src="<?php echo h_verf($exa_logo_web); ?>" alt="EXA Software Contable">
            <div>
                <span class="exa-brand-title">EXA Software Contable</span>
                <span class="exa-brand-sub">Verificaci&oacute;n de certificados B.07.01</span>
            </div>
        </header>
    <div class="wrap">
        <header class="head">
            <p class="sub">Verificaci&oacute;n certificado B.07.01</p>
            <h1>Datos oficiales de la factura</h1>
        </header>

        <?php if ($row) { ?>
        <p class="note">
            Compare estos valores con los que aparecen en el certificado impreso (n&uacute;mero de factura, cliente, totales de manifiestos, peso y valor).
        </p>
        <?php } ?>

        <div class="body-pad">
            <?php if ($error !== '') { ?>
                <div class="alert"><?php echo h_verf($error); ?></div>
                <p style="margin-top:12px;font-size:12px;color:#64748b;">
                    Ejemplo: <code>man_verf_certificado.php?codesales=151X7K2M9A4P1Q&amp;codecompany=620B3N8R2K5M7W</code>
                </p>
            <?php } else { ?>

            <h2 class="section-title">Datos del documento</h2>
            <table class="meta">
                <tr><th>No. factura</th><td><?php echo h_verf($row['Vet_Num_Completo']); ?></td></tr>
                <tr><th>Fecha factura</th><td><?php echo h_verf($row['Vet_Fec']); ?></td></tr>
                <tr><th>Rango manifiestos facturados</th><td>Desde <?php echo h_verf($fec_des_txt); ?> &mdash; Hasta <?php echo h_verf($fec_has_txt); ?></td></tr>
            </table>

            <h2 class="section-title">Generador</h2>
            <table class="meta">
                <tr><th>RUC</th><td><?php echo h_verf($row['Prs_Ced']); ?></td></tr>
                <tr><th>Representante</th><td><?php echo h_verf($row['Representante']); ?></td></tr>
                <tr><th>Nombre de planta</th><td><?php echo h_verf($row['Pla_Nom']); ?></td></tr>
                <tr><th>C&oacute;digo de planta</th><td><?php echo h_verf($row['Pla_Car']); ?></td></tr>
            </table>

            <h2 class="section-title totales-cert">Totales del certificado (verificar)</h2>
            <div class="totals-key">
                <div class="tot-key">
                    <span class="lbl">Manifiestos facturados</span>
                    <span class="val"><?php echo (int)$row['cant_manifiestos']; ?></span>
                    <span class="unit">cantidad total</span>
                </div>
                <div class="tot-key">
                    <span class="lbl">Peso total</span>
                    <span class="val"><?php echo nf_verf($row['peso_total']); ?></span>
                    <span class="unit">kilogramos (kg)</span>
                </div>
                <div class="tot-key">
                    <span class="lbl">Valor manifiestos</span>
                    <span class="val money">$ <?php echo nf_verf($row['valor_manifiestos']); ?></span>
                    <span class="unit">suma del certificado</span>
                </div>
            </div>

            <h2 class="section-title totales-fact">Otros totales de la factura</h2>
            <div class="totals-fact">
                <div class="tot-fact">
                    <span class="lbl">IVA factura</span>
                    <span class="val">$ <?php echo nf_verf($row['iva_factura']); ?></span>
                </div>
                <div class="tot-fact">
                    <span class="lbl">Subtotal factura</span>
                    <span class="val">$ <?php echo nf_verf($row['subtotal_factura']); ?></span>
                </div>
                <div class="tot-fact tot-fact-final">
                    <span class="lbl">Valor total factura</span>
                    <span class="val">$ <?php echo nf_verf($row['total_factura']); ?></span>
                </div>
            </div>

            <?php } ?>
        </div>

        <footer class="foot">
            Verificaci&oacute;n de certificado &mdash; <?php echo date('d/m/Y H:i'); ?> &mdash; EXA Software Contable
        </footer>
    </div>
    </div>
</body>
</html>
