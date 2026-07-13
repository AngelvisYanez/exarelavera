<?php
/**
 * EXA Adquisiciones - Dashboard Gerencial de Flujos
 * @author Oz <oz-agent@warp.dev>
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/wf_manager_log.php');

$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new MysqlDatos($obBD_conexion);
$wf_mgr = new wf_manager_log($Ses_Dat_Dis);

// Verificar acceso a la ventana 'dashboard' y pestaña 'dashboard_general'
if (!$wf_mgr->verificarAccesoVentana('dashboard', 'dashboard_general')) {
    echo "<div class='alert alert-danger m-3'>Acceso denegado. No tiene permisos para ver esta ventana.</div>";
    exit;
}

// 1. Estadísticas de Resumen
$stats = $obBD_con1->getRowConsultaSql("
    SELECT 
        COUNT(CASE WHEN Sol_Est = 'E' THEN 1 END) as Activos,
        COUNT(CASE WHEN Sol_Est = 'A' THEN 1 END) as Aprobados,
        COUNT(CASE WHEN Sol_Est = 'R' THEN 1 END) as Rechazados,
        COUNT(CASE WHEN Sol_Est = 'O' THEN 1 END) as Observados,
        IFNULL(AVG(CASE WHEN Sol_Est = 'A' THEN TIMESTAMPDIFF(HOUR, Sol_Fec, Sol_Sys) / 24.0 END), 0) as Tiempo_Promedio
    FROM adq_solicitudes 
    WHERE Emp_Cod = $Ses_Emp_Cod;", $obBD_conexion);

// 2. Cuellos de Botella: Solicitudes por etapa activa actual
$cuellos = $obBD_con1->getArrayConsultaSql("
    SELECT n.Nod_Nom, d.Wde_Des AS Dep_Des, COUNT(i.Ins_Cod) as Total
    FROM wf_instancias i
    INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
    LEFT JOIN wf_departamentos d ON d.Dep_Cod = n.Dep_Cod
    WHERE i.Ins_Est = 'P' AND n.Wfm_Cod IN (SELECT Wfm_Cod FROM wf_flujos_modelos WHERE Emp_Cod = $Ses_Emp_Cod)
    GROUP BY n.Nod_Nom, d.Wde_Des
    ORDER BY Total DESC;", $obBD_conexion);

// 3. Ranking de Departamentos por SLA de atención
$departamentos_ranking = $obBD_con1->getArrayConsultaSql("
    SELECT d.Wde_Des AS Dep_Des,
           COUNT(h.Isn_Cod) as Resoluciones,
           IFNULL(AVG(TIMESTAMPDIFF(HOUR, h.Isn_Fec, (SELECT MIN(h2.Isn_Fec) FROM wf_instancias_nodos h2 WHERE h2.Ins_Cod = h.Ins_Cod AND h2.Isn_Cod > h.Isn_Cod)) / 24.0), 0) as Tiempo_Atencion
    FROM wf_instancias_nodos h
    INNER JOIN wf_nodos n ON n.Nod_Cod = h.Nod_Cod
    INNER JOIN wf_departamentos d ON d.Dep_Cod = h.Dep_Cod
    WHERE h.Isn_Acc IN ('APROBAR', 'OBSERVAR', 'DEVOLVER') AND d.Emp_Cod = $Ses_Emp_Cod
    GROUP BY d.Wde_Des
    ORDER BY Tiempo_Atencion ASC;", $obBD_conexion);

// 4. Volúmenes Mensuales de Solicitudes
$volumenes = $obBD_con1->getArrayConsultaSql("
    SELECT DATE_FORMAT(Sol_Fec, '%Y-%m') as Mes, COUNT(Sol_Cod) as Total, SUM(Sol_Val_Est) as Monto
    FROM adq_solicitudes
    WHERE Emp_Cod = $Ses_Emp_Cod AND Sol_Fec >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY Mes
    ORDER BY Mes ASC;", $obBD_conexion);

// 5. Todos los Procesos (Monitor de Gerencia)
$es_gerencial_admin = true; // Dejado abierto para que el perfil se asigne manualmente por seguridad.php / permisos nativos de EXA

$total_activos = 0;
$total_a_tiempo = 0;
$total_en_riesgo = 0;
$total_vencidos = 0;
$total_sin_sla = 0;
$procesos = array();
$departamentos = array();
$tipos_req = array();

$filtro_estado = isset($_GET['filtro_estado']) ? $_GET['filtro_estado'] : '';
$filtro_depto = isset($_GET['filtro_depto']) ? intval($_GET['filtro_depto']) : 0;
$filtro_tipo = isset($_GET['filtro_tipo']) ? intval($_GET['filtro_tipo']) : 0;

if ($es_gerencial_admin) {
    // Calcular métricas de SLA generales para todos los procesos activos
    $sql_metrics = "
        SELECT i.Ins_Fec_Ini, COALESCE(s.Sol_Tiempo_Est, tr.Trq_Tiempo_Est) AS Sla_Dias
        FROM wf_instancias i
        INNER JOIN adq_solicitudes s ON i.Ins_Ent_Typ = 'adq_solicitudes' AND i.Ins_Ent_Cod = s.Sol_Cod
        INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
        WHERE s.Emp_Cod = $Ses_Emp_Cod AND i.Ins_Est = 'P';";
    $active_processes = $obBD_con1->getArrayConsultaSql($sql_metrics, $obBD_conexion);

    $now = time();
    foreach ($active_processes as $ap) {
        $total_activos++;
        if ($ap['Sla_Dias'] === null || $ap['Sla_Dias'] === '') {
            $total_sin_sla++;
        } else {
            $fec_ini = strtotime($ap['Ins_Fec_Ini']);
            $elapsed_days = ($now - $fec_ini) / 86400.0;
            $limit_days = floatval($ap['Sla_Dias']);
            $ratio = $elapsed_days / $limit_days;
            
            if ($ratio < 0.8) {
                $total_a_tiempo++;
            } elseif ($ratio >= 0.8 && $ratio <= 1.0) {
                $total_en_riesgo++;
            } else {
                $total_vencidos++;
            }
        }
    }

    // Obtener listas para filtros
    $departamentos = $obBD_con1->getArrayConsultaSql("SELECT Wde_Cod AS Dep_Cod, Wde_Des AS Dep_Des FROM wf_departamentos WHERE Emp_Cod = $Ses_Emp_Cod AND Wde_Est = 'A' ORDER BY Wde_Des ASC;", $obBD_conexion);
    $tipos_req = $obBD_con1->getArrayConsultaSql("SELECT Trq_Cod, Trq_Des FROM adq_tipos_requerimientos WHERE Emp_Cod = $Ses_Emp_Cod AND Trq_Est = 'A' ORDER BY Trq_Des ASC;", $obBD_conexion);

    // Construir consulta para la tabla
    $where_clauses = array("s.Emp_Cod = $Ses_Emp_Cod");

    if (!empty($filtro_estado)) {
        if ($filtro_estado === 'P') {
            $where_clauses[] = "i.Ins_Est = 'P'";
        } elseif ($filtro_estado === 'F') {
            $where_clauses[] = "i.Ins_Est = 'F'";
        } elseif ($filtro_estado === 'R') {
            $where_clauses[] = "i.Ins_Est = 'R'";
        } elseif ($filtro_estado === 'O') {
            $where_clauses[] = "s.Sol_Est = 'O'";
        }
    }

    if ($filtro_depto > 0) {
        $where_clauses[] = "n.Dep_Cod = $filtro_depto";
    }

    if ($filtro_tipo > 0) {
        $where_clauses[] = "s.Trq_Cod = $filtro_tipo";
    }

    $sql_table = "
        SELECT i.Ins_Cod, i.Ins_Fec_Ini, i.Ins_Fec_Fin, i.Ins_Est, i.Nod_Act,
               s.Sol_Cod, s.Sol_Num, s.Sol_Fec, s.Sol_Val_Est, s.Sol_Est,
               tr.Trq_Des, COALESCE(s.Sol_Tiempo_Est, tr.Trq_Tiempo_Est) AS Sla_Dias,
               n.Nod_Nom, d.Wde_Des AS Dep_Des,
               u.Usu_Ced as Usu_Nom, p.Prs_Nom, p.Prs_Ape
        FROM wf_instancias i
        INNER JOIN adq_solicitudes s ON i.Ins_Ent_Typ = 'adq_solicitudes' AND i.Ins_Ent_Cod = s.Sol_Cod
        INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
        LEFT JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
        LEFT JOIN wf_departamentos d ON d.Dep_Cod = n.Dep_Cod
        LEFT JOIN usuarios u ON u.Usu_Cod = s.Usu_Sol
        LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod
        WHERE " . implode(" AND ", $where_clauses) . "
        ORDER BY s.Sol_Fec DESC;";
        
    $procesos = $obBD_con1->getArrayConsultaSql($sql_table, $obBD_conexion);
}
?>
<!DOCTYPE html>
<html lang="es" class="exa-ui-fill-root">
<head>
    <meta charset="UTF-8">
    <title>EXA Dashboard Gerencial</title>
    <?php require_once('adq_model3_assets.php'); ?>
</head>
<body class="exa-ui-fill-root">
    <div class="panel panel-main exa-ui-panel exa-ui-fill-page">
        <div class="panel-heading exa-header exa-header-flex">
            <h3 class="panel-title"><i class="bi bi-graph-up-arrow"></i> Dashboard Gerencial</h3>
        </div>
        <div class="panel-body exa-body">
            <div class="exa-ui-page-view">
        <ul class="nav nav-tabs exa-ui-nav-tabs" id="dashboardTabs" role="tablist">
            <li role="presentation" class="active">
                <a href="#metrics-panel" id="metrics-tab" role="tab" data-toggle="tab"><i class="bi bi-bar-chart-line"></i> Métricas Generales</a>
            </li>
            <li role="presentation">
                <a href="#all-processes-panel" id="all-processes-tab" role="tab" data-toggle="tab"><i class="bi bi-collection-play"></i> Todos los Procesos</a>
            </li>
        </ul>

        <div class="tab-content exa-ui-tab-content panels-area" id="dashboardTabsContent">
            <!-- 1. MÉTRICAS GENERALES -->
            <div class="tab-pane active" id="metrics-panel" role="tabpanel">
                <div class="exa-adq-kpi-row">
                    <div class="exa-adq-kpi kpi-primary">
                        <span class="kpi-label">Procesos en ejecución</span>
                        <span class="kpi-value"><?php echo $stats['Activos']; ?></span>
                    </div>
                    <div class="exa-adq-kpi kpi-success">
                        <span class="kpi-label">Solicitudes aprobadas</span>
                        <span class="kpi-value"><?php echo $stats['Aprobados']; ?></span>
                    </div>
                    <div class="exa-adq-kpi kpi-warning">
                        <span class="kpi-label">Solicitudes observadas</span>
                        <span class="kpi-value"><?php echo $stats['Observados']; ?></span>
                    </div>
                    <div class="exa-adq-kpi kpi-danger">
                        <span class="kpi-label">Tiempo promedio ciclo</span>
                        <span class="kpi-value"><?php echo number_format($stats['Tiempo_Promedio'], 1); ?> <small>Días</small></span>
                    </div>
                </div>

        <div class="row">
            <!-- 1. Cuellos de Botella -->
            <div class="col-md-6">
                <div class="exa-adq-section">
                    <h5 class="exa-adq-section-title"><i class="bi bi-exclamation-octagon text-danger"></i> Cuellos de Botella (Procesos en Espera)</h5>
                    <div class="exa-adq-table-wrap">
                        <table class="table table-bordered exa-adq-table">
                            <thead>
                                <tr>
                                    <th>Etapa del Workflow</th>
                                    <th>Departamento Responsable</th>
                                    <th style="width: 100px;">En Espera</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($cuellos)) { ?>
                                    <tr class="exa-adq-empty text-center"><td colspan="3">No hay cuellos de botella identificados.</td></tr>
                                <?php } else {
                                    foreach ($cuellos as $c) { ?>
                                        <tr class="text-center">
                                            <td class="text-start fw-bold"><?php echo $c['Nod_Nom']; ?></td>
                                            <td><?php echo $c['Dep_Des'] ?: '[General]'; ?></td>
                                            <td class="fw-bold fs-6 text-danger"><?php echo $c['Total']; ?></td>
                                        </tr>
                                <?php }
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 2. Rendimiento Departamental -->
            <div class="col-md-6">
                <div class="exa-adq-section">
                    <h5 class="exa-adq-section-title"><i class="bi bi-speedometer2 text-success"></i> Eficiencia y SLA Departamental</h5>
                    <div class="exa-adq-table-wrap">
                        <table class="table table-bordered exa-adq-table">
                            <thead>
                                <tr>
                                    <th>Departamento</th>
                                    <th style="width: 120px;">Pasos Resueltos</th>
                                    <th style="width: 150px;">Tiempo Medio Aprob.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($departamentos_ranking)) { ?>
                                    <tr class="text-center"><td colspan="3" class="text-muted py-3">No se han registrado transacciones de workflow aprobadas aún.</td></tr>
                                <?php } else {
                                    foreach ($departamentos_ranking as $r) { ?>
                                        <tr class="text-center">
                                            <td class="text-start fw-bold"><?php echo $r['Dep_Des']; ?></td>
                                            <td><?php echo $r['Resoluciones']; ?></td>
                                            <td class="fw-bold font-monospace text-success"><?php echo number_format($r['Tiempo_Atencion'], 1); ?> d&iacute;as</td>
                                        </tr>
                                <?php }
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 3. Evolución del Gasto -->
            <div class="col-12">
                <div class="exa-adq-section">
                    <h5 class="exa-adq-section-title"><i class="bi bi-calendar-event text-primary"></i> Volúmenes de Gasto de los Últimos 6 Meses</h5>
                    <div class="exa-adq-table-wrap">
                        <table class="table table-bordered exa-adq-table">
                            <thead>
                                <tr>
                                    <th>Mes Calendario</th>
                                    <th>Total Requerimientos de Adquisición</th>
                                    <th>Presupuesto Total Estimado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($volumenes)) { ?>
                                    <tr class="text-center"><td colspan="3" class="text-muted py-3">No se registran solicitudes en el semestre actual.</td></tr>
                                <?php } else {
                                    foreach ($volumenes as $v) { ?>
                                        <tr>
                                            <td class="fw-bold"><?php echo $v['Mes']; ?></td>
                                            <td class="fs-6"><?php echo $v['Total']; ?></td>
                                            <td class="fw-bold font-monospace fs-6 text-primary">$ <?php echo number_format($v['Monto'], 2); ?></td>
                                        </tr>
                                <?php }
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. TODOS LOS PROCESOS (MONITOR DE GERENCIA) -->
    <div class="tab-pane" id="all-processes-panel" role="tabpanel">
        <?php if (!$es_gerencial_admin) { ?>
            <div class="alert alert-warning m-4 text-center">
                <i class="bi bi-shield-slash fs-1 d-block mb-2"></i>
                <h5 class="fw-bold">Acceso Restringido</h5>
                <p class="mb-0">Esta sección es de uso exclusivo para perfiles gerenciales, directores o administradores del sistema.</p>
            </div>
        <?php } else { ?>
            <!-- Tarjetas de Resumen SLA -->
            <div class="exa-adq-kpi-row">
                <div class="exa-adq-kpi kpi-muted"><span class="kpi-label">Procesos activos</span><span class="kpi-value"><?php echo $total_activos; ?></span></div>
                <div class="exa-adq-kpi kpi-success"><span class="kpi-label">A tiempo (&lt;80%)</span><span class="kpi-value"><?php echo $total_a_tiempo; ?></span></div>
                <div class="exa-adq-kpi kpi-warning"><span class="kpi-label">En riesgo (80%-100%)</span><span class="kpi-value"><?php echo $total_en_riesgo; ?></span></div>
                <div class="exa-adq-kpi kpi-danger"><span class="kpi-label">Vencidos (&gt;100%)</span><span class="kpi-value"><?php echo $total_vencidos; ?></span></div>
                <div class="exa-adq-kpi kpi-muted"><span class="kpi-label">Sin SLA definido</span><span class="kpi-value"><?php echo $total_sin_sla; ?></span></div>
            </div>

            <!-- Formulario de Filtros -->
            <form method="GET" action="adq_dashboard.php" class="exa-adq-filter-bar">
                    <input type="hidden" name="tab" value="todos_procesos">
                    
                    <div class="filter-item">
                        <label>Estado del Proceso</label>
                        <select class="form-control input-sm" name="filtro_estado">
                            <option value="">-- Todos los Estados --</option>
                            <option value="P" <?php echo $filtro_estado === 'P' ? 'selected' : ''; ?>>En Proceso (Activos)</option>
                            <option value="F" <?php echo $filtro_estado === 'F' ? 'selected' : ''; ?>>Aprobados (Finalizados)</option>
                            <option value="R" <?php echo $filtro_estado === 'R' ? 'selected' : ''; ?>>Rechazados</option>
                            <option value="O" <?php echo $filtro_estado === 'O' ? 'selected' : ''; ?>>Observados</option>
                        </select>
                    </div>
                    
                    <div class="filter-item">
                        <label>Departamento Responsable</label>
                        <select class="form-control input-sm" name="filtro_depto">
                            <option value="0">-- Todos los Departamentos --</option>
                            <?php foreach ($departamentos as $d) { ?>
                                <option value="<?php echo $d['Dep_Cod']; ?>" <?php echo $filtro_depto == $d['Dep_Cod'] ? 'selected' : ''; ?>><?php echo $d['Dep_Des']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    
                    <div class="filter-item">
                        <label>Tipo de Requerimiento</label>
                        <select class="form-control input-sm" name="filtro_tipo">
                            <option value="0">-- Todos los Tipos --</option>
                            <?php foreach ($tipos_req as $tr) { ?>
                                <option value="<?php echo $tr['Trq_Cod']; ?>" <?php echo $filtro_tipo == $tr['Trq_Cod'] ? 'selected' : ''; ?>><?php echo $tr['Trq_Des']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filtrar</button>
                        <a href="adq_dashboard.php?tab=todos_procesos" class="btn btn-default btn-sm"><i class="bi bi-x-circle"></i> Limpiar</a>
                    </div>
                </form>

            <!-- Tabla de Procesos -->
            <div class="exa-adq-table-wrap">
                    <table class="table table-bordered exa-adq-table">
                        <thead>
                            <tr>
                                <th>Nº Sol.</th>
                                <th>Fecha Emisión</th>
                                <th>Solicitante</th>
                                <th>Tipo Requerimiento</th>
                                <th>Monto Est.</th>
                                <th>Etapa Actual</th>
                                <th>Responsable</th>
                                <th>Estado</th>
                                <th>SLA Semáforo</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($procesos)) { ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">No se encontraron procesos que coincidan con los filtros seleccionados.</td>
                                </tr>
                            <?php } else {
                                foreach ($procesos as $p) {
                                    // Calcular SLA y Semáforo
                                    $elapsed_days = 0;
                                    $limit_days = !empty($p['Sla_Dias']) ? floatval($p['Sla_Dias']) : null;
                                    
                                    $fec_ini = strtotime($p['Ins_Fec_Ini']);
                                    if ($p['Ins_Est'] === 'P') {
                                        $elapsed_days = (time() - $fec_ini) / 86400.0;
                                    } else {
                                        $fec_fin = strtotime($p['Ins_Fec_Fin']);
                                        $elapsed_days = ($fec_fin - $fec_ini) / 86400.0;
                                    }
                                    
                                    $elapsed_days_fmt = number_format($elapsed_days, 1);
                                    
                                    $semaforo_class = 'bg-semaforo-gris';
                                    $sla_badge = '<span class="badge bg-secondary">Sin SLA</span>';
                                    
                                    if ($limit_days !== null && $limit_days > 0) {
                                        $ratio = $elapsed_days / $limit_days;
                                        if ($ratio < 0.8) {
                                            $semaforo_class = 'bg-semaforo-verde';
                                            $sla_badge = '<span class="badge bg-success">A tiempo</span>';
                                        } elseif ($ratio >= 0.8 && $ratio <= 1.0) {
                                            $semaforo_class = 'bg-semaforo-amarillo';
                                            $sla_badge = '<span class="badge bg-warning text-dark">En riesgo</span>';
                                        } else {
                                            $semaforo_class = 'bg-semaforo-rojo';
                                            $sla_badge = '<span class="badge bg-danger">Vencido</span>';
                                        }
                                    }
                                    
                                    // Estado de la solicitud
                                    $est_badge = '';
                                    if ($p['Ins_Est'] === 'F') {
                                        $est_badge = '<span class="badge bg-success">Aprobado</span>';
                                    } elseif ($p['Ins_Est'] === 'R') {
                                        $est_badge = '<span class="badge bg-danger">Rechazado</span>';
                                    } else {
                                        if ($p['Sol_Est'] === 'O') {
                                            $est_badge = '<span class="badge bg-warning text-dark">Observado</span>';
                                        } else {
                                            $est_badge = '<span class="badge bg-primary">En Proceso</span>';
                                        }
                                    }
                                    
                                    $solicitante_nom = $p['Prs_Nom'] ? ($p['Prs_Nom'] . ' ' . $p['Prs_Ape']) : $p['Usu_Nom'];
                                    ?>
                                    <tr class="text-center">
                                        <td class="fw-bold"><?php echo $p['Sol_Num']; ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($p['Sol_Fec'])); ?></td>
                                        <td class="text-start"><?php echo $solicitante_nom; ?></td>
                                        <td class="text-start fw-semibold text-primary"><?php echo $p['Trq_Des']; ?></td>
                                        <td class="fw-bold font-monospace text-success text-end">$ <?php echo number_format($p['Sol_Val_Est'], 2); ?></td>
                                        <td class="text-start"><?php echo $p['Nod_Nom'] ?: '<span class="text-muted">-</span>'; ?></td>
                                        <td><?php echo $p['Dep_Des'] ?: '<span class="text-muted">[General]</span>'; ?></td>
                                        <td><?php echo $est_badge; ?></td>
                                        <td class="text-start">
                                            <span class="semaforo-dot <?php echo $semaforo_class; ?>"></span>
                                            <?php echo $sla_badge; ?>
                                            <span class="text-muted font-monospace" style="font-size: 10px;">
                                                (<?php echo $elapsed_days_fmt; ?>/<?php echo $limit_days !== null ? $limit_days : '-'; ?>d)
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-xs btn-outline-primary py-0" onclick="abrirSeguimiento(<?php echo intval($p['Sol_Cod']); ?>, '<?php echo htmlspecialchars($p['Sol_Num'], ENT_QUOTES, 'UTF-8'); ?>')">
                                                <i class="bi bi-clock-history"></i> Seguimiento
                                            </button>
                                        </td>
                                    </tr>
                            <?php }
                            } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php } ?>
    </div>
        </div>
            </div>
        </div>
    </div>

<!-- MODAL SEGUIMIENTO DETALLADO (SLA) -->
<div class="modal fade" id="mdlSeguimiento" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" style="width:95%;max-width:1200px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="lblSeguimientoTitle">Seguimiento de Requerimiento</h4>
            </div>
            <div class="modal-body" id="seguimientoModalBody" style="max-height:75vh;overflow-y:auto;">
                <!-- Contenido AJAX se inyecta aquí -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>


<script>
    let currentSolCod = null;
    function abrirSeguimiento(solCod, solNum) {
        currentSolCod = solCod;
        const tituloNum = solNum || solCod;
        $('#lblSeguimientoTitle').text('Seguimiento de Requerimiento #' + tituloNum);
        $('#seguimientoModalBody').html('<div class="text-center p-4"><i class="glyphicon glyphicon-refresh glyphicon-spin" style="font-size:24px;"></i><div class="mt-2">Cargando seguimiento...</div></div>');
        
        $('#mdlSeguimiento').modal('show');
        
        $.get('adq_seguimiento.php', { sol_cod: solCod }, function(html) {
            $('#seguimientoModalBody').html(html);
        }).fail(function() {
            $('#seguimientoModalBody').html('<div class="alert alert-danger m-2 small">No se pudo cargar el seguimiento del requerimiento.</div>');
        });
    }

    $(document).ready(function() {
        // Activar pestaña específica por URL si se solicita
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab === 'todos_procesos') {
            $('a[href="#all-processes-panel"]').tab('show');
        }
    });
</script>
</body>
</html>
