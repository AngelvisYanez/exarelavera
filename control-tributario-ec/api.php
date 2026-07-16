<?php
/**
 * API de integración — Control Tributario EC
 * 
 * Uso desde otro sistema PHP:
 * 
 * 1) Incluir vía AJAX (recomendado):
 *    $.post('control-tributario-ec/api.php', { action: 'get_data' }, function(r) { ... }, 'json');
 * 
 * 2) Incluir vía PHP (misma sesión):
 *    require_once 'control-tributario-ec/api.php';
 *    $data = cte_api('get_data');
 * 
 * 3)重定向 a la interfaz completa:
 *    header('Location: control-tributario-ec/');
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/includes/bootstrap.php';

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

switch ($action) {
    case 'init':
        cte_init_session();
        echo json_encode(['ok' => true, 'message' => 'Sesión inicializada']);
        break;

    case 'set_contribuyente':
        $ruc = isset($_REQUEST['ruc']) ? trim($_REQUEST['ruc']) : '';
        $nombre = isset($_REQUEST['nombre']) ? trim($_REQUEST['nombre']) : '';
        $regimen = isset($_REQUEST['regimen']) ? trim($_REQUEST['regimen']) : 'pn';
        $anio = isset($_REQUEST['anio']) ? intval($_REQUEST['anio']) : intval(date('Y'));

        if (empty($ruc)) {
            echo json_encode(['ok' => false, 'error' => 'RUC requerido']);
            break;
        }

        $_SESSION['ct_ruc'] = $ruc;
        $_SESSION['ct_nombre'] = $nombre;
        $_SESSION['ct_regimen'] = $regimen;
        $_SESSION['ct_anio'] = $anio;
        $_SESSION['contribuyente'] = [
            'ruc' => $ruc,
            'razon_social' => $nombre,
            'regimen' => $regimen,
            'anio' => $anio,
        ];

        $params = include __DIR__ . '/config/parametros.php';
        $_SESSION['ct_parametros'] = $params;

        echo json_encode(['ok' => true, 'message' => 'Contribuyente configurado']);
        break;

    case 'upload_pdf':
        require_once __DIR__ . '/parsers/pdf_text.php';
        require_once __DIR__ . '/parsers/parser_sri.php';
        require_once __DIR__ . '/parsers/parser_104.php';
        require_once __DIR__ . '/parsers/parser_103.php';
        require_once __DIR__ . '/parsers/parser_101.php';
        require_once __DIR__ . '/parsers/parser_retenciones_xml.php';

        if (!isset($_FILES['archivo'])) {
            echo json_encode(['ok' => false, 'error' => 'No se recibió archivo']);
            break;
        }

        $file = $_FILES['archivo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        $result = ['ok' => false, 'tipo' => 'desconocido', 'datos' => []];

        if ($ext === 'pdf') {
            $txt = cte_pdf_to_text($file['tmp_name']);
            $parser = new ParserSRI($txt);
            if ($parser->esDeclaracion()) {
                $form = $parser->detectarFormulario();
                if ($form === '104') {
                    $datos = parse_104($txt);
                    $result = ['ok' => true, 'tipo' => 'declaracion_104', 'datos' => $datos];
                    $_SESSION['declaraciones'][] = $datos;
                } elseif ($form === '103') {
                    $datos = parse_103($txt);
                    $result = ['ok' => true, 'tipo' => 'declaracion_103', 'datos' => $datos];
                    $_SESSION['declaraciones'][] = $datos;
                } elseif ($form === '101' || $form === '102') {
                    $datos = parse_101($txt);
                    $result = ['ok' => true, 'tipo' => 'declaracion_' . $form, 'datos' => $datos];
                    $_SESSION['declaraciones'][] = $datos;
                } else {
                    $result = ['ok' => true, 'tipo' => 'declaracion_generico', 'datos' => $parser->extraerTodosLosCampos()];
                }
            } else {
                $datos = $parser->extraerTodosLosCampos();
                $result = ['ok' => true, 'tipo' => 'comprobante', 'datos' => $datos];
                $_SESSION['comprobantes'][] = $datos;
            }
        } elseif ($ext === 'xml') {
            require_once __DIR__ . '/parsers/parser_retenciones_xml.php';
            $xmlContent = file_get_contents($file['tmp_name']);
            $datos = parse_retenciones_xml($xmlContent);
            $result = ['ok' => true, 'tipo' => 'retenciones_xml', 'datos' => $datos];
        } elseif ($ext === 'zip') {
            $tmpDir = sys_get_temp_dir() . '/ct_' . uniqid();
            mkdir($tmpDir, 0755, true);
            $zip = new ZipArchive();
            if ($zip->open($file['tmp_name']) === true) {
                $zip->extractTo($tmpDir);
                $zip->close();
                $allDatos = [];
                foreach (glob($tmpDir . '/*.pdf') as $pdf) {
                    $txt = cte_pdf_to_text($pdf);
                    $parser = new ParserSRI($txt);
                    if ($parser->esDeclaracion()) {
                        $form = $parser->detectarFormulario();
                        if ($form === '104') $allDatos[] = ['tipo' => '104', 'datos' => parse_104($txt)];
                        elseif ($form === '103') $allDatos[] = ['tipo' => '103', 'datos' => parse_103($txt)];
                        elseif ($form === '101' || $form === '102') $allDatos[] = ['tipo' => $form, 'datos' => parse_101($txt)];
                    }
                }
                foreach (glob($tmpDir . '/*.xml') as $xml) {
                    $xmlContent = file_get_contents($xml);
                    $allDatos[] = ['tipo' => 'retenciones_xml', 'datos' => parse_retenciones_xml($xmlContent)];
                }
                $result = ['ok' => true, 'tipo' => 'zip', 'datos' => $allDatos, 'count' => count($allDatos)];
            }
            // Limpiar archivos temporales
            cte_recursive_remove($tmpDir);
        }

        echo json_encode($result);
        break;

    case 'set_datos_manuales':
        $raw = file_get_contents('php://input');
        $req = json_decode($raw, true);
        if (!$req) $req = $_REQUEST;
        if (isset($req['datos_manuales'])) {
            $_SESSION['datos_manuales'] = $req['datos_manuales'];
        }
        echo json_encode(['ok' => true]);
        break;

    case 'recalcular':
        require_once __DIR__ . '/ajax/recalcular.php';
        break;

    case 'get_data':
        $anio = isset($_SESSION['ct_anio']) ? $_SESSION['ct_anio'] : intval(date('Y'));
        $regimen = isset($_SESSION['ct_regimen']) ? $_SESSION['ct_regimen'] : 'pn';
        $params = isset($_SESSION['ct_parametros']) ? $_SESSION['ct_parametros'] : include __DIR__ . '/config/parametros.php';

        echo json_encode([
            'ok' => true,
            'contribuyente' => [
                'ruc' => isset($_SESSION['ct_ruc']) ? $_SESSION['ct_ruc'] : '',
                'nombre' => isset($_SESSION['ct_nombre']) ? $_SESSION['ct_nombre'] : '',
                'regimen' => $regimen,
                'anio' => $anio,
            ],
            'declaraciones' => isset($_SESSION['declaraciones']) ? $_SESSION['declaraciones'] : [],
            'iess' => isset($_SESSION['iess']) ? $_SESSION['iess'] : [],
            'datos_manuales' => isset($_SESSION['datos_manuales']) ? $_SESSION['datos_manuales'] : [],
            'comprobantes' => isset($_SESSION['comprobantes']) ? $_SESSION['comprobantes'] : [],
        ]);
        break;

    case 'generar_excel':
        require_once __DIR__ . '/generadores/excel_generator.php';
        $anio = isset($_SESSION['ct_anio']) ? $_SESSION['ct_anio'] : intval(date('Y'));
        $nombre = isset($_SESSION['ct_nombre']) ? $_SESSION['ct_nombre'] : 'contribuyente';
        $filename = "ControlTributario_{$nombre}_{$anio}.xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        generar_excel_control_tributario($anio);
        exit;

    case 'generar_pdf':
        require_once __DIR__ . '/generadores/pdf_generator.php';
        $anio = isset($_SESSION['ct_anio']) ? $_SESSION['ct_anio'] : intval(date('Y'));
        $nombre = isset($_SESSION['ct_nombre']) ? $_SESSION['ct_nombre'] : 'contribuyente';
        $filename = "ControlTributario_{$nombre}_{$anio}.pdf";
        header('Content-Type: application/pdf');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        generar_pdf_control_tributario($anio);
        exit;

    case 'scrap_sri':
        require_once __DIR__ . '/ajax/modal_sri_auto.php';
        break;

    case 'scrap_exa':
        require_once __DIR__ . '/ajax/run_exa_scraper.php';
        break;

    default:
        echo json_encode([
            'ok' => false,
            'error' => 'Acción no válida',
            'acciones_disponibles' => [
                'init', 'set_contribuyente', 'upload_pdf', 'set_datos_manuales',
                'recalcular', 'get_data', 'generar_excel', 'generar_pdf',
                'scrap_sri', 'scrap_exa',
            ]
        ]);
        break;
}

function cte_recursive_remove($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                $path = $dir . "/" . $object;
                if (is_dir($path)) cte_recursive_remove($path);
                else unlink($path);
            }
        }
        rmdir($dir);
    }
}
