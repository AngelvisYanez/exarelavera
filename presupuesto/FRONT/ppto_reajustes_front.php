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
    $Ppe_Cod = (int)$_POST['Ppe_Cod'];
    $res_ver = $mysqli->query("SELECT Ppe_Cod AS Ppe_Cod FROM pre_presupuesto WHERE Emp_Cod=$emp_filtro AND Ppe_Cod=$Ppe_Cod LIMIT 1");
    if ($res_ver && $res_ver->num_rows) {
        $datos = array(
            'Emp_Cod' => $emp_filtro,
            'Usu_Cod' => (int)$Ses_Usu_Cod,
            'Ppe_Cod' => $Ppe_Cod,
            'Rea_Tipo' => $_POST['Rea_Tipo'],
            'Rea_Mes' => (int)$_POST['Rea_Mes'],
            'Rea_Mon' => (float)$_POST['Rea_Mon'],
            'Rea_Jus' => $_POST['Rea_Jus'],
            'Ppa_Cod_Origen' => isset($_POST['Ppa_Cod_Origen']) ? $_POST['Ppa_Cod_Origen'] : '',
            'Ppa_Cod_Destino' => isset($_POST['Ppa_Cod_Destino']) ? $_POST['Ppa_Cod_Destino'] : '',
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
$res_v = $mysqli->query("SELECT Ppe_Cod AS Ppe_Cod, Ppe_Ani AS Ppe_Ani, Ppe_Ver AS Ppe_Ver, Ppe_Des AS Ppe_Des, Ppe_Est AS Ppe_Est FROM pre_presupuesto WHERE Emp_Cod=$emp_filtro ORDER BY Ppe_Ani DESC, Ppe_Ver DESC");
if ($res_v) {
    while ($r = $res_v->fetch_assoc()) {
        $versiones[] = $r;
    }
}
// Mapa anio -> cabecera activa (sin pedir version al usuario).
$ppe_por_anio = array();
foreach ($versiones as $v) {
    $ani_v = (int)$v['Ppe_Ani'];
    if (!isset($ppe_por_anio[$ani_v])) {
        $ppe_por_anio[$ani_v] = (int)$v['Ppe_Cod'];
    }
    if (isset($v['Ppe_Est']) && $v['Ppe_Est'] === 'A') {
        $ppe_por_anio[$ani_v] = (int)$v['Ppe_Cod'];
    }
}
$anios_reaj = array_keys($ppe_por_anio);
rsort($anios_reaj, SORT_NUMERIC);
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

$ppe_prefill = isset($_REQUEST['Ppe_Cod']) ? (int)$_REQUEST['Ppe_Cod'] : 0;
if ($ppe_prefill <= 0) {
    $act_reaj = ppto_presupuesto_activo($mysqli, $emp_filtro, (int)date('Y'));
    if (!$act_reaj) {
        $act_reaj = ppto_presupuesto_activo($mysqli, $emp_filtro, null);
    }
    if ($act_reaj) {
        $ppe_prefill = (int)$act_reaj['Ppe_Cod'];
    } elseif (!empty($anios_reaj)) {
        $ppe_prefill = (int)$ppe_por_anio[$anios_reaj[0]];
    }
}
$anio_prefill = (int)date('Y');
foreach ($versiones as $v) {
    if ((int)$v['Ppe_Cod'] === $ppe_prefill) {
        $anio_prefill = (int)$v['Ppe_Ani'];
        break;
    }
}
if ($ppe_prefill > 0 && isset($ppe_por_anio[$anio_prefill])) {
    $ppe_prefill = (int)$ppe_por_anio[$anio_prefill];
}
$ppa_dest_prefill = isset($_REQUEST['Ppa_Cod_Destino']) ? (int)$_REQUEST['Ppa_Cod_Destino'] : 0;
$rea_tipo_prefill = isset($_REQUEST['Rea_Tipo']) && in_array($_REQUEST['Rea_Tipo'], array('incremento', 'reduccion', 'transferencia'), true)
    ? $_REQUEST['Rea_Tipo'] : 'incremento';
$monto_prefill = isset($_REQUEST['Rea_Mon']) ? (float)$_REQUEST['Rea_Mon'] : 0.0;
$mes_prefill = isset($_REQUEST['Rea_Mes']) ? (int)$_REQUEST['Rea_Mes'] : (int)date('n');
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
                    <label>A&ntilde;o fiscal</label>
                    <select id="reaj_anio" class="form-control input-sm">
                        <?php foreach ($anios_reaj as $ani_opt): ?>
                        <option value="<?php echo (int)$ani_opt; ?>" data-ppe="<?php echo (int)$ppe_por_anio[$ani_opt]; ?>"<?php echo ((int)$ani_opt === $anio_prefill) ? ' selected="selected"' : ''; ?>><?php echo (int)$ani_opt; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="Ppe_Cod" id="reaj_ppe_cod" value="<?php echo (int)$ppe_prefill; ?>" />
                </div>
                <div class="ppto-reaj-field">
                    <label>Tipo</label>
                    <select name="Rea_Tipo" class="form-control input-sm" required>
                        <option value="incremento"<?php echo $rea_tipo_prefill === 'incremento' ? ' selected="selected"' : ''; ?>>Incremento</option>
                        <option value="reduccion"<?php echo $rea_tipo_prefill === 'reduccion' ? ' selected="selected"' : ''; ?>>Reduccion</option>
                        <option value="transferencia"<?php echo $rea_tipo_prefill === 'transferencia' ? ' selected="selected"' : ''; ?>>Transferencia</option>
                    </select>
                </div>
                <div class="ppto-reaj-field">
                    <label>Mes</label>
                    <select name="Rea_Mes" class="form-control input-sm" required>
                        <?php echo ppto_meses_select_options($mes_prefill, false, ''); ?>
                    </select>
                </div>
                <div class="ppto-reaj-field">
                    <label>Monto</label>
                    <input type="number" step="0.01" name="Rea_Mon" class="form-control input-sm" required
                        value="<?php echo $monto_prefill > 0 ? htmlspecialchars(number_format($monto_prefill, 2, '.', '')) : ''; ?>" />
                </div>
                <div class="ppto-reaj-field ppto-reaj-half">
                    <label>Partida destino</label>
                    <select name="Ppa_Cod_Destino" class="form-control input-sm">
                        <option value="">-- N/A --</option>
                        <?php foreach ($partidas as $p): ?>
                        <option value="<?php echo (int)$p['Ppa_Cod']; ?>"<?php echo ($ppa_dest_prefill > 0 && (int)$p['Ppa_Cod'] === $ppa_dest_prefill) ? ' selected="selected"' : ''; ?>><?php echo htmlspecialchars($p['Ppa_Cla'] . ' - ' . $p['Ppa_Des']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ppto-reaj-field ppto-reaj-half">
                    <label>Partida origen <span style="font-weight:500;text-transform:none;">(solo transf./reduc.)</span></label>
                    <select name="Ppa_Cod_Origen" class="form-control input-sm">
                        <option value="">-- N/A --</option>
                        <?php foreach ($partidas as $p): ?>
                        <option value="<?php echo (int)$p['Ppa_Cod']; ?>"><?php echo htmlspecialchars($p['Ppa_Cla'] . ' - ' . $p['Ppa_Des']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ppto-reaj-field ppto-reaj-full">
                    <label>Justificacion</label>
                    <input type="text" name="Rea_Jus" class="form-control input-sm" required value="<?php echo htmlspecialchars($just_prefill); ?>" />
                </div>
            </div>
            <div class="ppto-reaj-actions">
                <button type="submit" class="btn btn-success btn-sm">Registrar reajuste</button>
            </div>
            <?php else: ?>
            <div class="row">
                <div class="col-md-2">
                    <label>A&ntilde;o fiscal</label>
                    <select id="reaj_anio" class="form-control input-sm">
                        <?php foreach ($anios_reaj as $ani_opt): ?>
                        <option value="<?php echo (int)$ani_opt; ?>" data-ppe="<?php echo (int)$ppe_por_anio[$ani_opt]; ?>"<?php echo ((int)$ani_opt === $anio_prefill) ? ' selected="selected"' : ''; ?>><?php echo (int)$ani_opt; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="Ppe_Cod" id="reaj_ppe_cod" value="<?php echo (int)$ppe_prefill; ?>" />
                </div>
                <div class="col-md-2">
                    <label>Tipo</label>
                    <select name="Rea_Tipo" class="form-control input-sm" required>
                        <option value="incremento"<?php echo $rea_tipo_prefill === 'incremento' ? ' selected="selected"' : ''; ?>>Incremento</option>
                        <option value="reduccion"<?php echo $rea_tipo_prefill === 'reduccion' ? ' selected="selected"' : ''; ?>>Reduccion</option>
                        <option value="transferencia"<?php echo $rea_tipo_prefill === 'transferencia' ? ' selected="selected"' : ''; ?>>Transferencia</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Mes</label>
                    <select name="Rea_Mes" class="form-control input-sm" required>
                        <?php echo ppto_meses_select_options($mes_prefill, false, ''); ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Monto</label>
                    <input type="number" step="0.01" name="Rea_Mon" class="form-control input-sm" required
                        value="<?php echo $monto_prefill > 0 ? htmlspecialchars(number_format($monto_prefill, 2, '.', '')) : ''; ?>" />
                </div>
            </div>
            <div class="row" style="margin-top:10px;">
                <div class="col-md-4">
                    <label>Partida origen (transferencia/reduccion)</label>
                    <select name="Ppa_Cod_Origen" class="form-control input-sm">
                        <option value="">-- N/A --</option>
                        <?php foreach ($partidas as $p): ?>
                        <option value="<?php echo (int)$p['Ppa_Cod']; ?>"><?php echo htmlspecialchars($p['Ppa_Cla'] . ' - ' . $p['Ppa_Des']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Partida destino (incremento/transferencia)</label>
                    <select name="Ppa_Cod_Destino" class="form-control input-sm">
                        <option value="">-- N/A --</option>
                        <?php foreach ($partidas as $p): ?>
                        <option value="<?php echo (int)$p['Ppa_Cod']; ?>"<?php echo ($ppa_dest_prefill > 0 && (int)$p['Ppa_Cod'] === $ppa_dest_prefill) ? ' selected="selected"' : ''; ?>><?php echo htmlspecialchars($p['Ppa_Cla'] . ' - ' . $p['Ppa_Des']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4"><label>Justificacion</label><input type="text" name="Rea_Jus" class="form-control input-sm" required value="<?php echo htmlspecialchars($just_prefill); ?>" /></div>
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
                    <td><?php echo htmlspecialchars($r['Rea_FecReg']); ?></td>
                    <td><?php echo htmlspecialchars($r['Rea_Tipo']); ?></td>
                    <td><?php echo htmlspecialchars(ppto_nombre_mes($r['Rea_Mes'])); ?></td>
                    <td><?php echo htmlspecialchars(!empty($r['cod_orig']) ? $r['cod_orig'] : '-'); ?></td>
                    <td><?php echo htmlspecialchars(!empty($r['cod_dest']) ? $r['cod_dest'] : '-'); ?></td>
                    <td class="text-right"><?php echo ppto_fmt_money($r['Rea_Mon']); ?></td>
                    <td><?php echo htmlspecialchars($r['Rea_Jus']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php if ($embed): ?></details><?php endif; ?>
        </div>
    </div>
</div>
<script>
(function() {
    var anioSel = document.getElementById('reaj_anio');
    var ppeHid = document.getElementById('reaj_ppe_cod');
    if (!anioSel || !ppeHid) return;
    function syncPpe() {
        var opt = anioSel.options[anioSel.selectedIndex];
        if (opt && opt.getAttribute('data-ppe')) {
            ppeHid.value = opt.getAttribute('data-ppe');
        }
    }
    anioSel.addEventListener('change', syncPpe);
    syncPpe();
})();
</script>
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
