<?php
// ajax/upload.php
error_reporting(0);
session_start();
header('Content-Type: application/json');

$tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
if (empty($_FILES['file'])) {
    echo json_encode(array('error' => 'No se recibio ningun archivo'));
    exit;
}

$file = $_FILES['file']['tmp_name'];
$fileName = strtolower($_FILES['file']['name']);
$ext = pathinfo($fileName, PATHINFO_EXTENSION);

if ($tipo === 'retenciones_rec' || $tipo === 'retenciones' || $ext === 'xml' || ($ext === 'zip' && ($tipo === 'retenciones_rec' || $tipo === 'retenciones'))) {
    require __DIR__ . '/upload_retenciones.php';
    exit;
}

// Limpiar estimaciones previas en la sesion
foreach($_SESSION as $k => $v) {
    if(strpos($k, 'ct_') === 0 && $k !== 'ct_data' && $k !== 'ct_anio' && $k !== 'ct_regimen' && $k !== 'ct_ruc' && $k !== 'ct_nombre') {
        unset($_SESSION[$k]);
    }
}

if ($ext === 'zip') {
    require_once '../parsers/pdf_text.php';
    if ($tipo == '104') require_once '../parsers/parser_104.php';
    elseif ($tipo == '103') require_once '../parsers/parser_103.php';
    elseif ($tipo == 'renta' || $tipo == '101' || $tipo == '102') require_once '../parsers/parser_101.php';

    $zip = new ZipArchive;
    if ($zip->open($file) === TRUE) {
        if (!isset($_SESSION['ct_data'])) $_SESSION['ct_data'] = array();
        if (!isset($_SESSION['ct_data'][$tipo])) $_SESSION['ct_data'][$tipo] = array();

        $mesesProcesados = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if (strtolower(pathinfo($entryName, PATHINFO_EXTENSION)) == 'pdf') {
                $content = $zip->getFromIndex($i);
                $tmpPdf = tempnam(sys_get_temp_dir(), 'f104_');
                file_put_contents($tmpPdf, $content);
                $txt = cte_pdf_extraer_texto($tmpPdf);
                @unlink($tmpPdf);

                if ($tipo == '104') {
                    $res = parse_104($txt);
                    if (isset($res['mes']) && isset($res['datos'])) {
                        $_SESSION['ct_data']['104'][$res['mes']] = $res['datos'];
                        $mesesProcesados++;
                    }
                } elseif ($tipo == '103') {
                    $res = parse_103($txt);
                    if (isset($res['mes']) && isset($res['datos'])) {
                        $_SESSION['ct_data']['103'][$res['mes']] = $res['datos'];
                        $mesesProcesados++;
                    }
                } elseif ($tipo == 'renta' || $tipo == '101' || $tipo == '102') {
                    $res = parse_101($txt);
                    $_SESSION['ct_data']['renta'] = $res;
                    $mesesProcesados++;
                }
            }
        }
        $zip->close();
        echo json_encode(array('status' => 'ok', 'msg' => "Se cargaron $mesesProcesados formulario(s) desde el ZIP correctamente.", 'mes' => 'Varios'));
        exit;
    } else {
        echo json_encode(array('error' => 'No se pudo abrir el archivo ZIP.'));
        exit;
    }
}

if ($ext === 'xlsx' || $ext === 'xls') {
    if ($tipo === 'exa') {
        $_POST['tipo'] = 'exa_excel';
        require __DIR__ . '/upload_exa_excel.php';
        exit;
    }
    
    if ($tipo !== 'iess') {
        echo json_encode(array('error' => 'Los archivos Excel solo se soportan para planillas del IESS o EXA.'));
        exit;
    }
    require_once '../parsers/parser_iess_excel.php';
    $res = parse_iess_excel($file);
    
    if (isset($res['error'])) {
        echo json_encode($res);
        exit;
    }
    
    if (!isset($_SESSION['ct_data'])) $_SESSION['ct_data'] = array();
    if (!isset($_SESSION['ct_data']['iess'])) $_SESSION['ct_data']['iess'] = array();
    
    foreach($res['meses'] as $m => $datos) {
        $_SESSION['ct_data']['iess'][$m] = $datos;
    }
    
    echo json_encode(array('status' => 'ok', 'msg' => 'Archivo Excel IESS procesado correctamente.'));
    exit;
}

// Extraer texto real usando cte_pdf_extraer_texto
require_once '../parsers/pdf_text.php';
$extractedText = cte_pdf_extraer_texto($file);

// Si extrajo texto, intentamos obtener RUC y Razón Social
$foundRuc = null;
$foundNombre = null;

// Convertimos a mayúsculas para las regex
$textUpper = mb_strtoupper($extractedText, 'UTF-8');

file_put_contents('../uploads/debug_text.log', "EXTRACTED:\n" . $textUpper . "\n\n");

if (preg_match('/\b(\d{10}001)\b/', $textUpper, $m)) {
    $foundRuc = trim($m[1]);
}

$patrones = array(
    '/(?:COMPLETOS|RAZ[OÓ]?N\s+SOCIAL)[\s\:]+([A-Z0-9\s\.,&\-Ñ]{5,60}?)(?:RUC|PER[IÍ]?ODO|N[UÚ]?MERO|TIPO|MONEDA|DECLARACI|104|103|102|\d{13}|\n|$)/im',
    '/(\d{10}001)[\s\:]+([A-Z0-9\s\.,&\-Ñ]{5,60}?)(?:RUC|PER|NUM|TIPO|MONEDA|DEC|\n|$)/im'
);
foreach ($patrones as $p) {
    if (preg_match($p, $textUpper, $m)) {
        $n = trim(preg_replace('/\s+/', ' ', $m[count($m)-1]));
        if (strlen($n) > 3 && $n != 'O APELLIDOS Y NOMBRES COMPLETOS' && !is_numeric(str_replace(' ', '', $n))) {
            $foundNombre = $n;
            break;
        }
    }
}

file_put_contents('../uploads/debug_text.log', "RUC: $foundRuc\nNOMBRE: $foundNombre\n", FILE_APPEND);

if ($foundRuc) $_SESSION['ct_ruc'] = $foundRuc;
if ($foundNombre) $_SESSION['ct_nombre'] = $foundNombre;

// Seguimos usando el texto original o simulado para que los parsers fake funcionen en el demo
$text = "Simulated PDF content. " . $textUpper;
// Randomize month if not present in filename
$mesesStr = array('ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE');
$foundMes = false;

// 1. Extraer todos los meses que tengan un año adjunto (ej. "ABRIL 2026")
// Los PDFs del SRI separan los textos en su formato crudo, así que "PERIODO FISCAL" y "ABRIL 2026" no siempre están juntos.
$mesesRegex = implode('|', $mesesStr);
$bestMesIdx = -1;
$bestYear = 0;

if (preg_match_all('/(' . $mesesRegex . ')[\s\-\/]+(20[2-3][0-9])/i', $textUpper, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $match) {
        $mName = strtoupper($match[1]);
        $y = intval($match[2]);
        
        if ($y > $bestYear) {
            $bestYear = $y;
            $bestMesIdx = array_search($mName, $mesesStr);
        } elseif ($y == $bestYear && $bestMesIdx == 0 && $mName != 'ENERO') {
            // Si empatan en año, pero el mejor actual es ENERO (que suele ser texto de relleno legal), preferimos el nuevo mes detectado.
            $bestMesIdx = array_search($mName, $mesesStr);
        }
    }
}

if ($bestMesIdx >= 0) {
    $mesIdx = $bestMesIdx;
    $text .= $mesesStr[$mesIdx] . " ";
    $foundMes = true;
} else {
    // Buscar en el nombre del archivo si contiene el nombre del mes
    foreach($mesesStr as $idx => $m) {
        if (strpos(strtoupper($fileName), $m) !== false) {
            $mesIdx = $idx;
            $text .= $m . " ";
            $foundMes = true;
            break;
        }
    }
}

// 2. Buscar "MES: 05" o "PERIODO: 05"
if (!$foundMes) {
    if (preg_match('/(?:MES|PERIODO|PER[IÍ]ODO FISCAL|PERIODO\s+FISCAL)[:\s]*(0[1-9]|1[0-2])/i', $textUpper, $m)) {
        $mesIdx = intval($m[1]) - 1;
        $text .= $mesesStr[$mesIdx] . " ";
        $foundMes = true;
    }
}

// 3. Buscar un mes en formato _MM o -MM en el nombre del archivo (ej. form_104_05.pdf)
if (!$foundMes) {
    if (preg_match('/[_\-](0[1-9]|1[0-2])(?:\.pdf|_)/i', $fileName, $m)) {
        $mesIdx = intval($m[1]) - 1;
        $text .= $mesesStr[$mesIdx] . " ";
        $foundMes = true;
    }
}

if (!$foundMes) {
    $mesIdx = rand(0, 11);
    $text .= $mesesStr[$mesIdx] . " ";
    $foundMes = true;
}

try {
    // Dispatch to specific parser
    if ($tipo == '104') {
        require '../parsers/parser_104.php';
        $res = parse_104($text);
    } elseif ($tipo == '103') {
        require '../parsers/parser_103.php';
        $res = parse_103($text);
    } elseif ($tipo == 'iess') {
        require '../parsers/parser_iess.php';
        $res = parse_iess($text);
    } elseif ($tipo == 'renta' || $tipo == '101' || $tipo == '102') {
        require '../parsers/parser_101.php';
        $res = parse_101($textUpper);
    } else {
        $res = array('error' => 'Tipo desconocido');
    }
    
    // Sobrescribir el mes del parser fake con el mes detectado con precisión
    if ($foundMes && isset($mesIdx) && $tipo != 'renta') {
        $res['mes'] = $mesIdx + 1; // 1 to 12
        $res['nombre_mes'] = $mesesStr[$mesIdx];
    }
    if ($bestYear > 0) {
        $res['anio'] = $bestYear;
    }
    
    // Detectar si es una declaración Sustitutiva
    if (isset($res['tipo_declaracion'])) {
        $esSustitutiva = ($res['tipo_declaracion'] === 'SUSTITUTIVA');
    } else {
        $esSustitutiva = (strpos(strtoupper($fileName), 'SUST') !== false || preg_match('/FORMULARIO\s+SUSTITUYE\s*:\s*\d+/iu', $text));
        $res['tipo_declaracion'] = $esSustitutiva ? 'SUSTITUTIVA' : 'ORIGINAL';
    }
    $res['reemplazo_sustitutiva'] = $esSustitutiva;

    // Save to session or return
    if (!isset($res['error'])) {
        if (!isset($_SESSION['ct_data'])) $_SESSION['ct_data'] = array();
        if (!isset($_SESSION['ct_data'][$tipo])) $_SESSION['ct_data'][$tipo] = array();
        
        $meta = array(
            'origen' => 'manual',
            'fecha_sincronizacion' => date('Y-m-d H:i:s'),
            'tipo_declaracion' => $esSustitutiva ? 'SUSTITUTIVA' : 'ORIGINAL',
            'reemplazo_sustitutiva' => $esSustitutiva,
            'fecha_presentacion' => isset($res['fecha_presentacion']) ? $res['fecha_presentacion'] : null,
            'numero_serial' => isset($res['numero_serial']) ? $res['numero_serial'] : null,
            'codigo_verificador' => isset($res['codigo_verificador']) ? $res['codigo_verificador'] : null
        );
        
        // Append data depending on type
        if ($tipo == '104') {
            $mes = $res['mes'];
            $_SESSION['ct_data']['104'][$mes] = $meta + $res['datos'];
        } elseif ($tipo == '103') {
            $mes = $res['mes'];
            $_SESSION['ct_data']['103'][$mes] = $meta + $res['datos'];
        } elseif ($tipo == 'iess') {
            if (isset($res['meses']) && is_array($res['meses'])) {
                // El parser devolvió múltiples meses (Planilla Consolidada)
                foreach ($res['meses'] as $m => $datosMes) {
                    $_SESSION['ct_data']['iess'][$m] = $datosMes;
                }
                $res['nombre_mes'] = count($res['meses']) . ' meses cargados';
            } else {
                // Fallback para planilla individual
                $mes = isset($res['mes']) ? $res['mes'] : (isset($mesIdx) ? $mesIdx + 1 : rand(1, 12));
                $_SESSION['ct_data']['iess'][$mes] = $res['datos'];
                $res['nombre_mes'] = 'Planilla cargada';
            }
        } elseif ($tipo == 'renta') {
            $_SESSION['ct_data']['renta'] = $res;
            $tipo_form = isset($res['tipo_formulario']) ? $res['tipo_formulario'] : '102';
            $anio_form = isset($res['anio']) && $res['anio'] > 0 ? $res['anio'] : '';
            $res['nombre_mes'] = 'F' . $tipo_form . ($anio_form ? ' ' . $anio_form : '');
        }
    }
    
    // Incluir RUC y nombre en la respuesta para actualizar navbar en tiempo real
    $res['ruc_detectado']    = $foundRuc    ?: (isset($_SESSION['ct_ruc'])    ? $_SESSION['ct_ruc']    : null);
    $res['nombre_detectado'] = $foundNombre ?: (isset($_SESSION['ct_nombre']) ? $_SESSION['ct_nombre'] : null);

    echo json_encode($res);
} catch (Exception $e) {
    echo json_encode(array('error' => 'Error al procesar: ' . $e->getMessage()));
}

