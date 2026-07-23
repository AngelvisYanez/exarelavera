<?php
/**
 * EXA Adquisiciones - CRUD de Departamentos y Asignaci�n de Usuarios
 * Permite crear, editar, activar/desactivar departamentos y asignarles usuarios.
 * @author Oz <oz-agent@warp.dev>
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/wf_manager_log.php');

$db_param = isset($Ses_Dis_Dis) ? $Ses_Dis_Dis : (isset($_SESSION['Ses_Dis_Dis']) ? $_SESSION['Ses_Dis_Dis'] : (isset($Ses_Dat_Dis) ? $Ses_Dat_Dis : (isset($_SESSION['Ses_Dat_Dis']) ? $_SESSION['Ses_Dat_Dis'] : null)));
$obBD_conexion = new Class_Log_Conexion_Global($db_param);
$obBD_con1 = new MysqlDatos($obBD_conexion);
$wf_mgr = new wf_manager_log($db_param);

// Definir variables AJAX si se solicita directamente
$ajax_get_deptos = isset($_GET['ajax_get_deptos']) ? $_GET['ajax_get_deptos'] : (isset($ajax_get_deptos) ? $ajax_get_deptos : null);
$ajax_get_depto_req = isset($_GET['ajax_get_depto_req']) ? $_GET['ajax_get_depto_req'] : (isset($ajax_get_depto_req) ? $ajax_get_depto_req : null);
$ajax_save_depto_req = isset($_GET['ajax_save_depto_req']) ? $_GET['ajax_save_depto_req'] : (isset($_POST['ajax_save_depto_req']) ? $_POST['ajax_save_depto_req'] : (isset($ajax_save_depto_req) ? $ajax_save_depto_req : null));
$ajax_toggle_depto_req = isset($_GET['ajax_toggle_depto_req']) ? $_GET['ajax_toggle_depto_req'] : (isset($_POST['ajax_toggle_depto_req']) ? $_POST['ajax_toggle_depto_req'] : (isset($ajax_toggle_depto_req) ? $ajax_toggle_depto_req : null));
$ajax_get_depto_users = isset($_GET['ajax_get_depto_users']) ? $_GET['ajax_get_depto_users'] : (isset($ajax_get_depto_users) ? $ajax_get_depto_users : null);
$ajax_save_depto_users = isset($_POST['ajax_save_depto_users']) ? $_POST['ajax_save_depto_users'] : (isset($ajax_save_depto_users) ? $ajax_save_depto_users : null);

// Verificar acceso a la ventana 'configuracion'
if (!$wf_mgr->verificarAccesoVentana('configuracion')) {
    if (isset($ajax_save_depto_req) || isset($ajax_toggle_depto_req) || isset($ajax_get_depto_req) || isset($ajax_get_depto_users) || isset($ajax_save_depto_users)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Acceso denegado. No tiene permisos para realizar esta acci�n.'));
        exit;
    } else {
        echo "<div class='alert alert-danger m-3'>Acceso denegado. No tiene permisos para ver esta ventana.</div>";
        exit;
    }
}

// Redirecci�n segura para navegaci�n directa del navegador (no AJAX)
$request_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
$is_ajax_get = isset($_GET['ajax_get_deptos']) || isset($_GET['ajax_get_depto_req']) || isset($_GET['ajax_get_depto_users']);
if ($request_method === 'GET' && !$is_ajax_get) {
    header("Location: adq_configuracion.php?tab=departamentos");
    exit;
}

// --- AJAX: Guardar Departamento (Crear o Editar) ---
if (isset($ajax_save_depto_req)) {
    $dep_cod = !empty($_POST['Dep_Cod']) ? intval($_POST['Dep_Cod']) : null;
    $dep_des = isset($_POST['Dep_Des']) ? strtoupper(trim($_POST['Dep_Des'])) : '';

    if ($dep_des === '') {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'El nombre del departamento es obligatorio.'));
        exit;
    }

    try {
        $emp_id = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : (isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0);
        $wf_mgr->guardarDepartamentoWorkflow($emp_id, $dep_cod, $dep_des);
        $obBD_con1->echoJson(array('success' => true));
    } catch (Exception $e) {
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
    exit;
}

// --- AJAX: Cambiar estado en Workflow ---
if (isset($ajax_toggle_depto_req)) {
    $dep_cod = intval($_POST['Dep_Cod']);
    $estado_actual = isset($_POST['Wfd_Est']) ? $_POST['Wfd_Est'] : (isset($_POST['Dep_Est']) ? $_POST['Dep_Est'] : 'A');
    $nuevo_est = ($estado_actual === 'A') ? 'I' : 'A';
    $emp_id = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : (isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0);
    try {
        $obBD_con1->grabarv_registros("UPDATE wf_departamentos SET Wde_Est = '$nuevo_est' WHERE Wde_Cod = $dep_cod AND Emp_Cod = $emp_id;", $obBD_conexion);
        $obBD_con1->echoJson(array('success' => true, 'nuevo_estado' => $nuevo_est, 'wfd_est' => $nuevo_est));
    } catch (Exception $e) {
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
    exit;
}

// --- AJAX: Obtener datos de un departamento para editar ---
if (isset($ajax_get_depto_req)) {
    $dep_cod = intval($_GET['Dep_Cod']);
    $emp_id = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : (isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0);
    $row = $obBD_con1->getRowConsultaSql("
        SELECT w.Wde_Cod AS Dep_Cod, w.Wde_Des AS Dep_Des, w.Wde_Est AS Dep_Est
        FROM wf_departamentos w
        WHERE w.Wde_Cod = $dep_cod AND w.Emp_Cod = $emp_id;", $obBD_conexion);
    if (!empty($row) && function_exists('utf8_encode_deep')) {
        utf8_encode_deep($row);
    }
    $obBD_con1->echoJson(array('success' => true, 'data' => $row));
    exit;
}

// --- AJAX: Obtener usuarios de la empresa con flag de asignacion al departamento ---
if (isset($ajax_get_depto_users) || isset($_GET['ajax_get_depto_users'])) {
    $wde_cod = intval($_GET['dep_cod']);
    $emp_id = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : (isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0);
    if (!$wf_mgr->validarWdeCodWorkflow($wde_cod, $emp_id)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Departamento de workflow no valido.'));
        exit;
    }
    try {
        $wf_mgr->ensureWfDepartamentoUsuariosWdeCod($emp_id);
        $filtro_du = $wf_mgr->sqlDuPorWdeCod($wde_cod, 'du2');
        $usuarios = $obBD_con1->getArrayConsultaSql("
            SELECT base.Usu_Cod,
                   base.Usuario_Nom,
                   IF(EXISTS (
                       SELECT 1
                       FROM usuarios ux
                       INNER JOIN sucursal sx ON sx.Suc_Cod = ux.Suc_Cod
                       INNER JOIN wf_departamento_usuarios du2 ON du2.Usu_Cod = ux.Usu_Cod
                       WHERE sx.Emp_Cod = $emp_id AND ux.Usu_Ced = base.Usu_Ced
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
                WHERE s.Emp_Cod = $emp_id AND u.Usu_Est = 'A' AND u.Usu_Wf = 'S'
                GROUP BY u.Usu_Ced, p.Prs_Nom, p.Prs_Ape
            ) base
            ORDER BY asignado DESC, Usuario_Nom;", $obBD_conexion);
        
        if ($usuarios === false || $usuarios === null) {
            $usuarios = array();
        }
        if (function_exists('utf8_encode_deep')) {
            utf8_encode_deep($usuarios);
        }
        $obBD_con1->echoJson(array('success' => true, 'usuarios' => $usuarios));
    } catch (Exception $e) {
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
    exit;
}

// --- AJAX: Guardar asignaciones de usuarios a un departamento ---
if (isset($ajax_save_depto_users)) {
    $wde_cod = intval($_POST['dep_cod']);
    $usuarios_ids = array();
    if (isset($_POST['usuarios'])) {
        $usuarios_ids = is_array($_POST['usuarios']) ? $_POST['usuarios'] : array($_POST['usuarios']);
    }
    $emp_id = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : (isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0);
    try {
        $result = $wf_mgr->guardarUsuariosDepartamentoWorkflow($wde_cod, $emp_id, $usuarios_ids);
        $obBD_con1->echoJson(array('success' => true, 'data' => $result));
    } catch (Exception $e) {
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
    exit;
}

// Cargar datos para la vista
$emp_id = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : (isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0);
$deptos = $obBD_con1->getArrayConsultaSql("
    SELECT d.Wde_Cod AS Dep_Cod,
           d.Wde_Des AS Dep_Des,
           d.Wde_Est AS Dep_Est, d.Wde_Est AS Wfd_Est,
           (SELECT COUNT(DISTINCT u.Usu_Ced)
            FROM wf_departamento_usuarios du
            INNER JOIN usuarios u ON u.Usu_Cod = du.Usu_Cod
            INNER JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod
            WHERE s.Emp_Cod = $emp_id AND u.Usu_Wf = 'S'
              AND (du.Wde_Cod = d.Wde_Cod OR du.Dep_Cod = d.Wde_Cod)) as Cant_Usuarios
    FROM wf_departamentos d
    WHERE d.Emp_Cod = $emp_id
    ORDER BY d.Wde_Cod DESC;", $obBD_conexion);
if ($deptos === false || $deptos === null) {
    $deptos = array();
}

if (isset($ajax_get_deptos)) {
    if (function_exists('utf8_encode_deep')) {
        utf8_encode_deep($deptos);
    }
    ?>
    <style>
        #tblDeptos tr.wf-depto-inactivo {
            background-color: #fee2e2 !important;
            color: #991b1b !important;
        }
        #tblDeptos tr.wf-depto-inactivo td {
            border-color: #fecaca !important;
        }
        #tblDeptos tr.wf-depto-inactivo .text-primary {
            color: #b91c1c !important;
        }
    </style>
    <div class="p-1">
        <div class="adq-tipos-toolbar">
            <h4 class="fw-bold text-primary adq-tipos-toolbar-title"><i class="bi bi-building"></i> Gesti&oacute;n de Departamentos</h4>
            <div class="adq-tipos-toolbar-actions">
                <span class="text-muted small adq-tipos-toolbar-count"><?php echo count($deptos); ?> departamento(s)</span>
                <button type="button" class="btn btn-sm btn-success" onclick="abrirFormularioDepto()"><i class="bi bi-plus-lg"></i> Nuevo Departamento</button>
            </div>
        </div>

        <!-- Listado de Departamentos -->
        <div class="exa-adq-table-wrap">
            <table class="table table-bordered exa-adq-table" id="tblDeptos">
                <thead>
                    <tr class="text-center font-monospace" style="font-size: 13px;">
                        <th style="width: 80px;">ID</th>
                        <th>Nombre del Departamento</th>
                        <th style="width: 150px;">Usuarios Asignados</th>
                        <th style="width: 140px;">Estado</th>
                        <th style="width: 180px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($deptos)) { ?>
                        <tr class="text-center"><td colspan="5" class="text-muted py-3">No hay departamentos registrados. Use <strong>Nuevo Departamento</strong> para crear uno.</td></tr>
                    <?php } else { 
                        foreach ($deptos as $d) { ?>
                            <tr class="text-center <?php echo $d['Wfd_Est'] === 'I' ? 'wf-depto-inactivo' : ''; ?>" id="row_dep_<?php echo $d['Dep_Cod']; ?>" data-wfd-est="<?php echo $d['Wfd_Est']; ?>">
                                <td class="fw-bold"><?php echo $d['Dep_Cod']; ?></td>
                                <td class="text-start"><?php echo htmlspecialchars($d['Dep_Des'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="fw-bold text-primary">
                                    <span class="badge bg-info text-dark" id="cant_usu_<?php echo $d['Dep_Cod']; ?>">
                                        <?php echo $d['Cant_Usuarios']; ?> usuarios
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $d['Wfd_Est'] === 'A' ? 'success' : 'danger'; ?>" id="badge_dep_<?php echo $d['Dep_Cod']; ?>">
                                        <?php echo $d['Wfd_Est'] === 'A' ? 'Activo' : 'Desactivado'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-xs">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editarDepto(<?php echo $d['Dep_Cod']; ?>)" title="Editar Nombre"><i class="bi bi-pencil"></i></button>
                                        <button type="button" class="btn btn-sm btn-outline-info btn-abrir-depto-usuarios" data-wde-cod="<?php echo intval($d['Dep_Cod']); ?>" data-dep-nom="<?php echo htmlspecialchars($d['Dep_Des'], ENT_QUOTES, 'UTF-8'); ?>" title="Asignar Usuarios"><i class="bi bi-people"></i></button>
                                        <button type="button" class="btn btn-sm btn-outline-<?php echo $d['Wfd_Est'] === 'A' ? 'danger' : 'success'; ?>" id="btn_toggle_dep_<?php echo $d['Dep_Cod']; ?>" onclick="toggleEstadoDepto(<?php echo $d['Dep_Cod']; ?>)" title="<?php echo $d['Wfd_Est'] === 'A' ? 'Desactivar' : 'Activar'; ?>">
                                            <i class="bi bi-power"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php } 
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    exit;
}
?>
