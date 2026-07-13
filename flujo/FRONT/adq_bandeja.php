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
$wf_mgr->ensureVersioningSchema();
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

$ajax_workflow_action = isset($_GET['ajax_workflow_action']) ? $_GET['ajax_workflow_action'] : (isset($_POST['ajax_workflow_action']) ? $_POST['ajax_workflow_action'] : null);
$ajax_enviar_borrador = isset($_POST['ajax_enviar_borrador']) ? $_POST['ajax_enviar_borrador'] : null;
$ajax_reenviar_observada = isset($_POST['ajax_reenviar_observada']) ? $_POST['ajax_reenviar_observada'] : null;
$ajax_buscar_compras = isset($_GET['ajax_buscar_compras']) ? $_GET['ajax_buscar_compras'] : null;
$ajax_vincular_compra = isset($_GET['ajax_vincular_compra']) ? $_GET['ajax_vincular_compra'] : (isset($_POST['ajax_vincular_compra']) ? $_POST['ajax_vincular_compra'] : null);
$ajax_desvincular_compra = isset($_POST['ajax_desvincular_compra']) ? $_POST['ajax_desvincular_compra'] : null;
$ajax_get_solicitud_detail = isset($_GET['ajax_get_solicitud_detail']) ? $_GET['ajax_get_solicitud_detail'] : null;
$ajax_save_avance_docs = isset($_POST['ajax_save_avance_docs']) ? $_POST['ajax_save_avance_docs'] : null;
$ajax_get_compra_avance = isset($_GET['ajax_get_compra_avance']) ? $_GET['ajax_get_compra_avance'] : null;
$ajax_descargar_expediente = isset($_GET['ajax_descargar_expediente']) ? $_GET['ajax_descargar_expediente'] : null;
$ajax_subir_expediente = isset($_POST['ajax_subir_expediente']) ? $_POST['ajax_subir_expediente'] : null;
$ajax_firmar_expediente = isset($_POST['ajax_firmar_expediente']) ? $_POST['ajax_firmar_expediente'] : null;

// Verificar acceso a la ventana 'bandeja'
if (!$wf_mgr->verificarAccesoVentana('bandeja')) {
    if (isset($ajax_workflow_action) || isset($ajax_buscar_compras) || isset($ajax_vincular_compra) || isset($ajax_desvincular_compra) || isset($ajax_get_solicitud_detail) || isset($ajax_enviar_borrador) || isset($ajax_reenviar_observada) || isset($ajax_save_avance_docs) || isset($ajax_get_compra_avance) || isset($ajax_descargar_expediente) || isset($ajax_subir_expediente) || isset($ajax_firmar_expediente)) {
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
    
    // Carga de adjunto opcional
    $adjunto_db_path = null;
    if (isset($_FILES['adjunto']) && $_FILES['adjunto']['error'] == 0) {
        $target_dir = "../../DATA/adquisiciones_sustentos/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $name = $_FILES['adjunto']['name'];
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $unique_name = "action_" . uniqid() . "." . $ext;
        if (move_uploaded_file($_FILES['adjunto']['tmp_name'], $target_dir . $unique_name)) {
            $adjunto_db_path = "adquisiciones_sustentos/" . $unique_name;
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
                'Sav_Cop_Cod' => isset($doc['Sav_Cop_Cod']) ? intval($doc['Sav_Cop_Cod']) : 0
            );
        }
    }

    $mapa_campos = array(
        'avance_factura_nuevos' => 'Sav_Fac_Adj',
        'avance_retencion_nuevos' => 'Sav_Ret_Adj',
        'avance_comprobante_nuevos' => 'Sav_Com_Adj'
    );

    $target_dir = "../../DATA/adquisiciones_sustentos/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

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
                        $docs_nuevos[$idx][$db_field] = "adquisiciones_sustentos/" . $unique_name;
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
                        $docs_existentes[$sav_cod][$db_field] = "adquisiciones_sustentos/" . $unique_name;
                    }
                }
            }
        }
    }

    $docs_nuevos = array_values($docs_nuevos);
    $resp = $obBD_adq->guardarAvanceEtapa($sol_cod, $docs_nuevos, $docs_existentes, $sav_eliminar);
    if (!empty($resp['success'])) {
        $auth = $obBD_adq->autorizarAvanceEtapa($sol_cod, intval($Ses_Emp_Cod), intval($wf_ctx['usu_cod']));
        if (!empty($auth['success'])) {
            $avances = $obBD_adq->listarAvancesSolicitud($sol_cod, intval($auth['Ins_Cod']), intval($auth['Nod_Cod']));
            $resp['avances'] = $obBD_adq->enriquecerAvancesConCompras($avances, intval($Ses_Emp_Cod));

            $ins_cod_hist = intval($auth['Ins_Cod']);
            $inst = $obBD_con1->getRowConsultaSql(
                "SELECT Ins_Est, Nod_Act FROM wf_instancias WHERE Ins_Cod = $ins_cod_hist LIMIT 1;",
                $obBD_conexion
            );
            $historial = $obBD_con1->getArrayConsultaSql("
                SELECT h.*,
                       COALESCE(n.Nod_Nom, CONCAT('Nodo #', h.Nod_Cod)) AS Nod_Nom,
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
                LEFT JOIN wf_departamentos d ON d.Dep_Cod = h.Dep_Cod
                WHERE h.Ins_Cod = $ins_cod_hist
                ORDER BY h.Isn_Fec DESC, h.Isn_Cod DESC;", $obBD_conexion);
            if ($historial === false || $historial === null) {
                $historial = array();
            }
            $puede_resolver = $wf_mgr->puedeUsuarioResolverSolicitud(
                array('Ins_Cod' => $ins_cod_hist, 'Ins_Est' => $inst['Ins_Est'], 'Sol_Est' => 'P', 'Usu_Sol' => 0),
                $wf_ctx['usu_cod'],
                $wf_ctx['dep_cod'],
                $wf_ctx['perfiles_ids'],
                ($wf_ctx['usu_cod'] == 1) || (isset($_SESSION['Ses_Lis_Per']) && count(array_intersect(array(1, 2), $_SESSION['Ses_Lis_Per'])) > 0)
            );
            $historial = $wf_mgr->normalizarHistorialFirmas(
                $historial,
                isset($inst['Ins_Est']) ? $inst['Ins_Est'] : '',
                isset($inst['Nod_Act']) ? intval($inst['Nod_Act']) : 0
            );
            $historial = $wf_mgr->agregarNodoPendienteHistorial(
                $historial,
                isset($inst['Ins_Est']) ? $inst['Ins_Est'] : '',
                isset($inst['Nod_Act']) ? intval($inst['Nod_Act']) : 0,
                $puede_resolver ? intval($wf_ctx['usu_cod']) : 0
            );
            $historial = $wf_mgr->agregarRechazoHistorialSiFalta(
                $historial,
                $ins_cod_hist,
                '',
                isset($inst['Ins_Est']) ? $inst['Ins_Est'] : ''
            );
            $historial = $obBD_adq->enriquecerHistorialConArchivos($historial, $sol_cod);
            adq_preparar_historial_json_utf8($historial);
            $resp['historial'] = $historial;
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
        $path_abs = dirname(__FILE__) . '/../../DATA/' . ltrim($estado['firmado'], '/');
        if (!is_file($path_abs)) {
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
        $path_abs = dirname(__FILE__) . '/../../DATA/' . ltrim($estado['pdf'], '/');
        if (!is_file($path_abs)) {
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
        $tmp_dir = dirname(__FILE__) . '/../../DATA/adquisiciones_sustentos/tmp_llaves/';
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
               n.Nod_Nom, n.Nod_Tip, n.Nod_Com_Obl, n.Nod_Adj_Obl, IFNULL(n.Nod_Cot_Edit, 0) AS Nod_Cot_Edit
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
    $sol['Puede_Cargar_Avance'] = $wf_mgr->puedeUsuarioCargarAvance(
        $sol,
        $wf_ctx['usu_cod'],
        $wf_ctx['dep_cod'],
        $wf_ctx['perfiles_ids']
    ) ? 1 : 0;

    $avances = array();
    if (!empty($sol['Ins_Cod']) && !empty($sol['Nod_Act']) && $sol['Nod_Tip'] === 'AVANCE') {
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
                   COALESCE(n.Nod_Nom, CONCAT('Nodo #', h.Nod_Cod)) AS Nod_Nom,
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
            LEFT JOIN wf_departamentos d ON d.Dep_Cod = h.Dep_Cod
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

$wf_mgr->repararInstanciasEnInicio('adq_solicitudes');

$es_gerencial_admin = ($usu_cod == 1) || (isset($_SESSION['Ses_Lis_Per']) && count(array_intersect(array(1, 2), $_SESSION['Ses_Lis_Per'])) > 0);
$es_gerencial_sql = $es_gerencial_admin ? '1' : '0';
$filtro_pendiente_sin_auto = $wf_mgr->sqlFiltroPendienteSinAutoaprobacion($usu_cod, $es_gerencial_sql);

// A. PENDIENTES DE MI APROBACI?N (Etapa activa asignada a mi depto o mis perfiles)
$pendientes = $obBD_con1->getArrayConsultaSql("
    SELECT s.*, tr.Trq_Des,
           IFNULL(wfm.Wfm_Nom, 'Sin flujo') AS Wfm_Nom,
           COALESCE(wfm.Wfm_Fam_Cod, wfm.Wfm_Cod, tr.Wfm_Cod) AS Wfm_Fam_Cod,
           CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as Solicitante_Nom, d.Dep_Des, i.Ins_Cod, n.Nod_Nom, n.Nod_Sla
    FROM adq_solicitudes s
    INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
    INNER JOIN usuarios u ON u.Usu_Cod = s.Usu_Sol
    INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
    LEFT JOIN departamen d ON d.Dep_Cod = s.Dep_Sol
    INNER JOIN wf_instancias i ON i.Ins_Ent_Typ = 'adq_solicitudes' AND i.Ins_Ent_Cod = s.Sol_Cod AND i.Ins_Est = 'P'
    INNER JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act AND $clausula_nodo_usuario
    LEFT JOIN wf_flujos_modelos wfm ON wfm.Wfm_Cod = COALESCE(i.Wfm_Cod, tr.Wfm_Cod)
    WHERE s.Emp_Cod = $emp_cod AND s.Sol_Est IN ('E', 'P')
      AND n.Nod_Tip NOT IN ('INICIO')
      AND $filtro_pendiente_sin_auto
    ORDER BY s.Sol_Pri DESC, s.Sol_Fec ASC;", $obBD_conexion);
if ($pendientes === false || $pendientes === null) {
    $pendientes = array();
}
foreach ($pendientes as $idx => $p) {
    $pendientes[$idx]['Puede_Cargar_Cotizaciones'] = $wf_mgr->puedeUsuarioCargarCotizaciones(
        array(
            'Ins_Cod' => $p['Ins_Cod'],
            'Ins_Est' => 'P',
            'Sol_Est' => $p['Sol_Est']
        ),
        $wf_ctx['usu_cod'],
        $wf_ctx['dep_cod'],
        $wf_ctx['perfiles_ids']
    ) ? 1 : 0;
    $pendientes[$idx]['Puede_Cargar_Avance'] = $wf_mgr->puedeUsuarioCargarAvance(
        array(
            'Ins_Cod' => $p['Ins_Cod'],
            'Ins_Est' => 'P',
            'Sol_Est' => $p['Sol_Est']
        ),
        $wf_ctx['usu_cod'],
        $wf_ctx['dep_cod'],
        $wf_ctx['perfiles_ids']
    ) ? 1 : 0;
}

// B. MIS SOLICITUDES EN CURSO (Creadas por m?)
$mis_solicitudes = $obBD_con1->getArrayConsultaSql("
    SELECT s.*, tr.Trq_Des,
           IFNULL(wfm.Wfm_Nom, 'Sin flujo') AS Wfm_Nom,
           COALESCE(wfm.Wfm_Fam_Cod, wfm.Wfm_Cod, tr.Wfm_Cod) AS Wfm_Fam_Cod,
           i.Ins_Cod, n.Nod_Nom
    FROM adq_solicitudes s
    INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
    LEFT JOIN wf_instancias i ON i.Ins_Cod = (
        SELECT MAX(i2.Ins_Cod)
        FROM wf_instancias i2
        WHERE i2.Ins_Ent_Typ = 'adq_solicitudes' AND i2.Ins_Ent_Cod = s.Sol_Cod AND i2.Ins_Est = 'P'
    )
    LEFT JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
    LEFT JOIN wf_flujos_modelos wfm ON wfm.Wfm_Cod = COALESCE(i.Wfm_Cod, tr.Wfm_Cod)
    WHERE s.Emp_Cod = $Ses_Emp_Cod AND s.Usu_Sol = $usu_cod AND s.Sol_Est IN ('E', 'O', 'P')
    ORDER BY s.Sol_Fec DESC;", $obBD_conexion);

// C. GESTION? / PARTICIP? (solicitudes de otros en las que actu? en el workflow)
$gestionadas = $obBD_con1->getArrayConsultaSql("
    SELECT s.*, tr.Trq_Des,
           IFNULL(wfm.Wfm_Nom, 'Sin flujo') AS Wfm_Nom,
           COALESCE(wfm.Wfm_Fam_Cod, wfm.Wfm_Cod, tr.Wfm_Cod) AS Wfm_Fam_Cod,
           CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) AS Solicitante_Nom,
           d.Dep_Des,
           i.Ins_Cod, i.Ins_Est AS Ins_Est_Act,
           n.Nod_Nom AS Etapa_Actual,
           h_last.Isn_Acc AS Mi_Accion,
           h_last.Isn_Fec AS Mi_Fecha,
           hn.Nod_Nom AS Mi_Etapa
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
    ) h_last ON h_last.Ins_Cod = i.Ins_Cod
    LEFT JOIN wf_nodos n ON n.Nod_Cod = i.Nod_Act
    LEFT JOIN wf_nodos hn ON hn.Nod_Cod = h_last.Nod_Cod
    WHERE s.Emp_Cod = $emp_cod
    ORDER BY h_last.Isn_Fec DESC
    LIMIT 100;", $obBD_conexion);
if ($gestionadas === false || $gestionadas === null) {
    $gestionadas = array();
}
foreach ($gestionadas as $idx => $g) {
    $gestionadas[$idx]['Puede_Resolver'] = $wf_mgr->puedeUsuarioResolverSolicitud(
        $g,
        $usu_cod,
        $dep_cod,
        $perfiles_ids,
        $es_gerencial_admin
    ) ? 1 : 0;
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
    SELECT s.*, tr.Trq_Des,
           IFNULL(wfm.Wfm_Nom, 'Sin flujo') AS Wfm_Nom,
           COALESCE(wfm.Wfm_Fam_Cod, wfm.Wfm_Cod, tr.Wfm_Cod) AS Wfm_Fam_Cod,
           CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) as Solicitante_Nom, d.Dep_Des
    FROM adq_solicitudes s
    INNER JOIN adq_tipos_requerimientos tr ON tr.Trq_Cod = s.Trq_Cod
    INNER JOIN usuarios u ON u.Usu_Cod = s.Usu_Sol
    INNER JOIN persona p ON p.Prs_Cod = u.Prs_Cod
    LEFT JOIN departamen d ON d.Dep_Cod = s.Dep_Sol
    LEFT JOIN wf_instancias i_hist ON i_hist.Ins_Cod = (
        SELECT MAX(i2.Ins_Cod)
        FROM wf_instancias i2
        WHERE i2.Ins_Ent_Typ = 'adq_solicitudes' AND i2.Ins_Ent_Cod = s.Sol_Cod
    )
    LEFT JOIN wf_flujos_modelos wfm ON wfm.Wfm_Cod = COALESCE(i_hist.Wfm_Cod, tr.Wfm_Cod)
    WHERE s.Emp_Cod = $Ses_Emp_Cod AND s.Sol_Est IN ('A', 'R') $historico_filtro_usuario
    ORDER BY s.Sol_Fec DESC LIMIT 100;", $obBD_conexion);
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
        .adq-bandj-page .exa-ui-nav-tabs > li > a {
            font-size: 14px;
            padding: 11px 20px;
        }
        .adq-bandj-page .exa-ui-nav-tabs > li > a .badge {
            font-size: 12px;
        }
        .adq-bandj-page .exa-adq-table {
            font-size: 14px;
        }
        .adq-bandj-page .exa-adq-table > thead > tr > th {
            font-size: 13px;
            padding: 11px 12px !important;
        }
        .adq-bandj-page .exa-adq-table > tbody > tr > td {
            font-size: 14px;
            padding: 10px 12px !important;
        }
        .adq-bandj-page .exa-adq-table .badge {
            font-size: 12px;
            padding: 4px 8px;
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
            padding: 8px 10px;
            min-width: 38px;
            min-height: 38px;
            line-height: 1;
        }
        .adq-bandj-page .adq-btn-icon-only i {
            font-size: 18px;
            line-height: 1;
        }
        .adq-bandj-page p.text-muted,
        .adq-bandj-page .text-muted.small {
            font-size: 14px !important;
        }
        .adq-bandj-page .exa-adq-table tbody tr.text-muted td,
        .adq-bandj-page .exa-adq-table tbody td.text-muted {
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
        #create-panel-content .adq-cot-pdf-strip {
            display: flex;
            flex-wrap: nowrap;
            align-items: stretch;
            gap: 8px;
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 4px;
        }
        #create-panel-content .adq-cot-pdf-strip .adq-cot-pdf-zone {
            display: flex;
            flex-wrap: nowrap;
            align-items: stretch;
            gap: 8px;
            flex: 0 0 auto;
        }
        #create-panel-content .adq-cot-pdf-strip .adq-cot-pdf-rows {
            display: flex;
            flex-wrap: nowrap;
            gap: 8px;
            flex: 0 0 auto;
        }
        #create-panel-content .adq-cot-pdf-strip .adq-cot-pdf-row {
            flex: 0 0 180px;
            width: 180px;
            min-width: 180px;
            max-width: 180px;
            margin-bottom: 0;
        }
        #create-panel-content .adq-cot-main-row {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 10px 12px;
        }
        #create-panel-content .adq-cot-top-prov {
            flex: 2 1 300px;
            min-width: 240px;
        }
        #create-panel-content .adq-cot-top-val {
            flex: 0 0 130px;
            min-width: 120px;
        }
        #create-panel-content .adq-cot-pdf-strip .adq-btn-add-pdf-cot {
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
            background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
            border: 1px solid #1e40af;
            color: #ffffff;
            box-shadow: 0 1px 4px rgba(37, 99, 235, 0.3);
        }
        #create-panel-content .adq-cot-pdf-strip .adq-btn-add-pdf-cot:hover,
        #create-panel-content .adq-cot-pdf-strip .adq-btn-add-pdf-cot:focus {
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
        .adq-msg-box.is-warning .adq-msg-icon { color: #f59e0b; }
        .adq-msg-box.is-info .adq-msg-icon { color: #0ea5e9; }
        .adq-msg-box.is-success .adq-msg-icon { color: #10b981; }
        .adq-msg-box .adq-msg-text {
            margin: 18px 0 22px;
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.45;
            white-space: pre-wrap;
            word-break: break-word;
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
            padding: 10px 4px 8px;
            flex-wrap: wrap;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 4px;
        }
        .adq-bandj-filters label {
            margin: 0;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            white-space: nowrap;
        }
        .adq-bandj-filters select {
            max-width: 320px;
            min-width: 200px;
            font-size: 13px;
            height: 32px;
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
        }
        .adq-btn-quitar-factura:hover,
        .adq-btn-quitar-factura:focus {
            background-color: #bb2d3b !important;
            border-color: #b02a37 !important;
            color: #ffffff !important;
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
            align-items: center;
            gap: 10px;
            text-align: left;
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
            flex-wrap: nowrap;
            align-items: center;
            gap: 10px 14px;
            width: 100%;
            padding-top: 2px;
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
            flex: 2 1 0;
            min-width: 0;
        }
        #panelExpedienteFin #expLlaveP12,
        #panelExpedienteFin .adq-exp-clave {
            flex: 1 1 0;
            width: 0;
            min-width: 110px;
            max-width: none;
            height: 30px;
            font-size: 12px;
            padding: 5px 10px;
            line-height: 1.3;
        }
        #panelExpedienteFin .adq-exp-step.is-disabled {
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
            #panelExpedienteFin .adq-exp-steps {
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
                flex: 1 1 0;
                width: auto;
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
        <ul class="nav nav-tabs exa-ui-nav-tabs" id="inboxTabs" role="tablist">
            <li role="presentation">
                <a href="#create-panel" id="create-tab" role="tab" data-toggle="tab"><i class="bi bi-file-earmark-plus"></i> Crear Solicitud</a>
            </li>
            <li role="presentation" class="active">
                <a href="#pending-panel" id="pending-tab" role="tab" data-toggle="tab"><i class="bi bi-clipboard-check"></i> Mis Pendientes <span class="badge"><?php echo count($pendientes); ?></span></a>
            </li>
            <li role="presentation">
                <a href="#my-panel" id="my-tab" role="tab" data-toggle="tab"><i class="bi bi-person-workspace"></i> Mis Solicitudes <span class="badge"><?php echo count($mis_solicitudes); ?></span></a>
            </li>
            <li role="presentation">
                <a href="#managed-panel" id="managed-tab" role="tab" data-toggle="tab"><i class="bi bi-check2-square"></i> Gestion&eacute; <span class="badge"><?php echo count($gestionadas); ?></span></a>
            </li>
            <li role="presentation">
                <a href="#history-panel" id="history-tab" role="tab" data-toggle="tab"><i class="bi bi-clock-history"></i> Historial <span class="badge"><?php echo count($historico); ?></span></a>
            </li>
        </ul>

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
                <div class="exa-adq-table-wrap">
                    <table class="table table-bordered exa-adq-table">
                        <thead>
                            <tr>
                                <th style="width: 100px;">N? Sol.</th>
                                <th>Flujo</th>
                                <th style="width: 150px;">Fecha</th>
                                <th>Solicitante</th>
                                <th>Departamento</th>
                                <th>Tipo Pedido</th>
                                <th style="width: 100px;">Prioridad</th>
                                <th style="width: 150px;">Valor Est.</th>
                                <th>Paso Actual Workflow</th>
                                <th style="width: 90px;">Acci?n</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pendientes)) { ?>
                                <tr class="text-center"><td colspan="10" class="text-muted py-4">No posee requerimientos de adquisiciones pendientes de aprobaci?n en este momento.</td></tr>
                            <?php } else { 
                                foreach ($pendientes as $p) { ?>
                                    <tr class="text-center adq-row-solicitud" data-wfm-fam="<?php echo intval($p['Wfm_Fam_Cod']); ?>">
                                        <td class="fw-bold"><?php echo $p['Sol_Num']; ?></td>
                                        <td class="text-start"><?php echo htmlspecialchars($p['Wfm_Nom']); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($p['Sol_Fec'])); ?></td>
                                        <td class="text-start"><?php echo $p['Solicitante_Nom']; ?></td>
                                        <td><?php echo $p['Dep_Des']; ?></td>
                                        <td class="text-start"><?php echo $p['Trq_Des']; ?></td>
                                        <td><span class="badge badge-<?php echo strtolower($p['Sol_Pri']); ?>"><?php echo $p['Sol_Pri']; ?></span></td>
                                        <td class="text-end fw-bold font-monospace">$ <?php echo number_format($p['Sol_Val_Est'], 2); ?></td>
                                        <td><span class="badge bg-primary fs-6"><i class="bi bi-clock"></i> <?php echo $p['Nod_Nom']; ?></span></td>
                                        <td class="adq-col-acciones">
                                            <div class="adq-acciones-row">
                                                <button type="button" class="btn btn-sm btn-primary adq-btn-icon-only" title="Resolver" onclick="abrirResolucion(<?php echo $p['Sol_Cod']; ?>, true)"><i class="bi bi-shield-check"></i></button>
                                                <?php if (!empty($p['Puede_Cargar_Cotizaciones'])) { ?>
                                                <button type="button" class="btn btn-sm btn-info adq-btn-icon-only" title="Cargar cotizaciones / proformas" onclick="abrirEdicionCotizaciones(<?php echo $p['Sol_Cod']; ?>)"><i class="bi bi-file-earmark-pdf"></i></button>
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
            </div>

            <!-- 2. MIS SOLICITUDES -->
            <div class="tab-pane" id="my-panel" role="tabpanel">
                <div class="exa-adq-table-wrap">
                    <table class="table table-bordered exa-adq-table">
                        <thead>
                            <tr>
                                <th style="width: 100px;">N? Sol.</th>
                                <th>Flujo</th>
                                <th style="width: 150px;">Fecha</th>
                                <th>Tipo Pedido</th>
                                <th style="width: 100px;">Prioridad</th>
                                <th style="width: 150px;">Valor Est.</th>
                                <th>Estado Solicitud</th>
                                <th>Etapa Workflow</th>
                                <th style="width: 120px;">Acci?n</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mis_solicitudes)) { ?>
                                <tr class="text-center"><td colspan="9" class="text-muted py-4">No ha iniciado requerimientos de adquisici?n a?n.</td></tr>
                            <?php } else { 
                                foreach ($mis_solicitudes as $ms) { 
                                    $est = 'Borrador'; $badge = 'secondary';
                                    if ($ms['Sol_Est'] == 'E') { $est = 'En Workflow'; $badge = 'primary'; }
                                    elseif ($ms['Sol_Est'] == 'A') { $est = 'Aprobada'; $badge = 'success'; }
                                    elseif ($ms['Sol_Est'] == 'R') { $est = 'Rechazada'; $badge = 'danger'; }
                                    elseif ($ms['Sol_Est'] == 'O') { $est = 'Observada'; $badge = 'warning text-dark'; }
                                    $etapa = !empty($ms['Nod_Nom']) ? $ms['Nod_Nom'] : (($ms['Sol_Est'] == 'P') ? 'Sin enviar' : '[Inactivo]');
                                    ?>
                                    <tr class="text-center adq-row-solicitud" data-wfm-fam="<?php echo intval($ms['Wfm_Fam_Cod']); ?>">
                                        <td class="fw-bold"><?php echo $ms['Sol_Num']; ?></td>
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
                                            <?php if ($ms['Sol_Est'] == 'P') { ?>
                                                <button type="button" class="btn btn-sm btn-warning text-dark adq-btn-icon-only" title="Completar" onclick="abrirEdicionBorrador(<?php echo $ms['Sol_Cod']; ?>)"><i class="bi bi-pencil"></i></button>
                                                <button type="button" class="btn btn-sm btn-success adq-btn-icon-only" title="Enviar" onclick="enviarBorrador(<?php echo $ms['Sol_Cod']; ?>, '<?php echo htmlspecialchars($ms['Sol_Num'], ENT_QUOTES, 'UTF-8'); ?>')"><i class="bi bi-send-check"></i></button>
                                            <?php } elseif ($ms['Sol_Est'] == 'O') { ?>
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
            </div>

            <!-- 3. GESTION? / PARTICIP? -->
            <div class="tab-pane" id="managed-panel" role="tabpanel">
                <p class="text-muted small mb-2" style="padding: 0 4px;">Solicitudes de otros usuarios en las que usted ya registro una decision en el workflow. Siguen visibles aunque ya no esten en sus pendientes.</p>
                <div class="exa-adq-table-wrap">
                    <table class="table table-bordered exa-adq-table">
                        <thead>
                            <tr>
                                <th style="width: 100px;">N? Sol.</th>
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
            </div>

            <!-- 4. HISTORIAL (cerrados) -->
            <div class="tab-pane" id="history-panel" role="tabpanel">
                <p class="text-muted small mb-2" style="padding: 0 4px;"><?php if ($es_gerencial_admin) { ?>Solicitudes finalizadas (aprobadas o rechazadas) de toda la empresa.<?php } else { ?>Solicitudes finalizadas que usted creo o en las que participo en el workflow.<?php } ?></p>
                <div class="exa-adq-table-wrap">
                    <table class="table table-bordered exa-adq-table">
                        <thead>
                            <tr>
                                <th style="width: 100px;">N? Sol.</th>
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
                                    <td><?php echo $h['Dep_Des']; ?></td>
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
                                                <th class="text-center" style="width: 60px;">Acci?n</th>
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

                            <!-- Panel: cargar cotizaciones/proformas en etapa -->
                            <div class="adq-detail-card" id="panelCotizacionesEtapa" style="display: none; border-color: #0ea5e9; background-color: #f0f9ff; padding: 10px 12px;">
                                <h5 class="adq-section-header" style="color: #0369a1; border-bottom-color: #bae6fd; margin-bottom: 8px; padding-bottom: 4px;"><i class="bi bi-file-earmark-pdf"></i> Cotizaciones / Proformas</h5>
                                <p class="mb-2" style="font-size: 12px; color: #0c4a6e;">La etapa <strong id="lblCotizacionesEtapaNodo"></strong> permite cargar o actualizar las cotizaciones de esta solicitud.</p>
                                <button type="button" class="btn btn-primary btn-sm" id="btnCargarCotizaciones" onclick="abrirEdicionCotizaciones(currentSolCod)"><i class="bi bi-file-earmark-pdf"></i> Cargar cotizaciones</button>
                            </div>

                            <!-- Panel: documentos de avance en etapa AVANCE -->
                            <div class="adq-detail-card" id="panelAvanceEtapa" style="display: none; border-color: #0dcaf0; background-color: #f0fcff; padding: 10px 12px;">
                                <h5 class="adq-section-header" style="color: #087990; border-bottom-color: #9eeaf9; margin-bottom: 8px; padding-bottom: 4px;"><i class="bi bi-receipt-cutoff"></i> Facturas de Avance</h5>
                                <p class="mb-2" style="font-size: 12px; color: #055160;">Etapa <strong id="lblAvanceEtapaNodo"></strong>: seleccione facturas de compra del sistema EXA. Use <strong>Guardar</strong> para registrar y seguir cargando mas facturas. Cuando termine, pulse <strong>Finalizar proceso</strong>.</p>
                                <div style="margin-bottom: 8px;">
                                    <input type="text" id="txtBuscarCompraAvance" class="form-control input-sm" placeholder="Buscar factura por N? o Proveedor..." oninput="buscarComprasAvance()" style="height: 26px; font-size: 11px; padding: 3px 8px;">
                                </div>
                                <div class="table-responsive mb-2" id="divResultComprasAvance" style="display: none; border: 1px solid #9eeaf9; border-radius: 4px; background-color: #ffffff; max-height: 120px; overflow-y: auto;">
                                    <table class="table table-condensed table-hover mb-0" style="font-size: 11px;">
                                        <thead style="background-color: #e7f9fc;">
                                            <tr>
                                                <th class="text-center">N? Factura</th>
                                                <th class="text-center">Fecha</th>
                                                <th>Proveedor</th>
                                                <th class="text-end">Subtotal</th>
                                                <th class="text-end">IVA</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-center" style="width: 60px;">Acci?n</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tblBuscarComprasAvance"></tbody>
                                    </table>
                                </div>
                                <form id="frmAvanceDocs" enctype="multipart/form-data">
                                    <input type="hidden" name="Sol_Cod" id="avanceSolCod" value="">
                                    <div id="lstAvanceDocsExistentes" class="mb-2"></div>
                                    <div id="lstAvanceDocsNuevos" class="mb-2"></div>
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
                                    <span class="adq-exp-sep"></span>
                                    <div class="adq-exp-step adq-exp-step-firma" id="expFirmaBlock">
                                        <span class="adq-exp-step-num">3</span>
                                        <input type="file" name="llave_p12" id="expLlaveP12" class="form-control adq-exp-file" accept=".p12" title="Llave .p12">
                                        <label id="expUsarLlaveEmpresaWrap" class="adq-exp-check" style="display: none;">
                                            <input type="checkbox" id="expUsarLlaveEmpresa" onchange="toggleLlaveEmpresaExpediente()"> Llave empresa
                                        </label>
                                        <input type="password" class="form-control adq-exp-clave" id="expLlaveClave" placeholder="Clave" autocomplete="off">
                                        <button type="button" class="btn btn-success btn-xs adq-exp-btn" id="btnFirmarExpediente" onclick="firmarExpedienteFin()">
                                            <i class="bi bi-pen"></i> Firmar
                                        </button>
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
                                        <textarea class="form-control" name="Comentario" id="actionComentario" rows="3" placeholder="Redacte el motivo de su decision..."></textarea>
                                    </div>
                                    <div style="margin-bottom: 10px;">
                                        <label class="form-label fw-semibold" style="font-size: 11px; color: #1d4ed8; margin: 0 0 4px 0; display: block;"><i class="bi bi-paperclip"></i> Sustento Adjunto <span id="lblAdjReq" class="text-danger" style="display: none;">*</span></label>
                                        <input type="file" name="adjunto" id="actionAdjunto" class="adq-file-native" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                                        <div class="adq-dropzone" id="adqDropzone" role="button" tabindex="0" onclick="document.getElementById('actionAdjunto').click();" onkeypress="if(event.key==='Enter'||event.key===' '){event.preventDefault();document.getElementById('actionAdjunto').click();}">
                                            <div class="adq-dropzone-empty" id="adqDropzoneEmpty">
                                                <i class="bi bi-cloud-arrow-up-fill adq-dropzone-icon"></i>
                                                <div class="adq-dropzone-text"><strong>Seleccionar archivo</strong> o arrastrar aqu&iacute;</div>
                                                <div class="adq-dropzone-hint">PDF, imagen o documento (m&aacute;x. 10 MB)</div>
                                            </div>
                                            <div class="adq-dropzone-file" id="adqDropzoneFile" style="display: none;">
                                                <i class="bi adq-file-icon" id="adqFileIcon"></i>
                                                <div class="adq-file-info">
                                                    <div class="adq-file-name" id="adqFileName"></div>
                                                    <div class="adq-file-size" id="adqFileSize"></div>
                                                </div>
                                                <button type="button" class="adq-file-remove" id="adqFileRemove" title="Quitar archivo" onclick="event.stopPropagation(); quitarSustentoAdjunto();"><i class="bi bi-x-lg"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="adq-action-buttons">
                                        <button type="button" class="btn btn-success" id="btnAccionAprobar" onclick="enviarAccion('APROBAR')"><i class="bi bi-check-circle"></i> Aprobar</button>
                                        <button type="button" class="btn btn-success" id="btnAccionCompletar" style="display: none;" onclick="enviarAccion('COMPLETAR')"><i class="bi bi-card-checklist"></i> Completar tarea</button>
                                        <button type="button" class="btn btn-warning text-dark" style="background-color: #f59e0b; border-color: #f59e0b; color: #ffffff;" onclick="enviarAccion('OBSERVAR')"><i class="bi bi-exclamation-triangle"></i> Observar</button>
                                        <button type="button" class="btn btn-adq-devolver" onclick="enviarAccion('DEVOLVER')"><i class="bi bi-reply"></i> Devolver</button>
                                        <button type="button" class="btn btn-danger" id="btnAccionRechazar" onclick="enviarAccion('RECHAZAR')"><i class="bi bi-x-circle"></i> Rechazar</button>
                                    </div>
                                </form>
                            </div>

                            <!-- Acciones finales de avance (al final de todo el panel izquierdo) -->
                            <div id="panelAvanceAccionesFin" class="adq-detail-card" style="display: none; border-color: #0dcaf0; background-color: #f0fcff; padding: 10px 12px; margin-top: 8px;">
                                <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                                    <button type="button" class="btn btn-primary btn-sm" id="btnGuardarAvance" onclick="guardarAvanceDocs()"><i class="bi bi-save"></i> Guardar</button>
                                    <button type="button" class="btn btn-success btn-sm" id="btnFinalizarAvance" style="display: none;" onclick="finalizarAvanceProceso()"><i class="bi bi-check-circle"></i> Finalizar proceso</button>
                                    <span id="avanceGuardadoMsg" class="text-success small" style="display: none;"></span>
                                </div>
                            </div>
                        </div>

                        <!-- COLUMNA DERECHA: Historial -->
                        <div class="col-md-5 col-sm-12">
                            <div class="adq-detail-card">
                                <div class="adq-wf-progress-header">
                                    <h5 class="adq-section-header m-0" style="border: none; padding: 0; margin: 0;"><i class="bi bi-list-stars"></i> Historial de Firmas</h5>
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

    <div id="mdlAccionOkOverlay" class="adq-msg-overlay" role="alertdialog" aria-modal="true" aria-labelledby="mdlAccionOkText">
        <div class="adq-msg-box is-success" id="mdlAccionOkBox">
            <div class="adq-msg-icon" id="mdlAccionOkIcon"><i class="bi bi-check-circle-fill"></i></div>
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
        let searchTimer = null;

        const NODOS_RESOLUBLES = ['APROBACION', 'RECEPCION', 'FACTURA', 'TAREA', 'AVANCE', 'FIN'];
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
            const esTarea = currentNodTip === 'TAREA';
            const esFin = currentNodTip === 'FIN';
            const esAvance = currentNodTip === 'AVANCE';
            $('#lblPanelDecisionTitulo').text(esFin ? 'Cierre del expediente' : (esTarea ? 'Tarea pendiente' : (esAvance ? 'Otras acciones' : 'Decisi\u00f3n en esta Etapa')));
            $('#icoPanelDecision').attr('class', esFin ? 'bi bi-file-earmark-pdf' : (esTarea ? 'bi bi-card-checklist' : (esAvance ? 'bi bi-sliders' : 'bi bi-check2-all')));
            $('#actionComentario').attr('placeholder', esFin
                ? 'Comentario de cierre del expediente...'
                : (esTarea
                    ? 'Describa el resultado de la tarea o el trabajo realizado...'
                    : (esAvance
                        ? 'Comentario para observar, devolver o rechazar...'
                        : 'Redacte el motivo de su decisi\u00f3n...')));
            $('#btnAccionAprobar').toggle(!esTarea && !esAvance).html(esFin
                ? '<i class="bi bi-check-circle"></i> Finalizar expediente'
                : '<i class="bi bi-check-circle"></i> Aprobar');
            $('#btnAccionCompletar').toggle(esTarea);
            $('#btnAccionRechazar').toggle(!esTarea);
            if (esFin) {
                renderPanelExpedienteFin();
            } else {
                $('#panelExpedienteFin').hide();
                $('#btnAccionAprobar').prop('disabled', false).attr('title', '');
            }
        }

        function actualizarBotonFinalizarExpediente() {
            if (currentNodTip !== 'FIN') {
                return;
            }
            const tienePdf = currentExpedienteEstado && parseInt(currentExpedienteEstado.tiene_pdf, 10) === 1;
            $('#btnAccionAprobar').prop('disabled', !tienePdf);
            if (tienePdf) {
                $('#btnAccionAprobar').attr('title', 'Finalizar el expediente y cerrar la solicitud');
                $('#expFinAyuda').hide().text('');
            } else {
                $('#btnAccionAprobar').attr('title', 'Debe cargar el expediente revisado antes de finalizar');
                $('#expFinAyuda').show().text('Para finalizar: descargue el expediente y cargue el PDF revisado.');
            }
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
        let avanceSearchTimer = null;
        const avanceCopCodSeleccionados = new Set();

        function avanceFileLink(path, label) {
            if (!path) {
                return `<span class="text-muted small d-inline-block me-2">${label}: sin archivo</span>`;
            }
            return `<a href="../../DATA/${path}" target="_blank" class="small d-inline-block me-2"><i class="bi bi-download"></i> ${label}</a>`;
        }

        function avanceEsc(text) {
            return $('<div>').text(text == null ? '' : String(text)).html();
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
                    return `<div class="small mb-1">
                        <a href="${avanceEsc(c.Link)}" target="_blank"><i class="bi bi-journal-text"></i> ${avanceEsc(c.Codigo)}</a>
                        <span class="text-muted">(${avanceEsc(c.Pag_Fec)})</span>
                        <span class="font-monospace">$ ${parseFloat(c.Pag_Val || 0).toFixed(2)}</span>
                        ${c.Forma ? `<span class="text-muted ms-1">${avanceEsc(c.Forma)}</span>` : ''}
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
            const dataAttr = esNuevo ? `data-avance-nuevo="${idx}"` : `data-sav-cod="${savCod}" data-sav-cop-cod="${copCod}"`;
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
            avanceDocNuevoIdx = 0;
            avanceCopCodSeleccionados.clear();
            if (!avances || avances.length === 0) {
                $exist.html('<div class="text-muted small mb-2">No hay facturas registradas en esta etapa.</div>');
            } else {
                avances.forEach(function(doc) {
                    const copCod = parseInt(doc.Sav_Cop_Cod, 10) || 0;
                    if (copCod > 0) { avanceCopCodSeleccionados.add(copCod); }
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
            $('#divResultComprasAvance').hide();
        }

        function marcarEliminarAvanceDoc(savCod, btn) {
            const $row = $(btn).closest('[data-sav-cod]');
            const cop = parseInt($row.attr('data-sav-cop-cod'), 10);
            if (cop > 0) { avanceCopCodSeleccionados.delete(cop); }
            if ($row.find('input[name="sav_eliminar[]"]').length) { $row.remove(); return; }
            $row.append(`<input type="hidden" name="sav_eliminar[]" value="${savCod}">`);
            $row.css('opacity', '0.45');
        }

        function quitarCompraAvanceNueva(idx, copCod) {
            if (copCod > 0) { avanceCopCodSeleccionados.delete(copCod); }
            $(`[data-avance-nuevo="${idx}"]`).remove();
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
                            const btn = ya
                                ? '<span class="badge bg-secondary">Agregada</span>'
                                : `<button type="button" class="btn btn-xs btn-success p-1 py-0" onclick="agregarCompraAvance(${c.Cop_Cod})"><i class="bi bi-plus-lg"></i></button>`;
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
                const idx = avanceDocNuevoIdx++;
                avanceCopCodSeleccionados.add(copCod);
                $('#lstAvanceDocsNuevos').append(htmlTarjetaAvanceCompra({
                    idx: idx,
                    copCod: copCod,
                    des: '',
                    compra: res.compra,
                    esNuevo: true
                }));
                buscarComprasAvance();
            }).fail(function() {
                alert('Error de red al consultar la factura.');
            });
        }

        function guardarAvanceDocs(onSuccess) {
            const solCod = $('#avanceSolCod').val() || currentSolCod;
            if (!solCod) {
                alert('No se identifico la solicitud.');
                return;
            }
            const fd = new FormData($('#frmAvanceDocs')[0]);
            fd.append('ajax_save_avance_docs', '1');
            fd.set('Sol_Cod', solCod);
            $('#btnGuardarAvance').prop('disabled', true);
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
                        onSuccess();
                    } else {
                        const $msg = $('#avanceGuardadoMsg');
                        $msg.text('Guardado. Puede seguir agregando facturas.').show().delay(3500).fadeOut();
                    }
                } else {
                    alert('Error: ' + (res.message || 'No se pudieron guardar las facturas.'));
                }
            }).fail(function() {
                alert('Error de red al guardar las facturas de avance.');
            }).always(function() {
                $('#btnGuardarAvance').prop('disabled', false);
            });
        }

        function tieneFacturasAvanceSinGuardar() {
            return $('#lstAvanceDocsNuevos [data-avance-nuevo]').length > 0;
        }

        function contarFacturasAvanceGuardadas() {
            return $('#lstAvanceDocsExistentes [data-sav-cod]').length;
        }

        function finalizarAvanceProceso() {
            if (tieneFacturasAvanceSinGuardar()) {
                confirmarCentrado('Hay facturas agregadas que aun no se han guardado. Desea guardarlas antes de finalizar?', function() {
                    guardarAvanceDocs(function() {
                        finalizarAvanceProcesoConfirmado();
                    });
                });
                return;
            }
            finalizarAvanceProcesoConfirmado();
        }

        function finalizarAvanceProcesoConfirmado() {
            if (contarFacturasAvanceGuardadas() <= 0) {
                alert('Debe registrar al menos una factura antes de finalizar el proceso.');
                return;
            }
            confirmarCentrado('Desea finalizar el proceso de avance? Ya no podra agregar mas facturas en esta etapa.', function() {
                enviarAccion('APROBAR');
            });
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
                const icon = ext === 'pdf' ? 'bi-file-earmark-pdf' : 'bi-paperclip';
                const label = escHtmlHist(a.label || 'Archivo');
                html += `<a href="../../DATA/${a.path}" target="_blank" class="btn btn-xs btn-outline-primary" style="font-size:11px;padding:3px 8px;"><i class="bi ${icon}"></i> ${label}</a>`;
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
                html += `
                    <div class="border rounded p-2 mb-1 bg-white small">
                        <strong><i class="bi bi-receipt-cutoff"></i> Factura # ${numero}</strong> - ${proveedor}
                        ${fecha}${total}${pdf}${des}
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

        function renderHistorialPanel(historial) {
            const $hist = $('#lstHistorial').empty();
            const historialOrdenado = ordenarHistorialDesc(historial);
            if (!historialOrdenado.length) {
                $hist.append('<div class="text-center text-muted py-3 small">No se registran movimientos en el workflow todav&iacute;a.</div>');
                return;
            }
            historialOrdenado.forEach(function(h, idx) {
                // Con orden desc: arriba = mas reciente (ultimo paso), abajo = inicio (paso 1)
                const numProceso = historialOrdenado.length - idx;
                const actor = escHtmlHist(h.Actor_Nom || h.Usuario_Nom || h.Dep_Des || 'Sistema');
                const actorModo = escHtmlHist(h.Actor_Modo || 'Por');
                const nodNom = escHtmlHist(h.Nod_Nom || '');
                const fechaHist = escHtmlHist(h.Isn_Fec || 'Sin movimiento');
                let actionBadge = '';
                let itemClass = '';

                if (parseInt(h.Fin_Pendiente || 0, 10) === 1) {
                    actionBadge = '<span class="badge bg-info" style="background-color: #0ea5e9 !important; color: #ffffff !important;">Pendiente cierre</span>';
                    itemClass = 'active';
                } else if (parseInt(h.Pendiente_Aprobacion || 0, 10) === 1 || h.Isn_Acc === 'PENDIENTE') {
                    let pendTxt = 'Pendiente de aprobaci&oacute;n';
                    if (h.Nod_Tip === 'TAREA') {
                        pendTxt = 'Tarea pendiente';
                    } else if (h.Nod_Tip === 'FIN') {
                        pendTxt = 'Pendiente cierre';
                    } else if (h.Nod_Tip === 'AVANCE') {
                        pendTxt = 'Pendiente de avance';
                    } else if (h.Nod_Tip === 'FACTURA') {
                        pendTxt = 'Pendiente de factura';
                    }
                    actionBadge = '<span class="badge bg-primary" style="background-color: #2563eb !important; color: #ffffff !important;">' + pendTxt + '</span>';
                    itemClass = 'active';
                } else if (parseInt(h.Sin_Registro || 0, 10) === 1 || h.Isn_Acc === 'SIN_REGISTRO') {
                    actionBadge = '<span class="badge bg-secondary" style="background-color: #94a3b8 !important; color: #ffffff !important;">Sin registro</span>';
                    itemClass = '';
                } else if (h.Isn_Acc === 'CREAR') {
                    actionBadge = '<span class="badge bg-secondary" style="background-color: #64748b !important; color: #ffffff !important;">Inici&oacute; Pedido</span>';
                    itemClass = 'active';
                } else if (h.Isn_Acc === 'APROBAR') {
                    actionBadge = '<span class="badge bg-success" style="background-color: #10b981 !important; color: #ffffff !important;">Aprob&oacute;</span>';
                    itemClass = 'success';
                } else if (h.Isn_Acc === 'COMPLETAR') {
                    actionBadge = '<span class="badge bg-success" style="background-color: #059669 !important; color: #ffffff !important;">Complet&oacute; tarea</span>';
                    itemClass = 'success';
                } else if (h.Isn_Acc === 'OBSERVAR') {
                    actionBadge = '<span class="badge bg-warning text-dark" style="background-color: #f59e0b !important; color: #1e293b !important;">Observ&oacute;</span>';
                    itemClass = 'warning';
                } else if (h.Isn_Acc === 'DEVOLVER') {
                    actionBadge = '<span class="badge bg-secondary" style="background-color: #4b5563 !important; color: #ffffff !important;">Devolvi&oacute;</span>';
                    itemClass = 'active';
                } else if (h.Isn_Acc === 'RECHAZAR') {
                    actionBadge = '<span class="badge bg-danger" style="background-color: #ef4444 !important; color: #ffffff !important;">Rechazado</span>';
                    itemClass = 'danger';
                } else if (h.Isn_Acc === 'REENVIAR') {
                    actionBadge = '<span class="badge bg-info text-dark" style="background-color: #38bdf8 !important; color: #0f172a !important;">Reenvi&oacute; correcci&oacute;n</span>';
                    itemClass = 'active';
                } else if (h.Isn_Acc === 'AVANCE') {
                    actionBadge = '<span class="badge bg-info" style="background-color: #0ea5e9 !important; color: #ffffff !important;">Carg&oacute; documentos</span>';
                    itemClass = 'active';
                } else if (h.Isn_Acc === 'COTIZAR') {
                    actionBadge = '<span class="badge bg-primary" style="background-color: #2563eb !important; color: #ffffff !important;">Carg&oacute; proformas</span>';
                    itemClass = 'active';
                }

                $hist.append(`
                    <div class="adq-timeline-item ${itemClass}">
                        <div class="adq-timeline-content">
                            <div class="adq-timeline-header">
                                <span class="adq-timeline-title"><span class="adq-timeline-step-num" title="Paso ${numProceso}">${numProceso}</span>${actionBadge} en etapa: <strong>${nodNom}</strong></span>
                                <span class="adq-timeline-date"><i class="bi bi-clock"></i> ${fechaHist}</span>
                            </div>
                            <div class="adq-timeline-body">
                                ${actorModo}: <span class="text-primary fw-bold">${actor}</span>
                                ${h.Isn_Com ? `<div class="adq-timeline-comment">"${escHtmlHist(h.Isn_Com)}"</div>` : ''}
                                ${renderHistorialFacturas(h.facturas)}
                                ${renderHistorialArchivos(h.archivos, h.Isn_Adj)}
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
                    $('#detDepartamento').text(sol.Dep_Des);
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
                    if (parseInt(sol.Sol_Req_Adj, 10) === 1) reqParts.push('Adjuntos obligatorios');
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
                                ? adjuntos.map(function(path, i) {
                                    const label = adjuntos.length > 1 ? ('PDF ' + (i + 1)) : 'Ver PDF';
                                    return `<a href="../../DATA/${path}" target="_blank" class="btn btn-xs btn-primary" style="background-color:#1e3a8a;border-color:#1e3a8a;color:#fff;margin-right:3px;"><i class="bi bi-file-earmark-pdf"></i> ${label}</a>`;
                                }).join('')
                                : '<span class="text-muted" style="font-size:11px;">Sin PDF</span>';
                            const jusTexto = c.Cot_Jus
                                ? $('<div>').text(c.Cot_Jus).html()
                                : (c.Cot_Sel == 1 ? '<span class="text-warning">Sin justificacion</span>' : '<span class="text-muted">?</span>');
                            $cotList.append(`
                                <tr class="${ganadorClass}">
                                    <td class="align-middle"><span class="fw-bold text-dark">${$('<div>').text(proveedor).html()}</span>${badgeGanador}</td>
                                    <td class="text-end align-middle font-monospace text-success fw-bold">$ ${parseFloat(c.Cot_Val || 0).toFixed(2)}</td>
                                    <td class="text-center align-middle">${pdfLinks}</td>
                                    <td class="align-middle adq-cot-jus-cell" style="font-size:12px;">${jusTexto}</td>
                                </tr>
                            `);
                        });
                    } else {
                        $('#divDetCotizaciones').hide();
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
                    $('#panelAvanceAccionesFin').hide();
                    $('#panelExpedienteFin').hide();
                    $('#btnFinalizarAvance').hide();
                    $('#panelEsperaCorreccion').hide();
                    $('#btnIrCorregirObservada').hide();

                    const puedeResolver = parseInt(sol.Puede_Resolver, 10) === 1;
                    const puedeCot = parseInt(sol.Puede_Cargar_Cotizaciones, 10) === 1;
                    const puedeAvance = parseInt(sol.Puede_Cargar_Avance, 10) === 1;

                    if (puedeCot) {
                        $('#lblCotizacionesEtapaNodo').text(sol.Nod_Nom || 'Etapa actual');
                        $('#panelCotizacionesEtapa').show();
                    }

                    if (puedeAvance) {
                        $('#lblAvanceEtapaNodo').text(sol.Nod_Nom || 'Etapa actual');
                        $('#avanceSolCod').val(sol.Sol_Cod);
                        renderAvanceDocs(res.avances || []);
                        $('#btnFinalizarAvance').toggle(puedeResolver);
                        $('#avanceGuardadoMsg').hide().text('');
                        $('#panelAvanceEtapa').show();
                        $('#panelAvanceAccionesFin').show();
                    }

                    if (sol.Sol_Est === 'O') {
                        const detalleObs = (sol.Motivo_Bloqueo || 'Corrija lo solicitado y pulse Reenviar correccion desde Mis Solicitudes.');
                        mostrarPanelEsperaCorreccion(
                            currentEsSolicitante ? 'Debe corregir esta solicitud' : 'Esperando correccion del solicitante',
                            detalleObs,
                            currentEsSolicitante
                        );
                    } else if (renderPanelAction && sol.Ins_Est === 'P' && puedeResolver) {
                        isComObl = parseInt(sol.Nod_Com_Obl) === 1;
                        isAdjObl = parseInt(sol.Nod_Adj_Obl) === 1;

                        $('#lblNodeActionName').text(sol.Nod_Nom || 'Etapa actual');
                        $('#actionInsCod').val(sol.Ins_Cod);
                        $('#actionComentario').val('');
                        quitarSustentoAdjunto();

                        $('#lblComReq').toggle(isComObl);
                        $('#lblAdjReq').toggle(isAdjObl);

                        configurarPanelResolucion(sol.Nod_Tip, res.expediente_pdfs, res.expediente, parseInt(res.tiene_llave_empresa, 10) === 1);
                        $('#panelDecision').show();
                    } else if (sol.Ins_Est === 'P' && !puedeResolver && !puedeCot && !puedeAvance && (sol.Sol_Est === 'E' || sol.Sol_Est === 'P')) {
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

        function mostrarMensajeCentrado(mensaje, callback, tipo) {
            const tipoFinal = tipo || adqInferirTipoMensaje(mensaje);
            const iconos = {
                success: 'bi bi-check-circle-fill',
                error: 'bi bi-x-circle-fill',
                warning: 'bi bi-exclamation-triangle-fill',
                info: 'bi bi-info-circle-fill'
            };
            const btnClass = {
                success: 'btn btn-success',
                error: 'btn btn-danger',
                warning: 'btn btn-warning',
                info: 'btn btn-primary'
            };
            const $overlay = $('#mdlAccionOkOverlay');
            const $box = $('#mdlAccionOkBox');
            $box.removeClass('is-success is-error is-warning is-info').addClass('is-' + tipoFinal);
            $('#mdlAccionOkIcon').html('<i class="' + (iconos[tipoFinal] || iconos.info) + '"></i>');
            $('#mdlAccionOkText').text(String(mensaje == null ? '' : mensaje));
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

        function confirmarCentrado(mensaje, onConfirm, onCancel) {
            const $overlay = $('#mdlAccionOkOverlay');
            const $box = $('#mdlAccionOkBox');
            $box.removeClass('is-success is-error is-warning is-info').addClass('is-warning');
            $('#mdlAccionOkIcon').html('<i class="bi bi-question-circle-fill"></i>');
            $('#mdlAccionOkText').text(String(mensaje == null ? '' : mensaje));
            $('#btnMdlAccionOk').attr('class', 'btn btn-primary').text('Continuar').off('click.accOk');
            $('#btnMdlAccionCancel').show().text('Cancelar').off('click.accOk');

            $overlay.stop(true, true).css({ display: 'flex', opacity: 0 }).animate({ opacity: 1 }, 150);

            const cerrar = function(ok) {
                $overlay.animate({ opacity: 0 }, 150, function() {
                    $overlay.css('display', 'none');
                    $('#btnMdlAccionCancel').hide();
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

        function mostrarSustentoSeleccionado(file) {
            const info = adqIconoPorExtension(file.name);
            $('#adqFileIcon').attr('class', 'bi adq-file-icon ' + info.icon).css('color', info.color);
            $('#adqFileName').text(file.name);
            $('#adqFileSize').text(adqFormatFileSize(file.size));
            $('#adqDropzoneEmpty').hide();
            $('#adqDropzoneFile').css('display', 'flex');
        }

        function quitarSustentoAdjunto() {
            const input = document.getElementById('actionAdjunto');
            if (input) { input.value = ''; }
            $('#adqDropzoneFile').hide();
            $('#adqDropzoneEmpty').show();
            $('#adqDropzone').removeClass('adq-dropzone-invalid');
        }

        function procesarSustentoArchivo(file) {
            if (!file) { return; }
            if (file.size > ADQ_ADJ_MAX_BYTES) {
                $('#adqDropzone').addClass('adq-dropzone-invalid');
                alert('El archivo supera el limite de 10 MB. Seleccione uno mas liviano.');
                quitarSustentoAdjunto();
                return;
            }
            $('#adqDropzone').removeClass('adq-dropzone-invalid');
            mostrarSustentoSeleccionado(file);
        }

        $(document).on('change', '#actionAdjunto', function() {
            if (this.files && this.files.length > 0) {
                procesarSustentoArchivo(this.files[0]);
            } else {
                quitarSustentoAdjunto();
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
                    const input = document.getElementById('actionAdjunto');
                    try { input.files = dt.files; } catch (err) { /* algunos navegadores */ }
                    procesarSustentoArchivo(dt.files[0]);
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
            if (accion === 'APROBAR' || accion === 'COMPLETAR') {
                if (isComObl && !$('#actionComentario').val().trim()) {
                    alert(accion === 'COMPLETAR'
                        ? 'El comentario es obligatorio para completar esta tarea.'
                        : 'El comentario es obligatorio para aprobar en esta etapa.');
                    return;
                }
                if (isAdjObl && !$('#actionAdjunto').val()) {
                    alert('Cargar un archivo adjunto de soporte es obligatorio en esta etapa.');
                    return;
                }
            }
            if (accion === 'RECHAZAR' && !$('#actionComentario').val().trim()) {
                alert('Debe indicar el motivo del rechazo en el comentario.');
                $('#actionComentario').focus();
                return;
            }

            $('#actionName').val(accion);
            const formData = new FormData($('#frmWorkflowAction')[0]);

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
                                        ? 'Solicitud devuelta correctamente'
                                        : (accion === 'COMPLETAR')
                                            ? 'Tarea completada correctamente'
                                            : ('Accion "' + accion + '" procesada correctamente');
                        mostrarMensajeCentrado(msgOk, function() {
                            window.location.reload();
                        }, accion === 'RECHAZAR' ? 'warning' : 'success');
                    } else {
                        alert('Error al procesar accion: ' + res.message);
                    }
                },
                error: function() {
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
                const itemsHtml = htmlMap[nodId] || '<div class="text-center text-muted py-3 small">No hay tareas registradas en esta etapa.</div>';
                $('#segNodoTareasTitulo').text(nom);
                $('#segNodoTareasSub').text(tip ? (' [' + tip + ']') : '');
                $('#segNodoTareasBody').html(itemsHtml);
                $('#segNodoTareasPanel').show();
                const panel = document.getElementById('segNodoTareasPanel');
                if (panel && panel.scrollIntoView) {
                    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }

            $body.find('.adq-seg-flow-tracker .tracker-node-clickable').off('click.segNodo').on('click.segNodo', function() {
                mostrarTareasNodo($(this).data('nod-id'), $(this));
            });

            $body.find('#btnSegNodoTareasCerrar').off('click.segNodo').on('click.segNodo', function() {
                $body.find('.tracker-node-clickable').removeClass('tracker-node-selected');
                $('#segNodoTareasPanel').hide();
            });
        }

        function abrirSeguimientoDetallado() {
            if (!currentSolCod) return;
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
            $.get('adq_solicitud.php', { ajax_get_form: 1 }, function(html) {
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
                            return $.getScript('../VALIDACIONES/adq_solicitud.js');
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
                $(this).toggle(!fam || rowFam === fam);
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
        }

        function actualizarVisibilidadFiltroFlujo() {
            const enCrear = $('#create-panel').hasClass('active');
            $('#adqBandjFiltersFlujo').toggle(!enCrear);
        }

        $(document).ready(function() {
            $('a[data-toggle="tab"]').on('shown.bs.tab', actualizarVisibilidadFiltroFlujo);
            actualizarVisibilidadFiltroFlujo();

            $('#filtroFlujo').on('change', aplicarFiltroFlujo);
            if ($('#filtroFlujo').val()) {
                aplicarFiltroFlujo();
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
            }
            const detalleSol = urlParams.get('detalle_sol');
            if (detalleSol) {
                abrirResolucion(parseInt(detalleSol, 10), false);
            }
        });
    </script>
</body>
</html>
