<?php
/**
 * EXA Adquisiciones - Panel de Configuración Unificado
 * Reúne la gestión de Tipos de Requerimientos y el Diseñador de Flujos en una sola ventana con pestañas.
 * @author Oz <oz-agent@warp.dev>
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/wf_manager_log.php');

$db_param = isset($Ses_Dis_Dis) ? $Ses_Dis_Dis : (isset($_SESSION['Ses_Dis_Dis']) ? $_SESSION['Ses_Dis_Dis'] : (isset($Ses_Dat_Dis) ? $Ses_Dat_Dis : (isset($_SESSION['Ses_Dat_Dis']) ? $_SESSION['Ses_Dat_Dis'] : null)));
$obBD_conexion = new Class_Log_Conexion_Global($db_param);
$obBD_con1 = new MysqlDatos($obBD_conexion);
$wf_mgr = new wf_manager_log($db_param);

// Verificar acceso a la ventana 'configuracion'
if (!$wf_mgr->verificarAccesoVentana('configuracion')) {
    if (isset($ajax_save_tipo_req) || isset($ajax_toggle_tipo_req) || isset($ajax_get_tipo_req) ||
        isset($ajax_save_workflow) || isset($ajax_publish_workflow) || isset($ajax_load_workflow) || isset($ajax_get_department_users) ||
        isset($ajax_save_department_users) || isset($ajax_get_users_by_department) ||
        isset($ajax_save_depto_req) || isset($ajax_toggle_depto_req) || isset($ajax_get_depto_req) ||
        isset($ajax_get_depto_users) || isset($ajax_save_depto_users)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Acceso denegado. No tiene permisos para realizar esta acción.'));
        exit;
    } else {
        echo "<div class='alert alert-danger m-3'>Acceso denegado. No tiene permisos para ver esta ventana.</div>";
        exit;
    }
}

// --- ENDPOINTS AJAX: Tipos de Requerimientos ---
if (isset($_GET['ajax_get_tipos']) || isset($_GET['ajax_get_tipo_req']) || isset($_GET['ajax_save_tipo_req']) || isset($_POST['ajax_save_tipo_req']) || isset($_POST['ajax_toggle_tipo_req'])) {
    $ajax_get_tipos = isset($_GET['ajax_get_tipos']) ? $_GET['ajax_get_tipos'] : null;
    $ajax_get_tipo_req = isset($_GET['ajax_get_tipo_req']) ? $_GET['ajax_get_tipo_req'] : null;
    $ajax_save_tipo_req = isset($_GET['ajax_save_tipo_req']) ? $_GET['ajax_save_tipo_req'] : (isset($_POST['ajax_save_tipo_req']) ? $_POST['ajax_save_tipo_req'] : null);
    $ajax_toggle_tipo_req = isset($_POST['ajax_toggle_tipo_req']) ? $_POST['ajax_toggle_tipo_req'] : null;

    // --- AJAX: Guardar Tipo de Requerimiento (Crear o Editar) ---
    if (isset($ajax_save_tipo_req)) {
        $trq_cod = !empty($_POST['Trq_Cod']) ? intval($_POST['Trq_Cod']) : null;
        $wfm_cod = intval($_POST['Wfm_Cod']);
        $trq_des = mysqli_real_escape_string($obBD_conexion->conexion, $_POST['Trq_Des']);
        $trq_req_fac = !empty($_POST['Trq_Req_Fac']) ? 1 : 0;
        $trq_per_cie = !empty($_POST['Trq_Per_Cie']) ? 1 : 0;
        $trq_req_cot = !empty($_POST['Trq_Req_Cot']) ? 1 : 0;
        $trq_min_cot = intval($_POST['Trq_Min_Cot']);
        $trq_req_pre = !empty($_POST['Trq_Req_Pre']) ? 1 : 0;
        $trq_req_adj = !empty($_POST['Trq_Req_Adj']) ? 1 : 0;
        $trq_req_pro = !empty($_POST['Trq_Req_Pro']) ? 1 : 0;
        
        // SLA / Tiempo Estimado
        $define_sla = !empty($_POST['chkDefineSla']) ? true : false;
        $trq_tiempo_est = ($define_sla && !empty($_POST['Trq_Tiempo_Est'])) ? intval($_POST['Trq_Tiempo_Est']) : 'NULL';

        if (empty($trq_des) || empty($wfm_cod)) {
            $obBD_con1->echoJson(array('success' => false, 'message' => 'El nombre y el flujo modelo son obligatorios.'));
            exit;
        }

        if ($define_sla && $trq_tiempo_est !== 'NULL' && $trq_tiempo_est <= 0) {
            $obBD_con1->echoJson(array('success' => false, 'message' => 'El tiempo estimado debe ser un número entero positivo.'));
            exit;
        }

        try {
            if ($trq_cod) {
                // Actualizar
                $sql = "UPDATE adq_tipos_requerimientos SET 
                    Wfm_Cod = $wfm_cod, Trq_Des = '$trq_des', Trq_Req_Fac = $trq_req_fac, 
                    Trq_Per_Cie = $trq_per_cie, Trq_Req_Cot = $trq_req_cot, Trq_Min_Cot = $trq_min_cot, 
                    Trq_Req_Pre = $trq_req_pre, Trq_Req_Adj = $trq_req_adj, Trq_Req_Pro = $trq_req_pro,
                    Trq_Tiempo_Est = $trq_tiempo_est
                    WHERE Trq_Cod = $trq_cod;";
            } else {
                // Crear
                $sql = "INSERT INTO adq_tipos_requerimientos (Emp_Cod, Wfm_Cod, Trq_Des, Trq_Req_Fac, Trq_Per_Cie, Trq_Req_Cot, Trq_Min_Cot, Trq_Req_Pre, Trq_Req_Adj, Trq_Req_Pro, Trq_Tiempo_Est, Trq_Est) 
                        VALUES ($Ses_Emp_Cod, $wfm_cod, '$trq_des', $trq_req_fac, $trq_per_cie, $trq_req_cot, $trq_min_cot, $trq_req_pre, $trq_req_adj, $trq_req_pro, $trq_tiempo_est, 'A');";
            }
            $obBD_con1->grabarv_registros($sql, $obBD_conexion);
            $obBD_con1->echoJson(array('success' => true));
        } catch (Exception $e) {
            $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
        }
        exit;
    }

    // --- AJAX: Cambiar estado (Activar/Desactivar) ---
    if (isset($ajax_toggle_tipo_req)) {
        $trq_cod = intval($_POST['Trq_Cod']);
        $nuevo_est = $_POST['Trq_Est'] === 'A' ? 'I' : 'A';
        $obBD_con1->grabarv_registros("UPDATE adq_tipos_requerimientos SET Trq_Est = '$nuevo_est' WHERE Trq_Cod = $trq_cod;", $obBD_conexion);
        $obBD_con1->echoJson(array('success' => true, 'nuevo_estado' => $nuevo_est));
        exit;
    }

    // --- AJAX: Obtener datos de un tipo para editar ---
    if (isset($ajax_get_tipo_req)) {
        $trq_cod = intval($_GET['Trq_Cod']);
        $row = $obBD_con1->getRowConsultaSql("SELECT * FROM adq_tipos_requerimientos WHERE Trq_Cod = $trq_cod;", $obBD_conexion);
        $obBD_con1->echoJson(array('success' => true, 'data' => $row));
        exit;
    }

    // --- AJAX: Obtener listado de tipos ---
    if (isset($ajax_get_tipos)) {
        $tipos = $obBD_con1->getArrayConsultaSql("
            SELECT t.*, w.Wfm_Nom 
            FROM adq_tipos_requerimientos t 
            INNER JOIN wf_flujos_modelos w ON w.Wfm_Cod = t.Wfm_Cod 
            WHERE t.Emp_Cod = $Ses_Emp_Cod 
            ORDER BY t.Trq_Des;", $obBD_conexion);

        $flujos = $wf_mgr->listarFlujosPublicados($Ses_Emp_Cod);
        ?>
        <div class="p-1">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                <h4 class="fw-bold m-0 text-primary"><i class="bi bi-tags"></i> Tipos de Requerimientos</h4>
                <button class="btn btn-sm btn-success" onclick="abrirFormulario()"><i class="bi bi-plus-lg"></i> Nuevo Tipo</button>
            </div>

            <!-- Listado de Tipos -->
            <div class="exa-adq-table-wrap">
                <table class="table table-bordered exa-adq-table" id="tblTipos">
                    <thead>
                        <tr class="text-center font-monospace" style="font-size: 13px;">
                            <th style="width: 60px;">ID</th>
                            <th>Descripción</th>
                            <th>Flujo Modelo</th>
                            <th style="width: 80px;">Factura</th>
                            <th style="width: 80px;">Cotiz.</th>
                            <th style="width: 60px;">Mín.</th>
                            <th style="width: 80px;">Adjunto</th>
                            <th style="width: 80px;">Presup.</th>
                            <th style="width: 80px;">Proveedor</th>
                            <th style="width: 100px;">Tiempo Est.</th>
                            <th style="width: 80px;">Estado</th>
                            <th style="width: 100px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tipos)) { ?>
                            <tr class="text-center"><td colspan="12" class="text-muted py-3">No hay tipos de requerimientos configurados.</td></tr>
                        <?php } else { 
                            foreach ($tipos as $t) { ?>
                                <tr class="text-center <?php echo $t['Trq_Est'] === 'I' ? 'table-light text-muted' : ''; ?>" id="row_trq_<?php echo $t['Trq_Cod']; ?>">
                                    <td class="fw-bold"><?php echo $t['Trq_Cod']; ?></td>
                                    <td class="text-start"><?php echo $t['Trq_Des']; ?></td>
                                    <td class="text-start fw-semibold text-primary"><?php echo $t['Wfm_Nom']; ?></td>
                                    <td><?php echo $t['Trq_Req_Fac'] ? '<i class="bi bi-check-lg text-success"></i>' : '<i class="bi bi-x-lg text-danger"></i>'; ?></td>
                                    <td><?php echo $t['Trq_Req_Cot'] ? '<i class="bi bi-check-lg text-success"></i>' : '<i class="bi bi-x-lg text-danger"></i>'; ?></td>
                                    <td class="fw-bold"><?php echo $t['Trq_Min_Cot']; ?></td>
                                    <td><?php echo $t['Trq_Req_Adj'] ? '<i class="bi bi-check-lg text-success"></i>' : '<i class="bi bi-x-lg text-danger"></i>'; ?></td>
                                    <td><?php echo $t['Trq_Req_Pre'] ? '<i class="bi bi-check-lg text-success"></i>' : '<i class="bi bi-x-lg text-danger"></i>'; ?></td>
                                    <td><?php echo $t['Trq_Req_Pro'] ? '<i class="bi bi-check-lg text-success"></i>' : '<i class="bi bi-x-lg text-danger"></i>'; ?></td>
                                    <td class="fw-bold text-secondary"><?php echo $t['Trq_Tiempo_Est'] !== null ? $t['Trq_Tiempo_Est'] . ' días' : '<span class="text-muted">-</span>'; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $t['Trq_Est'] === 'A' ? 'success' : 'secondary'; ?>" id="badge_trq_<?php echo $t['Trq_Cod']; ?>">
                                            <?php echo $t['Trq_Est'] === 'A' ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button class="btn btn-xs btn-outline-primary" onclick="editarTipo(<?php echo $t['Trq_Cod']; ?>)" title="Editar"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-xs btn-outline-<?php echo $t['Trq_Est'] === 'A' ? 'danger' : 'success'; ?>" id="btn_toggle_<?php echo $t['Trq_Cod']; ?>" onclick="toggleEstado(<?php echo $t['Trq_Cod']; ?>)" title="<?php echo $t['Trq_Est'] === 'A' ? 'Desactivar' : 'Activar'; ?>">
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

        <!-- MODAL FORMULARIO -->
        <div class="modal fade" id="mdlTipoReq" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="frmTipoReq" onsubmit="guardarTipo(event)">
                        <div class="modal-header bg-primary text-white">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title fw-bold" id="mdlTitle">Nuevo Tipo de Requerimiento</h4>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="Trq_Cod" name="Trq_Cod">
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nombre / Descripción *</label>
                                <input type="text" class="form-control" id="Trq_Des" name="Trq_Des" required placeholder="Ej. Compra de Tecnología">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Flujo Modelo Asociado *</label>
                                <select class="form-control" id="Wfm_Cod" name="Wfm_Cod" required>
                                    <option value="">[Seleccione un Flujo]</option>
                                    <?php foreach ($flujos as $f) { ?>
                                        <option value="<?php echo $f['Wfm_Cod']; ?>"><?php echo htmlspecialchars($f['Wfm_Nom'], ENT_QUOTES, 'UTF-8'); ?> (v<?php echo intval($f['Wfm_Version']); ?>)</option>
                                    <?php } ?>
                                </select>
                            </div>

                            <h6 class="fw-bold text-muted border-bottom pb-1 mb-3">Requisitos por defecto (al crear solicitud)</h6>
                            <p class="text-muted small mb-2">El solicitante podra ajustar estos valores en cada solicitud sin crear otro tipo.</p>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="Trq_Req_Fac" name="Trq_Req_Fac" value="1">
                                <label class="form-check-label" for="Trq_Req_Fac">Sustentar con Factura de Compra Física (Cierre)</label>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="Trq_Per_Cie" name="Trq_Per_Cie" value="1">
                                <label class="form-check-label" for="Trq_Per_Cie">Permitir cierre parcial de ítems</label>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="Trq_Req_Cot" name="Trq_Req_Cot" value="1" onchange="$('#divMinCot').toggle(this.checked)">
                                <label class="form-check-label" for="Trq_Req_Cot">Sustentar con Cotizaciones Múltiples al registrar</label>
                            </div>

                            <div class="mb-3 ms-4" id="divMinCot" style="display: none;">
                                <label class="form-label small fw-semibold">Mínimo de Cotizaciones requeridas</label>
                                <input type="number" class="form-control form-control-sm" id="Trq_Min_Cot" name="Trq_Min_Cot" min="1" value="3" style="width: 100px;">
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="Trq_Req_Pre" name="Trq_Req_Pre" value="1">
                                <label class="form-check-label" for="Trq_Req_Pre">Verificar disponibilidad presupuestaria</label>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="Trq_Req_Adj" name="Trq_Req_Adj" value="1">
                                <label class="form-check-label" for="Trq_Req_Adj">Archivos adjuntos de soporte obligatorios</label>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="Trq_Req_Pro" name="Trq_Req_Pro" value="1">
                                <label class="form-check-label" for="Trq_Req_Pro">Sugerir Proveedor al registrar</label>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="chkDefineSla" name="chkDefineSla" value="1" onchange="$('#divSlaDays').toggle(this.checked)">
                                <label class="form-check-label" for="chkDefineSla">Definir tiempo estimado (SLA) por defecto</label>
                            </div>

                            <div class="mb-3 ms-4" id="divSlaDays" style="display: none;">
                                <label class="form-label small fw-semibold">Días estimados de resolución</label>
                                <input type="number" class="form-control form-control-sm" id="Trq_Tiempo_Est" name="Trq_Tiempo_Est" min="1" style="width: 120px;">
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

        <script>
            function abrirFormulario() {
                $('#frmTipoReq')[0].reset();
                $('#Trq_Cod').val('');
                $('#mdlTitle').text('Nuevo Tipo de Requerimiento');
                $('#divMinCot').hide();
                $('#chkDefineSla').prop('checked', false);
                $('#divSlaDays').hide();
                $('#Trq_Tiempo_Est').val('');
                $('#mdlTipoReq').modal('show');
            }

            function editarTipo(id) {
                $.getJSON('adq_configuracion.php', { ajax_get_tipo_req: true, Trq_Cod: id }, function(res) {
                    if (res.success) {
                        const d = res.data;
                        $('#Trq_Cod').val(d.Trq_Cod);
                        $('#Trq_Des').val(d.Trq_Des);
                        $('#Wfm_Cod').val(d.Wfm_Cod);
                        
                        $('#Trq_Req_Fac').prop('checked', parseInt(d.Trq_Req_Fac) === 1);
                        $('#Trq_Per_Cie').prop('checked', parseInt(d.Trq_Per_Cie) === 1);
                        $('#Trq_Req_Cot').prop('checked', parseInt(d.Trq_Req_Cot) === 1);
                        $('#Trq_Min_Cot').val(d.Trq_Min_Cot);
                        $('#Trq_Req_Pre').prop('checked', parseInt(d.Trq_Req_Pre) === 1);
                        $('#Trq_Req_Adj').prop('checked', parseInt(d.Trq_Req_Adj) === 1);
                        $('#Trq_Req_Pro').prop('checked', parseInt(d.Trq_Req_Pro) === 1);

                        if (d.Trq_Tiempo_Est !== null && d.Trq_Tiempo_Est !== '') {
                            $('#chkDefineSla').prop('checked', true);
                            $('#Trq_Tiempo_Est').val(d.Trq_Tiempo_Est);
                            $('#divSlaDays').show();
                        } else {
                            $('#chkDefineSla').prop('checked', false);
                            $('#Trq_Tiempo_Est').val('');
                            $('#divSlaDays').hide();
                        }

                        $('#divMinCot').toggle(parseInt(d.Trq_Req_Cot) === 1);
                        $('#mdlTitle').text('Editar Tipo de Requerimiento');
                        
                        $('#mdlTipoReq').modal('show');
                    } else {
                        mostrarNotificacion('danger', 'Error al cargar datos: ' + res.message);
                    }
                });
            }

            function guardarTipo(e) {
                e.preventDefault();
                const data = $('#frmTipoReq').serialize();
                $.post('adq_configuracion.php?ajax_save_tipo_req=1', data, function(res) {
                    if (res.success) {
                        $('#mdlTipoReq').modal('hide');
                        mostrarNotificacion('success', 'Tipo de requerimiento guardado con éxito.');
                        cargarTiposConfiguracion(); // Recargar pestaña
                    } else {
                        mostrarNotificacion('danger', 'Error al guardar: ' + res.message);
                    }
                }, 'json');
            }

            function toggleEstado(id) {
                $.post('adq_configuracion.php?ajax_toggle_tipo_req=1', { Trq_Cod: id, Trq_Est: $('#badge_trq_' + id).text().trim() === 'Activo' ? 'A' : 'I' }, function(res) {
                    if (res.success) {
                        const badge = $('#badge_trq_' + id);
                        const btn = $('#btn_toggle_' + id);
                        const row = $('#row_trq_' + id);
                        
                        if (res.nuevo_estado === 'A') {
                            badge.removeClass('bg-secondary').addClass('bg-success').text('Activo');
                            btn.removeClass('btn-outline-success').addClass('btn-outline-danger').attr('title', 'Desactivar');
                            row.removeClass('table-light text-muted');
                        } else {
                            badge.removeClass('bg-success').addClass('bg-secondary').text('Inactivo');
                            btn.removeClass('btn-outline-danger').addClass('btn-outline-success').attr('title', 'Activar');
                            row.addClass('table-light text-muted');
                        }
                    } else {
                        mostrarNotificacion('danger', 'Error al cambiar estado: ' + res.message);
                    }
                }, 'json');
            }
        </script>
        <?php
        exit;
    }
}

if (isset($_GET['ajax_load_workflow']) || isset($_GET['ajax_save_workflow']) || isset($_POST['ajax_save_workflow']) ||
    isset($_GET['ajax_publish_workflow']) || isset($_POST['ajax_publish_workflow']) ||
    isset($_GET['ajax_get_department_users']) || isset($_POST['ajax_save_department_users']) || isset($_GET['ajax_get_users_by_department'])) {
    $ajax_load_workflow = isset($_GET['ajax_load_workflow']) ? $_GET['ajax_load_workflow'] : null;
    $ajax_save_workflow = isset($_GET['ajax_save_workflow']) ? $_GET['ajax_save_workflow'] : (isset($_POST['ajax_save_workflow']) ? $_POST['ajax_save_workflow'] : null);
    $ajax_publish_workflow = isset($_GET['ajax_publish_workflow']) ? $_GET['ajax_publish_workflow'] : (isset($_POST['ajax_publish_workflow']) ? $_POST['ajax_publish_workflow'] : null);
    $ajax_get_department_users = isset($_GET['ajax_get_department_users']) ? $_GET['ajax_get_department_users'] : null;
    $ajax_save_department_users = isset($_POST['ajax_save_department_users']) ? $_POST['ajax_save_department_users'] : null;
    $ajax_get_users_by_department = isset($_GET['ajax_get_users_by_department']) ? $_GET['ajax_get_users_by_department'] : null;
    include('wf_builder.php');
    exit;
}

if (isset($_GET['ajax_get_deptos']) || isset($_GET['ajax_get_depto_req']) || isset($_GET['ajax_save_depto_req']) || isset($_POST['ajax_save_depto_req']) || isset($_GET['ajax_toggle_depto_req']) || isset($_POST['ajax_toggle_depto_req']) || isset($_GET['ajax_get_depto_users']) || isset($_POST['ajax_save_depto_users'])) {
    $ajax_get_deptos = isset($_GET['ajax_get_deptos']) ? $_GET['ajax_get_deptos'] : null;
    $ajax_get_depto_req = isset($_GET['ajax_get_depto_req']) ? $_GET['ajax_get_depto_req'] : null;
    $ajax_save_depto_req = isset($_GET['ajax_save_depto_req']) ? $_GET['ajax_save_depto_req'] : (isset($_POST['ajax_save_depto_req']) ? $_POST['ajax_save_depto_req'] : null);
    $ajax_toggle_depto_req = isset($_GET['ajax_toggle_depto_req']) ? $_GET['ajax_toggle_depto_req'] : (isset($_POST['ajax_toggle_depto_req']) ? $_POST['ajax_toggle_depto_req'] : null);
    $ajax_get_depto_users = isset($_GET['ajax_get_depto_users']) ? $_GET['ajax_get_depto_users'] : null;
    $ajax_save_depto_users = isset($_POST['ajax_save_depto_users']) ? $_POST['ajax_save_depto_users'] : null;
    include('adq_departamentos.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es" class="exa-ui-fill-root">
<head>
    <meta charset="UTF-8">
    <title>Configuración de Adquisiciones</title>
    <?php require_once('adq_model3_assets.php'); ?>
</head>
<body class="exa-ui-fill-root">
    <div class="panel panel-main exa-ui-panel exa-ui-fill-page">
        <div class="panel-heading exa-header exa-header-flex">
            <h3 class="panel-title"><i class="bi bi-sliders"></i> Configuración de Adquisiciones</h3>
        </div>
        <div class="panel-body exa-body">
            <div class="exa-ui-page-view">
                <ul class="nav nav-tabs exa-ui-nav-tabs" id="configTabs" role="tablist">
                    <li role="presentation" class="active">
                        <a href="#tipos-panel" id="tipos-tab" role="tab" data-toggle="tab" onclick="cargarTiposConfiguracion()"><i class="bi bi-tags"></i> Tipos de Requerimiento</a>
                    </li>
                    <li role="presentation">
                        <a href="#builder-panel" id="builder-tab" role="tab" data-toggle="tab" onclick="cargarDisenadorFlujos()"><i class="bi bi-diagram-3"></i> Diseñador de Flujos</a>
                    </li>
                    <li role="presentation">
                        <a href="#deptos-panel" id="deptos-tab" role="tab" data-toggle="tab" onclick="cargarDepartamentos()"><i class="bi bi-building"></i> Departamentos</a>
                    </li>
                </ul>

                <div class="tab-content exa-ui-tab-content panels-area" id="configTabsContent">
                    <div class="tab-pane active" id="tipos-panel" role="tabpanel">
                        <div id="tipos-panel-content">
                            <div class="text-center p-5 text-muted">
                                <i class="glyphicon glyphicon-refresh glyphicon-spin" style="font-size:24px;"></i>
                                <div>Cargando tipos de requerimiento...</div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="builder-panel" role="tabpanel">
                        <div id="builder-panel-content">
                            <div class="text-center p-5 text-muted">
                                <i class="glyphicon glyphicon-refresh glyphicon-spin" style="font-size:24px;"></i>
                                <div>Cargando diseñador visual de flujos...</div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="deptos-panel" role="tabpanel">
                        <div id="deptos-panel-content">
                            <div class="text-center p-5 text-muted">
                                <i class="glyphicon glyphicon-refresh glyphicon-spin" style="font-size:24px;"></i>
                                <div>Cargando departamentos...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de mensajes (exito / error) -->
    <div class="modal fade" id="mdlMensajeExa" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-sm" style="margin-top: 10%;">
            <div class="modal-content" style="border-radius: 8px; overflow: hidden; box-shadow: 0 8px 24px rgba(0,0,0,0.18);">
                <div class="modal-header" id="mdlMensajeExaHeader" style="border-bottom: none; padding: 14px 16px;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title fw-bold m-0" id="mdlMensajeExaTitle">Mensaje</h4>
                </div>
                <div class="modal-body text-center" id="mdlMensajeExaBody" style="padding: 20px 24px; font-size: 14px;"></div>
                <div class="modal-footer" style="text-align: center; border-top: 1px solid #e5e7eb; padding: 12px 16px;">
                    <button type="button" class="btn btn-primary" id="btnMensajeExaAceptar" style="min-width: 120px;">Aceptar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../VALIDACIONES/wf_builder.js"></script>
    <script>
        function limpiarBackdropModal() {
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
        }

        function mostrarMensajeModal(tipo, mensaje, onAceptar) {
            const $modal = $('#mdlMensajeExa');
            const $header = $('#mdlMensajeExaHeader');
            const $title = $('#mdlMensajeExaTitle');
            const $body = $('#mdlMensajeExaBody');
            const $btn = $('#btnMensajeExaAceptar');

            $header.removeClass('bg-success bg-danger bg-primary text-white');
            $btn.removeClass('btn-success btn-danger btn-primary');

            if (tipo === 'success') {
                $header.addClass('bg-success text-white');
                $title.html('<i class="bi bi-check-circle-fill"></i> Correcto');
                $btn.addClass('btn-success');
            } else if (tipo === 'danger' || tipo === 'error') {
                $header.addClass('bg-danger text-white');
                $title.html('<i class="bi bi-exclamation-triangle-fill"></i> Error');
                $btn.addClass('btn-danger');
            } else {
                $header.addClass('bg-primary text-white');
                $title.html('<i class="bi bi-info-circle-fill"></i> Informaci&oacute;n');
                $btn.addClass('btn-primary');
            }

            $body.html(mensaje);

            $btn.off('click.mensajeExa').on('click.mensajeExa', function() {
                $modal.modal('hide');
                limpiarBackdropModal();
                if (typeof onAceptar === 'function') {
                    onAceptar();
                }
            });

            $modal.off('hidden.bs.modal.mensajeExa').on('hidden.bs.modal.mensajeExa', function() {
                limpiarBackdropModal();
            });

            limpiarBackdropModal();
            $modal.modal('show');
        }

        function mostrarNotificacion(tipo, mensaje) {
            let container = $('#notificaciones-container');
            if (container.length === 0) {
                container = $('<div id="notificaciones-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 450px;"></div>');
                $('body').append(container);
            }
            const alertId = 'alert_' + Date.now();
            const icon = tipo === 'success' ? 'bi-check-circle-fill' : (tipo === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill');
            const alertHtml = `
                <div id="${alertId}" class="alert alert-${tipo} alert-dismissible fade in" role="alert" style="box-shadow: 0 4px 12px rgba(0,0,0,0.15); margin-bottom: 10px; border-radius: 6px; padding: 12px 15px;">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="top: -2px; right: -5px;"><span aria-hidden="true">&times;</span></button>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="bi ${icon}" style="font-size: 1.2rem;"></i>
                        <span>${mensaje}</span>
                    </div>
                </div>
            `;
            container.append(alertHtml);
            setTimeout(function() {
                $('#' + alertId).fadeOut(500, function() {
                    $(this).remove();
                });
            }, 4000);
        }

        let tiposLoaded = false;
        function cargarTiposConfiguracion() {
            $.get('adq_configuracion.php', { ajax_get_tipos: 1 }, function(html) {
                $('#tipos-panel-content').html(html);
                tiposLoaded = true;
            }).fail(function(xhr, status, error) {
                mostrarNotificacion('danger', 'Error al cargar tipos de requerimientos: ' + error + ' (Status: ' + xhr.status + ')');
            });
        }

        let builderLoaded = false;
        function cargarDisenadorFlujos() {
            if (builderLoaded) return;
            $.get('wf_builder.php', { ajax_get_builder: 1 }, function(html) {
                $('#builder-panel-content').html(html);
                builderLoaded = true;
                if (typeof initWorkflowBuilder === 'function') {
                    initWorkflowBuilder();
                }
            }).fail(function(xhr, status, error) {
                mostrarNotificacion('danger', 'Error al cargar diseñador de flujos: ' + error + ' (Status: ' + xhr.status + ')');
            });
        }

        let deptosLoaded = false;
        function cargarDepartamentos() {
            $.get('adq_configuracion.php', { ajax_get_deptos: 1 }, function(html) {
                $('#deptos-panel-content').html(html);
                deptosLoaded = true;
            }).fail(function(xhr, status, error) {
                mostrarNotificacion('danger', 'Error al cargar departamentos: ' + error + ' (Status: ' + xhr.status + ')');
            });
        }

        $(document).ready(function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            if (tab === 'disenador') {
                $('a[href="#builder-panel"]').tab('show');
                cargarDisenadorFlujos();
            } else if (tab === 'departamentos') {
                $('a[href="#deptos-panel"]').tab('show');
                cargarDepartamentos();
            } else {
                cargarTiposConfiguracion();
            }
        });
    </script>
</body>
</html>
