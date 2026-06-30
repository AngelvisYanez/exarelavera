<?php
/**
 * EXA Workflow Builder - Diseñador Visual de Flujos
 * @author Oz <oz-agent@warp.dev>
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/wf_manager_log.php');

$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new MysqlDatos($obBD_conexion);
$wf_mgr = new wf_manager_log($Ses_Dat_Dis);

// Verificar acceso a la ventana 'configuracion' y pestaña 'disenador_flujos'
if (!$wf_mgr->verificarAccesoVentana('configuracion', 'disenador_flujos')) {
    if (isset($ajax_save_workflow) || isset($ajax_publish_workflow) || isset($ajax_load_workflow) || isset($ajax_get_department_users) || isset($ajax_save_department_users) || isset($ajax_get_users_by_department)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Acceso denegado. No tiene permisos para realizar esta acción.'));
        exit;
    } else {
        echo "<div class='alert alert-danger m-3'>Acceso denegado. No tiene permisos para ver esta ventana.</div>";
        exit;
    }
}

// Redirección segura para navegación directa del navegador (no AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['ajax_get_builder']) && !isset($_GET['ajax_load_workflow']) && !isset($_GET['ajax_get_department_users']) && !isset($_GET['ajax_get_users_by_department'])) {
    header("Location: adq_configuracion.php?tab=disenador");
    exit;
}

function wf_builder_escape($obBD_conexion, $value) {
    if ($value === null) {
        return '';
    }
    return mysqli_real_escape_string($obBD_conexion->conexion, (string)$value);
}

function wf_builder_resolve_node_id($frontend_id) {
    if ($frontend_id === null || $frontend_id === '') {
        return 0;
    }
    $id = (string)$frontend_id;
    if (strpos($id, 'n_') === 0) {
        return intval(substr($id, 2));
    }
    if (strpos($id, 'node_') === 0) {
        return 0;
    }
    if (ctype_digit($id)) {
        return intval($id);
    }
    return 0;
}

// Manejo de peticiones AJAX
if (isset($ajax_save_workflow)) {
    $data = json_decode(file_get_contents('php://input'), true);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $result = $wf_mgr->guardarFlujoDisenador($data, $Ses_Emp_Cod);
        $obBD_con1->commit_nomsn($obBD_conexion);
        $obBD_con1->echoJson(array_merge(array('success' => true), $result));
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
}

if (isset($ajax_publish_workflow)) {
    $data = json_decode(file_get_contents('php://input'), true);
    $wfm_cod = !empty($data['id']) ? intval($data['id']) : 0;
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $result = $wf_mgr->publicarFlujoDisenador($wfm_cod, $Ses_Emp_Cod);
        $obBD_con1->commit_nomsn($obBD_conexion);
        $obBD_con1->echoJson(array_merge(array('success' => true, 'message' => 'Flujo publicado correctamente.'), $result));
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
}

if (isset($ajax_load_workflow)) {
    $wfm_cod = intval($_GET['id']);
    try {
        $pack = $wf_mgr->cargarFlujoDisenador($wfm_cod, $Ses_Emp_Cod);
        $flujo = $pack['flujo'];
        $payload_nodos = array();
        foreach ($pack['nodos'] as $nodo) {
            $payload_nodos[] = array(
                'id' => $nodo['Nod_Cod'],
                'tipo' => $nodo['Nod_Tip'],
                'nombre' => $nodo['Nod_Nom'],
                'descripcion' => $nodo['Nod_Des'],
                'dep_cod' => $nodo['Dep_Cod'],
                'per_cod' => $nodo['Per_Cod'],
                'sla' => $nodo['Nod_Sla'],
                'com_obl' => $nodo['Nod_Com_Obl'],
                'adj_obl' => $nodo['Nod_Adj_Obl'],
                'x' => $nodo['Nod_Vis_X'],
                'y' => $nodo['Nod_Vis_Y'],
                'usu_asig' => !empty($nodo['Nod_Usu_Asig']) ? $nodo['Nod_Usu_Asig'] : 'TODOS'
            );
        }

        $payload_conexiones = array();
        foreach ($pack['conexiones'] as $con) {
            $condicion = !empty($con['Con_Con_Exp']) ? json_decode($con['Con_Con_Exp'], true) : null;
            $payload_conexiones[] = array(
                'origen' => $con['Nod_Ori'],
                'destino' => $con['Nod_Des'],
                'accion' => $con['Con_Acc'],
                'condicion' => $condicion
            );
        }

        $estado = $flujo['Wfm_Est'];
        $obBD_con1->echoJson(array(
            'success' => true,
            'flujo' => array(
                'id' => $flujo['Wfm_Cod'],
                'familia_cod' => $pack['familia_cod'],
                'nombre' => $flujo['Wfm_Nom'],
                'descripcion' => $flujo['Wfm_Des'],
                'version' => intval($flujo['Wfm_Version']),
                'estado' => $estado,
                'es_borrador' => ($estado === 'B'),
                'instancias_activas' => $pack['instancias_activas'],
                'version_publicada' => !empty($pack['publicado']) ? intval($pack['publicado']['Wfm_Version']) : null
            ),
            'nodos' => $payload_nodos,
            'conexiones' => $payload_conexiones
        ));
    } catch (Exception $e) {
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
}

// --- AJAX: Obtener usuarios de la empresa con flag de asignación al departamento ---
if (isset($ajax_get_department_users)) {
    $dep_cod = intval($_GET['dep_cod']);
    try {
        $usuarios = $obBD_con1->getArrayConsultaSql("
            SELECT base.Usu_Cod,
                   base.Usuario_Nom,
                   IF(EXISTS (
                       SELECT 1
                       FROM usuarios ux
                       INNER JOIN sucursal sx ON sx.Suc_Cod = ux.Suc_Cod
                       INNER JOIN wf_departamento_usuarios du2 ON du2.Usu_Cod = ux.Usu_Cod AND du2.Dep_Cod = $dep_cod
                       WHERE sx.Emp_Cod = $Ses_Emp_Cod AND ux.Usu_Ced = base.Usu_Ced AND ux.Usu_Est = 'A' AND ux.Usu_Wf = 'S'
                   ), 1, 0) AS asignado
            FROM (
                SELECT MIN(u.Usu_Cod) AS Usu_Cod,
                       TRIM(CONCAT(p.Prs_Nom, ' ', p.Prs_Ape)) AS Usuario_Nom,
                       u.Usu_Ced
                FROM usuarios u
                INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
                INNER JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod
                WHERE s.Emp_Cod = $Ses_Emp_Cod AND u.Usu_Est = 'A' AND u.Usu_Wf = 'S'
                GROUP BY u.Usu_Ced, p.Prs_Nom, p.Prs_Ape
            ) base
            ORDER BY asignado DESC, Usuario_Nom;", $obBD_conexion);
        $obBD_con1->echoJson(array('success' => true, 'usuarios' => $usuarios));
    } catch (Exception $e) {
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
    exit;
}

// --- AJAX: Guardar asignaciones de usuarios a un departamento ---
if (isset($ajax_save_department_users)) {
    $dep_cod = intval($_POST['dep_cod']);
    $usuarios_ids = isset($_POST['usuarios']) ? $_POST['usuarios'] : array();
    
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $obBD_con1->grabarv_registros("DELETE FROM wf_departamento_usuarios WHERE Dep_Cod = $dep_cod;", $obBD_conexion);
        foreach ($usuarios_ids as $u_id) {
            $u_id = intval($u_id);
            $cuentas = $obBD_con1->getArrayConsultaSql("
                SELECT ux.Usu_Cod
                FROM usuarios ux
                INNER JOIN sucursal sx ON sx.Suc_Cod = ux.Suc_Cod
                WHERE sx.Emp_Cod = $Ses_Emp_Cod AND ux.Usu_Est = 'A' AND ux.Usu_Wf = 'S'
                  AND ux.Usu_Ced = (SELECT u0.Usu_Ced FROM usuarios u0 WHERE u0.Usu_Cod = $u_id LIMIT 1);", $obBD_conexion);
            if ($cuentas === false || $cuentas === null) {
                $cuentas = array();
            }
            foreach ($cuentas as $cuenta) {
                $obBD_con1->grabarv_registros("INSERT INTO wf_departamento_usuarios (Dep_Cod, Usu_Cod) VALUES ($dep_cod, {$cuenta['Usu_Cod']});", $obBD_conexion);
            }
        }
        $obBD_con1->commit_nomsn($obBD_conexion);
        $obBD_con1->echoJson(array('success' => true));
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
    exit;
}

// --- AJAX: Obtener usuarios asignados a un departamento (uno por persona/cédula) ---
if (isset($ajax_get_users_by_department)) {
    $dep_cod = intval($_GET['dep_cod']);
    try {
        $usuarios = $obBD_con1->getArrayConsultaSql("
            SELECT MIN(u.Usu_Cod) AS Usu_Cod,
                   TRIM(CONCAT(p.Prs_Nom, ' ', p.Prs_Ape)) AS Usuario_Nom,
                   GROUP_CONCAT(u.Usu_Cod ORDER BY u.Usu_Cod) AS Usu_Cods
            FROM wf_departamento_usuarios du
            INNER JOIN usuarios u ON u.Usu_Cod = du.Usu_Cod
            INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
            INNER JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod
            WHERE du.Dep_Cod = $dep_cod AND s.Emp_Cod = $Ses_Emp_Cod
              AND u.Usu_Est = 'A' AND u.Usu_Wf = 'S'
            GROUP BY u.Usu_Ced, p.Prs_Nom, p.Prs_Ape
            ORDER BY Usuario_Nom;", $obBD_conexion);
        if ($usuarios === false || $usuarios === null) {
            $usuarios = array();
        }
        $obBD_con1->echoJson(array('success' => true, 'usuarios' => $usuarios));
    } catch (Exception $e) {
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
    exit;
}

// Cargar catálogos para configuración de nodos
$wf_mgr->syncDepartamentosFromRrhh($Ses_Emp_Cod);
$departamentos = $obBD_con1->getArrayConsultaSql("
    SELECT d.Wde_Cod AS Dep_Cod, d.Wde_Des AS Dep_Des
    FROM wf_departamentos d
    WHERE d.Emp_Cod = $Ses_Emp_Cod AND d.Wde_Est = 'A'
      AND EXISTS (
          SELECT 1
          FROM wf_departamento_usuarios du
          INNER JOIN usuarios u ON u.Usu_Cod = du.Usu_Cod
          INNER JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod
          WHERE du.Dep_Cod = d.Wde_Cod AND s.Emp_Cod = $Ses_Emp_Cod
            AND u.Usu_Est = 'A' AND u.Usu_Wf = 'S'
      )
    ORDER BY d.Wde_Des;", $obBD_conexion);
$perfiles = $obBD_con1->getArrayConsultaSql("SELECT Per_Cod, Per_Des FROM perfiles WHERE Emp_Cod = $Ses_Emp_Cod AND Per_Est = 'A' ORDER BY Per_Des;", $obBD_conexion);
$wf_mgr->ensureVersioningSchema();
$flujos_existentes = $wf_mgr->listarFlujosPublicados($Ses_Emp_Cod);

if (isset($ajax_get_builder)) {
    ?>
    <style>
        .builder-container {
            display: flex;
            min-height: calc(100vh - 210px);
            height: calc(100vh - 210px);
            border: 1px solid #c5d0dc;
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
            margin-top: 15px;
        }
        .toolbox {
            width: 220px;
            background-color: #ffffff;
            border-right: 1px solid #dee2e6;
            padding: 10px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            overflow-y: auto;
        }
        .toolbox-item {
            padding: 8px 10px;
            background-color: #f1f3f5;
            border: 1px solid #ced4da;
            border-radius: 8px;
            cursor: grab;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            user-select: none;
            font-size: 12px;
        }
        .canvas-area {
            flex: 1 1 auto;
            position: relative;
            min-width: 0;
            min-height: 100%;
            overflow: auto;
            user-select: none;
            background-color: #e8edf2;
            background-image:
                linear-gradient(to right, rgba(93, 114, 137, 0.16) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(93, 114, 137, 0.16) 1px, transparent 1px),
                radial-gradient(circle, rgba(74, 136, 181, 0.45) 1.5px, transparent 1.5px);
            background-size: 24px 24px, 24px 24px, 24px 24px;
            background-position: -1px -1px, -1px -1px, 0 0;
        }
        .wf-node {
            position: absolute;
            width: 180px;
            background-color: #ffffff;
            border: 2px solid #6c757d;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            cursor: move;
            z-index: 10;
            font-size: 12px;
        }
        .wf-node-header {
            padding: 6px 10px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .wf-node-body {
            padding: 10px;
            text-align: center;
        }
        .node-port {
            position: absolute;
            width: 12px;
            height: 12px;
            background-color: #6c757d;
            border-radius: 50%;
            border: 2px solid #ffffff;
            cursor: pointer;
        }
        .node-port:hover {
            background-color: #0d6efd;
            transform: scale(1.2);
        }
        .node-port-in {
            top: 50%;
            left: -6px;
            margin-top: -6px;
        }
        .node-port-out {
            top: 50%;
            right: -6px;
            margin-top: -6px;
        }
        .svg-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }
        .svg-canvas path {
            pointer-events: auto;
            cursor: pointer;
        }
        .node-INICIO { border-color: #198754; }
        .node-INICIO .wf-node-header { background-color: #d1e7dd; color: #198754; }
        .node-FIN { border-color: #dc3545; }
        .node-FIN .wf-node-header { background-color: #f8d7da; color: #dc3545; }
        .node-APROBACION { border-color: #0d6efd; }
        .node-APROBACION .wf-node-header { background-color: #cfe2ff; color: #0d6efd; }
        .node-DECISION { border-color: #ffc107; }
        .node-DECISION .wf-node-header { background-color: #fff3cd; color: #ffc107; }
        
        .properties-drawer {
            position: fixed;
            top: 56px;
            right: -350px;
            width: 350px;
            height: calc(100vh - 56px);
            background-color: #ffffff;
            border-left: 1px solid #dee2e6;
            box-shadow: -4px 0 10px rgba(0,0,0,0.05);
            transition: right 0.3s ease;
            z-index: 100;
            padding: 20px;
            overflow-y: auto;
        }
        .properties-drawer.open { right: 0; }
        .wf-builder-header {
            margin-bottom: 1rem;
            padding: 0.5rem 0.75rem;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        .wf-builder-status-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
            margin-top: 0.65rem;
            padding-top: 0.65rem;
            border-top: 1px solid #e2e8f0;
        }
    </style>
    <div class="wf-builder-header">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <label class="fw-bold m-0 small text-dark"><i class="bi bi-funnel"></i> Seleccionar Flujo:</label>
            <select id="selWorkflow" class="form-control form-control-sm" style="width: 200px; display: inline-block;">
                <option value="">-- Seleccionar un Flujo --</option>
                <?php foreach ($flujos_existentes as $flow) { ?>
                    <option value="<?php echo intval($flow['Wfm_Fam_Cod']); ?>"><?php echo htmlspecialchars($flow['Wfm_Nom'], ENT_QUOTES, 'UTF-8'); ?> (v<?php echo intval($flow['Wfm_Version']); ?>)</option>
                <?php } ?>
            </select>
            <button class="btn btn-sm btn-info text-white fw-bold" onclick="cargarFlujo()"><i class="bi bi-folder-2-open"></i> Abrir</button>
            <button class="btn btn-sm btn-primary fw-bold" onclick="abrirModalNuevoFlujo()"><i class="bi bi-plus-lg"></i> Nuevo</button>
            <button class="btn btn-sm btn-success fw-bold" onclick="guardarFlujo()"><i class="bi bi-save"></i> Guardar borrador</button>
            <button class="btn btn-sm btn-warning fw-bold text-dark" onclick="publicarFlujo()"><i class="bi bi-cloud-upload"></i> Publicar</button>
        </div>
        <div class="wf-builder-status-bar" style="display: none;">
            <span class="badge bg-primary p-2" id="lblFlowActiveName" style="font-size: 12px; display: none;"><i class="bi bi-diagram-3-fill"></i> Flujo Activo: <span></span></span>
            <span class="badge bg-secondary p-2" id="lblFlowVersion" style="font-size: 11px; display: none;"></span>
            <span class="badge bg-warning text-dark p-2" id="lblFlowDraft" style="font-size: 11px; display: none;"><i class="bi bi-pencil-square"></i> Borrador</span>
            <span class="badge bg-info text-dark p-2" id="lblFlowActiveInstances" style="font-size: 11px; display: none;"></span>
            <!-- Inputs ocultos para mantener compatibilidad con el JS -->
            <input type="hidden" id="flowName">
            <input type="hidden" id="flowDesc">
        </div>
    </div>
    <div class="builder-container">
        <!-- Toolbox -->
        <div class="toolbox">
            <h6 class="fw-bold mb-3">Nodos de Flujo</h6>
            <div class="toolbox-item" draggable="true" data-type="INICIO">
                <i class="bi bi-play-circle text-success"></i> Inicio
            </div>
            <div class="toolbox-item" draggable="true" data-type="APROBACION">
                <i class="bi bi-check-circle text-primary"></i> Aprobación
            </div>
            <div class="toolbox-item" draggable="true" data-type="DECISION">
                <i class="bi bi-shuffle text-warning"></i> Decisión
            </div>
            <div class="toolbox-item" draggable="true" data-type="RECEPCION">
                <i class="bi bi-box-seam text-secondary"></i> Recepción
            </div>
            <div class="toolbox-item" draggable="true" data-type="FACTURA">
                <i class="bi bi-receipt text-info"></i> Factura
            </div>
            <div class="toolbox-item" draggable="true" data-type="NOTIFICACION">
                <i class="bi bi-envelope text-dark"></i> Notificación
            </div>
            <div class="toolbox-item" draggable="true" data-type="TAREA">
                <i class="bi bi-card-checklist text-muted"></i> Tarea
            </div>
            <div class="toolbox-item" draggable="true" data-type="FIN">
                <i class="bi bi-stop-circle text-danger"></i> Fin
            </div>
        </div>

        <!-- Canvas -->
        <div class="canvas-area" id="canvas">
            <svg class="svg-canvas" id="svgCanvas"></svg>
        </div>
    </div>

    <!-- Properties Drawer -->
    <div class="properties-drawer" id="propertiesDrawer">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold m-0"><i class="bi bi-sliders"></i> Propiedades</h5>
            <button type="button" class="close" onclick="closeDrawer()"><span aria-hidden="true">&times;</span></button>
        </div>
        <div id="flujoProps" class="mb-4">
            <!-- Movido a la barra superior para mejor UX -->
        </div>
        <div id="nodeProps" class="mb-4" style="display: none;">
            <h6 class="fw-bold text-muted border-bottom pb-2">Propiedades del Nodo</h6>
            <input type="hidden" id="nodeId">
            <div class="mb-3">
                <label class="form-label">Nombre del Nodo</label>
                <input type="text" id="nodeName" class="form-control form-control-sm">
            </div>
            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea id="nodeDesc" class="form-control form-control-sm" rows="2"></textarea>
            </div>
            <div class="mb-3 sec-responsabilidad">
                <label class="form-label">Departamento Responsable</label>
                <div class="input-group input-group-sm">
                    <select id="nodeDep" class="form-control form-control-sm" onchange="onDepartmentChange(this.value)">
                        <option value="">[Cualquiera/Solicitante]</option>
                        <?php foreach ($departamentos as $dep) { ?>
                            <option value="<?php echo $dep['Dep_Cod']; ?>"><?php echo $dep['Dep_Des']; ?></option>
                        <?php } ?>
                    </select>
                    <button class="btn btn-outline-secondary" type="button" id="btnManageDepUsers" onclick="abrirGestionUsuarios()" title="Gestionar Usuarios de este Departamento" style="display: none;">
                        <i class="bi bi-people-fill"></i>
                    </button>
                </div>
            </div>
            <div class="mb-3 sec-responsabilidad sec-asignacion-usuarios" style="display: none;">
                <label class="form-label d-block">Asignación de Usuarios</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="userAsigType" id="asigTodos" value="TODOS" checked onchange="toggleAsigType(this.value)">
                    <label class="form-check-label" for="asigTodos">Todos los del depto</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="userAsigType" id="asigEspecificos" value="ESPECIFICOS" onchange="toggleAsigType(this.value)">
                    <label class="form-check-label" for="asigEspecificos">Usuarios específicos</label>
                </div>
                
                <div id="secAsigEspecificosList" class="mt-2" style="display: none; max-height: 150px; overflow-y: auto; border: 1px solid #dee2e6; padding: 8px; border-radius: 4px; background: #fff;">
                    <!-- Checkboxes de usuarios se cargarán dinámicamente aquí -->
                </div>
            </div>
            <div class="mb-3 sec-responsabilidad" id="secNodePer">
                <label class="form-label">Perfil / Rol requerido</label>
                <select id="nodePer" class="form-control form-control-sm">
                    <option value="">[Cualquiera]</option>
                    <?php foreach ($perfiles as $perf) { ?>
                        <option value="<?php echo $perf['Per_Cod']; ?>"><?php echo $perf['Per_Des']; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="mb-3 sec-sla">
                <label class="form-label">Tiempo Límite (Días SLA)</label>
                <input type="number" id="nodeSla" class="form-control form-control-sm" min="0">
            </div>
            <div class="mb-3 form-check sec-checks">
                <input type="checkbox" id="nodeComObl" class="form-check-input">
                <label class="form-check-label">Comentario obligatorio</label>
            </div>
            <div class="mb-3 form-check sec-checks">
                <input type="checkbox" id="nodeAdjObl" class="form-check-input">
                <label class="form-check-label">Archivos adjuntos obligatorios</label>
            </div>
        </div>
    </div>

    <!-- Modal de Gestión de Usuarios por Departamento -->
    <div class="modal fade" id="modalDepUsers" tabindex="-1" role="dialog" aria-labelledby="modalDepUsersLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title fw-bold" id="modalDepUsersLabel"><i class="bi bi-people"></i> Usuarios del Departamento</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="manageDepCod">
                    <p class="text-muted small">Seleccione los usuarios que pertenecen a este departamento para el Workflow:</p>
                    <div id="depUsersList" class="list-group">
                        <!-- Lista de usuarios con checkboxes se cargarán aquí -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-primary" onclick="guardarUsuariosDepartamento()"><i class="bi bi-save"></i> Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Crear/Editar Datos Generales del Flujo -->
    <div class="modal fade" id="modalWorkflowData" tabindex="-1" role="dialog" aria-labelledby="modalWorkflowDataLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title fw-bold" id="modalWorkflowDataLabel"><i class="bi bi-diagram-3"></i> Datos del Flujo Modelo</h4>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Flujo *</label>
                        <input type="text" id="modalFlowName" class="form-control" placeholder="Ej. Aprobación de Compras de Tecnología" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descripción / Notas</label>
                        <textarea id="modalFlowDesc" class="form-control" rows="3" placeholder="Indique el propósito de este flujo..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="aceptarDatosFlujo()"><i class="bi bi-check-lg"></i> Aceptar</button>
                </div>
            </div>
        </div>
    </div>

    <?php
    exit;
}

// Cargar catálogos para configuración de nodos
$wf_mgr->syncDepartamentosFromRrhh($Ses_Emp_Cod);
$departamentos = $obBD_con1->getArrayConsultaSql("
    SELECT d.Wde_Cod AS Dep_Cod, d.Wde_Des AS Dep_Des
    FROM wf_departamentos d
    WHERE d.Emp_Cod = $Ses_Emp_Cod AND d.Wde_Est = 'A'
      AND EXISTS (
          SELECT 1
          FROM wf_departamento_usuarios du
          INNER JOIN usuarios u ON u.Usu_Cod = du.Usu_Cod
          INNER JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod
          WHERE du.Dep_Cod = d.Wde_Cod AND s.Emp_Cod = $Ses_Emp_Cod
            AND u.Usu_Est = 'A' AND u.Usu_Wf = 'S'
      )
    ORDER BY d.Wde_Des;", $obBD_conexion);
$perfiles = $obBD_con1->getArrayConsultaSql("SELECT Per_Cod, Per_Des FROM perfiles WHERE Emp_Cod = $Ses_Emp_Cod AND Per_Est = 'A' ORDER BY Per_Des;", $obBD_conexion);
$wf_mgr->ensureVersioningSchema();
$flujos_existentes = $wf_mgr->listarFlujosPublicados($Ses_Emp_Cod);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>EXA Workflow Builder</title>
    <!-- Bootstrap & FontAwesome Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            overflow: hidden;
        }
        .builder-container {
            display: flex;
            min-height: calc(100vh - 120px);
            height: calc(100vh - 120px);
            border: 1px solid #c5d0dc;
            border-radius: 0;
            overflow: hidden;
            background: #ffffff;
            margin-top: 15px;
        }
        .toolbox {
            width: 220px;
            background-color: #ffffff;
            border-right: 1px solid #dee2e6;
            padding: 10px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            overflow-y: auto;
        }
        .toolbox-item {
            padding: 8px 10px;
            background-color: #f1f3f5;
            border: 1px solid #ced4da;
            border-radius: 8px;
            cursor: grab;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            user-select: none;
            font-size: 12px;
        }
        .canvas-area {
            flex: 1 1 auto;
            position: relative;
            min-width: 0;
            min-height: 100%;
            overflow: auto;
            user-select: none;
            background-color: #e8edf2;
            background-image:
                linear-gradient(to right, rgba(93, 114, 137, 0.16) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(93, 114, 137, 0.16) 1px, transparent 1px),
                radial-gradient(circle, rgba(74, 136, 181, 0.45) 1.5px, transparent 1.5px);
            background-size: 24px 24px, 24px 24px, 24px 24px;
            background-position: -1px -1px, -1px -1px, 0 0;
        }
        .wf-node {
            position: absolute;
            width: 180px;
            background-color: #ffffff;
            border: 2px solid #6c757d;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            cursor: move;
            z-index: 10;
        }
        .wf-node.node-INICIO { border-color: #198754; }
        .wf-node.node-FIN { border-color: #dc3545; }
        .wf-node.node-APROBACION { border-color: #0d6efd; }
        .wf-node.node-DECISION { border-color: #fd7e14; }
        .wf-node.node-RECEPCION { border-color: #6f42c1; }
        .wf-node.node-FACTURA { border-color: #20c997; }
        .wf-node-header {
            padding: 8px 10px;
            border-bottom: 1px solid #dee2e6;
            font-weight: bold;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 8px 8px 0 0;
            background-color: #f8f9fa;
        }
        .wf-node-body {
            padding: 8px 10px;
            font-size: 11px;
            color: #6c757d;
        }
        .node-port {
            width: 12px;
            height: 12px;
            background-color: #adb5bd;
            border-radius: 50%;
            position: absolute;
            border: 2px solid #ffffff;
            cursor: crosshair;
        }
        .node-port-in { left: -6px; top: calc(50% - 6px); }
        .node-port-out { right: -6px; top: calc(50% - 6px); }
        .node-port:hover { background-color: #495057; transform: scale(1.2); }
        .svg-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }
        .properties-drawer {
            position: fixed;
            top: 56px;
            right: -350px;
            width: 350px;
            height: calc(100vh - 56px);
            background-color: #ffffff;
            border-left: 1px solid #dee2e6;
            box-shadow: -4px 0 10px rgba(0,0,0,0.05);
            transition: right 0.3s ease;
            z-index: 100;
            padding: 20px;
            overflow-y: auto;
        }
        .properties-drawer.open { right: 0; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-3">
        <div class="container-fluid">
            <span class="navbar-brand fw-bold"><i class="bi bi-diagram-3"></i> EXA Workflow Builder</span>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <label class="text-white fw-bold m-0 small">Seleccionar Flujo:</label>
                <select id="selWorkflow" class="form-control form-control-sm" style="width: 180px; display: inline-block;">
                    <option value="">-- Seleccionar un Flujo --</option>
                    <?php foreach ($flujos_existentes as $flow) { ?>
                        <option value="<?php echo intval($flow['Wfm_Fam_Cod']); ?>"><?php echo htmlspecialchars($flow['Wfm_Nom'], ENT_QUOTES, 'UTF-8'); ?> (v<?php echo intval($flow['Wfm_Version']); ?>)</option>
                    <?php } ?>
                </select>
                <button class="btn btn-sm btn-info text-white fw-bold" onclick="cargarFlujo()"><i class="bi bi-folder-2-open"></i> Abrir</button>
                <button class="btn btn-sm btn-primary fw-bold" onclick="abrirModalNuevoFlujo()"><i class="bi bi-plus-lg"></i> Nuevo</button>
                <button class="btn btn-sm btn-success fw-bold" onclick="guardarFlujo()"><i class="bi bi-save"></i> Guardar borrador</button>
            <button class="btn btn-sm btn-warning fw-bold text-dark" onclick="publicarFlujo()"><i class="bi bi-cloud-upload"></i> Publicar</button>

                <span class="badge bg-primary p-2 ms-3" id="lblFlowActiveName" style="font-size: 12px; display: none;"><i class="bi bi-diagram-3-fill"></i> Flujo Activo: <span></span></span>
                <!-- Inputs ocultos para mantener compatibilidad con el JS -->
                <input type="hidden" id="flowName">
                <input type="hidden" id="flowDesc">
            </div>
        </div>
    </nav>

    <div class="builder-container">
        <!-- Toolbox -->
        <div class="toolbox">
            <h6 class="fw-bold mb-3">Nodos de Flujo</h6>
            <div class="toolbox-item" draggable="true" data-type="INICIO">
                <i class="bi bi-play-circle text-success"></i> Inicio
            </div>
            <div class="toolbox-item" draggable="true" data-type="APROBACION">
                <i class="bi bi-check-circle text-primary"></i> Aprobación
            </div>
            <div class="toolbox-item" draggable="true" data-type="DECISION">
                <i class="bi bi-shuffle text-warning"></i> Decisión
            </div>
            <div class="toolbox-item" draggable="true" data-type="RECEPCION">
                <i class="bi bi-box-seam text-secondary"></i> Recepción
            </div>
            <div class="toolbox-item" draggable="true" data-type="FACTURA">
                <i class="bi bi-receipt text-info"></i> Factura
            </div>
            <div class="toolbox-item" draggable="true" data-type="NOTIFICACION">
                <i class="bi bi-envelope text-dark"></i> Notificación
            </div>
            <div class="toolbox-item" draggable="true" data-type="TAREA">
                <i class="bi bi-card-checklist text-muted"></i> Tarea
            </div>
            <div class="toolbox-item" draggable="true" data-type="FIN">
                <i class="bi bi-stop-circle text-danger"></i> Fin
            </div>
        </div>

        <!-- Canvas -->
        <div class="canvas-area" id="canvas">
            <svg class="svg-canvas" id="svgCanvas"></svg>
        </div>
    </div>

    <!-- Properties Drawer -->
    <div class="properties-drawer" id="propertiesDrawer">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold m-0"><i class="bi bi-sliders"></i> Propiedades</h5>
            <button type="button" class="close" onclick="closeDrawer()"><span aria-hidden="true">&times;</span></button>
        </div>
        <div id="flujoProps" class="mb-4">
            <!-- Movido a la barra superior para mejor UX -->
        </div>
        <div id="nodeProps" class="mb-4" style="display: none;">
            <h6 class="fw-bold text-muted border-bottom pb-2">Propiedades del Nodo</h6>
            <input type="hidden" id="nodeId">
            <div class="mb-3">
                <label class="form-label">Nombre del Nodo</label>
                <input type="text" id="nodeName" class="form-control form-control-sm">
            </div>
            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea id="nodeDesc" class="form-control form-control-sm" rows="2"></textarea>
            </div>
            <div class="mb-3 sec-responsabilidad">
                <label class="form-label">Departamento Responsable</label>
                <div class="input-group input-group-sm">
                    <select id="nodeDep" class="form-control form-control-sm" onchange="onDepartmentChange(this.value)">
                        <option value="">[Cualquiera/Solicitante]</option>
                        <?php foreach ($departamentos as $dep) { ?>
                            <option value="<?php echo $dep['Dep_Cod']; ?>"><?php echo $dep['Dep_Des']; ?></option>
                        <?php } ?>
                    </select>
                    <button class="btn btn-outline-secondary" type="button" id="btnManageDepUsers" onclick="abrirGestionUsuarios()" title="Gestionar Usuarios de este Departamento" style="display: none;">
                        <i class="bi bi-people-fill"></i>
                    </button>
                </div>
            </div>
            <div class="mb-3 sec-responsabilidad sec-asignacion-usuarios" style="display: none;">
                <label class="form-label d-block">Asignación de Usuarios</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="userAsigType" id="asigTodos" value="TODOS" checked onchange="toggleAsigType(this.value)">
                    <label class="form-check-label" for="asigTodos">Todos los del depto</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="userAsigType" id="asigEspecificos" value="ESPECIFICOS" onchange="toggleAsigType(this.value)">
                    <label class="form-check-label" for="asigEspecificos">Usuarios específicos</label>
                </div>
                
                <div id="secAsigEspecificosList" class="mt-2" style="display: none; max-height: 150px; overflow-y: auto; border: 1px solid #dee2e6; padding: 8px; border-radius: 4px; background: #fff;">
                    <!-- Checkboxes de usuarios se cargarán dinámicamente aquí -->
                </div>
            </div>
            <div class="mb-3 sec-responsabilidad" id="secNodePer">
                <label class="form-label">Perfil / Rol requerido</label>
                <select id="nodePer" class="form-control form-control-sm">
                    <option value="">[Cualquiera]</option>
                    <?php foreach ($perfiles as $perf) { ?>
                        <option value="<?php echo $perf['Per_Cod']; ?>"><?php echo $perf['Per_Des']; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="mb-3 sec-sla">
                <label class="form-label">Tiempo Límite (Días SLA)</label>
                <input type="number" id="nodeSla" class="form-control form-control-sm" min="0">
            </div>
            <div class="mb-3 form-check sec-checks">
                <input type="checkbox" id="nodeComObl" class="form-check-input">
                <label class="form-check-label">Comentario obligatorio</label>
            </div>
            <div class="mb-3 form-check sec-checks">
                <input type="checkbox" id="nodeAdjObl" class="form-check-input">
                <label class="form-check-label">Archivos adjuntos obligatorios</label>
            </div>
        </div>
    </div>

    <!-- Modal de Gestión de Usuarios por Departamento -->
    <div class="modal fade" id="modalDepUsers" tabindex="-1" role="dialog" aria-labelledby="modalDepUsersLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title fw-bold" id="modalDepUsersLabel"><i class="bi bi-people"></i> Usuarios del Departamento</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="manageDepCod">
                    <p class="text-muted small">Seleccione los usuarios que pertenecen a este departamento para el Workflow:</p>
                    <div id="depUsersList" class="list-group">
                        <!-- Lista de usuarios con checkboxes se cargarán aquí -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-primary" onclick="guardarUsuariosDepartamento()"><i class="bi bi-save"></i> Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Crear/Editar Datos Generales del Flujo -->
    <div class="modal fade" id="modalWorkflowData" tabindex="-1" role="dialog" aria-labelledby="modalWorkflowDataLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title fw-bold" id="modalWorkflowDataLabel"><i class="bi bi-diagram-3"></i> Datos del Flujo Modelo</h4>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Flujo *</label>
                        <input type="text" id="modalFlowName" class="form-control" placeholder="Ej. Aprobación de Compras de Tecnología" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descripción / Notas</label>
                        <textarea id="modalFlowDesc" class="form-control" rows="3" placeholder="Indique el propósito de este flujo..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="aceptarDatosFlujo()"><i class="bi bi-check-lg"></i> Aceptar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../VALIDACIONES/wf_builder.js"></script>
</body>
</html>
