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

    if (empty($trq_des) || empty($wfm_cod)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'El nombre y el flujo modelo son obligatorios.'));
        exit;
    }

    try {
        if ($trq_cod) {
            // Actualizar
            $sql = "UPDATE adq_tipos_requerimientos SET 
                Wfm_Cod = $wfm_cod, Trq_Des = '$trq_des', Trq_Req_Fac = $trq_req_fac, 
                Trq_Per_Cie = $trq_per_cie, Trq_Req_Cot = $trq_req_cot, Trq_Min_Cot = $trq_min_cot, 
                Trq_Req_Pre = $trq_req_pre, Trq_Req_Adj = $trq_req_adj, Trq_Req_Pro = $trq_req_pro
                WHERE Trq_Cod = $trq_cod;";
        } else {
            // Crear
            $sql = "INSERT INTO adq_tipos_requerimientos (Emp_Cod, Wfm_Cod, Trq_Des, Trq_Req_Fac, Trq_Per_Cie, Trq_Req_Cot, Trq_Min_Cot, Trq_Req_Pre, Trq_Req_Adj, Trq_Req_Pro, Trq_Est) 
                    VALUES ($Ses_Emp_Cod, $wfm_cod, '$trq_des', $trq_req_fac, $trq_per_cie, $trq_req_cot, $trq_min_cot, $trq_req_pre, $trq_req_adj, $trq_req_pro, 'A');";
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
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle" id="tblTipos">
                <thead class="table-light">
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
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="lblTipoReqTitle">Nuevo Tipo de Requerimiento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                            <select class="form-select" id="trqWfm" name="Wfm_Cod" required>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
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
