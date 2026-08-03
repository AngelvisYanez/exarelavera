<?php
/**
 * Lector XLSX nativo (ZipArchive + SimpleXML).
 * Corrige archivos LibreOffice Calc donde rId != numero de hoja.
 */

/**
 * @param string $ref
 * @return int
 */
function ppto_xlsx_col_index($ref) {
    if (!preg_match('/^([A-Z]+)/i', (string)$ref, $m)) {
        return 0;
    }
    $letters = strtoupper($m[1]);
    $idx = 0;
    $len = strlen($letters);
    for ($i = 0; $i < $len; $i++) {
        $idx = $idx * 26 + (ord($letters[$i]) - ord('A') + 1);
    }
    return $idx - 1;
}

/**
 * @param ZipArchive $zip
 * @return array
 */
function ppto_xlsx_load_shared_strings($zip) {
    $strings = array();
    $xml = $zip->getFromName('xl/sharedStrings.xml');
    if ($xml === false || $xml === '') {
        return $strings;
    }
    $x = @simplexml_load_string($xml);
    if (!$x || !isset($x->si)) {
        return $strings;
    }
    foreach ($x->si as $si) {
        $t = '';
        if (isset($si->t)) {
            $t = (string)$si->t;
        } elseif (isset($si->r)) {
            foreach ($si->r as $r) {
                if (isset($r->t)) {
                    $t .= (string)$r->t;
                }
            }
        }
        $strings[] = $t;
    }
    return $strings;
}

/**
 * @param ZipArchive $zip
 * @return array
 */
function ppto_xlsx_sheet_paths($zip) {
    $paths = array();
    $map = array();
    $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($rels !== false && $rels !== '') {
        $rx = @simplexml_load_string($rels);
        if ($rx) {
            foreach ($rx->Relationship as $rel) {
                $type = (string)$rel['Type'];
                if (strpos($type, '/worksheet') === false) {
                    continue;
                }
                $id = (string)$rel['Id'];
                $target = str_replace('\\', '/', (string)$rel['Target']);
                if (strpos($target, 'xl/') === 0) {
                    $map[$id] = $target;
                } elseif (strpos($target, '/') === 0) {
                    $map[$id] = ltrim($target, '/');
                } else {
                    $map[$id] = 'xl/' . $target;
                }
            }
        }
    }

    $wb = $zip->getFromName('xl/workbook.xml');
    if ($wb !== false && $wb !== '') {
        $wx = @simplexml_load_string($wb);
        if ($wx && isset($wx->sheets->sheet)) {
            $ns = $wx->getNamespaces(true);
            $rns = isset($ns['r']) ? $ns['r'] : 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
            foreach ($wx->sheets->sheet as $sheet) {
                $attrs = $sheet->attributes($rns);
                if ($attrs && isset($attrs['id'])) {
                    $rid = (string)$attrs['id'];
                    if (isset($map[$rid])) {
                        $paths[] = $map[$rid];
                    }
                }
            }
        }
    }

    if (empty($paths)) {
        for ($i = 1; $i <= 30; $i++) {
            $p = "xl/worksheets/sheet$i.xml";
            if ($zip->locateName($p) !== false) {
                $paths[] = $p;
            }
        }
    }
    return $paths;
}

/**
 * @param SimpleXMLElement $cell
 * @param array $sharedStrings
 * @return string
 */
function ppto_xlsx_cell_value($cell, $sharedStrings) {
    $t = isset($cell['t']) ? (string)$cell['t'] : '';
    if ($t === 'inlineStr') {
        if (isset($cell->is->t)) {
            return (string)$cell->is->t;
        }
        if (isset($cell->is)) {
            return trim((string)$cell->is);
        }
    }
    if (!isset($cell->v)) {
        return '';
    }
    $v = (string)$cell->v;
    if ($t === 's') {
        $idx = (int)$v;
        return isset($sharedStrings[$idx]) ? $sharedStrings[$idx] : '';
    }
    if ($t === 'str') {
        return $v;
    }
    return $v;
}

/**
 * @param ZipArchive $zip
 * @param string $sheetPath
 * @param array $sharedStrings
 * @return array
 */
function ppto_xlsx_parse_sheet_rows($zip, $sheetPath, $sharedStrings) {
    $xml = $zip->getFromName($sheetPath);
    if ($xml === false || $xml === '') {
        return array();
    }
    $sx = @simplexml_load_string($xml);
    if (!$sx || !isset($sx->sheetData->row)) {
        return array();
    }
    $rows = array();
    foreach ($sx->sheetData->row as $row) {
        $line = array();
        foreach ($row->c as $cell) {
            $ref = (string)$cell['r'];
            if ($ref === '') {
                continue;
            }
            $col = ppto_xlsx_col_index($ref);
            $val = trim(ppto_xlsx_cell_value($cell, $sharedStrings));
            if ($val !== '') {
                $line[$col] = $val;
            }
        }
        if (!empty($line)) {
            $rows[] = $line;
        }
    }
    return $rows;
}

/**
 * Lee todas las hojas de un .xlsx como filas indexadas por columna (0 = A).
 *
 * @param string $filepath
 * @return array list of sheets, each sheet = list of row arrays
 */
function ppto_xlsx_leer_filas($filepath) {
    if (!is_readable($filepath) || !class_exists('ZipArchive')) {
        return array();
    }
    $zip = new ZipArchive();
    if ($zip->open($filepath) !== true) {
        return array();
    }
    $shared = ppto_xlsx_load_shared_strings($zip);
    $paths = ppto_xlsx_sheet_paths($zip);
    $sheets = array();
    foreach ($paths as $path) {
        $rows = ppto_xlsx_parse_sheet_rows($zip, $path, $shared);
        if (!empty($rows)) {
            $sheets[] = $rows;
        }
    }
    $zip->close();
    return $sheets;
}

/**
 * @param array $rows
 * @return string
 */
function ppto_xlsx_filas_a_texto($rows) {
    if (!is_array($rows) || empty($rows)) {
        return '';
    }
    $cols = null;
    $tn_dia = 0;
    $dias_lab = 0;
    $grupo_emitido = false;
    $lines = array();

    foreach ($rows as $row) {
        if ($cols === null && ppto_spreadsheet_es_fila_cabecera($row)) {
            $cols = ppto_spreadsheet_detectar_columnas($row);
            continue;
        }
        if ($cols === null) {
            $cols = ppto_spreadsheet_columnas_default();
        }

        $grupo = ppto_spreadsheet_fila_grupo($row);
        if ($grupo !== '') {
            if (!$grupo_emitido || strpos($grupo, 'PRESUPUESTO GRUPO:') === 0) {
                $lines[] = $grupo;
                $grupo_emitido = true;
            }
            continue;
        }
        if (ppto_spreadsheet_fila_ignorar($row, $cols)) {
            continue;
        }
        $sec = ppto_spreadsheet_fila_seccion($row, $cols);
        if ($sec !== null) {
            $lines[] = $sec;
            continue;
        }
        $meta = ppto_spreadsheet_fila_meta($row, $cols);
        if ($meta === null) {
            continue;
        }
        if ((int)$meta['tn_dia'] === 3500) {
            $tn_dia = 3500;
        }
        if ((int)$meta['dias_laborables'] === 22) {
            $dias_lab = 22;
        }
        if (!ppto_pdf_meta_es_rubro_driver($meta, $meta['descripcion'])) {
            continue;
        }
        $lines[] = ppto_pdf_linea_rubro_meta($meta['descripcion'], $meta);
    }

    if ($tn_dia > 0) {
        $dias_mes = $dias_lab > 0 ? $dias_lab : ppto_pdf_dias_mes_ton_default();
        $lines[] = 'tn/BASE ' . (int)$tn_dia . ' ' . (int)$dias_mes . ' dias laborables';
    }
    return implode("\n", $lines);
}

/**
 * @param string $filepath
 * @return string
 */
function ppto_xlsx_extraer_texto($filepath) {
    $best = '';
    $mesesGrupo = 0;
    $sheets = ppto_xlsx_leer_filas($filepath);
    foreach ($sheets as $rows) {
        $cols = ppto_spreadsheet_columnas_default();
        foreach ($rows as $row) {
            if (ppto_spreadsheet_es_fila_cabecera($row)) {
                $cols = ppto_spreadsheet_detectar_columnas($row);
                break;
            }
        }
        $m = ppto_spreadsheet_extraer_meses_grupo($rows, $cols);
        if ($m > $mesesGrupo) {
            $mesesGrupo = $m;
        }

        $chunk = ppto_xlsx_filas_a_texto($rows);
        if ($chunk === '') {
            continue;
        }
        if (strlen($chunk) > strlen($best)) {
            $best = $chunk;
        }
        if (stripos($chunk, 'PRESUPUESTO GRUPO:') !== false && substr_count($chunk, '||') >= 6) {
            $best = $chunk;
            break;
        }
    }
    return ppto_spreadsheet_append_meses_grupo($best, $mesesGrupo);
}
