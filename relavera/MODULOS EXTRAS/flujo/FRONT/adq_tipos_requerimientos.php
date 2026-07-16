<?php
/**
 * EXA Adquisiciones - CRUD de Tipos de Requerimientos
 * Permite crear, editar, activar/desactivar tipos de requerimiento y asignarles un flujo modelo.
 * @author Oz <oz-agent@warp.dev>
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/wf_manager_log.php');

$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new MysqlDatos($obBD_conexion);
$wf_mgr = new wf_manager_log($Ses_Dat_Dis);

// Verificar acceso a la ventana 'configuracion' y pesta�a 'tipos_requerimientos'
if (!$wf_mgr->verificarAccesoVentana('configuracion', 'tipos_requerimientos')) {
    if (isset($ajax_save_tipo_req) || isset($ajax_toggle_tipo_req) || isset($ajax_get_tipo_req)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Acceso denegado. No tiene permisos para realizar esta acci�n.'));
        exit;
    } else {
        echo "<div class='alert alert-danger m-3'>Acceso denegado. No tiene permisos para ver esta ventana.</div>";
        exit;
    }
}

// Redirecci�n segura para navegaci�n directa del navegador (no AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['ajax_get_tipos']) && !isset($_GET['ajax_get_tipo_req'])) {
    header("Location: adq_configuracion.php?tab=tipos");
    exit;
}

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
        $obBD_con1->echoJson(array('success' => false, 'message' => 'El tiempo estimado debe ser un n�mero entero positivo.'));
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
}

// --- AJAX: Cambiar estado (Activar/Desactivar) ---
if (isset($ajax_toggle_tipo_req)) {
    $trq_cod = intval($_POST['Trq_Cod']);
    $nuevo_est = $_POST['Trq_Est'] === 'A' ? 'I' : 'A';
    $obBD_con1->grabarv_registros("UPDATE adq_tipos_requerimientos SET Trq_Est = '$nuevo_est' WHERE Trq_Cod = $trq_cod;", $obBD_conexion);
    $obBD_con1->echoJson(array('success' => true, 'nuevo_estado' => $nuevo_est));
}

// --- AJAX: Obtener datos de un tipo para editar ---
if (isset($ajax_get_tipo_req)) {
    $trq_cod = intval($_GET['Trq_Cod']);
    $row = $obBD_con1->getRowConsultaSql("SELECT * FROM adq_tipos_requerimientos WHERE Trq_Cod = $trq_cod;", $obBD_conexion);
    $obBD_con1->echoJson(array('success' => true, 'data' => $row));
}

// Cargar datos para la vista
$tipos = $obBD_con1->getArrayConsultaSql("
    SELECT t.*, w.Wfm_Nom 
    FROM adq_tipos_requerimientos t 
    INNER JOIN wf_flujos_modelos w ON w.Wfm_Cod = t.Wfm_Cod 
    WHERE t.Emp_Cod = $Ses_Emp_Cod 
    ORDER BY t.Trq_Des;", $obBD_conexion);

$flujos = $obBD_con1->getArrayConsultaSql("SELECT Wfm_Cod, Wfm_Nom FROM wf_flujos_modelos WHERE Emp_Cod = $Ses_Emp_Cod AND Wfm_Est = 'A' ORDER BY Wfm_Nom;", $obBD_conexion);

if (isset($ajax_get_tipos)) {
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
                                <td><?php echo $t['Trq_Req_Fac'] ? '✔' : '❌'; ?></td>
                                <td><?php echo $t['Trq_Req_Cot'] ? '✔' : '❌'; ?></td>
                                <td class="fw-bold"><?php echo $t['Trq_Min_Cot']; ?></td>
                                <td><?php echo $t['Trq_Req_Adj'] ? '✔' : '❌'; ?></td>
                                <td><?php echo $t['Trq_Req_Pre'] ? '✔' : '❌'; ?></td>
                                <td><?php echo $t['Trq_Req_Pro'] ? '✔' : '❌'; ?></td>
                                <td class="fw-bold text-secondary"><?php echo $t['Trq_Tiempo_Est'] !== null ? $t['Trq_Tiempo_Est'] . ' d�as' : '<span class="text-muted">-</span>'; ?></td>
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
                                    <option value="<?php echo $f['Wfm_Cod']; ?>"><?php echo $f['Wfm_Nom']; ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <h6 class="fw-bold text-muted border-bottom pb-1 mb-3">Requisitos obligatorios para avanzar</h6>
                        
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
                            <label class="form-check-label" for="chkDefineSla">Definir tiempo estimado (SLA)</label>
                        </div>

                        <div class="mb-3 ms-4" id="divSlaDays" style="display: none;">
                            <label class="form-label small fw-semibold">D�as estimados de resoluci�n</label>
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
        let modalTipo = null;

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
                    alert('Error al cargar datos: ' + res.message);
                }
            });
        }

        function guardarTipo(e) {
            e.preventDefault();
            const data = $('#frmTipoReq').serialize();
            $.post('adq_configuracion.php?ajax_save_tipo_req=1', data, function(res) {
                if (res.success) {
                    $('#mdlTipoReq').modal('hide');
                    alert('Tipo de requerimiento guardado con �xito.');
                    cargarTiposConfiguracion(); // Recargar pesta�a
                } else {
                    alert('Error al guardar: ' + res.message);
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
                    alert('Error al cambiar estado: ' + res.message);
                }
            }, 'json');
        }
    </script>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tipos de Requerimientos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light py-4">
    <div class="container bg-white p-4 rounded shadow-sm" style="max-width: 1000px;">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
            <h3 class="fw-bold m-0 text-primary"><i class="bi bi-tags"></i> Tipos de Requerimientos</h3>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-success" onclick="abrirFormulario()"><i class="bi bi-plus-lg"></i> Nuevo Tipo</button>
                <a href="adq_bandeja.php" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left"></i> Volver a Bandeja</a>
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
                        <th style="width: 80px;">Proveedor</th>
                        <th style="width: 80px;">Estado</th>
                        <th style="width: 120px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tipos)) { ?>
                        <tr class="text-center"><td colspan="10" class="text-muted py-4">No se han configurado tipos de requerimientos aún.</td></tr>
                    <?php } else {
                        foreach ($tipos as $t) {
                            $estBadge = $t['Trq_Est'] == 'A' ? 'success' : 'secondary';
                            $estLabel = $t['Trq_Est'] == 'A' ? 'Activo' : 'Inactivo';
                            ?>
                            <tr class="text-center">
                                <td class="fw-bold"><?php echo $t['Trq_Cod']; ?></td>
                                <td class="text-start fw-bold"><?php echo $t['Trq_Des']; ?></td>
                                <td class="text-start"><span class="badge bg-dark"><i class="bi bi-diagram-3"></i> <?php echo $t['Wfm_Nom']; ?></span></td>
                                <td><?php echo $t['Trq_Req_Fac'] ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-dash text-muted"></i>'; ?></td>
                                <td><?php echo $t['Trq_Req_Cot'] ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-dash text-muted"></i>'; ?></td>
                                <td class="fw-bold"><?php echo $t['Trq_Min_Cot']; ?></td>
                                <td><?php echo $t['Trq_Req_Adj'] ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-dash text-muted"></i>'; ?></td>
                                <td><?php echo $t['Trq_Req_Pro'] ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-dash text-muted"></i>'; ?></td>
                                <td><span class="badge bg-<?php echo $estBadge; ?>"><?php echo $estLabel; ?></span></td>
                                <td>
                                    <button class="btn btn-xs btn-outline-primary p-1 py-0" onclick="editarTipo(<?php echo $t['Trq_Cod']; ?>)" title="Editar"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-xs btn-outline-<?php echo $t['Trq_Est'] == 'A' ? 'warning' : 'success'; ?> p-1 py-0" 
                                            onclick="toggleEstado(<?php echo $t['Trq_Cod']; ?>, '<?php echo $t['Trq_Est']; ?>')" 
                                            title="<?php echo $t['Trq_Est'] == 'A' ? 'Desactivar' : 'Activar'; ?>">
                                        <i class="bi bi-<?php echo $t['Trq_Est'] == 'A' ? 'pause-circle' : 'play-circle'; ?>"></i>
                                    </button>
                                </td>
                            </tr>
                    <?php }
                    } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Formulario -->
    <div class="modal fade" id="mdlTipoReq" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title fw-bold" id="lblTipoReqTitle">Nuevo Tipo de Requerimiento</h4>
                </div>
                <div class="modal-body">
                    <form id="frmTipoReq">
                        <input type="hidden" id="trqCod" name="Trq_Cod">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Descripción / Nombre del Tipo *</label>
                            <input type="text" class="form-control" id="trqDes" name="Trq_Des" required placeholder="Ej. Compra de Bienes y Equipos">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Flujo Modelo Asignado *</label>
                            <select class="form-control" id="trqWfm" name="Wfm_Cod" required>
                                <option value="">[Seleccione un flujo]</option>
                                <?php foreach ($flujos as $f) { ?>
                                    <option value="<?php echo $f['Wfm_Cod']; ?>"><?php echo $f['Wfm_Nom']; ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <hr>
                        <h6 class="fw-bold text-muted mb-3">Configuración de Requisitos</h6>

                        <div class="row g-3">
                            <div class="col-6">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="trqReqFac" name="Trq_Req_Fac" value="1" checked>
                                    <label class="form-check-label">Requiere Factura</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="trqPerCie" name="Trq_Per_Cie" value="1">
                                    <label class="form-check-label">Permite cierre sin factura</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="trqReqCot" name="Trq_Req_Cot" value="1" checked>
                                    <label class="form-check-label">Requiere Cotizaciones</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label" style="font-size: 12px;">Mínimo Cotizaciones</label>
                                <input type="number" class="form-control form-control-sm" id="trqMinCot" name="Trq_Min_Cot" value="1" min="1" max="10">
                            </div>
                            <div class="col-6">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="trqReqAdj" name="Trq_Req_Adj" value="1">
                                    <label class="form-check-label">Requiere Adjuntos Iniciales</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="trqReqPro" name="Trq_Req_Pro" value="1">
                                    <label class="form-check-label">Requiere Proveedor Sugerido</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="trqReqPre" name="Trq_Req_Pre" value="1">
                                    <label class="form-check-label">Verificar Presupuesto</label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="guardarTipoReq()"><i class="bi bi-save"></i> Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let mdlTipoReq = null;

        function abrirFormulario() {
            $('#lblTipoReqTitle').text('Nuevo Tipo de Requerimiento');
            $('#frmTipoReq')[0].reset();
            $('#trqCod').val('');
            $('#trqReqFac').prop('checked', true);
            $('#trqReqCot').prop('checked', true);
            $('#trqMinCot').val(1);

            mdlTipoReq = new bootstrap.Modal(document.getElementById('mdlTipoReq'));
            mdlTipoReq.show();
        }

        function editarTipo(trqCod) {
            $.getJSON('', { ajax_get_tipo_req: true, Trq_Cod: trqCod }, function(res) {
                if (res.success) {
                    const d = res.data;
                    $('#lblTipoReqTitle').text('Editar Tipo de Requerimiento');
                    $('#trqCod').val(d.Trq_Cod);
                    $('#trqDes').val(d.Trq_Des);
                    $('#trqWfm').val(d.Wfm_Cod);
                    $('#trqReqFac').prop('checked', parseInt(d.Trq_Req_Fac) === 1);
                    $('#trqPerCie').prop('checked', parseInt(d.Trq_Per_Cie) === 1);
                    $('#trqReqCot').prop('checked', parseInt(d.Trq_Req_Cot) === 1);
                    $('#trqMinCot').val(d.Trq_Min_Cot);
                    $('#trqReqPre').prop('checked', parseInt(d.Trq_Req_Pre) === 1);
                    $('#trqReqAdj').prop('checked', parseInt(d.Trq_Req_Adj) === 1);
                    $('#trqReqPro').prop('checked', parseInt(d.Trq_Req_Pro) === 1);

                    mdlTipoReq = new bootstrap.Modal(document.getElementById('mdlTipoReq'));
                    mdlTipoReq.show();
                }
            });
        }

        function guardarTipoReq() {
            const des = $('#trqDes').val().trim();
            const wfm = $('#trqWfm').val();
            if (!des || !wfm) {
                alert('Complete el nombre y seleccione un flujo modelo.');
                return;
            }

            $.post('?ajax_save_tipo_req=1', $('#frmTipoReq').serialize(), function(res) {
                if (res.success) {
                    alert('Tipo de Requerimiento guardado correctamente.');
                    window.location.reload();
                } else {
                    alert('Error: ' + res.message);
                }
            }, 'json');
        }

        function toggleEstado(trqCod, estActual) {
            const accion = estActual === 'A' ? 'desactivar' : 'activar';
            if (!confirm(`¿Desea ${accion} este tipo de requerimiento?`)) return;

            $.post('?ajax_toggle_tipo_req=1', { Trq_Cod: trqCod, Trq_Est: estActual }, function(res) {
                if (res.success) {
                    window.location.reload();
                } else {
                    alert('Error al cambiar estado.');
                }
            }, 'json');
        }
    </script>
</body>
</html>
