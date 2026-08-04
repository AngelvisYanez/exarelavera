<?php
/**
 * ppto_consulta_front.php
 * Interfaz de Consulta de Presupuestos y Ejecución (EXA PPTO).
 * Permite a los usuarios consultar métricas, semáforos y alertas del presupuesto.
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../contabilidad/LOGICA/con_log_balances2.php');

if (!isset($Ses_Dat_Dis) && isset($_SESSION['Ses_Dat_Dis'])) {
    $Ses_Dat_Dis = $_SESSION['Ses_Dat_Dis'];
}
if (!isset($Ses_Emp_Cod) && isset($_SESSION['Ses_Emp_Cod'])) {
    $Ses_Emp_Cod = $_SESSION['Ses_Emp_Cod'];
}
if (!isset($Ses_Usu_Cod) && isset($_SESSION['Ses_Usu_Cod'])) {
    $Ses_Usu_Cod = $_SESSION['Ses_Usu_Cod'];
}

$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
$mysqli_conn = $obBD_conexion->conexion;
if ($mysqli_conn) {
    $mysqli_conn->set_charset('utf8mb4');
}

include_once(__DIR__ . '/../LOGICA/ppto_persistencia_logica.php');
require_once(__DIR__ . '/../LOGICA/ppto_format_helpers.php');
require_once(__DIR__ . '/../LOGICA/ppto_partidas_logica.php');

// Procesar AJAX: Desglose detallado de documentos de ejecución de una partida
if (isset($_GET['ajax_partida_detalle'])) {
    $ppe_cod = (int)$_GET['ppe_cod'];
    $ppa_cod = (int)$_GET['ppa_cod'];
    $mes = isset($_GET['mes']) ? (int)$_GET['mes'] : null;
    $p = array('ppe_id' => $ppe_cod, 'ppa_id' => $ppa_cod);
    if ($mes !== null) {
        $p['mes'] = $mes;
    }
    $docs = ppto_persistencia_consultar($mysqli_conn, 12, $p);
    header('Content-Type: application/json; charset=utf-8');
    echo ppto_json_encode_safe($docs);
    exit();
}

// Procesar: Marcar alerta de presupuesto como leída
if (isset($_GET['marcar_leida'])) {
    $pal_cod = (int)$_GET['marcar_leida'];
    $mysqli_conn->query("UPDATE pre_alertas SET Pal_Lei = 'L' WHERE Pal_Cod = $pal_cod OR Pal_Cod = $pal_cod");
    header("Location: ppto_consulta_front.php?tab=3");
    exit();
}

$emp_filtro = isset($_REQUEST['emp_cod']) ? (int)$_REQUEST['emp_cod'] : (int)$Ses_Emp_Cod;
$ani_filtro = isset($_REQUEST['ani']) ? (int)$_REQUEST['ani'] : (int)date('Y');
$mes_acumulado = isset($_REQUEST['mes']) ? (int)$_REQUEST['mes'] : (int)date('n');
$mes_acumulado = max(1, min(12, $mes_acumulado));
$vista_periodo = isset($_REQUEST['vista']) ? strtolower(trim((string)$_REQUEST['vista'])) : 'acumulado';
if (!in_array($vista_periodo, array('anual', 'acumulado', 'mes'), true)) {
    $vista_periodo = 'acumulado';
}
if ($vista_periodo === 'anual') {
    $mes_consulta = 12;
} else {
    $mes_consulta = $mes_acumulado;
}
$active_tab = isset($_GET['tab']) ? (int)$_GET['tab'] : 1;

$res_empresas = $mysqli_conn->query("SELECT Emp_Cod, Emp_Des FROM empresas WHERE Emp_Est = 'A' OR Emp_Est = 'S' ORDER BY Emp_Des");
$empresas = array();
if ($res_empresas) {
    while ($row = $res_empresas->fetch_assoc()) {
        $empresas[] = $row;
    }
}
if (empty($empresas)) {
    $empresas[] = array('Emp_Cod' => $Ses_Emp_Cod, 'Emp_Des' => isset($_SESSION['Ses_Emp_Nom']) ? $_SESSION['Ses_Emp_Nom'] : 'Empresa Actual');
}

$res_anios = $mysqli_conn->query("SELECT DISTINCT Ppe_Ani AS ppe_anio FROM pre_presupuesto WHERE Emp_Cod = $emp_filtro ORDER BY Ppe_Ani DESC");
$anios = array();
if ($res_anios) {
    while ($row = $res_anios->fetch_assoc()) {
        $anios[] = $row['ppe_anio'];
    }
}
if (empty($anios)) {
    $anios[] = date('Y');
}
$anio_actual = (int)date('Y');
$anios_filtro = array();
for ($y = $anio_actual - 3; $y <= $anio_actual + 5; $y++) {
    $anios_filtro[$y] = $y;
}
foreach ($anios as $a_db) {
    $anios_filtro[(int)$a_db] = (int)$a_db;
}
krsort($anios_filtro, SORT_NUMERIC);
$anios_filtro = array_values($anios_filtro);

$res_vers = $mysqli_conn->query("SELECT Ppe_Ver AS ppe_version, Ppe_Des AS ppe_descripcion, Ppe_Est AS ppe_estado, Ppe_Cod AS ppe_id FROM pre_presupuesto WHERE Emp_Cod = $emp_filtro AND Ppe_Ani = $ani_filtro ORDER BY Ppe_Ver DESC");
$versiones = array();
if ($res_vers) {
    while ($row = $res_vers->fetch_assoc()) {
        $versiones[] = $row;
    }
}

$ver_filtro = isset($_REQUEST['ver']) && $_REQUEST['ver'] !== '' ? (int)$_REQUEST['ver'] : null;
$ppe_cod_activa = null;
if (!empty($versiones)) {
    if ($ver_filtro !== null) {
        foreach ($versiones as $v) {
            if ((int)$v['ppe_version'] === $ver_filtro) {
                $ppe_cod_activa = (int)$v['ppe_id'];
                break;
            }
        }
    }
    if ($ppe_cod_activa === null) {
        foreach ($versiones as $v) {
            if ($v['ppe_estado'] === 'A') {
                $ppe_cod_activa = (int)$v['ppe_id'];
                $ver_filtro = (int)$v['ppe_version'];
                break;
            }
        }
    }
    if ($ppe_cod_activa === null) {
        $ppe_cod_activa = (int)$versiones[0]['ppe_id'];
        $ver_filtro = (int)$versiones[0]['ppe_version'];
    }
}

$partidas_matriz = array();
if ($ppe_cod_activa) {
    $partidas_matriz = ppto_persistencia_consultar($mysqli_conn, 8, array(
        'Emp_Cod'   => $emp_filtro,
        'ppe_anio'  => $ani_filtro,
        'pej_mes'   => $mes_consulta,
        'pej_vista' => $vista_periodo,
        'ppe_id'    => $ppe_cod_activa
    ));
}

$alertas = array();
if ($ppe_cod_activa) {
    $alertas = ppto_persistencia_consultar($mysqli_conn, 9, array('Emp_Cod' => $emp_filtro, 'ppe_id' => $ppe_cod_activa));
}

$metricas_admin = array();
if ($ppe_cod_activa) {
    $metricas_admin = ppto_admin_metricas_consolidado($mysqli_conn, $emp_filtro, $ppe_cod_activa, $mes_consulta, $vista_periodo);
}

$meses_nombres = array(
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
);
?>
<!DOCTYPE html>
<html lang="es" class="exa-ui-fill-root">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Presupuesto - EXA</title>
    <?php require_once(__DIR__ . '/../../contabilidad/FRONT/con_model3_assets.php'); ?>
    <script src="../VALIDACIONES/ppto_format.js"></script>
    <style>
        .badge-partida-G { background-color: #e2e8f0; color: #2d3748; font-weight: 700; }
        .badge-partida-D { background-color: #ebf8ff; color: #2b6cb0; font-weight: 600; }
        .row-partida-G { background-color: #f7fafc; font-weight: 700; }
        .nav-tabs .nav-link.active { font-weight: 700; border-bottom: 3px solid #3182ce; }
    </style>
</head>
<body class="exa-ui-body bg-light">

    <div class="container-fluid p-3">
        <!-- TITULO Y CABECERA -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="m-0 text-primary font-weight-bold">
                    <i class="bi bi-search me-2"></i>
                    Consulta y Balance Presupuestario
                </h4>
                <small class="text-muted">Informe consolidado de ejecución por empresa, partida y estado de alertas</small>
            </div>
            <div>
                <button class="btn btn-outline-secondary btn-sm" onclick="window.print();">
                    <i class="bi bi-printer me-1"></i> Imprimir
                </button>
            </div>
        </div>

        <!-- FILTROS PRINCIPALES -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-3 bg-white rounded">
                <form method="get" action="ppto_consulta_front.php" class="row g-2 align-items-end">
                    <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                    
                    <div class="col-md-3">
                        <label class="form-label form-label-sm fw-bold mb-1">Empresa</label>
                        <select name="emp_cod" class="form-select form-select-sm" onchange="this.form.submit();">
                            <?php foreach ($empresas as $emp): ?>
                                <option value="<?php echo $emp['Emp_Cod']; ?>" <?php echo ($emp['Emp_Cod'] == $emp_filtro) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($emp['Emp_Des']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label form-label-sm fw-bold mb-1">Año Fiscal</label>
                        <select name="ani" class="form-select form-select-sm" onchange="this.form.submit();">
                            <?php foreach ($anios_filtro as $a): ?>
                                <option value="<?php echo $a; ?>" <?php echo ($a == $ani_filtro) ? 'selected' : ''; ?>>
                                    <?php echo $a; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label form-label-sm fw-bold mb-1">Versión de Ppto</label>
                        <select name="ver" class="form-select form-select-sm" onchange="this.form.submit();">
                            <?php if (empty($versiones)): ?>
                                <option value="">-- Sin Versiones --</option>
                            <?php else: ?>
                                <?php foreach ($versiones as $v): ?>
                                    <option value="<?php echo $v['ppe_version']; ?>" <?php echo ($v['ppe_version'] == $ver_filtro) ? 'selected' : ''; ?>>
                                        V<?php echo $v['ppe_version']; ?> - <?php echo htmlspecialchars($v['ppe_descripcion']); ?> <?php echo ($v['ppe_estado'] === 'A') ? '(Activa)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label form-label-sm fw-bold mb-1">Vista de Periodo</label>
                        <select name="vista" id="vista_select" class="form-select form-select-sm" onchange="ppto_toggle_vista_mes(); this.form.submit();">
                            <option value="acumulado" <?php echo ($vista_periodo === 'acumulado') ? 'selected' : ''; ?>>Acumulado a mes</option>
                            <option value="mes" <?php echo ($vista_periodo === 'mes') ? 'selected' : ''; ?>>Solo el mes elegido</option>
                            <option value="anual" <?php echo ($vista_periodo === 'anual') ? 'selected' : ''; ?>>Anual completo</option>
                        </select>
                    </div>

                    <div class="col-md-2" id="col_select_mes" style="<?php echo ($vista_periodo === 'anual') ? 'display:none;' : ''; ?>">
                        <label class="form-label form-label-sm fw-bold mb-1">Mes de Corte</label>
                        <select name="mes" class="form-select form-select-sm" onchange="this.form.submit();">
                            <?php foreach ($meses_nombres as $m_num => $m_nom): ?>
                                <option value="<?php echo $m_num; ?>" <?php echo ($m_num == $mes_consulta) ? 'selected' : ''; ?>>
                                    <?php echo $m_nom; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-1">
                        <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold">
                            <i class="bi bi-filter"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TABS DE NAVEGACION -->
        <ul class="nav nav-tabs mb-3 border-bottom-0" id="tabs_consulta" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_tab === 1) ? 'active' : ''; ?>" href="ppto_consulta_front.php?tab=1&emp_cod=<?php echo $emp_filtro; ?>&ani=<?php echo $ani_filtro; ?>&ver=<?php echo $ver_filtro; ?>&mes=<?php echo $mes_consulta; ?>&vista=<?php echo $vista_periodo; ?>">
                    <i class="bi bi-table me-1"></i> Balanza por Partidas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_tab === 2) ? 'active' : ''; ?>" href="ppto_consulta_front.php?tab=2&emp_cod=<?php echo $emp_filtro; ?>&ani=<?php echo $ani_filtro; ?>&ver=<?php echo $ver_filtro; ?>&mes=<?php echo $mes_consulta; ?>&vista=<?php echo $vista_periodo; ?>">
                    <i class="bi bi-pie-chart me-1"></i> Resumen por Proyectos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_tab === 3) ? 'active' : ''; ?>" href="ppto_consulta_front.php?tab=3&emp_cod=<?php echo $emp_filtro; ?>&ani=<?php echo $ani_filtro; ?>&ver=<?php echo $ver_filtro; ?>&mes=<?php echo $mes_consulta; ?>&vista=<?php echo $vista_periodo; ?>">
                    <i class="bi bi-bell me-1"></i> Alertas (<?php echo count($alertas); ?>)
                </a>
            </li>
        </ul>

        <!-- TAB 1: BALANZA PRESUPUESTARIA POR PARTIDAS -->
        <?php if ($active_tab === 1): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-secondary">
                        Balanza Presupuestaria General (USD) - <?php echo ($vista_periodo === 'anual') ? 'Año ' . $ani_filtro : $meses_nombres[$mes_consulta] . ' ' . $ani_filtro; ?>
                    </h6>
                    <small class="text-muted">Total Partidas Evaluadas: <?php echo count($partidas_matriz); ?></small>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.82rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Descripción de Partida</th>
                                <th class="text-center">Tipo</th>
                                <th class="text-end">Presupuesto</th>
                                <th class="text-end">Ejecutado</th>
                                <th class="text-end">Disponible</th>
                                <th class="text-center">% Ejec</th>
                                <th class="text-center">Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($partidas_matriz)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        No hay información presupuestaria para los criterios seleccionados.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $sum_pre = 0; $sum_eje = 0; $sum_disp = 0;
                                foreach ($partidas_matriz as $pm): 
                                    $clase = isset($pm['ppa_clase']) ? $pm['ppa_clase'] : 'D';
                                    $is_padre = ($clase === 'G');
                                    $bg_class = $is_padre ? 'row-partida-G' : '';
                                    
                                    if (!$is_padre) {
                                        $sum_pre += (float)$pm['Presupuestado'];
                                        $sum_eje += (float)$pm['Ejecutado'];
                                        $sum_disp += (float)$pm['Disponible'];
                                    }
                                    
                                    $pct = (float)$pm['Pct_Ejecutado'];
                                    $badge_class = ($pct >= 100) ? 'bg-danger' : (($pct >= 80) ? 'bg-warning text-dark' : 'bg-success');
                                ?>
                                    <tr class="<?php echo $bg_class; ?>">
                                        <td><code><?php echo htmlspecialchars($pm['ppa_codigo_clasificacion']); ?></code></td>
                                        <td><?php echo htmlspecialchars($pm['ppa_descripcion']); ?></td>
                                        <td class="text-center">
                                            <span class="badge <?php echo $is_padre ? 'badge-partida-G' : 'badge-partida-D'; ?>">
                                                <?php echo $is_padre ? 'Grupo' : 'Detalle'; ?>
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold"><?php echo ppto_fmt_money($pm['Presupuestado']); ?></td>
                                        <td class="text-end text-success fw-bold"><?php echo ppto_fmt_money($pm['Ejecutado']); ?></td>
                                        <td class="text-end fw-bold <?php echo ($pm['Disponible'] < 0) ? 'text-danger' : ''; ?>">
                                            <?php echo ppto_fmt_money($pm['Disponible']); ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo ppto_fmt_num($pct, 1); ?>%</span>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!$is_padre && $ppe_cod_activa): ?>
                                                <button class="btn btn-xs btn-outline-info p-1 py-0" title="Ver comprobantes imputados" onclick="ppto_ver_documentos_partida(<?php echo $ppe_cod_activa; ?>, <?php echo $pm['ppa_id']; ?>, '<?php echo htmlspecialchars($pm['ppa_codigo_clasificacion'] . ' - ' . $pm['ppa_descripcion']); ?>');">
                                                    <i class="bi bi-receipt"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($partidas_matriz)): ?>
                            <tfoot class="table-light fw-bold" style="font-size: 0.85rem;">
                                <tr>
                                    <td colspan="3">TOTALES CONSOLIDADOS (DETALLE IMPUTABLE)</td>
                                    <td class="text-end text-primary"><?php echo ppto_fmt_money($sum_pre); ?></td>
                                    <td class="text-end text-success"><?php echo ppto_fmt_money($sum_eje); ?></td>
                                    <td class="text-end <?php echo ($sum_disp < 0) ? 'text-danger' : ''; ?>"><?php echo ppto_fmt_money($sum_disp); ?></td>
                                    <td class="text-center">
                                        <?php $pct_tot = ($sum_pre > 0) ? ($sum_eje / $sum_pre) * 100 : 0; ?>
                                        <span class="badge bg-secondary"><?php echo ppto_fmt_num($pct_tot, 1); ?>%</span>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB 2: RESUMEN POR PROYECTOS -->
        <?php if ($active_tab === 2): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-secondary">
                        Distribución del Presupuesto: Plan Estándar vs. Proyectos Activos
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="card border-light bg-light">
                                <div class="card-body">
                                    <h6 class="fw-bold text-dark">Plan Estándar Empresa</h6>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Presupuesto Vigente:</span>
                                        <span class="fw-bold"><?php echo ppto_fmt_money($metricas_admin['estandar_vigente']); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Ejecutado Real:</span>
                                        <span class="fw-bold text-success"><?php echo ppto_fmt_money($metricas_admin['estandar_ejecutado']); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Disponible:</span>
                                        <span class="fw-bold text-primary"><?php echo ppto_fmt_money($metricas_admin['estandar_disponible']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Código Proyecto</th>
                                            <th>Nombre del Proyecto</th>
                                            <th class="text-end">Vigente</th>
                                            <th class="text-end">Ejecutado</th>
                                            <th class="text-end">Disponible</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($metricas_admin['proyectos'])): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-3 text-muted">No existen proyectos con asignación presupuestaria activa.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($metricas_admin['proyectos'] as $pr): ?>
                                                <tr>
                                                    <td><code><?php echo htmlspecialchars($pr['proy_id']); ?></code></td>
                                                    <td><?php echo htmlspecialchars($pr['proy_nombre']); ?></td>
                                                    <td class="text-end fw-bold"><?php echo ppto_fmt_money($pr['vigente']); ?></td>
                                                    <td class="text-end text-success"><?php echo ppto_fmt_money($pr['ejecutado']); ?></td>
                                                    <td class="text-end text-primary"><?php echo ppto_fmt_money($pr['disponible']); ?></td>
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
        <?php endif; ?>

        <!-- TAB 3: ALERTAS Y DESVIOS -->
        <?php if ($active_tab === 3): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-secondary">
                        <i class="bi bi-bell-fill text-warning me-1"></i> Histórico de Alertas Generadas
                    </h6>
                    <small class="text-muted">Superaciones de umbrales del 80% y 100%</small>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.8rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha Alerta</th>
                                <th>Partida Presupuestaria</th>
                                <th>Umbral Rebasado</th>
                                <th>% Consumo Actual</th>
                                <th>Estado</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($alertas)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No hay alertas registradas para la versión activa del presupuesto.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($alertas as $al): 
                                    $umb = (int)$al['Pal_Umb'];
                                    $badge = ($umb >= 100) ? 'bg-danger' : 'bg-warning text-dark';
                                ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($al['Pal_FecReg'])); ?></td>
                                        <td>
                                            <code><?php echo htmlspecialchars($al['ppa_codigo_clasificacion']); ?></code> - 
                                            <?php echo htmlspecialchars($al['ppa_descripcion']); ?>
                                        </td>
                                        <td><span class="badge <?php echo $badge; ?>"><?php echo $umb; ?>% Umbral</span></td>
                                        <td class="fw-bold"><?php echo ppto_fmt_num($al['Pal_PorAct'], 1); ?>%</td>
                                        <td>
                                            <span class="badge <?php echo ($al['Pal_Lei'] === 'N') ? 'bg-primary' : 'bg-secondary'; ?>">
                                                <?php echo ($al['Pal_Lei'] === 'N') ? 'No Leída' : 'Atendida'; ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($al['Pal_Lei'] === 'N'): ?>
                                                <a href="ppto_consulta_front.php?marcar_leida=<?php echo $al['Pal_Cod']; ?>" class="btn btn-xs btn-outline-success p-1 py-0" title="Marcar como leída">
                                                    <i class="bi bi-check-lg"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div> <!-- /container-fluid -->

    <!-- MODAL PARA VER DOCUMENTOS IMPUTADOS A UNA PARTIDA -->
    <div class="modal fade" id="modal_docs_partida" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title font-weight-bold text-primary" id="modal_docs_title">
                        Comprobantes Imputados
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0" style="font-size: 0.78rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo Doc</th>
                                    <th>Código Doc</th>
                                    <th class="text-center">Fase</th>
                                    <th class="text-center">Signo</th>
                                    <th class="text-end">Monto</th>
                                </tr>
                            </thead>
                            <tbody id="tbody_docs_partida">
                                <!-- Inyectado dinámicamente vía AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light p-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function ppto_toggle_vista_mes() {
            var v = $('#vista_select').val();
            if (v === 'anual') {
                $('#col_select_mes').hide();
            } else {
                $('#col_select_mes').show();
            }
        }

        function ppto_ver_documentos_partida(ppe_cod, ppa_cod, nombre_partida) {
            $('#modal_docs_title').html('<i class="bi bi-receipt me-2"></i>' + ppto_escape_html(nombre_partida));
            var $tb = $('#tbody_docs_partida');
            $tb.html('<tr><td colspan="6" class="text-center py-3 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Cargando comprobantes...</td></tr>');

            var modal = new bootstrap.Modal(document.getElementById('modal_docs_partida'));
            modal.show();

            $.ajax({
                url: 'ppto_consulta_front.php',
                type: 'GET',
                data: { ajax_partida_detalle: 1, ppe_cod: ppe_cod, ppa_cod: ppa_cod },
                dataType: 'json',
                success: function(docs) {
                    $tb.empty();
                    if (!docs || docs.length === 0) {
                        $tb.append('<tr><td colspan="6" class="text-center py-3 text-muted">No existen movimientos o comprobantes registrados para esta partida.</td></tr>');
                        return;
                    }

                    var tot = 0;
                    $.each(docs, function(i, d) {
                        var mon = parseFloat(d.Pej_Mon || 0);
                        var sig = d.Pej_Sig || '+';
                        if (sig === '+') tot += mon; else tot -= mon;

                        var fase_badge = (d.Pej_Fase === 'C') ? '<span class="badge bg-secondary">Comprometido</span>' : '<span class="badge bg-success">Ejecutado</span>';

                        $tb.append('<tr>' +
                            '<td>' + ppto_escape_html(d.Pej_Fec || '') + '</td>' +
                            '<td>' + ppto_escape_html(d.Pej_TipDoc || '') + '</td>' +
                            '<td><code>' + ppto_escape_html(d.Pej_DocCod || '') + '</code></td>' +
                            '<td class="text-center">' + fase_badge + '</td>' +
                            '<td class="text-center fw-bold">' + sig + '</td>' +
                            '<td class="text-end fw-bold">' + ppto_fmt_money(mon) + '</td>' +
                            '</tr>');
                    });

                    $tb.append('<tr class="table-light fw-bold"><td colspan="5">TOTAL IMPUTADO NETO</td><td class="text-end text-primary">' + ppto_fmt_money(tot) + '</td></tr>');
                },
                error: function() {
                    $tb.html('<tr><td colspan="6" class="text-center py-3 text-danger">Error al consultar los comprobantes de la partida.</td></tr>');
                }
            });
        }

        function ppto_escape_html(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    </script>
</body>
</html>
