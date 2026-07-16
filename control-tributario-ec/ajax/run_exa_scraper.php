<?php
header('Content-Type: application/json');

// Obtener json desde php://input
$inputData = file_get_contents('php://input');
$request = json_decode($inputData, true);

if (!isset($request['username']) || !isset($request['password'])) {
    echo json_encode(array('success' => false, 'error' => 'Faltan credenciales'));
    exit;
}

$username = $request['username'];
$password = $request['password'];
$empresa = isset($request['empresa']) ? $request['empresa'] : '';

// Crear un config temporal para el scraper
$config = array(
    'username' => $username,
    'password' => $password,
    'empresa' => $empresa,
    'job_id' => isset($request['job_id']) ? $request['job_id'] : 'exa_manual',
    'anio' => isset($request['anio']) ? $request['anio'] : '',
    'mes' => isset($request['mes']) ? $request['mes'] : ''
);

$configBase64 = base64_encode(json_encode($config));
$scraperPath = realpath(__DIR__ . '/../sri_scraper/run_exa_scrape.js');

// Ejecutar node (asegurarse de que node esté en el path)
$cmd = 'node "' . $scraperPath . '" base64:' . $configBase64;

// Como es Windows, podemos usar exec o shell_exec, o abrir en un proceso asíncrono
// Por ahora ejecutamos y esperamos resultado, o lanzamos de fondo.
// Dado que puede tardar, lo ejecutamos
exec($cmd . ' 2>&1', $output, $return_var);

// Determinar éxito: sea porque retornó 0, o porque se generó exitosamente result.json con status ok
$resultFile = dirname($scraperPath) . '/../uploads/sri_auto/' . $config['job_id'] . '/result.json';
$success = ($return_var === 0);
$resJson = null;

if (file_exists($resultFile)) {
    $resJson = json_decode(file_get_contents($resultFile), true);
    if (isset($resJson['status']) && $resJson['status'] === 'ok') {
        $success = true;
    }
}

if (!$success) {
    echo json_encode(array(
        'success' => false,
        'error' => 'Error al ejecutar el scraper',
        'output' => implode("\n", $output)
    ));
} else {
    $archivos = array();
    $exa_data = array();
    $warnings = array();
    
    if ($resJson !== null) {
        if (isset($resJson['archivos'])) {
            $archivos = $resJson['archivos'];
        }
        
        $archivo_path = isset($resJson['archivo_path']) ? $resJson['archivo_path'] : '';
        if (!empty($archivo_path) && file_exists($archivo_path)) {
            require_once __DIR__ . '/../parsers/parser_exa_excel.php';
            $parsed = parse_exa_html_excel($archivo_path);
            if (isset($parsed['exa_data'])) {
                $exa_data = $parsed['exa_data'];
                
                // Procesar la matriz para generar meses_exa
                $grid = $exa_data;
                $headerRow1Index = -1;
                for ($r = 0; $r < count($grid); $r++) {
                    for ($c = 0; $c < count($grid[$r]); $c++) {
                        if (strpos(strtoupper($grid[$r][$c]), 'MESES') !== false) {
                            $headerRow1Index = $r;
                            break 2;
                        }
                    }
                }
                
                if ($headerRow1Index !== -1) {
                    $headerRow2Index = $headerRow1Index + 1;
                    $headerRow3Index = $headerRow1Index + 2;
                    
                    $colVentasStart = -1;
                    $colComprasStart = -1;
                    $colTotalVentas = -1;
                    $colTotalCompras = -1;
                    
                    if (isset($grid[$headerRow2Index])) {
                        for ($c = 0; $c < count($grid[$headerRow2Index]); $c++) {
                            $val = strtoupper(trim($grid[$headerRow2Index][$c]));
                            if (strpos($val, 'VENTAS') !== false) $colVentasStart = $c;
                            elseif (strpos($val, 'COMPRAS') !== false) $colComprasStart = $c;
                        }
                        for ($c = 0; $c < count($grid[$headerRow2Index]); $c++) {
                            $val = strtoupper(trim($grid[$headerRow2Index][$c]));
                            if (strpos($val, 'TOTAL') !== false) {
                                if ($colVentasStart !== -1 && $colComprasStart !== -1 && $c > $colVentasStart && $c < $colComprasStart) {
                                    $colTotalVentas = $c;
                                } elseif ($colComprasStart !== -1 && $c > $colComprasStart) {
                                    $colTotalCompras = $c;
                                }
                            }
                        }
                    }
                    
                    if ($colTotalVentas === -1) {
                        $colTotalVentas = 11;
                        $warnings[] = "No se detectó columna TOTAL VENTAS en fila de sub-headers, se usó posición por defecto (11).";
                    }
                    if ($colTotalCompras === -1) {
                        $colTotalCompras = 21;
                        $warnings[] = "No se detectó columna TOTAL COMPRAS en fila de sub-headers, se usó posición por defecto (21).";
                    }
                    
                    // Form 103 Casilleros Detection
                    $colF103Start = -1;
                    for ($c = 0; $c < count($grid[$headerRow1Index]); $c++) {
                        $val = strtoupper(trim($grid[$headerRow1Index][$c]));
                        if (strpos($val, 'FORMULARIO 103') !== false) {
                            $colF103Start = $c;
                            break;
                        }
                    }
                    
                    $colF103Casilleros = array();
                    $colTotal103 = -1;
                    if ($colF103Start !== -1 && isset($grid[$headerRow2Index])) {
                        for ($c = $colF103Start; $c < count($grid[$headerRow2Index]); $c++) {
                            $val = trim($grid[$headerRow2Index][$c]);
                            if (preg_match('/^\d+$/', $val)) {
                                $colF103Casilleros[] = array('col' => $c, 'code' => intval($val));
                            } elseif (strpos(strtoupper($val), 'TOTAL') !== false) {
                                $colTotal103 = $c;
                            }
                        }
                    }
                    
                    $mesesMap = array(
                        'ENERO' => 1, 'FEBRERO' => 2, 'MARZO' => 3, 'ABRIL' => 4,
                        'MAYO' => 5, 'JUNIO' => 6, 'JULIO' => 7, 'AGOSTO' => 8,
                        'SEPTIEMBRE' => 9, 'OCTUBRE' => 10, 'NOVIEMBRE' => 11, 'DICIEMBRE' => 12
                    );
                    
                    $mesesExa = array();
                    for ($i = 1; $i <= 12; $i++) {
                        $mesesExa[$i] = array(
                            'ventas' => 0.0,
                            'compras' => 0.0,
                            'form103_casillero_numero' => null,
                            'form103_casillero_valor' => 0.0,
                            'form103_total' => 0.0,
                            'form103_casilleros' => array()
                        );
                    }
                    
                    for ($r = $headerRow1Index + 3; $r < count($grid); $r++) {
                        if (!isset($grid[$r][0]) || empty($grid[$r][0])) continue;
                        $mesVal = strtoupper(trim($grid[$r][0]));
                        if (strpos($mesVal, 'TOTAL') !== false) continue;
                        
                        if (isset($mesesMap[$mesVal])) {
                            $mesNum = $mesesMap[$mesVal];
                            $ventasTot = isset($grid[$r][$colTotalVentas]) ? floatval($grid[$r][$colTotalVentas]) : 0.0;
                            $comprasTot = isset($grid[$r][$colTotalCompras]) ? floatval($grid[$r][$colTotalCompras]) : 0.0;
                            
                            $casList = array();
                            $firstCasNo = null;
                            $firstCasVal = 0.0;
                            foreach ($colF103Casilleros as $cas) {
                                $cVal = isset($grid[$r][$cas['col']]) ? floatval($grid[$r][$cas['col']]) : 0.0;
                                $casList[] = array('numero' => $cas['code'], 'valor' => $cVal);
                                if ($firstCasNo === null) {
                                    $firstCasNo = $cas['code'];
                                    $firstCasVal = $cVal;
                                }
                            }
                            
                            $tot103 = isset($grid[$r][$colTotal103]) ? floatval($grid[$r][$colTotal103]) : 0.0;
                            
                            $mesesExa[$mesNum] = array(
                                'ventas' => $ventasTot,
                                'compras' => $comprasTot,
                                'form103_casillero_numero' => $firstCasNo,
                                'form103_casillero_valor' => $firstCasVal,
                                'form103_total' => $tot103,
                                'form103_casilleros' => $casList
                            );
                        }
                    }
                    
                    session_start();
                    if (!isset($_SESSION['ct_data'])) {
                        $_SESSION['ct_data'] = array();
                    }
                    $_SESSION['ct_data']['meses_exa'] = $mesesExa;
                    $_SESSION['ct_data']['exa_excel_grid'] = $grid;
                    session_write_close();
                }
            }
        }
    }

    echo json_encode(array(
        'success' => true,
        'message' => 'Sesión de EXA ERP abierta y extraída.',
        'output' => implode("\n", $output),
        'job_id' => $config['job_id'],
        'archivos' => $archivos,
        'exa_data' => $exa_data,
        'warnings' => $warnings
    ));
}
