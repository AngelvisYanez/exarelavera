<?php
/**
 * ppto_param_contable_export.php
 * Exporta Mapa Contable Presupuestario a Excel (HTML compatible Excel).
 */

if (!ob_get_level()) {
    @ob_start();
}
@ini_set('display_errors', '0');

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../contabilidad/LOGICA/con_log_balances2.php');
require_once(__DIR__ . '/../LOGICA/ppto_schema_logica.php');
require_once(__DIR__ . '/../LOGICA/ppto_format_helpers.php');
require_once(__DIR__ . '/../LOGICA/ppto_param_contable_logica.php');

if (!isset($Ses_Dat_Dis) && isset($_SESSION['Ses_Dat_Dis'])) {
    $Ses_Dat_Dis = $_SESSION['Ses_Dat_Dis'];
}
if (!isset($Ses_Emp_Cod) && isset($_SESSION['Ses_Emp_Cod'])) {
    $Ses_Emp_Cod = $_SESSION['Ses_Emp_Cod'];
}

$Emp_Cod = isset($_REQUEST['Emp_Cod']) ? (int)$_REQUEST['Emp_Cod'] : (isset($_REQUEST['emp_cod']) ? (int)$_REQUEST['emp_cod'] : (int)$Ses_Emp_Cod);
if ($Emp_Cod <= 0) {
    $Emp_Cod = (int)$Ses_Emp_Cod;
}
$anio = isset($_REQUEST['anio']) ? (int)$_REQUEST['anio'] : (int)date('Y');
$filtro = isset($_REQUEST['filtro']) ? $_REQUEST['filtro'] : 'todos';
$con_mov = !isset($_REQUEST['con_movimientos']) || $_REQUEST['con_movimientos'] !== '0';

$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
$mysqli = $obBD_conexion->conexion;
if (!$mysqli) {
    die('Sin conexion BD.');
}
$mysqli->set_charset('utf8mb4');
ppto_param_contable_boot($mysqli);

$plan = ppto_param_contable_plan_empresa($mysqli, $Emp_Cod, $anio);
$Pla_Cod = $plan ? (int)$plan['Pla_Cod'] : 0;
$pec_cod = $plan ? (int)$plan['pec_cod'] : 0;
$mapa = ppto_param_contable_mapa($mysqli, $Emp_Cod, $Pla_Cod, $pec_cod, $con_mov, $filtro);

$fname = 'mapa_contable_' . $Emp_Cod . '_' . $anio . '_' . date('Ymd_His') . '.xls';
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
<h3>Mapa Contable Presupuestario</h3>
<p>
    Empresa: <?php echo (int)$Emp_Cod; ?> |
    Anio: <?php echo (int)$anio; ?> |
    Plan: <?php echo $Pla_Cod; ?> |
    Filtro: <?php echo htmlspecialchars($filtro); ?> |
    Generado: <?php echo date('Y-m-d H:i'); ?>
</p>
<table border="1" cellpadding="4" cellspacing="0">
    <thead>
        <tr style="background:#ebf8ff;">
            <th>Cod. rubro</th>
            <th>Rubro</th>
            <th>Estado param.</th>
            <th>Cod. cuenta</th>
            <th>Cuenta</th>
            <th>Naturaleza</th>
            <th>Est. cuenta</th>
            <th>Mov. acumulado</th>
            <th>Ultimo movimiento</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($mapa['rows'])): ?>
        <tr><td colspan="9">Sin datos</td></tr>
    <?php else: ?>
        <?php foreach ($mapa['rows'] as $rubro): ?>
            <?php if (empty($rubro['cuentas'])): ?>
                <tr>
                    <td><?php echo htmlspecialchars($rubro['codigo']); ?></td>
                    <td><?php echo htmlspecialchars($rubro['descripcion']); ?></td>
                    <td>Pendiente</td>
                    <td></td><td></td><td></td><td></td><td></td><td></td>
                </tr>
            <?php else: ?>
                <?php foreach ($rubro['cuentas'] as $cta): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($rubro['codigo']); ?></td>
                        <td><?php echo htmlspecialchars($rubro['descripcion']); ?></td>
                        <td>Parametrizado</td>
                        <td><?php echo htmlspecialchars($cta['codigo']); ?></td>
                        <td><?php echo htmlspecialchars($cta['descripcion']); ?></td>
                        <td><?php echo htmlspecialchars($cta['naturaleza']); ?></td>
                        <td><?php echo htmlspecialchars($cta['pld_estado']); ?></td>
                        <td style="text-align:right;"><?php
                            echo $cta['acumulado'] === null ? '' : ppto_fmt_money($cta['acumulado']);
                        ?></td>
                        <td><?php echo $cta['ultimo_mov'] ? htmlspecialchars($cta['ultimo_mov']) : ''; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
</body>
</html>
<?php
exit;
