<?php
/**
 * parser_iess_excel.php
 * Lee planillas consolidadas del IESS en formato XLSX o XLS (export CSV/HTML).
 * Implementacion pura PHP 5.3+ — no requiere Python, pandas, ni PhpSpreadsheet.
 */

function parse_iess_excel($ruta) {
    // Intentar leer como XLSX (ZIP nativo)
    $rows = _iess_leer_xlsx($ruta);

    // Si falla, intentar como CSV / TSV / texto delimitado
    if ($rows === false || count($rows) === 0) {
        $rows = _iess_leer_csv($ruta);
    }

    if ($rows === false || count($rows) === 0) {
        return array('error' => 'No se pudo leer el archivo. Asegurate de que sea un Excel (.xlsx) o CSV exportado del IESS.');
    }

    // ---- Mapear columnas ----
    // El reader XLSX ya devuelve las filas de datos con las cabeceras como claves string.
    // La primera fila devuelta contiene el encabezado con claves numericas (indices de columna).
    // Las filas siguientes ya tienen como clave el VALOR del encabezado (ej: 'Periodo', 'Sueldo').
    // Por eso buscamos el nombre real de la columna para usarlo como clave de colMap.
    $colMap = array();
    $dataRows = array();

    if (empty($rows)) {
        return array('error' => 'El archivo no contiene datos legibles.');
    }

    // La primera fila del reader tiene indices enteros con los nombres de columna como VALOR
    $headerRow = $rows[0];
    foreach ($headerRow as $k => $v) {
        $checkStr = strtolower((string)$v);
        // Guardar el valor original (el nombre real de la columna) para usarlo como clave
        if (strpos($checkStr, 'periodo') !== false || strpos($checkStr, 'período') !== false) $colMap['periodo'] = (string)$v;
        if (strpos($checkStr, 'sueldo') !== false || strpos($checkStr, 'remuneracion') !== false || strpos($checkStr, 'remuneraci') !== false) $colMap['sueldo'] = (string)$v;
        if (strpos($checkStr, 'patronal') !== false) $colMap['patronal'] = (string)$v;
        if (strpos($checkStr, 'individual') !== false || strpos($checkStr, 'personal') !== false) $colMap['individual'] = (string)$v;
        if (strpos($checkStr, 'ccc') !== false && strpos($checkStr, '%') === false) {
            $colMap['valor_ccc'] = (string)$v;
        }
    }

    // Si no hallamos nada con la primera fila como cabecera, buscar en todas las filas (por si hay titulos antes)
    if (empty($colMap)) {
        foreach ($rows as $rowIdx => $row) {
            foreach ($row as $k => $v) {
                $checkStr = strtolower((string)$k . ' ' . (string)$v);
                if (strpos($checkStr, 'periodo') !== false || strpos($checkStr, 'período') !== false) $colMap['periodo'] = $k;
                if (strpos($checkStr, 'sueldo') !== false || strpos($checkStr, 'remuneracion') !== false || strpos($checkStr, 'remuneraci') !== false) $colMap['sueldo'] = $k;
                if (strpos($checkStr, 'patronal') !== false) $colMap['patronal'] = $k;
                if (strpos($checkStr, 'individual') !== false || strpos($checkStr, 'personal') !== false) $colMap['individual'] = $k;
                if (strpos($checkStr, 'ccc') !== false && strpos($checkStr, '%') === false) $colMap['valor_ccc'] = $k;
            }
            if (!empty($colMap)) {
                $dataRows = array_slice($rows, $rowIdx + 1);
                break;
            }
        }
    } else {
        // Las filas de datos son las filas 1..N (el reader ya las mapeo con claves string)
        $dataRows = array_slice($rows, 1);
    }

    $sbu = 460;
    if (isset($_SESSION['ct_parametros']['sbu'])) {
        $sbu = floatval($_SESSION['ct_parametros']['sbu']);
    }

    $meses = array();
    $mesesNombresLow = array(
        'enero'=>1,'febrero'=>2,'marzo'=>3,'abril'=>4,'mayo'=>5,'junio'=>6,
        'julio'=>7,'agosto'=>8,'septiembre'=>9,'octubre'=>10,'noviembre'=>11,'diciembre'=>12
    );

    foreach ($dataRows as $row) {
        $periodo   = isset($colMap['periodo'])   && isset($row[$colMap['periodo']])   ? trim((string)$row[$colMap['periodo']])   : '';
        $sueldo    = isset($colMap['sueldo'])    && isset($row[$colMap['sueldo']])    ? floatval(str_replace(',', '.', $row[$colMap['sueldo']]))    : 0;
        $patronal  = isset($colMap['patronal'])  && isset($row[$colMap['patronal']])  ? floatval(str_replace(',', '.', $row[$colMap['patronal']]))  : 0;
        $individual= isset($colMap['individual'])&& isset($row[$colMap['individual']])? floatval(str_replace(',', '.', $row[$colMap['individual']])) : 0;
        $valor_ccc = isset($colMap['valor_ccc'])  && isset($row[$colMap['valor_ccc']]) ? floatval(str_replace(',', '.', $row[$colMap['valor_ccc']]))  : 0;

        // Aceptar formatos: 2025-01, 2025-1, 2025/01, 01/2025, enero 2025, etc.
        $mesNum = 0;
        if (preg_match('/20[2-3][0-9][\/\-](1[0-2]|0?[1-9])/', $periodo, $m)) {
            $parts = preg_split('/[\/\-]/', $m[0]);
            $mesNum = intval($parts[1]);
        } elseif (preg_match('/(1[0-2]|0?[1-9])[\/\-]20[2-3][0-9]/', $periodo, $m)) {
            $parts = preg_split('/[\/\-]/', $m[0]);
            $mesNum = intval($parts[0]);
        } else {
            $periodoLow = strtolower($periodo);
            foreach ($mesesNombresLow as $nombre => $num) {
                if (strpos($periodoLow, $nombre) !== false) {
                    $mesNum = $num;
                    break;
                }
            }
        }

        if ($mesNum < 1 || $mesNum > 12) continue;
        if ($sueldo <= 0 && $patronal <= 0) continue;

        if (!isset($meses[$mesNum])) {
            $meses[$mesNum] = array('empleados' => 0, 'n_bruta' => 0, 'n_pat' => 0, 'n_ind' => 0, 'n_ccc' => 0, 'n_prov1314' => 0, 'n_vac' => 0);
        }

        $meses[$mesNum]['empleados']++;
        $meses[$mesNum]['n_bruta'] += $sueldo;
        $meses[$mesNum]['n_pat']   += $patronal;
        $meses[$mesNum]['n_ind']   += $individual;
        $meses[$mesNum]['n_ccc']   += $valor_ccc;

        // Provisiones
        $d13 = $sueldo / 12;
        $d14 = $sbu / 12;
        $fr  = $sueldo * 0.0833;
        $vac = $sueldo / 24;

        $meses[$mesNum]['n_prov1314'] += ($d13 + $d14 + $fr);
        $meses[$mesNum]['n_vac']      += $vac;
    }

    if (empty($meses)) {
        $debugInfo = 'colMap: ' . json_encode($colMap) . ' | primeras filas: ' . json_encode(array_slice($rows, 0, 3));
        @file_put_contents('../uploads/debug_excel.log', $debugInfo);
        return array('error' => 'No se encontraron registros validos. Verifica que el Excel tenga columnas de Periodo, Sueldo/Remuneracion y Aporte Patronal. Columnas detectadas: ' . implode(', ', array_keys($colMap)));
    }

    $res = array('status' => 'ok', 'meses' => $meses);
    $keys = array_keys($meses);
    $firstMes = $keys[0];
    $res['mes'] = $firstMes;
    $mesesNombresList = array('Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');
    $res['nombre_mes'] = isset($mesesNombresList[$firstMes - 1]) ? $mesesNombresList[$firstMes - 1] : 'Mes ' . $firstMes;

    return $res;
}

/**
 * Lee un archivo XLSX (ZIP con XML internos) sin librerias externas.
 * Devuelve un array de arrays, o false si no es un XLSX valido.
 */
function _iess_leer_xlsx($ruta) {
    $zip = new ZipArchive();
    if ($zip->open($ruta) !== true) {
        return false;
    }

    // Leer shared strings
    $sharedStrings = array();
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml) {
        $ssXml = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $ssXml);
        $ssDom = @simplexml_load_string($ssXml);
        if ($ssDom) {
            foreach ($ssDom->si as $si) {
                $text = '';
                foreach ($si->r as $r) {
                    $text .= (string)$r->t;
                }
                if ($text === '') {
                    $text = (string)$si->t;
                }
                $sharedStrings[] = $text;
            }
        }
    }

    // Encontrar la primera hoja mediante relationships
    $sheetFile = 'xl/worksheets/sheet1.xml';
    $workbookXml = $zip->getFromName('xl/workbook.xml');
    if ($workbookXml) {
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        $relsMap = array();
        if ($relsXml) {
            $relsXml2 = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $relsXml);
            $rels = @simplexml_load_string($relsXml2);
            if ($rels) {
                foreach ($rels->Relationship as $rel) {
                    $id     = (string)$rel['Id'];
                    $target = (string)$rel['Target'];
                    $relsMap[$id] = $target;
                }
            }
        }
        $wbXml = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $workbookXml);
        $wb = @simplexml_load_string($wbXml);
        if ($wb && isset($wb->sheets->sheet)) {
            $firstSheet = $wb->sheets->sheet;
            // r:id puede venir como atributo con namespace; buscamos la clave
            $rId = (string)$firstSheet['r:id'];
            if ($rId === '') {
                // intentar sin namespace
                $attrs = $firstSheet->attributes('r', true);
                if ($attrs && isset($attrs['id'])) $rId = (string)$attrs['id'];
            }
            if ($rId !== '' && isset($relsMap[$rId])) {
                $target = $relsMap[$rId];
                if (strpos($target, '/') === false || strpos($target, 'worksheets') === false) {
                    $target = 'xl/worksheets/' . basename($target);
                } elseif (strpos($target, 'xl/') !== 0) {
                    $target = 'xl/' . $target;
                }
                $sheetFile = $target;
            }
        }
    }

    $sheetXml = $zip->getFromName($sheetFile);
    $zip->close();

    if (!$sheetXml) return false;

    $sheetXml = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $sheetXml);
    $sheet = @simplexml_load_string($sheetXml);
    if (!$sheet) return false;

    $rows = array();
    $headers = null;

    foreach ($sheet->sheetData->row as $row) {
        $rowData = array();
        foreach ($row->c as $cell) {
            $cellRef = (string)$cell['r'];
            $colLetter = preg_replace('/[0-9]/', '', $cellRef);
            $colIdx = _iess_col_to_index($colLetter);
            $type = (string)$cell['t'];
            $val  = (string)$cell->v;

            if ($type === 's') {
                $idx = intval($val);
                $val = isset($sharedStrings[$idx]) ? $sharedStrings[$idx] : '';
            } elseif ($type === 'str') {
                $val = isset($cell->v) ? (string)$cell->v : '';
            } elseif ($type === 'inlineStr') {
                $val = isset($cell->is->t) ? (string)$cell->is->t : '';
            } else {
                if ($val !== '' && is_numeric($val)) {
                    $val = floatval($val);
                }
            }
            $rowData[$colIdx] = $val;
        }

        // Saltar filas completamente vacias
        $nonEmpty = false;
        foreach ($rowData as $rv) {
            if ($rv !== '' && $rv !== 0) { $nonEmpty = true; break; }
        }
        if (!$nonEmpty) continue;

        if ($headers === null) {
            $headers = $rowData;
            $rows[] = $rowData;
        } else {
            $mappedRow = array();
            foreach ($headers as $idx => $hdr) {
                $key = ($hdr !== '') ? $hdr : $idx;
                $mappedRow[$key] = isset($rowData[$idx]) ? $rowData[$idx] : '';
            }
            $rows[] = $mappedRow;
        }
    }

    return $rows;
}

/** Convierte letra(s) de columna Excel (A, B, ..., Z, AA...) a indice entero (0-based) */
function _iess_col_to_index($col) {
    $col = strtoupper($col);
    $idx = 0;
    for ($i = 0; $i < strlen($col); $i++) {
        $idx = $idx * 26 + (ord($col[$i]) - 64);
    }
    return $idx - 1;
}

/**
 * Intenta leer como CSV / TSV / texto delimitado.
 */
function _iess_leer_csv($ruta) {
    $content = @file_get_contents($ruta);
    if ($content === false) return false;

    $lines = preg_split('/\r\n|\r|\n/', trim($content));
    if (count($lines) < 2) return false;

    $firstLine = $lines[0];
    $sep = ',';
    if (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) $sep = "\t";
    elseif (substr_count($firstLine, ';') > substr_count($firstLine, ',')) $sep = ';';

    $rows    = array();
    $headers = null;
    foreach ($lines as $line) {
        if (trim($line) === '') continue;
        $cols = str_getcsv($line, $sep);

        if ($headers === null) {
            $headers = $cols;
            $rows[] = array_combine(range(0, count($cols) - 1), $cols);
            continue;
        }
        $mapped = array();
        foreach ($headers as $i => $h) {
            $key = ($h !== '') ? $h : $i;
            $mapped[$key] = isset($cols[$i]) ? trim($cols[$i]) : '';
        }
        $rows[] = $mapped;
    }
    return $rows;
}
?>
