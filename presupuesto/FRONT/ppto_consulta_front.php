<?php
/**
 * ppto_consulta_front.php
 * Interfaz de Consulta de Presupuestos y Ejecuci&oacute;n (EXA PPTO).
 * Permite a los usuarios consultar m&eacute;tricas, sem&aacute;foros y alertas del presupuesto.
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
require_once(__DIR__ . '/../LOGICA/ppto_schema_logica.php');
require_once(__DIR__ . '/../LOGICA/ppto_format_helpers.php');
require_once(__DIR__ . '/../LOGICA/ppto_partidas_logica.php');
if ($mysqli_conn) {
    ppto_schema_ensure($mysqli_conn);
}

// Procesar AJAX: Desglose detallado de documentos de ejecuci&oacute;n de una partida
if (isset($_GET['ajax_partida_detalle'])) {
    $Ppe_Cod = (int)$_GET['Ppe_Cod'];
    $Ppa_Cod = (int)$_GET['Ppa_Cod'];
    $mes = isset($_GET['mes']) ? (int)$_GET['mes'] : null;
    $p = array('Ppe_Cod' => $Ppe_Cod, 'Ppa_Cod' => $Ppa_Cod);
    if ($mes !== null) {
        $p['mes'] = $mes;
    }
    $docs = ppto_persistencia_consultar($mysqli_conn, 12, $p);
    header('Content-Type: application/json; charset=utf-8');
    echo ppto_json_encode_safe($docs);
    exit();
}

// Procesar: Marcar alerta de presupuesto como le&iacute;da
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

$res_anios = $mysqli_conn->query("SELECT DISTINCT Ppe_Ani AS Ppe_Ani FROM pre_presupuesto WHERE Emp_Cod = $emp_filtro ORDER BY Ppe_Ani DESC");
$anios = array();
if ($res_anios) {
    while ($row = $res_anios->fetch_assoc()) {
        $anios[] = $row['Ppe_Ani'];
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

$res_vers = $mysqli_conn->query("SELECT Ppe_Ver AS Ppe_Ver, Ppe_Des AS Ppe_Des, Ppe_Est AS Ppe_Est, Ppe_Cod AS Ppe_Cod FROM pre_presupuesto WHERE Emp_Cod = $emp_filtro AND Ppe_Ani = $ani_filtro ORDER BY Ppe_Ver DESC");
$versiones = array();
if ($res_vers) {
    while ($row = $res_vers->fetch_assoc()) {
        $versiones[] = $row;
    }
}

$ver_activa = ppto_presupuesto_pick_activo_from_list($versiones);
$ver_filtro = $ver_activa ? (int)$ver_activa['Ppe_Ver'] : 1;
$ppe_cod_filtro = $ver_activa ? (int)$ver_activa['Ppe_Cod'] : 0;

$reporte_datos = ppto_persistencia_consultar($mysqli_conn, 8, array(
    'Emp_Cod' => $emp_filtro,
    'Ppe_Ani' => $ani_filtro,
    'Ppe_Cod' => $ppe_cod_filtro,
    'Pej_Mes' => $mes_consulta,
    'pej_vista' => $vista_periodo
));
$reporte_datos = ppto_consulta_rollup_partidas($reporte_datos);
$ppa_proyecto_map = ppto_consulta_ppa_ids_proyecto($mysqli_conn, $emp_filtro, $ppe_cod_filtro);
$reporte_plan_estandar = ppto_consulta_filtrar_plan_estandar($reporte_datos, $ppa_proyecto_map);

$totales_hoja = ppto_consulta_sumar_hojas($reporte_plan_estandar);
$tot_presupuesto = $totales_hoja['presupuestado'];
$tot_ejecutado = $totales_hoja['ejecutado'];
$tot_disponible = $totales_hoja['disponible'];

$metricas_consolidado = ppto_admin_metricas_consolidado($mysqli_conn, $emp_filtro, $ppe_cod_filtro, $mes_consulta, $vista_periodo);
$ver_descripcion = '';
foreach ($versiones as $v) {
    if ((int)$v['Ppe_Ver'] === $ver_filtro) {
        $ver_descripcion = $v['Ppe_Des'];
        break;
    }
}
$dash_url_base = 'dashboard_front.php?emp_cod=' . (int)$emp_filtro
    . '&ani=' . (int)$ani_filtro
    . '&ver=' . (int)$ppe_cod_filtro
    . '&mes=' . (int)$mes_consulta
    . '&vista=' . urlencode($vista_periodo);
$metricas_hay_proyectos = !empty($metricas_consolidado['proyectos']);
$metricas_gap_proyecto = round($metricas_consolidado['total_vigente'] - $tot_presupuesto, 2);

$alertas_activas = array();
if ($ppe_cod_filtro) {
    $alertas_activas = ppto_persistencia_consultar($mysqli_conn, 9, array(
        'Emp_Cod' => $emp_filtro,
        'Ppe_Cod' => $ppe_cod_filtro
    ));
}
$cant_alertas = count($alertas_activas);

$emp_nom_display = 'Empresa Actual';
foreach ($empresas as $e) {
    if ((int)$e['Emp_Cod'] === $emp_filtro) {
        $emp_nom_display = $e['Emp_Des'];
        break;
    }
}

$tab_qs = 'emp_cod=' . $emp_filtro . '&amp;ani=' . $ani_filtro . '&amp;ver=' . $ver_filtro . '&amp;mes=' . $mes_acumulado . '&amp;vista=' . $vista_periodo;
$tab_qs_raw = 'emp_cod=' . $emp_filtro . '&ani=' . $ani_filtro . '&ver=' . $ver_filtro . '&mes=' . $mes_acumulado . '&vista=' . $vista_periodo;
$admin_url_base = 'ppto_admin_front.php?' . $tab_qs_raw;

if ($vista_periodo === 'anual') {
    $periodo_lbl = 'Anual completo';
    $kpi_plan_titulo = 'Plan anual';
    $kpi_ejec_titulo = 'Ejecutado anual';
    $kpi_disp_titulo = 'Disponible anual';
} elseif ($vista_periodo === 'mes') {
    $periodo_lbl = 'Mes: ' . ppto_nombre_mes($mes_consulta);
    $kpi_plan_titulo = 'Plan del mes';
    $kpi_ejec_titulo = 'Ejecutado del mes';
    $kpi_disp_titulo = 'Disponible del mes';
} else {
    $periodo_lbl = 'Acumulado eneâ€“' . strtolower(ppto_nombre_mes($mes_consulta));
    $kpi_plan_titulo = 'Plan acumulado';
    $kpi_ejec_titulo = 'Ejecutado acumulado';
    $kpi_disp_titulo = 'Disponible plan';
}
?>
<!DOCTYPE html>
<html lang="es" class="exa-ui-fill-root">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Presupuesto - EXA</title>
    <?php require_once(__DIR__ . '/../../contabilidad/FRONT/con_model3_assets.php'); ?>
    <!-- Carga unificada de validaciones y JS de presupuesto -->
    <script src="../VALIDACIONES/ppto_format.js"></script>
    <script src="../VALIDACIONES/ppto_validaciones_js.js"></script>
    <style>
        .ppto-consulta-body { padding: 20px !important; }
        .ppto-metricas-callout {
            padding: 14px 16px; margin-bottom: 18px; border-radius: 6px;
            border: 1px solid #bee3f8; background: #ebf8ff;
            font-size: 12px; color: #2c5282; line-height: 1.5;
        }
        .ppto-metricas-callout strong { color: #1a365d; }
        .ppto-metricas-nota-proy {
            margin-top: 10px; padding: 10px 12px; border-radius: 6px;
            border: 1px solid #fbd38d; background: #fffaf0;
            font-size: 11px; color: #744210;
        }
        .ppto-admin-filter-section {
            background: var(--v2-bg-subtle, #f7fafc);
            border: var(--v2-elev-border, 1px solid #e2e8f0);
            border-radius: var(--v2-radius, 8px);
            padding: 12px 14px; margin-bottom: 14px;
        }
        .ppto-admin-filter-form { margin: 0; }
        .ppto-admin-filter-grid {
            display: grid;
            grid-template-columns: 88px minmax(0, 1.2fr) auto minmax(120px, 0.9fr) auto;
            gap: 10px 12px;
            align-items: end;
        }
        .ppto-admin-filter-field.field-ani { max-width: 96px; }
        .ppto-admin-filter-field.field-actions { width: auto; justify-self: start; }
        .ppto-admin-filter-field label {
            display: block; font-size: 10px; font-weight: 700; color: #718096;
            text-transform: uppercase; letter-spacing: 0.35px; margin-bottom: 4px;
        }
        .ppto-admin-filter-field .form-control { width: 100%; height: 32px; font-size: 12px; }
        .ppto-admin-filter-actions {
            display: inline-flex;
            gap: 6px;
            align-items: center;
            flex-wrap: nowrap;
            height: 32px;
        }
        .ppto-admin-filter-actions .btn {
            margin: 0;
            padding: 5px 10px;
            width: auto;
            min-width: 0;
            flex: 0 0 auto;
            white-space: nowrap;
        }
        .ppto-consulta-vista-seg {
            display: inline-flex;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
            overflow: hidden;
            background: #f7fafc;
            height: 32px;
        }
        .ppto-consulta-vista-seg .vista-btn {
            border: none;
            background: transparent;
            padding: 0 12px;
            font-size: 11px;
            font-weight: 600;
            color: #4a5568;
            cursor: pointer;
            line-height: 30px;
            white-space: nowrap;
        }
        .ppto-consulta-vista-seg .vista-btn + .vista-btn { border-left: 1px solid #cbd5e0; }
        .ppto-consulta-vista-seg .vista-btn.active {
            background: #2b6cb0;
            color: #fff;
        }
        .ppto-consulta-vista-seg .vista-btn:hover:not(.active) { background: #edf2f7; }
        .ppto-consulta-periodo-lbl {
            font-size: 11px;
            color: #718096;
            margin-left: 8px;
            white-space: nowrap;
        }
        .ppto-consulta-periodo-lbl strong { color: #2d3748; }
        @media (max-width: 991px) {
            .ppto-admin-filter-grid { grid-template-columns: 88px minmax(0, 1fr) minmax(0, 1fr); }
            .ppto-admin-filter-field.field-actions {
                grid-column: 1 / -1;
                justify-self: end;
                height: auto;
            }
        }
        .ppto-admin-kpi-row {
            display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px; align-items: stretch; margin-bottom: 4px;
        }
        .ppto-admin-kpi-row.cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        @media (max-width: 991px) { .ppto-admin-kpi-row { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        .ppto-admin-kpi-card {
            background: var(--v2-bg-panel, #fff); border: 1px solid #e2e8f0;
            border-radius: 8px; padding: 14px 16px 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); position: relative; min-height: 96px;
            display: flex; flex-direction: column; justify-content: flex-end;
        }
        .ppto-admin-kpi-card .kpi-title {
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.4px;
            font-weight: 700; color: #718096; margin-bottom: 4px; padding-right: 30px;
        }
        .ppto-admin-kpi-card .kpi-value {
            font-size: clamp(16px, 1.6vw, 20px); font-weight: 700; color: #2d3748;
        }
        .ppto-admin-kpi-card .kpi-indicator {
            position: absolute; top: 12px; right: 14px; font-size: 18px; color: #cbd5e0;
        }
        .ppto-admin-kpi-sub { display: block; font-size: 10px; color: #718096; margin-top: 4px; }
        .ppto-admin-bloque { margin-bottom: 20px; border-color: #cbd5e0 !important; }
        .ppto-admin-bloque > .panel-heading {
            background-color: #ebf8ff; border-bottom: 1px solid #cbd5e0; padding: 10px 15px;
        }
        .ppto-admin-bloque > .panel-heading h5 { margin: 0; font-weight: 700; color: #2c5282; font-size: 13px; }
        .ppto-admin-bloque > .panel-body { padding: 14px 15px; }
        .ppto-admin-bloque-desc { margin: 0 0 12px; font-size: 11px; color: #718096; line-height: 1.45; }
    </style>
</head>
<body class="exa-ui-fill-root ppto-consulta-front">

<div class="panel panel-main exa-ui-panel exa-ui-fill-page">
    <div class="panel-heading exa-header exa-header-flex">
        <h3 class="panel-title"><i class="bi bi-graph-up-arrow"></i> Control de Presupuestos &amp; Ejecuci&oacute;n</h3>
        <div class="exa-header-actions">
            <span class="text-muted" style="font-size:12px;">Empresa: <?php echo htmlspecialchars($emp_nom_display); ?></span>
        </div>
    </div>
    <div class="panel-body exa-body ppto-consulta-body">
        <div class="exa-ui-page-view">

    <div class="ppto-admin-filter-section">
    <form method="GET" action="" class="ppto-admin-filter-form" id="main_filters">
        <input type="hidden" name="tab" value="<?php echo $active_tab; ?>" />
        <input type="hidden" name="emp_cod" value="<?php echo (int)$emp_filtro; ?>" />
        <div class="ppto-admin-filter-grid">
        <div class="ppto-admin-filter-field field-ani">
            <label for="ani">A&ntilde;o fiscal</label>
            <select name="ani" id="ani" class="form-control input-sm">
                <?php foreach ($anios_filtro as $a): ?>
                    <option value="<?php echo (int)$a; ?>" <?php echo (int)$a === (int)$ani_filtro ? 'selected' : ''; ?>><?php echo (int)$a; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <input type="hidden" name="ver" id="ver" value="<?php echo (int)$ver_filtro; ?>" />
        <div class="ppto-admin-filter-field">
            <label>Vista</label>
            <input type="hidden" name="vista" id="vista" value="<?php echo htmlspecialchars($vista_periodo); ?>" />
            <div class="ppto-consulta-vista-seg" role="group" aria-label="Vista del periodo">
                <button type="button" class="vista-btn<?php echo $vista_periodo === 'anual' ? ' active' : ''; ?>" data-vista="anual">Anual</button>
                <button type="button" class="vista-btn<?php echo $vista_periodo === 'acumulado' ? ' active' : ''; ?>" data-vista="acumulado">Acumulado</button>
                <button type="button" class="vista-btn<?php echo $vista_periodo === 'mes' ? ' active' : ''; ?>" data-vista="mes">Mes</button>
            </div>
        </div>
        <div class="ppto-admin-filter-field" id="mesFilterWrap" style="<?php echo $vista_periodo === 'anual' ? 'display:none;' : ''; ?>">
            <label for="mes" id="mesFilterLbl"><?php echo $vista_periodo === 'mes' ? 'Mes' : 'Hasta mes'; ?></label>
            <select name="mes" id="mes" class="form-control input-sm">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo $m == $mes_acumulado ? 'selected' : ''; ?>>
                        <?php echo $vista_periodo === 'mes' ? ppto_nombre_mes($m) : ('Hasta ' . ppto_nombre_mes($m)); ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="ppto-admin-filter-field field-actions">
            <label>&nbsp;</label>
            <div class="ppto-admin-filter-actions">
                <span class="ppto-consulta-periodo-lbl"><strong><?php echo htmlspecialchars($periodo_lbl); ?></strong></span>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filtrar</button>
                <a href="ppto_consulta_front.php" class="btn btn-default btn-sm">Limpiar</a>
            </div>
        </div>
        </div>
    </form>
    </div>

    <ul class="nav nav-tabs exa-ui-nav-tabs" role="tablist">
        <?php
        $tabs = array(
            1 => array('icon' => 'bi-bar-chart-line', 'label' => 'M&eacute;tricas Generales'),
            2 => array('icon' => 'bi-traffic-light', 'label' => 'Sem&aacute;foro de Partidas'),
            3 => array('icon' => 'bi-bell', 'label' => 'Alertas'),
        );
        foreach ($tabs as $num => $tab):
        ?>
        <li role="presentation" class="<?php echo $active_tab === $num ? 'active' : ''; ?>">
            <a class="exa-pre-tab-link" href="?tab=<?php echo $num; ?>&amp;<?php echo $tab_qs; ?>">
                <i class="bi <?php echo $tab['icon']; ?>"></i> <?php echo $tab['label']; ?>
                <?php if ($num === 3 && $cant_alertas > 0): ?>
                    <span class="exa-pre-tab-badge"><?php echo $cant_alertas; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="exa-ui-tab-content panels-area">
        <?php if ($active_tab === 1): ?>
            <div class="ppto-metricas-callout">
                <strong><i class="bi bi-info-circle"></i> Dos vistas complementarias</strong><br/>
                <strong>Plan est&aacute;ndar</strong> = presupuesto cargado por partida y mes (sin rubros de proyecto Relavera).<br/>
                <strong>Consolidado</strong> = incluye proyectos presupuestarios (Relavera / RCET-01), reajustes y ejecuci&oacute;n real. La <strong>proyecci&oacute;n por toneladas</strong> est&aacute; en el Dashboard.
            </div>

            <div class="panel panel-default exa-ui-panel ppto-admin-bloque">
                <div class="panel-heading">
                    <h5><i class="bi bi-table text-primary"></i> Plan est&aacute;ndar (presupuesto normal)</h5>
                </div>
                <div class="panel-body">
                    <p class="ppto-admin-bloque-desc">Montos de partidas <strong>sin rubro de proyecto</strong> (no incluye Relavera/RCET). Para editar, use <a href="<?php echo htmlspecialchars($admin_url_base . '&tab=3'); ?>">Administraci&oacute;n &rarr; Mensual</a>.</p>
                    <div class="ppto-admin-kpi-row">
                        <div class="ppto-admin-kpi-card">
                            <i class="bi bi-cash-stack kpi-indicator"></i>
                            <div class="kpi-title"><?php echo htmlspecialchars($kpi_plan_titulo); ?></div>
                            <div class="kpi-value"><?php echo ppto_fmt_money($tot_presupuesto); ?></div>
                            <span class="ppto-admin-kpi-sub">V<?php echo (int)$ver_filtro; ?> &middot; <?php echo htmlspecialchars($periodo_lbl); ?></span>
                        </div>
                        <div class="ppto-admin-kpi-card">
                            <i class="bi bi-cart-check kpi-indicator"></i>
                            <div class="kpi-title"><?php echo htmlspecialchars($kpi_ejec_titulo); ?></div>
                            <div class="kpi-value"><?php echo ppto_fmt_money($tot_ejecutado); ?></div>
                        </div>
                        <div class="ppto-admin-kpi-card">
                            <i class="bi bi-piggy-bank kpi-indicator"></i>
                            <div class="kpi-title"><?php echo htmlspecialchars($kpi_disp_titulo); ?></div>
                            <div class="kpi-value"><?php echo ppto_fmt_money($tot_disponible); ?></div>
                        </div>
                        <div class="ppto-admin-kpi-card">
                            <i class="bi bi-bell kpi-indicator"></i>
                            <div class="kpi-title">Alertas activas</div>
                            <div class="kpi-value"><?php echo $cant_alertas; ?></div>
                        </div>
                    </div>
                    <div class="row" style="margin-top:14px;">
                        <div class="col-md-6">
                            <div class="exa-adq-section" style="margin-bottom:0;box-shadow:none;border:1px solid #e2e8f0;">
                                <h5 class="exa-adq-section-title"><i class="bi bi-pie-chart text-primary"></i> Resumen por tipo <span class="text-muted" style="font-size:11px;font-weight:normal;">(plan est&aacute;ndar)</span></h5>
                                <div class="exa-adq-table-wrap">
                                <table class="table table-bordered exa-adq-table">
                                    <thead>
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Presupuestado</th>
                                            <th>Ejecutado</th>
                                            <th>% Ejecuci&oacute;n</th>
                                            <th>Progreso</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $resumen_tipo = array(
                                            'I' => array('Presup' => 0.00, 'Ejec' => 0.00),
                                            'G' => array('Presup' => 0.00, 'Ejec' => 0.00),
                                            'V' => array('Presup' => 0.00, 'Ejec' => 0.00)
                                        );
                                        $tipos_nombres = array(
                                            'I' => 'Ingresos',
                                            'G' => 'Gastos',
                                            'V' => 'Inversi&oacute;n'
                                        );
                                        if (!empty($reporte_plan_estandar)) {
                                            foreach ($reporte_plan_estandar as $row) {
                                                $clase_row = isset($row['Ppa_Clase']) ? $row['Ppa_Clase'] : 'D';
                                                if ($clase_row !== 'D') {
                                                    continue;
                                                }
                                                $tip = $row['Ppa_Tip'];
                                                if (isset($resumen_tipo[$tip])) {
                                                    $resumen_tipo[$tip]['Presup'] += (float)$row['Presupuestado'];
                                                    $resumen_tipo[$tip]['Ejec'] += (float)$row['Ejecutado'];
                                                }
                                            }
                                        }
                                        foreach ($resumen_tipo as $key => $values):
                                            $pct_tipo = $values['Presup'] > 0 ? ($values['Ejec'] / $values['Presup']) * 100 : 0.00;
                                            $color_prog = '#3182ce';
                                            if ($pct_tipo >= 80 && $pct_tipo < 100) $color_prog = '#dd6b20';
                                            elseif ($pct_tipo >= 100) $color_prog = '#e53e3e';
                                        ?>
                                            <tr>
                                                <td><strong><?php echo $tipos_nombres[$key]; ?></strong></td>
                                                <td><?php echo ppto_fmt_money($values['Presup']); ?></td>
                                                <td><?php echo ppto_fmt_money($values['Ejec']); ?></td>
                                                <td><strong><?php echo ppto_fmt_pct($pct_tipo); ?></strong></td>
                                                <td>
                                                    <div class="exa-pre-progress-wrap">
                                                        <div class="exa-pre-progress-bar" style="background-color: <?php echo $color_prog; ?>; width: <?php echo min(100, $pct_tipo); ?>%;"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="exa-adq-section" style="margin-bottom:0;box-shadow:none;border:1px solid #e2e8f0;">
                                <h5 class="exa-adq-section-title"><i class="bi bi-calendar-event text-primary"></i> Volumen &uacute;ltimos 6 meses <span class="text-muted" style="font-size:11px;font-weight:normal;">(plan est&aacute;ndar)</span></h5>
                                <div class="exa-adq-table-wrap">
                                <table class="table table-bordered exa-adq-table">
                                    <thead>
                                        <tr>
                                            <th>Mes</th>
                                            <th><?php echo $vista_periodo === 'mes' ? 'Presup. del mes' : 'Presup acumulado'; ?></th>
                                            <th>Ejecutado</th>
                                            <th>Disponible</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $ultimos_meses = array();
                                        for ($m = max(1, $mes_acumulado - 5); $m <= $mes_acumulado; $m++) {
                                            $res_m = ppto_persistencia_consultar($mysqli_conn, 8, array(
                                                'Emp_Cod' => $emp_filtro,
                                                'Ppe_Ani' => $ani_filtro,
                                                'Ppe_Cod' => $ppe_cod_filtro,
                                                'Pej_Mes' => $m,
                                                'pej_vista' => ($vista_periodo === 'mes' ? 'mes' : 'acumulado')
                                            ));
                                            $res_m = ppto_consulta_filtrar_plan_estandar($res_m, $ppa_proyecto_map);
                                            $sum_m = ppto_consulta_sumar_hojas($res_m);
                                            $ultimos_meses[] = array(
                                                'mes' => $m,
                                                'presupuestado' => $sum_m['presupuestado'],
                                                'ejecutado' => $sum_m['ejecutado'],
                                                'disponible' => $sum_m['disponible']
                                            );
                                        }
                                        foreach ($ultimos_meses as $um):
                                        ?>
                                            <tr>
                                                <td><strong><?php echo ppto_nombre_mes($um['mes']); ?></strong></td>
                                                <td><?php echo ppto_fmt_money($um['presupuestado']); ?></td>
                                                <td><?php echo ppto_fmt_money($um['ejecutado']); ?></td>
                                                <td><?php echo ppto_fmt_money($um['disponible']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel panel-default exa-ui-panel ppto-admin-bloque">
                <div class="panel-heading" style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                    <h5 style="margin:0;"><i class="bi bi-speedometer2 text-primary"></i> Consolidado Relavera / proyectos</h5>
                    <a href="<?php echo htmlspecialchars($dash_url_base); ?>" class="btn btn-primary btn-sm" title="Dashboard con proyecci&oacute;n por toneladas">
                        <i class="bi bi-box-arrow-up-right"></i> Ir al Dashboard
                    </a>
                </div>
                <div class="panel-body">
                    <p class="ppto-admin-bloque-desc">Presupuesto por proyecto (toneladas, rubros, reajustes). Incluye <strong>Relavera RCET-01</strong> cuando est&aacute; configurado.</p>
                    <div class="ppto-admin-kpi-row cols-3">
                        <div class="ppto-admin-kpi-card">
                            <i class="bi bi-wallet2 kpi-indicator"></i>
                            <div class="kpi-title">Ppto. vigente consolidado</div>
                            <div class="kpi-value"><?php echo ppto_fmt_money($metricas_consolidado['total_vigente']); ?></div>
                            <span class="ppto-admin-kpi-sub">Plan empresa + RCET/proyectos &middot; <?php echo htmlspecialchars($periodo_lbl); ?></span>
                        </div>
                        <div class="ppto-admin-kpi-card">
                            <i class="bi bi-cart-check kpi-indicator"></i>
                            <div class="kpi-title">Ejecutado consolidado</div>
                            <div class="kpi-value"><?php echo ppto_fmt_money($metricas_consolidado['total_ejecutado']); ?></div>
                        </div>
                        <div class="ppto-admin-kpi-card">
                            <i class="bi bi-piggy-bank kpi-indicator"></i>
                            <div class="kpi-title">Disponible consolidado</div>
                            <div class="kpi-value"><?php echo ppto_fmt_money($metricas_consolidado['total_disponible']); ?></div>
                        </div>
                    </div>
                <?php if ($metricas_hay_proyectos): ?>
                    <div class="exa-adq-table-wrap" style="margin-top:14px;">
                        <table class="table table-bordered exa-adq-table table-condensed">
                            <thead>
                                <tr>
                                    <th>Proyecto</th>
                                    <th class="text-right">Ppto. vigente</th>
                                    <th class="text-right">Ejecutado</th>
                                    <th class="text-right">Disponible</th>
                                    <th class="text-center" style="width:120px;">Dashboard</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($metricas_consolidado['proyectos'] as $mp):
                                    $url_proy = $dash_url_base . '&Pro_Cod=' . rawurlencode($mp['Pro_Cod']);
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($mp['Pro_Cod']); ?></strong><br/>
                                        <small class="text-muted"><?php echo htmlspecialchars($mp['Pro_Nom']); ?></small>
                                    </td>
                                    <td class="text-right"><?php echo ppto_fmt_money($mp['vigente']); ?></td>
                                    <td class="text-right"><?php echo ppto_fmt_money($mp['ejecutado']); ?></td>
                                    <td class="text-right"><?php echo ppto_fmt_money($mp['disponible']); ?></td>
                                    <td class="text-center">
                                        <a href="<?php echo htmlspecialchars($url_proy); ?>" class="btn btn-default btn-xs" title="Ver en Dashboard">
                                            <i class="bi bi-graph-up"></i> Ver
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted" style="margin-top:12px;font-size:12px;">No hay proyectos presupuestarios con montos en este per&iacute;odo.</p>
                <?php endif; ?>
                <?php if ($metricas_gap_proyecto > 0.01): ?>
                    <div class="ppto-metricas-nota-proy">
                        <i class="bi bi-exclamation-triangle"></i>
                        El consolidado (<strong><?php echo ppto_fmt_money($metricas_consolidado['total_vigente']); ?></strong>)
                        supera el plan est&aacute;ndar (<strong><?php echo ppto_fmt_money($tot_presupuesto); ?></strong>).
                        La diferencia corresponde a <strong>rubros Relavera / proyecto</strong>, no al presupuesto mensual normal.
                    </div>
                <?php endif; ?>
                </div>
            </div>

        <?php elseif ($active_tab === 2): ?>
            <?php
            $filtro_tipo = isset($_REQUEST['partida_tipo']) ? $_REQUEST['partida_tipo'] : 'All';
            $filtro_nat = isset($_REQUEST['partida_nat']) ? $_REQUEST['partida_nat'] : 'All';
            $filtro_clase = isset($_REQUEST['partida_clase']) ? $_REQUEST['partida_clase'] : 'All';

            $kpi_total = 0;
            $kpi_a_tiempo = 0;
            $kpi_en_riesgo = 0;
            $kpi_superadas = 0;

            $filtered_partidas = array();
            if (!empty($reporte_datos)) {
                foreach ($reporte_datos as $row) {
                    $clase_row = isset($row['Ppa_Clase']) ? $row['Ppa_Clase'] : 'D';
                    $match_tipo = ($filtro_tipo === 'All' || $row['Ppa_Tip'] === $filtro_tipo);
                    $match_nat = ($filtro_nat === 'All' || $row['Ppa_Nat'] === $filtro_nat);
                    $match_clase = ($filtro_clase === 'All' || $clase_row === $filtro_clase);
                    if ($match_tipo && $match_nat && $match_clase) {
                        $filtered_partidas[] = $row;
                        $kpi_total++;
                        $pct = (float)$row['Pct_Ejecutado'];
                        if ($pct < 80.00) {
                            $kpi_a_tiempo++;
                        } elseif ($pct >= 80.00 && $pct < 100.00) {
                            $kpi_en_riesgo++;
                        } else {
                            $kpi_superadas++;
                        }
                    }
                }
            }
            ?>
            <div class="exa-adq-kpi-row">
                <div class="exa-adq-kpi kpi-neutral">
                    <span class="kpi-label">Total Partidas</span>
                    <span class="kpi-value"><?php echo $kpi_total; ?></span>
                </div>
                <div class="exa-adq-kpi kpi-success">
                    <span class="kpi-label">A Tiempo (&lt;80%)</span>
                    <span class="kpi-value"><?php echo $kpi_a_tiempo; ?></span>
                </div>
                <div class="exa-adq-kpi kpi-warning">
                    <span class="kpi-label">En Riesgo (80% - 99%)</span>
                    <span class="kpi-value"><?php echo $kpi_en_riesgo; ?></span>
                </div>
                <div class="exa-adq-kpi kpi-danger">
                    <span class="kpi-label">Superadas (&ge;100%)</span>
                    <span class="kpi-value"><?php echo $kpi_superadas; ?></span>
                </div>
            </div>

            <div class="exa-pre-inline-filters">
                <div class="filter-group">
                    <label for="partida_clase">Clase</label>
                    <select id="partida_clase" class="form-control input-sm" onchange="actualizarFiltrosTab2()">
                        <option value="All" <?php echo $filtro_clase === 'All' ? 'selected' : ''; ?>>Todas</option>
                        <option value="G" <?php echo $filtro_clase === 'G' ? 'selected' : ''; ?>>Grupo</option>
                        <option value="D" <?php echo $filtro_clase === 'D' ? 'selected' : ''; ?>>Detalle</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="partida_tipo">Tipo contable</label>
                    <select id="partida_tipo" class="form-control input-sm" onchange="actualizarFiltrosTab2()">
                        <option value="All" <?php echo $filtro_tipo === 'All' ? 'selected' : ''; ?>>Todas</option>
                        <option value="I" <?php echo $filtro_tipo === 'I' ? 'selected' : ''; ?>>Ingresos</option>
                        <option value="G" <?php echo $filtro_tipo === 'G' ? 'selected' : ''; ?>>Gastos</option>
                        <option value="V" <?php echo $filtro_tipo === 'V' ? 'selected' : ''; ?>>Inversi&oacute;n</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="partida_nat">Naturaleza</label>
                    <select id="partida_nat" class="form-control input-sm" onchange="actualizarFiltrosTab2()">
                        <option value="All" <?php echo $filtro_nat === 'All' ? 'selected' : ''; ?>>Todas</option>
                        <option value="OPE" <?php echo $filtro_nat === 'OPE' ? 'selected' : ''; ?>>OPE</option>
                        <option value="ADM" <?php echo $filtro_nat === 'ADM' ? 'selected' : ''; ?>>ADM</option>
                        <option value="COM" <?php echo $filtro_nat === 'COM' ? 'selected' : ''; ?>>COM</option>
                        <option value="FIN" <?php echo $filtro_nat === 'FIN' ? 'selected' : ''; ?>>FIN</option>
                        <option value="RRH" <?php echo $filtro_nat === 'RRH' ? 'selected' : ''; ?>>RRH</option>
                    </select>
                </div>
            </div>

            <div class="exa-adq-section">
                <h5 class="exa-adq-section-title"><i class="bi bi-traffic-light text-primary"></i> Sem&aacute;foro de Partidas</h5>
                <p class="text-muted" style="font-size:11px;margin:0 0 10px;">
                    <strong>Clase</strong> = Grupo (agrupador) o Detalle (imputable).
                    Los montos de grupos se acumulan desde sus partidas detalle hijas.
                    <strong>Proyectado</strong> = plan mensual + presupuesto publicado de proyectos (Relaves / ton &times; $/Ton).
                    Si sigue en $0, publique rubros en <a href="ppto_proyectos_front.php">Proyectos</a>
                    o cargue el plan en <a href="ppto_admin_front.php?tab=3&amp;emp_cod=<?php echo (int)$emp_filtro; ?>">Administraci&oacute;n &rarr; Mensual</a>.
                </p>
                <div class="exa-adq-table-wrap">
                <table class="table table-bordered exa-adq-table">
                    <thead>
                        <tr>
                            <th>C&oacute;digo</th>
                            <th>Partida</th>
                            <th>Clase</th>
                            <th>Tipo cont.</th>
                            <th title="Plan mensual + proyectado publicado de proyectos">Proyectado</th>
                            <th>Ejecutado</th>
                            <th>Disponible</th>
                            <th>%</th>
                            <th>Sem&aacute;foro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($filtered_partidas)): ?>
                            <?php foreach ($filtered_partidas as $p_row):
                                $pct = (float)$p_row['Pct_Ejecutado'];
                                if ($pct < 80.00) {
                                    $bg = '#c6f6d5'; $fg = '#22543d'; $dot = '#38a169'; $text_sem = 'A tiempo';
                                } elseif ($pct >= 80.00 && $pct < 100.00) {
                                    $bg = '#feebc8'; $fg = '#744210'; $dot = '#dd6b20'; $text_sem = 'En riesgo';
                                } else {
                                    $bg = '#fed7d7'; $fg = '#742a2a'; $dot = '#e53e3e'; $text_sem = 'Superado';
                                }
                                $clase_row = isset($p_row['Ppa_Clase']) ? $p_row['Ppa_Clase'] : 'D';
                                $nivel_row = isset($p_row['Ppa_Niv']) ? (int)$p_row['Ppa_Niv'] : ppto_partida_nivel_desde_codigo($p_row['Ppa_Cla']);
                                $indent = ppto_partida_indent_px($nivel_row);
                                $tr_style = ($clase_row === 'G') ? ' style="background:#f8fafc;font-weight:600;"' : '';
                            ?>
                                <tr<?php echo $tr_style; ?>>
                                    <td><strong><?php echo htmlspecialchars($p_row['Ppa_Cla']); ?></strong></td>
                                    <td style="padding-left:<?php echo (int)$indent; ?>px;"><?php echo htmlspecialchars($p_row['Ppa_Des']); ?></td>
                                    <td>
                                        <?php if ($clase_row === 'G'): ?>
                                            <span class="label label-primary" style="font-size:10px;">Grupo</span>
                                        <?php else: ?>
                                            <span class="label label-default" style="font-size:10px;">Detalle</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(ppto_tipo_contable_etiqueta($p_row['Ppa_Tip'])); ?></td>
                                    <td><?php echo ppto_fmt_money($p_row['Presupuestado']); ?></td>
                                    <td><?php echo ppto_fmt_money($p_row['Ejecutado']); ?></td>
                                    <td><?php echo ppto_fmt_money($p_row['Disponible']); ?></td>
                                    <td><strong><?php echo ppto_fmt_pct($pct); ?></strong></td>
                                    <td>
                                        <span class="exa-pre-dot-badge" style="background-color: <?php echo $bg; ?>; color: <?php echo $fg; ?>;">
                                            <span class="exa-pre-dot" style="background-color: <?php echo $dot; ?>;"></span>
                                            <?php echo $text_sem; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-xs" type="button" onclick="verDetallePartida(<?php echo $ppe_cod_filtro; ?>, <?php echo $p_row['Ppa_Cod']; ?>, '<?php echo addslashes($p_row['Ppa_Des']); ?>', <?php echo $mes_acumulado; ?>)">
                                            <i class="bi bi-search"></i> Detalle
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center exa-pre-muted" style="padding:24px;">
                                    No hay registros que coincidan con los filtros aplicados.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>

        <?php elseif ($active_tab === 3): ?>
            <div class="exa-adq-section">
                <h5 class="exa-adq-section-title"><i class="bi bi-bell text-primary"></i> Alertas de Consumo de Presupuesto</h5>
            <?php if (!empty($alertas_activas)): ?>
                <?php foreach ($alertas_activas as $al):
                    $color_card = '#38a169'; $color_bg = '#f0fff4';
                    if ((int)$al['pal_umbral'] === 90) {
                        $color_card = '#dd6b20'; $color_bg = '#fffaf0';
                    } elseif ((int)$al['pal_umbral'] === 100) {
                        $color_card = '#e53e3e'; $color_bg = '#fff5f5';
                    }
                ?>
                    <div class="exa-pre-alert-card" style="border-left: 4px solid <?php echo $color_card; ?>; background-color: <?php echo $color_bg; ?>;">
                        <div>
                            <div style="font-size:14px; font-weight:700; color:#2d3748; margin-bottom:4px;">
                                Partida: <?php echo htmlspecialchars($al['Ppa_Cla']); ?> - <?php echo htmlspecialchars($al['Ppa_Des']); ?>
                            </div>
                            <div style="font-size:12px; color:#4a5568;">
                                Umbral de alerta: <strong style="color: <?php echo $color_card; ?>;"><?php echo $al['pal_umbral']; ?>%</strong>
                                | Ejecuci&oacute;n actual: <strong><?php echo ppto_fmt_pct($al['pal_porcentaje_actual']); ?></strong>
                                | Fecha de alerta: <strong><?php echo $al['pal_fecha_registro']; ?></strong>
                            </div>
                        </div>
                        <a href="ppto_consulta_front.php?marcar_leida=<?php echo $al['pal_id']; ?>&amp;emp_cod=<?php echo $emp_filtro; ?>&amp;ani=<?php echo $ani_filtro; ?>&amp;ver=<?php echo $ver_filtro; ?>&amp;mes=<?php echo $mes_acumulado; ?>" class="btn btn-default btn-xs">
                            Marcar como le&iacute;da
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="exa-pre-empty-state">
                    <h4>&iexcl;Todo al d&iacute;a!</h4>
                    <p>No existen alertas sin leer para el presupuesto activo del a&ntilde;o.</p>
                </div>
            <?php endif; ?>
            </div>
        <?php endif; ?>
    </div><!-- exa-ui-tab-content -->
        </div><!-- exa-ui-page-view -->
    </div><!-- panel-body -->
</div><!-- panel -->

<div id="modal_detalle" class="exa-pre-modal-overlay">
    <div class="exa-pre-modal-box" style="width:70%; max-width:960px;">
        <span class="exa-pre-modal-close" onclick="cerrarModal()">&times;</span>
        <h3 id="modal_titulo" class="exa-adq-section-title">Documentos de Ejecuci&oacute;n</h3>
        <div class="exa-adq-table-wrap" style="margin-top:16px;">
            <table class="table table-bordered exa-adq-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo Documento</th>
                        <th>C&oacute;digo Doc</th>
                        <th>Monto</th>
                        <th>Signo</th>
                    </tr>
                </thead>
                <tbody id="modal_tabla_body">
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function actualizarFiltrosTab2() {
    var t = document.getElementById('partida_tipo').value;
    var n = document.getElementById('partida_nat').value;
    var c = document.getElementById('partida_clase').value;
    var currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('partida_tipo', t);
    currentUrl.searchParams.set('partida_nat', n);
    currentUrl.searchParams.set('partida_clase', c);
    window.location.href = currentUrl.toString();
}

function verDetallePartida(ppeCod, ppaCod, partidaNombre, mes) {
    document.getElementById('modal_titulo').innerText = 'Ejecuci\u00f3n Detallada - ' + partidaNombre;
    var tbody = document.getElementById('modal_tabla_body');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center exa-pre-muted" style="padding:20px;">Cargando...</td></tr>';
    document.getElementById('modal_detalle').style.display = 'block';

    var xhr = new XMLHttpRequest();
    var url = 'ppto_consulta_front.php?ajax_partida_detalle=1&Ppe_Cod=' + ppeCod + '&Ppa_Cod=' + ppaCod;
    if (mes) {
        url += '&mes=' + mes;
    }
    xhr.open('GET', url, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var data = JSON.parse(xhr.responseText);
                tbody.innerHTML = '';
                if (data && data.length > 0) {
                    data.forEach(function (row) {
                        var tr = document.createElement('tr');
                        tr.innerHTML = '<td>' + row.Pej_Fec + '</td>' +
                                       '<td style="text-transform:capitalize;">' + row.Pej_TipDoc.replace('_', ' ') + '</td>' +
                                       '<td>' + row.Pej_DocCod + '</td>' +
                                       '<td style="font-weight:600;">' + formatCurrency(row.Pej_Mon) + '</td>' +
                                       '<td style="font-weight:bold; color:' + (row.Pej_Sig === '+' ? '#38a169' : '#e53e3e') + ';">' + row.Pej_Sig + '</td>';
                        tbody.appendChild(tr);
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center exa-pre-muted" style="padding:20px;">No hay documentos registrados para esta partida.</td></tr>';
                }
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center exa-pre-muted" style="padding:20px;">Error al cargar los datos.</td></tr>';
            }
        }
    };
    xhr.send();
}

function cerrarModal() {
    document.getElementById('modal_detalle').style.display = 'none';
}

window.onclick = function(event) {
    var modal = document.getElementById('modal_detalle');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

window.addEventListener('DOMContentLoaded', () => {
    switchTab(<?php echo $active_tab; ?>);
});

(function() {
    var MESES = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    function actualizarVistaUi(vista) {
        var mesWrap = document.getElementById('mesFilterWrap');
        var lbl = document.getElementById('mesFilterLbl');
        var mes = document.getElementById('mes');
        var vistaInput = document.getElementById('vista');
        if (vistaInput) vistaInput.value = vista;
        document.querySelectorAll('.ppto-consulta-vista-seg .vista-btn').forEach(function(btn) {
            btn.classList.toggle('active', btn.getAttribute('data-vista') === vista);
        });
        if (mesWrap) mesWrap.style.display = (vista === 'anual') ? 'none' : '';
        if (lbl) lbl.textContent = (vista === 'mes') ? 'Mes' : 'Hasta mes';
        if (mes) {
            Array.prototype.forEach.call(mes.options, function(opt) {
                var m = parseInt(opt.value, 10);
                opt.text = (vista === 'mes') ? MESES[m] : ('Hasta ' + MESES[m]);
            });
        }
    }
    document.querySelectorAll('.ppto-consulta-vista-seg .vista-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            actualizarVistaUi(this.getAttribute('data-vista') || 'acumulado');
            document.getElementById('main_filters').submit();
        });
    });
})();
</script>

</body>
</html>
<?php
$obBD_conexion->cerrar();
?>
