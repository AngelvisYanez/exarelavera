<?php
/**
 * dashboard_front.php
 * Interfaz Gráfica del Dashboard Presupuestario (EXA PPTO).
 * Permite visualizar de forma interactiva KPIs, semáforos, gráficos de rendimiento y tablas analíticas.
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
        (int)$filtros['Emp_Cod']
    );
}

// 2. Cargar opciones para los filtros de seleccion
// 2.1 Proyectos
$res_proyectos = $mysqli_conn->query("SELECT Pro_Cod AS proy_id, Pro_Nom AS proy_nombre FROM pre_proyectos WHERE Emp_Cod = " . $filtros['Emp_Cod'] . " AND Pro_Est = 'A' ORDER BY Pro_Nom");
$proyectos = array();
if ($res_proyectos) {
    while ($row = $res_proyectos->fetch_assoc()) {
        $proyectos[] = $row;
    }
}

// 2.3 Versiones/Cabeceras presupuestarias para el año actual
$res_cabeceras = $mysqli_conn->query("SELECT Ppe_Cod AS ppe_id, Ppe_Ver AS ppe_version, Ppe_Des AS ppe_descripcion, Ppe_Est AS ppe_estado FROM pre_presupuesto WHERE Emp_Cod = " . $filtros['Emp_Cod'] . " AND Ppe_Ani = " . $filtros['anio'] . " ORDER BY Ppe_Ver DESC");
$versiones = array();
if ($res_cabeceras) {
    while ($row = $res_cabeceras->fetch_assoc()) {
        $versiones[] = $row;
    }
}

// Resolver la versión por defecto si no se especificó
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
    'Emp_Cod' => (int)$filtros['Emp_Cod'],
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
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }
        .kpi-card .kpi-icon {
            position: absolute;
            top: 12px;
            right: 14px;
            font-size: 2rem;
            opacity: 0.15;
            color: currentColor;
        }
        .kpi-card .kpi-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #718096;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .kpi-card .kpi-value {
            font-size: 1.45rem;
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 2px;
        }
        .kpi-card .kpi-sub {
            font-size: 0.73rem;
            color: #a0aec0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .semaforo-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 4px;
        }
        .semaforo-dot.verde { background-color: #38a169; box-shadow: 0 0 6px #38a169; }
        .semaforo-dot.amarillo { background-color: #d69e2e; box-shadow: 0 0 6px #d69e2e; }
        .semaforo-dot.rojo { background-color: #e53e3e; box-shadow: 0 0 6px #e53e3e; }
        .badge-status-v2 {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-status-v2.verde { background-color: #c6f6d5; color: #22543d; }
        .badge-status-v2.amarillo { background-color: #fefcbf; color: #744210; }
        .badge-status-v2.rojo { background-color: #fed7d7; color: #742a2a; }
        
        .filter-panel {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
        }
        .table-resumen th {
            background-color: #edf2f7;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #4a5568;
            font-weight: 700;
            padding: 8px 10px;
            border-bottom: 2px solid #cbd5e0;
        }
        .table-resumen td {
            font-size: 0.8rem;
            vertical-align: middle;
            padding: 6px 10px;
        }
        .progress-bar-custom {
            height: 6px;
            border-radius: 3px;
            background-color: #edf2f7;
            overflow: hidden;
            margin-top: 3px;
        }
        .progress-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.4s ease;
        }
    </style>
</head>
<body class="exa-ui-body bg-light">

    <div class="container-fluid p-3">
        <!-- CABECERA PRINCIPAL Y TITULO -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="m-0 text-primary font-weight-bold">
                    <i class="bi bg-primary text-white p-1 rounded bi-speedometer2 me-2"></i>
                    Dashboard Presupuestario
                </h4>
                <small class="text-muted">Control consolidado de ejecucion financiera, semaforos y rendimiento de produccion</small>
            </div>
            <div>
                <div class="btn-group me-2" role="group" aria-label="Modo de lectura">
                    <input type="radio" class="btn-check" name="btn_modo_ux" id="btn_modo_gerente" value="gerente" <?php echo ($filtros['modo_ux'] === 'gerente') ? 'checked' : ''; ?> onchange="ppto_dash_cambiar_modo_ux('gerente')">
                    <label class="btn btn-outline-primary btn-sm" for="btn_modo_gerente"><i class="bi bi-briefcase me-1"></i>Gerente</label>

                    <input type="radio" class="btn-check" name="btn_modo_ux" id="btn_modo_tecnico" value="tecnico" <?php echo ($filtros['modo_ux'] === 'tecnico') ? 'checked' : ''; ?> onchange="ppto_dash_cambiar_modo_ux('tecnico')">
                    <label class="btn btn-outline-primary btn-sm" for="btn_modo_tecnico"><i class="bi bi-sliders me-1"></i>Tecnico</label>
                </div>
                <button class="btn btn-outline-secondary btn-sm me-2" onclick="ppto_dash_actualizar_todo();" title="Refrescar Dashboard">
                    <i class="bi bi-arrow-clockwise"></i> Refrescar
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-download me-1"></i> Exportar
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-menu-item dropdown-item" href="#" onclick="ppto_dash_exportar('excel'); return false;"><i class="bi bi-file-earmark-excel text-success me-2"></i>Exportar a Excel (.xls)</a></li>
                        <li><a class="dropdown-menu-item dropdown-item" href="#" onclick="ppto_dash_exportar('pdf'); return false;"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>Imprimir / Exportar a PDF</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- PANEL DE FILTROS GLOBALES -->
        <div class="filter-panel shadow-sm">
            <form id="form_filtros_dash" onsubmit="event.preventDefault(); ppto_dash_actualizar_todo();">
                <input type="hidden" name="Emp_Cod" id="filtro_Emp_Cod" value="<?php echo $filtros['Emp_Cod']; ?>">
                <input type="hidden" name="modo_ux" id="filtro_modo_ux" value="<?php echo htmlspecialchars($filtros['modo_ux']); ?>">
                <div class="row g-2 align-items-center">
                    <div class="col-md-2">
                        <label class="form-label form-label-sm fw-bold mb-1">Año Fiscal</label>
                        <select name="anio" id="filtro_anio" class="form-select form-select-sm" onchange="ppto_dash_cargar_catalogos();">
                            <?php 
                            $a_actual = (int)date('Y');
                            for ($a = $a_actual + 1; $a >= $a_actual - 3; $a--) {
                                $sel = ($a == $filtros['anio']) ? 'selected' : '';
                                echo "<option value='$a' $sel>$a</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label form-label-sm fw-bold mb-1">Version de Presupuesto</label>
                        <select name="ppe_id" id="filtro_ppe_id" class="form-select form-select-sm" onchange="ppto_dash_actualizar_todo();">
                            <option value="">-- Cargando versiones... --</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label form-label-sm fw-bold mb-1">Proyecto (Opcional)</label>
                        <select name="proy_id" id="filtro_proy_id" class="form-select form-select-sm" onchange="ppto_dash_actualizar_todo();">
                            <option value="">-- Todos los Proyectos / Estándar --</option>
                            <?php foreach ($proyectos as $p): ?>
                                <option value="<?php echo htmlspecialchars($p['proy_id']); ?>" <?php echo ($p['proy_id'] === $filtros['proy_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['proy_nombre'] . " (" . $p['proy_id'] . ")"); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label form-label-sm fw-bold mb-1">Vista de Periodo</label>
                        <select name="periodo_vista" id="filtro_periodo_vista" class="form-select form-select-sm" onchange="ppto_dash_toggle_select_mes(); ppto_dash_actualizar_todo();">
                            <option value="acumulado" <?php echo ($filtros['periodo_vista'] === 'acumulado') ? 'selected' : ''; ?}>Acumulado hasta mes</option>
                            <option value="mes" <?php echo ($filtros['periodo_vista'] === 'mes') ? 'selected' : ''; ?}>Solo el mes elegido</option>
                            <option value="anual" <?php echo ($filtros['periodo_vista'] === 'anual') ? 'selected' : ''; ?}>Anual completo (12 meses)</option>
                        </select>
                    </div>

                    <div class="col-md-2" id="wrap_select_mes" style="<?php echo ($filtros['periodo_vista'] === 'anual') ? 'display:none;' : ''; ?>">
                        <label class="form-label form-label-sm fw-bold mb-1">Mes de Corte</label>
                        <select name="mes" id="filtro_mes" class="form-select form-select-sm" onchange="ppto_dash_actualizar_todo();">
                            <option value="">-- Ultimo mes con datos --</option>
                            <?php 
                            $meses_n = array(1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio', 7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre');
                            foreach ($meses_n as $num => $nom) {
                                $sel = ($filtros['mes'] !== null && (int)$filtros['mes'] === $num) ? 'selected' : '';
                                echo "<option value='$num' $sel>$nom</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <!-- CONTENEDOR DE ALERTAS D8 / PF / REINVERSION -->
        <div id="container_alertas_d8" class="mb-3"></div>

        <!-- REGION GERENTE DE VISTA RAPIDA -->
        <div id="panel_modo_gerente" style="<?php echo ($filtros['modo_ux'] === 'gerente') ? 'display:block;' : 'display:none;'; ?>">
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <div class="kpi-card text-primary">
                        <i class="bi bi-pie-chart kpi-icon"></i>
                        <div class="kpi-label">Presupuesto Aprobado</div>
                        <div class="kpi-value" id="kpi_ger_presupuesto_vigente">$ 0,00</div>
                        <div class="kpi-sub" id="kpi_ger_sub_presupuesto">Monto total autorizado</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="kpi-card text-secondary">
                        <i class="bi bi-bookmark-check kpi-icon"></i>
                        <div class="kpi-label">Comprometido</div>
                        <div class="kpi-value" id="kpi_ger_comprometido">$ 0,00</div>
                        <div class="kpi-sub" id="kpi_ger_sub_comprometido">Reservado / Órdenes en proceso</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="kpi-card text-success">
                        <i class="bi bi-cash-stack kpi-icon"></i>
                        <div class="kpi-label">Ya Gastado (Ejecutado)</div>
                        <div class="kpi-value" id="kpi_ger_ejecutado">$ 0,00</div>
                        <div class="kpi-sub" id="kpi_ger_sub_ejecutado">Pagado / Facturado real</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="kpi-card" id="kpi_card_ger_disponible">
                        <i class="bi bi-wallet2 kpi-icon"></i>
                        <div class="kpi-label">Saldo Disponible</div>
                        <div class="kpi-value" id="kpi_ger_disponible_plan">$ 0,00</div>
                        <div class="kpi-sub" id="kpi_ger_sub_disponible">Libre para gastar</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- REGION TECNICA CON DETALLE AMPLIADO -->
        <div id="panel_modo_tecnico" style="<?php echo ($filtros['modo_ux'] === 'tecnico') ? 'display:block;' : 'display:none;'; ?>">
            <div class="row g-3 mb-3">
                <div class="col-md-2">
                    <div class="kpi-card text-dark">
                        <i class="bi bi-file-earmark-spreadsheet kpi-icon"></i>
                        <div class="kpi-label">Ppto Inicial</div>
                        <div class="kpi-value" id="kpi_presupuesto_inicial">$ 0,00</div>
                        <div class="kpi-sub">Nominal Aprobado</div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="kpi-card text-info">
                        <i class="bi bi-arrow-left-right kpi-icon"></i>
                        <div class="kpi-label">Reajustes</div>
                        <div class="kpi-value" id="kpi_reajustes_neto">$ 0,00</div>
                        <div class="kpi-sub" id="kpi_sub_reajustes">Traspasos / Modif</div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="kpi-card text-primary">
                        <i class="bi bi-calculator kpi-icon"></i>
                        <div class="kpi-label">Ppto Vigente</div>
                        <div class="kpi-value" id="kpi_presupuesto_vigente">$ 0,00</div>
                        <div class="kpi-sub">Inicial + Ajustes</div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="kpi-card text-secondary">
                        <i class="bi bi-clock-history kpi-icon"></i>
                        <div class="kpi-label">Comprometido</div>
                        <div class="kpi-value" id="kpi_comprometido">$ 0,00</div>
                        <div class="kpi-sub">Pre-gasto OCs</div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="kpi-card text-success">
                        <i class="bi bi-check-circle kpi-icon"></i>
                        <div class="kpi-label">Ejecutado Real</div>
                        <div class="kpi-value" id="kpi_ejecutado">$ 0,00</div>
                        <div class="kpi-sub" id="kpi_sub_ejecutado">0.00% Consumido</div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="kpi-card" id="kpi_card_disponible">
                        <i class="bi bi-shield-check kpi-icon"></i>
                        <div class="kpi-label">Disponible</div>
                        <div class="kpi-value" id="kpi_disponible_plan">$ 0,00</div>
                        <div class="kpi-sub" id="kpi_sub_disponible">Plan vs Ejecutado</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FILA DE KPIS SECUNDARIOS (Rendimiento Físico y Costo Unitario) -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="rounded-circle bg-light-primary text-primary p-3 me-3">
                            <i class="bi bi-box-seam fs-3"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Toneladas Procesadas</div>
                            <h4 class="mb-0 fw-bold" id="kpi_toneladas_procesadas">0,00 TON</h4>
                            <small class="text-muted" id="kpi_sub_toneladas">Acumulado al mes</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="rounded-circle bg-light-success text-success p-3 me-3">
                            <i class="bi bi-currency-dollar fs-3"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Costo Promedio Unitario</div>
                            <h4 class="mb-0 fw-bold text-primary" id="kpi_costo_por_tonelada">$ 0,00 / TON</h4>
                            <small class="text-muted">Costo Real por Tonelada Físicamente Procesada</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Estado Global y Semáforo</div>
                            <div class="d-flex align-items-center mt-1">
                                <span class="semaforo-dot verde" id="kpi_dot_semaforo"></span>
                                <span class="fw-bold fs-5 text-dark me-2" id="kpi_label_semaforo">SALUDABLE</span>
                                <span class="badge-status-v2 verde" id="kpi_badge_estado">EN RANGO</span>
                            </div>
                            <small class="text-muted d-block mt-1" id="kpi_sub_semaforo">Evaluación automática de límites</small>
                        </div>
                        <div class="text-end">
                            <button class="btn btn-sm btn-outline-info" onclick="ppto_dash_modal_alertas();">
                                <i class="bi bi-bell"></i> Ver Alertas (<span id="count_alertas_badge">0</span>)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCION DE GRAFICOS INTERACTIVOS (Evolución y Distribución) -->
        <div class="row g-3 mb-4">
            <!-- Gráfico 1: Evolución Mensual (Líneas / Barras) -->
            <div class="col-md-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-secondary">
                            <i class="bi bi-graph-up me-2 text-primary"></i>
                            Evolución Mensual: Presupuestado vs. Ejecutado vs. Producción
                        </h6>
                        <small class="text-muted" id="label_chart_periodo">Valores en USD y TON</small>
                    </div>
                    <div class="card-body p-3" style="position: relative; min-height: 280px;">
                        <canvas id="chart_evolucion_mensual"></canvas>
                    </div>
                </div>
            </div>

            <!-- Gráfico 2: Distribución por Capítulos / Partidas Nivel 1 -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-secondary">
                            <i class="bi bi-pie-chart-fill me-2 text-info"></i>
                            Gasto por Capítulos Principales
                        </h6>
                        <small class="text-muted">Top 7 Partidas</small>
                    </div>
                    <div class="card-body p-3" style="position: relative; min-height: 280px;">
                        <canvas id="chart_partidas_dona"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLA ANALITICA CONSOLIDADA POR PARTIDAS -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="m-0 fw-bold text-secondary">
                        <i class="bi bi-table me-2 text-primary"></i>
                        Desglose Analítico por Partidas Presupuestarias
                    </h6>
                    <small class="text-muted" id="label_tabla_periodo">Detalle de presupuesto inicial, reajustes, ejecutado y disponibilidad</small>
                </div>
                <div class="d-flex align-items-center">
                    <input type="text" id="input_buscar_partida" class="form-control form-control-sm me-2" placeholder="Filtrar por código o nombre..." onkeyup="ppto_dash_filtrar_tabla_local();" style="width: 240px;">
                    <button class="btn btn-sm btn-outline-secondary" onclick="ppto_dash_toggle_partidas_padre();" id="btn_toggle_padres">
                        <i class="bi bi-eye"></i> Ocultar Capítulos
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-resumen align-middle mb-0" id="tabla_partidas_dash">
                    <thead>
                        <tr id="thead_partidas_row">
                            <!-- Inyectado dinámicamente según modo UX Gerente / Técnico -->
                        </tr>
                    </thead>
                    <tbody id="tbody_partidas_dash">
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                Cargando datos consolidados...
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light fw-bold" id="tfoot_partidas_dash">
                        <!-- Totales consolidados -->
                    </tfoot>
                </table>
            </div>
        </div>

    </div> <!-- /container-fluid -->

    <!-- MODAL PARA VER ALERTAS / DESVIOS -->
    <div class="modal fade" id="modal_alertas_dash" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title font-weight-bold text-primary">
                        <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                        Alertas y Desvíos Presupuestarios D8
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-3">
                    <div id="body_alertas_list">
                        <!-- Inyectado dinámicamente -->
                    </div>
                </div>
                <div class="modal-footer bg-light p-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPT DE CONTROL Y LOGICA FRONTEND DASHBOARD -->
    <script>
        var ppto_dash_chart_evolucion = null;
        var ppto_dash_chart_dona = null;
        var ppto_dash_raw_data = null;
        var ppto_dash_ocultar_padres = false;

        $(document).ready(function() {
            ppto_dash_cargar_catalogos(function() {
                ppto_dash_actualizar_todo();
            });
        });

        function ppto_dash_toggle_select_mes() {
            var v = $('#filtro_periodo_vista').val();
            if (v === 'anual') {
                $('#wrap_select_mes').hide();
            } else {
                $('#wrap_select_mes').show();
            }
        }

        function ppto_dash_cambiar_modo_ux(modo) {
            $('#filtro_modo_ux').val(modo);
            if (modo === 'gerente') {
                $('#panel_modo_gerente').show();
                $('#panel_modo_tecnico').hide();
            } else {
                $('#panel_modo_gerente').hide();
                $('#panel_modo_tecnico').show();
            }
            if (ppto_dash_raw_data) {
                ppto_dash_renderizar_partidas(ppto_dash_raw_data.partidas);
            }
        }

        function ppto_dash_cargar_catalogos(callback) {
            var emp = $('#filtro_Emp_Cod').val();
            var ani = $('#filtro_anio').val();

            $.ajax({
                url: 'dashboard_ajax.php',
                type: 'GET',
                data: { action: 'catalogos', Emp_Cod: emp, anio: ani },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        // Cargar versiones
                        var $v = $('#filtro_ppe_id');
                        $v.empty();
                        if (res.versiones && res.versiones.length > 0) {
                            $.each(res.versiones, function(i, item) {
                                var sel = (item.ppe_estado === 'A') ? 'selected' : '';
                                $v.append('<option value="' + item.ppe_id + '" ' + sel + '>V' + item.ppe_version + ' (' + item.ppe_descripcion + ')</option>');
                            });
                        } else {
                            $v.append('<option value="">-- Sin versión para este año --</option>');
                        }
                    }
                    if (typeof callback === 'function') callback();
                },
                error: function() {
                    if (typeof callback === 'function') callback();
                }
            });
        }

        function ppto_dash_actualizar_todo() {
            var params = $('#form_filtros_dash').serialize() + '&action=fetch';

            $.ajax({
                url: 'dashboard_ajax.php',
                type: 'GET',
                data: params,
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        ppto_dash_raw_data = res;
                        ppto_dash_renderizar_alertas_banner(res.alertas_d8);
                        ppto_dash_renderizar_kpis(res.kpis);
                        ppto_dash_renderizar_partidas(res.partidas);
                        ppto_dash_renderizar_graficos(res.mensual, res.partidas);
                    } else {
                        alert("Error al cargar datos del Dashboard: " + res.message);
                    }
                },
                error: function(xhr, status, err) {
                    console.error("Error AJAX Dashboard:", err);
                }
            });
        }

        function ppto_dash_renderizar_alertas_banner(alertas) {
            var $c = $('#container_alertas_d8');
            $c.empty();
            if (!alertas || alertas.length === 0) return;

            $.each(alertas, function(i, a) {
                var cls = (a.nivel === 'rojo') ? 'alert-danger' : 'alert-warning';
                var icon = (a.nivel === 'rojo') ? 'bi-exclamation-octagon-fill' : 'bi-exclamation-triangle-fill';
                $c.append('<div class="alert ' + cls + ' alert-dismissible fade show p-2 mb-2 d-flex align-items-center small" role="alert">' +
                          '<i class="bi ' + icon + ' fs-5 me-2"></i>' +
                          '<div>' + ppto_escape_html(a.mensaje) + '</div>' +
                          '<button type="button" class="btn-close p-2" data-bs-dismiss="alert" aria-label="Cerrar"></button>' +
                          '</div>');
            });
        }

        function ppto_dash_renderizar_kpis(k) {
            if (!k) return;

            var disp_plan = k.disponible_plan !== undefined ? k.disponible_plan : k.disponible;
            var disp_pct = k.disponible_plan_porcentaje !== undefined ? k.disponible_plan_porcentaje : k.disponible_porcentaje;

            // GERENTE
            $('#kpi_ger_presupuesto_vigente').text(ppto_fmt_money(k.presupuesto_vigente));
            $('#kpi_ger_comprometido').text(ppto_fmt_money(k.comprometido));
            $('#kpi_ger_ejecutado').text(ppto_fmt_money(k.ejecutado));
            $('#kpi_ger_disponible_plan').text(ppto_fmt_money(disp_plan));
            $('#kpi_ger_sub_disponible').text(ppto_fmt_num(disp_pct, 1) + '% disponible del plan');

            // TECNICO
            $('#kpi_presupuesto_inicial').text(ppto_fmt_money(k.presupuesto_inicial));
            $('#kpi_reajustes_neto').text(ppto_fmt_money(k.reajustes_neto));
            $('#kpi_presupuesto_vigente').text(ppto_fmt_money(k.presupuesto_vigente));
            $('#kpi_comprometido').text(ppto_fmt_money(k.comprometido));
            $('#kpi_ejecutado').text(ppto_fmt_money(k.ejecutado));
            $('#kpi_sub_ejecutado').text(ppto_fmt_num(k.porcentaje_ejecucion, 1) + '% consumido');
            $('#kpi_disponible_plan').text(ppto_fmt_money(disp_plan));
            $('#kpi_sub_disponible').text(ppto_fmt_num(disp_pct, 1) + '% libre');

            // FISICOS Y UNITARIOS
            $('#kpi_toneladas_procesadas').text(ppto_fmt_num(k.toneladas_procesadas, 2) + ' TON');
            $('#kpi_costo_por_tonelada').text(ppto_fmt_money(k.costo_por_tonelada) + ' / TON');

            // SEMAFORO
            var sem = k.semaforo_global || 'verde';
            var lbl = (sem === 'rojo') ? 'EXCEDEDO / CRITICO' : ((sem === 'amarillo') ? 'ADVERTENCIA' : 'SALUDABLE');
            var bg_badge = (sem === 'rojo') ? 'rojo' : ((sem === 'amarillo') ? 'amarillo' : 'verde');

            $('#kpi_dot_semaforo').attr('class', 'semaforo-dot ' + sem);
            $('#kpi_label_semaforo').text(lbl);
            $('#kpi_badge_estado').attr('class', 'badge-status-v2 ' + bg_badge).text(sem.toUpperCase());

            var num_alertas = (k.alertas_criticas || 0) + (k.alertas_advertencia || 0);
            $('#count_alertas_badge').text(num_alertas);
        }

        function ppto_dash_renderizar_partidas(partidas) {
            var modo = $('#filtro_modo_ux').val();
            var $th = $('#thead_partidas_row');
            var $tb = $('#tbody_partidas_dash');
            var $tf = $('#tfoot_partidas_dash');

            $th.empty();
            $tb.empty();
            $tf.empty();

            if (modo === 'gerente') {
                $th.append('<th>Código</th><th>Rubro / Capítulo</th><th class="text-end">Presupuesto</th><th class="text-end">Ya Gastado</th><th class="text-end">Saldo Disponible</th><th class="text-center">Consumo (%)</th>');
            } else {
                $th.append('<th>Código</th><th>Descripción Partida</th><th class="text-end">Ppto Inicial</th><th class="text-end">Reajustes</th><th class="text-end">Ppto Vigente</th><th class="text-end">Comprometido</th><th class="text-end">Ejecutado Real</th><th class="text-end">Disponible</th><th class="text-center">% Ejec</th>');
            }

            if (!partidas || partidas.length === 0) {
                var cols = (modo === 'gerente') ? 6 : 9;
                $tb.append('<tr><td colspan="' + cols + '" class="text-center py-3 text-muted">No hay datos de partidas registrados.</td></tr>');
                return;
            }

            var filter_txt = $.trim($('#input_buscar_partida').val()).toLowerCase();

            var tot_ini = 0, tot_reaj = 0, tot_vig = 0, tot_comp = 0, tot_eje = 0, tot_disp = 0;

            $.each(partidas, function(i, p) {
                var is_padre = (p.ppa_clase === 'G');
                if (ppto_dash_ocultar_padres && is_padre) return;

                if (filter_txt !== '') {
                    var match = p.ppa_codigo_clasificacion.toLowerCase().indexOf(filter_txt) >= 0 || p.ppa_descripcion.toLowerCase().indexOf(filter_txt) >= 0;
                    if (!match) return;
                }

                if (!is_padre) {
                    tot_ini += parseFloat(p.presupuesto_inicial || 0);
                    tot_reaj += parseFloat(p.reajustes || 0);
                    tot_vig += parseFloat(p.presupuesto_vigente || 0);
                    tot_comp += parseFloat(p.comprometido || 0);
                    tot_eje += parseFloat(p.ejecutado || 0);
                    tot_disp += parseFloat(p.disponible || 0);
                }

                var pct = parseFloat(p.porcentaje_ejecucion || 0);
                var sem_cls = (pct >= 100) ? 'rojo' : ((pct >= 80) ? 'amarillo' : 'verde');
                var bg_row = is_padre ? 'table-light fw-bold' : '';

                var row_html = '<tr class="' + bg_row + '">';
                row_html += '<td><code>' + ppto_escape_html(p.ppa_codigo_clasificacion) + '</code></td>';
                row_html += '<td>' + ppto_escape_html(p.ppa_descripcion) + '</td>';

                if (modo === 'gerente') {
                    row_html += '<td class="text-end fw-bold text-primary">' + ppto_fmt_money(p.presupuesto_vigente) + '</td>';
                    row_html += '<td class="text-end text-success fw-bold">' + ppto_fmt_money(p.ejecutado) + '</td>';
                    row_html += '<td class="text-end fw-bold ' + (p.disponible < 0 ? 'text-danger' : 'text-dark') + '">' + ppto_fmt_money(p.disponible) + '</td>';
                } else {
                    row_html += '<td class="text-end">' + ppto_fmt_money(p.presupuesto_inicial) + '</td>';
                    row_html += '<td class="text-end">' + ppto_fmt_money(p.reajustes) + '</td>';
                    row_html += '<td class="text-end fw-bold text-primary">' + ppto_fmt_money(p.presupuesto_vigente) + '</td>';
                    row_html += '<td class="text-end text-muted">' + ppto_fmt_money(p.comprometido) + '</td>';
                    row_html += '<td class="text-end text-success fw-bold">' + ppto_fmt_money(p.ejecutado) + '</td>';
                    row_html += '<td class="text-end fw-bold ' + (p.disponible < 0 ? 'text-danger' : 'text-dark') + '">' + ppto_fmt_money(p.disponible) + '</td>';
                }

                row_html += '<td class="text-center">' +
                            '<span class="badge-status-v2 ' + sem_cls + '">' + ppto_fmt_num(pct, 1) + '%</span>' +
                            '<div class="progress-bar-custom"><div class="progress-fill ' + sem_cls + '" style="width: ' + Math.min(100, pct) + '%;"></div></div>' +
                            '</td>';
                row_html += '</tr>';

                $tb.append(row_html);
            });

            // Fila de Totales en Footer
            var pct_tot = (tot_vig > 0) ? (tot_eje / tot_vig) * 100 : 0;
            var ft_html = '<tr><td colspan="2">TOTAL CONSOLIDADO IMPUTABLE</td>';
            if (modo === 'gerente') {
                ft_html += '<td class="text-end text-primary">' + ppto_fmt_money(tot_vig) + '</td>';
                ft_html += '<td class="text-end text-success">' + ppto_fmt_money(tot_eje) + '</td>';
                ft_html += '<td class="text-end ' + (tot_disp < 0 ? 'text-danger' : '') + '">' + ppto_fmt_money(tot_disp) + '</td>';
            } else {
                ft_html += '<td class="text-end">' + ppto_fmt_money(tot_ini) + '</td>';
                ft_html += '<td class="text-end">' + ppto_fmt_money(tot_reaj) + '</td>';
                ft_html += '<td class="text-end text-primary">' + ppto_fmt_money(tot_vig) + '</td>';
                ft_html += '<td class="text-end">' + ppto_fmt_money(tot_comp) + '</td>';
                ft_html += '<td class="text-end text-success">' + ppto_fmt_money(tot_eje) + '</td>';
                ft_html += '<td class="text-end ' + (tot_disp < 0 ? 'text-danger' : '') + '">' + ppto_fmt_money(tot_disp) + '</td>';
            }
            ft_html += '<td class="text-center">' + ppto_fmt_num(pct_tot, 1) + '%</td></tr>';
            $tf.append(ft_html);
        }

        function ppto_dash_renderizar_graficos(mensual, partidas) {
            // 1. Gráfico de Evolución Mensual
            if (ppto_dash_chart_evolucion) ppto_dash_chart_evolucion.destroy();

            var ctx1 = document.getElementById('chart_evolucion_mensual').getContext('2d');
            ppto_dash_chart_evolucion = new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                    datasets: [
                        {
                            label: 'Presupuesto Plan (USD)',
                            data: mensual ? mensual.presupuestado : [],
                            backgroundColor: 'rgba(66, 153, 225, 0.5)',
                            borderColor: '#4299e1',
                            borderWidth: 1,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Ejecutado Real (USD)',
                            data: mensual ? mensual.ejecutado : [],
                            backgroundColor: 'rgba(72, 187, 120, 0.8)',
                            borderColor: '#48bb78',
                            borderWidth: 1,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Producción (TON)',
                            data: mensual ? mensual.produccion : [],
                            type: 'line',
                            borderColor: '#ed8936',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            pointRadius: 3,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            type: 'linear',
                            position: 'left',
                            ticks: { callback: function(v) { return '$ ' + ppto_fmt_num(v, 0); } }
                        },
                        y1: {
                            type: 'linear',
                            position: 'right',
                            grid: { drawOnChartArea: false },
                            ticks: { callback: function(v) { return ppto_fmt_num(v, 0) + ' TON'; } }
                        }
                    }
                }
            });

            // 2. Gráfico Dona por Capítulos
            if (ppto_dash_chart_dona) ppto_dash_chart_dona.destroy();

            var labels = [], data = [];
            if (partidas) {
                $.each(partidas, function(i, p) {
                    if (p.ppa_clase === 'G' && p.ejecutado > 0) {
                        labels.push(p.ppa_codigo_clasificacion + ' ' + p.ppa_descripcion);
                        data.push(p.ejecutado);
                    }
                });
            }

            var ctx2 = document.getElementById('chart_partidas_dona').getContext('2d');
            ppto_dash_chart_dona = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: labels.slice(0, 7),
                    datasets: [{
                        data: data.slice(0, 7),
                        backgroundColor: ['#4299e1', '#48bb78', '#ed8936', '#9f7aea', '#ed64a6', '#38b2ac', '#ecc94b']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right', labels: { boxWidth: 12, font: { size: 10 } } }
                    }
                }
            });
        }

        function ppto_dash_filtrar_tabla_local() {
            if (ppto_dash_raw_data) {
                ppto_dash_renderizar_partidas(ppto_dash_raw_data.partidas);
            }
        }

        function ppto_dash_toggle_partidas_padre() {
            ppto_dash_ocultar_padres = !ppto_dash_ocultar_padres;
            $('#btn_toggle_padres').html(ppto_dash_ocultar_padres ? '<i class="bi bi-eye-slash"></i> Mostrar Capítulos' : '<i class="bi bi-eye"></i> Ocultar Capítulos');
            ppto_dash_filtrar_tabla_local();
        }

        function ppto_dash_modal_alertas() {
            var $b = $('#body_alertas_list');
            $b.empty();

            if (!ppto_dash_raw_data || !ppto_dash_raw_data.kpis || !ppto_dash_raw_data.kpis.alertas_recientes || ppto_dash_raw_data.kpis.alertas_recientes.length === 0) {
                $b.append('<div class="text-center py-4 text-muted">No existen alertas activas registradas para el período actual.</div>');
            } else {
                $.each(ppto_dash_raw_data.kpis.alertas_recientes, function(i, a) {
                    var umb = parseInt(a.pal_umbral || 100);
                    var cls = (umb >= 100) ? 'alert-danger' : 'alert-warning';
                    $b.append('<div class="alert ' + cls + ' mb-2 p-2 small">' +
                              '<strong>[' + ppto_escape_html(a.ppa_codigo_clasificacion) + '] ' + ppto_escape_html(a.ppa_descripcion) + ':</strong> ' +
                              'Desvío detectado al ' + ppto_fmt_num(a.pal_porcentaje_actual, 1) + '% sobre el umbral de ' + umb + '%.' +
                              '</div>');
                });
            }

            var modal = new bootstrap.Modal(document.getElementById('modal_alertas_dash'));
            modal.show();
        }

        function ppto_dash_exportar(fmt) {
            var query = $('#form_filtros_dash').serialize() + '&export=' + fmt;
            window.open('dashboard_export.php?' + query, '_blank');
        }

        function ppto_escape_html(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    </script>
</body>
</html>
