<?php
/**
 * ppto_reajustes_front.php - Reajustes presupuestarios (incremento, reduccion, transferencia).
 * ?embed=1 = vista embebida para modal del Dashboard (sin chrome de pagina).
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../contabilidad/LOGICA/con_log_balances2.php');
require_once('../LOGICA/ppto_schema_logica.php');
require_once('../LOGICA/ppto_reajustes_logica.php');
require_once('../LOGICA/ppto_format_helpers.php');
require_once('../LOGICA/ppto_partidas_logica.php');

if (!isset($Ses_Dat_Dis) && isset($_SESSION['Ses_Dat_Dis'])) {
    $Ses_Dat_Dis = $_SESSION['Ses_Dat_Dis'];
}
if (!isset($Ses_Usu_Cod) && isset($_SESSION['Ses_Usu_Cod'])) {
    $Ses_Usu_Cod = $_SESSION['Ses_Usu_Cod'];
}

$obBD = new Class_Log_Conexion_Con($Ses_Dat_Dis);
$mysqli = $obBD->conexion;
ppto_schema_ensure($mysqli);

$emp_filtro = ppto_resolve_emp_id();
$msg = '';
$msg_type = 'info';
$embed = (isset($_REQUEST['embed']) && (string)$_REQUEST['embed'] === '1');

if (isset($_POST['guardar_reajuste'])) {
    $ppe_id = (int)$_POST['ppe_id'];
    $res_ver = $mysqli->query("SELECT Ppe_Cod AS ppe_id FROM pre_presupuesto WHERE Emp_Cod=$emp_filtro AND Ppe_Cod=$ppe_id LIMIT 1");
    if ($res_ver && $res_ver->num_rows) {
        $datos = array(
            'Emp_Cod' => $emp_filtro,
            'Usu_Cod' => (int)$Ses_Usu_Cod,
            'ppe_id' => $ppe_id,
            'rea_tipo' => $_POST['rea_tipo'],
            'rea_mes' => (int)$_POST['rea_mes'],
            'rea_monto' => (float)$_POST['rea_monto'],
            'rea_justificacion' => $_POST['rea_justificacion'],
            'ppa_id_origen' => isset($_POST['ppa_id_origen']) ? $_POST['ppa_id_origen'] : '',
            'ppa_id_destino' => isset($_POST['ppa_id_destino']) ? $_POST['ppa_id_destino'] : '',
        );
        if (ppto_reajuste_registrar($mysqli, $datos)) {
            $msg = 'Reajuste registrado correctamente.';
            $msg_type = 'success';
        } else {
            $msg = 'No se pudo registrar el reajuste. Verifique fondos y partidas.';
            $msg_type = 'danger';
        }
    }
}

$versiones = array();
$partidas = array();
$res_v = $mysqli->query("SELECT Ppe_Cod AS ppe_id, Ppe_Ani AS ppe_anio, Ppe_Ver AS ppe_version, Ppe_Des AS ppe_descripcion FROM pre_presupuesto WHERE Emp_Cod=$emp_filtro ORDER BY Ppe_Ani DESC, Ppe_Ver DESC");
if ($res_v) {
    while ($r = $res_v->fetch_assoc()) {
        $versiones[] = $r;
    }
}
$partidas = ppto_partidas_listar($mysqli, array(
    'Emp_Cod' => $emp_filtro,
    'solo_activas' => true,
    'clase' => 'D'
));
$reajustes = array();
$res_r = $mysqli->query("SELECT r.*, po.Ppa_Cla AS cod_orig, pd.Ppa_Cla AS cod_dest
    FROM pre_reajustes r
    LEFT JOIN pre_partidas po ON r.Ppa_Cod_Origen = po.Ppa_Cod
    LEFT JOIN pre_partidas pd ON r.Ppa_Cod_Destino = pd.Ppa_Cod
    WHERE r.Emp_Cod=$emp_filtro ORDER BY r.Rea_FecReg DESC LIMIT 50");
if ($res_r) {
    while ($r = $res_r->fetch_assoc()) {
        $reajustes[] = $r;
    }
}

$ppe_prefill = isset($_REQUEST['ppe_id']) ? (int)$_REQUEST['ppe_id'] : 0;
$ppa_dest_prefill = isset($_REQUEST['ppa_id_destino']) ? (int)$_REQUEST['ppa_id_destino'] : 0;
$rea_tipo_prefill = isset($_REQUEST['rea_tipo']) && in_array($_REQUEST['rea_tipo'], array('incremento', 'reduccion', 'transferencia'), true)
    ? $_REQUEST['rea_tipo'] : 'incremento';
$monto_prefill = isset($_REQUEST['rea_monto']) ? (float)$_REQUEST['rea_monto'] : 0.0;
$mes_prefill = isset($_REQUEST['rea_mes']) ? (int)$_REQUEST['rea_mes'] : (int)date('n');
if ($mes_prefill < 1 || $mes_prefill > 12) {
    $mes_prefill = (int)date('n');
}
$origen_alerta = isset($_REQUEST['origen']) ? trim($_REQUEST['origen']) : '';
if ($origen_alerta === 'formalizar') {
    $just_prefill = 'Formalizar: derecho por tonelada real del periodo';
} elseif ($ppa_dest_prefill > 0) {
    $just_prefill = 'Alerta D8: PF supera VA + umbral configurable';
} else {
    $just_prefill = '';
}

$body_class = $embed ? 'exa-ui-fill-root ppto-reaj-embed' : 'exa-ui-fill-root';
?>
<!DOCTYPE html>
<html lang="es" class="exa-ui-fill-root">
<head>
    <meta charset="UTF-8">
    <title>Reajustes Presupuestarios</title>
    <?php require_once(__DIR__ . '/../../contabilidad/FRONT/con_model3_assets.php'); ?>
    <style>
        .ppto-reaj-embed { background: #fff !important; }
        .ppto-reaj-embed .container-fluid { padding: 10px !important; }
        .ppto-reaj-embed h3.text-primary { font-size: 1.15rem; margin-bottom: 6px !important; }
        .ppto-reaj-embed p.text-muted { font-size: 0.8rem; margin-bottom: 12px !important; }
    </style>
</head>
<body class="<?php echo $body_class; ?> bg-light">
<div class="container-fluid p-3">
    <h3 class="text-primary fw-bold mb-1"><i class="bi bi-arrow-left-right me-2"></i>Reajustes y Transferencias Presupuestarias</h3>
    <p class="text-muted small mb-3">Registra incrementos, reducciones y traspasos entre partidas sin alterar el presupuesto inicial nominal de referencia.</p>

    <?php if ($msg): ?>
        <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Nuevo Reajuste</div>
                <div class="card-body">
                    <form method="post" action="ppto_reajustes_front.php">
                        <?php if ($embed): ?>
                            <input type="hidden" name="embed" value="1">
                        <?php endif; ?>
                        <div class="mb-2">
                            <label class="form-label form-label-sm fw-bold">Versión Presupuestaria</label>
                            <select name="ppe_id" class="form-select form-select-sm" required>
                                <?php foreach ($versiones as $v): ?>
                                    <?php $sel_v = ($ppe_prefill > 0 && (int)$v['ppe_id'] === $ppe_prefill) ? 'selected' : ''; ?>
                                    <option value="<?php echo $v['ppe_id']; ?>" <?php echo $sel_v; ?>>V<?php echo $v['ppe_version']; ?> - <?php echo htmlspecialchars($v['ppe_descripcion']); ?> (<?php echo $v['ppe_anio']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label form-label-sm fw-bold">Tipo de Reajuste</label>
                            <select name="rea_tipo" id="rea_tipo" class="form-select form-select-sm" onchange="ppto_toggle_orig();">
                                <option value="incremento" <?php echo $rea_tipo_prefill === 'incremento' ? 'selected' : ''; ?>>Incremento (+)</option>
                                <option value="reduccion" <?php echo $rea_tipo_prefill === 'reduccion' ? 'selected' : ''; ?>>Reducción (-)</option>
                                <option value="transferencia" <?php echo $rea_tipo_prefill === 'transferencia' ? 'selected' : ''; ?>>Transferencia (Origen -> Destino)</option>
                            </select>
                        </div>
                        <div class="mb-2" id="wrap_origen" style="display:none;">
                            <label class="form-label form-label-sm fw-bold">Partida Origen (Cede Fondos)</label>
                            <select name="ppa_id_origen" class="form-select form-select-sm">
                                <option value="">-- Seleccione origen --</option>
                                <?php foreach ($partidas as $p): ?>
                                    <option value="<?php echo $p['ppa_id']; ?>"><?php echo htmlspecialchars($p['ppa_codigo_clasificacion'] . ' - ' . $p['ppa_descripcion']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label form-label-sm fw-bold">Partida Destino (Afectada)</label>
                            <select name="ppa_id_destino" class="form-select form-select-sm" required>
                                <option value="">-- Seleccione destino --</option>
                                <?php foreach ($partidas as $p): ?>
                                    <?php $sel_d = ($ppa_dest_prefill > 0 && (int)$p['ppa_id'] === $ppa_dest_prefill) ? 'selected' : ''; ?>
                                    <option value="<?php echo $p['ppa_id']; ?>" <?php echo $sel_d; ?>><?php echo htmlspecialchars($p['ppa_codigo_clasificacion'] . ' - ' . $p['ppa_descripcion']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label form-label-sm fw-bold">Mes Afectado</label>
                                <select name="rea_mes" class="form-select form-select-sm">
                                    <?php
                                    $meses = array(1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre');
                                    foreach ($meses as $m_num => $m_nom):
                                        $sel_m = ($m_num === $mes_prefill) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $m_num; ?>" <?php echo $sel_m; ?>><?php echo $m_nom; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label form-label-sm fw-bold">Monto (USD)</label>
                                <input type="number" step="0.01" name="rea_monto" class="form-control form-control-sm" required placeholder="0.00" value="<?php echo ($monto_prefill > 0) ? htmlspecialchars(number_format($monto_prefill, 2, '.', '')) : ''; ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-bold">Justificación / Sustento</label>
                            <textarea name="rea_justificacion" class="form-control form-control-sm" rows="2" required placeholder="Motivo del ajuste..."><?php echo htmlspecialchars($just_prefill); ?></textarea>
                        </div>
                        <button type="submit" name="guardar_reajuste" class="btn btn-primary btn-sm w-100 fw-bold">
                            <i class="bi bi-save me-1"></i> Registrar Movimiento
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Historial de Reajustes</div>
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0" style="font-size: 0.8rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Origen</th>
                                <th>Destino</th>
                                <th class="text-end">Monto</th>
                                <th>Sustento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reajustes)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-3">No hay reajustes registrados.</td></tr>
                            <?php else: ?>
                                <?php foreach ($reajustes as $r): ?>
                                    <tr>
                                        <td><small><?php echo date('d/m/Y H:i', strtotime($r['Rea_FecReg'])); ?></small></td>
                                        <td><span class="badge bg-secondary"><?php echo strtoupper($r['Rea_Tip']); ?></span></td>
                                        <td><code><?php echo htmlspecialchars($r['cod_orig'] ? $r['cod_orig'] : '-'); ?></code></td>
                                        <td><code><?php echo htmlspecialchars($r['cod_dest'] ? $r['cod_dest'] : '-'); ?></code></td>
                                        <td class="text-end fw-bold"><?php echo ppto_fmt_money($r['Rea_Mon']); ?></td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars($r['Rea_Jus']); ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function ppto_toggle_orig() {
    var t = $('#rea_tipo').val();
    if (t === 'transferencia') {
        $('#wrap_origen').show();
    } else {
        $('#wrap_origen').hide();
    }
}
$(document).ready(function() {
    ppto_toggle_orig();
});
</script>
</body>
</html>
