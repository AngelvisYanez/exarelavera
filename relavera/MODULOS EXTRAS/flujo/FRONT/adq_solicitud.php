<?php
/**
 * EXA Adquisiciones - Registro de Solicitudes
 * @author Oz <oz-agent@warp.dev>
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/adq_adquisiciones_log.php');

$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new adq_adquisiciones_log($obBD_conexion);

// Manejo de llamadas AJAX
if (isset($ajax_save_solicitud)) {
    // Formatear items recibidos
    $items = array();
    if (isset($_POST['items'])) {
        $items = $_POST['items'];
    }

    // Formatear cotizaciones cargadas si aplica
    $cotizaciones = array();
    if (isset($_POST['cotizaciones'])) {
        $cotizaciones = $_POST['cotizaciones'];
    }

    // Si hay archivos cargados para cotización, moverlos a un directorio seguro
    if (isset($_FILES['cotizacion_archivos'])) {
        $target_dir = "../../DATA/adquisiciones_sustentos/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        foreach ($_FILES['cotizacion_archivos']['name'] as $idx => $name) {
            if ($_FILES['cotizacion_archivos']['error'][$idx] == 0) {
                $tmp_name = $_FILES['cotizacion_archivos']['tmp_name'][$idx];
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $unique_name = "cot_" . uniqid() . "." . $ext;
                $target_file = $target_dir . $unique_name;
                if (move_uploaded_file($tmp_name, $target_file)) {
                    $cot_index = $_POST['cot_index'][$idx];
                    $cotizaciones[$cot_index]['Cot_Adj'] = "adquisiciones_sustentos/" . $unique_name;
                }
            }
        }
    }

    $_POST['Emp_Cod'] = $Ses_Emp_Cod;
    $_POST['Suc_Cod'] = $Ses_Suc_Cod;

    $resp = $obBD_con1->guardarSolicitud($_POST, $items, $cotizaciones);
    $obBD_con1->echoJson($resp);
}

if (isset($ajax_get_trq_details)) {
    $trq_cod = intval($_GET['trq_cod']);
    $trq = $obBD_con1->getRowConsultaSql("SELECT * FROM adq_tipos_requerimientos WHERE Trq_Cod = $trq_cod;", $obBD_conexion);
    $obBD_con1->echoJson(array('success' => true, 'data' => $trq));
}

// Cargar catálogos iniciales
$tipos_req = $obBD_con1->getArrayConsultaSql("SELECT Trq_Cod, Trq_Des FROM adq_tipos_requerimientos WHERE Emp_Cod = $Ses_Emp_Cod AND Trq_Est = 'A' ORDER BY Trq_Des;", $obBD_conexion);
$centros_costo = $obBD_con1->getArrayConsultaSql("SELECT DISTINCT Dep_Cdc AS Cdc_Cod FROM departamen WHERE Emp_Cod = $Ses_Emp_Cod AND Dep_Cdc IS NOT NULL AND Dep_Cdc <> '';", $obBD_conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Solicitud de Adquisición</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body class="bg-light py-4">
    <div class="container bg-white p-4 rounded shadow-sm" style="max-width: 900px;">
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
            <h4 class="fw-bold text-primary m-0"><i class="bi bi-file-earmark-plus"></i> Registro de Adquisición</h4>
            <a href="adq_bandeja.php" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left"></i> Volver a mi Bandeja</a>
        </div>

        <form id="frmSolicitud" method="POST" enctype="multipart/form-data">
            <div class="row g-3 mb-4">
                <!-- Tipo de Requerimiento -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Tipo de Requerimiento *</label>
                    <select class="form-select" id="Trq_Cod" name="Trq_Cod" required onchange="cargarConfiguracionTipo(this.value)">
                        <option value="">[Seleccione un Tipo]</option>
                        <?php foreach ($tipos_req as $tr) { ?>
                            <option value="<?php echo $tr['Trq_Cod']; ?>"><?php echo $tr['Trq_Des']; ?></option>
                        <?php } ?>
                    </select>
                </div>

                <!-- Prioridad -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Prioridad *</label>
                    <select class="form-select" id="Sol_Pri" name="Sol_Pri" required>
                        <option value="BAJA">Baja</option>
                        <option value="MEDIA" selected>Media</option>
                        <option value="ALTA">Alta</option>
                        <option value="URGENTE">Urgente</option>
                    </select>
                </div>

                <!-- Centro de costo -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Centro de Costo</label>
                    <select class="form-select" id="Cdc_Cod" name="Cdc_Cod">
                        <option value="">[Ninguno]</option>
                        <?php foreach ($centros_costo as $cc) { ?>
                            <option value="<?php echo $cc['Cdc_Cod']; ?>"><?php echo $cc['Cdc_Cod']; ?></option>
                        <?php } ?>
                    </select>
                </div>

                <!-- Proveedor Sugerido -->
                <div class="col-md-6" id="divProveedorSugerido" style="display: none;">
                    <label class="form-label fw-semibold">Proveedor Sugerido</label>
                    <select class="form-select select2-ajax" id="Prv_Sug" name="Prv_Sug" style="width: 100%;">
                        <option value=""></option>
                    </select>
                </div>

                <!-- Justificación -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Justificación *</label>
                    <textarea class="form-control" id="Sol_Jus" name="Sol_Jus" rows="2" placeholder="Redacte el motivo comercial de la solicitud..." required></textarea>
                </div>

                <!-- Descripción detallada -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Descripción del Pedido *</label>
                    <textarea class="form-control" id="Sol_Det" name="Sol_Det" rows="3" placeholder="Detalle técnico general del requerimiento..." required></textarea>
                </div>
            </div>

            <!-- Tabla de Ítems / Líneas de Pedido -->
            <div class="mb-4">
                <h6 class="fw-bold text-muted border-bottom pb-2 mb-3"><i class="bi bi-cart"></i> Artículos / Servicios del Requerimiento</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle" id="tblItems">
                        <thead class="table-light">
                            <tr class="text-center font-monospace" style="font-size: 12px;">
                                <th style="width: 50px;">#</th>
                                <th>Artículo / Descripción Técnica Libre *</th>
                                <th style="width: 120px;">Cant *</th>
                                <th style="width: 150px;">Valor Unit. Est. *</th>
                                <th style="width: 150px;">Total Est.</th>
                                <th style="width: 50px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Las líneas se inyectan por JS -->
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-xs btn-outline-primary" onclick="agregarLinea()"><i class="bi bi-plus-circle"></i> Agregar Ítem</button>
                
                <div class="d-flex justify-content-end mt-3 border-top pt-2">
                    <span class="fs-5 fw-bold text-dark">Valor Total Estimado: $ <span id="lblTotalEstimado">0.00</span></span>
                    <input type="hidden" id="Sol_Val_Est" name="Sol_Val_Est" value="0.00">
                </div>
            </div>

            <!-- Sección de Cotizaciones Múltiples (Sustentos) -->
            <div class="mb-4" id="divCotizaciones" style="display: none;">
                <h6 class="fw-bold text-muted border-bottom pb-2 mb-3"><i class="bi bi-file-earmark-pdf"></i> Cotizaciones Físicas (Mínimo <span id="lblMinCot">1</span> Requeridas)</h6>
                <div class="row g-3" id="cotizacionesList">
                    <!-- Se inyectan cotizaciones dinámicas -->
                </div>
                <button type="button" class="btn btn-xs btn-outline-secondary mt-3" onclick="agregarCotizacionHTML()"><i class="bi bi-paperclip"></i> Añadir Cotización Física</button>
            </div>

            <!-- Enviar formulario -->
            <div class="border-top pt-3 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" onclick="limpiarFormulario()">Limpiar</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-send-check"></i> Enviar a Workflow</button>
            </div>
        </form>
    </div>

    <!-- Script del validador de adquisición -->
    <script src="../VALIDACIONES/adq_solicitud.js"></script>
</body>
</html>
