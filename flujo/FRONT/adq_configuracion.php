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

function adq_cfg_h($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function adq_cfg_utf8_deep(&$data) {
    if ($data !== null && $data !== false && function_exists('utf8_encode_deep')) {
        utf8_encode_deep($data);
    }
}

// Verificar acceso a la ventana 'configuracion'
if (!$wf_mgr->verificarAccesoVentana('configuracion')) {
    if (isset($ajax_save_tipo_req) || isset($ajax_toggle_tipo_req) || isset($ajax_get_tipo_req) ||
        isset($ajax_save_workflow) || isset($ajax_publish_workflow) || isset($ajax_load_workflow) || isset($ajax_get_department_users) ||
        isset($ajax_save_department_users) || isset($ajax_get_users_by_department) ||
        isset($ajax_save_depto_req) || isset($ajax_toggle_depto_req) || isset($ajax_get_depto_req) ||
        isset($ajax_get_depto_users) || isset($ajax_save_depto_users) ||
        isset($_GET['ajax_get_usuarios_wf']) || isset($_POST['ajax_save_usuario_wf']) ||
        isset($_GET['ajax_get_builder'])) {
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
        if (!empty($row)) {
            adq_cfg_utf8_deep($row);
        }
        $obBD_con1->echoJson(array('success' => true, 'data' => $row));
        exit;
    }

    // --- AJAX: Obtener listado de tipos ---
    if (isset($ajax_get_tipos)) {
        header('Content-Type: text/html; charset=UTF-8');
        $filtro_wfm = isset($_GET['filtro_wfm']) ? intval($_GET['filtro_wfm']) : 0;
        $filtro_fam = 0;
        $where_flujo = '';
        if ($filtro_wfm > 0) {
            $filtro_fam = $wf_mgr->resolverFamiliaCod($filtro_wfm);
            if ($filtro_fam > 0) {
                $where_flujo = " AND COALESCE(w.Wfm_Fam_Cod, w.Wfm_Cod) = $filtro_fam ";
            }
        }

        $tipos = $obBD_con1->getArrayConsultaSql("
            SELECT t.*, w.Wfm_Nom, COALESCE(w.Wfm_Fam_Cod, w.Wfm_Cod) AS Wfm_Fam_Cod
            FROM adq_tipos_requerimientos t 
            INNER JOIN wf_flujos_modelos w ON w.Wfm_Cod = t.Wfm_Cod 
            WHERE t.Emp_Cod = $Ses_Emp_Cod $where_flujo
            ORDER BY w.Wfm_Nom, t.Trq_Des;", $obBD_conexion);

        $flujos = $wf_mgr->listarFlujosDisenador($Ses_Emp_Cod);
        adq_cfg_utf8_deep($tipos);
        adq_cfg_utf8_deep($flujos);
        ?>
        <div class="p-1">
            <div class="adq-tipos-toolbar">
                <h4 class="fw-bold text-primary adq-tipos-toolbar-title"><i class="bi bi-tags"></i> Tipos de Requerimientos</h4>
                <div class="adq-tipos-toolbar-actions">
                    <label class="form-label fw-semibold small" for="filtroFlujoModelo">Flujo modelo:</label>
                    <select class="form-control form-control-sm adq-tipos-filtro-flujo" id="filtroFlujoModelo" onchange="filtrarTiposPorFlujo()">
                        <option value="" <?php echo $filtro_fam <= 0 ? 'selected' : ''; ?>>Todos los flujos</option>
                        <?php foreach ($flujos as $f) {
                            $wfm_opt = intval($f['Wfm_Cod']);
                            $fam_opt = intval($f['Wfm_Fam_Cod']);
                            $sel = ($filtro_fam > 0 && $fam_opt === $filtro_fam) ? 'selected' : '';
                            ?>
                            <option value="<?php echo $wfm_opt; ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($wf_mgr->etiquetaFlujoListado($f), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php } ?>
                    </select>
                    <span class="text-muted small adq-tipos-toolbar-count"><?php echo count($tipos); ?> tipo(s)</span>
                    <button type="button" class="btn btn-sm btn-success" onclick="abrirFormulario()"><i class="bi bi-plus-lg"></i> Nuevo Tipo</button>
                </div>
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
                            <tr class="text-center"><td colspan="12" class="text-muted py-3"><?php echo $filtro_fam > 0 ? 'No hay tipos de requerimientos asociados a este flujo modelo.' : 'No hay tipos de requerimientos configurados.'; ?></td></tr>
                        <?php } else { 
                            foreach ($tipos as $t) { ?>
                                <tr class="text-center <?php echo $t['Trq_Est'] === 'I' ? 'table-light text-muted' : ''; ?>" id="row_trq_<?php echo $t['Trq_Cod']; ?>">
                                    <td class="fw-bold"><?php echo $t['Trq_Cod']; ?></td>
                                    <td class="text-start"><?php echo adq_cfg_h($t['Trq_Des']); ?></td>
                                    <td class="text-start fw-semibold text-primary"><?php echo adq_cfg_h($t['Wfm_Nom']); ?></td>
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
                                        <option value="<?php echo intval($f['Wfm_Cod']); ?>"><?php echo htmlspecialchars($wf_mgr->etiquetaFlujoListado($f), ENT_QUOTES, 'UTF-8'); ?></option>
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
                        $('#mdlTipoReq')
                            .one('hidden.bs.modal', function() {
                                if (typeof limpiarBackdropModal === 'function') {
                                    limpiarBackdropModal();
                                } else {
                                    $('body').removeClass('modal-open');
                                    $('.modal-backdrop').remove();
                                }
                                mostrarNotificacion('success', 'Tipo de requerimiento guardado con éxito.');
                                cargarTiposConfiguracion(); // Recargar pestaña
                            })
                            .modal('hide');
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

// Nodos no disponibles en el diseñador embebido de configuracion
$wf_builder_nodos_ocultos = array('DECISION', 'NOTIFICACION');

if (isset($_GET['ajax_get_builder'])) {
    header('Content-Type: text/html; charset=UTF-8');
    $ajax_get_builder = $_GET['ajax_get_builder'];
    include('wf_builder.php');
    exit;
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

if (isset($_GET['ajax_get_deptos']) || isset($_GET['ajax_get_depto_req']) || isset($_GET['ajax_get_deptos_rrhh']) || isset($_GET['ajax_save_depto_req']) || isset($_POST['ajax_save_depto_req']) || isset($_GET['ajax_toggle_depto_req']) || isset($_POST['ajax_toggle_depto_req']) || isset($_GET['ajax_get_depto_users']) || isset($_POST['ajax_save_depto_users'])) {
    if (isset($_GET['ajax_get_deptos'])) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    $ajax_get_deptos = isset($_GET['ajax_get_deptos']) ? $_GET['ajax_get_deptos'] : null;
    $ajax_get_depto_req = isset($_GET['ajax_get_depto_req']) ? $_GET['ajax_get_depto_req'] : null;
    $ajax_save_depto_req = isset($_GET['ajax_save_depto_req']) ? $_GET['ajax_save_depto_req'] : (isset($_POST['ajax_save_depto_req']) ? $_POST['ajax_save_depto_req'] : null);
    $ajax_toggle_depto_req = isset($_GET['ajax_toggle_depto_req']) ? $_GET['ajax_toggle_depto_req'] : (isset($_POST['ajax_toggle_depto_req']) ? $_POST['ajax_toggle_depto_req'] : null);
    $ajax_get_depto_users = isset($_GET['ajax_get_depto_users']) ? $_GET['ajax_get_depto_users'] : null;
    $ajax_save_depto_users = isset($_POST['ajax_save_depto_users']) ? $_POST['ajax_save_depto_users'] : null;
    include('adq_departamentos.php');
    exit;
}

function adq_cfg_ensure_usu_wf_column($obBD_con1, $conexion) {
    $row = $obBD_con1->getRowConsultaSql(
        "SELECT COUNT(*) AS cnt
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'usuarios'
           AND COLUMN_NAME = 'Usu_Wf';",
        $conexion
    );
    if (empty($row['cnt'])) {
        $obBD_con1->grabarv_registros(
            "ALTER TABLE usuarios
             ADD COLUMN Usu_Wf CHAR(1) NOT NULL DEFAULT 'N'
             COMMENT 'S=habilitado workflow, N=no habilitado' AFTER Usu_Est;",
            $conexion
        );
    }
}

// --- ENDPOINTS AJAX: Usuarios Workflow ---
if (isset($_GET['ajax_get_usuarios_wf']) || isset($_POST['ajax_save_usuario_wf'])) {
    $emp_id = isset($Ses_Emp_Cod) ? intval($Ses_Emp_Cod) : (isset($_SESSION['Ses_Emp_Cod']) ? intval($_SESSION['Ses_Emp_Cod']) : 0);
    adq_cfg_ensure_usu_wf_column($obBD_con1, $obBD_conexion);

    if (isset($_GET['ajax_get_usuarios_wf'])) {
        $usuarios = $obBD_con1->getArrayConsultaSql("
            SELECT base.Usu_Cod,
                   base.Prs_Cod,
                   base.Usu_Ced,
                   base.Nombres,
                   base.Prs_Tel,
                   base.Prs_Cel,
                   base.Prs_Cor,
                   base.Usu_Wf
            FROM (
                SELECT MIN(u.Usu_Cod) AS Usu_Cod,
                       MIN(p.Prs_Cod) AS Prs_Cod,
                       u.Usu_Ced,
                       TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Nombres,
                       MAX(IFNULL(p.Prs_Tel, '')) AS Prs_Tel,
                       MAX(IFNULL(p.Prs_Cel, '')) AS Prs_Cel,
                       MAX(IFNULL(p.Prs_Cor, '')) AS Prs_Cor,
                       MAX(CASE WHEN IFNULL(u.Usu_Wf, 'N') = 'S' THEN 'S' ELSE 'N' END) AS Usu_Wf
                FROM usuarios u
                INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
                INNER JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod
                WHERE s.Emp_Cod = $emp_id AND u.Usu_Est = 'A'
                GROUP BY u.Usu_Ced, p.Prs_Nom, p.Prs_Ape
            ) base
            ORDER BY base.Nombres ASC;", $obBD_conexion);
        if ($usuarios === false || $usuarios === null) {
            $usuarios = array();
        }
        foreach ($usuarios as &$u) {
            $tel = trim(isset($u['Prs_Tel']) ? $u['Prs_Tel'] : '');
            if ($tel === '' && !empty($u['Prs_Cel'])) {
                $tel = trim($u['Prs_Cel']);
            }
            $u['Telefono'] = $tel;
            $u['Correo'] = trim(isset($u['Prs_Cor']) ? $u['Prs_Cor'] : '');
            $u['Usu_Wf'] = (isset($u['Usu_Wf']) && strtoupper($u['Usu_Wf']) === 'S') ? 'S' : 'N';
        }
        unset($u);
        adq_cfg_utf8_deep($usuarios);
        $obBD_con1->echoJson(array('success' => true, 'usuarios' => $usuarios));
        exit;
    }

    if (isset($_POST['ajax_save_usuario_wf'])) {
        $usu_cod = intval(isset($_POST['Usu_Cod']) ? $_POST['Usu_Cod'] : 0);
        $prs_cod = intval(isset($_POST['Prs_Cod']) ? $_POST['Prs_Cod'] : 0);
        $usu_ced = trim(isset($_POST['Usu_Ced']) ? $_POST['Usu_Ced'] : '');
        $telefono = trim(isset($_POST['Telefono']) ? $_POST['Telefono'] : '');
        $correo = trim(isset($_POST['Correo']) ? $_POST['Correo'] : '');
        $usu_wf = (!empty($_POST['Usu_Wf']) && strtoupper($_POST['Usu_Wf']) === 'S') ? 'S' : 'N';

        if ($usu_cod <= 0 || $prs_cod <= 0) {
            $obBD_con1->echoJson(array('success' => false, 'message' => 'Usuario invalido.'));
            exit;
        }
        if ($telefono === '' || $correo === '') {
            $obBD_con1->echoJson(array('success' => false, 'message' => 'Debe ingresar telefono y correo para registrar.'));
            exit;
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $obBD_con1->echoJson(array('success' => false, 'message' => 'El correo electronico no es valido.'));
            exit;
        }

        $chk = $obBD_con1->getRowConsultaSql("
            SELECT u.Usu_Cod, u.Usu_Ced, u.Prs_Cod
            FROM usuarios u
            INNER JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod
            WHERE u.Usu_Cod = $usu_cod AND s.Emp_Cod = $emp_id AND u.Usu_Est = 'A'
            LIMIT 1;", $obBD_conexion);
        if (empty($chk)) {
            $obBD_con1->echoJson(array('success' => false, 'message' => 'El usuario no pertenece a esta empresa.'));
            exit;
        }
        if ($usu_ced === '') {
            $usu_ced = isset($chk['Usu_Ced']) ? $chk['Usu_Ced'] : '';
        }
        $prs_cod = !empty($chk['Prs_Cod']) ? intval($chk['Prs_Cod']) : $prs_cod;

        $tel_esc = mysqli_real_escape_string($obBD_conexion->conexion, $telefono);
        $cor_esc = mysqli_real_escape_string($obBD_conexion->conexion, $correo);
        $ced_esc = mysqli_real_escape_string($obBD_conexion->conexion, $usu_ced);

        try {
            $ok_prs = $obBD_con1->grabarv_registros(
                "UPDATE persona
                 SET Prs_Tel = '$tel_esc', Prs_Cor = '$cor_esc'
                 WHERE Prs_Cod = $prs_cod;",
                $obBD_conexion
            );
            if (!$ok_prs) {
                throw new Exception('No se pudo actualizar los datos de persona.');
            }

            if ($ced_esc !== '') {
                $ok_usu = $obBD_con1->grabarv_registros(
                    "UPDATE usuarios u
                     INNER JOIN sucursal s ON s.Suc_Cod = u.Suc_Cod
                     SET u.Usu_Wf = '$usu_wf'
                     WHERE s.Emp_Cod = $emp_id AND u.Usu_Est = 'A' AND u.Usu_Ced = '$ced_esc';",
                    $obBD_conexion
                );
            } else {
                $ok_usu = $obBD_con1->grabarv_registros(
                    "UPDATE usuarios SET Usu_Wf = '$usu_wf' WHERE Usu_Cod = $usu_cod;",
                    $obBD_conexion
                );
            }
            if (!$ok_usu) {
                throw new Exception('No se pudo actualizar el estado del usuario en workflow.');
            }

            $obBD_con1->echoJson(array(
                'success' => true,
                'message' => 'Usuario registrado correctamente.',
                'Usu_Wf' => $usu_wf
            ));
        } catch (Exception $e) {
            $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es" class="exa-ui-fill-root">
<head>
    <meta charset="UTF-8">
    <title>Configuración de Adquisiciones</title>
    <?php require_once('adq_model3_assets.php'); ?>
    <style>
        .adq-tipos-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: nowrap;
            gap: 16px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 12px;
            margin-bottom: 12px;
        }
        .adq-tipos-toolbar-title {
            flex: 0 0 auto;
            margin: 0;
        }
        .adq-tipos-toolbar-actions {
            display: flex;
            align-items: center;
            flex-wrap: nowrap;
            gap: 10px;
            margin-left: auto;
            flex: 0 0 auto;
        }
        .adq-tipos-toolbar-actions label {
            margin: 0;
            white-space: nowrap;
        }
        .adq-tipos-filtro-flujo {
            width: 260px !important;
            min-width: 200px;
            max-width: 320px;
            flex: 0 0 auto;
            display: inline-block;
        }
        .adq-tipos-toolbar-count {
            white-space: nowrap;
            flex: 0 0 auto;
        }
        .adq-tipos-toolbar-actions .btn {
            flex: 0 0 auto;
            white-space: nowrap;
        }
        .adq-usuarios-toolbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .adq-usuarios-filters {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        .adq-usuarios-filters .form-group {
            margin: 0;
            min-width: 180px;
            flex: 1 1 180px;
            max-width: 280px;
        }
        .adq-usuarios-filters .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 4px;
            color: #475569;
        }
        .adq-usuarios-filters .form-control {
            height: 34px;
            font-size: 13px;
        }
        .adq-usuarios-filters .adq-usuarios-filter-actions {
            display: flex;
            gap: 8px;
            flex: 0 0 auto;
            padding-bottom: 1px;
        }
        .adq-usuarios-table-wrap {
            max-height: calc(100vh - 300px);
            overflow: auto;
        }
        #tblUsuariosWf input.form-control {
            height: 32px;
            font-size: 12px;
            padding: 4px 8px;
        }
        #tblUsuariosWf .adq-usu-estado-wrap {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }
        #tblUsuariosWf .adq-usu-estado-lbl.is-on {
            color: #15803d;
        }
        #tblUsuariosWf .adq-usu-estado-lbl.is-off {
            color: #64748b;
        }
    </style>
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
                    <li role="presentation">
                        <a href="#usuarios-panel" id="usuarios-tab" role="tab" data-toggle="tab" onclick="cargarUsuariosWf()"><i class="bi bi-people"></i> Usuarios</a>
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

                    <div class="tab-pane" id="usuarios-panel" role="tabpanel">
                        <div class="adq-usuarios-toolbar">
                            <div>
                                <h5 class="adq-section-header" style="margin:0;border:none;padding:0;"><i class="bi bi-people"></i> Usuarios del sistema</h5>
                                <p class="text-muted small" style="margin:6px 0 0;">Configure telefono, correo y habilite el usuario para workflow (<strong>Usu_Wf = S</strong>). Telefono y correo se guardan en <strong>persona</strong>.</p>
                            </div>
                            <button type="button" class="btn btn-default btn-sm" onclick="cargarUsuariosWf()"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                        </div>
                        <div class="adq-usuarios-filters">
                            <div class="form-group">
                                <label for="txtFiltroUsuNombres">Nombres</label>
                                <input type="text" class="form-control" id="txtFiltroUsuNombres" placeholder="Buscar por nombres..." autocomplete="off">
                            </div>
                            <div class="form-group">
                                <label for="txtFiltroUsuCedula">Cedula</label>
                                <input type="text" class="form-control" id="txtFiltroUsuCedula" placeholder="Buscar por cedula..." autocomplete="off">
                            </div>
                            <div class="adq-usuarios-filter-actions">
                                <button type="button" class="btn btn-primary btn-sm" id="btnFiltrarUsuariosWf"><i class="bi bi-funnel"></i> Filtrar</button>
                                <button type="button" class="btn btn-default btn-sm" id="btnLimpiarFiltroUsuariosWf"><i class="bi bi-x-circle"></i> Limpiar</button>
                            </div>
                        </div>
                        <div id="adqUsuariosMsg" style="display:none;"></div>
                        <div class="exa-adq-table-wrap adq-usuarios-table-wrap">
                            <table class="table table-bordered exa-adq-table" id="tblUsuariosWf">
                                <thead>
                                    <tr>
                                        <th>Nombres</th>
                                        <th style="width:120px;">Cedula</th>
                                        <th style="width:140px;">Telefono</th>
                                        <th style="width:220px;">Email</th>
                                        <th style="width:100px;">Estado</th>
                                        <th style="width:120px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tblUsuariosWfBody">
                                    <tr class="text-center">
                                        <td colspan="6" class="text-muted">Seleccione el tab Usuarios para cargar el listado.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales persistentes de Departamentos (fuera del contenido AJAX) -->
    <div class="modal fade" id="mdlDepto" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="frmDepto" onsubmit="guardarDepto(event)">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="mdlDeptoTitle"><i class="bi bi-building"></i> Nuevo Departamento</h4>
                    </div>
                    <div class="modal-body">
                        <div class="adq-detail-card">
                            <input type="hidden" id="Dep_Cod" name="Dep_Cod">
                            <div id="divDeptoNuevo">
                                <h5 class="adq-section-header"><i class="bi bi-diagram-3"></i> Vincular con RRHH</h5>
                                <div class="form-group">
                                    <label for="Dep_Rrhh_Cod" class="control-label">Departamento (Recursos Humanos) *</label>
                                    <select class="form-control select2-depto-rrhh" id="Dep_Rrhh_Cod" name="Dep_Rrhh_Cod" style="width: 100%;">
                                        <option value="">[Seleccione un departamento]</option>
                                    </select>
                                    <p class="help-block small text-muted" style="margin-top: 8px; margin-bottom: 0;">Los departamentos se registran en el m&oacute;dulo de Recursos Humanos.</p>
                                </div>
                            </div>
                            <div id="divDeptoEditar" style="display: none;">
                                <h5 class="adq-section-header"><i class="bi bi-pencil-square"></i> Editar nombre</h5>
                                <div class="form-group">
                                    <label for="Dep_Des" class="control-label">Nombre del Departamento *</label>
                                    <input type="text" class="form-control" id="Dep_Des" name="Dep_Des" placeholder="Ej. Departamento de Compras, Sistemas, etc." autocomplete="off">
                                </div>
                            </div>
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

    <div class="modal fade" id="mdlDeptoUsuarios" tabindex="-1" role="dialog" aria-hidden="true" data-gramm="false" data-gramm_editor="false" data-enable-grammarly="false">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    <div>
                        <h4 class="modal-title" id="mdlDeptoUsuariosTitle"><i class="bi bi-people"></i> Asignar Usuarios</h4>
                        <p class="adq-modal-subtitle" id="depto_users_dep_nom"></p>
                    </div>
                </div>
                <div class="modal-body" data-gramm="false" data-gramm_editor="false" data-enable-grammarly="false">
                    <input type="hidden" id="depto_users_dep_cod">
                    <div class="adq-depto-users-card">
                        <h5 class="adq-section-header"><i class="bi bi-person-check"></i> Usuarios del departamento</h5>
                        <p class="text-muted small" style="margin: -4px 0 12px;">Seleccione los usuarios habilitados para workflow (<strong>Usu_Wf = S</strong>) que pertenecen a este departamento.</p>
                        <div class="adq-depto-users-search">
                            <input type="text" class="form-control input-sm" id="txtBuscarUsuarioDepto" placeholder="Buscar usuario por nombre..." autocomplete="off" spellcheck="false">
                        </div>
                        <div id="deptoUsersList" class="adq-depto-users-scroll" data-gramm="false"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnGuardarUsuariosDepto"><i class="bi bi-save"></i> Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de mensajes (exito / error) -->
    <div class="modal fade" id="mdlMensajeExa" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-sm" style="margin-top: 10%;">
            <div class="modal-content">
                <div class="modal-header" id="mdlMensajeExaHeader">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title m-0" id="mdlMensajeExaTitle">Mensaje</h4>
                </div>
                <div class="modal-body text-center" id="mdlMensajeExaBody"></div>
                <div class="modal-footer">
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

        function escHtmlCfg(text) {
            if (text === null || text === undefined) {
                return '';
            }
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
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

            $body.html(escHtmlCfg(mensaje));

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
                        <span>${escHtmlCfg(mensaje)}</span>
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
        function obtenerFiltroFlujoTipos() {
            const $filtro = $('#filtroFlujoModelo');
            return $filtro.length ? ($filtro.val() || '') : '';
        }

        function cargarTiposConfiguracion(filtroWfm) {
            const params = { ajax_get_tipos: 1 };
            const filtro = typeof filtroWfm !== 'undefined' ? filtroWfm : obtenerFiltroFlujoTipos();
            if (filtro) {
                params.filtro_wfm = filtro;
            }
            $.get('adq_configuracion.php', params, function(html) {
                $('#tipos-panel-content').html(html);
                tiposLoaded = true;
            }).fail(function(xhr, status, error) {
                mostrarNotificacion('danger', 'Error al cargar tipos de requerimientos: ' + error + ' (Status: ' + xhr.status + ')');
            });
        }

        function filtrarTiposPorFlujo() {
            cargarTiposConfiguracion(obtenerFiltroFlujoTipos());
        }

        let builderLoaded = false;
        function cargarDisenadorFlujos() {
            if (builderLoaded) return;
            $.get('adq_configuracion.php', { ajax_get_builder: 1 }, function(html) {
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

        function escHtmlDepto(text) {
            if (typeof escHtmlCfg === 'function') {
                return escHtmlCfg(text);
            }
            if (text === null || text === undefined) {
                return '';
            }
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function msgExaDepto(tipo, mensaje, onAceptar) {
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
            if (selector === '#mdlDepto') {
                destroyDeptoRrhhSelect();
            }
            const $modal = $(selector);
            if ($modal.length) {
                $modal.modal('hide');
            }
            setTimeout(function() {
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
            }, 200);
        }

        function destroyDeptoRrhhSelect() {
            const $el = $('#Dep_Rrhh_Cod');
            if ($el.length && $el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
        }

        function initDeptoRrhhSelect() {
            const $el = $('#Dep_Rrhh_Cod');
            if (!$el.length || typeof $.fn.select2 !== 'function') {
                return;
            }
            destroyDeptoRrhhSelect();
            $el.select2({
                placeholder: '[Seleccione un departamento]',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#mdlDepto'),
                language: {
                    noResults: function() { return 'Sin resultados'; },
                    searching: function() { return 'Buscando...'; }
                }
            });
        }

        function cargarOpcionesDeptoRrhh() {
            return $.getJSON('adq_configuracion.php', { ajax_get_deptos_rrhh: 1 }).then(function(res) {
                const $sel = $('#Dep_Rrhh_Cod');
                $sel.empty().append($('<option>', { value: '', text: '[Seleccione un departamento]' }));
                if (res.success && res.data && res.data.length) {
                    res.data.forEach(function(d) {
                        $sel.append($('<option>', {
                            value: String(d.Dep_Cod),
                            text: (d.Dep_Des || ('Depto ' + d.Dep_Cod)) + ' (ID ' + d.Dep_Cod + ')'
                        }));
                    });
                } else {
                    $sel.append($('<option>', { value: '', text: 'No hay departamentos activos en Recursos Humanos', disabled: true }));
                }
            }).fail(function(xhr, status, error) {
                msgExaDepto('danger', 'No se pudo cargar departamentos de RRHH: ' + (error || status || xhr.status));
            });
        }

        function abrirFormularioDepto() {
            destroyDeptoRrhhSelect();
            $('#frmDepto')[0].reset();
            $('#Dep_Cod').val('');
            $('#Dep_Rrhh_Cod').prop('required', true);
            $('#Dep_Des').prop('required', false).val('');
            $('#divDeptoNuevo').show();
            $('#divDeptoEditar').hide();
            $('#mdlDeptoTitle').html('<i class="bi bi-building"></i> Nuevo Departamento');
            cargarOpcionesDeptoRrhh().always(function() {
                $('#mdlDepto')
                    .off('shown.bs.modal.deptoRrhh')
                    .on('shown.bs.modal.deptoRrhh', function() {
                        initDeptoRrhhSelect();
                    })
                    .modal('show');
            });
        }

        function editarDepto(id) {
            $.getJSON('adq_configuracion.php', { ajax_get_depto_req: true, Dep_Cod: id }, function(res) {
                if (res.success) {
                    destroyDeptoRrhhSelect();
                    const d = res.data;
                    $('#Dep_Cod').val(d.Dep_Cod);
                    $('#Dep_Des').val(d.Dep_Des).prop('required', true);
                    $('#Dep_Rrhh_Cod').prop('required', false).val('');
                    $('#divDeptoNuevo').hide();
                    $('#divDeptoEditar').show();
                    $('#mdlDeptoTitle').html('<i class="bi bi-pencil-square"></i> Editar Departamento');
                    $('#mdlDepto').modal('show');
                } else {
                    msgExaDepto('danger', 'Error al cargar datos: ' + (res.message || ''));
                }
            });
        }

        function guardarDepto(e) {
            e.preventDefault();
            const depCod = $('#Dep_Cod').val();
            if (!depCod && !$('#Dep_Rrhh_Cod').val()) {
                msgExaDepto('danger', 'Debe seleccionar un departamento de Recursos Humanos.');
                return;
            }
            $.post('adq_configuracion.php?ajax_save_depto_req=1', $('#frmDepto').serialize(), function(res) {
                if (res.success) {
                    $('#mdlDepto').one('hidden.bs.modal', function() {
                        limpiarBackdropModal();
                        msgExaDepto('success', 'Departamento guardado con exito.', function() {
                            cargarDepartamentos();
                        });
                    }).modal('hide');
                } else {
                    msgExaDepto('danger', 'Error al guardar: ' + (res.message || 'Error desconocido'));
                }
            }, 'json').fail(function() {
                msgExaDepto('danger', 'Error de red al guardar el departamento.');
            });
        }

        function toggleEstadoDepto(id) {
            const row = $('#row_dep_' + id);
            const currentEst = row.data('wfd-est') || 'A';
            $.post('adq_configuracion.php?ajax_toggle_depto_req=1', { Dep_Cod: id, Wfd_Est: currentEst }, function(res) {
                if (res.success) {
                    cargarDepartamentos();
                } else {
                    msgExaDepto('danger', 'Error al cambiar estado: ' + (res.message || 'Error desconocido'));
                }
            }, 'json');
        }

        function renderListaUsuariosDepto(usuarios) {
            const $list = $('#deptoUsersList').empty();
            if (!usuarios || !usuarios.length) {
                $list.append($('<div class="adq-depto-users-empty">').text('No hay usuarios habilitados para workflow (Usu_Wf = S) en esta empresa.'));
                return;
            }
            usuarios.forEach(function(u) {
                const usuCod = parseInt(u.Usu_Cod, 10);
                const $label = $('<label class="item-usuario-depto">');
                const $wrap = $('<div class="form-check">');
                const $chk = $('<input type="checkbox" class="chk-depto-usu">')
                    .val(String(usuCod))
                    .prop('checked', parseInt(u.asignado, 10) === 1)
                    .attr('id', 'chk_u_' + usuCod);
                const $nom = $('<span class="lbl-usuario-nom">').text(u.Usuario_Nom || '');
                $wrap.append($chk).append($nom);
                $label.append($wrap);
                $list.append($label);
            });
        }

        function abrirDeptoUsuarios(depCod, depNom) {
            const wdeCod = parseInt(depCod, 10);
            if (!wdeCod) {
                msgExaDepto('danger', 'Departamento de workflow no valido.');
                return;
            }
            depNom = depNom || '';
            $('#depto_users_dep_cod').val(String(wdeCod));
            $('#mdlDeptoUsuariosTitle').html('<i class="bi bi-people"></i> Asignar Usuarios');
            $('#depto_users_dep_nom').text(depNom);
            $('#txtBuscarUsuarioDepto').val('');
            $('#deptoUsersList').empty().append(
                $('<div class="adq-depto-users-empty">').text('Cargando usuarios...')
            );
            limpiarBackdropModal();
            $('#mdlDeptoUsuarios').modal('show');

            $.getJSON('adq_configuracion.php', { ajax_get_depto_users: true, dep_cod: wdeCod }, function(res) {
                if (res.success) {
                    const usuarios = (res.usuarios || []).slice().sort(function(a, b) {
                        const aa = parseInt(a.asignado, 10) === 1 ? 0 : 1;
                        const bb = parseInt(b.asignado, 10) === 1 ? 0 : 1;
                        if (aa !== bb) return aa - bb;
                        return String(a.Usuario_Nom || '').localeCompare(String(b.Usuario_Nom || ''), 'es');
                    });
                    renderListaUsuariosDepto(usuarios);
                } else {
                    $('#deptoUsersList').empty().append(
                        $('<div class="alert alert-danger p-2 small" style="margin: 8px;">').text(res.message || 'No se pudo cargar la lista de usuarios.')
                    );
                }
            }).fail(function(xhr, status) {
                $('#deptoUsersList').empty().append(
                    $('<div class="alert alert-danger p-2 small" style="margin: 8px;">').text('Error al cargar usuarios: ' + (status || xhr.status))
                );
            });
        }

        function filtrarUsuariosDepto() {
            const query = String($('#txtBuscarUsuarioDepto').val() || '').toLowerCase();
            $('#deptoUsersList .item-usuario-depto').each(function() {
                const nombre = $(this).find('.lbl-usuario-nom').text().toLowerCase();
                $(this).toggle(nombre.indexOf(query) !== -1);
            });
        }

        function guardarUsuariosDepto() {
            const depCod = String($('#depto_users_dep_cod').val() || '').trim();
            if (!depCod) {
                msgExaDepto('danger', 'Departamento de workflow no valido.');
                return;
            }
            const selectedUsers = [];
            $('#deptoUsersList .chk-depto-usu:checked').each(function() {
                selectedUsers.push($(this).val());
            });

            const $btn = $('#btnGuardarUsuariosDepto');
            const originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Guardando...');

            $.ajax({
                url: 'adq_configuracion.php',
                type: 'POST',
                dataType: 'json',
                traditional: true,
                data: {
                    ajax_save_depto_users: 1,
                    dep_cod: depCod,
                    'usuarios[]': selectedUsers
                }
            }).done(function(res) {
                if (res.success) {
                    const n = (res.data && res.data.insertados) ? res.data.insertados : selectedUsers.length;
                    $('#mdlDeptoUsuarios').one('hidden.bs.modal', function() {
                        limpiarBackdropModal();
                        msgExaDepto('success', 'Usuarios asignados con exito (' + n + ').', function() {
                            cargarDepartamentos();
                        });
                    }).modal('hide');
                } else {
                    msgExaDepto('danger', 'Error al guardar usuarios: ' + (res.message || 'Error desconocido'));
                }
            }).fail(function(xhr, status, error) {
                let detalle = error || status;
                try {
                    const parsed = JSON.parse(xhr.responseText);
                    if (parsed && parsed.message) {
                        detalle = parsed.message;
                    }
                } catch (e) {
                    if (xhr.responseText) {
                        detalle = String(xhr.responseText).substring(0, 300);
                    }
                }
                msgExaDepto('danger', 'Error de red al guardar usuarios: ' + detalle);
            }).always(function() {
                $btn.prop('disabled', false).html(originalHtml);
            });
        }

        function cargarDepartamentos() {
            $.get('adq_configuracion.php', { ajax_get_deptos: 1 }, function(html) {
                $('#deptos-panel-content').html(html);
                deptosLoaded = true;
            }).fail(function(xhr, status, error) {
                mostrarNotificacion('danger', 'Error al cargar departamentos: ' + error + ' (Status: ' + xhr.status + ')');
            });
        }

        let usuariosWfLoaded = false;
        let usuariosWfCache = [];

        function msgUsuariosWf(tipo, mensaje) {
            const $box = $('#adqUsuariosMsg');
            $box.removeClass('alert-success alert-danger alert-warning alert-info')
                .addClass('alert alert-' + tipo)
                .html(mensaje)
                .show();
            setTimeout(function() { $box.fadeOut(200); }, 4000);
        }

        function escUsuariosWf(text) {
            return String(text == null ? '' : text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function actualizarLblEstadoUsu($row) {
            const activo = $row.find('.chk-usu-wf').is(':checked');
            const $lbl = $row.find('.adq-usu-estado-lbl');
            $lbl.text(activo ? 'ACTIVO' : 'NO ACTIVO')
                .toggleClass('is-on', activo)
                .toggleClass('is-off', !activo);
        }

        function filtrarUsuariosWf() {
            const nom = $.trim($('#txtFiltroUsuNombres').val() || '').toLowerCase();
            const ced = $.trim($('#txtFiltroUsuCedula').val() || '').toLowerCase();
            let lista = usuariosWfCache.slice();
            if (nom !== '') {
                lista = lista.filter(function(u) {
                    return String(u.Nombres || '').toLowerCase().indexOf(nom) !== -1;
                });
            }
            if (ced !== '') {
                lista = lista.filter(function(u) {
                    return String(u.Usu_Ced || '').toLowerCase().indexOf(ced) !== -1;
                });
            }
            renderUsuariosWf(lista, nom !== '' || ced !== '');
        }

        function renderUsuariosWf(usuarios, esFiltro) {
            const $tb = $('#tblUsuariosWfBody').empty();
            if (!usuarios || !usuarios.length) {
                const msg = esFiltro
                    ? 'No se encontraron usuarios con los filtros indicados.'
                    : 'No hay usuarios activos en esta empresa.';
                $tb.append('<tr class="text-center"><td colspan="6" class="text-muted">' + msg + '</td></tr>');
                return;
            }
            usuarios.forEach(function(u) {
                const usuCod = parseInt(u.Usu_Cod, 10) || 0;
                const prsCod = parseInt(u.Prs_Cod, 10) || 0;
                const activo = String(u.Usu_Wf || 'N').toUpperCase() === 'S';
                const tel = u.Telefono || u.Prs_Tel || '';
                const correo = u.Correo || u.Prs_Cor || '';
                const $tr = $('<tr class="adq-usu-row">')
                    .attr('data-usu-cod', usuCod)
                    .attr('data-prs-cod', prsCod)
                    .attr('data-usu-ced', u.Usu_Ced || '');
                $tr.append($('<td class="text-start">').text(u.Nombres || ''));
                $tr.append($('<td>').text(u.Usu_Ced || ''));
                $tr.append(
                    $('<td>').append(
                        $('<input type="text" class="form-control input-usu-tel" maxlength="20" placeholder="Telefono">').val(tel)
                    )
                );
                $tr.append(
                    $('<td>').append(
                        $('<input type="email" class="form-control input-usu-cor" maxlength="120" placeholder="correo@empresa.com">').val(correo)
                    )
                );
                $tr.append(
                    $('<td class="text-center">').append(
                        $('<label class="adq-usu-estado-wrap">')
                            .append($('<input type="checkbox" class="chk-usu-wf">').prop('checked', activo))
                            .append($('<span class="adq-usu-estado-lbl">'))
                    )
                );
                $tr.append(
                    $('<td class="text-center">').append(
                        $('<button type="button" class="btn btn-sm btn-primary btn-registrar-usu-wf">')
                            .html('<i class="bi bi-save"></i> Actualizar')
                    )
                );
                actualizarLblEstadoUsu($tr);
                $tb.append($tr);
            });
        }

        function cargarUsuariosWf() {
            const $tb = $('#tblUsuariosWfBody');
            $tb.html('<tr class="text-center"><td colspan="6" class="text-muted"><i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Cargando usuarios...</td></tr>');
            $.getJSON('adq_configuracion.php', { ajax_get_usuarios_wf: 1 }, function(res) {
                if (!res.success) {
                    $tb.html('<tr class="text-center"><td colspan="6" class="text-danger">' + escUsuariosWf(res.message || 'Error al cargar') + '</td></tr>');
                    return;
                }
                usuariosWfLoaded = true;
                usuariosWfCache = res.usuarios || [];
                filtrarUsuariosWf();
            }).fail(function() {
                usuariosWfCache = [];
                $tb.html('<tr class="text-center"><td colspan="6" class="text-danger">Error de red al cargar usuarios.</td></tr>');
            });
        }

        function registrarUsuarioWf($row) {
            const usuCod = parseInt($row.data('usu-cod'), 10) || 0;
            const prsCod = parseInt($row.data('prs-cod'), 10) || 0;
            const usuCed = String($row.data('usu-ced') || '');
            const telefono = $.trim($row.find('.input-usu-tel').val() || '');
            const correo = $.trim($row.find('.input-usu-cor').val() || '');
            const activo = $row.find('.chk-usu-wf').is(':checked');

            if (!usuCod || !prsCod) {
                msgUsuariosWf('danger', 'Usuario invalido.');
                return;
            }
            if (telefono === '' || correo === '') {
                msgUsuariosWf('danger', 'Debe ingresar telefono y correo para registrar.');
                $row.find(telefono === '' ? '.input-usu-tel' : '.input-usu-cor').focus();
                return;
            }
            const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo);
            if (!emailOk) {
                msgUsuariosWf('danger', 'El correo electronico no es valido.');
                $row.find('.input-usu-cor').focus();
                return;
            }

            const $btn = $row.find('.btn-registrar-usu-wf');
            const htmlOriginal = $btn.html();
            $btn.prop('disabled', true).html('<i class="glyphicon glyphicon-refresh glyphicon-spin"></i>');

            $.post('adq_configuracion.php', {
                ajax_save_usuario_wf: 1,
                Usu_Cod: usuCod,
                Prs_Cod: prsCod,
                Usu_Ced: usuCed,
                Telefono: telefono,
                Correo: correo,
                Usu_Wf: activo ? 'S' : 'N'
            }, function(res) {
                if (res.success) {
                    msgUsuariosWf('success', res.message || 'Usuario registrado correctamente.');
                    actualizarLblEstadoUsu($row);
                } else {
                    msgUsuariosWf('danger', res.message || 'No se pudo registrar el usuario.');
                }
            }, 'json').fail(function() {
                msgUsuariosWf('danger', 'Error de red al registrar el usuario.');
            }).always(function() {
                $btn.prop('disabled', false).html(htmlOriginal);
            });
        }

        $(document).ready(function() {
            $('#btnGuardarUsuariosDepto').on('click', guardarUsuariosDepto);
            $('#txtBuscarUsuarioDepto').on('keyup', filtrarUsuariosDepto);

            $(document).on('change', '#tblUsuariosWf .chk-usu-wf', function() {
                actualizarLblEstadoUsu($(this).closest('tr'));
            });
            $(document).on('click', '#tblUsuariosWf .btn-registrar-usu-wf', function() {
                registrarUsuarioWf($(this).closest('tr'));
            });
            $('#btnFiltrarUsuariosWf').on('click', filtrarUsuariosWf);
            $('#btnLimpiarFiltroUsuariosWf').on('click', function() {
                $('#txtFiltroUsuNombres').val('');
                $('#txtFiltroUsuCedula').val('');
                filtrarUsuariosWf();
            });
            $('#txtFiltroUsuNombres, #txtFiltroUsuCedula').on('keydown', function(e) {
                if (e.keyCode === 13) {
                    e.preventDefault();
                    filtrarUsuariosWf();
                }
            });

            $(document).on('click', '.btn-abrir-depto-usuarios', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const wdeCod = parseInt($btn.attr('data-wde-cod'), 10);
                const depNom = $btn.attr('data-dep-nom') || '';
                abrirDeptoUsuarios(wdeCod, depNom);
            });

            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            if (tab === 'disenador') {
                $('a[href="#builder-panel"]').tab('show');
                cargarDisenadorFlujos();
            } else if (tab === 'departamentos') {
                $('a[href="#deptos-panel"]').tab('show');
                cargarDepartamentos();
            } else if (tab === 'usuarios') {
                $('a[href="#usuarios-panel"]').tab('show');
                cargarUsuariosWf();
            } else if (tab === 'tipos') {
                $('a[href="#tipos-panel"]').tab('show');
                cargarTiposConfiguracion();
            } else {
                cargarTiposConfiguracion();
            }
        });
    </script>
</body>
</html>
