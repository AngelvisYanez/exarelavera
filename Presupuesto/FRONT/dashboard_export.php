<?php
/**
 * dashboard_export.php
 * Exportador analitico para Excel y PDF del Dashboard Presupuestario (EXA PPTO).
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
    $res_ver = $mysqli_conn->query("SELECT ppe_version, ppe_descripcion FROM exa_ppto_cabeceras WHERE ppe_id = " . $filtros['ppe_id']);
    if ($res_ver && $v_row = $res_ver->fetch_assoc()) {
        $version_nombre = "V" . $v_row['ppe_version'] . " (" . $v_row['ppe_descripcion'] . ")";
    }
}

$proyecto_nombre = "Todos los Proyectos";
if ($filtros['proy_id']) {
    $res_pr = $mysqli_conn->query("SELECT proy_nombre FROM exa_ppto_proyectos WHERE proy_id = '" . $mysqli_conn->real_escape_string($filtros['proy_id']) . "' LIMIT 1");
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
            <tr style="font-weight: 700; font-size: 14px;">
                <td style="border-right: 1px solid #cbd5e0; padding: 10px 6px;">' . formatUSD($kpis['presupuesto_inicial']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; padding: 10px 6px;">' . formatUSD($kpis['total_reajustes']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; padding: 10px 6px; color: #2b6cb0;">' . formatUSD($kpis['presupuesto_vigente']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; padding: 10px 6px; color: #718096;">' . formatUSD($kpis['comprometido']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; padding: 10px 6px; color: #38a169;">' . formatUSD($kpis['ejecutado']) . '</td>
                <td style="padding: 10px 6px; color: ' . ($disponible_plan < 0 ? '#e53e3e' : '#2d3748') . ';">' . formatUSD($disponible_plan) . ' (' . number_format($disponible_plan_pct, 2) . '%)</td>
            </tr>
        </tbody>
    </table>
    <table width="100%" cellpadding="8" cellspacing="0" style="border: 1px solid #cbd5e0; margin-bottom: 20px; font-size: 12px; text-align: center;">
        <thead>
            <tr style="background-color: #f7fafc; font-weight: 700; color: #2b6cb0;">
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0;">PPTO FORECAST (GLOBAL)</th>
                <th style="border-bottom: 1px solid #cbd5e0;">DISPONIBLE FORECAST (GLOBAL)</th>
            </tr>
        </thead>
        <tbody>
            <tr style="font-weight: 700; font-size: 14px;">
                <td style="border-right: 1px solid #cbd5e0; padding: 10px 6px;">' . formatUSD($kpis['presupuesto_proyectado']) . '</td>
                <td style="padding: 10px 6px; color: ' . ($disponible_forecast < 0 ? '#e53e3e' : '#2d3748') . ';">' . formatUSD($disponible_forecast) . ' (' . number_format($disponible_forecast_pct, 2) . '%)</td>
            </tr>
        </tbody>
    </table>';
    $html_partidas_thead = '
            <tr style="background-color: #edf2f7; font-weight: 700; color: #2d3748; text-align: left;">
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: left;">CLASIFICACION</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0;">DESCRIPCION DE PARTIDA</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">INICIAL</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">REAJUSTES</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">VIGENTE</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">COMPROMETIDO</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">EJECUTADO</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">DISPONIBLE</th>
                <th style="border-bottom: 1px solid #cbd5e0; text-align: center;">DISP (%)</th>
            </tr>';
    $partidas_colspan = 9;
}

$html = '
<div style="font-family: Arial, sans-serif; color: #2d3748;">
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
        <tr>
            <td>
                <h2 style="margin: 0; color: #1a365d; font-size: 20px;">EXA ERP - INFORME CONTROL DE PRESUPUESTO Y PRODUCCION</h2>
                <div style="font-size: 11px; color: #718096; margin-top: 4px;">Generado el: ' . date('Y-m-d H:i:s') . '</div>
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="6" cellspacing="0" style="background-color: #f7fafc; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 20px; font-size: 12px;">
        <tr>
            <td width="15%" style="font-weight: 700; color: #4a5568;">Empresa ID:</td>
            <td width="35%">' . $filtros['Emp_Cod'] . '</td>
            <td width="15%" style="font-weight: 700; color: #4a5568;">Periodo:</td>
            <td width="35%">' . $periodo_display . '</td>
        </tr>
        <tr>
            <td style="font-weight: 700; color: #4a5568;">Proyecto:</td>
            <td>' . htmlspecialchars($proyecto_nombre) . '</td>
            <td style="font-weight: 700; color: #4a5568;">Version Ppto:</td>
            <td>' . htmlspecialchars($version_nombre) . '</td>
        </tr>' . $html_modo_row . '
    </table>
' . $html_kpi . '

    <h4 style="color: #2c5282; margin-top: 0; margin-bottom: 10px; font-size: 14px; border-bottom: 2px solid #ebf8ff; padding-bottom: 4px;">II. CONTROL DE PRODUCCION FISICA RELACIONADA</h4>
    <table width="100%" cellpadding="8" cellspacing="0" style="border: 1px solid #cbd5e0; margin-bottom: 20px; font-size: 12px; text-align: center;">
        <thead>
            <tr style="background-color: #f7fafc; font-weight: 700; color: #4a5568;">
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0;">ESPERADA PLANIFICADA</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0;">REAL REGISTRADA</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0;">PROYECTADO</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0;">DESV. VS PROYECTADO</th>
                <th style="border-bottom: 1px solid #cbd5e0;">DESVIACION PORCENTUAL</th>
            </tr>
        </thead>
        <tbody>
            <tr style="font-weight: 700; font-size: 13px;">
                <td style="border-right: 1px solid #cbd5e0; padding: 10px 6px;">' . formatTon($kpis['prod_esperada']) . ' ' . $kpis['prod_unidad'] . '</td>
                <td style="border-right: 1px solid #cbd5e0; padding: 10px 6px; color: #3182ce;">' . formatTon($kpis['prod_real']) . ' ' . $kpis['prod_unidad'] . '</td>
                <td style="border-right: 1px solid #cbd5e0; padding: 10px 6px; color: #2c5282;">' . formatTon($kpis['prod_proyectada']) . ' ' . $kpis['prod_unidad'] . '</td>
                <td style="border-right: 1px solid #cbd5e0; padding: 10px 6px; color: ' . ($kpis['prod_var_absoluta'] >= 0 ? '#38a169' : '#e53e3e') . ';">' . ($kpis['prod_var_absoluta'] >= 0 ? '+' : '') . formatTon($kpis['prod_var_absoluta']) . ' ' . $kpis['prod_unidad'] . '</td>
                <td style="padding: 10px 6px; color: ' . ($kpis['prod_var_absoluta'] >= 0 ? '#38a169' : '#e53e3e') . ';">' . ($kpis['prod_var_absoluta'] >= 0 ? '+' : '') . $kpis['prod_var_porcentual'] . '%</td>
            </tr>
        </tbody>
    </table>

    <h4 style="color: #2c5282; margin-top: 0; margin-bottom: 10px; font-size: 14px; border-bottom: 2px solid #ebf8ff; padding-bottom: 4px;">III. DESGLOSE DETALLADO POR PARTIDAS PRESUPUESTARIAS</h4>
    <table width="100%" cellpadding="6" cellspacing="0" style="border: 1px solid #cbd5e0; margin-bottom: 25px; font-size: 11px;">
        <thead>' . $html_partidas_thead . '
        </thead>
        <tbody>';

if (empty($partidas)) {
    $html .= '<tr><td colspan="' . $partidas_colspan . '" style="text-align: center; color: #718096; padding: 20px;">Sin registros presupuestarios vinculados.</td></tr>';
} else {
    foreach ($partidas as $p) {
        $color_disp = $p['disponible'] < 0 ? 'color: #e53e3e; font-weight: 700;' : '';
        if ($modo_ux === 'gerente') {
            $html .= '
            <tr>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0; text-align: left; font-weight: 700;">' . htmlspecialchars($p['codigo']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($p['descripcion']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: 600;">' . formatUSD($p['vigente']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0; text-align: right;">' . formatUSD($p['ejecutado']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0; text-align: right; ' . $color_disp . '">' . formatUSD($p['disponible']) . '</td>
                <td style="border-bottom: 1px solid #e2e8f0; text-align: center; font-weight: 700;">' . $p['disponible_porcentaje'] . '%</td>
            </tr>';
        } else {
            $html .= '
            <tr>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0; text-align: left; font-weight: 700;">' . htmlspecialchars($p['codigo']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($p['descripcion']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0; text-align: right;">' . formatUSD($p['inicial']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0; text-align: right;">' . formatUSD($p['reajustes']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: 600;">' . formatUSD($p['vigente']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0; text-align: right;">' . formatUSD($p['comprometido']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0; text-align: right;">' . formatUSD($p['ejecutado']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0; text-align: right; ' . $color_disp . '">' . formatUSD($p['disponible']) . '</td>
                <td style="border-bottom: 1px solid #e2e8f0; text-align: center; font-weight: 700;">' . $p['disponible_porcentaje'] . '%</td>
            </tr>';
        }
    }
}

$html .= '
        </tbody>
    </table>

    <h4 style="color: #2c5282; margin-top: 0; margin-bottom: 10px; font-size: 14px; border-bottom: 2px solid #ebf8ff; padding-bottom: 4px;">IV. DISTRIBUCION Y EVOLUCION HISTORICA MENSUAL</h4>
    <table width="100%" cellpadding="6" cellspacing="0" style="border: 1px solid #cbd5e0; font-size: 11px;">
        <thead>
            <tr style="background-color: #edf2f7; font-weight: 700; color: #2d3748; text-align: left;">
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: center;">MES</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">INICIAL</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">REAJUSTES</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">VIGENTE</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">COMPROMETIDO</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">EJECUTADO</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">DISPONIBLE</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">ESPERADA (FIS)</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">PROYECTADA (FIS)</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">REAL (FIS)</th>
                <th style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #cbd5e0; text-align: right;">PPTO SEGUN PROY.</th>
                <th style="border-bottom: 1px solid #cbd5e0; text-align: center;">DESV. VS PROY.</th>
            </tr>
        </thead>
        <tbody>';

foreach ($mensual as $m) {
    $dev = isset($m['prod_var_fisica']) ? (float)$m['prod_var_fisica'] : ((float)$m['prod_real'] - (float)$m['prod_proyectada']);
    $dev_text = ($dev >= 0 ? '+' : '') . formatTon($dev);
    $dev_color = $dev >= 0 ? 'color: #38a169;' : 'color: #e53e3e;';
    $ppto_proy = isset($m['ppto_proyectado']) ? (float)$m['ppto_proyectado'] : 0;

    $html .= '
            <tr>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0; text-align: center; font-weight: 700;">' . htmlspecialchars($m['nombre_mes']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0; text-align: right;">' . formatUSD($m['inicial']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0; text-align: right;">' . formatUSD($m['reajustes']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: 600;">' . formatUSD($m['vigente']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0; text-align: right;">' . formatUSD($m['comprometido']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0; text-align: right;">' . formatUSD($m['ejecutado']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: 700;">' . formatUSD($m['disponible']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0; text-align: right; color: #718096;">' . formatTon($m['prod_esperada']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0; text-align: right; color: #2c5282;">' . formatTon($m['prod_proyectada']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0; text-align: right; color: #3182ce; font-weight: 600;">' . formatTon($m['prod_real']) . '</td>
                <td style="border-right: 1px solid #cbd5e0; border-bottom: 1px solid #e2e8f0; text-align: right; color: #2c5282;">' . ($ppto_proy > 0 ? formatUSD($ppto_proy) : '-') . '</td>
                <td style="border-bottom: 1px solid #e2e8f0; text-align: center; font-weight: 700; ' . $dev_color . '">' . $dev_text . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>
</div>';

if ($tipo_export === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="EXA_Reporte_Presupuestario_' . date('Y-m-d_His') . '.xls"');
    echo "\xEF\xBB\xBF";
    echo '<html><head><meta charset="UTF-8"></head><body>';
    echo $html;
    echo '</body></html>';
    exit;
}

ini_set('memory_limit', '128M');
$mpdf_path = __DIR__ . '/../../Librerias/MPDF57/mpdf.php';
if (!file_exists($mpdf_path)) {
    die("La libreria mPDF no esta disponible en la ruta especificada.");
}
include($mpdf_path);
$mpdf = new mPDF('c', 'A4-L', '', '', 10, 10, 10, 10, 6, 6);
$mpdf->SetDisplayMode('fullpage');
$mpdf->WriteHTML('<meta charset="UTF-8">' . $html, 2);
$nombre_pdf = 'EXA_Reporte_Presupuestario_' . date('Y-m-d_His') . '.pdf';
$mpdf->Output($nombre_pdf, 'D');
exit;
