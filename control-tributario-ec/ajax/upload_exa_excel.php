<?php
// ajax/upload_exa_excel.php
error_reporting(0);
session_start();
header('Content-Type: application/json');

if (empty($_FILES['file'])) {
    echo json_encode(array('error' => 'No se recibió ningún archivo.'));
    exit;
}

$file = $_FILES['file']['tmp_name'];
$fileName = strtolower($_FILES['file']['name']);
$ext = pathinfo($fileName, PATHINFO_EXTENSION);

if ($ext !== 'xlsx') {
    echo json_encode(array('error' => 'Formato no soportado. Por favor sube un archivo Excel (.xlsx) válido.'));
    exit;
}

// Lightweight XLSX parser compatible with PHP 5.3
function exa_excel_parse_xlsx($filename) {
    $zip = new ZipArchive();
    if ($zip->open($filename) !== true) {
        return array('error' => 'No se pudo abrir el archivo XLSX.');
    }

    // 1. Read shared strings
    $sharedStrings = array();
    $sharedStringsData = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedStringsData) {
        $xml = simplexml_load_string($sharedStringsData);
        if ($xml) {
            foreach ($xml->si as $val) {
                if (isset($val->t)) {
                    $sharedStrings[] = (string)$val->t;
                } else if (isset($val->r)) {
                    $t = '';
                    foreach ($val->r as $r) {
                        $t .= (string)$r->t;
                    }
                    $sharedStrings[] = $t;
                } else {
                    $sharedStrings[] = '';
                }
            }
        }
    }

    // 2. Read sheet1
    $sheetData = $zip->getFromName('xl/worksheets/sheet1.xml');
    if (!$sheetData) {
        $zip->close();
        return array('error' => 'No se encontró la hoja de cálculo de EXA en el archivo.');
    }

    $xml = simplexml_load_string($sheetData);
    if (!$xml) {
        $zip->close();
        return array('error' => 'Error al analizar el contenido de la hoja de cálculo.');
    }

    $rows = array();
    foreach ($xml->sheetData->row as $row) {
        $rowIndex = (int)$row['r'];
        $rowData = array();
        foreach ($row->c as $cell) {
            $r = (string)$cell['r'];
            preg_match('/^([A-Z]+)/', $r, $matches);
            if (empty($matches)) continue;
            $colName = $matches[1];
            $colIndex = 0;
            $len = strlen($colName);
            for ($i = 0; $i < $len; $i++) {
                $colIndex = $colIndex * 26 + (ord($colName[$i]) - 64);
            }
            $colIndex = $colIndex - 1; // 0-based

            $val = '';
            if (isset($cell->v)) {
                $v = (string)$cell->v;
                $t = (string)$cell['t'];
                if ($t === 's') {
                    $val = isset($sharedStrings[$v]) ? $sharedStrings[$v] : '';
                } else {
                    $val = $v;
                }
            }
            $rowData[$colIndex] = $val;
        }
        $rows[$rowIndex] = $rowData;
    }

    $zip->close();

    // Fill missing columns and normalize to 0-based rows
    $maxCol = 0;
    foreach ($rows as $r => $cols) {
        if (!empty($cols)) {
            $m = max(array_keys($cols));
            if ($m > $maxCol) $maxCol = $m;
        }
    }

    $grid = array();
    if (!empty($rows)) {
        $maxRow = max(array_keys($rows));
        for ($r = 1; $r <= $maxRow; $r++) {
            $rowArray = array();
            for ($c = 0; $c <= $maxCol; $c++) {
                $rowArray[] = isset($rows[$r][$c]) ? $rows[$r][$c] : '';
            }
            $grid[] = $rowArray;
        }
    }

    return $grid;
}

$grid = exa_excel_parse_xlsx($file);

if (isset($grid['error'])) {
    echo json_encode($grid);
    exit;
}

// Column Mapping Algorithm by Header Names
$headerRow1Index = -1;
for ($r = 0; $r < count($grid); $r++) {
    for ($c = 0; $c < count($grid[$r]); $c++) {
        if (strpos(strtoupper($grid[$r][$c]), 'MESES') !== false) {
            $headerRow1Index = $r;
            break 2;
        }
    }
}

if ($headerRow1Index === -1) {
    echo json_encode(array('error' => 'No se encontró la cabecera "MESES" en el archivo Excel.'));
    exit;
}

$headerRow2Index = $headerRow1Index + 1;
$headerRow3Index = $headerRow1Index + 2;

$colVentasStart = -1;
$colComprasStart = -1;
$colTotalVentas = -1;
$colTotalCompras = -1;

$colVentas15 = -1;
$colVentas0 = -1;
$colIvaVentas = -1;
$colCompras15 = -1;
$colCompras0 = -1;
$colIvaCompras = -1;

// Scan Row 2 (Ventas, Compras, Totales)
if (isset($grid[$headerRow2Index])) {
    for ($c = 0; $c < count($grid[$headerRow2Index]); $c++) {
        $val = strtoupper(trim($grid[$headerRow2Index][$c]));
        if (strpos($val, 'VENTAS') !== false) {
            $colVentasStart = $c;
        } elseif (strpos($val, 'COMPRAS') !== false) {
            $colComprasStart = $c;
        }
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

// Scan Row 3 (BI 15%, BI 12%, I.V.A., etc.)
if (isset($grid[$headerRow3Index])) {
    for ($c = 0; $c < count($grid[$headerRow3Index]); $c++) {
        $val = strtoupper(trim($grid[$headerRow3Index][$c]));
        if ($colVentasStart !== -1 && $colComprasStart !== -1 && $c >= $colVentasStart && $c < $colComprasStart) {
            // Ventas section
            if (strpos($val, '15%') !== false) $colVentas15 = $c;
            elseif (strpos($val, '0%') !== false) $colVentas0 = $c;
            elseif (strpos($val, 'I.V.A.') !== false || $val === 'IVA') $colIvaVentas = $c;
        } elseif ($colComprasStart !== -1 && $c >= $colComprasStart) {
            // Compras section
            if (strpos($val, '15%') !== false) $colCompras15 = $c;
            elseif (strpos($val, '0%') !== false) $colCompras0 = $c;
            elseif (strpos($val, 'I.V.A.') !== false || $val === 'IVA') $colIvaCompras = $c;
        }
    }
}

// Fallback mappings if headers are slightly different
if ($colTotalVentas === -1) $colTotalVentas = 11;
if ($colTotalCompras === -1) $colTotalCompras = 21;

// Dynamic Form 103 Casilleros Detection
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

// Map Month names to numbers (1 to 12)
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

function upload_exa_clean_number($val) {
    if ($val === null || $val === '') return 0.0;
    $s = trim(strval($val));
    if ($s === '') return 0.0;
    
    $s = preg_replace('/[^\d.,-]/', '', $s);
    
    $lastDot = strrpos($s, '.');
    $lastComma = strrpos($s, ',');
    
    if ($lastDot !== false && $lastComma !== false) {
        if ($lastDot < $lastComma) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else {
            $s = str_replace(',', '', $s);
        }
    } elseif ($lastComma !== false) {
        $afterComma = strlen($s) - $lastComma - 1;
        if ($afterComma === 3) {
            $s = str_replace(',', '', $s);
        } else {
            $s = str_replace(',', '.', $s);
        }
    } elseif ($lastDot !== false) {
        $afterDot = strlen($s) - $lastDot - 1;
        if ($afterDot === 3) {
            $s = str_replace('.', '', $s);
        }
    }
    
    return floatval($s);
}

// Process data rows
for ($r = $headerRow1Index + 3; $r < count($grid); $r++) {
    if (!isset($grid[$r][0]) || empty($grid[$r][0])) continue;
    $mesVal = strtoupper(trim($grid[$r][0]));
    if (strpos($mesVal, 'TOTAL') !== false) continue; // Skip totals row
    
    if (isset($mesesMap[$mesVal])) {
        $mesNum = $mesesMap[$mesVal];
        $ventasTot = isset($grid[$r][$colTotalVentas]) ? upload_exa_clean_number($grid[$r][$colTotalVentas]) : 0.0;
        $comprasTot = isset($grid[$r][$colTotalCompras]) ? upload_exa_clean_number($grid[$r][$colTotalCompras]) : 0.0;
        
        $casList = array();
        $firstCasNo = null;
        $firstCasVal = 0.0;
        foreach ($colF103Casilleros as $cas) {
            $cVal = isset($grid[$r][$cas['col']]) ? upload_exa_clean_number($grid[$r][$cas['col']]) : 0.0;
            $casList[] = array('numero' => $cas['code'], 'valor' => $cVal);
            if ($firstCasNo === null) {
                $firstCasNo = $cas['code'];
                $firstCasVal = $cVal;
            }
        }
        
        $tot103 = isset($grid[$r][$colTotal103]) ? upload_exa_clean_number($grid[$r][$colTotal103]) : 0.0;
        
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

if (!isset($_SESSION['ct_data'])) {
    $_SESSION['ct_data'] = array();
}

$_SESSION['ct_data']['meses_exa'] = $mesesExa;
$_SESSION['ct_data']['exa_excel_grid'] = $grid;

echo json_encode(array(
    'status' => 'ok',
    'msg' => 'Archivo Excel EXA procesado y guardado en sesión correctamente.',
    'exa_data' => $grid,
    'meses_exa' => $mesesExa
));
exit;
