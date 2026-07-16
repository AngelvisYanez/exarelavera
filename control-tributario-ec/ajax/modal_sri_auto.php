<?php
set_time_limit(0);
// c:/xampp/htdocs/control-tributario-ec/ajax/modal_sri_auto.php
error_reporting(0);
set_time_limit(0);
session_start();
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/parsers/pdf_text.php';
require_once dirname(__DIR__) . '/parsers/parser_104.php';
require_once dirname(__DIR__) . '/parsers/parser_103.php';
require_once dirname(__DIR__) . '/parsers/parser_101.php';

function cte_sri_auto_extract_ruc_from_file($filePath, $ext) {
    if ($ext === 'pdf') {
        $txt = cte_pdf_extraer_texto($filePath);
        if (preg_match('/\b(\d{10}001)\b/', $txt, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/\b(\d{10})\b/', $txt, $m)) {
            return trim($m[1]);
        }
    } elseif ($ext === 'xml') {
        $txt = @file_get_contents($filePath);
        if ($txt && preg_match('/<(?:IdInformante|numRuc|ruc|identificacion|rucRepresentante)>(\d{10,13})<\//i', $txt, $m)) {
            return trim($m[1]);
        }
    } elseif ($ext === 'zip') {
        $zip = new ZipArchive();
        if ($zip->open($filePath) === TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $nameInside = $zip->getNameIndex($i);
                if (strtolower(pathinfo($nameInside, PATHINFO_EXTENSION)) === 'xml') {
                    $txt = $zip->getFromIndex($i);
                    if ($txt && preg_match('/<(?:IdInformante|numRuc|ruc|identificacion|rucRepresentante)>(\d{10,13})<\//i', $txt, $m)) {
                        $zip->close();
                        return trim($m[1]);
                    }
                }
            }
            $zip->close();
        }
    }
    return null;
}


$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input) {
    $input = $_POST;
}

$ruc = isset($input['ruc']) ? trim($input['ruc']) : '';
$password = isset($input['password']) ? trim($input['password']) : '';
$anio = isset($input['anio']) ? $input['anio'] : date('Y');
$mes = isset($input['mes']) ? $input['mes'] : 'todos';
$tipo_doc = isset($input['tipo_doc']) ? $input['tipo_doc'] : '104';
$omitir_meses = isset($input['omitir_meses']) && is_array($input['omitir_meses']) ? array_map('intval', $input['omitir_meses']) : array();

if (empty($ruc) || empty($password)) {
    echo json_encode(array('error' => 'Por favor ingresa el RUC y la contraseña del SRI.'));
    exit;
}

$jobId = !empty($input['job_id']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $input['job_id']) : 'job_' . time() . '_' . rand(1000, 9999);
$uploadDir = dirname(__DIR__) . '/uploads/sri_auto/' . $jobId;
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

file_put_contents($uploadDir . '/progress.json', json_encode(array(
    'title' => 'Conectando con srienlinea.sri.gob.ec...',
    'desc' => 'Abriendo navegador y verificando portal del SRI...'
)));

$configPath = $uploadDir . '/config.json';
$configData = array(
    'ruc' => $ruc,
    'password' => $password,
    'anio' => $anio,
    'mes' => $mes,
    'tipo_doc' => $tipo_doc,
    'output_dir' => $uploadDir,
    'omitir_meses' => $omitir_meses
);

array_walk_recursive($configData, function(&$val) {
    if (is_string($val) && !mb_check_encoding($val, 'UTF-8')) {
        $val = mb_convert_encoding($val, 'UTF-8', 'ISO-8859-1');
    }
});
$jsonStr = json_encode($configData);
if (!$jsonStr) {
    echo json_encode(array('error' => 'Error en formato de credenciales (codificación): ' . json_last_error_msg()));
    exit;
}
file_put_contents($configPath, $jsonStr);
$base64Arg = 'base64:' . base64_encode($jsonStr);

// Detectar ruta de node
$nodeExe = 'node';
if (file_exists('C:\\Program Files\\nodejs\\node.exe')) {
    $nodeExe = '"C:\\Program Files\\nodejs\\node.exe"';
}

// Ejecutar el scraper nativo de Playwright localmente en el servidor pasándole base64 para evitar race conditions de IO en Windows
$scraperScript = dirname(__DIR__) . '/sri_scraper/run_scrape.js';
if ($tipo_doc === 'iess') {
    $scraperScript = dirname(__DIR__) . '/sri_scraper/run_iess_scrape.js';
}
$cmd = $nodeExe . ' "' . $scraperScript . '" "' . $base64Arg . '" 2>&1';

exec($cmd, $output, $returnCode);
$errorLog = implode("\n", $output);
file_put_contents($uploadDir . '/last_debug.log', "CMD: $cmd\nRET: $returnCode\nOUT:\n$errorLog");

$resultPath = $uploadDir . '/result.json';
if (file_exists($resultPath)) {
    $res = json_decode(file_get_contents($resultPath), true);
    
    if (isset($res['status']) && $res['status'] === 'ok') {
        // Aplicar la regla de prioridad: Sustitutiva sobre Original
        $esSustitutiva = isset($res['sustitutiva_aplicada']) && $res['sustitutiva_aplicada'];
        $unmigratedFiles = array();
        
        require_once __DIR__ . '/../parsers/pdf_text.php';
        if ($tipo_doc === '104') require_once __DIR__ . '/../parsers/parser_104.php';
        elseif ($tipo_doc === '103') require_once __DIR__ . '/../parsers/parser_103.php';
        elseif ($tipo_doc === 'renta' || $tipo_doc === '101' || $tipo_doc === '102') require_once __DIR__ . '/../parsers/parser_101.php';
        elseif ($tipo_doc === 'iess') {
            require_once __DIR__ . '/../parsers/parser_iess.php';
            require_once __DIR__ . '/../parsers/parser_iess_excel.php';
        }

        if (isset($res['archivos']) && is_array($res['archivos'])) {
            if ($tipo_doc === 'retenciones_rec' || $tipo_doc === 'retenciones') {
                require_once __DIR__ . '/../parsers/parser_retenciones_xml.php';
                $resultados = array();
                $analisis = array(
                    'docs_por_mes' => array(),
                    'agentes' => array(),
                    'codigos' => array(),
                    'total_docs' => 0
                );

                $zipName = "RETENCIONES_RECIBIDAS_" . $anio . "_" . $ruc . ".zip";
                $zipPath = $uploadDir . '/' . $zipName;
                $zip = new ZipArchive;
                $zipCreated = ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE);

                foreach ($res['archivos'] as $fNombre) {
                    if (strtolower(pathinfo($fNombre, PATHINFO_EXTENSION)) === 'xml') {
                        $rutaXml = $uploadDir . '/' . $fNombre;
                        if (file_exists($rutaXml)) {
                            $xmlContent = file_get_contents($rutaXml);
                            parsearRetencionXML($xmlContent, $resultados, $analisis);
                            if ($zipCreated) {
                                $zip->addFile($rutaXml, $fNombre);
                            }
                        }
                    }
                }
                if ($zipCreated) {
                    $zip->close();
                    $homeDir = getenv("USERPROFILE") ?: getenv("HOME");
                    if ($homeDir && file_exists($homeDir . '/Downloads')) {
                        @copy($zipPath, $homeDir . '/Downloads/' . $zipName);
                    } elseif ($homeDir && file_exists($homeDir . '/Descargas')) {
                        @copy($zipPath, $homeDir . '/Descargas/' . $zipName);
                    }
                }

                if (!isset($_SESSION['ct_data'])) $_SESSION['ct_data'] = array();
                $_SESSION['ct_data']['retenciones_rec'] = $resultados;
                $_SESSION['ct_data']['ret_analisis'] = $analisis;
            } elseif ($tipo_doc === 'ats') {
                if (!isset($res['archivos']) || !is_array($res['archivos'])) $res['archivos'] = array();
                // Ya no escaneamos la carpeta ni adivinamos huérfanos. Playwright reporta exactamente los archivos descargados.
            } else {
                $mesesNombresArr = array('ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE');
                $mesesPermitidos = array();
                if (is_numeric($mes) && intval($mes) >= 1 && intval($mes) <= 12) {
                    $mesesPermitidos[] = $mesesNombresArr[intval($mes)-1];
                } else {
                    $mesesPermitidos = $mesesNombresArr;
                }

                if (!isset($res['archivos']) || !is_array($res['archivos'])) $res['archivos'] = array();
                // Ya no escaneamos directorios externos, solo usamos los archivos reportados.
                
                $archivosFiltrados = array();
                foreach ($res['archivos'] as $fNombre) {
                    if (strtolower(pathinfo($fNombre, PATHINFO_EXTENSION)) === 'pdf') {
                        $coincideMes = false;
                        if ($tipo_doc === 'renta' || $tipo_doc === '101' || $tipo_doc === '102' || $tipo_doc === 'iess') {
                            $coincideMes = true;
                        } else {
                            foreach ($mesesPermitidos as $mPerm) {
                                if (stripos($fNombre, $mPerm) !== false) {
                                    $coincideMes = true;
                                    break;
                                }
                            }
                        }
                        if ($coincideMes) $archivosFiltrados[] = $fNombre;
                    } else {
                        $archivosFiltrados[] = $fNombre;
                    }
                }

                
                // Agrupar archivos por mes y mantener solo el mas reciente (por mtime)
                $archivosPorMes = array();
                $archivosSinMes = array();
                foreach ($archivosFiltrados as $fNombre) {
                    $rutaFile = $uploadDir . '/' . $fNombre;
                    if (!file_exists($rutaFile)) continue;
                    
                    $mtime = filemtime($rutaFile);
                    $mesDetectado = null;
                    if ($tipo_doc !== 'renta' && $tipo_doc !== '101' && $tipo_doc !== '102' && $tipo_doc !== 'iess') {
                        foreach ($mesesPermitidos as $idx => $mPerm) {
                            if (stripos($fNombre, $mPerm) !== false) {
                                $mesDetectado = $idx + 1;
                                break;
                            }
                        }
                    }
                    
                    if ($mesDetectado !== null) {
                        if (!isset($archivosPorMes[$mesDetectado]) || $mtime > $archivosPorMes[$mesDetectado]['mtime']) {
                            $archivosPorMes[$mesDetectado] = array('name' => $fNombre, 'mtime' => $mtime);
                        }
                    } else {
                        $archivosSinMes[] = $fNombre;
                    }
                }
                
                $archivosUnicos = array();
                foreach ($archivosPorMes as $item) {
                    $archivosUnicos[] = $item['name'];
                }
                $archivosUnicos = array_merge($archivosUnicos, $archivosSinMes);
                $res['archivos'] = $archivosUnicos;

                if (!isset($res['datos_meses']) || !is_array($res['datos_meses'])) $res['datos_meses'] = array();

                $archivosFiltradosFinal = array();
                foreach ($res['archivos'] as $fNombre) {
                    if (strtolower(pathinfo($fNombre, PATHINFO_EXTENSION)) === 'pdf') {
                        $rutaPdf = $uploadDir . '/' . $fNombre;
                        if (file_exists($rutaPdf)) {
                            $txtPdf = cte_pdf_extraer_texto($rutaPdf);
                            
                            // Extraer RUC y Razon Social para actualizar la session globalmente y validar
                            $textUpper = mb_strtoupper($txtPdf, 'UTF-8');
                            $pdfRuc = null;
                            if (preg_match('/\b(\d{10}001)\b/', $textUpper, $mRuc)) {
                                $pdfRuc = trim($mRuc[1]);
                            }

                            // Si el PDF contiene un RUC y este no coincide con el consultado, lo descartamos
                            if ($pdfRuc !== null && $pdfRuc !== $ruc) {
                                @unlink($rutaPdf);
                                continue;
                            }

                            if ($pdfRuc !== null) {
                                $_SESSION['ct_ruc'] = $pdfRuc;
                            }

                            $patrones = array(
                                '/(?:COMPLETOS|RAZ[OÓ]?N\s+SOCIAL)[\s\:]+([A-Z0-9\s\.,&\-Ñ]{5,60}?)(?:RUC|PER[IÍ]?ODO|N[UÚ]?MERO|TIPO|MONEDA|DECLARACI|104|103|102|\d{13}|\n|$)/im',
                                '/(\d{10}001)[\s\:]+([A-Z0-9\s\.,&\-Ñ]{5,60}?)(?:RUC|PER|NUM|TIPO|MONEDA|DEC|\n|$)/im'
                            );
                            foreach ($patrones as $p) {
                                if (preg_match($p, $textUpper, $mNom)) {
                                    $n = trim(preg_replace('/\s+/', ' ', $mNom[count($mNom)-1]));
                                    if (strlen($n) > 3 && $n != 'O APELLIDOS Y NOMBRES COMPLETOS' && !is_numeric(str_replace(' ', '', $n))) {
                                        $_SESSION['ct_nombre'] = $n;
                                        break;
                                    }
                                }
                            }

                            if ($tipo_doc === '104') {
                                $parsed = parse_104($txtPdf);
                                
                                // Sobrescribir mes detectado por parse_104 con el del nombre del archivo si es posible
                                foreach ($mesesPermitidos as $idx => $mPerm) {
                                    if (stripos($fNombre, $mPerm) !== false) {
                                        $parsed['mes'] = $idx + 1;
                                        break;
                                    }
                                }
                                
                                if (isset($parsed['mes']) && isset($parsed['datos'])) {
                                    $mesReal = intval($parsed['mes']);
                                    if (isset($parsed['fecha_presentacion'])) {
                                        $parsed['datos']['fecha_presentacion'] = $parsed['fecha_presentacion'];
                                    }
                                    if (isset($parsed['tipo_declaracion'])) {
                                        $parsed['datos']['tipo_declaracion'] = $parsed['tipo_declaracion'];
                                    }
                                    if (stripos($fNombre, '_SUST') !== false) {
                                        $parsed['datos']['tipo_declaracion'] = 'SUSTITUTIVA';
                                    }
                                    $res['datos_meses'][$mesReal] = $parsed['datos'];
                                }
                            } elseif ($tipo_doc === '103') {
                                $parsed = parse_103($txtPdf);
                                
                                // Sobrescribir mes detectado
                                foreach ($mesesPermitidos as $idx => $mPerm) {
                                    if (stripos($fNombre, $mPerm) !== false) {
                                        $parsed['mes'] = $idx + 1;
                                        break;
                                    }
                                }
                                
                                if (isset($parsed['mes']) && isset($parsed['datos'])) {
                                    $mesReal = intval($parsed['mes']);
                                    if (isset($parsed['fecha_presentacion'])) {
                                        $parsed['datos']['fecha_presentacion'] = $parsed['fecha_presentacion'];
                                    }
                                    if (isset($parsed['tipo_declaracion'])) {
                                        $parsed['datos']['tipo_declaracion'] = $parsed['tipo_declaracion'];
                                    }
                                    if (stripos($fNombre, '_SUST') !== false) {
                                        $parsed['datos']['tipo_declaracion'] = 'SUSTITUTIVA';
                                    }
                                    $res['datos_meses'][$mesReal] = $parsed['datos'];
                                }
                            } elseif ($tipo_doc === 'renta' || $tipo_doc === '101' || $tipo_doc === '102') {
                                $parsed = parse_101($txtPdf);
                                $parsed['origen'] = 'sri_auto';
                                $parsed['fecha_sincronizacion'] = date('Y-m-d H:i:s');
                                $parsed['anio'] = $anio;
                                $_SESSION['ct_data']['renta'] = $parsed;
                            } elseif ($tipo_doc === 'iess') {
                                $txtPdfIess = cte_pdf_extraer_texto($rutaPdf);
                                // Si pdftotext falla, intentar leer el archivo directamente (si es txt disfrazado de pdf)
                                if (strlen(trim($txtPdfIess)) < 20) {
                                    $txtPdfIess = file_get_contents($rutaPdf);
                                }
                                if (!isset($_SESSION['ct_data']['iess'])) $_SESSION['ct_data']['iess'] = array();
                                $parsed = parse_iess($txtPdfIess);
                                if (!isset($parsed['error'])) {
                                    if (isset($parsed['meses']) && is_array($parsed['meses'])) {
                                        foreach ($parsed['meses'] as $m => $datosMes) {
                                            $_SESSION['ct_data']['iess'][$m] = $datosMes;
                                        }
                                    } elseif (isset($parsed['mes']) && isset($parsed['datos'])) {
                                        $mesReal = intval($parsed['mes']);
                                        $_SESSION['ct_data']['iess'][$mesReal] = $parsed['datos'];
                                    }
                                }
                            }
                            $archivosFiltradosFinal[] = $fNombre;
                        }
                    } elseif (strtolower(pathinfo($fNombre, PATHINFO_EXTENSION)) === 'txt' && $tipo_doc === 'iess') {
                        $rutaTxt = $uploadDir . '/' . $fNombre;
                        if (file_exists($rutaTxt)) {
                            $txtDoc = file_get_contents($rutaTxt);
                            $parsed = parse_iess($txtDoc);
                            if (isset($parsed['mes']) && isset($parsed['datos'])) {
                                $mesReal = intval($parsed['mes']);
                                $_SESSION['ct_data']['iess'][$mesReal] = $parsed['datos'];
                            } elseif (isset($parsed['meses']) && is_array($parsed['meses'])) {
                                foreach ($parsed['meses'] as $m => $datosMes) {
                                    $_SESSION['ct_data']['iess'][$m] = $datosMes;
                                }
                            }
                        }
                        $archivosFiltradosFinal[] = $fNombre;
                    } elseif (in_array(strtolower(pathinfo($fNombre, PATHINFO_EXTENSION)), array('xls', 'xlsx')) && $tipo_doc === 'iess') {
                        $rutaExcel = $uploadDir . '/' . $fNombre;
                        if (file_exists($rutaExcel)) {
                            $parsed = parse_iess_excel($rutaExcel);
                            if (!isset($parsed['error']) && isset($parsed['meses'])) {
                                foreach ($parsed['meses'] as $m => $datosMes) {
                                    $_SESSION['ct_data']['iess'][$m] = $datosMes;
                                }
                            }
                        }
                        $archivosFiltradosFinal[] = $fNombre;
                    } else {
                        $archivosFiltradosFinal[] = $fNombre;
                    }
                }
                $res['archivos'] = $archivosFiltradosFinal;
            }
        }

        if (!isset($_SESSION['ct_data'])) $_SESSION['ct_data'] = array();
        if (!isset($_SESSION['ct_data'][$tipo_doc])) $_SESSION['ct_data'][$tipo_doc] = array();
        
        // Limpiar estimaciones previas
        foreach($_SESSION as $k => $v) {
            if(strpos($k, 'ct_') === 0 && $k !== 'ct_data' && $k !== 'ct_anio' && $k !== 'ct_regimen' && $k !== 'ct_ruc' && $k !== 'ct_nombre') {
                unset($_SESSION[$k]);
            }
        }
        
        $esRentaDoc = ($tipo_doc === 'renta' || $tipo_doc === '101' || $tipo_doc === '102');
        $esRetencionesRec = ($tipo_doc === 'retenciones_rec' || $tipo_doc === 'retenciones');
        if (!$esRentaDoc && !$esRetencionesRec) {
            if (!empty($res['datos_meses']) && is_array($res['datos_meses'])) {
                foreach ($res['datos_meses'] as $mesNum => $casilleros) {
                    if (!empty($casilleros) && is_array($casilleros)) {
                        $meta = array(
                            'origen' => 'sri_auto',
                            'fecha_sincronizacion' => date('Y-m-d H:i:s'),
                            'tipo_declaracion' => $esSustitutiva ? 'SUSTITUTIVA' : 'ORIGINAL',
                            'reemplazo_sustitutiva' => $esSustitutiva
                        );
                        $_SESSION['ct_data'][$tipo_doc][$mesNum] = $meta + $casilleros;
                    }
                }
            }
        }
        
        // Si el usuario ingresó un RUC, actualizar sesión
        if ($ruc) $_SESSION['ct_ruc'] = $ruc;
        
        // Crear un comprobante descargable en HTML con los datos sincronizados para verificación del usuario
        $mesesNombres = array('ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE');
        $mesNombreStr = is_numeric($mes) && $mes >= 1 && $mes <= 12 ? $mesesNombres[intval($mes)-1] : 'ANUAL';
        
        $prefijo = "IVA_104_{$mesNombreStr}_{$anio}_{$ruc}";
        if ($tipo_doc === '103') $prefijo = "RETENCIONES_103_{$mesNombreStr}_{$anio}_{$ruc}";
        elseif ($esRentaDoc) $prefijo = "RENTA_ANUAL_101_102_{$anio}_{$ruc}";
        elseif ($esRetencionesRec) $prefijo = "RETENCIONES_RECIBIDAS_{$mesNombreStr}_{$anio}_{$ruc}";
        elseif ($tipo_doc === 'ats') $prefijo = "ATS_{$mesNombreStr}_{$anio}_{$ruc}";
        elseif ($tipo_doc === 'iess') $prefijo = "PLANILLA_IESS_{$anio}_{$ruc}";
        
        $nombreComprobante = "{$prefijo}_Comprobante.html";
        $rutaComprobante = $uploadDir . '/' . $nombreComprobante;
        
        $htmlContenido = "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Comprobante de Descarga SRI</title><style>body{font-family:Arial,sans-serif;margin:30px;color:#333;}table{width:100%;border-collapse:collapse;margin-top:20px;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}.header{background:#1e3a8a;color:#fff;padding:15px;border-radius:5px;}</style></head><body>";
        $htmlContenido .= "<div class='header'><h2>Comprobante de Sincronización - Portal SRI en Línea</h2></div>";
        $htmlContenido .= "<p><strong>RUC:</strong> {$ruc} | <strong>Período Fiscal:</strong> {$anio} | <strong>Módulo:</strong> " . ($esRetencionesRec ? 'Retenciones Recibidas Electrónicas (XML)' : ($esRentaDoc ? 'Impuesto a la Renta 101/102' : ($tipo_doc === 'ats' ? 'Anexo Transaccional Simplificado (ATS)' : $tipo_doc))) . "</p>";
        
        if ($esRetencionesRec) {
            $totalDocs = isset($analisis['total_docs']) ? $analisis['total_docs'] : 0;
            $numAgentes = isset($analisis['agentes']) ? count($analisis['agentes']) : 0;
            $htmlContenido .= "<table><thead><tr><th>Mes / Período</th><th>Agentes de Retención Detectados</th><th>Total Comprobantes Descargados</th></tr></thead><tbody>";
            $htmlContenido .= "<tr><td>{$mesNombreStr} {$anio}</td><td>{$numAgentes} agentes</td><td><strong>{$totalDocs} archivos XML</strong></td></tr>";
            $htmlContenido .= "</tbody></table>";
            if (isset($analisis['codigos']) && is_array($analisis['codigos']) && count($analisis['codigos']) > 0) {
                $htmlContenido .= "<h3 style='margin-top:20px;'>Resumen por Concepto Retenido</h3><table><thead><tr><th>Concepto / Código</th><th>Nro. Veces</th><th>Total Base Imponible</th><th>Total Retenido</th></tr></thead><tbody>";
                foreach ($analisis['codigos'] as $codRow) {
                    $htmlContenido .= "<tr><td>{$codRow['codigo']}</td><td>{$codRow['veces']}</td><td>$" . number_format($codRow['base'], 2) . "</td><td><strong>$" . number_format($codRow['retenido'], 2) . "</strong></td></tr>";
                }
                $htmlContenido .= "</tbody></table>";
            }
        } elseif ($esRentaDoc) {
            $rentaDatos = isset($_SESSION['ct_data']['renta']['datos']) ? $_SESSION['ct_data']['renta']['datos'] : array();
            $htmlContenido .= "<table><thead><tr><th>Año Fiscal</th><th>Total Activos (499)</th><th>Total Pasivos (599)</th><th>Patrimonio Net (698)</th><th>Ingresos (6999)</th><th>Costos/Gastos (7999)</th><th>Impuesto Causado (850)</th></tr></thead><tbody>";
            $htmlContenido .= "<tr><td>Año {$anio}</td><td>$" . number_format(isset($rentaDatos['499'])?$rentaDatos['499']:0, 2) . "</td><td>$" . number_format(isset($rentaDatos['599'])?$rentaDatos['599']:0, 2) . "</td><td>$" . number_format(isset($rentaDatos['698'])?$rentaDatos['698']:0, 2) . "</td><td>$" . number_format(isset($rentaDatos['6999'])?$rentaDatos['6999']:0, 2) . "</td><td>$" . number_format(isset($rentaDatos['7999'])?$rentaDatos['7999']:0, 2) . "</td><td><strong>$" . number_format(isset($rentaDatos['850'])?$rentaDatos['850']:0, 2) . "</strong></td></tr>";
        } elseif ($tipo_doc === 'ats') {
                if (!isset($res['archivos']) || !is_array($res['archivos'])) $res['archivos'] = array();
                // Ya no escaneamos la carpeta ni adivinamos huérfanos. Playwright reporta exactamente los archivos descargados.
            } else {
            if (!isset($listaMeses)) {
                if ($mes === 'todos') $listaMeses = range(1, 12);
                elseif ($mes === 'sem1') $listaMeses = range(1, 6);
                elseif ($mes === 'sem2') $listaMeses = range(7, 12);
                else $listaMeses = array(intval($mes));
            }
            $htmlContenido .= "<table><thead><tr><th>Mes</th><th>Ventas 15% (401)</th><th>Ventas Netas (411)</th><th>Compras 15% (500)</th><th>Impuesto Causado (601)</th><th>Impuesto Pagado (999)</th></tr></thead><tbody>";
            $sufijoSO = $esSustitutiva ? ' (S)' : ' (O)';
            foreach ($listaMeses as $m) {
                $c = isset($res['datos_meses'][$m]) ? $res['datos_meses'][$m] : array();
                $htmlContenido .= "<tr><td>Mes {$m}{$sufijoSO}</td><td>$" . number_format(isset($c['401'])?$c['401']:0, 2) . "</td><td>$" . number_format(isset($c['411'])?$c['411']:0, 2) . "</td><td>$" . number_format(isset($c['500'])?$c['500']:0, 2) . "</td><td>$" . number_format(isset($c['601'])?$c['601']:0, 2) . "</td><td><strong>$" . number_format(isset($c['999'])?$c['999']:0, 2) . "</strong></td></tr>";
            }
        }
        $htmlContenido .= "</tbody></table></body></html>";
        file_put_contents($rutaComprobante, $htmlContenido);

        if (!isset($res['archivos']) || !is_array($res['archivos'])) {
            $res['archivos'] = array();
        }
        if (!in_array($nombreComprobante, $res['archivos'])) {
            array_unshift($res['archivos'], $nombreComprobante);
        }

        $res['mes'] = $mes;
        $res['reemplazo_sustitutiva'] = $esSustitutiva;
        $res['job_dir_url'] = 'uploads/sri_auto/' . basename($uploadDir);
        $res['unmigrated'] = array();
        
        // Save the migration results to a tracking file on the server
        @file_put_contents(dirname(__DIR__) . '/ajax/migration_result.json', json_encode(array(
            'migrated' => array(),
            'unmigrated' => $res['unmigrated']
        ), JSON_PRETTY_PRINT));
    }
    
    echo json_encode($res);
} else {
    echo json_encode(array(
        'error' => 'Fallo en la ejecución del scraper nativo Playwright. Detalle: ' . ($errorLog ?: 'Sin salida de consola (código ' . $returnCode . ')'),
        'debug' => $errorLog,
        'cmd' => $cmd
    ));
}

