<?php
/**
 * EXA Adquisiciones - Bandeja de Trabajo de Usuarios
 * @author Oz <oz-agent@warp.dev>
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/wf_manager_log.php');
require_once('../LOGICA/adq_adquisiciones_log.php');

$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new MysqlDatos($obBD_conexion);
$obBD_adq = new adq_adquisiciones_log($obBD_conexion);
$wf_mgr = new wf_manager_log($Ses_Dat_Dis);
$wf_ctx = $wf_mgr->resolverContextoUsuario($Ses_Emp_Cod);
$clausula_nodo_usuario = $wf_mgr->sqlClausulaNodoAsignadoAUsuario($wf_ctx['usu_cod'], $wf_ctx['dep_cod'], $wf_ctx['perfiles_ids']);

$ajax_workflow_action = isset($_GET['ajax_workflow_action']) ? $_GET['ajax_workflow_action'] : (isset($_POST['ajax_workflow_action']) ? $_POST['ajax_workflow_action'] : null);
$ajax_enviar_borrador = isset($_POST['ajax_enviar_borrador']) ? $_POST['ajax_enviar_borrador'] : null;
$ajax_buscar_compras = isset($_GET['ajax_buscar_compras']) ? $_GET['ajax_buscar_compras'] : null;
$ajax_vincular_compra = isset($_GET['ajax_vincular_compra']) ? $_GET['ajax_vincular_compra'] : (isset($_POST['ajax_vincular_compra']) ? $_POST['ajax_vincular_compra'] : null);
$ajax_desvincular_compra = isset($_POST['ajax_desvincular_compra']) ? $_POST['ajax_desvincular_compra'] : null;
$ajax_get_solicitud_detail = isset($_GET['ajax_get_solicitud_detail']) ? $_GET['ajax_get_solicitud_detail'] : null;

// Verificar acceso a la ventana 'bandeja'
if (!$wf_mgr->verificarAccesoVentana('bandeja')) {
    if (isset($ajax_workflow_action) || isset($ajax_buscar_compras) || isset($ajax_vincular_compra) || isset($ajax_desvincular_compra) || isset($ajax_get_solicitud_detail) || isset($ajax_enviar_borrador)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Acceso denegado. No tiene permisos para realizar esta acción.'));
        exit;
    } else {
        echo "<div class='alert alert-danger m-3'>Acceso denegado. No tiene permisos para ver esta ventana.</div>";
        exit;
    }
}

// 1. Procesar Acciones de Workflow (Aprobar, Rechazar, Observar, Devolver)
if (isset($ajax_workflow_action)) {
    $ins_cod = intval($_POST['Ins_Cod']);
    $accion = mysqli_real_escape_string($obBD_conexion->conexion, $_POST['Action']);
    $comentario = mysqli_real_escape_string($obBD_conexion->conexion, $_POST['Comentario']);
    
    // Validar si el usuario tiene permiso para procesar este paso del workflow
    $usu_cod = $wf_ctx['usu_cod'];
    $dep_cod = $wf_ctx['dep_cod'];
    $perfiles_ids = $wf_ctx['perfiles_ids'];
    
    $check_perm = $obBD_con1->getRowConsultaSql("
        SELECT n.Nod_Cod 
        FROM wf_instancias i
        INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
        WHERE i.Ins_Cod = $ins_cod AND i.Ins_Est = 'P' AND $clausula_nodo_usuario;", $obBD_conexion);
        
    if (empty($check_perm)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Acceso denegado. No tiene permisos para procesar esta etapa del requerimiento.'));
        exit;
    }
    
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
    exit;
}

if (isset($ajax_enviar_borrador)) {
    $sol_cod = intval($_POST['Sol_Cod']);
    $resp = $obBD_adq->enviarBorrador($sol_cod);
    $obBD_con1->echoJson($resp);
    exit;
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

    if ($sol_cod <= 0) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Codigo de solicitud invalido.'));
    }

    $sol = $obBD_con1->getRowConsultaSql("
        SELECT s.*, tr.Trq_Des,
               tr.Trq_Req_Fac, tr.Trq_Per_Cie, tr.Trq_Req_Cot, tr.Trq_Min_Cot,
               tr.Trq_Req_Pre, tr.Trq_Req_Adj, tr.Trq_Req_Pro, tr.Trq_Tiempo_Est,
               IFNULL(u.Usu_Ced, '') as Usu_Nom,
               IFNULL(d.Dep_Des, '') as Dep_Des,
               IFNULL(p.Prs_Nom, '') as Sol_Nom, IFNULL(p.Prs_Ape, '') as Sol_Ape,
               i.Ins_Cod, i.Nod_Act, i.Ins_Est,
               n.Nod_Nom, n.Nod_Tip, n.Nod_Com_Obl, n.Nod_Adj_Obl
        FROM adq_solicitudes s
        INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
        LEFT JOIN usuarios u ON u.Usu_Cod = s.Usu_Sol
        LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod
        LEFT JOIN departamen d ON d.Dep_Cod = s.Dep_Sol
        LEFT JOIN wf_instancias i ON i.Ins_Cod = (
            SELECT MAX(i2.Ins_Cod)
            FROM wf_instancias i2
            WHERE i2.Ins_Ent_Typ = 'adq_solicitudes' AND i2.Ins_Ent_Cod = s.Sol_Cod
        )
        LEFT JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
        WHERE s.Sol_Cod = $sol_cod;", $obBD_conexion);

    if (empty($sol)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'No se encontro la solicitud solicitada.'));
    }

    $sol = $obBD_adq->aplicarRequisitosEfectivos($sol);

    $items = $obBD_con1->getArrayConsultaSql("
        SELECT d.*, i.Ite_Lar AS Pro_Nom
        FROM adq_solicitudes_det d
        LEFT JOIN producto pr ON pr.Pro_Cod = d.Pro_Cod
        LEFT JOIN item i ON i.Ite_Cod = pr.Ite_Cod
        WHERE d.Sol_Cod = $sol_cod
        ORDER BY d.Sde_Int;", $obBD_conexion);
    if ($items === false || $items === null) {
        $items = array();
    }
    $cotizaciones = $obBD_con1->getArrayConsultaSql("SELECT c.*, p.Prs_Nom, p.Prs_Ape, pr.Prv_Com FROM adq_solicitudes_cotizaciones c INNER JOIN proveedore pr ON pr.Prv_Cod = c.Prv_Cod INNER JOIN persona p ON p.Prs_Cod = pr.Prs_Cod WHERE c.Sol_Cod = $sol_cod;", $obBD_conexion);
    if ($cotizaciones === false || $cotizaciones === null) {
        $cotizaciones = array();
    }

    $historial = array();
    if (!empty($sol['Ins_Cod'])) {
        $historial = $obBD_con1->getArrayConsultaSql("
            SELECT h.*,
                   COALESCE(n.Nod_Nom, CONCAT('Nodo #', h.Nod_Cod)) AS Nod_Nom,
                   COALESCE(n.Nod_Tip, 'PASO') AS Nod_Tip,
                   TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Usuario_Nom,
                   p.Prs_Nom, p.Prs_Ape,
                   d.Wde_Des AS Dep_Des
            FROM wf_instancias_nodos h
            LEFT JOIN wf_nodos n ON n.Nod_Cod = h.Nod_Cod
            LEFT JOIN usuarios u ON u.Usu_Cod = h.Usu_Cod
            LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod
            LEFT JOIN wf_departamentos d ON d.Wde_Cod = h.Dep_Cod
            WHERE h.Ins_Cod = {$sol['Ins_Cod']}
            ORDER BY h.Isn_Fec DESC;", $obBD_conexion);
        if ($historial === false || $historial === null) {
            $historial = array();
        }
    }

    $flow_visual = !empty($sol['Ins_Cod']) ? $wf_mgr->getVisualFlowData($sol['Ins_Cod']) : array('nodos' => array());
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
    exit;
}

// Consultar listas de solicitudes
$usu_cod = $wf_ctx['usu_cod'];
$dep_cod = $wf_ctx['dep_cod'];
$perfiles_ids = $wf_ctx['perfiles_ids'];
$emp_cod = intval($Ses_Emp_Cod);

$wf_mgr->repararInstanciasEnInicio('adq_solicitudes');

// A. PENDIENTES DE MI APROBACIÓN (Etapa activa asignada a mi depto o mis perfiles)
$pendientes = $obBD_con1->getArrayConsultaSql("
    SELECT s.*, tr.Trq_Des, CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as Solicitante_Nom, d.Dep_Des, i.Ins_Cod, n.Nod_Nom, n.Nod_Sla
    FROM adq_solicitudes s
    INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
    INNER JOIN usuarios u ON u.Usu_Cod = s.Usu_Sol
    INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
    LEFT JOIN departamen d ON d.Dep_Cod = s.Dep_Sol
    INNER JOIN wf_instancias i ON i.Ins_Ent_Typ = 'adq_solicitudes' AND i.Ins_Ent_Cod = s.Sol_Cod AND i.Ins_Est = 'P'
    INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act AND $clausula_nodo_usuario
    WHERE s.Emp_Cod = $emp_cod AND s.Sol_Est IN ('E', 'O')
    ORDER BY s.Sol_Pri DESC, s.Sol_Fec ASC;", $obBD_conexion);
if ($pendientes === false || $pendientes === null) {
    $pendientes = array();
}

// B. MIS SOLICITUDES EN CURSO (Creadas por mí)
$mis_solicitudes = $obBD_con1->getArrayConsultaSql("
    SELECT s.*, tr.Trq_Des, i.Ins_Cod, n.Nod_Nom
    FROM adq_solicitudes s
    INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
    LEFT JOIN wf_instancias i ON i.Ins_Ent_Typ = 'adq_solicitudes' AND i.Ins_Ent_Cod = s.Sol_Cod
    LEFT JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
    WHERE s.Emp_Cod = $Ses_Emp_Cod AND s.Usu_Sol = $usu_cod AND s.Sol_Est IN ('E', 'O', 'P')
    ORDER BY s.Sol_Fec DESC;", $obBD_conexion);

// C. GESTIONÉ / PARTICIPÉ (solicitudes de otros en las que actué en el workflow)
$gestionadas = $obBD_con1->getArrayConsultaSql("
    SELECT s.*, tr.Trq_Des,
           CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) AS Solicitante_Nom,
           d.Dep_Des,
           i.Ins_Cod, i.Ins_Est AS Ins_Est_Act,
           n.Nod_Nom AS Etapa_Actual,
           h_last.Isn_Acc AS Mi_Accion,
           h_last.Isn_Fec AS Mi_Fecha,
           hn.Nod_Nom AS Mi_Etapa
    FROM adq_solicitudes s
    INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
    INNER JOIN usuarios u ON u.Usu_Cod = s.Usu_Sol
    INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
    LEFT JOIN departamen d ON d.Dep_Cod = s.Dep_Sol
    INNER JOIN wf_instancias i ON i.Ins_Cod = (
        SELECT MAX(i2.Ins_Cod)
        FROM wf_instancias i2
        WHERE i2.Ins_Ent_Typ = 'adq_solicitudes' AND i2.Ins_Ent_Cod = s.Sol_Cod
    )
    INNER JOIN (
        SELECT h1.Ins_Cod, h1.Isn_Acc, h1.Isn_Fec, h1.Nod_Cod
        FROM wf_instancias_nodos h1
        INNER JOIN (
            SELECT Ins_Cod, MAX(Isn_Fec) AS max_fec
            FROM wf_instancias_nodos
            WHERE Usu_Cod = $usu_cod AND Isn_Acc IN ('APROBAR', 'OBSERVAR', 'DEVOLVER', 'RECHAZAR')
            GROUP BY Ins_Cod
        ) hmx ON hmx.Ins_Cod = h1.Ins_Cod AND hmx.max_fec = h1.Isn_Fec AND h1.Usu_Cod = $usu_cod
    ) h_last ON h_last.Ins_Cod = i.Ins_Cod
    LEFT JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
    LEFT JOIN wf_nodos hn ON hn.Nod_Cod = h_last.Nod_Cod
    WHERE s.Emp_Cod = $emp_cod AND s.Usu_Sol != $usu_cod
    ORDER BY h_last.Isn_Fec DESC
    LIMIT 100;", $obBD_conexion);
if ($gestionadas === false || $gestionadas === null) {
    $gestionadas = array();
}

$es_gerencial_admin = ($usu_cod == 1) || (isset($_SESSION['Ses_Lis_Per']) && count(array_intersect(array(1, 2), $_SESSION['Ses_Lis_Per'])) > 0);

// D. HISTORIAL (cerrados: propios + donde participo; gerencia ve todos)
$historico_filtro_usuario = '';
if (!$es_gerencial_admin) {
    $historico_filtro_usuario = " AND (
        s.Usu_Sol = $usu_cod
        OR EXISTS (
            SELECT 1
            FROM wf_instancias i
            INNER JOIN wf_instancias_nodos h ON h.Ins_Cod = i.Ins_Cod
            WHERE i.Ins_Ent_Typ = 'adq_solicitudes'
              AND i.Ins_Ent_Cod = s.Sol_Cod
              AND h.Usu_Cod = $usu_cod
              AND h.Isn_Acc IN ('APROBAR', 'OBSERVAR', 'DEVOLVER', 'RECHAZAR')
        )
    )";
}

$historico = $obBD_con1->getArrayConsultaSql("
    SELECT s.*, tr.Trq_Des, CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as Solicitante_Nom, d.Dep_Des
    FROM adq_solicitudes s
    INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
    INNER JOIN usuarios u ON u.Usu_Cod = s.Usu_Sol
    INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
    LEFT JOIN departamen d ON d.Dep_Cod = s.Dep_Sol
    WHERE s.Emp_Cod = $Ses_Emp_Cod AND s.Sol_Est IN ('A', 'R') $historico_filtro_usuario
    ORDER BY s.Sol_Fec DESC LIMIT 100;", $obBD_conexion);
if ($historico === false || $historico === null) {
    $historico = array();
}

function adqEtiquetaEstadoSolicitud($sol_est) {
    switch ($sol_est) {
        case 'P': return array('Borrador', 'secondary');
        case 'E': return array('En proceso', 'primary');
        case 'A': return array('Aprobada', 'success');
        case 'R': return array('Rechazada', 'danger');
        case 'O': return array('Observada', 'warning text-dark');
        default:  return array('Desconocido', 'secondary');
    }
}

function adqEtiquetaMiAccion($accion) {
    switch ($accion) {
        case 'APROBAR':  return array('Aprob' . "\xC3\xA9", 'success');
        case 'OBSERVAR': return array('Observ' . "\xC3\xB3", 'warning text-dark');
        case 'DEVOLVER': return array('Devolv' . "\xC3\xAD", 'secondary');
        case 'RECHAZAR': return array('Rechaz' . "\xC3\xB3", 'danger');
        default:         return array($accion, 'secondary');
    }
}
?>
<!DOCTYPE html>
<html lang="es" class="exa-ui-fill-root">
<head>
    <meta charset="UTF-8">
    <title>Bandeja de Adquisiciones</title>
    <?php require_once('adq_model3_assets.php'); ?>
</head>
<body class="exa-ui-fill-root">
    <div class="panel panel-main exa-ui-panel exa-ui-fill-page">
        <div class="panel-heading exa-header exa-header-flex">
            <h3 class="panel-title"><i class="bi bi-inboxes"></i> Bandeja de Adquisiciones</h3>
        </div>
        <div class="panel-body exa-body">
            <div class="exa-ui-page-view">
        <ul class="nav nav-tabs exa-ui-nav-tabs" id="inboxTabs" role="tablist">
            <li role="presentation" class="active">
                <a href="#pending-panel" id="pending-tab" role="tab" data-toggle="tab"><i class="bi bi-clipboard-check"></i> Mis Pendientes <span class="badge"><?php echo count($pendientes); ?></span></a>
            </li>
            <li role="presentation">
                <a href="#my-panel" id="my-tab" role="tab" data-toggle="tab"><i class="bi bi-person-workspace"></i> Mis Solicitudes <span class="badge"><?php echo count($mis_solicitudes); ?></span></a>
            </li>
            <li role="presentation">
                <a href="#managed-panel" id="managed-tab" role="tab" data-toggle="tab"><i class="bi bi-check2-square"></i> Gestion&eacute; <span class="badge"><?php echo count($gestionadas); ?></span></a>
            </li>
            <li role="presentation">
                <a href="#history-panel" id="history-tab" role="tab" data-toggle="tab"><i class="bi bi-clock-history"></i> Historial <span class="badge"><?php echo count($historico); ?></span></a>
            </li>
            <li role="presentation">
                <a href="#create-panel" id="create-tab" role="tab" data-toggle="tab" onclick="cargarFormularioCreacion()"><i class="bi bi-file-earmark-plus"></i> Crear Solicitud</a>
            </li>
        </ul>

        <div class="tab-content exa-ui-tab-content panels-area" id="inboxTabsContent">
            <!-- 1. MIS PENDIENTES -->
            <div class="tab-pane active" id="pending-panel" role="tabpanel">
                <div class="exa-adq-table-wrap">
                    <table class="table table-bordered exa-adq-table">
                        <thead>
                            <tr>
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

            <!-- 2. MIS SOLICITUDES -->
            <div class="tab-pane" id="my-panel" role="tabpanel">
                <div class="exa-adq-table-wrap">
                    <table class="table table-bordered exa-adq-table">
                        <thead>
                            <tr>
                                <th style="width: 100px;">Nº Sol.</th>
                                <th style="width: 150px;">Fecha</th>
                                <th>Tipo Pedido</th>
                                <th style="width: 100px;">Prioridad</th>
                                <th style="width: 150px;">Valor Est.</th>
                                <th>Estado Solicitud</th>
                                <th>Etapa Workflow</th>
                                <th style="width: 180px;">Acción</th>
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
                                    $etapa = ($ms['Sol_Est'] == 'P') ? 'Sin enviar' : ($ms['Nod_Nom'] ?: '[Inactivo]');
                                    ?>
                                    <tr class="text-center">
                                        <td class="fw-bold"><?php echo $ms['Sol_Num']; ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($ms['Sol_Fec'])); ?></td>
                                        <td class="text-start"><?php echo $ms['Trq_Des']; ?></td>
                                        <td><span class="badge badge-<?php echo strtolower($ms['Sol_Pri']); ?>"><?php echo $ms['Sol_Pri']; ?></span></td>
                                        <td class="text-end fw-bold font-monospace">$ <?php echo number_format($ms['Sol_Val_Est'], 2); ?></td>
                                        <td><span class="badge bg-<?php echo $badge; ?>"><?php echo $est; ?></span></td>
                                        <td><span class="text-dark fw-bold"><?php echo htmlspecialchars($etapa); ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-dark" onclick="abrirResolucion(<?php echo $ms['Sol_Cod']; ?>, false)"><i class="bi bi-eye"></i> Detalle</button>
                                            <?php if ($ms['Sol_Est'] == 'P') { ?>
                                                <button class="btn btn-sm btn-warning text-dark" onclick="abrirEdicionBorrador(<?php echo $ms['Sol_Cod']; ?>)"><i class="bi bi-pencil"></i> Completar</button>
                                                <button class="btn btn-sm btn-success" onclick="enviarBorrador(<?php echo $ms['Sol_Cod']; ?>, <?php echo $ms['Sol_Num']; ?>)"><i class="bi bi-send-check"></i> Enviar</button>
                                            <?php } ?>
                                        </td>
                                    </tr>
                            <?php } 
                            } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. GESTIONÉ / PARTICIPÉ -->
            <div class="tab-pane" id="managed-panel" role="tabpanel">
                <p class="text-muted small mb-2" style="padding: 0 4px;">Solicitudes de otros usuarios en las que usted ya registro una decision en el workflow. Siguen visibles aunque ya no esten en sus pendientes.</p>
                <div class="exa-adq-table-wrap">
                    <table class="table table-bordered exa-adq-table">
                        <thead>
                            <tr>
                                <th style="width: 100px;">Nº Sol.</th>
                                <th style="width: 130px;">Mi gestion</th>
                                <th style="width: 130px;">Fecha gestion</th>
                                <th>Solicitante</th>
                                <th>Tipo Pedido</th>
                                <th style="width: 130px;">Valor Est.</th>
                                <th>Estado actual</th>
                                <th>Etapa actual</th>
                                <th style="width: 100px;">Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($gestionadas)) { ?>
                                <tr class="text-center"><td colspan="9" class="text-muted py-4">Aun no ha gestionado solicitudes de otros usuarios.</td></tr>
                            <?php } else {
                                foreach ($gestionadas as $g) {
                                    list($est, $badge) = adqEtiquetaEstadoSolicitud($g['Sol_Est']);
                                    list($mi_acc, $mi_badge) = adqEtiquetaMiAccion($g['Mi_Accion']);
                                    $etapa_actual = $g['Etapa_Actual'] ? $g['Etapa_Actual'] : '[Sin etapa]';
                                    if ($g['Sol_Est'] == 'A' || $g['Sol_Est'] == 'R') {
                                        $etapa_actual = 'Cerrado';
                                    }
                                    ?>
                                    <tr class="text-center">
                                        <td class="fw-bold"><?php echo $g['Sol_Num']; ?></td>
                                        <td><span class="badge bg-<?php echo $mi_badge; ?>"><?php echo $mi_acc; ?></span><div class="text-muted" style="font-size:10px;"><?php echo htmlspecialchars($g['Mi_Etapa']); ?></div></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($g['Mi_Fecha'])); ?></td>
                                        <td class="text-start"><?php echo htmlspecialchars($g['Solicitante_Nom']); ?></td>
                                        <td class="text-start"><?php echo htmlspecialchars($g['Trq_Des']); ?></td>
                                        <td class="text-end fw-bold font-monospace">$ <?php echo number_format($g['Sol_Val_Est'], 2); ?></td>
                                        <td><span class="badge bg-<?php echo $badge; ?>"><?php echo $est; ?></span></td>
                                        <td><span class="text-dark fw-bold"><?php echo htmlspecialchars($etapa_actual); ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-dark" onclick="abrirResolucion(<?php echo $g['Sol_Cod']; ?>, false)"><i class="bi bi-eye"></i> Detalle</button>
                                        </td>
                                    </tr>
                            <?php }
                            } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. HISTORIAL (cerrados) -->
            <div class="tab-pane" id="history-panel" role="tabpanel">
                <p class="text-muted small mb-2" style="padding: 0 4px;"><?php if ($es_gerencial_admin) { ?>Solicitudes finalizadas (aprobadas o rechazadas) de toda la empresa.<?php } else { ?>Solicitudes finalizadas que usted creo o en las que participo en el workflow.<?php } ?></p>
                <div class="exa-adq-table-wrap">
                    <table class="table table-bordered exa-adq-table">
                        <thead>
                            <tr>
                                <th style="width: 100px;">Nº Sol.</th>
                                <th style="width: 150px;">Fecha</th>
                                <th>Solicitante</th>
                                <th>Departamento</th>
                                <th>Tipo Pedido</th>
                                <th style="width: 100px;">Prioridad</th>
                                <th style="width: 150px;">Valor Est.</th>
                                <th>Estado</th>
                                <th style="width: 100px;">Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($historico)) { ?>
                                <tr class="text-center"><td colspan="9" class="text-muted py-4"><?php echo $es_gerencial_admin ? 'No hay solicitudes cerradas registradas.' : 'No tiene solicitudes cerradas propias ni en las que haya participado.'; ?></td></tr>
                            <?php } else {
                            foreach ($historico as $h) {
                                list($est, $badge) = adqEtiquetaEstadoSolicitud($h['Sol_Est']);
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
                            <?php }
                            } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 5. CREAR SOLICITUD -->
            <div class="tab-pane" id="create-panel" role="tabpanel">
                <div id="create-panel-content">
                    <div class="text-center p-5 text-muted">
                        <i class="glyphicon glyphicon-refresh glyphicon-spin" style="font-size:24px;"></i>
                        <div>Cargando formulario de registro...</div>
                    </div>
                </div>
            </div>
        </div>
            </div>
        </div>
    </div>

    <!-- RESOLUTION MODAL -->
    <div class="modal fade" id="mdlResolution" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="lblModalTitle">Detalle de Solicitud</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- COLUMNA IZQUIERDA: Datos, Ítems, Cotizaciones, Acciones -->
                        <div class="col-md-7 col-sm-12">
                            <!-- Datos Generales -->
                            <div class="adq-detail-card" style="padding: 8px 12px; margin-bottom: 10px;">
                                <h5 class="adq-section-header" style="color: #1e3a8a; border-bottom-color: #cbd5e1; margin-bottom: 6px; padding-bottom: 4px;"><i class="bi bi-file-earmark-text"></i> Datos del Requerimiento</h5>
                                <table class="table table-condensed table-borderless small mb-0" style="font-size: 11px; margin-bottom: 0;">
                                    <tr>
                                        <td class="fw-bold text-muted" style="width: 80px; padding: 2px 4px; border:none;">Solicitante:</td>
                                        <td id="detSolicitante" class="fw-bold text-dark" style="padding: 2px 4px; border:none;"></td>
                                        <td class="fw-bold text-muted" style="width: 80px; padding: 2px 4px; border:none;">Depto:</td>
                                        <td id="detDepartamento" style="padding: 2px 4px; border:none;"></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted" style="padding: 2px 4px; border:none;">Tipo Pedido:</td>
                                        <td id="detTipo" class="fw-semibold text-primary" style="padding: 2px 4px; border:none;"></td>
                                        <td class="fw-bold text-muted" style="padding: 2px 4px; border:none;">Valor Est:</td>
                                        <td id="detTotal" class="fw-bold font-monospace text-success" style="padding: 2px 4px; border:none;"></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted" style="padding: 2px 4px; border:none; vertical-align: top;">Justificación:</td>
                                        <td colspan="3" id="detJustificacion" class="text-muted" style="font-size: 10.5px; padding: 2px 4px; border:none; line-height: 1.2; font-style: italic;"></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-muted" style="padding: 2px 4px; border:none; vertical-align: top;">Requisitos:</td>
                                        <td colspan="3" id="detRequisitos" class="text-muted" style="font-size: 10px; padding: 2px 4px; border:none; line-height: 1.3;"></td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Ítems Solicitados -->
                            <div class="adq-detail-card">
                                <h5 class="adq-section-header" style="margin-bottom: 6px; padding-bottom: 4px;"><i class="bi bi-cart3"></i> Ítems Requeridos</h5>
                                <div class="table-responsive" style="border: none; margin-bottom: 0; max-height: 180px; overflow-y: auto;">
                                    <table class="table table-striped table-hover align-middle mb-0" style="font-size: 11px; border: 1px solid #e2e8f0; border-radius: 4px; overflow: hidden;" id="tblDetItems">
                                        <thead style="background-color: #f8fafc; border-bottom: 2px solid #cbd5e1;">
                                            <tr>
                                                <th class="text-center" style="width: 30px; padding: 4px; color: #475569; font-weight: 700;">#</th>
                                                <th style="padding: 4px; color: #475569; font-weight: 700;">Descripción / Artículo</th>
                                                <th class="text-center" style="width: 50px; padding: 4px; color: #475569; font-weight: 700;">IVA</th>
                                                <th class="text-center" style="width: 60px; padding: 4px; color: #475569; font-weight: 700;">Cant.</th>
                                                <th class="text-end" style="width: 90px; padding: 4px; color: #475569; font-weight: 700;">V. Unit.</th>
                                                <th class="text-end" style="width: 90px; padding: 4px; color: #475569; font-weight: 700;">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Cotizaciones de Sustento -->
                            <div class="adq-detail-card" id="divDetCotizaciones">
                                <h5 class="adq-section-header" style="margin-bottom: 6px; padding-bottom: 4px;"><i class="bi bi-file-earmark-pdf"></i> Cotizaciones de Sustento</h5>
                                <div class="row" id="detCotizacionesList" style="margin-left: -6px; margin-right: -10px; max-height: 160px; overflow-y: auto;"></div>
                            </div>

                            <!-- Compras Vinculadas -->
                            <div class="adq-detail-card" id="divComprasVinculadas" style="display: none;">
                                <h5 class="adq-section-header" style="margin-bottom: 6px; padding-bottom: 4px;"><i class="bi bi-link-45deg"></i> Facturas de Compra Vinculadas</h5>
                                <div id="lstComprasVinculadas" style="max-height: 120px; overflow-y: auto;"></div>
                            </div>

                            <!-- Panel de Vinculación de Compra (solo en nodo FACTURA) -->
                            <div class="adq-detail-card" id="panelVincularCompra" style="display: none; border-color: #0ea5e9; background-color: #f0f9ff; padding: 8px 12px;">
                                <h5 class="adq-section-header" style="color: #0369a1; border-bottom-color: #bae6fd; margin-bottom: 6px; padding-bottom: 4px;"><i class="bi bi-receipt"></i> Vincular Factura de Compra EXA</h5>
                                <div style="margin-bottom: 8px;">
                                    <input type="text" id="txtBuscarCompra" class="form-control input-sm" placeholder="Buscar factura por Nº o Proveedor..." oninput="buscarComprasVincular()" style="height: 26px; font-size: 11px; padding: 3px 8px;">
                                </div>
                                <div class="table-responsive" id="divResultCompras" style="display: none; border: 1px solid #bae6fd; border-radius: 4px; background-color: #ffffff; max-height: 120px; overflow-y: auto;">
                                    <table class="table table-condensed table-hover mb-0" style="font-size: 11px;">
                                        <thead style="background-color: #f0f9ff;">
                                            <tr>
                                                <th class="text-center">Nº Factura</th>
                                                <th class="text-center">Fecha</th>
                                                <th>Proveedor</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-center" style="width: 60px;">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tblBuscarCompras"></tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Panel de Decisión del Aprobador -->
                            <div class="adq-detail-card" id="panelDecision" style="display: none; border-color: #3b82f6; background-color: #f0f7ff; padding: 8px 12px;">
                                <h5 class="adq-section-header" style="color: #1d4ed8; border-bottom-color: #bfdbfe; margin-bottom: 6px; padding-bottom: 4px;"><i class="bi bi-check2-all"></i> Decisión en esta Etapa (<span id="lblNodeActionName"></span>)</h5>
                                <form id="frmWorkflowAction" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="Ins_Cod" id="actionInsCod">
                                    <input type="hidden" name="Action" id="actionName">
                                    <div style="margin-bottom: 8px;">
                                        <textarea class="form-control" name="Comentario" id="actionComentario" rows="2" placeholder="Redacte el motivo de su decisión..." style="border-radius: 4px; border-color: #bfdbfe; font-size: 11px; padding: 4px 8px;"></textarea>
                                    </div>
                                    <div style="margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                                        <label class="form-label fw-semibold" style="font-size: 11px; color: #1d4ed8; margin: 0; white-space: nowrap;">Sustento Adjunto <span id="lblAdjReq" class="text-danger" style="display: none;">*</span></label>
                                        <input type="file" class="form-control input-sm" name="adjunto" id="actionAdjunto" style="border-radius: 4px; border-color: #bfdbfe; background-color: #ffffff; height: 26px; font-size: 11px; padding: 2px 6px; width: auto; max-width: 200px;">
                                    </div>
                                    <div class="adq-action-buttons">
                                        <button type="button" class="btn btn-success" onclick="enviarAccion('APROBAR')"><i class="bi bi-check-circle"></i> Aprobar</button>
                                        <button type="button" class="btn btn-warning text-dark" style="background-color: #f59e0b; border-color: #f59e0b; color: #ffffff;" onclick="enviarAccion('OBSERVAR')"><i class="bi bi-exclamation-triangle"></i> Observar</button>
                                        <button type="button" class="btn btn-default" style="background-color: #64748b; border-color: #64748b; color: #ffffff;" onclick="enviarAccion('DEVOLVER')"><i class="bi bi-reply"></i> Devolver</button>
                                        <button type="button" class="btn btn-danger" onclick="enviarAccion('RECHAZAR')"><i class="bi bi-x-circle"></i> Rechazar</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- COLUMNA DERECHA: Progreso y Historial -->
                        <div class="col-md-5 col-sm-12">
                            <!-- Tracker Visual de Progreso por Colores -->
                            <div class="adq-detail-card" style="background-color: #f8fafc; border-color: #e2e8f0;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 4px;">
                                    <label class="form-label fw-bold text-muted m-0" style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em;"><i class="bi bi-activity"></i> Progreso del Workflow</label>
                                    <button class="btn btn-xs btn-primary" style="background-color: #1e3a8a; border-color: #1e3a8a; font-size: 9.5px; padding: 1px 6px;" onclick="abrirSeguimientoDetallado()"><i class="bi bi-clock-history"></i> Ver Línea de Tiempo</button>
                                </div>
                                <div class="tracker-wrapper" id="flowTracker" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 4px; padding: 6px; justify-content: flex-start; overflow-x: auto; white-space: nowrap;">
                                    <!-- Los pasos del workflow dinámicos se inyectan aquí -->
                                </div>
                            </div>

                            <!-- Historial del Workflow -->
                            <div class="adq-detail-card">
                                <h5 class="adq-section-header" style="margin-bottom: 6px; padding-bottom: 4px;"><i class="bi bi-list-stars"></i> Historial de Firmas</h5>
                                <div style="max-height: 380px; overflow-y: auto; padding-right: 4px;">
                                    <div class="adq-timeline" id="lstHistorial"></div>
                                </div>
                            </div>
                        </div>
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
                    <button type="button" class="btn btn-default btn-sm" onclick="volverAResolucion()"><i class="bi bi-arrow-left"></i> Volver al Detalle</button>
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cerrar</button>
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

        function enviarBorrador(solCod, solNum) {
            if (!confirm('¿Desea enviar la solicitud #' + solNum + ' a aprobacion?')) {
                return;
            }
            $.post('adq_bandeja.php', { ajax_enviar_borrador: 1, Sol_Cod: solCod }, function(res) {
                if (res.success) {
                    alert('La solicitud #' + res.Num + ' fue enviada a aprobacion correctamente.');
                    window.location.reload();
                } else {
                    let msg = res.message || 'Error desconocido';
                    if (res.requiere_completar) {
                        msg += '\n\nUse el boton Completar para agregar la informacion faltante.';
                    }
                    alert('No se puede enviar: ' + msg);
                }
            }, 'json').fail(function() {
                alert('Error de red al enviar el borrador.');
            });
        }

        let formLoaded = false;
        let formSolCod = null;

        function cargarFormularioCreacion(solCod, callback) {
            const targetSol = solCod || null;
            if (formLoaded && formSolCod === targetSol) {
                if (callback) callback();
                return;
            }
            $('#create-panel-content').data('sol-cod', targetSol || '');
            $.get('adq_solicitud.php', { ajax_get_form: 1 }, function(html) {
                $('#create-panel-content').html(html);
                formLoaded = true;
                formSolCod = targetSol;
                if (targetSol && typeof cargarBorradorEnFormulario === 'function') {
                    cargarBorradorEnFormulario(targetSol);
                }
                if (callback) callback();
            }).fail(function(xhr, status, error) {
                alert('Error al cargar el formulario de creacion: ' + error + ' (Status: ' + xhr.status + ')');
            });
        }

        function abrirEdicionBorrador(solCod) {
            formLoaded = false;
            formSolCod = null;
            $('a[href="#create-panel"]').tab('show');
            cargarFormularioCreacion(solCod);
        }

        function activarTabCrear() {
            $('a[href="#create-panel"]').tab('show');
            const urlParams = new URLSearchParams(window.location.search);
            const solCod = urlParams.get('sol_cod');
            cargarFormularioCreacion(solCod ? parseInt(solCod, 10) : null);
        }

        function abrirResolucion(solCod, renderPanelAction) {
            currentSolCod = solCod;
            $.getJSON('adq_bandeja.php', { ajax_get_solicitud_detail: true, sol_cod: solCod }, function(res) {
                if (res.success) {
                    const sol = res.solicitud;
                    currentInsCod = sol.Ins_Cod;

                    // Cabecera
                    $('#lblModalTitle').text(`Solicitud de Adquisición Nº ${sol.Sol_Num}`);
                    const solicitante = (sol.Sol_Nom || sol.Sol_Ape) ? `${sol.Sol_Nom} ${sol.Sol_Ape}`.trim() : (sol.Usu_Nom || 'N/D');
                    $('#detSolicitante').text(solicitante);
                    $('#detDepartamento').text(sol.Dep_Des);
                    $('#detTipo').text(sol.Trq_Des);
                    $('#detTotal').text(`$ ${parseFloat(sol.Sol_Val_Est).toFixed(2)}`);
                    $('#detJustificacion').text(sol.Sol_Jus);

                    const reqParts = [];
                    if (parseInt(sol.Sol_Req_Cot, 10) === 1) {
                        reqParts.push(`Cotizaciones: min. ${sol.Sol_Min_Cot || 1}`);
                    } else {
                        reqParts.push('Cotizaciones: no obligatorias');
                    }
                    if (parseInt(sol.Sol_Req_Fac, 10) === 1) reqParts.push('Factura al cierre');
                    if (parseInt(sol.Sol_Req_Pro, 10) === 1) reqParts.push('Proveedor sugerido');
                    if (parseInt(sol.Sol_Req_Adj, 10) === 1) reqParts.push('Adjuntos obligatorios');
                    if (parseInt(sol.Sol_Req_Pre, 10) === 1) reqParts.push('Verificar presupuesto');
                    if (sol.Sol_Tiempo_Est) reqParts.push(`SLA: ${sol.Sol_Tiempo_Est} dias`);
                    $('#detRequisitos').text(reqParts.join(' · '));

                    // Ítems
                    const $tbody = $('#tblDetItems tbody').empty();
                    if (!res.items || res.items.length === 0) {
                        $tbody.append('<tr class="text-center"><td colspan="6" class="text-muted py-2">No hay articulos registrados en esta solicitud.</td></tr>');
                    } else {
                    res.items.forEach(function(item, idx) {
                        const descripcion = item.Sde_Des || item.Pro_Nom || 'Sin descripcion';
                        const ivaBadge = parseInt(item.Sde_Iva) === 1 ? '<span class="badge bg-success" style="background-color: #10b981 !important; color: #ffffff !important; font-size: 9px; padding: 2px 4px;">SI</span>' : '<span class="badge bg-secondary" style="background-color: #6b7280 !important; color: #ffffff !important; font-size: 9px; padding: 2px 4px;">NO</span>';
                        $tbody.append(`
                            <tr class="text-center">
                                <td>${idx + 1}</td>
                                <td class="text-start">${descripcion}</td>
                                <td>${ivaBadge}</td>
                                <td>${parseFloat(item.Sde_Can).toFixed(4)}</td>
                                <td class="text-end">$ ${parseFloat(item.Sde_Pru).toFixed(2)}</td>
                                <td class="text-end fw-bold">$ ${(parseFloat(item.Sde_Can) * parseFloat(item.Sde_Pru)).toFixed(2)}</td>
                            </tr>
                        `);
                    });
                    }

                    // Cotizaciones
                    const $cotList = $('#detCotizacionesList').empty();
                    if (res.cotizaciones.length > 0) {
                        $('#divDetCotizaciones').show();
                        res.cotizaciones.forEach(function(c) {
                            const ganadorClass = c.Cot_Sel == 1 ? 'ganadora' : '';
                            const badgeGanador = c.Cot_Sel == 1 ? '<span class="badge bg-success pull-right" style="background-color: #10b981 !important; color: #ffffff !important; margin-top: 2px;"><i class="bi bi-trophy"></i> Seleccionada</span>' : '';
                            $cotList.append(`
                                <div class="col-sm-6" style="padding-left: 10px; padding-right: 10px;">
                                    <div class="adq-cot-card ${ganadorClass}">
                                        <div style="margin-bottom: 6px; overflow: hidden;">
                                            <span class="fw-bold text-dark" style="font-size: 13px;">${c.Prv_Com || (c.Prs_Nom + ' ' + c.Prs_Ape)}</span>
                                            ${badgeGanador}
                                        </div>
                                        <div style="color: #64748b; margin-bottom: 8px; font-size: 12px;">
                                            Valor: <strong class="text-success" style="font-size: 14px;">$ ${parseFloat(c.Cot_Val).toFixed(2)}</strong>
                                        </div>
                                        <a href="../../DATA/${c.Cot_Adj}" target="_blank" class="btn btn-xs btn-primary" style="background-color: #1e3a8a; border-color: #1e3a8a; color: #ffffff;"><i class="bi bi-file-earmark-pdf"></i> Ver Sustento PDF</a>
                                        ${c.Cot_Sel == 1 && c.Cot_Jus ? `<div class="mt-2 text-muted" style="font-size: 11px; margin-top: 8px; border-top: 1px dashed #cbd5e1; padding-top: 6px;"><strong>Justificación:</strong> ${c.Cot_Jus}</div>` : ''}
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
                        let itemClass = '';
                        
                        if (h.Isn_Acc === 'CREAR') {
                            actionBadge = '<span class="badge bg-secondary" style="background-color: #64748b !important; color: #ffffff !important;">Inició Pedido</span>';
                            itemClass = 'active';
                        } else if (h.Isn_Acc === 'APROBAR') {
                            actionBadge = '<span class="badge bg-success" style="background-color: #10b981 !important; color: #ffffff !important;">Aprobó</span>';
                            itemClass = 'success';
                        } else if (h.Isn_Acc === 'OBSERVAR') {
                            actionBadge = '<span class="badge bg-warning text-dark" style="background-color: #f59e0b !important; color: #1e293b !important;">Observó</span>';
                            itemClass = 'warning';
                        } else if (h.Isn_Acc === 'DEVOLVER') {
                            actionBadge = '<span class="badge bg-secondary" style="background-color: #4b5563 !important; color: #ffffff !important;">Devolvió</span>';
                            itemClass = 'active';
                        } else if (h.Isn_Acc === 'RECHAZAR') {
                            actionBadge = '<span class="badge bg-danger" style="background-color: #ef4444 !important; color: #ffffff !important;">Rechazó</span>';
                            itemClass = 'danger';
                        }

                        $hist.append(`
                            <div class="adq-timeline-item ${itemClass}">
                                <div class="adq-timeline-content">
                                    <div class="adq-timeline-header">
                                        <span class="adq-timeline-title">${actionBadge} en etapa: <strong>${h.Nod_Nom}</strong></span>
                                        <span class="adq-timeline-date"><i class="bi bi-clock"></i> ${h.Isn_Fec}</span>
                                    </div>
                                    <div class="adq-timeline-body">
                                        Por: <span class="text-primary fw-bold">${actor}</span>
                                        ${h.Isn_Com ? `<div class="adq-timeline-comment">"${h.Isn_Com}"</div>` : ''}
                                        ${h.Isn_Adj ? `<div style="margin-top: 6px;"><a href="../../DATA/${h.Isn_Adj}" target="_blank" class="btn btn-xs btn-default" style="font-size: 10px; padding: 1px 6px;"><i class="bi bi-paperclip"></i> Ver Adjunto</a></div>` : ''}
                                    </div>
                                </div>
                            </div>
                        `);
                    });

                    // Progreso Visual del Workflow por Colores (Fase 3.2 tracker)
                    const $tracker = $('#flowTracker').empty();
                    const flowNodos = (res.flow_visual && res.flow_visual.nodos) ? res.flow_visual.nodos : [];
                    flowNodos.forEach(function(node, index) {
                        if (index > 0) {
                            $tracker.append('<div class="tracker-arrow"><i class="bi bi-arrow-right-short"></i></div>');
                        }
                        const actorLine = (function(node) {
                            if (node.pendiente_meta) {
                                const pm = node.pendiente_meta;
                                let lines = ['<span><i class="bi bi-hourglass-split"></i> Pendiente de aprobacion</span>'];
                                if (pm.depto) lines.push(`<span>Depto: ${pm.depto}</span>`);
                                if (pm.asignados) lines.push(`<span>Asignado: ${pm.asignados}</span>`);
                                if (pm.enviado_por) lines.push(`<span>Enviado por: ${pm.enviado_por}</span>`);
                                return `<br><span class="tracker-actor tracker-pendiente">${lines.join('')}</span>`;
                            }
                            if (node.actor_label) {
                                return `<br><span class="tracker-actor"><i class="bi bi-person-check"></i> ${node.actor_label}</span>`;
                            }
                            return '';
                        })(node);
                        $tracker.append(`
                            <div class="tracker-node color-${node.color}">
                                <i class="bi bi-circle-fill"></i> ${node.nombre}<br>
                                <span style="font-size: 9px; font-weight: normal; opacity: 0.8;">[${node.tipo}]</span>
                                ${actorLine}
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
                                        <strong><i class="bi bi-receipt-cutoff"></i> Factura # ${cv.Cop_Num}</strong> - ${cv.Proveedor} 
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
                    $('#mdlResolution').modal('show');
                } else {
                    alert('No se pudo cargar el detalle: ' + (res.message || 'Error desconocido'));
                }
            }).fail(function() {
                alert('Error de red al cargar el detalle de la solicitud.');
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
                $.getJSON('adq_bandeja.php', { ajax_buscar_compras: true, search: term }, function(res) {
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
            $.post('adq_bandeja.php?ajax_vincular_compra=1', { Sol_Cod: currentSolCod, Cop_Cod: copCod }, function(res) {
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
            $.post('adq_bandeja.php?ajax_desvincular_compra=1', { Scm_Cod: scmCod }, function(res) {
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
                url: 'adq_bandeja.php?ajax_workflow_action=1',
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

        function abrirSeguimientoDetallado() {
            if (!currentSolCod) return;
            $('#lblSeguimientoTitle').text('Seguimiento de Requerimiento #' + currentSolCod);
            $('#seguimientoModalBody').html('<div class="text-center p-4"><i class="glyphicon glyphicon-refresh glyphicon-spin" style="font-size:24px;"></i><div class="mt-2">Cargando seguimiento...</div></div>');
            
            // Ocultar modal de resolución para no encimar
            $('#mdlResolution').modal('hide');
            $('#mdlSeguimiento').modal('show');
            
            $.ajax({
                url: 'adq_seguimiento.php',
                data: { sol_cod: currentSolCod },
                dataType: 'html',
                cache: false,
                success: function(html) {
                    $('#seguimientoModalBody').html(html);
                },
                error: function() {
                    $('#seguimientoModalBody').html('<div class="alert alert-danger m-2">No se pudo cargar el seguimiento.</div>');
                }
            });
        }

        function volverAResolucion() {
            $('#mdlSeguimiento').modal('hide');
            if ($('#mdlResolution').data('bs.modal')) {
                $('#mdlResolution').modal('show');
            } else {
                abrirResolucion(currentSolCod, true);
            }
        }

        $(document).ready(function() {
            // Verificar si se solicitó activar una pestaña específica por URL
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            if (tab === 'crear_solicitud') {
                activarTabCrear();
            }
        });
    </script>
</body>
</html>
