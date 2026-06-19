<?php
/**
 * EXA Workflow Builder - Dise�ador Visual de Flujos
 * @author Oz <oz-agent@warp.dev>
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/wf_manager_log.php');

$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new MysqlDatos($obBD_conexion);
$wf_mgr = new wf_manager_log($Ses_Dat_Dis);

// Verificar acceso a la ventana 'configuracion' y pesta�a 'disenador_flujos'
if (!$wf_mgr->verificarAccesoVentana('configuracion', 'disenador_flujos')) {
    if (isset($ajax_save_workflow) || isset($ajax_load_workflow) || isset($ajax_get_department_users) || isset($ajax_save_department_users) || isset($ajax_get_users_by_department)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Acceso denegado. No tiene permisos para realizar esta acci�n.'));
        exit;
    } else {
        echo "<div class='alert alert-danger m-3'>Acceso denegado. No tiene permisos para ver esta ventana.</div>";
        exit;
    }
}

// Redirecci�n segura para navegaci�n directa del navegador (no AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['ajax_get_builder']) && !isset($_GET['ajax_load_workflow']) && !isset($_GET['ajax_get_department_users']) && !isset($_GET['ajax_get_users_by_department'])) {
    header("Location: adq_configuracion.php?tab=disenador");
    exit;
}

// Manejo de peticiones AJAX
if (isset($ajax_save_workflow)) {
    $data = json_decode(file_get_contents('php://input'), true);
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $wfm_nom = mysqli_real_escape_string($obBD_conexion->conexion, $data['nombre']);
        $wfm_des = mysqli_real_escape_string($obBD_conexion->conexion, $data['descripcion']);
        
        // 1. Guardar o actualizar cabecera del Flujo Modelo
        if (empty($data['id'])) {
            $sqlFlujo = "INSERT INTO wf_flujos_modelos (Emp_Cod, Wfm_Nom, Wfm_Des, Wfm_Est) VALUES ($Ses_Emp_Cod, '$wfm_nom', '$wfm_des', 'A');";
            $obBD_con1->grabarv_registros($sqlFlujo, $obBD_conexion);
            $wfm_cod = $obBD_con1->insercionid($obBD_conexion);
        } else {
            $wfm_cod = intval($data['id']);
            $sqlFlujo = "UPDATE wf_flujos_modelos SET Wfm_Nom = '$wfm_nom', Wfm_Des = '$wfm_des' WHERE Wfm_Cod = $wfm_cod;";
            $obBD_con1->grabarv_registros($sqlFlujo, $obBD_conexion);
            
            // Limpiar conexiones y nodos previos para resalvado (cascada resolver� consistencias)
            $obBD_con1->grabarv_registros("DELETE FROM wf_conexiones WHERE Wfm_Cod = $wfm_cod;", $obBD_conexion);
            $obBD_con1->grabarv_registros("DELETE FROM wf_nodos WHERE Wfm_Cod = $wfm_cod;", $obBD_conexion);
        }

        // 2. Guardar Nodos
        $node_map = array(); // Mapea id temporal frontend a Nod_Cod real
        foreach ($data['nodos'] as $nodo) {
            $nod_nom = mysqli_real_escape_string($obBD_conexion->conexion, $nodo['nombre']);
            $nod_des = mysqli_real_escape_string($obBD_conexion->conexion, $nodo['descripcion']);
            $dep_cod = !empty($nodo['dep_cod']) ? intval($nodo['dep_cod']) : 'NULL';
            $per_cod = !empty($nodo['per_cod']) ? intval($nodo['per_cod']) : 'NULL';
            $sla = !empty($nodo['sla']) ? intval($nodo['sla']) : 'NULL';
            $com_obl = !empty($nodo['com_obl']) ? 1 : 0;
            $adj_obl = !empty($nodo['adj_obl']) ? 1 : 0;
            $vis_x = intval($nodo['x']);
            $vis_y = intval($nodo['y']);
            $usu_asig = !empty($nodo['usu_asig']) ? "'" . mysqli_real_escape_string($obBD_conexion->conexion, $nodo['usu_asig']) . "'" : "'TODOS'";
            
            $sqlNodo = "INSERT INTO wf_nodos (Wfm_Cod, Nod_Tip, Nod_Nom, Nod_Des, Dep_Cod, Per_Cod, Nod_Sla, Nod_Com_Obl, Nod_Adj_Obl, Nod_Vis_X, Nod_Vis_Y, Nod_Est, Nod_Usu_Asig) 
                        VALUES ($wfm_cod, '$nodo[tipo]', '$nod_nom', '$nod_des', $dep_cod, $per_cod, $sla, $com_obl, $adj_obl, $vis_x, $vis_y, 'A', $usu_asig);";
            $obBD_con1->grabarv_registros($sqlNodo, $obBD_conexion);
            $real_id = $obBD_con1->insercionid($obBD_conexion);
            $node_map[$nodo['id']] = $real_id;
        }

        // 3. Guardar Conexiones
        foreach ($data['conexiones'] as $con) {
            $nod_ori = $node_map[$con['origen']];
            $nod_des = $node_map[$con['destino']];
            $con_acc = mysqli_real_escape_string($obBD_conexion->conexion, $con['accion']);
            $con_con_exp = !empty($con['condicion']) ? "'" . mysqli_real_escape_string($obBD_conexion->conexion, json_encode($con['condicion'])) . "'" : 'NULL';
            
            $sqlCon = "INSERT INTO wf_conexiones (Wfm_Cod, Nod_Ori, Nod_Des, Con_Acc, Con_Con_Exp) 
                       VALUES ($wfm_cod, $nod_ori, $nod_des, '$con_acc', $con_con_exp);";
            $obBD_con1->grabarv_registros($sqlCon, $obBD_conexion);
        }

        $obBD_con1->commit_nomsn($obBD_conexion);
        $obBD_con1->echoJson(array('success' => true, 'id' => $wfm_cod));
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
}

if (isset($ajax_load_workflow)) {
    $wfm_cod = intval($_GET['id']);
    try {
        $flujo = $obBD_con1->getRowConsultaSql("SELECT * FROM wf_flujos_modelos WHERE Wfm_Cod = $wfm_cod;", $obBD_conexion);
        $nodos = $obBD_con1->getArrayConsultaSql("SELECT * FROM wf_nodos WHERE Wfm_Cod = $wfm_cod AND Nod_Est = 'A';", $obBD_conexion);
        $conexiones = $obBD_con1->getArrayConsultaSql("SELECT * FROM wf_conexiones WHERE Wfm_Cod = $wfm_cod;", $obBD_conexion);
        
        $payload_nodos = array();
        foreach ($nodos as $nodo) {
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
        foreach ($conexiones as $con) {
            $condicion = !empty($con['Con_Con_Exp']) ? json_decode($con['Con_Con_Exp'], true) : null;
            $payload_conexiones[] = array(
                'origen' => $con['Nod_Ori'],
                'destino' => $con['Nod_Des'],
                'accion' => $con['Con_Acc'],
                'condicion' => $condicion
            );
        }

        $obBD_con1->echoJson(array(
            'success' => true,
            'flujo' => array(
                'id' => $flujo['Wfm_Cod'],
                'nombre' => $flujo['Wfm_Nom'],
                'descripcion' => $flujo['Wfm_Des']
            ),
            'nodos' => $payload_nodos,
            'conexiones' => $payload_conexiones
        ));
    } catch (Exception $e) {
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
}

// --- AJAX: Obtener usuarios de la empresa con flag de asignaci�n al departamento ---
if (isset($ajax_get_department_users)) {
    $dep_cod = intval($_GET['dep_cod']);
    try {
        $usuarios = $obBD_con1->getArrayConsultaSql("
            SELECT u.Usu_Cod, CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as Usuario_Nom,
                   IF(du.Wdu_Cod IS NOT NULL, 1, 0) as asignado
            FROM usuarios u
            INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
            INNER JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod
            LEFT JOIN wf_departamento_usuarios du ON du.Usu_Cod = u.Usu_Cod AND du.Dep_Cod = $dep_cod
            WHERE s.Emp_Cod = $Ses_Emp_Cod AND u.Usu_Est = 'A'
            ORDER BY Usuario_Nom;", $obBD_conexion);
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
            $obBD_con1->grabarv_registros("INSERT INTO wf_departamento_usuarios (Dep_Cod, Usu_Cod) VALUES ($dep_cod, $u_id);", $obBD_conexion);
        }
        $obBD_con1->commit_nomsn($obBD_conexion);
        $obBD_con1->echoJson(array('success' => true));
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
    exit;
}

// --- AJAX: Obtener usuarios asignados a un departamento ---
if (isset($ajax_get_users_by_department)) {
    $dep_cod = intval($_GET['dep_cod']);
    try {
        $usuarios = $obBD_con1->getArrayConsultaSql("
            SELECT u.Usu_Cod, CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as Usuario_Nom
            FROM wf_departamento_usuarios du
            INNER JOIN usuarios u ON u.Usu_Cod = du.Usu_Cod
            INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
            WHERE du.Dep_Cod = $dep_cod AND u.Usu_Est = 'A'
            ORDER BY Usuario_Nom;", $obBD_conexion);
        $obBD_con1->echoJson(array('success' => true, 'usuarios' => $usuarios));
    } catch (Exception $e) {
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
    exit;
}

// Cargar cat�logos para configuraci�n de nodos
$departamentos = $obBD_con1->getArrayConsultaSql("SELECT MIN(Dep_Cod) AS Dep_Cod, Dep_Des FROM departamen WHERE Emp_Cod = $Ses_Emp_Cod AND Dep_Est = 'A' GROUP BY Dep_Des ORDER BY Dep_Des;", $obBD_conexion);
$perfiles = $obBD_con1->getArrayConsultaSql("SELECT Per_Cod, Per_Des FROM perfiles WHERE Emp_Cod = $Ses_Emp_Cod AND Per_Est = 'A' ORDER BY Per_Des;", $obBD_conexion);
$flujos_existentes = $obBD_con1->getArrayConsultaSql("SELECT Wfm_Cod, Wfm_Nom FROM wf_flujos_modelos WHERE Emp_Cod = $Ses_Emp_Cod AND Wfm_Est = 'A';", $obBD_conexion);

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
    </style>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 p-2 bg-light border rounded gap-2 shadow-sm">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <label class="fw-bold m-0 small text-dark"><i class="bi bi-funnel"></i> Seleccionar Flujo:</label>
            <select id="selWorkflow" class="form-control form-control-sm" style="width: 200px; display: inline-block;">
                <option value="">-- Seleccionar un Flujo --</option>
                <?php foreach ($flujos_existentes as $flow) { ?>
                    <option value="<?php echo $flow['Wfm_Cod']; ?>"><?php echo $flow['Wfm_Nom']; ?></option>
                <?php } ?>
            </select>
            <button class="btn btn-sm btn-info text-white fw-bold" onclick="cargarFlujo()"><i class="bi bi-folder-2-open"></i> Abrir</button>
            <button class="btn btn-sm btn-primary fw-bold" onclick="abrirModalNuevoFlujo()"><i class="bi bi-plus-lg"></i> Nuevo</button>
            <button class="btn btn-sm btn-success fw-bold" onclick="guardarFlujo()"><i class="bi bi-save"></i> Guardar</button>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="badge bg-primary p-2" id="lblFlowActiveName" style="font-size: 12px; display: none;"><i class="bi bi-diagram-3-fill"></i> Flujo Activo: <span></span></span>
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
                <i class="bi bi-check-circle text-primary"></i> Aprobaci�n
            </div>
            <div class="toolbox-item" draggable="true" data-type="DECISION">
                <i class="bi bi-shuffle text-warning"></i> Decisi�n
            </div>
            <div class="toolbox-item" draggable="true" data-type="RECEPCION">
                <i class="bi bi-box-seam text-secondary"></i> Recepci�n
            </div>
            <div class="toolbox-item" draggable="true" data-type="FACTURA">
                <i class="bi bi-receipt text-info"></i> Factura
            </div>
            <div class="toolbox-item" draggable="true" data-type="NOTIFICACION">
                <i class="bi bi-envelope text-dark"></i> Notificaci�n
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
                <label class="form-label">Descripci�n</label>
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
                <label class="form-label d-block">Asignaci�n de Usuarios</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="userAsigType" id="asigTodos" value="TODOS" checked onchange="toggleAsigType(this.value)">
                    <label class="form-check-label" for="asigTodos">Todos los del depto</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="userAsigType" id="asigEspecificos" value="ESPECIFICOS" onchange="toggleAsigType(this.value)">
                    <label class="form-check-label" for="asigEspecificos">Usuarios espec�ficos</label>
                </div>
                
                <div id="secAsigEspecificosList" class="mt-2" style="display: none; max-height: 150px; overflow-y: auto; border: 1px solid #dee2e6; padding: 8px; border-radius: 4px; background: #fff;">
                    <!-- Checkboxes de usuarios se cargar�n din�micamente aqu� -->
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
                <label class="form-label">Tiempo L�mite (Horas SLA)</label>
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

    <!-- Modal de Gesti�n de Usuarios por Departamento -->
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
                        <!-- Lista de usuarios con checkboxes se cargar� aqu� -->
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
                        <input type="text" id="modalFlowName" class="form-control" placeholder="Ej. Aprobaci�n de Compras de Tecnolog�a" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descripci�n / Notas</label>
                        <textarea id="modalFlowDesc" class="form-control" rows="3" placeholder="Indique el prop�sito de este flujo..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="aceptarDatosFlujo()"><i class="bi bi-check-lg"></i> Aceptar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../VALIDACIONES/wf_builder.js"></script>
    <?php
    exit;
}

// Cargar cat�logos para configuraci�n de nodos
$departamentos = $obBD_con1->getArrayConsultaSql("SELECT MIN(Dep_Cod) AS Dep_Cod, Dep_Des FROM departamen WHERE Emp_Cod = $Ses_Emp_Cod AND Dep_Est = 'A' GROUP BY Dep_Des ORDER BY Dep_Des;", $obBD_conexion);
$perfiles = $obBD_con1->getArrayConsultaSql("SELECT Per_Cod, Per_Des FROM perfiles WHERE Emp_Cod = $Ses_Emp_Cod AND Per_Est = 'A' ORDER BY Per_Des;", $obBD_conexion);
$flujos_existentes = $obBD_con1->getArrayConsultaSql("SELECT Wfm_Cod, Wfm_Nom FROM wf_flujos_modelos WHERE Emp_Cod = $Ses_Emp_Cod AND Wfm_Est = 'A';", $obBD_conexion);
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
                        <option value="<?php echo $flow['Wfm_Cod']; ?>"><?php echo $flow['Wfm_Nom']; ?></option>
                    <?php } ?>
                </select>
                <button class="btn btn-sm btn-info text-white fw-bold" onclick="cargarFlujo()"><i class="bi bi-folder-2-open"></i> Abrir</button>
                <button class="btn btn-sm btn-primary fw-bold" onclick="abrirModalNuevoFlujo()"><i class="bi bi-plus-lg"></i> Nuevo</button>
                <button class="btn btn-sm btn-success fw-bold" onclick="guardarFlujo()"><i class="bi bi-save"></i> Guardar</button>

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
                <i class="bi bi-check-circle text-primary"></i> Aprobaci�n
            </div>
            <div class="toolbox-item" draggable="true" data-type="DECISION">
                <i class="bi bi-shuffle text-warning"></i> Decisi�n
            </div>
            <div class="toolbox-item" draggable="true" data-type="RECEPCION">
                <i class="bi bi-box-seam text-secondary"></i> Recepci�n
            </div>
            <div class="toolbox-item" draggable="true" data-type="FACTURA">
                <i class="bi bi-receipt text-info"></i> Factura
            </div>
            <div class="toolbox-item" draggable="true" data-type="NOTIFICACION">
                <i class="bi bi-envelope text-dark"></i> Notificaci�n
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
                <label class="form-label">Descripci�n</label>
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
                <label class="form-label d-block">Asignaci�n de Usuarios</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="userAsigType" id="asigTodos" value="TODOS" checked onchange="toggleAsigType(this.value)">
                    <label class="form-check-label" for="asigTodos">Todos los del depto</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="userAsigType" id="asigEspecificos" value="ESPECIFICOS" onchange="toggleAsigType(this.value)">
                    <label class="form-check-label" for="asigEspecificos">Usuarios espec�ficos</label>
                </div>
                
                <div id="secAsigEspecificosList" class="mt-2" style="display: none; max-height: 150px; overflow-y: auto; border: 1px solid #dee2e6; padding: 8px; border-radius: 4px; background: #fff;">
                    <!-- Checkboxes de usuarios se cargar�n din�micamente aqu� -->
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
                <label class="form-label">Tiempo L�mite (Horas SLA)</label>
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

    <!-- Modal de Gesti�n de Usuarios por Departamento -->
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
                        <!-- Lista de usuarios con checkboxes se cargar� aqu� -->
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
                        <input type="text" id="modalFlowName" class="form-control" placeholder="Ej. Aprobaci�n de Compras de Tecnolog�a" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descripci�n / Notas</label>
                        <textarea id="modalFlowDesc" class="form-control" rows="3" placeholder="Indique el prop�sito de este flujo..."></textarea>
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
