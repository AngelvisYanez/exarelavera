<?php
/**
 * ppto_proyectos_cuadro_export.php
 * Exporta el cuadro presupuestario a Excel (HTML compatible Excel).
 */

if (!ob_get_level()) {
    @ob_start();
}
@ini_set('display_errors', '0');

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../contabilidad/LOGICA/con_log_balances2.php');
require_once(__DIR__ . '/../LOGICA/ppto_schema_logica.php');
require_once(__DIR__ . '/../LOGICA/ppto_format_helpers.php');
require_once(__DIR__ . '/../LOGICA/ppto_proyectos_cuadro_logica.php');

if (!isset($Ses_Dat_Dis) && isset($_SESSION['Ses_Dat_Dis'])) {
    $Ses_Dat_Dis = $_SESSION['Ses_Dat_Dis'];
}

$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
$mysqli = $obBD_conexion->conexion;
if (!$mysqli) {
    die('Sin conexion BD.');
}
$mysqli->set_charset('utf8mb4');
ppto_schema_ensure($mysqli);
ppto_schema_ensure_proyecto_version($mysqli);

$Emp_Cod = ppto_resolve_emp_id();
$proy_id = isset($_GET['proy_id']) ? trim($_GET['proy_id']) : '';
$ppe_id = isset($_GET['ppe_id']) ? (int)$_GET['ppe_id'] : 0;
$cuadro_vista = isset($_GET['cuadro_vista']) ? $_GET['cuadro_vista'] : 'anual';
$cuadro_mes = isset($_GET['cuadro_mes']) ? $_GET['cuadro_mes'] : null;
$anio_precio = isset($_GET['anio_precio']) && $_GET['anio_precio'] !== '' ? (int)$_GET['anio_precio'] : null;
$escenario = isset($_GET['escenario']) ? trim($_GET['escenario']) : 'esperada';
if (!in_array($escenario, array('esperada', 'proyectada', 'real'), true)) {
    $escenario = 'esperada';
}

if ($proy_id === '' || $ppe_id <= 0) {
    die('Seleccione proyecto y version antes de exportar.');
}

$data = ppto_proy_cuadro_cargar($mysqli, $Emp_Cod, $proy_id, $ppe_id, $cuadro_vista, $cuadro_mes, $anio_precio);
$rows = isset($data['rows']) ? $data['rows'] : array();
$grupos_tope = isset($data['grupos_tope']) ? $data['grupos_tope'] : array();
$periodo = isset($data['cuadro_periodo']) ? $data['cuadro_periodo'] : array();
$ingreso = isset($data['escenarios_ingreso']) ? $data['escenarios_ingreso'] : array();
$ton_periodo = isset($data['escenarios_ton_periodo']) ? $data['escenarios_ton_periodo'] : array();
$ton_mes = isset($data['escenarios_ton_mes']) ? $data['escenarios_ton_mes'] : array();
$cfg = isset($data['version_cfg']) ? $data['version_cfg'] : array();

$proy_nombre = $proy_id;
$r_proy = $mysqli->query("SELECT Pro_Nom AS proy_nombre FROM pre_proyectos
    WHERE Pro_Cod='" . $mysqli->real_escape_string($proy_id) . "' AND Emp_Cod=" . (int)$Emp_Cod . " LIMIT 1");
if ($r_proy && ($rp = $r_proy->fetch_assoc())) {
    $proy_nombre = $rp['proy_nombre'] . ' (' . $proy_id . ')';
}

$ver_nombre = 'V' . $ppe_id;
$r_ver = $mysqli->query("SELECT Ppe_Ani AS ppe_anio, Ppe_Ver AS ppe_version FROM pre_presupuesto WHERE Ppe_Cod=" . (int)$ppe_id . " LIMIT 1");
if ($r_ver && ($rv = $r_ver->fetch_assoc())) {
    $ver_nombre = (int)$rv['ppe_anio'] . ' V' . (int)$rv['ppe_version'];
}

$esc_labels = array(
    'esperada' => 'Base PDF (esperada)',
    'proyectada' => 'Proyectada',
    'real' => 'Real (+proyectado)',
);
$esc_key = 'esc_' . $escenario;

function ppto_cuadro_export_num($val, $dec = 2) {
    return number_format((float)$val, (int)$dec, '.', ',');
}

function ppto_cuadro_export_money($val) {
    return ppto_cuadro_export_num($val, 2);
}

function ppto_cuadro_export_rubro_monto($row, $esc_key) {
    if (isset($row[$esc_key])) {
        return round((float)$row[$esc_key], 2);
    }
    return round((float)$row['pdp_presupuesto_anual'], 2);
}

function ppto_cuadro_export_factor_anual($anual, $ton_mes) {
    $ton_mes = (float)$ton_mes;
    if ($ton_mes <= 0.0001) {
        return 0.0;
    }
    return round((float)$anual / ($ton_mes * 12.0), 4);
}

$gastos = array('esperada' => 0.0, 'proyectada' => 0.0, 'real' => 0.0);
foreach ($rows as $r) {
    $gastos['esperada'] += ppto_cuadro_export_rubro_monto($r, 'esc_esperada');
    $gastos['proyectada'] += ppto_cuadro_export_rubro_monto($r, 'esc_proyectada');
    $gastos['real'] += ppto_cuadro_export_rubro_monto($r, 'esc_real');
}

$grupos = array();
foreach ($rows as $r) {
    $gk = isset($r['grupo_cod']) ? trim($r['grupo_cod']) : '';
    if ($gk === '') {
        $cod = isset($r['ppa_codigo_clasificacion']) ? $r['ppa_codigo_clasificacion'] : '00';
        $parts = explode('.', $cod);
        $gk = $parts[0];
    }
    if (!isset($grupos[$gk])) {
        $grupos[$gk] = array(
            'cod' => $gk,
            'nombre' => !empty($r['grupo_descripcion']) ? $r['grupo_descripcion'] : ('Grupo ' . $gk),
            'rows' => array(),
            'total' => 0.0,
        );
    }
    $monto = ppto_cuadro_export_rubro_monto($r, $esc_key);
    $grupos[$gk]['rows'][] = $r;
    $grupos[$gk]['total'] += $monto;
}
ksort($grupos, SORT_STRING);

$periodo_label = isset($periodo['label']) ? $periodo['label'] : $cuadro_vista;
$ton_costo = isset($cfg['ton_costo_mes']) ? (float)$cfg['ton_costo_mes'] : 0.0;
$ton_ingreso_mes = isset($ton_mes['esperada']) ? (float)$ton_mes['esperada'] : 0.0;

$safe_proy = preg_replace('/[^A-Za-z0-9_\-]/', '_', $proy_id);
$fname = 'cuadro_presupuestario_' . $safe_proy . '_' . date('Ymd_His') . '.xls';

while (ob_get_level() > 0) {
    @ob_end_clean();
}
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $fname . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF";
?>
<html>
<head><meta charset="UTF-8" /></head>
<body>
<h3>Cuadro presupuestario</h3>
<p>
    Proyecto: <?php echo htmlspecialchars($proy_nombre); ?> |
    Version: <?php echo htmlspecialchars($ver_nombre); ?> |
    Periodo: <?php echo htmlspecialchars($periodo_label); ?> |
    Escenario detalle: <?php echo htmlspecialchars($esc_labels[$escenario]); ?> |
    Ton ingresos (mes): <?php echo ppto_cuadro_export_num($ton_ingreso_mes, 0); ?> |
    Generado: <?php echo date('Y-m-d H:i'); ?>
</p>

<h4>Ingresos vs gastos</h4>
<table border="1" cellpadding="4" cellspacing="0">
    <thead>
        <tr style="background:#ebf8ff;">
            <th>Concepto</th>
            <th>Base PDF</th>
            <th>Proyectada</th>
            <th>Real</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Ton periodo ingresos</td>
            <td align="right"><?php echo ppto_cuadro_export_num(isset($ton_periodo['esperada']) ? $ton_periodo['esperada'] : 0, 0); ?></td>
            <td align="right"><?php echo ppto_cuadro_export_num(isset($ton_periodo['proyectada']) ? $ton_periodo['proyectada'] : 0, 0); ?></td>
            <td align="right"><?php echo ppto_cuadro_export_num(isset($ton_periodo['real']) ? $ton_periodo['real'] : 0, 0); ?></td>
        </tr>
        <tr>
            <td>Ingresos</td>
            <td align="right"><?php echo ppto_cuadro_export_money(isset($ingreso['esperada']) ? $ingreso['esperada'] : 0); ?></td>
            <td align="right"><?php echo ppto_cuadro_export_money(isset($ingreso['proyectada']) ? $ingreso['proyectada'] : 0); ?></td>
            <td align="right"><?php echo ppto_cuadro_export_money(isset($ingreso['real']) ? $ingreso['real'] : 0); ?></td>
        </tr>
        <tr>
            <td>Gastos presup.</td>
            <td align="right"><?php echo ppto_cuadro_export_money($gastos['esperada']); ?></td>
            <td align="right"><?php echo ppto_cuadro_export_money($gastos['proyectada']); ?></td>
            <td align="right"><?php echo ppto_cuadro_export_money($gastos['real']); ?></td>
        </tr>
        <tr style="font-weight:700; background:#f7fafc;">
            <td>Utilidad / Perdida</td>
            <td align="right"><?php echo ppto_cuadro_export_money((isset($ingreso['esperada']) ? $ingreso['esperada'] : 0) - $gastos['esperada']); ?></td>
            <td align="right"><?php echo ppto_cuadro_export_money((isset($ingreso['proyectada']) ? $ingreso['proyectada'] : 0) - $gastos['proyectada']); ?></td>
            <td align="right"><?php echo ppto_cuadro_export_money((isset($ingreso['real']) ? $ingreso['real'] : 0) - $gastos['real']); ?></td>
        </tr>
    </tbody>
</table>

<br />

<h4>Detalle por grupos y rubros (escenario: <?php echo htmlspecialchars($esc_labels[$escenario]); ?>)</h4>
<table border="1" cellpadding="4" cellspacing="0">
    <thead>
        <tr style="background:#ebf8ff;">
            <th>Grupo</th>
            <th>Partida</th>
            <th>Rubro</th>
            <th>Ton/mes costo</th>
            <th>$/Ton anual</th>
            <th>$/Ton mensual</th>
            <th>Presupuesto periodo</th>
            <th>Presup. mensual equiv.</th>
            <th>Base PDF</th>
            <th>Proyectada</th>
            <th>Real</th>
            <th>% Tope</th>
            <th>Tope anual</th>
            <th>Usado %</th>
        </tr>
    </thead>
    <tbody>
<?php
$total_esc = 0.0;
$total_mes = 0.0;
if (empty($grupos)):
?>
        <tr><td colspan="14">Sin rubros para exportar.</td></tr>
<?php
else:
    foreach ($grupos as $g):
        $tope = isset($grupos_tope[$g['cod']]) ? $grupos_tope[$g['cod']] : null;
        $fac_anual = ppto_cuadro_export_factor_anual($g['total'], $ton_costo > 0 ? $ton_costo : $ton_ingreso_mes);
        $fac_mes = $fac_anual > 0 ? round($fac_anual / 12.0, 6) : 0.0;
        $presup_mes_grupo = ($cuadro_vista === 'anual') ? round($g['total'] / 12.0, 2) : round($g['total'], 2);
        $total_esc += $g['total'];
        $total_mes += $presup_mes_grupo;
?>
        <tr style="background:#f0fff4; font-weight:700;">
            <td><?php echo htmlspecialchars($g['cod'] . ' ' . $g['nombre']); ?></td>
            <td colspan="2"><?php echo count($g['rows']); ?> rubro(s)</td>
            <td align="right"><?php echo $ton_costo > 0 ? ppto_cuadro_export_num($ton_costo, 0) : '-'; ?></td>
            <td align="right"><?php echo $fac_anual > 0 ? ppto_cuadro_export_num($fac_anual, 4) : '-'; ?></td>
            <td align="right"><?php echo $fac_mes > 0 ? ppto_cuadro_export_num($fac_mes, 6) : '-'; ?></td>
            <td align="right"><?php echo ppto_cuadro_export_money($g['total']); ?></td>
            <td align="right"><?php echo ppto_cuadro_export_money($presup_mes_grupo); ?></td>
            <td colspan="3"></td>
            <td align="right"><?php echo $tope ? ppto_cuadro_export_num($tope['tope_pct'], 2) . '%' : '-'; ?></td>
            <td align="right"><?php echo ($tope && $tope['tope_anual'] > 0) ? ppto_cuadro_export_money($tope['tope_anual']) : '-'; ?></td>
            <td align="right"><?php echo ($tope && $tope['tope_anual'] > 0) ? ppto_cuadro_export_num($tope['usado_pct'], 1) . '%' : '-'; ?></td>
        </tr>
<?php
        foreach ($g['rows'] as $r):
            $monto = ppto_cuadro_export_rubro_monto($r, $esc_key);
            $ton_rubro = isset($r['pdp_toneladas_base']) ? (float)$r['pdp_toneladas_base'] : 0.0;
            if ($ton_rubro <= 0) {
                $ton_rubro = $ton_costo;
            }
            $fac_r = isset($r['pdp_factor_anual_tonelada']) ? (float)$r['pdp_factor_anual_tonelada'] : 0.0;
            if ($fac_r <= 0 && $ton_rubro > 0) {
                $fac_r = ppto_cuadro_export_factor_anual($monto, $ton_rubro);
            }
            $fac_r_mes = $fac_r > 0 ? round($fac_r / 12.0, 6) : 0.0;
            $monto_mes = ($cuadro_vista === 'anual') ? round($monto / 12.0, 2) : $monto;
?>
        <tr>
            <td><?php echo htmlspecialchars($g['cod']); ?></td>
            <td><?php echo htmlspecialchars(isset($r['ppa_codigo_clasificacion']) ? $r['ppa_codigo_clasificacion'] : ''); ?></td>
            <td><?php echo htmlspecialchars(isset($r['pdp_rubro']) ? $r['pdp_rubro'] : ''); ?></td>
            <td align="right"><?php echo $ton_rubro > 0 ? ppto_cuadro_export_num($ton_rubro, 0) : '-'; ?></td>
            <td align="right"><?php echo $fac_r > 0 ? ppto_cuadro_export_num($fac_r, 4) : '-'; ?></td>
            <td align="right"><?php echo $fac_r_mes > 0 ? ppto_cuadro_export_num($fac_r_mes, 6) : '-'; ?></td>
            <td align="right"><?php echo ppto_cuadro_export_money($monto); ?></td>
            <td align="right"><?php echo ppto_cuadro_export_money($monto_mes); ?></td>
            <td align="right"><?php echo ppto_cuadro_export_money(ppto_cuadro_export_rubro_monto($r, 'esc_esperada')); ?></td>
            <td align="right"><?php echo ppto_cuadro_export_money(ppto_cuadro_export_rubro_monto($r, 'esc_proyectada')); ?></td>
            <td align="right"><?php echo ppto_cuadro_export_money(ppto_cuadro_export_rubro_monto($r, 'esc_real')); ?></td>
            <td></td><td></td><td></td>
        </tr>
<?php
        endforeach;
    endforeach;
?>
        <tr style="background:#ebf8ff; font-weight:700;">
            <td colspan="6" align="right">TOTAL</td>
            <td align="right"><?php echo ppto_cuadro_export_money($total_esc); ?></td>
            <td align="right"><?php echo ppto_cuadro_export_money($total_mes); ?></td>
            <td align="right"><?php echo ppto_cuadro_export_money($gastos['esperada']); ?></td>
            <td align="right"><?php echo ppto_cuadro_export_money($gastos['proyectada']); ?></td>
            <td align="right"><?php echo ppto_cuadro_export_money($gastos['real']); ?></td>
            <td colspan="3"></td>
        </tr>
<?php endif; ?>
    </tbody>
</table>
</body>
</html>
<?php
exit;
