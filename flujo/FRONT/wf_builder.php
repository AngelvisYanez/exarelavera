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
    if (isset($ajax_save_workflow) || isset($ajax_publish_workflow) || isset($ajax_duplicate_workflow) || isset($ajax_load_workflow) || isset($ajax_get_department_users) || isset($ajax_save_department_users) || isset($ajax_get_users_by_department) || isset($ajax_get_departamentos_disenador)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Acceso denegado. No tiene permisos para realizar esta acción.'));
        exit;
    } else {
        echo "<div class='alert alert-danger m-3'>Acceso denegado. No tiene permisos para ver esta ventana.</div>";
        exit;
    }
}

// Redirección segura para navegación directa del navegador (no AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['ajax_get_builder']) && !isset($_GET['ajax_load_workflow']) && !isset($_GET['ajax_get_department_users']) && !isset($_GET['ajax_get_users_by_department']) && !isset($_GET['ajax_get_departamentos_disenador'])) {
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

if (isset($ajax_duplicate_workflow)) {
    $data = json_decode(file_get_contents('php://input'), true);
    $selector_cod = !empty($data['id']) ? intval($data['id']) : 0;
    $nombre = isset($data['nombre']) ? trim($data['nombre']) : '';
    $descripcion = array_key_exists('descripcion', $data) ? $data['descripcion'] : null;
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $result = $wf_mgr->duplicarFlujoDisenador($selector_cod, $Ses_Emp_Cod, $nombre, $descripcion);
        $obBD_con1->commit_nomsn($obBD_conexion);
        $obBD_con1->echoJson(array_merge(array('success' => true, 'message' => 'Esquema duplicado correctamente.'), $result));
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
                'descripcion' => isset($nodo['Nod_Des']) ? (string)$nodo['Nod_Des'] : '',
                'dep_cod' => $wf_mgr->resolverWdeCodDisenador($nodo['Dep_Cod'], $Ses_Emp_Cod),
                'per_cod' => $nodo['Per_Cod'],
                'sla' => $nodo['Nod_Sla'],
                'com_obl' => $nodo['Nod_Com_Obl'],
                'adj_obl' => $nodo['Nod_Adj_Obl'],
                'cot_edit' => !empty($nodo['Nod_Cot_Edit']) ? 1 : 0,
                'cot_sel' => !empty($nodo['Nod_Cot_Sel']) ? 1 : 0,
                'cre_sol' => !isset($nodo['Nod_Cre_Sol']) ? 1 : (!empty($nodo['Nod_Cre_Sol']) ? 1 : 0),
                'not_wa' => !empty($nodo['Nod_Not_Wa']) ? 1 : 0,
                'not_em' => !empty($nodo['Nod_Not_Em']) ? 1 : 0,
                'not_asunto' => isset($nodo['Nod_Not_Asunto']) ? (string)$nodo['Nod_Not_Asunto'] : '',
                'not_texto' => isset($nodo['Nod_Not_Texto']) ? (string)$nodo['Nod_Not_Texto'] : '',
                'x' => $nodo['Nod_Vis_X'],
                'y' => $nodo['Nod_Vis_Y'],
                'usu_asig' => !empty($nodo['Nod_Usu_Asig']) ? $nodo['Nod_Usu_Asig'] : 'TODOS',
                'asig_nombres' => $wf_mgr->resolverTextoAsignadosDisenador(
                    $nodo['Dep_Cod'],
                    !empty($nodo['Nod_Usu_Asig']) ? $nodo['Nod_Usu_Asig'] : 'TODOS',
                    $nodo['Per_Cod']
                )
            );
        }

        $payload_conexiones = array();
        foreach ($pack['conexiones'] as $con) {
            $condicion = !empty($con['Con_Con_Exp']) ? json_decode($con['Con_Con_Exp'], true) : null;
            $side_ori = isset($con['Con_Side_Ori']) ? $con['Con_Side_Ori'] : null;
            $side_des = isset($con['Con_Side_Des']) ? $con['Con_Side_Des'] : null;
            // Compatibilidad: puertos embebidos en JSON de condicion
            if ((empty($side_ori) || empty($side_des)) && is_array($condicion) && !empty($condicion['_ports'])) {
                if (empty($side_ori) && !empty($condicion['_ports']['ori'])) {
                    $side_ori = $condicion['_ports']['ori'];
                }
                if (empty($side_des) && !empty($condicion['_ports']['des'])) {
                    $side_des = $condicion['_ports']['des'];
                }
                unset($condicion['_ports']);
                if (empty($condicion)) {
                    $condicion = null;
                }
            }
            $comentario = '';
            if (is_array($condicion) && isset($condicion['comentario'])) {
                $comentario = trim((string)$condicion['comentario']);
            }
            $payload_conexiones[] = array(
                'origen' => $con['Nod_Ori'],
                'destino' => $con['Nod_Des'],
                'accion' => $con['Con_Acc'],
                'condicion' => $condicion,
                'comentario' => $comentario,
                'side_ori' => $side_ori,
                'side_des' => $side_des
            );
        }

        $estado = $flujo['Wfm_Est'];
        $payload = array(
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
        );
        if (function_exists('utf8_encode_deep')) {
            utf8_encode_deep($payload);
        }
        $obBD_con1->echoJson($payload);
    } catch (Exception $e) {
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
}

// --- AJAX: Obtener usuarios de la empresa con flag de asignación al departamento ---
if (isset($ajax_get_department_users)) {
    $wde_cod = intval($_GET['dep_cod']);
    if (!$wf_mgr->validarWdeCodWorkflow($wde_cod, $Ses_Emp_Cod)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Departamento de workflow no valido.'));
        exit;
    }
    try {
        $filtro_du = $wf_mgr->sqlDuPorWdeCod($wde_cod, 'du2');
        $usuarios = $obBD_con1->getArrayConsultaSql("
            SELECT base.Usu_Cod,
                   base.Usuario_Nom,
                   IF(EXISTS (
                       SELECT 1
                       FROM usuarios ux
                       INNER JOIN sucursal sx ON sx.Suc_Cod = ux.Suc_Cod
                       INNER JOIN wf_departamento_usuarios du2 ON du2.Usu_Cod = ux.Usu_Cod
                       WHERE sx.Emp_Cod = $Ses_Emp_Cod AND ux.Usu_Ced = base.Usu_Ced
                         AND ux.Usu_Est = 'A' AND ux.Usu_Wf = 'S'
                         AND $filtro_du
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
        if (function_exists('utf8_encode_deep') && $usuarios) {
            utf8_encode_deep($usuarios);
        }
        $obBD_con1->echoJson(array('success' => true, 'usuarios' => $usuarios));
    } catch (Exception $e) {
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
    exit;
}

// --- AJAX: Guardar asignaciones de usuarios a un departamento ---
if (isset($ajax_save_department_users)) {
    $wde_cod = intval($_POST['dep_cod']);
    $usuarios_ids = isset($_POST['usuarios']) ? $_POST['usuarios'] : array();
    try {
        $result = $wf_mgr->guardarUsuariosDepartamentoWorkflow($wde_cod, $Ses_Emp_Cod, $usuarios_ids);
        $obBD_con1->echoJson(array('success' => true, 'data' => $result));
    } catch (Exception $e) {
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
    exit;
}

// --- AJAX: Obtener usuarios asignados a un departamento (uno por persona/cédula) ---
if (isset($ajax_get_users_by_department)) {
    $wde_cod = intval($_GET['dep_cod']);
    if (!$wf_mgr->validarWdeCodWorkflow($wde_cod, $Ses_Emp_Cod)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Departamento de workflow no valido.'));
        exit;
    }
    try {
        $usuarios = $wf_mgr->listarUsuariosAsignacionDepartamento($wde_cod, $Ses_Emp_Cod);
        if (function_exists('utf8_encode_deep') && $usuarios) {
            utf8_encode_deep($usuarios);
        }
        $obBD_con1->echoJson(array('success' => true, 'usuarios' => $usuarios));
    } catch (Exception $e) {
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
    exit;
}

// --- AJAX: Listar departamentos activos del diseñador (combo nodo) ---
if (isset($ajax_get_departamentos_disenador)) {
    try {
        $departamentos = $wf_mgr->listarDepartamentosDisenador($Ses_Emp_Cod);
        if (!is_array($departamentos)) {
            $departamentos = array();
        }
        if (function_exists('utf8_encode_deep') && $departamentos) {
            utf8_encode_deep($departamentos);
        }
        $obBD_con1->echoJson(array('success' => true, 'departamentos' => $departamentos));
    } catch (Exception $e) {
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
    exit;
}

// Cargar catálogos para configuración de nodos
$departamentos = $wf_mgr->listarDepartamentosDisenador($Ses_Emp_Cod);
$perfiles = $obBD_con1->getArrayConsultaSql("SELECT Per_Cod, Per_Des FROM perfiles WHERE Emp_Cod = $Ses_Emp_Cod AND Per_Est = 'A' ORDER BY Per_Des;", $obBD_conexion);
$wf_mgr->ensureVersioningSchema();
$flujos_existentes = $wf_mgr->listarFlujosDisenador($Ses_Emp_Cod);
if (function_exists('utf8_encode_deep')) {
    if ($departamentos) {
        utf8_encode_deep($departamentos);
    }
    if ($perfiles) {
        utf8_encode_deep($perfiles);
    }
    if ($flujos_existentes) {
        utf8_encode_deep($flujos_existentes);
    }
}

if (!isset($wf_builder_nodos_ocultos) || !is_array($wf_builder_nodos_ocultos)) {
    $wf_builder_nodos_ocultos = array();
}

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
        .toolbox-item > .bi {
            font-size: 1.45rem;
            line-height: 1;
            flex-shrink: 0;
            width: 1.5rem;
            text-align: center;
        }
        .wf-node-tip {
            position: fixed;
            z-index: 10050;
            max-width: 280px;
            padding: 8px 10px;
            border-radius: 6px;
            background: #1e293b;
            color: #f8fafc;
            font-size: 12px;
            line-height: 1.35;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.28);
            pointer-events: none;
            opacity: 0;
            visibility: hidden;
            transition: opacity .12s ease;
            white-space: normal;
            word-break: break-word;
        }
        .wf-node-tip.is-visible {
            opacity: 1;
            visibility: visible;
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
        .canvas-surface {
            position: relative;
            min-width: 100%;
            min-height: 100%;
            box-sizing: border-box;
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
        .wf-node.is-selected {
            border-width: 3px;
            border-color: #f59e0b !important;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.4), 0 8px 18px rgba(15, 23, 42, 0.18);
            z-index: 40;
        }
        .wf-node.node-INICIO.is-selected,
        .wf-node.node-FIN.is-selected,
        .node-INICIO.is-selected,
        .node-FIN.is-selected {
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.45), 0 8px 18px rgba(15, 23, 42, 0.18);
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
            position: relative;
        }
        .wf-node-header > button {
            position: absolute;
            top: 3px;
            right: 3px;
            width: 18px;
            height: 18px;
            min-width: 18px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            border: none;
            border-radius: 50%;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.12);
            z-index: 3;
            cursor: pointer;
            line-height: 1;
        }
        .wf-node-header > button .bi {
            font-size: 15px;
            font-weight: 900;
            color: #dc3545 !important;
            line-height: 1;
            -webkit-text-stroke: 0.35px #dc3545;
            paint-order: stroke fill;
        }
        .wf-node-header > button:hover .bi {
            color: #b02a37 !important;
            -webkit-text-stroke: 0.35px #b02a37;
        }
        .wf-node-header > span {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            min-width: 0;
            flex: 1;
            text-align: center;
        }
        .wf-node-header .wf-node-type-icon {
            font-size: 2rem;
            line-height: 1;
            flex-shrink: 0;
        }
        .wf-node-header .wf-node-title-label {
            display: block;
            max-width: 100%;
            font-size: 11px;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .wf-node-body .wf-node-tipo-label {
            display: block;
            font-size: 10px;
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 2px;
        }
        .wf-node-body {
            padding: 10px;
            text-align: center;
        }
        .wf-node-body .wf-node-desc {
            display: block;
            margin-top: 4px;
            font-size: 11px;
            line-height: 1.35;
            color: #495057;
            word-break: break-word;
            white-space: pre-wrap;
        }
        .wf-node-body .wf-node-asig {
            display: block;
            margin-top: 4px;
            font-size: 10px;
            line-height: 1.35;
            color: #0d6efd;
            word-break: break-word;
        }
        .node-port {
            position: absolute;
            width: 12px;
            height: 12px;
            background-color: #6c757d;
            border-radius: 50%;
            border: 2px solid #ffffff;
            cursor: crosshair;
            z-index: 5;
        }
        .node-port:hover {
            background-color: #0d6efd;
            transform: scale(1.25);
        }
        .node-port-left {
            top: 50%;
            left: -6px;
            margin-top: -6px;
        }
        .node-port-right {
            top: 50%;
            right: -6px;
            margin-top: -6px;
        }
        .node-port-top {
            top: -6px;
            left: 50%;
            margin-left: -6px;
        }
        .node-port-bottom {
            bottom: -6px;
            left: 50%;
            margin-left: -6px;
        }
        /* Compatibilidad con clases antiguas */
        .node-port-in { top: 50%; left: -6px; margin-top: -6px; }
        .node-port-out { top: 50%; right: -6px; margin-top: -6px; }
        .svg-canvas {
            position: absolute;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: 1;
        }
        .svg-canvas path,
        .svg-canvas .wf-conn-group,
        .svg-canvas .wf-conn-delete {
            pointer-events: auto;
            cursor: pointer;
        }
        .wf-conn-group .wf-conn-delete {
            opacity: 0.85;
        }
        .wf-conn-group:hover .wf-conn-line {
            stroke: #0d6efd !important;
            stroke-width: 3.5px;
        }
        .wf-conn-group:hover .wf-conn-delete,
        .wf-conn-group.is-selected .wf-conn-delete {
            opacity: 1;
        }
        .wf-conn-delete:hover circle {
            fill: #b02a37;
        }
        .node-INICIO,
        .node-FIN {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            overflow: visible;
            padding: 10px 12px;
            user-select: none;
            box-sizing: border-box;
        }
        .node-INICIO { border-color: #198754; background-color: #d1e7dd; color: #198754; }
        .node-FIN { border-color: #dc3545; background-color: #f8d7da; color: #dc3545; }
        .node-INICIO .wf-node-header,
        .node-FIN .wf-node-header {
            background: transparent;
            border: none;
            border-bottom: none;
            border-radius: 0;
            padding: 4px 8px 0;
            flex: 0 0 auto;
            width: 100%;
            min-height: 0;
            flex-direction: column;
            gap: 2px;
            justify-content: center;
            align-items: center;
            position: relative;
            cursor: move;
        }
        .node-INICIO .wf-node-header span,
        .node-FIN .wf-node-header span {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            line-height: 1.2;
            width: 100%;
            font-weight: 700;
        }
        .node-INICIO .wf-node-header .wf-node-type-icon,
        .node-FIN .wf-node-header .wf-node-type-icon,
        .node-INICIO .wf-node-header span > .bi,
        .node-FIN .wf-node-header span > .bi {
            font-size: 28px;
            line-height: 1;
        }
        .node-INICIO .wf-node-header .wf-node-title-label,
        .node-FIN .wf-node-header .wf-node-title-label,
        .node-INICIO .wf-node-header .wf-node-terminal-label,
        .node-FIN .wf-node-header .wf-node-terminal-label {
            display: block;
            max-width: 120px;
            font-size: 12px;
            line-height: 1.25;
            white-space: normal;
            word-break: break-word;
            overflow: visible;
            text-overflow: clip;
        }
        .node-INICIO .wf-node-header button .bi,
        .node-FIN .wf-node-header button .bi {
            font-size: 12px;
            font-weight: 900;
            -webkit-text-stroke: 0.3px #dc3545;
        }
        .node-INICIO .wf-node-body,
        .node-FIN .wf-node-body {
            display: block;
            padding: 4px 10px 8px;
            text-align: center;
            font-size: 11px;
            line-height: 1.3;
            width: 100%;
            box-sizing: border-box;
        }
        .node-INICIO .wf-node-body .wf-node-tipo-label,
        .node-FIN .wf-node-body .wf-node-tipo-label {
            font-size: 11px;
            margin-bottom: 2px;
        }
        .node-INICIO .wf-node-body .wf-node-desc,
        .node-FIN .wf-node-body .wf-node-desc,
        .node-INICIO .wf-node-body .wf-node-asig,
        .node-FIN .wf-node-body .wf-node-asig {
            font-size: 10px;
            max-width: 120px;
            margin-left: auto;
            margin-right: auto;
        }
        .node-INICIO .wf-node-header button,
        .node-FIN .wf-node-header button {
            top: 8px;
            right: 14px;
        }
        .node-APROBACION { border-color: #0d6efd; }
        .node-APROBACION .wf-node-header { background-color: #cfe2ff; color: #0d6efd; }
        .node-DECISION { border-color: #ffc107; }
        .node-DECISION .wf-node-header { background-color: #fff3cd; color: #ffc107; }
        .node-NOTIFICACION { border-color: #495057; }
        .node-NOTIFICACION .wf-node-header { background-color: #e9ecef; color: #212529; }
        .node-FACTURA {
            border-color: #ffc107;
            background-color: #fffbeb;
        }
        .node-FACTURA .wf-node-header {
            background-color: #fff3cd;
            color: #856404;
            border-bottom: 1px solid #ffc107;
        }
        .node-TAREA {
            border-color: #198754;
            background-color: #f4fbf6;
        }
        .node-TAREA .wf-node-header {
            background-color: #d1e7dd;
            color: #198754;
            border-bottom: 1px solid #198754;
        }
        .node-RECEPCION {
            border-color: #6f42c1;
            background-color: #faf8ff;
        }
        .node-RECEPCION .wf-node-header {
            background-color: #e9dffc;
            color: #5a32a3;
            border-bottom: 1px solid #6f42c1;
        }
        .node-AVANCE {
            border-color: #0dcaf0;
            background-color: #f0fcff;
        }
        .node-AVANCE .wf-node-header {
            background-color: #cff4fc;
            color: #087990;
            border-bottom: 1px solid #0dcaf0;
        }
        .node-FISCALIZACION {
            border-color: #6c757d;
            background-color: #f8f9fa;
        }
        .node-FISCALIZACION .wf-node-header {
            background-color: #e9ecef;
            color: #495057;
            border-bottom: 1px solid #6c757d;
        }
        .text-purple { color: #6f42c1 !important; }
        
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
                    <option value="<?php echo intval($flow['Wfm_Fam_Cod']); ?>"><?php echo htmlspecialchars($wf_mgr->etiquetaFlujoListado($flow), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php } ?>
            </select>
            <button class="btn btn-sm btn-info text-white fw-bold" onclick="cargarFlujo()"><i class="bi bi-folder-2-open"></i> Abrir</button>
            <button class="btn btn-sm btn-primary fw-bold" onclick="abrirModalNuevoFlujo()"><i class="bi bi-plus-lg"></i> Nuevo</button>
            <button class="btn btn-sm btn-secondary fw-bold" onclick="abrirModalDuplicarFlujo()"><i class="bi bi-copy"></i> Duplicar</button>
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
            <?php if (!in_array('DECISION', $wf_builder_nodos_ocultos, true)) { ?>
            <div class="toolbox-item" draggable="true" data-type="DECISION">
                <i class="bi bi-shuffle text-warning"></i> Decisión
            </div>
            <?php } ?>
            <div class="toolbox-item" draggable="true" data-type="RECEPCION">
                <i class="bi bi-box-seam text-purple"></i> Recepción
            </div>
            <?php if (!in_array('FACTURA', $wf_builder_nodos_ocultos, true)) { ?>
            <div class="toolbox-item" draggable="true" data-type="FACTURA">
                <i class="bi bi-receipt text-warning"></i> Factura
            </div>
            <?php } ?>
            <?php if (!in_array('NOTIFICACION', $wf_builder_nodos_ocultos, true)) { ?>
            <div class="toolbox-item" draggable="true" data-type="NOTIFICACION">
                <i class="bi bi-envelope text-dark"></i> Notificación
            </div>
            <?php } ?>
            <div class="toolbox-item" draggable="true" data-type="TAREA">
                <i class="bi bi-card-checklist text-success"></i> Tarea
            </div>
            <div class="toolbox-item" draggable="true" data-type="AVANCE">
                <i class="bi bi-folder-plus text-info"></i> Avance/Facturas
            </div>
            <div class="toolbox-item" draggable="true" data-type="FISCALIZACION">
                <i class="bi bi-shield-check text-secondary"></i> Fiscalización
            </div>
            <div class="toolbox-item" draggable="true" data-type="FIN">
                <i class="bi bi-stop-circle text-danger"></i> Fin
            </div>
        </div>

        <!-- Canvas -->
        <div class="canvas-area" id="canvas">
            <div class="canvas-surface" id="canvasSurface">
                <svg class="svg-canvas" id="svgCanvas"></svg>
            </div>
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
                        <?php foreach ($departamentos as $dep) {
                            $cant_dep_usu = isset($dep['Cant_Usuarios']) ? intval($dep['Cant_Usuarios']) : 0;
                            $dep_label = $dep['Dep_Des'] . ($cant_dep_usu > 0 ? (' (' . $cant_dep_usu . ' usuario' . ($cant_dep_usu === 1 ? '' : 's') . ')') : ' (sin usuarios WF)');
                            ?>
                            <option value="<?php echo intval($dep['Dep_Cod']); ?>"><?php echo htmlspecialchars($dep_label, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php } ?>
                    </select>
                    <button class="btn btn-outline-secondary" type="button" id="btnManageDepUsers" onclick="abrirGestionUsuarios()" title="Gestionar Usuarios de este Departamento" style="display: none;">
                        <i class="bi bi-people-fill"></i>
                    </button>
                </div>
                <small class="text-muted d-block mt-1" id="lblNodeDepHint">Seleccione un departamento para habilitar la asignacion de usuarios.</small>
            </div>
            <div class="mb-3 sec-responsabilidad sec-asignacion-usuarios" style="display: none;">
                <label class="form-label d-block">Asignacion de Usuarios</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="userAsigType" id="asigTodos" value="TODOS" checked onchange="toggleAsigType(this.value)" disabled>
                    <label class="form-check-label" for="asigTodos">Todos los del depto</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="userAsigType" id="asigEspecificos" value="ESPECIFICOS" onchange="toggleAsigType(this.value)" disabled>
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
                        <option value="<?php echo $perf['Per_Cod']; ?>"><?php echo htmlspecialchars($perf['Per_Des'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="mb-3 sec-sla">
                <label class="form-label">Tiempo Límite (Días SLA)</label>
                <input type="number" id="nodeSla" class="form-control form-control-sm" min="0">
            </div>
            <div class="mb-3 sec-notificaciones" style="display: none;">
                <label class="form-label d-block fw-semibold" id="lblNodeNotTitle">Al completar esta etapa, notificar al siguiente responsable</label>
                <p class="text-muted small mb-2" id="lblNodeNotHelp">Se envía WhatsApp o correo a quien debe atender la siguiente tarea. En la primera etapa humana, también aplica al enviar la solicitud.</p>
                <div class="form-check mb-1">
                    <input type="checkbox" id="nodeNotWa" class="form-check-input">
                    <label class="form-check-label" for="nodeNotWa"><i class="bi bi-whatsapp text-success"></i> <span class="node-not-wa-label">WhatsApp</span></label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" id="nodeNotEm" class="form-check-input">
                    <label class="form-check-label" for="nodeNotEm"><i class="bi bi-envelope text-primary"></i> <span class="node-not-em-label">Correo electrónico</span></label>
                </div>
            </div>
            <div class="mb-3 form-check sec-checks">
                <input type="checkbox" id="nodeComObl" class="form-check-input">
                <label class="form-check-label">Comentario obligatorio</label>
            </div>
            <div class="mb-3 form-check sec-checks">
                <input type="checkbox" id="nodeAdjObl" class="form-check-input">
                <label class="form-check-label">Archivos adjuntos obligatorios</label>
            </div>
            <div class="mb-3 form-check sec-checks sec-cot-edit">
                <input type="checkbox" id="nodeCotEdit" class="form-check-input">
                <label class="form-check-label" for="nodeCotEdit">Permitir cargar cotizaciones en esta etapa</label>
                <p class="text-muted small mb-0">El responsable de esta etapa podrá abrir la solicitud y adjuntar proformas/cotizaciones.</p>
            </div>
            <div class="mb-3 form-check sec-checks sec-cot-edit sec-cot-sel">
                <input type="checkbox" id="nodeCotSel" class="form-check-input">
                <label class="form-check-label" for="nodeCotSel">Permitir seleccionar cotización ganadora</label>
                <p class="text-muted small mb-0">Independiente de quien carga las cotizaciones: el responsable de esta etapa podrá marcar cuál es la ganadora.</p>
            </div>
            <div class="mb-3 form-check sec-inicio-crear" style="display: none;">
                <input type="checkbox" id="nodeCreSol" class="form-check-input">
                <label class="form-check-label" for="nodeCreSol">Permitir modificar solicitud</label>
                <p class="text-muted small mb-0">Los usuarios asignados a este nodo Inicio podrán modificar solicitudes de los tipos ligados a este flujo.</p>
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
                        <input type="text" id="modalFlowName" class="form-control" placeholder="Ej. APROBACIÓN DE COMPRAS DE TECNOLOGÍA" required style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase();" autocomplete="off">
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

    <div class="modal fade" id="modalConnectionCondition" tabindex="-1" role="dialog" aria-labelledby="modalConnectionConditionLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title fw-bold" id="modalConnectionConditionLabel"><i class="bi bi-shuffle"></i> Condición de Decisión</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="condOrigen">
                    <input type="hidden" id="condDestino">
                    <p class="text-muted small">Configure la regla para esta flecha. Si marca rama por defecto, se usará cuando ninguna otra condición se cumpla.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold" for="condComentario">Comentario</label>
                        <input type="text" id="condComentario" class="form-control" maxlength="120" placeholder="Ej. Mayor, Menor (único por cada flecha/rama)" autocomplete="off">
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="condDefault">
                        <label class="form-check-label" for="condDefault">Rama por defecto / caso contrario</label>
                    </div>
                    <div id="condFields">
                        <div class="mb-3">
                            <label class="form-label fw-bold" for="condCampo">Campo</label>
                            <select id="condCampo" class="form-control">
                                <option value="Sol_Val_Est">Valor estimado de la solicitud</option>
                                <option value="Sol_Tiempo_Est">Días estimados del proyecto</option>
                                <option value="Sol_Pri">Prioridad</option>
                                <option value="Trq_Cod">Tipo de requerimiento</option>
                                <option value="Dep_Cod">Departamento solicitante</option>
                                <option value="Sol_Cod">Código de solicitud</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold" for="condOperador">Operador</label>
                            <select id="condOperador" class="form-control">
                                <option value=">">Mayor que (&gt;)</option>
                                <option value="<">Menor que (&lt;)</option>
                                <option value="=">Igual (=)</option>
                                <option value=">=">Mayor o igual (&gt;=)</option>
                                <option value="<=">Menor o igual (&lt;=)</option>
                                <option value="!=">Diferente (!=)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold" for="condValor">Valor</label>
                            <input type="text" id="condValor" class="form-control" placeholder="Ej. 5000, ALTA, 30">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" onclick="limpiarCondicionConexion()"><i class="bi bi-trash"></i> Limpiar</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="guardarCondicionConexion()"><i class="bi bi-check-lg"></i> Guardar condición</button>
                </div>
            </div>
        </div>
    </div>

    <?php
    exit;
}

// Cargar catálogos para configuración de nodos
$departamentos = $wf_mgr->listarDepartamentosDisenador($Ses_Emp_Cod);
$perfiles = $obBD_con1->getArrayConsultaSql("SELECT Per_Cod, Per_Des FROM perfiles WHERE Emp_Cod = $Ses_Emp_Cod AND Per_Est = 'A' ORDER BY Per_Des;", $obBD_conexion);
$wf_mgr->ensureVersioningSchema();
$flujos_existentes = $wf_mgr->listarFlujosDisenador($Ses_Emp_Cod);
if (function_exists('utf8_encode_deep')) {
    if ($departamentos) {
        utf8_encode_deep($departamentos);
    }
    if ($perfiles) {
        utf8_encode_deep($perfiles);
    }
    if ($flujos_existentes) {
        utf8_encode_deep($flujos_existentes);
    }
}
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
        .toolbox-item > .bi {
            font-size: 1.45rem;
            line-height: 1;
            flex-shrink: 0;
            width: 1.5rem;
            text-align: center;
        }
        .wf-node-tip {
            position: fixed;
            z-index: 10050;
            max-width: 280px;
            padding: 8px 10px;
            border-radius: 6px;
            background: #1e293b;
            color: #f8fafc;
            font-size: 12px;
            line-height: 1.35;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.28);
            pointer-events: none;
            opacity: 0;
            visibility: hidden;
            transition: opacity .12s ease;
            white-space: normal;
            word-break: break-word;
        }
        .wf-node-tip.is-visible {
            opacity: 1;
            visibility: visible;
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
        .canvas-surface {
            position: relative;
            min-width: 100%;
            min-height: 100%;
            box-sizing: border-box;
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
        .wf-node.is-selected {
            border-width: 3px;
            border-color: #f59e0b !important;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.4), 0 8px 18px rgba(15, 23, 42, 0.18);
            z-index: 40;
        }
        .wf-node.node-INICIO.is-selected,
        .wf-node.node-FIN.is-selected {
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.45), 0 8px 18px rgba(15, 23, 42, 0.18);
        }
        .wf-node.node-INICIO,
        .wf-node.node-FIN {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            overflow: visible;
            padding: 10px 12px;
            user-select: none;
            box-sizing: border-box;
        }
        .wf-node.node-INICIO { border-color: #198754; background-color: #d1e7dd; color: #198754; }
        .wf-node.node-FIN { border-color: #dc3545; background-color: #f8d7da; color: #dc3545; }
        .wf-node.node-INICIO .wf-node-header,
        .wf-node.node-FIN .wf-node-header {
            background: transparent;
            border: none;
            border-bottom: none;
            border-radius: 0;
            padding: 4px 8px 0;
            flex: 0 0 auto;
            width: 100%;
            min-height: 0;
            flex-direction: column;
            gap: 2px;
            justify-content: center;
            align-items: center;
            position: relative;
            cursor: move;
        }
        .wf-node.node-INICIO .wf-node-header span,
        .wf-node.node-FIN .wf-node-header span {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            line-height: 1.2;
            width: 100%;
            font-weight: 700;
        }
        .wf-node.node-INICIO .wf-node-header .wf-node-type-icon,
        .wf-node.node-FIN .wf-node-header .wf-node-type-icon,
        .wf-node.node-INICIO .wf-node-header span > .bi,
        .wf-node.node-FIN .wf-node-header span > .bi {
            font-size: 28px;
            line-height: 1;
        }
        .wf-node.node-INICIO .wf-node-header .wf-node-title-label,
        .wf-node.node-FIN .wf-node-header .wf-node-title-label,
        .wf-node.node-INICIO .wf-node-header .wf-node-terminal-label,
        .wf-node.node-FIN .wf-node-header .wf-node-terminal-label {
            display: block;
            max-width: 120px;
            font-size: 12px;
            line-height: 1.25;
            white-space: normal;
            word-break: break-word;
            overflow: visible;
            text-overflow: clip;
        }
        .wf-node.node-INICIO .wf-node-header button .bi,
        .wf-node.node-FIN .wf-node-header button .bi {
            font-size: 12px;
            font-weight: 900;
            -webkit-text-stroke: 0.3px #dc3545;
        }
        .wf-node.node-INICIO .wf-node-body,
        .wf-node.node-FIN .wf-node-body {
            display: block;
            padding: 4px 10px 8px;
            text-align: center;
            font-size: 11px;
            line-height: 1.3;
            width: 100%;
            box-sizing: border-box;
        }
        .wf-node.node-INICIO .wf-node-body .wf-node-tipo-label,
        .wf-node.node-FIN .wf-node-body .wf-node-tipo-label {
            font-size: 11px;
            margin-bottom: 2px;
        }
        .wf-node.node-INICIO .wf-node-body .wf-node-desc,
        .wf-node.node-FIN .wf-node-body .wf-node-desc,
        .wf-node.node-INICIO .wf-node-body .wf-node-asig,
        .wf-node.node-FIN .wf-node-body .wf-node-asig {
            font-size: 10px;
            max-width: 120px;
            margin-left: auto;
            margin-right: auto;
        }
        .wf-node.node-INICIO .wf-node-header button,
        .wf-node.node-FIN .wf-node-header button {
            top: 8px;
            right: 14px;
        }
        .wf-node.node-APROBACION { border-color: #0d6efd; }
        .wf-node.node-DECISION { border-color: #fd7e14; }
        .wf-node.node-DECISION .wf-node-header { background-color: #fff3cd; color: #fd7e14; }
        .wf-node.node-NOTIFICACION { border-color: #495057; }
        .wf-node.node-NOTIFICACION .wf-node-header { background-color: #e9ecef; color: #212529; }
        .wf-node.node-RECEPCION {
            border-color: #6f42c1;
            background-color: #faf8ff;
        }
        .wf-node.node-RECEPCION .wf-node-header {
            background-color: #e9dffc;
            color: #5a32a3;
            border-bottom: 1px solid #6f42c1;
        }
        .wf-node.node-AVANCE {
            border-color: #0dcaf0;
            background-color: #f0fcff;
        }
        .wf-node.node-AVANCE .wf-node-header {
            background-color: #cff4fc;
            color: #087990;
            border-bottom: 1px solid #0dcaf0;
        }
        .wf-node.node-FISCALIZACION {
            border-color: #6c757d;
            background-color: #f8f9fa;
        }
        .wf-node.node-FISCALIZACION .wf-node-header {
            background-color: #e9ecef;
            color: #495057;
            border-bottom: 1px solid #6c757d;
        }
        .wf-node.node-FACTURA {
            border-color: #ffc107;
            background-color: #fffbeb;
        }
        .wf-node.node-FACTURA .wf-node-header {
            background-color: #fff3cd;
            color: #856404;
            border-bottom: 1px solid #ffc107;
        }
        .wf-node.node-TAREA {
            border-color: #198754;
            background-color: #f4fbf6;
        }
        .wf-node.node-TAREA .wf-node-header {
            background-color: #d1e7dd;
            color: #198754;
            border-bottom: 1px solid #198754;
        }
        .text-purple { color: #6f42c1 !important; }
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
            position: relative;
        }
        .wf-node-header > button {
            position: absolute;
            top: 3px;
            right: 3px;
            width: 18px;
            height: 18px;
            min-width: 18px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            border: none;
            border-radius: 50%;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.12);
            z-index: 3;
            cursor: pointer;
            line-height: 1;
        }
        .wf-node-header > button .bi {
            font-size: 15px;
            font-weight: 900;
            color: #dc3545 !important;
            line-height: 1;
            -webkit-text-stroke: 0.35px #dc3545;
            paint-order: stroke fill;
        }
        .wf-node-header > button:hover .bi {
            color: #b02a37 !important;
            -webkit-text-stroke: 0.35px #b02a37;
        }
        .wf-node-header > span {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            min-width: 0;
            flex: 1;
            text-align: center;
        }
        .wf-node-header .wf-node-type-icon {
            font-size: 2rem;
            line-height: 1;
            flex-shrink: 0;
        }
        .wf-node-header .wf-node-title-label {
            display: block;
            max-width: 100%;
            font-size: 11px;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .wf-node-body .wf-node-tipo-label {
            display: block;
            font-size: 10px;
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 2px;
        }
        .wf-node-body {
            padding: 8px 10px;
            font-size: 11px;
            color: #6c757d;
        }
        .wf-node-body .wf-node-desc {
            display: block;
            margin-top: 4px;
            font-size: 11px;
            line-height: 1.35;
            color: #495057;
            word-break: break-word;
            white-space: pre-wrap;
        }
        .wf-node-body .wf-node-asig {
            display: block;
            margin-top: 4px;
            font-size: 10px;
            line-height: 1.35;
            color: #0d6efd;
            word-break: break-word;
        }
        .node-port {
            width: 12px;
            height: 12px;
            background-color: #adb5bd;
            border-radius: 50%;
            position: absolute;
            border: 2px solid #ffffff;
            cursor: crosshair;
            z-index: 5;
        }
        .node-port-left { left: -6px; top: calc(50% - 6px); }
        .node-port-right { right: -6px; top: calc(50% - 6px); }
        .node-port-top { top: -6px; left: calc(50% - 6px); }
        .node-port-bottom { bottom: -6px; left: calc(50% - 6px); }
        .node-port-in { left: -6px; top: calc(50% - 6px); }
        .node-port-out { right: -6px; top: calc(50% - 6px); }
        .node-port:hover { background-color: #0d6efd; transform: scale(1.25); }
        .svg-canvas {
            position: absolute;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: 1;
        }
        .svg-canvas path,
        .svg-canvas .wf-conn-group,
        .svg-canvas .wf-conn-delete {
            pointer-events: auto;
            cursor: pointer;
        }
        .wf-conn-group .wf-conn-delete {
            opacity: 0.85;
        }
        .wf-conn-group:hover .wf-conn-line {
            stroke: #0d6efd !important;
            stroke-width: 3.5px;
        }
        .wf-conn-group:hover .wf-conn-delete,
        .wf-conn-group.is-selected .wf-conn-delete {
            opacity: 1;
        }
        .wf-conn-delete:hover circle {
            fill: #b02a37;
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
                        <option value="<?php echo intval($flow['Wfm_Fam_Cod']); ?>"><?php echo htmlspecialchars($wf_mgr->etiquetaFlujoListado($flow), ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php } ?>
                </select>
                <button class="btn btn-sm btn-info text-white fw-bold" onclick="cargarFlujo()"><i class="bi bi-folder-2-open"></i> Abrir</button>
                <button class="btn btn-sm btn-primary fw-bold" onclick="abrirModalNuevoFlujo()"><i class="bi bi-plus-lg"></i> Nuevo</button>
                <button class="btn btn-sm btn-secondary fw-bold" onclick="abrirModalDuplicarFlujo()"><i class="bi bi-copy"></i> Duplicar</button>
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
            <?php if (!in_array('DECISION', $wf_builder_nodos_ocultos, true)) { ?>
            <div class="toolbox-item" draggable="true" data-type="DECISION">
                <i class="bi bi-shuffle text-warning"></i> Decisión
            </div>
            <?php } ?>
            <div class="toolbox-item" draggable="true" data-type="RECEPCION">
                <i class="bi bi-box-seam text-purple"></i> Recepción
            </div>
            <?php if (!in_array('FACTURA', $wf_builder_nodos_ocultos, true)) { ?>
            <div class="toolbox-item" draggable="true" data-type="FACTURA">
                <i class="bi bi-receipt text-warning"></i> Factura
            </div>
            <?php } ?>
            <?php if (!in_array('NOTIFICACION', $wf_builder_nodos_ocultos, true)) { ?>
            <div class="toolbox-item" draggable="true" data-type="NOTIFICACION">
                <i class="bi bi-envelope text-dark"></i> Notificación
            </div>
            <?php } ?>
            <div class="toolbox-item" draggable="true" data-type="TAREA">
                <i class="bi bi-card-checklist text-success"></i> Tarea
            </div>
            <div class="toolbox-item" draggable="true" data-type="AVANCE">
                <i class="bi bi-folder-plus text-info"></i> Avance/Facturas
            </div>
            <div class="toolbox-item" draggable="true" data-type="FISCALIZACION">
                <i class="bi bi-shield-check text-secondary"></i> Fiscalización
            </div>
            <div class="toolbox-item" draggable="true" data-type="FIN">
                <i class="bi bi-stop-circle text-danger"></i> Fin
            </div>
        </div>

        <!-- Canvas -->
        <div class="canvas-area" id="canvas">
            <div class="canvas-surface" id="canvasSurface">
                <svg class="svg-canvas" id="svgCanvas"></svg>
            </div>
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
                        <?php foreach ($departamentos as $dep) {
                            $cant_dep_usu = isset($dep['Cant_Usuarios']) ? intval($dep['Cant_Usuarios']) : 0;
                            $dep_label = $dep['Dep_Des'] . ($cant_dep_usu > 0 ? (' (' . $cant_dep_usu . ' usuario' . ($cant_dep_usu === 1 ? '' : 's') . ')') : ' (sin usuarios WF)');
                            ?>
                            <option value="<?php echo intval($dep['Dep_Cod']); ?>"><?php echo htmlspecialchars($dep_label, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php } ?>
                    </select>
                    <button class="btn btn-outline-secondary" type="button" id="btnManageDepUsers" onclick="abrirGestionUsuarios()" title="Gestionar Usuarios de este Departamento" style="display: none;">
                        <i class="bi bi-people-fill"></i>
                    </button>
                </div>
                <small class="text-muted d-block mt-1" id="lblNodeDepHint">Seleccione un departamento para habilitar la asignacion de usuarios.</small>
            </div>
            <div class="mb-3 sec-responsabilidad sec-asignacion-usuarios" style="display: none;">
                <label class="form-label d-block">Asignacion de Usuarios</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="userAsigType" id="asigTodos" value="TODOS" checked onchange="toggleAsigType(this.value)" disabled>
                    <label class="form-check-label" for="asigTodos">Todos los del depto</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="userAsigType" id="asigEspecificos" value="ESPECIFICOS" onchange="toggleAsigType(this.value)" disabled>
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
                        <option value="<?php echo $perf['Per_Cod']; ?>"><?php echo htmlspecialchars($perf['Per_Des'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="mb-3 sec-sla">
                <label class="form-label">Tiempo Límite (Días SLA)</label>
                <input type="number" id="nodeSla" class="form-control form-control-sm" min="0">
            </div>
            <div class="mb-3 sec-notificaciones" style="display: none;">
                <label class="form-label d-block fw-semibold" id="lblNodeNotTitle">Al completar esta etapa, notificar al siguiente responsable</label>
                <p class="text-muted small mb-2" id="lblNodeNotHelp">Se envía WhatsApp o correo a quien debe atender la siguiente tarea. En la primera etapa humana, también aplica al enviar la solicitud.</p>
                <div class="form-check mb-1">
                    <input type="checkbox" id="nodeNotWa" class="form-check-input">
                    <label class="form-check-label" for="nodeNotWa"><i class="bi bi-whatsapp text-success"></i> <span class="node-not-wa-label">WhatsApp</span></label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" id="nodeNotEm" class="form-check-input">
                    <label class="form-check-label" for="nodeNotEm"><i class="bi bi-envelope text-primary"></i> <span class="node-not-em-label">Correo electrónico</span></label>
                </div>
            </div>
            <div class="mb-3 form-check sec-checks">
                <input type="checkbox" id="nodeComObl" class="form-check-input">
                <label class="form-check-label">Comentario obligatorio</label>
            </div>
            <div class="mb-3 form-check sec-checks">
                <input type="checkbox" id="nodeAdjObl" class="form-check-input">
                <label class="form-check-label">Archivos adjuntos obligatorios</label>
            </div>
            <div class="mb-3 form-check sec-checks sec-cot-edit">
                <input type="checkbox" id="nodeCotEdit" class="form-check-input">
                <label class="form-check-label" for="nodeCotEdit">Permitir cargar cotizaciones en esta etapa</label>
                <p class="text-muted small mb-0">El responsable de esta etapa podrá abrir la solicitud y adjuntar proformas/cotizaciones.</p>
            </div>
            <div class="mb-3 form-check sec-checks sec-cot-edit sec-cot-sel">
                <input type="checkbox" id="nodeCotSel" class="form-check-input">
                <label class="form-check-label" for="nodeCotSel">Permitir seleccionar cotización ganadora</label>
                <p class="text-muted small mb-0">Independiente de quien carga las cotizaciones: el responsable de esta etapa podrá marcar cuál es la ganadora.</p>
            </div>
            <div class="mb-3 form-check sec-inicio-crear" style="display: none;">
                <input type="checkbox" id="nodeCreSol" class="form-check-input">
                <label class="form-check-label" for="nodeCreSol">Permitir modificar solicitud</label>
                <p class="text-muted small mb-0">Los usuarios asignados a este nodo Inicio podrán modificar solicitudes de los tipos ligados a este flujo.</p>
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
                        <input type="text" id="modalFlowName" class="form-control" placeholder="Ej. APROBACIÓN DE COMPRAS DE TECNOLOGÍA" required style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase();" autocomplete="off">
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

    <div class="modal fade" id="modalConnectionCondition" tabindex="-1" role="dialog" aria-labelledby="modalConnectionConditionLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title fw-bold" id="modalConnectionConditionLabel"><i class="bi bi-shuffle"></i> Condición de Decisión</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="condOrigen">
                    <input type="hidden" id="condDestino">
                    <p class="text-muted small">Configure la regla para esta flecha. Si marca rama por defecto, se usará cuando ninguna otra condición se cumpla.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold" for="condComentario">Comentario</label>
                        <input type="text" id="condComentario" class="form-control" maxlength="120" placeholder="Ej. Mayor, Menor (único por cada flecha/rama)" autocomplete="off">
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="condDefault">
                        <label class="form-check-label" for="condDefault">Rama por defecto / caso contrario</label>
                    </div>
                    <div id="condFields">
                        <div class="mb-3">
                            <label class="form-label fw-bold" for="condCampo">Campo</label>
                            <select id="condCampo" class="form-control">
                                <option value="Sol_Val_Est">Valor estimado de la solicitud</option>
                                <option value="Sol_Tiempo_Est">Días estimados del proyecto</option>
                                <option value="Sol_Pri">Prioridad</option>
                                <option value="Trq_Cod">Tipo de requerimiento</option>
                                <option value="Dep_Cod">Departamento solicitante</option>
                                <option value="Sol_Cod">Código de solicitud</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold" for="condOperador">Operador</label>
                            <select id="condOperador" class="form-control">
                                <option value=">">Mayor que (&gt;)</option>
                                <option value="<">Menor que (&lt;)</option>
                                <option value="=">Igual (=)</option>
                                <option value=">=">Mayor o igual (&gt;=)</option>
                                <option value="<=">Menor o igual (&lt;=)</option>
                                <option value="!=">Diferente (!=)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold" for="condValor">Valor</label>
                            <input type="text" id="condValor" class="form-control" placeholder="Ej. 5000, ALTA, 30">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" onclick="limpiarCondicionConexion()"><i class="bi bi-trash"></i> Limpiar</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="guardarCondicionConexion()"><i class="bi bi-check-lg"></i> Guardar condición</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../VALIDACIONES/wf_builder.js?v=52"></script>
</body>
</html>
