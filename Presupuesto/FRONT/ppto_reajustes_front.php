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
    $res_ver = $mysqli->query("SELECT ppe_id FROM exa_ppto_cabeceras WHERE emp_id=$emp_filtro AND ppe_id=$ppe_id LIMIT 1");
    if ($res_ver && $res_ver->num_rows) {
        $datos = array(
            'emp_id' => $emp_filtro,
            'usu_id' => (int)$Ses_Usu_Cod,
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
$res_v = $mysqli->query("SELECT ppe_id, ppe_anio, ppe_version, ppe_descripcion FROM exa_ppto_cabeceras WHERE emp_id=$emp_filtro ORDER BY ppe_anio DESC, ppe_version DESC");
if ($res_v) {
    while ($r = $res_v->fetch_assoc()) {
        $versiones[] = $r;
    }
}
$partidas = ppto_partidas_listar($mysqli, array(
    'emp_id' => $emp_filtro,
    'solo_activas' => true,
    'clase' => 'D'
));
$reajustes = array();
$res_r = $mysqli->query("SELECT r.*, po.ppa_codigo_clasificacion AS cod_orig, pd.ppa_codigo_clasificacion AS cod_dest
    FROM exa_ppto_reajustes r
    LEFT JOIN exa_ppto_partidas po ON r.ppa_id_origen = po.ppa_id
    LEFT JOIN exa_ppto_partidas pd ON r.ppa_id_destino = pd.ppa_id
    WHERE r.emp_id=$emp_filtro ORDER BY r.rea_fecha_registro DESC LIMIT 50");
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
    <title>Reajustes Presupuestarios - EXA</title>
    <?php require_once dirname(dirname(__DIR__)) . '/contabilidad/FRONT/con_model3_assets.php'; ?>
    <script src="../VALIDACIONES/ppto_format.js"></script>
    <style>
        .exa-pre-form-panel label { display: block; font-size: 11px; font-weight: 600; margin-bottom: 4px; color: #4a5568; }
        body.ppto-reaj-embed { background: #fff; margin: 0; }
        body.ppto-reaj-embed .panel-main { border: none; box-shadow: none; margin: 0; }
        body.ppto-reaj-embed .exa-header { display: none; }
        body.ppto-reaj-embed .exa-body { padding: 4px 2px 8px; }
        body.ppto-reaj-embed .exa-ui-page-view { padding: 0; }
        body.ppto-reaj-embed .alert { margin: 0 0 8px; padding: 8px 10px; font-size: 12px; }
        body.ppto-reaj-embed .ppto-reaj-grid {
            display: grid;
            grid-template-columns: 1.5fr 0.85fr 0.75fr 0.95fr;
            gap: 8px 12px;
            align-items: end;
        }
        body.ppto-reaj-embed .ppto-reaj-grid .ppto-reaj-field { min-width: 0; }
        body.ppto-reaj-embed .ppto-reaj-grid .ppto-reaj-half { grid-column: span 2; }
        body.ppto-reaj-embed .ppto-reaj-grid .ppto-reaj-full { grid-column: 1 / -1; }
        body.ppto-reaj-embed .ppto-reaj-grid label {
            margin-bottom: 3px;
            font-size: 10px;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: #718096;
        }
        body.ppto-reaj-embed .ppto-reaj-grid .form-control {
            height: 32px;
            font-size: 13px;
            padding: 4px 8px;
        }
        body.ppto-reaj-embed .ppto-reaj-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        body.ppto-reaj-embed .ppto-reaj-hist-wrap {
            margin-top: 10px;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
        body.ppto-reaj-embed .ppto-reaj-hist-wrap summary {
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            color: #4a5568;
            user-select: none;
            outline: none;
        }
        body.ppto-reaj-embed .ppto-reaj-hist {
            max-height: 160px;
            overflow: auto;
            margin-top: 6px;
        }
        body.ppto-reaj-embed .ppto-reaj-hist .table { margin-bottom: 0; font-size: 11px; }
        @media (max-width: 720px) {
            body.ppto-reaj-embed .ppto-reaj-grid {
                grid-template-columns: 1fr 1fr;
            }
            body.ppto-reaj-embed .ppto-reaj-grid .ppto-reaj-half { grid-column: span 1; }
        }
    </style>
</head>
<body class="<?php echo htmlspecialchars($body_class); ?>">
<div class="panel panel-main exa-ui-panel exa-ui-fill-page">
    <?php if (!$embed): ?>
    <div class="panel-heading exa-header exa-header-flex">
        <h3 class="panel-title"><i class="bi bi-arrow-left-right"></i> Reajustes Presupuestarios</h3>
    </div>
    <?php endif; ?>
    <div class="panel-body exa-body">
        <div class="exa-ui-page-view">
        <?php if ($msg): ?><div class="alert alert-<?php echo htmlspecialchars($msg_type); ?>"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
        <form method="POST" class="exa-pre-form-panel" action="">
            <input type="hidden" name="guardar_reajuste" value="1" />
            <input type="hidden" name="emp_cod" value="<?php echo (int)$emp_filtro; ?>" />
            <?php if ($embed): ?>
            <input type="hidden" name="embed" value="1" />
            <input type="hidden" name="origen" value="<?php echo htmlspecialchars($origen_alerta); ?>" />
            <div class="ppto-reaj-grid">
                <div class="ppto-reaj-field">
                    <label>Version</label>
                    <select name="ppe_id" class="form-control input-sm" required>
                        <?php foreach ($versiones as $v): ?>
                        <option value="<?php echo (int)$v['ppe_id']; ?>"<?php echo ($ppe_prefill > 0 && (int)$v['ppe_id'] === $ppe_prefill) ? ' selected="selected"' : ''; ?>><?php echo htmlspecialchars($v['ppe_anio'] . ' V' . $v['ppe_version'] . ' - ' . $v['ppe_descripcion']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ppto-reaj-field">
                    <label>Tipo</label>
                    <select name="rea_tipo" class="form-control input-sm" required>
                        <option value="incremento"<?php echo $rea_tipo_prefill === 'incremento' ? ' selected="selected"' : ''; ?>>Incremento</option>
                        <option value="reduccion"<?php echo $rea_tipo_prefill === 'reduccion' ? ' selected="selected"' : ''; ?>>Reduccion</option>
                        <option value="transferencia"<?php echo $rea_tipo_prefill === 'transferencia' ? ' selected="selected"' : ''; ?>>Transferencia</option>
                    </select>
                </div>
                <div class="ppto-reaj-field">
                    <label>Mes</label>
                    <select name="rea_mes" class="form-control input-sm" required>
                        <?php echo ppto_meses_select_options($mes_prefill, false, ''); ?>
                    </select>
                </div>
                <div class="ppto-reaj-field">
                    <label>Monto</label>
                    <input type="number" step="0.01" name="rea_monto" class="form-control input-sm" required
                        value="<?php echo $monto_prefill > 0 ? htmlspecialchars(number_format($monto_prefill, 2, '.', '')) : ''; ?>" />
                </div>
                <div class="ppto-reaj-field ppto-reaj-half">
                    <label>Partida destino</label>
                    <select name="ppa_id_destino" class="form-control input-sm">
                        <option value="">-- N/A --</option>
                        <?php foreach ($partidas as $p): ?>
                        <option value="<?php echo (int)$p['ppa_id']; ?>"<?php echo ($ppa_dest_prefill > 0 && (int)$p['ppa_id'] === $ppa_dest_prefill) ? ' selected="selected"' : ''; ?>><?php echo htmlspecialchars($p['ppa_codigo_clasificacion'] . ' - ' . $p['ppa_descripcion']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ppto-reaj-field ppto-reaj-half">
                    <label>Partida origen <span style="font-weight:500;text-transform:none;">(solo transf./reduc.)</span></label>
                    <select name="ppa_id_origen" class="form-control input-sm">
                        <option value="">-- N/A --</option>
                        <?php foreach ($partidas as $p): ?>
                        <option value="<?php echo (int)$p['ppa_id']; ?>"><?php echo htmlspecialchars($p['ppa_codigo_clasificacion'] . ' - ' . $p['ppa_descripcion']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ppto-reaj-field ppto-reaj-full">
                    <label>Justificacion</label>
                    <input type="text" name="rea_justificacion" class="form-control input-sm" required value="<?php echo htmlspecialchars($just_prefill); ?>" />
                </div>
            </div>
            <div class="ppto-reaj-actions">
                <button type="submit" class="btn btn-success btn-sm">Registrar reajuste</button>
            </div>
            <?php else: ?>
            <div class="row">
                <div class="col-md-3">
                    <label>Version</label>
                    <select name="ppe_id" class="form-control input-sm" required>
                        <?php foreach ($versiones as $v): ?>
                        <option value="<?php echo (int)$v['ppe_id']; ?>"<?php echo ($ppe_prefill > 0 && (int)$v['ppe_id'] === $ppe_prefill) ? ' selected="selected"' : ''; ?>><?php echo htmlspecialchars($v['ppe_anio'] . ' V' . $v['ppe_version'] . ' - ' . $v['ppe_descripcion']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Tipo</label>
                    <select name="rea_tipo" class="form-control input-sm" required>
                        <option value="incremento"<?php echo $rea_tipo_prefill === 'incremento' ? ' selected="selected"' : ''; ?>>Incremento</option>
                        <option value="reduccion"<?php echo $rea_tipo_prefill === 'reduccion' ? ' selected="selected"' : ''; ?>>Reduccion</option>
                        <option value="transferencia"<?php echo $rea_tipo_prefill === 'transferencia' ? ' selected="selected"' : ''; ?>>Transferencia</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Mes</label>
                    <select name="rea_mes" class="form-control input-sm" required>
                        <?php echo ppto_meses_select_options($mes_prefill, false, ''); ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Monto</label>
                    <input type="number" step="0.01" name="rea_monto" class="form-control input-sm" required
                        value="<?php echo $monto_prefill > 0 ? htmlspecialchars(number_format($monto_prefill, 2, '.', '')) : ''; ?>" />
                </div>
            </div>
            <div class="row" style="margin-top:10px;">
                <div class="col-md-4">
                    <label>Partida origen (transferencia/reduccion)</label>
                    <select name="ppa_id_origen" class="form-control input-sm">
                        <option value="">-- N/A --</option>
                        <?php foreach ($partidas as $p): ?>
                        <option value="<?php echo (int)$p['ppa_id']; ?>"><?php echo htmlspecialchars($p['ppa_codigo_clasificacion'] . ' - ' . $p['ppa_descripcion']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Partida destino (incremento/transferencia)</label>
                    <select name="ppa_id_destino" class="form-control input-sm">
                        <option value="">-- N/A --</option>
                        <?php foreach ($partidas as $p): ?>
                        <option value="<?php echo (int)$p['ppa_id']; ?>"<?php echo ($ppa_dest_prefill > 0 && (int)$p['ppa_id'] === $ppa_dest_prefill) ? ' selected="selected"' : ''; ?>><?php echo htmlspecialchars($p['ppa_codigo_clasificacion'] . ' - ' . $p['ppa_descripcion']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4"><label>Justificacion</label><input type="text" name="rea_justificacion" class="form-control input-sm" required value="<?php echo htmlspecialchars($just_prefill); ?>" /></div>
            </div>
            <div style="margin-top:15px;"><button type="submit" class="btn btn-success btn-sm">Registrar reajuste</button></div>
            <?php endif; ?>
        </form>
        <?php if ($embed): ?>
        <details class="ppto-reaj-hist-wrap">
            <summary>Historial reciente de reajustes</summary>
            <div class="exa-adq-table-wrap ppto-reaj-hist">
        <?php else: ?>
        <hr/>
        <div class="exa-adq-table-wrap ppto-reaj-hist">
        <?php endif; ?>
        <table class="table table-bordered exa-adq-table">
            <thead><tr><th>Fecha</th><th>Tipo</th><th>Mes</th><th>Origen</th><th>Destino</th><th>Monto</th><th>Justificacion</th></tr></thead>
            <tbody>
            <?php foreach ($reajustes as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['rea_fecha_registro']); ?></td>
                    <td><?php echo htmlspecialchars($r['rea_tipo']); ?></td>
                    <td><?php echo htmlspecialchars(ppto_nombre_mes($r['rea_mes'])); ?></td>
                    <td><?php echo htmlspecialchars(!empty($r['cod_orig']) ? $r['cod_orig'] : '-'); ?></td>
                    <td><?php echo htmlspecialchars(!empty($r['cod_dest']) ? $r['cod_dest'] : '-'); ?></td>
                    <td class="text-right"><?php echo ppto_fmt_money($r['rea_monto']); ?></td>
                    <td><?php echo htmlspecialchars($r['rea_justificacion']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php if ($embed): ?></details><?php endif; ?>
        </div>
    </div>
</div>
<?php if ($embed && $msg_type === 'success'): ?>
<script>
if (window.parent && window.parent !== window) {
    try {
        window.parent.postMessage({ type: 'ppto_reajuste_ok' }, window.location.origin);
    } catch (e) {}
}
</script>
<?php endif; ?>
</body>
</html>
