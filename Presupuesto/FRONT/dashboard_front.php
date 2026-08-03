<?php
/**
 * dashboard_front.php
 * Interfaz Gr?fica del Dashboard Presupuestario (EXA PPTO).
 * Permite visualizar de forma interactiva KPIs, sem?foros, gr?ficos de rendimiento y tablas anal?ticas.
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../../contabilidad/LOGICA/con_log_balances2.php');
require_once('../LOGICA/ppto_schema_logica.php');
require_once('../LOGICA/ppto_partidas_logica.php');
require_once('../VALIDACIONES/dashboard_validaciones.php');
require_once('../LOGICA/dashboard_logica.php');

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
    ppto_schema_ensure($mysqli_conn);
}

// 1. Obtener y validar filtros iniciales
$filtros = ppto_dashboard_validar_filtros($_REQUEST);

$dash_inicial_relavera = false;
if (!empty($filtros['proy_id']) && $mysqli_conn) {
    $dash_inicial_relavera = ppto_proy_es_modo_reinversion(
        $mysqli_conn,
        $filtros['proy_id'],
        (int)$filtros['emp_id']
    );
}

// 2. Cargar opciones para los filtros de seleccion
// 2.1 Proyectos
$res_proyectos = $mysqli_conn->query("SELECT proy_id, proy_nombre FROM exa_ppto_proyectos WHERE emp_id = " . $filtros['emp_id'] . " AND proy_estado = 'A' ORDER BY proy_nombre");
$proyectos = array();
if ($res_proyectos) {
    while ($row = $res_proyectos->fetch_assoc()) {
        $proyectos[] = $row;
    }
}

// 2.3 Versiones/Cabeceras presupuestarias para el a?o actual
$res_cabeceras = $mysqli_conn->query("SELECT ppe_id, ppe_version, ppe_descripcion, ppe_estado FROM exa_ppto_cabeceras WHERE emp_id = " . $filtros['emp_id'] . " AND ppe_anio = " . $filtros['anio'] . " ORDER BY ppe_version DESC");
$versiones = array();
if ($res_cabeceras) {
    while ($row = $res_cabeceras->fetch_assoc()) {
        $versiones[] = $row;
    }
}

// Resolver la versi?n por defecto si no se especific?
if (!$filtros['ppe_id'] && !empty($versiones)) {
    foreach ($versiones as $v) {
        if ($v['ppe_estado'] === 'A') {
            $filtros['ppe_id'] = (int)$v['ppe_id'];
            break;
        }
    }
    if (!$filtros['ppe_id']) {
        $filtros['ppe_id'] = (int)$versiones[0]['ppe_id'];
    }
}

// 2.4 Partidas (activas, imputables)
$partidas = ppto_partidas_listar($mysqli_conn, array(
    'emp_id' => (int)$filtros['emp_id'],
    'solo_activas' => true,
    'clase' => 'D'
));
?>
<!DOCTYPE html>
<html lang="es" class="exa-ui-fill-root">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Presupuestario - EXA</title>
    <?php require_once(__DIR__ . '/../../contabilidad/FRONT/con_model3_assets.php'); ?>
    <script src="../VALIDACIONES/ppto_format.js"></script>
    <style>
        .kpi-card {
            background: var(--v2-bg-panel, #ffffff);
            border: var(--v2-elev-border, 1px solid #e2e8f0);
            border-radius: var(--v2-radius, 8px);
            padding: 14px 16px 12px;
            box-shadow: var(--v2-elev-shadow, 0 4px 6px rgba(0,0,0,0.05));
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
            height: 100%;
            min-height: 96px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.08);
        }
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            align-items: stretch;
        }
        .kpi-row-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        @media (max-width: 991px) {
            .kpi-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 575px) {
            .kpi-row { grid-template-columns: 1fr; }
        }
        .kpi-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-weight: 700;
            color: var(--v2-text-muted, #718096);
            margin-bottom: 4px;
            padding-right: 30px;
            line-height: 1.35;
            min-height: 2.7em;
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
        }
        .kpi-pct {
            font-size: 10px;
            font-weight: 600;
            color: #48bb78;
            margin-bottom: 2px;
            line-height: 1.2;
        }
        .kpi-value {
            font-size: clamp(16px, 1.6vw, 20px);
            font-weight: 700;
            color: var(--v2-text-primary, #2d3748);
            line-height: 1.25;
            word-break: break-word;
        }
        .kpi-card-prod .kpi-value { color: #3182ce; }
        .kpi-card-prod.kpi-prod-var .kpi-value { font-size: clamp(14px, 1.4vw, 18px); }
        .kpi-indicator {
            position: absolute;
            top: 12px;
            right: 14px;
            font-size: 18px;
            color: var(--v2-primary-subtle, #cbd5e0);
            line-height: 1;
        }
        .semaforo-indicator {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
            vertical-align: middle;
            border: 2px solid rgba(255,255,255,0.4);
        }
        .semaforo-bg-verde { background-color: #48bb78 !important; box-shadow: 0 0 8px #48bb78; }
        .semaforo-bg-amarillo { background-color: #ecc94b !important; box-shadow: 0 0 8px #ecc94b; }
        .exa-dash-row-grupo td {
            background-color: #ebf8ff !important;
            border-top: 2px solid #4299e1 !important;
            font-weight: 700;
            color: #1a365d;
        }
        .exa-dash-row-grupo td:first-child {
            font-size: 13px;
        }
        .exa-dash-row-detalle td {
            background-color: #ffffff;
        }
        .exa-dash-th-tip {
            cursor: help;
            border-bottom: 1px dotted #a0aec0;
        }
        .exa-dash-filter-footer .exa-dash-segment { margin-left: 0; }
        .kpi-tecnico-only { display: none; }
        body.ppto-dash-modo-tecnico .kpi-tecnico-only { display: block; }
        .exa-dash-vista-tecnico { display: none; }
        body.ppto-dash-modo-tecnico .exa-dash-vista-tecnico.exa-dash-segment-btn { display: block; }
        .semaforo-bg-rojo { background-color: #f56565 !important; box-shadow: 0 0 8px #f56565; }

        .progress-bar-custom {
            height: 8px;
            background-color: #edf2f7;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 10px;
        }
        .progress-fill {
            height: 100%;
            border-radius: 4px;
        }

        .filter-section {
            background: var(--v2-bg-subtle, #f7fafc);
            border: var(--v2-elev-border, 1px solid #e2e8f0);
            border-radius: var(--v2-radius, 8px);
            padding: 12px 14px;
            margin-bottom: 14px;
        }
        .exa-dash-filter-form { margin: 0; }
        .exa-dash-filter-grid {
            display: grid;
            grid-template-columns: 78px minmax(150px, 1.5fr) minmax(130px, 1.3fr) 92px minmax(300px, 2fr) 36px;
            gap: 10px 12px;
            align-items: end;
        }
        .exa-dash-filter-field {
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        .exa-dash-filter-field label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.35px;
            margin: 0 0 4px;
            line-height: 1.2;
            white-space: nowrap;
            height: 14px;
        }
        .exa-dash-filter-field .form-control {
            width: 100%;
            height: 32px;
            font-size: 12px;
            margin: 0;
        }
        .exa-dash-filter-btn {
            width: 36px;
        }
        .exa-dash-filter-btn label {
            visibility: hidden;
        }
        .exa-dash-filter-btn .btn {
            height: 32px;
            width: 36px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .exa-dash-periodo-bar {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 8px;
            height: 32px;
            min-width: 0;
        }
        .exa-dash-periodo-bar .exa-dash-segment {
            height: 32px;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: stretch;
        }
        .exa-dash-periodo-bar .exa-dash-segment-btn {
            height: 100%;
            padding: 0 10px;
            font-size: 11px;
            line-height: 30px;
            display: inline-flex;
            align-items: center;
        }
        .exa-dash-periodo-bar #mes {
            width: 76px;
            flex: 0 0 76px;
            height: 32px;
            margin: 0;
        }
        .exa-dash-periodo-bar .periodo-lbl {
            display: none;
        }
        .exa-dash-filter-footer {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            flex-wrap: wrap;
        }
        .exa-dash-filter-footer-label {
            font-size: 10px;
            font-weight: 700;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.35px;
            white-space: nowrap;
        }
        .exa-dash-filter-footer .periodo-lbl {
            margin-left: auto;
            font-size: 11px;
            color: #718096;
        }
        .exa-dash-filter-footer .periodo-lbl strong { color: #2d3748; }
        @media (max-width: 1100px) {
            .exa-dash-filter-grid {
                grid-template-columns: 78px minmax(140px, 1fr) minmax(120px, 1fr) 92px;
            }
            .exa-dash-filter-field-periodo,
            .exa-dash-filter-btn {
                grid-column: span 2;
            }
        }
        @media (max-width: 991px) {
            .exa-dash-filter-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            .exa-dash-filter-field-periodo { grid-column: span 2; }
            .exa-dash-filter-btn { grid-column: span 1; }
        }
        @media (max-width: 575px) {
            .exa-dash-filter-grid { grid-template-columns: 1fr 1fr; }
            .exa-dash-filter-field-periodo { grid-column: span 2; }
        }
        .tab-navigation {
            margin-bottom: 0;
            border-bottom: none;
            flex: 1;
            min-width: 0;
        }
        .tab-link {
            display: inline-block;
            padding: 8px 14px;
            font-weight: 600;
            color: var(--v2-text-muted, #718096);
            border-bottom: 2px solid transparent;
            cursor: pointer;
            text-decoration: none !important;
            font-size: 12px;
            white-space: nowrap;
        }
        .tab-link.active {
            color: var(--v2-primary, #3182ce);
            border-bottom-color: var(--v2-primary, #3182ce);
        }
        .exa-dash-report-toolbar {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            padding: 10px 14px;
            margin-bottom: 12px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }
        .exa-dash-report-toolbar .tab-navigation {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        }
        .exa-dash-toolbar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
            flex-shrink: 0;
        }
        .exa-dash-segment {
            display: inline-flex;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
            overflow: hidden;
            background: #f7fafc;
        }
        .exa-dash-segment-btn {
            border: none;
            background: transparent;
            padding: 5px 12px;
            font-size: 11px;
            font-weight: 600;
            color: #4a5568;
            cursor: pointer;
            line-height: 1.3;
            white-space: nowrap;
            transition: background 0.15s, color 0.15s;
        }
        .exa-dash-segment-btn + .exa-dash-segment-btn {
            border-left: 1px solid #cbd5e0;
        }
        .exa-dash-segment-btn.active {
            background: #2b6cb0;
            color: #fff;
            box-shadow: none;
        }
        .exa-dash-segment-btn.active:hover {
            background: #2c5282;
            color: #fff;
        }
        .exa-dash-segment-btn:hover:not(.active) {
            background: #edf2f7;
        }
        .exa-dash-modo-bar .exa-dash-segment { margin-left: 4px; }
        .exa-dash-ayuda {
            font-size: 11px;
            color: #718096;
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 10px;
            margin-bottom: 10px;
            line-height: 1.4;
        }
        .exa-dash-alert-compact {
            border-radius: 6px;
            margin-bottom: 10px;
            font-size: 11px;
            overflow: hidden;
        }
        .exa-dash-alert-compact .alert-head {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            cursor: pointer;
            user-select: none;
        }
        .exa-dash-alert-compact .alert-head strong {
            flex: 1;
            font-size: 11px;
            font-weight: 700;
        }
        .exa-dash-alert-compact .alert-toggle {
            font-size: 10px;
            font-weight: 600;
            color: inherit;
            opacity: 0.85;
            white-space: nowrap;
        }
        .exa-dash-alert-compact .alert-body {
            display: none;
            padding: 0 12px 10px;
            border-top: 1px solid rgba(0,0,0,0.06);
        }
        .exa-dash-alert-compact.open .alert-body { display: block; }
        .exa-dash-alert-compact.open .alert-toggle::before { content: 'Ocultar'; }
        .exa-dash-alert-compact:not(.open) .alert-toggle::before { content: 'Ver detalle'; }
        .exa-dash-alert-item {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            padding: 8px 10px;
            margin-top: 6px;
            background: rgba(255,255,255,0.55);
            border-radius: 4px;
            font-size: 11px;
            line-height: 1.4;
        }
        .exa-dash-alert-item-text { flex: 1; min-width: 0; }
        .exa-dash-alert-item-name {
            font-weight: 700;
            color: #742a2a;
            text-decoration: none;
            display: block;
            word-wrap: break-word;
        }
        .exa-dash-alert-item-name:hover { text-decoration: underline; color: #9b2c2c; }
        .exa-dash-alert-item-metrics {
            color: #4a5568;
            font-size: 10px;
            margin-top: 3px;
            line-height: 1.45;
        }
        .exa-dash-alert-item a.exa-dash-alert-link {
            font-weight: 700;
            white-space: nowrap;
            font-size: 10px;
            flex-shrink: 0;
            align-self: center;
        }
        .exa-dash-leyenda-tags {
            font-size: 10px;
            color: #718096;
            margin-bottom: 8px;
            line-height: 1.45;
        }
        .exa-dash-vista-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        body.ppto-dash-modo-gerente #ppto_dash_vista_bar { display: none; }
        .exa-dash-vista-label {
            font-size: 10px;
            font-weight: 700;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            white-space: nowrap;
        }
        .exa-dash-tag {
            display: inline-block;
            font-size: 9px;
            font-weight: 700;
            padding: 1px 5px;
            border-radius: 3px;
            margin-left: 5px;
            vertical-align: middle;
            letter-spacing: 0.2px;
            line-height: 1.4;
        }
        .exa-dash-cell-rubro {
            line-height: 1.35;
            max-width: 320px;
            word-break: break-word;
        }
        .exa-dash-cell-money {
            white-space: nowrap;
            font-size: 11px;
        }
        #tabla_partidas { table-layout: fixed; font-size: 11px; }
        #tabla_partidas th { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; padding: 8px 6px !important; vertical-align: middle; }
        #tabla_partidas td { padding: 7px 6px !important; vertical-align: middle; }
        #ppto_dash_partidas_wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        #tabla_partidas.ppto-dash-tabla-relavera {
            table-layout: auto;
            width: 100%;
            min-width: 1220px;
        }
        #tabla_partidas.ppto-dash-tabla-relavera th {
            text-transform: none;
            letter-spacing: 0;
            font-size: 10px;
            line-height: 1.25;
            white-space: normal;
            vertical-align: bottom;
            padding: 6px 5px !important;
            hyphens: auto;
        }
        #tabla_partidas.ppto-dash-tabla-relavera th.col-codigo {
            min-width: 92px;
            width: 92px;
        }
        #tabla_partidas.ppto-dash-tabla-relavera th.col-desc {
            min-width: 180px;
            white-space: nowrap;
        }
        #tabla_partidas.ppto-dash-tabla-relavera th.col-money {
            min-width: 78px;
            width: 78px;
            max-width: 86px;
        }
        #tabla_partidas.ppto-dash-tabla-relavera th.col-pct {
            min-width: 54px;
            width: 54px;
        }
        #tabla_partidas.ppto-dash-tabla-relavera td.col-money {
            width: 78px;
        }
        .exa-dash-tag.tag-mix { background: #bee3f8; color: #2c5282; }
        .exa-dash-tag.tag-driver { background: #4299e1; color: #fff; }
        .exa-dash-tag.tag-fijo { background: #e2e8f0; color: #4a5568; }
        .exa-dash-tag.tag-d8 { background: #fed7d7; color: #c53030; text-decoration: none; }
        .exa-dash-tag.tag-d8:hover { background: #feb2b2; color: #9b2c2c; }
        .exa-dash-tag.tag-formalizar { background: #feebc8; color: #c05621; text-decoration: none; }
        .exa-dash-tag.tag-formalizar:hover { background: #fbd38d; color: #9c4221; }
        .exa-dash-pct-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 10px;
            min-width: 42px;
            text-align: center;
        }
        .exa-dash-export-btn {
            padding: 5px 10px !important;
            font-size: 11px !important;
            line-height: 1.3;
        }
        #ppto_dash_relavera_badge {
            display: none;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 12px;
            background: #feebc8;
            color: #c05621;
            border: 1px solid #f6ad55;
            white-space: nowrap;
        }
        #ppto_dash_sin_proy_hint {
            display: none;
            margin: 0 0 12px;
            padding: 10px 12px;
            font-size: 12px;
            border-radius: 6px;
            background: #ebf8ff;
            border: 1px solid #90cdf4;
            color: #2c5282;
        }
        #ppto_dash_pista_bar {
            display: none;
            margin: 0 0 14px;
            padding: 12px 14px;
            background: #fffaf0;
            border: 1px solid #f6ad55;
            border-radius: 8px;
        }
        #ppto_dash_pista_bar .pista-title {
            font-size: 12px;
            font-weight: 700;
            color: #c05621;
            margin: 0 0 8px;
        }
        #ppto_dash_pista_help {
            font-size: 11px;
            color: #744210;
            margin: 8px 0 0;
            line-height: 1.4;
        }
        body.ppto-dash-modo-relavera .kpi-card-plan-ref {
            opacity: 0.85;
        }
        body.ppto-dash-modo-relavera .kpi-pista-activa .kpi-value {
            color: #2c5282;
        }
        /* Gerente Relavera: mostrar Proyectado/Derecho y Por formalizar; ocultar Comprometido */
        body.ppto-dash-modo-gerente.ppto-dash-modo-relavera .kpi-relavera-gerente-show {
            display: block !important;
        }
        body.ppto-dash-modo-gerente.ppto-dash-modo-relavera .kpi-relavera-hide-comp {
            display: none !important;
        }
        body.ppto-dash-modo-gerente.ppto-dash-modo-relavera.ppto-dash-pista-proy .kpi-relavera-formalizar {
            display: none !important;
        }
        body.ppto-dash-modo-gerente.ppto-dash-modo-relavera.ppto-dash-pista-real .kpi-relavera-formalizar {
            display: block !important;
        }
        #modal_formalizar.exa-pre-modal-overlay { z-index: 10060; display: none; }
        #modal_formalizar .exa-pre-modal-box {
            width: 94%;
            max-width: 920px;
            margin: 2.5% auto;
            padding: 14px 16px 10px;
        }
        #modal_formalizar .ppto-dash-modal-iframe-wrap {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
            background: #fff;
            height: min(58vh, 420px);
        }
        #modal_formalizar_iframe {
            display: block;
            width: 100%;
            height: 100%;
            border: 0;
            background: #fff;
        }
        #modal_formalizar .ppto-dash-modal-foot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            font-size: 12px;
            color: #718096;
        }
        a.exa-dash-open-reajuste { cursor: pointer; }
    </style>
</head>
<body class="exa-ui-fill-root ppto-dash-modo-<?php echo htmlspecialchars($filtros['modo_ux']); ?>">

<div class="panel panel-main exa-ui-panel exa-ui-fill-page">
    <div class="panel-heading exa-header exa-header-flex">
        <h3 class="panel-title"><i class="bi bi-speedometer2"></i> Dashboard Control Presupuestario y de Producci&oacute;n</h3>
        <div class="exa-header-actions" style="display:flex;align-items:center;gap:10px;">
            <span id="ppto_dash_relavera_badge"<?php echo $dash_inicial_relavera ? ' style="display:inline-block;"' : ''; ?>><i class="bi bi-recycle"></i> Modo Relavera (reinversi&oacute;n)</span>
            <span class="text-muted" style="font-size:12px;">EXA ERP Financial BI Engine</span>
        </div>
    </div>
    
    <div class="panel-body exa-body" style="padding: 20px;">
        <!-- SECCION DE FILTROS -->
        <div class="filter-section">
            <form id="form_filtros" class="exa-dash-filter-form">
                <input type="hidden" name="emp_id" id="emp_id" value="<?php echo (int)$filtros['emp_id']; ?>" />
                <div class="exa-dash-filter-grid">
                    <div class="exa-dash-filter-field">
                        <label for="ani">A&ntilde;o fiscal</label>
                        <select name="ani" id="ani" class="form-control input-sm">
                            <?php
                            $anio_actual = (int)date('Y');
                            for ($y = $anio_actual - 3; $y <= $anio_actual + 5; $y++) {
                                echo '<option value="' . $y . '" ' . ($y == $filtros['anio'] ? 'selected' : '') . '>' . $y . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="exa-dash-filter-field">
                        <label for="proy_id">Proyecto</label>
                        <select name="proy_id" id="proy_id" class="form-control input-sm">
                            <option value="">Todos los proyectos</option>
                            <?php foreach ($proyectos as $p): ?>
                                <option value="<?php echo htmlspecialchars($p['proy_id']); ?>" <?php echo ($p['proy_id'] === $filtros['proy_id'] ? 'selected' : ''); ?>>
                                    <?php echo htmlspecialchars($p['proy_nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="exa-dash-filter-field">
                        <label for="ver">Versi&oacute;n</label>
                        <select name="ver" id="ver" class="form-control input-sm">
                            <?php if (empty($versiones)): ?>
                                <option value="">Sin versi&oacute;n activa</option>
                            <?php else: ?>
                                <?php foreach ($versiones as $v): ?>
                                    <option value="<?php echo $v['ppe_id']; ?>" <?php echo ($v['ppe_id'] == $filtros['ppe_id'] ? 'selected' : ''); ?>>
                                        V<?php echo $v['ppe_version']; ?> &middot; <?php echo htmlspecialchars($v['ppe_descripcion']); ?><?php echo ($v['ppe_estado'] === 'A' ? ' [Activa]' : ''); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="exa-dash-filter-field">
                        <label for="auto_refresh">Auto-refresh</label>
                        <select id="auto_refresh" class="form-control input-sm">
                            <option value="0">Off</option>
                            <option value="1">1 min</option>
                            <option value="5" selected>5 min</option>
                            <option value="10">10 min</option>
                        </select>
                    </div>
                    <div class="exa-dash-filter-field exa-dash-filter-field-periodo">
                        <label>Periodo</label>
                        <div class="exa-dash-periodo-bar">
                            <div class="exa-dash-segment" id="seg_periodo_vista">
                                <?php
                                $pv = isset($filtros['periodo_vista']) ? $filtros['periodo_vista'] : 'acumulado';
                                ?>
                                <button type="button" class="exa-dash-segment-btn<?php echo ($pv === 'anual' ? ' active' : ''); ?>" data-periodo="anual">Anual</button>
                                <button type="button" class="exa-dash-segment-btn<?php echo ($pv === 'acumulado' ? ' active' : ''); ?>" data-periodo="acumulado">Acumulado</button>
                                <button type="button" class="exa-dash-segment-btn<?php echo ($pv === 'mes' ? ' active' : ''); ?>" data-periodo="mes">Mes</button>
                            </div>
                            <select name="mes" id="mes" class="form-control input-sm"<?php echo ($pv === 'anual' ? ' style="display:none;"' : ''); ?>>
                                <?php
                                $meses_nombres = array(
                                    1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
                                    7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
                                );
                                $mes_sel = $filtros['mes'] !== null ? (int)$filtros['mes'] : (int)date('n');
                                foreach ($meses_nombres as $num => $nom) {
                                    echo '<option value="' . $num . '" ' . ($num === $mes_sel ? 'selected' : '') . '>' . $nom . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <input type="hidden" name="periodo_vista" id="periodo_vista" value="<?php echo htmlspecialchars($pv); ?>" />
                    </div>
                    <div class="exa-dash-filter-field exa-dash-filter-btn">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-primary btn-sm" id="btn_recargar" title="Recargar datos">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>
                <div class="exa-dash-filter-footer">
                    <span class="exa-dash-filter-footer-label">Modo</span>
                    <div class="exa-dash-segment" id="seg_modo_ux">
                        <button type="button" class="exa-dash-segment-btn<?php echo ($filtros['modo_ux'] === 'gerente' ? ' active' : ''); ?>" data-modo="gerente">Gerente</button>
                        <button type="button" class="exa-dash-segment-btn<?php echo ($filtros['modo_ux'] === 'tecnico' ? ' active' : ''); ?>" data-modo="tecnico">T&eacute;cnico</button>
                    </div>
                    <input type="radio" name="modo_ux_ui" value="gerente" <?php echo ($filtros['modo_ux'] === 'gerente' ? 'checked="checked"' : ''); ?> style="display:none;" />
                    <input type="radio" name="modo_ux_ui" value="tecnico" <?php echo ($filtros['modo_ux'] === 'tecnico' ? 'checked="checked"' : ''); ?> style="display:none;" />
                    <span class="periodo-lbl" id="periodo_label_hint"></span>
                </div>
                <input type="hidden" name="vista_partidas" id="vista_partidas" value="<?php echo htmlspecialchars($filtros['vista_partidas']); ?>" />
                <input type="hidden" name="modo_ux" id="modo_ux" value="<?php echo htmlspecialchars($filtros['modo_ux']); ?>" />
            </form>
        </div>

        <div id="ppto_dash_sin_proy_hint">
            <strong>Dashboard Relavera:</strong> seleccione en el filtro un proyecto configurado con origen <em>Relavera</em>
            para ver columnas <em>Dcho. real</em> y <em>A formalizar</em>.
        </div>

        <!-- BLOQUE B: PRESUPUESTO -->
        <div class="panel panel-default exa-ui-panel" style="margin-bottom: 20px; border-color:#cbd5e0;">
            <div class="panel-heading" style="background-color:#ebf8ff; border-bottom:1px solid #cbd5e0; padding:10px 15px;">
                <h5 style="margin:0; font-weight:700; color:#2c5282;"><i class="bi bi-wallet2 text-primary"></i> Bloque B &mdash; Presupuesto (USD)</h5>
            </div>
            <div class="panel-body" style="padding: 14px 15px;">
        <div class="kpi-row kpi-row-presupuesto">
            <div class="kpi-tecnico-only">
                <div class="kpi-card">
                    <div class="kpi-indicator"><i class="bi bi-cash-stack text-info"></i></div>
                    <div class="kpi-title" id="lbl_kpi_inicial" title="Monto del presupuesto original antes de reajustes">Ppto. original</div>
                    <div class="kpi-value" id="kpi_inicial">$0.00</div>
                </div>
            </div>
            <div class="kpi-tecnico-only">
                <div class="kpi-card">
                    <div class="kpi-indicator"><i class="bi bi-journals text-warning"></i></div>
                    <div class="kpi-title" id="lbl_kpi_reajustes" title="Suma de incrementos, reducciones y transferencias">Reajustes netos</div>
                    <div class="kpi-value" id="kpi_reajustes">$0.00</div>
                </div>
            </div>
            <div>
                <div class="kpi-card">
                    <div class="kpi-indicator"><i class="bi bi-wallet2 text-primary"></i></div>
                    <div class="kpi-title" id="lbl_kpi_vigente" title="Presupuesto vigente despues de reajustes">Presupuesto aprobado</div>
                    <div class="kpi-value" id="kpi_vigente">$0.00</div>
                </div>
            </div>
            <div class="kpi-relavera-hide-comp">
                <div class="kpi-card">
                    <div class="kpi-indicator"><i class="bi bi-lock text-muted"></i></div>
                    <div class="kpi-title" id="lbl_kpi_comprometido" title="OC, contratos y documentos reservados sin pagar">Comprometido (reservado)</div>
                    <div class="kpi-value" id="kpi_comprometido">$0.00</div>
                </div>
            </div>
            <div>
                <div class="kpi-card">
                    <div class="kpi-indicator"><i class="bi bi-cart-check text-success"></i></div>
                    <div class="kpi-title" id="lbl_kpi_ejecutado" title="Pagos y egresos ya registrados">Ya gastado</div>
                    <div class="kpi-value" id="kpi_ejecutado">$0.00</div>
                </div>
            </div>
            <div>
                <div class="kpi-card">
                    <div class="kpi-indicator" id="semaforo_kpi"><span class="semaforo-indicator semaforo-bg-verde"></span></div>
                    <div class="kpi-title" id="kpi_disponible_title" title="Saldo segun plan vigente">Saldo disponible (plan)</div>
                    <div class="kpi-pct" id="kpi_disponible_pct"></div>
                    <div class="kpi-value" id="kpi_disponible">$0.00</div>
                </div>
            </div>
            <div class="kpi-tecnico-only kpi-relavera-gerente-show">
                <div class="kpi-card">
                    <div class="kpi-indicator"><i class="bi bi-graph-up text-info"></i></div>
                    <div class="kpi-title" id="lbl_kpi_pf" title="Presupuesto forecast segun produccion">Ppto. forecast</div>
                    <div class="kpi-value" id="kpi_pf">$0.00</div>
                </div>
            </div>
            <div class="kpi-tecnico-only kpi-relavera-gerente-show kpi-relavera-formalizar">
                <div class="kpi-card">
                    <div class="kpi-indicator" id="semaforo_kpi_forecast"><span class="semaforo-indicator semaforo-bg-verde"></span></div>
                    <div class="kpi-title" id="kpi_disp_forecast_title" title="Saldo segun forecast de produccion">Disp. forecast</div>
                    <div class="kpi-pct" id="kpi_disp_forecast_pct"></div>
                    <div class="kpi-value" id="kpi_disp_forecast">$0.00</div>
                </div>
            </div>
        </div>
            </div>
        </div>

        <!-- BLOQUE A: PRODUCCION FISICA -->
        <div class="panel panel-default exa-ui-panel" style="margin-bottom: 20px; border-color:#cbd5e0;">
            <div class="panel-heading" style="background-color:#edf2f7; border-bottom:1px solid #cbd5e0; padding:10px 15px;">
                <h5 style="margin:0; font-weight:700; color:#2d3748;"><i class="bi bi-activity text-primary"></i> Bloque A &mdash; Produccion Fisica Relacionada</h5>
            </div>
            <div class="panel-body" style="padding: 14px 15px;">
                <div class="kpi-row kpi-row-prod">
                    <div>
                        <div class="kpi-card kpi-card-prod">
                            <div class="kpi-title" id="lbl_prod_esperada" title="Meta de produccion planificada">Meta planificada</div>
                            <div class="kpi-value" id="prod_esperada">0.00 Ton</div>
                        </div>
                    </div>
                    <div>
                        <div class="kpi-card kpi-card-prod">
                            <div class="kpi-title" id="lbl_prod_real" title="Produccion real registrada">Producido (real)</div>
                            <div class="kpi-value" id="prod_real">0.00 Ton</div>
                        </div>
                    </div>
                    <div>
                        <div class="kpi-card kpi-card-prod">
                            <div class="kpi-title" id="lbl_prod_proyectada" title="Proyeccion al cierre del periodo">Proyectado al cierre</div>
                            <div class="kpi-value" id="prod_proyectada">0.00 Ton</div>
                        </div>
                    </div>
                    <div>
                        <div class="kpi-card kpi-card-prod kpi-prod-var">
                            <div class="kpi-title" id="lbl_prod_var" title="Diferencia entre real/proyectado y meta">Diferencia vs meta</div>
                            <div class="kpi-value">
                                <span id="prod_var_absoluta">+0.00 Ton</span>
                                <span class="badge label-success" style="font-size:11px; margin-left:6px; vertical-align:middle;" id="prod_var_porcentual">+0.00%</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="kpi-row kpi-row-2 kpi-tecnico-only" style="margin-top:12px;">
                    <div>
                        <div class="kpi-card">
                            <div class="kpi-title" id="lbl_prod_desv_forecast" title="Desviacion meta vs forecast">Desv. fisica (meta vs forecast)</div>
                            <div class="kpi-value" style="font-size:clamp(14px,1.4vw,18px);" id="prod_desv_meta_forecast">+0.00 Ton (0.00%)</div>
                        </div>
                    </div>
                    <div>
                        <div class="kpi-card">
                            <div class="kpi-title" title="Comparacion de la proyectada vs el real en meses cerrados">Proyectada vs real</div>
                            <div class="kpi-value" style="font-size:clamp(14px,1.4vw,18px);">
                                <span class="badge" id="d2_alineado_sn" style="font-size:12px;">S</span>
                                <span style="font-size:11px; margin-left:8px; color:#718096; font-weight:600;" id="d2_alineado_det">?</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="d2_warning_banner" class="alert alert-warning exa-dash-alert-compact kpi-tecnico-only" style="display:none; margin-top:12px; margin-bottom:0; padding:0; border:none;">
                    <div class="alert-head" onclick="pptoDashToggleAlert(this)">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <strong id="d2_warning_summary">Divergencia D2</strong>
                        <span class="alert-toggle"></span>
                    </div>
                    <div class="alert-body" id="d2_warning_body"></div>
                </div>
            </div>
        </div>

        <!-- SECCI?N DE REPORTES TABULARES -->
        <div class="exa-dash-report-toolbar">
            <div class="tab-navigation">
                <a class="tab-link active" onclick="switchTab(1)" id="tab_btn_partidas"><i class="bi bi-list-columns-reverse"></i> Desglose partidas</a>
                <a class="tab-link" onclick="switchTab(2)" id="tab_btn_mensual"><i class="bi bi-calendar3"></i> Evoluci&oacute;n mensual</a>
            </div>
            <div class="exa-dash-vista-wrap" id="ppto_dash_vista_bar">
                <span class="exa-dash-vista-label">Vista</span>
                <div class="exa-dash-segment" id="seg_vista_partidas">
                    <button type="button" class="exa-dash-segment-btn<?php echo ($filtros['vista_partidas'] === 'jerarquica' ? ' active' : ''); ?>" data-vista="jerarquica">Cap&iacute;tulos</button>
                    <button type="button" class="exa-dash-segment-btn exa-dash-vista-tecnico<?php echo ($filtros['vista_partidas'] === 'plana' ? ' active' : ''); ?>" data-vista="plana">Solo rubros</button>
                </div>
                <input type="radio" name="vista_partidas_ui" value="jerarquica" <?php echo ($filtros['vista_partidas'] === 'jerarquica' ? 'checked="checked"' : ''); ?> style="display:none;" />
                <input type="radio" name="vista_partidas_ui" value="plana" <?php echo ($filtros['vista_partidas'] === 'plana' ? 'checked="checked"' : ''); ?> style="display:none;" />
            </div>
            <div class="exa-dash-toolbar-actions">
                <button class="btn btn-default btn-sm exa-dash-export-btn" onclick="exportarData('excel')">
                    <i class="bi bi-file-earmark-excel-fill text-success"></i> Excel
                </button>
                <button class="btn btn-default btn-sm exa-dash-export-btn" onclick="exportarData('pdf')">
                    <i class="bi bi-file-earmark-pdf-fill text-danger"></i> PDF
                </button>
            </div>
        </div>

        <!-- TAB 1: DESGLOSE POR PARTIDAS -->
        <div id="tab_content_partidas" class="tab-pane-custom active">
            <div id="ppto_dash_pista_bar">
                <div class="pista-title"><i class="bi bi-signpost-split"></i> Control presupuestario Relavera</div>
                <div class="exa-dash-vista-wrap" style="margin:0;">
                    <span class="exa-dash-vista-label">Comparar</span>
                    <div class="exa-dash-segment" id="seg_pista_presupuesto">
                        <button type="button" class="exa-dash-segment-btn active" data-pista="proyectado">Proyectado vs Ejecutado</button>
                        <button type="button" class="exa-dash-segment-btn" data-pista="real">Real vs Ejecutado</button>
                    </div>
                </div>
                <p id="ppto_dash_pista_help">
                    <strong>Proyectado vs Ejecutado:</strong> toneladas <em>proyectadas</em> de producci&oacute;n &times; $/Ton base del rubro; el % disponible es su br&uacute;jula operativa.
                </p>
            </div>
            <div id="d8_alertas_banner" class="alert alert-danger exa-dash-alert-compact kpi-tecnico-only" style="display:none; margin-bottom:10px; padding:0; border:none;">
                <div class="alert-head" onclick="pptoDashToggleAlert(this)">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong id="d8_alertas_summary">Alertas de presupuesto proyectado</strong>
                    <span class="alert-toggle"></span>
                </div>
                <div class="alert-body" id="d8_alertas_body">
                    <p class="exa-dash-leyenda-tags" style="margin:8px 0 4px;">
                        <strong>Proyectado</strong> = toneladas proyectadas &times; $/Ton base del rubro.
                        <strong>Aprobado</strong> = presupuesto vigente del plan.
                        <strong>Exceso</strong> = supera lo aprobado m&aacute;s el margen permitido.
                    </p>
                    <div id="d8_alertas_list"></div>
                </div>
            </div>
            <div id="ppto_dash_leyenda_tags" class="exa-dash-leyenda-tags kpi-tecnico-only">
                Etiquetas: <span class="exa-dash-tag tag-mix">Mixto</span> cap&iacute;tulo con rubros variables y fijos;
                <span class="exa-dash-tag tag-driver">Toneladas</span> calculado por producci&oacute;n;
                <span class="exa-dash-tag tag-fijo">Fijo</span> monto constante;
                <span class="exa-dash-tag tag-d8">Exceso</span> requiere reajuste presupuestario.
            </div>
            <div id="ppto_dash_ayuda_jerarquica" class="exa-dash-ayuda">
                Filas azules = cap&iacute;tulos (suma de rubros hijos).
            </div>
            <div class="exa-adq-table-wrap" id="ppto_dash_partidas_wrap">
                <table class="table table-bordered table-striped exa-adq-table" id="tabla_partidas">
                    <thead id="thead_partidas">
                        <tr style="background-color:#edf2f7; color:#2d3748;"></tr>
                    </thead>
                    <tbody id="tbody_partidas">
                        <tr>
                            <td colspan="11" class="text-center text-muted" style="padding: 40px;">
                                <i class="bi bi-hourglass-split"></i> Cargando resumen de partidas...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 2: EVOLUCION MENSUAL -->
        <div id="tab_content_mensual" class="tab-pane-custom" style="display: none;">
            <p class="text-muted" style="font-size:12px;margin:0 0 10px;">
                <strong>Proyectada mes:</strong> desde abril, promedio real de los 3 meses anteriores; si no hay real, mantiene la ultima proyectada.
                <strong>Acumulados:</strong> cuanto llevas vs cuanto proyectabas al cierre de ese mes.
                <strong>Avance %</strong> = Real acum. / Proy. acum. (solo meses con dato real).
            </p>
            <div class="exa-adq-table-wrap">
                <table class="table table-bordered table-striped exa-adq-table" id="tabla_mensual" style="font-size:12px;">
                    <thead>
                        <tr style="background-color:#edf2f7; color:#2d3748;">
                            <th class="text-center" rowspan="2" style="vertical-align:middle;">Mes</th>
                            <th class="text-center" colspan="3" style="border-bottom:1px solid #cbd5e0;">Toneladas del mes</th>
                            <th class="text-center" colspan="3" style="border-bottom:1px solid #cbd5e0;">Acumulado al mes</th>
                            <th class="text-center" rowspan="2" style="vertical-align:middle;">Avance %</th>
                            <th class="text-center" rowspan="2" style="vertical-align:middle;">Presup. segun proy.</th>
                            <th class="text-center" rowspan="2" style="vertical-align:middle;">Desv. mes</th>
                        </tr>
                        <tr style="background-color:#edf2f7; color:#2d3748;">
                            <th class="text-right">Plan</th>
                            <th class="text-right">Proyectada</th>
                            <th class="text-right">Real</th>
                            <th class="text-right">Real acum.</th>
                            <th class="text-right">Proy. acum.</th>
                            <th class="text-right">Dif. acum.</th>
                        </tr>
                    </thead>
                    <tbody id="tbody_mensual">
                        <tr>
                            <td colspan="10" class="text-center text-muted" style="padding: 40px;">
                                <i class="bi bi-hourglass-split"></i> Cargando evolucion mensual...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- panel-body -->
</div><!-- panel -->

<script>
    var pptoDashUltimasPartidas = [];
    var pptoDashModoReinversion = false;
    var pptoDashPistaPresupuesto = 'proyectado';
    var pptoDashUltimasAlertasFormalizar = [];
    var pptoDashUltimasAlertasD8 = [];

    function pptoDashGetPista() {
        return (pptoDashPistaPresupuesto === 'real') ? 'real' : 'proyectado';
    }

    function pptoDashSetPista(pista) {
        pptoDashPistaPresupuesto = (pista === 'real') ? 'real' : 'proyectado';
        try {
            if (window.localStorage) {
                localStorage.setItem('ppto_dash_pista_presupuesto', pptoDashPistaPresupuesto);
            }
        } catch (e) {}
    }

    function pptoDashPctBadge(vig, disp) {
        var pctVal = (parseFloat(vig) > 0.0001)
            ? Math.max(0, Math.min(100, (parseFloat(disp) / parseFloat(vig)) * 100))
            : 0;
        var pctBadgeClass = 'badge badge-success';
        if (pctVal < 10) pctBadgeClass = 'badge badge-danger';
        else if (pctVal < 25) pctBadgeClass = 'badge badge-warning';
        return '<span class="exa-dash-pct-badge ' + pctBadgeClass.replace('badge ', '') + '">' + formatNumber(pctVal, 2) + '%</span>';
    }

    function pptoDashColCountPartidas(modo) {
        modo = modo || pptoDashGetModoUx();
        if (pptoDashModoReinversion) {
            var pista = pptoDashGetPista();
            if (modo === 'gerente') {
                return (pista === 'real') ? 7 : 6;
            }
            return (pista === 'real') ? 8 : 7;
        }
        return (modo === 'gerente') ? 6 : 11;
    }

    jQuery(document).ready(function($) {
        try {
            if (window.localStorage && !/[?&]modo_ux=/.test(window.location.search)) {
                var stored = localStorage.getItem('ppto_dash_modo_ux');
                if (stored === 'tecnico' || stored === 'gerente') {
                    $('#modo_ux').val(stored);
                }
            }
            if (window.localStorage) {
                var storedPista = localStorage.getItem('ppto_dash_pista_presupuesto');
                if (storedPista === 'real' || storedPista === 'proyectado') {
                    pptoDashPistaPresupuesto = storedPista;
                }
            }
        } catch (e) {}
        pptoDashSyncPistaUi();
        pptoDashAplicarModoUx($('#modo_ux').val(), true);
        cargarDashboard();

        $('#seg_pista_presupuesto .exa-dash-segment-btn').on('click', function() {
            pptoDashCambiarPista($(this).data('pista'));
        });

        $('#seg_periodo_vista .exa-dash-segment-btn').on('click', function() {
            pptoDashCambiarPeriodoVista($(this).data('periodo'));
        });

        $('#seg_modo_ux .exa-dash-segment-btn').on('click', function() {
            pptoDashCambiarModoUx($(this).data('modo'));
        });
        $('#seg_vista_partidas .exa-dash-segment-btn').on('click', function() {
            pptoDashCambiarVistaPartidas($(this).data('vista'));
        });

        // Enlazar eventos de filtros
        $('#ani, #proy_id, #ver, #mes').on('change', function() {
            var triggerId = $(this).attr('id');
            if (triggerId === 'ani') {
                actualizarCatalogosFiltros();
            } else {
                cargarDashboard();
            }
        });
        pptoDashSyncMesVisibility($('#periodo_vista').val() || 'acumulado');
        $('body').toggleClass('ppto-dash-pista-real', pptoDashGetPista() === 'real');
        $('body').toggleClass('ppto-dash-pista-proy', pptoDashGetPista() !== 'real');

        $('#btn_recargar').on('click', function() {
            cargarDashboard();
        });

        // MECANISMO DE AUTO-REFRESH (Fase 7)
        var refreshInterval = null;
        function iniciarAutoRefresh() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
            var mins = parseInt($('#auto_refresh').val());
            if (mins > 0) {
                var ms = mins * 60 * 1000;
                refreshInterval = setInterval(function() {
                    console.log('Auto-refresh gatillado: ' + new Date().toLocaleTimeString());
                    cargarDashboard();
                }, ms);
            }
        }

        // Iniciar auto-refresh por defecto (5 min)
        iniciarAutoRefresh();

        // Escuchar cambios en la opci?n de auto-refresh
        $('#auto_refresh').on('change', function() {
            iniciarAutoRefresh();
        });
    });

    /**
     * Devuelve modo UX activo (gerente | tecnico).
     */
    function pptoDashGetModoUx() {
        var m = $('#modo_ux').val();
        return (m === 'tecnico') ? 'tecnico' : 'gerente';
    }

    function pptoDashToggleAlert(headEl) {
        $(headEl).closest('.exa-dash-alert-compact').toggleClass('open');
    }

    function pptoDashSyncSegmento($seg, val, attr) {
        $seg.find('.exa-dash-segment-btn').removeClass('active');
        $seg.find('.exa-dash-segment-btn[' + attr + '="' + val + '"]').addClass('active');
    }

    function pptoDashTag(text, cls, title) {
        return '<span class="exa-dash-tag ' + cls + '" title="' + htmlspecialchars(title || '') + '">' + text + '</span>';
    }

    /**
     * Aplica modo Gerente/Tecnico: clases body, encabezados, etiquetas KPI y ayuda.
     * @param {string} modo
     * @param {boolean} sinAnimacion Si true, no recarga datos (solo UI).
     */
    function pptoDashAplicarModoUx(modo, sinAnimacion) {
        modo = (modo === 'tecnico') ? 'tecnico' : 'gerente';
        $('#modo_ux').val(modo);
        $('input[name="modo_ux_ui"][value="' + modo + '"]').prop('checked', true);
        $('body').removeClass('ppto-dash-modo-gerente ppto-dash-modo-tecnico').addClass('ppto-dash-modo-' + modo);
        pptoDashSyncSegmento($('#seg_modo_ux'), modo, 'data-modo');
        $('input[name="modo_ux_ui"][value="' + modo + '"]').prop('checked', true);

        if (modo === 'gerente') {
            $('#vista_partidas').val('jerarquica');
            $('input[name="vista_partidas_ui"][value="jerarquica"]').prop('checked', true);
            pptoDashSyncSegmento($('#seg_vista_partidas'), 'jerarquica', 'data-vista');
            $('#ppto_dash_ayuda_jerarquica').show();
            $('#lbl_kpi_vigente').text('Presupuesto aprobado');
            $('#lbl_kpi_comprometido').text('Comprometido (reservado)');
            $('#lbl_kpi_ejecutado').text('Ya gastado');
            $('#lbl_prod_esperada').text('Meta planificada');
            $('#lbl_prod_real').text('Producido (real)');
            $('#lbl_prod_var').text('Diferencia vs meta');
        } else {
            $('#ppto_dash_ayuda_jerarquica').hide();
            $('#lbl_kpi_inicial').text('Ppto. original');
            $('#lbl_kpi_reajustes').text('Reajustes netos');
            $('#lbl_kpi_vigente').text('Ppto. vigente');
            $('#lbl_kpi_comprometido').text('Comprometido');
            $('#lbl_kpi_ejecutado').text('Ejecutado real');
            $('#lbl_prod_esperada').text('Esperado planificado');
            $('#lbl_prod_real').text('Real registrado');
            $('#lbl_prod_var').text('Desviacion YTD vs proyectado');
        }

        pptoDashRenderTheadPartidas(modo);

        if (!sinAnimacion && pptoDashUltimasPartidas.length > 0) {
            pptoDashRenderFilasPartidas(pptoDashUltimasPartidas);
        }

        try {
            if (window.localStorage) {
                localStorage.setItem('ppto_dash_modo_ux', modo);
            }
        } catch (e) {}
    }

    /**
     * Cambia modo UX y recarga datos si hace falta.
     */
    function pptoDashCambiarModoUx(modo) {
        var prev = pptoDashGetModoUx();
        pptoDashAplicarModoUx(modo, false);
        if (prev !== modo || pptoDashUltimasPartidas.length === 0) {
            cargarDashboard();
        } else {
            pptoDashRenderFilasPartidas(pptoDashUltimasPartidas);
        }
    }

    function pptoDashSyncTablaRelaveraClass(modo) {
        modo = modo || pptoDashGetModoUx();
        var $tabla = $('#tabla_partidas');
        if (pptoDashModoReinversion && modo === 'tecnico') {
            $tabla.addClass('ppto-dash-tabla-relavera');
        } else {
            $tabla.removeClass('ppto-dash-tabla-relavera');
        }
    }

    function pptoDashSyncPistaUi() {
        var pista = pptoDashGetPista();
        pptoDashSyncSegmento($('#seg_pista_presupuesto'), pista, 'data-pista');
        if (pista === 'real') {
            $('#ppto_dash_pista_help').html(
                '<strong>Real vs Ejecutado:</strong> derecho = <em>solo toneladas reales</em> del periodo '
                + '(sin completar con proyectada). Use <em>Por formalizar</em> para registrar el reajuste del mes.'
            );
        } else {
            $('#ppto_dash_pista_help').html(
                '<strong>Proyectado vs Ejecutado:</strong> toneladas <em>proyectadas</em> &times; $/Ton (br&uacute;jula operativa). '
                + 'El Asignado formal sigue siendo el techo PDF.'
            );
        }
    }

    function pptoDashCambiarPista(pista) {
        var prev = pptoDashGetPista();
        pptoDashSetPista(pista);
        $('body').toggleClass('ppto-dash-pista-real', pista === 'real');
        $('body').toggleClass('ppto-dash-pista-proy', pista !== 'real');
        pptoDashSyncPistaUi();
        if (prev !== pista) {
            pptoDashRenderTheadPartidas(pptoDashGetModoUx());
            if (pptoDashUltimasPartidas.length > 0) {
                pptoDashRenderFilasPartidas(pptoDashUltimasPartidas);
            }
            pptoDashRefreshAlertasPista();
            if (pptoDashUltimosKpis) {
                pptoDashAplicarKpisRelavera(pptoDashUltimosKpis, pptoDashGetModoUx());
            }
        }
    }

    var pptoDashUltimosKpis = null;

    function pptoDashAplicarKpisRelavera(kpis, modo) {
        if (!pptoDashModoReinversion || !kpis) return;
        var pista = pptoDashGetPista();
        $('body').toggleClass('ppto-dash-pista-real', pista === 'real');
        $('body').toggleClass('ppto-dash-pista-proy', pista !== 'real');

        var proy = parseFloat(kpis.presupuesto_proyectado) || 0;
        var derecho = parseFloat(kpis.presupuesto_por_real) || 0;
        var porForm = parseFloat(kpis.por_formalizar_total) || 0;
        var dispProy = parseFloat(kpis.disponible_proyectado) || 0;
        var dispFormal = parseFloat(kpis.disponible_formal != null ? kpis.disponible_formal : kpis.disponible_plan) || 0;
        var dispProyPct = parseFloat(kpis.disponible_proyectado_porcentaje) || 0;
        var dispFormalPct = parseFloat(kpis.disponible_formal_porcentaje != null
            ? kpis.disponible_formal_porcentaje
            : kpis.disponible_por_real_porcentaje) || 0;
        var semProy = kpis.semaforo_proyectado || 'verde';
        var semFormal = kpis.semaforo_formal || kpis.semaforo_por_real || 'verde';

        $('#lbl_kpi_vigente').text('Asignado formal');
        $('#kpi_vigente').text(formatCurrency(kpis.asignado_formal != null ? kpis.asignado_formal : kpis.presupuesto_vigente));
        $('#lbl_kpi_pf').text(pista === 'real' ? 'Derecho por real' : 'Proyectado');
        $('#kpi_pf').text(formatCurrency(pista === 'real' ? derecho : proy));

        function paintSem($pct, $sem, sem, pctVal) {
            var pctColor = '#48bb78';
            if (sem === 'amarillo') pctColor = '#d69e2e';
            else if (sem === 'rojo') pctColor = '#e53e3e';
            var colorClass = 'semaforo-bg-verde';
            if (sem === 'amarillo') colorClass = 'semaforo-bg-amarillo';
            else if (sem === 'rojo') colorClass = 'semaforo-bg-rojo';
            $pct.text(formatNumber(pctVal, 2) + '% disponible').css('color', pctColor);
            $sem.empty().append('<span class="semaforo-indicator ' + colorClass + '"></span>');
        }

        if (modo === 'gerente') {
            if (pista === 'real') {
                $('#kpi_disponible_title').text('Saldo formal');
                $('#kpi_disponible').text(formatCurrency(dispFormal));
                paintSem($('#kpi_disponible_pct'), $('#semaforo_kpi'), semFormal, dispFormalPct);
                $('#kpi_disp_forecast_title').text('Por formalizar');
                $('#kpi_disp_forecast').text(formatCurrency(porForm));
                $('#kpi_disp_forecast_pct').text(porForm > 0 ? 'pendiente de reajuste' : 'al dia');
                $('#kpi_disp_forecast_pct').css('color', porForm > 0 ? '#d69e2e' : '#48bb78');
                $('#semaforo_kpi_forecast').empty().append(
                    '<span class="semaforo-indicator ' + (porForm > 0 ? 'semaforo-bg-amarillo' : 'semaforo-bg-verde') + '"></span>'
                );
            } else {
                $('#kpi_disponible_title').text('Saldo proyectado');
                $('#kpi_disponible').text(formatCurrency(dispProy));
                paintSem($('#kpi_disponible_pct'), $('#semaforo_kpi'), semProy, dispProyPct);
            }
        } else {
            var dispPlan = parseFloat(kpis.disponible_plan != null ? kpis.disponible_plan : kpis.disponible) || 0;
            var dispPlanPct = parseFloat(kpis.disponible_plan_porcentaje != null ? kpis.disponible_plan_porcentaje : kpis.disponible_porcentaje) || 0;
            var semPlan = kpis.semaforo_plan || kpis.semaforo || 'verde';
            $('#kpi_disponible_title').text('Disp. plan');
            $('#kpi_disponible').text(formatCurrency(dispPlan));
            paintSem($('#kpi_disponible_pct'), $('#semaforo_kpi'), semPlan, dispPlanPct);

            if (pista === 'real') {
                $('#kpi_disp_forecast_title').text('Por formalizar');
                $('#kpi_disp_forecast').text(formatCurrency(porForm));
                $('#kpi_disp_forecast_pct').text(porForm > 0 ? 'pendiente de reajuste' : 'al dia');
                $('#kpi_disp_forecast_pct').css('color', porForm > 0 ? '#d69e2e' : '#48bb78');
            } else {
                $('#kpi_disp_forecast_title').text('Disp. proyectado');
                $('#kpi_disp_forecast').text(formatCurrency(dispProy));
                paintSem($('#kpi_disp_forecast_pct'), $('#semaforo_kpi_forecast'), semProy, dispProyPct);
            }
        }

        if (kpis.periodo_label) {
            $('#periodo_label_hint').html('<strong>' + htmlspecialchars(kpis.periodo_label) + '</strong>');
        }
        if (kpis.periodo_vista) {
            $('#periodo_vista').val(kpis.periodo_vista);
            pptoDashSyncSegmento($('#seg_periodo_vista'), kpis.periodo_vista, 'data-periodo');
            pptoDashSyncMesVisibility(kpis.periodo_vista);
        }
        if (kpis.periodo_mes) {
            $('#mes').val(String(kpis.periodo_mes));
        }
    }

    function pptoDashSyncMesVisibility(vista) {
        if (vista === 'anual') {
            $('#mes').hide();
        } else {
            $('#mes').show();
        }
    }

    function pptoDashCambiarPeriodoVista(vista) {
        vista = (vista === 'anual' || vista === 'mes') ? vista : 'acumulado';
        $('#periodo_vista').val(vista);
        pptoDashSyncSegmento($('#seg_periodo_vista'), vista, 'data-periodo');
        pptoDashSyncMesVisibility(vista);
        cargarDashboard();
    }

    function pptoDashPaintAlertasFormalizar(alertas) {
        alertas = alertas || pptoDashUltimasAlertasFormalizar || [];
        var itemsF = '';
        $.each(alertas, function(i, a) {
            var nom = a.descripcion ? htmlspecialchars(a.descripcion) : 'Sin descripcion';
            var metrics = 'Derecho ' + formatCurrency(a.derecho || a.pf) +
                ' &middot; Asignado ' + formatCurrency(a.va) +
                ' &middot; Por formalizar ' + formatCurrency(a.por_formalizar || a.exceso);
            itemsF += '<div class="exa-dash-alert-item">' +
                '<div class="exa-dash-alert-item-text">' +
                '<a href="' + htmlspecialchars(a.url_reajuste) + '" class="exa-dash-alert-item-name exa-dash-open-reajuste" data-titulo="Formalizar ' + htmlspecialchars(a.codigo || '') + '">' +
                htmlspecialchars(a.codigo) + ' &mdash; ' + nom +
                '</a>' +
                '<div class="exa-dash-alert-item-metrics">' + metrics + '</div>' +
                '</div>' +
                '<a href="' + htmlspecialchars(a.url_reajuste) + '" class="exa-dash-alert-link exa-dash-open-reajuste" data-titulo="Formalizar ' + htmlspecialchars(a.codigo || '') + '">Formalizar</a>' +
                '</div>';
        });
        $('#d8_alertas_list').html(itemsF);
    }

    function pptoDashRefreshAlertasPista() {
        var $d8 = $('#d8_alertas_banner');
        if (!pptoDashModoReinversion) return;
        if (pptoDashGetPista() === 'real' && pptoDashUltimasAlertasFormalizar.length > 0) {
            $('#d8_alertas_summary').text(
                pptoDashUltimasAlertasFormalizar.length + ' partida(s): derecho por real supera lo asignado'
            );
            // No vaciar: al cambiar de pista se reaprovecha el listado ya cargado.
            if (!$('#d8_alertas_list').children().length) {
                pptoDashPaintAlertasFormalizar(pptoDashUltimasAlertasFormalizar);
            }
            $d8.removeClass('open').show();
        } else {
            $d8.hide().removeClass('open');
        }
    }

    /**
     * Construye encabezado de tabla segun modo.
     */
    function pptoDashRenderTheadPartidas(modo) {
        modo = modo || pptoDashGetModoUx();
        var tip = ' class="exa-dash-th-tip"';
        var thMoney = ' class="text-right col-money exa-dash-th-tip"';
        var html = '';
        if (pptoDashModoReinversion) {
            var pista = pptoDashGetPista();
            if (modo === 'gerente') {
                if (pista === 'real') {
                    html = '<tr style="background-color:#edf2f7; color:#2d3748;">' +
                        '<th class="text-left" style="width:10%;"' + tip + '>Codigo</th>' +
                        '<th style="width:28%;"' + tip + '>Rubro / capitulo</th>' +
                        '<th class="text-right" style="width:14%;"' + tip + ' title="Solo toneladas reales del periodo (sin proyectada)">Derecho real</th>' +
                        '<th class="text-right" style="width:12%;"' + tip + '>Ya gastado</th>' +
                        '<th class="text-right" style="width:12%;"' + tip + '>Saldo</th>' +
                        '<th class="text-right" style="width:14%;"' + tip + '>Por formalizar</th>' +
                        '<th class="text-center" style="width:10%;"' + tip + '>% disp.</th>' +
                        '</tr>';
                } else {
                    html = '<tr style="background-color:#edf2f7; color:#2d3748;">' +
                        '<th class="text-left" style="width:10%;"' + tip + '>Codigo</th>' +
                        '<th style="width:30%;"' + tip + '>Rubro / capitulo</th>' +
                        '<th class="text-right" style="width:14%;"' + tip + ' title="Toneladas proyectadas x $/Ton base del rubro">Proyectado</th>' +
                        '<th class="text-right" style="width:12%;"' + tip + '>Ya gastado</th>' +
                        '<th class="text-right" style="width:14%;"' + tip + '>Saldo</th>' +
                        '<th class="text-center" style="width:10%;"' + tip + '>% disp.</th>' +
                        '</tr>';
                }
            } else if (pista === 'real') {
                html = '<tr style="background-color:#edf2f7; color:#2d3748;">' +
                    '<th class="text-left col-codigo"' + tip + '>Clasif.</th>' +
                    '<th class="col-desc"' + tip + '>Descripcion</th>' +
                    '<th' + thMoney + ' title="Derecho segun meses cerrados">Derecho real</th>' +
                    '<th' + thMoney + '>Comprom.</th>' +
                    '<th' + thMoney + '>Ejecutado</th>' +
                    '<th' + thMoney + '>Disp. real</th>' +
                    '<th' + thMoney + '>A formalizar</th>' +
                    '<th class="text-center col-pct"' + tip + '>% disp.</th>' +
                    '</tr>';
            } else {
                html = '<tr style="background-color:#edf2f7; color:#2d3748;">' +
                    '<th class="text-left col-codigo"' + tip + '>Clasif.</th>' +
                    '<th class="col-desc"' + tip + '>Descripcion</th>' +
                    '<th' + thMoney + ' title="Toneladas proyectadas x $/Ton base">Proyectado</th>' +
                    '<th' + thMoney + '>Comprom.</th>' +
                    '<th' + thMoney + '>Ejecutado</th>' +
                    '<th' + thMoney + '>Disp. proy.</th>' +
                    '<th class="text-center col-pct"' + tip + '>% disp.</th>' +
                    '</tr>';
            }
        } else if (modo === 'gerente') {
            html = '<tr style="background-color:#edf2f7; color:#2d3748;">' +
                '<th class="text-left" style="width:11%;"' + tip + ' title="Codigo del rubro o capitulo presupuestario">Codigo</th>' +
                '<th style="width:34%;"' + tip + ' title="Nombre del rubro; las filas azules son capitulos con suma de hijos">Rubro / capitulo</th>' +
                '<th class="text-right" style="width:15%;"' + tip + ' title="Monto aprobado vigente (despues de reajustes)">Presupuesto</th>' +
                '<th class="text-right" style="width:14%;"' + tip + ' title="Pagos y egresos ya registrados">Ya gastado</th>' +
                '<th class="text-right" style="width:14%;"' + tip + ' title="Saldo que aun puede utilizarse">Saldo</th>' +
                '<th class="text-center" style="width:12%;"' + tip + ' title="Porcentaje del presupuesto aun disponible">Estado</th>' +
                '</tr>';
        } else {
            html = '<tr style="background-color:#edf2f7; color:#2d3748;">' +
                '<th class="text-left" style="width:140px;"' + tip + ' title="Codigo jerarquico de la partida">Clasificacion</th>' +
                '<th' + tip + ' title="Descripcion de la partida presupuestaria">Descripcion</th>' +
                '<th class="text-right" style="width:110px;"' + tip + ' title="Monto del presupuesto original">Inicial</th>' +
                '<th class="text-right" style="width:110px;"' + tip + ' title="Suma de incrementos, reducciones y transferencias">Reajustes</th>' +
                '<th class="text-right" style="width:100px;"' + tip + ' title="Presupuesto vigente segun plan">Vigente (plan)</th>' +
                '<th class="text-right" style="width:100px;"' + tip + ' title="Presupuesto segun avance de produccion">Segun proy.</th>' +
                '<th class="text-right" style="width:100px;"' + tip + ' title="OC, contratos y documentos reservados sin pagar">Comprometido</th>' +
                '<th class="text-right" style="width:100px;"' + tip + ' title="Pagos y egresos registrados">Ejecutado</th>' +
                '<th class="text-right" style="width:100px;"' + tip + ' title="Saldo disponible segun plan">Disp. plan</th>' +
                '<th class="text-right" style="width:100px;"' + tip + ' title="Saldo disponible segun produccion proyectada">Disp. proy.</th>' +
                '<th class="text-center" style="width:90px;"' + tip + ' title="Sem?foro de control presupuestario">Control (%)</th>' +
                '</tr>';
        }
        $('#thead_partidas').html(html);
        pptoDashSyncTablaRelaveraClass(modo);
    }

    /**
     * Renderiza filas de partidas segun modo UX.
     */
    function pptoDashRenderFilasPartidas(partidas) {
        var modo = pptoDashGetModoUx();
        var cols = pptoDashColCountPartidas(modo);
        var tbodyPartidas = $('#tbody_partidas');
        tbodyPartidas.empty();
        if (!partidas || partidas.length === 0) {
            tbodyPartidas.append('<tr><td colspan="' + cols + '" class="text-center text-muted" style="padding:30px;">Sin afectaciones presupuestarias para los filtros indicados.</td></tr>');
            return;
        }
        $.each(partidas, function(i, r) {
            tbodyPartidas.append(pptoDashRenderFilaPartida(r, modo));
        });
    }

    function pptoDashRenderFilaPartida(r, modo) {
        var badgeClass = 'badge badge-success';
        if (r.semaforo === 'amarillo') badgeClass = 'badge badge-warning';
        else if (r.semaforo === 'rojo') badgeClass = 'badge badge-danger';
        // Nota de naming: nivel global usa presupuesto_proyectado/disponible_forecast;
        // nivel partida usa vigente_proyectado/disponible_proyectado (campos API existentes).
        var vigProy = parseFloat(r.vigente_proyectado);
        var dispProy = parseFloat(r.disponible_proyectado);
        var vigReal = parseFloat(r.vigente_por_real != null ? r.vigente_por_real : r.vigente);
        var dispReal = parseFloat(r.disponible_por_real != null ? r.disponible_por_real : r.disponible);
        var porFormalizar = parseFloat(r.por_formalizar != null ? r.por_formalizar : 0);
        var usaProy = r.muestra_pf === true || r.muestra_pf === 1 || r.muestra_pf === '1'
            || r.rollup_tipo === 'driver' || r.rollup_tipo === 'mixto' || r.rollup_tipo === 'fijo'
            || r.driver_tipo === 'driver' || r.driver_tipo === 'fijo'
            || r.es_tonelada === true || r.es_tonelada === 1 || r.es_tonelada === '1';
        var esGrupo = r.es_grupo === true || r.es_grupo === 1 || r.es_grupo === '1';
        var indent = (parseInt(r.nivel_indent, 10) || 0) * 18;
        var trClass = esGrupo ? 'exa-dash-row-grupo' : 'exa-dash-row-detalle';
        var descHtml = esGrupo
            ? '<strong>' + htmlspecialchars(r.descripcion) + '</strong>'
            : htmlspecialchars(r.descripcion);
        if (modo === 'tecnico') {
            if (r.rollup_mixto) {
                descHtml += pptoDashTag('Mixto', 'tag-mix', 'Capitulo con rubros por toneladas y montos fijos');
            } else if (r.driver_tipo === 'driver') {
                descHtml += pptoDashTag('Toneladas', 'tag-driver', 'Rubro calculado segun produccion (toneladas)');
            } else if (r.driver_tipo === 'fijo' && !esGrupo) {
                descHtml += pptoDashTag('Fijo', 'tag-fijo', 'Monto fijo que no varia con la produccion');
            } else if (r.rollup_tipo === 'driver' && esGrupo) {
                descHtml += pptoDashTag('Toneladas', 'tag-driver', 'Capitulo calculado por produccion');
            } else if (r.rollup_tipo === 'fijo' && esGrupo) {
                descHtml += pptoDashTag('Fijo', 'tag-fijo', 'Capitulo con montos fijos');
            }
        }
        if (r.alerta_d8 && r.alerta_d8_det && r.alerta_d8_det.url_reajuste && !pptoDashModoReinversion) {
            var d8t = 'Proyectado ' + formatCurrency(r.alerta_d8_det.pf) + ' supera aprobado ' + formatCurrency(r.alerta_d8_det.va);
            descHtml += ' <a href="' + htmlspecialchars(r.alerta_d8_det.url_reajuste) + '" class="exa-dash-tag tag-d8 exa-dash-open-reajuste" data-titulo="Reajustar presupuesto" title="' + htmlspecialchars(d8t) + '">Exceso</a>';
        }
        var formalizarUrl = '';
        var formalizarTitulo = 'Formalizar ' + (r.codigo || '');
        if (pptoDashModoReinversion && pptoDashGetPista() === 'real' && porFormalizar > 0.0001
            && r.alerta_formalizar_det && r.alerta_formalizar_det.url_reajuste) {
            formalizarUrl = r.alerta_formalizar_det.url_reajuste;
            var ft = 'Derecho por real ' + formatCurrency(r.alerta_formalizar_det.derecho) +
                ' supera asignado ' + formatCurrency(r.alerta_formalizar_det.va) +
                '. Por formalizar ' + formatCurrency(porFormalizar);
            if (!esGrupo) {
                descHtml += ' <a href="' + htmlspecialchars(formalizarUrl) + '" class="exa-dash-tag tag-formalizar exa-dash-open-reajuste" data-titulo="' + htmlspecialchars(formalizarTitulo) + '" title="' + htmlspecialchars(ft) + '">Por formalizar</a>';
            }
        }

        var pctBadge;
        if (pptoDashModoReinversion) {
            var pista = pptoDashGetPista();
            var refVig = (pista === 'real') ? vigReal : vigProy;
            var refDisp = (pista === 'real') ? dispReal : dispProy;
            if (!usaProy) {
                refVig = parseFloat(r.vigente);
                refDisp = parseFloat(r.disponible);
            }
            pctBadge = pptoDashPctBadge(refVig, refDisp);
        } else {
            pctBadge = '<span class="exa-dash-pct-badge ' + badgeClass.replace('badge ', '') + '">' + r.disponible_porcentaje + '%</span>';
        }

        function pptoDashCellPorFormalizar(usa, monto, url, titulo) {
            if (!usa) {
                return '-';
            }
            var txt = formatCurrency(monto);
            if (monto > 0.0001 && url) {
                return '<a href="' + htmlspecialchars(url) + '" class="exa-dash-open-reajuste" data-titulo="' + htmlspecialchars(titulo || 'Formalizar') + '" style="font-weight:700;color:#c05621;text-decoration:underline;" title="Abrir formalizacion">' + txt + '</a>';
            }
            if (monto > 0.0001) {
                return '<span style="font-weight:700;color:#c05621;">' + txt + '</span>';
            }
            return txt;
        }

        if (modo === 'gerente') {
            if (pptoDashModoReinversion) {
                var pistaG = pptoDashGetPista();
                if (pistaG === 'real') {
                    return '<tr class="' + trClass + '">' +
                        '<td class="text-left" style="padding-left:' + (8 + indent) + 'px;font-weight:700;font-size:11px;">' + htmlspecialchars(r.codigo) + '</td>' +
                        '<td class="exa-dash-cell-rubro" style="padding-left:' + indent + 'px;">' + descHtml + '</td>' +
                        '<td class="text-right exa-dash-cell-money" style="font-weight:600;color:#276749;">' + (usaProy ? formatCurrency(vigReal) : formatCurrency(r.vigente)) + '</td>' +
                        '<td class="text-right exa-dash-cell-money">' + formatCurrency(r.ejecutado) + '</td>' +
                        '<td class="text-right exa-dash-cell-money' + (dispReal < 0 ? ' text-danger' : '') + '">' + (usaProy ? formatCurrency(dispReal) : formatCurrency(r.disponible)) + '</td>' +
                        '<td class="text-right exa-dash-cell-money">' + pptoDashCellPorFormalizar(usaProy, porFormalizar, formalizarUrl, formalizarTitulo) + '</td>' +
                        '<td class="text-center">' + pctBadge + '</td>' +
                        '</tr>';
                }
                return '<tr class="' + trClass + '">' +
                    '<td class="text-left" style="padding-left:' + (8 + indent) + 'px;font-weight:700;font-size:11px;">' + htmlspecialchars(r.codigo) + '</td>' +
                    '<td class="exa-dash-cell-rubro" style="padding-left:' + indent + 'px;">' + descHtml + '</td>' +
                    '<td class="text-right exa-dash-cell-money" style="font-weight:600;color:#2c5282;">' + (usaProy ? formatCurrency(vigProy) : formatCurrency(r.vigente)) + '</td>' +
                    '<td class="text-right exa-dash-cell-money">' + formatCurrency(r.ejecutado) + '</td>' +
                    '<td class="text-right exa-dash-cell-money' + (dispProy < 0 ? ' text-danger' : '') + '">' + (usaProy ? formatCurrency(dispProy) : formatCurrency(r.disponible)) + '</td>' +
                    '<td class="text-center">' + pctBadge + '</td>' +
                    '</tr>';
            }
            return '<tr class="' + trClass + '">' +
                '<td class="text-left" style="padding-left:' + (8 + indent) + 'px;font-weight:700;font-size:11px;">' + htmlspecialchars(r.codigo) + '</td>' +
                '<td class="exa-dash-cell-rubro" style="padding-left:' + indent + 'px;">' + descHtml + '</td>' +
                '<td class="text-right exa-dash-cell-money' + (esGrupo ? ' font-weight-bold' : '') + '">' + formatCurrency(r.vigente) + '</td>' +
                '<td class="text-right exa-dash-cell-money">' + formatCurrency(r.ejecutado) + '</td>' +
                '<td class="text-right exa-dash-cell-money' + (parseFloat(r.disponible) < 0 ? ' text-danger' : '') + '">' + formatCurrency(r.disponible) + '</td>' +
                '<td class="text-center">' + pctBadge + '</td>' +
                '</tr>';
        }

        if (pptoDashModoReinversion) {
            var pistaT = pptoDashGetPista();
            if (pistaT === 'real') {
                return '<tr class="' + trClass + '">' +
                    '<td class="text-left" style="padding-left:' + (8 + indent) + 'px;font-weight:700;font-size:11px;">' + htmlspecialchars(r.codigo) + '</td>' +
                    '<td class="exa-dash-cell-rubro" style="padding-left:' + indent + 'px;">' + descHtml + '</td>' +
                    '<td class="text-right exa-dash-cell-money" style="font-weight:600;color:#276749;">' + (usaProy ? formatCurrency(vigReal) : '-') + '</td>' +
                    '<td class="text-right exa-dash-cell-money">' + formatCurrency(r.comprometido) + '</td>' +
                    '<td class="text-right exa-dash-cell-money">' + formatCurrency(r.ejecutado) + '</td>' +
                    '<td class="text-right exa-dash-cell-money" style="font-weight:700;color:' + (dispReal < 0 ? '#e53e3e' : '#2d3748') + ';">' + (usaProy ? formatCurrency(dispReal) : formatCurrency(r.disponible)) + '</td>' +
                    '<td class="text-right exa-dash-cell-money">' + pptoDashCellPorFormalizar(usaProy, porFormalizar, formalizarUrl, formalizarTitulo) + '</td>' +
                    '<td class="text-center">' + pctBadge + '</td>' +
                    '</tr>';
            }
            return '<tr class="' + trClass + '">' +
                '<td class="text-left" style="padding-left:' + (8 + indent) + 'px;font-weight:700;font-size:11px;">' + htmlspecialchars(r.codigo) + '</td>' +
                '<td class="exa-dash-cell-rubro" style="padding-left:' + indent + 'px;">' + descHtml + '</td>' +
                '<td class="text-right exa-dash-cell-money" style="font-weight:600;color:#2c5282;">' + (usaProy ? formatCurrency(vigProy) : '-') + '</td>' +
                '<td class="text-right exa-dash-cell-money">' + formatCurrency(r.comprometido) + '</td>' +
                '<td class="text-right exa-dash-cell-money">' + formatCurrency(r.ejecutado) + '</td>' +
                '<td class="text-right exa-dash-cell-money" style="font-weight:700;color:' + (dispProy < 0 ? '#e53e3e' : '#2d3748') + ';">' + (usaProy ? formatCurrency(dispProy) : formatCurrency(r.disponible)) + '</td>' +
                '<td class="text-center">' + pctBadge + '</td>' +
                '</tr>';
        }

        return '<tr class="' + trClass + '">' +
            '<td class="text-left" style="padding-left:' + (8 + indent) + 'px;font-weight:700;font-size:11px;">' + htmlspecialchars(r.codigo) + '</td>' +
            '<td class="exa-dash-cell-rubro" style="padding-left:' + indent + 'px;">' + descHtml + '</td>' +
            '<td class="text-right exa-dash-cell-money">' + formatCurrency(r.inicial) + '</td>' +
            '<td class="text-right exa-dash-cell-money">' + formatCurrency(r.reajustes) + '</td>' +
            '<td class="text-right exa-dash-cell-money' + (esGrupo ? '' : ' text-muted') + '">' + formatCurrency(r.vigente) + '</td>' +
            '<td class="text-right exa-dash-cell-money" style="font-weight:600;color:#2c5282;">' + (usaProy ? formatCurrency(vigProy) : '-') + '</td>' +
            '<td class="text-right exa-dash-cell-money">' + formatCurrency(r.comprometido) + '</td>' +
            '<td class="text-right exa-dash-cell-money">' + formatCurrency(r.ejecutado) + '</td>' +
            '<td class="text-right exa-dash-cell-money' + (esGrupo ? '' : ' text-muted') + '">' + formatCurrency(r.disponible) + '</td>' +
            '<td class="text-right exa-dash-cell-money" style="font-weight:700; color:' + (dispProy < 0 ? '#e53e3e' : '#2d3748') + ';">' + (usaProy ? formatCurrency(dispProy) : formatCurrency(r.disponible)) + '</td>' +
            '<td class="text-center">' + pctBadge + '</td>' +
            '</tr>';
    }

    /**
     * Cambia vista de la tabla de partidas (jerarquica / solo con valores).
     */
    function pptoDashCambiarVistaPartidas(vista) {
        // Gerente clásico: capítulos. Técnico puede elegir Capítulos o Solo rubros.
        if (pptoDashGetModoUx() === 'gerente') {
            vista = 'jerarquica';
        }
        vista = vista === 'plana' ? 'plana' : 'jerarquica';
        $('#vista_partidas').val(vista);
        $('input[name="vista_partidas_ui"][value="' + vista + '"]').prop('checked', true);
        pptoDashSyncSegmento($('#seg_vista_partidas'), vista, 'data-vista');
        cargarDashboard();
    }

    function switchTab(num) {
        if (num === 1) {
            $('#tab_btn_partidas').addClass('active');
            $('#tab_btn_mensual').removeClass('active');
            $('#tab_content_partidas').show();
            $('#tab_content_mensual').hide();
        } else {
            $('#tab_btn_partidas').removeClass('active');
            $('#tab_btn_mensual').addClass('active');
            $('#tab_content_partidas').hide();
            $('#tab_content_mensual').show();
        }
    }

    /**
     * Consulta as?ncronamente proyectos y versiones al cambiar empresa o a?o fiscal
     */
    function actualizarCatalogosFiltros() {
        var emp = $('#emp_id').val();
        var ani = $('#ani').val();
        
        $.ajax({
            url: 'dashboard_ajax.php',
            type: 'GET',
            dataType: 'json',
            data: {
                action: 'catalogos',
                emp_id: emp,
                anio: ani
            },
            success: function(res) {
                if (res.status === 'success') {
                    // Actualizar dropdown de versiones
                    var selectVer = $('#ver');
                    selectVer.empty();
                    if (res.versiones.length === 0) {
                        selectVer.append('<option value="">Sin versi&oacute;n activa</option>');
                    } else {
                        $.each(res.versiones, function(i, v) {
                            var text = 'V' + v.ppe_version + ' \u00b7 ' + v.ppe_descripcion;
                            if (v.ppe_estado === 'A') text += ' [Activa]';
                            selectVer.append($('<option>', { value: v.ppe_id, text: text }));
                        });
                    }

                    // Actualizar dropdown de proyectos
                    var selectProy = $('#proy_id');
                    selectProy.empty();
                    selectProy.append('<option value="">Todos los proyectos</option>');
                    $.each(res.proyectos, function(i, p) {
                        selectProy.append($('<option>', { value: p.proy_id, text: p.proy_nombre }));
                    });
                }
                // Disparar carga del dashboard principal
                cargarDashboard();
            },
            error: function() {
                cargarDashboard();
            }
        });
    }

    /**
     * Carga y actualiza de forma reactiva los KPI, sem?foros y tablas detalladas del Dashboard
     */
    function cargarDashboard() {
        var data_filtros = $('#form_filtros').serialize() + '&action=fetch';
        var colsPart = pptoDashColCountPartidas();
        
        // Poner cargando en tablas
        $('#tbody_partidas').html('<tr><td colspan="' + colsPart + '" class="text-center text-muted" style="padding:40px;"><i class="bi bi-hourglass-split"></i> Cargando resumen de partidas...</td></tr>');
        $('#tbody_mensual').html('<tr><td colspan="10" class="text-center text-muted" style="padding:40px;"><i class="bi bi-hourglass-split"></i> Cargando evolucion mensual...</td></tr>');

        $.ajax({
            url: 'dashboard_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: data_filtros,
            success: function(res) {
                if (res.status === 'success') {
                    var modo = pptoDashGetModoUx();
                    pptoDashModoReinversion = res.modo_reinversion === true || res.modo_reinversion === 1 || res.modo_reinversion === '1';
                    pptoDashUltimosKpis = res.kpis || null;
                    pptoDashUltimasAlertasFormalizar = res.alertas_formalizar || [];
                    pptoDashUltimasAlertasD8 = res.alertas_d8 || [];
                    $('body').toggleClass('ppto-dash-modo-relavera', pptoDashModoReinversion);
                    $('#ppto_dash_relavera_badge').toggle(pptoDashModoReinversion);
                    $('#ppto_dash_pista_bar').toggle(pptoDashModoReinversion);
                    var proySel = ($('#proy_id').val() || '').trim();
                    $('#ppto_dash_sin_proy_hint').toggle(!pptoDashModoReinversion && proySel === '');
                    pptoDashSyncPistaUi();
                    pptoDashRenderTheadPartidas(modo);
                    if (pptoDashModoReinversion) {
                        $('#ppto_dash_leyenda_tags').html(
                            'Modo Relavera: <strong>Real vs Ejecutado</strong> muestra columna y etiqueta '
                            + '<span class="exa-dash-tag tag-formalizar">Por formalizar</span> cuando el derecho supera lo asignado. '
                            + 'Vista <strong>Cap&iacute;tulos</strong> / <strong>Solo rubros</strong>.'
                        );
                    } else {
                        $('#lbl_kpi_pf').text('Ppto. forecast');
                        $('#kpi_disp_forecast_title').text('Disp. forecast');
                        $('#ppto_dash_leyenda_tags').html(
                            'Etiquetas: <span class="exa-dash-tag tag-mix">Mixto</span> capitulo con rubros variables y fijos; ' +
                            '<span class="exa-dash-tag tag-driver">Toneladas</span> calculado por produccion; ' +
                            '<span class="exa-dash-tag tag-fijo">Fijo</span> monto constante; ' +
                            '<span class="exa-dash-tag tag-d8">Exceso</span> requiere reajuste presupuestario.'
                        );
                    }
                    // 1. KPIs financieros ? fuente unica: servidor (motor v2, Fase 2B)
                    var dispPlan = parseFloat(res.kpis.disponible_plan != null ? res.kpis.disponible_plan : res.kpis.disponible) || 0;
                    var dispPlanPct = parseFloat(res.kpis.disponible_plan_porcentaje != null ? res.kpis.disponible_plan_porcentaje : res.kpis.disponible_porcentaje) || 0;
                    var dispForecast = parseFloat(res.kpis.disponible_forecast != null ? res.kpis.disponible_forecast : dispPlan) || 0;
                    var dispForecastPct = parseFloat(res.kpis.disponible_forecast_porcentaje != null ? res.kpis.disponible_forecast_porcentaje : dispPlanPct) || 0;
                    var semaforoPlan = res.kpis.semaforo_plan || res.kpis.semaforo || 'verde';
                    var semaforoForecast = res.kpis.semaforo_forecast || semaforoPlan;
                    var prodDesvAbs = (parseFloat(res.kpis.prod_proyectada) || 0) - (parseFloat(res.kpis.prod_esperada) || 0);
                    var prodDesvPct = (parseFloat(res.kpis.prod_esperada) > 0) ? ((prodDesvAbs / parseFloat(res.kpis.prod_esperada)) * 100) : 0;

                    $('#kpi_inicial').text(formatCurrency(res.kpis.presupuesto_inicial));
                    $('#kpi_reajustes').text(formatCurrency(res.kpis.total_reajustes));
                    $('#kpi_vigente').text(formatCurrency(res.kpis.presupuesto_vigente));
                    if (modo === 'gerente') {
                        $('#kpi_disponible_title').text('Saldo disponible (plan)');
                    } else {
                        $('#kpi_disponible_title').text('Disp. plan');
                    }
                    $('#kpi_disponible_pct').text(formatNumber(dispPlanPct, 2) + '% disponible');
                    var pctColor = '#48bb78';
                    if (semaforoPlan === 'amarillo') pctColor = '#d69e2e';
                    else if (semaforoPlan === 'rojo') pctColor = '#e53e3e';
                    $('#kpi_disponible_pct').css('color', pctColor);
                    $('#kpi_comprometido').text(formatCurrency(res.kpis.comprometido));
                    $('#kpi_ejecutado').text(formatCurrency(res.kpis.ejecutado));
                    $('#kpi_disponible').text(formatCurrency(dispPlan));
                    $('#kpi_pf').text(formatCurrency(res.kpis.presupuesto_proyectado));
                    $('#kpi_disp_forecast').text(formatCurrency(dispForecast));
                    $('#kpi_disp_forecast_title').text('Disp. forecast');
                    $('#kpi_disp_forecast_pct').text(formatNumber(dispForecastPct, 2) + '% disponible');
                    if (pptoDashModoReinversion) {
                        pptoDashAplicarKpisRelavera(res.kpis, modo);
                    } else {
                        var pctColorF = '#48bb78';
                        if (semaforoForecast === 'amarillo') pctColorF = '#d69e2e';
                        else if (semaforoForecast === 'rojo') pctColorF = '#e53e3e';
                        $('#kpi_disp_forecast_pct').css('color', pctColorF);

                        var sIndicator = $('#semaforo_kpi');
                        sIndicator.empty();
                        var colorClass = 'semaforo-bg-verde';
                        if (semaforoPlan === 'amarillo') colorClass = 'semaforo-bg-amarillo';
                        else if (semaforoPlan === 'rojo') colorClass = 'semaforo-bg-rojo';
                        sIndicator.append('<span class="semaforo-indicator ' + colorClass + '"></span>');
                        var sIndicatorF = $('#semaforo_kpi_forecast');
                        sIndicatorF.empty();
                        var colorClassF = 'semaforo-bg-verde';
                        if (semaforoForecast === 'amarillo') colorClassF = 'semaforo-bg-amarillo';
                        else if (semaforoForecast === 'rojo') colorClassF = 'semaforo-bg-rojo';
                        sIndicatorF.append('<span class="semaforo-indicator ' + colorClassF + '"></span>');
                    }

                    // 2. Renderizar KPIs de Producci?n F?sica
                    var uni = res.kpis.prod_unidad;
                    $('#lbl_prod_proyectada').text(res.kpis.prod_modo === 'mes' ? 'Proyectado (mes)' : 'Proyectado al cierre');
                    $('#prod_esperada').text(formatNumber(res.kpis.prod_esperada, 2) + ' ' + uni);
                    $('#prod_real').text(formatNumber(res.kpis.prod_real, 2) + ' ' + uni);
                    $('#prod_proyectada').text(formatNumber(res.kpis.prod_proyectada, 2) + ' ' + uni);
                    
                    var varAbsEl = $('#prod_var_absoluta');
                    var varAbs = parseFloat(res.kpis.prod_var_absoluta);
                    varAbsEl.text((varAbs >= 0 ? '+' : '') + formatNumber(varAbs, 2) + ' ' + uni);
                    if (res.kpis.prod_var_absoluta >= 0) {
                        varAbsEl.css('color', '#3182ce');
                        $('#prod_var_porcentual').removeClass('label-danger').addClass('label-success').text('+' + res.kpis.prod_var_porcentual + '%');
                    } else {
                        varAbsEl.css('color', '#e53e3e');
                        $('#prod_var_porcentual').removeClass('label-success').addClass('label-danger').text(res.kpis.prod_var_porcentual + '%');
                    }
                    $('#prod_desv_meta_forecast').text((prodDesvAbs >= 0 ? '+' : '') + formatNumber(prodDesvAbs, 2) + ' ' + uni + ' (' + formatNumber(prodDesvPct, 2) + '%)');
                    $('#prod_desv_meta_forecast').css('color', prodDesvAbs >= 0 ? '#2f855a' : '#c53030');

                    var d2 = res.kpis.divergencia_d2 || {};
                    var d2Sn = d2.alineado_sn || (d2.alineado ? 'S' : 'N');
                    var $d2Badge = $('#d2_alineado_sn');
                    $d2Badge.text(d2Sn);
                    $d2Badge.removeClass('label-success label-danger').addClass(d2Sn === 'S' ? 'label-success' : 'label-danger');
                    var d2Det = '';
                    var d2Proy = (d2.ton_proyectada_periodo !== undefined) ? d2.ton_proyectada_periodo : d2.ton_esperada_anual;
                    var d2Real = (d2.ton_real_periodo !== undefined) ? d2.ton_real_periodo : d2.ton_base_anual;
                    if (d2Proy !== undefined && d2Real !== undefined) {
                        d2Det = 'Proy ' + formatNumber(d2Proy, 0) + ' vs Real ' + formatNumber(d2Real, 0) + ' Ton (' + formatNumber(d2.pct_diferencia || 0, 2) + '%)';
                        if (d2.meses_con_real) {
                            d2Det += ' · ' + d2.meses_con_real + ' mes(es)';
                        }
                        if (d2.rubro_driver) {
                            d2Det += ' · ' + d2.rubro_driver;
                        }
                    }
                    $('#d2_alineado_det').text(d2Det);
                    if (d2.warning && d2.mensaje) {
                        var d2msg = d2.mensaje || '';
                        var d2Sum = d2.requiere_reajuste
                            ? 'Proyectada corta vs real (' + formatNumber(d2.pct_diferencia || 0, 2) + '%) — reajustar'
                            : 'Proyectada vs real (' + formatNumber(d2.pct_diferencia || 0, 2) + '% de diferencia)';
                        $('#d2_warning_summary').text(d2Sum);
                        $('#d2_warning_body').html('<div class="exa-dash-alert-item"><span style="flex:1;">' + htmlspecialchars(d2msg) + '</span></div>');
                        $('#d2_warning_banner').removeClass('open').show();
                    } else {
                        $('#d2_warning_banner').hide().removeClass('open');
                        $('#d2_warning_body').empty();
                    }

                    var alertasD8 = pptoDashUltimasAlertasD8;
                    var alertasFormalizar = pptoDashUltimasAlertasFormalizar;
                    var $d8 = $('#d8_alertas_banner');
                    if (pptoDashModoReinversion && alertasFormalizar.length > 0) {
                        pptoDashPaintAlertasFormalizar(alertasFormalizar);
                        pptoDashRefreshAlertasPista();
                    } else if (!pptoDashModoReinversion && alertasD8.length > 0) {
                        $('#d8_alertas_summary').text(
                            alertasD8.length + ' partida(s): presupuesto proyectado supera el aprobado + margen permitido'
                        );
                        var itemsD8 = '';
                        $.each(alertasD8, function(i, a) {
                            var nom = a.descripcion ? htmlspecialchars(a.descripcion) : 'Sin descripcion';
                            var metrics = 'Proyectado ' + formatCurrency(a.pf) +
                                ' &middot; Aprobado ' + formatCurrency(a.va) +
                                ' &middot; Margen ' + formatNumber(a.umbral_pct, 2) + '%';
                            if (a.exceso !== undefined && parseFloat(a.exceso) > 0) {
                                metrics += ' &middot; Exceso ' + formatCurrency(a.exceso);
                            }
                            itemsD8 += '<div class="exa-dash-alert-item">' +
                                '<div class="exa-dash-alert-item-text">' +
                                '<a href="' + htmlspecialchars(a.url_reajuste) + '" class="exa-dash-alert-item-name exa-dash-open-reajuste" data-titulo="Reajustar ' + htmlspecialchars(a.codigo || '') + '">' +
                                htmlspecialchars(a.codigo) + ' &mdash; ' + nom +
                                '</a>' +
                                '<div class="exa-dash-alert-item-metrics">' + metrics + '</div>' +
                                '</div>' +
                                '<a href="' + htmlspecialchars(a.url_reajuste) + '" class="exa-dash-alert-link exa-dash-open-reajuste" data-titulo="Reajustar ' + htmlspecialchars(a.codigo || '') + '">Reajustar</a>' +
                                '</div>';
                        });
                        $('#d8_alertas_list').html(itemsD8);
                        $d8.removeClass('open').show();
                    } else {
                        $d8.hide().removeClass('open');
                        $('#d8_alertas_list').empty();
                    }

                    // 3. Renderizar Tabla de Partidas
                    pptoDashUltimasPartidas = res.partidas || [];
                    pptoDashRenderFilasPartidas(pptoDashUltimasPartidas);

                    // 4. Renderizar Tabla Evolucion Mensual
                    var tbodyMensual = $('#tbody_mensual');
                    tbodyMensual.empty();
                    $.each(res.mensual, function(i, r) {
                        var pptoProy = parseFloat(r.ppto_proyectado) || 0;
                        var varAcumHtml = '<span class="text-muted">-</span>';
                        if (r.prod_var_acum !== null && r.prod_var_acum !== undefined && r.prod_var_acum !== '') {
                            var varAcum = parseFloat(r.prod_var_acum);
                            var varAcumColor = varAcum >= 0 ? '#48bb78' : '#f56565';
                            varAcumHtml = '<span style="font-weight:700;color:' + varAcumColor + ';">' + (varAcum >= 0 ? '+' : '') + formatNumber(varAcum, 2) + '</span>';
                        }
                        var avance = r.prod_avance_pct;
                        var avanceHtml = (avance !== null && avance !== undefined && avance !== '')
                            ? '<strong style="color:#2c5282;">' + formatNumber(avance, 1) + '%</strong>'
                            : '<span class="text-muted">-</span>';
                        var devMesHtml = '<span class="text-muted">-</span>';
                        if (r.tiene_real && r.prod_var_fisica !== null && r.prod_var_fisica !== undefined) {
                            var dev = parseFloat(r.prod_var_fisica);
                            var devColor = dev >= 0 ? '#48bb78' : '#f56565';
                            devMesHtml = '<span style="font-weight:700;color:' + devColor + ';">' + (dev >= 0 ? '+' : '') + formatNumber(dev, 2) + '</span>';
                        }

                        tbodyMensual.append(
                            '<tr>' +
                                '<td class="text-center" style="font-weight:700;">' + htmlspecialchars(r.nombre_mes) + '</td>' +
                                '<td class="text-right text-muted">' + formatNumber(r.prod_esperada, 2) + '</td>' +
                                '<td class="text-right" style="color:#2c5282;">' + formatNumber(r.prod_proyectada, 2) + '</td>' +
                                '<td class="text-right" style="color:#3182ce;font-weight:600;">' + formatNumber(r.prod_real, 2) + '</td>' +
                                '<td class="text-right" style="font-weight:600;">' + formatNumber(r.prod_real_acum, 2) + '</td>' +
                                '<td class="text-right" style="color:#2c5282;">' + formatNumber(r.prod_proy_acum, 2) + '</td>' +
                                '<td class="text-right">' + varAcumHtml + '</td>' +
                                '<td class="text-center">' + avanceHtml + '</td>' +
                                '<td class="text-right" style="font-weight:600;">' + (pptoProy > 0 ? formatCurrency(pptoProy) : '-') + '</td>' +
                                '<td class="text-center">' + devMesHtml + '</td>' +
                            '</tr>'
                        );
                    });
                } else {
                    alert('Error en consulta: ' + res.error);
                }
            },
            error: function() {
                alert('No se pudo conectar con el servidor de informes.');
            }
        });
    }

    /**
     * Escapa caracteres HTML para evitar ataques XSS en datos renderizados
     */
    function htmlspecialchars(str) {
        if (typeof str !== 'string') return str;
        return str.replace(/&/g, '&amp;')
                  .replace(/</g, '&lt;')
                  .replace(/>/g, '&gt;')
                  .replace(/"/g, '&quot;')
                  .replace(/'/g, '&#039;');
    }

    /**
     * Redirecciona a la exportaci?n anal?tica de datos en Excel o PDF
     */
    function exportarData(tipo) {
        var filters = $('#form_filtros').serialize();
        var base = window.location.pathname.replace(/dashboard_front\.php.*/, 'dashboard_export.php');
        window.open(base + '?export=' + tipo + '&' + filters, '_blank');
    }

    /**
     * Abre reajustes / formalizar en ventana flotante (iframe).
     */
    function pptoDashAbrirFormalizar(url, titulo) {
        if (!url) return false;
        var $modal = $('#modal_formalizar');
        var $iframe = $('#modal_formalizar_iframe');
        $('#modal_formalizar_titulo').text(titulo || 'Formalizar / Reajuste presupuestario');
        $iframe.attr('src', 'about:blank');
        $modal.css('display', 'block');
        setTimeout(function() {
            $iframe.attr('src', url);
        }, 30);
        return false;
    }

    function pptoDashCerrarFormalizar(recargar) {
        $('#modal_formalizar').css('display', 'none');
        $('#modal_formalizar_iframe').attr('src', 'about:blank');
        if (recargar) {
            cargarDashboard();
        }
    }

    $(document).on('click', 'a.exa-dash-open-reajuste', function(e) {
        e.preventDefault();
        var url = $(this).attr('href') || $(this).data('url');
        var titulo = $(this).data('titulo') || $(this).text() || 'Formalizar';
        pptoDashAbrirFormalizar(url, titulo);
    });

    $(document).on('click', '#modal_formalizar', function(e) {
        if (e.target === this) {
            pptoDashCerrarFormalizar(false);
        }
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#modal_formalizar').is(':visible')) {
            pptoDashCerrarFormalizar(false);
        }
    });

    window.addEventListener('message', function(ev) {
        if (!ev.data || ev.data.type !== 'ppto_reajuste_ok') return;
        if (ev.origin !== window.location.origin) return;
        pptoDashCerrarFormalizar(true);
    });
</script>

<div id="modal_formalizar" class="exa-pre-modal-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="modal_formalizar_titulo">
    <div class="exa-pre-modal-box">
        <span class="exa-pre-modal-close" title="Cerrar" onclick="pptoDashCerrarFormalizar(false)">&times;</span>
        <h3 id="modal_formalizar_titulo" class="exa-adq-section-title" style="margin:0 28px 10px 0; font-size:16px;">
            Formalizar / Reajuste presupuestario
        </h3>
        <div class="ppto-dash-modal-iframe-wrap">
            <iframe id="modal_formalizar_iframe" title="Formulario de formalizacion" src="about:blank"></iframe>
        </div>
        <div class="ppto-dash-modal-foot">
            <span>Registre el incremento y cierre esta ventana. El dashboard se actualiza al guardar.</span>
            <button type="button" class="btn btn-default btn-sm" onclick="pptoDashCerrarFormalizar(false)">Cerrar</button>
        </div>
    </div>
</div>

</body>
</html>
