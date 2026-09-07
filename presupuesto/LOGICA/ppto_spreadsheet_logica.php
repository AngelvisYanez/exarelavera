<?php
/**
 * Convierte hojas Excel/CSV presupuestarias RCET a texto para ppto_pdf_parsear_presupuesto.
 * Lee columnas ANUAL, tn/BASE, Días, meses, Usd/tn y Monto recalculado por fila.
 */

require_once(__DIR__ . '/ppto_pdf_logica.php');

/**
 * @return array
 */
function ppto_spreadsheet_columnas_default() {
    return array(
        'desc' => 2,
        'anual' => 4,
        'tn' => 6,
        'dias' => 7,
        'factor' => 8,
        'meses' => 10,
        'usd' => 11,
        'monto' => 13,
    );
}

/**
 * @param string $ext
 * @return string|false
 */
function ppto_spreadsheet_mime_por_extension($ext) {
    switch (strtolower(trim((string)$ext))) {
        case 'xlsx':
        case 'xltx':
            return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        case 'xlsm':
        case 'xltm':
            return 'application/vnd.ms-excel.sheet.macroEnabled.12';
        case 'xls':
        case 'xlt':
            return 'application/vnd.ms-excel';
        case 'csv':
            return 'text/csv';
        default:
            return false;
    }
}

/**
 * @param array $row
 * @param int $idx
 * @return string
 */
function ppto_spreadsheet_cell($row, $idx) {
    if (!is_array($row) || !array_key_exists($idx, $row)) {
        return '';
    }
    $v = trim((string)$row[$idx]);
    $v = rtrim($v, '_');
    if (function_exists('ppto_pdf_a_utf8')) {
        $v = ppto_pdf_a_utf8($v);
    }
    return trim($v);
}

/**
 * @param string $text
 * @return string
 */
function ppto_spreadsheet_norm_header($text) {
    $text = strtolower(trim(preg_replace('/\s+/', ' ', (string)$text)));
    $text = str_replace(array('á', 'é', 'í', 'ó', 'ú', 'ñ'), array('a', 'e', 'i', 'o', 'u', 'n'), $text);
    return $text;
}

/**
 * @param array $row
 * @return bool
 */
function ppto_spreadsheet_es_fila_cabecera($row) {
    if (!is_array($row)) {
        return false;
    }
    $joined = ppto_spreadsheet_norm_header(implode(' ', array_map('strval', $row)));
    if (strpos($joined, 'anual') === false) {
        return false;
    }
    return (strpos($joined, 'meses') !== false
        || strpos($joined, 'laborables') !== false
        || strpos($joined, 'tn /') !== false
        || strpos($joined, 'recalculado') !== false
        || strpos($joined, 'usd /tn') !== false);
}

/**
 * @param string $msg
 */
function ppto_spreadsheet_set_error($msg) {
    $GLOBALS['ppto_spreadsheet_last_error'] = trim((string)$msg);
}

/**
 * @return string
 */
function ppto_spreadsheet_last_error() {
    return isset($GLOBALS['ppto_spreadsheet_last_error']) ? (string)$GLOBALS['ppto_spreadsheet_last_error'] : '';
}

/**
 * @return string
 */
function ppto_spreadsheet_resolver_vendor() {
    $candidates = array(
        __DIR__ . '/vendor/spreadsheet/SpreadsheetReader.php',
        __DIR__ . '/../../tesoreria/FRONT/vendor/SpreadsheetReader.php',
        __DIR__ . '/../../facturacion/FRONT/vendor/SpreadsheetReader.php',
    );
    foreach ($candidates as $path) {
        $real = realpath($path);
        if ($real && is_readable($real)) {
            return $real;
        }
    }
    return '';
}

/**
 * @param string $filepath
 * @return string
 */
function ppto_spreadsheet_sniff_ext($filepath) {
    $fh = @fopen($filepath, 'rb');
    if (!$fh) {
        return '';
    }
    $head = fread($fh, 4);
    fclose($fh);
    if ($head === "PK\x03\x04") {
        return 'xlsx';
    }
    if ($head === "\xD0\xCF\x11\xE0") {
        return 'xls';
    }
    return '';
}

/**
 * Copia el upload temporal a un archivo con extension real (.xlsx).
 *
 * @param string $filepath
 * @param string $ext
 * @return array
 */
function ppto_spreadsheet_prepare_file($filepath, $ext) {
    $ext = strtolower(trim((string)$ext));
    if ($ext === '') {
        $ext = ppto_spreadsheet_sniff_ext($filepath);
    }
    if ($ext === '' || !is_readable($filepath)) {
        return array('path' => $filepath, 'temp' => false, 'ext' => $ext);
    }
    $currentExt = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    if ($currentExt === $ext) {
        return array('path' => $filepath, 'temp' => false, 'ext' => $ext);
    }
    $dest = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ppto_xl_'
        . md5($filepath . '|' . filesize($filepath) . '|' . filemtime($filepath)) . '.' . $ext;
    if (!@copy($filepath, $dest)) {
        ppto_spreadsheet_set_error('No se pudo preparar copia temporal del Excel.');
        return array('path' => $filepath, 'temp' => false, 'ext' => $ext);
    }
    return array('path' => $dest, 'temp' => true, 'ext' => $ext);
}

/**
 * @param SpreadsheetReader $reader
 * @return string
 */
function ppto_spreadsheet_procesar_reader($reader) {
    $lines = array();
    $cols = null;
    $tn_dia = 0;
    $dias_lab = 0;
    $grupo_emitido = false;

    foreach ($reader as $row) {
        if (!is_array($row)) {
            continue;
        }

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
 * @param array $row
 * @return array
 */
function ppto_spreadsheet_detectar_columnas($row) {
    $map = ppto_spreadsheet_columnas_default();
    if (!is_array($row)) {
        return $map;
    }
    foreach ($row as $idx => $cell) {
        $c = ppto_spreadsheet_norm_header($cell);
        if ($c === 'anual') {
            $map['anual'] = (int)$idx;
        } elseif (strpos($c, 'tn /') === 0 || $c === 'tn/base' || $c === 'tn / base') {
            $map['tn'] = (int)$idx;
        } elseif ($c === 'laborables' || strpos($c, 'dias') === 0) {
            $map['dias'] = (int)$idx;
        } elseif ($c === 'meses') {
            $map['meses'] = (int)$idx;
        } elseif (strpos($c, 'usd /tn') === 0 || strpos($c, 'usd/tn') === 0) {
            $map['usd'] = (int)$idx;
        } elseif (strpos($c, 'recalculado') !== false) {
            $map['monto'] = (int)$idx;
        }
    }
    return $map;
}

/**
 * @param array $row
 * @return string
 */
function ppto_spreadsheet_fila_grupo($row) {
    if (!is_array($row)) {
        return '';
    }
    foreach ($row as $cell) {
        $cell = trim(preg_replace('/\s+/', ' ', (string)$cell));
        if ($cell === '') {
            continue;
        }
        if (preg_match('/PRESUPUESTO\s+GRUPO\s*:?\s*(.+)$/i', $cell, $m)) {
            $rest = trim($m[1]);
            if (preg_match('/^(\d{1,2})\.\s*(.+)$/', $rest, $gm)) {
                return 'PRESUPUESTO GRUPO: ' . str_pad((int)$gm[1], 2, '0', STR_PAD_LEFT) . '. ' . trim($gm[2]);
            }
            return 'PRESUPUESTO GRUPO: ' . $rest;
        }
    }
    return '';
}

/**
 * @param array $row
 * @param array $cols
 * @return string|null
 */
function ppto_spreadsheet_fila_seccion($row, $cols) {
    $letter = strtoupper(ppto_spreadsheet_cell($row, 1));
    $titulo = ppto_spreadsheet_cell($row, $cols['desc']);
    $anual = ppto_pdf_parse_numero(ppto_spreadsheet_cell($row, $cols['anual']));
    if ($anual > 0) {
        return null;
    }
    if (preg_match('/^[A-H]$/', $letter) && $titulo !== '' && !ppto_pdf_es_linea_cabecera_tabla($titulo)) {
        return $letter . '. ' . $titulo;
    }
    $desc = $titulo;
    if ($desc !== '' && ppto_pdf_parse_linea_seccion($desc) !== null) {
        return $desc;
    }
    return null;
}

/**
 * @param array $row
 * @param array $cols
 * @return bool
 */
function ppto_spreadsheet_fila_ignorar($row, $cols) {
    $desc = ppto_spreadsheet_cell($row, $cols['desc']);
    $joined = trim(implode(' ', array_map('strval', $row)));
    if ($joined === '' || preg_match('/^CONSOLIDAR!/i', $joined)) {
        return true;
    }
    if (ppto_pdf_es_linea_cabecera_tabla($joined)) {
        return true;
    }
    if (preg_match('/^(PRESUPUESTO|PERIODO|PRORRATA)/i', $desc)) {
        return true;
    }
    if (strtoupper($desc) === 'MESES') {
        return true;
    }
    if ($desc === '' && ppto_pdf_parse_numero(ppto_spreadsheet_cell($row, $cols['anual'])) > 0) {
        return true;
    }
    return false;
}

/**
 * Lee meses de prorrateo del pie RCET (fila MESES en columna meses, valor en Usd/tn).
 *
 * @param array $rows
 * @param array|null $cols
 * @return int
 */
function ppto_spreadsheet_extraer_meses_grupo($rows, $cols = null) {
    if ($cols === null) {
        $cols = ppto_spreadsheet_columnas_default();
        foreach ((array)$rows as $row) {
            if (ppto_spreadsheet_es_fila_cabecera($row)) {
                $cols = ppto_spreadsheet_detectar_columnas($row);
                break;
            }
        }
    }
    foreach ((array)$rows as $row) {
        $label = strtoupper(trim(ppto_spreadsheet_cell($row, $cols['meses'])));
        if ($label !== 'MESES') {
            continue;
        }
        $val = ppto_pdf_parse_numero(ppto_spreadsheet_cell($row, $cols['usd']));
        if ($val <= 0) {
            $val = ppto_pdf_parse_numero(ppto_spreadsheet_cell($row, $cols['monto']));
        }
        if ($val >= 1 && $val <= 999) {
            return (int)$val;
        }
    }
    return 0;
}

/**
 * @param string $text
 * @return string
 */
function ppto_spreadsheet_append_meses_grupo($text, $meses) {
    $meses = (int)$meses;
    if ($meses < 1 || trim((string)$text) === '') {
        return $text;
    }
    if (preg_match('/^MESES_GRUPO\|\|/m', $text)) {
        return $text;
    }
    return rtrim((string)$text) . "\nMESES_GRUPO||" . $meses;
}

/**
 * @param array $row
 * @param array $cols
 * @return array|null
 */
function ppto_spreadsheet_fila_meta($row, $cols) {
    $desc = ppto_spreadsheet_cell($row, $cols['desc']);
    if ($desc === '' || ppto_pdf_es_linea_cabecera_tabla($desc)) {
        return null;
    }
    if (ppto_pdf_parse_linea_seccion($desc) !== null) {
        return null;
    }

    $anual = ppto_pdf_parse_numero(ppto_spreadsheet_cell($row, $cols['anual']));
    $tn = ppto_pdf_parse_numero(ppto_spreadsheet_cell($row, $cols['tn']));
    $dias = ppto_pdf_parse_numero(ppto_spreadsheet_cell($row, $cols['dias']));
    $factor = ppto_pdf_parse_numero(ppto_spreadsheet_cell($row, $cols['factor']));
    $meses = ppto_pdf_parse_numero(ppto_spreadsheet_cell($row, $cols['meses']));
    $usd = ppto_pdf_parse_numero(ppto_spreadsheet_cell($row, $cols['usd']));
    $monto = ppto_pdf_parse_numero(ppto_spreadsheet_cell($row, $cols['monto']));

    if ($anual <= 0 && $monto <= 0 && $factor <= 0) {
        return null;
    }
    if ($anual > 0 && $anual < 50 && $monto <= 0 && $factor <= 0) {
        return null;
    }

    if ($dias <= 0) {
        $dias = ppto_pdf_dias_laborables_default();
    }
    if ($meses <= 0) {
        $meses = 12;
    }
    if ($factor <= 0 && $usd > 0) {
        $factor = round($usd * 12, 6);
    }
    if ($factor <= 0 && $anual > 0) {
        $tonEst = ($tn > 0 && $dias > 0) ? ($tn * $dias) : ppto_pdf_ton_base_mes_default();
        if ($tonEst > 0) {
            $factor = round($anual / $tonEst, 6);
        }
    }

    return array(
        'descripcion' => $desc,
        'factor_anual' => $factor,
        'presup_anual_pdf' => $anual,
        'tn_dia' => $tn,
        'dias_laborables' => $dias,
        'meses' => $meses,
        'usd_ton' => $usd,
        'monto_recalc' => $monto,
    );
}

/**
 * @param array $cells
 * @return string
 */
function ppto_spreadsheet_row_to_line($cells) {
    if (!is_array($cells)) {
        return '';
    }
    $cols = ppto_spreadsheet_columnas_default();
    $sec = ppto_spreadsheet_fila_seccion($cells, $cols);
    if ($sec !== null) {
        return $sec;
    }
    $grupo = ppto_spreadsheet_fila_grupo($cells);
    if ($grupo !== '') {
        return $grupo;
    }
    if (ppto_spreadsheet_fila_ignorar($cells, $cols)) {
        return '';
    }
    $meta = ppto_spreadsheet_fila_meta($cells, $cols);
    if ($meta === null) {
        return '';
    }
    return ppto_pdf_linea_rubro_meta($meta['descripcion'], $meta);
}

/**
 * @param string $filepath
 * @param string $ext
 * @param string $originalName
 * @param string $mimeType
 * @return string
 */
function ppto_spreadsheet_extraer_texto($filepath, $ext = '', $originalName = '', $mimeType = '') {
    ppto_spreadsheet_set_error('');

    $ext = strtolower(trim((string)$ext));
    if ($ext === '') {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    }
    $prepared = ppto_spreadsheet_prepare_file($filepath, $ext);
    $readPath = $prepared['path'];
    $ext = $prepared['ext'] !== '' ? $prepared['ext'] : $ext;
    if ($ext === '') {
        $ext = ppto_spreadsheet_sniff_ext($readPath);
    }

    $best = '';
    if (in_array($ext, array('xlsx', 'xlsm', 'xltx'), true) && class_exists('ZipArchive')) {
        require_once(__DIR__ . '/ppto_xlsx_logica.php');
        $best = ppto_xlsx_extraer_texto($readPath);
    }

    if ($best === '') {
        $vendor = ppto_spreadsheet_resolver_vendor();
        if ($vendor === '') {
            ppto_spreadsheet_set_error('No se encontro el lector de Excel en el servidor.');
            if (!empty($prepared['temp']) && is_file($readPath)) {
                @unlink($readPath);
            }
            return '';
        }

        require_once($vendor);

        $originalName = trim((string)$originalName);
        if ($originalName === '') {
            $originalName = basename($readPath);
        }
        if ($ext !== '' && strpos($originalName, '.') === false) {
            $originalName .= '.' . $ext;
        }
        $mimeType = trim((string)$mimeType);
        if ($mimeType === '' && $ext !== '') {
            $mimeGuess = ppto_spreadsheet_mime_por_extension($ext);
            if ($mimeGuess !== false) {
                $mimeType = $mimeGuess;
            }
        }

        try {
            $reader = new SpreadsheetReader($readPath, $originalName, $mimeType !== '' ? $mimeType : false);
        } catch (Exception $e) {
            ppto_spreadsheet_set_error('No se pudo abrir el Excel: ' . $e->getMessage());
            if (!empty($prepared['temp']) && is_file($readPath)) {
                @unlink($readPath);
            }
            return '';
        }

        $sheets = method_exists($reader, 'Sheets') ? $reader->Sheets() : array(0 => 'Sheet1');
        if (!is_array($sheets) || empty($sheets)) {
            $sheets = array(0 => 'Sheet1');
        }

        foreach ($sheets as $idx => $sheetName) {
            if ((int)$idx > 0 && method_exists($reader, 'ChangeSheet')) {
                if (!$reader->ChangeSheet($idx)) {
                    continue;
                }
            } elseif ((int)$idx > 0) {
                continue;
            }

            $chunk = ppto_spreadsheet_procesar_reader($reader);
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
    }

    if (!empty($prepared['temp']) && is_file($readPath)) {
        @unlink($readPath);
    }

    if ($best === '') {
        ppto_spreadsheet_set_error('La hoja no tiene filas RCET con ANUAL, secciones A/B/C y rubros con montos.');
    }

    return $best;
}
