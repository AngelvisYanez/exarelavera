<?php
/**
 * EXA Workflow Builder - Diseñador Visual de Flujos
 * @author Oz <oz-agent@warp.dev>
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/wf_manager_log.php');

$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new MysqlDatos($obBD_conexion);

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
            
            // Limpiar conexiones y nodos previos para resalvado (cascada resolverá consistencias)
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
            
            $sqlNodo = "INSERT INTO wf_nodos (Wfm_Cod, Nod_Tip, Nod_Nom, Nod_Des, Dep_Cod, Per_Cod, Nod_Sla, Nod_Com_Obl, Nod_Adj_Obl, Nod_Vis_X, Nod_Vis_Y, Nod_Est) 
                        VALUES ($wfm_cod, '$nodo[tipo]', '$nod_nom', '$nod_des', $dep_cod, $per_cod, $sla, $com_obl, $adj_obl, $vis_x, $vis_y, 'A');";
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
                'y' => $nodo['Nod_Vis_Y']
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

// Cargar catálogos para configuración de nodos
$departamentos = $obBD_con1->getArrayConsultaSql("SELECT Dep_Cod, Dep_Des FROM departamen WHERE Emp_Cod = $Ses_Emp_Cod AND Dep_Est = 'A' ORDER BY Dep_Des;", $obBD_conexion);
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
            height: calc(100vh - 56px);
        }
        .toolbox {
            width: 250px;
            background-color: #ffffff;
            border-right: 1px solid #dee2e6;
            padding: 15px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .toolbox-item {
            padding: 10px;
            background-color: #f1f3f5;
            border: 1px solid #ced4da;
            border-radius: 8px;
            cursor: grab;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            user-select: none;
        }
        .canvas-area {
            flex-grow: 1;
            position: relative;
            background-size: 20px 20px;
            background-image: radial-gradient(circle, #dee2e6 1px, transparent 1px);
            overflow: auto;
            user-select: none;
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand fw-bold"><i class="bi bi-diagram-3"></i> EXA Workflow Builder</span>
            <div class="d-flex gap-2">
                <select id="selWorkflow" class="form-select form-select-sm" style="width: 200px;">
                    <option value="">-- Nuevo Flujo Modelo --</option>
                    <?php foreach ($flujos_existentes as $flow) { ?>
                        <option value="<?php echo $flow['Wfm_Cod']; ?>"><?php echo $flow['Wfm_Nom']; ?></option>
                    <?php } ?>
                </select>
                <button class="btn btn-sm btn-outline-light" onclick="cargarFlujo()"><i class="bi bi-folder-2-open"></i> Abrir</button>
                <button class="btn btn-sm btn-success" onclick="guardarFlujo()"><i class="bi bi-save"></i> Guardar</button>
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
            <button type="button" class="btn-close" onclick="closeDrawer()"></button>
        </div>
        <div id="flujoProps" class="mb-4">
            <h6 class="fw-bold text-muted border-bottom pb-2">Información del Flujo</h6>
            <div class="mb-3">
                <label class="form-label">Nombre del Flujo</label>
                <input type="text" id="flowName" class="form-control form-control-sm" placeholder="Ej. Compra de Bienes">
            </div>
            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea id="flowDesc" class="form-control form-control-sm" rows="3"></textarea>
            </div>
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
                <select id="nodeDep" class="form-select form-select-sm">
                    <option value="">[Cualquiera/Solicitante]</option>
                    <?php foreach ($departamentos as $dep) { ?>
                        <option value="<?php echo $dep['Dep_Cod']; ?>"><?php echo $dep['Dep_Des']; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="mb-3 sec-responsabilidad">
                <label class="form-label">Perfil / Rol requerido</label>
                <select id="nodePer" class="form-select form-select-sm">
                    <option value="">[Cualquiera]</option>
                    <?php foreach ($perfiles as $perf) { ?>
                        <option value="<?php echo $perf['Per_Cod']; ?>"><?php echo $perf['Per_Des']; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="mb-3 sec-sla">
                <label class="form-label">Tiempo Límite (Horas SLA)</label>
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

    <script src="../VALIDACIONES/wf_builder.js"></script>
</body>
</html>
