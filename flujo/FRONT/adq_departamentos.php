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
    $dep_des = mysqli_real_escape_string($obBD_conexion->conexion, $_POST['Dep_Des']);
    
    if (empty($dep_des)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'El nombre del departamento es obligatorio.'));
        exit;
    }

    try {
        $emp_id = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : (isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0);
        if ($dep_cod) {
            // Actualizar en wf_departamentos
            $sql = "UPDATE wf_departamentos SET Wde_Des = '$dep_des' WHERE Wde_Cod = $dep_cod AND Emp_Cod = $emp_id;";
        } else {
            // Crear en wf_departamentos
            $sql = "INSERT INTO wf_departamentos (Emp_Cod, Wde_Des, Wde_Est) 
                    VALUES ($emp_id, '$dep_des', 'A');";
        }
        $obBD_con1->grabarv_registros($sql, $obBD_conexion);
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
    $row = $obBD_con1->getRowConsultaSql("SELECT Wde_Cod AS Dep_Cod, Wde_Des AS Dep_Des, Wde_Est AS Dep_Est FROM wf_departamentos WHERE Wde_Cod = $dep_cod AND Emp_Cod = $emp_id;", $obBD_conexion);
    $obBD_con1->echoJson(array('success' => true, 'data' => $row));
    exit;
}

// --- AJAX: Obtener usuarios de la empresa con flag de asignaci�n al departamento ---
if (isset($ajax_get_depto_users) || isset($_GET['ajax_get_depto_users'])) {
    $dep_cod = intval($_GET['dep_cod']);
    $emp_id = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : (isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0);
    try {
        $usuarios = $obBD_con1->getArrayConsultaSql("
            SELECT base.Usu_Cod,
                   base.Usuario_Nom,
                   IF(EXISTS (
                       SELECT 1
                       FROM usuarios ux
                       INNER JOIN sucursal sx ON sx.Suc_Cod = ux.Suc_Cod
                       INNER JOIN wf_departamento_usuarios du2 ON du2.Usu_Cod = ux.Usu_Cod AND du2.Dep_Cod = $dep_cod
                       WHERE sx.Emp_Cod = $emp_id AND ux.Usu_Ced = base.Usu_Ced AND ux.Usu_Est = 'A' AND ux.Usu_Wf = 'S'
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
        $obBD_con1->echoJson(array('success' => true, 'usuarios' => $usuarios));
    } catch (Exception $e) {
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
    exit;
}

// --- AJAX: Guardar asignaciones de usuarios a un departamento ---
if (isset($ajax_save_depto_users)) {
    $dep_cod = intval($_POST['dep_cod']);
    $usuarios_ids = isset($_POST['usuarios']) ? $_POST['usuarios'] : array();
    
    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        $obBD_con1->grabarv_registros("DELETE FROM wf_departamento_usuarios WHERE Dep_Cod = $dep_cod;", $obBD_conexion);
        $emp_id = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : (isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0);
        foreach ($usuarios_ids as $u_id) {
            $u_id = intval($u_id);
            $cuentas = $obBD_con1->getArrayConsultaSql("
                SELECT ux.Usu_Cod
                FROM usuarios ux
                INNER JOIN sucursal sx ON sx.Suc_Cod = ux.Suc_Cod
                WHERE sx.Emp_Cod = $emp_id AND ux.Usu_Est = 'A' AND ux.Usu_Wf = 'S'
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

// Cargar datos para la vista (sincroniza RRHH -> wf_departamentos y luego lista)
$emp_id = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : (isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0);
$wf_mgr->syncDepartamentosFromRrhh($emp_id);
$deptos = $obBD_con1->getArrayConsultaSql("
    SELECT d.Wde_Cod AS Dep_Cod, d.Wde_Des AS Dep_Des, d.Wde_Est AS Dep_Est, d.Wde_Est AS Wfd_Est,
           (SELECT COUNT(DISTINCT u.Usu_Ced)
            FROM wf_departamento_usuarios du
            INNER JOIN usuarios u ON u.Usu_Cod = du.Usu_Cod
            INNER JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod
            WHERE du.Dep_Cod = d.Wde_Cod AND s.Emp_Cod = $emp_id AND u.Usu_Wf = 'S') as Cant_Usuarios
    FROM wf_departamentos d
    WHERE d.Emp_Cod = $emp_id
    ORDER BY (d.Wde_Est = 'A') DESC, Cant_Usuarios DESC, d.Wde_Des ASC;", $obBD_conexion);
if ($deptos === false || $deptos === null) {
    $deptos = array();
}

if (isset($ajax_get_deptos)) {
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
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
            <h4 class="fw-bold m-0 text-primary"><i class="bi bi-building"></i> Gesti�n de Departamentos</h4>
            <button class="btn btn-sm btn-success" onclick="abrirFormularioDepto()"><i class="bi bi-plus-lg"></i> Nuevo Departamento</button>
        </div>

        <!-- Listado de Departamentos -->
        <div class="exa-adq-table-wrap">
            <table class="table table-bordered exa-adq-table" id="tblDeptos">
                <thead>
                    <tr class="text-center font-monospace" style="font-size: 13px;">
                        <th style="width: 80px;">ID</th>
                        <th>Nombre del Departamento</th>
                        <th style="width: 150px;">Usuarios Asignados</th>
                        <th style="width: 140px;">Estado Workflow</th>
                        <th style="width: 180px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($deptos)) { ?>
                        <tr class="text-center"><td colspan="5" class="text-muted py-3">No hay departamentos activos en RRHH para esta empresa<?php echo $emp_id <= 0 ? ' (sesi&oacute;n sin Emp_Cod)' : ''; ?>.</td></tr>
                    <?php } else { 
                        foreach ($deptos as $d) { ?>
                            <tr class="text-center <?php echo $d['Wfd_Est'] === 'I' ? 'wf-depto-inactivo' : ''; ?>" id="row_dep_<?php echo $d['Dep_Cod']; ?>" data-wfd-est="<?php echo $d['Wfd_Est']; ?>">
                                <td class="fw-bold"><?php echo $d['Dep_Cod']; ?></td>
                                <td class="text-start"><?php echo $d['Dep_Des']; ?></td>
                                <td class="fw-bold text-primary">
                                    <span class="badge bg-info text-dark" id="cant_usu_<?php echo $d['Dep_Cod']; ?>">
                                        <?php echo $d['Cant_Usuarios']; ?> usuarios
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $d['Wfd_Est'] === 'A' ? 'success' : 'danger'; ?>" id="badge_dep_<?php echo $d['Dep_Cod']; ?>">
                                        <?php echo $d['Wfd_Est'] === 'A' ? 'Activo en WF' : 'Inactivo en WF'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-xs">
                                        <button class="btn btn-sm btn-outline-primary" onclick="editarDepto(<?php echo $d['Dep_Cod']; ?>)" title="Editar Nombre"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-outline-info" onclick="abrirDeptoUsuarios(<?php echo $d['Dep_Cod']; ?>, '<?php echo addslashes($d['Dep_Des']); ?>')" title="Asignar Usuarios"><i class="bi bi-people"></i></button>
                                        <button class="btn btn-sm btn-outline-<?php echo $d['Wfd_Est'] === 'A' ? 'danger' : 'success'; ?>" id="btn_toggle_dep_<?php echo $d['Dep_Cod']; ?>" onclick="toggleEstadoDepto(<?php echo $d['Dep_Cod']; ?>)" title="<?php echo $d['Wfd_Est'] === 'A' ? 'Desactivar en Workflow' : 'Activar en Workflow'; ?>">
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

    <!-- Modal para Crear/Editar Departamento -->
    <div class="modal fade" id="mdlDepto" tabindex="-1" aria-labelledby="mdlDeptoTitle" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="frmDepto" onsubmit="guardarDepto(event)">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold" id="mdlDeptoTitle">Nuevo Departamento</h5>
                        <button type="button" class="btn-close btn-close-white" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="Dep_Cod" name="Dep_Cod">
                        <div class="mb-3">
                            <label for="Dep_Des" class="form-label fw-bold">Nombre del Departamento *</label>
                            <input type="text" class="form-control" id="Dep_Des" name="Dep_Des" required placeholder="Ej. Departamento de Compras, Sistemas, etc.">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Asignar Usuarios -->
    <div class="modal fade" id="mdlDeptoUsuarios" tabindex="-1" aria-labelledby="mdlDeptoUsuariosTitle" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-info text-dark">
                    <h5 class="modal-title fw-bold" id="mdlDeptoUsuariosTitle"><i class="bi bi-people"></i> Asignar Usuarios</h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="depto_users_dep_cod">
                    <p class="text-muted small">Seleccione los usuarios que pertenecen al departamento <strong id="depto_users_dep_nom"></strong>:</p>
                    <div class="mb-3">
                        <input type="text" class="form-control form-control-sm" id="txtBuscarUsuarioDepto" placeholder="Buscar usuario..." onkeyup="filtrarUsuariosDepto()">
                    </div>
                    <div id="deptoUsersList" class="list-group" style="max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px;">
                        <!-- Lista de usuarios con checkboxes -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarUsuariosDepto()"><i class="bi bi-save"></i> Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function msgExa(tipo, mensaje, onAceptar) {
            if (typeof mostrarMensajeModal === 'function') {
                mostrarMensajeModal(tipo, mensaje, onAceptar);
            } else {
                alert(mensaje);
                if (typeof onAceptar === 'function') {
                    onAceptar();
                }
            }
        }

        function cerrarModalDepto(selector) {
            $(selector).modal('hide');
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
        }

        function abrirFormularioDepto() {
            $('#frmDepto')[0].reset();
            $('#Dep_Cod').val('');
            $('#mdlDeptoTitle').text('Nuevo Departamento');
            $('#mdlDepto').modal('show');
        }

        function editarDepto(id) {
            $.getJSON('adq_configuracion.php', { ajax_get_depto_req: true, Dep_Cod: id }, function(res) {
                if (res.success) {
                    const d = res.data;
                    $('#Dep_Cod').val(d.Dep_Cod);
                    $('#Dep_Des').val(d.Dep_Des);
                    $('#mdlDeptoTitle').text('Editar Departamento');
                    $('#mdlDepto').modal('show');
                } else {
                    msgExa('danger', 'Error al cargar datos: ' + res.message);
                }
            });
        }

        function guardarDepto(e) {
            e.preventDefault();
            const data = $('#frmDepto').serialize();
            $.post('adq_configuracion.php?ajax_save_depto_req=1', data, function(res) {
                if (res.success) {
                    cerrarModalDepto('#mdlDepto');
                    msgExa('success', 'Departamento guardado con &eacute;xito.', function() {
                        if (typeof cargarDepartamentos === 'function') {
                            cargarDepartamentos();
                        }
                    });
                } else {
                    msgExa('danger', 'Error al guardar: ' + (res.message || 'Error desconocido'));
                }
            }, 'json').fail(function() {
                msgExa('danger', 'Error de red al guardar el departamento.');
            });
        }

        function toggleEstadoDepto(id) {
            const row = $('#row_dep_' + id);
            const currentEst = row.data('wfd-est') || 'A';
            $.post('adq_configuracion.php?ajax_toggle_depto_req=1', { Dep_Cod: id, Wfd_Est: currentEst }, function(res) {
                if (res.success) {
                    if (typeof cargarDepartamentos === 'function') {
                        cargarDepartamentos();
                    } else {
                        location.reload();
                    }
                } else {
                    msgExa('danger', 'Error al cambiar estado: ' + (res.message || 'Error desconocido'));
                }
            }, 'json');
        }

        function abrirDeptoUsuarios(depCod, depNom) {
            $('#depto_users_dep_cod').val(depCod);
            $('#depto_users_dep_nom').text(depNom);
            $('#txtBuscarUsuarioDepto').val('');
            $('#deptoUsersList').html('<div class="text-center p-3"><div class="spinner-border spinner-border-sm text-secondary" role="status"></div> Cargando usuarios...</div>');
            $('#mdlDeptoUsuarios').modal('show');

            $.getJSON('adq_configuracion.php', { ajax_get_depto_users: true, dep_cod: depCod }, function(res) {
                if (res.success) {
                    const usuarios = res.usuarios.slice().sort(function(a, b) {
                        const aa = parseInt(a.asignado) === 1 ? 0 : 1;
                        const bb = parseInt(b.asignado) === 1 ? 0 : 1;
                        if (aa !== bb) return aa - bb;
                        return (a.Usuario_Nom || '').localeCompare(b.Usuario_Nom || '', 'es');
                    });
                    let html = '';
                    usuarios.forEach(function(u) {
                        const checked = parseInt(u.asignado) === 1 ? 'checked' : '';
                        html += `
                            <label class="list-group-item d-flex justify-content-between align-items-center item-usuario-depto" style="cursor: pointer; padding: 8px 12px; margin-bottom: 0; border: none; border-bottom: 1px solid #dee2e6;">
                                <div class="form-check m-0">
                                    <input class="form-check-input me-2 chk-depto-usu" type="checkbox" value="${u.Usu_Cod}" ${checked} id="chk_u_${u.Usu_Cod}">
                                    <span class="lbl-usuario-nom">${u.Usuario_Nom}</span>
                                </div>
                            </label>
                        `;
                    });
                    if (usuarios.length === 0) {
                        html = '<div class="text-center p-3 text-muted">No hay usuarios habilitados para workflow (Usu_Wf = S) en esta empresa.</div>';
                    }
                    $('#deptoUsersList').html(html);
                } else {
                    $('#deptoUsersList').html(`<div class="alert alert-danger p-2 small">${res.message || 'No se pudo cargar la lista de usuarios.'}</div>`);
                }
            }).fail(function(xhr, status, error) {
                $('#deptoUsersList').html(`<div class="alert alert-danger p-2 small">Error al cargar usuarios: ${error || status} (${xhr.status})</div>`);
            });
        }

        function filtrarUsuariosDepto() {
            const query = $('#txtBuscarUsuarioDepto').val().toLowerCase();
            $('.item-usuario-depto').each(function() {
                const nombre = $(this).find('.lbl-usuario-nom').text().toLowerCase();
                if (nombre.indexOf(query) !== -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }

        function guardarUsuariosDepto() {
            const depCod = $('#depto_users_dep_cod').val();
            const selectedUsers = [];
            $('.chk-depto-usu:checked').each(function() {
                selectedUsers.push($(this).val());
            });

            const btnGuardar = $('#mdlDeptoUsuarios .modal-footer button.btn-primary');
            const originalHtml = btnGuardar.html();
            btnGuardar.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...');

            $.post('adq_configuracion.php', {
                ajax_save_depto_users: true,
                dep_cod: depCod,
                usuarios: selectedUsers
            }, function(res) {
                btnGuardar.prop('disabled', false).html(originalHtml);
                if (res.success) {
                    cerrarModalDepto('#mdlDeptoUsuarios');
                    msgExa('success', 'Usuarios asignados con &eacute;xito.', function() {
                        if (typeof cargarDepartamentos === 'function') {
                            cargarDepartamentos();
                        }
                    });
                } else {
                    msgExa('danger', 'Error al guardar usuarios: ' + (res.message || 'Error desconocido'));
                }
            }, 'json').fail(function(xhr, status, error) {
                btnGuardar.prop('disabled', false).html(originalHtml);
                msgExa('danger', 'Error de red al guardar usuarios: ' + error);
            });
        }
    </script>
    <?php
    exit;
}
?>
