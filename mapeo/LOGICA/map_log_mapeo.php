<?php
/**
 * Controlador de Lógica y Puntos de Enlace AJAX del Módulo de Mapeo Interactivo
 * Despacho de solicitudes AJAX para eventos georreferenciados, sectores, evidencias fotográficas,
 * flota, manifiestos del día, telemetría y botón de pánico SOS.
 * 
 * Cumple estrictamente con la arquitectura de 3 capas del ERP:
 * FRONT (UI limpia) <-> JS (Asíncrono AJAX) <-> LOGICA (Controlador) <-> SQL (Capa de Base de Datos MySQL)
 * 
 * @package mapeo.LOGICA
 * @version 3.0 (100% MySQL ecoparkmining / PHP 5.3+)
 */

if (!isset($_SESSION)) {
    session_start();
}

// Encabezado estándar para respuestas JSON
header('Content-Type: application/json; charset=utf-8');

// Compatibilidad con PHP 5.3
if (!defined('JSON_PRETTY_PRINT')) {
    define('JSON_PRETTY_PRINT', 0);
}
if (!defined('JSON_UNESCAPED_UNICODE')) {
    define('JSON_UNESCAPED_UNICODE', 0);
}

// Requerir capa de datos y sentencias SQL
require_once(dirname(__FILE__) . '/map_sql_mapeo.php');

// Parámetros de contexto de sesión del ERP
$empresaCod = isset($_SESSION['Ses_Emp_Cod']) && !empty($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 503;
$sucursalCod = isset($_SESSION['Ses_Suc_Cod']) && !empty($_SESSION['Ses_Suc_Cod']) ? (int)$_SESSION['Ses_Suc_Cod'] : 759;
$dispositivoBD = isset($_SESSION['Ses_Dat_Dis']) && !empty($_SESSION['Ses_Dat_Dis']) ? $_SESSION['Ses_Dat_Dis'] : 'ecoparkmining';

// Inicializar conexión
$dbConexion = sql_map_get_conexion($dispositivoBD);

$action = isset($_GET['action']) ? trim($_GET['action']) : '';

switch ($action) {

    // 1. Obtener lista de actividades/eventos registrados desde MySQL con filtros
    case 'get_actividades':
        $filtroTipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
        $filtroEstado = isset($_GET['estado']) ? trim($_GET['estado']) : '';

        $resultado = sql_map_get_actividades($empresaCod, $filtroTipo, $filtroEstado, $dbConexion);
        echo json_encode($resultado);
        break;

    // 2. Guardar o actualizar una actividad/evento georreferenciado en MySQL
    case 'guardar_actividad':
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data || !isset($data['lat']) || !isset($data['lng'])) {
            echo json_encode(array('success' => false, 'message' => 'Datos incompletos para registrar la actividad.'));
            exit;
        }

        $resultado = sql_map_guardar_actividad($data, $empresaCod, $sucursalCod, $dbConexion);
        echo json_encode($resultado);
        break;

    // 3. Eliminar lógicamente una actividad (Act_Est = 'I') en MySQL
    case 'eliminar_actividad':
        $id = isset($_GET['id']) ? trim($_GET['id']) : '';
        if (empty($id)) {
            echo json_encode(array('success' => false, 'message' => 'ID de actividad no especificado.'));
            exit;
        }

        $resultado = sql_map_eliminar_actividad($id, $empresaCod, $dbConexion);
        echo json_encode($resultado);
        break;

    // 4. Obtener sectores y ubicaciones operativas desde finca_actividad (MySQL)
    case 'get_sectores':
        $resultado = sql_map_get_sectores($sucursalCod, $dbConexion);
        echo json_encode($resultado);
        break;

    // 5. Guardar un nuevo sector o zona de trabajo en finca_actividad (MySQL)
    case 'guardar_sector':
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data || empty($data['nombre']) || !isset($data['lat']) || !isset($data['lng'])) {
            echo json_encode(array('success' => false, 'message' => 'El nombre y las coordenadas del sector son obligatorios.'));
            exit;
        }

        $resultado = sql_map_guardar_sector($data, $sucursalCod, $dbConexion);
        echo json_encode($resultado);
        break;

    // 6. Subir y guardar evidencia fotográfica en mapeo/RECURSOS/locator/
    case 'subir_evidencia':
        $dirDestino = dirname(dirname(__FILE__)) . '/RECURSOS/locator/';
        if (!is_dir($dirDestino)) {
            @mkdir($dirDestino, 0777, true);
        }

        $archivoGuardado = null;
        $nombreOriginal = '';
        $tamanoBytes = 0;

        // A) Multipart FormData ($_FILES['evidencia'])
        if (isset($_FILES['evidencia']) && $_FILES['evidencia']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['evidencia']['tmp_name'];
            $nombreOriginal = $_FILES['evidencia']['name'];
            $tamanoBytes = $_FILES['evidencia']['size'];
            $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
            if (!in_array($ext, array('jpg', 'jpeg', 'png', 'webp'))) {
                $ext = 'jpg';
            }
            $nuevoNombre = 'EVI_' . date('Ymd_His') . '_' . substr(md5(uniqid(mt_rand(), true)), 0, 6) . '.' . $ext;
            $rutaDestino = $dirDestino . $nuevoNombre;
            if (move_uploaded_file($tmpName, $rutaDestino)) {
                $archivoGuardado = $nuevoNombre;
            }
        }
        // B) Payload JSON en Base64
        else {
            $input = file_get_contents('php://input');
            $jsonData = json_decode($input, true);
            if ($jsonData && !empty($jsonData['imagen_base64'])) {
                $base64 = $jsonData['imagen_base64'];
                $nombreOriginal = !empty($jsonData['nombre']) ? $jsonData['nombre'] : 'evidencia.jpg';
                $ext = 'jpg';
                if (preg_match('/^data:image\/(\w+);base64,/', $base64, $typeMatch)) {
                    $base64 = substr($base64, strpos($base64, ',') + 1);
                    $extRaw = strtolower($typeMatch[1]);
                    if (in_array($extRaw, array('jpg', 'jpeg', 'png', 'webp'))) {
                        $ext = ($extRaw === 'jpeg') ? 'jpg' : $extRaw;
                    }
                }
                $decoded = base64_decode($base64);
                if ($decoded !== false) {
                    $nuevoNombre = 'EVI_' . date('Ymd_His') . '_' . substr(md5(uniqid(mt_rand(), true)), 0, 6) . '.' . $ext;
                    $rutaDestino = $dirDestino . $nuevoNombre;
                    if (file_put_contents($rutaDestino, $decoded)) {
                        $archivoGuardado = $nuevoNombre;
                        $tamanoBytes = strlen($decoded);
                    }
                }
            }
        }

        if ($archivoGuardado) {
            echo json_encode(array(
                'success' => true,
                'archivo' => $archivoGuardado,
                'url' => '../RECURSOS/locator/' . $archivoGuardado,
                'nombre_original' => $nombreOriginal,
                'tamano' => $tamanoBytes,
                'fecha' => date('Y-m-d H:i:s'),
                'message' => 'Evidencia fotográfica guardada con éxito en locator.'
            ));
        } else {
            echo json_encode(array(
                'success' => false,
                'message' => 'No se pudo almacenar la evidencia fotográfica.'
            ));
        }
        break;

    // 7. Sincronización en lote desde almacenamiento offline a MySQL
    case 'sincronizar_lote_offline':
        $input = file_get_contents('php://input');
        $lote = json_decode($input, true);

        if (!$lote || (!isset($lote['actividades']) && !isset($lote['sectores']))) {
            echo json_encode(array('success' => false, 'message' => 'Lote offline vacío o formato inválido.'));
            exit;
        }

        $resultado = sql_map_sincronizar_lote($lote, $empresaCod, $sucursalCod, $dbConexion);
        echo json_encode($resultado);
        break;

    // 8. Consultar flota de maquinaria y operadores desde MySQL ERP
    case 'get_flota':
        $resultado = sql_map_get_flota($dbConexion);
        echo json_encode($resultado);
        break;

    // 9. Consultar Manifiestos de hoy desde MySQL ERP
    case 'get_manifiestos_hoy':
        $fechaHoy = isset($_GET['fecha']) ? trim($_GET['fecha']) : date('Y-m-d');
        $resultado = sql_map_get_manifiestos_hoy($empresaCod, $fechaHoy, $dbConexion);
        echo json_encode($resultado);
        break;

    // 10. Botón de Pánico y Alerta SOS Georreferenciada en MySQL
    case 'reportar_sos':
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data || !isset($data['lat']) || !isset($data['lng'])) {
            echo json_encode(array('success' => false, 'message' => 'Coordenadas requeridas para activar la alerta SOS.'));
            exit;
        }

        $resultado = sql_map_reportar_sos($data, $empresaCod, $sucursalCod, $dbConexion);
        echo json_encode($resultado);
        break;

    // 11. Consultar alertas de emergencias SOS activas desde MySQL
    case 'get_emergencias':
        $resultado = sql_map_get_emergencias($empresaCod, $dbConexion);
        echo json_encode($resultado);
        break;

    // 12. Telemetría GPS en tiempo real (mantenida en memoria de sesión)
    case 'registrar_gps':
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if ($data && isset($data['lat']) && isset($data['lng'])) {
            $data['timestamp'] = date('Y-m-d H:i:s');
            if (!isset($_SESSION['map_telemetria_gps']) || !is_array($_SESSION['map_telemetria_gps'])) {
                $_SESSION['map_telemetria_gps'] = array();
            }
            $_SESSION['map_telemetria_gps'][] = $data;
            if (count($_SESSION['map_telemetria_gps']) > 150) {
                $_SESSION['map_telemetria_gps'] = array_slice($_SESSION['map_telemetria_gps'], -150);
            }
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false));
        }
        break;

    // 13. Obtener historial GPS para el Reproductor Histórico (Playback)
    case 'get_gps_history':
        $volqueta = isset($_GET['volqueta']) ? trim($_GET['volqueta']) : 'VOL-04';
        $puntos = isset($_SESSION['map_telemetria_gps']) ? $_SESSION['map_telemetria_gps'] : array();
        echo json_encode(array('success' => true, 'volqueta' => $volqueta, 'puntos' => $puntos));
        break;

    // 14. Guardar Canva Vectorial GeoJSON
    case 'guardar_vectores':
        $input = file_get_contents('php://input');
        if ($input) {
            $_SESSION['map_canva_vectores'] = $input;
            echo json_encode(array('success' => true, 'message' => 'Capa vectorial guardada en sesión.'));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Sin contenido GeoJSON.'));
        }
        break;

    // 15. Obtener Canva Vectorial GeoJSON
    case 'get_vectores':
        if (isset($_SESSION['map_canva_vectores']) && !empty($_SESSION['map_canva_vectores'])) {
            echo $_SESSION['map_canva_vectores'];
        } else {
            echo json_encode(array('type' => 'FeatureCollection', 'features' => array()));
        }
        break;

    default:
        echo json_encode(array('success' => false, 'message' => 'Acción no válida o no especificada.'));
        break;
}
