<?php
/**
 * Clasificacion Gestora por Plantas (archivo unico)
 * Dependencias basicas: seguridad + manifiesto + estilos model3
 * SI / SE / EV -> manifiesto_vehiculo_gestora
 * Contratos PDF -> manifiesto_vehiculo.Mav_Con / manifiesto_chofer.Mac_Con
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/man_log_manifiesto.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Mani;

function ges_json($response)
{
    // Enviar SQL al panel DebugBar ANTES de limpiar buffers / headers JSON
    if (class_exists('DebugBar')) {
        @DebugBar::sendDataInHeaders(true);
    }
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
    exit;
}

function ges_sql($obBD_con1, $obBD_conexion, $sql)
{
    return $obBD_con1->getArrayConsultaSql($sql, $obBD_conexion);
}

function ges_row($obBD_con1, $obBD_conexion, $sql)
{
    $rows = ges_sql($obBD_con1, $obBD_conexion, $sql);
    return (!empty($rows) && is_array($rows)) ? $rows[0] : array();
}

function ges_exec($obBD_con1, $obBD_conexion, $sql)
{
    $obBD_con1->setError(0, '');
    $obBD_con1->consulta($sql, $obBD_conexion->conexion);
    return ($obBD_con1->Error == 0);
}

function ges_slug_nombre($texto)
{
    $texto = strtoupper(trim((string)$texto));
    $texto = preg_replace('/\s+/', '_', $texto);
    $texto = preg_replace('/[^A-Z0-9_\-]/', '', $texto);
    $texto = preg_replace('/_+/', '_', $texto);
    $texto = trim($texto, '_');
    return $texto !== '' ? $texto : 'SIN_ID';
}

/**
 * Guarda PDF con nombre y carpeta por planta (evita conflicto si el mismo vehiculo/chofer esta en varias plantas).
 * Ruta: contratos/{vehiculos|choferes}/planta_{Pla_Cod}/{veh|cho}_{Cod}/contrato_....pdf
 */
function ges_guardar_pdf($fileInfo, $subdir, $codigo, $nombreBase = '', $Pla_Cod = 0)
{
    if (empty($fileInfo) || !isset($fileInfo['error']) || $fileInfo['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($fileInfo['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir el archivo PDF.');
    }
    $ext = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        throw new Exception('Solo se permiten archivos PDF.');
    }
    $Pla_Cod = (int)$Pla_Cod;
    if ($Pla_Cod <= 0) {
        throw new Exception('Planta no valida para el contrato.');
    }
    $safeCod = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string)$codigo);
    $baseDir = dirname(__DIR__) . '/RECURSOS/gestora/contratos/'
        . $subdir . '/planta_' . $Pla_Cod . '/' . $safeCod . '/';
    if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
        throw new Exception('No se pudo crear la carpeta de contratos.');
    }
    $prefijo = ges_slug_nombre($nombreBase !== '' ? $nombreBase : ('COD_' . $codigo));
    $filename = 'contrato_' . $prefijo . '_PLA' . $Pla_Cod . '_' . date('Ymd_His') . '.pdf';
    if (is_file($baseDir . $filename)) {
        $filename = 'contrato_' . $prefijo . '_PLA' . $Pla_Cod . '_' . date('Ymd_His') . '_' . substr(uniqid('', true), -4) . '.pdf';
    }
    if (!move_uploaded_file($fileInfo['tmp_name'], $baseDir . $filename)) {
        throw new Exception('No se pudo guardar el PDF.');
    }
    $relPath = '../RECURSOS/gestora/contratos/' . $subdir . '/planta_' . $Pla_Cod . '/' . $safeCod . '/' . $filename;
    return array(
        'path' => $relPath,
        'filename' => $filename,
        'nombre_original' => isset($fileInfo['name']) ? (string)$fileInfo['name'] : '',
        'Pla_Cod' => $Pla_Cod
    );
}

/** Elimina archivo previo solo si pertenece a la misma planta (no toca contratos de otras plantas). */
function ges_borrar_contrato_previo($pathRel, $Pla_Cod)
{
    if (empty($pathRel) || (int)$Pla_Cod <= 0) {
        return;
    }
    $pathRel = str_replace('\\', '/', (string)$pathRel);
    $marker = '/planta_' . (int)$Pla_Cod . '/';
    // Compatibilidad con rutas viejas sin planta_: no borrar (podrian compartirse)
    if (strpos($pathRel, $marker) === false && strpos($pathRel, 'planta_' . (int)$Pla_Cod . '/') === false) {
        return;
    }
    $abs = dirname(__DIR__) . '/' . ltrim(preg_replace('#^\.\./#', '', $pathRel), '/');
    // Normalizar: path guardado como ../RECURSOS/... desde FRONT
    if (strpos($pathRel, '../RECURSOS/') === 0) {
        $abs = dirname(__DIR__) . '/' . substr($pathRel, 3); // quita ../
    }
    if (is_file($abs)) {
        @unlink($abs);
    }
}

/* ==================== AJAX ==================== */

function ges_sql_stats_plantas()
{
    return "LEFT JOIN (
                SELECT mv.Pla_Cod, COUNT(*) AS tot_veh
                FROM manifiesto_vehiculo mv
                INNER JOIN vehiculo v ON v.Veh_Cod = mv.Veh_Cod AND v.Veh_Est = 'A'
                WHERE mv.Pla_Cod IS NOT NULL
                GROUP BY mv.Pla_Cod
            ) veh ON veh.Pla_Cod = p.Pla_Cod
            LEFT JOIN (
                SELECT mc.Pla_Cod, COUNT(*) AS tot_cho
                FROM manifiesto_chofer mc
                INNER JOIN chofer ch ON ch.Cho_Cod = mc.Cho_Cod AND ch.Cho_Est = 'A'
                WHERE mc.Pla_Cod IS NOT NULL
                GROUP BY mc.Pla_Cod
            ) cho ON cho.Pla_Cod = p.Pla_Cod";
}

/* Grid principal: solo plantas YA clasificadas en el tipo de la pestana */
if (isset($_REQUEST['listPlantasGestoraAjax'])) {
    $emp = (int)$Ses_Emp_Cod;
    $tipo = isset($_REQUEST['Ges_Tso']) ? strtoupper(trim($_REQUEST['Ges_Tso'])) : '';
    if (!in_array($tipo, array('SI', 'SE', 'EV'), true)) {
        ges_json(array('success' => false, 'rows' => array(), 'message' => 'Tipo invalido.'));
    }
    $search = "";
    if (!empty($_REQUEST['search'])) {
        $term = addslashes(trim($_REQUEST['search']));
        $search = " AND (p.Pla_Nom LIKE '%$term%' OR p.Pla_Lic LIKE '%$term%')";
    }
    $stats = ges_sql_stats_plantas();
    $sql = "SELECT p.Pla_Cod, p.Pla_Nom, p.Pla_Lic, p.Pla_Dir,
                   COALESCE(veh.tot_veh, 0) AS tot_veh,
                   COALESCE(cho.tot_cho, 0) AS tot_cho,
                   g.Ges_Cod, g.Ges_Tso, g.Ges_Por, g.Ges_Est
            FROM manifiesto_vehiculo_gestora g
            INNER JOIN manifiesto_plantas p ON p.Pla_Cod = g.Pla_Cod AND p.Pla_Est = 'A'
            LEFT JOIN cliente c ON c.Cli_Cod = p.Cli_Cod
            $stats
            WHERE g.Ges_Est = 'A'
              AND g.Ges_Tso = '$tipo'
              AND (c.Emp_Cod = $emp OR p.Cli_Cod IS NULL)
              AND (c.Cli_Est = 'A' OR p.Cli_Cod IS NULL)
              $search
            ORDER BY p.Pla_Nom ASC";
    $rows = ges_sql($obBD_con1, $obBD_conexion, $sql);
    if (!is_array($rows)) {
        $rows = array();
    }
    $obBD_con1->utf8_change_param($rows);
    ges_json(array('success' => true, 'rows' => $rows));
}

/* Modal: plantas sin clasificacion activa (no aparecen en SI ni SE ni EV) */
if (isset($_REQUEST['listPlantasDisponiblesAjax'])) {
    $emp = (int)$Ses_Emp_Cod;
    $search = "";
    if (!empty($_REQUEST['search'])) {
        $term = addslashes(trim($_REQUEST['search']));
        $search = " AND (p.Pla_Nom LIKE '%$term%' OR p.Pla_Lic LIKE '%$term%')";
    }
    $stats = ges_sql_stats_plantas();
    $sql = "SELECT p.Pla_Cod, p.Pla_Nom, p.Pla_Lic, p.Pla_Dir,
                   COALESCE(veh.tot_veh, 0) AS tot_veh,
                   COALESCE(cho.tot_cho, 0) AS tot_cho
            FROM manifiesto_plantas p
            LEFT JOIN cliente c ON c.Cli_Cod = p.Cli_Cod
            LEFT JOIN manifiesto_vehiculo_gestora g ON g.Pla_Cod = p.Pla_Cod AND g.Ges_Est = 'A'
            $stats
            WHERE p.Pla_Est = 'A'
              AND g.Ges_Cod IS NULL
              AND (c.Emp_Cod = $emp OR p.Cli_Cod IS NULL)
              AND (c.Cli_Est = 'A' OR p.Cli_Cod IS NULL)
              $search
            ORDER BY p.Pla_Nom ASC";
    $rows = ges_sql($obBD_con1, $obBD_conexion, $sql);
    if (!is_array($rows)) {
        $rows = array();
    }
    $obBD_con1->utf8_change_param($rows);
    ges_json(array('success' => true, 'rows' => $rows));
}

if (isset($_REQUEST['getPlantaDetalleAjax'])) {
    $Pla_Cod = isset($_REQUEST['Pla_Cod']) ? (int)$_REQUEST['Pla_Cod'] : 0;
    $resp = array('success' => false, 'planta' => null, 'gestora' => null, 'vehiculos' => array(), 'choferes' => array());
    if ($Pla_Cod > 0) {
        $planta = ges_row($obBD_con1, $obBD_conexion, "SELECT Pla_Cod, Pla_Nom, Pla_Lic, Pla_Dir FROM manifiesto_plantas WHERE Pla_Cod = $Pla_Cod LIMIT 1");
        $gestora = ges_row($obBD_con1, $obBD_conexion, "SELECT * FROM manifiesto_vehiculo_gestora WHERE Pla_Cod = $Pla_Cod AND Ges_Est = 'A' LIMIT 1");
        $vehiculos = ges_sql($obBD_con1, $obBD_conexion,
            "SELECT v.Veh_Cod, v.Veh_Pla, v.Veh_Mar, v.Veh_Col, mv.Pla_Cod, mv.Mav_Con,
                    (SELECT COUNT(*) FROM manifiesto_vehiculo mv2
                      WHERE mv2.Veh_Cod = mv.Veh_Cod AND mv2.Pla_Cod <> mv.Pla_Cod) AS otras_plantas,
                    (SELECT COUNT(*) FROM manifiesto_vehiculo mv3
                      WHERE mv3.Veh_Cod = mv.Veh_Cod AND mv3.Pla_Cod <> mv.Pla_Cod
                        AND mv3.Mav_Con IS NOT NULL AND TRIM(mv3.Mav_Con) <> '') AS contratos_otras
             FROM manifiesto_vehiculo mv
             INNER JOIN vehiculo v ON v.Veh_Cod = mv.Veh_Cod
             WHERE mv.Pla_Cod = $Pla_Cod AND v.Veh_Est = 'A'
             ORDER BY v.Veh_Pla ASC");
        $choferes = ges_sql($obBD_con1, $obBD_conexion,
            "SELECT ch.Cho_Cod, pr.Prs_Ced,
                    CONCAT(IFNULL(pr.Prs_Nom,''), ' ', IFNULL(pr.Prs_Ape,'')) AS Cho_Nom,
                    mc.Pla_Cod, mc.Mac_Con,
                    (SELECT COUNT(*) FROM manifiesto_chofer mc2
                      WHERE mc2.Cho_Cod = mc.Cho_Cod AND mc2.Pla_Cod <> mc.Pla_Cod) AS otras_plantas,
                    (SELECT COUNT(*) FROM manifiesto_chofer mc3
                      WHERE mc3.Cho_Cod = mc.Cho_Cod AND mc3.Pla_Cod <> mc.Pla_Cod
                        AND mc3.Mac_Con IS NOT NULL AND TRIM(mc3.Mac_Con) <> '') AS contratos_otras
             FROM manifiesto_chofer mc
             INNER JOIN chofer ch ON ch.Cho_Cod = mc.Cho_Cod
             LEFT JOIN persona pr ON pr.Prs_Cod = ch.Prs_Cod
             WHERE mc.Pla_Cod = $Pla_Cod AND ch.Cho_Est = 'A'
             ORDER BY pr.Prs_Ape ASC, pr.Prs_Nom ASC");
        if (!is_array($vehiculos)) {
            $vehiculos = array();
        }
        if (!is_array($choferes)) {
            $choferes = array();
        }
        $obBD_con1->utf8_change_param($planta);
        $obBD_con1->utf8_change_param($gestora);
        $obBD_con1->utf8_change_param($vehiculos);
        $obBD_con1->utf8_change_param($choferes);
        $resp['success'] = !empty($planta);
        $resp['planta'] = $planta;
        $resp['gestora'] = $gestora;
        $resp['vehiculos'] = $vehiculos;
        $resp['choferes'] = $choferes;
    }
    ges_json($resp);
}

if (isset($_POST['saveGestoraPlantaAjax'])) {
    $resp = array('success' => false);
    try {
        $Pla_Cod = !empty($_POST['Pla_Cod']) ? (int)$_POST['Pla_Cod'] : 0;
        $Ges_Tso = isset($_POST['Ges_Tso']) ? strtoupper(trim($_POST['Ges_Tso'])) : '';
        $Ges_Por = isset($_POST['Ges_Por']) ? floatval($_POST['Ges_Por']) : 0;
        if ($Pla_Cod <= 0) {
            throw new Exception('Seleccione una planta.');
        }
        if (!in_array($Ges_Tso, array('SI', 'SE', 'EV'), true)) {
            throw new Exception('Tipo de socio invalido.');
        }
        if ($Ges_Por < 0 || $Ges_Por > 100) {
            throw new Exception('El porcentaje de comision debe estar entre 0 y 100.');
        }
        $exist = ges_row($obBD_con1, $obBD_conexion,
            "SELECT Ges_Cod, Ges_Tso FROM manifiesto_vehiculo_gestora WHERE Pla_Cod = $Pla_Cod AND Ges_Est = 'A' LIMIT 1");
        if (!empty($exist['Ges_Cod'])) {
            $tipoActual = strtoupper(trim($exist['Ges_Tso']));
            // Una planta no puede estar en INTERNO y EXTERNO (ni en otro tipo) a la vez
            if ($tipoActual !== $Ges_Tso) {
                $nombres = array('SI' => 'Socio Interno', 'SE' => 'Socio Externo', 'EV' => 'Socio Eventual');
                $nom = isset($nombres[$tipoActual]) ? $nombres[$tipoActual] : $tipoActual;
                throw new Exception("Esta planta ya esta clasificada como $nom. No puede repetirse en otra seccion.");
            }
            $ok = ges_exec($obBD_con1, $obBD_conexion,
                "UPDATE manifiesto_vehiculo_gestora
                 SET Ges_Por = $Ges_Por, Ges_Est = 'A'
                 WHERE Ges_Cod = " . (int)$exist['Ges_Cod']);
            $Ges_Cod = (int)$exist['Ges_Cod'];
        } else {
            $ok = ges_exec($obBD_con1, $obBD_conexion,
                "INSERT INTO manifiesto_vehiculo_gestora (Pla_Cod, Ges_Tso, Ges_Por, Ges_Est)
                 VALUES ($Pla_Cod, '" . addslashes($Ges_Tso) . "', $Ges_Por, 'A')");
            $Ges_Cod = (int)$obBD_con1->insercionid($obBD_conexion);
        }
        if (!$ok) {
            throw new Exception(!empty($obBD_con1->MsgError) ? $obBD_con1->MsgError : 'Error al guardar.');
        }
        $resp = array('success' => true, 'message' => 'Clasificacion guardada correctamente.', 'Ges_Cod' => $Ges_Cod);
    } catch (Exception $e) {
        $resp['message'] = $e->getMessage();
    }
    ges_json($resp);
}

if (isset($_POST['removeGestoraPlantaAjax'])) {
    $resp = array('success' => false);
    try {
        $Pla_Cod = !empty($_POST['Pla_Cod']) ? (int)$_POST['Pla_Cod'] : 0;
        $Ges_Tso = isset($_POST['Ges_Tso']) ? strtoupper(trim($_POST['Ges_Tso'])) : '';
        if ($Pla_Cod <= 0) {
            throw new Exception('Seleccione una planta.');
        }
        if (!in_array($Ges_Tso, array('SI', 'SE', 'EV'), true)) {
            throw new Exception('Tipo de socio invalido.');
        }
        $exist = ges_row($obBD_con1, $obBD_conexion,
            "SELECT Ges_Cod, Ges_Tso FROM manifiesto_vehiculo_gestora
             WHERE Pla_Cod = $Pla_Cod AND Ges_Est = 'A' LIMIT 1");
        if (empty($exist['Ges_Cod'])) {
            throw new Exception('La planta no tiene clasificacion activa.');
        }
        if (strtoupper(trim($exist['Ges_Tso'])) !== $Ges_Tso) {
            throw new Exception('La planta no pertenece a esta seccion.');
        }
        $ok = ges_exec($obBD_con1, $obBD_conexion,
            "UPDATE manifiesto_vehiculo_gestora SET Ges_Est = 'I'
             WHERE Ges_Cod = " . (int)$exist['Ges_Cod']);
        if (!$ok) {
            throw new Exception(!empty($obBD_con1->MsgError) ? $obBD_con1->MsgError : 'No se pudo quitar la clasificacion.');
        }
        $resp = array('success' => true, 'message' => 'Planta retirada de la clasificacion.');
    } catch (Exception $e) {
        $resp['message'] = $e->getMessage();
    }
    ges_json($resp);
}

if (isset($_POST['uploadContratoVehiculoAjax'])) {
    $resp = array('success' => false);
    try {
        $Veh_Cod = isset($_POST['Veh_Cod']) ? (int)$_POST['Veh_Cod'] : 0;
        $Pla_Cod = isset($_POST['Pla_Cod']) ? (int)$_POST['Pla_Cod'] : 0;
        if ($Veh_Cod <= 0 || $Pla_Cod <= 0) {
            throw new Exception('Datos incompletos.');
        }
        $rel = ges_row($obBD_con1, $obBD_conexion,
            "SELECT mv.Veh_Cod, mv.Pla_Cod, mv.Mav_Con, v.Veh_Pla
             FROM manifiesto_vehiculo mv
             INNER JOIN vehiculo v ON v.Veh_Cod = mv.Veh_Cod
             WHERE mv.Veh_Cod = $Veh_Cod AND mv.Pla_Cod = $Pla_Cod
             LIMIT 1");
        if (empty($rel['Veh_Cod'])) {
            throw new Exception('El vehiculo no pertenece a esta planta. No se puede guardar el contrato.');
        }
        $placa = !empty($rel['Veh_Pla']) ? $rel['Veh_Pla'] : ('VEH' . $Veh_Cod);
        $guardado = ges_guardar_pdf(
            isset($_FILES['contrato']) ? $_FILES['contrato'] : null,
            'vehiculos',
            'veh_' . $Veh_Cod,
            'VEH_' . $placa,
            $Pla_Cod
        );
        if (empty($guardado) || empty($guardado['path'])) {
            throw new Exception('Seleccione un PDF de contrato.');
        }
        $path = $guardado['path'];
        // Solo actualiza la relacion de ESTA planta (no afecta otras)
        $ok = ges_exec($obBD_con1, $obBD_conexion,
            "UPDATE manifiesto_vehiculo SET Mav_Con = '" . addslashes($path) . "'
             WHERE Veh_Cod = $Veh_Cod AND Pla_Cod = $Pla_Cod");
        if (!$ok) {
            throw new Exception(!empty($obBD_con1->MsgError) ? $obBD_con1->MsgError : 'No se pudo guardar el contrato.');
        }
        if (!empty($rel['Mav_Con']) && $rel['Mav_Con'] !== $path) {
            ges_borrar_contrato_previo($rel['Mav_Con'], $Pla_Cod);
        }
        $otras = ges_row($obBD_con1, $obBD_conexion,
            "SELECT COUNT(*) AS n FROM manifiesto_vehiculo
             WHERE Veh_Cod = $Veh_Cod AND Pla_Cod <> $Pla_Cod");
        $nOtras = !empty($otras['n']) ? (int)$otras['n'] : 0;
        $msg = 'Contrato de esta planta guardado como: ' . $guardado['filename'];
        if ($nOtras > 0) {
            $msg .= ' (El vehiculo tambien esta en ' . $nOtras . ' planta(s); sus contratos no se modificaron.)';
        }
        $resp = array(
            'success' => true,
            'message' => $msg,
            'path' => $path,
            'filename' => $guardado['filename'],
            'Pla_Cod' => $Pla_Cod
        );
    } catch (Exception $e) {
        $resp['message'] = $e->getMessage();
    }
    ges_json($resp);
}

if (isset($_POST['uploadContratoChoferAjax'])) {
    $resp = array('success' => false);
    try {
        $Cho_Cod = isset($_POST['Cho_Cod']) ? (int)$_POST['Cho_Cod'] : 0;
        $Pla_Cod = isset($_POST['Pla_Cod']) ? (int)$_POST['Pla_Cod'] : 0;
        if ($Cho_Cod <= 0 || $Pla_Cod <= 0) {
            throw new Exception('Datos incompletos.');
        }
        $rel = ges_row($obBD_con1, $obBD_conexion,
            "SELECT mc.Cho_Cod, mc.Pla_Cod, mc.Mac_Con, pr.Prs_Ced
             FROM manifiesto_chofer mc
             INNER JOIN chofer ch ON ch.Cho_Cod = mc.Cho_Cod
             LEFT JOIN persona pr ON pr.Prs_Cod = ch.Prs_Cod
             WHERE mc.Cho_Cod = $Cho_Cod AND mc.Pla_Cod = $Pla_Cod
             LIMIT 1");
        if (empty($rel['Cho_Cod'])) {
            throw new Exception('El chofer no pertenece a esta planta. No se puede guardar el contrato.');
        }
        $ced = !empty($rel['Prs_Ced']) ? $rel['Prs_Ced'] : ('CHO' . $Cho_Cod);
        $guardado = ges_guardar_pdf(
            isset($_FILES['contrato']) ? $_FILES['contrato'] : null,
            'choferes',
            'cho_' . $Cho_Cod,
            'CHO_' . $ced,
            $Pla_Cod
        );
        if (empty($guardado) || empty($guardado['path'])) {
            throw new Exception('Seleccione un PDF de contrato.');
        }
        $path = $guardado['path'];
        $ok = ges_exec($obBD_con1, $obBD_conexion,
            "UPDATE manifiesto_chofer SET Mac_Con = '" . addslashes($path) . "'
             WHERE Cho_Cod = $Cho_Cod AND Pla_Cod = $Pla_Cod");
        if (!$ok) {
            throw new Exception(!empty($obBD_con1->MsgError) ? $obBD_con1->MsgError : 'No se pudo guardar el contrato.');
        }
        if (!empty($rel['Mac_Con']) && $rel['Mac_Con'] !== $path) {
            ges_borrar_contrato_previo($rel['Mac_Con'], $Pla_Cod);
        }
        $otras = ges_row($obBD_con1, $obBD_conexion,
            "SELECT COUNT(*) AS n FROM manifiesto_chofer
             WHERE Cho_Cod = $Cho_Cod AND Pla_Cod <> $Pla_Cod");
        $nOtras = !empty($otras['n']) ? (int)$otras['n'] : 0;
        $msg = 'Contrato de esta planta guardado como: ' . $guardado['filename'];
        if ($nOtras > 0) {
            $msg .= ' (El chofer tambien esta en ' . $nOtras . ' planta(s); sus contratos no se modificaron.)';
        }
        $resp = array(
            'success' => true,
            'message' => $msg,
            'path' => $path,
            'filename' => $guardado['filename'],
            'Pla_Cod' => $Pla_Cod
        );
    } catch (Exception $e) {
        $resp['message'] = $e->getMessage();
    }
    ges_json($resp);
}

$secciones = array(
    'SI' => array('id' => 'tabSI', 'titulo' => 'Socios Internos', 'active' => true),
    'SE' => array('id' => 'tabSE', 'titulo' => 'Socios Externos', 'active' => false),
    'EV' => array('id' => 'tabEV', 'titulo' => 'Socios Eventuales', 'active' => false),
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clasificacion Gestora - Socios</title>
    <?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
    <?php require_once("../../mascaras/model3/estilos/estilos.php"); ?>
    <style>
        :root {
            --ges-bg: #DFE9F6;
            --ges-card: #ffffff;
            --ges-line: #dcdfe6;
            --ges-ink: #2d3748;
            --ges-muted: #4a5568;
            --ges-brand: #1e88e5;
            --ges-brand-dark: #1565c0;
            --ges-grid-main: #2b3e50;
            --ges-grid-main-2: #3a536b;
            --ges-grid-sub: #1e88e5;
            --ges-grid-sub-2: #1565c0;
            --ges-ok: #5cb85c;
            --ges-warn: #f0ad4e;
            --ges-info: #5bc0de;
            --ges-radius: 6px;
        }
        body { background-color: var(--ges-bg) !important; }
        .panel-main.exa-ui-panel {
            border: 1px solid var(--ges-line); box-shadow: 0 1px 4px rgba(0,0,0,.06);
            border-radius: var(--ges-radius); overflow: hidden; margin: 8px;
            background: var(--ges-bg);
        }
        .panel-main .panel-heading.exa-header {
            background: linear-gradient(to bottom, #3a536b, #2b3e50);
            color: #fff; border: 0; padding: 10px 14px;
        }
        .panel-main .panel-heading .panel-title {
            margin: 0; font-size: 13px; font-weight: 700; letter-spacing: .05em;
            text-transform: uppercase;
        }
        .panel-main .panel-body.exa-body { padding: 12px 14px; background: var(--ges-bg); }

        .ges-tabs > .nav-tabs {
            margin: 0; padding: 6px 10px 0; border: 0; border-bottom: 1px solid var(--ges-line);
            background: #f4f6f9; border-radius: 6px 6px 0 0; display: flex; gap: 4px; flex-wrap: wrap;
        }
        .ges-tabs > .nav-tabs > li { float: none; margin: 0; }
        .ges-tabs > .nav-tabs > li > a {
            margin: 0 4px 0 0; border: 1px solid transparent !important;
            border-radius: 6px 6px 0 0 !important;
            background: transparent; color: #4a5568; font-size: 13px; font-weight: 600;
            padding: 8px 14px !important; line-height: 1.2;
        }
        .ges-tabs > .nav-tabs > li > a:hover { background: #e2e8f0; color: #2d3748; }
        .ges-tabs > .nav-tabs > li.active > a,
        .ges-tabs > .nav-tabs > li.active > a:focus,
        .ges-tabs > .nav-tabs > li.active > a:hover {
            color: #1e88e5 !important; background: #fff !important;
            border-color: #dcdfe6 #dcdfe6 transparent #dcdfe6 !important;
            border-top: 3px solid #1e88e5 !important;
        }
        .ges-tabs {
            border: 1px solid var(--ges-line); border-radius: 6px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06); background: #fff; overflow: hidden;
        }
        .ges-tabs > .tab-content {
            margin: 0; background: var(--ges-bg); border: 0;
            border-radius: 0; padding: 12px 14px; box-shadow: none;
        }

        .ges-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; margin-bottom: 10px; }
        .ges-stat-card {
            background: #fff; border: 1px solid var(--ges-line); border-radius: 6px; padding: 7px 10px;
            display: flex; align-items: baseline; gap: 8px; min-width: 0;
        }
        .ges-stat-card .n { font-size: 18px; font-weight: 800; color: #1e88e5; line-height: 1; }
        .ges-stat-card .l { font-size: 10px; color: var(--ges-muted); text-transform: uppercase; font-weight: 700; letter-spacing: .4px; }

        .ges-toolbar {
            display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
            margin-bottom: 8px; padding: 8px 10px; background: #f8fafc;
            border: 1px solid #cbd5e1; border-radius: 6px;
        }
        .ges-toolbar .ges-toolbar-title {
            font-size: 11px; font-weight: 700; color: #334155;
            text-transform: uppercase; letter-spacing: .05em; margin-right: 4px; white-space: nowrap;
        }
        .ges-toolbar .filtroPlantaForm { display: flex; align-items: center; gap: 8px; flex: 1; flex-wrap: wrap; margin: 0; }
        .ges-toolbar .input-group { width: min(340px, 100%); }
        .ges-toolbar .form-control,
        #modalIntegrantes .form-control,
        #modalClasificarPlanta .form-control {
            height: 30px; font-size: 12px; border-radius: 4px; border-color: #cbd5e1;
            box-shadow: none; padding: 4px 8px;
        }
        .ges-toolbar .form-control:focus,
        #modalIntegrantes .form-control:focus,
        #modalClasificarPlanta .form-control:focus {
            border-color: #1e88e5; box-shadow: 0 0 0 3px rgba(30, 136, 229, .18);
        }
        .ges-toolbar .btn,
        #modalIntegrantes .btn,
        #modalClasificarPlanta .btn {
            border-radius: 4px; font-size: 12px; font-weight: 600; padding: 5px 10px;
        }
        .ges-toolbar .btn-primary,
        #modalIntegrantes .btn-primary {
            background: #1e88e5; border-color: #1565c0; color: #fff;
        }
        .ges-toolbar .btn-primary:hover,
        #modalIntegrantes .btn-primary:hover {
            background: #1565c0; border-color: #0d47a1; color: #fff;
        }
        .ges-toolbar .btn-success { background: #5cb85c; border-color: #4cae4c; color: #fff; }

        /* ===== GRID PRINCIPAL (plantas) — estilo titlebar jqGrid choferes/vehiculos ===== */
        .ges-grid-main-wrap {
            border: 1px solid var(--ges-grid-main); border-radius: 5px; overflow: hidden; background: #fff;
        }
        .ges-grid-main-wrap .ges-grid-caption {
            height: 34px; line-height: 34px; padding: 0 14px;
            background: linear-gradient(to bottom, #3a536b, #2b3e50);
            color: #fff; font-size: 12px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .05em; text-shadow: 0 1px 2px rgba(0,0,0,.35);
        }
        .tbl-plantas, #tblPlantasDisponibles { margin: 0; background: #fff; }
        .tbl-plantas > thead > tr > th,
        #tblPlantasDisponibles > thead > tr > th {
            background: #2b3e50; color: #fff; font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .3px; border: 0 !important;
            padding: 7px 8px !important; white-space: nowrap;
        }
        .tbl-plantas > tbody > tr > td,
        #tblPlantasDisponibles > tbody > tr > td {
            vertical-align: middle !important; font-size: 12px; padding: 6px 8px !important;
            border-color: #e2e8f0 !important; color: #1e293b;
        }
        #tblPlantasDisponibles > thead > tr > th {
            padding: 5px 7px !important; font-size: 10px !important;
            position: sticky; top: 0; z-index: 2;
        }
        #tblPlantasDisponibles > tbody > tr > td {
            padding: 3px 7px !important; font-size: 11px !important; line-height: 1.2 !important;
            height: 26px;
        }
        .tbl-plantas > tbody > tr:nth-child(even) { background: #f8fafc; }
        .tbl-plantas > tbody > tr:hover,
        #tblPlantasDisponibles > tbody > tr:hover { background: #eff6ff; }
        #tblPlantasDisponibles > tbody > tr.info,
        #tblPlantasDisponibles > tbody > tr.info:hover { background: #dbeafe !important; }
        #modalClasificarPlanta .ges-modal-grid-scroll {
            max-height: 216px; /* ~8 filas compactas */
            overflow: auto;
            border: 1px solid #d0d7e2;
            border-radius: 5px;
            background: #fff;
        }

        /* ===== GRID INTEGRANTES — filas compactas + scroll ===== */
        .ges-grid-sub-wrap {
            border: 0; border-radius: 0; overflow: hidden; background: #fff; margin: 0;
        }
        .ges-grid-sub-wrap .ges-grid-caption { display: none; }
        .tbl-vehiculos, .tbl-choferes { margin: 0 !important; background: #fff; border: 0 !important; }
        .tbl-vehiculos > thead > tr > th,
        .tbl-choferes > thead > tr > th {
            background: #334155; color: #fff; font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .35px; border: 0 !important;
            padding: 4px 7px !important; white-space: nowrap;
            position: sticky; top: 0; z-index: 2;
        }
        .tbl-vehiculos > tbody > tr > td,
        .tbl-choferes > tbody > tr > td {
            vertical-align: middle !important; font-size: 11px; padding: 2px 7px !important;
            border-color: #e2e8f0 !important; color: #334155; background: #fff;
            line-height: 1.15; height: 26px;
        }
        .tbl-vehiculos > tbody > tr:nth-child(even) > td,
        .tbl-choferes > tbody > tr:nth-child(even) > td { background: #f8fafc; }
        .tbl-vehiculos > tbody > tr:hover > td,
        .tbl-choferes > tbody > tr:hover > td { background: #eef2f7 !important; }

        .ges-planta-panel { display: none; }

        /* Modal integrantes — layout profesional */
        .ui-dialog.ges-dlg-integrantes { width: 820px !important; }
        .ui-dialog.ges-dlg-integrantes .ui-dialog-titlebar {
            background: linear-gradient(to bottom, #3a536b, #2b3e50) !important;
            padding: 11px 16px !important;
        }
        .ui-dialog.ges-dlg-integrantes .ui-dialog-content {
            background: #f4f6f9 !important; padding: 12px !important;
        }
        #modalIntegrantes .ges-int-card {
            background: #fff; border: 1px solid #d0d7e2; border-radius: 6px;
            overflow: hidden; box-shadow: 0 1px 2px rgba(15,23,42,.04);
        }
        #modalIntegrantes .ges-int-meta {
            display: flex; align-items: center; justify-content: space-between;
            gap: 10px; flex-wrap: wrap; padding: 10px 12px;
            background: linear-gradient(180deg, #ffffff, #f8fafc);
            border-bottom: 1px solid #e2e8f0;
        }
        #modalIntegrantes .ges-int-identity { min-width: 0; flex: 1; }
        #modalIntegrantes .ges-int-kicker {
            display: block; font-size: 10px; font-weight: 700; color: #64748b;
            text-transform: uppercase; letter-spacing: .06em; margin-bottom: 2px;
        }
        #modalIntegrantes .ges-int-name {
            margin: 0; font-size: 15px; font-weight: 800; color: #1e293b; line-height: 1.2;
        }
        #modalIntegrantes .ges-int-lic {
            display: inline-block; margin-top: 3px; font-size: 11px; color: #64748b;
        }
        #modalIntegrantes .ges-int-badge {
            display: inline-block; margin-left: 6px; vertical-align: middle;
            font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 999px;
            background: #e2e8f0; color: #334155;
        }
        #modalIntegrantes .ges-int-badge.si { background: #dbeafe; color: #1d4ed8; }
        #modalIntegrantes .ges-int-badge.se { background: #ffedd5; color: #c2410c; }
        #modalIntegrantes .ges-int-badge.ev { background: #e0f2fe; color: #0369a1; }
        #modalIntegrantes .ges-int-actions {
            display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
            padding: 8px 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 5px;
        }
        #modalIntegrantes .ges-int-actions label {
            margin: 0; font-size: 10px; font-weight: 700; color: #64748b;
            text-transform: uppercase; letter-spacing: .3px;
        }
        #modalIntegrantes .ges-int-actions .input-group { width: 110px; }
        #modalIntegrantes .ges-int-actions .form-control {
            height: 28px !important; font-size: 12px !important; padding: 3px 8px !important;
        }
        #modalIntegrantes .ges-int-actions .btn { padding: 4px 10px !important; font-size: 11px !important; }

        #modalIntegrantes .ges-subtabs {
            margin: 0; padding: 6px 10px 0; border: 0; background: #edf1f5;
            display: flex; width: auto; max-width: 100%;
            border-bottom: 1px solid #d0d7e2; gap: 4px;
        }
        #modalIntegrantes .ges-subtabs > li { float: none; margin: 0; flex: 0 0 auto; }
        #modalIntegrantes .ges-subtabs > li > a {
            display: inline-block; text-align: center; margin: 0 !important;
            padding: 6px 12px !important; font-size: 11px; font-weight: 700;
            border: 1px solid transparent !important; border-bottom: 0 !important;
            border-radius: 5px 5px 0 0 !important; color: #64748b;
            background: transparent; line-height: 1.2; white-space: nowrap;
        }
        #modalIntegrantes .ges-subtabs > li > a .ges-tab-count {
            display: inline-block; min-width: 18px; margin-left: 4px; padding: 0 5px;
            border-radius: 999px; background: #cbd5e1; color: #334155;
            font-size: 10px; font-weight: 800; line-height: 16px; vertical-align: middle;
        }
        #modalIntegrantes .ges-subtabs > li > a:hover { background: #e2e8f0; color: #1e293b; }
        #modalIntegrantes .ges-subtabs > li.active > a,
        #modalIntegrantes .ges-subtabs > li.active > a:hover,
        #modalIntegrantes .ges-subtabs > li.active > a:focus {
            color: #1e293b !important; background: #fff !important;
            border-color: #d0d7e2 #d0d7e2 #fff #d0d7e2 !important;
            box-shadow: none !important;
        }
        #modalIntegrantes .ges-subtabs > li.active > a .ges-tab-count {
            background: #2b3e50; color: #fff;
        }
        #modalIntegrantes .ges-int-body { padding: 0; }
        #modalIntegrantes .ges-int-body > .tab-content {
            background: #fff; border: 0; border-radius: 0; padding: 0; margin: 0; overflow: hidden;
        }
        #modalIntegrantes .ges-int-body > .tab-content > .tab-pane { padding: 0; margin: 0; }
        #modalIntegrantes .ges-int-scroll {
            max-height: 232px; /* cabecera + ~8 filas */
            overflow-y: auto;
            overflow-x: auto;
        }
        .ges-contrato-ok {
            display: inline-flex; align-items: center; gap: 4px; max-width: 220px;
            color: #15803d; font-weight: 700; font-size: 10px; text-decoration: none;
            padding: 2px 8px; border-radius: 999px; background: #dcfce7; border: 1px solid #86efac;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .ges-contrato-ok:hover { background: #bbf7d0; text-decoration: none; }
        .ges-contrato-no {
            display: inline-flex; align-items: center; gap: 4px;
            color: #64748b; font-size: 11px; font-style: normal; font-weight: 600;
            padding: 2px 8px; border-radius: 999px; background: #f1f5f9; border: 1px solid #e2e8f0;
        }
        .ges-upload-row { white-space: nowrap; min-width: 220px; }
        .ges-upload-row input[type=file] { display: none !important; }
        .ges-filepick {
            display: inline-flex; align-items: stretch; max-width: 260px;
            border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden;
            background: #fff; vertical-align: middle; box-shadow: 0 1px 1px rgba(15,23,42,.04);
        }
        .ges-filepick-btn {
            border: 0; background: #334155; color: #fff; font-size: 11px; font-weight: 700;
            padding: 0 10px; height: 30px; line-height: 30px; cursor: pointer; white-space: nowrap;
        }
        .ges-filepick-btn:hover { background: #1e293b; color: #fff; }
        .ges-filepick-btn .glyphicon { margin-right: 4px; font-size: 11px; }
        .ges-filepick-name {
            display: inline-block; min-width: 88px; max-width: 120px; padding: 0 8px;
            height: 30px; line-height: 30px; font-size: 10px; color: #64748b;
            background: #f8fafc; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: top;
        }
        .ges-filepick-name.has-file { color: #0f172a; font-weight: 700; background: #eff6ff; }
        .ges-filepick-up {
            border: 0; background: #16a34a; color: #fff; width: 34px; height: 30px;
            line-height: 30px; padding: 0; cursor: pointer; font-size: 13px;
        }
        .ges-filepick-up:hover { background: #15803d; color: #fff; }
        .ges-filepick-up[disabled] { opacity: .7; cursor: wait; background: #64748b; }
        #modalIntegrantes .ges-upload-row .btn-upload-contrato,
        .ges-upload-row .btn-upload-contrato { display: none; }
        .ges-upload-overlay {
            display: none; position: absolute; inset: 0; z-index: 20;
            background: rgba(248, 250, 252, .78);
            align-items: center; justify-content: center; flex-direction: column; gap: 8px;
        }
        .ges-upload-overlay.show { display: flex; }
        .ges-upload-overlay .ges-spinner {
            width: 28px; height: 28px; border: 3px solid #cbd5e1; border-top-color: #2b3e50;
            border-radius: 50%; animation: ges-spin .7s linear infinite;
        }
        .ges-upload-overlay .ges-upload-txt {
            font-size: 12px; font-weight: 700; color: #334155;
        }
        @keyframes ges-spin { to { transform: rotate(360deg); } }
        #modalIntegrantes .ges-int-body { position: relative; }
        .ges-multi-pla {
            display: inline-block; margin-left: 4px; font-size: 9px; font-weight: 700;
            padding: 1px 5px; border-radius: 999px; vertical-align: middle;
            background: #ffedd5; color: #9a3412; border: 1px solid #fdba74;
        }
        .label { border-radius: 999px; font-weight: 700; padding: 3px 8px; font-size: 10px; }
        .label-primary { background: #1e88e5; }
        .label-warning { background: #f0ad4e; }
        .label-info { background: #5bc0de; }

        #modalClasificarPlanta .ges-modal-hint {
            margin: 0 0 10px; padding: 8px 10px; font-size: 11px; line-height: 1.4;
            color: #334155; background: #fff; border: 1px solid #d0d7e2; border-left: 3px solid #2b3e50;
            border-radius: 4px;
        }
        #modalClasificarPlanta .ges-modal-toolbar {
            display: flex; gap: 8px; align-items: center; margin-bottom: 8px; flex-wrap: wrap;
        }
        #modalClasificarPlanta .ges-modal-toolbar .input-group { flex: 1; min-width: 180px; }
        #modalClasificarPlanta .ges-modal-por {
            display: flex; align-items: center; gap: 8px; margin-top: 10px;
            padding: 8px 10px; background: #fff; border: 1px solid #d0d7e2; border-radius: 5px;
        }
        #modalClasificarPlanta .ges-modal-por label {
            margin: 0; font-size: 10px; font-weight: 700; color: #64748b;
            text-transform: uppercase; white-space: nowrap;
        }
        #modalClasificarPlanta .ges-modal-por .input-group { width: 120px; }
        #modalClasificarPlanta .table-responsive,
        #modalClasificarPlanta .ges-modal-grid-scroll {
            border: 1px solid #d0d7e2; border-radius: 5px; background: #fff; overflow: auto;
        }

        .ui-dialog {
            border-radius: 8px !important; border: 0 !important;
            box-shadow: 0 18px 48px rgba(15,23,42,.28) !important; overflow: hidden;
        }
        .ui-dialog .ui-dialog-titlebar {
            background: linear-gradient(to bottom, #3a536b, #2b3e50) !important;
            color: #fff !important; border: 0 !important; padding: 11px 16px !important;
            font-size: 13px; font-weight: 700; letter-spacing: .02em;
        }
        .ui-dialog .ui-dialog-title { float: none !important; display: block !important; }
        .ui-dialog .ui-dialog-titlebar-close {
            right: 10px !important; top: 50% !important; margin-top: -10px !important;
            width: 20px !important; height: 20px !important; background: rgba(255,255,255,.15) !important;
            border: 0 !important; border-radius: 4px !important;
        }
        .ui-dialog .ui-dialog-titlebar-close:hover { background: rgba(255,255,255,.28) !important; }
        .ui-dialog .ui-dialog-content { padding: 12px 14px !important; font-size: 12px; background: #f4f6f9 !important; }
        .ui-dialog .ui-dialog-buttonpane {
            margin: 0 !important; border-top: 1px solid #d0d7e2 !important;
            background: #fff !important; padding: 8px 12px !important;
        }
        .ui-dialog .ui-dialog-buttonpane .ui-dialog-buttonset { float: right; }
        .ui-dialog .ui-dialog-buttonpane .ui-button,
        .ui-dialog .ui-dialog-buttonpane button.btn {
            font-family: inherit !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            line-height: 1.4 !important;
            border-radius: 4px !important;
            padding: 6px 12px !important;
            margin-left: 6px !important;
            box-shadow: none !important;
            background-image: none !important;
            text-shadow: none !important;
            outline: none !important;
        }
        .ui-dialog .ui-dialog-buttonpane .btn-primary,
        .ui-dialog .ui-dialog-buttonpane .btn-primary.ui-state-default,
        .ui-dialog .ui-dialog-buttonpane .btn-primary.ui-state-hover,
        .ui-dialog .ui-dialog-buttonpane .btn-primary.ui-state-focus,
        .ui-dialog .ui-dialog-buttonpane .btn-primary.ui-state-active,
        .ui-dialog .ui-dialog-buttonpane .btn-primary:hover,
        .ui-dialog .ui-dialog-buttonpane .btn-primary:focus,
        .ui-dialog .ui-dialog-buttonpane .btn-primary:active {
            color: #fff !important;
            background-color: #337ab7 !important;
            border: 1px solid #2e6da4 !important;
        }
        .ui-dialog .ui-dialog-buttonpane .btn-primary:hover,
        .ui-dialog .ui-dialog-buttonpane .btn-primary.ui-state-hover {
            background-color: #286090 !important;
            border-color: #204d74 !important;
        }
        .ui-dialog .ui-dialog-buttonpane .btn-primary:focus,
        .ui-dialog .ui-dialog-buttonpane .btn-primary.ui-state-focus {
            background-color: #286090 !important;
            border-color: #122b40 !important;
            box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 0 3px rgba(51,122,183,.35) !important;
        }
        .ui-dialog .ui-dialog-buttonpane .btn-default,
        .ui-dialog .ui-dialog-buttonpane .btn-default.ui-state-default,
        .ui-dialog .ui-dialog-buttonpane .btn-default.ui-state-hover,
        .ui-dialog .ui-dialog-buttonpane .btn-default.ui-state-focus,
        .ui-dialog .ui-dialog-buttonpane .btn-default.ui-state-active,
        .ui-dialog .ui-dialog-buttonpane .btn-default:hover,
        .ui-dialog .ui-dialog-buttonpane .btn-default:focus,
        .ui-dialog .ui-dialog-buttonpane .btn-default:active {
            color: #333 !important;
            background-color: #fff !important;
            border: 1px solid #ccc !important;
        }
        .ui-dialog .ui-dialog-buttonpane .btn-default:hover,
        .ui-dialog .ui-dialog-buttonpane .btn-default.ui-state-hover,
        .ui-dialog .ui-dialog-buttonpane .btn-default:focus,
        .ui-dialog .ui-dialog-buttonpane .btn-default.ui-state-focus {
            color: #333 !important;
            background-color: #e6e6e6 !important;
            border-color: #adadad !important;
        }
        .ui-dialog .ui-dialog-buttonpane .btn-success,
        .ui-dialog .ui-dialog-buttonpane .btn-success.ui-state-default,
        .ui-dialog .ui-dialog-buttonpane .btn-success.ui-state-hover,
        .ui-dialog .ui-dialog-buttonpane .btn-success.ui-state-focus,
        .ui-dialog .ui-dialog-buttonpane .btn-success:hover,
        .ui-dialog .ui-dialog-buttonpane .btn-success:focus {
            color: #fff !important;
            background-color: #5cb85c !important;
            border: 1px solid #4cae4c !important;
        }
        .ui-dialog .ui-dialog-buttonpane .btn-success:hover,
        .ui-dialog .ui-dialog-buttonpane .btn-success.ui-state-hover,
        .ui-dialog .ui-dialog-buttonpane .btn-success:focus,
        .ui-dialog .ui-dialog-buttonpane .btn-success.ui-state-focus {
            background-color: #449d44 !important;
            border-color: #398439 !important;
        }
        .ui-dialog .ui-dialog-buttonpane .btn-danger,
        .ui-dialog .ui-dialog-buttonpane .btn-danger.ui-state-default,
        .ui-dialog .ui-dialog-buttonpane .btn-danger.ui-state-hover,
        .ui-dialog .ui-dialog-buttonpane .btn-danger.ui-state-focus,
        .ui-dialog .ui-dialog-buttonpane .btn-danger:hover,
        .ui-dialog .ui-dialog-buttonpane .btn-danger:focus {
            color: #fff !important;
            background-color: #d9534f !important;
            border: 1px solid #d43f3a !important;
        }
        .ui-dialog .ui-dialog-buttonpane .btn-danger:hover,
        .ui-dialog .ui-dialog-buttonpane .btn-danger.ui-state-hover,
        .ui-dialog .ui-dialog-buttonpane .btn-danger:focus,
        .ui-dialog .ui-dialog-buttonpane .btn-danger.ui-state-focus {
            background-color: #c9302c !important;
            border-color: #ac2925 !important;
        }

        @media (max-width: 900px) {
            .ges-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 560px) {
            .ges-stats { grid-template-columns: 1fr 1fr; }
            .ges-toolbar .input-group { width: 100%; }
        }
    </style>
</head>
<body>
<div class="panel panel-default panel-main exa-ui-panel">
    <div class="panel-heading exa-header">
        <h3 class="panel-title"><span class="glyphicon glyphicon-tags"></span> Clasificacion Gestora de Socios</h3>
    </div>
    <div class="panel-body exa-body">
        <div class="nav-tabs-custom ges-tabs">
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active"><a href="#tabSI" role="tab" data-toggle="tab" data-tipo="SI"><i class="glyphicon glyphicon-home"></i> Internos</a></li>
                <li role="presentation"><a href="#tabSE" role="tab" data-toggle="tab" data-tipo="SE"><i class="glyphicon glyphicon-globe"></i> Externos</a></li>
                <li role="presentation" style="display: none;"><a href="#tabEV" role="tab" data-toggle="tab" data-tipo="EV"><i class="glyphicon glyphicon-time"></i> Eventuales</a></li>
            </ul>
            <div class="tab-content">
                <?php foreach ($secciones as $tipo => $sec) {
                    $active = !empty($sec['active']) ? ' active' : ''; ?>
                <div role="tabpanel" class="tab-pane<?php echo $active; ?>" id="<?php echo $sec['id']; ?>">
                    <div class="ges-stats" id="stats<?php echo $tipo; ?>">
                        <div class="ges-stat-card"><div class="n" data-k="plantas">0</div><div class="l">Plantas</div></div>
                        <div class="ges-stat-card"><div class="n" data-k="vehiculos">0</div><div class="l">Vehiculos</div></div>
                        <div class="ges-stat-card"><div class="n" data-k="choferes">0</div><div class="l">Choferes</div></div>
                        <div class="ges-stat-card"><div class="n" data-k="clasificadas">0</div><div class="l">Clasificadas</div></div>
                    </div>

                    <div class="ges-toolbar">
                        <span class="ges-toolbar-title"><?php echo htmlspecialchars($sec['titulo']); ?></span>
                        <form class="filtroPlantaForm" data-tipo="<?php echo $tipo; ?>" onsubmit="event.preventDefault(); cargarPlantas('<?php echo $tipo; ?>');">
                            <div class="input-group input-group-sm">
                                <input type="text" name="search" class="form-control" placeholder="Buscar planta clasificada...">
                                <span class="input-group-btn"><button type="submit" class="btn btn-success"><i class="glyphicon glyphicon-search"></i></button></span>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" onclick="abrirModalClasificar('<?php echo $tipo; ?>');">
                                <i class="glyphicon glyphicon-plus"></i> Clasificar
                            </button>
                        </form>
                    </div>

                    <div class="ges-grid-main-wrap">
                        <div class="ges-grid-caption">Plantas clasificadas</div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-condensed table-hover tbl-plantas" id="tblPlantas<?php echo $tipo; ?>">
                                <thead>
                                    <tr>
                                        <th>#</th><th>Cod.</th><th>Planta</th><th>Licencia</th>
                                        <th>Veh.</th><th>Chof.</th><th>Tipo</th><th>%</th><th></th>
                                    </tr>
                                </thead>
                                <tbody><tr><td colspan="9">Cargando...</td></tr></tbody>
                            </table>
                        </div>
                    </div>

                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<div id="alertCustomDialog" title="Aviso" style="display:none;"></div>

<div id="modalClasificarPlanta" title="Clasificar planta" style="display:none;">
    <p class="ges-modal-hint">Elija una planta <strong>sin clasificar</strong>. No puede repetirse entre Interno y Externo.</p>
    <input type="hidden" id="modalGesTso" value="">
    <div class="ges-modal-toolbar">
        <div class="input-group input-group-sm">
            <input type="text" id="modalSearchPlanta" class="form-control" placeholder="Buscar planta disponible...">
            <span class="input-group-btn"><button type="button" class="btn btn-default" onclick="cargarPlantasDisponibles();"><i class="glyphicon glyphicon-search"></i></button></span>
        </div>
    </div>
    <div class="ges-modal-grid-scroll">
        <table class="table table-bordered table-condensed table-hover" id="tblPlantasDisponibles">
            <thead>
                <tr>
                    <th style="width:36px;"></th>
                    <th>Planta</th>
                    <th>Licencia</th>
                    <th>Veh.</th>
                    <th>Chof.</th>
                </tr>
            </thead>
            <tbody><tr><td colspan="5">Cargando...</td></tr></tbody>
        </table>
    </div>
    <div class="ges-modal-por">
        <label>% Comision</label>
        <div class="input-group input-group-sm">
            <input type="number" id="modalGesPor" class="form-control" min="0" max="100" step="0.01" value="0">
            <span class="input-group-addon">%</span>
        </div>
    </div>
</div>

<div id="modalIntegrantes" title="Integrantes de la planta" style="display:none;">
    <div class="ges-int-card">
        <div class="ges-int-meta">
            <div class="ges-int-identity">
                <span class="ges-int-kicker">Planta seleccionada</span>
                <h4 class="ges-int-name">
                    <span class="detalle-planta-nom">Planta</span>
                    <span class="ges-int-badge" id="intTipoBadge">—</span>
                </h4>
                <span class="ges-int-lic detalle-planta-lic"></span>
            </div>
            <form class="ges-int-actions" id="formIntegrantes" onsubmit="event.preventDefault(); guardarClasificacionModal();">
                <input type="hidden" id="intPlaCod" value="">
                <input type="hidden" id="intGesTso" value="">
                <label>% Comision</label>
                <div class="input-group input-group-sm">
                    <input type="number" id="intGesPor" class="form-control" min="0" max="100" step="0.01" value="0" required>
                    <span class="input-group-addon">%</span>
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar %</button>
                <button type="button" class="btn btn-danger btn-sm" onclick="quitarClasificacionDesdeModal();" title="Quitar de esta clasificacion">
                    <i class="glyphicon glyphicon-remove"></i> Quitar
                </button>
            </form>
        </div>
        <div class="ges-int-body">
            <div class="ges-upload-overlay" id="gesUploadOverlay">
                <div class="ges-spinner"></div>
                <div class="ges-upload-txt">Subiendo contrato...</div>
            </div>
            <ul class="nav nav-tabs ges-subtabs" role="tablist">
                <li class="active"><a href="#modalTabVeh" data-toggle="tab"><i class="glyphicon glyphicon-road"></i> Vehiculos <span class="ges-tab-count" id="cntModalVeh">0</span></a></li>
                <li><a href="#modalTabCho" data-toggle="tab"><i class="glyphicon glyphicon-user"></i> Choferes <span class="ges-tab-count" id="cntModalCho">0</span></a></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane active" id="modalTabVeh">
                    <div class="ges-grid-sub-wrap ges-int-scroll">
                        <table class="table table-bordered table-condensed table-hover tbl-vehiculos" id="tblModalVehiculos">
                            <thead><tr><th>Placa</th><th>Marca</th><th>Contrato (esta planta)</th><th>Subir</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane" id="modalTabCho">
                    <div class="ges-grid-sub-wrap ges-int-scroll">
                        <table class="table table-bordered table-condensed table-hover tbl-choferes" id="tblModalChoferes">
                            <thead><tr><th>Cedula</th><th>Nombre</th><th>Contrato (esta planta)</th><th>Subir</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
var modalPlaCodSel = null;

function mostrarAlertaUI(titulo, mensaje, tipo, callback) {
  var $dlg = $("#alertCustomDialog");
  if (!$dlg.hasClass("ui-dialog-content")) {
    $dlg.dialog({ autoOpen: false, modal: true, resizable: false, width: 420, appendTo: "body" });
  }
  $dlg.dialog("option", "title", titulo || "Aviso");
  $dlg.html("<p>" + (mensaje || "") + "</p>");
  $dlg.dialog("option", "buttons", [{
    text: "Aceptar", class: "btn btn-sm btn-primary",
    click: function () { $(this).dialog("close"); if (typeof callback === "function") callback(); }
  }]);
  $dlg.dialog("open");
}
function confirmarAlertaUI(titulo, mensaje, onYes) {
  var $dlg = $("#alertCustomDialog");
  if (!$dlg.hasClass("ui-dialog-content")) {
    $dlg.dialog({ autoOpen: false, modal: true, resizable: false, width: 420, appendTo: "body" });
  }
  $dlg.dialog("option", "title", titulo || "Confirmar");
  $dlg.html("<p>" + (mensaje || "") + "</p>");
  $dlg.dialog("option", "buttons", [
    {
      text: "Si, quitar", class: "btn btn-sm btn-danger",
      click: function () {
        $(this).dialog("close");
        if (typeof onYes === "function") onYes();
      }
    },
    {
      text: "Cancelar", class: "btn btn-sm btn-default",
      click: function () { $(this).dialog("close"); }
    }
  ]);
  $dlg.dialog("open");
}
function setUploadLoading(on, texto) {
  var $ov = $("#gesUploadOverlay");
  if (on) {
    $ov.find(".ges-upload-txt").text(texto || "Subiendo contrato...");
    $ov.addClass("show");
  } else {
    $ov.removeClass("show");
  }
}
function htmlEstadoContrato(path, otrasPlantas, contratosOtras) {
  var tiene = path && String(path).trim() !== "";
  if (tiene) {
    var nom = String(path).split("/").pop() || "contrato.pdf";
    return '<a class="ges-contrato-ok" href="' + rutaOk(path) + '" target="_blank" title="' + nom + '"><i class="glyphicon glyphicon-ok-sign"></i> ' + nom + '</a>';
  }
  return '<span class="ges-contrato-no"><i class="glyphicon glyphicon-minus-sign"></i> Sin contrato</span>';
}
function htmlBadgeMultiPlanta(otrasPlantas) {
  var n = parseInt(otrasPlantas, 10) || 0;
  if (n <= 0) return "";
  return '<span class="ges-multi-pla" title="Tambien pertenece a ' + n + ' planta(s)">+' + n + ' planta(s)</span>';
}
function actualizarFilaContrato(kind, cod, path) {
  var $tr = kind === "veh"
    ? $("#tblModalVehiculos tbody tr[data-veh-cod='" + cod + "']")
    : $("#tblModalChoferes tbody tr[data-cho-cod='" + cod + "']");
  if (!$tr.length) return;
  var otras = $tr.attr("data-otras-plantas") || "0";
  var contratosOtras = $tr.attr("data-contratos-otras") || "0";
  $tr.find("td").eq(2).html(htmlEstadoContrato(path, otras, contratosOtras));
  var Pla_Cod = $("#intPlaCod").val();
  $tr.find("td").eq(3).html(htmlUploader(kind, cod, Pla_Cod));
}
function resetBtnUpload($btn) {
  if ($btn && $btn.length) {
    $btn.prop("disabled", false).html('<i class="glyphicon glyphicon-cloud-upload"></i>');
  }
}
function htmlUploader(kind, cod, Pla_Cod) {
  var fileId = (kind === "veh" ? "fileVeh_" : "fileCho_") + cod;
  var nameId = (kind === "veh" ? "nameVeh_" : "nameCho_") + cod;
  var btnId = (kind === "veh" ? "btnUpVeh_" : "btnUpCho_") + cod;
  var clickUp = kind === "veh"
    ? "subirContratoVehiculo(" + cod + "," + Pla_Cod + ")"
    : "subirContratoChofer(" + cod + "," + Pla_Cod + ")";
  return '<div class="ges-upload-row">' +
    '<input type="file" accept=".pdf,application/pdf" id="' + fileId + '" onchange="onGesFilePicked(this,\'' + nameId + '\')">' +
    '<div class="ges-filepick">' +
      '<button type="button" class="ges-filepick-btn" onclick="document.getElementById(\'' + fileId + '\').click();">' +
        '<i class="glyphicon glyphicon-paperclip"></i> PDF' +
      '</button>' +
      '<span class="ges-filepick-name" id="' + nameId + '" title="El sistema asigna el nombre al guardar">Sin archivo</span>' +
      '<button type="button" class="ges-filepick-up" id="' + btnId + '" title="Subir contrato" onclick="' + clickUp + '">' +
        '<i class="glyphicon glyphicon-cloud-upload"></i>' +
      '</button>' +
    '</div>' +
  '</div>';
}
function onGesFilePicked(input, nameId) {
  var $n = $("#" + nameId);
  if (!input || !input.files || !input.files[0]) {
    $n.removeClass("has-file").text("Sin archivo").attr("title", "El sistema asigna el nombre al guardar");
    return;
  }
  $n.addClass("has-file").text("Listo (nombre auto)").attr("title", "Se ignorara el nombre original. El sistema asignara uno estandar.");
}
function labelTipo(t) {
  if (t === "SI") return '<span class="label label-primary">Interno</span>';
  if (t === "SE") return '<span class="label label-warning">Externo</span>';
  if (t === "EV") return '<span class="label label-info">Eventual</span>';
  return '<span class="label label-default">Sin clasificar</span>';
}
function tituloTipo(t) {
  if (t === "SI") return "Socio Interno";
  if (t === "SE") return "Socio Externo";
  if (t === "EV") return "Socio Eventual";
  return t;
}
function rutaOk(p) {
  if (!p) return "";
  p = String(p).trim();
  if (p.indexOf("http") === 0 || p.indexOf("../") === 0) return p;
  return "../" + p.replace(/^\/+/, "");
}
function actualizarStats(tipo, rows) {
  var plantas = rows.length, veh = 0, cho = 0;
  rows.forEach(function (r) {
    veh += parseInt(r.tot_veh, 10) || 0;
    cho += parseInt(r.tot_cho, 10) || 0;
  });
  var $s = $("#stats" + tipo);
  $s.find('[data-k="plantas"]').text(plantas);
  $s.find('[data-k="vehiculos"]').text(veh);
  $s.find('[data-k="choferes"]').text(cho);
  $s.find('[data-k="clasificadas"]').text(plantas);
}
function cargarPlantas(tipo) {
  var search = $('.filtroPlantaForm[data-tipo="' + tipo + '"] input[name="search"]').val() || "";
  var $tb = $("#tblPlantas" + tipo + " tbody");
  $tb.html('<tr><td colspan="9">Cargando...</td></tr>');
  $.getJSON("", { listPlantasGestoraAjax: true, Ges_Tso: tipo, search: search }, function (r) {
    var rows = (r && r.rows) || [];
    actualizarStats(tipo, rows);
    if (!rows.length) {
      $tb.html('<tr><td colspan="9">No hay plantas clasificadas en esta seccion. Use <strong>Clasificar planta</strong>.</td></tr>');
      return;
    }
    var html = "";
    rows.forEach(function (row, i) {
      var por = (row.Ges_Por != null && row.Ges_Por !== "") ? (parseFloat(row.Ges_Por).toFixed(2) + " %") : "-";
      html += "<tr>" +
        "<td>" + (i + 1) + "</td>" +
        "<td>" + (row.Pla_Cod || "") + "</td>" +
        "<td>" + (row.Pla_Nom || "") + "</td>" +
        "<td>" + (row.Pla_Lic || "") + "</td>" +
        "<td class='text-center'>" + (row.tot_veh || 0) + "</td>" +
        "<td class='text-center'>" + (row.tot_cho || 0) + "</td>" +
        "<td class='text-center'>" + labelTipo(row.Ges_Tso) + "</td>" +
        "<td class='text-right'>" + por + "</td>" +
        "<td class='text-center ges-grid-actions'>" +
          "<button type='button' class='btn btn-info btn-xs' title='Integrantes / Contratos' onclick=\"abrirDetallePlanta('" + tipo + "'," + row.Pla_Cod + ")\"><i class='glyphicon glyphicon-folder-open'></i></button> " +
          "<button type='button' class='btn btn-danger btn-xs' title='Quitar clasificacion' onclick=\"quitarClasificacion('" + tipo + "'," + row.Pla_Cod + ",'" + String(row.Pla_Nom || '').replace(/'/g, "\\'") + "')\"><i class='glyphicon glyphicon-remove'></i></button>" +
        "</td>" +
        "</tr>";
    });
    $tb.html(html);
  }).fail(function () {
    $tb.html('<tr><td colspan="9">Error al cargar.</td></tr>');
  });
}
function abrirModalClasificar(tipo) {
  modalPlaCodSel = null;
  $("#modalGesTso").val(tipo);
  $("#modalGesPor").val("0");
  $("#modalSearchPlanta").val("");
  var $dlg = $("#modalClasificarPlanta");
  if (!$dlg.hasClass("ui-dialog-content")) {
    $dlg.dialog({
      autoOpen: false, modal: true, resizable: true, width: 640, appendTo: "body",
      dialogClass: "ges-dlg-clasificar",
      buttons: [
        {
          text: "Guardar clasificacion", class: "btn btn-sm btn-primary",
          click: function () { guardarDesdeModal(); }
        },
        {
          text: "Cancelar", class: "btn btn-sm btn-default",
          click: function () { $(this).dialog("close"); }
        }
      ]
    });
  }
  $dlg.dialog("option", "title", "Clasificar planta como " + tituloTipo(tipo));
  $dlg.dialog("open");
  cargarPlantasDisponibles();
}
function cargarPlantasDisponibles() {
  var search = $("#modalSearchPlanta").val() || "";
  var $tb = $("#tblPlantasDisponibles tbody");
  $tb.html('<tr><td colspan="5">Cargando...</td></tr>');
  modalPlaCodSel = null;
  $.getJSON("", { listPlantasDisponiblesAjax: true, search: search }, function (r) {
    var rows = (r && r.rows) || [];
    if (!rows.length) {
      $tb.html('<tr><td colspan="5">No hay plantas disponibles (todas ya estan clasificadas).</td></tr>');
      return;
    }
    var html = "";
    rows.forEach(function (row) {
      html += "<tr style='cursor:pointer;' onclick='seleccionarPlantaModal(" + row.Pla_Cod + ", this)'>" +
        "<td class='text-center'><input type='radio' name='modalPlaRadio' value='" + row.Pla_Cod + "'></td>" +
        "<td>" + (row.Pla_Nom || "") + "</td>" +
        "<td>" + (row.Pla_Lic || "") + "</td>" +
        "<td class='text-center'>" + (row.tot_veh || 0) + "</td>" +
        "<td class='text-center'>" + (row.tot_cho || 0) + "</td>" +
        "</tr>";
    });
    $tb.html(html);
  }).fail(function () {
    $tb.html('<tr><td colspan="5">Error al cargar.</td></tr>');
  });
}
function seleccionarPlantaModal(Pla_Cod, tr) {
  modalPlaCodSel = Pla_Cod;
  $("#tblPlantasDisponibles tbody tr").removeClass("info");
  $(tr).addClass("info");
  $(tr).find("input[type=radio]").prop("checked", true);
}
function guardarDesdeModal() {
  var tipo = $("#modalGesTso").val();
  var Pla_Cod = modalPlaCodSel;
  var Ges_Por = $("#modalGesPor").val();
  if (!Pla_Cod) {
    mostrarAlertaUI("Atencion", "Seleccione una planta de la lista.");
    return;
  }
  $.post("", { saveGestoraPlantaAjax: true, Pla_Cod: Pla_Cod, Ges_Tso: tipo, Ges_Por: Ges_Por }, function (r) {
    if (r && r.success) {
      $("#modalClasificarPlanta").dialog("close");
      mostrarAlertaUI("Exito", r.message || "Guardado", "success", function () {
        cargarPlantas(tipo);
      });
    } else {
      mostrarAlertaUI("Error", (r && r.message) || "No se pudo guardar");
    }
  }, "json").fail(function () { mostrarAlertaUI("Error", "Error de conexion"); });
}
function abrirDetallePlanta(tipo, Pla_Cod) {
  var $dlg = $("#modalIntegrantes");
  if (!$dlg.hasClass("ui-dialog-content")) {
    $dlg.dialog({
      autoOpen: false, modal: true, resizable: true, width: 820, maxHeight: 620,
      appendTo: "body", dialogClass: "ges-dlg-integrantes",
      buttons: [{
        text: "Cerrar", class: "btn btn-sm btn-default",
        click: function () { $(this).dialog("close"); }
      }]
    });
  }
  $("#intPlaCod").val(Pla_Cod);
  $("#intGesTso").val(tipo);
  $("#intGesPor").val("0");
  $dlg.find(".detalle-planta-nom").text("Cargando...");
  $dlg.find(".detalle-planta-lic").text("");
  var $badge = $("#intTipoBadge");
  $badge.attr("class", "ges-int-badge " + String(tipo || "").toLowerCase()).text(tituloTipo(tipo));
  $("#tblModalVehiculos tbody").html("<tr><td colspan='4'>Cargando...</td></tr>");
  $("#tblModalChoferes tbody").html("<tr><td colspan='4'>Cargando...</td></tr>");
  $("#cntModalVeh").text("0");
  $("#cntModalCho").text("0");
  $dlg.find('.ges-subtabs a[href="#modalTabVeh"]').tab("show");
  $dlg.dialog("option", "title", "Integrantes / Contratos");
  $dlg.dialog("open");

  $.getJSON("", { getPlantaDetalleAjax: true, Pla_Cod: Pla_Cod }, function (r) {
    if (!r || !r.success) {
      mostrarAlertaUI("Error", "No se pudo cargar el detalle.", "error");
      return;
    }
    var p = r.planta || {}, g = r.gestora || {};
    var tipoAct = (g && g.Ges_Tso) ? g.Ges_Tso : tipo;
    var vehiculos = r.vehiculos || [];
    var choferes = r.choferes || [];
    $dlg.find(".detalle-planta-nom").text(p.Pla_Nom || "Planta");
    $dlg.find(".detalle-planta-lic").text(p.Pla_Lic ? "Licencia: " + p.Pla_Lic : "");
    if (g && g.Ges_Est === "A" && g.Ges_Por != null) $("#intGesPor").val(g.Ges_Por);
    $("#intGesTso").val(tipoAct);
    $badge.attr("class", "ges-int-badge " + String(tipoAct || "").toLowerCase()).text(tituloTipo(tipoAct));
    $("#cntModalVeh").text(vehiculos.length);
    $("#cntModalCho").text(choferes.length);

    var vehHtml = "";
    vehiculos.forEach(function (v) {
      vehHtml += "<tr data-veh-cod='" + v.Veh_Cod + "' data-otras-plantas='" + (v.otras_plantas || 0) + "' data-contratos-otras='" + (v.contratos_otras || 0) + "'>" +
        "<td>" + (v.Veh_Pla || "") + htmlBadgeMultiPlanta(v.otras_plantas) + "</td><td>" + (v.Veh_Mar || "") + "</td><td>" +
        htmlEstadoContrato(v.Mav_Con, v.otras_plantas, v.contratos_otras) + "</td><td>" + htmlUploader("veh", v.Veh_Cod, Pla_Cod) + "</td></tr>";
    });
    $("#tblModalVehiculos tbody").html(vehHtml || "<tr><td colspan='4'>No hay vehiculos en esta planta.</td></tr>");

    var choHtml = "";
    choferes.forEach(function (c) {
      choHtml += "<tr data-cho-cod='" + c.Cho_Cod + "' data-otras-plantas='" + (c.otras_plantas || 0) + "' data-contratos-otras='" + (c.contratos_otras || 0) + "'>" +
        "<td>" + (c.Prs_Ced || "") + htmlBadgeMultiPlanta(c.otras_plantas) + "</td><td>" + (c.Cho_Nom || "") + "</td><td>" +
        htmlEstadoContrato(c.Mac_Con, c.otras_plantas, c.contratos_otras) + "</td><td>" + htmlUploader("cho", c.Cho_Cod, Pla_Cod) + "</td></tr>";
    });
    $("#tblModalChoferes tbody").html(choHtml || "<tr><td colspan='4'>No hay choferes en esta planta.</td></tr>");
  });
}
function quitarClasificacion(tipo, Pla_Cod, Pla_Nom) {
  var nom = Pla_Nom || ("planta #" + Pla_Cod);
  confirmarAlertaUI(
    "Quitar clasificacion",
    "¿Retirar <strong>" + nom + "</strong> de la clasificacion <strong>" + tituloTipo(tipo) + "</strong>?",
    function () {
      $.post("", { removeGestoraPlantaAjax: true, Pla_Cod: Pla_Cod, Ges_Tso: tipo }, function (r) {
        if (r && r.success) {
          if ($("#modalIntegrantes").hasClass("ui-dialog-content") && $("#modalIntegrantes").dialog("isOpen")) {
            $("#modalIntegrantes").dialog("close");
          }
          mostrarAlertaUI("Exito", r.message || "Clasificacion retirada", "success", function () { cargarPlantas(tipo); });
        } else {
          mostrarAlertaUI("Error", (r && r.message) || "No se pudo quitar");
        }
      }, "json").fail(function () { mostrarAlertaUI("Error", "Error de conexion"); });
    }
  );
}
function quitarClasificacionDesdeModal() {
  var Pla_Cod = $("#intPlaCod").val();
  var tipo = $("#intGesTso").val();
  var nom = $("#modalIntegrantes .detalle-planta-nom").text() || "";
  if (!Pla_Cod || !tipo) {
    mostrarAlertaUI("Atencion", "No hay planta seleccionada.");
    return;
  }
  quitarClasificacion(tipo, Pla_Cod, nom);
}
function guardarClasificacionModal() {
  var Pla_Cod = $("#intPlaCod").val();
  var tipo = $("#intGesTso").val();
  var Ges_Por = $("#intGesPor").val();
  if (!Pla_Cod) { mostrarAlertaUI("Atencion", "Seleccione una planta."); return; }
  $.post("", { saveGestoraPlantaAjax: true, Pla_Cod: Pla_Cod, Ges_Tso: tipo, Ges_Por: Ges_Por }, function (r) {
    if (r && r.success) mostrarAlertaUI("Exito", r.message || "Guardado", "success", function () { cargarPlantas(tipo); });
    else mostrarAlertaUI("Error", (r && r.message) || "No se pudo guardar");
  }, "json").fail(function () { mostrarAlertaUI("Error", "Error de conexion"); });
}
function subirContratoVehiculo(Veh_Cod, Pla_Cod) {
  var input = document.getElementById("fileVeh_" + Veh_Cod);
  if (!input || !input.files || !input.files[0]) { mostrarAlertaUI("Atencion", "Seleccione un PDF."); return; }
  var $btn = $("#btnUpVeh_" + Veh_Cod);
  var fd = new FormData();
  fd.append("uploadContratoVehiculoAjax", "true");
  fd.append("Veh_Cod", Veh_Cod);
  fd.append("Pla_Cod", Pla_Cod);
  fd.append("contrato", input.files[0]);
  $btn.prop("disabled", true).html('<i class="glyphicon glyphicon-refresh"></i>');
  setUploadLoading(true, "Subiendo contrato de vehiculo...");
  $.ajax({ url: "", type: "POST", data: fd, processData: false, contentType: false, dataType: "json",
    success: function (r) {
      if (r && r.success) {
        actualizarFilaContrato("veh", Veh_Cod, r.path || "");
        mostrarAlertaUI("Exito", r.message || "Contrato subido", "success");
      } else {
        mostrarAlertaUI("Error", (r && r.message) || "Error al subir");
        resetBtnUpload($btn);
      }
    },
    error: function () {
      mostrarAlertaUI("Error", "Error de conexion");
      resetBtnUpload($btn);
    },
    complete: function () { setUploadLoading(false); }
  });
}
function subirContratoChofer(Cho_Cod, Pla_Cod) {
  var input = document.getElementById("fileCho_" + Cho_Cod);
  if (!input || !input.files || !input.files[0]) { mostrarAlertaUI("Atencion", "Seleccione un PDF."); return; }
  var $btn = $("#btnUpCho_" + Cho_Cod);
  var fd = new FormData();
  fd.append("uploadContratoChoferAjax", "true");
  fd.append("Cho_Cod", Cho_Cod);
  fd.append("Pla_Cod", Pla_Cod);
  fd.append("contrato", input.files[0]);
  $btn.prop("disabled", true).html('<i class="glyphicon glyphicon-refresh"></i>');
  setUploadLoading(true, "Subiendo contrato de chofer...");
  $.ajax({ url: "", type: "POST", data: fd, processData: false, contentType: false, dataType: "json",
    success: function (r) {
      if (r && r.success) {
        actualizarFilaContrato("cho", Cho_Cod, r.path || "");
        mostrarAlertaUI("Exito", r.message || "Contrato subido", "success");
      } else {
        mostrarAlertaUI("Error", (r && r.message) || "Error al subir");
        resetBtnUpload($btn);
      }
    },
    error: function () {
      mostrarAlertaUI("Error", "Error de conexion");
      resetBtnUpload($btn);
    },
    complete: function () { setUploadLoading(false); }
  });
}
$(function () {
  cargarPlantas("SI");
  $('a[data-toggle="tab"][data-tipo]').on("shown.bs.tab", function (e) {
    var tipo = $(e.target).data("tipo");
    if (tipo) cargarPlantas(tipo);
  });
  $("#modalSearchPlanta").on("keydown", function (e) {
    if (e.which === 13) { e.preventDefault(); cargarPlantasDisponibles(); }
  });
});
</script>
</body>
</html>
