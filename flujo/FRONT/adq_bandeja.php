<?php
/**
 * EXA Adquisiciones - Bandeja de Trabajo de Usuarios
 * @author Oz <oz-agent@warp.dev>
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/wf_manager_log.php');
require_once('../LOGICA/adq_adquisiciones_log.php');

$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new MysqlDatos($obBD_conexion);
$obBD_adq = new adq_adquisiciones_log($obBD_conexion);
$wf_mgr = new wf_manager_log($Ses_Dat_Dis);

$ajax_workflow_action = isset($_GET['ajax_workflow_action']) ? $_GET['ajax_workflow_action'] : (isset($_POST['ajax_workflow_action']) ? $_POST['ajax_workflow_action'] : null);
$ajax_enviar_borrador = isset($_POST['ajax_enviar_borrador']) ? $_POST['ajax_enviar_borrador'] : null;
$ajax_reenviar_observada = isset($_POST['ajax_reenviar_observada']) ? $_POST['ajax_reenviar_observada'] : null;
$ajax_buscar_compras = isset($_GET['ajax_buscar_compras']) ? $_GET['ajax_buscar_compras'] : null;
$ajax_vincular_compra = isset($_GET['ajax_vincular_compra']) ? $_GET['ajax_vincular_compra'] : (isset($_POST['ajax_vincular_compra']) ? $_POST['ajax_vincular_compra'] : null);
$ajax_desvincular_compra = isset($_POST['ajax_desvincular_compra']) ? $_POST['ajax_desvincular_compra'] : null;
$ajax_get_solicitud_detail = isset($_GET['ajax_get_solicitud_detail']) ? $_GET['ajax_get_solicitud_detail'] : null;
$ajax_save_avance_docs = isset($_POST['ajax_save_avance_docs']) ? $_POST['ajax_save_avance_docs'] : null;
$ajax_get_compra_avance = isset($_GET['ajax_get_compra_avance']) ? $_GET['ajax_get_compra_avance'] : null;
$ajax_buscar_anticipos = isset($_GET['ajax_buscar_anticipos']) ? $_GET['ajax_buscar_anticipos'] : null;
$ajax_get_anticipo_avance = isset($_GET['ajax_get_anticipo_avance']) ? $_GET['ajax_get_anticipo_avance'] : null;
$ajax_descargar_expediente = isset($_GET['ajax_descargar_expediente']) ? $_GET['ajax_descargar_expediente'] : null;
$ajax_descargar_docs_zip = isset($_GET['ajax_descargar_docs_zip']) ? $_GET['ajax_descargar_docs_zip'] : null;
$ajax_subir_expediente = isset($_POST['ajax_subir_expediente']) ? $_POST['ajax_subir_expediente'] : null;
$ajax_firmar_expediente = isset($_POST['ajax_firmar_expediente']) ? $_POST['ajax_firmar_expediente'] : null;

$es_ajax_bandeja = (
    isset($ajax_workflow_action) || isset($ajax_enviar_borrador) || isset($ajax_reenviar_observada)
    || isset($ajax_buscar_compras) || isset($ajax_vincular_compra) || isset($ajax_desvincular_compra)
    || isset($ajax_get_solicitud_detail) || isset($ajax_save_avance_docs) || isset($ajax_get_compra_avance)
    || isset($ajax_buscar_anticipos) || isset($ajax_get_anticipo_avance) || isset($ajax_descargar_expediente)
    || isset($ajax_descargar_docs_zip) || isset($ajax_subir_expediente) || isset($ajax_firmar_expediente)
);

// Ensures de esquema solo en carga HTML (o escrituras que ya los invocan internamente).
if (!$es_ajax_bandeja) {
    $obBD_adq->ensureSolicitudTituloColumn();
    $wf_mgr->ensureVersioningSchema();
}

$wf_ctx = $wf_mgr->resolverContextoUsuario($Ses_Emp_Cod);
$clausula_nodo_usuario = $wf_mgr->sqlClausulaNodoAsignadoAUsuario($wf_ctx['usu_cod'], $wf_ctx['dep_cod'], $wf_ctx['perfiles_ids']);

function adq_es_utf8_valido($text) {
    if (!is_string($text) || $text === '') {
        return true;
    }
    if (function_exists('mb_check_encoding')) {
        return mb_check_encoding($text, 'UTF-8');
    }
    return (bool)preg_match('//u', $text);
}

/**
 * Detecta mojibake tipico de utf8_encode sobre texto ya UTF-8 ("Ã³", "Ã±", "Â").
 * Marcadores en bytes (no literales) para no depender del encoding del .php.
 */
function adq_parece_utf8_doble($text) {
    return is_string($text)
        && $text !== ''
        && (strpos($text, "\xC3\x83") !== false || strpos($text, "\xC3\x82") !== false);
}

/**
 * Normaliza texto a UTF-8 sin doble codificacion (evita "InstanciaciÃ³n").
 */
function adq_ensure_utf8_string($text) {
    if (!is_string($text) || $text === '') {
        return $text;
    }

    // Caso tipico: ya era UTF-8 y se aplico utf8_encode otra vez.
    if (adq_es_utf8_valido($text) && adq_parece_utf8_doble($text)) {
        $recovered = null;
        if (function_exists('mb_convert_encoding')) {
            $recovered = @mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
        }
        if (!is_string($recovered) || $recovered === '') {
            $recovered = @utf8_decode($text);
        }
        if (is_string($recovered) && adq_es_utf8_valido($recovered) && !adq_parece_utf8_doble($recovered)) {
            return $recovered;
        }
    }

    if (adq_es_utf8_valido($text)) {
        return $text;
    }

    if (function_exists('mb_convert_encoding')) {
        $converted = @mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
        if (is_string($converted) && adq_es_utf8_valido($converted)) {
            return $converted;
        }
    }

    return function_exists('utf8_encode') ? utf8_encode($text) : $text;
}

/** Texto de departamento solicitante; si no hay, "Sin departamento". */
function adqTextoDepartamentoSolicitante($dep_des) {
    $dep_des = is_string($dep_des) ? trim($dep_des) : '';
    if ($dep_des === '') {
        return 'Sin departamento';
    }
    return $dep_des;
}

function adq_utf8_deep(&$data) {
    if (is_array($data)) {
        foreach ($data as $k => &$v) {
            adq_utf8_deep($v);
        }
        unset($v);
        return;
    }
    if (is_string($data)) {
        $data = adq_ensure_utf8_string($data);
    }
}

function adq_preparar_historial_json_utf8(&$historial) {
    if (!empty($historial)) {
        adq_utf8_deep($historial);
    }
}

function adq_preparar_payload_utf8(&$payload) {
    if (!empty($payload) && is_array($payload)) {
        adq_utf8_deep($payload);
    }
}

// Verificar acceso a la ventana 'bandeja'
if (!$wf_mgr->verificarAccesoVentana('bandeja')) {
    if (isset($ajax_workflow_action) || isset($ajax_buscar_compras) || isset($ajax_vincular_compra) || isset($ajax_desvincular_compra) || isset($ajax_get_solicitud_detail) || isset($ajax_enviar_borrador) || isset($ajax_reenviar_observada) || isset($ajax_save_avance_docs) || isset($ajax_get_compra_avance) || isset($ajax_buscar_anticipos) || isset($ajax_get_anticipo_avance) || isset($ajax_descargar_expediente) || isset($ajax_descargar_docs_zip) || isset($ajax_subir_expediente) || isset($ajax_firmar_expediente)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Acceso denegado. No tiene permisos para realizar esta acci?n.'));
        exit;
    } else {
        echo "<div class='alert alert-danger m-3'>Acceso denegado. No tiene permisos para ver esta ventana.</div>";
        exit;
    }
}

// 1. Procesar Acciones de Workflow (Aprobar, Rechazar, Observar, Devolver)
if (isset($ajax_workflow_action)) {
    $ins_cod = intval($_POST['Ins_Cod']);
    $accion = mysqli_real_escape_string($obBD_conexion->conexion, $_POST['Action']);
    $comentario = mysqli_real_escape_string($obBD_conexion->conexion, $_POST['Comentario']);
    
    // Validar si el usuario tiene permiso para procesar este paso del workflow
    $usu_cod = $wf_ctx['usu_cod'];
    $dep_cod = $wf_ctx['dep_cod'];
    $perfiles_ids = $wf_ctx['perfiles_ids'];
    
    $check_perm = $obBD_con1->getRowConsultaSql("
        SELECT n.Nod_Cod 
        FROM wf_instancias i
        INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
        WHERE i.Ins_Cod = $ins_cod AND i.Ins_Est = 'P' AND $clausula_nodo_usuario;", $obBD_conexion);
        
    if (empty($check_perm)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Acceso denegado. No tiene permisos para procesar esta etapa del requerimiento.'));
        exit;
    }

    if ($accion === 'APROBAR') {
        $nod_fin = $obBD_con1->getRowConsultaSql(
            "SELECT n.Nod_Tip, i.Ins_Ent_Cod
             FROM wf_instancias i
             INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
             WHERE i.Ins_Cod = $ins_cod
             LIMIT 1;",
            $obBD_conexion
        );
        if (!empty($nod_fin['Nod_Tip']) && $nod_fin['Nod_Tip'] === 'FIN') {
            $val_exp = $obBD_adq->validarFinalizarExpedienteFin(intval($nod_fin['Ins_Ent_Cod']));
            if (empty($val_exp['success'])) {
                $obBD_con1->echoJson($val_exp);
                exit;
            }
        }
    }
    
    // Carga de uno o varios adjuntos opcionales
    $adjunto_db_path = null;
    $archivos_accion = array();
    if (isset($_FILES['adjuntos']) && is_array($_FILES['adjuntos']['name'])) {
        foreach ($_FILES['adjuntos']['name'] as $i => $nombre) {
            $archivos_accion[] = array(
                'name' => $nombre,
                'tmp_name' => isset($_FILES['adjuntos']['tmp_name'][$i]) ? $_FILES['adjuntos']['tmp_name'][$i] : '',
                'error' => isset($_FILES['adjuntos']['error'][$i]) ? intval($_FILES['adjuntos']['error'][$i]) : UPLOAD_ERR_NO_FILE,
                'size' => isset($_FILES['adjuntos']['size'][$i]) ? intval($_FILES['adjuntos']['size'][$i]) : 0
            );
        }
    } elseif (isset($_FILES['adjunto'])) {
        $archivos_accion[] = $_FILES['adjunto'];
    }

    $extensiones_permitidas = array('pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx');
    foreach ($archivos_accion as $archivo) {
        if (intval($archivo['error']) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if (intval($archivo['error']) !== UPLOAD_ERR_OK) {
            $obBD_con1->echoJson(array('success' => false, 'message' => 'No se pudo cargar uno de los archivos seleccionados.'));
            exit;
        }
        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $extensiones_permitidas, true)) {
            $obBD_con1->echoJson(array('success' => false, 'message' => 'Tipo de archivo no permitido: ' . $archivo['name']));
            exit;
        }
        if (intval($archivo['size']) > 10 * 1024 * 1024) {
            $obBD_con1->echoJson(array('success' => false, 'message' => 'El archivo ' . $archivo['name'] . ' supera el limite de 10 MB.'));
            exit;
        }
    }

    if (!empty($archivos_accion)) {
        $sol_row = $obBD_con1->getRowConsultaSql(
            "SELECT i.Ins_Ent_Cod AS Sol_Cod, s.Sol_Tit
             FROM wf_instancias i
             LEFT JOIN adq_solicitudes s ON s.Sol_Cod = i.Ins_Ent_Cod AND i.Ins_Ent_Typ = 'adq_solicitudes'
             WHERE i.Ins_Cod = $ins_cod
             LIMIT 1;",
            $obBD_conexion
        );
        $sol_cod_adj = !empty($sol_row['Sol_Cod']) ? intval($sol_row['Sol_Cod']) : 0;
        $sol_tit_adj = !empty($sol_row['Sol_Tit']) ? $sol_row['Sol_Tit'] : '';
        try {
            $dir_info = $obBD_adq->asegurarDirectorioDocumentosSolicitud($sol_cod_adj, $sol_tit_adj);
        } catch (Exception $e) {
            $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
            exit;
        }
        $target_dir = $dir_info['abs'] . '/';
        $rel_dir = $dir_info['rel'];
        $paths_adjuntos = array();
        foreach ($archivos_accion as $archivo) {
            if (intval($archivo['error']) !== UPLOAD_ERR_OK) {
                continue;
            }
            $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
            $unique_name = "action_" . uniqid('', true) . "." . $ext;
            if (!move_uploaded_file($archivo['tmp_name'], $target_dir . $unique_name)) {
                $obBD_con1->echoJson(array('success' => false, 'message' => 'No se pudo guardar el archivo ' . $archivo['name'] . '.'));
                exit;
            }
            $paths_adjuntos[] = $rel_dir . '/' . $unique_name;
        }
        if (!empty($paths_adjuntos)) {
            $adjunto_db_path = json_encode($paths_adjuntos);
        }
    }

    $resp = $wf_mgr->procesarAccionUsuario($ins_cod, $accion, $comentario, $adjunto_db_path);
    $obBD_con1->echoJson($resp);
    exit;
}

if (isset($ajax_enviar_borrador)) {
    $sol_cod = intval($_POST['Sol_Cod']);
    $resp = $obBD_adq->enviarBorrador($sol_cod);
    $obBD_con1->echoJson($resp);
    exit;
}

if (isset($ajax_reenviar_observada)) {
    $sol_cod = intval($_POST['Sol_Cod']);
    $resp = $obBD_adq->reenviarObservada($sol_cod);
    $obBD_con1->echoJson($resp);
    exit;
}

// Guardar documentos de etapa AVANCE
if (isset($ajax_save_avance_docs)) {
    $sol_cod = intval($_POST['Sol_Cod']);
    $docs_nuevos = array();
    $docs_existentes = array();
    $sav_eliminar = array();

    if (isset($_POST['sav_eliminar']) && is_array($_POST['sav_eliminar'])) {
        $sav_eliminar = $_POST['sav_eliminar'];
    }

    if (isset($_POST['avance_docs_nuevos']) && is_array($_POST['avance_docs_nuevos'])) {
        foreach ($_POST['avance_docs_nuevos'] as $idx => $doc) {
            $docs_nuevos[$idx] = array(
                'Sav_Des' => isset($doc['Sav_Des']) ? $doc['Sav_Des'] : '',
                'Sav_Cop_Cod' => isset($doc['Sav_Cop_Cod']) ? intval($doc['Sav_Cop_Cod']) : 0,
                'Sav_Atp_Cod' => isset($doc['Sav_Atp_Cod']) ? intval($doc['Sav_Atp_Cod']) : 0
            );
        }
    }

    $mapa_campos = array(
        'avance_factura_nuevos' => 'Sav_Fac_Adj',
        'avance_retencion_nuevos' => 'Sav_Ret_Adj',
        'avance_comprobante_nuevos' => 'Sav_Com_Adj'
    );

    try {
        $dir_info = $obBD_adq->asegurarDirectorioDocumentosSolicitud($sol_cod);
    } catch (Exception $e) {
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
        exit;
    }
    $target_dir = $dir_info['abs'] . '/';
    $rel_dir = $dir_info['rel'];

    foreach ($mapa_campos as $input_name => $db_field) {
        if (isset($_FILES[$input_name]) && is_array($_FILES[$input_name]['name'])) {
            foreach ($_FILES[$input_name]['name'] as $idx => $name) {
                if ($_FILES[$input_name]['error'][$idx] == 0 && $name !== '') {
                    $ext = pathinfo($name, PATHINFO_EXTENSION);
                    $unique_name = "avance_" . uniqid() . "." . $ext;
                    if (move_uploaded_file($_FILES[$input_name]['tmp_name'][$idx], $target_dir . $unique_name)) {
                        if (!isset($docs_nuevos[$idx])) {
                            $docs_nuevos[$idx] = array('Sav_Des' => '');
                        }
                        $docs_nuevos[$idx][$db_field] = $rel_dir . '/' . $unique_name;
                    }
                }
            }
        }
    }

    if (isset($_POST['avance_docs_existentes']) && is_array($_POST['avance_docs_existentes'])) {
        $docs_existentes = $_POST['avance_docs_existentes'];
    }

    $mapa_campos_exist = array(
        'avance_factura_existentes' => 'Sav_Fac_Adj',
        'avance_retencion_existentes' => 'Sav_Ret_Adj',
        'avance_comprobante_existentes' => 'Sav_Com_Adj'
    );
    foreach ($mapa_campos_exist as $input_name => $db_field) {
        if (isset($_FILES[$input_name]) && is_array($_FILES[$input_name]['name'])) {
            foreach ($_FILES[$input_name]['name'] as $sav_cod => $name) {
                if ($_FILES[$input_name]['error'][$sav_cod] == 0 && $name !== '') {
                    $ext = pathinfo($name, PATHINFO_EXTENSION);
                    $unique_name = "avance_" . uniqid() . "." . $ext;
                    if (move_uploaded_file($_FILES[$input_name]['tmp_name'][$sav_cod], $target_dir . $unique_name)) {
                        if (!isset($docs_existentes[$sav_cod])) {
                            $docs_existentes[$sav_cod] = array();
                        }
                        $docs_existentes[$sav_cod][$db_field] = $rel_dir . '/' . $unique_name;
                    }
                }
            }
        }
    }

    if (isset($_POST['fiscal_docs']) && is_array($_POST['fiscal_docs'])) {
        foreach ($_POST['fiscal_docs'] as $fi => $fiscal_doc) {
            $titulo = trim(isset($fiscal_doc['titulo']) ? $fiscal_doc['titulo'] : '');
            $name = isset($_FILES['fiscal_archivos']['name'][$fi]) ? $_FILES['fiscal_archivos']['name'][$fi] : '';
            $error = isset($_FILES['fiscal_archivos']['error'][$fi]) ? intval($_FILES['fiscal_archivos']['error'][$fi]) : UPLOAD_ERR_NO_FILE;

            if ($titulo === '' && ($name === '' || $error === UPLOAD_ERR_NO_FILE)) {
                continue;
            }
            if ($titulo === '') {
                $obBD_con1->echoJson(array('success' => false, 'message' => 'Cada PDF de fiscalizacion debe tener un titulo.'));
                exit;
            }
            if ($name === '' || $error !== UPLOAD_ERR_OK) {
                $obBD_con1->echoJson(array('success' => false, 'message' => 'Seleccione el PDF correspondiente al titulo "' . $titulo . '".'));
                exit;
            }

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $tmp_name = $_FILES['fiscal_archivos']['tmp_name'][$fi];
            $header = @file_get_contents($tmp_name, false, null, 0, 5);
            if ($ext !== 'pdf' || $header !== '%PDF-') {
                $obBD_con1->echoJson(array('success' => false, 'message' => 'El archivo "' . $name . '" no es un PDF valido.'));
                exit;
            }

            $unique_name = "fiscal_" . uniqid() . ".pdf";
            if (!move_uploaded_file($tmp_name, $target_dir . $unique_name)) {
                $obBD_con1->echoJson(array('success' => false, 'message' => 'No se pudo guardar el PDF "' . $name . '".'));
                exit;
            }
            $docs_nuevos[] = array(
                'Sav_Des' => $titulo,
                'Sav_Cop_Cod' => 0,
                'Sav_Fac_Adj' => $rel_dir . '/' . $unique_name
            );
        }
    }

    $docs_nuevos = array_values($docs_nuevos);
    $resp = $obBD_adq->guardarAvanceEtapa($sol_cod, $docs_nuevos, $docs_existentes, $sav_eliminar);
    if (!empty($resp['success'])) {
        $ins_cod = !empty($resp['Ins_Cod']) ? intval($resp['Ins_Cod']) : 0;
        $nod_cod = !empty($resp['Nod_Cod']) ? intval($resp['Nod_Cod']) : 0;
        if ($ins_cod > 0 && $nod_cod > 0) {
            $avances = $obBD_adq->listarAvancesSolicitud($sol_cod, $ins_cod, $nod_cod);
            $resp['avances'] = $obBD_adq->enriquecerAvancesConCompras($avances, intval($Ses_Emp_Cod));
        }
    }
    adq_preparar_payload_utf8($resp);
    $obBD_con1->echoJson($resp);
    exit;
}

// 2. Buscar facturas de compra para vincular a solicitud
if (isset($ajax_buscar_compras)) {
    $search = mysqli_real_escape_string($obBD_conexion->conexion, $_GET['search']);
    $compras = $obBD_con1->getArrayConsultaSql("
        SELECT c.Cop_Cod, c.Cop_Num, c.Cop_Fec, CONCAT(p.Prs_Ape, ' ', p.Prs_Nom) as Proveedor,
               ROUND((SELECT SUM(dc.Cop_Imp - (dc.Cop_Imp * dc.Cop_Dec / 100)) FROM det_compra dc WHERE dc.Cop_Cod = c.Cop_Cod), 2) as Total
        FROM compras c
        INNER JOIN proveedore pr ON pr.Prv_Cod = c.Prv_Cod
        INNER JOIN persona p ON p.Prs_Cod = pr.Prs_Cod
        WHERE c.Cop_Est = 'A' AND pr.Emp_Cod = $Ses_Emp_Cod
          AND (c.Cop_Num LIKE '%$search%' OR p.Prs_Ape LIKE '%$search%' OR p.Prs_Nom LIKE '%$search%')
        ORDER BY c.Cop_Fec DESC LIMIT 20;", $obBD_conexion);
    $compras = $obBD_adq->enriquecerListaCompras($compras);
    $obBD_con1->echoJson(array('success' => true, 'compras' => $compras));
    exit;
}

// Detalle de factura de compra para nodo AVANCE
if (isset($ajax_get_compra_avance)) {
    $cop_cod = isset($_GET['cop_cod']) ? intval($_GET['cop_cod']) : 0;
    $resp = $obBD_adq->obtenerDetalleCompraAvance($cop_cod, intval($Ses_Emp_Cod));
    $obBD_con1->echoJson($resp);
    exit;
}

// Buscar anticipos de proveedores para AVANCE/FISCALIZACION
if (isset($ajax_buscar_anticipos)) {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $anticipos = $obBD_adq->buscarAnticiposProveedorAvance(intval($Ses_Emp_Cod), $search, 20);
    $obBD_con1->echoJson(array('success' => true, 'anticipos' => $anticipos));
    exit;
}

// Detalle de anticipo de proveedor para nodo AVANCE/FISCALIZACION
if (isset($ajax_get_anticipo_avance)) {
    $atp_cod = isset($_GET['atp_cod']) ? intval($_GET['atp_cod']) : 0;
    $resp = $obBD_adq->obtenerDetalleAnticipoAvance($atp_cod, intval($Ses_Emp_Cod));
    $obBD_con1->echoJson($resp);
    exit;
}

// 3. Vincular factura de compra a solicitud
if (isset($ajax_vincular_compra)) {
    $sol_cod = intval($_POST['Sol_Cod']);
    $cop_cod = intval($_POST['Cop_Cod']);
    $resp = $wf_mgr->vincularCompra($sol_cod, $cop_cod);
    $obBD_con1->echoJson($resp);
}

// 4. Desvincular factura de compra
if (isset($ajax_desvincular_compra)) {
    $scm_cod = intval($_POST['Scm_Cod']);
    $resp = $wf_mgr->desvincularCompra($scm_cod);
    $obBD_con1->echoJson($resp);
}

// 4b. Descargar expediente PDF unido (nodo FIN)
if (isset($ajax_descargar_expediente)) {
    $sol_cod = intval(isset($_GET['sol_cod']) ? $_GET['sol_cod'] : 0);
    $tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : 'unido';
    if ($sol_cod <= 0) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Codigo de solicitud invalido.'));
        exit;
    }

    $estado = $obBD_adq->obtenerEstadoExpedienteSolicitud($sol_cod);
    $sol = $obBD_con1->getRowConsultaSql("SELECT Sol_Num FROM adq_solicitudes WHERE Sol_Cod = $sol_cod LIMIT 1;", $obBD_conexion);
    if (empty($sol)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'No se encontro la solicitud.'));
        exit;
    }

    if ($tipo === 'firmado') {
        if (empty($estado['firmado'])) {
            $obBD_con1->echoJson(array('success' => false, 'message' => 'No hay expediente firmado disponible.'));
            exit;
        }
        $path_abs = $obBD_adq->rutaAbsolutaDocumento($estado['firmado']);
        if ($path_abs === '' || !is_file($path_abs)) {
            $obBD_con1->echoJson(array('success' => false, 'message' => 'No se encontro el archivo firmado.'));
            exit;
        }
        $filename = 'expediente_solicitud_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', trim((string)$sol['Sol_Num'])) . '_firmado.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path_abs));
        readfile($path_abs);
        exit;
    }

    if ($tipo === 'cargado' || $tipo === 'generado') {
        if (empty($estado['pdf'])) {
            $obBD_con1->echoJson(array('success' => false, 'message' => 'Aun no se ha cargado el expediente PDF.'));
            exit;
        }
        $path_abs = $obBD_adq->rutaAbsolutaDocumento($estado['pdf']);
        if ($path_abs === '' || !is_file($path_abs)) {
            $obBD_con1->echoJson(array('success' => false, 'message' => 'No se encontro el expediente cargado.'));
            exit;
        }
        $filename = 'expediente_solicitud_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', trim((string)$sol['Sol_Num'])) . '_cargado.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path_abs));
        readfile($path_abs);
        exit;
    }

    $sol_row = $obBD_con1->getRowConsultaSql("
        SELECT s.Sol_Cod, s.Sol_Num, i.Ins_Cod, n.Nod_Tip
        FROM adq_solicitudes s
        LEFT JOIN wf_instancias i ON i.Ins_Cod = (
            SELECT MAX(i2.Ins_Cod)
            FROM wf_instancias i2
            WHERE i2.Ins_Ent_Typ = 'adq_solicitudes' AND i2.Ins_Ent_Cod = s.Sol_Cod
        )
        LEFT JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
        WHERE s.Sol_Cod = $sol_cod
        LIMIT 1;", $obBD_conexion);

    $ins_cod = !empty($sol_row['Ins_Cod']) ? intval($sol_row['Ins_Cod']) : 0;
    $resultado = $obBD_adq->generarExpedientePdfUnido($sol_cod, $ins_cod);
    if (empty($resultado['success'])) {
        $obBD_con1->echoJson($resultado);
        exit;
    }

    if (!is_file($resultado['path'])) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'No se pudo generar el archivo del expediente.'));
        exit;
    }

    $filename = 'expediente_solicitud_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', trim((string)$sol['Sol_Num'])) . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($resultado['path']));
    readfile($resultado['path']);
    exit;
}

// Descargar ZIP con todos los documentos de la solicitud (modal seguimiento)
if (isset($ajax_descargar_docs_zip)) {
    $sol_cod = intval(isset($_GET['sol_cod']) ? $_GET['sol_cod'] : 0);
    if ($sol_cod <= 0) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(array('success' => false, 'message' => 'Codigo de solicitud invalido.'));
        exit;
    }
    $sol_emp = $obBD_con1->getRowConsultaSql(
        "SELECT Emp_Cod FROM adq_solicitudes WHERE Sol_Cod = $sol_cod LIMIT 1;",
        $obBD_conexion
    );
    if (empty($sol_emp) || intval($sol_emp['Emp_Cod']) !== intval($Ses_Emp_Cod)) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(array('success' => false, 'message' => 'No tiene acceso a esta solicitud.'));
        exit;
    }

    $resultado = $obBD_adq->generarZipDocumentosSolicitud($sol_cod);
    if (empty($resultado['success']) || empty($resultado['path']) || !is_file($resultado['path'])) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(array(
            'success' => false,
            'message' => !empty($resultado['message']) ? $resultado['message'] : 'No se pudo generar el ZIP.'
        ));
        exit;
    }

    $filename = !empty($resultado['filename']) ? $resultado['filename'] : ('documentos_sol_' . $sol_cod . '.zip');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($resultado['path']));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    readfile($resultado['path']);
    @unlink($resultado['path']);
    exit;
}

// Subir expediente PDF cargado por el usuario (nodo FIN)
if (isset($ajax_subir_expediente)) {
    $sol_cod = intval(isset($_POST['Sol_Cod']) ? $_POST['Sol_Cod'] : 0);
    if ($sol_cod <= 0) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Solicitud invalida.'));
        exit;
    }
    if (!isset($_FILES['expediente_pdf']) || $_FILES['expediente_pdf']['error'] !== UPLOAD_ERR_OK) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Debe seleccionar el archivo PDF del expediente.'));
        exit;
    }
    $ext = strtolower(pathinfo($_FILES['expediente_pdf']['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'El expediente debe ser un archivo PDF.'));
        exit;
    }
    $resp = $obBD_adq->subirExpedienteSolicitud(
        $sol_cod,
        intval($Ses_Emp_Cod),
        $_FILES['expediente_pdf']['tmp_name'],
        $_FILES['expediente_pdf']['name']
    );
    $obBD_con1->echoJson($resp);
    exit;
}

// Firmar expediente con llave electronica (nodo FIN)
if (isset($ajax_firmar_expediente)) {
    $sol_cod = intval(isset($_POST['Sol_Cod']) ? $_POST['Sol_Cod'] : 0);
    $clave = isset($_POST['Lla_Cla']) ? $_POST['Lla_Cla'] : '';
    $usar_empresa = isset($_POST['usar_llave_empresa']) && intval($_POST['usar_llave_empresa']) === 1;
    if ($sol_cod <= 0) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Solicitud invalida.'));
        exit;
    }

    $p12_tmp = '';
    if (isset($_FILES['llave_p12']) && $_FILES['llave_p12']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['llave_p12']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'p12') {
            $obBD_con1->echoJson(array('success' => false, 'message' => 'La llave electronica debe ser un archivo .p12'));
            exit;
        }
        $tmp_dir = dirname(__FILE__) . '/../documentos_flujo/_tmp_llaves/';
        if (!is_dir($tmp_dir)) {
            mkdir($tmp_dir, 0777, true);
        }
        $p12_tmp = $tmp_dir . 'llave_' . uniqid() . '.p12';
        if (!move_uploaded_file($_FILES['llave_p12']['tmp_name'], $p12_tmp)) {
            $obBD_con1->echoJson(array('success' => false, 'message' => 'No se pudo cargar la llave electronica.'));
            exit;
        }
    }

    $resp = $obBD_adq->firmarExpedienteSolicitud($sol_cod, intval($Ses_Emp_Cod), $clave, $p12_tmp, $usar_empresa);
    if ($p12_tmp !== '' && is_file($p12_tmp)) {
        @unlink($p12_tmp);
    }
    $obBD_con1->echoJson($resp);
    exit;
}

// 5. Obtener Detalle Completo de una Solicitud
if (isset($ajax_get_solicitud_detail)) {
    $sol_cod = intval($_GET['sol_cod']);

    if ($sol_cod <= 0) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Codigo de solicitud invalido.'));
        exit;
    }

    $sol = $obBD_con1->getRowConsultaSql("
        SELECT s.*, tr.Trq_Des,
               tr.Trq_Req_Fac, tr.Trq_Per_Cie, tr.Trq_Req_Cot, tr.Trq_Min_Cot,
               tr.Trq_Req_Pre, tr.Trq_Req_Adj, tr.Trq_Req_Pro, tr.Trq_Tiempo_Est,
               IFNULL(u.Usu_Ced, '') as Usu_Nom,
               IFNULL(d.Dep_Des, '') as Dep_Des,
               IFNULL(p.Prs_Nom, '') as Sol_Nom, IFNULL(p.Prs_Ape, '') as Sol_Ape,
               i.Ins_Cod, i.Nod_Act, i.Ins_Est,
               n.Nod_Nom, n.Nod_Tip, n.Nod_Com_Obl, n.Nod_Adj_Obl,
               IFNULL(n.Nod_Cot_Edit, 0) AS Nod_Cot_Edit,
               IFNULL(n.Nod_Cot_Sel, 0) AS Nod_Cot_Sel
        FROM adq_solicitudes s
        INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
        LEFT JOIN usuarios u ON u.Usu_Cod = s.Usu_Sol
        LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod
        LEFT JOIN departamen d ON d.Dep_Cod = s.Dep_Sol
        LEFT JOIN wf_instancias i ON i.Ins_Cod = (
            SELECT MAX(i2.Ins_Cod)
            FROM wf_instancias i2
            WHERE i2.Ins_Ent_Typ = 'adq_solicitudes' AND i2.Ins_Ent_Cod = s.Sol_Cod
        )
        LEFT JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
        WHERE s.Sol_Cod = $sol_cod;", $obBD_conexion);

    if (empty($sol)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'No se encontro la solicitud solicitada.'));
        exit;
    }

    $sol = $obBD_adq->aplicarRequisitosEfectivos($sol);

    $puede_resolver = $wf_mgr->puedeUsuarioResolverSolicitud(
        $sol,
        $wf_ctx['usu_cod'],
        $wf_ctx['dep_cod'],
        $wf_ctx['perfiles_ids'],
        ($wf_ctx['usu_cod'] == 1) || (isset($_SESSION['Ses_Lis_Per']) && count(array_intersect(array(1, 2), $_SESSION['Ses_Lis_Per'])) > 0)
    );
    $motivo_bloqueo = $puede_resolver ? '' : $wf_mgr->motivoBloqueoResolucionSolicitud(
        $sol,
        $wf_ctx['usu_cod'],
        $wf_ctx['dep_cod'],
        $wf_ctx['perfiles_ids'],
        ($wf_ctx['usu_cod'] == 1) || (isset($_SESSION['Ses_Lis_Per']) && count(array_intersect(array(1, 2), $_SESSION['Ses_Lis_Per'])) > 0)
    );
    $sol['Puede_Resolver'] = $puede_resolver ? 1 : 0;
    $sol['Motivo_Bloqueo'] = $motivo_bloqueo;
    $sol['Es_Solicitante'] = (intval($sol['Usu_Sol']) === intval($wf_ctx['usu_cod'])) ? 1 : 0;
    if (!empty($sol['Ins_Cod'])) {
        $sol['Nod_Cot_Edit'] = $wf_mgr->resolverNodCotEditInstancia(intval($sol['Ins_Cod']));
    } else {
        $sol['Nod_Cot_Edit'] = 0;
    }
    $sol['Puede_Cargar_Cotizaciones'] = $wf_mgr->puedeUsuarioCargarCotizaciones(
        $sol,
        $wf_ctx['usu_cod'],
        $wf_ctx['dep_cod'],
        $wf_ctx['perfiles_ids']
    ) ? 1 : 0;
    $sol['Puede_Seleccionar_Ganadora'] = $wf_mgr->puedeUsuarioSeleccionarGanadora(
        $sol,
        $wf_ctx['usu_cod'],
        $wf_ctx['dep_cod'],
        $wf_ctx['perfiles_ids']
    ) ? 1 : 0;
    $sol['Puede_Cargar_Avance'] = $wf_mgr->puedeUsuarioCargarAvance(
        $sol,
        $wf_ctx['usu_cod'],
        $wf_ctx['dep_cod'],
        $wf_ctx['perfiles_ids']
    ) ? 1 : 0;

    $avances = array();
    if (!empty($sol['Ins_Cod']) && !empty($sol['Nod_Act']) && in_array($sol['Nod_Tip'], array('AVANCE', 'FISCALIZACION'), true)) {
        $avances = $obBD_adq->listarAvancesSolicitud($sol_cod, intval($sol['Ins_Cod']), intval($sol['Nod_Act']));
    } elseif (!empty($sol['Ins_Cod'])) {
        $avances = $obBD_adq->listarAvancesSolicitud($sol_cod);
    }
    $avances = $obBD_adq->enriquecerAvancesConCompras($avances, intval($Ses_Emp_Cod));

    $items = $obBD_con1->getArrayConsultaSql("
        SELECT d.*, i.Ite_Lar AS Pro_Nom
        FROM adq_solicitudes_det d
        LEFT JOIN producto pr ON pr.Pro_Cod = d.Pro_Cod
        LEFT JOIN item i ON i.Ite_Cod = pr.Ite_Cod
        WHERE d.Sol_Cod = $sol_cod
        ORDER BY d.Sde_Int;", $obBD_conexion);
    if ($items === false || $items === null) {
        $items = array();
    }
    $cotizaciones = $obBD_con1->getArrayConsultaSql("SELECT c.*, p.Prs_Nom, p.Prs_Ape, pr.Prv_Com FROM adq_solicitudes_cotizaciones c INNER JOIN proveedore pr ON pr.Prv_Cod = c.Prv_Cod INNER JOIN persona p ON p.Prs_Cod = pr.Prs_Cod WHERE c.Sol_Cod = $sol_cod;", $obBD_conexion);
    if ($cotizaciones === false || $cotizaciones === null) {
        $cotizaciones = array();
    }
    $adjuntos = $obBD_adq->listarAdjuntosSolicitud($sol_cod);

    $historial = array();
    $ins_cod_hist = !empty($sol['Ins_Cod']) ? intval($sol['Ins_Cod']) : 0;
    if ($ins_cod_hist <= 0) {
        $row_ins = $obBD_con1->getRowConsultaSql(
            "SELECT Ins_Cod, Ins_Est, Nod_Act
             FROM wf_instancias
             WHERE Ins_Ent_Typ = 'adq_solicitudes' AND Ins_Ent_Cod = $sol_cod
             ORDER BY Ins_Cod DESC
             LIMIT 1;",
            $obBD_conexion
        );
        if (!empty($row_ins['Ins_Cod'])) {
            $ins_cod_hist = intval($row_ins['Ins_Cod']);
            $sol['Ins_Cod'] = $ins_cod_hist;
            if (empty($sol['Ins_Est'])) {
                $sol['Ins_Est'] = $row_ins['Ins_Est'];
            }
            if (empty($sol['Nod_Act'])) {
                $sol['Nod_Act'] = $row_ins['Nod_Act'];
            }
        }
    }
    if ($ins_cod_hist > 0) {
        $historial = $obBD_con1->getArrayConsultaSql("
            SELECT h.*,
                   COALESCE(n.Nod_Nom, CONCAT('Proceso #', h.Nod_Cod)) AS Nod_Nom,
                   COALESCE(n.Nod_Tip, 'PASO') AS Nod_Tip,
                   n.Dep_Cod AS Nodo_Dep_Cod,
                   n.Per_Cod AS Nodo_Per_Cod,
                   n.Nod_Usu_Asig AS Nodo_Usu_Asig,
                   TRIM(CONCAT(IFNULL(p.Prs_Nom, ''), ' ', IFNULL(p.Prs_Ape, ''))) AS Usuario_Nom,
                   p.Prs_Nom, p.Prs_Ape,
                   d.Wde_Des AS Dep_Des
            FROM wf_instancias_nodos h
            LEFT JOIN wf_nodos n ON n.Nod_Cod = h.Nod_Cod
            LEFT JOIN usuarios u ON u.Usu_Cod = h.Usu_Cod
            LEFT JOIN persona p ON p.Prs_Cod = u.Prs_Cod
            LEFT JOIN wf_departamentos d ON d.Wde_Cod = h.Dep_Cod
            WHERE h.Ins_Cod = $ins_cod_hist
            ORDER BY h.Isn_Fec DESC, h.Isn_Cod DESC;", $obBD_conexion);
        if ($historial === false || $historial === null) {
            $historial = array();
        }
        $historial = $wf_mgr->normalizarHistorialFirmas(
            $historial,
            isset($sol['Ins_Est']) ? $sol['Ins_Est'] : '',
            isset($sol['Nod_Act']) ? intval($sol['Nod_Act']) : 0
        );
        $historial = $wf_mgr->agregarNodoPendienteHistorial(
            $historial,
            isset($sol['Ins_Est']) ? $sol['Ins_Est'] : '',
            isset($sol['Nod_Act']) ? intval($sol['Nod_Act']) : 0,
            $puede_resolver ? intval($wf_ctx['usu_cod']) : 0
        );
        $historial = $wf_mgr->agregarRechazoHistorialSiFalta(
            $historial,
            $ins_cod_hist,
            isset($sol['Sol_Est']) ? $sol['Sol_Est'] : '',
            isset($sol['Ins_Est']) ? $sol['Ins_Est'] : ''
        );
        $historial = $obBD_adq->enriquecerHistorialConArchivos($historial, $sol_cod);
    }

    adq_preparar_historial_json_utf8($historial);

    $flow_visual = $ins_cod_hist > 0 ? $wf_mgr->getVisualFlowData($ins_cod_hist) : array('nodos' => array());
    $compras_vinculadas = $wf_mgr->getComprasVinculadas($sol_cod);
    $expediente_pdfs = array();
    try {
        $expediente_pdfs = $obBD_adq->recolectarPdfsSolicitud($sol_cod, $ins_cod_hist);
    } catch (Exception $e) {
        $expediente_pdfs = array();
    }

    $expediente = array();
    $tiene_llave_empresa = 0;
    if (!empty($sol['Nod_Tip']) && $sol['Nod_Tip'] === 'FIN') {
        $expediente = $obBD_adq->obtenerEstadoExpedienteSolicitud($sol_cod);
        $row_llave = $obBD_con1->getRowConsultaSql(
            "SELECT Lla_Rut FROM llave_elect WHERE Lla_Est = 'A' AND Emp_Cod = " . intval($Ses_Emp_Cod) . " LIMIT 1;",
            $obBD_conexion
        );
        $tiene_llave_empresa = !empty($row_llave['Lla_Rut']) ? 1 : 0;
    }

    $payload = array(
        'success' => true,
        'solicitud' => $sol,
        'items' => $items,
        'cotizaciones' => $cotizaciones,
        'adjuntos' => $adjuntos,
        'avances' => $avances,
        'historial' => $historial,
        'flow_visual' => $flow_visual,
        'compras_vinculadas' => $compras_vinculadas,
        'expediente_pdfs' => count($expediente_pdfs),
        'expediente' => $expediente,
        'tiene_llave_empresa' => $tiene_llave_empresa
    );
    adq_preparar_payload_utf8($payload);
    $obBD_con1->echoJson($payload);
    exit;
}

// Consultar listas de solicitudes
$usu_cod = $wf_ctx['usu_cod'];
$dep_cod = $wf_ctx['dep_cod'];
$perfiles_ids = $wf_ctx['perfiles_ids'];
$emp_cod = intval($Ses_Emp_Cod);

$es_gerencial_admin = ($usu_cod == 1) || (isset($_SESSION['Ses_Lis_Per']) && count(array_intersect(array(1, 2), $_SESSION['Ses_Lis_Per'])) > 0);
$es_gerencial_sql = $es_gerencial_admin ? '1' : '0';
$filtro_pendiente_sin_auto = $wf_mgr->sqlFiltroPendienteSinAutoaprobacion($usu_cod, $es_gerencial_sql);

// A. PENDIENTES DE MI APROBACION (Etapa activa asignada a mi depto o mis perfiles)
$pendientes = $obBD_con1->getArrayConsultaSql("
    SELECT s.Sol_Cod, s.Sol_Num, s.Sol_Tit, s.Sol_Fec, s.Sol_Est, s.Sol_Pri, s.Sol_Val_Est, s.Usu_Sol, s.Emp_Cod,
           tr.Trq_Des,
           IFNULL(wfm.Wfm_Nom, 'Sin flujo') AS Wfm_Nom,
           COALESCE(wfm.Wfm_Fam_Cod, wfm.Wfm_Cod, tr.Wfm_Cod) AS Wfm_Fam_Cod,
           CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) AS Solicitante_Nom,
           d.Dep_Des,
           i.Ins_Cod,
           n.Nod_Nom, n.Nod_Sla, n.Nod_Tip, IFNULL(n.Nod_Cot_Edit, 0) AS Nod_Cot_Edit
    FROM adq_solicitudes s
    INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
    INNER JOIN usuarios u ON u.Usu_Cod = s.Usu_Sol
    INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
    LEFT JOIN departamen d ON d.Dep_Cod = s.Dep_Sol
    INNER JOIN wf_instancias i ON i.Ins_Ent_Typ = 'adq_solicitudes' AND i.Ins_Ent_Cod = s.Sol_Cod AND i.Ins_Est = 'P'
    INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
    LEFT JOIN wf_flujos_modelos wfm ON wfm.Wfm_Cod = COALESCE(i.Wfm_Cod, tr.Wfm_Cod)
    WHERE s.Emp_Cod = $emp_cod
      AND s.Sol_Est IN ('E', 'P')
      AND $clausula_nodo_usuario
      AND $filtro_pendiente_sin_auto
    ORDER BY s.Sol_Fec DESC, s.Sol_Cod DESC
    LIMIT 200;", $obBD_conexion);
if ($pendientes === false || $pendientes === null) {
    $pendientes = array();
}
foreach ($pendientes as $idx => $p) {
    $sol_est_ok = !in_array($p['Sol_Est'], array('A', 'R'), true);
    $cot_edit = $sol_est_ok && !empty($p['Ins_Cod'])
        ? ($wf_mgr->resolverNodCotEditInstancia(intval($p['Ins_Cod'])) === 1)
        : false;
    $cot_sel = $sol_est_ok && !empty($p['Ins_Cod'])
        ? ($wf_mgr->resolverNodCotSelInstancia(intval($p['Ins_Cod'])) === 1)
        : false;
    $pendientes[$idx]['Nod_Cot_Edit'] = $cot_edit ? 1 : 0;
    $pendientes[$idx]['Nod_Cot_Sel'] = $cot_sel ? 1 : 0;
    $pendientes[$idx]['Puede_Cargar_Cotizaciones'] = $cot_edit ? 1 : 0;
    $pendientes[$idx]['Puede_Seleccionar_Ganadora'] = $cot_sel ? 1 : 0;
    $pendientes[$idx]['Puede_Cargar_Avance'] = ($sol_est_ok && in_array($p['Nod_Tip'], array('AVANCE', 'FISCALIZACION'), true)) ? 1 : 0;
}

// B. MIS SOLICITUDES EN CURSO (Creadas por mi)
$mis_solicitudes = $obBD_con1->getArrayConsultaSql("
    SELECT s.Sol_Cod, s.Sol_Num, s.Sol_Tit, s.Sol_Fec, s.Sol_Est, s.Sol_Pri, s.Sol_Val_Est, s.Usu_Sol,
           tr.Trq_Des,
           IFNULL(wfm.Wfm_Nom, 'Sin flujo') AS Wfm_Nom,
           COALESCE(wfm.Wfm_Fam_Cod, wfm.Wfm_Cod, tr.Wfm_Cod) AS Wfm_Fam_Cod,
           i.Ins_Cod, n.Nod_Nom
    FROM adq_solicitudes s
    INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
    LEFT JOIN wf_instancias i ON i.Ins_Ent_Typ = 'adq_solicitudes' AND i.Ins_Ent_Cod = s.Sol_Cod AND i.Ins_Est = 'P'
    LEFT JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
    LEFT JOIN wf_flujos_modelos wfm ON wfm.Wfm_Cod = COALESCE(i.Wfm_Cod, tr.Wfm_Cod)
    WHERE s.Emp_Cod = $emp_cod AND s.Usu_Sol = $usu_cod AND s.Sol_Est IN ('E', 'O', 'P')
    ORDER BY s.Sol_Fec DESC, s.Sol_Cod DESC
    LIMIT 150;", $obBD_conexion);
if ($mis_solicitudes === false || $mis_solicitudes === null) {
    $mis_solicitudes = array();
}

// C. GESTIONE / PARTICIPE (solicitudes de otros en las que actue en el workflow)
$gestionadas = $obBD_con1->getArrayConsultaSql("
    SELECT s.Sol_Cod, s.Sol_Num, s.Sol_Tit, s.Sol_Fec, s.Sol_Est, s.Sol_Pri, s.Sol_Val_Est, s.Usu_Sol,
           tr.Trq_Des,
           IFNULL(wfm.Wfm_Nom, 'Sin flujo') AS Wfm_Nom,
           COALESCE(wfm.Wfm_Fam_Cod, wfm.Wfm_Cod, tr.Wfm_Cod) AS Wfm_Fam_Cod,
           CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) AS Solicitante_Nom,
           d.Dep_Des,
           i.Ins_Cod, i.Ins_Est AS Ins_Est_Act,
           n.Nod_Nom AS Etapa_Actual,
           h_last.Isn_Acc AS Mi_Accion,
           h_last.Isn_Fec AS Mi_Fecha,
           hn.Nod_Nom AS Mi_Etapa,
           CASE
             WHEN i.Ins_Est = 'P'
              AND s.Sol_Est <> 'O'
              AND ($clausula_nodo_usuario)
              AND (
                    s.Usu_Sol <> $usu_cod
                    OR $es_gerencial_sql = 1
                    OR (
                        n.Nod_Usu_Asig IS NOT NULL
                        AND n.Nod_Usu_Asig != ''
                        AND n.Nod_Usu_Asig != 'TODOS'
                        AND FIND_IN_SET($usu_cod, n.Nod_Usu_Asig) > 0
                    )
                    OR EXISTS (
                        SELECT 1 FROM wf_instancias_nodos hr
                        WHERE hr.Ins_Cod = i.Ins_Cod AND hr.Isn_Acc = 'REENVIAR'
                    )
              )
             THEN 1 ELSE 0
           END AS Puede_Resolver
    FROM adq_solicitudes s
    INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
    INNER JOIN usuarios u ON u.Usu_Cod = s.Usu_Sol
    INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
    LEFT JOIN departamen d ON d.Dep_Cod = s.Dep_Sol
    INNER JOIN wf_instancias i ON i.Ins_Ent_Typ = 'adq_solicitudes' AND i.Ins_Ent_Cod = s.Sol_Cod AND i.Ins_Est = 'P'
    LEFT JOIN wf_flujos_modelos wfm ON wfm.Wfm_Cod = COALESCE(i.Wfm_Cod, tr.Wfm_Cod)
    INNER JOIN (
        SELECT h1.Ins_Cod, h1.Isn_Acc, h1.Isn_Fec, h1.Nod_Cod
        FROM wf_instancias_nodos h1
        INNER JOIN (
            SELECT Ins_Cod, MAX(Isn_Fec) AS max_fec
            FROM wf_instancias_nodos
            WHERE Usu_Cod = $usu_cod AND Isn_Acc IN ('APROBAR', 'COMPLETAR', 'OBSERVAR', 'DEVOLVER', 'RECHAZAR')
            GROUP BY Ins_Cod
        ) hmx ON hmx.Ins_Cod = h1.Ins_Cod AND hmx.max_fec = h1.Isn_Fec AND h1.Usu_Cod = $usu_cod
        WHERE h1.Isn_Acc IN ('APROBAR', 'COMPLETAR', 'OBSERVAR', 'DEVOLVER', 'RECHAZAR')
    ) h_last ON h_last.Ins_Cod = i.Ins_Cod
    LEFT JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
    LEFT JOIN wf_nodos hn ON hn.Nod_Cod = h_last.Nod_Cod
    WHERE s.Emp_Cod = $emp_cod
    ORDER BY h_last.Isn_Fec DESC, s.Sol_Fec DESC, s.Sol_Cod DESC
    LIMIT 100;", $obBD_conexion);
if ($gestionadas === false || $gestionadas === null) {
    $gestionadas = array();
}

// D. HISTORIAL (cerrados: propios + donde participo; gerencia ve todos)
$historico_filtro_usuario = '';
if (!$es_gerencial_admin) {
    $historico_filtro_usuario = " AND (
        s.Usu_Sol = $usu_cod
        OR EXISTS (
            SELECT 1
            FROM wf_instancias i
            INNER JOIN wf_instancias_nodos h ON h.Ins_Cod = i.Ins_Cod
            WHERE i.Ins_Ent_Typ = 'adq_solicitudes'
              AND i.Ins_Ent_Cod = s.Sol_Cod
              AND h.Usu_Cod = $usu_cod
              AND h.Isn_Acc IN ('APROBAR', 'COMPLETAR', 'OBSERVAR', 'DEVOLVER', 'RECHAZAR')
        )
    )";
}

$historico = $obBD_con1->getArrayConsultaSql("
    SELECT s.Sol_Cod, s.Sol_Num, s.Sol_Tit, s.Sol_Fec, s.Sol_Est, s.Sol_Pri, s.Sol_Val_Est, s.Usu_Sol,
           tr.Trq_Des,
           IFNULL(wfm.Wfm_Nom, 'Sin flujo') AS Wfm_Nom,
           COALESCE(wfm.Wfm_Fam_Cod, wfm.Wfm_Cod, tr.Wfm_Cod) AS Wfm_Fam_Cod,
           CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) AS Solicitante_Nom,
           d.Dep_Des
    FROM adq_solicitudes s
    INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
    INNER JOIN usuarios u ON u.Usu_Cod = s.Usu_Sol
    INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
    LEFT JOIN departamen d ON d.Dep_Cod = s.Dep_Sol
    LEFT JOIN wf_flujos_modelos wfm ON wfm.Wfm_Cod = tr.Wfm_Cod
    WHERE s.Emp_Cod = $emp_cod AND s.Sol_Est IN ('A', 'R') $historico_filtro_usuario
    ORDER BY s.Sol_Fec DESC, s.Sol_Cod DESC
    LIMIT 100;", $obBD_conexion);
if ($historico === false || $historico === null) {
    $historico = array();
}

$flujos_opciones = array();
foreach ($wf_mgr->listarFlujosPublicados($emp_cod) as $f) {
    $fam = !empty($f['Wfm_Fam_Cod']) ? intval($f['Wfm_Fam_Cod']) : intval($f['Wfm_Cod']);
    $flujos_opciones[$fam] = $f['Wfm_Nom'];
}
foreach (array_merge($pendientes, $mis_solicitudes, $gestionadas, $historico) as $row) {
    if (!empty($row['Wfm_Fam_Cod']) && !empty($row['Wfm_Nom'])) {
        $flujos_opciones[intval($row['Wfm_Fam_Cod'])] = $row['Wfm_Nom'];
    }
}
asort($flujos_opciones, SORT_NATURAL | SORT_FLAG_CASE);
$filtro_wfm_fam = isset($_GET['filtro_wfm']) ? intval($_GET['filtro_wfm']) : 0;

function adqEtiquetaEstadoSolicitud($sol_est) {
    switch ($sol_est) {
        case 'P': return array('Borrador', 'secondary');
        case 'E': return array('En proceso', 'primary');
        case 'A': return array('Aprobada', 'success');
        case 'R': return array('Rechazada', 'danger');
        case 'O': return array('Observada', 'warning text-dark');
        default:  return array('Desconocido', 'secondary');
    }
}

function adqEtiquetaMiAccion($accion) {
    switch ($accion) {
        case 'APROBAR':  return array('Aprob' . "\xC3\xA9", 'success');
        case 'COMPLETAR': return array('Complet' . "\xC3\xB3", 'success');
        case 'OBSERVAR': return array('Observ' . "\xC3\xB3", 'warning text-dark');
        case 'DEVOLVER': return array('Devolv' . "\xC3\xAD", 'secondary');
        case 'RECHAZAR': return array('Rechaz' . "\xC3\xB3", 'danger');
        default:         return array($accion, 'secondary');
    }
}
?>
<!DOCTYPE html>
<html lang="es" class="exa-ui-fill-root">
<head>
    <meta charset="UTF-8">
    <title>Bandeja de Adquisiciones</title>
    <?php require_once('adq_model3_assets.php'); ?>
    <style>
        /* Tipografia mas legible en bandeja */
        .adq-bandj-page.exa-ui-panel > .panel-heading .panel-title {
            font-size: 18px;
        }

        /* Tabs estilo man_adm_configuracion (nav-tabs-custom) */
        .adq-bandj-page .nav-tabs-custom {
            margin-bottom: 0;
        }
        .adq-bandj-page .nav-tabs-custom > .nav-tabs {
            border-bottom: 3px solid #3c8dbc;
            margin-bottom: 0;
        }
        .adq-bandj-page .nav-tabs-custom > .nav-tabs > li {
            margin-right: 5px;
        }
        .adq-bandj-page .nav-tabs-custom > .nav-tabs > li > a {
            border-radius: 5px 5px 0 0;
            color: #444;
            background: #f4f4f4;
            border: 1px solid #ddd;
            border-bottom: none;
            padding: 10px 20px;
            font-weight: bold;
            margin-right: 0;
        }
        .adq-bandj-page .nav-tabs-custom > .nav-tabs > li.active > a,
        .adq-bandj-page .nav-tabs-custom > .nav-tabs > li.active > a:hover,
        .adq-bandj-page .nav-tabs-custom > .nav-tabs > li.active > a:focus {
            background: #3c8dbc;
            color: #fff;
            border-color: #3c8dbc;
        }
        .adq-bandj-page .nav-tabs-custom > .nav-tabs > li > a:hover {
            background: #e9ecef;
        }
        .adq-bandj-page .nav-tabs-custom > .nav-tabs > li.active > a:hover {
            background: #367fa9;
            color: #fff;
        }
        .adq-bandj-page .nav-tabs-custom .icon-tab {
            margin-right: 8px;
            font-size: 16px;
        }
        .adq-bandj-page .nav-tabs-custom > .nav-tabs > li > a .adq-tab-count,
        .adq-bandj-page .nav-tabs-custom > .nav-tabs > li > a .badge {
            display: inline-block;
            min-width: 18px;
            padding: 2px 6px;
            margin-left: 6px;
            border-radius: 10px;
            background: #dde4ea !important;
            color: #333 !important;
            font-size: 11px !important;
            font-weight: bold;
            line-height: 1.2;
            vertical-align: middle;
        }
        .adq-bandj-page .nav-tabs-custom > .nav-tabs > li.active > a .adq-tab-count,
        .adq-bandj-page .nav-tabs-custom > .nav-tabs > li.active > a .badge {
            background: #fff !important;
            color: #3c8dbc !important;
        }
        .adq-bandj-page .exa-ui-tab-content.panels-area {
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
            background: #fff !important;
        }

        .adq-bandj-page .adq-table-panel {
            display: flex;
            flex-direction: column;
            gap: 0;
        }
        .adq-bandj-page .exa-adq-table-wrap {
            border: 1px solid #64748b;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
            background: #ffffff;
            overflow: auto;
        }
        .adq-bandj-page .adq-table-panel .exa-adq-table-wrap {
            border-radius: 10px 10px 0 0;
            box-shadow: none;
        }
        .adq-bandj-page .adq-table-pager {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 12px;
            margin-top: -1px;
            background: #f8fafc;
            border: 1px solid #64748b;
            border-top: 1px solid #cbd5e1;
            border-radius: 0 0 10px 10px;
        }
        .adq-bandj-page .adq-table-pager-info {
            font-size: 12px;
            color: #475569;
            font-weight: 600;
        }
        .adq-bandj-page .adq-table-pager-controls {
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }
        .adq-bandj-page .adq-table-pager-pages {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .adq-bandj-page .adq-table-pager .btn {
            min-width: 34px;
            height: 32px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 6px;
            border: 1px solid #64748b;
            background: #ffffff;
            color: #334155;
        }
        .adq-bandj-page .adq-table-pager .btn:hover:not(:disabled) {
            background: #eff6ff;
            border-color: #3b82f6;
            color: #1e3a8a;
        }
        .adq-bandj-page .adq-table-pager .btn.active,
        .adq-bandj-page .adq-table-pager .btn.active:hover {
            background: #4b678a;
            border-color: #3a516e;
            color: #ffffff;
        }
        .adq-bandj-page .adq-table-pager .btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }
        .adq-bandj-page .adq-table-pager-size {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
        }
        .adq-bandj-page .adq-table-pager-size select {
            height: 32px;
            font-size: 12px;
            border-radius: 6px;
            border: 1px solid #64748b;
            padding: 2px 8px;
            background: #ffffff;
            color: #1e293b;
        }
        .adq-bandj-page .adq-row-solicitud.adq-filtro-oculto {
            display: none !important;
        }
        .adq-bandj-page .exa-adq-table {
            font-size: 13px;
            width: 100%;
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
            background: #ffffff;
        }
        .adq-bandj-page .exa-adq-table > thead > tr > th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: linear-gradient(180deg, #5f7ea3 0%, #4b678a 100%) !important;
            background-color: #4b678a !important;
            color: #ffffff !important;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 12px 12px !important;
            border: none !important;
            border-bottom: 2px solid #3a516e !important;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
        }
        .adq-bandj-page .exa-adq-table > tbody > tr > td {
            font-size: 13px;
            padding: 11px 12px !important;
            border-color: #cbd5e1 !important;
            border-left: none !important;
            border-right: none !important;
            color: #1e293b;
            vertical-align: middle !important;
            background: #ffffff;
        }
        .adq-bandj-page .exa-adq-table > tbody > tr > td:first-child {
            font-weight: 700;
            color: #1e3a8a;
        }
        .adq-bandj-page .exa-adq-table > tbody > tr:nth-child(even) > td {
            background: #f8fafc;
        }
        .adq-bandj-page .exa-adq-table > tbody > tr:hover > td {
            background: #eff6ff !important;
        }
        .adq-bandj-page .exa-adq-table tbody tr.text-center td.text-muted,
        .adq-bandj-page .exa-adq-table tbody td.text-muted {
            font-size: 13px !important;
            font-style: italic;
            color: #64748b !important;
            padding: 28px 16px !important;
            background: #f8fafc !important;
        }
        .adq-bandj-page .exa-adq-table .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 9px;
            border-radius: 999px;
            letter-spacing: 0.02em;
            border: 1px solid transparent;
            line-height: 1.2;
        }
        .adq-bandj-page .exa-adq-table .badge-alta {
            background: #fee2e2 !important;
            color: #b91c1c !important;
            border-color: #fca5a5;
        }
        .adq-bandj-page .exa-adq-table .badge-media {
            background: #ffedd5 !important;
            color: #c2410c !important;
            border-color: #fdba74;
        }
        .adq-bandj-page .exa-adq-table .badge-baja {
            background: #dcfce7 !important;
            color: #15803d !important;
            border-color: #86efac;
        }
        .adq-bandj-page .exa-adq-table .badge.bg-primary,
        .adq-bandj-page .exa-adq-table .badge-primary {
            background: #dbeafe !important;
            color: #1e40af !important;
            border-color: #93c5fd;
        }
        .adq-bandj-page .exa-adq-table .badge.bg-success,
        .adq-bandj-page .exa-adq-table .badge-success {
            background: #dcfce7 !important;
            color: #166534 !important;
            border-color: #86efac;
        }
        .adq-bandj-page .exa-adq-table .badge.bg-danger,
        .adq-bandj-page .exa-adq-table .badge-danger {
            background: #fee2e2 !important;
            color: #b91c1c !important;
            border-color: #fca5a5;
        }
        .adq-bandj-page .exa-adq-table .badge.bg-warning,
        .adq-bandj-page .exa-adq-table .badge-warning {
            background: #fef3c7 !important;
            color: #92400e !important;
            border-color: #fcd34d;
        }
        .adq-bandj-page .exa-adq-table .badge.bg-secondary,
        .adq-bandj-page .exa-adq-table .badge-secondary {
            background: #e2e8f0 !important;
            color: #334155 !important;
            border-color: #94a3b8;
        }
        .adq-bandj-page .exa-adq-table .badge.bg-info,
        .adq-bandj-page .exa-adq-table .badge-info {
            background: #e0f2fe !important;
            color: #0369a1 !important;
            border-color: #7dd3fc;
        }
        .adq-bandj-page .exa-adq-table .font-monospace {
            font-family: Consolas, "Courier New", monospace;
            font-weight: 700;
            color: #0f172a;
        }
        .adq-bandj-page .btn-sm {
            font-size: 13px;
            padding: 6px 12px;
        }
        .adq-bandj-page .exa-adq-table td.adq-col-acciones {
            white-space: nowrap;
            vertical-align: middle !important;
        }
        .adq-bandj-page .adq-acciones-row {
            display: inline-flex;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .adq-bandj-page .adq-btn-icon-only {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 9px;
            min-width: 36px;
            min-height: 36px;
            line-height: 1;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
        }
        .adq-bandj-page .adq-btn-icon-only i {
            font-size: 16px;
            line-height: 1;
        }
        .adq-bandj-page .adq-btn-icon-only.btn-primary {
            background: #1e3a8a;
            border-color: #1e3a8a;
        }
        .adq-bandj-page .adq-btn-icon-only.btn-info {
            background: #0369a1;
            border-color: #0369a1;
            color: #fff;
        }
        .adq-bandj-page p.text-muted,
        .adq-bandj-page .text-muted.small {
            font-size: 14px !important;
        }
        #mdlResolution .modal-title,
        #mdlSeguimiento .modal-title {
            font-size: 18px !important;
        }
        #mdlResolution .modal-body,
        #mdlSeguimiento .modal-body {
            font-size: 14px;
        }
        #mdlSegNodoDetalle {
            z-index: 1060;
        }
        #mdlSegNodoDetalle .modal-dialog {
            margin-top: 60px;
        }
        #mdlSegNodoDetalle .adq-seg-nodo-modal-header {
            background: linear-gradient(180deg, #5f7ea3 0%, #4b678a 100%);
            color: #ffffff;
            border-bottom: 1px solid #3a516e;
        }
        #mdlSegNodoDetalle .adq-seg-nodo-modal-header .modal-title {
            font-size: 16px;
            font-weight: 700;
            color: #ffffff;
        }
        #mdlSegNodoDetalle .adq-seg-nodo-modal-header .text-muted {
            color: #dbeafe !important;
        }
        #mdlSegNodoDetalle .adq-seg-nodo-modal-header .close {
            color: #ffffff;
            opacity: 0.85;
            text-shadow: none;
        }
        #mdlSegNodoDetalle .adq-seg-nodo-modal-header .close:hover {
            opacity: 1;
        }
        #mdlSegNodoDetalle .modal-body {
            background: #f8fafc;
            padding: 14px 16px;
        }
        body.modal-open .modal-backdrop.adq-seg-nodo-backdrop {
            z-index: 1055;
        }
        #create-panel {
            margin-left: -16px;
            margin-right: -16px;
            padding-left: 24px;
            padding-right: 24px;
        }
        #create-panel-content {
            font-size: 14px;
            width: 100%;
            max-width: none;
            padding: 12px 8px 32px;
            box-sizing: border-box;
        }
        #create-panel-content .adq-solicitud-form {
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 8px 6px 32px;
        }
        #create-panel-content .adq-field-block {
            margin-bottom: 22px;
        }
        #create-panel-content .form-label-req,
        #create-panel-content .adq-cot-label {
            display: block;
            margin-bottom: 8px;
            padding: 0;
            color: #0f172a;
            font-weight: 700;
        }
        #create-panel-content .form-check-label {
            color: #1e293b;
            font-weight: 600;
        }
        #create-panel-content .adq-field-hint {
            color: #334155 !important;
        }
        #create-panel-content #divJustificacionComercial,
        #create-panel-content #divDescripcionDetallada {
            min-width: 0;
        }
        #create-panel-content .adq-row-textareas textarea.form-control-adq {
            width: 100%;
            min-height: 96px;
        }
        #create-panel-content .adq-form-fields-stack .row {
            margin-left: 0;
            margin-right: 0;
        }
        #create-panel-content select.form-control-adq {
            min-height: 42px;
            height: auto;
            line-height: 1.4;
            padding: 10px 32px 10px 14px;
        }
        #create-panel-content .select2-container {
            width: 100% !important;
            max-width: 100%;
            box-sizing: border-box;
        }
        #create-panel-content .select2-container--default .select2-selection--single {
            height: 44px !important;
        }
        #create-panel-content .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px;
            padding: 0 36px 0 14px;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            display: block;
        }
        #create-panel-content .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px;
            top: 1px;
        }
        #create-panel-content .adq-cot-col,
        #create-panel-content .adq-cot-provider-row .select-wrap {
            min-width: 0;
            max-width: 100%;
        }
        #create-panel .row.g-3,
        #create-panel #cotizacionesList {
            margin-left: 0;
            margin-right: 0;
        }
        #create-panel-content .adq-cot-pdf-section {
            width: 100%;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
        }
        #create-panel-content .adq-proformas-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
        }
        #create-panel-content .adq-proformas-head .adq-cot-label {
            margin-bottom: 0;
        }
        #create-panel-content .adq-proformas-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
        }
        #create-panel-content .adq-proforma-row {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 12px;
        }
        #create-panel-content .adq-proforma-row.adq-proforma-ganadora {
            border-color: #86efac;
            background: #f0fdf4;
        }
        #create-panel-content .adq-proforma-fields {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 10px 12px;
        }
        #create-panel-content .adq-proforma-pdf {
            flex: 1 1 200px;
            min-width: 180px;
            max-width: 280px;
        }
        #create-panel-content .adq-proforma-pdf .adq-file-upload,
        #create-panel-content .adq-proforma-pdf .adq-cot-pdfs-inline {
            width: 100%;
        }
        #create-panel-content .adq-proforma-val {
            flex: 0 0 130px;
            min-width: 120px;
        }
        #create-panel-content .adq-proforma-actions {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
        }
        #create-panel-content .adq-proforma-jus {
            width: 100%;
            margin-top: 8px;
        }
        #create-panel-content .adq-proforma-remove {
            color: #dc2626;
            padding: 4px 6px;
        }
        #create-panel-content .adq-cot-main-row {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 10px 12px;
        }
        #create-panel-content .adq-cot-top-prov {
            flex: 1 1 100%;
            min-width: 240px;
        }
        #create-panel-content .adq-cot-provider-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        #create-panel-content .adq-cot-provider-row .select-wrap {
            flex: 1 1 auto;
            min-width: 0;
        }
        #create-panel-content .adq-btn-add-pdf-cot {
            min-height: 34px;
            height: 34px;
            min-width: auto;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            white-space: nowrap;
            flex: 0 0 auto;
            background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
            border: 1px solid #1e40af;
            color: #ffffff;
            box-shadow: 0 1px 4px rgba(37, 99, 235, 0.3);
        }
        #create-panel-content .adq-btn-add-pdf-cot:hover,
        #create-panel-content .adq-btn-add-pdf-cot:focus {
            background: linear-gradient(180deg, #1d4ed8 0%, #1e3a8a 100%);
            border-color: #1e3a8a;
            color: #ffffff;
        }
        #create-panel-content .adq-cot-remove {
            color: #ffffff;
            background: #dc2626;
            border: 1px solid #b91c1c;
            border-radius: 8px;
            width: 36px;
            height: 36px;
            min-width: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            padding: 0;
            opacity: 1;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }
        #create-panel-content .adq-cot-remove:hover,
        #create-panel-content .adq-cot-remove:focus {
            color: #ffffff;
            background: #b91c1c;
            border-color: #991b1b;
        }
        #create-panel-content .adq-cot-winner.adq-cot-winner-on {
            display: inline-flex !important;
            align-items: center;
            gap: 4px;
            min-height: 32px;
            cursor: pointer;
            pointer-events: auto !important;
            opacity: 1 !important;
        }
        #create-panel-content .adq-cot-winner .chk-cot-sel {
            pointer-events: auto !important;
            width: 16px;
            height: 16px;
            margin: 0;
            position: static;
            opacity: 1 !important;
        }
        #create-panel-content .adq-cot-winner-text {
            font-size: 11px;
            font-weight: 700;
        }
        #create-panel-content .adq-cot-winner.adq-cot-winner-off {
            display: none !important;
        }
        .adq-msg-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 2000;
            background: rgba(15, 23, 42, 0.45);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .adq-msg-box {
            background: #ffffff;
            border-radius: 12px;
            padding: 32px 36px;
            max-width: 440px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.2);
        }
        .adq-msg-box .adq-msg-icon {
            font-size: 52px;
            color: #10b981;
            line-height: 1;
        }
        .adq-msg-box.is-error .adq-msg-icon { color: #ef4444; }
        .adq-msg-box.is-danger .adq-msg-icon { color: #dc2626; }
        .adq-msg-box.is-warning .adq-msg-icon { color: #f59e0b; }
        .adq-msg-box.is-info .adq-msg-icon { color: #0ea5e9; }
        .adq-msg-box.is-success .adq-msg-icon { color: #10b981; }
        .adq-msg-box .adq-msg-title {
            margin: 14px 0 8px;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
        }
        .adq-msg-box .adq-msg-text {
            margin: 8px 0 22px;
            font-size: 14px;
            font-weight: 500;
            color: #475569;
            line-height: 1.55;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .adq-msg-box.is-danger {
            border-top: 4px solid #dc2626;
        }
        .adq-msg-box.is-warning {
            border-top: 4px solid #f59e0b;
        }
        .adq-msg-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .adq-msg-actions .btn {
            min-width: 110px;
        }
        .adq-bandj-filters {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            flex-wrap: wrap;
            margin: 0;
            background: #ffffff;
            border-left: 1px solid #ddd;
            border-right: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
        }
        .adq-bandj-filters label {
            margin: 0;
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            white-space: nowrap;
        }
        .adq-bandj-filters select {
            max-width: 320px;
            min-width: 200px;
            font-size: 13px;
            height: 34px;
            border-radius: 8px;
            border-color: #cbd5e1;
        }
        /* Vista de resolucion embebida (reemplaza la tabla, ocupa todo el ancho) */
        .adq-resolution-embed {
            width: 100%;
        }
        .adq-resolution-embed .adq-resolution-content {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.08);
        }
        .adq-resolution-embed .modal-header.adq-resolution-header {
            position: relative;
        }
        #mdlResolution.adq-resolution-embed .modal-body.adq-resolution-body {
            max-height: none;
            overflow: visible;
        }
        .adq-btn-quitar-factura {
            background-color: #dc3545 !important;
            border-color: #dc3545 !important;
            color: #ffffff !important;
            white-space: nowrap;
            font-size: 12px;
            padding: 4px 10px;
            line-height: 1.3;
        }
        .adq-cot-pdf-cell .adq-cot-pdf-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }
        .adq-cot-pdf-cell .adq-cot-pdf-link {
            background-color: #1e3a8a !important;
            border-color: #1e3a8a !important;
            color: #fff !important;
            margin: 0;
            white-space: nowrap;
        }
        #create-panel-content .adq-cot-pdfs-inline {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .adq-avance-factura-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 6px;
        }
        .adq-avance-factura-titulo {
            flex: 1 1 auto;
            min-width: 0;
        }
        .adq-avance-quitar-wrap {
            flex: 0 0 auto;
            margin-left: auto;
            padding-top: 10px;
        }
        .adq-btn-quitar-factura:hover,
        .adq-btn-quitar-factura:focus {
            background-color: #bb2d3b !important;
            border-color: #b02a37 !important;
            color: #ffffff !important;
        }
        .adq-fiscal-file-control {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 32px;
            padding: 4px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
        }
        .adq-fiscal-file-native {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            opacity: 0;
            pointer-events: none;
        }
        .adq-fiscal-file-btn {
            margin: 0;
            padding: 5px 10px;
            border-radius: 6px;
            background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
            border: 1px solid #1e40af;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(37, 99, 235, 0.25);
        }
        .adq-fiscal-file-btn:hover,
        .adq-fiscal-file-btn:focus {
            color: #ffffff;
            background: linear-gradient(180deg, #1d4ed8 0%, #1e3a8a 100%);
        }
        .adq-fiscal-file-name {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #64748b;
            font-size: 11px;
        }
        .adq-fiscal-file-control.has-file {
            border-color: #86efac;
            background: #f0fdf4;
        }
        .adq-fiscal-file-control.has-file .adq-fiscal-file-name {
            color: #166534;
            font-weight: 600;
        }
        .adq-avance-save-message {
            margin: 0 0 10px;
            padding: 10px 14px;
            border: 1px solid #047857;
            border-left: 5px solid #064e3b;
            border-radius: 8px;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.35;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.28);
        }
        .adq-avance-save-message::before {
            content: "\2713";
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            margin-right: 8px;
            border-radius: 50%;
            background: #ffffff;
            color: #047857;
            font-size: 13px;
            font-weight: 900;
            vertical-align: middle;
        }
        .adq-file-native {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
        }
        .adq-dropzone {
            border: 1.5px dashed #93c5fd;
            border-radius: 8px;
            background: #f8fbff;
            padding: 12px;
            cursor: pointer;
            transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease;
            outline: none;
        }
        .adq-dropzone:hover,
        .adq-dropzone:focus {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        .adq-dropzone.adq-dragover {
            border-color: #2563eb;
            background: #dbeafe;
            box-shadow: inset 0 0 0 2px rgba(37, 99, 235, .15);
        }
        .adq-dropzone-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 2px;
            color: #1d4ed8;
        }
        .adq-dropzone-icon {
            font-size: 22px;
            color: #3b82f6;
            line-height: 1;
        }
        .adq-dropzone-text {
            font-size: 12px;
            color: #1e3a8a;
        }
        .adq-dropzone-hint {
            font-size: 10px;
            color: #64748b;
        }
        .adq-dropzone-file {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 6px;
            text-align: left;
        }
        .adq-selected-file {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 8px;
            border: 1px solid #bfdbfe;
            border-radius: 7px;
            background: #ffffff;
        }
        .adq-selected-files-summary {
            margin-bottom: 2px;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 700;
        }
        .adq-file-icon {
            font-size: 26px;
            color: #dc2626;
            flex-shrink: 0;
        }
        .adq-file-info {
            flex: 1 1 auto;
            min-width: 0;
        }
        .adq-file-name {
            font-size: 12px;
            font-weight: 600;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .adq-file-size {
            font-size: 10px;
            color: #64748b;
        }
        .adq-file-remove {
            flex-shrink: 0;
            border: none;
            background: #fee2e2;
            color: #dc2626;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color .15s ease, color .15s ease;
        }
        .adq-file-remove:hover {
            background: #dc2626;
            color: #ffffff;
        }
        .adq-dropzone.adq-dropzone-invalid {
            border-color: #f87171;
            background: #fef2f2;
        }
        #panelExpedienteFin.adq-exp-panel {
            border-color: #7c3aed;
            background: #faf5ff;
            padding: 10px 14px;
        }
        #panelExpedienteFin .adq-exp-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px 12px;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e9d5ff;
        }
        #panelExpedienteFin .adq-exp-head-download {
            margin-left: auto;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        #panelExpedienteFin .adq-exp-title {
            font-size: 14px;
            font-weight: 700;
            color: #6d28d9;
            white-space: nowrap;
        }
        #panelExpedienteFin .adq-exp-title i {
            margin-right: 5px;
        }
        #panelExpedienteFin .adq-exp-status {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            flex: 1 1 auto;
        }
        #panelExpedienteFin .adq-exp-msg {
            font-size: 11px;
            color: #15803d;
            display: none;
        }
        #panelExpedienteFin .adq-exp-msg.is-visible {
            display: inline;
        }
        #panelExpedienteFin .adq-exp-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 999px;
            line-height: 1.5;
        }
        #panelExpedienteFin .adq-exp-badge-ok {
            background: #dcfce7;
            color: #166534;
        }
        #panelExpedienteFin .adq-exp-badge-pend {
            background: #fef3c7;
            color: #92400e;
        }
        #panelExpedienteFin .adq-exp-steps {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
            width: 100%;
            padding-top: 2px;
        }
        #panelExpedienteFin .adq-exp-steps-row {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 10px 14px;
            width: 100%;
        }
        #panelExpedienteFin .adq-exp-steps-firma {
            padding-top: 8px;
            border-top: 1px dashed #e9d5ff;
        }
        #panelExpedienteFin .adq-exp-step {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 0 0 auto;
            min-width: 0;
        }
        #panelExpedienteFin .adq-exp-step-upload {
            flex: 1 1 0;
            min-width: 0;
        }
        #panelExpedienteFin .adq-exp-step-upload .adq-exp-file {
            flex: 1 1 80px;
            width: auto;
            max-width: none;
            min-width: 80px;
        }
        #panelExpedienteFin .adq-exp-step-num {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #7c3aed;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        #panelExpedienteFin .adq-exp-step-firma {
            flex: 1 1 100%;
            width: 100%;
            min-width: 0;
            flex-wrap: wrap;
        }
        #panelExpedienteFin #expLlaveP12,
        #panelExpedienteFin .adq-exp-clave {
            flex: 1 1 140px;
            width: auto;
            min-width: 120px;
            max-width: none;
            height: 30px;
            font-size: 12px;
            padding: 5px 10px;
            line-height: 1.3;
        }
        #panelExpedienteFin .adq-exp-step.is-disabled,
        #panelExpedienteFin #expFirmaBlock.is-disabled {
            opacity: .55;
            pointer-events: none;
        }
        #panelExpedienteFin .adq-exp-file {
            font-size: 12px;
            padding: 4px 8px;
            height: 30px;
            line-height: 1.3;
        }
        #panelExpedienteFin .adq-exp-check {
            font-size: 11px;
            color: #5b21b6;
            margin: 0;
            white-space: nowrap;
            cursor: pointer;
        }
        #panelExpedienteFin .adq-exp-check input {
            margin: 0 4px 0 0;
            vertical-align: middle;
        }
        #panelExpedienteFin .btn-xs.adq-exp-btn {
            padding: 5px 12px;
            font-size: 12px;
            line-height: 1.35;
            height: 30px;
            flex-shrink: 0;
        }
        #panelExpedienteFin #expDescargaFirmadoBlock {
            flex: 1 1 0;
            min-width: 0;
            justify-content: flex-end;
        }
        #panelExpedienteFin #expDescargaFirmadoBlock .adq-exp-btn {
            width: 100%;
            max-width: 100%;
        }
        #panelExpedienteFin .adq-exp-sep {
            width: 1px;
            height: 28px;
            background: #ddd6fe;
            flex-shrink: 0;
            margin: 0 2px;
        }
        @media (max-width: 767px) {
            #panelExpedienteFin .adq-exp-steps-row {
                flex-wrap: wrap;
                gap: 10px;
            }
            #panelExpedienteFin .adq-exp-sep {
                display: none;
            }
            #panelExpedienteFin .adq-exp-step,
            #panelExpedienteFin .adq-exp-step-upload,
            #panelExpedienteFin .adq-exp-step-firma,
            #panelExpedienteFin #expDescargaFirmadoBlock {
                width: 100%;
                flex: 1 1 100%;
            }
            #panelExpedienteFin .adq-exp-file {
                max-width: none;
                flex: 1 1 auto;
            }
            #panelExpedienteFin #expLlaveP12,
            #panelExpedienteFin .adq-exp-clave {
                flex: 1 1 100%;
                width: 100%;
                min-width: 0;
            }
        }
    </style>
</head>
<body class="exa-ui-fill-root">
    <div class="panel panel-main exa-ui-panel exa-ui-fill-page adq-bandj-page">
        <div class="panel-heading exa-header exa-header-flex">
            <h3 class="panel-title"><i class="bi bi-inboxes"></i> Bandeja de Adquisiciones</h3>
            <a href="adq_lista_solicitud.php" class="btn btn-default btn-sm"><i class="bi bi-collection"></i> Todas las Solicitudes</a>
        </div>
        <div class="panel-body exa-body">
            <div class="exa-ui-page-view">
        <div class="nav-tabs-custom">
        <ul class="nav nav-tabs" id="inboxTabs" role="tablist">
            <li role="presentation">
                <a href="#create-panel" id="create-tab" aria-controls="create-panel" role="tab" data-toggle="tab">
                    <i class="bi bi-file-earmark-plus icon-tab"></i>Crear Solicitud
                </a>
            </li>
            <li role="presentation" class="active">
                <a href="#pending-panel" id="pending-tab" aria-controls="pending-panel" role="tab" data-toggle="tab">
                    <i class="bi bi-clipboard-check icon-tab"></i>Mis Pendientes
                    <span class="badge adq-tab-count"><?php echo count($pendientes); ?></span>
                </a>
            </li>
            <li role="presentation">
                <a href="#my-panel" id="my-tab" aria-controls="my-panel" role="tab" data-toggle="tab">
                    <i class="bi bi-person-workspace icon-tab"></i>Mis Solicitudes
                    <span class="badge adq-tab-count"><?php echo count($mis_solicitudes); ?></span>
                </a>
            </li>
            <li role="presentation">
                <a href="#managed-panel" id="managed-tab" aria-controls="managed-panel" role="tab" data-toggle="tab">
                    <i class="bi bi-check2-square icon-tab"></i>Gestion&eacute;
                    <span class="badge adq-tab-count"><?php echo count($gestionadas); ?></span>
                </a>
            </li>
            <li role="presentation">
                <a href="#history-panel" id="history-tab" aria-controls="history-panel" role="tab" data-toggle="tab">
                    <i class="bi bi-clock-history icon-tab"></i>Historial
                    <span class="badge adq-tab-count"><?php echo count($historico); ?></span>
                </a>
            </li>
        </ul>
        </div>

        <div class="adq-bandj-filters" id="adqBandjFiltersFlujo">
            <label for="filtroFlujo"><i class="bi bi-diagram-3"></i> Filtrar por flujo:</label>
            <select id="filtroFlujo" class="form-control input-sm">
                <option value="">Todos los flujos</option>
                <?php foreach ($flujos_opciones as $fam_cod => $wfm_nom) { ?>
                    <option value="<?php echo intval($fam_cod); ?>"<?php echo ($filtro_wfm_fam === intval($fam_cod)) ? ' selected' : ''; ?>><?php echo htmlspecialchars($wfm_nom); ?></option>
                <?php } ?>
            </select>
        </div>

        <div class="tab-content exa-ui-tab-content panels-area" id="inboxTabsContent">
            <!-- 1. MIS PENDIENTES -->
            <div class="tab-pane active" id="pending-panel" role="tabpanel">
                <div class="adq-table-panel" data-page-size="20">
                <div class="exa-adq-table-wrap">
                    <table class="table table-bordered exa-adq-table adq-table-paginated">
                        <thead>
                            <tr>
                                <th style="width: 100px;">N&deg; Sol.</th>
                                <th>Nombre</th>
                                <th>Flujo</th>
                                <th style="width: 150px;">Fecha</th>
                                <th>Solicitante</th>
                                <th>Departamento</th>
                                <th>Tipo Pedido</th>
                                <th style="width: 100px;">Prioridad</th>
                                <th style="width: 150px;">Valor Est.</th>
                                <th>Paso Actual Workflow</th>
                                <th style="width: 110px;">Acci&oacute;n</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pendientes)) { ?>
                                <tr class="text-center"><td colspan="11" class="text-muted py-4">No posee requerimientos de adquisiciones pendientes de aprobaci&oacute;n en este momento.</td></tr>
                            <?php } else { 
                                foreach ($pendientes as $p) { ?>
                                    <tr class="text-center adq-row-solicitud" data-wfm-fam="<?php echo intval($p['Wfm_Fam_Cod']); ?>">
                                        <td class="fw-bold"><?php echo $p['Sol_Num']; ?></td>
                                        <td class="text-start"><?php echo htmlspecialchars(isset($p['Sol_Tit']) ? $p['Sol_Tit'] : '', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-start"><?php echo htmlspecialchars($p['Wfm_Nom']); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($p['Sol_Fec'])); ?></td>
                                        <td class="text-start"><?php echo $p['Solicitante_Nom']; ?></td>
                                        <td><?php echo htmlspecialchars(adqTextoDepartamentoSolicitante(isset($p['Dep_Des']) ? $p['Dep_Des'] : ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-start"><?php echo $p['Trq_Des']; ?></td>
                                        <td><span class="badge badge-<?php echo strtolower($p['Sol_Pri']); ?>"><?php echo $p['Sol_Pri']; ?></span></td>
                                        <td class="text-end fw-bold font-monospace">$ <?php echo number_format($p['Sol_Val_Est'], 2); ?></td>
                                        <td><span class="badge bg-primary fs-6"><i class="bi bi-clock"></i> <?php echo $p['Nod_Nom']; ?></span></td>
                                        <td class="adq-col-acciones">
                                            <div class="adq-acciones-row">
                                                <?php if ($p['Sol_Est'] === 'P') { ?>
                                                <button type="button" class="btn btn-sm btn-warning text-dark adq-btn-icon-only" title="Completar solicitud" onclick="abrirCompletarSolicitud(<?php echo $p['Sol_Cod']; ?>)"><i class="bi bi-clipboard-check"></i></button>
                                                <?php } else { ?>
                                                <button type="button" class="btn btn-sm btn-primary adq-btn-icon-only" title="Resolver" onclick="abrirResolucion(<?php echo $p['Sol_Cod']; ?>, true)"><i class="bi bi-shield-check"></i></button>
                                                <?php } ?>
                                                <?php if (!empty($p['Puede_Cargar_Cotizaciones']) || !empty($p['Puede_Seleccionar_Ganadora'])) {
                                                    $titulo_cot = !empty($p['Puede_Cargar_Cotizaciones'])
                                                        ? 'Cargar cotizaciones / proformas'
                                                        : 'Seleccionar cotizacion ganadora';
                                                    $ico_cot = !empty($p['Puede_Cargar_Cotizaciones'])
                                                        ? 'bi-file-earmark-pdf'
                                                        : 'bi-trophy';
                                                ?>
                                                <button type="button" class="btn btn-sm btn-info adq-btn-icon-only" title="<?php echo htmlspecialchars($titulo_cot, ENT_QUOTES, 'UTF-8'); ?>" onclick="abrirEdicionCotizaciones(<?php echo $p['Sol_Cod']; ?>)"><i class="bi <?php echo $ico_cot; ?>"></i></button>
                                                <?php } ?>
                                                <?php if (!empty($p['Puede_Cargar_Avance'])) { ?>
                                                <button type="button" class="btn btn-sm btn-info adq-btn-icon-only" title="Cargar documentos de avance" onclick="abrirResolucion(<?php echo $p['Sol_Cod']; ?>, true)"><i class="bi bi-folder-plus"></i></button>
                                                <?php } ?>
                                            </div>
                                        </td>
                                    </tr>
                            <?php } 
                            } ?>
                        </tbody>
                    </table>
                </div>
                <div class="adq-table-pager"></div>
                </div>
            </div>

            <!-- 2. MIS SOLICITUDES -->
            <div class="tab-pane" id="my-panel" role="tabpanel">
                <div class="adq-table-panel" data-page-size="20">
                <div class="exa-adq-table-wrap">
                    <table class="table table-bordered exa-adq-table adq-table-paginated">
                        <thead>
                            <tr>
                                <th style="width: 100px;">N&deg; Sol.</th>
                                <th>Nombre</th>
                                <th>Flujo</th>
                                <th style="width: 150px;">Fecha</th>
                                <th>Tipo Pedido</th>
                                <th style="width: 100px;">Prioridad</th>
                                <th style="width: 150px;">Valor Est.</th>
                                <th>Estado Solicitud</th>
                                <th>Etapa Workflow</th>
                                <th style="width: 120px;">Acci&oacute;n</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mis_solicitudes)) { ?>
                                <tr class="text-center"><td colspan="10" class="text-muted py-4">No ha iniciado requerimientos de adquisici&oacute;n a&uacute;n.</td></tr>
                            <?php } else { 
                                foreach ($mis_solicitudes as $ms) { 
                                    $est = 'En espera de completar'; $badge = 'secondary';
                                    if ($ms['Sol_Est'] == 'E') { $est = 'En Workflow'; $badge = 'primary'; }
                                    elseif ($ms['Sol_Est'] == 'A') { $est = 'Aprobada'; $badge = 'success'; }
                                    elseif ($ms['Sol_Est'] == 'R') { $est = 'Rechazada'; $badge = 'danger'; }
                                    elseif ($ms['Sol_Est'] == 'O') { $est = 'Observada'; $badge = 'warning text-dark'; }
                                    $etapa = !empty($ms['Nod_Nom']) ? $ms['Nod_Nom'] : (($ms['Sol_Est'] == 'P') ? 'Pendiente de completar' : '[Inactivo]');
                                    ?>
                                    <tr class="text-center adq-row-solicitud" data-wfm-fam="<?php echo intval($ms['Wfm_Fam_Cod']); ?>">
                                        <td class="fw-bold"><?php echo $ms['Sol_Num']; ?></td>
                                        <td class="text-start"><?php echo htmlspecialchars(isset($ms['Sol_Tit']) ? $ms['Sol_Tit'] : '', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-start"><?php echo htmlspecialchars($ms['Wfm_Nom']); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($ms['Sol_Fec'])); ?></td>
                                        <td class="text-start"><?php echo $ms['Trq_Des']; ?></td>
                                        <td><span class="badge badge-<?php echo strtolower($ms['Sol_Pri']); ?>"><?php echo $ms['Sol_Pri']; ?></span></td>
                                        <td class="text-end fw-bold font-monospace">$ <?php echo number_format($ms['Sol_Val_Est'], 2); ?></td>
                                        <td><span class="badge bg-<?php echo $badge; ?>"><?php echo $est; ?></span></td>
                                        <td><span class="text-dark fw-bold"><?php echo htmlspecialchars($etapa); ?></span></td>
                                        <td class="adq-col-acciones">
                                            <div class="adq-acciones-row">
                                                <button type="button" class="btn btn-sm btn-outline-dark adq-btn-icon-only" title="Detalle" onclick="abrirResolucion(<?php echo $ms['Sol_Cod']; ?>, false)"><i class="bi bi-eye"></i></button>
                                            <?php if ($ms['Sol_Est'] == 'O') { ?>
                                                <button type="button" class="btn btn-sm btn-warning text-dark adq-btn-icon-only" title="Corregir" onclick="abrirEdicionBorrador(<?php echo $ms['Sol_Cod']; ?>)"><i class="bi bi-pencil"></i></button>
                                                <button type="button" class="btn btn-sm btn-success adq-btn-icon-only" title="Reenviar correccion" onclick="reenviarObservada(<?php echo $ms['Sol_Cod']; ?>, '<?php echo htmlspecialchars($ms['Sol_Num'], ENT_QUOTES, 'UTF-8'); ?>')"><i class="bi bi-send-check"></i></button>
                                            <?php } ?>
                                            </div>
                                        </td>
                                    </tr>
                            <?php } 
                            } ?>
                        </tbody>
                    </table>
                </div>
                <div class="adq-table-pager"></div>
                </div>
            </div>

            <!-- 3. GESTION? / PARTICIP? -->
            <div class="tab-pane" id="managed-panel" role="tabpanel">
                <p class="text-muted small mb-2" style="padding: 0 4px;">Solicitudes de otros usuarios en las que usted ya registro una decision en el workflow. Siguen visibles aunque ya no esten en sus pendientes.</p>
                <div class="adq-table-panel" data-page-size="20">
                <div class="exa-adq-table-wrap">
                    <table class="table table-bordered exa-adq-table adq-table-paginated">
                        <thead>
                            <tr>
                                <th style="width: 100px;">N&deg; Sol.</th>
                                <th>Flujo</th>
                                <th style="width: 130px;">Mi gestion</th>
                                <th style="width: 130px;">Fecha gestion</th>
                                <th>Solicitante</th>
                                <th>Tipo Pedido</th>
                                <th style="width: 130px;">Valor Est.</th>
                                <th>Estado actual</th>
                                <th>Etapa actual</th>
                                <th style="width: 100px;">Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($gestionadas)) { ?>
                                <tr class="text-center"><td colspan="10" class="text-muted py-4">Aun no ha gestionado solicitudes de otros usuarios.</td></tr>
                            <?php } else {
                                foreach ($gestionadas as $g) {
                                    list($est, $badge) = adqEtiquetaEstadoSolicitud($g['Sol_Est']);
                                    list($mi_acc, $mi_badge) = adqEtiquetaMiAccion($g['Mi_Accion']);
                                    $etapa_actual = $g['Etapa_Actual'] ? $g['Etapa_Actual'] : '[Sin etapa]';
                                    if ($g['Sol_Est'] == 'A' || $g['Sol_Est'] == 'R') {
                                        $etapa_actual = 'Cerrado';
                                    }
                                    ?>
                                    <tr class="text-center adq-row-solicitud" data-wfm-fam="<?php echo intval($g['Wfm_Fam_Cod']); ?>">
                                        <td class="fw-bold"><?php echo $g['Sol_Num']; ?></td>
                                        <td class="text-start"><?php echo htmlspecialchars($g['Wfm_Nom']); ?></td>
                                        <td><span class="badge bg-<?php echo $mi_badge; ?>"><?php echo $mi_acc; ?></span><div class="text-muted" style="font-size:10px;"><?php echo htmlspecialchars($g['Mi_Etapa']); ?></div></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($g['Mi_Fecha'])); ?></td>
                                        <td class="text-start"><?php echo htmlspecialchars($g['Solicitante_Nom']); ?></td>
                                        <td class="text-start"><?php echo htmlspecialchars($g['Trq_Des']); ?></td>
                                        <td class="text-end fw-bold font-monospace">$ <?php echo number_format($g['Sol_Val_Est'], 2); ?></td>
                                        <td><span class="badge bg-<?php echo $badge; ?>"><?php echo $est; ?></span></td>
                                        <td><span class="text-dark fw-bold"><?php echo htmlspecialchars($etapa_actual); ?></span></td>
                                        <td class="adq-col-acciones">
                                            <div class="adq-acciones-row">
                                                <button type="button" class="btn btn-sm btn-outline-dark adq-btn-icon-only" title="Detalle" onclick="abrirResolucion(<?php echo $g['Sol_Cod']; ?>, false)"><i class="bi bi-eye"></i></button>
                                            <?php if (!empty($g['Puede_Resolver']) && in_array($g['Sol_Est'], array('E', 'P'), true)) { ?>
                                                <button type="button" class="btn btn-sm btn-primary adq-btn-icon-only" title="Resolver nuevamente" onclick="abrirResolucion(<?php echo $g['Sol_Cod']; ?>, true)"><i class="bi bi-shield-check"></i></button>
                                            <?php } ?>
                                            </div>
                                        </td>
                                    </tr>
                            <?php }
                            } ?>
                        </tbody>
                    </table>
                </div>
                <div class="adq-table-pager"></div>
                </div>
            </div>

            <!-- 4. HISTORIAL (cerrados) -->
            <div class="tab-pane" id="history-panel" role="tabpanel">
                <p class="text-muted small mb-2" style="padding: 0 4px;"><?php if ($es_gerencial_admin) { ?>Solicitudes finalizadas (aprobadas o rechazadas) de toda la empresa.<?php } else { ?>Solicitudes finalizadas que usted creo o en las que participo en el workflow.<?php } ?></p>
                <div class="adq-table-panel" data-page-size="20">
                <div class="exa-adq-table-wrap">
                    <table class="table table-bordered exa-adq-table adq-table-paginated">
                        <thead>
                            <tr>
                                <th style="width: 100px;">N&deg; Sol.</th>
                                <th>Flujo</th>
                                <th style="width: 150px;">Fecha</th>
                                <th>Solicitante</th>
                                <th>Departamento</th>
                                <th>Tipo Pedido</th>
                                <th style="width: 100px;">Prioridad</th>
                                <th style="width: 150px;">Valor Est.</th>
                                <th>Estado</th>
                                <th style="width: 100px;">Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($historico)) { ?>
                                <tr class="text-center"><td colspan="10" class="text-muted py-4"><?php echo $es_gerencial_admin ? 'No hay solicitudes cerradas registradas.' : 'No tiene solicitudes cerradas propias ni en las que haya participado.'; ?></td></tr>
                            <?php } else {
                            foreach ($historico as $h) {
                                list($est, $badge) = adqEtiquetaEstadoSolicitud($h['Sol_Est']);
                                ?>
                                <tr class="text-center adq-row-solicitud" data-wfm-fam="<?php echo intval($h['Wfm_Fam_Cod']); ?>">
                                    <td class="fw-bold"><?php echo $h['Sol_Num']; ?></td>
                                    <td class="text-start"><?php echo htmlspecialchars($h['Wfm_Nom']); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($h['Sol_Fec'])); ?></td>
                                    <td class="text-start"><?php echo $h['Solicitante_Nom']; ?></td>
                                    <td><?php echo htmlspecialchars(adqTextoDepartamentoSolicitante(isset($h['Dep_Des']) ? $h['Dep_Des'] : ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-start"><?php echo $h['Trq_Des']; ?></td>
                                    <td><span class="badge badge-<?php echo strtolower($h['Sol_Pri']); ?>"><?php echo $h['Sol_Pri']; ?></span></td>
                                    <td class="text-end fw-bold font-monospace">$ <?php echo number_format($h['Sol_Val_Est'], 2); ?></td>
                                    <td><span class="badge bg-<?php echo $badge; ?>"><?php echo $est; ?></span></td>
                                    <td class="adq-col-acciones">
                                        <div class="adq-acciones-row">
                                            <button type="button" class="btn btn-sm btn-outline-dark adq-btn-icon-only" title="Detalle" onclick="abrirResolucion(<?php echo $h['Sol_Cod']; ?>, false)"><i class="bi bi-eye"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php }
                            } ?>
                        </tbody>
                    </table>
                </div>
                <div class="adq-table-pager"></div>
                </div>
            </div>

            <!-- 5. CREAR SOLICITUD -->
            <div class="tab-pane" id="create-panel" role="tabpanel">
                <div id="create-panel-content">
                    <div class="text-center p-5 text-muted">
                        <i class="glyphicon glyphicon-refresh glyphicon-spin" style="font-size:24px;"></i>
                        <div>Cargando formulario de registro...</div>
                    </div>
                </div>
            </div>
        </div>
            </div>

    <!-- RESOLUCION EMBEBIDA (ocupa el area de la tabla) -->
    <div id="mdlResolution" class="adq-resolution-embed" style="display:none;">
            <div class="adq-resolution-content">
                <div class="modal-header adq-resolution-header">
                    <button type="button" class="close" onclick="cerrarResolucion()" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    <div class="adq-resolution-header-text">
                        <h4 class="modal-title" id="lblModalTitle">Detalle de Solicitud</h4>
                        <p class="adq-modal-subtitle" id="lblModalSubtitle"></p>
                    </div>
                </div>
                <div class="modal-body adq-resolution-body">
                    <div class="row adq-resolution-layout">
                        <!-- COLUMNA IZQUIERDA: Datos, ?tems, Cotizaciones, Acciones -->
                        <div class="col-md-7 col-sm-12">
                            <!-- Datos Generales -->
                            <div class="adq-detail-card">
                                <h5 class="adq-section-header" style="color: #1e3a8a; border-bottom-color: #cbd5e1;"><i class="bi bi-file-earmark-text"></i> Datos del Requerimiento</h5>
                                <table class="table table-condensed table-borderless mb-0 adq-detail-kv">
                                    <tr>
                                        <td class="adq-detail-kv-label">Solicitante:</td>
                                        <td id="detSolicitante" class="fw-bold text-dark"></td>
                                        <td class="adq-detail-kv-label">Depto:</td>
                                        <td id="detDepartamento"></td>
                                    </tr>
                                    <tr>
                                        <td class="adq-detail-kv-label">Tipo Pedido:</td>
                                        <td id="detTipo" class="fw-semibold text-primary"></td>
                                        <td class="adq-detail-kv-label">Valor Est:</td>
                                        <td id="detTotal" class="fw-bold font-monospace text-success"></td>
                                    </tr>
                                    <tr>
                                        <td class="adq-detail-kv-label">Justificaci?n:</td>
                                        <td colspan="3" id="detJustificacion" class="text-muted" style="line-height: 1.45; font-style: italic;"></td>
                                    </tr>
                                    <tr>
                                        <td class="adq-detail-kv-label">Requisitos:</td>
                                        <td colspan="3" id="detRequisitos" class="text-muted" style="line-height: 1.45;"></td>
                                    </tr>
                                </table>
                            </div>

                            <!-- ?tems Solicitados (oculto) -->
                            <div class="adq-detail-card" id="divDetItems" style="display: none;">
                                <h5 class="adq-section-header"><i class="bi bi-cart3"></i> ?tems Requeridos</h5>
                                <div class="table-responsive adq-scroll-items">
                                    <table class="table table-striped table-hover align-middle mb-0" id="tblDetItems">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width: 36px;">#</th>
                                                <th>Descripci?n / Art?culo</th>
                                                <th class="text-center" style="width: 56px;">IVA</th>
                                                <th class="text-center" style="width: 72px;">Cant.</th>
                                                <th class="text-end" style="width: 100px;">V. Unit.</th>
                                                <th class="text-end" style="width: 100px;">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Cotizaciones de Sustento -->
                            <div class="adq-detail-card" id="divDetCotizaciones">
                                <h5 class="adq-section-header"><i class="bi bi-file-earmark-pdf"></i> Cotizaciones de Sustento</h5>
                                <div class="table-responsive adq-scroll-cotizaciones">
                                    <table class="table table-condensed table-hover adq-cot-sustento-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Proveedor</th>
                                                <th class="text-end" style="width: 110px;">Valor</th>
                                                <th class="text-center" style="width: 100px;">PDF</th>
                                                <th>Justificacion</th>
                                            </tr>
                                        </thead>
                                        <tbody id="detCotizacionesList"></tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Otros adjuntos PDF -->
                            <div class="adq-detail-card" id="divDetAdjuntos" style="display: none;">
                                <h5 class="adq-section-header"><i class="bi bi-paperclip"></i> Otros archivos PDF de soporte</h5>
                                <div class="table-responsive">
                                    <table class="table table-condensed table-hover mb-0" style="font-size: 12px;">
                                        <thead>
                                            <tr>
                                                <th>Descripci&oacute;n</th>
                                                <th class="text-center" style="width: 120px;">Archivo</th>
                                            </tr>
                                        </thead>
                                        <tbody id="detAdjuntosList"></tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Compras Vinculadas -->
                            <div class="adq-detail-card" id="divComprasVinculadas" style="display: none;">
                                <h5 class="adq-section-header" style="margin-bottom: 6px; padding-bottom: 4px;"><i class="bi bi-link-45deg"></i> Facturas de Compra Vinculadas</h5>
                                <div id="lstComprasVinculadas" style="max-height: 120px; overflow-y: auto;"></div>
                            </div>

                            <!-- Panel de Vinculaci?n de Compra (solo en nodo FACTURA) -->
                            <div class="adq-detail-card" id="panelVincularCompra" style="display: none; border-color: #0ea5e9; background-color: #f0f9ff; padding: 8px 12px;">
                                <h5 class="adq-section-header" style="color: #0369a1; border-bottom-color: #bae6fd; margin-bottom: 6px; padding-bottom: 4px;"><i class="bi bi-receipt"></i> Vincular Factura de Compra EXA</h5>
                                <div style="margin-bottom: 8px;">
                                    <input type="text" id="txtBuscarCompra" class="form-control input-sm" placeholder="Buscar factura por N? o Proveedor..." oninput="buscarComprasVincular()" style="height: 26px; font-size: 11px; padding: 3px 8px;">
                                </div>
                                <div class="table-responsive" id="divResultCompras" style="display: none; border: 1px solid #bae6fd; border-radius: 4px; background-color: #ffffff; max-height: 120px; overflow-y: auto;">
                                    <table class="table table-condensed table-hover mb-0" style="font-size: 11px;">
                                        <thead style="background-color: #f0f9ff;">
                                            <tr>
                                                <th class="text-center">N? Factura</th>
                                                <th class="text-center">Fecha</th>
                                                <th>Proveedor</th>
                                                <th class="text-end">Subtotal</th>
                                                <th class="text-end">IVA</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-center" style="width: 60px;">Acci&oacute;n</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tblBuscarCompras"></tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Panel: esperando correccion del solicitante -->
                            <div class="adq-detail-card" id="panelEsperaCorreccion" style="display: none; border-color: #f59e0b; background-color: #fffbeb; padding: 10px 12px;">
                                <h5 class="adq-section-header" style="color: #b45309; border-bottom-color: #fde68a; margin-bottom: 8px; padding-bottom: 4px;"><i class="bi bi-hourglass-split"></i> <span id="lblEsperaCorreccionTitulo">Esperando correccion del solicitante</span></h5>
                                <p class="mb-2" id="lblEsperaCorreccionDetalle" style="font-size: 12px; color: #78350f;">La solicitud fue observada y debe corregirse antes de volver a su bandeja de aprobacion.</p>
                                <button type="button" class="btn btn-warning text-dark btn-sm" id="btnIrCorregirObservada" style="display: none;" onclick="irCorregirDesdeModal()"><i class="bi bi-pencil"></i> Ir a corregir solicitud</button>
                            </div>

                            <!-- Panel: cargar cotizaciones/proformas o seleccionar ganadora en etapa -->
                            <div class="adq-detail-card" id="panelCotizacionesEtapa" style="display: none; border-color: #0ea5e9; background-color: #f0f9ff; padding: 10px 12px;">
                                <h5 class="adq-section-header" style="color: #0369a1; border-bottom-color: #bae6fd; margin-bottom: 8px; padding-bottom: 4px;"><i class="bi bi-file-earmark-pdf" id="icoCotizacionesEtapa"></i> <span id="lblCotizacionesEtapaTitulo">Cotizaciones / Proformas</span></h5>
                                <p class="mb-2" id="lblCotizacionesEtapaAyuda" style="font-size: 12px; color: #0c4a6e;">La etapa <strong id="lblCotizacionesEtapaNodo"></strong> permite cargar o actualizar las cotizaciones de esta solicitud.</p>
                                <button type="button" class="btn btn-primary btn-sm" id="btnCargarCotizaciones" onclick="abrirEdicionCotizaciones(currentSolCod)"><i class="bi bi-file-earmark-pdf"></i> <span id="lblBtnCargarCotizaciones">Cargar cotizaciones</span></button>
                            </div>

                            <!-- Panel: documentos de avance / fiscalizacion -->
                            <div class="adq-detail-card" id="panelAvanceEtapa" style="display: none; border-color: #0dcaf0; background-color: #f0fcff; padding: 10px 12px;">
                                <h5 class="adq-section-header" style="color: #087990; border-bottom-color: #9eeaf9; margin-bottom: 8px; padding-bottom: 4px;"><i class="bi bi-receipt-cutoff" id="icoAvanceEtapa"></i> <span id="lblAvanceEtapaTitulo">Facturas de Avance</span></h5>
                                <p class="mb-2" id="lblAvanceEtapaAyuda" style="font-size: 12px; color: #055160;">Etapa <strong id="lblAvanceEtapaNodo"></strong>: seleccione facturas de compra o anticipos de proveedores del sistema EXA. Use <strong>Guardar</strong> para registrar. Cuando termine, pulse <strong>Finalizar proceso</strong>.</p>
                                <div id="avanceTotalesResumen" class="mb-2" style="display:none;font-size:12px;padding:8px 10px;border-radius:6px;background:#e0f2fe;border:1px solid #7dd3fc;color:#0c4a6e;">
                                    <span class="me-3"><strong>Proforma/solicitud:</strong> $ <span id="avanceTotRef">0.00</span></span>
                                    <span class="me-3"><strong>Facturas:</strong> $ <span id="avanceTotSum">0.00</span></span>
                                    <span><strong>Diferencia:</strong> $ <span id="avanceTotDif">0.00</span></span>
                                </div>
                                <div class="row" style="margin-left:0;margin-right:0;margin-bottom:8px;">
                                    <div class="col-sm-6" style="padding-left:0;padding-right:4px;">
                                        <label class="small fw-semibold" style="color:#087990;margin-bottom:2px;display:block;"><i class="bi bi-receipt"></i> Facturas de compra</label>
                                        <input type="text" id="txtBuscarCompraAvance" class="form-control input-sm" placeholder="Buscar factura por N&deg; o Proveedor..." oninput="buscarComprasAvance()" style="height: 26px; font-size: 11px; padding: 3px 8px;">
                                    </div>
                                    <div class="col-sm-6" style="padding-left:4px;padding-right:0;">
                                        <label class="small fw-semibold" style="color:#087990;margin-bottom:2px;display:block;"><i class="bi bi-cash-coin"></i> Anticipos de proveedores</label>
                                        <input type="text" id="txtBuscarAnticipoAvance" class="form-control input-sm" placeholder="Buscar anticipo por #, proveedor o cedula..." oninput="buscarAnticiposAvance()" style="height: 26px; font-size: 11px; padding: 3px 8px;">
                                    </div>
                                </div>
                                <div class="table-responsive mb-2" id="divResultComprasAvance" style="display: none; border: 1px solid #9eeaf9; border-radius: 4px; background-color: #ffffff; max-height: 120px; overflow-y: auto;">
                                    <table class="table table-condensed table-hover mb-0" style="font-size: 11px;">
                                        <thead style="background-color: #e7f9fc;">
                                            <tr>
                                                <th class="text-center">N&deg; Factura</th>
                                                <th class="text-center">Fecha</th>
                                                <th>Proveedor</th>
                                                <th class="text-end">Subtotal</th>
                                                <th class="text-end">IVA</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-center" style="width: 60px;">Acci&oacute;n</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tblBuscarComprasAvance"></tbody>
                                    </table>
                                </div>
                                <div class="table-responsive mb-2" id="divResultAnticiposAvance" style="display: none; border: 1px solid #9eeaf9; border-radius: 4px; background-color: #ffffff; max-height: 120px; overflow-y: auto;">
                                    <table class="table table-condensed table-hover mb-0" style="font-size: 11px;">
                                        <thead style="background-color: #e7f9fc;">
                                            <tr>
                                                <th class="text-center"># Anticipo</th>
                                                <th class="text-center">Fecha</th>
                                                <th>Proveedor</th>
                                                <th class="text-end">Valor</th>
                                                <th class="text-end">Saldo</th>
                                                <th class="text-center">Estado</th>
                                                <th class="text-center" style="width: 60px;">Acci&oacute;n</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tblBuscarAnticiposAvance"></tbody>
                                    </table>
                                </div>
                                <form id="frmAvanceDocs" enctype="multipart/form-data">
                                    <input type="hidden" name="Sol_Cod" id="avanceSolCod" value="">
                                    <div id="avanceDocsEliminar"></div>
                                    <div id="lstAvanceDocsExistentes" class="mb-2"></div>
                                    <div id="lstAvanceDocsNuevos" class="mb-2"></div>
                                    <div id="secFiscalArchivos" class="mb-2" style="display: none; border-top: 1px dashed #9eeaf9; padding-top: 8px;">
                                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-bottom:6px;">
                                            <div>
                                                <label class="form-label fw-semibold" style="font-size: 12px; color: #087990; display: block; margin-bottom: 2px;"><i class="bi bi-file-earmark-pdf"></i> Documentos de fiscalizaci&oacute;n</label>
                                                <span class="text-muted small">Agregue uno o varios PDF; cada archivo debe tener un t&iacute;tulo.</span>
                                            </div>
                                            <button type="button" class="btn btn-primary btn-xs" onclick="agregarFiscalDocumento()"><i class="bi bi-plus-lg"></i> Agregar PDF</button>
                                        </div>
                                        <div id="lstFiscalDocsNuevos"></div>
                                    </div>
                                </form>
                            </div>

                            <!-- Panel expediente PDF (nodo FIN) -->
                            <div class="adq-detail-card adq-exp-panel" id="panelExpedienteFin" style="display: none;">
                                <div class="adq-exp-head">
                                    <span class="adq-exp-title"><i class="bi bi-file-earmark-lock2"></i> Expediente PDF</span>
                                    <span id="expEstadoBadges" class="adq-exp-status"></span>
                                    <span id="expAccionMsg" class="adq-exp-msg"></span>
                                    <div class="adq-exp-head-download">
                                        <button type="button" class="btn btn-primary btn-xs adq-exp-btn" id="btnDescargarExpUnido" onclick="descargarExpedientePdf('unido')" title="Unir PDFs de etapas anteriores">
                                            <i class="bi bi-download"></i> Descargar expediente
                                        </button>
                                        <button type="button" class="btn btn-outline-success btn-xs adq-exp-btn" id="btnDescargarExpFirmado" style="display: none;" onclick="descargarExpedienteFirmado()">
                                            <i class="bi bi-patch-check"></i> Descargar firmado
                                        </button>
                                    </div>
                                </div>
                                <div class="adq-exp-steps">
                                    <div class="adq-exp-steps-row">
                                        <div class="adq-exp-step adq-exp-step-upload">
                                            <span class="adq-exp-step-num">1</span>
                                            <span class="adq-exp-step-label">Descargar y revisar</span>
                                        </div>
                                        <span class="adq-exp-sep"></span>
                                        <div class="adq-exp-step adq-exp-step-upload">
                                            <span class="adq-exp-step-num">2</span>
                                            <input type="file" name="expediente_pdf" id="expPdfUpload" class="form-control adq-exp-file" accept=".pdf" title="Seleccionar PDF revisado">
                                            <button type="button" class="btn btn-default btn-xs adq-exp-btn" id="btnSubirExpediente" onclick="subirExpedienteFin()">
                                                <i class="bi bi-upload"></i> Cargar revisado
                                            </button>
                                        </div>
                                    </div>
                                    <div class="adq-exp-steps-row adq-exp-steps-firma" id="expFirmaBlock">
                                        <div class="adq-exp-step adq-exp-step-firma">
                                            <span class="adq-exp-step-num">3</span>
                                            <span class="adq-exp-step-label">Firma electronica</span>
                                            <input type="file" name="llave_p12" id="expLlaveP12" class="form-control adq-exp-file" accept=".p12" title="Elegir archivo de firma (.p12)">
                                            <label id="expUsarLlaveEmpresaWrap" class="adq-exp-check" style="display: none;">
                                                <input type="checkbox" id="expUsarLlaveEmpresa" onchange="toggleLlaveEmpresaExpediente()"> Llave empresa
                                            </label>
                                            <input type="password" class="form-control adq-exp-clave" id="expLlaveClave" placeholder="Clave" autocomplete="off">
                                            <button type="button" class="btn btn-success btn-xs adq-exp-btn" id="btnFirmarExpediente" onclick="firmarExpedienteFin()">
                                                <i class="bi bi-pen"></i> Firmar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div id="expFinAyuda" class="adq-exp-msg" style="display:none;margin-top:6px;"></div>
                            </div>

                            <!-- Panel de Decisi?n / Tarea -->
                            <div class="adq-detail-card" id="panelDecision" style="display: none; border-color: #3b82f6; background-color: #f0f7ff; padding: 8px 12px;">
                                <h5 class="adq-section-header" style="color: #1d4ed8; border-bottom-color: #bfdbfe; margin-bottom: 6px; padding-bottom: 4px;"><i class="bi bi-check2-all" id="icoPanelDecision"></i> <span id="lblPanelDecisionTitulo">Decisi&oacute;n en esta Etapa</span> (<span id="lblNodeActionName"></span>)</h5>
                                <form id="frmWorkflowAction" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="Ins_Cod" id="actionInsCod">
                                    <input type="hidden" name="Action" id="actionName">
                                    <div style="margin-bottom: 8px;">
                                        <label class="form-label fw-semibold" style="font-size: 11px; color: #1d4ed8; margin: 0 0 4px 0; display: block;">
                                            Comentario / justificaci&oacute;n <span id="lblComReq" class="text-danger" style="display: none;">*</span>
                                        </label>
                                        <textarea class="form-control" name="Comentario" id="actionComentario" rows="3" placeholder="Redacte el motivo de su decision..."></textarea>
                                    </div>
                                    <div style="margin-bottom: 10px;">
                                        <label class="form-label fw-semibold" style="font-size: 11px; color: #1d4ed8; margin: 0 0 4px 0; display: block;"><i class="bi bi-paperclip"></i> Sustentos adjuntos <span id="lblAdjReq" class="text-danger" style="display: none;">*</span></label>
                                        <input type="file" name="adjuntos[]" id="actionAdjunto" class="adq-file-native" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" multiple>
                                        <div class="adq-dropzone" id="adqDropzone" role="button" tabindex="0" onclick="document.getElementById('actionAdjunto').click();" onkeypress="if(event.key==='Enter'||event.key===' '){event.preventDefault();document.getElementById('actionAdjunto').click();}">
                                            <div class="adq-dropzone-empty" id="adqDropzoneEmpty">
                                                <i class="bi bi-cloud-arrow-up-fill adq-dropzone-icon"></i>
                                                <div class="adq-dropzone-text"><strong>Seleccionar uno o varios archivos</strong> o arrastrarlos aqu&iacute;</div>
                                                <div class="adq-dropzone-hint">PDF, imagen o documento (m&aacute;x. 10 MB por archivo)</div>
                                            </div>
                                            <div class="adq-dropzone-file" id="adqDropzoneFile" style="display: none;"></div>
                                        </div>
                                    </div>
                                    <div id="avanceGuardadoMsg" class="adq-avance-save-message" role="status" aria-live="polite" style="display: none;"></div>
                                    <div class="adq-action-buttons">
                                        <button type="button" class="btn btn-primary" id="btnGuardarAvance" style="display: none;" onclick="guardarAvanceDocs()"><i class="bi bi-save"></i> Guardar</button>
                                        <button type="button" class="btn btn-success" id="btnFinalizarAvance" style="display: none;" onclick="finalizarAvanceProceso()"><i class="bi bi-check-circle"></i> Finalizar proceso</button>
                                        <button type="button" class="btn btn-success" id="btnAccionAprobar" onclick="enviarAccion('APROBAR')"><i class="bi bi-check-circle"></i> Aprobar</button>
                                        <button type="button" class="btn btn-success" id="btnAccionCompletar" style="display: none;" onclick="enviarAccion('COMPLETAR')"><i class="bi bi-card-checklist"></i> Completar tarea</button>
                                        <button type="button" class="btn btn-warning text-dark" id="btnAccionObservar" style="display: none; background-color: #f59e0b; border-color: #f59e0b; color: #ffffff;" onclick="enviarAccion('OBSERVAR')"><i class="bi bi-exclamation-triangle"></i> Observar</button>
                                        <button type="button" class="btn btn-adq-devolver" id="btnAccionDevolver" onclick="enviarAccion('DEVOLVER')"><i class="bi bi-reply"></i> Devolver</button>
                                        <button type="button" class="btn btn-danger" id="btnAccionRechazar" onclick="enviarAccion('RECHAZAR')"><i class="bi bi-x-circle"></i> Rechazar</button>
                                    </div>
                                    <div id="lblAprobarBloqueo" class="text-danger" style="display:none; font-size: 11px; margin-top: 6px;"></div>
                                </form>
                            </div>
                        </div>

                        <!-- COLUMNA DERECHA: Historial -->
                        <div class="col-md-5 col-sm-12">
                            <div class="adq-detail-card">
                                <div class="adq-wf-progress-header">
                                    <h5 class="adq-section-header m-0" style="border: none; padding: 0; margin: 0;"><i class="bi bi-pen"></i> Historial de Firmas</h5>
                                    <button class="btn btn-xs btn-primary" type="button" onclick="abrirSeguimientoDetallado()"><i class="bi bi-clock-history"></i> Ver linea de tiempo</button>
                                </div>
                                <div class="adq-scroll-historial">
                                    <div class="adq-timeline" id="lstHistorial"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer adq-resolution-footer">
                    <button type="button" class="btn btn-default" onclick="cerrarResolucion()"><i class="bi bi-x-lg"></i> Cerrar</button>
                </div>
            </div>
    </div>
        </div>
    </div>

    <!-- MODAL SEGUIMIENTO DETALLADO (SLA) -->
    <div class="modal fade" id="mdlSeguimiento" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" style="width:95%;max-width:1200px;">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="lblSeguimientoTitle">Seguimiento de Requerimiento</h4>
                </div>
                <div class="modal-body" id="seguimientoModalBody" style="max-height:75vh;overflow-y:auto;"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm" onclick="volverAResolucion()"><i class="bi bi-arrow-left"></i> Volver al Detalle</button>
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DETALLE DE NODO (linea de tiempo) -->
    <div class="modal fade" id="mdlSegNodoDetalle" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="true">
        <div class="modal-dialog modal-lg" style="width:90%;max-width:900px;">
            <div class="modal-content adq-seg-nodo-modal">
                <div class="modal-header adq-seg-nodo-modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">
                        <i class="bi bi-list-task"></i> Tareas de la etapa:
                        <span id="segNodoTareasTitulo"></span>
                        <small class="text-muted" id="segNodoTareasSub" style="font-weight:normal;"></small>
                    </h4>
                </div>
                <div class="modal-body" style="max-height:65vh;overflow-y:auto;">
                    <div class="adq-timeline" id="segNodoTareasBody"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal" id="btnSegNodoTareasCerrar">
                        <i class="bi bi-x-lg"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="mdlAccionOkOverlay" class="adq-msg-overlay" role="alertdialog" aria-modal="true" aria-labelledby="mdlAccionOkTitle" aria-describedby="mdlAccionOkText">
        <div class="adq-msg-box is-success" id="mdlAccionOkBox">
            <div class="adq-msg-icon" id="mdlAccionOkIcon"><i class="bi bi-check-circle-fill"></i></div>
            <h3 class="adq-msg-title" id="mdlAccionOkTitle" style="display:none;"></h3>
            <p class="adq-msg-text" id="mdlAccionOkText">Accion Aprobada correctamente</p>
            <div class="adq-msg-actions" id="mdlAccionOkActions">
                <button type="button" class="btn btn-default" id="btnMdlAccionCancel" style="display:none;">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnMdlAccionOk">Aceptar</button>
            </div>
        </div>
    </div>

    <script src="../VALIDACIONES/adq_solicitud.js" charset="UTF-8"></script>
    <script>
let currentInsCod = null;
        let currentSolCod = null;
        let currentSolEst = null;
        let currentFlowVisual = null;
        let currentEsSolicitante = false;
        let isComObl = false;
        let isAdjObl = false;
        let isCotSelObl = false;
        let isCotEditObl = false;
        let hasCotGanadora = false;
        let hasCotMinimas = true;
        let solReqCot = 0;
        let solMinCot = 1;
        let searchTimer = null;

        const NODOS_RESOLUBLES = ['INICIO', 'APROBACION', 'RECEPCION', 'FACTURA', 'TAREA', 'AVANCE', 'FISCALIZACION', 'FIN'];
        let currentNodTip = null;
        let currentExpedienteEstado = null;
        let currentTieneLlaveEmpresa = false;
        let currentExpedientePdfsCount = 0;

        function ordenarNodosPorConexionesFlujo(flowVisual) {
            const nodos = (flowVisual && flowVisual.nodos) ? flowVisual.nodos.slice() : [];
            const conexiones = (flowVisual && flowVisual.conexiones) ? flowVisual.conexiones : [];
            if (nodos.length <= 1 || !conexiones.length) {
                return nodos;
            }

            const byId = {};
            const ordenOriginal = [];
            nodos.forEach(function(n) {
                const id = parseInt(n.id, 10) || 0;
                if (id <= 0) return;
                byId[id] = n;
                ordenOriginal.push(id);
            });

            const avance = { APROBAR: 1, COMPLETAR: 1, CREAR: 1, CONDICIONAL: 1, '': 1 };
            const adjFwd = {};
            const adjAll = {};
            const incomingFwd = {};

            conexiones.forEach(function(c) {
                const ori = parseInt(c.Nod_Ori, 10) || 0;
                const des = parseInt(c.Nod_Des, 10) || 0;
                if (ori <= 0 || des <= 0 || !byId[ori] || !byId[des] || ori === des) return;
                if (!adjAll[ori]) adjAll[ori] = [];
                adjAll[ori].push(des);
                const acc = (c.Con_Acc || '').toString().toUpperCase().trim();
                if (avance[acc] || acc === '') {
                    if (!adjFwd[ori]) adjFwd[ori] = [];
                    adjFwd[ori].push(des);
                    incomingFwd[des] = (incomingFwd[des] || 0) + 1;
                }
            });

            let starts = [];
            ordenOriginal.forEach(function(id) {
                if ((byId[id].tipo || '') === 'INICIO') starts.push(id);
            });
            if (!starts.length) {
                ordenOriginal.forEach(function(id) {
                    if (!incomingFwd[id]) starts.push(id);
                });
            }
            if (!starts.length) starts = [ordenOriginal[0]];

            const ordenados = [];
            const visitados = {};
            const cola = starts.slice();
            while (cola.length) {
                const actual = cola.shift();
                if (visitados[actual] || !byId[actual]) continue;
                visitados[actual] = true;
                ordenados.push(byId[actual]);
                const siguientes = (adjFwd[actual] && adjFwd[actual].length)
                    ? adjFwd[actual]
                    : (adjAll[actual] || []);
                const vistoSig = {};
                siguientes.forEach(function(sig) {
                    sig = parseInt(sig, 10) || 0;
                    if (sig <= 0 || vistoSig[sig] || visitados[sig]) return;
                    vistoSig[sig] = true;
                    cola.push(sig);
                });
            }

            ordenOriginal.forEach(function(id) {
                if (!visitados[id]) ordenados.push(byId[id]);
            });
            return ordenados;
        }

        function renderTrackerHtml(flowVisual, inline, bareInner) {
            const flowNodos = ordenarNodosPorConexionesFlujo(flowVisual || {});
            if (!flowNodos.length) {
                return '<span class="text-muted small">Sin workflow</span>';
            }
            const wrapperClass = inline ? 'tracker-wrapper adq-tracker-inline' : 'tracker-wrapper';
            let html = bareInner ? '' : `<div class="${wrapperClass}">`;
            flowNodos.forEach(function(node, index) {
                if (index > 0) {
                    html += '<div class="tracker-arrow"><i class="bi bi-arrow-right-short"></i></div>';
                }
                let actorLine = '';
                if (!inline) {
                    if (node.pendiente_meta) {
                        const pm = node.pendiente_meta;
                        let lines = [`<span><i class="bi bi-hourglass-split"></i> ${node.tipo === 'TAREA' ? 'Tarea pendiente' : 'Pendiente de aprobacion'}</span>`];
                        if (pm.depto) lines.push(`<span>Depto: ${pm.depto}</span>`);
                        if (pm.asignados) lines.push(`<span>Asignado: ${pm.asignados}</span>`);
                        if (pm.enviado_por) lines.push(`<span>Enviado por: ${pm.enviado_por}</span>`);
                        actorLine = `<br><span class="tracker-actor tracker-pendiente">${lines.join('')}</span>`;
                    } else if (node.actor_label) {
                        actorLine = `<br><span class="tracker-actor"><i class="bi bi-person-check"></i> ${node.actor_label}</span>`;
                    }
                }
                html += `
                    <div class="tracker-node color-${node.color}">
                        <i class="bi bi-circle-fill"></i> ${node.nombre}
                        <span class="tracker-node-tipo">[${node.tipo}]</span>
                        ${actorLine}
                    </div>
                `;
            });
            if (!bareInner) {
                html += '</div>';
            }
            return html;
        }

        function buildSeguimientoTrackerPreview() {
            const inner = renderTrackerHtml(currentFlowVisual || {}, false, true);
            if (!inner || inner.indexOf('tracker-node') === -1) {
                return '';
            }
            return ''
                + '<div class="adq-detail-card adq-seg-tracker-card" style="margin-bottom:12px;padding:12px 14px;">'
                + '<h5 class="adq-section-header" style="margin-bottom:8px;"><i class="bi bi-diagram-3"></i> Flujo del Workflow</h5>'
                + '<div class="tracker-wrapper adq-seg-flow-tracker">' + inner + '</div>'
                + '</div>';
        }

        function configurarPanelResolucion(nodTip, expedientePdfs, expedienteEstado, tieneLlaveEmpresa) {
            currentNodTip = nodTip || '';
            currentExpedienteEstado = expedienteEstado || null;
            currentTieneLlaveEmpresa = !!tieneLlaveEmpresa;
            currentExpedientePdfsCount = parseInt(expedientePdfs, 10) || 0;
            const esInicio = currentNodTip === 'INICIO';
            const esTarea = currentNodTip === 'TAREA';
            const esFin = currentNodTip === 'FIN';
            const esAvance = currentNodTip === 'AVANCE';
            const esFiscal = currentNodTip === 'FISCALIZACION';
            $('#btnGuardarAvance').toggle(esAvance || esFiscal);
            $('#lblPanelDecisionTitulo').text(esFin ? 'Cierre del expediente' : (esInicio ? 'Completar etapa Inicio' : (esTarea ? 'Tarea pendiente' : (esAvance ? 'Otras acciones' : (esFiscal ? 'Fiscalizaci\u00f3n' : 'Decisi\u00f3n en esta Etapa')))));
            $('#icoPanelDecision').attr('class', esFin ? 'bi bi-file-earmark-pdf' : (esInicio ? 'bi bi-play-circle' : (esTarea ? 'bi bi-card-checklist' : (esAvance ? 'bi bi-sliders' : (esFiscal ? 'bi bi-shield-check' : 'bi bi-check2-all')))));
            $('#actionComentario').attr('placeholder', esFin
                ? 'Comentario de cierre del expediente...'
                : (esInicio
                    ? 'Comentario de la etapa Inicio...'
                    : (esTarea
                    ? 'Describa el resultado de la tarea o el trabajo realizado...'
                    : (esAvance
                        ? 'Comentario obligatorio para finalizar el avance (Guardar solo registra facturas)...'
                        : (esFiscal
                            ? 'Comentario obligatorio de fiscalizaci\u00f3n (Guardar solo registra archivos)...'
                            : 'Redacte el motivo de su decisi\u00f3n...')))));
            // En AVANCE/FISCALIZACION el comentario es obligatorio para avanzar (no al Guardar).
            if (esAvance || esFiscal) {
                $('#lblComReq').show();
            }
            // Fiscalizacion mantiene Aprobar (a diferencia de Avance)
            $('#btnAccionAprobar').toggle(!esTarea && !esAvance).html(esFin
                ? '<i class="bi bi-check-circle"></i> Finalizar expediente'
                : (esInicio
                    ? '<i class="bi bi-check-circle"></i> Continuar proceso'
                    : '<i class="bi bi-check-circle"></i> Aprobar'));
            $('#btnAccionCompletar').toggle(esTarea);
            $('#btnAccionRechazar').toggle(!esTarea && !esInicio);
            $('#btnAccionObservar').hide(); // Oculto por ahora (usar Devolver)
            $('#btnAccionDevolver').toggle(!esTarea && !esInicio && !esFin);
            if (esFin) {
                renderPanelExpedienteFin();
            } else {
                $('#panelExpedienteFin').hide();
            }
            actualizarEstadoBotonesResolucion();
        }

        function resumenCotizacionesResolucion(cotizaciones) {
            const lista = Array.isArray(cotizaciones) ? cotizaciones : [];
            let validas = 0;
            let ganadora = false;
            lista.forEach(function(c) {
                const prv = parseInt(c.Prv_Cod, 10) || 0;
                const val = parseFloat(c.Cot_Val) || 0;
                const adj = (c.Cot_Adj == null) ? '' : String(c.Cot_Adj).trim();
                if (prv > 0 && val > 0 && adj !== '') {
                    validas++;
                }
                if (parseInt(c.Cot_Sel, 10) === 1) {
                    ganadora = true;
                }
            });
            return { validas: validas, ganadora: ganadora };
        }

        function motivosBloqueoAprobacion() {
            const motivos = [];
            if (isComObl && !$('#actionComentario').val().trim()) {
                motivos.push('comentario obligatorio');
            }
            if (isAdjObl && actionAdjuntosSeleccionados.length === 0) {
                motivos.push('adjunto de sustento obligatorio');
            }
            if (isCotEditObl && !hasCotMinimas) {
                motivos.push('cotizaciones minimas incompletas');
            }
            if (isCotSelObl && !hasCotGanadora) {
                motivos.push('cotizacion ganadora no seleccionada');
            }
            if (currentNodTip === 'FIN') {
                const tienePdf = currentExpedienteEstado && parseInt(currentExpedienteEstado.tiene_pdf, 10) === 1;
                if (!tienePdf) {
                    motivos.push('expediente PDF no cargado');
                }
            }
            if (currentNodTip === 'FISCALIZACION') {
                if (!$('#actionComentario').val().trim()) {
                    motivos.push('comentario obligatorio');
                }
                if (typeof contarDocsFiscalGuardados === 'function' && contarDocsFiscalGuardados() <= 0) {
                    motivos.push('documentos de fiscalizacion pendientes');
                }
            }
            return motivos;
        }

        function actualizarEstadoBotonesResolucion() {
            const motivos = motivosBloqueoAprobacion();
            const ok = motivos.length === 0;
            const title = ok ? '' : ('Complete antes de continuar: ' + motivos.join(', ') + '.');
            const $aprobar = $('#btnAccionAprobar');
            const $completar = $('#btnAccionCompletar');
            if ($aprobar.is(':visible')) {
                $aprobar.prop('disabled', !ok).attr('title', title);
            }
            if ($completar.is(':visible')) {
                $completar.prop('disabled', !ok).attr('title', title);
            }
            const $finAvance = $('#btnFinalizarAvance');
            if ($finAvance.is(':visible') && currentNodTip === 'AVANCE') {
                const motivosAvance = [];
                if (!$('#actionComentario').val().trim()) {
                    motivosAvance.push('comentario obligatorio');
                }
                if (typeof contarFacturasAvanceGuardadas === 'function' && contarFacturasAvanceGuardadas() <= 0) {
                    motivosAvance.push('factura/anticipo pendiente');
                }
                const okAvance = motivosAvance.length === 0;
                $finAvance.prop('disabled', !okAvance)
                    .attr('title', okAvance ? '' : ('Complete antes de finalizar: ' + motivosAvance.join(', ') + '.'));
            }
            const $lbl = $('#lblAprobarBloqueo');
            if (!ok && ($aprobar.is(':visible') || $completar.is(':visible'))) {
                $lbl.html('<i class="bi bi-lock-fill"></i> Para habilitar Aprobar/Completar: <strong>' + motivos.join(', ') + '</strong>.').show();
            } else {
                $lbl.hide().text('');
            }
            if (currentNodTip === 'FIN') {
                const tienePdf = currentExpedienteEstado && parseInt(currentExpedienteEstado.tiene_pdf, 10) === 1;
                if (tienePdf) {
                    $('#expFinAyuda').hide().text('');
                } else {
                    $('#expFinAyuda').show().text('Para finalizar: descargue el expediente y cargue el PDF revisado.');
                }
            }
        }

        function configurarTextosPanelAvance(nodTip) {
            const esFiscal = nodTip === 'FISCALIZACION';
            if (esFiscal) {
                $('#lblAvanceEtapaTitulo').text('Facturas, anticipos y archivos de fiscalizaci\u00f3n');
                $('#icoAvanceEtapa').attr('class', 'bi bi-shield-check');
                $('#lblAvanceEtapaAyuda').html('Etapa <strong id="lblAvanceEtapaNodo"></strong>: vincule facturas EXA, anticipos de proveedores y/o cargue PDF con t\u00edtulo. <strong>Guardar</strong> solo registra (no avanza de proceso). Para avanzar: documentos guardados + comentario, luego <strong>Aprobar</strong>.');
                $('#secFiscalArchivos').show();
                $('#panelAvanceEtapa').css({ 'border-color': '#6c757d', 'background-color': '#f8f9fa' });
            } else {
                $('#lblAvanceEtapaTitulo').text('Facturas y anticipos de Avance');
                $('#icoAvanceEtapa').attr('class', 'bi bi-receipt-cutoff');
                $('#lblAvanceEtapaAyuda').html('Etapa <strong id="lblAvanceEtapaNodo"></strong>: seleccione facturas de compra o anticipos de proveedores del sistema EXA. <strong>Guardar</strong> solo registra (no avanza de proceso). Para avanzar: registros guardados + comentario, luego <strong>Finalizar proceso</strong>.');
                $('#secFiscalArchivos').hide();
                $('#lstFiscalDocsNuevos').empty();
                $('#panelAvanceEtapa').css({ 'border-color': '#0dcaf0', 'background-color': '#f0fcff' });
            }
        }

        function actualizarBotonFinalizarExpediente() {
            if (currentNodTip !== 'FIN') {
                return;
            }
            actualizarEstadoBotonesResolucion();
        }

        function mostrarExpAccionMsg(texto) {
            const $msg = $('#expAccionMsg');
            if (texto) {
                $msg.text(texto).addClass('is-visible');
            } else {
                $msg.text('').removeClass('is-visible');
            }
        }

        function renderPanelExpedienteFin() {
            if (currentNodTip !== 'FIN') {
                $('#panelExpedienteFin').hide();
                return;
            }
            const exp = currentExpedienteEstado || {};
            const tienePdf = parseInt(exp.tiene_pdf, 10) === 1;
            const tieneFirmado = parseInt(exp.tiene_firmado, 10) === 1;
            let badges = '';
            if (tienePdf) {
                badges += '<span class="adq-exp-badge adq-exp-badge-ok">Cargado</span>';
            } else {
                badges += '<span class="adq-exp-badge adq-exp-badge-pend">Sin cargar</span>';
            }
            if (tieneFirmado) {
                badges += '<span class="adq-exp-badge adq-exp-badge-ok">Firmado</span>';
                if (exp.firm_nom) {
                    const nom = String(exp.firm_nom);
                    const corto = nom.length > 22 ? nom.substring(0, 20) + '…' : nom;
                    badges += '<span class="adq-exp-badge adq-exp-badge-ok" title="' + $('<div>').text(nom).html() + '">' + $('<div>').text(corto).html() + '</span>';
                }
            } else if (tienePdf) {
                badges += '<span class="adq-exp-badge adq-exp-badge-pend">Pendiente firma</span>';
            }
            $('#expEstadoBadges').html(badges);
            $('#btnDescargarExpUnido').prop('disabled', currentExpedientePdfsCount <= 0);
            $('#btnDescargarExpFirmado').toggle(tieneFirmado);
            $('#expFirmaBlock').toggleClass('is-disabled', !tienePdf);
            $('#btnFirmarExpediente').prop('disabled', !tienePdf);
            $('#expUsarLlaveEmpresaWrap').toggle(currentTieneLlaveEmpresa);
            if (currentTieneLlaveEmpresa) {
                $('#expUsarLlaveEmpresa').prop('checked', true);
                $('#expLlaveP12').prop('disabled', true);
            } else {
                $('#expUsarLlaveEmpresa').prop('checked', false);
                $('#expLlaveP12').prop('disabled', false);
            }
            mostrarExpAccionMsg('');
            $('#panelExpedienteFin').show();
            actualizarBotonFinalizarExpediente();
        }

        function toggleLlaveEmpresaExpediente() {
            const usarEmp = $('#expUsarLlaveEmpresa').is(':checked');
            $('#expLlaveP12').prop('disabled', usarEmp);
            if (usarEmp) {
                $('#expLlaveP12').val('');
            }
        }

        function ejecutarSubirExpedienteFin(file) {
            const fd = new FormData();
            fd.append('ajax_subir_expediente', '1');
            fd.append('Sol_Cod', currentSolCod);
            fd.append('expediente_pdf', file);
            const $btn = $('#btnSubirExpediente').prop('disabled', true);
            mostrarExpAccionMsg('');
            $.ajax({
                url: 'adq_bandeja.php',
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function(res) {
                $btn.prop('disabled', false);
                if (res.success) {
                    currentExpedienteEstado = res.expediente || null;
                    $('#expPdfUpload').val('');
                    renderPanelExpedienteFin();
                    mostrarExpAccionMsg('Cargado correctamente.');
                    if (currentSolCod) {
                        $.getJSON('adq_bandeja.php', { ajax_get_solicitud_detail: true, sol_cod: currentSolCod }, function(det) {
                            if (det && det.success) {
                                if (det.historial) {
                                    renderHistorialPanel(det.historial);
                                }
                                if (det.expediente) {
                                    currentExpedienteEstado = det.expediente;
                                    renderPanelExpedienteFin();
                                }
                            }
                        });
                    }
                } else {
                    alert('Error: ' + (res.message || 'No se pudo cargar el expediente.'));
                }
            }).fail(function() {
                $btn.prop('disabled', false);
                alert('Error de red al cargar el expediente.');
            });
        }

        function subirExpedienteFin() {
            if (!currentSolCod) {
                return;
            }
            const archivo = document.getElementById('expPdfUpload');
            if (!archivo || !archivo.files || archivo.files.length === 0) {
                alert('Seleccione el archivo PDF del expediente para subir.');
                return;
            }
            const file = archivo.files[0];
            if (!/\.pdf$/i.test(file.name)) {
                alert('El expediente debe ser un archivo PDF.');
                return;
            }
            if (currentExpedienteEstado && parseInt(currentExpedienteEstado.tiene_firmado, 10) === 1) {
                confirmarCentrado('Si vuelve a cargar el expediente, debera firmarlo nuevamente. Continuar?', function() {
                    ejecutarSubirExpedienteFin(file);
                });
                return;
            }
            ejecutarSubirExpedienteFin(file);
        }

        function firmarExpedienteFin() {
            if (!currentSolCod) {
                return;
            }
            if (!currentExpedienteEstado || parseInt(currentExpedienteEstado.tiene_pdf, 10) !== 1) {
                alert('Primero debe descargar el expediente y volver a cargarlo.');
                return;
            }
            const clave = ($('#expLlaveClave').val() || '').trim();
            const usarEmpresa = $('#expUsarLlaveEmpresa').is(':checked') ? 1 : 0;
            const archivo = document.getElementById('expLlaveP12');
            const tieneArchivo = archivo && archivo.files && archivo.files.length > 0;
            if (!usarEmpresa && !tieneArchivo) {
                alert('Debe cargar la llave electronica (.p12) o marcar la opcion de usar la llave de la empresa.');
                return;
            }
            if (!clave) {
                alert('Ingrese la clave de la llave electronica.');
                $('#expLlaveClave').focus();
                return;
            }
            const fd = new FormData();
            fd.append('ajax_firmar_expediente', '1');
            fd.append('Sol_Cod', currentSolCod);
            fd.append('Lla_Cla', clave);
            fd.append('usar_llave_empresa', usarEmpresa);
            if (tieneArchivo) {
                fd.append('llave_p12', archivo.files[0]);
            }
            const $btn = $('#btnFirmarExpediente').prop('disabled', true);
            mostrarExpAccionMsg('');
            $.ajax({
                url: 'adq_bandeja.php',
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function(res) {
                $btn.prop('disabled', false);
                if (res.success) {
                    currentExpedienteEstado = res.expediente || null;
                    renderPanelExpedienteFin();
                    $('#expLlaveClave').val('');
                    $('#expLlaveP12').val('');
                    mostrarExpAccionMsg('Firmado correctamente.');
                    // Actualizar Historial de Firmas con el expediente firmado en etapa FIN
                    if (currentSolCod) {
                        $.getJSON('adq_bandeja.php', { ajax_get_solicitud_detail: true, sol_cod: currentSolCod }, function(det) {
                            if (det && det.success) {
                                if (det.historial) {
                                    renderHistorialPanel(det.historial);
                                }
                                if (det.expediente) {
                                    currentExpedienteEstado = det.expediente;
                                    renderPanelExpedienteFin();
                                }
                            }
                        });
                    }
                } else {
                    alert('Error: ' + (res.message || 'No se pudo firmar el expediente.'));
                }
            }).fail(function() {
                $btn.prop('disabled', false);
                alert('Error de red al firmar el expediente.');
            });
        }

        function descargarExpedientePdf(tipo) {
            if (!currentSolCod) {
                return;
            }
            const t = tipo || 'unido';
            if (t !== 'unido' && t !== 'cargado') {
                return;
            }
            window.open(
                'adq_bandeja.php?ajax_descargar_expediente=1&sol_cod=' + encodeURIComponent(currentSolCod) + '&tipo=' + encodeURIComponent(t),
                '_blank'
            );
        }

        function descargarExpedienteFirmado() {
            if (!currentSolCod) {
                return;
            }
            if (!currentExpedienteEstado || parseInt(currentExpedienteEstado.tiene_firmado, 10) !== 1) {
                alert('Aun no hay expediente firmado para descargar.');
                return;
            }
            window.open(
                'adq_bandeja.php?ajax_descargar_expediente=1&sol_cod=' + encodeURIComponent(currentSolCod) + '&tipo=firmado',
                '_blank'
            );
        }

        function descargarDocumentosZip(solCod) {
            const cod = solCod || currentSolCod;
            if (!cod) {
                alert('No se identifico la solicitud.');
                return;
            }
            const $btn = $('#btnDescargarDocsZip');
            const original = $btn.length ? $btn.html() : '';
            if ($btn.length) {
                $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Generando ZIP...');
            }
            const url = 'adq_bandeja.php?ajax_descargar_docs_zip=1&sol_cod=' + encodeURIComponent(cod);
            fetch(url, { credentials: 'same-origin' }).then(function(resp) {
                const ctype = (resp.headers.get('Content-Type') || '').toLowerCase();
                if (ctype.indexOf('application/json') !== -1) {
                    return resp.json().then(function(res) {
                        throw new Error((res && res.message) ? res.message : 'No se pudo generar el ZIP.');
                    });
                }
                if (!resp.ok) {
                    throw new Error('Error al generar el ZIP.');
                }
                const disp = resp.headers.get('Content-Disposition') || '';
                let filename = 'documentos_solicitud_' + cod + '.zip';
                const m = /filename="?([^"]+)"?/i.exec(disp);
                if (m && m[1]) {
                    filename = m[1];
                }
                return resp.blob().then(function(blob) {
                    const a = document.createElement('a');
                    const objUrl = window.URL.createObjectURL(blob);
                    a.href = objUrl;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(objUrl);
                });
            }).catch(function(err) {
                alert(err && err.message ? err.message : 'No se pudo descargar el ZIP.');
            }).then(function() {
                if ($btn.length) {
                    $btn.prop('disabled', false).html(original);
                }
            });
        }

        function mostrarPanelEsperaCorreccion(titulo, detalle, mostrarBtnCorregir) {
            $('#lblEsperaCorreccionTitulo').text(titulo);
            $('#lblEsperaCorreccionDetalle').text(detalle);
            $('#btnIrCorregirObservada').toggle(!!mostrarBtnCorregir);
            $('#panelEsperaCorreccion').show();
        }

        function irCorregirDesdeModal() {
            if (!currentSolCod) {
                return;
            }
            cerrarResolucion();
            abrirEdicionBorrador(currentSolCod);
        }

        let avanceDocNuevoIdx = 0;
        let fiscalDocNuevoIdx = 0;
        let avanceSearchTimer = null;
        let anticipoSearchTimer = null;
        const avanceCopCodSeleccionados = new Set();
        const avanceAtpCodSeleccionados = new Set();
        let avanceValorReferencia = 0;

        function roundMoney(v) {
            return Math.round((parseFloat(v) || 0) * 100) / 100;
        }

        function configurarValorReferenciaAvance(sol, cotizaciones) {
            let proforma = 0;
            (cotizaciones || []).forEach(function(c) {
                if (parseInt(c.Cot_Sel, 10) === 1) {
                    proforma = roundMoney(c.Cot_Val);
                }
            });
            const solVal = roundMoney(sol && sol.Sol_Val_Est !== undefined ? sol.Sol_Val_Est : 0);
            avanceValorReferencia = proforma > 0 ? proforma : solVal;
            actualizarResumenTotalesAvance();
        }

        function obtenerTotalFacturasAvanceSeleccionadas() {
            let sum = 0;
            $('#lstAvanceDocsExistentes .adq-avance-factura-card').each(function() {
                if ($(this).attr('data-eliminado') === '1' || $(this).hasClass('adq-avance-eliminado')) {
                    return;
                }
                sum += roundMoney($(this).attr('data-factura-total'));
            });
            $('#lstAvanceDocsNuevos .adq-avance-factura-card').each(function() {
                sum += roundMoney($(this).attr('data-factura-total'));
            });
            return roundMoney(sum);
        }

        function actualizarResumenTotalesAvance() {
            const ref = roundMoney(avanceValorReferencia);
            const sum = obtenerTotalFacturasAvanceSeleccionadas();
            const dif = roundMoney(sum - ref);
            $('#avanceTotRef').text(ref.toFixed(2));
            $('#avanceTotSum').text(sum.toFixed(2));
            $('#avanceTotDif').text(dif.toFixed(2));
            const $box = $('#avanceTotalesResumen');
            if (ref > 0 || sum > 0) {
                $box.show();
                if (Math.abs(dif) <= 0.01) {
                    $box.css({ background: '#dcfce7', borderColor: '#86efac', color: '#166534' });
                } else if (dif > 0.01) {
                    $box.css({ background: '#fee2e2', borderColor: '#fca5a5', color: '#991b1b' });
                } else {
                    $box.css({ background: '#e0f2fe', borderColor: '#7dd3fc', color: '#0c4a6e' });
                }
            } else {
                $box.hide();
            }
        }

        function validarAgregarFacturaAvance(totalFactura) {
            const ref = roundMoney(avanceValorReferencia);
            const tot = roundMoney(totalFactura);
            if (ref <= 0) {
                return { ok: false, message: 'La solicitud no tiene un valor de proforma/solicitud de referencia.' };
            }
            if (tot > ref + 0.01) {
                return { ok: false, message: 'El valor de la factura es mayor al valor de la solicitud.' };
            }
            const suma = obtenerTotalFacturasAvanceSeleccionadas();
            if (roundMoney(suma + tot) > ref + 0.01) {
                return {
                    ok: false,
                    message: 'El valor de la factura es mayor al valor de la solicitud. Suma actual $ '
                        + suma.toFixed(2) + ' + factura $ ' + tot.toFixed(2)
                        + ' supera $ ' + ref.toFixed(2) + '.'
                };
            }
            return { ok: true };
        }

        function validarTotalesAvanceParaFinalizar() {
            const ref = roundMoney(avanceValorReferencia);
            const suma = obtenerTotalFacturasAvanceSeleccionadas();
            if (ref <= 0) {
                return { ok: false, message: 'No se puede finalizar: falta el valor de la proforma/solicitud.' };
            }
            if (suma <= 0) {
                return { ok: false, message: 'Debe registrar al menos una factura antes de finalizar el proceso.' };
            }
            const dif = roundMoney(suma - ref);
            if (dif > 0.01) {
                return { ok: false, message: 'El valor de la factura es mayor al valor de la solicitud.' };
            }
            if (Math.abs(dif) > 0.01) {
                return {
                    ok: false,
                    message: 'La suma de las facturas debe ser igual al valor de la proforma. Proforma: $ '
                        + ref.toFixed(2) + '. Facturas: $ ' + suma.toFixed(2) + '.'
                };
            }
            return { ok: true };
        }

        function avanceFileLink(path, label) {
            if (!path) {
                return `<span class="text-muted small d-inline-block me-2">${label}: sin archivo</span>`;
            }
            return `<a href="${adqUrlDocumento(path)}" target="_blank" class="small d-inline-block me-2"><i class="bi bi-download"></i> ${label}</a>`;
        }

        function avanceEsc(text) {
            return $('<div>').text(text == null ? '' : String(text)).html();
        }

        function htmlFiscalDocumentoNuevo(idx) {
            return `
                <div class="border rounded p-2 mb-2 bg-white adq-fiscal-doc-nuevo" data-fiscal-nuevo="${idx}">
                    <div style="display:flex;align-items:flex-end;gap:8px;flex-wrap:wrap;">
                        <div style="flex:1 1 260px;">
                            <label class="small fw-semibold mb-1">T&iacute;tulo del documento *</label>
                            <input type="text" class="form-control input-sm" name="fiscal_docs[${idx}][titulo]" maxlength="250" required placeholder="Ej. Informe de fiscalizaci&oacute;n">
                        </div>
                        <div style="flex:1 1 280px;">
                            <label class="small fw-semibold mb-1">Archivo PDF *</label>
                            <div class="adq-fiscal-file-control">
                                <input type="file" class="adq-fiscal-file-native" id="fiscal_pdf_${idx}" name="fiscal_archivos[${idx}]" accept=".pdf,application/pdf" required onchange="actualizarFiscalArchivo(this)">
                                <label class="adq-fiscal-file-btn" for="fiscal_pdf_${idx}"><i class="bi bi-file-earmark-arrow-up"></i> Seleccionar PDF</label>
                                <span class="adq-fiscal-file-name">Ning&uacute;n archivo seleccionado</span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-danger btn-xs" onclick="quitarFiscalDocumentoNuevo(${idx})" title="Quitar PDF"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            `;
        }

        function agregarFiscalDocumento() {
            const idx = fiscalDocNuevoIdx++;
            $('#lstFiscalDocsNuevos').append(htmlFiscalDocumentoNuevo(idx));
            $('#lstFiscalDocsNuevos [data-fiscal-nuevo="' + idx + '"] input[type="text"]').focus();
        }

        function quitarFiscalDocumentoNuevo(idx) {
            $('#lstFiscalDocsNuevos [data-fiscal-nuevo="' + idx + '"]').remove();
        }

        function actualizarFiscalArchivo(input) {
            const $control = $(input).closest('.adq-fiscal-file-control');
            const file = input.files && input.files[0] ? input.files[0] : null;
            $control.toggleClass('has-file', !!file);
            $control.find('.adq-fiscal-file-name').text(file ? file.name : 'Ningun archivo seleccionado');
        }

        function htmlTarjetaFiscalExistente(doc) {
            const savCod = parseInt(doc.Sav_Cod, 10) || 0;
            const titulo = doc.Sav_Des || '';
            const path = doc.Sav_Fac_Adj || doc.Sav_Adj || doc.Sav_Ret_Adj || doc.Sav_Com_Adj || '';
            const usuario = doc.Usuario_Nom ? doc.Usuario_Nom.trim() : '';
            return `
                <div class="border rounded p-2 mb-2 bg-white adq-fiscal-doc-guardado" data-sav-cod="${savCod}" data-sav-cop-cod="0">
                    <div style="display:flex;align-items:flex-end;gap:8px;flex-wrap:wrap;">
                        <div style="flex:1 1 300px;">
                            <label class="small fw-semibold mb-1">T&iacute;tulo del documento</label>
                            <input type="text" class="form-control input-sm" name="avance_docs_existentes[${savCod}][Sav_Des]" value="${avanceEsc(titulo)}" maxlength="250">
                        </div>
                        <div style="flex:1 1 240px;padding-bottom:4px;">
                            ${avanceFileLink(path, titulo || 'Ver PDF')}
                        </div>
                        <button type="button" class="btn btn-danger btn-xs" onclick="marcarEliminarAvanceDoc(${savCod}, this)" title="Quitar PDF"><i class="bi bi-trash"></i></button>
                    </div>
                    ${usuario ? `<div class="text-muted small mt-1">Registrado por: ${avanceEsc(usuario)}</div>` : ''}
                </div>
            `;
        }

        function obtenerCopCodsAvanceSeleccionados() {
            const cods = new Set();
            $('#lstAvanceDocsExistentes [data-sav-cop-cod]').each(function() {
                const v = parseInt($(this).attr('data-sav-cop-cod'), 10);
                if (v > 0) { cods.add(v); }
            });
            $('#lstAvanceDocsNuevos input[name*="[Sav_Cop_Cod]"]').each(function() {
                const v = parseInt($(this).val(), 10);
                if (v > 0) { cods.add(v); }
            });
            return cods;
        }

        function obtenerAtpCodsAvanceSeleccionados() {
            const cods = new Set();
            $('#lstAvanceDocsExistentes [data-sav-atp-cod]').each(function() {
                const v = parseInt($(this).attr('data-sav-atp-cod'), 10);
                if (v > 0) { cods.add(v); }
            });
            $('#lstAvanceDocsNuevos input[name*="[Sav_Atp_Cod]"]').each(function() {
                const v = parseInt($(this).val(), 10);
                if (v > 0) { cods.add(v); }
            });
            return cods;
        }

        function renderAvanceAnticipoDetalle(anticipo, removeBtnHtml) {
            const quitBtn = removeBtnHtml
                ? `<div class="adq-avance-quitar-wrap">${removeBtnHtml}</div>`
                : '';
            if (!anticipo) {
                return `<div class="adq-avance-factura-header">
                    <div class="adq-avance-factura-titulo text-muted small">Sin datos de anticipo.</div>
                    ${quitBtn}
                </div>`;
            }
            const valor = parseFloat(anticipo.Atp_Val || 0).toFixed(2);
            const saldo = parseFloat(anticipo.Saldo || 0).toFixed(2);
            const linkCom = anticipo.Link_Comprobante
                ? `<a href="${avanceEsc(anticipo.Link_Comprobante)}" target="_blank" class="small"><i class="bi bi-journal-text"></i> ${avanceEsc(anticipo.Com_Codigo || 'Comprobante')}</a>`
                : '<span class="text-muted small">Sin comprobante contable</span>';
            let imgsHtml = '';
            if (anticipo.Comprobantes_Img && anticipo.Comprobantes_Img.length) {
                imgsHtml = anticipo.Comprobantes_Img.map(function(img, i) {
                    const label = anticipo.Comprobantes_Img.length > 1
                        ? ('Comprobante ' + (i + 1))
                        : 'Ver comprobante';
                    const forma = img.Pag_Abr || img.Pag_Des || '';
                    return `<a href="${avanceEsc(img.Pap_img)}" target="_blank" class="small d-inline-flex align-items-center me-2 mb-1" title="${avanceEsc(forma)} $ ${parseFloat(img.Pap_Val || 0).toFixed(2)}">
                        <img src="${avanceEsc(img.Pap_img)}" alt="${avanceEsc(label)}" style="width:42px;height:42px;object-fit:cover;border-radius:4px;border:1px solid #cbd5e1;margin-right:6px;">
                        <span><i class="bi bi-image"></i> ${avanceEsc(label)}</span>
                    </a>`;
                }).join('');
                imgsHtml = `<div class="mt-1"><span class="text-muted d-block mb-1">Comprobante(s) de pago:</span>${imgsHtml}</div>`;
            } else {
                imgsHtml = '<div class="text-muted small mt-1">Sin imagen de comprobante (Pap_img).</div>';
            }
            return `
                <div class="small">
                    <div class="adq-avance-factura-header">
                        <div class="adq-avance-factura-titulo">
                            <strong>Anticipo # ${avanceEsc(anticipo.Atp_Cod)}</strong> - ${avanceEsc(anticipo.Proveedor)}
                            <span class="text-muted">(${avanceEsc(anticipo.Atp_Fec)})</span>
                        </div>
                        ${quitBtn}
                    </div>
                    <div class="mb-1" style="display:flex;flex-wrap:wrap;align-items:center;gap:12px;">
                        <span><span class="text-muted">Valor:</span> <span class="font-monospace fw-semibold">$ ${valor}</span></span>
                        <span><span class="text-muted">Saldo:</span> <span class="font-monospace fw-bold text-success">$ ${saldo}</span></span>
                        <span><span class="text-muted">Estado:</span> <strong>${avanceEsc(anticipo.Estado || anticipo.Atp_Est || '')}</strong></span>
                        ${linkCom}
                    </div>
                    ${imgsHtml}
                    ${anticipo.Atp_Obs ? `<div class="text-muted small">Obs.: ${avanceEsc(anticipo.Atp_Obs)}</div>` : ''}
                </div>
            `;
        }

        function htmlTarjetaAvanceAnticipo(opts) {
            const savCod = opts.savCod || '';
            const idx = opts.idx;
            const atpCod = opts.atpCod || 0;
            const des = opts.des || '';
            const anticipo = opts.anticipo || null;
            const usuario = opts.usuario || '';
            const esNuevo = !!opts.esNuevo;
            const dataAttr = esNuevo
                ? `data-avance-nuevo="${idx}" data-sav-atp-cod="${atpCod}"`
                : `data-sav-cod="${savCod}" data-sav-atp-cod="${atpCod}" data-sav-cop-cod="0"`;
            const namePrefix = esNuevo ? `avance_docs_nuevos[${idx}]` : `avance_docs_existentes[${savCod}]`;
            const removeBtn = esNuevo
                ? `<button type="button" class="btn btn-danger btn-sm adq-btn-quitar-factura" onclick="quitarAnticipoAvanceNuevo(${idx}, ${atpCod})" title="Quitar anticipo"><i class="bi bi-trash"></i> Quitar anticipo</button>`
                : `<button type="button" class="btn btn-danger btn-sm adq-btn-quitar-factura" onclick="marcarEliminarAvanceDoc(${savCod}, this)" title="Quitar anticipo"><i class="bi bi-trash"></i> Quitar anticipo</button>`;
            const detalleHtml = anticipo
                ? `<div class="avance-anticipo-detalle">${renderAvanceAnticipoDetalle(anticipo, removeBtn)}</div>`
                : `<div class="adq-avance-factura-header">
                        <div class="adq-avance-factura-titulo text-muted small">Anticipo # ${atpCod} sin detalle.</div>
                        <div class="adq-avance-quitar-wrap">${removeBtn}</div>
                   </div>`;
            return `
                <div class="border rounded p-2 mb-2 bg-white adq-avance-anticipo-card" ${dataAttr} style="border-left:3px solid #0d9488 !important;">
                    <input type="hidden" name="${namePrefix}[Sav_Atp_Cod]" value="${atpCod}">
                    <input type="hidden" name="${namePrefix}[Sav_Cop_Cod]" value="0">
                    <input type="text" class="form-control form-control-sm mb-2" name="${namePrefix}[Sav_Des]" value="${avanceEsc(des)}" placeholder="Observacion opcional">
                    ${detalleHtml}
                    ${usuario ? `<div class="text-muted small mt-1">Registrado por: ${avanceEsc(usuario)}</div>` : ''}
                </div>
            `;
        }

        function renderAvanceCompraDetalle(compra, removeBtnHtml) {
            const quitBtn = removeBtnHtml
                ? `<div class="adq-avance-quitar-wrap">${removeBtnHtml}</div>`
                : '';
            if (!compra) {
                return `<div class="adq-avance-factura-header">
                    <div class="adq-avance-factura-titulo text-muted small">Sin datos de factura.</div>
                    ${quitBtn}
                </div>`;
            }
            const subtotal = parseFloat(compra.Subtotal || 0).toFixed(2);
            const iva = parseFloat(compra.Iva || 0).toFixed(2);
            const total = parseFloat(compra.Total || 0).toFixed(2);
            let comps = '';
            if (compra.Comprobantes && compra.Comprobantes.length) {
                comps = compra.Comprobantes.map(function(c) {
                    const imgHtml = (c.Pag_img && String(c.Pag_img).trim() !== '')
                        ? `<a href="${avanceEsc(c.Pag_img)}" target="_blank" class="d-inline-flex align-items-center ms-2" title="Ver imagen del pago">
                                <img src="${avanceEsc(c.Pag_img)}" alt="Comprobante pago" style="width:36px;height:36px;object-fit:cover;border-radius:4px;border:1px solid #cbd5e1;margin-right:4px;">
                                <span class="small"><i class="bi bi-image"></i></span>
                           </a>`
                        : '';
                    return `<div class="small mb-1" style="display:flex;flex-wrap:wrap;align-items:center;gap:6px;">
                        <a href="${avanceEsc(c.Link)}" target="_blank"><i class="bi bi-journal-text"></i> ${avanceEsc(c.Codigo)}</a>
                        <span class="text-muted">(${avanceEsc(c.Pag_Fec)})</span>
                        <span class="font-monospace">$ ${parseFloat(c.Pag_Val || 0).toFixed(2)}</span>
                        ${c.Forma ? `<span class="text-muted">${avanceEsc(c.Forma)}</span>` : ''}
                        ${imgHtml}
                    </div>`;
                }).join('');
            } else {
                comps = '<span class="text-muted small">Sin comprobantes de pago registrados.</span>';
            }
            return `
                <div class="small">
                    <div class="adq-avance-factura-header">
                        <div class="adq-avance-factura-titulo">
                            <strong>Factura # ${avanceEsc(compra.Cop_Num)}</strong> - ${avanceEsc(compra.Proveedor)}
                            <span class="text-muted">(${avanceEsc(compra.Cop_Fec)})</span>
                        </div>
                        ${quitBtn}
                    </div>
                    <div class="mb-1" style="display:flex;flex-wrap:wrap;align-items:center;gap:12px;">
                        <span><span class="text-muted">Subtotal:</span> <span class="font-monospace fw-semibold">$ ${subtotal}</span></span>
                        <span><span class="text-muted">IVA:</span> <span class="font-monospace fw-semibold">$ ${iva}</span></span>
                        <span><span class="text-muted">Total:</span> <span class="font-monospace fw-bold text-success">$ ${total}</span></span>
                        <span><span class="text-muted">Forma de pago:</span> <strong>${avanceEsc(compra.Forma_Pago || 'N/D')}</strong></span>
                        <a href="${avanceEsc(compra.Link_Factura)}" target="_blank" class="small"><i class="bi bi-file-earmark-pdf"></i> PDF factura</a>
                    </div>
                    <div><span class="text-muted d-block mb-1">Comprobantes de pago:</span>${comps}</div>
                </div>
            `;
        }

        function htmlTarjetaAvanceCompra(opts) {
            const savCod = opts.savCod || '';
            const idx = opts.idx;
            const copCod = opts.copCod || 0;
            const des = opts.des || '';
            const compra = opts.compra || null;
            const usuario = opts.usuario || '';
            const esNuevo = !!opts.esNuevo;
            const dataAttr = esNuevo
                ? `data-avance-nuevo="${idx}" data-factura-total="${roundMoney(compra && compra.Total !== undefined ? compra.Total : 0).toFixed(2)}"`
                : `data-sav-cod="${savCod}" data-sav-cop-cod="${copCod}" data-factura-total="${roundMoney(compra && compra.Total !== undefined ? compra.Total : 0).toFixed(2)}"`;
            const namePrefix = esNuevo ? `avance_docs_nuevos[${idx}]` : `avance_docs_existentes[${savCod}]`;
            const legacy = opts.legacy || null;
            let legacyHtml = '';
            if (legacy) {
                legacyHtml = `<div class="mt-2 pt-2 border-top">
                    <div class="text-muted small mb-1">Archivos cargados manualmente (registro anterior):</div>
                    ${avanceFileLink(legacy.fac, 'Factura')}
                    ${avanceFileLink(legacy.ret, 'Retencion')}
                    ${avanceFileLink(legacy.com, 'Comprobante')}
                </div>`;
            }
            const removeBtn = esNuevo
                ? `<button type="button" class="btn btn-danger btn-sm adq-btn-quitar-factura" onclick="quitarCompraAvanceNueva(${idx}, ${copCod})" title="Quitar factura"><i class="bi bi-trash"></i> Quitar factura</button>`
                : `<button type="button" class="btn btn-danger btn-sm adq-btn-quitar-factura" onclick="marcarEliminarAvanceDoc(${savCod}, this)" title="Quitar factura"><i class="bi bi-trash"></i> Quitar factura</button>`;
            const detalleHtml = compra
                ? `<div class="avance-compra-detalle">${renderAvanceCompraDetalle(compra, removeBtn)}</div>`
                : (legacy
                    ? `<div class="adq-avance-factura-header">
                            <div class="adq-avance-factura-titulo text-muted small">Registro anterior sin factura vinculada.</div>
                            <div class="adq-avance-quitar-wrap">${removeBtn}</div>
                       </div>`
                    : `<div class="adq-avance-factura-header">
                            <div class="adq-avance-factura-titulo text-muted small">Factura del sistema no vinculada.</div>
                            <div class="adq-avance-quitar-wrap">${removeBtn}</div>
                       </div>`);
            return `
                <div class="border rounded p-2 mb-2 bg-white adq-avance-factura-card" ${dataAttr}>
                    <input type="hidden" name="${namePrefix}[Sav_Cop_Cod]" value="${copCod}">
                    <input type="text" class="form-control form-control-sm mb-2" name="${namePrefix}[Sav_Des]" value="${avanceEsc(des)}" placeholder="Observacion opcional">
                    ${detalleHtml}
                    ${legacyHtml}
                    ${usuario ? `<div class="text-muted small mt-1">Registrado por: ${avanceEsc(usuario)}</div>` : ''}
                </div>
            `;
        }

        function renderAvanceDocs(avances) {
            const $exist = $('#lstAvanceDocsExistentes').empty();
            $('#lstAvanceDocsNuevos').empty();
            $('#lstFiscalDocsNuevos').empty();
            avanceDocNuevoIdx = 0;
            fiscalDocNuevoIdx = 0;
            avanceCopCodSeleccionados.clear();
            avanceAtpCodSeleccionados.clear();
            $('#avanceDocsEliminar').empty();
            if (!avances || avances.length === 0) {
                $exist.html(currentNodTip === 'FISCALIZACION'
                    ? '<div class="text-muted small mb-2">No hay facturas, anticipos ni documentos PDF registrados en esta fiscalizaci&oacute;n.</div>'
                    : '<div class="text-muted small mb-2">No hay facturas ni anticipos registrados en esta etapa.</div>');
            } else {
                avances.forEach(function(doc) {
                    const copCod = parseInt(doc.Sav_Cop_Cod, 10) || 0;
                    const atpCod = parseInt(doc.Sav_Atp_Cod, 10) || 0;
                    if (copCod > 0) { avanceCopCodSeleccionados.add(copCod); }
                    if (atpCod > 0) { avanceAtpCodSeleccionados.add(atpCod); }
                    if (atpCod > 0) {
                        $exist.append(htmlTarjetaAvanceAnticipo({
                            savCod: doc.Sav_Cod,
                            atpCod: atpCod,
                            des: doc.Sav_Des || '',
                            anticipo: doc.anticipo || null,
                            usuario: doc.Usuario_Nom ? doc.Usuario_Nom.trim() : ''
                        }));
                        return;
                    }
                    if (currentNodTip === 'FISCALIZACION' && copCod <= 0) {
                        $exist.append(htmlTarjetaFiscalExistente(doc));
                        return;
                    }
                    const legacy = (!doc.compra && (doc.Sav_Fac_Adj || doc.Sav_Adj || doc.Sav_Ret_Adj || doc.Sav_Com_Adj))
                        ? { fac: doc.Sav_Fac_Adj || doc.Sav_Adj || '', ret: doc.Sav_Ret_Adj || '', com: doc.Sav_Com_Adj || '' }
                        : null;
                    $exist.append(htmlTarjetaAvanceCompra({
                        savCod: doc.Sav_Cod,
                        copCod: copCod,
                        des: doc.Sav_Des || '',
                        compra: doc.compra || null,
                        usuario: doc.Usuario_Nom ? doc.Usuario_Nom.trim() : '',
                        legacy: legacy
                    }));
                });
            }
            $('#txtBuscarCompraAvance').val('');
            $('#txtBuscarAnticipoAvance').val('');
            $('#divResultComprasAvance').hide();
            $('#divResultAnticiposAvance').hide();
            actualizarResumenTotalesAvance();
        }

        function marcarEliminarAvanceDoc(savCod, btn) {
            savCod = parseInt(savCod, 10) || 0;
            const $row = $(btn).closest('[data-sav-cod]');
            if (!$row.length) {
                return;
            }
            const cop = parseInt($row.attr('data-sav-cop-cod'), 10);
            const atp = parseInt($row.attr('data-sav-atp-cod'), 10);
            if (cop > 0) { avanceCopCodSeleccionados.delete(cop); }
            if (atp > 0) { avanceAtpCodSeleccionados.delete(atp); }
            // Quitar visualmente al instante y registrar la baja para el siguiente Guardar/Finalizar.
            if (savCod > 0 && $('#avanceDocsEliminar input[data-sav-elim="' + savCod + '"]').length === 0) {
                $('#avanceDocsEliminar').append(
                    '<input type="hidden" name="sav_eliminar[]" data-sav-elim="' + savCod + '" value="' + savCod + '">'
                );
            }
            $row.remove();
            actualizarResumenTotalesAvance();
            if (typeof buscarComprasAvance === 'function') {
                const term = ($('#txtBuscarCompraAvance').val() || '').trim();
                if (term.length >= 2) {
                    buscarComprasAvance();
                }
            }
        }

        function tieneAvancePendienteGuardar() {
            return $('#lstAvanceDocsNuevos [data-avance-nuevo]').length > 0
                || $('#lstFiscalDocsNuevos .adq-fiscal-doc-nuevo').length > 0
                || $('#avanceDocsEliminar input[data-sav-elim]').length > 0;
        }

        function quitarCompraAvanceNueva(idx, copCod) {
            if (copCod > 0) { avanceCopCodSeleccionados.delete(copCod); }
            $(`[data-avance-nuevo="${idx}"]`).remove();
            actualizarResumenTotalesAvance();
            const term = ($('#txtBuscarCompraAvance').val() || '').trim();
            if (term.length >= 2) {
                buscarComprasAvance();
            }
        }

        function quitarAnticipoAvanceNuevo(idx, atpCod) {
            if (atpCod > 0) { avanceAtpCodSeleccionados.delete(atpCod); }
            $(`[data-avance-nuevo="${idx}"]`).remove();
            actualizarResumenTotalesAvance();
        }

        function htmlFilaBusquedaCompra(c, btnHtml) {
            const sub = parseFloat(c.Subtotal || 0).toFixed(2);
            const iva = parseFloat(c.Iva || 0).toFixed(2);
            const tot = parseFloat(c.Total || 0).toFixed(2);
            return `
                <tr class="text-center">
                    <td class="fw-bold">${avanceEsc(c.Cop_Num)}</td>
                    <td>${avanceEsc(c.Cop_Fec)}</td>
                    <td class="text-start">${avanceEsc(c.Proveedor)}</td>
                    <td class="text-end font-monospace">$ ${sub}</td>
                    <td class="text-end font-monospace">$ ${iva}</td>
                    <td class="text-end font-monospace fw-bold">$ ${tot}</td>
                    <td>${btnHtml}</td>
                </tr>
            `;
        }

        function buscarComprasAvance() {
            clearTimeout(avanceSearchTimer);
            const term = $('#txtBuscarCompraAvance').val().trim();
            if (term.length < 2) { $('#divResultComprasAvance').hide(); return; }
            avanceSearchTimer = setTimeout(function() {
                $.getJSON('adq_bandeja.php', { ajax_buscar_compras: true, search: term }, function(res) {
                    if (!res.success) { return; }
                    const seleccionados = obtenerCopCodsAvanceSeleccionados();
                    const $tbody = $('#tblBuscarComprasAvance').empty();
                    if (!res.compras.length) {
                        $tbody.append('<tr><td colspan="7" class="text-center text-muted">No se encontraron facturas.</td></tr>');
                    } else {
                        res.compras.forEach(function(c) {
                            const ya = seleccionados.has(parseInt(c.Cop_Cod, 10));
                            const tot = roundMoney(c.Total);
                            const check = validarAgregarFacturaAvance(tot);
                            let btn;
                            if (ya) {
                                btn = '<span class="badge bg-secondary">Agregada</span>';
                            } else if (!check.ok) {
                                btn = '<span class="badge bg-danger" title="' + avanceEsc(check.message) + '">Excede</span>';
                            } else {
                                btn = `<button type="button" class="btn btn-xs btn-success p-1 py-0" onclick="agregarCompraAvance(${c.Cop_Cod})"><i class="bi bi-plus-lg"></i></button>`;
                            }
                            $tbody.append(htmlFilaBusquedaCompra(c, btn));
                        });
                    }
                    $('#divResultComprasAvance').show();
                });
            }, 350);
        }

        function agregarCompraAvance(copCod) {
            copCod = parseInt(copCod, 10);
            if (!copCod) { return; }
            if (obtenerCopCodsAvanceSeleccionados().has(copCod)) {
                alert('Esta factura ya fue agregada.');
                return;
            }
            $.getJSON('adq_bandeja.php', { ajax_get_compra_avance: true, cop_cod: copCod }, function(res) {
                if (!res.success) {
                    alert(res.message || 'No se pudo obtener el detalle de la factura.');
                    return;
                }
                const totalFactura = roundMoney(res.compra && res.compra.Total !== undefined ? res.compra.Total : 0);
                const check = validarAgregarFacturaAvance(totalFactura);
                if (!check.ok) {
                    alert(check.message);
                    return;
                }
                const idx = avanceDocNuevoIdx++;
                avanceCopCodSeleccionados.add(copCod);
                $('#lstAvanceDocsNuevos').append(htmlTarjetaAvanceCompra({
                    idx: idx,
                    copCod: copCod,
                    des: '',
                    compra: res.compra,
                    esNuevo: true
                }));
                actualizarResumenTotalesAvance();
                buscarComprasAvance();
            }).fail(function() {
                alert('Error de red al consultar la factura.');
            });
        }

        function htmlFilaBusquedaAnticipo(a, btnHtml) {
            const valor = parseFloat(a.Atp_Val || 0).toFixed(2);
            const saldo = parseFloat(a.Saldo || 0).toFixed(2);
            const est = (a.Atp_Est === 'U') ? 'Usado' : 'Activo';
            return `
                <tr class="text-center">
                    <td class="fw-bold"># ${avanceEsc(a.Atp_Cod)}</td>
                    <td>${avanceEsc(a.Atp_Fec)}</td>
                    <td class="text-start">${avanceEsc(a.Proveedor)}</td>
                    <td class="text-end font-monospace">$ ${valor}</td>
                    <td class="text-end font-monospace fw-bold">$ ${saldo}</td>
                    <td>${est}</td>
                    <td>${btnHtml}</td>
                </tr>
            `;
        }

        function buscarAnticiposAvance() {
            clearTimeout(anticipoSearchTimer);
            const term = $('#txtBuscarAnticipoAvance').val().trim();
            if (term.length < 2) { $('#divResultAnticiposAvance').hide(); return; }
            anticipoSearchTimer = setTimeout(function() {
                $.getJSON('adq_bandeja.php', { ajax_buscar_anticipos: true, search: term }, function(res) {
                    if (!res.success) { return; }
                    const seleccionados = obtenerAtpCodsAvanceSeleccionados();
                    const $tbody = $('#tblBuscarAnticiposAvance').empty();
                    if (!res.anticipos || !res.anticipos.length) {
                        $tbody.append('<tr><td colspan="7" class="text-center text-muted">No se encontraron anticipos.</td></tr>');
                    } else {
                        res.anticipos.forEach(function(a) {
                            const ya = seleccionados.has(parseInt(a.Atp_Cod, 10));
                            const btn = ya
                                ? '<span class="badge bg-secondary">Agregado</span>'
                                : `<button type="button" class="btn btn-xs btn-success p-1 py-0" onclick="agregarAnticipoAvance(${a.Atp_Cod})"><i class="bi bi-plus-lg"></i></button>`;
                            $tbody.append(htmlFilaBusquedaAnticipo(a, btn));
                        });
                    }
                    $('#divResultAnticiposAvance').show();
                });
            }, 350);
        }

        function agregarAnticipoAvance(atpCod) {
            atpCod = parseInt(atpCod, 10);
            if (!atpCod) { return; }
            if (obtenerAtpCodsAvanceSeleccionados().has(atpCod)) {
                alert('Este anticipo ya fue agregado.');
                return;
            }
            $.getJSON('adq_bandeja.php', { ajax_get_anticipo_avance: true, atp_cod: atpCod }, function(res) {
                if (!res.success) {
                    alert(res.message || 'No se pudo obtener el detalle del anticipo.');
                    return;
                }
                const idx = avanceDocNuevoIdx++;
                avanceAtpCodSeleccionados.add(atpCod);
                $('#lstAvanceDocsNuevos').append(htmlTarjetaAvanceAnticipo({
                    idx: idx,
                    atpCod: atpCod,
                    des: '',
                    anticipo: res.anticipo,
                    esNuevo: true
                }));
                buscarAnticiposAvance();
            }).fail(function() {
                alert('Error de red al consultar el anticipo.');
            });
        }

        function guardarAvanceDocs(onSuccess) {
            const solCod = $('#avanceSolCod').val() || currentSolCod;
            if (!solCod) {
                alert('No se identifico la solicitud.');
                return;
            }
            if (currentNodTip === 'FISCALIZACION') {
                let fiscalInvalido = '';
                $('#lstAvanceDocsExistentes .adq-fiscal-doc-guardado').each(function() {
                    if ($(this).find('input[name="sav_eliminar[]"]').length) {
                        return;
                    }
                    const titulo = $(this).find('input[name*="[Sav_Des]"]').val().trim();
                    if (!titulo) {
                        fiscalInvalido = 'Todos los PDF guardados deben tener un titulo.';
                        return false;
                    }
                });
                if (!fiscalInvalido) {
                    $('#lstFiscalDocsNuevos .adq-fiscal-doc-nuevo').each(function() {
                        const titulo = $(this).find('input[type="text"]').val().trim();
                        const input = $(this).find('input[type="file"]')[0];
                        const file = input && input.files && input.files[0] ? input.files[0] : null;
                        if (!titulo) {
                            fiscalInvalido = 'Ingrese un titulo para cada PDF de fiscalizacion.';
                            return false;
                        }
                        if (!file) {
                            fiscalInvalido = 'Seleccione el archivo correspondiente a cada titulo.';
                            return false;
                        }
                        if (!/\.pdf$/i.test(file.name)) {
                            fiscalInvalido = 'Solo se permiten archivos PDF en fiscalizacion.';
                            return false;
                        }
                    });
                }
                if (fiscalInvalido) {
                    alert(fiscalInvalido);
                    return;
                }
            }
            const fd = new FormData($('#frmAvanceDocs')[0]);
            fd.append('ajax_save_avance_docs', '1');
            fd.set('Sol_Cod', solCod);
            adqMostrarLoaderAccion('Guardando...', 'Registrando documentos. Espere un momento.');
            $.ajax({
                url: 'adq_bandeja.php',
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function(res) {
                if (res.success) {
                    if (res.avances) {
                        renderAvanceDocs(res.avances);
                    }
                    if (res.historial) {
                        renderHistorialPanel(res.historial);
                    }
                    if (typeof onSuccess === 'function') {
                        // Quitar loader del guardado; el siguiente paso (validar/finalizar) muestra el suyo si aplica.
                        adqOcultarLoaderAccion();
                        onSuccess();
                    } else {
                        adqOcultarLoaderAccion();
                        const $msg = $('#avanceGuardadoMsg');
                        const msgOk = (currentNodTip === 'FISCALIZACION')
                            ? 'Documentos guardados. La solicitud permanece en este proceso; para avanzar complete el comentario y pulse Aprobar.'
                            : 'Facturas/anticipos guardados. La solicitud permanece en este proceso; para avanzar complete el comentario y pulse Finalizar proceso.';
                        $msg.stop(true, true).text(msgOk).fadeIn(180).delay(5500).fadeOut(350);
                    }
                } else {
                    adqOcultarLoaderAccion();
                    alert('Error: ' + (res.message || 'No se pudieron guardar los documentos.'));
                }
            }).fail(function() {
                adqOcultarLoaderAccion();
                alert('Error de red al guardar las facturas de avance.');
            });
        }

        function tieneFacturasAvanceSinGuardar() {
            return tieneAvancePendienteGuardar();
        }

        function contarFacturasAvanceGuardadas() {
            return $('#lstAvanceDocsExistentes [data-sav-cod]').length
                + $('#lstAvanceDocsNuevos [data-avance-nuevo]').length;
        }

        function finalizarAvanceProceso() {
            // Validar montos ANTES de guardar/mostrar loader, incluyendo facturas recien agregadas.
            const checkTot = validarTotalesAvanceParaFinalizar();
            if (!checkTot.ok) {
                adqOcultarLoaderAccion();
                alert(checkTot.message);
                return;
            }
            if (tieneAvancePendienteGuardar()) {
                // Persistir altas/bajas pendientes para que el backend no sume facturas ya quitadas.
                guardarAvanceDocs(function() {
                    finalizarAvanceProcesoConfirmado();
                });
                return;
            }
            finalizarAvanceProcesoConfirmado();
        }

        function finalizarAvanceProcesoConfirmado() {
            adqOcultarLoaderAccion();
            if (contarFacturasAvanceGuardadas() <= 0) {
                alert('Debe registrar al menos una factura o anticipo antes de finalizar el proceso.');
                return;
            }
            const checkTot = validarTotalesAvanceParaFinalizar();
            if (!checkTot.ok) {
                alert(checkTot.message);
                return;
            }
            if (!$('#actionComentario').val().trim()) {
                alert('Debe ingresar el comentario/justificacion antes de finalizar el proceso de avance.');
                $('#actionComentario').focus();
                return;
            }
            confirmarCentrado('Desea finalizar el proceso de avance? Ya no podra agregar mas facturas ni anticipos en esta etapa.', function() {
                enviarAccion('APROBAR');
            });
        }

        function contarDocsFiscalGuardados() {
            return $('#lstAvanceDocsExistentes [data-sav-cod]').length
                + $('#lstFiscalDocsNuevos .adq-fiscal-doc-nuevo').length;
        }

        function tieneDocsFiscalSinGuardar() {
            return tieneAvancePendienteGuardar();
        }

        function renderHistorialArchivos(archivos, isnAdjFallback) {
            let lista = [];
            if (archivos && archivos.length) {
                lista = archivos;
            } else if (isnAdjFallback) {
                const texto = String(isnAdjFallback).trim();
                if (texto.charAt(0) === '[') {
                    try {
                        lista = JSON.parse(texto).filter(Boolean).map(function(p, i) {
                            return { path: p, label: 'Sustento ' + (i + 1) };
                        });
                    } catch (e) { lista = []; }
                } else {
                    lista = [{ path: texto, label: 'Sustento adjunto' }];
                }
            }
            if (!lista.length) return '';
            let html = '<div class="adq-hist-archivos" style="margin-top:8px;display:flex;flex-wrap:wrap;gap:6px;">';
            lista.forEach(function(a) {
                const ext = String(a.path || '').split('.').pop().toLowerCase();
                const esExpFirmado = parseInt(a.es_expediente_firmado || 0, 10) === 1;
                const esExp = esExpFirmado || parseInt(a.es_expediente || 0, 10) === 1;
                let icon = ext === 'pdf' ? 'bi-file-earmark-pdf' : 'bi-paperclip';
                let btnClass = 'btn-outline-primary';
                if (esExpFirmado) {
                    icon = 'bi-file-earmark-check';
                    btnClass = 'btn-outline-success';
                } else if (esExp) {
                    icon = 'bi-file-earmark-lock2';
                    btnClass = 'btn-outline-secondary';
                }
                const label = escHtmlHist(a.label || 'Archivo');
                html += `<a href="${adqUrlDocumento(a.path)}" target="_blank" class="btn btn-xs ${btnClass}" style="font-size:11px;padding:3px 8px;"><i class="bi ${icon}"></i> ${label}</a>`;
            });
            html += '</div>';
            return html;
        }

        function renderHistorialFacturas(facturas) {
            if (!facturas || !facturas.length) {
                return '';
            }
            let html = '<div class="adq-hist-facturas mt-2">';
            facturas.forEach(function(f) {
                const numero = escHtmlHist(f.numero || ('#' + (f.cop_cod || '')));
                const proveedor = escHtmlHist(f.proveedor || 'Proveedor');
                const fecha = f.fecha ? `<span class="text-muted">(${escHtmlHist(f.fecha)})</span>` : '';
                const total = parseFloat(f.total || 0) > 0
                    ? `<span class="fw-bold font-monospace ms-1">$ ${parseFloat(f.total).toFixed(2)}</span>`
                    : '';
                const pdf = f.link
                    ? `<a href="${f.link}" target="_blank" class="btn btn-xs btn-outline-primary ms-2" style="font-size:11px;padding:2px 8px;"><i class="bi bi-file-earmark-pdf"></i> Ver PDF</a>`
                    : '';
                const des = f.des ? `<div class="text-muted mt-1" style="font-size:11px;">${escHtmlHist(f.des)}</div>` : '';
                let compsHtml = '';
                if (f.comprobantes && f.comprobantes.length) {
                    compsHtml = '<div class="adq-hist-comprobantes mt-1" style="padding-left:8px;border-left:2px solid #cbd5e1;">'
                        + '<div class="text-muted" style="font-size:11px;margin-bottom:2px;"><i class="bi bi-journal-text"></i> Comprobantes de pago:</div>';
                    f.comprobantes.forEach(function(c) {
                        const codigo = escHtmlHist(c.codigo || ('#' + (c.com_cod || '')));
                        const cFecha = c.fecha ? `<span class="text-muted">(${escHtmlHist(c.fecha)})</span>` : '';
                        const cVal = parseFloat(c.valor || 0) > 0
                            ? `<span class="font-monospace ms-1">$ ${parseFloat(c.valor).toFixed(2)}</span>`
                            : '';
                        const cForma = c.forma ? `<span class="text-muted ms-1">${escHtmlHist(c.forma)}</span>` : '';
                        const cLink = c.link
                            ? `<a href="${c.link}" target="_blank" class="btn btn-xs btn-outline-secondary ms-1" style="font-size:10px;padding:1px 6px;"><i class="bi bi-box-arrow-up-right"></i> ${codigo}</a>`
                            : `<span class="fw-semibold">${codigo}</span>`;
                        const cImg = (c.pag_img && String(c.pag_img).trim() !== '')
                            ? `<a href="${escHtmlHist(c.pag_img)}" target="_blank" class="ms-1" title="Ver imagen del pago"><img src="${escHtmlHist(c.pag_img)}" alt="Pago" style="width:28px;height:28px;object-fit:cover;border-radius:3px;border:1px solid #cbd5e1;vertical-align:middle;"></a>`
                            : '';
                        compsHtml += `<div style="font-size:11px;margin-bottom:2px;">${cLink} ${cFecha}${cVal}${cForma}${cImg}</div>`;
                    });
                    compsHtml += '</div>';
                }
                html += `
                    <div class="border rounded p-2 mb-1 bg-white small">
                        <strong><i class="bi bi-receipt-cutoff"></i> Factura # ${numero}</strong> - ${proveedor}
                        ${fecha}${total}${pdf}${des}${compsHtml}
                    </div>
                `;
            });
            html += '</div>';
            return html;
        }

        function escHtmlHist(text) {
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

        function ordenarHistorialDesc(historial) {
            return (historial || []).slice()
                .filter(function(h) {
                    // Solo movimientos reales + etapa pendiente actual (no etapas futuras sin registro)
                    return !(parseInt(h.Sin_Registro || 0, 10) === 1 || h.Isn_Acc === 'SIN_REGISTRO');
                })
                .sort(function(a, b) {
                    const pendA = (parseInt(a.Pendiente_Aprobacion || 0, 10) === 1 || parseInt(a.Fin_Pendiente || 0, 10) === 1 || a.Isn_Acc === 'PENDIENTE') ? 1 : 0;
                    const pendB = (parseInt(b.Pendiente_Aprobacion || 0, 10) === 1 || parseInt(b.Fin_Pendiente || 0, 10) === 1 || b.Isn_Acc === 'PENDIENTE') ? 1 : 0;
                    if (pendA !== pendB) {
                        return pendB - pendA;
                    }
                    const fa = new Date(String(a.Isn_Fec || '').replace(' ', 'T')).getTime() || 0;
                    const fb = new Date(String(b.Isn_Fec || '').replace(' ', 'T')).getTime() || 0;
                    if (fb !== fa) {
                        return fb - fa;
                    }
                    const oa = parseInt(a.Hist_Orden, 10) || 0;
                    const ob = parseInt(b.Hist_Orden, 10) || 0;
                    if (ob !== oa) {
                        return ob - oa;
                    }
                    return (parseInt(b.Isn_Cod, 10) || 0) - (parseInt(a.Isn_Cod, 10) || 0);
                });
        }

        function inicialesActorHist(nombre) {
            const parts = String(nombre || '').trim().split(/\s+/).filter(Boolean);
            if (!parts.length) {
                return 'SY';
            }
            if (parts.length === 1) {
                return parts[0].substring(0, 2).toUpperCase();
            }
            return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
        }

        function formatearFechaHist(fec) {
            const raw = String(fec || '').trim();
            if (!raw || raw === 'Sin movimiento') {
                return 'Sin movimiento';
            }
            const d = new Date(raw.replace(' ', 'T'));
            if (isNaN(d.getTime())) {
                return raw;
            }
            const pad = function(n) { return (n < 10 ? '0' : '') + n; };
            return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear()
                + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
        }

        function badgeHistorialHtml(texto, color) {
            return '<span class="badge adq-hist-badge" style="background-color:' + color + ' !important;color:#ffffff !important;">' + texto + '</span>';
        }

        function renderHistorialPanel(historial) {
            const $hist = $('#lstHistorial').empty();
            const historialOrdenado = ordenarHistorialDesc(historial);
            if (!historialOrdenado.length) {
                $hist.append('<div class="adq-hist-empty"><i class="bi bi-journal-text"></i>No se registran movimientos en el workflow todav&iacute;a.</div>');
                return;
            }
            historialOrdenado.forEach(function(h, idx) {
                const numProceso = historialOrdenado.length - idx;
                const actor = h.Actor_Nom || h.Usuario_Nom || h.Dep_Des || 'Sistema';
                const actorEsc = escHtmlHist(actor);
                const actorModo = escHtmlHist(h.Actor_Modo || 'Por');
                const nodNom = escHtmlHist(h.Nod_Nom || 'Etapa');
                const fechaHist = escHtmlHist(formatearFechaHist(h.Isn_Fec));
                const initials = escHtmlHist(inicialesActorHist(actor));
                let actionBadge = '';
                let itemClass = '';

                if (parseInt(h.Fin_Pendiente || 0, 10) === 1) {
                    actionBadge = badgeHistorialHtml('Pendiente cierre', '#0284c7');
                    itemClass = 'active';
                } else if (h.Nod_Tip === 'DECISION') {
                    actionBadge = badgeHistorialHtml('Decisi&oacute;n', '#d97706');
                    itemClass = 'warning';
                } else if (parseInt(h.Pendiente_Aprobacion || 0, 10) === 1 || h.Isn_Acc === 'PENDIENTE') {
                    let pendTxt = 'Pendiente de aprobaci&oacute;n';
                    if (h.Nod_Tip === 'TAREA') {
                        pendTxt = 'Tarea pendiente';
                    } else if (h.Nod_Tip === 'FIN') {
                        pendTxt = 'Pendiente cierre';
                    } else if (h.Nod_Tip === 'AVANCE') {
                        pendTxt = 'Pendiente de avance';
                    } else if (h.Nod_Tip === 'FISCALIZACION') {
                        pendTxt = 'Pendiente de fiscalizaci&oacute;n';
                    } else if (h.Nod_Tip === 'FACTURA') {
                        pendTxt = 'Pendiente de factura';
                    }
                    actionBadge = badgeHistorialHtml(pendTxt, '#2563eb');
                    itemClass = 'active';
                } else if (parseInt(h.Sin_Registro || 0, 10) === 1 || h.Isn_Acc === 'SIN_REGISTRO') {
                    actionBadge = badgeHistorialHtml('Sin registro', '#94a3b8');
                    itemClass = '';
                } else if (h.Isn_Acc === 'CREAR') {
                    actionBadge = badgeHistorialHtml('Inici&oacute; pedido', '#64748b');
                    itemClass = 'active';
                } else if (h.Isn_Acc === 'APROBAR') {
                    actionBadge = badgeHistorialHtml('Aprobado', '#059669');
                    itemClass = 'success';
                } else if (h.Isn_Acc === 'COMPLETAR') {
                    actionBadge = badgeHistorialHtml('Tarea completada', '#059669');
                    itemClass = 'success';
                } else if (h.Isn_Acc === 'OBSERVAR') {
                    actionBadge = '<span class="badge adq-hist-badge" style="background-color:#d97706 !important;color:#fffbeb !important;">Observado</span>';
                    itemClass = 'warning';
                } else if (h.Isn_Acc === 'DEVOLVER') {
                    actionBadge = badgeHistorialHtml('Devuelto', '#4b5563');
                    itemClass = 'active';
                } else if (h.Isn_Acc === 'RECHAZAR') {
                    actionBadge = badgeHistorialHtml('Rechazado', '#dc2626');
                    itemClass = 'danger';
                } else if (h.Isn_Acc === 'REENVIAR') {
                    actionBadge = badgeHistorialHtml('Reenvi&oacute; correcci&oacute;n', '#0284c7');
                    itemClass = 'active';
                } else if (h.Isn_Acc === 'AVANCE') {
                    actionBadge = badgeHistorialHtml('Documentos cargados', '#0284c7');
                    itemClass = 'active';
                } else if (h.Isn_Acc === 'ACTUALIZAR') {
                    actionBadge = badgeHistorialHtml('Formulario completado', '#0d9488');
                    itemClass = 'active';
                } else if (h.Isn_Acc === 'COTIZAR') {
                    actionBadge = badgeHistorialHtml('Proformas cargadas', '#2563eb');
                    itemClass = 'active';
                } else if (h.Isn_Acc === 'CONDICIONAL') {
                    actionBadge = badgeHistorialHtml('Rama', '#d97706');
                    itemClass = 'warning';
                } else if (h.Isn_Acc) {
                    actionBadge = badgeHistorialHtml(escHtmlHist(h.Isn_Acc), '#64748b');
                    itemClass = 'active';
                }

                const esDecision = (h.Nod_Tip === 'DECISION');
                const commentHtml = h.Isn_Com
                    ? `<div class="adq-timeline-comment">${escHtmlHist(h.Isn_Com)}</div>`
                    : '';
                const actorHtml = esDecision
                    ? ''
                    : `<div class="adq-hist-actor">
                                    <span class="adq-hist-avatar" aria-hidden="true">${initials}</span>
                                    <span class="adq-hist-actor-meta">
                                        <span class="adq-hist-actor-mode">${actorModo}</span>
                                        <span class="adq-hist-actor-name">${actorEsc}</span>
                                    </span>
                                </div>`;

                $hist.append(`
                    <div class="adq-timeline-item ${itemClass}">
                        <div class="adq-timeline-content">
                            <div class="adq-timeline-header">
                                <span class="adq-timeline-title">
                                    <span class="adq-timeline-step">
                                        <span class="adq-timeline-step-num" title="Paso ${numProceso}">${numProceso}</span>
                                        <span class="adq-timeline-stage">${nodNom}</span>
                                    </span>
                                    ${actionBadge}
                                </span>
                                <span class="adq-timeline-date"><i class="bi bi-calendar3"></i> ${fechaHist}</span>
                            </div>
                            <div class="adq-timeline-body">
                                ${actorHtml}
                                ${commentHtml}
                                ${esDecision ? '' : renderHistorialFacturas(h.facturas)}
                                ${esDecision ? '' : renderHistorialArchivos(h.archivos, h.Isn_Adj)}
                            </div>
                        </div>
                    </div>
                `);
            });
            $hist.closest('.adq-scroll-historial').scrollTop(0);
        }

        function reenviarObservada(solCod, solNum) {
            confirmarCentrado('Desea reenviar la solicitud #' + solNum + ' corregida a aprobacion?', function() {
                $.post('adq_bandeja.php', { ajax_reenviar_observada: 1, Sol_Cod: solCod }, function(res) {
                    if (res.success) {
                        mostrarMensajeCentrado('La solicitud #' + res.Num + ' fue reenviada a aprobacion correctamente.', function() {
                            window.location.reload();
                        }, 'success');
                    } else {
                        let msg = res.message || 'Error desconocido';
                        if (res.requiere_completar) {
                            msg += '\n\nUse el boton Corregir para completar la informacion faltante antes de reenviar.';
                        }
                        alert('No se puede reenviar: ' + msg);
                    }
                }, 'json').fail(function() {
                    alert('Error de red al reenviar la solicitud observada.');
                });
            });
        }

        function enviarBorrador(solCod, solNum) {
            confirmarCentrado('Desea enviar la solicitud #' + solNum + ' a aprobacion?', function() {
                $.post('adq_bandeja.php', { ajax_enviar_borrador: 1, Sol_Cod: solCod }, function(res) {
                    if (res.success) {
                        mostrarMensajeCentrado('La solicitud #' + res.Num + ' fue enviada a aprobacion correctamente.', function() {
                            window.location.reload();
                        }, 'success');
                    } else {
                        let msg = res.message || 'Error desconocido';
                        if (res.requiere_completar) {
                            msg += '\n\nUse el boton Completar para agregar la informacion faltante.';
                        }
                        alert('No se puede enviar: ' + msg);
                    }
                }, 'json').fail(function() {
                    alert('Error de red al enviar el borrador.');
                });
            });
        }

        function mostrarResolucionView() {
            $('.exa-ui-page-view').hide();
            $('#mdlResolution').show();
            $('.exa-body').scrollTop(0);
            $(window).scrollTop(0);
        }

        function cerrarResolucion() {
            $('#mdlResolution').hide();
            $('.exa-ui-page-view').show();
        }

        function abrirResolucion(solCod, renderPanelAction) {
            currentSolCod = solCod;
            $.getJSON('adq_bandeja.php', { ajax_get_solicitud_detail: true, sol_cod: solCod }, function(res) {
                if (res.success) {
                    const sol = res.solicitud;
                    currentFlowVisual = res.flow_visual || null;
                    currentInsCod = sol.Ins_Cod;
                    currentSolEst = sol.Sol_Est;
                    currentEsSolicitante = parseInt(sol.Es_Solicitante, 10) === 1;

                    // Cabecera
                    $('#lblModalTitle').text('Solicitud de Adquisicion N ' + sol.Sol_Num);
                    const etapaTxt = sol.Nod_Nom ? ('Etapa: ' + sol.Nod_Nom) : 'Sin etapa activa';
                    const tipoTxt = sol.Trq_Des ? ('Tipo: ' + sol.Trq_Des) : '';
                    $('#lblModalSubtitle').text([tipoTxt, etapaTxt].filter(Boolean).join(' ? '));
                    const solicitante = (sol.Sol_Nom || sol.Sol_Ape) ? `${sol.Sol_Nom} ${sol.Sol_Ape}`.trim() : (sol.Usu_Nom || 'N/D');
                    $('#detSolicitante').text(solicitante);
                    $('#detDepartamento').text((sol.Dep_Des && String(sol.Dep_Des).trim() !== '') ? sol.Dep_Des : 'Sin departamento');
                    $('#detTipo').text(sol.Trq_Des);
                    $('#detTotal').text(`$ ${parseFloat(sol.Sol_Val_Est).toFixed(2)}`);
                    $('#detJustificacion').text(sol.Sol_Jus);

                    const reqParts = [];
                    if (parseInt(sol.Sol_Req_Cot, 10) === 1) {
                        reqParts.push(`Cotizaciones: min. ${sol.Sol_Min_Cot || 1}`);
                    } else {
                        reqParts.push('Cotizaciones: no obligatorias');
                    }
                    if (parseInt(sol.Sol_Req_Fac, 10) === 1) reqParts.push('Factura al cierre');
                    if (parseInt(sol.Sol_Req_Pro, 10) === 1) reqParts.push('Proveedor sugerido');
                    if (parseInt(sol.Sol_Req_Adj, 10) === 1) reqParts.push('Adjuntos de soporte (opcionales)');
                    if (parseInt(sol.Sol_Req_Pre, 10) === 1) reqParts.push('Verificar presupuesto');
                    if (sol.Sol_Tiempo_Est) reqParts.push(`SLA: ${sol.Sol_Tiempo_Est} dias`);
                    $('#detRequisitos').text(reqParts.join(' ? '));

                    // ?tems
                    const $tbody = $('#tblDetItems tbody').empty();
                    if (!res.items || res.items.length === 0) {
                        $tbody.append('<tr class="text-center"><td colspan="6" class="text-muted py-2">No hay articulos registrados en esta solicitud.</td></tr>');
                    } else {
                    res.items.forEach(function(item, idx) {
                        const descripcion = item.Sde_Des || item.Pro_Nom || 'Sin descripcion';
                        const ivaBadge = parseInt(item.Sde_Iva) === 1 ? '<span class="badge bg-success" style="background-color: #10b981 !important; color: #ffffff !important; font-size: 9px; padding: 2px 4px;">SI</span>' : '<span class="badge bg-secondary" style="background-color: #6b7280 !important; color: #ffffff !important; font-size: 9px; padding: 2px 4px;">NO</span>';
                        $tbody.append(`
                            <tr class="text-center">
                                <td>${idx + 1}</td>
                                <td class="text-start">${descripcion}</td>
                                <td>${ivaBadge}</td>
                                <td>${parseFloat(item.Sde_Can).toFixed(4)}</td>
                                <td class="text-end">$ ${parseFloat(item.Sde_Pru).toFixed(2)}</td>
                                <td class="text-end fw-bold">$ ${(parseFloat(item.Sde_Can) * parseFloat(item.Sde_Pru)).toFixed(2)}</td>
                            </tr>
                        `);
                    });
                    }

                    // Cotizaciones
                    const $cotList = $('#detCotizacionesList').empty();
                    const cotizaciones = res.cotizaciones || [];
                    if (cotizaciones.length > 0) {
                        $('#divDetCotizaciones').show();
                        cotizaciones.forEach(function(c) {
                            const ganadorClass = c.Cot_Sel == 1 ? 'adq-cot-row-ganadora' : '';
                            const badgeGanador = c.Cot_Sel == 1 ? ' <span class="badge bg-success ms-1" style="background-color:#10b981!important;color:#fff!important;"><i class="bi bi-trophy"></i> Seleccionada</span>' : '';
                            const proveedor = c.Prv_Com || ((c.Prs_Nom || '') + ' ' + (c.Prs_Ape || '')).trim();
                            let adjuntos = [];
                            if (c.Cot_Adj) {
                                const texto = String(c.Cot_Adj).trim();
                                if (texto.charAt(0) === '[') {
                                    try { adjuntos = JSON.parse(texto).filter(Boolean); } catch (e) { adjuntos = []; }
                                } else {
                                    adjuntos = [texto];
                                }
                            }
                            const pdfLinks = adjuntos.length
                                ? `<div class="adq-cot-pdf-links">${adjuntos.map(function(path, i) {
                                    const label = adjuntos.length > 1 ? ('PDF ' + (i + 1)) : 'Ver PDF';
                                    return `<a href="${adqUrlDocumento(path)}" target="_blank" class="btn btn-xs btn-primary adq-cot-pdf-link"><i class="bi bi-file-earmark-pdf"></i> ${label}</a>`;
                                }).join('')}</div>`
                                : '<span class="text-muted" style="font-size:11px;">Sin PDF</span>';
                            const jusTexto = c.Cot_Jus
                                ? $('<div>').text(c.Cot_Jus).html()
                                : (c.Cot_Sel == 1 ? '<span class="text-warning">Sin justificacion</span>' : '<span class="text-muted">?</span>');
                            $cotList.append(`
                                <tr class="${ganadorClass}">
                                    <td class="align-middle"><span class="fw-bold text-dark">${$('<div>').text(proveedor).html()}</span>${badgeGanador}</td>
                                    <td class="text-end align-middle font-monospace text-success fw-bold">$ ${parseFloat(c.Cot_Val || 0).toFixed(2)}</td>
                                    <td class="text-center align-middle adq-cot-pdf-cell">${pdfLinks}</td>
                                    <td class="align-middle adq-cot-jus-cell" style="font-size:12px;">${jusTexto}</td>
                                </tr>
                            `);
                        });
                    } else {
                        $('#divDetCotizaciones').hide();
                    }

                    const $adjList = $('#detAdjuntosList').empty();
                    const adjuntos = res.adjuntos || [];
                    if (adjuntos.length > 0) {
                        $('#divDetAdjuntos').show();
                        adjuntos.forEach(function(a) {
                            const des = $('<div>').text(a.Sad_Des || 'Sin descripcion').html();
                            const path = a.Sad_Adj || '';
                            const pdf = path
                                ? `<a href="${adqUrlDocumento(path)}" target="_blank" class="btn btn-xs btn-primary"><i class="bi bi-file-earmark-pdf"></i> Ver PDF</a>`
                                : '<span class="text-muted">Sin archivo</span>';
                            $adjList.append(`
                                <tr>
                                    <td class="align-middle text-start">${des}</td>
                                    <td class="text-center align-middle">${pdf}</td>
                                </tr>
                            `);
                        });
                    } else {
                        $('#divDetAdjuntos').hide();
                    }

                    // Historial (mas reciente arriba, inicio del flujo abajo)
                    renderHistorialPanel(res.historial || []);

                    // Compras vinculadas
                    currentSolCod = sol.Sol_Cod;
                    const comprasV = res.compras_vinculadas || [];
                    const $comprasList = $('#lstComprasVinculadas').empty();
                    if (comprasV.length > 0) {
                        $('#divComprasVinculadas').show();
                        comprasV.forEach(function(cv) {
                            $comprasList.append(`
                                <div class="card p-2 mb-2 border-success bg-success-subtle d-flex flex-row justify-content-between align-items-center" style="font-size: 12px;">
                                    <div>
                                        <strong><i class="bi bi-receipt-cutoff"></i> Factura # ${cv.Cop_Num}</strong> - ${cv.Proveedor} 
                                        <span class="text-muted">(${cv.Cop_Fec})</span>
                                        <span class="fw-bold font-monospace text-dark ms-2">$ ${parseFloat(cv.Total_Compra || 0).toFixed(2)}</span>
                                    </div>
                                    <button class="btn btn-xs btn-outline-danger p-1 py-0 border-0" onclick="desvincularCompra(${cv.Scm_Cod}, ${sol.Sol_Cod})"><i class="bi bi-x-lg"></i></button>
                                </div>
                            `);
                        });
                    } else {
                        $('#divComprasVinculadas').hide();
                    }

                    // Panel de vinculaci?n de compra (solo en nodos FACTURA)
                    if (renderPanelAction && sol.Nod_Tip === 'FACTURA' && sol.Ins_Est === 'P') {
                        $('#panelVincularCompra').show();
                        $('#txtBuscarCompra').val('');
                        $('#divResultCompras').hide();
                    } else {
                        $('#panelVincularCompra').hide();
                    }

                    // Panel de acci?n / espera de correcci?n / cotizaciones
                    $('#panelDecision').hide();
                    $('#panelCotizacionesEtapa').hide();
                    $('#panelAvanceEtapa').hide();
                    $('#panelExpedienteFin').hide();
                    $('#btnFinalizarAvance').hide();
                    $('#panelEsperaCorreccion').hide();
                    $('#btnIrCorregirObservada').hide();

                    const puedeResolver = parseInt(sol.Puede_Resolver, 10) === 1;
                    const puedeCot = parseInt(sol.Puede_Cargar_Cotizaciones, 10) === 1;
                    const puedeSelGanadora = parseInt(sol.Puede_Seleccionar_Ganadora, 10) === 1;
                    const puedeAvance = parseInt(sol.Puede_Cargar_Avance, 10) === 1;

                    if (puedeCot || puedeSelGanadora) {
                        $('#lblCotizacionesEtapaNodo').text(sol.Nod_Nom || 'Etapa actual');
                        if (puedeCot && puedeSelGanadora) {
                            $('#lblCotizacionesEtapaTitulo').text('Cotizaciones / Proformas');
                            $('#lblCotizacionesEtapaAyuda').html('La etapa <strong id="lblCotizacionesEtapaNodo"></strong> permite cargar cotizaciones y seleccionar la ganadora.');
                            $('#lblCotizacionesEtapaNodo').text(sol.Nod_Nom || 'Etapa actual');
                            $('#icoCotizacionesEtapa').attr('class', 'bi bi-file-earmark-pdf');
                            $('#btnCargarCotizaciones').find('i').attr('class', 'bi bi-file-earmark-pdf');
                            $('#lblBtnCargarCotizaciones').text('Cargar / seleccionar cotizaciones');
                        } else if (puedeSelGanadora) {
                            $('#lblCotizacionesEtapaTitulo').text('Seleccionar cotizacion ganadora');
                            $('#lblCotizacionesEtapaAyuda').html('La etapa <strong id="lblCotizacionesEtapaNodo"></strong> permite marcar la cotizacion ganadora (sin cargar nuevas proformas).');
                            $('#lblCotizacionesEtapaNodo').text(sol.Nod_Nom || 'Etapa actual');
                            $('#icoCotizacionesEtapa').attr('class', 'bi bi-trophy');
                            $('#btnCargarCotizaciones').find('i').attr('class', 'bi bi-trophy');
                            $('#lblBtnCargarCotizaciones').text('Seleccionar ganadora');
                        } else {
                            $('#lblCotizacionesEtapaTitulo').text('Cotizaciones / Proformas');
                            $('#lblCotizacionesEtapaAyuda').html('La etapa <strong id="lblCotizacionesEtapaNodo"></strong> permite cargar o actualizar las cotizaciones de esta solicitud.');
                            $('#lblCotizacionesEtapaNodo').text(sol.Nod_Nom || 'Etapa actual');
                            $('#icoCotizacionesEtapa').attr('class', 'bi bi-file-earmark-pdf');
                            $('#btnCargarCotizaciones').find('i').attr('class', 'bi bi-file-earmark-pdf');
                            $('#lblBtnCargarCotizaciones').text('Cargar cotizaciones');
                        }
                        $('#panelCotizacionesEtapa').show();
                    }

                    if (puedeAvance) {
                        configurarTextosPanelAvance(sol.Nod_Tip);
                        configurarValorReferenciaAvance(sol, res.cotizaciones || []);
                        $('#lblAvanceEtapaNodo').text(sol.Nod_Nom || 'Etapa actual');
                        $('#avanceSolCod').val(sol.Sol_Cod);
                        renderAvanceDocs(res.avances || []);
                        // En Fiscalizacion se aprueba desde el panel de decision (no Finalizar proceso)
                        $('#btnFinalizarAvance').toggle(puedeResolver && sol.Nod_Tip === 'AVANCE');
                        $('#avanceGuardadoMsg').hide().text('');
                        $('#panelAvanceEtapa').show();
                    } else {
                        $('#secFiscalArchivos').hide();
                        $('#lstFiscalDocsNuevos').empty();
                    }

                    if (sol.Sol_Est === 'O') {
                        const detalleObs = (sol.Motivo_Bloqueo || 'Corrija lo solicitado y pulse Reenviar correccion desde Mis Solicitudes.');
                        mostrarPanelEsperaCorreccion(
                            currentEsSolicitante ? 'Debe corregir esta solicitud' : 'Esperando correccion del solicitante',
                            detalleObs,
                            currentEsSolicitante
                        );
                    } else if (renderPanelAction && sol.Ins_Est === 'P' && puedeResolver) {
                        isComObl = parseInt(sol.Nod_Com_Obl, 10) === 1;
                        isAdjObl = parseInt(sol.Nod_Adj_Obl, 10) === 1;
                        solReqCot = parseInt(sol.Sol_Req_Cot, 10) === 1 ? 1 : 0;
                        solMinCot = Math.max(1, parseInt(sol.Sol_Min_Cot, 10) || 1);
                        // Si el nodo permite/requiere seleccionar ganadora, no se aprueba sin ella.
                        isCotSelObl = parseInt(sol.Nod_Cot_Sel, 10) === 1;
                        isCotEditObl = (parseInt(sol.Nod_Cot_Edit, 10) === 1) && solReqCot === 1;
                        const resumenCot = resumenCotizacionesResolucion(res.cotizaciones || []);
                        hasCotGanadora = !!resumenCot.ganadora;
                        hasCotMinimas = resumenCot.validas >= solMinCot;

                        $('#lblNodeActionName').text(sol.Nod_Nom || 'Etapa actual');
                        $('#actionInsCod').val(sol.Ins_Cod);
                        $('#actionComentario').val('');
                        quitarSustentoAdjunto();

                        $('#lblComReq').toggle(isComObl);
                        $('#lblAdjReq').toggle(isAdjObl);

                        configurarPanelResolucion(sol.Nod_Tip, res.expediente_pdfs, res.expediente, parseInt(res.tiene_llave_empresa, 10) === 1);
                        $('#panelDecision').show();
                        actualizarEstadoBotonesResolucion();
                    } else if (sol.Ins_Est === 'P' && !puedeResolver && !puedeCot && !puedeSelGanadora && !puedeAvance && (sol.Sol_Est === 'E' || sol.Sol_Est === 'P')) {
                        mostrarPanelEsperaCorreccion(
                            'Decisi\u00f3n no disponible',
                            sol.Motivo_Bloqueo || 'La solicitud esta en otra etapa o asignada a otro responsable.',
                            false
                        );
                    }

                    // Mostrar vista embebida (reemplaza la tabla)
                    mostrarResolucionView();
                } else {
                    alert('No se pudo cargar el detalle: ' + (res.message || 'Error desconocido'));
                }
            }).fail(function() {
                alert('Error de red al cargar el detalle de la solicitud.');
            });
        }

        function buscarComprasVincular() {
            clearTimeout(searchTimer);
            const term = $('#txtBuscarCompra').val().trim();
            if (term.length < 2) {
                $('#divResultCompras').hide();
                return;
            }
            searchTimer = setTimeout(function() {
                $.getJSON('adq_bandeja.php', { ajax_buscar_compras: true, search: term }, function(res) {
                    if (res.success) {
                        const $tbody = $('#tblBuscarCompras').empty();
                        if (res.compras.length === 0) {
                            $tbody.append('<tr><td colspan="7" class="text-center text-muted">No se encontraron facturas.</td></tr>');
                        } else {
                            res.compras.forEach(function(c) {
                                const btn = `<button class="btn btn-xs btn-success p-1 py-0" onclick="vincularCompra(${c.Cop_Cod})"><i class="bi bi-link-45deg"></i></button>`;
                                $tbody.append(htmlFilaBusquedaCompra(c, btn));
                            });
                        }
                        $('#divResultCompras').show();
                    }
                });
            }, 350);
        }

        function vincularCompra(copCod) {
            $.post('adq_bandeja.php?ajax_vincular_compra=1', { Sol_Cod: currentSolCod, Cop_Cod: copCod }, function(res) {
                if (res.success) {
                    alert('Factura vinculada correctamente a la solicitud.');
                    abrirResolucion(currentSolCod, true);
                } else {
                    alert('Error: ' + res.message);
                }
            }, 'json');
        }

        function desvincularCompra(scmCod, solCod) {
            confirmarCentrado('Desea desvincular esta factura de compra?', function() {
                $.post('adq_bandeja.php?ajax_desvincular_compra=1', { Scm_Cod: scmCod }, function(res) {
                    if (res.success) {
                        abrirResolucion(solCod, true);
                    } else {
                        alert('Error: ' + res.message);
                    }
                }, 'json');
            });
        }

        function adqInferirTipoMensaje(mensaje) {
            const t = String(mensaje || '').toLowerCase();
            if (t.indexOf('error') >= 0 || t.indexOf('no se pudo') >= 0 || t.indexOf('no se puede') >= 0 || t.indexOf('denegado') >= 0 || t.indexOf('critico') >= 0 || t.indexOf('crítico') >= 0) {
                return 'error';
            }
            if (t.indexOf('debe ') === 0 || t.indexOf('seleccione') >= 0 || t.indexOf('indique') >= 0 || t.indexOf('obligator') >= 0 || t.indexOf('supera el limite') >= 0 || t.indexOf('aun no') >= 0 || t.indexOf('aún no') >= 0) {
                return 'warning';
            }
            if (t.indexOf('correctamente') >= 0 || t.indexOf('exitos') >= 0 || t.indexOf('aprobad') >= 0 || t.indexOf('guardad') >= 0 || t.indexOf('enviad') >= 0 || t.indexOf('vinculad') >= 0 || t.indexOf('firmado') >= 0) {
                return 'success';
            }
            return 'info';
        }

        function adqMostrarLoaderAccion(titulo, detalle) {
            const $loader = $('#adqLoaderAccion');
            if (!$loader.length) {
                return;
            }
            if ($loader.parent()[0] !== document.body) {
                $loader.appendTo(document.body);
            }
            $('#adqLoaderAccionTitulo').text(titulo || 'Procesando...');
            $('#adqLoaderAccionDetalle').text(detalle || 'Por favor espere mientras se registra la tarea.');
            $('.adq-action-buttons button').prop('disabled', true);
            $loader.css('display', 'flex').attr('aria-busy', 'true');
        }

        function adqOcultarLoaderAccion() {
            const $loader = $('#adqLoaderAccion');
            if ($loader.length) {
                $loader.hide().attr('aria-busy', 'false');
            }
            $('.adq-action-buttons button').prop('disabled', false);
        }

        function mostrarMensajeCentrado(mensaje, callback, tipo) {
            const tipoFinal = tipo || adqInferirTipoMensaje(mensaje);
            const iconos = {
                success: 'bi bi-check-circle-fill',
                error: 'bi bi-x-circle-fill',
                danger: 'bi bi-x-octagon-fill',
                warning: 'bi bi-exclamation-triangle-fill',
                info: 'bi bi-info-circle-fill'
            };
            const btnClass = {
                success: 'btn btn-success',
                error: 'btn btn-danger',
                danger: 'btn btn-danger',
                warning: 'btn btn-warning',
                info: 'btn btn-primary'
            };
            const $overlay = $('#mdlAccionOkOverlay');
            const $box = $('#mdlAccionOkBox');
            $box.removeClass('is-success is-error is-danger is-warning is-info').addClass('is-' + tipoFinal);
            $('#mdlAccionOkIcon').html('<i class="' + (iconos[tipoFinal] || iconos.info) + '"></i>');
            $('#mdlAccionOkTitle').hide().text('');
            $('#mdlAccionOkText').css('font-weight', '700').css('color', '#0f172a').text(String(mensaje == null ? '' : mensaje));
            $('#btnMdlAccionCancel').hide().off('click.accOk');
            $('#btnMdlAccionOk')
                .attr('class', btnClass[tipoFinal] || btnClass.info)
                .text('Aceptar')
                .off('click.accOk');

            $overlay.stop(true, true).css({ display: 'flex', opacity: 0 }).animate({ opacity: 1 }, 150);

            const cerrar = function() {
                $overlay.animate({ opacity: 0 }, 150, function() {
                    $overlay.css('display', 'none');
                    if (typeof callback === 'function') {
                        callback();
                    }
                });
            };

            $('#btnMdlAccionOk').on('click.accOk', cerrar);
            $overlay.off('click.accOkBg').on('click.accOkBg', function(e) {
                if (e.target === this) {
                    cerrar();
                }
            });
        }

        /**
         * Modal de confirmacion centrado (profesional).
         * opciones: { titulo, mensaje, tipo, okText, cancelText }
         */
        function confirmarCentrado(mensajeOpciones, onConfirm, onCancel) {
            let opts = {
                titulo: '',
                mensaje: '',
                tipo: 'warning',
                okText: 'Continuar',
                cancelText: 'Cancelar'
            };
            if (mensajeOpciones && typeof mensajeOpciones === 'object') {
                opts.titulo = mensajeOpciones.titulo || '';
                opts.mensaje = mensajeOpciones.mensaje || '';
                opts.tipo = mensajeOpciones.tipo || 'warning';
                opts.okText = mensajeOpciones.okText || 'Continuar';
                opts.cancelText = mensajeOpciones.cancelText || 'Cancelar';
            } else {
                opts.mensaje = String(mensajeOpciones == null ? '' : mensajeOpciones);
            }

            const iconos = {
                warning: 'bi bi-exclamation-triangle-fill',
                danger: 'bi bi-exclamation-octagon-fill',
                error: 'bi bi-x-circle-fill',
                info: 'bi bi-question-circle-fill'
            };
            const btnClass = {
                warning: 'btn btn-warning',
                danger: 'btn btn-danger',
                error: 'btn btn-danger',
                info: 'btn btn-primary'
            };

            const $overlay = $('#mdlAccionOkOverlay');
            const $box = $('#mdlAccionOkBox');
            $box.removeClass('is-success is-error is-danger is-warning is-info').addClass('is-' + opts.tipo);
            $('#mdlAccionOkIcon').html('<i class="' + (iconos[opts.tipo] || iconos.warning) + '"></i>');
            if (opts.titulo) {
                $('#mdlAccionOkTitle').text(opts.titulo).show();
                $('#mdlAccionOkText').css('font-weight', '500').css('color', '#475569');
            } else {
                $('#mdlAccionOkTitle').hide().text('');
                $('#mdlAccionOkText').css('font-weight', '700').css('color', '#0f172a');
            }
            $('#mdlAccionOkText').text(opts.mensaje);
            $('#btnMdlAccionOk').attr('class', btnClass[opts.tipo] || btnClass.warning).text(opts.okText).off('click.accOk');
            $('#btnMdlAccionCancel').show().text(opts.cancelText).off('click.accOk');

            $overlay.stop(true, true).css({ display: 'flex', opacity: 0 }).animate({ opacity: 1 }, 150);

            const cerrar = function(ok) {
                $overlay.animate({ opacity: 0 }, 150, function() {
                    $overlay.css('display', 'none');
                    $('#btnMdlAccionCancel').hide();
                    $('#mdlAccionOkTitle').hide().text('');
                    if (ok) {
                        if (typeof onConfirm === 'function') onConfirm();
                    } else if (typeof onCancel === 'function') {
                        onCancel();
                    }
                });
            };

            $('#btnMdlAccionOk').on('click.accOk', function() { cerrar(true); });
            $('#btnMdlAccionCancel').on('click.accOk', function() { cerrar(false); });
            $overlay.off('click.accOkBg').on('click.accOkBg', function(e) {
                if (e.target === this) {
                    cerrar(false);
                }
            });
        }

        // Reemplaza alert nativo para que salga centrado en esta pagina
        window.alert = function(mensaje) {
            mostrarMensajeCentrado(mensaje);
        };

        const ADQ_ADJ_MAX_BYTES = 10 * 1024 * 1024;

        function adqFormatFileSize(bytes) {
            if (!bytes || bytes <= 0) { return '0 KB'; }
            if (bytes < 1024 * 1024) { return (bytes / 1024).toFixed(1) + ' KB'; }
            return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        }

        function adqIconoPorExtension(nombre) {
            const ext = String(nombre || '').split('.').pop().toLowerCase();
            if (ext === 'pdf') { return { icon: 'bi-file-earmark-pdf-fill', color: '#dc2626' }; }
            if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].indexOf(ext) >= 0) { return { icon: 'bi-file-earmark-image-fill', color: '#0ea5e9' }; }
            if (['doc', 'docx'].indexOf(ext) >= 0) { return { icon: 'bi-file-earmark-word-fill', color: '#2563eb' }; }
            if (['xls', 'xlsx', 'csv'].indexOf(ext) >= 0) { return { icon: 'bi-file-earmark-excel-fill', color: '#16a34a' }; }
            return { icon: 'bi-file-earmark-fill', color: '#64748b' };
        }

        let actionAdjuntosSeleccionados = [];

        function sincronizarInputSustentos() {
            const input = document.getElementById('actionAdjunto');
            if (!input) { return; }
            const dt = new DataTransfer();
            actionAdjuntosSeleccionados.forEach(function(file) {
                dt.items.add(file);
            });
            input.files = dt.files;
        }

        function mostrarSustentosSeleccionados() {
            const $lista = $('#adqDropzoneFile').empty();
            if (!actionAdjuntosSeleccionados.length) {
                $lista.hide();
                $('#adqDropzoneEmpty').show();
                return;
            }
            $lista.append('<div class="adq-selected-files-summary">' + actionAdjuntosSeleccionados.length + ' archivo(s) seleccionado(s)</div>');
            actionAdjuntosSeleccionados.forEach(function(file, index) {
                const info = adqIconoPorExtension(file.name);
                const nombre = $('<div>').text(file.name).html();
                $lista.append(
                    '<div class="adq-selected-file">' +
                        '<i class="bi adq-file-icon ' + info.icon + '" style="color:' + info.color + '"></i>' +
                        '<div class="adq-file-info">' +
                            '<div class="adq-file-name">' + nombre + '</div>' +
                            '<div class="adq-file-size">' + adqFormatFileSize(file.size) + '</div>' +
                        '</div>' +
                        '<button type="button" class="adq-file-remove" title="Quitar archivo" onclick="event.stopPropagation(); quitarSustentoAdjunto(' + index + ');"><i class="bi bi-x-lg"></i></button>' +
                    '</div>'
                );
            });
            $('#adqDropzoneEmpty').hide();
            $lista.show();
        }

        function quitarSustentoAdjunto(indice) {
            if (typeof indice === 'number') {
                actionAdjuntosSeleccionados.splice(indice, 1);
            } else {
                actionAdjuntosSeleccionados = [];
            }
            sincronizarInputSustentos();
            mostrarSustentosSeleccionados();
            $('#adqDropzone').removeClass('adq-dropzone-invalid');
            actualizarEstadoBotonesResolucion();
        }

        function procesarSustentoArchivos(files) {
            const nuevos = Array.from(files || []);
            let excedidos = [];
            nuevos.forEach(function(file) {
                if (file.size > ADQ_ADJ_MAX_BYTES) {
                    excedidos.push(file.name);
                    return;
                }
                const repetido = actionAdjuntosSeleccionados.some(function(actual) {
                    return actual.name === file.name && actual.size === file.size && actual.lastModified === file.lastModified;
                });
                if (!repetido) {
                    actionAdjuntosSeleccionados.push(file);
                }
            });
            sincronizarInputSustentos();
            mostrarSustentosSeleccionados();
            $('#adqDropzone').toggleClass('adq-dropzone-invalid', excedidos.length > 0);
            if (excedidos.length) {
                alert('Estos archivos superan el limite de 10 MB y no fueron agregados: ' + excedidos.join(', '));
            }
            actualizarEstadoBotonesResolucion();
        }

        $(document).on('input change', '#actionComentario', function() {
            actualizarEstadoBotonesResolucion();
        });

        $(document).on('change', '#actionAdjunto', function() {
            if (this.files && this.files.length > 0) {
                procesarSustentoArchivos(this.files);
            }
        });

        $(function() {
            const dz = document.getElementById('adqDropzone');
            if (!dz) { return; }
            ['dragenter', 'dragover'].forEach(function(evt) {
                dz.addEventListener(evt, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dz.classList.add('adq-dragover');
                });
            });
            ['dragleave', 'drop'].forEach(function(evt) {
                dz.addEventListener(evt, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dz.classList.remove('adq-dragover');
                });
            });
            dz.addEventListener('drop', function(e) {
                const dt = e.dataTransfer;
                if (dt && dt.files && dt.files.length > 0) {
                    procesarSustentoArchivos(dt.files);
                }
            });
        });

        function enviarAccion(accion) {
            if (currentNodTip === 'FIN' && accion === 'APROBAR') {
                if (!currentExpedienteEstado || parseInt(currentExpedienteEstado.tiene_pdf, 10) !== 1) {
                    alert('Debe descargar el expediente, revisarlo y cargarlo nuevamente antes de finalizar.');
                    return;
                }
            }
            // AVANCE / FISCALIZACION: Guardar no avanza; Aprobar/Finalizar exige justificacion + comentario.
            if (accion === 'APROBAR' && (currentNodTip === 'AVANCE' || currentNodTip === 'FISCALIZACION')) {
                if (tieneAvancePendienteGuardar()) {
                    guardarAvanceDocs(function() {
                        enviarAccion('APROBAR');
                    });
                    return;
                }
                if (currentNodTip === 'FISCALIZACION' && contarDocsFiscalGuardados() <= 0) {
                    alert('Debe guardar al menos un archivo, factura o anticipo de fiscalizacion antes de aprobar.');
                    return;
                }
                if (currentNodTip === 'AVANCE' && contarFacturasAvanceGuardadas() <= 0) {
                    alert('Debe registrar al menos una factura o anticipo antes de finalizar el proceso de avance.');
                    return;
                }
                if (currentNodTip === 'AVANCE') {
                    const checkTot = validarTotalesAvanceParaFinalizar();
                    if (!checkTot.ok) {
                        alert(checkTot.message);
                        return;
                    }
                }
                if (!$('#actionComentario').val().trim()) {
                    alert(currentNodTip === 'FISCALIZACION'
                        ? 'Debe ingresar el comentario/justificacion antes de aprobar la fiscalizacion.'
                        : 'Debe ingresar el comentario/justificacion antes de finalizar el avance.');
                    $('#actionComentario').focus();
                    return;
                }
            }
            if (accion === 'APROBAR' || accion === 'COMPLETAR') {
                const motivos = motivosBloqueoAprobacion();
                if (motivos.length) {
                    alert('No puede continuar. Falta: ' + motivos.join(', ') + '.');
                    return;
                }
                if (isComObl && !$('#actionComentario').val().trim()) {
                    alert(accion === 'COMPLETAR'
                        ? 'El comentario es obligatorio para completar esta tarea.'
                        : 'El comentario es obligatorio para aprobar en esta etapa.');
                    return;
                }
                if (isAdjObl && actionAdjuntosSeleccionados.length === 0) {
                    alert('Cargar un archivo adjunto de soporte es obligatorio en esta etapa.');
                    return;
                }
                if (isCotSelObl && !hasCotGanadora) {
                    alert('Debe seleccionar la cotizacion ganadora antes de aprobar esta etapa.');
                    return;
                }
                if (isCotEditObl && !hasCotMinimas) {
                    alert('Debe completar las cotizaciones minimas requeridas antes de aprobar esta etapa.');
                    return;
                }
            }
            if (accion === 'RECHAZAR') {
                if (!$('#actionComentario').val().trim()) {
                    alert('Debe indicar el motivo del rechazo en el comentario.');
                    $('#actionComentario').focus();
                    return;
                }
                confirmarCentrado({
                    titulo: 'Confirmar rechazo',
                    mensaje: 'Esta accion no se puede revertir.\n\nEste proceso quedara suspendido permanentemente.',
                    tipo: 'danger',
                    okText: 'Si, rechazar',
                    cancelText: 'Cancelar'
                }, function() {
                    ejecutarAccionWorkflow(accion);
                });
                return;
            }
            if (accion === 'DEVOLVER') {
                if (!$('#actionComentario').val().trim()) {
                    alert('Debe indicar el motivo de la devolucion en el comentario.');
                    $('#actionComentario').focus();
                    return;
                }
                confirmarCentrado({
                    titulo: 'Confirmar devolucion',
                    mensaje: 'La solicitud regresara al proceso anterior del flujo.\n\nSe activara para los responsables de ese proceso (no para el solicitante).',
                    tipo: 'warning',
                    okText: 'Si, devolver',
                    cancelText: 'Cancelar'
                }, function() {
                    ejecutarAccionWorkflow(accion);
                });
                return;
            }

            ejecutarAccionWorkflow(accion);
        }

        function ejecutarAccionWorkflow(accion) {
            $('#actionName').val(accion);
            const formData = new FormData($('#frmWorkflowAction')[0]);
            const textosLoader = {
                APROBAR: { titulo: 'Finalizando...', detalle: 'Registrando la aprobacion. Espere un momento.' },
                COMPLETAR: { titulo: 'Completando tarea...', detalle: 'Registrando la tarea. Espere un momento.' },
                OBSERVAR: { titulo: 'Registrando observacion...', detalle: 'Guardando la observacion. Espere un momento.' },
                DEVOLVER: { titulo: 'Devolviendo...', detalle: 'Registrando la devolucion. Espere un momento.' },
                RECHAZAR: { titulo: 'Rechazando...', detalle: 'Registrando el rechazo. Espere un momento.' }
            };
            const loaderTxt = textosLoader[accion] || { titulo: 'Procesando...', detalle: 'Registrando la tarea. Espere un momento.' };
            if (accion === 'APROBAR' && (currentNodTip === 'AVANCE' || currentNodTip === 'FIN')) {
                loaderTxt.titulo = 'Finalizando...';
                loaderTxt.detalle = 'Cerrando la etapa. Espere un momento.';
            }
            adqMostrarLoaderAccion(loaderTxt.titulo, loaderTxt.detalle);

            $.ajax({
                url: 'adq_bandeja.php?ajax_workflow_action=1',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        cerrarResolucion();
                        const msgOk = (accion === 'APROBAR')
                            ? 'Accion aprobada correctamente'
                            : (accion === 'RECHAZAR')
                                ? 'Solicitud rechazada correctamente'
                                : (accion === 'OBSERVAR')
                                    ? 'Observacion registrada correctamente'
                                    : (accion === 'DEVOLVER')
                                        ? (res.message || 'Solicitud devuelta al proceso anterior. Queda activa para sus responsables.')
                                        : (accion === 'COMPLETAR')
                                            ? 'Tarea completada correctamente'
                                            : ('Accion "' + accion + '" procesada correctamente');
                        adqOcultarLoaderAccion();
                        mostrarMensajeCentrado(msgOk, function() {
                            window.location.reload();
                        }, accion === 'RECHAZAR' ? 'warning' : 'success');
                    } else {
                        adqOcultarLoaderAccion();
                        alert('Error al procesar accion: ' + res.message);
                    }
                },
                error: function() {
                    adqOcultarLoaderAccion();
                    alert('Error critico de red al comunicarse con el servidor.');
                }
            });
        }

        

        function initSeguimientoNodoInteraccion() {
            const $body = $('#seguimientoModalBody');
            let htmlMap = {};
            let metaMap = {};
            const $htmlData = $body.find('#segHistorialNodoHtml');
            const $metaData = $body.find('#segNodosMeta');
            if ($htmlData.length) {
                try { htmlMap = JSON.parse($htmlData.text()); } catch (e) { htmlMap = {}; }
            }
            if ($metaData.length) {
                try { metaMap = JSON.parse($metaData.text()); } catch (e) { metaMap = {}; }
            }

            function mostrarTareasNodo(nodId, $nodeEl) {
                nodId = String(nodId);
                $body.find('.tracker-node-clickable').removeClass('tracker-node-selected');
                if ($nodeEl && $nodeEl.length) {
                    $nodeEl.addClass('tracker-node-selected');
                }
                const meta = metaMap[nodId] || {};
                const nom = meta.nombre || ($nodeEl ? $nodeEl.data('nod-nom') : '') || 'Etapa';
                const tip = meta.tipo || ($nodeEl ? $nodeEl.data('nod-tip') : '') || '';
                const orden = meta.orden ? parseInt(meta.orden, 10) : 0;
                const itemsHtml = htmlMap[nodId] || '<div class="text-center text-muted py-3 small">No hay tareas registradas en esta etapa.</div>';
                $('#segNodoTareasTitulo').text((orden > 0 ? (orden + '. ') : '') + nom);
                $('#segNodoTareasSub').text(tip ? (' [' + tip + ']') : '');
                $('#segNodoTareasBody').html(itemsHtml);

                const $nodoModal = $('#mdlSegNodoDetalle');
                $nodoModal.off('shown.bs.modal.segNodo hidden.bs.modal.segNodo');
                $nodoModal.on('shown.bs.modal.segNodo', function() {
                    $('.modal-backdrop').not('.adq-seg-nodo-backdrop').last().addClass('adq-seg-nodo-backdrop');
                });
                $nodoModal.on('hidden.bs.modal.segNodo', function() {
                    $body.find('.tracker-node-clickable').removeClass('tracker-node-selected');
                    $('body').addClass('modal-open');
                });
                $nodoModal.modal('show');
            }

            $body.find('.adq-seg-flow-tracker .tracker-node-clickable').off('click.segNodo').on('click.segNodo', function() {
                mostrarTareasNodo($(this).data('nod-id'), $(this));
            });

            $('#btnSegNodoTareasCerrar').off('click.segNodo').on('click.segNodo', function() {
                $('#mdlSegNodoDetalle').modal('hide');
            });
        }

        function abrirSeguimientoDetallado() {
            if (!currentSolCod) return;
            $('#mdlSegNodoDetalle').modal('hide');
            $('#lblSeguimientoTitle').text('Seguimiento de Requerimiento #' + currentSolCod);
            const trackerPreview = buildSeguimientoTrackerPreview();
            $('#seguimientoModalBody').html(
                trackerPreview
                + '<div class="text-center p-4"><i class="glyphicon glyphicon-refresh glyphicon-spin" style="font-size:24px;"></i><div class="mt-2">Cargando seguimiento...</div></div>'
            );

            $('#mdlSeguimiento').modal('show');

            $.ajax({
                url: 'adq_seguimiento.php',
                data: { sol_cod: currentSolCod },
                dataType: 'html',
                cache: false,
                success: function(html) {
                    if (html.indexOf('Acceso denegado') !== -1) {
                        if (trackerPreview) {
                            $('#seguimientoModalBody').html(
                                trackerPreview
                                + '<div class="alert alert-warning m-2">No se pudo cargar el detalle completo del seguimiento, pero puede ver el grafico del flujo arriba.</div>'
                            );
                        } else {
                            $('#seguimientoModalBody').html(html);
                        }
                        return;
                    }
                    $('#seguimientoModalBody').html(html);
                    initSeguimientoNodoInteraccion();
                },
                error: function() {
                    if (trackerPreview) {
                        $('#seguimientoModalBody').html(
                            trackerPreview
                            + '<div class="alert alert-warning m-2">No se pudo cargar el detalle del seguimiento, pero puede ver el grafico del flujo arriba.</div>'
                        );
                    } else {
                        $('#seguimientoModalBody').html('<div class="alert alert-danger m-2">No se pudo cargar el seguimiento.</div>');
                    }
                }
            });
        }

        function volverAResolucion() {
            $('#mdlSegNodoDetalle').modal('hide');
            $('#mdlSeguimiento').modal('hide');
            mostrarResolucionView();
        }

        let formLoaded = false;
        let formSolCod = null;
        let formLoadRequestId = 0;
        let formModo = 'borrador';

        function bootFormularioSolicitud(targetSol, callback) {
            if (typeof initAdqSolicitudForm === 'function') {
                initAdqSolicitudForm();
            }
            if (targetSol) {
                if (formModo === 'cotizaciones' && typeof cargarSolicitudParaCotizaciones === 'function') {
                    cargarSolicitudParaCotizaciones(targetSol);
                } else if (formModo === 'completar_nodo' && typeof cargarSolicitudParaCompletar === 'function') {
                    cargarSolicitudParaCompletar(targetSol);
                } else if (typeof cargarBorradorEnFormulario === 'function') {
                    cargarBorradorEnFormulario(targetSol);
                }
            }
            formModo = 'borrador';
            if (callback) {
                callback();
            }
        }

        function cargarFormularioCreacion(solCod, callback) {
            const targetSol = (solCod !== undefined && solCod !== null && solCod !== '') ? parseInt(solCod, 10) : null;
            const requestId = ++formLoadRequestId;

            if (formLoaded && formSolCod === targetSol && $('#frmSolicitud').length) {
                bootFormularioSolicitud(targetSol, callback);
                return;
            }

            $('#create-panel-content').data('sol-cod', targetSol || '');
            $.get('adq_solicitud.php', { ajax_get_form: 1, modo: targetSol ? 'completar' : 'corto', sol_cod: targetSol || '' }, function(html) {
                if (requestId !== formLoadRequestId) {
                    return;
                }
                html = html.replace(/<script[^>]*cedulaRuc\.js[^>]*>\s*<\/script>/gi, '');
                html = html.replace(/<script[^>]*adq_solicitud\.js[^>]*>\s*<\/script>/gi, '');
                $('#create-panel-content').html(html);
                formLoaded = true;
                formSolCod = targetSol;

                if (typeof initAdqSolicitudForm === 'function' && typeof validaNoIdentif === 'function') {
                    bootFormularioSolicitud(targetSol, callback);
                } else {
                    $.getScript('../../framework/plugins/cedulaRuc.js')
                        .done(function() {
                            return $.getScript('../VALIDACIONES/adq_solicitud.js?v=20260728a');
                        })
                        .done(function() {
                            if (requestId !== formLoadRequestId) {
                                return;
                            }
                            bootFormularioSolicitud(targetSol, callback);
                        })
                        .fail(function() {
                            alert('Error al cargar la logica del formulario de solicitud.');
                        });
                }
            }).fail(function(xhr, status, error) {
                alert('Error al cargar el formulario de creacion: ' + error + ' (Status: ' + xhr.status + ')');
            });
        }

        function abrirEdicionBorrador(solCod) {
            formLoaded = false;
            formSolCod = null;
            formModo = 'borrador';
            const targetSol = parseInt(solCod, 10);
            const $tab = $('a[href="#create-panel"]');
            if ($tab.parent().hasClass('active')) {
                cargarFormularioCreacion(targetSol);
            } else {
                $('#create-panel-content').data('pending-sol-cod', targetSol);
                $tab.tab('show');
            }
        }

        function abrirCompletarSolicitud(solCod) {
            formLoaded = false;
            formSolCod = null;
            formModo = 'completar_nodo';
            const targetSol = parseInt(solCod, 10);
            const $tab = $('a[href="#create-panel"]');
            if ($tab.parent().hasClass('active') || $('#create-panel').hasClass('active')) {
                cargarFormularioCreacion(targetSol);
            } else {
                $('#create-panel-content').data('pending-sol-cod', targetSol);
                $tab.tab('show');
            }
        }

        function volverAMisPendientes() {
            formModo = 'borrador';
            formLoaded = false;
            formSolCod = null;
            if (typeof setModoEdicionFormulario === 'function') {
                setModoEdicionFormulario('', null, null);
            }
            // Vaciar el panel sin llamar a limpiarFormulario() (ese pide confirmacion).
            $('#create-panel-content').empty().removeData('sol-cod').removeData('pending-sol-cod');
            const $tab = $('a[href="#pending-panel"], #pending-tab');
            if ($tab.length) {
                $tab.tab('show');
            }
            setTimeout(function() {
                const el = document.getElementById('pending-panel');
                if (el && el.scrollIntoView) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                $('.exa-body').scrollTop(0);
                $(window).scrollTop(0);
            }, 100);
        }

        function abrirEdicionCotizaciones(solCod) {
            const targetSol = parseInt(solCod, 10);
            if (!targetSol) {
                alert('No se identifico la solicitud para cargar cotizaciones.');
                return;
            }

            // Salir de la vista de detalle/resolucion para mostrar el formulario
            if ($('#mdlSeguimiento').length && ($('#mdlSeguimiento').hasClass('in') || $('#mdlSeguimiento').is(':visible'))) {
                $('#mdlSeguimiento').modal('hide');
            }
            cerrarResolucion();

            formLoaded = false;
            formSolCod = null;
            formModo = 'cotizaciones';

            const $tab = $('a[href="#create-panel"]');
            const tabActivo = $tab.parent().hasClass('active') || $('#create-panel').hasClass('active');
            if (tabActivo) {
                cargarFormularioCreacion(targetSol);
            } else {
                $('#create-panel-content').data('pending-sol-cod', targetSol);
                $tab.tab('show');
            }

            // Llevar el scroll al formulario visible
            setTimeout(function() {
                const el = document.getElementById('create-panel');
                if (el && el.scrollIntoView) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                $('.exa-body').scrollTop(0);
                $(window).scrollTop(0);
            }, 150);
        }

        function activarTabCrear() {
            const urlParams = new URLSearchParams(window.location.search);
            const solCod = urlParams.get('sol_cod');
            if (solCod) {
                $('#create-panel-content').data('pending-sol-cod', parseInt(solCod, 10));
            }
            $('a[href="#create-panel"]').tab('show');
        }

        function aplicarFiltroFlujo() {
            const fam = $('#filtroFlujo').val();
            $('.adq-row-solicitud').each(function() {
                const rowFam = String($(this).data('wfm-fam') || '');
                const match = !fam || rowFam === fam;
                $(this).toggleClass('adq-filtro-oculto', !match);
            });
            const params = new URLSearchParams(window.location.search);
            if (fam) {
                params.set('filtro_wfm', fam);
            } else {
                params.delete('filtro_wfm');
            }
            const qs = params.toString();
            const nuevaUrl = window.location.pathname + (qs ? '?' + qs : '') + window.location.hash;
            window.history.replaceState({}, '', nuevaUrl);
            inicializarPaginacionTablas(1);
        }

        function getVisibleRowsTabla($panel) {
            return $panel.find('tbody tr.adq-row-solicitud').not('.adq-filtro-oculto');
        }

        function renderPagerTabla($panel, page, pages, total, pageSize) {
            const $pager = $panel.find('.adq-table-pager');
            if (!$pager.length) {
                return;
            }
            if (total <= 0) {
                $pager.html('<div class="adq-table-pager-info">Sin registros para mostrar</div>');
                return;
            }
            const from = ((page - 1) * pageSize) + 1;
            const to = Math.min(page * pageSize, total);
            let pagesHtml = '';
            const maxBtns = 5;
            let start = Math.max(1, page - Math.floor(maxBtns / 2));
            let end = Math.min(pages, start + maxBtns - 1);
            if (end - start < maxBtns - 1) {
                start = Math.max(1, end - maxBtns + 1);
            }
            for (let i = start; i <= end; i++) {
                pagesHtml += '<button type="button" class="btn' + (i === page ? ' active' : '') + '" data-page="' + i + '">' + i + '</button>';
            }
            $pager.html(
                '<div class="adq-table-pager-info">Mostrando ' + from + '-' + to + ' de ' + total + '</div>'
                + '<div class="adq-table-pager-controls">'
                + '<label class="adq-table-pager-size">Filas '
                + '<select class="adq-table-page-size">'
                + '<option value="10"' + (pageSize === 10 ? ' selected' : '') + '>10</option>'
                + '<option value="20"' + (pageSize === 20 ? ' selected' : '') + '>20</option>'
                + '<option value="25"' + (pageSize === 25 ? ' selected' : '') + '>25</option>'
                + '<option value="50"' + (pageSize === 50 ? ' selected' : '') + '>50</option>'
                + '</select></label>'
                + '<div class="adq-table-pager-pages">'
                + '<button type="button" class="btn" data-page="prev" title="Anterior"' + (page <= 1 ? ' disabled' : '') + '><i class="bi bi-chevron-left"></i></button>'
                + pagesHtml
                + '<button type="button" class="btn" data-page="next" title="Siguiente"' + (page >= pages ? ' disabled' : '') + '><i class="bi bi-chevron-right"></i></button>'
                + '</div></div>'
            );
        }

        function paginarTablaPanel($panel, pageForce) {
            if (!$panel || !$panel.length) {
                return;
            }
            let pageSize = parseInt($panel.attr('data-page-size'), 10) || 20;
            if (pageSize < 1) {
                pageSize = 20;
            }
            const $allRows = $panel.find('tbody tr.adq-row-solicitud');
            const $emptyRows = $panel.find('tbody tr').not('.adq-row-solicitud');
            const $visible = getVisibleRowsTabla($panel);
            const total = $visible.length;
            const pages = Math.max(1, Math.ceil(total / pageSize) || 1);
            let page = pageForce != null ? parseInt(pageForce, 10) : (parseInt($panel.data('page'), 10) || 1);
            if (isNaN(page) || page < 1) {
                page = 1;
            }
            if (page > pages) {
                page = pages;
            }
            $panel.data('page', page);

            $allRows.hide();
            if (total > 0) {
                $emptyRows.hide();
                const start = (page - 1) * pageSize;
                $visible.slice(start, start + pageSize).show();
            } else {
                $emptyRows.show();
            }
            renderPagerTabla($panel, page, pages, total, pageSize);
        }

        function inicializarPaginacionTablas(pageForce) {
            $('.adq-table-panel').each(function() {
                paginarTablaPanel($(this), pageForce);
            });
        }

        function actualizarVisibilidadFiltroFlujo() {
            const enCrear = $('#create-panel').hasClass('active');
            $('#adqBandjFiltersFlujo').toggle(!enCrear);
        }

        $(document).ready(function() {
            $('a[data-toggle="tab"]').on('shown.bs.tab', function() {
                actualizarVisibilidadFiltroFlujo();
                inicializarPaginacionTablas();
            });
            actualizarVisibilidadFiltroFlujo();

            $(document).on('click', '.adq-table-pager [data-page]', function() {
                const $btn = $(this);
                if ($btn.is(':disabled')) {
                    return;
                }
                const $panel = $btn.closest('.adq-table-panel');
                let page = parseInt($panel.data('page'), 10) || 1;
                const pages = Math.max(1, Math.ceil(getVisibleRowsTabla($panel).length / (parseInt($panel.attr('data-page-size'), 10) || 20)));
                const accion = String($btn.data('page'));
                if (accion === 'prev') {
                    page -= 1;
                } else if (accion === 'next') {
                    page += 1;
                } else {
                    page = parseInt(accion, 10) || 1;
                }
                page = Math.min(Math.max(1, page), pages);
                paginarTablaPanel($panel, page);
            });

            $(document).on('change', '.adq-table-pager .adq-table-page-size', function() {
                const $panel = $(this).closest('.adq-table-panel');
                const size = parseInt($(this).val(), 10) || 20;
                $panel.attr('data-page-size', size);
                paginarTablaPanel($panel, 1);
            });

            $('#filtroFlujo').on('change', aplicarFiltroFlujo);
            if ($('#filtroFlujo').val()) {
                aplicarFiltroFlujo();
            } else {
                inicializarPaginacionTablas(1);
            }

            $('a[data-toggle="tab"][href="#create-panel"]').on('shown.bs.tab', function() {
                const pendingSol = $('#create-panel-content').data('pending-sol-cod');
                $('#create-panel-content').removeData('pending-sol-cod');
                const targetSol = pendingSol ? parseInt(pendingSol, 10) : null;
                if (!targetSol) {
                    formModo = 'borrador';
                }
                cargarFormularioCreacion(targetSol);
            });

            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            const modo = urlParams.get('modo');
            if (tab === 'crear_solicitud') {
                if (modo === 'cotizaciones') {
                    formModo = 'cotizaciones';
                }
                activarTabCrear();
            } else if (tab === 'mis_solicitudes') {
                $('a[href="#my-panel"]').tab('show');
            }
            const detalleSol = urlParams.get('detalle_sol');
            if (detalleSol) {
                abrirResolucion(parseInt(detalleSol, 10), false);
            }
        });
    </script>
    <div id="adqLoaderAccion" aria-live="polite" aria-busy="false" style="display:none;position:fixed;inset:0;z-index:4000;background:rgba(15,23,42,0.55);align-items:center;justify-content:center;padding:20px;">
        <div style="background:#ffffff;border-radius:12px;padding:28px 36px;text-align:center;box-shadow:0 20px 40px rgba(15,23,42,0.25);min-width:260px;max-width:360px;">
            <div class="spinner-border text-primary" role="status" style="width:2.6rem;height:2.6rem;"></div>
            <div id="adqLoaderAccionTitulo" style="margin-top:16px;font-size:16px;font-weight:700;color:#0f172a;">Procesando...</div>
            <div id="adqLoaderAccionDetalle" style="margin-top:6px;font-size:13px;color:#64748b;">Por favor espere mientras se registra la tarea.</div>
        </div>
    </div>
</body>
</html>
