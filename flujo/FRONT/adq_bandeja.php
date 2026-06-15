<?php
/**
 * EXA Adquisiciones - Bandeja de Trabajo de Usuarios
 * @author Oz <oz-agent@warp.dev>
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/wf_manager_log.php');

$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new MysqlDatos($obBD_conexion);
$wf_mgr = new wf_manager_log($Ses_Dat_Dis);

// 1. Procesar Acciones de Workflow (Aprobar, Rechazar, Observar, Devolver)
if (isset($ajax_workflow_action)) {
    $ins_cod = intval($_POST['Ins_Cod']);
    $accion = mysqli_real_escape_string($obBD_conexion->conexion, $_POST['Action']);
    $comentario = mysqli_real_escape_string($obBD_conexion->conexion, $_POST['Comentario']);
    
    // Carga de adjunto opcional
    $adjunto_db_path = null;
    if (isset($_FILES['adjunto']) && $_FILES['adjunto']['error'] == 0) {
        $target_dir = "../../DATA/adquisiciones_sustentos/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $name = $_FILES['adjunto']['name'];
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $unique_name = "action_" . uniqid() . "." . $ext;
        if (move_uploaded_file($_FILES['adjunto']['tmp_name'], $target_dir . $unique_name)) {
            $adjunto_db_path = "adquisiciones_sustentos/" . $unique_name;
        }
    }

    $resp = $wf_mgr->procesarAccionUsuario($ins_cod, $accion, $comentario, $adjunto_db_path);
    $obBD_con1->echoJson($resp);
}

// 2. Buscar facturas de compra para vincular a solicitud
if (isset($ajax_buscar_compras)) {
    $search = mysqli_real_escape_string($obBD_conexion->conexion, $_GET['search']);
    $compras = $obBD_con1->getArrayConsultaSql("
        SELECT c.Cop_Cod, c.Cop_Num, c.Cop_Fec, CONCAT(p.Prs_Ape, ' ', p.Prs_Nom) as Proveedor,
               ROUND((SELECT SUM(dc.Cop_Imp - (dc.Cop_Imp * dc.Cop_Dec / 100)) FROM det_compra dc WHERE dc.Cop_Cod = c.Cop_Cod), 2) as Total
        FROM compras c
        INNER JOIN proveedore pr ON pr.Prv_Cod = c.Prv_Cod
        INNER JOIN persona p ON p.Prs_Cod = pr.Prs_Cod
        WHERE c.Cop_Est = 'A' AND pr.Emp_Cod = $Ses_Emp_Cod
          AND (c.Cop_Num LIKE '%$search%' OR p.Prs_Ape LIKE '%$search%' OR p.Prs_Nom LIKE '%$search%')
        ORDER BY c.Cop_Fec DESC LIMIT 20;", $obBD_conexion);
    $obBD_con1->echoJson(array('success' => true, 'compras' => $compras));
}

// 3. Vincular factura de compra a solicitud
if (isset($ajax_vincular_compra)) {
    $sol_cod = intval($_POST['Sol_Cod']);
    $cop_cod = intval($_POST['Cop_Cod']);
    $resp = $wf_mgr->vincularCompra($sol_cod, $cop_cod);
    $obBD_con1->echoJson($resp);
}

// 4. Desvincular factura de compra
if (isset($ajax_desvincular_compra)) {
    $scm_cod = intval($_POST['Scm_Cod']);
    $resp = $wf_mgr->desvincularCompra($scm_cod);
    $obBD_con1->echoJson($resp);
}

// 5. Obtener Detalle Completo de una Solicitud
if (isset($ajax_get_solicitud_detail)) {
    $sol_cod = intval($_GET['sol_cod']);
    
    $sol = $obBD_con1->getRowConsultaSql("
        SELECT s.*, tr.Trq_Des, u.Usu_Nom, d.Dep_Des,
               IFNULL(p.Prs_Nom, '') as Sol_Nom, IFNULL(p.Prs_Ape, '') as Sol_Ape,
               i.Ins_Cod, i.Nod_Act, i.Ins_Est,
               n.Nod_Nom, n.Nod_Tip, n.Nod_Com_Obl, n.Nod_Adj_Obl
        FROM adq_solicitudes s
        INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
        INNER JOIN usuarios u ON u.Usu_Cod = s.Usu_Sol
        INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
        INNER JOIN departamen d ON d.Dep_Cod = s.Dep_Sol
        LEFT JOIN wf_instancias i ON i.Ins_Ent_Typ = 'adq_solicitudes' AND i.Ins_Ent_Cod = s.Sol_Cod
        LEFT JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
        WHERE s.Sol_Cod = $sol_cod;", $obBD_conexion);
        
    $items = $obBD_con1->getArrayConsultaSql("SELECT id.*, pr.Pro_Nom FROM adq_solicitudes_det id LEFT JOIN producto pr ON pr.Pro_Cod = id.Pro_Cod WHERE id.Sol_Cod = $sol_cod ORDER BY id.Sde_Int;", $obBD_conexion);
    $cotizaciones = $obBD_con1->getArrayConsultaSql("SELECT c.*, p.Prs_Nom, p.Prs_Ape, pr.Prv_Com FROM adq_solicitudes_cotizaciones c INNER JOIN proveedore pr ON pr.Prv_Cod = c.Prv_Cod INNER JOIN persona p ON p.Prs_Cod = pr.Prs_Cod WHERE c.Sol_Cod = $sol_cod;", $obBD_conexion);
    $historial = $obBD_con1->getArrayConsultaSql("SELECT h.*, n.Nod_Nom, n.Nod_Tip, p.Prs_Nom, p.Prs_Ape, d.Dep_Des FROM wf_instancias_nodos h INNER JOIN wf_nodos n ON n.Nod_Cod = h.Nod_Cod LEFT JOIN usuarios u ON u.Usu_Cod = h.Usu_Cod LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod LEFT JOIN departamen d ON d.Dep_Cod = h.Dep_Cod WHERE h.Ins_Cod = {$sol['Ins_Cod']} ORDER BY h.Isn_Fec DESC;", $obBD_conexion);
    
    // Obtener árbol visual
    $flow_visual = $wf_mgr->getVisualFlowData($sol['Ins_Cod']);

    // Obtener compras vinculadas
    $compras_vinculadas = $wf_mgr->getComprasVinculadas($sol_cod);

    $obBD_con1->echoJson(array(
        'success' => true,
        'solicitud' => $sol,
        'items' => $items,
        'cotizaciones' => $cotizaciones,
        'historial' => $historial,
        'flow_visual' => $flow_visual,
        'compras_vinculadas' => $compras_vinculadas
    ));
}

// Consultar listas de solicitudes
$usu_cod = $_SESSION['Ses_Usu_Cod'];
$dep_cod = $_SESSION['Ses_Dep_Cod'];
$perfiles_ids = implode(",", $_SESSION['Ses_Lis_Per']);

// A. PENDIENTES DE MI APROBACIÓN (Etapa activa asignada a mi depto o mis perfiles)
$pendientes = $obBD_con1->getArrayConsultaSql("
    SELECT s.*, tr.Trq_Des, CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as Solicitante_Nom, d.Dep_Des, i.Ins_Cod, n.Nod_Nom, n.Nod_Sla
    FROM adq_solicitudes s
    INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
    INNER JOIN usuarios u ON u.Usu_Cod = s.Usu_Sol
    INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
    INNER JOIN departamen d ON d.Dep_Cod = s.Dep_Sol
    INNER JOIN wf_instancias i ON i.Ins_Ent_Typ = 'adq_solicitudes' AND i.Ins_Ent_Cod = s.Sol_Cod AND i.Ins_Est = 'P'
    INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act AND (n.Dep_Cod = $dep_cod OR n.Per_Cod IN ($perfiles_ids))
    WHERE s.Emp_Cod = $Ses_Emp_Cod AND s.Sol_Est IN ('E', 'O')
    ORDER BY s.Sol_Pri DESC, s.Sol_Fec ASC;", $obBD_conexion);

// B. MIS SOLICITUDES EN CURSO (Creadas por mí)
$mis_solicitudes = $obBD_con1->getArrayConsultaSql("
    SELECT s.*, tr.Trq_Des, i.Ins_Cod, n.Nod_Nom
    FROM adq_solicitudes s
    INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
    LEFT JOIN wf_instancias i ON i.Ins_Ent_Typ = 'adq_solicitudes' AND i.Ins_Ent_Cod = s.Sol_Cod
    LEFT JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
    WHERE s.Emp_Cod = $Ses_Emp_Cod AND s.Usu_Sol = $usu_cod AND s.Sol_Est IN ('E', 'O', 'P')
    ORDER BY s.Sol_Fec DESC;", $obBD_conexion);

// C. HISTÓRICO / CERRADOS (Aprobadas, rechazadas o finalizadas)
$historico = $obBD_con1->getArrayConsultaSql("
    SELECT s.*, tr.Trq_Des, CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as Solicitante_Nom, d.Dep_Des
    FROM adq_solicitudes s
    INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
    INNER JOIN usuarios u ON u.Usu_Cod = s.Usu_Sol
    INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
    INNER JOIN departamen d ON d.Dep_Cod = s.Dep_Sol
    WHERE s.Emp_Cod = $Ses_Emp_Cod AND (s.Usu_Sol = $usu_cod OR s.Sol_Est IN ('A', 'R'))
    ORDER BY s.Sol_Fec DESC LIMIT 100;", $obBD_conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bandeja de Adquisiciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .badge-alta { background-color: #dc3545; }
        .badge-media { background-color: #fd7e14; }
        .badge-baja { background-color: #198754; }
        .badge-urgente { background-color: #6f42c1; }
        
        .tracker-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            overflow-x: auto;
            padding: 15px;
            background-color: #f1f3f5;
            border-radius: 8px;
        }
        .tracker-node {
            padding: 8px 12px;
            background-color: #ffffff;
            border: 2px solid #adb5bd;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            color: #495057;
            text-align: center;
            min-width: 120px;
        }
        .tracker-node.color-green { border-color: #198754; background-color: #e2f0d9; color: #198754; }
        .tracker-node.color-blue { border-color: #0d6efd; background-color: #cfe2ff; color: #0d6efd; box-shadow: 0 0 10px rgba(13,110,253,0.3); }
        .tracker-node.color-red { border-color: #dc3545; background-color: #f8d7da; color: #dc3545; }
        .tracker-node.color-grey { border-color: #6c757d; background-color: #f8f9fa; color: #6c757d; }
        .tracker-arrow {
            font-size: 18px;
            color: #6c757d;
        }
    </style>
</head>
<body class="bg-light py-4">
    <div class="container bg-white p-4 rounded shadow-sm">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
            <h3 class="fw-bold m-0 text-primary"><i class="bi bi-inboxes"></i> Bandeja de Adquisiciones</h3>
            <div class="d-flex gap-2">
                <a href="adq_solicitud.php" class="btn btn-sm btn-success"><i class="bi bi-plus-lg"></i> Crear Requerimiento</a>
                <a href="adq_dashboard.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-graph-up-arrow"></i> Dashboard</a>
                <a href="adq_tipos_requerimientos.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-tags"></i> Tipos</a>
                <a href="wf_builder.php" class="btn btn-sm btn-outline-dark"><i class="bi bi-gear"></i> Diseñador Flujos</a>
            </div>
        </div>

        <!-- Tabs de Bandeja -->
        <ul class="nav nav-tabs mb-4" id="inboxTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending-panel" type="button" role="tab"><i class="bi bi-clipboard-check"></i> Mis Pendientes <span class="badge bg-danger ms-1"><?php echo count($pendientes); ?></span></button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="my-tab" data-bs-toggle="tab" data-bs-target="#my-panel" type="button" role="tab"><i class="bi bi-person-workspace"></i> Mis Requerimientos <span class="badge bg-secondary ms-1"><?php echo count($mis_solicitudes); ?></span></button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-panel" type="button" role="tab"><i class="bi bi-clock-history"></i> Historial General</button>
            </li>
        </ul>

        <div class="tab-content" id="inboxTabsContent">
            <!-- 1. MIS PENDIENTES -->
            <div class="tab-pane fade show active" id="pending-panel" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle">
                        <thead class="table-light">
                            <tr class="font-monospace text-center" style="font-size: 13px;">
                                <th style="width: 100px;">Nº Sol.</th>
                                <th style="width: 150px;">Fecha</th>
                                <th>Solicitante</th>
                                <th>Departamento</th>
                                <th>Tipo Pedido</th>
                                <th style="width: 100px;">Prioridad</th>
                                <th style="width: 150px;">Valor Est.</th>
                                <th>Paso Actual Workflow</th>
                                <th style="width: 120px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pendientes)) { ?>
                                <tr class="text-center"><td colspan="9" class="text-muted py-4">No posee requerimientos de adquisiciones pendientes de aprobación en este momento.</td></tr>
                            <?php } else { 
                                foreach ($pendientes as $p) { ?>
                                    <tr class="text-center">
                                        <td class="fw-bold"><?php echo $p['Sol_Num']; ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($p['Sol_Fec'])); ?></td>
                                        <td class="text-start"><?php echo $p['Solicitante_Nom']; ?></td>
                                        <td><?php echo $p['Dep_Des']; ?></td>
                                        <td class="text-start"><?php echo $p['Trq_Des']; ?></td>
                                        <td><span class="badge badge-<?php echo strtolower($p['Sol_Pri']); ?>"><?php echo $p['Sol_Pri']; ?></span></td>
                                        <td class="text-end fw-bold font-monospace">$ <?php echo number_format($p['Sol_Val_Est'], 2); ?></td>
                                        <td><span class="badge bg-primary fs-6"><i class="bi bi-clock"></i> <?php echo $p['Nod_Nom']; ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="abrirResolucion(<?php echo $p['Sol_Cod']; ?>, true)"><i class="bi bi-shield-check"></i> Resolver</button>
                                        </td>
                                    </tr>
                            <?php } 
                            } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. MIS REQUERIMIENTOS -->
            <div class="tab-pane fade" id="my-panel" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle">
                        <thead class="table-light">
                            <tr class="font-monospace text-center" style="font-size: 13px;">
                                <th style="width: 100px;">Nº Sol.</th>
                                <th style="width: 150px;">Fecha</th>
                                <th>Tipo Pedido</th>
                                <th style="width: 100px;">Prioridad</th>
                                <th style="width: 150px;">Valor Est.</th>
                                <th>Estado Solicitud</th>
                                <th>Etapa Workflow</th>
                                <th style="width: 100px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mis_solicitudes)) { ?>
                                <tr class="text-center"><td colspan="8" class="text-muted py-4">No ha iniciado requerimientos de adquisición aún.</td></tr>
                            <?php } else { 
                                foreach ($mis_solicitudes as $ms) { 
                                    $est = 'Borrador'; $badge = 'secondary';
                                    if ($ms['Sol_Est'] == 'E') { $est = 'En Workflow'; $badge = 'primary'; }
                                    elseif ($ms['Sol_Est'] == 'A') { $est = 'Aprobada'; $badge = 'success'; }
                                    elseif ($ms['Sol_Est'] == 'R') { $est = 'Rechazada'; $badge = 'danger'; }
                                    elseif ($ms['Sol_Est'] == 'O') { $est = 'Observada'; $badge = 'warning text-dark'; }
                                    ?>
                                    <tr class="text-center">
                                        <td class="fw-bold"><?php echo $ms['Sol_Num']; ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($ms['Sol_Fec'])); ?></td>
                                        <td class="text-start"><?php echo $ms['Trq_Des']; ?></td>
                                        <td><span class="badge badge-<?php echo strtolower($ms['Sol_Pri']); ?>"><?php echo $ms['Sol_Pri']; ?></span></td>
                                        <td class="text-end fw-bold font-monospace">$ <?php echo number_format($ms['Sol_Val_Est'], 2); ?></td>
                                        <td><span class="badge bg-<?php echo $badge; ?>"><?php echo $est; ?></span></td>
                                        <td><span class="text-dark fw-bold"><?php echo $ms['Nod_Nom'] ?: '[Inactivo]'; ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-dark" onclick="abrirResolucion(<?php echo $ms['Sol_Cod']; ?>, false)"><i class="bi bi-eye"></i> Detalle</button>
                                        </td>
                                    </tr>
                            <?php } 
                            } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. HISTORIAL GENERAL -->
            <div class="tab-pane fade" id="history-panel" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle">
                        <thead class="table-light">
                            <tr class="font-monospace text-center" style="font-size: 13px;">
                                <th style="width: 100px;">Nº Sol.</th>
                                <th style="width: 150px;">Fecha</th>
                                <th>Solicitante</th>
                                <th>Departamento</th>
                                <th>Tipo Pedido</th>
                                <th style="width: 100px;">Prioridad</th>
                                <th style="width: 150px;">Valor Est.</th>
                                <th>Estado</th>
                                <th style="width: 100px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historico as $h) { 
                                $est = 'Borrador'; $badge = 'secondary';
                                if ($h['Sol_Est'] == 'E') { $est = 'En Proceso'; $badge = 'primary'; }
                                elseif ($h['Sol_Est'] == 'A') { $est = 'Aprobada'; $badge = 'success'; }
                                elseif ($h['Sol_Est'] == 'R') { $est = 'Rechazada'; $badge = 'danger'; }
                                elseif ($h['Sol_Est'] == 'O') { $est = 'Observada'; $badge = 'warning text-dark'; }
                                ?>
                                <tr class="text-center">
                                    <td class="fw-bold"><?php echo $h['Sol_Num']; ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($h['Sol_Fec'])); ?></td>
                                    <td class="text-start"><?php echo $h['Solicitante_Nom']; ?></td>
                                    <td><?php echo $h['Dep_Des']; ?></td>
                                    <td class="text-start"><?php echo $h['Trq_Des']; ?></td>
                                    <td><span class="badge badge-<?php echo strtolower($h['Sol_Pri']); ?>"><?php echo $h['Sol_Pri']; ?></span></td>
                                    <td class="text-end fw-bold font-monospace">$ <?php echo number_format($h['Sol_Val_Est'], 2); ?></td>
                                    <td><span class="badge bg-<?php echo $badge; ?>"><?php echo $est; ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-dark" onclick="abrirResolucion(<?php echo $h['Sol_Cod']; ?>, false)"><i class="bi bi-eye"></i> Detalle</button>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- RESOLUTION MODAL -->
    <div class="modal fade" id="mdlResolution" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="lblModalTitle">Detalle de Solicitud de Adquisición</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Tracker Visual de Progreso por Colores -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted"><i class="bi bi-activity"></i> Progreso Visual del Workflow</label>
                        <div class="tracker-wrapper" id="flowTracker">
                            <!-- Los pasos del workflow dinámicos se inyectan aquí -->
                        </div>
                    </div>

                    <!-- Datos Generales -->
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <span class="text-muted d-block" style="font-size: 11px;">SOLICITANTE</span>
                            <span class="fw-bold fs-6" id="detSolicitante"></span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block" style="font-size: 11px;">DEPARTAMENTO</span>
                            <span class="fw-bold fs-6" id="detDepartamento"></span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block" style="font-size: 11px;">TIPO DE PEDIDO</span>
                            <span class="fw-bold text-primary fs-6" id="detTipo"></span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block" style="font-size: 11px;">VALOR ESTIMADO</span>
                            <span class="fw-bold font-monospace text-dark fs-5" id="detTotal"></span>
                        </div>
                        <div class="col-12">
                            <span class="text-muted d-block" style="font-size: 11px;">JUSTIFICACIÓN</span>
                            <p class="mb-0 bg-light p-2 rounded" id="detJustificacion" style="font-size: 13px;"></p>
                        </div>
                    </div>

                    <!-- Ítems Solicitados -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted"><i class="bi bi-cart"></i> Ítems Requeridos</label>
                        <table class="table table-bordered table-sm align-middle" style="font-size: 13px;" id="tblDetItems">
                            <thead class="table-light">
                                <tr class="text-center font-monospace">
                                    <th style="width: 40px;">#</th>
                                    <th>Descripción / Artículo</th>
                                    <th style="width: 100px;">Cant.</th>
                                    <th style="width: 120px;">V. Unit. Est.</th>
                                    <th style="width: 120px;">Total</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <!-- Cotizaciones de Sustento -->
                    <div class="mb-4" id="divDetCotizaciones">
                        <label class="form-label fw-bold text-muted"><i class="bi bi-file-earmark-pdf"></i> Cotizaciones de Sustento</label>
                        <div class="row g-2" id="detCotizacionesList"></div>
                    </div>

                    <!-- Historial del Workflow -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted"><i class="bi bi-list-stars"></i> Historial del Workflow</label>
                        <ul class="list-group list-group-flush" id="lstHistorial" style="font-size: 12px;"></ul>
                    </div>

                    <!-- Compras Vinculadas -->
                    <div class="mb-4" id="divComprasVinculadas" style="display: none;">
                        <label class="form-label fw-bold text-muted"><i class="bi bi-link-45deg"></i> Facturas de Compra Vinculadas</label>
                        <div id="lstComprasVinculadas"></div>
                    </div>

                    <!-- Panel de Vinculación de Compra (solo en nodo FACTURA) -->
                    <div class="card p-3 border-info bg-info-subtle mb-4" id="panelVincularCompra" style="display: none;">
                        <h6 class="fw-bold text-info mb-3 border-bottom pb-2"><i class="bi bi-receipt"></i> Vincular Factura de Compra EXA</h6>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Buscar factura por N° o Proveedor</label>
                            <input type="text" id="txtBuscarCompra" class="form-control form-control-sm" placeholder="Ej. 001-001-0001234 o nombre del proveedor..." oninput="buscarComprasVincular()">
                        </div>
                        <div class="table-responsive" id="divResultCompras" style="display: none;">
                            <table class="table table-sm table-bordered table-hover" style="font-size: 12px;">
                                <thead class="table-light">
                                    <tr class="text-center font-monospace">
                                        <th>N° Factura</th>
                                        <th>Fecha</th>
                                        <th>Proveedor</th>
                                        <th>Total</th>
                                        <th style="width: 80px;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tblBuscarCompras"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Panel de Decisión del Aprobador -->
                    <div class="card p-3 border-primary bg-light-subtle" id="panelDecision" style="display: none;">
                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="bi bi-check2-all"></i> Decisión / Resolución en esta Etapa (<span id="lblNodeActionName"></span>)</h6>
                        <form id="frmWorkflowAction" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="Ins_Cod" id="actionInsCod">
                            <input type="hidden" name="Action" id="actionName">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Comentario u Observaciones <span id="lblComReq" class="text-danger" style="display: none;">*</span></label>
                                <textarea class="form-control" name="Comentario" id="actionComentario" rows="2" placeholder="Redacte el motivo de su decisión..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Sustento Adjunto (PDF/Imagen) <span id="lblAdjReq" class="text-danger" style="display: none;">*</span></label>
                                <input type="file" class="form-control form-control-sm" name="adjunto" id="actionAdjunto">
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-success" onclick="enviarAccion('APROBAR')"><i class="bi bi-check-circle"></i> Aprobar / Autorizar</button>
                                <button type="button" class="btn btn-warning text-dark" onclick="enviarAccion('OBSERVAR')"><i class="bi bi-exclamation-triangle"></i> Observar / Detener</button>
                                <button type="button" class="btn btn-secondary" onclick="enviarAccion('DEVOLVER')"><i class="bi bi-reply"></i> Devolver Paso</button>
                                <button type="button" class="btn btn-danger" onclick="enviarAccion('RECHAZAR')"><i class="bi bi-x-circle"></i> Rechazar Requerimiento</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentInsCod = null;
        let currentSolCod = null;
        let isComObl = false;
        let isAdjObl = false;
        let searchTimer = null;

        function abrirResolucion(solCod, renderPanelAction) {
            $.getJSON('', { ajax_get_solicitud_detail: true, sol_cod: solCod }, function(res) {
                if (res.success) {
                    const sol = res.solicitud;
                    currentInsCod = sol.Ins_Cod;

                    // Cabecera
                    $('#lblModalTitle').text(`Solicitud de Adquisición Nº ${sol.Sol_Num}`);
                    $('#detSolicitante').text(`${sol.Sol_Nom} ${sol.Sol_Ape}`);
                    $('#detDepartamento').text(sol.Dep_Des);
                    $('#detTipo').text(sol.Trq_Des);
                    $('#detTotal').text(`$ ${parseFloat(sol.Sol_Val_Est).toFixed(2)}`);
                    $('#detJustificacion').text(sol.Sol_Jus);

                    // Ítems
                    const $tbody = $('#tblDetItems tbody').empty();
                    res.items.forEach(function(item, idx) {
                        $tbody.append(`
                            <tr class="text-center">
                                <td>${idx + 1}</td>
                                <td class="text-start">${item.Sde_Des}</td>
                                <td>${parseFloat(item.Sde_Can).toFixed(4)}</td>
                                <td class="text-end">$ ${parseFloat(item.Sde_Pru).toFixed(2)}</td>
                                <td class="text-end fw-bold">$ ${(parseFloat(item.Sde_Can) * parseFloat(item.Sde_Pru)).toFixed(2)}</td>
                            </tr>
                        `);
                    });

                    // Cotizaciones
                    const $cotList = $('#detCotizacionesList').empty();
                    if (res.cotizaciones.length > 0) {
                        $('#divDetCotizaciones').show();
                        res.cotizaciones.forEach(function(c) {
                            const ganadorClass = c.Cot_Sel == 1 ? 'border-success bg-success-subtle' : '';
                            const badgeGanador = c.Cot_Sel == 1 ? '<span class="badge bg-success float-end"><i class="bi bi-trophy"></i> Seleccionada</span>' : '';
                            $cotList.append(`
                                <div class="col-md-6">
                                    <div class="card p-2 ${ganadorClass}" style="font-size: 12px;">
                                        <span class="fw-bold">${badgeGanador}${c.Prv_Com || (c.Prs_Nom + ' ' + c.Prs_Ape)}</span>
                                        <span>Valor: <strong>$ ${parseFloat(c.Cot_Val).toFixed(2)}</strong></span>
                                        <a href="../../DATA/${c.Cot_Adj}" target="_blank" class="btn btn-xs btn-outline-primary py-0 mt-2 text-decoration-none"><i class="bi bi-file-earmark-pdf"></i> Ver Sustento PDF</a>
                                        ${c.Cot_Sel == 1 && c.Cot_Jus ? `<div class="mt-2 text-muted" style="font-size: 11px;"><strong>Justificación:</strong> ${c.Cot_Jus}</div>` : ''}
                                    </div>
                                </div>
                            `);
                        });
                    } else {
                        $('#divDetCotizaciones').hide();
                    }

                    // Historial
                    const $hist = $('#lstHistorial').empty();
                    res.historial.forEach(function(h) {
                        const actor = h.Usuario_Nom || h.Dep_Des || 'Sistema';
                        let actionBadge = '';
                        if (h.Isn_Acc === 'CREAR') actionBadge = '<span class="badge bg-secondary">Inició Pedido</span>';
                        else if (h.Isn_Acc === 'APROBAR') actionBadge = '<span class="badge bg-success">Aprobó</span>';
                        else if (h.Isn_Acc === 'OBSERVAR') actionBadge = '<span class="badge bg-warning text-dark">Observó</span>';
                        else if (h.Isn_Acc === 'DEVOLVER') actionBadge = '<span class="badge bg-secondary">Devolvió</span>';
                        else if (h.Isn_Acc === 'RECHAZAR') actionBadge = '<span class="badge bg-danger">Rechazó</span>';

                        $hist.append(`
                            <li class="list-group-item d-flex justify-content-between align-items-start px-0">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">${actionBadge} en etapa: <strong>${h.Nod_Nom}</strong> por <span class="text-primary">${actor}</span></div>
                                    ${h.Isn_Com ? `<span class="text-muted d-block mt-1 bg-light p-1 rounded" style="font-size:11px;">"${h.Isn_Com}"</span>` : ''}
                                    ${h.Isn_Adj ? `<a href="../../DATA/${h.Isn_Adj}" target="_blank" class="d-inline-block mt-1 py-0 px-1 btn btn-xs btn-outline-secondary" style="font-size: 10px;"><i class="bi bi-paperclip"></i> Sustento Adjunto</a>` : ''}
                                </div>
                                <span class="badge bg-light text-dark font-monospace" style="font-size: 10px;">${h.Isn_Fec}</span>
                            </li>
                        `);
                    });

                    // Progreso Visual del Workflow por Colores (Fase 3.2 tracker)
                    const $tracker = $('#flowTracker').empty();
                    res.flow_visual.nodos.forEach(function(node, index) {
                        if (index > 0) {
                            $tracker.append('<div class="tracker-arrow"><i class="bi bi-arrow-right-short"></i></div>');
                        }
                        $tracker.append(`
                            <div class="tracker-node color-${node.color}">
                                <i class="bi bi-circle-fill"></i> ${node.nombre}<br>
                                <span style="font-size: 9px; font-weight: normal; opacity: 0.8;">[${node.tipo}]</span>
                            </div>
                        `);
                    });

                    // Compras vinculadas
                    currentSolCod = sol.Sol_Cod;
                    const comprasV = res.compras_vinculadas || [];
                    const $comprasList = $('#lstComprasVinculadas').empty();
                    if (comprasV.length > 0) {
                        $('#divComprasVinculadas').show();
                        comprasV.forEach(function(cv) {
                            $comprasList.append(`
                                <div class="card p-2 mb-2 border-success bg-success-subtle d-flex flex-row justify-content-between align-items-center" style="font-size: 12px;">
                                    <div>
                                        <strong><i class="bi bi-receipt-cutoff"></i> Factura # ${cv.Cop_Num}</strong> — ${cv.Proveedor} 
                                        <span class="text-muted">(${cv.Cop_Fec})</span>
                                        <span class="fw-bold font-monospace text-dark ms-2">$ ${parseFloat(cv.Total_Compra || 0).toFixed(2)}</span>
                                    </div>
                                    <button class="btn btn-xs btn-outline-danger p-1 py-0 border-0" onclick="desvincularCompra(${cv.Scm_Cod}, ${sol.Sol_Cod})"><i class="bi bi-x-lg"></i></button>
                                </div>
                            `);
                        });
                    } else {
                        $('#divComprasVinculadas').hide();
                    }

                    // Panel de vinculación de compra (solo en nodos FACTURA)
                    if (renderPanelAction && sol.Nod_Tip === 'FACTURA' && sol.Ins_Est === 'P') {
                        $('#panelVincularCompra').show();
                        $('#txtBuscarCompra').val('');
                        $('#divResultCompras').hide();
                    } else {
                        $('#panelVincularCompra').hide();
                    }

                    // Panel de acción
                    if (renderPanelAction && sol.Ins_Est === 'P') {
                        isComObl = parseInt(sol.Nod_Com_Obl) === 1;
                        isAdjObl = parseInt(sol.Nod_Adj_Obl) === 1;

                        $('#lblNodeActionName').text(sol.Nod_Nom);
                        $('#actionInsCod').val(sol.Ins_Cod);
                        $('#actionComentario').val('');
                        $('#actionAdjunto').val('');

                        $('#lblComReq').toggle(isComObl);
                        $('#lblAdjReq').toggle(isAdjObl);

                        $('#panelDecision').show();
                    } else {
                        $('#panelDecision').hide();
                    }

                    // Abrir modal
                    const modal = new bootstrap.Modal(document.getElementById('mdlResolution'));
                    modal.show();
                } else {
                    alert('No se pudo cargar el detalle: ' + res.message);
                }
            });
        }

        function buscarComprasVincular() {
            clearTimeout(searchTimer);
            const term = $('#txtBuscarCompra').val().trim();
            if (term.length < 2) {
                $('#divResultCompras').hide();
                return;
            }
            searchTimer = setTimeout(function() {
                $.getJSON('', { ajax_buscar_compras: true, search: term }, function(res) {
                    if (res.success) {
                        const $tbody = $('#tblBuscarCompras').empty();
                        if (res.compras.length === 0) {
                            $tbody.append('<tr><td colspan="5" class="text-center text-muted">No se encontraron facturas.</td></tr>');
                        } else {
                            res.compras.forEach(function(c) {
                                $tbody.append(`
                                    <tr class="text-center">
                                        <td class="fw-bold">${c.Cop_Num}</td>
                                        <td>${c.Cop_Fec}</td>
                                        <td class="text-start">${c.Proveedor}</td>
                                        <td class="text-end font-monospace">$ ${parseFloat(c.Total || 0).toFixed(2)}</td>
                                        <td><button class="btn btn-xs btn-success p-1 py-0" onclick="vincularCompra(${c.Cop_Cod})"><i class="bi bi-link-45deg"></i></button></td>
                                    </tr>
                                `);
                            });
                        }
                        $('#divResultCompras').show();
                    }
                });
            }, 350);
        }

        function vincularCompra(copCod) {
            $.post('?ajax_vincular_compra=1', { Sol_Cod: currentSolCod, Cop_Cod: copCod }, function(res) {
                if (res.success) {
                    alert('Factura vinculada correctamente a la solicitud.');
                    abrirResolucion(currentSolCod, true);
                } else {
                    alert('Error: ' + res.message);
                }
            }, 'json');
        }

        function desvincularCompra(scmCod, solCod) {
            if (!confirm('¿Desea desvincular esta factura de compra?')) return;
            $.post('?ajax_desvincular_compra=1', { Scm_Cod: scmCod }, function(res) {
                if (res.success) {
                    abrirResolucion(solCod, true);
                } else {
                    alert('Error: ' + res.message);
                }
            }, 'json');
        }

        function enviarAccion(accion) {
            if (accion === 'APROBAR') {
                if (isComObl && !$('#actionComentario').val().trim()) {
                    alert('El comentario es obligatorio para aprobar en esta etapa.');
                    return;
                }
                if (isAdjObl && !$('#actionAdjunto').val()) {
                    alert('Cargar un archivo adjunto de soporte es obligatorio en esta etapa.');
                    return;
                }
            }

            $('#actionName').val(accion);
            const formData = new FormData($('#frmWorkflowAction')[0]);

            $.ajax({
                url: '?ajax_workflow_action=1',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        alert(`¡Acción '${accion}' procesada correctamente en el Workflow!`);
                        window.location.reload();
                    } else {
                        alert('Error al procesar acción: ' + res.message);
                    }
                },
                error: function() {
                    alert('Error crítico de red al comunicarse con el servidor.');
                }
            });
        }
    </script>
</body>
</html>
