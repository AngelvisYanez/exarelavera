<?php
/**
 * EXA Adquisiciones - Registro de Solicitudes
 * @author Oz <oz-agent@warp.dev>
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/adq_adquisiciones_log.php');

$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new adq_adquisiciones_log($obBD_conexion);
require_once('../LOGICA/wf_manager_log.php');
$wf_mgr = new wf_manager_log($Ses_Dat_Dis);

$ajax_save_solicitud = isset($_GET['ajax_save_solicitud']) ? $_GET['ajax_save_solicitud'] : (isset($_POST['ajax_save_solicitud']) ? $_POST['ajax_save_solicitud'] : null);
$ajax_save_borrador = isset($_GET['ajax_save_borrador']) ? $_GET['ajax_save_borrador'] : (isset($_POST['ajax_save_borrador']) ? $_POST['ajax_save_borrador'] : null);
$ajax_get_trq_details = isset($_GET['ajax_get_trq_details']) ? $_GET['ajax_get_trq_details'] : null;
$ajax_get_borrador = isset($_GET['ajax_get_borrador']) ? $_GET['ajax_get_borrador'] : null;
$ajax_search_proveedores = isset($_GET['ajax_search_proveedores']) ? $_GET['ajax_search_proveedores'] : null;
$ajax_save_proveedor = isset($_POST['ajax_save_proveedor']) ? $_POST['ajax_save_proveedor'] : null;

// Verificar acceso a la ventana 'bandeja' y pestaña 'crear_solicitud'
if (!$wf_mgr->verificarAccesoVentana('bandeja', 'crear_solicitud')) {
    if (isset($ajax_save_solicitud) || isset($ajax_save_borrador) || isset($ajax_get_trq_details) || isset($ajax_get_borrador) || isset($ajax_search_proveedores) || isset($ajax_save_proveedor)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Acceso denegado. No tiene permisos para realizar esta acción.'));
        exit;
    } else {
        echo "<div class='alert alert-danger m-3'>Acceso denegado. No tiene permisos para ver esta ventana.</div>";
        exit;
    }
}

// Redirección segura para navegación directa del navegador (no AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['ajax_get_form']) && !isset($_GET['ajax_get_trq_details']) && !isset($_GET['ajax_get_borrador']) && !isset($_GET['ajax_search_proveedores']) && !isset($_GET['ajax_save_proveedor']) && !isset($_GET['ajax_save_solicitud']) && !isset($_GET['ajax_save_borrador'])) {
    header("Location: adq_bandeja.php?tab=crear_solicitud");
    exit;
}

// Manejo de llamadas AJAX
if (isset($ajax_save_solicitud) || isset($ajax_save_borrador)) {
    $items = array();
    if (isset($_POST['items'])) {
        $items = $_POST['items'];
    }

    $cotizaciones = array();
    if (isset($_POST['cotizaciones'])) {
        $cotizaciones = $_POST['cotizaciones'];
    }

    $cotizaciones_existentes = array();
    if (isset($_POST['cotizaciones_existentes'])) {
        $cotizaciones_existentes = $_POST['cotizaciones_existentes'];
    }

    $cot_eliminar = array();
    if (isset($_POST['cot_eliminar'])) {
        $cot_eliminar = is_array($_POST['cot_eliminar']) ? $_POST['cot_eliminar'] : array($_POST['cot_eliminar']);
    }

    $target_dir = "../../DATA/adquisiciones_sustentos/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (isset($_FILES['cotizacion_archivos'])) {
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

    if (isset($_FILES['cotizacion_archivos_existentes'])) {
        foreach ($_FILES['cotizacion_archivos_existentes']['name'] as $sco_cod => $name) {
            if ($_FILES['cotizacion_archivos_existentes']['error'][$sco_cod] == 0) {
                $tmp_name = $_FILES['cotizacion_archivos_existentes']['tmp_name'][$sco_cod];
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $unique_name = "cot_" . uniqid() . "." . $ext;
                $target_file = $target_dir . $unique_name;
                if (move_uploaded_file($tmp_name, $target_file)) {
                    if (!isset($cotizaciones_existentes[$sco_cod])) {
                        $cotizaciones_existentes[$sco_cod] = array();
                    }
                    $cotizaciones_existentes[$sco_cod]['Cot_Adj'] = "adquisiciones_sustentos/" . $unique_name;
                }
            }
        }
    }

    $_POST['Emp_Cod'] = $Ses_Emp_Cod;
    $_POST['Suc_Cod'] = $Ses_Suc_Cod;

    if (isset($ajax_save_borrador)) {
        $resp = $obBD_con1->guardarBorrador($_POST, $items, $cotizaciones, $cotizaciones_existentes, $cot_eliminar);
    } else {
        $resp = $obBD_con1->guardarSolicitud($_POST, $items, $cotizaciones, $cotizaciones_existentes, $cot_eliminar);
    }
    $obBD_con1->echoJson($resp);
    exit;
}

if (isset($ajax_get_borrador)) {
    $sol_cod = intval($_GET['sol_cod']);
    $usu_sol = isset($Ses_Usu_Cod) ? intval($Ses_Usu_Cod) : 0;
    $resp = $obBD_con1->obtenerBorradorParaEdicion($sol_cod, $Ses_Emp_Cod, $usu_sol);
    $obBD_con1->echoJson($resp);
    exit;
}

if (isset($ajax_get_trq_details)) {
    $trq_cod = intval($_GET['trq_cod']);
    $trq = $obBD_con1->getRowConsultaSql("SELECT * FROM adq_tipos_requerimientos WHERE Trq_Cod = $trq_cod;", $obBD_conexion);
    $obBD_con1->echoJson(array('success' => true, 'data' => $trq));
    exit;
}

if (isset($ajax_search_proveedores)) {
    $search = isset($_GET['q']) ? mysqli_real_escape_string($obBD_conexion->conexion, $_GET['q']) : '';
    $sql = "SELECT p.Prv_Cod, per.Prs_Ced, per.Prs_Nom, per.Prs_Ape, p.Prv_Com 
            FROM proveedore p
            INNER JOIN persona per ON per.Prs_Cod = p.Prs_Cod
            WHERE p.Emp_Cod = $Ses_Emp_Cod 
              AND (per.Prs_Ced LIKE '%$search%' OR per.Prs_Ape LIKE '%$search%' OR per.Prs_Nom LIKE '%$search%' OR p.Prv_Com LIKE '%$search%')
            ORDER BY per.Prs_Ape, per.Prs_Nom
            LIMIT 30;";
    $rows = $obBD_con1->getArrayConsultaSql($sql, $obBD_conexion);
    
    $results = array();
    foreach ($rows as $r) {
        $nombre = trim($r['Prs_Ape'] . ' ' . $r['Prs_Nom']);
        if (!empty($r['Prv_Com'])) {
            $nombre .= " (" . $r['Prv_Com'] . ")";
        }
        $nombre .= " - RUC: " . $r['Prs_Ced'];
        
        $results[] = array(
            'id' => $r['Prv_Cod'],
            'text' => $nombre
        );
    }
    $obBD_con1->echoJson($results);
    exit;
}

if (isset($ajax_save_proveedor)) {
    $prs_ced = mysqli_real_escape_string($obBD_conexion->conexion, trim($_POST['Prs_Ced']));
    $prs_ape = mysqli_real_escape_string($obBD_conexion->conexion, trim($_POST['Prs_Ape']));
    $prv_com = mysqli_real_escape_string($obBD_conexion->conexion, trim($_POST['Prv_Com']));
    $prv_cor = mysqli_real_escape_string($obBD_conexion->conexion, trim($_POST['Prv_Cor']));
    $prv_tel = mysqli_real_escape_string($obBD_conexion->conexion, trim($_POST['Prv_Tel']));

    if (empty($prs_ced) || empty($prs_ape)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'El RUC/Cédula y la Razón Social son obligatorios.'));
        exit;
    }

    // Verificar si ya existe el proveedor
    $sql_check = "SELECT p.Prv_Cod FROM proveedore p 
                  INNER JOIN persona per ON per.Prs_Cod = p.Prs_Cod 
                  WHERE per.Prs_Ced = '$prs_ced' AND p.Emp_Cod = $Ses_Emp_Cod LIMIT 1;";
    $exist_prv = $obBD_con1->getRowConsultaSql($sql_check, $obBD_conexion);
    if ($exist_prv) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'El proveedor con este RUC/Cédula ya se encuentra registrado.'));
        exit;
    }

    // Obtener un Ciu_Cod válido
    $row_ciu = $obBD_con1->getRowConsultaSql("SELECT Ciu_Cod FROM ciudad LIMIT 1;", $obBD_conexion);
    $ciu_cod = $row_ciu ? intval($row_ciu['Ciu_Cod']) : 217;

    $ide_cod = (strlen($prs_ced) === 10) ? 2 : 1;
    $prv_tic = (strlen($prs_ced) === 10) ? 'N' : 'J';

    // Verificar si ya existe la persona
    $sql_pers = "SELECT Prs_Cod FROM persona WHERE Prs_Ced = '$prs_ced' LIMIT 1;";
    $exist_pers = $obBD_con1->getRowConsultaSql($sql_pers, $obBD_conexion);

    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        if ($exist_pers) {
            $prs_cod = intval($exist_pers['Prs_Cod']);
            // Actualizar persona si es necesario
            $sql_upd_pers = "UPDATE persona SET Prs_Ape = '$prs_ape', Prs_Cor = '$prv_cor', Prs_Tel = '$prv_tel' WHERE Prs_Cod = $prs_cod;";
            $obBD_con1->grabarv_registros($sql_upd_pers, $obBD_conexion);
        } else {
            // Insertar persona
            $sql_ins_pers = "INSERT INTO persona (Prs_Ced, Prs_Nom, Prs_Ape, Prs_Sex, Prs_Dir, Prs_Tel, Prs_Cel, Ciu_Cod, Ide_Cod, Prs_Cor, Prs_Est) 
                             VALUES ('$prs_ced', '', '$prs_ape', 'M', 'S/N', '$prv_tel', '', $ciu_cod, $ide_cod, '$prv_cor', 'A');";
            $obBD_con1->grabarv_registros($sql_ins_pers, $obBD_conexion);
            $prs_cod = $obBD_con1->insercionid($obBD_conexion);
        }

        // Insertar proveedor
        $final_com = !empty($prv_com) ? $prv_com : $prs_ape;
        $sql_ins_prv = "INSERT INTO proveedore (Emp_Cod, Prs_Cod, Prv_Com, Prv_Tic, Prv_Tel, Prv_Cor, Prv_Esp, Prv_Con, Prv_Reg, Prv_Ris, Prv_Gct, Prv_Rim_Emp, Prv_Rim_Np, Prv_Ag_Ret, Prv_Est) 
                        VALUES ($Ses_Emp_Cod, $prs_cod, '$final_com', '$prv_tic', '$prv_tel', '$prv_cor', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'A');";
        $obBD_con1->grabarv_registros($sql_ins_prv, $obBD_conexion);
        $prv_cod = $obBD_con1->insercionid($obBD_conexion);

        $obBD_con1->commit_nomsn($obBD_conexion);
        
        $nombre_completo = $prs_ape;
        if (!empty($prv_com)) {
            $nombre_completo .= " (" . $prv_com . ")";
        }
        $nombre_completo .= " - RUC: " . $prs_ced;

        $obBD_con1->echoJson(array(
            'success' => true, 
            'id' => $prv_cod, 
            'text' => $nombre_completo
        ));
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
    exit;
}

// Cargar catálogos iniciales
$tipos_req = $obBD_con1->getArrayConsultaSql("SELECT Trq_Cod, Trq_Des FROM adq_tipos_requerimientos WHERE Emp_Cod = $Ses_Emp_Cod AND Trq_Est = 'A' ORDER BY Trq_Des;", $obBD_conexion);
$centros_costo = $obBD_con1->getArrayConsultaSql("SELECT DISTINCT Dep_Cdc AS Cdc_Cod FROM departamen WHERE Emp_Cod = $Ses_Emp_Cod AND Dep_Cdc IS NOT NULL AND Dep_Cdc <> '';", $obBD_conexion);

if (isset($ajax_get_form)) {
    header('Content-Type: text/html; charset=UTF-8');
    ?>
    <style>
        /* Estilos de mejora visual para el formulario de creación */
        .adq-step-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            position: relative;
        }
        .adq-step-badge {
            position: absolute;
            top: -12px;
            left: 20px;
            background: #1e40af;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: 0 2px 5px rgba(30,64,175,0.3);
        }
        .adq-step-title {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            margin-top: 5px;
            margin-bottom: 15px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 8px;
        }
        .adq-step-title i {
            color: #3b82f6;
            margin-right: 6px;
        }
        .form-label-req {
            font-weight: 600;
            font-size: 12px;
            color: #475569;
            margin-bottom: 5px;
        }
        .form-control-adq {
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            font-size: 13px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control-adq:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
            outline: none;
        }
        .table-items-adq {
            font-size: 12px;
        }
        .table-items-adq th {
            background-color: #f8fafc !important;
            color: #475569 !important;
            font-weight: 600 !important;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.03em;
            padding: 10px !important;
        }
        .table-items-adq td {
            padding: 8px !important;
            vertical-align: middle !important;
        }
        .lbl-total-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 20px;
            display: inline-block;
        }
    </style>

    <div class="p-1">
        <div id="bannerEdicionBorrador" class="alert alert-info py-2 px-3 mb-3" style="display: none; font-size: 12px;">
            <i class="bi bi-pencil-square"></i> <strong>Completando borrador</strong> <span id="lblBorradorNum"></span>.
            Puede guardar sin cotizaciones y enviar a aprobacion cuando este listo.
        </div>
        <form id="frmSolicitud" method="POST" enctype="multipart/form-data">
            <input type="hidden" id="Sol_Cod" name="Sol_Cod" value="">
            <div id="cotEliminarContainer"></div>
            
            <!-- PASO 1: Información General -->
            <div class="adq-step-card">
                <span class="adq-step-badge">Paso 1</span>
                <h5 class="adq-step-title"><i class="bi bi-info-circle-fill"></i> Información General de la Solicitud</h5>
                
                <div class="row">
                    <!-- Tipo de Requerimiento -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label-req">Tipo de Requerimiento *</label>
                        <select class="form-control form-control-adq" id="Trq_Cod" name="Trq_Cod" required onchange="cargarConfiguracionTipo(this.value)">
                            <option value="">[Seleccione un Tipo]</option>
                            <?php foreach ($tipos_req as $tr) { ?>
                                <option value="<?php echo $tr['Trq_Cod']; ?>"><?php echo $tr['Trq_Des']; ?></option>
                            <?php } ?>
                        </select>
                        <span class="text-muted" style="font-size: 10px; display: block; margin-top: 3px;">Determina el flujo de aprobaciones que seguirá la solicitud.</span>
                    </div>

                    <!-- Prioridad -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label-req">Prioridad de la Compra *</label>
                        <select class="form-control form-control-adq" id="Sol_Pri" name="Sol_Pri" required>
                            <option value="BAJA">Baja</option>
                            <option value="MEDIA" selected>Media</option>
                            <option value="ALTA">Alta</option>
                            <option value="URGENTE">Urgente</option>
                        </select>
                    </div>

                    <!-- Centro de costo -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label-req">Centro de Costo Imputable</label>
                        <select class="form-control form-control-adq" id="Cdc_Cod" name="Cdc_Cod">
                            <option value="">[Ninguno / No aplica]</option>
                            <?php foreach ($centros_costo as $cc) { ?>
                                <option value="<?php echo $cc['Cdc_Cod']; ?>"><?php echo $cc['Cdc_Cod']; ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <!-- Requisitos de esta solicitud -->
                    <div class="col-12 mb-3" id="divRequisitosSolicitud" style="display: none;">
                        <div class="p-3 rounded border" style="background: #f8fafc; border-color: #e2e8f0 !important;">
                            <h6 class="fw-bold text-primary mb-2" style="font-size: 13px;"><i class="bi bi-sliders"></i> Requisitos de esta solicitud</h6>
                            <p class="text-muted mb-3" style="font-size: 10px;">Se precargan desde el tipo seleccionado. Puede ajustarlos para este caso sin crear otro tipo de requerimiento.</p>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" id="Sol_Req_Fac" name="Sol_Req_Fac" value="1" onchange="syncReqConfigFromForm()">
                                        <label class="form-check-label small" for="Sol_Req_Fac">Factura de compra al cierre</label>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" id="Sol_Per_Cie" name="Sol_Per_Cie" value="1" onchange="syncReqConfigFromForm()">
                                        <label class="form-check-label small" for="Sol_Per_Cie">Permitir cierre parcial de items</label>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" id="Sol_Req_Cot" name="Sol_Req_Cot" value="1" onchange="toggleMinCotizaciones(); syncReqConfigFromForm();">
                                        <label class="form-check-label small" for="Sol_Req_Cot">Cotizaciones de sustento obligatorias</label>
                                    </div>
                                    <div class="ms-4 mb-2" id="divSolMinCot" style="display: none;">
                                        <label class="form-label-req small mb-1">Minimo de cotizaciones</label>
                                        <input type="number" class="form-control form-control-sm form-control-adq" id="Sol_Min_Cot" name="Sol_Min_Cot" min="1" value="1" style="width: 100px;" onchange="syncReqConfigFromForm(); aplicarReglasCotizaciones();">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" id="Sol_Req_Pre" name="Sol_Req_Pre" value="1" onchange="syncReqConfigFromForm()">
                                        <label class="form-check-label small" for="Sol_Req_Pre">Verificar disponibilidad presupuestaria</label>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" id="Sol_Req_Adj" name="Sol_Req_Adj" value="1" onchange="syncReqConfigFromForm()">
                                        <label class="form-check-label small" for="Sol_Req_Adj">Archivos adjuntos obligatorios</label>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" id="Sol_Req_Pro" name="Sol_Req_Pro" value="1" onchange="toggleProveedorSugerido(); syncReqConfigFromForm();">
                                        <label class="form-check-label small" for="Sol_Req_Pro">Proveedor sugerido obligatorio</label>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" id="Sol_Define_Sla" name="Sol_Define_Sla" value="1" onchange="toggleSlaDias(); syncReqConfigFromForm();">
                                        <label class="form-check-label small" for="Sol_Define_Sla">Definir tiempo estimado (SLA)</label>
                                    </div>
                                    <div class="ms-4" id="divSolTiempoEst" style="display: none;">
                                        <label class="form-label-req small mb-1">Dias estimados de resolucion</label>
                                        <input type="number" class="form-control form-control-sm form-control-adq" id="Sol_Tiempo_Est" name="Sol_Tiempo_Est" min="1" style="width: 120px;" onchange="syncReqConfigFromForm()">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Proveedor Sugerido -->
                    <div class="col-md-6 mb-3" id="divProveedorSugerido" style="display: none;">
                        <label class="form-label-req">Proveedor Sugerido *</label>
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <div style="flex: 1; min-width: 0;">
                                <select class="form-control select2-ajax" id="Prv_Sug" name="Prv_Sug" style="width: 100%;">
                                    <option value=""></option>
                                </select>
                            </div>
                            <button type="button" class="btn btn-sm btn-success" onclick="abrirModalNuevoProveedor('sugerido')" title="Agregar Nuevo Proveedor" style="height: 32px; padding: 0 10px; display: flex; align-items: center; justify-content: center; background-color: #10b981; border-color: #10b981; color: white; border-radius: 6px;"><i class="bi bi-plus-lg" style="font-size: 14px; font-weight: bold;"></i></button>
                        </div>
                    </div>

                    <!-- Justificación -->
                    <div class="col-12 mb-3">
                        <label class="form-label-req">Justificación Comercial *</label>
                        <textarea class="form-control form-control-adq" id="Sol_Jus" name="Sol_Jus" rows="2" placeholder="Explique brevemente por qué es necesaria esta adquisición..." required></textarea>
                    </div>

                    <!-- Descripción detallada -->
                    <div class="col-12 mb-2">
                        <label class="form-label-req">Descripción Detallada del Pedido *</label>
                        <textarea class="form-control form-control-adq" id="Sol_Det" name="Sol_Det" rows="3" placeholder="Indique especificaciones técnicas, marcas, modelos o detalles específicos..." required></textarea>
                    </div>
                </div>
            </div>

            <!-- PASO 2: Artículos o Servicios -->
            <div class="adq-step-card">
                <span class="adq-step-badge">Paso 2</span>
                <h5 class="adq-step-title"><i class="bi bi-cart-fill"></i> Artículos / Servicios Requeridos</h5>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-condensed table-items-adq" id="tblItems">
                        <thead>
                            <tr class="text-center">
                                <th style="width: 40px; text-align: center;">#</th>
                                <th>Artículo / Descripción Técnica</th>
                                <th style="width: 100px; text-align: center;">Cantidad</th>
                                <th style="width: 140px; text-align: right;">P. Unit. Est. ($)</th>
                                <th style="width: 100px; text-align: center;">Lleva IVA</th>
                                <th style="width: 140px; text-align: right;">Total Est. ($)</th>
                                <th style="width: 50px; text-align: center;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Las líneas se inyectan por JS -->
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <button type="button" class="btn btn-sm btn-primary fw-bold" onclick="agregarLinea()"><i class="bi bi-plus-circle"></i> Agregar Ítem / Fila</button>
                    
                    <div class="lbl-total-box text-end">
                        <span class="text-muted small fw-bold d-block">VALOR TOTAL ESTIMADO</span>
                        <span class="fs-4 fw-bold text-success">$ <span id="lblTotalEstimado">0.00</span></span>
                        <input type="hidden" id="Sol_Val_Est" name="Sol_Val_Est" value="0.00">
                    </div>
                </div>
            </div>

            <!-- PASO 3: Cotizaciones de Sustento -->
            <div class="adq-step-card" id="divCotizaciones">
                <span class="adq-step-badge">Paso 3</span>
                <h5 class="adq-step-title"><i class="bi bi-file-earmark-pdf-fill"></i> Sustento de Cotizaciones Físicas</h5>
                
                <!-- Estado 1: Inicial (Sin Tipo de Requerimiento seleccionado) -->
                <div id="cotizacionesStateInitial" class="text-center p-4" style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px;">
                    <i class="bi bi-file-earmark-lock text-muted" style="font-size: 28px;"></i>
                    <p class="text-muted fw-semibold mt-2 mb-0" style="font-size: 13px;">Debe seleccionar un Tipo de Requerimiento en el Paso 1 para configurar las cotizaciones de sustento.</p>
                </div>

                <!-- Estado 2: Tipo de Requerimiento seleccionado -->
                <div id="cotizacionesStateActive" style="display: none;">
                    <div id="cotizacionesAlert" class="alert p-3 mb-3" style="font-size: 12px; border-radius: 6px; line-height: 1.5;"></div>
                    <div class="row g-3" id="cotizacionesList"></div>
                    <div class="mt-3" id="divBtnAddCot">
                        <button type="button" class="btn btn-sm btn-outline-secondary fw-bold" onclick="agregarCotizacionHTML(false)"><i class="bi bi-paperclip"></i> Anadir Cotizacion Fisica</button>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="d-flex justify-content-end gap-2 mt-4 mb-3">
                <button type="button" class="btn btn-default" onclick="limpiarFormulario()"><i class="bi bi-trash"></i> Limpiar Todo</button>
                <button type="button" class="btn btn-outline-secondary fw-bold p-3 py-2" style="font-size: 14px;" onclick="guardarBorrador()"><i class="bi bi-save"></i> Guardar Borrador</button>
                <button type="submit" class="btn btn-success fw-bold p-3 py-2" style="font-size: 14px;"><i class="bi bi-send-check"></i> Enviar Solicitud a Aprobación</button>
            </div>
        </form>

        <!-- MODAL NUEVO PROVEEDOR -->
        <div class="modal fade" id="mdlNuevoProveedor" tabindex="-1" role="dialog" aria-labelledby="mdlNuevoProveedorLabel" aria-hidden="true" style="z-index: 1060;">
            <div class="modal-dialog" role="document">
                <div class="modal-content" style="border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    <div class="modal-header bg-success text-white" style="padding: 12px 16px;">
                        <h5 class="modal-title fw-bold" id="mdlNuevoProveedorLabel" style="font-size: 14px; margin: 0;"><i class="bi bi-person-plus-fill"></i> Registrar Nuevo Proveedor</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="$('#mdlNuevoProveedor').modal('hide')" style="background: none; border: none; color: #fff; font-size: 20px; line-height: 1; opacity: 0.8; padding: 0;">&times;</button>
                    </div>
                    <div class="modal-body" style="background-color: #f8fafc; padding: 16px;">
                        <form id="frmNuevoProveedor" onsubmit="guardarNuevoProveedor(event)">
                            <input type="hidden" id="prov_target_idx" value="">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" style="font-size: 11px; color: #475569; margin-bottom: 4px; display: block;">RUC / Cédula *</label>
                                <input type="text" class="form-control form-control-sm form-control-adq" id="new_Prs_Ced" name="Prs_Ced" required placeholder="Ej. 0999999999001 o 0999999999" style="width: 100%;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" style="font-size: 11px; color: #475569; margin-bottom: 4px; display: block;">Razón Social *</label>
                                <input type="text" class="form-control form-control-sm form-control-adq" id="new_Prs_Ape" name="Prs_Ape" required placeholder="Ej. CORPORACION EXA S.A." style="width: 100%;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" style="font-size: 11px; color: #475569; margin-bottom: 4px; display: block;">Nombre Comercial</label>
                                <input type="text" class="form-control form-control-sm form-control-adq" id="new_Prv_Com" name="Prv_Com" placeholder="Ej. EXA" style="width: 100%;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" style="font-size: 11px; color: #475569; margin-bottom: 4px; display: block;">Correo Electrónico</label>
                                <input type="email" class="form-control form-control-sm form-control-adq" id="new_Prv_Cor" name="Prv_Cor" placeholder="Ej. info@exa.com" style="width: 100%;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" style="font-size: 11px; color: #475569; margin-bottom: 4px; display: block;">Teléfono</label>
                                <input type="text" class="form-control form-control-sm form-control-adq" id="new_Prv_Tel" name="Prv_Tel" placeholder="Ej. 042XXXXXX o 09XXXXXXXX" style="width: 100%;">
                            </div>
                            <div class="text-end mt-3" style="margin-top: 15px;">
                                <button type="button" class="btn btn-sm btn-default" onclick="$('#mdlNuevoProveedor').modal('hide')" style="margin-right: 5px;">Cancelar</button>
                                <button type="submit" class="btn btn-sm btn-success fw-bold"><i class="bi bi-save"></i> Guardar Proveedor</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../VALIDACIONES/adq_solicitud.js" charset="UTF-8"></script>
    <?php
    exit;
}
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
            <!-- El contenido de este formulario se maneja dinámicamente o por carga híbrida -->
        </form>
    </div>

    <!-- Script del validador de adquisición -->
    <script src="../VALIDACIONES/adq_solicitud.js" charset="UTF-8"></script>
</body>
</html>
