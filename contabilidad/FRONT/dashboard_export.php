<?php
/**
 * dashboard_export.php
 * Exportador analítico para Excel y PDF del Dashboard Presupuestario (EXA PPTO).
 */

@ini_set('display_errors', '0');
if (function_exists('error_reporting')) {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../contabilidad/LOGICA/con_log_balances2.php');
require_once('../VALIDACIONES/dashboard_validaciones.php');
require_once('../LOGICA/dashboard_logica.php');
require_once('../LOGICA/ppto_format_helpers.php');

if (!isset($Ses_Dat_Dis) && isset($_SESSION['Ses_Dat_Dis'])) {
    $Ses_Dat_Dis = $_SESSION['Ses_Dat_Dis'];
}

$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
$mysqli_conn = $obBD_conexion->conexion;

$tipo_export = isset($_GET['export']) ? trim(strtolower($_GET['export'])) : '';
if ($tipo_export !== 'excel' && $tipo_export !== 'pdf') {
    die("Formato de exportacion no soportado.");
}

$filtros = ppto_dashboard_validar_filtros($_GET);
$modo_ux = isset($filtros['modo_ux']) ? $filtros['modo_ux'] : 'gerente';
$modo_label = ($modo_ux === 'tecnico') ? 'Tecnico (detalle completo)' : 'Gerente (resumen simple)';

$version_nombre = "N/A";
if ($filtros['ppe_id']) {
    $res_ver = $mysqli_conn->query("SELECT Ppe_Ver AS ppe_version, Ppe_Des AS ppe_descripcion FROM pre_presupuesto WHERE Ppe_Cod = " . $filtros['ppe_id']);
    if ($res_ver && $v_row = $res_ver->fetch_assoc()) {
        $version_nombre = "V" . $v_row['ppe_version'] . " (" . $v_row['ppe_descripcion'] . ")";
    }
}

$proyecto_nombre = "Todos los Proyectos";
if ($filtros['proy_id']) {
    $res_pr = $mysqli_conn->query("SELECT Pro_Nom AS proy_nombre FROM pre_proyectos WHERE Pro_Cod = '" . $mysqli_conn->real_escape_string($filtros['proy_id']) . "' LIMIT 1");
    if ($res_pr && $pr_row = $res_pr->fetch_assoc()) {
        $proyecto_nombre = $pr_row['proy_nombre'] . " (" . $filtros['proy_id'] . ")";
    }
}

$meses_nombres = array(
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
);
$periodo_display = $filtros['mes'] !== null ? $meses_nombres[$filtros['mes']] . " de " . $filtros['anio'] : "Consolidado Anual " . $filtros['anio'];

$kpis     = ppto_dash_kpis($mysqli_conn, $filtros);
$partidas = ppto_dash_resumen_partidas($mysqli_conn, $filtros);
$mensual  = ppto_dash_evolucion_mensual($mysqli_conn, $filtros);
$disponible_plan = isset($kpis['disponible_plan']) ? (float)$kpis['disponible_plan'] : (float)$kpis['disponible'];
$disponible_plan_pct = isset($kpis['disponible_plan_porcentaje']) ? (float)$kpis['disponible_plan_porcentaje'] : (float)$kpis['disponible_porcentaje'];
$disponible_forecast = isset($kpis['disponible_forecast']) ? (float)$kpis['disponible_forecast'] : $disponible_plan;
$disponible_forecast_pct = isset($kpis['disponible_forecast_porcentaje']) ? (float)$kpis['disponible_forecast_porcentaje'] : $disponible_plan_pct;

function formatUSD($val) {
    return ppto_fmt_money($val);
}

function formatTon($val) {
    return ppto_fmt_num($val, 2);
}

$html_modo_row = '
        <tr>
            <td style="font-weight: 700; color: #4a5568;">Modo de lectura:</td>
            <td colspan="3">' . htmlspecialchars($modo_label) . '</td>
        </tr>';

if ($modo_ux === 'gerente') {
    $html_kpi = '
    <h4 style="color: #2c5282; margin-top: 0; margin-bottom: 10px; font-size: 14px; border-bottom: 2px solid #ebf8ff; padding-bottom: 4px;">I. KPIs FINANCIEROS Y DE EJECUCION (USD)</h4>
    <table width="100%" cellpadding="8" cellspacing="0" style="border: 1px solid #cbd5e0; margin-bottom: 20px; font-size: 12px; text-align: center;">
        <thead>
            <tr style="background-color: #ebf8ff; font-weight: 700; color: #2b6cb0;">
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0;">PRESUPUESTO APROBADO</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0;">COMPROMETIDO (RESERVADO)</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0;">YA GASTADO</th>
                <th style="border-bottom: 1px solid #cbd5e0;">SALDO DISPONIBLE</th>
            </tr>
        </thead>
        <tbody>
            <tr style="font-weight: 700; font-size: 14px;">
                <td style="border-right: 1px solid #cbd5e0; padding: 10px 6px; color: #2b6cb0;">' . formatUSD($kpis['presupuesto_vigente']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; padding: 10px 6px; color: #718096;">' . formatUSD($kpis['comprometido']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; padding: 10px 6px; color: #38a169;">' . formatUSD($kpis['ejecutado']) . '</td>
                <td style="padding: 10px 6px; color: ' . ($disponible_plan < 0 ? '#e53e3e' : '#2d3748') . ';">' . formatUSD($disponible_plan) . ' (' . number_format($disponible_plan_pct, 2) . '%)</td>
            </tr>
        </tbody>
    </table>';
    $html_partidas_thead = '
            <tr style="background-color: #edf2f7; font-weight: 700; color: #2d3748; text-align: left;">
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: left;">CODIGO</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0;">RUBRO / CAPITULO</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">PRESUPUESTO</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">YA GASTADO</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">SALDO</th>
                <th style="border-bottom: 1px solid #cbd5e0; text-align: center;">ESTADO (%)</th>
            </tr>';
    $partidas_colspan = 6;
} else {
    $html_kpi = '
    <h4 style="color: #2c5282; margin-top: 0; margin-bottom: 10px; font-size: 14px; border-bottom: 2px solid #ebf8ff; padding-bottom: 4px;">I. KPIs FINANCIEROS Y DE EJECUCION (USD)</h4>
    <table width="100%" cellpadding="8" cellspacing="0" style="border: 1px solid #cbd5e0; margin-bottom: 20px; font-size: 12px; text-align: center;">
        <thead>
            <tr style="background-color: #ebf8ff; font-weight: 700; color: #2b6cb0;">
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0;">PPTO INICIAL</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0;">REAJUSTES</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0;">PPTO VIGENTE</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0;">COMPROMETIDO</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0;">EJECUTADO REAL</th>
                <th style="border-bottom: 1px solid #cbd5e0;">DISPONIBLE</th>
            </tr>
        </thead>
        <tbody>
            <tr style="font-weight: 700; font-size: 13px;">
                <td style="border-right: 1px solid #cbd5e0; padding: 10px 6px;">' . formatUSD($kpis['presupuesto_inicial']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; padding: 10px 6px;">' . formatUSD($kpis['reajustes_neto']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; padding: 10px 6px; color: #2b6cb0;">' . formatUSD($kpis['presupuesto_vigente']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; padding: 10px 6px; color: #718096;">' . formatUSD($kpis['comprometido']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; padding: 10px 6px; color: #38a169;">' . formatUSD($kpis['ejecutado']) . '</td>
                <td style="padding: 10px 6px; color: ' . ($disponible_plan < 0 ? '#e53e3e' : '#2d3748') . ';">' . formatUSD($disponible_plan) . '</td>
            </tr>
        </tbody>
    </table>';
    $html_partidas_thead = '
            <tr style="background-color: #edf2f7; font-weight: 700; color: #2d3748; text-align: left;">
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: left;">CODIGO</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0;">DESCRIPCION DE PARTIDA</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">PPTO INICIAL</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">REAJUSTES</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">PPTO VIGENTE</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">COMPROMETIDO</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">EJECUTADO REAL</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">DISPONIBLE</th>
                <th style="border-bottom: 1px solid #cbd5e0; text-align: center;">% EJEC</th>
            </tr>';
    $partidas_colspan = 9;
}

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte Presupuestario Consolidado</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #2d3748; margin: 20px; }
        table { border-collapse: collapse; }
        .badge-verde { background-color: #c6f6d5; color: #22543d; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
        .badge-amarillo { background-color: #fefcbf; color: #744210; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
        .badge-rojo { background-color: #fed7d7; color: #742a2a; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>

    <table width="100%" style="margin-bottom: 15px; border-bottom: 2px solid #2b6cb0; padding-bottom: 10px;">
        <tr>
            <td width="60%" valign="top">
                <h2 style="color: #2b6cb0; margin: 0 0 5px 0; font-size: 18px;">REPORTING EJECUTIVO Y TECNICO - EXA PPTO</h2>
                <div style="font-size: 12px; color: #718096;">Consolidado de Gestion Presupuestaria y Produccion Fisica</div>
            </td>
            <td width="40%" align="right" valign="top" style="font-size: 10px; color: #718096;">
                <strong>Fecha Emision:</strong> ' . date('d/m/Y H:i') . '<br>
                <strong>Empresa ID:</strong> ' . $filtros['Emp_Cod'] . '
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="4" cellspacing="0" style="background-color: #f7fafc; border: 1px solid #e2e8f0; margin-bottom: 15px; font-size: 11px;">
        <tr>
            <td width="15%" style="font-weight: 700; color: #4a5568;">Version Ppto:</td>
            <td width="35%">' . htmlspecialchars($version_nombre) . '</td>
            <td width="15%" style="font-weight: 700; color: #4a5568;">Periodo Evaluado:</td>
            <td width="35%">' . htmlspecialchars($periodo_display) . '</td>
        </tr>
        <tr>
            <td style="font-weight: 700; color: #4a5568;">Proyecto:</td>
            <td colspan="3">' . htmlspecialchars($proyecto_nombre) . '</td>
        </tr>' . $html_modo_row . '
    </table>

    ' . $html_kpi . '

    <h4 style="color: #2c5282; margin-top: 15px; margin-bottom: 10px; font-size: 14px; border-bottom: 2px solid #ebf8ff; padding-bottom: 4px;">II. METRICAS FISICAS Y COSTO UNITARIO</h4>
    <table width="100%" cellpadding="6" cellspacing="0" style="border: 1px solid #cbd5e0; margin-bottom: 20px; font-size: 11px;">
        <tr style="background-color: #f7fafc;">
            <td width="25%" style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; font-weight: 700;">Toneladas Procesadas:</td>
            <td width="25%" style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0;">' . formatTon($kpis['toneladas_procesadas']) . ' TON</td>
            <td width="25%" style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; font-weight: 700;">Costo Promed. por Tonelada:</td>
            <td width="25%" style="border-bottom: 1px solid #cbd5e0; font-weight: 700; color: #2b6cb0;">' . formatUSD($kpis['costo_por_tonelada']) . ' / TON</td>
        </tr>
        <tr>
            <td style="border-right: 1px solid #cbd5e0; font-weight: 700;">Semaforo Produccion:</td>
            <td style="border-right: 1px solid #cbd5e0;">' . strtoupper($kpis['semaforo_produccion']) . '</td>
            <td style="border-right: 1px solid #cbd5e0; font-weight: 700;">Semaforo Financiero Global:</td>
            <td>' . strtoupper($kpis['semaforo_global']) . '</td>
        </tr>
    </table>

    <h4 style="color: #2c5282; margin-top: 15px; margin-bottom: 10px; font-size: 14px; border-bottom: 2px solid #ebf8ff; padding-bottom: 4px;">III. DESGLOSE ANALITICO POR PARTIDAS Y CAPITULOS</h4>
    <table width="100%" cellpadding="5" cellspacing="0" style="border: 1px solid #cbd5e0; margin-bottom: 20px; font-size: 10px;">
        <thead>' . $html_partidas_thead . '</thead>
        <tbody>';

if (!empty($partidas)) {
    foreach ($partidas as $p) {
        $clase = isset($p['ppa_clase']) ? $p['ppa_clase'] : 'D';
        $bg_row = ($clase === 'G') ? 'background-color: #f7fafc; font-weight: 700;' : 'background-color: #ffffff;';
        $pct = (float)$p['porcentaje_ejecucion'];
        $badge = 'badge-verde';
        if ($pct >= 100) { $badge = 'badge-rojo'; }
        elseif ($pct >= 80) { $badge = 'badge-amarillo'; }

        if ($modo_ux === 'gerente') {
            $html .= '
            <tr style="' . $bg_row . '">
                <td style="border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($p['ppa_codigo_clasificacion']) . '</td>
                <td style="border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($p['ppa_descripcion']) . '</td>
                <td style="border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; text-align: right;">' . formatUSD($p['presupuesto_vigente']) . '</td>
                <td style="border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; text-align: right; color: #38a169;">' . formatUSD($p['ejecutado']) . '</td>
                <td style="border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; text-align: right; color: ' . ($p['disponible'] < 0 ? '#e53e3e' : '#2d3748') . ';">' . formatUSD($p['disponible']) . '</td>
                <td style="border-bottom: 1px solid #e2e8f0; text-align: center;"><span class="' . $badge . '">' . number_format($pct, 1) . '%</span></td>
            </tr>';
        } else {
            $html .= '
            <tr style="' . $bg_row . '">
                <td style="border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($p['ppa_codigo_clasificacion']) . '</td>
                <td style="border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($p['ppa_descripcion']) . '</td>
                <td style="border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; text-align: right;">' . formatUSD($p['presupuesto_inicial']) . '</td>
                <td style="border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; text-align: right;">' . formatUSD($p['reajustes']) . '</td>
                <td style="border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; text-align: right;">' . formatUSD($p['presupuesto_vigente']) . '</td>
                <td style="border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; text-align: right;">' . formatUSD($p['comprometido']) . '</td>
                <td style="border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; text-align: right; color: #38a169;">' . formatUSD($p['ejecutado']) . '</td>
                <td style="border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; text-align: right; color: ' . ($p['disponible'] < 0 ? '#e53e3e' : '#2d3748') . ';">' . formatUSD($p['disponible']) . '</td>
                <td style="border-bottom: 1px solid #e2e8f0; text-align: center;"><span class="' . $badge . '">' . number_format($pct, 1) . '%</span></td>
            </tr>';
        }
    }
} else {
    $html .= '<tr><td colspan="' . $partidas_colspan . '" align="center" style="padding: 10px;">No hay partidas registradas para los filtros seleccionados.</td></tr>';
}

$html .= '
        </tbody>
    </table>

    <h4 style="color: #2c5282; margin-top: 15px; margin-bottom: 10px; font-size: 14px; border-bottom: 2px solid #ebf8ff; padding-bottom: 4px;">IV. DISTRIBUCION Y TENDENCIA MENSUAL DE LA EJECUCION</h4>
    <table width="100%" cellpadding="4" cellspacing="0" style="border: 1px solid #cbd5e0; font-size: 9px; text-align: right;">
        <thead>
            <tr style="background-color: #edf2f7; font-weight: 700; color: #2d3748;">
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: left;">CONCEPTO / MES</th>';
for ($m = 1; $m <= 12; $m++) {
    $html .= '<th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: center;">' . substr($meses_nombres[$m], 0, 3) . '</th>';
}
$html .= '<th style="border-bottom: 1px solid #cbd5e0; text-align: right;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; font-weight: 700; text-align: left;">Presupuesto (USD)</td>';
$tot_pres = 0;
for ($i = 0; $i < 12; $i++) {
    $val = isset($mensual['presupuestado'][$i]) ? $mensual['presupuestado'][$i] : 0;
    $tot_pres += $val;
    $html .= '<td style="border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">' . formatUSD($val) . '</td>';
}
$html .= '<td style="border-bottom: 1px solid #e2e8f0; font-weight: 700;">' . formatUSD($tot_pres) . '</td>
            </tr>
            <tr>
                <td style="border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; font-weight: 700; text-align: left; color: #38a169;">Ejecutado Real (USD)</td>';
$tot_eje = 0;
for ($i = 0; $i < 12; $i++) {
    $val = isset($mensual['ejecutado'][$i]) ? $mensual['ejecutado'][$i] : 0;
    $tot_eje += $val;
    $html .= '<td style="border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; color: #38a169;">' . formatUSD($val) . '</td>';
}
$html .= '<td style="border-bottom: 1px solid #e2e8f0; font-weight: 700; color: #38a169;">' . formatUSD($tot_eje) . '</td>
            </tr>
            <tr>
                <td style="border-right: 1px solid #e2e8f0; font-weight: 700; text-align: left;">Produccion (TON)</td>';
$tot_prod = 0;
for ($i = 0; $i < 12; $i++) {
    $val = isset($mensual['produccion'][$i]) ? $mensual['produccion'][$i] : 0;
    $tot_prod += $val;
    $html .= '<td style="border-right: 1px solid #e2e8f0;">' . formatTon($val) . '</td>';
}
$html .= '<td style="font-weight: 700;">' . formatTon($tot_prod) . '</td>
            </tr>
        </tbody>
    </table>

</body>
</html>';

if ($tipo_export === 'excel') {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=Dashboard_Presupuesto_" . date('Ymd_His') . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo $html;
    exit();
} elseif ($tipo_export === 'pdf') {
    header("Content-Type: text/html; charset=utf-8");
    echo $html;
    echo '<script>window.print();</script>';
    exit();
}
